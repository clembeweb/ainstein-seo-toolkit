<?php

namespace Services;

use Core\Settings;
use Core\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * EmailService
 * Servizio centralizzato per l'invio email via SMTP.
 * Legge la configurazione dalla tabella settings (DB).
 */
class EmailService
{
    /**
     * Verifica se SMTP e configurato
     */
    public static function isConfigured(): bool
    {
        $host = Settings::get('smtp_host', '');
        $username = Settings::get('smtp_username', '');
        return !empty($host) && !empty($username);
    }

    /**
     * Ottieni stato configurazione per admin
     */
    public static function getStatus(): array
    {
        return [
            'configured' => self::isConfigured(),
            'host' => Settings::get('smtp_host', ''),
            'port' => Settings::get('smtp_port', '465'),
            'username' => Settings::get('smtp_username', ''),
            'from_email' => Settings::get('smtp_from_email', ''),
            'from_name' => Settings::get('smtp_from_name', 'Ainstein'),
        ];
    }

    /**
     * Crea istanza PHPMailer configurata
     */
    private static function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        // Server SMTP
        $mail->isSMTP();
        $mail->Host = Settings::get('smtp_host', '');
        $mail->Port = (int) Settings::get('smtp_port', 465);
        $mail->SMTPAuth = true;
        $mail->Username = Settings::get('smtp_username', '');
        $mail->Password = Settings::get('smtp_password', '');

