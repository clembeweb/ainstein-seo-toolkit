# Chunk 1: Database Migration + Models

> **Parent plan:** [Plan Index](./2026-03-12-image-generation-plan-index.md)
> **Design spec:** [Design Spec](./2026-03-12-content-creator-image-generation-design.md) — Sections 1, 2

---

## Task 1: Database Migration

**Files:**
- Create: `modules/content-creator/database/migration-images.sql`

- [ ] **Step 1: Write the migration SQL**

```sql
-- Content Creator — Image Generation Tables
-- Date: 2026-03-12

-- 1. Images (products to generate variants for)
CREATE TABLE IF NOT EXISTS cc_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    product_url VARCHAR(2048) DEFAULT NULL COMMENT 'URL pagina prodotto sul sito e-commerce',
    sku VARCHAR(100) DEFAULT NULL,
    product_name VARCHAR(500) NOT NULL,
    category ENUM('fashion', 'home', 'custom') NOT NULL DEFAULT 'fashion',
    source_image_path VARCHAR(500) DEFAULT NULL,
    source_image_url VARCHAR(2048) DEFAULT NULL,
    source_type ENUM('cms', 'upload', 'url') NOT NULL DEFAULT 'upload',
    connector_id INT UNSIGNED DEFAULT NULL,
    cms_entity_id VARCHAR(100) DEFAULT NULL,
    cms_entity_type VARCHAR(50) DEFAULT NULL,
    generation_settings JSON DEFAULT NULL COMMENT 'Override preset per-item, null = usa default progetto',
    status ENUM('pending', 'source_acquired', 'generated', 'approved', 'published', 'error') NOT NULL DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project_status (project_id, status),
    INDEX idx_user (user_id),
    FOREIGN KEY (project_id) REFERENCES cc_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Image variants (AI-generated images per product)
CREATE TABLE IF NOT EXISTS cc_image_variants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_id INT UNSIGNED NOT NULL,
    variant_number TINYINT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    prompt_used TEXT DEFAULT NULL,
    revised_prompt TEXT DEFAULT NULL,
    provider_used VARCHAR(50) DEFAULT NULL COMMENT 'gemini, fashn, stability — per quality tracking',
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    is_pushed TINYINT(1) NOT NULL DEFAULT 0,
    cms_sync_error TEXT DEFAULT NULL,
    file_size_bytes INT UNSIGNED DEFAULT NULL,
    generation_time_ms INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_image_id (image_id),
    UNIQUE KEY uq_image_variant (image_id, variant_number),
    FOREIGN KEY (image_id) REFERENCES cc_images(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Extend cc_jobs with image types
ALTER TABLE cc_jobs MODIFY COLUMN type ENUM('scrape','generate','cms_push','image_generate','image_push') NOT NULL DEFAULT 'scrape';
```

- [ ] **Step 2: Run migration locally**

```bash
cd /c/laragon/www/seo-toolkit
/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe -u root seo_toolkit < modules/content-creator/database/migration-images.sql
```

Expected: no errors. Verify:
```bash
/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe -u root seo_toolkit -e "DESCRIBE cc_images;"
/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe -u root seo_toolkit -e "DESCRIBE cc_image_variants;"
/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe -u root seo_toolkit -e "SHOW COLUMNS FROM cc_jobs LIKE 'type';"
```

- [ ] **Step 3: Create storage directories**

```bash
mkdir -p /c/laragon/www/seo-toolkit/storage/images/sources
mkdir -p /c/laragon/www/seo-toolkit/storage/images/generated
```

- [ ] **Step 4: Commit**

```bash
git add modules/content-creator/database/migration-images.sql
git commit -m "feat(content-creator): add cc_images + cc_image_variants tables, extend cc_jobs ENUM"
```

---

## Task 2: Image Model

**Files:**
- Create: `modules/content-creator/models/Image.php`

**Reference:** `modules/content-creator/models/Url.php` — follow same patterns (findByProject, getPaginated, getStats, bulk ops, etc.)

- [ ] **Step 1: Write Image model**

