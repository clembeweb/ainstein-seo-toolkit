<?php $currentPage = 'quick-wins'; ?>
<div class="space-y-6" x-data="quickWinsApp()">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <?php if ($group): ?>
    <p class="text-sm text-slate-500 dark:text-slate-400">
        Gruppo: <?= e($group['name']) ?>
    </p>
    <?php endif; ?>

    <!-- Info Box -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-6">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <svg class="w-8 h-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Cos'e Quick Wins?</h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    Quick Wins identifica le keyword con <strong>alto potenziale di miglioramento</strong>:
                    keyword in posizione 4-20 con buone impressioni che potrebbero facilmente salire in Top 3.
                </p>
                <ul class="mt-3 text-sm text-amber-700 dark:text-amber-300 space-y-1">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Posizione attuale: 4-20
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Impressioni minime: 100
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        L'AI analizza e suggerisce azioni concrete
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Candidate Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= count($candidates) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Keyword eligibili</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= $creditCost ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Crediti richiesti</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-violet-100 dark:bg-violet-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= $userCredits ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">I tuoi crediti</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyze Button -->
    <?php if (!$isConfigured): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-sm text-red-700 dark:text-red-300">API AI non configurata. Contatta l'amministratore.</p>
        </div>
    </div>
    <?php elseif (count($candidates) === 0): ?>
    <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg p-8 text-center">
        <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessuna keyword eligibile</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Non ci sono keyword in posizione 4-20 con almeno 100 impressioni.<br>
            Sincronizza i dati GSC o aggiungi piu keyword al tracking.
        </p>
    </div>
    <?php elseif ($userCredits < $creditCost): ?>
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-sm text-amber-700 dark:text-amber-300">
                Crediti insufficienti. Hai <?= $userCredits ?> crediti, ne servono <?= $creditCost ?>.
            </p>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Pronto per l'analisi</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Verranno analizzate <?= count($candidates) ?> keyword per identificare le migliori opportunita
                </p>
            </div>
            <button
                @click="analyze()"
                :disabled="loading"
                class="inline-flex items-center px-6 py-3 rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold shadow-lg hover:from-amber-600 hover:to-orange-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
                <template x-if="!loading">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Analizza Quick Wins
                    </span>
                </template>
                <template x-if="loading">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Analisi in corso...
                    </span>
                </template>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Loading State -->
    <div x-show="loading" x-cloak class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-8">
        <div class="flex flex-col items-center justify-center">
            <div class="relative">
                <div class="w-16 h-16 border-4 border-amber-200 dark:border-amber-800 rounded-full"></div>
                <div class="absolute top-0 left-0 w-16 h-16 border-4 border-amber-500 rounded-full border-t-transparent animate-spin"></div>
            </div>
            <p class="mt-4 text-lg font-medium text-slate-900 dark:text-white">Analisi AI in corso</p>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">L'intelligenza artificiale sta analizzando le tue keyword...</p>
            <div class="mt-4 flex items-center gap-2 text-xs text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Questo processo richiede circa 30-60 secondi
            </div>
        </div>
    </div>

    <!-- Error Message -->
    <div x-show="error" x-cloak class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
        </div>
    </div>

    <!-- Results -->
    <div x-show="results" x-cloak>
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-emerald-100 text-sm font-medium">Opportunita trovate</p>
                        <p class="text-3xl font-bold mt-1" x-text="results?.summary?.total_opportunities || 0"></p>
                    </div>
                    <div class="p-3 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Click potenziali</p>
                        <p class="text-3xl font-bold mt-1">+<span x-text="results?.summary?.estimated_click_increase || 0"></span></p>
                    </div>
                    <div class="p-3 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-amber-100 text-sm font-medium">Alta priorita</p>
                        <p class="text-3xl font-bold mt-1" x-text="results?.summary?.top_priority_count || 0"></p>
                    </div>
                    <div class="p-3 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opportunities List -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Opportunita Quick Wins</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Ordinate per impatto potenziale</p>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <template x-for="opp in results?.opportunities || []" :key="opp.rank">
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-white"
                                     :class="opp.impact === 'high' ? 'bg-emerald-500' : (opp.impact === 'medium' ? 'bg-blue-500' : 'bg-slate-400')"
                                     x-text="opp.rank">
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-900 dark:text-white" x-text="opp.keyword"></h4>
                                    <div class="flex items-center gap-4 mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        <span>Pos: <span class="font-medium" x-text="opp.current_position?.toFixed(1)"></span></span>
                                        <span>Click: <span class="font-medium" x-text="opp.current_clicks"></span></span>
                                        <span>Impr: <span class="font-medium" x-text="opp.current_impressions"></span></span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">+<span x-text="opp.potential_clicks"></span></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">click potenziali</p>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-2">
                            <span class="px-2 py-1 rounded text-xs font-medium"
                                  :class="opp.impact === 'high' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : (opp.impact === 'medium' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300')"
                                  x-text="opp.impact === 'high' ? 'Alto impatto' : (opp.impact === 'medium' ? 'Medio impatto' : 'Basso impatto')">
                            </span>
                            <span class="px-2 py-1 rounded text-xs font-medium"
                                  :class="opp.difficulty === 'facile' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : (opp.difficulty === 'media' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300')"
                                  x-text="'Difficolta: ' + opp.difficulty">
                            </span>
                        </div>

                        <div class="mt-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase mb-2">Suggerimenti</p>
                            <ul class="space-y-2">
                                <template x-for="(suggestion, idx) in opp.suggestions || []" :key="idx">
                                    <li class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300">
                                        <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        <span x-text="suggestion"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- General Recommendations -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6" x-show="results?.recommendations?.length > 0">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Raccomandazioni Generali</h3>
            <ul class="space-y-3">
                <template x-for="(rec, idx) in results?.recommendations || []" :key="idx">
                    <li class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center">
                            <span class="text-xs font-bold text-primary-600 dark:text-primary-400" x-text="idx + 1"></span>
                        </div>
                        <p class="text-sm text-slate-700 dark:text-slate-300" x-text="rec"></p>
                    </li>
                </template>
            </ul>
        </div>

        <!-- Credits Used -->
        <div class="mt-4 text-center text-sm text-slate-500 dark:text-slate-400">
            <span x-show="creditsUsed">Crediti utilizzati: <span class="font-medium" x-text="creditsUsed"></span></span>
            <span x-show="keywordsAnalyzed"> | Keyword analizzate: <span class="font-medium" x-text="keywordsAnalyzed"></span></span>
        </div>
    </div>

    <!-- Candidate Keywords Preview -->
    <?php if (count($candidates) > 0 && $isConfigured): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Keyword Candidate</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Preview delle keyword che verranno analizzate</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Click</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Impressioni</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">CTR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach (array_slice($candidates, 0, 10) as $kw): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate max-w-xs"><?= e($kw['keyword']) ?></p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <?php
                            $pos = $kw['position'] ?? 0;
                            $posClass = $pos <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300';
                            ?>
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $posClass ?>">
                                <?= number_format($pos, 1) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-slate-900 dark:text-white"><?= number_format($kw['clicks'] ?? 0) ?></td>
                        <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400"><?= number_format($kw['impressions'] ?? 0) ?></td>
                        <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400"><?= number_format(($kw['ctr'] ?? 0) * 100, 2) ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($candidates) > 10): ?>
        <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/50 text-center text-sm text-slate-500 dark:text-slate-400">
            ... e altre <?= count($candidates) - 10 ?> keyword
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
[x-cloak] { display: none !important; }
</style>

<script>
function quickWinsApp() {
    return {
        loading: false,
        error: null,
        results: null,
        creditsUsed: 0,
        keywordsAnalyzed: 0,

        async analyze() {
            this.loading = true;
            this.error = null;
            this.results = null;

            try {
                <?php if ($group): ?>
                const url = '<?= url('/seo-tracking/project/' . $project['id'] . '/groups/' . $group['id'] . '/ai/quick-wins/analyze') ?>';
                <?php else: ?>
                const url = '<?= url('/seo-tracking/project/' . $project['id'] . '/ai/quick-wins/analyze') ?>';
                <?php endif; ?>

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= csrf_token() ?>'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Errore durante l\'analisi');
                }

                this.results = data.data;
                this.creditsUsed = data.credits_used;
                this.keywordsAnalyzed = data.keywords_analyzed;

            } catch (err) {
                this.error = err.message;
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
