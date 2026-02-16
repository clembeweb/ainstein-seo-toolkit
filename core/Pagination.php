<?php

namespace Core;

/**
 * Classe standard di paginazione per tutti i moduli.
 *
 * Struttura dati unificata:
 * - current_page, last_page, total, per_page, from, to
 */
class Pagination
{
    /**
     * Genera array standard di paginazione.
     *
     * @param int $total Totale record
     * @param int $page Pagina corrente (1-based)
     * @param int $perPage Record per pagina
     * @return array{current_page: int, last_page: int, total: int, per_page: int, from: int, to: int}
     */
    public static function make(int $total, int $page, int $perPage = 25): array
    {
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $from = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
        $to = min($page * $perPage, $total);

        return [
            'current_page' => $page,
            'last_page' => $lastPage,
            'total' => $total,
            'per_page' => $perPage,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Calcola offset e limit per query SQL.
     *
     * @return array{0: int, 1: int} [offset, limit]
     */
    public static function sqlLimit(int $page, int $perPage = 25): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        return [$offset, $perPage];
    }
}
