<?php

namespace Modules\AdsAnalyzer\Services;

class EvaluationPdfService
{
    /**
     * Genera il PDF della valutazione campagne
     *
     * @param array $evaluation Record valutazione dal DB
     * @param array $aiResponse Risposta AI decodificata
     * @param array $project Record progetto dal DB
     * @return string PDF binary content
     */
    public function generate(array $evaluation, array $aiResponse, array $project): string
    {
        // Build HTML from template
        $html = $this->buildHtml($evaluation, $aiResponse, $project);

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 25,
            'margin_bottom' => 20,
            'margin_header' => 10,
            'margin_footer' => 10,
            'default_font' => 'dejavusans',
            'tempDir' => sys_get_temp_dir() . '/mpdf',
        ]);

        $mpdf->SetHTMLHeader($this->buildHeader($project));
        $mpdf->SetHTMLFooter($this->buildFooter());
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * Header HTML per ogni pagina
     */
    private function buildHeader(array $project): string
    {
        $projectName = htmlspecialchars($project['name'] ?? 'Progetto', ENT_QUOTES, 'UTF-8');
        $date = date('d/m/Y');

        return <<<HTML
<div style="border-bottom: 2px solid #e11d48; padding-bottom: 6px; font-family: dejavusans, sans-serif;">
    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td style="width: 33%; text-align: left; vertical-align: bottom;">
                <span style="font-size: 14px; font-weight: bold; color: #e11d48;">AINSTEIN</span>
            </td>
            <td style="width: 34%; text-align: center; vertical-align: bottom;">
                <span style="font-size: 9px; color: #64748b;">{$projectName}</span>
            </td>
            <td style="width: 33%; text-align: right; vertical-align: bottom;">
                <span style="font-size: 9px; color: #64748b;">{$date}</span>
            </td>
        </tr>
    </table>
</div>
HTML;
    }

    /**
     * Footer HTML per ogni pagina
     */
    private function buildFooter(): string
    {
        return <<<HTML
<div style="border-top: 1px solid #e2e8f0; padding-top: 6px; font-family: dejavusans, sans-serif;">
    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td style="width: 50%; text-align: left;">
                <span style="font-size: 8px; color: #94a3b8;">Generato da Ainstein &mdash; ainstein.it</span>
            </td>
            <td style="width: 50%; text-align: right;">
                <span style="font-size: 8px; color: #94a3b8;">Pagina {PAGENO} di {nbpg}</span>
            </td>
        </tr>
    </table>
</div>
HTML;
    }

    /**
     * Costruisce il corpo HTML del PDF usando il template
     */
    private function buildHtml(array $evaluation, array $aiResponse, array $project): string
    {
        // Variabili disponibili nel template
        $ai = $aiResponse;

        ob_start();
        include __DIR__ . '/../views/campaigns/evaluation-pdf.php';
        return ob_get_clean();
    }
}
