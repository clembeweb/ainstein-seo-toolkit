<?php

namespace Modules\AdsAnalyzer\Services;

use Services\AiService;
use Core\Database;
use Core\ModuleLoader;
use Modules\KeywordResearch\Services\KeywordInsightService;

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
     * Fase 1: AI genera seed keywords dal brief/contesto
     */
    /**
     * Location valide per Google Keyword Insight API
     */
    const VALID_LOCATIONS = [
        'IT', 'US', 'GB', 'DE', 'FR', 'ES', 'BR', 'AU', 'CA', 'IN',
        'NL', 'BE', 'CH', 'AT', 'PT', 'MX', 'AR', 'CO', 'CL', 'PE',
        'SE', 'NO', 'DK', 'FI', 'PL', 'CZ', 'RO', 'HU', 'GR', 'TR',
        'RU', 'JP', 'KR', 'CN', 'TW', 'HK', 'SG', 'MY', 'TH', 'ID',
        'PH', 'VN', 'ZA', 'EG', 'NG', 'KE', 'AE', 'SA', 'IL', 'IE',
        'NZ', 'UA',
    ];

    const VALID_LANGS = [
        'it', 'en', 'de', 'fr', 'es', 'pt', 'nl', 'sv', 'no', 'da',
        'fi', 'pl', 'cs', 'ro', 'hu', 'el', 'tr', 'ru', 'ja', 'ko',
        'zh', 'th', 'vi', 'id', 'ms', 'ar', 'he', 'uk',
    ];

    public static function generateSeedKeywords(int $userId, array $project): array
    {
        $ai = new AiService('ads-analyzer');
        if (!$ai->isConfigured()) {
            return ['error' => true, 'message' => 'AI non configurata.'];
        }

        $brief = $project['brief'] ?? '';
        $context = $project['scraped_context'] ?? '';
        $type = $project['campaign_type_gads'];
        $typeLabel = $type === 'pmax' ? 'Performance Max' : 'Search';

        $prompt = "Analizza il seguente contesto e genera 5-8 seed keyword per una campagna Google Ads {$typeLabel}.\n";
        $prompt .= "Le seed keyword devono essere i termini di ricerca principali che un potenziale cliente userebbe.\n";
        $prompt .= "Scegli keyword CORTE (1-3 parole) con alto potenziale commerciale/transazionale per Google Ads.\n";
        $prompt .= "Le seed keyword devono essere nella lingua del mercato target della campagna.\n\n";

        if (!empty($brief)) {
            $prompt .= "BRIEF CAMPAGNA:\n{$brief}\n\n";
        }
        if (!empty($context)) {
            $prompt .= "CONTESTO LANDING PAGE:\n{$context}\n\n";
        }
        if (!empty($project['landing_url'])) {
            $prompt .= "URL: {$project['landing_url']}\n\n";
        }

        $prompt .= "IMPORTANTE: Rileva anche il mercato target e la lingua della campagna.\n";
        $prompt .= "- location: codice paese ISO 2 lettere del mercato target (dove cercherebbero i clienti, es. IT, US, GB, DE, FR, ES)\n";
        $prompt .= "- lang: codice lingua ISO 2 lettere dei clienti target (es. it, en, de, fr, es)\n";
        $prompt .= "Analizza il brief, la landing page e il contesto per determinare a quale mercato/lingua si rivolge la campagna.\n\n";
        $prompt .= "Rispondi SOLO con JSON: {\"seeds\": [\"keyword1\", \"keyword2\", ...], \"location\": \"XX\", \"lang\": \"xx\"}";

        $result = $ai->analyze($userId, $prompt, '', 'ads-analyzer');
        Database::reconnect();

        if (!empty($result['error'])) {
            return $result;
        }

        $parsed = self::parseJsonResponse($result['result'] ?? '');
        if ($parsed === null || empty($parsed['seeds'])) {
            return ['error' => true, 'message' => 'Errore nella generazione seed keywords.'];
        }

        // Valida location e lang (fallback IT/it)
        $location = strtoupper($parsed['location'] ?? 'IT');
        $lang = strtolower($parsed['lang'] ?? 'it');
        if (!in_array($location, self::VALID_LOCATIONS)) $location = 'IT';
        if (!in_array($lang, self::VALID_LANGS)) $lang = 'it';

        return [
            'success' => true,
            'seeds' => array_slice($parsed['seeds'], 0, 8),
            'location' => $location,
            'lang' => $lang,
        ];
    }

    /**
     * Fase 2: Espande seed con Google Keyword Insight API → keyword reali con volumi
     */
    public static function expandAndFilterKeywords(array $seeds, string $location = 'US', string $lang = 'en', int $minVolume = 10): array
    {
        $service = new KeywordInsightService();
        if (!$service->isConfigured()) {
            return ['success' => false, 'error' => 'API keyword non configurata (RapidAPI key mancante).'];
        }

        $expandResult = $service->expandSeeds($seeds, $location, $lang);

        Database::reconnect();

        if (empty($expandResult['keywords'])) {
            return ['success' => false, 'error' => 'Nessuna keyword trovata per i seed forniti.'];
        }

        // Filtra per volume minimo
        $filterResult = $service->filterKeywords($expandResult['keywords'], [], $minVolume);

        return [
            'success' => true,
            'keywords' => $filterResult['keywords'],
            'raw_count' => count($expandResult['keywords']),
            'filtered_count' => count($filterResult['keywords']),
            'errors' => $expandResult['errors'] ?? [],
        ];
    }

    /**
     * Fase 3: AI organizza keyword reali in ad groups/search themes
     */
    public static function generateKeywordResearch(int $userId, array $project, array $realKeywords = []): array
    {
        $ai = new AiService('ads-analyzer');
        if (!$ai->isConfigured()) {
            return ['error' => true, 'message' => 'AI non configurata. Configura il provider AI nelle impostazioni.'];
        }

        $type = $project['campaign_type_gads'];

        // Se abbiamo keyword reali, usa il prompt con volumi
        if (!empty($realKeywords)) {
            $systemPrompt = self::getKeywordResearchWithVolumesSystemPrompt($type);
            $userPrompt = self::getKeywordResearchWithVolumesUserPrompt($type, $project, $realKeywords);
        } else {
            // Fallback: prompt originale senza volumi
            $scrapedContent = mb_substr($project['scraped_content'] ?? '', 0, 6000);
            $scrapedContext = $project['scraped_context'] ?? '';
            $systemPrompt = self::getKeywordResearchSystemPrompt($type);
            $userPrompt = self::getKeywordResearchUserPrompt($type, $project['brief'], $project['landing_url'], $scrapedContext, $scrapedContent);
        }

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

    /**
     * Genera brief automatico dal contesto scraping
     */
    public static function generateBriefFromContext(int $userId, string $context, string $content, string $campaignType): string
    {
        $ai = new AiService('ads-analyzer');
        if (!$ai->isConfigured()) return '';

        $typeLabel = $campaignType === 'pmax' ? 'Performance Max' : 'Search';
        $contentSnippet = mb_substr($content, 0, 3000);

        $prompt = "Basandoti sul contesto e contenuto di questa landing page, genera un brief per campagna Google Ads {$typeLabel}.\n\n";
        $prompt .= "CONTESTO ESTRATTO:\n{$context}\n\n";
        if (!empty($contentSnippet)) {
            $prompt .= "CONTENUTO PAGINA:\n{$contentSnippet}\n\n";
        }
        $prompt .= "Il brief deve includere:\n";
        $prompt .= "- Obiettivo della campagna (lead generation, vendite, awareness)\n";
        $prompt .= "- Target di riferimento (chi sono i clienti ideali)\n";
        $prompt .= "- Prodotti/servizi da promuovere\n";
        $prompt .= "- Tono di voce (professionale, commerciale, etc.)\n";
        $prompt .= "- USP e punti di forza da evidenziare\n\n";
        $prompt .= "Rispondi SOLO con il testo del brief (150-400 parole), senza preamboli.";

        $result = $ai->analyze($userId, $prompt, '', 'ads-analyzer');
        Database::reconnect();

        if (!empty($result['error'])) return '';

        $brief = trim($result['result'] ?? '');
        $brief = preg_replace('/^```[\w]*\n?/', '', $brief);
        $brief = preg_replace('/\n?```$/', '', $brief);

        return trim($brief);
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
        $prompt = '';

        if (!empty($brief)) {
            $prompt .= "BRIEF CAMPAGNA:\n{$brief}\n\n";
        }

        if (!empty($url)) {
            $prompt .= "URL LANDING PAGE: {$url}\n\n";
        }

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

    // ===== PROMPT KEYWORD RESEARCH CON VOLUMI REALI =====

    private static function getKeywordResearchWithVolumesSystemPrompt(string $type): string
    {
        $base = "Sei un esperto certificato Google Ads con 15 anni di esperienza in keyword research e strutturazione campagne.\n";
        $base .= "Rispondi ESCLUSIVAMENTE con un oggetto JSON valido, senza testo aggiuntivo, senza markdown.\n\n";
        $base .= "REGOLA FONDAMENTALE: Usa SOLO keyword dalla lista fornita. NON inventare keyword nuove.\n";
        $base .= "Ogni keyword ha dati reali di Google Ads (volume, CPC, competition, intent).\n\n";

        if ($type === 'search') {
            $base .= "Devi organizzare le keyword in ad group tematici per una campagna Google Ads Search (RSA).\n";
            $base .= "Crea 3-6 ad group tematici con 5-15 keyword per gruppo.\n";
            $base .= "Assegna match type appropriati:\n";
            $base .= "- exact: keyword ad alto volume e alta precisione (main keyword del gruppo)\n";
            $base .= "- phrase: keyword a volume medio, buon bilanciamento\n";
            $base .= "- broad: keyword a coda lunga per scoperta\n";
            $base .= "Genera keyword negative per escludere traffico non pertinente.\n";
            $base .= "Seleziona keyword con volume di ricerca adeguato per campagne Google Ads.\n\n";
            $base .= "FORMATO OUTPUT JSON:\n";
            $base .= "{\n";
            $base .= "  \"ad_groups\": [{\"name\": \"Nome Gruppo\", \"theme\": \"Tema\", \"keywords\": [{\"text\": \"keyword\", \"match_type\": \"broad|phrase|exact\", \"intent\": \"transactional|informational|navigational\"}]}],\n";
            $base .= "  \"negative_keywords\": [{\"text\": \"keyword\", \"match_type\": \"phrase\", \"reason\": \"Motivo\"}],\n";
            $base .= "  \"notes\": \"Note strategiche\"\n";
            $base .= "}";
        } else {
            $base .= "Devi selezionare i migliori search themes per una campagna Google Ads Performance Max.\n";
            $base .= "I search themes devono avere max 80 caratteri (max 25 temi).\n";
            $base .= "Seleziona i temi con maggiore rilevanza commerciale e volume.\n";
            $base .= "Genera keyword negative per escludere traffico non pertinente.\n\n";
            $base .= "FORMATO OUTPUT JSON:\n";
            $base .= "{\n";
            $base .= "  \"search_themes\": [{\"text\": \"tema di ricerca\", \"relevance\": \"high|medium\"}],\n";
            $base .= "  \"negative_keywords\": [{\"text\": \"keyword\", \"match_type\": \"phrase\", \"reason\": \"Motivo\"}],\n";
            $base .= "  \"notes\": \"Note strategiche\"\n";
            $base .= "}";
        }

        return $base;
    }

    private static function getKeywordResearchWithVolumesUserPrompt(string $type, array $project, array $realKeywords): string
    {
        $prompt = '';

        if (!empty($project['brief'])) {
            $prompt .= "BRIEF CAMPAGNA:\n{$project['brief']}\n\n";
        }
        if (!empty($project['landing_url'])) {
            $prompt .= "URL LANDING: {$project['landing_url']}\n\n";
        }
        if (!empty($project['scraped_context'])) {
            $prompt .= "CONTESTO LANDING:\n{$project['scraped_context']}\n\n";
        }

        // Lista keyword con metriche reali
        $prompt .= "KEYWORD REALI CON METRICHE GOOGLE ADS:\n";
        foreach ($realKeywords as $kw) {
            $vol = number_format($kw['volume'] ?? 0);
            $cpc = number_format(($kw['high_bid'] ?? $kw['low_bid'] ?? 0), 2);
            $comp = $kw['competition_level'] ?? 'n/a';
            $intent = $kw['intent'] ?? 'n/a';
            $prompt .= "- \"{$kw['text']}\" (vol: {$vol}, CPC: €{$cpc}, comp: {$comp}, intent: {$intent})\n";
        }

        $prompt .= "\n";

        if ($type === 'search') {
            $prompt .= "Organizza queste keyword REALI in 3-6 ad group tematici per campagna Search RSA.\n";
            $prompt .= "Usa SOLO keyword dalla lista sopra. NON inventarne di nuove.\n";
            $prompt .= "Assegna match type in base al volume: exact per le principali, phrase per medie, broad per coda lunga.\n";
            $prompt .= "Escludi keyword non pertinenti al business. Genera keyword negative rilevanti.";
        } else {
            $prompt .= "Seleziona i migliori search themes dalla lista per campagna Performance Max (max 80 char, max 25 temi).\n";
            $prompt .= "Usa SOLO keyword dalla lista sopra. Genera keyword negative rilevanti.";
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
            $base .= "  \"structured_snippets\": [{\"header\": \"Types|Service catalog|...\", \"values\": [\"max 25\", ...]}],\n";
            $base .= "  \"daily_budget\": {\"conservative\": 10.00, \"moderate\": 25.00, \"aggressive\": 50.00, \"currency\": \"EUR\", \"rationale\": \"Spiegazione breve della logica di budget\"}\n";
            $base .= "}\n\n";
            $base .= "BUDGET GIORNALIERO: Calcola 3 livelli di budget giornaliero basandoti sui CPC medi delle keyword, i volumi di ricerca e la competizione. ";
            $base .= "Conservativo = copertura parziale, minimo rischio. Moderato = buon bilanciamento copertura/costo. Aggressivo = massima copertura e impression share.\n\n";
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
            $base .= "  \"structured_snippets\": [{\"header\": \"Types|Service catalog|...\", \"values\": [\"max 25\", ...]}],\n";
            $base .= "  \"daily_budget\": {\"conservative\": 10.00, \"moderate\": 25.00, \"aggressive\": 50.00, \"currency\": \"EUR\", \"rationale\": \"Spiegazione breve della logica di budget\"}\n";
            $base .= "}\n\n";
            $base .= "BUDGET GIORNALIERO: Calcola 3 livelli di budget giornaliero basandoti sui CPC medi delle keyword, i volumi di ricerca e la competizione. ";
            $base .= "Conservativo = copertura parziale, minimo rischio. Moderato = buon bilanciamento copertura/costo. Aggressivo = massima copertura e impression share.\n\n";
            $base .= "Header snippet validi: Brands, Types, Service catalog, Styles, Destinations, Models, Amenities, Courses, Shows, Neighborhoods, Insurance coverage, Degree programs, Featured hotels";
        }

        return $base;
    }

    private static function getCampaignGenerationUserPrompt(string $type, array $project, array $keywords): string
    {
        $prompt = '';

        if (!empty($project['brief'])) {
            $prompt .= "BRIEF CAMPAGNA:\n" . $project['brief'] . "\n\n";
        }

        if (!empty($project['landing_url'])) {
            $prompt .= "URL LANDING PAGE: " . $project['landing_url'] . "\n\n";
        }

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

        // Dati CPC/volume per calcolo budget
        $cpcValues = [];
        $totalVolume = 0;
        foreach ($positiveKw as $kw) {
            if (!empty($kw['cpc']) && $kw['cpc'] > 0) {
                $cpcValues[] = (float) $kw['cpc'];
            }
            $totalVolume += (int) ($kw['search_volume'] ?? 0);
        }
        if (!empty($cpcValues)) {
            $avgCpc = round(array_sum($cpcValues) / count($cpcValues), 2);
            $maxCpc = round(max($cpcValues), 2);
            $prompt .= "\nDATI PER CALCOLO BUDGET:\n";
            $prompt .= "- CPC medio keyword: €{$avgCpc}\n";
            $prompt .= "- CPC massimo: €{$maxCpc}\n";
            $prompt .= "- Volume ricerca mensile totale: {$totalVolume}\n";
            $prompt .= "- Numero keyword positive: " . count($positiveKw) . "\n\n";
        }

        $prompt .= "Genera TUTTI gli asset della campagna rispettando ESATTAMENTE i limiti di caratteri. ";
        $prompt .= "Crea copy persuasivi, orientati alla conversione, con call-to-action chiare. ";
        $prompt .= "Genera 4-6 sitelinks pertinenti, 4-8 callout e 1-2 gruppi di structured snippets con header appropriati. ";
        $prompt .= "Includi la raccomandazione budget giornaliero a 3 livelli basata sui dati CPC e volumi.";

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
     * Genera CSV compatibile con Google Ads Editor
     */
    public static function generateCsvExport(array $campaign, string $type, array $keywords, string $landingUrl = '', string $budgetLevel = 'moderate'): string
    {
        $rows = [];

        $campaignName = $campaign['campaign_name'] ?? 'Campagna';
        $assets = $campaign['assets'] ?? $campaign;

        // Calcola daily budget dal livello scelto
        $dailyBudget = '';
        if (!empty($assets['daily_budget'][$budgetLevel])) {
            $dailyBudget = (string) $assets['daily_budget'][$budgetLevel];
        }

        if ($type === 'search') {
            $rows = self::generateSearchCsv($campaignName, $assets, $keywords, $landingUrl, $dailyBudget);
        } else {
            $rows = self::generatePmaxCsv($campaignName, $assets, $keywords, $landingUrl, $dailyBudget);
        }

        // Genera CSV con BOM UTF-8
        $output = "\xEF\xBB\xBF"; // BOM
        foreach ($rows as $row) {
            $output .= self::csvLine($row);
        }

        return $output;
    }

    private static function generateSearchCsv(string $campaignName, array $assets, array $keywords, string $landingUrl = '', string $dailyBudget = ''): array
    {
        $rows = [];
        $totalCols = 35; // Numero totale colonne

        // Header — nomi colonna compatibili Google Ads Editor
        $rows[] = ['Row Type', 'Campaign', 'Daily Budget', 'Ad Group', 'Keyword', 'Criterion Type',
                    'Headline 1', 'Headline 2', 'Headline 3', 'Headline 4', 'Headline 5',
                    'Headline 6', 'Headline 7', 'Headline 8', 'Headline 9', 'Headline 10',
                    'Headline 11', 'Headline 12', 'Headline 13', 'Headline 14', 'Headline 15',
                    'Description 1', 'Description 2', 'Description 3', 'Description 4',
                    'Path 1', 'Path 2', 'Final URL',
                    'Sitelink Text', 'Sitelink Description Line 1', 'Sitelink Description Line 2', 'Sitelink Final URL',
                    'Callout Text', 'Structured Snippet Header', 'Structured Snippet Values'];

        // Campaign row con Daily Budget
        $rows[] = self::csvRow($totalCols, [0 => 'Campaign', 1 => $campaignName, 2 => $dailyBudget]);

        // Raggruppa keyword per ad group
        $adGroups = [];
        foreach ($keywords as $kw) {
            if ($kw['is_negative']) continue;
            $group = $kw['ad_group_name'] ?? 'Generale';
            $adGroups[$group][] = $kw;
        }

        $headlines = $assets['headlines'] ?? [];
        $descriptions = $assets['descriptions'] ?? [];
        $paths = $assets['display_paths'] ?? [];

        foreach ($adGroups as $groupName => $kws) {
            // Ad Group row
            $rows[] = self::csvRow($totalCols, [0 => 'Ad Group', 1 => $campaignName, 3 => $groupName]);

            // RSA row per ad group
            $rsaData = [0 => 'Ad', 1 => $campaignName, 3 => $groupName];
            for ($i = 0; $i < 15; $i++) {
                $rsaData[6 + $i] = $headlines[$i] ?? '';
            }
            for ($i = 0; $i < 4; $i++) {
                $rsaData[21 + $i] = $descriptions[$i] ?? '';
            }
            $rsaData[25] = $paths['path1'] ?? '';
            $rsaData[26] = $paths['path2'] ?? '';
            $rsaData[27] = $landingUrl; // Final URL
            $rows[] = self::csvRow($totalCols, $rsaData);

            // Keywords
            foreach ($kws as $kw) {
                $rows[] = self::csvRow($totalCols, [
                    0 => 'Keyword', 1 => $campaignName, 3 => $groupName,
                    4 => $kw['keyword'], 5 => ucfirst($kw['match_type'] ?? 'broad'),
                ]);
            }
        }

        // Negative keywords (campaign level)
        $negatives = array_filter($keywords, fn($kw) => $kw['is_negative']);
        foreach ($negatives as $nk) {
            $rows[] = self::csvRow($totalCols, [
                0 => 'Negative Keyword', 1 => $campaignName,
                4 => $nk['keyword'], 5 => ucfirst($nk['match_type'] ?? 'phrase'),
            ]);
        }

        // Sitelinks
        foreach (($assets['sitelinks'] ?? []) as $sl) {
            $rows[] = self::csvRow($totalCols, [
                0 => 'Sitelink', 1 => $campaignName,
                28 => $sl['title'] ?? '',
                29 => $sl['desc1'] ?? '',
                30 => $sl['desc2'] ?? '',
                31 => self::resolveUrl($sl['url'] ?? '', $landingUrl),
            ]);
        }

        // Callouts
        foreach (($assets['callouts'] ?? []) as $callout) {
            $rows[] = self::csvRow($totalCols, [0 => 'Callout', 1 => $campaignName, 32 => $callout]);
        }

        // Structured Snippets
        foreach (($assets['structured_snippets'] ?? []) as $snippet) {
            $rows[] = self::csvRow($totalCols, [
                0 => 'Structured Snippet', 1 => $campaignName,
                33 => $snippet['header'] ?? '',
                34 => implode(';', $snippet['values'] ?? []),
            ]);
        }

        return $rows;
    }

    private static function generatePmaxCsv(string $campaignName, array $assets, array $keywords, string $landingUrl = '', string $dailyBudget = ''): array
    {
        $rows = [];
        $totalCols = 18;

        // Header
        $rows[] = ['Row Type', 'Campaign', 'Daily Budget', 'Asset Group', 'Asset Type', 'Asset Text',
                    'Final URL', 'Business Name',
                    'Sitelink Text', 'Sitelink Description Line 1', 'Sitelink Description Line 2', 'Sitelink Final URL',
                    'Callout Text', 'Structured Snippet Header', 'Structured Snippet Values',
                    'Search Theme', 'Keyword', 'Criterion Type'];

        // Campaign row con Daily Budget
        $rows[] = self::csvRow($totalCols, [0 => 'Campaign', 1 => $campaignName, 2 => $dailyBudget]);

        // Asset Group
        $assetGroupName = $campaignName . ' - Asset Group';
        $rows[] = self::csvRow($totalCols, [
            0 => 'Asset Group', 1 => $campaignName, 3 => $assetGroupName,
            6 => $landingUrl, 7 => $assets['business_name'] ?? '',
        ]);

        // Headlines
        foreach (($assets['headlines'] ?? []) as $h) {
            $rows[] = self::csvRow($totalCols, [
                0 => 'Asset', 1 => $campaignName, 3 => $assetGroupName, 4 => 'Headline', 5 => $h,
            ]);
        }

        // Long Headlines
        foreach (($assets['long_headlines'] ?? []) as $lh) {
            $rows[] = self::csvRow($totalCols, [
                0 => 'Asset', 1 => $campaignName, 3 => $assetGroupName, 4 => 'Long Headline', 5 => $lh,
            ]);
        }

        // Descriptions
        foreach (($assets['descriptions'] ?? []) as $d) {
            $rows[] = self::csvRow($totalCols, [
                0 => 'Asset', 1 => $campaignName, 3 => $assetGroupName, 4 => 'Description', 5 => $d,
            ]);
        }

        // Search Themes
        $positiveKw = array_filter($keywords, fn($kw) => !$kw['is_negative']);
        foreach ($positiveKw as $kw) {
            $rows[] = self::csvRow($totalCols, [
                0 => 'Search Theme', 1 => $campaignName, 3 => $assetGroupName, 15 => $kw['keyword'],
            ]);
        }

        // Negative keywords
        $negatives = array_filter($keywords, fn($kw) => $kw['is_negative']);
        foreach ($negatives as $nk) {
            $rows[] = self::csvRow($totalCols, [
                0 => 'Negative Keyword', 1 => $campaignName,
                16 => $nk['keyword'], 17 => ucfirst($nk['match_type'] ?? 'phrase'),
            ]);
        }

        // Sitelinks
        foreach (($assets['sitelinks'] ?? []) as $sl) {
            $rows[] = self::csvRow($totalCols, [
                0 => 'Sitelink', 1 => $campaignName,
                8 => $sl['title'] ?? '', 9 => $sl['desc1'] ?? '', 10 => $sl['desc2'] ?? '',
                11 => self::resolveUrl($sl['url'] ?? '', $landingUrl),
            ]);
        }

        // Callouts
        foreach (($assets['callouts'] ?? []) as $callout) {
            $rows[] = self::csvRow($totalCols, [0 => 'Callout', 1 => $campaignName, 12 => $callout]);
        }

        // Structured Snippets
        foreach (($assets['structured_snippets'] ?? []) as $snippet) {
            $rows[] = self::csvRow($totalCols, [
                0 => 'Structured Snippet', 1 => $campaignName,
                13 => $snippet['header'] ?? '',
                14 => implode(';', $snippet['values'] ?? []),
            ]);
        }

        return $rows;
    }

    /**
     * Crea una riga CSV con N colonne vuote, riempiendo solo gli indici specificati
     */
    private static function csvRow(int $totalCols, array $data): array
    {
        $row = array_fill(0, $totalCols, '');
        foreach ($data as $index => $value) {
            $row[$index] = $value;
        }
        return $row;
    }

    /**
     * Risolve URL relativi in URL assoluti (per sitelink)
     */
    private static function resolveUrl(string $url, string $baseUrl): string
    {
        if (empty($url)) return $baseUrl;
        // Gia assoluto
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        // Relativo: prendi base domain
        if (empty($baseUrl)) return $url;
        $parsed = parse_url($baseUrl);
        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        // Path relativo con /
        if (str_starts_with($url, '/')) {
            return $base . $url;
        }
        return $base . '/' . $url;
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
