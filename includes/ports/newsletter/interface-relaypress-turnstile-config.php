<?php
/**
 * Turnstile configuration port.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RelayPress_Turnstile_Config {
	/**
	 * Determine if Turnstile is configured.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool;

	/**
	 * Get the Turnstile site key.
	 *
	 * @return string
	 */
	public function get_site_key(): string;

	/**
	 * Get the Turnstile secret key.
	 *
	 * @return string
	 */
	public function get_secret_key(): string;
}
