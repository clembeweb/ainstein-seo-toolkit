<?php

namespace Modules\AiReputation\Services;

use Core\Database;
use Services\ScraperService;

/**
 * GeoTechService - diagnosi tecnica GEO: le AI riescono a entrare nel sito, a leggerlo e a capire chi è il soggetto?
 *
 * Nessuna chiamata AI (gratis). Serve a dare la CAUSA dei dubbi delle AI (ADR-016, spec
 * docs/superpowers/specs/2026-10-13-ai-reputation-diagnosi-tecnica-design.md): il dato manca, c'è ma non nei dati
 * strutturati, oppure c'è ma i crawler AI non entrano. Le richieste usano lo user-agent vero di ogni crawler:
 * il robots.txt da solo non mostra i blocchi di server, CDN e firewall.
 */
class GeoTechService
{
    /** Crawler AI: nome, di chi è, a cosa serve, user-agent per la richiesta (null = solo robots.txt), token robots */
    public const BOTS = [
        'oai-searchbot' => ['name' => 'OAI-SearchBot', 'owner' => 'OpenAI', 'purpose' => 'risposte di ChatGPT con ricerca', 'ua' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; OAI-SearchBot/1.0; +https://openai.com/searchbot', 'token' => 'oai-searchbot'],
        'chatgpt-user' => ['name' => 'ChatGPT-User', 'owner' => 'OpenAI', 'purpose' => 'pagine aperte da ChatGPT per un utente', 'ua' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot', 'token' => 'chatgpt-user'],
        'gptbot' => ['name' => 'GPTBot', 'owner' => 'OpenAI', 'purpose' => 'addestramento dei modelli OpenAI', 'ua' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; GPTBot/1.2; +https://openai.com/gptbot', 'token' => 'gptbot'],
        'perplexitybot' => ['name' => 'PerplexityBot', 'owner' => 'Perplexity', 'purpose' => 'risposte di Perplexity', 'ua' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)', 'token' => 'perplexitybot'],
        'claude-searchbot' => ['name' => 'Claude-SearchBot', 'owner' => 'Anthropic', 'purpose' => 'risposte di Claude con ricerca', 'ua' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-SearchBot/1.0; +Claude-SearchBot@anthropic.com)', 'token' => 'claude-searchbot'],
        'claudebot' => ['name' => 'ClaudeBot', 'owner' => 'Anthropic', 'purpose' => 'addestramento dei modelli Anthropic', 'ua' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)', 'token' => 'claudebot'],
        'bingbot' => ['name' => 'Bingbot', 'owner' => 'Microsoft', 'purpose' => 'indice Bing, usato anche da ChatGPT e Copilot', 'ua' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'token' => 'bingbot'],
        'google-extended' => ['name' => 'Google-Extended', 'owner' => 'Google', 'purpose' => 'uso dei contenuti per Gemini (solo robots.txt)', 'ua' => null, 'token' => 'google-extended'],
    ];

    /** Crawler che servono a FARSI CITARE (le risposte); gli altri sono addestramento: bloccarli è una scelta legittima */
    public const ANSWER_BOTS = ['oai-searchbot', 'chatgpt-user', 'perplexitybot', 'claude-searchbot', 'bingbot'];

    private ScraperService $scraper;

    public function __construct(?ScraperService $scraper = null)
    {
        $this->scraper = $scraper ?? new ScraperService();
    }

    /**
     * Esegue la diagnosi sul sito. Ritorna sempre un array (con 'error' se il sito non risponde).
     */
    public function check(string $website): array
    {
        $website = trim($website);
        if ($website !== '' && !preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }
        $started = microtime(true);
        $result = ['url' => $website, 'checked_at' => date('Y-m-d H:i:s')];

        // 1. Home col browser: è il riferimento (se non entra nemmeno il browser, i blocchi bot non sono verificabili)
        $home = $this->get($website, null);
        $result['home'] = [
            'final_url' => $home['final_url'] ?? $website,
            'http_code' => $home['http_code'] ?? 0,
            'ok' => self::isOk($home),
        ];
        if (!$result['home']['ok']) {
            $result['error'] = 'Il sito non risponde normalmente al nostro controllo (HTTP ' . ($home['http_code'] ?? 'errore') . '): i risultati sui crawler non sono verificabili.';
        }
        $base = self::origin($result['home']['final_url']);
        $homeHtml = $result['home']['ok'] ? (string) $home['body'] : '';

        // 2. robots.txt
        $robots = $this->get($base . '/robots.txt', null);
        $robotsTxt = (self::isOk($robots) && !self::looksHtml((string) $robots['body'])) ? (string) $robots['body'] : null;
        $result['robots'] = ['found' => $robotsTxt !== null, 'sitemaps' => $robotsTxt ? self::robotsSitemaps($robotsTxt) : []];

        // 3. Crawler AI: robots.txt + richiesta vera
        $result['bots'] = [];
        // Se lo scraper sa fare richieste in parallelo (SafeScraper del controllo pubblico), le 7 richieste dei
        // crawler partono insieme: su un sito lento (6 s a pagina) si risparmiano ~35 s. Altrimenti una per volta.
        $pre = [];
        if ($result['home']['ok'] && method_exists($this->scraper, 'fetchMany')) {
            $batch = [];
            foreach (self::BOTS as $key => $bot) {
                if ($bot['ua'] !== null) {
                    $batch[$key] = [$result['home']['final_url'], self::botOptions($bot['ua'])];
                }
            }
            $pre = $this->scraper->fetchMany($batch);
        }
        foreach (self::BOTS as $key => $bot) {
            $rule = $robotsTxt !== null ? self::robotsVerdict($robotsTxt, $bot['token']) : 'no_robots';
            $row = ['robots' => $rule, 'http_code' => null, 'verdict' => null];
            if ($bot['ua'] !== null && $result['home']['ok']) {
                $r = isset($pre[$key]) ? (!empty($pre[$key]['error']) ? ['http_code' => 0, 'body' => '', 'final_url' => $result['home']['final_url']] : $pre[$key])
                    : $this->get($result['home']['final_url'], $bot['ua']);
                $row['http_code'] = $r['http_code'] ?? 0;
                $serverBlocked = !self::isOk($r);
                $row['verdict'] = $serverBlocked ? 'blocked_server' : ($rule === 'blocked' ? 'blocked_robots' : 'ok');
            } elseif ($bot['ua'] !== null) {
                $row['verdict'] = $rule === 'blocked' ? 'blocked_robots' : 'unverifiable';
            } else {
                $row['verdict'] = $rule === 'blocked' ? 'blocked_robots' : 'ok';
            }
            $result['bots'][$key] = $row;
        }

        // 4. Pagine Chi siamo / Contatti (dai link della home)
        $pages = ['home' => $homeHtml];
        foreach (self::findPages($homeHtml, $result['home']['final_url']) as $kind => $url) {
            $r = $this->get($url, null);
            if (self::isOk($r)) {
                $pages[$kind] = (string) $r['body'];
                $result['pages'][$kind] = $url;
            }
        }

        // 5. Leggibilità, dati strutturati, fatti visibili
        $homeText = self::visibleText($homeHtml);
        $result['readable'] = ['words' => str_word_count($homeText, 0, 'àèéìòùÀÈÉÌÒÙ')];
        $result['schema'] = self::schemaFacts(array_merge(...array_map(fn($h) => self::jsonLd($h), array_values($pages))));
        $result['facts'] = self::visibleFacts($pages, $result['home']['final_url']);

        // 6. File: sitemap, llms.txt
        $result['sitemap'] = ['found' => false, 'url' => null];
        foreach (array_unique(array_merge($result['robots']['sitemaps'], [$base . '/sitemap.xml', $base . '/sitemap_index.xml', $base . '/wp-sitemap.xml'])) as $sm) {
            $r = $this->get($sm, null);
            if (self::isOk($r) && stripos((string) $r['body'], '<urlset') !== false || self::isOk($r) && stripos((string) $r['body'], '<sitemapindex') !== false) {
                $result['sitemap'] = ['found' => true, 'url' => $sm];
                break;
            }
        }
        $llms = $this->get($base . '/llms.txt', null);
        $result['llms'] = ['found' => self::isOk($llms) && !self::looksHtml((string) $llms['body']) && trim((string) $llms['body']) !== ''];

        $result['seconds'] = round(microtime(true) - $started, 1);
        return $result;
    }

    // ---------------------------------------------------------------- persistenza

    public function save(int $projectId, ?int $runId, array $result): int
    {
        return Database::insert('ar_tech_checks', [
            'project_id' => $projectId,
            'run_id' => $runId,
            'url' => mb_substr((string) ($result['url'] ?? ''), 0, 500),
            'result' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    /** Diagnosi del run se c'è, altrimenti l'ultima del progetto (null se mai fatta) */
    public static function latest(int $projectId, ?int $runId = null): ?array
    {
        $row = $runId ? Database::fetch("SELECT * FROM ar_tech_checks WHERE project_id = ? AND run_id = ? ORDER BY id DESC LIMIT 1", [$projectId, $runId]) : null;
        $row ??= Database::fetch("SELECT * FROM ar_tech_checks WHERE project_id = ? ORDER BY id DESC LIMIT 1", [$projectId]);
        if (!$row) {
            return null;
        }
        $res = json_decode((string) $row['result'], true) ?: [];
        $res['_id'] = (int) $row['id'];
        $res['_created_at'] = $row['created_at'];
        return $res;
    }

    // ---------------------------------------------------------------- diagnosi leggibile

    /**
     * Esiti per l'utente, in ordine di gravità. level: bad | warn | ok | info.
     * @return array<int, array{area:string, level:string, title:string, detail:string}>
     */
    public static function findings(array $r, string $subjectType = 'company'): array
    {
        $isPerson = $subjectType === 'person';
        $out = [];
        if (!empty($r['error'])) {
            $out[] = ['area' => 'access', 'level' => 'warn', 'title' => 'Sito non verificabile dal nostro server', 'detail' => $r['error']];
        }
        // Accesso
        $blockedAnswer = $blockedTraining = [];
        foreach ($r['bots'] ?? [] as $key => $b) {
            if (in_array($b['verdict'], ['blocked_server', 'blocked_robots'], true)) {
                $label = self::BOTS[$key]['name'] . ' (' . ($b['verdict'] === 'blocked_server' ? 'bloccato dal server, HTTP ' . $b['http_code'] : 'bloccato da robots.txt') . ')';
                in_array($key, self::ANSWER_BOTS, true) ? $blockedAnswer[] = $label : $blockedTraining[] = $label;
            }
        }
        if ($blockedAnswer) {
            $out[] = ['area' => 'access', 'level' => 'bad', 'title' => 'Alcune AI non riescono a leggere il sito', 'detail' => 'Bloccati: ' . implode(', ', $blockedAnswer) . '. Sono i crawler che servono per essere citati nelle risposte: le AI rispondono su di te senza leggere il tuo sito. Va sbloccato in robots.txt o nelle impostazioni di sicurezza dell\'hosting/CDN (es. Cloudflare, SiteGround).'];
        } elseif (empty($r['error'])) {
            $out[] = ['area' => 'access', 'level' => 'ok', 'title' => 'I crawler delle AI entrano nel sito', 'detail' => 'ChatGPT, Perplexity, Claude e Bing leggono la home senza blocchi.'];
        }
        if ($blockedTraining) {
            $out[] = ['area' => 'access', 'level' => 'info', 'title' => 'Bloccato l\'addestramento delle AI', 'detail' => implode(', ', $blockedTraining) . '. È una scelta legittima: non impedisce di essere citati, ma le AI conosceranno il sito solo tramite la ricerca.'];
        }
        if (!empty($r['error'])) {
            return $out; // senza la home non si può dire nulla su testo, dati strutturati e fatti
        }
        // Leggibilità
        $words = (int) ($r['readable']['words'] ?? 0);
        if (empty($r['error']) && $words < 100) {
            $out[] = ['area' => 'readable', 'level' => 'bad', 'title' => 'La home quasi non ha testo leggibile senza JavaScript', 'detail' => "Senza JavaScript la home ha circa {$words} parole: molti crawler AI non eseguono JavaScript e vedono una pagina vuota."];
        }
        // Dati strutturati e fatti
        $s = $r['schema'] ?? [];
        $f = $r['facts'] ?? [];
        if (empty($s['types'])) {
            $out[] = ['area' => 'schema', 'level' => 'bad', 'title' => 'Nessun dato strutturato su chi sei', 'detail' => $isPerson
                ? 'Non c\'è JSON-LD Person: le AI devono indovinare chi sei, cosa fai e quali profili sono tuoi.'
                : 'Non c\'è JSON-LD Organization/LocalBusiness: le AI devono indovinare nome, indirizzo e dati societari.'];
        } else {
            $missing = [];
            $wanted = $isPerson
                ? ['same_as' => 'profili esterni (sameAs: LinkedIn, testate, albo)']
                : ['legal_name' => 'ragione sociale (legalName)', 'vat' => 'partita IVA (vatID)', 'street' => 'via e numero (streetAddress)', 'telephone' => 'telefono', 'same_as' => 'profili esterni (sameAs)'];
            foreach ($wanted as $k => $lbl) {
                if (empty($s[$k])) {
                    $missing[] = $lbl;
                }
            }
            $out[] = ['area' => 'schema', 'level' => $missing ? 'warn' : 'ok', 'title' => 'Dati strutturati: ' . implode(', ', $s['types']), 'detail' => $missing ? 'Mancano: ' . implode(', ', $missing) . '.' : ($isPerson ? 'Ci sono nome e profili esterni.' : 'Ci sono nome, dati societari, indirizzo, telefono e profili esterni.')];
        }
        foreach (self::themeNotes($r, $subjectType) as $theme => $note) {
            $out[] = ['area' => 'theme:' . $theme, 'level' => $note['level'], 'title' => ReportBuilderService::DOUBT_THEMES[$theme][0] ?? $theme, 'detail' => $note['text']];
        }
        // File
        if (empty($r['sitemap']['found']) && empty($r['error'])) {
            $out[] = ['area' => 'files', 'level' => 'warn', 'title' => 'Sitemap non trovata', 'detail' => 'Né in robots.txt né agli indirizzi standard: crawler e motori trovano meno pagine.'];
        }
        $out[] = ['area' => 'files', 'level' => 'info', 'title' => !empty($r['llms']['found']) ? 'llms.txt presente' : 'llms.txt assente', 'detail' => 'Peso basso: quasi nessun crawler AI lo legge e Google dice che non serve. Utile soprattutto per assistenti di programmazione e agenti.'];

        $rank = ['bad' => 0, 'warn' => 1, 'info' => 2, 'ok' => 3];
        usort($out, fn($a, $b) => $rank[$a['level']] <=> $rank[$b['level']]);
        return $out;
    }

    /**
     * "Sul sito oggi": per ogni tema dei dubbi delle AI, cosa c'è davvero sul sito (la causa del dubbio).
     * @return array<string, array{level:string, text:string}>
     */
    public static function themeNotes(array $r, string $subjectType = 'company'): array
    {
        // Persona: P.IVA, via e recensioni non sono i suoi temi; i dubbi su una persona li coprono profilo e contro-contenuto
        if (!empty($r['error']) || $subjectType === 'person') {
            return [];
        }
        $s = $r['schema'] ?? [];
        $f = $r['facts'] ?? [];
        $notes = [];
        // Dati societari
        $vatText = !empty($f['vat']);
        $vatSchema = !empty($s['vat']);
        if (!$vatText && !$vatSchema) {
            $notes['company'] = ['level' => 'bad', 'text' => 'Partita IVA non trovata né nel testo né nei dati strutturati' . (empty($s['legal_name']) ? ', ragione sociale assente nei dati strutturati' : '') . ': il dato manca, va pubblicato.'];
        } elseif ($vatText && !$vatSchema) {
            $notes['company'] = ['level' => 'warn', 'text' => 'Partita IVA presente nel testo (' . $f['vat'][0] . ') ma non nei dati strutturati: aggiungere vatID e legalName al JSON-LD.'];
        } else {
            $notes['company'] = ['level' => 'ok', 'text' => 'Partita IVA presente' . ($vatText ? ' nel testo e' : '') . ' nei dati strutturati: se le AI non la trovano, verificare che coincida con il Registro Imprese e con la scheda Google.'];
        }
        // Recensioni
        $links = $f['review_links'] ?? [];
        $sameAs = array_map(fn($u) => strtolower((string) $u), $s['same_as'] ?? []);
        $platforms = [];
        foreach (['google' => '/google\.[a-z.]+\/maps|maps\.app\.goo\.gl|g\.page|business\.google/', 'tripadvisor' => '/tripadvisor\./', 'trustpilot' => '/trustpilot\./', 'facebook' => '/facebook\.com/'] as $p => $re) {
            foreach (array_merge($links, $sameAs) as $u) {
                if (preg_match($re, $u)) {
                    $platforms[$p] = true;
                }
            }
        }
        $notes['reviews'] = $platforms
            ? ['level' => isset($platforms['google']) ? 'ok' : 'warn', 'text' => 'Il sito collega: ' . implode(', ', array_map('ucfirst', array_keys($platforms))) . (isset($platforms['google']) ? '.' : '; manca il link alla scheda Google con le recensioni.')]
            : ['level' => 'bad', 'text' => 'Nessun link a Google, TripAdvisor o Trustpilot né sameAs nei dati strutturati: le recensioni restano verificabili solo sul tuo sito.'];
        // Chi siete e come contattarvi
        $phones = $f['phones'] ?? [];
        $parts = [];
        $level = 'ok';
        if (count($phones) > 1) {
            $parts[] = count($phones) . ' numeri di telefono diversi (' . implode(', ', array_slice($phones, 0, 3)) . ')';
            $level = 'warn';
        }
        if (empty($f['street']) && empty($s['street'])) {
            $parts[] = 'nessuna via nel testo né nei dati strutturati';
            $level = 'bad';
        }
        $notes['identity'] = ['level' => $level, 'text' => $parts ? 'Sul sito: ' . implode('; ', $parts) . '.' : 'Indirizzo e telefono presenti e coerenti.'];
        return $notes;
    }

    // ---------------------------------------------------------------- estrazione (pubbliche per i test)

    /** Verdetto robots.txt per un token: blocked | allowed (gruppo del bot o, in mancanza, gruppo *) */
    public static function robotsVerdict(string $robots, string $token): string
    {
        $groups = [];
        $current = null;
        $lastWasAgent = false;
        foreach (preg_split('/\R/', $robots) as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));
            if (!preg_match('/^([A-Za-z-]+)\s*:\s*(.*)$/', $line, $m)) {
                continue;
            }
            $field = strtolower($m[1]);
            $value = trim($m[2]);
            if ($field === 'user-agent') {
                if (!$lastWasAgent) {
                    $groups[] = ['agents' => [], 'rules' => []];
                    $current = count($groups) - 1;
                }
                $groups[$current]['agents'][] = strtolower($value);
                $lastWasAgent = true;
            } elseif (in_array($field, ['allow', 'disallow'], true) && $current !== null) {
                $groups[$current]['rules'][] = [$field, $value];
                $lastWasAgent = false;
            } else {
                $lastWasAgent = false;
            }
        }
        $pick = null;
        foreach ($groups as $g) {
            foreach ($g['agents'] as $a) {
                if ($a !== '*' && str_contains($token, $a)) {
                    $pick = $g;
                    break 2;
                }
            }
        }
        if ($pick === null) {
            foreach ($groups as $g) {
                if (in_array('*', $g['agents'], true)) {
                    $pick = $g;
                    break;
                }
            }
        }
        if ($pick === null) {
            return 'allowed';
        }
        $blocked = false;
        foreach ($pick['rules'] as [$kind, $path]) {
            if ($kind === 'disallow' && in_array($path, ['/', '/*'], true)) {
                $blocked = true;
            }
        }
        foreach ($pick['rules'] as [$kind, $path]) {
            if ($kind === 'allow' && in_array($path, ['/', '/*', '/$'], true)) {
                $blocked = false;
            }
        }
        return $blocked ? 'blocked' : 'allowed';
    }

    public static function robotsSitemaps(string $robots): array
    {
        preg_match_all('/^\s*sitemap\s*:\s*(\S+)/im', $robots, $m);
        return array_values(array_unique($m[1] ?? []));
    }

    /** Tutti i nodi JSON-LD della pagina (anche dentro @graph) */
    public static function jsonLd(string $html): array
    {
        $nodes = [];
        preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $m);
        foreach ($m[1] ?? [] as $raw) {
            $data = json_decode(trim(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5)), true);
            if (!is_array($data)) {
                continue;
            }
            $stack = array_is_list($data) ? $data : [$data];
            while ($stack) {
                $n = array_shift($stack);
                if (!is_array($n)) {
                    continue;
                }
                if (isset($n['@graph']) && is_array($n['@graph'])) {
                    array_push($stack, ...$n['@graph']);
                }
                if (isset($n['@type'])) {
                    $nodes[] = $n;
                }
            }
        }
        return $nodes;
    }

    /** Dati su chi è il soggetto dai nodi JSON-LD di tipo organizzazione/attività/persona */
    public static function schemaFacts(array $nodes): array
    {
        $skip = '/^(WebSite|WebPage|BreadcrumbList|ListItem|ImageObject|SiteNavigationElement|SearchAction|Article|BlogPosting|FAQPage|Question|Answer|Offer|AggregateRating|Review|ReadAction|EntryPoint|CollectionPage|ItemList|VideoObject|HowTo|Product|Event)$/i';
        $out = ['types' => [], 'name' => null, 'legal_name' => null, 'vat' => null, 'street' => null, 'locality' => null, 'telephone' => null, 'same_as' => []];
        foreach ($nodes as $n) {
            $types = array_map('strval', (array) $n['@type']);
            $own = array_filter($types, fn($t) => !preg_match($skip, $t));
            if (!$own) {
                continue;
            }
            $out['types'] = array_values(array_unique(array_merge($out['types'], $own)));
            $out['name'] ??= is_string($n['name'] ?? null) ? $n['name'] : null;
            $out['legal_name'] ??= is_string($n['legalName'] ?? null) ? $n['legalName'] : null;
            foreach (['vatID', 'taxID'] as $k) {
                if (!$out['vat'] && is_scalar($n[$k] ?? null) && preg_match('/\d{11}/', (string) $n[$k], $mm)) {
                    $out['vat'] = $mm[0];
                }
            }
            $out['telephone'] ??= is_scalar($n['telephone'] ?? null) ? (string) $n['telephone'] : null;
            $addr = $n['address'] ?? null;
            if (is_array($addr)) {
                $addr = array_is_list($addr) ? ($addr[0] ?? []) : $addr;
                $out['street'] ??= is_string($addr['streetAddress'] ?? null) && trim($addr['streetAddress']) !== '' ? $addr['streetAddress'] : null;
                $out['locality'] ??= is_string($addr['addressLocality'] ?? null) ? $addr['addressLocality'] : null;
            } elseif (is_string($addr) && preg_match('/\d/', $addr)) {
                $out['street'] ??= $addr;
            }
            foreach ((array) ($n['sameAs'] ?? []) as $u) {
                if (is_string($u) && preg_match('#^https?://#', $u)) {
                    $out['same_as'][] = $u;
                }
            }
        }
        $out['same_as'] = array_values(array_unique($out['same_as']));
        return $out;
    }

    /** Fatti visibili nel testo delle pagine: P.IVA, via, telefoni distinti, link a piattaforme di recensioni */
    public static function visibleFacts(array $pagesHtml, string $baseUrl): array
    {
        $vat = $streets = $phones = $reviewLinks = [];
        foreach ($pagesHtml as $html) {
            if ($html === '') {
                continue;
            }
            $text = self::visibleText($html);
            if (preg_match_all('/(?:p\.?\s?iva|partita\s+iva|vat(?:\s+(?:number|no\.?))?|c\.?\s?f\.?\s*(?:e|\/)\s*p\.?\s?iva)[^0-9\n]{0,20}?(?:IT\s?)?(\d{11})\b/iu', $text, $m)) {
                $vat = array_merge($vat, $m[1]);
            }
            if (preg_match_all('/\b(?:via|viale|piazza|piazzale|corso|largo|vico|vicolo|contrada|strada|località|loc\.)\s+[A-ZÀ-Ü0-9][\p{L}\'’.\s]{2,40}?,?\s*(?:n\.?\s*)?\d{1,4}\b/iu', $text, $m)) {
                $streets = array_merge($streets, array_map('trim', $m[0]));
            }
            preg_match_all('#href=["\']tel:([^"\']+)["\']#i', $html, $m);
            $raw = $m[1] ?? [];
            if (preg_match_all('/(?:\+39|0039)?[\s.]?(?:3\d{2}|0\d{1,3})[\s.\/-]?\d{3}[\s.-]?\d{3,4}/', $text, $m2)) {
                $raw = array_merge($raw, $m2[0]);
            }
            foreach ($raw as $p) {
                $d = preg_replace('/\D/', '', urldecode($p));
                $d = preg_replace('/^(0039|39)(?=[03]\d{7,})/', '', $d);
                if (strlen($d) >= 9 && strlen($d) <= 11) {
                    $phones[$d] = true;
                }
            }
            preg_match_all('#href=["\'](https?://[^"\']+)["\']#i', $html, $m);
            foreach ($m[1] ?? [] as $u) {
                if (preg_match('/google\.[a-z.]+\/maps|maps\.app\.goo\.gl|g\.page|business\.google|tripadvisor\.|trustpilot\.|facebook\.com|instagram\.com/i', $u)) {
                    $reviewLinks[] = strtolower(html_entity_decode($u));
                }
            }
        }
        // 3335397011 → +39 333 539 7011 · 0818701234 → 081 870 1234
        foreach ($vat as $v) {
            unset($phones[$v]); // la P.IVA (11 cifre, spesso inizia per 0) non è un telefono
        }
        $fmt = fn(string $d) => (str_starts_with($d, '3') ? '+39 ' : '') . substr($d, 0, 3) . ' ' . substr($d, 3, 3) . ' ' . substr($d, 6);
        return [
            'vat' => array_values(array_unique($vat)),
            'street' => array_values(array_unique($streets)),
            'phones' => array_map($fmt, array_keys($phones)),
            'review_links' => array_values(array_unique($reviewLinks)),
        ];
    }

    /** URL delle pagine Chi siamo / Contatti dello stesso sito, dai link della home */
    public static function findPages(string $html, string $baseUrl): array
    {
        $found = [];
        $host = EngineCollectorService::domainOf($baseUrl);
        preg_match_all('#<a\s[^>]*href=["\']([^"\'\#]+)["\'][^>]*>(.*?)</a>#is', $html, $m, PREG_SET_ORDER);
        foreach ($m as [, $href, $label]) {
            $abs = self::absolute($href, $baseUrl);
            if (!$abs || EngineCollectorService::domainOf($abs) !== $host) {
                continue;
            }
            $hay = mb_strtolower($href . ' ' . strip_tags($label));
            if (!isset($found['about']) && preg_match('/chi[\s-]?siamo|about|azienda|la[\s-]nostra[\s-]storia|our[\s-]story/u', $hay)) {
                $found['about'] = $abs;
            } elseif (!isset($found['contact']) && preg_match('/contatt|contact|dove[\s-]siamo/u', $hay)) {
                $found['contact'] = $abs;
            }
        }
        return $found;
    }

    public static function visibleText(string $html): string
    {
        $t = preg_replace('#<(script|style|noscript|svg|template)\b[^>]*>.*?</\1>#is', ' ', $html) ?? '';
        $t = preg_replace('#<(br|p|div|li|h[1-6]|tr|td|section|footer|header)\b[^>]*>#i', "\n", $t) ?? '';
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $t) ?? '');
    }

