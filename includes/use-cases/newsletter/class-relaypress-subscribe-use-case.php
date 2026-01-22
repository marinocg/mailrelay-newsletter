<?php
/**
 * Subscribe use case.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subscription orchestration use case (neutral entrypoint).
 */
final class RelayPress_Subscribe_Use_Case {
	/**
	 * Mailrelay client.
	 *
	 * @var RelayPress_Mailrelay_Client
	 */
	private $mailrelay;

	/**
	 * Options repository.
	 *
	 * @var RelayPress_Options_Repository
	 */
	private $options;

	/**
	 * Logs repository.
	 *
	 * @var RelayPress_Logs_Repository
	 */
	private $logs;

	/**
	 * Request context.
	 *
	 * @var RelayPress_Request_Context
	 */
	private $request_context;

	/**
	 * Task scheduler.
	 *
	 * @var RelayPress_Task_Scheduler
	 */
	private $scheduler;

	/**
	 * Create the use case with adapters.
	 *
	 * @param RelayPress_Mailrelay_Client    $mailrelay Mailrelay client.
	 * @param RelayPress_Options_Repository  $options Options repository.
	 * @param RelayPress_Logs_Repository     $logs Logs repository.
	 * @param RelayPress_Request_Context     $request_context Request context.
	 * @param RelayPress_Task_Scheduler|null $scheduler Task scheduler.
	 */
	public function __construct(
		RelayPress_Mailrelay_Client $mailrelay,
		RelayPress_Options_Repository $options,
		RelayPress_Logs_Repository $logs,
		RelayPress_Request_Context $request_context,
		?RelayPress_Task_Scheduler $scheduler = null
	) {
		$this->mailrelay       = $mailrelay;
		$this->options         = $options;
		$this->logs            = $logs;
		$this->request_context = $request_context;
		$this->scheduler       = $scheduler ? $scheduler : new RelayPress_WP_Task_Scheduler();
	}

	/**
	 * Execute a subscription request.
	 *
	 * @param array $payload Subscription payload.
	 * @return array
	 */
	public function execute( array $payload ): array {
		$email_raw = (string) ( $payload['email'] ?? '' );
		$email     = function_exists( 'sanitize_email' ) ? sanitize_email( $email_raw ) : trim( $email_raw );
		if ( '' === $email || ( function_exists( 'is_email' ) && ! is_email( $email ) ) ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'body'      => 'Invalid email',
			);
		}