        // SSL implicito per porta 465
        $port = (int) Settings::get('smtp_port', 465);
        if ($port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($port === 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // Timeout
        $mail->Timeout = 15;
        $mail->SMTPKeepAlive = false;

        // Mittente
        $fromEmail = Settings::get('smtp_from_email', Settings::get('smtp_username', ''));
        $fromName = Settings::get('smtp_from_name', 'Ainstein');
        $mail->setFrom($fromEmail, $fromName);

        // Charset
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        return $mail;
    }

    /**
     * Invia email semplice
     *
     * @param string $to Indirizzo destinatario
     * @param string $subject Oggetto
     * @param string $htmlBody Corpo HTML
     * @param string|null $textBody Corpo testo (fallback)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function send(string $to, string $subject, string $htmlBody, ?string $textBody = null): array
    {
        if (!self::isConfigured()) {
            return ['success' => false, 'message' => 'SMTP non configurato'];
        }

        try {
            $mail = self::createMailer();
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            if ($textBody) {
                $mail->AltBody = $textBody;
            } else {
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            }

            $mail->send();

            Logger::channel('email')->info('Email inviata', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return ['success' => true, 'message' => 'Email inviata con successo'];

        } catch (Exception $e) {
            $errorMsg = $e->getMessage();

            Logger::channel('email')->error('Errore invio email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $errorMsg,
            ]);

            return ['success' => false, 'message' => $errorMsg];
        }
    }

    /**
     * Invia email usando un template (DB-first con fallback a file PHP)
     *
     * @param string $to Indirizzo destinatario
     * @param string $subject Oggetto (fallback; se template DB ha subject, quello ha priorita)
     * @param string $template Nome/slug template
     * @param array $data Variabili da passare al template
     * @param int|null $userId ID utente per unsubscribe link (opzionale)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendTemplate(string $to, string $subject, string $template, array $data = [], ?int $userId = null): array
    {
        // Variabili globali sempre disponibili
        $data['app_name'] = $data['app_name'] ?? Settings::get('site_name', 'Ainstein');
        $data['app_url'] = $data['app_url'] ?? rtrim(env('APP_URL', 'https://ainstein.it'), '/');
        $data['year'] = date('Y');

        // Unsubscribe URL
        if ($userId) {
            $data['unsubscribe_url'] = self::getUnsubscribeUrl($userId);
        } else {
            $data['unsubscribe_url'] = $data['app_url'] . '/profile';
        }

        // Legacy camelCase per compatibilita con template PHP file
        $data['appName'] = $data['app_name'];
        $data['appUrl'] = $data['app_url'];

        // 1. Cerca template in DB
        $dbTemplate = \Core\Database::fetch(
            "SELECT * FROM email_templates WHERE slug = ? AND is_active = 1",
            [$template]
        );

        if ($dbTemplate) {
            return self::sendFromDb($to, $subject, $dbTemplate, $data);
        }

        // 2. Fallback: template file PHP (comportamento originale)
        $templatePath = ROOT_PATH . '/shared/views/emails/' . $template . '.php';

        if (!file_exists($templatePath)) {
            return ['success' => false, 'message' => "Template email '{$template}' non trovato"];
        }

        ob_start();
        extract($data);
        include $templatePath;
        $htmlBody = ob_get_clean();

        return self::send($to, $subject, $htmlBody);
    }

    /**
     * Invia email usando template da DB
     */
    private static function sendFromDb(string $to, string $fallbackSubject, array $dbTemplate, array $data): array
    {
        // Render body con {{placeholder}} replacement
        $emailContent = self::renderPlaceholders($dbTemplate['body_html'], $data);

        // Subject da DB con placeholder replacement
        $subject = self::renderPlaceholders($dbTemplate['subject'], $data);
        if (empty(trim($subject))) {
            $subject = $fallbackSubject;
        }

        // Footer personalizzato
        $footerText = Settings::get('email_footer_text', '');
        if (!empty($footerText)) {
            $emailContent .= '<hr class="divider"><p style="font-size: 12px; color: #94a3b8;">'
                . htmlspecialchars($footerText) . '</p>';
        }

        // Link gestione preferenze
        $emailContent .= '<p style="font-size: 11px; color: #94a3b8; margin-top: 12px;">'
            . '<a href="' . htmlspecialchars($data['unsubscribe_url']) . '" style="color: #64748b;">Gestisci preferenze email</a></p>';

        // Render layout wrapper base.php
        $data['emailContent'] = $emailContent;
        $data['preheader'] = mb_substr(strip_tags($emailContent), 0, 150);

        $layoutPath = ROOT_PATH . '/shared/views/emails/base.php';
        ob_start();
        extract($data);
        include $layoutPath;
        $htmlBody = ob_get_clean();

        return self::send($to, $subject, $htmlBody);
    }

    /**
     * Sostituisce {{placeholder}} con valori
     */
    private static function renderPlaceholders(string $html, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $html = str_replace('{{' . $key . '}}', (string) $value, $html);
            }
        }
        // Rimuovi placeholder non sostituiti
        $html = preg_replace('/\{\{[a-z_]+\}\}/', '', $html);
        return $html;
    }

    /**
     * Genera o recupera URL unsubscribe per utente
     */
    public static function getUnsubscribeUrl(int $userId): string
    {
        $appUrl = rtrim(env('APP_URL', 'https://ainstein.it'), '/');

        $existing = \Core\Database::fetch(
            "SELECT token FROM email_unsubscribe_tokens WHERE user_id = ?",
            [$userId]
        );

        if ($existing) {
            return $appUrl . '/email/preferences?token=' . $existing['token'];
        }

        $token = bin2hex(random_bytes(32));
        \Core\Database::insert('email_unsubscribe_tokens', [
            'user_id' => $userId,
            'token' => $token,
        ]);

        return $appUrl . '/email/preferences?token=' . $token;
    }

    /**
     * Renderizza preview di un template con dati di esempio (per admin panel)
     */
    public static function renderPreview(string $slug, ?string $subjectOverride = null, ?string $bodyOverride = null): string
    {
        $dbTemplate = \Core\Database::fetch(
            "SELECT * FROM email_templates WHERE slug = ?",
            [$slug]
        );

        if (!$dbTemplate) {
            return '<p>Template non trovato</p>';
        }

        $sampleData = self::getSampleData($slug);
        $sampleData['app_name'] = Settings::get('site_name', 'Ainstein');
        $sampleData['app_url'] = rtrim(env('APP_URL', 'https://ainstein.it'), '/');
        $sampleData['year'] = date('Y');
        $sampleData['unsubscribe_url'] = '#';

        // Override per preview live durante editing
        if ($subjectOverride !== null) $dbTemplate['subject'] = $subjectOverride;
        if ($bodyOverride !== null) $dbTemplate['body_html'] = $bodyOverride;

        $emailContent = self::renderPlaceholders($dbTemplate['body_html'], $sampleData);

        // Footer
        $footerText = Settings::get('email_footer_text', '');
        if (!empty($footerText)) {
            $emailContent .= '<hr class="divider"><p style="font-size: 12px; color: #94a3b8;">'
                . htmlspecialchars($footerText) . '</p>';
        }
        $emailContent .= '<p style="font-size: 11px; color: #94a3b8; margin-top: 12px;">'
            . '<a href="#" style="color: #64748b;">Gestisci preferenze email</a></p>';

        $data = [
            'appName' => $sampleData['app_name'],
            'appUrl' => $sampleData['app_url'],
            'year' => $sampleData['year'],
            'emailContent' => $emailContent,
            'preheader' => '',
        ];

        $layoutPath = ROOT_PATH . '/shared/views/emails/base.php';
        ob_start();
        extract($data);
        include $layoutPath;
        return ob_get_clean();
    }

    /**
     * Dati di esempio per preview template
     */
    private static function getSampleData(string $slug): array
    {
        $samples = [
            'welcome' => ['user_name' => 'Mario Rossi', 'user_email' => 'mario@example.com', 'free_credits' => 30, 'login_url' => '#'],
            'password-reset' => ['user_email' => 'mario@example.com', 'reset_url' => '#'],
            'password-changed' => ['user_name' => 'Mario Rossi', 'user_email' => 'mario@example.com', 'changed_at' => date('d/m/Y H:i')],
            'email-changed' => ['user_name' => 'Mario Rossi', 'user_email' => 'vecchia@example.com', 'new_email' => 'nuova@example.com'],
            'project-invite' => ['project_name' => 'SEO Blog Aziendale', 'inviter_name' => 'Luca Bianchi', 'role' => 'Editor', 'accept_url' => '#'],
            'notification' => ['title' => 'Analisi SEO completata', 'body' => 'Il crawl del sito example.com e terminato con successo.', 'action_url' => '#', 'action_label' => 'Vai ai risultati'],
            'seo-alert' => ['user_name' => 'Mario Rossi', 'project_name' => 'SEO Blog', 'alert_count' => 3, 'period' => 'Ultima settimana', 'alerts_html' => '<table style="width:100%;border-collapse:collapse;margin:16px 0;"><tr><td style="padding:8px;border-bottom:1px solid #e2e8f0;">📉 <span style="color:#ef4444;font-weight:600;">[critical]</span> Keyword "seo tool" scesa da #5 a #12</td></tr><tr><td style="padding:8px;border-bottom:1px solid #e2e8f0;">📈 <span style="color:#3b82f6;font-weight:600;">[info]</span> Keyword "analytics" salita da #15 a #8</td></tr></table>', 'dashboard_url' => '#'],
            'admin-report' => ['period' => 'Settimana 8/2026', 'new_users' => 12, 'total_users' => 350, 'active_users' => 89, 'credits_consumed' => 1250, 'top_modules_html' => '<ol style="margin:8px 0;padding-left:20px;"><li>AI Content (450 cr)</li><li>SEO Audit (320 cr)</li><li>Keyword Research (280 cr)</li></ol>', 'api_errors' => 5, 'failed_jobs' => 2],
        ];

        return $samples[$slug] ?? [];
    }

    /**
     * Accessor pubblico per dati di esempio (usato da admin)
     */
    public static function getSampleDataPublic(string $slug): array
    {
        return self::getSampleData($slug);
    }

    /**
     * Template default hardcoded per ripristino
     */
    public static function getDefaultTemplates(): array
    {
        $path = ROOT_PATH . '/config/email-defaults.php';
        if (file_exists($path)) {
            return require $path;
        }
        return [];
    }

    /**
     * Testa connessione SMTP (senza inviare email)
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public static function testConnection(): array
    {
        if (!self::isConfigured()) {
            return ['success' => false, 'message' => 'SMTP non configurato. Inserisci host e username.'];
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = Settings::get('smtp_host', '');
            $mail->Port = (int) Settings::get('smtp_port', 465);
            $mail->SMTPAuth = true;
            $mail->Username = Settings::get('smtp_username', '');
            $mail->Password = Settings::get('smtp_password', '');
            $mail->Timeout = 10;

            $port = (int) Settings::get('smtp_port', 465);
            if ($port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($port === 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            // Test connessione senza inviare
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $smtp = $mail->getSMTPInstance();
            $smtp->setTimeout(10);

            $connected = $smtp->connect(
                ($port === 465 ? 'ssl://' : '') . Settings::get('smtp_host', ''),
                $port
            );

            if (!$connected) {
                return ['success' => false, 'message' => 'Impossibile connettersi al server SMTP'];
            }

            $hello = $smtp->hello(gethostname() ?: 'localhost');
            if (!$hello) {
                $smtp->close();
                return ['success' => false, 'message' => 'EHLO fallito'];
            }

            // STARTTLS se porta 587
            if ($port === 587) {
                $smtp->startTLS();
                $smtp->hello(gethostname() ?: 'localhost');
            }

            $auth = $smtp->authenticate(
                Settings::get('smtp_username', ''),
                Settings::get('smtp_password', '')
            );

            $smtp->close();

            if (!$auth) {
                return ['success' => false, 'message' => 'Autenticazione SMTP fallita. Verifica username e password.'];
            }

            return ['success' => true, 'message' => 'Connessione SMTP riuscita! Server raggiungibile e credenziali valide.'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Errore SMTP: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Errore: ' . $e->getMessage()];
        }
    }

    /**
     * Invia email di test all'indirizzo specificato
     *
     * @param string $to Email destinatario
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendTestEmail(string $to): array
    {
        $appName = Settings::get('site_name', 'Ainstein');

        return self::sendTemplate($to, "Email di test - {$appName}", 'test', [
            'recipientEmail' => $to,
        ]);
    }

    /**
     * Invia email di benvenuto
     */
    public static function sendWelcome(string $to, string $name, int $freeCredits = 30, ?int $userId = null): array
    {
        $appName = Settings::get('site_name', 'Ainstein');
        $appUrl = rtrim(env('APP_URL', 'https://ainstein.it'), '/');

        return self::sendTemplate($to, "Benvenuto su {$appName}!", 'welcome', [
            'userName' => $name,
            'userEmail' => $to,
            'freeCredits' => $freeCredits,
            'user_name' => $name,
            'user_email' => $to,
            'free_credits' => $freeCredits,
            'login_url' => $appUrl . '/login',
        ], $userId);
    }

    /**
     * Invia email di reset password
     */
    public static function sendPasswordReset(string $to, string $token, ?int $userId = null): array
    {
        $appName = Settings::get('site_name', 'Ainstein');
        $appUrl = rtrim(env('APP_URL', 'https://ainstein.it'), '/');
        $resetUrl = $appUrl . '/reset-password?token=' . urlencode($token);

        return self::sendTemplate($to, "Reimposta la tua password - {$appName}", 'password-reset', [
            'resetUrl' => $resetUrl,
            'userEmail' => $to,
            'reset_url' => $resetUrl,
            'user_email' => $to,
        ], $userId);
    }
}
