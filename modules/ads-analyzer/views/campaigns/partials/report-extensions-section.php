<?php
/**
 * Report Extensions Section — Estensioni filtrate per campagna
 *
 * Variables from parent scope (report-campaign-table.php router):
 *   $camp, $syncCamp, $aiCamp, $cIdx, $canEdit
 *   $syncedExtensions, $viewCampaigns
 *   Helper functions: formatNum()
 */

$campIdGoogle = $syncCamp['campaign_id_google'] ?? '';

// Filter extensions for this campaign
$campExtensions = array_filter($syncedExtensions ?? [], fn($ext) =>
    ($ext['campaign_id_google'] ?? '') === $campIdGoogle
);

// Group by type
$extByType = [];
foreach ($campExtensions as $ext) {
    $type = $ext['extension_type'] ?? 'UNKNOWN';
    $extByType[$type][] = $ext;
}

// AI extension evaluation (if available)
$extEval = $aiCamp['extensions_evaluation'] ?? [];
$extMissing = $extEval['missing'] ?? [];
$extSuggestions = $extEval['suggestions'] ?? [];
$extScore = (float)($extEval['score'] ?? 0);
?>

<?php if (!empty($extByType) || !empty($extMissing)): ?>
<div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="px-4 py-2 bg-slate-50 dark:bg-slate-700/30 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
        <!-- Heroicon: puzzle-piece -->
        <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.401.604-.401.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.959.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z"/>
        </svg>
        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Estensioni (<?= count($campExtensions) ?>)</span>
        <?php if ($extScore > 0): ?>
        <span class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= $extScore >= 7 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : ($extScore >= 4 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300') ?>">
            <?= formatNum($extScore, 0) ?>/10
        </span>
        <?php endif; ?>
    </div>
    <div class="p-3 space-y-3">

        <!-- Grid of extension types -->
        <?php if (!empty($extByType)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            <?php foreach ($extByType as $extType => $exts): ?>
            <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-3 py-1.5 bg-slate-50 dark:bg-slate-700/30 border-b border-slate-100 dark:border-slate-700/50">
                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400"><?= e(ucfirst(strtolower(str_replace('_', ' ', $extType)))) ?></span>
                    <span class="text-[10px] text-slate-400 ml-1">(<?= count($exts) ?>)</span>
                </div>
                <div class="p-2 space-y-1">
                    <?php foreach (array_slice($exts, 0, 5) as $ext): ?>
                    <?php
                        $extClicks = (int)($ext['clicks'] ?? 0);
                        $extImpr = (int)($ext['impressions'] ?? 0);
                        $noImpressions = $extImpr === 0;
                    ?>
                    <div class="flex items-center justify-between text-xs <?= $noImpressions ? 'text-red-600 dark:text-red-400' : 'text-slate-600 dark:text-slate-400' ?>">
                        <span class="truncate max-w-[180px]" title="<?= e($ext['extension_text'] ?? '') ?>"><?= e(mb_strimwidth($ext['extension_text'] ?? '-', 0, 35, '...')) ?></span>
                        <span class="text-[10px] ml-2 whitespace-nowrap flex-shrink-0">
                            <?php if ($noImpressions): ?>
                            <span class="text-red-500 font-medium">0 impr.</span>
                            <?php else: ?>
                            <?= $extClicks ?> cl / <?= $extImpr ?> impr.
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($exts) > 5): ?>
                    <div class="text-[10px] text-slate-400">+<?= count($exts) - 5 ?> altre</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Missing extensions -->
        <?php if (!empty($extMissing)): ?>
        <div class="flex flex-wrap gap-1.5">
            <span class="text-[10px] text-slate-500 font-medium">Mancanti:</span>
            <?php foreach ($extMissing as $m): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300"><?= e($m) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- AI Suggestions -->
        <?php if (!empty($extSuggestions)): ?>
        <div>
            <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Suggerimenti AI</p>
            <ul class="space-y-1">
                <?php foreach ($extSuggestions as $sug): ?>
                <li class="flex items-start gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                    <svg class="w-3 h-3 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <?= e(is_string($sug) ? $sug : ($sug['text'] ?? '')) ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Generate extensions button -->
        <?php
        $extIssueKey = null;
        foreach (($aiCamp['issues'] ?? []) as $iiIdx => $issue) {
            if (($issue['fix_type'] ?? '') === 'add_extensions') {
                $extIssueKey = "cissue_{$cIdx}_{$iiIdx}";
                break;
            }
        }
        ?>
        <?php if ($extIssueKey && $canEdit): ?>
        <div class="pt-2 border-t border-slate-200 dark:border-slate-700">
            <div x-show="!generators['<?= $extIssueKey ?>']?.result">
                <button @click="generateFix('add_extensions', <?= e(json_encode(['campaign_name' => $camp['campaign_name'] ?? '', 'missing' => $extMissing])) ?>, '<?= $extIssueKey ?>')"
                        :class="(generators['<?= $extIssueKey ?>'] || {}).loading ? 'opacity-50 cursor-not-allowed' : ''"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-orange-100 text-orange-800 hover:bg-orange-200 dark:bg-orange-900/30 dark:text-orange-300 dark:hover:bg-orange-900/50 transition-colors disabled:opacity-50">
                    <template x-if="generators['<?= $extIssueKey ?>']?.loading">
                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    </template>
                    <template x-if="!generators['<?= $extIssueKey ?>']?.loading">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                    </template>
                    <span x-text="generators['<?= $extIssueKey ?>']?.loading ? 'Generazione...' : 'Genera Estensioni Mancanti (1 credito)'"></span>
                </button>
                <p class="text-[10px] text-slate-400 mt-1">Genera sitelink, callout e snippet strutturati per questa campagna</p>
            </div>
            <!-- Result -->
            <template x-if="generators['<?= $extIssueKey ?>']?.result">
                <div class="mt-2 p-3 bg-emerald-50 dark:bg-emerald-900/10 rounded-lg border border-emerald-200 dark:border-emerald-900/30 text-xs">
                    <div class="text-[10px] font-medium text-emerald-700 dark:text-emerald-300 mb-2">Estensioni Generate</div>
                    <div class="whitespace-pre-wrap text-slate-700 dark:text-slate-300 leading-relaxed" x-text="generators['<?= $extIssueKey ?>']?.result"></div>
                    <div class="flex gap-2 mt-2 pt-2 border-t border-emerald-200 dark:border-emerald-900/30">
                        <button @click="copyResult('<?= $extIssueKey ?>')" class="text-[10px] text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                            <span x-text="generators['<?= $extIssueKey ?>']?.copied ? 'Copiato!' : 'Copia'"></span>
                        </button>
                        <button @click="exportCsv('<?= $extIssueKey ?>')" class="text-[10px] text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">CSV Ads Editor</button>
                        <button @click="openApplyModal('<?= $extIssueKey ?>')" class="text-[10px] text-rose-600 hover:text-rose-700 dark:text-rose-400 font-medium">Applica su Google Ads</button>
                    </div>
                </div>
            </template>
            <template x-if="generators['<?= $extIssueKey ?>']?.error">
                <p class="text-xs text-red-500 mt-1" x-text="generators['<?= $extIssueKey ?>']?.error"></p>
            </template>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
