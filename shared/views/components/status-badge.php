<?php
/**
 * Status Badge Component — Palette standard per badge stato.
 *
 * Uso:
 *   View::partial('components/status-badge', ['type' => 'success', 'label' => 'Completato'])
 *   View::partial('components/status-badge', ['type' => 'error', 'label' => 'Errore', 'icon' => true])
 *
 * Tipi disponibili:
 *   success  → emerald (completato, attivo, pubblicato)
 *   error    → red (errore, fallito)
 *   warning  → amber (attenzione, in pausa manuale)
 *   info     → blue (in corso, in elaborazione)
 *   pending  → slate (in attesa, bozza)
 *   paused   → gray (disabilitato, sospeso)
 *
 * @var string $type   Tipo badge (default: 'info')
 * @var string $label  Testo del badge
 * @var bool   $icon   Mostrare icona accanto al testo (default: false)
 * @var string $size   'sm' o 'md' (default: 'sm')
 * @var string $extra  Classi CSS extra (default: '')
 */

$palettes = [
    'success' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    'error'   => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    'info'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'pending' => 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300',
    'paused'  => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
];

$icons = [
    'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'error'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    'info'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'pending' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'paused'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
];

$type = $type ?? 'info';
$label = $label ?? '';
$showIcon = $icon ?? false;
$size = $size ?? 'sm';
$extra = $extra ?? '';

$colorClass = $palettes[$type] ?? $palettes['info'];
$sizeClass = $size === 'md' ? 'px-3 py-1 text-sm' : 'px-2.5 py-0.5 text-xs';
?>
<span class="inline-flex items-center rounded-full font-medium <?= $sizeClass ?> <?= $colorClass ?> <?= $extra ?>">
    <?php if ($showIcon && isset($icons[$type])): ?>
    <svg class="w-3.5 h-3.5 mr-1 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <?= $icons[$type] ?>
    </svg>
    <?php endif; ?>
    <?= e($label) ?>
</span>
