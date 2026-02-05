<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * Keyword Model
 * Gestisce la tabella st_keywords
 */
class Keyword
{
    protected string $table = 'st_keywords';

    /**
     * Trova keyword per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Trova keyword per ID con verifica progetto
     */
    public function findByProject(int $id, int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND project_id = ?",
            [$id, $projectId]
        );
    }

    /**
     * Tutte le keyword di un progetto
     */
    public function allByProject(int $projectId, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if (isset($filters['is_tracked'])) {
            $sql .= " AND is_tracked = ?";
            $params[] = (int) $filters['is_tracked'];
        }

        if (!empty($filters['group'])) {
            $sql .= " AND group_name = ?";
            $params[] = $filters['group'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND keyword LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $orderBy = $filters['order_by'] ?? 'last_position';
        $orderDir = strtoupper($filters['order_dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        // Gestione NULL per ordinamento posizione
        if ($orderBy === 'last_position') {
            $sql .= " ORDER BY last_position IS NULL, last_position {$orderDir}";
        } else {
            $sql .= " ORDER BY {$orderBy} {$orderDir}";
        }

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . (int) $filters['offset'];
            }
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Keyword con dati posizione recenti e volumi
     */
    public function allWithPositions(int $projectId, int $days = 7, array $filters = []): array
    {
        $sql = "
            SELECT
                k.*,
                kp.avg_position as current_position,
                kp.total_clicks as period_clicks,
                kp.total_impressions as period_impressions,
                kp.position_change,
                COALESCE(k.keyword_intent, JSON_UNQUOTE(JSON_EXTRACT(kv.data, '$.keyword_intent'))) as keyword_intent
            FROM {$this->table} k
            LEFT JOIN (
                SELECT
                    keyword_id,
                    AVG(avg_position) as avg_position,
                    SUM(total_clicks) as total_clicks,
                    SUM(total_impressions) as total_impressions,
                    (
                        SELECT position_change
                        FROM st_keyword_positions
                        WHERE keyword_id = kp2.keyword_id
                        ORDER BY date DESC
                        LIMIT 1
                    ) as position_change
                FROM st_keyword_positions kp2
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY keyword_id
            ) kp ON k.id = kp.keyword_id
            LEFT JOIN st_keyword_volumes kv ON k.keyword = kv.keyword
                AND kv.location_code = (
                    CASE UPPER(k.location_code)
                        WHEN 'IT' THEN 2380 WHEN 'US' THEN 2840 WHEN 'GB' THEN 2826 WHEN 'UK' THEN 2826
                        WHEN 'DE' THEN 2276 WHEN 'FR' THEN 2250 WHEN 'ES' THEN 2724 WHEN 'NL' THEN 2528
                        WHEN 'BE' THEN 2056 WHEN 'AT' THEN 2040 WHEN 'CH' THEN 2756 WHEN 'PT' THEN 2620
                        ELSE 2380
                    END
                )
            WHERE k.project_id = ?
        ";

        $params = [$days, $projectId];

        // Filtri
        if (isset($filters['is_tracked']) && $filters['is_tracked'] !== '' && $filters['is_tracked'] !== null) {
            $sql .= " AND k.is_tracked = ?";
            $params[] = (int) $filters['is_tracked'];
        }

        if (!empty($filters['group_name'])) {
            $sql .= " AND k.group_name = ?";
            $params[] = $filters['group_name'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND k.keyword LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['position_max'])) {
            $sql .= " AND k.last_position IS NOT NULL AND k.last_position <= ?";
            $params[] = (int) $filters['position_max'];
        }

        $sql .= " ORDER BY k.last_position IS NULL, k.last_position ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Gruppi keyword distinti con conteggio
     */
    public function getGroups(int $projectId): array
    {
        $sql = "SELECT group_name, COUNT(*) as count
                FROM {$this->table}
                WHERE project_id = ? AND group_name IS NOT NULL AND group_name != ''
                GROUP BY group_name
                ORDER BY group_name";
        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Crea keyword
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Crea multiple keyword
     */
    public function createMany(int $projectId, array $keywords): int
    {
        $inserted = 0;

        foreach ($keywords as $kw) {
            $data = [
                'project_id' => $projectId,
                'keyword' => trim($kw['keyword']),
                'group_name' => $kw['group'] ?? null,
                'is_brand' => $kw['is_brand'] ?? false,
                'target_url' => $kw['target_url'] ?? null,
                'priority' => $kw['priority'] ?? 'medium',
            ];

            try {
                Database::insert($this->table, $data);
                $inserted++;
            } catch (\Exception $e) {
                // Ignora duplicati
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
            }
        }

        return $inserted;
    }

    /**
     * Aggiorna keyword
     */
    public function update(int $id, array $data): bool
    {
        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Elimina keyword
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Aggiorna cache posizione
     */
    public function updatePositionCache(int $id, array $data): void
    {
        Database::update($this->table, [
            'last_position' => $data['position'],
            'last_clicks' => $data['clicks'],
            'last_impressions' => $data['impressions'],
            'last_ctr' => $data['ctr'],
            'last_updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    /**
     * Conta keyword per progetto
     */
    public function countByProject(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Top keyword per click
     */
    public function getTopByClicks(int $projectId, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND last_clicks IS NOT NULL
             ORDER BY last_clicks DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Keyword con maggiori variazioni posizione
     */
    public function getTopMovers(int $projectId, int $limit = 10): array
    {
        $sql = "
            SELECT
                k.*,
                kp.position_change,
                kp.avg_position as current_position
            FROM {$this->table} k
            JOIN st_keyword_positions kp ON k.id = kp.keyword_id
            WHERE k.project_id = ?
              AND kp.date = (SELECT MAX(date) FROM st_keyword_positions WHERE keyword_id = k.id)
              AND kp.position_change IS NOT NULL
            ORDER BY ABS(kp.position_change) DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Keyword per priorità alta con alert attivi
     */
    public function getHighPriorityWithAlerts(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND priority = 'high' AND alert_enabled = 1",
            [$projectId]
        );
    }

    /**
     * Cerca keyword esistente
     */
    public function findByKeyword(int $projectId, string $keyword): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE project_id = ? AND keyword = ?",
            [$projectId, $keyword]
        );
    }

    /**
     * Statistiche keyword per progetto
     */
    public function getStats(int $projectId): array
    {
        $total = Database::count($this->table, 'project_id = ?', [$projectId]);

        $tracked = Database::count(
            $this->table,
            'project_id = ? AND is_tracked = 1',
            [$projectId]
        );

        $top10 = Database::count(
            $this->table,
            'project_id = ? AND last_position IS NOT NULL AND last_position <= 10',
            [$projectId]
        );

        $top3 = Database::count(
            $this->table,
            'project_id = ? AND last_position IS NOT NULL AND last_position <= 3',
            [$projectId]
        );

        $withClicks = Database::count(
            $this->table,
            'project_id = ? AND last_clicks > 0',
            [$projectId]
        );

        return [
            'total' => $total,
            'total_keywords' => $total,
            'tracked' => $tracked,
            'keywords_top3' => $top3,
            'keywords_top10' => $top10,
            'keywords_with_clicks' => $withClicks,
        ];
    }

    /**
     * Aggiorna volumi di ricerca per keyword del progetto
     * Raggruppa le keyword per location_code e chiama il provider volumi.
     *
     * Cascade: DataForSEO (primario) -> Keywords Everywhere (fallback)
     */
    public function updateSearchVolumes(int $projectId): array
    {
        // Determina quale service usare
        $service = $this->getVolumeService();

        if ($service === null) {
            return ['success' => false, 'error' => 'Nessun provider volumi configurato. Vai in Admin > Impostazioni per configurare DataForSEO o Keywords Everywhere'];
        }

        // Prendi tutte le keyword del progetto
        $keywords = $this->allByProject($projectId);

        if (empty($keywords)) {
            return ['success' => true, 'updated' => 0, 'message' => 'Nessuna keyword nel progetto'];
        }

        // Raggruppa keyword per location_code
        $keywordsByLocation = [];
        foreach ($keywords as $kw) {
            $locCode = $kw['location_code'] ?? 'IT';
            if (!isset($keywordsByLocation[$locCode])) {
                $keywordsByLocation[$locCode] = [];
            }
            $keywordsByLocation[$locCode][] = $kw;
        }

        $updated = 0;
        $totalCached = 0;
        $totalFetched = 0;
        $errors = [];
        $provider = $service instanceof \Services\RapidApiKeywordService ? 'RapidAPI' :
                   ($service instanceof \Services\DataForSeoService ? 'DataForSEO' : 'Keywords Everywhere');

        // Processa ogni gruppo di location
        foreach ($keywordsByLocation as $locationCode => $locationKeywords) {
            $keywordTexts = array_column($locationKeywords, 'keyword');

            // Ottieni volumi per questa location
            $result = $service->getSearchVolumes($keywordTexts, $locationCode);

            if (!$result['success']) {
                $errors[] = "Errore per location {$locationCode}: " . ($result['error'] ?? 'unknown');
                continue;
            }

            $totalCached += $result['cached'] ?? 0;
            $totalFetched += $result['fetched'] ?? 0;

            // Aggiorna keyword nel DB
            foreach ($locationKeywords as $kw) {
                $volumeData = $result['data'][$kw['keyword']] ?? null;
                if ($volumeData) {
                    // Sanitizza competition: deve essere decimal, non stringa
                    $competition = $volumeData['competition'] ?? null;
                    if ($competition !== null && !is_numeric($competition)) {
                        $competition = null; // Se e' una stringa come 'LOW', usa null
                    } elseif ($competition !== null) {
                        $competition = (float) $competition;
                    }

                    // Normalizza keyword_intent: se array, converti in stringa
                    $intent = $volumeData['keyword_intent'] ?? null;
                    if (is_array($intent)) {
                        $intent = implode(', ', $intent);
                    }

                    $sql = "UPDATE {$this->table} SET
                            search_volume = ?,
                            cpc = ?,
                            competition = ?,
                            competition_level = ?,
                            keyword_intent = ?,
                            volume_updated_at = NOW()
                            WHERE id = ?";

                    Database::execute($sql, [
                        $volumeData['search_volume'] ?? null,
                        $volumeData['cpc'] ?? null,
                        $competition,
                        $volumeData['competition_level'] ?? null,
                        $intent,
                        $kw['id']
                    ]);
                    $updated++;
                }
            }
        }

        $response = [
            'success' => true,
            'updated' => $updated,
            'total' => count($keywords),
            'cached' => $totalCached,
            'fetched' => $totalFetched,
            'provider' => $provider,
            'message' => "Volumi aggiornati per {$updated} keyword (provider: {$provider})"
        ];

        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }

        return $response;
    }

    /**
     * Ottieni il service per i volumi di ricerca
     * Cascade: RapidAPI (primario) -> DataForSEO -> Keywords Everywhere (fallback)
     *
     * @return \Services\RapidApiKeywordService|\Services\DataForSeoService|\Services\KeywordsEverywhereService|null
     */
    private function getVolumeService()
    {
        // Prova RapidAPI (primario)
        $rapidApi = new \Services\RapidApiKeywordService();
        if ($rapidApi->isConfigured()) {
            return $rapidApi;
        }

        // Fallback: DataForSEO
        $dataForSeo = new \Services\DataForSeoService();
        if ($dataForSeo->isConfigured()) {
            return $dataForSeo;
        }

        // Fallback: Keywords Everywhere
        $kwEverywhere = new \Services\KeywordsEverywhereService();
        if ($kwEverywhere->isConfigured()) {
            return $kwEverywhere;
        }

        return null;
    }

    /**
     * Keyword con volumi di ricerca
     */
    public function allWithVolumes(int $projectId, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if (isset($filters['is_tracked'])) {
            $sql .= " AND is_tracked = ?";
            $params[] = (int) $filters['is_tracked'];
        }

        if (!empty($filters['group_name'])) {
            $sql .= " AND group_name = ?";
            $params[] = $filters['group_name'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND keyword LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        // Ordinamento default per volume
        $sql .= " ORDER BY search_volume DESC, keyword ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Aggiorna volumi di ricerca per keyword specifiche (per ID)
     * Usato per auto-fetch dopo inserimento nuove keyword
     *
     * Cascade: DataForSEO (primario) -> Keywords Everywhere (fallback)
     */
    public function updateSearchVolumesForIds(array $keywordIds): array
    {
        if (empty($keywordIds)) {
            return ['success' => true, 'updated' => 0, 'message' => 'Nessuna keyword da aggiornare'];
        }

        // Ottieni tutti i provider disponibili per fallback automatico
        $providers = $this->getAllVolumeServices();

        if (empty($providers)) {
            return ['success' => false, 'error' => 'Nessun provider volumi configurato'];
        }

        // Prendi le keyword per ID
        $placeholders = implode(',', array_fill(0, count($keywordIds), '?'));
        $keywords = Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE id IN ({$placeholders})",
            $keywordIds
        );

        if (empty($keywords)) {
            return ['success' => true, 'updated' => 0, 'message' => 'Nessuna keyword trovata'];
        }

        // Raggruppa keyword per location_code
        $keywordsByLocation = [];
        foreach ($keywords as $kw) {
            $locCode = $kw['location_code'] ?? 'IT';
            if (!isset($keywordsByLocation[$locCode])) {
                $keywordsByLocation[$locCode] = [];
            }
            $keywordsByLocation[$locCode][] = $kw;
        }

        $updated = 0;
        $totalCached = 0;
        $totalFetched = 0;
        $errors = [];
        $usedProvider = null;

        // Processa ogni gruppo di location
        foreach ($keywordsByLocation as $locationCode => $locationKeywords) {
            $keywordTexts = array_column($locationKeywords, 'keyword');

            // Prova ogni provider in sequenza (fallback automatico)
            $result = null;
            foreach ($providers as $providerName => $service) {
                $result = $service->getSearchVolumes($keywordTexts, $locationCode);

                if ($result['success'] && !empty($result['data'])) {
                    $usedProvider = $providerName;
                    break; // Provider ha funzionato, esci dal loop
                }

                // Log del fallback
                $errorMsg = $result['error'] ?? 'Nessun dato';
                error_log("[Keyword] Provider {$providerName} fallito per location {$locationCode}: {$errorMsg}. Provo il successivo...");
            }

            if (!$result || !$result['success']) {
                $errors[] = "Errore per location {$locationCode}: tutti i provider hanno fallito";
                continue;
            }

            if (empty($result['data'])) {
                $errors[] = "Location {$locationCode}: nessun dato trovato per le keyword";
                continue;
            }

            $totalCached += $result['cached'] ?? 0;
            $totalFetched += $result['fetched'] ?? 0;

            // Aggiorna keyword nel DB
            foreach ($locationKeywords as $kw) {
                $volumeData = $result['data'][$kw['keyword']] ?? null;
                if ($volumeData) {
                    // Sanitizza competition: deve essere decimal, non stringa
                    $competition = $volumeData['competition'] ?? null;
                    if ($competition !== null && !is_numeric($competition)) {
                        $competition = null; // Se e' una stringa come 'LOW', usa null
                    } elseif ($competition !== null) {
                        $competition = (float) $competition;
                    }

                    // Normalizza keyword_intent: se array, converti in stringa
                    $intent = $volumeData['keyword_intent'] ?? null;
                    if (is_array($intent)) {
                        $intent = implode(', ', $intent);
                    }

                    $sql = "UPDATE {$this->table} SET
                            search_volume = ?,
                            cpc = ?,
                            competition = ?,
                            competition_level = ?,
                            keyword_intent = ?,
                            volume_updated_at = NOW()
                            WHERE id = ?";

                    Database::execute($sql, [
                        $volumeData['search_volume'] ?? null,
                        $volumeData['cpc'] ?? null,
                        $competition,
                        $volumeData['competition_level'] ?? null,
                        $intent,
                        $kw['id']
                    ]);
                    $updated++;
                }
            }
        }

        $response = [
            'success' => true,
            'updated' => $updated,
            'total' => count($keywords),
            'cached' => $totalCached,
            'fetched' => $totalFetched,
            'provider' => $usedProvider ?? 'unknown',
            'message' => "Volumi aggiornati per {$updated} keyword (provider: {$usedProvider})"
        ];

        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }

        return $response;
    }

    /**
     * Ottieni tutti i provider volumi configurati (per fallback automatico)
     * Ordine: RapidAPI -> DataForSEO -> Keywords Everywhere
     *
     * @return array Array associativo [nome => service]
     */
    private function getAllVolumeServices(): array
    {
        $providers = [];

        $rapidApi = new \Services\RapidApiKeywordService();
        if ($rapidApi->isConfigured()) {
            $providers['RapidAPI'] = $rapidApi;
        }

        $dataForSeo = new \Services\DataForSeoService();
        if ($dataForSeo->isConfigured()) {
            $providers['DataForSEO'] = $dataForSeo;
        }

        $kwEverywhere = new \Services\KeywordsEverywhereService();
        if ($kwEverywhere->isConfigured()) {
            $providers['KeywordsEverywhere'] = $kwEverywhere;
        }

        return $providers;
    }

    /**
     * Ottieni tutte le keyword tracciate di un progetto (per job di rank check)
     *
     * @param int $projectId ID del progetto
     * @return array Lista keyword con id, keyword, location_code
     */
    public function getTrackedByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT id, keyword, location_code, group_name, last_position, last_updated_at
             FROM {$this->table}
             WHERE project_id = ? AND is_tracked = 1
             ORDER BY keyword ASC",
            [$projectId]
        );
    }

    /**
     * Ottieni dati stagionalita (monthly_searches) per una keyword dalla cache
     *
     * @param string $keyword La keyword
     * @param string $locationCode Codice paese (es. 'IT')
     * @return array|null Array con monthly_searches e keyword_intent, o null se non in cache
     */
    public function getSeasonalityData(string $keyword, string $locationCode = 'IT'): ?array
    {
        // Mapping country code -> location code numerico (compatibile DataForSEO)
        $locationCodes = [
            'IT' => 2380, 'US' => 2840, 'GB' => 2826, 'UK' => 2826,
            'DE' => 2276, 'FR' => 2250, 'ES' => 2724, 'NL' => 2528,
            'BE' => 2056, 'AT' => 2040, 'CH' => 2756, 'PT' => 2620,
            'PL' => 2616, 'SE' => 2752, 'NO' => 2578, 'DK' => 2208,
            'FI' => 2246, 'IE' => 2372, 'AU' => 2036, 'NZ' => 2554,
            'CA' => 2124, 'BR' => 2076, 'MX' => 2484, 'IN' => 2356,
            'JP' => 2392,
        ];

        $locCode = $locationCodes[strtoupper($locationCode)] ?? 2380;

        $row = Database::fetch(
            "SELECT data FROM st_keyword_volumes WHERE keyword = ? AND location_code = ?",
            [$keyword, $locCode]
        );

        if (!$row || empty($row['data'])) {
            return null;
        }

        $data = json_decode($row['data'], true);

        return [
            'monthly_searches' => $data['monthly_searches'] ?? [],
            'keyword_intent' => $data['keyword_intent'] ?? null,
        ];
    }
}
