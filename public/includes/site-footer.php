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
                <img src="<?= url('/assets/images/logo-ainstein-orizzontal.png') ?>" alt="Ainstein" height="24" style="filter:brightness(0) invert(1); opacity:0.9;">
                <p>La piattaforma AI che automatizza il tuo marketing digitale. Dalla keyword alla pubblicazione.</p>
            </div>
            <div class="footer-col">
                <h4>Prodotto</h4>
                <a href="<?= url('/') ?>#funzionalita">AI Content Generator</a>
                <a href="<?= url('/') ?>#funzionalita">Keyword Research</a>
                <a href="<?= url('/') ?>#funzionalita">SEO Audit</a>
                <a href="<?= url('/') ?>#funzionalita">Position Tracking</a>
                <a href="<?= url('/') ?>#funzionalita">Google Ads Analyzer</a>
                <a href="<?= url('/') ?>#funzionalita">Content Creator</a>
            </div>
            <div class="footer-col">
                <h4>Risorse</h4>
                <a href="<?= url('/docs') ?>">Documentazione</a>
                <a href="<?= url('/docs/faq') ?>">FAQ</a>
                <a href="<?= url('/docs/credits') ?>">Sistema Crediti</a>
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
            <p>&copy; <?= date('Y') ?> Ainstein. Tutti i diritti riservati.</p>
        </div>
    </div>
</footer>
