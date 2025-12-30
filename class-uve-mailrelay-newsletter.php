<?php
/**
 * Plugin Name: MR4WP
 * Description: Widget + shortcode newsletter with Cloudflare Turnstile and Mailrelay official API. Uses inactive + resend_confirmation_email for double opt-in. Neutral success message to prevent email enumeration. GDPR consent log with retention and confirmation-send logging.
 * Version: 1.6.2
 * Author: Uve / Custom
 * License: GPLv3 or later
 * Text Domain: uve-mailrelay-newsletter
 * Domain Path: /languages
 *
 * @package UVE_Mailrelay_Newsletter
 * @phpcsSuppress WordPress.Files.FileName.InvalidClassFileName
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-uve-mr-utils.php';
require_once __DIR__ . '/includes/class-uve-mr-turnstile.php';
require_once __DIR__ . '/includes/class-uve-mr-logs.php';
require_once __DIR__ . '/includes/class-uve-mr-mailrelay.php';
require_once __DIR__ . '/includes/class-uve-mr-frontend.php';
require_once __DIR__ . '/includes/class-uve-mr-admin.php';
require_once __DIR__ . '/includes/class-uve-mr-submit.php';
require_once __DIR__ . '/includes/class-uve-mr-widgets.php';
require_once __DIR__ . '/includes/class-uve-mr-elementor.php';
require_once __DIR__ . '/includes/class-uve-mr-newsletter-widget.php';

/**
 * Main plugin class.
 */
final class UVE_Mailrelay_Newsletter {

	const OPT_KEY     = 'uve_mr_newsletter_options';
	const TABLE       = 'uve_mr_newsletter_consent';
	const NONCE       = 'uve_mr_subscribe_nonce';
	const CRON_PURGE  = 'uve_mr_newsletter_purge_logs';
	const VERSION     = '1.6.2';
	const TEXT_DOMAIN = 'uve-mailrelay-newsletter';

	/**
	 * Register hooks and handlers.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'init', array( 'UVE_MR_Frontend', 'register_shortcode' ) );
		add_action( 'widgets_init', array( 'UVE_MR_Widgets', 'register_widget' ) );
		add_action( 'elementor/widgets/register', array( 'UVE_MR_Elementor', 'register_elementor_widget' ) );
		add_action( 'elementor/elements/categories_registered', array( 'UVE_MR_Elementor', 'register_elementor_category' ) );
		add_action( 'admin_menu', array( 'UVE_MR_Admin', 'admin_menu' ) );
		add_action( 'admin_init', array( 'UVE_MR_Admin', 'admin_init' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'add_settings_link' ) );

		add_action( 'admin_post_nopriv_uve_mr_subscribe', array( 'UVE_MR_Submit', 'handle_submit' ) );
		add_action( 'admin_post_uve_mr_subscribe', array( 'UVE_MR_Submit', 'handle_submit' ) );

		add_action( self::CRON_PURGE, array( 'UVE_MR_Logs', 'purge_old_logs_cron' ) );

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
			'title'                         => __( 'Newsletter', 'uve-mailrelay-newsletter' ),
			'description'                   => __( 'Subscribe and stay up to date with our news', 'uve-mailrelay-newsletter' ),
			'email_placeholder'             => __( 'Email...', 'uve-mailrelay-newsletter' ),
			'submit_label'                  => __( 'Subscribe', 'uve-mailrelay-newsletter' ),

			// GDPR.
			'privacy_url'                   => '',
			'consent_label'                 => __( 'I agree to receive the newsletter and have read the privacy policy', 'uve-mailrelay-newsletter' ),
			'store_consent_log'             => '1', // String flag (1 or 0).
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
		UVE_MR_Logs::maybe_create_or_update_table();
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
			'uve-mailrelay-newsletter',
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
		$url           = admin_url( 'options-general.php?page=uve-mr-newsletter' );
		$settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'uve-mailrelay-newsletter' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Backwards compatible shortcode entry point.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts = array() ): string {
		return UVE_MR_Frontend::shortcode( $atts );
	}
}

UVE_Mailrelay_Newsletter::init();
