<?php
/**
 * Extension state repository port.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RelayPress_Extension_State_Repository {
	/**
	 * Build option name for an extension.
	 *
	 * @param string $slug Extension slug.
	 * @return string
	 */
	public function option_name( string $slug ): string;

	/**
	 * Determine whether extension is enabled.
	 *
	 * @param string $slug Extension slug.
	 * @param bool   $default_enabled Default enabled value.
	 * @return bool
	 */
	public function is_enabled( string $slug, bool $default_enabled = false ): bool;

	/**
	 * Persist extension enabled flag.
	 *
	 * @param string $slug Extension slug.
	 * @param bool   $enabled Enabled flag.
	 * @return void
	 */
	public function set_enabled( string $slug, bool $enabled ): void;
}
