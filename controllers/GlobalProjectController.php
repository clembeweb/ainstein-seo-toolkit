<?php

namespace Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Middleware;
use Core\ModuleLoader;
use Core\Models\GlobalProject;

/**
 * GlobalProjectController
 * Gestisce CRUD progetti globali e attivazione moduli.
 */
class GlobalProjectController
{
    private GlobalProject $project;

    public function __construct()
    {
        $this->project = new GlobalProject();
    }

    /**
     * Lista progetti globali (propri + condivisi)
     * GET /projects
     */
    public function index(): string
    {
        Middleware::auth();
        $user = Auth::user();

        // Recupera progetti propri e condivisi
        $projectData = $this->project->allWithShared($user['id']);
        $pendingInvitations = \Services\ProjectAccessService::getPendingInvitations($user['id']);

        // Arricchisci progetti propri con statistiche moduli (come faceva allWithModuleStats)
        $ownedProjects = $this->enrichWithModuleStats($projectData['owned']);
        $sharedProjects = $this->enrichWithModuleStats($projectData['shared']);

        // Mantieni compatibilita: $projects = owned per viste esistenti
        $projects = $ownedProjects;

        // Tutti i siti WordPress dell'utente (con info progetto collegato)
        $wpSiteModel = new \Modules\AiContent\Models\WpSite();
        $allWpSites = $wpSiteModel->allByUser($user['id']);
        // Arricchisci con nome progetto
        $projectNames = [];
        foreach (array_merge($ownedProjects, $sharedProjects) as $p) {
            $projectNames[(int) $p['id']] = $p['name'];
        }
        foreach ($allWpSites as &$wpSite) {
            $gpId = (int) ($wpSite['global_project_id'] ?? 0);
            $wpSite['project_name'] = $gpId > 0 ? ($projectNames[$gpId] ?? 'Progetto #' . $gpId) : null;
        }
        unset($wpSite);

        return View::render('projects/index', [
            'title' => 'Progetti',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projects' => $projects,
            'ownedProjects' => $ownedProjects,
            'sharedProjects' => $sharedProjects,
            'pendingInvitations' => $pendingInvitations,
            'allWpSites' => $allWpSites,
        ]);
    }

