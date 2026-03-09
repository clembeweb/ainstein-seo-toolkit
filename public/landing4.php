<?php
// Data
$plans = \Core\Database::fetchAll("SELECT * FROM plans WHERE is_active = 1 ORDER BY price_monthly");

// Social proof stats (with fallbacks)
try {
    $statArticles = (int)(\Core\Database::fetch("SELECT COUNT(*) as c FROM aic_articles")['c'] ?? 0);
} catch (\Exception $e) { $statArticles = 0; }
try {
    $statKeywords = (int)(\Core\Database::fetch("SELECT COUNT(*) as c FROM st_keywords")['c'] ?? 0);
    $statKeywords += (int)(\Core\Database::fetch("SELECT COUNT(*) as c FROM kr_keywords")['c'] ?? 0);
} catch (\Exception $e) { $statKeywords = 0; }

$displayArticles = $statArticles > 50 ? number_format($statArticles) . '+' : '500+';
$displayKeywords = $statKeywords > 100 ? number_format($statKeywords) . '+' : '10.000+';
$displayMinutes = $statArticles > 50 ? number_format($statArticles * 240) . '+' : '50.000+';

$currentPage = 'home';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ainstein — Automatizza il tuo marketing digitale con l'AI</title>
    <meta name="description" content="Ainstein automatizza ricerca keyword, creazione contenuti SEO, audit tecnici e campagne Google Ads. Dai la keyword, l'AI fa il resto. Prova gratis.">
    <link rel="icon" type="image/svg+xml" href="<?= url('/favicon.svg') ?>">
    <meta name="theme-color" content="#f59e0b">

    <!-- OG -->
    <meta property="og:title" content="Ainstein — Automatizza il tuo marketing digitale con l'AI">
    <meta property="og:description" content="Dalla keyword alla pubblicazione. 7 moduli AI integrati per SEO, content e Google Ads.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ainstein.it">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">

    <style>
        /* ============================================
           AINSTEIN — Design System: Light Clean
           Aesthetic: Swiss Precision + Warm Amber
           ============================================ */

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --font: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --white: #ffffff;
            --bg: #fafafa;
            --bg-alt: #f4f4f5;
            --text: #18181b;
            --text-md: #3f3f46;
            --text-muted: #71717a;
            --text-light: #a1a1aa;
            --border: #e4e4e7;
            --border-light: #f4f4f5;
            --amber: #f59e0b;
            --amber-hover: #d97706;
            --amber-light: #fef3c7;
            --amber-dark: #92400e;
            --slate-900: #0f172a;
            --max-w: 1200px;
            --nav-h: 72px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font);
            background: var(--white);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
            line-height: 1.6;
        }

        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; }

        /* ---------- NAVBAR ---------- */
        .site-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            height: var(--nav-h);
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.82);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid transparent;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .site-nav.scrolled {
            border-bottom-color: var(--border);
            box-shadow: 0 1px 16px rgba(0,0,0,0.04);
        }
        .site-nav-inner {
            max-width: var(--max-w); width: 100%; padding: 0 24px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .site-nav-logo img { height: 28px; display: block; }
        .site-nav-links { display: flex; align-items: center; gap: 36px; }
        .site-nav-link {
            font-size: 15px; font-weight: 500; color: var(--text-muted);
            transition: color 0.2s; position: relative;
        }
        .site-nav-link:hover, .site-nav-link.active { color: var(--text); }
        .site-nav-actions { display: flex; align-items: center; gap: 20px; }
        .nav-cta-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 28px; border-radius: 50px;
            background: var(--amber); color: var(--amber-dark);
            font-size: 14px; font-weight: 700; letter-spacing: -0.01em;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 3px rgba(245,158,11,0.3);
        }
        .nav-cta-btn:hover {
            background: var(--amber-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(245,158,11,0.3);
        }
        .nav-hamburger {
            display: none; background: none; border: none; cursor: pointer;
            width: 32px; height: 24px; position: relative; padding: 0;
        }
        .nav-hamburger span {
            display: block; width: 100%; height: 2px; background: var(--text);
            position: absolute; left: 0; transition: all 0.3s;
        }
        .nav-hamburger span:nth-child(1) { top: 2px; }
        .nav-hamburger span:nth-child(2) { top: 11px; }
        .nav-hamburger span:nth-child(3) { top: 20px; }
        .nav-hamburger.open span:nth-child(1) { top: 11px; transform: rotate(45deg); }
        .nav-hamburger.open span:nth-child(2) { opacity: 0; }
        .nav-hamburger.open span:nth-child(3) { top: 11px; transform: rotate(-45deg); }
        .nav-mobile-menu {
            display: none; flex-direction: column; gap: 4px;
            background: var(--white); border-top: 1px solid var(--border);
            padding: 16px 24px 24px; box-shadow: 0 16px 48px rgba(0,0,0,0.08);
        }
        .nav-mobile-menu a {
            padding: 12px 0; font-size: 16px; font-weight: 500; color: var(--text-md);
            border-bottom: 1px solid var(--border-light);
        }
        .nav-mobile-menu hr { border: none; border-top: 1px solid var(--border); margin: 8px 0; }

        /* ---------- SECTIONS ---------- */
        .section-pad { padding: 120px 24px; }
        .section-inner { max-width: var(--max-w); margin: 0 auto; }
        .section-label {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.1em; color: var(--amber-dark);
            margin-bottom: 16px;
        }
        .section-title {
            font-size: clamp(32px, 4.5vw, 52px);
            font-weight: 800; letter-spacing: -0.035em;
            line-height: 1.08; margin-bottom: 16px; color: var(--text);
        }
        .section-desc {
            font-size: 18px; color: var(--text-muted); line-height: 1.7;
            max-width: 600px;
        }
        .text-center { text-align: center; }
        .mx-auto { margin-left: auto; margin-right: auto; }

        /* ---------- HERO ---------- */
        .hero {
            padding: calc(var(--nav-h) + 80px) 24px 100px;
            text-align: center; position: relative; overflow: hidden;
            background: linear-gradient(180deg, #fffbeb 0%, var(--white) 60%);
        }
        .hero::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background:
                radial-gradient(circle at 25% 30%, rgba(245,158,11,0.07) 0%, transparent 50%),
                radial-gradient(circle at 75% 60%, rgba(217,119,6,0.05) 0%, transparent 50%);
        }
        .hero-content { position: relative; z-index: 1; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 20px; border-radius: 50px;
            background: var(--amber-light); color: var(--amber-dark);
            font-size: 13px; font-weight: 700; margin-bottom: 28px;
            border: 1px solid rgba(245,158,11,0.2);
        }
        .hero-badge svg { width: 16px; height: 16px; }
        .hero h1 {
            font-size: clamp(40px, 6vw, 68px);
            font-weight: 800; letter-spacing: -0.04em;
            line-height: 1.05; margin-bottom: 24px; max-width: 820px; margin-inline: auto;
        }
        .hero h1 .accent {
            background: linear-gradient(135deg, var(--amber), #b45309);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-sub {
            font-size: clamp(17px, 2vw, 20px); color: var(--text-muted);
            max-width: 580px; margin: 0 auto 40px; line-height: 1.65;
        }
        .hero-ctas { display: flex; align-items: center; justify-content: center; gap: 16px; flex-wrap: wrap; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 16px 36px; border-radius: 50px;
            background: var(--amber); color: var(--amber-dark);
            font-size: 16px; font-weight: 700; letter-spacing: -0.01em;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(245,158,11,0.3);
        }
        .btn-primary:hover {
            background: var(--amber-hover); transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(245,158,11,0.35);
        }
        .btn-primary svg { width: 18px; height: 18px; }
        .btn-secondary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 16px 36px; border-radius: 50px;
            border: 2px solid var(--border); color: var(--text-md);
            font-size: 16px; font-weight: 600; transition: all 0.2s; background: transparent;
        }
        .btn-secondary:hover { border-color: var(--amber); color: var(--amber-dark); }

        /* Hero mockup */
        .hero-mockup {
            margin-top: 64px; position: relative; max-width: 960px; margin-inline: auto;
        }
        .hero-mockup .glow {
            position: absolute; inset: -40px; z-index: -1; border-radius: 32px;
            background: radial-gradient(ellipse at center, rgba(245,158,11,0.12) 0%, transparent 70%);
            filter: blur(40px);
        }
        .mockup-frame {
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 32px 80px rgba(0,0,0,0.1), 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid var(--border); background: var(--white);
        }
        .mockup-bar {
            height: 36px; background: #f9fafb; display: flex; align-items: center;
            gap: 6px; padding: 0 14px; border-bottom: 1px solid var(--border);
        }
        .mockup-content {
            display: grid; grid-template-columns: 1fr 1.4fr; min-height: 320px;
        }
        .mockup-input {
            padding: 28px 24px; border-right: 1px solid var(--border);
            display: flex; flex-direction: column; gap: 16px;
        }
        .mockup-label {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.08em; color: var(--text-light);
        }
        .mockup-field {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px; border-radius: 10px; border: 2px solid var(--amber);
            background: #fffbeb; font-size: 16px; font-weight: 600; color: var(--text);
        }
        .mockup-module-tag {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px; border-radius: 8px;
            background: var(--amber-light); color: var(--amber-dark);
            font-size: 12px; font-weight: 700; width: fit-content;
        }
        .mockup-steps { display: flex; flex-direction: column; gap: 10px; margin-top: 8px; }
        .mockup-step {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--text-muted);
        }
        .mockup-step.done { color: #10b981; }
        .mockup-step.active { color: var(--amber-dark); font-weight: 600; }
        .mockup-spinner {
            width: 14px; height: 14px; border: 2px solid var(--amber-light);
            border-top-color: var(--amber); border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .mockup-output {
            padding: 28px 24px; display: flex; flex-direction: column; gap: 16px;
        }
        .mockup-output-header {
            display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;
        }
        .mockup-output-title {
            font-size: 17px; font-weight: 700; color: var(--text);
            line-height: 1.35; flex: 1;
        }
        .mockup-score {
            font-size: 28px; font-weight: 800; color: #10b981;
            background: #d1fae5; padding: 6px 14px; border-radius: 12px;
            line-height: 1; white-space: nowrap;
        }
        .mockup-score small { font-size: 14px; color: #065f46; font-weight: 600; }
        .mockup-output-structure {
            display: flex; flex-direction: column; gap: 6px;
        }
        .mockup-h2 {
            font-size: 13px; color: var(--text-muted); padding: 8px 12px;
            background: var(--bg); border-radius: 8px; border-left: 3px solid var(--amber);
        }
        .mockup-output-stats {
            display: flex; gap: 16px; margin-top: auto;
            padding-top: 12px; border-top: 1px solid var(--border);
        }
        .mockup-output-stats span {
            font-size: 12px; font-weight: 600; color: var(--text-muted);
            padding: 4px 10px; background: var(--bg); border-radius: 6px;
        }

        /* Hero stats */
        .hero-stats {
            display: flex; justify-content: center; gap: 48px; margin-top: 48px;
            flex-wrap: wrap;
        }
        .hero-stat { text-align: center; }
        .hero-stat-num {
            font-size: 28px; font-weight: 800; letter-spacing: -0.03em; color: var(--text);
        }
        .hero-stat-label { font-size: 14px; color: var(--text-muted); margin-top: 2px; }

        /* ---------- PIPELINE ---------- */
        .pipeline-section {
            background: var(--bg); padding: 100px 24px;
        }
        .pipeline-header {
            text-align: center; margin-bottom: 64px;
        }
        .pipeline-flow {
            display: flex; align-items: flex-start; justify-content: center; gap: 12px;
            max-width: var(--max-w); margin: 0 auto;
        }
        .pipeline-step {
            flex: 1; max-width: 200px; text-align: center;
        }
        .pipeline-step-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px; background: var(--amber-light); color: var(--amber-dark);
        }
        .pipeline-step-icon svg { width: 24px; height: 24px; }
        .pipeline-step-label {
            font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 12px;
        }
        .pipeline-mini {
            border-radius: 12px; overflow: hidden; border: 1px solid var(--border);
            background: var(--white); box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        }
        .pipeline-mini-bar {
            height: 24px; background: #f9fafb; display: flex; align-items: center;
            gap: 4px; padding: 0 10px; border-bottom: 1px solid var(--border);
        }
        .pipeline-mini-bar span { width: 7px; height: 7px; border-radius: 50%; }
        .pipeline-mini-content {
            padding: 14px 12px; font-size: 12px; color: var(--text-md); text-align: left;
            min-height: 80px; display: flex; flex-direction: column; gap: 4px;
        }
        .pipeline-mini-content .mini-highlight {
            font-weight: 700; color: var(--text); font-size: 13px;
        }
        .pipeline-mini-content .mini-muted {
            color: var(--text-light); font-size: 11px;
        }
        .pipeline-mini-content .mini-badge {
            display: inline-flex; padding: 2px 8px; border-radius: 6px;
            font-size: 11px; font-weight: 700; width: fit-content;
        }
        .pipeline-arrow {
            display: flex; align-items: center; padding-top: 60px;
            color: var(--text-light); flex-shrink: 0;
        }
        .pipeline-arrow svg { width: 20px; height: 20px; }

        /* ---------- HOW IT WORKS ---------- */
        .how-section { background: var(--bg); }
        .how-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px;
            margin-top: 56px; position: relative;
        }
        .how-card {
            background: var(--white); border-radius: 20px; padding: 40px 32px;
            border: 1px solid var(--border); position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .how-card:hover { transform: translateY(-4px); box-shadow: 0 16px 48px rgba(0,0,0,0.06); }
        .how-num {
            font-size: 64px; font-weight: 800; color: var(--amber-light); letter-spacing: -0.04em;
            line-height: 1; margin-bottom: 16px;
        }
        .how-icon {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 20px;
        }
        .how-icon svg { width: 24px; height: 24px; }
        .how-icon.amber-bg { background: var(--amber-light); color: var(--amber-dark); }
        .how-icon.purple-bg { background: #ede9fe; color: #6d28d9; }
        .how-icon.emerald-bg { background: #d1fae5; color: #047857; }
        .how-card h3 { font-size: 20px; font-weight: 700; margin-bottom: 8px; letter-spacing: -0.02em; }
        .how-card p { font-size: 15px; color: var(--text-muted); line-height: 1.65; }

        /* Connectors between cards */
        .how-connector {
            position: absolute; top: 50%; width: 32px;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-light); z-index: 2;
        }
        .how-connector:first-of-type { left: calc(33.333% - 16px); }
        .how-connector:last-of-type { left: calc(66.666% - 16px); }
        .how-connector svg { width: 24px; height: 24px; }

        /* ---------- FEATURE BLOCKS ---------- */
        .feature-block { padding: 100px 24px; }
        .feature-block.bg-alt { background: var(--bg); }
        .feature-inner {
            max-width: var(--max-w); margin: 0 auto;
            display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center;
        }
        .feature-inner.reverse .feature-text { order: 2; }
        .feature-inner.reverse .feature-mockup-wrap { order: 1; }
        .feature-badge {
            display: inline-flex; padding: 5px 14px; border-radius: 8px;
            font-size: 12px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; margin-bottom: 16px;
        }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }
        .badge-emerald { background: #d1fae5; color: #065f46; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-rose { background: #ffe4e6; color: #9f1239; }
        .badge-cyan { background: #cffafe; color: #155e75; }
        .feature-headline {
            font-size: clamp(24px, 3vw, 34px); font-weight: 800;
            letter-spacing: -0.03em; line-height: 1.15; margin-bottom: 20px;
        }
        .feature-bullets { list-style: none; margin-bottom: 28px; }
        .feature-bullets li {
            display: flex; align-items: flex-start; gap: 12px;
            font-size: 16px; color: var(--text-md); padding: 8px 0; line-height: 1.5;
        }
        .feature-bullets li svg { width: 20px; height: 20px; color: #10b981; flex-shrink: 0; margin-top: 2px; }
        .feature-mockup-frame {
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 24px 64px rgba(0,0,0,0.1), 0 4px 16px rgba(0,0,0,0.06);
            border: 1px solid var(--border); background: var(--white);
        }
        .feature-mockup-bar {
            height: 36px; background: #f9fafb; display: flex; align-items: center;
            gap: 6px; padding: 0 14px; border-bottom: 1px solid var(--border);
        }
        .screen-dot { width: 10px; height: 10px; border-radius: 50%; }
        .screen-dot.r { background: #fca5a5; }
        .screen-dot.y { background: #fcd34d; }
        .screen-dot.g { background: #86efac; }
        .feature-mockup-body { padding: 24px; }

        /* Keyword cluster mockup */
        .cluster-viz { display: flex; flex-direction: column; align-items: center; gap: 20px; }
        .cluster-seed {
            padding: 12px 24px; border-radius: 50px; font-weight: 700; font-size: 15px;
            background: var(--amber-light); color: var(--amber-dark); border: 2px solid var(--amber);
        }
        .cluster-branches { display: flex; gap: 16px; width: 100%; }
        .cluster-branch {
            flex: 1; border-radius: 12px; padding: 14px; border: 1px solid var(--border);
            background: var(--bg); font-size: 13px;
        }
        .cluster-branch-title {
            font-weight: 700; font-size: 12px; text-transform: uppercase;
            letter-spacing: 0.06em; margin-bottom: 8px;
        }
        .cluster-kw {
            display: flex; justify-content: space-between; padding: 4px 0;
            font-size: 12px; color: var(--text-md);
        }
        .cluster-kw .vol { font-weight: 700; color: var(--text); }
        .cluster-stats {
            display: flex; gap: 16px; justify-content: center;
            padding: 12px 0 0; border-top: 1px solid var(--border);
            font-size: 13px; color: var(--text-muted); font-weight: 600;
        }

        /* Article gen mockup */
        .article-mockup { display: flex; flex-direction: column; gap: 16px; }
        .article-kw-badge {
            display: inline-flex; padding: 6px 14px; border-radius: 8px;
            background: var(--amber-light); color: var(--amber-dark);
            font-size: 13px; font-weight: 700; width: fit-content;
        }
        .article-structure { display: flex; flex-direction: column; gap: 6px; }
        .article-h2 {
            font-size: 13px; color: var(--text-md); padding: 8px 12px;
            background: var(--bg); border-radius: 8px; border-left: 3px solid var(--amber);
        }
        .article-stats {
            display: flex; gap: 12px; padding: 12px 0 0; border-top: 1px solid var(--border);
        }
        .article-stats span {
            font-size: 12px; font-weight: 600; color: var(--text-muted);
            padding: 4px 10px; background: var(--bg); border-radius: 6px;
        }
        .article-stats .score-badge {
            background: #d1fae5; color: #065f46;
        }

        /* Campaign mockup */
        .campaign-mockup { display: flex; flex-direction: column; gap: 14px; }
        .campaign-header {
            font-size: 16px; font-weight: 700; color: var(--text);
            padding-bottom: 10px; border-bottom: 1px solid var(--border);
        }
        .campaign-group {
            padding: 14px; border-radius: 12px; border: 1px solid var(--border);
            background: var(--bg);
        }
        .campaign-group-title {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 6px;
        }
        .campaign-group-title .kw-count {
            font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 50px;
            background: #ffe4e6; color: #9f1239;
        }
        .campaign-group-keywords {
            display: flex; flex-wrap: wrap; gap: 4px;
        }
        .campaign-group-keywords span {
            font-size: 11px; padding: 3px 8px; border-radius: 6px;
            background: var(--white); border: 1px solid var(--border); color: var(--text-muted);
        }
        .campaign-stats {
            display: flex; gap: 12px; padding: 12px 0 0; border-top: 1px solid var(--border);
            font-size: 13px; color: var(--text-muted); font-weight: 600;
        }

        /* ---------- MODULES GRID ---------- */
        .modules-section { background: var(--bg); }
        .modules-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 24px; margin-top: 56px;
        }
        .module-card {
            background: var(--white); border-radius: 20px; border: 1px solid var(--border);
            padding: 32px; transition: transform 0.3s, box-shadow 0.3s;
        }
        .module-card:hover { transform: translateY(-4px); box-shadow: 0 16px 48px rgba(0,0,0,0.06); }
        .module-icon {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
        }
        .module-icon svg { width: 24px; height: 24px; }
        .module-card h4 { font-size: 18px; font-weight: 700; margin-bottom: 6px; letter-spacing: -0.02em; }
        .module-card p { font-size: 14px; color: var(--text-muted); line-height: 1.55; margin-bottom: 16px; }
        .module-link {
            font-size: 14px; font-weight: 600; display: inline-flex;
            align-items: center; gap: 4px; transition: gap 0.2s;
        }
        .module-link:hover { gap: 8px; }
        .module-link svg { width: 16px; height: 16px; }

        /* ---------- PAIN/SOLUTION ---------- */
        .pain-section { background: var(--bg); }
        .pain-grid { margin-top: 56px; }
        .pain-row {
            display: grid; grid-template-columns: 1fr 40px 1fr; gap: 0; align-items: stretch;
        }
        .pain-cell {
            padding: 20px 28px; font-size: 15px; line-height: 1.6;
            display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid var(--border);
        }
        .pain-cell:first-child { background: #fef2f2; color: #991b1b; }
        .pain-cell:last-child { background: #f0fdf4; color: #166534; }
        .pain-arrow {
            display: flex; align-items: center; justify-content: center;
            background: var(--white); border-bottom: 1px solid var(--border);
        }
        .pain-arrow svg { width: 20px; height: 20px; color: var(--text-light); }
        .pain-icon { flex-shrink: 0; width: 24px; height: 24px; }
        .pain-row:first-child .pain-cell:first-child { border-radius: 16px 0 0 0; }
        .pain-row:first-child .pain-cell:last-child { border-radius: 0 16px 0 0; }
        .pain-row:last-child .pain-cell { border-bottom: none; }
        .pain-row:last-child .pain-cell:first-child { border-radius: 0 0 0 16px; }
        .pain-row:last-child .pain-cell:last-child { border-radius: 0 0 16px 0; }
        .pain-row:last-child .pain-arrow { border-bottom: none; }
        .pain-header {
            display: grid; grid-template-columns: 1fr 40px 1fr; gap: 0;
            margin-bottom: 0;
        }
        .pain-header-cell {
            padding: 14px 28px; font-size: 13px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.08em;
        }
        .pain-header-cell:first-child { color: #dc2626; }
        .pain-header-cell:last-child { color: #16a34a; }

        /* ---------- SOCIAL PROOF ---------- */
        .proof-section {
            background: var(--slate-900); color: #fff;
            position: relative; overflow: hidden;
        }
        .proof-section::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background:
                radial-gradient(circle at 20% 50%, rgba(245,158,11,0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(59,130,246,0.06) 0%, transparent 50%);
        }
        .proof-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 40px; position: relative; z-index: 1;
        }
        .proof-item { text-align: center; }
        .proof-num {
            font-size: clamp(36px, 5vw, 56px); font-weight: 800;
            letter-spacing: -0.04em; line-height: 1;
            background: linear-gradient(135deg, #fff, #94a3b8);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .proof-label { font-size: 15px; color: #94a3b8; margin-top: 8px; }

        /* ---------- PRICING PREVIEW ---------- */
        .pricing-toggle {
            display: flex; align-items: center; justify-content: center;
            gap: 16px; margin-top: 32px;
        }
        .pricing-toggle-label { font-size: 15px; font-weight: 500; color: var(--text-muted); }
        .pricing-toggle-label.active { color: var(--text); font-weight: 700; }
        .pricing-switch {
            width: 52px; height: 28px; border-radius: 14px;
            background: var(--border); cursor: pointer; position: relative;
            transition: background 0.3s; border: none; padding: 0;
        }
        .pricing-switch.active { background: var(--amber); }
        .pricing-switch::after {
            content: ''; position: absolute; top: 3px; left: 3px;
            width: 22px; height: 22px; border-radius: 50%;
            background: #fff; transition: transform 0.3s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.15);
        }
        .pricing-switch.active::after { transform: translateX(24px); }
        .pricing-save-badge {
            display: inline-flex; padding: 3px 10px; border-radius: 50px;
            background: #d1fae5; color: #065f46; font-size: 12px; font-weight: 700;
        }
        .pricing-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 20px; margin-top: 48px;
        }
        .pricing-card {
            background: var(--white); border-radius: 20px; border: 1px solid var(--border);
            padding: 32px 24px; text-align: left; transition: transform 0.3s, box-shadow 0.3s;
        }
        .pricing-card:hover { transform: translateY(-4px); box-shadow: 0 20px 48px rgba(0,0,0,0.06); }
        .pricing-card.featured {
            border-color: var(--amber); position: relative;
            box-shadow: 0 0 0 1px var(--amber), 0 16px 48px rgba(245,158,11,0.1);
        }
        .pricing-card.featured::before {
            content: 'Piu scelto'; position: absolute; top: -12px; left: 50%;
            transform: translateX(-50%); padding: 4px 16px; border-radius: 50px;
            background: var(--amber); color: var(--amber-dark);
            font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em;
        }
        .p-name { font-size: 18px; font-weight: 700; margin-bottom: 4px; color: var(--text); }
        .p-desc { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; }
        .p-price { margin-bottom: 4px; }
        .p-price .amount { font-size: 44px; font-weight: 800; letter-spacing: -0.04em; color: var(--text); }
        .p-price .period { font-size: 16px; color: var(--text-muted); font-weight: 400; }
        .p-price .old { text-decoration: line-through; color: var(--text-light); font-size: 18px; margin-right: 8px; }
        .p-yearly { font-size: 13px; color: var(--text-light); margin-bottom: 20px; height: 18px; }
        .p-credits {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 14px; border-radius: 10px;
            background: var(--amber-light); font-size: 14px; font-weight: 700;
            color: var(--amber-dark); margin-bottom: 20px;
        }
        .p-credits svg { width: 16px; height: 16px; }
        .p-features { list-style: none; margin-bottom: 24px; min-height: 160px; }
        .p-features li {
            display: flex; align-items: center; gap: 8px;
            font-size: 14px; color: var(--text-md); padding: 5px 0;
        }
        .p-features li svg { width: 16px; height: 16px; color: #10b981; flex-shrink: 0; }
        .p-btn {
            display: block; text-align: center; padding: 12px 20px; border-radius: 12px;
            font-size: 14px; font-weight: 700; transition: all 0.2s; width: 100%;
        }
        .p-btn-primary {
            background: var(--amber); color: var(--amber-dark);
            box-shadow: 0 1px 3px rgba(245,158,11,0.25);
        }
        .p-btn-primary:hover { background: var(--amber-hover); transform: translateY(-1px); }
        .p-btn-outline {
            border: 2px solid var(--border); color: var(--text-md); background: transparent;
        }
        .p-btn-outline:hover { border-color: var(--amber); color: var(--amber-dark); }
        .pricing-note {
            font-size: 14px; color: var(--text-light); max-width: 520px;
            margin: 32px auto 0; text-align: center; line-height: 1.6;
        }
        .pricing-note a { color: var(--amber-dark); font-weight: 600; }
        .pricing-more {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 15px; font-weight: 600; color: var(--amber-dark);
            margin-top: 24px; transition: gap 0.2s;
        }
        .pricing-more:hover { gap: 10px; }
        .pricing-more svg { width: 18px; height: 18px; }

        /* ---------- CTA FINALE ---------- */
        .cta-final {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            text-align: center; color: #fff; position: relative; overflow: hidden;
        }
        .cta-final::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(circle at 50% 80%, rgba(245,158,11,0.1) 0%, transparent 60%);
        }
        .cta-final h2 {
            font-size: clamp(32px, 4.5vw, 52px); font-weight: 800;
            letter-spacing: -0.035em; margin-bottom: 16px; position: relative; z-index: 1;
        }
        .cta-final p {
            font-size: 18px; color: #94a3b8; margin-bottom: 40px; position: relative; z-index: 1;
        }
        .btn-cta-large {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 18px 44px; border-radius: 50px;
            background: var(--amber); color: var(--amber-dark);
            font-size: 18px; font-weight: 700; letter-spacing: -0.01em;
            transition: all 0.2s; position: relative; z-index: 1;
            box-shadow: 0 4px 16px rgba(245,158,11,0.3);
        }
        .btn-cta-large:hover {
            background: var(--amber-hover); transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(245,158,11,0.35);
        }
        .btn-cta-large svg { width: 20px; height: 20px; }

        /* ---------- FOOTER ---------- */
        .site-footer {
            background: var(--slate-900); color: #94a3b8; padding: 80px 24px 40px;
        }
        .footer-inner { max-width: var(--max-w); margin: 0 auto; }
        .footer-grid {
            display: grid; grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px; margin-bottom: 48px;
        }
        .footer-brand p { font-size: 14px; line-height: 1.7; margin-top: 16px; max-width: 280px; }
        .footer-col h4 {
            font-size: 13px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.08em; color: #e2e8f0; margin-bottom: 16px;
        }
        .footer-col a {
            display: block; font-size: 14px; color: #94a3b8;
            padding: 4px 0; transition: color 0.2s;
        }
        .footer-col a:hover { color: #fff; }
        .footer-bottom {
            padding-top: 32px; border-top: 1px solid rgba(255,255,255,0.08);
            font-size: 13px;
        }

        /* ---------- REVEAL ANIMATION ---------- */
        .reveal {
            opacity: 0; transform: translateY(24px);
            transition: opacity 0.7s cubic-bezier(0.16,1,0.3,1), transform 0.7s cubic-bezier(0.16,1,0.3,1);
        }
        .reveal.visible { opacity: 1; transform: none; }
        .reveal-d1 { transition-delay: 0.08s; }
        .reveal-d2 { transition-delay: 0.16s; }
        .reveal-d3 { transition-delay: 0.24s; }
        .reveal-d4 { transition-delay: 0.32s; }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 1024px) {
            .feature-inner { grid-template-columns: 1fr; gap: 40px; }
            .feature-inner.reverse .feature-text { order: 1; }
            .feature-inner.reverse .feature-mockup-wrap { order: -1; }
            .feature-mockup-wrap { order: -1; }
            .cluster-branches { flex-direction: column; }
            .modules-grid { grid-template-columns: repeat(2, 1fr); }
            .pricing-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-grid { grid-template-columns: repeat(2, 1fr); }
            .proof-grid { grid-template-columns: repeat(2, 1fr); gap: 32px; }
        }
        @media (max-width: 768px) {
            .site-nav-links, .site-nav-actions { display: none; }
            .nav-hamburger { display: block; }
            .nav-mobile-menu.open { display: flex; }
            .how-grid { grid-template-columns: 1fr; }
            .how-connector { display: none; }
            .pain-row { grid-template-columns: 1fr; }
            .pain-arrow { display: none; }
            .pain-header { grid-template-columns: 1fr; }
            .pain-header-cell:last-child { display: none; }
            .pain-cell:first-child { border-radius: 12px 12px 0 0 !important; }
            .pain-cell:last-child { border-radius: 0 0 12px 12px !important; margin-bottom: 16px; }
            .pain-row:first-child .pain-cell:first-child,
            .pain-row:first-child .pain-cell:last-child,
            .pain-row:last-child .pain-cell:first-child,
            .pain-row:last-child .pain-cell:last-child { border-radius: inherit; }
            .hero-stats { gap: 32px; }
            .hero h1 { font-size: clamp(32px, 7vw, 48px); }
            .mockup-content { grid-template-columns: 1fr; }
            .mockup-input { border-right: none; border-bottom: 1px solid var(--border); }
            .pipeline-flow { flex-direction: column; align-items: center; gap: 8px; }
            .pipeline-step { max-width: 320px; width: 100%; }
            .pipeline-arrow { padding-top: 0; transform: rotate(90deg); }
        }
        @media (max-width: 640px) {
            .pricing-grid { grid-template-columns: 1fr; max-width: 400px; margin-inline: auto; }
            .modules-grid { grid-template-columns: 1fr; }
            .hero-ctas { flex-direction: column; }
            .footer-grid { grid-template-columns: 1fr; gap: 32px; }
            .proof-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ====== NAVBAR ====== -->
<?php include __DIR__ . '/includes/site-header.php'; ?>

<!-- ====== HERO ====== -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge reveal">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Creata da professionisti SEO e ADV
        </div>

        <h1 class="reveal reveal-d1">
            Dai la keyword.<br><span class="accent">Ainstein fa il resto.</span>
        </h1>

        <p class="hero-sub reveal reveal-d2">
            Ricerca, piano editoriale, articoli SEO, pubblicazione. Processi che richiedono giorni, completati in minuti. Non AI generica &mdash; automazione costruita da chi fa SEO ogni giorno.
        </p>

        <div class="hero-ctas reveal reveal-d3">
            <a href="<?= url('/register') ?>" class="btn-primary">
                Prova gratis &mdash; 30 crediti
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="#come-funziona" class="btn-secondary">Guarda come funziona &darr;</a>
        </div>

        <div class="hero-mockup reveal reveal-d4">
            <div class="glow"></div>
            <div class="mockup-frame">
                <div class="mockup-bar"><span class="screen-dot r"></span><span class="screen-dot y"></span><span class="screen-dot g"></span></div>
                <div class="mockup-content">
                    <div class="mockup-input">
                        <div class="mockup-label">Keyword</div>
                        <div class="mockup-field">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:16px;height:16px;color:var(--text-light);flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <span>scarpe running</span>
                        </div>
                        <div class="mockup-module-tag">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                            AI Content Generator
                        </div>
                        <div class="mockup-steps">
                            <div class="mockup-step done">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Analisi SERP
                            </div>
                            <div class="mockup-step done">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Brief generato
                            </div>
                            <div class="mockup-step active">
                                <div class="mockup-spinner"></div>
                                Scrittura articolo...
                            </div>
                        </div>
                    </div>
                    <div class="mockup-output">
                        <div class="mockup-output-header">
                            <span class="mockup-output-title">Le migliori scarpe da running per principianti: guida 2026</span>
                            <span class="mockup-score">92<small>/100</small></span>
                        </div>
                        <div class="mockup-output-structure">
                            <div class="mockup-h2">H2: Come scegliere le scarpe da running</div>
                            <div class="mockup-h2">H2: Top 10 scarpe running principianti</div>
                            <div class="mockup-h2">H2: Ammortizzazione vs reattivita</div>
                            <div class="mockup-h2">H2: Errori da evitare nella scelta</div>
                        </div>
                        <div class="mockup-output-stats">
                            <span>2.800 parole</span>
                            <span>12 H2/H3</span>
                            <span>Tempo: 8 min</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-stats reveal">
            <div class="hero-stat">
                <div class="hero-stat-num"><?= $displayArticles ?></div>
                <div class="hero-stat-label">Articoli generati</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-num">7</div>
                <div class="hero-stat-label">Moduli AI integrati</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-num">10 min</div>
                <div class="hero-stat-label">Per un articolo SEO</div>
            </div>
        </div>
    </div>
</section>

<!-- ====== PIPELINE ====== -->
<section class="pipeline-section">
    <div class="pipeline-header">
        <div class="section-label reveal">Il flusso</div>
        <h2 class="section-title reveal reveal-d1">Dall'idea alla pubblicazione</h2>
        <p class="section-desc mx-auto reveal reveal-d2">Zero copia-incolla tra tool diversi. Un flusso unico dalla ricerca alla pubblicazione.</p>
    </div>

    <div class="pipeline-flow reveal reveal-d3">
        <!-- Step 1 -->
        <div class="pipeline-step">
            <div class="pipeline-step-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="pipeline-step-label">Keyword seed</div>
            <div class="pipeline-mini">
                <div class="pipeline-mini-bar"><span style="background:#fca5a5;"></span><span style="background:#fcd34d;"></span><span style="background:#86efac;"></span></div>
                <div class="pipeline-mini-content">
                    <div class="mini-muted">Inserisci keyword</div>
                    <div class="mini-highlight" style="padding:6px 10px;border:2px solid var(--amber);border-radius:8px;background:#fffbeb;">scarpe running</div>
                </div>
            </div>
        </div>

        <div class="pipeline-arrow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></div>

        <!-- Step 2 -->
        <div class="pipeline-step">
            <div class="pipeline-step-icon" style="background:#ede9fe;color:#6d28d9;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            </div>
            <div class="pipeline-step-label">Ricerca AI</div>
            <div class="pipeline-mini">
                <div class="pipeline-mini-bar"><span style="background:#fca5a5;"></span><span style="background:#fcd34d;"></span><span style="background:#86efac;"></span></div>
                <div class="pipeline-mini-content">
                    <div class="mini-highlight">120+ keyword</div>
                    <div class="mini-muted">5 cluster &middot; volumi &middot; intent</div>
                    <div class="mini-badge" style="background:#ede9fe;color:#6d28d9;">Completato</div>
                </div>
            </div>
        </div>

        <div class="pipeline-arrow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></div>

        <!-- Step 3 -->
        <div class="pipeline-step">
            <div class="pipeline-step-icon" style="background:#dbeafe;color:#1e40af;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="pipeline-step-label">Piano editoriale</div>
            <div class="pipeline-mini">
                <div class="pipeline-mini-bar"><span style="background:#fca5a5;"></span><span style="background:#fcd34d;"></span><span style="background:#86efac;"></span></div>
                <div class="pipeline-mini-content">
                    <div class="mini-highlight">12 mesi di contenuti</div>
                    <div class="mini-muted">48 articoli pianificati</div>
                    <div class="mini-badge" style="background:#dbeafe;color:#1e40af;">Pronto</div>
                </div>
            </div>
        </div>

        <div class="pipeline-arrow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></div>

        <!-- Step 4 -->
        <div class="pipeline-step">
            <div class="pipeline-step-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="pipeline-step-label">Articolo SEO</div>
            <div class="pipeline-mini">
                <div class="pipeline-mini-bar"><span style="background:#fca5a5;"></span><span style="background:#fcd34d;"></span><span style="background:#86efac;"></span></div>
                <div class="pipeline-mini-content">
                    <div class="mini-highlight">2.800 parole</div>
                    <div class="mini-muted">SEO Score: 92/100</div>
                    <div class="mini-badge" style="background:#d1fae5;color:#065f46;">8 min</div>
                </div>
            </div>
        </div>

        <div class="pipeline-arrow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></div>

        <!-- Step 5 -->
        <div class="pipeline-step">
            <div class="pipeline-step-icon" style="background:#d1fae5;color:#047857;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="pipeline-step-label">Pubblicato su WP</div>
            <div class="pipeline-mini">
                <div class="pipeline-mini-bar"><span style="background:#fca5a5;"></span><span style="background:#fcd34d;"></span><span style="background:#86efac;"></span></div>
                <div class="pipeline-mini-content">
                    <div class="mini-highlight">Post pubblicato</div>
                    <div class="mini-muted">WordPress &middot; 1 click</div>
                    <div class="mini-badge" style="background:#d1fae5;color:#065f46;">Live</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== COME FUNZIONA ====== -->
<section class="section-pad how-section" id="come-funziona">
    <div class="section-inner text-center">
        <div class="section-label reveal">Come funziona</div>
        <h2 class="section-title reveal reveal-d1">Tre passi. Risultati reali.</h2>
        <p class="section-desc mx-auto reveal reveal-d2">Ainstein non e l'ennesimo tool AI generico. E un sistema costruito da professionisti SEO e ADV.</p>

        <div class="how-grid">
            <div class="how-card reveal">
                <div class="how-num">01</div>
                <div class="how-icon amber-bg">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <h3>Inserisci la keyword</h3>
                <p>Scrivi "scarpe running" e scegli il modulo. Ainstein si occupa del resto.</p>
            </div>

            <div class="how-card reveal reveal-d1">
                <div class="how-num">02</div>
                <div class="how-icon purple-bg">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
                <h3>L'AI analizza e crea</h3>
                <p>Studia la SERP di Google, analizza i competitor, genera output strategici &mdash; non contenuti generici.</p>
            </div>

            <div class="how-card reveal reveal-d2">
                <div class="how-num">03</div>
                <div class="how-icon emerald-bg">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3>Risultati pronti all'uso</h3>
                <p>Articoli SEO, piani editoriali, campagne Ads. Output professionali, non bozze da rifare.</p>
            </div>
        </div>
    </div>
</section>

<!-- ====== FEATURE 1: KEYWORD RESEARCH (purple) ====== -->
<section class="feature-block" id="funzionalita">
    <div class="feature-inner reveal">
        <div class="feature-text">
            <div class="feature-badge badge-purple">AI Keyword Research</div>
            <h3 class="feature-headline">Da 3 keyword a un piano editoriale completo</h3>
            <ul class="feature-bullets">
                <li>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Espansione AI: da 2-3 seed a 120+ keyword con volumi e intent
                </li>
                <li>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Clustering automatico per intento di ricerca
                </li>
                <li>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Piano editoriale 12 mesi pronto per la produzione
                </li>
            </ul>
            <a href="<?= url('/register') ?>" class="btn-primary" style="display:inline-flex;">Prova gratis</a>
        </div>
        <div class="feature-mockup-wrap">
            <div class="feature-mockup-frame">
                <div class="feature-mockup-bar"><span class="screen-dot r"></span><span class="screen-dot y"></span><span class="screen-dot g"></span></div>
                <div class="feature-mockup-body">
                    <div class="cluster-viz">
                        <div class="cluster-seed">scarpe running</div>
                        <div class="cluster-branches">
                            <div class="cluster-branch" style="border-left:3px solid #3b82f6;">
                                <div class="cluster-branch-title" style="color:#1e40af;">Informazionale</div>
                                <div class="cluster-kw"><span>come scegliere scarpe running</span><span class="vol">2.400</span></div>
                                <div class="cluster-kw"><span>migliori scarpe running 2026</span><span class="vol">5.400</span></div>
                                <div class="cluster-kw"><span>scarpe running principianti</span><span class="vol">1.900</span></div>
                            </div>
                            <div class="cluster-branch" style="border-left:3px solid #10b981;">
                                <div class="cluster-branch-title" style="color:#065f46;">Commerciale</div>
                                <div class="cluster-kw"><span>scarpe running offerte</span><span class="vol">3.600</span></div>
                                <div class="cluster-kw"><span>scarpe running economiche</span><span class="vol">1.300</span></div>
                                <div class="cluster-kw"><span>scarpe running Nike prezzi</span><span class="vol">2.900</span></div>
                            </div>
                            <div class="cluster-branch" style="border-left:3px solid var(--amber);">
                                <div class="cluster-branch-title" style="color:var(--amber-dark);">Transazionale</div>
                                <div class="cluster-kw"><span>comprare scarpe running online</span><span class="vol">880</span></div>
                                <div class="cluster-kw"><span>scarpe running uomo saldi</span><span class="vol">1.600</span></div>
                                <div class="cluster-kw"><span>scarpe running donna leggere</span><span class="vol">720</span></div>
                            </div>
                        </div>
                        <div class="cluster-stats">
                            <span>127 keyword</span>
                            <span>&middot;</span>
                            <span>5 cluster</span>
                            <span>&middot;</span>
                            <span>Volume totale: 48.200/mese</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== FEATURE 2: AI CONTENT (amber) ====== -->
<section class="feature-block bg-alt">
    <div class="feature-inner reverse reveal">
        <div class="feature-text">
            <div class="feature-badge badge-amber">AI Content Generator</div>
            <h3 class="feature-headline">Da keyword ad articolo pubblicato in 10 minuti</h3>
            <ul class="feature-bullets">
                <li>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Analisi SERP automatica dei top 10 risultati Google
                </li>
                <li>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Brief AI strategico + articolo completo ottimizzato
                </li>
                <li>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Pubblicazione WordPress automatica in 1 click
                </li>
            </ul>
            <a href="<?= url('/register') ?>" class="btn-primary" style="display:inline-flex;">Prova gratis</a>
        </div>
        <div class="feature-mockup-wrap">
            <div class="feature-mockup-frame">
                <div class="feature-mockup-bar"><span class="screen-dot r"></span><span class="screen-dot y"></span><span class="screen-dot g"></span></div>
                <div class="feature-mockup-body">
                    <div class="article-mockup">
                        <div class="article-kw-badge">scarpe running principianti</div>
                        <div class="article-structure">
                            <div class="article-h2">H2: Come scegliere le scarpe da running</div>
                            <div class="article-h2">H2: Top 10 scarpe running per principianti 2026</div>
                            <div class="article-h2">H2: Ammortizzazione vs reattivita: cosa conta</div>
                            <div class="article-h2">H2: Errori da evitare nella scelta</div>
                            <div class="article-h2">H2: FAQ: domande frequenti sulle scarpe running</div>
                        </div>
                        <div class="article-stats">
                            <span>2.800 parole</span>
                            <span class="score-badge">SEO Score: 92/100</span>
                            <span>Tempo: 8 min</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== FEATURE 3: GOOGLE ADS (rose) ====== -->
<section class="feature-block">
    <div class="feature-inner reveal">
        <div class="feature-text">
            <div class="feature-badge badge-rose">Google Ads Analyzer</div>
            <h3 class="feature-headline">Campagne Google Ads complete, generate dall'AI</h3>
            <ul class="feature-bullets">
                <li>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Analisi approfondita dei competitor e della landing page
                </li>
                <li>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Keyword negative + struttura gruppi di annunci automatica
                </li>
                <li>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Campagna pronta per Google Ads Editor
                </li>
            </ul>
            <a href="<?= url('/register') ?>" class="btn-primary" style="display:inline-flex;">Prova gratis</a>
        </div>
        <div class="feature-mockup-wrap">
            <div class="feature-mockup-frame">
                <div class="feature-mockup-bar"><span class="screen-dot r"></span><span class="screen-dot y"></span><span class="screen-dot g"></span></div>
                <div class="feature-mockup-body">
                    <div class="campaign-mockup">
                        <div class="campaign-header">Campagna: Scarpe Running Online</div>
                        <div class="campaign-group">
                            <div class="campaign-group-title">
                                <span>Scarpe Running Generiche</span>
                                <span class="kw-count">8 keyword</span>
                            </div>
                            <div class="campaign-group-keywords">
                                <span>scarpe running</span>
                                <span>scarpe da corsa</span>
                                <span>running shoes</span>
                                <span>scarpe jogging</span>
                            </div>
                        </div>
                        <div class="campaign-group">
                            <div class="campaign-group-title">
                                <span>Scarpe Running Brand</span>
                                <span class="kw-count">12 keyword</span>
                            </div>
                            <div class="campaign-group-keywords">
                                <span>scarpe running Nike</span>
                                <span>scarpe running Asics</span>
                                <span>Hoka running</span>
                                <span>Brooks Ghost</span>
                            </div>
                        </div>
                        <div class="campaign-group">
                            <div class="campaign-group-title">
                                <span>Scarpe Running Offerte</span>
                                <span class="kw-count">6 keyword</span>
                            </div>
                            <div class="campaign-group-keywords">
                                <span>scarpe running saldi</span>
                                <span>scarpe running economiche</span>
                                <span>offerte running</span>
                            </div>
                        </div>
                        <div class="campaign-stats">
                            <span>14 keyword negative</span>
                            <span>&middot;</span>
                            <span>3 gruppi annunci</span>
                            <span>&middot;</span>
                            <span>Pronto per Ads Editor</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== TOOLKIT GRID ====== -->
<section class="section-pad modules-section">
    <div class="section-inner text-center">
        <div class="section-label reveal">La suite completa</div>
        <h2 class="section-title reveal reveal-d1">7 moduli integrati. Un'unica piattaforma.</h2>
        <p class="section-desc mx-auto reveal reveal-d2">Ogni modulo risolve un pezzo del puzzle. Insieme, automatizzano l'intero flusso SEO e ADV.</p>

        <div class="modules-grid">
            <div class="module-card reveal">
                <div class="module-icon" style="background:#fef3c7;color:#92400e;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <h4>AI Content Generator</h4>
                <p>Da keyword ad articolo SEO pubblicato su WordPress in 10 minuti.</p>
                <a href="#funzionalita" class="module-link" style="color:#92400e;">Scopri di piu <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></a>
            </div>
            <div class="module-card reveal reveal-d1">
                <div class="module-icon" style="background:#ede9fe;color:#5b21b6;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <h4>AI Keyword Research</h4>
                <p>120+ keyword clusterizzate con volumi, intent e piano editoriale.</p>
                <a href="#funzionalita" class="module-link" style="color:#5b21b6;">Scopri di piu <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></a>
            </div>
            <div class="module-card reveal reveal-d2">
                <div class="module-icon" style="background:#d1fae5;color:#065f46;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h4>SEO Audit</h4>
                <p>Audit tecnico completo con AI action plan prioritizzato.</p>
                <a href="#funzionalita" class="module-link" style="color:#065f46;">Scopri di piu <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></a>
            </div>
            <div class="module-card reveal reveal-d3">
                <div class="module-icon" style="background:#dbeafe;color:#1e40af;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <h4>Position Tracking</h4>
                <p>Monitora posizioni su Google con report AI settimanali automatici.</p>
                <a href="#funzionalita" class="module-link" style="color:#1e40af;">Scopri di piu <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></a>
            </div>
            <div class="module-card reveal reveal-d4">
                <div class="module-icon" style="background:#ffe4e6;color:#9f1239;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                </div>
                <h4>Google Ads Analyzer</h4>
                <p>Campagne Ads complete generate dall'AI, pronte per Ads Editor.</p>
                <a href="#funzionalita" class="module-link" style="color:#9f1239;">Scopri di piu <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></a>
            </div>
            <div class="module-card reveal reveal-d4">
                <div class="module-icon" style="background:#cffafe;color:#155e75;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                </div>
                <h4>Content Creator</h4>
                <p>Contenuti HTML per WordPress, Shopify, PrestaShop e Magento.</p>
                <a href="#funzionalita" class="module-link" style="color:#155e75;">Scopri di piu <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></a>
            </div>
        </div>
    </div>
</section>

<!-- ====== PAIN / SOLUTION ====== -->
<section class="section-pad pain-section">
    <div class="section-inner">
        <div class="text-center">
            <div class="section-label reveal">Perche Ainstein</div>
            <h2 class="section-title reveal reveal-d1">Il problema che risolviamo</h2>
            <p class="section-desc mx-auto reveal reveal-d2">Il marketing digitale e pieno di attivita ripetitive e dispendiose. Ainstein le automatizza.</p>
        </div>

        <div class="pain-grid reveal reveal-d3" style="margin-top:48px;">
            <div class="pain-header">
                <div class="pain-header-cell">Senza Ainstein</div>
                <div></div>
                <div class="pain-header-cell">Con Ainstein</div>
            </div>

            <div class="pain-row">
                <div class="pain-cell">
                    <svg class="pain-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Scrivere articoli SEO richiede 4-6 ore di ricerca
                </div>
                <div class="pain-arrow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></div>
                <div class="pain-cell">
                    <svg class="pain-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Brief + articolo generati in 10 minuti
                </div>
            </div>
            <div class="pain-row">
                <div class="pain-cell">
                    <svg class="pain-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Trovare keyword utili richiede tool costosi
                </div>
                <div class="pain-arrow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></div>
                <div class="pain-cell">
                    <svg class="pain-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    120+ keyword clusterizzate con 1 click
                </div>
            </div>
            <div class="pain-row">
                <div class="pain-cell">
                    <svg class="pain-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Audit tecnici SEO complessi e manuali
                </div>
                <div class="pain-arrow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></div>
                <div class="pain-cell">
                    <svg class="pain-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Spider automatico + AI action plan prioritizzato
                </div>
            </div>
            <div class="pain-row">
                <div class="pain-cell">
                    <svg class="pain-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Monitorare posizioni e creare report e tedioso
                </div>
                <div class="pain-arrow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></div>
                <div class="pain-cell">
                    <svg class="pain-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Check automatici + report AI settimanali
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== SOCIAL PROOF ====== -->
<section class="section-pad proof-section">
    <div class="section-inner">
        <div class="proof-grid">
            <div class="proof-item reveal">
                <div class="proof-num" data-count="<?= $statArticles > 50 ? $statArticles : 500 ?>">0</div>
                <div class="proof-label">Articoli generati</div>
            </div>
            <div class="proof-item reveal reveal-d1">
                <div class="proof-num" data-count="<?= $statArticles > 50 ? $statArticles * 240 : 50000 ?>">0</div>
                <div class="proof-label">Minuti risparmiati</div>
            </div>
            <div class="proof-item reveal reveal-d2">
                <div class="proof-num">7</div>
                <div class="proof-label">Moduli AI integrati</div>
            </div>
            <div class="proof-item reveal reveal-d3">
                <div class="proof-num" data-count="<?= $statKeywords > 100 ? $statKeywords : 10000 ?>">0</div>
                <div class="proof-label">Keyword analizzate</div>
            </div>
        </div>
        <p class="reveal" style="text-align:center;color:#94a3b8;font-size:17px;max-width:600px;margin:40px auto 0;line-height:1.7;">
            Ainstein e costruita da professionisti SEO e ADV che automatizzano i processi che fanno ogni giorno. Non AI generica &mdash; automazione di qualita.
        </p>
    </div>
</section>

<!-- ====== PRICING PREVIEW ====== -->
<section class="section-pad" id="prezzi">
    <div class="section-inner text-center">
        <div class="section-label reveal">Prezzi</div>
        <h2 class="section-title reveal reveal-d1">Piani semplici, risultati straordinari</h2>
        <p class="section-desc mx-auto reveal reveal-d2">Tutti i piani includono tutti i moduli. Paghi solo in base a quanto usi.</p>

        <div class="pricing-toggle reveal reveal-d3">
            <span class="pricing-toggle-label active" id="toggleMonthly">Mensile</span>
            <button class="pricing-switch" id="pricingSwitch" aria-label="Toggle annuale"></button>
            <span class="pricing-toggle-label" id="toggleYearly">Annuale</span>
            <span class="pricing-save-badge">Risparmia 17%</span>
        </div>

        <div class="pricing-grid reveal reveal-d4">
            <?php
            $planFeatures = [
                'free' => ['30 crediti/mese', 'Tutti i moduli', 'Quick Check gratuiti', 'Documentazione completa'],
                'starter' => ['150 crediti/mese', 'Tutti i moduli', 'WordPress sync', 'Google Search Console', 'Report AI settimanali'],
                'pro' => ['500 crediti/mese', 'Tutti i moduli', 'WordPress sync', 'Google Search Console', 'Report AI settimanali', 'Supporto prioritario'],
                'agency' => ['1.500 crediti/mese', 'Tutti i moduli', 'WordPress sync', 'Google Search Console', 'Report AI settimanali', 'Supporto prioritario'],
            ];
            ?>
            <?php foreach ($plans as $plan):
                $slug = $plan['slug'] ?? strtolower($plan['name']);
                if ($slug === 'starter') continue;
                $features = $planFeatures[$slug] ?? $planFeatures['starter'];
                $isFeatured = ($plan['features'] ?? false) && isset(json_decode($plan['features'], true)['recommended']);
                $desc = json_decode($plan['features'] ?? '{}', true)['description'] ?? '';
            ?>
            <div class="pricing-card<?= $isFeatured ? ' featured' : '' ?>">
                <div class="p-name"><?= htmlspecialchars($plan['name']) ?></div>
                <div class="p-desc"><?= htmlspecialchars($desc) ?></div>
                <div class="p-price">
                    <?php if ((float)$plan['price_monthly'] === 0.0): ?>
                        <span class="amount">Gratis</span>
                    <?php else: ?>
                        <span class="old price-monthly-old" style="display:none;">&euro;<?= number_format($plan['price_monthly'], 0) ?></span>
                        <span class="amount price-monthly-val">&euro;<?= number_format($plan['price_monthly'], 0) ?></span>
                        <span class="amount price-yearly-val" style="display:none;">&euro;<?= number_format($plan['price_yearly'] / 12, 0) ?></span>
                        <span class="period">/mese</span>
                    <?php endif; ?>
                </div>
                <?php if ((float)$plan['price_yearly'] > 0): ?>
                    <div class="p-yearly">
                        <span class="yearly-text">&euro;<?= number_format($plan['price_monthly'] * 12, 0) ?>/anno</span>
                        <span class="yearly-text-annual" style="display:none;">&euro;<?= number_format($plan['price_yearly'], 0) ?>/anno (risparmi <?= round((1 - $plan['price_yearly'] / ($plan['price_monthly'] * 12)) * 100) ?>%)</span>
                    </div>
                <?php else: ?>
                    <div class="p-yearly">Per sempre</div>
                <?php endif; ?>
                <div class="p-credits">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <?= number_format($plan['credits_monthly']) ?> crediti/mese
                </div>
                <ul class="p-features">
                    <?php foreach ($features as $feat): ?>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <?= htmlspecialchars($feat) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= url('/register') ?>" class="p-btn <?= $isFeatured ? 'p-btn-primary' : 'p-btn-outline' ?>">
                    <?= (float)$plan['price_monthly'] === 0.0 ? 'Inizia gratis' : 'Prova gratis 7 giorni' ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <p class="pricing-note reveal">
            Un articolo SEO: 10 crediti &middot; Un audit: 10 crediti &middot; Monitoraggio keyword: 1 credito.
            <a href="<?= url('/docs/credits') ?>">Vedi tutti i costi &rarr;</a>
        </p>

        <div class="text-center reveal" style="margin-top:32px;">
            <a href="<?= url('/pricing') ?>" class="pricing-more">
                Confronta tutti i piani nel dettaglio
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </div>
</section>

<!-- ====== CTA FINALE ====== -->
<section class="section-pad cta-final">
    <h2 class="reveal">Inizia ad automatizzare il tuo SEO</h2>
    <p class="reveal reveal-d1">30 crediti gratuiti alla registrazione. Nessuna carta di credito richiesta.</p>
    <div class="reveal reveal-d2">
        <a href="<?= url('/register') ?>" class="btn-cta-large">
            Crea account gratis
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
    </div>
</section>

<!-- ====== FOOTER ====== -->
<?php include __DIR__ . '/includes/site-footer.php'; ?>

<script>
// Navbar scroll effect
const nav = document.getElementById('siteNav');
window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 10), { passive: true });

// Mobile menu
const hamburger = document.getElementById('navHamburger');
const mobileMenu = document.getElementById('navMobile');
hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('open');
    mobileMenu.classList.toggle('open');
});

// Scroll reveal
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

// Pricing toggle
const pricingSwitch = document.getElementById('pricingSwitch');
const toggleMonthly = document.getElementById('toggleMonthly');
const toggleYearly = document.getElementById('toggleYearly');
let isYearly = false;

pricingSwitch.addEventListener('click', () => {
    isYearly = !isYearly;
    pricingSwitch.classList.toggle('active', isYearly);
    toggleMonthly.classList.toggle('active', !isYearly);
    toggleYearly.classList.toggle('active', isYearly);

    document.querySelectorAll('.price-monthly-val').forEach(el => el.style.display = isYearly ? 'none' : '');
    document.querySelectorAll('.price-yearly-val').forEach(el => el.style.display = isYearly ? '' : 'none');
    document.querySelectorAll('.price-monthly-old').forEach(el => el.style.display = isYearly ? '' : 'none');
    document.querySelectorAll('.yearly-text').forEach(el => el.style.display = isYearly ? 'none' : '');
    document.querySelectorAll('.yearly-text-annual').forEach(el => el.style.display = isYearly ? '' : 'none');
});

// Counter animation
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const target = parseInt(el.dataset.count);
        if (!target) return;
        const duration = 2000;
        const start = performance.now();
        const fmt = (n) => n.toLocaleString('it-IT');
        const animate = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = fmt(Math.floor(target * eased)) + '+';
            if (progress < 1) requestAnimationFrame(animate);
        };
        requestAnimationFrame(animate);
        counterObserver.unobserve(el);
    });
}, { threshold: 0.5 });
document.querySelectorAll('.proof-num[data-count]').forEach(el => counterObserver.observe(el));
</script>
</body>
</html>
