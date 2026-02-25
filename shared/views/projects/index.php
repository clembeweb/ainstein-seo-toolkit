<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Progetti</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gestisci i tuoi progetti e i moduli collegati</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <!-- Banner inviti in sospeso -->
    <?php if (!empty($pendingInvitations)): ?>
    <div class="space-y-3">
        <?php foreach ($pendingInvitations as $invite): ?>
        <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                     style="background-color: <?= e($invite['project_color'] ?? '#3B82F6') ?>20; color: <?= e($invite['project_color'] ?? '#3B82F6') ?>">
                    <?= strtoupper(substr($invite['project_name'], 0, 1)) ?>
                </div>
                <div>
                    <p class="text-sm text-slate-900 dark:text-white">
                        <strong><?= e($invite['invited_by_name'] ?? $invite['invited_by_email']) ?></strong>
                        ti ha invitato come <strong><?= $invite['role'] === 'editor' ? 'Editor' : 'Visualizzatore' ?></strong>
                        nel progetto <strong><?= e($invite['project_name']) ?></strong>
                    </p>
                    <?php if (!empty($invite['project_domain'])): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= e($invite['project_domain']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex gap-2 flex-shrink-0">
                <?php if (($invite['invite_type'] ?? 'internal') === 'email'): ?>
                    <!-- Invito email: accetta tramite token -->
                    <a href="<?= url('/invite/accept?token=' . urlencode($invite['token'])) ?>" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm rounded-lg transition inline-block">
                        Accetta
                    </a>
                <?php else: ?>
                    <!-- Invito interno: accetta/rifiuta tramite POST -->
                    <form method="POST" action="<?= url('/invite/' . $invite['id'] . '/accept') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm rounded-lg transition">
                            Accetta
                        </button>
                    </form>
                    <form method="POST" action="<?= url('/invite/' . $invite['id'] . '/decline') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-3 py-1.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg transition">
                            Rifiuta
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($projects) && empty($sharedProjects)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto">
            <svg class="mx-auto w-16 h-16 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-slate-700 dark:text-slate-300">Nessun progetto</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                Crea il tuo primo progetto per organizzare il lavoro sui tuoi clienti. Ogni progetto raggruppa i moduli attivi in un unico hub.
            </p>
            <a href="<?= url('/projects/create') ?>" class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>
    <?php else: ?>

    <!-- I miei progetti -->
    <?php if (!empty($projects)): ?>
    <?php if (!empty($sharedProjects)): ?>
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">I miei progetti</h2>
    <?php endif; ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($projects as $project): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= htmlspecialchars($project['color'] ?? '#3B82F6') ?>"></span>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($project['name']) ?></h3>
                        <?php if (!empty($project['domain'])): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400 truncate"><?= htmlspecialchars($project['domain']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?= url('/projects/' . $project['id'] . '/settings') ?>" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors flex-shrink-0" title="Impostazioni">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </a>
            </div>

            <div class="mt-3 flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <?= (int)($project['active_modules_count'] ?? 0) ?> moduli attivi
                </span>
                <?php if (!empty($project['last_module_activity'])): ?>
                <span class="inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php
                    $lastActivity = strtotime($project['last_module_activity']);
                    $diff = time() - $lastActivity;
                    if ($diff < 60) {
                        $timeAgo = 'Adesso';
                    } elseif ($diff < 3600) {
                        $timeAgo = floor($diff / 60) . ' min fa';
                    } elseif ($diff < 86400) {
                        $timeAgo = floor($diff / 3600) . ' ore fa';
                    } elseif ($diff < 604800) {
                        $timeAgo = floor($diff / 86400) . ' giorni fa';
                    } else {
                        $timeAgo = date('d/m/Y', $lastActivity);
                    }
                    ?>
                    <?= $timeAgo ?>
                </span>
                <?php endif; ?>
            </div>

            <a href="<?= url('/projects/' . $project['id']) ?>" class="mt-3 inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
                Apri progetto &rarr;
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Condivisi con me -->
    <?php if (!empty($sharedProjects)): ?>
    <div class="mt-10">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Condivisi con me</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($sharedProjects as $project): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= htmlspecialchars($project['color'] ?? '#3B82F6') ?>"></span>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($project['name']) ?></h3>
                            <?php if (!empty($project['domain'])): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400 truncate"><?= htmlspecialchars($project['domain']) ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-slate-500 dark:text-slate-400">di <?= e($project['owner_name'] ?? $project['owner_email']) ?></p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0
                                 <?= $project['access_role'] === 'editor' ? 'bg-blue-500/20 text-blue-400' : 'bg-slate-500/20 text-slate-400' ?>">
                        <?= $project['access_role'] === 'editor' ? 'Editor' : 'Visualizzatore' ?>
                    </span>
                </div>

                <div class="mt-3 flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <?= (int)($project['active_modules_count'] ?? 0) ?> moduli attivi
                    </span>
                    <?php if (!empty($project['last_module_activity'])): ?>
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <?php
                        $lastActivity = strtotime($project['last_module_activity']);
                        $diff = time() - $lastActivity;
                        if ($diff < 60) {
                            $timeAgo = 'Adesso';
                        } elseif ($diff < 3600) {
                            $timeAgo = floor($diff / 60) . ' min fa';
                        } elseif ($diff < 86400) {
                            $timeAgo = floor($diff / 3600) . ' ore fa';
                        } elseif ($diff < 604800) {
                            $timeAgo = floor($diff / 86400) . ' giorni fa';
                        } else {
                            $timeAgo = date('d/m/Y', $lastActivity);
                        }
                        ?>
                        <?= $timeAgo ?>
                    </span>
                    <?php endif; ?>
                </div>

                <a href="<?= url('/projects/' . $project['id']) ?>" class="mt-3 inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
                    Apri progetto &rarr;
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <!-- Siti WordPress — vista centralizzata -->
    <?php if (!empty($allWpSites)): ?>
    <div class="mt-10">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Siti WordPress</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Tutti i tuoi siti WordPress collegati alla piattaforma</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="dark:bg-slate-700/50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sito</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Progetto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ultimo test</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($allWpSites as $wpSite):
                        $wpDomain = parse_url($wpSite['url'], PHP_URL_HOST) ?: $wpSite['url'];
                        $testStatus = $wpSite['last_test_status'] ?? null;
                        $testAt = $wpSite['last_test_at'] ?? null;
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <?php
                                    if ($testStatus === 'success') {
                                        $dotColor = 'bg-emerald-400';
                                    } elseif ($testStatus === 'error') {
                                        $dotColor = 'bg-red-400';
                                    } else {
                                        $dotColor = 'bg-slate-400';
                                    }
                                ?>
                                <span class="w-2 h-2 rounded-full <?= $dotColor ?> flex-shrink-0"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate"><?= htmlspecialchars($wpSite['name']) ?></p>
                                    <a href="<?= htmlspecialchars($wpSite['url']) ?>" target="_blank" class="text-xs text-indigo-500 dark:text-indigo-400 hover:underline"><?= htmlspecialchars($wpDomain) ?></a>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($wpSite['project_name']): ?>
                            <a href="<?= url('/projects/' . $wpSite['global_project_id']) ?>" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"><?= htmlspecialchars($wpSite['project_name']) ?></a>
                            <?php else: ?>
                            <span class="text-xs text-slate-400 dark:text-slate-500 italic">Non collegato</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $wpSite['is_active'] ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-400' ?>">
                                <?= $wpSite['is_active'] ? 'Attivo' : 'Disattivato' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                            <?php if ($testAt): ?>
                                <?php
                                    $tDiff = time() - strtotime($testAt);
                                    if ($tDiff < 60) $tAgo = 'Adesso';
                                    elseif ($tDiff < 3600) $tAgo = floor($tDiff / 60) . ' min fa';
                                    elseif ($tDiff < 86400) $tAgo = floor($tDiff / 3600) . ' ore fa';
                                    elseif ($tDiff < 604800) $tAgo = floor($tDiff / 86400) . 'g fa';
                                    else $tAgo = date('d/m/Y', strtotime($testAt));
                                ?>
                                <span class="<?= $testStatus === 'success' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400' ?>"><?= $testStatus === 'success' ? 'OK' : 'Errore' ?></span>
                                <span class="ml-1"><?= $tAgo ?></span>
                            <?php else: ?>
                                <span class="italic">Mai testato</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="<?= url('/wp-sites/delete') ?>"
                                  onsubmit="return confirm('Eliminare il sito <?= htmlspecialchars(addslashes($wpSite['name'] ?: $wpDomain)) ?>? Gli articoli già pubblicati non saranno modificati, ma la configurazione auto-publish perderà il riferimento.')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="site_id" value="<?= (int) $wpSite['id'] ?>">
                                <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Elimina sito">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
