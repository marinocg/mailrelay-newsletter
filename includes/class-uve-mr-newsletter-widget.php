<?php
/**
 * WordPress widget for the newsletter form.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Newsletter widget.
 */
class UVE_MR_Newsletter_Widget extends WP_Widget {

	/**
	 * Set up the widget.
	 */
	public function __construct() {
		parent::__construct(
			'uve_mr_newsletter_widget',
			__( 'MR4WP', 'uve-mailrelay-newsletter' ),
			array( 'description' => __( 'Formulario de newsletter con Turnstile + Mailrelay API + double opt-in (mensaje neutro) y logs.', 'uve-mailrelay-newsletter' ) )
		);
	}

	/**
	 * Render widget output.
	 *
	 * @param array $args Widget args.
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		echo wp_kses_post( $args['before_widget'] ?? '' );
		$opts = UVE_Mailrelay_Newsletter::get_options();

		$form_args = array(
			'title'             => $instance['title'] ?? $opts['title'],
			'description'       => $instance['description'] ?? $opts['description'],
			'email_placeholder' => $instance['email_placeholder'] ?? $opts['email_placeholder'],
			'submit_label'      => $instance['submit_label'] ?? $opts['submit_label'],
			'group_ids'         => $instance['group_ids'] ?? $opts['group_ids'],
			'privacy_url'       => $instance['privacy_url'] ?? $opts['privacy_url'],
			'consent_label'     => $instance['consent_label'] ?? $opts['consent_label'],
			'class'             => $instance['class'] ?? '',
		);

		echo UVE_MR_Frontend::shortcode( $form_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_kses_post( $args['after_widget'] ?? '' );
	}

	/**
	 * Render widget form in the admin.
	 *
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function form( $instance ) {
		$opts              = UVE_Mailrelay_Newsletter::get_options();
		$title             = $instance['title'] ?? $opts['title'];
		$description       = $instance['description'] ?? $opts['description'];
		$email_placeholder = $instance['email_placeholder'] ?? $opts['email_placeholder'];
		$submit_label      = $instance['submit_label'] ?? $opts['submit_label'];
		$group_ids         = $instance['group_ids'] ?? $opts['group_ids'];
		$privacy_url       = $instance['privacy_url'] ?? $opts['privacy_url'];
		$consent_label     = $instance['consent_label'] ?? $opts['consent_label'];
		$class             = $instance['class'] ?? '';
		?>
		<p><label><?php echo esc_html__( 'Título', 'uve-mailrelay-newsletter' ); ?>
				<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</label></p>
		<p><label><?php echo esc_html__( 'Descripción', 'uve-mailrelay-newsletter' ); ?>
				<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'description' ) ); ?>" type="text" value="<?php echo esc_attr( $description ); ?>">
			</label></p>
		<p><label><?php echo esc_html__( 'Placeholder email', 'uve-mailrelay-newsletter' ); ?>
				<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'email_placeholder' ) ); ?>" type="text" value="<?php echo esc_attr( $email_placeholder ); ?>">
			</label></p>
		<p><label><?php echo esc_html__( 'Texto botón', 'uve-mailrelay-newsletter' ); ?>
				<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'submit_label' ) ); ?>" type="text" value="<?php echo esc_attr( $submit_label ); ?>">
			</label></p>
		<p><label><?php echo esc_html__( 'Group IDs', 'uve-mailrelay-newsletter' ); ?>
				<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'group_ids' ) ); ?>" type="text" value="<?php echo esc_attr( $group_ids ); ?>">
			</label></p>
		<p><label><?php echo esc_html__( 'URL privacidad', 'uve-mailrelay-newsletter' ); ?>
				<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'privacy_url' ) ); ?>" type="text" value="<?php echo esc_attr( $privacy_url ); ?>">
			</label></p>
		<p><label><?php echo esc_html__( 'Texto consentimiento', 'uve-mailrelay-newsletter' ); ?>
				<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'consent_label' ) ); ?>" type="text" value="<?php echo esc_attr( $consent_label ); ?>">
			</label></p>
		<p><label><?php echo esc_html__( 'Clase CSS extra (opcional)', 'uve-mailrelay-newsletter' ); ?>
				<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'class' ) ); ?>" type="text" value="<?php echo esc_attr( $class ); ?>">
			</label></p>
		<?php
	}

	/**
	 * Sanitize widget form values.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Old values.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                      = array();
		$instance['title']             = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['description']       = sanitize_text_field( $new_instance['description'] ?? '' );
		$instance['email_placeholder'] = sanitize_text_field( $new_instance['email_placeholder'] ?? '' );
		$instance['submit_label']      = sanitize_text_field( $new_instance['submit_label'] ?? '' );
		$instance['group_ids']         = sanitize_text_field( $new_instance['group_ids'] ?? '' );
		$instance['privacy_url']       = esc_url_raw( $new_instance['privacy_url'] ?? '' );
		$instance['consent_label']     = sanitize_text_field( $new_instance['consent_label'] ?? '' );
		$instance['class']             = sanitize_text_field( $new_instance['class'] ?? '' );
		return $instance;
	}
}
