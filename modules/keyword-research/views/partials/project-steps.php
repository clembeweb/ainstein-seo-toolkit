<?php
/**
 * Schede del progetto AI Keywords: 3 strumenti indipendenti (si usa anche uno solo) + Impostazioni.
 * L'unico legame è Architettura → Piano: lo propone il banner nel Piano, non l'ordine delle schede.
 * Stesse voci e stessi nomi del menu laterale (shared/views/components/nav-items.php).
 *
 * Variabili: $project (array), $krStep ('research' | 'architecture' | 'editorial' | 'settings')
 */
$krBase = '/keyword-research/project/' . (int) $project['id'];
$krSteps = [
    'research' => ['Ricerca keyword', 'Le keyword con i volumi di Google, raggruppate per argomento'],
    'architecture' => ['Architettura sito', 'Le pagine che il sito deve avere'],
    'editorial' => ['3', 'Piano editoriale', 'Gli articoli mese per mese, dalle pagine dell\'architettura'],
];
$krStep = $krStep ?? 'research';
?>
<div class="mb-6 border-b border-slate-200 dark:border-slate-700">
    <nav class="flex items-center gap-1 overflow-x-auto" aria-label="Strumenti del progetto">
        <?php foreach ($krSteps as $key => [$label, $hint]):
            $on = $krStep === $key;
        ?>
        <a href="<?= url($krBase . '/' . $key) ?>" title="<?= e($hint) ?>"
           class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors <?= $on
               ? 'border-primary-500 text-primary-600 dark:text-primary-400'
               : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300' ?>">
            <?= e($label) ?>
        </a>
        <?php endforeach; ?>
        <a href="<?= url($krBase . '/settings') ?>"
           class="ml-auto flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors <?= $krStep === 'settings'
               ? 'border-primary-500 text-primary-600 dark:text-primary-400'
               : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300' ?>">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Impostazioni
        </a>
    </nav>
</div>
