<?php

namespace Modules\AdsAnalyzer\Services;

use Services\AiService;
use Core\Database;
use Core\ModuleLoader;

class CampaignCreatorService
{
    // Limiti caratteri Google Ads ufficiali
    const LIMITS = [
        'headline' => 30,
        'description' => 90,
        'long_headline' => 90,
        'display_path' => 15,
        'business_name' => 25,
        'search_theme' => 80,
        'sitelink_title' => 25,
        'sitelink_desc' => 35,
        'callout' => 25,
        'snippet_value' => 25,
    ];

    const SNIPPET_HEADERS = [
        'Brands', 'Types', 'Service catalog', 'Styles', 'Destinations',
        'Models', 'Amenities', 'Courses', 'Shows', 'Neighborhoods',
        'Insurance coverage', 'Degree programs', 'Featured hotels'
    ];

    /**
     * Genera keyword research per il progetto
     */
    public static function generateKeywordResearch(int $userId, array $project): array
    {
        $ai = new AiService('ads-analyzer');
        if (!$ai->isConfigured()) {
            return ['error' => true, 'message' => 'AI non configurata. Configura il provider AI nelle impostazioni.'];
        }

        $type = $project['campaign_type_gads'];
        $scrapedContent = mb_substr($project['scraped_content'] ?? '', 0, 6000);
        $scrapedContext = $project['scraped_context'] ?? '';

        $systemPrompt = self::getKeywordResearchSystemPrompt($type);
        $userPrompt = self::getKeywordResearchUserPrompt($type, $project['brief'], $project['landing_url'], $scrapedContext, $scrapedContent);

        $result = $ai->complete($userId, [
            ['role' => 'user', 'content' => $userPrompt]
        ], [
            'system' => $systemPrompt,
            'max_tokens' => 6000,
        ], 'ads-analyzer');

        if (!empty($result['error'])) {
            return $result;
        }

        $parsed = self::parseJsonResponse($result['result'] ?? '');
        if ($parsed === null) {
            return ['error' => true, 'message' => 'Errore nel parsing della risposta AI. Riprova.'];
        }

        return [
            'success' => true,
            'data' => $parsed,
            'credits_used' => $result['credits_used'] ?? 0,
        ];
    }

    /**
     * Genera campagna completa
     */
    public static function generateCampaign(int $userId, array $project, array $keywords): array
    {
        $ai = new AiService('ads-analyzer');
        if (!$ai->isConfigured()) {
            return ['error' => true, 'message' => 'AI non configurata. Configura il provider AI nelle impostazioni.'];
        }

        $type = $project['campaign_type_gads'];

        $systemPrompt = self::getCampaignGenerationSystemPrompt($type);
        $userPrompt = self::getCampaignGenerationUserPrompt($type, $project, $keywords);

        $result = $ai->complete($userId, [
            ['role' => 'user', 'content' => $userPrompt]
        ], [
            'system' => $systemPrompt,
            'max_tokens' => 8000,
        ], 'ads-analyzer');

        if (!empty($result['error'])) {
            return $result;
        }

        $parsed = self::parseJsonResponse($result['result'] ?? '');
        if ($parsed === null) {
            return ['error' => true, 'message' => 'Errore nel parsing della risposta AI. Riprova.'];
        }

        // Validazione e troncamento limiti caratteri
        $parsed = self::validateCharLimits($parsed, $type);

        return [
            'success' => true,
            'data' => $parsed,
            'credits_used' => $result['credits_used'] ?? 0,
        ];
    }

    // ===== PROMPT KEYWORD RESEARCH =====

