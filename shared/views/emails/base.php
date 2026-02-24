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

// Fallback: usa il logo orizzontale del brand se email_logo_url non e impostato
if (empty($logoUrl)) {
    $brandLogo = \Core\Settings::get('brand_logo_horizontal', '');
    if (!empty($brandLogo)) {
        // Il path in DB e relativo (assets/images/...), costruisci URL completo
        $logoUrl = rtrim($appUrl ?? '', '/') . '/' . ltrim($brandLogo, '/');
    }
}

// Darken brand color by ~15% for hover state
$brandColorHover = (function($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return '#005577';
    $r = max(0, intval(hexdec(substr($hex, 0, 2)) * 0.85));
    $g = max(0, intval(hexdec(substr($hex, 2, 2)) * 0.85));
    $b = max(0, intval(hexdec(substr($hex, 4, 2)) * 0.85));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
})($brandColor);

// Lighten brand color for accent backgrounds
$brandColorLight = (function($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return '#e6f4f8';
    $r = min(255, intval(hexdec(substr($hex, 0, 2)) * 0.15 + 255 * 0.85));
    $g = min(255, intval(hexdec(substr($hex, 2, 2)) * 0.15 + 255 * 0.85));
    $b = min(255, intval(hexdec(substr($hex, 4, 2)) * 0.15 + 255 * 0.85));
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
    <!--[if mso]>
    <noscript>
    <xml>
    <o:OfficeDocumentSettings>
    <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings>
    </xml>
    </noscript>
    <![endif]-->
    <style>
        /* Reset */
        body, table, td, p, a, li { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100%; height: 100%; }

        /* Base styles */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            line-height: 1.6;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
        }

        /* Header — brand identity bar */
        .email-header {
            background-color: <?= htmlspecialchars($brandColor) ?>;
            text-align: center;
            padding: 20px 32px;
            border-radius: 12px 12px 0 0;
        }

        .email-header a {
            color: #ffffff;
            text-decoration: none;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .email-header img {
            max-height: 36px;
            width: auto;
        }

        /* Accent line under header */
        .email-accent-line {
            height: 3px;
            background: linear-gradient(90deg, <?= htmlspecialchars($brandColor) ?>, <?= htmlspecialchars($brandColorLight) ?>, <?= htmlspecialchars($brandColor) ?>);
        }

        /* Body */
        .email-body {
            background-color: #ffffff;
            padding: 36px 32px;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
        }

        /* Footer */
        .email-footer {
            background-color: #f8fafc;
            border-radius: 0 0 12px 12px;
            border: 1px solid #e2e8f0;
            border-top: none;
            text-align: center;
            padding: 24px 32px;
        }

        .email-footer p {
            margin: 0 0 6px;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .email-footer a {
            color: <?= htmlspecialchars($brandColor) ?>;
            text-decoration: none;
            font-weight: 500;
        }

        .email-footer a:hover {
            text-decoration: underline;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 14px 36px;
            background-color: <?= htmlspecialchars($brandColor) ?>;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            line-height: 1;
            text-align: center;
            transition: background-color 0.2s;
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
            background-color: <?= htmlspecialchars($brandColorLight) ?>;
            border-left: 4px solid <?= htmlspecialchars($brandColor) ?>;
            border-radius: 0 8px 8px 0;
            padding: 16px 20px;
            margin: 20px 0;
        }

        .info-box p {
            color: #1e293b;
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
            .email-wrapper { padding: 0 !important; }
            .email-header { padding: 16px 20px; border-radius: 0; }
            .email-body { padding: 28px 20px; }
            .email-footer { padding: 20px; border-radius: 0; }
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
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f1f5f9;">
            <tr><td style="padding: 32px 16px;">
                <div class="email-container">

                    <!-- Header — brand bar -->
                    <div class="email-header">
                        <?php if (!empty($logoUrl)): ?>
                            <a href="<?= htmlspecialchars($appUrl) ?>">
                                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($appName) ?>" style="max-height:36px;width:auto;filter:brightness(0) invert(1);">
                            </a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($appUrl) ?>"><?= htmlspecialchars($appName) ?></a>
                        <?php endif; ?>
                    </div>

                    <!-- Accent gradient line -->
                    <div class="email-accent-line"></div>

                    <!-- Body -->
                    <div class="email-body">
                        <?= $emailContent ?>
                    </div>

                    <!-- Footer -->
                    <div class="email-footer">
                        <?php if (!empty($customFooterText)): ?>
                            <p style="margin: 0 0 10px; color: #64748b; font-size: 13px;">
                                <?= htmlspecialchars($customFooterText) ?>
                            </p>
                        <?php endif; ?>
                        <p style="margin: 0 0 6px;">
                            &copy; <?= $year ?> <?= htmlspecialchars($appName) ?>. Tutti i diritti riservati.
                        </p>
                        <p style="margin: 0;">
                            <a href="<?= htmlspecialchars($appUrl) ?>"><?= htmlspecialchars(str_replace(['https://', 'http://'], '', $appUrl)) ?></a>
                        </p>
                    </div>

                </div>
            </td></tr>
        </table>
    </div>
</body>
</html>
