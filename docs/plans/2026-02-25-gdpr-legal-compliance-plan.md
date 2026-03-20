# GDPR & Legal Compliance Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Make Ainstein SEO Toolkit fully compliant with GDPR and Italian privacy law — privacy policy, terms of service, cookie policy, consent tracking, account deletion, data export, admin GDPR panel.

**Architecture:** Modulo trasversale "legal/compliance" integrato nel core. Services dedicati (ConsentService, AccountDeletionService, DataExportService) + LegalController per pagine pubbliche + AdminGdprController per admin panel. Dati titolare configurabili da admin settings. Consenso tracciato in DB con versioning.

**Tech Stack:** PHP 8+, MySQL, Tailwind CSS, Alpine.js, Heroicons SVG

**Design doc:** `docs/plans/2026-02-25-gdpr-legal-compliance-design.md`

---

## Task 1: Database Migration

**Files:**
- Create: `migrations/2026_02_25_gdpr_compliance.sql`

**Step 1: Create migration file**

```sql
-- GDPR Compliance Migration
-- 2026-02-25

-- Consensi utente (tracciabilita GDPR)
CREATE TABLE IF NOT EXISTS consent_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    consent_type ENUM('terms', 'privacy', 'cookie', 'marketing') NOT NULL,
    version VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    accepted_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    INDEX idx_consent_user (user_id),
    INDEX idx_consent_type (consent_type),
    INDEX idx_consent_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log cancellazioni account (audit trail)
CREATE TABLE IF NOT EXISTS account_deletion_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    reason TEXT NULL,
    deleted_data_summary JSON NULL,
    deleted_at DATETIME NOT NULL,
    deleted_by ENUM('user', 'admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Richieste export dati
CREATE TABLE IF NOT EXISTS data_export_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'expired') DEFAULT 'pending',
    file_path VARCHAR(500) NULL,
    requested_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    expires_at DATETIME NULL,
    INDEX idx_export_user (user_id),
    INDEX idx_export_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Campi GDPR sulla tabella users
ALTER TABLE users ADD COLUMN IF NOT EXISTS privacy_accepted_at DATETIME NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS terms_accepted_at DATETIME NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS legal_version_accepted VARCHAR(20) NULL;

-- Settings legali iniziali
INSERT INTO settings (key_name, value) VALUES
    ('legal_company_name', ''),
    ('legal_vat_number', ''),
    ('legal_address', ''),
    ('legal_pec', ''),
    ('legal_email', ''),
    ('legal_dpo_email', ''),
    ('legal_version', '2026.1'),
    ('legal_last_updated', '2026-02-25')
ON DUPLICATE KEY UPDATE key_name = key_name;
```

**Step 2: Run migration locally**

```bash
mysql -u root seo_toolkit < migrations/2026_02_25_gdpr_compliance.sql
```

Expected: Query OK, 3 tables created, users altered, settings inserted.

**Step 3: Verify tables exist**

```bash
mysql -u root seo_toolkit -e "SHOW TABLES LIKE 'consent%'; SHOW TABLES LIKE 'account%'; SHOW TABLES LIKE 'data_export%'; DESCRIBE users;"
```

Expected: 3 new tables visible, `privacy_accepted_at`, `terms_accepted_at`, `legal_version_accepted` in users.

**Step 4: Commit**

```bash
git add migrations/2026_02_25_gdpr_compliance.sql
git commit -m "feat(gdpr): add database migration for consent, deletion logs, export requests"
```

---

## Task 2: ConsentService

**Files:**
- Create: `services/ConsentService.php`

**Context:** This service handles recording consent, checking current version, and managing re-acceptance flow.

**Step 1: Create ConsentService**

```php
<?php

namespace Services;

use Core\Database;
use Core\Settings;

class ConsentService
{
    /**
     * Record user consent for a specific type
     */
    public static function record(int $userId, string $type, ?string $ip = null, ?string $userAgent = null): bool
    {
        $version = Settings::get('legal_version', '2026.1');
        $now = date('Y-m-d H:i:s');

        Database::insert('consent_records', [
            'user_id' => $userId,
            'consent_type' => $type,
            'version' => $version,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'accepted_at' => $now
        ]);

        // Aggiorna colonne utente
        if ($type === 'privacy') {
            Database::update('users', ['privacy_accepted_at' => $now, 'legal_version_accepted' => $version], 'id = ?', [$userId]);
        } elseif ($type === 'terms') {
            Database::update('users', ['terms_accepted_at' => $now, 'legal_version_accepted' => $version], 'id = ?', [$userId]);
        }

        return true;
    }

    /**
     * Record both terms and privacy consent (registration)
     */
    public static function recordRegistration(int $userId, ?string $ip = null, ?string $userAgent = null): void
    {
        $version = Settings::get('legal_version', '2026.1');
        $now = date('Y-m-d H:i:s');

        foreach (['terms', 'privacy'] as $type) {
            Database::insert('consent_records', [
                'user_id' => $userId,
                'consent_type' => $type,
                'version' => $version,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'accepted_at' => $now
            ]);
        }

        Database::update('users', [
            'privacy_accepted_at' => $now,
            'terms_accepted_at' => $now,
            'legal_version_accepted' => $version
        ], 'id = ?', [$userId]);
    }

    /**
     * Check if user has accepted current legal version
     */
    public static function needsReacceptance(array $user): bool
    {
        $currentVersion = Settings::get('legal_version', '2026.1');
        $userVersion = $user['legal_version_accepted'] ?? null;

        if (!$userVersion) return true;

        return version_compare($userVersion, $currentVersion, '<');
    }

    /**
     * Get all consent records for a user
     */
    public static function getUserConsents(int $userId): array
    {
        return Database::fetchAll(
            "SELECT * FROM consent_records WHERE user_id = ? ORDER BY accepted_at DESC",
            [$userId]
        );
    }

    /**
     * Get all consent records (admin, paginated)
     */
    public static function getAllConsents(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $where = "1=1";
        $params = [];

        if (!empty($filters['consent_type'])) {
            $where .= " AND cr.consent_type = ?";
            $params[] = $filters['consent_type'];
        }
        if (!empty($filters['version'])) {
            $where .= " AND cr.version = ?";
            $params[] = $filters['version'];
        }

        $total = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM consent_records cr WHERE $where",
            $params
        )['cnt'];

        $offset = ($page - 1) * $perPage;
        $records = Database::fetchAll(
            "SELECT cr.*, u.name as user_name, u.email as user_email
             FROM consent_records cr
             LEFT JOIN users u ON u.id = cr.user_id
             WHERE $where
             ORDER BY cr.accepted_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return ['records' => $records, 'total' => $total];
    }
}
```

**Step 2: Verify syntax**

```bash
php -l services/ConsentService.php
```

Expected: No syntax errors detected.

**Step 3: Commit**

```bash
git add services/ConsentService.php
git commit -m "feat(gdpr): add ConsentService for consent tracking and version checking"
```

---

## Task 3: LegalController + Routes + Pagine legali

**Files:**
- Create: `controllers/LegalController.php`
- Create: `shared/views/legal/privacy.php`
- Create: `shared/views/legal/terms.php`
- Create: `shared/views/legal/cookies.php`
- Create: `shared/views/legal/accept.php`
- Modify: `public/index.php` (add routes)
- Modify: `public/includes/site-footer.php:30-35` (update links)

