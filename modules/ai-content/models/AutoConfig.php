<?php
namespace Modules\AiContent\Models;

use Core\Database;

class AutoConfig
{
    private $db;
    private $table = 'aic_auto_config';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Trova config per progetto
     */
    public function findByProject(int $projectId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} WHERE project_id = ?
        ");
        $stmt->execute([$projectId]);
        $config = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($config && $config['publish_times']) {
            $config['publish_times'] = json_decode($config['publish_times'], true);
        }

        return $config ?: null;
    }

    /**
     * Crea config per progetto
     */
    public function create(int $projectId, array $data = []): int
    {
        $publishTimes = $data['publish_times'] ?? ['09:00'];
        if (is_array($publishTimes)) {
            $publishTimes = json_encode($publishTimes);
        }

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (project_id, is_active, articles_per_day, publish_times, auto_select_sources, auto_publish, wp_site_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $projectId,
            (int) ($data['is_active'] ?? 1),
            (int) ($data['articles_per_day'] ?? 1),
            $publishTimes,
            (int) ($data['auto_select_sources'] ?? 3),
            (int) ($data['auto_publish'] ?? 0),
            $data['wp_site_id'] ?? null
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Aggiorna config
     */
    public function update(int $projectId, array $data): bool
    {
        $publishTimes = $data['publish_times'] ?? null;
        if (is_array($publishTimes)) {
            $publishTimes = json_encode($publishTimes);
        }

        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET
                is_active = ?,
                articles_per_day = ?,
                publish_times = ?,
                auto_select_sources = ?,
                auto_publish = ?,
                wp_site_id = ?,
                updated_at = NOW()
            WHERE project_id = ?
        ");
        return $stmt->execute([
            (int) ($data['is_active'] ?? 1),
            (int) ($data['articles_per_day'] ?? 1),
            $publishTimes,
            (int) ($data['auto_select_sources'] ?? 3),
            (int) ($data['auto_publish'] ?? 0),
            $data['wp_site_id'] ?? null,
            $projectId
        ]);
    }

    /**
     * Crea o aggiorna (upsert)
     */
    public function upsert(int $projectId, array $data): bool
    {
        $existing = $this->findByProject($projectId);
        if ($existing) {
            return $this->update($projectId, $data);
        } else {
            return $this->create($projectId, $data) > 0;
        }
    }

    /**
     * Attiva/disattiva automazione
     */
    public function toggle(int $projectId, bool $active): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET is_active = ?, updated_at = NOW()
            WHERE project_id = ?
        ");
        return $stmt->execute([$active, $projectId]);
    }

    /**
     * Calcola prossimi slot disponibili
     * Ritorna array di datetime per scheduling keyword
     *
     * LOGICA:
     * - articles_per_day determina quante KW processare al giorno
     * - publish_times determina gli orari di pubblicazione
     * - Se articles_per_day > count(publish_times), distribuisce le KW sui tempi disponibili
     *   (es: 3 articoli/giorno con orari ['09:00','12:00'] = 2 a 09:00 e 1 a 12:00, oppure distribuiti equamente)
     */
    public function calculateScheduleSlots(int $projectId, int $count): array
    {
        $config = $this->findByProject($projectId);
        if (!$config) {
            return [];
        }

        $times = $config['publish_times'] ?? ['09:00'];
        $perDay = (int) ($config['articles_per_day'] ?? 1);

        // Conta già schedulati per ogni giorno/ora
        $stmt = $this->db->prepare("
            SELECT DATE(scheduled_at) as day, TIME_FORMAT(scheduled_at, '%H:%i') as slot_time, COUNT(*) as cnt
            FROM aic_queue
            WHERE project_id = ? AND status = 'pending'
            GROUP BY DATE(scheduled_at), TIME_FORMAT(scheduled_at, '%H:%i')
        ");
        $stmt->execute([$projectId]);

        // Mappa: day => [time => count]
        $existingByDayTime = [];
        // Mappa: day => total count
        $existingByDay = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $day = $row['day'];
            $time = $row['slot_time'];
            $existingByDayTime[$day][$time] = (int) $row['cnt'];
            $existingByDay[$day] = ($existingByDay[$day] ?? 0) + (int) $row['cnt'];
        }

        $slots = [];
        $date = new \DateTime();
        $now = new \DateTime();
        $maxDays = 365; // Limite sicurezza
        $daysChecked = 0;

        while (count($slots) < $count && $daysChecked < $maxDays) {
            $dateStr = $date->format('Y-m-d');
            $usedToday = $existingByDay[$dateStr] ?? 0;
            $availableToday = $perDay - $usedToday;

            if ($availableToday > 0) {
                // Distribuisci le KW disponibili sugli orari
                // Usa round-robin sugli orari per bilanciare
                $timeIndex = 0;
                $timeCount = count($times);

                while ($availableToday > 0 && count($slots) < $count) {
                    $time = $times[$timeIndex % $timeCount];
                    $slotTime = new \DateTime($dateStr . ' ' . $time);

                    // Salta slot passati per oggi
                    if ($slotTime <= $now) {
                        $timeIndex++;
                        // Se abbiamo controllato tutti gli orari di oggi e sono tutti passati, esci
                        if ($timeIndex >= $timeCount) {
                            break;
                        }
                        continue;
                    }

                    $slots[] = $slotTime->format('Y-m-d H:i:s');
                    $availableToday--;
                    $timeIndex++;

                    // Se abbiamo usato tutti gli orari disponibili, ricomincia il ciclo
                    // (permette più KW allo stesso orario se articles_per_day > count(times))
                    if ($timeIndex >= $timeCount) {
                        $timeIndex = 0;
                    }
                }
            }

            $date->modify('+1 day');
            $daysChecked++;
        }

        return $slots;
    }

    /**
     * Elimina config (cascade da FK, ma metodo esplicito)
     */
    public function delete(int $projectId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE project_id = ?");
        return $stmt->execute([$projectId]);
    }

    /**
     * Get remaining articles for today
     * Resets counter if day changed
     */
    public function getRemainingToday(int $projectId): int
    {
        $config = $this->findByProject($projectId);
        if (!$config) {
            return 0;
        }

        // Check if we need to reset (new day)
        $today = date('Y-m-d');
        if ($config['last_reset_date'] !== $today) {
            $this->resetDailyCounter($projectId);
            return (int) $config['articles_per_day'];
        }

        $articlesToday = (int) ($config['articles_today'] ?? 0);
        $maxPerDay = (int) ($config['articles_per_day'] ?? 1);

        return max(0, $maxPerDay - $articlesToday);
    }

    /**
     * Reset daily counter
     */
    public function resetDailyCounter(int $projectId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET articles_today = 0, last_reset_date = CURDATE()
            WHERE project_id = ?
        ");
        return $stmt->execute([$projectId]);
    }

    /**
     * Reset daily counter if new day (check and reset atomically)
     */
    public function resetDailyCounterIfNeeded(int $projectId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET articles_today = 0, last_reset_date = CURDATE()
            WHERE project_id = ? AND (last_reset_date IS NULL OR last_reset_date < CURDATE())
        ");
        $stmt->execute([$projectId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Increment articles generated today
     */
    public function incrementArticlesToday(int $projectId, int $count = 1): bool
    {
        // First check if we need to reset
        $config = $this->findByProject($projectId);
        if ($config && $config['last_reset_date'] !== date('Y-m-d')) {
            $this->resetDailyCounter($projectId);
        }

        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET articles_today = articles_today + ?,
                last_reset_date = CURDATE()
            WHERE project_id = ?
        ");
        return $stmt->execute([$count, $projectId]);
    }

    /**
     * Update last run timestamp
     */
    public function updateLastRun(int $projectId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET last_run_at = NOW()
            WHERE project_id = ?
        ");
        return $stmt->execute([$projectId]);
    }

    /**
     * Check if current time matches a publish time
     */
    public function isPublishTime(int $projectId): bool
    {
        $config = $this->findByProject($projectId);
        if (!$config || !$config['is_active']) {
            return false;
        }

        $times = $config['publish_times'] ?? [];
        $currentHour = date('H:i');

        // Check with 5 minute tolerance
        foreach ($times as $time) {
            $timeParts = explode(':', $time);
            if (count($timeParts) >= 2) {
                $targetTime = sprintf('%02d:%02d', $timeParts[0], $timeParts[1]);
                if ($targetTime === $currentHour) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get all active projects that should run now
     */
    public function getProjectsToRun(): array
    {
        $currentHour = date('H:i');

        $stmt = $this->db->prepare("
            SELECT ac.*, p.name as project_name, p.user_id
            FROM {$this->table} ac
            JOIN aic_projects p ON p.id = ac.project_id
            WHERE ac.is_active = 1
              AND p.type = 'auto'
        ");
        $stmt->execute();
        $configs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $projectsToRun = [];
        foreach ($configs as $config) {
            // Decode publish_times
            $times = json_decode($config['publish_times'] ?? '[]', true) ?: [];

            // Check if current time matches
            foreach ($times as $time) {
                $timeParts = explode(':', $time);
                if (count($timeParts) >= 2) {
                    $targetTime = sprintf('%02d:%02d', $timeParts[0], $timeParts[1]);
                    if ($targetTime === $currentHour) {
                        // Check daily limit
                        $today = date('Y-m-d');
                        $articlesToday = ($config['last_reset_date'] === $today)
                            ? (int) $config['articles_today']
                            : 0;

                        if ($articlesToday < (int) $config['articles_per_day']) {
                            $config['publish_times'] = $times;
                            $projectsToRun[] = $config;
                        }
                        break;
                    }
                }
            }
        }

        return $projectsToRun;
    }
}
