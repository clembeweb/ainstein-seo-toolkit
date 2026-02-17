<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-8">
    <a href="<?= url('/docs') ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Documentazione</a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-slate-900 dark:text-white font-medium">Sistema Crediti</span>
</nav>

<!-- Header -->
<div class="mb-10">
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">Sistema Crediti</h1>
    <p class="text-lg text-slate-600 dark:text-slate-400 leading-relaxed">
        I crediti sono la valuta della piattaforma Ainstein. Ogni operazione ha un costo fisso che rientra in uno dei <strong class="text-slate-900 dark:text-white">4 livelli</strong> di prezzo, rendendo semplice e prevedibile il consumo.
    </p>
</div>

<!-- I 4 Livelli -->
<section class="mb-12">
    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
        </svg>
        I 4 livelli di prezzo
    </h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Gratis -->
        <div class="p-5 rounded-xl border-2 border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 text-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 mb-3">Gratis</span>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">0 cr</p>
            <p class="mt-2 text-sm text-emerald-700 dark:text-emerald-400">Consultazione, export, Quick Check, navigazione</p>
        </div>

        <!-- Base -->
        <div class="p-5 rounded-xl border-2 border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 text-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 mb-3">Base</span>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">1 cr</p>
            <p class="mt-2 text-sm text-blue-700 dark:text-blue-400">Scraping, rank check, volumi keyword, scan pagina</p>
        </div>

        <!-- Standard -->
        <div class="p-5 rounded-xl border-2 border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 text-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 mb-3">Standard</span>
            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">3 cr</p>
            <p class="mt-2 text-sm text-amber-700 dark:text-amber-400">Operazioni AI singole: brief, analisi, clustering, report</p>
        </div>

        <!-- Premium -->
        <div class="p-5 rounded-xl border-2 border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/20 text-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 mb-3">Premium</span>
            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">10 cr</p>
            <p class="mt-2 text-sm text-purple-700 dark:text-purple-400">Operazioni AI complesse: articoli, report executive, piani</p>
        </div>
    </div>
</section>

<!-- Come funzionano -->
<section class="mb-12">
    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-3">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Come funzionano
    </h2>

    <div class="space-y-4 ml-10">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-slate-600 dark:text-slate-400">Ogni operazione ha un <strong class="text-slate-900 dark:text-white">costo fisso</strong> in uno dei 4 livelli (Gratis, Base, Standard, Premium), visibile prima di confermare.</p>
        </div>
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-slate-600 dark:text-slate-400">I crediti vengono scalati <strong class="text-slate-900 dark:text-white">solo al completamento</strong> dell'operazione. Se un'operazione fallisce, non vengono addebitati crediti.</p>
        </div>
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-slate-600 dark:text-slate-400">Se non hai crediti sufficienti, l'operazione <strong class="text-slate-900 dark:text-white">non parte</strong> e riceverai un avviso con il costo richiesto.</p>
        </div>
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-slate-600 dark:text-slate-400">Navigazione, export CSV e Quick Check keyword sono sempre <strong class="text-slate-900 dark:text-white">gratuiti</strong>.</p>
        </div>
    </div>
</section>

<!-- Costi per modulo -->
<section class="mb-12">
    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
        Costi per modulo
    </h2>

    <div class="space-y-8">

        <!-- AI Content Generator -->
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-800 px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">AI Content Generator</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="text-left px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Operazione</th>
                            <th class="text-right px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Estrazione SERP</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Scraping URL competitor</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">1 cr/URL</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Generazione Brief strategico</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Generazione Articolo completo</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Immagine di copertina (DALL-E 3)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Scraping + Meta Tag AI</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">1 cr</span>
                                <span class="text-slate-400 mx-1">+</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400">
                Articolo completo end-to-end: SERP 3 + Scraping ~3 + Brief 3 + Articolo 10 + Cover 3 = <strong class="text-slate-700 dark:text-slate-300">~22 cr</strong>
            </div>
        </div>

        <!-- SEO Audit -->
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-800 px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">SEO Audit</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="text-left px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Operazione</th>
                            <th class="text-right px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Crawling sito</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">Gratuito</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Piano d'Azione AI (analisi completa)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 cr</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SEO Tracking -->
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-800 px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">SEO Tracking</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="text-left px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Operazione</th>
                            <th class="text-right px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Rank check (per keyword)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">1 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Aggiornamento volumi keyword</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">1 cr/kw</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">GSC Full Sync (16 mesi)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Quick Wins (opportunita rapide)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Digest settimanale</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Report Executive mensile</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Analisi keyword AI</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Analisi anomalie</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Analisi pagina</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Keyword Research -->
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-800 px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">Keyword Research</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="text-left px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Operazione</th>
                            <th class="text-right px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Clustering AI</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Architettura Sito</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Piano Editoriale</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Quick Check</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">Gratuito</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Google Ads Analyzer -->
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-800 px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">Google Ads Analyzer</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="text-left px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Operazione</th>
                            <th class="text-right px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Valutazione AI campagne</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Estrazione contesto business</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Analisi keyword negative (per ad group)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Genera con AI (copy, estensioni, keyword)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">1 cr</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mb-1">Campaign Creator</p>
                <table class="w-full text-xs text-slate-500 dark:text-slate-400">
                    <tr>
                        <td class="py-0.5">Scraping landing page</td>
                        <td class="text-right"><span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">1 cr</span></td>
                    </tr>
                    <tr>
                        <td class="py-0.5">Keyword research AI</td>
                        <td class="text-right"><span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span></td>
                    </tr>
                    <tr>
                        <td class="py-0.5">Generazione campagna</td>
                        <td class="text-right"><span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">10 cr</span></td>
                    </tr>
                    <tr class="border-t border-slate-200 dark:border-slate-700">
                        <td class="py-1 font-medium text-slate-700 dark:text-slate-300">Totale Campaign Creator</td>
                        <td class="text-right"><span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">~14 cr</span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Internal Links -->
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-800 px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">Internal Links</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="text-left px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Operazione</th>
                            <th class="text-right px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Analisi link interni (per pagina)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">1 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Suggerimenti AI</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Content Creator -->
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-800 px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">Content Creator</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="text-left px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Operazione</th>
                            <th class="text-right px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Scraping pagina (per contesto)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">1 cr/URL</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Generazione contenuto AI</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">3 cr</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Export CSV / Push CMS</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">Gratuito</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<!-- Piani -->
