<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-8">
    <a href="<?= url('/docs') ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Documentazione</a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-slate-900 dark:text-white font-medium">AI Keyword Research</span>
</nav>

<!-- H1 -->
<div class="flex items-center gap-4 mb-8">
    <div class="shrink-0 w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </div>
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">AI Keyword Research</h1>
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
            Ricerca keyword potenziata dall'intelligenza artificiale. Tre modalita operative: <strong class="text-slate-900 dark:text-white">Research Guidata</strong> con clustering semantico, <strong class="text-slate-900 dark:text-white">Architettura Sito</strong> con suggerimenti di struttura pagine, e <strong class="text-slate-900 dark:text-white">Quick Check</strong> gratuito per verifiche rapide. L'AI analizza le keyword raccolte, le raggruppa per intent semantico e ti fornisce una mappa operativa pronta all'uso.
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
                <h3 class="font-medium text-slate-900 dark:text-white">Scegli la modalita</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Seleziona tra Research Guidata, Architettura Sito o Quick Check in base al tuo obiettivo.</p>
            </div>
        </div>
        <!-- Step 2 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">2</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Descrivi il business e inserisci le keyword seed</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Per la Research Guidata, descrivi brevemente il tuo business e inserisci 5-10 keyword seed di partenza.</p>
            </div>
        </div>
        <!-- Step 3 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">3</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Raccolta keyword correlate</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">L'API raccoglie automaticamente le keyword correlate con dati su volumi di ricerca e CPC.</p>
            </div>
        </div>
        <!-- Step 4 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">4</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Clustering AI per intent semantico</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">L'intelligenza artificiale raggruppa le keyword per intent semantico, creando cluster tematici utilizzabili.</p>
            </div>
        </div>
        <!-- Step 5 -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">5</div>
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Esplora, filtra e esporta</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Esplora i cluster generati, filtra le keyword per rilevanza e esporta tutto in formato CSV.</p>
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
        <!-- Research Guidata -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Research Guidata (4 step)</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Brief, raccolta keyword, clustering AI e visualizzazione risultati con filtri avanzati.</p>
        </div>
        <!-- Architettura Sito -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Architettura Sito (3 step)</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Brief, raccolta e analisi combinata, con struttura pagine consigliata dall'AI.</p>
        </div>
        <!-- Quick Check -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Quick Check (gratuito)</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Verifica istantanea di volume, CPC, competition e intent per una singola keyword.</p>
        </div>
        <!-- Clustering AI -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Clustering AI</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Raggruppamento semantico per intent: informational, transactional, commercial, navigational.</p>
        </div>
        <!-- Architettura suggerita -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Architettura suggerita</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Nomi pagina, URL consigliati, H1 e keyword principale per ogni pagina suggerita.</p>
        </div>
        <!-- Keyword correlate -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Correlate ed export</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Keyword correlate con ordinamento, paginazione e export CSV completo di tutti i dati.</p>
        </div>
        <!-- Dati Google Keyword Insight -->
        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 md:col-span-2">
            <div class="flex items-center gap-3 mb-2">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">Dati da Google Keyword Insight API</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Volumi di ricerca, CPC, competition e keyword correlate forniti da Google Keyword Insight tramite RapidAPI.</p>
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
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Clustering AI</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 crediti</span>
                    </td>
                </tr>
                <tr class="bg-white dark:bg-slate-800/50">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Architettura Sito</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 crediti</span>
                    </td>
                </tr>
                <tr class="bg-white dark:bg-slate-800/50">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Piano Editoriale</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 crediti</span>
                    </td>
                </tr>
                <tr class="bg-white dark:bg-slate-800/50">
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">Quick Check</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">GRATIS</span>
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
                <h3 class="font-medium text-blue-900 dark:text-blue-200 text-sm">Inizia con il Quick Check</h3>
                <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">Usa il Quick Check per verificare rapidamente il potenziale di una keyword prima di avviare una ricerca completa. E gratuito e ti permette di risparmiare crediti.</p>
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
                <h3 class="font-medium text-blue-900 dark:text-blue-200 text-sm">Diversifica le keyword seed</h3>
                <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">Nella Research Guidata, inserisci keyword seed diverse tra loro per coprire angolazioni differenti del tuo settore e ampliare la copertura semantica.</p>
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
                <h3 class="font-medium text-blue-900 dark:text-blue-200 text-sm">Architettura Sito per nuovi progetti</h3>
                <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">L'Architettura Sito e perfetta per nuovi progetti web: ti indica esattamente quali pagine creare, con URL suggeriti, H1 ottimizzati e keyword principali per ciascuna.</p>
            </div>
        </div>
    </div>
</section>

<!-- Nav link -->
<div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-700 flex justify-between">
    <a href="<?= url('/docs/seo-tracking') ?>" class="flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        SEO Tracking
    </a>
    <a href="<?= url('/docs/internal-links') ?>" class="flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
        Internal Links
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>