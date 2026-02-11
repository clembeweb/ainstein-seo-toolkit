<?php

namespace Modules\KeywordResearch\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\ModuleLoader;
use Modules\KeywordResearch\Services\KeywordInsightService;

class QuickCheckController
{
    /**
     * Carica la lista progetti SEO Tracking dell'utente (per "Invia a SEO Tracking")
     */
    private function getStProjects(int $userId): array
    {
        if (!ModuleLoader::isModuleActive('seo-tracking')) {
            return [];
        }
        $stProject = new \Modules\SeoTracking\Models\Project();
        $projects = $stProject->allByUser($userId);
        return array_map(fn($p) => [
            'id' => $p['id'],
            'name' => $p['name'],
            'domain' => $p['domain'] ?? '',
        ], $projects);
    }

    public function index(): string
    {
        $user = Auth::user();

        return View::render('keyword-research::quick-check/index', [
            'title' => 'Quick Check - Keyword Research',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'keyword' => '',
            'location' => 'IT',
            'results' => null,
            'stProjects' => $this->getStProjects($user['id']),
        ]);
    }

    public function search(): string
    {
        $user = Auth::user();
        $keyword = trim($_POST['keyword'] ?? '');
        $location = trim($_POST['location'] ?? 'IT');

        if (empty($keyword)) {
            $_SESSION['_flash']['error'] = 'Inserisci una keyword da cercare.';
            return View::render('keyword-research::quick-check/index', [
                'title' => 'Quick Check - Keyword Research',
                'user' => $user,
                'modules' => ModuleLoader::getUserModules($user['id']),
                'keyword' => '',
                'location' => $location,
                'results' => null,
                'stProjects' => $this->getStProjects($user['id']),
            ]);
        }

        $langMap = [
            'IT' => 'it', 'US' => 'en', 'GB' => 'en',
            'DE' => 'de', 'FR' => 'fr', 'ES' => 'es',
        ];
        $lang = $langMap[$location] ?? 'it';

        $service = new KeywordInsightService();

        if (!$service->isConfigured()) {
            $_SESSION['_flash']['error'] = 'API RapidAPI non configurata. Contatta l\'amministratore.';
            return View::render('keyword-research::quick-check/index', [
                'title' => 'Quick Check - Keyword Research',
                'user' => $user,
                'modules' => ModuleLoader::getUserModules($user['id']),
                'keyword' => $keyword,
                'location' => $location,
                'results' => null,
                'stProjects' => $this->getStProjects($user['id']),
            ]);
        }

        $result = $service->keySuggest($keyword, $location, $lang);

        $mainKeyword = null;
        $related = [];

        if ($result['success'] && !empty($result['data'])) {
            foreach ($result['data'] as $item) {
                if (strtolower($item['text'] ?? '') === strtolower($keyword)) {
                    $mainKeyword = $item;
                    break;
                }
            }

            if (!$mainKeyword && !empty($result['data'][0])) {
                $mainKeyword = $result['data'][0];
            }

            foreach ($result['data'] as $item) {
                if ($mainKeyword && ($item['text'] ?? '') === ($mainKeyword['text'] ?? '')) continue;
                $related[] = $item;
            }

            usort($related, fn($a, $b) => ($b['volume'] ?? 0) - ($a['volume'] ?? 0));
        }

        return View::render('keyword-research::quick-check/index', [
            'title' => 'Quick Check - ' . $keyword,
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'keyword' => $keyword,
            'location' => $location,
            'results' => $result['success'] ? [
                'main' => $mainKeyword,
                'related' => $related,
                'total_found' => count($result['data']),
            ] : null,
            'error' => !$result['success'] ? $result['error'] : null,
            'stProjects' => $this->getStProjects($user['id']),
        ]);
    }

    /**
     * POST: Invia keyword selezionate a un progetto SEO Tracking
     */
    public function sendToTracking(): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $groupName = trim($_POST['group_name'] ?? '');
        $location = trim($_POST['location'] ?? 'IT');
        $keywordsJson = $_POST['keywords_data'] ?? '[]';
        $keywordsData = json_decode($keywordsJson, true) ?: [];

        if (empty($projectId) || empty($keywordsData)) {
            echo json_encode(['success' => false, 'error' => 'Dati mancanti.']);
            return;
        }

        // Verifica che il modulo seo-tracking sia attivo
        if (!ModuleLoader::isModuleActive('seo-tracking')) {
            echo json_encode(['success' => false, 'error' => 'Modulo SEO Tracking non attivo.']);
            return;
        }

        // Verifica ownership progetto
        $stProject = new \Modules\SeoTracking\Models\Project();
        $project = $stProject->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato.']);
            return;
        }

        $keywordModel = new \Modules\SeoTracking\Models\Keyword();
        $keywordGroupModel = new \Modules\SeoTracking\Models\KeywordGroup();

        $added = 0;
        $skipped = 0;

        foreach ($keywordsData as $kwData) {
            $kwText = trim($kwData['text'] ?? '');
            if (empty($kwText)) continue;

            // Skip se duplicata
            $existing = $keywordModel->findByKeyword($projectId, $kwText);
            if ($existing) {
                $skipped++;
                continue;
            }

            $newId = $keywordModel->create([
                'project_id' => $projectId,
                'keyword' => $kwText,
                'group_name' => $groupName ?: null,
                'location_code' => $location,
                'is_tracked' => 1,
                'source' => 'keyword_research',
                'search_volume' => (int) ($kwData['volume'] ?? 0) ?: null,
                'cpc' => ($kwData['high_bid'] ?? 0) > 0 ? round((float) $kwData['high_bid'], 2) : null,
                'competition_level' => !empty($kwData['competition_level']) ? strtolower($kwData['competition_level']) : null,
                'keyword_intent' => !empty($kwData['intent']) ? strtolower($kwData['intent']) : null,
                'volume_updated_at' => date('Y-m-d H:i:s'),
            ]);

            if ($newId && !empty($groupName)) {
                $keywordGroupModel->syncKeywordToGroup($projectId, $newId, $groupName);
            }

            $added++;
        }

        echo json_encode([
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
        ]);
    }

    /**
     * GET: Ritorna i nomi dei gruppi di un progetto SEO Tracking (per autocomplete)
     */
    public function projectGroups(): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $projectId = (int) ($_GET['project_id'] ?? 0);

        if (!$projectId || !ModuleLoader::isModuleActive('seo-tracking')) {
            echo json_encode(['success' => true, 'groups' => []]);
            return;
        }

        $stProject = new \Modules\SeoTracking\Models\Project();
        $project = $stProject->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => true, 'groups' => []]);
            return;
        }

        $keywordModel = new \Modules\SeoTracking\Models\Keyword();
        $groups = $keywordModel->getGroups($projectId);
        $groupNames = array_column($groups, 'group_name');

        echo json_encode(['success' => true, 'groups' => $groupNames]);
    }
}
