<?php
/**
 * Extension definition.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extension definition.
 */
final class RelayPress_Extension {

	/**
	 * Extension slug.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Extension label.
	 *
	 * @var string
	 */
	private string $label;

	/**
	 * Extension description.
	 *
	 * @var string
	 */
	private string $description;

	/**
	 * Settings URL.
	 *
	 * @var string
	 */
	private string $settings_url;

	/**
	 * Health label.
	 *
	 * @var string
	 */
	private string $health_label;

	/**
	 * Requirement note when unavailable.
	 *
	 * @var string
	 */
	private string $requirement_note;

	/**
	 * Availability flag.
	 *
	 * @var bool
	 */
	private bool $available;

	/**
	 * Default enabled flag.
	 *
	 * @var bool
	 */
	private bool $default_enabled;

	/**
	 * Create an extension definition.
	 *
	 * @param string $slug Slug.
	 * @param string $label Label.
	 * @param string $description Description.
	 * @param string $settings_url Settings URL.
	 * @param string $health_label Health label.
	 * @param string $requirement_note Requirement note for unmet dependencies.
	 * @param bool   $available Availability.
	 * @param bool   $default_enabled Default enabled state.
	 */
	public function __construct(
		string $slug,
		string $label,
		string $description,
		string $settings_url,
		string $health_label,
		string $requirement_note,
		bool $available,
		bool $default_enabled = false
	) {
		$this->slug             = $slug;
		$this->label            = $label;
		$this->description      = $description;
		$this->settings_url     = $settings_url;
		$this->health_label     = $health_label;
		$this->requirement_note = $requirement_note;
		$this->available        = $available;
		$this->default_enabled  = $default_enabled;
	}

	/**
	 * Get the extension slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Get the extension label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Get the extension description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Get the settings URL.
	 *
	 * @return string
	 */
	public function get_settings_url(): string {
		return $this->settings_url;
	}

	/**
	 * Get the health label.
	 *
	 * @return string
	 */
	public function get_health_label(): string {
		return $this->health_label;
	}

	/**
	 * Get requirement note when unavailable.
	 *
	 * @return string
	 */
	public function get_requirement_note(): string {
		return $this->requirement_note;
	}

	/**
	 * Determine availability.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->available;
	}

	/**
	 * Determine default enabled state.
	 *
	 * @return bool
	 */
	public function is_default_enabled(): bool {
		return $this->default_enabled;
	}
}
