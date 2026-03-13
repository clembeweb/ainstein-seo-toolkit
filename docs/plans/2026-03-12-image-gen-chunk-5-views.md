# Chunk 5: Views (Navigation, List, Import, Preview, Settings)

> **Parent plan:** [Plan Index](./2026-03-12-image-generation-plan-index.md)
> **Design spec:** [Design Spec](./2026-03-12-content-creator-image-generation-design.md) — Section 8
> **Depends on:** Chunk 4 (controllers + routes)

---

## Task 17: Update project-nav.php (Segmento Toggle)

**Files:**
- Modify: `modules/content-creator/views/partials/project-nav.php`

**Design:** Add a segmented control (Contenuti / Immagini) ABOVE the tab row. When "Immagini" is active, change the tab labels.

- [ ] **Step 1: Read current project-nav.php**

Read full file to understand current structure.

- [ ] **Step 2: Add image mode detection + segmented toggle**

At the TOP of the file (after `$projectId` and `$basePath` setup), add:

```php
// Detect image mode from URL or variable
$imageMode = $imageMode ?? false;
$imagePath = "/content-creator/projects/{$projectId}/images";

// Tab definitions per mode
if ($imageMode) {
    $tabs = [
        'images' => ['path' => '/images', 'label' => 'Dashboard', 'icon' => 'photo'],
        'images-import' => ['path' => '/images/import', 'label' => 'Importa Prodotti', 'icon' => 'cloud-upload'],
        'settings' => ['path' => '/settings', 'label' => 'Impostazioni', 'icon' => 'cog'],
    ];
} else {
    // Keep existing tabs unchanged
    $tabs = [
        'dashboard' => ['path' => '', 'label' => 'Dashboard', 'icon' => 'chart-bar'],
        'import' => ['path' => '/import', 'label' => 'Import URL', 'icon' => 'cloud-upload'],
        'results' => ['path' => '/results', 'label' => 'Risultati', 'icon' => 'document-text'],
        'settings' => ['path' => '/settings', 'label' => 'Impostazioni', 'icon' => 'cog'],
    ];
}
```

Add segmented toggle HTML BETWEEN the project header and the tab row:

```html
<!-- Segmento Toggle: Contenuti / Immagini -->
<div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-700 rounded-lg p-1 mb-4 w-fit">
    <a href="<?= url("/content-creator/projects/{$projectId}") ?>"
       class="px-4 py-1.5 text-sm font-medium rounded-md transition-all <?= !$imageMode ? 'bg-white dark:bg-slate-600 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' ?>">
        <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Contenuti
    </a>
    <a href="<?= url("/content-creator/projects/{$projectId}/images") ?>"
       class="px-4 py-1.5 text-sm font-medium rounded-md transition-all <?= $imageMode ? 'bg-white dark:bg-slate-600 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' ?>">
        <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Immagini
    </a>
</div>
```

- [ ] **Step 3: Update isActiveTabCc() to handle image pages**

Add 'images' and 'images-import' to the alias mapping:

```php
function isActiveTabCc(string $tab, string $currentPage): bool {
    $aliases = [
        'dashboard' => ['dashboard', 'show'],
        'import' => ['import'],
        'results' => ['results'],
        'settings' => ['settings'],
        'images' => ['images'],
        'images-import' => ['images-import'],
    ];
    return in_array($currentPage, $aliases[$tab] ?? [$tab]);
}
```

- [ ] **Step 4: Verify syntax**

```bash
php -l modules/content-creator/views/partials/project-nav.php
```

- [ ] **Step 5: Commit**

```bash
git add modules/content-creator/views/partials/project-nav.php
git commit -m "feat(content-creator): add Contenuti/Immagini segmented toggle to project-nav"
```

---

## Task 18: images/index.php (Image List)

**Files:**
- Create: `modules/content-creator/views/images/index.php`

**Reference:** `views/results/index.php` for table pattern + bulk select + SSE integration

