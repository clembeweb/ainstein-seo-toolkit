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

// Slugs dei moduli attivi per quick-check
$activeSlugs = array_column($activeModules, 'slug');

// Slugs dei moduli attivi nel sistema (ModuleLoader)
$systemActiveSlugs = array_column($availableModules, 'slug');
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

    <?php if (!empty($activeModules)): ?>
    <!-- Active Modules KPI Section -->
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Moduli attivi</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <?php foreach ($activeModules as $module):
                $slug = $module['slug'];
                $color = $module['color'] ?? 'blue';
                $colors = $colorMap[$color] ?? $colorMap['blue'];
                $stats = $moduleStats[$slug] ?? ['metrics' => [], 'lastActivity' => null];
                $metrics = $stats['metrics'] ?? [];
                $moduleLink = url($module['route_prefix'] . '/' . $module['module_project_id']);
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5" style="border-left: 4px solid <?= htmlspecialchars($colors['border']) ?>">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg <?= $colors['bg'] ?> flex items-center justify-center">
                            <svg class="w-5 h-5 <?= $colors['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= htmlspecialchars($module['icon'] ?? '') ?>"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($module['label']) ?></h3>
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
                        <p class="text-lg font-semibold text-slate-900 dark:text-white mt-0.5">
                            <?php
                            $value = $metric['value'] ?? 0;
                            if (is_float($value)) {
                                echo number_format($value, 1);
                            } elseif (is_numeric($value)) {
                                echo number_format((int)$value);
                            } else {
                                echo htmlspecialchars((string)$value);
                            }
                            ?>
                        </p>
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
                    <?php
                    $lastActivity = strtotime($stats['lastActivity']);
                    $diff = time() - $lastActivity;
                    if ($diff < 60) {
                        $timeAgo = 'Adesso';
                    } elseif ($diff < 3600) {
                        $timeAgo = floor($diff / 60) . ' min fa';
                    } elseif ($diff < 86400) {
                        $timeAgo = floor($diff / 3600) . ' ore fa';
                    } elseif ($diff < 604800) {
                        $timeAgo = floor($diff / 86400) . ' giorni fa';
                    } else {
                        $timeAgo = date('d/m/Y', $lastActivity);
                    }
                    ?>
                    Ultima attivit&agrave;: <?= $timeAgo ?>
                </p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <!-- No modules activated yet -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
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
    <?php
    // Filtra moduli disponibili non ancora attivati
    $nonActivated = [];
    foreach ($moduleConfig as $slug => $config) {
        if (!in_array($slug, $activeSlugs) && in_array($slug, $systemActiveSlugs)) {
            $nonActivated[$slug] = $config;
        }
    }
    ?>

    <?php if (!empty($nonActivated)): ?>
    <?php
    // Tipi modulo per modal selezione
    $_moduleTypes = $moduleTypes ?? [];
    ?>
    <div x-data="activationModal()">
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
    </div>

    <script>
    function activationModal() {
        const allTypes = <?= json_encode($_moduleTypes) ?>;
        const labels = <?= json_encode(array_combine(array_keys($moduleConfig), array_column($moduleConfig, 'label'))) ?>;
        return {
            open: false,
            moduleSlug: '',
            moduleLabel: '',
            types: {},
            openTypeModal(slug) {
                this.moduleSlug = slug;
                this.moduleLabel = labels[slug] || slug;
                this.types = allTypes[slug] || {};
                this.open = true;
            }
        };
    }
    </script>
    <?php endif; ?>

    <!-- Project Description (if set) -->
    <?php if (!empty($project['description'])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Descrizione</h3>
        <p class="text-sm text-slate-700 dark:text-slate-300"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
    </div>
    <?php endif; ?>
</div>
