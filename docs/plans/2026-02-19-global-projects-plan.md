# Progetti Globali - Piano di Implementazione

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Creare un sistema di progetti globali cross-modulo che fa da hub per i progetti dei singoli moduli, con dashboard KPI aggregata stile Semrush.

**Architecture:** Tabella `projects` globale nel core con FK opzionale `global_project_id` in ogni tabella modulo. Model e Controller nel core (`core/Models/`, `core/Controllers/`). Ogni modulo riceve un metodo `getProjectKpi()` per fornire KPI sintetici alla dashboard globale. Dual mode: moduli funzionano sia con che senza progetto globale.

**Tech Stack:** PHP 8+, MySQL, Tailwind CSS, Alpine.js. Pattern MVC esistente del progetto Ainstein.

**Design doc:** `docs/plans/2026-02-19-global-projects-design.md`

---

### Task 1: Database - Creare tabella `projects` e migration

**Files:**
- Create: `database/migrations/001_create_global_projects.sql`

**Step 1: Scrivere la migration SQL**

```sql
-- Migration: Crea tabella progetti globali e aggiunge FK ai moduli
-- Data: 2026-02-19

-- Tabella progetti globali
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(500) NULL,
    description TEXT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6',
    status ENUM('active', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aggiunta global_project_id a ogni tabella modulo
ALTER TABLE aic_projects ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_global_project (global_project_id),
    ADD FOREIGN KEY fk_aic_global (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

ALTER TABLE sa_projects ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_global_project (global_project_id),
    ADD FOREIGN KEY fk_sa_global (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

ALTER TABLE st_projects ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_global_project (global_project_id),
    ADD FOREIGN KEY fk_st_global (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

ALTER TABLE kr_projects ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_global_project (global_project_id),
    ADD FOREIGN KEY fk_kr_global (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

ALTER TABLE ga_projects ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_global_project (global_project_id),
    ADD FOREIGN KEY fk_ga_global (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

ALTER TABLE il_projects ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_global_project (global_project_id),
    ADD FOREIGN KEY fk_il_global (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

ALTER TABLE cc_projects ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_global_project (global_project_id),
    ADD FOREIGN KEY fk_cc_global (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;
```

**Step 2: Eseguire migration in locale**

Run: `"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root seo_toolkit < database/migrations/001_create_global_projects.sql`
Expected: nessun errore, tabella `projects` creata, colonne `global_project_id` aggiunte.

**Step 3: Verificare**

Run: `"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root seo_toolkit -e "DESCRIBE projects; SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='seo_toolkit' AND COLUMN_NAME='global_project_id';"`
Expected: struttura tabella `projects` + 7 righe con `global_project_id`.

**Step 4: Commit**

```bash
git add database/migrations/001_create_global_projects.sql
git commit -m "feat(db): add global projects table and FK to all modules"
```

---

### Task 2: Model - Creare `GlobalProject.php`

**Files:**
- Create: `core/Models/GlobalProject.php`

**Step 1: Creare il Model**

Il model segue lo stesso pattern di `modules/ai-content/models/Project.php` (instance methods, `Database::fetch/fetchAll/insert/update/delete`).

Metodi da implementare:
- `find(int $id, ?int $userId = null): ?array`
- `allByUser(int $userId, string $status = 'active'): array`
- `allWithModuleStats(int $userId): array` - progetti + conteggio moduli attivi
- `create(array $data): int`
- `update(int $id, array $data, int $userId): bool`
- `delete(int $id, int $userId): bool`
- `countByUser(int $userId): int`
- `getActiveModules(int $id): array` - query tutte le `{prefix}_projects` con `global_project_id = $id`
- `getModuleStats(int $id): array` - chiama `getProjectKpi()` per ogni modulo attivato
- `activateModule(int $globalProjectId, string $moduleSlug, int $userId, array $extraData = []): int` - crea progetto modulo collegato

**Note implementative:**

Per `getActiveModules()`, interrogare tutte le 7 tabelle modulo:
```php
$modulesTables = [
    'ai-content'       => ['table' => 'aic_projects', 'label' => 'AI Content Generator', 'icon' => '...'],
    'seo-audit'        => ['table' => 'sa_projects',  'label' => 'SEO Audit', 'icon' => '...'],
    'seo-tracking'     => ['table' => 'st_projects',  'label' => 'SEO Tracking', 'icon' => '...'],
    'keyword-research' => ['table' => 'kr_projects',  'label' => 'Keyword Research', 'icon' => '...'],
    'ads-analyzer'     => ['table' => 'ga_projects',  'label' => 'Google Ads Analyzer', 'icon' => '...'],
    'internal-links'   => ['table' => 'il_projects',  'label' => 'Internal Links', 'icon' => '...'],
    'content-creator'  => ['table' => 'cc_projects',  'label' => 'Content Creator', 'icon' => '...'],
];
```

