<?php

namespace Modules\SeoAudit\Services;

use Core\Database;
use Modules\SeoAudit\Models\Page;

/**
 * BudgetScoreCalculator
 *
 * Calcola il Crawl Budget Score (0-100) basato su issue budget
 * e percentuale di pagine spreco.
 *
 * Formula: 100 - (critical*3 cap40 + warning*1.5 cap30 + notice*0.5 cap10 + waste%*0.4 cap20)
 */
class BudgetScoreCalculator
{
    private const BUDGET_CATEGORIES = ['redirect', 'waste', 'indexability'];

    /**
     * Calculate budget score for a project/session
     * @return array ['score', 'label', 'color', 'waste_percentage', 'severity_counts', 'total_pages', 'waste_pages']
     */
    public function calculate(int $projectId, ?int $sessionId = null): array
    {
        // Count budget issues by severity (only budget categories)
        $categoriesIn = "'" . implode("','", self::BUDGET_CATEGORIES) . "'";
        $sql = "SELECT severity, COUNT(*) as cnt FROM sa_issues
                WHERE project_id = ? AND category IN ({$categoriesIn})";
        $params = [$projectId];
        if ($sessionId) {
            $sql .= " AND session_id = ?";
            $params[] = $sessionId;
        }
        $sql .= " GROUP BY severity";

        $rows = Database::fetchAll($sql, $params);
        $severity = ['critical' => 0, 'warning' => 0, 'notice' => 0];
        foreach ($rows as $row) {
            $severity[$row['severity']] = (int) $row['cnt'];
        }

        // Total crawled pages
        $totalSql = "SELECT COUNT(*) as cnt FROM sa_pages WHERE project_id = ? AND status = 'crawled'";
        $totalParams = [$projectId];
        if ($sessionId) {
            $totalSql .= " AND session_id = ?";
            $totalParams[] = $sessionId;
        }
        $totalResult = Database::fetch($totalSql, $totalParams);
        $totalPages = (int) ($totalResult['cnt'] ?? 0);

        if ($totalPages === 0) {
            return [
                'score' => 0,
                'label' => 'N/D',
                'color' => 'slate',
                'waste_percentage' => 0,
                'severity_counts' => $severity,
                'total_pages' => 0,
                'waste_pages' => 0,
            ];
        }

        // Waste pages count
        $pageModel = new Page();
        $wastePages = $pageModel->getWastePages($projectId, $sessionId);
        $wastePercentage = ($wastePages / $totalPages) * 100;

        // Score formula
        $score = 100.0;
        $score -= min(40, $severity['critical'] * 3);
        $score -= min(30, $severity['warning'] * 1.5);
        $score -= min(10, $severity['notice'] * 0.5);
        $score -= min(20, $wastePercentage * 0.4);
        $score = max(0, (int) round($score));

        return [
            'score' => $score,
            'label' => self::getLabel($score),
            'color' => self::getColor($score),
            'waste_percentage' => round($wastePercentage, 1),
            'severity_counts' => $severity,
            'total_pages' => $totalPages,
            'waste_pages' => $wastePages,
        ];
    }

    public static function getLabel(int $score): string
    {
        if ($score >= 90) return 'Eccellente';
        if ($score >= 70) return 'Buono';
        if ($score >= 50) return 'Migliorabile';
        return 'Critico';
    }

    public static function getColor(int $score): string
    {
        if ($score >= 90) return 'emerald';
        if ($score >= 70) return 'blue';
        if ($score >= 50) return 'amber';
        return 'red';
    }
}
