<?php
/**
 * WordPress Turnstile adapter.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Turnstile verifier adapter.
 */
final class RelayPress_WP_Turnstile_Verifier implements RelayPress_Turnstile_Verifier {
	/**
	 * Verify a Turnstile token.
	 *
	 * @param string $token Token.
	 * @param string $ip Client IP.
	 * @return bool
	 */
	public function verify( string $token, string $ip ): bool {
		return RelayPress_Turnstile::verify( $token, $ip );
	}
}
