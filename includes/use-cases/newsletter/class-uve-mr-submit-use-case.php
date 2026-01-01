<?php
/**
 * Submit use case.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submission orchestration use case.
 */
final class UVE_MR_Submit_Use_Case {
	/**
	 * Mailrelay client.
	 *
	 * @var UVE_MR_Mailrelay_Client
	 */
	private $mailrelay;

	/**
	 * Options repository.
	 *
	 * @var UVE_MR_Options_Repository
	 */
	private $options;

	/**
	 * Logs repository.
	 *
	 * @var UVE_MR_Logs_Repository
	 */
	private $logs;

	/**
	 * Turnstile verifier.
	 *
	 * @var UVE_MR_Turnstile_Verifier
	 */
	private $turnstile;

	/**
	 * Rate limiter.
	 *
	 * @var UVE_MR_Rate_Limiter
	 */
	private $rate_limiter;

	/**
	 * Create the use case with adapters.
	 *
	 * @param UVE_MR_Mailrelay_Client   $mailrelay Mailrelay client.
	 * @param UVE_MR_Options_Repository $options Options repository.
	 * @param UVE_MR_Logs_Repository    $logs Logs repository.
	 * @param UVE_MR_Turnstile_Verifier $turnstile Turnstile verifier.
	 * @param UVE_MR_Rate_Limiter       $rate_limiter Rate limiter.
	 */
	public function __construct(
		UVE_MR_Mailrelay_Client $mailrelay,
		UVE_MR_Options_Repository $options,
		UVE_MR_Logs_Repository $logs,
		UVE_MR_Turnstile_Verifier $turnstile,
		UVE_MR_Rate_Limiter $rate_limiter
	) {
		$this->mailrelay    = $mailrelay;
		$this->options      = $options;
		$this->logs         = $logs;
		$this->turnstile    = $turnstile;
		$this->rate_limiter = $rate_limiter;
	}

