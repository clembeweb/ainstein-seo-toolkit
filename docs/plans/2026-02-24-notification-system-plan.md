# Notification System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add an in-app notification system with bell icon, dropdown panel, polling, email alerts, and a full notifications page.

**Architecture:** Single `notifications` table + `notification_preferences` table. Centralized `NotificationService` called by modules. Polling endpoint every 30s updates badge. Alpine.js dropdown + dedicated `/notifications` page.

**Tech Stack:** PHP 8+, MySQL, Tailwind CSS, Alpine.js (existing stack)

**Design Doc:** `docs/plans/2026-02-24-notification-system-design.md`

---

## Task 1: Database Migration — Create Tables

**Files:**
- Create: `database/migrations/2026_02_24_notifications.sql`

**Step 1: Write the migration SQL**

```sql
-- Notification System Tables
-- Run: mysql -u root seo_toolkit < database/migrations/2026_02_24_notifications.sql

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NULL,
    icon VARCHAR(50) NULL,
    color VARCHAR(20) DEFAULT 'blue',
    action_url VARCHAR(500) NULL,
    data JSON NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_unread (user_id, read_at, created_at),
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_type (type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    email_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_type (user_id, type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Step 2: Run migration locally**

```bash
mysql -u root seo_toolkit < database/migrations/2026_02_24_notifications.sql
```

**Step 3: Verify**

```bash
mysql -u root seo_toolkit -e "DESCRIBE notifications;"
mysql -u root seo_toolkit -e "DESCRIBE notification_preferences;"
```

**Step 4: Commit**

```bash
git add database/migrations/2026_02_24_notifications.sql
git commit -m "feat: add notifications database tables"
```

---

## Task 2: NotificationService — Core Service

**Files:**
- Create: `services/NotificationService.php`

**Context:** Read `services/ProjectAccessService.php` for coding style (namespace, static methods, Database usage). Read `services/EmailService.php` for `sendTemplate()` pattern.

**Step 1: Create the service**

The service must have these static methods:

### Constants
```php
const EMAIL_DEFAULTS = [
    'project_invite' => true,
    'project_invite_accepted' => false,
    'project_invite_declined' => false,
    'operation_completed' => true,
    'operation_failed' => true,
];

