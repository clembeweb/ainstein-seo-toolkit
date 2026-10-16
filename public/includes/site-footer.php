<?php
/**
 * Shared Site Footer for public pages
 * Multi-column layout on dark background
 */
?>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-grid">
            <div class="footer-col footer-brand">
                <img src="<?= url('/assets/images/logo-ainstein-orizzontal-white.png') ?>" alt="Ainstein" height="26">
                <p>Strumenti con l'intelligenza artificiale per farti trovare su Google e consigliare dalle AI. In italiano, senza essere esperti SEO.</p>
            </div>
            <div class="footer-col">
                <h4>Strumenti AI</h4>
                <a href="<?= url('/docs/ai-reputation') ?>">AI GEO Audit</a>
                <a href="<?= url('/docs/keyword-research') ?>">AI Keywords</a>
                <a href="<?= url('/docs/seo-tracking') ?>">AI Rank</a>
                <a href="<?= url('/docs/seo-audit') ?>">AI Audit</a>
                <a href="<?= url('/docs/ai-content') ?>">AI Writer</a>
            </div>
            <div class="footer-col">
                <h4>Risorse</h4>
                <a href="<?= url('/docs') ?>">Guide</a>
                <a href="<?= url('/docs/faq') ?>">FAQ</a>
                <a href="<?= url('/docs/credits') ?>">Come funzionano i crediti</a>
                <a href="mailto:info@ainstein.it">Contatti</a>
                <a href="<?= url('/pricing') ?>">Prezzi</a>
            </div>
            <div class="footer-col">
                <h4>Legale</h4>
                <a href="<?= url('/docs/privacy') ?>">Privacy Policy</a>
                <a href="<?= url('/docs/cookies') ?>">Cookie Policy</a>
                <a href="<?= url('/docs/terms') ?>">Termini e Condizioni</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Ainstein è un servizio di Beweb Agency S.r.l.s. · Via Tommaso Sorrentino 26, 80054 Gragnano (NA) · P.IVA 09334871218 · <a href="mailto:info@ainstein.it" style="color:inherit">info@ainstein.it</a></p>
        </div>
    </div>
</footer>
