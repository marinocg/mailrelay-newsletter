<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

class WP_Error {
	private string $message;

	public function __construct( string $message ) {
		$this->message = $message;
	}

	public function get_error_message(): string {
		return $this->message;
	}
}

class WPDB_Mock {
	public string $prefix = 'wp_';

	public function prepare( string $query, ...$args ): string {
		$flat_args = array();
		foreach ( $args as $arg ) {
			if ( is_array( $arg ) ) {
				foreach ( $arg as $inner ) {
					$flat_args[] = $inner;
				}
				continue;
			}
			$flat_args[] = $arg;
		}
		foreach ( $flat_args as $arg ) {
			$query = preg_replace( '/%s/', (string) $arg, $query, 1 );
			$query = preg_replace( '/%i/', (string) $arg, $query, 1 );
		}
		return $query;
	}

	public function get_charset_collate(): string {
		return 'utf8mb4';
	}

	public function get_var( string $query ) {
		if ( false !== stripos( $query, 'SHOW TABLES LIKE' ) ) {
			return $GLOBALS['uve_mr_test_wpdb_tables_like'] ?? null;
		}
		if ( false !== stripos( $query, 'COUNT(' ) ) {
			return $GLOBALS['uve_mr_test_wpdb_count'] ?? 0;
		}
		return $GLOBALS['uve_mr_test_wpdb_get_var'] ?? null;
	}

	public function get_results( string $query, $output = null ) {
		if ( false !== stripos( $query, 'SHOW COLUMNS FROM' ) ) {
			return $GLOBALS['uve_mr_test_wpdb_columns'] ?? array();
		}
		return $GLOBALS['uve_mr_test_wpdb_get_results'] ?? array();
	}

	public function query( string $query ) {
		return $GLOBALS['uve_mr_test_wpdb_query'] ?? 0;
	}

	public function insert( string $table, array $data ) {
		$GLOBALS['uve_mr_test_wpdb_insert'][] = array(
			'table' => $table,
			'data'  => $data,
		);
		return 1;
	}
}

class WP_Widget {
	public function __construct( $id_base = '', $name = '', $widget_options = array(), $control_options = array() ) {}
	public function get_field_name( string $field_name ): string {
		return $field_name;
	}
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	class WP_List_Table {
		protected array $_args = array();

		public function __construct( array $args = array() ) {
			$this->_args = $args;
		}

		protected function row_actions( array $actions, bool $always_visible = false ): string {
			if ( empty( $actions ) ) {
				return '';
			}
			return ' ' . implode( ' ', $actions );
		}
	}
}

$GLOBALS['wpdb'] = new WPDB_Mock();

function __( string $text, ?string $domain = null ): string {
	return $text;
}

function esc_html__( string $text, ?string $domain = null ): string {
	return $text;
}

function esc_attr__( string $text, ?string $domain = null ): string {
	return $text;
}

function esc_html( string $text ): string {
	return $text;
}

function esc_attr( string $text ): string {
	return $text;
}

function esc_sql( string $text ): string {
	return $text;
}

function esc_url_raw( string $url ): string {
	return $url;
}

function esc_url( string $url ): string {
	return $url;
}

function wp_kses_post( string $html ): string {
	return $html;
}

function sanitize_text_field( $text ): string {
	return is_string( $text ) ? trim( $text ) : '';
}

function sanitize_email( string $email ): string {
	return trim( $email );
}

function is_email( string $email ): bool {
	return false !== filter_var( $email, FILTER_VALIDATE_EMAIL );
}