const TYPE_LABELS = [
    'project_invite' => 'Inviti progetto',
    'project_invite_accepted' => 'Inviti accettati',
    'project_invite_declined' => 'Inviti rifiutati',
    'operation_completed' => 'Operazioni completate',
    'operation_failed' => 'Operazioni fallite',
];
```

### Methods

1. **`send(int $userId, string $type, string $title, array $options = []): int`**
   - `$options`: `body`, `icon`, `color`, `action_url`, `data` (array → json_encode), `skip_email`
   - INSERT into `notifications`
   - If email enabled and not `skip_email`: call `EmailService::sendTemplate($userEmail, $title, 'notification', [...])`
   - Return the notification ID

2. **`sendToMany(array $userIds, string $type, string $title, array $options = []): int`**
   - Loop `send()` for each userId
   - Return count sent

3. **`getUnreadCount(int $userId): int`**
   - `SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL`

4. **`getRecent(int $userId, int $limit = 15): array`**
   - `SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?`
   - Add `time_ago` field to each (human-readable relative time in Italian)

5. **`getAll(int $userId, int $page = 1, int $perPage = 20, ?string $filter = null): array`**
   - Paginated query. Filter: `null` = all, `'unread'` = `read_at IS NULL`
   - Return `['notifications' => [...], 'total' => N, 'page' => N, 'perPage' => N]`
   - Add `time_ago` field to each

6. **`markAsRead(int $notificationId, int $userId): bool`**
   - `UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ? AND read_at IS NULL`

7. **`markAllAsRead(int $userId): int`**
   - `UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL`
   - Return affected rows

8. **`isEmailEnabled(int $userId, string $type): bool`**
   - Check `notification_preferences` for override
   - If no record: return `EMAIL_DEFAULTS[$type] ?? false`

9. **`updatePreferences(int $userId, array $preferences): void`**
   - `$preferences`: `['type' => bool, ...]`
   - UPSERT into `notification_preferences`

10. **`getPreferences(int $userId): array`**
    - Return merged defaults + user overrides

11. **Private `timeAgo(string $datetime): string`**
    - Return Italian relative time: "adesso", "5 min fa", "2 ore fa", "ieri", "3 giorni fa", "15 gen"

**Step 2: Verify syntax**

```bash
php -l services/NotificationService.php
```

**Step 3: Commit**

```bash
git add services/NotificationService.php
git commit -m "feat: add NotificationService for in-app and email notifications"
```

---

## Task 3: Email Template for Notifications

**Files:**
- Create: `shared/views/emails/notification.php`

**Context:** Read existing email templates in `shared/views/emails/` to match style. This is a generic notification email template.

**Variables:** `$title`, `$body`, `$action_url`, `$action_label` (default "Vai alla pagina")

**Content:**
- Heading: the notification title
- Body paragraph (if provided)
- CTA button linking to action_url (if provided)
- Footer: "Puoi modificare le preferenze di notifica nel tuo profilo."

Match existing email template HTML structure exactly.

**Step 1: Read existing templates, create the file**
**Step 2: `php -l` verify**
**Step 3: Commit**

```bash
git add shared/views/emails/notification.php
git commit -m "feat: add generic notification email template"
```

---

## Task 4: NotificationController — API Endpoints

**Files:**
- Create: `controllers/NotificationController.php`
- Modify: `public/index.php` — add routes

**Context:** Read `controllers/GlobalProjectController.php` for controller patterns. All endpoints require `Middleware::auth()`. JSON endpoints return via `echo json_encode() + exit`.

### Controller Methods

1. **`unreadCount(): string`** — GET `/notifications/unread-count`
   - Returns JSON `{"count": N}`
   - No CSRF needed (GET)
   - Lightweight: single COUNT query

2. **`recent(): string`** — GET `/notifications/recent`
   - Returns JSON array of recent notifications
   - Called when dropdown opens

3. **`index(): string`** — GET `/notifications`
   - Renders full page view `shared/views/notifications/index.php`
   - Paginated, with filter (all/unread)
   - Pass `$notifications`, `$total`, `$page`, `$filter`

4. **`markRead(int $id): string`** — POST `/notifications/{id}/read`
   - CSRF required
   - Returns JSON `{"success": true}`

5. **`markAllRead(): string`** — POST `/notifications/read-all`
   - CSRF required
   - Returns JSON `{"success": true, "count": N}`

### Routes to add in `public/index.php`

Add BEFORE module routes (these are global):

```php
// Notifications
Router::get('/notifications/unread-count', function () {
    return (new Controllers\NotificationController())->unreadCount();
});
Router::get('/notifications/recent', function () {
    return (new Controllers\NotificationController())->recent();
});
Router::get('/notifications', function () {
    return (new Controllers\NotificationController())->index();
});
Router::post('/notifications/{id}/read', function ($id) {
    return (new Controllers\NotificationController())->markRead((int) $id);
});
Router::post('/notifications/read-all', function () {
    return (new Controllers\NotificationController())->markAllRead();
});
```

**Step 1: Read controller patterns, create NotificationController**
**Step 2: Add routes to index.php**
**Step 3: `php -l` both files**
**Step 4: Commit**

```bash
git add controllers/NotificationController.php public/index.php
git commit -m "feat: add notification API endpoints and controller"
```

---

## Task 5: Bell Icon + Dropdown in Layout

**Files:**
- Modify: `shared/views/layout.php` (lines 158-167, between dark mode toggle and user dropdown)

**Context:** The top bar header at line 115 of layout.php has: mobile menu → credits badge → dark mode toggle → user dropdown. The bell goes between dark mode toggle (ends ~line 166) and user dropdown (starts ~line 169).

**Step 1: Add the notification bell component**

Insert between the dark mode toggle `</button>` and the user dropdown `<div class="relative" x-data="{ open: false }">`:

```html
<!-- Notification bell -->
<div x-data="notificationBell()" x-init="startPolling()" class="relative">
    <button @click="togglePanel()" class="relative p-2 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        <!-- Unread badge -->
        <span x-show="unreadCount > 0" x-cloak
              x-text="unreadCount > 9 ? '9+' : unreadCount"
              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold leading-none">
        </span>
    </button>

    <!-- Dropdown panel -->
    <div x-show="open" @click.away="open = false" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         class="absolute right-0 z-50 mt-2 w-96 rounded-xl bg-white dark:bg-slate-800 shadow-xl ring-1 ring-slate-200 dark:ring-slate-700 overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Notifiche</h3>
            <button x-show="unreadCount > 0" @click.stop="markAllRead()"
                    class="text-xs text-blue-500 hover:text-blue-400 font-medium">
                Segna tutte come lette
            </button>
        </div>

        <!-- Notification list -->
        <div class="max-h-[400px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700/50">
            <template x-if="notifications.length === 0 && loaded">
                <div class="px-4 py-8 text-center text-sm text-slate-500">
                    Nessuna notifica
                </div>
            </template>
            <template x-for="n in notifications" :key="n.id">
                <a :href="n.action_url || '#'" @click="markRead(n.id)"
                   class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                   :class="{ 'bg-blue-50/50 dark:bg-blue-900/10': !n.read_at }">
                    <!-- Icon circle -->
                    <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center mt-0.5"
                         :class="{
                            'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400': n.color === 'blue',
                            'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400': n.color === 'emerald',
                            'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400': n.color === 'amber',
                            'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400': n.color === 'red',
                            'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400': !['blue','emerald','amber','red'].includes(n.color)
                         }">
                        <!-- Dynamic icon based on n.icon -->
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                             x-html="getIconPath(n.icon)"></svg>
                    </div>
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-900 dark:text-white" :class="{ 'font-semibold': !n.read_at }" x-text="n.title"></p>
                        <p x-show="n.body" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2" x-text="n.body"></p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1" x-text="n.time_ago"></p>
                    </div>
                    <!-- Unread dot -->
                    <div x-show="!n.read_at" class="flex-shrink-0 w-2 h-2 rounded-full bg-blue-500 mt-2"></div>
                </a>
            </template>
        </div>

        <!-- Footer -->
        <div class="border-t border-slate-200 dark:border-slate-700 px-4 py-2">
            <a href="/notifications" class="block text-center text-xs text-blue-500 hover:text-blue-400 font-medium py-1">
                Vedi tutte le notifiche
            </a>
        </div>
    </div>
