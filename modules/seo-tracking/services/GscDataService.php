<?php
/**
 * GscDataService - Orchestratore dati GSC con approccio Smart Hybrid
 *
 * Gestisce l'accesso ai dati GSC con logica automatica:
 * - Dati recenti (90 giorni): legge da database locale
 * - Dati storici (>90 giorni): chiama API GSC + cache 24h
 *
 * @package Modules\SeoTracking\Services
 */

namespace Modules\SeoTracking\Services;

use Core\Database;
use Core\Settings;
use Modules\SeoTracking\Models\GscConnection;
use Modules\SeoTracking\Models\GscData;

class GscDataService
{
    /** Numero di giorni da mantenere nel database locale */
    private const GIORNI_DATI_LOCALI = 90;

    /** TTL cache per dati storici (ore) */
    private const CACHE_TTL_ORE = 24;

    /** Delay GSC - i dati non sono disponibili prima di X giorni */
    private const GSC_DELAY_GIORNI = 3;

    private GscService $gscService;
    private GscCacheService $cache;
    private GscData $gscData;
    private GscConnection $gscConnection;

    public function __construct()
    {
        $this->gscService = new GscService();
        $this->cache = new GscCacheService();
        $this->gscData = new GscData();
        $this->gscConnection = new GscConnection();
    }

    /**
     * Recupera dati aggregati per keyword con selezione automatica fonte
     *
     * @param int $projectId ID progetto
     * @param string $dataInizio Data inizio (Y-m-d)
     * @param string $dataFine Data fine (Y-m-d)
     * @param array $filtri Filtri opzionali ['keyword' => '', 'url' => '']
     * @return array ['data' => [...], 'fonte' => 'db'|'api'|'cache', 'cache_scade' => ?string]
     */
    public function getKeywordData(
        int $projectId,
        string $dataInizio,
        string $dataFine,
        array $filtri = []
    ): array {
        $fonte = 'db';
        $cacheScade = null;

        // 1. Verifica se dati recenti (ultimi 90gg)
        if ($this->sonoDatiLocali($dataInizio)) {
            $dati = $this->recuperaDaDatabase($projectId, $dataInizio, $dataFine, $filtri);
        } else {
            // 2. Verifica cache
            $chiaveCache = $this->cache->generaChiave($projectId, $dataInizio, $dataFine, $filtri);
            $dati = $this->cache->recupera($chiaveCache);

            if ($dati !== null) {
                $fonte = 'cache';
                $cacheScade = $this->cache->getScadenza($chiaveCache);
            } else {
                // 3. Recupera da API GSC
                $dati = $this->recuperaDaApi($projectId, $dataInizio, $dataFine, $filtri);

                // GOLDEN RULE #10: Riconnetti DB dopo chiamata API lunga
                Database::reconnect();

                // Salva in cache
                $this->cache->salva($chiaveCache, $dati, self::CACHE_TTL_ORE);
                $fonte = 'api';
            }
        }

        return [
            'data' => $dati,
            'fonte' => $fonte,
            'cache_scade' => $cacheScade,
            'cache_tempo_rimanente' => $fonte === 'cache'
                ? $this->cache->getTempoRimanente($chiaveCache ?? '')
                : null
        ];
    }

    /**
     * Verifica se la data rientra nei dati locali (ultimi 90 giorni)
     *
     * @param string $dataInizio Data da verificare (Y-m-d)
     * @return bool True se i dati sono disponibili localmente
     */
    public function sonoDatiLocali(string $dataInizio): bool
    {
        $soglia = date('Y-m-d', strtotime('-' . self::GIORNI_DATI_LOCALI . ' days'));
        return $dataInizio >= $soglia;
    }

