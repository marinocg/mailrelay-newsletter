<?php
/**
 * WordPress Turnstile configuration adapter.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Turnstile configuration adapter.
 */
final class RelayPress_WP_Turnstile_Config implements RelayPress_Turnstile_Config {

	/**
	 * Determine if Turnstile is configured.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return '' !== $this->get_site_key() && '' !== $this->get_secret_key();
	}

	/**
	 * Get the Turnstile site key.
	 *
	 * @return string
	 */
	public function get_site_key(): string {
		if ( defined( 'CF_TURNSTILE_SITE_KEY' ) && CF_TURNSTILE_SITE_KEY ) {
			return (string) CF_TURNSTILE_SITE_KEY;
		}
		$opts = RelayPress_Newsletter::get_options();
		return (string) ( $opts['turnstile_site_key'] ?? '' );
	}

	/**
	 * Get the Turnstile secret key.
	 *
	 * @return string
	 */
	public function get_secret_key(): string {
		if ( defined( 'CF_TURNSTILE_SECRET_KEY' ) && CF_TURNSTILE_SECRET_KEY ) {
			return (string) CF_TURNSTILE_SECRET_KEY;
		}
		$opts = RelayPress_Newsletter::get_options();
		return (string) ( $opts['turnstile_secret_key'] ?? '' );
	}
}
