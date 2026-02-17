<?php

namespace Admin\Controllers;

use Core\Database;
use Core\View;
use Core\Auth;
use Core\Credits;
use Core\ModuleLoader;
use Core\Middleware;

class FinanceController
{
    public function __construct()
    {
        Middleware::admin();
    }

    public function index(): string
    {
        $period = $_GET['period'] ?? '30';
        $startDate = $this->getStartDate($period);
        $prevStartDate = $this->getPrevStartDate($period, $startDate);

        $tab = $_GET['tab'] ?? 'overview';

        // Provider names map
        $providerNames = [
            'dataforseo' => 'DataForSEO',
            'serpapi' => 'SerpAPI',
            'serper' => 'Serper.dev',
            'google_gsc' => 'Google GSC',
            'google_oauth' => 'Google OAuth',
            'google_ga4' => 'Google GA4',
            'rapidapi_keyword' => 'RapidAPI Keyword',
            'rapidapi_keyword_insight' => 'RapidAPI Insight',
            'keywordseverywhere' => 'Keywords Everywhere',
            'openai_dalle' => 'OpenAI DALL-E',
            'anthropic' => 'Anthropic',
            'openai' => 'OpenAI',
        ];

        // Module names map
        $moduleNames = [
            'ai-content' => 'AI Content',
            'seo-audit' => 'SEO Audit',
            'seo-tracking' => 'SEO Tracking',
            'keyword-research' => 'Keyword Research',
            'ads-analyzer' => 'Ads Analyzer',
            'internal-links' => 'Internal Links',
            'content-creator' => 'Content Creator',
            'ai-optimizer' => 'AI Optimizer',
        ];

        // Tab 1: Overview
        $overview = $this->getOverviewData($startDate, $prevStartDate);

        // Tab 2: Costi API (solo se tab attivo per performance)
        $apiCosts = ($tab === 'api-costs') ? $this->getApiCostsData($startDate) : [];

        // Tab 3: Crediti & Utenti
        $creditsData = ($tab === 'credits') ? $this->getCreditsData($startDate, $period) : [];

        // Tab 4: Piani & Revenue
        $plansData = ($tab === 'plans') ? $this->getPlansData($startDate) : [];

        return View::render('admin/finance', [
            'title' => 'Finance',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'period' => $period,
            'tab' => $tab,
            'overview' => $overview,
            'apiCosts' => $apiCosts,
            'creditsData' => $creditsData,
            'plansData' => $plansData,
            'providerNames' => $providerNames,
            'moduleNames' => $moduleNames,
        ]);
    }

    private function getStartDate(string $period): string
    {
        return match ($period) {
            '7' => date('Y-m-d', strtotime('-7 days')),
            '30' => date('Y-m-d', strtotime('-30 days')),
            '90' => date('Y-m-d', strtotime('-90 days')),
            'ytd' => date('Y-01-01'),
            default => date('Y-m-d', strtotime('-30 days')),
        };
    }

    private function getPrevStartDate(string $period, string $startDate): string
    {
        $days = (int) ((strtotime('today') - strtotime($startDate)) / 86400);
        return date('Y-m-d', strtotime("-{$days} days", strtotime($startDate)));
    }

    // ─── Tab 1: Overview ─────────────────────────────────────

    private function getOverviewData(string $startDate, string $prevStartDate): array
    {
        // Current period KPIs
        $apiCost = Database::fetch(
            "SELECT COALESCE(SUM(cost), 0) as total FROM api_logs WHERE created_at >= ?",
            [$startDate]
        );
        $aiCost = Database::fetch(
            "SELECT COALESCE(SUM(estimated_cost), 0) as total FROM ai_logs WHERE created_at >= ?",
            [$startDate]
        );
        $creditsConsumed = Database::fetch(
            "SELECT COALESCE(SUM(credits_used), 0) as total FROM usage_log WHERE created_at >= ?",
            [$startDate]
        );
        $creditsDistributed = Database::fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM credit_transactions
             WHERE type IN ('purchase','subscription','manual','bonus') AND amount > 0 AND created_at >= ?",
            [$startDate]
        );

