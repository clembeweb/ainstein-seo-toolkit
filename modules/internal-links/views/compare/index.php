<!-- Comparison Mode -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Confronta Snapshot</h1>
            <p class="mt-1 text-slate-500 dark:text-slate-400">
                Monitora i cambiamenti nella struttura dei link interni nel tempo
            </p>
        </div>
    </div>

    <!-- Create Snapshot Form -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Crea Nuovo Snapshot
        </h3>
        <form method="POST" action="<?= url("/internal-links/project/{$project['id']}/compare/create") ?>" class="flex flex-wrap items-end gap-4">
            <?= csrf_field() ?>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome Snapshot</label>
                <input type="text" name="name" placeholder="es. Audit pre-lancio"
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descrizione (opzionale)</label>
                <input type="text" name="description" placeholder="Breve descrizione..."
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900">
            </div>
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Crea Snapshot
            </button>
        </form>
    </div>

    <!-- Snapshots List -->
    <?php if (empty($snapshots)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Nessuno Snapshot</h3>
        <p class="text-slate-500 dark:text-slate-400">
            Crea il tuo primo snapshot per iniziare a monitorare i cambiamenti nel tempo.
        </p>
    </div>
    <?php else: ?>

    <!-- Compare Form -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            Confronta Due Snapshot
        </h3>
        <form method="GET" action="<?= url("/internal-links/project/{$project['id']}/compare") ?>" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Baseline (piu vecchio)</label>
                <select name="snapshot1" class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900">
                    <option value="">Seleziona snapshot...</option>
                    <?php foreach ($snapshots as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($selectedSnapshot1 ?? '') == $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?> (<?= date('j M Y', strtotime($s['created_at'])) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confronta con (piu recente)</label>
                <select name="snapshot2" class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900">
                    <option value="">Seleziona snapshot...</option>
                    <?php foreach ($snapshots as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($selectedSnapshot2 ?? '') == $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?> (<?= date('j M Y', strtotime($s['created_at'])) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Confronta
            </button>
        </form>
    </div>

    <!-- Comparison Results -->
    <?php if (!empty($comparison)): ?>
    <div class="space-y-6">
        <!-- Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <?php
            $metrics = [
                ['label' => 'URL', 'key' => 'total_urls', 'icon' => 'document'],
                ['label' => 'Link Totali', 'key' => 'total_links', 'icon' => 'link'],
                ['label' => 'Interni', 'key' => 'internal_links', 'icon' => 'link-internal'],
                ['label' => 'Esterni', 'key' => 'external_links', 'icon' => 'link-external'],
                ['label' => 'Orfani', 'key' => 'orphan_pages', 'icon' => 'unlink'],
                ['label' => 'Score Medio', 'key' => 'avg_relevance_score', 'icon' => 'star'],
            ];
            ?>
            <?php foreach ($metrics as $metric):
                $diff = $comparison['stats_diff'][$metric['key']] ?? 0;
                $isPositive = $metric['key'] === 'orphan_pages' ? $diff < 0 : $diff > 0;
                $diffColor = $diff == 0 ? 'text-slate-400' : ($isPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400');
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center justify-between mb-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/></svg>
                    <span class="text-xs <?= $diffColor ?> font-medium">
                        <?php if ($diff > 0): ?>+<?php endif; ?><?= $metric['key'] === 'avg_relevance_score' ? round($diff, 1) : $diff ?>
                    </span>
                </div>
                <p class="text-lg font-bold text-slate-900 dark:text-white">
                    <?= $metric['key'] === 'avg_relevance_score'
                        ? round($comparison['snapshot2'][$metric['key']] ?? 0, 1)
                        : number_format($comparison['snapshot2'][$metric['key']] ?? 0) ?>
                </p>
                <p class="text-xs text-slate-500"><?= $metric['label'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Change Summary -->
        <div class="grid md:grid-cols-3 gap-4">
            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?= number_format($comparison['summary']['links_added'] ?? 0) ?></p>
                        <p class="text-sm text-green-700 dark:text-green-300">Link Aggiunti</p>
                    </div>
                </div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= number_format($comparison['summary']['links_removed'] ?? 0) ?></p>
                        <p class="text-sm text-red-700 dark:text-red-300">Link Rimossi</p>
                    </div>
                </div>
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= number_format($comparison['summary']['links_changed'] ?? $comparison['summary']['anchors_changed'] ?? 0) ?></p>
                        <p class="text-sm text-amber-700 dark:text-amber-300">Link Modificati</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Changes -->
        <?php if (!empty($comparison['added']) || !empty($comparison['removed']) || !empty($comparison['changed'])): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div x-data="{ tab: 'added' }" class="divide-y divide-slate-200 dark:divide-slate-700">
                <!-- Tabs -->
                <div class="flex border-b border-slate-200 dark:border-slate-700">
                    <button @click="tab = 'added'" :class="tab === 'added' ? 'border-b-2 border-green-500 text-green-600' : 'text-slate-500'"
                            class="px-6 py-3 text-sm font-medium">
                        Aggiunti (<?= count($comparison['added'] ?? []) ?>)
                    </button>
                    <button @click="tab = 'removed'" :class="tab === 'removed' ? 'border-b-2 border-red-500 text-red-600' : 'text-slate-500'"
                            class="px-6 py-3 text-sm font-medium">
                        Rimossi (<?= count($comparison['removed'] ?? []) ?>)
                    </button>
                    <button @click="tab = 'changed'" :class="tab === 'changed' ? 'border-b-2 border-amber-500 text-amber-600' : 'text-slate-500'"
                            class="px-6 py-3 text-sm font-medium">
                        Modificati (<?= count($comparison['changed'] ?? []) ?>)
                    </button>
                </div>

                <!-- Added Links -->
                <div x-show="tab === 'added'" class="overflow-x-auto">
                    <?php if (empty($comparison['added'])): ?>
                    <div class="p-8 text-center text-slate-500">Nessun link aggiunto</div>
                    <?php else: ?>
                    <table class="w-full data-table">
                        <thead>
                            <tr>
                                <th>URL Sorgente</th>
                                <th>URL Destinazione</th>
                                <th>Anchor Text</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($comparison['added'], 0, 50) as $link): ?>
                            <tr>
                                <td class="max-w-[200px] truncate text-sm"><?= e(mb_substr($link['source_url'] ?? '', 0, 40)) ?></td>
                                <td class="max-w-[200px] truncate text-sm"><?= e(mb_substr($link['destination_url'] ?? '', 0, 40)) ?></td>
                                <td class="max-w-[150px]">
                                    <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs rounded">
                                        <?= e(mb_substr($link['anchor_text'] ?? '-', 0, 30)) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($comparison['added']) > 50): ?>
                    <div class="p-4 text-center text-sm text-slate-500">
                        Mostrando 50 di <?= number_format(count($comparison['added'])) ?> link aggiunti
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Removed Links -->
                <div x-show="tab === 'removed'" class="overflow-x-auto">
                    <?php if (empty($comparison['removed'])): ?>
                    <div class="p-8 text-center text-slate-500">Nessun link rimosso</div>
                    <?php else: ?>
                    <table class="w-full data-table">
                        <thead>
                            <tr>
                                <th>URL Sorgente</th>
                                <th>URL Destinazione</th>
                                <th>Anchor Text</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($comparison['removed'], 0, 50) as $link): ?>
                            <tr>
                                <td class="max-w-[200px] truncate text-sm"><?= e(mb_substr($link['source_url'] ?? '', 0, 40)) ?></td>
                                <td class="max-w-[200px] truncate text-sm"><?= e(mb_substr($link['destination_url'] ?? '', 0, 40)) ?></td>
                                <td class="max-w-[150px]">
                                    <span class="inline-flex items-center px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs rounded line-through">
                                        <?= e(mb_substr($link['anchor_text'] ?? '-', 0, 30)) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($comparison['removed']) > 50): ?>
                    <div class="p-4 text-center text-sm text-slate-500">
                        Mostrando 50 di <?= number_format(count($comparison['removed'])) ?> link rimossi
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Changed Anchors -->
                <div x-show="tab === 'changed'" class="overflow-x-auto">
                    <?php if (empty($comparison['changed'])): ?>
                    <div class="p-8 text-center text-slate-500">Nessun anchor text modificato</div>
                    <?php else: ?>
                    <table class="w-full data-table">
                        <thead>
                            <tr>
                                <th>URL Sorgente</th>
                                <th>URL Destinazione</th>
                                <th>Anchor Vecchio</th>
                                <th>Anchor Nuovo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($comparison['changed'], 0, 50) as $link): ?>
                            <tr>
                                <td class="max-w-[180px] truncate text-sm"><?= e(mb_substr($link['source_url'] ?? '', 0, 35)) ?></td>
                                <td class="max-w-[180px] truncate text-sm"><?= e(mb_substr($link['destination_url'] ?? '', 0, 35)) ?></td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs rounded line-through">
                                        <?= e(mb_substr($link['old_anchor'] ?? '-', 0, 25)) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs rounded">
                                        <?= e(mb_substr($link['new_anchor'] ?? '-', 0, 25)) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($comparison['changed']) > 50): ?>
                    <div class="p-4 text-center text-sm text-slate-500">
                        Mostrando 50 di <?= number_format(count($comparison['changed'])) ?> anchor modificati
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-200 dark:border-green-800 p-8 text-center">
            <svg class="w-12 h-12 text-green-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Nessuna Modifica Rilevata</h3>
            <p class="text-green-700 dark:text-green-300 mt-1">
                La struttura dei link interni e identica tra questi due snapshot.
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Existing Snapshots -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">Snapshot Salvati</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full data-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrizione</th>
                        <th class="text-center">URL</th>
                        <th class="text-center">Link</th>
                        <th class="text-center">Orfani</th>
                        <th class="text-center">Score Medio</th>
                        <th>Creato</th>
                        <th class="w-20">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($snapshots as $s): ?>
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-white"><?= e($s['name']) ?></td>
                        <td class="text-sm text-slate-500"><?= e(mb_substr($s['description'] ?? '-', 0, 40)) ?></td>
                        <td class="text-center"><?= number_format($s['total_urls'] ?? 0) ?></td>
                        <td class="text-center"><?= number_format($s['total_links'] ?? 0) ?></td>
                        <td class="text-center">
                            <span class="<?= ($s['orphan_pages'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' ?>">
                                <?= number_format($s['orphan_pages'] ?? 0) ?>
                            </span>
                        </td>
                        <td class="text-center"><?= !empty($s['avg_relevance_score']) ? round($s['avg_relevance_score'], 1) : '-' ?></td>
                        <td class="text-sm text-slate-500"><?= date('j M Y', strtotime($s['created_at'])) ?></td>
                        <td>
                            <form method="POST" action="<?= url("/internal-links/project/{$project['id']}/compare/delete/{$s['id']}") ?>"
                                  onsubmit="return confirm('Eliminare questo snapshot?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 transition" title="Elimina">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
