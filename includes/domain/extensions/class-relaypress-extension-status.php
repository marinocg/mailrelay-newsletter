<?php
/**
 * Extension status.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extension status.
 */
final class RelayPress_Extension_Status {

	/**
	 * Extension definition.
	 *
	 * @var RelayPress_Extension
	 */
	private RelayPress_Extension $extension;

	/**
	 * Enabled flag.
	 *
	 * @var bool
	 */
	private bool $enabled;

	/**
	 * Create an extension status.
	 *
	 * @param RelayPress_Extension $extension Extension definition.
	 * @param bool                 $enabled Enabled flag.
	 */
	public function __construct( RelayPress_Extension $extension, bool $enabled ) {
		$this->extension = $extension;
		$this->enabled   = $enabled;
	}

	/**
	 * Get the extension definition.
	 *
	 * @return RelayPress_Extension
	 */
	public function get_extension(): RelayPress_Extension {
		return $this->extension;
	}

	/**
	 * Determine whether the extension is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}
}
