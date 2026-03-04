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

if (count($countries) < 2) return; // Single country: no bar needed
?>

<!-- Country Summary Cards -->
<div class="flex flex-wrap gap-3 mb-4">
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
</div>

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
