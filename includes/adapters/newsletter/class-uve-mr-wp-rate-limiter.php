<?php
/**
 * WordPress rate limiter adapter.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress rate limiter adapter.
 */
final class UVE_MR_WP_Rate_Limiter implements UVE_MR_Rate_Limiter {
	/**
	 * Increment a key and check if it is still allowed.
	 *
	 * @param string $key Key to rate limit.
	 * @param int    $max Maximum allowed hits.
	 * @param int    $window_seconds Window length in seconds.
	 * @return bool True when allowed, false when rate-limited.
	 */
	public function hit( string $key, int $max, int $window_seconds ): bool {
		$count = (int) get_transient( $key );
		if ( $max <= $count ) {
			return false;
		}
		set_transient( $key, $count + 1, $window_seconds );
		return true;
	}
}
