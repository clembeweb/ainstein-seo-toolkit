<?php

namespace Modules\AdsAnalyzer\Services;

require_once __DIR__ . '/../../../services/AiService.php';

class KeywordAnalyzerService
{
    private \Services\AiService $aiService;

    public function __construct()
    {
        // OBBLIGATORIO: passa module_slug per logging automatico
        $this->aiService = new \Services\AiService('ads-analyzer');
    }

    /**
     * Analizza termini di un Ad Group
     */
    public function analyzeAdGroup(
        int $userId,
        string $businessContext,
        array $terms,
        int $maxTerms = 300
    ): array {
        // Prepara termini per prompt (limita a maxTerms)
        $termsForPrompt = array_slice($terms, 0, $maxTerms);
        $termsSummary = array_map(
            fn($t) => "{$t['term']} | {$t['clicks']} clic | {$t['impressions']} imp",
            $termsForPrompt
        );
        $termsText = implode("\n", $termsSummary);

        // Costruisci prompt
        $prompt = $this->buildPrompt($businessContext, $termsText);

        // Chiama AI
        $response = $this->aiService->analyze(
            $userId,
            $prompt,
            '', // Content vuoto, tutto nel prompt
            'ads-analyzer'
        );

        if (isset($response['error'])) {
            throw new \Exception($response['message'] ?? 'Errore AI');
        }

        // Parse risposta JSON
        return $this->parseResponse($response['result']);
    }

    /**
     * Costruisce prompt dinamico basato su contesto business
     */
    private function buildPrompt(string $businessContext, string $terms): string
    {
        return <<<PROMPT
Sei un esperto Google Ads. Analizza i termini di ricerca e identifica keyword negative da escludere per questa campagna.

CONTESTO BUSINESS:
{$businessContext}

TERMINI DI RICERCA (formato: termine | click | impressioni):
{$terms}

ISTRUZIONI:
1. Analizza ATTENTAMENTE il contesto business per capire cosa vende/promuove il cliente
2. Identifica termini di ricerca NON PERTINENTI rispetto all'offerta
3. Raggruppa le keyword negative in categorie logiche per questo specifico business
4. Assegna priorita: "high" (escludi subito), "medium" (probabilmente da escludere), "evaluate" (valuta caso per caso)

Rispondi SOLO con un JSON valido (senza markdown, senza backtick, senza testo prima o dopo) con questa struttura:
{
  "stats": {
    "total_terms": numero_termini_analizzati,
    "zero_ctr_terms": numero_termini_con_ctr_zero,
    "wasted_impressions": impressioni_sprecate_stimate
  },
  "categories": {
    "NOME_CATEGORIA_1": {
      "priority": "high|medium|evaluate",
      "description": "Breve descrizione della categoria",
      "keywords": ["keyword1", "keyword2", "keyword3"]
    },
    "NOME_CATEGORIA_2": { ... }
  }
}

REGOLE PER LE CATEGORIE:
- Crea 5-12 categorie pertinenti al business (non usare categorie predefinite)
- Esempi categorie comuni: CONCORRENTI, PRODOTTI_NON_OFFERTI, INTENTO_INFORMATIVO, NAVIGAZIONALI, BRAND_ALTRI, LOCALITA, LINGUE_STRANIERE
- Adatta i nomi delle categorie al contesto specifico
- Estrai SOLO keyword singole o frasi brevi (max 3 parole), non i termini interi
- Identifica pattern ricorrenti nei termini non pertinenti
PROMPT;
    }

    /**
     * Parse risposta AI in JSON
     */
    private function parseResponse(string $text): array
    {
        // Rimuovi eventuali markdown
        $jsonStr = preg_replace('/```json\s*/i', '', $text);
        $jsonStr = preg_replace('/```\s*/', '', $jsonStr);

        // Estrai JSON
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
