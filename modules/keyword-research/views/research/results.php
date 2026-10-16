<?php $krStep = 'research'; include __DIR__ . '/../partials/project-steps.php'; ?>
<?php
// Raccogli intent unici per il filtro
$uniqueIntents = array_unique(array_filter(array_map(fn($c) => strtolower($c['intent'] ?? ''), $clusters)));
sort($uniqueIntents);
?>
<?php include __DIR__ . '/../partials/table-helpers.php'; ?>
<?php include_once __DIR__ . '/../partials/intent-label.php'; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
                <a href="<?= url('/keyword-research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">AI Keywords</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="<?= url('/keyword-research/project/' . $project['id'] . '/research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 dark:text-white">Risultati</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Risultati della ricerca</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= date('d/m/Y H:i', strtotime($research['created_at'])) ?> · <?= e(mb_strimwidth((string) ($brief['business'] ?? ''), 0, 140, '…')) ?>
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <a href="<?= url('/keyword-research/project/' . $project['id'] . '/research/' . $research['id'] . '/export') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Esporta CSV
            </a>
            <a href="<?= url('/keyword-research/project/' . $project['id'] . '/research') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuova ricerca
            </a>
        </div>
    </div>

    <?= \Core\View::partial('components/orphaned-project-notice', ['project' => $project]) ?>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($research['raw_keywords_count'] ?? 0, 0, ',', '.') ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Ricerche trovate</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($research['filtered_keywords_count'] ?? 0, 0, ',', '.') ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Date all'AI</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= count($clusters) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Gruppi</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalKeywords, 0, ',', '.') ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Keyword nei gruppi</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalVolume, 0, ',', '.') ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Volume totale</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5" title="Google dà lo stesso volume a 'giro in barca capri' e 'capri giro in barca': qui contano una volta sola">varianti della stessa ricerca contate una volta</p>
        </div>
    </div>

    <!-- Nota strategica: da dove partire + 3 azioni -->
    <?php $note = $research['strategy_note'] ?? ''; $noteColor = 'emerald'; include __DIR__ . '/../partials/strategy-note.php'; ?>

    <!-- E adesso? -->
    <?php
    $nextClusters = array_map(function ($c) {
        $main = null;
        foreach ($c['keywords_list'] ?? [] as $kw) {
            if (!empty($kw['is_main']) || mb_strtolower($kw['text']) === mb_strtolower((string) $c['main_keyword'])) {
                $main = $kw;
                break;
            }
        }
        return [
            'id' => (int) $c['id'],
            'name' => (string) $c['name'],
            'main_keyword' => (string) $c['main_keyword'],
            'main_volume' => (int) $c['main_volume'],
            'kw' => [
                'text' => (string) $c['main_keyword'],
                'volume' => (int) ($main['volume'] ?? $c['main_volume']),
                'competition_level' => $main['competition_level'] ?? '',
                'intent' => $main['intent'] ?? ($c['intent'] ?? ''),
                'high_bid' => (float) ($main['high_bid'] ?? 0),
            ],
        ];
    }, $clusters);
    $rankTarget = $nextTargets['rank'] ?? null;
    $writerTarget = $nextTargets['writer'] ?? null;
    $activateUrl = !empty($nextTargets['global_project_id']) ? url('/projects/' . (int) $nextTargets['global_project_id']) : url('/projects');
    $canWrite = ($project['access_role'] ?? 'owner') !== 'viewer';
    ?>
    <?php if (!empty($clusters) && $canWrite): ?>
    <div x-data="nextActions()" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">E adesso?</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Scegli i gruppi e mandali dove ti servono: va la keyword principale di ogni gruppo.</p>
        </div>

        <div class="flex items-center gap-3 text-sm">
            <button type="button" @click="setAll(true)" class="text-primary-600 dark:text-primary-400 hover:underline">Tutti</button>
            <span class="text-slate-300 dark:text-slate-600">·</span>
            <button type="button" @click="setAll(false)" class="text-primary-600 dark:text-primary-400 hover:underline">Nessuno</button>
            <span class="text-slate-400 ml-auto" x-text="selectedIds().length + ' gruppi scelti'"></span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-72 overflow-y-auto">
            <template x-for="c in clusters" :key="c.id">
                <label class="flex items-start gap-2 rounded-lg border px-3 py-2 cursor-pointer transition-colors"
                       :class="picked[c.id] ? 'border-primary-300 dark:border-primary-700 bg-primary-50/50 dark:bg-primary-900/20' : 'border-slate-200 dark:border-slate-700'">
                    <input type="checkbox" x-model="picked[c.id]" class="mt-0.5 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-slate-900 dark:text-white truncate" x-text="c.main_keyword"></span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400 truncate" x-text="c.name + ' · ' + fmt(c.main_volume) + ' al mese'"></span>
                    </span>
                </label>
            </template>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- AI Rank -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <p class="font-medium text-slate-900 dark:text-white">Monitora le posizioni su Google</p>
                <?php if ($rankTarget): ?>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Le keyword vanno in AI Rank (<?= e($rankTarget['name']) ?>), un gruppo per ogni gruppo scelto. Il controllo delle posizioni si paga in AI Rank.</p>
                <button type="button" @click="sendToRank()" :disabled="busy || selectedIds().length === 0"
                        class="mt-3 inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    Manda ad AI Rank
                </button>
                <p x-show="rankMsg" class="mt-2 text-sm text-emerald-700 dark:text-emerald-400">
                    <span x-text="rankMsg"></span>
                    <a href="<?= url('/seo-tracking/project/' . (int) $rankTarget['id'] . '/keywords') ?>" class="font-medium underline">Apri AI Rank</a>
                </p>
                <?php else: ?>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">AI Rank non è attivo su questo progetto: attivalo e poi torna qui.</p>
                <a href="<?= $activateUrl ?>" class="mt-3 inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Attiva AI Rank</a>
                <?php endif; ?>
            </div>
            <!-- AI Writer -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <p class="font-medium text-slate-900 dark:text-white">Scrivi gli articoli</p>
                <?php if ($writerTarget): ?>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Le keyword vanno tra quelle di AI Writer (<?= e($writerTarget['name']) ?>), pronte da scrivere. Brief e articolo si pagano in AI Writer.</p>
                <button type="button" @click="sendToWriter()" :disabled="busy || selectedIds().length === 0"
                        class="mt-3 inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    Manda ad AI Writer
                </button>
                <p x-show="writerMsg" class="mt-2 text-sm text-emerald-700 dark:text-emerald-400">
                    <span x-text="writerMsg"></span>
                    <a :href="writerUrl" class="font-medium underline">Apri AI Writer</a>
                </p>
                <?php else: ?>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">AI Writer (articoli) non è attivo su questo progetto: attivalo e poi torna qui.</p>
                <a href="<?= $activateUrl ?>" class="mt-3 inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Attiva AI Writer</a>
                <?php endif; ?>
            </div>
        </div>
        <p x-show="errorMsg" x-text="errorMsg" class="text-sm text-red-600 dark:text-red-400"></p>
    </div>

    <script>
    function nextActions() {
        return {
            clusters: <?= json_encode($nextClusters, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) ?>,
            picked: {},
            busy: false,
            rankMsg: '',
            writerMsg: '',
            writerUrl: '',
            errorMsg: '',

            init() {
                this.setAll(true);
            },
            setAll(value) {
                const p = {};
                this.clusters.forEach(c => { p[c.id] = value; });
                this.picked = p;
            },
            selectedIds() {
                return this.clusters.filter(c => this.picked[c.id]).map(c => c.id);
            },
            fmt(n) {
                return new Intl.NumberFormat('it-IT', { useGrouping: 'always' }).format(Number(n) || 0);
            },
            doneText(added, skipped) {
                let t = added === 1 ? '1 keyword aggiunta' : `${added} keyword aggiunte`;
                if (skipped) t += skipped === 1 ? ", 1 c'era già" : `, ${skipped} c'erano già`;
                return t + '.';
            },

            async sendToRank() {
                <?php if ($rankTarget): ?>
                this.busy = true; this.errorMsg = ''; this.rankMsg = '';
                let added = 0, skipped = 0;
                try {
                    for (const c of this.clusters.filter(c => this.picked[c.id])) {
                        const fd = new FormData();
                        fd.append('_csrf_token', '<?= csrf_token() ?>');
                        fd.append('project_id', '<?= (int) $rankTarget['id'] ?>');
                        fd.append('group_name', c.name);
                        fd.append('location', '<?= e($brief['geography'] ?? 'IT') ?>');
                        fd.append('keywords_data', JSON.stringify([c.kw]));
                        const resp = await fetch('<?= url('/keyword-research/quick-check/send-to-tracking') ?>', { method: 'POST', body: fd });
                        if (!resp.ok) { throw new Error(`Errore server (${resp.status})`); }
                        const data = await resp.json();
                        if (!data.success) { throw new Error(data.error || 'Invio non riuscito'); }
                        added += data.added || 0;
                        skipped += data.skipped || 0;
                    }
                    this.rankMsg = this.doneText(added, skipped);
                } catch (e) {
                    this.errorMsg = e.message || 'Errore di connessione, riprova.';
                } finally {
                    this.busy = false;
                }
                <?php endif; ?>
            },

            async sendToWriter() {
                <?php if ($writerTarget): ?>
                this.busy = true; this.errorMsg = ''; this.writerMsg = '';
                try {
                    const fd = new FormData();
                    fd.append('_csrf_token', '<?= csrf_token() ?>');
                    fd.append('cluster_ids', JSON.stringify(this.selectedIds()));
                    const resp = await fetch('<?= url('/keyword-research/project/' . (int) $project['id'] . '/research/' . (int) $research['id'] . '/send-to-writer') ?>', { method: 'POST', body: fd });
                    if (!resp.ok) { throw new Error(`Errore server (${resp.status})`); }
                    const data = await resp.json();
                    if (!data.success) { throw new Error(data.error || 'Invio non riuscito'); }
                    this.writerMsg = this.doneText(data.added, data.skipped);
                    this.writerUrl = data.url;
                } catch (e) {
                    this.errorMsg = e.message || 'Errore di connessione, riprova.';
                } finally {
                    this.busy = false;
                }
                <?php endif; ?>
            },
        };
    }
    </script>
    <?php endif; ?>

    <!-- Clusters -->
    <div x-data="clusterResults()">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                    <span x-show="!intentFilter"><?= count($clusters) ?> gruppi</span>
                    <span x-show="intentFilter" x-cloak>
                        Gruppi <span x-text="krIntentLabel(intentFilter)"></span>
                    </span>
                </h2>
                <select x-model="intentFilter"
                        class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Tutti gli intenti</option>
                    <?php foreach ($uniqueIntents as $intent): ?>
                    <option value="<?= e($intent) ?>"><?= e(kr_intent_label($intent)) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" x-model="searchQuery" placeholder="Cerca gruppo o keyword..."
                       class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-3 w-48 focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="flex items-center gap-2">
                <!-- View Toggle -->
                <div class="inline-flex rounded-lg border border-slate-300 dark:border-slate-600 overflow-hidden">
                    <button @click="viewMode = 'card'" :class="viewMode === 'card' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-600'" class="px-3 py-1.5 text-xs font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </button>
                    <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-600'" class="px-3 py-1.5 text-xs font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
                </div>
                <button x-show="viewMode === 'card'" @click="allExpanded = !allExpanded; $dispatch('toggle-all-clusters', { expand: allExpanded })"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <svg x-show="!allExpanded" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                    <svg x-show="allExpanded" x-cloak class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.5 3.5M9 15v4.5M9 15H4.5M9 15l-5.5 5.5M15 9h4.5M15 9V4.5M15 9l5.5-5.5M15 15h4.5M15 15v4.5m0-4.5l5.5 5.5"/>
                    </svg>
                    <span x-text="allExpanded ? 'Comprimi tutti' : 'Espandi tutti'"></span>
                </button>
            </div>
        </div>

        <!-- Table View -->
        <div x-show="viewMode === 'table'" x-cloak>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-700/50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer select-none" @click="toggleTableSort('name')">
                                    <span class="inline-flex items-center gap-1">Cluster <span x-html="tableSortIcon('name')"></span></span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Main Keyword</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer select-none" @click="toggleTableSort('keywords_count')">
                                    <span class="inline-flex items-center gap-1">Keywords <span x-html="tableSortIcon('keywords_count')"></span></span>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer select-none" @click="toggleTableSort('total_volume')">
                                    <span class="inline-flex items-center gap-1 justify-end">Volume Tot. <span x-html="tableSortIcon('total_volume')"></span></span>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer select-none" @click="toggleTableSort('main_volume')">
                                    <span class="inline-flex items-center gap-1 justify-end">Vol. principale <span x-html="tableSortIcon('main_volume')"></span></span>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Intento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <template x-for="(cluster, idx) in filteredTableClusters" :key="cluster.name">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="text-xs font-bold text-slate-400 mr-1" x-text="'#' + cluster._index"></span>
                                        <span class="font-medium text-slate-900 dark:text-white" x-text="cluster.name"></span>
                                        <template x-if="cluster.note">
                                            <p class="text-xs text-slate-500 mt-0.5 truncate max-w-xs" x-text="cluster.note"></p>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300" x-text="cluster.main_keyword"></td>
                                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900 dark:text-white" x-text="cluster.keywords_count"></td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-emerald-600 dark:text-emerald-400" x-text="cluster.total_volume.toLocaleString('it-IT', { useGrouping: 'always' })"></td>
                                    <td class="px-4 py-3 text-right text-sm text-slate-700 dark:text-slate-300" x-text="cluster.main_volume.toLocaleString('it-IT', { useGrouping: 'always' })"></td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="intentBadgeClass(cluster.intent)" x-text="cluster.intent ? krIntentLabel(cluster.intent) : '-'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Card View -->
        <div x-show="viewMode === 'card'" class="space-y-4">
            <?php foreach ($clusters as $i => $cluster): ?>
                <div x-show="cardMatchesFilter(<?= $i ?>)"
                     x-transition>
                    <?php $index = $i; include __DIR__ . '/../partials/cluster-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<script>
