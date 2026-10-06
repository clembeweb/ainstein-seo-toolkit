<?php

namespace Modules\AiReputation\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\AiReputation\Models\Project;
use Modules\AiReputation\Models\ProfileFact;
use Modules\AiReputation\Services\OnboardingService;

/**
 * ProfileController - profilo del soggetto: generazione (onboarding agent) e conferma riga per riga
 */
class ProfileController
{
    private Project $project;
    private ProfileFact $facts;

    public function __construct()
    {
        $this->project = new Project();
        $this->facts = new ProfileFact();
    }

    public function index(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-reputation');
            exit;
        }

        return View::render('ai-reputation::profile/index', [
            'title' => $project['name'] . ' - Profilo',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'grouped' => $this->facts->grouped($id),
            'counts' => $this->facts->counts($id),
            'categories' => ProfileFact::CATEGORIES,
            'perplexityOk' => \Modules\AiReputation\Services\EngineCollectorService::isConfigured('perplexity'),
        ]);
    }

    /**
     * POST /ai-reputation/project/{id}/profile/generate (AJAX lungo, JSON)
     */
    public function generate(int $id): void
    {
        ignore_user_abort(true);
        set_time_limit(300);
        ob_start();
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Non hai i permessi per questa operazione']);
            exit;
        }
        $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);
        session_write_close();

        try {
            $result = (new OnboardingService())->buildProfile($creditUserId, $project);
        } catch (\Throwable $e) {
            $result = ['success' => false, 'error' => 'Errore interno: ' . $e->getMessage()];
        }
        \Core\Database::reconnect();

        ob_end_clean();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /ai-reputation/project/{id}/profile/facts - riga manuale (già confermata) */
    public function store(int $id): void
    {
        $project = $this->requireEditable($id);
        $text = trim($_POST['text'] ?? '');
        $category = $_POST['category'] ?? 'fact';
        if (mb_strlen($text) < 3) {
            $_SESSION['_flash']['error'] = 'Scrivi un\'affermazione';
            Router::redirect("/ai-reputation/project/{$id}/profile");
            return;
        }
        $this->facts->create($id, $category, $text, null, 'manual', 'confirmed');
        $_SESSION['_flash']['success'] = 'Riga aggiunta e confermata';
        Router::redirect("/ai-reputation/project/{$id}/profile");
    }

    /**
     * POST /ai-reputation/project/{id}/profile/facts/{factId}/{decision}
     * decision: confirm | reject | correct | delete | homonym-yes (è lui) | homonym-no (è un altro)
     */
    public function decide(int $id, int $factId, string $decision): void
    {
        $project = $this->requireEditable($id);
        $fact = $this->facts->find($factId, $id);
        $back = (string) ($_POST['back'] ?? '');
        if (!str_starts_with($back, "/ai-reputation/project/{$id}/") || str_contains($back, '//')) {
            $back = "/ai-reputation/project/{$id}/profile";
        }
        if (!$fact) {
            $_SESSION['_flash']['error'] = 'Riga non trovata';
            Router::redirect($back);
            return;
        }

        switch ($decision) {
            case 'confirm':
                $this->facts->setStatus($factId, $id, 'confirmed');
                break;
            case 'reject':
                $this->facts->setStatus($factId, $id, 'rejected');
                $_SESSION['_flash']['success'] = 'Rifiutata: diventa un errore da monitorare nelle risposte delle AI';
                break;
            case 'correct':
                $corrected = trim($_POST['corrected_text'] ?? '');
                if ($corrected === '') {
                    $_SESSION['_flash']['error'] = 'Scrivi il testo corretto';
                    Router::redirect($back);
                    return;
                }
                $this->facts->setStatus($factId, $id, 'corrected', $corrected);
                break;
            case 'delete':
                $this->facts->delete($factId, $id);
                break;
            case 'homonym-yes':
                // "Sì, è lui": non c'è omonimo → la riga si rifiuta
                $this->facts->setStatus($factId, $id, 'rejected');
                $_SESSION['_flash']['success'] = 'Confermato: è il soggetto monitorato';
                break;
            case 'homonym-no':
                // "No, è un altro": omonimo confermato → va nelle note di disambiguazione per il judge
                $this->facts->setStatus($factId, $id, 'confirmed');
                $notes = trim((string) ($project['disambiguation_notes'] ?? ''));
                if (!str_contains($notes, $fact['text'])) {
                    $notes .= ($notes !== '' ? "\n" : '') . 'Omonimo confermato: ' . $fact['text'];
                    $this->project->update($id, ['disambiguation_notes' => $notes]);
                }
                $_SESSION['_flash']['success'] = 'Segnato come omonimo: il judge ne terrà conto dal prossimo run';
                break;
            default:
                $_SESSION['_flash']['error'] = 'Azione non valida';
        }
        Router::redirect($back);
    }

    private function requireEditable(int $id): array
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || ($project['access_role'] ?? 'owner') === 'viewer') {
            $_SESSION['_flash']['error'] = 'Progetto non trovato o permessi insufficienti';
            Router::redirect('/ai-reputation');
            exit;
        }
        return $project;
    }
}
