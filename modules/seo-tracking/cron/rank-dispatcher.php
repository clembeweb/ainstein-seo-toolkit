<?php
/**
 * Rank Check Auto Dispatcher (CRON Scheduler)
 *
 * Esegue automaticamente rank check per keyword tracciate secondo la schedulazione configurata.
 * Elabora direttamente le keyword senza usare exec/popen
 * (compatibile con hosting condiviso come SiteGround)
 *
 * Crontab: ogni 5 minuti
 *
 * Settings (da /admin/modules/{id}/settings - modules.settings JSON):
 * - rank_auto_enabled: true/false per attivare
 * - rank_auto_days: preset giorni (mon_thu, mon_wed_fri, daily, weekly)
 * - rank_auto_time: Orario di avvio (es. "04:00")
 */

// Solo CLI
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}

// Timezone Italia
date_default_timezone_set('Europe/Rome');

// Bootstrap
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

use Core\Database;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\RankCheck;
use Modules\SeoTracking\Services\RankCheckerService;

// Log file giornaliero
define('LOG_FILE', BASE_PATH . '/storage/logs/rank_dispatcher_' . date('Y-m-d') . '.log');
define('LAST_RUN_FILE', BASE_PATH . '/storage/logs/rank_auto_last_run.txt');

/**
 * Converte preset giorni in array di codici giorno
 */
function convertDaysPreset(string $preset): array
{
    return match ($preset) {
        'mon_thu' => ['mon', 'thu'],
        'mon_wed_fri' => ['mon', 'wed', 'fri'],
        'daily' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
        'weekly' => ['mon'],
        default => ['mon', 'thu'],
    };
}

/**
 * Legge data ultimo run da file
 */
function getLastRunDate(): ?string
{
    if (!file_exists(LAST_RUN_FILE)) {
        return null;
    }
    $content = trim(file_get_contents(LAST_RUN_FILE));
    return $content ?: null;
}

/**
 * Salva data ultimo run su file
 */
function setLastRunDate(string $date): void
{
    $dir = dirname(LAST_RUN_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(LAST_RUN_FILE, $date);
}

/**
 * Log message per rank dispatcher
 */
function logRankDispatcher(string $message, string $level = 'INFO'): void
{
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] [{$level}] {$message}\n";

    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents(LOG_FILE, $logLine, FILE_APPEND);

    // Echo per monitoraggio CLI
    echo $logLine;
}

/**
 * Verifica se oggi e' un giorno schedulato
 *
 * @param array $scheduledDays Array di giorni (es. ["mon", "thu"])
 * @return bool
 */
function isScheduledDay(array $scheduledDays): bool
{
    if (empty($scheduledDays)) {
        return false;
    }

    $today = strtolower(date('D')); // mon, tue, wed, thu, fri, sat, sun
    return in_array($today, array_map('strtolower', $scheduledDays));
}

/**
 * Verifica se e' l'orario giusto per eseguire
 *
 * @param string $scheduledTime Orario schedulato (HH:MM)
 * @param string|null $lastRun Data ultimo run (YYYY-MM-DD)
 * @return bool
 */
function isScheduledTime(string $scheduledTime, ?string $lastRun): bool
{
    // Se gia' eseguito oggi, non eseguire di nuovo
    $today = date('Y-m-d');
    if ($lastRun === $today) {
        return false;
    }

    // Verifica orario
    $currentTime = date('H:i');
    $scheduledParts = explode(':', $scheduledTime);
    $scheduledHour = (int) ($scheduledParts[0] ?? 0);
    $scheduledMinute = (int) ($scheduledParts[1] ?? 0);

    $currentParts = explode(':', $currentTime);
    $currentHour = (int) $currentParts[0];
    $currentMinute = (int) $currentParts[1];

    // Se ora corrente >= orario schedulato, e' il momento giusto
    if ($currentHour > $scheduledHour) {
        return true;
    }
    if ($currentHour === $scheduledHour && $currentMinute >= $scheduledMinute) {
        return true;
    }

    return false;
}

