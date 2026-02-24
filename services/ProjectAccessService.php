<?php

namespace Services;

use Core\Database;

/**
 * ProjectAccessService
 * Servizio centralizzato per l'autorizzazione sui progetti condivisi.
 *
 * Ruoli: owner (proprietario progetto), editor, viewer.
 * L'owner NON e salvato in project_members — si verifica da projects.user_id.
 */
class ProjectAccessService
{
    /**
     * Ottieni il ruolo di un utente per un progetto.
     *
     * @return string|null 'owner', 'editor', 'viewer', oppure null se nessun accesso
     */
    public static function getRole(int $projectId, int $userId): ?string
    {
        // Fast path: controlla se e il proprietario
        $project = Database::fetch(
            "SELECT user_id FROM projects WHERE id = ?",
            [$projectId]
        );

        if (!$project) {
            return null;
        }

        if ((int)$project['user_id'] === $userId) {
            return 'owner';
        }

        // Controlla membership (solo membri che hanno accettato)
        $member = Database::fetch(
            "SELECT role FROM project_members
             WHERE project_id = ? AND user_id = ? AND accepted_at IS NOT NULL",
            [$projectId, $userId]
        );

        return $member ? $member['role'] : null;
    }

    /**
     * L'utente puo visualizzare il progetto? (qualsiasi ruolo)
     */
    public static function canView(int $projectId, int $userId): bool
    {
        return self::getRole($projectId, $userId) !== null;
    }

    /**
     * L'utente puo modificare il progetto? (owner o editor)
     */
    public static function canEdit(int $projectId, int $userId): bool
    {
        $role = self::getRole($projectId, $userId);
        return $role === 'owner' || $role === 'editor';
    }

    /**
     * L'utente e il proprietario del progetto?
     */
    public static function isOwner(int $projectId, int $userId): bool
    {
        return self::getRole($projectId, $userId) === 'owner';
    }

    /**
     * L'utente puo accedere a un modulo specifico del progetto?
     * Owner ha accesso a tutti i moduli. I membri solo ai moduli assegnati.
     */
    public static function canAccessModule(int $projectId, int $userId, string $moduleSlug): bool
    {
        // Fast path: owner ha accesso a tutto
        $project = Database::fetch(
            "SELECT user_id FROM projects WHERE id = ?",
            [$projectId]
        );

        if (!$project) {
            return false;
        }

        if ((int)$project['user_id'] === $userId) {
            return true;
        }

        // Membro: verifica modulo specifico in project_member_modules
        $access = Database::fetch(
            "SELECT pmm.id
             FROM project_member_modules pmm
             JOIN project_members pm ON pm.id = pmm.member_id
             WHERE pm.project_id = ?
               AND pm.user_id = ?
               AND pm.accepted_at IS NOT NULL
               AND pmm.module_slug = ?",
            [$projectId, $userId, $moduleSlug]
        );

        return $access !== null;
    }

    /**
     * Ottieni l'ID del proprietario del progetto (per billing crediti).
     */
    public static function getOwnerId(int $projectId): ?int
    {
        $project = Database::fetch(
            "SELECT user_id FROM projects WHERE id = ?",
            [$projectId]
        );

        return $project ? (int)$project['user_id'] : null;
    }

    /**
     * Ottieni tutti i progetti accessibili da un utente.
     *
     * @return array ['owned' => [...], 'shared' => [...]]
     */
    public static function getAccessibleProjects(int $userId, string $status = 'active'): array
    {
        // Progetti di proprieta
        $owned = Database::fetchAll(
            "SELECT p.*
             FROM projects p
             WHERE p.user_id = ? AND p.status = ?
             ORDER BY p.updated_at DESC",
            [$userId, $status]
        );

        // Progetti condivisi (membro accettato)
        $shared = Database::fetchAll(
            "SELECT p.*, pm.role AS member_role, pm.accepted_at AS member_since
             FROM projects p
             JOIN project_members pm ON pm.project_id = p.id
             WHERE pm.user_id = ?
               AND pm.accepted_at IS NOT NULL
               AND p.status = ?
             ORDER BY pm.accepted_at DESC",
            [$userId, $status]
        );

        return [
            'owned' => $owned,
            'shared' => $shared,
        ];
    }

    /**
     * Ottieni inviti in sospeso per un utente (email).
     * Usato per mostrare banner/notifiche di inviti ricevuti.
     */
    public static function getPendingInvitations(int $userId): array
    {
        // Recupera email utente
        $user = Database::fetch(
            "SELECT email FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            return [];
        }

        return Database::fetchAll(
            "SELECT pi.id, pi.project_id, pi.role, pi.modules, pi.token, pi.created_at, pi.expires_at,
                    p.name AS project_name, p.domain AS project_domain, p.color AS project_color,
                    u.name AS invited_by_name, u.email AS invited_by_email
             FROM project_invitations pi
             JOIN projects p ON p.id = pi.project_id
             JOIN users u ON u.id = pi.invited_by
             WHERE pi.email = ?
               AND pi.accepted_at IS NULL
               AND pi.expires_at > NOW()
             ORDER BY pi.created_at DESC",
            [$user['email']]
        );
    }

    /**
     * Ottieni tutti i membri di un progetto (con info utente e moduli).
     */
    public static function getProjectMembers(int $projectId): array
    {
        $members = Database::fetchAll(
            "SELECT pm.id, pm.user_id, pm.role, pm.accepted_at, pm.created_at,
                    u.name, u.email, u.avatar
             FROM project_members pm
             JOIN users u ON u.id = pm.user_id
             WHERE pm.project_id = ?
             ORDER BY pm.created_at ASC",
            [$projectId]
        );

        // Aggiungi moduli per ogni membro
        foreach ($members as &$member) {
            $modules = Database::fetchAll(
                "SELECT module_slug FROM project_member_modules WHERE member_id = ?",
                [$member['id']]
            );
            $member['module_slugs'] = array_column($modules, 'module_slug');
        }
        unset($member);

        return $members;
    }

    /**
     * Ottieni inviti email in sospeso per un progetto (non scaduti, non accettati).
     */
    public static function getProjectInvitations(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT pi.id, pi.email, pi.role, pi.modules, pi.token, pi.created_at, pi.expires_at,
                    u.name AS invited_by_name
             FROM project_invitations pi
             JOIN users u ON u.id = pi.invited_by
             WHERE pi.project_id = ?
               AND pi.accepted_at IS NULL
               AND pi.expires_at > NOW()
             ORDER BY pi.created_at DESC",
            [$projectId]
        );
    }

    /**
     * Ottieni i moduli a cui un membro ha accesso per un progetto.
     *
     * @return array Array di module slug (es. ['seo-tracking', 'seo-audit'])
     */
    public static function getMemberModules(int $projectId, int $userId): array
    {
        $modules = Database::fetchAll(
            "SELECT pmm.module_slug
             FROM project_member_modules pmm
             JOIN project_members pm ON pm.id = pmm.member_id
             WHERE pm.project_id = ?
               AND pm.user_id = ?
               AND pm.accepted_at IS NOT NULL",
            [$projectId, $userId]
        );

        return array_column($modules, 'module_slug');
    }
}
