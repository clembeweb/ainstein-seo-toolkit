<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Ga4Connection;
use Modules\SeoTracking\Services\Ga4Service;

/**
 * Ga4Controller
 * Gestisce connessione e sync Google Analytics 4
 */
class Ga4Controller
{
    private Project $project;
    private Ga4Connection $ga4Connection;
    private Ga4Service $ga4Service;

    public function __construct()
    {
        $this->project = new Project();
        $this->ga4Connection = new Ga4Connection();
        $this->ga4Service = new Ga4Service();
    }

    /**
     * Form connessione GA4
     */
    public function connect(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        // Verifica se gia connesso
        $connection = $this->ga4Connection->getByProject($id);

        return View::render('seo-tracking/ga4/connect', [
            'title' => 'Connetti Google Analytics 4',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'connection' => $connection,
        ]);
    }

    /**
     * Salva connessione GA4
     */
    public function store(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $propertyId = trim($_POST['property_id'] ?? '');

        // Validazione
        $errors = [];

        if (empty($propertyId)) {
            $errors[] = 'Il Property ID e obbligatorio';
        } elseif (!preg_match('/^\d+$/', $propertyId)) {
            $errors[] = 'Il Property ID deve essere numerico (es. 123456789)';
        }

        // Gestione file JSON
        $serviceAccountJson = null;

        if (isset($_FILES['service_account_file']) && $_FILES['service_account_file']['error'] === UPLOAD_ERR_OK) {
            $jsonContent = file_get_contents($_FILES['service_account_file']['tmp_name']);

            try {
                $this->ga4Service->validateServiceAccount($jsonContent);
                $serviceAccountJson = $jsonContent;
            } catch (\Exception $e) {
                $errors[] = 'File Service Account non valido: ' . $e->getMessage();
            }
        } elseif (!$this->ga4Connection->getByProject($id)) {
            // Richiedi file solo se non esiste connessione precedente
            $errors[] = 'Il file Service Account JSON e obbligatorio';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/seo-tracking/projects/' . $id . '/ga4/connect');
            return;
        }

        try {
            // Salva o aggiorna connessione
            $data = [
                'property_id' => $propertyId,
            ];

            if ($serviceAccountJson) {
                $data['service_account_json'] = $serviceAccountJson;
            }

            $this->ga4Connection->upsert($id, $data);

            // Verifica accesso alla property
            if (!$this->ga4Service->verifyPropertyAccess($id, $propertyId)) {
                $_SESSION['_flash']['error'] = 'Impossibile accedere alla property GA4. Verifica che il Service Account abbia i permessi corretti.';
                Router::redirect('/seo-tracking/projects/' . $id . '/ga4/connect');
                return;
            }

            // Marca come connesso
            $this->project->setGa4Connected($id, true);

            $_SESSION['_flash']['success'] = 'Google Analytics 4 connesso con successo!';
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nella connessione: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $id . '/ga4/connect');
        }
    }

    /**
     * Disconnetti GA4
     */
    public function disconnect(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $this->ga4Connection->delete($id);
        $this->project->setGa4Connected($id, false);

        $_SESSION['_flash']['success'] = 'Google Analytics 4 disconnesso';
        Router::redirect('/seo-tracking/projects/' . $id . '/settings');
    }

    /**
     * Sync manuale GA4
     */
    public function sync(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        if (!$project['ga4_connected']) {
            $_SESSION['_flash']['error'] = 'GA4 non connesso';
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
            return;
        }

        try {
            $result = $this->ga4Service->syncAnalytics($id);

            $_SESSION['_flash']['success'] = 'Sincronizzazione GA4 completata: ' . $result['records_fetched'] . ' record elaborati';
            Router::redirect('/seo-tracking/projects/' . $id . '/dashboard');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore sync GA4: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
        }
    }

    /**
     * Test connessione GA4
     */
    public function test(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $connection = $this->ga4Connection->getByProject($id);

        if (!$connection) {
            return View::json(['error' => 'Connessione GA4 non trovata'], 404);
        }

        try {
            $token = $this->ga4Service->getAccessToken($id);
            $verified = $this->ga4Service->verifyPropertyAccess($id, $connection['property_id']);

            return View::json([
                'success' => $verified,
                'message' => $verified ? 'Connessione verificata' : 'Impossibile accedere alla property',
            ]);

        } catch (\Exception $e) {
            return View::json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Stato connessione
     */
    public function status(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $connection = $this->ga4Connection->getByProject($id);

        return View::json([
            'connected' => (bool) $project['ga4_connected'],
            'property_id' => $connection['property_id'] ?? null,
            'last_sync' => $connection['last_sync_at'] ?? null,
        ]);
    }
}
