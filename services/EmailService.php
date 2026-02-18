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
     * Invia email usando un template PHP
     *
     * @param string $to Indirizzo destinatario
     * @param string $subject Oggetto
     * @param string $template Nome template (es. 'welcome', 'password-reset')
     * @param array $data Variabili da passare al template
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendTemplate(string $to, string $subject, string $template, array $data = []): array
    {
        $templatePath = ROOT_PATH . '/shared/views/emails/' . $template . '.php';

        if (!file_exists($templatePath)) {
            return ['success' => false, 'message' => "Template email '{$template}' non trovato"];
        }

        // Render template
        $data['appName'] = Settings::get('site_name', 'Ainstein');
        $data['appUrl'] = rtrim(env('APP_URL', 'https://ainstein.it'), '/');
        $data['year'] = date('Y');

        ob_start();
        extract($data);
        include $templatePath;
        $htmlBody = ob_get_clean();

        return self::send($to, $subject, $htmlBody);
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
    public static function sendWelcome(string $to, string $name, int $freeCredits = 30): array
    {
        $appName = Settings::get('site_name', 'Ainstein');

        return self::sendTemplate($to, "Benvenuto su {$appName}!", 'welcome', [
            'userName' => $name,
            'userEmail' => $to,
            'freeCredits' => $freeCredits,
        ]);
    }

    /**
     * Invia email di reset password
     */
    public static function sendPasswordReset(string $to, string $token): array
    {
        $appName = Settings::get('site_name', 'Ainstein');
        $appUrl = rtrim(env('APP_URL', 'https://ainstein.it'), '/');
        $resetUrl = $appUrl . '/reset-password?token=' . urlencode($token);

        return self::sendTemplate($to, "Reimposta la tua password - {$appName}", 'password-reset', [
            'resetUrl' => $resetUrl,
            'userEmail' => $to,
        ]);
    }
}
