<?php
$currentPage = 'dashboard';
include __DIR__ . '/../partials/project-nav.php';
$csrf = csrf_token();
$basePath = '/ai-reputation/project/' . $project['id'];
?>

<?php
$guideSteps = [
    [
        'done' => $profileConfirmed,
        'title' => 'Crea e conferma il profilo',
        'text' => 'In Profilo clicca "Genera il profilo": l\'AI propone delle righe su ' . $project['subject_name'] . ', tu le confermi o le correggi. È la verità con cui giudichiamo le risposte delle AI.',
        'link' => $basePath . '/profile', 'cta' => 'Apri il profilo',
    ],
    [
        'done' => $promptsActive > 0,
        'title' => 'Prepara le domande',
        'text' => 'In Domande scegli l\'audit: Acquisto (le AI citano te o i concorrenti?), Reputazione (cosa dicono di te) o Entrambi. Poi "Genera domande", o scrivile a mano.',
        'link' => $basePath . '/prompts', 'cta' => 'Vai alle domande',
    ],
    [
        'done' => $runsCompleted > 0,
        'title' => 'Avvia l\'audit',
        'text' => 'Qui sotto, "Avvia l\'audit": ogni domanda viene posta a ogni AI scelta in Impostazioni, poi le risposte vengono giudicate. Dura qualche minuto: lascia la pagina aperta.',
        'link' => null, 'cta' => null,
    ],
    [
        'done' => false,
        'title' => 'Leggi il report',
        'text' => 'In cima il rischio e gli interventi suggeriti, sotto le risposte delle AI con le fonti citate.',
        'link' => $lastCompletedRun ? $basePath . '/runs/' . $lastCompletedRun['id'] : null,
        'cta' => $lastCompletedRun ? 'Apri l\'ultimo report' : null,
    ],
];
$guideOpen = $runsCompleted === 0;
?>

<div class="space-y-6">
    <?php if (!$profileConfirmed && ($project['access_role'] ?? 'owner') !== 'viewer'): ?>
    <!-- Chi stai monitorando: nome e tipo decidono come l'AI cerca il soggetto e come scrive piano e schede -->
    <form method="POST" action="<?= url($basePath . '/subject') ?>" class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800 p-5">
        <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Chi stai monitorando?</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">Scrivi il nome come lo cercherebbe un cliente: è quello che chiederemo alle AI. Controllalo prima di generare il profilo.</p>
        <div class="flex flex-wrap items-end gap-3">
            <label class="flex-1 min-w-[14rem]">
                <span class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Nome</span>
                <input type="text" name="subject_name" value="<?= e($project['subject_name']) ?>" required maxlength="255" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </label>
            <label class="w-44">
                <span class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Città (facoltativa)</span>
                <input type="text" name="city" value="<?= e($project['city'] ?? '') ?>" maxlength="120" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </label>
            <div class="flex rounded-lg border border-slate-300 dark:border-slate-600 overflow-hidden text-sm">
                <?php foreach (['company' => 'Azienda o brand', 'person' => 'Persona'] as $val => $lbl): ?>
                <label class="px-3 py-2 cursor-pointer has-[:checked]:bg-indigo-600 has-[:checked]:text-white bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                    <input type="radio" name="subject_type" value="<?= $val ?>" class="sr-only" <?= $project['subject_type'] === $val ? 'checked' : '' ?>><?= $lbl ?>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Salva</button>
        </div>
    </form>
    <?php endif; ?>

    <!-- Come si usa -->
    <div x-data="{ open: <?= $guideOpen ? 'true' : 'false' ?> }" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <button type="button" @click="open = !open" class="w-full px-5 py-4 flex items-center justify-between gap-4 text-left">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Come si usa</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">4 passi per sapere cosa dicono ChatGPT, Gemini e le altre AI su <?= e($project['subject_name']) ?>.</p>
                </div>
            </div>
            <svg class="w-5 h-5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
        </button>
        <ol x-show="open" x-cloak class="px-5 pb-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <?php foreach ($guideSteps as $i => $step): ?>
            <li class="rounded-xl border p-4 <?= $step['done'] ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-800 dark:bg-emerald-900/10' : 'border-slate-200 dark:border-slate-700' ?>">
                <div class="flex items-center gap-2 mb-2">
                    <?php if ($step['done']): ?>
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    </span>
                    <?php else: ?>
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 text-xs font-bold"><?= $i + 1 ?></span>
                    <?php endif; ?>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white"><?= e($step['title']) ?></h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300"><?= e($step['text']) ?></p>
                <?php if ($step['link']): ?>
                <a href="<?= url($step['link']) ?>" class="inline-flex mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                    <?= e($step['cta']) ?> →
                </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>

    <!-- KPI -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400"><a href="<?= url($basePath . '/prompts') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400">Domande</a> <span class="text-xs">(audit <span x-text="$store.arAudit.label().toLowerCase()"></span>)</span></p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><span x-text="$store.arAudit.activeCount()"></span> <span class="text-sm font-normal text-slate-400">attive su <span x-text="$store.arAudit.shownCount()"></span></span></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">AI interrogate</p>
            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400"><?= count($enginesReady) ?></p>
            <p class="text-xs text-slate-400"><?= e(implode(', ', array_map(fn($e) => $engineLabels[$e] ?? $e, $enginesReady))) ?><?php if ($enginesMissing && \Core\Auth::isAdmin()): ?> · <span title="Visibile solo agli admin: manca la chiave API in Admin > Impostazioni">senza chiave: <?= e(implode(', ', array_map(fn($e) => $engineLabels[$e] ?? $e, $enginesMissing))) ?></span><?php endif; ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Audit completati</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $runsCompleted ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Crediti usati</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= rtrim(rtrim(number_format($creditsTotal, 1, ',', '.'), '0'), ',') ?><?php if (\Core\Auth::isAdmin()): ?> <span class="text-sm font-normal text-slate-400" title="Visibile solo agli admin">(<?= number_format($costTotal, 2, ',', '.') ?> $ API)</span><?php endif; ?></p>
        </div>
    </div>

    <!-- Run -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Audit</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Ogni audit pone le domande attive a ogni AI scelta e salva le risposte con le fonti citate.</p>
            </div>
            <?php if ($promptsActive > 0 && ($project['access_role'] ?? 'owner') !== 'viewer'): ?>
            <div x-data="arRunner()" x-init="init()" class="flex items-center gap-3">
                <button type="button" x-show="!running" @click="start()" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                    Avvia l'audit
                </button>
                <span x-show="!running" class="text-xs text-slate-400 whitespace-nowrap" x-text="$store.arAudit.est.responses + ' risposte · ' + $store.arAudit.fmt($store.arAudit.est.credits) + ' crediti'"></span>
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
            Nessun audit ancora. <?= $promptsActive === 0 ? 'Prima prepara le domande nella scheda <a href="' . url($basePath . '/prompts') . '" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Domande</a>.' : 'Avvia il primo audit quando sei pronto.' ?>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Risposte</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Crediti</th>
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
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= rtrim(rtrim(number_format((float) $run['credits_used'], 1, ',', '.'), '0'), ',') ?><?php if (\Core\Auth::isAdmin()): ?> <span class="text-xs text-slate-400" title="Visibile solo agli admin">(<?= number_format((float) $run['cost_total'], 2, ',', '.') ?> $)</span><?php endif; ?></td>
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
</div>

