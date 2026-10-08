<?php

namespace Modules\AiReputation\Services;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * PDF del piano d'azione di un run: gli interventi come si vedono nel report, con la scheda operativa se esiste.
 * Nessun metodo, nessun costo di testate (regole commerciali del modulo).
 */
class ActionPlanPdfService
{
    public const TYPE_LABELS = ['removal' => 'Rimozione', 'counter_content' => 'Contro-contenuto', 'gap_article' => 'Articolo gap', 'correction' => 'Correzione'];
    public const STATUS_LABELS = ['proposed' => 'Proposto', 'accepted' => 'Accettato', 'done' => 'Fatto', 'dismissed' => 'Scartato'];
    public const CHANNEL_LABELS = ['own_site' => 'Sito proprietario', 'external' => 'Siti esterni', 'both' => 'Sito proprietario + siti esterni'];

    /** HTML del template (testabile senza mPDF). */
    public function html(array $project, array $run, array $actions, array $metrics): string
    {
        $actions = array_values(array_filter($actions, fn($a) => ($a['status'] ?? 'proposed') !== 'dismissed'));
        $contents = array_values(array_filter($actions, fn($a) => $a['type'] !== 'removal'));
        $removals = array_values(array_filter($actions, fn($a) => $a['type'] === 'removal'));
        foreach ([&$contents, &$removals] as &$list) {
            foreach ($list as &$a) {
                $a['pages'] = self::pages($a);
                $a['brief_data'] = is_string($a['brief'] ?? null) ? (json_decode($a['brief'], true) ?: null) : ($a['brief'] ?? null);
                $a['outlets'] = is_string($a['suggested_outlets'] ?? null) ? (json_decode($a['suggested_outlets'], true) ?: []) : ($a['suggested_outlets'] ?? []);
            }
            unset($a);
        }
        unset($list);
        ob_start();
        $typeLabels = self::TYPE_LABELS;
        $statusLabels = self::STATUS_LABELS;
        $channelLabels = self::CHANNEL_LABELS;
        include __DIR__ . '/../views/pdf/action-plan.php';
        return (string) ob_get_clean();
    }

    /** PDF binario. */
    public function render(array $project, array $run, array $actions, array $metrics): string
    {
        $tmp = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3)) . '/storage/cache/mpdf';
        if (!is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 16, 'margin_bottom' => 18,
            'tempDir' => $tmp,
            'default_font' => 'dejavusans',
        ]);
        $mpdf->SetTitle('Piano interventi - ' . $project['subject_name']);
        $mpdf->SetAuthor('Ainstein');
        $mpdf->SetHTMLFooter('<div style="font-size:8.5pt;color:#64748b;text-align:center;border-top:0.5pt solid #e2e8f0;padding-top:4pt;">Ainstein · AI Reputation Radar · pagina {PAGENO} di {nbpg}</div>');
        $mpdf->WriteHTML($this->html($project, $run, $actions, $metrics));
        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    public function filename(array $project, array $run): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', (string) $project['subject_name'])) ?? 'soggetto', '-')) ?: 'soggetto';
        return "piano-interventi-{$slug}-run{$run['id']}-" . date('Y-m-d', strtotime((string) $run['created_at'])) . '.pdf';
    }

    /** Pagine dell'azione (target_urls JSON, fallback target_url), solo http(s), con titolo leggibile. */
    public static function pages(array $a): array
    {
        $urls = json_decode((string) ($a['target_urls'] ?? ''), true);
        if (!is_array($urls) || !$urls) {
            $urls = !empty($a['target_url']) ? [$a['target_url']] : [];
        }
        $out = [];
        foreach ($urls as $u) {
            if (!is_string($u) || !preg_match('#^https?://#i', $u)) {
                continue;
            }
            $domain = EngineCollectorService::domainOf($u) ?? $u;
            $out[] = ['url' => $u, 'label' => $domain];
        }
        return $out;
    }
}
