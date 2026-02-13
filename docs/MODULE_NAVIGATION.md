# Standard Navigazione Moduli

## Regola Principale

Ogni modulo che ha sotto-pagine (es. progetti con sezioni) **deve** usare l'accordion nella sidebar principale, **NON** una sidebar separata.

Questo garantisce:
- UI consistente in tutta la piattaforma
- Nessuna confusione con doppia sidebar
- Esperienza utente uniforme
- Manutenzione centralizzata

---

## Struttura Navigazione

```
[Nome Modulo] ▼
  └── [Nome Entità] ▼  (es. nome progetto)
        ├── Dashboard
        ├── Sezione 1
        ├── Sezione 2
        ├── ─── CATEGORIA ───
        ├── Sotto-sezione 1
        ├── Sotto-sezione 2
        └── Settings
```

### Esempio: Internal Links Module

```
Internal Links ▼
  └── "My Website" ▼
        ├── Dashboard        (exact match)
        ├── URLs
        ├── Scraping
        ├── Links
        ├── AI Analysis
        ├── Link Graph
        ├── ─── REPORTS ───
        ├── Anchor Analysis
        ├── Orphan Pages
        ├── Link Juice
        ├── Compare
        └── Settings
```

---

## Implementazione

### 1. Sidebar Principale (`shared/views/components/nav-items.php`)

La sidebar principale gestisce **TUTTA** la navigazione. Ogni modulo con sotto-entità deve aggiungere il proprio blocco accordion qui.

```php
<?php if ($module['slug'] === 'mio-modulo'): ?>
    <!-- Mio Modulo con Accordion -->
    <div x-data="{ expanded: <?= $mioModuloEntityId ? 'true' : 'false' ?> }">
        <!-- Link principale modulo -->
        <div class="flex items-center">
            <a href="<?= url('/mio-modulo') ?>" class="...">
                <svg>...</svg>
                <span><?= e($module['name']) ?></span>
            </a>
            <?php if ($mioModuloEntityId): ?>
            <button @click="expanded = !expanded" class="p-2 ...">
                <svg class="w-4 h-4 transition-transform" :class="expanded && 'rotate-180'">...</svg>
            </button>
            <?php endif; ?>
        </div>

        <?php if ($mioModuloEntityId && $mioModuloEntity): ?>
        <!-- Sub-navigation -->
        <div x-show="expanded"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="ml-3 mt-1 pl-3 border-l-2 border-slate-200 dark:border-slate-700 space-y-0.5">

            <!-- Entity Name Header -->
            <div class="px-2 py-1.5 text-xs font-semibold text-slate-500 truncate">
                <?= e($mioModuloEntity['name']) ?>
            </div>

            <!-- Navigation Links -->
            <?= navSubLink("/mio-modulo/entity/{$entityId}", 'Dashboard', '<svg>...</svg>', $currentPath, true) ?>
            <?= navSubLink("/mio-modulo/entity/{$entityId}/section1", 'Section 1', '<svg>...</svg>', $currentPath) ?>

            <!-- Category Separator -->
            <div class="px-2 py-1 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-2">
                Reports
            </div>

            <?= navSubLink("/mio-modulo/entity/{$entityId}/reports/one", 'Report 1', '<svg>...</svg>', $currentPath) ?>

            <!-- Settings (with separator) -->
            <div class="pt-1 mt-1 border-t border-slate-200 dark:border-slate-700">
                <?= navSubLink("/mio-modulo/entity/{$entityId}/settings", 'Settings', '<svg>...</svg>', $currentPath) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Moduli semplici (link singolo) -->
    <?= navLink('/' . $module['slug'], $module['name'], '<svg>...</svg>', $currentPath) ?>
<?php endif; ?>
```

### 2. Rilevamento Entità

All'inizio di `nav-items.php`, rileva se l'utente è dentro un'entità:

```php
// Check if we're inside a module entity
$mioModuloEntityId = null;
$mioModuloEntity = null;
if (preg_match('#^/mio-modulo/entity/(\d+)#', $currentPath, $matches)) {
    $mioModuloEntityId = (int) $matches[1];
    try {
        if (class_exists('\\Modules\\MioModulo\\Models\\Entity')) {
            $entityModel = new \Modules\MioModulo\Models\Entity();
            $mioModuloEntity = $entityModel->find($mioModuloEntityId);
        }
    } catch (\Exception $e) {
        // Silently fail - entity info not critical for navigation
    }
}
```

### 3. Helper Functions

Usare le funzioni helper definite in `nav-items.php`:

```php
// Link principale (moduli, admin)
navLink($path, $label, $icon, $currentPath, $exact = false)

// Sub-link (dentro accordion)
navSubLink($path, $label, $icon, $currentPath, $exact = false)
```

**Parametro `$exact`:**
- `true` = highlight solo se URL corrisponde esattamente (usare per Dashboard)
- `false` = highlight se URL inizia con il path (default, per sezioni)

---

## Views dei Moduli

### DO: Usare tutto lo spazio content

```php
<!-- Corretto: space-y-6 diretto -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Page Title</h1>
            <p class="text-slate-500">Description</p>
        </div>
        <div class="flex gap-3">
            <!-- Actions -->
        </div>
    </div>

    <!-- Quick Stats (header banner, NOT sidebar) -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4">...</div>
        <div class="bg-white rounded-xl p-4">...</div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-2xl p-6">
        ...
    </div>
</div>
```

### DON'T: Wrapper con sidebar

```php
<!-- SBAGLIATO: Non fare questo! -->
<div class="flex min-h-screen -mt-6 -mx-6">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="flex-1 p-6">
        ...
    </div>
</div>
```

