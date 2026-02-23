<?php
// Color map per moduli
$colorMap = [
    'amber'   => ['bg' => 'bg-amber-100 dark:bg-amber-900/30',     'text' => 'text-amber-600 dark:text-amber-400',     'border' => '#F59E0B'],
    'emerald' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-600 dark:text-emerald-400', 'border' => '#10B981'],
    'blue'    => ['bg' => 'bg-blue-100 dark:bg-blue-900/30',       'text' => 'text-blue-600 dark:text-blue-400',       'border' => '#3B82F6'],
    'purple'  => ['bg' => 'bg-purple-100 dark:bg-purple-900/30',   'text' => 'text-purple-600 dark:text-purple-400',   'border' => '#8B5CF6'],
    'rose'    => ['bg' => 'bg-rose-100 dark:bg-rose-900/30',       'text' => 'text-rose-600 dark:text-rose-400',       'border' => '#F43F5E'],
    'cyan'    => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/30',       'text' => 'text-cyan-600 dark:text-cyan-400',       'border' => '#06B6D4'],
    'orange'  => ['bg' => 'bg-orange-100 dark:bg-orange-900/30',   'text' => 'text-orange-600 dark:text-orange-400',   'border' => '#F97316'],
];

// Helper: build module project URL (appends type if module requires it in route)
function _dashboard_module_link(array $mod, array $moduleConfig): string {
    $slug = $mod['slug'];
    $link = $mod['route_prefix'] . '/' . $mod['module_project_id'];
    if (!empty($moduleConfig[$slug]['type_in_route']) && !empty($mod['type'])) {
        $link .= '/' . $mod['type'];
    }
    return url($link);
}

// Slugs dei moduli attivi per quick-check
$activeSlugs = array_unique(array_column($activeModules, 'slug'));

// Slugs dei moduli attivi nel sistema (ModuleLoader)
$systemActiveSlugs = array_column($availableModules, 'slug');

// Tipi modulo per modal selezione
$_moduleTypes = $moduleTypes ?? [];

// Group active modules by slug for multi-type display
$groupedModules = [];
foreach ($activeModules as $mod) {
    $groupedModules[$mod['slug']][] = $mod;
}

// Filtra moduli disponibili non ancora attivati (o con tipi rimanenti)
$nonActivated = [];
foreach ($moduleConfig as $slug => $config) {
    if (!in_array($slug, $systemActiveSlugs)) {
        continue;
    }
    if (isset($moduleTypes[$slug])) {
        // Typed module: show in available only if NOT yet activated at all
        if (!in_array($slug, $activeSlugs)) {
            $nonActivated[$slug] = $config;
        }
    } else {
        // Untyped module: show if not activated
        if (!in_array($slug, $activeSlugs)) {
            $nonActivated[$slug] = $config;
        }
    }
}

// Helper: format metric value
if (!function_exists('_dashboard_format_value')):
function _dashboard_format_value($value) {
    if (is_float($value)) {
        return number_format($value, 1);
    } elseif (is_numeric($value)) {
        return number_format((int)$value);
    } else {
        return htmlspecialchars((string)$value);
    }
}

