<?php

namespace Modules\AdsAnalyzer\Services;

class CsvParserService
{
    /**
     * Parse CSV export Google Ads (formato italiano)
     *
     * Gestisce:
     * - Header con BOM UTF-8
     * - Separatore punto e virgola o virgola
     * - Numeri con virgola decimale italiana
     * - Righe totali da ignorare
     */
    public function parse(string $csvContent): array
    {
        // Rimuovi BOM UTF-8 se presente
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent);

        $lines = explode("\n", $csvContent);
        $lines = array_filter($lines, fn($l) => trim($l) !== '');

        // Trova riga header
        $headerIdx = $this->findHeaderRow($lines);
        if ($headerIdx === -1) {
            throw new \Exception('Header CSV non trovato. Verifica formato export Google Ads.');
        }

        // Determina separatore
        $separator = strpos($lines[$headerIdx], ';') !== false ? ';' : ',';

        // Parse header
        $headers = str_getcsv($lines[$headerIdx], $separator);
        $headers = array_map('trim', $headers);
        $headers = array_map(fn($h) => $this->normalizeHeader($h), $headers);

        // Mappa colonne
        $colMap = $this->mapColumns($headers);

        // Parse dati
        $adGroups = [];

        for ($i = $headerIdx + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);

            // Salta righe totali
            if ($this->isTotalRow($line)) continue;

            $row = str_getcsv($line, $separator);
            if (count($row) < 3) continue;

            $term = trim($row[$colMap['term']] ?? '');
            $adGroup = trim($row[$colMap['ad_group']] ?? 'Senza Gruppo');

            if (empty($term)) continue;

            // Parse numeri (formato italiano)
            $clicks = $this->parseNumber($row[$colMap['clicks']] ?? 0);
            $impressions = $this->parseNumber($row[$colMap['impressions']] ?? 0);
            $ctr = $this->parsePercent($row[$colMap['ctr']] ?? 0);
            $cost = $this->parseNumber($row[$colMap['cost']] ?? 0);
            $conversions = $this->parseNumber($row[$colMap['conversions']] ?? 0);

            // Raggruppa per Ad Group
            if (!isset($adGroups[$adGroup])) {
                $adGroups[$adGroup] = [
                    'name' => $adGroup,
                    'terms' => [],
                    'stats' => ['total' => 0, 'zero_ctr' => 0, 'wasted_imp' => 0]
                ];
            }

            $termData = [
                'term' => $term,
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
                'cost' => $cost,
                'conversions' => $conversions,
                'is_zero_ctr' => $ctr == 0 && $impressions > 0
            ];

            $adGroups[$adGroup]['terms'][] = $termData;
            $adGroups[$adGroup]['stats']['total']++;

            if ($termData['is_zero_ctr']) {
                $adGroups[$adGroup]['stats']['zero_ctr']++;
                $adGroups[$adGroup]['stats']['wasted_imp'] += $impressions;
            }
        }

        return $adGroups;
    }

    private function findHeaderRow(array $lines): int
    {
        $keywords = ['termine di ricerca', 'search term', 'query', 'keyword'];

        foreach ($lines as $idx => $line) {
            $lower = strtolower($line);
            foreach ($keywords as $kw) {
                if (strpos($lower, $kw) !== false) {
                    return $idx;
                }
            }
        }

        return -1;
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^\w\s]/', '', $header);
        return $header;
    }

    private function mapColumns(array $headers): array
    {
        $map = [
            'term' => 0,
            'ad_group' => 1,
            'clicks' => 2,
            'impressions' => 3,
            'ctr' => 4,
            'cost' => 5,
            'conversions' => 6
        ];

        foreach ($headers as $idx => $h) {
            if (strpos($h, 'termine') !== false || strpos($h, 'search term') !== false || strpos($h, 'query') !== false) {
                $map['term'] = $idx;
            } elseif (strpos($h, 'gruppo') !== false || strpos($h, 'ad group') !== false) {
                $map['ad_group'] = $idx;
            } elseif ($h === 'clic' || $h === 'clicks' || strpos($h, 'clic') !== false) {
                $map['clicks'] = $idx;
            } elseif (strpos($h, 'impression') !== false) {
                $map['impressions'] = $idx;
            } elseif ($h === 'ctr') {
                $map['ctr'] = $idx;
            } elseif (strpos($h, 'costo') !== false || strpos($h, 'cost') !== false) {
                $map['cost'] = $idx;
            } elseif (strpos($h, 'conversion') !== false) {
                $map['conversions'] = $idx;
            }
        }

        return $map;
    }

    private function parseNumber($value): float
    {
        if (is_numeric($value)) return (float) $value;

        $value = str_replace(['€', '$', ' '], '', $value);
        $value = str_replace('.', '', $value);  // Migliaia
        $value = str_replace(',', '.', $value); // Decimali

        return (float) $value;
    }

    private function parsePercent($value): float
    {
        $value = str_replace(['%', ' '], '', $value);
        $value = str_replace(',', '.', $value);
        return (float) $value / 100;
    }

    private function isTotalRow(string $line): bool
    {
        $lower = strtolower($line);
        return strpos($lower, 'totale') !== false || strpos($lower, 'total') !== false;
    }
}
