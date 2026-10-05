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
use Modules\AiReputation\Models\Analysis;
use Modules\AiReputation\Services\EngineCollectorService;
use Modules\AiReputation\Services\JudgeService;
use Modules\AiReputation\Services\ReportBuilderService;

/**
 * RunController - avvio run, stream SSE (fase 1 collector, fase 2 judge), stato, annullamento, report.
 * Pattern: modules/seo-tracking/controllers/RankCheckController.php
 */
class RunController
{
    private Project $project;
    private Prompt $prompt;
    private Run $run;
    private Analysis $analysis;

    public function __construct()
    {
        $this->project = new Project();
        $this->prompt = new Prompt();
        $this->run = new Run();
        $this->analysis = new Analysis();
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
        $judgeCost = Credits::getCost('ai_analysis_medium', null, 1);
        $needed = $total * ($unitCost + $judgeCost);
        if (!Credits::hasEnough($creditUserId, $needed)) {
            echo json_encode(['success' => false, 'error' => 'Crediti insufficienti. Necessari: ' . $needed . ', disponibili: ' . Credits::getBalance($creditUserId)]);
            exit;
        }

        $runId = $this->run->create($projectId, $user['id'], $prompts, $engines, $repeats);

        echo json_encode([
            'success' => true,
            'run_id' => $runId,
            'responses_total' => $total,
            'engines' => $engines,
            'engines_missing' => array_values($missing),
            'estimated_credits' => $needed,
        ]);
        exit;
    }

