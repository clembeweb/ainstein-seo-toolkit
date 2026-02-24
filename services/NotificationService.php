<?php

namespace Services;

use Core\Database;
use Core\Settings;

/**
 * NotificationService
 * Servizio centralizzato per le notifiche in-app e via email.
 *
 * Gestisce: creazione notifiche, conteggio non lette, preferenze email,
 * invio email automatico tramite EmailService.
 */
class NotificationService
{
    /**
     * Default per invio email per tipo di notifica.
     * Se l'utente non ha override in notification_preferences, si usano questi.
     */
    const EMAIL_DEFAULTS = [
        'project_invite'          => true,
        'project_invite_accepted' => false,
        'project_invite_declined' => false,
        'operation_completed'     => true,
        'operation_failed'        => true,
    ];

    /**
     * Etichette italiane per i tipi di notifica.
     */
    const TYPE_LABELS = [
        'project_invite'          => 'Inviti progetto',
        'project_invite_accepted' => 'Inviti accettati',
        'project_invite_declined' => 'Inviti rifiutati',
        'operation_completed'     => 'Operazioni completate',
        'operation_failed'        => 'Operazioni fallite',
    ];

    /**
     * Invia una notifica a un utente.
     *
     * @param int    $userId ID utente destinatario
     * @param string $type   Tipo notifica (es. 'project_invite')
     * @param string $title  Titolo della notifica
     * @param array  $options Opzioni: body, icon, color, action_url, data, skip_email
     * @return int ID della notifica creata
     */
    public static function send(int $userId, string $type, string $title, array $options = []): int
    {
        $body      = $options['body'] ?? null;
        $icon      = $options['icon'] ?? null;
        $color     = $options['color'] ?? null;
        $actionUrl = $options['action_url'] ?? null;
        $data      = $options['data'] ?? null;
        $skipEmail = $options['skip_email'] ?? false;

        // Serializza data come JSON se array
        $dataJson = is_array($data) ? json_encode($data) : $data;

        Database::execute(
            "INSERT INTO notifications (user_id, type, title, body, icon, color, action_url, data, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$userId, $type, $title, $body, $icon, $color, $actionUrl, $dataJson]
        );

        $notificationId = (int) Database::lastInsertId();

        // Invio email se abilitato per questo tipo
        if (!$skipEmail && self::isEmailEnabled($userId, $type)) {
            $user = Database::fetch("SELECT email FROM users WHERE id = ?", [$userId]);

            if ($user && !empty($user['email'])) {
                $emailData = [
                    'title' => $title,
                    'body'  => $body,
                ];

                if ($actionUrl) {
                    $appUrl = rtrim(Settings::get('app_url', 'https://ainstein.it'), '/');
                    $emailData['action_url']   = $appUrl . $actionUrl;
                    $emailData['action_label'] = 'Vai alla pagina';
                }

                EmailService::sendTemplate(
                    $user['email'],
                    $title,
                    'notification',
                    $emailData
                );
            }
        }

        return $notificationId;
    }

    /**
     * Invia una notifica a piu utenti.
     *
     * @param array  $userIds Array di ID utente
     * @param string $type    Tipo notifica
     * @param string $title   Titolo
     * @param array  $options Opzioni (come send())
     * @return int Numero di notifiche inviate
     */
    public static function sendToMany(array $userIds, string $type, string $title, array $options = []): int
    {
        $count = 0;

        foreach ($userIds as $userId) {
            self::send((int) $userId, $type, $title, $options);
            $count++;
        }

        return $count;
    }

