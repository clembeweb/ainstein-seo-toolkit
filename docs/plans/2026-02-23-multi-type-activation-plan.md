# Multi-Type Module Activation - Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Permettere l'attivazione di piu' tipi dello stesso modulo (es. ai-content manual + auto + meta-tag) dentro un singolo progetto globale, con UX chiara sulla dashboard.

**Architecture:** Modifiche a 3 file: model GlobalProject (query type, chiave composita stats, nuovo metodo getRemainingTypes), controller (passare dati extra alla view), dashboard view (raggruppamento per slug, sub-card per tipo, bottone "+", filtro modal). Nessuna modifica DB.

**Tech Stack:** PHP 8+, MySQL, Tailwind CSS, Alpine.js. Pattern MVC Ainstein.

**Design doc:** `docs/plans/2026-02-23-multi-type-activation-design.md`

---

### Task 1: Model — Aggiornare `getActiveModules()` per includere type

**Files:**
- Modify: `core/Models/GlobalProject.php` (metodo `getActiveModules`, righe 278-307)

**Step 1: Modificare la query per includere `type` nei moduli tipizzati**

Nel metodo `getActiveModules()`, cambiare la query da `SELECT id` a `SELECT id, type` per i moduli che hanno tipi definiti in `MODULE_TYPES`. Per i moduli senza tipi, mantenere `SELECT id`.

Aggiungere `type` e `type_label` ai dati ritornati.

```php
public function getActiveModules(int $id): array
{
    $active = [];

    foreach (self::MODULE_CONFIG as $slug => $config) {
        try {
            $hasTypes = isset(self::MODULE_TYPES[$slug]);
            $sql = $hasTypes
                ? "SELECT id, type FROM {$config['table']} WHERE global_project_id = ?"
                : "SELECT id FROM {$config['table']} WHERE global_project_id = ?";

            $rows = Database::fetchAll($sql, [$id]);

            foreach ($rows as $row) {
                $type = $row['type'] ?? null;
                $typeLabel = null;
                if ($type && $hasTypes && isset(self::MODULE_TYPES[$slug][$type])) {
                    $typeLabel = self::MODULE_TYPES[$slug][$type]['label'];
                }

                $active[] = [
                    'slug' => $slug,
                    'module_project_id' => (int) $row['id'],
                    'label' => $config['label'],
                    'table' => $config['table'],
                    'color' => $config['color'],
                    'icon' => $config['icon'],
                    'route_prefix' => $config['route_prefix'],
                    'type' => $type,
                    'type_label' => $typeLabel,
                ];
            }
        } catch (\Exception $e) {
            continue;
        }
    }

    return $active;
}
```

**Step 2: Verificare sintassi**

Run: `php -l core/Models/GlobalProject.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add core/Models/GlobalProject.php
git commit -m "feat(model): include type/type_label in getActiveModules()"
```

---

### Task 2: Model — Aggiornare `getModuleStats()` con chiave composita

**Files:**
- Modify: `core/Models/GlobalProject.php` (metodo `getModuleStats`, righe 315-360)

**Step 1: Usare chiave composita `slug:type` per moduli tipizzati**

```php
public function getModuleStats(int $id): array
{
    $activeModules = $this->getActiveModules($id);
    $stats = [];

    $modelMap = [
        'ai-content' => \Modules\AiContent\Models\Project::class,
        'seo-audit' => \Modules\SeoAudit\Models\Project::class,
        'seo-tracking' => \Modules\SeoTracking\Models\Project::class,
        'keyword-research' => \Modules\KeywordResearch\Models\Project::class,
        'ads-analyzer' => \Modules\AdsAnalyzer\Models\Project::class,
        'internal-links' => \Modules\InternalLinks\Models\Project::class,
        'content-creator' => \Modules\ContentCreator\Models\Project::class,
    ];

    foreach ($activeModules as $module) {
        $slug = $module['slug'];
        $moduleProjectId = $module['module_project_id'];

        if (!isset($modelMap[$slug])) {
            continue;
        }

        try {
            $modelClass = $modelMap[$slug];

            if ($slug === 'ads-analyzer') {
                $kpi = $modelClass::getProjectKpi($moduleProjectId);
            } else {
                $model = new $modelClass();
                $kpi = $model->getProjectKpi($moduleProjectId);
            }

            // Chiave composita per moduli tipizzati
            $key = $module['type'] ? "{$slug}:{$module['type']}" : $slug;
            $stats[$key] = $kpi;
        } catch (\Exception $e) {
            $key = $module['type'] ? "{$slug}:{$module['type']}" : $slug;
            $stats[$key] = [
                'metrics' => [],
                'lastActivity' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    return $stats;
}
```

