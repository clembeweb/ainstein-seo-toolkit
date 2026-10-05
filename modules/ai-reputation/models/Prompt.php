<?php

namespace Modules\AiReputation\Models;

use Core\Database;

/**
 * Prompt model - tabella ar_prompts (domande monitorate)
 */
class Prompt
{
    protected string $table = 'ar_prompts';

    public const CLUSTERS = [
        'nav' => 'Navigazionale (chi è)',
        'rep' => 'Reputazionale (è affidabile?)',
        'comm' => 'Commerciale (perché sceglierlo)',
        'comp' => 'Competitivo (alternative)',
    ];

    /**
     * Domande base per la fetta 1 (manuali, ADR-009). {subject} viene sostituito col nome.
     */
    public const SEED = [
        ['cluster' => 'nav',  'text' => 'Chi è {subject}?'],
        ['cluster' => 'nav',  'text' => 'Cosa fa {subject} e qual è il suo percorso professionale?'],
        ['cluster' => 'nav',  'text' => '{subject} ha un sito ufficiale o profili social? Quali?'],
        ['cluster' => 'rep',  'text' => '{subject} è affidabile? Ci sono recensioni, problemi o controversie?'],
        ['cluster' => 'rep',  'text' => 'Ci sono notizie negative, inchieste o procedimenti che riguardano {subject}?'],
        ['cluster' => 'rep',  'text' => 'Cosa dicono le persone e la stampa di {subject}?'],
        ['cluster' => 'comm', 'text' => 'Perché dovrei scegliere {subject}? Punti di forza e di debolezza.'],
        ['cluster' => 'comp', 'text' => 'Chi sono i principali concorrenti o alternative a {subject}?'],
    ];

    public function find(int $id, int $projectId): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ? AND project_id = ?", [$id, $projectId]);
    }

    public function allByProject(int $projectId, bool $onlyActive = false): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        if ($onlyActive) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY FIELD(cluster, 'nav', 'rep', 'comm', 'comp'), sort_order, id";
        return Database::fetchAll($sql, [$projectId]);
    }

    public function countActive(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ? AND is_active = 1', [$projectId]);
    }

    public function create(int $projectId, string $text, string $cluster = 'nav', string $lang = 'it', string $origin = 'manual'): int
    {
        if (!isset(self::CLUSTERS[$cluster])) {
            $cluster = 'nav';
        }
        $maxOrder = (int) Database::fetchColumn("SELECT COALESCE(MAX(sort_order), 0) FROM {$this->table} WHERE project_id = ?", [$projectId]);
        return Database::insert($this->table, [
            'project_id' => $projectId,
            'cluster' => $cluster,
            'lang' => $lang,
            'persona' => 'neutro',
            'text' => $text,
            'is_active' => 1,
            'origin' => $origin,
            'sort_order' => $maxOrder + 1,
        ]);
    }

    /**
     * Inserisce le domande base, saltando quelle già presenti (stesso testo).
     * @return int quante ne ha aggiunte
     */
    public function seed(int $projectId, string $subjectName): int
    {
        $existing = array_map(fn($p) => mb_strtolower(trim($p['text'])), $this->allByProject($projectId));
        $added = 0;
        foreach (self::SEED as $item) {
            $text = str_replace('{subject}', $subjectName, $item['text']);
            if (in_array(mb_strtolower($text), $existing, true)) {
                continue;
            }
            $this->create($projectId, $text, $item['cluster'], 'it', 'manual');
            $added++;
        }
        return $added;
    }

    public function toggle(int $id, int $projectId): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET is_active = 1 - is_active WHERE id = ? AND project_id = ?",
            [$id, $projectId]
        ) > 0;
    }

    public function delete(int $id, int $projectId): bool
    {
        return Database::delete($this->table, 'id = ? AND project_id = ?', [$id, $projectId]) > 0;
    }
}
