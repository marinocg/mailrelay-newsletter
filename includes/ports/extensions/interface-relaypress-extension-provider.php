<?php
/**
 * Extension provider port.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RelayPress_Extension_Provider {
	/**
	 * Provide extension definition.
	 *
	 * @return RelayPress_Extension
	 */
	public function get_extension(): RelayPress_Extension;
}
