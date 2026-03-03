<?php

namespace Modules\SeoTracking\Services;

use Core\Database;

/**
 * VisibilityService
 * Calcola Visibility Score, Estimated Traffic e metriche di distribuzione
 * basate su curva CTR standard (AWR 2024).
 *
 * Usato dalla dashboard e tabella keyword del modulo SEO Tracking.
 */
class VisibilityService
{
    /**
     * Curva CTR per posizione (AWR 2024)
     * Posizioni 1-10 con valori espliciti, 11+ con decadimento
     */
    private const CTR_CURVE = [
        1  => 31.7,
        2  => 24.7,
        3  => 18.6,
        4  => 13.6,
        5  => 9.5,
        6  => 6.2,
        7  => 4.2,
        8  => 3.2,
        9  => 2.8,
        10 => 2.6,
    ];

    /**
     * CTR per posizioni 11-20 (decadimento da 1.9% a 0.6%)
     */
    private const CTR_CURVE_11_20 = [
        11 => 1.9,
        12 => 1.6,
        13 => 1.4,
        14 => 1.2,
        15 => 1.1,
        16 => 1.0,
        17 => 0.9,
        18 => 0.8,
        19 => 0.7,
        20 => 0.6,
    ];

    // ──────────────────────────────────────────────
    //  Metodi statici di calcolo
    // ──────────────────────────────────────────────

    /**
     * Ottieni CTR stimato per una posizione SERP
     *
     * @param int $position Posizione SERP (1-100+)
     * @return float CTR in percentuale (es. 31.7 per posizione 1)
     */
    public static function getCtrForPosition(int $position): float
    {
        if ($position < 1) {
            return 0.0;
        }

        // Posizioni 1-10: valori fissi
        if (isset(self::CTR_CURVE[$position])) {
            return self::CTR_CURVE[$position];
        }

        // Posizioni 11-20: valori fissi con decadimento
        if (isset(self::CTR_CURVE_11_20[$position])) {
            return self::CTR_CURVE_11_20[$position];
        }

        // Posizioni 21-50: 0.5% con decadimento
        if ($position <= 50) {
            // Decadimento lineare da 0.5 a 0.2
            $decay = 0.5 - (($position - 21) * (0.3 / 29));
            return round(max(0.2, $decay), 2);
        }

        // Posizioni 51-100: 0.1%
        if ($position <= 100) {
            return 0.1;
        }

        // Oltre 100: nessuna visibilita
        return 0.0;
    }

    /**
     * Calcola Visibility Score % aggregato per un set di keyword
     * Visibility = media dei CTR delle posizioni delle keyword
     *
     * @param array $keywords Array di keyword con 'last_position' (int|null)
     * @return float Visibility score in percentuale (0-100)
     */
    public static function calculateVisibility(array $keywords): float
    {
        if (empty($keywords)) {
            return 0.0;
        }

        $totalCtr = 0.0;
        $count = 0;

        foreach ($keywords as $kw) {
            $position = $kw['last_position'] ?? null;
            if ($position === null || $position < 1) {
                continue;
            }
            $totalCtr += self::getCtrForPosition((int)$position);
            $count++;
        }

        if ($count === 0) {
            return 0.0;
        }

        return round($totalCtr / $count, 2);
    }

    /**
     * Calcola traffico stimato aggregato per un set di keyword
     * Est. Traffic = SUM(volume * CTR/100) per ogni keyword
     *
     * @param array $keywords Array di keyword con 'last_position' e 'search_volume'
     * @return float Traffico mensile stimato
     */
    public static function calculateEstTraffic(array $keywords): float
    {
        $totalTraffic = 0.0;

        foreach ($keywords as $kw) {
            $position = $kw['last_position'] ?? null;
            $volume = $kw['search_volume'] ?? 0;

            if ($position === null || $position < 1 || $volume <= 0) {
                continue;
            }

            $totalTraffic += self::calculateKeywordEstTraffic((int)$volume, (int)$position);
        }

        return round($totalTraffic, 1);
    }

    /**
     * Calcola visibilita per una singola keyword (= CTR della posizione)
     *
     * @param int $position Posizione SERP
     * @return float Visibilita in percentuale
     */
    public static function calculateKeywordVisibility(int $position): float
    {
        return self::getCtrForPosition($position);
    }

