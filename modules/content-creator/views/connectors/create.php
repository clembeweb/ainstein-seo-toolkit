<div class="space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/content-creator') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Content Creator</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/content-creator/connectors') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Connettori</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white">Nuovo Connettore</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nuovo Connettore</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Collega un CMS per importare URL e pubblicare contenuti</p>
    </div>

    <!-- Form -->
    <div class="max-w-2xl">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700" x-data="{ type: 'wordpress' }">
            <form action="<?= url('/content-creator/connectors') ?>" method="POST" class="p-6 space-y-6">
                <?= csrf_field() ?>

                <!-- Nome -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Nome connettore <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required maxlength="255"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                           placeholder="Es. WordPress Blog Aziendale">
                </div>

                <!-- Tipo -->
                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Tipo CMS <span class="text-red-500">*</span>
                    </label>
                    <select id="type" name="type" x-model="type"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="wordpress">WordPress</option>
                        <option value="shopify">Shopify</option>
                        <option value="prestashop">PrestaShop</option>
                        <option value="magento">Magento</option>
                        <option value="custom_api">Custom API</option>
                    </select>
                </div>

                <!-- Divider -->
                <div class="border-t border-slate-200 dark:border-slate-700 pt-2">
                    <h3 class="text-sm font-medium text-slate-900 dark:text-white">Configurazione connessione</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Inserisci le credenziali per il tipo di CMS selezionato</p>
                </div>

                <!-- ===================== -->
                <!-- WordPress Fields     -->
                <!-- ===================== -->
                <div x-show="type === 'wordpress'" x-cloak class="space-y-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                <p class="font-medium mb-1">Plugin richiesto</p>
                                <p>Installa il plugin <strong>SEO Toolkit Connector</strong> sul sito WordPress. L'API Key viene generata automaticamente dal plugin.</p>
                                <a href="<?= url('/content-creator/connectors/download-plugin/wordpress') ?>"
                                   class="inline-flex items-center mt-2 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Scarica Plugin WordPress
                                </a>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="wp_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            URL del sito <span class="text-red-500">*</span>
                        </label>
                        <input type="url" id="wp_url" :name="type === 'wordpress' ? 'wp_url' : null" :required="type === 'wordpress'"
                               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="https://www.example.com">
                    </div>
                    <div>
                        <label for="wp_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            API Key Plugin <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="wp_api_key" :name="type === 'wordpress' ? 'wp_api_key' : null" :required="type === 'wordpress'"
                               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="stk_xxxxxxxxxxxxxxxxxxxxxxxxxx">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Trova l'API Key in Impostazioni > SEO Toolkit nella dashboard WordPress
                        </p>
                    </div>
                </div>

                <!-- ===================== -->
                <!-- Shopify Fields       -->
                <!-- ===================== -->
                <div x-show="type === 'shopify'" x-cloak class="space-y-4">
                    <div>
                        <label for="shopify_store_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            URL negozio <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="shopify_store_url" :name="type === 'shopify' ? 'shopify_store_url' : null" :required="type === 'shopify'"
                               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="myshop.myshopify.com">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Inserisci il dominio .myshopify.com del negozio
                        </p>
                    </div>
                    <div>
                        <label for="shopify_access_token" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Access Token <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="shopify_access_token" :name="type === 'shopify' ? 'shopify_access_token' : null" :required="type === 'shopify'"
                               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="shpat_xxxxxxxxxxxxxxxxx">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Genera da Settings > Apps > Develop apps nella dashboard Shopify
                        </p>
                    </div>
                </div>

                <!-- ===================== -->
                <!-- PrestaShop Fields    -->
                <!-- ===================== -->
                <div x-show="type === 'prestashop'" x-cloak class="space-y-4">
                    <div>
                        <label for="ps_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            URL negozio <span class="text-red-500">*</span>
                        </label>
                        <input type="url" id="ps_url" :name="type === 'prestashop' ? 'ps_url' : null" :required="type === 'prestashop'"
                               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="https://www.myshop.com">
                    </div>
                    <div>
                        <label for="ps_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            API Key Webservice <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="ps_api_key" :name="type === 'prestashop' ? 'ps_api_key' : null" :required="type === 'prestashop'"
                               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Genera da Parametri Avanzati > Webservice nel Back Office PrestaShop
                        </p>
                    </div>
                </div>

                <!-- ===================== -->
                <!-- Magento Fields       -->
                <!-- ===================== -->
                <div x-show="type === 'magento'" x-cloak class="space-y-4">
                    <div>
                        <label for="magento_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            URL Magento <span class="text-red-500">*</span>
                        </label>
                        <input type="url" id="magento_url" :name="type === 'magento' ? 'magento_url' : null" :required="type === 'magento'"
                               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="https://www.myshop.com">
                    </div>
                    <div>
                        <label for="magento_access_token" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Access Token <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="magento_access_token" :name="type === 'magento' ? 'magento_access_token' : null" :required="type === 'magento'"
                               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Genera da System > Integrations nel pannello Admin Magento
                        </p>
                    </div>
                </div>

                <!-- ===================== -->
                <!-- Custom API Fields    -->
                <!-- ===================== -->
                <div x-show="type === 'custom_api'" x-cloak class="space-y-4">
                    <div>
                        <label for="custom_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            URL API Base <span class="text-red-500">*</span>
                        </label>
                        <input type="url" id="custom_url" :name="type === 'custom_api' ? 'custom_url' : null" :required="type === 'custom_api'"
                               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="https://api.example.com/v1">
                    </div>
                    <div>
                        <label for="custom_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            API Key
                        </label>
                        <input type="password" id="custom_api_key" :name="type === 'custom_api' ? 'custom_api_key' : null"
                               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="Opzionale">
                    </div>
                    <div>
                        <label for="custom_headers_json" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Headers custom (JSON)
                        </label>
                        <textarea id="custom_headers_json" :name="type === 'custom_api' ? 'custom_headers_json' : null" rows="3"
                                  class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                                  placeholder='{"Authorization": "Bearer xxx", "X-Custom-Header": "value"}'></textarea>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Opzionale. Formato JSON con chiave-valore degli headers HTTP aggiuntivi.
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <a href="<?= url('/content-creator/connectors') ?>"
                       class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                        Annulla
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Crea Connettore
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Box -->
    <div class="max-w-2xl">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-sm font-medium text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Guide per la configurazione
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-600 dark:text-slate-400">
                <div>
                    <p class="font-medium text-slate-700 dark:text-slate-300">WordPress</p>
                    <p class="mt-1">Installa il plugin SEO Toolkit Connector. L'API Key viene generata automaticamente. Trova la chiave in Impostazioni > SEO Toolkit.</p>
                </div>
                <div>
                    <p class="font-medium text-slate-700 dark:text-slate-300">Shopify</p>
                    <p class="mt-1">Crea una Custom App con permessi di lettura/scrittura su Products e Pages.</p>
                </div>
                <div>
                    <p class="font-medium text-slate-700 dark:text-slate-300">PrestaShop</p>
                    <p class="mt-1">Abilita il Webservice e genera una chiave API con permessi su products e categories.</p>
                </div>
                <div>
                    <p class="font-medium text-slate-700 dark:text-slate-300">Magento</p>
                    <p class="mt-1">Crea una Integration con accesso al catalogo prodotti e categorie.</p>
                </div>
            </div>
        </div>
    </div>
</div>