### Step 1: Create LegalController

```php
<?php

namespace Controllers;

use Core\View;
use Core\Auth;
use Core\Settings;
use Core\Middleware;
use Services\ConsentService;

class LegalController
{
    /**
     * Privacy Policy page (public)
     */
    public function privacy(): string
    {
        $user = Auth::user();
        $legal = $this->getLegalSettings();

        return View::render('legal/privacy', [
            'title' => 'Privacy Policy',
            'legal' => $legal,
            'user' => $user,
            'modules' => $user ? \Core\ModuleLoader::getActiveModules() : []
        ]);
    }

    /**
     * Terms of Service page (public)
     */
    public function terms(): string
    {
        $user = Auth::user();
        $legal = $this->getLegalSettings();

        return View::render('legal/terms', [
            'title' => 'Termini di Servizio',
            'legal' => $legal,
            'user' => $user,
            'modules' => $user ? \Core\ModuleLoader::getActiveModules() : []
        ]);
    }

    /**
     * Cookie Policy page (public)
     */
    public function cookies(): string
    {
        $user = Auth::user();
        $legal = $this->getLegalSettings();

        return View::render('legal/cookies', [
            'title' => 'Cookie Policy',
            'legal' => $legal,
            'user' => $user,
            'modules' => $user ? \Core\ModuleLoader::getActiveModules() : []
        ]);
    }

    /**
     * Show re-acceptance form (auth required)
     */
    public function showAcceptForm(): string
    {
        Middleware::auth();
        $user = Auth::user();
        $legal = $this->getLegalSettings();

        return View::render('legal/accept', [
            'title' => 'Aggiornamento Termini',
            'legal' => $legal,
            'user' => $user,
            'modules' => \Core\ModuleLoader::getActiveModules()
        ]);
    }

    /**
     * Process re-acceptance (auth required)
     */
    public function acceptUpdated(): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        if (empty($_POST['accept_terms'])) {
            $_SESSION['flash_error'] = 'Devi accettare i termini per continuare.';
            header('Location: ' . url('/legal/accept'));
            exit;
        }

        ConsentService::record($user['id'], 'terms', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
        ConsentService::record($user['id'], 'privacy', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);

        $_SESSION['flash_success'] = 'Termini accettati. Benvenuto!';
        header('Location: ' . url('/projects'));
        exit;
    }

    /**
     * Get all legal settings
     */
    private function getLegalSettings(): array
    {
        return [
            'company_name' => Settings::get('legal_company_name', '[RAGIONE SOCIALE]'),
            'vat_number' => Settings::get('legal_vat_number', '[P.IVA]'),
            'address' => Settings::get('legal_address', '[SEDE LEGALE]'),
            'pec' => Settings::get('legal_pec', '[PEC]'),
            'email' => Settings::get('legal_email', '[EMAIL PRIVACY]'),
            'dpo_email' => Settings::get('legal_dpo_email', ''),
            'version' => Settings::get('legal_version', '2026.1'),
            'last_updated' => Settings::get('legal_last_updated', date('Y-m-d')),
        ];
    }
}
```

### Step 2: Create Privacy Policy view

Create `shared/views/legal/privacy.php` — full Italian privacy policy with dynamic placeholders `$legal['company_name']`, etc.

Sezioni obbligatorie (Art. 13-14 GDPR):
1. Titolare del trattamento (da `$legal`)
2. Dati raccolti (nome, email, password hash, IP, user agent)
3. Dati forniti volontariamente (URL, keyword, contenuti SEO)
4. Finalita e base giuridica (contratto Art. 6.1.b, consenso Art. 6.1.a, obbligo legale Art. 6.1.c, legittimo interesse Art. 6.1.f)
5. Sub-responsabili (Anthropic/Claude, OpenAI, Google, Stripe, DataForSEO, RapidAPI, SERP API) con sede e garanzie
6. Trasferimento dati extra-UE (SCC)
7. Periodo di conservazione per categoria
8. Diritti dell'interessato (accesso Art. 15, rettifica Art. 16, cancellazione Art. 17, limitazione Art. 18, portabilita Art. 20, opposizione Art. 21, reclamo Garante)
9. Cookie (rimando a cookie policy)
10. Sicurezza (HTTPS, bcrypt, prepared statements, CSRF, role-based access)
11. Modifiche alla policy (notifica + versioning)

**Stile**: layout pulito con `prose` Tailwind, sezioni con `<h2>` numerate, testo in italiano chiaro. Layout con sidebar se utente loggato, senza se pubblico. Usare lo stesso pattern di `shared/views/docs/` per pagine documentazione.

### Step 3: Create Terms of Service view

Create `shared/views/legal/terms.php` — termini di servizio completi in italiano.

Sezioni:
1. Definizioni
2. Oggetto del servizio
3. Registrazione e account
4. Sistema crediti (acquisto Stripe, consumo, non rimborsabilita)
5. Uso accettabile (divieti)
6. Proprieta intellettuale (contenuti AI)
7. Limitazione responsabilita
8. SLA e disponibilita
9. Recesso e cancellazione account
10. Modifiche ai termini
11. Legge applicabile (italiana)
12. Foro competente
13. Contatti

### Step 4: Create Cookie Policy view

Create `shared/views/legal/cookies.php` — cookie policy semplificata (solo cookie tecnici).

Sezioni:
1. Cosa sono i cookie
2. Cookie utilizzati (PHPSESSID, remember_token, cookie_notice_seen — tutti tecnici)
3. Nessun cookie di profilazione (dichiarazione esplicita)
4. Base giuridica (Art. 122 D.Lgs. 196/2003)
5. Come gestire i cookie nei browser
6. Contatti

### Step 5: Create re-acceptance page

Create `shared/views/legal/accept.php` — pagina mostrata quando `legal_version` cambia.

Contenuto:
- Titolo: "Abbiamo aggiornato i nostri Termini"
- Messaggio: spiegazione che i termini sono stati aggiornati
- Link alle pagine complete (privacy + terms)
- Checkbox: "Ho letto e accetto i nuovi Termini di Servizio e la Privacy Policy"
- 3 bottoni: "Accetta e continua", "Scarica i miei dati" (link a profilo), "Elimina il mio account" (link a profilo)
- CSRF token

### Step 6: Add routes in public/index.php

Modify `public/index.php` — aggiungere le routes legali e i redirect legacy.

Inserire **prima** delle route dei moduli (vicino alle route docs, ~linea 461):

```php
// Legal pages (public)
Router::get('/legal/privacy', fn() => (new \Controllers\LegalController())->privacy());
Router::get('/legal/terms', fn() => (new \Controllers\LegalController())->terms());
Router::get('/legal/cookies', fn() => (new \Controllers\LegalController())->cookies());

// Legal re-acceptance (auth)
Router::get('/legal/accept', fn() => (new \Controllers\LegalController())->showAcceptForm());
Router::post('/legal/accept', fn() => (new \Controllers\LegalController())->acceptUpdated());

// Legacy redirects
Router::get('/docs/privacy', function() { header('Location: ' . url('/legal/privacy'), true, 301); exit; });
Router::get('/docs/terms', function() { header('Location: ' . url('/legal/terms'), true, 301); exit; });
Router::get('/docs/cookies', function() { header('Location: ' . url('/legal/cookies'), true, 301); exit; });
Router::get('/privacy', function() { header('Location: ' . url('/legal/privacy'), true, 301); exit; });
Router::get('/terms', function() { header('Location: ' . url('/legal/terms'), true, 301); exit; });
```

