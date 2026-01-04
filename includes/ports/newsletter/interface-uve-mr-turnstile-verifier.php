<?php
/**
 * Turnstile verifier port.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface UVE_MR_Turnstile_Verifier {
	/**
	 * Verify a Turnstile token.
	 *
	 * @param string $token Token.
	 * @param string $ip Client IP.
	 * @return bool
	 */
	public function verify( string $token, string $ip ): bool;
}
