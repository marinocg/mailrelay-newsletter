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
	 * Get a safe page URL for logging.
	 *
	 * @param array $data Request data.
	 * @return string
	 */
	public static function safe_page_url_from_request( array $data ): string {
		$home_url  = home_url( '/' );
		$home_host = wp_parse_url( $home_url, PHP_URL_HOST );

		$candidate = sanitize_text_field( (string) wp_unslash( $data['uve_mr_page_url'] ?? '' ) );
		if ( $candidate ) {
			$c_host = wp_parse_url( $candidate, PHP_URL_HOST );
			if ( $c_host && $home_host && strtolower( (string) $c_host ) === strtolower( (string) $home_host ) ) {
				return esc_url_raw( $candidate );
			}
		}

		$ref = wp_get_referer();
		if ( $ref ) {
			$ref_host = wp_parse_url( $ref, PHP_URL_HOST );
			if ( $ref_host && $home_host && strtolower( (string) $ref_host ) === strtolower( (string) $home_host ) ) {
				return esc_url_raw( $ref );
			}
		}

		return $home_url;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	public static function get_client_ip(): string {
		$ip       = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$filtered = preg_replace( '/[^0-9a-fA-F\.:]/', '', $ip );
		if ( ! $filtered || false === filter_var( $filtered, FILTER_VALIDATE_IP ) ) {
			return '';
		}
		return $filtered;
	}

	/**
	 * Normalize common mojibake issues to UTF-8.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	public static function normalize_text( string $text ): string {
		if ( '' === $text ) {
			return $text;
		}

		$needs_fix = false;
		foreach ( array( 'Ã', 'Â', 'â' ) as $needle ) {
			if ( false !== strpos( $text, $needle ) ) {
				$needs_fix = true;
				break;
			}
		}

		if ( ! $needs_fix ) {
			return $text;
		}

		$fixed = $text;
		if ( function_exists( 'iconv' ) ) {
			$converted = iconv( 'ISO-8859-1', 'UTF-8//IGNORE', $text );
			if ( false !== $converted && '' !== $converted ) {
				$fixed = $converted;
			}
		}

		return $fixed;
	}
}
