<?php
/**
 * Componente empty state standard per tutte le tabelle.
 *
 * Variabili richieste:
 *   $icon    - SVG path(s) per l'icona (stringa con <path> elements)
 *   $heading - Titolo (es. "Nessun risultato")
 *   $message - Messaggio descrittivo
 *
 * Variabili opzionali:
 *   $ctaText - Testo bottone CTA
 *   $ctaUrl  - URL bottone CTA
 */
?>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700 mb-4">
        <svg class="w-8 h-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <?= $icon ?>
        </svg>
    </div>
    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2"><?= $heading ?></h3>
    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto mb-4"><?= $message ?></p>
    <?php if (!empty($ctaText) && !empty($ctaUrl)): ?>
    <a href="<?= $ctaUrl ?>"
       class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
        <?= e($ctaText) ?>
    </a>
    <?php endif; ?>
</div>
