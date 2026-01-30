<?php $currentPage = 'queue'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="queueManager()" @update-item.window="updateItem($event.detail)">

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Totale</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($stats['pending'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">In Attesa</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($stats['completed'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Completati</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= number_format($stats['errors'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Errori</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="border-b border-slate-200 dark:border-slate-700">
            <nav class="flex -mb-px">
                <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto/queue') ?>"
                   class="px-6 py-3 text-sm font-medium border-b-2 <?= !$statusFilter ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' ?>">
                    Tutti
                </a>
                <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto/queue?status=pending') ?>"
                   class="px-6 py-3 text-sm font-medium border-b-2 <?= $statusFilter === 'pending' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' ?>">
                    In Attesa
                </a>
                <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto/queue?status=completed') ?>"
                   class="px-6 py-3 text-sm font-medium border-b-2 <?= $statusFilter === 'completed' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' ?>">
                    Completati
                </a>
                <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto/queue?status=error') ?>"
                   class="px-6 py-3 text-sm font-medium border-b-2 <?= $statusFilter === 'error' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' ?>">
                    Errori
                </a>
            </nav>
        </div>

        <?php if (empty($items)): ?>
        <!-- Empty State -->
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <p class="mt-4 text-slate-500 dark:text-slate-400">Nessun elemento trovato</p>
        </div>
        <?php else: ?>
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fonti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Schedulato</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($items as $item): ?>
                    <?php
                        $statusClass = match($item['status']) {
                            'pending' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                            'processing' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                            'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                            'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                            default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
                        };
                        $statusLabel = match($item['status']) {
                            'pending' => 'In Attesa',
                            'processing' => 'In Elaborazione',
                            'completed' => 'Completato',
                            'error' => 'Errore',
                            default => $item['status']
                        };
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($item['keyword']) ?></p>
                            <?php if ($item['status'] === 'error' && !empty($item['error_message'])): ?>
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1"><?= e($item['error_message']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                                <?= $statusLabel ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($item['status'] === 'pending'): ?>
                            <div x-data="{ editing: false, value: '<?= $item['sources_count'] ?? 3 ?>', originalValue: '<?= $item['sources_count'] ?? 3 ?>' }">
                                <span x-show="!editing"
                                      @click="editing = true"
                                      class="cursor-pointer hover:text-primary-600 dark:hover:text-primary-400 text-sm text-slate-700 dark:text-slate-300">
                                    <span x-text="value"></span> fonti
                                </span>
                                <select x-show="editing"
                                        x-model="value"
                                        @change="if(value !== originalValue) { $dispatch('update-item', { id: <?= $item['id'] ?>, field: 'sources_count', value: value }); originalValue = value; } editing = false"
                                        @blur="editing = false"
                                        class="text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded px-2 py-1 w-20">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                            <?php else: ?>
                            <span class="text-sm text-slate-400 dark:text-slate-500"><?= $item['sources_count'] ?? 3 ?> fonti</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($item['status'] === 'pending'): ?>
                                <?php if (empty($item['scheduled_at'])): ?>
                                <!-- Non pianificato: mostra bottone Pianifica -->
                                <div x-data="{
                                    editing: false,
                                    value: '',
                                    saved: false,
                                    get displayDate() {
                                        if (!this.value) return '';
                                        const d = new Date(this.value);
                                        return d.toLocaleDateString('it-IT', {day:'2-digit',month:'2-digit',year:'numeric'}) + ' ' + d.toLocaleTimeString('it-IT', {hour:'2-digit',minute:'2-digit'});
                                    }
                                }">
                                    <!-- Bottone Pianifica (se non ancora salvato) -->
                                    <button x-show="!editing && !saved"
                                            @click="editing = true; $nextTick(() => $refs.dateInput<?= $item['id'] ?>.focus())"
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-400">
                                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Pianifica
                                    </button>
                                    <!-- Data salvata (mostra dopo primo salvataggio) -->
                                    <span x-show="!editing && saved"
                                          @click="editing = true; $nextTick(() => $refs.dateInput<?= $item['id'] ?>.focus())"
                                          class="cursor-pointer hover:text-primary-600 dark:hover:text-primary-400 text-sm text-slate-700 dark:text-slate-300 inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span x-text="displayDate"></span>
                                    </span>
                                    <!-- Form editing -->
                                    <div x-show="editing" class="flex items-center gap-1">
                                        <input x-ref="dateInput<?= $item['id'] ?>"
                                               type="datetime-local"
                                               x-model="value"
                                               class="text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded px-2 py-1">
                                        <button @click="if(value) { $dispatch('update-item', { id: <?= $item['id'] ?>, field: 'scheduled_at', value: value }); saved = true; } editing = false"
                                                class="p-1 text-emerald-600 hover:text-emerald-700">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                        <button @click="editing = false; if(!saved) value = ''"
                                                class="p-1 text-slate-400 hover:text-slate-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <?php else: ?>
                                <!-- Già pianificato: mostra data editabile -->
                                <div x-data="{
                                    editing: false,
                                    value: '<?= date('Y-m-d\TH:i', strtotime($item['scheduled_at'])) ?>',
                                    originalValue: '<?= date('Y-m-d\TH:i', strtotime($item['scheduled_at'])) ?>',
                                    get displayDate() {
                                        if (!this.value) return '';
                                        const d = new Date(this.value);
                                        return d.toLocaleDateString('it-IT', {day:'2-digit',month:'2-digit',year:'numeric'}) + ' ' + d.toLocaleTimeString('it-IT', {hour:'2-digit',minute:'2-digit'});
                                    }
                                }">
                                    <span x-show="!editing"
                                          @click="editing = true; $nextTick(() => $refs.editInput<?= $item['id'] ?>.focus())"
                                          class="cursor-pointer hover:text-primary-600 dark:hover:text-primary-400 text-sm text-slate-700 dark:text-slate-300 inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span x-text="displayDate"></span>
                                    </span>
                                    <div x-show="editing" class="flex items-center gap-1">
                                        <input x-ref="editInput<?= $item['id'] ?>"
                                               type="datetime-local"
                                               x-model="value"
                                               class="text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded px-2 py-1">
                                        <button @click="if(value && value !== originalValue) { $dispatch('update-item', { id: <?= $item['id'] ?>, field: 'scheduled_at', value: value }); originalValue = value; } editing = false"
                                                class="p-1 text-emerald-600 hover:text-emerald-700">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                        <button @click="editing = false; value = originalValue"
                                                class="p-1 text-slate-400 hover:text-slate-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                            <span class="text-sm text-slate-400 dark:text-slate-500"><?= $item['scheduled_at'] ? date('d/m/Y H:i', strtotime($item['scheduled_at'])) : '-' ?></span>
                            <?php if ($item['completed_at']): ?>
                            <br><span class="text-xs text-emerald-600 dark:text-emerald-400">Completato: <?= date('H:i', strtotime($item['completed_at'])) ?></span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <?php if ($item['status'] === 'completed' && !empty($item['article_id'])): ?>
                                <a href="<?= url('/ai-content/articles/' . $item['article_id']) ?>"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-400 dark:hover:bg-primary-900/50 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Vedi Articolo
                                </a>
                                <?php elseif ($item['status'] === 'error'): ?>
                                <form action="<?= url('/ai-content/projects/' . $project['id'] . '/auto/queue/' . $item['id'] . '/retry') ?>" method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Riprova
                                    </button>
                                </form>
                                <?php elseif ($item['status'] === 'pending'): ?>
                                <button @click="confirmDelete(<?= $item['id'] ?>, '<?= e(addslashes($item['keyword'])) ?>')"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Elimina
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Footer Actions -->
        <?php if (($stats['pending'] ?? 0) > 0): ?>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <?= $stats['pending'] ?> keyword in attesa di elaborazione
                </p>
                <button @click="confirmClearAll()"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Svuota Coda
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Delete Single Item Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showDeleteModal" class="fixed inset-0 bg-slate-900/50" @click="showDeleteModal = false"></div>
            <div x-show="showDeleteModal" x-transition class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Sei sicuro di voler rimuovere "<span x-text="deleteKeyword" class="font-medium"></span>" dalla coda?
                </p>
                <form :action="deleteUrl" method="POST" class="mt-6">
                    <?= csrf_field() ?>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showDeleteModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                            Annulla
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700">
                            Elimina
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Clear All Modal -->
    <div x-show="showClearModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showClearModal" class="fixed inset-0 bg-slate-900/50" @click="showClearModal = false"></div>
            <div x-show="showClearModal" x-transition class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Svuota Coda</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Sei sicuro di voler rimuovere tutte le keyword in attesa dalla coda? Questa azione non può essere annullata.
                </p>
                <form action="<?= url('/ai-content/projects/' . $project['id'] . '/auto/queue/clear') ?>" method="POST" class="mt-6">
                    <?= csrf_field() ?>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showClearModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                            Annulla
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700">
                            Svuota Coda
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function queueManager() {
    return {
        showDeleteModal: false,
        showClearModal: false,
        deleteId: null,
        deleteKeyword: '',
        deleteUrl: '',

        confirmDelete(id, keyword) {
            this.deleteId = id;
            this.deleteKeyword = keyword;
            this.deleteUrl = '<?= url('/ai-content/projects/' . $project['id'] . '/auto/queue') ?>/' + id + '/delete';
            this.showDeleteModal = true;
        },

        confirmClearAll() {
            this.showClearModal = true;
        },

        async updateItem(detail) {
            const formData = new FormData();
            formData.append('_csrf_token', '<?= csrf_token() ?>');
            formData.append(detail.field, detail.value);

            try {
                const response = await fetch(
                    '<?= url("/ai-content/projects/{$project['id']}/auto/queue") ?>/' + detail.id + '/update',
                    { method: 'POST', body: formData }
                );
                const data = await response.json();

                if (data.success) {
                    if (window.dispatchEvent) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: 'Aggiornato!', type: 'success' }
                        }));
                    }
                } else {
                    alert(data.error || 'Errore durante l\'aggiornamento');
                }
            } catch (e) {
                alert('Errore di connessione');
            }
        }
    }
}
</script>
