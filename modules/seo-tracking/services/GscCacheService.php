<?php
/**
 * GscCacheService - Gestione cache per dati GSC storici
 *
 * Memorizza temporaneamente (24h) i dati GSC recuperati via API
 * per evitare chiamate ripetute per lo stesso range di date.
 *
 * @package Modules\SeoTracking\Services
 */

namespace Modules\SeoTracking\Services;

class GscCacheService
{
    private string $cacheDir;
    private const DEFAULT_TTL_ORE = 24;

    public function __construct()
    {
        $this->cacheDir = ROOT_PATH . '/storage/cache/gsc/';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Recupera dati dalla cache
     *
     * @param string $chiave Chiave cache
     * @return array|null Dati cachati o null se non presenti/scaduti
     */
    public function recupera(string $chiave): ?array
    {
        $file = $this->getPercorsoFile($chiave);

        if (!file_exists($file)) {
            return null;
        }

        $contenuto = json_decode(file_get_contents($file), true);

        if (!$contenuto || !isset($contenuto['scade_at'])) {
            unlink($file);
            return null;
        }

        // Verifica scadenza
        if ($contenuto['scade_at'] < time()) {
            unlink($file);
            return null;
        }

        return $contenuto['data'];
    }

    /**
     * Salva dati in cache
     *
     * @param string $chiave Chiave cache
     * @param array $dati Dati da memorizzare
     * @param int $ttlOre Tempo di vita in ore (default 24)
     */
    public function salva(string $chiave, array $dati, int $ttlOre = self::DEFAULT_TTL_ORE): void
    {
        $file = $this->getPercorsoFile($chiave);

        $contenuto = [
            'data' => $dati,
            'scade_at' => time() + ($ttlOre * 3600),
            'creato_at' => time(),
            'chiave' => $chiave
        ];

        file_put_contents($file, json_encode($contenuto, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Ottieni data/ora scadenza formattata
     *
     * @param string $chiave Chiave cache
     * @return string|null Orario scadenza (es. "14:30") o null
     */
    public function getScadenza(string $chiave): ?string
    {
        $file = $this->getPercorsoFile($chiave);

        if (!file_exists($file)) {
            return null;
        }

        $contenuto = json_decode(file_get_contents($file), true);

        if (!$contenuto || !isset($contenuto['scade_at'])) {
            return null;
        }

        return date('H:i', $contenuto['scade_at']);
    }

    /**
     * Ottieni tempo rimanente in formato leggibile
     *
     * @param string $chiave Chiave cache
     * @return string|null Es. "23h 45m" o null
     */
    public function getTempoRimanente(string $chiave): ?string
    {
        $file = $this->getPercorsoFile($chiave);

        if (!file_exists($file)) {
            return null;
        }

        $contenuto = json_decode(file_get_contents($file), true);

        if (!$contenuto || !isset($contenuto['scade_at'])) {
            return null;
        }

        $secondiRimanenti = $contenuto['scade_at'] - time();

        if ($secondiRimanenti <= 0) {
            return null;
        }

        $ore = floor($secondiRimanenti / 3600);
        $minuti = floor(($secondiRimanenti % 3600) / 60);

        if ($ore > 0) {
            return "{$ore}h {$minuti}m";
        }

        return "{$minuti}m";
    }

    /**
     * Invalida cache per un progetto specifico
     *
     * @param int $projectId ID progetto
     */
    public function invalidaProgetto(int $projectId): void
    {
        $pattern = $this->cacheDir . "project_{$projectId}_*.json";

        foreach (glob($pattern) as $file) {
            unlink($file);
        }
    }

    /**
     * Invalida tutta la cache GSC
     */
    public function invalidaTutto(): void
    {
        $pattern = $this->cacheDir . "*.json";

        foreach (glob($pattern) as $file) {
            unlink($file);
        }
    }

    /**
     * Pulisci file cache scaduti
     *
     * @return int Numero file eliminati
     */
    public function pulisciScaduti(): int
    {
        $eliminati = 0;
        $pattern = $this->cacheDir . "*.json";

        foreach (glob($pattern) as $file) {
            $contenuto = json_decode(file_get_contents($file), true);

            if (!$contenuto || !isset($contenuto['scade_at']) || $contenuto['scade_at'] < time()) {
                unlink($file);
                $eliminati++;
            }
        }

        return $eliminati;
    }

    /**
     * Genera chiave cache univoca
     *
     * @param int $projectId ID progetto
     * @param string $dataInizio Data inizio (Y-m-d)
     * @param string $dataFine Data fine (Y-m-d)
     * @param array $filtri Filtri applicati
     * @return string Chiave cache
     */
    public function generaChiave(int $projectId, string $dataInizio, string $dataFine, array $filtri = []): string
    {
        $hashFiltri = md5(json_encode($filtri));
        return "project_{$projectId}_{$dataInizio}_{$dataFine}_{$hashFiltri}";
    }

    /**
     * Ottieni statistiche cache
     *
     * @return array ['totale_file' => int, 'dimensione_mb' => float, 'scaduti' => int]
     */
    public function getStatistiche(): array
    {
        $totaleFile = 0;
        $dimensioneTotale = 0;
        $scaduti = 0;

        $pattern = $this->cacheDir . "*.json";

        foreach (glob($pattern) as $file) {
            $totaleFile++;
            $dimensioneTotale += filesize($file);

            $contenuto = json_decode(file_get_contents($file), true);
            if (!$contenuto || !isset($contenuto['scade_at']) || $contenuto['scade_at'] < time()) {
                $scaduti++;
            }
        }

        return [
            'totale_file' => $totaleFile,
            'dimensione_mb' => round($dimensioneTotale / 1024 / 1024, 2),
            'scaduti' => $scaduti
        ];
    }

    /**
     * Percorso file cache
     */
    private function getPercorsoFile(string $chiave): string
    {
        // Sanitizza chiave per nome file sicuro
        $chiaveSicura = preg_replace('/[^a-zA-Z0-9_-]/', '_', $chiave);
        return $this->cacheDir . $chiaveSicura . '.json';
    }
}
