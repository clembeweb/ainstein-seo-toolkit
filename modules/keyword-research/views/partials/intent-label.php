<?php
/**
 * Etichetta italiana per l'intent di ricerca (informational, commercial, ...).
 * Includere con include_once prima dell'uso. Versione JS: krIntentLabel() in table-helpers.php.
 */
if (!function_exists('kr_intent_label')) {
    function kr_intent_label(?string $intent): string
    {
        $map = [
            'informational' => 'Informativo',
            'transactional' => 'Transazionale',
            'commercial' => 'Commerciale',
            'navigational' => 'Navigazionale',
        ];
        $k = strtolower(trim((string) $intent));
        return $map[$k] ?? ($k !== '' ? ucfirst($k) : 'N/D');
    }
}