/**
 * Calcola il prossimo run schedulato
 *
 * @param array $scheduledDays Array di giorni
 * @param string $scheduledTime Orario (HH:MM)
 * @return string Data/ora prossimo run (Y-m-d H:i:s)
 */
function calculateNextRun(array $scheduledDays, string $scheduledTime): string
{
    if (empty($scheduledDays)) {
        return date('Y-m-d H:i:s', strtotime('+7 days'));
    }

    // Mappa giorni a numeri (0=dom, 1=lun, ...)
    $dayMap = [
        'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3,
        'thu' => 4, 'fri' => 5, 'sat' => 6
    ];

    $scheduledDayNumbers = [];
    foreach ($scheduledDays as $day) {
        $dayLower = strtolower($day);
        if (isset($dayMap[$dayLower])) {
            $scheduledDayNumbers[] = $dayMap[$dayLower];
        }
    }

    if (empty($scheduledDayNumbers)) {
        return date('Y-m-d H:i:s', strtotime('+7 days'));
    }

    sort($scheduledDayNumbers);

    $currentDayNumber = (int) date('w'); // 0-6
    $currentTime = date('H:i');

    // Cerca il prossimo giorno schedulato
    $foundNextDay = false;
    $daysToAdd = 0;

    foreach ($scheduledDayNumbers as $dayNum) {
        if ($dayNum > $currentDayNumber) {
            $daysToAdd = $dayNum - $currentDayNumber;
            $foundNextDay = true;
            break;
        }
        if ($dayNum === $currentDayNumber && $currentTime < $scheduledTime) {
            // Oggi ma ancora non passato l'orario
            $daysToAdd = 0;
            $foundNextDay = true;
            break;
        }
    }

    // Se non trovato, prendi il primo giorno della prossima settimana
    if (!$foundNextDay) {
        $firstDay = $scheduledDayNumbers[0];
        $daysToAdd = 7 - $currentDayNumber + $firstDay;
    }

    $nextDate = date('Y-m-d', strtotime("+{$daysToAdd} days"));
    return $nextDate . ' ' . $scheduledTime . ':00';
}

/**
 * Popola la coda rank check con keyword tracciate da tutti i progetti
 *
 * @return array Array con count di keyword aggiunte per progetto
 */
function populateRankQueue(): array
{
    $keywordModel = new Keyword();
    $results = [];

    // Ottieni tutti i progetti con sync abilitato
    $projects = Database::fetchAll(
        "SELECT p.*
         FROM st_projects p
         WHERE p.sync_enabled = 1"
    );

    foreach ($projects as $project) {
        // Prendi keyword tracciate del progetto
        $keywords = $keywordModel->allByProject($project['id'], ['is_tracked' => 1]);

        if (empty($keywords)) {
            continue;
        }

        $count = 0;
        foreach ($keywords as $kw) {
            // Verifica se gia' in coda pending
            $existing = Database::fetch(
                "SELECT id FROM st_rank_queue
                 WHERE project_id = ? AND keyword_id = ? AND status = 'pending'",
                [$project['id'], $kw['id']]
            );

            if (!$existing) {
                // Usa NOW() di MySQL per coerenza con le query
                Database::execute(
                    "INSERT INTO st_rank_queue
                     (project_id, keyword_id, keyword, target_domain, location_code, device, status, scheduled_at)
                     VALUES (?, ?, ?, ?, ?, 'mobile', 'pending', NOW())",
                    [
                        $project['id'],
                        $kw['id'],
                        $kw['keyword'],
                        $project['domain'],
                        $kw['location_code'] ?? 'IT'
                    ]
                );
                $count++;
            }
        }

        if ($count > 0) {
            $results[$project['id']] = [
                'name' => $project['name'],
                'keywords_added' => $count,
            ];
        }
    }

    return $results;
}

/**
 * Ottieni il prossimo item pending dalla coda
 *
 * @return array|null
 */
