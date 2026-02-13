<?php

namespace Modules\AdsAnalyzer\Services;

use Core\Database;

require_once __DIR__ . '/../../../services/AiService.php';

class CampaignEvaluatorService
{
    private \Services\AiService $aiService;

    /** Benchmark per tipo campagna */
    private const BENCHMARKS = [
        'SEARCH' => [
            'label' => 'Search',
            'ctr_good' => 3.0,
            'ctr_excellent' => 5.0,
            'qs_good' => 6,
            'qs_excellent' => 8,
            'conv_rate_good' => 2.0,
            'conv_rate_excellent' => 5.0,
            'focus' => 'Pertinenza keyword-annuncio, quality score, intento di ricerca',
            'notes' => 'CTR < 2% critico. QS < 5 spreco budget. Match type strategy fondamentale.',
        ],
        'SHOPPING' => [
            'label' => 'Shopping',
            'ctr_good' => 1.0,
            'ctr_excellent' => 3.0,
            'conv_rate_good' => 1.5,
            'conv_rate_excellent' => 3.0,
            'focus' => 'ROAS, qualita feed prodotti, titoli prodotto, competitivita prezzi',
            'notes' => 'No keyword tradizionali. Valutare titoli prodotto e landing page. ROAS > 400% buono.',
        ],
        'PERFORMANCE_MAX' => [
            'label' => 'Performance Max',
            'ctr_good' => 1.5,
            'ctr_excellent' => 3.0,
            'conv_rate_good' => 2.0,
            'conv_rate_excellent' => 4.0,
            'focus' => 'Qualita asset group, segnali audience, multi-rete',
            'notes' => 'Metriche aggregate da Search+Display+YouTube+Discover+Gmail+Maps. CTR aggregato non confrontabile con Search puro. Valutare conversioni e CPA.',
        ],
        'DISPLAY' => [
            'label' => 'Display',
            'ctr_good' => 0.5,
            'ctr_excellent' => 1.0,
            'conv_rate_good' => 0.5,
            'conv_rate_excellent' => 1.5,
            'focus' => 'Viewability, targeting audience, qualita banner, placement',
            'notes' => 'CTR basso e fisiologico. Valutare awareness e impression share. Conversion assist importante.',
        ],
        'VIDEO' => [
            'label' => 'Video',
            'ctr_good' => 0.5,
            'ctr_excellent' => 1.5,
            'conv_rate_good' => 0.3,
            'conv_rate_excellent' => 1.0,
            'focus' => 'View rate (15-30% buono), CPV, retention audience, CTA effectiveness',
            'notes' => 'Metriche di engagement piu rilevanti di CTR. View-through conversions importanti.',
        ],
    ];

    /** Limiti per contenere il prompt */
    private const MAX_ADS_PER_AD_GROUP = 5;
    private const MAX_KEYWORDS_PER_AD_GROUP = 20;
    private const MAX_AD_GROUPS_TOTAL = 30;

    public function __construct()
    {
        $this->aiService = new \Services\AiService('ads-analyzer');
    }

    /**
     * Valuta le campagne con AI - analisi a 3 livelli (Account > Campagna > Ad Group)
     *
     * @param array|null $campaignFilter Array di campaign_id_google da includere (null = tutte)
     */
    public function evaluate(
        int $userId,
        array $campaigns,
        array $ads,
        array $extensions,
        array $landingContexts = [],
        array $adGroups = [],
        array $keywords = [],
        ?array $campaignFilter = null
    ): array {
        // Filtra per campagne selezionate
        if ($campaignFilter !== null && !empty($campaignFilter)) {
            $campaigns = array_values(array_filter($campaigns, fn($c) => in_array($c['campaign_id_google'], $campaignFilter)));
            $ads = array_values(array_filter($ads, fn($a) => in_array($a['campaign_id_google'], $campaignFilter)));
            $adGroups = array_values(array_filter($adGroups, fn($ag) => in_array($ag['campaign_id_google'], $campaignFilter)));
            $keywords = array_values(array_filter($keywords, fn($kw) => in_array($kw['campaign_id_google'], $campaignFilter)));
        }

        $prompt = $this->buildPrompt($campaigns, $ads, $extensions, $landingContexts, $adGroups, $keywords);

        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->aiService->complete(
            $userId,
            $messages,
            ['max_tokens' => 8192],
            'ads-analyzer'
        );

        Database::reconnect();

        if (isset($response['error'])) {
            throw new \Exception($response['message'] ?? 'Errore AI');
        }

        return $this->parseResponse($response['result']);
    }

