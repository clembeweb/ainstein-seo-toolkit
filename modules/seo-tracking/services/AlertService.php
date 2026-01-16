<?php

namespace Modules\SeoTracking\Services;

use Modules\SeoTracking\Models\Alert;
use Modules\SeoTracking\Models\AlertSettings;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\GscDaily;
use Modules\SeoTracking\Models\Ga4Daily;

/**
 * AlertService
 * Gestisce rilevamento e creazione alert
 */
class AlertService
{
    private Alert $alert;
    private AlertSettings $alertSettings;
    private Project $project;
    private Keyword $keyword;
    private GscDaily $gscDaily;
    private Ga4Daily $ga4Daily;

    public function __construct()
    {
        $this->alert = new Alert();
        $this->alertSettings = new AlertSettings();
        $this->project = new Project();
        $this->keyword = new Keyword();
        $this->gscDaily = new GscDaily();
        $this->ga4Daily = new Ga4Daily();
    }

    /**
     * Esegui check alert per progetto
     */
    public function checkAlerts(int $projectId): array
    {
        $settings = $this->alertSettings->getByProject($projectId);

        if (!$settings) {
            return [];
        }

        $alerts = [];

        // Check posizioni keyword
        if ($settings['position_alert_enabled']) {
            $positionAlerts = $this->checkPositionAlerts($projectId, $settings);
            $alerts = array_merge($alerts, $positionAlerts);
        }

        // Check traffico
        if ($settings['traffic_alert_enabled']) {
            $trafficAlerts = $this->checkTrafficAlerts($projectId, $settings);
            $alerts = array_merge($alerts, $trafficAlerts);
        }

        // Check revenue
        if ($settings['revenue_alert_enabled']) {
            $revenueAlerts = $this->checkRevenueAlerts($projectId, $settings);
            $alerts = array_merge($alerts, $revenueAlerts);
        }

        return $alerts;
    }

    /**
     * Check variazioni posizione keyword
     */
    private function checkPositionAlerts(int $projectId, array $settings): array
    {
        $alerts = [];
        $threshold = $settings['position_threshold'] ?? 5;
        $trackedOnly = ($settings['position_alert_keywords'] ?? 'tracked') === 'tracked';

        $filters = $trackedOnly ? ['is_tracked' => 1] : [];
        $keywords = $this->keyword->allByProject($projectId, $filters);

        foreach ($keywords as $kw) {
            $change = abs($kw['position_change'] ?? 0);

            if ($change >= $threshold) {
                $isGain = ($kw['position_change'] ?? 0) < 0; // Negative = improved

                $alertType = $isGain ? 'position_gain' : 'position_drop';
                $message = $isGain
                    ? "Keyword \"{$kw['keyword']}\" salita di {$change} posizioni"
                    : "Keyword \"{$kw['keyword']}\" scesa di {$change} posizioni";

                $alertId = $this->alert->create([
                    'project_id' => $projectId,
                    'alert_type' => $alertType,
                    'severity' => $change >= 10 ? 'high' : 'medium',
                    'message' => $message,
                    'data' => json_encode([
                        'keyword_id' => $kw['id'],
                        'keyword' => $kw['keyword'],
                        'old_position' => $kw['last_position'] + $kw['position_change'],
                        'new_position' => $kw['last_position'],
                        'change' => $kw['position_change'],
                    ]),
                ]);

                $alerts[] = ['id' => $alertId, 'type' => $alertType, 'message' => $message];
            }
        }

        return $alerts;
    }

