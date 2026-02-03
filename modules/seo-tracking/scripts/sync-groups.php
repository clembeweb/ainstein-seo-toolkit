<?php
/**
 * Script CLI per sincronizzare gruppi da st_keywords.group_name a st_keyword_groups
 *
 * Uso: php sync-groups.php [project_id]
 *
 * Se project_id non è specificato, sincronizza TUTTI i progetti
 */

if (php_sapi_name() !== 'cli') {
    die('Questo script può essere eseguito solo da CLI');
}

// Setup
define('BASE_PATH', dirname(__DIR__, 3));
define('ROOT_PATH', BASE_PATH);

require_once BASE_PATH . '/vendor/autoload.php';

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        'Core\\' => BASE_PATH . '/core/',
        'Services\\' => BASE_PATH . '/services/',
        'Modules\\SeoTracking\\' => BASE_PATH . '/modules/seo-tracking/',
    ];

    foreach ($paths as $prefix => $baseDir) {
        if (strpos($class, $prefix) === 0) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

use Core\Database;
use Modules\SeoTracking\Models\KeywordGroup;

echo "=== SYNC GRUPPI KEYWORD ===\n\n";

$projectId = isset($argv[1]) ? (int)$argv[1] : null;

$keywordGroup = new KeywordGroup();

if ($projectId) {
    // Sincronizza solo un progetto
    echo "Sincronizzazione progetto {$projectId}...\n";
    $stats = $keywordGroup->syncAllFromKeywords($projectId);
    echo "  Gruppi: {$stats['groups_created']}\n";
    echo "  Keywords collegate: {$stats['keywords_linked']}\n";
} else {
    // Sincronizza tutti i progetti
    $projects = Database::fetchAll("SELECT id, name FROM st_projects");
    echo "Trovati " . count($projects) . " progetti\n\n";

    $totalGroups = 0;
    $totalLinked = 0;

    foreach ($projects as $project) {
        echo "Progetto {$project['id']} ({$project['name']})... ";
        $stats = $keywordGroup->syncAllFromKeywords($project['id']);
        echo "gruppi: {$stats['groups_created']}, keywords: {$stats['keywords_linked']}\n";

        $totalGroups += $stats['groups_created'];
        $totalLinked += $stats['keywords_linked'];
    }

    echo "\n=== TOTALE ===\n";
    echo "Gruppi creati: {$totalGroups}\n";
    echo "Keywords collegate: {$totalLinked}\n";
}

echo "\nCompletato!\n";