    /**
     * Ottieni informazioni sui range dati disponibili (per UI)
     *
     * @param int $projectId ID progetto
     * @return array Info range dati
     */
    public function getInfoRange(int $projectId): array
    {
        $connection = $this->gscConnection->getByProject($projectId);

        return [
            'dati_locali_da' => date('Y-m-d', strtotime('-' . self::GIORNI_DATI_LOCALI . ' days')),
            'dati_locali_a' => date('Y-m-d', strtotime('-' . self::GSC_DELAY_GIORNI . ' days')),
            'dati_api_da' => date('Y-m-d', strtotime('-16 months')),
            'dati_api_a' => date('Y-m-d', strtotime('-' . self::GSC_DELAY_GIORNI . ' days')),
            'giorni_locali' => self::GIORNI_DATI_LOCALI,
            'gsc_connesso' => !empty($connection),
            'ultimo_sync' => $connection['last_sync_at'] ?? null
        ];
    }

    /**
     * Determina la fonte dati per un range specifico
     *
     * @param string $dataInizio Data inizio
     * @param string $dataFine Data fine
     * @return string 'db' | 'api' | 'mista'
     */
    public function determinaFonte(string $dataInizio, string $dataFine): string
    {
        $sogliaLocale = date('Y-m-d', strtotime('-' . self::GIORNI_DATI_LOCALI . ' days'));

        if ($dataInizio >= $sogliaLocale && $dataFine >= $sogliaLocale) {
            return 'db';
        }

        if ($dataInizio < $sogliaLocale && $dataFine < $sogliaLocale) {
            return 'api';
        }

        return 'mista';
    }

