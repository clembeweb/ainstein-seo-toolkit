<?php
/**
 * Helper functions per tabelle standard.
 *
 * Includi una volta con: include __DIR__ . '/path/to/table-helpers.php';
 * Oppure il file viene auto-incluso quando usi i componenti tabella.
 */

if (!function_exists('table_sort_header')) {
    /**
     * Genera header <th> sortabile con indicatore direzione.
     *
     * @param string $label       Testo header
     * @param string $field       Nome campo per sorting (usato in query string)
     * @param string $currentSort Campo attualmente ordinato
     * @param string $currentDir  Direzione corrente (asc/desc)
     * @param string $baseUrl     URL base pagina
     * @param array  $filters     Filtri attivi da preservare
     * @return string HTML del <th>
     */
    function table_sort_header(string $label, string $field, string $currentSort, string $currentDir, string $baseUrl, array $filters = []): string
    {
        $isActive = $currentSort === $field;
        $newDir = ($isActive && $currentDir === 'asc') ? 'desc' : 'asc';

        $params = array_filter($filters, fn($v) => $v !== '' && $v !== null);
        $params['sort'] = $field;
        $params['dir'] = $newDir;
        unset($params['page']); // Reset pagina al cambio sorting

        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        $url = $baseUrl . $separator . http_build_query($params);

        // Icona SVG
        if (!$isActive) {
            $icon = '<svg class="w-3 h-3 ml-1 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>';
        } elseif ($currentDir === 'asc') {
            $icon = '<svg class="w-3 h-3 ml-1 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>';
        } else {
            $icon = '<svg class="w-3 h-3 ml-1 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
        }

        return '<th class="px-4 py-3 text-left">'
            . '<a href="' . e($url) . '" class="inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider hover:text-slate-700 dark:hover:text-slate-200 transition-colors">'
            . e($label) . $icon
            . '</a></th>';
    }
}

if (!function_exists('table_header')) {
    /**
     * Genera header <th> non sortabile (statico).
     *
     * @param string $label Testo header
     * @param string $align Allineamento: 'left', 'center', 'right'
     * @return string HTML del <th>
     */
    function table_header(string $label, string $align = 'left'): string
    {
        return '<th class="px-4 py-3 text-' . $align . ' text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">'
            . e($label)
            . '</th>';
    }
}

if (!function_exists('table_status_badge')) {
    /**
     * Genera badge di stato con colori.
     *
     * @param string $status   Valore stato
     * @param array  $colorMap Mapping stato => classe colore Tailwind
     *                         Es: ['pending' => 'slate', 'active' => 'emerald', 'error' => 'red']
     * @param string|null $label Label custom (default: ucfirst del status)
     * @return string HTML del badge
     */
    function table_status_badge(string $status, array $colorMap, ?string $label = null): string
    {
        $color = $colorMap[$status] ?? 'slate';
        $label = $label ?? ucfirst($status);

        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-' . $color . '-100 text-' . $color . '-700 dark:bg-' . $color . '-900/50 dark:text-' . $color . '-300">'
            . e($label)
            . '</span>';
    }
}

if (!function_exists('table_bulk_init')) {
    /**
     * Genera snippet x-data Alpine.js per bulk operations.
     *
     * @param array $allIds Array di tutti gli ID visibili nella pagina corrente
     * @return string JSON-encoded per x-data (contiene selectedIds e toggleAll)
     */
    function table_bulk_init(array $allIds): string
    {
        $idsJson = json_encode(array_map('strval', $allIds));
        return "{ selectedIds: [], allIds: {$idsJson}, toggleAll(\$event) { this.selectedIds = \$event.target.checked ? [...this.allIds] : []; } }";
    }
}

if (!function_exists('table_checkbox_header')) {
    /**
     * Genera <th> con checkbox "seleziona tutti".
     *
     * @return string HTML del <th>
     */
    function table_checkbox_header(): string
    {
        return '<th class="w-10 px-4 py-3">'
            . '<input type="checkbox" @change="toggleAll($event)" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">'
            . '</th>';
    }
}

if (!function_exists('table_checkbox_cell')) {
    /**
     * Genera <td> con checkbox per selezione riga.
     *
     * @param string|int $id ID del record
     * @return string HTML del <td>
     */
    function table_checkbox_cell(string|int $id): string
    {
        return '<td class="px-4 py-3">'
            . '<input type="checkbox" value="' . e((string)$id) . '" x-model="selectedIds" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">'
            . '</td>';
    }
}