    /**
     * Calcola traffico stimato per una singola keyword
     *
     * @param int $volume Volume di ricerca mensile
     * @param int $position Posizione SERP
     * @return float Traffico mensile stimato
     */
    public static function calculateKeywordEstTraffic(int $volume, int $position): float
    {
        if ($volume <= 0 || $position < 1) {
            return 0.0;
        }

        $ctr = self::getCtrForPosition($position);
        return round($volume * ($ctr / 100), 1);
    }

    // ──────────────────────────────────────────────
    //  Metodi con query DB
    // ──────────────────────────────────────────────

    /**
     * Distribuzione posizioni nel tempo (per grafico stacked area)
     * Raggruppa le keyword per fasce di posizione per ogni data
     *
     * @param int $projectId ID del progetto
     * @param int $days Numero di giorni da analizzare
     * @return array Array di date con distribuzione [date, top3, top10, top20, top50, top100, beyond]
     */
    public static function getDistributionOverTime(int $projectId, int $days = 30, ?string $country = null): array
    {
        $countryJoin = '';
        $countryWhere = '';
        $params = [$projectId, $days];

        if ($country) {
            $countryJoin = 'JOIN st_keywords k ON kp.keyword_id = k.id';
            $countryWhere = 'AND k.location_code = ?';
            $params[] = $country;
        }

        $sql = "
            SELECT
                kp.date,
                SUM(CASE WHEN kp.avg_position BETWEEN 1 AND 3 THEN 1 ELSE 0 END) as top3,
                SUM(CASE WHEN kp.avg_position BETWEEN 4 AND 10 THEN 1 ELSE 0 END) as top10,
                SUM(CASE WHEN kp.avg_position BETWEEN 11 AND 20 THEN 1 ELSE 0 END) as top20,
                SUM(CASE WHEN kp.avg_position BETWEEN 21 AND 50 THEN 1 ELSE 0 END) as top50,
                SUM(CASE WHEN kp.avg_position BETWEEN 51 AND 100 THEN 1 ELSE 0 END) as top100,
                SUM(CASE WHEN kp.avg_position > 100 THEN 1 ELSE 0 END) as beyond,
                COUNT(*) as total
            FROM st_keyword_positions kp
            {$countryJoin}
            WHERE kp.project_id = ?
              AND kp.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              {$countryWhere}
            GROUP BY kp.date
            ORDER BY kp.date ASC
        ";

        $rows = Database::fetchAll($sql, $params);

        // Cast a interi
        return array_map(function ($row) {
            return [
                'date'   => $row['date'],
                'top3'   => (int)$row['top3'],
                'top10'  => (int)$row['top10'],
                'top20'  => (int)$row['top20'],
                'top50'  => (int)$row['top50'],
                'top100' => (int)$row['top100'],
                'beyond' => (int)$row['beyond'],
                'total'  => (int)$row['total'],
            ];
        }, $rows);
    }

    /**
     * Trend visibilita nel tempo (per grafico linea)
     * Calcola visibilita, traffico stimato e posizione media giornaliera
     *
     * @param int $projectId ID del progetto
     * @param int $days Numero di giorni da analizzare
     * @return array Array di [date, visibility, est_traffic, avg_position, keyword_count]
     */
    public static function getVisibilityTrend(int $projectId, int $days = 30, ?string $country = null): array
    {
        // Recupera keyword con volume per il progetto (cache per calcolo traffico)
        $keywordVolumes = self::getKeywordVolumes($projectId, $country);

        // Recupera posizioni giornaliere
        $countryJoin = '';
        $countryWhere = '';
        $params = [$projectId, $days];

        if ($country) {
            $countryJoin = 'JOIN st_keywords k ON kp.keyword_id = k.id';
            $countryWhere = 'AND k.location_code = ?';
            $params[] = $country;
        }

        $sql = "
            SELECT
                kp.date,
                kp.keyword_id,
                kp.avg_position
            FROM st_keyword_positions kp
            {$countryJoin}
            WHERE kp.project_id = ?
              AND kp.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              AND kp.avg_position IS NOT NULL
              {$countryWhere}
            ORDER BY kp.date ASC
        ";

        $rows = Database::fetchAll($sql, $params);

        // Raggruppa per data
        $byDate = [];
        foreach ($rows as $row) {
            $date = $row['date'];
            if (!isset($byDate[$date])) {
                $byDate[$date] = [];
            }
            $byDate[$date][] = [
                'keyword_id'    => (int)$row['keyword_id'],
                'avg_position'  => (float)$row['avg_position'],
            ];
        }

        // Calcola metriche per ogni data
        $trend = [];
        foreach ($byDate as $date => $keywords) {
            $totalCtr = 0.0;
            $totalTraffic = 0.0;
            $totalPosition = 0.0;
            $count = count($keywords);

            foreach ($keywords as $kw) {
                $position = (int)round($kw['avg_position']);
                if ($position < 1) {
                    $position = 1;
                }

                $ctr = self::getCtrForPosition($position);
                $totalCtr += $ctr;
                $totalPosition += $kw['avg_position'];

                // Traffico stimato con volume
                $volume = $keywordVolumes[$kw['keyword_id']] ?? 0;
                if ($volume > 0) {
                    $totalTraffic += $volume * ($ctr / 100);
                }
            }

            $trend[] = [
                'date'          => $date,
                'visibility'    => $count > 0 ? round($totalCtr / $count, 2) : 0,
                'est_traffic'   => round($totalTraffic, 1),
                'avg_position'  => $count > 0 ? round($totalPosition / $count, 1) : 0,
                'keyword_count' => $count,
            ];
        }

        return $trend;
    }

