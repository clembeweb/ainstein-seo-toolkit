<?php

namespace Modules\SeoTracking\Services;

use Core\Database;
use Services\ApiLoggerService;
use Services\GoogleOAuthService;

/**
 * Ga4Service - connessione GA4 di un progetto AI Rank e lettura report dal vivo (nessuna sync storica).
 * Usato dal "Punto della situazione" (docs/specs/seo-tracking-punto-situazione.md).
 * OAuth via GoogleOAuthService centralizzato (GR #11); token in st_ga4_connections.
 * Retry su 401 (refresh forzato) ed errori di rete come il Recap di Amevista (lib/helpers.php ga4RunReport).
 */
class Ga4Service
{
    private const DATA_API = 'https://analyticsdata.googleapis.com/v1beta';
    private const ADMIN_API = 'https://analyticsadmin.googleapis.com/v1beta';

    public function getConnection(int $projectId): ?array
    {
        return Database::fetch("SELECT * FROM st_ga4_connections WHERE project_id = ? AND is_active = 1", [$projectId]) ?: null;
    }

    /** Salva i token dopo il consenso (la property si sceglie dopo). */
    public function saveTokens(int $projectId, string $accessToken, ?string $refreshToken, int $expiresIn): void
    {
        Database::execute(
            "INSERT INTO st_ga4_connections (project_id, access_token, refresh_token, token_expires_at, is_active)
             VALUES (?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE access_token = VALUES(access_token), refresh_token = COALESCE(VALUES(refresh_token), refresh_token),
                token_expires_at = VALUES(token_expires_at), is_active = 1, last_error = NULL",
            [$projectId, $accessToken, $refreshToken, date('Y-m-d H:i:s', time() + $expiresIn)]
        );
    }

    public function saveProperty(int $projectId, string $propertyId, string $propertyName): void
    {
        Database::execute("UPDATE st_ga4_connections SET property_id = ?, property_name = ? WHERE project_id = ?", [$propertyId, mb_substr($propertyName, 0, 255), $projectId]);
    }

    public function disconnect(int $projectId): void
    {
        Database::execute("DELETE FROM st_ga4_connections WHERE project_id = ?", [$projectId]);
    }

    /** Token valido (rinnovato se scade entro 5 minuti, come Amevista getOAuthToken). */
    public function getValidToken(int $projectId, bool $force = false): ?string
    {
        $conn = $this->getConnection($projectId);
        if (!$conn || empty($conn['access_token'])) {
            return null;
        }
        if (!$force && $conn['token_expires_at'] && strtotime($conn['token_expires_at']) > time() + 300) {
            return $conn['access_token'];
        }
        if (empty($conn['refresh_token'])) {
            return null;
        }
        $tokens = (new GoogleOAuthService())->refreshToken($conn['refresh_token']);
        if (!empty($tokens['error'])) {
            Database::execute("UPDATE st_ga4_connections SET last_error = ? WHERE project_id = ?", [mb_substr((string) ($tokens['message'] ?? 'refresh fallito'), 0, 500), $projectId]);
            return null;
        }
        $this->saveTokens($projectId, $tokens['access_token'], $tokens['refresh_token'] ?? null, (int) ($tokens['expires_in'] ?? 3600));
        return $tokens['access_token'];
    }

    /** Proprietà GA4 visibili all'account collegato: [['property_id','property_name','account_name']]. */
    public function listProperties(int $projectId): array
    {
        $token = $this->getValidToken($projectId);
        if (!$token) {
            throw new \RuntimeException('Collegamento GA4 scaduto: ricollega Google Analytics.');
        }
        $res = $this->request('GET', self::ADMIN_API . '/accountSummaries?pageSize=200', $token, null, $projectId);
        if (isset($res['error'])) {
            throw new \RuntimeException('Google Analytics: ' . ($res['error']['message'] ?? 'errore'));
        }
        $out = [];
        foreach ($res['accountSummaries'] ?? [] as $account) {
            foreach ($account['propertySummaries'] ?? [] as $p) {
                $out[] = [
                    'property_id' => str_replace('properties/', '', (string) $p['property']),
                    'property_name' => $p['displayName'] ?? '',
                    'account_name' => $account['displayName'] ?? '',
                ];
            }
        }
        return $out;
    }

    /**
     * runReport sulla property collegata. Ritorna le righe come array associativo
     * [['dim1' => ..., 'met1' => float, ...]] per ciascun dateRange (chiave 'range' se più range).
     */
    public function runReport(int $projectId, array $body): array
    {
        $conn = $this->getConnection($projectId);
        if (!$conn || empty($conn['property_id'])) {
            throw new \RuntimeException('GA4 non collegato o proprietà non scelta.');
        }
        $token = $this->getValidToken($projectId);
        if (!$token) {
            throw new \RuntimeException('Collegamento GA4 scaduto: ricollega Google Analytics.');
        }
        $url = self::DATA_API . '/properties/' . rawurlencode($conn['property_id']) . ':runReport';
        $res = $this->request('POST', $url, $token, $body, $projectId);
        if (($res['_http'] ?? 200) === 401) {
            $token = $this->getValidToken($projectId, true);
            $res = $token ? $this->request('POST', $url, $token, $body, $projectId) : $res;
        }
        if (isset($res['error'])) {
            $code = (int) ($res['_http'] ?? 0);
            throw new \RuntimeException($code === 429 ? 'Quota di Google Analytics esaurita, riprova tra qualche minuto.' : 'Google Analytics: ' . ($res['error']['message'] ?? 'errore'));
        }
        return self::parseRows($res);
    }

    /** Converte la risposta runReport in righe associative. Pura: testabile senza rete. */
    public static function parseRows(array $res): array
    {
        $dims = array_map(fn($h) => $h['name'], $res['dimensionHeaders'] ?? []);
        $mets = array_map(fn($h) => $h['name'], $res['metricHeaders'] ?? []);
        $rows = [];
        foreach ($res['rows'] ?? [] as $r) {
            $row = [];
            foreach ($dims as $i => $d) {
                $row[$d] = $r['dimensionValues'][$i]['value'] ?? '';
            }
            foreach ($mets as $i => $m) {
                $row[$m] = (float) ($r['metricValues'][$i]['value'] ?? 0);
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /** HTTP con un retry sugli errori di rete transitori; logga ogni chiamata (GR #14). */
    private function request(string $method, string $url, string $token, ?array $body, int $projectId): array
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $start = microtime(true);
            $ch = curl_init($url);
            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json', 'Content-Type: application/json'],
            ];
            if ($method === 'POST') {
                $opts[CURLOPT_POST] = true;
                $opts[CURLOPT_POSTFIELDS] = json_encode($body);
            }
            curl_setopt_array($ch, $opts);
            $raw = curl_exec($ch);
            $errno = curl_errno($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $decoded = is_string($raw) ? (json_decode($raw, true) ?: []) : [];
            ApiLoggerService::log('google_ga4', parse_url($url, PHP_URL_PATH) ?: $url, $body ?? [], $decoded, $http, $start, [
                'module' => 'seo-tracking', 'cost' => 0, 'context' => "project_id={$projectId}",
            ]);
            if ($errno && $attempt === 0) {
                usleep(800000);
                continue;
            }
            if ($errno) {
                return ['error' => ['message' => 'connessione non riuscita'], '_http' => 0];
            }
            if ($http >= 400) {
                return ['error' => $decoded['error'] ?? ['message' => 'HTTP ' . $http], '_http' => $http];
            }
            return $decoded;
        }
        return ['error' => ['message' => 'connessione non riuscita'], '_http' => 0];
    }
}