**Step 2: Verificare sintassi**

Run: `php -l core/Models/GlobalProject.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add core/Models/GlobalProject.php
git commit -m "feat(model): use composite key slug:type in getModuleStats()"
```

---

### Task 3: Model — Aggiungere metodo `getRemainingTypes()`

**Files:**
- Modify: `core/Models/GlobalProject.php` (nuovo metodo, dopo `getModuleStats`)

**Step 1: Scrivere il metodo**

```php
/**
 * Ritorna i tipi NON ancora attivati per ogni modulo tipizzato.
 * Usato dalla dashboard per mostrare il bottone "+ Aggiungi tipo".
 *
 * @return array<string, array> Tipi rimanenti indicizzati per slug modulo
 */
public function getRemainingTypes(int $id): array
{
    $remaining = [];

    foreach (self::MODULE_TYPES as $slug => $allTypes) {
        if (!isset(self::MODULE_CONFIG[$slug])) {
            continue;
        }

        $table = self::MODULE_CONFIG[$slug]['table'];

        try {
            $rows = Database::fetchAll(
                "SELECT DISTINCT type FROM {$table} WHERE global_project_id = ? AND type IS NOT NULL",
                [$id]
            );
            $activeTypes = array_column($rows, 'type');
        } catch (\Exception $e) {
            $activeTypes = [];
        }

        $notActivated = [];
        foreach ($allTypes as $typeKey => $typeInfo) {
            if (!in_array($typeKey, $activeTypes, true)) {
                $notActivated[$typeKey] = $typeInfo;
            }
        }

        if (!empty($notActivated)) {
            $remaining[$slug] = $notActivated;
        }
    }

    return $remaining;
}
```

**Step 2: Verificare sintassi**

Run: `php -l core/Models/GlobalProject.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add core/Models/GlobalProject.php
git commit -m "feat(model): add getRemainingTypes() for multi-type activation"
```

---

### Task 4: Controller — Passare dati multi-tipo alla view

**Files:**
- Modify: `controllers/GlobalProjectController.php` (metodo `dashboard`)

**Step 1: Leggere il metodo dashboard attuale**

Read: `controllers/GlobalProjectController.php` — trovare il metodo `dashboard()`.

**Step 2: Aggiungere remainingTypes e activeTypesPerModule**

Dopo la riga che chiama `getModuleStats()`, aggiungere:

```php
$remainingTypes = $this->project->getRemainingTypes($id);

// Tipi attivi per modulo (per filtrare la modal)
$activeTypesPerModule = [];
foreach ($activeModules as $m) {
    if (!empty($m['type'])) {
        $activeTypesPerModule[$m['slug']][] = $m['type'];
    }
}
```

E nella chiamata `View::render()`, aggiungere:

```php
'remainingTypes' => $remainingTypes,
'activeTypesPerModule' => $activeTypesPerModule,
```

**Step 3: Verificare sintassi**

Run: `php -l controllers/GlobalProjectController.php`
Expected: `No syntax errors detected`

**Step 4: Commit**

```bash
git add controllers/GlobalProjectController.php
git commit -m "feat(controller): pass multi-type data to project dashboard view"
```

---

### Task 5: View — Riscrivere sezione moduli attivi con raggruppamento per slug

**Files:**
- Modify: `shared/views/projects/dashboard.php` (sezione "Active Modules KPI", righe 50-148)

**Step 1: Leggere il file dashboard corrente per avere il contesto completo**

Read: `shared/views/projects/dashboard.php`

**Step 2: Sostituire la sezione "Active Modules KPI" (righe 50-148)**

La nuova logica:
1. Raggruppa `$activeModules` per slug
2. Per ogni slug, se ha piu' record (tipi multipli), mostra una card container con sub-card per tipo
3. Se ha un solo record E ha tipi definiti, mostra card compatta con badge tipo
4. Se non ha tipi (moduli senza tipo), mostra card come prima
5. In fondo alla card, se ci sono tipi rimanenti (`$remainingTypes[$slug]`), mostra bottone "+"

Sostituire il blocco da `<?php if (!empty($activeModules)): ?>` fino a `<?php else: ?>` (riga 50-136) con:

```php
<?php if (!empty($activeModules)): ?>
<!-- Active Modules KPI Section -->
<div>
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Moduli attivi</h2>
    <?php
    // Raggruppa moduli per slug
    $groupedModules = [];
    foreach ($activeModules as $module) {
        $groupedModules[$module['slug']][] = $module;
    }
    ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <?php foreach ($groupedModules as $slug => $modules):
            $firstModule = $modules[0];
            $color = $firstModule['color'] ?? 'blue';
            $colors = $colorMap[$color] ?? $colorMap['blue'];
            $hasTypes = isset(self::MODULE_TYPES[$slug] ?? $moduleTypes[$slug] ?? null);
            $remaining = $remainingTypes[$slug] ?? [];
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5" style="border-left: 4px solid <?= htmlspecialchars($colors['border']) ?>">
            <!-- Header modulo -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg <?= $colors['bg'] ?> flex items-center justify-center">
                        <svg class="w-5 h-5 <?= $colors['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= htmlspecialchars($firstModule['icon'] ?? '') ?>"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($firstModule['label']) ?></h3>
                </div>
                <?php if (count($modules) === 1): ?>
                <a href="<?= url($firstModule['route_prefix'] . '/' . $firstModule['module_project_id']) ?>" class="text-sm font-medium <?= $colors['text'] ?> hover:underline">
                    Vai al modulo &rarr;
                </a>
                <?php endif; ?>
            </div>

            <?php if (count($modules) === 1): ?>
                <?php
                // Card singola — con badge tipo se tipizzato
                $mod = $modules[0];
                $statsKey = $mod['type'] ? "{$slug}:{$mod['type']}" : $slug;
                $stats = $moduleStats[$statsKey] ?? ['metrics' => [], 'lastActivity' => null];
                $metrics = $stats['metrics'] ?? [];
                ?>

                <?php if ($mod['type_label']): ?>
                <div class="mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $colors['bg'] ?> <?= $colors['text'] ?>">
                        <?= htmlspecialchars($mod['type_label']) ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php if (!empty($metrics)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-<?= min(count($metrics), 4) ?> gap-3">
                    <?php foreach ($metrics as $metric): ?>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-lg p-2.5">
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($metric['label'] ?? '') ?></p>
                        <p class="text-lg font-semibold text-slate-900 dark:text-white mt-0.5">
                            <?php
                            $value = $metric['value'] ?? 0;
                            if (is_float($value)) {
                                echo number_format($value, 1);
                            } elseif (is_numeric($value)) {
                                echo number_format((int)$value);
                            } else {
                                echo htmlspecialchars((string)$value);
                            }
                            ?>
                        </p>
                        <?php if (isset($metric['delta']) && $metric['delta'] !== null): ?>
                        <span class="text-xs <?= $metric['delta'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>">
                            <?= $metric['delta'] >= 0 ? '+' : '' ?><?= $metric['delta'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessun dato ancora. Inizia a usare il modulo per vedere le statistiche.</p>
                <?php endif; ?>

                <?php if (!empty($stats['lastActivity'])): ?>
                <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">
                    <?php
                    $lastActivity = strtotime($stats['lastActivity']);
                    $diff = time() - $lastActivity;
                    if ($diff < 3600) { $timeAgo = floor($diff / 60) . ' min fa'; }
                    elseif ($diff < 86400) { $timeAgo = floor($diff / 3600) . ' ore fa'; }
                    else { $timeAgo = date('d/m/Y', $lastActivity); }
                    ?>
                    Ultima attivit&agrave;: <?= $timeAgo ?>
                </p>
                <?php endif; ?>

            <?php else: ?>
                <?php
                // Multi-tipo — sub-card per ogni tipo
                foreach ($modules as $mod):
                    $statsKey = $mod['type'] ? "{$slug}:{$mod['type']}" : $slug;
                    $stats = $moduleStats[$statsKey] ?? ['metrics' => [], 'lastActivity' => null];
                    $metrics = $stats['metrics'] ?? [];
                    $moduleLink = url($mod['route_prefix'] . '/' . $mod['module_project_id']);
                ?>
                <div class="bg-slate-50 dark:bg-slate-700/30 rounded-lg p-3 mb-2">
                    <div class="flex items-center justify-between mb-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $colors['bg'] ?> <?= $colors['text'] ?>">
                            <?= htmlspecialchars($mod['type_label'] ?? $mod['type'] ?? '') ?>
                        </span>
                        <a href="<?= $moduleLink ?>" class="text-xs font-medium <?= $colors['text'] ?> hover:underline">
                            Vai &rarr;
                        </a>
                    </div>
                    <?php if (!empty($metrics)): ?>
                    <div class="grid grid-cols-<?= min(count($metrics), 3) ?> gap-2">
                        <?php foreach ($metrics as $metric): ?>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($metric['label'] ?? '') ?></p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                <?php
                                $value = $metric['value'] ?? 0;
                                echo is_float($value) ? number_format($value, 1) : (is_numeric($value) ? number_format((int)$value) : htmlspecialchars((string)$value));
                                ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Nessun dato ancora.</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($remaining)): ?>
            <!-- Bottone + Aggiungi tipo -->
            <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                <button type="button" @click="openTypeModal('<?= $slug ?>')"
                        class="inline-flex items-center text-sm font-medium <?= $colors['text'] ?> hover:underline">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <?php
                    $remainingLabels = array_column($remaining, 'label');
                    if (count($remainingLabels) === 1) {
                        echo 'Aggiungi ' . htmlspecialchars($remainingLabels[0]);
                    } else {
                        echo 'Aggiungi tipo';
                    }
                    ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
```