    private static function getKeywordResearchSystemPrompt(string $type): string
    {
        $base = "Sei un esperto certificato Google Ads con 15 anni di esperienza in keyword research e strutturazione campagne.\n";
        $base .= "Rispondi ESCLUSIVAMENTE con un oggetto JSON valido, senza testo aggiuntivo, senza markdown.\n\n";

        if ($type === 'search') {
            $base .= "Devi generare una keyword research completa per una campagna Google Ads Search (RSA).\n";
            $base .= "Organizza le keyword in ad group tematici (3-6 ad group, 5-15 keyword per gruppo).\n";
            $base .= "Usa match type appropriati: broad per scoperta, phrase per controllo, exact per alta precisione.\n";
            $base .= "Includi keyword negative per escludere traffico non pertinente.\n\n";
            $base .= "FORMATO OUTPUT JSON:\n";
            $base .= "{\n";
            $base .= "  \"ad_groups\": [{\"name\": \"Nome Gruppo\", \"theme\": \"Tema del gruppo\", \"keywords\": [{\"text\": \"keyword\", \"match_type\": \"broad|phrase|exact\", \"intent\": \"transactional|informational|navigational\"}]}],\n";
            $base .= "  \"negative_keywords\": [{\"text\": \"keyword\", \"match_type\": \"phrase\", \"reason\": \"Motivo esclusione\"}],\n";
            $base .= "  \"notes\": \"Note strategiche sulla struttura\"\n";
            $base .= "}";
        } else {
            $base .= "Devi generare search themes e audience signals per una campagna Google Ads Performance Max.\n";
            $base .= "I search themes descrivono i temi di ricerca dell'audience (max 80 caratteri ciascuno, max 25 temi).\n";
            $base .= "Includi audience signals: custom segments (termini di ricerca e URL competitor), interessi e dati demografici.\n";
            $base .= "Includi keyword negative per escludere traffico non pertinente.\n\n";
            $base .= "FORMATO OUTPUT JSON:\n";
            $base .= "{\n";
            $base .= "  \"search_themes\": [{\"text\": \"tema di ricerca\", \"relevance\": \"high|medium\"}],\n";
            $base .= "  \"audience_signals\": {\n";
            $base .= "    \"custom_segments\": {\"search_terms\": [\"termine1\", \"termine2\"], \"competitor_urls\": [\"https://...\"]},\n";
            $base .= "    \"interests\": [\"Interesse 1\", \"Interesse 2\"],\n";
            $base .= "    \"demographics\": \"Descrizione target demografico\"\n";
            $base .= "  },\n";
            $base .= "  \"negative_keywords\": [{\"text\": \"keyword\", \"match_type\": \"phrase\", \"reason\": \"Motivo esclusione\"}],\n";
            $base .= "  \"notes\": \"Note strategiche\"\n";
            $base .= "}";
        }

        return $base;
    }

    private static function getKeywordResearchUserPrompt(string $type, string $brief, string $url, string $context, string $content): string
    {
        $prompt = "BRIEF CAMPAGNA:\n{$brief}\n\n";
        $prompt .= "URL LANDING PAGE: {$url}\n\n";

        if (!empty($context)) {
            $prompt .= "CONTESTO ESTRATTO DALLA LANDING:\n{$context}\n\n";
        }

        if (!empty($content)) {
            $prompt .= "CONTENUTO LANDING PAGE (estratto):\n{$content}\n\n";
        }

        if ($type === 'search') {
            $prompt .= "Genera una keyword research per campagna Search RSA. Struttura le keyword in ad group tematici con match type appropriati. Includi keyword negative rilevanti.";
        } else {
            $prompt .= "Genera search themes e audience signals per campagna Performance Max. I search themes devono avere max 80 caratteri. Includi audience signals dettagliati e keyword negative.";
        }

        return $prompt;
    }

    // ===== PROMPT GENERAZIONE CAMPAGNA =====

