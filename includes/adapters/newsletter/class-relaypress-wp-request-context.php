<?php
/**
 * WordPress-backed request context.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress-backed request context.
 */
final class RelayPress_WP_Request_Context implements RelayPress_Request_Context {
	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	public function get_client_ip(): string {
		return RelayPress_Utils::get_client_ip();
	}

	/**
	 * Get sanitized user agent.
	 *
	 * @return string
	 */
	public function get_user_agent(): string {
		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		return substr( $user_agent, 0, 2000 );
	}

	/**
	 * Get safe page URL for logging.
	 *
	 * @param array $data Request data.
	 * @return string
	 */
	public function get_page_url_from_request( array $data ): string {
		return RelayPress_Utils::safe_page_url_from_request( $data );
	}

	/**
	 * Get raw Accept-Language header.
	 *
	 * @return string
	 */
	public function get_accept_language(): string {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '' ) );
	}

	/**
	 * Get current time in MySQL format.
	 *
	 * @return string
	 */
	public function current_time_mysql(): string {
		return current_time( 'mysql' );
	}
}
