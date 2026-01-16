<?php
$severityColors = [
    'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
    'notice' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'info' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
];

// Status code color
$statusCode = $page['status_code'] ?? 0;
if ($statusCode >= 200 && $statusCode < 300) {
    $statusColor = 'text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30';
} elseif ($statusCode >= 300 && $statusCode < 400) {
    $statusColor = 'text-blue-600 bg-blue-100 dark:bg-blue-900/30';
} elseif ($statusCode >= 400) {
    $statusColor = 'text-red-600 bg-red-100 dark:bg-red-900/30';
} else {
    $statusColor = 'text-slate-600 bg-slate-100 dark:bg-slate-700';
}
?>

<div class="space-y-6" x-data="{ activeTab: 'overview' }">
    <!-- Breadcrumb + Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/seo-audit') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">SEO Audit</a>
            <svg class="w-4 h-4" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/dashboard') ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
            <svg class="w-4 h-4" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/pages') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Pagine</a>
            <svg class="w-4 h-4" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white truncate max-w-xs">Dettaglio</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white truncate" title="<?= e($page['url'] ?? '') ?>">
                    <?= e(parse_url($page['url'] ?? '', PHP_URL_PATH) ?: '/') ?>
                </h1>
                <a href="<?= e($page['url'] ?? '#') ?>" target="_blank" class="text-sm text-slate-500 dark:text-slate-400 hover:text-primary-600 flex items-center gap-1 truncate">
                    <?= e($page['url'] ?? 'URL non disponibile') ?>
                    <svg class="w-3.5 h-3.5 flex-shrink-0" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-lg text-sm font-medium <?= $statusColor ?>">
                    HTTP <?= $statusCode ?>
                </span>
                <?php if ($page['is_indexable'] ?? false): ?>
                <span class="px-3 py-1 rounded-lg text-sm font-medium text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 flex items-center gap-1">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Indicizzabile
                </span>
                <?php else: ?>
                <span class="px-3 py-1 rounded-lg text-sm font-medium text-red-600 bg-red-100 dark:bg-red-900/30 flex items-center gap-1">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Non indicizzabile
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $page['load_time_ms'] ?? '-' ?><span class="text-sm font-normal text-slate-500">ms</span></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Tempo Caricamento</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($page['word_count'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Parole</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $page['internal_links_count'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Link Interni</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $page['images_count'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Immagini</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold <?= count($pageIssues) > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' ?>"><?= count($pageIssues) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Issues</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <!-- Tab Headers -->
        <div class="border-b border-slate-200 dark:border-slate-700">
            <nav class="flex overflow-x-auto">
                <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                    Panoramica
                </button>
                <button @click="activeTab = 'meta'" :class="activeTab === 'meta' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                    Meta Tags
                </button>
                <button @click="activeTab = 'headings'" :class="activeTab === 'headings' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                    Headings
                </button>
                <button @click="activeTab = 'images'" :class="activeTab === 'images' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                    Immagini (<?= count($imagesData) ?>)
                </button>
                <button @click="activeTab = 'links'" :class="activeTab === 'links' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                    Links
                </button>
                <button @click="activeTab = 'technical'" :class="activeTab === 'technical' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                    Tecnico
                </button>
                <button @click="activeTab = 'issues'" :class="activeTab === 'issues' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                    Issues (<?= count($pageIssues) ?>)
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Title & Description -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Title (<?= $page['title_length'] ?? 0 ?> caratteri)</label>
                            <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <p class="text-slate-900 dark:text-white <?= ($page['title_length'] ?? 0) > 60 ? 'text-yellow-600' : '' ?>"><?= e($page['title'] ?? 'Non presente') ?></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Meta Description (<?= $page['meta_description_length'] ?? 0 ?> caratteri)</label>
                            <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <p class="text-slate-900 dark:text-white text-sm <?= ($page['meta_description_length'] ?? 0) > 160 ? 'text-yellow-600' : '' ?>"><?= e($page['meta_description'] ?? 'Non presente') ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Info -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Canonical URL</label>
                            <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <p class="text-slate-900 dark:text-white text-sm truncate"><?= e($page['canonical_url'] ?? 'Non specificato') ?></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Meta Robots</label>
                            <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <p class="text-slate-900 dark:text-white text-sm"><?= e($page['meta_robots'] ?? 'Non specificato (default: index, follow)') ?></p>
                            </div>
                        </div>
                        <?php if (!($page['is_indexable'] ?? true) && !empty($page['indexability_reason'])): ?>
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-sm text-red-700 dark:text-red-300">
                                <strong>Non indicizzabile:</strong> <?= e($page['indexability_reason']) ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Meta Tags Tab -->
            <div x-show="activeTab === 'meta'" x-cloak>
                <div class="space-y-6">
                    <!-- OG Tags -->
                    <div>
                        <h3 class="font-medium text-slate-900 dark:text-white mb-3">Open Graph Tags</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">og:title</label>
                                <div class="p-2 bg-slate-50 dark:bg-slate-700/50 rounded text-sm"><?= e($page['og_title'] ?? 'Non presente') ?></div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">og:image</label>
                                <div class="p-2 bg-slate-50 dark:bg-slate-700/50 rounded text-sm truncate"><?= e($page['og_image'] ?? 'Non presente') ?></div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-500 mb-1">og:description</label>
                                <div class="p-2 bg-slate-50 dark:bg-slate-700/50 rounded text-sm"><?= e($page['og_description'] ?? 'Non presente') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Headings Tab -->
            <div x-show="activeTab === 'headings'" x-cloak>
                <div class="space-y-4">
                    <div class="flex flex-wrap gap-4 mb-4">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <div class="px-4 py-2 bg-slate-100 dark:bg-slate-700 rounded-lg">
                            <span class="text-lg font-bold text-slate-900 dark:text-white">H<?= $i ?>:</span>
                            <span class="text-slate-600 dark:text-slate-400"><?= $page["h{$i}_count"] ?? 0 ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <?php if (!empty($h1Texts)): ?>
                    <div>
                        <h3 class="font-medium text-slate-900 dark:text-white mb-2">Tag H1</h3>
                        <ul class="space-y-2">
                            <?php foreach ($h1Texts as $h1): ?>
                            <li class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg text-slate-900 dark:text-white">
                                <?= e(is_string($h1) ? $h1 : (string)$h1) ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">Nessun tag H1 trovato nella pagina.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Images Tab -->
            <div x-show="activeTab === 'images'" x-cloak>
                <?php if (empty($imagesData)): ?>
                <p class="text-slate-500 dark:text-slate-400">Nessuna immagine trovata.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="text-left py-2 px-3 font-medium text-slate-500">Sorgente</th>
                                <th class="text-left py-2 px-3 font-medium text-slate-500">Alt</th>
                                <th class="text-center py-2 px-3 font-medium text-slate-500">Stato</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php foreach (array_slice($imagesData, 0, 50) as $img): ?>
                            <tr>
                                <td class="py-2 px-3 max-w-xs truncate text-slate-600 dark:text-slate-300" title="<?= e($img['src'] ?? '') ?>"><?= e($img['src'] ?? '') ?></td>
                                <td class="py-2 px-3 max-w-xs truncate text-slate-600 dark:text-slate-300"><?= e(($img['alt'] ?? '') ?: '-') ?></td>
                                <td class="py-2 px-3 text-center">
                                    <?php if (empty($img['alt'] ?? '')): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">Alt mancante</span>
                                    <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">OK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($imagesData) > 50): ?>
                    <p class="mt-4 text-sm text-slate-500">Mostrate 50 di <?= count($imagesData) ?> immagini.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Links Tab -->
            <div x-show="activeTab === 'links'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Internal Links -->
                    <div>
                        <h3 class="font-medium text-slate-900 dark:text-white mb-3">Link Interni (<?= count($linksData['internal'] ?? []) ?>)</h3>
                        <?php if (empty($linksData['internal'])): ?>
                        <p class="text-slate-500">Nessun link interno.</p>
                        <?php else: ?>
                        <div class="max-h-64 overflow-y-auto space-y-2">
                            <?php foreach (array_slice($linksData['internal'] ?? [], 0, 30) as $link): ?>
                            <div class="p-2 bg-slate-50 dark:bg-slate-700/50 rounded text-sm">
                                <p class="text-slate-900 dark:text-white truncate" title="<?= e($link['url'] ?? '') ?>"><?= e($link['url'] ?? '') ?></p>
                                <p class="text-xs text-slate-500"><?= e(($link['text'] ?? '') ?: '[nessun testo]') ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- External Links -->
                    <div>
                        <h3 class="font-medium text-slate-900 dark:text-white mb-3">Link Esterni (<?= count($linksData['external'] ?? []) ?>)</h3>
                        <?php if (empty($linksData['external'])): ?>
                        <p class="text-slate-500">Nessun link esterno.</p>
                        <?php else: ?>
                        <div class="max-h-64 overflow-y-auto space-y-2">
                            <?php foreach (array_slice($linksData['external'] ?? [], 0, 30) as $link): ?>
                            <div class="p-2 bg-slate-50 dark:bg-slate-700/50 rounded text-sm">
                                <p class="text-slate-900 dark:text-white truncate" title="<?= e($link['url'] ?? '') ?>"><?= e($link['url'] ?? '') ?></p>
                                <p class="text-xs text-slate-500"><?= e(($link['text'] ?? '') ?: '[nessun testo]') ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Technical Tab -->
            <div x-show="activeTab === 'technical'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Schema Markup</label>
                            <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <?php if (($page['has_schema'] ?? false) && !empty($schemaTypes)): ?>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($schemaTypes as $type): ?>
                                    <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded text-xs"><?= e(is_string($type) ? $type : (is_array($type) ? ($type['@type'] ?? json_encode($type)) : (string)$type)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-slate-500 text-sm">Nessuno schema markup trovato</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Hreflang Tags</label>
                            <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <?php if (!empty($hreflangTags)): ?>
                                <ul class="space-y-1 text-sm">
                                    <?php foreach ($hreflangTags as $key => $tag): ?>
                                    <?php if (is_array($tag)): ?>
                                    <li><span class="font-medium"><?= e($tag['lang'] ?? $key) ?>:</span> <?= e($tag['url'] ?? '') ?></li>
                                    <?php else: ?>
                                    <li><span class="font-medium"><?= e(is_string($key) ? $key : '') ?>:</span> <?= e(is_string($tag) ? $tag : '') ?></li>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                                <?php else: ?>
                                <p class="text-slate-500 text-sm">Nessun tag hreflang</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Dimensione Pagina</label>
                            <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg text-sm">
                                <?= number_format(($page['content_length'] ?? 0) / 1024, 1) ?> KB
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Data Scansione</label>
                            <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg text-sm">
                                <?= !empty($page['crawled_at']) ? date('d/m/Y H:i', strtotime($page['crawled_at'])) : '-' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Issues Tab -->
            <div x-show="activeTab === 'issues'" x-cloak>
                <?php if (empty($pageIssues)): ?>
                <div class="text-center py-8">
                    <div class="mx-auto h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-3">
                        <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400">Nessun problema rilevato per questa pagina.</p>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($pageIssues as $issue): ?>
                    <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $severityColors[$issue['severity'] ?? 'info'] ?? $severityColors['info'] ?>">
                                <?= ucfirst($issue['severity'] ?? 'info') ?>
                            </span>
                            <div class="flex-1">
                                <p class="font-medium text-slate-900 dark:text-white"><?= e($issue['title'] ?? '') ?></p>
                                <?php if (!empty($issue['affected_element'])): ?>
                                <code class="mt-1 text-xs bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-slate-600 dark:text-slate-300 block max-w-full overflow-x-auto">
                                    <?= e($issue['affected_element']) ?>
                                </code>
                                <?php endif; ?>
                                <?php if (!empty($issue['recommendation'])): ?>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400"><?= e($issue['recommendation']) ?></p>
                                <?php endif; ?>
                            </div>
                            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . ($issue['category'] ?? 'technical')) ?>" class="text-xs text-slate-400 hover:text-primary-600">
                                <?= \Modules\SeoAudit\Models\Issue::CATEGORIES[$issue['category'] ?? ''] ?? ($issue['category'] ?? 'Altro') ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>