    private static function getCampaignGenerationSystemPrompt(string $type): string
    {
        $base = "Sei un copywriter esperto Google Ads specializzato nella creazione di annunci ad alta conversione.\n";
        $base .= "Rispondi ESCLUSIVAMENTE con un oggetto JSON valido, senza testo aggiuntivo, senza markdown.\n\n";
        $base .= "REGOLA FONDAMENTALE: Rispetta ESATTAMENTE i limiti di caratteri indicati. Ogni carattere conta.\n\n";

        if ($type === 'search') {
            $base .= "LIMITI CARATTERI CAMPAGNA SEARCH (RSA):\n";
            $base .= "- Headlines: max 30 caratteri ciascuno, genera ESATTAMENTE 15 headlines\n";
            $base .= "- Descriptions: max 90 caratteri ciascuna, genera ESATTAMENTE 4 descrizioni\n";
            $base .= "- Display Path 1 e 2: max 15 caratteri ciascuno\n";
            $base .= "- Sitelink title: max 25 caratteri\n";
            $base .= "- Sitelink description 1 e 2: max 35 caratteri ciascuna\n";
            $base .= "- Callout: max 25 caratteri ciascuno\n";
            $base .= "- Structured Snippet value: max 25 caratteri ciascuno\n\n";
            $base .= "FORMATO OUTPUT JSON:\n";
            $base .= "{\n";
            $base .= "  \"campaign_name\": \"Nome Campagna\",\n";
            $base .= "  \"headlines\": [\"H1 (max 30)\", ...x15],\n";
            $base .= "  \"descriptions\": [\"D1 (max 90)\", ...x4],\n";
            $base .= "  \"display_paths\": {\"path1\": \"max15\", \"path2\": \"max15\"},\n";
            $base .= "  \"sitelinks\": [{\"title\": \"max 25\", \"desc1\": \"max 35\", \"desc2\": \"max 35\", \"url\": \"/path\"}],\n";
            $base .= "  \"callouts\": [\"max 25\", ...x4-8],\n";
            $base .= "  \"structured_snippets\": [{\"header\": \"Types|Service catalog|...\", \"values\": [\"max 25\", ...]}]\n";
            $base .= "}\n\n";
            $base .= "Header snippet validi: Brands, Types, Service catalog, Styles, Destinations, Models, Amenities, Courses, Shows, Neighborhoods, Insurance coverage, Degree programs, Featured hotels";
        } else {
            $base .= "LIMITI CARATTERI CAMPAGNA PERFORMANCE MAX:\n";
            $base .= "- Headlines: max 30 caratteri ciascuno, genera ESATTAMENTE 15 headlines\n";
            $base .= "- Long Headlines: max 90 caratteri ciascuno, genera ESATTAMENTE 5 long headlines\n";
            $base .= "- Descriptions: max 90 caratteri ciascuna, genera ESATTAMENTE 5 descrizioni\n";
            $base .= "- Business Name: max 25 caratteri\n";
            $base .= "- Display Path 1 e 2: max 15 caratteri ciascuno\n";
            $base .= "- Sitelink title: max 25 caratteri\n";
            $base .= "- Sitelink description 1 e 2: max 35 caratteri ciascuna\n";
            $base .= "- Callout: max 25 caratteri ciascuno\n";
            $base .= "- Structured Snippet value: max 25 caratteri ciascuno\n\n";
            $base .= "FORMATO OUTPUT JSON:\n";
            $base .= "{\n";
            $base .= "  \"campaign_name\": \"Nome Campagna\",\n";
            $base .= "  \"business_name\": \"max 25\",\n";
            $base .= "  \"headlines\": [\"H1 (max 30)\", ...x15],\n";
            $base .= "  \"long_headlines\": [\"LH1 (max 90)\", ...x5],\n";
            $base .= "  \"descriptions\": [\"D1 (max 90)\", ...x5],\n";
            $base .= "  \"display_paths\": {\"path1\": \"max15\", \"path2\": \"max15\"},\n";
            $base .= "  \"sitelinks\": [{\"title\": \"max 25\", \"desc1\": \"max 35\", \"desc2\": \"max 35\", \"url\": \"/path\"}],\n";
            $base .= "  \"callouts\": [\"max 25\", ...x4-8],\n";
            $base .= "  \"structured_snippets\": [{\"header\": \"Types|Service catalog|...\", \"values\": [\"max 25\", ...]}]\n";
            $base .= "}\n\n";
            $base .= "Header snippet validi: Brands, Types, Service catalog, Styles, Destinations, Models, Amenities, Courses, Shows, Neighborhoods, Insurance coverage, Degree programs, Featured hotels";
        }

        return $base;
    }

