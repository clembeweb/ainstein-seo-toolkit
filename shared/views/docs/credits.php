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
        I crediti sono la valuta della piattaforma Ainstein. Ogni operazione che utilizza l'intelligenza artificiale o servizi esterni ha un costo in crediti, che viene sempre mostrato prima dell'esecuzione, in modo da avere pieno controllo sul consumo.
    </p>
</div>

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
            <p class="text-slate-600 dark:text-slate-400">Ogni operazione AI ha un <strong class="text-slate-900 dark:text-white">costo predefinito</strong> in crediti, visibile prima di confermare l'esecuzione.</p>
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
            <p class="text-slate-600 dark:text-slate-400">Alcune operazioni base come la navigazione, il <strong class="text-slate-900 dark:text-white">Quick Check</strong> delle keyword e la visualizzazione dei report sono <strong class="text-slate-900 dark:text-white">gratuite</strong>.</p>
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">3 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Scraping URL competitor</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">1 credito/URL</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Generazione Brief strategico</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">5 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Generazione Articolo completo</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">10 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Immagine di copertina (DALL-E 3)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">3 crediti</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">AI Overview (analisi completa)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">15 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">AI Category (analisi per categoria)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">3 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Sincronizzazione GSC</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">5 crediti</span>
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
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">GSC Full Sync (sincronizzazione completa)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">10 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Quick Wins (opportunità rapide)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">2 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Report Settimanale</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">5 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Report Mensile</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">15 crediti</span>
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
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Clustering AI (&lt; 100 keyword)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">2 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Clustering AI (&gt; 100 keyword)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">5 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Architettura Sito</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">5 crediti</span>
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
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Estrazione contesto landing page</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">3 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Analisi singola campagna</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">2 crediti</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">Analisi in blocco (per campagna)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">1.5 crediti</span>
                            </td>
                        </tr>
                    </tbody>
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">0.5 crediti</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<!-- Ottenere crediti -->
<section class="mb-12">
    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Ottenere crediti
    </h2>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Bonus registrazione -->
        <div class="p-5 rounded-xl border-2 border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-6 h-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                </svg>
                <h3 class="font-semibold text-emerald-800 dark:text-emerald-300">Bonus Registrazione</h3>
            </div>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mb-1">50 crediti</p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400">Ricevi immediatamente 50 crediti gratuiti alla creazione dell'account. Nessuna carta di credito richiesta.</p>
        </div>

        <!-- Piani abbonamento -->
        <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">Piani Abbonamento</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Scegli il piano mensile o annuale piu adatto alle tue esigenze. I crediti del piano si rinnovano ogni mese.</p>
        </div>

        <!-- Bonus admin -->
        <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">Bonus Speciali</h3>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Crediti bonus possono essere assegnati dall'amministratore per promozioni o riconoscimenti speciali. I bonus non scadono mai.</p>
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
                <p class="text-sm text-slate-600 dark:text-slate-400">Il tuo saldo crediti è sempre visibile nel badge nella barra di navigazione superiore dell'applicazione. Cliccando sul badge puoi accedere rapidamente al riepilogo.</p>
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
                    Quando il tuo saldo scende sotto i 10 crediti, il badge nella barra superiore diventa arancione per avvisarti. Assicurati di avere crediti sufficienti prima di avviare operazioni complesse come la generazione di articoli o report completi.
                </p>
            </div>
        </div>
    </div>
</section>