Per ogni tabella, fare `SELECT id FROM {table} WHERE global_project_id = ?` con try/catch per graceful degradation se tabella non esiste.

Per `activateModule()`:
- Leggere il progetto globale per ereditare `name` e `domain`
- In base al `$moduleSlug`, creare il record nella tabella appropriata
- Gestire i casi speciali: `sa_projects` ha `createWithConfig()`, `il_projects` ha `createWithStats()`, `st_projects` auto-crea `st_alert_settings`
- Impostare `global_project_id` sul nuovo record

Per `getModuleStats()`:
- Per ogni modulo attivo, fare `require_once` del model e chiamare `getProjectKpi()`
- Usare try/catch per resilienza

**Step 2: Verificare sintassi**

Run: `php -l core/Models/GlobalProject.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add core/Models/GlobalProject.php
git commit -m "feat(core): add GlobalProject model with cross-module support"
```

---

### Task 3: Aggiungere `getProjectKpi()` a tutti i moduli

**Files:**
- Modify: `modules/ai-content/models/Project.php` - aggiungere metodo
- Modify: `modules/seo-audit/models/Project.php` - aggiungere metodo
- Modify: `modules/seo-tracking/models/Project.php` - aggiungere metodo
- Modify: `modules/keyword-research/models/Project.php` - aggiungere metodo
- Modify: `modules/ads-analyzer/models/Project.php` - aggiungere metodo (static)
- Modify: `modules/internal-links/models/Project.php` - aggiungere metodo
- Modify: `modules/content-creator/models/Project.php` - aggiungere metodo

**Step 1: Aggiungere metodo a ogni modulo**

Il metodo ritorna un array standardizzato:
```php
[
    'label' => string,          // Nome modulo
    'slug' => string,           // Slug modulo
    'icon' => string,           // SVG path per icona
    'color' => string,          // Colore modulo (amber, emerald, blue, etc.)
    'metrics' => [              // Array di metriche KPI
        ['label' => string, 'value' => mixed, 'delta' => ?float, 'deltaGood' => ?bool],
    ],
    'link' => string,           // URL alla sezione modulo
    'lastActivity' => ?string,  // Data ultima attivita'
]
```

**KPI specifici per modulo:**

**AI Content** (`aic_`):
- Articoli totali (count da `aic_articles`)
- Articoli in coda (count da `aic_queue` con status pending)
- Parole generate totali (sum `word_count` da `aic_articles`)
- Link: `/ai-content/projects/{id}`

**SEO Audit** (`sa_`):
- Health score (da `sa_projects.health_score`)
- Issues critiche (count `sa_issues` severity=critical)
- Pagine analizzate (count `sa_pages`)
- Link: `/seo-audit/project/{id}/dashboard`

**SEO Tracking** (`st_`):
- Keyword monitorate (count `st_keywords` is_tracked=1)
- Posizione media (avg dalle ultime `st_rank_checks`)
- Keyword in top 10 (count `st_keywords` last_position <= 10)
- Link: `/seo-tracking/project/{id}`

**Keyword Research** (`kr_`):
- Ricerche completate (count `kr_researches` status=completed)
- Keyword totali (sum `filtered_keywords_count`)
- Cluster totali (count `kr_clusters`)
- Link: `/keyword-research/project/{id}/research`

