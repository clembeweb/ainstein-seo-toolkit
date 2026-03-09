<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;
use Core\Settings;

class ApiUsage
{
    public static function increment(int $userId): void
    {
        Database::query(
            "INSERT INTO ga_api_usage (user_id, date, operations_count)
             VALUES (?, CURDATE(), 1)
             ON DUPLICATE KEY UPDATE operations_count = operations_count + 1",
            [$userId]
        );
    }

    public static function getUsageToday(int $userId): int
    {
        $result = Database::fetch(
            "SELECT operations_count FROM ga_api_usage WHERE user_id = ? AND date = CURDATE()",
            [$userId]
        );
        return (int) ($result['operations_count'] ?? 0);
    }

    public static function getGlobalUsageToday(): int
    {
        $result = Database::fetch(
            "SELECT SUM(operations_count) as total FROM ga_api_usage WHERE date = CURDATE()"
        );
        return (int) ($result['total'] ?? 0);
    }

    public static function hasQuota(int $userId): bool
    {
        $userLimit = (int) Settings::get('gads_daily_limit_per_user', 1000);
        $globalLimit = (int) Settings::get('gads_daily_limit_global', 12000);

        $userUsage = self::getUsageToday($userId);
        if ($userUsage >= $userLimit) {
            return false;
        }

        $globalUsage = self::getGlobalUsageToday();
        if ($globalUsage >= $globalLimit) {
            return false;
        }

        return true;
    }
}
