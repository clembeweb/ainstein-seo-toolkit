<?php

namespace Modules\AiContent\Models;

use Core\Database;

/**
 * InternalLinksPool Model
 *
 * Gestisce il pool di link interni per i progetti ai-content.
 * I link vengono importati da sitemap e usati dall'AI per inserire
 * link contestuali negli articoli generati.
 */
class InternalLinksPool
{
    private $db;
    private string $table = 'aic_internal_links_pool';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Trova link per ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Trova link per ID con verifica progetto
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
     * Lista tutti i link di un progetto
     */
    public function getByProject(int $projectId, ?string $status = null, int $limit = 500): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($status) {
            $sql .= " AND scrape_status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lista link paginati con filtri
     */
    public function getPaginated(int $projectId, int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [$projectId];
        $where = ["project_id = ?"];

        // Filtro status
        if (!empty($filters['status'])) {
            $where[] = "scrape_status = ?";
            $params[] = $filters['status'];
        }

        // Filtro search
        if (!empty($filters['search'])) {
            $where[] = "(url LIKE ? OR title LIKE ? OR description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Filtro attivo
        if (isset($filters['active']) && $filters['active'] !== '') {
            $where[] = "is_active = ?";
            $params[] = (int) $filters['active'];
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
     * Lista link attivi di un progetto (per l'AI)
     */
    public function getActiveByProject(int $projectId, int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT id, url, title, description
            FROM {$this->table}
            WHERE project_id = ?
              AND is_active = 1
              AND scrape_status = 'completed'
              AND title IS NOT NULL
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$projectId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Formatta i link per il prompt AI
     * Restituisce una stringa formattata per essere inclusa nel prompt
     */
    public function getForPrompt(int $projectId, int $limit = 50): string
    {
        $links = $this->getActiveByProject($projectId, $limit);

        if (empty($links)) {
            return "";
        }

        $output = "## POOL LINK INTERNI DISPONIBILI\n";
        $output .= "Inserisci 2-4 di questi link in modo naturale nel testo, usando anchor text descrittivi e contestuali:\n\n";

        foreach ($links as $link) {
            $title = $link['title'] ?? 'Pagina senza titolo';
            $desc = !empty($link['description'])
                ? mb_substr($link['description'], 0, 100) . (mb_strlen($link['description']) > 100 ? '...' : '')
                : '';

            $output .= "- **{$title}**\n";
            $output .= "  URL: {$link['url']}\n";
            if ($desc) {
                $output .= "  Descrizione: {$desc}\n";
            }
            $output .= "\n";
        }

        return $output;
    }

    /**
     * Conta link per progetto
     */
    public function countByProject(int $projectId, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($status) {
            $sql .= " AND scrape_status = ?";
            $params[] = $status;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Conta link attivi per progetto
     */
    public function countActive(int $projectId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM {$this->table}
            WHERE project_id = ? AND is_active = 1
        ");
        $stmt->execute([$projectId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Statistiche pool per progetto
     */
    public function getStats(int $projectId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total,
                SUM(scrape_status = 'pending') as pending,
                SUM(scrape_status = 'completed') as completed,
                SUM(scrape_status = 'error') as errors,
                SUM(is_active = 1) as active,
                SUM(is_active = 0) as inactive
            FROM {$this->table}
            WHERE project_id = ?
        ");
        $stmt->execute([$projectId]);
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'total' => (int) ($stats['total'] ?? 0),
            'pending' => (int) ($stats['pending'] ?? 0),
            'completed' => (int) ($stats['completed'] ?? 0),
            'errors' => (int) ($stats['errors'] ?? 0),
            'active' => (int) ($stats['active'] ?? 0),
            'inactive' => (int) ($stats['inactive'] ?? 0),
        ];
    }

    /**
     * Inserisce singolo link
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (project_id, url, title, description, sitemap_source, scrape_status, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['project_id'],
            $data['url'],
            $data['title'] ?? null,
            $data['description'] ?? null,
            $data['sitemap_source'] ?? null,
            $data['scrape_status'] ?? 'pending',
            $data['is_active'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Inserimento bulk di URL
     * Ignora duplicati (UNIQUE su project_id + url)
     */
    public function addBulk(int $projectId, array $urls, ?string $sitemapSource = null): int
    {
        $sql = "INSERT IGNORE INTO {$this->table}
                (project_id, url, sitemap_source, scrape_status, is_active)
                VALUES (?, ?, ?, 'pending', 1)";
        $stmt = $this->db->prepare($sql);

        $inserted = 0;
        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $stmt->execute([$projectId, $url, $sitemapSource]);
            if ($stmt->rowCount() > 0) {
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Aggiorna link
     */
    public function update(int $id, array $data): bool
    {
        $sets = [];
        $params = [];

        foreach (['url', 'title', 'description', 'is_active'] as $field) {
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
            SET title = ?, description = ?, scrape_status = 'completed', scraped_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'] ?? null,
            $data['description'] ?? null,
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
            SET scrape_status = 'error', scrape_error = ?, scraped_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$error, $id]);
    }

    /**
     * Toggle stato attivo
     */
    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET is_active = NOT is_active
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /**
     * Attiva/disattiva bulk
     */
    public function setActiveBulk(array $ids, bool $active): int
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$active ? 1 : 0], $ids);

        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET is_active = ?
            WHERE id IN ({$placeholders})
        ");
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Elimina link
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Elimina link bulk
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
     * Elimina tutti i link di un progetto
     */
    public function deleteByProject(int $projectId): int
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE project_id = ?");
        $stmt->execute([$projectId]);
        return $stmt->rowCount();
    }

    /**
     * Ottieni prossimi link da scrapare
     */
    public function getNextPending(int $projectId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE project_id = ? AND scrape_status = 'pending'
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
     * Ricerca link per URL o titolo
     */
    public function search(int $projectId, string $query, int $limit = 50): array
    {
        $searchTerm = '%' . $query . '%';
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE project_id = ?
              AND (url LIKE ? OR title LIKE ? OR description LIKE ?)
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$projectId, $searchTerm, $searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Reset tutti gli errori a pending (per retry)
     */
    public function resetErrors(int $projectId): int
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET scrape_status = 'pending', scrape_error = NULL
            WHERE project_id = ? AND scrape_status = 'error'
        ");
        $stmt->execute([$projectId]);
        return $stmt->rowCount();
    }
}
