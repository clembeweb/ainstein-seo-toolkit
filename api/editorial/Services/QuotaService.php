<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Services;

use Core\Database;

/**
 * Calcolo quota azioni per periodo di billing.
 *
 * M1: solo conteggio (read). Enforcement (block + topup) arriva in M3 via
 * QuotaMiddleware. Il periodo di billing è mensile a partire dal giorno 1.
 */
class QuotaService
{
    private array $tiers;

    public function __construct()
    {
        $config = require __DIR__ . '/../../../config/editorial.php';
        $this->tiers = $config['tiers'];
    }

    /**
     * Ritorna lo stato quota per un utente nel periodo corrente.
     *
     * @return array{articles_used:int,articles_total:int,period_start:string,topup_balance:int}
     */
    public function status(int $userId, string $tier): array
    {
        $periodStart = date('Y-m-01');
        $used = (int) Database::fetchColumn(
            'SELECT COALESCE(SUM(cost_in_units), 0) FROM aied_actions_log
              WHERE user_id = ? AND action_type = "article" AND billing_period_start >= ?',
            [$userId, $periodStart]
        );

        $articlesTotal = (int) ($this->tiers[$tier]['articles'] ?? 0);
        $topup = (int) Database::fetchColumn(
            'SELECT COALESCE(topup_balance_articles, 0) FROM aied_users WHERE id = ?',
            [$userId]
        );

        return [
            'articles_used'  => $used,
            'articles_total' => $articlesTotal,
            'period_start'   => $periodStart,
            'topup_balance'  => $topup,
        ];
    }
}
