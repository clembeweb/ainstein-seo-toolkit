# Email System (Admin-Customizable) Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build an admin-customizable email template system with DB-stored templates, live preview editor, unsubscribe mechanism, and admin platform report.

**Architecture:** Templates stored in `email_templates` table with `{{placeholder}}` syntax. `EmailService::sendTemplate()` enhanced to load from DB first (fallback to PHP file). Layout wrapper stays in `base.php` file. Admin panel for editing templates with textarea + live preview. Token-based unsubscribe integrated with `notification_preferences`.

**Tech Stack:** PHP 8+, MySQL, Tailwind CSS, Alpine.js (existing stack)

**Design Doc:** `docs/plans/2026-02-24-email-system-design.md`

**Integration Note:** This plan extends the notification system plan (`notification-system-plan.md`). The notification plan's Task 3 (create `notification.php` file template) is replaced by the DB seed here. All other notification tasks remain valid — `NotificationService::send()` calls `EmailService::sendTemplate()` which now reads from DB.

---

## Task 1: Database Migration — Create Tables + Seed Templates

**Files:**
- Create: `database/migrations/2026_02_24_email_templates.sql`

**Step 1: Write the migration SQL**

```sql
-- Email Template System Tables
-- Run: mysql -u root seo_toolkit < database/migrations/2026_02_24_email_templates.sql

CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NOT NULL,
    description TEXT NULL,
    available_vars JSON NOT NULL,
    category VARCHAR(50) DEFAULT 'system',
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_unsubscribe_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add email branding settings (if not exist)
INSERT IGNORE INTO settings (key_name, value) VALUES
('email_logo_url', ''),
('email_brand_color', '#006e96'),
('email_footer_text', '');
```

**Step 2: Write the seed SQL for default templates**

In the same file, after the CREATE TABLE statements, add INSERT statements for 8 default templates. Each template's `body_html` should contain the same HTML content currently in the PHP file templates, but with `{{placeholder}}` syntax instead of PHP variables. For reference:

- **`welcome`** (auth): Convert `shared/views/emails/welcome.php` content (lines 16-55). Replace `<?= htmlspecialchars($userName) ?>` → `{{user_name}}`, `<?= (int)$freeCredits ?>` → `{{free_credits}}`, `<?= htmlspecialchars($appName) ?>` → `{{app_name}}`, `<?= htmlspecialchars($appUrl) ?>` → `{{app_url}}`, `<?= htmlspecialchars($userEmail) ?>` → `{{user_email}}`. Available vars: `["user_name","user_email","free_credits","app_name","app_url","login_url"]`
- **`password-reset`** (auth): Convert `shared/views/emails/password-reset.php` content (lines 15-39). Replace `<?= $resetUrl ?>` → `{{reset_url}}`, etc. Available vars: `["user_email","reset_url","app_name","app_url"]`
- **`password-changed`** (auth): New. Subject: "Password modificata — {{app_name}}". Body: confirmation text + security notice. Available vars: `["user_name","user_email","app_name","app_url"]`
- **`email-changed`** (auth): New. Subject: "Email aggiornata — {{app_name}}". Body: confirmation with new email + security notice. Available vars: `["user_name","old_email","new_email","app_name","app_url"]`
- **`project-invite`** (notification): Convert `shared/views/emails/project-invite.php` content (lines 17-46). Available vars: `["project_name","inviter_name","role","accept_url","app_name","app_url"]`
- **`notification`** (notification): Generic notification email. Subject: "{{title}} — {{app_name}}". Body: title heading + body paragraph + optional CTA button. Available vars: `["title","body","action_url","action_label","app_name","app_url"]`
- **`seo-alert`** (module): SEO Tracking alert digest. Subject: "[SEO Tracking] {{project_name}} — Nuovi alert". Body: intro + alert table HTML + dashboard link. Available vars: `["project_name","domain","alert_count","alerts_html","dashboard_url","app_name","app_url"]`
- **`admin-report`** (report): Admin platform digest. Subject: "Report piattaforma {{period}} — {{app_name}}". Body: metrics summary. Available vars: `["period","new_users","total_users","active_users","credits_consumed","top_modules_html","api_errors","failed_jobs","app_name","app_url"]`

Important: body_html should contain ONLY the inner content (what goes inside `.email-body`), NOT the full HTML document. The base layout wrapper handles `<html>`, `<head>`, styles, header, footer.

**Step 3: Run migration locally**

```bash
mysql -u root seo_toolkit < database/migrations/2026_02_24_email_templates.sql
```

**Step 4: Verify**

```bash
mysql -u root seo_toolkit -e "SELECT slug, name, category FROM email_templates;"
mysql -u root seo_toolkit -e "DESCRIBE email_unsubscribe_tokens;"
```

**Step 5: Commit**

```bash
git add database/migrations/2026_02_24_email_templates.sql
git commit -m "feat: add email_templates and unsubscribe_tokens tables with default seeds"
```

---

## Task 2: Enhance EmailService — DB Template Rendering

**Files:**
- Modify: `services/EmailService.php` (lines 141-160: `sendTemplate()` method + add new methods)

**Context:** Read `services/EmailService.php` (full file). The current `sendTemplate()` at line 141 loads PHP file templates via `include`. We need to:
1. Check DB first for the template slug
2. If found: render with `{{placeholder}}` replacement
3. If not found: fallback to current PHP file behavior
4. Add unsubscribe URL to all emails
5. Add new methods: `renderFromDb()`, `getUnsubscribeUrl()`, `renderPreview()`, `getDefaultTemplate()`

**Step 1: Add new private method `renderFromDb()`**

Add after line 160 (after `sendTemplate()`):

