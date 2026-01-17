<?php
/**
 * WordPress options adapter.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress options repository adapter.
 */
final class RelayPress_WP_Options_Repository implements RelayPress_Options_Repository {
	/**
	 * Fetch merged options.
	 *
	 * @return array
	 */
	public function get_options(): array {
		return RelayPress_Newsletter::get_options();
	}
}
