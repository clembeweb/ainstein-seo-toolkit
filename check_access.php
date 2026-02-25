<?php
require_once __DIR__ . '/config/environment.php';
$cfg = require __DIR__ . '/config/database.php';
$pdo = new PDO(
    "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset={$cfg['charset']}",
    $cfg['username'],
    $cfg['password']
);

// Members status
echo "=== PROJECT MEMBERS ===\n";
$r = $pdo->query("SELECT pm.id, pm.project_id, p.name as project_name, pm.user_id, pm.role, pm.accepted_at, u.email
    FROM project_members pm
    JOIN users u ON u.id = pm.user_id
    JOIN projects p ON p.id = pm.project_id
    WHERE pm.user_id = 4");
while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo implode(" | ", array_map(fn($v) => $v ?? 'NULL', $row)) . "\n";
}

// Module access
echo "\n=== MODULE ACCESS ===\n";
$r = $pdo->query("SELECT pmm.member_id, pmm.module_slug, pm.project_id, p.name as project_name, pm.accepted_at
    FROM project_member_modules pmm
    JOIN project_members pm ON pm.id = pmm.member_id
    JOIN projects p ON p.id = pm.project_id
    WHERE pm.user_id = 4");
while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo implode(" | ", array_map(fn($v) => $v ?? 'NULL', $row)) . "\n";
}

// Module projects linked to global projects
echo "\n=== ADS-ANALYZER PROJECTS FOR BEWEB ===\n";
$r = $pdo->query("SELECT ga.id, ga.name, ga.global_project_id, ga.user_id, ga.type
    FROM ga_projects ga
    WHERE ga.global_project_id IN (SELECT project_id FROM project_members WHERE user_id = 4 AND accepted_at IS NOT NULL)");
while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo implode(" | ", array_map(fn($v) => $v ?? 'NULL', $row)) . "\n";
}

// Other module projects
echo "\n=== ALL MODULE PROJECTS SHARED WITH BEWEB ===\n";
foreach (['aic_projects', 'sa_projects', 'st_projects', 'kr_projects', 'il_projects', 'cc_projects'] as $table) {
    $r = $pdo->query("SELECT '$table' as tbl, mp.id, mp.name, mp.global_project_id
        FROM $table mp
        WHERE mp.global_project_id IN (SELECT project_id FROM project_members WHERE user_id = 4 AND accepted_at IS NOT NULL)");
    while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
        echo implode(" | ", array_map(fn($v) => $v ?? 'NULL', $row)) . "\n";
    }
}