### Step 7: Update footer links

Modify `public/includes/site-footer.php:30-35` — cambiare URL da `/docs/privacy` a `/legal/privacy`, etc.

### Step 8: Verify syntax on all new files

```bash
php -l controllers/LegalController.php
php -l shared/views/legal/privacy.php
php -l shared/views/legal/terms.php
php -l shared/views/legal/cookies.php
php -l shared/views/legal/accept.php
php -l public/index.php
```

### Step 9: Commit

```bash
git add controllers/LegalController.php shared/views/legal/ public/index.php public/includes/site-footer.php
git commit -m "feat(gdpr): add legal pages (privacy, terms, cookies) with controller and routes"
```

---

## Task 4: Cookie banner

**Files:**
- Create: `shared/views/components/cookie-banner.php`
- Modify: `shared/views/layout.php:~99` (include banner)

### Step 1: Create cookie banner component

Create `shared/views/components/cookie-banner.php` — Alpine.js component, informativo (solo cookie tecnici).

Comportamento:
- Appare in basso a sinistra/centro se `cookie_notice_seen` cookie non esiste
- Testo: "Questo sito utilizza solo cookie tecnici necessari al funzionamento del servizio. Non utilizziamo cookie di profilazione."
- Link "Cookie Policy" a `/legal/cookies`
- Bottone "Ho capito" → setta cookie `cookie_notice_seen=1` (expires 6 mesi) e nasconde banner
- Stile: `fixed bottom-0`, sfondo `bg-slate-800`, testo bianco, `z-50`, `rounded-t-xl` o pill style
- Alpine.js `x-data="cookieBanner()"` con metodo `accept()` che setta cookie e `show = false`
- `x-show="show"` con transizione fade
- `x-init` controlla se cookie esiste gia

### Step 2: Include banner in layout.php

Modify `shared/views/layout.php` — inserire il partial **prima del closing `</body>`** (prima di line ~474), cosi appare su tutte le pagine:

```php
<?php // Cookie banner (informativo, solo cookie tecnici) ?>
<?= \Core\View::partial('components/cookie-banner') ?>
```

Posizionarlo FUORI dal blocco `if (isset($user) && $user)` cosi appare anche per utenti non loggati.

### Step 3: Verify syntax

```bash
php -l shared/views/components/cookie-banner.php
php -l shared/views/layout.php
```

### Step 4: Commit

```bash
git add shared/views/components/cookie-banner.php shared/views/layout.php
git commit -m "feat(gdpr): add informative cookie banner (technical cookies only)"
```

---

## Task 5: Registration consent tracking

**Files:**
- Modify: `shared/views/auth/register.php:70-77` (update checkbox text + links)
- Modify: `public/index.php:253-314` (validate checkbox + record consent)

### Step 1: Update registration form checkbox

Modify `shared/views/auth/register.php:70-77` — aggiornare testo e link del checkbox:

```html
<div class="flex items-start">
    <input id="terms" name="terms" type="checkbox" required
           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 mt-0.5">
    <label for="terms" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
        Ho letto e accetto la
        <a href="<?= url('/legal/privacy') ?>" target="_blank" class="text-blue-600 hover:text-blue-500 underline">Privacy Policy</a>
        e i
        <a href="<?= url('/legal/terms') ?>" target="_blank" class="text-blue-600 hover:text-blue-500 underline">Termini di Servizio</a>
        <span class="text-red-500">*</span>
    </label>
</div>
```

### Step 2: Add backend validation and consent recording

Modify `public/index.php` nel POST `/register` handler (~linea 263-314):

Dopo la creazione dell'utente (dopo l'INSERT in `users` che ritorna l'id), aggiungere:

```php
// Record GDPR consent
\Services\ConsentService::recordRegistration(
    $userId,
    $_SERVER['REMOTE_ADDR'] ?? null,
    $_SERVER['HTTP_USER_AGENT'] ?? null
);
```

Aggiungere anche validazione backend che `$_POST['terms']` sia presente (prima della creazione utente):

```php
if (empty($_POST['terms'])) {
    $errors[] = 'Devi accettare la Privacy Policy e i Termini di Servizio.';
}
```

### Step 3: Verify syntax

```bash
php -l shared/views/auth/register.php
php -l public/index.php
```

### Step 4: Commit

```bash
git add shared/views/auth/register.php public/index.php
git commit -m "feat(gdpr): track consent on registration with backend validation"
```

---

## Task 6: Legal version check middleware

**Files:**
- Modify: `core/Middleware.php` (add legalVersionCheck method)
- Modify: `public/index.php` (add middleware call after auth on protected routes)

### Step 1: Add middleware method

Modify `core/Middleware.php` — aggiungere metodo `legalVersionCheck()` dopo il metodo `auth()`:

```php
/**
 * Check if user has accepted current legal version.
 * Redirects to /legal/accept if not.
 * Skip for legal routes, profile, and API endpoints.
 */
public static function legalVersionCheck(): void
{
    $user = \Core\Auth::user();
    if (!$user) return;

    // Skip for legal pages, profile (needs access for data export/deletion), and API
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $skipPrefixes = ['/legal/', '/profile', '/logout', '/api/', '/email/preferences'];
    foreach ($skipPrefixes as $prefix) {
        if (strpos($path, $prefix) !== false) return;
    }

    if (\Services\ConsentService::needsReacceptance($user)) {
        header('Location: ' . url('/legal/accept'));
        exit;
    }
}
```

### Step 2: Hook into auth middleware

Il check va chiamato dopo `auth()` nelle route protette. L'approccio piu pulito: aggiungere la chiamata dentro `Middleware::auth()` dopo la verifica che l'utente e loggato, oppure come chiamata separata nelle route.

Approccio raccomandato: aggiungere alla fine di `Middleware::auth()` (dopo il check di autenticazione passa):

```php
// In Middleware::auth(), dopo il check Auth::check():
static::legalVersionCheck();
```

Cosi ogni route protetta con `Middleware::auth()` controlla automaticamente.

### Step 3: Verify syntax

```bash
php -l core/Middleware.php
```

### Step 4: Commit

```bash
git add core/Middleware.php
git commit -m "feat(gdpr): add legal version check middleware for re-acceptance flow"
```

---

## Task 7: AccountDeletionService

**Files:**
- Create: `services/AccountDeletionService.php`

### Step 1: Create the service

Questo e il servizio piu complesso — gestisce il cascading delete su tutti i moduli.

