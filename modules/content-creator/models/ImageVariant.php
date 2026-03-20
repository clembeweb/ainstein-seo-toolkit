<?php

namespace Modules\ContentCreator\Models;

use Core\Database;

/**
 * ImageVariant Model for Content Creator Module
 *
 * Gestisce la tabella cc_image_variants per le varianti
 * generate dall'AI per ogni immagine prodotto.
 */
class ImageVariant
{
    protected string $table = 'cc_image_variants';

    public function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public function findByImage(int $id, int $imageId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND image_id = ?",
            [$id, $imageId]
        ) ?: null;
    }

    public function getByImage(int $imageId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE image_id = ? ORDER BY variant_number ASC",
            [$imageId]
        );
    }

    public function getApprovedByImage(int $imageId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE image_id = ? AND is_approved = 1 ORDER BY variant_number ASC",
            [$imageId]
        );
    }

    public function getApprovedByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT v.*, i.product_name, i.sku, i.product_url, i.category,
                    i.cms_entity_id, i.cms_entity_type, i.connector_id
             FROM {$this->table} v
             JOIN cc_images i ON i.id = v.image_id
             WHERE i.project_id = ? AND v.is_approved = 1
             ORDER BY i.id ASC, v.variant_number ASC",
            [$projectId]
        );
    }

    public function countApprovedByProject(int $projectId): int
    {
        return (int) Database::fetch(
            "SELECT COUNT(*) as cnt
             FROM {$this->table} v
             JOIN cc_images i ON i.id = v.image_id
             WHERE i.project_id = ? AND v.is_approved = 1",
            [$projectId]
        )['cnt'];
    }

    public function create(array $data): int
    {
        $allowed = [
            'image_id', 'variant_number', 'image_path', 'prompt_used',
            'revised_prompt', 'provider_used', 'is_approved', 'is_pushed',
            'file_size_bytes', 'generation_time_ms',
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

    public function approve(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET is_approved = 1 WHERE id = ?",
            [$id]
        ) !== false;
    }

    public function reject(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET is_approved = 0 WHERE id = ?",
            [$id]
        ) !== false;
    }

    public function markPushed(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET is_pushed = 1 WHERE id = ?",
            [$id]
        ) !== false;
    }

    public function markPushError(int $id, string $error): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET cms_sync_error = ? WHERE id = ?",
            [$error, $id]
        ) !== false;
    }

    public function deleteByImage(int $imageId): int
    {
        $stmt = Database::execute(
            "DELETE FROM {$this->table} WHERE image_id = ?",
            [$imageId]
        );
        return $stmt ? $stmt->rowCount() : 0;
    }

    public function delete(int $id): bool
    {
        return Database::execute(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        ) !== false;
    }

    public function getNextVariantNumber(int $imageId): int
    {
        $result = Database::fetch(
            "SELECT COALESCE(MAX(variant_number), 0) + 1 as next_num FROM {$this->table} WHERE image_id = ?",
            [$imageId]
        );
        return (int) ($result['next_num'] ?? 1);
    }

    public function countApproved(int $imageId): int
    {
        return (int) Database::fetch(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE image_id = ? AND is_approved = 1",
            [$imageId]
        )['cnt'];
    }

    public function findByFilename(string $filename): ?array
    {
        if (preg_match('/^(\d+)_v(\d+)_/', $filename, $matches)) {
            return Database::fetch(
                "SELECT * FROM {$this->table} WHERE image_id = ? AND variant_number = ?",
                [(int) $matches[1], (int) $matches[2]]
            ) ?: null;
        }
        return null;
    }
}
