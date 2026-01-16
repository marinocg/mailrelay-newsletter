<?php
/**
 * WordPress logs adapter.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress logs repository adapter.
 */
final class RelayPress_WP_Logs_Repository implements RelayPress_Logs_Repository {
	/**
	 * Ensure log table schema exists.
	 *
	 * @return void
	 */
	public function ensure_table(): void {
		RelayPress_Logs::maybe_create_or_update_table();
	}

	/**
	 * Store a consent log entry.
	 *
	 * @param array $data Log data.
	 * @return void
	 */
	public function store_consent_log( array $data ): void {
		RelayPress_Logs::store_consent_log_compatible( $data );
	}
}
