<!-- Hero Banner -->
<?= \Core\View::partial('components/dashboard-hero-banner', [
    'title' => 'AI Content Bulk Creator',
    'description' => 'Genera contenuti HTML ottimizzati per le pagine del tuo sito e pubblicali direttamente sul CMS.',
    'color' => 'orange',
    'badge' => 'Generazione Contenuti',
    'storageKey' => 'ainstein_hero_content_creator',
    'steps' => [
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>', 'title' => 'Configura', 'subtitle' => 'Template e CMS target'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>', 'title' => 'Importa URL', 'subtitle' => 'Pagine da processare'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>', 'title' => 'Genera & Pubblica', 'subtitle' => 'AI crea e invia al CMS'],
    ],
    'ctaText' => 'Nuovo Progetto',
    'ctaUrl' => url('/projects/create'),
]) ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Content Creator</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Genera massivamente Meta Title, Meta Description e Page Description con AI</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-3">
            <a href="<?= url('/content-creator/connectors') ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors" title="Connettori CMS">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </a>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <?php if (empty($projects)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Crea il tuo primo progetto per iniziare a generare contenuti SEO ottimizzati per le tue pagine.
        </p>
        <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crea primo progetto
        </a>
    </div>
    <?php else: ?>

    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($projects as $project):
            $contentTypes = [
                'product' => ['label' => 'Prodotto', 'color' => 'orange'],
                'category' => ['label' => 'Categoria', 'color' => 'blue'],
                'article' => ['label' => 'Articolo', 'color' => 'purple'],
                'service' => ['label' => 'Servizio', 'color' => 'orange'],
                'custom' => ['label' => 'Custom', 'color' => 'slate'],
            ];
            $ct = $contentTypes[$project['content_type'] ?? 'product'] ?? $contentTypes['product'];
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-start justify-between">
                    <div class="min-w-0 flex-1">
                        <a href="<?= url('/content-creator/projects/' . $project['id']) ?>" class="block">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate hover:text-primary-600 dark:hover:text-primary-400">
                                <?= e($project['name']) ?>
                            </h3>
                        </a>
                        <?php if (!empty($project['description'])): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400 truncate mt-1"><?= e($project['description']) ?></p>
                        <?php else: ?>
                        <p class="text-sm text-slate-400 dark:text-slate-500 italic mt-1">
                            <?= e($project['language'] ?? 'it') ?> / <?= e($project['tone'] ?? 'professionale') ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium bg-<?= $ct['color'] ?>-100 text-<?= $ct['color'] ?>-700 dark:bg-<?= $ct['color'] ?>-900/50 dark:text-<?= $ct['color'] ?>-300">
                        <?= $ct['label'] ?>
                    </span>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-4 divide-x divide-slate-200 dark:divide-slate-700">
                <div class="p-3 text-center">
                    <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($project['total_urls'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">URL</p>
                </div>
                <div class="p-3 text-center">
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400"><?= number_format($project['scraped_urls'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Scrappate</p>
                </div>
                <div class="p-3 text-center">
                    <p class="text-lg font-bold text-purple-600 dark:text-purple-400"><?= number_format($project['generated_urls'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Generate</p>
                </div>
                <div class="p-3 text-center">
                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($project['approved_urls'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Approvate</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <?php if (($project['error_urls'] ?? 0) > 0): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                            <?= $project['error_urls'] ?> errori
                        </span>
                        <?php elseif (($project['published_urls'] ?? 0) > 0): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                            <?= $project['published_urls'] ?> pubblicati
                        </span>
                        <?php else: ?>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            Creato: <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-1">
                        <a href="<?= url('/content-creator/projects/' . $project['id']) ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Dashboard">
                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </a>
                        <a href="<?= url('/content-creator/projects/' . $project['id'] . '/settings') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Impostazioni">
                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Info Box -->
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Workflow Content Creator</h3>
                <p class="text-orange-100 text-sm mt-1">Genera contenuti SEO ottimizzati in pochi click</p>
            </div>
            <div class="flex gap-6">
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">1</span>
                    </div>
                    <p class="text-xs text-orange-200">Importa URL</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">2</span>
                    </div>
                    <p class="text-xs text-orange-200">Scrape</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">3</span>
                    </div>
                    <p class="text-xs text-orange-200">Genera AI</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">4</span>
                    </div>
                    <p class="text-xs text-orange-200">Approva</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">5</span>
                    </div>
                    <p class="text-xs text-orange-200">Pubblica</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════ SEZIONE EDUCATIVA ═══════════ -->
<div class="relative my-16">
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full h-px bg-gradient-to-r from-transparent via-orange-400/50 to-transparent"></div>
    </div>
    <div class="relative flex justify-center">
        <span class="bg-white dark:bg-slate-900 px-6 text-sm font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider">Scopri cosa puoi fare</span>
    </div>
</div>

<!-- Hero Educativo -->
<div class="bg-gradient-to-br from-orange-50/50 via-white to-amber-50/30 dark:from-orange-950/20 dark:via-slate-900 dark:to-amber-950/10 rounded-2xl border border-orange-200/50 dark:border-orange-800/30 p-8 lg:p-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <!-- Left: Text -->
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Content Creator
            </span>
            <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white mt-4">Genera contenuti SEO in massa e pubblicali sul tuo CMS</h2>
            <p class="text-lg text-slate-600 dark:text-slate-300 mt-4 leading-relaxed">Dall'importazione URL alla pubblicazione diretta: scrapa le pagine esistenti, genera contenuti HTML ottimizzati con AI e inviali al CMS con un click. WordPress, Shopify, PrestaShop e Magento supportati.</p>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-6 py-3 rounded-xl bg-orange-500 text-white font-semibold hover:bg-orange-600 shadow-lg shadow-orange-500/25 transition-all mt-8">
                Crea il tuo primo progetto
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        <!-- Right: Workflow Visualization -->
        <div class="transform lg:rotate-1 hover:rotate-0 transition-transform duration-500">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <!-- Top bar -->
                <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                    </div>
                    <span class="text-xs text-slate-400 dark:text-slate-500 flex-1 text-center">Content Creator — Generazione Bulk</span>
                </div>
                <!-- Workflow visualization -->
                <div class="p-5 space-y-3">
                    <!-- URL Row 1 -->
                    <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">/prodotti/scarpe-running-uomo</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 font-medium">Generata</span>
                        <span class="text-[10px] text-slate-400">850 parole</span>
                    </div>
                    <!-- URL Row 2 -->
                    <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800/40">
                        <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                        <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">/categorie/accessori-fitness</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300 font-medium">Approvata</span>
                        <span class="text-[10px] text-slate-400">1.200 parole</span>
                    </div>
                    <!-- URL Row 3 -->
                    <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/40">
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                        <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">/servizi/consulenza-seo</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 font-medium">In scraping...</span>
                        <span class="text-[10px] text-slate-400">—</span>
                    </div>
                    <!-- URL Row 4 -->
                    <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-700/30 border border-slate-200 dark:border-slate-600 opacity-60">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 flex-1 truncate">/blog/guida-allenamento-running</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 font-medium">In attesa</span>
                        <span class="text-[10px] text-slate-400">—</span>
                    </div>
                </div>
                <!-- Bottom stats -->
                <div class="px-4 py-3 bg-orange-50 dark:bg-orange-900/20 border-t border-orange-200 dark:border-orange-800/30 flex items-center justify-between">
                    <span class="text-xs text-orange-600 dark:text-orange-300 font-medium">4 URL in lavorazione</span>
                    <span class="text-xs text-orange-500 dark:text-orange-400">2 generate, 1 approvata</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Come funziona -->
<div class="mt-20">
    <div class="text-center mb-12">
        <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Come funziona</h3>
        <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Da URL a contenuto pubblicato in 3 passaggi</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Step 1 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center text-sm font-bold shadow-md">1</div>
            <div class="h-12 w-12 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Importa le URL</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Importa le pagine da ottimizzare tramite CSV, sitemap XML, connettore CMS o inserimento manuale. Ogni URL diventa un contenuto da generare.</p>
        </div>

        <!-- Step 2 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center text-sm font-bold shadow-md">2</div>
            <div class="h-12 w-12 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Genera con AI</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">L'AI analizza il contenuto esistente di ogni pagina (scraping) e genera nuovi testi HTML ottimizzati SEO: H1, descrizioni, meta tag — tutto personalizzabile per tono e lingua.</p>
        </div>

        <!-- Step 3 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center text-sm font-bold shadow-md">3</div>
            <div class="h-12 w-12 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Approva e Pubblica</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Rivedi i contenuti generati, approva quelli pronti e pubblicali direttamente sul CMS con un click. Supporta WordPress, Shopify, PrestaShop e Magento.</p>
        </div>
    </div>
</div>

<!-- Feature Sections -->
<div class="mt-20 space-y-0">

    <!-- Feature 1: Generazione Bulk (text LEFT, visual RIGHT, white bg) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Generazione bulk intelligente</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Non un contenuto generico copia-incolla. L'AI <strong class="text-orange-600 dark:text-orange-400">analizza ogni pagina individualmente</strong> tramite scraping, comprende il contesto del prodotto o servizio e genera contenuto su misura.
                        Definisci lingua, tono e lunghezza — il sistema fa il resto, processando centinaia di URL in parallelo con SSE in tempo reale.
                    </p>
                    <a href="<?= url('/projects/create') ?>" class="inline-flex items-center mt-6 text-orange-600 dark:text-orange-400 font-medium hover:text-orange-700 dark:hover:text-orange-300 transition-colors">
                        Prova la generazione
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Generazione AI in corso</span>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-xs text-slate-700 dark:text-slate-300 flex-1">/prodotti/crema-viso-anti-age</span>
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400">920 parole</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-xs text-slate-700 dark:text-slate-300 flex-1">/prodotti/siero-vitamina-c</span>
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400">1.100 parole</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-orange-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-xs text-slate-700 dark:text-slate-300 flex-1">/prodotti/olio-argan-bio</span>
                                <span class="text-[10px] text-orange-500">Generazione...</span>
                            </div>
                            <div class="flex items-center gap-3 opacity-40">
                                <div class="w-4 h-4 rounded-full border-2 border-slate-300 dark:border-slate-600 flex-shrink-0"></div>
                                <span class="text-xs text-slate-400 flex-1">/prodotti/maschera-idratante</span>
                                <span class="text-[10px] text-slate-400">In coda</span>
                            </div>
                        </div>
                        <div class="px-4 py-3 bg-orange-50 dark:bg-orange-900/20 border-t border-orange-200 dark:border-orange-800/30">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-orange-600 dark:text-orange-300 font-medium">3 di 4 completate</span>
                                <div class="w-24 bg-orange-200 dark:bg-orange-800 rounded-full h-1.5">
                                    <div class="bg-orange-500 h-1.5 rounded-full" style="width: 75%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 2: 4 connettori CMS (text RIGHT, visual LEFT, slate bg) -->
    <div class="py-16 bg-slate-50 dark:bg-slate-800/50">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Connettori CMS</span>
                        </div>
                        <div class="p-4 grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-lg border-2 border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20">
                                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center mb-2">
                                    <span class="text-white text-xs font-bold">WP</span>
                                </div>
                                <p class="text-xs font-semibold text-blue-700 dark:text-blue-300">WordPress</p>
                                <p class="text-[10px] text-blue-600 dark:text-blue-400 mt-0.5">REST API v2</p>
                            </div>
                            <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/30">
                                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center mb-2">
                                    <span class="text-white text-xs font-bold">SH</span>
                                </div>
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Shopify</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Admin API</p>
                            </div>
                            <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/30">
                                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-pink-500 to-pink-600 flex items-center justify-center mb-2">
                                    <span class="text-white text-xs font-bold">PS</span>
                                </div>
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">PrestaShop</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Webservice API</p>
                            </div>
                            <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/30">
                                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center mb-2">
                                    <span class="text-white text-xs font-bold">MG</span>
                                </div>
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Magento</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">REST API v2</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">4 connettori CMS integrati</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Collega il tuo CMS e pubblica i contenuti generati <strong class="text-orange-600 dark:text-orange-400">direttamente dalle pagine del progetto</strong>. Importa le URL esistenti dal CMS, genera i contenuti con AI e pubblicali con un click — senza mai lasciare Ainstein.
                        Ogni connettore supporta test di connessione, toggle attivo/disattivo e configurazione sicura.
                    </p>
                    <a href="<?= url('/content-creator/connectors/create') ?>" class="inline-flex items-center mt-6 text-orange-600 dark:text-orange-400 font-medium hover:text-orange-700 dark:hover:text-orange-300 transition-colors">
                        Configura un connettore
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 3: Workflow completo (text LEFT, visual RIGHT, white bg) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Workflow completo con revisione umana</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Nessun contenuto viene pubblicato senza la tua approvazione. Il flusso <strong class="text-orange-600 dark:text-orange-400">Scraping → Generazione → Revisione → Approvazione → Pubblicazione</strong> garantisce qualita e controllo.
                        Ogni contenuto e visibile in anteprima HTML prima dell'approvazione, con editor integrato per modifiche manuali.
                    </p>
                    <a href="<?= url('/projects/create') ?>" class="inline-flex items-center mt-6 text-orange-600 dark:text-orange-400 font-medium hover:text-orange-700 dark:hover:text-orange-300 transition-colors">
                        Inizia il tuo workflow
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Flusso di stato URL</span>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-center flex-1">
                                    <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-600 flex items-center justify-center mx-auto">
                                        <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-1.5 font-medium">In Attesa</p>
                                </div>
                                <svg class="w-4 h-4 text-slate-300 dark:text-slate-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                <div class="text-center flex-1">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mx-auto">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                    </div>
                                    <p class="text-[10px] text-blue-600 mt-1.5 font-medium">Scrappata</p>
                                </div>
                                <svg class="w-4 h-4 text-slate-300 dark:text-slate-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                <div class="text-center flex-1">
                                    <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mx-auto">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                    </div>
                                    <p class="text-[10px] text-purple-600 mt-1.5 font-medium">Generata</p>
                                </div>
                                <svg class="w-4 h-4 text-slate-300 dark:text-slate-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                <div class="text-center flex-1">
                                    <div class="w-10 h-10 rounded-full bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center mx-auto">
                                        <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <p class="text-[10px] text-teal-600 mt-1.5 font-medium">Approvata</p>
                                </div>
                                <svg class="w-4 h-4 text-slate-300 dark:text-slate-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                <div class="text-center flex-1">
                                    <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mx-auto">
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    </div>
                                    <p class="text-[10px] text-emerald-600 mt-1.5 font-medium">Pubblicata</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cosa puoi fare -->
<div class="mt-20">
    <div class="text-center mb-12">
        <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Cosa puoi fare con Content Creator</h3>
        <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Generazione e pubblicazione contenuti SEO per ogni esigenza</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Card 1 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-orange-300 dark:hover:border-orange-700 transition-all">
            <div class="h-10 w-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Descrizioni prodotto in massa</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400">Genera descrizioni uniche e ottimizzate SEO per centinaia di prodotti e-commerce in un click.</p>
        </div>
        <!-- Card 2 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-orange-300 dark:hover:border-orange-700 transition-all">
            <div class="h-10 w-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Meta tag ottimizzati SEO</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400">Title e description persuasivi, rispettando i limiti di caratteri e includendo le keyword target.</p>
        </div>
        <!-- Card 3 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-orange-300 dark:hover:border-orange-700 transition-all">
            <div class="h-10 w-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
            </div>
            <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Contenuti categorie e-commerce</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400">Testi introduttivi per pagine categoria che migliorano il posizionamento e guidano l'utente.</p>
        </div>
        <!-- Card 4 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-orange-300 dark:hover:border-orange-700 transition-all">
            <div class="h-10 w-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Landing page per servizi</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400">Crea contenuti persuasivi per pagine servizio con struttura H1-H2 ottimizzata e call to action.</p>
        </div>
        <!-- Card 5 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-orange-300 dark:hover:border-orange-700 transition-all">
            <div class="h-10 w-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Articoli blog da keyword</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400">Genera articoli completi partendo da una keyword target. Struttura SEO con H1, H2, paragrafi e conclusione.</p>
        </div>
        <!-- Card 6 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-orange-300 dark:hover:border-orange-700 transition-all">
            <div class="h-10 w-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Pubblicazione diretta su CMS</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400">Approva i contenuti e pubblicali con un click su WordPress, Shopify, PrestaShop o Magento.</p>
        </div>
    </div>
</div>

<!-- FAQ -->
<div class="mt-20" x-data="{ openFaq: null }">
    <div class="text-center mb-12">
        <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Domande frequenti</h3>
    </div>
    <div class="max-w-3xl mx-auto space-y-3">
        <!-- FAQ 1 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <button @click="openFaq === 1 ? openFaq = null : openFaq = 1" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <span class="font-medium text-slate-900 dark:text-white">Quanti contenuti posso generare alla volta?</span>
                <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 1 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openFaq === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Non c'e un limite fisso. Puoi importare centinaia di URL e generare contenuti per tutte in un'unica sessione. Il sistema processa le URL in sequenza con SSE in tempo reale, mostrando il progresso per ogni pagina. L'unico limite sono i crediti disponibili.</p>
            </div>
        </div>
        <!-- FAQ 2 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <button @click="openFaq === 2 ? openFaq = null : openFaq = 2" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <span class="font-medium text-slate-900 dark:text-white">Quali CMS sono supportati?</span>
                <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 2 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openFaq === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Attualmente supportiamo <strong>WordPress</strong> (REST API), <strong>Shopify</strong> (Admin API), <strong>PrestaShop</strong> (Webservice) e <strong>Magento 2</strong> (REST API). Ogni connettore include test di connessione e toggle attivo/disattivo. Puoi configurare piu connettori e assegnarne uno diverso a ogni progetto.</p>
            </div>
        </div>
        <!-- FAQ 3 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <button @click="openFaq === 3 ? openFaq = null : openFaq = 3" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <span class="font-medium text-slate-900 dark:text-white">Posso personalizzare il tono dei contenuti?</span>
                <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 3 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openFaq === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Si, ogni progetto ha impostazioni di lingua e tono (professionale, informale, tecnico, etc.). Queste impostazioni vengono passate all'AI durante la generazione per mantenere coerenza su tutti i contenuti del progetto.</p>
            </div>
        </div>
        <!-- FAQ 4 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <button @click="openFaq === 4 ? openFaq = null : openFaq = 4" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <span class="font-medium text-slate-900 dark:text-white">Come funziona il sistema di approvazione?</span>
                <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 4 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openFaq === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Dopo la generazione, ogni contenuto passa in stato "Generato". Puoi visualizzare l'anteprima HTML, modificare il contenuto se necessario, e poi approvare o rifiutare. Solo i contenuti approvati possono essere pubblicati sul CMS. Puoi anche revocare un'approvazione o ri-approvare un contenuto rifiutato in qualsiasi momento.</p>
            </div>
        </div>
        <!-- FAQ 5 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <button @click="openFaq === 5 ? openFaq = null : openFaq = 5" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <span class="font-medium text-slate-900 dark:text-white">Quanto costano i crediti per la generazione?</span>
                <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 5 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openFaq === 5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Lo scraping delle URL non consuma crediti. La generazione AI consuma crediti in base alla complessita del contenuto richiesto — il costo e visibile prima dell'avvio. L'approvazione, il rifiuto, l'export CSV e la pubblicazione CMS sono gratuiti.</p>
            </div>
        </div>
        <!-- FAQ 6 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <button @click="openFaq === 6 ? openFaq = null : openFaq = 6" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <span class="font-medium text-slate-900 dark:text-white">Il contenuto generato e SEO-friendly?</span>
                <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 6 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openFaq === 6" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Assolutamente si. L'AI genera contenuti con struttura HTML semantica (H1, H2, paragrafi), include la keyword target naturalmente nel testo, e ottimizza per leggibilita e engagement. Ogni contenuto e unico e specifico per la pagina di destinazione.</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA Finale -->
<div class="mt-20 mb-8">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-orange-500 to-amber-500 p-8 lg:p-12">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/4"></div>

        <div class="relative flex flex-col sm:flex-row items-center justify-between gap-6">
            <div>
                <h3 class="text-2xl font-bold text-white">Pronto a generare contenuti in massa?</h3>
                <p class="mt-2 text-orange-100">Importa le URL, genera con AI e pubblica sul CMS in pochi click</p>
            </div>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-8 py-3 rounded-xl bg-white text-orange-600 font-semibold hover:bg-orange-50 shadow-lg transition-all flex-shrink-0">
                Crea progetto
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </div>
</div>
