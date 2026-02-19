<?php
// === Data maps ===
$moduleIcons = [
    'ai-content' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    'seo-audit' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    'seo-tracking' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    'keyword-research' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
    'ads-analyzer' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
    'internal-links' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
    'content-creator' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
];
$defaultIcon = 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z';

$moduleColors = [
    'ai-content' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-600 dark:text-amber-400', 'border' => 'border-amber-200 dark:border-amber-700', 'borderL' => 'border-l-amber-400', 'hover' => 'hover:border-amber-300 dark:hover:border-amber-600', 'ring' => 'hover:ring-amber-200 dark:hover:ring-amber-800'],
    'seo-audit' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-600 dark:text-emerald-400', 'border' => 'border-emerald-200 dark:border-emerald-700', 'borderL' => 'border-l-emerald-400', 'hover' => 'hover:border-emerald-300 dark:hover:border-emerald-600', 'ring' => 'hover:ring-emerald-200 dark:hover:ring-emerald-800'],
    'seo-tracking' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-600 dark:text-blue-400', 'border' => 'border-blue-200 dark:border-blue-700', 'borderL' => 'border-l-blue-400', 'hover' => 'hover:border-blue-300 dark:hover:border-blue-600', 'ring' => 'hover:ring-blue-200 dark:hover:ring-blue-800'],
    'keyword-research' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-600 dark:text-purple-400', 'border' => 'border-purple-200 dark:border-purple-700', 'borderL' => 'border-l-purple-400', 'hover' => 'hover:border-purple-300 dark:hover:border-purple-600', 'ring' => 'hover:ring-purple-200 dark:hover:ring-purple-800'],
    'ads-analyzer' => ['bg' => 'bg-rose-100 dark:bg-rose-900/30', 'text' => 'text-rose-600 dark:text-rose-400', 'border' => 'border-rose-200 dark:border-rose-700', 'borderL' => 'border-l-rose-400', 'hover' => 'hover:border-rose-300 dark:hover:border-rose-600', 'ring' => 'hover:ring-rose-200 dark:hover:ring-rose-800'],
    'internal-links' => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/30', 'text' => 'text-cyan-600 dark:text-cyan-400', 'border' => 'border-cyan-200 dark:border-cyan-700', 'borderL' => 'border-l-cyan-400', 'hover' => 'hover:border-cyan-300 dark:hover:border-cyan-600', 'ring' => 'hover:ring-cyan-200 dark:hover:ring-cyan-800'],
    'content-creator' => ['bg' => 'bg-indigo-100 dark:bg-indigo-900/30', 'text' => 'text-indigo-600 dark:text-indigo-400', 'border' => 'border-indigo-200 dark:border-indigo-700', 'borderL' => 'border-l-indigo-400', 'hover' => 'hover:border-indigo-300 dark:hover:border-indigo-600', 'ring' => 'hover:ring-indigo-200 dark:hover:ring-indigo-800'],
];
$defaultColor = ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-600 dark:text-slate-400', 'border' => 'border-slate-200 dark:border-slate-700', 'borderL' => 'border-l-slate-400', 'hover' => 'hover:border-slate-300 dark:hover:border-slate-600', 'ring' => 'hover:ring-slate-200 dark:hover:ring-slate-800'];

$moduleNames = [
    'ai-content' => 'AI Content',
    'seo-audit' => 'SEO Audit',
    'seo-tracking' => 'Position Tracking',
    'keyword-research' => 'Keyword Research',
    'ads-analyzer' => 'Google Ads',
    'internal-links' => 'Internal Links',
    'content-creator' => 'Content Creator',
];

// === Helpers ===
$_credits = (float)($user['credits'] ?? 0);
$_pipe = $pipelineData ?? [];
$_w = $widgetData ?? [];
$_hour = (int) date('H');
$_greeting = $_hour < 12 ? 'Buongiorno' : ($_hour < 18 ? 'Buon pomeriggio' : 'Buonasera');
$isNewUser = ($projectsCount ?? 0) === 0;
$_moduleSlugs = array_column($modules ?? [], 'slug');
?>

