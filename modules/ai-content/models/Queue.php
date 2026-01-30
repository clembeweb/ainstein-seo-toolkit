<?php
namespace Modules\AiContent\Models;

use Core\Database;

class Queue
{
    private $db;
    private $table = 'aic_queue';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Trova item per ID e user_id
     */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Trova item per ID (senza verifica user_id)
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Lista coda per progetto
     */
    public function getByProject(int $projectId, ?string $status = null, int $limit = 100): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY scheduled_at ASC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Conta items per progetto e status
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
     * Prossimo item da processare (per cron)
     * sources_count viene letto direttamente dalla tabella aic_queue
     */
    public function getNextPending(): ?array
    {
        $stmt = $this->db->prepare("
            SELECT q.*, p.name as project_name
            FROM {$this->table} q
            JOIN aic_projects p ON q.project_id = p.id
            WHERE q.status = 'pending'
            AND q.scheduled_at <= NOW()
            ORDER BY q.scheduled_at ASC
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Prossimo item da processare per un progetto specifico
     * sources_count viene letto direttamente dalla tabella aic_queue
     */
    public function getNextPendingForProject(int $projectId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT q.*, p.name as project_name
            FROM {$this->table} q
            JOIN aic_projects p ON q.project_id = p.id
            WHERE q.project_id = ?
            AND q.status = 'pending'
            AND q.scheduled_at <= NOW()
            ORDER BY q.scheduled_at ASC
            LIMIT 1
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get pending items for a project (for CRON dispatcher)
     * sources_count viene letto direttamente dalla tabella aic_queue
     *
     * @param int $projectId Project ID
     * @param int $limit Max items to return
     * @return array List of pending queue items
     */
    public function getPending(int $projectId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT q.*, p.name as project_name
            FROM {$this->table} q
            JOIN aic_projects p ON q.project_id = p.id
            WHERE q.project_id = ?
            AND q.status = 'pending'
            AND q.scheduled_at <= NOW()
            ORDER BY q.scheduled_at ASC
            LIMIT ?
        ");
        $stmt->execute([$projectId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Inserisce keyword in coda (bulk)
     *
     * @param int $userId User ID
     * @param int $projectId Project ID
     * @param array $keywordData Array di oggetti con chiavi: keyword, scheduled_at, sources_count
     *                           Esempio: [['keyword' => 'test', 'scheduled_at' => '2024-01-01 10:00:00', 'sources_count' => 3], ...]
     *                           Per retrocompatibilità accetta anche array di stringhe (keywords) con $scheduledTimes opzionale
     * @param array|null $scheduledTimes (Deprecato) Array di date/time - usare $keywordData con oggetti
     * @return int Numero di keyword inserite
     */
    public function addBulk(int $userId, int $projectId, array $keywordData, ?array $scheduledTimes = null): int
    {
        $sql = "INSERT INTO {$this->table}
                (user_id, project_id, keyword, language, location, sources_count, scheduled_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        $inserted = 0;

        // Retrocompatibilità: se $keywordData contiene stringhe, usa il vecchio formato
        if (!empty($keywordData) && is_string(reset($keywordData))) {
            // Vecchio formato: array di stringhe + array di scheduled times
            foreach ($keywordData as $i => $kw) {
                $keyword = trim($kw);
                if (empty($keyword)) continue;

                $scheduledAt = $scheduledTimes[$i] ?? ($scheduledTimes ? end($scheduledTimes) : date('Y-m-d H:i:s'));
                $stmt->execute([
                    $userId,
                    $projectId,
                    $keyword,
                    'it',
                    'Italy',
                    3, // default sources_count per retrocompatibilità
                    $scheduledAt
                ]);
                $inserted++;
            }
        } else {
            // Nuovo formato: array di oggetti con keyword, scheduled_at, sources_count
            foreach ($keywordData as $item) {
                $keyword = trim($item['keyword'] ?? '');
                if (empty($keyword)) continue;

                // scheduled_at può essere NULL (da pianificare dalla coda)
                $scheduledAt = $item['scheduled_at'] ?? null;
                $sourcesCount = $item['sources_count'] ?? 3;

                $stmt->execute([
                    $userId,
                    $projectId,
                    $keyword,
                    'it',
                    'Italy',
                    $sourcesCount,
                    $scheduledAt
                ]);
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Inserisce singola keyword
     */
    public function add(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (user_id, project_id, keyword, language, location, sources_count, scheduled_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'],
            $data['project_id'],
            $data['keyword'],
            $data['language'] ?? 'it',
            $data['location'] ?? 'Italy',
            $data['sources_count'] ?? 3,
            $data['scheduled_at']
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Aggiorna scheduled_at di un item
     */
    public function updateScheduledAt(int $id, string $scheduledAt): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET scheduled_at = ?
            WHERE id = ?
        ");
        return $stmt->execute([$scheduledAt, $id]);
    }

    /**
     * Aggiorna status
     */
    public function updateStatus(int $id, string $status, ?string $error = null): bool
    {
        $sql = "UPDATE {$this->table} SET status = ?";
        $params = [$status];

        if ($status === 'processing') {
            $sql .= ", started_at = NOW()";
        } elseif (in_array($status, ['completed', 'error'])) {
            $sql .= ", completed_at = NOW()";
        }

        if ($error !== null) {
            $sql .= ", error_message = ?";
            $params[] = $error;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Aggiorna un item nella coda (solo pending)
     */
    public function update(int $id, array $data): bool
    {
        $allowed = ['scheduled_at', 'sources_count'];
        $updates = [];
        $params = [];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ? AND status = 'pending'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Collega keyword e article generati
     */
    public function linkGenerated(int $id, int $keywordId, int $articleId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET keyword_id = ?, article_id = ?, status = 'completed', completed_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$keywordId, $articleId, $id]);
    }

    /**
     * Elimina item
     */
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Elimina tutti pending di un progetto
     */
    public function clearPending(int $projectId): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE project_id = ? AND status = 'pending'
        ");
        $stmt->execute([$projectId]);
        return $stmt->rowCount();
    }

    /**
     * Statistiche coda per progetto
     */
    public function getStats(int $projectId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total,
                SUM(status = 'pending') as pending,
                SUM(status = 'processing') as processing,
                SUM(status = 'completed') as completed,
                SUM(status = 'error') as errors,
                MIN(CASE WHEN status = 'pending' THEN scheduled_at END) as next_scheduled
            FROM {$this->table}
            WHERE project_id = ?
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Statistiche oggi per progetto
     */
    public function getTodayStats(int $projectId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                SUM(status = 'completed' AND DATE(completed_at) = CURDATE()) as completed_today,
                SUM(status = 'pending' AND DATE(scheduled_at) = CURDATE()) as scheduled_today
            FROM {$this->table}
            WHERE project_id = ?
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Make pending items immediately processable (for manual processing)
     * Sets scheduled_at to NOW() for pending items in a project
     *
     * @param int $projectId Project ID
     * @param int|null $limit Max items to update (null = all)
     * @return int Number of items updated
     */
    public function makeImmediatelyProcessable(int $projectId, ?int $limit = null): int
    {
        if ($limit !== null) {
            // Get IDs of items to update
            $stmt = $this->db->prepare("
                SELECT id FROM {$this->table}
                WHERE project_id = ? AND status = 'pending'
                ORDER BY scheduled_at ASC
                LIMIT ?
            ");
            $stmt->execute([$projectId, $limit]);
            $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($ids)) {
                return 0;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("
                UPDATE {$this->table}
                SET scheduled_at = NOW()
                WHERE id IN ({$placeholders})
            ");
            $stmt->execute($ids);
            return $stmt->rowCount();
        }

        // Update all pending
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET scheduled_at = NOW()
            WHERE project_id = ? AND status = 'pending'
        ");
        $stmt->execute([$projectId]);
        return $stmt->rowCount();
    }
}
