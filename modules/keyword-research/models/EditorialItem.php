<?php

namespace Modules\KeywordResearch\Models;

use Core\Database;

class EditorialItem
{
    protected string $table = 'kr_editorial_items';

    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function findByResearch(int $researchId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE research_id = ? ORDER BY month_number ASC, sort_order ASC",
            [$researchId]
        );
    }

    /**
     * Restituisce gli item raggruppati per mese
     */
    public function findGroupedByMonth(int $researchId): array
    {
        $items = $this->findByResearch($researchId);
        $grouped = [];

        foreach ($items as $item) {
            $month = $item['month_number'];
            if (!isset($grouped[$month])) {
                $grouped[$month] = [];
            }
            $grouped[$month][] = $item;
        }

        return $grouped;
    }

    public function create(array $data): int
    {
        Database::insert($this->table, [
            'research_id' => $data['research_id'],
            'month_number' => $data['month_number'],
            'week_number' => $data['week_number'] ?? null,
            'category' => $data['category'],
            'title' => $data['title'],
            'main_keyword' => $data['main_keyword'],
            'main_volume' => $data['main_volume'] ?? 0,
            'secondary_keywords' => json_encode($data['secondary_keywords'] ?? []),
            'intent' => $data['intent'] ?? null,
            'difficulty' => $data['difficulty'] ?? 'medium',
            'content_type' => $data['content_type'] ?? null,
            'notes' => $data['notes'] ?? null,
            'seasonal_note' => $data['seasonal_note'] ?? null,
            'serp_gap' => $data['serp_gap'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        return (int) Database::lastInsertId();
    }

    public function markSentToContent(array $ids): void
    {
        if (empty($ids)) return;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        Database::query(
            "UPDATE {$this->table} SET sent_to_content = 1, sent_at = NOW() WHERE id IN ({$placeholders})",
            $ids
        );
    }

    public function getStats(int $researchId): array
    {
        return Database::fetch(
            "SELECT
                COUNT(*) as total_articles,
                COUNT(DISTINCT month_number) as total_months,
                COUNT(DISTINCT category) as total_categories,
                SUM(main_volume) as total_volume,
                SUM(sent_to_content) as sent_count
            FROM {$this->table} WHERE research_id = ?",
            [$researchId]
        ) ?: ['total_articles' => 0, 'total_months' => 0, 'total_categories' => 0, 'total_volume' => 0, 'sent_count' => 0];
    }

    public function deleteByResearch(int $researchId): void
    {
        Database::delete($this->table, 'research_id = ?', [$researchId]);
    }
}
