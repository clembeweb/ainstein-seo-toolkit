<?php

namespace Modules\AiContent\Models;

use Core\Database;

/**
 * MetaTag Model
 *
 * Gestisce i meta tag SEO per i progetti ai-content.
 * Workflow: Import → Scrape → Generate → Approve → Publish
 */
class MetaTag
{
    private $db;
    private string $table = 'aic_meta_tags';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Trova meta tag per ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Trova meta tag per ID con verifica progetto
     */
    public function findByProject(int $id, int $projectId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE id = ? AND project_id = ?
        ");
        $stmt->execute([$id, $projectId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Lista tutti i meta tag di un progetto
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
     * Lista meta tag paginati con filtri
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

        // Filtro search
        if (!empty($filters['search'])) {
            $where[] = "(url LIKE ? OR original_title LIKE ? OR generated_title LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Filtro has_generated (ha meta generati)
        if (isset($filters['has_generated']) && $filters['has_generated'] !== '') {
            if ($filters['has_generated']) {
                $where[] = "generated_title IS NOT NULL";
            } else {
                $where[] = "generated_title IS NULL";
            }
        }

        $whereClause = implode(' AND ', $where);

        // Count totale
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch con LIMIT/OFFSET
        $params[] = $perPage;
        $params[] = $offset;

        $sql = "SELECT * FROM {$this->table}
                WHERE {$whereClause}
                ORDER BY created_at DESC
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
     * Conta meta tag per progetto
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
     * Statistiche meta tag per progetto
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
            'published' => (int) ($stats['published'] ?? 0),
            'errors' => (int) ($stats['errors'] ?? 0),
        ];
    }

    /**
     * Inserisce singolo meta tag
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (project_id, user_id, url, original_title, original_h1,
             current_meta_title, current_meta_desc, wp_site_id, wp_post_id, wp_post_type, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['project_id'],
            $data['user_id'],
            $data['url'],
            $data['original_title'] ?? null,
            $data['original_h1'] ?? null,
            $data['current_meta_title'] ?? null,
            $data['current_meta_desc'] ?? null,
            $data['wp_site_id'] ?? null,
            $data['wp_post_id'] ?? null,
            $data['wp_post_type'] ?? 'post',
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
                (project_id, user_id, url, status)
                VALUES (?, ?, ?, 'pending')";
        $stmt = $this->db->prepare($sql);

        $inserted = 0;
        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $stmt->execute([$projectId, $userId, $url]);
            if ($stmt->rowCount() > 0) {
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Inserimento bulk da WordPress
     */
    public function addBulkFromWp(int $projectId, int $userId, int $wpSiteId, array $posts): int
    {
        $sql = "INSERT IGNORE INTO {$this->table}
                (project_id, user_id, url, original_title, current_meta_title, current_meta_desc,
                 wp_site_id, wp_post_id, wp_post_type, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $this->db->prepare($sql);

        $inserted = 0;
        foreach ($posts as $post) {
            if (empty($post['url'])) {
                continue;
            }
            $stmt->execute([
                $projectId,
                $userId,
                $post['url'],
                $post['title'] ?? null,
                $post['seo_title'] ?? null,
                $post['seo_description'] ?? null,
                $wpSiteId,
                $post['id'] ?? null,
                $post['post_type'] ?? 'post',
            ]);
            if ($stmt->rowCount() > 0) {
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Aggiorna meta tag
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'url', 'original_title', 'original_h1',
            'current_meta_title', 'current_meta_desc',
            'generated_title', 'generated_desc',
            'scraped_content', 'scraped_word_count',
            'status', 'scrape_error', 'generation_error', 'publish_error',
            'scraped_at', 'generated_at', 'published_at',
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
            SET original_title = ?,
                original_h1 = ?,
                current_meta_title = ?,
                current_meta_desc = ?,
                scraped_content = ?,
                scraped_word_count = ?,
                status = 'scraped',
                scrape_error = NULL,
                scraped_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['original_title'] ?? null,
            $data['original_h1'] ?? null,
            $data['current_meta_title'] ?? null,
            $data['current_meta_desc'] ?? null,
            $data['scraped_content'] ?? null,
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
     * Aggiorna dati generazione AI
     */
    public function updateGeneratedData(int $id, string $title, string $desc): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET generated_title = ?,
                generated_desc = ?,
                status = 'generated',
                generation_error = NULL,
                generated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$title, $desc, $id]);
    }

    /**
     * Segna errore generazione
     */
    public function markGenerationError(int $id, string $error): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'error', generation_error = ?
            WHERE id = ?
        ");
        return $stmt->execute([$error, $id]);
    }

    /**
     * Approva meta tag (singolo)
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
            SET status = 'published', publish_error = NULL, published_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /**
     * Segna errore pubblicazione
     */
    public function markPublishError(int $id, string $error): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'error', publish_error = ?
            WHERE id = ?
        ");
        return $stmt->execute([$error, $id]);
    }

    /**
     * Elimina meta tag
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Elimina meta tag bulk
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
     * Elimina tutti i meta tag di un progetto
     */
    public function deleteByProject(int $projectId): int
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE project_id = ?");
        $stmt->execute([$projectId]);
        return $stmt->rowCount();
    }

    /**
     * Ottieni prossimi meta tag da scrapare
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
     * Ottieni prossimi meta tag da generare (scrappati ma non generati)
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
     * Ottieni meta tag approvati da pubblicare
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
     * Reset errori a pending (per retry scraping)
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
            SET status = 'scraped', generation_error = NULL
            WHERE project_id = ? AND status = 'error' AND generation_error IS NOT NULL
        ");
        $stmt->execute([$projectId]);
        return $stmt->rowCount();
    }
}