</div>
```

**Step 2: Add the Alpine.js `notificationBell()` function**

Add to the bottom of layout.php (before closing `</body>`) or in a script tag:

```javascript
function notificationBell() {
    return {
        open: false,
        unreadCount: 0,
        notifications: [],
        loaded: false,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content
                   || document.querySelector('input[name="_csrf_token"]')?.value || '',

        startPolling() {
            this.fetchCount();
            setInterval(() => { if (!document.hidden) this.fetchCount(); }, 30000);
        },

        async fetchCount() {
            try {
                const resp = await fetch('/notifications/unread-count');
                if (resp.ok) {
                    const data = await resp.json();
                    this.unreadCount = data.count;
                }
            } catch (e) {}
        },

        async togglePanel() {
            this.open = !this.open;
            if (this.open) {
                await this.fetchRecent();
            }
        },

        async fetchRecent() {
            try {
                const resp = await fetch('/notifications/recent');
                if (resp.ok) {
                    this.notifications = await resp.json();
                    this.loaded = true;
                }
            } catch (e) {}
        },

        async markRead(id) {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                await fetch('/notifications/' + id + '/read', { method: 'POST', body: formData });
                const n = this.notifications.find(x => x.id === id);
                if (n && !n.read_at) {
                    n.read_at = new Date().toISOString();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
            } catch (e) {}
        },

        async markAllRead() {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                await fetch('/notifications/read-all', { method: 'POST', body: formData });
                this.notifications.forEach(n => n.read_at = n.read_at || new Date().toISOString());
                this.unreadCount = 0;
            } catch (e) {}
        },

        getIconPath(icon) {
            const icons = {
                'user-plus': '<path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m3-3h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />',
                'check-circle': '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                'x-circle': '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                'exclamation-triangle': '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />',
                'bell': '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />'
            };
            return icons[icon] || icons['bell'];
        }
    }
}
```

**Step 3: Add CSRF meta tag to layout head** (if not already present, needed for JS fetch)

Check if a `<meta name="csrf-token">` exists in the `<head>`. If not, add:
```html
<meta name="csrf-token" content="<?= csrf_token() ?>">
```

**Step 4: `php -l shared/views/layout.php`**

**Step 5: Commit**

```bash
git add shared/views/layout.php
git commit -m "feat: add notification bell with dropdown and polling in top bar"
```

---

## Task 6: Notifications Full Page

**Files:**
- Create: `shared/views/notifications/index.php`
- Modify: `controllers/NotificationController.php` (the `index()` method already defined in Task 4)

**Context:** Simple paginated list of all notifications. Filter tabs: "Tutte" / "Non lette". Uses existing table/pagination components.

**Step 1: Create the view**

Page structure:
- Header: "Notifiche" title + "Segna tutte come lette" button (if unread > 0)
- Filter tabs: "Tutte" (active/inactive) | "Non lette" (active/inactive) — simple links with `?filter=unread`
- Notification list: cards (not table — notifications are better as cards)
  - Each card: icon circle + title + body + time_ago + read/unread styling
  - Click → navigates to action_url, marks as read via JS
- Pagination: use `View::partial('components/table-pagination', [...])`
- Empty state: "Nessuna notifica" with bell icon

**Design:**
- Card per notification: `bg-white dark:bg-slate-800 rounded-xl` when read, `bg-blue-50/50 dark:bg-blue-900/10` border-left accent when unread
- Follow existing dark mode patterns

**Step 2: Verify**
```bash
php -l shared/views/notifications/index.php
```

**Step 3: Commit**
```bash
git add shared/views/notifications/index.php
git commit -m "feat: add full notifications page with pagination and filters"
```

---

## Task 7: Notification Preferences in Profile

**Files:**
- Modify: find the profile page view (likely `shared/views/profile/index.php` or similar)
- Modify: find the profile controller (check `public/index.php` for `/profile` route)

**Context:** Add a "Preferenze Notifiche" section to the user profile page. Simple toggle list for each notification type's email preference.

**Step 1: Find and read the profile view and controller**

Search for the profile route and files.

**Step 2: Add preferences section to the profile view**

After existing profile sections, add a card:

```html
<!-- Preferenze Notifiche -->
<div class="bg-slate-800 rounded-xl p-6 mt-6">
    <h3 class="text-lg font-semibold text-white mb-4">Preferenze Notifiche Email</h3>
    <p class="text-sm text-slate-400 mb-4">Scegli per quali notifiche ricevere anche un'email.</p>
    <form method="POST" action="/profile/notification-preferences">
        <?= csrf_field() ?>
        <!-- For each type: toggle switch with label -->
        <!-- Types: project_invite, operation_completed, operation_failed -->
        <!-- project_invite_accepted and project_invite_declined have email=false by default, less important -->
        <div class="space-y-3">
            <!-- Each row: label + description + toggle -->
        </div>
        <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 ...">Salva preferenze</button>
    </form>
