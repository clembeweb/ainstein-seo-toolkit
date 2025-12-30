<?php
/**
 * Import URLs Page
 *
 * Uses the shared import-tabs component for standardized 3-tab import interface.
 */

// Configure the import component
$projectId = $project['id'];
$importUrl = "/internal-links/project/{$projectId}/urls";
$moduleSlug = "internal-links";
$backUrl = $importUrl;
$backLabel = __('Back to URLs');
$showKeyword = true;
$maxUrls = 10000;

// Override API routes to use existing routes
$apiRoutes = [
    'discover' => "/internal-links/api/sitemap-discover",
    'sitemap' => "/internal-links/api/sitemap",
    'csv' => "{$importUrl}/store",      // Uses same route as manual (handles import_type)
    'manual' => "{$importUrl}/store",
];

// Include the shared import tabs component
include BASE_PATH . '/shared/views/components/import-tabs.php';
?>
