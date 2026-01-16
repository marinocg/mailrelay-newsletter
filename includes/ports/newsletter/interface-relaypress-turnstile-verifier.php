<?php
/**
 * Turnstile verifier port.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RelayPress_Turnstile_Verifier {
	/**
	 * Verify a Turnstile token.
	 *
	 * @param string $token Token.
	 * @param string $ip Client IP.
	 * @return bool
	 */
	public function verify( string $token, string $ip ): bool;
}
