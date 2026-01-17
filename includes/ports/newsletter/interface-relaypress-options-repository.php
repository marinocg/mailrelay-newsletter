<?php
/**
 * Options repository port.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RelayPress_Options_Repository {
	/**
	 * Fetch merged options.
	 *
	 * @return array
	 */
	public function get_options(): array;
}
