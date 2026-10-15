<?php
/**
 * Riquadro "Come si usa" in cima alla Panoramica di AI Rank: 3 passi che si spuntano da soli
 * (come nel GEO Audit). Aperto finché non c'è stato il primo controllo posizioni.
 * Variabili: $project, $guideState = ['keywords' => int, 'checked' => bool, 'gsc' => bool, 'ga4' => bool]
 */
$basePath = '/seo-tracking/project/' . (int) $project['id'];
$rankCost = rtrim(rtrim(number_format((float) \Core\Credits::getCost('rank_check', 'seo-tracking', 1), 1, ',', ''), '0'), ',');
$guideSteps = [
    [
        'done' => $guideState['keywords'] > 0,
        'title' => 'Aggiungi le keyword',
        'text' => 'Le ricerche su Google che vuoi seguire: scrivile, incolla una lista o importa un CSV. Per ognuna scegli il paese: puoi seguire lo stesso sito in più mercati. Con Search Console collegata le trovi già pronte.',
        'link' => $basePath . '/keywords/add', 'cta' => 'Aggiungi keyword',
    ],
    [
        'done' => $guideState['checked'],
        'title' => 'Controlla le posizioni',
        'text' => 'Nella scheda Keyword clicca "Posizioni": vedi dove sta il sito su Google per ogni ricerca, da computer e da telefono. Costa ' . $rankCost . ' credit' . ($rankCost === '1' ? 'o' : 'i') . ' a keyword e lo storico resta salvato.',
        'link' => $basePath . '/keywords', 'cta' => 'Vai alle keyword',
    ],
    [
        'done' => $guideState['gsc'] && $guideState['ga4'],
        'title' => 'Collega Search Console e Analytics',
        'text' => 'Con i dati veri di Google (clic, impressioni, visite) si accendono i Quick Wins e il Punto della situazione: cosa cresce, cosa cala e da dove partire.'
            . (!$guideState['gsc'] && $guideState['ga4'] ? ' Manca Search Console.' : '') . ($guideState['gsc'] && !$guideState['ga4'] ? ' Manca Analytics.' : ''),
        'link' => $basePath . '/settings', 'cta' => 'Apri le impostazioni',
    ],
];
$guideOpen = !$guideState['checked'];
?>
<div x-data="{ open: <?= $guideOpen ? 'true' : 'false' ?> }" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
    <button type="button" @click="open = !open" class="w-full px-5 py-4 flex items-center justify-between gap-4 text-left">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Come si usa</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">3 passi per sapere dove compare <?= e($project['domain'] ?? $project['name']) ?> su Google e come cambia nel tempo.</p>
            </div>
        </div>
        <svg class="w-5 h-5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
    </button>
    <ol x-show="open" x-cloak class="px-5 pb-5 grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php foreach ($guideSteps as $i => $step): ?>
        <li class="rounded-xl border p-4 <?= $step['done'] ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-800 dark:bg-emerald-900/10' : 'border-slate-200 dark:border-slate-700' ?>">
            <div class="flex items-center gap-2 mb-2">
                <?php if ($step['done']): ?>
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </span>
                <?php else: ?>
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 text-xs font-bold"><?= $i + 1 ?></span>
                <?php endif; ?>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white"><?= e($step['title']) ?></h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300"><?= e($step['text']) ?></p>
            <?php if (!$step['done']): ?>
            <a href="<?= url($step['link']) ?>" class="inline-flex mt-3 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                <?= e($step['cta']) ?> →
            </a>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ol>
</div>
