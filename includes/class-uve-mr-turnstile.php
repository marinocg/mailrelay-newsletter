<?php
/**
 * Turnstile verification helpers.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turnstile verification helpers.
 */
final class UVE_MR_Turnstile {

	/**
	 * Determine if Turnstile is configured.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return '' !== self::get_site_key() && '' !== self::get_secret_key();
	}

	/**
	 * Get the Turnstile site key.
	 *
	 * @return string
	 */
	public static function get_site_key(): string {
		if ( defined( 'CF_TURNSTILE_SITE_KEY' ) && CF_TURNSTILE_SITE_KEY ) {
			return (string) CF_TURNSTILE_SITE_KEY;
		}
		$opts = UVE_Mailrelay_Newsletter::get_options();
		return (string) ( $opts['turnstile_site_key'] ?? '' );
	}

	/**
	 * Get the Turnstile secret key.
	 *
	 * @return string
	 */
	public static function get_secret_key(): string {
		if ( defined( 'CF_TURNSTILE_SECRET_KEY' ) && CF_TURNSTILE_SECRET_KEY ) {
			return (string) CF_TURNSTILE_SECRET_KEY;
		}
		$opts = UVE_Mailrelay_Newsletter::get_options();
		return (string) ( $opts['turnstile_secret_key'] ?? '' );
	}

	/**
	 * Verify a Turnstile response.
	 *
	 * @param string $token Token from the client.
	 * @param string $ip Client IP address.
	 * @return bool
	 */
	public static function verify( string $token, string $ip ): bool {
		$secret = self::get_secret_key();
		if ( ! $secret || ! $token ) {
			return false;
		}

		$resp = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 10,
				'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
				'body'    => array(
					'secret'   => $secret,
					'response' => $token,
					'remoteip' => $ip,
				),
			)
		);

		if ( is_wp_error( $resp ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $resp );
		$body = wp_remote_retrieve_body( $resp );
		if ( 200 > $code || 300 <= $code ) {
			return false;
		}

		$data = json_decode( $body, true );
		return is_array( $data ) && ! empty( $data['success'] );
	}
}
