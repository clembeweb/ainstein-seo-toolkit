<?php

namespace Services;

use Core\Credits;

class ExportService
{
    public function toCsv(int $userId, array $data, string $filename, ?string $moduleSlug = null): array
    {
        $cost = Credits::getCost('export_csv');

        if ($cost > 0 && !Credits::hasEnough($userId, $cost)) {
            return ['error' => true, 'message' => 'Crediti insufficienti'];
        }

        if (empty($data)) {
            return ['error' => true, 'message' => 'Nessun dato da esportare'];
        }

        // Genera CSV
        $output = fopen('php://temp', 'r+');

        // Header
        fputcsv($output, array_keys($data[0]));

        // Dati
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        // Consuma crediti se > 0
        if ($cost > 0) {
            Credits::consume($userId, $cost, 'export_csv', $moduleSlug, [
                'filename' => $filename,
                'rows' => count($data),
            ]);
        }

        return [
            'success' => true,
            'content' => $csv,
            'filename' => $filename . '.csv',
            'mime' => 'text/csv',
            'credits_used' => $cost,
        ];
    }

    public function toExcel(int $userId, array $data, string $filename, ?string $moduleSlug = null): array
    {
        $cost = Credits::getCost('export_excel');

        if (!Credits::hasEnough($userId, $cost)) {
            return ['error' => true, 'message' => 'Crediti insufficienti'];
        }

        if (empty($data)) {
            return ['error' => true, 'message' => 'Nessun dato da esportare'];
        }

        // Genera Excel semplice (XML Spreadsheet)
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= '  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= '<Worksheet ss:Name="Sheet1">' . "\n";
        $xml .= '<Table>' . "\n";

        // Header
        $xml .= '<Row>' . "\n";
        foreach (array_keys($data[0]) as $header) {
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";

        // Dati
        foreach ($data as $row) {
            $xml .= '<Row>' . "\n";
            foreach ($row as $value) {
                $type = is_numeric($value) ? 'Number' : 'String';
                $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars((string) $value) . '</Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>';

        Credits::consume($userId, $cost, 'export_excel', $moduleSlug, [
            'filename' => $filename,
            'rows' => count($data),
        ]);

        return [
            'success' => true,
            'content' => $xml,
            'filename' => $filename . '.xls',
            'mime' => 'application/vnd.ms-excel',
            'credits_used' => $cost,
        ];
    }

    public function download(array $exportResult): void
    {
        if (isset($exportResult['error'])) {
            http_response_code(400);
            echo $exportResult['message'];
            return;
        }

        header('Content-Type: ' . $exportResult['mime']);
        header('Content-Disposition: attachment; filename="' . $exportResult['filename'] . '"');
        header('Content-Length: ' . strlen($exportResult['content']));
        header('Cache-Control: no-cache, must-revalidate');

        echo $exportResult['content'];
        exit;
    }
}
