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
	 * Turnstile verifier.
	 *
	 * @var RelayPress_Turnstile_Verifier
	 */
	private $turnstile;

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
	 * @param RelayPress_Turnstile_Verifier        $turnstile Turnstile verifier.
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
		RelayPress_Turnstile_Verifier $turnstile,
		RelayPress_Rate_Limiter $rate_limiter,
		RelayPress_Request_Context $request_context,
		RelayPress_Input_Sanitizer $sanitizer,
		RelayPress_Form_Repository_Interface $forms,
		?RelayPress_Subscribe_Use_Case $subscribe_use_case = null
	) {
		$this->options         = $options;
		$this->turnstile       = $turnstile;
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

		if ( $this->turnstile_enabled( $config ) ) {
			$token = $this->sanitizer->sanitize_text( $this->sanitizer->unslash( $data['cf-turnstile-response'] ?? '' ) );
			if ( ! $this->turnstile->verify( $token, $ip ) ) {
				return $this->build_result( 'captcha', $messages );
			}
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
		$phone_meta     = null;
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
				$prefix_raw = $data['subscriber']['phone_prefix'] ?? '';
				$prefix_raw = $this->sanitizer->unslash( $prefix_raw );
				$prefix     = $this->sanitizer->sanitize_text( $prefix_raw );
				if ( '' === $prefix && '1' === (string) ( $global_opts['hide_phone_prefix_selector'] ?? '0' ) ) {
					$default_country = (string) ( $global_opts['default_phone_country'] ?? '' );
					$default_prefix  = RelayPress_Phone_Normalizer::calling_code_for_country( $default_country );
					if ( '' !== $default_prefix ) {
						$prefix = '+' . $default_prefix;
					}
				}
				$combined   = RelayPress_Phone_Normalizer::combine_phone_with_prefix( $value, $prefix );
				$phone_meta = RelayPress_Phone_Normalizer::normalize(
					$combined,
					array(
						'country'           => $country_hint,
						'default_country'   => (string) ( $global_opts['default_phone_country'] ?? '' ),
						'accept_extensions' => false,
						'require_e164'      => false,
					)
				);
				if ( empty( $phone_meta['is_valid'] ) ) {
					if ( '1' === (string) ( $global_opts['send_raw_phone_on_fail'] ?? '0' ) ) {
						$fallback = RelayPress_Phone_Normalizer::compact_raw( $phone_meta['raw_sanitized'] ?? '' );
						if ( '' !== $fallback && ! in_array( $phone_meta['reason'] ?? '', array( RelayPress_Phone_Normalizer::REASON_EXTENSION_NOT_SUPPORTED, RelayPress_Phone_Normalizer::REASON_INVALID_CHARS ), true ) ) {
							$fields_payload['sms_phone']      = $fallback;
							$fields_payload['whatsapp_phone'] = $fallback;
							continue;
						}
					}
					return $this->build_result( 'phone', $messages );
				}
				$fields_payload['sms_phone']      = $phone_meta['normalized'];
				$fields_payload['whatsapp_phone'] = $phone_meta['normalized'];
				continue;
			}

			$fields_payload[ $field_key ] = $value;
		}

		$this->subscribe_use_case->execute(
			array(
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
				'phone_meta'        => $phone_meta,
			)
		);

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
		return RelayPress_Turnstile::is_enabled();
	}
}
