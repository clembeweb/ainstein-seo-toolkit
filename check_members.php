<?php
require_once __DIR__ . '/config/environment.php';
$cfg = require __DIR__ . '/config/database.php';
$pdo = new PDO(
    "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset={$cfg['charset']}",
    $cfg['username'],
    $cfg['password']
);
$r = $pdo->query("SELECT pm.id, pm.project_id, pm.user_id, pm.role, pm.accepted_at, u.email FROM project_members pm JOIN users u ON u.id = pm.user_id");
while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo implode(" | ", array_map(fn($v) => $v ?? 'NULL', $row)) . "\n";
}
