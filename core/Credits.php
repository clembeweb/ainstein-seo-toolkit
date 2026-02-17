<?php

namespace Core;

class Credits
{
    public static function getBalance(int $userId): float
    {
        $user = Database::fetch("SELECT credits FROM users WHERE id = ?", [$userId]);
        return (float) ($user['credits'] ?? 0);
    }

    public static function hasEnough(int $userId, float $amount): bool
    {
        return self::getBalance($userId) >= $amount;
    }

    public static function consume(int $userId, float $amount, string $action, ?string $moduleSlug = null, array $metadata = []): bool
    {
        // Zero-cost operations always succeed (MySQL rowCount=0 for no-change UPDATE)
        if ($amount <= 0) {
            return true;
        }

        // Atomic debit: prevents race condition with concurrent requests
        $affected = Database::execute(
            "UPDATE users SET credits = credits - ? WHERE id = ? AND credits >= ?",
            [$amount, $userId, $amount]
        );

        if ($affected === 0) {
            return false;
        }

        // Read new balance after atomic update
        $newBalance = self::getBalance($userId);

        // Log transazione
        Database::insert('credit_transactions', [
            'user_id' => $userId,
            'amount' => -$amount,
            'type' => 'usage',
            'description' => $action,
            'balance_after' => $newBalance,
        ]);

        // Log utilizzo dettagliato
        Database::insert('usage_log', [
            'user_id' => $userId,
            'module_slug' => $moduleSlug,
            'action' => $action,
            'credits_used' => $amount,
            'metadata' => json_encode($metadata),
        ]);

        // Aggiorna cache utente
        Auth::refresh();

        return true;
    }

    public static function add(int $userId, float $amount, string $type, string $description, ?int $adminId = null): void
    {
        // Atomic credit addition (prevents race condition with concurrent requests)
        Database::execute(
            "UPDATE users SET credits = credits + ? WHERE id = ?",
            [$amount, $userId]
        );

        $newBalance = self::getBalance($userId);

        Database::insert('credit_transactions', [
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'admin_id' => $adminId,
            'balance_after' => $newBalance,
        ]);
    }

    /**
     * Ottiene il costo di un'operazione
     * Priorità: 1. modules.settings JSON, 2. settings table, 3. config fallback, 4. default
     *
     * @param string $operation Nome operazione (es. 'serp_extraction')
     * @param string|null $moduleSlug Slug modulo opzionale (es. 'ai-content')
     * @param float $default Valore default se non trovato (default: 1)
     * @return float Costo in crediti
     */
    public static function getCost(string $operation, ?string $moduleSlug = null, float $default = 1): float
    {
        $cacheKey = "cost_{$operation}_" . ($moduleSlug ?? 'global');

        return Cache::get($cacheKey, function () use ($operation, $moduleSlug, $default) {
            // 1. Se specificato modulo, cerca in modules.settings JSON
            if ($moduleSlug) {
                $module = Database::fetch(
                    "SELECT settings FROM modules WHERE slug = ?",
                    [$moduleSlug]
                );

                if ($module && !empty($module['settings'])) {
                    $settings = json_decode($module['settings'], true);
                    $key = 'cost_' . $operation;
                    if (isset($settings[$key])) {
                        return (float) $settings[$key];
                    }
                }
            }

            // 2. Cerca in tabella settings (formato cost_X)
            $setting = Database::fetch(
                "SELECT value FROM settings WHERE key_name = ?",
                ['cost_' . $operation]
            );

            if ($setting) {
                return (float) $setting['value'];
            }

            // 3. Fallback a config/app.php
            $config = require __DIR__ . '/../config/app.php';
            if (isset($config['credit_costs'][$operation])) {
                return (float) $config['credit_costs'][$operation];
            }

            // 4. Default value
            return $default;
        }, 3600);
    }

    public static function getTransactionHistory(int $userId, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT ct.*, u.name as admin_name
             FROM credit_transactions ct
             LEFT JOIN users u ON ct.admin_id = u.id
             WHERE ct.user_id = ?
             ORDER BY ct.created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    public static function getUsageStats(int $userId, string $period = 'month'): array
    {
        $dateCondition = match ($period) {
            'day' => 'DATE(created_at) = CURDATE()',
            'week' => 'created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)',
            'month' => 'created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)',
            'year' => 'created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)',
            default => '1=1',
        };

        return Database::fetchAll(
            "SELECT module_slug, action, SUM(credits_used) as total_credits, COUNT(*) as count
             FROM usage_log
             WHERE user_id = ? AND {$dateCondition}
             GROUP BY module_slug, action
             ORDER BY total_credits DESC",
            [$userId]
        );
    }

    public static function getTotalConsumedToday(): float
    {
        $result = Database::fetch(
            "SELECT COALESCE(SUM(credits_used), 0) as total
             FROM usage_log
             WHERE DATE(created_at) = CURDATE()"
        );
        return (float) $result['total'];
    }

    public static function getTotalConsumedMonth(): float
    {
        $result = Database::fetch(
            "SELECT COALESCE(SUM(credits_used), 0) as total
             FROM usage_log
             WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
        );
        return (float) $result['total'];
    }

    public static function getTopUsers(int $limit = 10, string $period = 'month'): array
    {
        $dateCondition = match ($period) {
            'day' => 'DATE(ul.created_at) = CURDATE()',
            'week' => 'ul.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)',
            'month' => 'ul.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)',
            default => '1=1',
        };

        return Database::fetchAll(
            "SELECT u.id, u.name, u.email, SUM(ul.credits_used) as total_used
             FROM users u
             JOIN usage_log ul ON u.id = ul.user_id
             WHERE {$dateCondition}
             GROUP BY u.id
             ORDER BY total_used DESC
             LIMIT ?",
            [$limit]
        );
    }
}