```php
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

    /**
     * Find image by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        ) ?: null;
    }

    /**
     * Find image by ID with project validation
     */
    public function findByProject(int $id, int $projectId): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ? AND project_id = ?",
            [$id, $projectId]
        ) ?: null;
    }

    /**
     * Get paginated images for project with filters
     * @return array {data, total, per_page, current_page, last_page, from, to}
     */
    public function getPaginated(int $projectId, int $page = 1, int $perPage = 25, array $filters = []): array
    {
        $where = "WHERE i.project_id = ?";
        $params = [$projectId];

        // Status filter
        if (!empty($filters['status'])) {
            $where .= " AND i.status = ?";
            $params[] = $filters['status'];
        }

        // Search filter (product name or SKU)
        if (!empty($filters['search'])) {
            $where .= " AND (i.product_name LIKE ? OR i.sku LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        // Category filter
        if (!empty($filters['category'])) {
            $where .= " AND i.category = ?";
            $params[] = $filters['category'];
        }

        // Count total
        $total = (int) Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} i {$where}",
            $params
        )['cnt'];

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        // Sort
        $sortCol = $filters['sort'] ?? 'created_at';
        $sortDir = strtoupper($filters['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $allowedSort = ['product_name', 'sku', 'category', 'status', 'created_at'];
        if (!in_array($sortCol, $allowedSort)) {
            $sortCol = 'created_at';
        }

        // Fetch with variant counts
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

    /**
     * Get stats for project (count per status)
     */
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

    /**
     * Get images ready for generation (source_acquired status)
     */
    public function getNextForGeneration(int $projectId, int $limit = 1): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND status = ?
             ORDER BY id ASC LIMIT ?",
            [$projectId, self::STATUS_SOURCE_ACQUIRED, $limit]
        );
    }

    /**
     * Count images ready for generation
     */
    public function countReadyForGeneration(int $projectId): int
    {
        return (int) Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM {$this->table}
             WHERE project_id = ? AND status = ?",
            [$projectId, self::STATUS_SOURCE_ACQUIRED]
        )['cnt'];
    }

    /**
     * Get approved images (for export/push)
     */
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

    /**
     * Create a new image record
     * @return int Inserted ID
     */
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

    /**
     * Bulk insert images (e.g. from CMS import)
     * @return int Number of rows inserted
     */
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
                // Skip duplicates or invalid items
                continue;
            }
        }

        return $inserted;
    }

    /**
     * Update image fields
     */
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

    /**
     * Update status (with state machine validation)
     */
    public function updateStatus(int $id, string $newStatus): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET status = ? WHERE id = ?",
            [$newStatus, $id]
        ) !== false;
    }

    /**
     * Approve image (auto-set when first variant approved)
     * Only from 'generated' status
     */
    public function approve(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET status = ? WHERE id = ? AND status = ?",
            [self::STATUS_APPROVED, $id, self::STATUS_GENERATED]
        ) !== false;
    }

    /**
     * Unapprove image (when last approved variant rejected)
     * Only from 'approved' back to 'generated'
     */
    public function unapprove(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET status = ? WHERE id = ? AND status = ?",
            [self::STATUS_GENERATED, $id, self::STATUS_APPROVED]
        ) !== false;
    }

    /**
     * Mark as published
     */
    public function markPublished(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET status = ? WHERE id = ? AND status = ?",
            [self::STATUS_PUBLISHED, $id, self::STATUS_APPROVED]
        ) !== false;
    }

    /**
     * Mark error
     */
    public function markError(int $id, string $error): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET status = ?, error_message = ? WHERE id = ?",
            [self::STATUS_ERROR, $error, $id]
        ) !== false;
    }

    /**
     * Delete single image (variants cascade via FK)
     */
    public function delete(int $id): bool
    {
        return Database::execute(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Bulk delete with project_id filter (IDOR protection)
     */
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

    /**
     * Bulk approve with project_id filter (IDOR protection)
     * Only approves images that have at least 1 approved variant
     */
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

    /**
     * Check if image has any approved variants
     */
    public function hasApprovedVariants(int $id): bool
    {
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM cc_image_variants WHERE image_id = ? AND is_approved = 1",
            [$id]
        );
        return (int) ($result['cnt'] ?? 0) > 0;
    }

    /**
     * Get source image filename from path (for serve route)
     */
    public static function extractFilename(string $path): string
    {
        return basename($path);
    }

    /**
     * Find image by filename pattern (for serve route access control)
     * Filename format: {image_id}_{timestamp}.{ext}
     */
    public function findByFilename(string $filename): ?array
    {
        // Extract image_id from filename: "123_1710000000.jpg" → 123
        if (preg_match('/^(\d+)_/', $filename, $matches)) {
            return $this->find((int) $matches[1]);
        }
        return null;
    }
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/content-creator/models/Image.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/models/Image.php
git commit -m "feat(content-creator): add Image model with state machine, pagination, bulk ops"
```

