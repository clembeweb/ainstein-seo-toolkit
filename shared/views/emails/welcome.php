<?php
/**
 * Template email di benvenuto
 *
 * Variabili:
 * - $userName (string)
 * - $userEmail (string)
 * - $freeCredits (int)
 * - $appName, $appUrl, $year (da EmailService)
 */
$preheader = "Benvenuto su {$appName}! Hai ricevuto {$freeCredits} crediti gratuiti.";

ob_start();
?>

<h1>Benvenuto su <?= htmlspecialchars($appName) ?>!</h1>

<p>Ciao <strong><?= htmlspecialchars($userName) ?></strong>,</p>

<p>Grazie per esserti registrato. Il tuo account e attivo e pronto all'uso.</p>

<div class="info-box">
    <p><strong><?= (int)$freeCredits ?> crediti gratuiti</strong> sono stati aggiunti al tuo account per iniziare subito a utilizzare tutti gli strumenti della piattaforma.</p>
</div>

<p>Con <?= htmlspecialchars($appName) ?> puoi:</p>

<table cellpadding="0" cellspacing="0" border="0" style="margin: 16px 0;">
    <tr>
        <td style="padding: 6px 12px 6px 0; vertical-align: top; color: #006e96; font-size: 18px;">&#10003;</td>
        <td style="padding: 6px 0; font-size: 14px; color: #475569;">Generare contenuti SEO con l'intelligenza artificiale</td>
    </tr>
    <tr>
        <td style="padding: 6px 12px 6px 0; vertical-align: top; color: #006e96; font-size: 18px;">&#10003;</td>
        <td style="padding: 6px 0; font-size: 14px; color: #475569;">Monitorare il posizionamento delle tue keyword</td>
    </tr>
    <tr>
        <td style="padding: 6px 12px 6px 0; vertical-align: top; color: #006e96; font-size: 18px;">&#10003;</td>
        <td style="padding: 6px 0; font-size: 14px; color: #475569;">Effettuare audit SEO completi del tuo sito</td>
    </tr>
    <tr>
        <td style="padding: 6px 12px 6px 0; vertical-align: top; color: #006e96; font-size: 18px;">&#10003;</td>
        <td style="padding: 6px 0; font-size: 14px; color: #475569;">Ricercare keyword e pianificare contenuti editoriali</td>
    </tr>
</table>

<div style="text-align: center; margin: 28px 0 12px;">
    <a href="<?= htmlspecialchars($appUrl) ?>/dashboard" class="btn">Vai alla Dashboard</a>
</div>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    Hai ricevuto questa email perche ti sei registrato su <?= htmlspecialchars($appName) ?> con l'indirizzo <?= htmlspecialchars($userEmail) ?>.
</p>

<?php
$emailContent = ob_get_clean();
include __DIR__ . '/base.php';
