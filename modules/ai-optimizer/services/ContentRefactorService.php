<?php

namespace Modules\AiOptimizer\Services;

use Core\Database;
use Core\Credits;

require_once __DIR__ . '/../../../services/AiService.php';

/**
 * ContentRefactorService
 *
 * Riscrive e ottimizza un articolo basandosi sull'analisi gap.
 *
 * Workflow:
 * 1. Riceve articolo originale + analisi gap
 * 2. Costruisce prompt per riscrittura
 * 3. AI genera versione ottimizzata
 * 4. Restituisce nuovo contenuto strutturato
 */
class ContentRefactorService
{
    private \Services\AiService $aiService;

    public function __construct()
    {
        $this->aiService = new \Services\AiService('ai-optimizer');
    }

    /**
     * Verifica se il servizio è configurato
     */
    public function isConfigured(): bool
    {
        return $this->aiService->isConfigured();
    }

    /**
     * Riscrivi articolo basandosi sull'analisi gap
     *
     * @param int $userId ID utente
     * @param array $originalArticle Dati articolo originale (title, content, h1, headings, word_count)
     * @param array $analysisData Risultati gap analysis
     * @param string $keyword Keyword target
     * @param array $options target_word_count, tone, keep_original_structure
     * @return array
     */
    public function refactor(
        int $userId,
        array $originalArticle,
        array $analysisData,
        string $keyword,
        array $options = []
    ): array {
        // Verifica crediti
        $creditCost = Credits::getCost('content_refactor', 'ai-optimizer');
        if (!Credits::hasEnough($userId, $creditCost)) {
            return [
                'error' => true,
                'message' => 'Crediti insufficienti. Richiesti: ' . $creditCost,
                'credits_required' => $creditCost,
            ];
        }

        $targetWordCount = $options['target_word_count'] ?? ($analysisData['content']['recommended_word_count'] ?? 1500);
        $tone = $options['tone'] ?? 'professionale';
        $keepStructure = $options['keep_original_structure'] ?? false;

        try {
            // Costruisci prompt
            $prompt = $this->buildRefactorPrompt(
                $originalArticle,
                $analysisData,
                $keyword,
                $targetWordCount,
                $tone,
                $keepStructure
            );

            // Chiama AI con max_tokens alto per articoli lunghi
            $response = $this->aiService->complete($userId, [
                ['role' => 'user', 'content' => $prompt],
            ], [
                'max_tokens' => 16000,
            ], 'ai-optimizer');

            // IMPORTANTE: Riconnetti DB dopo chiamata AI
            Database::reconnect();

            if (isset($response['error'])) {
                return [
                    'error' => true,
                    'message' => $response['message'] ?? 'Errore AI',
                ];
            }

            // Parse risposta
            $refactoredContent = $this->parseResponse($response['result']);

            // Conta parole del nuovo contenuto
            $newWordCount = str_word_count(strip_tags($refactoredContent['content'] ?? ''));

            // Consuma crediti
            Credits::consume($userId, $creditCost, 'content_refactor', 'ai-optimizer', [
                'keyword' => $keyword,
                'original_words' => $originalArticle['word_count'] ?? 0,
                'new_words' => $newWordCount,
            ]);

            return [
                'success' => true,
                'data' => [
                    'title' => $refactoredContent['title'] ?? $originalArticle['title'],
                    'meta_description' => $refactoredContent['meta_description'] ?? '',
                    'h1' => $refactoredContent['h1'] ?? $refactoredContent['title'],
                    'content' => $refactoredContent['content'] ?? '',
                    'word_count' => $newWordCount,
                    'changes_summary' => $refactoredContent['changes_summary'] ?? [],
                ],
                'credits_used' => $creditCost,
            ];

        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Errore riscrittura: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Costruisci prompt per riscrittura
     */
    private function buildRefactorPrompt(
        array $original,
        array $analysis,
        string $keyword,
        int $targetWordCount,
        string $tone,
        bool $keepStructure
    ): string {
        $originalContent = $original['content'] ?? '';
        $originalTitle = $original['title'] ?? '';
        $originalH1 = $original['h1'] ?? '';
        $originalMeta = $original['description'] ?? '';
        $originalWordCount = $original['word_count'] ?? 0;

        // Estrai suggerimenti dall'analisi
        $suggestedTitle = $analysis['on_page_seo']['title']['suggestion'] ?? '';
        $suggestedMeta = $analysis['on_page_seo']['meta_description']['suggestion'] ?? '';
        $suggestedH1 = $analysis['on_page_seo']['h1']['suggestion'] ?? '';
        $sectionsToAdd = $analysis['content']['sections_to_add'] ?? [];
        $missingTopics = $analysis['gap_analysis']['missing_topics'] ?? [];
        $missingSections = $analysis['gap_analysis']['missing_sections'] ?? [];
        $headingSuggestions = $analysis['on_page_seo']['heading_structure']['suggestions'] ?? [];

        // Formatta sezioni da aggiungere
        $sectionsText = '';
        if (!empty($sectionsToAdd)) {
            $sectionsText = "Sezioni da AGGIUNGERE:\n";
            foreach ($sectionsToAdd as $section) {
                $sectionsText .= "- {$section['title']}: {$section['description']} (priorità: {$section['priority']})\n";
            }
        }

        // Formatta topic mancanti
        $topicsText = !empty($missingTopics) ? "Topic da includere: " . implode(', ', $missingTopics) : '';

        // Formatta heading suggestions
        $headingsText = !empty($headingSuggestions) ? "Heading suggeriti:\n- " . implode("\n- ", $headingSuggestions) : '';

        $structureNote = $keepStructure
            ? "IMPORTANTE: Mantieni la struttura originale dell'articolo, ma espandi e migliora ogni sezione."
            : "Puoi riorganizzare completamente la struttura per renderla più efficace.";

        return <<<PROMPT
Sei un copywriter SEO esperto italiano. Riscrivi e ottimizza questo articolo per posizionarsi meglio su Google.

=== KEYWORD TARGET ===
"{$keyword}"

=== ARTICOLO ORIGINALE ===
Title: {$originalTitle}
Meta Description: {$originalMeta}
H1: {$originalH1}
Parole: {$originalWordCount}

Contenuto:
{$originalContent}

=== SUGGERIMENTI DALL'ANALISI GAP ===

Title suggerito: {$suggestedTitle}
Meta description suggerita: {$suggestedMeta}
H1 suggerito: {$suggestedH1}

{$sectionsText}

{$topicsText}

{$headingsText}

=== ISTRUZIONI RISCRITTURA ===

1. TARGET PAROLE: circa {$targetWordCount} parole (attualmente {$originalWordCount})
2. TONO: {$tone}
3. {$structureNote}
4. Includi la keyword "{$keyword}" in modo naturale nel testo (title, H1, primi 100 parole, H2, conclusione)
5. Aggiungi tutte le sezioni mancanti identificate
6. Espandi le sezioni esistenti con più dettagli e valore
7. Usa H2 per sezioni principali, H3 per sottosezioni
8. Scrivi in ITALIANO fluente e naturale
9. Aggiungi una sezione FAQ se mancante (3-5 domande pertinenti)

=== OUTPUT RICHIESTO ===

Rispondi SOLO con JSON valido (senza markdown, senza backtick):
{
  "title": "Nuovo title SEO ottimizzato (max 60 caratteri)",
  "meta_description": "Nuova meta description (max 155 caratteri, include keyword e CTA)",
  "h1": "Nuovo H1 (può essere uguale al title o leggermente diverso)",
  "content": "CONTENUTO HTML COMPLETO dell'articolo riscritto. Usa <h2>, <h3>, <p>, <ul>, <ol>, <strong>, <em>. NON usare <h1> (già definito sopra). Inizia direttamente con il primo paragrafo o <h2>.",
  "changes_summary": [
    "Breve descrizione delle modifiche principali apportate",
    "Es: Aggiunta sezione FAQ con 5 domande",
    "Es: Espanso paragrafo introduttivo da 50 a 150 parole"
  ]
}
PROMPT;
    }

    /**
     * Parse risposta AI
     */
    private function parseResponse(string $text): array
    {
        // Rimuovi markdown se presente
        $jsonStr = preg_replace('/```json\s*/i', '', $text);
        $jsonStr = preg_replace('/```\s*/', '', $jsonStr);

        // Estrai JSON
        $firstBrace = strpos($jsonStr, '{');
        $lastBrace = strrpos($jsonStr, '}');

        if ($firstBrace === false || $lastBrace === false) {
            // Risposta troncata senza nessuna parentesi chiusa - tenta riparazione
            if ($firstBrace !== false) {
                throw new \Exception('Risposta AI troncata (JSON incompleto). Riprova la generazione.');
            }
            throw new \Exception('Nessun JSON trovato nella risposta AI');
        }

        $jsonStr = substr($jsonStr, $firstBrace, $lastBrace - $firstBrace + 1);
        $data = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON troncato: potrebbe essere una risposta tagliata dal max_tokens
            throw new \Exception('Risposta AI troncata o JSON non valido. Riprova la generazione.');
        }

        // Verifica campi obbligatori
        if (empty($data['content'])) {
            throw new \Exception('Risposta AI incompleta: contenuto mancante. Riprova.');
        }

        return $data;
    }

    /**
     * Ottieni costo crediti
     */
    public function getCreditCost(): float
    {
        return Credits::getCost('content_refactor', 'ai-optimizer');
    }
}
