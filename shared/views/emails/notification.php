<?php
/**
 * Template email notifica generica
 *
 * Variabili:
 * - $title (string) - Titolo della notifica
 * - $body (string|null) - Corpo della notifica (opzionale)
 * - $action_url (string|null) - URL del pulsante CTA (opzionale)
 * - $action_label (string) - Etichetta del pulsante (default: "Vai alla pagina")
 * - $appName, $appUrl, $year (da EmailService)
 */
$preheader = $title;
$action_label = $action_label ?? 'Vai alla pagina';

ob_start();
?>

<h1><?= htmlspecialchars($title) ?></h1>

<?php if (!empty($body)): ?>
<p><?= nl2br(htmlspecialchars($body)) ?></p>
<?php endif; ?>

<?php if (!empty($action_url)): ?>
<div style="text-align: center; margin: 28px 0;">
    <a href="<?= htmlspecialchars($action_url) ?>" class="btn"><?= htmlspecialchars($action_label) ?></a>
</div>

<p style="font-size: 13px; color: #64748b;">Se il pulsante non funziona, copia e incolla questo link nel tuo browser:</p>
<p style="font-size: 12px; color: #94a3b8; word-break: break-all;"><?= htmlspecialchars($action_url) ?></p>
<?php endif; ?>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    Puoi modificare le preferenze di notifica email dal tuo profilo su <?= htmlspecialchars($appName) ?>.
</p>

<?php
$emailContent = ob_get_clean();
include __DIR__ . '/base.php';
