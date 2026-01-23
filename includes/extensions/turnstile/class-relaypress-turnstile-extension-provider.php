<?php
/**
 * Turnstile extension provider.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turnstile extension provider.
 */
final class RelayPress_Turnstile_Extension_Provider implements RelayPress_Extension_Provider {

	public const SLUG = 'turnstile';

	/**
	 * Register the provider.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( RelayPress_WP_Extension_Registry::FILTER, array( __CLASS__, 'register_provider' ) );
	}

	/**
	 * Add provider to the registry.
	 *
	 * @param array $providers Providers.
	 * @return array
	 */
	public static function register_provider( array $providers ): array {
		$providers[] = new self();
		return $providers;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_extension(): RelayPress_Extension {
		return new RelayPress_Extension(
			self::SLUG,
			__( 'Turnstile', 'relaypress-newsletter' ),
			__( 'Cloudflare Turnstile spam protection.', 'relaypress-newsletter' ),
			admin_url( 'admin.php?page=relaypress-newsletter-turnstile' ),
			$this->health_label(),
			__( 'Configure Turnstile site and secret keys.', 'relaypress-newsletter' ),
			$this->is_available(),
			true
		);
	}

	/**
	 * Determine availability.
	 *
	 * @return bool
	 */
	private function is_available(): bool {
		return RelayPress_Container::turnstile_config()->is_enabled();
	}

	/**
	 * Build health label.
	 *
	 * @return string
	 */
	private function health_label(): string {
		return RelayPress_Container::turnstile_config()->is_enabled()
			? __( 'Configured', 'relaypress-newsletter' )
			: __( 'Not configured', 'relaypress-newsletter' );
	}
}
