<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-8">
    <a href="<?= url('/docs') ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Documentazione</a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-slate-900 dark:text-white font-medium">Primi Passi</span>
</nav>

<!-- Header -->
<div class="mb-10">
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">Primi Passi con Ainstein</h1>
    <p class="text-lg text-slate-600 dark:text-slate-400 leading-relaxed">
        Ainstein è una piattaforma SEO completa potenziata dall'intelligenza artificiale. Grazie ai suoi 7 moduli integrati, puoi analizzare, monitorare e ottimizzare ogni aspetto della tua strategia SEO, dalla ricerca keyword alla generazione di contenuti, dall'audit tecnico al tracciamento delle posizioni.
    </p>
</div>

<!-- Step 1: Crea il tuo account -->
<section class="mb-12">
    <div class="flex items-start gap-4 mb-4">
        <div class="shrink-0 w-10 h-10 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-lg">1</div>
        <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Crea il tuo account</h2>
        </div>
    </div>
    <div class="ml-14">
        <p class="text-slate-600 dark:text-slate-400 mb-4">
            Per iniziare a usare Ainstein, registra il tuo account gratuito. Riceverai immediatamente <strong class="text-slate-900 dark:text-white">30 crediti omaggio</strong> per esplorare tutte le funzionalità della piattaforma.
        </p>
        <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-xl p-5">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-primary-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-primary-800 dark:text-primary-300 mb-1">Registrazione rapida</p>
                    <p class="text-sm text-primary-700 dark:text-primary-400">
                        Vai alla pagina <a href="<?= url('/register') ?>" class="underline font-medium hover:text-primary-900 dark:hover:text-primary-200">Registrazione</a>, inserisci il tuo nome, email e password. La verifica è immediata e potrai iniziare a lavorare subito.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Step 2: Esplora i moduli -->
<section class="mb-12">
    <div class="flex items-start gap-4 mb-4">
        <div class="shrink-0 w-10 h-10 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-lg">2</div>
        <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Esplora i moduli</h2>
        </div>
    </div>
    <div class="ml-14">
        <p class="text-slate-600 dark:text-slate-400 mb-6">
            Ainstein offre 7 moduli specializzati, ognuno progettato per un aspetto diverso della SEO. Puoi usarli singolarmente o combinarli per una strategia completa.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- AI Content Generator -->
            <a href="<?= url('/docs/ai-content') ?>" class="group flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                    <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">AI Content Generator</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Genera articoli SEO completi con analisi SERP, brief strategico e immagini di copertina.</p>
                </div>
            </a>

            <!-- SEO Audit -->
            <a href="<?= url('/docs/seo-audit') ?>" class="group flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                    <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">SEO Audit</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Analisi tecnica completa con 50+ controlli, punteggio di salute e piano d'azione AI.</p>
                </div>
            </a>

            <!-- SEO Tracking -->
            <a href="<?= url('/docs/seo-tracking') ?>" class="group flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                    <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">SEO Tracking</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Monitora posizioni keyword, traffico organico e revenue con integrazione Google Search Console.</p>
                </div>
            </a>

            <!-- Keyword Research -->
            <a href="<?= url('/docs/keyword-research') ?>" class="group flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                    <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Keyword Research</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ricerca keyword con clustering AI, architettura sito e quick check gratuito.</p>
                </div>
            </a>

            <!-- Internal Links -->
            <a href="<?= url('/docs/internal-links') ?>" class="group flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                    <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Internal Links</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Analizza e ottimizza la struttura dei link interni del tuo sito.</p>
                </div>
            </a>

            <!-- Google Ads Analyzer -->
            <a href="<?= url('/docs/ads-analyzer') ?>" class="group flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                    <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Google Ads Analyzer</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Analizza le tue campagne Google Ads e identifica keyword negative e ottimizzazioni.</p>
                </div>
            </a>

            <!-- Content Creator -->
            <a href="<?= url('/docs/content-creator') ?>" class="group flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                    <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Content Creator</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Genera contenuti HTML completi per pagine prodotto, categorie, servizi e articoli con integrazione CMS.</p>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Step 3: Il tuo primo progetto -->
