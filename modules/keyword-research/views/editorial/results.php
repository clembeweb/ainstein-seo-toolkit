<?php
$totalArticles = (int) ($stats['total_articles'] ?? 0);
$totalMonths = (int) ($stats['total_months'] ?? 0);
$totalCategories = (int) ($stats['total_categories'] ?? 0);
$totalVolume = (int) ($stats['total_volume'] ?? 0);
$sentCount = (int) ($stats['sent_count'] ?? 0);

// Colori per categorie
$categoryColors = [
    'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300',
    'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
    'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/50 dark:text-cyan-300',
];
$categories = $brief['categories'] ?? [];
$catColorMap = [];
foreach ($categories as $i => $cat) {
    $catColorMap[$cat] = $categoryColors[$i % count($categoryColors)];
}

// Mesi italiani
$mesiIt = ['', 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
$startMonth = (int) date('n');
$startYear = (int) date('Y');

// Intent badge colors
$intentColors = [
    'informational' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'transactional' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'commercial' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'navigational' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
];

// Difficulty badge
$diffColors = [
    'low' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'high' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
];
$diffLabels = ['low' => 'Facile', 'medium' => 'Media', 'high' => 'Difficile'];
?>

<div x-data="editorialResults()" class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
                <a href="<?= url('/keyword-research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Keyword Research</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="<?= url('/keyword-research/project/' . $project['id'] . '/editorial') ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 dark:text-white">Piano Editoriale</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Piano Editoriale</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= date('d/m/Y H:i', strtotime($research['created_at'])) ?> | <?= e($brief['theme'] ?? '') ?>
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex flex-wrap gap-3">
            <a href="<?= url('/keyword-research/project/' . $project['id'] . '/editorial/' . $research['id'] . '/export') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Esporta CSV
            </a>
            <a href="<?= url('/keyword-research/project/' . $project['id'] . '/editorial') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Piano
            </a>
        </div>
    </div>

    <?= \Core\View::partial('components/orphaned-project-notice', ['project' => $project]) ?>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-violet-600 dark:text-violet-400"><?= $totalArticles ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Articoli</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $totalMonths ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Mesi</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $totalCategories ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Categorie</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalVolume) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Volume totale</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $research['credits_used'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Crediti</p>
        </div>
    </div>

    <!-- Strategy Note -->
    <?php if (!empty($research['strategy_note'])): ?>
    <div class="bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded-xl p-5">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-violet-600 dark:text-violet-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            <div>
                <h3 class="font-semibold text-violet-800 dark:text-violet-200 mb-1">Strategia Editoriale</h3>
                <p class="text-sm text-violet-700 dark:text-violet-300"><?= e($research['strategy_note']) ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cross-module CTA: Send to AI Content banner -->
    <?php if ($sentCount === 0 && !empty($aicProjects) && $totalArticles > 0): ?>
    <div class="relative overflow-hidden rounded-xl border border-amber-500/20 bg-gradient-to-r from-amber-950 via-slate-900 to-slate-900 p-5">
        <div class="absolute top-0 right-0 w-48 h-48 bg-amber-500/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
        <div class="relative flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-xl bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-white">Piano pronto! Trasformalo in articoli</h3>
                    <p class="text-sm text-slate-300 mt-0.5">Seleziona gli articoli e inviali ad AI Content Generator. Ainstein scrivera articoli completi per ogni keyword.</p>
                </div>
            </div>
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-amber-500/20 text-amber-300 text-xs font-medium">
                    Seleziona gli articoli nella tabella sotto
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($aicProjects)): ?>
    <!-- Send to AI Content Bar -->
    <div x-show="selectedItems.length > 0"
         x-transition
         class="sticky top-4 z-10 bg-violet-600 text-white rounded-xl shadow-lg p-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span class="font-medium" x-text="selectedItems.length + ' articoli selezionati'"></span>
        </div>
        <div class="flex items-center gap-3">
            <select x-model="aicProjectId" class="rounded-lg border-0 bg-violet-500 text-white text-sm py-1.5 px-3 focus:ring-2 focus:ring-white">
                <option value="">Seleziona progetto AI Content</option>
                <?php foreach ($aicProjects as $aicP): ?>
                <option value="<?= $aicP['id'] ?>"><?= e($aicP['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button @click="sendToContent()" :disabled="!aicProjectId || sending"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-white text-violet-700 font-medium text-sm hover:bg-violet-50 transition-colors disabled:opacity-50">
                <svg x-show="!sending" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <svg x-show="sending" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Invia a AI Content
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Success/Error message -->
    <div x-show="sendMessage" x-transition
         class="rounded-xl p-4"
         :class="sendError ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800'">
        <p class="text-sm" :class="sendError ? 'text-red-700 dark:text-red-300' : 'text-emerald-700 dark:text-emerald-300'" x-text="sendMessage"></p>
    </div>

    <!-- Monthly Sections -->
    <?php foreach ($itemsByMonth as $monthNum => $items): ?>
    <?php
    $realMonth = (($startMonth - 1 + $monthNum - 1) % 12) + 1;
    $realYear = $startYear + intdiv($startMonth - 1 + $monthNum - 1, 12);
    $monthLabel = "Mese {$monthNum} — {$mesiIt[$realMonth]} {$realYear}";
    ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <!-- Month Header -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                    <span class="text-sm font-bold text-white"><?= $monthNum ?></span>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white"><?= $monthLabel ?></h3>
                <span class="text-xs text-slate-500"><?= count($items) ?> articoli</span>
            </div>
            <?php if (!empty($aicProjects)): ?>
            <button @click="toggleMonth(<?= $monthNum ?>)" class="text-xs text-violet-600 dark:text-violet-400 hover:text-violet-700">
                <span x-text="isMonthSelected(<?= $monthNum ?>) ? 'Deseleziona tutti' : 'Seleziona tutti'"></span>
            </button>
            <?php endif; ?>
        </div>

        <!-- Articles Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <?php if (!empty($aicProjects)): ?>
                        <th class="w-12 px-4 py-3"></th>
                        <?php endif; ?>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Titolo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Volume</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Categoria</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Intent</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Difficolta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($items as $item): ?>
                    <?php
                    $catColor = $catColorMap[$item['category']] ?? $categoryColors[0];
                    $intColor = $intentColors[strtolower($item['intent'] ?? '')] ?? $intentColors['informational'];
                    $difColor = $diffColors[strtolower($item['difficulty'] ?? 'medium')] ?? $diffColors['medium'];
                    $difLabel = $diffLabels[strtolower($item['difficulty'] ?? 'medium')] ?? 'Media';
                    $secondaryKw = json_decode($item['secondary_keywords'] ?? '[]', true);
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors <?= $item['sent_to_content'] ? 'opacity-60' : '' ?>">
                        <?php if (!empty($aicProjects)): ?>
                        <td class="px-4 py-3">
                            <?php if (!$item['sent_to_content']): ?>
                            <input type="checkbox" value="<?= $item['id'] ?>"
                                   @change="toggleItem(<?= $item['id'] ?>, <?= $monthNum ?>)"
                                   :checked="selectedItems.includes(<?= $item['id'] ?>)"
                                   class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                            <?php else: ?>
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-slate-900 dark:text-white"><?= e($item['title']) ?></div>
                            <?php if (!empty($item['notes'])): ?>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1"><?= e($item['notes']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($secondaryKw)): ?>
                            <div class="flex flex-wrap gap-1 mt-1">
                                <?php foreach (array_slice($secondaryKw, 0, 3) as $sk): ?>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400"><?= e($sk['text'] ?? '') ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300"><?= e($item['main_keyword']) ?></td>
                        <td class="px-4 py-3 text-center text-sm font-medium text-slate-900 dark:text-white"><?= number_format($item['main_volume']) ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $catColor ?>"><?= e($item['category']) ?></span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400"><?= e(ucfirst($item['content_type'] ?? '')) ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $intColor ?>"><?= e(ucfirst($item['intent'] ?? '')) ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $difColor ?>"><?= $difLabel ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($itemsByMonth)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <p class="text-slate-500 dark:text-slate-400">Nessun articolo nel piano editoriale.</p>
    </div>
    <?php endif; ?>
</div>

<script>
function editorialResults() {
    return {
        selectedItems: [],
        aicProjectId: '',
        sending: false,
        sendMessage: '',
        sendError: false,
        csrfToken: '<?= csrf_token() ?>',

        // Items per month mapping
        monthItems: <?= json_encode(array_map(function($items) {
            return array_map(function($item) { return (int)$item['id']; }, array_filter($items, function($item) { return !$item['sent_to_content']; }));
        }, $itemsByMonth)) ?>,

        toggleItem(id, month) {
            const idx = this.selectedItems.indexOf(id);
            if (idx >= 0) {
                this.selectedItems.splice(idx, 1);
            } else {
                this.selectedItems.push(id);
            }
        },

        isMonthSelected(month) {
            const items = this.monthItems[month] || [];
            return items.length > 0 && items.every(id => this.selectedItems.includes(id));
        },

        toggleMonth(month) {
            const items = this.monthItems[month] || [];
            if (this.isMonthSelected(month)) {
                this.selectedItems = this.selectedItems.filter(id => !items.includes(id));
            } else {
                items.forEach(id => {
                    if (!this.selectedItems.includes(id)) this.selectedItems.push(id);
                });
            }
        },

        async sendToContent() {
            if (!this.aicProjectId || this.selectedItems.length === 0) return;
            this.sending = true;
            this.sendMessage = '';

            const formData = new FormData();
            formData.append('_csrf_token', this.csrfToken);
            formData.append('item_ids', JSON.stringify(this.selectedItems));
            formData.append('aic_project_id', this.aicProjectId);

            try {
                const resp = await fetch('<?= url('/keyword-research/project/' . $project['id'] . '/editorial/' . $research['id'] . '/send-to-content') ?>', {
                    method: 'POST',
                    body: formData,
                });
                const data = await resp.json();

                if (data.success) {
                    this.sendMessage = data.message;
                    this.sendError = false;
                    this.selectedItems = [];
                    // Ricarica per aggiornare lo stato "inviato"
                    setTimeout(() => location.reload(), 2000);
                } else {
                    this.sendMessage = data.error;
                    this.sendError = true;
                }
            } catch (e) {
                this.sendMessage = 'Errore di connessione.';
                this.sendError = true;
            }

            this.sending = false;
        },
    };
}
</script>
