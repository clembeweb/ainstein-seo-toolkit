<?php
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$riskColor = match ($metrics['risk_label'] ?? '') { 'Alto' => '#dc2626', 'Medio' => '#d97706', 'Basso' => '#059669', default => '#64748b' };
$badge = fn(string $text, string $bg, string $fg) => '<span style="display:inline-block;padding:1pt 6pt;border-radius:8pt;font-size:8pt;font-weight:bold;background:' . $bg . ';color:' . $fg . ';">' . $h($text) . '</span>';
$typeBadge = fn(string $type) => $badge($typeLabels[$type] ?? $type, $type === 'removal' ? '#fee2e2' : '#e0e7ff', $type === 'removal' ? '#b91c1c' : '#3730a3');
$statusBadge = fn(string $status) => $badge($statusLabels[$status] ?? $status, '#f1f5f9', '#334155');
// $heading: titolo di sezione stampato nello stesso blocco del primo riquadro, così non resta mai da solo in fondo alla pagina
$renderAction = function (array $a, ?string $heading = null) use ($h, $typeBadge, $statusBadge, $channelLabels): void {
    if ($heading !== null) {
        echo '<div style="page-break-inside:avoid;">' . $heading;
    }
    ?>
    <div style="border:0.5pt solid #e2e8f0;border-radius:6pt;padding:8pt 10pt;margin-bottom:9pt;page-break-inside:avoid;">
        <div style="margin-bottom:4pt;"><?= $typeBadge($a['type']) ?> &nbsp;<?= $statusBadge($a['status'] ?? 'proposed') ?></div>
        <div style="font-size:11.5pt;font-weight:bold;color:#0f172a;margin-bottom:4pt;"><?= $h($a['title']) ?></div>
        <?php if (!empty($a['rationale'])): ?><div style="font-size:9.5pt;color:#334155;margin-bottom:4pt;"><?= $h($a['rationale']) ?></div><?php endif; ?>
        <?php if ($a['pages']): ?>
        <div style="font-size:9pt;color:#475569;">
            <?php foreach ($a['pages'] as $p): ?>
            <div>• <a href="<?= $h($p['url']) ?>" style="color:#4f46e5;text-decoration:none;"><?= $h($p['label']) ?></a> <span style="color:#94a3b8;"><?= $h(mb_strimwidth($p['url'], 0, 90, '…')) ?></span></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (empty($a['brief_data'])): ?>
        <div style="margin-top:6pt;font-size:9pt;color:#94a3b8;border-top:0.5pt dashed #e2e8f0;padding-top:4pt;">Scheda operativa non generata</div>
        <?php else: ?>
        <?php include __DIR__ . '/action-brief.php'; ?>
        <?php endif; ?>
    </div>
    <?php
    if ($heading !== null) {
        echo '</div>';
    }
};
$sectionTitle = fn(string $text, string $margin) => '<div style="font-size:10pt;font-weight:bold;color:#4f46e5;text-transform:uppercase;letter-spacing:0.8pt;margin:' . $margin . ';">' . $h($text) . '</div>';
?>
<div style="font-family:dejavusans,sans-serif;color:#0f172a;">
    <div style="border-bottom:1.5pt solid #4f46e5;padding-bottom:8pt;margin-bottom:12pt;">
        <div style="font-size:9pt;color:#4f46e5;font-weight:bold;letter-spacing:1pt;">AI REPUTATION RADAR · PIANO DEGLI INTERVENTI</div>
        <div style="font-size:18pt;font-weight:bold;margin-top:2pt;"><?= $h($project['subject_name']) ?></div>
        <div style="font-size:9.5pt;color:#475569;margin-top:3pt;">
            Run #<?= (int) $run['id'] ?> del <?= date('d/m/Y', strtotime((string) $run['created_at'])) ?>
            · Rischio reputazione: <span style="color:<?= $riskColor ?>;font-weight:bold;"><?= $h($metrics['risk_label'] ?? '–') ?><?= isset($metrics['risk']) ? ' ' . (int) $metrics['risk'] . '%' : '' ?></span>
            · <?= count($contents) ?> contenut<?= count($contents) === 1 ? 'o' : 'i' ?> da pubblicare · <?= count($removals) ?> sit<?= count($removals) === 1 ? 'o' : 'i' ?> da contattare
        </div>
    </div>

    <?php if (!$contents && !$removals): ?>
    <p style="font-size:10pt;color:#475569;">Nessun intervento necessario per questo run.</p>
    <?php endif; ?>

    <?php if ($contents): ?>
    <?php foreach ($contents as $i => $a) { $renderAction($a, $i === 0 ? $sectionTitle('Da pubblicare', '6pt 0') : null); } ?>
    <?php endif; ?>

    <?php if ($removals): ?>
    <?php foreach ($removals as $i => $a) { $renderAction($a, $i === 0 ? $sectionTitle('Da far rimuovere o aggiornare', '10pt 0 6pt') : null); } ?>
    <?php endif; ?>
</div>
