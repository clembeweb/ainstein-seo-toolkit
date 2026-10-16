<?php
// Nomi dei moduli come nel menu laterale (li decide Clemente da Admin), colori come nel progetto
$_pjNames = [];
foreach ($modules ?? [] as $_m) {
    $_pjNames[$_m['slug']] = $_m['name'];
}
$_pjChip = [
    'indigo' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    'purple' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    'blue' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    'amber' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    'rose' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    'cyan' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
    'orange' => 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
];
$_pjToolNames = array_values(array_filter(array_map(
    fn($slug) => $_pjNames[$slug] ?? null,
    \Core\ModuleLoader::ORDER
)));
$_pjCount = count($projects ?? []) + count($sharedProjects ?? []);

$_pjTimeAgo = function (?string $date): ?string {
    if (!$date) return null;
    $diff = time() - strtotime($date);
    if ($diff < 60) return 'adesso';
    if ($diff < 3600) return floor($diff / 60) . ' min fa';
    if ($diff < 86400) return floor($diff / 3600) . ' ore fa';
    if ($diff < 604800) return floor($diff / 86400) . ' giorni fa';
    return date('d/m/Y', strtotime($date));
};

// Card di un progetto (propri e condivisi)
$_pjCard = function (array $project, bool $shared) use ($moduleConfig, $_pjNames, $_pjChip, $_pjTimeAgo) {
    $slugs = \Core\ModuleLoader::sortByOrder(array_flip($project['active_module_slugs'] ?? []), null);
    $lastActivity = $_pjTimeAgo($project['last_module_activity'] ?? null);
    ?>
    <a href="<?= url('/projects/' . $project['id']) ?>" class="group flex flex-col bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md hover:border-primary-300 dark:hover:border-primary-600 transition">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= e($project['color'] ?? '#3B82F6') ?>"></span>
                <div class="min-w-0">
                    <h3 class="font-semibold text-slate-900 dark:text-white truncate group-hover:text-primary-600 dark:group-hover:text-primary-400"><?= e($project['name']) ?></h3>
                    <?php if (!empty($project['domain'])): ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400 truncate"><?= e($project['domain']) ?></p>
                    <?php endif; ?>
                    <?php if ($shared): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400">di <?= e($project['owner_name'] ?? $project['owner_email']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($shared): ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium flex-shrink-0 <?= $project['access_role'] === 'editor' ? 'bg-blue-500/20 text-blue-500 dark:text-blue-400' : 'bg-slate-500/20 text-slate-500 dark:text-slate-400' ?>">
                <?= $project['access_role'] === 'editor' ? 'Editor' : 'Visualizzatore' ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="mt-4 flex-1">
            <?php if (!empty($slugs)): ?>
            <div class="flex flex-wrap gap-1.5">
                <?php foreach (array_keys($slugs) as $slug):
                    $cfg = $moduleConfig[$slug] ?? null;
                    if (!$cfg) continue;
                ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium <?= $_pjChip[$cfg['color']] ?? $_pjChip['blue'] ?>">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= e($cfg['icon']) ?>"/></svg>
                    <?= e($_pjNames[$slug] ?? $cfg['label']) ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-amber-600 dark:text-amber-400">Nessuno strumento attivo: apri il progetto e attivane uno.</p>
            <?php endif; ?>
        </div>

        <div class="mt-4 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <span><?= $lastActivity ? 'Ultima attività: ' . e($lastActivity) : '' ?></span>
            <span class="font-medium text-primary-600 dark:text-primary-400 text-sm">Apri &rarr;</span>
        </div>
    </a>
    <?php
};
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Progetti</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Un progetto è un sito: il tuo o quello di un cliente. Dentro attivi gli strumenti che ti servono.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center whitespace-nowrap px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo progetto
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

    <!-- Come funziona: aperto per chi ha 0-1 progetti, poi richiudibile -->
    <div x-data="{ open: <?= $_pjCount <= 1 ? 'true' : 'false' ?> }" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <button type="button" @click="open = !open" class="w-full px-5 py-4 flex items-center justify-between gap-4 text-left">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Come funzionano i progetti</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Un progetto per ogni sito, e dentro tutti gli strumenti che usi su quel sito.</p>
                </div>
            </div>
            <svg class="w-5 h-5 text-slate-400 transition-transform flex-shrink-0" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
        </button>
        <ol x-show="open" x-cloak class="px-5 pb-5 grid grid-cols-1 md:grid-cols-3 gap-4">
            <li class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300 text-xs font-bold">1</span>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Crea il progetto</h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300">Un progetto per ogni sito: il tuo o quello di un cliente. Bastano il nome e il dominio.</p>
            </li>
            <li class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300 text-xs font-bold">2</span>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Attiva gli strumenti</h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300">Apri il progetto e accendi quelli che ti servono<?= $_pjToolNames ? ' (' . e(implode(', ', $_pjToolNames)) . ')' : '' ?>. Attivarli è gratis: i crediti si usano solo quando lanci un'analisi.</p>
            </li>
            <li class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300 text-xs font-bold">3</span>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Condividilo, se serve</h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300">Col bottone "Condividi" nel progetto inviti un collaboratore o il cliente e scegli cosa può vedere. Facoltativo.</p>
            </li>
        </ol>
    </div>

    <?php if (empty($projects) && empty($sharedProjects)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-dashed border-slate-300 dark:border-slate-600 p-12 text-center">
        <svg class="mx-auto w-14 h-14 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-slate-700 dark:text-slate-300">Ancora nessun progetto</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">Parti dal sito su cui vuoi lavorare. Ci vuole un minuto.</p>
        <a href="<?= url('/projects/create') ?>" class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crea il primo progetto
        </a>
    </div>
    <?php else: ?>

    <!-- I miei progetti -->
    <?php if (!empty($projects)): ?>
    <div>
        <?php if (!empty($sharedProjects)): ?>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">I miei progetti</h2>
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($projects as $project) { $_pjCard($project, false); } ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Condivisi con me -->
    <?php if (!empty($sharedProjects)): ?>
    <div class="mt-10">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Condivisi con me</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Progetti di altri utenti a cui ti hanno invitato. I crediti li usa chi ha creato il progetto.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($sharedProjects as $project) { $_pjCard($project, true); } ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>
