<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Progetti</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gestisci le tue analisi Google Ads</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/ads-analyzer/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-wrap gap-2">
            <a href="<?= url('/ads-analyzer/projects') ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= !$currentStatus ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' ?>">
                Tutti (<?= $stats['total'] ?? 0 ?>)
            </a>
            <a href="<?= url('/ads-analyzer/projects?status=draft') ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $currentStatus === 'draft' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' ?>">
                Bozze (<?= $stats['drafts'] ?? 0 ?>)
            </a>
            <a href="<?= url('/ads-analyzer/projects?status=completed') ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $currentStatus === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' ?>">
                Completati (<?= $stats['completed'] ?? 0 ?>)
            </a>
            <a href="<?= url('/ads-analyzer/projects?status=archived') ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $currentStatus === 'archived' ? 'bg-slate-200 text-slate-700 dark:bg-slate-600 dark:text-slate-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' ?>">
                Archiviati (<?= $stats['archived'] ?? 0 ?>)
            </a>
        </div>
    </div>

    <!-- Projects List -->
    <?php if (empty($projects)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto</h3>
        <p class="text-slate-500 dark:text-slate-400 mb-6">Inizia creando un nuovo progetto e caricando un export CSV da Google Ads</p>
        <a href="<?= url('/ads-analyzer/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crea Progetto
        </a>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Progetto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ad Groups</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Termini</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Negative</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($projects as $project): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="font-medium text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">
                            <?= e($project['name']) ?>
                        </a>
                        <?php if ($project['description']): ?>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-xs"><?= e($project['description']) ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                        <?= number_format($project['total_ad_groups']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                        <?= number_format($project['total_terms']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                        <?= number_format($project['total_negatives_found']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?php if ($project['status'] === 'completed'): ?>
                            bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300
                            <?php elseif ($project['status'] === 'analyzing'): ?>
                            bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300
                            <?php elseif ($project['status'] === 'archived'): ?>
                            bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400
                            <?php else: ?>
                            bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300
                            <?php endif; ?>
                        ">
                            <?= ucfirst($project['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                        <?= date('d/m/Y H:i', strtotime($project['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="text-slate-400 hover:text-amber-600 dark:hover:text-amber-400" title="Visualizza">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <?php if ($project['status'] === 'completed'): ?>
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/results') ?>" class="text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400" title="Risultati">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </a>
                            <?php endif; ?>
                            <form action="<?= url('/ads-analyzer/projects/' . $project['id'] . '/delete') ?>" method="POST" class="inline" onsubmit="return confirm('Eliminare questo progetto?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-slate-400 hover:text-red-600 dark:hover:text-red-400" title="Elimina">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
