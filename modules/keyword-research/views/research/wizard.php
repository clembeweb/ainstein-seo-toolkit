<div x-data="researchWizard()" x-cloak class="space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/keyword-research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Keyword Research</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/keyword-research/projects?type=' . ($project['type'] ?? 'research')) ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Progetti</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white"><?= e($project['name']) ?></span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Research Guidata</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Keyword research con clustering AI automatico</p>
    </div>

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

    <!-- Step 1: Brief -->
    <div x-show="currentStep === 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Descrivi il tuo business</h2>

        <!-- Business Description -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                Descrizione business <span class="text-red-500">*</span>
            </label>
            <textarea x-model="brief.business" rows="3" required
                      placeholder="Es: Agenzia SEO a Milano che offre consulenza e servizi di ottimizzazione per PMI"
                      class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
        </div>

        <!-- Target -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Target</label>
            <div class="flex gap-3">
                <template x-for="opt in ['B2B', 'B2C', 'Both']" :key="opt">
                    <button type="button" @click="brief.target = opt"
                            class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                            :class="brief.target === opt ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:border-slate-400'"
                            x-text="opt"></button>
                </template>
            </div>
        </div>

        <!-- Objective -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Obiettivo</label>
            <div class="flex gap-3">
                <template x-for="opt in ['SEO', 'Google Ads', 'Content']" :key="opt">
                    <button type="button" @click="brief.objective = opt"
                            class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                            :class="brief.objective === opt ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:border-slate-400'"
                            x-text="opt"></button>
                </template>
            </div>
        </div>

        <!-- Geography & Language (from project) -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Location</label>
                <select x-model="brief.geography"
                        class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="IT">Italia</option>
                    <option value="US">Stati Uniti</option>
                    <option value="GB">Regno Unito</option>
                    <option value="DE">Germania</option>
                    <option value="FR">Francia</option>
                    <option value="ES">Spagna</option>
                </select>
            </div>
            <div class="flex items-end">
                <div class="text-sm text-slate-500 dark:text-slate-400 py-2.5">
                    Lingua: <span class="font-medium text-slate-700 dark:text-slate-300"><?= e($project['default_language'] ?? 'it') ?></span>
                    (configurabile nelle impostazioni progetto)
                </div>
            </div>
        </div>

        <!-- Seed Keywords -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                Seed Keywords <span class="text-red-500">*</span>
            </label>
            <div class="space-y-2">
                <div class="flex gap-2">
                    <input type="text" x-model="seedInput" @keydown.enter.prevent="addSeed()"
                           placeholder="Digita una keyword e premi Invio"
                           class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <button type="button" @click="addSeed()" class="px-3 py-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(seed, idx) in brief.seeds" :key="idx">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 text-sm">
                            <span x-text="seed"></span>
                            <button type="button" @click="brief.seeds.splice(idx, 1)" class="hover:text-emerald-900 dark:hover:text-emerald-100">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </template>
                </div>
            </div>
            <p class="mt-1 text-xs text-slate-400">Min 1, max 5 seed keyword. L'API espanderà ogni seed in decine di keyword correlate.</p>
        </div>

        <!-- Exclusions -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                Parole da escludere <span class="text-slate-400">(opzionale)</span>
            </label>
            <div class="space-y-2">
                <div class="flex gap-2">
                    <input type="text" x-model="exclusionInput" @keydown.enter.prevent="addExclusion()"
                           placeholder="Es: gratis, free, corso..."
                           class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <button type="button" @click="addExclusion()" class="px-3 py-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(ex, idx) in brief.exclusions" :key="idx">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 text-sm">
                            <span x-text="ex"></span>
                            <button type="button" @click="brief.exclusions.splice(idx, 1)" class="hover:text-red-900 dark:hover:text-red-100">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Raccolta Keyword (SSE) -->
    <div x-show="currentStep === 1" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Raccolta Keyword</h2>

        <!-- Progress -->
        <div class="space-y-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500 dark:text-slate-400" x-text="collectionStatus"></span>
                <span class="font-medium text-slate-900 dark:text-white" x-text="collectionProgress + '%'"></span>
            </div>
            <div class="w-full h-3 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" :style="`width: ${collectionProgress}%`"></div>
            </div>
        </div>

        <!-- Seed Log -->
        <div class="space-y-2 max-h-60 overflow-y-auto">
            <template x-for="(log, idx) in seedLogs" :key="idx">
                <div class="flex items-center gap-3 text-sm py-1.5 px-3 rounded-lg"
                     :class="log.status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-900/20' : (log.status === 'error' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-blue-50 dark:bg-blue-900/20')">
                    <template x-if="log.status === 'completed'">
                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="log.status === 'error'">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </template>
                    <template x-if="log.status === 'loading'">
                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </template>
                    <span class="flex-1" x-text="log.message"></span>
                    <span x-show="log.count" class="text-xs text-slate-500" x-text="'+' + log.count + ' kw'"></span>
                </div>
            </template>
        </div>

        <!-- Risultato raccolta -->
        <div x-show="collectionDone" class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-xl font-bold text-slate-900 dark:text-white" x-text="rawKeywordsCount"></p>
                    <p class="text-xs text-slate-500">Raccolte</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400" x-text="filteredKeywords.length"></p>
                    <p class="text-xs text-slate-500">Dopo filtro</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-500" x-text="rawKeywordsCount - filteredKeywords.length"></p>
                    <p class="text-xs text-slate-500">Escluse</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: AI Clustering -->
    <div x-show="currentStep === 2" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">AI Clustering</h2>

        <div x-show="analyzing" class="text-center py-8">
            <svg class="w-12 h-12 text-primary-500 mx-auto animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-slate-600 dark:text-slate-400">L'AI sta analizzando <span x-text="filteredKeywords.length" class="font-bold"></span> keyword...</p>
            <p class="mt-1 text-sm text-slate-400">Clustering semantico, classificazione intent, note strategiche</p>
        </div>

        <div x-show="analyzeError" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
            <p class="text-sm text-red-700 dark:text-red-300" x-text="analyzeError"></p>
        </div>

        <div x-show="analyzeDone" class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 text-center">
            <svg class="w-12 h-12 text-emerald-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">Analisi completata!</p>
            <p class="mt-1 text-sm text-slate-500" x-text="`${analyzeResult.clusters_count} cluster identificati, ${analyzeResult.credits_used} crediti utilizzati`"></p>
        </div>
    </div>

    <!-- Step Navigation -->
    <div class="flex items-center justify-between">
        <button type="button" @click="prevStep()" x-show="currentStep > 0 && !collecting && !analyzing"
                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
            Indietro
        </button>
        <div x-show="currentStep === 0 || (currentStep > 0 && !collecting && !analyzing)" class="ml-auto"></div>
        <button type="button" @click="nextStep()"
                x-show="canProceed()"
                class="inline-flex items-center px-5 py-2.5 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
            <span x-text="nextButtonText()"></span>
            <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
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
                    <span class="text-xs text-slate-500 ml-2"><?= $r['filtered_keywords_count'] ?? 0 ?> kw filtrate</span>
                    <?php
                    $statusBadge = match($r['status']) {
                        'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                        'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                        default => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                    };
                    ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ml-2 <?= $statusBadge ?>">
                        <?= ucfirst($r['status']) ?>
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
        steps: ['Brief', 'Raccolta', 'AI Clustering', 'Risultati'],
        currentStep: 0,
        projectId: <?= $project['id'] ?>,
        csrfToken: '<?= csrf_token() ?>',

        // Brief
        brief: {
            business: '',
            target: 'B2B',
            geography: '<?= e($project['default_location'] ?? 'IT') ?>',
            objective: 'SEO',
            seeds: [],
            exclusions: [],
        },
        seedInput: '',
        exclusionInput: '',

        // Collection
        researchId: null,
        collecting: false,
        collectionDone: false,
        collectionProgress: 0,
        collectionStatus: 'In attesa...',
        seedLogs: [],
        rawKeywordsCount: 0,
        filteredKeywords: [],
        eventSource: null,

        // AI Analysis
        analyzing: false,
        analyzeDone: false,
        analyzeError: '',
        analyzeResult: {},

        addSeed() {
            const val = this.seedInput.trim();
            if (val && !this.brief.seeds.includes(val) && this.brief.seeds.length < 5) {
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
            if (this.currentStep === 0) return this.brief.business.trim() !== '' && this.brief.seeds.length > 0;
            if (this.currentStep === 1) return this.collectionDone && this.filteredKeywords.length > 0;
            if (this.currentStep === 2) return this.analyzeDone;
            return false;
        },

        nextButtonText() {
            if (this.currentStep === 0) return 'Avvia Raccolta';
            if (this.currentStep === 1) return 'Analizza con AI';
            if (this.currentStep === 2) return 'Vedi Risultati';
            return 'Avanti';
        },

        nextStep() {
            if (this.currentStep === 0) {
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
            formData.append('business', this.brief.business);
            formData.append('target', this.brief.target);
            formData.append('geography', this.brief.geography);
            formData.append('objective', this.brief.objective);
            formData.append('seeds', this.brief.seeds.join(','));
            formData.append('exclusions', this.brief.exclusions.join(','));

            try {
                const resp = await fetch(`<?= url('/keyword-research/project/' . $project['id'] . '/research/start') ?>`, {
                    method: 'POST',
                    body: formData,
                });
                const data = await resp.json();

                if (!data.success) {
                    this.collectionStatus = 'Errore: ' + data.error;
                    this.collecting = false;
                    return;
                }

                this.researchId = data.research_id;
                this.connectSSE();
            } catch (e) {
                this.collectionStatus = 'Errore di connessione.';
                this.collecting = false;
            }
        },

        connectSSE() {
            const url = `<?= url('/keyword-research/project/' . $project['id'] . '/research/stream') ?>?research_id=${this.researchId}`;
            this.eventSource = new EventSource(url);

            this.eventSource.addEventListener('started', (e) => {
                const d = JSON.parse(e.data);
                this.collectionStatus = `Raccolta da ${d.total_seeds} seed keyword...`;
            });

            this.eventSource.addEventListener('seed_started', (e) => {
                const d = JSON.parse(e.data);
                this.seedLogs.push({ message: `Ricerca: "${d.seed}"...`, status: 'loading', count: 0 });
                this.collectionProgress = Math.round(((d.index) / d.total) * 80);
                this.collectionStatus = `Seed ${d.index + 1}/${d.total}: "${d.seed}"`;
            });

            this.eventSource.addEventListener('seed_completed', (e) => {
                const d = JSON.parse(e.data);
                const last = this.seedLogs[this.seedLogs.length - 1];
                if (last) {
                    last.status = 'completed';
                    last.message = `"${d.seed}" completato`;
                    last.count = d.new_keywords;
                }
                this.collectionProgress = Math.round(((d.index + 1) / this.brief.seeds.length) * 80);
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
                this.collectionStatus = 'Filtraggio keyword...';
            });

            this.eventSource.addEventListener('completed', (e) => {
                const d = JSON.parse(e.data);
                this.rawKeywordsCount = d.raw_keywords;
                this.filteredKeywords = d.keywords || [];
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
                    this.eventSource.close();
                } catch (_) {
                    // Errore nativo SSE (no data), gestito da onerror
                }
            });

            this.eventSource.onerror = () => {
                this.eventSource.close();
                if (!this.collectionDone) {
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
                const data = await resp.json();
                if (data.success && data.status === 'collecting') {
                    // Ancora in corso, riprova tra 2 secondi
                    setTimeout(() => this.pollCollectionResults(attempts + 1), 2000);
                    return;
                }
                if (data.success && data.keywords && data.keywords.length > 0) {
                    this.rawKeywordsCount = data.raw_keywords;
                    this.filteredKeywords = data.keywords;
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
            formData.append('keywords', JSON.stringify(this.filteredKeywords));

            try {
                const resp = await fetch(`<?= url('/keyword-research/project/' . $project['id'] . '/research/analyze') ?>`, {
                    method: 'POST',
                    body: formData,
                });
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
