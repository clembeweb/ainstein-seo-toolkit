<?php
/**
 * Helper delle viste del Report AI (unified-view.php e unified-template.php).
 *
 * Il prompt (UnifiedReportService) chiede positives come {title, description}, le settimane della timeline come
 * {title, actions[]} ed estimated_impact come oggetto; i report più vecchi avevano stringhe. Le viste stampavano
 * tutto come stringa → TypeError in e() e pagina in 500 (test manuali 2026-10-16, Madcrumbs report id 1).
 * Qui si accettano entrambi i formati. Le funzioni restituiscono testo NON escapato: l'escape lo fa la vista.
 */

if (!function_exists('sa_report_text')) {
    /** Qualsiasi valore del report → testo semplice (righe separate da "\n") */
    function sa_report_text($value): string
    {
        if ($value === null || is_bool($value)) {
            return '';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        if (!is_array($value)) {
            return '';
        }
        if (isset($value['title']) || isset($value['description'])) {
            $title = sa_report_text($value['title'] ?? '');
            $desc = sa_report_text($value['description'] ?? '');
            return trim($title . ($title !== '' && $desc !== '' ? ': ' : '') . $desc);
        }
        $labels = sa_report_impact_labels();
        $lines = [];
        foreach ($value as $k => $v) {
            $text = sa_report_text($v);
            if ($text === '') {
                continue;
            }
            $lines[] = is_string($k) ? (($labels[$k] ?? str_replace('_', ' ', $k)) . ': ' . $text) : $text;
        }
        return implode("\n", $lines);
    }

    /** Aspetto positivo → ['title' => ..., 'description' => ...] (description vuota se era una stringa sola) */
    function sa_report_positive($value): array
    {
        if (is_array($value)) {
            return ['title' => sa_report_text($value['title'] ?? ''), 'description' => sa_report_text($value['description'] ?? '')];
        }
        return ['title' => sa_report_text($value), 'description' => ''];
    }

    /** Settimana della timeline → ['title' => ..., 'actions' => [...], 'text' => ...] */
    function sa_report_week($value): array
    {
        if (is_array($value)) {
            $actions = array_values(array_filter(array_map('sa_report_text', (array) ($value['actions'] ?? [])), fn($a) => $a !== ''));
            $text = $actions ? '' : sa_report_text(array_diff_key($value, ['title' => 1, 'actions' => 1]));
            return ['title' => sa_report_text($value['title'] ?? ''), 'actions' => $actions, 'text' => $text];
        }
        return ['title' => '', 'actions' => [], 'text' => sa_report_text($value)];
    }

    /**
     * Impatto di un problema: etichetta corta per il cartellino (ALTO/MEDIO/BASSO) e, se l'AI ha scritto una frase,
     * la frase da mostrare nel testo (prima finiva tutta in maiuscolo nel cartellino).
     * @return array{tag:string, text:string}
     */
    function sa_report_impact($value, string $default): array
    {
        $text = trim(sa_report_text($value));
        if ($text !== '' && mb_strlen($text) <= 15) {
            return ['tag' => mb_strtoupper($text), 'text' => ''];
        }
        return ['tag' => $default, 'text' => $text];
    }

    /** Etichette italiane di estimated_impact */
    function sa_report_impact_labels(): array
    {
        return [
            'crawl_efficiency' => 'Efficienza della scansione di Google',
            'index_coverage' => 'Pagine indicizzate',
            'pages_to_fix' => 'Pagine da sistemare',
        ];
    }
}
