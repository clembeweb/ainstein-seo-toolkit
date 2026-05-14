<?php

namespace Ainstein\Editorial\Admin\Pages;

use Ainstein\Editorial\Includes\LicenseManager;

/**
 * License page — UI per attivazione/disattivazione della license.
 *
 * Stati:
 *  - NON attivato: form license key + email
 *  - Attivato:     card riepilogo + form disattivazione
 *
 * Pattern: render() emette HTML escapato. Tutti i submit vanno via admin-post.php
 * (handler in AdminPages), così abbiamo redirect post-action e niente PRG issues.
 */
class License
{
    private LicenseManager $license;

    public function __construct(LicenseManager $license)
    {
        $this->license = $license;
    }

    public function render(): void
    {
        $status = $this->license->status();
        $notice = isset($_GET['notice']) ? sanitize_key((string) $_GET['notice']) : '';
        $msg = isset($_GET['msg']) ? wp_unslash((string) $_GET['msg']) : '';

        echo '<div class="wrap aied-wrap">';
        echo '<h1>' . esc_html__('Ainstein Editorial — License', 'ainstein-editorial') . '</h1>';

        $this->renderNotice($notice, $msg);

        if ($status['active']) {
            $this->renderActivatedState($status);
        } else {
            $this->renderActivationForm();
        }

        $this->renderFooter();
        echo '</div>';
    }

    private function renderNotice(string $notice, string $msg): void
    {
        if ($notice === '') return;

        $map = [
            'activated' => ['type' => 'success', 'default' => __('License attivata.', 'ainstein-editorial')],
            'activation_failed' => ['type' => 'error', 'default' => __('Attivazione fallita.', 'ainstein-editorial')],
            'deactivated' => ['type' => 'success', 'default' => __('License disattivata.', 'ainstein-editorial')],
            'deactivation_partial' => ['type' => 'warning', 'default' => __('Disattivazione parziale.', 'ainstein-editorial')],
        ];
        if (!isset($map[$notice])) return;

        $type = $map[$notice]['type'];
        $text = $msg !== '' ? $msg : $map[$notice]['default'];

        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr($type),
            esc_html($text)
        );
    }

    private function renderActivationForm(): void
    {
        $adminEmailDefault = function_exists('get_option') ? (string) get_option('admin_email', '') : '';
        $savedLicenseKey = (string) get_option('aied_license_key', '');
        $actionUrl = admin_url('admin-post.php');

        ?>
        <div class="aied-card">
            <h2><?php esc_html_e('Attiva la tua licenza', 'ainstein-editorial'); ?></h2>
            <p class="aied-muted">
                <?php
                printf(
                    /* translators: %s = dominio del sito (es. example.com) */
                    esc_html__('Inserisci la license key ricevuta via email dopo l’acquisto del plugin. Verrà attivata su questo sito (%s).', 'ainstein-editorial'),
                    '<code>' . esc_html(parse_url(home_url('/'), PHP_URL_HOST) ?: '') . '</code>'
                );
                ?>
            </p>

            <form method="post" action="<?php echo esc_url($actionUrl); ?>" class="aied-form">
                <?php wp_nonce_field('aied_activate_license'); ?>
                <input type="hidden" name="action" value="aied_activate_license">

                <p>
                    <label for="aied_license_key">
                        <strong><?php esc_html_e('License Key', 'ainstein-editorial'); ?></strong>
                    </label>
                    <input
                        type="text"
                        id="aied_license_key"
                        name="license_key"
                        class="regular-text code"
                        placeholder="XXXX-XXXX-XXXX-XXXX-XXXX"
                        value="<?php echo esc_attr($savedLicenseKey); ?>"
                        required
                        autocomplete="off"
                        spellcheck="false"
                    >
                </p>

                <p>
                    <label for="aied_admin_email">
                        <strong><?php esc_html_e('Email amministratore', 'ainstein-editorial'); ?></strong>
                    </label>
                    <input
                        type="email"
                        id="aied_admin_email"
                        name="admin_email"
                        class="regular-text"
                        value="<?php echo esc_attr($adminEmailDefault); ?>"
                        required
                    >
                    <span class="description">
                        <?php esc_html_e('Email associata all’account Ainstein Editorial (riceverai notifiche e report qui).', 'ainstein-editorial'); ?>
                    </span>
                </p>

                <p>
                    <button type="submit" class="button button-primary button-large">
                        <?php esc_html_e('Attiva licenza', 'ainstein-editorial'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * @param array{
     *   active:bool, tier:string, email:string, sites_used:int, sites_limit:int,
     *   activated_at:int, expires_at:int
     * } $status
     */
    private function renderActivatedState(array $status): void
    {
        $actionUrl = admin_url('admin-post.php');
        $tierLabel = ucfirst($status['tier'] ?: 'starter');
        $activatedDate = $status['activated_at'] > 0
            ? wp_date(get_option('date_format'), $status['activated_at'])
            : '';

        ?>
        <div class="aied-card aied-card--success">
            <h2>
                <span class="aied-badge aied-badge--success"><?php esc_html_e('Attivato', 'ainstein-editorial'); ?></span>
                <?php esc_html_e('La tua licenza è attiva su questo sito', 'ainstein-editorial'); ?>
            </h2>

            <table class="aied-status-table">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Piano', 'ainstein-editorial'); ?></th>
                        <td><strong><?php echo esc_html($tierLabel); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Email', 'ainstein-editorial'); ?></th>
                        <td><?php echo esc_html($status['email']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Siti utilizzati', 'ainstein-editorial'); ?></th>
                        <td><?php echo esc_html($status['sites_used'] . ' / ' . $status['sites_limit']); ?></td>
                    </tr>
                    <?php if ($activatedDate !== ''): ?>
                    <tr>
                        <th><?php esc_html_e('Attivato il', 'ainstein-editorial'); ?></th>
                        <td><?php echo esc_html($activatedDate); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <hr>

            <form method="post" action="<?php echo esc_url($actionUrl); ?>" class="aied-form" onsubmit="return confirm('<?php echo esc_js(__('Vuoi davvero disattivare la licenza su questo sito? Libererà uno slot, e potrai riattivarla in seguito.', 'ainstein-editorial')); ?>');">
                <?php wp_nonce_field('aied_deactivate_license'); ?>
                <input type="hidden" name="action" value="aied_deactivate_license">
                <button type="submit" class="button button-secondary">
                    <?php esc_html_e('Disattiva su questo sito', 'ainstein-editorial'); ?>
                </button>
            </form>
        </div>
        <?php
    }

    private function renderFooter(): void
    {
        ?>
        <p class="aied-footer">
            <small>
                <?php
                printf(
                    /* translators: %s = plugin version */
                    esc_html__('Ainstein Editorial v%s — Un prodotto Ainstein.', 'ainstein-editorial'),
                    esc_html(AIED_VERSION)
                );
                ?>
            </small>
        </p>
        <?php
    }
}
