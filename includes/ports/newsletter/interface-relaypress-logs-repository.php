<?php
/**
 * Logs repository port.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RelayPress_Logs_Repository {
	/**
	 * Ensure log table schema exists.
	 *
	 * @return void
	 */
	public function ensure_table(): void;

	/**
	 * Store a consent log entry.
	 *
	 * @param array $data Log data.
	 * @return void
	 */
	public function store_consent_log( array $data ): void;
}
