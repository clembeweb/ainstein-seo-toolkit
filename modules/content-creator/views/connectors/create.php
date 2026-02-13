<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="<?= url('/content-creator/connectors') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nuovo Connettore</h1>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700" x-data="{ type: 'wordpress' }">
        <form action="<?= url('/content-creator/connectors') ?>" method="POST" class="p-6 space-y-6">
            <?= csrf_field() ?>

            <!-- Nome -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Nome connettore <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" required maxlength="255"
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                       placeholder="Es. WordPress Blog Aziendale">
            </div>

            <!-- Tipo -->
            <div>
                <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Tipo CMS <span class="text-red-500">*</span>
                </label>
                <select id="type" name="type" x-model="type"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
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
                <div>
                    <label for="wp_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        URL del sito <span class="text-red-500">*</span>
                    </label>
                    <input type="url" id="wp_url" name="wp_url"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                           placeholder="https://www.example.com">
                </div>
                <div>
                    <label for="wp_username" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Username WordPress <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="wp_username" name="wp_username"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                           placeholder="admin">
                </div>
                <div>
                    <label for="wp_application_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Application Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="wp_application_password" name="wp_application_password"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                           placeholder="xxxx xxxx xxxx xxxx xxxx xxxx">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Genera da Utenti > Profilo > Password Applicazione nella dashboard WordPress
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
                    <input type="text" id="shopify_store_url" name="shopify_store_url"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                           placeholder="myshop.myshopify.com">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Inserisci il dominio .myshopify.com del negozio
                    </p>
                </div>
                <div>
                    <label for="shopify_access_token" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Access Token <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="shopify_access_token" name="shopify_access_token"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
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
                    <input type="url" id="ps_url" name="ps_url"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                           placeholder="https://www.myshop.com">
                </div>
                <div>
                    <label for="ps_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        API Key Webservice <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="ps_api_key" name="ps_api_key"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
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
                    <input type="url" id="magento_url" name="magento_url"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                           placeholder="https://www.myshop.com">
                </div>
                <div>
                    <label for="magento_access_token" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Access Token <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="magento_access_token" name="magento_access_token"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
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
                    <input type="url" id="custom_url" name="custom_url"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                           placeholder="https://api.example.com/v1">
                </div>
                <div>
                    <label for="custom_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        API Key
                    </label>
                    <input type="password" id="custom_api_key" name="custom_api_key"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                           placeholder="Opzionale">
                </div>
                <div>
                    <label for="custom_headers_json" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Headers custom (JSON)
                    </label>
                    <textarea id="custom_headers_json" name="custom_headers_json" rows="3"
                              class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 font-mono text-sm"
                              placeholder='{"Authorization": "Bearer xxx", "X-Custom-Header": "value"}'></textarea>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Opzionale. Formato JSON con chiave-valore degli headers HTTP aggiuntivi.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/content-creator/connectors') ?>"
                   class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    Annulla
                </a>
                <button type="submit"
                        class="px-6 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                    Crea Connettore
                </button>
            </div>
        </form>
    </div>

    <!-- Info Box -->
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
                <p class="mt-1">Richiede REST API attiva (default in WP 4.7+). Crea una Application Password dal profilo utente.</p>
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