    private static function getCampaignGenerationUserPrompt(string $type, array $project, array $keywords): string
    {
        $prompt = "BRIEF CAMPAGNA:\n" . ($project['brief'] ?? '') . "\n\n";
        $prompt .= "URL LANDING PAGE: " . ($project['landing_url'] ?? '') . "\n\n";

        if (!empty($project['scraped_context'])) {
            $prompt .= "CONTESTO LANDING:\n" . $project['scraped_context'] . "\n\n";
        }

        // Keyword selezionate
        $positiveKw = array_filter($keywords, fn($kw) => !$kw['is_negative']);
        $negativeKw = array_filter($keywords, fn($kw) => $kw['is_negative']);

        if ($type === 'search') {
            // Raggruppate per ad group
            $groups = [];
            foreach ($positiveKw as $kw) {
                $group = $kw['ad_group_name'] ?? 'Generale';
                $groups[$group][] = $kw['keyword'];
            }
            $prompt .= "KEYWORD PER AD GROUP:\n";
            foreach ($groups as $groupName => $kws) {
                $prompt .= "- {$groupName}: " . implode(', ', $kws) . "\n";
            }
        } else {
            // PMax: search themes
            $themes = array_column($positiveKw, 'keyword');
            $prompt .= "SEARCH THEMES SELEZIONATI:\n" . implode(', ', $themes) . "\n";
        }

        if (!empty($negativeKw)) {
            $prompt .= "\nKEYWORD NEGATIVE:\n";
            foreach ($negativeKw as $nk) {
                $prompt .= "- " . $nk['keyword'] . "\n";
            }
        }

        $prompt .= "\nGenera TUTTI gli asset della campagna rispettando ESATTAMENTE i limiti di caratteri. ";
        $prompt .= "Crea copy persuasivi, orientati alla conversione, con call-to-action chiare. ";
        $prompt .= "Genera 4-6 sitelinks pertinenti, 4-8 callout e 1-2 gruppi di structured snippets con header appropriati.";

        return $prompt;
    }

    // ===== VALIDAZIONE LIMITI CARATTERI =====

    public static function validateCharLimits(array $data, string $type): array
    {
        // Headlines (30 char)
        if (isset($data['headlines'])) {
            $data['headlines'] = array_map(fn($h) => mb_substr($h, 0, self::LIMITS['headline']), $data['headlines']);
        }

        // Descriptions (90 char)
        if (isset($data['descriptions'])) {
            $data['descriptions'] = array_map(fn($d) => mb_substr($d, 0, self::LIMITS['description']), $data['descriptions']);
        }

        // Long Headlines PMax (90 char)
        if ($type === 'pmax' && isset($data['long_headlines'])) {
            $data['long_headlines'] = array_map(fn($h) => mb_substr($h, 0, self::LIMITS['long_headline']), $data['long_headlines']);
        }

        // Business Name PMax (25 char)
        if ($type === 'pmax' && isset($data['business_name'])) {
            $data['business_name'] = mb_substr($data['business_name'], 0, self::LIMITS['business_name']);
        }

        // Display paths (15 char)
        if (isset($data['display_paths'])) {
            if (isset($data['display_paths']['path1'])) {
                $data['display_paths']['path1'] = mb_substr($data['display_paths']['path1'], 0, self::LIMITS['display_path']);
            }
            if (isset($data['display_paths']['path2'])) {
                $data['display_paths']['path2'] = mb_substr($data['display_paths']['path2'], 0, self::LIMITS['display_path']);
            }
        }

        // Sitelinks
        if (isset($data['sitelinks'])) {
            foreach ($data['sitelinks'] as &$sl) {
                if (isset($sl['title'])) $sl['title'] = mb_substr($sl['title'], 0, self::LIMITS['sitelink_title']);
                if (isset($sl['desc1'])) $sl['desc1'] = mb_substr($sl['desc1'], 0, self::LIMITS['sitelink_desc']);
                if (isset($sl['desc2'])) $sl['desc2'] = mb_substr($sl['desc2'], 0, self::LIMITS['sitelink_desc']);
            }
            unset($sl);
        }

        // Callouts (25 char)
        if (isset($data['callouts'])) {
            $data['callouts'] = array_map(fn($c) => mb_substr($c, 0, self::LIMITS['callout']), $data['callouts']);
        }

        // Structured Snippets (25 char per value)
        if (isset($data['structured_snippets'])) {
            foreach ($data['structured_snippets'] as &$snippet) {
                if (isset($snippet['values'])) {
                    $snippet['values'] = array_map(fn($v) => mb_substr($v, 0, self::LIMITS['snippet_value']), $snippet['values']);
                }
            }
            unset($snippet);
        }

        return $data;
    }

