<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Controllers;

use Ainstein\Editorial\Http\Request;
use Ainstein\Editorial\Services\QuotaService;

/**
 * Stato abbonamento + quota. Protetto da LicenseAuthMiddleware.
 * Versione base M1 (tier + quota da DB). Portal URL (LS) in M6.
 */
class SubscriptionController extends BaseController
{
    public function status(Request $request): never
    {
        $site = $request->context['site'];
        $tier = (string) $request->context['tier'];

        $quota = (new QuotaService())->status((int) $request->context['user_id'], $tier);

        $this->ok([
            'tier'                => $tier,
            'subscription_status' => $site['subscription_status'] ?? 'active',
            'renews_at'           => $site['subscription_renews_at'] ?? null,
            'quota'               => [
                'articles_used'  => $quota['articles_used'],
                'articles_total' => $quota['articles_total'],
                'period_start'   => $quota['period_start'],
            ],
            'topup_balance'       => ['articles' => $quota['topup_balance']],
        ]);
    }

    public function portalUrl(Request $request): never
    {
        // Customer portal embed → M6
        $this->notImplemented('M6');
    }
}
