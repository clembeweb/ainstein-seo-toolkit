<?php
/**
 * Simula l'accesso di bewebsolution (user_id=4) a tutti i moduli condivisi.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Rome');

define('BASE_PATH', __DIR__);
define('ROOT_PATH', __DIR__);
define('DEBUG', false);

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

spl_autoload_register(function ($class) {
    $paths = [
        'Core\\' => BASE_PATH . '/core/',
        'Services\\' => BASE_PATH . '/services/',
        'Controllers\\' => BASE_PATH . '/controllers/',
    ];
    foreach ($paths as $prefix => $basePath) {
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $basePath . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) { require_once $file; return; }
        }
    }
    if (str_starts_with($class, 'Modules\\')) {
        $parts = explode('\\', $class);
        if (count($parts) >= 4) {
            $moduleName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $parts[1]));
            $type = strtolower($parts[2]);
            $className = implode('/', array_slice($parts, 3));
            $file = BASE_PATH . '/modules/' . $moduleName . '/' . $type . '/' . $className . '.php';
            if (file_exists($file)) { require_once $file; return; }
        }
    }
});

require_once BASE_PATH . '/core/View.php';

use Services\ProjectAccessService;

$userId = 4; // bewebsolution
$user = \Core\Database::fetch("SELECT id, email, name FROM users WHERE id = ?", [$userId]);
echo "=== TEST ACCESSO: {$user['name']} ({$user['email']}) ===\n\n";

// 1. Memberships
echo "--- MEMBERSHIPS ---\n";
$members = \Core\Database::fetchAll(
    "SELECT pm.id, pm.project_id, p.name, pm.role, pm.accepted_at
     FROM project_members pm JOIN projects p ON p.id = pm.project_id
     WHERE pm.user_id = ?", [$userId]
);
foreach ($members as $m) {
    $status = $m['accepted_at'] ? 'ACCETTATO' : 'IN ATTESA';
    echo "  {$m['name']} (id={$m['project_id']}) | {$m['role']} | {$status}\n";
}

// 2. Moduli accessibili
echo "\n--- MODULI ACCESSIBILI PER PROGETTO ---\n";
foreach ($members as $m) {
    if (!$m['accepted_at']) continue;
    $modules = ProjectAccessService::getAccessibleModules((int)$m['project_id'], $userId);
    echo "  {$m['name']}: " . (empty($modules) ? 'NESSUNO' : implode(', ', $modules)) . "\n";
}

// 3. Test findAccessible()
$moduleTests = [
    'ads-analyzer' => ['table' => 'ga_projects', 'class' => \Modules\AdsAnalyzer\Models\Project::class],
    'ai-content' => ['table' => 'aic_projects', 'class' => \Modules\AiContent\Models\Project::class],
    'seo-audit' => ['table' => 'sa_projects', 'class' => \Modules\SeoAudit\Models\Project::class],
    'seo-tracking' => ['table' => 'st_projects', 'class' => \Modules\SeoTracking\Models\Project::class],
    'keyword-research' => ['table' => 'kr_projects', 'class' => \Modules\KeywordResearch\Models\Project::class],
    'internal-links' => ['table' => 'il_projects', 'class' => \Modules\InternalLinks\Models\Project::class],
    'content-creator' => ['table' => 'cc_projects', 'class' => \Modules\ContentCreator\Models\Project::class],
];

echo "\n--- TEST findAccessible() ---\n";
foreach ($moduleTests as $slug => $info) {
    $projects = \Core\Database::fetchAll(
        "SELECT mp.id, mp.name FROM {$info['table']} mp
         WHERE mp.global_project_id IN (
            SELECT project_id FROM project_members WHERE user_id = ? AND accepted_at IS NOT NULL
         )", [$userId]
    );
    if (empty($projects)) {
        echo "  [{$slug}] Nessun progetto collegato\n";
        continue;
    }
    foreach ($projects as $p) {
        try {
            $result = $info['class']::findAccessible($userId, (int)$p['id']);
            if ($result) {
                echo "  [{$slug}] {$p['name']} (id={$p['id']}): OK (role={$result['access_role']})\n";
            } else {
                echo "  [{$slug}] {$p['name']} (id={$p['id']}): BLOCCATO\n";
            }
        } catch (\Throwable $e) {
            echo "  [{$slug}] {$p['name']} (id={$p['id']}): ERRORE - {$e->getMessage()}\n";
        }
    }
}

// 4. Test listing
echo "\n--- TEST LISTING (getAccessibleModuleProjectIds) ---\n";
foreach ($moduleTests as $slug => $info) {
    $ids = ProjectAccessService::getAccessibleModuleProjectIds($userId, $slug, $info['table']);
    echo "  [{$slug}] IDs visibili: " . (empty($ids) ? 'nessuno' : implode(', ', $ids)) . "\n";
}
