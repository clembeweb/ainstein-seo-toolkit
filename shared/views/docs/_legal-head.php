<?php
/**
 * Intestazione condivisa pagine legali (docs/privacy, docs/terms, docs/cookies)
 * Variabili attese: $legalTitle, $legalSubtitle
 *
 * DATI TITOLARE: modificare qui una volta sola.
 */
$legalOwner = [
    'name'    => 'Beweb Agency S.r.l.s.',
    'address' => 'Via Tommaso Sorrentino 26, 80054 Gragnano (NA)',
    'vat'     => '09334871218',
    'email'   => 'supporto@ainstein.it',
];
$legalUpdated = '9 ottobre 2026';
?>
<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-8">
    <a href="<?= url('/docs') ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Documentazione</a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-slate-900 dark:text-white font-medium"><?= htmlspecialchars($legalTitle) ?></span>
</nav>

<!-- Header -->
<div class="mb-10">
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4"><?= htmlspecialchars($legalTitle) ?></h1>
    <p class="text-lg text-slate-600 dark:text-slate-400 leading-relaxed"><?= htmlspecialchars($legalSubtitle) ?></p>
    <p class="mt-3 text-sm text-slate-500 dark:text-slate-500">Ultimo aggiornamento: <?= $legalUpdated ?></p>
</div>

<!-- Navigazione tra le pagine legali -->
<div class="flex flex-wrap gap-2 mb-10">
    <?php foreach (['privacy' => 'Privacy Policy', 'terms' => 'Termini di Servizio', 'cookies' => 'Cookie Policy'] as $slug => $label): ?>
        <a href="<?= url('/docs/' . $slug) ?>"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium border transition-colors <?= ($currentPage ?? '') === $slug ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-200 dark:border-primary-800 text-primary-700 dark:text-primary-300' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' ?>">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>
