<?php
/**
 * Elementor widget for RelayPress.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'UVE_MR_Elementor_Newsletter_Widget' ) || ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Elementor widget.
 */
class UVE_MR_Elementor_Newsletter_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'uve_mr_newsletter';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'RelayPress', 'uve-mailrelay-newsletter' );
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
		return array( 'uve-mailrelay' );
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$opts = UVE_Mailrelay_Newsletter::get_options();

				$this->start_controls_section(
					'content_section',
					array(
						'label' => __( 'Content', 'uve-mailrelay-newsletter' ),
						'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
					)
				);

				do_action( 'uve_mr_elementor_register_controls', $this, $opts );

				$this->add_control(
					'title',
					array(
						'label'       => __( 'Title', 'uve-mailrelay-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => $opts['title'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'description',
					array(
						'label'       => __( 'Description', 'uve-mailrelay-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXTAREA,
						'default'     => $opts['description'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'email_placeholder',
					array(
						'label'       => __( 'Email placeholder', 'uve-mailrelay-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => $opts['email_placeholder'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'submit_label',
					array(
						'label'       => __( 'Button text', 'uve-mailrelay-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => $opts['submit_label'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'group_ids',
					array(
						'label'       => __( 'Group IDs', 'uve-mailrelay-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => $opts['group_ids'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'privacy_url',
					array(
						'label'       => __( 'Privacy URL', 'uve-mailrelay-newsletter' ),
						'type'        => \Elementor\Controls_Manager::URL,
						'default'     => array( 'url' => $opts['privacy_url'] ),
						'label_block' => true,
					)
				);

				$this->add_control(
					'consent_label',
					array(
						'label'       => __( 'Consent text', 'uve-mailrelay-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => $opts['consent_label'],
						'label_block' => true,
					)
				);

				$this->add_control(
					'extra_class',
					array(
						'label'       => __( 'Extra CSS class', 'uve-mailrelay-newsletter' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => '',
						'label_block' => true,
					)
				);

				$this->add_control(
					'ajax_mode',
					array(
						'label'        => __( 'Enable AJAX submissions', 'uve-mailrelay-newsletter' ),
						'type'         => \Elementor\Controls_Manager::SWITCHER,
						'label_on'     => __( 'Yes', 'uve-mailrelay-newsletter' ),
						'label_off'    => __( 'No', 'uve-mailrelay-newsletter' ),
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
			$primary_form = UVE_MR_Form_Use_Cases::get_primary_form_for_admin();
			$form_id      = $primary_form ? $primary_form->id : 0;
		}

		if ( $form_id ) {
			$form_args = array(
				'id'    => $form_id,
				'class' => $settings['extra_class'] ?? '',
			);
		} else {
			echo UVE_MR_Frontend::shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

				echo UVE_MR_Frontend::shortcode( $form_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
