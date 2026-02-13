<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-8">
    <a href="<?= url('/docs') ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Documentazione</a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-slate-900 dark:text-white font-medium">Internal Links Analyzer</span>
</nav>

<!-- H1 -->
<div class="flex items-center gap-4 mb-8">
    <div class="shrink-0 w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
        </svg>
    </div>
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">Internal Links Analyzer</h1>
</div>

<!-- Cos'e -->
<section class="mb-12">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Cos'e
    </h2>
    <div class="prose dark:prose-invert max-w-none">
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Analizza la struttura dei link interni del tuo sito per identificare pagine orfane, ottimizzare la distribuzione del link juice e migliorare la navigabilita. Essenziale per una <strong class="text-slate-900 dark:text-white">SEO on-page efficace</strong>: una buona struttura di link interni aiuta sia gli utenti che i motori di ricerca a scoprire e valorizzare tutti i contenuti del sito.
        </p>
    </div>
</section>

<!-- Quick Start -->
<section class="mb-12">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
        <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        Quick Start
    </h2>
    <div class="space-y-4">
        <!-- Step 1 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">1</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Crea un progetto</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Inserisci il nome del progetto e il dominio base del sito che vuoi analizzare.</p>
            </div>
        </div>
        <!-- Step 2 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">2</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Importa le URL</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Carica le URL da analizzare tramite CSV, Sitemap XML o inserimento manuale.</p>
            </div>
        </div>
        <!-- Step 3 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">3</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Avvia lo scraping</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Il sistema analizza ogni pagina per estrarre automaticamente tutti i link interni presenti.</p>
            </div>
        </div>
        <!-- Step 4 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">4</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Consulta la dashboard</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Visualizza le metriche di linking: link in entrata, link in uscita, distribuzione del link juice.</p>
            </div>
        </div>
        <!-- Step 5 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">5</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Identifica e ottimizza</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Individua le pagine orfane e i link da ottimizzare per migliorare la struttura del sito.</p>
            </div>
        </div>
    </div>
</section>

<!-- Funzionalita principali -->
<section class="mb-12">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
        <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
        </svg>
        Funzionalita principali
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Import URL -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Import URL multiplo</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Importa URL da file CSV, Sitemap XML oppure inseriscile manualmente una alla volta.</p>
        </div>
        <!-- Estrazione link -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Estrazione automatica link</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Scraping automatico di ogni pagina per estrarre tutti i link interni ed esterni presenti.</p>
        </div>
        <!-- Pagine orfane -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Rilevamento pagine orfane</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Identifica le pagine che non ricevono nessun link in entrata da altre pagine del sito.</p>
        </div>
        <!-- Link juice -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Distribuzione link juice</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Calcola come il link juice viene distribuito tra le pagine del sito attraverso i link interni.</p>
        </div>
        <!-- Anchor text -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Analisi anchor text</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Analisi della qualita e della rilevanza degli anchor text utilizzati nei link interni.</p>
        </div>
        <!-- Snapshot -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Snapshot storici</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Salva snapshot della struttura nel tempo per confrontare l'evoluzione e verificare i miglioramenti.</p>
        </div>
        <!-- Pool link -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Pool link condiviso</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Pool di link interni condiviso con il modulo AI Content per suggerimenti di linking automatici.</p>
        </div>
        <!-- Statistiche -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Statistiche progetto</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Dashboard con totale URL, link interni, link esterni e conteggio pagine orfane.</p>
        </div>
    </div>
</section>

<!-- Costi in crediti -->
<section class="mb-12">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
        <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Costi in crediti
    </h2>
    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-800">
                    <th class="text-left px-4 py-3 font-semibold text-slate-900 dark:text-white">Operazione</th>
                    <th class="text-right px-4 py-3 font-semibold text-slate-900 dark:text-white">Crediti</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <tr class="bg-white dark:bg-slate-800/50">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Analisi link (per pagina)</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">0.5 crediti</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<!-- Suggerimenti -->
<section class="mb-8">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
        <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
        </svg>
        Suggerimenti
    </h2>
    <div class="space-y-4">
        <!-- Tip 1 -->
        <div class="flex items-start gap-4 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
            <div class="shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-medium text-blue-900 dark:text-blue-200 text-sm">Priorita alle pagine orfane</h3>
                <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">Le pagine orfane sono la priorita assoluta: se nessun link punta verso una pagina, questa non riceve autorita e difficilmente si posizionera. Correggile per prime.</p>
            </div>
        </div>
        <!-- Tip 2 -->
        <div class="flex items-start gap-4 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
            <div class="shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-medium text-blue-900 dark:text-blue-200 text-sm">Anchor text descrittivi e vari</h3>
                <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">Usa anchor text descrittivi e pertinenti al contenuto della pagina di destinazione. Evita testi generici come "clicca qui" o "leggi di piu" e varia le formulazioni.</p>
            </div>
        </div>
        <!-- Tip 3 -->
        <div class="flex items-start gap-4 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
            <div class="shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-medium text-blue-900 dark:text-blue-200 text-sm">Confronta gli snapshot</h3>
                <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">Esegui analisi periodiche e confronta gli snapshot nel tempo per verificare che le ottimizzazioni stiano effettivamente migliorando la struttura di linking del sito.</p>
            </div>
        </div>
    </div>
</section>

<!-- Nav link -->
<div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-700 flex justify-between">
    <a href="<?= url('/docs/keyword-research') ?>" class="flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Keyword Research
    </a>
    <a href="<?= url('/docs/ads-analyzer') ?>" class="flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
        Google Ads Analyzer
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>