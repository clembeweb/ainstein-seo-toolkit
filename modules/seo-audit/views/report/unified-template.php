<?php
/**
 * Template HTML standalone per Report AI Unificato (on-page + crawl budget)
 * Stile amevista — self-contained, scaricabile, condivisibile
 *
 * Variabili attese:
 *   $reportData  — array parsed JSON dalla risposta AI
 *   $siteProfile — array dal SiteProfileDetector
 *   $healthScore — int health score on-page
 *   $budgetScore — int budget score
 *   $domain      — string dominio del sito
 *   $generatedAt — string data generazione
 */

$issues = $reportData['issues'] ?? [];
$positives = $reportData['positives'] ?? [];
$timeline = $reportData['timeline'] ?? [];
$summary = $reportData['executive_summary'] ?? '';
$priorityActions = $reportData['priority_actions'] ?? [];
$estimatedImpact = $reportData['estimated_impact'] ?? '';

// Raggruppa issues per severity
$criticals = array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'critical');
$importants = array_filter($issues, fn($i) => in_array($i['severity'] ?? '', ['warning', 'important']));
$minors = array_filter($issues, fn($i) => in_array($i['severity'] ?? '', ['notice', 'minor']));

$issueNum = 0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report SEO Unificato &mdash; <?= htmlspecialchars($domain) ?></title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

  :root {
    --critical: #dc2626;
    --critical-bg: #fef2f2;
    --critical-border: #fecaca;
    --important: #d97706;
    --important-bg: #fffbeb;
    --important-border: #fde68a;
    --minor: #2563eb;
    --minor-bg: #eff6ff;
    --minor-border: #bfdbfe;
    --positive: #16a34a;
    --positive-bg: #f0fdf4;
    --positive-border: #bbf7d0;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;
    --radius: 10px;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--gray-900);
    background: var(--gray-50);
    line-height: 1.65;
    font-size: 14px;
  }

  .container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 28px 24px 40px;
  }

  /* ---- Header ---- */
  .report-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: white;
    padding: 28px 32px;
    border-radius: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
  }
  .report-header .left h1 { font-size: 22px; font-weight: 700; letter-spacing: -0.4px; }
  .report-header .left .subtitle { font-size: 13px; opacity: 0.7; margin-top: 2px; }
  .report-header .stats { display: flex; gap: 12px; }
  .stat-chip {
    display: flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 8px;
    font-weight: 700; font-size: 14px;
    background: rgba(255,255,255,0.12);
  }
  .stat-chip .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
  .stat-chip.critical .dot { background: #f87171; }
  .stat-chip.important .dot { background: #fbbf24; }
  .stat-chip.minor .dot { background: #60a5fa; }

  /* ---- Score chips ---- */
  .score-row {
    display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
  }
  .score-chip {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 20px; border-radius: var(--radius);
    background: white; border: 1px solid var(--gray-200);
    font-size: 14px;
  }
  .score-chip .score-value {
    font-size: 28px; font-weight: 700; line-height: 1;
  }
  .score-chip .score-label { font-size: 12px; color: var(--gray-500); }

  /* ---- Summary ---- */
  .summary-box {
    background: white; border: 1px solid var(--gray-200);
    border-radius: var(--radius); padding: 20px;
    margin-bottom: 20px; font-size: 14px; line-height: 1.7;
    color: var(--gray-700);
  }
  .summary-box h3 { font-size: 15px; font-weight: 700; margin-bottom: 8px; color: var(--gray-900); }

  /* ---- Toolbar ---- */
  .toolbar {
    display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;
  }
  .toolbar button {
    font-family: inherit; font-size: 12px; font-weight: 600;
    padding: 6px 14px; border-radius: 6px;
    border: 1px solid var(--gray-200); background: white;
    color: var(--gray-600); cursor: pointer; transition: all .15s;
  }
  .toolbar button:hover { background: var(--gray-100); }
  .toolbar button.active { background: var(--gray-900); color: white; border-color: var(--gray-900); }
  .toolbar .separator { width: 1px; height: 24px; background: var(--gray-200); margin: 0 4px; }

  /* ---- Hidden issues drawer ---- */
  .hidden-drawer {
    background: white; border: 1px solid var(--gray-200);
    border-radius: var(--radius); margin-bottom: 20px; overflow: hidden; display: none;
  }
  .hidden-drawer.has-items { display: block; }
  .hidden-drawer-header {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 16px; cursor: pointer; user-select: none;
    font-size: 13px; font-weight: 600; color: var(--gray-600);
    background: var(--gray-50); border-bottom: 1px solid var(--gray-100);
  }
  .hidden-drawer-header .count-badge {
    background: var(--gray-200); color: var(--gray-600);
    font-size: 11px; font-weight: 700; padding: 1px 8px; border-radius: 10px;
  }
  .hidden-drawer-header .hd-arrow {
    font-size: 14px; color: var(--gray-400); transition: transform .2s; margin-left: auto;
  }
  .hidden-drawer.open .hd-arrow { transform: rotate(90deg); }
  .hidden-drawer-body { display: none; padding: 8px 12px; }
  .hidden-drawer.open .hidden-drawer-body { display: block; }
  .hidden-item {
    display: flex; align-items: center; gap: 10px;
    padding: 6px 10px; border-radius: 6px; font-size: 13px; color: var(--gray-500);
  }
  .hidden-item:hover { background: var(--gray-50); }
  .hidden-item .hi-num {
    width: 22px; height: 22px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; flex-shrink: 0;
    background: var(--gray-100); color: var(--gray-500);
  }
  .hidden-item .hi-title { flex: 1; }
  .hidden-item .hi-restore {
    font-size: 11px; font-weight: 600; color: var(--positive);
    cursor: pointer; padding: 2px 10px; border-radius: 4px;
    border: 1px solid var(--positive-border); background: var(--positive-bg);
    transition: all .15s;
  }
  .hidden-item .hi-restore:hover { background: var(--positive); color: white; }

  /* ---- Sezioni ---- */
  .section { margin-bottom: 24px; }
  .section.section-empty { display: none; }
  .section-bar {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 0; margin-bottom: 8px;
    border-bottom: 2px solid var(--gray-200);
    cursor: pointer; user-select: none;
  }
  .section-bar h2 { font-size: 16px; font-weight: 700; flex: 1; }
  .section-bar .badge {
    font-size: 11px; font-weight: 600; padding: 3px 10px;
    border-radius: 20px; text-transform: uppercase; letter-spacing: 0.4px;
  }
  .section-bar .section-count { font-size: 12px; font-weight: 600; color: var(--gray-400); }
  .section-bar .chevron { font-size: 18px; color: var(--gray-400); transition: transform .2s; line-height: 1; }
  .section.collapsed .chevron { transform: rotate(-90deg); }
  .section.collapsed .issues-grid { display: none; }

  .section.critical .section-bar h2 { color: var(--critical); }
  .section.critical .section-bar .badge { background: var(--critical-bg); color: var(--critical); border: 1px solid var(--critical-border); }
  .section.critical .section-bar { border-bottom-color: var(--critical-border); }
  .section.important .section-bar h2 { color: var(--important); }
  .section.important .section-bar .badge { background: var(--important-bg); color: var(--important); border: 1px solid var(--important-border); }
  .section.important .section-bar { border-bottom-color: var(--important-border); }
  .section.minor .section-bar h2 { color: var(--minor); }
  .section.minor .section-bar .badge { background: var(--minor-bg); color: var(--minor); border: 1px solid var(--minor-border); }
  .section.minor .section-bar { border-bottom-color: var(--minor-border); }
  .section.positive .section-bar h2 { color: var(--positive); }
  .section.positive .section-bar .badge { background: var(--positive-bg); color: var(--positive); border: 1px solid var(--positive-border); }
  .section.positive .section-bar { border-bottom-color: var(--positive-border); }

  /* ---- Issues grid ---- */
  .issues-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

  /* ---- Issue card ---- */
  .issue {
    background: white; border-radius: var(--radius);
    border: 1px solid var(--gray-200); overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04); transition: box-shadow .15s, opacity .2s;
  }
  .issue:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
  .issue.critical { border-left: 4px solid var(--critical); }
  .issue.important { border-left: 4px solid var(--important); }
  .issue.minor { border-left: 4px solid var(--minor); }
  .issue.hidden-issue { display: none; }

  .issue-header {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 16px; cursor: pointer; user-select: none;
  }
  .issue-header .num {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 50%;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
  }
  .issue.critical .num { background: var(--critical-bg); color: var(--critical); }
  .issue.important .num { background: var(--important-bg); color: var(--important); }
  .issue.minor .num { background: var(--minor-bg); color: var(--minor); }
  .issue-header .title { flex: 1; font-size: 14px; font-weight: 600; line-height: 1.3; }
  .issue-header .impact-tag {
    font-size: 11px; font-weight: 600; padding: 2px 8px;
    border-radius: 4px; white-space: nowrap; flex-shrink: 0;
  }
  .issue.critical .impact-tag { background: var(--critical-bg); color: var(--critical); }
  .issue.important .impact-tag { background: var(--important-bg); color: var(--important); }
  .issue.minor .impact-tag { background: var(--minor-bg); color: var(--minor); }
  .issue-header .hide-btn {
    width: 26px; height: 26px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 14px; color: var(--gray-400); cursor: pointer;
    border: none; background: none; flex-shrink: 0; transition: all .15s; padding: 0;
  }
  .issue-header .hide-btn:hover { background: var(--critical-bg); color: var(--critical); }
  .issue-header .arrow {
    font-size: 16px; color: var(--gray-400); transition: transform .2s; flex-shrink: 0;
  }
  .issue.open .issue-header .arrow { transform: rotate(90deg); }

  .issue-body {
    display: none; padding: 0 16px 16px 52px;
    font-size: 13px; color: var(--gray-700); line-height: 1.7;
  }
  .issue.open .issue-body { display: block; }
  .issue-body p { margin-bottom: 10px; }
  .issue-body p:last-child { margin-bottom: 0; }
  .issue-body .fix {
    background: var(--positive-bg); border: 1px solid var(--positive-border);
    border-radius: 6px; padding: 10px 14px; margin-top: 10px; font-size: 13px;
  }
  .issue-body .fix strong { color: var(--positive); }
  .issue-body .fix ul { margin: 4px 0 0 16px; }
  .issue-body .fix li { margin-bottom: 3px; }
  .issue-body .urls {
    background: var(--gray-50); border: 1px solid var(--gray-200);
    border-radius: 6px; padding: 8px 12px; margin-top: 8px; font-size: 12px;
  }
  .issue-body .urls code { font-size: 11px; }

  pre {
    background: #1e293b; color: #e2e8f0; border-radius: 6px;
    padding: 12px 14px; font-family: 'JetBrains Mono', monospace;
    font-size: 12px; overflow-x: auto; margin: 8px 0; line-height: 1.55;
  }
  code {
    font-family: 'JetBrains Mono', monospace; font-size: 12px;
    background: var(--gray-100); padding: 1px 5px; border-radius: 3px; color: var(--critical);
  }
  pre code { background: none; padding: 0; color: inherit; }

  .issue-body table { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 13px; }
  .issue-body th {
    background: var(--gray-100); text-align: left; padding: 7px 10px;
    font-weight: 600; font-size: 11px; text-transform: uppercase;
    letter-spacing: 0.3px; color: var(--gray-500); border-bottom: 2px solid var(--gray-200);
  }
  .issue-body td { padding: 7px 10px; border-bottom: 1px solid var(--gray-100); }

  /* ---- Positive grid ---- */
  .positive-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
  .positive-item {
    display: flex; align-items: center; gap: 7px;
    padding: 8px 12px; background: var(--positive-bg);
    border-radius: 6px; font-size: 13px; color: var(--gray-700);
  }
  .positive-item .check { color: var(--positive); font-weight: 700; font-size: 15px; flex-shrink: 0; }

  /* ---- Timeline ---- */
  .timeline-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 10px; }
  .tl-card { border-radius: var(--radius); padding: 14px 16px; background: white; border: 1px solid var(--gray-200); }
  .tl-card .week { font-weight: 700; font-size: 13px; margin-bottom: 6px; }
  .tl-card .desc { font-size: 13px; color: var(--gray-600); line-height: 1.5; }
  .tl-card.urgent { border-left: 4px solid var(--critical); }
  .tl-card.urgent .week { color: var(--critical); }
  .tl-card.imp { border-left: 4px solid var(--important); }
  .tl-card.imp .week { color: var(--important); }
  .tl-card.improve { border-left: 4px solid var(--minor); }
  .tl-card.improve .week { color: var(--minor); }

  /* ---- Extra panels ---- */
  .extra-panel {
    background: white; border-radius: var(--radius);
    border: 1px solid var(--gray-200); margin-bottom: 10px; overflow: hidden;
  }
  .extra-panel-header {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px; cursor: pointer; user-select: none;
    font-weight: 600; font-size: 14px; color: var(--gray-700);
  }
  .extra-panel-header .ep-arrow { font-size: 16px; color: var(--gray-400); transition: transform .2s; }
  .extra-panel.open .ep-arrow { transform: rotate(90deg); }
  .extra-panel-body { display: none; padding: 0 16px 16px 16px; }
  .extra-panel.open .extra-panel-body { display: block; }

  /* ---- Footer ---- */
  .footer {
    text-align: center; color: var(--gray-400); font-size: 12px;
    margin-top: 32px; padding-top: 16px; border-top: 1px solid var(--gray-200);
  }

  /* ---- Responsive ---- */
  @media (max-width: 900px) {
    .issues-grid { grid-template-columns: 1fr; }
    .positive-grid { grid-template-columns: 1fr 1fr; }
    .timeline-row { grid-template-columns: 1fr; }
  }
  @media (max-width: 640px) {
    .container { padding: 12px; }
    .report-header { flex-direction: column; align-items: flex-start; padding: 20px; }
    .report-header .stats { flex-wrap: wrap; }
    .positive-grid { grid-template-columns: 1fr; }
    .issue-body { padding-left: 16px; }
  }

  /* ---- Print ---- */
  @media print {
    body { background: white; font-size: 11px; }
    .container { padding: 0; max-width: 100%; }
    .issue.open .issue-body { display: block !important; }
    .issue.hidden-issue { display: none !important; }
    .report-header { background: #1e293b !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .toolbar, .hidden-drawer { display: none !important; }
    .hide-btn { display: none !important; }
    .issues-grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<div class="container">

  <!-- Header -->
  <div class="report-header">
    <div class="left">
      <h1>Report SEO Unificato</h1>
      <div class="subtitle"><?= htmlspecialchars($domain) ?> &mdash; <?= htmlspecialchars($generatedAt) ?></div>
    </div>
    <div class="stats">
      <div class="stat-chip critical"><span class="dot"></span> <span id="count-critical"><?= count($criticals) ?></span> Critici</div>
      <div class="stat-chip important"><span class="dot"></span> <span id="count-important"><?= count($importants) ?></span> Importanti</div>
      <div class="stat-chip minor"><span class="dot"></span> <span id="count-minor"><?= count($minors) ?></span> Minori</div>
    </div>
  </div>

  <!-- Score row -->
  <div class="score-row">
    <div class="score-chip">
      <div>
        <div class="score-value" style="color: <?= $healthScore >= 80 ? 'var(--positive)' : ($healthScore >= 50 ? 'var(--important)' : 'var(--critical)') ?>"><?= (int)$healthScore ?></div>
        <div class="score-label">Health Score</div>
      </div>
    </div>
    <div class="score-chip">
      <div>
        <div class="score-value" style="color: <?= $budgetScore >= 80 ? 'var(--positive)' : ($budgetScore >= 50 ? 'var(--important)' : 'var(--critical)') ?>"><?= (int)$budgetScore ?></div>
        <div class="score-label">Budget Score</div>
      </div>
    </div>
    <div class="score-chip">
      <div>
        <div class="score-value"><?= htmlspecialchars($siteProfile['size_label'] ?? 'N/A') ?></div>
        <div class="score-label">Tipo: <?= htmlspecialchars($siteProfile['type'] ?? 'generico') ?></div>
      </div>
    </div>
  </div>

  <!-- Executive Summary -->
  <?php if ($summary): ?>
  <div class="summary-box">
    <h3>Panoramica</h3>
    <p><?= nl2br(htmlspecialchars($summary)) ?></p>
  </div>
  <?php endif; ?>

  <!-- Toolbar -->
  <div class="toolbar">
    <button onclick="expandAll()">Espandi tutto</button>
    <button onclick="collapseAll()">Comprimi tutto</button>
    <span class="separator"></span>
    <button onclick="filterIssues('all')" class="active" data-filter="all">Tutti</button>
    <button onclick="filterIssues('critical')" data-filter="critical">Critici</button>
    <button onclick="filterIssues('important')" data-filter="important">Importanti</button>
    <button onclick="filterIssues('minor')" data-filter="minor">Minori</button>
    <span class="separator"></span>
    <button onclick="showAllIssues()">Ripristina nascosti</button>
  </div>

  <!-- Hidden issues drawer -->
  <div class="hidden-drawer" id="hiddenDrawer" onclick="this.classList.toggle('open')">
    <div class="hidden-drawer-header">
      &#128065;&#65039; Problemi nascosti <span class="count-badge" id="hiddenCount">0</span>
      <span class="hd-arrow">&#9654;</span>
    </div>
    <div class="hidden-drawer-body" id="hiddenList"></div>
  </div>

  <!-- ==================== CRITICI ==================== -->
  <?php if (count($criticals) > 0): ?>
  <div class="section critical" data-severity="critical">
    <div class="section-bar" onclick="toggleSection(this)">
      <h2>Problemi critici</h2>
      <span class="badge">Priorit&agrave; 1</span>
      <span class="section-count">(<?= count($criticals) ?>)</span>
      <span class="chevron">&#9660;</span>
    </div>
    <div class="issues-grid">
      <?php foreach ($criticals as $issue): $issueNum++; ?>
      <?php $this_impact = $issue['impact'] ?? 'ALTO'; ?>
      <div class="issue critical" data-id="<?= $issueNum ?>" data-sev="critical">
        <div class="issue-header" onclick="toggleIssue(this.parentElement, event)">
          <span class="num"><?= $issueNum ?></span>
          <span class="title"><?= htmlspecialchars($issue['title'] ?? '') ?></span>
          <span class="impact-tag"><?= htmlspecialchars(strtoupper($this_impact)) ?></span>
          <button class="hide-btn" onclick="hideIssue(event, this)" title="Nascondi">&#10005;</button>
          <span class="arrow">&#9654;</span>
        </div>
        <div class="issue-body">
          <p><?= nl2br(htmlspecialchars($issue['description'] ?? '')) ?></p>
          <?php if (!empty($issue['affected_urls'])): ?>
          <div class="urls"><strong>URL interessate:</strong><br>
            <?php foreach (array_slice($issue['affected_urls'], 0, 5) as $url): ?>
            <code><?= htmlspecialchars($url) ?></code><br>
            <?php endforeach; ?>
            <?php if (count($issue['affected_urls']) > 5): ?>
            <em>... e altre <?= count($issue['affected_urls']) - 5 ?></em>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($issue['code_example'])): ?>
          <pre><code><?= htmlspecialchars($issue['code_example']) ?></code></pre>
          <?php endif; ?>
          <?php if (!empty($issue['fix'])): ?>
          <div class="fix"><strong>Fix:</strong> <?= nl2br(htmlspecialchars($issue['fix'])) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ==================== IMPORTANTI ==================== -->
  <?php if (count($importants) > 0): ?>
  <div class="section important" data-severity="important">
    <div class="section-bar" onclick="toggleSection(this)">
      <h2>Problemi importanti</h2>
      <span class="badge">Priorit&agrave; 2</span>
      <span class="section-count">(<?= count($importants) ?>)</span>
      <span class="chevron">&#9660;</span>
    </div>
    <div class="issues-grid">
      <?php foreach ($importants as $issue): $issueNum++; ?>
      <?php $this_impact = $issue['impact'] ?? 'MEDIO'; ?>
      <div class="issue important" data-id="<?= $issueNum ?>" data-sev="important">
        <div class="issue-header" onclick="toggleIssue(this.parentElement, event)">
          <span class="num"><?= $issueNum ?></span>
          <span class="title"><?= htmlspecialchars($issue['title'] ?? '') ?></span>
          <span class="impact-tag"><?= htmlspecialchars(strtoupper($this_impact)) ?></span>
          <button class="hide-btn" onclick="hideIssue(event, this)" title="Nascondi">&#10005;</button>
          <span class="arrow">&#9654;</span>
        </div>
        <div class="issue-body">
          <p><?= nl2br(htmlspecialchars($issue['description'] ?? '')) ?></p>
          <?php if (!empty($issue['affected_urls'])): ?>
          <div class="urls"><strong>URL interessate:</strong><br>
            <?php foreach (array_slice($issue['affected_urls'], 0, 5) as $url): ?>
            <code><?= htmlspecialchars($url) ?></code><br>
            <?php endforeach; ?>
            <?php if (count($issue['affected_urls']) > 5): ?>
            <em>... e altre <?= count($issue['affected_urls']) - 5 ?></em>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($issue['code_example'])): ?>
          <pre><code><?= htmlspecialchars($issue['code_example']) ?></code></pre>
          <?php endif; ?>
          <?php if (!empty($issue['fix'])): ?>
          <div class="fix"><strong>Fix:</strong> <?= nl2br(htmlspecialchars($issue['fix'])) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ==================== MINORI ==================== -->
  <?php if (count($minors) > 0): ?>
  <div class="section minor" data-severity="minor">
    <div class="section-bar" onclick="toggleSection(this)">
      <h2>Problemi minori</h2>
      <span class="badge">Priorit&agrave; 3</span>
      <span class="section-count">(<?= count($minors) ?>)</span>
      <span class="chevron">&#9660;</span>
    </div>
    <div class="issues-grid">
      <?php foreach ($minors as $issue): $issueNum++; ?>
      <?php $this_impact = $issue['impact'] ?? 'BASSO'; ?>
      <div class="issue minor" data-id="<?= $issueNum ?>" data-sev="minor">
        <div class="issue-header" onclick="toggleIssue(this.parentElement, event)">
          <span class="num"><?= $issueNum ?></span>
          <span class="title"><?= htmlspecialchars($issue['title'] ?? '') ?></span>
          <span class="impact-tag"><?= htmlspecialchars(strtoupper($this_impact)) ?></span>
          <button class="hide-btn" onclick="hideIssue(event, this)" title="Nascondi">&#10005;</button>
          <span class="arrow">&#9654;</span>
        </div>
        <div class="issue-body">
          <p><?= nl2br(htmlspecialchars($issue['description'] ?? '')) ?></p>
          <?php if (!empty($issue['affected_urls'])): ?>
          <div class="urls"><strong>URL interessate:</strong><br>
            <?php foreach (array_slice($issue['affected_urls'], 0, 5) as $url): ?>
            <code><?= htmlspecialchars($url) ?></code><br>
            <?php endforeach; ?>
            <?php if (count($issue['affected_urls']) > 5): ?>
            <em>... e altre <?= count($issue['affected_urls']) - 5 ?></em>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($issue['code_example'])): ?>
          <pre><code><?= htmlspecialchars($issue['code_example']) ?></code></pre>
          <?php endif; ?>
          <?php if (!empty($issue['fix'])): ?>
          <div class="fix"><strong>Fix:</strong> <?= nl2br(htmlspecialchars($issue['fix'])) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ==================== POSITIVI ==================== -->
  <?php if (count($positives) > 0): ?>
  <div class="section positive" data-severity="positive">
    <div class="section-bar" onclick="toggleSection(this)">
      <h2>Aspetti positivi</h2>
      <span class="badge">Ben fatto</span>
      <span class="section-count">(<?= count($positives) ?>)</span>
      <span class="chevron">&#9660;</span>
    </div>
    <div class="positive-grid">
      <?php foreach ($positives as $positive): ?>
      <div class="positive-item">
        <span class="check">&#10003;</span>
        <?= htmlspecialchars($positive) ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ==================== TIMELINE ==================== -->
  <?php if (!empty($timeline)): ?>
  <div class="section" data-severity="positive">
    <div class="section-bar" onclick="toggleSection(this)">
      <h2>Piano d'azione consigliato</h2>
      <span class="badge" style="background:var(--gray-100);color:var(--gray-600);border:1px solid var(--gray-200)">Timeline</span>
      <span class="chevron">&#9660;</span>
    </div>
    <div class="timeline-row">
      <?php if (!empty($timeline['week1'])): ?>
      <div class="tl-card urgent">
        <div class="week">Settimana 1</div>
        <div class="desc"><?= nl2br(htmlspecialchars($timeline['week1'])) ?></div>
      </div>
      <?php endif; ?>
      <?php if (!empty($timeline['week2_3'])): ?>
      <div class="tl-card imp">
        <div class="week">Settimane 2-3</div>
        <div class="desc"><?= nl2br(htmlspecialchars($timeline['week2_3'])) ?></div>
      </div>
      <?php endif; ?>
      <?php if (!empty($timeline['week4_plus'])): ?>
      <div class="tl-card improve">
        <div class="week">Settimana 4+</div>
        <div class="desc"><?= nl2br(htmlspecialchars($timeline['week4_plus'])) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ==================== PRIORITY ACTIONS ==================== -->
  <?php if (!empty($priorityActions)): ?>
  <div class="extra-panel" onclick="togglePanel(this)">
    <div class="extra-panel-header">
      <span class="ep-arrow">&#9654;</span>
      Azioni prioritarie (<?= count($priorityActions) ?>)
    </div>
    <div class="extra-panel-body">
      <ol style="margin-left:16px;">
        <?php foreach ($priorityActions as $action): ?>
        <li style="margin-bottom:6px;"><?= htmlspecialchars($action) ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
  <?php endif; ?>

  <!-- ==================== ESTIMATED IMPACT ==================== -->
  <?php if ($estimatedImpact): ?>
  <div class="extra-panel" onclick="togglePanel(this)">
    <div class="extra-panel-header">
      <span class="ep-arrow">&#9654;</span>
      Impatto stimato delle correzioni
    </div>
    <div class="extra-panel-body">
      <p><?= nl2br(htmlspecialchars($estimatedImpact)) ?></p>
    </div>
  </div>
  <?php endif; ?>

  <div class="footer">
    Report generato da Ainstein &mdash; <?= htmlspecialchars($generatedAt) ?>
  </div>

