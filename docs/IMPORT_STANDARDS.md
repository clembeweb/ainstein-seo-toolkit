# Import Standards

Standard patterns and services for URL import functionality across SEO Toolkit modules.

## Overview

The SEO Toolkit provides a standardized import system with three methods:
1. **CSV Upload** - Import URLs from CSV/TXT files
2. **Sitemap** - Auto-discover and import from XML sitemaps
3. **Manual Input** - Direct text input for URLs

## Shared Services

### CsvImportService

Location: `services/CsvImportService.php`

A reusable service for parsing and importing data from CSV files.

#### Usage

```php
use Services\CsvImportService;

$csvService = new CsvImportService();

// Configure options
$csvService
    ->setDelimiter(',')      // or ';', '\t', 'auto'
    ->setHasHeader(true)     // first row is header
    ->setMaxRows(10000);     // limit imported rows

// Parse a file
$result = $csvService->parse('/path/to/file.csv');
// Returns: ['headers' => [...], 'data' => [...], 'total' => int, 'delimiter' => string]

// Parse content string
$result = $csvService->parseContent($csvString);

// Preview first N rows
$preview = $csvService->preview('/path/to/file.csv', 5);

// Import URLs with optional keyword column
$urls = $csvService->importUrls(
    '/path/to/file.csv',
    0,                      // URL column index
    1,                      // Keyword column index (optional, null to skip)
    'https://example.com'   // Base URL for relative URLs
);
// Returns: [['url' => '...', 'keyword' => '...'], ...]

// Import from uploaded file
$urls = $csvService->importFromUpload($_FILES['csv_file'], 0, 1, $baseUrl);

// Extract specific columns
$columnMap = ['url' => 0, 'title' => 1, 'keyword' => 2];
$extracted = $csvService->extractColumns($parsedData, $columnMap);

// Get errors
$errors = $csvService->getErrors();
```

#### Methods

| Method | Description |
|--------|-------------|
| `setDelimiter(string)` | Set CSV delimiter (`,`, `;`, `\t`, `auto`) |
| `setHasHeader(bool)` | Set if first row is header |
| `setMaxRows(int)` | Set maximum rows to import |
| `parse(string $filePath)` | Parse CSV file |
| `parseContent(string $content)` | Parse CSV content string |
| `preview(string $filePath, int $rows)` | Preview first N rows |
| `previewContent(string $content, int $rows)` | Preview content string |
| `detectDelimiter(string $content)` | Auto-detect delimiter |
| `getColumns(array $parsedData)` | Get column names |
| `extractColumns(array $parsedData, array $columnMap)` | Extract specific columns |
| `importUrls(string $filePath, ...)` | Import URLs from file |
| `importFromUpload(array $file, ...)` | Import from uploaded file |
| `getErrors()` | Get errors from last operation |

---

### SitemapService

Location: `services/SitemapService.php`

A reusable service for discovering and parsing XML sitemaps.

#### Usage

```php
use Services\SitemapService;

$sitemapService = new SitemapService();

// Configure options
$sitemapService
    ->setTimeout(30)         // HTTP request timeout
    ->setMaxUrls(10000);     // Maximum URLs to parse

// Discover sitemaps from robots.txt
$sitemaps = $sitemapService->discoverFromRobotsTxt('https://example.com', true);
// Returns: [['url' => '...', 'source' => 'robots.txt', 'url_count' => int], ...]

// Parse a single sitemap
$urls = $sitemapService->parse('https://example.com/sitemap.xml');
// Returns: ['https://example.com/page1', 'https://example.com/page2', ...]

// Parse with URL filter
$urls = $sitemapService->parse('https://example.com/sitemap.xml', '/blog/*');

// Parse multiple sitemaps (with deduplication)
$urls = $sitemapService->parseMultiple([
    'https://example.com/sitemap.xml',
    'https://example.com/sitemap-posts.xml'
], '/products/*');

// Preview sitemap (limited URLs)
$preview = $sitemapService->preview('https://example.com/sitemap.xml', 50);
// Returns: ['urls' => [...], 'total' => int, 'preview_urls' => [...], 'total_found' => int]

// Preview multiple sitemaps
$preview = $sitemapService->previewMultiple($sitemapUrls, '/blog/*', 10000);
// Returns: ['urls' => [...], 'total' => int, 'total_unique' => int, 'duplicates_removed' => int]

// Filter URLs by pattern
$filtered = $sitemapService->filterUrls($urls, '/products/*');

// Check if URL matches filter
$matches = $sitemapService->matchesFilter('https://example.com/products/item-1', '/products/*');

// Get errors
$errors = $sitemapService->getErrors();
```

#### Methods

