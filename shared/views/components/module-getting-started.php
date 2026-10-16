<?php
/**
 * Riquadro "Come iniziare" nelle pagine d'ingresso dei moduli (al posto del vecchio hero banner,
 * decisione Clemente 2026-10-16). Stesso stile del "Come si usa" di AI Rank (seo-tracking/views/partials/guide.php).
 *
 * Params:
 * @param string $color    indigo|purple|blue|emerald|amber
 * @param string $subtitle una riga sotto il titolo
 * @param array  $steps    [['title' => '...', 'text' => '...'], ...] (3 passi)
 * @param bool   $open     aperto all'arrivo (di solito: l'utente non ha ancora progetti nel modulo)
 * @param string $ctaText  facoltativo, bottone sotto i passi (es. "Crea il primo progetto")
 * @param string $ctaUrl   facoltativo
 * @param string $tour     facoltativo, valore di data-tour sul riquadro (per i tour guidati)
 */
$color = $color ?? 'blue';
$steps = $steps ?? [];
$open = !empty($open);
$_gsColors = [
    'indigo'  => ['icon' => 'text-indigo-600 dark:text-indigo-400', 'num' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300', 'btn' => 'bg-indigo-600 hover:bg-indigo-700'],
    'purple'  => ['icon' => 'text-purple-600 dark:text-purple-400', 'num' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300', 'btn' => 'bg-purple-600 hover:bg-purple-700'],
    'blue'    => ['icon' => 'text-blue-600 dark:text-blue-400', 'num' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300', 'btn' => 'bg-blue-600 hover:bg-blue-700'],
    'emerald' => ['icon' => 'text-emerald-600 dark:text-emerald-400', 'num' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300', 'btn' => 'bg-emerald-600 hover:bg-emerald-700'],
    'amber'   => ['icon' => 'text-amber-600 dark:text-amber-400', 'num' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300', 'btn' => 'bg-amber-600 hover:bg-amber-700'],
];
$_gs = $_gsColors[$color] ?? $_gsColors['blue'];
?>
<div x-data="{ open: <?= $open ? 'true' : 'false' ?> }" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700"<?= !empty($tour) ? ' data-tour="' . e($tour) . '"' : '' ?>>
    <button type="button" @click="open = !open" class="w-full px-5 py-4 flex items-center justify-between gap-4 text-left">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0 <?= $_gs['icon'] ?>" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Come iniziare</h2>
                <?php if (!empty($subtitle)): ?>
                <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($subtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <svg class="w-5 h-5 flex-shrink-0 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
    </button>
    <div x-show="open" x-cloak class="px-5 pb-5">
        <ol class="grid grid-cols-1 md:grid-cols-<?= min(count($steps), 4) ?> gap-4">
            <?php foreach ($steps as $i => $step): ?>
            <li class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full flex-shrink-0 text-xs font-bold <?= $_gs['num'] ?>"><?= $i + 1 ?></span>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white"><?= e($step['title']) ?></h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300"><?= e($step['text']) ?></p>
            </li>
            <?php endforeach; ?>
        </ol>
        <?php if (!empty($ctaText) && !empty($ctaUrl)): ?>
        <a href="<?= e($ctaUrl) ?>" class="mt-4 inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition-colors <?= $_gs['btn'] ?>">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <?= e($ctaText) ?>
        </a>
        <?php endif; ?>
    </div>
</div>