    /**
     * Recupera dati dal database locale
     */
    private function recuperaDaDatabase(int $projectId, string $dataInizio, string $dataFine, array $filtri): array
    {
        $db = Database::getInstance();

        $sql = "
            SELECT
                query AS keyword,
                page AS url,
                SUM(clicks) AS clicks,
                SUM(impressions) AS impressions,
                AVG(position) AS position,
                AVG(ctr) AS ctr
            FROM st_gsc_data
            WHERE project_id = ?
              AND date BETWEEN ? AND ?
        ";

        $params = [$projectId, $dataInizio, $dataFine];

        // Applica filtri
        if (!empty($filtri['keyword'])) {
            $sql .= " AND query LIKE ?";
            $params[] = '%' . $filtri['keyword'] . '%';
        }

        if (!empty($filtri['url'])) {
            $sql .= " AND page LIKE ?";
            $params[] = '%' . $filtri['url'] . '%';
        }

        $sql .= "
            GROUP BY query, page
            ORDER BY clicks DESC
            LIMIT 5000
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Recupera dati dall'API GSC
     */
    private function recuperaDaApi(int $projectId, string $dataInizio, string $dataFine, array $filtri): array
    {
        $connection = $this->gscConnection->getByProject($projectId);

        if (!$connection || !$connection['property_url']) {
            throw new \Exception('Connessione GSC non configurata');
        }

        $token = $this->gscService->getValidToken($projectId);

        if (!$token) {
            throw new \Exception('Token GSC non valido o scaduto');
        }

        // Fetch dati da API
        $rawData = $this->fetchFromGscApi($token, $connection['property_url'], $dataInizio, $dataFine, $filtri);

        // Aggrega per keyword+url
        return $this->aggregaDatiApi($rawData);
    }

    /**
     * Chiamata diretta all'API GSC Search Analytics
     */
    private function fetchFromGscApi(string $token, string $siteUrl, string $dataInizio, string $dataFine, array $filtri): array
    {
        $encodedUrl = urlencode($siteUrl);
        $url = 'https://searchconsole.googleapis.com/webmasters/v3/sites/' . $encodedUrl . '/searchAnalytics/query';

        $allRows = [];
        $startRow = 0;
        $rowLimit = 25000;

        // Prepara filtri dimensioni
        $dimensionFilterGroups = [];

        if (!empty($filtri['keyword'])) {
            $dimensionFilterGroups[] = [
                'filters' => [
                    [
                        'dimension' => 'query',
                        'operator' => 'contains',
                        'expression' => $filtri['keyword']
                    ]
                ]
            ];
        }

        if (!empty($filtri['url'])) {
            $dimensionFilterGroups[] = [
                'filters' => [
                    [
                        'dimension' => 'page',
                        'operator' => 'contains',
                        'expression' => $filtri['url']
                    ]
                ]
            ];
        }

        do {
            $body = [
                'startDate' => $dataInizio,
                'endDate' => $dataFine,
                'dimensions' => ['query', 'page'],
                'rowLimit' => $rowLimit,
                'startRow' => $startRow,
            ];

            if (!empty($dimensionFilterGroups)) {
                $body['dimensionFilterGroups'] = $dimensionFilterGroups;
            }

            $response = $this->httpPostJson($url, $body, $token);

            if (isset($response['error'])) {
                throw new \Exception('Errore API GSC: ' . ($response['error']['message'] ?? 'Errore sconosciuto'));
            }

            $rows = $response['rows'] ?? [];
            $allRows = array_merge($allRows, $rows);
            $startRow += $rowLimit;

        } while (count($rows) === $rowLimit && $startRow < 100000); // Max 100k righe

        return $allRows;
    }

    /**
     * Aggrega dati raw API nel formato standard
     */
    private function aggregaDatiApi(array $rawData): array
    {
        $result = [];

        foreach ($rawData as $row) {
            $keyword = $row['keys'][0] ?? '';
            $url = $row['keys'][1] ?? '';

            $result[] = [
                'keyword' => $keyword,
                'url' => $url,
                'clicks' => (int)($row['clicks'] ?? 0),
                'impressions' => (int)($row['impressions'] ?? 0),
                'position' => round((float)($row['position'] ?? 0), 1),
                'ctr' => round((float)($row['ctr'] ?? 0) * 100, 2)
            ];
        }

        // Ordina per click desc
        usort($result, fn($a, $b) => $b['clicks'] <=> $a['clicks']);

        return array_slice($result, 0, 5000);
    }

    /**
     * HTTP POST JSON con autenticazione
     */
    private function httpPostJson(string $url, array $data, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            $error = json_decode($response, true);
            return ['error' => $error['error'] ?? ['message' => 'HTTP ' . $httpCode]];
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Pulisci dati vecchi dal database (>90 giorni)
     *
     * @param int|null $projectId ID progetto specifico o null per tutti
     * @return int Numero record eliminati
     */
    public function pulisciDatiVecchi(?int $projectId = null): int
    {
        $db = Database::getInstance();
        $cutoffDate = date('Y-m-d', strtotime('-' . self::GIORNI_DATI_LOCALI . ' days'));

        $sql = "DELETE FROM st_gsc_data WHERE date < ?";
        $params = [$cutoffDate];

        if ($projectId !== null) {
            $sql .= " AND project_id = ?";
            $params[] = $projectId;
        }

        // Elimina in batch per evitare lock lunghi
        $sql .= " LIMIT 10000";

        $totaleEliminati = 0;

        do {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $eliminati = $stmt->rowCount();
            $totaleEliminati += $eliminati;
        } while ($eliminati === 10000);

        // Pulisci anche st_gsc_daily
        $sqlDaily = "DELETE FROM st_gsc_daily WHERE date < ?";
        $paramsDaily = [$cutoffDate];

        if ($projectId !== null) {
            $sqlDaily .= " AND project_id = ?";
            $paramsDaily[] = $projectId;
        }

        $stmt = $db->prepare($sqlDaily);
        $stmt->execute($paramsDaily);

        return $totaleEliminati;
    }

    /**
     * Invalida cache per un progetto
     */
    public function invalidaCache(int $projectId): void
    {
        $this->cache->invalidaProgetto($projectId);
    }

    /**
     * Ottieni costanti di configurazione (per UI/debug)
     */
    public function getConfig(): array
    {
        return [
            'giorni_dati_locali' => self::GIORNI_DATI_LOCALI,
            'cache_ttl_ore' => self::CACHE_TTL_ORE,
            'gsc_delay_giorni' => self::GSC_DELAY_GIORNI
        ];
    }
}