    /**
     * Valuta con contesto storico (per auto-eval e confronto trend)
     */
    public function evaluateWithContext(
        int $userId,
        array $campaigns,
        array $ads,
        array $extensions,
        array $landingContexts,
        array $adGroups,
        array $keywords,
        ?array $previousEvalSummary = null,
        ?array $metricDeltas = null,
        ?array $alerts = null,
        ?array $campaignFilter = null
    ): array {
        // Filtra per campagne selezionate
        if ($campaignFilter !== null && !empty($campaignFilter)) {
            $campaigns = array_values(array_filter($campaigns, fn($c) => in_array($c['campaign_id_google'], $campaignFilter)));
            $ads = array_values(array_filter($ads, fn($a) => in_array($a['campaign_id_google'], $campaignFilter)));
            $adGroups = array_values(array_filter($adGroups, fn($ag) => in_array($ag['campaign_id_google'], $campaignFilter)));
            $keywords = array_values(array_filter($keywords, fn($kw) => in_array($kw['campaign_id_google'], $campaignFilter)));
        }

        $prompt = $this->buildPrompt($campaigns, $ads, $extensions, $landingContexts, $adGroups, $keywords);

        // Aggiungi contesto storico
        if ($previousEvalSummary || $metricDeltas) {
            $prompt .= "\n\n" . $this->buildHistoricalContext($previousEvalSummary, $metricDeltas, $alerts);
        }

        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->aiService->complete(
            $userId,
            $messages,
            ['max_tokens' => 8192],
            'ads-analyzer'
        );

        Database::reconnect();

        if (isset($response['error'])) {
            throw new \Exception($response['message'] ?? 'Errore AI');
        }

        return $this->parseResponse($response['result']);
    }

    /**
     * Costruisce la sezione di contesto storico per il prompt
     */
    private function buildHistoricalContext(?array $previousEval, ?array $deltas, ?array $alerts): string
    {
        $lines = [];

        if ($previousEval) {
            $lines[] = "CONTESTO STORICO (valutazione precedente):";
            $lines[] = "- Punteggio precedente: " . ($previousEval['score'] ?? 'N/D') . "/10";
            if (!empty($previousEval['summary'])) {
                $lines[] = "- Summary precedente: " . $previousEval['summary'];
            }
            if (!empty($previousEval['top_recommendations'])) {
                $lines[] = "- Raccomandazioni precedenti:";
                foreach (array_slice($previousEval['top_recommendations'], 0, 5) as $rec) {
                    $lines[] = "  * " . $rec;
                }
            }
            $lines[] = "";
        }

        if ($deltas) {
            $lines[] = "VARIAZIONI METRICHE RISPETTO AL PERIODO PRECEDENTE:";
            $lines[] = \Modules\AdsAnalyzer\Services\MetricComparisonService::buildDeltaSummary($deltas, $alerts ?? []);
            $lines[] = "";
        }

        $lines[] = "ISTRUZIONI SPECIALI PER CONFRONTO STORICO:";
        $lines[] = "- Confronta la situazione attuale con quella precedente";
        $lines[] = "- Evidenzia cosa e MIGLIORATO e cosa e PEGGIORATO";
        $lines[] = "- NON ripetere raccomandazioni gia date se la situazione non e cambiata";
        $lines[] = "- Concentrati sui CAMBIAMENTI e sulle NUOVE criticita";
        $lines[] = "- Se le metriche sono stabili o migliorate, riconosci il progresso";
        $lines[] = "- Nel JSON includi \"trend\": \"improving\" | \"stable\" | \"declining\" | \"mixed\"";
        $lines[] = "- Nel JSON includi \"changes_summary\": breve descrizione dei cambiamenti principali";

        return implode("\n", $lines);
    }

