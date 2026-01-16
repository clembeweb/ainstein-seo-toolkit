<?php

namespace Modules\SeoTracking\Helpers;

/**
 * PaginationHelper
 * Gestisce paginazione e rendering controlli
 */
class PaginationHelper
{
    /**
     * Pagina un array di items
     */
    public static function paginate(array $items, int $page = 1, int $perPage = 50): array
    {
        $total = count($items);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($items, $offset, $perPage),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $total,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ]
        ];
    }

    /**
     * Genera HTML per i controlli di paginazione
     */
    public static function render(array $pagination, array $currentFilters = []): string
    {
        if ($pagination['total_pages'] <= 1) {
            return '';
        }

        // Rimuovi page dai filtri per costruire URL base
        unset($currentFilters['page']);

        $html = '<div class="flex items-center justify-between mt-4 px-4 py-3 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">';

        // Info risultati
        $html .= '<div class="text-sm text-slate-600 dark:text-slate-400">';
        $html .= 'Mostrando <span class="font-medium">' . $pagination['from'] . '</span>';
        $html .= ' - <span class="font-medium">' . $pagination['to'] . '</span>';
        $html .= ' di <span class="font-medium">' . number_format($pagination['total_items']) . '</span> risultati';
        $html .= '</div>';

        // Controlli paginazione
        $html .= '<div class="flex items-center gap-1">';

        // Prev
        if ($pagination['has_prev']) {
            $params = array_merge($currentFilters, ['page' => $pagination['current_page'] - 1]);
            $html .= '<a href="?' . http_build_query($params) . '" class="px-3 py-1.5 text-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded hover:bg-slate-200 dark:hover:bg-slate-600 transition">';
            $html .= '← Prec</a>';
        }

        // Page numbers
        $start = max(1, $pagination['current_page'] - 2);
        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);

        if ($start > 1) {
            $params = array_merge($currentFilters, ['page' => 1]);
            $html .= '<a href="?' . http_build_query($params) . '" class="px-3 py-1.5 text-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded hover:bg-slate-200 dark:hover:bg-slate-600 transition">1</a>';
            if ($start > 2) {
                $html .= '<span class="px-2 text-slate-400">...</span>';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $params = array_merge($currentFilters, ['page' => $i]);
            if ($i === $pagination['current_page']) {
                $html .= '<span class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded font-medium">' . $i . '</span>';
            } else {
                $html .= '<a href="?' . http_build_query($params) . '" class="px-3 py-1.5 text-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded hover:bg-slate-200 dark:hover:bg-slate-600 transition">' . $i . '</a>';
            }
        }

        if ($end < $pagination['total_pages']) {
            if ($end < $pagination['total_pages'] - 1) {
                $html .= '<span class="px-2 text-slate-400">...</span>';
            }
            $params = array_merge($currentFilters, ['page' => $pagination['total_pages']]);
            $html .= '<a href="?' . http_build_query($params) . '" class="px-3 py-1.5 text-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded hover:bg-slate-200 dark:hover:bg-slate-600 transition">' . $pagination['total_pages'] . '</a>';
        }

        // Next
        if ($pagination['has_next']) {
            $params = array_merge($currentFilters, ['page' => $pagination['current_page'] + 1]);
            $html .= '<a href="?' . http_build_query($params) . '" class="px-3 py-1.5 text-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded hover:bg-slate-200 dark:hover:bg-slate-600 transition">';
            $html .= 'Succ →</a>';
        }

        $html .= '</div></div>';

        return $html;
    }
}
