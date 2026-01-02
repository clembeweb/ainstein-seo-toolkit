<?php

namespace Modules\AiContent\Models;

use Core\Database;

/**
 * Keyword Model
 *
 * Manages aic_keywords table
 */
class Keyword
{
    protected string $table = 'aic_keywords';

    /**
     * Find keyword by ID (with user check)
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
     * Get all keywords for a user
     */
    public function allByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                k.*,
                (SELECT COUNT(*) FROM aic_serp_results WHERE keyword_id = k.id) as serp_count,
                (SELECT COUNT(*) FROM aic_articles WHERE keyword_id = k.id) as articles_count,
                (SELECT brief_data IS NOT NULL FROM aic_articles WHERE keyword_id = k.id ORDER BY created_at DESC LIMIT 1) as has_brief,
                (SELECT status FROM aic_articles WHERE keyword_id = k.id ORDER BY created_at DESC LIMIT 1) as article_status
            FROM {$this->table} k
            WHERE k.user_id = ?
            ORDER BY k.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $data = Database::fetchAll($sql, [$userId, $perPage, $offset]);
        $total = $this->countByUser($userId);

        return [
            'data' => $data,
            'total' => $total,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'per_page' => $perPage
        ];
    }

    /**
     * Create new keyword
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, [
            'user_id' => $data['user_id'],
            'keyword' => $data['keyword'],
            'language' => $data['language'] ?? 'it',
            'location' => $data['location'] ?? 'Italy'
        ]);
    }

    /**
     * Update keyword
     */
    public function update(int $id, array $data, int $userId): bool
    {
        return Database::update($this->table, $data, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Mark SERP as extracted
     */
    public function markSerpExtracted(int $id): bool
    {
        return Database::update($this->table, [
            'serp_extracted_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Delete keyword
     */
    public function delete(int $id, int $userId): bool
    {
        return Database::delete($this->table, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Count keywords for user
     */
    public function countByUser(int $userId): int
    {
        return Database::count($this->table, 'user_id = ?', [$userId]);
    }

    /**
     * Check if keyword already exists for user
     */
    public function exists(string $keyword, int $userId): bool
    {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE keyword = ? AND user_id = ?";
        $result = Database::fetch($sql, [$keyword, $userId]);
        return (int) $result['cnt'] > 0;
    }

    /**
     * Get keyword with SERP data
     */
    public function findWithSerp(int $id, int $userId): ?array
    {
        $keyword = $this->find($id, $userId);

        if (!$keyword) {
            return null;
        }

        // Get SERP results
        $serpModel = new SerpResult();
        $keyword['serp_results'] = $serpModel->getByKeyword($id);

        // Get PAA questions
        $keyword['paa_questions'] = $this->getPaaQuestions($id);

        return $keyword;
    }

    /**
     * Get PAA questions for keyword
     */
    public function getPaaQuestions(int $keywordId): array
    {
        $sql = "SELECT * FROM aic_paa_questions WHERE keyword_id = ? ORDER BY position ASC";
        return Database::fetchAll($sql, [$keywordId]);
    }

    /**
     * Save PAA questions
     */
    public function savePaaQuestions(int $keywordId, array $questions): void
    {
        // Delete existing
        Database::delete('aic_paa_questions', 'keyword_id = ?', [$keywordId]);

        // Insert new
        foreach ($questions as $q) {
            Database::insert('aic_paa_questions', [
                'keyword_id' => $keywordId,
                'question' => $q['question'],
                'snippet' => $q['snippet'] ?? null,
                'position' => $q['position'] ?? 0
            ]);
        }
    }

    /**
     * Save brief to keyword record
     */
    public function saveBrief(int $keywordId, array $briefData): bool
    {
        $data = [
            'brief_search_intent' => $briefData['searchIntent'] ?? $briefData['search_intent']['primary'] ?? null,
            'brief_structure' => is_array($briefData['suggestedHeadings'] ?? null)
                ? json_encode($briefData['suggestedHeadings'])
                : ($briefData['suggestedHeadings'] ?? null),
            'brief_entities' => is_array($briefData['entities'] ?? null)
                ? json_encode($briefData['entities'])
                : ($briefData['entities'] ?? null),
            'brief_word_count' => $briefData['targetWordCount'] ?? $briefData['recommended_word_count'] ?? null,
            'brief_notes' => $briefData['additionalNotes'] ?? $briefData['notes'] ?? null,
            'brief_generated_at' => date('Y-m-d H:i:s')
        ];

        return Database::update($this->table, $data, 'id = ?', [$keywordId]) > 0;
    }

    /**
     * Get brief from keyword record
     */
    public function getBrief(int $keywordId): ?array
    {
        $keyword = $this->find($keywordId);

        if (!$keyword || empty($keyword['brief_generated_at'])) {
            return null;
        }

        return [
            'searchIntent' => $keyword['brief_search_intent'],
            'intentDescription' => $this->getIntentDescription($keyword['brief_search_intent']),
            'suggestedHeadings' => json_decode($keyword['brief_structure'], true) ?? [],
            'entities' => json_decode($keyword['brief_entities'], true) ?? [],
            'targetWordCount' => $keyword['brief_word_count'] ?? 1500,
            'additionalNotes' => $keyword['brief_notes'] ?? '',
            'generated_at' => $keyword['brief_generated_at']
        ];
    }

    /**
     * Check if keyword has a saved brief
     */
    public function hasBrief(int $keywordId): bool
    {
        $keyword = $this->find($keywordId);
        return $keyword && !empty($keyword['brief_generated_at']);
    }

    /**
     * Get search intent description
     */
    private function getIntentDescription(?string $intent): string
    {
        $descriptions = [
            'informational' => 'L\'utente cerca informazioni, guide o spiegazioni su questo argomento.',
            'commercial' => 'L\'utente sta valutando opzioni e confrontando prodotti/servizi.',
            'transactional' => 'L\'utente è pronto ad acquistare o completare un\'azione.',
            'navigational' => 'L\'utente cerca un sito o una pagina specifica.',
        ];

        return $descriptions[$intent] ?? 'Search intent non determinato.';
    }
}
