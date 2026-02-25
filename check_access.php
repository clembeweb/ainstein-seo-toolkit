<?php
/**
 * Simula l'accesso di bewebsolution (user_id=4) a tutti i moduli condivisi.
 * Usa le stesse query di ProjectAccessService per verificare l'accesso reale.
 */
require_once __DIR__ . '/core/Bootstrap.php';

use Services\ProjectAccessService;

$userId = 4; // bewebsolution
$user = \Core\Database::fetch("SELECT id, email, name FROM users WHERE id = ?", [$userId]);
echo "=== TEST ACCESSO: {$user['name']} ({$user['email']}) ===\n\n";

// 1. Verifica membership
echo "--- MEMBERSHIPS ---\n";
$members = \Core\Database::fetchAll(
    "SELECT pm.id, pm.project_id, p.name, pm.role, pm.accepted_at
     FROM project_members pm
     JOIN projects p ON p.id = pm.project_id
     WHERE pm.user_id = ?",
    [$userId]
);
foreach ($members as $m) {
    $status = $m['accepted_at'] ? 'ACCETTATO' : 'IN ATTESA';
    echo "  Progetto: {$m['name']} (id={$m['project_id']}) | Ruolo: {$m['role']} | {$status}\n";
}

// 2. Verifica moduli accessibili per ogni progetto
echo "\n--- MODULI ACCESSIBILI ---\n";
foreach ($members as $m) {
    if (!$m['accepted_at']) continue;
    $modules = ProjectAccessService::getAccessibleModules((int)$m['project_id'], $userId);
    echo "  {$m['name']}: " . (empty($modules) ? 'NESSUNO' : implode(', ', $modules)) . "\n";
}

// 3. Test findAccessible() per ogni modulo
$moduleTests = [
    'ads-analyzer' => ['table' => 'ga_projects', 'class' => '\\Modules\\AdsAnalyzer\\Models\\Project'],
    'ai-content' => ['table' => 'aic_projects', 'class' => '\\Modules\\AiContent\\Models\\Project'],
    'seo-audit' => ['table' => 'sa_projects', 'class' => '\\Modules\\SeoAudit\\Models\\Project'],
    'seo-tracking' => ['table' => 'st_projects', 'class' => '\\Modules\\SeoTracking\\Models\\Project'],
    'keyword-research' => ['table' => 'kr_projects', 'class' => '\\Modules\\KeywordResearch\\Models\\Project'],
    'internal-links' => ['table' => 'il_projects', 'class' => '\\Modules\\InternalLinks\\Models\\Project'],
    'content-creator' => ['table' => 'cc_projects', 'class' => '\\Modules\\ContentCreator\\Models\\Project'],
];

echo "\n--- TEST findAccessible() PER MODULO ---\n";
foreach ($moduleTests as $slug => $info) {
    // Get all projects for this module linked to shared global projects
    $projects = \Core\Database::fetchAll(
        "SELECT mp.id, mp.name, mp.global_project_id
         FROM {$info['table']} mp
         WHERE mp.global_project_id IN (
            SELECT project_id FROM project_members WHERE user_id = ? AND accepted_at IS NOT NULL
         )",
        [$userId]
    );

    if (empty($projects)) {
        echo "  [{$slug}] Nessun progetto collegato\n";
        continue;
    }

    foreach ($projects as $p) {
        // Test findAccessible
        $class = $info['class'];
        if (!class_exists($class)) {
            echo "  [{$slug}] Classe {$class} non trovata\n";
            continue;
        }
        try {
            $result = $class::findAccessible($userId, (int)$p['id']);
            $canAccess = $result !== null;
            $role = $result['access_role'] ?? 'N/A';
            echo "  [{$slug}] {$p['name']} (id={$p['id']}): " . ($canAccess ? "OK (role={$role})" : "BLOCCATO") . "\n";
        } catch (\Throwable $e) {
            echo "  [{$slug}] {$p['name']} (id={$p['id']}): ERRORE - {$e->getMessage()}\n";
        }
    }
}

// 4. Test listing (getAllByUser o equivalente)
echo "\n--- TEST LISTING MODULI ---\n";
foreach ($moduleTests as $slug => $info) {
    $ids = ProjectAccessService::getAccessibleModuleProjectIds($userId, $slug, $info['table']);
    echo "  [{$slug}] Progetti visibili in lista: " . (empty($ids) ? 'nessuno' : implode(', ', $ids)) . "\n";
}