    /**
     * Check calo traffico
     */
    private function checkTrafficAlerts(int $projectId, array $settings): array
    {
        $alerts = [];
        $threshold = $settings['traffic_drop_threshold'] ?? 20;

        // Confronta ultima settimana vs settimana precedente
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $prevEndDate = date('Y-m-d', strtotime('-8 days'));
        $prevStartDate = date('Y-m-d', strtotime('-14 days'));

        $comparison = $this->gscDaily->comparePeriods($projectId, $startDate, $endDate, $prevStartDate, $prevEndDate);

        $clicksChange = $comparison['clicks_change_pct'] ?? 0;

        if ($clicksChange <= -$threshold) {
            $message = "Traffico organico calato del " . abs(round($clicksChange)) . "% rispetto alla settimana precedente";

            $alertId = $this->alert->create([
                'project_id' => $projectId,
                'alert_type' => 'traffic_drop',
                'severity' => abs($clicksChange) >= 50 ? 'critical' : 'high',
                'message' => $message,
                'data' => json_encode([
                    'current_clicks' => $comparison['current']['total_clicks'] ?? 0,
                    'previous_clicks' => $comparison['previous']['total_clicks'] ?? 0,
                    'change_pct' => $clicksChange,
                ]),
            ]);

            $alerts[] = ['id' => $alertId, 'type' => 'traffic_drop', 'message' => $message];
        }

        return $alerts;
    }

    /**
     * Check calo revenue
     */
    private function checkRevenueAlerts(int $projectId, array $settings): array
    {
        $alerts = [];
        $threshold = $settings['revenue_drop_threshold'] ?? 20;

        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $prevEndDate = date('Y-m-d', strtotime('-8 days'));
        $prevStartDate = date('Y-m-d', strtotime('-14 days'));

        $comparison = $this->ga4Daily->comparePeriods($projectId, $startDate, $endDate, $prevStartDate, $prevEndDate);

        $revenueChange = $comparison['revenue_change_pct'] ?? 0;

        if ($revenueChange <= -$threshold) {
            $message = "Revenue organico calato del " . abs(round($revenueChange)) . "% rispetto alla settimana precedente";

            $alertId = $this->alert->create([
                'project_id' => $projectId,
                'alert_type' => 'revenue_drop',
                'severity' => abs($revenueChange) >= 50 ? 'critical' : 'high',
                'message' => $message,
                'data' => json_encode([
                    'current_revenue' => $comparison['current']['revenue'] ?? 0,
                    'previous_revenue' => $comparison['previous']['revenue'] ?? 0,
                    'change_pct' => $revenueChange,
                ]),
            ]);

            $alerts[] = ['id' => $alertId, 'type' => 'revenue_drop', 'message' => $message];
        }

        return $alerts;
    }

    /**
     * Invia digest email alert
     */
    public function sendEmailDigest(int $projectId): bool
    {
        $project = $this->project->find($projectId);
        $settings = $this->alertSettings->getByProject($projectId);

        if (!$project || !$settings || !$settings['email_enabled']) {
            return false;
        }

        $emails = $project['notification_emails'] ? json_decode($project['notification_emails'], true) : [];

        if (empty($emails)) {
            return false;
        }

        // Prendi alert non letti
        $alerts = $this->alert->getUnread($projectId);

        if (empty($alerts)) {
            return false;
        }

        // Costruisci email
        $subject = "[SEO Tracking] {$project['name']} - " . count($alerts) . " nuovi alert";

        $body = "Ciao,\n\n";
        $body .= "Ecco gli alert per il progetto {$project['name']} ({$project['domain']}):\n\n";

        foreach ($alerts as $alert) {
            $icon = match($alert['alert_type']) {
                'position_gain' => '📈',
                'position_drop' => '📉',
                'traffic_drop' => '⚠️',
                'revenue_drop' => '💰',
                default => '🔔',
            };
            $body .= "{$icon} [{$alert['severity']}] {$alert['message']}\n";
        }

        $body .= "\n---\n";
        $body .= "Visualizza tutti gli alert: " . url('/seo-tracking/projects/' . $projectId . '/alerts') . "\n";

        // Invia email (usa la funzione mail di PHP o un servizio esterno)
        foreach ($emails as $email) {
            @mail($email, $subject, $body, "From: noreply@" . $_SERVER['HTTP_HOST']);
        }

        // Marca come letti
        foreach ($alerts as $alert) {
            $this->alert->markAsRead($alert['id']);
        }

        return true;
    }
}
