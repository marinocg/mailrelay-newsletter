<?php
/**
 * Request context interface.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Request context interface.
 */
interface UVE_MR_Request_Context {
	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	public function get_client_ip(): string;

	/**
	 * Get sanitized user agent.
	 *
	 * @return string
	 */
	public function get_user_agent(): string;

	/**
	 * Get safe page URL for logging.
	 *
	 * @param array $data Request data.
	 * @return string
	 */
	public function get_page_url_from_request( array $data ): string;

	/**
	 * Get current time in MySQL format.
	 *
	 * @return string
	 */
	public function current_time_mysql(): string;
}