---

## Checklist Nuovo Modulo con Sub-Entità

- [ ] Creare routes con pattern `/modulo/entity/{id}/section`
- [ ] Aggiungere rilevamento entità in `nav-items.php`
- [ ] Aggiungere blocco accordion in `nav-items.php`
- [ ] Views usano `<div class="space-y-6">` senza wrapper sidebar
- [ ] Quick stats in header/banner, non in sidebar
- [ ] Dashboard usa `exact: true` per highlight
- [ ] Separatori per categorie di navigazione
- [ ] Settings in fondo con border-top

---

## Componenti UI Standard

### Card Stats

```php
<div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center">
            <i data-lucide="icon-name" class="w-6 h-6 text-primary-600 dark:text-primary-400"></i>
        </div>
        <div>
            <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($value) ?></p>
            <p class="text-sm text-slate-500"><?= __('Label') ?></p>
        </div>
    </div>
</div>
```

### Page Header con Actions

```php
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= __('Page Title') ?></h1>
        <p class="mt-1 text-slate-500 dark:text-slate-400"><?= __('Description') ?></p>
    </div>
    <div class="flex items-center gap-3">
        <a href="..." class="btn-secondary">Action 1</a>
        <button class="btn-primary">Action 2</button>
    </div>
</div>
```

### Data Table

```php
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full data-table">
            <thead>
                <tr>
                    <th>Column 1</th>
                    <th class="text-center w-24">Column 2</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>...</td>
                    <td class="text-center">...</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

---

## Alpine.js Notes

- **NON usare** `x-collapse` (richiede plugin separato)
- **Usare** `x-show` con `x-transition` per expand/collapse
- L'accordion si espande automaticamente quando `$entityId` è presente

```php
x-data="{ expanded: <?= $entityId ? 'true' : 'false' ?> }"
```

---

## Esempio: Keyword Research Module

```
Keyword Research ▼
  └── "My Project" ▼
        ├── Research Guidata
        ├── Architettura Sito
        ├── ─── separator ───
        └── Impostazioni
  Quick Check (sempre visibile, no progetto)
```

**Nota:** Quick Check non richiede un progetto ed è sempre visibile nell'accordion, anche fuori dal contesto progetto.

---

## Regole Consistenza View (Project-Scoped)

Quando una view è dentro un contesto progetto (`/modulo/projects/{id}/sezione`), **tutti i link interni** devono mantenere il contesto progetto.

### 1. Link Project-Scoped (MAI usare percorsi legacy)

```php
// ✅ CORRETTO - Project-aware
<?php $baseUrl = '/ai-content/projects/' . $projectId; ?>
<a href="<?= url($baseUrl . '/articles/' . $article['id']) ?>">

// ❌ SBAGLIATO - Percorso legacy perde contesto progetto
<a href="<?= url('/ai-content/articles/' . $article['id']) ?>">
```

### 2. Paginazione con Contesto Progetto

```php
// ✅ CORRETTO - Paginazione project-aware
<?php $paginationBase = !empty($projectId)
    ? '/modulo/projects/' . $projectId . '/sezione'
    : '/modulo/sezione'; ?>
<a href="<?= url($paginationBase . '?page=' . ($page - 1)) ?>">

// ❌ SBAGLIATO - Perde contesto al cambio pagina
<a href="<?= url('/modulo/sezione?page=' . ($page - 1)) ?>">
```

### 3. Bottoni CTA nel project-nav.php

I bottoni azione (CTA) nell'header del progetto devono navigare con `<a>`, **NON** usare `CustomEvent` per azioni cross-pagina.

```php
// ✅ CORRETTO - Link diretto alla pagina giusta
<a href="<?= url($basePath . '/keywords?add=1') ?>" class="btn-primary">
    Nuova Keyword
</a>

// ❌ SBAGLIATO - CustomEvent funziona SOLO se il listener è nella pagina corrente
<button onclick="window.dispatchEvent(new CustomEvent('open-add-keyword'))">
    Nuova Keyword
</button>
```

**Quando usare CustomEvent:** Solo per comunicazione tra componenti **nella stessa pagina** (es. header → form nella stessa view).

**Quando usare link `<a>`:** Per navigazione tra pagine diverse. Usare `?param=1` nel query string per triggerare azioni al caricamento (es. aprire un modale).

### 4. Valori Dinamici (MAI hardcoded)

```php
// ✅ CORRETTO - Valori da controller/config
<p class="text-2xl font-bold"><?= number_format($creditCosts['serp'] ?? 3, 0) ?></p>

// ❌ SBAGLIATO - Valore hardcoded nella view
<p class="text-2xl font-bold">3</p>
```

I costi crediti, limiti, e configurazioni devono essere passati dal controller usando `Credits::getCost()` o `ModuleLoader::getSetting()`.

### 5. Query Param per Auto-Azioni

Per aprire modali o triggerare azioni al caricamento pagina tramite link:

```php
// Nel link (project-nav.php o altra pagina)
<a href="<?= url($basePath . '/keywords?add=1') ?>">Nuova Keyword</a>

// Nella pagina target (keywords/index.php) con Alpine.js x-init
<div x-data="keywordsManager()"
     x-init="if (new URLSearchParams(window.location.search).get('add')) showAddModal = true">
```

---

## File di Riferimento

- **Sidebar principale:** `shared/views/components/nav-items.php`
- **Esempio modulo con progetto:** `modules/internal-links/`, `modules/keyword-research/`
- **Template modulo:** `modules/_template/`
- **Reference view consistency:** `modules/ai-content/` (fix Feb 2026)
