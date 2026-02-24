<?php
// Filtro categoria
$categoryFilter = $_GET['category'] ?? '';
$filteredTemplates = $templates;
if ($categoryFilter) {
    $filteredTemplates = array_filter($templates, fn($t) => $t['category'] === $categoryFilter);
}

// Mappa colori categoria
$categoryColors = [
    'auth'         => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
    'notification' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
    'module'       => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
    'report'       => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
];

// Mappa label categoria (italiano)
$categoryLabels = [
    'auth'         => 'Auth',
    'notification' => 'Notifiche',
    'module'       => 'Moduli',
    'report'       => 'Report',
];

// Tab di categoria
$categoryTabs = [
    ''             => 'Tutte',
    'auth'         => 'Auth',
    'notification' => 'Notifiche',
    'module'       => 'Moduli',
    'report'       => 'Report',
];
?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Template Email</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gestisci i template delle email inviate dalla piattaforma</p>
    </div>

    <!-- Flash messages -->
    <?php if (!empty($_SESSION['_flash']['success'])): ?>
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200"><?= htmlspecialchars($_SESSION['_flash']['success']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['_flash']['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['_flash']['error'])): ?>
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-red-800 dark:text-red-200"><?= htmlspecialchars($_SESSION['_flash']['error']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['_flash']['error']); ?>
    <?php endif; ?>

    <!-- Category filter tabs -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-1" aria-label="Filtro categoria">
            <?php foreach ($categoryTabs as $key => $label): ?>
                <?php $isActive = $categoryFilter === $key; ?>
                <a href="<?= url('/admin/email-templates') . ($key ? '?category=' . $key : '') ?>"
                   class="flex items-center gap-2 px-4 py-3 border-b-2 font-medium text-sm rounded-t-lg transition-colors <?= $isActive
                       ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20'
                       : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300' ?>">
                    <?= $label ?>
                    <?php if ($key === ''): ?>
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-medium bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300"><?= count($templates) ?></span>
                    <?php else: ?>
                        <?php
                            $catCount = count(array_filter($templates, fn($t) => $t['category'] === $key));
                            if ($catCount > 0):
                        ?>
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-medium bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300"><?= $catCount ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Templates Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <?php if (empty($filteredTemplates)): ?>
            <!-- Empty state -->
            <div class="px-6 py-12 text-center">
                <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
                    <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessun template trovato</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    <?php if ($categoryFilter): ?>
                        Non ci sono template nella categoria selezionata.
                        <a href="<?= url('/admin/email-templates') ?>" class="text-primary-600 dark:text-primary-400 hover:underline">Mostra tutti</a>
                    <?php else: ?>
                        I template email appariranno qui una volta configurati.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nome</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Slug</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Categoria</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ultimo aggiornamento</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($filteredTemplates as $t): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                            x-data="{ isActive: <?= $t['is_active'] ? 'true' : 'false' ?>, toggling: false }">
                            <!-- Nome -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($t['name']) ?></span>
                                </div>
                            </td>

                            <!-- Slug -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-mono text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 px-2 py-0.5 rounded"><?= htmlspecialchars($t['slug']) ?></span>
                            </td>

                            <!-- Categoria -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <?php $catClass = $categoryColors[$t['category']] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-600 dark:text-slate-300'; ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $catClass ?>">
                                    <?= htmlspecialchars($categoryLabels[$t['category']] ?? ucfirst($t['category'])) ?>
                                </span>
                            </td>

                            <!-- Stato (toggle con Alpine.js) -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <button
                                    type="button"
                                    @click="
                                        if (toggling) return;
                                        toggling = true;
                                        fetch('<?= url('/admin/email-templates/' . $t['slug'] . '/toggle') ?>', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                            body: '_csrf_token=' + encodeURIComponent(document.querySelector('meta[name=csrf-token]')?.content || '')
                                        })
                                        .then(r => r.json())
                                        .then(data => {
                                            if (data.success) {
                                                isActive = !!data.is_active;
                                            }
                                            toggling = false;
                                        })
                                        .catch(() => { toggling = false; })
                                    "
                                    class="focus:outline-none"
                                    :class="toggling ? 'opacity-50 cursor-wait' : 'cursor-pointer'"
                                    :title="isActive ? 'Clicca per disattivare' : 'Clicca per attivare'"
                                >
                                    <span x-show="isActive" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Attivo
                                    </span>
                                    <span x-show="!isActive" x-cloak class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Disattivato
                                    </span>
                                </button>
                            </td>

                            <!-- Ultimo aggiornamento -->
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                <?= $t['updated_at'] ? date('d/m/Y H:i', strtotime($t['updated_at'])) : '-' ?>
                            </td>

                            <!-- Azioni -->
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <a href="<?= url('/admin/email-templates/' . $t['slug']) ?>"
                                   class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">
                                    Modifica
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Info card -->
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-slate-500 dark:text-slate-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Come funzionano i template</h4>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    Ogni template utilizza variabili dinamiche (es. <code class="bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded text-xs">{{user_name}}</code>) che vengono sostituite automaticamente al momento dell'invio.
                    Clicca su "Modifica" per personalizzare oggetto, contenuto HTML e testare l'anteprima.
                    I template disattivati non invieranno email.
                </p>
            </div>
        </div>
    </div>
</div>