**NOTA CRITICA:** La view usa `$moduleTypes` che viene passato dal controller come `$this->project->getModuleTypes()`. Verificare che la variabile sia disponibile nella view. Se non c'e', il controller la deve passare (gia' presente nel `dashboard()` come `$moduleTypes`). Usare `$moduleTypes[$slug]` anziche' `self::MODULE_TYPES[$slug]` (non accessibile dalla view).

**Step 3: Verificare sintassi**

Run: `php -l shared/views/projects/dashboard.php`
Expected: `No syntax errors detected`

**Step 4: Commit**

```bash
git add shared/views/projects/dashboard.php
git commit -m "feat(view): grouped KPI cards with type badges and + button"
```

---

### Task 6: View — Aggiornare sezione "Moduli disponibili" e modal

**Files:**
- Modify: `shared/views/projects/dashboard.php` (sezione "Available Modules", righe 150-288)

**Step 1: Aggiornare la logica di filtro moduli disponibili**

Sostituire il blocco PHP che calcola `$nonActivated` (righe 152-158):

```php
<?php
// Filtra moduli disponibili — per tipizzati, nasconde solo se TUTTI i tipi sono attivi
$nonActivated = [];
foreach ($moduleConfig as $slug => $config) {
    if (!in_array($slug, $systemActiveSlugs)) {
        continue; // Modulo non attivo nel sistema
    }

    if (isset($moduleTypes[$slug])) {
        // Modulo tipizzato: nasconde solo se tutti i tipi sono attivati
        $hasRemainingTypes = !empty($remainingTypes[$slug] ?? []);
        $isActivated = in_array($slug, $activeSlugs);
        if (!$isActivated || $hasRemainingTypes) {
            // Non attivato affatto OPPURE ha tipi rimanenti
            if (!$isActivated) {
                $nonActivated[$slug] = $config;
            }
            // Se e' gia' attivato con tipi rimanenti, il bottone "+" e' sulla card attiva
            // quindi non lo mostriamo in "disponibili" (evita duplicazione)
        }
    } else {
        // Modulo senza tipi: nasconde se attivato
        if (!in_array($slug, $activeSlugs)) {
            $nonActivated[$slug] = $config;
        }
    }
}
?>
```

**Step 2: Aggiornare la modal per filtrare i tipi gia' attivi**

Nel `<script>` della funzione `activationModal()`, aggiornare per ricevere i tipi attivi e filtrarli:

```javascript
function activationModal() {
    const allTypes = <?= json_encode($_moduleTypes) ?>;
    const labels = <?= json_encode(array_combine(array_keys($moduleConfig), array_column($moduleConfig, 'label'))) ?>;
    const activeTypesMap = <?= json_encode($activeTypesPerModule ?? []) ?>;
    return {
        open: false,
        moduleSlug: '',
        moduleLabel: '',
        types: {},
        openTypeModal(slug) {
            this.moduleSlug = slug;
            this.moduleLabel = labels[slug] || slug;
            // Filtra i tipi gia' attivi
            const activeForSlug = activeTypesMap[slug] || [];
            const available = {};
            for (const [key, val] of Object.entries(allTypes[slug] || {})) {
                if (!activeForSlug.includes(key)) {
                    available[key] = val;
                }
            }
            this.types = available;
            this.open = true;
        }
    };
}
```

**IMPORTANTE:** Il `x-data="activationModal()"` deve avvolgere TUTTA la sezione (sia moduli attivi che disponibili) perche' il bottone "+" nelle card attive chiama `openTypeModal()`. Spostare `x-data="activationModal()"` su un div wrapper che contenga sia la sezione attivi che disponibili.

**Step 3: Verificare sintassi**

Run: `php -l shared/views/projects/dashboard.php`
Expected: `No syntax errors detected`

**Step 4: Commit**

```bash
git add shared/views/projects/dashboard.php
git commit -m "feat(view): update available modules filter and modal for multi-type"
```

---

### Task 7: Test manuale in locale

**Nessun file da modificare — solo verifica.**

**Step 1: Creare un progetto globale di test**

URL: `http://localhost/seo-toolkit/projects/create`
Creare: nome "Test Multi-Tipo", dominio "test.example.com"

**Step 2: Attivare AI Content — tipo "manual"**

URL: `http://localhost/seo-toolkit/projects/{id}`
Click "Attiva" su AI Content Generator → selezionare "Articoli Manuali"
Expected: redirect, card KPI mostra badge "Articoli Manuali" + bottone "+ Aggiungi tipo"

**Step 3: Attivare AI Content — tipo "meta-tag" via bottone "+"**

Tornare alla dashboard progetto
Click "+ Aggiungi tipo" (o "+ Aggiungi SEO Meta Tags") sulla card AI Content
Expected: modal con solo "Articoli Automatici" e "SEO Meta Tags" (manual filtrato)
Selezionare "SEO Meta Tags"
Expected: redirect, card AI Content ora mostra 2 sub-card (Manuali + Meta Tags)

**Step 4: Verificare che bottone "+" mostri solo tipo rimanente**

Sulla card AI Content, il bottone "+" deve mostrare "Aggiungi Articoli Automatici" (unico tipo rimasto)

**Step 5: Attivare AI Content — tipo "auto"**

Click "+ Aggiungi Articoli Automatici"
Expected: card AI Content mostra 3 sub-card. Bottone "+" sparisce (tutti i tipi attivati).

**Step 6: Verificare sezione "Moduli disponibili"**

AI Content Generator NON deve apparire in "Moduli disponibili" (tutti i tipi attivi).
Gli altri moduli (SEO Audit, SEO Tracking, etc.) devono essere ancora visibili.

**Step 7: Ripetere per Keyword Research**

Attivare "Research Guidata", verificare bottone "+", attivare "Piano Editoriale", verificare sub-card.

**Step 8: Verificare DB**

Run: `"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root seo_toolkit -e "SELECT id, name, type, global_project_id FROM aic_projects WHERE global_project_id IS NOT NULL;"`
Expected: 3 record con tipi diversi, stesso global_project_id.

**Step 9: Commit finale**

```bash
git add -A
git commit -m "feat: multi-type module activation in global projects dashboard"
```

---

### Task 8: Verificare landing moduli

**Nessun file da modificare — solo verifica.**

**Step 1: Verificare landing AI Content**

URL: `http://localhost/seo-toolkit/ai-content`
Expected: 3 tab (manual, auto, meta-tag) con i progetti del global project visibili nei rispettivi tab.

**Step 2: Verificare landing Keyword Research**

URL: `http://localhost/seo-toolkit/keyword-research`
Expected: progetti attivati visibili nei tab corrispondenti.

**Step 3: Verificare che il progetto globale funzioni normalmente**

URL: `http://localhost/seo-toolkit/projects/{id}`
Expected: dashboard con tutte le card e KPI funzionanti.

---

## Dipendenze tra task

```
Task 1 (getActiveModules) ──► Task 2 (getModuleStats) ──► Task 3 (getRemainingTypes)
                                                                    │
                                                                    ▼
                                                          Task 4 (Controller)
                                                                    │
                                                                    ▼
                                                    Task 5 (View attivi) ──► Task 6 (View disponibili + modal)
                                                                                        │
                                                                                        ▼
                                                                            Task 7 (Test) ──► Task 8 (Verifica)
```

Task 1 → 2 → 3: sequenziali (ogni step dipende dal precedente)
Task 4: dipende da Task 3
Task 5: dipende da Task 4
Task 6: dipende da Task 5 (condividono lo stesso file)
Task 7: dopo tutti i precedenti
Task 8: dopo Task 7