</div>
```

**Step 3: Add route and controller method for saving preferences**

```php
Router::post('/profile/notification-preferences', function () {
    // Calls NotificationService::updatePreferences()
});
```

**Step 4: Verify and commit**

```bash
git add [profile files] public/index.php
git commit -m "feat: add notification email preferences to user profile"
```

---

## Task 8: Integrate with ProjectSharingService

**Files:**
- Modify: `services/ProjectSharingService.php`

**Context:** Add `NotificationService::send()` calls at key points in the sharing flow. This replaces the banner-based invite notification with the new system (the banner can stay as backup/immediate visibility).

**Step 1: Read ProjectSharingService.php**

**Step 2: Add notification calls**

### In `invite()` — when user exists (internal invite):
```php
\Services\NotificationService::send($existingUser['id'], 'project_invite',
    'Invito a collaborare su ' . $projectName, [
    'body' => ($inviterName) . ' ti ha invitato come ' . ($role === 'editor' ? 'Editor' : 'Visualizzatore'),
    'icon' => 'user-plus',
    'color' => 'blue',
    'action_url' => '/projects',
    'data' => ['project_id' => $projectId, 'invited_by' => $invitedBy],
]);
```

### In `acceptInternal()` / `acceptByToken()` — notify the owner:
```php
$ownerId = \Services\ProjectAccessService::getOwnerId($member['project_id']);
$userName = Database::fetch("SELECT name, email FROM users WHERE id = ?", [$userId]);
\Services\NotificationService::send($ownerId, 'project_invite_accepted',
    ($userName['name'] ?? $userName['email']) . ' ha accettato l\'invito', [
    'icon' => 'check-circle', 'color' => 'emerald',
    'action_url' => '/projects/' . $member['project_id'] . '/sharing',
    'data' => ['project_id' => $member['project_id'], 'user_id' => $userId],
]);
```

### In `declineInternal()` — notify the owner:
```php
// Same pattern but type 'project_invite_declined', color 'amber'
```

**Step 3: `php -l services/ProjectSharingService.php`**

**Step 4: Commit**

```bash
git add services/ProjectSharingService.php
git commit -m "feat: integrate notifications with project sharing invites"
```

---

## Task 9: Integrate with Module Operations (SSE/Cron completions)

**Files:**
- Modify: `modules/seo-audit/controllers/CrawlController.php` — after crawl completes
- Modify: `modules/seo-tracking/controllers/RankCheckController.php` — after rank check completes
- Modify: `modules/ai-content/cron/dispatcher.php` — after AI job completes
- Modify: `modules/keyword-research/controllers/ResearchController.php` — after research SSE completes

**Context:** Each module has an SSE or cron endpoint that processes items. At the end (on `completed` event or job finish), send a notification.

**Pattern for each module:**

After the operation completes successfully:
```php
\Services\NotificationService::send($userId, 'operation_completed',
    '[Operation name] completata per ' . $projectName, [
    'icon' => 'check-circle', 'color' => 'emerald',
    'action_url' => '/[module]/projects/' . $projectId . '/[results-page]',
    'data' => ['module' => '[module-slug]', 'project_id' => $projectId],
]);
```

On failure/error:
```php
\Services\NotificationService::send($userId, 'operation_failed',
    '[Operation name] fallita', [
    'icon' => 'exclamation-triangle', 'color' => 'red',
    'action_url' => '/[module]/projects/' . $projectId . '/[page]',
    'body' => 'Errore: ' . $errorMessage,
    'data' => ['module' => '[module-slug]', 'project_id' => $projectId],
]);
```

**Important:** Add `Database::reconnect()` before the notification send if it follows a long operation.

**Step 1: Read each file, find the completion point**
**Step 2: Add notification calls**
**Step 3: `php -l` all modified files**
**Step 4: Commit**

```bash
git add modules/*/controllers/*.php modules/ai-content/cron/dispatcher.php
git commit -m "feat: send notifications on module operation completion/failure"
```

---

## Task 10: Cron Cleanup for Old Notifications

**Files:**
- Modify: `cron/cleanup-data.php`

**Context:** Add notification cleanup (> 90 days) to the existing daily cron. Follow the exact same pattern as the project invitation cleanup added previously.

**Step 1: Add cleanup function**

```php
function cleanupOldNotifications(): int
{
    if (!tableExists('notifications')) return 0;
    return \Core\Database::execute(
        "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
    );
}
```

**Step 2: Add to sections array**

```php
['notifications (> 90 giorni)', 'cleanupOldNotifications', []],
```

**Step 3: `php -l cron/cleanup-data.php`**

**Step 4: Commit**

```bash
git add cron/cleanup-data.php
git commit -m "feat: add old notification cleanup to daily cron (90 days)"
```

---

## Task 11: Update Documentation

**Files:**
- Modify: `docs/data-model.html` — add notifications tables
- Modify: `CLAUDE.md` — add notification system info
- Modify: `shared/views/docs/getting-started.php` — mention notifications in user docs

**Step 1: Add tables to data-model.html ER diagram**

**Step 2: Add to CLAUDE.md** near the project sharing section:

```markdown
### Sistema Notifiche

| Tabella | Scopo |
|---------|-------|
| `notifications` | Notifiche in-app (tipo, titolo, read_at, action_url) |
| `notification_preferences` | Preferenze email per tipo notifica |

**Service**: `NotificationService::send($userId, $type, $title, $options)`

**Tipi v1**: `project_invite`, `project_invite_accepted`, `project_invite_declined`, `operation_completed`, `operation_failed`

**Polling**: `/notifications/unread-count` ogni 30s, Alpine.js `notificationBell()`
```

**Step 3: Commit**

```bash
git add docs/data-model.html CLAUDE.md shared/views/docs/getting-started.php
git commit -m "docs: update documentation with notification system"
```

---

## Execution Order Summary

| Task | Description | Dependencies |
|------|-------------|-------------|
| 1 | Database migration | None |
| 2 | NotificationService | Task 1 |
| 3 | Email template | None (parallel with 2) |
| 4 | NotificationController + routes | Task 2 |
| 5 | Bell icon + dropdown in layout | Task 4 |
| 6 | Full notifications page | Task 4 |
| 7 | Preferences in profile | Task 2 |
| 8 | Integrate with ProjectSharingService | Task 2 |
| 9 | Integrate with module operations | Task 2 |
| 10 | Cron cleanup | Task 2 |
| 11 | Documentation | All tasks |

**Parallelizable:** Tasks 1+3. Tasks 5+6+7+8+9+10 after Task 4.
