<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">
        <?php
        $statCards = [
            ['label' => 'Totale URL', 'value' => $stats['total'], 'color' => 'slate', 'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1'],
            ['label' => 'In Attesa', 'value' => $stats['pending'], 'color' => 'amber'],
            ['label' => 'Scrappate', 'value' => $stats['scraped'], 'color' => 'blue'],
            ['label' => 'Generate', 'value' => $stats['generated'], 'color' => 'purple'],
            ['label' => 'Approvate', 'value' => $stats['approved'], 'color' => 'teal'],
            ['label' => 'Pubblicate', 'value' => $stats['published'], 'color' => 'emerald'],
            ['label' => 'Errori', 'value' => $stats['errors'], 'color' => 'red'],
        ];
        foreach ($statCards as $card):
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-<?= $card['color'] ?>-600 dark:text-<?= $card['color'] ?>-400">
                <?= number_format($card['value']) ?>
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= $card['label'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($stats['total'] > 0): ?>
    <!-- Progress Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Avanzamento</span>
            <span class="text-sm text-slate-500 dark:text-slate-400">
                <?php
                $completedPct = $stats['total'] > 0
                    ? round(($stats['approved'] + $stats['published']) / $stats['total'] * 100)
                    : 0;
                ?>
                <?= $completedPct ?>% completato
            </span>
        </div>
        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
            <?php
            $total = max($stats['total'], 1);
            $segments = [
                ['pct' => $stats['published'] / $total * 100, 'color' => 'bg-emerald-500'],
                ['pct' => $stats['approved'] / $total * 100, 'color' => 'bg-teal-500'],
                ['pct' => $stats['generated'] / $total * 100, 'color' => 'bg-purple-500'],
                ['pct' => $stats['scraped'] / $total * 100, 'color' => 'bg-blue-500'],
                ['pct' => $stats['errors'] / $total * 100, 'color' => 'bg-red-500'],
            ];
            ?>
            <div class="flex h-full">
                <?php foreach ($segments as $seg): ?>
                <?php if ($seg['pct'] > 0): ?>
                <div class="<?= $seg['color'] ?> h-full" style="width: <?= $seg['pct'] ?>%"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Pubblicati</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-teal-500"></span> Approvati</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> Generati</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Scrappati</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span> Errori</span>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" x-data="{ scraping: false, generating: false }">
        <!-- Scrape -->
        <form action="<?= url('/content-creator/projects/' . $project['id'] . '/start-scrape-job') ?>" method="POST"
              @submit="scraping = true">
            <?= csrf_field() ?>
            <button type="submit" :disabled="scraping"
                    class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-medium transition-colors
                           <?= $stats['pending'] > 0
                               ? 'bg-blue-600 text-white hover:bg-blue-700'
                               : 'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500 cursor-not-allowed' ?>"
                    <?= $stats['pending'] == 0 ? 'disabled' : '' ?>>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                <span x-show="!scraping">Scrape (<?= $stats['pending'] ?>)</span>
                <span x-show="scraping" x-cloak>Avvio...</span>
            </button>
        </form>

        <!-- Generate -->
        <form action="<?= url('/content-creator/projects/' . $project['id'] . '/start-generate-job') ?>" method="POST"
              @submit="generating = true">
            <?= csrf_field() ?>
            <button type="submit" :disabled="generating"
                    class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-medium transition-colors
                           <?= $stats['scraped'] > 0
                               ? 'bg-purple-600 text-white hover:bg-purple-700'
                               : 'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500 cursor-not-allowed' ?>"
                    <?= $stats['scraped'] == 0 ? 'disabled' : '' ?>>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <span x-show="!generating">Genera AI (<?= $stats['scraped'] ?>)</span>
                <span x-show="generating" x-cloak>Avvio...</span>
            </button>
        </form>

        <!-- Export CSV -->
        <a href="<?= url('/content-creator/projects/' . $project['id'] . '/export/csv') ?>"
           class="flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-medium transition-colors
                  <?= ($stats['generated'] + $stats['approved'] + $stats['published']) > 0
                      ? 'bg-slate-600 text-white hover:bg-slate-700'
                      : 'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500 pointer-events-none' ?>">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Esporta CSV
        </a>

        <!-- Import URL -->
        <a href="<?= url('/content-creator/projects/' . $project['id'] . '/import') ?>"
           class="flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-medium bg-teal-600 text-white hover:bg-teal-700 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Importa URL
        </a>
    </div>

    <!-- Recent URLs Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">URL Recenti</h3>
            <?php if (count($recentUrls) > 0): ?>
            <a href="<?= url('/content-creator/projects/' . $project['id'] . '/results') ?>" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                Vedi tutti &rarr;
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($recentUrls)): ?>
        <div class="p-8 text-center">
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Nessuna URL importata ancora.</p>
            <a href="<?= url('/content-creator/projects/' . $project['id'] . '/import') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                Importa URL
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php
                    $statusColors = [
                        'pending' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                        'scraped' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                        'generated' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
                        'approved' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300',
                        'rejected' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300',
                        'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                        'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                    ];
                    $statusLabels = [
                        'pending' => 'In Attesa',
                        'scraped' => 'Scrappata',
                        'generated' => 'Generata',
                        'approved' => 'Approvata',
                        'rejected' => 'Rifiutata',
                        'published' => 'Pubblicata',
                        'error' => 'Errore',
                    ];
                    foreach ($recentUrls as $url):
                        $statusColor = $statusColors[$url['status']] ?? $statusColors['pending'];
                        $statusLabel = $statusLabels[$url['status']] ?? $url['status'];
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-3">
                            <div class="max-w-xs truncate text-sm text-slate-900 dark:text-white" title="<?= e($url['url']) ?>">
                                <?= e($url['url']) ?>
                            </div>
                            <?php if (!empty($url['scraped_title'])): ?>
                            <div class="max-w-xs truncate text-xs text-slate-500 dark:text-slate-400"><?= e($url['scraped_title']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-400">
                            <?= e($url['keyword'] ?? '-') ?>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $statusColor ?>">
                                <?= $statusLabel ?>
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="<?= url('/content-creator/projects/' . $project['id'] . '/urls/' . $url['id']) ?>"
                               class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                Dettagli
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>

    <!-- First time empty state -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Inizia importando le URL</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Importa le URL del tuo sito da CSV, Sitemap, CMS o inseriscile manualmente.
        </p>
        <a href="<?= url('/content-creator/projects/' . $project['id'] . '/import') ?>"
           class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Importa URL
        </a>
    </div>

    <?php endif; ?>
</div>
