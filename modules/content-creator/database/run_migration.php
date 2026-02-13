<?php
/**
 * Migration runner - eseguire una sola volta
 * php modules/content-creator/database/run_migration.php
 */

require __DIR__ . '/../../../config/database.php';

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$statements = [
    // 1. cc_urls: rimuovere campi meta, aggiungere campi contenuto
    "ALTER TABLE cc_urls DROP COLUMN ai_meta_title",
    "ALTER TABLE cc_urls DROP COLUMN ai_meta_description",
    "ALTER TABLE cc_urls CHANGE COLUMN ai_page_description ai_content LONGTEXT DEFAULT NULL",
    "ALTER TABLE cc_urls ADD COLUMN ai_h1 VARCHAR(500) DEFAULT NULL AFTER ai_content",
    "ALTER TABLE cc_urls ADD COLUMN ai_word_count INT DEFAULT 0 AFTER ai_h1",
    "ALTER TABLE cc_urls ADD COLUMN secondary_keywords JSON DEFAULT NULL AFTER keyword",
    "ALTER TABLE cc_urls ADD COLUMN intent VARCHAR(50) DEFAULT NULL AFTER secondary_keywords",
    "ALTER TABLE cc_urls ADD COLUMN source_type ENUM('manual','csv','sitemap','cms','keyword_research') DEFAULT 'manual' AFTER intent",

    // 1b. cc_projects: aggiungere 'service' al content_type ENUM
    "ALTER TABLE cc_projects MODIFY COLUMN content_type ENUM('product','category','article','service','custom') NOT NULL DEFAULT 'product'",

    // 2. cc_connectors: supporto plugin CMS
    "ALTER TABLE cc_connectors ADD COLUMN api_key VARCHAR(100) DEFAULT NULL AFTER config",
    "ALTER TABLE cc_connectors ADD COLUMN categories_cache JSON DEFAULT NULL AFTER last_sync_at",
    "ALTER TABLE cc_connectors ADD COLUMN seo_plugin VARCHAR(50) DEFAULT NULL AFTER categories_cache",
];

echo "Running Content Creator pivot migration...\n";

foreach ($statements as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 70) . "...\n";
    } catch (PDOException $e) {
        // Skip if column already exists/doesn't exist
        if (str_contains($e->getMessage(), 'Duplicate column') || str_contains($e->getMessage(), 'check that column/key exists')) {
            echo "SKIP (already done): " . substr($sql, 0, 60) . "...\n";
        } else {
            echo "ERR: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nMigration completed!\n";
