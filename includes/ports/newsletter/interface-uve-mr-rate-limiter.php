<?php
/**
 * Rate limiter port.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface UVE_MR_Rate_Limiter {
	/**
	 * Increment a key and check if it is still allowed.
	 *
	 * @param string $key Key to rate limit.
	 * @param int    $max Maximum allowed hits.
	 * @param int    $window_seconds Window length in seconds.
	 * @return bool True when allowed, false when rate-limited.
	 */
	public function hit( string $key, int $max, int $window_seconds ): bool;
}
