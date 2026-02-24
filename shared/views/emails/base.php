<?php
/**
 * Base email template - Layout comune per tutte le email
 *
 * Variabili disponibili:
 * - $appName (string) - Nome applicazione
 * - $appUrl (string) - URL base applicazione
 * - $year (string) - Anno corrente
 * - $emailContent (string) - Contenuto HTML del body
 * - $preheader (string, opzionale) - Testo preview email
 */
$preheader = $preheader ?? '';

// Branding settings
$brandColor = \Core\Settings::get('email_brand_color', '#006e96');
$logoUrl = \Core\Settings::get('email_logo_url', '');
$customFooterText = \Core\Settings::get('email_footer_text', '');

// Darken brand color by ~15% for hover state
$brandColorHover = (function($hex) {
    $hex = ltrim($hex, '#');
    $r = max(0, intval(hexdec(substr($hex, 0, 2)) * 0.85));
    $g = max(0, intval(hexdec(substr($hex, 2, 2)) * 0.85));
    $b = max(0, intval(hexdec(substr($hex, 4, 2)) * 0.85));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
})($brandColor);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= htmlspecialchars($appName ?? 'Ainstein') ?></title>
    <style>
        /* Reset */
        body, table, td, p, a, li { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100%; height: 100%; }

        /* Base styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            line-height: 1.6;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 32px 16px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .email-header {
            text-align: center;
            padding: 24px 0 16px;
        }

        .email-header a {
            color: <?= htmlspecialchars($brandColor) ?>;
            text-decoration: none;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .email-body {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 32px;
            border: 1px solid #e2e8f0;
        }

        .email-footer {
            text-align: center;
            padding: 24px 16px;
            font-size: 12px;
            color: #94a3b8;
        }

        .email-footer a {
            color: #64748b;
            text-decoration: underline;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background-color: <?= htmlspecialchars($brandColor) ?>;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            line-height: 1;
            text-align: center;
        }

        .btn:hover {
            background-color: <?= htmlspecialchars($brandColorHover) ?>;
        }

        /* Typography */
        h1 { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 16px; line-height: 1.3; }
        h2 { font-size: 18px; font-weight: 600; color: #1e293b; margin: 0 0 12px; line-height: 1.3; }
        p { margin: 0 0 16px; font-size: 15px; color: #475569; line-height: 1.6; }
        p:last-child { margin-bottom: 0; }

        /* Info box */
        .info-box {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }

        .info-box p {
            color: #0369a1;
            margin: 0;
            font-size: 14px;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 24px 0;
        }

        /* Responsive */
        @media only screen and (max-width: 620px) {
            .email-body { padding: 24px 20px; }
            .btn { padding: 12px 24px; font-size: 14px; }
            h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
    <?php if ($preheader): ?>
    <div style="display:none;font-size:1px;color:#f1f5f9;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        <?= htmlspecialchars($preheader) ?>
    </div>
    <?php endif; ?>

    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <?php if (!empty($logoUrl)): ?>
                    <a href="<?= htmlspecialchars($appUrl) ?>"><img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($appName) ?>" style="max-height:40px;"></a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($appUrl) ?>"><?= htmlspecialchars($appName) ?></a>
                <?php endif; ?>
            </div>

            <!-- Body -->
            <div class="email-body">
                <?= $emailContent ?>
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <?php if (!empty($customFooterText)): ?>
                    <p style="margin: 0 0 12px; color: #64748b; font-size: 13px;">
                        <?= htmlspecialchars($customFooterText) ?>
                    </p>
                <?php endif; ?>
                <p style="margin: 0 0 8px;">
                    &copy; <?= $year ?> <?= htmlspecialchars($appName) ?>. Tutti i diritti riservati.
                </p>
                <p style="margin: 0;">
                    <a href="<?= htmlspecialchars($appUrl) ?>"><?= htmlspecialchars(str_replace(['https://', 'http://'], '', $appUrl)) ?></a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
