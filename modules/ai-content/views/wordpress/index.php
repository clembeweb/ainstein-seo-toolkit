<div class="space-y-6" x-data="wpSitesManager()">
    <!-- Breadcrumbs -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="<?= url('/ai-content') ?>" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    AI Content
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-2 text-slate-900 dark:text-white font-medium">WordPress</span>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Siti WordPress</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gestisci i siti WordPress collegati per la pubblicazione articoli</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <a href="<?= url('/ai-content/wordpress/download-plugin') ?>" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Scarica Plugin
            </a>
            <button @click="showAddModal = true" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Aggiungi Sito
            </button>
        </div>
    </div>

    <?php if (empty($sites)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12">
        <div class="max-w-md mx-auto text-center">
            <div class="mx-auto h-20 w-20 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mb-6">
                <svg class="h-10 w-10 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/>
                    <path d="M6.5 12L10 17l7.5-10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">Nessun sito WordPress collegato</h3>
            <p class="text-slate-500 dark:text-slate-400 mb-6">
                Collega il tuo primo sito WordPress per pubblicare automaticamente gli articoli generati con AI.
            </p>

            <!-- Setup Instructions -->
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-5 text-left mb-6">
                <h4 class="font-medium text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Come collegare un sito
                </h4>
                <ol class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <li class="flex gap-2">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400 text-xs font-medium flex items-center justify-center">1</span>
                        <span>Installa il plugin <strong>SEO Toolkit Connector</strong> sul tuo sito WordPress</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400 text-xs font-medium flex items-center justify-center">2</span>
                        <span>Attiva il plugin e vai su <strong>Impostazioni > SEO Toolkit</strong></span>
                    </li>
                    <li class="flex gap-2">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400 text-xs font-medium flex items-center justify-center">3</span>
                        <span>Genera una nuova <strong>API Key</strong> e copiala</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400 text-xs font-medium flex items-center justify-center">4</span>
                        <span>Clicca "Aggiungi Sito" qui sotto e inserisci URL e API Key</span>
                    </li>
                </ol>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="<?= url('/ai-content/wordpress/download-plugin') ?>" class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Scarica Plugin WP
                </a>
                <button @click="showAddModal = true" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Aggiungi Primo Sito
                </button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Sites Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($sites as $site): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
             x-data="{ testing: false, syncing: false }">
            <!-- Card Header -->
            <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center flex-shrink-0">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 dark:text-white"><?= e($site['name']) ?></h3>
                            <a href="<?= e($site['url']) ?>" target="_blank" class="text-sm text-slate-500 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 flex items-center gap-1">
                                <?= e(preg_replace('#^https?://#', '', $site['url'])) ?>
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <!-- Status Badge -->
                    <?php if ($site['is_active']): ?>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                        Attivo
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
                        Disattivato
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-5 space-y-4">
                <!-- Stats -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Categorie</p>
                        <p class="text-lg font-semibold text-slate-900 dark:text-white">
                            <?= isset($site['categories']) ? count($site['categories']) : 0 ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ultimo sync</p>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            <?php if ($site['last_sync_at']): ?>
                                <?= date('d/m/Y H:i', strtotime($site['last_sync_at'])) ?>
                            <?php else: ?>
                                <span class="text-slate-400">Mai</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- API Key Preview -->
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">API Key</p>
                    <code class="text-xs bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-slate-600 dark:text-slate-400">
                        <?= substr($site['api_key'], 0, 8) ?>...<?= substr($site['api_key'], -4) ?>
                    </code>
                </div>
            </div>

            <!-- Card Actions -->
            <div class="px-5 py-4 bg-slate-50 dark:bg-slate-700/30 border-t border-slate-200 dark:border-slate-700">
                <div class="flex flex-wrap gap-2">
                    <!-- Test Connection -->
                    <button @click="testConnection(<?= $site['id'] ?>, $el)"
                            :disabled="testing"
                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors disabled:opacity-50">
                        <svg x-show="!testing" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg x-show="testing" x-cloak class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Test
                    </button>

                    <!-- Sync Categories -->
                    <button @click="syncCategories(<?= $site['id'] ?>, $el)"
                            :disabled="syncing"
                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors disabled:opacity-50">
                        <svg x-show="!syncing" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg x-show="syncing" x-cloak class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sync
                    </button>

                    <!-- Toggle Active -->
                    <button @click="toggleActive(<?= $site['id'] ?>, <?= $site['is_active'] ? 'true' : 'false' ?>)"
                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium <?= $site['is_active'] ? 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 dark:hover:bg-amber-900/30' : 'text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/30' ?> transition-colors">
                        <?php if ($site['is_active']): ?>
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        Disattiva
                        <?php else: ?>
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Attiva
                        <?php endif; ?>
                    </button>

                    <!-- Remove -->
                    <button @click="confirmRemove(<?= $site['id'] ?>, '<?= e(addslashes($site['name'])) ?>')"
                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Rimuovi
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Add Site Modal -->
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showAddModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/50" @click="showAddModal = false"></div>

            <div x-show="showAddModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Aggiungi Sito WordPress</h3>
                    <button @click="showAddModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="addSite()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome sito *</label>
                            <input type="text"
                                   x-model="newSite.name"
                                   required
                                   placeholder="es. Il Mio Blog"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <p class="mt-1 text-xs text-slate-500">Un nome identificativo per il sito</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL sito WordPress *</label>
                            <input type="url"
                                   x-model="newSite.url"
                                   required
                                   placeholder="https://esempio.com"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <p class="mt-1 text-xs text-slate-500">L'URL completo del sito WordPress</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Key *</label>
                            <div class="relative">
                                <input :type="showApiKey ? 'text' : 'password'"
                                       x-model="newSite.api_key"
                                       required
                                       placeholder="Incolla l'API Key generata dal plugin"
                                       class="w-full px-3 py-2 pr-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent font-mono text-sm">
                                <button type="button"
                                        @click="showApiKey = !showApiKey"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600">
                                    <svg x-show="!showApiKey" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showApiKey" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Generata dal plugin SEO Toolkit nel pannello WP</p>
                        </div>

                        <!-- Connection Test Result -->
                        <div x-show="testResult" x-cloak class="rounded-lg p-3" :class="testResult?.success ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'">
                            <div class="flex items-center gap-2">
                                <template x-if="testResult?.success">
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="!testResult?.success">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </template>
                                <span class="text-sm font-medium" :class="testResult?.success ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'" x-text="testResult?.message"></span>
                            </div>
                            <template x-if="testResult?.success && testResult?.wp_version">
                                <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                                    WordPress <span x-text="testResult.wp_version"></span>
                                    <template x-if="testResult?.plugin_version">
                                        <span> | Plugin v<span x-text="testResult.plugin_version"></span></span>
                                    </template>
                                </p>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showAddModal = false; resetForm()" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                            Annulla
                        </button>
                        <button type="submit"
                                :disabled="adding"
                                class="px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                            <svg x-show="adding" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="adding ? 'Connessione...' : 'Test e Aggiungi'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Remove Confirmation Modal -->
    <div x-show="showRemoveModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showRemoveModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/50" @click="showRemoveModal = false"></div>

            <div x-show="showRemoveModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-sm w-full p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Rimuovi sito</h3>
                    </div>
                </div>

                <div class="space-y-3 mb-6">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Sei sicuro di voler rimuovere <strong class="text-slate-900 dark:text-white" x-text="removeSiteName"></strong>?
                    </p>
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                        <p class="text-sm text-amber-700 dark:text-amber-300 flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Gli articoli gia pubblicati su WordPress <strong>non verranno eliminati</strong> e resteranno online.</span>
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="showRemoveModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Annulla
                    </button>
                    <button type="button"
                            @click="removeSite()"
                            :disabled="removing"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition-colors disabled:opacity-50 inline-flex items-center gap-2">
                        <svg x-show="removing" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="removing ? 'Rimozione...' : 'Rimuovi'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function wpSitesManager() {
    return {
        // Add modal
        showAddModal: false,
        showApiKey: false,
        adding: false,
        testResult: null,
        newSite: {
            name: '',
            url: '',
            api_key: ''
        },

        // Remove modal
        showRemoveModal: false,
        removeSiteId: null,
        removeSiteName: '',
        removing: false,

        resetForm() {
            this.newSite = { name: '', url: '', api_key: '' };
            this.testResult = null;
            this.showApiKey = false;
        },

        async addSite() {
            if (this.adding) return;

            // Validate
            if (!this.newSite.name || !this.newSite.url || !this.newSite.api_key) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Compila tutti i campi obbligatori', type: 'error' }
                }));
                return;
            }

            this.adding = true;
            this.testResult = null;

            try {
                const response = await fetch('<?= url('/ai-content/wordpress/sites') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>',
                        ...this.newSite
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.testResult = { success: true, message: 'Connessione riuscita!' };

                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message || 'Sito aggiunto con successo!', type: 'success' }
                    }));

                    setTimeout(() => {
                        this.showAddModal = false;
                        this.resetForm();
                        window.location.reload();
                    }, 1000);
                } else {
                    this.testResult = { success: false, message: data.error || 'Errore di connessione' };
                }
            } catch (error) {
                this.testResult = { success: false, message: 'Errore di rete. Riprova.' };
            } finally {
                this.adding = false;
            }
        },

        async testConnection(siteId, buttonEl) {
            const card = buttonEl.closest('[x-data]');
            const scope = Alpine.$data(card);
            scope.testing = true;

            try {
                const response = await fetch('<?= url('/ai-content/wordpress/sites') ?>/' + siteId + '/test', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _token: '<?= csrf_token() ?>' })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: {
                            message: 'Connessione OK' + (data.wp_version ? ' - WP ' + data.wp_version : ''),
                            type: 'success'
                        }
                    }));
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Test fallito', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                scope.testing = false;
            }
        },

        async syncCategories(siteId, buttonEl) {
            const card = buttonEl.closest('[x-data]');
            const scope = Alpine.$data(card);
            scope.syncing = true;

            try {
                const response = await fetch('<?= url('/ai-content/wordpress/sites') ?>/' + siteId + '/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _token: '<?= csrf_token() ?>' })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: {
                            message: 'Sincronizzate ' + data.categories_count + ' categorie',
                            type: 'success'
                        }
                    }));
                    // Reload to show updated count
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Sync fallito', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                scope.syncing = false;
            }
        },

        async toggleActive(siteId, currentlyActive) {
            try {
                const response = await fetch('<?= url('/ai-content/wordpress/sites') ?>/' + siteId + '/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _token: '<?= csrf_token() ?>' })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'success' }
                    }));
                    window.location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            }
        },

        confirmRemove(siteId, siteName) {
            this.removeSiteId = siteId;
            this.removeSiteName = siteName;
            this.showRemoveModal = true;
        },

        async removeSite() {
            if (this.removing || !this.removeSiteId) return;

            this.removing = true;

            try {
                const response = await fetch('<?= url('/ai-content/wordpress/sites') ?>/' + this.removeSiteId + '/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _token: '<?= csrf_token() ?>' })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message || 'Sito rimosso', type: 'success' }
                    }));
                    this.showRemoveModal = false;
                    window.location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore durante la rimozione', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.removing = false;
            }
        }
    }
}
</script>