```php
/**
 * Renderizza template da database con {{placeholder}} replacement
 */
private static function renderFromDb(array $template, array $data): string
{
    $html = $template['body_html'];

    // Replace all {{placeholder}} with data values
    foreach ($data as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
            $html = str_replace('{{' . $key . '}}', (string) $value, $html);
        }
    }

    // Clean up any remaining unreplaced placeholders
    $html = preg_replace('/\{\{[a-z_]+\}\}/', '', $html);

    return $html;
}
```

**Step 2: Add `getUnsubscribeUrl()` method**

```php
/**
 * Genera o recupera URL unsubscribe per utente
 */
public static function getUnsubscribeUrl(int $userId): string
{
    $appUrl = rtrim(env('APP_URL', 'https://ainstein.it'), '/');

    $existing = \Core\Database::fetch(
        "SELECT token FROM email_unsubscribe_tokens WHERE user_id = ?",
        [$userId]
    );

    if ($existing) {
        return $appUrl . '/email/preferences?token=' . $existing['token'];
    }

    $token = bin2hex(random_bytes(32));
    \Core\Database::insert('email_unsubscribe_tokens', [
        'user_id' => $userId,
        'token' => $token,
    ]);

    return $appUrl . '/email/preferences?token=' . $token;
}
```

**Step 3: Modify `sendTemplate()` to check DB first**

Replace lines 141-160 with:

```php
/**
 * Invia email usando un template (DB-first con fallback a file PHP)
 *
 * @param string $to Indirizzo destinatario
 * @param string $subject Oggetto (usato come fallback; se template DB ha subject, quello ha priorita)
 * @param string $template Nome/slug template
 * @param array $data Variabili da passare al template
 * @param int|null $userId ID utente per unsubscribe link (opzionale)
 * @return array ['success' => bool, 'message' => string]
 */
public static function sendTemplate(string $to, string $subject, string $template, array $data = [], ?int $userId = null): array
{
    // Variabili globali sempre disponibili
    $data['app_name'] = $data['app_name'] ?? Settings::get('site_name', 'Ainstein');
    $data['app_url'] = $data['app_url'] ?? rtrim(env('APP_URL', 'https://ainstein.it'), '/');
    $data['year'] = date('Y');

    // Unsubscribe URL se userId disponibile
    if ($userId) {
        $data['unsubscribe_url'] = self::getUnsubscribeUrl($userId);
    } else {
        $data['unsubscribe_url'] = $data['app_url'] . '/profile';
    }

    // Legacy: passa anche le variabili camelCase per compatibilita con template PHP
    $data['appName'] = $data['app_name'];
    $data['appUrl'] = $data['app_url'];

    // 1. Cerca template in DB
    $dbTemplate = \Core\Database::fetch(
        "SELECT * FROM email_templates WHERE slug = ? AND is_active = 1",
        [$template]
    );

    if ($dbTemplate) {
        // Render body da DB con {{placeholder}} replacement
        $emailContent = self::renderFromDb($dbTemplate, $data);

        // Subject da DB (con placeholder replacement) — sovrascrive il parametro
        $subject = $dbTemplate['subject'];
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $subject = str_replace('{{' . $key . '}}', (string) $value, $subject);
            }
        }
        $subject = preg_replace('/\{\{[a-z_]+\}\}/', '', $subject);

        // Render layout wrapper (base.php) con il contenuto DB
        $data['emailContent'] = $emailContent;
        $data['preheader'] = strip_tags(mb_substr($emailContent, 0, 150));

        // Aggiungi footer unsubscribe al contenuto
        $footerText = Settings::get('email_footer_text', '');
        if (!empty($footerText)) {
            $data['emailContent'] .= '<hr class="divider"><p style="font-size: 12px; color: #94a3b8;">' . htmlspecialchars($footerText) . '</p>';
        }
        $data['emailContent'] .= '<p style="font-size: 11px; color: #94a3b8; margin-top: 12px;">'
            . '<a href="' . htmlspecialchars($data['unsubscribe_url']) . '" style="color: #64748b;">Gestisci preferenze email</a></p>';

        $layoutPath = ROOT_PATH . '/shared/views/emails/base.php';
        ob_start();
        extract($data);
        include $layoutPath;
        $htmlBody = ob_get_clean();

        return self::send($to, $subject, $htmlBody);
    }

    // 2. Fallback: template file PHP (comportamento originale)
    $templatePath = ROOT_PATH . '/shared/views/emails/' . $template . '.php';

    if (!file_exists($templatePath)) {
        return ['success' => false, 'message' => "Template email '{$template}' non trovato"];
    }

    ob_start();
    extract($data);
    include $templatePath;
    $htmlBody = ob_get_clean();

    return self::send($to, $subject, $htmlBody);
}
```

**Step 4: Add `renderPreview()` for admin panel**

