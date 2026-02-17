<?php

namespace Modules\SeoTracking\Models;

use Core\Database;
use Core\ModuleLoader;

/**
 * RankQueue Model
 * Gestisce la coda di verifica posizioni SERP per il rank checker automatico
 *
 * Tabella: st_rank_queue
 */
class RankQueue
{
    protected string $table = 'st_rank_queue';

    /**
     * Ottiene il prossimo item in coda da processare
     * Ritorna il primo item con status=pending e scheduled_at <= NOW()
     *
     * @return array|null Item da processare o null se coda vuota
     */
    public function getNextPending(): ?array
    {
        $sql = "
            SELECT q.*, p.name as project_name, p.user_id
            FROM {$this->table} q
            JOIN st_projects p ON q.project_id = p.id
            WHERE q.status = 'pending'
            AND q.scheduled_at <= NOW()
            ORDER BY q.scheduled_at ASC
            LIMIT 1
        ";

        return Database::fetch($sql);
    }

    /**
     * Conta gli item in stato pending
     *
     * @return int Numero di item pending
     */
    public function getPendingCount(): int
    {
        return Database::count($this->table, "status = 'pending'");
    }

    /**
     * Ottiene tutti gli item di un progetto
     *
     * @param int $projectId ID del progetto
     * @param string|null $status Filtra per stato (opzionale)
     * @param int $limit Limite risultati
     * @return array Lista item
     */
    public function getByProject(int $projectId, ?string $status = null, int $limit = 100): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($status !== null) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY scheduled_at ASC LIMIT ?";
        $params[] = $limit;

