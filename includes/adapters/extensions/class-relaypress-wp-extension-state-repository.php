<?php
/**
 * WordPress extension state repository adapter.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress extension state repository adapter.
 */
final class RelayPress_WP_Extension_State_Repository implements RelayPress_Extension_State_Repository {

	/**
	 * Build option name for an extension.
	 *
	 * @param string $slug Extension slug.
	 * @return string
	 */
	public function option_name( string $slug ): string {
		if ( function_exists( 'sanitize_key' ) ) {
			$slug = sanitize_key( $slug );
		} else {
			$slug = preg_replace( '/[^a-z0-9_]/', '', strtolower( $slug ) );
		}
		return 'relaypress_extension_' . $slug . '_enabled';
	}

	/**
	 * Determine whether extension is enabled.
	 *
	 * @param string $slug Extension slug.
	 * @param bool   $default_enabled Default enabled value.
	 * @return bool
	 */
	public function is_enabled( string $slug, bool $default_enabled = false ): bool {
		$value = (string) get_option( $this->option_name( $slug ), $default_enabled ? 'yes' : 'no' );
		return in_array( strtolower( $value ), array( '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * Persist extension enabled flag.
	 *
	 * @param string $slug Extension slug.
	 * @param bool   $enabled Enabled flag.
	 * @return void
	 */
	public function set_enabled( string $slug, bool $enabled ): void {
		$this->update_option_value( $this->option_name( $slug ), $enabled ? 'yes' : 'no' );
	}

	/**
	 * Persist option value (test-safe fallback).
	 *
	 * @param string $key Option name.
	 * @param string $value Option value.
	 * @return void
	 */
	private function update_option_value( string $key, string $value ): void {
		if ( function_exists( 'update_option' ) ) {
			update_option( $key, $value );
			return;
		}

		if ( isset( $GLOBALS['relaypress_test_options'] ) ) {
			$GLOBALS['relaypress_test_options'][ $key ] = $value;
			return;
		}

		if ( function_exists( 'add_option' ) ) {
			add_option( $key, $value );
		}
	}
}
