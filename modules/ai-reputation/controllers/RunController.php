<?php

namespace Modules\AiReputation\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Credits;
use Core\Database;
use Core\ModuleLoader;
use Modules\AiReputation\Models\Project;
use Modules\AiReputation\Models\Prompt;
use Modules\AiReputation\Models\Run;
use Modules\AiReputation\Services\EngineCollectorService;

/**
 * RunController - avvio run (job), stream SSE del collector, stato, annullamento, report.
 * Pattern: modules/seo-tracking/controllers/RankCheckController.php
 */
class RunController
{
    private Project $project;
    private Prompt $prompt;
    private Run $run;

    public function __construct()
    {
        $this->project = new Project();
        $this->prompt = new Prompt();
        $this->run = new Run();
    }

    /**
     * POST /ai-reputation/project/{id}/runs/start (JSON)
     */
    public function start(int $projectId): void
    {
        header('Content-Type: application/json');
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            echo json_encode(['success' => false, 'error' => 'Non hai i permessi per questa operazione']);
            exit;
        }

        $prompts = $this->prompt->allByProject($projectId, true);
        if (empty($prompts)) {
            echo json_encode(['success' => false, 'error' => 'Nessuna domanda attiva: aggiungine almeno una']);
            exit;
        }
        $maxPrompts = (int) ModuleLoader::getSetting(Project::SLUG, 'max_prompts_per_run', 60);
        if (count($prompts) > $maxPrompts) {
            echo json_encode(['success' => false, 'error' => "Troppe domande attive (max {$maxPrompts})"]);
            exit;
        }

        $engines = array_values(array_filter($project['engines'], fn($e) => EngineCollectorService::isConfigured($e)));
        $missing = array_diff($project['engines'], $engines);
        if (empty($engines)) {
            echo json_encode(['success' => false, 'error' => 'Nessun engine configurato: mancano le API key in Impostazioni globali']);
            exit;
        }

        if ($this->run->getActiveForProject($projectId)) {
            echo json_encode(['success' => false, 'error' => 'C\'è già un run in corso per questo progetto']);
            exit;
        }

