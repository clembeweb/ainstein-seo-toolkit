<?php

namespace Services;

use Core\Database;
use Core\Settings;
use Core\Logger;

/**
 * ProjectSharingService
 * Servizio centralizzato per le mutazioni di condivisione progetto:
 * inviti, accettazione, rifiuto, rimozione, aggiornamento, pulizia.
 *
 * Operazioni read-only → ProjectAccessService
 * Operazioni write     → ProjectSharingService (questo file)
 */
class ProjectSharingService
{
    /**
     * Invita un utente a un progetto.
     *
     * Se l'utente esiste nel sistema → membership interna (project_members).
     * Se l'utente non esiste → invito via email con token (project_invitations).
     *
     * @param int    $projectId   ID del progetto
     * @param string $email       Email dell'invitato
     * @param string $role        Ruolo: 'editor' o 'viewer'
     * @param array  $moduleSlugs Moduli a cui dare accesso
     * @param int    $invitedBy   ID dell'utente che invita (owner)
     * @return array ['success' => bool, 'message' => string, 'type' => string]
     */
    public static function invite(int $projectId, string $email, string $role, array $moduleSlugs, int $invitedBy): array
    {
        // Valida ruolo
        if (!in_array($role, ['editor', 'viewer'], true)) {
            return ['success' => false, 'message' => 'Ruolo non valido. Usa "editor" o "viewer".', 'type' => 'error'];
        }

        // Recupera info progetto e owner
        $project = Database::fetch(
            "SELECT p.id, p.name, p.user_id, u.email AS owner_email, u.name AS owner_name
             FROM projects p
             JOIN users u ON u.id = p.user_id
             WHERE p.id = ?",
            [$projectId]
        );

        if (!$project) {
            return ['success' => false, 'message' => 'Progetto non trovato.', 'type' => 'error'];
        }

        // L'owner non puo invitare se stesso
        if (strcasecmp(trim($email), trim($project['owner_email'])) === 0) {
            return ['success' => false, 'message' => 'Non puoi invitare te stesso al tuo progetto.', 'type' => 'error'];
        }

        // Controlla se l'utente esiste nel sistema
        $targetUser = Database::fetch(
            "SELECT id, email, name FROM users WHERE LOWER(email) = LOWER(?)",
            [trim($email)]
        );

        if ($targetUser) {
            // --- INVITO INTERNO (utente gia registrato) ---

            // Controlla che non sia gia il proprietario del progetto
            if ((int)$targetUser['id'] === (int)$project['user_id']) {
                return ['success' => false, 'message' => 'Questo utente e gia il proprietario del progetto.', 'type' => 'error'];
            }

            // Controlla che non sia gia membro
            $existingMember = Database::fetch(
                "SELECT id FROM project_members WHERE project_id = ? AND user_id = ?",
                [$projectId, $targetUser['id']]
            );

            if ($existingMember) {
                return ['success' => false, 'message' => 'Questo utente e gia membro del progetto.', 'type' => 'error'];
            }

            // Inserisci membership (pending: accepted_at = NULL)
            $memberId = Database::insert('project_members', [
                'project_id' => $projectId,
                'user_id'    => $targetUser['id'],
                'role'       => $role,
                'invited_by' => $invitedBy,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$memberId) {
                return ['success' => false, 'message' => 'Errore durante la creazione della membership.', 'type' => 'error'];
            }

            // Inserisci moduli assegnati
            self::insertMemberModules($memberId, $moduleSlugs);

            $roleLabel = $role === 'editor' ? 'Editor' : 'Visualizzatore';

            Logger::channel('sharing')->info('Invito interno creato', [
                'project_id' => $projectId,
                'user_id'    => $targetUser['id'],
                'role'       => $role,
                'invited_by' => $invitedBy,
            ]);

            return [
                'success' => true,
                'message' => "Invito inviato a {$targetUser['name']} come {$roleLabel}.",
                'type'    => 'internal',
            ];
        }

        // --- INVITO VIA EMAIL (utente non registrato) ---

        // Controlla se esiste gia un invito attivo per questa email/progetto
        $existingInvitation = Database::fetch(
            "SELECT id FROM project_invitations
             WHERE project_id = ? AND LOWER(email) = LOWER(?)
               AND accepted_at IS NULL AND expires_at > NOW()",
            [$projectId, trim($email)]
        );

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        $modulesJson = json_encode(array_values($moduleSlugs));

        if ($existingInvitation) {
            // UPSERT: aggiorna invito esistente
            Database::execute(
                "UPDATE project_invitations
                 SET role = ?, modules = ?, token = ?, invited_by = ?, expires_at = ?, created_at = NOW()
                 WHERE id = ?",
                [$role, $modulesJson, $token, $invitedBy, $expiresAt, $existingInvitation['id']]
            );
        } else {
            // Nuovo invito
            Database::insert('project_invitations', [
                'project_id' => $projectId,
                'email'      => trim($email),
                'role'       => $role,
                'modules'    => $modulesJson,
                'token'      => $token,
                'invited_by' => $invitedBy,
                'expires_at' => $expiresAt,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Invia email di invito
        $appUrl = rtrim(Settings::get('app_url', 'https://ainstein.it'), '/');
        $acceptUrl = $appUrl . '/invitations/accept?token=' . urlencode($token);
        $roleLabel = $role === 'editor' ? 'Editor' : 'Visualizzatore';
        $inviterName = $project['owner_name'] ?: $project['owner_email'];

        $emailResult = EmailService::sendTemplate(
            trim($email),
            "Invito a collaborare su {$project['name']}",
            'project-invite',
            [
                'project_name' => $project['name'],
                'inviter_name' => $inviterName,
                'role'         => $roleLabel,
                'accept_url'   => $acceptUrl,
            ]
        );

        if (!$emailResult['success']) {
            Logger::channel('sharing')->warning('Email invito fallita', [
                'project_id' => $projectId,
                'email'      => trim($email),
                'error'      => $emailResult['message'],
            ]);
        }

        Logger::channel('sharing')->info('Invito email creato', [
            'project_id' => $projectId,
            'email'      => trim($email),
            'role'       => $role,
            'invited_by' => $invitedBy,
        ]);

        return [
            'success' => true,
            'message' => "Invito inviato a {$email} via email come {$roleLabel}.",
            'type'    => 'email',
        ];
    }

    /**
     * Accetta un invito interno (membership pending).
     *
     * @param int $memberId ID del record in project_members
     * @param int $userId   ID dell'utente che accetta
     * @return array ['success' => bool, 'message' => string]
     */
    public static function acceptInternal(int $memberId, int $userId): array
    {
        $member = Database::fetch(
            "SELECT id, project_id, user_id, accepted_at FROM project_members WHERE id = ?",
            [$memberId]
        );

        if (!$member) {
            return ['success' => false, 'message' => 'Invito non trovato.'];
        }

        if ((int)$member['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Non sei autorizzato ad accettare questo invito.'];
        }

        if ($member['accepted_at'] !== null) {
            return ['success' => false, 'message' => 'Questo invito e gia stato accettato.'];
        }

        Database::execute(
            "UPDATE project_members SET accepted_at = NOW() WHERE id = ?",
            [$memberId]
        );

        Logger::channel('sharing')->info('Invito interno accettato', [
            'member_id'  => $memberId,
            'project_id' => $member['project_id'],
            'user_id'    => $userId,
        ]);

        return [
            'success'    => true,
            'message'    => 'Invito accettato con successo.',
            'project_id' => (int)$member['project_id'],
        ];
    }

    /**
     * Rifiuta un invito interno (elimina la membership).
     *
     * @param int $memberId ID del record in project_members
     * @param int $userId   ID dell'utente che rifiuta
     * @return array ['success' => bool, 'message' => string]
     */
    public static function declineInternal(int $memberId, int $userId): array
    {
        $member = Database::fetch(
            "SELECT id, project_id, user_id, accepted_at FROM project_members WHERE id = ?",
            [$memberId]
        );

        if (!$member) {
            return ['success' => false, 'message' => 'Invito non trovato.'];
        }

        if ((int)$member['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Non sei autorizzato a rifiutare questo invito.'];
        }

        if ($member['accepted_at'] !== null) {
            return ['success' => false, 'message' => 'Questo invito e gia stato accettato e non puo essere rifiutato.'];
        }

        // DELETE cascade rimuove anche project_member_modules
        Database::execute(
            "DELETE FROM project_members WHERE id = ?",
            [$memberId]
        );

        Logger::channel('sharing')->info('Invito interno rifiutato', [
            'member_id'  => $memberId,
            'project_id' => $member['project_id'],
            'user_id'    => $userId,
        ]);

        return ['success' => true, 'message' => 'Invito rifiutato.'];
    }

    /**
     * Accetta un invito via email tramite token.
     *
     * @param string $token  Token univoco dell'invito
     * @param int    $userId ID dell'utente autenticato che accetta
     * @return array ['success' => bool, 'message' => string, 'project_id' => int|null]
     */
    public static function acceptByToken(string $token, int $userId): array
    {
        // Trova invito per token
        $invitation = Database::fetch(
            "SELECT pi.*, p.name AS project_name
             FROM project_invitations pi
             JOIN projects p ON p.id = pi.project_id
             WHERE pi.token = ?",
            [$token]
        );

        if (!$invitation) {
            return ['success' => false, 'message' => 'Invito non trovato o non valido.', 'project_id' => null];
        }

        // Controlla se gia accettato
        if ($invitation['accepted_at'] !== null) {
            return ['success' => false, 'message' => 'Questo invito e gia stato accettato.', 'project_id' => null];
        }

        // Controlla scadenza
        if (strtotime($invitation['expires_at']) < time()) {
            return ['success' => false, 'message' => 'Questo invito e scaduto. Chiedi un nuovo invito al proprietario del progetto.', 'project_id' => null];
        }

        // Verifica che l'email dell'utente corrisponda all'invito
        $user = Database::fetch(
            "SELECT email FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Utente non trovato.', 'project_id' => null];
        }

        if (strcasecmp(trim($user['email']), trim($invitation['email'])) !== 0) {
            return [
                'success'    => false,
                'message'    => 'L\'indirizzo email del tuo account non corrisponde a quello dell\'invito.',
                'project_id' => null,
            ];
        }

        // Controlla se gia membro del progetto
        $existingMember = Database::fetch(
            "SELECT id FROM project_members WHERE project_id = ? AND user_id = ?",
            [$invitation['project_id'], $userId]
        );

        if ($existingMember) {
            // Segna invito come accettato comunque
            Database::execute(
                "UPDATE project_invitations SET accepted_at = NOW() WHERE id = ?",
                [$invitation['id']]
            );

            return [
                'success'    => true,
                'message'    => 'Sei gia membro di questo progetto.',
                'project_id' => (int)$invitation['project_id'],
            ];
        }

        // Crea membership (gia accettata)
        $memberId = Database::insert('project_members', [
            'project_id'  => $invitation['project_id'],
            'user_id'     => $userId,
            'role'        => $invitation['role'],
            'invited_by'  => $invitation['invited_by'],
            'accepted_at' => date('Y-m-d H:i:s'),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        if (!$memberId) {
            return ['success' => false, 'message' => 'Errore durante la creazione della membership.', 'project_id' => null];
        }

        // Inserisci moduli dall'invito
        $moduleSlugs = json_decode($invitation['modules'] ?? '[]', true) ?: [];
        self::insertMemberModules($memberId, $moduleSlugs);

        // Segna invito come accettato
        Database::execute(
            "UPDATE project_invitations SET accepted_at = NOW() WHERE id = ?",
            [$invitation['id']]
        );

        Logger::channel('sharing')->info('Invito email accettato', [
            'invitation_id' => $invitation['id'],
            'project_id'    => $invitation['project_id'],
            'user_id'       => $userId,
        ]);

        $roleLabel = $invitation['role'] === 'editor' ? 'Editor' : 'Visualizzatore';

        return [
            'success'    => true,
            'message'    => "Hai accettato l'invito per il progetto \"{$invitation['project_name']}\" come {$roleLabel}.",
            'project_id' => (int)$invitation['project_id'],
        ];
    }

    /**
     * Rimuovi un membro dal progetto.
     *
     * @param int $projectId    ID del progetto
     * @param int $memberUserId ID dell'utente da rimuovere
     * @return array ['success' => bool, 'message' => string]
     */
    public static function removeMember(int $projectId, int $memberUserId): array
    {
        $member = Database::fetch(
            "SELECT id, user_id FROM project_members WHERE project_id = ? AND user_id = ?",
            [$projectId, $memberUserId]
        );

        if (!$member) {
            return ['success' => false, 'message' => 'Membro non trovato nel progetto.'];
        }

        // DELETE cascade rimuove anche project_member_modules
        Database::execute(
            "DELETE FROM project_members WHERE project_id = ? AND user_id = ?",
            [$projectId, $memberUserId]
        );

        Logger::channel('sharing')->info('Membro rimosso dal progetto', [
            'project_id' => $projectId,
            'user_id'    => $memberUserId,
        ]);

        return ['success' => true, 'message' => 'Membro rimosso dal progetto.'];
    }

    /**
     * Annulla un invito email in sospeso.
     *
     * @param int $invitationId ID dell'invito
     * @param int $projectId    ID del progetto (per validazione)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function cancelInvitation(int $invitationId, int $projectId): array
    {
        $invitation = Database::fetch(
            "SELECT id, email FROM project_invitations WHERE id = ? AND project_id = ?",
            [$invitationId, $projectId]
        );

        if (!$invitation) {
            return ['success' => false, 'message' => 'Invito non trovato.'];
        }

        Database::execute(
            "DELETE FROM project_invitations WHERE id = ?",
            [$invitationId]
        );

        Logger::channel('sharing')->info('Invito email annullato', [
            'invitation_id' => $invitationId,
            'project_id'    => $projectId,
            'email'         => $invitation['email'],
        ]);

        return ['success' => true, 'message' => 'Invito annullato.'];
    }

    /**
     * Aggiorna ruolo e moduli di un membro del progetto.
     *
     * @param int    $projectId    ID del progetto
     * @param int    $memberUserId ID dell'utente membro
     * @param string $role         Nuovo ruolo: 'editor' o 'viewer'
     * @param array  $moduleSlugs  Nuovi moduli assegnati
     * @return array ['success' => bool, 'message' => string]
     */
    public static function updateMember(int $projectId, int $memberUserId, string $role, array $moduleSlugs): array
    {
        // Valida ruolo
        if (!in_array($role, ['editor', 'viewer'], true)) {
            return ['success' => false, 'message' => 'Ruolo non valido. Usa "editor" o "viewer".'];
        }

        $member = Database::fetch(
            "SELECT id FROM project_members WHERE project_id = ? AND user_id = ?",
            [$projectId, $memberUserId]
        );

        if (!$member) {
            return ['success' => false, 'message' => 'Membro non trovato nel progetto.'];
        }

        // Aggiorna ruolo
        Database::execute(
            "UPDATE project_members SET role = ? WHERE id = ?",
            [$role, $member['id']]
        );

        // Replace strategy: elimina tutti i moduli e re-inserisci
        Database::execute(
            "DELETE FROM project_member_modules WHERE member_id = ?",
            [$member['id']]
        );

        self::insertMemberModules((int)$member['id'], $moduleSlugs);

        $roleLabel = $role === 'editor' ? 'Editor' : 'Visualizzatore';

        Logger::channel('sharing')->info('Membro aggiornato', [
            'project_id' => $projectId,
            'user_id'    => $memberUserId,
            'role'       => $role,
            'modules'    => $moduleSlugs,
        ]);

        return ['success' => true, 'message' => "Ruolo aggiornato a {$roleLabel}."];
    }

    /**
     * Elimina inviti email scaduti e non accettati.
     *
     * @return int Numero di inviti eliminati
     */
    public static function cleanupExpiredInvitations(): int
    {
        $count = Database::execute(
            "DELETE FROM project_invitations WHERE accepted_at IS NULL AND expires_at < NOW()"
        );

        if ($count > 0) {
            Logger::channel('sharing')->info('Inviti scaduti eliminati', [
                'count' => $count,
            ]);
        }

        return $count;
    }

    // ─────────────────────────────────────────────
    // Metodi privati
    // ─────────────────────────────────────────────

    /**
     * Inserisci moduli assegnati a un membro.
     *
     * @param int   $memberId    ID del record in project_members
     * @param array $moduleSlugs Array di slug modulo
     */
    private static function insertMemberModules(int $memberId, array $moduleSlugs): void
    {
        foreach ($moduleSlugs as $slug) {
            $slug = trim($slug);
            if ($slug === '') {
                continue;
            }

            Database::insert('project_member_modules', [
                'member_id'   => $memberId,
                'module_slug' => $slug,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