    private function buildPrompt(
        array $campaigns,
        array $ads,
        array $extensions,
        array $landingContexts,
        array $adGroups,
        array $keywords
    ): string {
        // Identifica tipi campagna presenti
        $campaignTypes = [];
        foreach ($campaigns as $c) {
            $type = strtoupper($c['campaign_type'] ?? 'SEARCH');
            $campaignTypes[$type] = true;
        }

        // Sezione 1: Benchmark (solo tipi presenti)
        $benchmarkText = $this->buildBenchmarkSection($campaignTypes);

        // Sezione 2: Campagne
        $campaignsText = $this->buildCampaignsSection($campaigns);

        // Sezione 3: Dettaglio per ad group
        $adGroupsText = $this->buildAdGroupDetailSection($campaigns, $adGroups, $ads, $keywords);

        // Sezione 4: Estensioni
        $extText = $this->buildExtensionsSection($extensions);

        // Sezione 5: Landing pages (con mapping URL→ad groups)
        $landingText = $this->buildLandingSection($landingContexts, $ads);

        // Sezione 6: Struttura risposta
        $hasAdGroups = !empty($adGroups);

        return <<<PROMPT
Sei un esperto Google Ads certificato con esperienza in tutti i tipi di campagna (Search, Shopping, Performance Max, Display, Video).

{$benchmarkText}

DATI CAMPAGNE:
{$campaignsText}

{$adGroupsText}

ESTENSIONI:
{$extText}

CONTESTO LANDING PAGES:
{$landingText}

ISTRUZIONI DI VALUTAZIONE:
Valuta a 3 livelli: Account > Campagna > Gruppo Annunci.

PER OGNI CAMPAGNA valuta in base al TIPO SPECIFICO:
- Confronta metriche con i benchmark del tipo (NON usare benchmark Search per Shopping!)
- Identifica opportunita specifiche per quel tipo di campagna
- Valuta strategia bidding e allocazione budget

PER OGNI GRUPPO ANNUNCI valuta:
1. COERENZA KEYWORD: le keyword sono tematicamente coerenti? troppo generiche/specifiche?
2. PERTINENZA ANNUNCI-KEYWORD: titoli/descrizioni riflettono le keyword del gruppo?
3. COERENZA LANDING: la landing page e coerente con le keyword e gli annunci?
   - Il contenuto della landing riflette cio che promettono gli annunci?
   - La proposta di valore e chiara e convincente?
   - La landing parla allo stesso target delle keyword?
   - Ci sono discrepanze tra keyword -> annuncio -> landing?
4. QUALITY SCORE: distribuzione (% con QS >= 7), suggerimenti per miglioramento
5. PERFORMANCE vs BENCHMARK: metriche vs benchmark specifici del tipo campagna
6. MATCH TYPE: uso appropriato di broad/phrase/exact

ANALISI LANDING PAGES ("landing_evaluation"):
Se sono disponibili contesti landing pages, valuta complessivamente:
- Punteggio generale (1-10)
- Problemi riscontrati per ogni URL con raccomandazione

SUGGERIMENTI CAMPAGNE ("campaign_suggestions"):
Genera 3-6 suggerimenti concreti e azionabili per migliorare le campagne:
- Struttura campagne (separazione brand/non-brand, segmentazione)
- Strategia keyword (match type, negative, long-tail)
- Copy annunci (headline, description, CTA, estensioni)
- Bidding e budget (distribuzione, strategia offerte)
- Targeting e audience (segmenti, esclusioni)
Ogni suggerimento: area, priority (high/medium/low), suggestion (testo specifico), expected_impact (impatto stimato).

SUGGERIMENTI LANDING PAGES ("landing_suggestions"):
Se sono disponibili contesti landing pages, genera 2-5 suggerimenti per migliorarle:
- Struttura pagina (above the fold, gerarchia contenuti)
- Copy e messaggi (coerenza con annunci, USP, urgency)
- CTA (posizionamento, visibilita, testo)
- Form (numero campi, friction, trust elements)
- Social proof (recensioni, certificazioni, case studies)
- Mobile experience (velocita, usabilita)
- Coerenza annuncio->landing (message match)
Ogni suggerimento: url (pagina specifica), priority (high/medium/low), suggestion (testo specifico), expected_impact (impatto stimato).

Rispondi SOLO con un JSON valido (senza markdown, senza backtick) con questa struttura:
{
  "overall_score": 7.5,
  "summary": "Valutazione complessiva in 2-3 frasi",
  "campaigns": [
    {
      "campaign_name": "Nome Campagna",
      "campaign_type": "SEARCH",
      "score": 8,
      "type_specific_insights": "Insight specifico per il tipo di campagna e i suoi benchmark",
      "strengths": ["Punto di forza 1"],
      "issues": [
        {
          "severity": "high",
          "area": "copy",
          "description": "Descrizione del problema",
          "recommendation": "Azione consigliata specifica"
        }
      ],
      "ad_groups": [
        {
          "ad_group_name": "Nome Gruppo",
          "score": 7,
          "keyword_coherence": 8,
          "ad_relevance": 6,
          "landing_coherence": 7,
          "landing_analysis": "Breve analisi della coerenza landing-annuncio per questo gruppo",
          "quality_score_avg": 6.5,
          "strengths": ["Punto di forza"],
          "issues": [
            {
              "severity": "medium",
              "area": "keywords",
              "description": "Problema specifico",
              "recommendation": "Suggerimento"
            }
          ]
        }
      ]
    }
  ],
  "top_recommendations": [
    "Raccomandazione prioritaria 1",
    "Raccomandazione prioritaria 2",
    "Raccomandazione prioritaria 3"
  ],
  "extensions_evaluation": {
    "score": 6,
    "missing": ["Tipo estensione mancante"],
    "suggestions": ["Suggerimento per estensioni"]
  },
  "landing_evaluation": {
    "overall_score": 7,
    "pages_analyzed": 5,
    "issues": [
      {
        "url": "https://example.com/pagina",
        "issue": "Problema riscontrato",
        "recommendation": "Azione consigliata"
      }
    ]
  },
  "campaign_suggestions": [
    {
      "area": "Struttura Campagne",
      "priority": "high",
      "suggestion": "Suggerimento concreto e azionabile",
      "expected_impact": "Impatto stimato quantificato"
    }
  ],
  "landing_suggestions": [
    {
      "url": "https://example.com/pagina",
      "priority": "high",
      "suggestion": "Suggerimento specifico per questa landing",
      "expected_impact": "Impatto stimato"
    }
  ]
}

REGOLE:
- Punteggi da 1 a 10
- severity: "high" (critico), "medium" (importante), "low" (suggerimento)
- area per campagne: "copy", "landing", "performance", "budget", "extensions"
- area per ad groups: "keywords", "copy", "landing", "performance", "match_type"
- Sii specifico e azionabile nelle raccomandazioni e nei suggerimenti
- Usa i BENCHMARK PER TIPO forniti sopra per valutare le metriche
- Se non ci sono dati ad group, ometti il campo "ad_groups" dalle campagne
- Se non ci sono dati landing, ometti "landing_evaluation" e "landing_suggestions"
- campaign_suggestions e sempre obbligatorio (anche senza dati landing)
PROMPT;
    }

