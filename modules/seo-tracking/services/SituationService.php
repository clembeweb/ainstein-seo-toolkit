<?php

namespace Modules\SeoTracking\Services;

use Core\Credits;
use Core\Database;
use Services\AiService;
use Services\ApiLoggerService;

/**
 * "Punto della situazione" di AI Rank: GA4 + Search Console letti dal vivo, confronto col periodo precedente e
 * con l'anno prima, commento AI in 5 paragrafi. Spec: docs/specs/seo-tracking-punto-situazione.md.
 * Metodo preso dal Recap SEO di Amevista (periodi, filtro organico pulito, quota brand GSC, deltaPack, output AI
 * JSON con refs in whitelist), senza le parti solo e-commerce di quel sito.
 */
class SituationService
{
    public const SLUG = 'seo-tracking';
    public const PERIODS = [
        'last_28' => 'Ultimi 28 giorni',
        'last_90' => 'Ultimi 90 giorni',
        'last_month' => 'Mese scorso',
        'last_quarter' => 'Trimestre scorso',
    ];
    /** Riquadri a cui un paragrafo AI può rimandare (data-kpi nella vista). */
    public const REFS = ['ga4_sessions', 'ga4_key_events', 'ga4_revenue', 'channels', 'organic_pages', 'gsc_clicks', 'gsc_impressions', 'gsc_position', 'gsc_brand', 'gsc_queries', 'gsc_pages'];
    private const GSC_API = 'https://searchconsole.googleapis.com/webmasters/v3';

    public static function cost(): float
    {
        return (float) Credits::getCost('situation_report', self::SLUG, 5);
    }

    /**
     * Periodo B (corrente), A (precedente di pari durata), Y (stesso periodo un anno prima). Pura: testabile.
     * GSC ha 2-3 giorni di ritardo: i periodi "ultimi N giorni" finiscono 2 giorni fa.
     */
    public static function periods(string $key, ?string $today = null): array
    {
        $t = new \DateTimeImmutable($today ?? 'today');
        switch ($key) {
            case 'last_90':
                $bEnd = $t->modify('-2 days');
                $bStart = $bEnd->modify('-89 days');
                break;
            case 'last_month':
                $bStart = $t->modify('first day of last month');
                $bEnd = $t->modify('last day of last month');
                break;
            case 'last_quarter':
                $q = intdiv((int) $t->format('n') - 1, 3); // trimestre corrente 0..3
                $firstOfCurrent = $t->setDate((int) $t->format('Y'), $q * 3 + 1, 1);
                $bStart = $firstOfCurrent->modify('-3 months');
                $bEnd = $firstOfCurrent->modify('-1 day');
                break;
            default:
                $key = 'last_28';
                $bEnd = $t->modify('-2 days');
                $bStart = $bEnd->modify('-27 days');
        }
        $days = (int) $bStart->diff($bEnd)->days + 1;
        $aEnd = $bStart->modify('-1 day');
        $aStart = $aEnd->modify('-' . ($days - 1) . ' days');
        $f = fn(\DateTimeImmutable $d) => $d->format('Y-m-d');
        return [
            'key' => $key,
            'label' => self::PERIODS[$key],
            'days' => $days,
            'b' => [$f($bStart), $f($bEnd)],
            'a' => [$f($aStart), $f($aEnd)],
            'y' => [$f($bStart->modify('-1 year')), $f($bEnd->modify('-1 year'))],
        ];
    }

    /** Variazione percentuale, null se il confronto non ha senso (come deltaPctSafe di Amevista). */
    public static function pct(?float $now, ?float $before): ?float
    {
        if ($now === null || $before === null || $before == 0.0) {
            return null;
        }
        return round(($now - $before) / $before * 100, 1);
    }

    /** {b, a, y, vs_prev_pct, vs_year_pct} */
    public static function pack(?float $b, ?float $a, ?float $y = null): array
    {
        return ['b' => $b, 'a' => $a, 'y' => $y, 'vs_prev_pct' => self::pct($b, $a), 'vs_year_pct' => self::pct($b, $y)];
    }

