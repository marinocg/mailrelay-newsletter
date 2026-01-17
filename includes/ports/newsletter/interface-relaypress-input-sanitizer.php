<?php
/**
 * Input sanitizer interface.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Input sanitizer interface.
 */
interface RelayPress_Input_Sanitizer {
	/**
	 * Unslash a scalar value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function unslash( $value ): string;

	/**
	 * Sanitize text input.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public function sanitize_text( string $value ): string;

	/**
	 * Sanitize email input.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public function sanitize_email( string $value ): string;

	/**
	 * Sanitize URL input.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public function sanitize_url( string $value ): string;

	/**
	 * Validate email.
	 *
	 * @param string $value Email.
	 * @return bool
	 */
	public function is_email( string $value ): bool;
}
