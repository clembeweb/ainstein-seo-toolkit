<?php
/**
 * Home pubblica di Ainstein in stile Semrush con il controllo AI gratuito (POST /check-ai).
 * Design: docs/plans/2026-10-13-landing-semrush-design.md · mockup approvato: _INBOX/2026-10-13-mockup-landing-semrush.html
 * Numeri e prezzi letti dal sistema; esempi inventati (nessun dato dei clienti, tranne il caso anonimo 79/2).
 */
$currentPage = 'home';
$bonus = \Core\Credits::signupBonus();
$botCount = count(\Modules\AiReputation\Services\GeoTechService::BOTS);
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
];
$C = ['geo' => '#6366f1', 'audit' => '#10b981', 'kw' => '#8b5cf6', 'writer' => '#f59e0b', 'rank' => '#0ea5e9'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ainstein · Fatti trovare su Google e consigliare dalle AI</title>
    <meta name="description" content="5 strumenti AI in italiano: scopri se ChatGPT e Gemini consigliano la tua attività, controlla il sito, trova cosa cerca la gente, scrivi gli articoli e segui le posizioni. Controllo gratuito in meno di un minuto.">
    <link rel="icon" type="image/svg+xml" href="<?= url('/favicon.svg') ?>">
    <meta property="og:title" content="Ainstein · Fatti trovare su Google e consigliare dalle AI">
    <meta property="og:description" content="Le AI riescono a leggere il tuo sito? Scoprilo gratis in meno di un minuto. Poi 5 strumenti AI per farti trovare e consigliare.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ainstein.it/">
    <meta name="theme-color" content="#006e96">
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

        /* hero + controllo */
        .hero{background:linear-gradient(180deg,var(--soft2) 0%,#fff 100%);padding:84px 0 56px;text-align:center}
        .hero h1{font-size:66px;line-height:1.04;letter-spacing:-2.2px;font-weight:800;max-width:960px;margin:0 auto}
        .hero h1 em{font-style:normal;color:var(--blue)}
        .hero .sub{font-size:20px;color:var(--md);max-width:700px;margin:22px auto 34px}
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

        .stats{display:grid;grid-template-columns:repeat(5,1fr);border-top:1px solid var(--line);border-bottom:1px solid var(--line);margin-top:64px}
        .stat{padding:28px 18px;text-align:center;border-right:1px solid var(--line)}.stat:last-child{border-right:0}
        .stat b{display:block;font-size:42px;letter-spacing:-1px;color:var(--dark);line-height:1.1}.stat span{font-size:14px;color:var(--muted)}

        section{padding:96px 0}
        .eyebrow{font-size:13px;font-weight:700;letter-spacing:1.6px;color:var(--blue);text-transform:uppercase}
        h2{font-size:46px;line-height:1.08;letter-spacing:-1.4px;font-weight:800;margin-top:10px}
        .lead{font-size:18px;color:#475569;max-width:660px;margin-top:14px}
        .tile{width:30px;height:30px;border-radius:9px;display:inline-grid;place-items:center;color:#fff;flex-shrink:0}
        .tile svg{width:17px;height:17px}
        .tabs{display:flex;gap:8px;flex-wrap:wrap;margin:40px 0 26px}
        .tab{border:1px solid var(--line);background:#fff;border-radius:999px;padding:9px 18px 9px 9px;font-weight:600;font-size:15px;cursor:pointer;display:flex;align-items:center;gap:10px;font-family:inherit;color:var(--ink)}
        .tab[aria-selected=true]{background:var(--ink);color:#fff;border-color:var(--ink)}
        .panel{display:none;grid-template-columns:1fr 1.15fr;gap:48px;align-items:center;background:var(--soft);border-radius:28px;padding:48px}
        .panel.on{display:grid;animation:rise .35s ease}
        .panel h3{font-size:34px;letter-spacing:-.8px;line-height:1.1;margin-top:12px}
        .panel ul{list-style:none;margin:22px 0 28px}
        .panel li{padding:8px 0 8px 30px;position:relative;color:var(--md)}
        .panel li::before{content:"";position:absolute;left:0;top:12px;width:18px;height:18px;border-radius:50%;background:var(--blue) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3'%3E%3Cpath d='M5 13l4 4L19 7'/%3E%3C/svg%3E") center/12px no-repeat}
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
        .chain{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-top:44px;counter-reset:s}
        .step{border:1px solid var(--line);border-radius:18px;padding:20px;background:#fff}
        .step .n{font-size:13px;font-weight:700;color:var(--blue)}
        .step h4{font-size:16px;margin:8px 0 6px}.step p{font-size:14px;color:var(--muted)}
        .case{display:grid;grid-template-columns:1fr 1.1fr;gap:56px;align-items:center}
        .aud{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:40px}
        .card{border:1px solid var(--line);border-radius:20px;padding:26px;background:#fff}
        .card h4{font-size:19px;margin:14px 0 8px}.card p{color:var(--muted);font-size:15px}
        .price{display:grid;grid-template-columns:1.2fr repeat(3,1fr);gap:16px;margin-top:40px}
        .pc{border:1px solid var(--line);border-radius:20px;padding:26px;display:flex;flex-direction:column}
        .pc.hl{background:var(--dark);color:#fff;border-color:var(--dark)}
        .pc b{font-size:36px;display:block;margin:10px 0 4px;letter-spacing:-1px}.pc small{opacity:.8}
        .pc .btn{margin-top:auto}.pc .sp{flex:1;min-height:20px}
        .faq{max-width:820px;margin:0 auto}
        details{border-bottom:1px solid var(--line);padding:20px 0}
        summary{font-weight:600;font-size:18px;cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:16px}
        summary::after{content:"+";color:var(--blue);font-size:22px;line-height:1}details[open] summary::after{content:"–"}
        details p{color:#475569;margin-top:10px}
        .final{background:var(--dark);color:#fff;border-radius:32px;padding:72px 40px;text-align:center}
        .final h2{color:#fff}.final .check{margin-top:30px}

        /* footer condiviso */
        .site-footer{background:var(--slate-900);color:#94a3b8;padding:72px 24px 36px}
        .footer-inner{max-width:var(--max-w);margin:0 auto}
        .footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:48px;margin-bottom:40px}
        .footer-brand p{font-size:14px;line-height:1.7;margin-top:16px;max-width:300px}
        .footer-col h4{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#e2e8f0;margin-bottom:16px}
        .footer-col a{display:block;font-size:14px;color:#94a3b8;padding:4px 0}.footer-col a:hover{color:#fff}
        .footer-bottom{padding-top:28px;border-top:1px solid rgba(255,255,255,.08);font-size:13px}

        @media(max-width:1000px){.hero h1{font-size:46px;letter-spacing:-1.4px}h2{font-size:34px}.stats{grid-template-columns:repeat(3,1fr)}.stat:nth-child(3){border-right:0}.chain{grid-template-columns:repeat(3,1fr)}.aud,.price{grid-template-columns:1fr 1fr}.panel,.case{grid-template-columns:1fr;padding:32px}.footer-grid{grid-template-columns:1fr 1fr}}
        @media(max-width:760px){.site-nav-links,.site-nav-actions{display:none}.nav-hamburger{display:block}.nav-mobile-menu.open{display:flex}}
        @media(max-width:600px){.hero{padding:56px 0 40px}.hero h1{font-size:36px}.hero .sub{font-size:17px}.check{flex-direction:column;border-radius:20px}.check input[type=text]{padding:12px 14px}.stats{grid-template-columns:1fr 1fr}.stat{border-right:0;border-bottom:1px solid var(--line)}.chain,.aud,.price,.footer-grid{grid-template-columns:1fr}section{padding:64px 0}.gate{flex-direction:column;align-items:flex-start}.lock{display:none}.final{padding:48px 20px}.kpis{grid-template-columns:1fr 1fr 1fr}.kpi b{font-size:18px}}
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
        <p class="sub">5 strumenti con l'intelligenza artificiale per controllare il sito, trovare cosa cerca la gente, scrivere i contenuti e scoprire cosa dicono di te ChatGPT e Gemini. In italiano, senza essere esperti SEO.</p>

        <form class="check js-check" novalidate>
            <label for="checkUrl" class="hp">Il tuo sito</label>
            <input type="text" id="checkUrl" name="url" placeholder="Scrivi il tuo sito, es. miosito.it" autocomplete="url" inputmode="url" required>
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
            <button class="btn btn-blue" type="submit">Controlla gratis</button>
        </form>
        <p class="micro">Le AI riescono a leggere il tuo sito? Lo scopri in meno di un minuto, gratis, senza registrarti.</p>

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

        <div class="stats">
            <div class="stat"><b>5</b><span>strumenti AI</span></div>
            <div class="stat"><b><?= (int) $botCount ?></b><span>lettori AI controllati</span></div>
            <div class="stat"><b>2</b><span>AI interrogate: ChatGPT e Gemini</span></div>
            <div class="stat"><b><?= (int) $bonus ?></b><span>crediti in regalo</span></div>
            <div class="stat"><b>0</b><span>carte di credito</span></div>
        </div>
    </div>
</header>

<!-- STRUMENTI PER OBIETTIVO -->
<section id="strumenti">
    <div class="wrap">
        <div class="eyebrow">Gli strumenti</div>
        <h2>Un obiettivo, uno strumento AI.</h2>
        <p class="lead">Parti da quello che ti serve oggi. Gli strumenti si passano i dati: le ricerche diventano pagine, le pagine articoli, gli articoli posizioni.</p>
        <div class="tabs" role="tablist">
            <?php foreach ([['geo', 'Visibilità nelle AI'], ['audit', 'Salute del sito'], ['kw', 'Ricerca'], ['writer', 'Contenuti'], ['rank', 'Posizioni e traffico']] as $i => [$k, $label]): ?>
            <button class="tab" role="tab" id="tab-<?= $k ?>" aria-controls="p-<?= $k ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" data-t="<?= $k ?>"><?= $tile($C[$k], $icons[$k]) ?><?= $label ?></button>
            <?php endforeach; ?>
        </div>

        <div class="panel on" id="p-geo" role="tabpanel" aria-labelledby="tab-geo">
            <div>
                <span class="chip" style="background:#eef2ff;color:#4f46e5">AI GEO Audit</span>
                <h3>Scopri se ChatGPT e Gemini ti consigliano.</h3>
                <ul><li>Fa alle AI le domande dei tuoi clienti, senza fare il tuo nome</li><li>Ti dice chi consigliano al posto tuo e da quali siti prendono le informazioni</li><li>Prepara il piano: articoli da pubblicare, dati da chiarire sul sito, correzioni tecniche</li></ul>
                <a class="btn btn-blue" href="<?= url('/docs/ai-reputation') ?>">Scopri AI GEO Audit →</a>
            </div>
            <div class="mock" aria-hidden="true"><div class="bar"><i></i><i></i><i></i><small>AI GEO Audit · esempio</small></div><div class="in">
                <div class="kpis"><div class="kpi"><small>Ti conoscono</small><b>81%</b></div><div class="kpi"><small>Ti consigliano</small><b style="color:#dc2626">6%</b></div><div class="kpi"><small>Usano il tuo sito</small><b style="color:#d97706">3</b></div></div>
                <small style="color:var(--muted)">Al tuo posto consigliano</small>
                <div class="bars"><div><span style="--w:82%;background:#6366f1"></span></div><div><span style="--w:61%;background:#a5b4fc"></span></div><div><span style="--w:44%;background:#c7d2fe"></span></div></div>
                <span class="chip" style="background:#fef3c7;color:#92400e">da chiarire: partita IVA</span><span class="chip" style="background:#ccfbf1;color:#0f766e">articolo: torte senza glutine su ordinazione</span>
            </div></div>
        </div>

        <div class="panel" id="p-audit" role="tabpanel" aria-labelledby="tab-audit">
            <div>
                <span class="chip" style="background:#d1fae5;color:#047857">AI Audit</span>
                <h3>Controlla il tuo sito come lo vede Google. Gratis.</h3>
                <ul><li>Titoli mancanti, link rotti, pagine nascoste, immagini senza descrizione</li><li>I problemi in italiano semplice, in ordine di importanza</li><li>Piano d'azione AI con difficoltà e impatto di ogni correzione</li></ul>
                <a class="btn btn-blue" href="<?= url('/docs/seo-audit') ?>">Scopri AI Audit →</a>
            </div>
            <div class="mock" aria-hidden="true"><div class="bar"><i></i><i></i><i></i><small>AI Audit · esempio</small></div><div class="in">
                <div class="kpis"><div class="kpi"><small>Salute</small><b style="color:#059669">78</b></div><div class="kpi"><small>Da sistemare</small><b style="color:#dc2626">3</b></div><div class="kpi"><small>Pagine</small><b>214</b></div></div>
                <small style="color:var(--muted)">Problemi per gravità</small>
                <div class="bars"><div><span style="--w:22%;background:#ef4444"></span></div><div><span style="--w:48%;background:#f59e0b"></span></div><div><span style="--w:88%;background:#10b981"></span></div></div>
            </div></div>
        </div>

        <div class="panel" id="p-kw" role="tabpanel" aria-labelledby="tab-kw">
            <div>
                <span class="chip" style="background:#ede9fe;color:#6d28d9">AI Keywords</span>
                <h3>Da cosa cerca la gente al sito intero.</h3>
                <ul><li>Le ricerche della gente con i volumi veri di Google</li><li>La struttura del sito: pagine, indirizzi, titoli</li><li>Il piano editoriale mese per mese, pronto per AI Writer</li></ul>
                <a class="btn btn-blue" href="<?= url('/docs/keyword-research') ?>">Scopri AI Keywords →</a>
            </div>
            <div class="mock" aria-hidden="true"><div class="bar"><i></i><i></i><i></i><small>Struttura del sito · esempio</small></div><div class="in">
                <table><tr><th>Ricerca</th><th>Al mese</th><th>Pagina</th></tr><tr><td>torte senza glutine torino</td><td>1.300</td><td>/senza-glutine/</td></tr><tr><td>torta di compleanno a domicilio</td><td>880</td><td>/compleanni/</td></tr><tr><td>pasticceria vegana torino</td><td>590</td><td>/vegano/</td></tr></table>
            </div></div>
        </div>

        <div class="panel" id="p-writer" role="tabpanel" aria-labelledby="tab-writer">
            <div>
                <span class="chip" style="background:#fef3c7;color:#b45309">AI Writer</span>
                <h3>Dal piano all'articolo pubblicato.</h3>
                <ul><li>Guarda chi è primo su Google e prepara la scaletta</li><li>Scrive l'articolo con copertina e link alle tue pagine</li><li>Anche in automatico nei giorni che scegli, fino a WordPress</li></ul>
                <a class="btn btn-blue" href="<?= url('/docs/ai-content') ?>">Scopri AI Writer →</a>
            </div>
            <div class="mock" aria-hidden="true"><div class="bar"><i></i><i></i><i></i><small>AI Writer · esempio</small></div><div class="in">
                <div style="height:110px;border-radius:12px;background:linear-gradient(135deg,#fde68a,#f59e0b)"></div>
                <b style="display:block;margin:12px 0 6px">Torte senza glutine: come ordinarle senza rischi</b>
                <div class="bars"><div><span style="--w:95%;background:#e2e8f0"></span></div><div><span style="--w:80%;background:#e2e8f0"></span></div></div>
                <span class="chip" style="background:#dcfce7;color:#15803d">1.800 parole</span><span class="chip" style="background:#e0f2fe;color:#0369a1">bozza su WordPress</span>
            </div></div>
        </div>

        <div class="panel" id="p-rank" role="tabpanel" aria-labelledby="tab-rank">
            <div>
                <span class="chip" style="background:#e0f2fe;color:#0369a1">AI Rank</span>
                <h3>Posizioni, traffico e il punto della situazione.</h3>
                <ul><li>Dove compari su Google e come cambia nel tempo</li><li>Quick Wins: le ricerche a un passo dalla prima pagina</li><li>Punto della situazione: Google Analytics e Search Console spiegati dall'AI</li></ul>
                <a class="btn btn-blue" href="<?= url('/docs/seo-tracking') ?>">Scopri AI Rank →</a>
            </div>
            <div class="mock" aria-hidden="true"><div class="bar"><i></i><i></i><i></i><small>Punto della situazione · esempio</small></div><div class="in">
                <svg viewBox="0 0 300 90" style="width:100%"><polyline fill="none" stroke="#0ea5e9" stroke-width="3" stroke-linecap="round" points="0,70 40,62 80,66 120,48 160,40 200,44 240,26 300,18"/></svg>
                <p style="font-size:14px;color:var(--md);margin-top:8px"><b>Il punto:</b> le visite da Google crescono del 12%. Il merito è della pagina sulle torte senza glutine, salita dalla posizione 9 alla 3…</p>
            </div></div>
        </div>
    </div>
</section>

<!-- CATENA -->
<section style="background:var(--soft)">
    <div class="wrap">
        <div class="eyebrow">L'AI lavora per te</div>
        <h2>Dal brief all'articolo pubblicato.</h2>
        <p class="lead">Descrivi la tua attività in italiano. Il resto è una catena: ogni passo prepara il successivo.</p>
        <div class="chain">
            <?php foreach ([['Descrivi', 'Cosa fai, dove, per chi.'], ['Ricerche', 'Cosa cerca la gente, coi volumi veri.'], ['Struttura', 'Le pagine che servono al sito.'], ['Piano', 'Gli articoli mese per mese.'], ['Articoli', 'Scritti e pubblicati.'], ['Controlla', 'Posizioni, traffico e AI.']] as $i => [$t, $d]): ?>
            <div class="step"><div class="n"><?= $i + 1 ?></div><h4><?= $t ?></h4><p><?= $d ?></p></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CASO VERO -->
<section>
    <div class="wrap case">
        <div>
            <div class="eyebrow">Un caso vero</div>
            <h2>Le AI sanno chi sei. Ma ai clienti consigliano altri.</h2>
            <p class="lead">Un'attività turistica in Campania. Quando chiedi a ChatGPT di lei per nome, la conosce. Quando un turista chiede dove noleggiare una barca, consiglia i concorrenti. E in due risposte legge il suo sito per farlo.</p>
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
<section style="background:var(--soft)">
    <div class="wrap">
        <div class="eyebrow">Per chi è</div>
        <h2>Non serve essere esperti SEO.</h2>
        <div class="aud">
            <div class="card"><?= $tile($C['writer'], $icons['blog']) ?><h4>Hai un blog</h4><p>Scopri di cosa scrivere e fatti preparare l'articolo.</p></div>
            <div class="card"><?= $tile($C['kw'], $icons['shop']) ?><h4>Piccolo e-commerce</h4><p>Pagine di categoria che si trovano su Google e prodotti consigliati dalle AI.</p></div>
            <div class="card"><?= $tile($C['geo'], $icons['local']) ?><h4>Attività locale</h4><p>Fatti consigliare quando un cliente chiede alle AI chi chiamare in zona.</p></div>
            <div class="card"><?= $tile($C['rank'], $icons['team']) ?><h4>Freelance e agenzie</h4><p>Progetti condivisi con i clienti, report e PDF da girare.</p></div>
        </div>
    </div>
</section>

<!-- PREZZI -->
<section>
    <div class="wrap">
        <div class="eyebrow">Prezzi</div>
        <h2>Inizia con <?= (int) $bonus ?> crediti in regalo.</h2>
        <p class="lead">Ogni operazione con l'AI costa crediti e il costo lo vedi prima di partire. La scansione di AI Audit e il controllo tecnico per le AI sono gratis.</p>
        <div class="price">
            <div class="pc hl"><small>All'iscrizione</small><b><?= (int) $bonus ?> crediti</b><small>in regalo, una volta sola, senza carta di credito.</small><div class="sp"></div><a class="btn" style="background:#fff;color:var(--dark)" href="<?= url('/register') ?>">Inizia gratis</a></div>
            <?php foreach ($plans as $p): ?>
            <div class="pc"><small><?= htmlspecialchars($p['name']) ?></small><b><?= $num($p['price_monthly']) ?> €</b><small><?= $num($p['credits_monthly']) ?> crediti al mese</small><div class="sp"></div><a class="btn btn-ghost" href="mailto:info@ainstein.it?subject=<?= rawurlencode('Piano ' . $p['name'] . ' Ainstein') ?>">Contattami</a></div>
            <?php endforeach; ?>
        </div>
        <p class="micro" style="text-align:left">I piani per ora si attivano a mano: scrivi a info@ainstein.it. <a href="<?= url('/pricing') ?>" style="color:var(--blue);font-weight:600">Tutti i dettagli →</a></p>
    </div>
</section>

<!-- FAQ -->
<section style="padding-top:0">
    <div class="wrap faq">
        <h2 style="text-align:center;margin-bottom:24px">Domande frequenti</h2>
        <details><summary>Devo essere un esperto SEO?</summary><p>No. Ogni strumento ti dice cosa fare con parole semplici: quale pagina sistemare, quale articolo scrivere, quale dato aggiungere al sito.</p></details>
        <details><summary>Cosa controlla il test gratuito?</summary><p>Se i lettori di ChatGPT, Claude, Perplexity e Bing entrano nel sito, se la home si legge senza JavaScript, se ci sono i dati su chi sei (nome, indirizzo, partita IVA), la sitemap e llms.txt. Non usa l'AI e non salva niente del tuo sito oltre al risultato.</p></details>
        <details><summary>Cosa vuol dire che le AI mi "consigliano"?</summary><p>Quando qualcuno chiede a ChatGPT o Gemini "dove compro…" o "a chi mi rivolgo per…", la risposta fa dei nomi. AI GEO Audit fa quelle domande per te e ti dice se c'è il tuo, chi c'è al posto tuo e cosa pubblicare per entrarci.</p></details>
        <details><summary>Come funzionano i crediti?</summary><p>All'iscrizione ricevi <?= (int) $bonus ?> crediti, una volta sola. Ogni operazione con l'AI costa crediti e il costo è indicato prima di partire. Per averne di più ci sono i piani, che per ora attivo a mano su richiesta.</p></details>
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
