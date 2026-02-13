<?php

namespace Modules\ContentCreator\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\ContentCreator\Models\Connector;
use Modules\ContentCreator\Services\Connectors\ConnectorInterface;
use Modules\ContentCreator\Services\Connectors\WordPressConnector;
use Modules\ContentCreator\Services\Connectors\ShopifyConnector;
use Modules\ContentCreator\Services\Connectors\PrestaShopConnector;
use Modules\ContentCreator\Services\Connectors\MagentoConnector;

class ConnectorController
{
    private Connector $connector;

    public function __construct()
    {
        $this->connector = new Connector();
    }

    /**
     * Lista connettori utente
     * GET /content-creator/connectors
     */
    public function index(): string
    {
        $user = Auth::user();
        $connectors = $this->connector->allByUser($user['id']);

        return View::render('content-creator/connectors/index', [
            'title' => 'Connettori CMS - Content Creator',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'connectors' => $connectors,
        ]);
    }

    /**
     * Form creazione connettore
     * GET /content-creator/connectors/create
     */
    public function create(): string
    {
        $user = Auth::user();

        return View::render('content-creator/connectors/create', [
            'title' => 'Nuovo Connettore - Content Creator',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Salva nuovo connettore
     * POST /content-creator/connectors
     */
    public function store(): void
    {
        $user = Auth::user();

        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');

        // Validazione base
        $errors = [];
        if (empty($name)) {
            $errors[] = 'Il nome del connettore è obbligatorio';
        }

        $validTypes = ['wordpress', 'shopify', 'prestashop', 'magento', 'custom_api'];
        if (!in_array($type, $validTypes)) {
            $errors[] = 'Tipo di connettore non valido';
        }

        // Build config JSON in base al tipo
        $config = [];

        if ($type === 'wordpress') {
            $config = [
                'url' => trim($_POST['wp_url'] ?? ''),
                'username' => trim($_POST['wp_username'] ?? ''),
                'application_password' => trim($_POST['wp_application_password'] ?? ''),
            ];
            if (empty($config['url'])) {
                $errors[] = 'URL del sito WordPress è obbligatorio';
            }
            if (empty($config['username'])) {
                $errors[] = 'Username WordPress è obbligatorio';
            }
            if (empty($config['application_password'])) {
                $errors[] = 'Application Password è obbligatoria';
            }
        } elseif ($type === 'shopify') {
            $config = [
                'store_url' => trim($_POST['shopify_store_url'] ?? ''),
                'access_token' => trim($_POST['shopify_access_token'] ?? ''),
            ];
            if (empty($config['store_url'])) {
                $errors[] = 'URL del negozio Shopify è obbligatorio';
            }
            if (empty($config['access_token'])) {
                $errors[] = 'Access Token Shopify è obbligatorio';
            }
        } elseif ($type === 'prestashop') {
            $config = [
                'url' => trim($_POST['ps_url'] ?? ''),
                'api_key' => trim($_POST['ps_api_key'] ?? ''),
            ];
            if (empty($config['url'])) {
                $errors[] = 'URL del negozio PrestaShop è obbligatorio';
            }
            if (empty($config['api_key'])) {
                $errors[] = 'API Key Webservice è obbligatoria';
            }
        } elseif ($type === 'magento') {
            $config = [
                'url' => trim($_POST['magento_url'] ?? ''),
                'access_token' => trim($_POST['magento_access_token'] ?? ''),
            ];
            if (empty($config['url'])) {
                $errors[] = 'URL Magento è obbligatorio';
            }
            if (empty($config['access_token'])) {
                $errors[] = 'Access Token Magento è obbligatorio';
            }
        } elseif ($type === 'custom_api') {
            $config = [
                'url' => trim($_POST['custom_url'] ?? ''),
                'api_key' => trim($_POST['custom_api_key'] ?? ''),
                'headers_json' => trim($_POST['custom_headers_json'] ?? ''),
            ];
            if (empty($config['url'])) {
                $errors[] = 'URL API Base è obbligatorio';
            }
            // Validate headers JSON if provided
            if (!empty($config['headers_json'])) {
                $decoded = json_decode($config['headers_json'], true);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = 'Headers custom non è un JSON valido';
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/content-creator/connectors/create');
            return;
        }

        try {
            $this->connector->create([
                'user_id' => $user['id'],
                'name' => $name,
                'type' => $type,
                'config' => $config,
            ]);

            $_SESSION['_flash']['success'] = 'Connettore creato con successo!';
            Router::redirect('/content-creator/connectors');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nella creazione: ' . $e->getMessage();
            Router::redirect('/content-creator/connectors/create');
        }
    }

    /**
     * Test connessione connettore
     * POST /content-creator/connectors/{id}/test (AJAX)
     */
    public function test(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $connector = $this->connector->findByUser($id, $user['id']);

        if (!$connector) {
            echo json_encode(['success' => false, 'message' => 'Connettore non trovato']);
            return;
        }

        $config = json_decode($connector['config'] ?? '{}', true);
        if (!is_array($config)) {
            $config = [];
        }

        try {
            $service = $this->createConnectorService($connector['type'], $config);
            $result = $service->test();

            $this->connector->updateTestStatus($id, $result['success'] ? 'success' : 'error');

            echo json_encode($result);

        } catch (\Exception $e) {
            $this->connector->updateTestStatus($id, 'error');
            echo json_encode([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Elimina connettore
     * POST /content-creator/connectors/{id}/delete (AJAX)
     */
    public function delete(int $id): void
    {
        $user = Auth::user();
        $connector = $this->connector->findByUser($id, $user['id']);

        if (!$connector) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Connettore non trovato']);
                return;
            }
            $_SESSION['_flash']['error'] = 'Connettore non trovato';
            Router::redirect('/content-creator/connectors');
            return;
        }

        $this->connector->delete($id);

        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Connettore eliminato']);
            return;
        }

        $_SESSION['_flash']['success'] = 'Connettore eliminato con successo';
        Router::redirect('/content-creator/connectors');
    }

    /**
     * Toggle attivo/inattivo
     * POST /content-creator/connectors/{id}/toggle (AJAX)
     */
    public function toggle(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $connector = $this->connector->findByUser($id, $user['id']);

        if (!$connector) {
            echo json_encode(['success' => false, 'message' => 'Connettore non trovato']);
            return;
        }

        $this->connector->toggleActive($id);

        // Reload to get new state
        $updated = $this->connector->find($id);
        $isActive = (bool) ($updated['is_active'] ?? false);

        echo json_encode([
            'success' => true,
            'is_active' => $isActive,
            'message' => $isActive ? 'Connettore attivato' : 'Connettore disattivato',
        ]);
    }

    /**
     * Factory: crea il servizio connettore appropriato
     */
    private function createConnectorService(string $type, array $config): ConnectorInterface
    {
        return match ($type) {
            'wordpress' => new WordPressConnector($config),
            'shopify' => new ShopifyConnector($config),
            'prestashop' => new PrestaShopConnector($config),
            'magento' => new MagentoConnector($config),
            default => throw new \Exception("Tipo connettore non supportato: {$type}"),
        };
    }

    /**
     * Check se la request è AJAX
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }
}