**Ads Analyzer** (`ga_`):
- Campagne analizzate (count `ga_campaigns`)
- Ultima valutazione (score da `ga_campaign_evaluations` piu' recente)
- Delta score vs precedente (calcolo differenza)
- Link: `/ads-analyzer/projects/{id}`

**Internal Links** (`il_`):
- Pagine analizzate (da `il_project_stats.total_urls`)
- Link interni totali (da `il_project_stats.total_links`)
- Relevance media (da `il_project_stats.avg_relevance_score`)
- Link: `/internal-links/project/{id}`

**Content Creator** (`cc_`):
- Contenuti totali (count `cc_urls`)
- Contenuti generati (count `cc_urls` status=generated)
- Contenuti pubblicati (count `cc_urls` status=published)
- Link: `/content-creator/projects/{id}`

Nota: `ga/Project` usa metodi statici, quindi `getProjectKpi()` sara' `static` per quel modulo. Per gli altri, sara' un metodo di istanza.

**Step 2: Verificare sintassi di tutti i file modificati**

Run: `php -l modules/ai-content/models/Project.php && php -l modules/seo-audit/models/Project.php && php -l modules/seo-tracking/models/Project.php && php -l modules/keyword-research/models/Project.php && php -l modules/ads-analyzer/models/Project.php && php -l modules/internal-links/models/Project.php && php -l modules/content-creator/models/Project.php`
Expected: `No syntax errors detected` x7

**Step 3: Commit**

```bash
git add modules/*/models/Project.php
git commit -m "feat(modules): add getProjectKpi() method to all module Project models"
```

---

### Task 4: Controller - Creare `GlobalProjectController.php`

**Files:**
- Create: `core/Controllers/GlobalProjectController.php`

**Step 1: Creare il Controller**

Namespace: `Core\Controllers` (il controller e' nel core, non in un modulo).

L'autoloader in `public/index.php` riga 33 ha gia' `'Controllers\\' => BASE_PATH . '/controllers/'` ma NON `'Core\\Controllers\\'`. Opzione:
- **Mettere il controller in `controllers/GlobalProjectController.php`** con namespace `Controllers` per sfruttare l'autoloader esistente.

Questo e' piu' coerente col progetto (i controller core come `OAuthController` usano namespace `Controllers`).

**Azioni:**

```php
namespace Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\ModuleLoader;
use Core\Models\GlobalProject;

class GlobalProjectController
{
    private GlobalProject $project;

    public function __construct()
    {
        $this->project = new GlobalProject();
    }

    // GET /projects - Lista progetti globali
    public function index(): void
    {
        Middleware::auth();
        $user = Auth::user();
        $projects = $this->project->allWithModuleStats($user['id']);

        View::render('projects/index', [
            'title' => 'Progetti',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projects' => $projects,
        ]);
    }

    // GET /projects/create - Form creazione
    public function create(): void { ... }

    // POST /projects - Salva nuovo progetto
    public function store(): void { ... }

    // GET /projects/{id} - Dashboard progetto
    public function dashboard(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);
        if (!$project) { redirect(url('/projects')); }

        $activeModules = $this->project->getActiveModules($id);
        $moduleStats = $this->project->getModuleStats($id);
        $availableModules = ModuleLoader::getActiveModules(); // tutti i moduli attivi nel sistema

        View::render('projects/dashboard', [
            'title' => $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'activeModules' => $activeModules,
            'moduleStats' => $moduleStats,
            'availableModules' => $availableModules,
        ]);
    }

    // GET /projects/{id}/settings - Impostazioni
    public function settings(int $id): void { ... }

    // POST /projects/{id}/settings - Salva impostazioni
    public function update(int $id): void { ... }

    // POST /projects/{id}/activate-module - Attiva modulo
    public function activateModule(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);
        if (!$project) { redirect(url('/projects')); }

        $moduleSlug = $_POST['module'] ?? '';
        // Validazione slug, creazione progetto modulo, redirect
        $moduleProjectId = $this->project->activateModule($id, $moduleSlug, $user['id']);
        // Redirect alla sezione del modulo
    }

    // POST /projects/{id}/delete - Elimina
    public function destroy(int $id): void { ... }
}
```

**Step 2: Verificare sintassi**

Run: `php -l controllers/GlobalProjectController.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add controllers/GlobalProjectController.php
git commit -m "feat(core): add GlobalProjectController with CRUD and module activation"
```

---

### Task 5: Routes - Aggiungere routes in `public/index.php`

**Files:**
- Modify: `public/index.php` (aggiungere routes prima di `ModuleLoader::loadAll()`, circa riga 660)

**Step 1: Aggiungere routes**

Inserire prima di `// --- Admin Routes ---` (riga ~657):

```php
// --- Global Projects Routes ---
Router::get('/projects', function () {
    $controller = new Controllers\GlobalProjectController();
    $controller->index();
});

Router::get('/projects/create', function () {
    $controller = new Controllers\GlobalProjectController();
    $controller->create();
});

Router::post('/projects', function () {
    $controller = new Controllers\GlobalProjectController();
    $controller->store();
});

Router::get('/projects/{id}', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->dashboard((int) $id);
});

Router::get('/projects/{id}/settings', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->settings((int) $id);
});

Router::post('/projects/{id}/settings', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->update((int) $id);
});

Router::post('/projects/{id}/activate-module', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->activateModule((int) $id);
});

Router::post('/projects/{id}/delete', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->destroy((int) $id);
});
```

**Step 2: Verificare sintassi**

Run: `php -l public/index.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add public/index.php
git commit -m "feat(routes): add global projects routes"
```

---

### Task 6: Views - Creare le viste dei progetti globali

**Files:**
- Create: `shared/views/projects/index.php` - Lista progetti
- Create: `shared/views/projects/create.php` - Form creazione
- Create: `shared/views/projects/dashboard.php` - Dashboard progetto con KPI
- Create: `shared/views/projects/settings.php` - Impostazioni progetto

**Step 1: Creare `shared/views/projects/index.php`**

Lista progetti con griglia di card. Ogni card mostra:
- Nome progetto con badge colore
- Dominio
- Numero moduli attivi (es. "3 moduli attivi")
- Ultimo aggiornamento
- Link a dashboard progetto

CTA "Nuovo Progetto" in alto a destra.
Empty state con `View::partial('components/table-empty-state', ...)`.

Stile: griglia responsive `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`.
Card: `bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5`.
Badge colore: `<span class="w-3 h-3 rounded-full" style="background-color: {color}"></span>`.

**Step 2: Creare `shared/views/projects/create.php`**

Form con campi:
- Nome (text, required)
- Dominio (text, opzionale, placeholder "es. esempio.it")
- Descrizione (textarea, opzionale)
- Colore (input color picker o preset di 6-8 colori)

CSRF token: `<?= csrf_field() ?>`.
Submit POST a `/projects`.

**Step 3: Creare `shared/views/projects/dashboard.php`**

**Header progetto:**
- Badge colore + nome + dominio
- Pulsanti: Impostazioni, Elimina

**Griglia moduli attivati (KPI cards):**
Per ogni modulo in `$moduleStats`:
- Card con icona modulo, nome, metriche KPI
- Ogni metrica mostra label, value, delta (se presente) con freccia su/giu' colorata
- Link "Vai al modulo" in fondo alla card

**Sezione moduli non attivati:**
Card grigie/outline per moduli disponibili ma non attivi, con pulsante "Attiva" e breve descrizione.
Il pulsante "Attiva" e' un form POST a `/projects/{id}/activate-module` con campo hidden `module={slug}`.

Stile card KPI:
```
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 p-5" style="border-left-color: {module_color}">
    <div class="flex items-center gap-3 mb-3">
        <svg>{icon}</svg>
        <h3>{module_label}</h3>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <span class="text-xs text-slate-500">{metric_label}</span>
            <span class="text-lg font-semibold">{value}</span>
            <!-- delta opzionale -->
            <span class="text-xs text-green-600">+2.3</span>
        </div>
    </div>
    <a href="{link}" class="mt-3 text-sm text-primary-600 hover:underline">Vai al modulo &rarr;</a>
</div>
```

**Step 4: Creare `shared/views/projects/settings.php`**

Form per modificare nome, dominio, descrizione, colore del progetto.
Sezione "Zona pericolosa" con pulsante "Elimina progetto".

**Step 5: Verificare sintassi di tutte le viste**

Run: `php -l shared/views/projects/index.php && php -l shared/views/projects/create.php && php -l shared/views/projects/dashboard.php && php -l shared/views/projects/settings.php`
Expected: `No syntax errors detected` x4

**Step 6: Commit**

```bash
git add shared/views/projects/
git commit -m "feat(views): add global project views (index, create, dashboard, settings)"
```

---

### Task 7: Navigazione - Aggiungere "Progetti" alla sidebar

**Files:**
- Modify: `shared/views/components/nav-items.php`

**Step 1: Aggiungere link "Progetti"**

Aggiungere prima dei link ai moduli (prima dei blocchi `navLink` per i moduli):

```php
<!-- Progetti Globali -->
<?= navLink('/projects', 'Progetti', '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>', $currentPath) ?>

<div class="my-2 border-t border-slate-200 dark:border-slate-700"></div>
```

L'icona e' Heroicons "folder" (archivio/cartella). Posizionata sopra il separatore prima dei moduli.

**Step 2: Verificare sintassi**

Run: `php -l shared/views/components/nav-items.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add shared/views/components/nav-items.php
git commit -m "feat(nav): add Progetti link to sidebar navigation"
```

---

### Task 8: Dashboard principale - Aggiungere sezione progetti

**Files:**
- Modify: `public/index.php` (route `/dashboard`, circa riga 431-564)
- Modify: `shared/views/dashboard.php`

**Step 1: Aggiungere query progetti globali nella route dashboard**

Nel handler della route `/dashboard` (riga ~431), aggiungere dopo le query esistenti:

```php
// Global Projects
$globalProjects = [];
try {
    $gpModel = new \Core\Models\GlobalProject();
    $globalProjects = $gpModel->allWithModuleStats($uid);
} catch (\Exception $e) {}
```

Passare `$globalProjects` alla view.

**Step 2: Modificare la vista dashboard**

Aggiungere in cima alla dashboard (prima dei widget moduli esistenti):

1. **Se ci sono progetti globali**: griglia di card progetto con mini-stats
2. **Se non ci sono**: CTA "Crea il tuo primo progetto" con breve spiegazione
3. **Widget moduli standalone**: mantenerli sotto, solo per dati non collegati a progetti globali

Il layout sara':
```
[Header dashboard]
[Sezione "I tuoi progetti" - card grid]
[Separatore]
[Widget moduli standalone esistenti]
```

**Step 3: Verificare sintassi**

Run: `php -l public/index.php && php -l shared/views/dashboard.php`
Expected: `No syntax errors detected` x2

**Step 4: Commit**

```bash
git add public/index.php shared/views/dashboard.php
git commit -m "feat(dashboard): add global projects section to main dashboard"
```

---

### Task 9: Test manuale completo

**Nessun file da creare/modificare - solo verifica.**

**Step 1: Testare lista progetti (vuota)**

URL: `http://localhost/seo-toolkit/projects`
Expected: pagina con empty state "Nessun progetto" e CTA "Nuovo Progetto"

**Step 2: Testare creazione progetto**

URL: `http://localhost/seo-toolkit/projects/create`
Expected: form con campi nome, dominio, descrizione, colore
Azione: compilare e submit
Expected: redirect a `/projects/{id}` (dashboard progetto)

**Step 3: Testare dashboard progetto (senza moduli)**

URL: `http://localhost/seo-toolkit/projects/{id}`
Expected: header progetto + card grigie per tutti i moduli con pulsante "Attiva"

**Step 4: Testare attivazione modulo**

Azione: cliccare "Attiva" su SEO Tracking
Expected: crea `st_projects` con `global_project_id`, redirect alla sezione modulo
Verifica DB: `SELECT global_project_id FROM st_projects ORDER BY id DESC LIMIT 1`

**Step 5: Testare dashboard progetto (con moduli attivi)**

URL: `http://localhost/seo-toolkit/projects/{id}`
Expected: card KPI per il modulo attivato + card grigie per gli altri

**Step 6: Testare settings progetto**

URL: `http://localhost/seo-toolkit/projects/{id}/settings`
Expected: form precompilato, modifica e salva funzionante

**Step 7: Testare dashboard principale**

URL: `http://localhost/seo-toolkit/dashboard`
Expected: sezione progetti globali visibile in cima

**Step 8: Testare sidebar**

Expected: voce "Progetti" visibile nella sidebar, sopra i moduli

**Step 9: Testare eliminazione progetto**

Azione: elimina progetto dalle settings
Expected: progetto eliminato, moduli collegati hanno `global_project_id = NULL`
Verifica DB: `SELECT global_project_id FROM st_projects WHERE id = {moduleProjectId}`

**Step 10: Commit finale**

```bash
git add -A
git commit -m "feat: global projects system - cross-module hub with KPI dashboard"
```

---

### Task 10: Deploy produzione

**Step 1: Push e pull**

```bash
# Locale
git push origin main

# Produzione (SSH)
ssh -i ~/.ssh/siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
cd ~/www/ainstein.it/public_html
git pull origin main
```

**Step 2: Eseguire migration in produzione**

```bash
mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1 < database/migrations/001_create_global_projects.sql
```

**Step 3: Verificare**

```bash
mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1 -e "DESCRIBE projects;"
mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1 -e "SELECT COLUMN_NAME, TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbj0xoiwysdlk1' AND COLUMN_NAME='global_project_id';"
```

**Step 4: Testare in produzione**

URL: `https://ainstein.it/projects`
Expected: pagina funzionante, possibilita' di creare progetto e attivare moduli.

---

## Dipendenze tra task

```
Task 1 (DB) ──► Task 2 (Model) ──► Task 3 (KPI moduli) ──► Task 4 (Controller)
                                                                      │
                                                                      ▼
                                              Task 5 (Routes) ◄── Task 4
                                                      │
                                                      ▼
                              Task 6 (Views) ◄── Task 5
                                      │
                                      ▼
                      Task 7 (Nav) ──► Task 8 (Dashboard) ──► Task 9 (Test) ──► Task 10 (Deploy)
```

Task 1 → 2 → 3: sequenziali (ogni step dipende dal precedente)
Task 3 e 4: possono essere parallelizzati
Task 5: dipende da Task 4
Task 6: dipende da Task 5 (routes devono esistere per le form action)
Task 7 e 8: possono essere parallelizzati dopo Task 6
Task 9: dopo tutti i precedenti
Task 10: dopo Task 9
