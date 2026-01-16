<?php
/**
 * Elementor widget for RelayPress.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'RelayPress_Elementor_Newsletter_Widget' ) || ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Elementor widget.
 */
class RelayPress_Elementor_Newsletter_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'relaypress_newsletter';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'RelayPress', 'relaypress-newsletter' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-mail';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'relaypress' );
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$opts = RelayPress_Newsletter::get_options();

				$this->start_controls_section(
					'content_section',
					array(
						'label' => __( 'Content', 'relaypress-newsletter' ),
						'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
					)
				);

				do_action( 'relaypress_elementor_register_controls', $this, $opts );

				$this->add_control(
					'title',
					array(
						'label'       => __( 'Title', 'relaypress-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => $opts['title'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'description',
					array(
						'label'       => __( 'Description', 'relaypress-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXTAREA,
						'default'     => $opts['description'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'email_placeholder',
					array(
						'label'       => __( 'Email placeholder', 'relaypress-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => $opts['email_placeholder'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'submit_label',
					array(
						'label'       => __( 'Button text', 'relaypress-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => $opts['submit_label'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'group_ids',
					array(
						'label'       => __( 'Group IDs', 'relaypress-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => $opts['group_ids'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'privacy_url',
					array(
						'label'       => __( 'Privacy URL', 'relaypress-newsletter' ),
						'type'        => \Elementor\Controls_Manager::URL,
						'default'     => array( 'url' => $opts['privacy_url'] ),
						'label_block' => true,
					)
				);

				$this->add_control(
					'consent_label',
					array(
						'label'       => __( 'Consent text', 'relaypress-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => $opts['consent_label'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'extra_class',
					array(
						'label'       => __( 'Extra CSS class', 'relaypress-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => '',
						'label_block' => true,
					)
				);

				$this->add_control(
					'ajax_mode',
					array(
						'label'        => __( 'Enable AJAX submissions', 'relaypress-newsletter' ),
						'type'         => \Elementor\Controls_Manager::SWITCHER,
						'label_on'     => __( 'Yes', 'relaypress-newsletter' ),
						'label_off'    => __( 'No', 'relaypress-newsletter' ),
						'return_value' => '1',
						'default'      => '0',
					)
				);

				$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings    = $this->get_settings_for_display();
		$privacy     = $settings['privacy_url']['url'] ?? '';
		$has_form_id = array_key_exists( 'form_id', $settings );
		$form_id_raw = $has_form_id ? (string) $settings['form_id'] : '';
		$form_id     = absint( $form_id_raw );
		if ( ! $form_id ) {
			$primary_form = RelayPress_Form_Use_Cases::get_primary_form_for_admin();
			$form_id      = $primary_form ? $primary_form->id : 0;
		}

		if ( $form_id ) {
			$form_args = array(
				'id'    => $form_id,
				'class' => $settings['extra_class'] ?? '',
			);
			if ( array_key_exists( 'ajax_mode', $settings ) ) {
				$form_args['ajax'] = ( '1' === (string) $settings['ajax_mode'] ) ? '1' : '0';
			}
			if ( '' !== $privacy ) {
				$form_args['privacy_url'] = $privacy;
			}
			if ( array_key_exists( 'consent_label', $settings ) ) {
				$form_args['consent_label'] = (string) $settings['consent_label'];
			}
		} else {
			echo RelayPress_Frontend::shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		echo RelayPress_Frontend::shortcode( $form_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