    /**
     * Conta caratteri e ritorna info per la UI
     */
    public static function getCharInfo(string $text, string $assetType): array
    {
        $limit = self::LIMITS[$assetType] ?? 0;
        $len = mb_strlen($text);
        return [
            'length' => $len,
            'limit' => $limit,
            'ok' => $len <= $limit,
            'remaining' => $limit - $len,
        ];
    }

    // ===== EXPORT CSV =====

    /**
     * Genera CSV per Google Ads Editor
     */
    public static function generateCsvExport(array $campaign, string $type, array $keywords): string
    {
        $rows = [];

        $campaignName = $campaign['campaign_name'] ?? 'Campagna';
        $assets = $campaign['assets'] ?? $campaign;

        if ($type === 'search') {
            $rows = self::generateSearchCsv($campaignName, $assets, $keywords);
        } else {
            $rows = self::generatePmaxCsv($campaignName, $assets, $keywords);
        }

        // Genera CSV con BOM UTF-8
        $output = "\xEF\xBB\xBF"; // BOM
        foreach ($rows as $row) {
            $output .= self::csvLine($row);
        }

        return $output;
    }

    private static function generateSearchCsv(string $campaignName, array $assets, array $keywords): array
    {
        $rows = [];

        // Header
        $rows[] = ['Row Type', 'Campaign', 'Ad Group', 'Keyword', 'Criterion Type',
                    'Headline 1', 'Headline 2', 'Headline 3', 'Headline 4', 'Headline 5',
                    'Headline 6', 'Headline 7', 'Headline 8', 'Headline 9', 'Headline 10',
                    'Headline 11', 'Headline 12', 'Headline 13', 'Headline 14', 'Headline 15',
                    'Description 1', 'Description 2', 'Description 3', 'Description 4',
                    'Path 1', 'Path 2', 'Final URL',
                    'Sitelink Text', 'Sitelink Description Line 1', 'Sitelink Description Line 2', 'Sitelink Final URL',
                    'Callout Text', 'Structured Snippet Header', 'Structured Snippet Values'];

        // Campaign row
        $rows[] = array_merge(['Campaign', $campaignName], array_fill(0, 32, ''));

        // Raggruppa keyword per ad group
        $adGroups = [];
        foreach ($keywords as $kw) {
            if ($kw['is_negative']) continue;
            $group = $kw['ad_group_name'] ?? 'Generale';
            $adGroups[$group][] = $kw;
        }

        foreach ($adGroups as $groupName => $kws) {
            // Ad Group row
            $rows[] = array_merge(['Ad Group', $campaignName, $groupName], array_fill(0, 31, ''));

            // RSA row per ad group
            $headlines = $assets['headlines'] ?? [];
            $descriptions = $assets['descriptions'] ?? [];
            $paths = $assets['display_paths'] ?? [];

            $rsaRow = ['Ad', $campaignName, $groupName, '', ''];
            for ($i = 0; $i < 15; $i++) {
                $rsaRow[] = $headlines[$i] ?? '';
            }
            for ($i = 0; $i < 4; $i++) {
                $rsaRow[] = $descriptions[$i] ?? '';
            }
            $rsaRow[] = $paths['path1'] ?? '';
            $rsaRow[] = $paths['path2'] ?? '';
            $rsaRow[] = ''; // Final URL da compilare
            $rsaRow = array_merge($rsaRow, array_fill(0, 7, ''));
            $rows[] = $rsaRow;

            // Keywords
            foreach ($kws as $kw) {
                $kwText = $kw['keyword'];
                $matchType = ucfirst($kw['match_type'] ?? 'broad');
                $kwRow = ['Keyword', $campaignName, $groupName, $kwText, $matchType];
                $kwRow = array_merge($kwRow, array_fill(0, 29, ''));
                $rows[] = $kwRow;
            }
        }

        // Negative keywords (campaign level)
        $negatives = array_filter($keywords, fn($kw) => $kw['is_negative']);
        foreach ($negatives as $nk) {
            $nkRow = ['Negative Keyword', $campaignName, '', $nk['keyword'], ucfirst($nk['match_type'] ?? 'phrase')];
            $nkRow = array_merge($nkRow, array_fill(0, 29, ''));
            $rows[] = $nkRow;
        }

        // Sitelinks
        foreach (($assets['sitelinks'] ?? []) as $sl) {
            $slRow = array_fill(0, 27, '');
            $slRow[0] = 'Sitelink';
            $slRow[1] = $campaignName;
            $slRow[27] = $sl['title'] ?? '';
            $slRow[28] = $sl['desc1'] ?? '';
            $slRow[29] = $sl['desc2'] ?? '';
            $slRow[30] = $sl['url'] ?? '';
            $slRow[] = '';
            $slRow[] = '';
            $slRow[] = '';
            $rows[] = $slRow;
        }

        // Callouts
        foreach (($assets['callouts'] ?? []) as $callout) {
            $cRow = array_fill(0, 31, '');
            $cRow[0] = 'Callout';
            $cRow[1] = $campaignName;
            $cRow[31] = $callout;
            $cRow[] = '';
            $cRow[] = '';
            $rows[] = $cRow;
        }

        // Structured Snippets
        foreach (($assets['structured_snippets'] ?? []) as $snippet) {
            $ssRow = array_fill(0, 32, '');
            $ssRow[0] = 'Structured Snippet';
            $ssRow[1] = $campaignName;
            $ssRow[32] = $snippet['header'] ?? '';
            $ssRow[33] = implode(';', $snippet['values'] ?? []);
            $rows[] = $ssRow;
        }

        return $rows;
    }