    private function buildBenchmarkSection(array $campaignTypes): string
    {
        $lines = ["BENCHMARK PER TIPO CAMPAGNA (usa questi per valutare le metriche):"];

        foreach ($campaignTypes as $type => $flag) {
            $bench = self::BENCHMARKS[$type] ?? self::BENCHMARKS['SEARCH'];
            $lines[] = sprintf(
                "- %s: CTR buono %.1f-%.1f%%, Conv Rate %.1f-%.1f%%, Focus: %s. %s",
                $bench['label'],
                $bench['ctr_good'],
                $bench['ctr_excellent'],
                $bench['conv_rate_good'],
                $bench['conv_rate_excellent'],
                $bench['focus'],
                $bench['notes']
            );
        }

        return implode("\n", $lines);
    }

    private function buildCampaignsSection(array $campaigns): string
    {
        $lines = [];
        foreach ($campaigns as $c) {
            $lines[] = sprintf(
                "- %s | Tipo: %s | Bidding: %s | Budget: %.2f/%s | Click: %d | Imp: %d | CTR: %.2f%% | CPC: %.2f | Costo: %.2f | Conv: %.1f | Valore: %.2f | Conv Rate: %.2f%%",
                $c['campaign_name'],
                $c['campaign_type'] ?? 'N/D',
                $c['bidding_strategy'] ?? 'N/D',
                $c['budget_amount'] ?? 0,
                $c['budget_type'] ?? 'DAILY',
                $c['clicks'],
                $c['impressions'],
                ($c['ctr'] ?? 0) * 100,
                $c['avg_cpc'] ?? 0,
                $c['cost'],
                $c['conversions'],
                $c['conversion_value'] ?? 0,
                ($c['conv_rate'] ?? 0) * 100
            );
        }
        return implode("\n", $lines);
    }

