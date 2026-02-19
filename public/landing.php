<?php
// Query pricing plans from DB
$plans = \Core\Database::fetchAll("SELECT * FROM plans WHERE is_active = 1 ORDER BY price_monthly");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ainstein - Il tuo team SEO e Google Ads, powered by AI</title>
    <meta name="description" content="Ainstein fa quello che normalmente richiederebbe un team di specialisti SEO e Google Ads. Articoli, keyword, audit, tracking e campagne. Tutto con l'AI.">
    <meta property="og:title" content="Ainstein - Il tuo team SEO e Google Ads, powered by AI">
    <meta property="og:description" content="Dalla ricerca keyword alla pubblicazione automatica su WordPress. Una piattaforma AI che lavora per te.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ainstein.it">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= url('/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= url('/favicon.svg') ?>">
    <meta name="theme-color" content="#000000">

    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --white: #f5f5f7;
            --gray: #86868b;
            --gray-dark: #6e6e73;
            --bg: #000000;
            --bg-alt: #101010;
            --bg-card: #1d1d1f;
            --border: rgba(255,255,255,0.08);
            --accent: #006e96;
            --accent-light: #2997ff;
        }

        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--white);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        /* ====== NAVBAR ====== */
        .nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            height: 48px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(0,0,0,0.72);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .nav-inner {
            max-width: 1024px; width: 100%;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 22px;
        }
        .nav-logo img { height: 22px; filter: brightness(0) invert(1); opacity: 0.9; }
        .nav-links { display: flex; align-items: center; gap: 1.75rem; }
        .nav-links a {
            font-size: 12px; font-weight: 400; color: var(--white);
            text-decoration: none; opacity: 0.8; transition: opacity 0.2s;
        }
        .nav-links a:hover { opacity: 1; }
        .nav-cta {
            padding: 5px 14px; border-radius: 980px;
            background: var(--accent-light); color: #fff !important; opacity: 1 !important;
            font-size: 12px; font-weight: 500;
        }
        .nav-cta:hover { background: #0077ED; }
        .nav-hamburger { display: none; background: none; border: none; color: white; cursor: pointer; }
        .mobile-menu {
            display: none; position: fixed; top: 48px; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.97); backdrop-filter: blur(20px);
            flex-direction: column; align-items: center; justify-content: center; gap: 2rem; z-index: 99;
        }
        .mobile-menu.open { display: flex; }
        .mobile-menu a { font-size: 1.1rem; color: var(--white); text-decoration: none; opacity: 0.8; }

        /* ====== HERO ====== */
        .hero {
            min-height: 100vh; display: flex; flex-direction: column;
            align-items: center; justify-content: center; text-align: center;
            padding: 140px 24px 80px;
            background: radial-gradient(ellipse at 50% 0%, rgba(0,110,150,0.12) 0%, transparent 50%);
        }
        .hero-eyebrow {
            font-size: 17px; font-weight: 600; color: var(--accent-light);
            margin-bottom: 16px; letter-spacing: -0.01em;
        }
        .hero h1 {
            font-size: clamp(48px, 8vw, 80px); font-weight: 700;
            letter-spacing: -0.04em; line-height: 1.05;
            max-width: 780px; margin-bottom: 20px;
            background: linear-gradient(180deg, #fff 0%, rgba(255,255,255,0.7) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero-sub {
            font-size: clamp(17px, 2.2vw, 21px); font-weight: 400;
            color: var(--gray); line-height: 1.5;
            max-width: 520px; margin-bottom: 36px;
        }
        .hero-actions { display: flex; gap: 16px; flex-wrap: wrap; justify-content: center; margin-bottom: 16px; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 12px 28px; border-radius: 980px;
            background: var(--accent-light); color: #fff;
            font-size: 17px; font-weight: 500; text-decoration: none;
            transition: all 0.3s; border: none; cursor: pointer;
        }
        .btn-primary:hover { background: #0077ED; transform: scale(1.02); }
        .btn-secondary {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 12px 28px; border-radius: 980px;
            background: transparent; color: var(--accent-light);
            font-size: 17px; font-weight: 500; text-decoration: none;
            transition: all 0.3s; border: none; cursor: pointer;
        }
        .btn-secondary:hover { text-decoration: underline; }
        .hero-note { font-size: 14px; color: var(--gray-dark); }

        /* Hero product preview */
        .hero-preview {
            margin-top: 72px; max-width: 1040px; width: 100%;
            perspective: 1200px;
        }
        .hero-preview .browser-frame {
            transform: rotateX(2deg);
            box-shadow: 0 40px 120px rgba(0,110,150,0.15), 0 0 0 1px rgba(255,255,255,0.06);
        }

        /* ====== BROWSER FRAME ====== */
        .browser-frame {
            border-radius: 12px; overflow: hidden;
            background: #1a1a1a; border: 1px solid rgba(255,255,255,0.08);
        }
        .browser-chrome {
            display: flex; align-items: center; gap: 8px;
            padding: 12px 16px;
            background: #2a2a2a; border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .browser-dot {
            width: 12px; height: 12px; border-radius: 50%;
        }
        .browser-dot.red { background: #ff5f57; }
        .browser-dot.yellow { background: #febc2e; }
        .browser-dot.green { background: #28c840; }
        .browser-url {
            flex: 1; margin-left: 12px;
            padding: 5px 12px; border-radius: 6px;
            background: rgba(255,255,255,0.06);
            font-size: 12px; color: var(--gray); font-weight: 400;
        }
        .browser-body {
            position: relative; overflow: hidden;
            aspect-ratio: 16/9.5;
            background: #111;
        }
        .browser-body img {
            width: 100%; height: 100%; object-fit: cover; object-position: top;
            display: block;
        }
        /* Placeholder when image doesn't load */
        .browser-body.placeholder {
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #0d1117 0%, #161b22 50%, #0d1117 100%);
        }
        .browser-body.placeholder::after {
            content: ''; width: 80%; height: 80%; border-radius: 8px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.03) 0%, transparent 40%),
                repeating-linear-gradient(0deg, transparent 0px, transparent 40px, rgba(255,255,255,0.02) 40px, rgba(255,255,255,0.02) 41px),
                repeating-linear-gradient(90deg, transparent 0px, transparent 200px, rgba(255,255,255,0.015) 200px, rgba(255,255,255,0.015) 201px);
        }

        /* ====== SECTIONS ====== */
        .section {
            padding: 140px 24px;
            text-align: center;
        }
        .section-alt {
            background: var(--bg-alt);
        }
        .section-inner {
            max-width: 1040px; margin: 0 auto;
        }
        .section-eyebrow {
            font-size: 15px; font-weight: 600; color: var(--accent-light);
            margin-bottom: 12px; letter-spacing: -0.01em;
        }
        .section h2 {
            font-size: clamp(36px, 5.5vw, 56px); font-weight: 700;
            letter-spacing: -0.04em; line-height: 1.07;
            margin-bottom: 16px;
        }
        .section-desc {
            font-size: clamp(17px, 2vw, 19px); color: var(--gray);
            line-height: 1.5; max-width: 600px; margin: 0 auto 56px;
        }

        /* Module feature section */
        .feature-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 20px; text-align: left; margin-top: 24px;
        }
        .feature-card {
            padding: 32px; border-radius: 20px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
        }
        .feature-card h4 {
            font-size: 17px; font-weight: 600; margin-bottom: 8px;
        }
        .feature-card p {
            font-size: 14px; color: var(--gray); line-height: 1.6;
        }

        /* Screenshot section layout */
        .screenshot-section {
            margin-top: 56px;
        }

        /* ====== MODULE SHOWCASE ====== */
        .module-showcase {
            padding: 0 24px;
        }
        .module-showcase-inner {
            max-width: 1040px; margin: 0 auto;
            padding: 140px 0;
            border-bottom: 1px solid var(--border);
        }
        .module-showcase-inner:last-child { border-bottom: none; }

        .module-layout {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 64px; align-items: center;
        }
        .module-layout.reverse .module-content { order: 2; }
        .module-layout.reverse .module-visual { order: 1; }

        .module-content { text-align: left; }
        .module-content .eyebrow {
            font-size: 13px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.06em; color: var(--accent-light); margin-bottom: 16px;
        }
        .module-content h3 {
            font-size: clamp(28px, 3.5vw, 40px); font-weight: 700;
            letter-spacing: -0.03em; line-height: 1.1; margin-bottom: 20px;
        }
        .module-content p {
            font-size: 17px; color: var(--gray); line-height: 1.65; margin-bottom: 20px;
        }
        .module-content .highlight {
            font-size: 17px; font-weight: 500; color: var(--white);
            padding-top: 16px; border-top: 1px solid var(--border);
        }
        .module-visual .browser-frame {
            box-shadow: 0 24px 80px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.04);
        }

        /* ====== PIPELINE ====== */
        .pipeline {
            padding: 140px 24px; text-align: center;
            background: linear-gradient(180deg, var(--bg) 0%, rgba(0,110,150,0.04) 50%, var(--bg) 100%);
        }
        .pipeline-inner { max-width: 860px; margin: 0 auto; }
        .pipeline-steps {
            display: grid; grid-template-columns: 1fr auto 1fr auto 1fr;
            gap: 0; align-items: center;
            margin: 56px 0 40px; padding: 0 20px;
        }
        .pipeline-step { text-align: center; }
        .pipeline-icon {
            width: 64px; height: 64px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .pipeline-icon svg { width: 28px; height: 28px; color: var(--accent-light); }
        .pipeline-step h4 { font-size: 17px; font-weight: 600; margin-bottom: 6px; }
        .pipeline-step p { font-size: 14px; color: var(--gray); }
        .pipeline-connector {
            width: 48px; height: 1px; background: var(--border);
            position: relative;
        }
        .pipeline-connector::after {
            content: ''; position: absolute; right: -3px; top: -3px;
            width: 7px; height: 7px; border-right: 1.5px solid rgba(255,255,255,0.2);
            border-top: 1.5px solid rgba(255,255,255,0.2);
            transform: rotate(45deg);
        }
        .pipeline-note {
            font-size: 19px; color: var(--gray); line-height: 1.5;
        }
        .pipeline-note strong { color: var(--white); }

        /* ====== PRICING ====== */
        .pricing { padding: 140px 24px; text-align: center; }
        .pricing-inner { max-width: 1040px; margin: 0 auto; }
        .pricing-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 16px; margin: 56px 0 32px;
        }
        .pricing-card {
            background: var(--bg-card); border-radius: 20px;
            border: 1px solid var(--border);
            padding: 40px 28px; text-align: center;
            position: relative; transition: transform 0.3s;
        }
        .pricing-card:hover { transform: translateY(-4px); }
        .pricing-card.featured {
            border-color: var(--accent-light);
            background: linear-gradient(180deg, rgba(41,151,255,0.06) 0%, var(--bg-card) 40%);
        }
        .pricing-card.featured::before {
            content: 'Consigliato'; position: absolute; top: -13px; left: 50%; transform: translateX(-50%);
            padding: 5px 14px; border-radius: 980px;
            background: var(--accent-light); color: #fff;
            font-size: 12px; font-weight: 600;
        }
        .pricing-name { font-size: 19px; font-weight: 600; margin-bottom: 8px; }
        .pricing-price { margin-bottom: 4px; }
        .pricing-price span { font-size: 48px; font-weight: 700; letter-spacing: -0.04em; }
        .pricing-price .period { font-size: 17px; color: var(--gray); font-weight: 400; }
        .pricing-yearly { font-size: 14px; color: var(--gray-dark); margin-bottom: 24px; }
        .pricing-credits {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 980px;
            background: rgba(41,151,255,0.08); font-size: 14px;
            font-weight: 600; color: var(--accent-light); margin-bottom: 28px;
        }
        .pricing-credits svg { width: 16px; height: 16px; }
        .pricing-features { list-style: none; text-align: left; margin-bottom: 28px; }
        .pricing-features li {
            font-size: 14px; color: var(--gray);
            padding: 8px 0; display: flex; align-items: center; gap: 10px;
        }
        .pricing-features li svg { width: 16px; height: 16px; color: #30d158; flex-shrink: 0; }
        .pricing-footer-note { font-size: 14px; color: var(--gray-dark); max-width: 520px; margin: 0 auto; line-height: 1.6; }
        .pricing-footer-note a { color: var(--accent-light); text-decoration: none; }
        .pricing-footer-note a:hover { text-decoration: underline; }

        /* ====== CTA ====== */
        .cta {
            padding: 140px 24px; text-align: center;
        }
        .cta h2 {
            font-size: clamp(36px, 5vw, 56px); font-weight: 700;
            letter-spacing: -0.04em; margin-bottom: 12px;
        }
        .cta p { font-size: 19px; color: var(--gray); margin-bottom: 32px; }
        .btn-large {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 16px 36px; border-radius: 980px;
            background: var(--accent-light); color: #fff;
            font-size: 19px; font-weight: 600; text-decoration: none;
            transition: all 0.3s;
        }
        .btn-large:hover { background: #0077ED; transform: scale(1.02); }
        .btn-large svg { width: 20px; height: 20px; }

        /* ====== FOOTER ====== */
        .footer {
            padding: 20px 24px; text-align: center;
            border-top: 1px solid var(--border);
        }
        .footer-links { display: flex; justify-content: center; gap: 24px; margin-bottom: 12px; flex-wrap: wrap; }
        .footer-links a { font-size: 12px; color: var(--gray); text-decoration: none; }
        .footer-links a:hover { color: var(--white); }
        .footer-copy { font-size: 12px; color: var(--gray-dark); }

        /* ====== SCROLL REVEAL ====== */
        .reveal {
            opacity: 0; transform: translateY(30px);
            transition: opacity 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .reveal.visible { opacity: 1; transform: translateY(0); }
        .reveal-d1 { transition-delay: 0.1s; }
        .reveal-d2 { transition-delay: 0.2s; }

        /* ====== RESPONSIVE ====== */
        @media (max-width: 1024px) {
            .module-layout { grid-template-columns: 1fr; gap: 40px; }
            .module-layout.reverse .module-content { order: 1; }
            .module-layout.reverse .module-visual { order: 2; }
            .module-content { text-align: center; }
            .pricing-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .nav-hamburger { display: block; }
            .section { padding: 100px 20px; }
            .module-showcase-inner { padding: 100px 0; }
            .feature-grid { grid-template-columns: 1fr; }
            .pipeline-steps { grid-template-columns: 1fr; gap: 24px; }
            .pipeline-connector { width: 1px; height: 32px; margin: 0 auto; }
            .pipeline-connector::after {
                right: -3px; top: auto; bottom: -3px;
                transform: rotate(135deg);
            }
            .hero-preview { margin-top: 48px; }
        }
        @media (max-width: 480px) {
            .hero { padding: 100px 20px 60px; }
            .hero-actions { flex-direction: column; align-items: center; }
            .pricing-grid { grid-template-columns: 1fr; }
            .hero-preview .browser-frame { transform: none; }
        }
    </style>
</head>
<body>

<!-- ====== NAVBAR ====== -->
<nav class="nav">
    <div class="nav-inner">
        <a href="<?= url('/') ?>" class="nav-logo">
            <img src="<?= url('/assets/images/logo-ainstein-orizzontal.png') ?>" alt="Ainstein">
        </a>
        <div class="nav-links">
            <a href="#funzionalita">Funzionalita</a>
            <a href="#prezzi">Prezzi</a>
            <a href="<?= url('/docs') ?>">Docs</a>
            <a href="<?= url('/login') ?>">Accedi</a>
            <a href="<?= url('/register') ?>" class="nav-cta">Prova Gratis</a>
        </div>
        <button class="nav-hamburger" onclick="document.getElementById('mobileMenu').classList.toggle('open')" aria-label="Menu">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        </button>
    </div>
</nav>
<div class="mobile-menu" id="mobileMenu">
    <a href="#funzionalita" onclick="document.getElementById('mobileMenu').classList.remove('open')">Funzionalita</a>
    <a href="#prezzi" onclick="document.getElementById('mobileMenu').classList.remove('open')">Prezzi</a>
    <a href="<?= url('/docs') ?>">Docs</a>
    <a href="<?= url('/login') ?>">Accedi</a>
    <a href="<?= url('/register') ?>" class="nav-cta" style="margin-top:0.5rem">Prova Gratis</a>
</div>

<!-- ====== HERO ====== -->
<section class="hero">
    <p class="hero-eyebrow reveal">Beta aperta &mdash; 30 crediti gratuiti</p>
    <h1 class="reveal reveal-d1">Il tuo team SEO e Google Ads.</h1>
    <p class="hero-sub reveal reveal-d2">
        Ainstein fa quello che normalmente richiederebbe un intero team di specialisti. Tu dici cosa vuoi. Lui lo fa.
    </p>
    <div class="hero-actions reveal reveal-d2">
        <a href="<?= url('/register') ?>" class="btn-primary">
            Inizia Gratis
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
        <a href="#funzionalita" class="btn-secondary">Scopri di piu</a>
    </div>
    <p class="hero-note reveal reveal-d2">Nessuna carta di credito richiesta.</p>

    <div class="hero-preview reveal">
        <div class="browser-frame">
            <div class="browser-chrome">
                <span class="browser-dot red"></span>
                <span class="browser-dot yellow"></span>
                <span class="browser-dot green"></span>
                <span class="browser-url">ainstein.it/dashboard</span>
            </div>
            <div class="browser-body" id="heroScreenshot">
                <img src="<?= url('/assets/images/screenshots/dashboard.png') ?>"
                     alt="Ainstein Dashboard"
                     onerror="this.style.display='none'; this.parentElement.classList.add('placeholder')">
            </div>
        </div>
    </div>
</section>

<!-- ====== FEATURES INTRO ====== -->
<section class="section section-alt" id="funzionalita">
    <div class="section-inner">
        <p class="section-eyebrow reveal">Cinque strumenti, un unico obiettivo</p>
        <h2 class="reveal reveal-d1">L'esperienza di un<br>professionista. In automatico.</h2>
        <p class="section-desc reveal reveal-d2">Ogni strumento segue lo stesso processo che un esperto seguirebbe a mano. Ainstein analizza, studia, pianifica e poi agisce.</p>
    </div>
</section>

<!-- ====== MODULE 1: AI Content ====== -->
<div class="module-showcase">
    <div class="module-showcase-inner">
        <div class="module-layout reveal">
            <div class="module-content">
                <p class="eyebrow">AI Content Generator</p>
                <h3>Un blog professionale. Senza saper scrivere.</h3>
                <p>Dai la keyword. Ainstein studia i migliori risultati su Google, crea un brief strategico, scrive un articolo piu completo dei competitor. E lo pubblica sul tuo WordPress.</p>
                <p class="highlight">Lo stesso processo che un SEO copywriter farebbe a mano. Ma in automatico.</p>
            </div>
            <div class="module-visual">
                <div class="browser-frame">
                    <div class="browser-chrome">
                        <span class="browser-dot red"></span>
                        <span class="browser-dot yellow"></span>
                        <span class="browser-dot green"></span>
                        <span class="browser-url">ainstein.it/ai-content</span>
                    </div>
                    <div class="browser-body" id="screenshotAiContent">
                        <img src="<?= url('/assets/images/screenshots/ai-content.png') ?>"
                             alt="AI Content Generator"
                             onerror="this.style.display='none'; this.parentElement.classList.add('placeholder')">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODULE 2: Google Ads ====== -->
<div class="module-showcase" style="background: var(--bg-alt)">
    <div class="module-showcase-inner">
        <div class="module-layout reverse reveal">
            <div class="module-content">
                <p class="eyebrow">Google Ads Tools</p>
                <h3>Le tue campagne. Analizzate e create dall'AI.</h3>
                <p>Carica i tuoi dati e scopri dove stai sprecando budget. Oppure crea una campagna completa da zero: keyword, annunci, budget. Un file pronto per Google Ads Editor.</p>
                <p class="highlight">Lo stesso processo che un Google Ads specialist seguirebbe.</p>
            </div>
            <div class="module-visual">
                <div class="browser-frame">
                    <div class="browser-chrome">
                        <span class="browser-dot red"></span>
                        <span class="browser-dot yellow"></span>
                        <span class="browser-dot green"></span>
                        <span class="browser-url">ainstein.it/ads-analyzer</span>
                    </div>
                    <div class="browser-body" id="screenshotAds">
                        <img src="<?= url('/assets/images/screenshots/ads-analyzer.png') ?>"
                             alt="Google Ads Tools"
                             onerror="this.style.display='none'; this.parentElement.classList.add('placeholder')">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODULE 3: Keyword Research ====== -->
<div class="module-showcase">
    <div class="module-showcase-inner">
        <div class="module-layout reveal">
            <div class="module-content">
                <p class="eyebrow">AI Keyword Research</p>
                <h3>Dalla keyword al piano editoriale.</h3>
                <p>Parti dalle keyword seed. Ainstein le espande, le raggruppa per intento di ricerca, analizza i competitor e costruisce un piano editoriale completo. Con un click, invii tutto ad AI Content.</p>
                <p class="highlight">Non una lista di keyword. Una strategia editoriale.</p>
            </div>
            <div class="module-visual">
                <div class="browser-frame">
                    <div class="browser-chrome">
                        <span class="browser-dot red"></span>
                        <span class="browser-dot yellow"></span>
                        <span class="browser-dot green"></span>
                        <span class="browser-url">ainstein.it/keyword-research</span>
                    </div>
                    <div class="browser-body" id="screenshotKr">
                        <img src="<?= url('/assets/images/screenshots/keyword-research.png') ?>"
                             alt="AI Keyword Research"
                             onerror="this.style.display='none'; this.parentElement.classList.add('placeholder')">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODULE 4: SEO Audit ====== -->
<div class="module-showcase" style="background: var(--bg-alt)">
    <div class="module-showcase-inner">
        <div class="module-layout reverse reveal">
            <div class="module-content">
                <p class="eyebrow">AI SEO Audit</p>
                <h3>Audit completo. Con piano d'azione.</h3>
                <p>Inserisci l'URL. Ainstein scansiona struttura, performance, contenuti e fattori tecnici. Non segnala solo i problemi: li mette in ordine di impatto e crea un piano d'azione.</p>
                <p class="highlight">Non un report generico. Un piano pronto da eseguire.</p>
            </div>
            <div class="module-visual">
                <div class="browser-frame">
                    <div class="browser-chrome">
                        <span class="browser-dot red"></span>
                        <span class="browser-dot yellow"></span>
                        <span class="browser-dot green"></span>
                        <span class="browser-url">ainstein.it/seo-audit</span>
                    </div>
                    <div class="browser-body" id="screenshotAudit">
                        <img src="<?= url('/assets/images/screenshots/seo-audit.png') ?>"
                             alt="AI SEO Audit"
                             onerror="this.style.display='none'; this.parentElement.classList.add('placeholder')">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODULE 5: SEO Tracking ====== -->
<div class="module-showcase">
    <div class="module-showcase-inner" style="border-bottom:none">
        <div class="module-layout reveal">
            <div class="module-content">
                <p class="eyebrow">AI Position Tracking</p>
                <h3>Le tue posizioni. Con i dati reali.</h3>
                <p>Monitora le keyword su Google. Collega Search Console per vedere click reali, CTR e tendenze. Report periodici con insight e anomalie generate dall'AI.</p>
                <p class="highlight">Non solo dove sei posizionato. Sai cosa funziona davvero.</p>
            </div>
            <div class="module-visual">
                <div class="browser-frame">
                    <div class="browser-chrome">
                        <span class="browser-dot red"></span>
                        <span class="browser-dot yellow"></span>
                        <span class="browser-dot green"></span>
                        <span class="browser-url">ainstein.it/seo-tracking</span>
                    </div>
                    <div class="browser-body" id="screenshotTracking">
                        <img src="<?= url('/assets/images/screenshots/seo-tracking.png') ?>"
                             alt="AI Position Tracking"
                             onerror="this.style.display='none'; this.parentElement.classList.add('placeholder')">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== PIPELINE ====== -->
<section class="pipeline">
    <div class="pipeline-inner">
        <p class="section-eyebrow reveal">Il flusso completo</p>
        <h2 class="reveal reveal-d1" style="font-size:clamp(36px,5.5vw,56px); font-weight:700; letter-spacing:-0.04em; margin-bottom:16px;">Dalla strategia alla<br>pubblicazione.</h2>
        <p class="section-desc reveal reveal-d2" style="margin-bottom:0">Un flusso che prima richiedeva un intero team.</p>

        <div class="pipeline-steps reveal">
            <div class="pipeline-step">
                <div class="pipeline-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <h4>Scegli le keyword</h4>
                <p>Con Keyword Research o manualmente</p>
            </div>
            <div class="pipeline-connector"></div>
            <div class="pipeline-step">
                <div class="pipeline-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h4>Ainstein scrive</h4>
                <p>Articoli completi e ottimizzati</p>
            </div>
            <div class="pipeline-connector"></div>
            <div class="pipeline-step">
                <div class="pipeline-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h4>Pubblicato</h4>
                <p>Sul tuo WordPress, quando vuoi</p>
            </div>
        </div>

        <p class="pipeline-note reveal">
            Senza copia-incolla. Senza export. Senza fatica.<br>
            <strong>Dalla ricerca alla pubblicazione, tutto in un unico flusso.</strong>
        </p>
    </div>
</section>

<!-- ====== PRICING ====== -->
<section class="pricing" id="prezzi">
    <div class="pricing-inner">
        <p class="section-eyebrow reveal">Prezzi</p>
        <h2 class="reveal reveal-d1" style="font-size:clamp(36px,5.5vw,56px); font-weight:700; letter-spacing:-0.04em; margin-bottom:16px;">Semplice. Trasparente.</h2>
        <p class="section-desc reveal reveal-d2" style="margin-bottom:0">Tutti i piani includono tutti i moduli. Paghi solo in base a quanto usi.</p>

        <div class="pricing-grid reveal">
            <?php foreach ($plans as $plan): ?>
            <div class="pricing-card<?= $plan['slug'] === 'pro' ? ' featured' : '' ?>">
                <div class="pricing-name"><?= htmlspecialchars($plan['name']) ?></div>
                <div class="pricing-price">
                    <?php if ((float)$plan['price_monthly'] === 0.0): ?>
                        <span>Gratis</span>
                    <?php else: ?>
                        <span>&euro;<?= number_format($plan['price_monthly'], 0) ?></span>
                        <span class="period">/mese</span>
                    <?php endif; ?>
                </div>
                <?php if ((float)$plan['price_yearly'] > 0): ?>
                    <div class="pricing-yearly">&euro;<?= number_format($plan['price_yearly'], 0) ?>/anno (risparmi <?= round((1 - $plan['price_yearly'] / ($plan['price_monthly'] * 12)) * 100) ?>%)</div>
                <?php else: ?>
                    <div class="pricing-yearly">Per sempre</div>
                <?php endif; ?>
                <div class="pricing-credits">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <?= number_format($plan['credits_monthly']) ?> crediti/mese
                </div>
                <ul class="pricing-features">
                    <li><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Tutti i moduli inclusi</li>
                    <li><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Pubblicazione WordPress</li>
                    <li><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Google Search Console</li>
                    <li><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Aggiornamenti continui</li>
                </ul>
                <a href="<?= url('/register') ?>" class="btn-primary" style="width:100%; justify-content:center; font-size:15px; padding:10px 20px;">
                    <?= (float)$plan['price_monthly'] === 0.0 ? 'Inizia Gratis' : 'Scegli ' . htmlspecialchars($plan['name']) ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <p class="pricing-footer-note reveal">Un articolo: 10 crediti. Un audit: 10 crediti. Monitoraggio keyword: 1 credito ciascuna. <a href="<?= url('/docs/credits') ?>">Vedi tutti i costi</a></p>
    </div>
</section>

<!-- ====== CTA ====== -->
<section class="cta">
    <h2 class="reveal">Pronto a provare?</h2>
    <p class="reveal reveal-d1">30 crediti gratuiti. Nessun vincolo.</p>
    <div class="reveal reveal-d2">
        <a href="<?= url('/register') ?>" class="btn-large">
            Crea il tuo account
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
    </div>
</section>

<!-- ====== FOOTER ====== -->
<footer class="footer">
    <div class="footer-links">
        <a href="<?= url('/docs') ?>">Documentazione</a>
        <a href="<?= url('/docs/credits') ?>">Prezzi e Crediti</a>
        <a href="<?= url('/docs/faq') ?>">FAQ</a>
        <a href="<?= url('/login') ?>">Accedi</a>
        <a href="<?= url('/register') ?>">Registrati</a>
    </div>
    <p class="footer-copy">&copy; <?= date('Y') ?> Ainstein. Tutti i diritti riservati.</p>
</footer>

<script>
// Scroll reveal
const revealElements = document.querySelectorAll('.reveal');
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
    });
}, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });
revealElements.forEach(el => observer.observe(el));
</script>
</body>
</html>