    /**
     * Arricchisci array progetti con active_modules_count e last_module_activity.
     * Replica la logica di allWithModuleStats() senza duplicare la query iniziale.
     */
    private function enrichWithModuleStats(array $projects): array
    {
        $moduleConfig = $this->project->getModuleConfig();

        foreach ($projects as &$project) {
            $activeCount = 0;
            $lastActivity = null;

            foreach ($moduleConfig as $slug => $config) {
                try {
                    $row = \Core\Database::fetch(
                        "SELECT COUNT(*) as cnt, MAX(created_at) as last_at FROM {$config['table']} WHERE global_project_id = ?",
                        [$project['id']]
                    );

                    if ($row && (int) $row['cnt'] > 0) {
                        $activeCount++;
                        if ($row['last_at'] && ($lastActivity === null || $row['last_at'] > $lastActivity)) {
                            $lastActivity = $row['last_at'];
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            $project['active_modules_count'] = $activeCount;
            $project['last_module_activity'] = $lastActivity;
        }
        unset($project);

        return $projects;
    }

    /**
     * Form creazione progetto
     * GET /projects/create
     */
    public function create(): string
    {
        Middleware::auth();
        $user = Auth::user();

        return View::render('projects/create', [
            'title' => 'Nuovo Progetto',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Salva nuovo progetto
     * POST /projects
     */
    public function store(): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $name = trim($_POST['name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? 'blue');

        // Validazione
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        if (strlen($name) > 255) {
            $errors[] = 'Il nome del progetto non può superare 255 caratteri';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/projects/create');
            return;
        }

        // Normalizza dominio se presente
        if (!empty($domain)) {
            $domain = rtrim($domain, '/');
            // Rimuovi protocollo se presente per uniformità
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = rtrim($domain, '/');
        }

        try {
            $projectId = $this->project->create([
                'user_id' => $user['id'],
                'name' => $name,
                'domain' => $domain ?: null,
                'description' => $description ?: null,
                'color' => $color,
                'status' => 'active',
            ]);

            $_SESSION['_flash']['success'] = 'Progetto creato con successo!';
            Router::redirect('/projects/' . $projectId);

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nella creazione: ' . $e->getMessage();
            Router::redirect('/projects/create');
        }
    }

    /**
     * Dashboard progetto globale
     * GET /projects/{id}
     */
    public function dashboard(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/projects');
            return '';
        }

        $activeModules = $this->project->getActiveModules($id);
        $moduleStats = $this->project->getModuleStats($id);
        $availableModules = ModuleLoader::getActiveModules();
        $moduleConfig = $this->project->getModuleConfig();

        $moduleTypes = $this->project->getModuleTypes();
        $remainingTypes = $this->project->getRemainingTypes($id);

        // Tipi attivi per modulo (per filtrare la modal)
        $activeTypesPerModule = [];
        foreach ($activeModules as $m) {
            if (!empty($m['type'])) {
                $activeTypesPerModule[$m['slug']][] = $m['type'];
            }
        }

        // Load WordPress sites linked to this project
        $wpSiteModel = new \Modules\AiContent\Models\WpSite();
        $wpSites = $wpSiteModel->getAllByProject($id);
        $unlinkedWpSites = $wpSiteModel->getUnlinkedByUser($user['id']);

        return View::render('projects/dashboard', [
            'title' => $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'access_role' => $project['access_role'],
            'activeModules' => $activeModules,
            'moduleStats' => $moduleStats,
            'availableModules' => $availableModules,
            'moduleConfig' => $moduleConfig,
            'moduleTypes' => $moduleTypes,
            'remainingTypes' => $remainingTypes,
            'activeTypesPerModule' => $activeTypesPerModule,
            'wpSites' => $wpSites,
            'unlinkedWpSites' => $unlinkedWpSites,
        ]);
    }

    /**
     * Impostazioni progetto (solo owner)
     * GET /projects/{id}/settings
     */
    public function settings(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project || $project['access_role'] !== 'owner') {
            $_SESSION['_flash']['error'] = 'Solo il proprietario puo modificare le impostazioni';
            Router::redirect('/projects/' . $id);
            return '';
        }

        return View::render('projects/settings', [
            'title' => $project['name'] . ' - Impostazioni',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'access_role' => $project['access_role'],
        ]);
    }

    /**
     * Aggiorna progetto (solo owner)
     * POST /projects/{id}/settings
     */
    public function update(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project || $project['access_role'] !== 'owner') {
            $_SESSION['_flash']['error'] = 'Solo il proprietario puo modificare le impostazioni';
            Router::redirect('/projects/' . $id);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? $project['color'] ?? 'blue');

        // Validazione
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        if (strlen($name) > 255) {
            $errors[] = 'Il nome del progetto non può superare 255 caratteri';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/projects/' . $id . '/settings');
            return;
        }

        // Normalizza dominio se presente
        if (!empty($domain)) {
            $domain = rtrim($domain, '/');
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = rtrim($domain, '/');
        }

        try {
            $this->project->update($id, [
                'name' => $name,
                'domain' => $domain ?: null,
                'description' => $description ?: null,
                'color' => $color,
            ], $user['id']);

            $_SESSION['_flash']['success'] = 'Impostazioni salvate con successo';
            Router::redirect('/projects/' . $id . '/settings');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nel salvataggio: ' . $e->getMessage();
            Router::redirect('/projects/' . $id . '/settings');
        }
    }

    /**
     * Attiva modulo per progetto globale (solo owner)
     * POST /projects/{id}/activate-module
     */
    public function activateModule(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project || $project['access_role'] !== 'owner') {
            $_SESSION['_flash']['error'] = 'Solo il proprietario puo attivare moduli';
            Router::redirect('/projects/' . $id);
            return;
        }

        $moduleSlug = trim($_POST['module'] ?? '');
        $moduleConfig = $this->project->getModuleConfig();

        // Verifica che il modulo sia valido
        if (empty($moduleSlug) || !isset($moduleConfig[$moduleSlug])) {
            $_SESSION['_flash']['error'] = 'Modulo non valido';
            Router::redirect('/projects/' . $id);
            return;
        }

        // Verifica se il modulo è già attivo per questo progetto
        $type = trim($_POST['type'] ?? '');
        $activeModules = $this->project->getActiveModules($id);
        foreach ($activeModules as $active) {
            if ($active['slug'] === $moduleSlug) {
                // Per moduli tipizzati, verificare anche il tipo
                $moduleTypes = $this->project->getModuleTypes();
                if (isset($moduleTypes[$moduleSlug]) && !empty($type)) {
                    // Tipizzato: blocca solo se stesso tipo già attivo
                    if (($active['type'] ?? '') === $type) {
                        $routePrefix = $moduleConfig[$moduleSlug]['route_prefix'];
                        $routeUrl = $routePrefix . '/' . $active['module_project_id'];
                        if (!empty($moduleConfig[$moduleSlug]['type_in_route'])) {
                            $routeUrl .= '/' . $type;
                        }
                        Router::redirect($routeUrl);
                        return;
                    }
                    // Tipo diverso: lascia passare per attivazione
                    continue;
                }
                // Non tipizzato: già attivo, redirect
                $routePrefix = $moduleConfig[$moduleSlug]['route_prefix'];
                Router::redirect($routePrefix . '/' . $active['module_project_id']);
                return;
            }
        }

        // Prepara dati extra (tipo per moduli tipizzati)
        $extraData = [];
        if (!empty($type)) {
            $extraData['type'] = $type;
        }

        // Attiva il modulo
        $moduleProjectId = $this->project->activateModule($id, $moduleSlug, $user['id'], $extraData);

        if ($moduleProjectId) {
            $_SESSION['_flash']['success'] = $moduleConfig[$moduleSlug]['label'] . ' attivato con successo!';
            $routePrefix = $moduleConfig[$moduleSlug]['route_prefix'];
            $routeUrl = $routePrefix . '/' . $moduleProjectId;
            if (!empty($moduleConfig[$moduleSlug]['type_in_route']) && !empty($type)) {
                $routeUrl .= '/' . $type;
            }
            Router::redirect($routeUrl);
        } else {
            $_SESSION['_flash']['error'] = 'Errore nell\'attivazione del modulo';
            Router::redirect('/projects/' . $id);
        }
    }

    /**
     * Aggiunge nuovo sito WordPress e lo collega al progetto (solo owner)
     * POST /projects/{id}/wp-sites
     */
    public function addWpSite(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || $project['access_role'] !== 'owner') {
            $_SESSION['_flash']['error'] = 'Solo il proprietario puo gestire i siti WordPress';
            Router::redirect('/projects/' . $id);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');

        if (empty($name) || empty($url) || empty($apiKey)) {
            $_SESSION['_flash']['error'] = 'Compilare tutti i campi obbligatori';
            Router::redirect('/projects/' . $id);
            return;
        }

        // Normalizza URL
        $url = rtrim($url, '/');
        if (!preg_match('#^https?://#', $url)) {
            $url = 'https://' . $url;
        }

        $wpSiteModel = new \Modules\AiContent\Models\WpSite();

        // Verifica duplicato
        if ($wpSiteModel->urlExists($url, $user['id'])) {
            $_SESSION['_flash']['error'] = 'Questo sito WordPress è già collegato';
            Router::redirect('/projects/' . $id);
            return;
        }

        // Test connessione
        try {
            $connector = new \Services\Connectors\WordPressSeoConnector([
                'url' => $url,
                'api_key' => $apiKey,
            ]);
            $testResult = $connector->test();

            if (!$testResult['success']) {
                $_SESSION['_flash']['error'] = 'Connessione fallita: ' . ($testResult['message'] ?? 'Errore sconosciuto');
                Router::redirect('/projects/' . $id);
                return;
            }

            $wpSiteModel->create([
                'user_id' => $user['id'],
                'global_project_id' => $id,
                'name' => $name,
                'url' => $url,
                'api_key' => $apiKey,
            ]);

            $_SESSION['_flash']['success'] = 'Sito WordPress collegato con successo';
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        Router::redirect('/projects/' . $id);
    }

    /**
     * Collega sito WordPress esistente al progetto (solo owner)
     * POST /projects/{id}/wp-sites/link
     */
    public function linkWpSite(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || $project['access_role'] !== 'owner') {
            $_SESSION['_flash']['error'] = 'Solo il proprietario puo gestire i siti WordPress';
            Router::redirect('/projects/' . $id);
            return;
        }

        $siteId = (int) ($_POST['site_id'] ?? 0);
        $wpSiteModel = new \Modules\AiContent\Models\WpSite();
        $site = $wpSiteModel->find($siteId, $user['id']);

        if (!$site) {
            $_SESSION['_flash']['error'] = 'Sito WordPress non trovato';
            Router::redirect('/projects/' . $id);
            return;
        }

        $linked = $wpSiteModel->linkToProject($siteId, $id, $user['id']);
        if (!$linked) {
            $_SESSION['_flash']['error'] = 'Errore nel collegamento del sito';
            Router::redirect('/projects/' . $id);
            return;
        }

        // Auto-test connessione dopo il link
        try {
            $connector = new \Services\Connectors\WordPressSeoConnector([
                'url' => $site['url'],
                'api_key' => $site['api_key'],
            ]);
            $testResult = $connector->test();

            if ($testResult['success']) {
                $wpSiteModel->updateTestStatus($siteId, 'success');
                $wpSiteModel->update($siteId, ['last_sync_at' => date('Y-m-d H:i:s')], $user['id']);
                // Sync categorie
                $this->syncWpCategories($siteId, $site['url'], $site['api_key'], $wpSiteModel);
                $_SESSION['_flash']['success'] = 'Sito "' . $site['name'] . '" collegato e connessione verificata';
            } else {
                $wpSiteModel->updateTestStatus($siteId, 'error');
                $errorMsg = $testResult['message'] ?? 'Errore sconosciuto';
                $_SESSION['_flash']['warning'] = 'Sito collegato ma connessione fallita: ' . $errorMsg . '. Verifica URL e API Key.';
            }
        } catch (\Exception $e) {
            $wpSiteModel->updateTestStatus($siteId, 'error');
            $_SESSION['_flash']['warning'] = 'Sito collegato ma test connessione fallito: ' . $e->getMessage();
        }

        Router::redirect('/projects/' . $id);
    }

    /**
     * Scollega sito WordPress dal progetto (solo owner)
     * POST /projects/{id}/wp-sites/unlink
     */
    public function unlinkWpSite(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || $project['access_role'] !== 'owner') {
            $_SESSION['_flash']['error'] = 'Solo il proprietario puo gestire i siti WordPress';
            Router::redirect('/projects/' . $id);
            return;
        }

        $siteId = (int) ($_POST['site_id'] ?? 0);
        $wpSiteModel = new \Modules\AiContent\Models\WpSite();
        $site = $wpSiteModel->find($siteId, $user['id']);

        if (!$site || (int) ($site['global_project_id'] ?? 0) !== $id) {
            $_SESSION['_flash']['error'] = 'Sito non trovato';
            Router::redirect('/projects/' . $id);
            return;
        }

        $wpSiteModel->unlinkFromProject($siteId, $user['id']);
        $_SESSION['_flash']['success'] = 'Sito scollegato dal progetto';
        Router::redirect('/projects/' . $id);
    }

    /**
     * Testa connessione di un sito WordPress (AJAX, solo owner)
     * POST /projects/{id}/wp-sites/test
     */
    public function testWpSite(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        header('Content-Type: application/json');

        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || $project['access_role'] !== 'owner') {
            echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
            exit;
        }

        $siteId = (int) ($_POST['site_id'] ?? 0);
        $wpSiteModel = new \Modules\AiContent\Models\WpSite();
        $site = $wpSiteModel->find($siteId, $user['id']);

        if (!$site) {
            echo json_encode(['success' => false, 'message' => 'Sito non trovato']);
            exit;
        }

        try {
            $connector = new \Services\Connectors\WordPressSeoConnector([
                'url' => $site['url'],
                'api_key' => $site['api_key'],
            ]);
            $result = $connector->test();

            if ($result['success']) {
                $wpSiteModel->updateTestStatus($siteId, 'success');
                $wpSiteModel->update($siteId, ['last_sync_at' => date('Y-m-d H:i:s')], $user['id']);
                // Sync categorie automaticamente
                $this->syncWpCategories($siteId, $site['url'], $site['api_key'], $wpSiteModel);

                echo json_encode([
                    'success' => true,
                    'message' => 'Connessione riuscita',
                    'details' => $result['details'] ?? [],
                    'site_name' => $result['site_name'] ?? '',
                    'wp_version' => $result['wp_version'] ?? '',
                    'plugin_version' => $result['plugin_version'] ?? '',
                    'seo_plugin' => $result['seo_plugin'] ?? 'none',
                ]);
            } else {
                $wpSiteModel->updateTestStatus($siteId, 'error');
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Connessione fallita',
                ]);
            }
        } catch (\Exception $e) {
            $wpSiteModel->updateTestStatus($siteId, 'error');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Aggiorna sito WordPress (nome/api_key) — AJAX, solo owner
     * POST /projects/{id}/wp-sites/update
     */
    public function updateWpSite(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        header('Content-Type: application/json');

        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || $project['access_role'] !== 'owner') {
            echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
            exit;
        }

        $siteId = (int) ($_POST['site_id'] ?? 0);
        $wpSiteModel = new \Modules\AiContent\Models\WpSite();
        $site = $wpSiteModel->find($siteId, $user['id']);

        if (!$site) {
            echo json_encode(['success' => false, 'message' => 'Sito non trovato']);
            exit;
        }

        $updateData = [];
        $newName = trim($_POST['name'] ?? '');
        $newApiKey = trim($_POST['api_key'] ?? '');

        if (!empty($newName)) {
            $updateData['name'] = $newName;
        }

        $apiKeyChanged = false;
        if (!empty($newApiKey) && $newApiKey !== $site['api_key']) {
            $updateData['api_key'] = $newApiKey;
            $apiKeyChanged = true;
        }

        if (empty($updateData)) {
            echo json_encode(['success' => false, 'message' => 'Nessuna modifica da salvare']);
            exit;
        }

        try {
            $wpSiteModel->update($siteId, $updateData, $user['id']);

            // Se API key cambiata, ritesta connessione
            $testResult = null;
            if ($apiKeyChanged) {
                $connector = new \Services\Connectors\WordPressSeoConnector([
                    'url' => $site['url'],
                    'api_key' => $newApiKey,
                ]);
                $testResult = $connector->test();

                if ($testResult['success']) {
                    $wpSiteModel->updateTestStatus($siteId, 'success');
                    $wpSiteModel->update($siteId, ['last_sync_at' => date('Y-m-d H:i:s')], $user['id']);
                    $this->syncWpCategories($siteId, $site['url'], $newApiKey, $wpSiteModel);
                } else {
                    $wpSiteModel->updateTestStatus($siteId, 'error');
                }
            }

            $response = [
                'success' => true,
                'message' => 'Sito aggiornato' . ($apiKeyChanged && $testResult ? ($testResult['success'] ? ' e connessione verificata' : ' ma connessione fallita') : ''),
            ];

            if ($testResult) {
                $response['test'] = $testResult;
            }

            echo json_encode($response);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Sync categorie da sito WordPress (helper interno)
     */
    private function syncWpCategories(int $siteId, string $url, string $apiKey, \Modules\AiContent\Models\WpSite $wpSiteModel): void
    {
        try {
            $connector = new \Services\Connectors\WordPressSeoConnector([
                'url' => $url,
                'api_key' => $apiKey,
            ]);
            // Usa endpoint categories — fetch manuale perche' il connector non ha il metodo
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => rtrim($url, '/') . '/wp-json/seo-toolkit/v1/categories',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-SEO-Toolkit-Key: ' . $apiKey,
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response && $httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                $categories = $data['categories'] ?? $data ?? [];
                if (is_array($categories) && !empty($categories)) {
                    $wpSiteModel->updateCategoriesCache($siteId, $categories);
                }
            }
        } catch (\Exception $e) {
            // Sync categorie non critico — ignora errori
        }
    }

    /**
     * Testa connessione sito WordPress — AJAX, non project-scoped
     * POST /wp-sites/test
     */
    public function testWpSiteGlobal(): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        header('Content-Type: application/json');

        $siteId = (int) ($_POST['site_id'] ?? 0);
        $wpSiteModel = new \Modules\AiContent\Models\WpSite();
        $site = $wpSiteModel->find($siteId, $user['id']);

        if (!$site) {
            echo json_encode(['success' => false, 'message' => 'Sito non trovato']);
            exit;
        }

        try {
            $connector = new \Services\Connectors\WordPressSeoConnector([
                'url' => $site['url'],
                'api_key' => $site['api_key'],
            ]);
            $result = $connector->test();

            if ($result['success']) {
                $wpSiteModel->updateTestStatus($siteId, 'success');
                $wpSiteModel->update($siteId, ['last_sync_at' => date('Y-m-d H:i:s')], $user['id']);
                $this->syncWpCategories($siteId, $site['url'], $site['api_key'], $wpSiteModel);

                echo json_encode([
                    'success' => true,
                    'message' => 'Connessione riuscita',
                    'wp_version' => $result['wp_version'] ?? '',
                    'plugin_version' => $result['plugin_version'] ?? '',
                    'seo_plugin' => $result['seo_plugin'] ?? 'none',
                ]);
            } else {
                $wpSiteModel->updateTestStatus($siteId, 'error');
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Connessione fallita',
                ]);
            }
        } catch (\Exception $e) {
            $wpSiteModel->updateTestStatus($siteId, 'error');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Elimina sito WordPress — POST con form submit
     * POST /wp-sites/delete
     */
    public function deleteWpSite(): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $siteId = (int) ($_POST['site_id'] ?? 0);
        $wpSiteModel = new \Modules\AiContent\Models\WpSite();
        $site = $wpSiteModel->find($siteId, $user['id']);

        if (!$site) {
            $_SESSION['_flash']['error'] = 'Sito non trovato o non autorizzato';
            Router::redirect('/projects');
            return;
        }

        // Scollega dal progetto se collegato
        if (!empty($site['global_project_id'])) {
            $wpSiteModel->unlinkFromProject($siteId, $user['id']);
        }

        // Elimina
        $deleted = $wpSiteModel->delete($siteId, $user['id']);

        if ($deleted) {
            $_SESSION['_flash']['success'] = 'Sito "' . ($site['name'] ?: parse_url($site['url'], PHP_URL_HOST)) . '" eliminato';
        } else {
            $_SESSION['_flash']['error'] = 'Errore nell\'eliminazione del sito';
        }

        Router::redirect('/projects');
    }

