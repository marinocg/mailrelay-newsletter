<?php
/**
 * Plugin Name: RelayPress
 * Description: Widget + shortcode newsletter with Cloudflare Turnstile and Mailrelay official API. Uses inactive + resend_confirmation_email for double opt-in. Neutral success message to prevent email enumeration. GDPR consent log with retention and confirmation-send logging.
 * Version: 1.8.0
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * Author: Uve / Custom
 * License: GPLv3 or later
 * Text Domain: relaypress-newsletter
 * Domain Path: /languages
 *
 * @package RelayPress_Newsletter
 * @phpcsSuppress WordPress.Files.FileName.InvalidClassFileName
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-relaypress-utils.php';
require_once __DIR__ . '/includes/class-relaypress-phone-normalizer.php';
require_once __DIR__ . '/includes/class-relaypress-turnstile.php';
require_once __DIR__ . '/includes/class-relaypress-logs.php';
require_once __DIR__ . '/includes/ports/newsletter/interface-relaypress-mailrelay-client.php';
require_once __DIR__ . '/includes/ports/newsletter/interface-relaypress-options-repository.php';
require_once __DIR__ . '/includes/ports/newsletter/interface-relaypress-logs-repository.php';
require_once __DIR__ . '/includes/ports/newsletter/interface-relaypress-turnstile-verifier.php';
require_once __DIR__ . '/includes/ports/newsletter/interface-relaypress-rate-limiter.php';
require_once __DIR__ . '/includes/ports/newsletter/interface-relaypress-request-context.php';
require_once __DIR__ . '/includes/ports/newsletter/interface-relaypress-input-sanitizer.php';
require_once __DIR__ . '/includes/adapters/newsletter/class-relaypress-wp-mailrelay-client.php';
require_once __DIR__ . '/includes/adapters/newsletter/class-relaypress-wp-options-repository.php';
require_once __DIR__ . '/includes/adapters/newsletter/class-relaypress-wp-logs-repository.php';
require_once __DIR__ . '/includes/adapters/newsletter/class-relaypress-wp-turnstile-verifier.php';
require_once __DIR__ . '/includes/adapters/newsletter/class-relaypress-wp-rate-limiter.php';
require_once __DIR__ . '/includes/adapters/newsletter/class-relaypress-wp-request-context.php';
require_once __DIR__ . '/includes/adapters/newsletter/class-relaypress-wp-input-sanitizer.php';
require_once __DIR__ . '/includes/use-cases/newsletter/class-relaypress-submit-use-case.php';
require_once __DIR__ . '/includes/class-relaypress-container.php';
require_once __DIR__ . '/includes/class-relaypress-frontend.php';
require_once __DIR__ . '/includes/class-relaypress-admin.php';
require_once __DIR__ . '/includes/admin/class-relaypress-upgrade-admin.php';
require_once __DIR__ . '/includes/domain/forms/class-relaypress-form.php';
require_once __DIR__ . '/includes/domain/forms/class-relaypress-form-config.php';
require_once __DIR__ . '/includes/ports/forms/interface-relaypress-form-repository.php';
require_once __DIR__ . '/includes/adapters/forms/class-relaypress-single-form-repository.php';
require_once __DIR__ . '/includes/adapters/forms/class-relaypress-form-repository.php';
require_once __DIR__ . '/includes/use-cases/forms/class-relaypress-form-use-cases.php';
require_once __DIR__ . '/includes/domain/forms/class-relaypress-forms.php';
require_once __DIR__ . '/includes/class-relaypress-submit.php';
require_once __DIR__ . '/includes/class-relaypress-widgets.php';
require_once __DIR__ . '/includes/class-relaypress-elementor.php';
require_once __DIR__ . '/includes/class-relaypress-gutenberg.php';
require_once __DIR__ . '/includes/class-relaypress-newsletter-widget.php';

/**
 * Main plugin class.
 */
final class RelayPress_Newsletter {

	const OPT_KEY     = 'relaypress_newsletter_options';
	const TABLE       = 'relaypress_newsletter_consent';
	const NONCE       = 'relaypress_subscribe_nonce';
	const CRON_PURGE  = 'relaypress_newsletter_purge_logs';
	const VERSION     = '1.8.0';
	const TEXT_DOMAIN = 'relaypress-newsletter';

