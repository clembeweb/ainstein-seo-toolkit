<?php

namespace Modules\ContentCreator\Models;

use Core\Database;

/**
 * Url Model for Content Creator Module
 *
 * Gestisce le URL da processare per i progetti content-creator.
 * Workflow: Import -> Scrape -> Generate -> Approve/Reject -> Publish
 */
class Url
{
    private $db;
    private string $table = 'cc_urls';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Trova URL per ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Trova URL per ID con verifica progetto
     */
    public function findByProject(int $id, int $projectId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE id = ? AND project_id = ?"
        );
        $stmt->execute([$id, $projectId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Trova URL per ID con verifica utente
     */
    public function findByUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$id, $userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Lista URL di un progetto
     */
    public function getByProject(int $projectId, ?string $status = null, int $limit = 500): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lista URL paginati con filtri
     */
    public function getPaginated(int $projectId, int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [$projectId];
        $where = ["project_id = ?"];

        // Filtro status
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        // Filtro search (URL, keyword, slug, meta title generato)
        if (!empty($filters['search'])) {
            $where[] = "(url LIKE ? OR keyword LIKE ? OR slug LIKE ? OR ai_meta_title LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Filtro categoria
        if (!empty($filters['category'])) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }

        $whereClause = implode(' AND ', $where);

        // Count totale
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Ordinamento
        $allowedSort = ['url', 'slug', 'keyword', 'category', 'scraped_title', 'ai_meta_title', 'status', 'created_at', 'scraped_word_count'];
        $sortBy = in_array($filters['sort'] ?? '', $allowedSort) ? $filters['sort'] : 'created_at';
        $sortDir = (strtolower($filters['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC';

        // Fetch con LIMIT/OFFSET
        $params[] = $perPage;
        $params[] = $offset;

        $sql = "SELECT * FROM {$this->table}
                WHERE {$whereClause}
                ORDER BY {$sortBy} {$sortDir}
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $lastPage = (int) ceil($total / $perPage);

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage ?: 1,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * Conta URL per progetto
     */
    public function countByProject(int $projectId, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Statistiche URL per progetto
     */
    public function getStats(int $projectId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total,
                SUM(status = 'pending') as pending,
                SUM(status = 'scraped') as scraped,
                SUM(status = 'generated') as `generated`,
                SUM(status = 'approved') as approved,
                SUM(status = 'rejected') as rejected,
                SUM(status = 'published') as published,
                SUM(status = 'error') as errors
            FROM {$this->table}
            WHERE project_id = ?
        ");
        $stmt->execute([$projectId]);
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'total' => (int) ($stats['total'] ?? 0),
            'pending' => (int) ($stats['pending'] ?? 0),
            'scraped' => (int) ($stats['scraped'] ?? 0),
            'generated' => (int) ($stats['generated'] ?? 0),
            'approved' => (int) ($stats['approved'] ?? 0),
            'rejected' => (int) ($stats['rejected'] ?? 0),
            'published' => (int) ($stats['published'] ?? 0),
            'errors' => (int) ($stats['errors'] ?? 0),
        ];
    }

    /**
     * Inserisce singola URL
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (project_id, user_id, url, slug, keyword, category,
             connector_id, cms_entity_id, cms_entity_type, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['project_id'],
            $data['user_id'],
            $data['url'],
            $data['slug'] ?? self::extractSlug($data['url']),
            $data['keyword'] ?? null,
            $data['category'] ?? null,
            $data['connector_id'] ?? null,
            $data['cms_entity_id'] ?? null,
            $data['cms_entity_type'] ?? null,
            $data['status'] ?? 'pending',
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Inserimento bulk di URL
     * Ignora duplicati (UNIQUE su project_id + url)
     */
    public function addBulk(int $projectId, int $userId, array $urls): int
    {
        $sql = "INSERT IGNORE INTO {$this->table}
                (project_id, user_id, url, slug, keyword, category, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $this->db->prepare($sql);

        $inserted = 0;
        foreach ($urls as $urlData) {
            // Supporta sia array di stringhe che array di array
            if (is_string($urlData)) {
                $url = trim($urlData);
                $keyword = null;
                $category = null;
            } else {
                $url = trim($urlData['url'] ?? '');
                $keyword = $urlData['keyword'] ?? null;
                $category = $urlData['category'] ?? null;
            }

            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $slug = self::extractSlug($url);
            $stmt->execute([$projectId, $userId, $url, $slug, $keyword, $category]);
            if ($stmt->rowCount() > 0) {
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Inserimento bulk da CMS (con entity_id)
     */
    public function addBulkFromCms(int $projectId, int $userId, int $connectorId, array $items): int
    {
        $sql = "INSERT IGNORE INTO {$this->table}
                (project_id, user_id, url, slug, keyword, category,
                 connector_id, cms_entity_id, cms_entity_type, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $this->db->prepare($sql);

        $inserted = 0;
        foreach ($items as $item) {
            $url = trim($item['url'] ?? '');
            if (empty($url)) {
                continue;
            }

            $slug = self::extractSlug($url);
            $stmt->execute([
                $projectId,
                $userId,
                $url,
                $slug,
                $item['keyword'] ?? $item['name'] ?? null,
                $item['category'] ?? null,
                $connectorId,
                $item['entity_id'] ?? null,
                $item['entity_type'] ?? 'product',
            ]);
            if ($stmt->rowCount() > 0) {
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Aggiorna URL (whitelist campi)
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'url', 'slug', 'keyword', 'category',
            'scraped_title', 'scraped_h1', 'scraped_meta_title', 'scraped_meta_description',
            'scraped_content', 'scraped_price', 'scraped_word_count',
            'ai_meta_title', 'ai_meta_description', 'ai_page_description',
            'status', 'scrape_error', 'ai_error',
            'scraped_at', 'ai_generated_at',
            'connector_id', 'cms_entity_id', 'cms_entity_type',
            'cms_synced_at', 'cms_sync_error',
        ];

        $sets = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Aggiorna dati scraping
     */
    public function updateScrapeData(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET scraped_title = ?,
                scraped_h1 = ?,
                scraped_meta_title = ?,
                scraped_meta_description = ?,
                scraped_content = ?,
                scraped_price = ?,
                scraped_word_count = ?,
                status = 'scraped',
                scrape_error = NULL,
                scraped_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['scraped_title'] ?? null,
            $data['scraped_h1'] ?? null,
            $data['scraped_meta_title'] ?? null,
            $data['scraped_meta_description'] ?? null,
            $data['scraped_content'] ?? null,
            $data['scraped_price'] ?? null,
            $data['scraped_word_count'] ?? null,
            $id
        ]);
    }

    /**
     * Segna errore scraping
     */
    public function markScrapeError(int $id, string $error): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'error', scrape_error = ?, scraped_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$error, $id]);
    }

    /**
     * Aggiorna dati generazione AI (3 campi: meta_title, meta_description, page_description)
     */
    public function updateGeneratedData(int $id, string $title, string $desc, string $pageDesc): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET ai_meta_title = ?,
                ai_meta_description = ?,
                ai_page_description = ?,
                status = 'generated',
                ai_error = NULL,
                ai_generated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$title, $desc, $pageDesc, $id]);
    }

    /**
     * Segna errore generazione AI
     */
    public function markGenerationError(int $id, string $error): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'error', ai_error = ?
            WHERE id = ?
        ");
        return $stmt->execute([$error, $id]);
    }

    /**
     * Approva URL (singola)
     */
    public function approve(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'approved'
            WHERE id = ? AND status IN ('generated', 'error')
        ");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Rifiuta URL (singola)
     */
    public function reject(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'rejected'
            WHERE id = ? AND status IN ('generated', 'approved', 'error')
        ");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Approva bulk
     */
    public function approveBulk(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'approved'
            WHERE id IN ({$placeholders}) AND status IN ('generated', 'error')
        ");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    /**
     * Segna come pubblicato
     */
    public function markPublished(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'published', cms_sync_error = NULL, cms_synced_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /**
     * Segna errore pubblicazione CMS
     */
    public function markPublishError(int $id, string $error): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'error', cms_sync_error = ?
            WHERE id = ?
        ");
        return $stmt->execute([$error, $id]);
    }

    /**
     * Elimina URL
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Elimina URL bulk
     */
    public function deleteBulk(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    /**
     * Elimina tutte le URL di un progetto
     */
    public function deleteByProject(int $projectId): int
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE project_id = ?");
        $stmt->execute([$projectId]);
        return $stmt->rowCount();
    }

    /**
     * Ottieni prossime URL da scrapare
     */
    public function getNextPending(int $projectId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE project_id = ? AND status = 'pending'
            ORDER BY created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$projectId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Ottieni prossime URL scrappate da generare
     */
    public function getNextScraped(int $projectId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE project_id = ? AND status = 'scraped'
            ORDER BY created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$projectId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Ottieni URL approvate da pubblicare su CMS
     */
    public function getApproved(int $projectId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE project_id = ? AND status = 'approved'
            ORDER BY created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$projectId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifica se URL esiste gia per progetto
     */
    public function urlExists(int $projectId, string $url): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM {$this->table}
            WHERE project_id = ? AND url = ?
            LIMIT 1
        ");
        $stmt->execute([$projectId, $url]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Reset errori scraping a pending (per retry)
     */
    public function resetScrapeErrors(int $projectId): int
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'pending', scrape_error = NULL
            WHERE project_id = ? AND status = 'error' AND scrape_error IS NOT NULL
        ");
        $stmt->execute([$projectId]);
        return $stmt->rowCount();
    }

    /**
     * Reset errori generazione a scraped (per retry)
     */
    public function resetGenerationErrors(int $projectId): int
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'scraped', ai_error = NULL
            WHERE project_id = ? AND status = 'error' AND ai_error IS NOT NULL
        ");
        $stmt->execute([$projectId]);
        return $stmt->rowCount();
    }

    /**
     * Estrai slug dall'URL
     */
    public static function extractSlug(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = rtrim($path ?? '', '/');
        $segments = explode('/', $path);
        return end($segments) ?: '';
    }
}
