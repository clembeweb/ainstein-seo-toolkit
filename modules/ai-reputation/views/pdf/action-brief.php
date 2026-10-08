<?php
/** Scheda operativa nel PDF. Variabili dal padre: $a (con brief_data, outlets), $h, $channelLabels. */
$b = $a['brief_data'] ?? null;
if (!is_array($b) || !in_array($b['kind'] ?? '', ['content', 'removal'], true)) {
    echo '<div style="margin-top:6pt;font-size:9pt;color:#94a3b8;border-top:0.5pt dashed #e2e8f0;padding-top:4pt;">Scheda operativa non generata</div>';
    return;
}
$outlets = is_array($a['outlets'] ?? null) ? $a['outlets'] : [];
$requestLabel = ['removal' => 'Rimozione della pagina', 'deindex' => 'Deindicizzazione', 'update' => 'Aggiornamento del contenuto'];
$label = fn(string $t) => '<span style="font-weight:bold;color:#3730a3;">' . $h($t) . ':</span> ';
$ul = function (array $items) use ($h): string {
    if (!$items) { return ''; }
    $out = '<ul style="margin:1pt 0 3pt 12pt;padding:0;">';
    foreach ($items as $i) { $out .= '<li style="margin:0 0 1pt;">' . $h($i) . '</li>'; }
    return $out . '</ul>';
};
$points = (array) ($b['points'] ?? []);
$facts = (array) ($b['facts'] ?? []);
$avoid = (array) ($b['avoid'] ?? []);
$pages = (array) ($b['pages'] ?? []);
$generatedAt = strtotime((string) ($a['brief_generated_at'] ?? ''));
?>
<div style="margin-top:6pt;border-top:0.5pt dashed #c7d2fe;padding-top:5pt;font-size:9.5pt;color:#1e293b;">
    <div style="font-size:9pt;font-weight:bold;color:#4f46e5;margin-bottom:3pt;">SCHEDA OPERATIVA</div>
    <?php if ($b['kind'] === 'content'): ?>
    <div><?= $label('Dove') ?><?= $h($channelLabels[$a['channel'] ?? ''] ?? ($a['channel'] ?? '')) ?><?php if ($outlets): ?> — testate: <?= $h(implode(', ', $outlets)) ?><?php endif; ?></div>
    <?php if (!empty($a['channel_rationale'])): ?><div style="color:#475569;margin-bottom:3pt;"><?= $h($a['channel_rationale']) ?></div><?php endif; ?>
    <div><?= $label('Titolo proposto') ?><?= $h($b['title'] ?? '') ?></div>
    <?php if (!empty($b['angle'])): ?><div><?= $label('Taglio') ?><?= $h($b['angle']) ?></div><?php endif; ?>
    <div><?= $label('Punti da coprire') ?><?= $ul($points) ?></div>
    <?php if ($facts): ?><div><?= $label('Fatti da citare') ?><?= $ul(array_map(fn($f) => (string) ($f['fact'] ?? '') . (!empty($f['source']) ? ' (fonte: ' . $f['source'] . ')' : ''), $facts)) ?></div><?php endif; ?>
    <?php if ($avoid): ?><div><?= $label('Da evitare') ?><?= $ul($avoid) ?></div><?php endif; ?>
    <div><?= $label('Lunghezza') ?><?= !empty($b['length_words']) ? (int) $b['length_words'] . ' parole' : 'a discrezione' ?> · <?= $label('Lingua') ?><?= $h(strtoupper((string) ($b['language'] ?? ''))) ?></div>
    <?php if (!empty($b['own_site_note'])): ?><div><?= $label('Sul sito ufficiale') ?><?= $h($b['own_site_note']) ?></div><?php endif; ?>
    <?php else: ?>
    <div><?= $label('A chi scrivere') ?><?= $h($b['recipient'] ?? '') ?></div>
    <div><?= $label('Cosa chiedere') ?><?= $h($requestLabel[$b['request'] ?? ''] ?? ($b['request'] ?? '')) ?></div>
    <div><?= $label('Su quale base') ?><?= $h($b['basis'] ?? '') ?></div>
    <?php if ($pages): ?><div><?= $label('Pagine') ?><ul style="margin:1pt 0 3pt 12pt;padding:0;"><?php foreach ($pages as $u): ?><li><a href="<?= $h($u) ?>" style="color:#4f46e5;text-decoration:none;"><?= $h(mb_strimwidth((string) $u, 0, 95, '…')) ?></a></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if (!empty($b['fallback'])): ?><div><?= $label('Se rifiutano') ?><?= $h($b['fallback']) ?></div><?php endif; ?>
    <?php endif; ?>
    <?php if ($generatedAt): ?><div style="margin-top:3pt;font-size:8pt;color:#94a3b8;">Scheda generata il <?= date('d/m/Y', $generatedAt) ?></div><?php endif; ?>
</div>
