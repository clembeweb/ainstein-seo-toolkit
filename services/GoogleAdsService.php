<?php

namespace Services;

use Core\Database;
use Core\Settings;
use Services\ApiLoggerService;
use Modules\AdsAnalyzer\Models\ApiUsage;

/**
 * GoogleAdsService - Servizio centralizzato per Google Ads API v18 REST
 *
 * Gestisce tutte le chiamate REST all'API Google Ads: query GAQL, mutazioni risorse,
 * keyword planner. Include rate limiting, retry con backoff, token refresh automatico.
 *
 * Uso:
 * ```php
 * $gads = new GoogleAdsService($userId, '2843496968');
 * $result = $gads->search("SELECT campaign.id, campaign.name FROM campaign LIMIT 10");
 * ```
 */
class GoogleAdsService
{
    private const BASE_URL = 'https://googleads.googleapis.com/v20';
    private const MAX_RETRIES = 3;
    private const RETRY_BASE_DELAY = 1; // secondi

    private int $userId;
    private string $customerId;
    private ?string $developerToken;
    private ?string $mccCustomerId;

    private bool $isMccMode = false;
    private string $module = 'ads-analyzer';

    private ?string $accessToken = null;
    private ?string $refreshToken = null;
    private ?string $tokenExpiresAt = null;

    private float $lastRequestTime = 0;

    /**
     * @param int $userId ID utente per OAuth tokens
     * @param string $customerId Google Ads customer ID (senza trattini, es: "2843496968")
     * @param string|null $loginCustomerId Override per login-customer-id header (null = usa MCC da settings, '' = nessun header)
     */
    public function __construct(int $userId, string $customerId, ?string $loginCustomerId = null)
    {
        $this->userId = $userId;
        $this->customerId = preg_replace('/[^0-9]/', '', $customerId);
        $this->developerToken = Settings::get('gads_developer_token');
        $this->mccCustomerId = $loginCustomerId === null
            ? Settings::get('gads_mcc_customer_id')
            : ($loginCustomerId ?: null);

        $this->loadTokens();
    }

    /**
     * Factory: crea istanza per MCC piattaforma (Keyword Planner, no OAuth utente).
     * Token MCC in google_oauth_tokens con user_id=0, service='google_ads_mcc'.
     */
    public static function forMcc(): self
    {
        $mccId = \Core\Settings::get('gads_mcc_customer_id');
        if (empty($mccId)) {
            throw new \RuntimeException('MCC Customer ID non configurato (gads_mcc_customer_id)');
        }
        $instance = new self(0, $mccId, $mccId);
        $instance->isMccMode = true;
        $instance->module = 'keyword-planner';
        $instance->loadTokens(); // Ricarica token con service='google_ads_mcc'
        return $instance;
    }

    // =========================================================================
    // READ METHODS
    // =========================================================================

    /**
     * Esegue una query GAQL con paginazione automatica
     *
     * @param string $gaql Query Google Ads Query Language
     * @return array Risposta API parsata
     * @throws \RuntimeException
     */
    public function search(string $gaql): array
    {
        $url = self::BASE_URL . '/customers/' . $this->customerId . '/googleAds:search';
        $body = ['query' => $gaql];

        return $this->request('POST', $url, $body, 'GAQL query');
    }

    /**
     * Esegue una query GAQL in streaming (risposta completa senza paginazione)
     *
     * @param string $gaql Query Google Ads Query Language
     * @return array Risposta API parsata
     * @throws \RuntimeException
     */
    public function searchStream(string $gaql): array
    {
        $url = self::BASE_URL . '/customers/' . $this->customerId . '/googleAds:searchStream';
        $body = ['query' => $gaql];

        return $this->request('POST', $url, $body, 'GAQL stream query');
    }

    /**
     * Lista tutti i customer ID accessibili con le credenziali correnti
     *
     * @return array Risposta API con lista customer ID
     * @throws \RuntimeException
     */
    public function listAccessibleCustomers(): array
    {
        $url = self::BASE_URL . '/customers:listAccessibleCustomers';

        return $this->request('GET', $url, null, 'list accessible customers');
    }

    // =========================================================================
    // WRITE METHODS (MUTATE)
    // =========================================================================

    /**
     * Muta campagne (create, update, remove)
     *
     * @param array $operations Array di operazioni mutate
     * @return array Risposta API
     * @throws \RuntimeException
     */
    public function mutateCampaigns(array $operations): array
    {
        return $this->mutateResource('campaigns', $operations);
    }

