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
	 * Supported Mailrelay locales.
	 *
	 * @return string[]
	 */
	public static function supported_locales(): array {
		return array( 'en', 'es', 'pt-BR', 'fr', 'gl', 'ca', 'eu', 'it' );
	}

	/**
	 * Map locales to human-friendly labels.
	 *
	 * @return array<string,string>
	 */
	public static function locale_labels(): array {
		return array(
			'en'    => __( 'English', 'uve-mailrelay-newsletter' ),
			'es'    => __( 'Spanish', 'uve-mailrelay-newsletter' ),
			'pt-BR' => __( 'Portuguese (Brazil)', 'uve-mailrelay-newsletter' ),
			'fr'    => __( 'French', 'uve-mailrelay-newsletter' ),
			'gl'    => __( 'Galician', 'uve-mailrelay-newsletter' ),
			'ca'    => __( 'Catalan', 'uve-mailrelay-newsletter' ),
			'eu'    => __( 'Basque', 'uve-mailrelay-newsletter' ),
			'it'    => __( 'Italian', 'uve-mailrelay-newsletter' ),
		);
	}

	/**
	 * Normalize a locale to Mailrelay supported values.
	 *
	 * @param string $locale Raw locale.
	 * @return string
	 */
	public static function normalize_locale( string $locale ): string {
		$locale = trim( $locale );
		if ( '' === $locale ) {
			return '';
		}
		$locale = str_replace( '_', '-', $locale );
		$needle = strtolower( $locale );
		foreach ( self::supported_locales() as $supported ) {
			$supported_lower = strtolower( $supported );
			if ( $needle === $supported_lower ) {
				return $supported;
			}
			if ( 2 === strlen( $supported_lower ) && str_starts_with( $needle, $supported_lower . '-' ) ) {
				return $supported;
			}
		}
		return '';
	}

	/**
	 * Resolve a locale from an Accept-Language header.
	 *
	 * @param string $header Accept-Language header.
	 * @return string
	 */
	public static function locale_from_accept_language( string $header ): string {
		$header = trim( $header );
		if ( '' === $header ) {
			return '';
		}
		$candidates = array();
		foreach ( explode( ',', $header ) as $part ) {
			$part = trim( $part );
			if ( '' === $part ) {
				continue;
			}
			$quality = 1.0;
			if ( false !== strpos( $part, ';' ) ) {
				$bits = array_map( 'trim', explode( ';', $part ) );
				$part = $bits[0] ?? '';
				foreach ( $bits as $bit ) {
					if ( 0 === strpos( $bit, 'q=' ) ) {
						$quality = (float) substr( $bit, 2 );
					}
				}
			}
			if ( '' === $part ) {
				continue;
			}
			$candidates[] = array(
				'locale'  => $part,
				'quality' => $quality,
			);
		}
		if ( empty( $candidates ) ) {
			return '';
		}
		usort(
			$candidates,
			static function ( array $a, array $b ): int {
				if ( $a['quality'] === $b['quality'] ) {
					return 0;
				}
				return ( $a['quality'] < $b['quality'] ) ? 1 : -1;
			}
		);
		foreach ( $candidates as $candidate ) {
			$normalized = self::normalize_locale( (string) ( $candidate['locale'] ?? '' ) );
			if ( '' !== $normalized ) {
				return $normalized;
			}
		}
		return '';
	}

	/**
	 * Resolve the default locale fallback.
	 *
	 * @return string
	 */
	public static function default_locale_fallback(): string {
		$wp_locale  = function_exists( 'get_locale' ) ? (string) get_locale() : '';
		$normalized = self::normalize_locale( $wp_locale );
		return '' !== $normalized ? $normalized : 'en';
	}

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
		$home_host = self::extract_host( $home_url );

		$raw_url = $data['uve_mr_page_url'] ?? '';
		if ( is_array( $raw_url ) ) {
			$raw_url = '';
		}
		$candidate = sanitize_text_field( is_scalar( $raw_url ) ? wp_unslash( (string) $raw_url ) : '' );
		if ( $candidate ) {
			$c_host = self::extract_host( $candidate );
			if ( '' !== $c_host && '' !== $home_host && strtolower( $c_host ) === strtolower( $home_host ) ) {
				return esc_url_raw( $candidate );
			}
		}

		$ref = wp_get_referer();
		if ( $ref ) {
			$ref_host = self::extract_host( $ref );
			if ( '' !== $ref_host && '' !== $home_host && strtolower( $ref_host ) === strtolower( $home_host ) ) {
				return esc_url_raw( $ref );
			}
		}

		return $home_url;
	}

	/**
	 * Extract host from a URL using wp_parse_url.
	 *
	 * @param string $url URL string.
	 * @return string
	 */
	private static function extract_host( string $url ): string {
		$parsed = wp_parse_url( $url );
		if ( is_array( $parsed ) ) {
			return (string) ( $parsed['host'] ?? '' );
		}
		return '';
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	public static function get_client_ip(): string {
		$raw_candidates = array();

		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && ! empty( $_SERVER['HTTP_CF_RAY'] ) ) {
			$raw_candidates[] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		}

		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$xff = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			foreach ( explode( ',', $xff ) as $part ) {
				$raw_candidates[] = $part;
			}
		}

		if ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$raw_candidates[] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$raw_candidates[] = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		$candidates = array();
		foreach ( $raw_candidates as $raw ) {
			$ip = trim( (string) $raw );
			$ip = preg_replace( '/[^0-9a-fA-F\.:]/', '', $ip );
			if ( ! $ip || false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				continue;
			}
			$candidates[] = $ip;
		}

		foreach ( $candidates as $candidate ) {
			if ( false !== filter_var( $candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return $candidate;
			}
		}

		return $candidates[0] ?? '';
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

	/**
	 * Check whether the premium plugin is installed.
	 *
	 * @return bool
	 */
	public static function is_premium_installed(): bool {
		$class = (string) apply_filters( 'uve_mr_premium_class', 'UVE_MR_Premium' );
		return '' !== $class && class_exists( $class );
	}

	/**
	 * Apply a filter only when the premium plugin is installed.
	 *
	 * @param string $hook Filter name.
	 * @param mixed  $value Default value to filter.
	 * @param mixed  $default_if_missing Default value when premium is not installed.
	 * @return mixed
	 */
	public static function premium_filter( string $hook, $value, $default_if_missing = null ) {
		if ( ! self::is_premium_installed() ) {
			return null === $default_if_missing ? $value : $default_if_missing;
		}

		return apply_filters( $hook, $value );
	}

	/**
	 * Get the main plugin file path.
	 *
	 * @return string
	 */
	public static function plugin_file(): string {
		return __DIR__ . '/../class-uve-mailrelay-newsletter.php';
	}
}
