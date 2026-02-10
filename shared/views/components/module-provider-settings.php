<?php
/**
 * Module Provider Settings Component
 *
 * Renders non-AI provider configuration for modules.
 * Included from admin/views/module-settings.php.
 *
 * Expected variables:
 * @var string $moduleSlug - The module slug
 * @var array $currentSettings - Current module settings
 * @var array $providerGroups - Array of provider group configurations
 *     Each group: ['key' => string, 'label' => string, 'description' => string, 'setting_key' => string, 'providers' => [...]]
 *     Each provider: ['value' => string, 'label' => string, 'configured' => bool, 'note' => string]
 */

use Core\Settings;
?>

<script>
const moduleProviderConfig = <?= json_encode([
    'groups' => $providerGroups,
    'currentSettings' => array_intersect_key(
        $currentSettings,
        array_flip(array_column($providerGroups, 'setting_key'))
    ),
], JSON_UNESCAPED_UNICODE) ?>;

function moduleProviderSettingsHandler() {
    return {
        open: true,
        selections: {},

        init() {
            // Initialize selections from current settings
            moduleProviderConfig.groups.forEach(group => {
                this.selections[group.setting_key] = moduleProviderConfig.currentSettings[group.setting_key] || group.providers[0].value;
            });
        },

        getSelectedLabel(settingKey) {
            const group = moduleProviderConfig.groups.find(g => g.setting_key === settingKey);
            if (!group) return '';
            const provider = group.providers.find(p => p.value === this.selections[settingKey]);
            return provider ? provider.label : '';
        },

        isConfigured(settingKey) {
            const group = moduleProviderConfig.groups.find(g => g.setting_key === settingKey);
            if (!group) return false;
            const val = this.selections[settingKey];
            if (val === 'auto') return true;
            const provider = group.providers.find(p => p.value === val);
            return provider ? provider.configured : false;
        }
    };
}
</script>

<div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
     x-data="moduleProviderSettingsHandler()">

    <!-- Group Header -->
    <button type="button"
            @click="open = !open"
            class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-slate-900 dark:text-white">Provider Esterni</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Selezione provider per le funzionalita del modulo</p>
            </div>
        </div>
        <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <!-- Group Content -->
    <div x-show="open" x-collapse>
        <div class="px-6 pb-6 pt-2 border-t border-slate-200 dark:border-slate-700 space-y-5">

            <!-- Provider Status Overview -->
            <div class="flex flex-wrap gap-2">
                <?php foreach ($providerGroups as $group): ?>
                    <?php foreach ($group['providers'] as $provider): ?>
                        <?php if ($provider['value'] === 'auto') continue; ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?= $provider['configured']
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                            : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' ?>">
                            <?php if ($provider['configured']): ?>
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <?php else: ?>
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            <?php endif; ?>
                            <?= e($provider['label']) ?>
                        </span>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <!-- Provider Selects -->
            <div class="grid grid-cols-1 sm:grid-cols-<?= count($providerGroups) > 1 ? '2' : '1' ?> gap-4">
                <?php foreach ($providerGroups as $group): ?>
                <div>
                    <label for="<?= e($group['setting_key']) ?>" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        <?= e($group['label']) ?>
                    </label>
                    <select name="<?= e($group['setting_key']) ?>" id="<?= e($group['setting_key']) ?>"
                            x-model="selections['<?= e($group['setting_key']) ?>']"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm">
                        <?php foreach ($group['providers'] as $provider): ?>
                        <option value="<?= e($provider['value']) ?>"
                                <?= ($currentSettings[$group['setting_key']] ?? $group['providers'][0]['value']) === $provider['value'] ? 'selected' : '' ?>>
                            <?= e($provider['label']) ?>
                            <?php if ($provider['value'] !== 'auto' && !$provider['configured']): ?>
                                (chiave non configurata)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($group['description'])): ?>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= e($group['description']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Effective Configuration -->
            <div class="flex items-center gap-2 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-sm text-slate-600 dark:text-slate-300">
                    <span class="font-medium">Configurazione effettiva:</span>
                    <?php foreach ($providerGroups as $i => $group): ?>
                        <?php if ($i > 0): ?> /<?php endif; ?>
                        <?= e($group['label']) ?>: <span x-text="getSelectedLabel('<?= e($group['setting_key']) ?>')"></span>
                        <template x-if="!isConfigured('<?= e($group['setting_key']) ?>')">
                            <span class="text-amber-600 dark:text-amber-400 text-xs ml-1">(chiave mancante!)</span>
                        </template>
                    <?php endforeach; ?>
                </span>
            </div>

        </div>
    </div>
</div>
