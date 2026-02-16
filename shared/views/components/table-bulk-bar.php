<?php
/**
 * Componente barra azioni bulk per tabelle.
 *
 * Variabili richieste:
 *   $actions  - Array di azioni disponibili, ogni elemento:
 *               ['label' => '', 'action' => '', 'color' => 'emerald|red|blue|amber', 'confirm' => bool]
 *               - label: testo bottone (es. "Approva", "Elimina")
 *               - action: nome metodo Alpine da chiamare (es. "bulkApprove()")
 *               - color: colore Tailwind del bottone
 *               - confirm: se true, l'azione deve gestire la conferma nel proprio metodo
 *
 * Variabili opzionali:
 *   $countVar - Nome variabile Alpine per il conteggio (default: "selectedIds.length")
 *
 * Richiede: Alpine.js con `selectedIds` array nel contesto x-data del parent.
 */

$countVar = $countVar ?? 'selectedIds.length';
?>

<div x-show="selectedIds.length > 0" x-cloak x-transition
     class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-xl p-4">
    <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
            <span x-text="<?= $countVar ?>"></span> selezionati
        </span>
        <div class="flex items-center gap-2">
            <?php foreach ($actions as $action): ?>
            <?php
                $color = $action['color'] ?? 'primary';
                $btnClass = "px-3 py-1.5 rounded-lg bg-{$color}-600 text-white text-sm font-medium hover:bg-{$color}-700 transition-colors";
            ?>
            <button type="button"
                    @click="<?= e($action['action']) ?>"
                    class="<?= $btnClass ?>">
                <?= e($action['label']) ?>
            </button>
            <?php endforeach; ?>
            <button type="button"
                    @click="selectedIds = []"
                    class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors">
                Deseleziona
            </button>
        </div>
    </div>
</div>
