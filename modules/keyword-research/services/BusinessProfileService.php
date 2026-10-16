<?php

namespace Modules\KeywordResearch\Services;

use Core\Database;
use Services\AiService;
use Services\SafeScraper;

/**
 * Profilo dell'attività: il punto di partenza dei 3 strumenti di AI Keywords.
 *
 * Un testo di 5-8 righe (cosa fa, dove, per chi, servizi), uno per sito: sta in projects.description del progetto
 * globale (così servirà anche agli altri moduli), o in kr_projects.description per un progetto senza progetto globale.
 * Nasce dal profilo del GEO Audit, dal sito o da un testo libero. Dal profilo l'AI propone parole di partenza,
 * categorie del blog, tipo di sito e target, salvati in kr_projects.suggestions e rifatti solo se il profilo cambia.
 * Tutte le chiamate AI qui sono gratis per l'utente (charge_credits => false).
 */
class BusinessProfileService
{
    private const SLUG = 'keyword-research';
    private const MAX_PAGES = 5;
    private const SITE_TYPES = ['corporate', 'ecommerce', 'blog', 'saas', 'local', 'portfolio'];
    private const TARGETS = ['B2B', 'B2C', 'Both'];

    public const LANGUAGE_NAMES = ['it' => 'italiano', 'en' => 'inglese', 'de' => 'tedesco', 'fr' => 'francese', 'es' => 'spagnolo'];
    public const COUNTRY_NAMES = ['IT' => 'Italia', 'US' => 'Stati Uniti', 'GB' => 'Regno Unito', 'DE' => 'Germania', 'FR' => 'Francia', 'ES' => 'Spagna'];

    /**
     * Tutto quello che serve al riquadro "La tua attività" di un wizard.
     */
    public function state(array $project): array
    {
        $profile = $this->getProfile($project);
        $suggestions = $this->getSuggestions($project, $profile);

        return [
            'profile' => $profile,
            'suggestions' => $suggestions,
            'geo_facts' => $this->geoFactsCount($project),
            'domain' => $this->domain($project),
        ];
    }

    public function getProfile(array $project): string
    {
        if (!empty($project['global_project_id'])) {
            $row = Database::fetch("SELECT description FROM projects WHERE id = ?", [(int) $project['global_project_id']]);
            $text = trim((string) ($row['description'] ?? ''));
        } else {
            $text = trim((string) ($project['description'] ?? ''));
        }
        // La descrizione facoltativa del progetto ("sito del cliente") non basta come profilo: si riparte dalla scelta
        return mb_strlen($text) >= 20 ? $text : '';
    }

    public function saveProfile(array $project, string $text): void
    {
        $text = trim($text);
        if (!empty($project['global_project_id'])) {
            Database::update('projects', ['description' => $text], 'id = ?', [(int) $project['global_project_id']]);
        } else {
            Database::update('kr_projects', ['description' => $text], 'id = ?', [(int) $project['id']]);
        }
    }

    /**
     * Proposte salvate, se valgono ancora per questo profilo.
     */
    public function getSuggestions(array $project, string $profile): ?array
    {
        if ($profile === '' || empty($project['suggestions'])) {
            return null;
        }
        $s = json_decode((string) $project['suggestions'], true);
        if (!is_array($s) || ($s['profile_hash'] ?? '') !== md5($profile)) {
            return null;
        }
        return $s;
    }

    public function domain(array $project): ?string
    {
        if (empty($project['global_project_id'])) {
            return null;
        }
        $row = Database::fetch("SELECT domain FROM projects WHERE id = ?", [(int) $project['global_project_id']]);
        $domain = trim((string) ($row['domain'] ?? ''));
        return $domain !== '' ? $domain : null;
    }

