<?php
$stats = $stats ?? ['last_24h' => [], 'last_30d' => [], 'by_provider' => [], 'daily_trend' => []];
$filters = $filters ?? [];
$providersList = $providersList ?? [];
$modulesList = $modulesList ?? [];
$logs = $logs ?? [];
$pagination = $pagination ?? ['current' => 1, 'total' => 1, 'total_items' => 0, 'per_page' => 50];

// Mappa colori provider (bg per card)
$providerCardColors = [
    'dataforseo'              => ['bg' => 'bg-blue-50 dark:bg-blue-900/20', 'border' => 'border-blue-200 dark:border-blue-800', 'text' => 'text-blue-700 dark:text-blue-300', 'accent' => 'bg-blue-500'],
    'serpapi'                 => ['bg' => 'bg-purple-50 dark:bg-purple-900/20', 'border' => 'border-purple-200 dark:border-purple-800', 'text' => 'text-purple-700 dark:text-purple-300', 'accent' => 'bg-purple-500'],
    'serper'                  => ['bg' => 'bg-green-50 dark:bg-green-900/20', 'border' => 'border-green-200 dark:border-green-800', 'text' => 'text-green-700 dark:text-green-300', 'accent' => 'bg-green-500'],
    'google_gsc'              => ['bg' => 'bg-red-50 dark:bg-red-900/20', 'border' => 'border-red-200 dark:border-red-800', 'text' => 'text-red-700 dark:text-red-300', 'accent' => 'bg-red-500'],
    'google_oauth'            => ['bg' => 'bg-orange-50 dark:bg-orange-900/20', 'border' => 'border-orange-200 dark:border-orange-800', 'text' => 'text-orange-700 dark:text-orange-300', 'accent' => 'bg-orange-500'],
    'google_ga4'              => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'border' => 'border-yellow-200 dark:border-yellow-800', 'text' => 'text-yellow-700 dark:text-yellow-300', 'accent' => 'bg-yellow-500'],
    'rapidapi_keyword'        => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/20', 'border' => 'border-cyan-200 dark:border-cyan-800', 'text' => 'text-cyan-700 dark:text-cyan-300', 'accent' => 'bg-cyan-500'],
    'rapidapi_keyword_insight' => ['bg' => 'bg-teal-50 dark:bg-teal-900/20', 'border' => 'border-teal-200 dark:border-teal-800', 'text' => 'text-teal-700 dark:text-teal-300', 'accent' => 'bg-teal-500'],
    'keywordseverywhere'      => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/20', 'border' => 'border-indigo-200 dark:border-indigo-800', 'text' => 'text-indigo-700 dark:text-indigo-300', 'accent' => 'bg-indigo-500'],
    'openai_dalle'            => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'border' => 'border-emerald-200 dark:border-emerald-800', 'text' => 'text-emerald-700 dark:text-emerald-300', 'accent' => 'bg-emerald-500'],
];

// Mappa colori inline per tabella
$providerColors = [
    'dataforseo'              => 'text-blue-600 dark:text-blue-400',
    'serpapi'                 => 'text-purple-600 dark:text-purple-400',
    'serper'                  => 'text-green-600 dark:text-green-400',
    'google_gsc'              => 'text-red-600 dark:text-red-400',
    'google_oauth'            => 'text-orange-600 dark:text-orange-400',
    'google_ga4'              => 'text-yellow-600 dark:text-yellow-400',
    'rapidapi_keyword'        => 'text-cyan-600 dark:text-cyan-400',
    'rapidapi_keyword_insight' => 'text-teal-600 dark:text-teal-400',
    'keywordseverywhere'      => 'text-indigo-600 dark:text-indigo-400',
    'openai_dalle'            => 'text-emerald-600 dark:text-emerald-400',
];

// Mappa nomi provider
$providerNames = [
    'dataforseo'              => 'DataForSEO',
    'serpapi'                 => 'SerpAPI',
    'serper'                  => 'Serper.dev',
    'google_gsc'              => 'Google GSC',
    'google_oauth'            => 'Google OAuth',
    'google_ga4'              => 'Google GA4',
    'rapidapi_keyword'        => 'RapidAPI Keyword',
    'rapidapi_keyword_insight' => 'RapidAPI Insight',
    'keywordseverywhere'      => 'Keywords Everywhere',
    'openai_dalle'            => 'OpenAI DALL-E',
];

