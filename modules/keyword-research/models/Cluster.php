<?php

namespace Modules\KeywordResearch\Models;

use Core\Database;

class Cluster
{
    protected string $table = 'kr_clusters';

    /**
     * Volume di un cluster contando UNA volta le varianti della stessa ricerca.
     * Google raggruppa "giro in barca a capri", "capri giro in barca", "giri in barca capri" nella stessa
     * keyword e riporta per tutte lo stesso volume/CPC/competizione: sommarle gonfiava i totali
     * (8 righe da 720 → 5.900, prova del 2026-10-16). Due righe sono la stessa ricerca se hanno le stesse
     * parole (senza articoli/preposizioni, al netto di plurali e ordine) E gli stessi numeri.
     */
    public static function dedupedVolume(array $keywords): int
    {
        $seen = [];
        $total = 0;
        foreach ($keywords as $kw) {
            $key = self::variantKey($kw);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $total += (int) ($kw['volume'] ?? 0);
        }
        return $total;
    }

    public static function variantKey(array $kw): string
    {
        $stop = ['a', 'ad', 'al', 'ai', 'agli', 'alla', 'alle', 'allo', 'con', 'da', 'dal', 'dai', 'dalla', 'dalle', 'dallo', 'di', 'del', 'dei', 'della', 'delle', 'dello', 'degli', 'e', 'ed', 'il', 'lo', 'la', 'i', 'gli', 'le', 'in', 'nel', 'nei', 'nella', 'nelle', 'nello', 'per', 'su', 'sul', 'sui', 'sulla', 'sulle', 'un', 'una', 'uno', 'd', 'l', 'the', 'of', 'for', 'to', 'and', 'in', 'on', 'with'];
        $text = mb_strtolower((string) ($kw['text'] ?? ''));
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stems = [];
        foreach ($tokens as $t) {
            if (in_array($t, $stop, true)) {
                continue;
            }
            // Radice grezza: via l'ultima vocale (giro/giri → gir, barca/barche → barc) e una eventuale h finale
            if (mb_strlen($t) > 3) {
                $t = preg_replace('/[aeiou]$/u', '', $t);
                $t = preg_replace('/h$/u', '', $t);
            }
            $stems[$t] = true;
        }
        $stems = array_keys($stems);
        sort($stems);
        return implode(' ', $stems)
            . '|' . (int) ($kw['volume'] ?? 0)
            . '|' . number_format((float) ($kw['low_bid'] ?? 0), 2, '.', '')
            . '|' . number_format((float) ($kw['high_bid'] ?? 0), 2, '.', '')
            . '|' . strtolower((string) ($kw['competition_level'] ?? ''));
    }

    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function findByResearch(int $researchId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE research_id = ? ORDER BY sort_order ASC, total_volume DESC",
            [$researchId]
        );
    }

    public function create(array $data): int
    {
        Database::insert($this->table, [
            'research_id' => $data['research_id'],
            'name' => $data['name'],
            'main_keyword' => $data['main_keyword'],
            'main_volume' => $data['main_volume'] ?? 0,
            'total_volume' => $data['total_volume'] ?? 0,
            'keywords_count' => $data['keywords_count'] ?? 0,
            'intent' => $data['intent'] ?? null,
            'note' => $data['note'] ?? null,
            'suggested_url' => $data['suggested_url'] ?? null,
            'suggested_h1' => $data['suggested_h1'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        return (int) Database::lastInsertId();
    }

    public function getKeywords(int $clusterId): array
    {
        return Database::fetchAll(
            "SELECT * FROM kr_keywords WHERE cluster_id = ? ORDER BY volume DESC",
            [$clusterId]
        );
    }

    public function getExcludedKeywords(int $researchId): array
    {
        return Database::fetchAll(
            "SELECT * FROM kr_keywords WHERE research_id = ? AND is_excluded = 1 ORDER BY volume DESC",
            [$researchId]
        );
    }

    public function getAllKeywords(int $researchId): array
    {
        return Database::fetchAll(
            "SELECT k.*, c.name as cluster_name FROM kr_keywords k LEFT JOIN kr_clusters c ON k.cluster_id = c.id WHERE k.research_id = ? ORDER BY k.volume DESC",
            [$researchId]
        );
    }

    public function saveKeyword(array $data): int
    {
        Database::insert('kr_keywords', [
            'research_id' => $data['research_id'],
            'cluster_id' => $data['cluster_id'] ?? null,
            'text' => $data['text'],
            'volume' => $data['volume'] ?? 0,
            'competition_level' => $data['competition_level'] ?? null,
            'competition_index' => $data['competition_index'] ?? 0,
            'low_bid' => $data['low_bid'] ?? 0,
            'high_bid' => $data['high_bid'] ?? 0,
            'trend' => $data['trend'] ?? 0,
            'intent' => $data['intent'] ?? null,
            'is_main' => $data['is_main'] ?? 0,
            'is_excluded' => $data['is_excluded'] ?? 0,
            'source' => $data['source'] ?? 'keysuggest',
        ]);
        return (int) Database::lastInsertId();
    }

    public static function countByUser(int $userId): int
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM kr_clusters c JOIN kr_researches r ON c.research_id = r.id WHERE r.user_id = ?",
            [$userId]
        );
        return (int) ($result['cnt'] ?? 0);
    }
}
