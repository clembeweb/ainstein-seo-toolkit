<?php
/**
 * Template PDF per valutazione campagne Google Ads
 *
 * Variabili disponibili:
 * - $evaluation: record valutazione dal DB
 * - $ai: risposta AI decodificata (alias di $aiResponse)
 * - $project: record progetto dal DB
 *
 * NOTA: mpdf NON supporta flexbox/grid. Usare solo table, div, p, h1-h4, span, strong, br.
 * Tutto il CSS deve essere inline.
 */

// Helper functions (guarded against double-include)
if (!function_exists('pdfScoreColor')) {

function pdfScoreColor(float $score): string {
    if ($score < 5) return '#ef4444';
    if ($score <= 7) return '#f59e0b';
    return '#10b981';
}

function pdfSeverityColor(string $severity): string {
    return match($severity) {
        'high' => '#ef4444',
        'medium' => '#f59e0b',
        default => '#3b82f6',
    };
}

function pdfSeverityLabel(string $s): string {
    return match($s) {
        'high' => 'Alta',
        'medium' => 'Media',
        default => 'Bassa',
    };
}

function pdfTrendLabel(string $t): string {
    return match($t) {
        'improving' => 'In miglioramento',
        'declining' => 'In calo',
        'mixed' => 'Misto',
        default => 'Stabile',
    };
}

function pdfTrendArrow(string $t): string {
    return match($t) {
        'improving' => '&#9650;',
        'declining' => '&#9660;',
        'mixed' => '&#9654;',
        default => '&#9644;',
    };
}

function pdfTrendColor(string $t): string {
    return match($t) {
        'improving' => '#10b981',
        'declining' => '#ef4444',
        'mixed' => '#f59e0b',
        default => '#64748b',
    };
}

function pdfE(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

} // end function_exists guard

// Extract data
$overallScore = (float)($ai['overall_score'] ?? 0);
$summary = $ai['summary'] ?? '';
$campaigns = $ai['campaigns'] ?? [];
$topRecommendations = $ai['top_recommendations'] ?? [];
$extensionsEval = $ai['extensions_evaluation'] ?? null;
$landingEval = $ai['landing_evaluation'] ?? null;
$campaignSuggestions = $ai['campaign_suggestions'] ?? [];
$landingSuggestions = $ai['landing_suggestions'] ?? [];
$trend = $ai['trend'] ?? 'stable';
$metricDeltas = $ai['metric_deltas'] ?? (!empty($evaluation['metric_deltas']) ? json_decode($evaluation['metric_deltas'], true) : null);
$landingPagesAnalyzed = (int)($evaluation['landing_pages_analyzed'] ?? 0);

$scoreColor = pdfScoreColor($overallScore);
$trendLabel = pdfTrendLabel($trend);
$trendColor = pdfTrendColor($trend);
$trendArrow = pdfTrendArrow($trend);
$evalDate = date('d/m/Y H:i', strtotime($evaluation['created_at'] ?? 'now'));
$projectName = pdfE($project['name'] ?? 'Progetto');
?>

<style>
    body { font-family: dejavusans, sans-serif; color: #334155; font-size: 10px; line-height: 1.5; }
    h1 { font-size: 22px; color: #1e293b; margin: 0 0 8px 0; }
    h2 { font-size: 16px; color: #1e293b; margin: 20px 0 10px 0; border-bottom: 2px solid #e11d48; padding-bottom: 6px; }
    h3 { font-size: 13px; color: #1e293b; margin: 14px 0 8px 0; }
    h4 { font-size: 11px; color: #1e293b; margin: 10px 0 6px 0; }
    p { margin: 4px 0; }
    .section { margin-bottom: 16px; }
    .table-standard { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .table-standard th { background-color: #f1f5f9; color: #475569; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; padding: 6px 8px; border-bottom: 2px solid #e2e8f0; }
    .table-standard td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 9px; vertical-align: top; }
    .table-standard tr.alt { background-color: #f8fafc; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8px; font-weight: bold; }
    .text-muted { color: #64748b; font-size: 9px; }
    .bullet-green { color: #10b981; }
    .bullet-red { color: #ef4444; }
</style>

<!-- ============================================ -->
<!-- COVER PAGE -->
<!-- ============================================ -->
<div style="text-align: center; padding-top: 120px;">
    <div style="font-size: 36px; font-weight: bold; color: #e11d48; letter-spacing: 3px; margin-bottom: 8px;">AINSTEIN</div>
    <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 50px;">Google Ads Analyzer</div>

    <div style="border-top: 2px solid #e2e8f0; border-bottom: 2px solid #e2e8f0; padding: 30px 0; margin: 0 60px;">
        <div style="font-size: 18px; color: #1e293b; font-weight: bold; margin-bottom: 6px;">Report Valutazione Campagne</div>
        <div style="font-size: 14px; color: #475569; margin-bottom: 20px;"><?= $projectName ?></div>

        <!-- Overall Score -->
        <div style="margin: 30px auto; width: 120px; height: 120px; border: 5px solid <?= $scoreColor ?>; border-radius: 60px; text-align: center; line-height: 110px;">
            <span style="font-size: 42px; font-weight: bold; color: <?= $scoreColor ?>;"><?= number_format($overallScore, 1) ?></span>
        </div>
        <div style="font-size: 11px; color: #64748b; margin-bottom: 6px;">Punteggio Complessivo</div>

        <?php if ($trend !== 'stable'): ?>
        <div style="margin-top: 10px;">
            <span style="font-size: 11px; color: <?= $trendColor ?>; font-weight: bold;"><?= $trendArrow ?> <?= pdfE($trendLabel) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div style="margin-top: 40px; color: #64748b; font-size: 10px;">
        <div>Data valutazione: <?= $evalDate ?></div>
        <div style="margin-top: 4px;">Campagne valutate: <?= (int)($evaluation['campaigns_evaluated'] ?? 0) ?> &bull; Annunci: <?= (int)($evaluation['ads_evaluated'] ?? 0) ?> &bull; Gruppi: <?= (int)($evaluation['ad_groups_evaluated'] ?? 0) ?></div>
        <?php if ($landingPagesAnalyzed > 0): ?>
        <div style="margin-top: 4px;">Landing page analizzate: <?= $landingPagesAnalyzed ?></div>
        <?php endif; ?>
    </div>

    <?php if ($summary): ?>
    <div style="margin: 30px 40px 0 40px; padding: 16px; background-color: #f8fafc; border-left: 3px solid #e11d48; text-align: left;">
        <div style="font-size: 9px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">Riepilogo</div>
        <div style="font-size: 10px; color: #334155; line-height: 1.6;"><?= pdfE($summary) ?></div>
    </div>
    <?php endif; ?>
</div>

<pagebreak />

<!-- ============================================ -->
<!-- METRIC DELTAS -->
<!-- ============================================ -->
<?php if (!empty($metricDeltas) && is_array($metricDeltas)): ?>
<h2>Variazioni Metriche</h2>
<div class="section">
    <table class="table-standard">
        <thead>
            <tr>
                <th style="width: 35%;">Metrica</th>
                <th style="width: 25%;">Variazione</th>
                <th style="width: 40%;">Dettaglio</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $metricLabels = [
                'avg_cpc' => 'CPC Medio',
                'avg_ctr' => 'CTR Medio',
                'total_cost' => 'Costo Totale',
                'total_clicks' => 'Click Totali',
                'total_conversions' => 'Conversioni',
                'total_impressions' => 'Impressioni',
            ];
            // Metriche dove diminuzione e positiva
            $negativeIsGood = ['avg_cpc', 'total_cost'];
            $rowIdx = 0;
            foreach ($metricDeltas as $key => $delta):
                $label = $metricLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                $percent = (float)($delta['percent'] ?? 0);
                $positiveIsGood = !in_array($key, $negativeIsGood);
                if (isset($delta['positive_is_good'])) {
                    $positiveIsGood = (bool)$delta['positive_is_good'];
                }
                $isPositive = $percent > 0;
                $isGood = ($isPositive && $positiveIsGood) || (!$isPositive && !$positiveIsGood);
                $changeColor = abs($percent) < 1 ? '#64748b' : ($isGood ? '#10b981' : '#ef4444');
                $arrow = $percent > 0 ? '&#9650;' : ($percent < 0 ? '&#9660;' : '&#9644;');
                $altClass = ($rowIdx % 2 === 1) ? ' class="alt"' : '';
            ?>
            <tr<?= $altClass ?>>
                <td style="font-weight: bold;"><?= pdfE($label) ?></td>
                <td>
                    <span style="color: <?= $changeColor ?>; font-weight: bold;">
                        <?= $arrow ?> <?= ($percent > 0 ? '+' : '') . number_format($percent, 1) ?>%
                    </span>
                </td>
                <td class="text-muted">
                    <?php if (isset($delta['previous']) && isset($delta['current'])): ?>
                        <?= number_format((float)$delta['previous'], 2) ?> &rarr; <?= number_format((float)$delta['current'], 2) ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php $rowIdx++; endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- TOP RECOMMENDATIONS -->
<!-- ============================================ -->
<?php if (!empty($topRecommendations)): ?>
<h2>Raccomandazioni Principali</h2>
<div class="section">
    <?php foreach (array_slice($topRecommendations, 0, 7) as $idx => $rec): ?>
    <div style="padding: 8px 12px; margin-bottom: 6px; background-color: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 0 4px 4px 0;">
        <span style="font-weight: bold; color: #92400e; font-size: 10px;"><?= $idx + 1 ?>.</span>
        <span style="font-size: 10px; color: #78350f;"><?= pdfE(is_string($rec) ? $rec : ($rec['text'] ?? ($rec['recommendation'] ?? ''))) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- CAMPAIGN ANALYSIS -->
<!-- ============================================ -->
<?php if (!empty($campaigns)): ?>
<h2>Analisi Campagne</h2>

<?php foreach ($campaigns as $cIdx => $campaign):
    $campScore = (float)($campaign['score'] ?? 0);
    $campScoreColor = pdfScoreColor($campScore);
    $campName = pdfE($campaign['name'] ?? $campaign['campaign_name'] ?? 'Campagna ' . ($cIdx + 1));
    $campType = strtoupper($campaign['type'] ?? $campaign['campaign_type'] ?? 'SEARCH');
    $strengths = $campaign['strengths'] ?? [];
    $issues = $campaign['issues'] ?? [];
    $adGroups = $campaign['ad_groups'] ?? [];
?>

    <!-- Campaign Header -->
    <div style="margin-top: 16px; margin-bottom: 10px; padding: 10px 14px; background-color: #f8fafc; border-left: 4px solid <?= $campScoreColor ?>; border-radius: 0 6px 6px 0;">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td style="vertical-align: middle;">
                    <span style="font-size: 20px; font-weight: bold; color: <?= $campScoreColor ?>; margin-right: 10px;"><?= number_format($campScore, 1) ?></span>
                    <span style="font-size: 13px; font-weight: bold; color: #1e293b;"><?= $campName ?></span>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <span class="badge" style="background-color: #e2e8f0; color: #475569;"><?= pdfE($campType) ?></span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Strengths -->
    <?php if (!empty($strengths)): ?>
    <h4 style="color: #10b981;">Punti di Forza</h4>
    <?php foreach ($strengths as $strength): ?>
    <div style="padding: 3px 0 3px 16px; font-size: 9px;">
        <span class="bullet-green">&#9679;</span>
        <span style="color: #334155;"><?= pdfE(is_string($strength) ? $strength : ($strength['text'] ?? '')) ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Issues -->
    <?php if (!empty($issues)): ?>
    <h4 style="color: #ef4444;">Problemi Rilevati</h4>
    <table class="table-standard">
        <thead>
            <tr>
                <th style="width: 12%;">Priorit&agrave;</th>
                <th style="width: 15%;">Area</th>
                <th style="width: 35%;">Problema</th>
                <th style="width: 38%;">Raccomandazione</th>
            </tr>
        </thead>
        <tbody>
            <?php $issueIdx = 0; foreach ($issues as $issue):
                $severity = $issue['severity'] ?? 'low';
                $sevColor = pdfSeverityColor($severity);
                $sevLabel = pdfSeverityLabel($severity);
                $altClass = ($issueIdx % 2 === 1) ? ' class="alt"' : '';
            ?>
            <tr<?= $altClass ?>>
                <td><span class="badge" style="background-color: <?= $sevColor ?>; color: #ffffff;"><?= $sevLabel ?></span></td>
                <td style="font-weight: bold;"><?= pdfE($issue['area'] ?? 'Generale') ?></td>
                <td><?= pdfE($issue['description'] ?? $issue['issue'] ?? '') ?></td>
                <td style="color: #475569;"><?= pdfE($issue['recommendation'] ?? '') ?></td>
            </tr>
            <?php $issueIdx++; endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Ad Groups -->
    <?php if (!empty($adGroups)): ?>
    <h4>Gruppi di Annunci</h4>
    <?php foreach ($adGroups as $agIdx => $adGroup):
        $agScore = (float)($adGroup['score'] ?? 0);
        $agScoreColor = pdfScoreColor($agScore);
        $agName = pdfE($adGroup['name'] ?? $adGroup['ad_group_name'] ?? 'Gruppo ' . ($agIdx + 1));
    ?>
    <div style="margin: 8px 0 6px 0; padding: 6px 10px; background-color: #f8fafc; border-left: 3px solid <?= $agScoreColor ?>;">
        <span style="font-size: 14px; font-weight: bold; color: <?= $agScoreColor ?>;"><?= number_format($agScore, 1) ?></span>
        <span style="font-size: 10px; font-weight: bold; color: #1e293b; margin-left: 8px;"><?= $agName ?></span>
    </div>

    <!-- Mini metrics -->
    <?php
    $miniMetrics = [];
    if (isset($adGroup['keyword_coherence'])) $miniMetrics['Coerenza KW'] = $adGroup['keyword_coherence'];
    if (isset($adGroup['ad_relevance'])) $miniMetrics['Rilevanza Annunci'] = $adGroup['ad_relevance'];
    if (isset($adGroup['landing_coherence'])) $miniMetrics['Coerenza Landing'] = $adGroup['landing_coherence'];
    if (isset($adGroup['quality_score_avg'])) $miniMetrics['Quality Score'] = $adGroup['quality_score_avg'];
    ?>
    <?php if (!empty($miniMetrics)): ?>
    <table style="border-collapse: collapse; margin: 4px 0 6px 14px;">
        <tr>
            <?php foreach ($miniMetrics as $mLabel => $mVal): ?>
            <td style="padding: 3px 12px 3px 0; font-size: 8px;">
                <span style="color: #94a3b8;"><?= pdfE($mLabel) ?>:</span>
                <span style="font-weight: bold; color: <?= pdfScoreColor((float)$mVal) ?>;"><?= number_format((float)$mVal, 1) ?></span>
            </td>
            <?php endforeach; ?>
        </tr>
    </table>
    <?php endif; ?>

    <!-- Ad Group Issues -->
    <?php $agIssues = $adGroup['issues'] ?? []; ?>
    <?php if (!empty($agIssues)): ?>
    <?php foreach ($agIssues as $agIssue): ?>
    <div style="padding: 2px 0 2px 24px; font-size: 9px;">
        <span class="bullet-red">&#9679;</span>
        <span style="color: #334155;">
            <?php if (is_string($agIssue)): ?>
                <?= pdfE($agIssue) ?>
            <?php else: ?>
                <?php if (!empty($agIssue['severity'])): ?>
                <span style="color: <?= pdfSeverityColor($agIssue['severity']) ?>; font-weight: bold;">[<?= pdfSeverityLabel($agIssue['severity']) ?>]</span>
                <?php endif; ?>
                <?= pdfE($agIssue['description'] ?? $agIssue['issue'] ?? '') ?>
                <?php if (!empty($agIssue['recommendation'])): ?>
                <span style="color: #64748b;"> &mdash; <?= pdfE($agIssue['recommendation']) ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php endforeach; // adGroups ?>
    <?php endif; ?>

    <?php if ($cIdx < count($campaigns) - 1): ?>
    <div style="border-bottom: 1px dashed #e2e8f0; margin: 14px 0;"></div>
    <?php endif; ?>

<?php endforeach; // campaigns ?>
<?php endif; ?>

<!-- ============================================ -->
<!-- EXTENSIONS EVALUATION -->
<!-- ============================================ -->
<?php if (!empty($extensionsEval)): ?>
<pagebreak />
<h2>Valutazione Estensioni</h2>
<div class="section">
    <?php $extScore = (float)($extensionsEval['score'] ?? 0); ?>
    <div style="margin-bottom: 12px;">
        <span style="font-size: 10px; color: #64748b;">Punteggio estensioni:</span>
        <span style="font-size: 16px; font-weight: bold; color: <?= pdfScoreColor($extScore) ?>;"><?= number_format($extScore, 1) ?>/10</span>
    </div>

    <!-- Present extensions -->
    <?php $present = $extensionsEval['present'] ?? []; ?>
    <?php if (!empty($present)): ?>
    <h4 style="color: #10b981;">Estensioni Presenti</h4>
    <?php foreach ($present as $ext): ?>
    <div style="padding: 2px 0 2px 16px; font-size: 9px;">
        <span class="bullet-green">&#10003;</span>
        <span style="color: #334155;"><?= pdfE(is_string($ext) ? $ext : ($ext['type'] ?? ($ext['name'] ?? ''))) ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Missing extensions -->
    <?php $missing = $extensionsEval['missing'] ?? []; ?>
    <?php if (!empty($missing)): ?>
    <h4 style="color: #ef4444;">Estensioni Mancanti</h4>
    <?php foreach ($missing as $ext): ?>
    <div style="padding: 2px 0 2px 16px; font-size: 9px;">
        <span class="bullet-red">&#10007;</span>
        <span style="color: #334155;"><?= pdfE(is_string($ext) ? $ext : ($ext['type'] ?? ($ext['name'] ?? ''))) ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Suggestions -->
    <?php $extSuggestions = $extensionsEval['suggestions'] ?? $extensionsEval['recommendations'] ?? []; ?>
    <?php if (!empty($extSuggestions)): ?>
    <h4 style="color: #f59e0b;">Suggerimenti</h4>
    <?php foreach ($extSuggestions as $sug): ?>
    <div style="padding: 4px 10px; margin-bottom: 4px; background-color: #fffbeb; border-left: 2px solid #f59e0b; font-size: 9px;">
        <?= pdfE(is_string($sug) ? $sug : ($sug['text'] ?? ($sug['suggestion'] ?? ($sug['recommendation'] ?? '')))) ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- LANDING PAGES -->
<!-- ============================================ -->
<?php if ($landingPagesAnalyzed > 0 && !empty($landingEval)): ?>
<h2>Valutazione Landing Page</h2>
<div class="section">
    <?php $lpScore = (float)($landingEval['overall_score'] ?? $landingEval['score'] ?? 0); ?>
    <div style="margin-bottom: 12px;">
        <span style="font-size: 10px; color: #64748b;">Punteggio landing:</span>
        <span style="font-size: 16px; font-weight: bold; color: <?= pdfScoreColor($lpScore) ?>;"><?= number_format($lpScore, 1) ?>/10</span>
    </div>

    <?php $lpIssues = $landingEval['issues'] ?? []; ?>
    <?php if (!empty($lpIssues)): ?>
    <table class="table-standard">
        <thead>
            <tr>
                <th style="width: 30%;">URL</th>
                <th style="width: 35%;">Problema</th>
                <th style="width: 35%;">Raccomandazione</th>
            </tr>
        </thead>
        <tbody>
            <?php $lpIdx = 0; foreach ($lpIssues as $lpIssue):
                $altClass = ($lpIdx % 2 === 1) ? ' class="alt"' : '';
            ?>
            <tr<?= $altClass ?>>
                <td style="word-break: break-all; font-size: 8px; color: #475569;"><?= pdfE($lpIssue['url'] ?? 'N/D') ?></td>
                <td><?= pdfE($lpIssue['issue'] ?? $lpIssue['description'] ?? '') ?></td>
                <td style="color: #475569;"><?= pdfE($lpIssue['recommendation'] ?? '') ?></td>
            </tr>
            <?php $lpIdx++; endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- SUGGESTIONS -->
<!-- ============================================ -->
<?php if (!empty($campaignSuggestions) || (!empty($landingSuggestions) && $landingPagesAnalyzed > 0)): ?>
<pagebreak />
<h2>Suggerimenti</h2>

<!-- Campaign Suggestions -->
<?php if (!empty($campaignSuggestions)): ?>
<h3>Suggerimenti Campagne</h3>
<div class="section">
    <table class="table-standard">
        <thead>
            <tr>
                <th style="width: 12%;">Priorit&agrave;</th>
                <th style="width: 15%;">Area</th>
                <th style="width: 43%;">Suggerimento</th>
                <th style="width: 30%;">Impatto Atteso</th>
            </tr>
        </thead>
        <tbody>
            <?php $csIdx = 0; foreach ($campaignSuggestions as $sug):
                $priority = $sug['priority'] ?? $sug['severity'] ?? 'medium';
                $priColor = pdfSeverityColor($priority);
                $priLabel = pdfSeverityLabel($priority);
                $altClass = ($csIdx % 2 === 1) ? ' class="alt"' : '';
            ?>
            <tr<?= $altClass ?>>
                <td><span class="badge" style="background-color: <?= $priColor ?>; color: #ffffff;"><?= $priLabel ?></span></td>
                <td style="font-weight: bold;"><?= pdfE($sug['area'] ?? 'Generale') ?></td>
                <td><?= pdfE($sug['suggestion'] ?? $sug['text'] ?? $sug['description'] ?? '') ?></td>
                <td style="color: #475569;"><?= pdfE($sug['expected_impact'] ?? $sug['impact'] ?? '') ?></td>
            </tr>
            <?php $csIdx++; endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Landing Suggestions -->
<?php if (!empty($landingSuggestions) && $landingPagesAnalyzed > 0): ?>
<h3>Suggerimenti Landing Page</h3>
<div class="section">
    <table class="table-standard">
        <thead>
            <tr>
                <th style="width: 10%;">Priorit&agrave;</th>
                <th style="width: 22%;">URL</th>
                <th style="width: 38%;">Suggerimento</th>
                <th style="width: 30%;">Impatto Atteso</th>
            </tr>
        </thead>
        <tbody>
            <?php $lsIdx = 0; foreach ($landingSuggestions as $sug):
                $priority = $sug['priority'] ?? $sug['severity'] ?? 'medium';
                $priColor = pdfSeverityColor($priority);
                $priLabel = pdfSeverityLabel($priority);
                $altClass = ($lsIdx % 2 === 1) ? ' class="alt"' : '';
            ?>
            <tr<?= $altClass ?>>
                <td><span class="badge" style="background-color: <?= $priColor ?>; color: #ffffff;"><?= $priLabel ?></span></td>
                <td style="word-break: break-all; font-size: 8px; color: #475569;"><?= pdfE($sug['url'] ?? '') ?></td>
                <td><?= pdfE($sug['suggestion'] ?? $sug['text'] ?? $sug['description'] ?? '') ?></td>
                <td style="color: #475569;"><?= pdfE($sug['expected_impact'] ?? $sug['impact'] ?? '') ?></td>
            </tr>
            <?php $lsIdx++; endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php endif; ?>
