<?php
/**
 * Helper per badge crediti colorati.
 *
 * Sistema a 4 livelli:
 *   Gratis (0 cr) → Verde
 *   Base   (1 cr) → Blu
 *   Standard (3 cr) → Ambra
 *   Premium (10 cr) → Viola
 *
 * Uso:
 *   include_once __DIR__ . '/path/to/credit-badge.php';
 *   echo credit_badge(3);          // → badge ambra "3 cr"
 *   echo credit_badge(0);          // → badge verde "Gratis"
 *   echo credit_badge(10, true);   // → badge viola grande "10 crediti"
 */

if (!function_exists('credit_badge')) {
    /**
     * Genera un badge colorato per il costo in crediti.
     *
     * @param float|int $cost     Costo in crediti (0, 1, 3, 10)
     * @param bool      $large    Badge grande con testo esteso
     * @param string    $extraClass Classi CSS extra
     * @return string HTML del badge
     */
    function credit_badge($cost, bool $large = false, string $extraClass = ''): string
    {
        $cost = (float) $cost;
        $tier = credit_tier($cost);

        $colors = [
            'free'     => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'base'     => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'standard' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'premium'  => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        ];

        $colorClass = $colors[$tier] ?? $colors['standard'];
        $sizeClass = $large
            ? 'px-3 py-1 text-sm'
            : 'px-2 py-0.5 text-xs';

        if ($cost == 0) {
            $label = 'Gratis';
        } elseif ($large) {
            $label = credit_format($cost) . ' crediti';
        } else {
            $label = credit_format($cost) . ' cr';
        }

        return '<span class="inline-flex items-center rounded-full font-medium ' . $sizeClass . ' ' . $colorClass . ' ' . $extraClass . '">' . $label . '</span>';
    }

    /**
     * Determina il livello (tier) di un costo crediti.
     *
     * @param float $cost Costo in crediti
     * @return string 'free', 'base', 'standard', 'premium'
     */
    function credit_tier(float $cost): string
    {
        if ($cost <= 0) return 'free';
        if ($cost <= 1) return 'base';
        if ($cost <= 3) return 'standard';
        return 'premium';
    }

    /**
     * Formatta un costo crediti per visualizzazione.
     * Rimuove decimali se intero (10.0 → "10", 1.5 → "1.5").
     *
     * @param float $cost
     * @return string
     */
    function credit_format(float $cost): string
    {
        return $cost == (int) $cost ? (string)(int)$cost : number_format($cost, 1);
    }

    /**
     * Restituisce il nome del livello in italiano.
     *
     * @param float $cost Costo in crediti
     * @return string Nome livello
     */
    function credit_tier_label(float $cost): string
    {
        $labels = [
            'free'     => 'Gratis',
            'base'     => 'Base',
            'standard' => 'Standard',
            'premium'  => 'Premium',
        ];
        return $labels[credit_tier($cost)] ?? 'Standard';
    }

    /**
     * Genera una riga di tabella per visualizzazione costi.
     *
     * @param string $operation Nome operazione
     * @param float  $cost      Costo in crediti
     * @param string $note      Nota aggiuntiva (opzionale)
     * @return string HTML della riga <tr>
     */
    function credit_cost_row(string $operation, float $cost, string $note = ''): string
    {
        $badge = credit_badge($cost);
        $noteHtml = $note ? '<span class="text-slate-400 dark:text-slate-500 text-xs ml-2">' . htmlspecialchars($note) . '</span>' : '';
        return '<tr><td class="px-4 py-2 text-sm text-slate-700 dark:text-slate-300">' . htmlspecialchars($operation) . $noteHtml . '</td><td class="px-4 py-2 text-right">' . $badge . '</td></tr>';
    }
}
