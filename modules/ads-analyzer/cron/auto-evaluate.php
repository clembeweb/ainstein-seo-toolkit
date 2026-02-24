<?php
/**
 * Auto-Evaluation Cron - Google Ads Analyzer
 *
 * Processa la coda di auto-valutazioni AI per i progetti campagna.
 * Cron: ogni 5 minuti
 * Esempio crontab:
 * 0/5 * * * * php /path/to/seo-toolkit/modules/ads-analyzer/cron/auto-evaluate.php
 */

// Solo CLI
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}

// Bootstrap CLI
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

use Core\Database;
use Core\Credits;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\AutoEvalQueue;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\Campaign;
use Modules\AdsAnalyzer\Models\Ad;
use Modules\AdsAnalyzer\Models\Extension;
use Modules\AdsAnalyzer\Models\CampaignAdGroup;
use Modules\AdsAnalyzer\Models\AdGroupKeyword;
use Modules\AdsAnalyzer\Models\CampaignEvaluation;
use Modules\AdsAnalyzer\Models\ScriptRun;
use Modules\AdsAnalyzer\Services\CampaignEvaluatorService;
use Modules\AdsAnalyzer\Services\MetricComparisonService;

// Configurazione
$maxItemsPerRun = 3;
$logFile = BASE_PATH . '/storage/logs/auto-eval.log';

set_time_limit(0);

function logMessage(string $message): void
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] {$message}\n";
    echo $line;

    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, $line, FILE_APPEND);
}

