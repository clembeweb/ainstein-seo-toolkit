<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\Database;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\ScriptRun;
use Modules\AdsAnalyzer\Models\AutoEvalQueue;
use Modules\AdsAnalyzer\Services\IngestService;

/**
 * Controller per l'API pubblica (nessuna auth sessione, nessun CSRF).
 * Autenticazione tramite token per-progetto.
 */
class ApiController
{
    private const MAX_PAYLOAD_SIZE = 5 * 1024 * 1024; // 5MB
    private const RATE_LIMIT_PER_HOUR = 60;

    /**
     * POST /api/v1/ads-analyzer/ingest
     * Riceve dati dal Google Ads Script
     */
    public function ingest(): void
    {
        // CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Content-Type: application/json');

        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        try {
            // Leggi body
            $rawBody = file_get_contents('php://input');

            if (strlen($rawBody) > self::MAX_PAYLOAD_SIZE) {
                $this->error('Payload troppo grande (max 5MB)', 'PAYLOAD_TOO_LARGE', 413);
            }

            $payload = json_decode($rawBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('JSON non valido: ' . json_last_error_msg(), 'INVALID_JSON', 400);
            }

            // Valida token
            $token = $payload['token'] ?? '';
            if (empty($token) || strlen($token) !== 64) {
                $this->error('Token non valido', 'INVALID_TOKEN', 401);
            }

            $project = Project::findByToken($token);
            if (!$project) {
                $this->error('Token non valido', 'INVALID_TOKEN', 401);
            }

            $projectId = $project['id'];

            // Rate limit
            $recentRuns = ScriptRun::countRecentByProject($projectId, 1);
            if ($recentRuns >= self::RATE_LIMIT_PER_HOUR) {
                $this->error('Rate limit superato (max ' . self::RATE_LIMIT_PER_HOUR . '/ora)', 'RATE_LIMITED', 429);
            }

            // Determina tipo di dati
            $type = $payload['type'] ?? 'both';
            $validTypes = ['search_terms', 'campaign_performance', 'both'];
            if (!in_array($type, $validTypes)) {
                $this->error('Tipo non valido. Valori: ' . implode(', ', $validTypes), 'INVALID_TYPE', 400);
            }

            // Verifica che ci siano dati da processare
            $hasSearchTerms = !empty($payload['search_terms']);
            $hasCampaigns = !empty($payload['campaigns']) || !empty($payload['ads']) || !empty($payload['extensions']) || !empty($payload['ad_groups']) || !empty($payload['keywords']);

            if (!$hasSearchTerms && !$hasCampaigns) {
                $this->error('Nessun dato da elaborare. Invia almeno search_terms o campaigns/ads/extensions.', 'NO_DATA', 400);
            }

            // Crea record run
            $runId = ScriptRun::create([
                'project_id' => $projectId,
                'run_type' => $type,
                'status' => 'processing',
                'script_version' => $payload['script_version'] ?? null,
                'date_range_start' => $payload['date_range']['start'] ?? null,
                'date_range_end' => $payload['date_range']['end'] ?? null,
            ]);

            $totalItems = 0;

            // Elabora search terms
            if (in_array($type, ['search_terms', 'both'])) {
                $searchTerms = $payload['search_terms'] ?? [];
                if (!empty($searchTerms)) {
                    $result = IngestService::processSearchTerms($projectId, $searchTerms, $runId);
                    $totalItems += $result['terms'];
                }
            }

            // Elabora dati campagne
            if (in_array($type, ['campaign_performance', 'both'])) {
                $result = IngestService::processCampaignData($projectId, $runId, $payload);
                $totalItems += $result['campaigns'] + $result['ads'] + ($result['ad_groups'] ?? 0) + ($result['keywords'] ?? 0) + $result['extensions'];
            }

            // Aggiorna run
            ScriptRun::update($runId, [
                'status' => 'completed',
                'items_received' => $totalItems,
            ]);

            // Auto-evaluation: accoda valutazione AI se abilitata
            if (in_array($type, ['campaign_performance', 'both'])) {
                $this->queueAutoEvaluation($projectId, $runId);
            }

            echo json_encode([
                'success' => true,
                'run_id' => $runId,
                'items_processed' => $totalItems,
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("API Ingest Error: " . $e->getMessage());

            // Aggiorna run con errore se esiste
            if (isset($runId)) {
                ScriptRun::updateStatus($runId, 'error', $e->getMessage());
            }

            $this->error('Errore interno: ' . $e->getMessage(), 'INTERNAL_ERROR', 500);
        }
    }

    /**
     * Accoda auto-valutazione AI se il progetto ha l'opzione attiva
     */
    private function queueAutoEvaluation(int $projectId, int $runId): void
    {
        try {
            $project = Project::find($projectId);
            if (!$project || !($project['auto_evaluate'] ?? false)) {
                return;
            }

            // Delay configurabile (default 2 minuti)
            $delayMinutes = (int)\Core\ModuleLoader::getSetting('ads-analyzer', 'auto_eval_delay_minutes', 2);
            AutoEvalQueue::create([
                'project_id' => $projectId,
                'run_id' => $runId,
                'scheduled_for' => date('Y-m-d H:i:s', strtotime("+{$delayMinutes} minutes")),
            ]);
        } catch (\Exception $e) {
            error_log("Auto-eval queue error: " . $e->getMessage());
        }
    }

    /**
     * Invia risposta di errore JSON ed esce
     */
    private function error(string $message, string $code, int $httpCode): void
    {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code,
        ]);
        exit;
    }
}
