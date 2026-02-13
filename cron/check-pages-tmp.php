<?php
require_once __DIR__ . '/bootstrap.php';
use Core\Database;

$rows = Database::fetchAll(
    'SELECT project_id, COUNT(*) as cnt, MIN(crawled_at) as oldest, MAX(crawled_at) as newest, SUM(CASE WHEN html_content IS NOT NULL THEN 1 ELSE 0 END) as with_html FROM sa_pages GROUP BY project_id'
);

foreach ($rows as $r) {
    echo "Progetto {$r['project_id']}: {$r['cnt']} pagine, oldest={$r['oldest']}, newest={$r['newest']}, con_html={$r['with_html']}\n";
}

$cutoff = date('Y-m-d', strtotime('-90 days'));
echo "\n90 giorni fa = {$cutoff}\n";

$old = Database::fetch('SELECT COUNT(*) as cnt FROM sa_pages WHERE html_content IS NOT NULL AND crawled_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');
echo "Pagine con html_content > 90gg: {$old['cnt']}\n";

$all = Database::fetch('SELECT COUNT(*) as cnt FROM sa_pages WHERE html_content IS NOT NULL');
echo "Pagine con html_content totali: {$all['cnt']}\n";
