<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="imageManager()">

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
        <?php
        $statItems = [
            ['key' => 'total', 'label' => 'Tutti', 'color' => 'slate', 'filter' => ''],
            ['key' => 'pending', 'label' => 'In attesa', 'color' => 'slate', 'filter' => 'pending'],
            ['key' => 'source_acquired', 'label' => 'Foto OK', 'color' => 'blue', 'filter' => 'source_acquired'],
            ['key' => 'generated', 'label' => 'Generate', 'color' => 'violet', 'filter' => 'generated'],
            ['key' => 'approved', 'label' => 'Approvate', 'color' => 'emerald', 'filter' => 'approved'],
            ['key' => 'published', 'label' => 'Pubblicate', 'color' => 'teal', 'filter' => 'published'],
            ['key' => 'error', 'label' => 'Errori', 'color' => 'red', 'filter' => 'error'],
        ];
        foreach ($statItems as $si):
            $count = $stats[$si['key']] ?? 0;
            $isActive = ($filters['status'] ?? '') === $si['filter'];
            $href = url("/content-creator/projects/{$project['id']}/images" . ($si['filter'] ? "?status={$si['filter']}" : ''));
        ?>
        <a href="<?= $href ?>"
           class="block p-3 rounded-xl border transition-all <?= $isActive ? "border-{$si['color']}-300 dark:border-{$si['color']}-700 bg-{$si['color']}-50 dark:bg-{$si['color']}-900/20" : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600' ?>">
            <div class="text-2xl font-bold text-slate-900 dark:text-white"><?= $count ?></div>
            <div class="text-xs text-slate-500 dark:text-slate-400"><?= $si['label'] ?></div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url("/content-creator/projects/{$project['id']}/images/import") ?>"
           class="inline-flex items-center px-3 py-2 rounded-lg bg-orange-600 text-white text-sm font-medium hover:bg-orange-700 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Importa Prodotti
        </a>
        <?php if (($stats['source_acquired'] ?? 0) > 0): ?>
        <button @click="startGenerate()" :disabled="generating || pushing"
                class="inline-flex items-center px-3 py-2 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 disabled:opacity-50 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
            </svg>
            Genera Immagini (<?= $stats['source_acquired'] ?>)
        </button>
        <?php endif; ?>
        <?php if ($approvedVariantCount > 0): ?>
        <a href="<?= url("/content-creator/projects/{$project['id']}/images/export/zip") ?>"
           class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Esporta ZIP (<?= $approvedVariantCount ?>)
        </a>
        <?php endif; ?>
        <?php if ($connectorSupportsImages && $approvedVariantCount > 0): ?>
        <button @click="startPush()" :disabled="generating || pushing"
                class="inline-flex items-center px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 disabled:opacity-50 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Push CMS
        </button>
        <?php endif; ?>
    </div>

    <!-- Bulk Select Bar -->
    <div x-show="selectedIds.length > 0" x-cloak
         class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-3 flex items-center gap-3">
        <span class="text-sm font-medium text-orange-700 dark:text-orange-300" x-text="selectedIds.length + ' selezionati'"></span>
        <button @click="bulkApprove()" class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-medium">Approva</button>
        <button @click="bulkDelete()" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 font-medium">Elimina</button>
    </div>

    <!-- SSE Progress Bar -->
    <div x-show="generating || pushing" x-cloak>
        <div :class="pushing ? 'bg-teal-50 dark:bg-teal-900/20 border-teal-200 dark:border-teal-800' : 'bg-violet-50 dark:bg-violet-900/20 border-violet-200 dark:border-violet-800'"
             class="border rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium" :class="pushing ? 'text-teal-700 dark:text-teal-300' : 'text-violet-700 dark:text-violet-300'"
                      x-text="pushing ? 'Push CMS in corso...' : 'Generazione in corso...'"></span>
                <button @click="cancelJob()" class="text-sm text-red-600 hover:text-red-700 font-medium">Annulla</button>
            </div>
            <div class="w-full rounded-full h-2" :class="pushing ? 'bg-teal-200 dark:bg-teal-800' : 'bg-violet-200 dark:bg-violet-800'">
                <div class="h-2 rounded-full transition-all" :class="pushing ? 'bg-teal-600' : 'bg-violet-600'" :style="'width:' + progress + '%'"></div>
            </div>
            <p class="text-xs mt-1" :class="pushing ? 'text-teal-600 dark:text-teal-400' : 'text-violet-600 dark:text-violet-400'" x-text="progressText"></p>
        </div>
    </div>

    <!-- Table or Empty State -->
    <?php if (empty($images)): ?>
    <div class="text-center py-12">
        <svg class="w-16 h-16 mx-auto text-orange-300 dark:text-orange-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-white">Nessun prodotto importato</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Importa i tuoi prodotti per iniziare a generare immagini</p>
        <a href="<?= url("/content-creator/projects/{$project['id']}/images/import") ?>"
           class="mt-4 inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700 transition-colors">
            Importa Prodotti
        </a>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" @change="toggleAll($event)" class="rounded border-slate-300 dark:border-slate-600">
                    </th>
                    <th class="px-4 py-3 w-16 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Foto</th>
                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Prodotto</th>
                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left hidden md:table-cell">SKU</th>
                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left hidden lg:table-cell">Categoria</th>
                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center">Varianti</th>
                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Stato</th>
                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($images as $img): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <td class="px-4 py-3">
                        <input type="checkbox" value="<?= $img['id'] ?>"
                               @change="toggleSelect(<?= $img['id'] ?>)"
                               :checked="selectedIds.includes(<?= $img['id'] ?>)"
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
                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400 hidden md:table-cell">
                        <?= e($img['sku'] ?: '—') ?>
                    </td>
                    <td class="px-4 py-3 hidden lg:table-cell">
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
                            <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400"><?= $ac ?>/<?= $vc ?></span>
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
    <?php \Core\View::partial('components/table-pagination', [
        'pagination' => $pagination,
        'baseUrl' => url("/content-creator/projects/{$project['id']}/images"),
        'filters' => $filters,
    ]); ?>
    <?php endif; ?>

