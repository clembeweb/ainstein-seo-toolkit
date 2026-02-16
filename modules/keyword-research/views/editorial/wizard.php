<div x-data="editorialWizard()" x-cloak class="space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/keyword-research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Keyword Research</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/keyword-research/projects?type=' . ($project['type'] ?? 'editorial')) ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Progetti</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white"><?= e($project['name']) ?></span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Piano Editoriale</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Crea un piano editoriale basato su keyword research e analisi competitor</p>
    </div>

    <!-- Progress Steps -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <template x-for="(step, idx) in steps" :key="idx">
                <div class="flex items-center" :class="idx < steps.length - 1 ? 'flex-1' : ''">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                             :class="currentStep > idx ? 'bg-violet-500 text-white' : (currentStep === idx ? 'bg-primary-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400')">
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
                        <div class="flex-1 mx-4 h-0.5 rounded" :class="currentStep > idx ? 'bg-violet-500' : 'bg-slate-200 dark:bg-slate-700'"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Step 1: Brief -->
    <div x-show="currentStep === 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Configura il piano editoriale</h2>

        <!-- Theme -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                Tema del blog <span class="text-red-500">*</span>
            </label>
            <textarea x-model="brief.theme" rows="3" required
                      placeholder="Es: Blog su SEO, digital marketing e advertising per professionisti del web"
                      class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
        </div>

        <!-- Categories -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                Categorie <span class="text-red-500">*</span>
            </label>
            <div class="space-y-2">
                <div class="flex gap-2">
                    <input type="text" x-model="categoryInput" @keydown.enter.prevent="addCategory()"
                           placeholder="Es: SEO tecnica, Google Ads, Content Marketing..."
                           class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <button type="button" @click="addCategory()" class="px-3 py-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(cat, idx) in brief.categories" :key="idx">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 text-sm">
                            <span x-text="cat"></span>
                            <button type="button" @click="brief.categories.splice(idx, 1)" class="hover:text-violet-900 dark:hover:text-violet-100">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </template>
                </div>
            </div>
            <p class="mt-1 text-xs text-slate-400">Min 2, max 6 categorie. Ogni categoria genera keyword e analisi SERP.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Period -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Periodo</label>
                <div class="flex gap-3">
                    <template x-for="opt in [3, 6, 12]" :key="opt">
                        <button type="button" @click="brief.months = opt"
                                class="flex-1 px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                                :class="brief.months === opt ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300' : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:border-slate-400'"
                                x-text="opt + ' mesi'"></button>
                    </template>
                </div>
            </div>

            <!-- Articles per month -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Articoli al mese</label>
                <div class="flex gap-3">
                    <template x-for="opt in [2, 4, 6, 8]" :key="opt">
                        <button type="button" @click="brief.articles_per_month = opt"
                                class="flex-1 px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                                :class="brief.articles_per_month === opt ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300' : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:border-slate-400'"
                                x-text="opt"></button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Target -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Target</label>
            <div class="flex gap-3">
                <template x-for="opt in ['B2B', 'B2C', 'Entrambi']" :key="opt">
                    <button type="button" @click="brief.target = opt"
                            class="px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                            :class="brief.target === opt ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:border-slate-400'"
                            x-text="opt"></button>
                </template>
            </div>
        </div>

        <!-- Geography -->
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
                    Totale articoli: <span class="font-bold text-violet-600 dark:text-violet-400" x-text="brief.months * brief.articles_per_month"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Raccolta Dati (SSE) -->
    <div x-show="currentStep === 1" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Raccolta Dati</h2>

        <!-- Progress -->
        <div class="space-y-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500 dark:text-slate-400" x-text="collectionStatus"></span>
                <span class="font-medium text-slate-900 dark:text-white" x-text="collectionProgress + '%'"></span>
            </div>
            <div class="w-full h-3 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-violet-500 rounded-full transition-all duration-500" :style="`width: ${collectionProgress}%`"></div>
            </div>
        </div>

        <!-- Category Log -->
        <div class="space-y-2 max-h-80 overflow-y-auto">
            <template x-for="(log, idx) in categoryLogs" :key="idx">
                <div class="flex items-center gap-3 text-sm py-2 px-3 rounded-lg"
                     :class="log.status === 'completed' ? 'bg-violet-50 dark:bg-violet-900/20' : (log.status === 'error' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-blue-50 dark:bg-blue-900/20')">
                    <template x-if="log.status === 'completed'">
                        <svg class="w-4 h-4 text-violet-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="log.status === 'error'">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </template>
                    <template x-if="log.status === 'loading'">
                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </template>
                    <span class="flex-1" x-text="log.message"></span>
                    <span x-show="log.detail" class="text-xs text-slate-500" x-text="log.detail"></span>
                </div>
            </template>
        </div>

        <!-- Risultato raccolta -->
        <div x-show="collectionDone" class="bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded-xl p-4">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-xl font-bold text-slate-900 dark:text-white" x-text="totalKeywords"></p>
                    <p class="text-xs text-slate-500">Keyword trovate</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-violet-600 dark:text-violet-400" x-text="brief.categories.length"></p>
                    <p class="text-xs text-slate-500">Categorie</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900 dark:text-white" x-text="totalSerpTitles"></p>
                    <p class="text-xs text-slate-500">Titoli competitor</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: AI Piano -->
    <div x-show="currentStep === 2" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">AI Piano Editoriale</h2>

        <div x-show="analyzing" class="text-center py-8">
            <svg class="w-12 h-12 text-violet-500 mx-auto animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-slate-600 dark:text-slate-400">L'AI sta creando il piano editoriale...</p>
            <p class="mt-1 text-sm text-slate-400">
                <span x-text="brief.months * brief.articles_per_month"></span> articoli su <span x-text="brief.months"></span> mesi
            </p>
        </div>

        <div x-show="analyzeError" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
            <p class="text-sm text-red-700 dark:text-red-300" x-text="analyzeError"></p>
        </div>

        <div x-show="analyzeDone" class="bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded-xl p-4 text-center">
            <svg class="w-12 h-12 text-violet-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">Piano editoriale creato!</p>
            <p class="mt-1 text-sm text-slate-500" x-text="`${analyzeResult.articles_count} articoli pianificati, ${analyzeResult.credits_used} crediti utilizzati`"></p>
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

    <!-- Piani precedenti -->
    <?php if (!empty($researches)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">Piani precedenti</h3>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($researches as $r): ?>
            <div class="px-6 py-3 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50">
                <div>
                    <span class="text-sm text-slate-900 dark:text-white"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
                    <?php
                    $rBrief = json_decode($r['brief'] ?? '{}', true);
                    $rCats = implode(', ', $rBrief['categories'] ?? []);
                    ?>
                    <span class="text-xs text-slate-500 ml-2"><?= e($rCats) ?></span>
                    <?php
                    $statusBadge = match($r['status']) {
                        'completed' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300',
                        'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                        default => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                    };
                    ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ml-2 <?= $statusBadge ?>">
                        <?= ucfirst($r['status']) ?>
                    </span>
                </div>
                <?php if ($r['status'] === 'completed'): ?>
                <a href="<?= url('/keyword-research/project/' . $project['id'] . '/editorial/' . $r['id']) ?>" class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Vedi piano
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function editorialWizard() {
    return {
        steps: ['Brief', 'Raccolta Dati', 'AI Piano', 'Risultati'],
        currentStep: 0,
        projectId: <?= $project['id'] ?>,
        csrfToken: '<?= csrf_token() ?>',

        // Brief
        brief: {
            theme: '',
            categories: [],
            months: 6,
            articles_per_month: 4,
            target: 'B2C',
            geography: '<?= e($project['default_location'] ?? 'IT') ?>',
        },
        categoryInput: '',

        // Collection
        researchId: null,
        collecting: false,
        collectionDone: false,
        collectionProgress: 0,
        collectionStatus: 'In attesa...',
        categoryLogs: [],
        totalKeywords: 0,
        totalSerpTitles: 0,
        categoriesData: {},
        eventSource: null,

        // AI Analysis
        analyzing: false,
        analyzeDone: false,
        analyzeError: '',
        analyzeResult: {},

        addCategory() {
            const val = this.categoryInput.trim();
            if (val && !this.brief.categories.includes(val) && this.brief.categories.length < 6) {
                this.brief.categories.push(val);
            }
            this.categoryInput = '';
        },

        canProceed() {
            if (this.collecting || this.analyzing) return false;
            if (this.currentStep === 0) return this.brief.theme.trim() !== '' && this.brief.categories.length >= 2;
            if (this.currentStep === 1) return this.collectionDone && this.totalKeywords > 0;
            if (this.currentStep === 2) return this.analyzeDone;
            return false;
        },

        nextButtonText() {
            if (this.currentStep === 0) return 'Avvia Raccolta';
            if (this.currentStep === 1) return 'Genera Piano AI';
            if (this.currentStep === 2) return 'Vedi Piano';
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
            this.categoryLogs = [];
            this.collectionStatus = 'Avvio raccolta dati...';

            const formData = new FormData();
            formData.append('_csrf_token', this.csrfToken);
            formData.append('theme', this.brief.theme);
            formData.append('categories', this.brief.categories.join(','));
            formData.append('months', this.brief.months);
            formData.append('articles_per_month', this.brief.articles_per_month);
            formData.append('target', this.brief.target);
            formData.append('geography', this.brief.geography);

            try {
                const resp = await fetch(`<?= url('/keyword-research/project/' . $project['id'] . '/editorial/start') ?>`, {
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
            const url = `<?= url('/keyword-research/project/' . $project['id'] . '/editorial/stream') ?>?research_id=${this.researchId}`;
            this.eventSource = new EventSource(url);

            this.eventSource.addEventListener('started', (e) => {
                const d = JSON.parse(e.data);
                this.collectionStatus = `Raccolta da ${d.total_categories} categorie...`;
            });

            this.eventSource.addEventListener('category_started', (e) => {
                const d = JSON.parse(e.data);
                this.categoryLogs.push({ message: `Analisi: "${d.category}"...`, status: 'loading', detail: '' });
                this.collectionProgress = Math.round((d.index / d.total) * 80);
                this.collectionStatus = `Categoria ${d.index + 1}/${d.total}: "${d.category}"`;
            });

            this.eventSource.addEventListener('category_keywords', (e) => {
                const d = JSON.parse(e.data);
                const last = this.categoryLogs[this.categoryLogs.length - 1];
                if (last) last.detail = `${d.new_keywords} kw`;
                this.totalKeywords = d.total_keywords;
            });

            this.eventSource.addEventListener('category_serp', (e) => {
                const d = JSON.parse(e.data);
                const last = this.categoryLogs[this.categoryLogs.length - 1];
                if (last) last.detail += ` + ${d.titles_count} SERP`;
                this.totalSerpTitles += d.titles_count;
            });

            this.eventSource.addEventListener('category_completed', (e) => {
                const d = JSON.parse(e.data);
                const last = this.categoryLogs[this.categoryLogs.length - 1];
                if (last) {
                    last.status = 'completed';
                    last.message = `"${d.category}" completata`;
                }
            });

            this.eventSource.addEventListener('completed', (e) => {
                const d = JSON.parse(e.data);
                this.totalKeywords = d.total_keywords;
                this.categoriesData = d.categories_data || {};
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
                } catch (_) {}
            });

            this.eventSource.onerror = () => {
                this.eventSource.close();
                if (!this.collectionDone) {
                    if (this.collectionProgress >= 70) {
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
            if (attempts > 15) {
                this.collectionStatus = 'Impossibile recuperare i risultati. Riprova.';
                this.collecting = false;
                return;
            }
            try {
                const resp = await fetch(`<?= url('/keyword-research/project/' . $project['id'] . '/editorial/collection-results') ?>?research_id=${this.researchId}`);
                const data = await resp.json();
                if (data.success && data.status === 'collecting') {
                    setTimeout(() => this.pollCollectionResults(attempts + 1), 2000);
                    return;
                }
                if (data.success && data.total_keywords > 0) {
                    this.totalKeywords = data.total_keywords;
                    this.categoriesData = data.categories_data || {};
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

            try {
                const resp = await fetch(`<?= url('/keyword-research/project/' . $project['id'] . '/editorial/analyze') ?>`, {
                    method: 'POST',
                    body: formData,
                });

                if (!resp.ok) {
                    this.analyzeError = 'Operazione avviata. Potrebbe richiedere qualche minuto. Ricarica tra poco.';
                    this.analyzing = false;
                    setTimeout(() => location.reload(), 15000);
                    return;
                }

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
