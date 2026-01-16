<?php
/**
 * Widget registration helpers.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget registration helpers.
 */
final class RelayPress_Widgets {

	/**
	 * Register the widget.
	 *
	 * @return void
	 */
	public static function register_widget(): void {
		register_widget( 'RelayPress_Newsletter_Widget' );
	}
}
