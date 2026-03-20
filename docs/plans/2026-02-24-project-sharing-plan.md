# Project Sharing Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Allow project owners to share their global projects with other users, with per-module granularity and Owner/Editor/Viewer roles.

**Architecture:** Three new tables (`project_members`, `project_member_modules`, `project_invitations`) + centralized `ProjectAccessService` that replaces user_id-only checks. Hybrid invitation system (internal for existing users, email for new). Credits always from owner.

**Tech Stack:** PHP 8+, MySQL, Tailwind CSS, Alpine.js, HTMX (existing stack)

**Design Doc:** `docs/plans/2026-02-24-project-sharing-design.md`

---

## Task 1: Database Migration — Create Tables

**Files:**
- Create: `database/migrations/2026_02_24_project_sharing.sql`

**Step 1: Write the migration SQL**

```sql
-- Project Sharing Tables
-- Run: mysql -u root seo_toolkit < database/migrations/2026_02_24_project_sharing.sql

CREATE TABLE IF NOT EXISTS project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('editor','viewer') NOT NULL DEFAULT 'viewer',
    invited_by INT NOT NULL,
    accepted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_project_user (project_id, user_id),
    INDEX idx_user (user_id),
    INDEX idx_project (project_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_member_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    module_slug VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_member_module (member_id, module_slug),
    FOREIGN KEY (member_id) REFERENCES project_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('editor','viewer') NOT NULL DEFAULT 'viewer',
    modules JSON NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    invited_by INT NOT NULL,
    accepted_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_project_email (project_id, email),
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Step 2: Run migration locally**

```bash
mysql -u root seo_toolkit < database/migrations/2026_02_24_project_sharing.sql
```

Expected: Query OK for all 3 tables.

**Step 3: Verify tables exist**

```bash
mysql -u root seo_toolkit -e "SHOW TABLES LIKE 'project_%';"
```

Expected: `project_invitations`, `project_member_modules`, `project_members`

**Step 4: Commit**

```bash
git add database/migrations/2026_02_24_project_sharing.sql
git commit -m "feat: add project sharing database tables (members, modules, invitations)"
```

---

## Task 2: ProjectAccessService — Core Authorization

**Files:**
- Create: `services/ProjectAccessService.php`

**Context:** This is the heart of the feature. Every controller will call this service to check access. Must be simple, well-tested, and efficient.

**Reference:** `core/Database.php` for query patterns (fetch at line 180, fetchAll at 171). `core/Models/GlobalProject.php` line 161 for the existing `find()` pattern.

**Step 1: Create the service**

```php
<?php

namespace Services;

use Core\Database;

class ProjectAccessService
{
    /**
     * Get the role of a user on a project.
     * Returns 'owner', 'editor', 'viewer', or null (no access).
     */
    public static function getRole(int $projectId, int $userId): ?string
    {
        // Check if owner
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

        // Check if member (accepted)
        $member = Database::fetch(
            "SELECT role FROM project_members WHERE project_id = ? AND user_id = ? AND accepted_at IS NOT NULL",
            [$projectId, $userId]
        );

        return $member ? $member['role'] : null;
    }

    /**
     * Check if user can view the project (any role).
     */
    public static function canView(int $projectId, int $userId): bool
    {
        return self::getRole($projectId, $userId) !== null;
    }

    /**
     * Check if user can edit (owner or editor).
     */
    public static function canEdit(int $projectId, int $userId): bool
    {
        $role = self::getRole($projectId, $userId);
        return in_array($role, ['owner', 'editor'], true);
    }

    /**
     * Check if user is the owner.
     */
    public static function isOwner(int $projectId, int $userId): bool
    {
        return self::getRole($projectId, $userId) === 'owner';
    }

    /**
     * Check if user can access a specific module in the project.
     * Owner can access all modules. Members need explicit module grant.
     */
    public static function canAccessModule(int $projectId, int $userId, string $moduleSlug): bool
    {
        $role = self::getRole($projectId, $userId);

        if ($role === null) {
            return false;
        }

        // Owner can access all modules
        if ($role === 'owner') {
            return true;
        }

        // Members need explicit module access
        $access = Database::fetch(
            "SELECT pmm.id FROM project_member_modules pmm
             JOIN project_members pm ON pm.id = pmm.member_id
             WHERE pm.project_id = ? AND pm.user_id = ? AND pmm.module_slug = ?
             AND pm.accepted_at IS NOT NULL",
            [$projectId, $userId, $moduleSlug]
        );

        return $access !== null;
    }

    /**
     * Get the owner's user_id for a project (for credit billing).
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
     * Get all projects accessible by a user (owned + shared).
     * Returns array with project data + 'role' field.
     */
    public static function getAccessibleProjects(int $userId, string $status = 'active'): array
    {
        $statusClause = $status !== 'all' ? "AND p.status = ?" : "";
        $params = [$userId];
        if ($status !== 'all') {
            $params[] = $status;
        }

        // Owned projects
        $owned = Database::fetchAll(
            "SELECT p.*, 'owner' as access_role FROM projects p
             WHERE p.user_id = ? {$statusClause}
             ORDER BY p.created_at DESC",
            $params
        );

        // Shared projects (accepted only)
        $shared = Database::fetchAll(
            "SELECT p.*, pm.role as access_role FROM projects p
             JOIN project_members pm ON pm.project_id = p.id
             WHERE pm.user_id = ? AND pm.accepted_at IS NOT NULL {$statusClause}
             ORDER BY p.created_at DESC",
            $params
        );

        return [
            'owned' => $owned,
            'shared' => $shared,
        ];
    }