</div>

<script>
const csrfToken = '<?= csrf_token() ?>';
const projectId = <?= $project['id'] ?>;
const baseUrl = '<?= url("/content-creator/projects/{$project['id']}/images") ?>';

function imageManager() {
    return {
        selectedIds: [],
        generating: false,
        pushing: false,
        progress: 0,
        progressText: '',
        jobId: null,
        eventSource: null,

        toggleSelect(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx > -1) {
                this.selectedIds.splice(idx, 1);
            } else {
                this.selectedIds.push(id);
            }
        },

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = <?= json_encode(array_column($images, 'id')) ?>.map(Number);
            } else {
                this.selectedIds = [];
            }
        },

        async startGenerate() {
            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                const resp = await fetch(baseUrl + '/start-generate-job', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
                const data = await resp.json();
                if (data.error) { alert(data.message); return; }
                this.jobId = data.job_id;
                this.generating = true;
                this.progress = 0;
                this.progressText = 'Avvio generazione...';
                this.connectSSE(baseUrl + '/generate-stream?job_id=' + data.job_id);
            } catch (e) {
                alert('Errore: ' + e.message);
            }
        },

        async startPush() {
            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                const resp = await fetch(baseUrl + '/start-push-job', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
                const data = await resp.json();
                if (data.error) { alert(data.message); return; }
                this.jobId = data.job_id;
                this.pushing = true;
                this.progress = 0;
                this.progressText = 'Avvio push CMS...';
                this.connectSSE(baseUrl + '/push-stream?job_id=' + data.job_id);
            } catch (e) {
                alert('Errore: ' + e.message);
            }
        },

        connectSSE(streamUrl) {
            this.eventSource = new EventSource(streamUrl);

            this.eventSource.addEventListener('started', (e) => {
                const d = JSON.parse(e.data);
                this.progressText = `0/${d.total} completati`;
            });

            this.eventSource.addEventListener('progress', (e) => {
                const d = JSON.parse(e.data);
                this.progress = d.percent;
                this.progressText = `${d.completed + d.failed}/${d.total} — ${d.current_item}`;
            });

            this.eventSource.addEventListener('item_completed', (e) => {
                const d = JSON.parse(e.data);
                this.progress = d.percent;
                this.progressText = `${d.completed}/${d.total} completati`;
            });

            this.eventSource.addEventListener('item_error', (e) => {
                const d = JSON.parse(e.data);
                this.progress = d.percent || this.progress;
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

            this.eventSource.onerror = () => {
                this.eventSource.close();
                this.generating = false;
                this.pushing = false;
            };
        },

        async cancelJob() {
            if (!this.jobId) return;
            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                fd.append('job_id', this.jobId);
                const resp = await fetch(baseUrl + '/cancel-job', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
            } catch (e) {
                console.error('Cancel error:', e);
            }
        },

        async bulkApprove() {
            if (!this.selectedIds.length) return;
            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                this.selectedIds.forEach(id => fd.append('image_ids[]', id));
                const resp = await fetch(baseUrl + '/approve-bulk', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
                const data = await resp.json();
                if (data.success) location.reload();
                else alert(data.message || 'Errore');
            } catch (e) {
                alert('Errore: ' + e.message);
            }
        },

        async bulkDelete() {
            if (!this.selectedIds.length) return;
            if (!confirm('Eliminare ' + this.selectedIds.length + ' immagini selezionate?')) return;
            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                this.selectedIds.forEach(id => fd.append('image_ids[]', id));
                const resp = await fetch(baseUrl + '/delete-bulk', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
                const data = await resp.json();
                if (data.success) location.reload();
                else alert(data.message || 'Errore');
            } catch (e) {
                alert('Errore: ' + e.message);
            }
        },
    }
}
</script>
