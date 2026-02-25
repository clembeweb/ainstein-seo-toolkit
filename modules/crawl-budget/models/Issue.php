<?php

namespace Modules\CrawlBudget\Models;

use Core\Database;

/**
 * Issue Model
 *
 * Gestisce la tabella cb_issues con i problemi di crawl budget rilevati
 */
class Issue
{
    protected string $table = 'cb_issues';

    public const CATEGORIES = [
        'redirect' => 'Redirect',
        'waste' => 'Pagine Spreco',
        'indexability' => 'Indexability',
    ];

    /**
     * Crea singola issue
     */
    public function create(array $data): int
    {
        if (isset($data['details']) && is_array($data['details'])) {
            $data['details'] = json_encode($data['details']);
        }
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        return Database::insert($this->table, $data);
    }

    /**
     * Crea issues in batch
     */
    public function createMany(array $issues): int
    {
        $count = 0;
        foreach ($issues as $issue) {
            $this->create($issue);
            $count++;
        }
        return $count;
    }

    /**
     * Ottieni issues per sessione con paginazione e filtri
     */
    public function getBySession(int $sessionId, ?string $category = null, ?string $severity = null, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $where = ['i.session_id = ?'];
        $params = [$sessionId];

        if ($category !== null) {
            $where[] = 'i.category = ?';
            $params[] = $category;
        }

        if ($severity !== null) {
            $where[] = 'i.severity = ?';
            $params[] = $severity;
        }

        $whereStr = implode(' AND ', $where);
        $sql = "SELECT i.*, p.url as page_url
                FROM {$this->table} i
                LEFT JOIN cb_pages p ON i.page_id = p.id
                WHERE {$whereStr}
                ORDER BY FIELD(i.severity, 'critical', 'warning', 'notice'), i.id DESC
                LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        return Database::fetchAll($sql, $params);
    }

    /**
     * Conta issues per sessione
     */
    public function countBySession(int $sessionId, ?string $category = null, ?string $severity = null): int
    {
        $where = ['session_id = ?'];
        $params = [$sessionId];

        if ($category !== null) {
            $where[] = 'category = ?';
            $params[] = $category;
        }

        if ($severity !== null) {
            $where[] = 'severity = ?';
            $params[] = $severity;
        }

        $whereStr = implode(' AND ', $where);
        $row = Database::fetch("SELECT COUNT(*) as cnt FROM {$this->table} WHERE {$whereStr}", $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Riepilogo issues per sessione raggruppato per category e severity
     */
    public function getSummaryBySession(int $sessionId): array
    {
        return Database::fetchAll(
            "SELECT
                category,
                severity,
                COUNT(*) as cnt
             FROM {$this->table}
             WHERE session_id = ?
             GROUP BY category, severity
             ORDER BY FIELD(category, 'redirect', 'waste', 'indexability'),
                      FIELD(severity, 'critical', 'warning', 'notice')",
            [$sessionId]
        );
    }

    /**
     * Top issues per severity (per report AI)
     */
    public function getTopIssues(int $sessionId, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT i.*, p.url as page_url
             FROM {$this->table} i
             LEFT JOIN cb_pages p ON i.page_id = p.id
             WHERE i.session_id = ?
             ORDER BY FIELD(i.severity, 'critical', 'warning', 'notice'), i.id
             LIMIT ?",
            [$sessionId, $limit]
        );
    }

    /**
     * Conta totali per severity
     */
    public function countBySeverity(int $sessionId): array
    {
        $row = Database::fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END), 0) as critical,
                COALESCE(SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END), 0) as warning,
                COALESCE(SUM(CASE WHEN severity = 'notice' THEN 1 ELSE 0 END), 0) as notice
             FROM {$this->table}
             WHERE session_id = ?",
            [$sessionId]
        );

        return [
            'critical' => (int) ($row['critical'] ?? 0),
            'warning' => (int) ($row['warning'] ?? 0),
            'notice' => (int) ($row['notice'] ?? 0),
        ];
    }

    /**
     * Elimina issues per progetto
     */
    public function deleteByProject(int $projectId): int
    {
        return Database::delete($this->table, 'project_id = ?', [$projectId]);
    }
}