    /**
     * Muta gruppi di annunci (create, update, remove)
     *
     * @param array $operations Array di operazioni mutate
     * @return array Risposta API
     * @throws \RuntimeException
     */
    public function mutateAdGroups(array $operations): array
    {
        return $this->mutateResource('adGroups', $operations);
    }

    /**
     * Muta annunci nei gruppi (create, update, remove)
     *
     * @param array $operations Array di operazioni mutate
     * @return array Risposta API
     * @throws \RuntimeException
     */
    public function mutateAdGroupAds(array $operations): array
    {
        return $this->mutateResource('adGroupAds', $operations);
    }

    /**
     * Muta criteri dei gruppi di annunci (create, update, remove)
     *
     * @param array $operations Array di operazioni mutate
     * @return array Risposta API
     * @throws \RuntimeException
     */
    public function mutateAdGroupCriteria(array $operations): array
    {
        return $this->mutateResource('adGroupCriteria', $operations);
    }

    /**
     * Muta criteri delle campagne (create, update, remove)
     *
     * @param array $operations Array di operazioni mutate
     * @return array Risposta API
     * @throws \RuntimeException
     */
    public function mutateCampaignCriteria(array $operations): array
    {
        return $this->mutateResource('campaignCriteria', $operations);
    }

    /**
     * Muta budget delle campagne (create, update, remove)
     *
     * @param array $operations Array di operazioni mutate
     * @return array Risposta API
     * @throws \RuntimeException
     */
    public function mutateCampaignBudgets(array $operations): array
    {
        return $this->mutateResource('campaignBudgets', $operations);
    }

    /**
     * Mutazione raggruppata di risorse multiple in una singola richiesta atomica
     *
     * @param array $mutateOperations Array di operazioni raggruppate
     * @return array Risposta API
     * @throws \RuntimeException
     */
    public function groupedMutate(array $mutateOperations): array
    {
        $url = self::BASE_URL . '/customers/' . $this->customerId . '/googleAds:mutate';
        $body = ['mutateOperations' => $mutateOperations];

        return $this->request('POST', $url, $body, 'grouped mutate');
    }

    // =========================================================================
    // KEYWORD PLANNER
    // =========================================================================

    /**
     * Keyword Planner: volumi storici per keyword specifiche.
     */
    public function generateKeywordHistoricalMetrics(array $keywords, string $language, array $geoTargets): array
    {
        $url = self::BASE_URL . '/customers/' . $this->customerId . ':generateKeywordHistoricalMetrics';
        $body = [
            'keywords' => $keywords,
            'language' => $language,
            'geoTargetConstants' => $geoTargets,
            'keywordPlanNetwork' => 'GOOGLE_SEARCH',
        ];
        return $this->request('POST', $url, $body, 'keyword historical metrics');
    }

    /**
     * Keyword Planner: genera idee keyword da seed o URL.
     */
    public function generateKeywordIdeas(
        array $seedKeywords = [],
        ?string $url = null,
        string $language = 'languageConstants/1004',
        array $geoTargets = ['geoTargetConstants/2380']
    ): array {
        $apiUrl = self::BASE_URL . '/customers/' . $this->customerId . ':generateKeywordIdeas';

        $body = [
            'language' => $language,
            'geoTargetConstants' => $geoTargets,
            'keywordPlanNetwork' => 'GOOGLE_SEARCH',
        ];

        if (!empty($seedKeywords) && !empty($url)) {
            $body['keywordAndUrlSeed'] = ['keywords' => $seedKeywords, 'url' => $url];
        } elseif (!empty($url)) {
            $body['urlSeed'] = ['url' => $url];
        } elseif (!empty($seedKeywords)) {
            $body['keywordSeed'] = ['keywords' => $seedKeywords];
        }

        return $this->request('POST', $apiUrl, $body, 'keyword ideas');
    }

    // =========================================================================
    // PRIVATE METHODS
    // =========================================================================

    /**
     * Esegue mutazione su una risorsa specifica
     *
     * @param string $resource Nome risorsa (campaigns, adGroups, etc.)
     * @param array $operations Operazioni mutate
     * @return array Risposta API
     * @throws \RuntimeException
     */
    private function mutateResource(string $resource, array $operations): array
    {
        $url = self::BASE_URL . '/customers/' . $this->customerId . '/' . $resource . ':mutate';
        $body = ['operations' => $operations];

        return $this->request('POST', $url, $body, "mutate {$resource}");
    }

