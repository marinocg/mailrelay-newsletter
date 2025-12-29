<?php
/**
 * Elementor widget for the Uve Mailrelay Newsletter.
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
		return 'Uve Mailrelay Newsletter';
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
				'label' => 'Contenido',
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => 'Titulo',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => $opts['title'],
				'label_block' => true,
			)
		);

		$this->add_control(
			'description',
			array(
				'label'       => 'Descripcion',
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => $opts['description'],
				'label_block' => true,
			)
		);

		$this->add_control(
			'email_placeholder',
			array(
				'label'       => 'Placeholder email',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => $opts['email_placeholder'],
				'label_block' => true,
			)
		);

		$this->add_control(
			'submit_label',
			array(
				'label'       => 'Texto boton',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => $opts['submit_label'],
				'label_block' => true,
			)
		);

		$this->add_control(
			'group_ids',
			array(
				'label'       => 'Group IDs',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => $opts['group_ids'],
				'label_block' => true,
			)
		);

		$this->add_control(
			'privacy_url',
			array(
				'label'       => 'URL privacidad',
				'type'        => \Elementor\Controls_Manager::URL,
				'default'     => array( 'url' => $opts['privacy_url'] ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'consent_label',
			array(
				'label'       => 'Texto consentimiento',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => $opts['consent_label'],
				'label_block' => true,
			)
		);

		$this->add_control(
			'extra_class',
			array(
				'label'       => 'Clase CSS extra',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
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
		$settings = $this->get_settings_for_display();
		$privacy  = $settings['privacy_url']['url'] ?? '';

		$form_args = array(
			'title'             => $settings['title'] ?? '',
			'description'       => $settings['description'] ?? '',
			'email_placeholder' => $settings['email_placeholder'] ?? '',
			'submit_label'      => $settings['submit_label'] ?? '',
			'group_ids'         => $settings['group_ids'] ?? '',
			'privacy_url'       => $privacy,
			'consent_label'     => $settings['consent_label'] ?? '',
			'class'             => $settings['extra_class'] ?? '',
		);

		echo UVE_MR_Frontend::shortcode( $form_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
