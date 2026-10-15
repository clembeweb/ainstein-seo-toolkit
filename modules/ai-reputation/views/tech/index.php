<?php
$currentPage = 'tech';
include __DIR__ . '/../partials/project-nav.php';
$csrf = csrf_token();
$basePath = '/ai-reputation/project/' . $project['id'];
$canEdit = ($project['access_role'] ?? 'owner') !== 'viewer';
$levelDot = ['bad' => 'bg-red-500', 'warn' => 'bg-amber-500', 'info' => 'bg-slate-400', 'ok' => 'bg-emerald-500'];
$levelLabel = ['bad' => 'Da sistemare', 'warn' => 'Da migliorare', 'info' => 'Da sapere', 'ok' => 'Ok'];
$verdictBadge = [
    'ok' => ['Entra', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'],
    'blocked_server' => ['Bloccato dal server', 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'],
    'blocked_robots' => ['Bloccato da robots.txt', 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'],
    'unverifiable' => ['Non verificabile', 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'],
];
$s = $check['schema'] ?? [];
$isPerson = $project['subject_type'] === 'person';
// Persona: niente righe da azienda (P.IVA, via, telefono) che risulterebbero sempre "mancanti"
$pick = fn(array $rows, ?array $only) => $only === null ? $rows : array_intersect_key($rows, $only);
$f = $check['facts'] ?? [];
?>

<div class="space-y-6" x-data="arTech()">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-3xl">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Le AI riescono a leggere il sito e a capire chi è <?= e($project['subject_name']) ?>?</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Entriamo nel sito con i crawler veri di ChatGPT, Claude, Perplexity e Bing (non solo leggendo il robots.txt), controlliamo i dati strutturati e i dati che <?= $project['subject_type'] === 'person' ? 'servono a riconoscerti: profili, attività' : 'i clienti cercano: partita IVA, indirizzo, telefono, recensioni' ?>. Il controllo è gratuito e gira anche a ogni audit, così il piano dice la causa di ogni dubbio delle AI.</p>
            <?php if ($check): ?>
            <p class="text-xs text-slate-400 mt-2">Ultimo controllo: <?= date('d/m/Y H:i', strtotime((string) $check['_created_at'])) ?> su <?= e($check['home']['final_url'] ?? $check['url'] ?? '') ?><?= isset($check['seconds']) ? ' · ' . str_replace('.', ',', (string) $check['seconds']) . ' s' : '' ?></p>
            <?php endif; ?>
        </div>
        <?php if ($canEdit): ?>
        <?php if (!empty($project['website'])): ?>
        <div class="flex flex-col items-end gap-1">
            <button type="button" @click="run()" :disabled="busy" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-60 transition-colors whitespace-nowrap">
                <svg class="w-5 h-5" :class="busy ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                <span x-text="busy ? 'Controllo in corso…' : '<?= $check ? 'Ripeti il controllo' : 'Esegui il controllo' ?>'"></span>
            </button>
            <span class="text-xs text-slate-400">Gratis · 5-30 secondi</span>
        </div>
        <?php else: ?>
        <a href="<?= url($basePath . '/settings') ?>" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Indica il sito in Impostazioni</a>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if (!$check): ?>
    <?= \Core\View::partial('components/table-empty-state', ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>', 'heading' => 'Nessun controllo ancora', 'message' => 'Esegui il controllo: ci vogliono pochi secondi e non costa crediti.']) ?>
    <?php else: ?>

    <!-- Esiti -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Cosa abbiamo trovato</h2>
        </div>
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($findings as $item): ?>
            <li class="px-5 py-3 flex gap-3">
                <span class="mt-1.5 w-2.5 h-2.5 rounded-full shrink-0 <?= $levelDot[$item['level']] ?>" title="<?= $levelLabel[$item['level']] ?>"></span>
                <div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($item['title']) ?></p>
                    <p class="text-sm text-slate-600 dark:text-slate-300"><?= e($item['detail']) ?></p>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Crawler -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Crawler delle AI</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Richiesta vera alla home con il nome di ogni crawler. Quelli "di risposta" servono per essere citati; quelli di addestramento si possono bloccare per scelta.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <?php foreach (['Crawler', 'Di chi', 'A cosa serve', 'robots.txt', 'Server', 'Esito'] as $th): ?>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= $th ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($check['bots'] ?? [] as $key => $b): $bot = $bots[$key] ?? ['name' => $key, 'owner' => '', 'purpose' => '']; $vb = $verdictBadge[$b['verdict']] ?? [$b['verdict'], '']; ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?= e($bot['name']) ?><?php if (in_array($key, $answerBots, true)): ?> <span class="text-xs font-normal text-indigo-600 dark:text-indigo-400">risposte</span><?php endif; ?></td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= e($bot['owner']) ?></td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= e($bot['purpose']) ?></td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= ['blocked' => 'blocca', 'allowed' => 'consente', 'no_robots' => 'assente'][$b['robots']] ?? e($b['robots']) ?></td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= $b['http_code'] === null ? '–' : 'HTTP ' . (int) $b['http_code'] ?></td>
                        <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $vb[1] ?>"><?= $vb[0] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chi sei per le macchine -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-3">Dati strutturati (JSON-LD)</h2>
            <dl class="text-sm space-y-1.5">
                <?php foreach ($pick(['Tipo' => !empty($s['types']) ? implode(', ', $s['types']) : null, 'Nome' => $s['name'] ?? null, 'Ragione sociale' => $s['legal_name'] ?? null, 'Partita IVA' => $s['vat'] ?? null, 'Via' => $s['street'] ?? null, 'Città' => $s['locality'] ?? null, 'Telefono' => $s['telephone'] ?? null, 'Profili esterni' => !empty($s['same_as']) ? count($s['same_as']) . ' link' : null], $isPerson ? ['Tipo' => 1, 'Nome' => 1, 'Profili esterni' => 1] : null) as $lbl => $val): ?>
                <div class="flex gap-2"><dt class="w-32 shrink-0 text-slate-500 dark:text-slate-400"><?= $lbl ?></dt><dd class="<?= $val === null ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' ?>"><?= $val === null ? 'manca' : e($val) ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-3">Cosa legge un crawler nelle pagine</h2>
            <dl class="text-sm space-y-1.5">
                <?php foreach ($pick(['Parole in home' => isset($check['readable']['words']) ? (string) (int) $check['readable']['words'] : null, 'Partita IVA' => !empty($f['vat']) ? implode(', ', $f['vat']) : null, 'Via' => !empty($f['street']) ? implode('; ', array_slice($f['street'], 0, 2)) : null, 'Telefoni' => !empty($f['phones']) ? implode(', ', $f['phones']) : null, 'Link esterni' => !empty($f['review_links']) ? count($f['review_links']) . ' (Google, social, recensioni)' : null, 'Pagine lette' => implode(', ', array_merge(['home'], array_map(fn($k) => ['about' => 'Chi siamo', 'contact' => 'Contatti'][$k] ?? $k, array_keys($check['pages'] ?? [])))), 'Sitemap' => !empty($check['sitemap']['found']) ? (string) $check['sitemap']['url'] : null, 'llms.txt' => !empty($check['llms']['found']) ? 'presente' : 'assente (peso basso)'], $isPerson ? ['Parole in home' => 1, 'Link esterni' => 1, 'Pagine lette' => 1, 'Sitemap' => 1, 'llms.txt' => 1] : null) as $lbl => $val): ?>
                <div class="flex gap-2"><dt class="w-32 shrink-0 text-slate-500 dark:text-slate-400"><?= $lbl ?></dt><dd class="break-all <?= $val === null ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' ?>"><?= $val === null ? 'non trovato' : e($val) ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function arTech() {
    return {
        busy: false,
        async run() {
            this.busy = true;
            try {
                const fd = new FormData(); fd.append('_csrf_token', '<?= $csrf ?>');
                const resp = await fetch('<?= url($basePath . '/tech/run') ?>', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
                const data = await resp.json();
                if (!data.success) throw new Error(data.error || 'Controllo non riuscito');
                window.location.reload();
            } catch (e) {
                if (window.ainstein && typeof window.ainstein.alert === 'function') { window.ainstein.alert(e.message, 'error'); } else { alert(e.message); }
                this.busy = false;
            }
        }
    };
}
</script>
