<!-- Hero Section -->
<div class="text-center mb-12">
    <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 dark:text-white mb-4">Centro Assistenza Ainstein</h1>
    <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Trova guide, tutorial e risposte alle domande frequenti</p>

    <!-- Search rimosso (UX audit 2026-03-11, task C13) -->
</div>

<!-- Module Grid -->
<div class="mb-16">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6">Moduli</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
        <!-- AI Writer -->
        <a href="<?= url('/docs/ai-content') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">AI Writer</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Articoli per il tuo blog scritti partendo da ciò che si cerca su Google</p>
            </div>
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 shrink-0 mt-0.5 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- AI Audit -->
        <a href="<?= url('/docs/seo-audit') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">AI Audit</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Analisi tecnica del sito con 50+ controlli e punteggio salute</p>
            </div>
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 shrink-0 mt-0.5 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- AI Rank -->
        <a href="<?= url('/docs/seo-tracking') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">AI Rank</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Segui le posizioni del tuo sito su Google e scopri dove puoi salire</p>
            </div>
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 shrink-0 mt-0.5 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- AI Keywords -->
        <a href="<?= url('/docs/keyword-research') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">AI Keywords</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Scopri cosa cerca la gente e trasformalo in pagine e articoli</p>
            </div>
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 shrink-0 mt-0.5 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- AI GEO Audit -->
        <a href="<?= url('/docs/ai-reputation') ?>" class="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
            <div class="shrink-0 w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">AI GEO Audit</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Cosa dicono ChatGPT, Gemini e le altre AI di te, e cosa fare</p>
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
