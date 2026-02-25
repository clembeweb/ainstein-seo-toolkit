<?php
require __DIR__ . '/config/database.php';
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$r = $pdo->query("SELECT pm.id, pm.project_id, pm.user_id, pm.role, pm.accepted_at, u.email FROM project_members pm JOIN users u ON u.id = pm.user_id");
while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo implode(" | ", $row) . "\n";
}