<?= \Core\View::partial('ai-reputation::partials/audit-store', ['project' => $project, 'prompts' => $prompts, 'auditType' => $auditType, 'runEstimate' => $runEstimate, 'csrf' => $csrf, 'basePath' => $basePath]) ?>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('arRun', {
        running: false, runId: null, total: 0, done: 0, percent: 0, phase: 'Raccolta risposte',
        currentEngine: '', currentPrompt: '', message: '', reportUrl: '', log: [],
        labels: <?= json_encode($engineLabels) ?>,
    });
});

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
            const est = this.$store.arAudit.est;
            const fmt = n => this.$store.arAudit.fmt(n);
            if (est.responses === 0) {
                this.notify('Nessuna domanda per questo tipo di audit: generale nella scheda Domande.', 'error');
                return;
            }
            if (est.credits > est.balance) {
                this.notify('Crediti insufficienti: l’audit costa ' + fmt(est.credits) + ' crediti, ne hai ' + fmt(est.balance) + '. Disattiva qualche domanda o ricarica.', 'error');
                return;
            }
            if (!(await window.arConfirm('L’audit pone ' + est.prompts + ' domande alle AI: ' + est.responses + ' risposte da raccogliere e analizzare. Costa ' + fmt(est.credits) + ' crediti (ne hai ' + fmt(est.balance) + ') e dura qualche minuto: lascia la pagina aperta per seguire l’avanzamento.', { buttonText: 'Avvia l’audit' }))) return;
            const s = this.$store.arRun;
            s.message = ''; s.reportUrl = ''; s.log = []; s.done = 0; s.percent = 0;
            try {
                const fd = new FormData(); fd.append('_csrf_token', csrf);
                const resp = await fetch(base + '/runs/start', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
                const data = await resp.json();
                if (!data.success) throw new Error(data.error || 'Errore avvio run');
                s.runId = data.run_id; s.total = data.responses_total; s.running = true;
                if (<?= \Core\Auth::isAdmin() ? 'true' : 'false' ?> && data.engines_missing && data.engines_missing.length) {
                    s.log.push({ id: 'm', text: 'AI senza chiave API (solo admin lo vede), saltate: ' + data.engines_missing.join(', '), error: true });
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
            this.es.addEventListener('failed', e => { const d = JSON.parse(e.data); this.finish(d.message, ''); });
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
            this.es.addEventListener('completed', e => { const d = JSON.parse(e.data); this.finish('Audit completato: ' + d.done + ' risposte, ' + d.analyses + ' analizzate, ' + d.actions + ' azioni proposte' + (d.errors ? ', ' + d.errors + ' errori' : ''), d.report_url); });
            this.es.addEventListener('cancelled', () => this.finish('Audit annullato', ''));
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
                        this.finish(r.status === 'completed' ? 'Audit completato' : ({ failed: 'Audit fallito', cancelled: 'Audit annullato' }[r.status] || 'Audit ' + r.status), r.status === 'completed' ? r.report_url : '');
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