// Status labels IT
$statusLabels = [
    'success' => 'Successo',
    'error' => 'Errore',
    'rate_limited' => 'Limite',
];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">API Logs</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cronologia chiamate API esterne per debug e analisi costi</p>
        </div>

        <!-- Cleanup Button -->
        <form method="POST" action="<?= url('/admin/api-logs/cleanup') ?>"
              x-data @submit.prevent="window.ainstein.confirm('Eliminare i log più vecchi di 30 giorni?', {destructive: true}).then(() => $el.submit())">
            <?= csrf_field() ?>
            <input type="hidden" name="days" value="30">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 text-sm font-medium transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Cleanup > 30 giorni
            </button>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['last_24h']['total'] ?? 0) ?></div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Chiamate 24h</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
            <?php
            $avgDuration = $stats['last_24h']['avg_duration'] ?? 0;
            ?>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">Media: <?= number_format($avgDuration) ?>ms</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400"><?= number_format($stats['last_24h']['success'] ?? 0) ?></div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Successi 24h</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <?php
            $total24h = max(1, $stats['last_24h']['total'] ?? 1);
            $successRate = round((($stats['last_24h']['success'] ?? 0) / $total24h) * 100);
            ?>
            <div class="mt-2 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full" style="width: <?= $successRate ?>%"></div>
            </div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= $successRate ?>% tasso successo</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400"><?= number_format($stats['last_24h']['errors'] ?? 0) ?></div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Errori 24h</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-red-50 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <?php $rateLimited = $stats['last_24h']['rate_limited'] ?? 0; ?>
            <?php if ($rateLimited > 0): ?>
            <div class="mt-2 text-xs text-yellow-600 dark:text-yellow-400"><?= $rateLimited ?> rate limited</div>
            <?php endif; ?>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">$<?= number_format($stats['last_30d']['cost'] ?? 0, 4) ?></div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Costo 30 giorni</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400"><?= number_format($stats['last_30d']['total'] ?? 0) ?> chiamate totali</div>
        </div>
    </div>

    <!-- Provider Breakdown con Card Visive -->
    <?php if (!empty($stats['by_provider'])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white uppercase tracking-wider">Breakdown per Provider (30 giorni)</h3>
            <span class="text-xs text-slate-500 dark:text-slate-400"><?= count($stats['by_provider']) ?> provider attivi</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            <?php foreach ($stats['by_provider'] as $provider):
                $key = $provider['provider'];
                $colors = $providerCardColors[$key] ?? ['bg' => 'bg-slate-50 dark:bg-slate-700/50', 'border' => 'border-slate-200 dark:border-slate-700', 'text' => 'text-slate-700 dark:text-slate-300', 'accent' => 'bg-slate-500'];
                $name = $providerNames[$key] ?? $key;
                $calls = (int)$provider['calls'];
                $errors = (int)$provider['errors'];
                $cost = (float)$provider['cost'];
                $rate = $calls > 0 ? round((($calls - $errors) / $calls) * 100) : 100;
                $hasErrors = $errors > 0;
                $highErrorRate = $calls > 0 && ($errors / $calls) > 0.2;
            ?>
                <div class="<?= $colors['bg'] ?> border <?= $colors['border'] ?> rounded-lg p-3 <?= $highErrorRate ? 'ring-2 ring-red-300 dark:ring-red-700' : '' ?>">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full <?= $colors['accent'] ?>"></div>
                            <span class="text-sm font-semibold <?= $colors['text'] ?>"><?= e($name) ?></span>
                        </div>
                        <?php if ($highErrorRate): ?>
                            <span class="text-xs px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 font-medium" title="Tasso errori > 20%">
                                <svg class="w-3 h-3 inline -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                                Alert
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($calls) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mb-2">chiamate</div>
                    <!-- Success rate bar -->
                    <div class="h-1.5 bg-white/60 dark:bg-slate-600/50 rounded-full overflow-hidden mb-2">
                        <div class="h-full rounded-full <?= $rate >= 80 ? 'bg-green-500' : ($rate >= 50 ? 'bg-yellow-500' : 'bg-red-500') ?>" style="width: <?= $rate ?>%"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-600 dark:text-slate-400"><?= $rate ?>% ok</span>
                        <div class="flex items-center gap-2">
                            <?php if ($hasErrors): ?>
                                <span class="text-red-600 dark:text-red-400 font-medium"><?= $errors ?> errori</span>
                            <?php endif; ?>
                            <?php if ($cost > 0): ?>
                                <span class="text-slate-600 dark:text-slate-400">$<?= number_format($cost, 4) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Trend Giornaliero (Chart) -->
    <?php if (!empty($stats['daily_trend'])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Trend Chiamate API (ultimi 7 giorni)</h3>
        <div class="h-48">
            <canvas id="apiTrendChart"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtri -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" class="grid grid-cols-2 md:grid-cols-7 gap-4">
            <!-- Date From -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Da</label>
                <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">A</label>
                <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <!-- Provider -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Provider</label>
                <select name="provider" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Tutti</option>
                    <?php foreach ($providersList as $provider): ?>
                        <option value="<?= e($provider) ?>" <?= ($filters['provider'] ?? '') === $provider ? 'selected' : '' ?>>
                            <?= $providerNames[$provider] ?? $provider ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Modulo -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Modulo</label>
                <select name="module" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Tutti</option>
                    <?php foreach ($modulesList as $module): ?>
                        <option value="<?= e($module) ?>" <?= ($filters['module'] ?? '') === $module ? 'selected' : '' ?>>
                            <?= e($module) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Tutti</option>
                    <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : '' ?>>Successo</option>
                    <option value="error" <?= ($filters['status'] ?? '') === 'error' ? 'selected' : '' ?>>Errore</option>
                    <option value="rate_limited" <?= ($filters['status'] ?? '') === 'rate_limited' ? 'selected' : '' ?>>Limite raggiunto</option>
                </select>
            </div>

            <!-- Search -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Cerca</label>
                <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="endpoint, payload..."
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <!-- Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium transition-colors">
                    Filtra
                </button>
                <a href="<?= url('/admin/api-logs') ?>" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 text-sm font-medium transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Tabella Logs -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data/Ora</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Provider</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Endpoint</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Modulo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">HTTP</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Durata</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Costo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                Nessun log trovato
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                    <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td class="px-4 py-3 text-sm whitespace-nowrap">
                                    <span class="font-medium <?= $providerColors[$log['provider']] ?? 'text-slate-600 dark:text-slate-400' ?>">
                                        <?= $providerNames[$log['provider']] ?? e($log['provider']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                    <span class="truncate block max-w-[200px]" title="<?= e($log['endpoint']) ?>">
                                        <span class="text-slate-400 dark:text-slate-500 text-xs"><?= e($log['method']) ?></span>
                                        <?= e($log['endpoint']) ?>
                                    </span>
                                    <?php if (!empty($log['context'])): ?>
                                        <span class="text-xs text-slate-400 dark:text-slate-500 truncate block max-w-[200px]" title="<?= e($log['context']) ?>">
                                            <?= e($log['context']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        <?= e($log['module_slug']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <?php
                                    $httpClass = 'text-green-600 dark:text-green-400';
                                    if ($log['response_code'] >= 400) {
                                        $httpClass = 'text-red-600 dark:text-red-400';
                                    } elseif ($log['response_code'] >= 300) {
                                        $httpClass = 'text-yellow-600 dark:text-yellow-400';
                                    }
                                    ?>
                                    <span class="font-mono <?= $httpClass ?>"><?= $log['response_code'] ?: '-' ?></span>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-slate-600 dark:text-slate-300">
                                    <?= number_format($log['duration_ms']) ?>ms
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-slate-600 dark:text-slate-300">
                                    <?php if ($log['cost'] > 0): ?>
                                        $<?= number_format($log['cost'], 4) ?>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($log['status'] === 'success'): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            Successo
                                        </span>
                                    <?php elseif ($log['status'] === 'error'): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300" title="<?= e($log['error_message'] ?? '') ?>">
                                            Errore
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                            Limite
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="<?= url('/admin/api-logs/' . $log['id']) ?>"
                                       class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300" title="Dettaglio">
                                        <svg class="w-5 h-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginazione -->
        <?php if ($pagination['total'] > 1): ?>
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    Mostrando <?= (($pagination['current'] - 1) * $pagination['per_page']) + 1 ?> -
                    <?= min($pagination['current'] * $pagination['per_page'], $pagination['total_items']) ?>
                    di <?= number_format($pagination['total_items']) ?>
                </div>
                <div class="flex gap-2">
                    <?php if ($pagination['current'] > 1): ?>
                        <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] - 1])) ?>"
                           class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                    <?php endif; ?>

                    <span class="px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded-lg font-medium">
                        <?= $pagination['current'] ?> / <?= $pagination['total'] ?>
                    </span>

                    <?php if ($pagination['current'] < $pagination['total']): ?>
                        <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] + 1])) ?>"
                           class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($stats['daily_trend'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    const trendData = <?= json_encode($stats['daily_trend']) ?>;
    const labels = trendData.map(d => {
        const date = new Date(d.day);
        return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
    });

    new Chart(document.getElementById('apiTrendChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Successi',
                    data: trendData.map(d => parseInt(d.success)),
                    backgroundColor: isDark ? 'rgba(34, 197, 94, 0.6)' : 'rgba(34, 197, 94, 0.8)',
                    borderRadius: 4,
                    barPercentage: 0.7,
                },
                {
                    label: 'Errori',
                    data: trendData.map(d => parseInt(d.errors)),
                    backgroundColor: isDark ? 'rgba(239, 68, 68, 0.6)' : 'rgba(239, 68, 68, 0.8)',
                    borderRadius: 4,
                    barPercentage: 0.7,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: { color: textColor, boxWidth: 12, padding: 16, font: { size: 11 } }
                }
            },
            scales: {
                y: { beginAtZero: true, stacked: true, grid: { color: gridColor }, ticks: { color: textColor, stepSize: 1 } },
                x: { stacked: true, grid: { display: false }, ticks: { color: textColor } }
            }
        }
    });
});
</script>
<?php endif; ?>
