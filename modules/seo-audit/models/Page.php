<?php

namespace Modules\SeoAudit\Models;

use Core\Database;

/**
 * Page Model
 *
 * Gestisce la tabella sa_pages con le pagine crawlate
 */
class Page
{
    protected string $table = 'sa_pages';

    /**
     * Trova pagina per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Trova pagina per URL e progetto
     */
    public function findByUrl(int $projectId, string $url): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ? AND url = ?";
        return Database::fetch($sql, [$projectId, $url]);
    }

    /**
     * Ottieni pagine per progetto con paginazione
     */
    public function getByProject(int $projectId, int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;

        $where = ['project_id = ?'];
        $params = [$projectId];

        // Filtro status code
        if (!empty($filters['status_code'])) {
            if ($filters['status_code'] === '2xx') {
                $where[] = 'status_code >= 200 AND status_code < 300';
            } elseif ($filters['status_code'] === '3xx') {
                $where[] = 'status_code >= 300 AND status_code < 400';
            } elseif ($filters['status_code'] === '4xx') {
                $where[] = 'status_code >= 400 AND status_code < 500';
            } elseif ($filters['status_code'] === '5xx') {
                $where[] = 'status_code >= 500';
            } else {
                $where[] = 'status_code = ?';
                $params[] = (int) $filters['status_code'];
            }
        }

        // Filtro indicizzabilità
        if (isset($filters['is_indexable'])) {
            $where[] = 'is_indexable = ?';
            $params[] = (bool) $filters['is_indexable'];
        }

        // Filtro ricerca URL
        if (!empty($filters['search'])) {
            $where[] = 'url LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        // Filtro issues
        if (!empty($filters['has_issues'])) {
            $where[] = 'id IN (SELECT DISTINCT page_id FROM sa_issues WHERE project_id = ? AND page_id IS NOT NULL)';
            $params[] = $projectId;
        }

        // Filtro status (pending, crawled, error)
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        // Count totale
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
        $countResult = Database::fetch($countSql, $params);
        $total = (int) $countResult['total'];

        // Dati paginati
        $sql = "
            SELECT
                p.*,
                (SELECT COUNT(*) FROM sa_issues WHERE page_id = p.id) as issues_count
            FROM {$this->table} p
            WHERE {$whereClause}
            ORDER BY p.crawled_at DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $perPage;
        $params[] = $offset;

        $data = Database::fetchAll($sql, $params);

        return [
            'data' => $data,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * Crea nuova pagina
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Crea o aggiorna pagina (upsert)
     * Automatically sets status='crawled' when saving crawl data
     */
    public function upsert(int $projectId, string $url, array $data): int
    {
        // Set status to crawled when we have crawl data
        if (isset($data['status_code']) || isset($data['title'])) {
            $data['status'] = 'crawled';
            $data['crawled_at'] = date('Y-m-d H:i:s');
        }

        $existing = $this->findByUrl($projectId, $url);

        if ($existing) {
            $this->update($existing['id'], $data);
            return $existing['id'];
        }

        $data['project_id'] = $projectId;
        $data['url'] = $url;
        return $this->create($data);
    }

    /**
     * Aggiorna pagina
     */
    public function update(int $id, array $data): bool
    {
        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Elimina pagina
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Elimina tutte le pagine di un progetto
     */
    public function deleteByProject(int $projectId): int
    {
        return Database::delete($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Elimina multiple pagine per ID
     */
    public function deleteMultiple(array $ids, int $projectId): int
    {
        if (empty($ids)) return 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, [$projectId]);

        return Database::execute(
            "DELETE FROM {$this->table} WHERE id IN ({$placeholders}) AND project_id = ?",
            $params
        );
    }

    /**
     * Elimina pagine per status
     */
    public function deleteByStatus(int $projectId, string $status): int
    {
        return Database::delete($this->table, 'project_id = ? AND status = ?', [$projectId, $status]);
    }

    /**
     * Ottieni URL pending per un progetto
     */
    public function getPendingUrls(int $projectId, int $limit = 0): array
    {
        $sql = "SELECT id, url FROM {$this->table} WHERE project_id = ? AND status = 'pending' ORDER BY id";
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Conta pagine per progetto
     */
    public function countByProject(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Ottieni statistiche pagine per progetto
     */
    public function getStats(int $projectId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'crawled' THEN 1 ELSE 0 END) as crawled,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error,
                SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as status_2xx,
                SUM(CASE WHEN status_code >= 300 AND status_code < 400 THEN 1 ELSE 0 END) as status_3xx,
                SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) as status_4xx,
                SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as status_5xx,
                SUM(CASE WHEN is_indexable = 1 THEN 1 ELSE 0 END) as indexable,
                SUM(CASE WHEN is_indexable = 0 THEN 1 ELSE 0 END) as not_indexable,
                AVG(load_time_ms) as avg_load_time,
                AVG(word_count) as avg_word_count
            FROM {$this->table}
            WHERE project_id = ?
        ";

        $result = Database::fetch($sql, [$projectId]);

        return [
            'total' => (int) ($result['total'] ?? 0),
            'pending' => (int) ($result['pending'] ?? 0),
            'crawled' => (int) ($result['crawled'] ?? 0),
            'error' => (int) ($result['error'] ?? 0),
            'status_2xx' => (int) ($result['status_2xx'] ?? 0),
            'status_3xx' => (int) ($result['status_3xx'] ?? 0),
            'status_4xx' => (int) ($result['status_4xx'] ?? 0),
            'status_5xx' => (int) ($result['status_5xx'] ?? 0),
            'indexable' => (int) ($result['indexable'] ?? 0),
            'not_indexable' => (int) ($result['not_indexable'] ?? 0),
            'avg_load_time' => round((float) ($result['avg_load_time'] ?? 0)),
            'avg_word_count' => round((float) ($result['avg_word_count'] ?? 0)),
        ];
    }

    /**
     * Ottieni pagina con dettagli completi
     */
    public function findWithDetails(int $id, int $projectId): ?array
    {
        $sql = "
            SELECT
                p.*,
                (SELECT COUNT(*) FROM sa_issues WHERE page_id = p.id AND severity = 'critical') as critical_issues,
                (SELECT COUNT(*) FROM sa_issues WHERE page_id = p.id AND severity = 'warning') as warning_issues,
                (SELECT COUNT(*) FROM sa_issues WHERE page_id = p.id AND severity = 'notice') as notice_issues
            FROM {$this->table} p
            WHERE p.id = ? AND p.project_id = ?
        ";

        return Database::fetch($sql, [$id, $projectId]);
    }

    /**
     * Ottieni pagine con title duplicato
     */
    public function getDuplicateTitles(int $projectId): array
    {
        $sql = "
            SELECT title, GROUP_CONCAT(url SEPARATOR '|||') as urls, COUNT(*) as count
            FROM {$this->table}
            WHERE project_id = ? AND title IS NOT NULL AND title != ''
            GROUP BY title
            HAVING count > 1
            ORDER BY count DESC
        ";

        $results = Database::fetchAll($sql, [$projectId]);

        // Parse URLs
        foreach ($results as &$row) {
            $row['urls'] = explode('|||', $row['urls']);
        }

        return $results;
    }

    /**
     * Ottieni pagine con meta description duplicata
     */
    public function getDuplicateDescriptions(int $projectId): array
    {
        $sql = "
            SELECT meta_description, GROUP_CONCAT(url SEPARATOR '|||') as urls, COUNT(*) as count
            FROM {$this->table}
            WHERE project_id = ? AND meta_description IS NOT NULL AND meta_description != ''
            GROUP BY meta_description
            HAVING count > 1
            ORDER BY count DESC
        ";

        $results = Database::fetchAll($sql, [$projectId]);

        foreach ($results as &$row) {
            $row['urls'] = explode('|||', $row['urls']);
        }

        return $results;
    }

    /**
     * Ottieni pagine con contenuto scarso (thin content)
     */
    public function getThinContentPages(int $projectId, int $minWords = 300): array
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE project_id = ? AND word_count < ? AND word_count > 0
            ORDER BY word_count ASC
        ";

        return Database::fetchAll($sql, [$projectId, $minWords]);
    }

    /**
     * Ottieni pagine senza H1
     */
    public function getPagesWithoutH1(int $projectId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ? AND h1_count = 0";
        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Ottieni pagine con H1 multipli
     */
    public function getPagesWithMultipleH1(int $projectId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ? AND h1_count > 1";
        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Ottieni pagine con immagini senza alt
     */
    public function getPagesWithMissingAlt(int $projectId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ? AND images_without_alt > 0";
        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Ottieni pagine con link rotti
     */
    public function getPagesWithBrokenLinks(int $projectId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ? AND broken_links_count > 0";
        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Ottieni pagine non indicizzabili
     */
    public function getNonIndexablePages(int $projectId): array
    {
        $sql = "
            SELECT *, indexability_reason
            FROM {$this->table}
            WHERE project_id = ? AND is_indexable = 0
            ORDER BY indexability_reason, url
        ";
        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Ottieni pagine lente (load time > threshold)
     */
    public function getSlowPages(int $projectId, int $thresholdMs = 3000): array
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE project_id = ? AND load_time_ms > ?
            ORDER BY load_time_ms DESC
        ";
        return Database::fetchAll($sql, [$projectId, $thresholdMs]);
    }
}
