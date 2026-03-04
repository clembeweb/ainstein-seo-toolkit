<?php
/**
 * Crawl Budget — Panoramica
 *
 * Variables: $project, $budgetScore, $statusDistribution, $topChains
 */

$baseUrl = url('/seo-audit/project/' . $project['id']);
$budgetUrl = $baseUrl . '/budget';

// Score ring color
$score = $budgetScore['score'] ?? 0;
$color = $budgetScore['color'] ?? 'slate';
$colorMap = [
    'emerald' => ['ring' => 'stroke-emerald-500', 'text' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-100 dark:bg-emerald-900/50', 'border' => 'border-emerald-200 dark:border-emerald-800'],
    'blue'    => ['ring' => 'stroke-blue-500',    'text' => 'text-blue-600 dark:text-blue-400',       'bg' => 'bg-blue-100 dark:bg-blue-900/50',       'border' => 'border-blue-200 dark:border-blue-800'],
    'amber'   => ['ring' => 'stroke-amber-500',   'text' => 'text-amber-600 dark:text-amber-400',     'bg' => 'bg-amber-100 dark:bg-amber-900/50',     'border' => 'border-amber-200 dark:border-amber-800'],
    'red'     => ['ring' => 'stroke-red-500',      'text' => 'text-red-600 dark:text-red-400',         'bg' => 'bg-red-100 dark:bg-red-900/50',         'border' => 'border-red-200 dark:border-red-800'],
    'slate'   => ['ring' => 'stroke-slate-400',    'text' => 'text-slate-500 dark:text-slate-400',     'bg' => 'bg-slate-100 dark:bg-slate-800',        'border' => 'border-slate-200 dark:border-slate-700'],
];
$c = $colorMap[$color] ?? $colorMap['slate'];

// SVG ring calc (circumference for r=54 = 2*pi*54 ~ 339.29)
$circumference = 339.29;
$dashOffset = $circumference - ($circumference * $score / 100);

// Severity counts
$severity = $budgetScore['severity_counts'] ?? ['critical' => 0, 'warning' => 0, 'notice' => 0];
$totalIssues = $severity['critical'] + $severity['warning'] + $severity['notice'];

// Status distribution
$s2xx = (int)($statusDistribution['2xx'] ?? 0);
$s3xx = (int)($statusDistribution['3xx'] ?? 0);
$s4xx = (int)($statusDistribution['4xx'] ?? 0);
$s5xx = (int)($statusDistribution['5xx'] ?? 0);
$sTotal = $s2xx + $s3xx + $s4xx + $s5xx;

// Sub-tab helper
$activeSubTab = 'overview';
?>

<?php $currentPage = 'budget'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<?= \Core\View::partial('components/orphaned-project-notice', ['project' => $project]) ?>

<div class="space-y-6">

    <!-- Sub-navigation tabs -->
    <div class="flex items-center gap-2 mb-6">
        <a href="<?= url($budgetUrl) ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">Panoramica</a>
        <a href="<?= url($budgetUrl . '/redirects') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Redirect</a>
        <a href="<?= url($budgetUrl . '/waste') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Pagine Spreco</a>
        <a href="<?= url($budgetUrl . '/indexability') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Indicizzabilit&agrave;</a>
    </div>

    <!-- Intro -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <div class="flex gap-3">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mt-0.5">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Il <strong>Crawl Budget</strong> rappresenta il numero di pagine che Google scansiona sul tuo sito in un dato periodo.
                    Redirect inutili, pagine duplicate e problemi di indicizzabilit&agrave; sprecano questo budget, rallentando l'indicizzazione delle pagine importanti.
                </p>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Punteggio pi&ugrave; alto = meno sprechi. Concentrati sui problemi <strong class="text-red-600">critici</strong> per ottenere i risultati migliori.
                </p>
            </div>
        </div>
    </div>

    <?php if (($budgetScore['total_pages'] ?? 0) === 0): ?>
    <!-- Empty state -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
        </svg>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun dato disponibile</h3>
        <p class="text-slate-500 dark:text-slate-400">Avvia una scansione per analizzare il crawl budget del sito.</p>
    </div>
    <?php else: ?>

    <!-- Score + KPI Row -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <!-- Score Ring -->
        <div class="lg:col-span-1 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col items-center justify-center">
            <div class="relative w-32 h-32">
                <svg class="w-32 h-32 -rotate-90" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="54" stroke-width="8" fill="none" class="stroke-slate-200 dark:stroke-slate-700"/>
                    <circle cx="60" cy="60" r="54" stroke-width="8" fill="none"
                            class="<?= $c['ring'] ?>"
                            stroke-linecap="round"
                            stroke-dasharray="<?= $circumference ?>"
                            stroke-dashoffset="<?= $dashOffset ?>"/>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-3xl font-bold <?= $c['text'] ?>"><?= $score ?></span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">/100</span>
                </div>
            </div>
            <p class="mt-3 text-sm font-medium <?= $c['text'] ?>"><?= e($budgetScore['label'] ?? 'N/D') ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Budget Score</p>
        </div>

        <!-- KPI Cards -->
        <div class="lg:col-span-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <!-- Budget Score -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border <?= $c['border'] ?> p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg <?= $c['bg'] ?> flex items-center justify-center">
                        <svg class="w-4 h-4 <?= $c['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Budget Score</span>
                </div>
                <p class="text-2xl font-bold <?= $c['text'] ?>"><?= $score ?><span class="text-sm font-normal text-slate-400">/100</span></p>
            </div>

            <!-- % Spreco -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">% Spreco</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $budgetScore['waste_percentage'] ?? 0 ?>%</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= $budgetScore['waste_pages'] ?? 0 ?> di <?= $budgetScore['total_pages'] ?? 0 ?> pagine</p>
            </div>

            <!-- Pagine con Redirect -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Redirect</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $s3xx ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">pagine con redirect</p>
            </div>

            <!-- Problemi Totali -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Problemi</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $totalIssues ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    <span class="text-red-600"><?= $severity['critical'] ?> critici</span> /
                    <span class="text-amber-600"><?= $severity['warning'] ?> warning</span> /
                    <span class="text-blue-600"><?= $severity['notice'] ?> notice</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Status Distribution Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Distribuzione Status Code</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Come rispondono le pagine del sito ai crawler. Le risposte 2xx sono corrette, 3xx indicano redirect, 4xx errori client e 5xx errori server.</p>

        <?php if ($sTotal > 0): ?>
        <!-- Stacked bar -->
        <div class="w-full h-6 rounded-full overflow-hidden flex bg-slate-100 dark:bg-slate-700 mb-4">
            <?php if ($s2xx > 0): ?>
            <div class="bg-emerald-500 h-full" style="width: <?= round(($s2xx / $sTotal) * 100, 1) ?>%" title="2xx: <?= $s2xx ?>"></div>
            <?php endif; ?>
            <?php if ($s3xx > 0): ?>
            <div class="bg-amber-500 h-full" style="width: <?= round(($s3xx / $sTotal) * 100, 1) ?>%" title="3xx: <?= $s3xx ?>"></div>
            <?php endif; ?>
            <?php if ($s4xx > 0): ?>
            <div class="bg-red-500 h-full" style="width: <?= round(($s4xx / $sTotal) * 100, 1) ?>%" title="4xx: <?= $s4xx ?>"></div>
            <?php endif; ?>
            <?php if ($s5xx > 0): ?>
            <div class="bg-red-800 h-full" style="width: <?= round(($s5xx / $sTotal) * 100, 1) ?>%" title="5xx: <?= $s5xx ?>"></div>
            <?php endif; ?>
        </div>
        <!-- Legend -->
        <div class="flex flex-wrap gap-6 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                <span class="text-slate-600 dark:text-slate-300">2xx: <strong><?= number_format($s2xx) ?></strong> (<?= $sTotal > 0 ? round(($s2xx / $sTotal) * 100, 1) : 0 ?>%)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                <span class="text-slate-600 dark:text-slate-300">3xx: <strong><?= number_format($s3xx) ?></strong> (<?= $sTotal > 0 ? round(($s3xx / $sTotal) * 100, 1) : 0 ?>%)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <span class="text-slate-600 dark:text-slate-300">4xx: <strong><?= number_format($s4xx) ?></strong> (<?= $sTotal > 0 ? round(($s4xx / $sTotal) * 100, 1) : 0 ?>%)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-red-800"></div>
                <span class="text-slate-600 dark:text-slate-300">5xx: <strong><?= number_format($s5xx) ?></strong> (<?= $sTotal > 0 ? round(($s5xx / $sTotal) * 100, 1) : 0 ?>%)</span>
            </div>
        </div>
        <?php else: ?>
        <p class="text-sm text-slate-500 dark:text-slate-400">Nessun dato disponibile.</p>
        <?php endif; ?>
    </div>

    <!-- Top Redirect Chains -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Catene di Redirect Principali</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Le pagine con il maggior numero di hop di redirect</p>
        </div>

        <?php if (empty($topChains)): ?>
        <div class="p-12 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-3">
                <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">Nessuna catena di redirect rilevata.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Destinazione</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Hop</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($topChains as $chain): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="text-sm text-slate-900 dark:text-white max-w-xs truncate block" title="<?= e($chain['url'] ?? '') ?>">
                                <?= e(strlen($chain['url'] ?? '') > 60 ? '...' . substr($chain['url'], -57) : ($chain['url'] ?? '')) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-slate-600 dark:text-slate-300 max-w-xs truncate block" title="<?= e($chain['redirect_target'] ?? '') ?>">
                                <?= e(strlen($chain['redirect_target'] ?? '') > 60 ? '...' . substr($chain['redirect_target'], -57) : ($chain['redirect_target'] ?? '-')) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= ($chain['redirect_hops'] ?? 0) >= 3 ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' ?>">
                                <?= (int)($chain['redirect_hops'] ?? 0) ?> hop
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-mono text-slate-600 dark:text-slate-300"><?= (int)($chain['status_code'] ?? 0) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Category Breakdown -->
    <?php
    $catBreakdown = $budgetScore['category_breakdown'] ?? [];
    $catMeta = [
        'redirect' => [
            'label' => 'Redirect',
            'url' => $budgetUrl . '/redirects',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'color' => 'blue',
        ],
        'waste' => [
            'label' => 'Spreco Crawl Budget',
            'url' => $budgetUrl . '/waste',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
            'color' => 'amber',
        ],
        'indexability' => [
            'label' => 'Indicizzabilit&agrave;',
            'url' => $budgetUrl . '/indexability',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            'color' => 'emerald',
        ],
    ];
    $colorClasses = [
        'blue'    => ['border' => 'border-blue-200 dark:border-blue-900/50',     'hover' => 'hover:bg-blue-50 dark:hover:bg-blue-900/20',     'bg' => 'bg-blue-100 dark:bg-blue-900/50',     'text' => 'text-blue-600 dark:text-blue-400'],
        'amber'   => ['border' => 'border-amber-200 dark:border-amber-900/50',   'hover' => 'hover:bg-amber-50 dark:hover:bg-amber-900/20',   'bg' => 'bg-amber-100 dark:bg-amber-900/50',   'text' => 'text-amber-600 dark:text-amber-400'],
        'emerald' => ['border' => 'border-emerald-200 dark:border-emerald-900/50','hover' => 'hover:bg-emerald-50 dark:hover:bg-emerald-900/20','bg' => 'bg-emerald-100 dark:bg-emerald-900/50','text' => 'text-emerald-600 dark:text-emerald-400'],
    ];
    ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Riepilogo per Categoria</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Clicca su una categoria per vedere il dettaglio dei problemi e le raccomandazioni specifiche.</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($catMeta as $catKey => $meta):
                $catData = $catBreakdown[$catKey] ?? ['total' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0];
                $cc = $colorClasses[$meta['color']];
            ?>
            <a href="<?= url($meta['url']) ?>" class="flex items-center justify-between p-4 rounded-xl border <?= $cc['border'] ?> <?= $cc['hover'] ?> transition-colors">
                <div>
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300"><?= $meta['label'] ?></p>
                    <p class="text-2xl font-bold <?= $cc['text'] ?>"><?= $catData['total'] ?></p>
                    <?php if ($catData['total'] > 0): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        <?php $parts = [];
                        if ($catData['critical'] > 0) $parts[] = '<span class="text-red-600">' . $catData['critical'] . ' critici</span>';
                        if ($catData['warning'] > 0) $parts[] = '<span class="text-amber-600">' . $catData['warning'] . ' warning</span>';
                        if ($catData['notice'] > 0) $parts[] = '<span class="text-blue-600">' . $catData['notice'] . ' notice</span>';
                        echo implode(' / ', $parts); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="w-10 h-10 rounded-full <?= $cc['bg'] ?> flex items-center justify-center">
                    <svg class="w-5 h-5 <?= $cc['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?= $meta['icon'] ?>
                    </svg>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>
</div>