    private function buildAdGroupDetailSection(
        array $campaigns,
        array $adGroups,
        array $ads,
        array $keywords
    ): string {
        if (empty($adGroups)) {
            // Fallback: raggruppa annunci per campagna (backward compatible)
            return $this->buildLegacyAdSection($ads);
        }

        // Indicizza ads e keywords per ad_group_id_google
        $adsByGroup = [];
        foreach ($ads as $ad) {
            $adsByGroup[$ad['ad_group_id_google']][] = $ad;
        }

        $kwByGroup = [];
        foreach ($keywords as $kw) {
            $kwByGroup[$kw['ad_group_id_google']][] = $kw;
        }

        // Raggruppa ad groups per campagna
        $agByCampaign = [];
        foreach ($adGroups as $ag) {
            $agByCampaign[$ag['campaign_id_google']][] = $ag;
        }

        // Mappa campaign_id => campaign data
        $campaignMap = [];
        foreach ($campaigns as $c) {
            $campaignMap[$c['campaign_id_google']] = $c;
        }

        // Limita ad groups totali
        $totalAdGroups = 0;
        $lines = ["DETTAGLIO PER GRUPPO ANNUNCI:"];

        foreach ($agByCampaign as $campaignId => $groups) {
            $camp = $campaignMap[$campaignId] ?? null;
            $campName = $camp['campaign_name'] ?? 'Sconosciuta';
            $campType = $camp['campaign_type'] ?? 'SEARCH';

            $lines[] = "\nCampagna: \"{$campName}\" (Tipo: {$campType})";

            // Ordina ad groups per cost DESC
            usort($groups, fn($a, $b) => ($b['cost'] ?? 0) <=> ($a['cost'] ?? 0));

            foreach ($groups as $ag) {
                if ($totalAdGroups >= self::MAX_AD_GROUPS_TOTAL) {
                    $lines[] = "  ... (altri gruppi omessi per limiti di analisi)";
                    break 2;
                }

                $agId = $ag['ad_group_id_google'];
                $lines[] = sprintf(
                    "\n  Gruppo: \"%s\" [%s]",
                    $ag['ad_group_name'],
                    $ag['ad_group_status'] ?? 'N/D'
                );
                $lines[] = sprintf(
                    "  Metriche: Click: %d | Imp: %d | CTR: %.2f%% | CPC: %.2f | Costo: %.2f | Conv: %.1f | Conv Rate: %.2f%%",
                    $ag['clicks'],
                    $ag['impressions'],
                    ($ag['ctr'] ?? 0) * 100,
                    $ag['avg_cpc'] ?? 0,
                    $ag['cost'] ?? 0,
                    $ag['conversions'] ?? 0,
                    ($ag['conv_rate'] ?? 0) * 100
                );

                // Annunci di questo ad group (max N)
                $groupAds = $adsByGroup[$agId] ?? [];
                usort($groupAds, fn($a, $b) => ($b['cost'] ?? 0) <=> ($a['cost'] ?? 0));
                $groupAds = array_slice($groupAds, 0, self::MAX_ADS_PER_AD_GROUP);

                if (!empty($groupAds)) {
                    $lines[] = "  Annunci:";
                    foreach ($groupAds as $ad) {
                        $headlines = array_filter([$ad['headline1'], $ad['headline2'], $ad['headline3']]);
                        $descriptions = array_filter([$ad['description1'], $ad['description2']]);
                        $lines[] = sprintf(
                            "    - [%s] Titoli: %s | Desc: %s | URL: %s | Click: %d | CTR: %.2f%%",
                            $ad['ad_type'] ?? 'N/D',
                            implode(' | ', $headlines) ?: 'N/D',
                            implode(' | ', $descriptions) ?: 'N/D',
                            $ad['final_url'] ?? 'N/D',
                            $ad['clicks'],
                            ($ad['ctr'] ?? 0) * 100
                        );
                    }
                }

                // Keyword di questo ad group (max N)
                $groupKw = $kwByGroup[$agId] ?? [];
                usort($groupKw, fn($a, $b) => ($b['cost'] ?? 0) <=> ($a['cost'] ?? 0));
                $groupKw = array_slice($groupKw, 0, self::MAX_KEYWORDS_PER_AD_GROUP);

                if (!empty($groupKw)) {
                    $lines[] = "  Keyword:";
                    foreach ($groupKw as $kw) {
                        $lines[] = sprintf(
                            "    - [%s] \"%s\" | Click: %d | Imp: %d | CTR: %.2f%% | QS: %s | CPC: %.2f",
                            $kw['match_type'] ?? 'N/D',
                            $kw['keyword_text'],
                            $kw['clicks'],
                            $kw['impressions'],
                            ($kw['ctr'] ?? 0) * 100,
                            $kw['quality_score'] ?? 'N/D',
                            $kw['avg_cpc'] ?? 0
                        );
                    }
                }

                $totalAdGroups++;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Fallback per valutazioni senza dati ad group (backward compatible)
     */
    private function buildLegacyAdSection(array $ads): string
    {
        if (empty($ads)) {
            return "ANNUNCI: Nessun annuncio disponibile";
        }

        $adsByCampaign = [];
        foreach ($ads as $ad) {
            $key = $ad['campaign_name'] ?? 'Sconosciuta';
            $adsByCampaign[$key][] = $ad;
        }

        $lines = ["ANNUNCI PER CAMPAGNA:"];
        foreach ($adsByCampaign as $campaignName => $campaignAds) {
            $lines[] = "\n  Campagna: {$campaignName}";
            foreach (array_slice($campaignAds, 0, 10) as $ad) {
                $headlines = array_filter([$ad['headline1'], $ad['headline2'], $ad['headline3']]);
                $descriptions = array_filter([$ad['description1'], $ad['description2']]);
                $lines[] = sprintf(
                    "    - [%s] %s | Titoli: %s | Desc: %s | URL: %s | QS: %s | Click: %d | CTR: %.2f%% | CPC: %.2f",
                    $ad['ad_type'] ?? 'N/D',
                    $ad['ad_group_name'] ?? 'N/D',
                    implode(' | ', $headlines),
                    implode(' | ', $descriptions),
                    $ad['final_url'] ?? 'N/D',
                    $ad['quality_score'] ?? 'N/D',
                    $ad['clicks'],
                    ($ad['ctr'] ?? 0) * 100,
                    $ad['avg_cpc'] ?? 0
                );
            }
        }

        return implode("\n", $lines);
    }

    private function buildExtensionsSection(array $extensions): string
    {
        if (empty($extensions)) {
            return 'Nessuna estensione trovata';
        }

        $lines = [];
        foreach ($extensions as $ext) {
            $lines[] = sprintf(
                "- [%s] %s | Click: %d | Imp: %d",
                $ext['extension_type'],
                $ext['extension_text'] ?? '',
                $ext['clicks'],
                $ext['impressions']
            );
        }

        return implode("\n", $lines);
    }

    private function buildLandingSection(array $landingContexts, array $ads = []): string
    {
        if (empty($landingContexts)) {
            return 'Non disponibili (landing pages non accessibili o non presenti)';
        }

        // Mappa URL → ad groups che la usano
        $urlToAdGroups = [];
        foreach ($ads as $ad) {
            $url = $ad['final_url'] ?? '';
            if (!empty($url) && isset($landingContexts[$url])) {
                $key = $ad['ad_group_name'] ?? 'N/D';
                $urlToAdGroups[$url][$key] = true;
            }
        }

        $lines = [];
        foreach ($landingContexts as $url => $context) {
            $adGroupNames = array_keys($urlToAdGroups[$url] ?? []);
            $lines[] = "URL: {$url}";
            if (!empty($adGroupNames)) {
                $lines[] = "Usata da gruppi: " . implode(', ', array_slice($adGroupNames, 0, 10));
            }
            $lines[] = "Contesto: {$context}";
            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    private function parseResponse(string $text): array
    {
        // Rimuovi markdown
        $jsonStr = preg_replace('/```json\s*/i', '', $text);
        $jsonStr = preg_replace('/```\s*/', '', $jsonStr);

        $firstBrace = strpos($jsonStr, '{');
        $lastBrace = strrpos($jsonStr, '}');

        if ($firstBrace === false || $lastBrace === false) {
            throw new \Exception('Nessun JSON trovato nella risposta AI');
        }

        $jsonStr = substr($jsonStr, $firstBrace, $lastBrace - $firstBrace + 1);

        $result = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON non valido: ' . json_last_error_msg());
        }

        return $result;
    }
}