    /**
     * Get pending invitations for a user (by their email).
     */
    public static function getPendingInvitations(int $userId): array
    {
        return Database::fetchAll(
            "SELECT pm.*, p.name as project_name, p.domain as project_domain, p.color as project_color,
                    u.name as invited_by_name, u.email as invited_by_email
             FROM project_members pm
             JOIN projects p ON p.id = pm.project_id
             JOIN users u ON u.id = pm.invited_by
             WHERE pm.user_id = ? AND pm.accepted_at IS NULL
             ORDER BY pm.created_at DESC",
            [$userId]
        );
    }

    /**
     * Get members of a project (for settings page).
     */
    public static function getProjectMembers(int $projectId): array
    {
        $members = Database::fetchAll(
            "SELECT pm.*, u.name as user_name, u.email as user_email, u.avatar as user_avatar
             FROM project_members pm
             JOIN users u ON u.id = pm.user_id
             WHERE pm.project_id = ?
             ORDER BY pm.accepted_at IS NULL DESC, pm.created_at ASC",
            [$projectId]
        );

        // Attach modules for each member
        foreach ($members as &$member) {
            $member['modules'] = Database::fetchAll(
                "SELECT module_slug FROM project_member_modules WHERE member_id = ?",
                [$member['id']]
            );
            $member['module_slugs'] = array_column($member['modules'], 'module_slug');
        }

        return $members;
    }

    /**
     * Get pending email invitations for a project.
     */
    public static function getProjectInvitations(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT pi.*, u.name as invited_by_name
             FROM project_invitations pi
             JOIN users u ON u.id = pi.invited_by
             WHERE pi.project_id = ? AND pi.accepted_at IS NULL AND pi.expires_at > NOW()
             ORDER BY pi.created_at DESC",
            [$projectId]
        );
    }

    /**
     * Get modules a member has access to in a project.
     */
    public static function getMemberModules(int $projectId, int $userId): array
    {
        return Database::fetchAll(
            "SELECT pmm.module_slug FROM project_member_modules pmm
             JOIN project_members pm ON pm.id = pmm.member_id
             WHERE pm.project_id = ? AND pm.user_id = ? AND pm.accepted_at IS NOT NULL",
            [$projectId, $userId]
        );
    }
}
```

**Step 2: Verify syntax**

```bash
php -l services/ProjectAccessService.php
```

Expected: No syntax errors detected

**Step 3: Commit**

```bash
git add services/ProjectAccessService.php
git commit -m "feat: add ProjectAccessService for centralized sharing authorization"
```

---

## Task 3: ProjectSharingService — Invitation Logic

**Files:**
- Create: `services/ProjectSharingService.php`

**Context:** Handles invite, accept, decline, remove, update operations. Separated from ProjectAccessService (reads) to keep concerns clean.

**Reference:** `services/EmailService.php` line 141 for `sendTemplate()`. `core/Database.php` for insert/update/delete.

**Step 1: Create the service**

```php
<?php

namespace Services;

use Core\Database;
use Core\Auth;