    /**
     * Esegue una richiesta HTTP all'API Google Ads con retry e logging
     *
     * @param string $method Metodo HTTP (GET, POST)
     * @param string $url URL completo
     * @param array|null $body Body della richiesta (null per GET)
     * @param string $context Contesto per il logging
     * @return array Risposta API parsata
     * @throws \RuntimeException
     */
    private function request(string $method, string $url, ?array $body = null, string $context = ''): array
    {
        if (!$this->isMccMode) {
            $this->checkRateLimit();
        }
        $this->refreshTokenIfNeeded();

        $accessToken = $this->getAccessToken();

        // Headers standard per ogni richiesta
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'developer-token: ' . $this->developerToken,
        ];

        if (!empty($this->mccCustomerId)) {
            $mccClean = preg_replace('/[^0-9]/', '', $this->mccCustomerId);
            $headers[] = 'login-customer-id: ' . $mccClean;
        }

        // Endpoint per logging (rimuovi base URL per brevità)
        $endpoint = str_replace(self::BASE_URL, '', $url);

        $lastException = null;
        $lastHttpCode = 0;
        $lastResponse = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            if ($attempt > 0) {
                $delay = self::RETRY_BASE_DELAY * pow(2, $attempt - 1);
                sleep($delay);
                Database::reconnect();
            }

            $startTime = microtime(true);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            if ($method === 'POST' && $body !== null) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if (!$this->isMccMode) {
                $this->incrementUsage();
            }
            $this->lastRequestTime = microtime(true);

            // Errore cURL
            if ($curlError) {
                ApiLoggerService::log('google_ads', $endpoint, $body ?? [], null, 0, $startTime, [
                    'module' => $this->module,
                    'cost' => 0,
                    'error' => "CURL error: {$curlError}",
                    'context' => $context,
                ]);
                $lastException = new \RuntimeException("Google Ads API CURL error: {$curlError}");
                continue;
            }

            $data = json_decode($response, true);
            $lastHttpCode = $httpCode;
            $lastResponse = $data;

            // Log chiamata API
            ApiLoggerService::log('google_ads', $endpoint, $body ?? [], $data, $httpCode, $startTime, [
                'module' => $this->module,
                'cost' => 0,
                'context' => $context,
            ]);

            // Successo
            if ($httpCode >= 200 && $httpCode < 300) {
                return $data ?? [];
            }

            // Retry su 429 (rate limit) o 500/503 (server error)
            if (in_array($httpCode, [429, 500, 503])) {
                $errorMsg = $this->extractGoogleAdsError($data);
                $lastException = new \RuntimeException(
                    "Google Ads API HTTP {$httpCode}: {$errorMsg} (tentativo " . ($attempt + 1) . '/' . (self::MAX_RETRIES + 1) . ')'
                );
                continue;
            }

