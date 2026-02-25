<?php

namespace Modules\CrawlBudget\Models;

use Core\Database;

/**
 * Page Model
 *
 * Gestisce la tabella cb_pages con le pagine crawlate
 */
class Page
{
    protected string $table = 'cb_pages';

    /**
     * Trova pagina per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Trova pagina per URL e sessione
     */
    public function findByUrl(int $sessionId, string $url): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE session_id = ? AND url = ?",
            [$sessionId, $url]
        );
    }

    /**
     * Prossima pagina pending da crawlare
     */
    public function findPending(int $sessionId): ?array
    {
        return Database::fetch(
            "SELECT id, url, depth, discovered_from FROM {$this->table}
             WHERE session_id = ? AND status = 'pending'
             ORDER BY depth ASC, id ASC LIMIT 1",
            [$sessionId]
        );
    }

    /**
     * Inserisci o aggiorna pagina
     */
    public function upsert(int $projectId, int $sessionId, string $url, array $data = []): int
    {
        $existing = $this->findByUrl($sessionId, $url);
        if ($existing) {
            $this->updateById((int) $existing['id'], $data);
            return (int) $existing['id'];
        }

        $data['project_id'] = $projectId;
        $data['session_id'] = $sessionId;
        $data['url'] = $url;
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return Database::insert($this->table, $data);
    }

    /**
     * Aggiorna pagina per ID
     */
    public function updateById(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Segna come in crawling
     */
    public function markCrawling(int $id): bool
    {
        return $this->updateById($id, ['status' => 'crawling']);
    }

    /**
     * Segna come crawlata con dati
     */
    public function markCrawled(int $id, array $data): bool
    {
        $data['status'] = 'crawled';
        return $this->updateById($id, $data);
    }

    /**
     * Segna come errore
     */
    public function markError(int $id, ?string $reason = null): bool
    {
        $data = ['status' => 'error'];
        if ($reason) {
            $data['indexability_reason'] = $reason;
        }
        return $this->updateById($id, $data);
    }

    /**
     * Conta pagine per sessione
     */
    public function countBySession(int $sessionId, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE session_id = ?";
        $params = [$sessionId];

        if ($status !== null) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $row = Database::fetch($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Ottieni pagine per sessione con paginazione e filtri
     */
    public function getBySession(int $sessionId, array $filters = [], int $page = 1, int $perPage = 50, string $orderBy = 'id', string $orderDir = 'ASC'): array
    {
        $offset = ($page - 1) * $perPage;
        $where = ['session_id = ?'];
        $params = [$sessionId];

        if (!empty($filters['status_code'])) {
            if ($filters['status_code'] === '2xx') {
                $where[] = 'http_status >= 200 AND http_status < 300';
            } elseif ($filters['status_code'] === '3xx') {
                $where[] = 'http_status >= 300 AND http_status < 400';
            } elseif ($filters['status_code'] === '4xx') {
                $where[] = 'http_status >= 400 AND http_status < 500';
            } elseif ($filters['status_code'] === '5xx') {
                $where[] = 'http_status >= 500';
            }
        }

        if (isset($filters['is_indexable'])) {
            $where[] = 'is_indexable = ?';
            $params[] = (int) $filters['is_indexable'];
        }

        if (isset($filters['has_parameters'])) {
            $where[] = 'has_parameters = ?';
            $params[] = (int) $filters['has_parameters'];
        }

        if (!empty($filters['search'])) {
            $where[] = 'url LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['min_depth'])) {
            $where[] = 'depth >= ?';
            $params[] = (int) $filters['min_depth'];
        }

        $allowed = ['id', 'url', 'http_status', 'word_count', 'redirect_hops', 'depth', 'response_time_ms', 'internal_links_in'];
        if (!in_array($orderBy, $allowed)) {
            $orderBy = 'id';
        }
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        $whereStr = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->table} WHERE {$whereStr} ORDER BY {$orderBy} {$orderDir} LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        return Database::fetchAll($sql, $params);
    }

    /**
     * Conta pagine filtrate per sessione
     */
    public function countFiltered(int $sessionId, array $filters = []): int
    {
        $where = ['session_id = ?'];
        $params = [$sessionId];

        if (!empty($filters['status_code'])) {
            if ($filters['status_code'] === '2xx') {
                $where[] = 'http_status >= 200 AND http_status < 300';
            } elseif ($filters['status_code'] === '3xx') {
                $where[] = 'http_status >= 300 AND http_status < 400';
            } elseif ($filters['status_code'] === '4xx') {
                $where[] = 'http_status >= 400 AND http_status < 500';
            } elseif ($filters['status_code'] === '5xx') {
                $where[] = 'http_status >= 500';
            }
        }

        if (isset($filters['is_indexable'])) {
            $where[] = 'is_indexable = ?';
            $params[] = (int) $filters['is_indexable'];
        }

        if (isset($filters['has_parameters'])) {
            $where[] = 'has_parameters = ?';
            $params[] = (int) $filters['has_parameters'];
        }

        if (!empty($filters['search'])) {
            $where[] = 'url LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $row = Database::fetch("SELECT COUNT(*) as cnt FROM {$this->table} WHERE {$whereStr}", $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Aggiorna internal_links_in per tutte le pagine di una sessione
     */
    public function updateInternalLinksIn(int $sessionId): void
    {
        Database::execute(
            "UPDATE {$this->table} SET internal_links_in = 0 WHERE session_id = ?",
            [$sessionId]
        );

        Database::execute(
            "UPDATE {$this->table} p1
             SET p1.internal_links_in = (
                 SELECT COUNT(*) FROM (SELECT discovered_from FROM {$this->table} WHERE session_id = ?) p2
                 WHERE p2.discovered_from = p1.url
             )
             WHERE p1.session_id = ?",
            [$sessionId, $sessionId]
        );
    }

    /**
     * Trova pagine con titoli duplicati nella sessione
     */
    public function getDuplicateTitles(int $sessionId): array
    {
        return Database::fetchAll(
            "SELECT title, COUNT(*) as cnt, GROUP_CONCAT(url SEPARATOR '|||') as urls
             FROM {$this->table}
             WHERE session_id = ? AND title IS NOT NULL AND title != '' AND status = 'crawled'
             GROUP BY title
             HAVING cnt > 1
             ORDER BY cnt DESC",
            [$sessionId]
        );
    }

    /**
     * Distribuzione status code per sessione
     */
    public function getStatusDistribution(int $sessionId): array
    {
        return Database::fetchAll(
            "SELECT
                CASE
                    WHEN http_status >= 200 AND http_status < 300 THEN '2xx'
                    WHEN http_status >= 300 AND http_status < 400 THEN '3xx'
                    WHEN http_status >= 400 AND http_status < 500 THEN '4xx'
                    WHEN http_status >= 500 THEN '5xx'
                    ELSE 'other'
                END as status_group,
                COUNT(*) as cnt
             FROM {$this->table}
             WHERE session_id = ? AND status = 'crawled'
             GROUP BY status_group
             ORDER BY status_group",
            [$sessionId]
        );
    }

    /**
     * Top redirect chains piu lunghe
     */
    public function getTopRedirectChains(int $sessionId, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT url, redirect_chain, redirect_hops, redirect_target, http_status
             FROM {$this->table}
             WHERE session_id = ? AND redirect_hops > 0
             ORDER BY redirect_hops DESC
             LIMIT ?",
            [$sessionId, $limit]
        );
    }

    /**
     * Elimina pagine per progetto
     */
    public function deleteByProject(int $projectId): int
    {
        return Database::delete($this->table, 'project_id = ?', [$projectId]);
    }
}
