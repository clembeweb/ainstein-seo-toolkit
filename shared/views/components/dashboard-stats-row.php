<?php
/**
 * Dashboard Stats Row Component
 * Renders a responsive grid of KPI cards.
 *
 * Params:
 * @param array $cards - Array of card params, each passed to dashboard-kpi-card
 *   Each card: ['label' => '...', 'value' => N, 'icon' => '...', 'color' => '...', 'url' => '...']
 * @param string $dataTour - (optional) data-tour attribute for tour targeting
 */

$cards = $cards ?? [];
$dataTour = $dataTour ?? null;
$count = count($cards);
$cols = min($count, 4);

$gridClass = match($cols) {
    1 => 'grid-cols-1',
    2 => 'grid-cols-2',
    3 => 'grid-cols-2 md:grid-cols-3',
    default => 'grid-cols-2 md:grid-cols-4',
};
?>
<div class="grid <?= $gridClass ?> gap-4"<?= $dataTour ? ' data-tour="' . htmlspecialchars($dataTour) . '"' : '' ?>>
    <?php foreach ($cards as $card): ?>
        <?= \Core\View::partial('components/dashboard-kpi-card', $card) ?>
    <?php endforeach; ?>
</div>
