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

$countryFlags = [
    'IT' => "\xF0\x9F\x87\xAE\xF0\x9F\x87\xB9", 'US' => "\xF0\x9F\x87\xBA\xF0\x9F\x87\xB8",
    'GB' => "\xF0\x9F\x87\xAC\xF0\x9F\x87\xA7", 'DE' => "\xF0\x9F\x87\xA9\xF0\x9F\x87\xAA",
    'FR' => "\xF0\x9F\x87\xAB\xF0\x9F\x87\xB7", 'ES' => "\xF0\x9F\x87\xAA\xF0\x9F\x87\xB8",
    'CH' => "\xF0\x9F\x87\xA8\xF0\x9F\x87\xAD", 'AT' => "\xF0\x9F\x87\xA6\xF0\x9F\x87\xB9",
    'NL' => "\xF0\x9F\x87\xB3\xF0\x9F\x87\xB1", 'BE' => "\xF0\x9F\x87\xA7\xF0\x9F\x87\xAA",
    'PT' => "\xF0\x9F\x87\xB5\xF0\x9F\x87\xB9", 'BR' => "\xF0\x9F\x87\xA7\xF0\x9F\x87\xB7",
    'CA' => "\xF0\x9F\x87\xA8\xF0\x9F\x87\xA6", 'AU' => "\xF0\x9F\x87\xA6\xF0\x9F\x87\xBA",
    'MX' => "\xF0\x9F\x87\xB2\xF0\x9F\x87\xBD", 'AR' => "\xF0\x9F\x87\xA6\xF0\x9F\x87\xB7",
    'CL' => "\xF0\x9F\x87\xA8\xF0\x9F\x87\xB1", 'CO' => "\xF0\x9F\x87\xA8\xF0\x9F\x87\xB4",
    'PL' => "\xF0\x9F\x87\xB5\xF0\x9F\x87\xB1", 'SE' => "\xF0\x9F\x87\xB8\xF0\x9F\x87\xAA",
];

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
        $flag = $countryFlags[$code] ?? "\xF0\x9F\x8C\x90";
        $isActive = $activeCountry === $code;
    ?>
    <a href="<?= $baseUrl ?>?country=<?= e($code) ?>"
       class="flex items-center gap-2 px-3 py-2 rounded-lg border transition-all text-sm
              <?= $isActive
                  ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-300 dark:border-blue-700 ring-1 ring-blue-200 dark:ring-blue-800'
                  : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-700' ?>">
        <span class="text-base"><?= $flag ?></span>
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
        $flag = $countryFlags[$code] ?? "\xF0\x9F\x8C\x90";
        $isActive = $activeCountry === $code;
    ?>
    <a href="<?= $baseUrl ?>?country=<?= e($code) ?>"
       class="px-3 py-2 text-sm font-medium rounded-t-lg transition-colors flex items-center gap-1
              <?= $isActive
                  ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 -mb-px'
                  : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' ?>">
        <span class="text-xs"><?= $flag ?></span>
        <?= e($code) ?>
    </a>
    <?php endforeach; ?>
</div>
