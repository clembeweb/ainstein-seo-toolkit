<?php

namespace Modules\InternalLinks\Models;

use Core\Database;

/**
 * Snapshot Model
 *
 * Manages snapshots for comparison mode
 * Tables: il_snapshots, il_snapshot_links
 */
class Snapshot
{
    protected string $table = 'il_snapshots';
    protected string $linksTable = 'il_snapshot_links';

    /**
     * Find snapshot by ID (with optional user check)
     */
    public function find(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        return Database::fetch($sql, $params);
    }

    /**
     * Get all snapshots for a project
     */
    public function getByProject(int $projectId, ?int $userId = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create a snapshot from current project data
     */
    public function createFromCurrent(int $projectId, int $userId, string $name, ?string $description = null): int
    {
        // Get current stats
        $statsSql = "
            SELECT
                (SELECT COUNT(*) FROM il_urls WHERE project_id = ?) as total_urls,
                (SELECT COUNT(*) FROM il_internal_links WHERE project_id = ?) as total_links,
                (SELECT COUNT(*) FROM il_internal_links WHERE project_id = ? AND is_internal = 1) as internal_links,
                (SELECT COUNT(*) FROM il_internal_links WHERE project_id = ? AND is_internal = 0) as external_links,
                (SELECT AVG(ai_relevance_score) FROM il_internal_links WHERE project_id = ? AND ai_relevance_score IS NOT NULL) as avg_relevance_score
        ";

        $stats = Database::fetch($statsSql, [$projectId, $projectId, $projectId, $projectId, $projectId]);

        // Count orphan pages
        $orphanSql = "
            SELECT COUNT(*) as orphan_count
            FROM il_urls u
            WHERE u.project_id = ?
            AND u.status = 'scraped'
            AND NOT EXISTS (
                SELECT 1 FROM il_internal_links il
                WHERE il.project_id = ?
                AND il.is_internal = 1
                AND LOWER(TRIM(TRAILING '/' FROM il.destination_url)) = LOWER(TRIM(TRAILING '/' FROM u.url))
            )
        ";
        $orphanResult = Database::fetch($orphanSql, [$projectId, $projectId]);
        $orphanCount = $orphanResult['orphan_count'] ?? 0;

        // Create snapshot record
        $snapshotId = Database::insert($this->table, [
            'project_id' => $projectId,
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'total_urls' => $stats['total_urls'] ?? 0,
            'total_links' => $stats['total_links'] ?? 0,
            'internal_links' => $stats['internal_links'] ?? 0,
            'external_links' => $stats['external_links'] ?? 0,
            'orphan_pages' => $orphanCount,
            'avg_relevance_score' => $stats['avg_relevance_score'],
        ]);

        // Copy links to snapshot
        $copyLinksSql = "
            INSERT INTO {$this->linksTable} (snapshot_id, source_url, destination_url, anchor_text, is_internal, ai_relevance_score, ai_juice_flow)
            SELECT ?, u.url, il.destination_url, il.anchor_text, il.is_internal, il.ai_relevance_score, il.ai_juice_flow
            FROM il_internal_links il
            JOIN il_urls u ON il.source_url_id = u.id
            WHERE il.project_id = ?
        ";

        Database::query($copyLinksSql, [$snapshotId, $projectId]);

        return $snapshotId;
    }

    /**
     * Get links for a snapshot
     */
    public function getLinks(int $snapshotId, int $limit = 10000): array
    {
        $sql = "SELECT * FROM {$this->linksTable} WHERE snapshot_id = ? ORDER BY source_url, destination_url LIMIT ?";
        return Database::fetchAll($sql, [$snapshotId, $limit]);
    }

    /**
     * Get links count for a snapshot
     */
    public function getLinksCount(int $snapshotId): int
    {
        return Database::count($this->linksTable, 'snapshot_id = ?', [$snapshotId]);
    }

    /**
     * Compare two snapshots
     */
    public function compare(int $snapshotId1, int $snapshotId2, ?int $userId = null): array
    {
        $snapshot1 = $this->find($snapshotId1, $userId);
        $snapshot2 = $this->find($snapshotId2, $userId);

        if (!$snapshot1 || !$snapshot2) {
            return ['error' => 'Snapshot not found'];
        }

        // Verify same project
        if ($snapshot1['project_id'] !== $snapshot2['project_id']) {
            return ['error' => 'Snapshots must be from the same project'];
        }

        // Get links from both snapshots
        $links1 = $this->getLinksIndexed($snapshotId1);
        $links2 = $this->getLinksIndexed($snapshotId2);

        // Find differences
        $added = [];
        $removed = [];
        $changed = [];

        // Links in snapshot2 but not in snapshot1 (added)
        foreach ($links2 as $key => $link) {
            if (!isset($links1[$key])) {
                $added[] = $link;
            } else {
                // Check for anchor text changes
                if ($links1[$key]['anchor_text'] !== $link['anchor_text']) {
                    $changed[] = [
                        'source_url' => $link['source_url'],
                        'destination_url' => $link['destination_url'],
                        'old_anchor' => $links1[$key]['anchor_text'],
                        'new_anchor' => $link['anchor_text'],
                        'old_score' => $links1[$key]['ai_relevance_score'],
                        'new_score' => $link['ai_relevance_score'],
                    ];
                }
                // Check for score changes (even if anchor didn't change)
                elseif ($links1[$key]['ai_relevance_score'] != $link['ai_relevance_score']) {
                    $changed[] = [
                        'source_url' => $link['source_url'],
                        'destination_url' => $link['destination_url'],
                        'old_anchor' => $links1[$key]['anchor_text'],
                        'new_anchor' => $link['anchor_text'],
                        'old_score' => $links1[$key]['ai_relevance_score'],
                        'new_score' => $link['ai_relevance_score'],
                        'score_only' => true,
                    ];
                }
            }
        }

        // Links in snapshot1 but not in snapshot2 (removed)
        foreach ($links1 as $key => $link) {
            if (!isset($links2[$key])) {
                $removed[] = $link;
            }
        }

        // Calculate stats differences
        $statsDiff = [
            'total_urls' => ($snapshot2['total_urls'] ?? 0) - ($snapshot1['total_urls'] ?? 0),
            'total_links' => ($snapshot2['total_links'] ?? 0) - ($snapshot1['total_links'] ?? 0),
            'internal_links' => ($snapshot2['internal_links'] ?? 0) - ($snapshot1['internal_links'] ?? 0),
            'external_links' => ($snapshot2['external_links'] ?? 0) - ($snapshot1['external_links'] ?? 0),
            'orphan_pages' => ($snapshot2['orphan_pages'] ?? 0) - ($snapshot1['orphan_pages'] ?? 0),
            'avg_relevance_score' => round(($snapshot2['avg_relevance_score'] ?? 0) - ($snapshot1['avg_relevance_score'] ?? 0), 2),
        ];

        return [
            'snapshot1' => $snapshot1,
            'snapshot2' => $snapshot2,
            'stats_diff' => $statsDiff,
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed,
            'summary' => [
                'links_added' => count($added),
                'links_removed' => count($removed),
                'links_changed' => count($changed),
                'net_change' => count($added) - count($removed),
            ],
        ];
    }

    /**
     * Get links indexed by source+destination for comparison
     */
    private function getLinksIndexed(int $snapshotId): array
    {
        $links = $this->getLinks($snapshotId);
        $indexed = [];

        foreach ($links as $link) {
            $key = strtolower(rtrim($link['source_url'], '/')) . '|' . strtolower(rtrim($link['destination_url'], '/'));
            $indexed[$key] = $link;
        }

        return $indexed;
    }

    /**
     * Delete snapshot (links deleted via CASCADE)
     */
    public function delete(int $id, ?int $userId = null): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        return Database::query($sql, $params)->rowCount() > 0;
    }

    /**
     * Count snapshots for a project
     */
    public function countByProject(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Get latest snapshot for a project
     */
    public function getLatest(int $projectId, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY created_at DESC LIMIT 1";

        return Database::fetch($sql, $params);
    }
}