    /**
     * Download plugin WordPress come ZIP
     * GET /projects/download-plugin/wordpress
     */
    public function downloadPlugin(): void
    {
        Middleware::auth();

        $pluginDir = __DIR__ . '/../storage/plugins/seo-toolkit-connector';
        $zipFile = tempnam(sys_get_temp_dir(), 'seo-toolkit-connector') . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $_SESSION['_flash']['error'] = 'Errore nella creazione del pacchetto';
            Router::redirect('/projects');
            return;
        }

        $files = glob($pluginDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $zip->addFile($file, 'seo-toolkit-connector/' . basename($file));
            }
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="seo-toolkit-connector.zip"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        unlink($zipFile);
        exit;
    }

    // =========================================
    // PROJECT SHARING
    // =========================================

    /**
     * Pagina gestione condivisione progetto
     * GET /projects/{id}/sharing
     */
    public function sharing(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || $project['access_role'] !== 'owner') {
            $_SESSION['_flash']['error'] = 'Accesso non autorizzato';
            Router::redirect('/projects');
            return '';
        }

        $members = \Services\ProjectAccessService::getProjectMembers($id);
        $invitations = \Services\ProjectAccessService::getProjectInvitations($id);

        // Deduplica moduli per slug (getActiveModules puo avere piu righe per moduli con tipi)
        $allModules = $this->project->getActiveModules($id);
        $activeModules = [];
        $seen = [];
        foreach ($allModules as $mod) {
            if (!isset($seen[$mod['slug']])) {
                $seen[$mod['slug']] = true;
                $activeModules[] = $mod;
            }
        }

        return View::render('projects/sharing', [
            'title' => 'Condivisione - ' . ($project['name'] ?? ''),
            'user' => $user,
            'project' => $project,
            'members' => $members,
            'invitations' => $invitations,
            'activeModules' => $activeModules,
            'modules' => ModuleLoader::getActiveModules()
        ]);
    }

    /**
     * Invita membro al progetto
     * POST /projects/{id}/sharing/invite
     */
    public function invite(int $id): string
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        // Solo il proprietario puo invitare
        if (!\Services\ProjectAccessService::isOwner($id, $user['id'])) {
            $_SESSION['_flash']['error'] = 'Solo il proprietario puo invitare membri';
            Router::redirect('/projects/' . $id);
            return '';
        }

        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'viewer';
        $moduleSlugs = $_POST['modules'] ?? [];

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['_flash']['error'] = 'Inserisci un indirizzo email valido';
            Router::redirect('/projects/' . $id . '/sharing');
            return '';
        }

        if (empty($moduleSlugs)) {
            $_SESSION['_flash']['error'] = 'Seleziona almeno un modulo da condividere';
            Router::redirect('/projects/' . $id . '/sharing');
            return '';
        }

        $result = \Services\ProjectSharingService::invite($id, $email, $role, $moduleSlugs, $user['id']);

        $_SESSION['_flash'][$result['success'] ? 'success' : 'error'] = $result['message'];
        Router::redirect('/projects/' . $id . '/sharing');
        return '';
    }

    /**
     * Rimuovi membro dal progetto
     * POST /projects/{id}/sharing/remove/{userId}
     */
    public function removeMember(int $id, int $memberUserId): string
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        if (!\Services\ProjectAccessService::isOwner($id, $user['id'])) {
            $_SESSION['_flash']['error'] = 'Accesso non autorizzato';
            Router::redirect('/projects/' . $id);
            return '';
        }

        $result = \Services\ProjectSharingService::removeMember($id, $memberUserId);
        $_SESSION['_flash'][$result['success'] ? 'success' : 'error'] = $result['message'];
        Router::redirect('/projects/' . $id . '/sharing');
        return '';
    }

    /**
     * Aggiorna ruolo/moduli membro
     * POST /projects/{id}/sharing/update/{userId}
     */
    public function updateMember(int $id, int $memberUserId): string
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        if (!\Services\ProjectAccessService::isOwner($id, $user['id'])) {
            $_SESSION['_flash']['error'] = 'Accesso non autorizzato';
            Router::redirect('/projects/' . $id);
            return '';
        }

        $role = $_POST['role'] ?? 'viewer';
        $moduleSlugs = $_POST['modules'] ?? [];

        $result = \Services\ProjectSharingService::updateMember($id, $memberUserId, $role, $moduleSlugs);
        $_SESSION['_flash'][$result['success'] ? 'success' : 'error'] = $result['message'];
        Router::redirect('/projects/' . $id . '/sharing');
        return '';
    }

    /**
     * Annulla invito in sospeso
     * POST /projects/{id}/sharing/cancel-invite/{inviteId}
     */
    public function cancelInvitation(int $id, int $invitationId): string
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        if (!\Services\ProjectAccessService::isOwner($id, $user['id'])) {
            $_SESSION['_flash']['error'] = 'Accesso non autorizzato';
            Router::redirect('/projects/' . $id);
            return '';
        }

        $result = \Services\ProjectSharingService::cancelInvitation($invitationId, $id);
        $_SESSION['_flash'][$result['success'] ? 'success' : 'error'] = $result['message'];
        Router::redirect('/projects/' . $id . '/sharing');
        return '';
    }

    /**
     * Accetta invito tramite token (link email)
     * GET /invite/accept?token=xxx
     */
    public function acceptInviteByToken(): string
    {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            $_SESSION['_flash']['error'] = 'Token invito mancante';
            Router::redirect('/login');
            return '';
        }

        if (!Auth::check()) {
            $_SESSION['invite_token'] = $token;
            $_SESSION['_flash']['info'] = 'Accedi o registrati per accettare l\'invito';
            Router::redirect('/login');
            return '';
        }

        $user = Auth::user();
        $result = \Services\ProjectSharingService::acceptByToken($token, $user['id']);

        if ($result['success']) {
            $_SESSION['_flash']['success'] = $result['message'];
            $redirectTo = isset($result['project_id']) ? '/projects/' . $result['project_id'] : '/projects';
            Router::redirect($redirectTo);
        } else {
            $_SESSION['_flash']['error'] = $result['message'];
            Router::redirect('/projects');
        }
        return '';
    }

    /**
     * Accetta invito interno (notifica in-app)
     * POST /invite/{id}/accept
     */
    public function acceptInternalInvite(int $memberId): string
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $result = \Services\ProjectSharingService::acceptInternal($memberId, $user['id']);
        $_SESSION['_flash'][$result['success'] ? 'success' : 'error'] = $result['message'];

        if ($result['success'] && isset($result['project_id'])) {
            Router::redirect('/projects/' . $result['project_id']);
        } else {
            Router::redirect('/projects');
        }
        return '';
    }

    /**
     * Rifiuta invito interno (notifica in-app)
     * POST /invite/{id}/decline
     */
    public function declineInternalInvite(int $memberId): string
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $result = \Services\ProjectSharingService::declineInternal($memberId, $user['id']);
        $_SESSION['_flash'][$result['success'] ? 'success' : 'error'] = $result['message'];
        Router::redirect('/projects');
        return '';
    }

    /**
     * Elimina progetto (solo owner)
     * POST /projects/{id}/delete
     */
    public function destroy(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project || $project['access_role'] !== 'owner') {
            $_SESSION['_flash']['error'] = 'Solo il proprietario puo eliminare il progetto';
            Router::redirect('/projects/' . $id);
            return;
        }

        try {
            $this->project->delete($id, $user['id']);

            $_SESSION['_flash']['success'] = 'Progetto eliminato con successo';
            Router::redirect('/projects');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nell\'eliminazione: ' . $e->getMessage();
            Router::redirect('/projects/' . $id . '/settings');
        }
    }
}
