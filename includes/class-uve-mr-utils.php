<?php
/**
 * Utility helpers.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility helpers.
 */
final class UVE_MR_Utils {

	/**
	 * Build a safe back URL with extra query args.
	 *
	 * @param array $add_query Query arguments to add.
	 * @return string
	 */
	public static function safe_back_url( array $add_query ): string {
		$ref = wp_get_referer();
		if ( ! $ref ) {
			$ref = home_url( '/' );
		}
		$url = remove_query_arg( array_keys( $add_query ), $ref );
		return add_query_arg( $add_query, $url );
	}

	/**
	 * Parse comma-separated group IDs.
	 *
	 * @param string $raw Raw group IDs string.
	 * @return array
	 */
	public static function parse_group_ids( string $raw ): array {
		$parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
		$ids   = array();
		foreach ( $parts as $p ) {
			$ids[] = (int) $p;
		}
		$ids = array_filter( $ids, fn( $v ) => $v > 0 );
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Get the current URL.
	 *
	 * @return string
	 */
	public static function current_url(): string {
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );
		$uri    = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		return esc_url_raw( $scheme . '://' . $host . $uri );
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	public static function get_client_ip(): string {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		return preg_replace( '/[^0-9a-fA-F\.:]/', '', $ip );
	}
}