	/**
	 * Register hooks and handlers.
	 *
	 * @return void
	 */
	public static function init(): void {
		RelayPress_Logs::maybe_migrate_legacy_table();
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'init', array( 'RelayPress_Forms', 'register_post_type' ) );
		add_action( 'init', array( 'RelayPress_Forms', 'maybe_migrate_default_form' ) );
		add_action( 'init', array( 'RelayPress_Frontend', 'register_shortcode' ) );
		add_action( 'init', array( 'RelayPress_Gutenberg', 'register_block' ) );
		add_action( 'widgets_init', array( 'RelayPress_Widgets', 'register_widget' ) );
		add_action( 'elementor/widgets/register', array( 'RelayPress_Elementor', 'register_elementor_widget' ) );
		add_action( 'elementor/elements/categories_registered', array( 'RelayPress_Elementor', 'register_elementor_category' ) );
		add_action( 'relaypress_elementor_register_controls', array( 'RelayPress_Elementor', 'register_form_controls' ), 10, 2 );
		add_action( 'admin_menu', array( 'RelayPress_Admin', 'admin_menu' ) );
		add_action( 'admin_menu', array( 'RelayPress_Admin', 'reorder_submenu' ), 999 );
		add_action( 'admin_init', array( 'RelayPress_Admin', 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( 'RelayPress_Admin', 'admin_enqueue' ) );
		add_action( 'admin_menu', array( 'RelayPress_Upgrade_Admin', 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( 'RelayPress_Upgrade_Admin', 'admin_enqueue' ) );
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			require_once __DIR__ . '/includes/admin/forms/class-relaypress-forms-table.php';
			require_once __DIR__ . '/includes/admin/forms/class-relaypress-forms-list-page.php';
			require_once __DIR__ . '/includes/admin/forms/class-relaypress-forms-editor-page.php';
			require_once __DIR__ . '/includes/admin/forms/class-relaypress-forms-admin.php';
			add_action( 'admin_menu', array( 'RelayPress_Forms_Admin', 'admin_menu' ) );
			add_action( 'admin_init', array( 'RelayPress_Forms_Admin', 'admin_init' ) );
			add_action( 'admin_enqueue_scripts', array( 'RelayPress_Forms_Admin', 'admin_enqueue' ) );
		}
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'add_settings_link' ) );

		add_action( 'admin_post_nopriv_relaypress_subscribe', array( 'RelayPress_Submit', 'handle_submit' ) );
		add_action( 'admin_post_relaypress_subscribe', array( 'RelayPress_Submit', 'handle_submit' ) );
		add_action( 'wp_ajax_nopriv_relaypress_subscribe_ajax', array( 'RelayPress_Submit', 'handle_submit_ajax' ) );
		add_action( 'wp_ajax_relaypress_subscribe_ajax', array( 'RelayPress_Submit', 'handle_submit_ajax' ) );

		add_action( self::CRON_PURGE, array( 'RelayPress_Logs', 'purge_old_logs_cron' ) );

		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );
	}

	/**
	 * Default options.
	 *
	 * @return array
	 */
	public static function defaults(): array {
		return array(
			// Mailrelay.
			'api_base_url'                  => '', // e.g. https://YOURACCOUNT.ipzmarketing.com/api/v1.
			'api_token'                     => '',
			'group_ids'                     => '1', // comma-separated.
			'subscriber_status'             => 'inactive', // inactive (double opt-in) / active (single opt-in).

			// Turnstile (optional if you define CF_TURNSTILE_* constants).
			'turnstile_site_key'            => '',
			'turnstile_secret_key'          => '',

			// UI texts.
			'title'                         => __( 'Newsletter', 'relaypress-newsletter' ),
			'description'                   => __( 'Subscribe and stay up to date with our news', 'relaypress-newsletter' ),
			'email_placeholder'             => __( 'Email...', 'relaypress-newsletter' ),
			'submit_label'                  => __( 'Subscribe', 'relaypress-newsletter' ),
			'ajax_mode'                     => '0',

			// Locale fallback (Mailrelay).
			'locale_fallback'               => RelayPress_Utils::default_locale_fallback(),
			'locale_mode'                   => 'browser',
			'locale_force'                  => RelayPress_Utils::default_locale_fallback(),

			// Phone normalization.
			'default_phone_country'         => '',
			'send_raw_phone_on_fail'        => '0',
			'hide_phone_prefix_selector'    => '0',

			// GDPR.
			'privacy_url'                   => '',
			'consent_label'                 => __( 'I agree to receive the newsletter and have read the privacy policy', 'relaypress-newsletter' ),
			'store_consent_log'             => '1', // String flag (1 or 0).
			'log_phone_raw'                 => '0',
			'hash_ip'                       => '1',            // store hashed IP by default.
			'retention_days'                => 180,     // purge logs older than N days.

			// Rate limit.
			'rate_limit_max'                => 5,
			'rate_limit_window_seconds'     => 3600,

			// Confirmation resend anti-abuse (per IP+email).
			'confirm_resend_max'            => 2,
			'confirm_resend_window_seconds' => 3600,
		);
	}

	/**
	 * Fetch merged options.
	 *
	 * @return array
	 */
	public static function get_options(): array {
		$opts = get_option( self::OPT_KEY, array() );
		return wp_parse_args( is_array( $opts ) ? $opts : array(), self::defaults() );
	}

	/**
	 * Activation hook.
	 *
	 * @return void
	 */
	public static function activate(): void {
		RelayPress_Logs::maybe_create_or_update_table();
		if ( ! get_option( self::OPT_KEY ) ) {
			add_option( self::OPT_KEY, self::defaults() );
		}

		if ( ! wp_next_scheduled( self::CRON_PURGE ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_PURGE );
		}
	}

	/**
	 * Deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		$ts = wp_next_scheduled( self::CRON_PURGE );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::CRON_PURGE );
		}
	}

	/**
	 * Load plugin translations.
	 *
	 * @return void
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain(
			'relaypress-newsletter',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Add settings link on Plugins list.
	 *
	 * @param array $links Existing plugin links.
	 * @return array
	 */
	public static function add_settings_link( array $links ): array {
		$url           = admin_url( 'options-general.php?page=relaypress-newsletter' );
		$settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'relaypress-newsletter' ) . '</a>';
		array_unshift( $links, $settings_link );
		$show_upgrade = (bool) apply_filters( 'relaypress_show_upgrade_ui', true );
		if ( $show_upgrade ) {
			$upgrade_url  = admin_url( 'admin.php?page=relaypress-newsletter-upgrade' );
			$upgrade_link = '<a href="' . esc_url( $upgrade_url ) . '" style="color:#d63638;font-weight:600;">' . esc_html__( 'Upgrade to Premium', 'relaypress-newsletter' ) . '</a>';
			$links[]      = $upgrade_link;
		}
		return $links;
	}

	/**
	 * Backwards compatible shortcode entry point.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts = array() ): string {
		return RelayPress_Frontend::shortcode( $atts );
	}
}

RelayPress_Newsletter::init();
