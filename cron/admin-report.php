<?php
/**
 * Admin Report — Digest settimanale piattaforma
 * Cron: 0 8 * * 1 (Lunedi alle 8:00)
 * SiteGround: /usr/bin/php /home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/cron/admin-report.php
 */
require_once __DIR__ . '/bootstrap.php';

use Core\Database;
use Core\Logger;

$logger = Logger::channel('admin-report');
$logger->info('Inizio generazione report admin');

try {
    // Periodo: ultima settimana
    $periodStart = date('Y-m-d', strtotime('last monday'));
    $periodEnd = date('Y-m-d');
    $period = "Settimana " . date('W') . "/" . date('Y');

    // 1. Nuovi utenti nel periodo
    $newUsers = (int) Database::fetch(
        "SELECT COUNT(*) as cnt FROM users WHERE created_at >= ?",
        [$periodStart]
    )['cnt'];

    // 2. Totale utenti
    $totalUsers = (int) Database::fetch(
        "SELECT COUNT(*) as cnt FROM users"
    )['cnt'];

    // 3. Utenti attivi (logged in durante il periodo)
    // Check if last_login_at column exists, fallback to 0
    $activeUsers = 0;
    try {
        $activeUsers = (int) Database::fetch(
            "SELECT COUNT(*) as cnt FROM users WHERE last_login_at >= ?",
            [$periodStart]
        )['cnt'];
    } catch (\Exception $e) {
        // Column may not exist
    }

    // 4. Crediti consumati
    $creditsConsumed = 0;
    try {
        $creditsConsumed = (int) (Database::fetch(
            "SELECT COALESCE(SUM(ABS(amount)), 0) as total FROM credit_transactions WHERE type = 'consume' AND created_at >= ?",
            [$periodStart]
        )['total'] ?? 0);
    } catch (\Exception $e) {}

    // 5. Top moduli per crediti consumati
    $topModules = [];
    try {
        $topModules = Database::fetchAll(
            "SELECT module, SUM(ABS(amount)) as total FROM credit_transactions WHERE type = 'consume' AND created_at >= ? GROUP BY module ORDER BY total DESC LIMIT 5",
            [$periodStart]
        );
    } catch (\Exception $e) {}

    $topModulesHtml = '';
    if (!empty($topModules)) {
        $topModulesHtml = '<ol style="margin:8px 0;padding-left:20px;">';
        foreach ($topModules as $m) {
            $topModulesHtml .= '<li style="margin:4px 0;">' . htmlspecialchars($m['module'] ?? 'Sconosciuto') . ' (' . (int)$m['total'] . ' crediti)</li>';
        }
        $topModulesHtml .= '</ol>';
    } else {
        $topModulesHtml = '<p style="color:#94a3b8;">Nessuna attivita nel periodo.</p>';
    }

    // 6. Errori API
    $apiErrors = 0;
    try {
        $apiErrors = (int) Database::fetch(
            "SELECT COUNT(*) as cnt FROM api_logs WHERE http_code >= 400 AND created_at >= ?",
            [$periodStart]
        )['cnt'];
    } catch (\Exception $e) {}

    // 7. Job falliti
    $failedJobs = 0;
    try {
        $failedJobs = (int) Database::fetch(
            "SELECT COUNT(*) as cnt FROM background_jobs WHERE status = 'failed' AND created_at >= ?",
            [$periodStart]
        )['cnt'];
    } catch (\Exception $e) {}

    // Invia a tutti gli admin attivi
    $admins = Database::fetchAll(
        "SELECT id, email FROM users WHERE is_admin = 1 AND is_active = 1"
    );

    if (empty($admins)) {
        $logger->info('Nessun admin trovato, skip invio');
        exit(0);
    }

    $data = [
        'period' => $period,
        'new_users' => $newUsers,
        'total_users' => $totalUsers,
        'active_users' => $activeUsers,
        'credits_consumed' => $creditsConsumed,
        'top_modules_html' => $topModulesHtml,
        'api_errors' => $apiErrors,
        'failed_jobs' => $failedJobs,
    ];

    $sent = 0;
    foreach ($admins as $admin) {
        Database::reconnect();
        $result = \Services\EmailService::sendTemplate(
            $admin['email'],
            "Report piattaforma {$period}",
            'admin-report',
            $data,
            $admin['id']
        );
        if ($result['success']) {
            $sent++;
        } else {
            $logger->error("Invio fallito a {$admin['email']}: {$result['message']}");
        }
    }

    $logger->info("Report inviato a {$sent}/" . count($admins) . " admin");

} catch (\Exception $e) {
    $logger->error('Admin report failed: ' . $e->getMessage());
    exit(1);
}