- [ ] **Step 1: Write the image list view**

This is a large view file. Key sections:
1. Include project-nav.php partial
2. Stats row (per status)
3. Toolbar (Import, Genera, Export ZIP, Push CMS)
4. Bulk select bar
5. Table with thumbnails
6. Pagination
7. SSE JavaScript for generation/push
8. Empty state

The view should be written following the exact patterns from `results/index.php`:
- Alpine.js component `x-data="imageManager()"`
- Bulk checkbox pattern from `table-helpers.php`
- SSE event handling identical to existing pattern
- `response.ok` check on all fetch calls (GR #24)
- Orange color scheme (content-creator module color)

**Key HTML structure:**

```php
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="imageManager()">

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
        <!-- stat cards per status: Tutti, In attesa, Foto acquisita, Generate, Approvate, Pubblicate, Errori -->
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url("/content-creator/projects/{$project['id']}/images/import") ?>"
           class="inline-flex items-center px-3 py-2 rounded-lg bg-orange-600 text-white text-sm font-medium hover:bg-orange-700">
            <!-- upload icon --> Importa Prodotti
        </a>
        <button @click="startGenerate()" :disabled="generating || pushing"
                class="inline-flex items-center px-3 py-2 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 disabled:opacity-50">
            <!-- sparkles icon --> Genera Immagini (<?= $stats['source_acquired'] ?>)
        </button>
        <?php if ($approvedVariantCount > 0): ?>
        <a href="<?= url("/content-creator/projects/{$project['id']}/images/export/zip") ?>"
           class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700">
            <!-- download icon --> Esporta ZIP
        </a>
        <?php endif; ?>
        <?php if ($connectorSupportsImages && $approvedVariantCount > 0): ?>
        <button @click="startPush()" :disabled="generating || pushing"
                class="inline-flex items-center px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 disabled:opacity-50">
            <!-- cloud-upload icon --> Push CMS
        </button>
        <?php endif; ?>
    </div>

    <!-- Bulk Select Bar -->
    <div x-show="selectedIds.length > 0" x-cloak
         class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-3 flex items-center gap-3">
        <span class="text-sm font-medium" x-text="selectedIds.length + ' selezionati'"></span>
        <button @click="bulkApprove()" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Approva</button>
        <button @click="bulkDelete()" class="text-sm text-red-600 hover:text-red-700 font-medium">Elimina</button>
    </div>

    <!-- SSE Progress Bar (shown during generation) -->
    <div x-show="generating" x-cloak class="bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded-xl p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-violet-700 dark:text-violet-300">Generazione in corso...</span>
            <button @click="cancelJob()" class="text-sm text-red-600 hover:text-red-700">Annulla</button>
        </div>
        <div class="w-full bg-violet-200 dark:bg-violet-800 rounded-full h-2">
            <div class="bg-violet-600 h-2 rounded-full transition-all" :style="'width:' + progress + '%'"></div>
        </div>
        <p class="text-xs text-violet-600 dark:text-violet-400 mt-1" x-text="progressText"></p>
    </div>

    <!-- Table -->
    <?php if (empty($images)): ?>
        <!-- Empty state -->
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-orange-300 dark:text-orange-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-white">Nessun prodotto importato</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Importa i tuoi prodotti per iniziare a generare immagini</p>
            <a href="<?= url("/content-creator/projects/{$project['id']}/images/import") ?>"
               class="mt-4 inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700">
                Importa Prodotti
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 w-10"><!-- checkbox --></th>
                        <th class="px-4 py-3 w-16 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Foto</th>
                        <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Prodotto</th>
                        <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">SKU</th>
                        <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Categoria</th>
                        <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center">Varianti</th>
                        <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Stato</th>
                        <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($images as $img): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50"
                        :class="{
                            'bg-violet-50/50 dark:bg-violet-900/10': rowStates[<?= $img['id'] ?>] === 'generating',
                            'bg-emerald-50/50 dark:bg-emerald-900/10': rowStates[<?= $img['id'] ?>] === 'done',
                            'bg-red-50/50 dark:bg-red-900/10': rowStates[<?= $img['id'] ?>] === 'error'
                        }">
                        <td class="px-4 py-3">
                            <input type="checkbox" value="<?= $img['id'] ?>"
                                   @change="toggleSelect(<?= $img['id'] ?>)"
                                   class="rounded border-slate-300 dark:border-slate-600">
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($img['source_image_path'])): ?>
                            <img src="<?= url('/content-creator/images/serve/source/' . basename($img['source_image_path'])) ?>"
                                 class="w-12 h-12 object-cover rounded-lg" loading="lazy"
                                 alt="<?= e($img['product_name']) ?>">
                            <?php else: ?>
                            <div class="w-12 h-12 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <a href="<?= url("/content-creator/projects/{$project['id']}/images/{$img['id']}") ?>"
                               class="text-sm font-medium text-slate-900 dark:text-white hover:text-orange-600 dark:hover:text-orange-400">
                                <?= e($img['product_name']) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                            <?= e($img['sku'] ?: '—') ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                            $catColors = ['fashion' => 'purple', 'home' => 'blue', 'custom' => 'slate'];
                            $catLabels = ['fashion' => 'Fashion', 'home' => 'Home', 'custom' => 'Custom'];
                            $catColor = $catColors[$img['category']] ?? 'slate';
                            $catLabel = $catLabels[$img['category']] ?? $img['category'];
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $catColor ?>-100 text-<?= $catColor ?>-800 dark:bg-<?= $catColor ?>-900/30 dark:text-<?= $catColor ?>-400">
                                <?= $catLabel ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php
                            $vc = (int) ($img['variant_count'] ?? 0);
                            $ac = (int) ($img['approved_count'] ?? 0);
                            ?>
                            <?php if ($ac > 0): ?>
                                <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400"><?= $ac ?>/<?= $vc ?> ✓</span>
                            <?php elseif ($vc > 0): ?>
                                <span class="text-sm text-slate-500 dark:text-slate-400">0/<?= $vc ?></span>
                            <?php else: ?>
                                <span class="text-sm text-slate-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                            $statusConfig = [
                                'pending' => ['label' => 'In attesa', 'color' => 'slate'],
                                'source_acquired' => ['label' => 'Foto OK', 'color' => 'blue'],
                                'generated' => ['label' => 'Generata', 'color' => 'violet'],
                                'approved' => ['label' => 'Approvata', 'color' => 'emerald'],
                                'published' => ['label' => 'Pubblicata', 'color' => 'teal'],
                                'error' => ['label' => 'Errore', 'color' => 'red'],
                            ];
                            $sc = $statusConfig[$img['status']] ?? $statusConfig['pending'];
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $sc['color'] ?>-100 text-<?= $sc['color'] ?>-800 dark:bg-<?= $sc['color'] ?>-900/30 dark:text-<?= $sc['color'] ?>-400">
                                <?= $sc['label'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= url("/content-creator/projects/{$project['id']}/images/{$img['id']}") ?>"
                               class="text-sm text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300 font-medium">
                                Dettagli
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php View::partial('components/table-pagination', [
            'pagination' => $pagination,
            'baseUrl' => url("/content-creator/projects/{$project['id']}/images"),
            'filters' => $filters,
        ]); ?>
    <?php endif; ?>

</div>
```

**Alpine.js component** (at bottom of file inside `<script>`):

```javascript
function imageManager() {
    return {
        selectedIds: [],
        generating: false,
        pushing: false,
        progress: 0,
        progressText: '',
        jobId: null,
        eventSource: null,
        rowStates: {},

        toggleSelect(id) { /* standard checkbox toggle pattern */ },

        async startGenerate() {
            // POST start-generate-job → get job_id → SSE generate-stream
            // Pattern identical to resultsManager().startGenerate() in results/index.php
            // Use violet progress bar color
        },

        async startPush() {
            // POST start-push-job → get job_id → SSE push-stream
            // Use teal progress bar color
        },

        connectSSE(streamUrl, type) {
            this.eventSource = new EventSource(streamUrl);
            this.eventSource.addEventListener('started', (e) => { /* ... */ });
            this.eventSource.addEventListener('progress', (e) => { /* ... */ });
            this.eventSource.addEventListener('item_completed', (e) => {
                const d = JSON.parse(e.data);
                this.rowStates[d.image_id] = 'done';
                this.progress = d.percent;
                this.progressText = `${d.completed}/${d.total} completati`;
            });
            this.eventSource.addEventListener('item_error', (e) => {
                const d = JSON.parse(e.data);
                this.rowStates[d.image_id] = 'error';
            });
            this.eventSource.addEventListener('completed', (e) => {
                this.eventSource.close();
                this.generating = false;
                this.pushing = false;
                location.reload();
            });
            this.eventSource.addEventListener('cancelled', (e) => {
                this.eventSource.close();
                this.generating = false;
                this.pushing = false;
                location.reload();
            });
        },

        async cancelJob() {
            if (!this.jobId) return;
            const fd = new FormData();
            fd.append('job_id', this.jobId);
            const resp = await fetch('<?= url("/content-creator/projects/{$project['id']}/images/cancel-job") ?>', { method: 'POST', body: fd });
            if (!resp.ok) throw new Error('Errore');
        },

        async bulkApprove() { /* FormData + _csrf_token + response.ok check */ },
        async bulkDelete() { /* FormData + _csrf_token + response.ok check */ },
    }
}
```

**Note:** The full Alpine.js component is intentionally summarized here. The implementor should copy the exact patterns from `views/results/index.php` and `views/projects/show.php`, adapting:
- Variable names (scraping→generating, urls→images)
- SSE endpoint URLs
- Progress bar colors (violet for generate, teal for push)
- CSRF token field name (`_csrf_token`)
- `response.ok` check before `response.json()` (GR #24)

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/content-creator/views/images/index.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/views/images/index.php
git commit -m "feat(content-creator): add images/index.php with table, SSE, bulk ops, empty state"
```

---

## Task 19: images/import.php (Import View)

**Files:**
- Create: `modules/content-creator/views/images/import.php`

**Design spec 8.4:** Independent import (NOT reusing shared import-tabs.php). Three tabs: CMS, CSV, Manuale.

- [ ] **Step 1: Write import view**

Key sections:
1. project-nav.php partial
2. Alpine.js `x-data="importImageWizard()"` — 3 tabs
3. Tab CMS: global category selector + product table with thumbnails + checkboxes
4. Tab CSV: file upload + column mapping dropdowns
5. Tab Manuale: single file upload + product name + SKU + category

```php
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="importImageWizard()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Importa Prodotti</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Importa prodotti con le loro immagini per generare varianti AI</p>
        </div>
        <a href="<?= url("/content-creator/projects/{$project['id']}/images") ?>"
           class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
            ← Torna alla lista
        </a>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-4">
            <?php if ($connectorSupportsImages): ?>
            <button @click="activeTab = 'cms'" :class="activeTab === 'cms' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors">
                CMS
            </button>
            <?php endif; ?>
            <button @click="activeTab = 'csv'" :class="activeTab === 'csv' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors">
                CSV
            </button>
            <button @click="activeTab = 'manual'" :class="activeTab === 'manual' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors">
                Upload Manuale
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <!-- CMS Tab, CSV Tab, Manual Tab — see design spec 8.4 for details -->
    <!-- Implementation follows exact patterns from the design spec -->
</div>
```

The implementor should:
- Copy the tab CSS pattern from existing import-tabs.php
- Use `fetch('/content-creator/projects/{id}/images/fetch-cms')` for CMS product loading
- Global category selector with `setAllCategories()` Alpine method
- CSV column mapping with configurable indices
- Manual upload with `enctype="multipart/form-data"`, `accept="image/png,image/jpeg,image/webp"`, max 10MB validation
- All fetch calls MUST have `response.ok` check (GR #24)

- [ ] **Step 2: Verify syntax + commit**

```bash
php -l modules/content-creator/views/images/import.php
git add modules/content-creator/views/images/import.php
git commit -m "feat(content-creator): add images/import.php with CMS/CSV/manual tabs"
```

---

## Task 20: images/preview.php (Variant Preview + Lightbox)

**Files:**
- Create: `modules/content-creator/views/images/preview.php`

**Design spec 8.5:** Side-by-side layout (source | variants grid). Lightbox. Approve/reject per-variant. Preset override. Regenerate.

- [ ] **Step 1: Write preview view**

Key layout (from spec wireframe):
```
┌──────────────────────────────────────────────────────┐
│ ← Torna alla lista                                    │
│ HEADER: Nome prodotto | SKU | Status | Categoria      │
│                                                        │
│ ┌─────────────┐  ┌──────────────────────────────────┐ │
│ │ FOTO SOURCE │  │  Griglia varianti (2-3 col)      │ │
│ │ (max 300px) │  │  [Var1 ✓✗] [Var2 ✓✗] [Var3 ✓✗] │ │
│ └─────────────┘  └──────────────────────────────────┘ │
│                                                        │
│ PRESET OVERRIDE: [Cat▼] [Genere▼] [Sfondo▼] [Stile▼] │
│ Prompt custom: [_____________________________________] │
│ [Rigenera tutte]  [Scarica approvate]                  │
└──────────────────────────────────────────────────────┘
```

Alpine.js component:
```javascript
x-data="{
    lightboxOpen: false,
    lightboxSrc: '',
    regenerating: false,

    openLightbox(src) { this.lightboxSrc = src; this.lightboxOpen = true; },
    closeLightbox() { this.lightboxOpen = false; },

    async approveVariant(variantId) {
        const fd = new FormData();
        fd.append('_csrf_token', csrfToken);
        fd.append('variant_id', variantId);
        const resp = await fetch(approveUrl, { method: 'POST', body: fd });
        if (!resp.ok) throw new Error('Errore');
        location.reload();
    },

    async rejectVariant(variantId) { /* same pattern */ },

    async regenerate(variantId = null) {
        const fd = new FormData();
        fd.append('_csrf_token', csrfToken);
        if (variantId) fd.append('variant_id', variantId);
        const resp = await fetch(regenerateUrl, { method: 'POST', body: fd });
        if (!resp.ok) throw new Error('Errore');
        location.reload();
    },

    async saveOverride() {
        // Save per-item generation_settings override
    }
}"
```

Lightbox overlay:
```html
<!-- Lightbox -->
<div x-show="lightboxOpen" x-cloak @click.self="closeLightbox()" @keydown.escape.window="closeLightbox()"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/80">
    <img :src="lightboxSrc" class="max-w-5xl max-h-[90vh] object-contain rounded-lg shadow-2xl">
    <button @click="closeLightbox()" class="absolute top-4 right-4 text-white hover:text-slate-300">
        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
```

- [ ] **Step 2: Verify syntax + commit**

```bash
php -l modules/content-creator/views/images/preview.php
git add modules/content-creator/views/images/preview.php
git commit -m "feat(content-creator): add images/preview.php with variant grid, lightbox, approve/reject"
```

---

## Task 21: Update settings.php (Tab System + Immagini Tab)

**Files:**
- Modify: `modules/content-creator/views/projects/settings.php`

**Design spec 8.6:** Restructure with Alpine tab system: `[Generale] [AI] [Connettore] [Immagini]`

- [ ] **Step 1: Read current settings.php**

- [ ] **Step 2: Wrap existing content in tab system**

Add Alpine.js `x-data="{ settingsTab: 'general' }"` to the root container.

Add tab navigation:
```html
<div class="border-b border-slate-200 dark:border-slate-700 mb-6">
    <nav class="flex gap-4">
        <button @click="settingsTab = 'general'" :class="settingsTab === 'general' ? 'border-orange-500 text-orange-600' : 'border-transparent text-slate-500'"
                class="pb-3 px-1 border-b-2 text-sm font-medium">Generale</button>
        <button @click="settingsTab = 'ai'" :class="settingsTab === 'ai' ? 'border-orange-500 text-orange-600' : 'border-transparent text-slate-500'"
                class="pb-3 px-1 border-b-2 text-sm font-medium">AI</button>
        <button @click="settingsTab = 'connector'" :class="settingsTab === 'connector' ? 'border-orange-500 text-orange-600' : 'border-transparent text-slate-500'"
                class="pb-3 px-1 border-b-2 text-sm font-medium">Connettore</button>
        <button @click="settingsTab = 'images'" :class="settingsTab === 'images' ? 'border-orange-500 text-orange-600' : 'border-transparent text-slate-500'"
                class="pb-3 px-1 border-b-2 text-sm font-medium">Immagini</button>
    </nav>
</div>
```

Wrap existing form sections in `x-show="settingsTab === 'general'"`, etc.

Add "Immagini" tab content with:
- Scene type (fashion/home)
- Gender (woman/man/neutral)
- Background (studio_white/urban/lifestyle/nature)
- Environment (living_room/kitchen/bedroom/office/outdoor)
- Photo style (professional/editorial/minimal)
- Variants count (1-4)
- Custom prompt (textarea)
- Push mode (add_as_gallery/replace_main/add)

All values saved to `cc_projects.ai_settings.image_defaults` JSON.

- [ ] **Step 3: Verify syntax + commit**

```bash
php -l modules/content-creator/views/projects/settings.php
git add modules/content-creator/views/projects/settings.php
git commit -m "feat(content-creator): restructure settings with Alpine tabs, add Immagini tab"
```

---

## Task 22: Update ProjectController for Image Settings Save

**Files:**
- Modify: `modules/content-creator/controllers/ProjectController.php`

- [ ] **Step 1: Read the updateSettings method**

- [ ] **Step 2: Add image_defaults handling**

In the `updateSettings()` method, where `ai_settings` JSON is built, add:

```php
// Image generation defaults
$imageDefaults = [
    'scene_type' => $_POST['image_scene_type'] ?? 'fashion',
    'gender' => $_POST['image_gender'] ?? 'woman',
    'background' => $_POST['image_background'] ?? 'studio_white',
    'environment' => $_POST['image_environment'] ?? 'living_room',
    'photo_style' => $_POST['image_photo_style'] ?? 'professional',
    'variants_count' => max(1, min(4, (int) ($_POST['image_variants_count'] ?? 3))),
    'custom_prompt' => trim($_POST['image_custom_prompt'] ?? ''),
    'push_mode' => $_POST['image_push_mode'] ?? 'add_as_gallery',
];

$aiSettings['image_defaults'] = $imageDefaults;
```

- [ ] **Step 3: Verify syntax + commit**

```bash
php -l modules/content-creator/controllers/ProjectController.php
git add modules/content-creator/controllers/ProjectController.php
git commit -m "feat(content-creator): handle image_defaults in project settings save"
```

---

## Chunk 5 Complete

**Verify all views:**
```bash
php -l modules/content-creator/views/partials/project-nav.php
php -l modules/content-creator/views/images/index.php
php -l modules/content-creator/views/images/import.php
php -l modules/content-creator/views/images/preview.php
php -l modules/content-creator/views/projects/settings.php
php -l modules/content-creator/controllers/ProjectController.php
```
