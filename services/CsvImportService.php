<?php

namespace Services;

/**
 * CSV Import Service
 *
 * Reusable service for importing data from CSV files.
 * Can be used by any module that needs CSV import functionality.
 */
class CsvImportService
{
    protected string $delimiter = ',';
    protected bool $hasHeader = true;
    protected int $maxRows = 10000;
    protected array $errors = [];

    /**
     * Set the delimiter
     */
    public function setDelimiter(string $delimiter): self
    {
        if ($delimiter === 'auto') {
            $this->delimiter = 'auto';
        } else {
            $this->delimiter = $delimiter;
        }
        return $this;
    }

    /**
     * Set whether first row is header
     */
    public function setHasHeader(bool $hasHeader): self
    {
        $this->hasHeader = $hasHeader;
        return $this;
    }

    /**
     * Set maximum rows to import
     */
    public function setMaxRows(int $maxRows): self
    {
        $this->maxRows = $maxRows;
        return $this;
    }

    /**
     * Get errors from last operation
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Auto-detect delimiter from content
     */
    public function detectDelimiter(string $content): string
    {
        $firstLine = strtok($content, "\n");
        $delimiters = [',', ';', "\t", '|'];
        $counts = [];

        foreach ($delimiters as $d) {
            $counts[$d] = substr_count($firstLine, $d);
        }

        return array_search(max($counts), $counts) ?: ',';
    }

    /**
     * Parse CSV file and return data
     */
    public function parse(string $filePath): array
    {
        $this->errors = [];

        if (!file_exists($filePath)) {
            $this->errors[] = 'File not found: ' . $filePath;
            return [];
        }

        $content = file_get_contents($filePath);
        return $this->parseContent($content);
    }

    /**
     * Parse CSV content string
     */
    public function parseContent(string $content): array
    {
        $this->errors = [];

        // Handle BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // Auto-detect delimiter if needed
        $delimiter = $this->delimiter === 'auto' ? $this->detectDelimiter($content) : $this->delimiter;

        // Parse lines
        $lines = explode("\n", $content);
        $data = [];
        $headers = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Parse CSV line properly handling quotes
            $row = str_getcsv($line, $delimiter, '"', '\\');

            if ($this->hasHeader && $index === 0) {
                $headers = $row;
                continue;
            }

            if (count($data) >= $this->maxRows) {
                break;
            }

            $data[] = $row;
        }

        return [
            'headers' => $headers,
            'data' => $data,
            'total' => count($data),
            'delimiter' => $delimiter,
        ];
    }

    /**
     * Preview first N rows of CSV
     */
    public function preview(string $filePath, int $rows = 5): array
    {
        $originalMax = $this->maxRows;
        $this->maxRows = $rows + ($this->hasHeader ? 1 : 0);

        $result = $this->parse($filePath);

        $this->maxRows = $originalMax;

        return $result;
    }

    /**
     * Preview content (not file)
     */
    public function previewContent(string $content, int $rows = 5): array
    {
        $originalMax = $this->maxRows;
        $this->maxRows = $rows + ($this->hasHeader ? 1 : 0);

        $result = $this->parseContent($content);

        $this->maxRows = $originalMax;

        return $result;
    }

    /**
     * Get column names from parsed data
     */
    public function getColumns(array $parsedData): array
    {
        if (!empty($parsedData['headers'])) {
            return $parsedData['headers'];
        }

        // Generate column names if no header
        if (!empty($parsedData['data'][0])) {
            $count = count($parsedData['data'][0]);
            return array_map(fn($i) => "Column " . ($i + 1), range(0, $count - 1));
        }

        return [];
    }

    /**
     * Extract specific columns from parsed data
     *
     * @param array $parsedData The parsed CSV data
     * @param array $columnMap Map of output keys to column indices, e.g. ['url' => 0, 'keyword' => 1]
     * @return array Array of extracted rows
     */
    public function extractColumns(array $parsedData, array $columnMap): array
    {
        $result = [];

        foreach ($parsedData['data'] as $row) {
            $extracted = [];
            foreach ($columnMap as $key => $columnIndex) {
                if ($columnIndex !== '' && $columnIndex !== null && isset($row[$columnIndex])) {
                    $extracted[$key] = trim($row[$columnIndex]);
                } else {
                    $extracted[$key] = null;
                }
            }
            $result[] = $extracted;
        }

        return $result;
    }

    /**
     * Import URLs from CSV file
     *
     * @param string $filePath Path to CSV file
     * @param int $urlColumn Index of URL column
     * @param int|null $keywordColumn Index of keyword column (optional)
     * @param string $baseUrl Base URL to prepend to relative URLs
     * @return array Array of ['url' => ..., 'keyword' => ...]
     */
    public function importUrls(string $filePath, int $urlColumn = 0, ?int $keywordColumn = null, string $baseUrl = ''): array
    {
        $parsed = $this->parse($filePath);

        if (empty($parsed['data'])) {
            return [];
        }

        $columnMap = ['url' => $urlColumn];
        if ($keywordColumn !== null) {
            $columnMap['keyword'] = $keywordColumn;
        }

        $extracted = $this->extractColumns($parsed, $columnMap);

        // Normalize URLs
        $urls = [];
        $seen = [];

        foreach ($extracted as $row) {
            $url = $row['url'] ?? '';
            if (empty($url)) continue;

            // Make absolute URL if relative
            if (!preg_match('#^https?://#i', $url)) {
                $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
            }

            // Normalize URL
            $normalized = strtolower(rtrim($url, '/'));
            if (isset($seen[$normalized])) continue;
            $seen[$normalized] = true;

            $urls[] = [
                'url' => $url,
                'keyword' => $row['keyword'] ?? null,
            ];
        }

        return $urls;
    }

    /**
     * Import URLs from uploaded file
     */
    public function importFromUpload(array $file, int $urlColumn = 0, ?int $keywordColumn = null, string $baseUrl = ''): array
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $this->errors[] = 'Invalid uploaded file';
            return [];
        }

        return $this->importUrls($file['tmp_name'], $urlColumn, $keywordColumn, $baseUrl);
    }
}
