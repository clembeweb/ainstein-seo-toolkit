<?php

/**
 * SEO Tracking Module - Helper Functions
 */

namespace Modules\SeoTracking;

/**
 * Formatta posizione con colore
 */
function formatPosition(?float $position): string
{
    if ($position === null) {
        return '<span class="text-slate-400">-</span>';
    }

    $posInt = round($position, 1);

    if ($posInt <= 3) {
        $color = 'text-emerald-600 dark:text-emerald-400';
    } elseif ($posInt <= 10) {
        $color = 'text-blue-600 dark:text-blue-400';
    } elseif ($posInt <= 20) {
        $color = 'text-amber-600 dark:text-amber-400';
    } else {
        $color = 'text-slate-600 dark:text-slate-400';
    }

    return "<span class=\"font-medium {$color}\">{$posInt}</span>";
}

/**
 * Formatta variazione posizione con freccia
 */
function formatPositionChange(?float $change): string
{
    if ($change === null || $change == 0) {
        return '<span class="text-slate-400">-</span>';
    }

    // Posizione: negativo = miglioramento, positivo = peggioramento
    if ($change < 0) {
        $absChange = abs($change);
        return '<span class="inline-flex items-center text-emerald-600 dark:text-emerald-400">
            <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
            ' . number_format($absChange, 1) . '
        </span>';
    } else {
        return '<span class="inline-flex items-center text-red-600 dark:text-red-400">
            <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            ' . number_format($change, 1) . '
        </span>';
    }
}

/**
 * Formatta variazione percentuale
 */
function formatPercentChange(?float $change): string
{
    if ($change === null) {
        return '<span class="text-slate-400">-</span>';
    }

    $formatted = number_format(abs($change), 1) . '%';

    if ($change > 0) {
        return '<span class="inline-flex items-center text-emerald-600 dark:text-emerald-400">
            <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
            +' . $formatted . '
        </span>';
    } elseif ($change < 0) {
        return '<span class="inline-flex items-center text-red-600 dark:text-red-400">
            <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            ' . $formatted . '
        </span>';
    }

    return '<span class="text-slate-400">' . $formatted . '</span>';
}

/**
 * Formatta CTR
 */
function formatCtr(?float $ctr): string
{
    if ($ctr === null) {
        return '-';
    }

    return number_format($ctr * 100, 2) . '%';
}

/**
 * Formatta numero grande (1.2K, 1.5M)
 */
function formatLargeNumber(int $number): string
{
    if ($number >= 1000000) {
        return number_format($number / 1000000, 1) . 'M';
    }

    if ($number >= 1000) {
        return number_format($number / 1000, 1) . 'K';
    }

    return number_format($number);
}

/**
 * Formatta revenue
 */
function formatRevenue(?float $amount, string $currency = '€'): string
{
    if ($amount === null) {
        return '-';
    }

    return $currency . ' ' . number_format($amount, 2, ',', '.');
}

/**
 * Calcola colore severity
 */
function severityColor(string $severity): string
{
    return match($severity) {
        'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
        'high' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
        'medium' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        'low' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    };
}

/**
 * Calcola colore alert type
 */
function alertTypeColor(string $type): string
{
    return match($type) {
        'position_drop' => 'bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400',
        'position_gain' => 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400',
        'traffic_drop' => 'bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400',
        'revenue_drop' => 'bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400',
        default => 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400',
    };
}

/**
 * Calcola label alert type
 */
function alertTypeLabel(string $type): string
{
    return match($type) {
        'position_drop' => 'Calo Posizione',
        'position_gain' => 'Aumento Posizione',
        'traffic_drop' => 'Calo Traffico',
        'revenue_drop' => 'Calo Revenue',
        default => 'Alert',
    };
}

/**
 * Tronca testo con ellipsis
 */
function truncate(string $text, int $length = 50): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . '...';
}

/**
 * Formatta URL per visualizzazione
 */
function formatUrl(string $url, int $maxLength = 60): string
{
    // Rimuovi protocollo
    $url = preg_replace('/^https?:\/\//', '', $url);

    // Rimuovi www
    $url = preg_replace('/^www\./', '', $url);

    if (mb_strlen($url) <= $maxLength) {
        return $url;
    }

    return mb_substr($url, 0, $maxLength - 3) . '...';
}

/**
 * Genera badge per stato sync
 */
function syncStatusBadge(string $status): string
{
    return match($status) {
        'synced' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>Sincronizzato
        </span>',
        'syncing' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1 animate-pulse"></span>In sync...
        </span>',
        'error' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
            <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1"></span>Errore
        </span>',
        default => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1"></span>Non connesso
        </span>',
    };
}

/**
 * Tempo relativo (es: "2 ore fa")
 */
function timeAgo(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }

    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Adesso';
    }

    if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' min fa';
    }

    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ' . ($hours === 1 ? 'ora' : 'ore') . ' fa';
    }

    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ' . ($days === 1 ? 'giorno' : 'giorni') . ' fa';
    }

    return date('d/m/Y', $timestamp);
}

/**
 * Determina periodo di confronto label
 */
function periodLabel(int $days): string
{
    return match($days) {
        7 => 'Ultimi 7 giorni',
        14 => 'Ultime 2 settimane',
        30 => 'Ultimi 30 giorni',
        90 => 'Ultimi 3 mesi',
        180 => 'Ultimi 6 mesi',
        365 => 'Ultimo anno',
        default => "Ultimi {$days} giorni",
    };
}
