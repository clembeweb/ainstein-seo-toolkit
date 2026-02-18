<?php
// Data
$plans = \Core\Database::fetchAll("SELECT * FROM plans WHERE is_active = 1 ORDER BY price_monthly");
$currentPage = 'pricing';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prezzi - Ainstein | Piani per automatizzare il tuo marketing</title>
    <meta name="description" content="Piani trasparenti a crediti. Tutti i moduli inclusi in ogni piano. Prova gratis 7 giorni, nessuna carta richiesta.">
    <link rel="icon" type="image/svg+xml" href="<?= url('/favicon.svg') ?>">
    <meta name="theme-color" content="#f59e0b">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">

    <style>
        /* Shared design system — same as landing4.php */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --font: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --white: #ffffff; --bg: #fafafa; --bg-alt: #f4f4f5;
            --text: #18181b; --text-md: #3f3f46; --text-muted: #71717a; --text-light: #a1a1aa;
            --border: #e4e4e7; --border-light: #f4f4f5;
            --amber: #f59e0b; --amber-hover: #d97706; --amber-light: #fef3c7; --amber-dark: #92400e;
            --slate-900: #0f172a; --max-w: 1200px; --nav-h: 72px;
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font); background: var(--white); color: var(--text);
            -webkit-font-smoothing: antialiased; overflow-x: hidden; line-height: 1.6;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; }

        /* Navbar (same as landing4) */
        .site-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000; height: var(--nav-h);
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.82);
            backdrop-filter: blur(20px) saturate(180%); -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid transparent; transition: border-color 0.3s, box-shadow 0.3s;
        }
        .site-nav.scrolled { border-bottom-color: var(--border); box-shadow: 0 1px 16px rgba(0,0,0,0.04); }
        .site-nav-inner { max-width: var(--max-w); width: 100%; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; }
        .site-nav-logo img { height: 28px; display: block; }
        .site-nav-links { display: flex; align-items: center; gap: 36px; }
        .site-nav-link { font-size: 15px; font-weight: 500; color: var(--text-muted); transition: color 0.2s; }
        .site-nav-link:hover, .site-nav-link.active { color: var(--text); }
        .site-nav-actions { display: flex; align-items: center; gap: 20px; }
        .nav-cta-btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 10px 28px; border-radius: 50px;
            background: var(--amber); color: var(--amber-dark); font-size: 14px; font-weight: 700;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 3px rgba(245,158,11,0.3);
        }
        .nav-cta-btn:hover { background: var(--amber-hover); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,0.3); }
        .nav-hamburger {
            display: none; background: none; border: none; cursor: pointer;
            width: 32px; height: 24px; position: relative; padding: 0;
        }
        .nav-hamburger span { display: block; width: 100%; height: 2px; background: var(--text); position: absolute; left: 0; transition: all 0.3s; }
        .nav-hamburger span:nth-child(1) { top: 2px; }
        .nav-hamburger span:nth-child(2) { top: 11px; }
        .nav-hamburger span:nth-child(3) { top: 20px; }
        .nav-hamburger.open span:nth-child(1) { top: 11px; transform: rotate(45deg); }
        .nav-hamburger.open span:nth-child(2) { opacity: 0; }
        .nav-hamburger.open span:nth-child(3) { top: 11px; transform: rotate(-45deg); }
        .nav-mobile-menu { display: none; flex-direction: column; gap: 4px; background: var(--white); border-top: 1px solid var(--border); padding: 16px 24px 24px; box-shadow: 0 16px 48px rgba(0,0,0,0.08); }
        .nav-mobile-menu a { padding: 12px 0; font-size: 16px; font-weight: 500; color: var(--text-md); border-bottom: 1px solid var(--border-light); }
        .nav-mobile-menu hr { border: none; border-top: 1px solid var(--border); margin: 8px 0; }

        /* Sections */
        .section-pad { padding: 120px 24px; }
        .section-inner { max-width: var(--max-w); margin: 0 auto; }
        .section-title { font-size: clamp(32px, 4.5vw, 52px); font-weight: 800; letter-spacing: -0.035em; line-height: 1.08; margin-bottom: 16px; }
        .section-desc { font-size: 18px; color: var(--text-muted); line-height: 1.7; max-width: 600px; }
        .text-center { text-align: center; }
        .mx-auto { margin-left: auto; margin-right: auto; }

        /* Pricing Hero */
        .pricing-hero {
            padding: calc(var(--nav-h) + 80px) 24px 40px; text-align: center;
            background: linear-gradient(180deg, #fffbeb 0%, var(--white) 60%);
        }

        /* Toggle */
        .pricing-toggle { display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 32px; }
        .pricing-toggle-label { font-size: 15px; font-weight: 500; color: var(--text-muted); }
        .pricing-toggle-label.active { color: var(--text); font-weight: 700; }
        .pricing-switch {
            width: 52px; height: 28px; border-radius: 14px; background: var(--border);
            cursor: pointer; position: relative; transition: background 0.3s; border: none; padding: 0;
        }
        .pricing-switch.active { background: var(--amber); }
        .pricing-switch::after {
            content: ''; position: absolute; top: 3px; left: 3px;
            width: 22px; height: 22px; border-radius: 50%; background: #fff;
            transition: transform 0.3s; box-shadow: 0 1px 4px rgba(0,0,0,0.15);
        }
        .pricing-switch.active::after { transform: translateX(24px); }
        .pricing-save-badge { display: inline-flex; padding: 3px 10px; border-radius: 50px; background: #d1fae5; color: #065f46; font-size: 12px; font-weight: 700; }

        /* Cards */
        .pricing-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 48px; padding: 0 24px; max-width: var(--max-w); margin-inline: auto; }
        .pricing-card {
            background: var(--white); border-radius: 20px; border: 1px solid var(--border);
            padding: 36px 28px; text-align: left; transition: transform 0.3s, box-shadow 0.3s;
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
        .p-name { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .p-desc { font-size: 14px; color: var(--text-muted); margin-bottom: 20px; min-height: 40px; }
        .p-price { margin-bottom: 4px; }
        .p-price .amount { font-size: 48px; font-weight: 800; letter-spacing: -0.04em; }
        .p-price .period { font-size: 16px; color: var(--text-muted); }
        .p-price .old { text-decoration: line-through; color: var(--text-light); font-size: 18px; margin-right: 8px; }
        .p-yearly { font-size: 13px; color: var(--text-light); margin-bottom: 24px; height: 18px; }
        .p-credits {
            display: flex; align-items: center; gap: 8px;
            padding: 12px 16px; border-radius: 12px;
            background: var(--amber-light); font-size: 15px; font-weight: 700;
            color: var(--amber-dark); margin-bottom: 24px;
        }
        .p-credits svg { width: 18px; height: 18px; }
        .p-features { list-style: none; margin-bottom: 28px; }
        .p-features li { display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--text-md); padding: 6px 0; }
        .p-features li svg { width: 16px; height: 16px; color: #10b981; flex-shrink: 0; }
        .p-features li.disabled { color: var(--text-light); }
        .p-features li.disabled svg { color: var(--text-light); }
        .p-btn {
            display: block; text-align: center; padding: 14px 24px; border-radius: 12px;
            font-size: 15px; font-weight: 700; transition: all 0.2s; width: 100%;
        }
        .p-btn-primary { background: var(--amber); color: var(--amber-dark); box-shadow: 0 1px 3px rgba(245,158,11,0.25); }
        .p-btn-primary:hover { background: var(--amber-hover); transform: translateY(-1px); }
        .p-btn-outline { border: 2px solid var(--border); color: var(--text-md); background: transparent; }
        .p-btn-outline:hover { border-color: var(--amber); color: var(--amber-dark); }

        /* Comparison Table */
        .compare-section { background: var(--bg); }
        .compare-table { width: 100%; border-collapse: collapse; margin-top: 48px; background: var(--white); border-radius: 16px; overflow: hidden; border: 1px solid var(--border); }
        .compare-table thead th {
            padding: 16px 20px; font-size: 14px; font-weight: 700; text-align: center;
            background: #f9fafb; border-bottom: 2px solid var(--border); color: var(--text);
        }
        .compare-table thead th:first-child { text-align: left; min-width: 240px; }
        .compare-table thead th.featured { background: var(--amber-light); color: var(--amber-dark); }
        .compare-table tbody td {
            padding: 14px 20px; font-size: 14px; text-align: center;
            border-bottom: 1px solid var(--border-light); color: var(--text-md);
        }
        .compare-table tbody td:first-child { text-align: left; font-weight: 500; color: var(--text); }
        .compare-table tbody td.featured { background: #fffbeb; }
        .compare-table .category-row td {
            font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
            color: var(--text-muted); background: #f9fafb; padding: 12px 20px;
        }
        .check-icon { color: #10b981; }
        .dash-icon { color: var(--text-light); }

        /* FAQ */
        .faq-section {}
        .faq-list { max-width: 760px; margin: 48px auto 0; }
        .faq-item { border-bottom: 1px solid var(--border); }
        .faq-q {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            padding: 20px 0; font-size: 16px; font-weight: 600; color: var(--text);
            cursor: pointer; background: none; border: none; width: 100%; text-align: left;
            font-family: var(--font);
        }
        .faq-q svg { width: 20px; height: 20px; color: var(--text-muted); flex-shrink: 0; transition: transform 0.3s; }
        .faq-q.open svg { transform: rotate(180deg); }
        .faq-a { padding: 0 0 20px; font-size: 15px; color: var(--text-muted); line-height: 1.7; display: none; }
        .faq-a.open { display: block; }

        /* CTA Final */
        .cta-final {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            text-align: center; color: #fff; position: relative; overflow: hidden;
        }
        .cta-final::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(circle at 50% 80%, rgba(245,158,11,0.1) 0%, transparent 60%);
        }
        .cta-final h2 { font-size: clamp(28px, 4vw, 44px); font-weight: 800; letter-spacing: -0.035em; margin-bottom: 12px; position: relative; z-index: 1; }
        .cta-final p { font-size: 17px; color: #94a3b8; margin-bottom: 32px; position: relative; z-index: 1; }
        .cta-final a.cta-btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 16px 40px; border-radius: 50px;
            background: var(--amber); color: var(--amber-dark); font-size: 16px; font-weight: 700;
            transition: all 0.2s; position: relative; z-index: 1; box-shadow: 0 4px 16px rgba(245,158,11,0.3);
        }
        .cta-final a.cta-btn:hover { background: var(--amber-hover); transform: translateY(-2px); box-shadow: 0 12px 40px rgba(245,158,11,0.35); }

        /* Footer */
        .site-footer { background: var(--slate-900); color: #94a3b8; padding: 80px 24px 40px; }
        .footer-inner { max-width: var(--max-w); margin: 0 auto; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 48px; margin-bottom: 48px; }
        .footer-brand p { font-size: 14px; line-height: 1.7; margin-top: 16px; max-width: 280px; }
        .footer-col h4 { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #e2e8f0; margin-bottom: 16px; }
        .footer-col a { display: block; font-size: 14px; color: #94a3b8; padding: 4px 0; transition: color 0.2s; }
        .footer-col a:hover { color: #fff; }
        .footer-bottom { padding-top: 32px; border-top: 1px solid rgba(255,255,255,0.08); font-size: 13px; }

        /* Reveal */
        .reveal { opacity: 0; transform: translateY(24px); transition: opacity 0.7s cubic-bezier(0.16,1,0.3,1), transform 0.7s cubic-bezier(0.16,1,0.3,1); }
        .reveal.visible { opacity: 1; transform: none; }
        .reveal-d1 { transition-delay: 0.08s; }
        .reveal-d2 { transition-delay: 0.16s; }
        .reveal-d3 { transition-delay: 0.24s; }

        /* Responsive */
        @media (max-width: 1024px) {
            .pricing-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-grid { grid-template-columns: repeat(2, 1fr); }
            .compare-table { font-size: 13px; }
            .compare-table thead th, .compare-table tbody td { padding: 12px 12px; }
        }
        @media (max-width: 768px) {
            .site-nav-links, .site-nav-actions { display: none; }
            .nav-hamburger { display: block; }
            .nav-mobile-menu.open { display: flex; }
            .compare-table { display: block; overflow-x: auto; }
        }
        @media (max-width: 640px) {
            .pricing-grid { grid-template-columns: 1fr; max-width: 400px; margin-inline: auto; }
            .footer-grid { grid-template-columns: 1fr; gap: 32px; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/site-header.php'; ?>

<!-- ====== PRICING HERO ====== -->
<section class="pricing-hero">
    <h1 class="section-title reveal">Piani e prezzi</h1>
    <p class="section-desc mx-auto reveal reveal-d1">Scegli il piano giusto per automatizzare il tuo marketing digitale. Tutti i moduli inclusi in ogni piano.</p>

    <div class="pricing-toggle reveal reveal-d2">
        <span class="pricing-toggle-label active" id="toggleMonthly">Mensile</span>
        <button class="pricing-switch" id="pricingSwitch" aria-label="Toggle annuale"></button>
        <span class="pricing-toggle-label" id="toggleYearly">Annuale</span>
        <span class="pricing-save-badge">Risparmia 17%</span>
    </div>
</section>

<!-- ====== PRICING CARDS ====== -->
<section style="padding: 40px 0 80px;">
    <?php
    $planFeatures = [
        'free' => [
            ['text' => '30 crediti/mese', 'ok' => true],
            ['text' => 'Tutti i 7 moduli', 'ok' => true],
            ['text' => 'Quick Check gratuiti', 'ok' => true],
            ['text' => 'Documentazione', 'ok' => true],
            ['text' => 'WordPress sync', 'ok' => false],
            ['text' => 'Google Search Console', 'ok' => false],
        ],
        'starter' => [
            ['text' => '150 crediti/mese', 'ok' => true],
            ['text' => 'Tutti i 7 moduli', 'ok' => true],
            ['text' => 'WordPress sync', 'ok' => true],
            ['text' => 'Google Search Console', 'ok' => true],
            ['text' => 'Report AI settimanali', 'ok' => true],
            ['text' => 'Supporto email', 'ok' => true],
        ],
        'pro' => [
            ['text' => '500 crediti/mese', 'ok' => true],
            ['text' => 'Tutti i 7 moduli', 'ok' => true],
            ['text' => 'WordPress sync', 'ok' => true],
            ['text' => 'Google Search Console', 'ok' => true],
            ['text' => 'Report AI settimanali', 'ok' => true],
            ['text' => 'Supporto prioritario', 'ok' => true],
        ],
        'agency' => [
            ['text' => '1.500 crediti/mese', 'ok' => true],
            ['text' => 'Tutti i 7 moduli', 'ok' => true],
            ['text' => 'WordPress sync', 'ok' => true],
            ['text' => 'Google Search Console', 'ok' => true],
            ['text' => 'Report AI settimanali', 'ok' => true],
            ['text' => 'Supporto prioritario', 'ok' => true],
        ],
    ];
    ?>
    <div class="pricing-grid reveal">
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
                <li class="<?= $feat['ok'] ? '' : 'disabled' ?>">
                    <?php if ($feat['ok']): ?>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <?php else: ?>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    <?php endif; ?>
                    <?= htmlspecialchars($feat['text']) ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <a href="<?= url('/register') ?>" class="p-btn <?= $isFeatured ? 'p-btn-primary' : 'p-btn-outline' ?>">
                <?= (float)$plan['price_monthly'] === 0.0 ? 'Inizia gratis' : 'Prova gratis 7 giorni' ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ====== COMPARISON TABLE ====== -->
<section class="section-pad compare-section">
    <div class="section-inner text-center">
        <h2 class="section-title reveal">Confronta i piani nel dettaglio</h2>
        <p class="section-desc mx-auto reveal reveal-d1">Tutti i piani includono tutti i moduli. La differenza e solo nei crediti e nelle integrazioni.</p>

        <div class="reveal reveal-d2" style="overflow-x:auto; margin-top:48px;">
            <table class="compare-table">
                <thead>
                    <tr>
                        <th></th>
                        <?php foreach ($plans as $plan):
                            $isFeatured = ($plan['features'] ?? false) && isset(json_decode($plan['features'], true)['recommended']);
                        ?>
                        <th class="<?= $isFeatured ? 'featured' : '' ?>"><?= htmlspecialchars($plan['name']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- Limiti -->
                    <tr class="category-row"><td colspan="5">Limiti</td></tr>
                    <tr>
                        <td>Crediti mensili</td>
                        <?php foreach ($plans as $plan):
                            $isFeatured = ($plan['features'] ?? false) && isset(json_decode($plan['features'], true)['recommended']);
                        ?>
                        <td class="<?= $isFeatured ? 'featured' : '' ?>"><?= number_format($plan['credits_monthly']) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Progetti illimitati</td>
                        <?php foreach ($plans as $i => $plan):
                            $isFeatured = ($plan['features'] ?? false) && isset(json_decode($plan['features'], true)['recommended']);
                        ?>
                        <td class="<?= $isFeatured ? 'featured' : '' ?>">
                            <svg class="check-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Moduli -->
                    <tr class="category-row"><td colspan="5">Moduli AI</td></tr>
                    <?php
                    $moduleNames = ['AI Content Generator', 'Keyword Research', 'SEO Audit', 'Position Tracking', 'Google Ads Analyzer', 'Content Creator'];
                    foreach ($moduleNames as $mod):
                    ?>
                    <tr>
                        <td><?= $mod ?></td>
                        <?php foreach ($plans as $plan):
                            $isFeatured = ($plan['features'] ?? false) && isset(json_decode($plan['features'], true)['recommended']);
                        ?>
                        <td class="<?= $isFeatured ? 'featured' : '' ?>">
                            <svg class="check-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Integrazioni -->
                    <tr class="category-row"><td colspan="5">Integrazioni</td></tr>
                    <tr>
                        <td>WordPress sync</td>
                        <?php foreach ($plans as $i => $plan):
                            $isFeatured = ($plan['features'] ?? false) && isset(json_decode($plan['features'], true)['recommended']);
                            $available = (float)$plan['price_monthly'] > 0;
                        ?>
                        <td class="<?= $isFeatured ? 'featured' : '' ?>">
                            <?php if ($available): ?>
                            <svg class="check-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <?php else: ?>
                            <svg class="dash-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Google Search Console</td>
                        <?php foreach ($plans as $plan):
                            $isFeatured = ($plan['features'] ?? false) && isset(json_decode($plan['features'], true)['recommended']);
                            $available = (float)$plan['price_monthly'] > 0;
                        ?>
                        <td class="<?= $isFeatured ? 'featured' : '' ?>">
                            <?php if ($available): ?>
                            <svg class="check-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <?php else: ?>
                            <svg class="dash-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Cron automatici</td>
                        <?php foreach ($plans as $plan):
                            $isFeatured = ($plan['features'] ?? false) && isset(json_decode($plan['features'], true)['recommended']);
                            $available = (float)$plan['price_monthly'] > 0;
                        ?>
                        <td class="<?= $isFeatured ? 'featured' : '' ?>">
                            <?php if ($available): ?>
                            <svg class="check-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <?php else: ?>
                            <svg class="dash-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Supporto -->
                    <tr class="category-row"><td colspan="5">Supporto</td></tr>
                    <tr>
                        <td>Documentazione</td>
                        <?php foreach ($plans as $plan):
                            $isFeatured = ($plan['features'] ?? false) && isset(json_decode($plan['features'], true)['recommended']);
                        ?>
                        <td class="<?= $isFeatured ? 'featured' : '' ?>">
                            <svg class="check-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Supporto prioritario</td>
                        <?php foreach ($plans as $plan):
                            $isFeatured = ($plan['features'] ?? false) && isset(json_decode($plan['features'], true)['recommended']);
                            $available = (float)$plan['price_monthly'] >= 49;
                        ?>
                        <td class="<?= $isFeatured ? 'featured' : '' ?>">
                            <?php if ($available): ?>
                            <svg class="check-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <?php else: ?>
                            <svg class="dash-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Report AI settimanali</td>
                        <?php foreach ($plans as $plan):
                            $isFeatured = ($plan['features'] ?? false) && isset(json_decode($plan['features'], true)['recommended']);
                            $available = (float)$plan['price_monthly'] > 0;
                        ?>
                        <td class="<?= $isFeatured ? 'featured' : '' ?>">
                            <?php if ($available): ?>
                            <svg class="check-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <?php else: ?>
                            <svg class="dash-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ====== FAQ ====== -->
<section class="section-pad faq-section">
    <div class="section-inner text-center">
        <h2 class="section-title reveal">Domande frequenti</h2>
        <p class="section-desc mx-auto reveal reveal-d1">Tutto quello che devi sapere su piani, crediti e funzionamento della piattaforma.</p>

        <div class="faq-list">
            <div class="faq-item reveal">
                <button class="faq-q" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open');">
                    Come funzionano i crediti?
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="faq-a">Ogni operazione in Ainstein consuma un certo numero di crediti. Ad esempio, generare un articolo SEO costa 10 crediti, un audit SEO costa 10 crediti, monitorare una keyword costa 1 credito per check. I crediti si rinnovano ogni mese con il tuo piano.</div>
            </div>
            <div class="faq-item reveal reveal-d1">
                <button class="faq-q" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open');">
                    C'e un periodo di prova gratuito?
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="faq-a">Si. Il piano Free ti da 30 crediti al mese per sempre, senza carta di credito. Puoi provare tutti i moduli e vedere i risultati prima di scegliere un piano a pagamento.</div>
            </div>
            <div class="faq-item reveal reveal-d2">
                <button class="faq-q" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open');">
                    Posso cambiare piano in qualsiasi momento?
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="faq-a">Assolutamente si. Puoi fare upgrade o downgrade del tuo piano in qualsiasi momento. Il cambio e immediato e i crediti vengono ricalcolati proporzionalmente.</div>
            </div>
            <div class="faq-item reveal">
                <button class="faq-q" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open');">
                    Cosa succede se finisco i crediti prima della fine del mese?
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="faq-a">Puoi acquistare pacchetti di crediti aggiuntivi oppure fare upgrade al piano superiore. Le funzionalita gratuite come Quick Check continuano a funzionare anche a crediti esauriti.</div>
            </div>
            <div class="faq-item reveal reveal-d1">
                <button class="faq-q" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open');">
                    Posso cancellare in qualsiasi momento?
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="faq-a">Si, puoi cancellare il tuo abbonamento in qualsiasi momento. Non ci sono vincoli ne penali. I tuoi dati restano accessibili fino alla fine del periodo pagato.</div>
            </div>
            <div class="faq-item reveal reveal-d2">
                <button class="faq-q" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open');">
                    Quali metodi di pagamento accettate?
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="faq-a">Accettiamo tutte le principali carte di credito e debito (Visa, Mastercard, American Express) tramite Stripe. I pagamenti sono sicuri e crittografati.</div>
            </div>
        </div>
    </div>
</section>

<!-- ====== CTA FINALE ====== -->
<section class="section-pad cta-final">
    <h2 class="reveal">Hai bisogno di un piano personalizzato?</h2>
    <p class="reveal reveal-d1">Contattaci per un piano su misura per le esigenze del tuo team o della tua agenzia.</p>
    <div class="reveal reveal-d2">
        <a href="mailto:info@ainstein.it" class="cta-btn">
            Contattaci
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </a>
    </div>
</section>

<!-- ====== FOOTER ====== -->
<?php include __DIR__ . '/includes/site-footer.php'; ?>

<script>
// Navbar scroll
const nav = document.getElementById('siteNav');
window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 10), { passive: true });

// Mobile menu
const hamburger = document.getElementById('navHamburger');
const mobileMenu = document.getElementById('navMobile');
hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('open');
    mobileMenu.classList.toggle('open');
});

// Reveal
const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.reveal').forEach(el => obs.observe(el));

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
</script>
</body>
</html>
