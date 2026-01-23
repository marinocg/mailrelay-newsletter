<?php
/**
 * Extension registry port.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RelayPress_Extension_Registry {
	/**
	 * Fetch extension providers.
	 *
	 * @return RelayPress_Extension_Provider[]
	 */
	public function get_providers(): array;
}
