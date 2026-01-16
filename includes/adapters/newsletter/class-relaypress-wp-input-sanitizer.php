<?php
/**
 * WordPress-backed input sanitizer.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress-backed input sanitizer.
 */
final class RelayPress_WP_Input_Sanitizer implements RelayPress_Input_Sanitizer {
	/**
	 * Unslash a scalar value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function unslash( $value ): string {
		if ( is_scalar( $value ) ) {
			return (string) wp_unslash( $value );
		}
		return '';
	}

	/**
	 * Sanitize text input.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public function sanitize_text( string $value ): string {
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize email input.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public function sanitize_email( string $value ): string {
		return sanitize_email( $value );
	}

	/**
	 * Sanitize URL input.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public function sanitize_url( string $value ): string {
		return esc_url_raw( $value );
	}

	/**
	 * Validate email.
	 *
	 * @param string $value Email.
	 * @return bool
	 */
	public function is_email( string $value ): bool {
		return (bool) is_email( $value );
	}
}
