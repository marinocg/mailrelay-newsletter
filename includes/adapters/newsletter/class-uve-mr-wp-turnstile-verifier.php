<?php
/**
 * WordPress Turnstile adapter.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Turnstile verifier adapter.
 */
final class UVE_MR_WP_Turnstile_Verifier implements UVE_MR_Turnstile_Verifier {
	/**
	 * Verify a Turnstile token.
	 *
	 * @param string $token Token.
	 * @param string $ip Client IP.
	 * @return bool
	 */
	public function verify( string $token, string $ip ): bool {
		return UVE_MR_Turnstile::verify( $token, $ip );
	}
}