function getNextPendingItem(): ?array
{
    return Database::fetch(
        "SELECT q.*, q.job_id, p.name as project_name, p.user_id
         FROM st_rank_queue q
         JOIN st_projects p ON q.project_id = p.id
         WHERE q.status = 'pending'
         AND q.scheduled_at <= NOW()
         ORDER BY q.scheduled_at ASC, q.created_at ASC
         LIMIT 1"
    );
}

/**
 * Marca item come completato
 *
 * @param int $queueId ID item in coda
 * @param int $rankCheckId ID del rank check creato
 * @param int|null $position Posizione trovata (null se non in top 100)
 * @param string|null $url URL trovato in SERP
 * @param int|null $jobId ID del job associato (se presente)
 */
function markItemCompleted(int $queueId, int $rankCheckId, ?int $position = null, ?string $url = null, ?int $jobId = null): void
{
    Database::update('st_rank_queue', [
        'status' => 'completed',
        'rank_check_id' => $rankCheckId,
        'result_position' => $position,
        'result_url' => $url ? substr($url, 0, 2000) : null,
        'completed_at' => date('Y-m-d H:i:s'),
    ], 'id = ?', [$queueId]);

    // Aggiorna il job se presente
    if ($jobId) {
        updateJobProgress($jobId, true, $position !== null);
    }
}

/**
 * Marca item con errore
 *
 * @param int $queueId ID item in coda
 * @param string $error Messaggio errore
 * @param int|null $jobId ID del job associato (se presente)
 */
function markItemError(int $queueId, string $error, ?int $jobId = null): void
{
    Database::update('st_rank_queue', [
        'status' => 'error',
        'error_message' => substr($error, 0, 500),
        'completed_at' => date('Y-m-d H:i:s'),
    ], 'id = ?', [$queueId]);

    // Aggiorna il job se presente
    if ($jobId) {
        updateJobProgress($jobId, false, false);
    }
}

/**
 * Aggiorna progresso del job
 *
 * @param int $jobId ID del job
 * @param bool $success Se la keyword è stata processata con successo
 * @param bool $found Se la keyword è stata trovata in SERP
 */
function updateJobProgress(int $jobId, bool $success, bool $found): void
{
    // Avvia il job se non ancora avviato
    $job = Database::fetch("SELECT status, started_at FROM st_rank_jobs WHERE id = ?", [$jobId]);
    if ($job && $job['status'] === 'pending') {
        Database::update('st_rank_jobs', [
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$jobId]);
    }

    // Incrementa contatori
    if ($success) {
        $foundIncrement = $found ? ', keywords_found = keywords_found + 1' : '';
        Database::query(
            "UPDATE st_rank_jobs SET keywords_completed = keywords_completed + 1{$foundIncrement} WHERE id = ?",
            [$jobId]
        );
    } else {
        Database::query(
            "UPDATE st_rank_jobs SET keywords_failed = keywords_failed + 1 WHERE id = ?",
            [$jobId]
        );
    }

    // Verifica se il job è completato
    checkJobCompletion($jobId);
}

/**
 * Verifica se il job è completato e aggiorna lo status
 */
function checkJobCompletion(int $jobId): void
{
    $job = Database::fetch(
        "SELECT keywords_requested, keywords_completed, keywords_failed FROM st_rank_jobs WHERE id = ?",
        [$jobId]
    );

    if (!$job) return;

    $processed = (int)$job['keywords_completed'] + (int)$job['keywords_failed'];
    $requested = (int)$job['keywords_requested'];

    if ($processed >= $requested) {
        // Calcola posizione media
        $avgPos = Database::fetch(
            "SELECT AVG(result_position) as avg_pos FROM st_rank_queue WHERE job_id = ? AND result_position IS NOT NULL",
            [$jobId]
        );

        Database::update('st_rank_jobs', [
            'status' => 'completed',
            'avg_position' => $avgPos['avg_pos'] ? round((float)$avgPos['avg_pos'], 2) : null,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$jobId]);

        logRankDispatcher("Job #{$jobId} completato!");
    }
}

/**
 * Conta items pending nella coda
 */
