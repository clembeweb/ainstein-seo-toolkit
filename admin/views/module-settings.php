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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessuna impostazione disponibile</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
            Questo modulo non ha impostazioni configurabili nel file <code class="bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">module.json</code>.
        </p>
    </div>
    <?php else: ?>
    <!-- Settings Form -->
    <form action="<?= url('/admin/modules/' . $module['id'] . '/settings') ?>" method="POST">
        <?= csrf_field() ?>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 space-y-6">
                <?php foreach ($settingsSchema as $key => $schema): ?>
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
                        <?php if ($required): ?>
                        <span class="text-red-500">*</span>
                        <?php endif; ?>
                    </label>

                    <?php if ($type === 'select'): ?>
                    <!-- Select -->
                    <select name="<?= e($key) ?>" id="<?= e($key) ?>"
                            class="w-full max-w-md px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            <?= $required ? 'required' : '' ?>>
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

                    <?php elseif ($type === 'textarea'): ?>
                    <!-- Textarea -->
                    <textarea name="<?= e($key) ?>" id="<?= e($key) ?>"
                              rows="4"
                              class="w-full max-w-2xl px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                              <?= $required ? 'required' : '' ?>><?= e($value) ?></textarea>

                    <?php elseif ($type === 'number'): ?>
                    <!-- Number -->
                    <input type="number"
                           name="<?= e($key) ?>"
                           id="<?= e($key) ?>"
                           value="<?= e($value) ?>"
                           <?php if (isset($schema['min'])): ?>min="<?= $schema['min'] ?>"<?php endif; ?>
                           <?php if (isset($schema['max'])): ?>max="<?= $schema['max'] ?>"<?php endif; ?>
                           <?php if (isset($schema['step'])): ?>step="<?= $schema['step'] ?>"<?php endif; ?>
                           class="w-full max-w-xs px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           <?= $required ? 'required' : '' ?>>

                    <?php elseif ($type === 'password'): ?>
                    <!-- Password (API Key) -->
                    <div class="relative max-w-md" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'"
                               name="<?= e($key) ?>"
                               id="<?= e($key) ?>"
                               value="<?= e($value) ?>"
                               placeholder="<?= e($schema['placeholder'] ?? '') ?>"
                               class="w-full px-3 py-2 pr-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent font-mono text-sm"
                               <?= $required ? 'required' : '' ?>>
                        <button type="button"
                                @click="show = !show"
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
                    <!-- Checkbox -->
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="<?= e($key) ?>"
                               id="<?= e($key) ?>"
                               value="1"
                               <?= $value ? 'checked' : '' ?>
                               class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-slate-600 dark:text-slate-400"><?= e($description ?: 'Attivo') ?></span>
                    </label>

                    <?php else: ?>
                    <!-- Default: Text input -->
                    <input type="text"
                           name="<?= e($key) ?>"
                           id="<?= e($key) ?>"
                           value="<?= e($value) ?>"
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

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
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
