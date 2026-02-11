<div class="space-y-6" x-data="jobsManager()">
    <!-- Breadcrumbs -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="<?= url('/ai-content') ?>" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    AI Content
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-2 text-slate-900 dark:text-white font-medium">Gestione Job</span>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gestione Job</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Monitora e gestisci i job di elaborazione articoli</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <!-- Cancella bloccati -->
            <button @click="cancelStuck()"
                    :disabled="actionLoading"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-400 transition-colors disabled:opacity-50">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Cancella Bloccati
            </button>

            <!-- Pulisci vecchi -->
            <button @click="showCleanupModal = true"
                    :disabled="actionLoading"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Pulisci Storico
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Totale</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($stats['running'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">In Esecuzione</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= number_format($stats['pending'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">In Attesa</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($stats['completed'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Completati</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= number_format($stats['errors'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Errori</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-500 dark:text-slate-400"><?= number_format($stats['cancelled'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Cancellati</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <!-- Filtro Stato -->
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Stato</label>
                <select name="status" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="">Tutti gli stati</option>
                    <option value="running" <?= ($statusFilter ?? '') === 'running' ? 'selected' : '' ?>>In Esecuzione</option>
                    <option value="pending" <?= ($statusFilter ?? '') === 'pending' ? 'selected' : '' ?>>In Attesa</option>
                    <option value="completed" <?= ($statusFilter ?? '') === 'completed' ? 'selected' : '' ?>>Completati</option>
                    <option value="error" <?= ($statusFilter ?? '') === 'error' ? 'selected' : '' ?>>Errori</option>
                    <option value="cancelled" <?= ($statusFilter ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancellati</option>
                </select>
            </div>

            <!-- Filtro Progetto -->
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Progetto</label>
                <select name="project" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="">Tutti i progetti</option>
                    <?php foreach ($projects as $proj): ?>
                    <option value="<?= $proj['id'] ?>" <?= ($projectFilter ?? '') == $proj['id'] ? 'selected' : '' ?>>
                        <?= e($proj['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Reset Filtri -->
            <?php if (!empty($statusFilter) || !empty($projectFilter)): ?>
            <a href="<?= url('/ai-content/jobs') ?>" class="px-3 py-2 text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                Reset filtri
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Jobs Table -->
    <?php if (empty($jobs)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessun job trovato</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            <?php if (!empty($statusFilter) || !empty($projectFilter)): ?>
            Prova a modificare i filtri di ricerca.
            <?php else: ?>
            I job verranno visualizzati qui quando avvierai l'elaborazione degli articoli.
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Progetto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dettagli</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($jobs as $job): ?>
                    <?php
                        // Status badge
                        $statusConfig = [
                            'pending' => ['label' => 'In Attesa', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'],
                            'running' => ['label' => 'In Esecuzione', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'],
                            'completed' => ['label' => 'Completato', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'],
                            'error' => ['label' => 'Errore', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'],
                            'cancelled' => ['label' => 'Cancellato', 'class' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'],
                        ];
                        $status = $statusConfig[$job['status']] ?? $statusConfig['pending'];

                        // Step labels
                        $stepLabels = [
                            'pending' => 'In attesa',
                            'serp' => 'Estrazione SERP',
                            'scraping' => 'Scraping fonti',
                            'brief' => 'Generazione brief',
                            'article' => 'Generazione articolo',
                            'saving' => 'Salvataggio',
                            'done' => 'Completato',
                        ];
                        $stepLabel = $stepLabels[$job['current_step']] ?? $job['current_step'];
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3 text-sm font-mono text-slate-600 dark:text-slate-400">
                            #<?= $job['id'] ?>
                        </td>
                        <td class="px-4 py-3">
                            <a href="<?= url('/ai-content/projects/' . $job['project_id']) ?>" class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
                                <?= e($job['project_name']) ?>
                            </a>
                            <?php if ($job['project_type'] === 'auto'): ?>
                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">AUTO</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                            <?= $job['type'] === 'cron' ? 'CRON' : 'Manuale' ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $status['class'] ?>">
                                <?php if ($job['status'] === 'running'): ?>
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5 animate-pulse"></span>
                                <?php endif; ?>
                                <?= $status['label'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm">
                                <?php if ($job['status'] === 'running' && $job['current_keyword']): ?>
                                <p class="text-slate-900 dark:text-white font-medium truncate max-w-[200px]" title="<?= e($job['current_keyword']) ?>">
                                    <?= e($job['current_keyword']) ?>
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><?= $stepLabel ?></p>
                                <?php elseif ($job['status'] === 'error' && $job['error_message']): ?>
                                <p class="text-red-600 dark:text-red-400 truncate max-w-[200px]" title="<?= e($job['error_message']) ?>">
                                    <?= e(mb_substr($job['error_message'], 0, 50)) ?><?= mb_strlen($job['error_message']) > 50 ? '...' : '' ?>
                                </p>
                                <?php elseif ($job['keywords_completed'] > 0 || $job['keywords_failed'] > 0): ?>
                                <p class="text-slate-600 dark:text-slate-400">
                                    <span class="text-emerald-600"><?= $job['keywords_completed'] ?></span> completate,
                                    <span class="text-red-600"><?= $job['keywords_failed'] ?></span> fallite
                                </p>
                                <?php else: ?>
                                <p class="text-slate-400 dark:text-slate-500">-</p>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                            <p><?= date('d/m/Y H:i', strtotime($job['created_at'])) ?></p>
                            <?php if ($job['started_at']): ?>
                            <p class="text-xs text-slate-400">Inizio: <?= date('H:i:s', strtotime($job['started_at'])) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <?php if (in_array($job['status'], ['pending', 'running'])): ?>
                                <!-- Cancel -->
                                <button @click="cancelJob(<?= $job['id'] ?>)"
                                        :disabled="actionLoading"
                                        class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors disabled:opacity-50"
                                        title="Cancella">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <?php else: ?>
                                <!-- Delete -->
                                <button @click="deleteJob(<?= $job['id'] ?>)"
                                        :disabled="actionLoading"
                                        class="p-1.5 rounded-lg text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors disabled:opacity-50"
                                        title="Elimina">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cleanup Modal -->
    <div x-show="showCleanupModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showCleanupModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/50" @click="showCleanupModal = false"></div>

            <div x-show="showCleanupModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Pulisci Storico Job</h3>

                <div class="space-y-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Elimina i job completati, in errore o cancellati più vecchi di:
                    </p>

                    <select x-model="cleanupDays" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="1">1 giorno</option>
                        <option value="3">3 giorni</option>
                        <option value="7">7 giorni</option>
                        <option value="14">14 giorni</option>
                        <option value="30">30 giorni</option>
                    </select>

                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                        <p class="text-sm text-amber-700 dark:text-amber-300 flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Questa azione non può essere annullata.</span>
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showCleanupModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Annulla
                    </button>
                    <button type="button"
                            @click="cleanup()"
                            :disabled="actionLoading"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition-colors disabled:opacity-50 inline-flex items-center gap-2">
                        <svg x-show="actionLoading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Elimina
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function jobsManager() {
    return {
        actionLoading: false,
        showCleanupModal: false,
        cleanupDays: '7',

        async cancelJob(id) {
            try {
                await window.ainstein.confirm('Sei sicuro di voler cancellare questo job?', {destructive: true});
            } catch (e) { return; }

            this.actionLoading = true;
            try {
                const response = await fetch(`<?= url('/ai-content/jobs') ?>/${id}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _token: '<?= csrf_token() ?>' })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'success' }
                    }));
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.actionLoading = false;
            }
        },

        async deleteJob(id) {
            try {
                await window.ainstein.confirm('Sei sicuro di voler eliminare questo job dallo storico?', {destructive: true});
            } catch (e) { return; }

            this.actionLoading = true;
            try {
                const response = await fetch(`<?= url('/ai-content/jobs') ?>/${id}/delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _token: '<?= csrf_token() ?>' })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'success' }
                    }));
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.actionLoading = false;
            }
        },

        async cancelStuck() {
            try {
                await window.ainstein.confirm('Cancellare tutti i job bloccati (running da più di 30 minuti)?', {destructive: true});
            } catch (e) { return; }

            this.actionLoading = true;
            try {
                const response = await fetch('<?= url('/ai-content/jobs/cancel-stuck') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _token: '<?= csrf_token() ?>', minutes: 30 })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'success' }
                    }));
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.actionLoading = false;
            }
        },

        async cleanup() {
            this.actionLoading = true;
            try {
                const response = await fetch('<?= url('/ai-content/jobs/cleanup') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _token: '<?= csrf_token() ?>', days: this.cleanupDays })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'success' }
                    }));
                    this.showCleanupModal = false;
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.actionLoading = false;
            }
        }
    }
}
</script>