    /**
     * Righe del profilo GEO Audit utili a descrivere l'attività (confermate o corrette dall'utente).
     */
    public function geoFacts(array $project): array
    {
        if (empty($project['global_project_id'])) {
            return [];
        }
        try {
            return Database::fetchAll(
                "SELECT f.category, COALESCE(NULLIF(f.corrected_text, ''), f.text) AS text
                 FROM ar_profile_facts f JOIN ar_projects p ON p.id = f.project_id
                 WHERE p.global_project_id = ? AND f.status IN ('confirmed', 'corrected')
                   AND f.category IN ('identity', 'activity', 'alias', 'fact')
                 ORDER BY FIELD(f.category, 'identity', 'activity', 'alias', 'fact'), f.sort_order, f.id",
                [(int) $project['global_project_id']]
            );
        } catch (\Throwable $e) {
            return []; // modulo GEO non installato
        }
    }

    public function geoFactsCount(array $project): int
    {
        return count($this->geoFacts($project));
    }

    /**
     * Profilo dalle righe confermate del GEO Audit.
     */
    public function profileFromGeo(int $userId, array $project): array
    {
        $facts = $this->geoFacts($project);
        if (empty($facts)) {
            return ['success' => false, 'error' => 'Nel GEO Audit di questo progetto non ci sono ancora informazioni confermate.'];
        }
        $lines = array_map(fn($f) => '- ' . trim((string) $f['text']), $facts);
        return $this->writeProfile($userId, $project, "INFORMAZIONI CONFERMATE DAL CLIENTE (profilo GEO Audit):\n" . implode("\n", $lines));
    }

    /**
     * Profilo dal sito: home + fino a 4 pagine interne.
     */
    public function profileFromSite(int $userId, array $project): array
    {
        $domain = $this->domain($project);
        if (!$domain) {
            return ['success' => false, 'error' => 'Il progetto non ha un sito: scrivi tu la descrizione.'];
        }
        $home = preg_match('#^https?://#i', $domain) ? $domain : 'https://' . $domain;

        $scraper = new SafeScraper();
        $texts = [];
        $first = $this->readPage($scraper, $home);
        if ($first === null) {
            Database::reconnect();
            return ['success' => false, 'error' => 'Non riesco a leggere il sito ' . $domain . '. Scrivi tu la descrizione.'];
        }
        $texts[$first['url']] = $first;

        foreach ($this->pickInternalPages($scraper, $first['url']) as $url) {
            if (count($texts) >= self::MAX_PAGES) {
                break;
            }
            $page = $this->readPage($scraper, $url);
            if ($page !== null && !isset($texts[$page['url']])) {
                $texts[$page['url']] = $page;
            }
        }
        Database::reconnect();

        $source = "PAGINE DEL SITO {$domain}:\n";
        foreach ($texts as $url => $t) {
            $source .= "\n### {$url}\nTitolo: {$t['title']}\n" . ($t['description'] ? "Descrizione: {$t['description']}\n" : '') . "Testo: {$t['content']}\n";
        }
        return $this->writeProfile($userId, $project, $source);
    }

    /**
     * Profilo scritto dall'utente: si salva così com'è.
     */
    public function profileFromText(int $userId, array $project, string $text): array
    {
        $text = trim($text);
        if (mb_strlen($text) < 20) {
            return ['success' => false, 'error' => 'Scrivi almeno una frase: cosa fai, dove e per chi.'];
        }
        $this->saveProfile($project, mb_substr($text, 0, 3000));
        return $this->suggest($userId, $project, true);
    }