class ProjectSharingService
{
    /**
     * Invite a user to a project.
     * If user exists: create project_members record (pending).
     * If not: create project_invitations record + send email.
     *
     * @return array{success: bool, message: string, type: string}
     */
    public static function invite(int $projectId, string $email, string $role, array $moduleSlugs, int $invitedBy): array
    {
        // Validate role
        if (!in_array($role, ['editor', 'viewer'], true)) {
            return ['success' => false, 'message' => 'Ruolo non valido', 'type' => 'error'];
        }

        // Can't invite yourself
        $owner = Database::fetch("SELECT user_id FROM projects WHERE id = ?", [$projectId]);
        if (!$owner) {
            return ['success' => false, 'message' => 'Progetto non trovato', 'type' => 'error'];
        }

        $inviterUser = Database::fetch("SELECT email FROM users WHERE id = ?", [$invitedBy]);
        if ($inviterUser && strtolower($inviterUser['email']) === strtolower($email)) {
            return ['success' => false, 'message' => 'Non puoi invitare te stesso', 'type' => 'error'];
        }

        // Check if already a member
        $existingUser = Database::fetch("SELECT id FROM users WHERE email = ?", [strtolower($email)]);

        if ($existingUser) {
            // Check if owner
            if ((int)$owner['user_id'] === (int)$existingUser['id']) {
                return ['success' => false, 'message' => 'Questo utente e gia il proprietario del progetto', 'type' => 'error'];
            }

            // Check if already a member
            $existingMember = Database::fetch(
                "SELECT id FROM project_members WHERE project_id = ? AND user_id = ?",
                [$projectId, $existingUser['id']]
            );

            if ($existingMember) {
                return ['success' => false, 'message' => 'Questo utente ha gia accesso al progetto', 'type' => 'error'];
            }

            // Create pending member
            $memberId = Database::insert('project_members', [
                'project_id' => $projectId,
                'user_id' => $existingUser['id'],
                'role' => $role,
                'invited_by' => $invitedBy,
                'accepted_at' => null,
            ]);

            // Add module access
            foreach ($moduleSlugs as $slug) {
                Database::insert('project_member_modules', [
                    'member_id' => $memberId,
                    'module_slug' => $slug,
                ]);
            }

            return ['success' => true, 'message' => 'Invito inviato a ' . $email, 'type' => 'internal'];
        }

        // User doesn't exist — check for existing invitation
        $existingInvite = Database::fetch(
            "SELECT id FROM project_invitations WHERE project_id = ? AND email = ?",
            [$projectId, strtolower($email)]
        );

        if ($existingInvite) {
            // Update existing invitation (UPSERT behavior)
            Database::update('project_invitations', [
                'role' => $role,
                'modules' => json_encode($moduleSlugs),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            ], 'id = ?', [$existingInvite['id']]);

            return ['success' => true, 'message' => 'Invito aggiornato per ' . $email, 'type' => 'email_updated'];
        }

        // Create new email invitation
        $token = bin2hex(random_bytes(32));

        Database::insert('project_invitations', [
            'project_id' => $projectId,
            'email' => strtolower($email),
            'role' => $role,
            'modules' => json_encode($moduleSlugs),
            'token' => $token,
            'invited_by' => $invitedBy,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
        ]);

        // Send invitation email
        $project = Database::fetch("SELECT name FROM projects WHERE id = ?", [$projectId]);
        $inviter = Database::fetch("SELECT name, email FROM users WHERE id = ?", [$invitedBy]);

        EmailService::sendTemplate($email, 'Sei stato invitato a collaborare su ' . $project['name'], 'project-invite', [
            'project_name' => $project['name'],
            'inviter_name' => $inviter['name'] ?? $inviter['email'],
            'role' => $role === 'editor' ? 'Editor' : 'Visualizzatore',
            'accept_url' => rtrim(\Core\Settings::get('app_url', 'https://ainstein.it'), '/') . '/invite/accept?token=' . $token,
        ]);

        return ['success' => true, 'message' => 'Invito inviato via email a ' . $email, 'type' => 'email'];
    }

