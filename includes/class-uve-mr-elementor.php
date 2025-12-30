<?php
/**
 * Elementor integration.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor integration.
 */
final class UVE_MR_Elementor {

	/**
	 * Register Elementor category.
	 *
	 * @param object $elements_manager Elementor elements manager.
	 * @return void
	 */
	public static function register_elementor_category( $elements_manager ): void {
		if ( ! is_object( $elements_manager ) || ! method_exists( $elements_manager, 'add_category' ) ) {
			return;
		}

		$elements_manager->add_category(
			'uve-mailrelay',
			array(
				'title' => __( 'Uve Mailrelay', 'uve-mailrelay-newsletter' ),
				'icon'  => 'fa fa-envelope',
			)
		);
	}

	/**
	 * Register Elementor widget.
	 *
	 * @param object $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public static function register_elementor_widget( $widgets_manager ): void {
		if ( ! is_object( $widgets_manager ) ) {
			return;
		}

		if ( ! class_exists( 'UVE_MR_Elementor_Newsletter_Widget' ) && class_exists( '\Elementor\Widget_Base' ) ) {
			$path = __DIR__ . '/class-uve-mr-elementor-newsletter-widget.php';
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}

		if ( ! class_exists( 'UVE_MR_Elementor_Newsletter_Widget' ) ) {
			return;
		}

		$widget = new UVE_MR_Elementor_Newsletter_Widget();
		if ( method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( $widget );
			return;
		}

		if ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
			$widgets_manager->register_widget_type( $widget );
		}
	}
}
