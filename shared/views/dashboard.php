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
$_hour = (int) date('H');
$_greeting = $_hour < 12 ? 'Buongiorno' : ($_hour < 18 ? 'Buon pomeriggio' : 'Buonasera');
$_moduleSlugs = array_column($modules ?? [], 'slug');

// === Module capability blocks ===
$moduleBlocks = [
    'keyword-research' => [
        'slug' => 'keyword-research',
        'name' => 'Keyword Research',
        'tagline' => 'Trova le keyword giuste per il tuo business',
        'color' => 'purple',
        'iconPath' => $moduleIcons['keyword-research'],
        'capabilities' => [
            ['text' => 'Ricerca guidata con AI', 'cost' => 'da 2 crediti'],
            ['text' => 'Architettura sito completa', 'cost' => 'da 5 crediti'],
            ['text' => 'Piano editoriale', 'cost' => 'da 5 crediti'],
            ['text' => 'Quick check keyword', 'cost' => 'gratis'],
        ],
    ],
    'ai-content' => [
        'slug' => 'ai-content',
        'name' => 'AI Content Generator',
        'tagline' => 'Scrivi e pubblica articoli SEO ottimizzati',
        'color' => 'amber',
        'iconPath' => $moduleIcons['ai-content'],
        'capabilities' => [
            ['text' => 'Articoli SEO completi', 'cost' => 'da 3 crediti'],
            ['text' => 'Meta tag ottimizzati', 'cost' => 'da 1 credito'],
            ['text' => 'Pubblicazione WordPress', 'cost' => 'automatica'],
        ],
    ],
    'seo-audit' => [
        'slug' => 'seo-audit',
        'name' => 'SEO Audit',
        'tagline' => 'Scopri cosa migliorare nel tuo sito',
        'color' => 'emerald',
        'iconPath' => $moduleIcons['seo-audit'],
        'capabilities' => [
            ['text' => 'Audit tecnico completo', 'cost' => 'da 2 crediti'],
            ['text' => 'Piano d\'azione prioritizzato', 'cost' => 'incluso'],
            ['text' => 'Report esportabile', 'cost' => 'incluso'],
        ],
    ],
    'seo-tracking' => [
        'slug' => 'seo-tracking',
        'name' => 'SEO Tracking',
        'tagline' => 'Monitora le posizioni su Google ogni giorno',
        'color' => 'blue',
        'iconPath' => $moduleIcons['seo-tracking'],
        'capabilities' => [
            ['text' => 'Tracking keyword giornaliero', 'cost' => '1 cr/check'],
            ['text' => 'Dati reali da Search Console', 'cost' => 'gratis'],
            ['text' => 'Report AI settimanale', 'cost' => '1 credito'],
        ],
    ],
    'ads-analyzer' => [
        'slug' => 'ads-analyzer',
        'name' => 'Google Ads Analyzer',
        'tagline' => 'Analizza o crea campagne Google Ads',
        'color' => 'rose',
        'iconPath' => $moduleIcons['ads-analyzer'],
        'capabilities' => [
            ['text' => 'Analisi campagna esistente', 'cost' => 'da 2 crediti'],
            ['text' => 'Creazione campagna da zero', 'cost' => 'da 3 crediti'],
            ['text' => 'Valutazione performance', 'cost' => 'da 2 crediti'],
        ],
    ],
    'internal-links' => [
        'slug' => 'internal-links',
        'name' => 'Internal Links',
        'tagline' => 'Ottimizza la struttura dei link interni',
        'color' => 'cyan',
        'iconPath' => $moduleIcons['internal-links'],
        'capabilities' => [
            ['text' => 'Scansione struttura link', 'cost' => '1 credito'],
            ['text' => 'Mappa link interni', 'cost' => 'incluso'],
        ],
    ],
];
?>

