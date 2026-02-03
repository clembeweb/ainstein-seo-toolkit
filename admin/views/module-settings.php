<?php
/**
 * Module Settings View - Con supporto gruppi collassabili
 * Pattern riusabile per tutti i moduli
 */

// Organizza settings per gruppo
$groupedSettings = [];
$ungroupedSettings = [];

foreach ($settingsSchema as $key => $schema) {
    $group = $schema['group'] ?? null;
    if ($group && isset($settingsGroups[$group])) {
        if (!isset($groupedSettings[$group])) {
            $groupedSettings[$group] = [];
        }
        $groupedSettings[$group][$key] = $schema;
    } else {
        $ungroupedSettings[$key] = $schema;
    }
}

// Ordina i gruppi per 'order'
uasort($settingsGroups, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));

// Icone per i gruppi (Heroicons)
$groupIcons = [
    'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
    'refresh' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
    'sparkles' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>',
    'currency-euro' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'cog' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
];
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="<?= url('/admin/modules') ?>" class="p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impostazioni: <?= e($module['name']) ?></h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configura le impostazioni del modulo <code class="bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded"><?= e($module['slug']) ?></code></p>
        </div>
    </div>

    <?php if (empty($settingsSchema)): ?>
    <!-- No Settings -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <?= $groupIcons['cog'] ?>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessuna impostazione disponibile</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
            Questo modulo non ha impostazioni configurabili nel file <code class="bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">module.json</code>.
        </p>
    </div>
    <?php else: ?>
    <!-- Settings Form -->
    <form action="<?= url('/admin/modules/' . $module['id'] . '/settings') ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <?php if (!empty($settingsGroups)): ?>
        <!-- Grouped Settings -->
        <?php foreach ($settingsGroups as $groupKey => $groupInfo): ?>
        <?php if (!isset($groupedSettings[$groupKey]) || empty($groupedSettings[$groupKey])) continue; ?>
        <?php
            $isCollapsed = !empty($groupInfo['collapsed']);
            $iconPath = $groupIcons[$groupInfo['icon'] ?? 'cog'] ?? $groupIcons['cog'];
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
             x-data="{ open: <?= $isCollapsed ? 'false' : 'true' ?> }">
            <!-- Group Header -->
            <button type="button"
                    @click="open = !open"
                    class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-lg bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center">
                        <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <?= $iconPath ?>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-900 dark:text-white"><?= e($groupInfo['label']) ?></h3>
                        <?php if (!empty($groupInfo['description'])): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($groupInfo['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Group Content -->
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($groupedSettings[$groupKey] as $key => $schema): ?>
                        <?php
                            $value = $currentSettings[$key] ?? ($schema['default'] ?? '');
                            $type = $schema['type'] ?? 'text';
                            $label = $schema['label'] ?? ucfirst(str_replace('_', ' ', $key));
                            $description = $schema['description'] ?? '';
                            $required = !empty($schema['required']);
                        ?>
                        <div class="<?= $type === 'checkbox' ? 'sm:col-span-2 lg:col-span-3' : '' ?>">
                            <?php if ($type === 'checkbox' || $type === 'boolean'): ?>
                            <!-- Checkbox - Full width toggle style -->
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors">
                                <input type="checkbox"
                                       name="<?= e($key) ?>"
                                       id="<?= e($key) ?>"
                                       value="1"
                                       <?= $value ? 'checked' : '' ?>
                                       class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                <div class="flex-1">
                                    <span class="block text-sm font-medium text-slate-900 dark:text-white"><?= e($label) ?></span>
                                    <?php if ($description): ?>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400"><?= e($description) ?></span>
                                    <?php endif; ?>
                                </div>
                            </label>

                            <?php elseif ($type === 'select'): ?>
                            <!-- Select -->
                            <label for="<?= e($key) ?>" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                <?= e($label) ?>
                            </label>
                            <select name="<?= e($key) ?>" id="<?= e($key) ?>"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm">
                                <?php foreach ($schema['options'] as $opt): ?>
                                <?php
                                    $optValue = is_array($opt) ? ($opt['value'] ?? $opt) : $opt;
                                    $optLabel = is_array($opt) ? ($opt['label'] ?? $optValue) : $optValue;
                                ?>
                                <option value="<?= e($optValue) ?>" <?= $value == $optValue ? 'selected' : '' ?>>
                                    <?= e($optLabel) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($description): ?>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= e($description) ?></p>
                            <?php endif; ?>

                            <?php elseif ($type === 'number'): ?>
                            <!-- Number -->
                            <label for="<?= e($key) ?>" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                <?= e($label) ?>
                            </label>
                            <input type="number"
                                   name="<?= e($key) ?>"
                                   id="<?= e($key) ?>"
                                   value="<?= e($value) ?>"
                                   <?php if (isset($schema['min'])): ?>min="<?= $schema['min'] ?>"<?php endif; ?>
                                   <?php if (isset($schema['max'])): ?>max="<?= $schema['max'] ?>"<?php endif; ?>
                                   <?php if (isset($schema['step'])): ?>step="<?= $schema['step'] ?>"<?php endif; ?>
                                   class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm">
                            <?php if ($description): ?>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= e($description) ?></p>
                            <?php endif; ?>

                            <?php elseif ($type === 'password'): ?>
                            <!-- Password -->
                            <label for="<?= e($key) ?>" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                <?= e($label) ?>
                            </label>
                            <div class="relative" x-data="{ show: false }">
                                <input :type="show ? 'text' : 'password'"
                                       name="<?= e($key) ?>"
                                       id="<?= e($key) ?>"
                                       value="<?= e($value) ?>"
                                       class="w-full px-3 py-2 pr-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent font-mono text-sm">
                                <button type="button"
                                        @click="show = !show"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="show" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            <?php if ($description): ?>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= e($description) ?></p>
                            <?php endif; ?>

                            <?php else: ?>
                            <!-- Text -->
                            <label for="<?= e($key) ?>" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                <?= e($label) ?>
                            </label>
                            <input type="text"
                                   name="<?= e($key) ?>"
                                   id="<?= e($key) ?>"
                                   value="<?= e($value) ?>"
                                   placeholder="<?= e($schema['placeholder'] ?? '') ?>"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm"
                                   <?= $required ? 'required' : '' ?>>
                            <?php if ($description): ?>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= e($description) ?></p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($ungroupedSettings)): ?>
        <!-- Ungrouped Settings (Generale) -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
             x-data="{ open: true }">
            <button type="button"
                    @click="open = !open"
                    class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                        <svg class="h-5 w-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <?= $groupIcons['cog'] ?>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-900 dark:text-white">Impostazioni Generali</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Altre impostazioni del modulo</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-collapse>
                <div class="px-6 pb-6 pt-2 border-t border-slate-200 dark:border-slate-700 space-y-4">
                    <?php foreach ($ungroupedSettings as $key => $schema): ?>
                    <?php
                        $value = $currentSettings[$key] ?? ($schema['default'] ?? '');
                        $type = $schema['type'] ?? 'text';
                        $label = $schema['label'] ?? ucfirst(str_replace('_', ' ', $key));
                        $description = $schema['description'] ?? '';
                        $required = !empty($schema['required']);
                        $adminOnly = !empty($schema['admin_only']);
                    ?>
                    <div class="<?= $adminOnly ? 'border-l-4 border-amber-400 pl-4' : '' ?>">
                        <?php if ($adminOnly): ?>
                        <span class="inline-block mb-2 px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                            Solo Admin
                        </span>
                        <?php endif; ?>

                        <label for="<?= e($key) ?>" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            <?= e($label) ?>
                            <?php if ($required): ?><span class="text-red-500">*</span><?php endif; ?>
                        </label>

                        <?php if ($type === 'select'): ?>
                        <select name="<?= e($key) ?>" id="<?= e($key) ?>"
                                class="w-full max-w-md px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <?php foreach ($schema['options'] as $opt): ?>
                            <?php
                                $optValue = is_array($opt) ? ($opt['value'] ?? $opt) : $opt;
                                $optLabel = is_array($opt) ? ($opt['label'] ?? $optValue) : $optValue;
                            ?>
                            <option value="<?= e($optValue) ?>" <?= $value == $optValue ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <?php elseif ($type === 'textarea'): ?>
                        <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="4"
                                  class="w-full max-w-2xl px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                  <?= $required ? 'required' : '' ?>><?= e($value) ?></textarea>

                        <?php elseif ($type === 'number'): ?>
                        <input type="number" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($value) ?>"
                               <?php if (isset($schema['min'])): ?>min="<?= $schema['min'] ?>"<?php endif; ?>
                               <?php if (isset($schema['max'])): ?>max="<?= $schema['max'] ?>"<?php endif; ?>
                               <?php if (isset($schema['step'])): ?>step="<?= $schema['step'] ?>"<?php endif; ?>
                               class="w-full max-w-xs px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                               <?= $required ? 'required' : '' ?>>

                        <?php elseif ($type === 'password'): ?>
                        <div class="relative max-w-md" x-data="{ show: false }">
                            <input :type="show ? 'text' : 'password'" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($value) ?>"
                                   class="w-full px-3 py-2 pr-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent font-mono text-sm"
                                   <?= $required ? 'required' : '' ?>>
                            <button type="button" @click="show = !show"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>

                        <?php elseif ($type === 'checkbox' || $type === 'boolean'): ?>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="<?= e($key) ?>" id="<?= e($key) ?>" value="1" <?= $value ? 'checked' : '' ?>
                                   class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                            <span class="text-sm text-slate-600 dark:text-slate-400"><?= e($description ?: 'Attivo') ?></span>
                        </label>

                        <?php else: ?>
                        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($value) ?>"
                               placeholder="<?= e($schema['placeholder'] ?? '') ?>"
                               class="w-full max-w-md px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                               <?= $required ? 'required' : '' ?>>
                        <?php endif; ?>

                        <?php if ($description && $type !== 'checkbox' && $type !== 'boolean'): ?>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= e($description) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form Actions -->
        <div class="flex items-center justify-between bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 px-6 py-4">
            <a href="<?= url('/admin/modules') ?>" class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                Torna alla lista moduli
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Salva Impostazioni
            </button>
        </div>
    </form>
    <?php endif; ?>

    <!-- Module Info Card -->
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Informazioni Modulo</h4>
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <dt class="text-slate-500 dark:text-slate-400">Versione</dt>
                <dd class="font-medium text-slate-900 dark:text-white"><?= e($module['version']) ?></dd>
            </div>
            <div>
                <dt class="text-slate-500 dark:text-slate-400">Slug</dt>
                <dd class="font-mono text-slate-900 dark:text-white"><?= e($module['slug']) ?></dd>
            </div>
            <div>
                <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                <dd>
                    <?php if ($module['is_active']): ?>
                    <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Attivo
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 text-slate-500">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                        Inattivo
                    </span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt class="text-slate-500 dark:text-slate-400">Impostazioni</dt>
                <dd class="font-medium text-slate-900 dark:text-white"><?= count($settingsSchema) ?></dd>
            </div>
        </dl>
    </div>
</div>
