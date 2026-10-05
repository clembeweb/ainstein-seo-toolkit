<?php
$currentPage = 'settings';
include __DIR__ . '/../partials/project-nav.php';
$basePath = '/ai-reputation/project/' . $project['id'];
$canEdit = ($project['access_role'] ?? 'owner') !== 'viewer';
?>

<div class="max-w-3xl space-y-6">
    <form method="POST" action="<?= url($basePath . '/settings') ?>" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 divide-y divide-slate-200 dark:divide-slate-700">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

        <div class="p-5 space-y-4">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Soggetto</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome progetto</label>
                    <input type="text" name="name" value="<?= e($project['name']) ?>" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome del soggetto (come lo cercano)</label>
                    <input type="text" name="subject_name" value="<?= e($project['subject_name']) ?>" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo</label>
                    <select name="subject_type" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        <option value="person" <?= $project['subject_type'] === 'person' ? 'selected' : '' ?>>Persona</option>
                        <option value="company" <?= $project['subject_type'] === 'company' ? 'selected' : '' ?>>Azienda</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Città</label>
                    <input type="text" name="city" value="<?= e($project['city'] ?? '') ?>" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sito ufficiale</label>
                    <input type="text" name="website" value="<?= e($project['website'] ?? '') ?>" placeholder="https://" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Omonimi e soggetti da non confondere</label>
                    <textarea name="disambiguation_notes" rows="3" placeholder="Es. «Mario Rossi S.r.l. di Ancona non c'entra», «Maria Rossi è un'altra persona». Persone e aziende, in forma libera." class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"><?= e($project['disambiguation_notes'] ?? '') ?></textarea>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Serve al judge per non attribuire al soggetto fatti di altri. Se un run fa emergere un possibile omonimo non dichiarato, te lo chiederà nel report.</p>
                </div>
            </div>
        </div>

        <div class="p-5 space-y-4">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Engine da interrogare</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($engineLabels as $engine => $label): ?>
                <?php $available = in_array($engine, $availableEngines, true); ?>
                <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-700 <?= $available ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50' : 'opacity-50' ?>">
                    <input type="checkbox" name="engines[]" value="<?= $engine ?>" <?= in_array($engine, $project['engines'], true) ? 'checked' : '' ?> <?= $available ? '' : 'disabled' ?> class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-slate-900 dark:text-white"><?= e($label) ?></span>
                    <?php if (!$available): ?><span class="ml-auto text-xs text-slate-400">non abilitato</span><?php endif; ?>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="sm:w-48">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ripetizioni per domanda</label>
                <input type="number" name="repeats" min="1" max="5" value="<?= (int) $project['repeats'] ?>" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">1 per la demo. 3 per misurare la stabilità delle risposte.</p>
            </div>
        </div>

        <?php if ($canEdit): ?>
        <div class="p-5 flex justify-end">
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors">Salva</button>
        </div>
        <?php endif; ?>
    </form>

    <?php if (($project['access_role'] ?? 'owner') === 'owner'): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900/50 p-5 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-red-700 dark:text-red-400">Elimina progetto</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Cancella domande, run, risposte e analisi. Non si torna indietro.</p>
        </div>
        <form method="POST" action="<?= url($basePath . '/delete') ?>" onsubmit="return confirm('Eliminare definitivamente il progetto e tutti i suoi dati?')">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/30 font-medium transition-colors">Elimina</button>
        </form>
    </div>
    <?php endif; ?>
</div>
