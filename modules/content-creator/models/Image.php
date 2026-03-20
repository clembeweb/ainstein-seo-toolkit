<?php

namespace Modules\ContentCreator\Models;

use Core\Database;

/**
 * Image Model for Content Creator Module
 *
 * Gestisce la tabella cc_images per il tracking dei prodotti
 * e le loro immagini sorgente per la generazione AI.
 */
class Image
{
    protected string $table = 'cc_images';

    // Status constants (state machine)
    public const STATUS_PENDING = 'pending';
    public const STATUS_SOURCE_ACQUIRED = 'source_acquired';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ERROR = 'error';

    // Category constants
    public const CATEGORY_FASHION = 'fashion';
    public const CATEGORY_HOME = 'home';
    public const CATEGORY_CUSTOM = 'custom';

    // Source type constants
    public const SOURCE_CMS = 'cms';
    public const SOURCE_UPLOAD = 'upload';
    public const SOURCE_URL = 'url';

    public function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public function findByProject(int $id, int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND project_id = ?",
            [$id, $projectId]
        ) ?: null;
    }

    public function getPaginated(int $projectId, int $page = 1, int $perPage = 25, array $filters = []): array
    {
        $where = "WHERE i.project_id = ?";
        $params = [$projectId];

        if (!empty($filters['status'])) {
            $where .= " AND i.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where .= " AND (i.product_name LIKE ? OR i.sku LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['category'])) {
            $where .= " AND i.category = ?";
            $params[] = $filters['category'];
        }

        $total = (int) Database::fetch(
            "SELECT COUNT(*) as cnt FROM {$this->table} i {$where}",
            $params
        )['cnt'];

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $sortCol = $filters['sort'] ?? 'created_at';
        $sortDir = strtoupper($filters['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $allowedSort = ['product_name', 'sku', 'category', 'status', 'created_at'];
        if (!in_array($sortCol, $allowedSort)) {
            $sortCol = 'created_at';
        }

        $data = Database::fetchAll(
            "SELECT i.*,
                    COUNT(v.id) as variant_count,
                    SUM(CASE WHEN v.is_approved = 1 THEN 1 ELSE 0 END) as approved_count
             FROM {$this->table} i
             LEFT JOIN cc_image_variants v ON v.image_id = i.id
             {$where}
             GROUP BY i.id
             ORDER BY i.{$sortCol} {$sortDir}
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ];
    }

    public function getStats(int $projectId): array
    {
        $rows = Database::fetchAll(
            "SELECT status, COUNT(*) as cnt FROM {$this->table} WHERE project_id = ? GROUP BY status",
            [$projectId]
        );

        $stats = [
            'total' => 0,
            'pending' => 0,
            'source_acquired' => 0,
            'generated' => 0,
            'approved' => 0,
            'published' => 0,
            'error' => 0,
        ];

        foreach ($rows as $row) {
            $stats[$row['status']] = (int) $row['cnt'];
            $stats['total'] += (int) $row['cnt'];
        }

        return $stats;
    }

    public function getNextForGeneration(int $projectId, int $limit = 1): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND status = ?
             ORDER BY id ASC LIMIT ?",
            [$projectId, self::STATUS_SOURCE_ACQUIRED, $limit]
        );
    }

    public function countReadyForGeneration(int $projectId): int
    {
        return (int) Database::fetch(
            "SELECT COUNT(*) as cnt FROM {$this->table}
             WHERE project_id = ? AND status = ?",
            [$projectId, self::STATUS_SOURCE_ACQUIRED]
        )['cnt'];
    }

    public function getApproved(int $projectId, int $limit = 1000): array
    {
        return Database::fetchAll(
            "SELECT i.*, COUNT(v.id) as approved_variants
             FROM {$this->table} i
             JOIN cc_image_variants v ON v.image_id = i.id AND v.is_approved = 1
             WHERE i.project_id = ? AND i.status IN (?, ?)
             GROUP BY i.id
             ORDER BY i.id ASC LIMIT ?",
            [$projectId, self::STATUS_APPROVED, self::STATUS_PUBLISHED, $limit]
        );
    }

    public function create(array $data): int
    {
        $allowed = [
            'project_id', 'user_id', 'product_url', 'sku', 'product_name',
            'category', 'source_image_path', 'source_image_url', 'source_type',
            'connector_id', 'cms_entity_id', 'cms_entity_type',
            'generation_settings', 'status', 'error_message',
        ];

        $filtered = array_intersect_key($data, array_flip($allowed));
        $columns = implode(', ', array_keys($filtered));
        $placeholders = implode(', ', array_fill(0, count($filtered), '?'));

        Database::execute(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})",
            array_values($filtered)
        );

        return (int) Database::lastInsertId();
    }

    public function addBulk(int $projectId, int $userId, array $items): int
    {
        if (empty($items)) return 0;

        $inserted = 0;
        foreach ($items as $item) {
            try {
                $this->create(array_merge($item, [
                    'project_id' => $projectId,
                    'user_id' => $userId,
                ]));
                $inserted++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $inserted;
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'product_url', 'sku', 'product_name', 'category',
            'source_image_path', 'source_image_url', 'source_type',
            'generation_settings', 'status', 'error_message',
        ];

        $filtered = array_intersect_key($data, array_flip($allowed));
        if (empty($filtered)) return false;

        $sets = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($filtered)));
        $params = array_values($filtered);
        $params[] = $id;

        return Database::execute(
            "UPDATE {$this->table} SET {$sets} WHERE id = ?",
            $params
        ) !== false;
    }

    public function updateStatus(int $id, string $newStatus): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET status = ? WHERE id = ?",
            [$newStatus, $id]
        ) !== false;
    }

    public function approve(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET status = ? WHERE id = ? AND status = ?",
            [self::STATUS_APPROVED, $id, self::STATUS_GENERATED]
        ) !== false;
    }

    public function unapprove(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET status = ? WHERE id = ? AND status = ?",
            [self::STATUS_GENERATED, $id, self::STATUS_APPROVED]
        ) !== false;
    }

    public function markPublished(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET status = ? WHERE id = ? AND status = ?",
            [self::STATUS_PUBLISHED, $id, self::STATUS_APPROVED]
        ) !== false;
    }

    public function markError(int $id, string $error): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET status = ?, error_message = ? WHERE id = ?",
            [self::STATUS_ERROR, $error, $id]
        ) !== false;
    }

    public function delete(int $id): bool
    {
        return Database::execute(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        ) !== false;
    }

    public function deleteBulk(array $ids, int $projectId): int
    {
        if (empty($ids)) return 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, [$projectId]);

        $stmt = Database::execute(
            "DELETE FROM {$this->table} WHERE id IN ({$placeholders}) AND project_id = ?",
            $params
        );

        return $stmt ? $stmt->rowCount() : 0;
    }

    public function approveBulk(array $ids, int $projectId): int
    {
        if (empty($ids)) return 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge(
            [self::STATUS_APPROVED, self::STATUS_GENERATED],
            $ids,
            [$projectId]
        );

        $stmt = Database::execute(
            "UPDATE {$this->table} SET status = ?
             WHERE status = ? AND id IN ({$placeholders}) AND project_id = ?
             AND id IN (SELECT image_id FROM cc_image_variants WHERE is_approved = 1)",
            $params
        );

        return $stmt ? $stmt->rowCount() : 0;
    }

    public function hasApprovedVariants(int $id): bool
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM cc_image_variants WHERE image_id = ? AND is_approved = 1",
            [$id]
        );
        return (int) ($result['cnt'] ?? 0) > 0;
    }

    public static function extractFilename(string $path): string
    {
        return basename($path);
    }

    public function findByFilename(string $filename): ?array
    {
        if (preg_match('/^(\d+)_/', $filename, $matches)) {
            return $this->find((int) $matches[1]);
        }
        return null;
    }
}