| Method | Description |
|--------|-------------|
| `setTimeout(int)` | Set HTTP request timeout |
| `setMaxUrls(int)` | Set maximum URLs to parse |
| `discoverFromRobotsTxt(string $baseUrl, bool $getCounts)` | Discover sitemaps from robots.txt |
| `parse(string $sitemapUrl, ?string $filter)` | Parse sitemap and extract URLs |
| `parseMultiple(array $sitemapUrls, ?string $filter)` | Parse multiple sitemaps |
| `preview(string $sitemapUrl, int $limit)` | Preview URLs from sitemap |
| `previewMultiple(array $sitemapUrls, ?string $filter, int $maxUrls)` | Preview multiple sitemaps |
| `filterUrls(array $urls, string $filter)` | Filter URLs by pattern |
| `matchesFilter(string $url, string $filter)` | Check if URL matches filter |
| `getErrors()` | Get errors from last operation |

#### URL Filter Patterns

The filter uses wildcard matching:
- `*` matches any characters
- `?` matches a single character

Examples:
- `/blog/*` - Match all blog URLs
- `/products/*/reviews` - Match product review pages
- `*keyword*` - Match URLs containing "keyword"

---

## Reusable UI Component

### import-tabs.php

Location: `shared/views/components/import-tabs.php`

A complete, reusable import interface with all three tabs (CSV, Sitemap, Manual).

#### Usage

```php
<?php
// In your view file
$projectId = $project['id'];
$importUrl = "/your-module/project/{$projectId}/urls";
$moduleSlug = "your-module";

// Optional customization
$backUrl = $importUrl;
$backLabel = __('Back to URLs');
$showKeyword = true;
$maxUrls = 10000;

// Override API routes if needed
$apiRoutes = [
    'discover' => "/your-module/api/sitemap-discover",
    'sitemap' => "/your-module/api/sitemap",
    'csv' => "{$importUrl}/import-csv",
    'manual' => "{$importUrl}/store",
];

include __DIR__ . '/../../../shared/views/components/import-tabs.php';
?>
```

#### Required Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$projectId` | int | The project ID |
| `$importUrl` | string | Base URL for import actions |
| `$moduleSlug` | string | Module identifier |
| `$project` | array | Project data with `name` and `base_url` keys |

#### Optional Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$backUrl` | string | `$importUrl` | URL for back button |
| `$backLabel` | string | "Back" | Label for back button |
| `$showKeyword` | bool | `true` | Show keyword column option in CSV |
| `$maxUrls` | int | `10000` | Maximum URLs to import |
| `$apiRoutes` | array | Auto-generated | Override API route URLs |

---

## API Routes

Each module should implement these API endpoints for the import component to work:

### Sitemap Discovery

```
POST /{module}/api/sitemap-discover
```

**Request:**
```json
{
    "project_id": 123,
    "get_counts": true
}
```

**Response:**
```json
{
    "success": true,
    "sitemaps": [
        {
            "url": "https://example.com/sitemap.xml",
            "source": "robots.txt",
            "url_count": 1500
        }
    ]
}
```

### Sitemap Parse/Import

```
POST /{module}/api/sitemap
```

**Request (Preview):**
```json
{
    "project_id": 123,
    "action": "preview",
    "sitemap_urls": ["https://example.com/sitemap.xml"],
    "url_filter": "/blog/*",
    "max_urls": 10000
}
```

**Response (Preview):**
```json
{
    "success": true,
    "urls": ["https://...", "https://..."],
    "total_unique": 1500,
    "duplicates_removed": 25,
    "preview_urls": ["https://...", "https://..."]
}
```

**Request (Import):**
```json
{
    "project_id": 123,
    "action": "import",
    "sitemap_urls": ["https://example.com/sitemap.xml"],
    "url_filter": "/blog/*",
    "max_urls": 10000
}
```

**Response (Import):**
```json
{
    "success": true,
    "imported": 1500,
    "skipped": 25
}
```

### CSV Import

```
POST /{module}/project/{id}/urls/import-csv
Content-Type: multipart/form-data
```

**Form Fields:**
- `csv_file` - The uploaded CSV file
- `import_type` - "csv"
- `has_header` - "1" if first row is header
- `delimiter` - "," or ";" or "\t" or "auto"
- `url_column` - Column index for URLs (0-based)
- `keyword_column` - Column index for keywords (optional)

### Manual Import

```
POST /{module}/project/{id}/urls/store
Content-Type: application/x-www-form-urlencoded
```

**Form Fields:**
- `import_type` - "manual"
- `urls_text` - Newline-separated URLs with optional keywords

---

## Implementation Example

### routes.php