		$group_ids = $payload['group_ids'] ?? array();
		if ( ! is_array( $group_ids ) || empty( $group_ids ) ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'body'      => 'Missing group ids',
			);
		}

		$force_sync = ! empty( $payload['force_sync'] );
		$attempt    = isset( $payload['attempt'] ) ? max( 1, (int) $payload['attempt'] ) : 1;

		$accepted = ! empty( $payload['accepted'] );
		$ip       = (string) ( $payload['ip'] ?? $this->request_context->get_client_ip() );
		$fields   = $payload['fields'] ?? array();
		$locale   = (string) ( $payload['locale'] ?? '' );

		$opts         = $this->options->get_options();
		$fields       = is_array( $fields ) ? $fields : array();
		$phone_result = $this->normalize_phone_payload( $payload, $fields, $opts );
		if ( '' !== (string) ( $phone_result['error'] ?? '' ) ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'body'      => 'Invalid phone',
				'error'     => $phone_result['error'],
			);
		}
		$fields     = $phone_result['fields'] ?? $fields;
		$phone_meta = $phone_result['phone_meta'] ?? null;

		$queue_payload = $this->prepare_queue_payload( $payload, $fields, $phone_meta, $opts, $ip );
		if ( $this->should_queue_first( $opts ) && ! $force_sync && $this->scheduler->is_available() ) {
			if ( $this->enqueue_subscription( $queue_payload, 1 ) ) {
				return array(
					'ok'        => true,
					'http_code' => 202,
					'body'      => 'Queued',
					'queued'    => true,
				);
			}
		}

		$args = array(
			'subscriber_status' => (string) ( $payload['subscriber_status'] ?? $opts['subscriber_status'] ?? 'inactive' ),
			'fields'            => $fields,
		);
		if ( ! empty( $payload['update_existing'] ) ) {
			$args['update_existing'] = true;
		}

		$resolved_locale = $this->resolve_locale( $locale, $opts );
		if ( '' !== $resolved_locale ) {
			$args['locale'] = $resolved_locale;
		}

		$result = $this->mailrelay->subscribe_with_confirmation(
			$email,
			array_values( array_map( 'intval', $group_ids ) ),
			$accepted,
			$ip,
			$args
		);

		$max_attempts = $this->max_retry_attempts( $opts );
		if ( $this->should_retry_result( $result ) && $attempt < $max_attempts && $this->scheduler->is_available() ) {
			$delay = $this->retry_delay_seconds( $attempt );
			if ( $this->schedule_retry( $queue_payload, $attempt + 1, $delay ) ) {
				return array_merge(
					$result,
					array(
						'queued'          => true,
						'retry_scheduled' => true,
					)
				);
			}
		}

		$log_payload = $payload;
		if ( ! empty( $payload['phone_log'] ) && is_array( $phone_meta ) ) {
			$log_payload['phone_meta'] = $phone_meta;
		}

		$this->maybe_store_log( $log_payload, $email, $ip, $result );

		if ( ! empty( $result['ok'] ) || ! empty( $result['already_exists'] ) ) {
			/**
			 * Fires when a Mailrelay subscribe attempt succeeds.
			 *
			 * This hook is triggered for both newly created subscriptions and
			 * cases where the subscriber already exists in Mailrelay.
			 *
			 * @param array $payload Subscription payload.
			 * @param array $result Mailrelay result.
			 * @param int   $attempt Attempt number.
			 */
			do_action( 'relaypress_mailrelay_subscribe_success', $payload, $result, $attempt );
		}

		if ( ! empty( $result['already_exists'] ) ) {
			/**
			 * Fires when a Mailrelay subscribe attempt finds an existing subscriber.
			 *
			 * This hook is only triggered when the target email address is already
			 * present in Mailrelay, allowing consumers to distinguish this case
			 * from a newly created subscription.
			 *
			 * @param array $payload Subscription payload.
			 * @param array $result Mailrelay result.
			 * @param int   $attempt Attempt number.
			 */
			do_action( 'relaypress_mailrelay_subscribe_already_exists', $payload, $result, $attempt );
		}

		return $result;
	}

	/**
	 * Resolve locale from payload or global settings.
	 *
	 * @param string $provided Locale provided by caller.
	 * @param array  $opts Global options.
	 * @return string
	 */
	private function resolve_locale( string $provided, array $opts ): string {
		$provided = RelayPress_Utils::normalize_locale( $provided );
		if ( '' !== $provided ) {
			return $provided;
		}

		$mode = (string) ( $opts['locale_mode'] ?? 'browser' );
		if ( 'force' === $mode ) {
			$forced = RelayPress_Utils::normalize_locale( (string) ( $opts['locale_force'] ?? '' ) );
			return '' !== $forced ? $forced : RelayPress_Utils::default_locale_fallback();
		}

		$accept = $this->request_context->get_accept_language();
		$auto   = RelayPress_Utils::locale_from_accept_language( $accept );
		if ( '' !== $auto ) {
			return $auto;
		}

		$fallback = RelayPress_Utils::normalize_locale( (string) ( $opts['locale_fallback'] ?? '' ) );
		return '' !== $fallback ? $fallback : RelayPress_Utils::default_locale_fallback();
	}

	/**
	 * Store a consent log when enabled.
	 *
	 * @param array  $payload Payload data.
	 * @param string $email Email address.
	 * @param string $ip Client IP.
	 * @param array  $result Mailrelay result.
	 * @return void
	 */
	private function maybe_store_log( array $payload, string $email, string $ip, array $result ): void {
		$opts = $this->options->get_options();
		if ( '1' !== (string) ( $opts['store_consent_log'] ?? '0' ) ) {
			return;
		}

		$page_url = (string) ( $payload['page_url'] ?? '' );
		if ( '' === $page_url && isset( $payload['request_data'] ) && is_array( $payload['request_data'] ) ) {
			$page_url = $this->request_context->get_page_url_from_request( $payload['request_data'] );
		}

		$accepted_at = (string) ( $payload['accepted_at'] ?? '' );
		if ( '' === $accepted_at ) {
			$accepted_at = $this->request_context->current_time_mysql();
		}

		$user_agent = (string) ( $payload['user_agent'] ?? '' );
		if ( '' === $user_agent ) {
			$user_agent = $this->request_context->get_user_agent();
		}

		$log_row = array(
			'email'                     => $email,
			'accepted'                  => ! empty( $payload['accepted'] ) ? 1 : 0,
			'accepted_at'               => $accepted_at,
			'page_url'                  => $page_url,
			'ip'                        => $ip,
			'user_agent'                => $user_agent,
			'consent_label'             => (string) ( $payload['consent_label'] ?? '' ),
			'consent_context'           => (string) ( $payload['consent_context'] ?? '' ),
			'mailrelay_http_code'       => $result['http_code'] ?? null,
			'mailrelay_response'        => $result['body'] ?? null,
			'confirmation_requested_at' => $result['confirmation_requested_at'] ?? null,
			'confirmation_http_code'    => $result['confirmation_http_code'] ?? null,
			'confirmation_response'     => $result['confirmation_response'] ?? null,
		);

		$phone_meta = $payload['phone_meta'] ?? null;
		if ( is_array( $phone_meta ) ) {
			$log_row['phone_raw']        = ( '1' === (string) ( $opts['log_phone_raw'] ?? '0' ) ) ? (string) ( $phone_meta['raw_sanitized'] ?? '' ) : null;
			$log_row['phone_normalized'] = $phone_meta['normalized'] ?? null;
			$log_row['phone_valid']      = ! empty( $phone_meta['is_valid'] ) ? 1 : 0;
			$log_row['phone_reason']     = $phone_meta['reason'] ?? null;
			$log_row['phone_country']    = $phone_meta['country_used'] ?? null;
			$log_row['phone_confidence'] = $phone_meta['confidence'] ?? null;
		}

		$this->logs->ensure_table();
		$this->logs->store_consent_log( $log_row );
	}

	/**
	 * Normalize phone data and apply to fields.
	 *
	 * @param array $payload Subscription payload.
	 * @param array $fields Mailrelay fields.
	 * @param array $opts Global options.
	 * @return array{fields:array,phone_meta:?array,error:string}
	 */
	private function apply_phone_normalization( array $payload, array $fields, array $opts ): array {
		if ( ! class_exists( 'RelayPress_Phone_Normalizer' ) ) {
			$phone_meta = isset( $payload['phone_meta'] ) && is_array( $payload['phone_meta'] ) ? $payload['phone_meta'] : null;
			return array(
				'fields'     => $fields,
				'phone_meta' => $phone_meta,
				'error'      => '',
			);
		}

		return RelayPress_Phone_Normalizer::apply_payload_to_fields( $payload, $fields, $opts );
	}

	/**
	 * Normalize phone payload unless already normalized.
	 *
	 * @param array $payload Subscription payload.
	 * @param array $fields Mailrelay fields.
	 * @param array $opts Global options.
	 * @return array{fields:array,phone_meta:?array,error:string}
	 */
	private function normalize_phone_payload( array $payload, array $fields, array $opts ): array {
		if ( ! empty( $payload['phone_normalized'] ) ) {
			$phone_meta = isset( $payload['phone_meta'] ) && is_array( $payload['phone_meta'] ) ? $payload['phone_meta'] : null;
			return array(
				'fields'     => $fields,
				'phone_meta' => $phone_meta,
				'error'      => '',
			);
		}

		return $this->apply_phone_normalization( $payload, $fields, $opts );
	}

	/**
	 * Prepare payload for queued processing.
	 *
	 * @param array      $payload Subscription payload.
	 * @param array      $fields Mailrelay fields.
	 * @param array|null $phone_meta Phone metadata.
	 * @param array      $opts Global options.
	 * @param string     $ip Client IP.
	 * @return array
	 */
	private function prepare_queue_payload( array $payload, array $fields, ?array $phone_meta, array $opts, string $ip ): array {
		$queue_payload                     = $payload;
		$queue_payload['fields']           = $fields;
		$queue_payload['phone_meta']       = ! empty( $payload['phone_log'] ) ? $this->trim_phone_meta_for_queue( $phone_meta, $opts ) : null;
		$queue_payload['phone_normalized'] = true;
		if ( empty( $queue_payload['ip'] ) ) {
			$queue_payload['ip'] = $ip;
		}
		if ( empty( $queue_payload['accepted_at'] ) ) {
			$queue_payload['accepted_at'] = $this->request_context->current_time_mysql();
		}
		if ( empty( $queue_payload['user_agent'] ) ) {
			$queue_payload['user_agent'] = $this->request_context->get_user_agent();
		}
		unset( $queue_payload['phone'], $queue_payload['phone_strict'] );
		return $queue_payload;
	}

	/**
	 * Trim phone metadata before enqueueing.
	 *
	 * @param array|null $phone_meta Phone metadata.
	 * @param array      $opts Global options.
	 * @return array|null
	 */
	private function trim_phone_meta_for_queue( ?array $phone_meta, array $opts ): ?array {
		if ( ! is_array( $phone_meta ) ) {
			return null;
		}

		if ( '1' !== (string) ( $opts['log_phone_raw'] ?? '0' ) ) {
			unset( $phone_meta['raw_sanitized'] );
		}

		return $phone_meta;
	}

	/**
	 * Determine if first attempt should be queued.
	 *
	 * @param array $opts Global options.
	 * @return bool
	 */
	private function should_queue_first( array $opts ): bool {
		return '1' === (string) ( $opts['mailrelay_queue_first'] ?? '0' );
	}

	/**
	 * Decide if a result should be retried.
	 *
	 * @param array $result Mailrelay result.
	 * @return bool
	 */
	private function should_retry_result( array $result ): bool {
		if ( ! empty( $result['ok'] ) ) {
			return false;
		}
		if ( ! empty( $result['retryable'] ) ) {
			return true;
		}
		$code = (int) ( $result['http_code'] ?? 0 );
		return ( 429 === $code || ( 500 <= $code && 600 > $code ) );
	}

	/**
	 * Resolve max retry attempts.
	 *
	 * @param array $opts Global options.
	 * @return int
	 */
	private function max_retry_attempts( array $opts ): int {
		$max = 3;
		/**
		 * Filter Mailrelay retry attempts.
		 *
		 * @param int   $max  Max attempts.
		 * @param array $opts Global options.
		 */
		$max = (int) apply_filters( 'relaypress_mailrelay_retry_attempts', $max, $opts );
		return max( 1, $max );
	}

	/**
	 * Resolve retry delay.
	 *
	 * @param int $attempt Current attempt (retry attempt >= 2).
	 * @return int
	 */
	private function retry_delay_seconds( int $attempt ): int {
		$base  = 60;
		$delay = (int) pow( 2, max( 0, $attempt - 1 ) ) * $base;
		return min( $delay, 3600 );
	}

	/**
	 * Enqueue a subscription job.
	 *
	 * @param array $payload Subscription payload.
	 * @param int   $attempt Attempt number.
	 * @return bool
	 */
	private function enqueue_subscription( array $payload, int $attempt ): bool {
		return $this->scheduler->enqueue(
			RelayPress_Mailrelay_Queue::ACTION_HOOK,
			array( $payload, $attempt ),
			RelayPress_Mailrelay_Queue::GROUP
		);
	}

	/**
	 * Schedule a retry attempt.
	 *
	 * @param array $payload Subscription payload.
	 * @param int   $attempt Attempt number.
	 * @param int   $delay Delay in seconds.
	 * @return bool
	 */
	private function schedule_retry( array $payload, int $attempt, int $delay ): bool {
		return $this->scheduler->schedule(
			time() + max( 1, $delay ),
			RelayPress_Mailrelay_Queue::ACTION_HOOK,
			array( $payload, $attempt ),
			RelayPress_Mailrelay_Queue::GROUP
		);
	}
}
