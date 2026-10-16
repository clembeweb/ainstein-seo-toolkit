<?php
/**
 * Banner cookie per Google Analytics (Consent Mode v2). Incluso da \Core\Analytics::banner() prima di </body>
 * in tutti i layout (app, docs, landing, prezzi). CSS inline: deve funzionare anche fuori da Tailwind.
 * Scelta salvata in localStorage `ainstein_consent` = granted | denied; gtag('consent','update') al clic.
 */
?>
<div id="ainstein-cookie-banner" role="dialog" aria-live="polite" aria-label="Uso dei cookie"
     style="display:none;position:fixed;left:16px;right:16px;bottom:16px;z-index:9999;max-width:720px;margin:0 auto;background:#0f172a;color:#e2e8f0;border-radius:14px;box-shadow:0 10px 30px rgba(15,23,42,.35);padding:16px 18px;font:14px/1.5 Inter,system-ui,-apple-system,'Segoe UI',sans-serif;gap:16px;align-items:center;flex-wrap:wrap">
    <p style="margin:0;flex:1 1 320px">
        Usiamo Google Analytics per capire quante persone usano Ainstein e cosa funziona. Niente pubblicità, niente profilazione.
        <a href="<?= url('/docs/cookies') ?>" style="color:#7dd3fc;text-decoration:underline">Cookie Policy</a>
    </p>
    <div style="display:flex;gap:8px;flex:0 0 auto">
        <button type="button" data-consent="denied"
                style="background:transparent;color:#cbd5e1;border:1px solid #475569;border-radius:10px;padding:8px 14px;font:inherit;font-weight:600;cursor:pointer">Solo necessari</button>
        <button type="button" data-consent="granted"
                style="background:#0ea5e9;color:#fff;border:0;border-radius:10px;padding:8px 16px;font:inherit;font-weight:600;cursor:pointer">Accetta</button>
    </div>
</div>
<script>
(function () {
    var box = document.getElementById('ainstein-cookie-banner');
    if (!box) return;
    var saved = null;
    try { saved = localStorage.getItem('ainstein_consent'); } catch (e) {}
    if (saved === 'granted' || saved === 'denied') return;
    box.style.display = 'flex';
    box.querySelectorAll('button[data-consent]').forEach(function (b) {
        b.addEventListener('click', function () {
            var choice = b.getAttribute('data-consent');
            try { localStorage.setItem('ainstein_consent', choice); } catch (e) {}
            if (typeof gtag === 'function') {
                gtag('consent', 'update', { analytics_storage: choice });
            }
            box.style.display = 'none';
        });
    });
})();
</script>
