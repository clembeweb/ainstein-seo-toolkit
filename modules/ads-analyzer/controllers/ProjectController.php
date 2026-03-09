<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\Database;
use Core\Middleware;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Services\ValidationService;
use Services\GoogleOAuthService;
use Services\GoogleAdsService;

class ProjectController
{
    public function index(): string
    {
        $user = Auth::user();

        $type = $_GET['type'] ?? null;
        $validTypes = ['campaign', 'campaign-creator'];
        if ($type !== null && !in_array($type, $validTypes, true)) {
            $type = null;
        }

        $projectsByType = Project::allGroupedByType($user['id']);

        return View::render('ads-analyzer/projects/index', [
            'title' => 'Progetti - Google Ads Analyzer',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'campaignProjects' => $projectsByType['campaign'] ?? [],
            'creatorProjects' => $projectsByType['campaign-creator'] ?? [],
            'currentType' => $type,
        ]);
    }

    public function create(): string
    {
        $user = Auth::user();
        $preselectedType = $_GET['type'] ?? null;

        return View::render('ads-analyzer/projects/create', [
            'title' => 'Nuovo Progetto - Google Ads Analyzer',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'preselectedType' => $preselectedType,
        ]);
    }

    public function store(): void
    {
        $user = Auth::user();
        $type = $_POST['type'] ?? 'campaign';

        if ($type === 'campaign-creator') {
            $inputMode = $_POST['input_mode'] ?? 'url';
            if (!in_array($inputMode, ['url', 'brief', 'both'])) {
                $inputMode = 'url';
            }

            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'brief' => trim($_POST['brief'] ?? ''),
                'campaign_type_gads' => $_POST['campaign_type_gads'] ?? '',
                'landing_url' => trim($_POST['landing_url'] ?? ''),
                'input_mode' => $inputMode,
                'user_id' => $user['id'],
                'type' => 'campaign-creator',
            ];

            $errors = ValidationService::validateCampaignCreator($data);

            if (!empty($errors)) {
                $_SESSION['_flash']['error'] = implode(', ', $errors);
                $_SESSION['old_input'] = $data;
                header('Location: ' . url('/projects/create'));
                exit;
            }

            $projectId = Project::create($data);

            $_SESSION['_flash']['success'] = 'Progetto creato con successo';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaign-creator"));
            exit;
        }

