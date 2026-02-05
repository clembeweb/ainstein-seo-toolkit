<?php
$currentPage = 'issues';
include __DIR__ . '/../partials/project-nav.php';
?>

<div class="space-y-6" x-data="issueManager()">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Issues</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= $stats['open'] ?> aperti su <?= $stats['total'] ?> totali
            </p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Critici</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= $stats['critical'] ?></p>
                </div>
                <div class="h-10 w-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Avvisi</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= $stats['warning'] ?></p>
                </div>
                <div class="h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Risolti</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= $stats['fixed'] ?></p>
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
                    <p class="text-sm text-slate-500 dark:text-slate-400">Ignorati</p>
                    <p class="text-2xl font-bold text-slate-600 dark:text-slate-400"><?= $stats['ignored'] ?></p>
                </div>
                <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Severita</label>
                <select name="severity" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    <option value="">Tutte</option>
                    <option value="critical" <?= ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' ?>>Critico</option>
                    <option value="warning" <?= ($filters['severity'] ?? '') === 'warning' ? 'selected' : '' ?>>Avviso</option>
                    <option value="notice" <?= ($filters['severity'] ?? '') === 'notice' ? 'selected' : '' ?>>Info</option>
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categoria</label>
                <select name="category" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    <option value="">Tutte</option>
                    <option value="meta" <?= ($filters['category'] ?? '') === 'meta' ? 'selected' : '' ?>>Meta Tags</option>
                    <option value="content" <?= ($filters['category'] ?? '') === 'content' ? 'selected' : '' ?>>Contenuto</option>
                    <option value="images" <?= ($filters['category'] ?? '') === 'images' ? 'selected' : '' ?>>Immagini</option>
                    <option value="links" <?= ($filters['category'] ?? '') === 'links' ? 'selected' : '' ?>>Link</option>
                    <option value="technical" <?= ($filters['category'] ?? '') === 'technical' ? 'selected' : '' ?>>Tecnico</option>
                    <option value="performance" <?= ($filters['category'] ?? '') === 'performance' ? 'selected' : '' ?>>Performance</option>
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Stato</label>
                <select name="status" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    <option value="open" <?= ($filters['status'] ?? 'open') === 'open' ? 'selected' : '' ?>>Aperti</option>
                    <option value="fixed" <?= ($filters['status'] ?? '') === 'fixed' ? 'selected' : '' ?>>Risolti</option>
                    <option value="ignored" <?= ($filters['status'] ?? '') === 'ignored' ? 'selected' : '' ?>>Ignorati</option>
                    <option value="" <?= ($filters['status'] ?? 'open') === '' ? 'selected' : '' ?>>Tutti</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Issues List -->
    <?php if (empty($issuesGrouped)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun issue trovato</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">Non ci sono issues con i filtri selezionati.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php
        $categoryLabels = [
            'meta' => 'Meta Tags',
            'content' => 'Contenuto',
            'images' => 'Immagini',
            'links' => 'Link',
            'technical' => 'Tecnico',
            'performance' => 'Performance',
        ];
        foreach ($issuesGrouped as $issue):
            $severityColor = match($issue['severity']) {
                'critical' => 'border-l-red-500 bg-red-50 dark:bg-red-900/10',
                'warning' => 'border-l-amber-500 bg-amber-50 dark:bg-amber-900/10',
                default => 'border-l-blue-500 bg-blue-50 dark:bg-blue-900/10',
            };
            $badgeColor = match($issue['severity']) {
                'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                default => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
            };
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 border-l-4 <?= $severityColor ?> overflow-hidden">
            <div class="p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $badgeColor ?>">
                                <?= $issue['severity'] === 'critical' ? 'Critico' : ($issue['severity'] === 'warning' ? 'Avviso' : 'Info') ?>
                            </span>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                                <?= $categoryLabels[$issue['category']] ?? ucfirst($issue['category']) ?>
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                <?= $issue['occurrences'] ?> pagine
                            </span>
                        </div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($issue['message']) ?></p>

                        <!-- Affected Pages Preview -->
                        <?php if (!empty($issue['pages'])): ?>
                        <div class="mt-3 space-y-1">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Pagine interessate:</p>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($issue['pages'] as $pg): ?>
                                <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/pages/' . $pg['id']) ?>"
                                   class="inline-flex items-center px-2 py-1 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors truncate max-w-[200px]">
                                    <?= e(parse_url($pg['url'], PHP_URL_PATH) ?: '/') ?>
                                </a>
                                <?php endforeach; ?>
                                <?php if ($issue['has_more']): ?>
                                <span class="inline-flex items-center px-2 py-1 text-xs text-slate-500 dark:text-slate-400">
                                    +<?= $issue['occurrences'] - 5 ?> altre
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="flex-shrink-0 flex items-center gap-2">
                        <button @click="markAs('<?= e($issue['check_name']) ?>', 'fixed')"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-300 dark:hover:bg-emerald-900 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Risolto
                        </button>
                        <button @click="markAs('<?= e($issue['check_name']) ?>', 'ignored')"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                            Ignora
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Toast Notification -->
    <div x-show="toast.show" x-transition
         class="fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg"
         :class="toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'">
        <p x-text="toast.message"></p>
    </div>
</div>

<script>
function issueManager() {
    const projectId = <?= (int) $project['id'] ?>;
    const csrfToken = '<?= csrf_token() ?>';
    const baseUrl = '<?= rtrim(url(''), '/') ?>';

    return {
        toast: { show: false, message: '', type: 'success' },

        async markAs(checkName, status) {
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('check_name', checkName);
            formData.append('status', status);

            try {
                const resp = await fetch(`${baseUrl}/seo-onpage/project/${projectId}/issues/bulk`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await resp.json();

                if (data.success) {
                    this.showToast(data.message, 'success');
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