<div class="space-y-8">

    <?php if ($isNewUser): ?>
    <!-- ========================================================================
         MODALITA NEW USER (0 progetti)
         ======================================================================== -->

    <!-- === SEZIONE 1: Header nuovo utente === -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Benvenuto su Ainstein, <?= htmlspecialchars($user['name'] ?? 'Utente') ?>!</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Scegli uno strumento per iniziare.</p>
    </div>

    <!-- === SEZIONE 2: Module blocks grid === -->
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Cosa vuoi fare?</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">Ogni strumento ti guida passo-passo. Scegli quello che ti serve.</p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <?php foreach ($moduleBlocks as $slug => $block):
                if (!in_array($slug, $_moduleSlugs)) continue;
            ?>
                <?= \Core\View::partial('components/dashboard-module-block', ['block' => $block]) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- === SEZIONE 3: Suggerimento docs === -->
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
         MODALITA ACTIVE USER (1+ progetti)
         ======================================================================== -->

    <!-- === SEZIONE 1: Header === -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= $_greeting ?>, <?= htmlspecialchars($user['name'] ?? 'Utente') ?>!</h1>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                <?= count($globalProjects) ?> <?= count($globalProjects) == 1 ? 'progetto attivo' : 'progetti attivi' ?>
                <span class="mx-1.5 text-slate-300 dark:text-slate-600">&middot;</span>
                <?= (int)$urgentActionsCount ?> <?= (int)$urgentActionsCount == 1 ? 'azione da completare' : 'azioni da completare' ?>
            </p>
        </div>
        <!-- Credits badge -->
        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 text-sm font-medium">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
            </svg>
            <?= number_format((float)($credits ?? 0), 1) ?> crediti
        </div>
    </div>

    <!-- === SEZIONE 2: Global alerts (crediti) === -->
    <?php $_credits = (float)($credits ?? 0); ?>
    <?php if ($_credits < 3): ?>
    <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-5 py-4">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p class="text-sm text-red-700 dark:text-red-300">
                    Crediti quasi esauriti (<strong><?= number_format($_credits, 1) ?></strong>). Ricarica per continuare.
                </p>
            </div>
            <a href="<?= url('/profile') ?>" class="flex-shrink-0 inline-flex items-center px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-medium transition-colors shadow-sm">
                Ricarica
            </a>
        </div>
    </div>
    <?php elseif ($_credits < 10): ?>
    <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-5 py-4">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p class="text-sm text-amber-700 dark:text-amber-300">
                    Crediti in esaurimento (<strong><?= number_format($_credits, 1) ?></strong>). Considera una ricarica.
                </p>
            </div>
            <a href="<?= url('/profile') ?>" class="flex-shrink-0 inline-flex items-center px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium transition-colors shadow-sm">
                Ricarica
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- === SEZIONE 3: Project Cards grid === -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">I tuoi progetti</h2>
            <?php if (count($globalProjects) > 6): ?>
            <a href="<?= url('/projects') ?>" class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:underline">Vedi tutti</a>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <?php foreach (array_slice($globalProjects, 0, 6) as $gp): ?>
                <?= \Core\View::partial('components/dashboard-project-card', ['project' => $gp]) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- === SEZIONE 4: Quick tools bar === -->
    <?php if (!empty($modules)): ?>
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">I tuoi strumenti</h2>

        <div class="flex flex-wrap gap-2">
            <?php foreach ($modules as $module):
                $slug = $module['slug'];
                $color = $moduleColors[$slug] ?? $defaultColor;
                $icon = $moduleIcons[$slug] ?? $defaultIcon;
                $mName = $moduleNames[$slug] ?? $module['name'];
            ?>
            <a href="<?= url('/' . $slug) ?>"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 hover:shadow-sm transition-all duration-200">
                <svg class="w-4 h-4 <?= $color['text'] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/>
                </svg>
                <?= htmlspecialchars($mName) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- === SEZIONE 5: Scopri cosa puoi fare === -->
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Scopri cosa puoi fare</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">Tutti gli strumenti disponibili e le loro funzionalità.</p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <?php foreach ($moduleBlocks as $slug => $block):
                if (!in_array($slug, $_moduleSlugs)) continue;
            ?>
                <?= \Core\View::partial('components/dashboard-module-block', ['block' => $block]) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>

</div>