    /**
     * Proposte dal profilo (parole di partenza, categorie, tipo di sito, target). Rifatte solo se il profilo cambia.
     */
    public function suggest(int $userId, array $project, bool $force = false): array
    {
        $project = Database::fetch("SELECT * FROM kr_projects WHERE id = ?", [(int) $project['id']]) ?: $project;
        $profile = $this->getProfile($project);
        if ($profile === '') {
            return ['success' => false, 'error' => 'Manca il profilo dell\'attività.'];
        }
        if (!$force && ($cached = $this->getSuggestions($project, $profile))) {
            return ['success' => true, 'profile' => $profile, 'suggestions' => $cached];
        }

        $country = self::COUNTRY_NAMES[$project['default_location'] ?? 'IT'] ?? 'Italia';
        $language = self::LANGUAGE_NAMES[$project['default_language'] ?? 'it'] ?? 'italiano';

        $system = "Sei un consulente SEO. Dal profilo di un'attività proponi da dove partire per una ricerca keyword su Google. Rispondi solo con JSON valido.";
        $prompt = <<<PROMPT
PROFILO DELL'ATTIVITÀ:
{$profile}

PAESE DELLE RICERCHE: {$country}
LINGUA DELLE RICERCHE: {$language}

Proponi:
1. "seeds": 5 parole di partenza per Google Keyword Planner. Sono le ricerche che farebbe un potenziale cliente per
   trovare questa attività: servizio o prodotto + luogo quando l'attività è locale (es. "noleggio barche sorrento").
   2-5 parole ciascuna, in {$language}, minuscole, senza nome del marchio, una per servizio/prodotto principale,
   niente doppioni o varianti della stessa ricerca.
2. "categories": 5 categorie per il blog del sito: argomenti su cui scrivere articoli utili ai clienti (non i servizi
   venduti), 1-4 parole ciascuna, in {$language}.
3. "site_type": uno tra corporate, ecommerce, blog, saas, local, portfolio.
4. "target": B2B, B2C o Both.

JSON: {"seeds": ["..."], "categories": ["..."], "site_type": "...", "target": "..."}
PROMPT;

        $ai = new AiService(self::SLUG);
        $result = $ai->complete($userId, [['role' => 'user', 'content' => $prompt]], [
            'system' => $system,
            'max_tokens' => 1000,
            'charge_credits' => false,
        ], self::SLUG);
        Database::reconnect();

        if (!empty($result['error']) || empty($result['result'])) {
            return ['success' => false, 'error' => 'L\'AI non ha risposto, riprova tra poco.'];
        }
        $data = $this->parseJson((string) $result['result']);
        $seeds = $this->cleanList($data['seeds'] ?? [], 5);
        if (empty($seeds)) {
            return ['success' => false, 'error' => 'L\'AI non ha proposto parole, riprova tra poco.'];
        }

        $suggestions = [
            'seeds' => $seeds,
            'categories' => $this->cleanList($data['categories'] ?? [], 6),
            'site_type' => in_array($data['site_type'] ?? '', self::SITE_TYPES, true) ? $data['site_type'] : 'corporate',
            'target' => in_array($data['target'] ?? '', self::TARGETS, true) ? $data['target'] : 'Both',
            'profile_hash' => md5($profile),
        ];
        Database::update('kr_projects', ['suggestions' => json_encode($suggestions, JSON_UNESCAPED_UNICODE)], 'id = ?', [(int) $project['id']]);

        return ['success' => true, 'profile' => $profile, 'suggestions' => $suggestions];
    }

    // =========================================================================

    /**
     * L'AI scrive il profilo da una fonte (righe GEO o pagine del sito), lo salva e fa le proposte.
     */
    private function writeProfile(int $userId, array $project, string $source): array
    {
        $system = "Scrivi profili d'attività brevi e concreti per chi deve fare una ricerca keyword. Solo fatti presenti nella fonte, niente marketing.";
        $prompt = <<<PROMPT
{$source}

Scrivi in italiano il PROFILO DELL'ATTIVITÀ in 5-8 righe di testo semplice (niente elenchi puntati, niente markdown):
- chi è e cosa fa;
- dove lavora (città, zona) se è un'attività locale;
- i servizi o prodotti principali, con i dettagli che contano per chi cerca (es. con o senza patente, prezzi indicativi);
- per chi (tipo di clienti);
- lingua del sito se non è l'italiano.
Usa solo quello che c'è nella fonte. Rispondi solo col testo del profilo.
PROMPT;

        $ai = new AiService(self::SLUG);
        $result = $ai->complete($userId, [['role' => 'user', 'content' => $prompt]], [
            'system' => $system,
            'max_tokens' => 1200,
            'charge_credits' => false,
        ], self::SLUG);
        Database::reconnect();

        $text = trim((string) ($result['result'] ?? ''));
        if (!empty($result['error']) || mb_strlen($text) < 40) {
            return ['success' => false, 'error' => 'L\'AI non è riuscita a scrivere il profilo, riprova tra poco.'];
        }
        $text = trim(preg_replace('/^#+\s*.*\n/u', '', $text)); // eventuale titolo markdown
        $this->saveProfile($project, $text);

        return $this->suggest($userId, $project, true);
    }

