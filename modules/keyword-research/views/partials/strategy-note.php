<?php
/**
 * Nota strategica corta dei 3 strumenti: prima riga = da dove partire (in grassetto), righe "- " = azioni in elenco.
 * Le note salvate prima del 2026-10-16 sono un paragrafo unico: si vede la prima frase, il resto sotto "Leggi tutto".
 *
 * Variabili: $note (string), $noteColor ('emerald' | 'blue' | 'violet').
 */
$note = trim((string) ($note ?? ''));
if ($note === '') {
    return;
}
$noteColor = $noteColor ?? 'emerald';
$lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $note))));
$noteHead = $lines[0] ?? '';
$notePoints = [];
foreach (array_slice($lines, 1) as $line) {
    if (preg_match('/^[-•*]\s*(.+)$/u', $line, $m)) {
        $notePoints[] = $m[1];
    }
}
$noteRest = '';
if (empty($notePoints)) {
    // Paragrafo unico (note vecchie): prima frase visibile, il resto a richiesta
    $noteHead = preg_match('/^(.{20,}?[.!?])\s/u', $note, $m) ? $m[1] : mb_strimwidth($note, 0, 220, '…');
    $noteRest = trim(mb_substr($note, mb_strlen($noteHead)));
}
?>
<div class="bg-<?= $noteColor ?>-50 dark:bg-<?= $noteColor ?>-900/20 border border-<?= $noteColor ?>-200 dark:border-<?= $noteColor ?>-800 rounded-xl p-5" x-data="{ open: false }">
    <div class="flex gap-3">
        <svg class="w-5 h-5 text-<?= $noteColor ?>-600 dark:text-<?= $noteColor ?>-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
        </svg>
        <div class="min-w-0">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-<?= $noteColor ?>-700 dark:text-<?= $noteColor ?>-300">Da dove partire</h3>
            <p class="mt-1 font-medium text-slate-900 dark:text-white"><?= e($noteHead) ?></p>
            <?php if (!empty($notePoints)): ?>
            <ul class="mt-2 space-y-1">
                <?php foreach ($notePoints as $point): ?>
                <li class="flex gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-<?= $noteColor ?>-600 dark:text-<?= $noteColor ?>-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span><?= e($point) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php elseif ($noteRest !== ''): ?>
            <p x-show="open" x-collapse x-cloak class="mt-2 text-sm text-slate-700 dark:text-slate-300"><?= e($noteRest) ?></p>
            <button type="button" @click="open = !open" class="mt-1 text-xs font-medium text-<?= $noteColor ?>-700 dark:text-<?= $noteColor ?>-300 hover:underline" x-text="open ? 'Mostra meno' : 'Leggi tutto'"></button>
            <?php endif; ?>
        </div>
    </div>
</div>
