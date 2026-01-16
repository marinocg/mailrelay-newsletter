<?php
/**
 * Form configuration helpers.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form configuration helpers.
 */
final class RelayPress_Form_Config {
	/**
	 * Get default config from global options.
	 *
	 * @param array $opts Global options.
	 * @return array
	 */
	public static function defaults( array $opts ): array {
		return array(
			'version'     => RelayPress_Form::CONFIG_VERSION,
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
				'locale_mode'       => 'inherit',
				'locale'            => (string) ( $opts['locale_force'] ?? '' ),
			),
			'fields'      => array(
				'email'    => array(
					'enabled'     => true,
					'required'    => true,
					'label'       => __( 'Email', 'relaypress-newsletter' ),
					'placeholder' => (string) ( $opts['email_placeholder'] ?? '' ),
					'type'        => 'email',
				),
				'name'     => array(
					'enabled'     => false,
					'required'    => false,
					'label'       => __( 'Name', 'relaypress-newsletter' ),
					'placeholder' => '',
					'type'        => 'text',
				),
				'address'  => array(
					'enabled'     => false,
					'required'    => false,
					'label'       => __( 'Address', 'relaypress-newsletter' ),
					'placeholder' => '',
					'type'        => 'text',
				),
				'city'     => array(
					'enabled'     => false,
					'required'    => false,
					'label'       => __( 'City', 'relaypress-newsletter' ),
					'placeholder' => '',
					'type'        => 'text',
				),
				'state'    => array(
					'enabled'     => false,
					'required'    => false,
					'label'       => __( 'State', 'relaypress-newsletter' ),
					'placeholder' => '',
					'type'        => 'text',
				),
				'country'  => array(
					'enabled'     => false,
					'required'    => false,
					'label'       => __( 'Country', 'relaypress-newsletter' ),
					'placeholder' => __( 'Select a country', 'relaypress-newsletter' ),
					'type'        => 'country',
				),
				'birthday' => array(
					'enabled'     => false,
					'required'    => false,
					'label'       => __( 'Birthday', 'relaypress-newsletter' ),
					'placeholder' => '',
					'type'        => 'date',
				),
				'website'  => array(
					'enabled'     => false,
					'required'    => false,
					'label'       => __( 'Website', 'relaypress-newsletter' ),
					'placeholder' => '',
					'type'        => 'url',
				),
				'phone'    => array(
					'enabled'     => false,
					'required'    => false,
					'label'       => __( 'Phone', 'relaypress-newsletter' ),
					'placeholder' => '',
					'type'        => 'tel',
				),
			),
			'consent'     => array(
				'inherit'     => true,
				'label'       => (string) ( $opts['consent_label'] ?? '' ),
				'privacy_url' => (string) ( $opts['privacy_url'] ?? '' ),
			),
			'turnstile'   => array(
				'mode' => 'inherit',
			),
			'messages'    => array(
				'success' => __( 'Thanks. If the email is valid, you will receive a confirmation email (or you were already subscribed).', 'relaypress-newsletter' ),
				'captcha' => __( 'Please verify you are human.', 'relaypress-newsletter' ),
				'consent' => __( 'You must accept the privacy policy.', 'relaypress-newsletter' ),
				'phone'   => __( 'Please enter a valid phone number.', 'relaypress-newsletter' ),
				'error'   => __( 'We could not complete the request. Please try again.', 'relaypress-newsletter' ),
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
		$merged = array_replace_recursive( $defaults, $stored );
		if ( isset( $defaults['fields'] ) ) {
			$merged['fields'] = self::normalize_fields(
				$merged['fields'] ?? array(),
				$defaults['fields']
			);
		}
		return $merged;
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

		$out['basics']['title']             = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['basics']['title'] ?? $defaults['basics']['title'] ) ) );
		$out['basics']['description']       = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['basics']['description'] ?? $defaults['basics']['description'] ) ) );
		$out['basics']['email_placeholder'] = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['basics']['email_placeholder'] ?? $defaults['basics']['email_placeholder'] ) ) );
		$out['basics']['submit_label']      = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['basics']['submit_label'] ?? $defaults['basics']['submit_label'] ) ) );

		$group_ids_raw = $raw['destination']['group_ids'] ?? $defaults['destination']['group_ids'];
		if ( is_array( $group_ids_raw ) ) {
			$group_ids_clean = array();
			foreach ( $group_ids_raw as $gid ) {
				$gid = sanitize_text_field( (string) $gid );
				if ( '' !== $gid ) {
					$group_ids_clean[] = $gid;
				}
			}
			$out['destination']['group_ids'] = implode( ',', $group_ids_clean );
		} else {
			$out['destination']['group_ids'] = sanitize_text_field( (string) $group_ids_raw );
		}
		$status                                  = sanitize_text_field( (string) ( $raw['destination']['subscriber_status'] ?? $defaults['destination']['subscriber_status'] ) );
		$out['destination']['subscriber_status'] = in_array( $status, array( 'inactive', 'active' ), true ) ? $status : 'inactive';
		$locale_mode                             = sanitize_text_field( (string) ( $raw['destination']['locale_mode'] ?? $defaults['destination']['locale_mode'] ?? 'inherit' ) );
		$out['destination']['locale_mode']       = in_array( $locale_mode, array( 'inherit', 'browser', 'force' ), true ) ? $locale_mode : 'inherit';
		$raw_locale                              = sanitize_text_field( (string) ( $raw['destination']['locale'] ?? $defaults['destination']['locale'] ?? '' ) );
		$locale                                  = RelayPress_Utils::normalize_locale( $raw_locale );
		if ( '' === $locale ) {
			$locale = RelayPress_Utils::normalize_locale( (string) ( $defaults['destination']['locale'] ?? '' ) );
		}
		$out['destination']['locale'] = $locale;

		if ( ! empty( $defaults['fields'] ) && is_array( $defaults['fields'] ) ) {
			foreach ( $defaults['fields'] as $key => $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}
				$raw_field = $raw['fields'][ $key ] ?? array();
				if ( ! is_array( $raw_field ) ) {
					$raw_field = array();
				}
				$enabled = ! empty( $raw_field['enabled'] );
				if ( 'email' === $key ) {
					$enabled = true;
				}
				$out['fields'][ $key ] = array(
					'enabled'     => $enabled,
					'required'    => ! empty( $raw_field['required'] ),
					'label'       => RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw_field['label'] ?? $field['label'] ) ) ),
					'placeholder' => RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw_field['placeholder'] ?? $field['placeholder'] ) ) ),
					'type'        => (string) ( $field['type'] ?? 'text' ),
				);
				if ( 'email' === $key ) {
					if ( '' === $out['fields'][ $key ]['placeholder'] ) {
						$out['fields'][ $key ]['placeholder'] = $out['basics']['email_placeholder'];
					}
					$out['fields'][ $key ]['required'] = true;
				}
			}
		}
		$out['fields'] = self::normalize_fields( $out['fields'], $defaults['fields'] ?? array() );
		if ( ! empty( $out['fields']['email']['placeholder'] ) ) {
			$out['basics']['email_placeholder'] = $out['fields']['email']['placeholder'];
		}

		$out['consent']['inherit']     = ! empty( $raw['consent']['inherit'] );
		$out['consent']['label']       = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['consent']['label'] ?? $defaults['consent']['label'] ) ) );
		$out['consent']['privacy_url'] = esc_url_raw( (string) ( $raw['consent']['privacy_url'] ?? $defaults['consent']['privacy_url'] ) );

		$mode                     = sanitize_text_field( (string) ( $raw['turnstile']['mode'] ?? $defaults['turnstile']['mode'] ?? 'inherit' ) );
		$out['turnstile']['mode'] = in_array( $mode, array( 'inherit', 'on', 'off' ), true ) ? $mode : 'inherit';

		$out['messages']['success'] = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['messages']['success'] ?? $defaults['messages']['success'] ) ) );
		$out['messages']['captcha'] = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['messages']['captcha'] ?? $defaults['messages']['captcha'] ) ) );
		$out['messages']['consent'] = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['messages']['consent'] ?? $defaults['messages']['consent'] ) ) );
		$out['messages']['phone']   = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['messages']['phone'] ?? $defaults['messages']['phone'] ) ) );
		$out['messages']['error']   = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['messages']['error'] ?? $defaults['messages']['error'] ) ) );

		$out['rate_limit']['inherit']        = ! empty( $raw['rate_limit']['inherit'] );
		$out['rate_limit']['max']            = isset( $raw['rate_limit']['max'] ) ? max( 1, (int) $raw['rate_limit']['max'] ) : (int) $defaults['rate_limit']['max'];
		$out['rate_limit']['window_seconds'] = isset( $raw['rate_limit']['window_seconds'] ) ? max( 60, (int) $raw['rate_limit']['window_seconds'] ) : (int) $defaults['rate_limit']['window_seconds'];

		$out['ajax'] = ! empty( $raw['ajax'] ) ? '1' : '0';

		$out['version'] = RelayPress_Form::CONFIG_VERSION;

		if ( isset( $raw['turnstile']['inherit'] ) ) {
			$out['turnstile']['mode'] = ! empty( $raw['turnstile']['inherit'] ) ? 'inherit' : 'off';
		} elseif ( isset( $raw['turnstile']['enabled'] ) ) {
			$out['turnstile']['mode'] = ! empty( $raw['turnstile']['enabled'] ) ? 'on' : 'off';
		}

		return $out;
	}

	/**
	 * Normalize fields against the allowed defaults.
	 *
	 * @param array $fields Current fields config.
	 * @param array $defaults Default fields config.
	 * @return array
	 */
	private static function normalize_fields( array $fields, array $defaults ): array {
		$out = array();
		foreach ( $defaults as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$current = $fields[ $key ] ?? array();
			if ( ! is_array( $current ) ) {
				$current = array();
			}
			$enabled = ! empty( $current['enabled'] );
			if ( 'email' === $key ) {
				$enabled = true;
			}
			$out[ $key ] = array(
				'enabled'     => $enabled,
				'required'    => ! empty( $current['required'] ),
				'label'       => (string) ( $current['label'] ?? $field['label'] ?? '' ),
				'placeholder' => (string) ( $current['placeholder'] ?? $field['placeholder'] ?? '' ),
				'type'        => (string) ( $field['type'] ?? 'text' ),
			);
			if ( 'email' === $key ) {
				$out[ $key ]['required'] = true;
			}
		}
		if ( ! empty( $fields['email']['placeholder'] ) ) {
			$out['email']['placeholder'] = (string) $fields['email']['placeholder'];
		}
		if ( ! empty( $fields['email']['required'] ) ) {
			$out['email']['required'] = true;
		}
		return $out;
	}
}
