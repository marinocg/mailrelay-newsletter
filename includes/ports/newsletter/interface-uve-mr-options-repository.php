<?php
/**
 * Options repository port.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface UVE_MR_Options_Repository {
	/**
	 * Fetch merged options.
	 *
	 * @return array
	 */
	public function get_options(): array;
}