    /**
     * Confronto keyword tra due date con filtri
     * Ritorna lista keyword con posizione, visibilita e traffico stimato per entrambe le date
     *
     * @param int $projectId ID del progetto
     * @param string $dateFrom Data iniziale (Y-m-d)
     * @param string $dateTo Data finale (Y-m-d)
     * @param array $filters Filtri: search, intent, position_range, volume_range
     * @return array Array di keyword con metriche comparative
     */
    public static function getKeywordsCompare(int $projectId, string $dateFrom, string $dateTo, array $filters = []): array
    {
        // Query base: keyword con posizioni alle due date
        $sql = "
            SELECT
                k.id,
                k.keyword,
                k.search_volume,
                k.keyword_intent,
                k.is_tracked,
                kp_from.avg_position AS position_from,
                kp_to.avg_position AS position_to
            FROM st_keywords k
            LEFT JOIN st_keyword_positions kp_from ON kp_from.keyword_id = k.id
                AND kp_from.date = (
                    SELECT date FROM st_keyword_positions
                    WHERE keyword_id = k.id AND project_id = ?
                    AND date BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)
                    ORDER BY ABS(DATEDIFF(date, ?)) ASC, date DESC
                    LIMIT 1
                )
            LEFT JOIN st_keyword_positions kp_to ON kp_to.keyword_id = k.id
                AND kp_to.date = (
                    SELECT date FROM st_keyword_positions
                    WHERE keyword_id = k.id AND project_id = ?
                    AND date BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)
                    ORDER BY ABS(DATEDIFF(date, ?)) ASC, date DESC
                    LIMIT 1
                )
            WHERE k.project_id = ?
        ";

        $params = [
            $projectId, $dateFrom, $dateFrom, $dateFrom,
            $projectId, $dateTo, $dateTo, $dateTo,
            $projectId,
        ];

        // Filtro ricerca testo
        if (!empty($filters['search'])) {
            $sql .= " AND k.keyword LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        // Filtro intent
        if (!empty($filters['intent'])) {
            $sql .= " AND k.keyword_intent = ?";
            $params[] = $filters['intent'];
        }

        // Filtro range posizione (sulla data finale)
        if (!empty($filters['position_range'])) {
            $range = self::parsePositionRange($filters['position_range']);
            if ($range) {
                $sql .= " AND kp_to.avg_position BETWEEN ? AND ?";
                $params[] = $range[0];
                $params[] = $range[1];
            }
        }

        // Filtro range volume
        if (!empty($filters['volume_range'])) {
            $range = self::parseVolumeRange($filters['volume_range']);
            if ($range) {
                $sql .= " AND k.search_volume BETWEEN ? AND ?";
                $params[] = $range[0];
                $params[] = $range[1];
            }
        }

        // Filtro country
        if (!empty($filters['location_code'])) {
            $sql .= " AND k.location_code = ?";
            $params[] = $filters['location_code'];
        }

        $sql .= " ORDER BY k.search_volume DESC, k.keyword ASC";

        $rows = Database::fetchAll($sql, $params);

        // Arricchisci con metriche calcolate
        $results = [];
        foreach ($rows as $row) {
            $posFrom = $row['position_from'] !== null ? (float)$row['position_from'] : null;
            $posTo = $row['position_to'] !== null ? (float)$row['position_to'] : null;
            $volume = (int)($row['search_volume'] ?? 0);

            $posFromInt = $posFrom !== null ? (int)round($posFrom) : null;
            $posToInt = $posTo !== null ? (int)round($posTo) : null;

            // Visibilita
            $visFrom = $posFromInt !== null ? self::getCtrForPosition($posFromInt) : 0;
            $visTo = $posToInt !== null ? self::getCtrForPosition($posToInt) : 0;

            // Traffico stimato
            $trafficFrom = ($posFromInt !== null && $volume > 0)
                ? self::calculateKeywordEstTraffic($volume, $posFromInt) : 0;
            $trafficTo = ($posToInt !== null && $volume > 0)
                ? self::calculateKeywordEstTraffic($volume, $posToInt) : 0;

            // Delta posizione (positivo = miglioramento, cioe posizione scesa)
            $positionDelta = null;
            if ($posFrom !== null && $posTo !== null) {
                $positionDelta = round($posFrom - $posTo, 1);
            }

            // Status confronto
            $status = 'stable';
            if ($posFrom === null && $posTo !== null) {
                $status = 'new';
            } elseif ($posFrom !== null && $posTo === null) {
                $status = 'lost';
            } elseif ($positionDelta !== null) {
                if ($positionDelta >= 1) {
                    $status = 'improved';
                } elseif ($positionDelta <= -1) {
                    $status = 'declined';
                }
            }

            $results[] = [
                'id'              => (int)$row['id'],
                'keyword'         => $row['keyword'],
                'search_volume'   => $volume,
                'keyword_intent'  => $row['keyword_intent'],
                'is_tracked'      => (int)$row['is_tracked'],
                'position_from'   => $posFrom !== null ? round($posFrom, 1) : null,
                'position_to'     => $posTo !== null ? round($posTo, 1) : null,
                'position_delta'  => $positionDelta,
                'visibility_from' => $visFrom,
                'visibility_to'   => $visTo,
                'visibility_delta' => round($visTo - $visFrom, 2),
                'traffic_from'    => $trafficFrom,
                'traffic_to'      => $trafficTo,
                'traffic_delta'   => round($trafficTo - $trafficFrom, 1),
                'status'          => $status,
            ];
        }

        return $results;
    }

    // ──────────────────────────────────────────────
    //  Helper privati
    // ──────────────────────────────────────────────

    /**
     * Mappa range posizione da stringa a array [min, max]
     *
     * @param string $range Identificatore range (top3, top10, top20, top50, top100, 100+)
     * @return array|null [min, max] o null se non valido
     */
    private static function parsePositionRange(string $range): ?array
    {
        $map = [
            'top3'   => [1, 3],
            'top10'  => [1, 10],
            'top20'  => [1, 20],
            'top50'  => [1, 50],
            'top100' => [1, 100],
            '11-20'  => [11, 20],
            '21-50'  => [21, 50],
            '51-100' => [51, 100],
            '100+'   => [101, 999],
        ];

        return $map[$range] ?? null;
    }

    /**
     * Mappa range volume da stringa a array [min, max]
     *
     * @param string $range Identificatore range (0-100, 100-500, 500-1000, ecc.)
     * @return array|null [min, max] o null se non valido
     */
    private static function parseVolumeRange(string $range): ?array
    {
        $map = [
            '0-100'      => [0, 100],
            '100-500'    => [100, 500],
            '500-1000'   => [500, 1000],
            '1000-5000'  => [1000, 5000],
            '5000-10000' => [5000, 10000],
            '10000+'     => [10000, 9999999],
        ];

        return $map[$range] ?? null;
    }

    /**
     * Ottieni volumi di ricerca indicizzati per keyword_id
     *
     * @param int $projectId ID del progetto
     * @return array [keyword_id => search_volume]
     */
    private static function getKeywordVolumes(int $projectId, ?string $country = null): array
    {
        $sql = "SELECT id, search_volume FROM st_keywords WHERE project_id = ? AND search_volume > 0";
        $params = [$projectId];

        if ($country) {
            $sql .= " AND location_code = ?";
            $params[] = $country;
        }

        $rows = Database::fetchAll($sql, $params);

        $volumes = [];
        foreach ($rows as $row) {
            $volumes[(int)$row['id']] = (int)$row['search_volume'];
        }

        return $volumes;
    }
}
