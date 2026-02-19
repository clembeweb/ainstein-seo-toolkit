<?php
$currentPage = 'coming-soon';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ainstein — In Arrivo</title>
    <meta name="description" content="Ainstein: la piattaforma AI che automatizza il tuo marketing digitale. Ricerca keyword, contenuti SEO, audit tecnici — tutto in un unico posto. Presto disponibile.">
    <link rel="icon" type="image/svg+xml" href="<?= url('/favicon.svg') ?>">
    <meta name="theme-color" content="#f59e0b">

    <meta property="og:title" content="Ainstein — In Arrivo">
    <meta property="og:description" content="La piattaforma AI per il marketing digitale. Presto disponibile.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ainstein.it">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --font: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --white: #ffffff;
            --bg: #fafafa;
            --text: #18181b;
            --text-md: #3f3f46;
            --text-muted: #71717a;
            --text-light: #a1a1aa;
            --border: #e4e4e7;
            --amber: #f59e0b;
            --amber-hover: #d97706;
            --amber-light: #fef3c7;
            --amber-glow: rgba(245, 158, 11, 0.08);
        }

        html { font-size: 16px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* ── Decorative background ── */
        .bg-glow {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .bg-glow__orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            animation: orbDrift 25s ease-in-out infinite;
        }

        .bg-glow__orb--1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(245,158,11,0.10) 0%, transparent 70%);
            top: -10%; right: 15%;
        }

        .bg-glow__orb--2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(251,191,36,0.07) 0%, transparent 70%);
            bottom: -5%; left: 10%;
            animation-delay: -12s;
        }

        @keyframes orbDrift {
            0%, 100% { transform: translate(0, 0); }
            33% { transform: translate(30px, -20px); }
            66% { transform: translate(-20px, 15px); }
        }

        /* Subtle dot grid */
        .bg-dots {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background-image: radial-gradient(circle, var(--border) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(ellipse 50% 45% at 50% 45%, black 10%, transparent 65%);
            -webkit-mask-image: radial-gradient(ellipse 50% 45% at 50% 45%, black 10%, transparent 65%);
            opacity: 0.5;
        }

        /* ── Page layout ── */
        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* ── Content ── */
        .content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 2rem;
            gap: 2.5rem;
            max-width: 640px;
        }

        /* Logo */
        .logo {
            animation: fadeIn 0.8s ease 0.2s both;
        }

        .logo img {
            width: 300px;
            height: auto;
        }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem 0.4rem 0.65rem;
            background: var(--amber-light);
            border: 1px solid rgba(245,158,11,0.2);
            border-radius: 100px;
            font-size: 0.72rem;
            color: var(--amber-hover);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: 600;
            animation: fadeSlideUp 0.8s ease 0.4s both;
        }

        .badge__dot {
            width: 6px; height: 6px;
            background: var(--amber);
            border-radius: 50%;
            animation: pulse 2.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.35; }
        }

        /* Headline */
        .headline {
            font-size: clamp(2.25rem, 6vw, 3.75rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
            color: var(--text);
            animation: fadeSlideUp 0.8s ease 0.6s both;
        }

        .headline__accent {
            color: var(--amber);
            position: relative;
            display: inline-block;
        }

        /* Decorative arc behind accent word */
        .headline__accent::before {
            content: '';
            position: absolute;
            inset: -8px -16px;
            background: var(--amber-glow);
            border-radius: 50%;
            z-index: -1;
            filter: blur(4px);
        }

        /* Subtitle */
        .subtitle {
            font-size: clamp(0.95rem, 1.8vw, 1.125rem);
            color: var(--text-muted);
            max-width: 440px;
            line-height: 1.7;
            font-weight: 400;
            animation: fadeSlideUp 0.8s ease 0.8s both;
        }

        /* Docs link */
        .docs-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.5rem;
            border: 1.5px solid var(--amber);
            border-radius: 100px;
            font-family: var(--font);
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--amber-hover);
            text-decoration: none;
            background: transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeSlideUp 0.8s ease 1s both;
        }

        .docs-link:hover {
            background: var(--amber);
            color: var(--white);
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(245,158,11,0.2);
        }

        .docs-link svg {
            width: 14px; height: 14px;
            transition: transform 0.3s;
        }

        .docs-link:hover svg {
            transform: translateX(3px);
        }

        /* ── Footer ── */
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.25rem 2rem;
            text-align: center;
            animation: fadeIn 0.8s ease 1.2s both;
        }

        .footer__text {
            font-size: 0.7rem;
            color: var(--text-light);
            letter-spacing: 0.02em;
        }

        /* ── Animations ── */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .content { padding: 2rem 1.5rem; gap: 2rem; }
            .logo img { height: 36px; }
        }
    </style>
</head>
<body>

    <!-- Background -->
    <div class="bg-glow">
        <div class="bg-glow__orb bg-glow__orb--1"></div>
        <div class="bg-glow__orb bg-glow__orb--2"></div>
    </div>
    <div class="bg-dots"></div>

    <!-- Page -->
    <div class="page">

        <div class="content">

            <?php $logoPath = \Core\Settings::get('brand_logo_horizontal', '') ?: 'assets/images/logo-ainstein-orizzontal.png'; ?>
            <div class="logo">
                <img src="<?= url('/' . $logoPath) ?>" alt="Ainstein">
            </div>

            <div class="badge">
                <span class="badge__dot"></span>
                In arrivo
            </div>

            <h1 class="headline">
                Stiamo costruendo qualcosa di <span class="headline__accent">nuovo</span>.
            </h1>

            <p class="subtitle">
                La piattaforma AI che automatizza il tuo marketing digitale.
                Ci vediamo presto.
            </p>

            <a href="<?= url('/docs') ?>" class="docs-link">
                Scopri la documentazione
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>

        </div>

        <footer class="footer">
            <span class="footer__text">&copy; 2026 Ainstein. Tutti i diritti riservati.</span>
        </footer>

    </div>

</body>
</html>
