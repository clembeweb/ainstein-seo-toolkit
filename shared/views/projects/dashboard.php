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
                    <?php if (($access_role ?? 'owner') !== 'owner'): ?>
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                 <?= ($access_role ?? 'owner') === 'editor' ? 'bg-blue-500/20 text-blue-400' : 'bg-slate-500/20 text-slate-400' ?>">
                        <?= ($access_role ?? 'owner') === 'editor' ? 'Editor' : 'Sola lettura' ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($project['domain'])): ?>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 ml-6"><?= htmlspecialchars($project['domain']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if (($access_role ?? 'owner') === 'owner'): ?>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/projects/' . $project['id'] . '/settings') ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Impostazioni
            </a>
        </div>
        <?php endif; ?>
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

                <?php if ($hasRemaining && ($access_role ?? 'owner') === 'owner'): ?>
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

                <?php if ($hasRemaining && ($access_role ?? 'owner') === 'owner'): ?>
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

    <!-- Available (non-activated) Modules — owner only -->
    <?php if (!empty($nonActivated) && ($access_role ?? 'owner') === 'owner'): ?>
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

    <!-- Modal selezione tipo — owner only -->
    <?php if (($access_role ?? 'owner') === 'owner'): ?>
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
    <?php endif; ?><!-- /owner-only modal -->

    <script>
    function activationModal() {
        <?php if (($access_role ?? 'owner') === 'owner'): ?>
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
        <?php else: ?>
        return { open: false, moduleSlug: '', moduleLabel: '', types: {}, openTypeModal() {} };
        <?php endif; ?>
    }
    </script>

    </div><!-- /x-data activationModal -->

    <!-- WordPress Sites Section — owner only -->
    <?php if (($access_role ?? 'owner') === 'owner'): ?>
    <?php
        // Domain match suggestion: find unlinked sites matching project domain (confronto esatto)
        $suggestedSite = null;
        $projectDomain = $project['domain'] ?? '';
        if (!empty($projectDomain) && !empty($unlinkedWpSites)) {
            $cleanProjectDomain = preg_replace('#^www\.#i', '', strtolower($projectDomain));
            foreach ($unlinkedWpSites as $uSite) {
                $siteDomain = parse_url($uSite['url'], PHP_URL_HOST) ?: '';
                $cleanSiteDomain = preg_replace('#^www\.#i', '', strtolower($siteDomain));
                if ($cleanSiteDomain && $cleanSiteDomain === $cleanProjectDomain) {
                    $suggestedSite = $uSite;
                    break;
                }
            }
        }
    ?>
    <div x-data="wpSiteManager()" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <!-- Section Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white text-sm">Siti WordPress</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Collega il tuo WordPress per pubblicazione e analisi SEO dirette</p>
                </div>
            </div>
            <?php if (!empty($wpSites)): ?>
            <button @click="showForm = !showForm" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors">
                <span x-text="showForm ? 'Chiudi' : 'Aggiungi'"></span>
            </button>
            <?php endif; ?>
        </div>

        <?php if (!empty($suggestedSite) && empty($wpSites)): ?>
        <!-- Domain match suggestion -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Hai gi&agrave; il sito <strong><?= htmlspecialchars($suggestedSite['name']) ?></strong> (<?= htmlspecialchars(parse_url($suggestedSite['url'], PHP_URL_HOST)) ?>)
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">Corrisponde al dominio di questo progetto. Vuoi collegarlo?</p>
                    <form method="POST" action="<?= url('/projects/' . $project['id'] . '/wp-sites/link') ?>" class="mt-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="site_id" value="<?= $suggestedSite['id'] ?>">
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            Collega e verifica connessione
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($wpSites)): ?>
        <!-- Linked WordPress Sites -->
        <div class="space-y-3 mb-4">
            <?php foreach ($wpSites as $site):
                $siteDomain = parse_url($site['url'], PHP_URL_HOST) ?: $site['url'];
                $catCount = !empty($site['categories']) ? count($site['categories']) : 0;
                $apiKeyPreview = substr($site['api_key'], 0, 8) . '...' . substr($site['api_key'], -4);
                // Status connessione
                $testStatus = $site['last_test_status'] ?? null;
                $testAt = $site['last_test_at'] ?? null;
                if ($testStatus === 'success') {
                    $statusColor = 'bg-emerald-400';
                    $statusTitle = 'Connessione OK' . ($testAt ? ' — ' . _dashboard_time_ago($testAt) : '');
                } elseif ($testStatus === 'error') {
                    $statusColor = 'bg-red-400';
                    $statusTitle = 'Connessione fallita' . ($testAt ? ' — ' . _dashboard_time_ago($testAt) : '');
                } else {
                    $statusColor = 'bg-slate-400';
                    $statusTitle = 'Mai testato';
                }
            ?>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <!-- WP Icon con indicatore stato -->
                        <div class="relative w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 1.5c4.687 0 8.5 3.813 8.5 8.5 0 4.687-3.813 8.5-8.5 8.5-4.687 0-8.5-3.813-8.5-8.5 0-4.687 3.813-8.5 8.5-8.5z"/>
                            </svg>
                            <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full <?= $statusColor ?> ring-2 ring-white dark:ring-slate-700" title="<?= htmlspecialchars($statusTitle) ?>"></span>
                        </div>
                        <div class="min-w-0">
                            <!-- Nome (editabile inline) -->
                            <div x-show="!editing[<?= $site['id'] ?>]" class="flex items-center gap-2 flex-wrap">
                                <p class="font-medium text-sm text-slate-900 dark:text-white truncate"><?= htmlspecialchars($site['name']) ?></p>
                                <a href="<?= htmlspecialchars($site['url']) ?>" target="_blank" class="text-xs text-indigo-500 dark:text-indigo-400 hover:underline"><?= htmlspecialchars($siteDomain) ?></a>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium <?= $site['is_active'] ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-400' ?>">
                                    <?= $site['is_active'] ? 'Attivo' : 'Disattivato' ?>
                                </span>
                            </div>
                            <!-- Info riga -->
                            <div x-show="!editing[<?= $site['id'] ?>]" class="flex items-center gap-3 mt-1 text-xs text-slate-500 dark:text-slate-400">
                                <?php if ($catCount > 0): ?>
                                <span><?= $catCount ?> categorie</span>
                                <?php endif; ?>
                                <?php if (!empty($site['last_sync_at'])): ?>
                                <span>Sync: <?= _dashboard_time_ago($site['last_sync_at']) ?></span>
                                <?php endif; ?>
                                <span class="font-mono text-xs"><?= htmlspecialchars($apiKeyPreview) ?></span>
                            </div>

                            <!-- Edit inline form -->
                            <div x-show="editing[<?= $site['id'] ?>]" x-cloak class="mt-1">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2">
                                        <input type="text" x-model="editData[<?= $site['id'] ?>].name"
                                               class="flex-1 px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                               placeholder="Nome sito">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="password" x-model="editData[<?= $site['id'] ?>].api_key"
                                               class="flex-1 px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono"
                                               placeholder="Nuova API Key (lascia vuoto per mantenere)">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="saveEdit(<?= $site['id'] ?>)" :disabled="saving[<?= $site['id'] ?>]"
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-indigo-600 hover:bg-indigo-700 text-white transition-colors disabled:opacity-50">
                                            <template x-if="saving[<?= $site['id'] ?>]">
                                                <svg class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            </template>
                                            Salva
                                        </button>
                                        <button @click="editing[<?= $site['id'] ?>] = false"
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
                                            Annulla
                                        </button>
                                        <span x-show="editResult[<?= $site['id'] ?>]?.success" x-cloak class="text-xs text-emerald-600 dark:text-emerald-400" x-text="editResult[<?= $site['id'] ?>]?.message"></span>
                                        <span x-show="editResult[<?= $site['id'] ?>] && !editResult[<?= $site['id'] ?>]?.success" x-cloak class="text-xs text-red-600 dark:text-red-400" x-text="editResult[<?= $site['id'] ?>]?.message"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- AJAX test result arricchito -->
                            <template x-if="testResults[<?= $site['id'] ?>]">
                                <div class="mt-1.5">
                                    <template x-if="testResults[<?= $site['id'] ?>]?.success">
                                        <div class="text-xs text-emerald-600 dark:text-emerald-400">
                                            <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Connessione OK
                                            <template x-if="testResults[<?= $site['id'] ?>]?.wp_version">
                                                <span class="text-slate-500 dark:text-slate-400">
                                                    — WP <span x-text="testResults[<?= $site['id'] ?>]?.wp_version"></span><template x-if="testResults[<?= $site['id'] ?>]?.seo_plugin && testResults[<?= $site['id'] ?>]?.seo_plugin !== 'none'"><span>, <span x-text="testResults[<?= $site['id'] ?>]?.seo_plugin"></span></span></template>
                                                </span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!testResults[<?= $site['id'] ?>]?.success">
                                        <div class="text-xs text-red-600 dark:text-red-400">
                                            <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <span x-text="testResults[<?= $site['id'] ?>]?.message || 'Errore di connessione'"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                    <!-- Actions -->
                    <div x-show="!editing[<?= $site['id'] ?>]" class="flex items-center gap-1.5 flex-shrink-0">
                        <button @click="testSite(<?= $site['id'] ?>)"
                                :disabled="testing[<?= $site['id'] ?>]"
                                class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors disabled:opacity-50" title="Testa connessione">
                            <template x-if="testing[<?= $site['id'] ?>]">
                                <svg class="animate-spin w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                            <template x-if="!testing[<?= $site['id'] ?>]">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </template>
                            Testa
                        </button>
                        <button @click="startEdit(<?= $site['id'] ?>, '<?= htmlspecialchars(addslashes($site['name']), ENT_QUOTES) ?>')"
                                class="inline-flex items-center px-2 py-1.5 rounded-lg text-xs font-medium text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-600/50 transition-colors" title="Modifica nome o API Key">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button @click="confirmUnlink(<?= $site['id'] ?>, '<?= htmlspecialchars(addslashes($site['name']), ENT_QUOTES) ?>')"
                                class="inline-flex items-center px-2 py-1.5 rounded-lg text-xs font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 transition-colors" title="Scollega dal progetto">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Link existing site or Add new -->
        <div x-show="showForm" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <?php if (!empty($wpSites)): ?>
            <div class="border-t border-slate-200 dark:border-slate-700 pt-4 mb-3">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Aggiungi sito WordPress</p>
            </div>
            <?php endif; ?>

            <?php if (!empty($unlinkedWpSites)): ?>
            <!-- Link existing WP site -->
            <div class="bg-blue-50 dark:bg-blue-900/10 rounded-xl p-4 mb-3">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Collega un sito esistente</p>
                <form method="POST" action="<?= url('/projects/' . $project['id'] . '/wp-sites/link') ?>" class="flex items-end gap-3">
                    <?= csrf_field() ?>
                    <div class="flex-1">
                        <select name="site_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors">
                            <?php foreach ($unlinkedWpSites as $uSite): ?>
                            <option value="<?= $uSite['id'] ?>"><?= htmlspecialchars($uSite['name']) ?> (<?= htmlspecialchars(parse_url($uSite['url'], PHP_URL_HOST)) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Collega e verifica
                    </button>
                </form>
            </div>
            <p class="text-xs text-center text-slate-400 dark:text-slate-500 my-3">oppure</p>
            <?php endif; ?>

            <!-- Add new WP site form -->
            <form method="POST" action="<?= url('/projects/' . $project['id'] . '/wp-sites') ?>" class="space-y-3">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="wp_name" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Nome sito</label>
                        <input type="text" id="wp_name" name="name" required placeholder="Il Mio Blog"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label for="wp_url" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">URL sito</label>
                        <input type="url" id="wp_url" name="url" required placeholder="https://esempio.com"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label for="wp_api_key" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">API Key</label>
                        <input type="password" id="wp_api_key" name="api_key" required placeholder="stk_..."
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Aggiungi e collega
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function wpSiteManager() {
        return {
            showForm: <?= empty($wpSites) ? 'true' : 'false' ?>,
            testing: {},
            testResults: {},
            editing: {},
            editData: {},
            saving: {},
            editResult: {},

            async testSite(siteId) {
                this.testing[siteId] = true;
                this.testResults[siteId] = null;
                try {
                    const formData = new FormData();
                    formData.append('_csrf_token', '<?= csrf_token() ?>');
                    formData.append('site_id', siteId);
                    const resp = await fetch('<?= url('/projects/' . $project['id'] . '/wp-sites/test') ?>', {
                        method: 'POST',
                        body: formData
                    });
                    if (!resp.ok) {
                        this.testResults[siteId] = { success: false, message: 'Errore del server (' + resp.status + ')' };
                    } else {
                        this.testResults[siteId] = await resp.json();
                    }
                } catch (e) {
                    this.testResults[siteId] = { success: false, message: 'Errore di connessione' };
                }
                this.testing[siteId] = false;
            },

            startEdit(siteId, currentName) {
                this.editing[siteId] = true;
                this.editData[siteId] = { name: currentName, api_key: '' };
                this.editResult[siteId] = null;
            },

            async saveEdit(siteId) {
                this.saving[siteId] = true;
                this.editResult[siteId] = null;
                try {
                    const formData = new FormData();
                    formData.append('_csrf_token', '<?= csrf_token() ?>');
                    formData.append('site_id', siteId);
                    if (this.editData[siteId].name) {
                        formData.append('name', this.editData[siteId].name);
                    }
                    if (this.editData[siteId].api_key) {
                        formData.append('api_key', this.editData[siteId].api_key);
                    }
                    const resp = await fetch('<?= url('/projects/' . $project['id'] . '/wp-sites/update') ?>', {
                        method: 'POST',
                        body: formData
                    });
                    if (!resp.ok) {
                        this.editResult[siteId] = { success: false, message: 'Errore del server' };
                    } else {
                        const data = await resp.json();
                        this.editResult[siteId] = data;
                        if (data.success) {
                            // Ricarica pagina per vedere le modifiche
                            setTimeout(() => window.location.reload(), 800);
                        }
                    }
                } catch (e) {
                    this.editResult[siteId] = { success: false, message: 'Errore di connessione' };
                }
                this.saving[siteId] = false;
            },

            confirmUnlink(siteId, siteName) {
                if (confirm('Scollegare "' + siteName + '" da questo progetto? Il sito non verr\u00e0 eliminato.')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '<?= url('/projects/' . $project['id'] . '/wp-sites/unlink') ?>';
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_csrf_token';
                    csrf.value = '<?= csrf_token() ?>';
                    const id = document.createElement('input');
                    id.type = 'hidden';
                    id.name = 'site_id';
                    id.value = siteId;
                    form.appendChild(csrf);
                    form.appendChild(id);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        };
    }
    </script>
    <?php endif; ?><!-- /owner-only WordPress Sites -->

    <!-- Project Description (if set) -->
    <?php if (!empty($project['description'])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Descrizione</h3>
        <p class="text-sm text-slate-700 dark:text-slate-300"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
    </div>
    <?php endif; ?>
</div>
