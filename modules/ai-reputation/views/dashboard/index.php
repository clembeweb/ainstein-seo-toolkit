<?php
$currentPage = 'dashboard';
include __DIR__ . '/../partials/project-nav.php';
$csrf = csrf_token();
$basePath = '/ai-reputation/project/' . $project['id'];
?>

<div class="space-y-6">
    <!-- KPI -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Domande attive</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $promptsActive ?> <span class="text-sm font-normal text-slate-400">/ <?= count($prompts) ?></span></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Engine</p>
            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400"><?= count($project['engines']) ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Run completati</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $runsCompleted ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Costo API totale</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($costTotal, 2) ?> <span class="text-sm font-normal text-slate-400">$</span></p>
        </div>
    </div>

    <!-- Run -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Run</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Ogni run pone tutte le domande attive a ogni engine e salva le risposte con le fonti citate.</p>
            </div>
            <?php if ($promptsActive > 0 && ($project['access_role'] ?? 'owner') !== 'viewer'): ?>
            <div x-data="arRunner()" x-init="init()" class="flex items-center gap-3">
                <button type="button" x-show="!running" @click="start()" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                    Avvia run
                </button>
                <button type="button" x-show="running" x-cloak @click="cancel()" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 font-medium transition-colors whitespace-nowrap">Annulla</button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Avanzamento run (Alpine, SSE) -->
        <div x-data x-show="$store.arRun.running || $store.arRun.message" x-cloak class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-indigo-50/50 dark:bg-indigo-900/10">
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="text-slate-700 dark:text-slate-200" x-text="$store.arRun.message || ($store.arRun.phase + ': ' + $store.arRun.done + ' / ' + $store.arRun.total)"></span>
                <span class="text-slate-500 dark:text-slate-400" x-text="$store.arRun.percent + '%'"></span>
            </div>
            <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                <div class="h-2 bg-indigo-600 transition-all" :style="'width:' + $store.arRun.percent + '%'"></div>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 truncate" x-show="$store.arRun.running" x-text="$store.arRun.currentEngine ? ($store.arRun.labels[$store.arRun.currentEngine] || $store.arRun.currentEngine) + ' · ' + $store.arRun.currentPrompt : ''"></p>
            <ul class="mt-2 space-y-0.5 text-xs text-slate-600 dark:text-slate-300 max-h-40 overflow-y-auto">
                <template x-for="l in $store.arRun.log" :key="l.id"><li x-text="l.text" :class="l.error ? 'text-red-600 dark:text-red-400' : ''"></li></template>
            </ul>
            <a x-show="$store.arRun.reportUrl" :href="$store.arRun.reportUrl" class="inline-flex mt-3 items-center px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Apri il report</a>
        </div>
        <?php if (empty($runs)): ?>
        <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">
            Nessun run ancora. <?= $promptsActive === 0 ? 'Prima aggiungi le domande qui sotto.' : 'Avvia il primo run quando sei pronto.' ?>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Risposte</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Costo</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($runs as $run): ?>
                    <?php
                    $statusClass = match ($run['status']) {
                        'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                        'running' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                        'cancelled' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                        default => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                    };
                    $statusLabel = match ($run['status']) {
                        'completed' => 'Completato', 'running' => 'In corso', 'failed' => 'Fallito', 'cancelled' => 'Annullato', default => 'In attesa',
                    };
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?= date('d/m/Y H:i', strtotime($run['created_at'])) ?></td>
                        <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= (int) $run['responses_done'] ?> / <?= (int) $run['responses_total'] ?><?= (int) $run['responses_error'] > 0 ? ' <span class="text-red-500">(' . (int) $run['responses_error'] . ' errori)</span>' : '' ?></td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= number_format((float) $run['cost_total'], 3) ?> $</td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= url($basePath . '/runs/' . $run['id']) ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Report</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Prompt -->
    <div id="prompts" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Domande monitorate</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Le domande che la gente fa alle AI su <?= e($project['subject_name']) ?>. Generale dal <a href="<?= url($basePath . '/profile') ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline">profilo confermato</a>, aggiungile a mano, o parti dalle 8 base.</p>
            </div>
            <div class="flex items-center gap-2" x-data="arPromptGen()">
                <button type="button" @click="generate()" :disabled="busy" class="inline-flex items-center px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60 text-sm font-medium transition-colors whitespace-nowrap" title="Il prompt engine scrive 40 domande dal profilo confermato">
                    <svg x-show="busy" x-cloak class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span x-text="busy ? 'Genero…' : 'Genera domande con AI'"></span>
                </button>
                <form method="POST" action="<?= url($basePath . '/prompts/seed') ?>">
                    <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
                    <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg border border-indigo-200 text-indigo-700 hover:bg-indigo-50 dark:border-indigo-800 dark:text-indigo-300 dark:hover:bg-indigo-900/30 text-sm font-medium transition-colors whitespace-nowrap">
                        + Domande base
                    </button>
                </form>
            </div>
        </div>

        <form method="POST" action="<?= url($basePath . '/prompts') ?>" class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
            <select name="cluster" class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                <?php foreach ($clusters as $key => $label): ?>
                <option value="<?= $key ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="text" required minlength="5" placeholder="Es. <?= e($project['subject_name']) ?> è affidabile?" class="flex-1 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">Aggiungi</button>
        </form>

        <?php if (empty($prompts)): ?>
        <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessuna domanda. Clicca "Domande base" per partire con 8 domande standard.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cluster</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Domanda</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Attiva</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($prompts as $p): ?>
                    <?php
                    $clusterClass = match ($p['cluster']) {
                        'rep' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
                        'comm' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                        'comp' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
                        default => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                    };
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 <?= (int) $p['is_active'] ? '' : 'opacity-50' ?>">
                        <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $clusterClass ?>"><?= strtoupper($p['cluster']) ?></span></td>
                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?= e($p['text']) ?></td>
                        <td class="px-4 py-3">
                            <form method="POST" action="<?= url($basePath . '/prompts/' . $p['id'] . '/toggle') ?>">
                                <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
                                <button type="submit" class="text-xs font-medium <?= (int) $p['is_active'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' ?>" title="Attiva/disattiva">
                                    <?= (int) $p['is_active'] ? 'Sì' : 'No' ?>
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="<?= url($basePath . '/prompts/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('Eliminare questa domanda?')">
                                <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
                                <button type="submit" class="text-slate-400 hover:text-red-600" title="Elimina">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('arRun', {
        running: false, runId: null, total: 0, done: 0, percent: 0, phase: 'Raccolta risposte',
        currentEngine: '', currentPrompt: '', message: '', reportUrl: '', log: [],
        labels: <?= json_encode($engineLabels) ?>,
    });
});

