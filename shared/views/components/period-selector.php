<?php
/**
 * Period Selector Component — Bottoni periodo riutilizzabili.
 *
 * Uso:
 *   View::partial('components/period-selector', [
 *       'periods'    => [7 => '7gg', 30 => '30gg', 90 => '90gg'],
 *       'selected'   => 30,
 *       'baseUrl'    => $baseUrl,          // URL base per link (append ?period=X)
 *       'alpineVar'  => 'dateRange',       // (opzionale) Alpine.js variable to bind
 *       'alpineClick'=> 'setRange',        // (opzionale) Alpine.js method to call on click
 *       'showCustom' => false,             // (opzionale) Mostra bottone "Personalizzato"
 *       'color'      => 'blue',            // (opzionale) Colore attivo: blue|rose|emerald|purple
 *   ])
 *
 * Modalità:
 *   - Con $baseUrl: genera <a> links con ?period=X
 *   - Con $alpineVar + $alpineClick: genera <button> con Alpine.js bindings
 *   - Fallback: genera <button> con data-period attribute
 *
 * @var array  $periods     Associative array [days => label]
 * @var int    $selected    Currently selected period (days)
 * @var string $baseUrl     (optional) Base URL for link mode
 * @var string $alpineVar   (optional) Alpine.js variable name
 * @var string $alpineClick (optional) Alpine.js click handler function name
 * @var bool   $showCustom  (optional) Show custom date button
 * @var string $color       (optional) Active button color
 */

$periods = $periods ?? [7 => '7gg', 30 => '30gg', 90 => '90gg'];
$selected = $selected ?? 30;
$baseUrl = $baseUrl ?? null;
$alpineVar = $alpineVar ?? null;
$alpineClick = $alpineClick ?? null;
$showCustom = $showCustom ?? false;
$color = $color ?? 'blue';

$activeColors = [
    'blue'    => 'bg-blue-600 text-white',
    'rose'    => 'bg-rose-600 text-white',
    'emerald' => 'bg-emerald-600 text-white',
    'purple'  => 'bg-purple-600 text-white',
    'amber'   => 'bg-amber-600 text-white',
];
$activeClass = $activeColors[$color] ?? $activeColors['blue'];
$inactiveClass = 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700';

$useAlpine = $alpineVar && $alpineClick;
?>
<div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
    <?php foreach ($periods as $days => $label): ?>
    <?php if ($useAlpine): ?>
    <button @click="<?= $alpineClick ?>(<?= (int)$days ?>)"
            :class="<?= $alpineVar ?> == <?= (int)$days ?> ? '<?= $activeClass ?>' : '<?= $inactiveClass ?>'"
            class="px-3 py-1.5 text-xs font-medium transition-colors<?= $days !== array_key_first($periods) ? ' border-l border-slate-200 dark:border-slate-700' : '' ?>">
        <?= htmlspecialchars($label) ?>
    </button>
    <?php elseif ($baseUrl): ?>
    <a href="<?= htmlspecialchars($baseUrl) ?><?= str_contains($baseUrl, '?') ? '&' : '?' ?>period=<?= (int)$days ?>"
       class="px-3 py-1.5 text-xs font-medium transition-colors <?= (int)$selected === (int)$days ? $activeClass : $inactiveClass ?><?= $days !== array_key_first($periods) ? ' border-l border-slate-200 dark:border-slate-700' : '' ?>">
        <?= htmlspecialchars($label) ?>
    </a>
    <?php else: ?>
    <button data-period="<?= (int)$days ?>"
            class="period-btn px-3 py-1.5 text-xs font-medium transition-colors <?= (int)$selected === (int)$days ? $activeClass : $inactiveClass ?><?= $days !== array_key_first($periods) ? ' border-l border-slate-200 dark:border-slate-700' : '' ?>">
        <?= htmlspecialchars($label) ?>
    </button>
    <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($showCustom): ?>
    <?php if ($useAlpine): ?>
    <button @click="showCustom = !showCustom; <?= $alpineVar ?> = 'custom'"
            :class="<?= $alpineVar ?> == 'custom' ? '<?= $activeClass ?>' : '<?= $inactiveClass ?>'"
            class="px-3 py-1.5 text-xs font-medium border-l border-slate-200 dark:border-slate-700 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
    </button>
    <?php else: ?>
    <button class="period-btn px-3 py-1.5 text-xs font-medium border-l border-slate-200 dark:border-slate-700 transition-colors <?= $inactiveClass ?>" data-period="custom">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
    </button>
    <?php endif; ?>
    <?php endif; ?>
</div>