endif;
// Helper: time ago string
if (!function_exists('_dashboard_time_ago')):
function _dashboard_time_ago($dateStr) {
    $ts = strtotime($dateStr);
    $diff = time() - $ts;
    if ($diff < 60) return 'Adesso';
    if ($diff < 3600) return floor($diff / 60) . ' min fa';
    if ($diff < 86400) return floor($diff / 3600) . ' ore fa';
    if ($diff < 604800) return floor($diff / 86400) . ' giorni fa';
    return date('d/m/Y', $ts);
}
endif;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="<?= url('/projects') ?>" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Torna ai progetti">
                <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= htmlspecialchars($project['color'] ?? '#3B82F6') ?>"></span>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($project['name']) ?></h1>
                </div>
                <?php if (!empty($project['domain'])): ?>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 ml-6"><?= htmlspecialchars($project['domain']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/projects/' . $project['id'] . '/settings') ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Impostazioni
            </a>
        </div>
    </div>

    <div x-data="activationModal()">

    <?php if (!empty($activeModules)): ?>
    <!-- Active Modules KPI Section -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Moduli attivi</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <?php foreach ($groupedModules as $slug => $modules):
                $firstMod = $modules[0];
                $color = $firstMod['color'] ?? 'blue';
                $colors = $colorMap[$color] ?? $colorMap['blue'];
                $hasTypes = isset($_moduleTypes[$slug]);
                $hasRemaining = !empty($remainingTypes[$slug] ?? []);
                $moduleCount = count($modules);
            ?>

            <?php if (!$hasTypes): ?>
            <!-- Untyped module: single card (unchanged behavior) -->
            <?php
                $statsKey = $slug;
                $stats = $moduleStats[$statsKey] ?? ['metrics' => [], 'lastActivity' => null];
                $metrics = $stats['metrics'] ?? [];
                $moduleLink = _dashboard_module_link($firstMod, $moduleConfig);
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5" style="border-left: 4px solid <?= htmlspecialchars($colors['border']) ?>">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg <?= $colors['bg'] ?> flex items-center justify-center">
                            <svg class="w-5 h-5 <?= $colors['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= htmlspecialchars($firstMod['icon'] ?? '') ?>"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($firstMod['label']) ?></h3>
                    </div>
                    <a href="<?= $moduleLink ?>" class="text-sm font-medium <?= $colors['text'] ?> hover:underline">
                        Vai al modulo &rarr;
                    </a>
                </div>

                <?php if (!empty($metrics)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-<?= min(count($metrics), 4) ?> gap-3">
                    <?php foreach ($metrics as $metric): ?>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-lg p-2.5">
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($metric['label'] ?? '') ?></p>
                        <p class="text-lg font-semibold text-slate-900 dark:text-white mt-0.5"><?= _dashboard_format_value($metric['value'] ?? 0) ?></p>
                        <?php if (isset($metric['delta']) && $metric['delta'] !== null): ?>
                        <span class="text-xs <?= $metric['delta'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>">
                            <?= $metric['delta'] >= 0 ? '+' : '' ?><?= $metric['delta'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <?php if (!empty($stats['error'])): ?>
                    Dati non disponibili al momento.
                    <?php else: ?>
                    Nessun dato ancora. Inizia a usare il modulo per vedere le statistiche.
                    <?php endif; ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($stats['lastActivity'])): ?>
                <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">
                    Ultima attivit&agrave;: <?= _dashboard_time_ago($stats['lastActivity']) ?>
                </p>
                <?php endif; ?>
            </div>

            <?php elseif ($moduleCount === 1): ?>
            <!-- Typed module: single type activated -->
            <?php
                $mod = $modules[0];
                $statsKey = $mod['type'] ? $slug . ':' . $mod['type'] : $slug;
                $stats = $moduleStats[$statsKey] ?? ['metrics' => [], 'lastActivity' => null];
                $metrics = $stats['metrics'] ?? [];
                $moduleLink = _dashboard_module_link($mod, $moduleConfig);
                $configLabel = $moduleConfig[$slug]['label'] ?? $slug;
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5" style="border-left: 4px solid <?= htmlspecialchars($colors['border']) ?>">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg <?= $colors['bg'] ?> flex items-center justify-center">
                            <svg class="w-5 h-5 <?= $colors['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= htmlspecialchars($mod['icon'] ?? '') ?>"/>
                            </svg>
                        </div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($configLabel) ?></h3>
                            <?php if (!empty($mod['type_label'])): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $colors['bg'] ?> <?= $colors['text'] ?>">
                                <?= htmlspecialchars($mod['type_label']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="<?= $moduleLink ?>" class="text-sm font-medium <?= $colors['text'] ?> hover:underline">
                            Vai al modulo &rarr;
                        </a>
                    </div>
                </div>

                <?php if (!empty($metrics)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-<?= min(count($metrics), 4) ?> gap-3">
                    <?php foreach ($metrics as $metric): ?>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-lg p-2.5">
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($metric['label'] ?? '') ?></p>
                        <p class="text-lg font-semibold text-slate-900 dark:text-white mt-0.5"><?= _dashboard_format_value($metric['value'] ?? 0) ?></p>
                        <?php if (isset($metric['delta']) && $metric['delta'] !== null): ?>
                        <span class="text-xs <?= $metric['delta'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>">
                            <?= $metric['delta'] >= 0 ? '+' : '' ?><?= $metric['delta'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <?php if (!empty($stats['error'])): ?>
                    Dati non disponibili al momento.
                    <?php else: ?>
                    Nessun dato ancora. Inizia a usare il modulo per vedere le statistiche.
                    <?php endif; ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($stats['lastActivity'])): ?>
                <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">
                    Ultima attivit&agrave;: <?= _dashboard_time_ago($stats['lastActivity']) ?>
                </p>
                <?php endif; ?>

                <?php if ($hasRemaining): ?>
                <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" @click="openTypeModal('<?= $slug ?>')"
                            class="inline-flex items-center text-sm font-medium <?= $colors['text'] ?> hover:underline">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Aggiungi tipo
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <?php else: ?>
            <!-- Typed module: multiple types activated — container card with sub-cards -->
            <?php $configLabel = $moduleConfig[$slug]['label'] ?? $slug; ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 lg:col-span-2" style="border-left: 4px solid <?= htmlspecialchars($colors['border']) ?>">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 rounded-lg <?= $colors['bg'] ?> flex items-center justify-center">
                        <svg class="w-5 h-5 <?= $colors['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= htmlspecialchars($firstMod['icon'] ?? '') ?>"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($configLabel) ?></h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php foreach ($modules as $mod):
                        $statsKey = $mod['type'] ? $slug . ':' . $mod['type'] : $slug;
                        $stats = $moduleStats[$statsKey] ?? ['metrics' => [], 'lastActivity' => null];
                        $metrics = $stats['metrics'] ?? [];
                        $moduleLink = _dashboard_module_link($mod, $moduleConfig);
                    ?>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <?php if (!empty($mod['type_label'])): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $colors['bg'] ?> <?= $colors['text'] ?>">
                                    <?= htmlspecialchars($mod['type_label']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <a href="<?= $moduleLink ?>" class="text-sm font-medium <?= $colors['text'] ?> hover:underline">
                                Vai &rarr;
                            </a>
                        </div>

                        <?php if (!empty($metrics)): ?>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($metrics as $metric): ?>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($metric['label'] ?? '') ?></p>
                                <p class="text-base font-semibold text-slate-900 dark:text-white"><?= _dashboard_format_value($metric['value'] ?? 0) ?></p>
                                <?php if (isset($metric['delta']) && $metric['delta'] !== null): ?>
                                <span class="text-xs <?= $metric['delta'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>">
                                    <?= $metric['delta'] >= 0 ? '+' : '' ?><?= $metric['delta'] ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <?php if (!empty($stats['error'])): ?>
                            Dati non disponibili.
                            <?php else: ?>
                            Nessun dato ancora.
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>

                        <?php if (!empty($stats['lastActivity'])): ?>
                        <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                            Ultima attivit&agrave;: <?= _dashboard_time_ago($stats['lastActivity']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($hasRemaining): ?>
                <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" @click="openTypeModal('<?= $slug ?>')"
                            class="inline-flex items-center text-sm font-medium <?= $colors['text'] ?> hover:underline">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Aggiungi tipo
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <!-- No modules activated yet -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center mb-6">
        <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
            <svg class="w-6 h-6 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
        </div>
        <h3 class="text-base font-semibold text-slate-700 dark:text-slate-300">Nessun modulo attivo</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Attiva il primo modulo qui sotto per iniziare a lavorare sul progetto.</p>
    </div>
    <?php endif; ?>

    <!-- Available (non-activated) Modules -->
    <?php if (!empty($nonActivated)): ?>
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Moduli disponibili</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($nonActivated as $slug => $config):
                $color = $config['color'] ?? 'blue';
                $colors = $colorMap[$color] ?? $colorMap['blue'];
                $hasTypes = isset($_moduleTypes[$slug]);
            ?>
            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-300 dark:border-slate-600 p-4 hover:border-slate-400 dark:hover:border-slate-500 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-lg <?= $colors['bg'] ?> flex items-center justify-center">
                        <svg class="w-5 h-5 <?= $colors['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= htmlspecialchars($config['icon'] ?? '') ?>"/>
                        </svg>
                    </div>
                    <span class="font-medium text-slate-700 dark:text-slate-300 text-sm"><?= htmlspecialchars($config['label'] ?? $slug) ?></span>
                </div>
                <?php if ($hasTypes): ?>
                <!-- Modulo con tipi: apre modal -->
                <button type="button" @click="openTypeModal('<?= $slug ?>')"
                        class="inline-flex items-center text-sm font-medium <?= $colors['text'] ?> hover:underline">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Attiva modulo
                </button>
                <?php else: ?>
                <!-- Modulo senza tipi: attivazione diretta -->
                <form method="POST" action="<?= url('/projects/' . $project['id'] . '/activate-module') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="module" value="<?= htmlspecialchars($slug) ?>">
                    <button type="submit" class="inline-flex items-center text-sm font-medium <?= $colors['text'] ?> hover:underline">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Attiva modulo
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal selezione tipo -->
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="open = false">
            <!-- Backdrop -->
            <div x-show="open" @click="open = false"
                 class="fixed inset-0 bg-black/50"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"></div>
            <!-- Panel -->
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div x-show="open"
                     @click.away="open = false"
                     class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95">
                    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white" x-text="'Attiva ' + moduleLabel"></h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Seleziona il tipo di progetto da creare</p>
                    </div>
                    <div class="p-5 space-y-3">
                        <template x-for="(typeInfo, typeKey) in types" :key="typeKey">
                            <form method="POST" action="<?= url('/projects/' . $project['id'] . '/activate-module') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="module" :value="moduleSlug">
                                <input type="hidden" name="type" :value="typeKey">
                                <button type="submit"
                                        class="w-full text-left p-4 rounded-lg border-2 border-slate-200 dark:border-slate-600 hover:border-primary-500 dark:hover:border-primary-400 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all group">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30 transition-colors">
                                            <svg class="w-5 h-5 text-slate-500 dark:text-slate-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="typeInfo.icon"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-900 dark:text-white" x-text="typeInfo.label"></p>
                                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5" x-text="typeInfo.description"></p>
                                        </div>
                                    </div>
                                </button>
                            </form>
                        </template>
                    </div>
                    <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-700">
                        <button @click="open = false" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">
                            Annulla
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script>
    function activationModal() {
        const allTypes = <?= json_encode($_moduleTypes) ?>;
        const labels = <?= json_encode(array_combine(array_keys($moduleConfig), array_column($moduleConfig, 'label'))) ?>;
        const activeTypesMap = <?= json_encode($activeTypesPerModule ?? []) ?>;
        return {
            open: false,
            moduleSlug: '',
            moduleLabel: '',
            types: {},
            openTypeModal(slug) {
                this.moduleSlug = slug;
                this.moduleLabel = labels[slug] || slug;
                const activeForSlug = activeTypesMap[slug] || [];
                const available = {};
                for (const [key, val] of Object.entries(allTypes[slug] || {})) {
                    if (!activeForSlug.includes(key)) {
                        available[key] = val;
                    }
                }
                this.types = available;
                this.open = true;
            }
        };
    }
    </script>

    </div><!-- /x-data activationModal -->

    <!-- CMS Connector Section -->
    <div x-data="connectorManager()" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <!-- Section Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white text-sm">Connessione CMS</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Collega il tuo WordPress per analisi dirette senza scraping</p>
                </div>
            </div>
            <?php if (!empty($connectors)): ?>
            <button @click="showForm = !showForm" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors">
                <span x-text="showForm ? 'Chiudi' : 'Aggiungi'"></span>
            </button>
            <?php endif; ?>
        </div>

        <?php if (!empty($connectors)): ?>
        <!-- Existing Connectors -->
        <div class="space-y-3 mb-4">
            <?php foreach ($connectors as $conn):
                $connConfig = json_decode($conn['config'], true) ?: [];
                $connUrl = $connConfig['url'] ?? '';
                $connDomain = parse_url($connUrl, PHP_URL_HOST) ?: $connUrl;
                $isSuccess = ($conn['last_test_status'] ?? '') === 'success';
                $isError = ($conn['last_test_status'] ?? '') === 'error';
                $hasTest = !empty($conn['last_test_at']);
            ?>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <!-- WP Icon -->
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 1.5c4.687 0 8.5 3.813 8.5 8.5 0 4.687-3.813 8.5-8.5 8.5-4.687 0-8.5-3.813-8.5-8.5 0-4.687 3.813-8.5 8.5-8.5zM4.356 12c0 1.357.353 2.633.972 3.742L4.09 9.26A8.458 8.458 0 004.356 12zm7.644 8c-1.21 0-2.363-.26-3.406-.724l3.617-10.504 3.705 10.148a.56.56 0 00.043.077A7.965 7.965 0 0112 20zm1.31-11.744l2.98 8.868 1.072-3.215c.428-1.054.77-1.87.77-2.558 0-.978-.352-1.653-.653-2.18-.404-.655-.78-1.207-.78-1.86 0-.73.552-1.408 1.332-1.408.035 0 .068.004.103.007A7.965 7.965 0 0012 4c-2.178 0-4.153.87-5.597 2.28.157.005.305.008.432.008.702 0 1.79-.085 1.79-.085.362-.021.405.51.043.553 0 0-.364.043-.77.064l3.412 10.146z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-medium text-sm text-slate-900 dark:text-white truncate"><?= htmlspecialchars($conn['name']) ?></p>
                                <span class="text-xs text-slate-500 dark:text-slate-400">(<?= htmlspecialchars($connDomain) ?>)</span>
                            </div>
                            <!-- CMS details -->
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <?php if (!empty($conn['seo_plugin'])): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                    <?= htmlspecialchars(ucwords(str_replace('-', ' ', $conn['seo_plugin']))) ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($conn['wp_version'])): ?>
                                <span class="text-xs text-slate-500 dark:text-slate-400">WP <?= htmlspecialchars($conn['wp_version']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($conn['plugin_version'])): ?>
                                <span class="text-xs text-slate-500 dark:text-slate-400">Plugin v<?= htmlspecialchars($conn['plugin_version']) ?></span>
                                <?php endif; ?>
                            </div>
                            <!-- Test status -->
                            <div class="flex items-center gap-2 mt-1.5">
                                <?php if ($hasTest): ?>
                                <span class="text-xs <?= $isSuccess ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>">
                                    <?php if ($isSuccess): ?>
                                        <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Connesso
                                    <?php else: ?>
                                        <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                        Errore
                                    <?php endif; ?>
                                </span>
                                <span class="text-xs text-slate-400 dark:text-slate-500">&middot; <?= _dashboard_time_ago($conn['last_test_at']) ?></span>
                                <?php else: ?>
                                <span class="text-xs text-slate-400 dark:text-slate-500">Mai testato</span>
                                <?php endif; ?>
                            </div>
                            <!-- AJAX test result -->
                            <template x-if="testResults[<?= $conn['id'] ?>]">
                                <div class="mt-1.5">
                                    <span x-show="testResults[<?= $conn['id'] ?>]?.success" class="text-xs text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Connessione riuscita
                                    </span>
                                    <span x-show="!testResults[<?= $conn['id'] ?>]?.success" class="text-xs text-red-600 dark:text-red-400">
                                        <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        <span x-text="testResults[<?= $conn['id'] ?>]?.message || 'Errore'"></span>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                    <!-- Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button @click="testConnection(<?= $conn['id'] ?>)"
                                :disabled="testing[<?= $conn['id'] ?>]"
                                class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors disabled:opacity-50">
                            <template x-if="testing[<?= $conn['id'] ?>]">
                                <svg class="animate-spin w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                            <template x-if="!testing[<?= $conn['id'] ?>]">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </template>
                            Testa
                        </button>
                        <button @click="confirmRemove(<?= $conn['id'] ?>)"
                                class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Add Connector Form -->
        <div x-show="showForm" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <?php if (!empty($connectors)): ?>
            <div class="border-t border-slate-200 dark:border-slate-700 pt-4 mb-3">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Aggiungi connettore</p>
            </div>
            <?php endif; ?>
            <form method="POST" action="<?= url('/projects/' . $project['id'] . '/connectors') ?>" class="space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="wordpress">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="conn_name" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Nome sito</label>
                        <input type="text" id="conn_name" name="name" required placeholder="Il Mio Blog"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label for="conn_url" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">URL sito</label>
                        <input type="url" id="conn_url" name="url" required placeholder="https://esempio.com"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label for="conn_api_key" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">API Key</label>
                        <input type="password" id="conn_api_key" name="api_key" required placeholder="stk_..."
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors">
                    </div>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <a href="<?= url('/projects/download-plugin/wordpress') ?>" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Scarica Plugin WordPress
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Connetti WordPress
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function connectorManager() {
        return {
            showForm: <?= empty($connectors) ? 'true' : 'false' ?>,
            testing: {},
            testResults: {},

            async testConnection(connectorId) {
                this.testing[connectorId] = true;
                this.testResults[connectorId] = null;
                try {
                    const formData = new FormData();
                    formData.append('_csrf_token', '<?= csrf_token() ?>');
                    formData.append('connector_id', connectorId);
                    const resp = await fetch('<?= url('/projects/' . $project['id'] . '/connectors/test') ?>', {
                        method: 'POST',
                        body: formData
                    });
                    if (!resp.ok) {
                        this.testResults[connectorId] = { success: false, message: 'Errore del server (' + resp.status + ')' };
                    } else {
                        const data = await resp.json();
                        this.testResults[connectorId] = data;
                    }
                } catch (e) {
                    this.testResults[connectorId] = { success: false, message: 'Errore di connessione' };
                }
                this.testing[connectorId] = false;
            },

            confirmRemove(connectorId) {
                if (confirm('Rimuovere questo connettore? L\'azione non puo essere annullata.')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '<?= url('/projects/' . $project['id'] . '/connectors/remove') ?>';
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_csrf_token';
                    csrf.value = '<?= csrf_token() ?>';
                    const id = document.createElement('input');
                    id.type = 'hidden';
                    id.name = 'connector_id';
                    id.value = connectorId;
                    form.appendChild(csrf);
                    form.appendChild(id);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        };
    }
    </script>

    <!-- Project Description (if set) -->
    <?php if (!empty($project['description'])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Descrizione</h3>
        <p class="text-sm text-slate-700 dark:text-slate-300"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
    </div>
    <?php endif; ?>
</div>