        $repeats = max(1, (int) ($project['repeats'] ?? 1));
        $total = count($prompts) * count($engines) * $repeats;
        $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);
        $unitCost = Credits::getCost('collect_response', Project::SLUG, 0.2);
        if (!Credits::hasEnough($creditUserId, $total * $unitCost)) {
            echo json_encode(['success' => false, 'error' => 'Crediti insufficienti. Necessari: ' . ($total * $unitCost) . ', disponibili: ' . Credits::getBalance($creditUserId)]);
            exit;
        }

        $runId = $this->run->create($projectId, $user['id'], $prompts, $engines, $repeats);

        echo json_encode([
            'success' => true,
            'run_id' => $runId,
            'responses_total' => $total,
            'engines' => $engines,
            'engines_missing' => array_values($missing),
            'estimated_credits' => $total * $unitCost,
        ]);
        exit;
    }

    /**
     * GET /ai-reputation/project/{id}/runs/stream?run_id=X (SSE)
     */
    public function stream(int $projectId): void
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $user = Auth::user();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            exit('Unauthorized');
        }
        $project = $this->project->findAccessible($projectId, $user['id']);
        if (!$project) {
            header('HTTP/1.1 404 Not Found');
            exit('Progetto non trovato');
        }
        $runId = (int) ($_GET['run_id'] ?? 0);
        $run = $runId ? $this->run->find($runId, $projectId) : null;
        if (!$run) {
            header('HTTP/1.1 404 Not Found');
            exit('Run non trovato');
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        session_write_close();

        $sendEvent = function (string $event, array $data) {
            echo "event: {$event}\n";
            echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
            if (ob_get_level()) ob_flush();
            flush();
        };

        if ($run['status'] === Run::STATUS_PENDING) {
            $this->run->start($runId);
        }
        $sendEvent('started', ['run_id' => $runId, 'total' => (int) $run['responses_total']]);

        $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);
        $unitCost = Credits::getCost('collect_response', Project::SLUG, 0.2);
        $collector = new EngineCollectorService();
        $subject = $project['subject_name'];

        while (true) {
            Database::reconnect();

            if ($this->run->isCancelled($runId)) {
                $sendEvent('cancelled', ['run_id' => $runId]);
                break;
            }

            $item = $this->run->nextPending($runId);
            if (!$item) {
                $this->run->complete($runId);
                $final = $this->run->find($runId);
                $sendEvent('completed', [
                    'run_id' => $runId,
                    'done' => (int) $final['responses_done'],
                    'errors' => (int) $final['responses_error'],
                    'cost_total' => (float) $final['cost_total'],
                    'report_url' => \Core\Router::url("/ai-reputation/project/{$projectId}/runs/{$runId}"),
                ]);
                try {
                    Database::reconnect();
                    \Services\NotificationService::send($user['id'], 'operation_completed', "AI Reputation Radar: run completato per {$subject}", [
                        'icon' => 'check-circle',
                        'color' => 'indigo',
                        'action_url' => "/ai-reputation/project/{$projectId}/runs/{$runId}",
                        'body' => "Raccolte {$final['responses_done']} risposte" . ((int) $final['responses_error'] > 0 ? ", {$final['responses_error']} errori" : '') . '.',
                        'data' => ['module' => Project::SLUG, 'project_id' => $projectId, 'run_id' => $runId],
                    ]);
                } catch (\Throwable $e) {
                    // non bloccante
                }
                break;
            }

            $sendEvent('progress', [
                'run_id' => $runId,
                'engine' => $item['engine'],
                'prompt' => $item['prompt_text'],
            ]);

            $result = $collector->ask($item['engine'], $item['prompt_text'], [
                'cluster' => $item['prompt_cluster'],
                'user_id' => $user['id'],
                'context' => "run {$runId} prompt {$item['prompt_id']}",
            ]);

            Database::reconnect();
            $this->run->saveResponse((int) $item['id'], $result);

            $ok = $result['status'] === 'ok';
            $credits = 0.0;
            if ($ok) {
                $credits = $unitCost;
                Credits::consume($creditUserId, $unitCost, 'collect_response', Project::SLUG, [
                    'project_id' => $projectId, 'run_id' => $runId, 'engine' => $item['engine'],
                ]);
            }
            $this->run->addProgress($runId, $ok, (float) $result['cost'], $credits);

            $sendEvent($ok ? 'item_completed' : 'item_error', [
                'run_id' => $runId,
                'response_id' => (int) $item['id'],
                'prompt_id' => (int) $item['prompt_id'],
                'engine' => $item['engine'],
                'mentioned' => $ok ? self::mentions($result['text'], $subject) : null,
                'citations' => $ok ? count($result['citations']) : 0,
                'latency_ms' => $result['latency_ms'],
                'cost' => $result['cost'],
                'error' => $result['error_message'],
            ]);
        }
        exit;
    }

    /**
     * GET /ai-reputation/project/{id}/runs/status?run_id=X (polling fallback)
     */
    public function status(int $projectId): void
    {
        header('Content-Type: application/json');
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);
        $runId = (int) ($_GET['run_id'] ?? 0);
        $run = ($project && $runId) ? $this->run->find($runId, $projectId) : null;
        if (!$run) {
            echo json_encode(['success' => false, 'error' => 'Run non trovato']);
            exit;
        }
        echo json_encode(['success' => true, 'run' => [
            'id' => (int) $run['id'],
            'status' => $run['status'],
            'total' => (int) $run['responses_total'],
            'done' => (int) $run['responses_done'],
            'errors' => (int) $run['responses_error'],
            'cost_total' => (float) $run['cost_total'],
            'report_url' => \Core\Router::url("/ai-reputation/project/{$projectId}/runs/{$runId}"),
        ]]);
        exit;
    }

    /**
     * POST /ai-reputation/project/{id}/runs/cancel (JSON)
     */
    public function cancel(int $projectId): void
    {
        header('Content-Type: application/json');
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);
        $runId = (int) ($_POST['run_id'] ?? 0);
        $run = ($project && $runId) ? $this->run->find($runId, $projectId) : null;
        if (!$run || ($project['access_role'] ?? 'owner') === 'viewer') {
            echo json_encode(['success' => false, 'error' => 'Run non trovato']);
            exit;
        }
        echo json_encode(['success' => $this->run->cancel($runId)]);
        exit;
    }

    /**
     * GET /ai-reputation/project/{id}/runs/{runId} - report del run
     */
    public function show(int $projectId, int $runId): string
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);
        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-reputation');
            exit;
        }
        $run = $this->run->find($runId, $projectId);
        if (!$run) {
            $_SESSION['_flash']['error'] = 'Run non trovato';
            Router::redirect("/ai-reputation/project/{$projectId}");
            exit;
        }

        $responses = $this->run->responses($runId);
        $subject = $project['subject_name'];
        $engines = $run['engines'];

        // Griglia prompt x engine + aggregati (fetta 1: menzione = match testuale, giudizio AI in fetta 2)
        $grid = [];
        $domains = [];
        $mentioned = 0;
        $okCount = 0;
        foreach ($responses as $r) {
            $r['mentioned'] = $r['status'] === 'ok' ? self::mentions((string) $r['text'], $subject) : null;
            if ($r['status'] === 'ok') {
                $okCount++;
                if ($r['mentioned']) {
                    $mentioned++;
                }
                foreach ($r['citations'] as $c) {
                    $d = $c['domain'] ?? EngineCollectorService::domainOf($c['url']);
                    if (!$d) {
                        continue;
                    }
                    $domains[$d] ??= ['domain' => $d, 'count' => 0, 'engines' => [], 'urls' => []];
                    $domains[$d]['count']++;
                    $domains[$d]['engines'][$r['engine']] = true;
                    $domains[$d]['urls'][$c['url']] = $c['title'] ?? '';
                }
            }
            $grid[$r['prompt_id']]['prompt'] = ['id' => $r['prompt_id'], 'text' => $r['prompt_text'], 'cluster' => $r['prompt_cluster']];
            $grid[$r['prompt_id']]['cells'][$r['engine']][] = $r;
        }
        usort($domains, fn($a, $b) => $b['count'] <=> $a['count']);

        return View::render('ai-reputation::runs/show', [
            'title' => "Report run #{$runId} - " . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'run' => $run,
            'engines' => $engines,
            'engineLabels' => Project::ENGINE_LABELS,
            'grid' => $grid,
            'domains' => array_slice($domains, 0, 15),
            'stats' => [
                'ok' => $okCount,
                'errors' => (int) $run['responses_error'],
                'mentioned' => $mentioned,
                'share' => $okCount > 0 ? round($mentioned / $okCount * 100) : 0,
                'cost' => (float) $run['cost_total'],
                'domains' => count($domains),
            ],
            'responsesJson' => json_encode(array_map(fn($r) => [
                'id' => (int) $r['id'],
                'engine' => $r['engine'],
                'model' => $r['model'],
                'status' => $r['status'],
                'text' => $r['text'],
                'citations' => $r['citations'],
                'queries' => $r['queries'],
                'search_count' => $r['search_count'],
                'latency_ms' => $r['latency_ms'],
                'cost' => $r['cost'],
                'error' => $r['error_message'],
                'prompt' => $r['prompt_text'],
            ], $responses), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ]);
    }

    /** Menzione del soggetto nel testo (fetta 1: match sul nome completo o sul cognome) */
    public static function mentions(string $text, string $subject): bool
    {
        $text = mb_strtolower($text);
        $subject = mb_strtolower(trim($subject));
        if ($subject === '') {
            return false;
        }
        if (str_contains($text, $subject)) {
            return true;
        }
        $parts = preg_split('/\s+/', $subject);
        $last = end($parts);
        return mb_strlen($last) >= 4 && str_contains($text, $last);
    }
}
