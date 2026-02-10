<?php
/**
 * Module AI Settings Component
 *
 * Renders AI provider/model configuration for modules that use AI.
 * Included from admin/views/module-settings.php.
 *
 * Expected variables:
 * @var string $moduleSlug - The module slug
 * @var array $currentSettings - Current module settings
 * @var array $aiConfig - Result of AiService::getModuleAiConfig($moduleSlug)
 * @var array $allModels - Result of AiService::getAvailableModels()
 * @var array $providers - Result of AiService::getProviders()
 */

$currentProvider = $currentSettings['ai_provider'] ?? 'global';
$currentModel = $currentSettings['ai_model'] ?? 'global';
$currentFallback = $currentSettings['ai_fallback_enabled'] ?? 'global';

$globalProviderLabel = $aiConfig['global']['provider_label'] ?? 'Anthropic (Claude)';
$globalModelLabel = $aiConfig['global']['model_label'] ?? 'Claude Sonnet 4';
$globalFallbackLabel = $aiConfig['global']['fallback'] ? 'Abilitato' : 'Disabilitato';

// Prepare models JSON for JavaScript
$modelsJson = [];
foreach ($allModels as $providerKey => $providerData) {
    $modelsJson[$providerKey] = [];
    foreach ($providerData['models'] as $modelId => $modelInfo) {
        $modelsJson[$providerKey][$modelId] = $modelInfo['name'] . ' (in: $' . $modelInfo['input_cost'] . '/1K, out: $' . $modelInfo['output_cost'] . '/1K)';
    }
}
?>

<script>
const moduleAiConfig = {
    currentProvider: <?= json_encode($currentProvider) ?>,
    currentModel: <?= json_encode($currentModel) ?>,
    currentFallback: <?= json_encode($currentFallback) ?>,
    models: <?= json_encode($modelsJson, JSON_UNESCAPED_UNICODE) ?>,
    globalProvider: <?= json_encode($aiConfig['global']['provider']) ?>,
    globalModel: <?= json_encode($aiConfig['global']['model']) ?>,
    globalModelLabel: <?= json_encode($globalModelLabel) ?>,
    globalProviderLabel: <?= json_encode($globalProviderLabel) ?>,
    globalFallbackLabel: <?= json_encode($globalFallbackLabel) ?>,
    providerLabels: <?= json_encode($providers) ?>
};

function moduleAiSettingsHandler() {
    return {
        open: true,
        provider: moduleAiConfig.currentProvider,
        model: moduleAiConfig.currentModel,
        fallback: moduleAiConfig.currentFallback,

        getEffectiveProviderLabel() {
            const p = this.provider !== 'global' ? this.provider : moduleAiConfig.globalProvider;
            return moduleAiConfig.providerLabels[p] || p;
        },

        getEffectiveModelLabel() {
            if (this.model !== 'global' && this.provider !== 'global') {
                const providerModels = moduleAiConfig.models[this.provider] || {};
                return providerModels[this.model] || this.model;
            }
            return moduleAiConfig.globalModelLabel;
        },

        getEffectiveFallbackLabel() {
            if (this.fallback !== 'global') {
                return this.fallback === '1' ? 'Abilitato' : 'Disabilitato';
            }
            return moduleAiConfig.globalFallbackLabel;
        },

        updateModelOptions() {
            const select = this.$refs.modelSelect;
            if (!select) return;
            const currentVal = select.value;

            while (select.options.length > 1) {
                select.remove(1);
            }

            if (this.provider !== 'global') {
                const providerModels = moduleAiConfig.models[this.provider] || {};
                for (const [id, label] of Object.entries(providerModels)) {
                    const opt = new Option(label, id);
                    select.add(opt);
                }
                if (currentVal === 'global' || !providerModels[currentVal]) {
                    select.value = Object.keys(providerModels)[0] || 'global';
                    this.model = select.value;
                }
            } else {
                select.value = 'global';
                this.model = 'global';
            }
        }
    };
}
</script>

<div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
     x-data="moduleAiSettingsHandler()"
     x-init="updateModelOptions()">

    <!-- Group Header -->
    <button type="button"
            @click="open = !open"
            class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-lg bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center">
                <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-slate-900 dark:text-white">Configurazione AI</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Provider e modello AI per questo modulo</p>
            </div>
        </div>
        <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <!-- Group Content -->
    <div x-show="open" x-collapse>
        <div class="px-6 pb-6 pt-2 border-t border-slate-200 dark:border-slate-700 space-y-4">

            <!-- Global Settings Reference Banner -->
            <div class="flex items-start gap-3 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm">
                    <p class="font-medium text-blue-800 dark:text-blue-300">Impostazione globale attuale</p>
                    <p class="text-blue-600 dark:text-blue-400">
                        <?= e($globalProviderLabel) ?> / <?= e($globalModelLabel) ?> / Fallback: <?= e($globalFallbackLabel) ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Provider Select -->
                <div>
                    <label for="ai_provider" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Provider AI
                    </label>
                    <select name="ai_provider" id="ai_provider"
                            x-model="provider"
                            @change="updateModelOptions()"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm">
                        <option value="global">Usa impostazione globale (<?= e($globalProviderLabel) ?>)</option>
                        <?php foreach ($providers as $providerKey => $providerLabel): ?>
                        <option value="<?= e($providerKey) ?>" <?= $currentProvider === $providerKey ? 'selected' : '' ?>>
                            <?= e($providerLabel) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Provider AI per questo modulo</p>
                </div>

                <!-- Model Select -->
                <div>
                    <label for="ai_model" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Modello AI
                    </label>
                    <select name="ai_model" id="ai_model"
                            x-ref="modelSelect"
                            x-model="model"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm">
                        <option value="global">Usa impostazione globale (<?= e($globalModelLabel) ?>)</option>
                        <?php if ($currentProvider !== 'global' && isset($modelsJson[$currentProvider])): ?>
                            <?php foreach ($modelsJson[$currentProvider] as $modelId => $modelLabel): ?>
                            <option value="<?= e($modelId) ?>" <?= $currentModel === $modelId ? 'selected' : '' ?>>
                                <?= e($modelLabel) ?>
                            </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Modello AI per questo modulo</p>
                </div>

                <!-- Fallback Select -->
                <div>
                    <label for="ai_fallback_enabled" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Fallback AI
                    </label>
                    <select name="ai_fallback_enabled" id="ai_fallback_enabled"
                            x-model="fallback"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm">
                        <option value="global">Usa impostazione globale (<?= e($globalFallbackLabel) ?>)</option>
                        <option value="1" <?= $currentFallback === '1' ? 'selected' : '' ?>>Abilitato</option>
                        <option value="0" <?= $currentFallback === '0' ? 'selected' : '' ?>>Disabilitato</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Fallback automatico su altro provider</p>
                </div>
            </div>

            <!-- Effective Configuration Badge -->
            <div class="flex items-center gap-2 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-sm text-slate-600 dark:text-slate-300">
                    <span class="font-medium">Configurazione effettiva:</span>
                    <span x-text="getEffectiveProviderLabel()"></span> /
                    <span x-text="getEffectiveModelLabel()"></span> /
                    Fallback: <span x-text="getEffectiveFallbackLabel()"></span>
                </span>
            </div>

        </div>
    </div>
</div>
