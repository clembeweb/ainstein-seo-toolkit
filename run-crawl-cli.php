<?php
/**
 * Lancia crawl completo via CLI (discovery + crawl + analisi)
 * Bypassa il proxy SiteGround che uccide i processi lunghi
 *
 * Uso: php run-crawl-cli.php <project_id> <user_id>
 * Es:  php run-crawl-cli.php 13 6
 *
 * ELIMINARE DOPO L'USO!
 */
if (php_sapi_name() !== 'cli') die('Solo CLI');

set_time_limit(0);
ignore_user_abort(true);

$projectId = (int) ($argv[1] ?? 0);
$userId = (int) ($argv[2] ?? 0);

if (!$projectId || !$userId) {
    echo "Uso: php run-crawl-cli.php <project_id> <user_id>\n";
    exit(1);
}

require_once __DIR__ . '/cron/bootstrap.php';

use Core\Database;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\CrawlSession;
use Modules\SeoAudit\Services\CrawlerService;
use Modules\SeoAudit\Services\RobotsTxtParser;
use Modules\SeoAudit\Services\SitemapParser;
use Modules\SeoAudit\Services\IssueDetector;
use Modules\SeoAudit\Services\BudgetIssueDetector;
use Modules\SeoAudit\Services\AuditAnalyzer;

$projectModel = new Project();
$sessionModel = new CrawlSession();
$pageModel = new Page();

$project = $projectModel->find($projectId);
if (!$project) {
    echo "Progetto $projectId non trovato!\n";
    exit(1);
}

echo "=== Crawl completo per: {$project['name']} ({$project['base_url']}) ===\n";
$startTime = microtime(true);

// Configurazione
$config = json_decode($project['crawl_config'] ?: '{}', true);
$config['user_agent'] = $config['user_agent'] ?? 'chrome';
$config['crawl_mode'] = 'spider';
$config['max_pages'] = $config['max_pages'] ?? 500;
$maxPages = $config['max_pages'];

echo "Config: max_pages={$maxPages}, user_agent={$config['user_agent']}\n";

// Pulisci dati precedenti
echo "\n--- Pulizia dati precedenti ---\n";
Database::delete('sa_pages', 'project_id = ?', [$projectId]);
Database::delete('sa_issues', 'project_id = ?', [$projectId]);
Database::execute("UPDATE sa_site_config SET discovered_urls = NULL WHERE project_id = ?", [$projectId]);

// Crea nuova sessione
$sessionId = $sessionModel->create($projectId, $config);
$sessionModel->start($sessionId);

$projectModel->update($projectId, [
    'current_session_id' => $sessionId,
    'crawl_config' => json_encode($config),
    'max_pages' => $maxPages,
    'status' => 'crawling',
    'pages_crawled' => 0,
    'pages_found' => 0,
]);

echo "Sessione creata: $sessionId\n";

// Init crawler
$crawler = new CrawlerService();
$crawler->init($projectId, $userId);
$crawler->setSessionId($sessionId);
$crawler->setConfig($config);

// Fetch robots.txt + sitemap
$baseUrl = rtrim($project['base_url'], '/');
if (!str_starts_with($baseUrl, 'http')) {
    $baseUrl = 'https://' . $baseUrl;
}

echo "\n--- Fetch robots.txt ---\n";
$robotsContent = @file_get_contents($baseUrl . '/robots.txt');
$robotsRules = [];
$sitemapUrls = [];
$crawlDelay = null;

if ($robotsContent) {
    $robotsParser = new RobotsTxtParser();
    $robotsRules = $robotsParser->parse($robotsContent);
    $crawlDelay = $robotsParser->extractCrawlDelay($robotsContent);
    echo "robots.txt trovato, crawl_delay: " . ($crawlDelay ?? 'nessuno') . "\n";
} else {
    echo "robots.txt non trovato\n";
}

echo "--- Parsing sitemap ---\n";
$sitemapParser = new SitemapParser();
$sitemapResult = $sitemapParser->discoverAndParse($baseUrl, $robotsContent ?: null);
$sitemapUrls = $sitemapResult['urls'] ?? [];
echo "URL da sitemap: " . count($sitemapUrls) . "\n";

// Save budget data
Database::execute(
    "UPDATE sa_site_config SET robots_rules = ?, crawl_delay = ? WHERE project_id = ?",
    [!empty($robotsRules) ? json_encode($robotsRules) : null, $crawlDelay, $projectId]
);

