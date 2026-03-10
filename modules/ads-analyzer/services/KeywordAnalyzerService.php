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
        string $campaignStructure = '',
        string $currentAdGroupName = '',
        string $currentCampaignName = ''
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
            $prompt = $this->buildPrompt($businessContext, $termsText, $campaignStructure, $currentAdGroupName, $currentCampaignName);
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

            \Core\Database::reconnect();

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
    private function buildPrompt(string $businessContext, string $terms, string $campaignStructure = '', string $currentAdGroupName = '', string $currentCampaignName = ''): string
    {
        $structureBlock = '';
        if (!empty($campaignStructure)) {
            $structureBlock = "\n\nSTRUTTURA CAMPAGNE E AD GROUP:\n{$campaignStructure}\n";
        }

        $currentAdGroupBlock = '';
        if (!empty($currentAdGroupName)) {
            $currentAdGroupBlock = "\n\nAD GROUP IN ANALISI: \"{$currentAdGroupName}\"";
            if (!empty($currentCampaignName)) {
                $currentAdGroupBlock .= " (nella campagna \"{$currentCampaignName}\")";
            }
            $currentAdGroupBlock .= "\nTutti i termini sotto appartengono a questo ad group. " .
                "Quando suggerisci livello \"ad_group\", usa ESATTAMENTE \"{$currentAdGroupName}\" come target_name.\n";
        }

        return <<<PROMPT
Sei un esperto Google Ads. Analizza i termini di ricerca e identifica keyword negative da escludere.

CONTESTO BUSINESS:
{$businessContext}
{$structureBlock}{$currentAdGroupBlock}
TERMINI DI RICERCA (formato: termine | click | impressioni | costo | campagna > ad group):
{$terms}

ISTRUZIONI:
1. Analizza ATTENTAMENTE il contesto business per capire cosa vende/promuove il cliente
2. Identifica termini di ricerca NON PERTINENTI rispetto all'offerta
3. Raggruppa le keyword negative in categorie logiche per questo specifico business
4. Assegna priorita: "high" (escludi subito), "medium" (probabilmente da escludere), "evaluate" (valuta caso per caso)
5. Per ogni keyword, decidi il LIVELLO di applicazione (CRITICO — leggi con attenzione):
   - "ad_group" (DEFAULT — usa questo nel 80-90% dei casi): il termine non e pertinente per QUESTO specifico ad group "{$currentAdGroupName}", ma potrebbe essere rilevante per altri ad group nella stessa campagna o in altre campagne. Usa target_name = "{$currentAdGroupName}".
     Esempi: un competitor di un altro ad group, un prodotto/servizio trattato da un altro ad group, termini generici che non matchano questo specifico ad group.
   - "campaign" (RARO — max 10-20% dei casi): SOLO per termini completamente estranei a TUTTO il business dell'inserzionista (spam, settori completamente diversi, errori). Usa il nome della campagna come target_name.
     Esempi: "download gratis", "torrent", nomi di settori totalmente diversi.
   REGOLA D'ORO: nel dubbio, usa SEMPRE "ad_group". E meglio bloccare troppo poco a livello campagna che troppo.
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
          "target_name": "nome ESATTO della campagna (se level=campaign) o dell'ad group (se level=ad_group)"
        }
      ]
    }
  }
}

REGOLE PER LE CATEGORIE:
- LINGUA: usa la STESSA LINGUA dei termini di ricerca, delle copy degli annunci e delle landing page. Se i termini sono in italiano, i nomi delle categorie, le descrizioni e il summary DEVONO essere in italiano. Se sono in inglese, usa l'inglese. MAI mischiare lingue.
- Crea 5-12 categorie pertinenti al business
- Adatta i nomi delle categorie al contesto specifico
- Estrai SOLO keyword singole o frasi brevi (max 3 parole)
- Identifica pattern ricorrenti nei termini non pertinenti
- LIVELLO (FONDAMENTALE): l'80-90% delle keyword DEVE avere level="ad_group". Usa "campaign" SOLO per spam/settori totalmente estranei (max 10-20%). Se un termine menziona un competitor o un servizio diverso, usa "ad_group" perche potrebbe essere utile in un altro ad group
- TARGET_NAME: usa ESATTAMENTE i nomi delle campagne/ad group come indicati nella struttura sopra. Per ad_group usa "{$currentAdGroupName}"
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
