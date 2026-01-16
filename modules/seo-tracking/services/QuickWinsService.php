<?php

namespace Modules\SeoTracking\Services;

use Core\Database;
use Core\Credits;

require_once __DIR__ . '/../../../services/AiService.php';

/**
 * QuickWinsService
 * Identifica keyword con alto potenziale di miglioramento (Quick Wins)
 *
 * Criteri Quick Wins:
 * - Posizione 4-20 (vicine a Top 3 ma non ancora)
 * - Impressioni >= 100 (volume sufficiente)
 * - CTR potenzialmente migliorabile
 */
class QuickWinsService
{
    private \Services\AiService $aiService;

    public function __construct()
    {
        $this->aiService = new \Services\AiService('seo-tracking');
    }

    /**
     * Verifica se il servizio AI è configurato
     */
    public function isConfigured(): bool
    {
        return $this->aiService->isConfigured();
    }

    /**
     * Ottieni keyword candidate per Quick Wins (progetto intero)
     */
    public function getCandidateKeywords(int $projectId, int $limit = 50): array
    {
        $sql = "
            SELECT
                k.id,
                k.keyword,
                k.last_position as position,
                k.last_clicks as clicks,
                k.last_impressions as impressions,
                k.last_ctr as ctr,
                k.target_url
            FROM st_keywords k
            WHERE k.project_id = ?
              AND k.last_position >= 4
              AND k.last_position <= 20
              AND k.last_impressions >= 100
            ORDER BY
                k.last_impressions DESC,
                k.last_position ASC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Ottieni keyword candidate per Quick Wins (gruppo specifico)
     */
    public function getCandidateKeywordsForGroup(int $groupId, int $limit = 50): array
    {
        $sql = "
            SELECT
                k.id,
                k.keyword,
                k.last_position as position,
                k.last_clicks as clicks,
                k.last_impressions as impressions,
                k.last_ctr as ctr,
                k.target_url
            FROM st_keywords k
            JOIN st_keyword_group_members m ON k.id = m.keyword_id
            WHERE m.group_id = ?
              AND k.last_position >= 4
              AND k.last_position <= 20
              AND k.last_impressions >= 100
            ORDER BY
                k.last_impressions DESC,
                k.last_position ASC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$groupId, $limit]);
    }

    /**
     * Analizza Quick Wins con AI
     */
    public function analyze(int $projectId, int $userId, ?int $groupId = null): array
    {
        // Verifica crediti
        $creditCost = Credits::getCost('quick_wins', 'seo-tracking');
        if (!Credits::hasEnough($userId, $creditCost)) {
            return [
                'error' => true,
                'message' => 'Crediti insufficienti',
                'credits_required' => $creditCost,
            ];
        }

        // Ottieni keyword candidate
        $keywords = $groupId
            ? $this->getCandidateKeywordsForGroup($groupId)
            : $this->getCandidateKeywords($projectId);

        if (empty($keywords)) {
            return [
                'error' => true,
                'message' => 'Nessuna keyword eligibile trovata (posizione 4-20, impressioni >= 100)',
            ];
        }

        // Ottieni info progetto
        $project = Database::fetch("SELECT name, domain FROM st_projects WHERE id = ?", [$projectId]);

        // Costruisci prompt
        $prompt = $this->buildPrompt($project, $keywords);

        // Chiama AI
        $response = $this->aiService->analyze(
            $userId,
            $prompt,
            '',
            'seo-tracking'
        );

        // IMPORTANTE: Riconnetti DB dopo chiamata AI lunga
        Database::reconnect();

        if (isset($response['error'])) {
            return [
                'error' => true,
                'message' => $response['message'] ?? 'Errore AI',
            ];
        }

        // Parse risposta JSON
        try {
            $result = $this->parseResponse($response['result']);
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Errore parsing risposta AI: ' . $e->getMessage(),
            ];
        }

        // Salva report
        $reportId = $this->saveReport($projectId, $groupId, $result, count($keywords));

        // Scala crediti (già scalati da AiService, ma logghiamo)
        // Credits già consumati in AiService->analyze()

        return [
            'success' => true,
            'report_id' => $reportId,
            'data' => $result,
            'keywords_analyzed' => count($keywords),
            'credits_used' => $response['credits_used'] ?? $creditCost,
        ];
    }

    /**
     * Costruisce prompt AI
     */
    private function buildPrompt(array $project, array $keywords): string
    {
        $keywordsSummary = array_map(
            fn($k) => sprintf(
                "%s | pos: %.1f | click: %d | impr: %d | ctr: %.2f%%",
                $k['keyword'],
                $k['position'],
                $k['clicks'],
                $k['impressions'],
                ($k['ctr'] ?? 0) * 100
            ),
            $keywords
        );
        $keywordsText = implode("\n", $keywordsSummary);

        return <<<PROMPT
Sei un esperto SEO. Analizza queste keyword "Quick Wins" - keyword in posizione 4-20 con buone impressioni che potrebbero facilmente salire in Top 3.

PROGETTO: {$project['name']}
DOMINIO: {$project['domain']}

KEYWORD CANDIDATE (formato: keyword | posizione | click | impressioni | ctr):
{$keywordsText}

ISTRUZIONI:
1. Identifica le TOP 10 opportunità Quick Wins ordinate per impatto potenziale
2. Per ogni opportunità:
   - Stima il potenziale aumento di click se raggiungesse Top 3
   - Suggerisci 2-3 azioni concrete per migliorare il ranking
   - Indica la difficoltà di implementazione (facile/media/difficile)
3. Fornisci raccomandazioni generali per il progetto

Rispondi SOLO con JSON valido (senza markdown, senza backtick):
{
  "summary": {
    "total_opportunities": numero,
    "estimated_click_increase": numero_click_potenziali,
    "top_priority_count": numero_alta_priorita
  },
  "opportunities": [
    {
      "rank": 1,
      "keyword": "keyword",
      "current_position": posizione,
      "current_clicks": click,
      "current_impressions": impressioni,
      "potential_clicks": click_stimati_in_top3,
      "impact": "high|medium|low",
      "difficulty": "facile|media|difficile",
      "suggestions": [
        "Suggerimento 1",
        "Suggerimento 2"
      ]
    }
  ],
  "recommendations": [
    "Raccomandazione generale 1",
    "Raccomandazione generale 2"
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
            throw new \Exception('Nessun JSON trovato nella risposta');
        }

        $jsonStr = substr($jsonStr, $firstBrace, $lastBrace - $firstBrace + 1);
        $data = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON non valido: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Salva report nel database
     */
    private function saveReport(int $projectId, ?int $groupId, array $data, int $keywordsAnalyzed): int
    {
        $title = $groupId
            ? 'Quick Wins - Gruppo #' . $groupId
            : 'Quick Wins Analysis';

        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        Database::insert('st_ai_reports', [
            'project_id' => $projectId,
            'report_type' => 'quick_wins',
            'title' => $title,
            'content' => $content,
            'metadata' => json_encode([
                'group_id' => $groupId,
                'keywords_analyzed' => $keywordsAnalyzed,
                'opportunities_found' => count($data['opportunities'] ?? []),
            ]),
        ]);

        return Database::lastInsertId();
    }

    /**
     * Ottieni costo in crediti
     */
    public function getCreditCost(): float
    {
        return Credits::getCost('quick_wins', 'seo-tracking');
    }
}