```php
/**
 * Renderizza preview di un template con dati di esempio
 */
public static function renderPreview(string $slug, ?string $subjectOverride = null, ?string $bodyOverride = null): string
{
    $dbTemplate = \Core\Database::fetch(
        "SELECT * FROM email_templates WHERE slug = ?",
        [$slug]
    );

    if (!$dbTemplate) {
        return '<p>Template non trovato</p>';
    }

    // Dati di esempio per preview
    $sampleData = self::getSampleData($slug);
    $sampleData['app_name'] = Settings::get('site_name', 'Ainstein');
    $sampleData['app_url'] = rtrim(env('APP_URL', 'https://ainstein.it'), '/');
    $sampleData['year'] = date('Y');
    $sampleData['unsubscribe_url'] = '#';

    // Usa override se forniti (per preview live durante editing)
    $template = $dbTemplate;
    if ($subjectOverride !== null) $template['subject'] = $subjectOverride;
    if ($bodyOverride !== null) $template['body_html'] = $bodyOverride;

    $emailContent = self::renderFromDb($template, $sampleData);

    // Footer
    $footerText = Settings::get('email_footer_text', '');
    if (!empty($footerText)) {
        $emailContent .= '<hr class="divider"><p style="font-size: 12px; color: #94a3b8;">' . htmlspecialchars($footerText) . '</p>';
    }
    $emailContent .= '<p style="font-size: 11px; color: #94a3b8; margin-top: 12px;"><a href="#" style="color: #64748b;">Gestisci preferenze email</a></p>';

    $data = [
        'appName' => $sampleData['app_name'],
        'appUrl' => $sampleData['app_url'],
        'year' => $sampleData['year'],
        'emailContent' => $emailContent,
        'preheader' => '',
    ];

    $layoutPath = ROOT_PATH . '/shared/views/emails/base.php';
    ob_start();
    extract($data);
    include $layoutPath;
    return ob_get_clean();
}

/**
 * Dati di esempio per preview template
 */
private static function getSampleData(string $slug): array
{
    $samples = [
        'welcome' => ['user_name' => 'Mario Rossi', 'user_email' => 'mario@example.com', 'free_credits' => 30, 'login_url' => '#'],
        'password-reset' => ['user_email' => 'mario@example.com', 'reset_url' => '#'],
        'password-changed' => ['user_name' => 'Mario Rossi', 'user_email' => 'mario@example.com'],
        'email-changed' => ['user_name' => 'Mario Rossi', 'old_email' => 'vecchia@example.com', 'new_email' => 'nuova@example.com'],
        'project-invite' => ['project_name' => 'SEO Blog Aziendale', 'inviter_name' => 'Luca Bianchi', 'role' => 'Editor', 'accept_url' => '#'],
        'notification' => ['title' => 'Analisi SEO completata', 'body' => 'Il crawl del sito example.com e terminato con successo.', 'action_url' => '#', 'action_label' => 'Vai ai risultati'],
        'seo-alert' => ['project_name' => 'SEO Blog', 'domain' => 'example.com', 'alert_count' => 3, 'alerts_html' => '<table><tr><td>📉 Keyword "seo tool" scesa da #5 a #12</td></tr></table>', 'dashboard_url' => '#'],
        'admin-report' => ['period' => 'Settimana 8/2026', 'new_users' => 12, 'total_users' => 350, 'active_users' => 89, 'credits_consumed' => 1250, 'top_modules_html' => '<ol><li>AI Content (450 cr)</li><li>SEO Audit (320 cr)</li></ol>', 'api_errors' => 5, 'failed_jobs' => 2],
    ];

    return $samples[$slug] ?? [];
}
```

**Step 5: Verify syntax**

```bash
php -l services/EmailService.php
```

**Step 6: Commit**

```bash
git add services/EmailService.php
git commit -m "feat: enhance EmailService with DB template rendering and unsubscribe support"
```

---

## Task 3: Update Existing Email Callers for userId Parameter

**Files:**
- Modify: `services/EmailService.php` (lines 254-278: `sendWelcome()` and `sendPasswordReset()`)
- Modify: `public/index.php` (line 230: register route)
- Modify: `controllers/OAuthController.php` (line ~138: OAuth welcome email)
- Modify: `services/ProjectSharingService.php` (line ~171: invite email)

**Context:** The enhanced `sendTemplate()` now accepts an optional `$userId` parameter for unsubscribe links. We need to pass it from all call sites.

**Step 1: Update `sendWelcome()` in EmailService**

Find the method (around line 254) and add userId parameter:

```php
public static function sendWelcome(string $to, string $name, int $freeCredits = 30, ?int $userId = null): array
{
    return self::sendTemplate($to, "Benvenuto su " . Settings::get('site_name', 'Ainstein') . "!", 'welcome', [
        'userName' => $name,     // legacy camelCase for PHP file fallback
        'userEmail' => $to,      // legacy
        'freeCredits' => $freeCredits, // legacy
        'user_name' => $name,
        'user_email' => $to,
        'free_credits' => $freeCredits,
        'login_url' => rtrim(env('APP_URL', 'https://ainstein.it'), '/') . '/login',
    ], $userId);
}
```

**Step 2: Update `sendPasswordReset()` in EmailService**

```php
public static function sendPasswordReset(string $to, string $token, ?int $userId = null): array
{
    $appUrl = rtrim(env('APP_URL', 'https://ainstein.it'), '/');
    $resetUrl = $appUrl . '/reset-password?token=' . $token;

    return self::sendTemplate($to, "Reimposta la tua password", 'password-reset', [
        'resetUrl' => $resetUrl,  // legacy
        'userEmail' => $to,       // legacy
        'reset_url' => $resetUrl,
        'user_email' => $to,
    ], $userId);
}
```

**Step 3: Update register route in `public/index.php` (line 230)**

Change:
```php
\Services\EmailService::sendWelcome($email, $name, $config['free_credits'] ?? 30);
```
To:
```php
\Services\EmailService::sendWelcome($email, $name, $config['free_credits'] ?? 30, $userId);
```

**Step 4: Update OAuthController (around line 138)**

Find the `sendWelcome()` call and add `$user['id']` as the 4th parameter.

**Step 5: Update ProjectSharingService (around line 171)**

