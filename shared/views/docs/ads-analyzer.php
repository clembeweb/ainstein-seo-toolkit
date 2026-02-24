<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-8">
    <a href="<?= url('/docs') ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Documentazione</a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-slate-900 dark:text-white font-medium">Google Ads Analyzer</span>
</nav>

<!-- H1 -->
<div class="flex items-center gap-4 mb-8">
    <div class="shrink-0 w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
        </svg>
    </div>
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">Google Ads Analyzer</h1>
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
            Due modalita per Google Ads: <strong class="text-slate-900 dark:text-white">Analisi Campagne</strong> per monitorare e valutare campagne esistenti tramite Google Ads Script, e <strong class="text-slate-900 dark:text-white">Campaign Creator</strong> per generare da zero campagne complete (Search o PMax) con AI, pronte da copiare in piattaforma o importare via CSV.
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
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Accedi a Google Ads Analyzer e crea un nuovo progetto. Assegna un nome descrittivo per identificare facilmente l'account o la campagna che vuoi monitorare.</p>
            </div>
        </div>
        <!-- Step 2 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">2</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Configura lo script Google Ads</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Copia lo script generato automaticamente e incollalo nel tuo account Google Ads (Strumenti > Script). Eseguilo per inviare i dati delle campagne ad Ainstein.</p>
            </div>
        </div>
        <!-- Step 3 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">3</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Avvia la valutazione AI delle campagne</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Dopo aver ricevuto i dati, avvia la valutazione AI per ottenere un'analisi dettagliata delle performance, suggerimenti sui copy degli annunci e raccomandazioni di ottimizzazione.</p>
            </div>
        </div>
        <!-- Step 4 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">4</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Analizza le keyword negative</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Usa il tab "Keyword Negative" per analizzare i search terms raccolti. L'AI identifica i termini irrilevanti, li classifica per categoria e priorita, e puoi esportarli per Google Ads Editor.</p>
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
        <!-- Google Ads Script -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Google Ads Script</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Integrazione automatica per ricevere dati aggiornati sulle campagne senza intervento manuale.</p>
        </div>
        <!-- Valutazione campagne -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Valutazione campagne AI</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Analisi delle performance con raccomandazioni AI su copy, budget, landing pages e strategie di ottimizzazione. Include analisi automatica delle landing page e report esportabile in PDF.</p>
        </div>
        <!-- Genera con AI -->
        <div class="p-4 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Genera con AI</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Dalla pagina valutazione, genera contenuti actionable per risolvere i problemi identificati: copy annunci (headline + description), estensioni mancanti (sitelink, callout, snippet) e keyword negative raggruppate per categoria. Ogni generazione puo essere copiata o esportata in CSV compatibile Google Ads Editor per importazione diretta.</p>
        </div>
        <!-- Keyword negative -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Analisi keyword negative</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Identifica i termini di ricerca irrilevanti dai dati raccolti dallo script, classificati per categoria e priorita con AI.</p>
        </div>
        <!-- Contesti business salvati -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Contesti business salvati</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Salva e riutilizza i contesti business per analisi ripetute. L'AI puo anche estrarre il contesto automaticamente dalle landing page.</p>
        </div>
        <!-- Auto-evaluation -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Auto-evaluation</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Valutazione automatica dopo ogni esecuzione dello script per monitoraggio continuo senza intervento manuale.</p>
        </div>
        <!-- Export e copia rapida -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Export PDF e CSV</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Esporta il report completo della valutazione in PDF per condividerlo con il cliente. I contenuti generati con AI (copy, estensioni, keyword negative) possono essere esportati in CSV compatibile Google Ads Editor per importazione diretta.</p>
        </div>
        <!-- Campaign Creator -->
        <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Campaign Creator AI</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Genera campagne Google Ads complete (Search o PMax) con un wizard guidato in 3 step: analisi landing page, keyword research con volumi reali da Google Keyword Insight API (3 fasi: AI seed &rarr; espansione API &rarr; organizzazione AI), generazione copy/asset completi con raccomandazione budget giornaliero a 3 livelli. Export CSV compatibile con Google Ads Editor.</p>
        </div>
        <!-- Asset completi -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Asset completi con limiti Google</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Headlines, descrizioni, sitelinks, callouts e structured snippets generati rispettando i limiti caratteri ufficiali Google Ads, con contatori visivi.</p>
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
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Valutazione AI campagne</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 crediti</span>
                    </td>
                </tr>
                <tr class="bg-white dark:bg-slate-800/50">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Estrazione contesto business dal sito</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 crediti</span>
                    </td>
                </tr>
                <tr class="bg-white dark:bg-slate-800/50">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Analisi keyword negative (per ad group)</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 crediti</span>
                    </td>
                </tr>
                <tr class="bg-white dark:bg-slate-800/50">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Genera con AI (copy / estensioni / keyword)</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">1 credito / generazione</span>
                    </td>
                </tr>
                <tr class="bg-amber-50/50 dark:bg-amber-900/10">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300 font-medium" colspan="2">Campaign Creator</td>
                </tr>
                <tr class="bg-white dark:bg-slate-800/50">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Scraping landing page</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">1 credito</span>
                    </td>
                </tr>
                <tr class="bg-white dark:bg-slate-800/50">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Keyword research AI</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 crediti</span>
                    </td>
                </tr>
                <tr class="bg-white dark:bg-slate-800/50">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Generazione campagna completa</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 crediti</span>
                    </td>
                </tr>
                <tr class="bg-amber-50/50 dark:bg-amber-900/10">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300 font-medium">Totale Campaign Creator</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200">~14 crediti</span>
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
                <h3 class="font-medium text-blue-900 dark:text-blue-200 text-sm">Pianifica lo script come cron</h3>
                <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">In Google Ads, programma lo script per eseguirsi automaticamente ogni giorno o settimana. Cosi avrai sempre dati aggiornati senza doverlo eseguire manualmente.</p>
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
                <h3 class="font-medium text-blue-900 dark:text-blue-200 text-sm">Attiva l'auto-evaluation</h3>
                <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">Abilita la valutazione automatica per ricevere analisi aggiornate dopo ogni sincronizzazione. Monitora l'andamento delle campagne senza intervento manuale.</p>
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
                <h3 class="font-medium text-blue-900 dark:text-blue-200 text-sm">Salva i contesti business</h3>
                <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">Se gestisci piu campagne per lo stesso cliente, salva il contesto business per riutilizzarlo nelle analisi keyword negative successive senza doverlo reinserire.</p>
            </div>
        </div>
    </div>
</section>

<!-- Nav link -->
<div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-700 flex justify-between">
    <a href="<?= url('/docs/internal-links') ?>" class="flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Internal Links
    </a>
    <a href="<?= url('/docs/credits') ?>" class="flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
        Sistema Crediti
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>