```php
<?php

namespace Services;

use Core\Database;
use Core\Auth;

class AccountDeletionService
{
    /**
     * Delete user account and all associated data.
     * Returns summary of deleted data.
     */
    public static function deleteAccount(int $userId, ?string $reason = null, string $deletedBy = 'user'): array
    {
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            throw new \RuntimeException("Utente non trovato");
        }

        $summary = [];

        // Get all user's global projects (as owner)
        $ownedProjects = Database::fetchAll(
            "SELECT id FROM projects WHERE user_id = ?", [$userId]
        );
        $ownedProjectIds = array_column($ownedProjects, 'id');

        // Get module project IDs for each module
        $moduleProjectIds = [];
        $modulePrefixes = [
            'aic' => 'aic_projects',
            'sa' => 'sa_projects',
            'st' => 'st_projects',
            'kr' => 'kr_projects',
            'ga' => 'ga_projects',
            'cc' => 'cc_projects',
            'il' => 'il_projects',
            'ao' => 'ao_projects',
            'so' => 'so_projects',
        ];

        foreach ($modulePrefixes as $prefix => $table) {
            $exists = Database::fetchOne("SHOW TABLES LIKE '$table'");
            if ($exists) {
                if (!empty($ownedProjectIds)) {
                    $placeholders = implode(',', array_fill(0, count($ownedProjectIds), '?'));
                    $moduleProjectIds[$prefix] = Database::fetchAll(
                        "SELECT id FROM $table WHERE global_project_id IN ($placeholders)",
                        $ownedProjectIds
                    );
                    $moduleProjectIds[$prefix] = array_column($moduleProjectIds[$prefix], 'id');
                } else {
                    // Check for projects by user_id directly
                    $moduleProjectIds[$prefix] = Database::fetchAll(
                        "SELECT id FROM $table WHERE user_id = ?",
                        [$userId]
                    );
                    $moduleProjectIds[$prefix] = array_column($moduleProjectIds[$prefix], 'id');
                }
            }
        }

        Database::beginTransaction();

        try {
            // 1. Delete module data per project
            $summary = array_merge($summary, self::deleteModuleData($moduleProjectIds, $userId));

            // 2. Delete project memberships (where user is member, not owner)
            $summary['project_members'] = self::deleteRows("DELETE FROM project_members WHERE user_id = ?", [$userId]);

            // 3. Delete project invitations
            $summary['project_invitations'] = self::deleteRows(
                "DELETE FROM project_invitations WHERE invited_by = ? OR email = ?",
                [$userId, $user['email']]
            );

            // 4. Delete module projects
            foreach ($modulePrefixes as $prefix => $table) {
                $exists = Database::fetchOne("SHOW TABLES LIKE '$table'");
                if ($exists && !empty($moduleProjectIds[$prefix])) {
                    $placeholders = implode(',', array_fill(0, count($moduleProjectIds[$prefix]), '?'));
                    $summary[$table] = self::deleteRows(
                        "DELETE FROM $table WHERE id IN ($placeholders)",
                        $moduleProjectIds[$prefix]
                    );
                }
            }

            // 5. Delete global projects
            if (!empty($ownedProjectIds)) {
                $placeholders = implode(',', array_fill(0, count($ownedProjectIds), '?'));

                // Delete project_member_modules first
                $summary['project_member_modules'] = self::deleteRows(
                    "DELETE pmm FROM project_member_modules pmm
                     INNER JOIN project_members pm ON pm.id = pmm.project_member_id
                     WHERE pm.project_id IN ($placeholders)",
                    $ownedProjectIds
                );

                // Delete project_members for owned projects
                $summary['project_members_owned'] = self::deleteRows(
                    "DELETE FROM project_members WHERE project_id IN ($placeholders)",
                    $ownedProjectIds
                );

                $summary['projects'] = self::deleteRows(
                    "DELETE FROM projects WHERE id IN ($placeholders)",
                    $ownedProjectIds
                );
            }

            // 6. Delete notifications
            $summary['notifications'] = self::deleteRows(
                "DELETE FROM notifications WHERE user_id = ?", [$userId]
            );
            $summary['notification_preferences'] = self::deleteRows(
                "DELETE FROM notification_preferences WHERE user_id = ?", [$userId]
            );

            // 7. Delete data export requests + files
            $exports = Database::fetchAll(
                "SELECT file_path FROM data_export_requests WHERE user_id = ?", [$userId]
            );
            foreach ($exports as $export) {
                if (!empty($export['file_path']) && file_exists($export['file_path'])) {
                    unlink($export['file_path']);
                }
            }
            $summary['data_export_requests'] = self::deleteRows(
                "DELETE FROM data_export_requests WHERE user_id = ?", [$userId]
            );

            // 8. Anonymize financial/audit records (legal obligation)
            $summary['credit_transactions_anonymized'] = self::anonymizeRows(
                'credit_transactions', $userId
            );
            $summary['ai_logs_anonymized'] = self::anonymizeRows('ai_logs', $userId);
            $summary['api_logs_anonymized'] = self::anonymizeRows('api_logs', $userId);

            // 9. Delete auth-related
            $summary['password_resets'] = self::deleteRows(
                "DELETE FROM password_resets WHERE email = ?", [$user['email']]
            );
            $summary['email_unsubscribe_tokens'] = self::deleteRows(
                "DELETE FROM email_unsubscribe_tokens WHERE user_id = ?", [$userId]
            );

            // 10. Delete user (last!)
            Database::execute("DELETE FROM users WHERE id = ?", [$userId]);
            $summary['user'] = 1;

            // Log deletion (consent_records NOT deleted — legal requirement)
            Database::insert('account_deletion_logs', [
                'user_id' => $userId,
                'email' => $user['email'],
                'reason' => $reason,
                'deleted_data_summary' => json_encode($summary),
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $deletedBy
            ]);

            Database::commit();

            return $summary;

        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Delete module-specific data tables.
     * Each module prefix has known child tables.
     */
    private static function deleteModuleData(array $moduleProjectIds, int $userId): array
    {
        $summary = [];

        // AI Content (aic_)
        if (!empty($moduleProjectIds['aic'])) {
            $ids = $moduleProjectIds['aic'];
            $ph = implode(',', array_fill(0, count($ids), '?'));
            foreach (['aic_queue', 'aic_generated_content', 'aic_briefs'] as $table) {
                $exists = Database::fetchOne("SHOW TABLES LIKE '$table'");
                if ($exists) {
                    $summary[$table] = self::deleteRows("DELETE FROM $table WHERE project_id IN ($ph)", $ids);
                }
            }
        }

        // SEO Audit (sa_)
        if (!empty($moduleProjectIds['sa'])) {
            $ids = $moduleProjectIds['sa'];
            $ph = implode(',', array_fill(0, count($ids), '?'));
            foreach (['sa_pages', 'sa_sessions', 'sa_site_config'] as $table) {
                $exists = Database::fetchOne("SHOW TABLES LIKE '$table'");
                if ($exists) {
                    $summary[$table] = self::deleteRows("DELETE FROM $table WHERE project_id IN ($ph)", $ids);
                }
            }
        }

        // SEO Tracking (st_)
        if (!empty($moduleProjectIds['st'])) {
            $ids = $moduleProjectIds['st'];
            $ph = implode(',', array_fill(0, count($ids), '?'));
            foreach (['st_keywords', 'st_keyword_positions', 'st_keyword_volumes', 'st_gsc_data', 'st_gsc_pages', 'st_rank_checks', 'st_ai_reports'] as $table) {
                $exists = Database::fetchOne("SHOW TABLES LIKE '$table'");
                if ($exists) {
                    $summary[$table] = self::deleteRows("DELETE FROM $table WHERE project_id IN ($ph)", $ids);
                }
            }
        }

        // Keyword Research (kr_)
        if (!empty($moduleProjectIds['kr'])) {
            $ids = $moduleProjectIds['kr'];
            $ph = implode(',', array_fill(0, count($ids), '?'));
            foreach (['kr_collections', 'kr_keywords', 'kr_editorial_items', 'kr_serp_cache'] as $table) {
                $exists = Database::fetchOne("SHOW TABLES LIKE '$table'");
                if ($exists) {
                    $summary[$table] = self::deleteRows("DELETE FROM $table WHERE project_id IN ($ph)", $ids);
                }
            }
        }

        // Google Ads Analyzer (ga_)
        if (!empty($moduleProjectIds['ga'])) {
            $ids = $moduleProjectIds['ga'];
            $ph = implode(',', array_fill(0, count($ids), '?'));
            foreach (['ga_evaluations', 'ga_campaigns', 'ga_runs'] as $table) {
                $exists = Database::fetchOne("SHOW TABLES LIKE '$table'");
                if ($exists) {
                    $summary[$table] = self::deleteRows("DELETE FROM $table WHERE project_id IN ($ph)", $ids);
                }
            }
        }

        // Content Creator (cc_)
        if (!empty($moduleProjectIds['cc'])) {
            $ids = $moduleProjectIds['cc'];
            $ph = implode(',', array_fill(0, count($ids), '?'));
            foreach (['cc_contents', 'cc_publications'] as $table) {
                $exists = Database::fetchOne("SHOW TABLES LIKE '$table'");
                if ($exists) {
                    $summary[$table] = self::deleteRows("DELETE FROM $table WHERE project_id IN ($ph)", $ids);
                }
            }
        }

        // Internal Links (il_)
        if (!empty($moduleProjectIds['il'])) {
            $ids = $moduleProjectIds['il'];
            $ph = implode(',', array_fill(0, count($ids), '?'));
            foreach (['il_pages', 'il_links', 'il_sessions'] as $table) {
                $exists = Database::fetchOne("SHOW TABLES LIKE '$table'");
                if ($exists) {
                    $summary[$table] = self::deleteRows("DELETE FROM $table WHERE project_id IN ($ph)", $ids);
                }
            }
        }

        return $summary;
    }

    /**
     * Execute DELETE and return affected rows count
     */
    private static function deleteRows(string $sql, array $params = []): int
    {
        return Database::execute($sql, $params);
    }

    /**
     * Anonymize rows by setting user_id to NULL
     */
    private static function anonymizeRows(string $table, int $userId): int
    {
        $exists = Database::fetchOne("SHOW TABLES LIKE '$table'");
        if (!$exists) return 0;

        // Check if table has user_id column
        $columns = Database::fetchAll("SHOW COLUMNS FROM $table LIKE 'user_id'");
        if (empty($columns)) return 0;

        return Database::execute(
            "UPDATE $table SET user_id = NULL WHERE user_id = ?",
            [$userId]
        );
    }
}
```