    /**
     * Conta le notifiche non lette di un utente.
     */
    public static function getUnreadCount(int $userId): int
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND read_at IS NULL",
            [$userId]
        );

        return $row ? (int) $row['cnt'] : 0;
    }

    /**
     * Ottieni le notifiche piu recenti di un utente.
     *
     * @param int $userId ID utente
     * @param int $limit  Numero massimo di notifiche
     * @return array
     */
    public static function getRecent(int $userId, int $limit = 15): array
    {
        $notifications = Database::fetchAll(
            "SELECT * FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );

        foreach ($notifications as &$n) {
            $n['time_ago'] = self::timeAgo($n['created_at']);
            if (!empty($n['data'])) {
                $n['data'] = json_decode($n['data'], true);
            }
        }
        unset($n);

        return $notifications;
    }

    /**
     * Ottieni tutte le notifiche paginate.
     *
     * @param int         $userId  ID utente
     * @param int         $page    Pagina corrente
     * @param int         $perPage Risultati per pagina
     * @param string|null $filter  Filtro: 'unread' per sole non lette
     * @return array ['notifications' => [...], 'total' => int, 'page' => int, 'perPage' => int]
     */
    public static function getAll(int $userId, int $page = 1, int $perPage = 20, ?string $filter = null): array
    {
        $where  = "WHERE user_id = ?";
        $params = [$userId];

        if ($filter === 'unread') {
            $where .= " AND read_at IS NULL";
        }

        // Conteggio totale
        $countRow = Database::fetch(
            "SELECT COUNT(*) AS cnt FROM notifications {$where}",
            $params
        );
        $total = $countRow ? (int) $countRow['cnt'] : 0;

        // Offset paginazione
        $offset = ($page - 1) * $perPage;

        $notifications = Database::fetchAll(
            "SELECT * FROM notifications {$where}
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        foreach ($notifications as &$n) {
            $n['time_ago'] = self::timeAgo($n['created_at']);
            if (!empty($n['data'])) {
                $n['data'] = json_decode($n['data'], true);
            }
        }
        unset($n);

        return [
            'notifications' => $notifications,
            'total'         => $total,
            'page'          => $page,
            'perPage'       => $perPage,
        ];
    }

    /**
     * Segna una notifica come letta.
     *
     * @param int $notificationId ID notifica
     * @param int $userId         ID utente (sicurezza: verifica proprieta)
     * @return bool true se aggiornata, false se gia letta o non trovata
     */
    public static function markAsRead(int $notificationId, int $userId): bool
    {
        $affected = Database::execute(
            "UPDATE notifications SET read_at = NOW()
             WHERE id = ? AND user_id = ? AND read_at IS NULL",
            [$notificationId, $userId]
        );

        return $affected > 0;
    }

    /**
     * Segna tutte le notifiche di un utente come lette.
     *
     * @return int Numero di notifiche aggiornate
     */
    public static function markAllAsRead(int $userId): int
    {
        return Database::execute(
            "UPDATE notifications SET read_at = NOW()
             WHERE user_id = ? AND read_at IS NULL",
            [$userId]
        );
    }

    /**
     * Verifica se le email sono abilitate per un tipo di notifica.
     *
     * Controlla prima le preferenze utente (notification_preferences),
     * poi i default (EMAIL_DEFAULTS).
     */
    public static function isEmailEnabled(int $userId, string $type): bool
    {
        $pref = Database::fetch(
            "SELECT email_enabled FROM notification_preferences
             WHERE user_id = ? AND type = ?",
            [$userId, $type]
        );

        if ($pref !== null) {
            return (bool) $pref['email_enabled'];
        }

        return self::EMAIL_DEFAULTS[$type] ?? false;
    }

    /**
     * Aggiorna le preferenze email di un utente.
     *
     * @param int   $userId      ID utente
     * @param array $preferences ['tipo' => bool, ...]
     */
    public static function updatePreferences(int $userId, array $preferences): void
    {
        foreach ($preferences as $type => $enabled) {
            $emailEnabled = $enabled ? 1 : 0;

            Database::execute(
                "INSERT INTO notification_preferences (user_id, type, email_enabled)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE email_enabled = VALUES(email_enabled)",
                [$userId, $type, $emailEnabled]
            );
        }
    }

    /**
     * Ottieni tutte le preferenze notifica di un utente.
     * Ritorna i default con eventuali override dell'utente.
     *
     * @return array ['tipo' => ['label' => string, 'email_enabled' => bool], ...]
     */
    public static function getPreferences(int $userId): array
    {
        // Carica override utente
        $rows = Database::fetchAll(
            "SELECT type, email_enabled FROM notification_preferences WHERE user_id = ?",
            [$userId]
        );

        $overrides = [];
        foreach ($rows as $row) {
            $overrides[$row['type']] = (bool) $row['email_enabled'];
        }

        // Merge default con override
        $result = [];
        foreach (self::TYPE_LABELS as $type => $label) {
            $result[$type] = [
                'label'         => $label,
                'email_enabled' => $overrides[$type] ?? (self::EMAIL_DEFAULTS[$type] ?? false),
            ];
        }

        return $result;
    }

    /**
     * Tempo relativo in italiano.
     *
     * @param string $datetime Datetime in formato MySQL
     * @return string Es. "adesso", "5 min fa", "2 ore fa", "ieri", "3 giorni fa", "15 gen"
     */
    private static function timeAgo(string $datetime): string
    {
        $now  = new \DateTime();
        $then = new \DateTime($datetime);
        $diff = $now->getTimestamp() - $then->getTimestamp();

        if ($diff < 60) {
            return 'adesso';
        }

        if ($diff < 3600) {
            $min = (int) floor($diff / 60);
            return $min . ' min fa';
        }

        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);
            return $hours === 1 ? '1 ora fa' : $hours . ' ore fa';
        }

        if ($diff < 172800) {
            return 'ieri';
        }

        if ($diff < 604800) {
            $days = (int) floor($diff / 86400);
            return $days . ' giorni fa';
        }

        // Mesi italiani abbreviati
        $months = [
            1 => 'gen', 2 => 'feb', 3 => 'mar', 4 => 'apr',
            5 => 'mag', 6 => 'giu', 7 => 'lug', 8 => 'ago',
            9 => 'set', 10 => 'ott', 11 => 'nov', 12 => 'dic',
        ];

        $day   = (int) $then->format('j');
        $month = (int) $then->format('n');

        return $day . ' ' . $months[$month];
    }
}
