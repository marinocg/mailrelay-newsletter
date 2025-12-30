<?php
/**
 * Widget registration helpers.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget registration helpers.
 */
final class UVE_MR_Widgets {

	/**
	 * Register the widget.
	 *
	 * @return void
	 */
	public static function register_widget(): void {
		register_widget( 'UVE_MR_Newsletter_Widget' );
	}
}