**Note per l'implementatore**: Le tabelle figlio di ogni modulo potrebbero variare. Il service usa `SHOW TABLES LIKE` per verificare l'esistenza prima di cancellare, cosi e resiliente a moduli non ancora installati. Controllare le tabelle reali del DB locale con `SHOW TABLES LIKE 'aic_%'` etc. e aggiornare gli array di tabelle se necessario.

### Step 2: Verify syntax

```bash
php -l services/AccountDeletionService.php
```

### Step 3: Commit

```bash
git add services/AccountDeletionService.php
git commit -m "feat(gdpr): add AccountDeletionService with cascading delete across all modules"
```

---

## Task 8: DataExportService

**Files:**
- Create: `services/DataExportService.php`

### Step 1: Create the service

```php
<?php

namespace Services;

use Core\Database;

class DataExportService
{
    private const EXPORT_DIR = __DIR__ . '/../storage/exports';
    private const EXPIRE_HOURS = 48;

    /**
     * Generate a JSON export of all user data
     */
    public static function generateExport(int $userId): array
    {
        $user = Database::fetchOne("SELECT id, name, email, role, plan, created_at FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            throw new \RuntimeException("Utente non trovato");
        }

        // Create export request record
        $requestId = Database::insert('data_export_requests', [
            'user_id' => $userId,
            'status' => 'processing',
            'requested_at' => date('Y-m-d H:i:s'),
        ]);

        try {
            $data = [
                'export_info' => [
                    'platform' => 'Ainstein SEO Toolkit',
                    'exported_at' => date('c'),
                    'format_version' => '1.0',
                    'user_id' => $userId,
                ],
                'profile' => $user,
                'consent_records' => self::getConsents($userId),
                'notification_preferences' => self::getNotificationPrefs($userId),
                'projects' => self::getProjects($userId),
                'credit_history' => self::getCreditHistory($userId),
            ];

            // Ensure export directory exists
            $userDir = self::EXPORT_DIR . '/' . $userId;
            if (!is_dir($userDir)) {
                mkdir($userDir, 0755, true);
            }

            $filename = 'ainstein-export-' . date('Y-m-d-His') . '.json';
            $filePath = $userDir . '/' . $filename;

            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::EXPIRE_HOURS . ' hours'));

            Database::update('data_export_requests', [
                'status' => 'completed',
                'file_path' => $filePath,
                'completed_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt,
            ], 'id = ?', [$requestId]);

            return ['request_id' => $requestId, 'file_path' => $filePath, 'expires_at' => $expiresAt];

        } catch (\Exception $e) {
            Database::update('data_export_requests', [
                'status' => 'expired',
                'completed_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$requestId]);
            throw $e;
        }
    }

    /**
     * Check if user can request a new export (rate limit: 1 per 24h)
     */
    public static function canRequestExport(int $userId): bool
    {
        $recent = Database::fetchOne(
            "SELECT id FROM data_export_requests
             WHERE user_id = ? AND requested_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             AND status IN ('pending', 'processing', 'completed')",
            [$userId]
        );
        return !$recent;
    }

    /**
     * Get export file for download (validates ownership + expiry)
     */
    public static function getExportForDownload(int $requestId, int $userId): ?array
    {
        $export = Database::fetchOne(
            "SELECT * FROM data_export_requests WHERE id = ? AND user_id = ? AND status = 'completed'",
            [$requestId, $userId]
        );

        if (!$export) return null;
        if (strtotime($export['expires_at']) < time()) return null;
        if (!file_exists($export['file_path'])) return null;

        return $export;
    }

    /**
     * Cleanup expired export files (called from cron)
     */
    public static function cleanupExpired(): int
    {
        $expired = Database::fetchAll(
            "SELECT id, file_path FROM data_export_requests WHERE status = 'completed' AND expires_at < NOW()"
        );

        $count = 0;
        foreach ($expired as $export) {
            if (!empty($export['file_path']) && file_exists($export['file_path'])) {
                unlink($export['file_path']);
            }
            Database::update('data_export_requests', ['status' => 'expired'], 'id = ?', [$export['id']]);
            $count++;
        }

        return $count;
    }

    // --- Private data collection methods ---

    private static function getConsents(int $userId): array
    {
        return Database::fetchAll(
            "SELECT consent_type, version, accepted_at, revoked_at FROM consent_records WHERE user_id = ? ORDER BY accepted_at",
            [$userId]
        );
    }

    private static function getNotificationPrefs(int $userId): array
    {
        return Database::fetchAll(
            "SELECT * FROM notification_preferences WHERE user_id = ?",
            [$userId]
        );
    }

    private static function getProjects(int $userId): array
    {
        $projects = Database::fetchAll(
            "SELECT * FROM projects WHERE user_id = ?",
            [$userId]
        );

        foreach ($projects as &$project) {
            $project['modules'] = [];

            // Collect module data for each project
            $moduleTables = [
                'ai-content' => 'aic_projects',
                'seo-audit' => 'sa_projects',
                'seo-tracking' => 'st_projects',
                'keyword-research' => 'kr_projects',
                'ads-analyzer' => 'ga_projects',
                'content-creator' => 'cc_projects',
                'internal-links' => 'il_projects',
            ];

            foreach ($moduleTables as $moduleName => $table) {
                $exists = Database::fetchOne("SHOW TABLES LIKE '$table'");
                if (!$exists) continue;

                $moduleProject = Database::fetchOne(
                    "SELECT * FROM $table WHERE global_project_id = ?",
                    [$project['id']]
                );

                if ($moduleProject) {
                    $project['modules'][$moduleName] = [
                        'project' => $moduleProject,
                        'data_summary' => self::getModuleDataSummary($moduleName, $moduleProject['id'])
                    ];
                }
            }
        }

        return $projects;
    }

    private static function getModuleDataSummary(string $module, int $projectId): array
    {
        $summary = [];

        $dataQueries = [
            'ai-content' => [
                'briefs' => "SELECT COUNT(*) as cnt FROM aic_briefs WHERE project_id = ?",
                'generated_content' => "SELECT COUNT(*) as cnt FROM aic_generated_content WHERE project_id = ?",
                'queue' => "SELECT COUNT(*) as cnt FROM aic_queue WHERE project_id = ?",
            ],
            'seo-audit' => [
                'sessions' => "SELECT COUNT(*) as cnt FROM sa_sessions WHERE project_id = ?",
                'pages' => "SELECT COUNT(*) as cnt FROM sa_pages WHERE project_id = ?",
            ],
            'seo-tracking' => [
                'keywords' => "SELECT COUNT(*) as cnt FROM st_keywords WHERE project_id = ?",
            ],
            'keyword-research' => [
                'collections' => "SELECT COUNT(*) as cnt FROM kr_collections WHERE project_id = ?",
            ],
            'ads-analyzer' => [
                'evaluations' => "SELECT COUNT(*) as cnt FROM ga_evaluations WHERE project_id = ?",
            ],
            'content-creator' => [
                'contents' => "SELECT COUNT(*) as cnt FROM cc_contents WHERE project_id = ?",
            ],
            'internal-links' => [
                'sessions' => "SELECT COUNT(*) as cnt FROM il_sessions WHERE project_id = ?",
            ],
        ];

        if (isset($dataQueries[$module])) {
            foreach ($dataQueries[$module] as $key => $query) {
                try {
                    $result = Database::fetchOne($query, [$projectId]);
                    $summary[$key . '_count'] = $result['cnt'] ?? 0;
                } catch (\Exception $e) {
                    $summary[$key . '_count'] = 'N/A';
                }
            }
        }

        return $summary;
    }

    private static function getCreditHistory(int $userId): array
    {
        return Database::fetchAll(
            "SELECT amount, type, description, created_at FROM credit_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 500",
            [$userId]
        );
    }
}
```

