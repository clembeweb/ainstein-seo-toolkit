<?php
/** Dettagli del controllo AI gratuito della home (login richiesto). Dati: $check (row, result, findings, score), $bots, $answerBots */
$row = $check['row'];
$res = $check['result'];
$score = $check['score'];
$pct = $score['total'] ? (int) round($score['score'] / $score['total'] * 100) : 0;
$levelStyle = [
    'bad' => ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', 'Da sistemare'],
    'warn' => ['bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'Da migliorare'],
    'info' => ['bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300', 'Nota'],
    'ok' => ['bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'Ok'],
];
$verdictLabel = [
    'ok' => ['Entra', 'text-emerald-600 dark:text-emerald-400'],
    'blocked_server' => ['Bloccato dal server', 'text-red-600 dark:text-red-400'],
    'blocked_robots' => ['Bloccato da robots.txt', 'text-red-600 dark:text-red-400'],
    'unverifiable' => ['Non verificabile', 'text-slate-500'],
];
?>
<div class="max-w-4xl space-y-6">
    <div>
        <p class="text-sm text-slate-500 dark:text-slate-400">Controllo AI gratuito · <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></p>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">Le AI leggono <?= e($row['host']) ?>?</h1>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 flex flex-wrap items-center gap-6">
        <div class="relative w-24 h-24 rounded-full grid place-items-center" style="background: conic-gradient(#006e96 0 <?= $pct ?>%, #e2e8f0 <?= $pct ?>% 100%)">
            <div class="w-[76px] h-[76px] rounded-full bg-white dark:bg-slate-800 grid place-items-center text-xl font-bold text-slate-900 dark:text-white"><?= (int) $score['score'] ?>/<?= (int) $score['total'] ?></div>
        </div>
        <div class="flex-1 min-w-[16rem]">
            <p class="text-lg font-semibold text-slate-900 dark:text-white">
                <?= $score['score'] >= 4 ? 'Le AI leggono bene il sito e capiscono chi sei.' : ($score['score'] >= 2 ? 'Le AI leggono il sito, ma non capiscono bene chi sei.' : 'Le AI fanno fatica a leggere il sito.') ?>
            </p>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">5 controlli: accesso dei lettori AI, testo leggibile, dati strutturati, dati societari, sitemap.</p>
        </div>
    </div>

    <?php if (!empty($res['error'])): ?>
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 px-5 py-4 text-sm text-amber-800 dark:text-amber-200"><?= e($res['error']) ?></div>
    <?php endif; ?>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700/60">
        <?php foreach ($check['findings'] as $f): [$cls, $lbl] = $levelStyle[$f['level']] ?? $levelStyle['info']; ?>
        <div class="px-5 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $cls ?>"><?= $lbl ?></span>
                <h3 class="font-medium text-slate-900 dark:text-white"><?= e(\Services\PublicAiCheckService::title($f)) ?></h3>
            </div>
            <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-300"><?= e($f['detail']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($res['bots'])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700"><h2 class="font-semibold text-slate-900 dark:text-white">I lettori delle AI, uno per uno</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <?php foreach (['Lettore', 'Di chi è', 'A cosa serve', 'Esito'] as $h): ?>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= $h ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($res['bots'] as $key => $b): if (!isset($bots[$key])) continue; [$vl, $vc] = $verdictLabel[$b['verdict']] ?? [$b['verdict'], 'text-slate-500']; ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?= e($bots[$key]['name']) ?><?= in_array($key, $answerBots, true) ? '' : ' <span class="text-xs font-normal text-slate-400">(addestramento)</span>' ?></td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= e($bots[$key]['owner']) ?></td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= e($bots[$key]['purpose']) ?></td>
                        <td class="px-4 py-3 text-sm font-medium <?= $vc ?>"><?= e($vl) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 p-6 text-white flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-lg font-semibold">Questo è il lato tecnico. Ma le AI ti consigliano?</p>
            <p class="text-sm text-indigo-100 mt-1">Crea il progetto per <?= e($row['host']) ?> e attiva AI GEO Audit: facciamo a ChatGPT e Gemini le domande dei tuoi clienti.</p>
        </div>
        <a href="<?= url('/projects/create?domain=' . urlencode($row['host'])) ?>" class="inline-flex items-center px-5 py-2.5 rounded-lg bg-white text-indigo-700 font-semibold hover:bg-indigo-50">Crea il progetto</a>
    </div>
</div>