<div class="space-y-8">

    <!-- ========== HEADER ========== -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= $_greeting ?>, <?= htmlspecialchars($user['name'] ?? 'Utente') ?>!</h1>
            <?php if (!$isNewUser): ?>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                Crediti usati oggi: <span class="font-medium text-slate-700 dark:text-slate-300"><?= number_format($usageToday, 1) ?></span>
                <span class="mx-1.5 text-slate-300 dark:text-slate-600">&middot;</span>
                Questo mese: <span class="font-medium text-slate-700 dark:text-slate-300"><?= number_format($usageMonth, 1) ?></span>
            </p>
            <?php else: ?>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Benvenuto su Ainstein! Scegli uno strumento per iniziare.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isNewUser): ?>
    <!-- ========================================================================
         MODALITA LAUNCHPAD (utente nuovo, 0 progetti)
         ======================================================================== -->

    <?php
    $launchpadCards = [
        [
            'slug' => 'keyword-research',
            'task' => 'Trovare keyword strategiche',
            'desc' => 'L\'AI analizza il tuo settore, espande le keyword e le raggruppa per intento di ricerca.',
            'cost' => 'Da 2 crediti',
            'step' => '1',
        ],
        [
            'slug' => 'ai-content',
            'task' => 'Scrivere articoli SEO per il tuo blog',
            'desc' => 'Dai la keyword, Ainstein studia i top Google, scrive e pubblica su WordPress.',
            'cost' => 'Da 3 crediti',
            'step' => '2',
        ],
        [
            'slug' => 'seo-audit',
            'task' => 'Scoprire cosa migliorare nel tuo sito',
            'desc' => 'Audit completo con piano d\'azione ordinato per impatto.',
            'cost' => 'Da 2 crediti',
            'step' => '3',
        ],
        [
            'slug' => 'seo-tracking',
            'task' => 'Monitorare le posizioni su Google',
            'desc' => 'Tracking keyword giornaliero con dati click reali da Google Search Console.',
            'cost' => 'Da 1 credito',
            'step' => '4',
        ],
        [
            'slug' => 'ads-analyzer',
            'task' => 'Analizzare o creare campagne Google Ads',
            'desc' => 'Trova sprechi di budget o crea campagne complete da zero con l\'AI.',
            'cost' => 'Da 2 crediti',
            'step' => '5',
        ],
    ];
    ?>

    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Cosa vuoi fare?</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">Ogni strumento ti guida passo-passo. Scegli quello che ti serve.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($launchpadCards as $card):
                if (!in_array($card['slug'], $_moduleSlugs)) continue;
                $color = $moduleColors[$card['slug']] ?? $defaultColor;
                $icon = $moduleIcons[$card['slug']] ?? $defaultIcon;
            ?>
            <a href="<?= url('/' . $card['slug']) ?>"
               class="group relative bg-white dark:bg-slate-800 rounded-xl border <?= $color['border'] ?> p-5 shadow-sm <?= $color['hover'] ?> hover:ring-1 <?= $color['ring'] ?> hover:shadow-md transition-all duration-200">
                <!-- Step number -->
                <div class="absolute top-3 right-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-700 text-[10px] font-bold text-slate-400 dark:text-slate-500"><?= $card['step'] ?></span>
                </div>

                <!-- Icon -->
                <div class="h-11 w-11 rounded-xl <?= $color['bg'] ?> flex items-center justify-center mb-4">
                    <svg class="h-5 w-5 <?= $color['text'] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/>
                    </svg>
                </div>

                <!-- Content -->
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1.5 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                    <?= $card['task'] ?>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4">
                    <?= $card['desc'] ?>
                </p>

                <!-- Footer -->
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider"><?= $card['cost'] ?></span>
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 dark:text-primary-400 group-hover:translate-x-0.5 transition-transform">
                        Inizia
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Suggerimento docs per nuovi utenti -->
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 px-5 py-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-primary-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm text-slate-700 dark:text-slate-300">
                    Primo accesso? Leggi la <a href="<?= url('/docs/getting-started') ?>" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">guida rapida</a> per capire come funziona Ainstein in 2 minuti.
                </p>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ========================================================================
         MODALITA TASK MANAGER (utente attivo, 1+ progetti)
         ======================================================================== -->

    <?php
    // --- Build smart actions ---
    $smartActions = [];

    // Crediti critici
    if ($_credits < 3) {
        $smartActions[] = [
            'text' => 'Crediti quasi esauriti (' . number_format($_credits, 1) . '). Ricarica per continuare a usare gli strumenti.',
            'cta' => 'Ricarica',
            'url' => url('/profile'),
            'slug' => '_credits_critical',
            'iconPath' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
            'color' => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-500', 'borderL' => 'border-l-red-400'],
        ];
    }

    // Articoli pronti da pubblicare
    if (($_pipe['aic_ready'] ?? 0) > 0) {
        $n = $_pipe['aic_ready'];
        $smartActions[] = [
            'text' => $n . ' articol' . ($n > 1 ? 'i pronti' : 'o pronto') . ' da pubblicare su WordPress.',
            'cta' => 'Pubblica',
            'url' => url('/ai-content'),
            'slug' => 'ai-content',
        ];
    }

    // WordPress non collegato
    if (!($_pipe['wp_connected'] ?? false) && ($_pipe['aic_keywords'] ?? 0) > 0) {
        $smartActions[] = [
            'text' => 'Collega WordPress per pubblicare automaticamente gli articoli generati.',
            'cta' => 'Collega',
            'url' => url('/ai-content/wordpress'),
            'slug' => 'ai-content',
        ];
    }

    // Keyword in coda senza articoli
    if (($_pipe['aic_keywords'] ?? 0) > 0 && ($_pipe['aic_articles'] ?? 0) === 0) {
        $smartActions[] = [
            'text' => 'Hai keyword in coda ma nessun articolo generato. Avvia la generazione!',
            'cta' => 'Genera',
            'url' => url('/ai-content'),
            'slug' => 'ai-content',
        ];
    }

    // Audit con problemi
    $saWidget = $_w['seo-audit'] ?? null;
    if ($saWidget && ($saWidget['issues'] ?? 0) > 0) {
        $smartActions[] = [
            'text' => $saWidget['issues'] . ' problemi trovati nell\'ultimo audit SEO. Vedi il piano d\'azione.',
            'cta' => 'Vedi piano',
            'url' => url('/seo-audit'),
            'slug' => 'seo-audit',
        ];
    }

    // GSC non collegato
    $stWidget = $_w['seo-tracking'] ?? null;
    if ($stWidget && ($stWidget['keywords'] ?? 0) > 0 && !($stWidget['gsc_connected'] ?? false)) {
        $smartActions[] = [
            'text' => 'Collega Google Search Console per visualizzare click e impression reali.',
            'cta' => 'Collega',
            'url' => url('/seo-tracking'),
            'slug' => 'seo-tracking',
        ];
    }

    // Crediti in esaurimento (non critico)
    if ($_credits >= 3 && $_credits < 10) {
        $smartActions[] = [
            'text' => 'Crediti in esaurimento (' . number_format($_credits, 1) . '). Considera una ricarica.',
            'cta' => 'Ricarica',
            'url' => url('/profile'),
            'slug' => '_credits_low',
            'iconPath' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
            'color' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-500', 'borderL' => 'border-l-amber-400'],
        ];
    }

    $smartActions = array_slice($smartActions, 0, 5);
    ?>

    <!-- === SEZIONE: Da fare === -->
    <?php if (!empty($smartActions)): ?>
    <div>
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Da fare</h2>
        </div>

        <div class="space-y-2.5">
            <?php foreach ($smartActions as $action):
                // Determina colori: usa quelli custom se presenti, altrimenti dal modulo
                if (isset($action['color'])) {
                    $aBg = $action['color']['bg'];
                    $aText = $action['color']['text'];
                    $aBorderL = $action['color']['borderL'];
                    $aIcon = $action['iconPath'] ?? 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z';
                } else {
                    $aColor = $moduleColors[$action['slug']] ?? $defaultColor;
                    $aBg = $aColor['bg'];
                    $aText = $aColor['text'];
                    $aBorderL = $aColor['borderL'];
                    $aIcon = $moduleIcons[$action['slug']] ?? $defaultIcon;
                }
            ?>
            <div class="flex items-center justify-between bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 <?= $aBorderL ?> px-4 py-3 shadow-sm">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="h-8 w-8 rounded-lg <?= $aBg ?> flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 <?= $aText ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $aIcon ?>"/>
                        </svg>
                    </div>
                    <p class="text-sm text-slate-700 dark:text-slate-300 truncate"><?= $action['text'] ?></p>
                </div>
                <a href="<?= $action['url'] ?>" class="flex-shrink-0 ml-4 inline-flex items-center px-3 py-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-xs font-medium transition-colors shadow-sm">
                    <?= $action['cta'] ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <!-- Tutto in ordine -->
    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800 px-5 py-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-emerald-700 dark:text-emerald-300">Tutto in ordine! Nessuna azione urgente.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- === SEZIONE: I tuoi progetti === -->
    <?php $_gp = $globalProjects ?? []; ?>
    <div>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">I tuoi progetti</h2>
            </div>
            <?php if (!empty($_gp)): ?>
            <a href="<?= url('/projects') ?>" class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:underline">Vedi tutti</a>
            <?php endif; ?>
        </div>

        <?php if (!empty($_gp)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach (array_slice($_gp, 0, 6) as $gp):
                $gpColor = $gp['color'] ?? '#3B82F6';
                $gpModules = (int)($gp['active_modules_count'] ?? 0);
                $gpLastAct = $gp['last_module_activity'] ?? null;
                if ($gpLastAct) {
                    $diff = time() - strtotime($gpLastAct);
                    if ($diff < 3600) $gpTimeAgo = max(1, intdiv($diff, 60)) . ' min fa';
                    elseif ($diff < 86400) $gpTimeAgo = intdiv($diff, 3600) . ' ore fa';
                    elseif ($diff < 604800) $gpTimeAgo = intdiv($diff, 86400) . ' giorni fa';
                    else $gpTimeAgo = date('d/m/Y', strtotime($gpLastAct));
                } else {
                    $gpTimeAgo = null;
                }
            ?>
            <a href="<?= url('/projects/' . $gp['id']) ?>"
               class="group flex items-center gap-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-3 shadow-sm hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all duration-200">
                <!-- Color dot -->
                <div class="h-9 w-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background-color: <?= htmlspecialchars($gpColor) ?>20">
                    <div class="w-3 h-3 rounded-full" style="background-color: <?= htmlspecialchars($gpColor) ?>"></div>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors truncate"><?= htmlspecialchars($gp['name']) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        <?= $gpModules ?> <?= $gpModules == 1 ? 'modulo' : 'moduli' ?>
                        <?php if ($gpTimeAgo): ?>
                        <span class="mx-1 text-slate-300 dark:text-slate-600">&middot;</span>
                        <?= $gpTimeAgo ?>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Arrow -->
                <svg class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-primary-500 transition-colors flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <?php endforeach; ?>

            <?php if (count($_gp) > 6): ?>
            <a href="<?= url('/projects') ?>"
               class="flex items-center justify-center gap-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-300 dark:border-slate-600 px-4 py-3 text-sm text-slate-500 dark:text-slate-400 hover:border-primary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                Vedi tutti i <?= count($_gp) ?> progetti
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- CTA per creare il primo progetto -->
        <a href="<?= url('/projects/create') ?>"
           class="group flex items-center gap-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-300 dark:border-slate-600 px-5 py-4 hover:border-primary-400 dark:hover:border-primary-500 hover:bg-white dark:hover:bg-slate-800 transition-all duration-200">
            <div class="h-10 w-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Crea il tuo primo progetto</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Raggruppa i tuoi strumenti sotto un unico progetto per cliente o sito web.</p>
            </div>
        </a>
        <?php endif; ?>
    </div>

    <!-- === SEZIONE: I tuoi strumenti === -->
    <?php if (!empty($modules)): ?>
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">I tuoi strumenti</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach ($modules as $module):
                $slug = $module['slug'];
                $color = $moduleColors[$slug] ?? $defaultColor;
                $icon = $moduleIcons[$slug] ?? $defaultIcon;
                $mName = $moduleNames[$slug] ?? $module['name'];
                $w = $_w[$slug] ?? null;

                // Metrica compatta per modulo
                $metric = '';
                $metricLabel = '';
                if ($slug === 'ai-content' && $w) {
                    $metric = $w['articles_total'];
                    $metricLabel = $w['articles_total'] == 1 ? 'articolo' : 'articoli';
                } elseif ($slug === 'seo-tracking' && $w) {
                    $metric = $w['keywords'];
                    $metricLabel = 'keyword';
                } elseif ($slug === 'keyword-research' && $w) {
                    $metric = $w['projects'];
                    $metricLabel = $w['projects'] == 1 ? 'progetto' : 'progetti';
                } elseif ($slug === 'seo-audit' && $w) {
                    if ($w['health_score'] !== null) {
                        $metric = $w['health_score'];
                        $metricLabel = 'score';
                    } else {
                        $metric = $w['projects'];
                        $metricLabel = $w['projects'] == 1 ? 'progetto' : 'progetti';
                    }
                } elseif ($slug === 'ads-analyzer' && $w) {
                    $metric = $w['total'];
                    $metricLabel = $w['total'] == 1 ? 'campagna' : 'campagne';
                } elseif ($slug === 'internal-links' && $w) {
                    $metric = $w['projects'];
                    $metricLabel = $w['projects'] == 1 ? 'progetto' : 'progetti';
                }
            ?>
            <a href="<?= url('/' . $slug) ?>"
               class="group flex items-center gap-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-3 shadow-sm <?= $color['hover'] ?> hover:shadow-md transition-all duration-200">
                <!-- Icon -->
                <div class="h-9 w-9 rounded-lg <?= $color['bg'] ?> flex items-center justify-center flex-shrink-0">
                    <svg class="h-4 w-4 <?= $color['text'] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/>
                    </svg>
                </div>

                <!-- Name + Metric -->
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors truncate"><?= htmlspecialchars($mName) ?></p>
                    <?php if ($w && $metric !== ''): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= $metric ?> <?= $metricLabel ?></p>
                    <?php else: ?>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Inizia</p>
                    <?php endif; ?>
                </div>

                <!-- Arrow -->
                <svg class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-primary-500 transition-colors flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>
