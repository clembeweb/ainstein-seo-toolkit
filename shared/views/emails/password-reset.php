<?php
/**
 * Template email reset password
 *
 * Variabili:
 * - $resetUrl (string) - URL completo con token
 * - $userEmail (string)
 * - $appName, $appUrl, $year (da EmailService)
 */
$preheader = "Hai richiesto di reimpostare la password del tuo account {$appName}.";

ob_start();
?>

<h1>Reimposta la tua password</h1>

<p>Abbiamo ricevuto una richiesta per reimpostare la password associata all'account <strong><?= htmlspecialchars($userEmail) ?></strong>.</p>

<p>Clicca il pulsante qui sotto per scegliere una nuova password:</p>

<div style="text-align: center; margin: 28px 0;">
    <a href="<?= htmlspecialchars($resetUrl) ?>" class="btn">Reimposta Password</a>
</div>

<div class="info-box">
    <p>Questo link e valido per <strong>1 ora</strong>. Se scade, puoi richiedere un nuovo link dalla pagina di login.</p>
</div>

<p style="font-size: 13px; color: #64748b;">Se il pulsante non funziona, copia e incolla questo link nel tuo browser:</p>
<p style="font-size: 12px; color: #94a3b8; word-break: break-all;"><?= htmlspecialchars($resetUrl) ?></p>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    Se non hai richiesto il reset della password, ignora questa email. Il tuo account e al sicuro.
</p>

<?php
$emailContent = ob_get_clean();
include __DIR__ . '/base.php';
