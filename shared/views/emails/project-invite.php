<?php
/**
 * Template email invito progetto
 *
 * Variabili:
 * - $project_name (string) - Nome del progetto
 * - $inviter_name (string) - Nome o email di chi ha invitato
 * - $role (string) - "Editor" o "Visualizzatore"
 * - $accept_url (string) - URL completo per accettare l'invito
 * - $appName, $appUrl, $year (da EmailService)
 */
$preheader = "Sei stato invitato a collaborare su {$project_name} come {$role}.";

ob_start();
?>

<h1>Invito a collaborare</h1>

<p>Ciao,</p>

<p><strong><?= htmlspecialchars($inviter_name) ?></strong> ti ha invitato a collaborare sul progetto <strong><?= htmlspecialchars($project_name) ?></strong> su <?= htmlspecialchars($appName) ?>.</p>

<div class="info-box">
    <p>Il tuo ruolo nel progetto sara: <strong><?= htmlspecialchars($role) ?></strong></p>
</div>

<p>Clicca il pulsante qui sotto per accettare l'invito e accedere al progetto:</p>

<div style="text-align: center; margin: 28px 0;">
    <a href="<?= htmlspecialchars($accept_url) ?>" class="btn">Accetta Invito</a>
</div>

<div class="info-box">
    <p>Questo invito e valido per <strong>7 giorni</strong>. Se scade, chiedi a <?= htmlspecialchars($inviter_name) ?> di inviarti un nuovo invito.</p>
</div>

<p style="font-size: 13px; color: #64748b;">Se il pulsante non funziona, copia e incolla questo link nel tuo browser:</p>
<p style="font-size: 12px; color: #94a3b8; word-break: break-all;"><?= htmlspecialchars($accept_url) ?></p>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    Se non conosci la persona che ti ha invitato o ritieni che questo invito sia stato inviato per errore, puoi ignorare questa email.
</p>

<?php
$emailContent = ob_get_clean();
include __DIR__ . '/base.php';