</div>

<script>
  const STORAGE_KEY = 'ainstein_report_hidden_<?= (int)($reportId ?? 0) ?>';

  function getHiddenIds() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; }
    catch { return []; }
  }
  function saveHiddenIds(ids) { localStorage.setItem(STORAGE_KEY, JSON.stringify(ids)); }

  function toggleIssue(el, e) {
    if (e && e.target.closest('.hide-btn')) return;
    el.classList.toggle('open');
  }
  function toggleSection(bar) { bar.parentElement.classList.toggle('collapsed'); }
  function togglePanel(panel) { panel.classList.toggle('open'); }

  function expandAll() {
    document.querySelectorAll('.issue:not(.hidden-issue)').forEach(i => i.classList.add('open'));
    document.querySelectorAll('.section').forEach(s => s.classList.remove('collapsed'));
    document.querySelectorAll('.extra-panel').forEach(p => p.classList.add('open'));
  }
  function collapseAll() {
    document.querySelectorAll('.issue').forEach(i => i.classList.remove('open'));
    document.querySelectorAll('.extra-panel').forEach(p => p.classList.remove('open'));
  }

  function filterIssues(severity) {
    document.querySelectorAll('.toolbar button[data-filter]').forEach(b => b.classList.remove('active'));
    document.querySelector('.toolbar button[data-filter="' + severity + '"]').classList.add('active');
    document.querySelectorAll('.section[data-severity]').forEach(section => {
      const sev = section.dataset.severity;
      if (sev === 'positive') { section.style.display = ''; return; }
      section.style.display = (severity === 'all' || sev === severity) ? '' : 'none';
      if (severity === 'all' || sev === severity) section.classList.remove('collapsed');
    });
  }

  function hideIssue(e, btn) {
    e.stopPropagation();
    const issue = btn.closest('.issue');
    issue.classList.add('hidden-issue');
    issue.classList.remove('open');
    const ids = getHiddenIds();
    if (!ids.includes(issue.dataset.id)) ids.push(issue.dataset.id);
    saveHiddenIds(ids);
    refreshUI();
  }

  function restoreIssue(e, id) {
    e.stopPropagation();
    const issue = document.querySelector('.issue[data-id="' + id + '"]');
    if (issue) issue.classList.remove('hidden-issue');
    saveHiddenIds(getHiddenIds().filter(i => i !== id));
    refreshUI();
  }

  function showAllIssues() {
    document.querySelectorAll('.issue.hidden-issue').forEach(i => i.classList.remove('hidden-issue'));
    saveHiddenIds([]);
    refreshUI();
  }

  function refreshUI() {
    ['critical', 'important', 'minor'].forEach(sev => {
      const visible = document.querySelectorAll('.issue[data-sev="' + sev + '"]:not(.hidden-issue)');
      const el = document.getElementById('count-' + sev);
      if (el) el.textContent = visible.length;
    });
    document.querySelectorAll('.section[data-severity]').forEach(sec => {
      if (sec.dataset.severity === 'positive') return;
      const visible = sec.querySelectorAll('.issue:not(.hidden-issue)');
      const countEl = sec.querySelector('.section-count');
      if (countEl) countEl.textContent = '(' + visible.length + ')';
      sec.classList.toggle('section-empty', visible.length === 0);
    });
    const list = document.getElementById('hiddenList');
    const countBadge = document.getElementById('hiddenCount');
    const drawer = document.getElementById('hiddenDrawer');
    list.innerHTML = '';
    const hidden = document.querySelectorAll('.issue.hidden-issue');
    countBadge.textContent = hidden.length;
    drawer.classList.toggle('has-items', hidden.length > 0);
    hidden.forEach(issue => {
      const id = issue.dataset.id;
      const title = issue.querySelector('.title').textContent;
      const num = issue.querySelector('.num').textContent;
      const div = document.createElement('div');
      div.className = 'hidden-item';
      div.innerHTML = '<span class="hi-num">' + num + '</span><span class="hi-title">' + title + '</span><button class="hi-restore" onclick="restoreIssue(event, \'' + id + '\')">Ripristina</button>';
      list.appendChild(div);
    });
  }

  (function init() {
    getHiddenIds().forEach(id => {
      const issue = document.querySelector('.issue[data-id="' + id + '"]');
      if (issue) issue.classList.add('hidden-issue');
    });
    refreshUI();
  })();
</script>

</body>
</html>