    // ---------------------------------------------------------------- HTTP

    /** Opzioni di richiesta "come il crawler": solo user-agent e Accept, niente header da browser (Sec-Fetch-*). */
    private static function botOptions(string $ua): array
    {
        return ['timeout' => 15, 'api_mode' => true, 'headers' => ['User-Agent: ' . $ua, 'Accept: text/html,application/xhtml+xml,*/*;q=0.8']];
    }

    private function get(string $url, ?string $ua): array
    {
        $opts = $ua !== null ? self::botOptions($ua) : ['timeout' => 15];
        $r = $this->scraper->fetchRaw($url, $opts);
        return !empty($r['error']) ? ['http_code' => 0, 'body' => '', 'final_url' => $url] : $r;
    }

    public static function isOk(array $r): bool
    {
        $code = (int) ($r['http_code'] ?? 0);
        $body = (string) ($r['body'] ?? '');
        if ($code < 200 || $code >= 300) {
            return false;
        }
        // Pagine-sfida di CDN e hosting (stessi segnali di seo-audit CrawlerService::isBotProtectionPage)
        return !preg_match('/sgcaptcha|cf_chl_opt|cf-challenge-running|cf-browser-verification|<title[^>]*>\s*Just a moment|sucuri-firewall|Access Denied - Sucuri/i', $body)
            && !($code === 202 && strlen($body) < 1000 && stripos($body, 'http-equiv="refresh"') !== false);
    }

    private static function looksHtml(string $body): bool
    {
        return (bool) preg_match('/^\s*(<!doctype|<html|<head|<body)/i', $body);
    }

    private static function origin(string $url): string
    {
        $p = parse_url($url);
        return ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : '');
    }

    private static function absolute(string $href, string $base): ?string
    {
        $href = trim(html_entity_decode($href));
        if ($href === '' || preg_match('#^(mailto|tel|javascript|whatsapp):#i', $href)) {
            return null;
        }
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }
        if (str_starts_with($href, '//')) {
            return 'https:' . $href;
        }
        $origin = self::origin($base);
        if (str_starts_with($href, '/')) {
            return $origin . $href;
        }
        $path = (string) (parse_url($base, PHP_URL_PATH) ?? '/');
        return $origin . rtrim(str_ends_with($path, '/') ? $path : dirname($path) . '/', '/') . '/' . $href;
    }
}
