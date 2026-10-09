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
use Modules\AiReputation\Services\ActionBriefService;
use Modules\AiReputation\Services\ActionPlanPdfService;
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
        $total = Run::plannedTotal($prompts, $engines, $repeats);
        $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);
        $unitCost = Credits::getCost('collect_response', Project::SLUG, 0.2);
        $judgeCost = Credits::getCost('ai_analysis_medium', null, 1);
        $needed = $total * ($unitCost + $judgeCost);
        if (!Credits::hasEnough($creditUserId, $needed)) {
            echo json_encode(['success' => false, 'error' => 'Crediti insufficienti. Necessari: ' . $needed . ', disponibili: ' . Credits::getBalance($creditUserId)]);
            exit;
        }

        // Creazione atomica: due clic ravvicinati non devono creare due run (lock sulla riga del progetto)
        Database::beginTransaction();
        try {
            Database::fetch("SELECT id FROM ar_projects WHERE id = ? FOR UPDATE", [$projectId]);
            if ($this->run->getActiveForProject($projectId)) {
                Database::rollback();
                echo json_encode(['success' => false, 'error' => 'C\'è già un run in corso per questo progetto']);
                exit;
            }
            $runId = $this->run->create($projectId, $user['id'], $prompts, $engines, $repeats);
            Database::commit();
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            echo json_encode(['success' => false, 'error' => 'Impossibile creare il run: ' . $e->getMessage()]);
            exit;
        }

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
        $isViewer = ($project['access_role'] ?? 'owner') === 'viewer';

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
        $sendEvent('started', ['run_id' => $runId, 'total' => (int) $run['responses_total']]);

        // Il viewer può solo guardare: mai avviare, riprendere o rianalizzare (spende i crediti dell'owner)
        if ($isViewer) {
            $this->follow($runId, $projectId, $sendEvent);
            exit;
        }

        // Un solo stream elabora il run (lease su ar_runs). Gli altri seguono l'avanzamento dal DB.
        $token = bin2hex(random_bytes(8));
        if (!$this->run->acquireLease($runId, $token)) {
            $this->follow($runId, $projectId, $sendEvent);
            exit;
        }
        register_shutdown_function(function () use ($runId, $token) {
            try {
                Database::reconnect();
                (new Run())->releaseLease($runId, $token);
            } catch (\Throwable $e) {
                // il lease scade comunque da solo
            }
        });

        try {
            $this->process($run, $project, $user, $token, $sendEvent);
        } catch (\Throwable $e) {
            // Qualunque errore imprevisto: il run va in "fallito" (non resta appeso in "in corso") e la UI lo sa
            error_log("[ai-reputation] stream run {$runId}: " . $e->getMessage());
            try {
                Database::reconnect();
                $this->run->resetProcessing($runId);
                $this->run->fail($runId, mb_substr('Errore interno: ' . $e->getMessage(), 0, 1000));
                $this->run->releaseLease($runId, $token);
            } catch (\Throwable $e2) {
                // niente da fare
            }
            $sendEvent('failed', ['run_id' => $runId, 'message' => 'Il run si è interrotto per un errore interno. Dettagli nel log del server.']);
        }
        exit;
    }

    /**
     * Corpo dello stream (solo per chi ha il lease): raccolta → giudizi → report.
     */
    private function process(array $run, array $project, array $user, string $token, callable $sendEvent): void
    {
        $runId = (int) $run['id'];
        $projectId = (int) $project['id'];
        $wasCompleted = $run['status'] === Run::STATUS_COMPLETED;
        if ($run['status'] === Run::STATUS_PENDING) {
            $this->run->start($runId);
        }
        // Item rimasti "in lavorazione" da uno stream morto: tornano in coda (abbiamo il lease, siamo soli)
        $this->run->resetProcessing($runId);

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
                if (!$this->run->acquireLease($runId, $token)) {
                    return; // un altro stream ha preso il run (il nostro lease era scaduto): ci fermiamo
                }
                if ($this->run->isCancelled($runId)) {
                    $this->run->resetProcessing($runId);
                    $sendEvent('cancelled', ['run_id' => $runId]);
                    return;
                }
                $item = $this->run->claimNext($runId);
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
                    if (!Credits::consume($creditUserId, $unitCost, 'collect_response', Project::SLUG, [
                        'project_id' => $projectId, 'run_id' => $runId, 'engine' => $item['engine'],
                    ])) {
                        $this->run->recount($runId);
                        $this->run->fail($runId, 'Crediti esauriti durante la raccolta');
                        $sendEvent('failed', ['run_id' => $runId, 'message' => 'Crediti esauriti: la raccolta si è fermata. Le risposte già raccolte restano salvate.']);
                        return;
                    }
                    $credits = $unitCost;
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

        // Nessuna risposta valida (es. API key sbagliate): il run è fallito, non "completato"
        Database::reconnect();
        $this->run->recount($runId);
        $okCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM ar_responses WHERE run_id = ? AND status = 'ok'", [$runId]);
        if ($okCount === 0) {
            $firstError = (string) Database::fetchColumn("SELECT error_message FROM ar_responses WHERE run_id = ? AND status = 'error' LIMIT 1", [$runId]);
            $this->run->fail($runId, 'Nessuna risposta raccolta' . ($firstError ? ': ' . mb_substr($firstError, 0, 300) : ''));
            $this->run->releaseLease($runId, $token);
            $sendEvent('failed', ['run_id' => $runId, 'message' => 'Nessuna AI ha risposto' . ($firstError ? ': ' . mb_substr($firstError, 0, 200) : '') . '. Controlla le API key in Impostazioni globali.']);
            return;
        }

        // ---------- FASE 2: judge ----------
        $toJudge = $this->analysis->pendingForRun($runId);
        if (!empty($toJudge)) {
            $sendEvent('phase', ['phase' => 'analyze', 'total' => count($toJudge), 'label' => 'Analisi delle risposte']);
            $judge = new JudgeService();
            foreach ($toJudge as $item) {
                Database::reconnect();
                if (!$this->run->acquireLease($runId, $token)) {
                    return;
                }
                if (!$wasCompleted && $this->run->isCancelled($runId)) {
                    $sendEvent('cancelled', ['run_id' => $runId]);
                    return;
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
        $this->run->recount($runId);
        $this->run->complete($runId);
        $this->run->releaseLease($runId, $token);
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
    }

    /**
     * Stream "spettatore": un altro processo (o nessuno, per il viewer) elabora il run. Emette snapshot dal DB.
     * Si ferma se il browser chiude la connessione: non tiene occupato un worker per tutto il run.
     */
    private function follow(int $runId, int $projectId, callable $sendEvent): void
    {
        $sendEvent('attached', ['run_id' => $runId, 'message' => 'Mostro l\'avanzamento del run']);
        $last = '';
        $ticks = 0;
        while (true) {
            echo ": ping\n\n";
            if (ob_get_level()) ob_flush();
            flush();
            if (connection_aborted()) {
                return;
            }
            Database::reconnect();
            $r = $this->run->find($runId);
            if (!$r) {
                return;
            }
            $total = (int) $r['responses_total'];
            $done = (int) Database::fetchColumn("SELECT COUNT(*) FROM ar_responses WHERE run_id = ? AND status IN ('ok', 'error')", [$runId]);
            $analyzed = (int) Database::fetchColumn("SELECT COUNT(*) FROM ar_analyses WHERE run_id = ?", [$runId]);
            $collecting = $done < $total;
            $snap = [
                'run_id' => $runId,
                'phase' => $collecting ? 'Raccolta risposte' : 'Analisi delle risposte',
                'done' => $collecting ? $done : $analyzed,
                'total' => $collecting ? $total : (int) $r['responses_done'],
            ];
            $key = json_encode($snap);
            if ($key !== $last) {
                $sendEvent('snapshot', $snap);
                $last = $key;
            }
            $finished = in_array($r['status'], [Run::STATUS_COMPLETED, Run::STATUS_FAILED, Run::STATUS_CANCELLED], true) && $r['locked_by'] === null;
            if ($finished) {
                if ($r['status'] === Run::STATUS_FAILED) {
                    $sendEvent('failed', ['run_id' => $runId, 'message' => (string) ($r['error_message'] ?: 'Run fallito')]);
                    return;
                }
                $sendEvent($r['status'] === Run::STATUS_CANCELLED ? 'cancelled' : 'completed', [
                    'run_id' => $runId,
                    'done' => (int) $r['responses_done'],
                    'errors' => (int) $r['responses_error'],
                    'analyses' => (int) $r['analyses_done'],
                    'actions' => (int) Database::fetchColumn("SELECT COUNT(*) FROM ar_actions WHERE run_id = ?", [$runId]),
                    'homonyms' => 0,
                    'cost_total' => (float) $r['cost_total'],
                    'report_url' => Router::url("/ai-reputation/project/{$projectId}/runs/{$runId}"),
                ]);
                return;
            }
            // Nessuno lavora il run (lease scaduto o mai preso): fermati, riaprire la pagina lo riprende
            if (in_array($r['status'], [Run::STATUS_PENDING, Run::STATUS_RUNNING], true)
                && ($r['locked_by'] === null || strtotime((string) $r['locked_at']) < time() - Run::LEASE_TTL)) {
                if (++$ticks > 3) {
                    $sendEvent('stalled', ['run_id' => $runId, 'message' => 'Elaborazione ferma: ricarica la pagina per riprendere']);
                    return;
                }
            } else {
                $ticks = 0;
            }
            sleep(3);
        }
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
            $grid[$r['prompt_id']]['prompt'] = ['id' => $r['prompt_id'], 'text' => $r['prompt_text'], 'cluster' => $r['prompt_cluster'], 'leading' => (int) ($r['prompt_leading'] ?? 0)];
            $grid[$r['prompt_id']]['cells'][$r['engine']][] = $r;
        }

        $metrics = $builder->metrics($responses, $analyses, $engines, $project);
        $sources = $builder->sources($responses, $analyses);
        $competitors = $builder->competitors($analyses);
        $actions = Database::fetchAll("SELECT * FROM ar_actions WHERE run_id = ? ORDER BY FIELD(type, 'removal', 'counter_content', 'gap_article', 'correction', 'gap_pending'), id", [$runId]);
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
            'briefCost' => Credits::getCost('action_brief', Project::SLUG, 1),
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
                    'contradiction_note' => (int) ($analyses[(int) $r['id']]['contradicts_profile'] ?? 0) === 1 ? $analyses[(int) $r['id']]['contradiction_note'] : null,
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
     * GET /ai-reputation/project/{id}/runs/{runId}/export/plan.pdf - PDF del piano d'azione del run
     * (interventi come nel report, con la scheda operativa se generata).
     */
    public function exportPlanPdf(int $projectId, int $runId): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);
        $run = $project ? $this->run->find($runId, $projectId) : null;
        if (!$project || !$run) {
            $_SESSION['_flash']['error'] = 'Run non trovato';
            Router::redirect('/ai-reputation');
            exit;
        }
        $responses = $this->run->responses($runId);
        $analyses = $this->analysis->byRun($runId);
        $metrics = (new ReportBuilderService())->metrics($responses, $analyses, $run['engines'], $project);
        $actions = Database::fetchAll("SELECT * FROM ar_actions WHERE run_id = ? ORDER BY FIELD(type, 'removal', 'counter_content', 'gap_article', 'correction', 'gap_pending'), id", [$runId]);
        try {
            $pdfService = new ActionPlanPdfService();
            $pdf = $pdfService->render($project, $run, $actions, $metrics);
        } catch (\Throwable $e) {
            \Core\Logger::channel('ai-reputation')->error('Export PDF fallito', ['run_id' => $runId, 'error' => $e->getMessage()]);
            $_SESSION['_flash']['error'] = 'Export PDF non riuscito, riprova tra poco.';
            Router::redirect("/ai-reputation/project/{$projectId}/runs/{$runId}");
            exit;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $pdfService->filename($project, $run) . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, no-store');
        echo $pdf;
        exit;
    }

    /**
     * POST /ai-reputation/project/{id}/runs/{runId}/actions/{actionId}/brief
     * Genera (o rigenera con force=1) la scheda operativa di un intervento. AJAX lungo (GR 15/17/23).
     * Unico punto di addebito: ActionBriefService chiama AiService con charge_credits=false.
     */
    public function generateBrief(int $projectId, int $runId, int $actionId): void
    {
        ignore_user_abort(true);
        set_time_limit(300);
        ob_start();
        header('Content-Type: application/json');
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);
        $run = $project ? $this->run->find($runId, $projectId) : null;
        $action = $run ? Database::fetch("SELECT * FROM ar_actions WHERE id = ? AND run_id = ? AND project_id = ?", [$actionId, $runId, $projectId]) : null;
        if (!$project || !$run || !$action) {
            ob_end_clean();
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Intervento non trovato']);
            exit;
        }
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            ob_end_clean();
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
            exit;
        }
        // Prima di crediti e AI: niente schede per interventi scartati o rimozioni senza pagine da contattare
        if (($action['status'] ?? '') === 'dismissed') {
            ob_end_clean();
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Intervento scartato']);
            exit;
        }
        if ($action['type'] === 'removal' && !ActionPlanPdfService::pages($action)) {
            ob_end_clean();
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Nessuna pagina da contattare per questo intervento']);
            exit;
        }
        if ($action['type'] === 'gap_pending') {
            ob_end_clean();
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Domanda in sospeso: serve prima una prova dal cliente']);
            exit;
        }
        $force = !empty($_POST['force']);
        // Doppio clic: scheda appena generata → ritorna quella senza richiamare l'AI
        if (!$force && !empty($action['brief']) && !empty($action['brief_generated_at']) && strtotime($action['brief_generated_at']) > time() - 120) {
            ob_end_clean();
            echo json_encode(['success' => true, 'html' => $this->briefHtml($action, $project, $run)]);
            exit;
        }
        $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);
        $cost = Credits::getCost('action_brief', Project::SLUG, 1);
        if (!Credits::hasEnough($creditUserId, $cost)) {
            ob_end_clean();
            http_response_code(402);
            echo json_encode(['success' => false, 'error' => 'Crediti insufficienti. Necessari: ' . $cost . ', disponibili: ' . Credits::getBalance($creditUserId)]);
            exit;
        }
        session_write_close();
        try {
            $res = (new ActionBriefService())->generate($actionId, $creditUserId);
        } catch (\Throwable $e) {
            // Il messaggio grezzo va solo nel log, mai in brief_error (visibile in pagina)
            \Core\Logger::channel('ai-reputation')->error('Generazione scheda fallita', ['action_id' => $actionId, 'run_id' => $runId, 'error' => $e->getMessage()]);
            Database::reconnect();
            $genericError = 'Errore imprevisto durante la generazione, riprova.';
            Database::execute("UPDATE ar_actions SET brief_error = ? WHERE id = ?", [$genericError, $actionId]);
            $res = ['success' => false, 'error' => $genericError];
        }
        Database::reconnect();
        if (empty($res['success'])) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => $res['error'] ?? 'Generazione non riuscita']);
            exit;
        }
        // La scheda e' gia' salvata: un addebito fallito (es. saldo sceso nel frattempo) si logga, non blocca la risposta
        if (!Credits::consume($creditUserId, $cost, 'action_brief', Project::SLUG, ['action_id' => $actionId, 'run_id' => $runId])) {
            \Core\Logger::channel('ai-reputation')->warning('Addebito scheda non riuscito', ['action_id' => $actionId, 'run_id' => $runId, 'user_id' => $creditUserId, 'cost' => $cost]);
        }
        try {
            $html = $this->briefHtml($res['action'], $project, $run);
        } catch (\Throwable $e) {
            \Core\Logger::channel('ai-reputation')->error('Render scheda fallito', ['action_id' => $actionId, 'run_id' => $runId, 'error' => $e->getMessage()]);
            ob_end_clean();
            echo json_encode(['success' => true, 'html' => '', 'reload' => true]);
            exit;
        }
        ob_end_clean();
        echo json_encode(['success' => true, 'html' => $html]);
        exit;
    }

    private function briefHtml(array $action, array $project, array $run): string
    {
        return View::partial('ai-reputation::partials/action-brief', [
            'a' => $action,
            'basePath' => '/ai-reputation/project/' . $project['id'],
            'run' => $run,
            'csrf' => csrf_token(),
            'canEdit' => ($project['access_role'] ?? 'owner') !== 'viewer',
            'briefCost' => Credits::getCost('action_brief', Project::SLUG, 1),
        ]);
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