### Step 2: Create storage/exports directory

```bash
mkdir -p storage/exports
echo "*\n!.gitkeep" > storage/exports/.gitignore
```

### Step 3: Verify syntax

```bash
php -l services/DataExportService.php
```

### Step 4: Commit

```bash
git add services/DataExportService.php storage/exports/.gitignore
git commit -m "feat(gdpr): add DataExportService for user data portability (Art. 20)"
```

---

## Task 9: Profile GDPR section + account deletion/export routes

**Files:**
- Create: `shared/views/profile/privacy-section.php`
- Modify: `shared/views/profile.php:~129` (include privacy section)
- Modify: `public/index.php` (add POST /profile/delete-account, POST /profile/export-data, GET /profile/download-data/{id})

### Step 1: Create privacy section partial

Create `shared/views/profile/privacy-section.php` — sezione "Privacy e Dati Personali" con:

- **Consensi attivi**: mostra versione accettata, data accettazione per privacy e terms
- **Scarica i tuoi dati**: bottone che POSTa a `/profile/export-data`, mostra stato export se pendente/completato con link download
- **Elimina il mio account**: bottone rosso che apre modal Alpine.js con:
  - Avviso chiaro su cosa viene eliminato (tutti i progetti, dati, contenuti)
  - Avviso che l'azione e irreversibile
  - Campo motivo (textarea, opzionale)
  - Input email (deve corrispondere all'email dell'utente)
  - Input password
  - Checkbox conferma "Confermo di voler eliminare permanentemente il mio account"
  - Bottone "Elimina definitivamente" (rosso)

Dati passati dal controller: `$consents`, `$latestExport`, `$user`.

### Step 2: Include in profile.php

Modify `shared/views/profile.php` — dopo la sezione notifiche email (~linea 129), aggiungere:

```php
<?= \Core\View::partial('profile/privacy-section', [
    'user' => $user,
    'consents' => $consents ?? [],
    'latestExport' => $latestExport ?? null,
    'legalVersion' => $legalVersion ?? '',
]) ?>
```

### Step 3: Update profile controller to pass GDPR data

Trovare il controller o la route che renderizza il profilo e aggiungere le variabili necessarie:

```php
$consents = \Services\ConsentService::getUserConsents($user['id']);
$latestExport = Database::fetchOne(
    "SELECT * FROM data_export_requests WHERE user_id = ? AND status IN ('processing', 'completed') ORDER BY requested_at DESC LIMIT 1",
    [$user['id']]
);
$legalVersion = Settings::get('legal_version', '2026.1');
```

### Step 4: Add routes for delete and export

Modify `public/index.php` — aggiungere route:

```php
// Profile: export data
Router::post('/profile/export-data', function() {
    \Core\Middleware::auth();
    \Core\Middleware::csrf();
    $user = \Core\Auth::user();

    if (!\Services\DataExportService::canRequestExport($user['id'])) {
        $_SESSION['flash_error'] = 'Puoi richiedere un export ogni 24 ore.';
        header('Location: ' . url('/profile'));
        exit;
    }

    try {
        $result = \Services\DataExportService::generateExport($user['id']);
        $_SESSION['flash_success'] = 'Export completato! Puoi scaricarlo dalla sezione Privacy.';
    } catch (\Exception $e) {
        $_SESSION['flash_error'] = 'Errore durante l\'export: ' . $e->getMessage();
    }

    header('Location: ' . url('/profile'));
    exit;
});

// Profile: download export
Router::get('/profile/download-data/{id}', function($id) {
    \Core\Middleware::auth();
    $user = \Core\Auth::user();

    $export = \Services\DataExportService::getExportForDownload((int)$id, $user['id']);
    if (!$export) {
        $_SESSION['flash_error'] = 'Export non trovato o scaduto.';
        header('Location: ' . url('/profile'));
        exit;
    }

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="ainstein-dati-personali.json"');
    header('Content-Length: ' . filesize($export['file_path']));
    readfile($export['file_path']);
    exit;
});

// Profile: delete account
Router::post('/profile/delete-account', function() {
    \Core\Middleware::auth();
    \Core\Middleware::csrf();
    $user = \Core\Auth::user();

    // Validate email confirmation
    $confirmEmail = trim($_POST['confirm_email'] ?? '');
    if (strtolower($confirmEmail) !== strtolower($user['email'])) {
        $_SESSION['flash_error'] = 'L\'email inserita non corrisponde.';
        header('Location: ' . url('/profile'));
        exit;
    }

    // Validate password
    $password = $_POST['password'] ?? '';
    if (!password_verify($password, $user['password'])) {
        $_SESSION['flash_error'] = 'Password non corretta.';
        header('Location: ' . url('/profile'));
        exit;
    }

    // Validate confirmation checkbox
    if (empty($_POST['confirm_delete'])) {
        $_SESSION['flash_error'] = 'Devi confermare la cancellazione.';
        header('Location: ' . url('/profile'));
        exit;
    }

    $reason = trim($_POST['reason'] ?? '');

    try {
        \Services\AccountDeletionService::deleteAccount($user['id'], $reason ?: null);

        // Logout
        \Core\Auth::logout();

        $_SESSION['flash_success'] = 'Il tuo account e tutti i dati associati sono stati eliminati permanentemente.';
        header('Location: ' . url('/login'));
        exit;
    } catch (\Exception $e) {
        $_SESSION['flash_error'] = 'Errore durante la cancellazione: ' . $e->getMessage();
        header('Location: ' . url('/profile'));
        exit;
    }
});
```

### Step 5: Verify syntax

```bash
php -l shared/views/profile/privacy-section.php
php -l shared/views/profile.php
php -l public/index.php
```

### Step 6: Commit

```bash
git add shared/views/profile/privacy-section.php shared/views/profile.php public/index.php
git commit -m "feat(gdpr): add account deletion, data export, and privacy section to profile"
```

---

## Task 10: Admin GDPR Panel

**Files:**
- Create: `admin/controllers/AdminGdprController.php`
- Create: `shared/views/admin/gdpr/index.php` (settings tab)
- Create: `shared/views/admin/gdpr/consents.php`
- Create: `shared/views/admin/gdpr/deletions.php`
- Create: `shared/views/admin/gdpr/exports.php`
- Modify: `public/index.php` (add admin GDPR routes)
- Modify: `shared/views/components/nav-items.php:~772` (add GDPR nav item)

### Step 1: Create AdminGdprController

```php
<?php

namespace Admin\Controllers;

use Core\Auth;
use Core\Database;
use Core\Middleware;
use Core\Pagination;
use Core\Settings;
use Core\View;
use Services\ConsentService;

class AdminGdprController
{
    public function __construct()
    {
        Middleware::admin();
    }

    /**
     * GDPR settings page (default tab)
     */
    public function index(): string
    {
        $user = Auth::user();

        $settings = [
            'legal_company_name' => Settings::get('legal_company_name', ''),
            'legal_vat_number' => Settings::get('legal_vat_number', ''),
            'legal_address' => Settings::get('legal_address', ''),
            'legal_pec' => Settings::get('legal_pec', ''),
            'legal_email' => Settings::get('legal_email', ''),
            'legal_dpo_email' => Settings::get('legal_dpo_email', ''),
            'legal_version' => Settings::get('legal_version', '2026.1'),
            'legal_last_updated' => Settings::get('legal_last_updated', ''),
        ];

        return View::render('admin/gdpr/index', [
            'title' => 'GDPR & Privacy',
            'user' => $user,
            'settings' => $settings,
            'modules' => \Core\ModuleLoader::getActiveModules(),
            'activeTab' => 'settings',
        ]);
    }

    /**
     * Save legal settings
     */
    public function saveSettings(): void
    {
        Middleware::csrf();

        $allowedKeys = [
            'legal_company_name', 'legal_vat_number', 'legal_address',
            'legal_pec', 'legal_email', 'legal_dpo_email',
            'legal_version', 'legal_last_updated'
        ];

        foreach ($allowedKeys as $key) {
            if (isset($_POST[$key])) {
                Settings::set($key, trim($_POST[$key]));
            }
        }

        $_SESSION['flash_success'] = 'Impostazioni legali salvate.';
        header('Location: ' . url('/admin/gdpr'));
        exit;
    }

    /**
     * Consent records list
     */
    public function consents(): string
    {
        $user = Auth::user();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'consent_type' => $_GET['type'] ?? '',
            'version' => $_GET['version'] ?? '',
        ];

        $result = ConsentService::getAllConsents($page, 50, $filters);
        $pagination = Pagination::make($result['total'], $page, 50);

        return View::render('admin/gdpr/consents', [
            'title' => 'GDPR - Registro Consensi',
            'user' => $user,
            'records' => $result['records'],
            'pagination' => $pagination,
            'filters' => $filters,
            'modules' => \Core\ModuleLoader::getActiveModules(),
            'activeTab' => 'consents',
        ]);
    }

    /**
     * Account deletion logs
     */
    public function deletions(): string
    {
        $user = Auth::user();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $total = Database::fetchOne("SELECT COUNT(*) as cnt FROM account_deletion_logs")['cnt'];
        $logs = Database::fetchAll(
            "SELECT * FROM account_deletion_logs ORDER BY deleted_at DESC LIMIT $perPage OFFSET $offset"
        );
        $pagination = Pagination::make($total, $page, $perPage);

        return View::render('admin/gdpr/deletions', [
            'title' => 'GDPR - Log Cancellazioni',
            'user' => $user,
            'logs' => $logs,
            'pagination' => $pagination,
            'modules' => \Core\ModuleLoader::getActiveModules(),
            'activeTab' => 'deletions',
        ]);
    }

    /**
     * Data export requests
     */
    public function exports(): string
    {
        $user = Auth::user();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $total = Database::fetchOne("SELECT COUNT(*) as cnt FROM data_export_requests")['cnt'];
        $exports = Database::fetchAll(
            "SELECT der.*, u.name as user_name, u.email as user_email
             FROM data_export_requests der
             LEFT JOIN users u ON u.id = der.user_id
             ORDER BY der.requested_at DESC
             LIMIT $perPage OFFSET $offset"
        );
        $pagination = Pagination::make($total, $page, $perPage);

        return View::render('admin/gdpr/exports', [
            'title' => 'GDPR - Richieste Export',
            'user' => $user,
            'exports' => $exports,
            'pagination' => $pagination,
            'modules' => \Core\ModuleLoader::getActiveModules(),
            'activeTab' => 'exports',
        ]);
    }
}
```

### Step 2: Create admin views

Create 4 view files in `shared/views/admin/gdpr/`:

