<?php
/**
 * Form limits interface.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form limits interface.
 */
interface UVE_MR_Form_Limits {
	/**
	 * Get the maximum number of published forms allowed.
	 *
	 * @return int
	 */
	public function max_published_forms(): int;
}
