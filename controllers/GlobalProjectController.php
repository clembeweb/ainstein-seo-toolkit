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
     * Lista progetti globali
     * GET /projects
     */
    public function index(): string
    {
        Middleware::auth();
        $user = Auth::user();

        $projects = $this->project->allWithModuleStats($user['id']);

        return View::render('projects/index', [
            'title' => 'Progetti',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projects' => $projects,
        ]);
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

        $project = $this->project->find($id, $user['id']);

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

        // Load CMS connectors for this project
        $connectorModel = new \Core\Models\ProjectConnector();
        $connectors = $connectorModel->getByProject($id);

        return View::render('projects/dashboard', [
            'title' => $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'activeModules' => $activeModules,
            'moduleStats' => $moduleStats,
            'availableModules' => $availableModules,
            'moduleConfig' => $moduleConfig,
            'moduleTypes' => $moduleTypes,
            'remainingTypes' => $remainingTypes,
            'activeTypesPerModule' => $activeTypesPerModule,
            'connectors' => $connectors,
        ]);
    }

    /**
     * Impostazioni progetto
     * GET /projects/{id}/settings
     */
    public function settings(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/projects');
            return '';
        }

        return View::render('projects/settings', [
            'title' => $project['name'] . ' - Impostazioni',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
        ]);
    }

    /**
     * Aggiorna progetto
     * POST /projects/{id}/settings
     */
    public function update(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/projects');
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
     * Attiva modulo per progetto globale
     * POST /projects/{id}/activate-module
     */
    public function activateModule(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/projects');
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
     * Aggiunge connettore CMS al progetto
     * POST /projects/{id}/connectors
     */
    public function addConnector(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->find($id, $user['id']);
        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/projects');
            return;
        }

        $type = trim($_POST['type'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');

        if (empty($type) || empty($name) || empty($url) || empty($apiKey)) {
            $_SESSION['_flash']['error'] = 'Compilare tutti i campi obbligatori';
            Router::redirect('/projects/' . $id);
            return;
        }

        // Test connection before saving
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

            $connectorModel = new \Core\Models\ProjectConnector();
            $connectorModel->create([
                'project_id' => $id,
                'user_id' => $user['id'],
                'type' => $type,
                'name' => $name,
                'config' => ['url' => $url, 'api_key' => $apiKey],
                'seo_plugin' => $testResult['seo_plugin'] ?? null,
                'wp_version' => $testResult['wp_version'] ?? null,
                'plugin_version' => $testResult['plugin_version'] ?? null,
            ]);

            $_SESSION['_flash']['success'] = 'Connettore WordPress aggiunto con successo';
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        Router::redirect('/projects/' . $id);
    }

    /**
     * Testa connessione di un connettore esistente (AJAX)
     * POST /projects/{id}/connectors/test
     */
    public function testConnector(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        header('Content-Type: application/json');

        $connectorId = (int) ($_POST['connector_id'] ?? 0);
        $connectorModel = new \Core\Models\ProjectConnector();
        $connector = $connectorModel->findForUser($connectorId, $user['id']);

        if (!$connector || $connector['project_id'] != $id) {
            echo json_encode(['success' => false, 'message' => 'Connettore non trovato']);
            exit;
        }

        $config = json_decode($connector['config'], true);

        try {
            $service = new \Services\Connectors\WordPressSeoConnector($config);
            $result = $service->test();

            // Update test status in DB
            $connectorModel->updateTestStatus(
                $connectorId,
                $result['success'] ? 'success' : 'error',
                $result['message'] ?? null,
                $result['success'] ? [
                    'seo_plugin' => $result['seo_plugin'] ?? null,
                    'wp_version' => $result['wp_version'] ?? null,
                    'plugin_version' => $result['plugin_version'] ?? null,
                ] : null
            );

            echo json_encode($result);
        } catch (\Exception $e) {
            $connectorModel->updateTestStatus($connectorId, 'error', $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Rimuove connettore dal progetto
     * POST /projects/{id}/connectors/remove
     */
    public function removeConnector(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $connectorId = (int) ($_POST['connector_id'] ?? 0);
        $connectorModel = new \Core\Models\ProjectConnector();
        $connector = $connectorModel->findForUser($connectorId, $user['id']);

        if (!$connector || $connector['project_id'] != $id) {
            $_SESSION['_flash']['error'] = 'Connettore non trovato';
            Router::redirect('/projects/' . $id);
            return;
        }

        $connectorModel->delete($connectorId);
        $_SESSION['_flash']['success'] = 'Connettore rimosso';
        Router::redirect('/projects/' . $id);
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

    /**
     * Elimina progetto
     * POST /projects/{id}/delete
     */
    public function destroy(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/projects');
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