$crawler->setSitemapUrls($sitemapUrls);
$crawler->setRobotsRules($robotsRules);

// FASE 1: Discovery URL
echo "\n=== FASE 1: Discovery URL ===\n";
$urls = $crawler->discoverUrls();
Database::reconnect();

if (empty($urls)) {
    echo "ERRORE: Nessun URL trovato!\n";
    $sessionModel->fail($sessionId, 'Nessun URL trovato');
    $projectModel->update($projectId, ['status' => 'failed']);
    exit(1);
}

echo "URL trovati: " . count($urls) . "\n";

// Aggiorna sessione e progetto
$sessionModel->setPagesFound($sessionId, count($urls));
$projectModel->update($projectId, ['pages_found' => count($urls)]);

// Inserisci URL in sa_pages
$pendingCount = 0;
foreach ($urls as $url) {
    Database::insert('sa_pages', [
        'project_id' => $projectId,
        'session_id' => $sessionId,
        'url' => $url,
        'status' => 'pending',
    ]);
    $pendingCount++;
}
echo "Pagine inserite: $pendingCount\n";

// FASE 2: Crawl pagine
echo "\n=== FASE 2: Crawl pagine ===\n";
$crawled = 0;
$errors = 0;
$totalIssues = 0;

$issueDetector = new IssueDetector();
$issueDetector->init($projectId);
$budgetDetector = new BudgetIssueDetector();
$budgetDetector->init($projectId);
$auditAnalyzer = new AuditAnalyzer();

$pendingPages = Database::fetchAll(
    "SELECT id, url FROM sa_pages WHERE project_id = ? AND session_id = ? AND status = 'pending' ORDER BY id",
    [$projectId, $sessionId]
);

$total = count($pendingPages);
echo "Pagine da crawlare: $total\n";

foreach ($pendingPages as $page) {
    try {
        $pageData = $crawler->crawlPage($page['url']);

        if ($pageData && empty($pageData['error'])) {
            // upsert filtra automaticamente le colonne invalide (VALID_COLUMNS)
            $pageModel->upsert($projectId, $page['url'], array_merge($pageData, [
                'session_id' => $sessionId,
            ]));

            // Analizza issues
            try {
                $ic = $issueDetector->analyzeAndSave($pageData, $page['id']);
                $bc = $budgetDetector->analyzeAndSave($pageData, $page['id']);
                $totalIssues += $ic + $bc;
            } catch (\Exception $e) {
                // Non bloccare il crawl per errori di analisi
            }

            $crawled++;
        } else {
            Database::execute(
                "UPDATE sa_pages SET status = 'error', crawled_at = NOW() WHERE id = ?",
                [$page['id']]
            );
            $errors++;
        }

        // Aggiorna contatore ogni 10 pagine
        if (($crawled + $errors) % 10 === 0) {
            Database::reconnect();
            $projectModel->update($projectId, ['pages_crawled' => $crawled]);
            $sessionModel->update($sessionId, ['pages_crawled' => $crawled]);
            echo "  Progresso: $crawled crawled, $errors errori / $total totali\n";
        }
    } catch (\Exception $e) {
        Database::execute(
            "UPDATE sa_pages SET status = 'error', crawled_at = NOW() WHERE id = ?",
            [$page['id']]
        );
        $errors++;
        echo "  Errore: {$page['url']} - {$e->getMessage()}\n";
    }
}

Database::reconnect();

// Calcola health score
echo "\n--- Calcolo Health Score ---\n";
try {
    $healthScore = $auditAnalyzer->calculateHealthScore($projectId);
} catch (\Exception $e) {
    $healthScore = 0;
    echo "Errore health score: {$e->getMessage()}\n";
}

// Finalizza
$projectModel->update($projectId, [
    'status' => 'completed',
    'pages_crawled' => $crawled,
    'health_score' => $healthScore,
]);
$sessionModel->update($sessionId, [
    'status' => 'completed',
    'pages_crawled' => $crawled,
    'health_score' => $healthScore,
    'completed_at' => date('Y-m-d H:i:s'),
]);

$elapsed = round(microtime(true) - $startTime, 1);
echo "\n=== COMPLETATO ===\n";
echo "Pagine crawlate: $crawled/$total\n";
echo "Errori: $errors\n";
echo "Issues trovate: $totalIssues\n";
echo "Health Score: $healthScore\n";
echo "Tempo totale: {$elapsed}s\n";