<section class="mb-12">
    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
        Piani e crediti
    </h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Free -->
        <div class="p-5 rounded-xl border-2 border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20">
            <h3 class="font-semibold text-emerald-800 dark:text-emerald-300 mb-1">Free</h3>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mb-1">30 cr/mese</p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mb-3">Gratis per sempre</p>
            <ul class="text-xs text-emerald-700 dark:text-emerald-400 space-y-1">
                <li>3 articoli completi</li>
                <li>10 analisi AI</li>
                <li>30 rank check</li>
            </ul>
        </div>

        <!-- Starter -->
        <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Starter</h3>
            <p class="text-3xl font-bold text-slate-900 dark:text-white mb-1">150 cr/mese</p>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">&euro;19/mese &middot; &euro;190/anno</p>
            <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1">
                <li>15 articoli completi</li>
                <li>50 analisi AI</li>
                <li>150 rank check</li>
            </ul>
        </div>

        <!-- Pro -->
        <div class="p-5 rounded-xl border-2 border-primary-300 dark:border-primary-700 bg-primary-50 dark:bg-primary-900/20 relative">
            <span class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold bg-primary-600 text-white">Consigliato</span>
            <h3 class="font-semibold text-primary-800 dark:text-primary-300 mb-1">Pro</h3>
            <p class="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-1">500 cr/mese</p>
            <p class="text-sm text-primary-600 dark:text-primary-400 mb-3">&euro;49/mese &middot; &euro;490/anno</p>
            <ul class="text-xs text-primary-700 dark:text-primary-400 space-y-1">
                <li>50 articoli completi</li>
                <li>166 analisi AI</li>
                <li>500 rank check</li>
            </ul>
        </div>

        <!-- Agency -->
        <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Agency</h3>
            <p class="text-3xl font-bold text-slate-900 dark:text-white mb-1">1.500 cr/mese</p>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">&euro;99/mese &middot; &euro;990/anno</p>
            <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1">
                <li>150 articoli completi</li>
                <li>500 analisi AI</li>
                <li>1.500 rank check</li>
            </ul>
        </div>
    </div>
</section>

<!-- Monitoraggio -->
<section class="mb-8">
    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-3">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        Monitoraggio del saldo
    </h2>

    <div class="space-y-4">
        <div class="flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Saldo nella barra superiore</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Il tuo saldo crediti e sempre visibile nel badge nella barra di navigazione superiore dell'applicazione. Cliccando sul badge puoi accedere rapidamente al riepilogo.</p>
            </div>
        </div>

        <div class="flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Storico transazioni</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Nella sezione Profilo trovi lo storico completo di tutte le transazioni: operazioni eseguite, crediti consumati, bonus ricevuti e ricariche effettuate.</p>
            </div>
        </div>
    </div>

    <!-- Warning crediti bassi -->
    <div class="mt-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-5">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1">Crediti in esaurimento?</p>
                <p class="text-sm text-amber-700 dark:text-amber-400">
                    Quando il tuo saldo scende sotto i 10 crediti, il badge nella barra superiore diventa arancione per avvisarti. Assicurati di avere crediti sufficienti prima di avviare operazioni complesse come la generazione di articoli o report.
                </p>
            </div>
        </div>
    </div>
</section>