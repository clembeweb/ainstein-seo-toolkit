<?php

namespace Modules\SeoAudit\Services;

use Core\Database;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\Issue;

/**
 * AuditAnalyzer
 *
 * Servizio per analisi e statistiche audit
 */
class AuditAnalyzer
{
    private Project $projectModel;
    private Page $pageModel;
    private Issue $issueModel;

    /**
     * Definizione categorie con icone e colori
     */
    public const CATEGORY_CONFIG = [
        'meta' => [
            'icon' => 'document-text',
            'color' => 'blue',
            'description' => 'Title, description, OG tags',
        ],
        'headings' => [
            'icon' => 'bars-3-bottom-left',
            'color' => 'purple',
            'description' => 'Struttura H1-H6',
        ],
        'images' => [
            'icon' => 'photo',
            'color' => 'pink',
            'description' => 'Alt, dimensioni, ottimizzazione',
        ],
        'links' => [
            'icon' => 'link',
            'color' => 'indigo',
            'description' => 'Interni, esterni, broken',
        ],
        'content' => [
            'icon' => 'document-duplicate',
            'color' => 'amber',
            'description' => 'Thin content, duplicati',
        ],
        'technical' => [
            'icon' => 'cog-6-tooth',
            'color' => 'slate',
            'description' => 'Canonical, robots, hreflang',
        ],
        'schema' => [
            'icon' => 'code-bracket',
            'color' => 'emerald',
            'description' => 'Structured data JSON-LD',
        ],
        'security' => [
            'icon' => 'shield-check',
            'color' => 'red',
            'description' => 'HTTPS, SSL, mixed content',
        ],
        'sitemap' => [
            'icon' => 'map',
            'color' => 'cyan',
            'description' => 'Presenza e validità sitemap',
        ],
        'robots' => [
            'icon' => 'document-magnifying-glass',
            'color' => 'orange',
            'description' => 'Regole robots.txt',
        ],
    ];

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->pageModel = new Page();
        $this->issueModel = new Issue();
    }

    /**
     * Esegui analisi completa dopo crawl
     */
    public function analyze(int $projectId): array
    {
        $issueDetector = new IssueDetector();
        $issueDetector->init($projectId);

        // Esegui check a livello progetto
        $projectIssues = $issueDetector->runProjectLevelChecks();

        // Calcola health score
        $healthScore = $this->calculateHealthScore($projectId);

        // Aggiorna progetto
        $this->projectModel->update($projectId, [
            'health_score' => $healthScore,
            'status' => 'completed',
        ]);

        // Statistiche finali
        $stats = $this->getStatsByCategory($projectId);

        return [
            'health_score' => $healthScore,
            'project_issues_added' => $projectIssues,
            'category_stats' => $stats,
        ];
    }

    /**
     * Calcola Health Score (0-100)
     * Formula: 100 - (critical*10 + warning*3 + notice*1) / pagine * fattore_normalizzazione
     */
    public function calculateHealthScore(int $projectId): int
    {
        $totalPages = Database::count('sa_pages', 'project_id = ?', [$projectId]);

        if ($totalPages === 0) {
            return 0;
        }

        // Conta issues per severity
        $sql = "
            SELECT
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END) as warning,
                SUM(CASE WHEN severity = 'notice' THEN 1 ELSE 0 END) as notice
            FROM sa_issues
            WHERE project_id = ?
        ";
        $issues = Database::fetch($sql, [$projectId]);

        $criticalCount = (int) ($issues['critical'] ?? 0);
        $warningCount = (int) ($issues['warning'] ?? 0);
        $noticeCount = (int) ($issues['notice'] ?? 0);

        // Pesi per severity
        $criticalWeight = 10;
        $warningWeight = 3;
        $noticeWeight = 0.5;

        // Calcola penalità totale
        $totalPenalty = ($criticalCount * $criticalWeight)
                      + ($warningCount * $warningWeight)
                      + ($noticeCount * $noticeWeight);

        // Normalizza per numero di pagine (issues per pagina media)
        // Un sito con 100 pagine e 50 issues critiche è peggio di uno con 1000 pagine e 50 issues
        $normalizedPenalty = $totalPenalty / max(1, sqrt($totalPages));

        // Cap la penalità a 100
        $normalizedPenalty = min($normalizedPenalty, 100);

        // Score finale
        $score = 100 - (int) $normalizedPenalty;

        return max(0, min(100, $score));
    }

    /**
     * Statistiche issues per categoria
     */
    public function getStatsByCategory(int $projectId): array
    {
        $categoryCounts = $this->issueModel->countByCategory($projectId);
        $stats = [];

        // Aggiungi tutte le categorie (anche quelle senza issues)
        foreach (self::CATEGORY_CONFIG as $slug => $config) {
            $counts = $categoryCounts[$slug] ?? [
                'total' => 0,
                'critical' => 0,
                'warning' => 0,
                'notice' => 0,
                'info' => 0,
            ];

            $stats[$slug] = [
                'slug' => $slug,
                'label' => Issue::CATEGORIES[$slug] ?? $slug,
                'icon' => $config['icon'],
                'color' => $config['color'],
                'description' => $config['description'],
                'total' => $counts['total'],
                'critical' => $counts['critical'],
                'warning' => $counts['warning'],
                'notice' => $counts['notice'],
                'info' => $counts['info'] ?? 0,
                'is_gsc' => Issue::isGscCategory($slug),
            ];
        }

        // Ordina per numero issues (critiche prima)
        uasort($stats, function ($a, $b) {
            // Prima per critical
            if ($a['critical'] !== $b['critical']) {
                return $b['critical'] - $a['critical'];
            }
            // Poi per warning
            if ($a['warning'] !== $b['warning']) {
                return $b['warning'] - $a['warning'];
            }
            // Poi per totale
            return $b['total'] - $a['total'];
        });

        return $stats;
    }

    /**
     * Ottieni riepilogo audit per overview
     */
    public function getAuditSummary(int $projectId): array
    {
        $project = $this->projectModel->findWithStats($projectId, null);
        $pageStats = $this->pageModel->getStats($projectId);
        $issueCounts = $this->issueModel->countBySeverity($projectId);

        return [
            'project' => $project,
            'pages' => [
                'total' => $pageStats['total'],
                'indexable' => $pageStats['indexable'],
                'not_indexable' => $pageStats['not_indexable'],
                'status_2xx' => $pageStats['status_2xx'],
                'status_3xx' => $pageStats['status_3xx'],
                'status_4xx' => $pageStats['status_4xx'],
                'status_5xx' => $pageStats['status_5xx'],
                'avg_load_time' => $pageStats['avg_load_time'],
                'avg_word_count' => $pageStats['avg_word_count'],
            ],
            'issues' => [
                'total' => $issueCounts['total'],
                'critical' => $issueCounts['critical'],
                'warning' => $issueCounts['warning'],
                'notice' => $issueCounts['notice'],
            ],
            'health_score' => $project['health_score'] ?? 0,
        ];
    }

    /**
     * Ottieni trend issues (per confronto)
     */
    public function getIssuesTrend(int $projectId): array
    {
        // Placeholder per confronto storico
        // In futuro: confronta con audit precedenti
        return [
            'critical_change' => 0,
            'warning_change' => 0,
            'notice_change' => 0,
            'score_change' => 0,
        ];
    }

    /**
     * Ottieni top problemi per impatto
     */
    public function getTopIssuesByImpact(int $projectId, int $limit = 10): array
    {
        // Issues raggruppate per tipo con conteggio
        $sql = "
            SELECT
                issue_type,
                category,
                severity,
                title,
                COUNT(*) as occurrences,
                GROUP_CONCAT(DISTINCT SUBSTRING(affected_element, 1, 100) SEPARATOR '|||') as examples
            FROM sa_issues
            WHERE project_id = ?
            GROUP BY issue_type, category, severity, title
            ORDER BY
                FIELD(severity, 'critical', 'warning', 'notice', 'info'),
                occurrences DESC
            LIMIT ?
        ";

        $results = Database::fetchAll($sql, [$projectId, $limit]);

        foreach ($results as &$row) {
            $row['examples'] = array_slice(explode('|||', $row['examples'] ?? ''), 0, 3);
            $row['recommendation'] = Issue::ISSUE_TYPES[$row['issue_type']]['recommendation'] ?? null;
        }

        return $results;
    }

    /**
     * Calcola punteggio per categoria
     */
    public function getCategoryScores(int $projectId): array
    {
        $stats = $this->getStatsByCategory($projectId);
        $totalPages = Database::count('sa_pages', 'project_id = ?', [$projectId]);

        $scores = [];
        foreach ($stats as $slug => $data) {
            if ($totalPages === 0) {
                $scores[$slug] = 100;
                continue;
            }

            $penalty = ($data['critical'] * 10) + ($data['warning'] * 3) + ($data['notice'] * 0.5);
            $normalizedPenalty = min(100, $penalty / sqrt($totalPages));
            $scores[$slug] = max(0, 100 - (int) $normalizedPenalty);
        }

        return $scores;
    }

    /**
     * Esporta dati per CSV
     */
    public function exportIssuesForCsv(int $projectId, ?string $category = null): array
    {
        $filters = [];
        if ($category) {
            $filters['category'] = $category;
        }

        $issuesData = $this->issueModel->getByProject($projectId, 1, 10000, $filters);

        $rows = [];
        $rows[] = ['URL', 'Categoria', 'Tipo', 'Gravità', 'Titolo', 'Elemento', 'Raccomandazione'];

        foreach ($issuesData['data'] as $issue) {
            $rows[] = [
                $issue['page_url'] ?? 'N/A',
                Issue::CATEGORIES[$issue['category']] ?? $issue['category'],
                $issue['issue_type'],
                $issue['severity'],
                $issue['title'],
                $issue['affected_element'] ?? '',
                $issue['recommendation'] ?? '',
            ];
        }

        return $rows;
    }
}