        return Database::fetchAll($sql, $params);
    }

    /**
     * Aggiunge un singolo item alla coda
     *
     * @param array $data Dati dell'item (project_id, keyword_id, keyword, target_domain, location_code, device)
     * @return int ID del nuovo record
     */
    public function add(array $data): int
    {
        $insertData = [
            'project_id' => $data['project_id'],
            'keyword_id' => $data['keyword_id'] ?? null,
            'keyword' => $data['keyword'],
            'target_domain' => $data['target_domain'],
            'location_code' => $data['location_code'] ?? 2380, // Default: Italia
            'device' => $data['device'] ?? 'mobile',
            'status' => 'pending',
            'scheduled_at' => $data['scheduled_at'] ?? date('Y-m-d H:i:s'),
        ];

        return Database::insert($this->table, $insertData);
    }

    /**
     * Aggiunge multiple keyword alla coda in bulk
     *
     * @param int $projectId ID del progetto
     * @param array $keywords Array di keyword con i loro dati
     *                        Ogni elemento deve avere: keyword_id, keyword, location_code
     *                        Il target_domain viene letto dal progetto
     * @return int Numero di item inseriti
     */
    public function addBulk(int $projectId, array $keywords): int
    {
        // Ottieni il dominio dal progetto
        $project = Database::fetch("SELECT domain FROM st_projects WHERE id = ?", [$projectId]);

        if (!$project) {
            return 0;
        }

        $targetDomain = $project['domain'];
        $inserted = 0;

        // Prepara la query di inserimento
        $sql = "INSERT INTO {$this->table}
                (project_id, keyword_id, keyword, target_domain, location_code, device, status, scheduled_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";

        foreach ($keywords as $kw) {
            $keyword = trim($kw['keyword'] ?? '');
            if (empty($keyword)) {
                continue;
            }

            Database::execute($sql, [
                $projectId,
                $kw['keyword_id'] ?? null,
                $keyword,
                $targetDomain,
                $kw['location_code'] ?? 2380,
                $kw['device'] ?? 'mobile',
            ]);

            $inserted++;
        }

        return $inserted;
    }

    /**
     * Aggiorna lo stato di un item
     *
     * @param int $id ID dell'item
     * @param string $status Nuovo stato (pending, processing, completed, error)
     * @param string|null $error Messaggio di errore (opzionale)
     * @return bool Successo operazione
     */
    public function updateStatus(int $id, string $status, ?string $error = null): bool
    {
        $data = ['status' => $status];

        if ($status === 'processing') {
            $data['started_at'] = date('Y-m-d H:i:s');
        } elseif (in_array($status, ['completed', 'error'])) {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        if ($error !== null) {
            $data['error_message'] = $error;
        }

        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Segna un item come completato con il risultato del rank check
     *
     * @param int $id ID dell'item nella coda
     * @param int|null $position Posizione SERP (null se non trovato)
     * @param string|null $url URL trovato in SERP
     * @param int $rankCheckId ID del record creato in st_rank_checks
     * @return bool Successo operazione
     */
    public function markCompleted(int $id, ?int $position, ?string $url, int $rankCheckId): bool
    {
        $data = [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'result_position' => $position,
            'result_url' => $url,
            'rank_check_id' => $rankCheckId,
        ];

        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Segna un item come errore
     *
     * @param int $id ID dell'item
     * @param string $errorMessage Messaggio di errore
     * @return bool Successo operazione
     */
    public function markError(int $id, string $errorMessage): bool
    {
        return $this->updateStatus($id, 'error', $errorMessage);
    }

    /**
     * Elimina i record completati piu vecchi di N giorni
     * Pulizia periodica della coda
     *
     * @param int $days Giorni di retention (default: 7)
     * @return int Numero di record eliminati
     */
    public function clearOldCompleted(int $days = 7): int
    {
        return Database::delete(
            $this->table,
            "status IN ('completed', 'error') AND completed_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
    }

    /**
     * Ottiene statistiche globali della coda
     *
     * @return array Statistiche (pending, processing, completed, errors, total)
     */
    public function getStats(): array
    {
        $stats = Database::fetch("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
            FROM {$this->table}
        ");

        return [
            'total' => (int) ($stats['total'] ?? 0),
            'pending' => (int) ($stats['pending'] ?? 0),
            'processing' => (int) ($stats['processing'] ?? 0),
            'completed' => (int) ($stats['completed'] ?? 0),
            'errors' => (int) ($stats['errors'] ?? 0),
        ];
    }

    /**
     * Statistiche della coda per un progetto specifico
     *
     * @param int $projectId ID del progetto
     * @return array Statistiche
     */
    public function getStatsByProject(int $projectId): array
    {
        $stats = Database::fetch("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
            FROM {$this->table}
            WHERE project_id = ?
        ", [$projectId]);

        return [
            'total' => (int) ($stats['total'] ?? 0),
            'pending' => (int) ($stats['pending'] ?? 0),
            'processing' => (int) ($stats['processing'] ?? 0),
            'completed' => (int) ($stats['completed'] ?? 0),
            'errors' => (int) ($stats['errors'] ?? 0),
        ];
    }

    /**
     * Verifica se oggi e un giorno valido per il run automatico
     * Legge le impostazioni da modules.settings (via ModuleLoader)
     *
     * @return bool True se oggi e giorno di run
     */
    public function isRunScheduled(): bool
    {
        // Leggi preset giorni dal modulo (via cache)
        $settings = ModuleLoader::getModuleSettings('seo-tracking');

        if (empty($settings)) {
            return false;
        }

        // Verifica se abilitato
        if (empty($settings['rank_auto_enabled'])) {
            return false;
        }

        // Converti preset in array giorni
        $preset = $settings['rank_auto_days'] ?? 'mon_thu';
        $allowedDays = match ($preset) {
            'mon_thu' => ['mon', 'thu'],
            'mon_wed_fri' => ['mon', 'wed', 'fri'],
            'daily' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'weekly' => ['mon'],
            default => ['mon', 'thu'],
        };

        // Giorno corrente (mon, tue, wed, ...)
        $today = strtolower(date('D'));

        return in_array($today, $allowedDays);
    }

    /**
     * Popola la coda con tutte le keyword di tutti i progetti
     * Da chiamare dal cron per iniziare un nuovo ciclo di verifica
     *
     * Logica:
     * - Legge tutte le keyword da st_keywords
     * - Per ogni keyword crea un record in st_rank_queue
     * - Evita duplicati (non aggiunge keyword gia in coda pending)
     *
     * @param bool $onlyTracked Se true, inserisce solo keyword con is_tracked=1
     * @return array Risultato con conteggi (inserted, skipped, total)
     */
    public function populateQueue(bool $onlyTracked = true): array
    {
        $inserted = 0;
        $skipped = 0;

        // Costruisci query per ottenere keyword con dati progetto
        $sql = "
            SELECT
                k.id as keyword_id,
                k.project_id,
                k.keyword,
                k.location_code,
                p.domain as target_domain
            FROM st_keywords k
            JOIN st_projects p ON k.project_id = p.id
            WHERE 1=1
        ";

        $params = [];

        if ($onlyTracked) {
            $sql .= " AND k.is_tracked = 1";
        }

        $sql .= " ORDER BY k.project_id, k.id";

        $keywords = Database::fetchAll($sql, $params);

        if (empty($keywords)) {
            return [
                'inserted' => 0,
                'skipped' => 0,
                'total' => 0,
                'message' => 'Nessuna keyword da processare',
            ];
        }

        // Per ogni keyword, verifica se gia in coda e inserisci
        foreach ($keywords as $kw) {
            // Verifica se gia presente in coda (pending)
            $existing = Database::fetch(
                "SELECT id FROM {$this->table}
                 WHERE project_id = ? AND keyword_id = ? AND status = 'pending'",
                [$kw['project_id'], $kw['keyword_id']]
            );

            if ($existing) {
                $skipped++;
                continue;
            }

            // Inserisci in coda
            Database::insert($this->table, [
                'project_id' => $kw['project_id'],
                'keyword_id' => $kw['keyword_id'],
                'keyword' => $kw['keyword'],
                'target_domain' => $kw['target_domain'],
                'location_code' => $kw['location_code'] ?? 2380,
                'device' => 'mobile',
                'status' => 'pending',
                'scheduled_at' => date('Y-m-d H:i:s'),
            ]);

            $inserted++;
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'total' => count($keywords),
            'message' => "Inserite {$inserted} keyword in coda, {$skipped} gia presenti",
        ];
    }

    /**
     * Popola la coda per un singolo progetto
     *
     * @param int $projectId ID del progetto
     * @param bool $onlyTracked Se true, inserisce solo keyword tracciate
     * @return array Risultato operazione
     */
    public function populateQueueForProject(int $projectId, bool $onlyTracked = true): array
    {
        $inserted = 0;
        $skipped = 0;

        // Ottieni progetto
        $project = Database::fetch("SELECT domain FROM st_projects WHERE id = ?", [$projectId]);

        if (!$project) {
            return [
                'inserted' => 0,
                'skipped' => 0,
                'total' => 0,
                'message' => 'Progetto non trovato',
            ];
        }

        // Query keyword del progetto
        $sql = "SELECT id as keyword_id, keyword, location_code
                FROM st_keywords
                WHERE project_id = ?";
        $params = [$projectId];

        if ($onlyTracked) {
            $sql .= " AND is_tracked = 1";
        }

        $keywords = Database::fetchAll($sql, $params);

        if (empty($keywords)) {
            return [
                'inserted' => 0,
                'skipped' => 0,
                'total' => 0,
                'message' => 'Nessuna keyword nel progetto',
            ];
        }

        foreach ($keywords as $kw) {
            // Verifica duplicati
            $existing = Database::fetch(
                "SELECT id FROM {$this->table}
                 WHERE project_id = ? AND keyword_id = ? AND status = 'pending'",
                [$projectId, $kw['keyword_id']]
            );

            if ($existing) {
                $skipped++;
                continue;
            }

            Database::insert($this->table, [
                'project_id' => $projectId,
                'keyword_id' => $kw['keyword_id'],
                'keyword' => $kw['keyword'],
                'target_domain' => $project['domain'],
                'location_code' => $kw['location_code'] ?? 2380,
                'device' => 'mobile',
                'status' => 'pending',
                'scheduled_at' => date('Y-m-d H:i:s'),
            ]);

            $inserted++;
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'total' => count($keywords),
            'message' => "Inserite {$inserted} keyword in coda, {$skipped} gia presenti",
        ];
    }

    /**
     * Svuota la coda pending per un progetto
     *
     * @param int $projectId ID del progetto
     * @return int Numero di record eliminati
     */
    public function clearPendingByProject(int $projectId): int
    {
        return Database::delete($this->table, "project_id = ? AND status = 'pending'", [$projectId]);
    }

    /**
     * Svuota tutta la coda pending
     *
     * @return int Numero di record eliminati
     */
    public function clearAllPending(): int
    {
        return Database::delete($this->table, "status = 'pending'");
    }

    /**
     * Resetta gli item bloccati in processing da troppo tempo
     * (es. il processo e crashato)
     *
     * @param int $minutes Minuti dopo cui considerare bloccato
     * @return int Numero di item resettati
     */
    public function resetStuckProcessing(int $minutes = 30): int
    {
        return Database::update(
            $this->table,
            [
                'status' => 'pending',
                'started_at' => null,
                'error_message' => 'Reset automatico - processing bloccato',
            ],
            "status = 'processing' AND started_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$minutes]
        );
    }

    /**
     * Trova item per ID
     *
     * @param int $id ID dell'item
     * @return array|null Item o null se non trovato
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Elimina un item specifico
     *
     * @param int $id ID dell'item
     * @return bool Successo operazione
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    // =========================================
    // METODI PER JOB MANUALI (Background Processing)
    // =========================================

    /**
     * Aggiunge keywords alla coda collegandole a un job
     *
     * @param int $projectId ID del progetto
     * @param int $jobId ID del job
     * @param array $keywordIds Array di keyword IDs
     * @param string $device 'desktop' o 'mobile'
     * @return int Numero di items inseriti
     */
    public function addBulkForJob(int $projectId, int $jobId, array $keywordIds, string $device = 'desktop'): int
    {
        // Ottieni il dominio dal progetto
        $project = Database::fetch("SELECT domain FROM st_projects WHERE id = ?", [$projectId]);

        if (!$project) {
            return 0;
        }

        $targetDomain = $project['domain'];
        $inserted = 0;

        foreach ($keywordIds as $keywordId) {
            // Ottieni dati keyword
            $keyword = Database::fetch(
                "SELECT id, keyword, location_code FROM st_keywords WHERE id = ? AND project_id = ?",
                [$keywordId, $projectId]
            );

            if (!$keyword) {
                continue;
            }

            Database::insert($this->table, [
                'project_id' => $projectId,
                'job_id' => $jobId,
                'keyword_id' => $keyword['id'],
                'keyword' => $keyword['keyword'],
                'target_domain' => $targetDomain,
                'location_code' => $keyword['location_code'] ?? 'IT',
                'device' => $device,
                'status' => 'pending',
                'scheduled_at' => date('Y-m-d H:i:s'),
            ]);

            $inserted++;
        }

        return $inserted;
    }

    /**
     * Ottiene il prossimo item pending per un job specifico
     *
     * @param int $jobId ID del job
     * @return array|null Item o null se coda vuota
     */
    public function getNextPendingForJob(int $jobId): ?array
    {
        $sql = "
            SELECT q.*, p.name as project_name, p.user_id
            FROM {$this->table} q
            JOIN st_projects p ON q.project_id = p.id
            WHERE q.job_id = ?
            AND q.status = 'pending'
            ORDER BY q.id ASC
            LIMIT 1
        ";

        return Database::fetch($sql, [$jobId]);
    }

    /**
     * Conta items pending per un job
     *
     * @param int $jobId ID del job
     * @return int Numero di items pending
     */
    public function countPendingForJob(int $jobId): int
    {
        return Database::count($this->table, "job_id = ? AND status = 'pending'", [$jobId]);
    }

    /**
     * Conta items completati per un job
     *
     * @param int $jobId ID del job
     * @return int Numero di items completati
     */
    public function countCompletedForJob(int $jobId): int
    {
        return Database::count($this->table, "job_id = ? AND status = 'completed'", [$jobId]);
    }

    /**
     * Ottiene tutti i risultati di un job
     *
     * @param int $jobId ID del job
     * @return array Lista risultati
     */
    public function getResultsForJob(int $jobId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE job_id = ?
             ORDER BY completed_at ASC",
            [$jobId]
        );
    }
}