    /**
     * Regex del brand dal nome e dal dominio del progetto ("Stabia Boat Rental" → stabia ?boat ?rental|stabiaboatrental). Pura.
     * Note nel nome non sono brand: "(test)", "[old]", "- sito nuovo", "| prova" e le parole test/prova/demo/copia si scartano.
     */
    public static function brandRegex(string $name, string $domain): string
    {
        $parts = [];
        $name = preg_replace(['/\([^)]*\)|\[[^\]]*\]/u', '/\s[-–—|]\s.*$/u'], ' ', mb_strtolower($name));
        $words = array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', $name) ?: [],
            fn($w) => mb_strlen($w) >= 3 && !in_array($w, ['test', 'prova', 'demo', 'copia'], true)));
        if ($words) {
            $parts[] = implode(' ?', array_map(fn($w) => preg_quote($w, '/'), array_slice($words, 0, 4)));
        }
        $host = strtolower(preg_replace('/^www\./', '', (string) (parse_url(str_contains($domain, '://') ? $domain : "https://{$domain}", PHP_URL_HOST) ?: $domain)));
        $label = explode('.', $host)[0] ?? '';
        if (mb_strlen($label) >= 3) {
            $parts[] = preg_quote(str_replace('-', '', $label), '/');
        }
        return implode('|', array_unique($parts));
    }

    // =====================================================================
    // Dati
    // =====================================================================

    /** Calcola il JSON dei dati. Ogni fonte che manca diventa null + caveat, l'altra va avanti. */
    public function compute(array $project, string $periodKey): array
    {
        $p = self::periods($periodKey);
        $data = ['period' => $p, 'ga4' => null, 'gsc' => null, 'caveats' => []];
        $ga4 = new Ga4Service();
        $ga4Conn = $ga4->getConnection((int) $project['id']);
        if ($ga4Conn && !empty($ga4Conn['property_id'])) {
            try {
                $data['ga4'] = $this->ga4Block($ga4, (int) $project['id'], $p);
                $data['ga4']['property'] = $ga4Conn['property_name'] ?: $ga4Conn['property_id'];
            } catch (\Throwable $e) {
                $data['caveats'][] = 'Google Analytics non letto: ' . $e->getMessage();
            }
        } else {
            $data['caveats'][] = 'Google Analytics non collegato: niente sorgenti di traffico, conversioni e ricavi.';
        }
        Database::reconnect();
        try {
            $data['gsc'] = $this->gscBlock($project, $p);
            if ($data['gsc'] === null) {
                $data['caveats'][] = 'Search Console non collegata: niente clic, ricerche e posizioni su Google.';
            }
        } catch (\Throwable $e) {
            $data['caveats'][] = 'Search Console non letta: ' . $e->getMessage();
        }
        Database::reconnect();
        return $data;
    }

    private function ga4Block(Ga4Service $ga4, int $projectId, array $p): array
    {
        $ranges = [
            ['startDate' => $p['b'][0], 'endDate' => $p['b'][1], 'name' => 'b'],
            ['startDate' => $p['a'][0], 'endDate' => $p['a'][1], 'name' => 'a'],
            ['startDate' => $p['y'][0], 'endDate' => $p['y'][1], 'name' => 'y'],
        ];
        $metrics = [['name' => 'sessions'], ['name' => 'keyEvents'], ['name' => 'totalRevenue']];

        // 1. Canali (sorgenti di traffico) sui tre periodi, in una sola chiamata
        $rows = $ga4->runReport($projectId, [
            'dateRanges' => $ranges,
            'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
            'metrics' => $metrics,
            'limit' => 50,
        ]);
        $byChannel = [];
        $tot = ['b' => [0, 0, 0], 'a' => [0, 0, 0], 'y' => [0, 0, 0]];
        foreach ($rows as $r) {
            $range = $r['dateRange'] ?? 'b';
            $ch = $r['sessionDefaultChannelGroup'] ?: '(non definito)';
            $byChannel[$ch][$range] = [$r['sessions'], $r['keyEvents'], $r['totalRevenue']];
            $tot[$range][0] += $r['sessions'];
            $tot[$range][1] += $r['keyEvents'];
            $tot[$range][2] += $r['totalRevenue'];
        }
        $channels = [];
        foreach ($byChannel as $name => $v) {
            $g = fn(string $k, int $i) => isset($v[$k]) ? (float) $v[$k][$i] : 0.0;
            $channels[] = [
                'name' => $name,
                'sessions' => self::pack($g('b', 0), $g('a', 0), $g('y', 0)),
                'key_events' => self::pack($g('b', 1), $g('a', 1), $g('y', 1)),
                'revenue' => self::pack($g('b', 2), $g('a', 2), $g('y', 2)),
                'share_b_pct' => $tot['b'][0] > 0 ? round($g('b', 0) / $tot['b'][0] * 100, 1) : null,
            ];
        }
        usort($channels, fn($x, $y) => $y['sessions']['b'] <=> $x['sessions']['b']);

        // 2. Pagine di atterraggio dall'organico (filtro pulito come Amevista: niente gclid/fbclid)
        $pageRows = $ga4->runReport($projectId, [
            'dateRanges' => array_slice($ranges, 0, 2),
            'dimensions' => [['name' => 'landingPage']],
            'metrics' => $metrics,
            'dimensionFilter' => ['andGroup' => ['expressions' => [
                ['filter' => ['fieldName' => 'sessionDefaultChannelGroup', 'stringFilter' => ['matchType' => 'EXACT', 'value' => 'Organic Search']]],
                ['notExpression' => ['filter' => ['fieldName' => 'landingPage', 'stringFilter' => ['matchType' => 'CONTAINS', 'value' => 'gclid=']]]],
                ['notExpression' => ['filter' => ['fieldName' => 'landingPage', 'stringFilter' => ['matchType' => 'CONTAINS', 'value' => 'fbclid=']]]],
            ]]],
            'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
            'limit' => 300,
        ]);
        $pages = [];
        foreach ($pageRows as $r) {
            $path = $r['landingPage'] !== '' ? $r['landingPage'] : '(non definita)';
            $range = $r['dateRange'] ?? 'b';
            $pages[$path][$range] = $r;
        }
        $list = [];
        foreach ($pages as $path => $v) {
            $b = (float) ($v['b']['sessions'] ?? 0);
            $a = (float) ($v['a']['sessions'] ?? 0);
            $list[] = ['page' => $path, 'sessions_b' => $b, 'sessions_a' => $a, 'diff' => $b - $a, 'vs_prev_pct' => self::pct($b, $a),
                'key_events_b' => (float) ($v['b']['keyEvents'] ?? 0), 'revenue_b' => (float) ($v['b']['totalRevenue'] ?? 0)];
        }
        usort($list, fn($x, $y) => $y['sessions_b'] <=> $x['sessions_b']);
        $top = array_slice($list, 0, 10);
        $byDiff = $list;
        usort($byDiff, fn($x, $y) => $y['diff'] <=> $x['diff']);
        $gainers = array_values(array_filter(array_slice($byDiff, 0, 5), fn($r) => $r['diff'] > 0));
        $losers = array_values(array_filter(array_slice(array_reverse($byDiff), 0, 5), fn($r) => $r['diff'] < 0));

        return [
            'totals' => [
                'sessions' => self::pack($tot['b'][0], $tot['a'][0], $tot['y'][0]),
                'key_events' => self::pack($tot['b'][1], $tot['a'][1], $tot['y'][1]),
                'revenue' => self::pack($tot['b'][2], $tot['a'][2], $tot['y'][2]),
            ],
            'has_revenue' => ($tot['b'][2] + $tot['a'][2] + $tot['y'][2]) > 0,
            'channels' => $channels,
            'organic_pages' => ['top' => $top, 'gainers' => $gainers, 'losers' => $losers],
        ];
    }

    private function gscBlock(array $project, array $p): ?array
    {
        $conn = Database::fetch("SELECT property_url FROM st_gsc_connections WHERE project_id = ? AND is_active = 1", [(int) $project['id']]);
        if (!$conn || empty($conn['property_url'])) {
            return null;
        }
        $token = (new GscService())->getValidToken((int) $project['id']);
        if (!$token) {
            throw new \RuntimeException('collegamento scaduto, ricollega Search Console');
        }
        $site = $conn['property_url'];
        $q = fn(array $body) => $this->gscQuery($token, $site, $body, (int) $project['id']);
        $range = fn(string $k) => ['startDate' => $p[$k][0], 'endDate' => $p[$k][1]];

        $totals = [];
        foreach (['b', 'a', 'y'] as $k) {
            $row = $q($range($k) + ['rowLimit' => 1])[0] ?? [];
            $totals[$k] = ['clicks' => (float) ($row['clicks'] ?? 0), 'impressions' => (float) ($row['impressions'] ?? 0),
                'ctr' => isset($row['ctr']) ? round($row['ctr'] * 100, 2) : null, 'position' => isset($row['position']) ? round($row['position'], 1) : null];
        }

        // Quota brand sui clic con query visibile (metodo Amevista fetchGscBrandQuota)
        $regex = self::brandRegex((string) ($project['name'] ?? ''), (string) ($project['domain'] ?? ''));
        $brand = ['regex' => $regex, 'quota_b_pct' => null, 'quota_a_pct' => null];
        if ($regex !== '') {
            foreach (['b', 'a'] as $k) {
                $filter = fn(string $op) => ['dimensionFilterGroups' => [['filters' => [['dimension' => 'query', 'operator' => $op, 'expression' => '(?i)(' . $regex . ')']]]]];
                $in = (float) ($q($range($k) + ['rowLimit' => 1] + $filter('includingRegex'))[0]['clicks'] ?? 0);
                $out = (float) ($q($range($k) + ['rowLimit' => 1] + $filter('excludingRegex'))[0]['clicks'] ?? 0);
                $brand['quota_' . $k . '_pct'] = ($in + $out) > 0 ? round($in / ($in + $out) * 100, 1) : null;
            }
        }

        return [
            'property' => $site,
            'totals' => [
                'clicks' => self::pack($totals['b']['clicks'], $totals['a']['clicks'], $totals['y']['clicks']),
                'impressions' => self::pack($totals['b']['impressions'], $totals['a']['impressions'], $totals['y']['impressions']),
                'ctr_pct' => ['b' => $totals['b']['ctr'], 'a' => $totals['a']['ctr'], 'y' => $totals['y']['ctr']],
                'position' => ['b' => $totals['b']['position'], 'a' => $totals['a']['position'], 'y' => $totals['y']['position']],
            ],
            'brand' => $brand,
            'queries' => $this->movers($q, $range, 'query'),
            'pages' => $this->movers($q, $range, 'page'),
        ];
    }

    /** Chi guadagna e chi perde più clic tra A e B per una dimensione GSC. */
    private function movers(callable $q, callable $range, string $dim): array
    {
        $rows = [];
        foreach (['b', 'a'] as $k) {
            foreach ($q($range($k) + ['dimensions' => [$dim], 'rowLimit' => 500]) as $r) {
                $key = $r['keys'][0] ?? '';
                $rows[$key][$k] = $r;
            }
        }
        $list = [];
        foreach ($rows as $key => $v) {
            $cb = (float) ($v['b']['clicks'] ?? 0);
            $ca = (float) ($v['a']['clicks'] ?? 0);
            $list[] = ['key' => $key, 'clicks_b' => $cb, 'clicks_a' => $ca, 'diff' => $cb - $ca,
                'position_b' => isset($v['b']['position']) ? round($v['b']['position'], 1) : null,
                'position_a' => isset($v['a']['position']) ? round($v['a']['position'], 1) : null];
        }
        usort($list, fn($x, $y) => $y['diff'] <=> $x['diff']);
        return [
            'gainers' => array_values(array_filter(array_slice($list, 0, 8), fn($r) => $r['diff'] > 0)),
            'losers' => array_values(array_filter(array_slice(array_reverse($list), 0, 8), fn($r) => $r['diff'] < 0)),
        ];
    }

    private function gscQuery(string $token, string $site, array $body, int $projectId): array
    {
        $url = self::GSC_API . '/sites/' . urlencode($site) . '/searchAnalytics/query';
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $start = microtime(true);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 60,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
            ]);
            $raw = curl_exec($ch);
            $errno = curl_errno($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $res = is_string($raw) ? (json_decode($raw, true) ?: []) : [];
            ApiLoggerService::log('google_gsc', '/searchAnalytics/query', $body, $res, $http, $start, ['module' => self::SLUG, 'cost' => 0, 'context' => "situation project_id={$projectId}"]);
            if ($errno && $attempt === 0) {
                usleep(800000);
                continue;
            }
            if ($errno || $http >= 400) {
                throw new \RuntimeException($res['error']['message'] ?? ('errore HTTP ' . $http));
            }
            return $res['rows'] ?? [];
        }
        return [];
    }

    // =====================================================================
    // AI
    // =====================================================================

    /** Paragrafi AI: [['titolo','testo','refs'=>[]]]. Nessun addebito qui (lo fa il controller). */
    public function narrative(int $userId, array $project, array $data): array
    {
        $ai = new AiService(self::SLUG);
        $res = $ai->complete($userId, [['role' => 'user', 'content' => self::prompt($project, $data)]], [
            'max_tokens' => 3000,
            'system' => 'Sei un consulente SEO e di web analytics che spiega i numeri al titolare di un sito che non è un esperto. Rispondi solo con JSON valido, in italiano.',
            'charge_credits' => false,
        ], self::SLUG);
        Database::reconnect();
        if (!empty($res['error']) || empty($res['success'])) {
            throw new \RuntimeException((string) ($res['message'] ?? 'Chiamata AI non riuscita'));
        }
        return self::parseNarrative((string) $res['result']);
    }

    /**
     * Dati per l'AI con nomi che non si possono leggere al contrario (b/a/y → ora/prima/anno_scorso).
     * Con le sigle GPT-4o invertiva le posizioni e i segni (test 2026-10-13). Pura: testabile.
     */
    public static function forAi(array $data): array
    {
        $map = ['b' => 'ora', 'a' => 'prima', 'y' => 'anno_scorso', 'vs_prev_pct' => 'variazione_vs_prima_pct',
            'vs_year_pct' => 'variazione_vs_anno_scorso_pct', 'diff' => 'differenza_ora_meno_prima', 'gainers' => 'crescono', 'losers' => 'calano'];
        $out = [];
        foreach ($data as $k => $v) {
            $nk = $k;
            if (is_string($k)) {
                $nk = $map[$k] ?? preg_replace(['/_b$/', '/_a$/', '/^quota_b_pct$/', '/^quota_a_pct$/'], ['_ora', '_prima', 'quota_ora_pct', 'quota_prima_pct'], $k);
            }
            $out[$nk] = is_array($v) ? self::forAi($v) : $v;
        }
        return $out;
    }

    public static function prompt(array $project, array $data): string
    {
        $json = json_encode(self::forAi($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return "Sito: {$project['name']} ({$project['domain']}). Periodo analizzato: {$data['period']['label']} "
            . "(dal {$data['period']['b'][0]} al {$data['period']['b'][1]}), confrontato con il periodo precedente di pari durata (\"prima\") "
            . "e con lo stesso periodo dell'anno prima (\"anno_scorso\").\n\n"
            . "Scrivi il PUNTO DELLA SITUAZIONE in 5 paragrafi, in quest'ordine e con questi titoli:\n"
            . "1. \"Il punto\": com'è andato il periodo in 2-3 frasi, con i numeri principali.\n"
            . "2. \"Da dove arriva il traffico\": i canali (organico, diretto, social, a pagamento, referral...), il loro peso e cosa è cambiato.\n"
            . "3. \"Google: cosa cresce e cosa cala\": clic e impressioni da Search Console, ricerche e pagine che guadagnano o perdono, quota del brand.\n"
            . "4. \"Criticità\": i problemi veri che emergono dai numeri (cali, dipendenza da un canale o dal brand, pagine che perdono). Se non ce ne sono, dillo.\n"
            . "5. \"Cosa fare adesso\": esattamente 3 azioni concrete, scritte di seguito nel testo (prima..., poi..., infine...).\n\n"
            . "Regole:\n"
            . "- Usa SOLO i numeri del JSON. Le variazioni sono già calcolate (variazione_vs_prima_pct, variazione_vs_anno_scorso_pct): non ricalcolarle. Un numero negativo è un calo, positivo una crescita. Per la posizione media un numero più basso è MEGLIO (1 = primo risultato). null = dato non disponibile. Numeri all'italiana (virgola per i decimali, punto per le migliaia).\n"
            . "- Spiega il PERCHÉ quando i dati lo mostrano: cerca le ricerche e le pagine che pesano di più sulla variazione (campo differenza_ora_meno_prima; posizione_prima → posizione_ora) e confrontale col totale. Esempio: se i clic calano di 1.900 e una sola ricerca ne perde 890 scendendo dalla posizione 2,9 alla 7,8, quella è la causa principale e va detto chiaramente, con le posizioni.\n"
            . "- Parole semplici, mai gergo: \"visite\" (non sessioni), \"conversioni\" (non eventi chiave), \"ricerche\" (non query), \"pagina\" (non landing page), \"traffico da Google\" (non organico), \"ricerche col tuo nome\" (non brand). Niente elenchi puntati, frasi brevi.\n"
            . "- Se has_revenue è false non parlare di ricavi.\n"
            . "- Le 3 azioni devono nascere dai problemi trovati nei dati (pagina X, ricerca Y), non consigli generici validi per tutti.\n"
            . "- Se una fonte manca o c'è un avviso in caveats, dillo in modo semplice nel paragrafo giusto, senza inventare.\n"
            . "- Non dare la colpa a cause che i dati non mostrano (algoritmo, stagionalità) se non come ipotesi dichiarata.\n"
            . "- Ogni paragrafo ha \"refs\": i riquadri che cita, scelti solo tra: " . implode(', ', self::REFS) . ".\n\n"
            . "Rispondi SOLO con questo JSON: {\"paragrafi\":[{\"titolo\":\"...\",\"testo\":\"...\",\"refs\":[\"...\"]}]}\n\n"
            . "DATI:\n{$json}";
    }

    /** Parsing tollerante con refs filtrati in whitelist (come Amevista). Pura: testabile. */
    public static function parseNarrative(string $raw): array
    {
        $raw = trim(preg_replace('/^```(?:json)?|```$/m', '', $raw));
        $d = json_decode($raw, true);
        if (!is_array($d) && preg_match('/\{.*\}/s', $raw, $m)) {
            $d = json_decode($m[0], true);
        }
        $out = [];
        foreach ((array) ($d['paragrafi'] ?? []) as $par) {
            if (!is_array($par) || trim((string) ($par['testo'] ?? '')) === '') {
                continue;
            }
            $out[] = [
                'titolo' => mb_substr(trim((string) ($par['titolo'] ?? '')), 0, 120),
                'testo' => trim((string) $par['testo']),
                'refs' => array_values(array_intersect(array_map('strval', (array) ($par['refs'] ?? [])), self::REFS)),
            ];
        }
        if (!$out) {
            throw new \RuntimeException('Risposta AI non leggibile, riprova.');
        }
        return $out;
    }

    public function save(array $project, int $userId, array $data, array $paragraphs, float $credits): int
    {
        return Database::insert('st_situation_reports', [
            'project_id' => (int) $project['id'],
            'user_id' => $userId,
            'period_key' => $data['period']['key'],
            'b_start' => $data['period']['b'][0],
            'b_end' => $data['period']['b'][1],
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'narrative' => json_encode($paragraphs, JSON_UNESCAPED_UNICODE),
            'credits_used' => $credits,
        ]);
    }
}