    /**
     * Accept a pending invitation (internal — user exists).
     */
    public static function acceptInternal(int $memberId, int $userId): array
    {
        $member = Database::fetch(
            "SELECT * FROM project_members WHERE id = ? AND user_id = ? AND accepted_at IS NULL",
            [$memberId, $userId]
        );

        if (!$member) {
            return ['success' => false, 'message' => 'Invito non trovato o gia accettato'];
        }

        Database::update('project_members', [
            'accepted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$memberId]);

        return ['success' => true, 'message' => 'Invito accettato'];
    }

    /**
     * Decline a pending invitation (internal — user exists).
     */
    public static function declineInternal(int $memberId, int $userId): array
    {
        $member = Database::fetch(
            "SELECT * FROM project_members WHERE id = ? AND user_id = ? AND accepted_at IS NULL",
            [$memberId, $userId]
        );

        if (!$member) {
            return ['success' => false, 'message' => 'Invito non trovato'];
        }

        Database::execute(
            "DELETE FROM project_members WHERE id = ?",
            [$memberId]
        );

        return ['success' => true, 'message' => 'Invito rifiutato'];
    }

    /**
     * Accept an email invitation via token.
     * Called after user registers/logs in.
     */
    public static function acceptByToken(string $token, int $userId): array
    {
        $invite = Database::fetch(
            "SELECT * FROM project_invitations WHERE token = ? AND accepted_at IS NULL",
            [$token]
        );

        if (!$invite) {
            return ['success' => false, 'message' => 'Invito non trovato'];
        }

        if (strtotime($invite['expires_at']) < time()) {
            return ['success' => false, 'message' => 'Invito scaduto. Chiedi al proprietario di inviarti un nuovo invito.'];
        }

        // Verify email matches
        $user = Database::fetch("SELECT email FROM users WHERE id = ?", [$userId]);
        if (strtolower($user['email']) !== strtolower($invite['email'])) {
            return ['success' => false, 'message' => 'Questo invito e destinato a un altro indirizzo email (' . $invite['email'] . ')'];
        }

        // Check if already a member
        $existing = Database::fetch(
            "SELECT id FROM project_members WHERE project_id = ? AND user_id = ?",
            [$invite['project_id'], $userId]
        );

        if ($existing) {
            // Mark invite as accepted and clean up
            Database::update('project_invitations', ['accepted_at' => date('Y-m-d H:i:s')], 'id = ?', [$invite['id']]);
            return ['success' => true, 'message' => 'Hai gia accesso a questo progetto'];
        }

        // Create member record
        $memberId = Database::insert('project_members', [
            'project_id' => $invite['project_id'],
            'user_id' => $userId,
            'role' => $invite['role'],
            'invited_by' => $invite['invited_by'],
            'accepted_at' => date('Y-m-d H:i:s'),
        ]);

        // Add module access
        $modules = json_decode($invite['modules'], true) ?: [];
        foreach ($modules as $slug) {
            Database::insert('project_member_modules', [
                'member_id' => $memberId,
                'module_slug' => $slug,
            ]);
        }

        // Mark invitation as accepted
        Database::update('project_invitations', ['accepted_at' => date('Y-m-d H:i:s')], 'id = ?', [$invite['id']]);

        return ['success' => true, 'message' => 'Invito accettato! Ora hai accesso al progetto.', 'project_id' => $invite['project_id']];
    }

    /**
     * Remove a member from a project (owner only).
     */
    public static function removeMember(int $projectId, int $memberUserId): array
    {
        Database::execute(
            "DELETE FROM project_members WHERE project_id = ? AND user_id = ?",
            [$projectId, $memberUserId]
        );

        return ['success' => true, 'message' => 'Membro rimosso dal progetto'];
    }

    /**
     * Cancel an email invitation (owner only).
     */
    public static function cancelInvitation(int $invitationId, int $projectId): array
    {
        Database::execute(
            "DELETE FROM project_invitations WHERE id = ? AND project_id = ?",
            [$invitationId, $projectId]
        );

        return ['success' => true, 'message' => 'Invito annullato'];
    }

    /**
     * Update a member's role and modules.
     */
    public static function updateMember(int $projectId, int $memberUserId, string $role, array $moduleSlugs): array
    {
        $member = Database::fetch(
            "SELECT id FROM project_members WHERE project_id = ? AND user_id = ?",
            [$projectId, $memberUserId]
        );

        if (!$member) {
            return ['success' => false, 'message' => 'Membro non trovato'];
        }

        // Update role
        Database::update('project_members', ['role' => $role], 'id = ?', [$member['id']]);

        // Replace modules: delete all, re-insert
        Database::execute("DELETE FROM project_member_modules WHERE member_id = ?", [$member['id']]);
        foreach ($moduleSlugs as $slug) {
            Database::insert('project_member_modules', [
                'member_id' => $member['id'],
                'module_slug' => $slug,
            ]);
        }

        return ['success' => true, 'message' => 'Permessi aggiornati'];
    }

    /**
     * Clean up expired invitations (call from cron).
     */
    public static function cleanupExpiredInvitations(): int
    {
        return Database::execute(
            "DELETE FROM project_invitations WHERE expires_at < NOW() AND accepted_at IS NULL"
        );
    }
}
```

**Step 2: Verify syntax**

```bash
php -l services/ProjectSharingService.php
```

Expected: No syntax errors detected

**Step 3: Commit**

```bash
git add services/ProjectSharingService.php
git commit -m "feat: add ProjectSharingService for invite/accept/remove operations"
```

---

## Task 4: Email Template for Invitations

**Files:**
- Create: `shared/views/emails/project-invite.php`

**Context:** Email templates live in `shared/views/emails/`. Called via `EmailService::sendTemplate()`. Variables available: `$project_name`, `$inviter_name`, `$role`, `$accept_url`.

**Step 1: Create the email template**

The template should be a simple HTML email with:
- Header: "Sei stato invitato a collaborare"
- Body: "[inviter_name] ti ha invitato come [role] nel progetto [project_name] su Ainstein."
- CTA button: "Accetta Invito" → links to `$accept_url`
- Footer: "Questo invito scade tra 7 giorni."

Follow the existing email template style in `shared/views/emails/`. Use inline CSS for email compatibility.

**Step 2: Verify syntax**

```bash
php -l shared/views/emails/project-invite.php
```

**Step 3: Commit**

```bash
git add shared/views/emails/project-invite.php
git commit -m "feat: add project invitation email template"
```

---

## Task 5: GlobalProject Model — Add Shared Access Methods

**Files:**
- Modify: `core/Models/GlobalProject.php` (lines 161-190)

**Context:** The `find()` method at line 161 only checks `user_id`. We need a new `findAccessible()` method that also matches shared members. The `allByUser()` method at line 177 needs a companion that includes shared projects.

**Step 1: Add `findAccessible()` method**

Add after the existing `find()` method (after line 172):

```php
/**
 * Find a project accessible by the user (owner or accepted member).
 */
public function findAccessible(int $id, int $userId): ?array
{
    // First try as owner (fast path)
    $project = Database::fetch(
        "SELECT p.*, 'owner' as access_role FROM {$this->table} p WHERE p.id = ? AND p.user_id = ?",
        [$id, $userId]
    );

    if ($project) {
        return $project;
    }

    // Try as shared member
    return Database::fetch(
        "SELECT p.*, pm.role as access_role FROM {$this->table} p
         JOIN project_members pm ON pm.project_id = p.id
         WHERE p.id = ? AND pm.user_id = ? AND pm.accepted_at IS NOT NULL",
        [$id, $userId]
    );
}
```

**Step 2: Add `allWithShared()` method**

Add after `allByUser()` method (after line 190):

```php
/**
 * Get all projects for a user: owned + shared (with module stats).
 * Returns ['owned' => [...], 'shared' => [...]].
 */
public function allWithShared(int $userId, string $status = 'active'): array
{
    $statusClause = $status !== 'all' ? "AND p.status = ?" : "";
    $params = [$userId];
    if ($status !== 'all') {
        $params[] = $status;
    }

    $owned = Database::fetchAll(
        "SELECT p.*, 'owner' as access_role FROM {$this->table} p
         WHERE p.user_id = ? {$statusClause}
         ORDER BY p.updated_at DESC",
        $params
    );

    $sharedParams = $params; // same params
    $shared = Database::fetchAll(
        "SELECT p.*, pm.role as access_role, u.name as owner_name, u.email as owner_email, u.avatar as owner_avatar
         FROM {$this->table} p
         JOIN project_members pm ON pm.project_id = p.id
         JOIN users u ON u.id = p.user_id
         WHERE pm.user_id = ? AND pm.accepted_at IS NOT NULL {$statusClause}
         ORDER BY p.updated_at DESC",
        $sharedParams
    );

    return ['owned' => $owned, 'shared' => $shared];
}
```

**Step 3: Verify syntax**

```bash
php -l core/Models/GlobalProject.php
```

**Step 4: Commit**

```bash
git add core/Models/GlobalProject.php
git commit -m "feat: add findAccessible() and allWithShared() to GlobalProject model"
```

---

## Task 6: Invite Routes and Controller Methods

**Files:**
- Modify: `controllers/GlobalProjectController.php`
- Modify: `public/index.php` (lines 665-731, project routes section)

**Context:** Add sharing management endpoints to the existing GlobalProjectController. New routes follow the existing `/projects/{id}/...` pattern (line 692+).

**Step 1: Add controller methods to GlobalProjectController**

Add these methods to the controller:

- `sharing(int $id): string` — GET: renders the sharing tab in settings (owner only)
- `invite(int $id): string` — POST: handles invitation form submission (owner only)
- `removeMember(int $id, int $memberUserId): string` — POST: removes a member (owner only)
- `updateMember(int $id, int $memberUserId): string` — POST: updates member role/modules (owner only)
- `cancelInvitation(int $id, int $invitationId): string` — POST: cancels email invitation (owner only)

Each method must:
1. Call `Middleware::auth()` and `Middleware::csrf()` (for POST)
2. Check `ProjectAccessService::isOwner($id, $user['id'])`
3. Call the appropriate `ProjectSharingService` method
4. Redirect back to settings with flash message

**Step 2: Add routes to `public/index.php`**

Add after the existing project routes block (after line 731):

```php
// Project Sharing
Router::get('/projects/{id}/sharing', function ($id) {
    return (new Controllers\GlobalProjectController())->sharing((int) $id);
});
Router::post('/projects/{id}/sharing/invite', function ($id) {
    return (new Controllers\GlobalProjectController())->invite((int) $id);
});
Router::post('/projects/{id}/sharing/remove/{userId}', function ($id, $userId) {
    return (new Controllers\GlobalProjectController())->removeMember((int) $id, (int) $userId);
});
Router::post('/projects/{id}/sharing/update/{userId}', function ($id, $userId) {
    return (new Controllers\GlobalProjectController())->updateMember((int) $id, (int) $userId);
});
Router::post('/projects/{id}/sharing/cancel-invite/{inviteId}', function ($id, $inviteId) {
    return (new Controllers\GlobalProjectController())->cancelInvitation((int) $id, (int) $inviteId);
});
```

**Step 3: Add invitation acceptance routes**

Add to `public/index.php` (before or after project routes):

```php
// Invitation acceptance (public-ish, requires auth)
Router::get('/invite/accept', function () {
    return (new Controllers\GlobalProjectController())->acceptInviteByToken();
});
Router::post('/invite/{id}/accept', function ($id) {
    return (new Controllers\GlobalProjectController())->acceptInternalInvite((int) $id);
});
Router::post('/invite/{id}/decline', function ($id) {
    return (new Controllers\GlobalProjectController())->declineInternalInvite((int) $id);
});
```

**Step 4: Implement controller methods**

The `sharing()` method renders a view with members list + invite form. It calls:
- `ProjectAccessService::getProjectMembers($id)` for current members
- `ProjectAccessService::getProjectInvitations($id)` for pending email invites
- `$this->project->getActiveModules($id)` for the module checkboxes in the invite form

The `invite()` method:
1. Validates email, role, modules from `$_POST`
2. Calls `ProjectSharingService::invite(...)`
3. Redirects to `/projects/{id}/sharing` with flash

The `acceptInviteByToken()` method:
1. Gets `token` from `$_GET`
2. If not logged in: stores token in session, redirects to login
3. After login: calls `ProjectSharingService::acceptByToken($token, $userId)`
4. Redirects to project dashboard

The `acceptInternalInvite()` and `declineInternalInvite()` methods:
1. Call `ProjectSharingService::acceptInternal()` / `declineInternal()`
2. Redirect to `/projects` with flash

**Step 5: Verify syntax**

```bash
php -l controllers/GlobalProjectController.php
php -l public/index.php
```

**Step 6: Commit**

```bash
git add controllers/GlobalProjectController.php public/index.php
git commit -m "feat: add sharing routes and controller methods for project collaboration"
```

---

## Task 7: Sharing Settings View

**Files:**
- Create: `shared/views/projects/sharing.php`
- Modify: `shared/views/projects/settings.php`

**Context:** The settings page at `shared/views/projects/settings.php` currently has a form + danger zone. Add a tab navigation (Generale / Condivisione) at the top. The sharing tab is a separate view loaded via the `/projects/{id}/sharing` route.

**Reference:** Follow existing table/card patterns. Use standard CSS classes from CLAUDE.md: `rounded-xl`, `px-4 py-3`, `dark:bg-slate-700/50`. Heroicons SVG only.

**Step 1: Add tab navigation to settings.php**

At the top of the settings card (around line 15), add tab links:
- "Generale" → `/projects/{id}/settings` (active when on settings)
- "Condivisione" → `/projects/{id}/sharing` (active when on sharing)

Only show "Condivisione" tab if `$access_role === 'owner'`.

**Step 2: Create sharing.php view**

The sharing view contains:

1. **Same tab navigation** (with "Condivisione" active)
2. **Invite form**: email input, role select (Editor/Viewer), module checkboxes (from `$activeModules`), submit button
3. **Members table**: rows with avatar/initials, name, email, role badge, module badges, actions (edit/remove)
4. **Pending invitations table**: email, role, expiry, actions (resend/cancel)
5. **Edit member modal** (Alpine.js): change role + module checkboxes, save button

Use Alpine.js for the modal (consistent with existing patterns like FAQ accordion in landing pages).

**Step 3: Verify syntax**

```bash
php -l shared/views/projects/sharing.php
php -l shared/views/projects/settings.php
```

**Step 4: Commit**

```bash
git add shared/views/projects/sharing.php shared/views/projects/settings.php
git commit -m "feat: add sharing settings tab with invite form and members management UI"
```

---

## Task 8: Projects Index — "Condivisi con me" Section

**Files:**
- Modify: `shared/views/projects/index.php`
- Modify: `controllers/GlobalProjectController.php` (the `index()` method, line 29)

**Context:** The projects index at line 39 of `index.php` loops through `$projects` as a flat grid. We need to split into "I miei progetti" and "Condivisi con me" sections.

**Step 1: Update controller `index()` method**

Change from `allWithModuleStats($user['id'])` to use `allWithShared()` and pass both arrays to the view:

```php
$result = $this->project->allWithShared($user['id']);
// Pass $ownedProjects and $sharedProjects separately to the view
```

**Step 2: Update the view**

- Render "I miei progetti" section with owned projects (existing grid)
- Add "Condivisi con me" section below with shared projects
- Each shared project card shows:
  - Role badge (Editor in blue `bg-blue-500/20 text-blue-400`, Viewer in gray `bg-slate-500/20 text-slate-400`)
  - Owner name/avatar under the project name
  - Same card structure but no settings gear icon (only owner sees that)

**Step 3: Add pending invitations banner**

At the top of the page (before "I miei progetti"), if user has pending invitations, show a banner:

```html
<div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 mb-6">
    <!-- "Hai N inviti in sospeso" with accept/decline buttons -->
</div>
```

Use `ProjectAccessService::getPendingInvitations($userId)` to get pending invites.

**Step 4: Verify syntax**

```bash
php -l shared/views/projects/index.php
php -l controllers/GlobalProjectController.php
```

**Step 5: Commit**

```bash
git add shared/views/projects/index.php controllers/GlobalProjectController.php
git commit -m "feat: show shared projects and pending invitations in projects dashboard"
```

---

## Task 9: GlobalProjectController — Update Existing Methods for Shared Access

**Files:**
- Modify: `controllers/GlobalProjectController.php`

**Context:** Methods like `dashboard()` (line 123), `settings()` (line 178) currently use `find($id, $user['id'])` which only returns owner's projects. Need to switch to `findAccessible()` and add role-based guards.

**Step 1: Update `dashboard()` method (line 123)**

```php
// Before:
$project = $this->project->find($id, $user['id']);

// After:
$project = $this->project->findAccessible($id, $user['id']);
```

Pass `$project['access_role']` to the view so the UI can hide/show elements.

**Step 2: Update `settings()` method (line 178)**

```php
// Use findAccessible, but then check isOwner for settings access
$project = $this->project->findAccessible($id, $user['id']);
if (!$project || $project['access_role'] !== 'owner') {
    // Non-owners cannot access settings
    $_SESSION['_flash']['error'] = 'Accesso non autorizzato';
    Router::redirect('/projects/' . $id);
    return '';
}
```

**Step 3: Update `update()` method (line 203)**

Same pattern: `findAccessible()` then check `isOwner()`.

**Step 4: Update `activateModule()` method (line 267)**

Same pattern: only owner can activate modules.

**Step 5: Update `destroy()` method (line 552)**

Same pattern: only owner can delete.

**Step 6: Pass `access_role` to all views**

Every method that renders a view should pass `'access_role' => $project['access_role']` so the views can conditionally show/hide UI elements.

**Step 7: Verify syntax**

```bash
php -l controllers/GlobalProjectController.php
```

**Step 8: Commit**

```bash
git add controllers/GlobalProjectController.php
git commit -m "feat: update GlobalProjectController methods to support shared project access"
```

---

## Task 10: Project Dashboard View — Role-Aware UI

**Files:**
- Modify: `shared/views/projects/dashboard.php`

**Context:** The project dashboard shows modules, settings link, activate module button, etc. Non-owners should see a limited UI based on their role.

**Step 1: Add role badge in header**

If `$access_role !== 'owner'`, show a badge next to the project name:
- Editor: `<span class="bg-blue-500/20 text-blue-400 ...">Editor</span>`
- Viewer: `<span class="bg-slate-500/20 text-slate-400 ...">Sola lettura</span>`

**Step 2: Hide owner-only elements**

Wrap these in `<?php if ($access_role === 'owner'): ?>`:
- Settings gear/link
- "Attiva modulo" button
- Danger zone / delete button
- WordPress sites management

**Step 3: Filter visible modules for non-owners**

For members (not owner), only show modules they have access to. Use `ProjectAccessService::getMemberModules()` and filter the active modules list.

**Step 4: Verify syntax**

```bash
php -l shared/views/projects/dashboard.php
```

**Step 5: Commit**

```bash
git add shared/views/projects/dashboard.php
git commit -m "feat: role-aware project dashboard UI with permission badges"
```

---

## Task 11: Module Controllers — Shared Access Support

**Files:**
- Modify: All 7 module controllers that use project ownership checks

**Context:** Each module controller has a `find($id, $userId)` pattern (see seo-tracking example). This needs to also allow shared members with the right module access. This is the most widespread change.

**Modules to update** (each has a `models/Project.php` with `find()` method):
1. `modules/seo-tracking/models/Project.php`
2. `modules/ai-content/models/Project.php`
3. `modules/keyword-research/models/Project.php`
4. `modules/ads-analyzer/models/Project.php`
5. `modules/seo-audit/models/Project.php`
6. `modules/internal-links/models/Project.php`
7. `modules/content-creator/models/Project.php`

**Strategy:** Add a `findAccessible()` method to each module's Project model that:
1. First tries `find($id, $userId)` (owner — fast path)
2. If null, checks if the user has shared access to this module project via its `global_project_id`

```php
public function findAccessible(int $id, ?int $userId = null): ?array
{
    // Fast path: direct owner
    $project = $this->find($id, $userId);
    if ($project) {
        $project['access_role'] = 'owner';
        return $project;
    }

    if ($userId === null) {
        return null;
    }

    // Shared access: find project without user filter, then check sharing
    $project = $this->find($id);
    if (!$project || empty($project['global_project_id'])) {
        return null;
    }

    $role = \Services\ProjectAccessService::getRole((int)$project['global_project_id'], $userId);
    if ($role === null) {
        return null;
    }

    // Check module-level access
    $moduleSlug = '{MODULE_SLUG}'; // Replace per module
    if ($role !== 'owner' && !\Services\ProjectAccessService::canAccessModule(
        (int)$project['global_project_id'], $userId, $moduleSlug
    )) {
        return null;
    }

    $project['access_role'] = $role;
    return $project;
}
```

**Step 1:** Add `findAccessible()` to each of the 7 module Project models. Replace `{MODULE_SLUG}` with the correct slug for each module.

**Step 2:** In each module's controllers, replace `find($id, $user['id'])` calls with `findAccessible($id, $user['id'])`. For operations that modify data or consume credits, add a check:

```php
if (($project['access_role'] ?? 'owner') === 'viewer') {
    // 403 for viewers on write operations
}
```

**Step 3:** For credit consumption, use the owner's ID:

```php
$creditUserId = $user['id']; // default: current user
if (!empty($project['global_project_id'])) {
    $ownerId = \Services\ProjectAccessService::getOwnerId((int)$project['global_project_id']);
    if ($ownerId) {
        $creditUserId = $ownerId;
    }
}
Credits::consume($creditUserId, $cost, $action, $moduleSlug);
```

**Step 4:** Verify syntax for ALL modified files

```bash
php -l modules/seo-tracking/models/Project.php
php -l modules/ai-content/models/Project.php
php -l modules/keyword-research/models/Project.php
php -l modules/ads-analyzer/models/Project.php
php -l modules/seo-audit/models/Project.php
php -l modules/internal-links/models/Project.php
php -l modules/content-creator/models/Project.php
```

**Step 5: Commit (one per module or all together)**

```bash
git add modules/*/models/Project.php
git commit -m "feat: add findAccessible() to all module Project models for shared access"
```

Then update the controllers:

```bash
git add modules/*/controllers/*.php
git commit -m "feat: update module controllers to support shared project access and owner billing"
```

---

## Task 12: Sidebar Navigation — Filter by Module Access

**Files:**
- Modify: `shared/views/components/nav-items.php`

**Context:** The sidebar shows all modules. For shared projects, it should only show modules the user has access to. When navigating inside a module project, check if the user is a member with that module enabled.

**Step 1: Add module access check**

In the section where module project navigation is built (nav-items.php), when a project context is detected (via URL regex), check if the user is the owner of the linked global project OR has shared module access:

```php
// After detecting project context for a module
if ($project && !empty($project['global_project_id'])) {
    $globalProjectId = (int)$project['global_project_id'];
    $role = \Services\ProjectAccessService::getRole($globalProjectId, $userId);
    if ($role !== null && $role !== 'owner') {
        // Filter: only show modules this member has access to
        $memberModules = \Services\ProjectAccessService::getMemberModules($globalProjectId, $userId);
        $allowedSlugs = array_column($memberModules, 'module_slug');
        // Use $allowedSlugs to filter visible module nav items
    }
}
```

**Step 2: Verify syntax**

```bash
php -l shared/views/components/nav-items.php
```

**Step 3: Commit**

```bash
git add shared/views/components/nav-items.php
git commit -m "feat: filter sidebar modules by shared access permissions"
```

---

## Task 13: Token-Based Invite Acceptance Page

**Files:**
- Create: `shared/views/projects/invite-accept.php`

**Context:** When a non-registered user clicks the email link `/invite/accept?token=xxx`, they land on this page. If not logged in, show login/register prompt. If logged in, auto-accept.

**Step 1: Implement `acceptInviteByToken()` in GlobalProjectController**

```php
public function acceptInviteByToken(): string
{
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        $_SESSION['_flash']['error'] = 'Token invito mancante';
        Router::redirect('/login');
        return '';
    }

    // If not logged in, store token and redirect to login
    if (!Auth::check()) {
        $_SESSION['invite_token'] = $token;
        $_SESSION['_flash']['info'] = 'Accedi o registrati per accettare l\'invito';
        Router::redirect('/login');
        return '';
    }

    $user = Auth::user();
    $result = ProjectSharingService::acceptByToken($token, $user['id']);

    if ($result['success']) {
        $_SESSION['_flash']['success'] = $result['message'];
        $redirectTo = isset($result['project_id']) ? '/projects/' . $result['project_id'] : '/projects';
        Router::redirect($redirectTo);
    } else {
        $_SESSION['_flash']['error'] = $result['message'];
        Router::redirect('/projects');
    }

    return '';
}
```

**Step 2: Handle post-login token acceptance**

In `core/Auth.php`, after successful login (in the `login()` method or in the login controller), check for `$_SESSION['invite_token']`:

```php
if (!empty($_SESSION['invite_token'])) {
    $token = $_SESSION['invite_token'];
    unset($_SESSION['invite_token']);
    Router::redirect('/invite/accept?token=' . $token);
    exit;
}
```

Find where the login success redirect happens and add this check BEFORE the default redirect.

**Step 3: Verify syntax**

```bash
php -l controllers/GlobalProjectController.php
php -l core/Auth.php
```

**Step 4: Commit**

```bash
git add controllers/GlobalProjectController.php core/Auth.php
git commit -m "feat: handle invite token acceptance with login redirect flow"
```

---

## Task 14: Cron Cleanup for Expired Invitations

**Files:**
- Modify: `cron/cleanup-data.php`

**Context:** The daily cleanup cron at `cron/cleanup-data.php` already runs at `0 2 * * *`. Add expired invitation cleanup to it.

**Step 1: Add to existing cleanup script**

```php
// Clean up expired project invitations
$deleted = \Services\ProjectSharingService::cleanupExpiredInvitations();
if ($deleted > 0) {
    Logger::info("Cleaned up {$deleted} expired project invitations");
}
```

**Step 2: Verify syntax**

```bash
php -l cron/cleanup-data.php
```

**Step 3: Commit**

```bash
git add cron/cleanup-data.php
git commit -m "feat: add expired project invitation cleanup to daily cron"
```

---

## Task 15: Update Documentation

**Files:**
- Modify: `docs/data-model.html` (add new tables to ER diagram)
- Create or modify: `shared/views/docs/projects.php` (user-facing docs about sharing)
- Modify: `CLAUDE.md` (add sharing info to GOLDEN RULES or STATO MODULI)

**Step 1: Update data model**

Add `project_members`, `project_member_modules`, `project_invitations` tables to the Mermaid.js ER diagram with relationships to `projects` and `users`.

**Step 2: Update user docs**

Add a "Condivisione Progetti" section to the projects documentation explaining:
- How to invite members
- Role differences (Owner/Editor/Viewer)
- Per-module access
- How credits work (always owner's)

**Step 3: Update CLAUDE.md**

Add to the project structure section that `ProjectAccessService` and `ProjectSharingService` exist. Note the new tables.

**Step 4: Commit**

```bash
git add docs/data-model.html shared/views/docs/projects.php CLAUDE.md
git commit -m "docs: update documentation with project sharing feature"
```

---

## Execution Order Summary

| Task | Description | Dependencies |
|------|-------------|-------------|
| 1 | Database migration | None |
| 2 | ProjectAccessService | Task 1 |
| 3 | ProjectSharingService | Task 1, 2 |
| 4 | Email template | None (parallel with 2-3) |
| 5 | GlobalProject model methods | Task 2 |
| 6 | Routes + controller methods | Task 2, 3, 5 |
| 7 | Sharing settings view | Task 6 |
| 8 | Projects index — shared section | Task 5, 6 |
| 9 | Controller method updates | Task 5 |
| 10 | Dashboard role-aware UI | Task 9 |
| 11 | Module controllers (all 7) | Task 2, 5 |
| 12 | Sidebar filtering | Task 2 |
| 13 | Token acceptance page | Task 3, 6 |
| 14 | Cron cleanup | Task 3 |
| 15 | Documentation | All tasks |

**Parallelizable:** Tasks 1+4 can run in parallel. Tasks 7+8+12 can run in parallel after their deps. Task 11 (7 modules) can be split across parallel agents.