---

## Task 3: ImageVariant Model

**Files:**
- Create: `modules/content-creator/models/ImageVariant.php`

**Reference:** Simpler than Image model — mostly CRUD + approve/reject per-variant.

- [ ] **Step 1: Write ImageVariant model**

```php
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

    /**
     * Find variant by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        ) ?: null;
    }

    /**
     * Find variant by ID with image ownership validation
     */
    public function findByImage(int $id, int $imageId): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ? AND image_id = ?",
            [$id, $imageId]
        ) ?: null;
    }

    /**
     * Get all variants for an image
     */
    public function getByImage(int $imageId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE image_id = ? ORDER BY variant_number ASC",
            [$imageId]
        );
    }

    /**
     * Get approved variants for an image
     */
    public function getApprovedByImage(int $imageId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE image_id = ? AND is_approved = 1 ORDER BY variant_number ASC",
            [$imageId]
        );
    }

    /**
     * Get all approved variants for a project (for export/push)
     */
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

    /**
     * Count approved variants for a project
     */
    public function countApprovedByProject(int $projectId): int
    {
        return (int) Database::fetchOne(
            "SELECT COUNT(*) as cnt
             FROM {$this->table} v
             JOIN cc_images i ON i.id = v.image_id
             WHERE i.project_id = ? AND v.is_approved = 1",
            [$projectId]
        )['cnt'];
    }

    /**
     * Create a new variant
     * @return int Inserted ID
     */
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

    /**
     * Approve a variant
     */
    public function approve(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET is_approved = 1 WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Reject (unapprove) a variant
     */
    public function reject(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET is_approved = 0 WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Mark as pushed to CMS
     */
    public function markPushed(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET is_pushed = 1 WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Mark push error
     */
    public function markPushError(int $id, string $error): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET cms_sync_error = ? WHERE id = ?",
            [$error, $id]
        ) !== false;
    }

    /**
     * Delete all variants for an image (before regeneration)
     */
    public function deleteByImage(int $imageId): int
    {
        $stmt = Database::execute(
            "DELETE FROM {$this->table} WHERE image_id = ?",
            [$imageId]
        );
        return $stmt ? $stmt->rowCount() : 0;
    }

    /**
     * Delete a single variant
     */
    public function delete(int $id): bool
    {
        return Database::execute(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Get next variant number for an image
     */
    public function getNextVariantNumber(int $imageId): int
    {
        $result = Database::fetchOne(
            "SELECT COALESCE(MAX(variant_number), 0) + 1 as next_num FROM {$this->table} WHERE image_id = ?",
            [$imageId]
        );
        return (int) ($result['next_num'] ?? 1);
    }

    /**
     * Count approved variants for an image
     */
    public function countApproved(int $imageId): int
    {
        return (int) Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE image_id = ? AND is_approved = 1",
            [$imageId]
        )['cnt'];
    }

    /**
     * Find variant by filename pattern (for serve route)
     * Filename format: {image_id}_v{N}_{timestamp}.{ext}
     */
    public function findByFilename(string $filename): ?array
    {
        if (preg_match('/^(\d+)_v(\d+)_/', $filename, $matches)) {
            return Database::fetchOne(
                "SELECT * FROM {$this->table} WHERE image_id = ? AND variant_number = ?",
                [(int) $matches[1], (int) $matches[2]]
            ) ?: null;
        }
        return null;
    }
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/content-creator/models/ImageVariant.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/models/ImageVariant.php
git commit -m "feat(content-creator): add ImageVariant model with approve/reject, bulk ops"
```

---

## Task 4: Update Job Model Constants

**Files:**
- Modify: `modules/content-creator/models/Job.php:26-28`

- [ ] **Step 1: Add new type constants**

After line 28 (`public const TYPE_CMS_PUSH = 'cms_push';`), add:

```php
    public const TYPE_IMAGE_GENERATE = 'image_generate';
    public const TYPE_IMAGE_PUSH = 'image_push';
```

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/content-creator/models/Job.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/models/Job.php
git commit -m "feat(content-creator): add IMAGE_GENERATE and IMAGE_PUSH job type constants"
```

---

## Chunk 1 Complete

**Verify all together:**
```bash
php -l modules/content-creator/models/Image.php
php -l modules/content-creator/models/ImageVariant.php
php -l modules/content-creator/models/Job.php
```

All three must report `No syntax errors detected`.