    private static function generatePmaxCsv(string $campaignName, array $assets, array $keywords): array
    {
        $rows = [];

        // Header
        $rows[] = ['Row Type', 'Campaign', 'Asset Group', 'Asset Type', 'Asset Text',
                    'Final URL', 'Business Name',
                    'Sitelink Text', 'Sitelink Description Line 1', 'Sitelink Description Line 2', 'Sitelink Final URL',
                    'Callout Text', 'Structured Snippet Header', 'Structured Snippet Values',
                    'Search Theme', 'Keyword', 'Criterion Type'];

        // Campaign row
        $rows[] = array_merge(['Campaign', $campaignName], array_fill(0, 15, ''));

        // Asset Group
        $assetGroupName = $campaignName . ' - Asset Group';
        $agRow = array_merge(['Asset Group', $campaignName, $assetGroupName], array_fill(0, 14, ''));
        $agRow[6] = $assets['business_name'] ?? '';
        $rows[] = $agRow;

        // Headlines
        foreach (($assets['headlines'] ?? []) as $h) {
            $row = array_fill(0, 17, '');
            $row[0] = 'Asset';
            $row[1] = $campaignName;
            $row[2] = $assetGroupName;
            $row[3] = 'Headline';
            $row[4] = $h;
            $rows[] = $row;
        }

        // Long Headlines
        foreach (($assets['long_headlines'] ?? []) as $lh) {
            $row = array_fill(0, 17, '');
            $row[0] = 'Asset';
            $row[1] = $campaignName;
            $row[2] = $assetGroupName;
            $row[3] = 'Long Headline';
            $row[4] = $lh;
            $rows[] = $row;
        }

        // Descriptions
        foreach (($assets['descriptions'] ?? []) as $d) {
            $row = array_fill(0, 17, '');
            $row[0] = 'Asset';
            $row[1] = $campaignName;
            $row[2] = $assetGroupName;
            $row[3] = 'Description';
            $row[4] = $d;
            $rows[] = $row;
        }

        // Search Themes
        $positiveKw = array_filter($keywords, fn($kw) => !$kw['is_negative']);
        foreach ($positiveKw as $kw) {
            $row = array_fill(0, 17, '');
            $row[0] = 'Search Theme';
            $row[1] = $campaignName;
            $row[2] = $assetGroupName;
            $row[14] = $kw['keyword'];
            $rows[] = $row;
        }

        // Negative keywords
        $negatives = array_filter($keywords, fn($kw) => $kw['is_negative']);
        foreach ($negatives as $nk) {
            $row = array_fill(0, 17, '');
            $row[0] = 'Negative Keyword';
            $row[1] = $campaignName;
            $row[15] = $nk['keyword'];
            $row[16] = ucfirst($nk['match_type'] ?? 'phrase');
            $rows[] = $row;
        }

        // Sitelinks
        foreach (($assets['sitelinks'] ?? []) as $sl) {
            $row = array_fill(0, 17, '');
            $row[0] = 'Sitelink';
            $row[1] = $campaignName;
            $row[7] = $sl['title'] ?? '';
            $row[8] = $sl['desc1'] ?? '';
            $row[9] = $sl['desc2'] ?? '';
            $row[10] = $sl['url'] ?? '';
            $rows[] = $row;
        }

        // Callouts
        foreach (($assets['callouts'] ?? []) as $callout) {
            $row = array_fill(0, 17, '');
            $row[0] = 'Callout';
            $row[1] = $campaignName;
            $row[11] = $callout;
            $rows[] = $row;
        }

        // Structured Snippets
        foreach (($assets['structured_snippets'] ?? []) as $snippet) {
            $row = array_fill(0, 17, '');
            $row[0] = 'Structured Snippet';
            $row[1] = $campaignName;
            $row[12] = $snippet['header'] ?? '';
            $row[13] = implode(';', $snippet['values'] ?? []);
            $rows[] = $row;
        }

        return $rows;
    }

