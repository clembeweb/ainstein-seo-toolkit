<?php

namespace Modules\AdsAnalyzer\Services;

use Core\Database;

require_once __DIR__ . '/../../../services/AiService.php';

class CampaignEvaluatorService
{
    private \Services\AiService $aiService;

    public function __construct()
    {
        $this->aiService = new \Services\AiService('ads-analyzer');
    }

    /**
     * Valuta le campagne/annunci con AI
     *
     * @param int $userId
     * @param array $campaigns Dati campagne da ga_campaigns
     * @param array $ads Dati annunci da ga_ads
     * @param array $extensions Dati estensioni da ga_extensions
     * @param array $landingContexts Contesti landing page estratti [url => context]
     * @return array Risultato analisi AI strutturato
     */
    public function evaluate(
        int $userId,
        array $campaigns,
        array $ads,
        array $extensions,
        array $landingContexts = []
    ): array {
        $prompt = $this->buildPrompt($campaigns, $ads, $extensions, $landingContexts);

        $response = $this->aiService->analyze(
            $userId,
            $prompt,
            '',
            'ads-analyzer'
        );

        Database::reconnect();

        if (isset($response['error'])) {
            throw new \Exception($response['message'] ?? 'Errore AI');
        }

        return $this->parseResponse($response['result']);
    }

    private function buildPrompt(array $campaigns, array $ads, array $extensions, array $landingContexts): string
    {
        // Formatta campagne
        $campaignLines = [];
        foreach ($campaigns as $c) {
            $campaignLines[] = sprintf(
                "- %s | Tipo: %s | Bidding: %s | Budget: €%.2f/%s | Click: %d | Imp: %d | CTR: %.2f%% | CPC: €%.2f | Costo: €%.2f | Conv: %.1f | Valore: €%.2f | Conv Rate: %.2f%%",
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

        // Formatta annunci (raggruppa per campagna)
        $adsByCampaign = [];
        foreach ($ads as $ad) {
            $key = $ad['campaign_name'] ?? 'Sconosciuta';
            $adsByCampaign[$key][] = $ad;
        }

        $adLines = [];
        foreach ($adsByCampaign as $campaignName => $campaignAds) {
            $adLines[] = "\n  Campagna: {$campaignName}";
            foreach (array_slice($campaignAds, 0, 10) as $ad) {
                $headlines = array_filter([$ad['headline1'], $ad['headline2'], $ad['headline3']]);
                $descriptions = array_filter([$ad['description1'], $ad['description2']]);
                $adLines[] = sprintf(
                    "    - [%s] %s | Titoli: %s | Desc: %s | URL: %s | QS: %s | Click: %d | CTR: %.2f%% | CPC: €%.2f",
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

        // Formatta estensioni
        $extLines = [];
        foreach ($extensions as $ext) {
            $extLines[] = sprintf("- [%s] %s | Click: %d | Imp: %d",
                $ext['extension_type'],
                $ext['extension_text'] ?? '',
                $ext['clicks'],
                $ext['impressions']
            );
        }

        // Formatta contesti landing
        $landingLines = [];
        foreach ($landingContexts as $url => $context) {
            $landingLines[] = "- {$url}:\n  {$context}";
        }

        $campaignsText = implode("\n", $campaignLines);
        $adsText = implode("\n", $adLines);
        $extText = !empty($extLines) ? implode("\n", $extLines) : 'Nessuna estensione trovata';
        $landingText = !empty($landingLines) ? implode("\n", $landingLines) : 'Non disponibili';

        return <<<PROMPT
Sei un esperto Google Ads certificato. Analizza in dettaglio le campagne, gli annunci, le estensioni e le landing page fornite.

DATI CAMPAGNE:
{$campaignsText}

ANNUNCI PER CAMPAGNA:
{$adsText}

ESTENSIONI:
{$extText}

CONTESTO LANDING PAGES:
{$landingText}

ISTRUZIONI:
Valuta ogni aspetto dell'account Google Ads e fornisci raccomandazioni concrete e azionabili.

Aree da valutare:
1. COPY ANNUNCI: qualità titoli/descrizioni, uso keyword, best practice RSA, diversificazione messaggi
2. LANDING PAGE: coerenza annuncio-landing, pertinenza, UX percepita dal contenuto
3. PERFORMANCE: CTR rispetto a benchmark di settore, CPC, conversion rate, quality score
4. BUDGET E BIDDING: strategia di offerta appropriata, allocazione budget tra campagne
5. ESTENSIONI: copertura (sitelink, callout, snippet), qualità, pertinenza

Rispondi SOLO con un JSON valido (senza markdown, senza backtick) con questa struttura:
{
  "overall_score": 7.5,
  "summary": "Valutazione complessiva in 2-3 frasi",
  "campaigns": [
    {
      "campaign_name": "Nome Campagna",
      "score": 8,
      "strengths": ["Punto di forza 1", "Punto di forza 2"],
      "issues": [
        {
          "severity": "high",
          "area": "copy",
          "description": "Descrizione del problema",
          "recommendation": "Azione consigliata specifica"
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
  }
}

REGOLE:
- Punteggi da 1 a 10
- severity: "high" (critico), "medium" (importante), "low" (suggerimento)
- area: "copy", "landing", "performance", "budget", "extensions"
- Sii specifico e azionabile nelle raccomandazioni
- Considera benchmark di settore per CTR (Search: 3-5%), CPC, conversion rate
- Valuta il Quality Score medio e suggerisci miglioramenti
PROMPT;
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
