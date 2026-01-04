<?php
/**
 * WordPress options adapter.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress options repository adapter.
 */
final class UVE_MR_WP_Options_Repository implements UVE_MR_Options_Repository {
	/**
	 * Fetch merged options.
	 *
	 * @return array
	 */
	public function get_options(): array {
		return UVE_Mailrelay_Newsletter::get_options();
	}
}
