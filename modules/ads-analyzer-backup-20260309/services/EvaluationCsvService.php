<?php

namespace Modules\AdsAnalyzer\Services;

/**
 * Genera CSV compatibile Google Ads Editor dai fix AI della valutazione.
 * Formato colonne identico a CampaignCreatorService.
 */
class EvaluationCsvService
{
    /**
     * Genera CSV da dati fix strutturati
     *
     * @param string $type       extensions|copy|keywords
     * @param array  $data       Dati JSON strutturati dal fix AI
     * @param string $campaignName Nome campagna per la colonna Campaign
     * @return string CSV content con BOM UTF-8
     */
    public function generate(string $type, array $data, string $campaignName = ''): string
    {
        $rows = match (true) {
            str_contains($type, 'copy')       => $this->generateCopyCsv($data, $campaignName),
            str_contains($type, 'extensions') => $this->generateExtensionsCsv($data, $campaignName),
            str_contains($type, 'keywords')   => $this->generateKeywordsCsv($data, $campaignName),
            default => throw new \Exception('Tipo CSV non supportato: ' . $type),
        };

        $output = "\xEF\xBB\xBF"; // BOM UTF-8
        foreach ($rows as $row) {
            $output .= $this->csvLine($row);
        }
        return $output;
    }

    /**
     * CSV per copy (headlines + descriptions) — formato RSA Google Ads Editor
     */
    private function generateCopyCsv(array $data, string $campaignName): array
    {
        $rows = [];

        $rows[] = ['Campaign', 'Ad Group',
            'Headline 1', 'Headline 2', 'Headline 3', 'Headline 4', 'Headline 5',
            'Headline 6', 'Headline 7', 'Headline 8', 'Headline 9', 'Headline 10',
            'Headline 11', 'Headline 12', 'Headline 13', 'Headline 14', 'Headline 15',
            'Description 1', 'Description 2', 'Description 3', 'Description 4',
            'Path 1', 'Path 2', 'Final URL'];

        $totalCols = 24; // 2 + 15 + 4 + 2 + 1
        $rsaData = array_fill(0, $totalCols, '');
        $rsaData[0] = $campaignName;
        // Headlines (cols 2-16)
        foreach (($data['headlines'] ?? []) as $i => $h) {
            if ($i < 15) $rsaData[2 + $i] = $h;
        }
        // Descriptions (cols 17-20)
        foreach (($data['descriptions'] ?? []) as $i => $d) {
            if ($i < 4) $rsaData[17 + $i] = $d;
        }
        // Paths (cols 21-22)
        $rsaData[21] = $data['paths']['path1'] ?? '';
        $rsaData[22] = $data['paths']['path2'] ?? '';

        $rows[] = $rsaData;
        return $rows;
    }

    /**
     * CSV per estensioni — formato Google Ads Editor
     */
    private function generateExtensionsCsv(array $data, string $campaignName): array
    {
        $rows = [];
        $hasSs = !empty($data['structured_snippets']);

        // Header
        $header = ['Row Type', 'Campaign',
            'Sitelink Text', 'Sitelink Description Line 1', 'Sitelink Description Line 2', 'Sitelink Final URL',
            'Callout Text'];
        if ($hasSs) {
            $header[] = 'Structured Snippet Header';
            $header[] = 'Structured Snippet Values';
        }
        $rows[] = $header;
        $totalCols = count($header);

        // Sitelinks
        foreach (($data['sitelinks'] ?? []) as $sl) {
            $row = array_fill(0, $totalCols, '');
            $row[0] = 'Sitelink';
            $row[1] = $campaignName;
            $row[2] = $sl['title'] ?? '';
            $row[3] = $sl['desc1'] ?? '';
            $row[4] = $sl['desc2'] ?? '';
            $row[5] = $sl['url'] ?? '';
            $rows[] = $row;
        }

        // Callouts
        foreach (($data['callouts'] ?? []) as $callout) {
            $row = array_fill(0, $totalCols, '');
            $row[0] = 'Callout';
            $row[1] = $campaignName;
            $row[6] = $callout;
            $rows[] = $row;
        }

        // Structured Snippets
        if ($hasSs) {
            foreach ($data['structured_snippets'] as $ss) {
                $row = array_fill(0, $totalCols, '');
                $row[0] = 'Structured Snippet';
                $row[1] = $campaignName;
                $row[7] = $ss['header'] ?? '';
                $row[8] = implode(';', $ss['values'] ?? []);
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * CSV per keyword negative — formato Google Ads Editor
     */
    private function generateKeywordsCsv(array $data, string $campaignName): array
    {
        $rows = [];
        $rows[] = ['Campaign', 'Keyword', 'Criterion Type'];

        foreach (($data['keywords'] ?? []) as $kw) {
            $rows[] = [
                $campaignName,
                $kw['keyword'] ?? '',
                ucfirst($kw['match_type'] ?? 'phrase'),
            ];
        }

        return $rows;
    }

    /**
     * Escape e formattazione riga CSV (identica a CampaignCreatorService)
     */
    private function csvLine(array $fields): string
    {
        $escaped = array_map(function ($field) {
            $field = (string) $field;
            if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
                return '"' . str_replace('"', '""', $field) . '"';
            }
            return $field;
        }, $fields);
        return implode(',', $escaped) . "\r\n";
    }
}
