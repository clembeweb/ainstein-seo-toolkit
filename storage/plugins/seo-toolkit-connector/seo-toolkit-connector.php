<?php
/**
 * Plugin Name: SEO Toolkit Connector
 * Plugin URI: https://seo-toolkit.example.com
 * Description: Connettore per piattaforma SEO Toolkit SaaS - Pubblica articoli generati con AI direttamente su WordPress
 * Version: 1.0.0
 * Author: SEO Toolkit
 * Author URI: https://seo-toolkit.example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: seo-toolkit-connector
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

class SEOToolkitConnector {

    private $option_name = 'seo_toolkit_api_key';
    private $version = '1.1.0';

    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('rest_api_init', [$this, 'registerEndpoints']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_notices', [$this, 'showRegeneratedNotice']);

        // Rendering diretto meta tags quando nessun plugin SEO e' installato
        if (!is_admin()) {
            add_action('wp_head', [$this, 'renderMetaTags'], 1);
            add_filter('pre_get_document_title', [$this, 'overrideTitle'], 999);
        }
    }

    /**
     * Rileva quale plugin SEO e' attivo su WordPress
     * @return string 'yoast'|'rankmath'|'aioseo'|'none'
     */
    private function detectSeoPlugin(): string {
        if (defined('WPSEO_VERSION') || class_exists('WPSEO_Options')) {
            return 'yoast';
        }
        if (class_exists('RankMath') || defined('RANK_MATH_VERSION')) {
            return 'rankmath';
        }
        if (function_exists('aioseo') || defined('AIOSEO_VERSION')) {
            return 'aioseo';
        }
        return 'none';
    }

    /**
     * Renderizza meta tags nell'<head> quando nessun plugin SEO e' installato
     * Legge dai campi custom _seo_toolkit_title e _seo_toolkit_description
     */
    public function renderMetaTags(): void {
        if ($this->detectSeoPlugin() !== 'none') {
            return;
        }

        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }

        $seo_desc = get_post_meta($post_id, '_seo_toolkit_description', true);
        if (!empty($seo_desc)) {
            echo '<meta name="description" content="' . esc_attr($seo_desc) . '" />' . "\n";
        }
    }

    /**
     * Override del titolo pagina quando nessun plugin SEO e' installato
     * Usa il filtro pre_get_document_title (WP 4.1+)
     */
    public function overrideTitle($title): string {
        if ($this->detectSeoPlugin() !== 'none') {
            return $title;
        }

        if (!is_singular()) {
            return $title;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return $title;
        }

        $seo_title = get_post_meta($post_id, '_seo_toolkit_title', true);
        if (!empty($seo_title)) {
            return $seo_title;
        }

        return $title;
    }

    public function addAdminMenu() {
        add_options_page(
            'SEO Toolkit Connector',
            'SEO Toolkit',
            'manage_options',
            'seo-toolkit-connector',
            [$this, 'renderSettingsPage']
        );
    }

    public function registerSettings() {
        register_setting('seo_toolkit_settings', $this->option_name);
    }

    public function showRegeneratedNotice() {
        if (isset($_GET['page']) && $_GET['page'] === 'seo-toolkit-connector' && isset($_GET['regenerated'])) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>API Key rigenerata con successo!</strong> Ricordati di aggiornare la chiave nella piattaforma SEO Toolkit.</p></div>';
        }
    }

    public function renderSettingsPage() {
        // Handle regenerate request
        if (isset($_POST['regenerate_key']) && check_admin_referer('regenerate_api_key')) {
            $new_key = $this->generateApiKey();
            update_option($this->option_name, $new_key);
            wp_redirect(admin_url('options-general.php?page=seo-toolkit-connector&regenerated=1'));
            exit;
        }

        $api_key = get_option($this->option_name);
        if (empty($api_key)) {
            $api_key = $this->generateApiKey();
            update_option($this->option_name, $api_key);
        }
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-admin-links" style="font-size: 30px; margin-right: 10px;"></span>
                SEO Toolkit Connector
            </h1>

            <div class="card" style="max-width: 700px; margin-top: 20px;">
                <h2 style="margin-top: 0;">Collegamento alla Piattaforma</h2>
                <p>Utilizza i dati seguenti per collegare questo sito WordPress alla piattaforma SEO Toolkit:</p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">URL Sito</th>
                        <td>
                            <code style="padding: 8px 12px; background: #f0f0f0; font-size: 13px; display: inline-block;">
                                <?php echo esc_url(home_url()); ?>
                            </code>
                            <button type="button" class="button button-small" onclick="copyToClipboard('<?php echo esc_url(home_url()); ?>')" style="margin-left: 10px;">
                                Copia
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <code id="api-key-display" style="padding: 8px 12px; background: #f0f0f0; font-size: 13px; display: inline-block; word-break: break-all; max-width: 350px;">
                                <?php echo esc_html($api_key); ?>
                            </code>
                            <button type="button" class="button button-small" onclick="copyToClipboard('<?php echo esc_html($api_key); ?>')" style="margin-left: 10px;">
                                Copia
                            </button>
                        </td>
                    </tr>
                </table>

                <hr style="margin: 20px 0;">

                <form method="post" action="">
                    <?php wp_nonce_field('regenerate_api_key'); ?>
                    <p>
                        <input type="submit" name="regenerate_key" class="button button-secondary" value="Rigenera API Key" onclick="return confirm('Sei sicuro? Dovrai aggiornare la chiave nella piattaforma SEO Toolkit.');">
                    </p>
                    <p class="description">
                        Rigenera la chiave API se pensi che sia stata compromessa. Dovrai poi aggiornare la chiave nella piattaforma SEO Toolkit.
                    </p>
                </form>
            </div>

            <div class="card" style="max-width: 700px; margin-top: 20px;">
                <h2 style="margin-top: 0;">Come collegare il sito</h2>
                <ol>
                    <li>Accedi alla piattaforma SEO Toolkit</li>
                    <li>Vai sulla <strong>Dashboard del tuo Progetto</strong></li>
                    <li>Nella sezione <strong>Siti WordPress</strong>, clicca <strong>Aggiungi</strong></li>
                    <li>Inserisci un nome identificativo (es. "Il Mio Blog")</li>
                    <li>Incolla l'<strong>URL Sito</strong> e l'<strong>API Key</strong> mostrati sopra</li>
                    <li>Clicca su <strong>Aggiungi e collega</strong></li>
                </ol>
            </div>

            <div class="card" style="max-width: 700px; margin-top: 20px; background: #f7f7f7;">
                <h3 style="margin-top: 0;">Stato Plugin</h3>
                <table class="widefat" style="background: transparent; border: 0;">
                    <tr>
                        <td><strong>Versione Plugin:</strong></td>
                        <td><?php echo esc_html($this->version); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Versione WordPress:</strong></td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Versione PHP:</strong></td>
                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong>REST API:</strong></td>
                        <td>
                            <?php if (rest_url()): ?>
                                <span style="color: green;">Attiva</span>
                            <?php else: ?>
                                <span style="color: red;">Non disponibile</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Endpoint Base:</strong></td>
                        <td><code><?php echo esc_url(rest_url('seo-toolkit/v1/')); ?></code></td>
                    </tr>
                </table>
            </div>
        </div>

        <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Copiato negli appunti!');
            }).catch(function(err) {
                prompt('Copia manualmente:', text);
            });
        }
        </script>
        <?php
    }

    private function generateApiKey(): string {
        return 'stk_' . bin2hex(random_bytes(24));
    }

    public function registerEndpoints() {
        // Verifica connessione (ping)
        register_rest_route('seo-toolkit/v1', '/ping', [
            'methods' => 'GET',
            'callback' => [$this, 'ping'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);

        // Lista categorie
        register_rest_route('seo-toolkit/v1', '/categories', [
            'methods' => 'GET',
            'callback' => [$this, 'getCategories'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);

        // Lista tag
        register_rest_route('seo-toolkit/v1', '/tags', [
            'methods' => 'GET',
            'callback' => [$this, 'getTags'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);

        // Crea post
        register_rest_route('seo-toolkit/v1', '/posts', [
            'methods' => 'POST',
            'callback' => [$this, 'createPost'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);

        // Aggiorna post esistente
        register_rest_route('seo-toolkit/v1', '/posts/(?P<id>\d+)', [
            'methods' => ['PUT', 'PATCH'],
            'callback' => [$this, 'updatePost'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);

        // Lista post
        register_rest_route('seo-toolkit/v1', '/posts', [
            'methods' => 'GET',
            'callback' => [$this, 'getPosts'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);

        // Upload media
        register_rest_route('seo-toolkit/v1', '/media', [
            'methods' => 'POST',
            'callback' => [$this, 'uploadMedia'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);

        // All content with SEO meta (for meta tag generator)
        register_rest_route('seo-toolkit/v1', '/all-content', [
            'methods' => 'GET',
            'callback' => [$this, 'getAllContent'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);

        // Update SEO meta only (for meta tag publishing)
        register_rest_route('seo-toolkit/v1', '/posts/(?P<id>\d+)/seo-meta', [
            'methods' => ['PATCH', 'POST'],
            'callback' => [$this, 'updateSeoMeta'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);

        // SEO Audit - Full page data extraction for scraping-free audit
        register_rest_route('seo-toolkit/v1', '/seo-audit', [
            'methods'  => 'GET',
            'callback' => [$this, 'handleSeoAudit'],
            'permission_callback' => [$this, 'verifyApiKey'],
            'args' => [
                'per_page' => ['default' => 50, 'sanitize_callback' => 'absint'],
                'page'     => ['default' => 1, 'sanitize_callback' => 'absint'],
                'type'     => ['default' => 'post,page', 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);
    }

    public function verifyApiKey($request): bool {
        $provided_key = $request->get_header('X-SEO-Toolkit-Key');
        $stored_key = get_option($this->option_name);

        if (empty($provided_key) || empty($stored_key)) {
            return false;
        }

        return hash_equals($stored_key, $provided_key);
    }

    public function ping(): \WP_REST_Response {
        $seo_plugin = $this->detectSeoPlugin();
        return new \WP_REST_Response([
            'success' => true,
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => $this->version,
            'seo_plugin' => $seo_plugin,
        ]);
    }

    public function getCategories(): \WP_REST_Response {
        $categories = get_categories(['hide_empty' => false]);
        $result = [];

        foreach ($categories as $cat) {
            $result[] = [
                'id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'parent' => $cat->parent,
                'count' => $cat->count
            ];
        }

        return new \WP_REST_Response([
            'success' => true,
            'categories' => $result
        ]);
    }

    public function getTags(): \WP_REST_Response {
        $tags = get_tags(['hide_empty' => false]);
        $result = [];

        foreach ($tags as $tag) {
            $result[] = [
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug
            ];
        }

        return new \WP_REST_Response([
            'success' => true,
            'tags' => $result
        ]);
    }

    public function createPost($request): \WP_REST_Response {
        $params = $request->get_json_params();

        // Validazione
        if (empty($params['title'])) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Il titolo e obbligatorio'
            ], 400);
        }

        // Prepara dati post
        $post_data = [
            'post_title' => sanitize_text_field($params['title']),
            'post_content' => wp_kses_post($params['content'] ?? ''),
            'post_status' => in_array($params['status'] ?? 'draft', ['draft', 'publish', 'pending', 'private'])
                ? $params['status']
                : 'draft',
            'post_type' => 'post',
            'meta_input' => []
        ];

        // Gestione categorie
        if (!empty($params['category_id'])) {
            $post_data['post_category'] = [(int) $params['category_id']];
        } elseif (!empty($params['category_name'])) {
            // Cerca categoria per nome o creala
            $cat = get_term_by('name', $params['category_name'], 'category');
            if ($cat) {
                $post_data['post_category'] = [$cat->term_id];
            } else {
                $new_cat = wp_insert_term($params['category_name'], 'category');
                if (!is_wp_error($new_cat)) {
                    $post_data['post_category'] = [$new_cat['term_id']];
                }
            }
        } elseif (!empty($params['categories'])) {
            $post_data['post_category'] = array_map('intval', (array) $params['categories']);
        }

        // Tags
        if (!empty($params['tags'])) {
            $post_data['tags_input'] = $params['tags'];
        }

        // Meta description (supporto Yoast SEO e RankMath)
        if (!empty($params['meta_description'])) {
            $meta_desc = sanitize_text_field($params['meta_description']);
            $post_data['meta_input']['_yoast_wpseo_metadesc'] = $meta_desc;
            $post_data['meta_input']['rank_math_description'] = $meta_desc;
            // All In One SEO
            $post_data['meta_input']['_aioseo_description'] = $meta_desc;
        }

        // Crea il post
        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => $post_id->get_error_message()
            ], 400);
        }

        // Featured image se presente
        if (!empty($params['featured_image_id'])) {
            set_post_thumbnail($post_id, (int) $params['featured_image_id']);
        }

        return new \WP_REST_Response([
            'success' => true,
            'post_id' => $post_id,
            'post_url' => get_permalink($post_id),
            'edit_url' => admin_url("post.php?post={$post_id}&action=edit")
        ]);
    }

    public function updatePost($request): \WP_REST_Response {
        $post_id = (int) $request['id'];
        $params = $request->get_json_params();

        // Verifica che il post esista
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Post non trovato'
            ], 404);
        }

        $post_data = ['ID' => $post_id];

        if (isset($params['title'])) {
            $post_data['post_title'] = sanitize_text_field($params['title']);
        }

        if (isset($params['content'])) {
            $post_data['post_content'] = wp_kses_post($params['content']);
        }

        if (isset($params['status'])) {
            $post_data['post_status'] = in_array($params['status'], ['draft', 'publish', 'pending', 'private'])
                ? $params['status']
                : $post->post_status;
        }

        $result = wp_update_post($post_data, true);

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => $result->get_error_message()
            ], 400);
        }

        // Aggiorna meta description
        if (!empty($params['meta_description'])) {
            $meta_desc = sanitize_text_field($params['meta_description']);
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_desc);
            update_post_meta($post_id, 'rank_math_description', $meta_desc);
            update_post_meta($post_id, '_aioseo_description', $meta_desc);
        }

        return new \WP_REST_Response([
            'success' => true,
            'post_id' => $post_id,
            'post_url' => get_permalink($post_id)
        ]);
    }

    public function getPosts($request): \WP_REST_Response {
        $args = [
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => min((int) ($request->get_param('per_page') ?? 20), 100),
            'paged' => max((int) ($request->get_param('page') ?? 1), 1),
        ];

        if ($search = $request->get_param('search')) {
            $args['s'] = sanitize_text_field($search);
        }

        $query = new \WP_Query($args);

        $posts = [];
        foreach ($query->posts as $post) {
            $posts[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'status' => $post->post_status,
                'url' => get_permalink($post->ID),
                'date' => $post->post_date,
                'modified' => $post->post_modified,
                'categories' => wp_get_post_categories($post->ID, ['fields' => 'names'])
            ];
        }

        return new \WP_REST_Response([
            'success' => true,
            'posts' => $posts,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages
        ]);
    }

    public function uploadMedia($request): \WP_REST_Response {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $files = $request->get_file_params();

        if (empty($files['file'])) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Nessun file caricato'
            ], 400);
        }

        // Verifica tipo file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($files['file']['type'], $allowed_types)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Tipo file non supportato. Usa: JPEG, PNG, GIF, WEBP'
            ], 400);
        }

        $attachment_id = media_handle_sideload($files['file'], 0);

        if (is_wp_error($attachment_id)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => $attachment_id->get_error_message()
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id)
        ]);
    }

    /**
     * Get all content with SEO meta data
     * Used for importing pages into meta tag generator
     */
    public function getAllContent($request): \WP_REST_Response {
        $per_page = min((int) ($request->get_param('per_page') ?? 100), 500);
        $page = max((int) ($request->get_param('page') ?? 1), 1);
        $post_types_param = $request->get_param('post_types') ?? 'post,page';
        $status = $request->get_param('status') ?? 'publish';

        // Parse post types
        $post_types = array_filter(array_map('trim', explode(',', $post_types_param)));
        if (empty($post_types)) {
            $post_types = ['post', 'page'];
        }

        $args = [
            'post_type' => $post_types,
            'post_status' => $status,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $query = new \WP_Query($args);

        $posts = [];
        foreach ($query->posts as $post) {
            // Get SEO title and description from various plugins
            $seo_title = '';
            $seo_description = '';

            // Try Yoast SEO
            $yoast_title = get_post_meta($post->ID, '_yoast_wpseo_title', true);
            $yoast_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
            if ($yoast_title) $seo_title = $yoast_title;
            if ($yoast_desc) $seo_description = $yoast_desc;

            // Try RankMath
            if (empty($seo_title)) {
                $rm_title = get_post_meta($post->ID, 'rank_math_title', true);
                if ($rm_title) $seo_title = $rm_title;
            }
            if (empty($seo_description)) {
                $rm_desc = get_post_meta($post->ID, 'rank_math_description', true);
                if ($rm_desc) $seo_description = $rm_desc;
            }

            // Try All In One SEO
            if (empty($seo_title)) {
                $aioseo_title = get_post_meta($post->ID, '_aioseo_title', true);
                if ($aioseo_title) $seo_title = $aioseo_title;
            }
            if (empty($seo_description)) {
                $aioseo_desc = get_post_meta($post->ID, '_aioseo_description', true);
                if ($aioseo_desc) $seo_description = $aioseo_desc;
            }

            // Estrai contenuto pulito direttamente da WordPress (evita scraping HTTP)
            $clean_content = wp_strip_all_tags($post->post_content);
            $clean_content = preg_replace('/\s+/', ' ', $clean_content);
            $clean_content = mb_substr(trim($clean_content), 0, 50000);

            $posts[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'post_type' => $post->post_type,
                'status' => $post->post_status,
                'date' => $post->post_date,
                'seo_title' => $seo_title,
                'seo_description' => $seo_description,
                'has_seo_meta' => !empty($seo_title) || !empty($seo_description),
                'content' => $clean_content,
                'excerpt' => $post->post_excerpt,
                'word_count' => str_word_count($clean_content),
            ];
        }

        return new \WP_REST_Response([
            'success' => true,
            'posts' => $posts,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
            'seo_plugin' => $this->detectSeoPlugin(),
        ]);
    }

    /**
     * Update SEO meta (title and description) for a post
     * Rileva il plugin SEO attivo e scrive solo nei suoi campi.
     * Se nessun plugin SEO e' installato, salva in campi custom e
     * il rendering avviene direttamente via wp_head hook.
     */
    public function updateSeoMeta($request): \WP_REST_Response {
        $post_id = (int) $request['id'];
        $params = $request->get_json_params();

        // Verify post exists
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Post non trovato'
            ], 404);
        }

        $seo_plugin = $this->detectSeoPlugin();
        $updated = false;

        // Update SEO title
        if (isset($params['seo_title'])) {
            $seo_title = sanitize_text_field($params['seo_title']);

            switch ($seo_plugin) {
                case 'yoast':
                    update_post_meta($post_id, '_yoast_wpseo_title', $seo_title);
                    break;
                case 'rankmath':
                    update_post_meta($post_id, 'rank_math_title', $seo_title);
                    break;
                case 'aioseo':
                    update_post_meta($post_id, '_aioseo_title', $seo_title);
                    break;
                default:
                    // Nessun plugin SEO: salva in campo custom, renderizzato via wp_head
                    update_post_meta($post_id, '_seo_toolkit_title', $seo_title);
                    break;
            }
            $updated = true;
        }

        // Update SEO description
        if (isset($params['seo_description'])) {
            $seo_desc = sanitize_text_field($params['seo_description']);

            switch ($seo_plugin) {
                case 'yoast':
                    update_post_meta($post_id, '_yoast_wpseo_metadesc', $seo_desc);
                    break;
                case 'rankmath':
                    update_post_meta($post_id, 'rank_math_description', $seo_desc);
                    break;
                case 'aioseo':
                    update_post_meta($post_id, '_aioseo_description', $seo_desc);
                    break;
                default:
                    update_post_meta($post_id, '_seo_toolkit_description', $seo_desc);
                    break;
            }
            $updated = true;
        }

        if (!$updated) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Nessun dato da aggiornare. Invia seo_title o seo_description.'
            ], 400);
        }

        $method = $seo_plugin === 'none' ? 'direct' : $seo_plugin;

        return new \WP_REST_Response([
            'success' => true,
            'post_id' => $post_id,
            'message' => 'Meta SEO aggiornati',
            'method' => $method,
            'post_url' => get_permalink($post_id)
        ]);
    }

    // =========================================================================
    // SEO Audit Endpoint
    // =========================================================================

    /**
     * SEO Audit - Returns complete SEO data for all published pages/posts.
     * Pre-extracted server-side to allow scraping-free audit from Ainstein.
     */
    public function handleSeoAudit($request): \WP_REST_Response {
        $per_page = min($request->get_param('per_page') ?: 50, 100);
        $page = max($request->get_param('page') ?: 1, 1);
        $types = array_map('trim', explode(',', $request->get_param('type') ?: 'post,page'));

        // Validate post types
        $valid_types = get_post_types(['public' => true], 'names');
        $types = array_intersect($types, $valid_types);
        if (empty($types)) {
            $types = ['post', 'page'];
        }

        // Query posts
        $args = [
            'post_type'      => $types,
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ];

        $query = new \WP_Query($args);

        $pages = [];
        foreach ($query->posts as $post) {
            $pages[] = $this->extractPageSeoData($post);
        }

        $response = [
            'total'        => (int) $query->found_posts,
            'total_pages'  => (int) $query->max_num_pages,
            'current_page' => $page,
            'per_page'     => $per_page,
            'pages'        => $pages,
        ];

        // Include site_info on first page only
        if ($page === 1) {
            $response['site_info'] = $this->getSiteInfo();
        }

        return rest_ensure_response($response);
    }

    /**
     * Extract all SEO-relevant data from a single WordPress post.
     */
    private function extractPageSeoData($post): array {
        $url = get_permalink($post);
        $content = apply_filters('the_content', $post->post_content);

        // Get SEO meta using existing plugin detection
        $seo_title = $this->getAuditSeoMeta($post->ID, 'title');
        $seo_desc = $this->getAuditSeoMeta($post->ID, 'description');

        // Parse HTML content for structured data
        $headings = $this->extractHeadings($content);
        $images = $this->extractImages($content);
        $links = $this->extractLinks($content, home_url());

        // Canonical
        $canonical = wp_get_canonical_url($post->ID);
        if (!$canonical) {
            $canonical = $url;
        }

        // Robots meta
        $robots_meta = $this->getRobotsMeta($post->ID);

        // Open Graph
        $og = $this->getOgTags($post->ID);

        // Schema JSON-LD
        $schema = $this->extractSchemaJsonLd($post->ID);

        // Word count
        $word_count = str_word_count(wp_strip_all_tags($post->post_content));

        return [
            'id'                => $post->ID,
            'type'              => $post->post_type,
            'status'            => $post->post_status,
            'url'               => $url,
            'title_tag'         => $seo_title ?: get_the_title($post),
            'meta_description'  => $seo_desc ?: '',
            'canonical'         => $canonical,
            'robots_meta'       => $robots_meta,
            'og_title'          => $og['title'] ?? '',
            'og_description'    => $og['description'] ?? '',
            'og_image'          => $og['image'] ?? '',
            'headings'          => $headings,
            'images'            => $images,
            'internal_links'    => $links['internal'],
            'external_links'    => $links['external'],
            'schema_json_ld'    => $schema,
            'word_count'        => $word_count,
            'modified_at'       => $post->post_modified_gmt,
        ];
    }

    /**
     * Get SEO meta (title or description) for audit, detecting active SEO plugin.
     * Reuses detectSeoPlugin() logic for consistent plugin detection.
     */
    private function getAuditSeoMeta(int $post_id, string $field): string {
        $seo_plugin = $this->detectSeoPlugin();

        if ($field === 'title') {
            switch ($seo_plugin) {
                case 'yoast':
                    return get_post_meta($post_id, '_yoast_wpseo_title', true) ?: '';
                case 'rankmath':
                    return get_post_meta($post_id, 'rank_math_title', true) ?: '';
                case 'aioseo':
                    return get_post_meta($post_id, '_aioseo_title', true) ?: '';
                default:
                    return get_post_meta($post_id, '_seo_toolkit_title', true) ?: '';
            }
        }

        if ($field === 'description') {
            switch ($seo_plugin) {
                case 'yoast':
                    return get_post_meta($post_id, '_yoast_wpseo_metadesc', true) ?: '';
                case 'rankmath':
                    return get_post_meta($post_id, 'rank_math_description', true) ?: '';
                case 'aioseo':
                    return get_post_meta($post_id, '_aioseo_description', true) ?: '';
                default:
                    return get_post_meta($post_id, '_seo_toolkit_description', true) ?: '';
            }
        }

        return '';
    }

    /**
     * Extract headings (H1-H6) from HTML content.
     */
    private function extractHeadings(string $html): array {
        if (empty($html)) return [];

        $headings = [];
        if (preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/si', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $headings[] = [
                    'level' => (int) $match[1],
                    'text'  => wp_strip_all_tags($match[2]),
                ];
            }
        }
        return $headings;
    }

    /**
     * Extract images with src, alt, width, height from HTML content.
     */
    private function extractImages(string $html): array {
        if (empty($html)) return [];

        $images = [];
        if (preg_match_all('/<img[^>]+>/si', $html, $matches)) {
            foreach ($matches[0] as $img_tag) {
                $src = '';
                $alt = '';
                $width = 0;
                $height = 0;

                if (preg_match('/src=["\']([^"\']+)/i', $img_tag, $m)) $src = $m[1];
                if (preg_match('/alt=["\']([^"\']*)/i', $img_tag, $m)) $alt = $m[1];
                if (preg_match('/width=["\']?(\d+)/i', $img_tag, $m)) $width = (int) $m[1];
                if (preg_match('/height=["\']?(\d+)/i', $img_tag, $m)) $height = (int) $m[1];

                if ($src) {
                    $images[] = [
                        'src'    => $src,
                        'alt'    => $alt,
                        'width'  => $width,
                        'height' => $height,
                    ];
                }
            }
        }
        return $images;
    }

    /**
     * Extract internal and external links from HTML content.
     */
    private function extractLinks(string $html, string $site_url): array {
        if (empty($html)) return ['internal' => [], 'external' => []];

        $internal = [];
        $external = [];
        $site_host = wp_parse_url($site_url, PHP_URL_HOST);

        // Match full <a>...</a> tags to get anchor text
        if (preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/si', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $href = $match[1];
                $anchor = wp_strip_all_tags($match[2]);
                $nofollow = (bool) preg_match('/rel=["\'][^"\']*nofollow/i', $match[0]);

                // Skip anchors, javascript, mailto
                if (strpos($href, '#') === 0 || strpos($href, 'javascript:') === 0 || strpos($href, 'mailto:') === 0) {
                    continue;
                }

                // Make relative URLs absolute
                if (strpos($href, '/') === 0) {
                    $href = rtrim($site_url, '/') . $href;
                }

                $link_host = wp_parse_url($href, PHP_URL_HOST);
                $link_data = [
                    'url'      => $href,
                    'anchor'   => trim($anchor),
                    'nofollow' => $nofollow,
                ];

                if ($link_host && $link_host !== $site_host && $link_host !== 'www.' . $site_host && 'www.' . $link_host !== $site_host) {
                    $external[] = $link_data;
                } else {
                    $internal[] = $link_data;
                }
            }
        }

        return ['internal' => $internal, 'external' => $external];
    }

    /**
     * Get robots meta directives for a post, reading from detected SEO plugin.
     */
    private function getRobotsMeta(int $post_id): string {
        $seo_plugin = $this->detectSeoPlugin();

        switch ($seo_plugin) {
            case 'yoast':
                $robots = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
                $nofollow = get_post_meta($post_id, '_yoast_wpseo_meta-robots-nofollow', true);
                $parts = [];
                $parts[] = ($robots === '1') ? 'noindex' : 'index';
                $parts[] = ($nofollow === '1') ? 'nofollow' : 'follow';
                return implode(', ', $parts);

            case 'rankmath':
                $robots = get_post_meta($post_id, 'rank_math_robots', true);
                if (is_array($robots)) return implode(', ', $robots);
                return $robots ?: 'index, follow';

            case 'aioseo':
                $robots_default = get_post_meta($post_id, '_aioseo_noindex', true);
                $nofollow = get_post_meta($post_id, '_aioseo_nofollow', true);
                $parts = [];
                $parts[] = $robots_default ? 'noindex' : 'index';
                $parts[] = $nofollow ? 'nofollow' : 'follow';
                return implode(', ', $parts);

            default:
                return 'index, follow';
        }
    }

    /**
     * Get Open Graph tags for a post from detected SEO plugin.
     */
    private function getOgTags(int $post_id): array {
        $seo_plugin = $this->detectSeoPlugin();

        switch ($seo_plugin) {
            case 'yoast':
                return [
                    'title'       => get_post_meta($post_id, '_yoast_wpseo_opengraph-title', true) ?: '',
                    'description' => get_post_meta($post_id, '_yoast_wpseo_opengraph-description', true) ?: '',
                    'image'       => get_post_meta($post_id, '_yoast_wpseo_opengraph-image', true) ?: '',
                ];

            case 'rankmath':
                return [
                    'title'       => get_post_meta($post_id, 'rank_math_facebook_title', true) ?: '',
                    'description' => get_post_meta($post_id, 'rank_math_facebook_description', true) ?: '',
                    'image'       => get_post_meta($post_id, 'rank_math_facebook_image', true) ?: '',
                ];

            case 'aioseo':
                return [
                    'title'       => get_post_meta($post_id, '_aioseo_og_title', true) ?: '',
                    'description' => get_post_meta($post_id, '_aioseo_og_description', true) ?: '',
                    'image'       => get_post_meta($post_id, '_aioseo_og_image', true) ?: '',
                ];

            default:
                return ['title' => '', 'description' => '', 'image' => ''];
        }
    }

    /**
     * Extract Schema.org JSON-LD data from SEO plugin meta.
     */
    private function extractSchemaJsonLd(int $post_id): array {
        $seo_plugin = $this->detectSeoPlugin();
        $schemas = [];

        switch ($seo_plugin) {
            case 'yoast':
                // Yoast generates schema dynamically via wp_head, difficult to extract per-post
                break;

            case 'rankmath':
                $schema = get_post_meta($post_id, 'rank_math_schema_Article', true);
                if ($schema) {
                    $schemas[] = is_string($schema) ? json_decode($schema, true) : $schema;
                }
                break;
        }

        return array_filter($schemas);
    }

    /**
     * Get site-wide info for SEO audit (robots.txt, sitemap, SSL, etc.)
     * Only included on the first page of results.
     */
    private function getSiteInfo(): array {
        // Check robots.txt
        $robots_url = home_url('/robots.txt');
        $robots_response = wp_remote_get($robots_url, ['timeout' => 5, 'sslverify' => false]);
        $has_robots = !is_wp_error($robots_response) && wp_remote_retrieve_response_code($robots_response) === 200;
        $robots_content = $has_robots ? wp_remote_retrieve_body($robots_response) : '';

        // Check sitemap
        $sitemap_locations = ['/sitemap.xml', '/sitemap_index.xml', '/wp-sitemap.xml'];
        $has_sitemap = false;
        foreach ($sitemap_locations as $path) {
            $resp = wp_remote_head(home_url($path), ['timeout' => 5, 'sslverify' => false]);
            if (!is_wp_error($resp) && wp_remote_retrieve_response_code($resp) === 200) {
                $has_sitemap = true;
                break;
            }
        }

        return [
            'name'               => get_bloginfo('name'),
            'url'                => home_url('/'),
            'wp_version'         => get_bloginfo('version'),
            'seo_plugin'         => $this->detectSeoPlugin(),
            'plugin_version'     => $this->version,
            'has_ssl'            => is_ssl(),
            'has_robots_txt'     => $has_robots,
            'robots_txt_content' => $robots_content,
            'has_sitemap'        => $has_sitemap,
        ];
    }
}

// Inizializza plugin
new SEOToolkitConnector();
