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
</div>