function arPromptGen() {
    const base = '<?= url($basePath) ?>';
    const csrf = '<?= $csrf ?>';
    return {
        busy: false,
        async generate() {
            this.busy = true;
            try {
                const fd = new FormData(); fd.append('_csrf_token', csrf); fd.append('target', '40');
                const resp = await fetch(base + '/prompts/generate', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
                const data = await resp.json();
                if (!data.success) throw new Error(data.error || 'Errore');
                location.href = location.pathname + '?gen=' + data.added + '#prompts';
            } catch (e) { alert(e.message); this.busy = false; }
        },
    };
}

function arRunner() {
    const base = '<?= url($basePath) ?>';
    const csrf = '<?= $csrf ?>';
    const activeRunId = <?= (int) (array_values(array_filter($runs, fn($r) => in_array($r['status'], ['pending', 'running'], true)))[0]['id'] ?? 0) ?>;
    return {
        es: null, poll: null,
        get running() { return this.$store.arRun.running; },
        init() {
            if (activeRunId) { this.$store.arRun.runId = activeRunId; this.$store.arRun.running = true; this.connect(); }
        },
        notify(msg, type) {
            if (window.ainstein && typeof window.ainstein.alert === 'function') { window.ainstein.alert(msg, type); } else { alert(msg); }
        },
        async start() {
            const s = this.$store.arRun;
            s.message = ''; s.reportUrl = ''; s.log = []; s.done = 0; s.percent = 0;
            try {
                const fd = new FormData(); fd.append('_csrf_token', csrf);
                const resp = await fetch(base + '/runs/start', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
                const data = await resp.json();
                if (!data.success) throw new Error(data.error || 'Errore avvio run');
                s.runId = data.run_id; s.total = data.responses_total; s.running = true;
                if (data.engines_missing && data.engines_missing.length) {
                    s.log.push({ id: 'm', text: 'Engine senza API key, saltati: ' + data.engines_missing.join(', '), error: true });
                }
                this.connect();
            } catch (e) { this.notify(e.message, 'error'); }
        },
        connect() {
            const s = this.$store.arRun;
            this.es = new EventSource(base + '/runs/stream?run_id=' + s.runId);
            this.es.addEventListener('started', e => { const d = JSON.parse(e.data); s.total = d.total; });
            this.es.addEventListener('snapshot', e => { const d = JSON.parse(e.data); s.phase = d.phase; s.total = d.total; s.done = d.done; s.percent = d.total ? Math.round(d.done / d.total * 100) : 0; });
            this.es.addEventListener('stalled', e => { const d = JSON.parse(e.data); this.finish(d.message, ''); });
            this.es.addEventListener('phase', e => { const d = JSON.parse(e.data); s.phase = d.label; s.total = d.total; s.done = 0; s.percent = 0; });
            this.es.addEventListener('analysis_completed', e => { const d = JSON.parse(e.data); s.done++; s.percent = s.total ? Math.round(s.done / s.total * 100) : 0; s.log.unshift({ id: 'a' + d.response_id, error: false, text: 'Giudizio ' + (s.labels[d.engine] || d.engine) + ': ' + d.verdict + ' · ' + (d.summary || '') }); });
            this.es.addEventListener('analysis_error', e => { const d = JSON.parse(e.data); s.done++; s.log.unshift({ id: 'ae' + d.response_id, error: true, text: 'Giudizio ' + (s.labels[d.engine] || d.engine) + ': errore · ' + d.error }); });
            this.es.addEventListener('progress', e => { const d = JSON.parse(e.data); s.currentEngine = d.engine; s.currentPrompt = d.prompt; });
            const onItem = (e, isError) => {
                const d = JSON.parse(e.data);
                s.done++; s.percent = s.total ? Math.round(s.done / s.total * 100) : 0;
                const name = s.labels[d.engine] || d.engine;
                s.log.unshift({ id: d.response_id, error: isError,
                    text: isError ? (name + ': errore · ' + (d.error || '')) : (name + ': ' + (d.mentioned ? 'citato' : 'non citato') + ' · ' + d.citations + ' fonti · ' + Math.round(d.latency_ms / 1000) + ' s') });
            };
            this.es.addEventListener('item_completed', e => onItem(e, false));
            this.es.addEventListener('item_error', e => onItem(e, true));
            this.es.addEventListener('completed', e => { const d = JSON.parse(e.data); this.finish('Run completato: ' + d.done + ' risposte, ' + d.analyses + ' analizzate, ' + d.actions + ' azioni proposte' + (d.errors ? ', ' + d.errors + ' errori' : '') + ' · ' + Number(d.cost_total).toFixed(3) + ' $', d.report_url); });
            this.es.addEventListener('cancelled', () => this.finish('Run annullato', ''));
            this.es.onerror = () => { if (this.es) { this.es.close(); this.es = null; } this.startPolling(); };
        },
        startPolling() {
            if (this.poll) return;
            this.poll = setInterval(async () => {
                try {
                    const resp = await fetch(base + '/runs/status?run_id=' + this.$store.arRun.runId);
                    if (!resp.ok) return;
                    const data = await resp.json();
                    if (!data.success) return;
                    const s = this.$store.arRun; const r = data.run;
                    s.total = r.total; s.done = r.done + r.errors; s.percent = r.total ? Math.round(s.done / r.total * 100) : 0;
                    if (['completed', 'failed', 'cancelled'].includes(r.status)) {
                        this.finish(r.status === 'completed' ? 'Run completato' : 'Run ' + r.status, r.status === 'completed' ? r.report_url : '');
                    }
                } catch (e) { /* riprova al prossimo giro */ }
            }, 4000);
        },
        async cancel() {
            try {
                const fd = new FormData(); fd.append('_csrf_token', csrf); fd.append('run_id', this.$store.arRun.runId);
                await fetch(base + '/runs/cancel', { method: 'POST', body: fd });
            } catch (e) { this.notify(e.message, 'error'); }
        },
        finish(message, reportUrl) {
            const s = this.$store.arRun;
            s.running = false; s.message = message; s.reportUrl = reportUrl; s.percent = 100;
            if (this.es) { this.es.close(); this.es = null; }
            if (this.poll) { clearInterval(this.poll); this.poll = null; }
        },
    };
}
</script>
