<?php
$currentPage = 'audit';
include __DIR__ . '/../partials/project-nav.php';
?>

<div class="space-y-6" x-data="auditManager()">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Audit SEO</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Analizza tutte le pagine del progetto</p>
        </div>
    </div>

    <?php if ($totalCount === 0): ?>
    <!-- No Pages -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessuna pagina da analizzare</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Importa prima alcune pagine per poter avviare l'audit.</p>
        <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/pages/import') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
            Importa Pagine
        </a>
    </div>
    <?php else: ?>

    <!-- Audit Panel (not running) -->
    <div x-show="!isRunning && !isCompleted" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= $totalCount ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Pagine totali</p>
            </div>
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400"><?= $costPerPage ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Crediti per pagina</p>
            </div>
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= $estimatedCost ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Costo stimato totale</p>
            </div>
        </div>

        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg mb-6">
            <div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">I tuoi crediti</p>
                <p class="text-2xl font-bold <?= $userCredits >= $estimatedCost ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>">
                    <?= number_format($userCredits, 1) ?>
                </p>
            </div>
            <?php if ($userCredits < $estimatedCost): ?>
            <span class="text-sm text-red-600 dark:text-red-400">Crediti insufficienti</span>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <label class="flex items-center">
                    <input type="radio" name="scope" value="all" checked x-model="scope"
                           class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300">
                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Tutte le pagine (<?= $totalCount ?>)</span>
                </label>
                <?php if ($pendingCount > 0 && $pendingCount < $totalCount): ?>
                <label class="flex items-center">
                    <input type="radio" name="scope" value="pending" x-model="scope"
                           class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300">
                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Solo non analizzate (<?= $pendingCount ?>)</span>
                </label>
                <?php endif; ?>
            </div>
            <button @click="startAudit()" :disabled="starting || <?= $userCredits < $estimatedCost ? 'true' : 'false' ?>"
                    class="inline-flex items-center px-6 py-3 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 disabled:opacity-50 transition-colors">
                <svg x-show="starting" class="animate-spin -ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <svg x-show="!starting" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Avvia Audit
            </button>
        </div>

        <p x-show="error" x-text="error" class="mt-4 text-sm text-red-600 dark:text-red-400"></p>
    </div>

    <!-- Progress Panel (running) -->
    <div x-show="isRunning" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Progresso</span>
                <span class="text-sm text-slate-500" x-text="completed + ' / ' + total + ' pagine'"></span>
            </div>
            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3">
                <div class="bg-emerald-600 h-3 rounded-full transition-all duration-300" :style="'width: ' + percent + '%'"></div>
            </div>
        </div>

        <div class="mb-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
            <p class="text-sm text-slate-500 dark:text-slate-400">Analizzando:</p>
            <p class="text-sm font-medium text-slate-900 dark:text-white truncate" x-text="currentUrl || '...'"></p>
        </div>

        <!-- Recent Results -->
        <div class="space-y-2 max-h-64 overflow-y-auto mb-4">
            <template x-for="result in recentResults" :key="result.url">
                <div class="flex items-center justify-between p-2 rounded bg-slate-50 dark:bg-slate-700/50">
                    <span class="text-sm text-slate-700 dark:text-slate-300 truncate flex-1" x-text="result.url"></span>
                    <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium"
                          :class="result.score >= 80 ? 'bg-emerald-100 text-emerald-700' : (result.score >= 60 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                          x-text="result.score"></span>
                </div>
            </template>
        </div>

        <button @click="cancelAudit()" :disabled="cancelling"
                class="inline-flex items-center px-4 py-2 rounded-lg border border-red-300 text-red-600 font-medium hover:bg-red-50 disabled:opacity-50 transition-colors">
            Annulla Audit
        </button>
    </div>

    <!-- Completed Panel -->
    <div x-show="isCompleted" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="text-center mb-6">
            <div class="mx-auto h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Audit Completato!</h3>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-2xl font-bold text-slate-900 dark:text-white" x-text="completed"></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Completate</p>
            </div>
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400" x-text="failed"></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Errori</p>
            </div>
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400" x-text="avgScore || '-'"></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Score medio</p>
            </div>
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" x-text="totalIssues"></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Issues trovati</p>
            </div>
        </div>

        <div class="flex items-center justify-center gap-4">
            <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/issues') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
                Vedi Issues
            </a>
            <button @click="resetAudit()"
                    class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 text-slate-700 font-medium hover:bg-slate-50 transition-colors">
                Nuovo Audit
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function auditManager() {
    const projectId = <?= (int) $project['id'] ?>;
    const csrfToken = '<?= csrf_token() ?>';
    const baseUrl = '<?= rtrim(url(''), '/') ?>';

    return {
        scope: 'all',
        starting: false,
        isRunning: false,
        isCompleted: false,
        cancelling: false,
        error: '',
        jobId: null,
        eventSource: null,

        // Progress
        completed: 0,
        failed: 0,
        total: 0,
        percent: 0,
        currentUrl: '',
        recentResults: [],

        // Final stats
        avgScore: null,
        totalIssues: 0,

        async startAudit() {
            this.starting = true;
            this.error = '';

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('scope', this.scope);

            try {
                const resp = await fetch(`${baseUrl}/seo-onpage/project/${projectId}/audit/start`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await resp.json();

                if (data.success) {
                    this.jobId = data.job_id;
                    this.total = data.pages_count;
                    this.isRunning = true;
                    this.connectSSE();
                } else {
                    this.error = data.error;
                }
            } catch (e) {
                this.error = 'Errore di connessione';
            }

            this.starting = false;
        },

        connectSSE() {
            this.eventSource = new EventSource(`${baseUrl}/seo-onpage/project/${projectId}/audit/stream?job_id=${this.jobId}`);

            this.eventSource.addEventListener('started', (e) => {
                console.log('Audit started');
            });

            this.eventSource.addEventListener('progress', (e) => {
                const data = JSON.parse(e.data);
                this.currentUrl = data.url;
                this.completed = data.completed;
                this.failed = data.failed;
                this.percent = data.percent;
            });

            this.eventSource.addEventListener('page_completed', (e) => {
                const data = JSON.parse(e.data);
                this.completed = data.completed;
                this.recentResults.unshift({ url: data.url, score: data.score });
                if (this.recentResults.length > 10) this.recentResults.pop();
            });

            this.eventSource.addEventListener('page_error', (e) => {
                const data = JSON.parse(e.data);
                this.failed = data.failed;
            });

            this.eventSource.addEventListener('completed', (e) => {
                const data = JSON.parse(e.data);
                this.completed = data.completed;
                this.failed = data.failed;
                this.avgScore = data.avg_score;
                this.totalIssues = data.total_issues;
                this.isRunning = false;
                this.isCompleted = true;
                this.eventSource.close();
            });

            this.eventSource.addEventListener('cancelled', (e) => {
                this.isRunning = false;
                this.eventSource.close();
            });

            this.eventSource.onerror = () => {
                this.eventSource.close();
                this.startPolling();
            };
        },

        async startPolling() {
            if (!this.isRunning) return;

            try {
                const resp = await fetch(`${baseUrl}/seo-onpage/project/${projectId}/audit/status?job_id=${this.jobId}`);
                const data = await resp.json();

                if (data.success && data.job) {
                    this.completed = data.job.pages_completed;
                    this.failed = data.job.pages_failed;
                    this.percent = data.job.progress;

                    if (data.job.status === 'completed') {
                        this.avgScore = data.job.avg_score;
                        this.totalIssues = data.job.total_issues;
                        this.isRunning = false;
                        this.isCompleted = true;
                        return;
                    }
                    if (data.job.status === 'cancelled' || data.job.status === 'error') {
                        this.isRunning = false;
                        return;
                    }
                }
            } catch (e) {}

            setTimeout(() => this.startPolling(), 2000);
        },

        async cancelAudit() {
            this.cancelling = true;

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('job_id', this.jobId);

            await fetch(`${baseUrl}/seo-onpage/project/${projectId}/audit/cancel`, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            if (this.eventSource) this.eventSource.close();
            this.isRunning = false;
            this.cancelling = false;
        },

        resetAudit() {
            this.isCompleted = false;
            this.completed = 0;
            this.failed = 0;
            this.percent = 0;
            this.recentResults = [];
        }
    };
}
</script>
