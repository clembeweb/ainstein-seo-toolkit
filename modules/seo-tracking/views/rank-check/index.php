<?php
/**
 * Rank Checker View - Versione Semplificata
 * Verifica posizioni SERP reali per le keyword del progetto
 */

$currentPage = 'rank-check';
$projectId = $project['id'];
$targetDomain = $defaultDomain ?: '';
?>
<div class="space-y-6" x-data="rankChecker()">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <?php if (!$serpApiConfigured): ?>
    <!-- Warning: Nessun provider SERP configurato -->
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
        <div class="flex">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">Nessun provider SERP configurato</h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">Per utilizzare questa funzionalità, configura almeno una chiave API (Serper.dev o SERP API) in <a href="<?= url('/admin/settings') ?>" class="underline hover:no-underline">Admin &gt; Impostazioni</a>.</p>
            </div>
        </div>
    </div>
    <?php else: ?>

    <!-- Info Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center gap-6">
            <div>
                <span class="text-sm text-slate-500 dark:text-slate-400">Crediti disponibili:</span>
                <span class="ml-1 font-semibold text-slate-900 dark:text-white"><?= number_format($userCredits, 1) ?></span>
            </div>
            <div>
                <span class="text-sm text-slate-500 dark:text-slate-400">Costo per verifica:</span>
                <span class="ml-1 font-semibold text-slate-900 dark:text-white"><?= $creditCost ?> credito</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-slate-500 dark:text-slate-400">Provider:</span>
                <?php if ($providersInfo['serper']['configured']): ?>
                <span class="px-2 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded">Serper.dev</span>
                <?php endif; ?>
                <?php if ($providersInfo['serpapi']['configured']): ?>
                <span class="px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 rounded"><?= $providersInfo['serper']['configured'] ? 'SERP API (fallback)' : 'SERP API' ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <select x-model="device" class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-700 dark:text-white">
                <option value="desktop">Desktop</option>
                <option value="mobile">Mobile</option>
            </select>
            <a href="<?= url("/seo-tracking/project/{$projectId}/rank-check/history") ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Storico
            </a>
        </div>
    </div>

    <!-- Keyword del Progetto -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Keyword del Progetto</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Seleziona le keyword da verificare su SERP</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-500 dark:text-slate-400" x-show="keywords.length > 0">
                    <span x-text="selectedCount"></span> selezionate
                </span>
                <button @click="checkSelected()"
                        :disabled="selectedCount === 0 || checking"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors text-sm font-medium">
                    <svg x-show="!checking" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <svg x-show="checking" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Verifica (<span x-text="selectedCount"></span> crediti)
                </button>
            </div>
        </div>

        <!-- Progress bar -->
        <div x-show="checking" class="px-6 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-400 mb-1">
                <span>
                    Verifica in corso...
                    <span x-show="lastProvider" class="text-xs text-slate-500" x-text="'via ' + lastProvider"></span>
                </span>
                <span x-text="checkProgress + '%'"></span>
            </div>
            <div class="w-full bg-slate-200 dark:bg-slate-600 rounded-full h-2">
                <div class="bg-primary-600 h-2 rounded-full transition-all" :style="'width: ' + checkProgress + '%'"></div>
            </div>
        </div>

        <!-- Loading -->
        <div x-show="loading" class="p-12 text-center">
            <svg class="w-8 h-8 mx-auto animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Caricamento keyword...</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && keywords.length === 0" class="p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            <p class="mt-2 text-slate-500 dark:text-slate-400">Nessuna keyword tracciata nel progetto</p>
            <a href="<?= url("/seo-tracking/project/{$projectId}/keywords/add") ?>"
               class="inline-flex items-center mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Aggiungi Keyword
            </a>
        </div>

        <!-- Tabella keyword -->
        <div x-show="!loading && keywords.length > 0" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left w-10">
                            <input type="checkbox" @change="toggleAll($event)" class="rounded border-slate-300 dark:border-slate-600">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pos. GSC</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pos. SERP</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Diff</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Stato</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                    <template x-for="(kw, index) in keywords" :key="kw.id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3">
                                <input type="checkbox" x-model="kw.selected" class="rounded border-slate-300 dark:border-slate-600" :disabled="kw.checking">
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-slate-900 dark:text-white" x-text="kw.keyword"></span>
                                <span x-show="kw.target_url" class="block text-xs text-slate-400 truncate max-w-xs" x-text="kw.target_url"></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-medium" :class="getPositionClass(kw.last_position)" x-text="kw.last_position ? kw.last_position.toFixed(1) : '-'"></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-semibold"
                                      :class="kw.serp_position ? getPositionClass(kw.serp_position) : 'text-slate-400'"
                                      x-text="kw.serp_position || (kw.checked && !kw.serp_position ? 'N/F' : '-')"></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span x-show="kw.position_diff !== null" class="font-semibold"
                                      :class="getDiffClass(kw.position_diff)"
                                      x-text="(kw.position_diff > 0 ? '+' : '') + kw.position_diff.toFixed(1)"></span>
                                <span x-show="kw.position_diff === null" class="text-slate-400">-</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span x-show="!kw.checked && !kw.checking" class="text-slate-400">—</span>
                                <svg x-show="kw.checking" class="w-5 h-5 mx-auto animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <div x-show="kw.checked && kw.serp_position" class="flex items-center justify-center gap-1">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-show="kw.provider" class="text-[10px] text-slate-400" x-text="kw.provider === 'Serper.dev' ? 'S' : 'API'" :title="kw.provider"></span>
                                </div>
                                <svg x-show="kw.checked && !kw.serp_position" class="w-5 h-5 mx-auto text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <?php if ($stats['total_checks'] > 0): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Check Totali</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= $stats['total_checks'] ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-emerald-200 dark:border-emerald-800 p-4">
            <p class="text-sm text-emerald-600 dark:text-emerald-400">Trovati</p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1"><?= $stats['found_count'] ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-blue-200 dark:border-blue-800 p-4">
            <p class="text-sm text-blue-600 dark:text-blue-400">Top 10</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1"><?= $stats['top10_count'] ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Pos. Media</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= $stats['avg_position'] ? number_format($stats['avg_position'], 1) : '-' ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function rankChecker() {
    return {
        loading: true,
        keywords: [],
        checking: false,
        checkProgress: 0,
        device: 'desktop',
        lastProvider: '',

        projectId: <?= $projectId ?>,
        targetDomain: '<?= addslashes($targetDomain) ?>',
        baseUrl: '<?= url('') ?>',

        get selectedCount() {
            return this.keywords.filter(k => k.selected && !k.checked).length;
        },

        async init() {
            await this.loadKeywords();
        },

        async loadKeywords() {
            this.loading = true;
            try {
                const resp = await fetch(`${this.baseUrl}/seo-tracking/project/${this.projectId}/api/tracked-keywords`);
                const data = await resp.json();

                if (data.success) {
                    this.keywords = data.keywords.map(k => ({
                        ...k,
                        selected: false,
                        checked: false,
                        checking: false,
                        serp_position: null,
                        position_diff: null
                    }));
                }
            } catch (e) {
                console.error('Errore caricamento keyword:', e);
            } finally {
                this.loading = false;
            }
        },

        toggleAll(event) {
            const checked = event.target.checked;
            this.keywords.forEach(k => {
                if (!k.checked && !k.checking) k.selected = checked;
            });
        },

        async checkSelected() {
            const toCheck = this.keywords.filter(k => k.selected && !k.checked);
            if (toCheck.length === 0) return;

            this.checking = true;
            this.checkProgress = 0;

            for (let i = 0; i < toCheck.length; i++) {
                const kw = toCheck[i];
                kw.checking = true;

                try {
                    const formData = new FormData();
                    formData.append('keyword', kw.keyword);
                    formData.append('target_domain', this.targetDomain);
                    formData.append('device', this.device);
                    formData.append('gsc_position', kw.last_position || '');

                    const resp = await fetch(`${this.baseUrl}/seo-tracking/project/${this.projectId}/rank-check/single`, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await resp.json();

                    if (data.error) {
                        console.error(kw.keyword, data.error);
                        alert('Errore: ' + data.error);
                        break;
                    } else {
                        kw.serp_position = data.serp_position;
                        kw.position_diff = data.position_diff;
                        kw.provider = data.provider;
                        this.lastProvider = data.provider || '';
                    }
                } catch (e) {
                    console.error(kw.keyword, e);
                }

                kw.checking = false;
                kw.checked = true;
                kw.selected = false;
                this.checkProgress = Math.round(((i + 1) / toCheck.length) * 100);

                // Rate limit: 1 secondo tra richieste
                if (i < toCheck.length - 1) {
                    await new Promise(r => setTimeout(r, 1000));
                }
            }

            this.checking = false;
        },

        getPositionClass(pos) {
            if (!pos) return 'text-slate-400';
            if (pos <= 3) return 'text-emerald-600 dark:text-emerald-400';
            if (pos <= 10) return 'text-blue-600 dark:text-blue-400';
            if (pos <= 20) return 'text-amber-600 dark:text-amber-400';
            return 'text-red-600 dark:text-red-400';
        },

        getDiffClass(diff) {
            if (diff === null) return 'text-slate-400';
            if (diff <= -3) return 'text-emerald-600 dark:text-emerald-400'; // SERP migliore di GSC
            if (diff >= 3) return 'text-red-600 dark:text-red-400'; // SERP peggiore di GSC
            return 'text-amber-600 dark:text-amber-400'; // Simile
        }
    }
}
</script>