    /**
     * POST /ai-reputation/project/{id}/runs/{runId}/reanalyze (JSON) - cancella i giudizi, poi lo stream li rifà
     */
    public function reanalyze(int $projectId, int $runId): void
    {
        header('Content-Type: application/json');
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);
        $run = $project ? $this->run->find($runId, $projectId) : null;
        if (!$run || ($project['access_role'] ?? 'owner') === 'viewer') {
            echo json_encode(['success' => false, 'error' => 'Run non trovato']);
            exit;
        }
        if (in_array($run['status'], [Run::STATUS_PENDING, Run::STATUS_RUNNING], true)) {
            echo json_encode(['success' => false, 'error' => 'Il run è ancora in corso']);
            exit;
        }
        $this->analysis->deleteByRun($runId);
        echo json_encode(['success' => true, 'run_id' => $runId, 'to_judge' => (int) $run['responses_done']]);
        exit;
    }

    /**
     * GET /ai-reputation/project/{id}/runs/stream?run_id=X (SSE)
     * Fase 1: raccoglie le risposte pending. Fase 2: giudica le risposte senza analisi. Poi costruisce il report.
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

        $wasCompleted = $run['status'] === Run::STATUS_COMPLETED;
        if ($run['status'] === Run::STATUS_PENDING) {
            $this->run->start($runId);
        }
        $sendEvent('started', ['run_id' => $runId, 'total' => (int) $run['responses_total']]);

        $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);
        $subject = $project['subject_name'];

        // ---------- FASE 1: collector ----------
        $pendingTotal = (int) Database::fetchColumn("SELECT COUNT(*) FROM ar_responses WHERE run_id = ? AND status = 'pending'", [$runId]);
        if ($pendingTotal > 0) {
            $sendEvent('phase', ['phase' => 'collect', 'total' => $pendingTotal, 'label' => 'Raccolta risposte']);
            $unitCost = Credits::getCost('collect_response', Project::SLUG, 0.2);
            $collector = new EngineCollectorService();

            while (true) {
                Database::reconnect();
                if ($this->run->isCancelled($runId)) {
                    $sendEvent('cancelled', ['run_id' => $runId]);
                    exit;
                }
                $item = $this->run->nextPending($runId);
                if (!$item) {
                    break;
                }
                $sendEvent('progress', ['run_id' => $runId, 'engine' => $item['engine'], 'prompt' => $item['prompt_text']]);

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
        }

        // ---------- FASE 2: judge ----------
        Database::reconnect();
        $toJudge = $this->analysis->pendingForRun($runId);
        if (!empty($toJudge)) {
            $sendEvent('phase', ['phase' => 'analyze', 'total' => count($toJudge), 'label' => 'Analisi delle risposte']);
            $judge = new JudgeService();
            foreach ($toJudge as $item) {
                Database::reconnect();
                if (!$wasCompleted && $this->run->isCancelled($runId)) {
                    $sendEvent('cancelled', ['run_id' => $runId]);
                    exit;
                }
                $sendEvent('progress', ['run_id' => $runId, 'engine' => $item['engine'], 'prompt' => $item['prompt_text']]);
                $item['citations'] = json_decode((string) $item['citations'], true) ?: [];
                $item['sources_read'] = json_decode((string) $item['sources_read'], true) ?: [];

                try {
                    $verdict = $judge->judge($creditUserId, $project, $item);
                    Database::reconnect();
                    if (isset($verdict['error'])) {
                        $sendEvent('analysis_error', ['response_id' => (int) $item['id'], 'engine' => $item['engine'], 'error' => $verdict['error']]);
                        continue;
                    }
                    $this->analysis->save((int) $item['id'], $runId, $projectId, $verdict);
                } catch (\Throwable $e) {
                    Database::reconnect();
                    $sendEvent('analysis_error', ['response_id' => (int) $item['id'], 'engine' => $item['engine'], 'error' => 'Errore interno: ' . $e->getMessage()]);
                    error_log('[ai-reputation] judge error response ' . $item['id'] . ': ' . $e->getMessage());
                    continue;
                }
                Database::execute("UPDATE ar_runs SET analyses_done = analyses_done + 1 WHERE id = ?", [$runId]);
                $sendEvent('analysis_completed', [
                    'response_id' => (int) $item['id'],
                    'engine' => $item['engine'],
                    'verdict' => $verdict['verdict'],
                    'outcome' => $verdict['outcome'],
                    'is_homonym' => $verdict['is_homonym'],
                    'summary' => $verdict['summary'],
                ]);
            }
        }

        // ---------- REPORT: fonti, competitor, azioni, omonimi ----------
        Database::reconnect();
        $responses = $this->run->responses($runId);
        $analyses = $this->analysis->byRun($runId);
        $built = (new ReportBuilderService())->persist($runId, $projectId, $project, $responses, $analyses, Project::ENGINE_LABELS);
        $this->run->complete($runId);
        $final = $this->run->find($runId);

        $sendEvent('completed', [
            'run_id' => $runId,
            'done' => (int) $final['responses_done'],
            'errors' => (int) $final['responses_error'],
            'analyses' => (int) $final['analyses_done'],
            'actions' => count($built['actions']),
            'homonyms' => $built['homonyms'],
            'cost_total' => (float) $final['cost_total'],
            'report_url' => Router::url("/ai-reputation/project/{$projectId}/runs/{$runId}"),
        ]);

        if (!$wasCompleted) {
            try {
                Database::reconnect();
                \Services\NotificationService::send($user['id'], 'operation_completed', "AI Reputation Radar: run completato per {$subject}", [
                    'icon' => 'check-circle',
                    'color' => 'indigo',
                    'action_url' => "/ai-reputation/project/{$projectId}/runs/{$runId}",
                    'body' => "Raccolte {$final['responses_done']} risposte, {$final['analyses_done']} analizzate, " . count($built['actions']) . ' azioni proposte.',
                    'data' => ['module' => Project::SLUG, 'project_id' => $projectId, 'run_id' => $runId],
                ]);
            } catch (\Throwable $e) {
                // non bloccante
            }
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
            'analyses' => (int) $run['analyses_done'],
            'cost_total' => (float) $run['cost_total'],
            'report_url' => Router::url("/ai-reputation/project/{$projectId}/runs/{$runId}"),
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
        $analyses = $this->analysis->byRun($runId);
        $engines = $run['engines'];
        $builder = new ReportBuilderService();

        $grid = [];
        foreach ($responses as $r) {
            $r['analysis'] = $analyses[(int) $r['id']] ?? null;
            $r['mentioned'] = $r['status'] === 'ok' ? self::mentions((string) $r['text'], $project['subject_name']) : null;
            $grid[$r['prompt_id']]['prompt'] = ['id' => $r['prompt_id'], 'text' => $r['prompt_text'], 'cluster' => $r['prompt_cluster']];
            $grid[$r['prompt_id']]['cells'][$r['engine']][] = $r;
        }

        $metrics = $builder->metrics($responses, $analyses, $engines, $project);
        $sources = $builder->sources($responses, $analyses);
        $competitors = $builder->competitors($analyses);
        $actions = Database::fetchAll("SELECT * FROM ar_actions WHERE run_id = ? ORDER BY FIELD(type, 'removal', 'counter_content', 'gap_article', 'correction'), id", [$runId]);
        $homonyms = Database::fetchAll("SELECT * FROM ar_profile_facts WHERE project_id = ? AND category = 'homonym' AND status = 'proposed' ORDER BY id", [$projectId]);

        return View::render('ai-reputation::runs/show', [
            'title' => "Report run #{$runId} - " . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'run' => $run,
            'engines' => $engines,
            'engineLabels' => Project::ENGINE_LABELS,
            'grid' => $grid,
            'metrics' => $metrics,
            'sources' => array_slice($sources, 0, 20),
            'competitors' => array_slice($competitors, 0, 12),
            'actions' => $actions,
            'homonyms' => $homonyms,
            'hasAnalyses' => !empty($analyses),
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
                'analysis' => isset($analyses[(int) $r['id']]) ? [
                    'verdict' => $analyses[(int) $r['id']]['verdict'],
                    'outcome' => $analyses[(int) $r['id']]['outcome'],
                    'summary' => $analyses[(int) $r['id']]['summary'],
                    'sentiment' => (int) $analyses[(int) $r['id']]['sentiment'],
                    'is_homonym' => $analyses[(int) $r['id']]['is_homonym'],
                    'homonym_note' => $analyses[(int) $r['id']]['homonym_note'],
                    'negative_reasons' => $analyses[(int) $r['id']]['negative_reasons'],
                    'negative_urls' => $analyses[(int) $r['id']]['negative_urls'],
                    'citations_noise' => $analyses[(int) $r['id']]['citations_noise'],
                    'claims' => $analyses[(int) $r['id']]['claims'],
                    'competitors' => $analyses[(int) $r['id']]['competitors'],
                ] : null,
            ], $responses), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ]);
    }

    /**
     * POST /ai-reputation/project/{id}/facts/{factId}/{decision} - conferma (è lui) o rifiuta (è un altro) un omonimo
     */
    public function decideFact(int $projectId, int $factId, string $decision): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);
        if (!$project || ($project['access_role'] ?? 'owner') === 'viewer') {
            $_SESSION['_flash']['error'] = 'Permessi insufficienti';
            Router::redirect('/ai-reputation');
            return;
        }
        $fact = Database::fetch("SELECT * FROM ar_profile_facts WHERE id = ? AND project_id = ?", [$factId, $projectId]);
        if (!$fact) {
            $_SESSION['_flash']['error'] = 'Riga non trovata';
            Router::redirect("/ai-reputation/project/{$projectId}");
            return;
        }
        // "confirm" = è lui → la riga omonimo è rifiutata (non c'è omonimo). "reject" = è un altro → confermata come omonimo
        $status = $decision === 'confirm' ? 'rejected' : 'confirmed';
        Database::update('ar_profile_facts', ['status' => $status], 'id = ?', [$factId]);
        if ($status === 'confirmed') {
            // L'omonimo confermato finisce nelle note di disambiguazione, così il judge lo sa dal prossimo run
            $notes = trim((string) ($project['disambiguation_notes'] ?? ''));
            $notes .= ($notes !== '' ? "\n" : '') . 'Omonimo confermato: ' . $fact['text'];
            $this->project->update($projectId, ['disambiguation_notes' => $notes]);
        }
        $_SESSION['_flash']['success'] = $status === 'confirmed' ? 'Segnato come omonimo: il judge ne terrà conto dal prossimo run' : 'Confermato: è il soggetto monitorato';
        $back = $_POST['back'] ?? "/ai-reputation/project/{$projectId}";
        Router::redirect($back);
    }

    /** Menzione del soggetto nel testo (match sul nome completo o sul cognome) */
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