<section class="mb-12">
    <div class="flex items-start gap-4 mb-4">
        <div class="shrink-0 w-10 h-10 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-lg">3</div>
        <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Il tuo primo progetto</h2>
        </div>
    </div>
    <div class="ml-14">
        <p class="text-slate-600 dark:text-slate-400 mb-6">
            Ogni modulo lavora con i <strong class="text-slate-900 dark:text-white">progetti</strong>. Un progetto corrisponde solitamente a un sito web o a una campagna specifica. Ecco come creare il tuo primo progetto in 4 semplici passaggi:
        </p>

        <div class="space-y-4">
            <!-- Substep 1 -->
            <div class="flex items-start gap-4 p-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-accent-600 dark:text-accent-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">1. Scegli il modulo</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Dalla sidebar, seleziona il modulo che vuoi utilizzare. Ogni modulo ha la propria sezione progetti.</p>
                </div>
            </div>

            <!-- Substep 2 -->
            <div class="flex items-start gap-4 p-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-accent-600 dark:text-accent-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">2. Crea un nuovo progetto</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Clicca su "Nuovo progetto" e inserisci il nome del sito e l'URL di base. Puoi aggiungere dettagli come la lingua e il settore di riferimento.</p>
                </div>
            </div>

            <!-- Substep 3 -->
            <div class="flex items-start gap-4 p-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-accent-600 dark:text-accent-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">3. Configura il progetto</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Importa gli URL del tuo sito (da sitemap, CSV o manualmente), collega Google Search Console se necessario, e personalizza le impostazioni del modulo.</p>
                </div>
            </div>

            <!-- Substep 4 -->
            <div class="flex items-start gap-4 p-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-accent-600 dark:text-accent-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">4. Avvia l'analisi</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Lancia la prima analisi o generazione. L'intelligenza artificiale elaborerà i dati e ti fornirà risultati dettagliati con suggerimenti pratici.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Step 4: Gestisci i crediti -->
<section class="mb-12">
    <div class="flex items-start gap-4 mb-4">
        <div class="shrink-0 w-10 h-10 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-lg">4</div>
        <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Gestisci i crediti</h2>
        </div>
    </div>
    <div class="ml-14">
        <p class="text-slate-600 dark:text-slate-400 mb-4">
            Ainstein utilizza un sistema a crediti per le operazioni AI. Ogni operazione ha un costo in crediti, che viene sempre mostrato prima dell'esecuzione. Il tuo saldo attuale è visibile nella barra superiore dell'applicazione.
        </p>
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-5">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1">30 crediti omaggio alla registrazione</p>
                    <p class="text-sm text-amber-700 dark:text-amber-400">
                        Alla registrazione ricevi 30 crediti gratuiti, sufficienti per provare tutti i moduli. Per informazioni dettagliate sui costi di ogni operazione, consulta la pagina <a href="<?= url('/docs/credits') ?>" class="underline font-medium hover:text-amber-900 dark:hover:text-amber-200">Sistema Crediti</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Step 5: Supporto -->
<section class="mb-8">
    <div class="flex items-start gap-4 mb-4">
        <div class="shrink-0 w-10 h-10 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-lg">5</div>
        <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Supporto</h2>
        </div>
    </div>
    <div class="ml-14">
        <p class="text-slate-600 dark:text-slate-400 mb-6">
            Hai bisogno di aiuto? Siamo qui per te. Ecco come puoi contattarci o trovare risposte:
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- FAQ -->
            <a href="<?= url('/docs/faq') ?>" class="group p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all text-center">
                <div class="w-10 h-10 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-primary-50 dark:group-hover:bg-primary-900/30 transition-colors mb-3">
                    <svg class="w-5 h-5 text-slate-500 dark:text-slate-400 group-hover:text-primary-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">FAQ</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Risposte alle domande frequenti</p>
            </a>

            <!-- Email -->
            <a href="mailto:supporto@ainstein.it" class="group p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all text-center">
                <div class="w-10 h-10 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-primary-50 dark:group-hover:bg-primary-900/30 transition-colors mb-3">
                    <svg class="w-5 h-5 text-slate-500 dark:text-slate-400 group-hover:text-primary-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Email</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">supporto@ainstein.it</p>
            </a>

            <!-- Documentazione -->
            <a href="<?= url('/docs') ?>" class="group p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all text-center">
                <div class="w-10 h-10 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-primary-50 dark:group-hover:bg-primary-900/30 transition-colors mb-3">
                    <svg class="w-5 h-5 text-slate-500 dark:text-slate-400 group-hover:text-primary-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Documentazione</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Guide dettagliate per ogni modulo</p>
            </a>
        </div>
    </div>
</section>

<!-- CTA -->
<div class="mt-12 p-6 sm:p-8 rounded-2xl bg-gradient-to-r from-primary-500 to-accent-500 text-center">
    <h2 class="text-2xl font-bold text-white mb-2">Pronto per iniziare?</h2>
    <p class="text-primary-100 mb-6">Registrati gratuitamente e ricevi 30 crediti omaggio.</p>
    <a href="<?= url('/register') ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-primary-600 font-semibold rounded-lg hover:bg-primary-50 transition-colors shadow-lg">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        Crea il tuo account
    </a>
</div>