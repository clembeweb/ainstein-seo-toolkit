<!-- Hero Section -->
<div class="text-center mb-12">
    <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 dark:text-white mb-4">Centro Assistenza Ainstein</h1>
    <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Trova guide, tutorial e risposte alle domande frequenti</p>

    <!-- Search (visual placeholder) -->
    <div class="mt-8 max-w-xl mx-auto">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input type="text"
                   placeholder="Cerca nella documentazione..."
                   disabled
                   class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 shadow-sm cursor-not-allowed opacity-75">
            <span class="absolute inset-y-0 right-0 pr-4 flex items-center text-xs text-slate-400 dark:text-slate-500">Prossimamente</span>
        </div>
    </div>
</div>

<!-- Module Grid -->
<div class="mb-16">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6">Moduli</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
        <!-- AI Content Generator -->
        <a href="<?= url('/docs/ai-content') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">AI Content Generator</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Genera articoli SEO ottimizzati con intelligenza artificiale</p>
            </div>
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 shrink-0 mt-0.5 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- SEO Audit -->
        <a href="<?= url('/docs/seo-audit') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">SEO Audit</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Analisi tecnica del sito con 50+ controlli e punteggio salute</p>
            </div>
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 shrink-0 mt-0.5 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- SEO Tracking -->
        <a href="<?= url('/docs/seo-tracking') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">SEO Tracking</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Monitora posizioni keyword, traffico e revenue nel tempo</p>
            </div>
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 shrink-0 mt-0.5 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- Keyword Research -->
        <a href="<?= url('/docs/keyword-research') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Keyword Research</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Ricerca keyword con clustering AI e architettura sito</p>
            </div>
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 shrink-0 mt-0.5 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- Internal Links -->
        <a href="<?= url('/docs/internal-links') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Internal Links</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Analizza la struttura di link interni e ottimizza il link juice</p>
            </div>
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 shrink-0 mt-0.5 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- Google Ads Analyzer -->
        <a href="<?= url('/docs/ads-analyzer') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Google Ads Analyzer</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Analizza campagne Google Ads e identifica keyword negative</p>
            </div>
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 shrink-0 mt-0.5 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- Content Creator -->
        <a href="<?= url('/docs/content-creator') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Content Creator</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Genera contenuti HTML completi per pagine prodotto, categorie, servizi e articoli</p>
            </div>
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 shrink-0 mt-0.5 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>

<!-- Quick Links Section -->
<div>
    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6">Risorse utili</h2>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
        <!-- Primi Passi -->
        <a href="<?= url('/docs/getting-started') ?>" class="group p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-accent-300 dark:hover:border-accent-600 hover:shadow-md transition-all text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-accent-50 dark:bg-accent-900/30 flex items-center justify-center group-hover:bg-accent-100 dark:group-hover:bg-accent-900/50 transition-colors mb-3">
                <svg class="w-6 h-6 text-accent-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors">Primi Passi</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Come iniziare ad usare Ainstein</p>
        </a>

        <!-- Sistema Crediti -->
        <a href="<?= url('/docs/credits') ?>" class="group p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-accent-300 dark:hover:border-accent-600 hover:shadow-md transition-all text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-accent-50 dark:bg-accent-900/30 flex items-center justify-center group-hover:bg-accent-100 dark:group-hover:bg-accent-900/50 transition-colors mb-3">
                <svg class="w-6 h-6 text-accent-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors">Sistema Crediti</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Come funzionano i crediti</p>
        </a>

        <!-- FAQ -->
        <a href="<?= url('/docs/faq') ?>" class="group p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-accent-300 dark:hover:border-accent-600 hover:shadow-md transition-all text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-accent-50 dark:bg-accent-900/30 flex items-center justify-center group-hover:bg-accent-100 dark:group-hover:bg-accent-900/50 transition-colors mb-3">
                <svg class="w-6 h-6 text-accent-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors">FAQ</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Domande frequenti</p>
        </a>
    </div>
</div>
