<?php
/**
 * WordPress extension registry adapter.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress extension registry adapter.
 *
 * Provides extension providers registered via the WordPress filter.
 */
final class RelayPress_WP_Extension_Registry implements RelayPress_Extension_Registry {

	public const FILTER = 'relaypress_extensions';

	/**
	 * Get registered extension providers.
	 *
	 * @return RelayPress_Extension_Provider[]
	 */
	public function get_providers(): array {
		$providers = apply_filters( self::FILTER, array() );
		if ( ! is_array( $providers ) ) {
			return array();
		}

		$out = array();
		foreach ( $providers as $provider ) {
			if ( $provider instanceof RelayPress_Extension_Provider ) {
				$out[] = $provider;
			}
		}

		return $out;
	}
}