    private function readPage(SafeScraper $scraper, string $url): ?array
    {
        try {
            $res = $scraper->scrape($url);
        } catch (\Throwable $e) {
            return null;
        }
        $content = preg_replace('/\s+/u', ' ', (string) ($res['content'] ?? ''));
        if (!empty($res['error']) || mb_strlen($content) < 150) {
            return null;
        }
        return [
            'url' => (string) ($res['url'] ?? $url),
            'title' => mb_substr((string) ($res['title'] ?? ''), 0, 200),
            'description' => mb_substr((string) ($res['description'] ?? ''), 0, 300),
            'content' => mb_substr($content, 0, 2500),
        ];
    }

    /**
     * Pagine interne della home da leggere: quelle del menu (indirizzi corti), non legali, non carrello/account.
     */
    private function pickInternalPages(SafeScraper $scraper, string $homeUrl): array
    {
        $raw = $scraper->fetchRaw($homeUrl);
        $html = (string) ($raw['body'] ?? '');
        if ($html === '' || !preg_match_all('/<a\s[^>]*href=["\']([^"\'#]+)["\']/i', $html, $m)) {
            return [];
        }
        $host = preg_replace('/^www\./', '', (string) parse_url($homeUrl, PHP_URL_HOST));
        $skip = '#(privacy|cookie|termini|terms|condizioni|legal|note-legali|login|account|cart|carrello|checkout|wishlist|wp-|feed|tag/|author/|\.(jpg|jpeg|png|webp|gif|pdf|zip)$)#i';
        $pages = [];
        foreach ($m[1] as $href) {
            $abs = $this->absoluteUrl(html_entity_decode($href), $homeUrl);
            if ($abs === null) {
                continue;
            }
            $h = preg_replace('/^www\./', '', (string) parse_url($abs, PHP_URL_HOST));
            $path = rtrim((string) parse_url($abs, PHP_URL_PATH), '/');
            if ($h !== $host || $path === '' || preg_match($skip, $path) || substr_count($path, '/') > 2 || isset($pages[$path])) {
                continue;
            }
            $pages[$path] = strtok($abs, '?');
        }
        return array_slice(array_values($pages), 0, 8);
    }

    private function absoluteUrl(string $href, string $base): ?string
    {
        $href = trim($href);
        if ($href === '' || preg_match('#^(mailto:|tel:|javascript:|whatsapp:)#i', $href)) {
            return null;
        }
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }
        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($base, PHP_URL_HOST);
        if (str_starts_with($href, '//')) {
            return $scheme . ':' . $href;
        }
        if (str_starts_with($href, '/')) {
            return "{$scheme}://{$host}{$href}";
        }
        return "{$scheme}://{$host}/" . ltrim($href, './');
    }

    private function cleanList(mixed $list, int $max): array
    {
        $out = [];
        foreach ((array) $list as $item) {
            $v = trim(mb_strtolower((string) $item));
            if ($v !== '' && mb_strlen($v) <= 80 && !in_array($v, $out, true)) {
                $out[] = $v;
            }
        }
        return array_slice($out, 0, $max);
    }

    private function parseJson(string $text): array
    {
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', $text));
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false) {
            return [];
        }
        return json_decode(substr($text, $start, $end - $start + 1), true) ?: [];
    }
}
