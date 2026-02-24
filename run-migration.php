<?php
/**
 * Temporary migration runner - DELETE after use
 */
require_once __DIR__ . '/config/environment.php';

$config = require __DIR__ . '/config/database.php';

$dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'] . ';charset=' . ($config['charset'] ?? 'utf8mb4');
$pdo = new PDO($dsn, $config['username'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$files = [
    'database/migrations/2026_02_24_project_sharing.sql',
    'database/migrations/2026_02_24_notifications.sql',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "SKIP: {$file} not found\n";
        continue;
    }
    $sql = file_get_contents($file);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }
    echo "OK: " . basename($file) . "\n";
}

echo "All migrations done.\n";
