<?php
/**
 * Template email di test
 *
 * Variabili:
 * - $recipientEmail (string)
 * - $appName, $appUrl, $year (da EmailService)
 */
$preheader = "Email di test da {$appName} - La configurazione SMTP funziona correttamente!";

ob_start();
?>

<h1>Email di test</h1>

<p>Se stai leggendo questa email, la configurazione SMTP di <strong><?= htmlspecialchars($appName) ?></strong> funziona correttamente!</p>

<div class="info-box">
    <p><strong>Dettagli configurazione:</strong></p>
    <p style="margin-top: 8px;">
        Server: <?= htmlspecialchars(\Core\Settings::get('smtp_host', '-')) ?><br>
        Porta: <?= htmlspecialchars(\Core\Settings::get('smtp_port', '-')) ?><br>
        Mittente: <?= htmlspecialchars(\Core\Settings::get('smtp_from_email', \Core\Settings::get('smtp_username', '-'))) ?><br>
        Destinatario: <?= htmlspecialchars($recipientEmail) ?><br>
        Data: <?= date('d/m/Y H:i:s') ?>
    </p>
</div>

<p>Puoi chiudere questa email. Il sistema di notifiche e operativo.</p>

<?php
$emailContent = ob_get_clean();
include __DIR__ . '/base.php';