function countPendingItems(): int
{
    $result = Database::fetch("SELECT COUNT(*) as cnt FROM st_rank_queue WHERE status = 'pending'");
    return (int) ($result['cnt'] ?? 0);
}

/**
 * Recupera posizione GSC media per confronto
 */
function getGscPosition(int $projectId, string $keyword): ?float
{
    $result = Database::fetch(
        "SELECT AVG(position) as avg_position
         FROM st_gsc_data
         WHERE project_id = ?
           AND query = ?
           AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
           AND impressions > 0",
        [$projectId, $keyword]
    );

    if ($result && $result['avg_position'] !== null) {
        return round((float) $result['avg_position'], 2);
    }

    return null;
}

/**
 * Main dispatcher
 */
function runDispatcher(): void
{
    logRankDispatcher("=== RANK DISPATCHER START ===");

    // Verifica se il rank auto check e' abilitato (legge da modules.settings)
    $enabled = ModuleLoader::getSetting('seo-tracking', 'rank_auto_enabled', false);
    if (!$enabled) {
        logRankDispatcher("Rank auto check disabilitato");
        logRankDispatcher("=== RANK DISPATCHER END ===\n");
        return;
    }

    // Leggi configurazione schedulazione da module settings
    $daysPreset = ModuleLoader::getSetting('seo-tracking', 'rank_auto_days', 'mon_thu');
    $scheduledDays = convertDaysPreset($daysPreset);
    $scheduledTime = ModuleLoader::getSetting('seo-tracking', 'rank_auto_time', '04:00');

    // Leggi last_run da tabella dedicata o file
    $lastRun = getLastRunDate();

    logRankDispatcher("Configurazione: giorni=" . implode(',', $scheduledDays) . ", ora={$scheduledTime}, ultimo run={$lastRun}");

    // Verifica se e' il giorno giusto
    if (!isScheduledDay($scheduledDays)) {
        logRankDispatcher("Oggi non e' un giorno schedulato (" . date('D') . ")");
        // Processa comunque eventuali item rimasti in coda
        $pendingCount = countPendingItems();
        if ($pendingCount === 0) {
            logRankDispatcher("=== RANK DISPATCHER END ===\n");
            return;
        }
        logRankDispatcher("Ci sono {$pendingCount} item in coda, processo comunque");
    } else {
        // E' un giorno schedulato, verifica orario
        if (!isScheduledTime($scheduledTime, $lastRun)) {
            logRankDispatcher("Non e' ancora l'orario schedulato ({$scheduledTime}) o gia' eseguito oggi");
            // Processa comunque eventuali item rimasti in coda
            $pendingCount = countPendingItems();
            if ($pendingCount === 0) {
                logRankDispatcher("=== RANK DISPATCHER END ===\n");
                return;
            }
            logRankDispatcher("Ci sono {$pendingCount} item in coda, processo comunque");
        } else {
            // E' il momento giusto per popolare la coda (se vuota)
            $pendingCount = countPendingItems();
            if ($pendingCount === 0) {
                logRankDispatcher("Coda vuota, popolo con keyword tracciate...");
                $populated = populateRankQueue();

                $totalAdded = 0;
                foreach ($populated as $projectId => $info) {
                    logRankDispatcher("  Progetto {$info['name']}: +{$info['keywords_added']} keyword");
                    $totalAdded += $info['keywords_added'];
                }

                if ($totalAdded > 0) {
                    logRankDispatcher("Totale keyword aggiunte: {$totalAdded}");
                } else {
                    logRankDispatcher("Nessuna keyword da aggiungere");
                }

                // Aggiorna last_run
                setLastRunDate(date('Y-m-d'));
                $nextRun = calculateNextRun($scheduledDays, $scheduledTime);
                logRankDispatcher("Prossimo run schedulato: {$nextRun}");
            }
        }
    }

    // Processa UN item dalla coda (one-at-a-time per evitare timeout)
    $queueItem = getNextPendingItem();

    if (!$queueItem) {
        logRankDispatcher("Coda vuota, nessun item da processare");
        logRankDispatcher("=== RANK DISPATCHER END ===\n");
        return;
    }

    $keyword = $queueItem['keyword'];
    $projectId = (int) $queueItem['project_id'];
    $userId = (int) $queueItem['user_id'];
    $queueId = (int) $queueItem['id'];
    $keywordId = $queueItem['keyword_id'] ? (int) $queueItem['keyword_id'] : null;
    $jobId = $queueItem['job_id'] ? (int) $queueItem['job_id'] : null;
    $targetDomain = $queueItem['target_domain'];
    $locationCode = $queueItem['location_code'] ?? 'IT';
    $device = $queueItem['device'] ?? 'mobile';

    logRankDispatcher("Processo keyword: {$keyword} (progetto: {$queueItem['project_name']})");

    // Marca come processing
    Database::update('st_rank_queue', [
        'status' => 'processing',
        'started_at' => date('Y-m-d H:i:s'),
    ], 'id = ?', [$queueId]);

    try {
        // Verifica configurazione SERP API
        $rankChecker = new RankCheckerService();

        if (!$rankChecker->isConfigured()) {
            throw new \Exception('Nessun provider SERP configurato (Serper.dev o SERP API)');
        }

        // Esegui rank check
        $result = $rankChecker->checkPosition($keyword, $targetDomain, [
            'location_code' => $locationCode,
            'device' => $device,
        ]);

        // Reconnect al database dopo chiamata API lunga
        Database::reconnect();

        // Recupera posizione GSC per confronto
        $gscPosition = getGscPosition($projectId, $keyword);
        $positionDiff = null;

        if ($result['found'] && $result['position'] !== null && $gscPosition !== null) {
            $positionDiff = round($result['position'] - $gscPosition, 1);
        }

        // Salva risultato in st_rank_checks
        $rankCheckModel = new RankCheck();
        $serpPosition = $result['found'] ? $result['position'] : null;
        $serpUrl = $result['url'] ?? null;

        $checkId = $rankCheckModel->create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'keyword' => $keyword,
            'target_domain' => $targetDomain,
            'location' => $result['location'] ?? $locationCode,
            'language' => $result['language'] ?? 'it',
            'device' => $device,
            'serp_position' => $serpPosition,
            'serp_url' => $serpUrl,
            'serp_title' => $result['title'] ?? null,
            'serp_snippet' => $result['snippet'] ?? null,
            'gsc_position' => $gscPosition,
            'position_diff' => $positionDiff,
            'total_organic_results' => $result['total_organic_results'] ?? null,
            'credits_used' => 0, // Automatico, gratuito
            'checked_at' => date('Y-m-d H:i:s'), // Usa timezone PHP (Europe/Rome)
        ]);

        // Aggiorna last_position nella tabella keywords
        if ($result['found'] && $serpPosition !== null && $keywordId) {
            Database::update('st_keywords', [
                'last_position' => $serpPosition,
                'last_updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$keywordId]);
        }

        // Marca item come completato con posizione e URL
        markItemCompleted($queueId, $checkId, $serpPosition, $serpUrl, $jobId);

        $positionStr = $result['found'] ? "#{$serpPosition}" : 'NON TROVATO';
        logRankDispatcher("  OK: posizione {$positionStr}");

    } catch (\Exception $e) {
        // Reconnect in caso di errore
        Database::reconnect();

        // Marca errore
        markItemError($queueId, $e->getMessage(), $jobId);
        logRankDispatcher("  ERRORE: " . $e->getMessage(), 'ERROR');
    }

    // Statistiche finali
    $remainingCount = countPendingItems();
    logRankDispatcher("Item rimanenti in coda: {$remainingCount}");
    logRankDispatcher("=== RANK DISPATCHER END ===\n");
}

// Esegui
try {
    runDispatcher();
} catch (\Exception $e) {
    logRankDispatcher("FATAL: " . $e->getMessage(), 'FATAL');
    exit(1);
}

exit(0);