function clusterResults() {
    const allClusters = <?= json_encode(array_values(array_map(function($c, $i) {
        return [
            '_index' => $i + 1,
            'name' => $c['name'] ?? '',
            'main_keyword' => $c['main_keyword'] ?? '',
            'keywords_count' => $c['keywords_count'] ?? 0,
            'total_volume' => $c['total_volume'] ?? 0,
            'main_volume' => $c['main_volume'] ?? 0,
            'intent' => strtolower($c['intent'] ?? ''),
            'note' => $c['note'] ?? '',
            'searchText' => strtolower(($c['name'] ?? '') . ' ' . ($c['main_keyword'] ?? '') . ' ' . implode(' ', array_column($c['keywords_list'] ?? [], 'text'))),
        ];
    }, $clusters, array_keys($clusters))), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;

    return {
        viewMode: 'card',
        intentFilter: '',
        searchQuery: '',
        allExpanded: false,
        tableSortField: 'total_volume',
        tableSortDir: 'desc',

        get filteredTableClusters() {
            let list = [...allClusters];
            if (this.intentFilter) {
                list = list.filter(c => c.intent === this.intentFilter);
            }
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(c => c.name.toLowerCase().includes(q) || c.main_keyword.toLowerCase().includes(q));
            }
            const field = this.tableSortField;
            const dir = this.tableSortDir === 'asc' ? 1 : -1;
            list.sort((a, b) => {
                const va = typeof a[field] === 'string' ? a[field].toLowerCase() : a[field];
                const vb = typeof b[field] === 'string' ? b[field].toLowerCase() : b[field];
                return va < vb ? -dir : va > vb ? dir : 0;
            });
            return list;
        },

        cardMatchesFilter(index) {
            const c = allClusters[index];
            if (!c) return false;
            if (this.intentFilter && c.intent !== this.intentFilter) return false;
            if (this.searchQuery && !c.searchText.includes(this.searchQuery.toLowerCase())) return false;
            return true;
        },

        toggleTableSort(field) {
            if (this.tableSortField === field) {
                this.tableSortDir = this.tableSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.tableSortField = field;
                this.tableSortDir = field === 'name' ? 'asc' : 'desc';
            }
        },

        tableSortIcon(field) {
            if (this.tableSortField !== field) return '<svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>';
            return this.tableSortDir === 'asc'
                ? '<svg class="w-3 h-3 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>'
                : '<svg class="w-3 h-3 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
        },

        intentBadgeClass(intent) {
            const map = {
                'informational': 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                'transactional': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                'commercial': 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                'navigational': 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
            };
            return map[(intent || '').toLowerCase()] || 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
        }
    };
}
</script>

    <!-- Excluded Keywords -->
    <?php if (!empty($excludedKeywords)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden" x-data="{ showExcluded: false }">
        <div class="px-6 py-4 cursor-pointer flex items-center justify-between" @click="showExcluded = !showExcluded">
            <h3 class="font-semibold text-slate-900 dark:text-white">
                Keyword escluse dall'AI
                <span class="text-sm font-normal text-slate-500 ml-1">(<?= count($excludedKeywords) ?>)</span>
            </h3>
            <svg class="w-5 h-5 text-slate-400 transition-transform" :class="showExcluded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="showExcluded" x-transition class="border-t border-slate-200 dark:border-slate-700 px-6 py-4">
            <div class="flex flex-wrap gap-2">
                <?php foreach ($excludedKeywords as $kw): ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                    <?= e($kw['text']) ?>
                    <?php if ($kw['volume'] > 0): ?>
                    <span class="ml-1 text-slate-400">(<?= number_format($kw['volume'], 0, ',', '.') ?>)</span>
                    <?php endif; ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <p class="text-center text-xs text-slate-400 dark:text-slate-500">Crediti usati: <?= number_format((float) ($research['credits_used'] ?? 0), 0, ',', '.') ?></p>
</div>
