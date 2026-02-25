<?php
/**
 * Crawl Budget Optimizer — Lista Progetti
 */

$scoreColors = [
    'excellent' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
    'good' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
    'fair' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
    'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
    'none' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
];

function getScoreBadge(int $score, array $colors): string {
    if ($score >= 90) return $colors['excellent'];
    if ($score >= 70) return $colors['good'];
    if ($score >= 50) return $colors['fair'];
    if ($score > 0) return $colors['critical'];
    return $colors['none'];
}
?>

<!-- Hero Banner -->
<?= \Core\View::partial('components/dashboard-hero-banner', [
    'title' => 'Crawl Budget Optimizer',
    'description' => 'Analizza come i motori di ricerca spendono il crawl budget sul tuo sito. Identifica redirect chains, pagine spreco e conflitti di indexability.',
    'color' => 'orange',
    'badge' => 'Come funziona',
    'storageKey' => 'ainstein_hero_crawl_budget',
    'steps' => [
        ['icon' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>', 'title' => 'Inserisci il dominio', 'desc' => 'Crea un progetto con il sito da analizzare'],
        ['icon' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>', 'title' => 'Avvia il crawl', 'desc' => 'Il crawler analizza fino a 5.000 pagine con redirect tracing'],
        ['icon' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>', 'title' => 'Ottieni il report', 'desc' => 'Score, issue prioritarie e report AI con azioni concrete'],
    ],
    'ctaText' => 'Nuovo Progetto',
    'ctaUrl' => url('/projects/create'),
]) ?>

<div class="max-w-7xl mx-auto">
    <?php if (empty($projects)): ?>
        <!-- Empty State -->
        <?= \Core\View::partial('components/table-empty-state', [
            'icon' => '<svg class="w-16 h-16 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>',
            'title' => 'Nessun progetto',
            'description' => 'Crea il tuo primo progetto per analizzare il crawl budget di un sito.',
            'ctaText' => 'Nuovo Progetto',
            'ctaUrl' => url('/projects/create'),
        ]) ?>
    <?php else: ?>
        <!-- Projects Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($projects as $p): ?>
                <?php
                    $pScore = (int) ($p['crawl_budget_score'] ?? 0);
                    $badgeClass = getScoreBadge($pScore, $scoreColors);
                    $criticalCount = (int) ($p['critical_issues'] ?? 0);
                    $warningCount = (int) ($p['warning_issues'] ?? 0);
                ?>
                <a href="<?= url('/crawl-budget/projects/' . $p['id']) ?>"
                   class="block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md hover:border-orange-300 dark:hover:border-orange-600 transition-all">

                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white truncate"><?= e($p['name']) ?></h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 truncate"><?= e($p['domain']) ?></p>
                        </div>
                        <?php if ($pScore > 0): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $badgeClass ?>">
                            <?= $pScore ?>/100
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $scoreColors['none'] ?>">
                            N/A
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Issue counts -->
                    <div class="flex items-center gap-3 mb-3">
                        <?php if ($criticalCount > 0): ?>
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10"/></svg>
                            <?= $criticalCount ?> critici
                        </span>
                        <?php endif; ?>
                        <?php if ($warningCount > 0): ?>
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10"/></svg>
                            <?= $warningCount ?> warning
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Status + Last crawl -->
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span><?= ucfirst($p['status'] ?? 'pending') ?></span>
                        <?php if (!empty($p['last_crawl_at'])): ?>
                        <span><?= date('d/m/Y', strtotime($p['last_crawl_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
