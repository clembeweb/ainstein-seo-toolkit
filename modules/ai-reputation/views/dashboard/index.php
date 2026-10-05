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
            <?php if ($promptsActive > 0): ?>
            <button type="button" disabled class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400 font-medium cursor-not-allowed" title="Il collector arriva nel prossimo passo (M1.4)">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                Avvia run
            </button>
            <?php endif; ?>
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
                <p class="text-sm text-slate-500 dark:text-slate-400">Le domande che la gente fa alle AI su <?= e($project['subject_name']) ?>. Il prompt engine automatico arriva dopo: per ora si scrivono a mano.</p>
            </div>
            <form method="POST" action="<?= url($basePath . '/prompts/seed') ?>">
                <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
                <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg border border-indigo-200 text-indigo-700 hover:bg-indigo-50 dark:border-indigo-800 dark:text-indigo-300 dark:hover:bg-indigo-900/30 text-sm font-medium transition-colors whitespace-nowrap">
                    + Domande base
                </button>
            </form>
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
