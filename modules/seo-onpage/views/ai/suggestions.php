<?php
$currentPage = 'ai';
include __DIR__ . '/../partials/project-nav.php';
?>

<div class="space-y-6" x-data="aiManager()">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Suggerimenti AI</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Ottimizzazioni generate con intelligenza artificiale
            </p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Da applicare</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= $stats['pending'] ?></p>
                </div>
                <div class="h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Applicati</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= $stats['applied'] ?></p>
                </div>
                <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Rifiutati</p>
                    <p class="text-2xl font-bold text-slate-600 dark:text-slate-400"><?= $stats['rejected'] ?></p>
                </div>
                <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar: Pagine e Generazione -->
        <div class="lg:col-span-1 space-y-4">
            <!-- Generate New -->
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800 p-4">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <h3 class="font-semibold text-purple-900 dark:text-purple-100">Genera Suggerimenti</h3>
                </div>
                <p class="text-sm text-purple-700 dark:text-purple-300 mb-3">
                    Costo: <?= $aiCost ?> crediti per pagina
                </p>
                <p class="text-xs text-purple-600 dark:text-purple-400 mb-3">
                    Saldo: <?= $userCredits ?> crediti
                </p>

                <?php if ($selectedPage): ?>
                <button @click="generate(<?= $selectedPage['id'] ?>)"
                        :disabled="generating"
                        class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-purple-600 text-white font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors">
                    <svg x-show="generating" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="generating ? 'Generazione...' : 'Genera per questa pagina'"></span>
                </button>
                <?php else: ?>
                <p class="text-xs text-purple-600 dark:text-purple-400">Seleziona una pagina per generare suggerimenti</p>
                <?php endif; ?>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Filtri</h3>
                <form method="GET" class="space-y-3">
                    <?php if ($selectedPage): ?>
                    <input type="hidden" name="page_id" value="<?= $selectedPage['id'] ?>">
                    <?php endif; ?>
                    <div>
                        <label class="block text-sm text-slate-500 dark:text-slate-400 mb-1">Stato</label>
                        <select name="status" onchange="this.form.submit()"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                            <option value="pending" <?= ($filters['status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Da applicare</option>
                            <option value="applied" <?= ($filters['status'] ?? '') === 'applied' ? 'selected' : '' ?>>Applicati</option>
                            <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rifiutati</option>
                            <option value="" <?= ($filters['status'] ?? 'pending') === '' ? 'selected' : '' ?>>Tutti</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-500 dark:text-slate-400 mb-1">Priorita</label>
                        <select name="priority" onchange="this.form.submit()"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                            <option value="">Tutte</option>
                            <option value="high" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>Alta</option>
                            <option value="medium" <?= ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Media</option>
                            <option value="low" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Bassa</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Pages with Suggestions -->
            <?php if (!empty($pagesWithSuggestions)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Pagine con suggerimenti</h3>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-700 max-h-64 overflow-y-auto">
                    <?php foreach ($pagesWithSuggestions as $pg): ?>
                    <a href="?page_id=<?= $pg['id'] ?>&status=<?= $filters['status'] ?? 'pending' ?>"
                       class="block p-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors <?= ($selectedPage && $selectedPage['id'] == $pg['id']) ? 'bg-purple-50 dark:bg-purple-900/20' : '' ?>">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-900 dark:text-white truncate"><?= e(parse_url($pg['url'], PHP_URL_PATH) ?: '/') ?></span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                <?= $pg['suggestions_count'] ?>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content: Suggestions List -->
        <div class="lg:col-span-3">
            <?php if ($selectedPage): ?>
            <!-- Selected Page Header -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white"><?= e($selectedPage['title'] ?? 'Pagina senza titolo') ?></h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($selectedPage['url']) ?></p>
                    </div>
                    <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/ai') ?>" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        Mostra tutte
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($suggestions)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
                <div class="mx-auto h-16 w-16 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center mb-4">
                    <svg class="h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun suggerimento</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <?php if ($selectedPage): ?>
                    Genera suggerimenti AI per questa pagina.
                    <?php else: ?>
                    Seleziona una pagina analizzata e genera suggerimenti AI.
                    <?php endif; ?>
                </p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php
                $typeLabels = [
                    'title' => 'Meta Title',
                    'description' => 'Meta Description',
                    'h1' => 'Heading H1',
                    'content' => 'Contenuto',
                    'technical' => 'Tecnico',
                ];
                $typeIcons = [
                    'title' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>',
                    'description' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>',
                    'h1' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>',
                    'content' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                    'technical' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                ];
                $priorityColors = [
                    'high' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                    'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                    'low' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                ];
                $priorityLabels = [
                    'high' => 'Alta',
                    'medium' => 'Media',
                    'low' => 'Bassa',
                ];

                foreach ($suggestions as $s):
                    $type = $s['suggestion_type'] ?? 'content';
                    $priority = $s['priority'] ?? 'medium';
                    $isPending = ($s['status'] ?? 'pending') === 'pending';
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <?= $typeIcons[$type] ?? $typeIcons['content'] ?>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <span class="font-medium text-slate-900 dark:text-white"><?= $typeLabels[$type] ?? ucfirst($type) ?></span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $priorityColors[$priority] ?? $priorityColors['medium'] ?>">
                                            <?= $priorityLabels[$priority] ?? ucfirst($priority) ?>
                                        </span>
                                        <?php if (!$isPending): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $s['status'] === 'applied' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' ?>">
                                            <?= $s['status'] === 'applied' ? 'Applicato' : 'Rifiutato' ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($s['url'])): ?>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-2 truncate">
                                        <?= e(parse_url($s['url'], PHP_URL_PATH) ?: '/') ?>
                                    </p>
                                    <?php endif; ?>

                                    <?php if (!empty($s['current_value'])): ?>
                                    <div class="mb-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                        <p class="text-xs font-medium text-red-600 dark:text-red-400 mb-1">Valore Attuale</p>
                                        <p class="text-sm text-red-800 dark:text-red-200"><?= e($s['current_value']) ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($s['suggested_value'])): ?>
                                    <div class="mb-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                        <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400 mb-1">Suggerimento</p>
                                        <p class="text-sm text-emerald-800 dark:text-emerald-200"><?= e($s['suggested_value']) ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($s['reasoning'])): ?>
                                    <p class="text-sm text-slate-600 dark:text-slate-300"><?= e($s['reasoning']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($isPending): ?>
                            <div class="flex-shrink-0 flex flex-col gap-2">
                                <button @click="markApplied(<?= $s['id'] ?>)"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-300 dark:hover:bg-emerald-900 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Applicato
                                </button>
                                <button @click="reject(<?= $s['id'] ?>)"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Rifiuta
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="toast.show" x-transition
         class="fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg"
         :class="toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'">
        <p x-text="toast.message"></p>
    </div>
</div>

<script>
function aiManager() {
    const projectId = <?= (int) $project['id'] ?>;
    const csrfToken = '<?= csrf_token() ?>';
    const baseUrl = '<?= rtrim(url(''), '/') ?>';

    return {
        generating: false,
        toast: { show: false, message: '', type: 'success' },

        async generate(pageId) {
            this.generating = true;

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('page_id', pageId);

            try {
                const resp = await fetch(`${baseUrl}/seo-onpage/project/${projectId}/ai/generate`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await resp.json();

                if (data.success) {
                    this.showToast(`${data.suggestions_count} suggerimenti generati`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showToast(data.error || 'Errore generazione', 'error');
                }
            } catch (e) {
                this.showToast('Errore di connessione', 'error');
            }

            this.generating = false;
        },

        async markApplied(id) {
            const formData = new FormData();
            formData.append('_token', csrfToken);

            try {
                const resp = await fetch(`${baseUrl}/seo-onpage/project/${projectId}/ai/${id}/apply`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await resp.json();

                if (data.success) {
                    this.showToast('Suggerimento marcato come applicato', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showToast(data.error || 'Errore', 'error');
                }
            } catch (e) {
                this.showToast('Errore di connessione', 'error');
            }
        },

        async reject(id) {
            const formData = new FormData();
            formData.append('_token', csrfToken);

            try {
                const resp = await fetch(`${baseUrl}/seo-onpage/project/${projectId}/ai/${id}/reject`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await resp.json();

                if (data.success) {
                    this.showToast('Suggerimento rifiutato', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showToast(data.error || 'Errore', 'error');
                }
            } catch (e) {
                this.showToast('Errore di connessione', 'error');
            }
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        }
    };
}
</script>
