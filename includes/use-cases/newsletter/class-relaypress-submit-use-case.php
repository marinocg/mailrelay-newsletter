<?php
/**
 * Submit use case.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submission orchestration use case.
 */
final class RelayPress_Submit_Use_Case {
	/**
	 * Options repository.
	 *
	 * @var RelayPress_Options_Repository
	 */
	private $options;

	/**
	 * Rate limiter.
	 *
	 * @var RelayPress_Rate_Limiter
	 */
	private $rate_limiter;

	/**
	 * Request context.
	 *
	 * @var RelayPress_Request_Context
	 */
	private $request_context;

	/**
	 * Input sanitizer.
	 *
	 * @var RelayPress_Input_Sanitizer
	 */
	private $sanitizer;

	/**
	 * Form repository.
	 *
	 * @var RelayPress_Form_Repository_Interface
	 */
	private $forms;

	/**
	 * Subscribe use case.
	 *
	 * @var RelayPress_Subscribe_Use_Case
	 */
	private $subscribe_use_case;

	/**
	 * Create the use case with adapters.
	 *
	 * @param RelayPress_Mailrelay_Client          $mailrelay Mailrelay client.
	 * @param RelayPress_Options_Repository        $options Options repository.
	 * @param RelayPress_Logs_Repository           $logs Logs repository.
	 * @param RelayPress_Rate_Limiter              $rate_limiter Rate limiter.
	 * @param RelayPress_Request_Context           $request_context Request context.
	 * @param RelayPress_Input_Sanitizer           $sanitizer Input sanitizer.
	 * @param RelayPress_Form_Repository_Interface $forms Form repository.
	 * @param RelayPress_Subscribe_Use_Case|null   $subscribe_use_case Subscribe use case.
	 */
	public function __construct(
		RelayPress_Mailrelay_Client $mailrelay,
		RelayPress_Options_Repository $options,
		RelayPress_Logs_Repository $logs,
		RelayPress_Rate_Limiter $rate_limiter,
		RelayPress_Request_Context $request_context,
		RelayPress_Input_Sanitizer $sanitizer,
		RelayPress_Form_Repository_Interface $forms,
		?RelayPress_Subscribe_Use_Case $subscribe_use_case = null
	) {
		$this->options         = $options;
		$this->rate_limiter    = $rate_limiter;
		$this->request_context = $request_context;
		$this->sanitizer       = $sanitizer;
		$this->forms           = $forms;
		if ( $subscribe_use_case ) {
			$this->subscribe_use_case = $subscribe_use_case;
		} else {
			$this->subscribe_use_case = new RelayPress_Subscribe_Use_Case(
				$mailrelay,
				$options,
				$logs,
				$request_context
			);
		}
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

		$hp_raw = $data['relaypress_hp'] ?? '';
		$hp     = $this->sanitizer->sanitize_text( $this->sanitizer->unslash( $hp_raw ) );
		if ( '' !== $hp ) {
			return $this->build_result( 'ok', $messages );
		}

		$email_raw    = $data['subscriber']['email'] ?? '';
		$email        = $this->sanitizer->sanitize_email( $this->sanitizer->unslash( $email_raw ) );
		$accepted_raw = $data['subscriber']['subscribed_with_acceptance'] ?? '';
		$accepted     = $this->sanitizer->sanitize_text( $this->sanitizer->unslash( $accepted_raw ) );
		$accepted     = ( '1' === $accepted );
		$ip           = $this->request_context->get_client_ip();

		if ( ! $email || ! $this->sanitizer->is_email( $email ) ) {
			return $this->build_result( 'ok', $messages );
		}

		if ( ! $accepted ) {
			return $this->build_result( 'consent', $messages );
		}

		$rate_limit_max    = (int) ( $config['rate_limit']['max'] ?? 5 );
		$rate_limit_window = (int) ( $config['rate_limit']['window_seconds'] ?? 3600 );
		$form_key_part     = (int) ( $context['form_id'] ?? 0 );
		$rl_key            = 'relaypress_rl_' . $form_key_part . '_' . md5( $ip . '|' . strtolower( $email ) );
		if ( ! $this->rate_limiter->hit( $rl_key, $rate_limit_max, $rate_limit_window ) ) {
			return $this->build_result( 'ok', $messages );
		}

		$extension_result = apply_filters( 'relaypress_extension_verify_submission', null, $data, $context, $ip );
		if ( is_array( $extension_result ) && 'ok' !== ( $extension_result['status'] ?? 'ok' ) ) {
			return $this->build_result( (string) $extension_result['status'], $messages );
		}

		$group_ids_cfg   = RelayPress_Utils::parse_group_ids( (string) ( $config['destination']['group_ids'] ?? '' ) );
		$group_ids       = $group_ids_cfg;
		$group_ids_raw   = $this->sanitizer->sanitize_text( $this->sanitizer->unslash( $data['relaypress_group_ids'] ?? '' ) );
		$group_ids_input = RelayPress_Utils::parse_group_ids( $group_ids_raw );
		if ( '' !== $group_ids_raw && $group_ids_input ) {
			$group_ids = array_values( array_intersect( $group_ids_input, $group_ids_cfg ) );
		}
		$page_url    = $this->request_context->get_page_url_from_request( $data );
		$global_opts = $this->options->get_options();

		$fields_payload = array();
		$fields_config  = $config['fields'] ?? array();
		$allowed_fields = array( 'name', 'address', 'city', 'state', 'country', 'birthday', 'website', 'phone' );
		$country_hint   = '';
		$phone_payload  = null;
		$phone_strict   = false;
		foreach ( $allowed_fields as $field_key ) {
			$field_cfg = $fields_config[ $field_key ] ?? array();
			if ( ! is_array( $field_cfg ) || empty( $field_cfg['enabled'] ) ) {
				continue;
			}
			if ( empty( $field_cfg['required'] ) ) {
				continue;
			}
			$value_raw = $data['subscriber'][ $field_key ] ?? '';
			$value_raw = $this->sanitizer->unslash( $value_raw );
			if ( 'country' === $field_key ) {
				$country = $this->sanitizer->sanitize_text( $value_raw );
				$country = RelayPress_Utils::normalize_country_code( $country );
				if ( '' === $country ) {
					return $this->build_result( 'error', $messages );
				}
				continue;
			}
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
			$value_raw = $this->sanitizer->unslash( $value_raw );
			if ( '' === $value_raw ) {
				continue;
			}

			if ( 'country' === $field_key ) {
				$country = $this->sanitizer->sanitize_text( $value_raw );
				$country = RelayPress_Utils::normalize_country_code( $country );
				if ( '' !== $country ) {
					$fields_payload['country'] = $country;
					$country_hint              = $country;
				}
				continue;
			}

			if ( 'website' === $field_key ) {
				$value = $this->sanitizer->sanitize_url( $value_raw );
			} else {
				$value = $this->sanitizer->sanitize_text( $value_raw );
			}
			if ( '' === $value ) {
				continue;
			}

			if ( 'phone' === $field_key ) {
				$prefix_raw    = $data['subscriber']['phone_prefix'] ?? '';
				$prefix_raw    = $this->sanitizer->unslash( $prefix_raw );
				$prefix        = $this->sanitizer->sanitize_text( $prefix_raw );
				$phone_payload = array(
					'raw'                  => $value,
					'prefix'               => $prefix,
					'country'              => $country_hint,
					'default_country'      => (string) ( $global_opts['default_phone_country'] ?? '' ),
					'apply_default_prefix' => ( '1' === (string) ( $global_opts['hide_phone_prefix_selector'] ?? '0' ) ),
				);
				$phone_strict  = true;
				continue;
			}

			$fields_payload[ $field_key ] = $value;
		}

		$subscribe_payload = array(
			'email'             => $email,
			'group_ids'         => $group_ids,
			'accepted'          => true,
			'ip'                => $ip,
			'fields'            => $fields_payload,
			'locale'            => $this->resolve_locale( $config ),
			'subscriber_status' => (string) ( $config['destination']['subscriber_status'] ?? 'inactive' ),
			'page_url'          => $page_url,
			'consent_label'     => (string) ( $config['consent']['label'] ?? $global_opts['consent_label'] ?? '' ),
			'consent_context'   => 'form',
		);
		if ( is_array( $phone_payload ) ) {
			$subscribe_payload['phone']        = $phone_payload;
			$subscribe_payload['phone_strict'] = $phone_strict;
			$subscribe_payload['phone_log']    = true;
		}

		$result = $this->subscribe_use_case->execute( $subscribe_payload );
		if ( 'phone' === (string) ( $result['error'] ?? '' ) ) {
			return $this->build_result( 'phone', $messages );
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
		$form_id  = isset( $data['relaypress_form_id'] ) ? (int) $data['relaypress_form_id'] : 0;
		$form     = $form_id ? $this->forms->get( $form_id ) : null;
		$opts     = $this->options->get_options();
		$defaults = RelayPress_Form_Config::defaults( $opts );
		$config   = $form ? $form->merge_config( $defaults ) : $defaults;
		$messages = $config['messages'] ?? $this->default_messages();

		return array(
			'form_id'  => $form_id,
			'config'   => $config,
			'messages' => $messages,
		);
	}

	/**
	 * Resolve the locale payload for Mailrelay.
	 *
	 * @param array $config Form config.
	 * @return string
	 */
	private function resolve_locale( array $config ): string {
		$destination = $config['destination'] ?? array();
		$mode        = (string) ( $destination['locale_mode'] ?? 'inherit' );
		$force_value = (string) ( $destination['locale'] ?? '' );

		$global_opts = $this->options->get_options();
		$fallback    = RelayPress_Utils::normalize_locale( (string) ( $global_opts['locale_fallback'] ?? '' ) );
		if ( '' === $fallback ) {
			$fallback = RelayPress_Utils::default_locale_fallback();
		}
		$global_mode  = (string) ( $global_opts['locale_mode'] ?? 'browser' );
		$global_mode  = in_array( $global_mode, array( 'browser', 'force' ), true ) ? $global_mode : 'browser';
		$global_force = RelayPress_Utils::normalize_locale( (string) ( $global_opts['locale_force'] ?? '' ) );
		if ( '' === $global_force ) {
			$global_force = $fallback;
		}

		if ( 'inherit' === $mode ) {
			$mode = $global_mode;
			if ( 'force' === $mode ) {
				$force_value = $global_force;
			}
		}

		if ( 'force' === $mode ) {
			$forced = RelayPress_Utils::normalize_locale( $force_value );
			if ( '' !== $forced ) {
				return $forced;
			}
			return $global_force;
		}

		$accept = $this->request_context->get_accept_language();
		$auto   = RelayPress_Utils::locale_from_accept_language( $accept );
		return '' !== $auto ? $auto : $fallback;
	}

	/**
	 * Default messages for fallback flows.
	 *
	 * @return array
	 */
	private function default_messages(): array {
		$opts     = $this->options->get_options();
		$defaults = RelayPress_Form_Config::defaults( $opts );
		return $defaults['messages'] ?? array();
	}
}
