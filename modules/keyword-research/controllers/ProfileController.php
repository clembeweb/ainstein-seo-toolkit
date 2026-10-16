<?php

namespace Modules\KeywordResearch\Controllers;

use Core\Auth;
use Core\Database;
use Modules\KeywordResearch\Models\Project;
use Modules\KeywordResearch\Services\BusinessProfileService;

/**
 * Riquadro "La tua attività" dei 3 strumenti: profilo dal GEO Audit, dal sito o scritto a mano,
 * e proposte dell'AI (parole di partenza, categorie). Gratis per l'utente.
 */
class ProfileController
{
    /**
     * POST /keyword-research/project/{id}/profile — source: geo | site | text | suggest
     */
    public function build(int $projectId): void
    {
        ignore_user_abort(true);
        set_time_limit(180);
        ob_start();
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = (new Project())->findAccessible($projectId, $user['id']);

        if (!$project) {
            $this->json(['success' => false, 'error' => 'Progetto non trovato.']);
        }
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            $this->json(['success' => false, 'error' => 'Non hai i permessi per questa operazione']);
        }

        $source = (string) ($_POST['source'] ?? '');
        $text = (string) ($_POST['text'] ?? '');
        session_write_close();

        $service = new BusinessProfileService();
        $result = match ($source) {
            'geo' => $service->profileFromGeo($user['id'], $project),
            'site' => $service->profileFromSite($user['id'], $project),
            'text' => $service->profileFromText($user['id'], $project, $text),
            'suggest' => $service->suggest($user['id'], $project, true),
            default => ['success' => false, 'error' => 'Richiesta non valida.'],
        };
        Database::reconnect();

        $this->json($result);
    }

    private function json(array $data): never
    {
        ob_end_clean();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
