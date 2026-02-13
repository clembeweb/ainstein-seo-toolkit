<?php
use Services\AiService;
use Core\BrandingHelper;

// Carica dati branding
$brandColorPrimary = $settings['brand_color_primary']['value'] ?? '#006e96';
$brandColorSecondary = $settings['brand_color_secondary']['value'] ?? '#004d69';
$brandColorAccent = $settings['brand_color_accent']['value'] ?? '#00a3d9';
$brandFont = $settings['brand_font']['value'] ?? 'Inter';
$brandLogoH = $settings['brand_logo_horizontal']['value'] ?? '';
$brandLogoS = $settings['brand_logo_square']['value'] ?? '';
$brandFavicon = $settings['brand_favicon']['value'] ?? '';
$allowedFonts = BrandingHelper::getAllowedFonts();

// Carica dati AI
$aiStatus = AiService::getConfigurationStatus();
$providers = AiService::getProviders();
$allModels = AiService::getAvailableModels();
$currentProvider = $settings['ai_provider']['value'] ?? 'anthropic';
$currentModel = $settings['ai_model']['value'] ?? 'claude-sonnet-4-20250514';
$fallbackEnabled = ($settings['ai_fallback_enabled']['value'] ?? '1') === '1';

// Calcola status per ogni sezione
$aiConfigured = $aiStatus[$currentProvider]['configured'] ?? false;
$serpConfigured = !empty($settings['serper_api_key']['value'] ?? '');
$googleConfigured = !empty($settings['gsc_client_id']['value'] ?? '') && !empty($settings['gsc_client_secret']['value'] ?? '');
$keywordConfigured = !empty($settings['rapidapi_keyword_key']['value'] ?? '') || !empty($settings['dataforseo_login']['value'] ?? '') || !empty($settings['keywordseverywhere_api_key']['value'] ?? '');
$stripeConfigured = !empty($settings['stripe_public_key']['value'] ?? '') && !empty($settings['stripe_secret_key']['value'] ?? '');
$smtpConfigured = !empty($settings['smtp_host']['value'] ?? '');
?>