try {
    logMessage("=== Inizio auto-evaluation ===");

    // Reset items bloccati
    $reset = AutoEvalQueue::resetStuckProcessing(30);
    if ($reset > 0) {
        logMessage("Reset {$reset} items bloccati in processing");
    }

    $processed = 0;

    while ($processed < $maxItemsPerRun) {
        $item = AutoEvalQueue::getNextPending();
        if (!$item) {
            break;
        }

        $queueId = $item['id'];
        $projectId = $item['project_id'];
        $runId = $item['run_id'];

        logMessage("Processing queue #{$queueId}: project={$projectId}, run={$runId}");

        try {
            // Verifica progetto
            $project = Project::find($projectId);
            if (!$project) {
                AutoEvalQueue::markSkipped($queueId, 'project_not_found');
                logMessage("  SKIP: progetto non trovato");
                $processed++;
                continue;
            }

            // Verifica auto-eval ancora attiva
            if (!($project['auto_evaluate'] ?? false)) {
                AutoEvalQueue::markSkipped($queueId, 'auto_eval_disabled');
                logMessage("  SKIP: auto-eval disabilitata");
                $processed++;
                continue;
            }

            $userId = $project['user_id'];

            // Verifica crediti
            $cost = Credits::getCost('campaign_evaluation', 'ads-analyzer', 7);
            if (!Credits::hasEnough($userId, $cost)) {
                AutoEvalQueue::markSkipped($queueId, 'no_credits');
                logMessage("  SKIP: crediti insufficienti (necessari: {$cost})");
                $processed++;
                continue;
            }

            // Carica dati del run
            $campaigns = Campaign::getByRun($runId);
            if (empty($campaigns)) {
                AutoEvalQueue::markSkipped($queueId, 'no_campaigns');
                logMessage("  SKIP: nessuna campagna nel run");
                $processed++;
                continue;
            }

            // Trova run precedente per confronto
            $runs = ScriptRun::getByProject($projectId, 10);
            $campaignRuns = array_values(array_filter($runs, fn($r) =>
                in_array($r['run_type'], ['campaign_performance', 'both']) && $r['status'] === 'completed' && $r['id'] != $runId
            ));
            $previousRun = !empty($campaignRuns) ? $campaignRuns[0] : null;

            // Confronta metriche
            $metricDeltas = null;
            $alerts = null;
            $previousEvalSummary = null;
            $previousEvalId = null;
            $isSignificant = true; // default: significativo se non c'e run precedente

            if ($previousRun) {
                $significanceThreshold = (float)ModuleLoader::getSetting('ads-analyzer', 'auto_eval_significance_threshold', 10) / 100;
                $comparison = MetricComparisonService::compareRuns($runId, $previousRun['id'], $significanceThreshold);
                $metricDeltas = $comparison['deltas'];
                $alerts = $comparison['alerts'];
                $isSignificant = $comparison['is_significant'];

                // Carica valutazione precedente per contesto
                $prevEval = CampaignEvaluation::getLatestByProject($projectId);
                if ($prevEval) {
                    $previousEvalId = $prevEval['id'];
                    $prevAi = json_decode($prevEval['ai_response'] ?? '{}', true);
                    $previousEvalSummary = [
                        'score' => $prevAi['overall_score'] ?? null,
                        'summary' => $prevAi['summary'] ?? null,
                        'top_recommendations' => $prevAi['top_recommendations'] ?? [],
                    ];
                }
            }

            // Se non significativo, skip (risparmia crediti)
            if (!$isSignificant && !empty($campaignRuns)) {
                // Salva comunque un record eval di tipo "no_change" con i delta
                $evalId = CampaignEvaluation::create([
                    'project_id' => $projectId,
                    'user_id' => $userId,
                    'run_id' => $runId,
                    'eval_type' => 'auto',
                    'previous_eval_id' => $previousEvalId,
                    'name' => 'Auto-check ' . date('d/m/Y H:i') . ' (nessun cambiamento)',
                    'campaigns_evaluated' => count($campaigns),
                    'status' => 'completed',
                ]);
                CampaignEvaluation::update($evalId, [
                    'metric_deltas' => json_encode($metricDeltas, JSON_UNESCAPED_UNICODE),
                    'credits_used' => 0,
                ]);
                CampaignEvaluation::updateStatus($evalId, 'completed');

                AutoEvalQueue::markSkipped($queueId, 'no_significant_change');
                logMessage("  SKIP: nessun cambiamento significativo (0 crediti)");
                $processed++;
                continue;
            }

            // Carica tutti i dati
            $ads = Ad::getByRun($runId);
            $extensions = Extension::getByRun($runId);
            $adGroupsData = CampaignAdGroup::getByRun($runId);
            $keywordsData = AdGroupKeyword::getByRun($runId);

            // Crea record valutazione
            $evalId = CampaignEvaluation::create([
                'project_id' => $projectId,
                'user_id' => $userId,
                'run_id' => $runId,
                'eval_type' => 'auto',
                'previous_eval_id' => $previousEvalId,
                'name' => 'Auto-valutazione ' . date('d/m/Y H:i'),
                'campaigns_evaluated' => count($campaigns),
                'ads_evaluated' => count($ads),
                'ad_groups_evaluated' => count($adGroupsData),
                'keywords_evaluated' => count($keywordsData),
                'status' => 'analyzing',
            ]);

            logMessage("  Evaluation #{$evalId} creata, avvio AI...");

            // Scraping landing pages (max 5 URL uniche dagli annunci)
            $landingContexts = [];
            $uniqueUrls = [];
            foreach ($ads as $ad) {
                $url = $ad['final_url'] ?? '';
                if (!empty($url) && !isset($uniqueUrls[$url]) && count($uniqueUrls) < 5) {
                    $uniqueUrls[$url] = true;
                }
            }

            if (!empty($uniqueUrls)) {
                require_once dirname(__DIR__, 3) . '/services/ScraperService.php';
                $scraper = new \Services\ScraperService();
                foreach (array_keys($uniqueUrls) as $url) {
                    try {
                        $scraped = $scraper->scrape($url);
                        Database::reconnect();
                        if (!empty($scraped['success']) && !empty($scraped['content'])) {
                            $content = mb_substr($scraped['content'], 0, 3000);
                            $landingContexts[$url] = "Titolo: " . ($scraped['title'] ?? 'N/D')
                                . "\nWord count: " . ($scraped['word_count'] ?? 0)
                                . "\nContenuto: " . $content;
                        }
                    } catch (\Exception $e) {
                        logMessage("  WARNING: landing scrape failed for {$url}: " . $e->getMessage());
                    }
                }
            }

            $landingPagesAnalyzed = count($landingContexts);
            logMessage("  Landing pages scraped: {$landingPagesAnalyzed}/" . count($uniqueUrls));

            // Chiamata AI con contesto storico
            set_time_limit(0);
            $evaluator = new CampaignEvaluatorService();
            $aiResult = $evaluator->evaluateWithContext(
                $userId,
                $campaigns,
                $ads,
                $extensions,
                $landingContexts,
                $adGroupsData,
                $keywordsData,
                $previousEvalSummary,
                $metricDeltas,
                $alerts
            );

            Database::reconnect();

            // Salva risultato
            CampaignEvaluation::update($evalId, [
                'ai_response' => json_encode($aiResult, JSON_UNESCAPED_UNICODE),
                'metric_deltas' => $metricDeltas ? json_encode($metricDeltas, JSON_UNESCAPED_UNICODE) : null,
                'credits_used' => $cost,
                'landing_pages_analyzed' => $landingPagesAnalyzed,
            ]);
            CampaignEvaluation::updateStatus($evalId, 'completed');

            // Consuma crediti
            Credits::consume($userId, $cost, 'campaign_evaluation', 'ads-analyzer', [
                'run_id' => $runId,
                'eval_type' => 'auto',
                'campaigns' => count($campaigns),
            ]);

            Database::reconnect();

            AutoEvalQueue::updateStatus($queueId, 'completed');
            $score = $aiResult['overall_score'] ?? 'N/D';
            $trend = $aiResult['trend'] ?? 'N/D';
            logMessage("  COMPLETATA: eval #{$evalId}, score={$score}, trend={$trend}, costo={$cost} crediti");

        } catch (\Exception $e) {
            Database::reconnect();
            logMessage("  ERRORE: " . $e->getMessage());

            AutoEvalQueue::updateStatus($queueId, 'error', $e->getMessage());

            if (isset($evalId)) {
                CampaignEvaluation::updateStatus($evalId, 'error', $e->getMessage());
            }
        }

        $processed++;

        // Pausa tra items
        if ($processed < $maxItemsPerRun) {
            sleep(2);
        }
    }

    logMessage("Processati: {$processed} items");
    logMessage("=== Fine auto-evaluation ===");
    logMessage("");

} catch (\Exception $e) {
    logMessage("ERRORE FATALE: " . $e->getMessage());
    exit(1);
}

exit(0);
