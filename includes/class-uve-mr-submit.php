<?php
/**
 * Submission handler.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submission handler.
 */
final class UVE_MR_Submit {

	/**
	 * Handle form submission.
	 *
	 * @return void
	 */
	public static function handle_submit(): void {
		$nonce   = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		$form_id = isset( $_POST['uve_mr_form_id'] ) ? (int) $_POST['uve_mr_form_id'] : 0;
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, UVE_Mailrelay_Newsletter::NONCE ) ) {
			$args = array( 'uve_mr_status' => 'ok' );
			if ( $form_id ) {
				$args['uve_mr_form_id'] = $form_id;
			}
			wp_safe_redirect( UVE_MR_Utils::safe_back_url( $args ) );
			exit;
		}

		$result = self::process_submission( $_POST );
		$status = $result['status'] ?? 'ok';

		$args = array( 'uve_mr_status' => $status );
		if ( $form_id ) {
			$args['uve_mr_form_id'] = $form_id;
		}
		wp_safe_redirect( UVE_MR_Utils::safe_back_url( $args ) );
		exit;
	}

	/**
	 * Handle AJAX submission.
	 *
	 * @return void
	 */
	public static function handle_submit_ajax(): void {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, UVE_Mailrelay_Newsletter::NONCE ) ) {
			self::send_ajax_result( self::build_result( 'ok', self::default_messages() ) );
			return;
		}

		$result = self::process_submission( $_POST );
		self::send_ajax_result( $result );
	}

	/**
	 * Process a submission payload.
	 *
	 * @param array $data Submission payload.
	 * @return array{status:string,message:string}
	 */
	private static function process_submission( array $data ): array {
		$context  = self::resolve_form_context( $data );
		$config   = $context['config'];
		$messages = $context['messages'];

		$hp = isset( $data['uve_mr_hp'] ) ? sanitize_text_field( wp_unslash( $data['uve_mr_hp'] ) ) : '';
		if ( '' !== $hp ) {
			return self::build_result( 'ok', $messages );
		}

		$email_raw = isset( $data['subscriber']['email'] ) ? wp_unslash( $data['subscriber']['email'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$email     = sanitize_email( (string) $email_raw );
		$accepted  = isset( $data['subscriber']['subscribed_with_acceptance'] ) ? sanitize_text_field( wp_unslash( $data['subscriber']['subscribed_with_acceptance'] ) ) : '';
		$accepted  = ( '1' === $accepted );
		$ip        = UVE_MR_Utils::get_client_ip();

		if ( ! $email || ! is_email( $email ) ) {
			return self::build_result( 'ok', $messages );
		}

		if ( ! $accepted ) {
			return self::build_result( 'consent', $messages );
		}

		$rate_limit_max    = (int) ( $config['rate_limit']['max'] ?? 5 );
		$rate_limit_window = (int) ( $config['rate_limit']['window_seconds'] ?? 3600 );
		$form_key_part     = (int) ( $context['form_id'] ?? 0 );
		$rl_key            = 'uve_mr_rl_' . $form_key_part . '_' . md5( $ip . '|' . strtolower( $email ) );
		$count             = (int) get_transient( $rl_key );
		if ( $rate_limit_max <= $count ) {
			return self::build_result( 'ok', $messages );
		}
		set_transient( $rl_key, $count + 1, $rate_limit_window );

		if ( self::turnstile_enabled( $config ) ) {
			$token = sanitize_text_field( (string) wp_unslash( $data['cf-turnstile-response'] ?? '' ) );
			if ( ! UVE_MR_Turnstile::verify( $token, $ip ) ) {
				return self::build_result( 'captcha', $messages );
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

		$result = UVE_MR_Mailrelay::subscribe_with_confirmation(
			$email,
			$group_ids,
			true,
			$ip,
			array(
				'subscriber_status' => (string) ( $config['destination']['subscriber_status'] ?? 'inactive' ),
				'fields'            => $fields_payload,
			)
		);

		$global_opts = UVE_Mailrelay_Newsletter::get_options();
		if ( '1' === (string) ( $global_opts['store_consent_log'] ?? '0' ) ) {
			$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
			$user_agent = substr( $user_agent, 0, 2000 );
			UVE_MR_Logs::maybe_create_or_update_table();
			UVE_MR_Logs::store_consent_log_compatible(
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

		return self::build_result( 'ok', $messages );
	}

	/**
	 * Build a result payload.
	 *
	 * @param string $status Status.
	 * @param array  $messages Messages map.
	 * @return array{status:string,message:string}
	 */
	private static function build_result( string $status, array $messages ): array {
		return array(
			'status'  => $status,
			'message' => $messages[ $status ] ?? $messages['error'],
		);
	}

	/**
	 * Resolve form context.
	 *
	 * @param array $data Submission payload.
	 * @return array{form_id:int,config:array,messages:array}
	 */
	private static function resolve_form_context( array $data ): array {
		$form_id  = isset( $data['uve_mr_form_id'] ) ? (int) $data['uve_mr_form_id'] : 0;
		$form     = $form_id ? UVE_MR_Form_Use_Cases::get_form( $form_id ) : null;
		$opts     = UVE_Mailrelay_Newsletter::get_options();
		$defaults = UVE_MR_Form_Config::defaults( $opts );
		$config   = $form ? $form->merge_config( $defaults ) : $defaults;
		$messages = $config['messages'] ?? self::default_messages();

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
	private static function default_messages(): array {
		$opts     = UVE_Mailrelay_Newsletter::get_options();
		$defaults = UVE_MR_Form_Config::defaults( $opts );
		return $defaults['messages'] ?? array();
	}

	/**
	 * Determine if Turnstile should be enforced for a config.
	 *
	 * @param array $config Form config.
	 * @return bool
	 */
	private static function turnstile_enabled( array $config ): bool {
		$turnstile = $config['turnstile'] ?? array();
		if ( ! empty( $turnstile['inherit'] ) ) {
			return true;
		}
		return ! empty( $turnstile['enabled'] );
	}

	/**
	 * Send JSON response for AJAX.
	 *
	 * @param array $result Result payload.
	 * @return void
	 */
	private static function send_ajax_result( array $result ): void {
		$status  = $result['status'] ?? 'ok';
		$message = $result['message'] ?? '';

		if ( 'ok' === $status ) {
			wp_send_json_success(
				array(
					'status'  => $status,
					'message' => $message,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'status'  => $status,
					'message' => $message,
				)
			);
		}
	}
}
