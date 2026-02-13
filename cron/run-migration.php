<?php
/**
 * Script temporaneo per eseguire migration kr_keyword_cache
 * Da eliminare dopo l'uso
 */
require_once __DIR__ . '/bootstrap.php';

use Core\Database;

echo "Esecuzione migration 001_keyword_cache.sql...\n";

$sqlFile = dirname(__DIR__) . '/modules/keyword-research/database/migrations/001_keyword_cache.sql';

if (!file_exists($sqlFile)) {
    echo "ERRORE: File migration non trovato: {$sqlFile}\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);

try {
    Database::query($sql);
    echo "Migration eseguita con successo!\n";
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
    exit(1);
}

// Verifica
try {
    $check = Database::fetch("SHOW TABLES LIKE 'kr_keyword_cache'");
    echo "Verifica tabella: " . ($check ? "ESISTE" : "NON TROVATA") . "\n";

    $cols = Database::fetchAll("SHOW COLUMNS FROM kr_keyword_cache");
    echo "Colonne: " . count($cols) . "\n";
    foreach ($cols as $c) {
        echo "  - {$c['Field']} ({$c['Type']})\n";
    }
} catch (\Exception $e) {
    echo "Errore verifica: " . $e->getMessage() . "\n";
}