function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}
function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
	$GLOBALS['uve_mr_test_filters'][ $hook ][] = $callback;
}
function apply_filters( string $hook, $value, ...$args ) {
	$filters = $GLOBALS['uve_mr_test_filters'][ $hook ] ?? array();
	foreach ( $filters as $callback ) {
		if ( is_callable( $callback ) ) {
			$value = $callback( $value, ...$args );
		}
	}
	return $value;
}
function add_shortcode( string $tag, $callback ): void {}
function shortcode_atts( array $pairs, $atts ): array {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}
	return array_merge( $pairs, $atts );
}
function register_widget( string $class ): void {}
function add_options_page( string $page_title, string $menu_title, string $capability, string $menu_slug, $callback ): void {}
function add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, $callback, string $icon_url = '', $position = null ): void {
	$GLOBALS['uve_mr_test_menu_pages'][] = array(
		'page_title' => $page_title,
		'menu_title' => $menu_title,
		'capability' => $capability,
		'menu_slug'  => $menu_slug,
		'callback'   => $callback,
		'icon_url'   => $icon_url,
		'position'   => $position,
	);
}
function add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $callback ): void {
	$GLOBALS['uve_mr_test_submenu_pages'][] = array(
		'parent_slug' => $parent_slug,
		'page_title'  => $page_title,
		'menu_title'  => $menu_title,
		'capability'  => $capability,
		'menu_slug'   => $menu_slug,
		'callback'    => $callback,
	);
}
function register_setting( string $group, string $name, array $args = array() ): void {}
function register_activation_hook( string $file, $callback ): void {}
function register_deactivation_hook( string $file, $callback ): void {}
function wp_script_is( string $handle, string $status = '' ): bool { return false; }
function wp_enqueue_script( string $handle, string $src = '', array $deps = array(), $ver = false, $in_footer = false ): void {}
function load_textdomain( string $domain, string $mofile ): bool { return true; }
function load_plugin_textdomain( string $domain, bool $deprecated = false, string $plugin_rel_path = '' ): bool { return true; }
function determine_locale(): string { return 'en_US'; }
function get_locale(): string { return 'en_US'; }
function wp_verify_nonce( string $nonce, string $action ): bool {
	return $GLOBALS['uve_mr_test_nonce_ok'] ?? true;
}
function wp_create_nonce( string $action ): string {
	return 'testnonce';
}
function wp_next_scheduled( string $hook ) {
	return false;
}
function wp_schedule_event( int $timestamp, string $recurrence, string $hook ): void {}
function wp_unschedule_event( int $timestamp, string $hook ): void {}
function wp_nonce_field( string $action, string $name = '_wpnonce' ): void {}
function check_admin_referer( string $action ): bool {
	return true; }

function wp_get_referer(): string {
	return $GLOBALS['uve_mr_test_referer'] ?? '';
}

function home_url( string $path = '/' ): string {
	return 'https://example.test' . $path;
}

function remove_query_arg( $keys, string $url ): string {
	$parsed = wp_parse_url( $url );
	if ( ! $parsed ) {
		return $url;
	}
	$keys  = (array) $keys;
	$query = array();
	if ( ! empty( $parsed['query'] ) ) {
		parse_str( $parsed['query'], $query );
		foreach ( $keys as $key ) {
			unset( $query[ $key ] );
		}
	}
	$scheme   = $parsed['scheme'] ?? 'https';
	$host     = $parsed['host'] ?? '';
	$path     = $parsed['path'] ?? '';
	$qs       = $query ? '?' . http_build_query( $query ) : '';
	$fragment = isset( $parsed['fragment'] ) ? '#' . $parsed['fragment'] : '';
	return $scheme . '://' . $host . $path . $qs . $fragment;
}

function add_query_arg( array $args, string $url ): string {
	$parsed = wp_parse_url( $url );
	if ( ! $parsed ) {
		return $url;
	}
	$query = array();
	if ( ! empty( $parsed['query'] ) ) {
		parse_str( $parsed['query'], $query );
	}
	$query    = array_merge( $query, $args );
	$scheme   = $parsed['scheme'] ?? 'https';
	$host     = $parsed['host'] ?? '';
	$path     = $parsed['path'] ?? '';
	$qs       = $query ? '?' . http_build_query( $query ) : '';
	$fragment = isset( $parsed['fragment'] ) ? '#' . $parsed['fragment'] : '';
	return $scheme . '://' . $host . $path . $qs . $fragment;
}