	/**
	 * Process a submission payload.
	 *
	 * @param array $data Submission payload.
	 * @return array{status:string,message:string}
	 */
	public function process_submission( array $data ): array {
		$context  = $this->resolve_form_context( $data );
		$config   = $context['config'];
		$messages = $context['messages'];

		$hp = isset( $data['uve_mr_hp'] ) ? sanitize_text_field( wp_unslash( $data['uve_mr_hp'] ) ) : '';
		if ( '' !== $hp ) {
			return $this->build_result( 'ok', $messages );
		}

		$email_raw = isset( $data['subscriber']['email'] ) ? wp_unslash( $data['subscriber']['email'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$email     = sanitize_email( (string) $email_raw );
		$accepted  = isset( $data['subscriber']['subscribed_with_acceptance'] ) ? sanitize_text_field( wp_unslash( $data['subscriber']['subscribed_with_acceptance'] ) ) : '';
		$accepted  = ( '1' === $accepted );
		$ip        = UVE_MR_Utils::get_client_ip();

		if ( ! $email || ! is_email( $email ) ) {
			return $this->build_result( 'ok', $messages );
		}

		if ( ! $accepted ) {
			return $this->build_result( 'consent', $messages );
		}

		$rate_limit_max    = (int) ( $config['rate_limit']['max'] ?? 5 );
		$rate_limit_window = (int) ( $config['rate_limit']['window_seconds'] ?? 3600 );
		$form_key_part     = (int) ( $context['form_id'] ?? 0 );
		$rl_key            = 'uve_mr_rl_' . $form_key_part . '_' . md5( $ip . '|' . strtolower( $email ) );
		if ( ! $this->rate_limiter->hit( $rl_key, $rate_limit_max, $rate_limit_window ) ) {
			return $this->build_result( 'ok', $messages );
		}

		if ( $this->turnstile_enabled( $config ) ) {
			$token = sanitize_text_field( (string) wp_unslash( $data['cf-turnstile-response'] ?? '' ) );
			if ( ! $this->turnstile->verify( $token, $ip ) ) {
				return $this->build_result( 'captcha', $messages );
			}
		}

		$group_ids_cfg   = UVE_MR_Utils::parse_group_ids( (string) ( $config['destination']['group_ids'] ?? '' ) );
		$group_ids       = $group_ids_cfg;
		$group_ids_raw   = sanitize_text_field( (string) wp_unslash( $data['uve_mr_group_ids'] ?? '' ) );
		$group_ids_input = UVE_MR_Utils::parse_group_ids( $group_ids_raw );
		if ( '' !== $group_ids_raw && $group_ids_input ) {
			$group_ids = array_values( array_intersect( $group_ids_input, $group_ids_cfg ) );
		}
		$page_url = UVE_MR_Utils::safe_page_url_from_request( $data );

		$fields_payload = array();
		$fields_config  = $config['fields'] ?? array();
		$allowed_fields = array( 'name', 'address', 'city', 'state', 'birthday', 'website', 'phone' );
		foreach ( $allowed_fields as $field_key ) {
			$field_cfg = $fields_config[ $field_key ] ?? array();
			if ( ! is_array( $field_cfg ) || empty( $field_cfg['enabled'] ) ) {
				continue;
			}
			if ( empty( $field_cfg['required'] ) ) {
				continue;
			}
			$value_raw = $data['subscriber'][ $field_key ] ?? '';
			$value_raw = is_scalar( $value_raw ) ? (string) wp_unslash( $value_raw ) : '';
			if ( '' === $value_raw ) {
				return $this->build_result( 'error', $messages );
			}
		}
		foreach ( $allowed_fields as $field_key ) {
			$field_cfg = $fields_config[ $field_key ] ?? array();
			if ( ! is_array( $field_cfg ) || empty( $field_cfg['enabled'] ) ) {
				continue;
			}
			$value_raw = $data['subscriber'][ $field_key ] ?? '';
			$value_raw = is_scalar( $value_raw ) ? (string) wp_unslash( $value_raw ) : '';
			if ( '' === $value_raw ) {
				continue;
			}

			if ( 'website' === $field_key ) {
				$value = esc_url_raw( $value_raw );
			} else {
				$value = sanitize_text_field( $value_raw );
			}
			if ( '' === $value ) {
				continue;
			}

			if ( 'phone' === $field_key ) {
				$phone = preg_replace( '/\s+/', '', $value );
				if ( is_string( $phone ) && preg_match( '/^\+\d{7,15}$/', $phone ) ) {
					$fields_payload['sms_phone']      = $phone;
					$fields_payload['whatsapp_phone'] = $phone;
				}
				continue;
			}

			$fields_payload[ $field_key ] = $value;
		}

		$result = $this->mailrelay->subscribe_with_confirmation(
			$email,
			$group_ids,
			true,
			$ip,
			array(
				'subscriber_status' => (string) ( $config['destination']['subscriber_status'] ?? 'inactive' ),
				'fields'            => $fields_payload,
			)
		);

		$global_opts = $this->options->get_options();
		if ( '1' === (string) ( $global_opts['store_consent_log'] ?? '0' ) ) {
			$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
			$user_agent = substr( $user_agent, 0, 2000 );
			$this->logs->ensure_table();
			$this->logs->store_consent_log(
				array(
					'email'                     => $email,
					'accepted'                  => 1,
					'accepted_at'               => current_time( 'mysql' ),
					'page_url'                  => $page_url,
					'ip'                        => $ip,
					'user_agent'                => $user_agent,
					'mailrelay_http_code'       => $result['http_code'] ?? null,
					'mailrelay_response'        => $result['body'] ?? null,
					'confirmation_requested_at' => $result['confirmation_requested_at'] ?? null,
					'confirmation_http_code'    => $result['confirmation_http_code'] ?? null,
					'confirmation_response'     => $result['confirmation_response'] ?? null,
				)
			);
		}

		return $this->build_result( 'ok', $messages );
	}

	/**
	 * Build a result payload.
	 *
	 * @param string $status Status.
	 * @param array  $messages Messages map.
	 * @return array{status:string,message:string}
	 */
	private function build_result( string $status, array $messages ): array {
		$success_message = $messages['success'] ?? $messages['ok'] ?? $messages['error'] ?? '';
		if ( 'ok' === $status ) {
			$message = $success_message;
		} else {
			$message = $messages[ $status ] ?? $messages['error'] ?? $success_message;
		}

		return array(
			'status'  => $status,
			'message' => $message,
		);
	}

	/**
	 * Resolve form context.
	 *
	 * @param array $data Submission payload.
	 * @return array{form_id:int,config:array,messages:array}
	 */
	private function resolve_form_context( array $data ): array {
		$form_id  = isset( $data['uve_mr_form_id'] ) ? (int) $data['uve_mr_form_id'] : 0;
		$form     = $form_id ? UVE_MR_Form_Use_Cases::get_form( $form_id ) : null;
		$opts     = $this->options->get_options();
		$defaults = UVE_MR_Form_Config::defaults( $opts );
		$config   = $form ? $form->merge_config( $defaults ) : $defaults;
		$messages = $config['messages'] ?? $this->default_messages();

		return array(
			'form_id'  => $form_id,
			'config'   => $config,
			'messages' => $messages,
		);
	}

	/**
	 * Default messages for fallback flows.
	 *
	 * @return array
	 */
	private function default_messages(): array {
		$opts     = $this->options->get_options();
		$defaults = UVE_MR_Form_Config::defaults( $opts );
		return $defaults['messages'] ?? array();
	}

	/**
	 * Determine if Turnstile should be enforced for a config.
	 *
	 * @param array $config Form config.
	 * @return bool
	 */
	private function turnstile_enabled( array $config ): bool {
		$turnstile = $config['turnstile'] ?? array();
		$mode      = (string) ( $turnstile['mode'] ?? 'inherit' );
		if ( 'off' === $mode ) {
			return false;
		}
		if ( 'on' === $mode ) {
			return true;
		}
		return true;
	}
}
