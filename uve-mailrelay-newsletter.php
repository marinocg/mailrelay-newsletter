<?php
/**
 * Plugin Name: Uve Mailrelay Newsletter (Turnstile + GDPR, Double Opt-in + Logs)
 * Description: Widget + shortcode newsletter with Cloudflare Turnstile and Mailrelay official API. Uses inactive + resend_confirmation_email for double opt-in. Neutral success message to prevent email enumeration. GDPR consent log with retention and confirmation-send logging.
 * Version: 1.4.1
 * Author: Uve / Custom
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

final class UVE_Mailrelay_Newsletter {
    const OPT_KEY = 'uve_mr_newsletter_options';
    const TABLE   = 'uve_mr_newsletter_consent';
    const NONCE   = 'uve_mr_subscribe_nonce';
    const CRON_PURGE = 'uve_mr_newsletter_purge_logs';

    private static bool $assets_requested = false;

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_shortcode']);
        add_action('widgets_init', [__CLASS__, 'register_widget']);
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_init', [__CLASS__, 'admin_init']);

        add_action('admin_post_nopriv_uve_mr_subscribe', [__CLASS__, 'handle_submit']);
        add_action('admin_post_uve_mr_subscribe', [__CLASS__, 'handle_submit']);

        add_action(self::CRON_PURGE, [__CLASS__, 'purge_old_logs']);

        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);
    }

    public static function defaults(): array {
        return [
            // Mailrelay
            'api_base_url' => '', // e.g. https://YOURACCOUNT.ipzmarketing.com/api/v1
            'api_token'    => '',
            'group_ids'    => '1', // comma-separated
            'subscriber_status' => 'inactive', // inactive (double opt-in) / active (single opt-in)

            // Turnstile (optional if you define CF_TURNSTILE_* constants)
            'turnstile_site_key'   => '',
            'turnstile_secret_key' => '',

            // UI texts
            'title'       => 'Newsletter',
            'description' => 'Suscríbete y estarás al tanto de nuestras novedades',
            'email_placeholder' => 'Email...',
            'submit_label' => 'Suscribir',

            // GDPR
            'privacy_url' => '',
            'consent_label' => 'Acepto recibir la newsletter y he leído la política de privacidad',
            'store_consent_log' => '1', // "1" or "0"
            'hash_ip' => '1',            // store hashed IP by default
            'retention_days' => 180,     // purge logs older than N days

            // Rate limit
            'rate_limit_max' => 5,
            'rate_limit_window_seconds' => 3600,

            // Confirmation resend anti-abuse (per IP+email)
            'confirm_resend_max' => 2,
            'confirm_resend_window_seconds' => 3600,
        ];
    }

    public static function get_options(): array {
        $opts = get_option(self::OPT_KEY, []);
        return wp_parse_args(is_array($opts) ? $opts : [], self::defaults());
    }

    public static function activate(): void {
        self::maybe_create_or_update_table();
        if (!get_option(self::OPT_KEY)) add_option(self::OPT_KEY, self::defaults());

        if (!wp_next_scheduled(self::CRON_PURGE)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_PURGE);
        }
    }

    public static function deactivate(): void {
        $ts = wp_next_scheduled(self::CRON_PURGE);
        if ($ts) wp_unschedule_event($ts, self::CRON_PURGE);
    }

    public static function admin_menu(): void {
        add_options_page(
            'Uve Mailrelay Newsletter',
            'Uve Mailrelay Newsletter',
            'manage_options',
            'uve-mr-newsletter',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function admin_init(): void {
        register_setting('uve_mr_newsletter', self::OPT_KEY, [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_options'],
            'default' => self::defaults(),
        ]);
    }

    public static function sanitize_options($raw): array {
        $raw = is_array($raw) ? $raw : [];
        $def = self::defaults();

        $out = [];
        $out['api_base_url'] = esc_url_raw(trim((string)($raw['api_base_url'] ?? $def['api_base_url'])));
        $out['api_token']    = sanitize_text_field((string)($raw['api_token'] ?? $def['api_token']));
        $out['group_ids']    = sanitize_text_field((string)($raw['group_ids'] ?? $def['group_ids']));

        $status = sanitize_text_field((string)($raw['subscriber_status'] ?? $def['subscriber_status']));
        $out['subscriber_status'] = in_array($status, ['inactive', 'active'], true) ? $status : 'inactive';

        $out['turnstile_site_key']   = sanitize_text_field((string)($raw['turnstile_site_key'] ?? $def['turnstile_site_key']));
        $out['turnstile_secret_key'] = sanitize_text_field((string)($raw['turnstile_secret_key'] ?? $def['turnstile_secret_key']));

        $out['title'] = sanitize_text_field((string)($raw['title'] ?? $def['title']));
        $out['description'] = sanitize_text_field((string)($raw['description'] ?? $def['description']));
        $out['email_placeholder'] = sanitize_text_field((string)($raw['email_placeholder'] ?? $def['email_placeholder']));
        $out['submit_label'] = sanitize_text_field((string)($raw['submit_label'] ?? $def['submit_label']));

        $out['privacy_url'] = esc_url_raw(trim((string)($raw['privacy_url'] ?? $def['privacy_url'])));
        $out['consent_label'] = sanitize_text_field((string)($raw['consent_label'] ?? $def['consent_label']));

        $out['store_consent_log'] = !empty($raw['store_consent_log']) ? '1' : '0';
        $out['hash_ip'] = !empty($raw['hash_ip']) ? '1' : '0';
        $out['retention_days'] = isset($raw['retention_days']) ? max(1, (int)$raw['retention_days']) : (int)$def['retention_days'];

        $out['rate_limit_max'] = isset($raw['rate_limit_max']) ? max(1, (int)$raw['rate_limit_max']) : (int)$def['rate_limit_max'];
        $out['rate_limit_window_seconds'] = isset($raw['rate_limit_window_seconds']) ? max(60, (int)$raw['rate_limit_window_seconds']) : (int)$def['rate_limit_window_seconds'];

        $out['confirm_resend_max'] = isset($raw['confirm_resend_max']) ? max(0, (int)$raw['confirm_resend_max']) : (int)$def['confirm_resend_max'];
        $out['confirm_resend_window_seconds'] = isset($raw['confirm_resend_window_seconds']) ? max(60, (int)$raw['confirm_resend_window_seconds']) : (int)$def['confirm_resend_window_seconds'];

        return $out;
    }

    public static function render_settings_page(): void {
        if (!current_user_can('manage_options')) return;

        // Ensure schema is up-to-date before rendering (safe + idempotent)
        self::maybe_create_or_update_table();

        if (!empty($_POST['uve_mr_purge_now']) && check_admin_referer('uve_mr_purge_now')) {
            $deleted = self::purge_old_logs(true);
            echo '<div class="notice notice-success"><p>Purgados ' . esc_html((string)$deleted) . ' registros.</p></div>';
        }

        $opts = self::get_options();
        ?>
        <div class="wrap">
            <h1>Uve Mailrelay Newsletter</h1>

            <form method="post" action="options.php">
                <?php settings_fields('uve_mr_newsletter'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Mailrelay API base URL</th>
                        <td>
                            <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[api_base_url]"
                                   value="<?php echo esc_attr($opts['api_base_url']); ?>"
                                   placeholder="https://YOURACCOUNT.ipzmarketing.com/api/v1" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Mailrelay API token</th>
                        <td><input type="password" class="regular-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[api_token]"
                                   value="<?php echo esc_attr($opts['api_token']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Group IDs</th>
                        <td>
                            <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[group_ids]"
                                   value="<?php echo esc_attr($opts['group_ids']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Modo suscripción</th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPT_KEY); ?>[subscriber_status]">
                                <option value="inactive" <?php selected($opts['subscriber_status'], 'inactive'); ?>>Double opt-in (inactive + confirmación)</option>
                                <option value="active" <?php selected($opts['subscriber_status'], 'active'); ?>>Single opt-in (active)</option>
                            </select>
                        </td>
                    </tr>

                    <tr><th scope="row">Turnstile Site Key</th>
                        <td>
                            <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[turnstile_site_key]"
                                   value="<?php echo esc_attr($opts['turnstile_site_key']); ?>" />
                        </td>
                    </tr>
                    <tr><th scope="row">Turnstile Secret Key</th>
                        <td>
                            <input type="password" class="regular-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[turnstile_secret_key]"
                                   value="<?php echo esc_attr($opts['turnstile_secret_key']); ?>" />
                        </td>
                    </tr>

                    <tr><th scope="row">Textos</th>
                        <td>
                            <p><label>Título<br>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[title]" value="<?php echo esc_attr($opts['title']); ?>">
                            </label></p>
                            <p><label>Descripción<br>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[description]" value="<?php echo esc_attr($opts['description']); ?>">
                            </label></p>
                            <p><label>Placeholder email<br>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[email_placeholder]" value="<?php echo esc_attr($opts['email_placeholder']); ?>">
                            </label></p>
                            <p><label>Texto botón<br>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[submit_label]" value="<?php echo esc_attr($opts['submit_label']); ?>">
                            </label></p>
                        </td>
                    </tr>

                    <tr><th scope="row">RGPD + Logs</th>
                        <td>
                            <p><label>URL política de privacidad<br>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[privacy_url]"
                                       value="<?php echo esc_attr($opts['privacy_url']); ?>">
                            </label></p>
                            <p><label>Texto del checkbox<br>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[consent_label]"
                                       value="<?php echo esc_attr($opts['consent_label']); ?>">
                            </label></p>
                            <p>
                                <label><input type="checkbox" name="<?php echo esc_attr(self::OPT_KEY); ?>[store_consent_log]" value="1" <?php checked($opts['store_consent_log'], '1'); ?>>
                                    Guardar log en BD</label><br>
                                <label><input type="checkbox" name="<?php echo esc_attr(self::OPT_KEY); ?>[hash_ip]" value="1" <?php checked($opts['hash_ip'], '1'); ?>>
                                    Guardar IP como hash</label><br>
                                <label>Retención (días)<br>
                                    <input type="number" min="1" class="small-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[retention_days]"
                                           value="<?php echo esc_attr((string)$opts['retention_days']); ?>">
                                </label>
                            </p>
                        </td>
                    </tr>

                    <tr><th scope="row">Rate limit</th>
                        <td>
                            <p><label>Máx intentos por IP+email<br>
                                <input type="number" min="1" class="small-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[rate_limit_max]"
                                       value="<?php echo esc_attr((string)$opts['rate_limit_max']); ?>">
                            </label></p>
                            <p><label>Ventana (segundos)<br>
                                <input type="number" min="60" class="small-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[rate_limit_window_seconds]"
                                       value="<?php echo esc_attr((string)$opts['rate_limit_window_seconds']); ?>">
                            </label></p>

                            <hr>
                            <p><label>Máx reenvíos confirmación por IP+email (0 = desactivar)<br>
                                <input type="number" min="0" class="small-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[confirm_resend_max]"
                                       value="<?php echo esc_attr((string)$opts['confirm_resend_max']); ?>">
                            </label></p>
                            <p><label>Ventana confirmación (segundos)<br>
                                <input type="number" min="60" class="small-text" name="<?php echo esc_attr(self::OPT_KEY); ?>[confirm_resend_window_seconds]"
                                       value="<?php echo esc_attr((string)$opts['confirm_resend_window_seconds']); ?>">
                            </label></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr>
            <h2>Shortcode</h2>
            <p><code>[uve_mailrelay_newsletter]</code></p>

            <hr>
            <h2>Logs recientes</h2>
            <?php self::render_logs_table_safe(); ?>

            <form method="post" style="margin-top:12px;">
                <?php wp_nonce_field('uve_mr_purge_now'); ?>
                <button type="submit" class="button" name="uve_mr_purge_now" value="1">Purgar ahora</button>
            </form>
        </div>
        <?php
    }

    // --- LOGS TABLE rendering with schema introspection ---

    private static function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    private static function get_table_columns(): array {
        global $wpdb;
        $table = self::table_name();
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) return [];

        $cols = $wpdb->get_results("SHOW COLUMNS FROM {$table}", ARRAY_A);
        if (!is_array($cols)) return [];
        $names = [];
        foreach ($cols as $c) {
            if (!empty($c['Field'])) $names[] = (string)$c['Field'];
        }
        return $names;
    }

    private static function render_logs_table_safe(): void {
        global $wpdb;
        $table = self::table_name();

        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) {
            echo '<p>No existe la tabla de logs todavía. Desactiva y reactiva el plugin para crearla.</p>';
            return;
        }

        $cols = self::get_table_columns();

        // Build SELECT dynamically based on actual columns
        $want = [
            'id','created_at','email','accepted','ip_hash','ip_raw','page_url',
            'mailrelay_http_code',
            'confirmation_requested_at','confirmation_http_code'
        ];
        $select = [];
        foreach ($want as $c) {
            if (in_array($c, $cols, true)) $select[] = $c;
        }

        if (!$select) {
            echo '<p>La tabla existe pero no se pudieron leer columnas.</p>';
            return;
        }

        $sql = "SELECT " . implode(',', array_map(fn($c)=>"`{$c}`", $select)) . " FROM {$table} ORDER BY id DESC LIMIT 30";
        $rows = $wpdb->get_results($sql, ARRAY_A);

        if (!$rows) {
            echo '<p>Sin registros aún.</p>';
            return;
        }

        $hasConfirm = in_array('confirmation_requested_at', $select, true) || in_array('confirmation_http_code', $select, true);

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>ID</th><th>Fecha</th><th>Email</th><th>Consent</th><th>IP</th><th>Origen</th><th>Create</th>';
        if ($hasConfirm) echo '<th>Confirm email</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $r) {
            $ip = $r['ip_raw'] ?? '';
            if (!$ip) $ip = $r['ip_hash'] ?? '';

            $create = (string)($r['mailrelay_http_code'] ?? '');

            $confirmText = '-';
            if ($hasConfirm) {
                $cAt = (string)($r['confirmation_requested_at'] ?? '');
                $cCode = (string)($r['confirmation_http_code'] ?? '');
                if ($cAt) $confirmText = $cAt . ' / ' . $cCode;
            }

            echo '<tr>';
            echo '<td>' . esc_html((string)($r['id'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string)($r['created_at'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string)($r['email'] ?? '')) . '</td>';
            echo '<td>' . (((int)($r['accepted'] ?? 0) === 1) ? 'sí' : 'no') . '</td>';
            echo '<td><code>' . esc_html((string)$ip) . '</code></td>';
            echo '<td>' . esc_html((string)($r['page_url'] ?? '')) . '</td>';
            echo '<td>' . esc_html($create) . '</td>';
            if ($hasConfirm) echo '<td>' . esc_html($confirmText) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        if (!$hasConfirm) {
            echo '<p class="description">Aún no existen columnas de confirmación. El plugin debería añadirlas automáticamente al activarse (dbDelta).</p>';
        }
    }

    // ----- DB schema (create OR update with dbDelta always) -----

    private static function maybe_create_or_update_table(): void {
        global $wpdb;
        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();

        // dbDelta is idempotent and will add missing columns/indexes when SQL changes
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            accepted TINYINT(1) NOT NULL DEFAULT 0,
            accepted_at DATETIME NULL,
            page_url TEXT NULL,
            ip_hash VARCHAR(128) NULL,
            ip_raw VARCHAR(64) NULL,
            user_agent TEXT NULL,
            mailrelay_http_code INT NULL,
            mailrelay_response LONGTEXT NULL,
            confirmation_requested_at DATETIME NULL,
            confirmation_http_code INT NULL,
            confirmation_response LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY created_at (created_at),
            KEY email_idx (email)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // ----- Purge -----

    public static function purge_old_logs(bool $return_count = false) {
        $opts = self::get_options();
        $days = max(1, (int)$opts['retention_days']);

        global $wpdb;
        $table = self::table_name();

        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) return $return_count ? 0 : null;

        $cutoff_ts = time() - ($days * DAY_IN_SECONDS);
        $cutoff_local = date('Y-m-d H:i:s', $cutoff_ts);

        $deleted = $wpdb->query(
            $wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $cutoff_local)
        );

        return $return_count ? (int)$deleted : null;
    }

    // ----- Public hooks -----

    public static function register_widget(): void {
        register_widget('UVE_MR_Newsletter_Widget');
    }

    public static function register_shortcode(): void {
        add_shortcode('uve_mailrelay_newsletter', [__CLASS__, 'shortcode']);
    }

    // ----- Assets & render -----

    private static function ensure_assets(): void {
        if (self::$assets_requested) return;
        self::$assets_requested = true;

        add_action('wp_footer', function () {
            if (!wp_script_is('cf-turnstile', 'enqueued') && !wp_script_is('cf-turnstile', 'done')) {
                wp_enqueue_script(
                    'cf-turnstile',
                    'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
                    [],
                    null,
                    true
                );
            }
        }, 5);

        add_action('wp_footer', function () {
            $site_key = self::get_turnstile_site_key();
            if (!$site_key) return;
            ?>
            <script>
                (function(){
                    function renderAll(){
                        if (!window.turnstile) return;
                        document.querySelectorAll('.uve-mr-turnstile[data-sitekey]').forEach(function(el){
                            if (el.getAttribute('data-rendered') === '1') return;
                            el.setAttribute('data-rendered','1');
                            try { window.turnstile.render(el, { sitekey: el.getAttribute('data-sitekey') }); } catch(e) {}
                        });
                    }
                    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', renderAll);
                    else renderAll();
                    window.__uveMrRenderTurnstile = renderAll;
                })();
            </script>
            <?php
        }, 50);
    }

    public static function shortcode($atts = []): string {
        $opts = self::get_options();
        $atts = shortcode_atts([
            'title' => $opts['title'],
            'description' => $opts['description'],
            'email_placeholder' => $opts['email_placeholder'],
            'submit_label' => $opts['submit_label'],
            'group_ids' => $opts['group_ids'],
            'privacy_url' => $opts['privacy_url'],
            'consent_label' => $opts['consent_label'],
            'class' => '',
        ], is_array($atts) ? $atts : []);
        return self::render_form($atts);
    }

    private static function render_form(array $args): string {
        self::ensure_assets();

        $email_placeholder = $args['email_placeholder'] ?? 'Email...';
        $title = $args['title'] ?? '';
        $desc  = $args['description'] ?? '';
        $submit = $args['submit_label'] ?? 'Suscribir';
        $group_ids = $args['group_ids'] ?? '';
        $privacy_url = $args['privacy_url'] ?? '';
        $consent_label = $args['consent_label'] ?? 'Acepto la política de privacidad';
        $class = $args['class'] ?? '';

        $site_key = self::get_turnstile_site_key();
        $action = esc_url(admin_url('admin-post.php'));

        $msg_html = '';
        if (isset($_GET['uve_mr_status'])) {
            $st = sanitize_text_field((string)$_GET['uve_mr_status']);
            if ($st === 'ok') {
                $msg_html = '<p class="uve-mr-msg uve-mr-ok">Gracias. Si el email es válido, recibirás un correo para confirmar (o ya estabas suscrito).</p>';
            } elseif ($st === 'captcha') {
                $msg_html = '<p class="uve-mr-msg uve-mr-err">Por favor, verifica que eres humano.</p>';
            } elseif ($st === 'consent') {
                $msg_html = '<p class="uve-mr-msg uve-mr-err">Debes aceptar la política de privacidad.</p>';
            } else {
                $msg_html = '<p class="uve-mr-msg uve-mr-err">No se pudo completar la solicitud. Inténtalo de nuevo.</p>';
            }
        }

        ob_start();
        ?>
        <div class="uve-mr-newsletter <?php echo esc_attr($class); ?>">
            <?php if ($title): ?><h2 class="widgettitle"><?php echo esc_html($title); ?></h2><?php endif; ?>
            <?php echo $msg_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <form class="simple_form form form-vertical uve-mr-form" method="post" action="<?php echo $action; ?>" accept-charset="UTF-8">
                <input type="hidden" name="action" value="uve_mr_subscribe">
                <?php wp_nonce_field(self::NONCE, '_wpnonce'); ?>
                <input type="hidden" name="uve_mr_group_ids" value="<?php echo esc_attr($group_ids); ?>">
                <input type="hidden" name="uve_mr_page_url" value="<?php echo esc_attr(self::current_url()); ?>">

                <div class="mc4wp-form-fields">
                    <?php if ($desc): ?><p class="mdes"><?php echo esc_html($desc); ?></p><?php endif; ?>

                    <p class="memail">
                        <input type="email" name="subscriber[email]" placeholder="<?php echo esc_attr($email_placeholder); ?>" required>
                    </p>

                    <div style="position:absolute;left:-9999px;height:0;overflow:hidden;" aria-hidden="true">
                        <label>Deja este campo vacío</label>
                        <input type="text" name="uve_mr_hp" tabindex="-1" autocomplete="off" value="">
                    </div>

                    <p class="mconsent" style="margin: 8px 0;">
                        <input value="0" type="hidden" name="subscriber[subscribed_with_acceptance]">
                        <label style="display:flex; gap:8px; align-items:flex-start;">
                            <input type="checkbox" value="1" name="subscriber[subscribed_with_acceptance]" required>
                            <span>
                                <?php echo esc_html($consent_label); ?>
                                <?php if ($privacy_url): ?>
                                    <a href="<?php echo esc_url($privacy_url); ?>" rel="noopener" target="_blank">(ver)</a>
                                <?php endif; ?>
                                <br><small>Puedes darte de baja en cualquier momento desde el enlace de cada email.</small>
                            </span>
                        </label>
                    </p>

                    <?php if ($site_key): ?>
                        <div class="uve-mr-turnstile" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
                        <noscript><p>Activa JavaScript para poder suscribirte.</p></noscript>
                    <?php else: ?>
                        <p class="uve-mr-msg uve-mr-err">Falta configurar Turnstile (Site Key).</p>
                    <?php endif; ?>

                    <p class="msubmit" style="margin-top: 10px;">
                        <input type="submit" value="<?php echo esc_attr($submit); ?>">
                    </p>
                </div>
            </form>

            <style>
                .uve-mr-msg { margin: 0 0 10px; padding: 10px 12px; border-radius: 6px; }
                .uve-mr-ok  { background: rgba(0,128,0,.08); }
                .uve-mr-err { background: rgba(200,0,0,.08); }
            </style>
        </div>
        <?php
        return (string)ob_get_clean();
    }

    // ----- Submit handler -----

    public static function handle_submit(): void {
        $opts = self::get_options();

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce((string)$_POST['_wpnonce'], self::NONCE)) {
            wp_safe_redirect(self::safe_back_url(['uve_mr_status' => 'ok'])); exit;
        }

        $hp = isset($_POST['uve_mr_hp']) ? (string)$_POST['uve_mr_hp'] : '';
        if ($hp !== '') {
            wp_safe_redirect(self::safe_back_url(['uve_mr_status' => 'ok'])); exit;
        }

        $email = isset($_POST['subscriber']['email']) ? sanitize_email((string)$_POST['subscriber']['email']) : '';
        $accepted = isset($_POST['subscriber']['subscribed_with_acceptance']) && (string)$_POST['subscriber']['subscribed_with_acceptance'] === '1';
        $ip = self::get_client_ip();

        if (!$email || !is_email($email)) {
            wp_safe_redirect(self::safe_back_url(['uve_mr_status' => 'ok'])); exit;
        }

        if (!$accepted) {
            wp_safe_redirect(self::safe_back_url(['uve_mr_status' => 'consent'])); exit;
        }

        $rl_key = 'uve_mr_rl_' . md5($ip . '|' . strtolower($email));
        $count = (int) get_transient($rl_key);
        if ($count >= (int)$opts['rate_limit_max']) {
            wp_safe_redirect(self::safe_back_url(['uve_mr_status' => 'ok'])); exit;
        }
        set_transient($rl_key, $count + 1, (int)$opts['rate_limit_window_seconds']);

        $token = sanitize_text_field((string)($_POST['cf-turnstile-response'] ?? ''));
        if (!self::verify_turnstile($token, $ip)) {
            wp_safe_redirect(self::safe_back_url(['uve_mr_status' => 'captcha'])); exit;
        }

        $group_ids_raw = sanitize_text_field((string)($_POST['uve_mr_group_ids'] ?? $opts['group_ids']));
        $group_ids = self::parse_group_ids($group_ids_raw);
        $page_url = sanitize_text_field((string)($_POST['uve_mr_page_url'] ?? ''));

        $result = self::mailrelay_subscribe_with_confirmation($email, $group_ids, true, $ip);

        if ($opts['store_consent_log'] === '1') {
            self::maybe_create_or_update_table();
            self::store_consent_log_compatible([
                'email' => $email,
                'accepted' => 1,
                'accepted_at' => current_time('mysql'),
                'page_url' => $page_url,
                'ip' => $ip,
                'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 2000),
                'mailrelay_http_code' => $result['http_code'] ?? null,
                'mailrelay_response' => $result['body'] ?? null,
                'confirmation_requested_at' => $result['confirmation_requested_at'] ?? null,
                'confirmation_http_code' => $result['confirmation_http_code'] ?? null,
                'confirmation_response' => $result['confirmation_response'] ?? null,
            ]);
        }

        wp_safe_redirect(self::safe_back_url(['uve_mr_status' => 'ok']));
        exit;
    }

    private static function safe_back_url(array $add_query): string {
        $ref = wp_get_referer();
        if (!$ref) $ref = home_url('/');
        $url = remove_query_arg(array_keys($add_query), $ref);
        return add_query_arg($add_query, $url);
    }

    private static function parse_group_ids(string $raw): array {
        $parts = array_filter(array_map('trim', explode(',', $raw)));
        $ids = [];
        foreach ($parts as $p) $ids[] = (int)$p;
        $ids = array_filter($ids, fn($v) => $v > 0);
        return array_values(array_unique($ids));
    }

    private static function get_turnstile_site_key(): string {
        if (defined('CF_TURNSTILE_SITE_KEY') && CF_TURNSTILE_SITE_KEY) return (string)CF_TURNSTILE_SITE_KEY;
        $opts = self::get_options();
        return (string)($opts['turnstile_site_key'] ?? '');
    }

    private static function get_turnstile_secret_key(): string {
        if (defined('CF_TURNSTILE_SECRET_KEY') && CF_TURNSTILE_SECRET_KEY) return (string)CF_TURNSTILE_SECRET_KEY;
        $opts = self::get_options();
        return (string)($opts['turnstile_secret_key'] ?? '');
    }

    private static function verify_turnstile(string $token, string $ip): bool {
        $secret = self::get_turnstile_secret_key();
        if (!$secret || !$token) return false;

        $resp = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'timeout' => 10,
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ],
        ]);

        if (is_wp_error($resp)) return false;

        $code = (int) wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        if ($code < 200 || $code >= 300) return false;

        $data = json_decode($body, true);
        return is_array($data) && !empty($data['success']);
    }

    // ----- Mailrelay API -----

    private static function mailrelay_subscribe_with_confirmation(string $email, array $group_ids, bool $accepted, string $ip): array {
        $opts = self::get_options();
        $base = rtrim((string)$opts['api_base_url'], '/');
        $token = (string)$opts['api_token'];
        if (!$base || !$token) return ['ok' => false, 'http_code' => 0, 'body' => 'Missing config'];

        $status = ($opts['subscriber_status'] === 'active') ? 'active' : 'inactive';

        $create = self::mailrelay_post_json($base . '/subscribers', $token, [
            'status' => $status,
            'email' => $email,
            'group_ids' => $group_ids,
            'subscribed_with_acceptance' => $accepted,
            'subscribe_ip' => $ip,
        ]);

        $out = [
            'ok' => !empty($create['ok']) || !empty($create['already_exists']),
            'http_code' => $create['http_code'] ?? null,
            'body' => $create['body'] ?? null,
            'confirmation_requested_at' => null,
            'confirmation_http_code' => null,
            'confirmation_response' => null,
        ];

        if ($status !== 'inactive') return $out;

        if ((int)$opts['confirm_resend_max'] > 0) {
            $k = 'uve_mr_crl_' . md5($ip . '|' . strtolower($email));
            $c = (int) get_transient($k);
            if ($c >= (int)$opts['confirm_resend_max']) return $out;
            set_transient($k, $c + 1, (int)$opts['confirm_resend_window_seconds']);
        }

        $subscriber_id = null;
        if (!empty($create['ok'])) {
            $subscriber_id = self::extract_subscriber_id_from_body((string)($create['body'] ?? ''));
        } elseif (!empty($create['already_exists'])) {
            $subscriber_id = self::find_subscriber_id_by_email_best_effort($base, $token, $email);
        }

        if ($subscriber_id) {
            $confirm = self::mailrelay_resend_confirmation($base, $token, (int)$subscriber_id);
            $out['confirmation_requested_at'] = current_time('mysql');
            $out['confirmation_http_code'] = $confirm['http_code'] ?? null;
            $out['confirmation_response'] = $confirm['body'] ?? null;
        }

        return $out;
    }

    private static function mailrelay_resend_confirmation(string $base, string $token, int $subscriber_id): array {
        $url = $base . '/subscribers/' . $subscriber_id . '/resend_confirmation_email';
        $resp = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
                'X-AUTH-TOKEN' => $token,
            ],
            'body' => '',
        ]);

        if (is_wp_error($resp)) return ['ok' => false, 'http_code' => 0, 'body' => $resp->get_error_message()];

        $code = (int) wp_remote_retrieve_response_code($resp);
        $body = (string) wp_remote_retrieve_body($resp);
        return ['ok' => ($code >= 200 && $code < 300), 'http_code' => $code, 'body' => $body];
    }

    private static function extract_subscriber_id_from_body(string $body): ?int {
        if (!$body) return null;
        $data = json_decode($body, true);
        if (!is_array($data)) return null;

        $candidates = [
            $data['id'] ?? null,
            $data['data']['id'] ?? null,
            $data['subscriber']['id'] ?? null,
        ];
        foreach ($candidates as $c) {
            if (is_numeric($c) && (int)$c > 0) return (int)$c;
        }
        return null;
    }

    private static function find_subscriber_id_by_email_best_effort(string $base, string $token, string $email): ?int {
        $variants = [
            $base . '/subscribers?email=' . rawurlencode($email),
            $base . '/subscribers?search=' . rawurlencode($email),
        ];

        foreach ($variants as $url) {
            $resp = wp_remote_get($url, [
                'timeout' => 15,
                'headers' => [
                    'Accept' => 'application/json',
                    'X-AUTH-TOKEN' => $token,
                ],
            ]);
            if (is_wp_error($resp)) continue;

            $code = (int) wp_remote_retrieve_response_code($resp);
            if ($code < 200 || $code >= 300) continue;

            $body = (string) wp_remote_retrieve_body($resp);
            $data = json_decode($body, true);
            if (!is_array($data)) continue;

            $list = null;
            if (isset($data[0]) && is_array($data[0])) $list = $data;
            elseif (isset($data['data']) && is_array($data['data'])) $list = $data['data'];
            elseif (isset($data['items']) && is_array($data['items'])) $list = $data['items'];

            if (!is_array($list)) continue;

            foreach ($list as $row) {
                if (!is_array($row)) continue;
                $rowEmail = strtolower((string)($row['email'] ?? ''));
                if ($rowEmail === strtolower($email)) {
                    $id = $row['id'] ?? null;
                    if (is_numeric($id) && (int)$id > 0) return (int)$id;
                }
            }
        }
        return null;
    }

    private static function mailrelay_post_json(string $url, string $token, array $payload): array {
        $resp = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-AUTH-TOKEN' => $token,
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($resp)) return ['ok' => false, 'http_code' => 0, 'body' => $resp->get_error_message()];

        $code = (int) wp_remote_retrieve_response_code($resp);
        $body = (string) wp_remote_retrieve_body($resp);

        if ($code >= 200 && $code < 300) return ['ok' => true, 'http_code' => $code, 'body' => $body];

        if ($code === 422) {
            $data = json_decode($body, true);
            $emailErrors = $data['errors']['email'] ?? null;
            $text = is_array($emailErrors) ? implode(' ', array_map('strval', $emailErrors)) : (is_string($emailErrors) ? $emailErrors : '');
            if ($text && stripos($text, 'already exists') !== false) {
                return ['ok' => false, 'already_exists' => true, 'http_code' => $code, 'body' => $body];
            }
        }

        return ['ok' => false, 'http_code' => $code, 'body' => $body];
    }

    // ----- Insert with backward-compatible columns -----

    private static function store_consent_log_compatible(array $row): void {
        global $wpdb;
        $opts = self::get_options();
        $table = self::table_name();

        $cols = self::get_table_columns();
        if (!$cols) return;

        $ip = (string)($row['ip'] ?? '');
        $ip_hash = $ip ? hash_hmac('sha256', $ip, wp_salt('auth')) : null;
        $ip_raw = ($opts['hash_ip'] === '1') ? null : $ip;

        $data = [
            'email' => (string)$row['email'],
            'accepted' => (int)($row['accepted'] ?? 0),
            'accepted_at' => $row['accepted_at'] ?? null,
            'page_url' => $row['page_url'] ?? null,
            'ip_hash' => $ip_hash,
            'ip_raw' => $ip_raw,
            'user_agent' => (string)($row['user_agent'] ?? ''),
            'mailrelay_http_code' => isset($row['mailrelay_http_code']) ? (int)$row['mailrelay_http_code'] : null,
            'mailrelay_response' => isset($row['mailrelay_response']) ? (string)$row['mailrelay_response'] : null,
            'confirmation_requested_at' => $row['confirmation_requested_at'] ?? null,
            'confirmation_http_code' => isset($row['confirmation_http_code']) ? (int)$row['confirmation_http_code'] : null,
            'confirmation_response' => isset($row['confirmation_response']) ? (string)$row['confirmation_response'] : null,
            'created_at' => current_time('mysql'),
        ];

        // keep only existing columns
        $filtered = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $cols, true)) $filtered[$k] = $v;
        }

        $wpdb->insert($table, $filtered);
    }

    private static function current_url(): string {
        $scheme = is_ssl() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri  = $_SERVER['REQUEST_URI'] ?? '';
        return esc_url_raw($scheme . '://' . $host . $uri);
    }

    private static function get_client_ip(): string {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        return preg_replace('/[^0-9a-fA-F\.:]/', '', $ip);
    }
}

class UVE_MR_Newsletter_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'uve_mr_newsletter_widget',
            'Uve Mailrelay Newsletter',
            ['description' => 'Newsletter form con Turnstile + Mailrelay API + double opt-in (mensaje neutro) y logs.']
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'] ?? '';
        $opts = UVE_Mailrelay_Newsletter::get_options();

        $form_args = [
            'title' => $instance['title'] ?? $opts['title'],
            'description' => $instance['description'] ?? $opts['description'],
            'email_placeholder' => $instance['email_placeholder'] ?? $opts['email_placeholder'],
            'submit_label' => $instance['submit_label'] ?? $opts['submit_label'],
            'group_ids' => $instance['group_ids'] ?? $opts['group_ids'],
            'privacy_url' => $instance['privacy_url'] ?? $opts['privacy_url'],
            'consent_label' => $instance['consent_label'] ?? $opts['consent_label'],
            'class' => $instance['class'] ?? '',
        ];

        echo UVE_Mailrelay_Newsletter::shortcode($form_args);
        echo $args['after_widget'] ?? '';
    }

    public function form($instance) {
        $opts = UVE_Mailrelay_Newsletter::get_options();
        $title = esc_attr($instance['title'] ?? $opts['title']);
        $description = esc_attr($instance['description'] ?? $opts['description']);
        $email_placeholder = esc_attr($instance['email_placeholder'] ?? $opts['email_placeholder']);
        $submit_label = esc_attr($instance['submit_label'] ?? $opts['submit_label']);
        $group_ids = esc_attr($instance['group_ids'] ?? $opts['group_ids']);
        $privacy_url = esc_attr($instance['privacy_url'] ?? $opts['privacy_url']);
        $consent_label = esc_attr($instance['consent_label'] ?? $opts['consent_label']);
        $class = esc_attr($instance['class'] ?? '');
        ?>
        <p><label>Título
            <input class="widefat" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>">
        </label></p>
        <p><label>Descripción
            <input class="widefat" name="<?php echo $this->get_field_name('description'); ?>" type="text" value="<?php echo $description; ?>">
        </label></p>
        <p><label>Placeholder email
            <input class="widefat" name="<?php echo $this->get_field_name('email_placeholder'); ?>" type="text" value="<?php echo $email_placeholder; ?>">
        </label></p>
        <p><label>Texto botón
            <input class="widefat" name="<?php echo $this->get_field_name('submit_label'); ?>" type="text" value="<?php echo $submit_label; ?>">
        </label></p>
        <p><label>Group IDs
            <input class="widefat" name="<?php echo $this->get_field_name('group_ids'); ?>" type="text" value="<?php echo $group_ids; ?>">
        </label></p>
        <p><label>URL privacidad
            <input class="widefat" name="<?php echo $this->get_field_name('privacy_url'); ?>" type="text" value="<?php echo $privacy_url; ?>">
        </label></p>
        <p><label>Texto consentimiento
            <input class="widefat" name="<?php echo $this->get_field_name('consent_label'); ?>" type="text" value="<?php echo $consent_label; ?>">
        </label></p>
        <p><label>Clase CSS extra (opcional)
            <input class="widefat" name="<?php echo $this->get_field_name('class'); ?>" type="text" value="<?php echo $class; ?>">
        </label></p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        $instance['description'] = sanitize_text_field($new_instance['description'] ?? '');
        $instance['email_placeholder'] = sanitize_text_field($new_instance['email_placeholder'] ?? '');
        $instance['submit_label'] = sanitize_text_field($new_instance['submit_label'] ?? '');
        $instance['group_ids'] = sanitize_text_field($new_instance['group_ids'] ?? '');
        $instance['privacy_url'] = esc_url_raw($new_instance['privacy_url'] ?? '');
        $instance['consent_label'] = sanitize_text_field($new_instance['consent_label'] ?? '');
        $instance['class'] = sanitize_text_field($new_instance['class'] ?? '');
        return $instance;
    }
}

UVE_Mailrelay_Newsletter::init();
