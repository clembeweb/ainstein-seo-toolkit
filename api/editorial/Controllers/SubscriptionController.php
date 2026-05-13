<?php

namespace Editorial\Controllers;

use Core\Database;
use Editorial\Middleware\LicenseAuthMiddleware;
use Editorial\Services\LicenseManager;

/**
 * SubscriptionController
 *
 * GET /subscription/status   stato sottoscrizione + quota corrente (M1.4)
 * POST /subscription/portal-url   link Lemon Squeezy customer portal (M6)
 */
class SubscriptionController extends BaseController
{
    /**
     * GET /subscription/status
     * Risposta:
     * {
     *   tier: "starter",
     *   subscription_status: "active",
     *   sites_used: 1,
     *   sites_limit: 1,
     *   quota: {
     *     period_start: "2026-05-01",
     *     articles_used: 0,
     *     articles_total: 4,
     *     meta_tags_used: 0,
     *     images_used: 0,
     *     keyword_research_used: 0,
     *     internal_link_scan_used: 0
     *   },
     *   topup_balance: { articles: 0, meta_tags: 0, images: 0 }
     * }
     */
    public function status(): string
    {
        $user = LicenseAuthMiddleware::$currentUser;
        if (!$user) {
            return $this->jsonError('Auth context missing', 500);
        }

        $tier = $user['tier'];
        $tierLimits = LicenseManager::TIER_LIMITS[$tier] ?? LicenseManager::TIER_LIMITS['starter'];
        $articlesTotal = (int) ($tierLimits['articles'] ?? 0);

        $pdo = Database::getConnection();

        // Conta siti attivi
        $sitesUsed = (int) $pdo->query(
            "SELECT COUNT(*) FROM aied_sites WHERE user_id = " . (int) $user['id'] . " AND status = 'active'"
        )->fetchColumn();

        // Site limit dinamico (TODO: salvarlo su user al momento dell'attivazione per non
        // dover ricontattare LS qui; per ora deriviamo dal tier).
        $sitesLimit = self::tierSiteLimit($tier);

        // Conta azioni del periodo corrente (mese corrente)
        $periodStart = date('Y-m-01');
        $stmt = $pdo->prepare(
            "SELECT action_type, COALESCE(SUM(cost_in_units), 0) AS units
             FROM aied_actions_log
             WHERE user_id = ? AND billing_period_start = ?
             GROUP BY action_type"
        );
        $stmt->execute([(int) $user['id'], $periodStart]);
        $usage = [
            'article' => 0,
            'meta_tag' => 0,
            'image' => 0,
            'keyword_research' => 0,
            'internal_link_scan' => 0,
        ];
        while ($row = $stmt->fetch()) {
            $usage[$row['action_type']] = (int) $row['units'];
        }

        return $this->jsonOk([
            'tier' => $tier,
            'subscription_status' => $user['subscription_status'],
            'sites_used' => $sitesUsed,
            'sites_limit' => $sitesLimit,
            'quota' => [
                'period_start' => $periodStart,
                'articles_used' => $usage['article'],
                'articles_total' => $articlesTotal,
                'meta_tags_used' => $usage['meta_tag'],
                'images_used' => $usage['image'],
                'keyword_research_used' => $usage['keyword_research'],
                'internal_link_scan_used' => $usage['internal_link_scan'],
            ],
            'topup_balance' => [
                'articles' => 0,
                'meta_tags' => 0,
                'images' => 0,
            ],
        ]);
    }

    /** POST /subscription/portal-url */
    public function portalUrl(): string
    {
        return $this->jsonNotImplemented('6');
    }

    private static function tierSiteLimit(string $tier): int
    {
        // Mirror dei limiti definiti in MockLemonSqueezyClient. Verra' sostituito
        // da una colonna sites_limit su aied_users in una milestone futura.
        return match ($tier) {
            'pro' => 3,
            'business' => 10,
            'agency' => 50,
            default => 1,
        };
    }
}
