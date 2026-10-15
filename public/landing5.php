<?php
/**
 * Home pubblica di Ainstein: casella con il controllo AI gratuito (POST /check-ai) e racconto in tre tempi
 * (sapere dove sei → fare le cose giuste → vedere se funziona). Riscritta il 2026-10-15 con Clemente.
 * Design: docs/plans/2026-10-13-landing-semrush-design.md · stato: docs/LANCIO-LINKEDIN.md
 * Numeri e prezzi letti dal sistema; esempi inventati (nessun dato dei clienti, tranne il caso anonimo 79/2).
 */
$currentPage = 'home';
$variant = $variant ?? 'tabs';   // 'slider' su /landing-b (variante con le card che si aprono)
$bonus = \Core\Credits::signupBonus();
$plans = \Core\Database::fetchAll("SELECT name, slug, price_monthly, credits_monthly FROM plans WHERE is_active = 1 AND price_monthly > 0 ORDER BY price_monthly");
$num = fn($v) => number_format((float) $v, 0, ',', '.');
$csrf = csrf_token();
$tile = fn(string $color, string $path) => '<span class="tile" style="background:' . $color . '"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . $path . '"/></svg></span>';
$icons = [
    'geo' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    'audit' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    'kw' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    'writer' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    'rank' => 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z',
    'blog' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    'shop' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
    'local' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
    'team' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    'google' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    'chat' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
    'chart' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'doc' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'bag' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
    'pin' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
    'case' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
];
$C = ['geo' => '#6366f1', 'audit' => '#10b981', 'kw' => '#8b5cf6', 'writer' => '#f59e0b', 'rank' => '#0ea5e9'];
$ico = fn(string $path) => '<span class="ico"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . $path . '"/></svg></span>';
$tools = [
    'geo' => ['AI GEO Audit', 'Le risposte delle AI', '/docs/ai-reputation'],
    'audit' => ['AI Audit', 'Il tuo sito', '/docs/seo-audit'],
    'kw' => ['AI Keywords', 'Pagine e piano', '/docs/keyword-research'],
    'writer' => ['AI Writer', 'Articoli', '/docs/ai-content'],
    'rank' => ['AI Rank', 'Posizioni e traffico', '/docs/seo-tracking'],
];
$groups = [['Sapere dove sei', ['geo', 'audit']], ['Fare le cose giuste', ['kw', 'writer']], ['Vedere se funziona', ['rank']]];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ainstein · Fatti trovare su Google e consigliare dalle AI</title>
    <meta name="description" content="Le persone chiedono a ChatGPT e Gemini, non solo a Google. Ainstein ti dice se il tuo sito si fa trovare e consigliare, e ti prepara pagine, articoli e correzioni. In italiano, anche se non sei un esperto SEO. Controllo gratuito in un minuto.">
    <link rel="icon" type="image/svg+xml" href="<?= url('/favicon.svg') ?>">
    <meta property="og:title" content="Ainstein · Fatti trovare su Google e consigliare dalle AI">
    <meta property="og:description" content="Le AI riescono a leggere il tuo sito? Scoprilo gratis in meno di un minuto. Poi: dove sei, cosa fare, se funziona.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ainstein.it/">
    <meta name="theme-color" content="#006e96">
    <link rel="canonical" href="https://ainstein.it/">
    <?php if ($variant !== 'tabs'): ?><meta name="robots" content="noindex,nofollow"><?php endif; ?>
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => 'https://ainstein.it/#org',
                'name' => 'Ainstein',
                'legalName' => 'Beweb Agency S.r.l.s.',
                'vatID' => 'IT09334871218',
                'url' => 'https://ainstein.it/',
                'logo' => 'https://ainstein.it/assets/images/logo-ainstein-square.png',
                'email' => 'info@ainstein.it',
                'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Via Tommaso Sorrentino 26', 'postalCode' => '80054', 'addressLocality' => 'Gragnano', 'addressRegion' => 'NA', 'addressCountry' => 'IT'],
                'contactPoint' => ['@type' => 'ContactPoint', 'contactType' => 'customer support', 'email' => 'info@ainstein.it', 'availableLanguage' => 'it'],
            ],
            ['@type' => 'WebSite', '@id' => 'https://ainstein.it/#site', 'url' => 'https://ainstein.it/', 'name' => 'Ainstein', 'inLanguage' => 'it-IT', 'publisher' => ['@id' => 'https://ainstein.it/#org']],
            [
                '@type' => 'SoftwareApplication',
                'name' => 'Ainstein',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'inLanguage' => 'it-IT',
                'description' => '5 strumenti AI per farsi trovare su Google e consigliare da ChatGPT e Gemini: AI GEO Audit, AI Audit, AI Keywords, AI Writer, AI Rank.',
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR', 'description' => $bonus . ' crediti in regalo all’iscrizione'],
                'publisher' => ['@id' => 'https://ainstein.it/#org'],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--font:'DM Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;--blue:#006e96;--dark:#004d69;--accent:#00a3d9;--ink:#0f172a;--md:#334155;--muted:#64748b;--line:#e2e8f0;--soft:#f1f7fa;--soft2:#e6f3f8;--white:#fff;--text:#0f172a;--text-md:#334155;--text-muted:#64748b;--border:#e2e8f0;--border-light:#f1f5f9;--slate-900:#0f172a;--max-w:1200px;--nav-h:68px}
        html{scroll-behavior:smooth}
        body{font-family:var(--font);color:var(--ink);background:#fff;line-height:1.5;-webkit-font-smoothing:antialiased;overflow-x:hidden}
        a{color:inherit;text-decoration:none}
        img{max-width:100%;height:auto}
        .wrap{max-width:var(--max-w);margin:0 auto;padding:0 24px}
        :focus-visible{outline:3px solid var(--accent);outline-offset:2px;border-radius:6px}

        /* barra novità + nav (header condiviso, qui sticky e coi colori della home) */
        .topbar{background:var(--dark);color:#fff;font-size:14px;text-align:center;padding:9px 16px}
        .topbar b{color:#7fdcff}.topbar a:hover{text-decoration:underline}
        .site-nav{position:sticky;top:0;z-index:1000;height:var(--nav-h);display:flex;flex-direction:column;justify-content:center;background:rgba(255,255,255,.9);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid var(--line)}
        .site-nav-inner{max-width:var(--max-w);width:100%;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between}
        .site-nav-logo img{height:28px;display:block}
        .site-nav-links{display:flex;gap:32px}
        .site-nav-link{font-size:15px;font-weight:500;color:var(--md)}.site-nav-link:hover,.site-nav-link.active{color:var(--ink)}
        .site-nav-actions{display:flex;align-items:center;gap:18px}
        .nav-cta-btn{display:inline-flex;align-items:center;padding:10px 22px;border-radius:999px;background:var(--ink);color:#fff;font-size:14px;font-weight:600}
        .nav-cta-btn:hover{background:var(--dark)}
        .nav-hamburger{display:none;background:none;border:none;cursor:pointer;width:32px;height:24px;position:relative}
        .nav-hamburger span{display:block;width:100%;height:2px;background:var(--ink);position:absolute;left:0;transition:all .3s}
        .nav-hamburger span:nth-child(1){top:2px}.nav-hamburger span:nth-child(2){top:11px}.nav-hamburger span:nth-child(3){top:20px}
        .nav-hamburger.open span:nth-child(1){top:11px;transform:rotate(45deg)}.nav-hamburger.open span:nth-child(2){opacity:0}.nav-hamburger.open span:nth-child(3){top:11px;transform:rotate(-45deg)}
        .nav-mobile-menu{display:none;flex-direction:column;background:#fff;border-top:1px solid var(--line);padding:12px 24px 20px;position:absolute;top:var(--nav-h);left:0;right:0;box-shadow:0 16px 48px rgba(0,0,0,.08)}
        .nav-mobile-menu a{padding:12px 0;font-size:16px;font-weight:500;border-bottom:1px solid var(--border-light)}.nav-mobile-menu hr{border:none;border-top:1px solid var(--line);margin:8px 0}

        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:999px;padding:12px 24px;font-weight:600;font-size:15px;cursor:pointer;border:0;font-family:inherit;transition:background .2s,transform .2s}
        .btn-blue{background:var(--blue);color:#fff}.btn-blue:hover{background:var(--dark)}
        .btn-ghost{border:1px solid #cbd5e1;background:#fff;color:var(--ink)}.btn-ghost:hover{border-color:var(--ink)}
        .btn:disabled{opacity:.6;cursor:wait}
        .more{font-weight:600;color:var(--blue)}.more:hover{text-decoration:underline}

        /* hero + controllo */
        .hero{background:linear-gradient(180deg,var(--soft2) 0%,#fff 100%);padding:84px 0 72px;text-align:center}
        .hero h1{font-size:66px;line-height:1.04;letter-spacing:-2.2px;font-weight:800;max-width:960px;margin:0 auto}
        .hero h1 em{font-style:normal;color:var(--blue)}
        .hero .sub{font-size:20px;color:var(--md);max-width:760px;margin:22px auto 34px}
        .check{display:flex;gap:10px;max-width:660px;margin:0 auto;background:#fff;border:1px solid var(--line);border-radius:999px;padding:8px;box-shadow:0 10px 30px rgba(0,77,105,.10)}
        .check input[type=text]{flex:1;min-width:0;border:0;outline:0;font:inherit;font-size:17px;padding:0 18px;background:transparent}
        .check .hp{position:absolute;left:-9999px;width:1px;height:1px;opacity:0}
        .micro{font-size:14px;color:var(--muted);margin-top:14px}
        .progress{max-width:660px;margin:22px auto 0;text-align:left;display:none}
        .progress.show{display:block}
        .progress .track{height:8px;border-radius:99px;background:var(--line);overflow:hidden}
        .progress .fill{height:100%;width:0;background:linear-gradient(90deg,var(--blue),var(--accent));transition:width .6s ease}
        .progress p{font-size:14px;color:var(--muted);margin-top:10px}
        .msg{max-width:660px;margin:20px auto 0;padding:14px 18px;border-radius:14px;font-size:15px;display:none;text-align:left}
        .msg.show{display:block}.msg.err{background:#fef2f2;color:#991b1b}
        .result{max-width:780px;margin:32px auto 0;text-align:left;background:#fff;border:1px solid var(--line);border-radius:22px;padding:26px;box-shadow:0 20px 40px rgba(15,23,42,.07);display:none}
        .result.show{display:block;animation:rise .4s ease}
        @keyframes rise{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
        .score{display:flex;align-items:center;gap:18px;margin-bottom:16px}
        .ring{width:78px;height:78px;border-radius:50%;display:grid;place-items:center;flex-shrink:0}
        .ring div{width:62px;height:62px;border-radius:50%;background:#fff;display:grid;place-items:center;font-weight:800;font-size:20px}
        .score b{font-size:20px;line-height:1.25;display:block}.score span{color:var(--muted);font-size:14px}
        .row{display:flex;align-items:center;gap:12px;padding:11px 0;border-top:1px solid var(--line);font-size:15px}
        .dot{width:22px;height:22px;border-radius:50%;display:grid;place-items:center;color:#fff;font-size:13px;font-weight:700;flex-shrink:0}
        .dot.ok{background:#10b981}.dot.bad{background:#ef4444}.dot.warn{background:#f59e0b}
        .lock{margin-left:auto;font-size:13px;color:var(--muted);filter:blur(3.5px);user-select:none;white-space:nowrap}
        .gate{margin-top:16px;background:var(--soft);border-radius:16px;padding:18px;display:flex;align-items:center;justify-content:space-between;gap:16px}
        .gate span{color:var(--muted);font-size:14px}

        section{padding:96px 0}
        .eyebrow{font-size:13px;font-weight:700;letter-spacing:1.6px;color:var(--blue);text-transform:uppercase}
        h2{font-size:46px;line-height:1.08;letter-spacing:-1.4px;font-weight:800;margin-top:10px}
        .lead{font-size:18px;color:#475569;max-width:680px;margin-top:14px}
        .tile{width:30px;height:30px;border-radius:9px;display:inline-grid;place-items:center;color:#fff;flex-shrink:0}
        .tile svg{width:17px;height:17px}
        .ico{width:48px;height:48px;border-radius:50%;background:var(--soft2);color:var(--blue);display:inline-grid;place-items:center}
        .ico svg{width:24px;height:24px}
        .pro .ico{background:rgba(255,255,255,.1);color:#7fdcff}

        /* cos'è cambiato */
        .why{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:40px}
        .card{border:1px solid var(--line);border-radius:20px;padding:26px;background:#fff}
        .card h4{font-size:19px;margin:14px 0 8px}.card p{color:var(--md);font-size:15px}
        .card.fact{background:var(--dark);color:#fff;border-color:var(--dark)}.card.fact .ico{background:rgba(255,255,255,.1);color:#7fdcff}.card.fact p{color:#cfe9f3}.card.fact small{display:block;margin-top:14px;color:#7fdcff;font-weight:700;font-size:12px;letter-spacing:1.2px;text-transform:uppercase}

        /* strumenti in tre tempi */
        .groups{display:flex;gap:28px;flex-wrap:wrap;margin:40px 0 26px}
        .group small{display:block;font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:10px}
        .tabs{display:flex;gap:8px;flex-wrap:wrap}
        .tab{border:1px solid var(--line);background:#fff;border-radius:999px;padding:9px 18px 9px 9px;font-weight:600;font-size:15px;cursor:pointer;display:flex;align-items:center;gap:10px;font-family:inherit;color:var(--ink)}
        .tab[aria-selected=true]{background:var(--ink);color:#fff;border-color:var(--ink)}
        .panel{display:none;grid-template-columns:1fr 1.15fr;gap:48px;align-items:center;background:#fff;border:1px solid var(--line);border-radius:28px;padding:48px}
        .panel.on{display:grid;animation:rise .35s ease}
        .panel h3{font-size:32px;letter-spacing:-.8px;line-height:1.1;margin-top:12px}
        .panel .when{color:var(--muted);font-size:15px;margin-top:12px}
        .panel ul{list-style:none;margin:18px 0 20px}
        .panel li{padding:8px 0 8px 30px;position:relative;color:var(--md)}
        .panel li::before{content:"";position:absolute;left:0;top:12px;width:18px;height:18px;border-radius:50%;background:var(--blue) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3'%3E%3Cpath d='M5 13l4 4L19 7'/%3E%3C/svg%3E") center/12px no-repeat}
        .panel .cost{font-size:14px;color:var(--md);background:var(--soft);border-radius:12px;padding:10px 14px;margin-bottom:22px}
        .panel .acts{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
        .chip{display:inline-block;font-size:12px;font-weight:700;padding:4px 10px;border-radius:999px;margin:4px 4px 0 0}
        .mock{background:#fff;border-radius:18px;border:1px solid var(--line);box-shadow:0 30px 60px rgba(0,77,105,.12);overflow:hidden}
        .mock .bar{height:36px;background:#f8fafc;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:6px;padding:0 14px}
        .mock .bar i{width:10px;height:10px;border-radius:50%;background:#cbd5e1}
        .mock .bar small{margin-left:10px;font-size:12px;color:var(--muted)}
        .mock .in{padding:22px}
        .kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
        .kpi{border:1px solid var(--line);border-radius:12px;padding:12px}.kpi small{color:var(--muted);font-size:12px}.kpi b{display:block;font-size:24px}
        .bars div{height:10px;border-radius:6px;background:var(--line);margin:9px 0;position:relative;overflow:hidden}
        .bars div span{position:absolute;inset:0 auto 0 0;border-radius:6px;width:0;transition:width 1.2s ease}
        .panel.on .bars div span{width:var(--w)}
        table{width:100%;border-collapse:collapse;font-size:15px}
        th{font-size:12px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);text-align:left;padding:12px;border-bottom:1px solid var(--line)}
        td{padding:13px 12px;border-bottom:1px solid var(--line)}

        /* variante slider (effetto Semrush): card affiancate, "+" che apre in largo */
        .slider-nav{display:flex;justify-content:flex-end;gap:10px;margin:-52px 0 22px}
        .arr{width:48px;height:48px;border-radius:50%;border:1px solid #cbd5e1;background:#fff;font-size:18px;cursor:pointer;color:var(--ink);font-family:inherit}.arr:hover{border-color:var(--ink)}
        .track{display:flex;gap:16px;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;padding:4px 4px 24px;margin:0 -4px}
        .track::-webkit-scrollbar{display:none}
        .tcard{flex:0 0 384px;scroll-snap-align:start;background:#fff;border:1px solid var(--line);border-radius:24px;padding:26px;display:flex;flex-direction:column;gap:18px;min-height:520px;transition:flex-basis .45s cubic-bezier(.2,.8,.2,1)}
        .tcard.open{flex-basis:800px}
        .tcard-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start}
        .tcard .phase{display:block;font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
        .tcard h3{font-size:24px;letter-spacing:-.5px;line-height:1.15;margin-top:10px}
        .plus{flex-shrink:0;width:44px;height:44px;border-radius:50%;border:1px solid #cbd5e1;background:#fff;cursor:pointer;position:relative;transition:transform .3s}
        .plus span,.plus span::after{position:absolute;background:var(--ink);border-radius:2px}
        .plus span{width:16px;height:2px;left:13px;top:20px}.plus span::after{content:"";width:2px;height:16px;left:7px;top:-7px}
        .tcard.open .plus{transform:rotate(45deg)}
        .tcard-body{display:grid;grid-template-columns:1fr;gap:22px;align-items:start;flex:1}
        .tcard.open .tcard-body{grid-template-columns:1fr 1fr}
        .tcard-text{display:none}
        .tcard.open .tcard-text{display:block;animation:rise .35s ease}
        .tcard .when{color:var(--muted);font-size:15px}
        .tcard ul{list-style:none;margin:14px 0 16px}
        .tcard li{padding:7px 0 7px 28px;position:relative;color:var(--md);font-size:15px}
        .tcard li::before{content:"";position:absolute;left:0;top:11px;width:16px;height:16px;border-radius:50%;background:var(--blue) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3'%3E%3Cpath d='M5 13l4 4L19 7'/%3E%3C/svg%3E") center/11px no-repeat}
        .tcard .cost{font-size:14px;color:var(--md);background:var(--soft);border-radius:12px;padding:10px 14px;margin-bottom:18px}
        .tcard .acts{display:flex;align-items:center;gap:18px;flex-wrap:wrap}
        .tcard .mock{align-self:end;box-shadow:0 18px 40px rgba(0,77,105,.10)}
        .tcard:not(.open) .mock{transform:scale(.92);transform-origin:bottom center;margin-top:auto}
        .tcard.open .bars div span{width:var(--w)}
        /* una settimana */
        .chain{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-top:44px}
        .step{border:1px solid var(--line);border-radius:18px;padding:22px;background:#fff}
        .step .n{font-size:13px;font-weight:700;color:var(--blue);text-transform:uppercase;letter-spacing:1px}
        .step h4{font-size:17px;margin:8px 0 8px}.step p{font-size:14px;color:var(--md)}.step p b{color:var(--ink)}

        .case{display:grid;grid-template-columns:1fr 1.1fr;gap:56px;align-items:center}
        .case .then{margin-top:22px;padding:18px 20px;border-left:3px solid var(--blue);background:#fff;border-radius:0 14px 14px 0;color:var(--md);font-size:15px}
        .aud{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:40px}

        /* agenzie e professionisti */
        .pro{background:var(--dark);color:#fff;border-radius:32px;padding:56px 56px 48px}
        .pro .eyebrow{color:#7fdcff}.pro h2{color:#fff}.pro .lead{color:#cfe9f3}
        .pro .cols{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:32px}
        .pro .col{border:1px solid rgba(255,255,255,.14);border-radius:18px;padding:20px;background:rgba(255,255,255,.04)}
        .pro .col h4{font-size:17px;margin:10px 0 6px}.pro .col p{font-size:14px;color:#cfe9f3}
        .pro .acts{display:flex;align-items:center;gap:20px;flex-wrap:wrap;margin-top:32px}
        .pro .acts .more{color:#7fdcff}
        /* prezzi */
        .what{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:32px}
        .what .step b{font-size:26px;display:block;letter-spacing:-.5px;color:var(--dark);margin-top:6px}.what .step p{margin-top:4px}
        .price{display:grid;grid-template-columns:1.2fr repeat(3,1fr);gap:16px;margin-top:32px}
        .pc{border:1px solid var(--line);border-radius:20px;padding:26px;display:flex;flex-direction:column;background:#fff}
        .pc.hl{background:var(--dark);color:#fff;border-color:var(--dark)}
        .pc b{font-size:36px;display:block;margin:10px 0 4px;letter-spacing:-1px}.pc small{opacity:.8}
        .pc .btn{margin-top:auto}.pc .sp{flex:1;min-height:20px}
        .faq{max-width:820px;margin:0 auto}
        details{border-bottom:1px solid var(--line);padding:20px 0}
        summary{font-weight:600;font-size:18px;cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:16px}
        summary::after{content:"+";color:var(--blue);font-size:22px;line-height:1}details[open] summary::after{content:"–"}
        details p{color:#475569;margin-top:10px}
        .final{background:var(--dark);color:#fff;border-radius:32px;padding:72px 40px;text-align:center}
        .final h2{color:#fff}.final .check{margin-top:30px}.final .micro{color:#b7dcea;margin-top:22px}.final .micro a{color:#fff;font-weight:600;text-decoration:underline}

        /* footer condiviso */
        .site-footer{background:var(--slate-900);color:#94a3b8;padding:72px 24px 36px}
        .footer-inner{max-width:var(--max-w);margin:0 auto}
        .footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:48px;margin-bottom:40px}
        .footer-brand p{font-size:14px;line-height:1.7;margin-top:16px;max-width:300px}
        .footer-col h4{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#e2e8f0;margin-bottom:16px}
        .footer-col a{display:block;font-size:14px;color:#94a3b8;padding:4px 0}.footer-col a:hover{color:#fff}
        .footer-bottom{padding-top:28px;border-top:1px solid rgba(255,255,255,.08);font-size:13px}

        @media(max-width:1000px){.hero h1{font-size:46px;letter-spacing:-1.4px}h2{font-size:34px}.chain{grid-template-columns:repeat(3,1fr)}.aud,.price{grid-template-columns:1fr 1fr}.pro .cols{grid-template-columns:1fr}.why{grid-template-columns:1fr}.panel,.case{grid-template-columns:1fr;padding:32px}.tcard{flex-basis:min(84vw,384px)}.tcard.open{flex-basis:min(84vw,800px)}.tcard.open .tcard-body{grid-template-columns:1fr}.slider-nav{margin:0 0 18px}.footer-grid{grid-template-columns:1fr 1fr}}
        @media(max-width:760px){.site-nav-links,.site-nav-actions{display:none}.nav-hamburger{display:block}.nav-mobile-menu.open{display:flex}}
        @media(max-width:600px){.hero{padding:56px 0 48px}.hero h1{font-size:36px}.hero .sub{font-size:17px}.check{flex-direction:column;border-radius:20px}.check input[type=text]{padding:12px 14px}.chain,.aud,.price,.what,.footer-grid,.pro .cols{grid-template-columns:1fr}.pro{padding:44px 24px;border-radius:24px}section{padding:64px 0}.gate{flex-direction:column;align-items:flex-start}.lock{display:none}.final{padding:48px 20px}.kpis{grid-template-columns:1fr 1fr 1fr}.kpi b{font-size:18px}.panel h3{font-size:26px}}
        @media(prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
    </style>
</head>
<body>

<div class="topbar"><a href="#strumenti" data-tab="geo"><b>Novità:</b> AI GEO Audit — scopri se ChatGPT e Gemini consigliano la tua attività →</a></div>
<?php include __DIR__ . '/includes/site-header.php'; ?>

<!-- HERO + CONTROLLO GRATUITO -->
<header class="hero">
    <div class="wrap">
        <h1>Fatti trovare su Google.<br><em>Fatti consigliare dalle AI.</em></h1>
        <p class="sub">Le persone hanno iniziato a chiedere a ChatGPT e Gemini, non solo a Google. Ainstein ti dice se il tuo sito si fa trovare e consigliare, e ti prepara le pagine, gli articoli e le correzioni per arrivarci. In italiano, anche se non sei un esperto SEO.</p>

        <form class="check js-check" novalidate>
            <label for="checkUrl" class="hp">Il tuo sito</label>
            <input type="text" id="checkUrl" name="url" placeholder="Scrivi il tuo sito, es. miosito.it" autocomplete="url" inputmode="url" required>
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
            <button class="btn btn-blue" type="submit">Controlla gratis</button>
        </form>
        <p class="micro">Se le AI non leggono il tuo sito, non possono consigliarti. Lo scopri in meno di un minuto, gratis, senza registrarti.</p>

        <div class="progress" id="progress" aria-live="polite">
            <div class="track"><div class="fill" id="progressFill"></div></div>
            <p id="progressText">Apro il sito come lo aprirebbe ChatGPT…</p>
        </div>
        <div class="msg err" id="checkMsg" role="alert"></div>

        <div class="result" id="result" aria-live="polite">
            <div class="score">
                <div class="ring" id="ring"><div id="ringText">0/5</div></div>
                <div><b id="verdict"></b><span id="meta"></span></div>
            </div>
            <div id="rows"></div>
            <div class="gate">
                <div><strong>Vuoi i dettagli e come correggere?</strong><br><span>Registrati: ritrovi l'analisi completa e ricevi <?= (int) $bonus ?> crediti in regalo.</span></div>
                <a class="btn btn-blue" id="gateLink" href="<?= url('/register') ?>">Vedi i dettagli</a>
            </div>
        </div>
    </div>
</header>

<!-- COS'È CAMBIATO -->
<section style="padding-top:72px">
    <div class="wrap">
        <div class="eyebrow">Cos'è cambiato</div>
        <h2>Chi cerca, oggi, chiede.</h2>
        <p class="lead">Google resta il primo posto dove ti cercano. Ma non è più l'unico.</p>
        <div class="why">
            <div class="card"><?= $ico($icons['google']) ?><h4>Prima</h4><p>Cercavi su Google, guardavi dieci risultati e sceglievi tu. Bastava esserci, possibilmente in alto.</p></div>
            <div class="card"><?= $ico($icons['chat']) ?><h4>Oggi</h4><p>Chiedi a ChatGPT o a Gemini «a chi mi rivolgo per…» e la risposta fa due o tre nomi. Se il tuo non c'è, per quel cliente non esisti.</p></div>
            <div class="card fact"><?= $ico($icons['chart']) ?><h4>Sta già succedendo</h4><p>Su un sito che seguo, quasi una visita su dieci arriva da ChatGPT e simili. Senza che nessuno l'abbia cercato lì.</p><small>Google Analytics, ottobre 2026</small></div>
        </div>
    </div>
</section>

<!-- STRUMENTI (tabs su /, slider su /landing-b) -->
<?php include __DIR__ . '/includes/landing-tools.php'; ?>

<!-- UNA SETTIMANA CON AINSTEIN -->
<section>
    <div class="wrap">
        <div class="eyebrow">Come funziona in pratica</div>
        <h2>Una settimana con Ainstein.</h2>
        <p class="lead">Prendiamo una pasticceria di Torino che fa torte su ordinazione. Il sito c'è da anni, ma non lo trova nessuno.</p>
        <div class="chain">
            <div class="step"><div class="n">Lunedì</div><h4>Il controllo e l'audit</h4><p>Scrive il sito nella casella qui sopra. Poi <b>AI Audit</b> legge tutte le pagine: titoli, link rotti, pagine nascoste. Gratis.</p></div>
            <div class="step"><div class="n">Martedì</div><h4>Descrive l'attività</h4><p>«Pasticceria a Torino, torte su ordinazione, senza glutine e vegane.» <b>AI Keywords</b> le restituisce le ricerche con i volumi di Google, le pagine che servono e gli articoli dei prossimi tre mesi.</p></div>
            <div class="step"><div class="n">Giovedì</div><h4>Il primo articolo</h4><p><b>AI Writer</b> prende il primo titolo del piano, guarda chi è primo su Google, scrive l'articolo con la copertina e lo mette su WordPress come bozza.</p></div>
            <div class="step"><div class="n">Quando vuole</div><h4>Le AI</h4><p><b>AI GEO Audit</b> chiede a ChatGPT e Gemini «dove ordino una torta senza glutine a Torino» e le dice se fanno il suo nome, e chi c'è al posto suo.</p></div>
            <div class="step"><div class="n">Tra un mese</div><h4>Vede se funziona</h4><p><b>AI Rank</b> le dice dove compare, cosa è salito e cosa è sceso, e nell'Andamento vede da dove arriva il traffico, anche dalle AI, con una lettura breve scritta dall'AI.</p></div>
        </div>
    </div>
</section>

<!-- CASO VERO -->
<section style="background:var(--soft)">
    <div class="wrap case">
        <div>
            <div class="eyebrow">Un caso vero</div>
            <h2>Le AI sanno chi sei. Ma ai clienti consigliano altri.</h2>
            <p class="lead">Un'attività turistica in Campania. Quando chiedi a ChatGPT di lei per nome, la conosce. Quando un turista chiede dove noleggiare una barca, consiglia i concorrenti. E in due risposte legge il suo sito per farlo.</p>
            <p class="then"><b>Cosa ha detto di fare AI GEO Audit:</b> mettere sul sito la partita IVA e i dati dell'azienda, che le AI non trovavano e per questo si fidavano poco; scrivere tre articoli sulle domande che i turisti fanno davvero, a partire dal noleggio senza patente.</p>
        </div>
        <div class="mock"><div class="in">
            <table>
                <tr><th>Domanda alle AI</th><th>La nominano</th></tr>
                <tr><td>Domande con il suo nome</td><td><b style="color:#059669">79%</b></td></tr>
                <tr><td>Domande di chi cerca il servizio</td><td><b style="color:#dc2626">2%</b></td></tr>
                <tr><td>Risposte che leggono il suo sito e consigliano altri</td><td><b style="color:#d97706">2</b></td></tr>
            </table>
            <p style="font-size:13px;color:var(--muted);margin-top:12px">AI GEO Audit, 44 risposte di ChatGPT e Gemini, ottobre 2026.</p>
        </div></div>
    </div>
</section>

<!-- PER CHI È -->
<section>
    <div class="wrap">
        <div class="eyebrow">Per chi è</div>
        <h2>Non serve essere esperti SEO.</h2>
        <div class="aud">
            <div class="card"><?= $ico($icons['doc']) ?><h4>Hai un blog</h4><p>Descrivi di cosa scrivi: AI Keywords ti dà gli articoli dei prossimi mesi con i volumi di Google, AI Writer li scrive. Anche da solo, nei giorni che scegli.</p></div>
            <div class="card"><?= $ico($icons['bag']) ?><h4>Piccolo e-commerce</h4><p>Le pagine di categoria con i nomi che la gente cerca davvero, titoli e descrizioni scritti dall'AI, e un controllo su cosa consigliano le AI quando un cliente chiede un prodotto come il tuo.</p></div>
            <div class="card"><?= $ico($icons['pin']) ?><h4>Attività locale</h4><p>Quando un cliente chiede a ChatGPT «a chi mi rivolgo in zona», AI GEO Audit ti dice se fanno il tuo nome e cosa mettere sul sito per entrarci.</p></div>
            <div class="card"><?= $ico($icons['case']) ?><h4>Freelance e agenzie</h4><p>Un progetto per ogni cliente, condiviso con chi vuoi, con i report e i PDF da girare. I crediti li usi dove servono, cliente per cliente.</p></div>
        </div>
    </div>
</section>

<!-- AGENZIE E PROFESSIONISTI -->
<section style="padding-top:0">
    <div class="wrap">
        <div class="pro">
            <div class="eyebrow">Agenzie e professionisti</div>
            <h2>Un progetto per ogni cliente. Dentro, solo quello che serve.</h2>
            <p class="lead">Tieni gli strumenti che usi già: Ainstein si aggiunge. Crei un progetto per sito e in ognuno accendi o spegni i cinque strumenti come vuoi. I crediti sono un conto solo: li spendi dove servono, cliente per cliente.</p>
            <div class="cols">
                <div class="col"><?= $ico($icons['team']) ?><h4>Condiviso con chi vuoi</h4><p>Il cliente in sola lettura, chi lavora con te può modificare.</p></div>
                <div class="col"><?= $ico($icons['chart']) ?><h4>Niente da tenere acceso per forza</h4><p>Un GEO Audit per il ristorante, gli articoli per l'e-commerce, le posizioni per tutti.</p></div>
                <div class="col"><?= $ico($icons['doc']) ?><h4>Cose da girare</h4><p>PDF del GEO Audit, piano d'azione del sito, Andamento con Analytics e Search Console: in italiano, con i numeri veri.</p></div>
            </div>
            <div class="acts"><a class="btn" style="background:#fff;color:var(--dark)" href="<?= url('/register') ?>">Inizia con <?= (int) $bonus ?> crediti</a><a class="more" href="mailto:info@ainstein.it?subject=<?= rawurlencode('Ainstein per la mia agenzia') ?>">Ne usi di più? Scrivimi per un piano →</a></div>
        </div>
    </div>
</section>

<!-- PREZZI -->
<section style="background:var(--soft)">
    <div class="wrap">
        <div class="eyebrow">Quanto costa</div>
        <h2>Inizia con <?= (int) $bonus ?> crediti in regalo.</h2>
        <p class="lead">Gratis quello che non costa niente a me: la scansione del sito, i volumi delle ricerche, il controllo tecnico per le AI. A crediti quello che chiama l'AI, e il costo lo vedi prima di ogni operazione.</p>
        <div class="what">
            <div class="step"><div class="n">Con <?= (int) $bonus ?> crediti fai, per esempio</div><b>4 articoli</b><p>completi, con copertina e scaletta presa da chi è primo su Google.</p></div>
            <div class="step"><div class="n">Oppure</div><b>30 ricerche</b><p>di keyword con i volumi veri di Google, raggruppate per argomento.</p></div>
            <div class="step"><div class="n">Oppure</div><b>Audit + piano</b><p>la scansione del sito è gratis, il piano d'azione costa 10. Ne restano 90.</p></div>
        </div>
        <div class="price">
            <div class="pc hl"><small>All'iscrizione</small><b><?= (int) $bonus ?> crediti</b><small>in regalo, una volta sola, senza carta di credito.</small><div class="sp"></div><a class="btn" style="background:#fff;color:var(--dark)" href="<?= url('/register') ?>">Inizia gratis</a></div>
            <?php foreach ($plans as $p): ?>
            <div class="pc"><small><?= htmlspecialchars($p['name']) ?></small><b><?= $num($p['price_monthly']) ?> €</b><small><?= $num($p['credits_monthly']) ?> crediti al mese</small><div class="sp"></div><a class="btn btn-ghost" href="mailto:info@ainstein.it?subject=<?= rawurlencode('Piano ' . $p['name'] . ' Ainstein') ?>">Contattami</a></div>
            <?php endforeach; ?>
        </div>
        <p class="micro" style="text-align:left">I piani per ora li attivo a mano: scrivimi a info@ainstein.it. <a href="<?= url('/pricing') ?>" style="color:var(--blue);font-weight:600">Tutti i dettagli →</a></p>
    </div>
</section>

<!-- FAQ -->
<section>
    <div class="wrap faq">
        <h2 style="text-align:center;margin-bottom:24px">Domande vere</h2>
        <details><summary>Perché non uso direttamente ChatGPT?</summary><p>Per scrivere un testo puoi. Ainstein fa le cose intorno: legge il tuo sito, prende i volumi veri da Google, guarda chi è primo, tiene lo storico delle posizioni, pubblica su WordPress e ti dice cosa rispondono le AI quando un cliente chiede di te. Il testo è l'ultimo passo, non il primo.</p></details>
        <details><summary>Devo essere un esperto SEO?</summary><p>No. Ogni strumento ti dice cosa fare con parole semplici: quale pagina sistemare, quale articolo scrivere, quale dato aggiungere al sito. Se sei un esperto, trovi comunque tutti i numeri.</p></details>
        <details><summary>Cosa controlla il test gratuito in alto?</summary><p>Se i lettori di ChatGPT, Claude, Perplexity e Bing riescono a entrare nel sito, se la home si legge senza JavaScript, se ci sono i dati su chi sei (nome, indirizzo, partita IVA), la sitemap e il file llms.txt. Non usa l'AI e non salva niente del tuo sito oltre al risultato.</p></details>
        <details><summary>Serve WordPress?</summary><p>No. Serve solo se vuoi che gli articoli e i meta tag arrivino sul sito direttamente dal tool. Con altri sistemi copi il testo pronto.</p></details>
        <details><summary>Dove finiscono i dati del mio sito?</summary><p>Restano nel tuo progetto e li cancelli quando vuoi. Per le analisi con l'AI i testi passano ai fornitori dei modelli (Anthropic, OpenAI, Google), come quando usi ChatGPT. Collegare Search Console e Analytics è facoltativo e si scollega con un clic.</p></details>
        <details><summary>Come funzionano i crediti, e quando finiscono?</summary><p>All'iscrizione ne ricevi <?= (int) $bonus ?>, una volta sola. Ogni operazione con l'AI mostra il costo prima di partire. Quando finiscono, le parti gratuite continuano a funzionare; per il resto ci sono i piani, che per ora attivo a mano su richiesta.</p></details>
        <details><summary>Posso usarlo per i siti dei miei clienti?</summary><p>Sì. Crei un progetto per ogni sito, lo condividi con il cliente o con il team ed esporti i report in PDF.</p></details>
    </div>
</section>

<!-- CHIUSURA -->
<section style="padding-top:0">
    <div class="wrap">
        <div class="final">
            <h2>Le AI riescono a leggere il tuo sito?</h2>
            <p style="opacity:.8;margin-top:10px">Scoprilo adesso. Gratis, in meno di un minuto.</p>
            <form class="check js-check" novalidate>
                <input type="text" name="url" placeholder="Scrivi il tuo sito" aria-label="Il tuo sito" autocomplete="url" inputmode="url">
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                <button class="btn btn-blue" type="submit">Controlla gratis</button>
            </form>
            <p class="micro">Se lo provi e ti sembra inutile, dimmelo: <a href="mailto:info@ainstein.it">info@ainstein.it</a>. È il feedback più utile che posso ricevere.</p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>

<script>
(function () {
    // Menu mobile
    var ham = document.getElementById('navHamburger'), mob = document.getElementById('navMobile');
    if (ham && mob) ham.addEventListener('click', function () { ham.classList.toggle('open'); mob.classList.toggle('open'); });

    // Linguette degli strumenti
    function showTab(k) {
        document.querySelectorAll('.tab').forEach(function (t) { t.setAttribute('aria-selected', t.dataset.t === k ? 'true' : 'false'); });
        document.querySelectorAll('.panel').forEach(function (p) { p.classList.toggle('on', p.id === 'p-' + k); });
    }
    document.querySelectorAll('.tab').forEach(function (t) { t.addEventListener('click', function () { showTab(t.dataset.t); }); });
    document.querySelectorAll('[data-tab]').forEach(function (a) { a.addEventListener('click', function () { showTab(a.dataset.tab); }); });

    // Variante slider: "+" apre la card in largo, frecce scorrono di una card
    var track = document.getElementById('toolTrack');
    if (track) {
        track.querySelectorAll('.plus').forEach(function (b) {
            b.addEventListener('click', function () {
                var card = b.closest('.tcard'), open = !card.classList.contains('open');
                track.querySelectorAll('.tcard.open').forEach(function (c) { c.classList.remove('open'); c.querySelector('.plus').setAttribute('aria-expanded', 'false'); });
                card.classList.toggle('open', open);
                b.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) setTimeout(function () { card.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' }); }, 80);
            });
        });
        document.querySelectorAll('.arr').forEach(function (a) {
            a.addEventListener('click', function () { track.scrollBy({ left: 400 * Number(a.dataset.dir), behavior: 'smooth' }); });
        });
    }

    // Controllo AI gratuito
    var csrf = <?= json_encode($csrf) ?>, endpoint = <?= json_encode(url('/check-ai')) ?>, registerUrl = <?= json_encode(url('/register')) ?>;
    var progress = document.getElementById('progress'), fill = document.getElementById('progressFill'), ptext = document.getElementById('progressText');
    var msg = document.getElementById('checkMsg'), result = document.getElementById('result');
    var steps = ['Apro il sito come lo aprirebbe ChatGPT…', 'Provo con i lettori di Claude, Perplexity e Bing…', 'Leggo robots.txt e la sitemap…', 'Cerco i dati su chi sei: nome, indirizzo, partita IVA…', 'Preparo il risultato…'];
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
    var locks = ['dettaglio e correzione', 'come sistemarlo', 'cosa scrivere sul sito', 'dove intervenire'];
    var busy = false;

    document.querySelectorAll('.js-check').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (busy) return;
            var url = form.url.value.trim();
            msg.classList.remove('show');
            if (!url) { msg.textContent = 'Scrivi l\'indirizzo del tuo sito, es. miosito.it'; msg.classList.add('show'); return; }
            if (form !== document.querySelector('.js-check')) { document.getElementById('checkUrl').value = url; window.scrollTo({ top: 0, behavior: 'smooth' }); }
            busy = true;
            document.querySelectorAll('.js-check button').forEach(function (b) { b.disabled = true; });
            result.classList.remove('show');
            progress.classList.add('show');
            var i = 0; fill.style.width = '8%'; ptext.textContent = steps[0];
            var timer = setInterval(function () { i = Math.min(i + 1, steps.length - 1); ptext.textContent = steps[i]; fill.style.width = Math.min(92, 8 + i * 21) + '%'; }, 4500);
            try {
                var body = new FormData();
                body.append('_csrf_token', csrf);
                body.append('url', url);
                body.append('website', form.website.value);
                var resp = await fetch(endpoint, { method: 'POST', body: body, credentials: 'same-origin' });
                var data = null;
                try { data = await resp.json(); } catch (x) { data = null; }
                if (!resp.ok || !data || data.error) {
                    throw new Error((data && data.error) || ('Il controllo non è riuscito (' + resp.status + '), riprova tra poco.'));
                }
                fill.style.width = '100%';
                render(data);
            } catch (err) {
                msg.innerHTML = esc(err.message) + ' <a href="' + registerUrl + '" style="font-weight:600;text-decoration:underline">Registrati gratis</a>';
                msg.classList.add('show');
            } finally {
                clearInterval(timer);
                setTimeout(function () { progress.classList.remove('show'); }, 400);
                busy = false;
                document.querySelectorAll('.js-check button').forEach(function (b) { b.disabled = false; });
            }
        });
    });

    function render(d) {
        var pct = Math.round(d.score / d.total * 100);
        var color = d.score >= 4 ? '#10b981' : (d.score >= 2 ? '#006e96' : '#ef4444');
        document.getElementById('ring').style.background = 'conic-gradient(' + color + ' 0 ' + pct + '%, #e2e8f0 ' + pct + '% 100%)';
        document.getElementById('ringText').textContent = d.score + '/' + d.total;
        document.getElementById('verdict').textContent = d.error_site ? 'Il sito non ha risposto bene al controllo'
            : (d.score >= 4 ? 'Le AI leggono bene il sito e capiscono chi sei' : (d.score >= 2 ? 'Le AI leggono il sito, ma non capiscono bene chi sei' : 'Le AI fanno fatica a leggere il tuo sito'));
        document.getElementById('meta').textContent = d.host + ' · ' + d.bots + ' lettori AI controllati' + (d.seconds ? ' in ' + String(d.seconds).replace('.', ',') + ' secondi' : '');
        var rows = d.highlights.map(function (h, n) {
            var sign = h.level === 'ok' ? '✓' : '!';
            var lock = h.level === 'ok' ? '' : '<span class="lock">' + locks[n % locks.length] + '</span>';
            return '<div class="row"><span class="dot ' + esc(h.level) + '">' + sign + '</span>' + esc(h.title) + lock + '</div>';
        }).join('');
        document.getElementById('rows').innerHTML = rows;
        document.getElementById('gateLink').href = registerUrl + '?check=' + encodeURIComponent(d.token);
        result.classList.add('show');
        result.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
})();
</script>
</body>
</html>