function wp_parse_args( $args, $defaults ): array {
	if ( ! is_array( $args ) ) {
		$args = array();
	}
	return array_merge( $defaults, $args );
}

function is_ssl(): bool {
	return (bool) ( $GLOBALS['uve_mr_test_is_ssl'] ?? false );
}

function admin_url( string $path = '' ): string {
	return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
}

function wp_unslash( $value ) {
	return $value;
}

function get_option( string $key, $default = false ) {
	return $GLOBALS['uve_mr_test_options'][ $key ] ?? $default;
}

function add_option( string $key, $value ): void {
	$GLOBALS['uve_mr_test_options'][ $key ] = $value;
}

function wp_remote_post( string $url, array $args = array() ) {
	$GLOBALS['uve_mr_test_last_http'] = array(
		'method' => 'POST',
		'url'    => $url,
		'args'   => $args,
	);
	return $GLOBALS['uve_mr_test_http']['POST'][ $url ] ?? array(
		'response' => array( 'code' => 500 ),
		'body'     => '',
	);
}

function wp_remote_get( string $url, array $args = array() ) {
	$GLOBALS['uve_mr_test_last_http'] = array(
		'method' => 'GET',
		'url'    => $url,
		'args'   => $args,
	);
	return $GLOBALS['uve_mr_test_http']['GET'][ $url ] ?? array(
		'response' => array( 'code' => 500 ),
		'body'     => '',
	);
}

function wp_remote_retrieve_response_code( $response ): int {
	return (int) ( $response['response']['code'] ?? 0 );
}

function wp_remote_retrieve_body( $response ): string {
	return (string) ( $response['body'] ?? '' );
}

function is_wp_error( $thing ): bool {
	return $thing instanceof WP_Error;
}

function wp_json_encode( $value ): string {
	return json_encode( $value );
}

function wp_send_json_success( array $data ): void {
	echo wp_json_encode( array( 'success' => true, 'data' => $data ) );
	if ( empty( $GLOBALS['uve_mr_test_no_exit'] ) ) {
		exit;
	}
}

function wp_send_json_error( array $data ): void {
	echo wp_json_encode( array( 'success' => false, 'data' => $data ) );
	if ( empty( $GLOBALS['uve_mr_test_no_exit'] ) ) {
		exit;
	}
}

function get_transient( string $key ) {
	return $GLOBALS['uve_mr_test_transients'][ $key ] ?? 0;
}

function set_transient( string $key, $value, int $expiration ): bool {
	$GLOBALS['uve_mr_test_transients'][ $key ] = $value;
	return true;
}

function wp_parse_url( string $url ) {
	return parse_url( $url );
}

function wp_nonce_url( string $url, string $action ): string {
	return $url . '&_wpnonce=testnonce';
}

function date_i18n( string $format, int $timestamp ): string {
	return date( $format, $timestamp );
}

function locate_template( array $templates ) {
	return '';
}

function plugin_basename( string $file ): string {
	return basename( $file );
}

function current_time( string $type ): string {
	return '2025-01-01 00:00:00';
}

function wp_salt( string $scheme ): string {
	return 'test_salt';
}

function selected( $value, $current ): void {}
function checked( $value, $current ): void {}
function submit_button( string $text = 'Submit', string $type = 'primary', string $name = 'submit', bool $wrap = true, $other = array() ): void {
	echo '<button type="submit" class="button ' . esc_attr( $type ) . '">' . esc_html( $text ) . '</button>';
}
function sanitize_key( string $key ): string {
	return preg_replace( '/[^a-z0-9_]/', '', strtolower( $key ) );
}

require_once __DIR__ . '/../class-uve-mailrelay-newsletter.php';
