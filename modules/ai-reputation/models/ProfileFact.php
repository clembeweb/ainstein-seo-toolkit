<?php

namespace Modules\AiReputation\Models;

use Core\Database;

/**
 * ProfileFact model - tabella ar_profile_facts (righe del profilo, da confermare)
 */
class ProfileFact
{
    public const CATEGORIES = [
        'identity' => 'Identità',
        'activity' => 'Attività',
        'alias' => 'Alias e nomi',
        'person' => 'Persone collegate',
        'fact' => 'Fatti chiave',
        'risk' => 'Temi di rischio',
        'homonym' => 'Omonimi',
        'source' => 'Fonti',
    ];

    public const STATUSES = ['proposed' => 'Da confermare', 'confirmed' => 'Confermata', 'rejected' => 'Rifiutata', 'corrected' => 'Corretta'];

    public function find(int $id, int $projectId): ?array
    {
        return Database::fetch("SELECT * FROM ar_profile_facts WHERE id = ? AND project_id = ?", [$id, $projectId]);
    }

    public function allByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ar_profile_facts WHERE project_id = ? ORDER BY FIELD(category, 'identity', 'activity', 'alias', 'person', 'fact', 'risk', 'homonym', 'source'), sort_order, id",
            [$projectId]
        );
    }

    /** Righe raggruppate per categoria */
    public function grouped(int $projectId): array
    {
        $out = [];
        foreach ($this->allByProject($projectId) as $f) {
            $out[$f['category']][] = $f;
        }
        return $out;
    }

    public function counts(int $projectId): array
    {
        $rows = Database::fetchAll("SELECT status, COUNT(*) n FROM ar_profile_facts WHERE project_id = ? GROUP BY status", [$projectId]);
        $c = ['proposed' => 0, 'confirmed' => 0, 'rejected' => 0, 'corrected' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $c[$r['status']] = (int) $r['n'];
            $c['total'] += (int) $r['n'];
        }
        return $c;
    }

    /** Verità del brand: testi confermati/corretti, per categoria */
    public function truth(int $projectId): array
    {
        $rows = Database::fetchAll(
            "SELECT category, status, text, corrected_text FROM ar_profile_facts WHERE project_id = ? AND status IN ('confirmed', 'corrected') ORDER BY category, sort_order, id",
            [$projectId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[$r['category']][] = $r['status'] === 'corrected' && $r['corrected_text'] !== null ? $r['corrected_text'] : $r['text'];
        }
        return $out;
    }

    /** Errori da monitorare: righe rifiutate (cose che le AI dicono ma non sono vere / non sono lui) */
    public function rejected(int $projectId): array
    {
        return array_column(Database::fetchAll(
            "SELECT text FROM ar_profile_facts WHERE project_id = ? AND status = 'rejected' AND category <> 'homonym' ORDER BY id",
            [$projectId]
        ), 'text');
    }

    public function create(int $projectId, string $category, string $text, ?string $sourceUrl = null, string $origin = 'ai', string $status = 'proposed', ?int $runId = null): int
    {
        if (!isset(self::CATEGORIES[$category])) {
            $category = 'fact';
        }
        $maxOrder = (int) Database::fetchColumn("SELECT COALESCE(MAX(sort_order), 0) FROM ar_profile_facts WHERE project_id = ?", [$projectId]);
        return Database::insert('ar_profile_facts', [
            'project_id' => $projectId,
            'category' => $category,
            'text' => mb_substr(trim($text), 0, 1000),
            'status' => $status,
            'source_url' => $sourceUrl ? mb_substr($sourceUrl, 0, 2000) : null,
            'origin' => $origin,
            'run_id' => $runId,
            'sort_order' => $maxOrder + 1,
        ]);
    }

    public function exists(int $projectId, string $text): bool
    {
        return (bool) Database::fetch("SELECT id FROM ar_profile_facts WHERE project_id = ? AND LOWER(text) = LOWER(?)", [$projectId, trim($text)]);
    }

    public function setStatus(int $id, int $projectId, string $status, ?string $correctedText = null): bool
    {
        if (!isset(self::STATUSES[$status])) {
            return false;
        }
        $data = ['status' => $status];
        if ($status === 'corrected') {
            $data['corrected_text'] = $correctedText !== null ? mb_substr(trim($correctedText), 0, 1000) : null;
        }
        return Database::update('ar_profile_facts', $data, 'id = ? AND project_id = ?', [$id, $projectId]) >= 0;
    }

    public function delete(int $id, int $projectId): bool
    {
        return Database::delete('ar_profile_facts', 'id = ? AND project_id = ?', [$id, $projectId]) > 0;
    }

    /** Cancella le righe AI ancora da confermare (prima di rigenerare il profilo) */
    public function deleteProposedAi(int $projectId): int
    {
        return Database::delete('ar_profile_facts', "project_id = ? AND status = 'proposed' AND origin = 'ai'", [$projectId]);
    }
}
