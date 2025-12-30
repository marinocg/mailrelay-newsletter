<?php
/**
 * Minimal Elementor stubs for static analysis.
 */

namespace Elementor;

abstract class Widget_Base {
	/**
	 * @param array<string, mixed> $args
	 */
	public function start_controls_section( string $id, array $args = array() ): void {}
	public function end_controls_section(): void {}
	/**
	 * @param array<string, mixed> $args
	 */
	public function add_control( string $id, array $args = array() ): void {}
	/**
	 * @return array<string, mixed>
	 */
	public function get_settings_for_display(): array {
		return array();
	}
}

class Controls_Manager {
	public const TAB_CONTENT = 'content';
	public const TEXT        = 'text';
	public const TEXTAREA    = 'textarea';
	public const URL         = 'url';
	public const SWITCHER    = 'switcher';
}