**`index.php`** — Tab layout con 4 tab (Impostazioni, Consensi, Cancellazioni, Export). Default: form impostazioni legali con tutti i campi `legal_*`. Bottone "Incrementa versione" che aggiorna `legal_version` e `legal_last_updated`. Stile coerente con `/admin/settings`.

**`consents.php`** — Tabella con filtri tipo/versione. Colonne: Utente, Tipo, Versione, Data accettazione, Revocato. Usa componenti shared (table-pagination, table-empty-state). Stile tabelle standard (rounded-xl, px-4 py-3, dark:bg-slate-700/50).

**`deletions.php`** — Tabella log cancellazioni. Colonne: Email, Data, Motivo, Eliminato da, Riepilogo (expandable JSON). Usa componenti shared.

**`exports.php`** — Tabella richieste export. Colonne: Utente, Data richiesta, Stato (badge), Completato, Scadenza. Usa componenti shared.

Tutte le view usano tab navigation condivisa (link a `/admin/gdpr`, `/admin/gdpr/consents`, `/admin/gdpr/deletions`, `/admin/gdpr/exports`) con highlight tab attiva via `$activeTab`.

### Step 3: Add admin routes

Modify `public/index.php` — aggiungere nella sezione admin routes:

```php
// Admin GDPR
Router::get('/admin/gdpr', fn() => (new \Admin\Controllers\AdminGdprController())->index());
Router::post('/admin/gdpr/settings', fn() => (new \Admin\Controllers\AdminGdprController())->saveSettings());
Router::get('/admin/gdpr/consents', fn() => (new \Admin\Controllers\AdminGdprController())->consents());
Router::get('/admin/gdpr/deletions', fn() => (new \Admin\Controllers\AdminGdprController())->deletions());
Router::get('/admin/gdpr/exports', fn() => (new \Admin\Controllers\AdminGdprController())->exports());
```

### Step 4: Add GDPR nav item in admin sidebar

Modify `shared/views/components/nav-items.php` — dopo la voce "Cache & Log" (~linea 772), aggiungere:

```php
<?= navLink('/admin/gdpr', 'GDPR & Privacy', '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>') ?>
```

Icona: Heroicons `shield-check` (24px outline).

### Step 5: Verify syntax

```bash
php -l admin/controllers/AdminGdprController.php
php -l shared/views/admin/gdpr/index.php
php -l shared/views/admin/gdpr/consents.php
php -l shared/views/admin/gdpr/deletions.php
php -l shared/views/admin/gdpr/exports.php
php -l shared/views/components/nav-items.php
php -l public/index.php
```

### Step 6: Commit

```bash
git add admin/controllers/AdminGdprController.php shared/views/admin/gdpr/ shared/views/components/nav-items.php public/index.php
git commit -m "feat(gdpr): add admin GDPR panel with settings, consents, deletions, exports"
```

---

## Task 11: Cron cleanup for expired exports

**Files:**
- Modify: `cron/cleanup-data.php:~886` (add export cleanup)

### Step 1: Add cleanup function

Modify `cron/cleanup-data.php` — aggiungere funzione e chiamata:

Aggiungere funzione (prima del main execution loop ~linea 886):

```php
/**
 * Cleanup expired data export files
 */
function cleanupExpiredExports($db, $config, $logFile) {
    $deleted = \Services\DataExportService::cleanupExpired();
    if ($deleted > 0) {
        logMessage($logFile, "Cleaned up $deleted expired export files");
    }
    return $deleted;
}
```

Aggiungere chiamata nel main execution loop:

```php
$results['expired_exports'] = cleanupExpiredExports($db, $config, $logFile);
```

**Nota**: verificare che il cron abbia accesso all'autoloader e ai service. Se non usa autoloading, aggiungere il require manuale.

### Step 2: Verify syntax

```bash
php -l cron/cleanup-data.php
```

### Step 3: Commit

```bash
git add cron/cleanup-data.php
git commit -m "feat(gdpr): add expired data exports cleanup to cron"
```

---

## Task 12: Update registration links in auth views

**Files:**
- Modify: `shared/views/auth/login.php` (add legal links if present)
- Modify: `shared/views/auth/forgot-password.php` (add legal links in footer)
- Modify: `shared/views/auth/reset-password.php` (add legal links in footer)

### Step 1: Verify current auth views

Read each auth view to see if they have footer links. If they have links to `/terms` or `/privacy`, update to `/legal/terms` and `/legal/privacy`.

### Step 2: Update links

Update any references from `/terms` to `/legal/terms`, `/privacy` to `/legal/privacy`, `/docs/privacy` to `/legal/privacy`, etc.

### Step 3: Verify syntax

```bash
php -l shared/views/auth/login.php
php -l shared/views/auth/forgot-password.php
php -l shared/views/auth/reset-password.php
```

### Step 4: Commit

```bash
git add shared/views/auth/
git commit -m "fix(gdpr): update legal page links in auth views"
```

---

## Task 13: Final verification and docs update

**Files:**
- Modify: `docs/data-model.html` (add new tables to ER diagram)
- Modify: `CLAUDE.md` (add GDPR section reference if needed)

### Step 1: Test all routes manually

Test in browser at `http://localhost/seo-toolkit`:

1. `/legal/privacy` — pagina privacy visibile (public)
2. `/legal/terms` — pagina termini visibile (public)
3. `/legal/cookies` — pagina cookie visibile (public)
4. `/docs/privacy` — redirect 301 a `/legal/privacy`
5. `/register` — checkbox con link funzionanti, validazione backend
6. Login → `/profile` — sezione Privacy e Dati visibile
7. Profilo → "Scarica i tuoi dati" → file JSON scaricato
8. `/admin/gdpr` — panel admin con 4 tab funzionanti
9. Admin → Impostazioni legali → salva dati titolare → ricarica privacy page → dati visibili
10. Cookie banner appare alla prima visita, scompare dopo "Ho capito"

### Step 2: Update data model docs

Add new tables to `docs/data-model.html` Mermaid ER diagram:
- `consent_records`
- `account_deletion_logs`
- `data_export_requests`

### Step 3: Verify all PHP files

```bash
find . -name "*.php" -newer migrations/2026_02_25_gdpr_compliance.sql -exec php -l {} \;
```

### Step 4: Final commit

```bash
git add docs/data-model.html
git commit -m "docs: update data model with GDPR tables"
```

---

## Summary

| Task | Component | Files |
|------|-----------|-------|
| 1 | Database migration | 1 new |
| 2 | ConsentService | 1 new |
| 3 | Legal pages + controller + routes | 6 new, 2 modified |
| 4 | Cookie banner | 1 new, 1 modified |
| 5 | Registration consent tracking | 2 modified |
| 6 | Legal version check middleware | 1 modified |
| 7 | AccountDeletionService | 1 new |
| 8 | DataExportService | 1 new |
| 9 | Profile GDPR section + routes | 1 new, 2 modified |
| 10 | Admin GDPR panel | 5 new, 2 modified |
| 11 | Cron export cleanup | 1 modified |
| 12 | Auth view link updates | 3 modified |
| 13 | Verification + docs | 1 modified |

**Totale**: ~17 nuovi file, ~14 file modificati, 13 commit.
