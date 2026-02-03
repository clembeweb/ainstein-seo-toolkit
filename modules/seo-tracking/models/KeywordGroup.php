<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * KeywordGroup Model
 * Gestisce la tabella st_keyword_groups e st_keyword_group_members
 */
class KeywordGroup
{
    protected string $table = 'st_keyword_groups';
    protected string $membersTable = 'st_keyword_group_members';

    /**
     * Trova gruppo per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Trova gruppo per ID con verifica progetto
     */
    public function findByProject(int $id, int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND project_id = ?",
            [$id, $projectId]
        );
    }

    /**
     * Trova gruppo per nome
     */
    public function findByName(int $projectId, string $name): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE project_id = ? AND name = ?",
            [$projectId, $name]
        );
    }

    /**
     * Tutti i gruppi di un progetto
     */
    public function allByProject(int $projectId, bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($onlyActive) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY sort_order ASC, name ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Gruppi con statistiche keyword basate su rank checker
     */
    public function allWithStats(int $projectId): array
    {
        $sql = "
            SELECT
                g.*,
                COUNT(DISTINCT m.keyword_id) as keyword_count,
                AVG(rc_latest.serp_position) as avg_position,
                SUM(CASE WHEN rc_latest.serp_position <= 3 THEN 1 ELSE 0 END) as top3_count,
                SUM(CASE WHEN rc_latest.serp_position <= 10 THEN 1 ELSE 0 END) as top10_count
            FROM {$this->table} g
            LEFT JOIN {$this->membersTable} m ON g.id = m.group_id
            LEFT JOIN st_keywords k ON m.keyword_id = k.id
            LEFT JOIN (
                SELECT rc1.keyword, rc1.serp_position
                FROM st_rank_checks rc1
                WHERE rc1.project_id = ?
                  AND rc1.serp_position IS NOT NULL
                  AND rc1.checked_at = (
                      SELECT MAX(rc2.checked_at)
                      FROM st_rank_checks rc2
                      WHERE rc2.project_id = rc1.project_id
                        AND rc2.keyword = rc1.keyword
                        AND rc2.serp_position IS NOT NULL
                  )
            ) rc_latest ON k.keyword = rc_latest.keyword
            WHERE g.project_id = ?
            GROUP BY g.id
            ORDER BY g.sort_order ASC, g.name ASC
        ";

        return Database::fetchAll($sql, [$projectId, $projectId]);
    }

    /**
     * Crea gruppo
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Trova o crea gruppo per nome
     * Usato per sincronizzare group_name di st_keywords con st_keyword_groups
     */
    public function findOrCreate(int $projectId, string $name): int
    {
        $name = trim($name);
        if (empty($name)) {
            return 0;
        }

        // Cerca gruppo esistente
        $existing = $this->findByName($projectId, $name);
        if ($existing) {
            return (int) $existing['id'];
        }

        // Crea nuovo gruppo
        $colors = self::getDefaultColors();
        $colorIndex = Database::count($this->table, 'project_id = ?', [$projectId]) % count($colors);

        return $this->create([
            'project_id' => $projectId,
            'name' => $name,
            'color' => $colors[$colorIndex],
            'is_active' => 1,
            'sort_order' => 0,
        ]);
    }

    /**
     * Sincronizza keyword con gruppo
     * Chiamato quando si salva una keyword con group_name
     */
    public function syncKeywordToGroup(int $projectId, int $keywordId, ?string $groupName): void
    {
        // Rimuovi keyword da tutti i gruppi del progetto
        $groups = $this->allByProject($projectId, false);
        foreach ($groups as $group) {
            $this->removeKeyword($group['id'], $keywordId);
        }

        // Se c'è un nome gruppo, trova/crea e aggiungi
        if (!empty($groupName)) {
            $groupId = $this->findOrCreate($projectId, $groupName);
            if ($groupId > 0) {
                $this->addKeyword($groupId, $keywordId);
            }
        }
    }

    /**
     * Sincronizza tutti i gruppi da st_keywords.group_name a st_keyword_groups
     * Utility per migrare dati esistenti
     */
    public function syncAllFromKeywords(int $projectId): array
    {
        $stats = ['groups_created' => 0, 'keywords_linked' => 0];

        // Trova tutti i group_name distinti dalle keywords
        $keywordsWithGroups = Database::fetchAll(
            "SELECT id, group_name FROM st_keywords
             WHERE project_id = ? AND group_name IS NOT NULL AND group_name != ''",
            [$projectId]
        );

        foreach ($keywordsWithGroups as $kw) {
            $groupId = $this->findOrCreate($projectId, $kw['group_name']);
            if ($groupId > 0) {
                if (!$this->hasKeyword($groupId, $kw['id'])) {
                    $this->addKeyword($groupId, $kw['id']);
                    $stats['keywords_linked']++;
                }
            }
        }

        // Conta gruppi creati
        $stats['groups_created'] = $this->countByProject($projectId);

        return $stats;
    }

    /**
     * Aggiorna gruppo
     */
    public function update(int $id, array $data): bool
    {
        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Elimina gruppo
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Conta gruppi per progetto
     */
    public function countByProject(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }

    // =========================================
    // GESTIONE MEMBRI (keyword in gruppo)
    // =========================================

    /**
     * Aggiunge keyword al gruppo
     */
    public function addKeyword(int $groupId, int $keywordId): bool
    {
        try {
            Database::insert($this->membersTable, [
                'group_id' => $groupId,
                'keyword_id' => $keywordId,
            ]);
            return true;
        } catch (\Exception $e) {
            // Ignora duplicati
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                return true;
            }
            throw $e;
        }
    }

    /**
     * Aggiunge multiple keyword al gruppo
     */
    public function addKeywords(int $groupId, array $keywordIds): int
    {
        $added = 0;
        foreach ($keywordIds as $keywordId) {
            if ($this->addKeyword($groupId, (int)$keywordId)) {
                $added++;
            }
        }
        return $added;
    }

    /**
     * Rimuove keyword dal gruppo
     */
    public function removeKeyword(int $groupId, int $keywordId): bool
    {
        return Database::delete(
            $this->membersTable,
            'group_id = ? AND keyword_id = ?',
            [$groupId, $keywordId]
        ) > 0;
    }

    /**
     * Rimuove tutte le keyword dal gruppo
     */
    public function clearKeywords(int $groupId): int
    {
        return Database::delete($this->membersTable, 'group_id = ?', [$groupId]);
    }

    /**
     * Sincronizza keyword del gruppo (replace all)
     */
    public function syncKeywords(int $groupId, array $keywordIds): void
    {
        $this->clearKeywords($groupId);
        $this->addKeywords($groupId, $keywordIds);
    }

    /**
     * Ottieni keyword del gruppo
     */
    public function getKeywords(int $groupId): array
    {
        $sql = "
            SELECT k.*
            FROM st_keywords k
            JOIN {$this->membersTable} m ON k.id = m.keyword_id
            WHERE m.group_id = ?
            ORDER BY k.last_position IS NULL, k.last_position ASC
        ";

        return Database::fetchAll($sql, [$groupId]);
    }

    /**
     * Ottieni keyword del gruppo con metriche da rank checker
     */
    public function getKeywordsWithMetrics(int $groupId, int $days = 7): array
    {
        // Ottieni prima il project_id dal gruppo
        $group = $this->find($groupId);
        if (!$group) {
            return [];
        }
        $projectId = $group['project_id'];

        $sql = "
            SELECT
                k.*,
                m.added_at as group_added_at,
                rc_latest.serp_position as current_position,
                rc_latest.serp_url as ranking_url,
                rc_latest.checked_at as last_check,
                rc_prev.serp_position as prev_position,
                CASE
                    WHEN rc_latest.serp_position IS NOT NULL AND rc_prev.serp_position IS NOT NULL
                    THEN rc_prev.serp_position - rc_latest.serp_position
                    ELSE NULL
                END as position_change
            FROM st_keywords k
            JOIN {$this->membersTable} m ON k.id = m.keyword_id
            LEFT JOIN (
                SELECT rc1.keyword, rc1.serp_position, rc1.serp_url, rc1.checked_at
                FROM st_rank_checks rc1
                WHERE rc1.project_id = ?
                  AND rc1.checked_at = (
                      SELECT MAX(rc2.checked_at)
                      FROM st_rank_checks rc2
                      WHERE rc2.project_id = rc1.project_id
                        AND rc2.keyword = rc1.keyword
                  )
            ) rc_latest ON k.keyword = rc_latest.keyword
            LEFT JOIN (
                SELECT rc3.keyword, rc3.serp_position
                FROM st_rank_checks rc3
                WHERE rc3.project_id = ?
                  AND rc3.checked_at = (
                      SELECT MAX(rc4.checked_at)
                      FROM st_rank_checks rc4
                      WHERE rc4.project_id = rc3.project_id
                        AND rc4.keyword = rc3.keyword
                        AND rc4.checked_at < (
                            SELECT MAX(checked_at) FROM st_rank_checks
                            WHERE project_id = rc3.project_id AND keyword = rc3.keyword
                        )
                  )
            ) rc_prev ON k.keyword = rc_prev.keyword
            WHERE m.group_id = ?
            ORDER BY rc_latest.serp_position IS NULL, rc_latest.serp_position ASC
        ";

        return Database::fetchAll($sql, [$projectId, $projectId, $groupId]);
    }

    /**
     * Conta keyword nel gruppo
     */
    public function countKeywords(int $groupId): int
    {
        return Database::count($this->membersTable, 'group_id = ?', [$groupId]);
    }

    /**
     * Gruppi di una keyword
     */
    public function getGroupsForKeyword(int $keywordId): array
    {
        $sql = "
            SELECT g.*
            FROM {$this->table} g
            JOIN {$this->membersTable} m ON g.id = m.group_id
            WHERE m.keyword_id = ?
            ORDER BY g.name ASC
        ";

        return Database::fetchAll($sql, [$keywordId]);
    }

    /**
     * Verifica se keyword appartiene a gruppo
     */
    public function hasKeyword(int $groupId, int $keywordId): bool
    {
        $count = Database::count(
            $this->membersTable,
            'group_id = ? AND keyword_id = ?',
            [$groupId, $keywordId]
        );
        return $count > 0;
    }

    /**
     * Statistiche aggregate del gruppo basate su rank checker
     */
    public function getStats(int $groupId): array
    {
        // Ottieni project_id dal gruppo
        $group = $this->find($groupId);
        if (!$group) {
            return [
                'total_keywords' => 0,
                'avg_position' => 0,
                'top3_count' => 0,
                'top10_count' => 0,
                'top20_count' => 0,
                'beyond20_count' => 0,
                'improved_count' => 0,
                'declined_count' => 0,
            ];
        }
        $projectId = $group['project_id'];

        // Conta keyword nel gruppo
        $totalKeywords = $this->countKeywords($groupId);

        // Statistiche basate su st_rank_checks (ultima posizione per ogni keyword)
        $sql = "
            SELECT
                AVG(latest.serp_position) as avg_position,
                SUM(CASE WHEN latest.serp_position <= 3 THEN 1 ELSE 0 END) as top3_count,
                SUM(CASE WHEN latest.serp_position <= 10 THEN 1 ELSE 0 END) as top10_count,
                SUM(CASE WHEN latest.serp_position > 10 AND latest.serp_position <= 20 THEN 1 ELSE 0 END) as top20_count,
                SUM(CASE WHEN latest.serp_position > 20 OR latest.serp_position IS NULL THEN 1 ELSE 0 END) as beyond20_count
            FROM {$this->membersTable} m
            JOIN st_keywords k ON m.keyword_id = k.id
            LEFT JOIN (
                SELECT rc1.keyword, rc1.serp_position
                FROM st_rank_checks rc1
                WHERE rc1.project_id = ?
                  AND rc1.serp_position IS NOT NULL
                  AND rc1.checked_at = (
                      SELECT MAX(rc2.checked_at)
                      FROM st_rank_checks rc2
                      WHERE rc2.project_id = rc1.project_id
                        AND rc2.keyword = rc1.keyword
                        AND rc2.serp_position IS NOT NULL
                  )
            ) latest ON k.keyword = latest.keyword
            WHERE m.group_id = ?
        ";

        $result = Database::fetch($sql, [$projectId, $groupId]);

        // Calcola miglioramenti/peggioramenti
        $variationsSql = "
            SELECT
                SUM(CASE WHEN rc_new.serp_position < rc_old.serp_position THEN 1 ELSE 0 END) as improved,
                SUM(CASE WHEN rc_new.serp_position > rc_old.serp_position THEN 1 ELSE 0 END) as declined
            FROM {$this->membersTable} m
            JOIN st_keywords k ON m.keyword_id = k.id
            JOIN (
                SELECT keyword, serp_position,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks WHERE project_id = ? AND serp_position IS NOT NULL
            ) rc_new ON k.keyword = rc_new.keyword AND rc_new.rn = 1
            JOIN (
                SELECT keyword, serp_position,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks WHERE project_id = ? AND serp_position IS NOT NULL
            ) rc_old ON k.keyword = rc_old.keyword AND rc_old.rn = 2
            WHERE m.group_id = ?
        ";

        $variations = Database::fetch($variationsSql, [$projectId, $projectId, $groupId]);

        return [
            'total_keywords' => $totalKeywords,
            'avg_position' => round((float)($result['avg_position'] ?? 0), 1),
            'top3_count' => (int)($result['top3_count'] ?? 0),
            'top10_count' => (int)($result['top10_count'] ?? 0),
            'top20_count' => (int)($result['top20_count'] ?? 0),
            'beyond20_count' => (int)($result['beyond20_count'] ?? 0),
            'improved_count' => (int)($variations['improved'] ?? 0),
            'declined_count' => (int)($variations['declined'] ?? 0),
        ];
    }

    /**
     * Colori predefiniti per gruppi
     */
    public static function getDefaultColors(): array
    {
        return [
            '#006e96', // Primary Ainstein
            '#00a3d9', // Accent
            '#004d69', // Secondary
            '#ec4899', // Pink
            '#ef4444', // Red
            '#f97316', // Orange
            '#eab308', // Yellow
            '#22c55e', // Green
            '#14b8a6', // Teal
            '#3b82f6', // Blue
        ];
    }
}