<div x-data="{ activeTab: new URLSearchParams(window.location.search).get('tab') || 'essentials' }" class="space-y-6">
    <!-- Header compatto -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impostazioni</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configura le API e le preferenze di sistema</p>
        </div>
        <!-- Status rapido -->
        <div class="flex items-center gap-3">
            <?php if ($aiConfigured && $serpConfigured): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Pronto
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    Setup richiesto
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-1" aria-label="Tabs">
            <button type="button" @click="activeTab = 'essentials'"
                    :class="activeTab === 'essentials' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="flex items-center gap-2 px-4 py-3 border-b-2 font-medium text-sm rounded-t-lg transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Essenziali
                <?php if (!$aiConfigured || !$serpConfigured): ?>
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <?php endif; ?>
            </button>
            <button type="button" @click="activeTab = 'integrations'"
                    :class="activeTab === 'integrations' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="flex items-center gap-2 px-4 py-3 border-b-2 font-medium text-sm rounded-t-lg transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/></svg>
                Integrazioni
            </button>
            <button type="button" @click="activeTab = 'advanced'"
                    :class="activeTab === 'advanced' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="flex items-center gap-2 px-4 py-3 border-b-2 font-medium text-sm rounded-t-lg transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Avanzate
            </button>
            <button type="button" @click="activeTab = 'branding'"
                    :class="activeTab === 'branding' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="flex items-center gap-2 px-4 py-3 border-b-2 font-medium text-sm rounded-t-lg transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                Aspetto
            </button>
        </nav>
    </div>

    <form action="<?= url('/admin/settings') ?>" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <!-- ============================================ -->
        <!-- TAB 1: ESSENZIALI -->
        <!-- ============================================ -->
        <div x-show="activeTab === 'essentials'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">

            <!-- AI Configuration -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/30">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Intelligenza Artificiale</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Provider per analisi e generazione contenuti</p>
                        </div>
                    </div>
                    <?php if ($aiConfigured): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Configurato
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            Richiesto
                        </span>
                    <?php endif; ?>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Provider e Model Selection -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="ai_provider" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Provider</label>
                            <select name="ai_provider" id="ai_provider"
                                    class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <?php foreach ($providers as $providerId => $providerName): ?>
                                    <option value="<?= e($providerId) ?>" <?= $currentProvider === $providerId ? 'selected' : '' ?>>
                                        <?= e($providerName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="ai_model" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Modello</label>
                            <select name="ai_model" id="ai_model"
                                    class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <?php foreach ($allModels[$currentProvider]['models'] as $modelId => $modelInfo): ?>
                                    <option value="<?= e($modelId) ?>" <?= $currentModel === $modelId ? 'selected' : '' ?>>
                                        <?= e($modelInfo['name']) ?> (in: $<?= $modelInfo['input_cost'] ?>/1K, out: $<?= $modelInfo['output_cost'] ?>/1K)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- API Key principale (in base al provider selezionato) -->
                    <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Anthropic API Key -->
                            <div>
                                <label for="anthropic_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                    Anthropic API Key
                                    <?php if ($aiStatus['anthropic']['configured']): ?>
                                        <span class="text-green-600 dark:text-green-400 text-xs ml-1">Configurata</span>
                                    <?php endif; ?>
                                </label>
                                <div class="relative">
                                    <input type="password" name="anthropic_api_key" id="anthropic_api_key"
                                           value="<?= e($settings['anthropic_api_key']['value'] ?? '') ?>"
                                           placeholder="sk-ant-api03-..."
                                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 pr-10 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <button type="button" onclick="togglePassword('anthropic_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline">Ottieni API Key</a>
                                </p>
                            </div>

                            <!-- OpenAI API Key -->
                            <div>
                                <label for="openai_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                    OpenAI API Key
                                    <?php if ($aiStatus['openai']['configured']): ?>
                                        <span class="text-green-600 dark:text-green-400 text-xs ml-1">Configurata</span>
                                    <?php endif; ?>
                                </label>
                                <div class="relative">
                                    <input type="password" name="openai_api_key" id="openai_api_key"
                                           value="<?= e($settings['openai_api_key']['value'] ?? '') ?>"
                                           placeholder="sk-proj-..."
                                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 pr-10 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <button type="button" onclick="togglePassword('openai_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    <a href="https://platform.openai.com/api-keys" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline">Ottieni API Key</a>
                                </p>
                            </div>
                        </div>

                        <!-- Fallback Toggle -->
                        <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="ai_fallback_enabled" value="0">
                                <input type="checkbox" name="ai_fallback_enabled" value="1"
                                       <?= $fallbackEnabled ? 'checked' : '' ?>
                                       class="w-4 h-4 text-purple-600 border-slate-300 dark:border-slate-600 rounded focus:ring-purple-500 dark:bg-slate-700">
                                <span class="text-sm text-slate-700 dark:text-slate-300">
                                    Usa l'altro provider come fallback automatico
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SERP / Rank Tracking -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Rank Tracking</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Monitoraggio posizioni su Google</p>
                        </div>
                    </div>
                    <?php if ($serpConfigured): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Configurato
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            Richiesto
                        </span>
                    <?php endif; ?>
                </div>
                <div class="p-6">
                    <div>
                        <label for="serper_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Serper.dev API Key
                            <?php if (!empty($settings['serper_api_key']['value'])): ?>
                                <span class="text-green-600 dark:text-green-400 text-xs ml-1">Configurata</span>
                            <?php endif; ?>
                        </label>
                        <div class="relative">
                            <input type="password" name="serper_api_key" id="serper_api_key"
                                   value="<?= e($settings['serper_api_key']['value'] ?? '') ?>"
                                   placeholder="Inserisci Serper.dev API Key..."
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 pr-10 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <button type="button" onclick="togglePassword('serper_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            <a href="https://serper.dev/" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Ottieni API Key gratis</a>
                            <span class="mx-1.5">·</span>
                            2.500 query/mese incluse
                        </p>
                    </div>
                </div>
            </div>

            <!-- Save Button (fisso per tab essentials) -->
            <div class="flex justify-end pt-2">
                <button type="submit" class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salva impostazioni
                </button>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- TAB 2: INTEGRAZIONI -->
        <!-- ============================================ -->
        <div x-show="activeTab === 'integrations'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-4">

            <!-- Google OAuth -->
            <div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button type="button" @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <h3 class="font-semibold text-slate-900 dark:text-white">Google Search Console / Analytics</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Importa dati da GSC e GA4</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php if ($googleConfigured): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Collegato
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">Opzionale</span>
                        <?php endif; ?>
                        <svg class="w-5 h-5 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>
                <div x-show="open" x-collapse>
                    <div class="px-6 pb-6 pt-2 border-t border-slate-200 dark:border-slate-700 space-y-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-sm text-blue-700 dark:text-blue-300">
                            <p class="font-medium mb-1">Setup rapido:</p>
                            <ol class="list-decimal list-inside space-y-0.5 text-blue-600 dark:text-blue-400 text-xs">
                                <li>Vai su <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="underline">Google Cloud Console</a></li>
                                <li>Crea credenziali OAuth 2.0 (Web application)</li>
                                <li>Copia il Redirect URI qui sotto nelle origini autorizzate</li>
                            </ol>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="gsc_client_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Client ID</label>
                                <input type="text" name="gsc_client_id" id="gsc_client_id"
                                       value="<?= e($settings['gsc_client_id']['value'] ?? '') ?>"
                                       placeholder="xxxxx.apps.googleusercontent.com"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="gsc_client_secret" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Client Secret</label>
                                <div class="relative">
                                    <input type="password" name="gsc_client_secret" id="gsc_client_secret"
                                           value="<?= e($settings['gsc_client_secret']['value'] ?? '') ?>"
                                           placeholder="GOCSPX-..."
                                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <button type="button" onclick="togglePassword('gsc_client_secret')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Redirect URI</label>
                            <div class="flex items-center gap-2">
                                <input type="text" readonly
                                       value="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('/oauth/google/callback') ?>"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-600 text-slate-700 dark:text-slate-300 py-2 px-3 text-sm cursor-not-allowed"
                                       onclick="this.select()">
                                <button type="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.innerHTML='<svg class=\'w-4 h-4 text-green-500\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'/></svg>'; setTimeout(() => this.innerHTML='<svg class=\'w-4 h-4\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z\'/></svg>', 2000)"
                                        class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Copia">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keyword Volume Providers -->
            <div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button type="button" @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <h3 class="font-semibold text-slate-900 dark:text-white">Volumi di Ricerca Keyword</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">RapidAPI, DataForSEO o Keywords Everywhere</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php if ($keywordConfigured): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Attivo
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">Opzionale</span>
                        <?php endif; ?>
                        <svg class="w-5 h-5 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>
                <div x-show="open" x-collapse>
                    <div class="px-6 pb-6 pt-2 border-t border-slate-200 dark:border-slate-700 space-y-4">
                        <p class="text-sm text-slate-600 dark:text-slate-400">Configura almeno uno di questi provider per ottenere i volumi di ricerca delle keyword.</p>

                        <!-- RapidAPI (Primary) -->
                        <div class="p-4 rounded-lg border-2 border-indigo-200 dark:border-indigo-800 bg-indigo-50/50 dark:bg-indigo-900/10">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">CONSIGLIATO</span>
                                <span class="text-sm font-medium text-slate-900 dark:text-white">RapidAPI</span>
                            </div>
                            <div class="relative">
                                <input type="password" name="rapidapi_keyword_key" id="rapidapi_keyword_key"
                                       value="<?= e($settings['rapidapi_keyword_key']['value'] ?? '') ?>"
                                       placeholder="La tua RapidAPI Key"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                <button type="button" onclick="togglePassword('rapidapi_keyword_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-slate-500"><a href="https://rapidapi.com/developer-developer-default/api/google-seo-keyword-research-ai" target="_blank" class="text-indigo-600 hover:underline">Ottieni API Key</a></p>
                        </div>

                        <!-- DataForSEO (Fallback) -->
                        <div class="p-4 rounded-lg border border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">FALLBACK</span>
                                <span class="text-sm font-medium text-slate-900 dark:text-white">DataForSEO</span>
                                <span class="text-xs text-slate-500">~$0.05/keyword</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <input type="text" name="dataforseo_login" id="dataforseo_login"
                                       value="<?= e($settings['dataforseo_login']['value'] ?? '') ?>"
                                       placeholder="Email"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <div class="relative">
                                    <input type="password" name="dataforseo_password" id="dataforseo_password"
                                           value="<?= e($settings['dataforseo_password']['value'] ?? '') ?>"
                                           placeholder="API Password"
                                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <button type="button" onclick="togglePassword('dataforseo_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500"><a href="https://dataforseo.com/" target="_blank" class="text-blue-600 hover:underline">Ottieni credenziali</a></p>
                        </div>

                        <!-- Keywords Everywhere (Alternative) -->
                        <div class="p-4 rounded-lg border border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">ALTERNATIVA</span>
                                <span class="text-sm font-medium text-slate-900 dark:text-white">Keywords Everywhere</span>
                                <span class="text-xs text-slate-500">~$10/100k crediti</span>
                            </div>
                            <div class="relative">
                                <input type="password" name="keywordseverywhere_api_key" id="keywordseverywhere_api_key"
                                       value="<?= e($settings['keywordseverywhere_api_key']['value'] ?? '') ?>"
                                       placeholder="API Key"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <button type="button" onclick="togglePassword('keywordseverywhere_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-slate-500"><a href="https://keywordseverywhere.com/api-documentation.html" target="_blank" class="text-blue-600 hover:underline">Ottieni API Key</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SERP API Fallback -->
            <div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button type="button" @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-slate-100 dark:bg-slate-700">
                            <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <h3 class="font-semibold text-slate-900 dark:text-white">SERP API (Fallback)</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Backup per rank tracking se Serper fallisce</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php if (!empty($settings['serp_api_key']['value'] ?? '')): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Configurato</span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">Opzionale</span>
                        <?php endif; ?>
                        <svg class="w-5 h-5 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>
                <div x-show="open" x-collapse>
                    <div class="px-6 pb-6 pt-2 border-t border-slate-200 dark:border-slate-700">
                        <div class="relative">
                            <input type="password" name="serp_api_key" id="serp_api_key"
                                   value="<?= e($settings['serp_api_key']['value'] ?? '') ?>"
                                   placeholder="Inserisci SERP API Key..."
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-sm">
                            <button type="button" onclick="togglePassword('serp_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500"><a href="https://serpapi.com/manage-api-key" target="_blank" class="text-slate-600 hover:underline">Ottieni API Key</a> · 100 query/mese gratis</p>
                    </div>
                </div>
            </div>

            <!-- Stripe -->
            <div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button type="button" @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-violet-100 dark:bg-violet-900/30">
                            <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <div class="text-left">
                            <h3 class="font-semibold text-slate-900 dark:text-white">Stripe (Pagamenti)</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Acquisto crediti utenti</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php if ($stripeConfigured): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Configurato</span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">Opzionale</span>
                        <?php endif; ?>
                        <svg class="w-5 h-5 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>
                <div x-show="open" x-collapse>
                    <div class="px-6 pb-6 pt-2 border-t border-slate-200 dark:border-slate-700">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="stripe_public_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Public Key</label>
                                <input type="text" name="stripe_public_key" id="stripe_public_key"
                                       value="<?= e($settings['stripe_public_key']['value'] ?? '') ?>"
                                       placeholder="pk_..."
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 text-sm">
                            </div>
                            <div>
                                <label for="stripe_secret_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Secret Key</label>
                                <div class="relative">
                                    <input type="password" name="stripe_secret_key" id="stripe_secret_key"
                                           value="<?= e($settings['stripe_secret_key']['value'] ?? '') ?>"
                                           placeholder="sk_..."
                                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 pr-10 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 text-sm">
                                    <button type="button" onclick="togglePassword('stripe_secret_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SMTP -->
            <div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button type="button" @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-slate-100 dark:bg-slate-700">
                            <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="text-left">
                            <h3 class="font-semibold text-slate-900 dark:text-white">Email (SMTP)</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Invio notifiche email</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php if ($smtpConfigured): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Configurato</span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">Opzionale</span>
                        <?php endif; ?>
                        <svg class="w-5 h-5 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>
                <div x-show="open" x-collapse>
                    <div class="px-6 pb-6 pt-2 border-t border-slate-200 dark:border-slate-700">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <label for="smtp_host" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Host</label>
                                <input type="text" name="smtp_host" id="smtp_host"
                                       value="<?= e($settings['smtp_host']['value'] ?? '') ?>"
                                       placeholder="smtp.example.com"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-sm">
                            </div>
                            <div>
                                <label for="smtp_port" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Port</label>
                                <input type="number" name="smtp_port" id="smtp_port"
                                       value="<?= e($settings['smtp_port']['value'] ?? '587') ?>"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-sm">
                            </div>
                            <div>
                                <label for="smtp_username" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Username</label>
                                <input type="text" name="smtp_username" id="smtp_username"
                                       value="<?= e($settings['smtp_username']['value'] ?? '') ?>"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-sm">
                            </div>
                            <div>
                                <label for="smtp_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
                                <input type="password" name="smtp_password" id="smtp_password"
                                       value="<?= e($settings['smtp_password']['value'] ?? '') ?>"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end pt-2">
                <button type="submit" class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salva impostazioni
                </button>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- TAB 3: AVANZATE -->
        <!-- ============================================ -->
        <div x-show="activeTab === 'advanced'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-4">

            <!-- General Settings -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Generale
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="site_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome sito</label>
                            <input type="text" name="site_name" id="site_name" value="<?= e($settings['site_name']['value'] ?? 'SEO Toolkit') ?>"
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                        <div>
                            <label for="free_credits" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Crediti nuovi utenti</label>
                            <input type="number" name="free_credits" id="free_credits" value="<?= e($settings['free_credits']['value'] ?? '50') ?>"
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                            <p class="mt-1 text-xs text-slate-500">Assegnati automaticamente alla registrazione</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credit Costs -->
            <div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button type="button" @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472a4.265 4.265 0 01.264-.521z"/>
                        </svg>
                        <div class="text-left">
                            <h3 class="font-semibold text-slate-900 dark:text-white">Costi Operazioni (Crediti)</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Personalizza il costo di ogni operazione</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse>
                    <div class="px-6 pb-6 pt-2 border-t border-slate-200 dark:border-slate-700">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="cost_scrape_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Scrape URL</label>
                                <input type="number" step="0.1" name="cost_scrape_url" id="cost_scrape_url"
                                       value="<?= e($settings['cost_scrape_url']['value'] ?? '0.1') ?>"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                            </div>
                            <div>
                                <label for="cost_ai_analysis_small" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">AI Small (&lt;1k)</label>
                                <input type="number" step="0.1" name="cost_ai_analysis_small" id="cost_ai_analysis_small"
                                       value="<?= e($settings['cost_ai_analysis_small']['value'] ?? '1') ?>"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                            </div>
                            <div>
                                <label for="cost_ai_analysis_medium" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">AI Medium (1-5k)</label>
                                <input type="number" step="0.1" name="cost_ai_analysis_medium" id="cost_ai_analysis_medium"
                                       value="<?= e($settings['cost_ai_analysis_medium']['value'] ?? '2') ?>"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                            </div>
                            <div>
                                <label for="cost_ai_analysis_large" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">AI Large (&gt;5k)</label>
                                <input type="number" step="0.1" name="cost_ai_analysis_large" id="cost_ai_analysis_large"
                                       value="<?= e($settings['cost_ai_analysis_large']['value'] ?? '5') ?>"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                            </div>
                            <div>
                                <label for="cost_export_csv" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Export CSV</label>
                                <input type="number" step="0.1" name="cost_export_csv" id="cost_export_csv"
                                       value="<?= e($settings['cost_export_csv']['value'] ?? '0') ?>"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                            </div>
                            <div>
                                <label for="cost_export_excel" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Export Excel</label>
                                <input type="number" step="0.1" name="cost_export_excel" id="cost_export_excel"
                                       value="<?= e($settings['cost_export_excel']['value'] ?? '0.5') ?>"
                                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end pt-2">
                <button type="submit" class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salva impostazioni
                </button>
            </div>
        </div>
    </form>

    <!-- ============================================ -->
    <!-- TAB 4: ASPETTO (form separato per file upload) -->
    <!-- ============================================ -->
    <form action="<?= url('/admin/settings/branding') ?>" method="POST" enctype="multipart/form-data"
          x-show="activeTab === 'branding'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
          x-data="brandingHandler()" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Colori del Brand -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <div class="p-2 rounded-lg bg-pink-100 dark:bg-pink-900/30">
                    <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Colori del Brand</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Scegli i colori principali della piattaforma</p>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <!-- Colore Primario -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Colore Primario</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="brand_color_primary" x-model="colors.primary" @input="updateShades('primary')"
                                   class="h-10 w-14 rounded-lg border border-slate-300 dark:border-slate-600 cursor-pointer">
                            <input type="text" readonly :value="colors.primary"
                                   class="w-24 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-300 py-2 px-3 text-sm font-mono">
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Pulsanti, link, elementi attivi</p>
                        <div class="flex mt-2 rounded-lg overflow-hidden h-6 border border-slate-200 dark:border-slate-600">
                            <template x-for="shade in shades.primary" :key="shade">
                                <div class="flex-1" :style="'background:'+shade"></div>
                            </template>
                        </div>
                    </div>
                    <!-- Colore Secondario -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Colore Secondario</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="brand_color_secondary" x-model="colors.secondary" @input="updateShades('secondary')"
                                   class="h-10 w-14 rounded-lg border border-slate-300 dark:border-slate-600 cursor-pointer">
                            <input type="text" readonly :value="colors.secondary"
                                   class="w-24 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-300 py-2 px-3 text-sm font-mono">
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Sfondo sidebar, header scuri</p>
                        <div class="flex mt-2 rounded-lg overflow-hidden h-6 border border-slate-200 dark:border-slate-600">
                            <template x-for="shade in shades.secondary" :key="shade">
                                <div class="flex-1" :style="'background:'+shade"></div>
                            </template>
                        </div>
                    </div>
                    <!-- Colore Accent -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Colore Accent</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="brand_color_accent" x-model="colors.accent" @input="updateShades('accent')"
                                   class="h-10 w-14 rounded-lg border border-slate-300 dark:border-slate-600 cursor-pointer">
                            <input type="text" readonly :value="colors.accent"
                                   class="w-24 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-300 py-2 px-3 text-sm font-mono">
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Evidenziazioni, badge, link secondari</p>
                        <div class="flex mt-2 rounded-lg overflow-hidden h-6 border border-slate-200 dark:border-slate-600">
                            <template x-for="shade in shades.accent" :key="shade">
                                <div class="flex-1" :style="'background:'+shade"></div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" @click="resetColors()"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Ripristina colori predefiniti
                    </button>
                </div>
            </div>
        </div>

        <!-- Tipografia -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <div class="p-2 rounded-lg bg-sky-100 dark:bg-sky-900/30">
                    <svg class="w-5 h-5 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Tipografia</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Font principale della piattaforma</p>
                </div>
            </div>
            <div class="p-6">
                <div class="max-w-md">
                    <label for="brand_font" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Font principale</label>
                    <select name="brand_font" id="brand_font" x-model="selectedFont" @change="loadFontPreview()"
                            class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <?php foreach ($allowedFonts as $font): ?>
                            <option value="<?= e($font) ?>" <?= $brandFont === $font ? 'selected' : '' ?>><?= e($font) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mt-4 p-4 rounded-lg bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Anteprima:</p>
                    <p class="text-lg text-slate-900 dark:text-white" :style="'font-family: ' + selectedFont + ', sans-serif'">
                        La SEO e l'arte di posizionarsi in cima ai risultati di ricerca.
                    </p>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1" :style="'font-family: ' + selectedFont + ', sans-serif'">
                        ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz 0123456789
                    </p>
                </div>
            </div>
        </div>

        <!-- Loghi e Favicon -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/30">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Loghi e Favicon</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Carica i loghi personalizzati della piattaforma</p>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <!-- Logo Orizzontale -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Logo Orizzontale</label>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Visualizzato nella sidebar desktop</p>
                        <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-4 text-center hover:border-primary-400 dark:hover:border-primary-500 transition-colors">
                            <?php if ($brandLogoH): ?>
                                <img src="<?= url('/' . e($brandLogoH)) ?>" alt="Logo orizzontale" class="h-12 mx-auto mb-3 dark:brightness-0 dark:invert">
                            <?php else: ?>
                                <img src="<?= url('/assets/images/logo-ainstein-orizzontal.png') ?>" alt="Logo predefinito" class="h-12 mx-auto mb-3 opacity-40 dark:brightness-0 dark:invert">
                                <p class="text-xs text-slate-400 mb-2">Logo predefinito</p>
                            <?php endif; ?>
                            <input type="file" name="brand_logo_horizontal" accept=".png,.jpg,.jpeg,.svg,.webp"
                                   class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-900/30 dark:file:text-primary-400 hover:file:bg-primary-100">
                            <p class="mt-2 text-xs text-slate-400">PNG, JPG, SVG o WebP. Max 2MB</p>
                        </div>
                        <?php if ($brandLogoH): ?>
                        <label class="mt-2 flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remove_logo_horizontal" value="1"
                                   class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500 dark:bg-slate-700">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Rimuovi e usa logo predefinito</span>
                        </label>
                        <?php endif; ?>
                    </div>

                    <!-- Logo Quadrato -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Logo Quadrato</label>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Visualizzato nella sidebar mobile</p>
                        <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-4 text-center hover:border-primary-400 dark:hover:border-primary-500 transition-colors">
                            <?php if ($brandLogoS): ?>
                                <img src="<?= url('/' . e($brandLogoS)) ?>" alt="Logo quadrato" class="h-12 w-12 mx-auto mb-3 dark:brightness-0 dark:invert">
                            <?php else: ?>
                                <img src="<?= url('/assets/images/logo-ainstein-square.png') ?>" alt="Logo predefinito" class="h-12 w-12 mx-auto mb-3 opacity-40 dark:brightness-0 dark:invert">
                                <p class="text-xs text-slate-400 mb-2">Logo predefinito</p>
                            <?php endif; ?>
                            <input type="file" name="brand_logo_square" accept=".png,.jpg,.jpeg,.svg,.webp"
                                   class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-900/30 dark:file:text-primary-400 hover:file:bg-primary-100">
                            <p class="mt-2 text-xs text-slate-400">PNG, JPG, SVG o WebP. Max 2MB</p>
                        </div>
                        <?php if ($brandLogoS): ?>
                        <label class="mt-2 flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remove_logo_square" value="1"
                                   class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500 dark:bg-slate-700">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Rimuovi e usa logo predefinito</span>
                        </label>
                        <?php endif; ?>
                    </div>

                    <!-- Favicon -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Favicon</label>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Icona del browser (tab)</p>
                        <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-4 text-center hover:border-primary-400 dark:hover:border-primary-500 transition-colors">
                            <?php if ($brandFavicon): ?>
                                <img src="<?= url('/' . e($brandFavicon)) ?>" alt="Favicon" class="h-10 w-10 mx-auto mb-3">
                            <?php else: ?>
                                <img src="<?= url('/favicon.svg') ?>" alt="Favicon predefinito" class="h-10 w-10 mx-auto mb-3 opacity-40">
                                <p class="text-xs text-slate-400 mb-2">Favicon predefinito</p>
                            <?php endif; ?>
                            <input type="file" name="brand_favicon" accept=".svg,.png,.ico"
                                   class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-900/30 dark:file:text-primary-400 hover:file:bg-primary-100">
                            <p class="mt-2 text-xs text-slate-400">SVG, PNG o ICO. Max 500KB</p>
                        </div>
                        <?php if ($brandFavicon): ?>
                        <label class="mt-2 flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remove_favicon" value="1"
                                   class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500 dark:bg-slate-700">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Rimuovi e usa favicon predefinito</span>
                        </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end pt-2">
            <button type="submit" class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Salva Aspetto
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

// Branding handler
function brandingHandler() {
    return {
        colors: {
            primary: '<?= e($brandColorPrimary) ?>',
            secondary: '<?= e($brandColorSecondary) ?>',
            accent: '<?= e($brandColorAccent) ?>'
        },
        defaultColors: { primary: '#006e96', secondary: '#004d69', accent: '#00a3d9' },
        selectedFont: '<?= e($brandFont) ?>',
        shades: { primary: [], secondary: [], accent: [] },

        init() {
            this.updateShades('primary');
            this.updateShades('secondary');
            this.updateShades('accent');
            this.loadFontPreview();
        },

        updateShades(key) {
            this.shades[key] = this.generatePalette(this.colors[key]);
        },

        resetColors() {
            this.colors = { ...this.defaultColors };
            this.updateShades('primary');
            this.updateShades('secondary');
            this.updateShades('accent');
        },

        loadFontPreview() {
            const fontName = this.selectedFont.replace(/ /g, '+');
            const linkId = 'branding-font-preview';
            let link = document.getElementById(linkId);
            if (!link) {
                link = document.createElement('link');
                link.id = linkId;
                link.rel = 'stylesheet';
                document.head.appendChild(link);
            }
            link.href = 'https://fonts.googleapis.com/css2?family=' + fontName + ':wght@400;500;600;700&display=swap';
        },

        generatePalette(hex) {
            const hsl = this.hexToHSL(hex);
            const h = hsl.h, s = hsl.s, l = hsl.l;
            const map = [
                { l: 96, sf: 0.30 }, { l: 91, sf: 0.40 }, { l: 82, sf: 0.55 },
                { l: 68, sf: 0.70 }, { l: 52, sf: 0.85 }, { l: l, sf: 1.0 },
                { l: Math.max(5, l*0.82), sf: 1.0 }, { l: Math.max(5, l*0.65), sf: 1.0 },
                { l: Math.max(4, l*0.50), sf: 1.0 }, { l: Math.max(3, l*0.35), sf: 1.0 },
                { l: Math.max(2, l*0.20), sf: 1.0 }
            ];
            return map.map(m => this.hslToHex(h, s * m.sf, m.l));
        },

        hexToHSL(hex) {
            hex = hex.replace('#', '');
            const r = parseInt(hex.substr(0,2),16)/255;
            const g = parseInt(hex.substr(2,2),16)/255;
            const b = parseInt(hex.substr(4,2),16)/255;
            const max = Math.max(r,g,b), min = Math.min(r,g,b);
            let h = 0, s = 0, l = (max+min)/2;
            if (max !== min) {
                const d = max - min;
                s = l > 0.5 ? d/(2-max-min) : d/(max+min);
                if (max === r) h = ((g-b)/d + (g<b?6:0))/6;
                else if (max === g) h = ((b-r)/d+2)/6;
                else h = ((r-g)/d+4)/6;
            }
            return { h: h*360, s: s*100, l: l*100 };
        },

        hslToHex(h, s, l) {
            h /= 360; s /= 100; l /= 100;
            let r, g, b;
            if (s === 0) { r = g = b = l; }
            else {
                const q = l < 0.5 ? l*(1+s) : l+s-l*s;
                const p = 2*l - q;
                const hue2rgb = (p,q,t) => {
                    if (t<0) t+=1; if (t>1) t-=1;
                    if (t<1/6) return p+(q-p)*6*t;
                    if (t<1/2) return q;
                    if (t<2/3) return p+(q-p)*(2/3-t)*6;
                    return p;
                };
                r = hue2rgb(p,q,h+1/3);
                g = hue2rgb(p,q,h);
                b = hue2rgb(p,q,h-1/3);
            }
            const toHex = x => Math.round(x*255).toString(16).padStart(2,'0');
            return '#' + toHex(r) + toHex(g) + toHex(b);
        }
    };
}
</script>
