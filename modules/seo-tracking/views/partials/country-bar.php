<?php
/**
 * Country bar — summary cards + country tabs.
 *
 * Variables:
 * - $countries: array from Keyword::getActiveCountries() enriched with metrics
 * - $activeCountry: string|null (null = All)
 * - $project: array (project data)
 * - $currentPage: string (for URL building)
 */

// Flag images via flagcdn.com (emoji don't render on Windows)
// flagcdn sizes: w20, w40, w80, w160, w320
function countryFlagImg(string $code, int $displayW = 20): string {
    $lc = strtolower($code);
    return '<img src="https://flagcdn.com/w20/' . e($lc) . '.png" '
         . 'srcset="https://flagcdn.com/w40/' . e($lc) . '.png 2x" '
         . 'width="' . $displayW . '" alt="' . e($code) . '" class="inline-block rounded-sm" loading="lazy">';
}

// Build base URL for current page
$pageRoutes = [
    'overview' => '/seo-tracking/project/' . $project['id'],
    'keywords' => '/seo-tracking/project/' . $project['id'] . '/keywords',
    'urls' => '/seo-tracking/project/' . $project['id'] . '/urls',
    'groups' => '/seo-tracking/project/' . $project['id'] . '/groups',
];
$baseUrl = url($pageRoutes[$currentPage] ?? $pageRoutes['overview']);

if (count($countries) === 0) return; // Nessuna keyword ancora: la barra non ha niente da mostrare

// Barra sempre visibile, anche con un solo paese (prima compariva solo da 2 in su e nessuno scopriva il multi-paese):
// "+ Aggiungi paese" elenca i paesi non ancora usati e apre l'aggiunta keyword già impostata su quello.
$usedCodes = array_map(fn($c) => $c['country_code'] ?? $c['location_code'] ?? '', $countries);
$otherLocations = array_values(array_filter(
    (new \Modules\SeoTracking\Models\Location())->all(),
    fn($l) => !in_array($l['country_code'], $usedCodes, true)
));
$addKeywordUrl = url('/seo-tracking/project/' . $project['id'] . '/keywords/add');
$canEdit = ($project['access_role'] ?? 'owner') !== 'viewer';
?>

<!-- Country Summary Cards -->
<div class="flex flex-wrap items-center gap-3 mb-4">
    <?php foreach ($countries as $c):
        $code = $c['country_code'] ?? $c['location_code'] ?? '';
        $isActive = $activeCountry === $code;
    ?>
    <a href="<?= $baseUrl ?>?country=<?= e($code) ?>"
       class="flex items-center gap-2 px-3 py-2 rounded-lg border transition-all text-sm
              <?= $isActive
                  ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-300 dark:border-blue-700 ring-1 ring-blue-200 dark:ring-blue-800'
                  : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-700' ?>">
        <?= countryFlagImg($code, 20) ?>
        <span class="font-semibold text-slate-900 dark:text-white"><?= e($code) ?></span>
        <span class="text-slate-500 dark:text-slate-400"><?= number_format($c['visibility'] ?? 0, 1) ?>%</span>
        <span class="text-xs text-slate-400 dark:text-slate-500"><?= $c['keyword_count'] ?> kw</span>
    </a>
    <?php endforeach; ?>

    <?php if ($canEdit && $otherLocations): ?>
    <div x-data="{ open: false }" class="relative">
        <button type="button" @click="open = !open" @click.outside="open = false"
                class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-dashed border-slate-300 dark:border-slate-600 text-sm text-slate-600 dark:text-slate-300 hover:border-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                title="Segui lo stesso sito anche in un altro paese: le keyword si aggiungono con il Google di quel paese">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Aggiungi paese
        </button>
        <div x-show="open" x-cloak x-transition class="absolute left-0 z-20 mt-1 w-64 max-h-72 overflow-y-auto rounded-lg bg-white dark:bg-slate-800 shadow-lg ring-1 ring-slate-200 dark:ring-slate-700 py-1">
            <p class="px-3 py-1.5 text-xs text-slate-500 dark:text-slate-400">Le keyword del nuovo paese le aggiungi subito dopo.</p>
            <?php foreach ($otherLocations as $l): ?>
            <a href="<?= $addKeywordUrl ?>?country=<?= e($l['country_code']) ?>" class="flex items-center gap-2 px-3 py-1.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">
                <?= countryFlagImg($l['country_code'], 16) ?> <?= e($l['name']) ?> <span class="text-xs text-slate-400"><?= e($l['country_code']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (count($countries) >= 2): ?>
<!-- Country Tabs -->
<div class="flex items-center gap-1 mb-4 border-b border-slate-200 dark:border-slate-700 pb-px">
    <a href="<?= $baseUrl ?>"
       class="px-3 py-2 text-sm font-medium rounded-t-lg transition-colors
              <?= $activeCountry === null
                  ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 -mb-px'
                  : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' ?>">
        Tutti
    </a>
    <?php foreach ($countries as $c):
        $code = $c['country_code'] ?? $c['location_code'] ?? '';
        $isActive = $activeCountry === $code;
    ?>
    <a href="<?= $baseUrl ?>?country=<?= e($code) ?>"
       class="px-3 py-2 text-sm font-medium rounded-t-lg transition-colors flex items-center gap-1
              <?= $isActive
                  ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 -mb-px'
                  : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' ?>">
        <?= countryFlagImg($code, 16) ?>
        <?= e($code) ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