Find the `sendTemplate()` call for `project-invite` and add `null` as the 5th parameter (external users don't have a userId yet). Leave as-is if no userId is available.

**Step 6: Verify syntax on all modified files**

```bash
php -l services/EmailService.php
php -l public/index.php
php -l controllers/OAuthController.php
php -l services/ProjectSharingService.php
```

**Step 7: Commit**

```bash
git add services/EmailService.php public/index.php controllers/OAuthController.php services/ProjectSharingService.php
git commit -m "feat: pass userId to email methods for unsubscribe link support"
```

---

## Task 4: New Email Templates — password-changed and email-changed

**Files:**
- Modify: `public/index.php` (find `/profile/password` route around line 621)
- No new PHP template files needed (templates live in DB from Task 1 seed)

**Context:** The DB seed in Task 1 creates `password-changed` and `email-changed` templates. We just need to add the `sendTemplate()` calls at the right trigger points.

**Step 1: Add email on password change**

In `public/index.php`, find the `/profile/password` POST route (around line 621-649). After the password is successfully updated (after `Auth::updatePassword()`), add:

```php
// Notifica cambio password via email
try {
    $user = Auth::user();
    \Services\EmailService::sendTemplate(
        $user['email'],
        'Password modificata',
        'password-changed',
        [
            'user_name' => $user['name'],
            'user_email' => $user['email'],
        ],
        $user['id']
    );
} catch (\Exception $e) {
    error_log('Password changed email failed: ' . $e->getMessage());
}
```

**Step 2: Check if email change functionality exists**

Read `public/index.php` to see if there's a `/profile/email` route. If not, this template will be used when that feature is added. Skip the trigger integration for now and note it in the commit message.

**Step 3: Verify syntax**

```bash
php -l public/index.php
```

**Step 4: Commit**

```bash
git add public/index.php
git commit -m "feat: send password-changed email notification on profile password update"
```

---

## Task 5: Admin Email Template Controller

**Files:**
- Create: `admin/controllers/EmailTemplateController.php`
- Modify: `admin/routes.php` (add new routes after line 25)

**Context:** Read `admin/controllers/AdminController.php` for controller patterns (namespace `Admin\Controllers`, uses `Middleware::admin()` in constructor, returns `View::render()` or JSON). Read `admin/routes.php` for route patterns.

**Step 1: Create the controller**

```php
<?php

namespace Admin\Controllers;

use Core\Database;
use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\ModuleLoader;
use Core\Settings;

class EmailTemplateController
{
    public function __construct()
    {
        Middleware::admin();
    }

    /**
     * Lista tutti i template email
     */
    public function index(): string
    {
        $templates = Database::fetchAll(
            "SELECT id, slug, name, category, is_active, updated_at FROM email_templates ORDER BY category, name"
        );

        return View::render('admin/email-templates/index', [
            'title' => 'Template Email',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'templates' => $templates,
        ]);
    }

    /**
     * Form modifica template
     */
    public function edit(string $slug): string
    {
        $template = Database::fetch(
            "SELECT * FROM email_templates WHERE slug = ?",
            [$slug]
        );

        if (!$template) {
            $_SESSION['_flash']['error'] = 'Template non trovato';
            \Core\Router::redirect('/admin/email-templates');
            return '';
        }

        $template['available_vars'] = json_decode($template['available_vars'], true) ?? [];

        return View::render('admin/email-templates/edit', [
            'title' => 'Modifica Template: ' . $template['name'],
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'template' => $template,
        ]);
    }

    /**
     * Salva modifiche template
     */
    public function update(string $slug): string
    {
        Middleware::csrf();

        $template = Database::fetch(
            "SELECT id FROM email_templates WHERE slug = ?",
            [$slug]
        );

        if (!$template) {
            header('Content-Type: application/json');
            return json_encode(['success' => false, 'message' => 'Template non trovato']);
        }

        $subject = trim($_POST['subject'] ?? '');
        $bodyHtml = $_POST['body_html'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($subject)) {
            $_SESSION['_flash']['error'] = 'L\'oggetto e obbligatorio';
            \Core\Router::redirect('/admin/email-templates/' . $slug);
            return '';
        }

        Database::update('email_templates', [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'is_active' => $isActive,
        ], 'slug = ?', [$slug]);

        $_SESSION['_flash']['success'] = 'Template aggiornato con successo';
        \Core\Router::redirect('/admin/email-templates/' . $slug);
        return '';
    }

    /**
     * Preview AJAX (restituisce HTML renderizzato)
     */
    public function preview(string $slug): string
    {
        $subject = $_POST['subject'] ?? null;
        $bodyHtml = $_POST['body_html'] ?? null;

        $html = \Services\EmailService::renderPreview($slug, $subject, $bodyHtml);

        header('Content-Type: text/html; charset=utf-8');
        return $html;
    }

    /**
     * Invia email di test
     */
    public function sendTest(string $slug): string
    {
        Middleware::csrf();

        $template = Database::fetch(
            "SELECT * FROM email_templates WHERE slug = ?",
            [$slug]
        );

        if (!$template) {
            header('Content-Type: application/json');
            return json_encode(['success' => false, 'message' => 'Template non trovato']);
        }

        $adminEmail = Auth::user()['email'] ?? '';
        $sampleData = \Services\EmailService::getSampleDataPublic($slug);

        $result = \Services\EmailService::sendTemplate(
            $adminEmail,
            $template['subject'],
            $slug,
            $sampleData,
            Auth::id()
        );

        header('Content-Type: application/json');
        return json_encode($result);
    }

    /**
     * Ripristina template a default
     */
    public function resetDefault(string $slug): string
    {
        Middleware::csrf();

        $defaults = \Services\EmailService::getDefaultTemplates();

        if (!isset($defaults[$slug])) {
            header('Content-Type: application/json');
            return json_encode(['success' => false, 'message' => 'Default non trovato per questo template']);
        }

        $default = $defaults[$slug];
        Database::update('email_templates', [
            'subject' => $default['subject'],
            'body_html' => $default['body_html'],
            'is_active' => 1,
        ], 'slug = ?', [$slug]);

        header('Content-Type: application/json');
        return json_encode(['success' => true, 'message' => 'Template ripristinato', 'subject' => $default['subject'], 'body_html' => $default['body_html']]);
    }

    /**
     * Toggle attivo/disattivo
     */
    public function toggle(string $slug): string
    {
        Middleware::csrf();

        $template = Database::fetch(
            "SELECT id, is_active FROM email_templates WHERE slug = ?",
            [$slug]
        );

        if (!$template) {
            header('Content-Type: application/json');
            return json_encode(['success' => false, 'message' => 'Template non trovato']);
        }

        $newState = $template['is_active'] ? 0 : 1;
        Database::update('email_templates', ['is_active' => $newState], 'slug = ?', [$slug]);

        header('Content-Type: application/json');
        return json_encode(['success' => true, 'is_active' => $newState]);
    }
}
```

**Step 2: Add `getSampleDataPublic()` and `getDefaultTemplates()` to EmailService**

In `services/EmailService.php`, make `getSampleData()` accessible and add `getDefaultTemplates()`:

```php
/**
 * Dati di esempio per preview (public accessor)
 */
public static function getSampleDataPublic(string $slug): array
{
    return self::getSampleData($slug);
}

/**
 * Template default hardcoded (per ripristino)
 * Ritorna array slug => ['subject' => ..., 'body_html' => ...]
 */
public static function getDefaultTemplates(): array
{
    // Return the same content used in the DB seed migration
    // This is loaded from a separate file to keep EmailService clean
    $path = ROOT_PATH . '/config/email-defaults.php';
    if (file_exists($path)) {
        return require $path;
    }
    return [];
}
```

**Step 3: Create `config/email-defaults.php`**

This file returns the default template content (same as the DB seed). It acts as the "reset to default" source.

```php
<?php
// Default email template content for reset functionality
// Each key is a template slug
return [
    'welcome' => [
        'subject' => 'Benvenuto su {{app_name}}!',
        'body_html' => '...same as DB seed...',
    ],
    // ... all 8 templates
];
```

**Step 4: Add routes to `admin/routes.php`**

Add after line 25 (after test-email route):

```php
// Email Templates
Router::get('/admin/email-templates', [EmailTemplateController::class, 'index']);
Router::get('/admin/email-templates/{slug}', [EmailTemplateController::class, 'edit']);
Router::post('/admin/email-templates/{slug}', [EmailTemplateController::class, 'update']);
Router::post('/admin/email-templates/{slug}/preview', [EmailTemplateController::class, 'preview']);
Router::post('/admin/email-templates/{slug}/test', [EmailTemplateController::class, 'sendTest']);
Router::post('/admin/email-templates/{slug}/reset', [EmailTemplateController::class, 'resetDefault']);
Router::post('/admin/email-templates/{slug}/toggle', [EmailTemplateController::class, 'toggle']);
```

Add the use statement at top of file:
```php
use Admin\Controllers\EmailTemplateController;
```

**Step 5: Verify syntax**

```bash
php -l admin/controllers/EmailTemplateController.php
php -l admin/routes.php
php -l services/EmailService.php
php -l config/email-defaults.php
```

**Step 6: Commit**

```bash
git add admin/controllers/EmailTemplateController.php admin/routes.php services/EmailService.php config/email-defaults.php
git commit -m "feat: add admin email template controller with preview, test, and reset"
```

---

## Task 6: Admin Email Template Views — List Page

**Files:**
- Create: `admin/views/email-templates/index.php`

**Context:** Read `admin/views/settings.php` for admin view patterns (uses `$title`, `$user`, layout structure). Follow table CSS standards from CLAUDE.md (rounded-xl, px-4 py-3, dark:bg-slate-700/50). Check existing admin views for sidebar structure.

**Step 1: Create the list view**

Page structure:
- Header: "Template Email" title + subtitle "Gestisci i template delle email inviate dalla piattaforma"
- Category filter tabs: Tutte | Auth | Notifiche | Moduli | Report (simple links with `?category=xxx`)
- Table: Nome, Slug (monospace badge), Categoria (colored badge), Stato (toggle button), Ultimo aggiornamento, Azioni (Modifica button)
- Category badge colors: auth=blue, notification=amber, module=emerald, report=purple
- Active/inactive: green badge "Attivo" / red badge "Disattivato"
- Each row links to `/admin/email-templates/{slug}`

Follow standard admin table patterns from the codebase.

**Step 2: Verify syntax**

```bash
php -l admin/views/email-templates/index.php
```

**Step 3: Commit**

```bash
git add admin/views/email-templates/index.php
git commit -m "feat: add admin email template list view"
```

---

## Task 7: Admin Email Template Views — Edit Page with Live Preview

**Files:**
- Create: `admin/views/email-templates/edit.php`

**Context:** This is the core admin editing experience. Two-column layout: left column has form fields, right column has live preview. Alpine.js for reactivity. Read existing admin views for consistent styling.

**Step 1: Create the edit view**

Page structure (two columns with `grid grid-cols-1 lg:grid-cols-2 gap-6`):

**Left column (form):**
- Back link to `/admin/email-templates`
- Template name (read-only, informational)
- Description (read-only, explains when email is sent)
- Subject input (text field, shows placeholder variables like `{{user_name}}`)
- Body HTML textarea (monospace font, `font-mono text-sm`, ~25 rows)
- Available variables section: chips/buttons for each variable. Clicking copies `{{var_name}}` text or inserts at cursor position in textarea
- Toggle: "Attivo" on/off
- Buttons row: "Salva modifiche" (submit form), "Invia test" (AJAX), "Ripristina default" (AJAX + confirm)

**Right column (preview):**
- Sticky panel (`sticky top-6`)
- Subject preview line (rendered with sample data)
- iframe or div with full email HTML preview (refreshed on input change with debounce)
- Preview loaded via POST to `/admin/email-templates/{slug}/preview` with current subject + body_html

**Alpine.js `emailEditor()` component:**
- `subject` and `bodyHtml` bound to form fields
- `previewHtml` rendered in iframe
- `debounce` on input: after 500ms of no typing, POST to preview endpoint
- `insertVariable(varName)`: inserts `{{varName}}` at textarea cursor position
- `sendTest()`: POST to test endpoint, show toast on success/failure
- `resetDefault()`: confirm dialog, POST to reset endpoint, update form fields
- `saveTemplate()`: submit form

**Step 2: Verify syntax**

```bash
php -l admin/views/email-templates/edit.php
```

**Step 3: Commit**

```bash
git add admin/views/email-templates/edit.php
git commit -m "feat: add admin email template editor with live preview"
```

---

## Task 8: Add Email Templates Link to Admin Sidebar

**Files:**
- Modify: find admin sidebar/nav (likely in `shared/views/components/nav-items.php` or `admin/views/` layout)

**Context:** Read the admin layout/sidebar file. Add "Template Email" link with envelope Heroicon SVG, positioned after Settings or after existing admin nav items.

**Step 1: Find and read the admin sidebar file**

Search for admin nav items. It might be in `shared/views/layout.php` (check the admin section of the sidebar) or a dedicated admin layout.

**Step 2: Add nav item**

Add entry for `/admin/email-templates` with label "Template Email" and envelope Heroicon SVG.

**Step 3: Verify and commit**

```bash
php -l [sidebar file]
git add [sidebar file]
git commit -m "feat: add email templates link to admin sidebar"
```

---

## Task 9: Email Branding Settings in Admin

**Files:**
- Modify: `admin/views/settings.php` (add fields to existing Advanced or Branding tab)
- Modify: `shared/views/emails/base.php` (use dynamic branding settings)

**Context:** Read `admin/views/settings.php` to find existing tabs structure. Add email branding fields: logo URL, brand color, footer text. Read `shared/views/emails/base.php` to make colors/logo dynamic.

**Step 1: Add email branding fields to admin settings**

In the existing settings form, add to the "Branding" or "Advanced" tab:
- `email_logo_url` (input text): URL logo per header email
- `email_brand_color` (input color + text): Colore primario email (default #006e96)
- `email_footer_text` (textarea): Testo aggiuntivo footer email

**Step 2: Update `base.php` layout to use dynamic settings**

At the top of `shared/views/emails/base.php`, read branding settings:

```php
<?php
$brandColor = \Core\Settings::get('email_brand_color', '#006e96');
$logoUrl = \Core\Settings::get('email_logo_url', '');
$hoverColor = self::darkenColor($brandColor); // or calculate darker shade
?>
```

Replace hardcoded `#006e96` in CSS with `<?= $brandColor ?>`.
In the header section, if `$logoUrl` is set, show `<img>` instead of text app name.

**Step 3: Verify syntax**

```bash
php -l admin/views/settings.php
php -l shared/views/emails/base.php
```

**Step 4: Commit**

```bash
git add admin/views/settings.php shared/views/emails/base.php
git commit -m "feat: add email branding settings (logo, color, footer) to admin panel"
```

---

## Task 10: Unsubscribe Page + Routes

**Files:**
- Create: `shared/views/email-preferences.php`
- Modify: `public/index.php` (add routes)

**Context:** Public page accessible without login via token. Shows toggle list for each email notification type. Reads/writes `notification_preferences` table (same as notification system plan). Minimal branded design.

**Step 1: Add routes to `public/index.php`**

Add before the auth routes (these are public, no login required):

```php
// Email preferences (unsubscribe)
Router::get('/email/preferences', function () {
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        Router::redirect('/login');
        return '';
    }

    $tokenRecord = Database::fetch(
        "SELECT user_id FROM email_unsubscribe_tokens WHERE token = ?",
        [$token]
    );

    if (!$tokenRecord) {
        return View::render('email-preferences', [
            'title' => 'Preferenze Email',
            'error' => 'Link non valido o scaduto.',
            'token' => '',
            'preferences' => [],
        ], null);
    }

    $preferences = \Services\NotificationService::getPreferences($tokenRecord['user_id']);

    return View::render('email-preferences', [
        'title' => 'Preferenze Email',
        'token' => $token,
        'preferences' => $preferences,
        'error' => null,
    ], null);
});

Router::post('/email/preferences', function () {
    $token = $_POST['token'] ?? '';
    $tokenRecord = Database::fetch(
        "SELECT user_id FROM email_unsubscribe_tokens WHERE token = ?",
        [$token]
    );

    if (!$tokenRecord) {
        Router::redirect('/login');
        return '';
    }

    $prefs = [];
    $types = ['project_invite', 'project_invite_accepted', 'project_invite_declined', 'operation_completed', 'operation_failed'];
    foreach ($types as $type) {
        $prefs[$type] = isset($_POST['email_' . $type]);
    }

    \Services\NotificationService::updatePreferences($tokenRecord['user_id'], $prefs);

    return View::render('email-preferences', [
        'title' => 'Preferenze Email',
        'token' => $token,
        'preferences' => \Services\NotificationService::getPreferences($tokenRecord['user_id']),
        'success' => 'Preferenze salvate con successo.',
        'error' => null,
    ], null);
});
```

**Step 2: Create the view**

Standalone page (no sidebar, uses null layout or minimal layout):
- Ainstein logo/name at top
- "Preferenze Email" heading
- Description: "Scegli per quali notifiche ricevere email."
- If error: show error message
- Form with toggles for each email type (label + description + checkbox)
- Submit button: "Salva preferenze"
- Success flash if saved
- Minimal footer

**Step 3: Verify syntax**

```bash
php -l shared/views/email-preferences.php
php -l public/index.php
```

**Step 4: Commit**

```bash
git add shared/views/email-preferences.php public/index.php
git commit -m "feat: add token-based email preferences page for unsubscribe"
```

---

## Task 11: Migrate SEO Tracking Alerts to EmailService

**Files:**
- Modify: `modules/seo-tracking/services/AlertService.php` (lines 161-214: `sendEmailDigest()`)

**Context:** Read `modules/seo-tracking/services/AlertService.php` (lines 155-215). The current method uses `@mail()` (line 205) with plain text. Replace with `EmailService::sendTemplate('seo-alert', ...)` using the HTML template from DB.

**Step 1: Rewrite `sendEmailDigest()`**

Replace the method body (keep signature). Key changes:
- Build `alerts_html` as an HTML table with colored rows per alert type
- Use `EmailService::sendTemplate('seo-alert', $data)` instead of `mail()`
- Keep the same guard clauses (project check, settings check, empty emails, empty alerts)
- Keep the "mark as read" logic at the end

```php
public function sendEmailDigest(int $projectId): bool
{
    $project = $this->project->find($projectId);
    $settings = $this->alertSettings->getByProject($projectId);

    if (!$project || !$settings || !$settings['email_enabled']) {
        return false;
    }

    $emails = $project['notification_emails'] ? json_decode($project['notification_emails'], true) : [];
    if (empty($emails)) return false;

    $alerts = $this->alert->getUnread($projectId);
    if (empty($alerts)) return false;

    // Costruisci tabella HTML alert
    $alertsHtml = '<table style="width:100%;border-collapse:collapse;margin:16px 0;">';
    foreach ($alerts as $alert) {
        $icon = match($alert['alert_type']) {
            'position_gain' => '📈',
            'position_drop' => '📉',
            'traffic_drop' => '⚠️',
            default => '🔔',
        };
        $severityColor = match($alert['severity'] ?? 'info') {
            'critical' => '#ef4444',
            'warning' => '#f59e0b',
            'info' => '#3b82f6',
            default => '#64748b',
        };
        $alertsHtml .= '<tr><td style="padding:8px;border-bottom:1px solid #e2e8f0;">'
            . $icon . ' <span style="color:' . $severityColor . ';font-weight:600;">[' . htmlspecialchars($alert['severity'] ?? 'info') . ']</span> '
            . htmlspecialchars($alert['message'])
            . '</td></tr>';
    }
    $alertsHtml .= '</table>';

    $dashboardUrl = url('/seo-tracking/project/' . $projectId . '/alerts');

    $data = [
        'project_name' => $project['name'],
        'domain' => $project['domain'] ?? '',
        'alert_count' => count($alerts),
        'alerts_html' => $alertsHtml,
        'dashboard_url' => $dashboardUrl,
    ];

    $subject = "[SEO Tracking] {$project['name']} - " . count($alerts) . " nuovi alert";

    $success = true;
    foreach ($emails as $email) {
        $result = \Services\EmailService::sendTemplate(
            trim($email), $subject, 'seo-alert', $data
        );
        if (!$result['success']) $success = false;
    }

    // Marca come letti
    foreach ($alerts as $alert) {
        $this->alert->markAsRead($alert['id']);
    }

    return $success;
}
```

**Step 2: Verify syntax**

```bash
php -l modules/seo-tracking/services/AlertService.php
```

**Step 3: Commit**

```bash
git add modules/seo-tracking/services/AlertService.php
git commit -m "feat: migrate SEO Tracking alert emails from mail() to EmailService with HTML template"
```

---

## Task 12: Admin Platform Report Cron

**Files:**
- Create: `cron/admin-report.php`

**Context:** Read `cron/cleanup-data.php` for cron script patterns (bootstrap, logging, Database usage). This cron runs weekly (Monday 8:00). Collects platform metrics and emails all admins.

**Step 1: Create the cron script**

```php
<?php
/**
 * Admin Report — Digest settimanale piattaforma
 * Cron: 0 8 * * 1 (Lunedi alle 8:00)
 */
require_once __DIR__ . '/bootstrap.php';

use Core\Database;
use Core\Logger;

$logger = Logger::channel('admin-report');

try {
    // Periodo: ultima settimana
    $periodStart = date('Y-m-d', strtotime('last monday'));
    $periodEnd = date('Y-m-d');
    $period = "Settimana " . date('W/Y');

    // Metriche
    $newUsers = Database::count('users', 'created_at >= ?', [$periodStart]);
    $totalUsers = Database::count('users');
    $activeUsers = Database::count('users', 'last_login_at >= ?', [$periodStart]);

    $creditsConsumed = Database::fetch(
        "SELECT COALESCE(SUM(ABS(amount)), 0) as total FROM credit_transactions
         WHERE type = 'consume' AND created_at >= ?", [$periodStart]
    )['total'] ?? 0;

    // Top moduli per crediti
    $topModules = Database::fetchAll(
        "SELECT module, SUM(ABS(amount)) as total FROM credit_transactions
         WHERE type = 'consume' AND created_at >= ?
         GROUP BY module ORDER BY total DESC LIMIT 5", [$periodStart]
    );
    $topModulesHtml = '<ol style="margin:8px 0;padding-left:20px;">';
    foreach ($topModules as $m) {
        $topModulesHtml .= '<li>' . htmlspecialchars($m['module']) . ' (' . $m['total'] . ' crediti)</li>';
    }
    $topModulesHtml .= '</ol>';
    if (empty($topModules)) $topModulesHtml = '<p>Nessuna attivita nel periodo.</p>';

    // Errori API
    $apiErrors = Database::count('api_logs', 'http_code >= 400 AND created_at >= ?', [$periodStart]);

    // Job falliti (check if jobs table exists)
    $failedJobs = 0;
    try {
        $failedJobs = Database::count('background_jobs', "status = 'failed' AND created_at >= ?", [$periodStart]);
    } catch (\Exception $e) {
        // Table may not exist
    }

    // Invia a tutti gli admin
    $admins = Database::fetchAll("SELECT id, email FROM users WHERE is_admin = 1 AND is_active = 1");

    $data = [
        'period' => $period,
        'new_users' => $newUsers,
        'total_users' => $totalUsers,
        'active_users' => $activeUsers,
        'credits_consumed' => $creditsConsumed,
        'top_modules_html' => $topModulesHtml,
        'api_errors' => $apiErrors,
        'failed_jobs' => $failedJobs,
    ];

    $sent = 0;
    foreach ($admins as $admin) {
        $result = \Services\EmailService::sendTemplate(
            $admin['email'],
            "Report piattaforma {$period}",
            'admin-report',
            $data,
            $admin['id']
        );
        if ($result['success']) $sent++;
    }

    $logger->info("Report inviato a {$sent}/" . count($admins) . " admin");

} catch (\Exception $e) {
    $logger->error('Admin report failed: ' . $e->getMessage());
}
```

**Step 2: Verify syntax**

```bash
php -l cron/admin-report.php
```

**Step 3: Commit**

```bash
git add cron/admin-report.php
git commit -m "feat: add weekly admin platform report cron"
```

---

## Task 13: Cleanup Cron — Unsubscribe Tokens

**Files:**
- Modify: `cron/cleanup-data.php`

**Context:** Add cleanup for orphaned unsubscribe tokens (where user no longer exists). Add to the existing cleanup sections array.

**Step 1: Add cleanup function**

Add a function near the other cleanup functions:

```php
function cleanupOrphanedUnsubscribeTokens(): int
{
    try {
        return \Core\Database::execute(
            "DELETE t FROM email_unsubscribe_tokens t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE u.id IS NULL"
        );
    } catch (\Exception $e) {
        return 0;
    }
}
```

**Step 2: Add to sections array**

Add a new entry to the cleanup sections:

```php
['email_unsubscribe_tokens (orfani)', 'cleanupOrphanedUnsubscribeTokens', []],
```

**Step 3: Verify syntax**

```bash
php -l cron/cleanup-data.php
```

**Step 4: Commit**

```bash
git add cron/cleanup-data.php
git commit -m "feat: add orphaned unsubscribe token cleanup to daily cron"
```

---

## Task 14: Update Documentation

**Files:**
- Modify: `docs/data-model.html` — add email_templates and email_unsubscribe_tokens tables to ER diagram
- Modify: `CLAUDE.md` — add email system info

**Step 1: Add tables to `docs/data-model.html` ER diagram**

Read the file, find the Mermaid erDiagram section, add:

```mermaid
    email_templates {
        int id PK
        varchar slug UK
        varchar name
        varchar subject
        text body_html
        json available_vars
        varchar category
        tinyint is_active
    }
    email_unsubscribe_tokens {
        int id PK
        int user_id FK
        varchar token UK
    }
    email_unsubscribe_tokens }|--|| users : "user_id"
```

**Step 2: Add to CLAUDE.md**

After the "Condivisione Progetti" section, add:

```markdown
### Sistema Email Admin-Customizzabile

| Tabella | Scopo |
|---------|-------|
| `email_templates` | Template email editabili dall'admin (subject + body HTML con {{placeholder}}) |
| `email_unsubscribe_tokens` | Token per pagina gestione preferenze email (no login) |

**Rendering**: `EmailService::sendTemplate()` cerca prima in DB, poi fallback a file PHP in `shared/views/emails/`.

**Admin panel**: `/admin/email-templates` — lista, modifica con textarea + preview live, test, ripristina default.

**Template categories**: auth (welcome, password-reset, password-changed, email-changed), notification (project-invite, notification), module (seo-alert), report (admin-report)

**Unsubscribe**: ogni email include link a `/email/preferences?token=XXX` (pagina pubblica senza login)

**Cron**: `cron/admin-report.php` settimanale (Lunedi 8:00) — report piattaforma per admin
```

**Step 3: Commit**

```bash
git add docs/data-model.html CLAUDE.md
git commit -m "docs: update documentation with email template system"
```

---

## Execution Order Summary

| Task | Description | Dependencies |
|------|-------------|-------------|
| 1 | Database migration + seed | None |
| 2 | EmailService enhancement (DB rendering) | Task 1 |
| 3 | Update existing email callers (userId) | Task 2 |
| 4 | Password-changed email trigger | Task 2 |
| 5 | Admin EmailTemplateController | Task 2 |
| 6 | Admin list view | Task 5 |
| 7 | Admin edit view + live preview | Task 5 |
| 8 | Admin sidebar link | Task 6 |
| 9 | Email branding settings | Task 2 |
| 10 | Unsubscribe page + routes | Task 2 |
| 11 | Migrate SEO Tracking alerts | Task 2 |
| 12 | Admin report cron | Task 2 |
| 13 | Cleanup cron | Task 1 |
| 14 | Documentation | All tasks |

**Parallelizable:** Tasks 3+4+5+9+10+11+12+13 can all start after Task 2. Tasks 6+7 after Task 5. Task 8 after Task 6.