        // Previous period for delta
        $prevApiCost = Database::fetch(
            "SELECT COALESCE(SUM(cost), 0) as total FROM api_logs WHERE created_at >= ? AND created_at < ?",
            [$prevStartDate, $startDate]
        );
        $prevAiCost = Database::fetch(
            "SELECT COALESCE(SUM(estimated_cost), 0) as total FROM ai_logs WHERE created_at >= ? AND created_at < ?",
            [$prevStartDate, $startDate]
        );
        $prevCreditsConsumed = Database::fetch(
            "SELECT COALESCE(SUM(credits_used), 0) as total FROM usage_log WHERE created_at >= ? AND created_at < ?",
            [$prevStartDate, $startDate]
        );
        $prevCreditsDistributed = Database::fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM credit_transactions
             WHERE type IN ('purchase','subscription','manual','bonus') AND amount > 0 AND created_at >= ? AND created_at < ?",
            [$prevStartDate, $startDate]
        );

        // Trend costi giornaliero
        $costTrend = Database::fetchAll(
            "SELECT DATE(created_at) as day, COALESCE(SUM(cost), 0) as api_cost
             FROM api_logs WHERE created_at >= ?
             GROUP BY DATE(created_at) ORDER BY day ASC",
            [$startDate]
        );
        $aiCostTrend = Database::fetchAll(
            "SELECT DATE(created_at) as day, COALESCE(SUM(estimated_cost), 0) as ai_cost
             FROM ai_logs WHERE created_at >= ?
             GROUP BY DATE(created_at) ORDER BY day ASC",
            [$startDate]
        );

        // Provider breakdown
        $providerBreakdown = Database::fetchAll(
            "SELECT provider, COALESCE(SUM(cost), 0) as total_cost, COUNT(*) as calls
             FROM api_logs WHERE created_at >= ?
             GROUP BY provider ORDER BY total_cost DESC",
            [$startDate]
        );

        // AI provider breakdown
        $aiProviderBreakdown = Database::fetchAll(
            "SELECT provider, COALESCE(SUM(estimated_cost), 0) as total_cost, COUNT(*) as calls
             FROM ai_logs WHERE created_at >= ?
             GROUP BY provider ORDER BY total_cost DESC",
            [$startDate]
        );

        // Credits per module
        $creditsByModule = Database::fetchAll(
            "SELECT module_slug, COALESCE(SUM(credits_used), 0) as total_credits, COUNT(*) as operations
             FROM usage_log WHERE created_at >= ?
             GROUP BY module_slug ORDER BY total_credits DESC",
            [$startDate]
        );

        return [
            'api_cost' => (float) $apiCost['total'],
            'ai_cost' => (float) $aiCost['total'],
            'credits_consumed' => (float) $creditsConsumed['total'],
            'credits_distributed' => (float) $creditsDistributed['total'],
            'prev_api_cost' => (float) $prevApiCost['total'],
            'prev_ai_cost' => (float) $prevAiCost['total'],
            'prev_credits_consumed' => (float) $prevCreditsConsumed['total'],
            'prev_credits_distributed' => (float) $prevCreditsDistributed['total'],
            'cost_trend' => $costTrend,
            'ai_cost_trend' => $aiCostTrend,
            'provider_breakdown' => $providerBreakdown,
            'ai_provider_breakdown' => $aiProviderBreakdown,
            'credits_by_module' => $creditsByModule,
        ];
    }

    // ─── Tab 2: Costi API ────────────────────────────────────

    private function getApiCostsData(string $startDate): array
    {
        // Provider table
        $providerStats = Database::fetchAll(
            "SELECT provider, COUNT(*) as calls,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count,
                    SUM(CASE WHEN status = 'rate_limited' THEN 1 ELSE 0 END) as rate_limited_count,
                    COALESCE(SUM(cost), 0) as total_cost,
                    COALESCE(AVG(cost), 0) as avg_cost,
                    COALESCE(AVG(duration_ms), 0) as avg_duration
             FROM api_logs WHERE created_at >= ?
             GROUP BY provider ORDER BY total_cost DESC",
            [$startDate]
        );

        // AI model table
        $aiModelStats = Database::fetchAll(
            "SELECT provider, model, COUNT(*) as calls,
                    COALESCE(SUM(tokens_input), 0) as total_input,
                    COALESCE(SUM(tokens_output), 0) as total_output,
                    COALESCE(SUM(estimated_cost), 0) as total_cost,
                    COALESCE(AVG(duration_ms), 0) as avg_duration
             FROM ai_logs WHERE created_at >= ?
             GROUP BY provider, model ORDER BY total_cost DESC",
            [$startDate]
        );

        // Daily stacked cost trend (API + AI combined by day)
        $dailyCostTrend = Database::fetchAll(
            "SELECT day, SUM(api_cost) as api_cost, SUM(ai_cost) as ai_cost FROM (
                SELECT DATE(created_at) as day, SUM(cost) as api_cost, 0 as ai_cost
                FROM api_logs WHERE created_at >= ? GROUP BY DATE(created_at)
                UNION ALL
                SELECT DATE(created_at) as day, 0 as api_cost, SUM(estimated_cost) as ai_cost
                FROM ai_logs WHERE created_at >= ? GROUP BY DATE(created_at)
             ) combined GROUP BY day ORDER BY day ASC",
            [$startDate, $startDate]
        );

        // Top 10 most expensive calls (mix API + AI)
        // COLLATE needed: api_logs uses utf8mb4_unicode_ci, ai_logs uses utf8mb4_0900_ai_ci
        $topExpensiveCalls = Database::fetchAll(
            "(SELECT 'api' as type, id, provider COLLATE utf8mb4_unicode_ci as provider,
                     endpoint COLLATE utf8mb4_unicode_ci as detail, cost as cost_usd,
                     duration_ms, module_slug COLLATE utf8mb4_unicode_ci as module_slug, created_at
              FROM api_logs WHERE created_at >= ? AND cost > 0 ORDER BY cost DESC LIMIT 10)
             UNION ALL
             (SELECT 'ai' as type, id, provider COLLATE utf8mb4_unicode_ci as provider,
                     model COLLATE utf8mb4_unicode_ci as detail, estimated_cost as cost_usd,
                     duration_ms, module_slug COLLATE utf8mb4_unicode_ci as module_slug, created_at
              FROM ai_logs WHERE created_at >= ? AND estimated_cost > 0 ORDER BY estimated_cost DESC LIMIT 10)
             ORDER BY cost_usd DESC LIMIT 10",
            [$startDate, $startDate]
        );

        return [
            'provider_stats' => $providerStats,
            'ai_model_stats' => $aiModelStats,
            'daily_cost_trend' => $dailyCostTrend,
            'top_expensive' => $topExpensiveCalls,
        ];
    }

    // ─── Tab 3: Crediti & Utenti ─────────────────────────────

    private function getCreditsData(string $startDate, string $period): array
    {
        // KPIs
        $creditsToday = Credits::getTotalConsumedToday();
        $creditsMonth = Credits::getTotalConsumedMonth();

        $totalApiCost = Database::fetch(
            "SELECT COALESCE(SUM(cost), 0) as total FROM api_logs WHERE created_at >= ?",
            [$startDate]
        );
        $totalAiCost = Database::fetch(
            "SELECT COALESCE(SUM(estimated_cost), 0) as total FROM ai_logs WHERE created_at >= ?",
            [$startDate]
        );
        $totalCreditsUsed = Database::fetch(
            "SELECT COALESCE(SUM(credits_used), 0) as total FROM usage_log WHERE created_at >= ?",
            [$startDate]
        );

        $totalCost = (float) $totalApiCost['total'] + (float) $totalAiCost['total'];
        $totalCredits = (float) $totalCreditsUsed['total'];
        $costPerCredit = $totalCredits > 0 ? $totalCost / $totalCredits : 0;

        // Credit flow (consumed vs distributed)
        $creditsConsumed = $totalCredits;
        $creditsDistributed = Database::fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM credit_transactions
             WHERE type IN ('purchase','subscription','manual','bonus') AND amount > 0 AND created_at >= ?",
            [$startDate]
        );

        // Top users - map period to Credits method
        $creditsPeriod = match ($period) {
            '7' => 'week',
            '30' => 'month',
            '90' => 'year',
            'ytd' => 'year',
            default => 'month',
        };
        $topUsers = Credits::getTopUsers(10, $creditsPeriod);

        // Credits by module and action
        $creditsByAction = Database::fetchAll(
            "SELECT module_slug, action, COUNT(*) as count,
                    COALESCE(SUM(credits_used), 0) as total_credits,
                    COALESCE(AVG(credits_used), 0) as avg_credits
             FROM usage_log WHERE created_at >= ?
             GROUP BY module_slug, action
             ORDER BY total_credits DESC
             LIMIT 30",
            [$startDate]
        );

        return [
            'credits_today' => $creditsToday,
            'credits_month' => $creditsMonth,
            'cost_per_credit' => $costPerCredit,
            'credits_consumed' => $creditsConsumed,
            'credits_distributed' => (float) $creditsDistributed['total'],
            'top_users' => $topUsers,
            'credits_by_action' => $creditsByAction,
        ];
    }

    // ─── Tab 4: Piani & Revenue ──────────────────────────────

    private function getPlansData(string $startDate): array
    {
        $totalUsers = Database::count('users');

        // Check if users.plan_id column exists
        $hasPlanId = false;
        try {
            $cols = Database::fetchAll("SHOW COLUMNS FROM users LIKE 'plan_id'");
            $hasPlanId = !empty($cols);
        } catch (\Exception $e) {
            // Column doesn't exist
        }

        if ($hasPlanId) {
            // Plan adoption with user join
            $planAdoption = Database::fetchAll(
                "SELECT p.id, p.name, p.slug, p.price_monthly, p.price_yearly, p.credits_monthly,
                        COUNT(u.id) as user_count
                 FROM plans p
                 LEFT JOIN users u ON u.plan_id = p.id
                 WHERE p.is_active = 1
                 GROUP BY p.id
                 ORDER BY p.price_monthly ASC"
            );

            $usersWithoutPlan = Database::fetch(
                "SELECT COUNT(*) as total FROM users WHERE plan_id IS NULL"
            );
        } else {
            // plan_id not yet added - show plans with 0 users
            $planAdoption = Database::fetchAll(
                "SELECT id, name, slug, price_monthly, price_yearly, credits_monthly, 0 as user_count
                 FROM plans WHERE is_active = 1
                 ORDER BY price_monthly ASC"
            );
            $usersWithoutPlan = ['total' => $totalUsers];
        }

        // MRR calculation
        $mrr = 0;
        foreach ($planAdoption as $plan) {
            $mrr += (float) $plan['price_monthly'] * (int) $plan['user_count'];
        }

        // API + AI costs last 30 days for margin
        $costs30d = Database::fetch(
            "SELECT
                (SELECT COALESCE(SUM(cost), 0) FROM api_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as api_cost,
                (SELECT COALESCE(SUM(estimated_cost), 0) FROM ai_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as ai_cost
             FROM DUAL"
        );

        // Stripe config check
        $config = require __DIR__ . '/../../config/app.php';
        $stripeEnabled = !empty($config['stripe']['enabled']);

        return [
            'total_users' => $totalUsers,
            'plan_adoption' => $planAdoption,
            'users_without_plan' => (int) $usersWithoutPlan['total'],
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'api_cost_30d' => (float) ($costs30d['api_cost'] ?? 0),
            'ai_cost_30d' => (float) ($costs30d['ai_cost'] ?? 0),
            'stripe_enabled' => $stripeEnabled,
        ];
    }

    private function calcDelta(float $current, float $previous): ?float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : null;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
