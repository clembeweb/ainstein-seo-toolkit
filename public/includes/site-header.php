<?php
/**
 * Shared Site Header — Navbar for public pages
 * Include this in landing4.php, pricing.php, feature pages
 * Requires: url() helper to be available (loaded via index.php)
 */
$currentPage = $currentPage ?? '';
?>
<nav class="site-nav" id="siteNav">
    <div class="site-nav-inner">
        <a href="<?= url('/') ?>" class="site-nav-logo">
            <img src="<?= url('/assets/images/logo-ainstein-orizzontal.png') ?>" alt="Ainstein" height="28">
        </a>

        <div class="site-nav-links" id="navLinks">
            <a href="<?= url('/') ?>#funzionalita" class="site-nav-link<?= $currentPage === 'home' ? ' active' : '' ?>">Funzionalita</a>
            <a href="<?= url('/pricing') ?>" class="site-nav-link<?= $currentPage === 'pricing' ? ' active' : '' ?>">Prezzi</a>
            <a href="<?= url('/docs') ?>" class="site-nav-link">Docs</a>
        </div>

        <div class="site-nav-actions">
            <a href="<?= url('/login') ?>" class="site-nav-link">Accedi</a>
            <a href="<?= url('/register') ?>" class="nav-cta-btn">Prova gratis</a>
        </div>

        <button class="nav-hamburger" id="navHamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>

    <!-- Mobile menu -->
    <div class="nav-mobile-menu" id="navMobile">
        <a href="<?= url('/') ?>#funzionalita">Funzionalita</a>
        <a href="<?= url('/pricing') ?>">Prezzi</a>
        <a href="<?= url('/docs') ?>">Documentazione</a>
        <hr>
        <a href="<?= url('/login') ?>">Accedi</a>
        <a href="<?= url('/register') ?>" class="nav-cta-btn" style="text-align:center;margin-top:8px;">Prova gratis</a>
    </div>
</nav>
