<?php

namespace Modules\AiOptimizer\Models;

use Core\Database;

/**
 * Model per ottimizzazioni articoli
 */
class Optimization
{
    protected string $table = 'aio_optimizations';

    /**
     * Trova ottimizzazione per ID
     */
    public function find(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $result = Database::fetch($sql, $params);

        if ($result) {
            $result['analysis_data'] = json_decode($result['analysis_json'] ?? '{}', true);
            $result['competitors'] = json_decode($result['competitors_json'] ?? '[]', true);
            $result['original_headings'] = json_decode($result['original_headings_json'] ?? '[]', true);
        }

        return $result ?: null;
    }

    /**
     * Lista ottimizzazioni per progetto
     */
    public function findByProject(int $projectId, int $userId, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT id, keyword, original_url, original_title, status, seo_score,
                    original_word_count, optimized_word_count, credits_used,
                    created_at, analyzed_at, refactored_at
             FROM {$this->table}
             WHERE project_id = ? AND user_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$projectId, $userId, $limit]
        );
    }

    /**
     * Lista ottimizzazioni utente (tutti i progetti)
     */
    public function findByUser(int $userId, int $limit = 50, ?string $status = null): array
    {
        $sql = "SELECT o.*, p.name as project_name
                FROM {$this->table} o
                LEFT JOIN aio_projects p ON o.project_id = p.id
                WHERE o.user_id = ?";
        $params = [$userId];

        if ($status) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY o.created_at DESC LIMIT ?";
        $params[] = $limit;

        return Database::fetchAll($sql, $params);
    }

    /**
     * Crea nuova ottimizzazione (import articolo)
     */
    public function create(array $data): int
    {
        Database::insert($this->table, [
            'user_id' => $data['user_id'],
            'project_id' => $data['project_id'] ?? null,
            'original_url' => $data['original_url'],
            'keyword' => $data['keyword'],
            'original_title' => $data['original_title'] ?? null,
            'original_meta_description' => $data['original_meta_description'] ?? null,
            'original_h1' => $data['original_h1'] ?? null,
            'original_content' => $data['original_content'] ?? null,
            'original_word_count' => $data['original_word_count'] ?? 0,
            'original_headings_json' => isset($data['original_headings']) ? json_encode($data['original_headings']) : null,
            'status' => 'imported',
        ]);

        return Database::lastInsertId();
    }

    /**
     * Aggiorna con dati analisi
     */
    public function updateAnalysis(int $id, array $analysisData, array $competitors, int $seoScore): bool
    {
        return Database::query(
            "UPDATE {$this->table} SET
                analysis_json = ?,
                competitors_json = ?,
                competitors_count = ?,
                seo_score = ?,
                status = 'analyzed',
                analyzed_at = NOW()
             WHERE id = ?",
            [
                json_encode($analysisData, JSON_UNESCAPED_UNICODE),
                json_encode($competitors, JSON_UNESCAPED_UNICODE),
                count($competitors),
                $seoScore,
                $id
            ]
        ) !== false;
    }

    /**
     * Aggiorna con contenuto ottimizzato
     */
    public function updateOptimized(int $id, array $optimizedData): bool
    {
        return Database::query(
            "UPDATE {$this->table} SET
                optimized_title = ?,
                optimized_meta_description = ?,
                optimized_h1 = ?,
                optimized_content = ?,
                optimized_word_count = ?,
                status = 'refactored',
                refactored_at = NOW()
             WHERE id = ?",
            [
                $optimizedData['title'] ?? null,
                $optimizedData['meta_description'] ?? null,
                $optimizedData['h1'] ?? null,
                $optimizedData['content'] ?? null,
                $optimizedData['word_count'] ?? 0,
                $id
            ]
        ) !== false;
    }

    /**
     * Aggiorna stato
     */
    public function updateStatus(int $id, string $status, ?string $errorMessage = null): bool
    {
        $sql = "UPDATE {$this->table} SET status = ?";
        $params = [$status];

        if ($errorMessage !== null) {
            $sql .= ", error_message = ?";
            $params[] = $errorMessage;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        return Database::query($sql, $params) !== false;
    }

    /**
     * Aggiorna crediti usati
     */
    public function addCreditsUsed(int $id, float $credits): bool
    {
        return Database::query(
            "UPDATE {$this->table} SET credits_used = credits_used + ? WHERE id = ?",
            [$credits, $id]
        ) !== false;
    }

    /**
     * Segna come esportato
     */
    public function markExported(int $id): bool
    {
        return Database::query(
            "UPDATE {$this->table} SET status = 'exported' WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Elimina ottimizzazione
     */
    public function delete(int $id, int $userId): bool
    {
        return Database::query(
            "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?",
            [$id, $userId]
        ) !== false;
    }

    /**
     * Conta per stato (statistiche)
     */
    public function countByStatus(int $userId, ?int $projectId = null): array
    {
        $sql = "SELECT status, COUNT(*) as cnt FROM {$this->table} WHERE user_id = ?";
        $params = [$userId];

        if ($projectId) {
            $sql .= " AND project_id = ?";
            $params[] = $projectId;
        }

        $sql .= " GROUP BY status";

        $results = Database::fetchAll($sql, $params);

        $counts = [
            'imported' => 0,
            'analyzing' => 0,
            'analyzed' => 0,
            'refactoring' => 0,
            'refactored' => 0,
            'exported' => 0,
            'failed' => 0,
            'total' => 0,
        ];

        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['cnt'];
            $counts['total'] += (int)$row['cnt'];
        }

        return $counts;
    }

    /**
     * Ottimizzazioni recenti per progetto
     */
    public function getRecent(int $projectId, int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT id, keyword, original_url, status, seo_score, created_at
             FROM {$this->table}
             WHERE project_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }
}