            // Errore non retriabile — fallisci immediatamente
            $errorMsg = $this->extractGoogleAdsError($data);
            throw new \RuntimeException("Google Ads API HTTP {$httpCode}: {$errorMsg}");
        }

        // Tutti i retry esauriti
        if ($lastException) {
            throw $lastException;
        }

        throw new \RuntimeException("Google Ads API: tutti i tentativi falliti (HTTP {$lastHttpCode})");
    }

    /**
     * Ottieni access token corrente
     *
     * @return string Access token valido
     * @throws \RuntimeException Se nessun token disponibile
     */
    private function getAccessToken(): string
    {
        if (empty($this->accessToken)) {
            throw new \RuntimeException(
                'Nessun token Google Ads trovato per utente ' . $this->userId .
                '. Connetti il tuo account Google Ads nelle impostazioni.'
            );
        }

        return $this->accessToken;
    }

    /**
     * Rinnova il token se scaduto o in scadenza
     */
    private function refreshTokenIfNeeded(): void
    {
        if (empty($this->refreshToken)) {
            return;
        }

        // Rinnova se scade entro 60 secondi
        if ($this->tokenExpiresAt !== null) {
            $expiresTimestamp = strtotime($this->tokenExpiresAt);
            if ($expiresTimestamp > time() + 60) {
                return; // Token ancora valido
            }
        }

        // Token scaduto o in scadenza — rinnova
        $oauth = new GoogleOAuthService();
        $result = $oauth->refreshToken($this->refreshToken);

        if (isset($result['error'])) {
            throw new \RuntimeException(
                'Impossibile rinnovare il token Google Ads: ' . ($result['message'] ?? 'errore sconosciuto')
            );
        }

        $this->accessToken = $result['access_token'];
        $newRefreshToken = $result['refresh_token'] ?? $this->refreshToken;
        $expiresIn = $result['expires_in'] ?? 3600;
        $newExpiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

        // Aggiorna nel database
        $service = $this->isMccMode ? 'google_ads_mcc' : 'google_ads';
        $sql = "UPDATE google_oauth_tokens
                SET access_token = ?, refresh_token = ?, token_expires_at = ?, updated_at = NOW()
                WHERE user_id = ? AND service = ?";
        Database::execute($sql, [
            $this->accessToken,
            $newRefreshToken,
            $newExpiresAt,
            $this->userId,
            $service,
        ]);

        $this->refreshToken = $newRefreshToken;
        $this->tokenExpiresAt = $newExpiresAt;

        Database::reconnect();
    }

    /**
     * Verifica limiti di rate (per-user e globale)
     *
     * @throws \RuntimeException Se quota superata
     */
    private function checkRateLimit(): void
    {
        // Throttle semplice: almeno 1 secondo tra le richieste
        $elapsed = microtime(true) - $this->lastRequestTime;
        if ($this->lastRequestTime > 0 && $elapsed < 1.0) {
            usleep((int) ((1.0 - $elapsed) * 1000000));
        }

        if (!ApiUsage::hasQuota($this->userId)) {
            throw new \RuntimeException('Quota API Google Ads giornaliera esaurita');
        }
    }

    /**
     * Registra utilizzo API per il rate limiting
     */
    private function incrementUsage(): void
    {
        try {
            ApiUsage::increment($this->userId);
        } catch (\Exception $e) {
            // Non bloccare il flusso per errori di tracking
            \Core\Logger::channel('api')->warning('Google Ads usage tracking failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Carica OAuth tokens dal database
     */
    private function loadTokens(): void
    {
        $service = $this->isMccMode ? 'google_ads_mcc' : 'google_ads';

        $sql = "SELECT access_token, refresh_token, token_expires_at
                FROM google_oauth_tokens
                WHERE user_id = ? AND service = ?
                LIMIT 1";

        $row = Database::fetch($sql, [$this->userId, $service]);

        if ($row) {
            $this->accessToken = $row['access_token'] ?? null;
            $this->refreshToken = $row['refresh_token'] ?? null;
            $this->tokenExpiresAt = $row['token_expires_at'] ?? null;
        }
    }

    /**
     * Estrai messaggio di errore dalla risposta Google Ads API
     *
     * @param array|null $data Risposta API decodificata
     * @return string Messaggio di errore leggibile
     */
    private function extractGoogleAdsError(?array $data): string
    {
        if ($data === null) {
            return 'Risposta vuota o non valida';
        }

        // Formato errore standard Google Ads API v18
        // {"error": {"code": 400, "message": "...", "status": "INVALID_ARGUMENT", "details": [...]}}
        if (isset($data['error'])) {
            $error = $data['error'];

            $message = $error['message'] ?? '';
            $status = $error['status'] ?? '';
            $code = $error['code'] ?? '';

            // Estrai dettagli specifici Google Ads (errorCode, trigger, etc.)
            $details = $error['details'] ?? [];
            $adsErrors = [];

            foreach ($details as $detail) {
                $errors = $detail['errors'] ?? [];
                foreach ($errors as $adsError) {
                    $errorCode = $adsError['errorCode'] ?? [];
                    $errorCodeStr = '';
                    if (is_array($errorCode)) {
                        // L'errorCode è un oggetto con una chiave tipo: {"requestError": "RESOURCE_NOT_FOUND"}
                        foreach ($errorCode as $category => $specificError) {
                            $errorCodeStr = "{$category}.{$specificError}";
                            break;
                        }
                    }
                    $errorMsg = $adsError['message'] ?? '';
                    $trigger = $adsError['trigger'] ?? null;

                    $parts = array_filter([$errorCodeStr, $errorMsg]);
                    if ($trigger) {
                        $triggerValue = is_array($trigger) ? json_encode($trigger) : $trigger;
                        $parts[] = "trigger: {$triggerValue}";
                    }
                    $adsErrors[] = implode(' — ', $parts);
                }
            }

            if (!empty($adsErrors)) {
                return implode('; ', $adsErrors);
            }

            $parts = array_filter([$status, $message, "code {$code}"]);
            return implode(' — ', $parts);
        }

        return 'Errore sconosciuto nella risposta API';
    }

    /**
     * Verifica se il servizio è configurato correttamente
     *
     * @return bool True se developer token e OAuth sono presenti
     */
    public function isConfigured(): bool
    {
        return !empty($this->developerToken) && !empty($this->accessToken);
    }

    /**
     * Ottieni il customer ID configurato
     *
     * @return string Customer ID (senza trattini)
     */
    public function getCustomerId(): string
    {
        return $this->customerId;
    }
}
