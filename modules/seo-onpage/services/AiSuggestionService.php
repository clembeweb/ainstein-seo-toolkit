<?php

namespace Modules\SeoOnpage\Services;

use Core\Database;
use Core\Credits;
use Services\AiService;
use Modules\SeoOnpage\Models\AiSuggestion;
use Modules\SeoOnpage\Models\Analysis;
use Modules\SeoOnpage\Models\Issue;

/**
 * AiSuggestionService
 * Genera suggerimenti SEO usando Claude AI
 */
class AiSuggestionService
{
    private AiService $ai;
    private AiSuggestion $suggestion;
    private Analysis $analysis;
    private Issue $issue;

    public function __construct()
    {
        $this->ai = new AiService('seo-onpage');
        $this->suggestion = new AiSuggestion();
        $this->analysis = new Analysis();
        $this->issue = new Issue();
    }

    /**
     * Check if AI is configured
     */
    public function isConfigured(): bool
    {
        return $this->ai->isConfigured();
    }

    /**
     * Generate suggestions for a page
     */
    public function generateForPage(int $userId, int $projectId, int $pageId, array $pageData, array $analysisData, array $issues): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'AI non configurata'];
        }

        $cost = Credits::getCost('ai_suggestions', 'seo-onpage');

        if (!Credits::hasEnough($userId, $cost)) {
            return ['success' => false, 'error' => "Crediti insufficienti. Richiesti: {$cost}"];
        }

        // Build context for AI
        $context = $this->buildContext($pageData, $analysisData, $issues);

        // Generate suggestions using AI
        $prompt = $this->buildPrompt($context);

        try {
            $result = $this->ai->analyze($userId, $prompt, '', 'seo-onpage');

            if (!$result['success']) {
                return ['success' => false, 'error' => $result['error'] ?? 'Errore AI'];
            }

            // Parse suggestions from AI response
            $suggestions = $this->parseSuggestions($result['content']);

            if (empty($suggestions)) {
                return ['success' => false, 'error' => 'Nessun suggerimento generato'];
            }

            // Reconnect DB after AI call
            Database::reconnect();

            // Get latest analysis
            $latestAnalysis = $this->analysis->getLatestForPage($pageId);
            $analysisId = $latestAnalysis ? $latestAnalysis['id'] : 0;

            // Save suggestions
            $savedCount = $this->suggestion->createBulk($analysisId, $pageId, $suggestions, $cost);

            // Consume credits
            Credits::consume($userId, $cost, 'ai_suggestions', 'seo-onpage', [
                'page_id' => $pageId,
                'suggestions_count' => $savedCount,
            ]);

            return [
                'success' => true,
                'suggestions_count' => $savedCount,
                'credits_used' => $cost,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Errore generazione: ' . $e->getMessage()];
        }
    }

    /**
     * Build context from page data
     */
    private function buildContext(array $pageData, array $analysisData, array $issues): array
    {
        $context = [
            'url' => $pageData['url'] ?? '',
            'title' => $pageData['title'] ?? '',
            'score' => $analysisData['onpage_score'] ?? null,
            'meta_title_length' => $analysisData['meta_title_length'] ?? 0,
            'meta_description_length' => $analysisData['meta_description_length'] ?? 0,
            'h1_count' => $analysisData['h1_count'] ?? 0,
            'word_count' => $analysisData['content_word_count'] ?? 0,
            'images_count' => $analysisData['images_count'] ?? 0,
            'images_without_alt' => $analysisData['images_without_alt'] ?? 0,
            'internal_links' => $analysisData['internal_links_count'] ?? 0,
            'external_links' => $analysisData['external_links_count'] ?? 0,
        ];

        // Extract issues by category
        $context['issues'] = [];
        foreach ($issues as $issue) {
            $context['issues'][] = [
                'category' => $issue['category'],
                'severity' => $issue['severity'],
                'message' => $issue['message'],
                'current_value' => $issue['current_value'] ?? null,
            ];
        }

        return $context;
    }

    /**
     * Build AI prompt
     */
    private function buildPrompt(array $context): string
    {
        $issuesText = '';
        foreach ($context['issues'] as $issue) {
            $severity = $issue['severity'] === 'critical' ? 'CRITICO' : ($issue['severity'] === 'warning' ? 'AVVISO' : 'INFO');
            $issuesText .= "- [{$severity}] {$issue['message']}\n";
            if (!empty($issue['current_value'])) {
                $current = is_string($issue['current_value']) ? $issue['current_value'] : json_encode($issue['current_value']);
                $issuesText .= "  Valore attuale: {$current}\n";
            }
        }

        return <<<PROMPT
Sei un esperto SEO. Analizza questa pagina web e fornisci suggerimenti specifici per migliorare il posizionamento.

## Dati Pagina

URL: {$context['url']}
Title attuale: {$context['title']}
Score OnPage: {$context['score']}/100

### Metriche
- Lunghezza Title: {$context['meta_title_length']} caratteri
- Lunghezza Meta Description: {$context['meta_description_length']} caratteri
- Numero H1: {$context['h1_count']}
- Parole nel contenuto: {$context['word_count']}
- Immagini: {$context['images_count']} (senza alt: {$context['images_without_alt']})
- Link interni: {$context['internal_links']}
- Link esterni: {$context['external_links']}

### Issues Rilevati
{$issuesText}

## Istruzioni

Genera suggerimenti SEO specifici e azionabili. Per ogni suggerimento:
1. Indica il tipo (title, description, h1, content, technical)
2. Spiega cosa cambiare e perche
3. Se possibile, suggerisci il valore esatto da usare

Rispondi SOLO in formato JSON con questa struttura:
```json
{
  "suggestions": [
    {
      "type": "title|description|h1|content|technical",
      "priority": "high|medium|low",
      "current_value": "valore attuale se rilevante",
      "suggested_value": "valore suggerito specifico",
      "reasoning": "spiegazione chiara e concisa"
    }
  ]
}
```

Genera 3-5 suggerimenti prioritari. Sii specifico e pratico.
PROMPT;
    }

    /**
     * Parse suggestions from AI response
     */
    private function parseSuggestions(string $content): array
    {
        $suggestions = [];

        // Try to extract JSON from response
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $content, $matches)) {
            $jsonStr = $matches[1];
        } elseif (preg_match('/\{[\s\S]*"suggestions"[\s\S]*\}/', $content, $matches)) {
            $jsonStr = $matches[0];
        } else {
            $jsonStr = $content;
        }

        $data = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['suggestions'])) {
            return [];
        }

        foreach ($data['suggestions'] as $s) {
            $type = $this->normalizeType($s['type'] ?? 'content');
            $priority = $this->normalizePriority($s['priority'] ?? 'medium');

            $suggestions[] = [
                'suggestion_type' => $type,
                'priority' => $priority,
                'current_value' => $s['current_value'] ?? null,
                'suggested_value' => $s['suggested_value'] ?? null,
                'reasoning' => $s['reasoning'] ?? '',
            ];
        }

        return $suggestions;
    }

    /**
     * Normalize suggestion type
     */
    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        $map = [
            'title' => 'title',
            'meta_title' => 'title',
            'metatitle' => 'title',
            'description' => 'description',
            'meta_description' => 'description',
            'metadescription' => 'description',
            'h1' => 'h1',
            'heading' => 'h1',
            'content' => 'content',
            'testo' => 'content',
            'technical' => 'technical',
            'tecnico' => 'technical',
        ];

        return $map[$type] ?? 'content';
    }

    /**
     * Normalize priority
     */
    private function normalizePriority(string $priority): string
    {
        $priority = strtolower(trim($priority));

        $map = [
            'high' => 'high',
            'alta' => 'high',
            'critico' => 'high',
            'medium' => 'medium',
            'media' => 'medium',
            'low' => 'low',
            'bassa' => 'low',
        ];

        return $map[$priority] ?? 'medium';
    }
}
