<?php
/**
 * Badge Fonte Dati GSC
 *
 * Mostra visivamente la fonte dei dati (Database, API, Cache)
 *
 * Parametri richiesti:
 * @var string $fonte 'db' | 'api' | 'cache'
 *
 * Parametri opzionali:
 * @var string|null $cacheScade Orario scadenza cache (es. "14:30")
 * @var string|null $tempoRimanente Tempo rimanente (es. "23h 45m")
 * @var bool $compatto Se true, mostra versione compatta senza testo
 */

$fonte = $fonte ?? 'db';
$cacheScade = $cacheScade ?? null;
$tempoRimanente = $tempoRimanente ?? null;
$compatto = $compatto ?? false;
?>

<?php if ($fonte === 'api'): ?>
    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300" title="Dati recuperati in tempo reale da Google Search Console">
        <!-- Heroicons: bolt -->
        <svg class="w-3 h-3 <?= $compatto ? '' : 'mr-1' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        <?php if (!$compatto): ?>
            API in tempo reale
        <?php endif; ?>
    </span>

<?php elseif ($fonte === 'cache'): ?>
    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300" title="Dati in cache - <?= $tempoRimanente ? "scade tra {$tempoRimanente}" : ($cacheScade ? "scade alle {$cacheScade}" : 'validi 24 ore') ?>">
        <!-- Heroicons: clock -->
        <svg class="w-3 h-3 <?= $compatto ? '' : 'mr-1' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?php if (!$compatto): ?>
            Cache <?= $tempoRimanente ? "({$tempoRimanente})" : ($cacheScade ? "(scade {$cacheScade})" : '(24 ore)') ?>
        <?php endif; ?>
    </span>

<?php else: ?>
    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300" title="Dati dal database locale - caricamento istantaneo">
        <!-- Heroicons: server -->
        <svg class="w-3 h-3 <?= $compatto ? '' : 'mr-1' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
        </svg>
        <?php if (!$compatto): ?>
            Database
        <?php endif; ?>
    </span>
<?php endif; ?>
