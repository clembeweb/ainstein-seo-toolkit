<?php
/**
 * Componente paginazione standard per tutte le tabelle.
 *
 * Variabili richieste:
 *   $pagination - array da Core\Pagination::make() {current_page, last_page, total, per_page, from, to}
 *   $baseUrl    - URL base della pagina (senza query string di paginazione)
 *   $filters    - array di parametri query da preservare (sort, dir, status, q, etc.)
 */

if (!isset($pagination) || $pagination['last_page'] <= 1) {
    return;
}

// Costruisci parametri query preservando i filtri attivi
$buildUrl = function (int $page) use ($baseUrl, $filters) {
    $params = array_filter($filters, fn($v) => $v !== '' && $v !== null);
    $params['page'] = $page;
    $query = http_build_query($params);
    $separator = str_contains($baseUrl, '?') ? '&' : '?';
    return $baseUrl . $separator . $query;
};

$current = $pagination['current_page'];
$last = $pagination['last_page'];

// Calcola range pagine da mostrare (finestra di 5)
$start = max(1, $current - 2);
$end = min($last, $current + 2);
// Aggiusta per mostrare sempre 5 pagine se possibile
if ($end - $start < 4 && $last >= 5) {
    if ($start === 1) {
        $end = min($last, 5);
    } elseif ($end === $last) {
        $start = max(1, $last - 4);
    }
}
?>

<div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
        <!-- Info risultati -->
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Mostrando <span class="font-medium"><?= number_format($pagination['from']) ?></span>
            - <span class="font-medium"><?= number_format($pagination['to']) ?></span>
            di <span class="font-medium"><?= number_format($pagination['total']) ?></span> risultati
        </p>

        <!-- Mobile: Pagina X di Y -->
        <div class="flex sm:hidden items-center gap-2">
            <?php if ($current > 1): ?>
            <a href="<?= $buildUrl($current - 1) ?>"
               class="px-3 py-1.5 text-sm rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Prec
            </a>
            <?php endif; ?>
            <span class="text-sm text-slate-500 dark:text-slate-400">
                Pagina <?= $current ?> di <?= $last ?>
            </span>
            <?php if ($current < $last): ?>
            <a href="<?= $buildUrl($current + 1) ?>"
               class="px-3 py-1.5 text-sm rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Succ
            </a>
            <?php endif; ?>
        </div>

        <!-- Desktop: navigazione completa -->
        <div class="hidden sm:flex items-center gap-1">
            <!-- Precedente -->
            <?php if ($current > 1): ?>
            <a href="<?= $buildUrl($current - 1) ?>"
               class="px-3 py-1.5 text-sm rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Precedente
            </a>
            <?php else: ?>
            <span class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-600 cursor-not-allowed">
                Precedente
            </span>
            <?php endif; ?>

            <!-- Prima pagina + ellipsis -->
            <?php if ($start > 1): ?>
            <a href="<?= $buildUrl(1) ?>"
               class="px-3 py-1.5 text-sm rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                1
            </a>
            <?php if ($start > 2): ?>
            <span class="px-2 py-1.5 text-sm text-slate-400 dark:text-slate-500">...</span>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Numeri pagina -->
            <?php for ($i = $start; $i <= $end; $i++): ?>
            <?php if ($i === $current): ?>
            <span class="px-3 py-1.5 text-sm rounded-lg bg-primary-600 text-white font-medium">
                <?= $i ?>
            </span>
            <?php else: ?>
            <a href="<?= $buildUrl($i) ?>"
               class="px-3 py-1.5 text-sm rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <?= $i ?>
            </a>
            <?php endif; ?>
            <?php endfor; ?>

            <!-- Ellipsis + ultima pagina -->
            <?php if ($end < $last): ?>
            <?php if ($end < $last - 1): ?>
            <span class="px-2 py-1.5 text-sm text-slate-400 dark:text-slate-500">...</span>
            <?php endif; ?>
            <a href="<?= $buildUrl($last) ?>"
               class="px-3 py-1.5 text-sm rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <?= $last ?>
            </a>
            <?php endif; ?>

            <!-- Successivo -->
            <?php if ($current < $last): ?>
            <a href="<?= $buildUrl($current + 1) ?>"
               class="px-3 py-1.5 text-sm rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Successivo
            </a>
            <?php else: ?>
            <span class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-600 cursor-not-allowed">
                Successivo
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>
