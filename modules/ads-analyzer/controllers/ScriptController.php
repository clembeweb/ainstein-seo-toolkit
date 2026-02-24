<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\ModuleLoader;
use Core\Router;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\ScriptRun;
use Modules\AdsAnalyzer\Services\ScriptGeneratorService;

class ScriptController
{
    /**
     * Pagina setup script Google Ads
     */
    public function setup(int $projectId): string
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        // Genera token se non esiste
        if (empty($project['api_token'])) {
            $token = Project::generateToken($projectId);
            $project['api_token'] = $token;
            $project['api_token_created_at'] = date('Y-m-d H:i:s');
        }

        // Config script
        $config = Project::getScriptConfig($projectId);

        // Genera endpoint URL
        $endpointUrl = $this->getEndpointUrl();

        // Genera script
        $script = ScriptGeneratorService::generate(
            $project['api_token'],
            $endpointUrl,
            $config
        );

        // Storico esecuzioni recenti
        $recentRuns = ScriptRun::getRecentByProject($projectId, 10);

        return View::render('ads-analyzer/script/setup', [
            'title' => 'Google Ads Script - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'config' => $config,
            'script' => $script,
            'recentRuns' => $recentRuns,
            'endpointUrl' => $endpointUrl,
        ]);
    }

    /**
     * Rigenera token API (invalida il vecchio)
     */
    public function regenerateToken(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
        }

        $newToken = Project::generateToken($projectId);

        jsonResponse([
            'success' => true,
            'token' => $newToken,
            'message' => 'Token rigenerato. Aggiorna lo script in Google Ads.',
        ]);
    }

    /**
     * Aggiorna configurazione script
     */
    public function updateConfig(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
        }

        $config = [
            'enable_search_terms' => isset($_POST['enable_search_terms']),
            'enable_campaign_performance' => isset($_POST['enable_campaign_performance']),
            'date_range' => $_POST['date_range'] ?? 'LAST_30_DAYS',
            'campaign_filter' => trim($_POST['campaign_filter'] ?? ''),
        ];

        // Almeno uno deve essere attivo
        if (!$config['enable_search_terms'] && !$config['enable_campaign_performance']) {
            jsonResponse(['error' => 'Almeno uno strumento deve essere attivo'], 400);
        }

        // Valida date_range
        $validRanges = ['LAST_7_DAYS', 'LAST_14_DAYS', 'LAST_30_DAYS', 'LAST_90_DAYS', 'ALL_TIME'];
        if (!in_array($config['date_range'], $validRanges)) {
            $config['date_range'] = 'LAST_30_DAYS';
        }

        Project::updateScriptConfig($projectId, $config);

        $_SESSION['_flash']['success'] = 'Configurazione aggiornata. Rigenera lo script per applicare le modifiche.';
        Router::redirect("/ads-analyzer/projects/{$projectId}/script");
    }

    /**
     * Storico esecuzioni script
     */
    public function runs(int $projectId): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $runs = ScriptRun::getByProject($projectId, 50);

        return View::render('ads-analyzer/script/runs', [
            'title' => 'Esecuzioni Script - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'runs' => $runs,
        ]);
    }

    /**
     * Costruisce l'URL endpoint assoluto per l'API
     */
    private function getEndpointUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        if (str_ends_with($basePath, '/public')) {
            $basePath = substr($basePath, 0, -7);
        }

        return "{$scheme}://{$host}{$basePath}/api/v1/ads-analyzer/ingest";
    }
}
