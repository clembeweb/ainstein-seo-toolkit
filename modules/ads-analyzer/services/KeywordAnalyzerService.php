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
        int $maxTerms = 300,
        string $campaignStructure = ''
    ): array {
        \Core\Logger::channel('ai')->debug('KeywordAnalyzerService::analyzeAdGroup START', [
            'user_id' => $userId,
            'business_context_length' => strlen($businessContext),
            'terms_count' => count($terms),
            'max_terms' => $maxTerms,
        ]);

        try {
            // Prepara termini per prompt (limita a maxTerms)
            $termsForPrompt = array_slice($terms, 0, $maxTerms);
            \Core\Logger::channel('ai')->debug('Terms for prompt', ['count' => count($termsForPrompt)]);

            $termsSummary = array_map(
                fn($t) => "{$t['term']} | {$t['clicks']} clic | {$t['impressions']} imp | €" . round((float)($t['cost'] ?? 0), 2) . " | " . ($t['campaign_name'] ?? '?') . " > " . ($t['ad_group_name'] ?? '?'),
                $termsForPrompt
            );
            $termsText = implode("\n", $termsSummary);
            \Core\Logger::channel('ai')->debug('Terms text built', ['length' => strlen($termsText)]);

            // Costruisci prompt
            $prompt = $this->buildPrompt($businessContext, $termsText, $campaignStructure);
            \Core\Logger::channel('ai')->debug('Prompt built', [
                'length' => strlen($prompt),
                'preview' => substr($prompt, 0, 300),
            ]);
            \Core\Logger::channel('ai')->debug('Calling AiService->analyze()');

            // Chiama AI
            $response = $this->aiService->analyze(
                $userId,
                $prompt,
                '', // Content vuoto, tutto nel prompt
                'ads-analyzer'
            );

            \Core\Logger::channel('ai')->debug('AiService response received', ['keys' => array_keys($response)]);

            if (isset($response['error'])) {
                \Core\Logger::channel('ai')->error('AI returned error', ['message' => $response['message'] ?? 'Unknown error']);
                throw new \Exception($response['message'] ?? 'Errore AI');
            }

            \Core\Logger::channel('ai')->debug('AI response result', [
                'length' => strlen($response['result'] ?? ''),
                'preview' => substr($response['result'] ?? '', 0, 500),
            ]);

            // Parse risposta JSON
            \Core\Logger::channel('ai')->debug('Parsing AI response');
            $result = $this->parseResponse($response['result']);

            \Core\Logger::channel('ai')->info('KeywordAnalyzerService::analyzeAdGroup SUCCESS', [
                'categories_count' => count($result['categories'] ?? []),
            ]);

            return $result;

        } catch (\Exception $e) {
            \Core\Logger::channel('ai')->error('KeywordAnalyzerService EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Costruisce prompt dinamico basato su contesto business
     */
    private function buildPrompt(string $businessContext, string $terms, string $campaignStructure = ''): string
    {
        $structureBlock = '';
        if (!empty($campaignStructure)) {
            $structureBlock = "\n\nSTRUTTURA CAMPAGNE E AD GROUP:\n{$campaignStructure}\n";
        }

        return <<<PROMPT
Sei un esperto Google Ads. Analizza i termini di ricerca e identifica keyword negative da escludere.

CONTESTO BUSINESS:
{$businessContext}
{$structureBlock}
TERMINI DI RICERCA (formato: termine | click | impressioni | costo | campagna > ad group):
{$terms}

ISTRUZIONI:
1. Analizza ATTENTAMENTE il contesto business per capire cosa vende/promuove il cliente
2. Identifica termini di ricerca NON PERTINENTI rispetto all'offerta
3. Raggruppa le keyword negative in categorie logiche per questo specifico business
4. Assegna priorita: "high" (escludi subito), "medium" (probabilmente da escludere), "evaluate" (valuta caso per caso)
5. Per ogni keyword, decidi il LIVELLO di applicazione:
   - "campaign" se il termine e irrilevante per TUTTE le campagne/ad group (es. termine completamente fuori target)
   - "ad_group" se il termine e irrilevante solo per ALCUNI ad group ma potrebbe essere valido per altri
6. Suggerisci il match_type piu appropriato:
   - "exact" per termini molto specifici che devono essere bloccati esattamente come sono
   - "phrase" per pattern che devono essere bloccati come frase (default)
   - "broad" per concetti ampi che devono essere bloccati in qualsiasi combinazione

Rispondi SOLO con un JSON valido (senza markdown, senza backtick, senza testo prima o dopo):
{
  "summary": "Breve riepilogo dell'analisi (1-2 frasi)",
  "stats": {
    "total_terms": numero_termini_analizzati,
    "zero_ctr_terms": numero_termini_con_ctr_zero,
    "wasted_impressions": impressioni_sprecate_stimate,
    "estimated_waste_cost": costo_stimato_spreco
  },
  "categories": {
    "NOME_CATEGORIA_1": {
      "priority": "high|medium|evaluate",
      "description": "Breve descrizione della categoria",
      "keywords": [
        {
          "text": "keyword1",
          "match_type": "exact|phrase|broad",
          "level": "campaign|ad_group",
          "target_name": "nome campagna o ad group dove applicare (se ad_group)"
        }
      ]
    }
  }
}

REGOLE PER LE CATEGORIE:
- Crea 5-12 categorie pertinenti al business
- Adatta i nomi delle categorie al contesto specifico
- Estrai SOLO keyword singole o frasi brevi (max 3 parole)
- Identifica pattern ricorrenti nei termini non pertinenti
- IMPORTANTE: per il campo "level", considera che una keyword negativa a livello campagna blocca il termine su TUTTI gli ad group. Usa "ad_group" quando il termine potrebbe essere valido per un altro ad group della stessa campagna.
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
