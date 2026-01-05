<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <?= e($project['name']) ?>
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Storico Analisi</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= count($analyses) ?> analisi salvate per questo progetto
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/landing-urls') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuova Analisi
            </a>
        </div>
    </div>

    <?php if (empty($analyses)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-white">Nessuna analisi</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Non hai ancora eseguito analisi per questo progetto.
            </p>
            <div class="mt-6">
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/landing-urls') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Esegui prima analisi
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Analyses List -->
    <div class="space-y-4">
        <?php foreach ($analyses as $analysis): ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6" x-data="{ showDelete: false }">
            <div class="flex items-start justify-between">
                <!-- Analysis Info -->
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/analyses/' . $analysis['id']) ?>" class="text-lg font-medium text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">
                            <?= e($analysis['name']) ?>
                        </a>
                        <!-- Status Badge -->
                        <?php
                        $statusColors = [
                            'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                            'analyzing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                            'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                            'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                        ];
                        $statusLabels = [
                            'draft' => 'Bozza',
                            'analyzing' => 'In analisi',
                            'completed' => 'Completata',
                            'error' => 'Errore'
                        ];
                        $statusColor = $statusColors[$analysis['status']] ?? $statusColors['draft'];
                        $statusLabel = $statusLabels[$analysis['status']] ?? $analysis['status'];
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                            <?= $statusLabel ?>
                        </span>
                    </div>

                    <!-- Metadata -->
                    <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <?= date('d/m/Y H:i', strtotime($analysis['created_at'])) ?>
                        </span>

                        <?php if ($analysis['status'] === 'completed'): ?>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <?= $analysis['categories_count'] ?> categorie
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                            </svg>
                            <?= $analysis['selected_count'] ?>/<?= $analysis['keywords_count'] ?> keyword selezionate
                        </span>
                        <?php endif; ?>

                        <?php if ($analysis['context_mode'] !== 'manual'): ?>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            Contesto <?= $analysis['context_mode'] === 'auto' ? 'automatico' : 'misto' ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Business Context Preview -->
                    <?php if (!empty($analysis['business_context'])): ?>
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400 line-clamp-2">
                        <?= e(substr($analysis['business_context'], 0, 200)) ?><?= strlen($analysis['business_context']) > 200 ? '...' : '' ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="ml-4 flex items-center gap-2">
                    <?php if ($analysis['status'] === 'completed'): ?>
                    <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/analyses/' . $analysis['id']) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Visualizza
                    </a>
                    <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/analyses/' . $analysis['id'] . '/export') ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-sm font-medium hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export
                    </a>
                    <?php endif; ?>

                    <!-- Delete Button -->
                    <button @click="showDelete = true" class="inline-flex items-center p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Delete Confirmation -->
            <div x-show="showDelete" x-cloak class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <p class="text-sm text-red-700 dark:text-red-300">
                    Eliminare questa analisi? L'operazione non puo essere annullata.
                </p>
                <div class="mt-3 flex gap-2">
                    <button
                        @click="deleteAnalysis(<?= $analysis['id'] ?>)"
                        class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors"
                    >
                        Elimina
                    </button>
                    <button
                        @click="showDelete = false"
                        class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors"
                    >
                        Annulla
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function deleteAnalysis(analysisId) {
    fetch(`/ads-analyzer/projects/<?= $project['id'] ?>/analyses/${analysisId}/delete`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_token=' + document.querySelector('input[name="_token"]')?.value
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Errore durante l\'eliminazione');
        }
    })
    .catch(err => {
        console.error('Delete failed:', err);
        alert('Errore durante l\'eliminazione');
    });
}
</script>

<!-- Hidden CSRF token for AJAX -->
<input type="hidden" name="_token" value="<?= csrf_token() ?>">
