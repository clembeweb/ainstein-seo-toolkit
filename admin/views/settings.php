<?php
use Services\AiService;

// Carica dati AI
$aiStatus = AiService::getConfigurationStatus();
$providers = AiService::getProviders();
$allModels = AiService::getAvailableModels();
$currentProvider = $settings['ai_provider']['value'] ?? 'anthropic';
$currentModel = $settings['ai_model']['value'] ?? 'claude-sonnet-4-20250514';
$fallbackEnabled = ($settings['ai_fallback_enabled']['value'] ?? '1') === '1';
?>
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impostazioni</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configura API keys, costi crediti e altre impostazioni di sistema</p>
    </div>

    <form action="<?= url('/admin/settings') ?>" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Configurazione AI -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Configurazione AI
                </h3>
            </div>
            <div class="p-6">
                <!-- Status Box -->
                <?php
                $primaryConfigured = $aiStatus[$currentProvider]['configured'] ?? false;
                $fallbackProvider = $currentProvider === 'anthropic' ? 'openai' : 'anthropic';
                $fallbackConfigured = $aiStatus[$fallbackProvider]['configured'] ?? false;
                ?>
                <div class="mb-6 p-4 rounded-lg <?= $primaryConfigured ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' ?>">
                    <div class="flex items-center gap-2">
                        <?php if ($primaryConfigured): ?>
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-green-800 dark:text-green-200 font-medium">
                                AI Configurata: <?= e($providers[$currentProvider]) ?> - <?= e($currentModel) ?>
                            </span>
                        <?php else: ?>
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span class="text-yellow-800 dark:text-yellow-200 font-medium">
                                API Key non configurata per <?= e($providers[$currentProvider]) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($fallbackEnabled && $fallbackConfigured): ?>
                        <div class="mt-2 text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            Fallback attivo: <?= e($providers[$fallbackProvider]) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Provider e Model Selection -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="ai_provider" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Provider AI Primario</label>
                        <select name="ai_provider" id="ai_provider"
                                class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <?php foreach ($providers as $providerId => $providerName): ?>
                                <option value="<?= e($providerId) ?>" <?= $currentProvider === $providerId ? 'selected' : '' ?>>
                                    <?= e($providerName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="ai_model" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Modello</label>
                        <select name="ai_model" id="ai_model"
                                class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <?php foreach ($allModels[$currentProvider]['models'] as $modelId => $modelInfo): ?>
                                <option value="<?= e($modelId) ?>" <?= $currentModel === $modelId ? 'selected' : '' ?>>
                                    <?= e($modelInfo['name']) ?> (in: $<?= $modelInfo['input_cost'] ?>/1K, out: $<?= $modelInfo['output_cost'] ?>/1K)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Fallback Toggle -->
                <div class="mb-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="ai_fallback_enabled" value="0">
                        <input type="checkbox" name="ai_fallback_enabled" value="1"
                               <?= $fallbackEnabled ? 'checked' : '' ?>
                               class="w-4 h-4 text-purple-600 border-slate-300 dark:border-slate-600 rounded focus:ring-purple-500 dark:bg-slate-700">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Abilita fallback automatico
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            (se il provider primario fallisce, usa l'altro)
                        </span>
                    </label>
                </div>

                <!-- API Keys -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Anthropic API Key -->
                    <div>
                        <label for="anthropic_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Anthropic API Key
                            <?php if ($aiStatus['anthropic']['configured']): ?>
                                <span class="text-green-600 dark:text-green-400 text-xs ml-2">Configurata</span>
                            <?php endif; ?>
                        </label>
                        <div class="relative">
                            <input type="password" name="anthropic_api_key" id="anthropic_api_key"
                                   value="<?= e($settings['anthropic_api_key']['value'] ?? '') ?>"
                                   placeholder="sk-ant-api03-..."
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <button type="button" onclick="togglePassword('anthropic_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline">
                                Ottieni API Key da Anthropic Console
                            </a>
                        </p>
                    </div>

                    <!-- OpenAI API Key -->
                    <div>
                        <label for="openai_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            OpenAI API Key
                            <?php if ($aiStatus['openai']['configured']): ?>
                                <span class="text-green-600 dark:text-green-400 text-xs ml-2">Configurata</span>
                            <?php endif; ?>
                        </label>
                        <div class="relative">
                            <input type="password" name="openai_api_key" id="openai_api_key"
                                   value="<?= e($settings['openai_api_key']['value'] ?? '') ?>"
                                   placeholder="sk-proj-..."
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <button type="button" onclick="togglePassword('openai_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            <a href="https://platform.openai.com/api-keys" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline">
                                Ottieni API Key da OpenAI Platform
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- General Settings -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Generali
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="site_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome sito</label>
                    <input type="text" name="site_name" id="site_name" value="<?= e($settings['site_name']['value'] ?? 'SEO Toolkit') ?>"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="free_credits" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Crediti free tier</label>
                    <input type="number" name="free_credits" id="free_credits" value="<?= e($settings['free_credits']['value'] ?? '50') ?>"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Crediti assegnati ai nuovi utenti</p>
                </div>
            </div>
        </div>

        <!-- Stripe -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Stripe (Pagamenti)
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="stripe_public_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Public Key</label>
                    <input type="text" name="stripe_public_key" id="stripe_public_key"
                           value="<?= e($settings['stripe_public_key']['value'] ?? '') ?>"
                           placeholder="pk_..."
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="stripe_secret_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Secret Key</label>
                    <div class="relative">
                        <input type="password" name="stripe_secret_key" id="stripe_secret_key"
                               value="<?= e($settings['stripe_secret_key']['value'] ?? '') ?>"
                               placeholder="sk_..."
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <button type="button" onclick="togglePassword('stripe_secret_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Credit Costs -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472a4.265 4.265 0 01.264-.521z"/>
                    </svg>
                    Costi operazioni (crediti)
                </h3>
            </div>
            <div class="p-6 grid grid-cols-2 sm:grid-cols-3 gap-6">
                <div>
                    <label for="cost_scrape_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Scrape URL</label>
                    <input type="number" step="0.1" name="cost_scrape_url" id="cost_scrape_url"
                           value="<?= e($settings['cost_scrape_url']['value'] ?? '0.1') ?>"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="cost_ai_analysis_small" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">AI Small (&lt;1k)</label>
                    <input type="number" step="0.1" name="cost_ai_analysis_small" id="cost_ai_analysis_small"
                           value="<?= e($settings['cost_ai_analysis_small']['value'] ?? '1') ?>"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="cost_ai_analysis_medium" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">AI Medium (1-5k)</label>
                    <input type="number" step="0.1" name="cost_ai_analysis_medium" id="cost_ai_analysis_medium"
                           value="<?= e($settings['cost_ai_analysis_medium']['value'] ?? '2') ?>"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="cost_ai_analysis_large" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">AI Large (&gt;5k)</label>
                    <input type="number" step="0.1" name="cost_ai_analysis_large" id="cost_ai_analysis_large"
                           value="<?= e($settings['cost_ai_analysis_large']['value'] ?? '5') ?>"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="cost_export_csv" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Export CSV</label>
                    <input type="number" step="0.1" name="cost_export_csv" id="cost_export_csv"
                           value="<?= e($settings['cost_export_csv']['value'] ?? '0') ?>"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="cost_export_excel" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Export Excel</label>
                    <input type="number" step="0.1" name="cost_export_excel" id="cost_export_excel"
                           value="<?= e($settings['cost_export_excel']['value'] ?? '0.5') ?>"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>
        </div>

        <!-- Google Search Console -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google OAuth (GSC / Analytics)
                </h3>
            </div>
            <div class="p-6 space-y-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <p class="font-medium mb-1">Come configurare:</p>
                            <ol class="list-decimal list-inside space-y-1 text-blue-600 dark:text-blue-400">
                                <li>Vai su <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="underline hover:no-underline">Google Cloud Console</a></li>
                                <li>Crea un nuovo progetto o seleziona esistente</li>
                                <li>Abilita "Search Console API" e/o "Google Analytics Data API"</li>
                                <li>Crea credenziali OAuth 2.0 (Web application)</li>
                                <li>Aggiungi il Redirect URI mostrato sotto nelle origini autorizzate</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="gsc_client_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Client ID</label>
                        <input type="text" name="gsc_client_id" id="gsc_client_id"
                               value="<?= e($settings['gsc_client_id']['value'] ?? '') ?>"
                               placeholder="xxxxx.apps.googleusercontent.com"
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label for="gsc_client_secret" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Client Secret</label>
                        <div class="relative">
                            <input type="password" name="gsc_client_secret" id="gsc_client_secret"
                                   value="<?= e($settings['gsc_client_secret']['value'] ?? '') ?>"
                                   placeholder="GOCSPX-..."
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <button type="button" onclick="togglePassword('gsc_client_secret')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Redirect URI (da configurare in Google Cloud Console)</label>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly
                               value="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('/oauth/google/callback') ?>"
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-600 text-slate-700 dark:text-slate-300 py-2 px-3 cursor-not-allowed"
                               onclick="this.select()">
                        <button type="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.innerHTML='<svg class=\'w-5 h-5 text-green-500\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'/></svg>'; setTimeout(() => this.innerHTML='<svg class=\'w-5 h-5\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z\'/></svg>', 2000)"
                                class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Copia">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Questo URI centralizzato gestisce OAuth per tutti i moduli (SEO Tracking, SEO Audit, ecc.)
                    </p>
                </div>
            </div>
        </div>

        <!-- SERP Providers (Rank Checking) -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    SERP Providers (Rank Checking)
                </h3>
            </div>
            <div class="p-6 space-y-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <p class="font-medium mb-1">Come funziona:</p>
                            <p class="text-blue-600 dark:text-blue-400">
                                Il sistema usa <strong>Serper.dev come provider primario</strong> (2.500 query/mese gratis).
                                Se Serper fallisce, usa automaticamente <strong>SERP API come fallback</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Serper.dev (Primary) -->
                <div class="border border-emerald-200 dark:border-emerald-800 rounded-lg p-4 bg-emerald-50/50 dark:bg-emerald-900/10">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                            PRIMARIO
                        </span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">Serper.dev</span>
                    </div>
                    <label for="serper_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Serper.dev API Key
                        <?php if (!empty($settings['serper_api_key']['value'])): ?>
                            <span class="text-green-600 dark:text-green-400 text-xs ml-2">Configurata</span>
                        <?php endif; ?>
                    </label>
                    <div class="relative">
                        <input type="password" name="serper_api_key" id="serper_api_key"
                               value="<?= e($settings['serper_api_key']['value'] ?? '') ?>"
                               placeholder="Inserisci Serper.dev API Key..."
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <button type="button" onclick="togglePassword('serper_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        <a href="https://serper.dev/" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline">
                            Ottieni API Key da Serper.dev
                        </a>
                        - <strong>2.500 query/mese GRATIS</strong>
                    </p>
                </div>

                <!-- SERP API (Fallback) -->
                <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                            FALLBACK
                        </span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">SERP API</span>
                    </div>
                    <label for="serp_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        SERP API Key
                        <?php if (!empty($settings['serp_api_key']['value'])): ?>
                            <span class="text-green-600 dark:text-green-400 text-xs ml-2">Configurata</span>
                        <?php endif; ?>
                    </label>
                    <div class="relative">
                        <input type="password" name="serp_api_key" id="serp_api_key"
                               value="<?= e($settings['serp_api_key']['value'] ?? '') ?>"
                               placeholder="Inserisci SERP API Key..."
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <button type="button" onclick="togglePassword('serp_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        <a href="https://serpapi.com/manage-api-key" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline">
                            Ottieni API Key da SerpAPI
                        </a>
                        - Usato se Serper.dev fallisce (100 query/mese gratis)
                    </p>
                </div>
            </div>
        </div>

        <!-- DataForSEO (Volumi di Ricerca) -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    DataForSEO (Volumi di Ricerca)
                </h3>
            </div>
            <div class="p-6 space-y-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <p class="font-medium mb-1">Volumi di ricerca Google Ads</p>
                            <p class="text-blue-600 dark:text-blue-400">
                                DataForSEO fornisce volumi di ricerca, CPC, competition e trend mensili.
                                Costo: ~$0.05 per keyword in bulk.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="dataforseo_login" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            DataForSEO Login (Email)
                            <?php if (!empty($settings['dataforseo_login']['value'])): ?>
                                <span class="text-green-600 dark:text-green-400 text-xs ml-2">Configurato</span>
                            <?php endif; ?>
                        </label>
                        <input type="text" name="dataforseo_login" id="dataforseo_login"
                               value="<?= e($settings['dataforseo_login']['value'] ?? '') ?>"
                               placeholder="tuo@email.com"
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="dataforseo_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            DataForSEO Password (API Key)
                            <?php if (!empty($settings['dataforseo_password']['value'])): ?>
                                <span class="text-green-600 dark:text-green-400 text-xs ml-2">Configurata</span>
                            <?php endif; ?>
                        </label>
                        <div class="relative">
                            <input type="password" name="dataforseo_password" id="dataforseo_password"
                                   value="<?= e($settings['dataforseo_password']['value'] ?? '') ?>"
                                   placeholder="API Password"
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button type="button" onclick="togglePassword('dataforseo_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            <a href="https://dataforseo.com/" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">
                                Ottieni credenziali da DataForSEO
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMTP -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Email (SMTP)
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="smtp_host" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">SMTP Host</label>
                    <input type="text" name="smtp_host" id="smtp_host"
                           value="<?= e($settings['smtp_host']['value'] ?? '') ?>"
                           placeholder="smtp.example.com"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="smtp_port" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">SMTP Port</label>
                    <input type="number" name="smtp_port" id="smtp_port"
                           value="<?= e($settings['smtp_port']['value'] ?? '587') ?>"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="smtp_username" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Username</label>
                    <input type="text" name="smtp_username" id="smtp_username"
                           value="<?= e($settings['smtp_username']['value'] ?? '') ?>"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="smtp_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
                    <input type="password" name="smtp_password" id="smtp_password"
                           value="<?= e($settings['smtp_password']['value'] ?? '') ?>"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Salva impostazioni
            </button>
        </div>
    </form>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Modelli AI per provider - per aggiornamento dinamico
const aiModels = <?= json_encode($allModels) ?>;

document.getElementById('ai_provider').addEventListener('change', function() {
    const provider = this.value;
    const modelSelect = document.getElementById('ai_model');

    // Svuota e ripopola
    modelSelect.innerHTML = '';

    if (aiModels[provider] && aiModels[provider].models) {
        Object.entries(aiModels[provider].models).forEach(([modelId, modelInfo]) => {
            const option = document.createElement('option');
            option.value = modelId;
            option.textContent = `${modelInfo.name} (in: $${modelInfo.input_cost}/1K, out: $${modelInfo.output_cost}/1K)`;
            modelSelect.appendChild(option);
        });
    }
});
</script>
