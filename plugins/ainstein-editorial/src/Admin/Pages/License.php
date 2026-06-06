<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Admin\Pages;

use Ainstein\Editorial\Includes\LicenseManager;

/**
 * Pagina admin "License": attivazione / disattivazione del plugin su questo sito.
 */
class License
{
    private const NONCE_ACTION = 'aied_license_action';
    private const NONCE_FIELD  = 'aied_license_nonce';

    /**
     * Processa il submit del form (chiamato su admin_init).
     */
    public function handle(string $action): void
    {
        check_admin_referer(self::NONCE_ACTION, self::NONCE_FIELD);

        $manager = new LicenseManager();

        if ($action === 'activate') {
            $key    = isset($_POST['aied_license_key']) ? sanitize_text_field(wp_unslash((string) $_POST['aied_license_key'])) : '';
            $result = $manager->activate($key);
            if (is_wp_error($result)) {
                $this->setNotice('error', $result->get_error_message());
            } else {
                $this->setNotice('success', 'License attivata correttamente. Sei pronto per iniziare.');
            }
        } elseif ($action === 'deactivate') {
            $result = $manager->deactivate();
            if (is_wp_error($result)) {
                $this->setNotice('warning', 'Disattivazione completata localmente, ma il backend ha segnalato: ' . $result->get_error_message());
            } else {
                $this->setNotice('success', 'Plugin disattivato su questo sito. La license è di nuovo disponibile.');
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=ainstein-editorial'));
        exit;
    }

    /**
     * Render della pagina.
     */
    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html('Permessi insufficienti.'));
        }

        $manager = new LicenseManager();
        $notice  = $this->takeNotice();
        ?>
        <div class="wrap aied-wrap">
            <h1 class="aied-title">Ainstein Editorial</h1>
            <p class="aied-subtitle">Il tuo SEO Pro autonomo per WordPress.</p>

            <?php if ($notice !== null) : ?>
                <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                    <p><?php echo esc_html($notice['message']); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($manager->isActive()) : ?>
                <div class="aied-card aied-card--active">
                    <div class="aied-badge aied-badge--ok">✓ Attivato</div>
                    <h2>Tutto pronto</h2>
                    <p>Piano: <strong><?php echo esc_html(ucfirst($manager->getTier())); ?></strong></p>
                    <p>Account: <strong><?php echo esc_html($manager->getEmail()); ?></strong></p>
                    <form method="post" action="">
                        <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
                        <input type="hidden" name="aied_action" value="deactivate" />
                        <button type="submit" class="button button-secondary aied-btn"
                                onclick="return confirm('Disattivare Ainstein Editorial su questo sito? La license tornerà disponibile per un altro sito.');">
                            Disattiva su questo sito
                        </button>
                    </form>
                </div>
            <?php else : ?>
                <div class="aied-card">
                    <h2>Attiva il plugin</h2>
                    <p>Incolla la tua license key per collegare questo sito ad Ainstein Editorial.</p>
                    <form method="post" action="">
                        <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
                        <input type="hidden" name="aied_action" value="activate" />
                        <p>
                            <label for="aied_license_key" class="aied-label">License key</label>
                            <input type="text" id="aied_license_key" name="aied_license_key"
                                   class="regular-text aied-input" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="off" />
                        </p>
                        <button type="submit" class="button button-primary aied-btn aied-btn--primary">Attiva</button>
                    </form>
                </div>
            <?php endif; ?>

            <p class="aied-footer">An Ainstein product · v<?php echo esc_html(AIED_VERSION); ?></p>
        </div>
        <?php
    }

    private function setNotice(string $type, string $message): void
    {
        set_transient('aied_notice', ['type' => $type, 'message' => $message], 60);
    }

    /**
     * @return array{type:string,message:string}|null
     */
    private function takeNotice(): ?array
    {
        $notice = get_transient('aied_notice');
        if (is_array($notice) && isset($notice['type'], $notice['message'])) {
            delete_transient('aied_notice');
            return ['type' => (string) $notice['type'], 'message' => (string) $notice['message']];
        }
        return null;
    }
}