    private static function csvLine(array $fields): string
    {
        $escaped = array_map(function ($field) {
            $field = (string) $field;
            if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
                return '"' . str_replace('"', '""', $field) . '"';
            }
            return $field;
        }, $fields);
        return implode(',', $escaped) . "\r\n";
    }

    // ===== COPIA TESTO =====

    /**
     * Genera testo formattato per copia/incolla
     */
    public static function generateCopyText(array $campaign, string $type, array $keywords): string
    {
        $assets = $campaign['assets'] ?? $campaign;
        $text = "";

        $text .= "=== CAMPAGNA: " . ($campaign['campaign_name'] ?? 'Campagna') . " ===\n";
        $text .= "Tipo: " . ($type === 'pmax' ? 'Performance Max' : 'Search') . "\n\n";

        // Business Name (PMax)
        if ($type === 'pmax' && !empty($assets['business_name'])) {
            $text .= "--- BUSINESS NAME ---\n";
            $text .= $assets['business_name'] . "\n\n";
        }

        // Headlines
        $text .= "--- HEADLINES ---\n";
        foreach (($assets['headlines'] ?? []) as $i => $h) {
            $text .= ($i + 1) . ". {$h} [" . mb_strlen($h) . "/30]\n";
        }
        $text .= "\n";

        // Long Headlines (PMax)
        if ($type === 'pmax' && !empty($assets['long_headlines'])) {
            $text .= "--- LONG HEADLINES ---\n";
            foreach ($assets['long_headlines'] as $i => $lh) {
                $text .= ($i + 1) . ". {$lh} [" . mb_strlen($lh) . "/90]\n";
            }
            $text .= "\n";
        }

        // Descriptions
        $text .= "--- DESCRIPTIONS ---\n";
        foreach (($assets['descriptions'] ?? []) as $i => $d) {
            $text .= ($i + 1) . ". {$d} [" . mb_strlen($d) . "/90]\n";
        }
        $text .= "\n";

        // Display Paths
        if (!empty($assets['display_paths'])) {
            $text .= "--- DISPLAY PATHS ---\n";
            $text .= "Path 1: " . ($assets['display_paths']['path1'] ?? '') . "\n";
            $text .= "Path 2: " . ($assets['display_paths']['path2'] ?? '') . "\n\n";
        }

        // Keywords / Search Themes
        $positiveKw = array_filter($keywords, fn($kw) => !$kw['is_negative']);
        $negativeKw = array_filter($keywords, fn($kw) => $kw['is_negative']);

        if ($type === 'search') {
            $text .= "--- KEYWORDS ---\n";
            $groups = [];
            foreach ($positiveKw as $kw) {
                $group = $kw['ad_group_name'] ?? 'Generale';
                $groups[$group][] = $kw;
            }
            foreach ($groups as $groupName => $kws) {
                $text .= "\nAd Group: {$groupName}\n";
                foreach ($kws as $kw) {
                    $formatted = self::formatKeywordMatchType($kw['keyword'], $kw['match_type'] ?? 'broad');
                    $text .= "  {$formatted}\n";
                }
            }
        } else {
            $text .= "--- SEARCH THEMES ---\n";
            foreach ($positiveKw as $kw) {
                $text .= "- " . $kw['keyword'] . "\n";
            }
        }
        $text .= "\n";

        // Negative Keywords
        if (!empty($negativeKw)) {
            $text .= "--- KEYWORD NEGATIVE ---\n";
            foreach ($negativeKw as $nk) {
                $formatted = self::formatKeywordMatchType($nk['keyword'], $nk['match_type'] ?? 'phrase');
                $text .= "  {$formatted}\n";
            }
            $text .= "\n";
        }

        // Sitelinks
        if (!empty($assets['sitelinks'])) {
            $text .= "--- SITELINKS ---\n";
            foreach ($assets['sitelinks'] as $i => $sl) {
                $text .= ($i + 1) . ". " . ($sl['title'] ?? '') . " [" . mb_strlen($sl['title'] ?? '') . "/25]\n";
                $text .= "   Desc 1: " . ($sl['desc1'] ?? '') . "\n";
                $text .= "   Desc 2: " . ($sl['desc2'] ?? '') . "\n";
                if (!empty($sl['url'])) $text .= "   URL: " . $sl['url'] . "\n";
                $text .= "\n";
            }
        }

        // Callouts
        if (!empty($assets['callouts'])) {
            $text .= "--- CALLOUTS ---\n";
            foreach ($assets['callouts'] as $c) {
                $text .= "- {$c} [" . mb_strlen($c) . "/25]\n";
            }
            $text .= "\n";
        }

        // Structured Snippets
        if (!empty($assets['structured_snippets'])) {
            $text .= "--- STRUCTURED SNIPPETS ---\n";
            foreach ($assets['structured_snippets'] as $snippet) {
                $text .= "Header: " . ($snippet['header'] ?? '') . "\n";
                $text .= "Valori: " . implode(', ', $snippet['values'] ?? []) . "\n\n";
            }
        }

        return $text;
    }

    private static function formatKeywordMatchType(string $keyword, string $matchType): string
    {
        return match ($matchType) {
            'exact' => "[{$keyword}]",
            'phrase' => "\"{$keyword}\"",
            default => $keyword,
        };
    }

    // ===== UTILITY =====

    private static function parseJsonResponse(string $response): ?array
    {
        // Rimuovi eventuali markdown code blocks
        $response = preg_replace('/^```(?:json)?\s*/i', '', $response);
        $response = preg_replace('/\s*```\s*$/', '', $response);
        $response = trim($response);

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("CampaignCreatorService: JSON parse error: " . json_last_error_msg() . " - Response: " . substr($response, 0, 500));
            return null;
        }

        return $data;
    }

    /**
     * Ottiene il costo di un'operazione dal module.json
     */
    public static function getCost(string $operation): float
    {
        return (float) ModuleLoader::getSetting('ads-analyzer', 'cost_creator_' . $operation, self::getDefaultCost($operation));
    }

    private static function getDefaultCost(string $operation): float
    {
        return match ($operation) {
            'scrape' => 1,
            'keywords' => 3,
            'campaign' => 5,
            default => 1,
        };
    }
}
