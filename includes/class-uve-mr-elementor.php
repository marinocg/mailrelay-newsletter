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

	/**
	 * Register form selector control for the Elementor widget.
	 *
	 * @param object $widget Elementor widget instance.
	 * @param array  $opts   Default options.
	 * @return void
	 */
	public static function register_form_controls( $widget, array $opts ): void {
		unset( $opts );
		if ( ! is_object( $widget ) || ! method_exists( $widget, 'add_control' ) ) {
			return;
		}

		$forms = UVE_MR_Form_Use_Cases::list_forms(
			array(
				'post_status'    => array( 'publish' ),
				'posts_per_page' => 100,
			)
		);
		if ( empty( $forms ) ) {
			$create_url = add_query_arg(
				array(
					'page'   => 'uve-mr-newsletter-forms',
					'action' => 'new',
				),
				admin_url( 'admin.php' )
			);
			$message    = sprintf(
				/* translators: %s: link to create form. */
				__( 'No forms yet. <a href="%s" target="_blank" rel="noopener noreferrer">Create your first form</a>.', 'uve-mailrelay-newsletter' ),
				esc_url( $create_url )
			);
			$widget->add_control(
				'form_empty_notice',
				array(
					'type'            => defined( 'Elementor\\Controls_Manager::RAW_HTML' ) ? \Elementor\Controls_Manager::RAW_HTML : 'raw_html',
					'raw'             => wp_kses_post( $message ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				)
			);
			return;
		}

		$form_options = array();
		foreach ( $forms as $form ) {
			$form_options[ (string) $form->id ] = $form->name;
		}

		$select_control = defined( 'Elementor\\Controls_Manager::SELECT' )
			? \Elementor\Controls_Manager::SELECT
			: 'select';

		$widget->add_control(
			'form_id',
			array(
				'label'   => __( 'Form', 'uve-mailrelay-newsletter' ),
				'type'    => $select_control,
				'options' => $form_options,
			)
		);
	}
}
