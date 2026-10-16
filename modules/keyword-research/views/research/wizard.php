<?php $krStep = 'research'; include __DIR__ . '/../partials/project-steps.php'; ?>
<div x-data="researchWizard()" x-cloak class="space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/keyword-research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">AI Keywords</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/keyword-research/projects') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Progetti</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white"><?= e($project['name']) ?></span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Ricerca keyword</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Le ricerche che fanno i tuoi clienti su Google, con i volumi, raggruppate per argomento dall'AI</p>
    </div>

    <?= \Core\View::partial('components/orphaned-project-notice', ['project' => $project]) ?>

    <!-- Progress Steps -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <template x-for="(step, idx) in steps" :key="idx">
                <div class="flex items-center" :class="idx < steps.length - 1 ? 'flex-1' : ''">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                             :class="currentStep > idx ? 'bg-emerald-500 text-white' : (currentStep === idx ? 'bg-primary-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400')">
                            <template x-if="currentStep > idx">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="currentStep <= idx">
                                <span x-text="idx + 1"></span>
                            </template>
                        </div>
                        <span class="text-sm font-medium hidden sm:block"
                              :class="currentStep >= idx ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'"
                              x-text="step"></span>
                    </div>
                    <template x-if="idx < steps.length - 1">
                        <div class="flex-1 mx-4 h-0.5 rounded" :class="currentStep > idx ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Step 1: la tua attività + cosa cerchiamo -->
    <div x-show="currentStep === 0" class="space-y-6">
        <?php $profileHint = 'Le parole da cercare su Google le proponiamo noi.'; include __DIR__ . '/../partials/business-profile.php'; ?>

        <div x-show="profile && !editingProfile && !profileBusy" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
            <!-- Seed Keywords -->
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Cosa cerchiamo su Google</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Le ha proposte l'AI dal profilo: da ognuna Google ci dà decine di ricerche simili con i volumi. Puoi togliere o aggiungere, ma non serve.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <template x-for="(seed, idx) in brief.seeds" :key="idx">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 text-sm">
                            <span x-text="seed"></span>
                            <button type="button" @click="brief.seeds.splice(idx, 1)" class="hover:text-emerald-900 dark:hover:text-emerald-100" title="Togli">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </template>
                </div>
                <div class="mt-3 flex gap-2 max-w-xl" x-show="brief.seeds.length < 8">
                    <input type="text" x-model="seedInput" @keydown.enter.prevent="addSeed()"
                           placeholder="Aggiungi una ricerca e premi Invio"
                           class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <button type="button" @click="addSeed()" class="px-3 py-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors" title="Aggiungi">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <p x-show="validationAttempted && brief.seeds.length === 0" class="mt-1 text-xs text-red-500">Serve almeno una ricerca</p>
            </div>

            <div class="flex flex-wrap items-end gap-6">
                <!-- Paese -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Paese</label>
                    <select x-model="brief.geography"
                            class="block w-48 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="IT">Italia</option>
                        <option value="US">Stati Uniti</option>
                        <option value="GB">Regno Unito</option>
                        <option value="DE">Germania</option>
                        <option value="FR">Francia</option>
                        <option value="ES">Spagna</option>
                    </select>
                </div>
                <button type="button" @click="showExclusions = true" x-show="!showExclusions && brief.exclusions.length === 0"
                        class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 py-2">
                    + Parole da escludere
                </button>
            </div>

            <!-- Exclusions -->
            <div x-show="showExclusions || brief.exclusions.length > 0">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Parole da escludere <span class="text-slate-400">(facoltativo)</span>
                </label>
                <div class="flex gap-2 max-w-xl">
                    <input type="text" x-model="exclusionInput" @keydown.enter.prevent="addExclusion()"
                           placeholder="Es: gratis, corso, lavoro..."
                           class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <button type="button" @click="addExclusion()" class="px-3 py-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors" title="Aggiungi">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <div class="mt-2 flex flex-wrap gap-2">
                    <template x-for="(ex, idx) in brief.exclusions" :key="idx">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 text-sm">
                            <span x-text="ex"></span>
                            <button type="button" @click="brief.exclusions.splice(idx, 1)" class="hover:text-red-900 dark:hover:text-red-100" title="Togli">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: keyword trovate (gratis), da scegliere prima dell'AI -->
    <div x-show="currentStep === 1" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Keyword trovate</h2>

        <!-- Progress -->
        <div x-show="!collectionDone" class="space-y-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500 dark:text-slate-400" x-text="collectionStatus"></span>
                <span class="font-medium text-slate-900 dark:text-white" x-text="collectionProgress + '%'"></span>
            </div>
            <div class="w-full h-3 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" :style="`width: ${collectionProgress}%`"></div>
            </div>
        </div>

        <!-- Elenco: si tolgono quelle che non servono, poi si raggruppa con l'AI -->
        <div x-show="collectionDone" class="space-y-3">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Google ha trovato <strong x-text="fmt(rawKeywordsCount)"></strong> ricerche<span x-show="rawKeywordsCount > filteredKeywords.length"> (<span x-text="fmt(rawKeywordsCount - filteredKeywords.length)"></span> tolte perché con volume troppo basso o con parole escluse)</span>.
                Togli la spunta a quelle che non c'entrano: all'AI vanno solo le <strong x-text="fmt(selectedCount())"></strong> spuntate.
            </p>
            <div class="flex items-center gap-3 text-sm">
                <button type="button" @click="setAll(true)" class="text-primary-600 dark:text-primary-400 hover:underline">Spunta tutte</button>
                <span class="text-slate-300 dark:text-slate-600">·</span>
                <button type="button" @click="setAll(false)" class="text-primary-600 dark:text-primary-400 hover:underline">Togli tutte</button>
            </div>
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-700/50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 w-10"></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ricerca</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Volume al mese</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden sm:table-cell">Concorrenza</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <template x-for="(kw, idx) in filteredKeywords" :key="kw.text">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer" @click="selected[idx] = !selected[idx]">
                                <td class="px-4 py-2"><input type="checkbox" :checked="selected[idx]" @click.stop="selected[idx] = !selected[idx]" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500"></td>
                                <td class="px-4 py-2" :class="selected[idx] ? 'text-slate-900 dark:text-white' : 'line-through text-slate-400 dark:text-slate-500'" x-text="kw.text"></td>
                                <td class="px-4 py-2 text-right tabular-nums text-slate-700 dark:text-slate-300" x-text="fmt(kw.volume)"></td>
                                <td class="px-4 py-2 text-slate-500 dark:text-slate-400 hidden sm:table-cell" x-text="competitionLabel(kw.competition_level)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Step 3: AI Clustering -->
    <div x-show="currentStep === 2" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Raggruppamento AI</h2>

        <div x-show="analyzing" class="text-center py-8">
            <svg class="w-12 h-12 text-primary-500 mx-auto animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-slate-600 dark:text-slate-400">L'AI sta raggruppando <span x-text="fmt(selectedCount())" class="font-bold"></span> keyword...</p>
            <p class="mt-1 text-sm text-slate-400">Gruppi per argomento, intento di ricerca e una nota per gruppo. Di solito meno di un minuto.</p>
        </div>

        <div x-show="analyzeError" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
            <p class="text-sm text-red-700 dark:text-red-300" x-text="analyzeError"></p>
        </div>

        <div x-show="analyzeDone" class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 text-center">
            <svg class="w-12 h-12 text-emerald-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">Analisi completata!</p>
            <p class="mt-1 text-sm text-slate-500" x-text="`${analyzeResult.clusters_count} gruppi trovati, ${analyzeResult.credits_used} crediti usati`"></p>
        </div>
    </div>

    <!-- Step Navigation -->
    <div class="flex items-center justify-between">
        <button type="button" @click="prevStep()" x-show="currentStep > 0 && !collecting && !analyzing"
                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
            Indietro
        </button>
        <div x-show="currentStep === 0 || (currentStep > 0 && !collecting && !analyzing)" class="ml-auto"></div>
        <div class="flex items-center gap-2" x-show="canProceed()">
            <span x-show="currentStep === 0" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">gratis</span>
            <span x-show="currentStep === 1" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                <?= \Core\ModuleLoader::getSetting('keyword-research', 'cost_kr_ai_clustering', 3) ?> crediti
            </span>
            <button type="button" @click="nextStep()"
                    class="inline-flex items-center px-5 py-2.5 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <span x-text="nextButtonText()"></span>
                <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    <!-- Ricerche passate -->
    <?php if (!empty($researches)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">Ricerche precedenti</h3>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($researches as $r): ?>
            <div class="px-6 py-3 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50">
                <div>
                    <span class="text-sm text-slate-900 dark:text-white"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
                    <span class="text-xs text-slate-500 ml-2"><?= number_format((int) ($r['filtered_keywords_count'] ?? 0), 0, ',', '.') ?> keyword</span>
                    <?php
                    $statusBadge = match($r['status']) {
                        'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                        'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                        default => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                    };
                    ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ml-2 <?= $statusBadge ?>">
                        <?= ['completed' => 'Completata', 'error' => 'Errore', 'collecting' => 'In corso', 'clustering' => 'In corso', 'pending' => 'In attesa', 'processing' => 'In corso', 'failed' => 'Errore', 'draft' => 'Non finita', 'analyzing' => 'In corso'][$r['status']] ?? ucfirst($r['status']) ?>
                    </span>
                </div>
                <?php if ($r['status'] === 'completed'): ?>
                <a href="<?= url('/keyword-research/project/' . $project['id'] . '/research/' . $r['id']) ?>" class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Vedi risultati
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function researchWizard() {
    return {
        ...krProfile(<?= json_encode([
            'profile' => $profileState['profile'],
            'suggestions' => $profileState['suggestions'],
            'geoFacts' => $profileState['geo_facts'],
            'domain' => $profileState['domain'],
            'url' => url('/keyword-research/project/' . $project['id'] . '/profile'),
            'csrf' => csrf_token(),
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) ?>),

        steps: ['La tua attività', 'Keyword trovate', 'Raggruppamento AI', 'Risultati'],
        currentStep: 0,
        projectId: <?= $project['id'] ?>,
        csrfToken: '<?= csrf_token() ?>',

        // Brief: il resto (descrizione, target) lo prende il server dal profilo del progetto
        brief: {
            geography: '<?= e($project['default_location'] ?? 'IT') ?>',
            seeds: [],
            exclusions: [],
        },
        seedInput: '',
        exclusionInput: '',
        showExclusions: false,

        init() {
            this.profileInit();
        },

        // Proposte dell'AI dal profilo: parole di partenza (sostituite quando il profilo cambia)
        applySuggestions(s, profileChanged = false) {
            if (!s) return;
            if (profileChanged || this.brief.seeds.length === 0) {
                this.brief.seeds = [...(s.seeds || [])];
            }
        },

        validationAttempted: false,

        // Collection
        researchId: null,
        collecting: false,
        collectionDone: false,
        collectionProgress: 0,
        collectionStatus: 'In attesa...',
        seedLogs: [],
        rawKeywordsCount: 0,
        filteredKeywords: [],
        selected: [],
        eventSource: null,

        // AI Analysis
        analyzing: false,
        analyzeDone: false,
        analyzeError: '',
        analyzeResult: {},

        fmt(n) {
            return new Intl.NumberFormat('it-IT', { useGrouping: 'always' }).format(Number(n) || 0);
        },

        competitionLabel(level) {
            return { HIGH: 'Alta', MEDIUM: 'Media', LOW: 'Bassa' }[String(level || '').toUpperCase()] || '—';
        },

        selectedCount() {
            return this.selected.filter(Boolean).length;
        },

        setAll(value) {
            this.selected = this.filteredKeywords.map(() => value);
        },

        addSeed() {
            const val = this.seedInput.trim().toLowerCase();
            if (val && !this.brief.seeds.includes(val) && this.brief.seeds.length < 8) {
                this.brief.seeds.push(val);
            }
            this.seedInput = '';
        },

        addExclusion() {
            const val = this.exclusionInput.trim();
            if (val && !this.brief.exclusions.includes(val)) {
                this.brief.exclusions.push(val);
            }
            this.exclusionInput = '';
        },

        canProceed() {
            if (this.collecting || this.analyzing) return false;
            if (this.currentStep === 0) return this.profile !== '' && !this.profileBusy && !this.editingProfile && this.brief.seeds.length > 0;
            if (this.currentStep === 1) return this.collectionDone && this.selectedCount() > 0;
            if (this.currentStep === 2) return this.analyzeDone;
            return false;
        },

        nextButtonText() {
            if (this.currentStep === 0) return 'Cerca su Google';
            if (this.currentStep === 1) return 'Raggruppa con AI';
            if (this.currentStep === 2) return 'Vedi i risultati';
            return 'Avanti';
        },

        nextStep() {
            if (this.currentStep === 0) {
                this.validationAttempted = true;
                if (!this.canProceed()) return;
                this.currentStep = 1;
                this.startCollection();
            } else if (this.currentStep === 1) {
                this.currentStep = 2;
                this.startAnalysis();
            } else if (this.currentStep === 2 && this.analyzeDone) {
                window.location.href = this.analyzeResult.redirect;
            }
        },

        prevStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },

        async startCollection() {
            this.collecting = true;
            this.collectionDone = false;
            this.collectionProgress = 0;
            this.seedLogs = [];
            this.collectionStatus = 'Avvio raccolta...';

            const formData = new FormData();
            formData.append('_csrf_token', this.csrfToken);
            formData.append('geography', this.brief.geography);
            formData.append('seeds', this.brief.seeds.join(','));
            formData.append('exclusions', this.brief.exclusions.join(','));

            try {
                const resp = await fetch(`<?= url('/keyword-research/project/' . $project['id'] . '/research/start') ?>`, {
                    method: 'POST',
                    body: formData,
                });
                if (!resp.ok) { throw new Error('Errore server: ' + resp.status); }
                const data = await resp.json();

                if (!data.success) {
                    this.collectionStatus = 'Errore: ' + data.error;
                    this.collecting = false;
                    return;
                }

                this.researchId = data.research_id;
                this.connectSSE();
            } catch (e) {
                this.collectionStatus = 'Errore di connessione, riprova.';
                this.collecting = false;
            }
        },

        connectSSE() {
            const url = `<?= url('/keyword-research/project/' . $project['id'] . '/research/stream') ?>?research_id=${this.researchId}`;
            this.eventSource = new EventSource(url);

            this.eventSource.addEventListener('started', (e) => {
                const d = JSON.parse(e.data);
                this.collectionStatus = `Cerco su Google ${d.total_seeds} ricerche e quelle simili...`;
            });

            this.eventSource.addEventListener('seed_started', (e) => {
                const d = JSON.parse(e.data);
                this.seedLogs.push({ message: `Ricerca: "${d.seed}"...`, status: 'loading', count: 0 });
                this.collectionProgress = Math.round(((d.index) / d.total) * 80);
                this.collectionStatus = d.total > 1 ? `Ricerca ${d.index + 1} di ${d.total}: "${d.seed}"` : 'Chiedo a Google le ricerche simili e i volumi...';
            });

            this.eventSource.addEventListener('seed_completed', (e) => {
                const d = JSON.parse(e.data);
                const last = this.seedLogs[this.seedLogs.length - 1];
                if (last) {
                    last.status = 'completed';
                    last.message = `"${d.seed}" fatto`;
                    last.count = d.new_keywords;
                }
                this.collectionProgress = d.index === undefined ? 80 : Math.round(((d.index + 1) / this.brief.seeds.length) * 80);
            });

            this.eventSource.addEventListener('seed_error', (e) => {
                const d = JSON.parse(e.data);
                const last = this.seedLogs[this.seedLogs.length - 1];
                if (last) {
                    last.status = 'error';
                    last.message = `"${d.seed}": ${d.error}`;
                }
            });

            this.eventSource.addEventListener('filtering', (e) => {
                this.collectionProgress = 90;
                this.collectionStatus = 'Tolgo le ricerche senza volume...';
            });

            this.eventSource.addEventListener('completed', (e) => {
                const d = JSON.parse(e.data);
                this.rawKeywordsCount = d.raw_keywords;
                this.filteredKeywords = d.keywords || [];
                this.setAll(true);
                this.collectionProgress = 100;
                this.collectionStatus = 'Raccolta completata!';
                this.collectionDone = true;
                this.collecting = false;
                this.eventSource.close();
            });

            this.eventSource.addEventListener('error', (e) => {
                try {
                    const d = JSON.parse(e.data);
                    this.collectionStatus = 'Errore: ' + d.message;
                    this.collecting = false;
                    this.collectionFailed = true; // evita che onerror sovrascriva il messaggio con 'Connessione persa'
                    this.eventSource.close();
                } catch (_) {
                    // Errore nativo SSE (no data), gestito da onerror
                }
            });

            this.eventSource.onerror = () => {
                this.eventSource.close();
                if (!this.collectionDone && !this.collectionFailed) {
                    // Se progresso >= 85%, i dati sono probabilmente salvati nel DB - polling fallback
                    if (this.collectionProgress >= 85) {
                        this.collectionStatus = 'Recupero risultati...';
                        this.pollCollectionResults();
                    } else {
                        this.collectionStatus = 'Connessione persa. Riprova.';
                        this.collecting = false;
                    }
                }
            };
        },

        async pollCollectionResults(attempts = 0) {
            if (attempts > 10) {
                this.collectionStatus = 'Impossibile recuperare i risultati. Riprova.';
                this.collecting = false;
                return;
            }
            try {
                const resp = await fetch(`<?= url('/keyword-research/project/' . $project['id'] . '/research/collection-results') ?>?research_id=${this.researchId}`);
                if (!resp.ok) { throw new Error('Errore server: ' + resp.status); }
                const data = await resp.json();
                if (data.success && data.status === 'collecting') {
                    // Ancora in corso, riprova tra 2 secondi
                    setTimeout(() => this.pollCollectionResults(attempts + 1), 2000);
                    return;
                }
                if (data.success && data.keywords && data.keywords.length > 0) {
                    this.rawKeywordsCount = data.raw_keywords;
                    this.filteredKeywords = data.keywords;
                    this.setAll(true);
                    this.collectionProgress = 100;
                    this.collectionStatus = 'Raccolta completata!';
                    this.collectionDone = true;
                    this.collecting = false;
                } else {
                    this.collectionStatus = 'Nessun risultato trovato. Riprova.';
                    this.collecting = false;
                }
            } catch (e) {
                setTimeout(() => this.pollCollectionResults(attempts + 1), 2000);
            }
        },

        async startAnalysis() {
            this.analyzing = true;
            this.analyzeDone = false;
            this.analyzeError = '';

            const formData = new FormData();
            formData.append('_csrf_token', this.csrfToken);
            formData.append('research_id', this.researchId);
            formData.append('keywords', JSON.stringify(this.filteredKeywords.filter((_, i) => this.selected[i])));

            try {
                const resp = await fetch(`<?= url('/keyword-research/project/' . $project['id'] . '/research/analyze') ?>`, {
                    method: 'POST',
                    body: formData,
                });
                if (!resp.ok) { throw new Error('Errore server: ' + resp.status); }
                const data = await resp.json();

                if (!data.success) {
                    this.analyzeError = data.error;
                    this.analyzing = false;
                    return;
                }

                this.analyzeResult = data;
                this.analyzeDone = true;
                this.analyzing = false;
                this.currentStep = 3;

                // Redirect automatico ai risultati
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } catch (e) {
                this.analyzeError = 'Errore di connessione.';
                this.analyzing = false;
            }
        },
    };
}
</script>