        // Default: campaign (analisi)
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'user_id' => $user['id'],
            'type' => 'campaign',
        ];

        $errors = ValidationService::validateProject($data);

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode(', ', $errors);
            $_SESSION['old_input'] = $data;
            header('Location: ' . url('/projects/create'));
            exit;
        }

        $projectId = Project::create($data);

        $_SESSION['_flash']['success'] = 'Progetto creato con successo';
        header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaign-dashboard"));
        exit;
    }

    public function edit(int $id): string
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $id);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        // Edit: solo owner
        if (($project['access_role'] ?? 'owner') !== 'owner') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            header('Location: ' . url("/ads-analyzer/projects/{$id}"));
            exit;
        }

        return View::render('ads-analyzer/projects/edit', [
            'title' => 'Modifica ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'settings',
        ]);
    }

    public function update(int $id): void
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $id);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        // Update: solo owner
        if (($project['access_role'] ?? 'owner') !== 'owner') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            header('Location: ' . url("/ads-analyzer/projects/{$id}/edit"));
            exit;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? '')
        ];

        // Valida
        $errors = ValidationService::validateProject($data);

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode(', ', $errors);
            header('Location: ' . url("/ads-analyzer/projects/{$id}/edit"));
            exit;
        }

        Project::update($id, $data);

        $_SESSION['_flash']['success'] = 'Progetto aggiornato';
        header('Location: ' . url("/ads-analyzer/projects/{$id}"));
        exit;
    }

    public function destroy(int $id): void
    {
        $user = Auth::user();

        if (!Project::deleteByUser($user['id'], $id)) {
            $_SESSION['_flash']['error'] = 'Impossibile eliminare il progetto';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $_SESSION['_flash']['success'] = 'Progetto eliminato';
        header('Location: ' . url('/ads-analyzer'));
        exit;
    }

    public function duplicate(int $id): void
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $id);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $newProjectId = Project::create([
            'user_id' => $user['id'],
            'type' => 'campaign',
            'name' => $project['name'] . ' (copia)',
            'description' => $project['description'],
            'business_context' => $project['business_context'],
            'status' => 'draft'
        ]);

        $_SESSION['_flash']['success'] = 'Progetto duplicato';
        header('Location: ' . url("/ads-analyzer/projects/{$newProjectId}"));
        exit;
    }

    public function toggleArchive(int $id): void
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $id);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $newStatus = $project['status'] === 'archived' ? 'completed' : 'archived';
        Project::update($id, ['status' => $newStatus]);

        $message = $newStatus === 'archived' ? 'Progetto archiviato' : 'Progetto ripristinato';
        $_SESSION['_flash']['success'] = $message;
        header('Location: ' . url('/ads-analyzer'));
        exit;
    }

    /**
     * Avvia il flusso OAuth per connettere Google Ads
     */
    public function connectGoogleAds(int $projectId): void
    {
        Middleware::auth();
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        // Solo owner o editor possono connettere
        $role = $project['access_role'] ?? 'owner';
        if ($role === 'viewer') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaign-dashboard"));
            exit;
        }

        $oauth = new GoogleOAuthService();

        if (!$oauth->isConfigured()) {
            $_SESSION['_flash']['error'] = 'Credenziali Google non configurate. Contatta l\'amministratore.';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaign-dashboard"));
            exit;
        }

        $authUrl = $oauth->getAuthUrl('ads-analyzer', $projectId, GoogleOAuthService::SCOPE_GOOGLE_ADS, 'google_ads');
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * GET: mostra lista account Google Ads accessibili
     * POST: salva l'account selezionato
     */
    public function selectAccount(int $projectId): string|null
    {
        Middleware::auth();
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $role = $project['access_role'] ?? 'owner';
        if ($role === 'viewer') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaign-dashboard"));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->saveSelectedAccount($user, $project, $projectId);
        }

        // GET: carica token OAuth e lista account
        $token = Database::fetch(
            "SELECT access_token, refresh_token, token_expires_at
             FROM google_oauth_tokens
             WHERE user_id = ? AND service = 'google_ads'
             LIMIT 1",
            [$user['id']]
        );

        // Lista account accessibili (solo se OAuth token esiste)
        $accounts = [];
        $error = null;
        $hasOAuthToken = !empty($token);

        if ($hasOAuthToken) {
            try {
                $gads = new GoogleAdsService($user['id'], '0');
                $result = $gads->listAccessibleCustomers();

                $resourceNames = $result['resourceNames'] ?? [];
                $customerIds = [];
                foreach ($resourceNames as $resourceName) {
                    $customerIds[] = str_replace('customers/', '', $resourceName);
                }

                // 1. Recupera nomi figli MCC tramite customer_client
                $mccCustomerId = \Core\Settings::get('gads_mcc_customer_id', '');
                $mccClean = preg_replace('/[^0-9]/', '', $mccCustomerId);
                $accountNames = [];

                if (!empty($mccClean)) {
                    try {
                        $mccGads = new GoogleAdsService($user['id'], $mccClean);
                        $clientsResult = $mccGads->search(
                            "SELECT customer_client.id, customer_client.descriptive_name, customer_client.manager, customer_client.currency_code, customer_client.status FROM customer_client WHERE customer_client.level <= 1"
                        );
                        foreach (($clientsResult['results'] ?? []) as $row) {
                            $cc = $row['customerClient'] ?? [];
                            $ccId = (string)($cc['id'] ?? '');
                            if (!empty($ccId)) {
                                $accountNames[$ccId] = [
                                    'name' => $cc['descriptiveName'] ?? '',
                                    'is_manager' => !empty($cc['manager']),
                                    'currency' => $cc['currencyCode'] ?? '',
                                    'status' => $cc['status'] ?? '',
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        // MCC query fallita, proveremo query dirette
                    }
                }

                // 2. Per account non trovati nell'MCC, query diretta senza login-customer-id
                foreach ($customerIds as $customerId) {
                    if (!isset($accountNames[$customerId])) {
                        try {
                            $directGads = new GoogleAdsService($user['id'], $customerId, '');
                            $directResult = $directGads->search(
                                "SELECT customer.descriptive_name, customer.manager, customer.currency_code, customer.status FROM customer LIMIT 1"
                            );
                            $row = ($directResult['results'] ?? [])[0] ?? [];
                            $c = $row['customer'] ?? [];
                            $accountNames[$customerId] = [
                                'name' => $c['descriptiveName'] ?? '',
                                'is_manager' => !empty($c['manager']),
                                'currency' => $c['currencyCode'] ?? '',
                                'status' => $c['status'] ?? '',
                            ];
                        } catch (\Exception $e) {
                            // Account non accessibile, continua senza dettagli
                        }
                    }
                }

                // 3. Costruisci lista account
                foreach ($customerIds as $customerId) {
                    $info = $accountNames[$customerId] ?? [];
                    $displayId = substr($customerId, 0, 3) . '-' . substr($customerId, 3, 3) . '-' . substr($customerId, 6);
                    $accounts[] = [
                        'customer_id' => $customerId,
                        'display_id' => $displayId,
                        'name' => ($info['name'] ?? '') ?: 'Account ' . $displayId,
                        'is_manager' => $info['is_manager'] ?? false,
                        'currency' => $info['currency'] ?? '',
                        'status' => $info['status'] ?? '',
                    ];
                }
            } catch (\Exception $e) {
                $error = 'Impossibile recuperare gli account Google Ads: ' . $e->getMessage();
            }
        }

        // Genera URL per OAuth connect
        $connectUrl = '';
        try {
            $oauth = new GoogleOAuthService();
            if ($oauth->isConfigured()) {
                $connectUrl = $oauth->getAuthUrl('ads-analyzer', $projectId, GoogleOAuthService::SCOPE_GOOGLE_ADS, 'google_ads');
            }
        } catch (\Exception $e) {
            // OAuth non configurato
        }

        return View::render('ads-analyzer/campaigns/connect', [
            'title' => 'Connessione Google Ads',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'accounts' => $accounts,
            'error' => $error,
            'hasOAuthToken' => $hasOAuthToken,
            'connectUrl' => $connectUrl,
            'isConnected' => !empty($project['google_ads_customer_id']),
            'connectionInfo' => [
                'google_ads_customer_id' => $project['google_ads_customer_id'] ?? null,
                'google_ads_account_name' => $project['google_ads_account_name'] ?? null,
                'last_sync_at' => $project['last_sync_at'] ?? null,
            ],
            'currentPage' => 'connect',
        ]);
    }

    /**
     * Salva l'account Google Ads selezionato (POST handler)
     */
    private function saveSelectedAccount(array $user, array $project, int $projectId): null
    {
        $customerId = trim($_POST['customer_id'] ?? '');
        $accountName = trim($_POST['account_name'] ?? '');

        if (empty($customerId)) {
            $_SESSION['_flash']['error'] = 'Seleziona un account Google Ads';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/google-ads/select-account"));
            exit;
        }

        // Verifica che il customer_id sia nella lista degli account accessibili
        try {
            $gads = new GoogleAdsService($user['id'], '0');
            $result = $gads->listAccessibleCustomers();
            $resourceNames = $result['resourceNames'] ?? [];

            $validIds = array_map(function ($rn) {
                return str_replace('customers/', '', $rn);
            }, $resourceNames);

            $cleanCustomerId = preg_replace('/[^0-9]/', '', $customerId);

            if (!in_array($cleanCustomerId, $validIds, true)) {
                $_SESSION['_flash']['error'] = 'Account non valido o non accessibile';
                header('Location: ' . url("/ads-analyzer/projects/{$projectId}/google-ads/select-account"));
                exit;
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nella verifica dell\'account: ' . $e->getMessage();
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/google-ads/select-account"));
            exit;
        }

        // Determina login_customer_id: verifica se l'account è sotto l'MCC
        $mccCustomerId = \Core\Settings::get('gads_mcc_customer_id', '');
        $mccClean = preg_replace('/[^0-9]/', '', $mccCustomerId);
        $loginCustomerId = null; // default: nessun login-customer-id

        if (!empty($mccClean)) {
            try {
                $mccGads = new GoogleAdsService($user['id'], $mccClean);
                $clientsResult = $mccGads->search(
                    "SELECT customer_client.id FROM customer_client WHERE customer_client.level <= 1"
                );
                $mccChildIds = [];
                foreach (($clientsResult['results'] ?? []) as $row) {
                    $mccChildIds[] = (string)($row['customerClient']['id'] ?? '');
                }
                if (in_array($cleanCustomerId, $mccChildIds, true) || $cleanCustomerId === $mccClean) {
                    $loginCustomerId = $mccClean; // Account sotto l'MCC
                }
            } catch (\Exception $e) {
                // Se la query MCC fallisce, salva senza login_customer_id
            }
        }

        // Aggiorna il progetto con l'account selezionato
        Project::update($projectId, [
            'google_ads_customer_id' => $cleanCustomerId,
            'google_ads_account_name' => $accountName ?: $cleanCustomerId,
            'login_customer_id' => $loginCustomerId,
        ]);

        $_SESSION['_flash']['success'] = 'Account Google Ads collegato con successo';
        header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaign-dashboard"));
        exit;
    }

    /**
     * Disconnetti account Google Ads dal progetto
     */
    public function disconnectGoogleAds(int $projectId): void
    {
        Middleware::auth();
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        // Solo owner o editor possono disconnettere
        $role = $project['access_role'] ?? 'owner';
        if ($role === 'viewer') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaign-dashboard"));
            exit;
        }

        // Pulisci i dati Google Ads dal progetto
        Project::update($projectId, [
            'google_ads_customer_id' => null,
            'google_ads_account_name' => null,
            'oauth_token_id' => null,
        ]);

        // Rimuovi token OAuth per Google Ads di questo utente
        Database::execute(
            "DELETE FROM google_oauth_tokens WHERE user_id = ? AND service = 'google_ads'",
            [$user['id']]
        );

        $_SESSION['_flash']['success'] = 'Account Google Ads disconnesso';
        header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaign-dashboard"));
        exit;
    }
}
