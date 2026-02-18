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

        /* Hero screenshot */
        .hero-screenshot {
            margin-top: 64px; position: relative; max-width: 960px; margin-inline: auto;
        }
        .hero-screenshot img {
            width: 100%; border-radius: 16px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.1), 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid var(--border);
        }
        .hero-screenshot .glow {
            position: absolute; inset: -40px; z-index: -1; border-radius: 32px;
            background: radial-gradient(ellipse at center, rgba(245,158,11,0.12) 0%, transparent 70%);
            filter: blur(40px);
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

        /* ---------- TOOLKIT TABS ---------- */
        .toolkit-tabs {
            display: flex; flex-wrap: wrap; gap: 4px; margin-top: 48px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 0;
        }
        .toolkit-tab {
            padding: 14px 24px; font-size: 14px; font-weight: 600;
            color: var(--text-muted); cursor: pointer; border: none; background: none;
            border-bottom: 3px solid transparent; margin-bottom: -2px;
            transition: all 0.2s; white-space: nowrap;
        }
        .toolkit-tab:hover { color: var(--text); }
        .toolkit-tab.active { color: var(--text); }
        .toolkit-tab.active.tab-amber { border-bottom-color: #f59e0b; }
        .toolkit-tab.active.tab-purple { border-bottom-color: #8b5cf6; }
        .toolkit-tab.active.tab-emerald { border-bottom-color: #10b981; }
        .toolkit-tab.active.tab-blue { border-bottom-color: #3b82f6; }
        .toolkit-tab.active.tab-rose { border-bottom-color: #f43f5e; }
        .toolkit-tab.active.tab-cyan { border-bottom-color: #06b6d4; }

        .toolkit-panel {
            display: none; grid-template-columns: 1fr 1fr; gap: 64px;
            align-items: center; padding-top: 56px;
        }
        .toolkit-panel.active { display: grid; }
        .toolkit-info {}
        .toolkit-badge {
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
        .toolkit-headline {
            font-size: clamp(24px, 3vw, 34px); font-weight: 800;
            letter-spacing: -0.03em; line-height: 1.15; margin-bottom: 20px;
        }
        .toolkit-bullets { list-style: none; margin-bottom: 28px; }
        .toolkit-bullets li {
            display: flex; align-items: flex-start; gap: 12px;
            font-size: 16px; color: var(--text-md); padding: 8px 0; line-height: 1.5;
        }
        .toolkit-bullets li svg { width: 20px; height: 20px; color: #10b981; flex-shrink: 0; margin-top: 2px; }
        .toolkit-screenshot {
            position: relative; border-radius: 16px; overflow: hidden;
            box-shadow: 0 24px 64px rgba(0,0,0,0.1), 0 4px 16px rgba(0,0,0,0.06);
            border: 1px solid var(--border); background: var(--white);
        }
        .toolkit-screenshot .screen-bar {
            height: 36px; background: #f9fafb; display: flex; align-items: center;
            gap: 6px; padding: 0 14px; border-bottom: 1px solid var(--border);
        }
        .screen-dot { width: 10px; height: 10px; border-radius: 50%; }
        .screen-dot.r { background: #fca5a5; }
        .screen-dot.y { background: #fcd34d; }
        .screen-dot.g { background: #86efac; }
        .toolkit-screenshot img { display: block; width: 100%; }

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
            display: grid; grid-template-columns: repeat(4, 1fr);
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
            .toolkit-panel { grid-template-columns: 1fr; gap: 40px; }
            .toolkit-panel .toolkit-screenshot { order: -1; }
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
            .toolkit-tabs { overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
            .hero-stats { gap: 32px; }
            .hero h1 { font-size: clamp(32px, 7vw, 48px); }
        }
        @media (max-width: 640px) {
            .pricing-grid { grid-template-columns: 1fr; max-width: 400px; margin-inline: auto; }
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
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            Potenziato da Claude AI
        </div>

        <h1 class="reveal reveal-d1">
            Automatizza il tuo<br><span class="accent">marketing digitale</span><br>con l'AI
        </h1>

        <p class="hero-sub reveal reveal-d2">
            Dai la keyword, Ainstein fa il resto. Ricerca, analisi, contenuti e pubblicazione &mdash; processi che richiedono giorni, completati in minuti.
        </p>

        <div class="hero-ctas reveal reveal-d3">
            <a href="<?= url('/register') ?>" class="btn-primary">
                Prova gratis
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="#come-funziona" class="btn-secondary">Scopri come funziona</a>
        </div>

        <div class="hero-screenshot reveal reveal-d4">
            <div class="glow"></div>
            <img src="<?= url('/assets/images/screenshots/output-dashboard.png') ?>" alt="Ainstein Dashboard - Panoramica dei moduli AI">
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

<!-- ====== COME FUNZIONA ====== -->
<section class="section-pad how-section" id="come-funziona">
    <div class="section-inner text-center">
        <div class="section-label reveal">Come funziona</div>
        <h2 class="section-title reveal reveal-d1">Tre passi. Risultati reali.</h2>
        <p class="section-desc mx-auto reveal reveal-d2">Ainstein non e l'ennesimo tool AI generico. E un sistema che replica il lavoro di un team di specialisti SEO.</p>

        <div class="how-grid">
            <div class="how-card reveal">
                <div class="how-num">01</div>
                <div class="how-icon amber-bg">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h3>Configura</h3>
                <p>Scegli il modulo, inserisci la keyword o l'URL del tuo sito, imposta le preferenze. Due minuti e sei pronto.</p>
            </div>

            <div class="how-card reveal reveal-d1">
                <div class="how-num">02</div>
                <div class="how-icon purple-bg">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
                <h3>L'AI lavora</h3>
                <p>Claude AI analizza i competitor, studia la SERP di Google, genera contenuti ottimizzati e strategici.</p>
            </div>

            <div class="how-card reveal reveal-d2">
                <div class="how-num">03</div>
                <div class="how-icon emerald-bg">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
                <h3>Pubblica</h3>
                <p>Articoli su WordPress, report SEO, campagne Google Ads &mdash; output pronti all'uso, non bozze da rifare.</p>
            </div>
        </div>
    </div>
</section>

<!-- ====== TOOLKIT MODULES ====== -->
<section class="section-pad" id="funzionalita">
    <div class="section-inner">
        <div class="section-label reveal">I moduli</div>
        <h2 class="section-title reveal reveal-d1">Cosa automatizza Ainstein</h2>
        <p class="section-desc reveal reveal-d2">Sei strumenti integrati. Un'unica piattaforma. Zero copia-incolla tra tool diversi.</p>

        <div class="toolkit-tabs reveal reveal-d3" id="toolkitTabs">
            <button class="toolkit-tab tab-amber active" data-panel="tab-ai-content">Contenuti AI</button>
            <button class="toolkit-tab tab-purple" data-panel="tab-keyword-research">Keyword Research</button>
            <button class="toolkit-tab tab-emerald" data-panel="tab-seo-audit">SEO Audit</button>
            <button class="toolkit-tab tab-blue" data-panel="tab-seo-tracking">Position Tracking</button>
            <button class="toolkit-tab tab-rose" data-panel="tab-ads-analyzer">Google Ads</button>
            <button class="toolkit-tab tab-cyan" data-panel="tab-content-creator">Content Creator</button>
        </div>

        <!-- AI Content -->
        <div class="toolkit-panel active" id="tab-ai-content">
            <div class="toolkit-info">
                <div class="toolkit-badge badge-amber">AI Content Generator</div>
                <h3 class="toolkit-headline">Da keyword ad articolo pubblicato in 10 minuti</h3>
                <ul class="toolkit-bullets">
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Analisi SERP automatica dei top 10 risultati Google
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Brief AI strategico con struttura e parole chiave
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Pubblicazione WordPress automatica in 1 click
                    </li>
                </ul>
                <a href="<?= url('/register') ?>" class="btn-primary" style="display:inline-flex;">Prova gratis</a>
            </div>
            <div class="toolkit-screenshot">
                <div class="screen-bar"><span class="screen-dot r"></span><span class="screen-dot y"></span><span class="screen-dot g"></span></div>
                <img src="<?= url('/assets/images/screenshots/ai-content.png') ?>" alt="AI Content Generator" loading="lazy">
            </div>
        </div>

        <!-- Keyword Research -->
        <div class="toolkit-panel" id="tab-keyword-research">
            <div class="toolkit-info">
                <div class="toolkit-badge badge-purple">AI Keyword Research</div>
                <h3 class="toolkit-headline">120+ keyword clusterizzate in 2 minuti</h3>
                <ul class="toolkit-bullets">
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Espansione AI a partire da 2-3 keyword seed
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Clustering automatico per intento di ricerca
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Piano editoriale completo pronto per la produzione
                    </li>
                </ul>
                <a href="<?= url('/register') ?>" class="btn-primary" style="display:inline-flex;">Prova gratis</a>
            </div>
            <div class="toolkit-screenshot">
                <div class="screen-bar"><span class="screen-dot r"></span><span class="screen-dot y"></span><span class="screen-dot g"></span></div>
                <img src="<?= url('/assets/images/screenshots/keyword-research.png') ?>" alt="Keyword Research" loading="lazy">
            </div>
        </div>

        <!-- SEO Audit -->
        <div class="toolkit-panel" id="tab-seo-audit">
            <div class="toolkit-info">
                <div class="toolkit-badge badge-emerald">SEO Audit</div>
                <h3 class="toolkit-headline">Audit completo + action plan AI in 5 minuti</h3>
                <ul class="toolkit-bullets">
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Spider automatico che analizza ogni pagina del sito
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Scoring problemi con priorita per impatto SEO
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Piano d'azione AI con fix prioritizzate e concrete
                    </li>
                </ul>
                <a href="<?= url('/register') ?>" class="btn-primary" style="display:inline-flex;">Prova gratis</a>
            </div>
            <div class="toolkit-screenshot">
                <div class="screen-bar"><span class="screen-dot r"></span><span class="screen-dot y"></span><span class="screen-dot g"></span></div>
                <img src="<?= url('/assets/images/screenshots/seo-audit.png') ?>" alt="SEO Audit" loading="lazy">
            </div>
        </div>

        <!-- SEO Tracking -->
        <div class="toolkit-panel" id="tab-seo-tracking">
            <div class="toolkit-info">
                <div class="toolkit-badge badge-blue">Position Tracking</div>
                <h3 class="toolkit-headline">Monitora posizioni e ricevi report AI automatici</h3>
                <ul class="toolkit-bullets">
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Check posizioni giornaliero su Google
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Alert automatici per variazioni significative
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Report settimanale AI con insight e opportunita
                    </li>
                </ul>
                <a href="<?= url('/register') ?>" class="btn-primary" style="display:inline-flex;">Prova gratis</a>
            </div>
            <div class="toolkit-screenshot">
                <div class="screen-bar"><span class="screen-dot r"></span><span class="screen-dot y"></span><span class="screen-dot g"></span></div>
                <img src="<?= url('/assets/images/screenshots/seo-tracking.png') ?>" alt="SEO Tracking" loading="lazy">
            </div>
        </div>

        <!-- Ads Analyzer -->
        <div class="toolkit-panel" id="tab-ads-analyzer">
            <div class="toolkit-info">
                <div class="toolkit-badge badge-rose">Google Ads Analyzer</div>
                <h3 class="toolkit-headline">Campagne Google Ads complete, generate dall'AI</h3>
                <ul class="toolkit-bullets">
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Analisi approfondita dei competitor e della landing page
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Keyword negative e struttura gruppi di annunci
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Campagna completa pronta per Google Ads Editor
                    </li>
                </ul>
                <a href="<?= url('/register') ?>" class="btn-primary" style="display:inline-flex;">Prova gratis</a>
            </div>
            <div class="toolkit-screenshot">
                <div class="screen-bar"><span class="screen-dot r"></span><span class="screen-dot y"></span><span class="screen-dot g"></span></div>
                <img src="<?= url('/assets/images/screenshots/ads-analyzer.png') ?>" alt="Google Ads Analyzer" loading="lazy">
            </div>
        </div>

        <!-- Content Creator -->
        <div class="toolkit-panel" id="tab-content-creator">
            <div class="toolkit-info">
                <div class="toolkit-badge badge-cyan">Content Creator</div>
                <h3 class="toolkit-headline">Contenuti HTML per qualsiasi CMS, anche in bulk</h3>
                <ul class="toolkit-bullets">
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        4 connectors CMS: WordPress, Shopify, PrestaShop, Magento
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Template personalizzabili per ogni tipo di pagina
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Generazione in bulk da lista keyword o piano editoriale
                    </li>
                </ul>
                <a href="<?= url('/register') ?>" class="btn-primary" style="display:inline-flex;">Prova gratis</a>
            </div>
            <div class="toolkit-screenshot">
                <div class="screen-bar"><span class="screen-dot r"></span><span class="screen-dot y"></span><span class="screen-dot g"></span></div>
                <img src="<?= url('/assets/images/screenshots/dashboard.png') ?>" alt="Content Creator" loading="lazy">
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
            <div class="pain-row">
                <div class="pain-cell">
                    <svg class="pain-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Creare campagne Ads richiede esperienza
                </div>
                <div class="pain-arrow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></div>
                <div class="pain-cell">
                    <svg class="pain-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    AI analizza competitor e genera campagna completa
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

        <div class="text-center reveal">
            <a href="<?= url('/pricing') ?>" class="pricing-more">
                Confronta tutti i dettagli
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </div>
</section>

<!-- ====== CTA FINALE ====== -->
<section class="section-pad cta-final">
    <h2 class="reveal">Inizia ad automatizzare il tuo marketing</h2>
    <p class="reveal reveal-d1">Prova gratuita di 7 giorni. Nessuna carta di credito richiesta.</p>
    <div class="reveal reveal-d2">
        <a href="<?= url('/register') ?>" class="btn-cta-large">
            Inizia gratis
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

// Toolkit tabs
document.querySelectorAll('.toolkit-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.toolkit-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.toolkit-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(tab.dataset.panel).classList.add('active');
    });
});

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
