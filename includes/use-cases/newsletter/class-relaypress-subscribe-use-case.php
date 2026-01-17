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
	 * Create the use case with adapters.
	 *
	 * @param RelayPress_Mailrelay_Client   $mailrelay Mailrelay client.
	 * @param RelayPress_Options_Repository $options Options repository.
	 * @param RelayPress_Logs_Repository    $logs Logs repository.
	 * @param RelayPress_Request_Context    $request_context Request context.
	 */
	public function __construct(
		RelayPress_Mailrelay_Client $mailrelay,
		RelayPress_Options_Repository $options,
		RelayPress_Logs_Repository $logs,
		RelayPress_Request_Context $request_context
	) {
		$this->mailrelay       = $mailrelay;
		$this->options         = $options;
		$this->logs            = $logs;
		$this->request_context = $request_context;
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

		$accepted = ! empty( $payload['accepted'] );
		$ip       = (string) ( $payload['ip'] ?? $this->request_context->get_client_ip() );
		$fields   = $payload['fields'] ?? array();
		$locale   = (string) ( $payload['locale'] ?? '' );

		$opts = $this->options->get_options();
		$args = array(
			'subscriber_status' => (string) ( $payload['subscriber_status'] ?? $opts['subscriber_status'] ?? 'inactive' ),
			'fields'            => is_array( $fields ) ? $fields : array(),
		);

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

		$this->maybe_store_log( $payload, $email, $ip, $result );

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

		$log_row = array(
			'email'                     => $email,
			'accepted'                  => ! empty( $payload['accepted'] ) ? 1 : 0,
			'accepted_at'               => $this->request_context->current_time_mysql(),
			'page_url'                  => $page_url,
			'ip'                        => $ip,
			'user_agent'                => $this->request_context->get_user_agent(),
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
}
