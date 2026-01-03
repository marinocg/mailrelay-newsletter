<?php
/**
 * WordPress-backed form limits.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress-backed form limits.
 */
final class UVE_MR_WP_Form_Limits implements UVE_MR_Form_Limits {
	/**
	 * Get the maximum number of published forms allowed.
	 *
	 * @return int
	 */
	public function max_published_forms(): int {
		return (int) UVE_MR_Utils::premium_filter( 'uve_mr_max_published_forms', 1, 1 );
	}
}