```php
use Services\SitemapService;
use Services\CsvImportService;

// Sitemap discovery API
Router::post('/your-module/api/sitemap-discover', function () {
    Middleware::auth();
    header('Content-Type: application/json');

    $user = Auth::user();
    $projectId = (int) ($_POST['project_id'] ?? 0);

    $project = (new Project())->find($projectId, $user['id']);
    if (!$project) {
        echo json_encode(['error' => 'Project not found']);
        exit;
    }

    $sitemapService = new SitemapService();
    $sitemaps = $sitemapService->discoverFromRobotsTxt($project['base_url'], true);

    echo json_encode(['success' => true, 'sitemaps' => $sitemaps]);
    exit;
});

// Sitemap parse/import API
Router::post('/your-module/api/sitemap', function () {
    Middleware::auth();
    header('Content-Type: application/json');

    $user = Auth::user();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $projectId = (int) ($input['project_id'] ?? 0);
    $action = $input['action'] ?? 'preview';

    $project = (new Project())->find($projectId, $user['id']);
    if (!$project) {
        echo json_encode(['error' => 'Project not found']);
        exit;
    }

    $sitemapService = new SitemapService();
    $sitemapService->setMaxUrls($input['max_urls'] ?? 10000);

    $sitemapUrls = $input['sitemap_urls'] ?? [$input['sitemap_url'] ?? ''];
    $filter = $input['url_filter'] ?? '';

    $result = $sitemapService->previewMultiple($sitemapUrls, $filter);

    if ($action === 'import') {
        $urlModel = new Url();
        $imported = $urlModel->bulkImport($projectId, array_map(fn($u) => ['url' => $u], $result['urls']));

        echo json_encode([
            'success' => true,
            'imported' => $imported['imported'],
            'skipped' => $imported['skipped']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'urls' => $result['urls'],
            'total_unique' => $result['total_unique'],
            'duplicates_removed' => $result['duplicates_removed'],
            'preview_urls' => array_slice($result['urls'], 0, 100)
        ]);
    }
    exit;
});

// CSV import route
Router::post('/your-module/project/{id}/urls/import-csv', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $project = (new Project())->find((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Project not found';
        header('Location: ' . url('/your-module'));
        exit;
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash_error'] = 'Upload error';
        header('Location: ' . url("/your-module/project/{$id}/urls/import"));
        exit;
    }

    $csvService = new CsvImportService();
    $csvService
        ->setDelimiter($_POST['delimiter'] ?? ',')
        ->setHasHeader(isset($_POST['has_header']));

    $urls = $csvService->importFromUpload(
        $_FILES['csv_file'],
        (int) ($_POST['url_column'] ?? 0),
        isset($_POST['keyword_column']) && $_POST['keyword_column'] !== '' ? (int) $_POST['keyword_column'] : null,
        $project['base_url']
    );

    $urlModel = new Url();
    $result = $urlModel->bulkImport((int) $id, $urls);

    $_SESSION['flash_success'] = "Imported {$result['imported']} URLs, skipped {$result['skipped']}";
    header('Location: ' . url("/your-module/project/{$id}/urls"));
    exit;
});

// Manual import route
Router::post('/your-module/project/{id}/urls/store', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $project = (new Project())->find((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Project not found';
        header('Location: ' . url('/your-module'));
        exit;
    }

    $urls = [];
    $urlText = $_POST['urls_text'] ?? '';
    $lines = explode("\n", $urlText);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) continue;

        if (str_contains($line, "\t")) {
            $parts = explode("\t", $line, 2);
        } elseif (str_contains($line, ',')) {
            $parts = str_getcsv($line);
        } else {
            $parts = [$line];
        }

        $urls[] = [
            'url' => trim($parts[0]),
            'keyword' => isset($parts[1]) ? trim($parts[1]) : null,
        ];
    }

    $urlModel = new Url();
    $result = $urlModel->bulkImport((int) $id, $urls);

    $_SESSION['flash_success'] = "Imported {$result['imported']} URLs, skipped {$result['skipped']}";
    header('Location: ' . url("/your-module/project/{$id}/urls"));
    exit;
});
```

---

## Best Practices

1. **Always validate project ownership** before performing imports
2. **Set reasonable limits** for max URLs (default 10000)
3. **Handle duplicates** - the services automatically deduplicate URLs
4. **Normalize URLs** - remove trailing slashes, lowercase for comparison
5. **Support relative URLs** - prepend base_url when needed
6. **Provide user feedback** - show import counts and any errors
7. **Use transactions** for bulk database operations when possible

---

## Dependencies

- PHP 8.0+
- cURL extension
- SimpleXML extension
- Alpine.js (for UI component)
- Tailwind CSS (for styling)
- Lucide icons (optional, for icons)
