<?php
/**
 * Form configuration helpers.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form configuration helpers.
 */
final class UVE_MR_Form_Config {
	/**
	 * Get default config from global options.
	 *
	 * @param array $opts Global options.
	 * @return array
	 */
	public static function defaults( array $opts ): array {
		return array(
			'version'     => UVE_MR_Form::CONFIG_VERSION,
			'basics'      => array(
				'name'              => '',
				'title'             => (string) ( $opts['title'] ?? '' ),
				'description'       => (string) ( $opts['description'] ?? '' ),
				'email_placeholder' => (string) ( $opts['email_placeholder'] ?? '' ),
				'submit_label'      => (string) ( $opts['submit_label'] ?? '' ),
			),
			'destination' => array(
				'group_ids'         => (string) ( $opts['group_ids'] ?? '' ),
				'subscriber_status' => (string) ( $opts['subscriber_status'] ?? 'inactive' ),
			),
			'fields'      => array(
				'include_name'  => false,
				'name_label'    => __( 'Name', 'uve-mailrelay-newsletter' ),
				'custom_fields' => array(),
			),
			'consent'     => array(
				'inherit'     => true,
				'label'       => (string) ( $opts['consent_label'] ?? '' ),
				'privacy_url' => (string) ( $opts['privacy_url'] ?? '' ),
			),
			'turnstile'   => array(
				'inherit' => true,
				'enabled' => true,
			),
			'messages'    => array(
				'success' => __( 'Thanks. If the email is valid, you will receive a confirmation email (or you were already subscribed).', 'uve-mailrelay-newsletter' ),
				'captcha' => __( 'Please verify you are human.', 'uve-mailrelay-newsletter' ),
				'consent' => __( 'You must accept the privacy policy.', 'uve-mailrelay-newsletter' ),
				'error'   => __( 'We could not complete the request. Please try again.', 'uve-mailrelay-newsletter' ),
			),
			'rate_limit'  => array(
				'inherit'        => true,
				'max'            => (int) ( $opts['rate_limit_max'] ?? 5 ),
				'window_seconds' => (int) ( $opts['rate_limit_window_seconds'] ?? 3600 ),
			),
			'ajax'        => (string) ( $opts['ajax_mode'] ?? '0' ),
		);
	}

	/**
	 * Merge defaults with stored config.
	 *
	 * @param array $defaults Default config.
	 * @param array $stored Stored config.
	 * @return array
	 */
	public static function merge( array $defaults, array $stored ): array {
		return array_replace_recursive( $defaults, $stored );
	}

	/**
	 * Sanitize form config from input.
	 *
	 * @param array $raw Raw input.
	 * @param array $defaults Default config.
	 * @return array
	 */
	public static function sanitize( array $raw, array $defaults ): array {
		$out = $defaults;

		$out['basics']['title']             = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['basics']['title'] ?? $defaults['basics']['title'] ) ) );
		$out['basics']['description']       = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['basics']['description'] ?? $defaults['basics']['description'] ) ) );
		$out['basics']['email_placeholder'] = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['basics']['email_placeholder'] ?? $defaults['basics']['email_placeholder'] ) ) );
		$out['basics']['submit_label']      = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['basics']['submit_label'] ?? $defaults['basics']['submit_label'] ) ) );

		$out['destination']['group_ids']         = sanitize_text_field( (string) ( $raw['destination']['group_ids'] ?? $defaults['destination']['group_ids'] ) );
		$status                                  = sanitize_text_field( (string) ( $raw['destination']['subscriber_status'] ?? $defaults['destination']['subscriber_status'] ) );
		$out['destination']['subscriber_status'] = in_array( $status, array( 'inactive', 'active' ), true ) ? $status : 'inactive';

		$out['fields']['include_name'] = ! empty( $raw['fields']['include_name'] );
		$out['fields']['name_label']   = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['fields']['name_label'] ?? $defaults['fields']['name_label'] ) ) );

		$custom_fields = array();
		if ( ! empty( $raw['fields']['custom_fields'] ) && is_array( $raw['fields']['custom_fields'] ) ) {
			foreach ( $raw['fields']['custom_fields'] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$key      = sanitize_key( (string) ( $row['key'] ?? '' ) );
				$label    = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $row['label'] ?? '' ) ) );
				$required = ! empty( $row['required'] );
				if ( '' === $key || '' === $label ) {
					continue;
				}
				$custom_fields[] = array(
					'key'      => $key,
					'label'    => $label,
					'required' => $required,
				);
			}
		}
		$out['fields']['custom_fields'] = $custom_fields;

		$out['consent']['inherit']     = ! empty( $raw['consent']['inherit'] );
		$out['consent']['label']       = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['consent']['label'] ?? $defaults['consent']['label'] ) ) );
		$out['consent']['privacy_url'] = esc_url_raw( (string) ( $raw['consent']['privacy_url'] ?? $defaults['consent']['privacy_url'] ) );

		$out['turnstile']['inherit'] = ! empty( $raw['turnstile']['inherit'] );
		$out['turnstile']['enabled'] = ! empty( $raw['turnstile']['enabled'] );

		$out['messages']['success'] = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['messages']['success'] ?? $defaults['messages']['success'] ) ) );
		$out['messages']['captcha'] = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['messages']['captcha'] ?? $defaults['messages']['captcha'] ) ) );
		$out['messages']['consent'] = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['messages']['consent'] ?? $defaults['messages']['consent'] ) ) );
		$out['messages']['error']   = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['messages']['error'] ?? $defaults['messages']['error'] ) ) );

		$out['rate_limit']['inherit']        = ! empty( $raw['rate_limit']['inherit'] );
		$out['rate_limit']['max']            = isset( $raw['rate_limit']['max'] ) ? max( 1, (int) $raw['rate_limit']['max'] ) : (int) $defaults['rate_limit']['max'];
		$out['rate_limit']['window_seconds'] = isset( $raw['rate_limit']['window_seconds'] ) ? max( 60, (int) $raw['rate_limit']['window_seconds'] ) : (int) $defaults['rate_limit']['window_seconds'];

		$out['ajax'] = ! empty( $raw['ajax'] ) ? '1' : '0';

		$out['version'] = UVE_MR_Form::CONFIG_VERSION;

		return $out;
	}
}
