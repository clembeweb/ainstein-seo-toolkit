<div class="space-y-6" x-data="connectorsManager()">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="<?= url('/content-creator') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Connettori CMS</h1>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 ml-8">Gestisci le connessioni ai tuoi CMS per la sincronizzazione dei contenuti</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/content-creator/connectors/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Connettore
            </a>
        </div>
    </div>

    <?php if (empty($connectors)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun connettore configurato</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Collega il tuo CMS (WordPress, Shopify, PrestaShop, Magento) per importare URL e pubblicare direttamente i contenuti generati.
        </p>
        <a href="<?= url('/content-creator/connectors/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crea primo connettore
        </a>
    </div>
    <?php else: ?>

    <!-- Connectors List -->
    <div class="space-y-4">
        <?php foreach ($connectors as $conn):
            $typeConfig = [
                'wordpress' => ['label' => 'WordPress', 'color' => 'blue', 'icon' => 'W'],
                'shopify' => ['label' => 'Shopify', 'color' => 'green', 'icon' => 'S'],
                'prestashop' => ['label' => 'PrestaShop', 'color' => 'pink', 'icon' => 'P'],
                'magento' => ['label' => 'Magento', 'color' => 'orange', 'icon' => 'M'],
                'custom_api' => ['label' => 'Custom API', 'color' => 'slate', 'icon' => 'A'],
            ];
            $tc = $typeConfig[$conn['type']] ?? $typeConfig['custom_api'];
            $config = json_decode($conn['config'] ?? '{}', true);
            $connUrl = $config['url'] ?? $config['store_url'] ?? '';
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
             x-data="{ deleting: false }"
             id="connector-<?= $conn['id'] ?>">
            <div class="p-5">
                <div class="flex items-center justify-between">
                    <!-- Left: Info -->
                    <div class="flex items-center gap-4 min-w-0 flex-1">
                        <!-- Type Icon -->
                        <div class="flex-shrink-0 h-12 w-12 rounded-lg bg-<?= $tc['color'] ?>-100 dark:bg-<?= $tc['color'] ?>-900/50 flex items-center justify-center">
                            <span class="text-lg font-bold text-<?= $tc['color'] ?>-600 dark:text-<?= $tc['color'] ?>-400"><?= $tc['icon'] ?></span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-base font-semibold text-slate-900 dark:text-white truncate">
                                    <?= e($conn['name']) ?>
                                </h3>
                                <span class="flex-shrink-0 px-2 py-0.5 rounded text-xs font-medium bg-<?= $tc['color'] ?>-100 text-<?= $tc['color'] ?>-700 dark:bg-<?= $tc['color'] ?>-900/50 dark:text-<?= $tc['color'] ?>-300">
                                    <?= $tc['label'] ?>
                                </span>
                            </div>

                            <div class="flex items-center gap-4 mt-1">
                                <?php if ($connUrl): ?>
                                <span class="text-sm text-slate-500 dark:text-slate-400 truncate max-w-xs">
                                    <?= e($connUrl) ?>
                                </span>
                                <?php endif; ?>

                                <!-- Test Status -->
                                <?php if (!empty($conn['last_test_at'])): ?>
                                    <?php if ($conn['last_test_status'] === 'success'): ?>
                                    <span class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Test OK (<?= date('d/m H:i', strtotime($conn['last_test_at'])) ?>)
                                    </span>
                                    <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Test fallito (<?= date('d/m H:i', strtotime($conn['last_test_at'])) ?>)
                                    </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                <span class="text-xs text-slate-400 dark:text-slate-500 italic">Mai testato</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                        <!-- Active Toggle -->
                        <button @click="toggleConnector(<?= $conn['id'] ?>, $el)"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 <?= $conn['is_active'] ? 'bg-primary-600' : 'bg-slate-300 dark:bg-slate-600' ?>"
                                role="switch"
                                :aria-checked="<?= $conn['is_active'] ? 'true' : 'false' ?>"
                                title="<?= $conn['is_active'] ? 'Attivo' : 'Disattivato' ?>">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out <?= $conn['is_active'] ? 'translate-x-5' : 'translate-x-0' ?>"></span>
                        </button>

                        <!-- Test Connection -->
                        <button @click="testConnector(<?= $conn['id'] ?>, $el)"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                                title="Testa connessione">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Test</span>
                        </button>

                        <!-- Delete -->
                        <button @click="deleting = true"
                                x-show="!deleting"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                title="Elimina connettore">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>

                        <!-- Delete Confirmation -->
                        <div x-show="deleting" x-cloak class="flex items-center gap-2">
                            <span class="text-sm text-red-600 dark:text-red-400">Confermi?</span>
                            <button @click="deleteConnector(<?= $conn['id'] ?>)"
                                    class="px-2 py-1 rounded text-xs font-medium bg-red-600 text-white hover:bg-red-700 transition-colors">
                                Elimina
                            </button>
                            <button @click="deleting = false"
                                    class="px-2 py-1 rounded text-xs font-medium border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                Annulla
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Notification Toast -->
    <div x-show="notification.show" x-cloak
         x-transition:enter="transform ease-out duration-300 transition"
         x-transition:enter-start="translate-y-2 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-4 right-4 z-50 max-w-sm">
        <div :class="notification.type === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/50 border-emerald-200 dark:border-emerald-800' : 'bg-red-50 dark:bg-red-900/50 border-red-200 dark:border-red-800'"
             class="rounded-lg border p-4 shadow-lg">
            <div class="flex items-center gap-3">
                <template x-if="notification.type === 'success'">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </template>
                <template x-if="notification.type === 'error'">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </template>
                <p :class="notification.type === 'success' ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200'"
                   class="text-sm font-medium" x-text="notification.message"></p>
            </div>
        </div>
    </div>
</div>

<script>
function connectorsManager() {
    return {
        notification: { show: false, type: 'success', message: '' },

        showNotification(type, message) {
            this.notification = { show: true, type, message };
            setTimeout(() => { this.notification.show = false; }, 4000);
        },

        async testConnector(id, el) {
            const btn = el.closest('button');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span>Testing...</span>
            `;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const resp = await fetch(`<?= url('/content-creator/connectors') ?>/${id}/test`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await resp.json();

                this.showNotification(
                    data.success ? 'success' : 'error',
                    data.message || (data.success ? 'Connessione riuscita!' : 'Connessione fallita')
                );

                // Reload page to update test status
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (err) {
                this.showNotification('error', 'Errore di connessione');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        },

        async toggleConnector(id, el) {
            const toggle = el.closest('button');

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const resp = await fetch(`<?= url('/content-creator/connectors') ?>/${id}/toggle`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await resp.json();

                if (data.success) {
                    // Update toggle visual
                    if (data.is_active) {
                        toggle.classList.remove('bg-slate-300', 'dark:bg-slate-600');
                        toggle.classList.add('bg-primary-600');
                        toggle.querySelector('span').classList.remove('translate-x-0');
                        toggle.querySelector('span').classList.add('translate-x-5');
                        toggle.title = 'Attivo';
                    } else {
                        toggle.classList.remove('bg-primary-600');
                        toggle.classList.add('bg-slate-300', 'dark:bg-slate-600');
                        toggle.querySelector('span').classList.remove('translate-x-5');
                        toggle.querySelector('span').classList.add('translate-x-0');
                        toggle.title = 'Disattivato';
                    }

                    this.showNotification('success', data.message);
                } else {
                    this.showNotification('error', data.message || 'Errore');
                }
            } catch (err) {
                this.showNotification('error', 'Errore di connessione');
            }
        },

        async deleteConnector(id) {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const resp = await fetch(`<?= url('/content-creator/connectors') ?>/${id}/delete`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await resp.json();

                if (data.success) {
                    const card = document.getElementById(`connector-${id}`);
                    if (card) {
                        card.style.transition = 'opacity 0.3s, transform 0.3s';
                        card.style.opacity = '0';
                        card.style.transform = 'translateX(20px)';
                        setTimeout(() => card.remove(), 300);
                    }
                    this.showNotification('success', data.message || 'Connettore eliminato');
                } else {
                    this.showNotification('error', data.message || 'Errore eliminazione');
                }
            } catch (err) {
                this.showNotification('error', 'Errore di connessione');
            }
        }
    };
}
</script>
