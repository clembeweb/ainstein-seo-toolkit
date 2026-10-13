<?php

namespace Services;

use Core\Database;
use Modules\AiReputation\Services\GeoTechService;

/**
 * Controllo AI gratuito della home: "le AI leggono il tuo sito?".
 * Riusa GeoTechService::check() dell'AI GEO Audit (nessuna chiamata AI) con uno scraper che non può raggiungere
 * indirizzi interni (anti-SSRF), limiti per visitatore e cache per sito. Al pubblico escono solo voto e highlight;
 * i dettagli si vedono su /check/{token} dopo l'iscrizione.
 * Design: docs/plans/2026-10-13-landing-semrush-design.md
 */
class PublicAiCheckService
{
    public const PER_HOUR = 3;
    public const PER_DAY = 10;
    public const GLOBAL_PER_DAY = 300;
    public const REUSE_HOURS = 6;

    // ------------------------------------------------------------------ parti pure

    /** URL pulito e accettabile per un controllo pubblico, oppure null. */
    public static function normalizeUrl(string $in): ?string
    {
        $in = trim($in);
        if ($in === '') {
            return null;
        }
        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $in)) {
            $in = 'https://' . $in;
        }
        $p = parse_url($in);
        if (!$p || empty($p['host']) || !in_array(strtolower($p['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }
        if (isset($p['user']) || isset($p['pass'])) {
            return null;
        }
        if (isset($p['port']) && !in_array((int) $p['port'], [80, 443], true)) {
            return null;
        }
        $host = strtolower($p['host']);
        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) {
            if (!self::isPublicIp(trim($host, '[]'))) {
                return null;
            }
        } elseif (!str_contains($host, '.') || !preg_match('/^[a-z0-9.-]+$/', $host)) {
            return null;
        }
        return strtolower($p['scheme']) . '://' . $host . (isset($p['port']) ? ':' . $p['port'] : '') . ($p['path'] ?? '') . (isset($p['query']) ? '?' . $p['query'] : '');
    }

    /** true solo per IP raggiungibili da Internet (niente privati, loopback, link-local, riservati, CGNAT). */
    public static function isPublicIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $n = ip2long($ip);
            foreach ([['100.64.0.0', 10], ['169.254.0.0', 16], ['0.0.0.0', 8], ['127.0.0.0', 8], ['192.0.0.0', 24], ['198.18.0.0', 15]] as [$net, $bits]) {
                $mask = -1 << (32 - $bits);
                if (($n & $mask) === (ip2long($net) & $mask)) {
                    return false;
                }
            }
            return true;
        }
        $lower = strtolower($ip);
        return !($lower === '::1' || str_starts_with($lower, 'fc') || str_starts_with($lower, 'fd') || str_starts_with($lower, 'fe8')
            || str_starts_with($lower, 'fe9') || str_starts_with($lower, 'fea') || str_starts_with($lower, 'feb') || str_starts_with($lower, '::ffff:'));
    }

    /** Voto su 5 punti dai findings di GeoTechService. */
    public static function score(array $findings, bool $error = false): array
    {
        $by = fn(string $area) => array_values(array_filter($findings, fn($f) => ($f['area'] ?? '') === $area));
        $levels = fn(string $area) => array_column($by($area), 'level');
        $items = [
            'access' => !$error && in_array('ok', $levels('access'), true) && !in_array('bad', $levels('access'), true),
            'readable' => !$error && !in_array('bad', $levels('readable'), true),
            'schema' => !$error && array_intersect($levels('schema'), ['ok', 'warn']) !== [],
            'company' => !$error && $levels('theme:company') !== [] && !in_array('bad', $levels('theme:company'), true),
            'sitemap' => !$error && !array_filter($by('files'), fn($f) => $f['level'] === 'warn' && stripos($f['title'], 'sitemap') !== false),
        ];
        return ['score' => count(array_filter($items)), 'total' => count($items), 'items' => $items];
    }

    /** Titoli dei temi "dubbi delle AI" detti come frase, per esito (i titoli interni sono troppo secchi per il pubblico). */
    public const THEME_TITLES = [
        'company' => ['bad' => 'Partita IVA e ragione sociale non trovate', 'warn' => 'Partita IVA solo nel testo, non nei dati per le AI', 'ok' => 'Dati societari presenti'],
        'reviews' => ['bad' => 'Nessun collegamento alle recensioni (Google, TripAdvisor…)', 'warn' => 'Manca il collegamento alle recensioni Google', 'ok' => 'Recensioni collegate al sito'],
        'identity' => ['bad' => 'Contatti e indirizzo poco chiari per le AI', 'warn' => 'Contatti presenti ma non coerenti', 'ok' => 'Contatti e indirizzo chiari'],
    ];

    /** Titolo leggibile di un finding (i temi "dubbi delle AI" diventano frasi). */
    public static function title(array $f): string
    {
        $area = (string) ($f['area'] ?? '');
        $theme = str_starts_with($area, 'theme:') ? substr($area, 6) : null;
        return $theme !== null ? (self::THEME_TITLES[$theme][(string) ($f['level'] ?? '')] ?? (string) $f['title']) : (string) $f['title'];
    }

    /** Solo esito e titolo (mai i dettagli), senza le note informative, al massimo 6. */
    public static function highlights(array $findings): array
    {
        $out = [];
        foreach ($findings as $f) {
            $level = (string) ($f['level'] ?? '');
            if ($level === 'info') {
                continue;
            }
            $out[] = ['level' => $level, 'title' => self::title($f)];
        }
        return array_slice($out, 0, 6);
    }

    // ------------------------------------------------------------------ esecuzione

    /**
     * Esegue (o riusa) il controllo. Ritorna ['token','host','score','total','highlights'] oppure ['error' => messaggio].
     */
    public function run(string $rawUrl, string $ip): array
    {
        $url = self::normalizeUrl($rawUrl);
        if ($url === null) {
            return ['error' => 'Indirizzo non valido. Scrivi il sito così: miosito.it'];
        }
        $host = preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST));
        $ipHash = hash('sha256', $ip . '|' . (getenv('APP_KEY') ?: 'ainstein-public-check'));

        $hour = (int) Database::fetchColumn("SELECT COUNT(*) FROM public_ai_checks WHERE ip_hash = ? AND created_at > NOW() - INTERVAL 1 HOUR", [$ipHash]);
        $day = (int) Database::fetchColumn("SELECT COUNT(*) FROM public_ai_checks WHERE ip_hash = ? AND created_at > NOW() - INTERVAL 1 DAY", [$ipHash]);
        if ($hour >= self::PER_HOUR || $day >= self::PER_DAY) {
            return ['error' => 'Hai già fatto parecchi controlli: riprova più tardi, oppure registrati per controllare tutti i siti che vuoi.', 'limit' => true];
        }
        if ((int) Database::fetchColumn("SELECT COUNT(*) FROM public_ai_checks WHERE created_at > NOW() - INTERVAL 1 DAY") >= self::GLOBAL_PER_DAY) {
            return ['error' => 'Oggi i controlli gratuiti sono finiti: riprova domani, oppure registrati.', 'limit' => true];
        }

        // Stesso sito controllato da poco: niente nuove richieste al sito, nuovo token sugli stessi dati
        $recent = Database::fetch("SELECT url, result FROM public_ai_checks WHERE host = ? AND created_at > NOW() - INTERVAL " . self::REUSE_HOURS . " HOUR ORDER BY id DESC LIMIT 1", [$host]);
        $result = $recent ? (json_decode((string) $recent['result'], true) ?: null) : null;
        if (!$result) {
            $result = (new GeoTechService(new SafeScraper()))->check($url);
            Database::reconnect();
        }
        $findings = GeoTechService::findings($result, 'company');
        $score = self::score($findings, !empty($result['error']));
        $token = bin2hex(random_bytes(16));
        Database::insert('public_ai_checks', [
            'token' => $token,
            'url' => mb_substr($url, 0, 500),
            'host' => mb_substr($host, 0, 255),
            'result' => json_encode($result, JSON_UNESCAPED_UNICODE),
            'score' => $score['score'],
            'ip_hash' => $ipHash,
        ]);
        return [
            'token' => $token,
            'host' => $host,
            'score' => $score['score'],
            'total' => $score['total'],
            'error_site' => !empty($result['error']),
            'highlights' => self::highlights($findings),
            'seconds' => $result['seconds'] ?? null,
            'bots' => count(GeoTechService::BOTS),
        ];
    }

    /** Controllo completo per la pagina dei dettagli (login richiesto); lo lega all'utente la prima volta. */
    public static function forUser(string $token, int $userId): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }
        $row = Database::fetch("SELECT * FROM public_ai_checks WHERE token = ?", [$token]);
        if (!$row) {
            return null;
        }
        if ($row['user_id'] === null) {
            Database::execute("UPDATE public_ai_checks SET user_id = ? WHERE id = ? AND user_id IS NULL", [$userId, $row['id']]);
        } elseif ((int) $row['user_id'] !== $userId) {
            return null;
        }
        $result = json_decode((string) $row['result'], true) ?: [];
        $findings = GeoTechService::findings($result, 'company');
        return ['row' => $row, 'result' => $result, 'findings' => $findings, 'score' => self::score($findings, !empty($result['error']))];
    }
}
