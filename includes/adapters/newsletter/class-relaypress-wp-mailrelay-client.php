<?php
/**
 * WordPress Mailrelay adapter.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Mailrelay client adapter.
 */
final class RelayPress_WP_Mailrelay_Client implements RelayPress_Mailrelay_Client {
	/**
	 * Subscribe a user and optionally resend confirmation.
	 *
	 * @param string $email Subscriber email.
	 * @param array  $group_ids Group IDs.
	 * @param bool   $accepted Consent accepted.
	 * @param string $ip Client IP.
	 * @param array  $args Optional overrides.
	 * @return array
	 */
	public function subscribe_with_confirmation( string $email, array $group_ids, bool $accepted, string $ip, array $args = array() ): array {
		$opts  = RelayPress_Newsletter::get_options();
		$base  = rtrim( (string) $opts['api_base_url'], '/' );
		$token = (string) $opts['api_token'];
		if ( ! $base || ! $token ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'body'      => 'Missing config',
			);
		}

		$status = ( 'active' === ( $args['subscriber_status'] ?? $opts['subscriber_status'] ) ) ? 'active' : 'inactive';

		$payload = array(
			'status'                     => $status,
			'email'                      => $email,
			'group_ids'                  => $group_ids,
			'subscribed_with_acceptance' => $accepted,
			'subscribe_ip'               => $ip,
		);
		$locale  = $args['locale'] ?? '';
		if ( is_string( $locale ) ) {
			$locale = RelayPress_Utils::normalize_locale( $locale );
			if ( '' !== $locale ) {
				$payload['locale'] = $locale;
			}
		}

		$fields = $args['fields'] ?? array();
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			foreach ( $fields as $key => $value ) {
				if ( ! is_string( $key ) || '' === $key ) {
					continue;
				}
				$payload[ $key ] = $value;
			}
		}

		$create = $this->request_json_with_phone_retry( 'POST', $base . '/subscribers', $token, $payload );
		$create = $this->apply_already_exists_hint( $create );

		$out = array(
			'ok'                        => ! empty( $create['ok'] ) || ! empty( $create['already_exists'] ),
			'already_exists'            => ! empty( $create['already_exists'] ),
			'http_code'                 => $create['http_code'] ?? null,
			'body'                      => $create['body'] ?? null,
			'retryable'                 => ! empty( $create['retryable'] ),
			'confirmation_requested_at' => null,
			'confirmation_http_code'    => null,
			'confirmation_response'     => null,
		);

		if ( ! empty( $create['already_exists'] ) && ! empty( $args['update_existing'] ) ) {
			$update = $this->update_subscriber_fields_by_email(
				$email,
				array(
					'group_ids'         => $group_ids,
					'fields'            => $args['fields'] ?? array(),
					'subscriber_status' => $args['subscriber_status'] ?? $opts['subscriber_status'] ?? 'inactive',
				)
			);

			return array(
				'ok'                        => ! empty( $update['ok'] ),
				'already_exists'            => true,
				'http_code'                 => $update['http_code'] ?? null,
				'body'                      => $update['body'] ?? null,
				'retryable'                 => ! empty( $update['retryable'] ),
				'confirmation_requested_at' => null,
				'confirmation_http_code'    => null,
				'confirmation_response'     => null,
			);
		}

		if ( 'inactive' !== $status ) {
			$phone_fields = $this->extract_phone_fields_from_fields( $payload );
			if ( ! empty( $phone_fields ) && empty( $create['phone_stripped'] ) ) {
				$subscriber_id = $this->extract_subscriber_id_from_body( (string) ( $create['body'] ?? '' ) );
				$this->update_subscriber_phone_by_email( $base, $token, $email, $phone_fields, $subscriber_id );
			}
			return $out;
		}

		if ( 0 < (int) $opts['confirm_resend_max'] ) {
			$k = 'relaypress_crl_' . md5( $ip . '|' . strtolower( $email ) );
			$c = (int) get_transient( $k );
			if ( $c >= (int) $opts['confirm_resend_max'] ) {
				return $out;
			}
			set_transient( $k, $c + 1, (int) $opts['confirm_resend_window_seconds'] );
		}

		$subscriber_id = null;
		if ( ! empty( $create['ok'] ) ) {
			$subscriber_id = $this->extract_subscriber_id_from_body( (string) ( $create['body'] ?? '' ) );
		} elseif ( ! empty( $create['already_exists'] ) ) {
			$subscriber_id = $this->find_subscriber_id_by_email_best_effort( $base, $token, $email );
		}

		if ( $subscriber_id ) {
			$confirm                          = $this->resend_confirmation( $base, $token, (int) $subscriber_id );
			$out['confirmation_requested_at'] = current_time( 'mysql' );
			$out['confirmation_http_code']    = $confirm['http_code'] ?? null;
			$out['confirmation_response']     = $confirm['body'] ?? null;
		}

		$phone_fields = $this->extract_phone_fields_from_fields( $payload );
		if ( ! empty( $phone_fields ) && empty( $create['phone_stripped'] ) ) {
			$this->update_subscriber_phone_by_email( $base, $token, $email, $phone_fields, $subscriber_id );
		}

		return $out;
	}

	/**
	 * Update subscriber fields by email.
	 *
	 * @param string $email Subscriber email.
	 * @param array  $args Update payload.
	 * @return array
	 */
	public function update_subscriber_fields_by_email( string $email, array $args = array() ): array {
		$opts  = RelayPress_Newsletter::get_options();
		$base  = rtrim( (string) $opts['api_base_url'], '/' );
		$token = (string) $opts['api_token'];
		if ( ! $base || ! $token ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'body'      => 'Missing config',
			);
		}

		$email = function_exists( 'sanitize_email' ) ? sanitize_email( $email ) : trim( $email );
		if ( '' === $email ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'body'      => 'Invalid email',
			);
		}

		$payload   = array(
			'email'  => $email,
			'status' => ( 'active' === ( $args['subscriber_status'] ?? $opts['subscriber_status'] ?? 'inactive' ) ) ? 'active' : 'inactive',
		);
		$group_ids = $args['group_ids'] ?? array();
		if ( is_array( $group_ids ) && ! empty( $group_ids ) ) {
			$payload['group_ids'] = array_values( array_map( 'intval', $group_ids ) );
		}

		$fields       = $args['fields'] ?? array();
		$fields       = is_array( $fields ) ? $fields : array();
		$phone_fields = $this->extract_phone_fields_from_fields( $fields );
		if ( ! empty( $phone_fields ) ) {
			unset( $fields['sms_phone'], $fields['whatsapp_phone'] );
		}
		$phone_payload = $args['phone'] ?? null;
		if ( is_array( $phone_payload ) && class_exists( 'RelayPress_Phone_Normalizer' ) ) {
			$phone_result = RelayPress_Phone_Normalizer::apply_payload_to_fields(
				array(
					'phone'      => $phone_payload,
					'phone_meta' => $args['phone_meta'] ?? null,
				),
				$fields,
				$opts
			);
			if ( '' === (string) ( $phone_result['error'] ?? '' ) ) {
				$fields       = $phone_result['fields'] ?? $fields;
				$phone_fields = $this->extract_phone_fields_from_fields( $fields );
				if ( ! empty( $phone_fields ) ) {
					unset( $fields['sms_phone'], $fields['whatsapp_phone'] );
				}
			}
		}
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			foreach ( $fields as $key => $value ) {
				if ( ! is_string( $key ) || '' === $key ) {
					continue;
				}
				$payload[ $key ] = $value;
			}
		}

		$should_sync = ( 2 !== count( $payload ) );
		if ( ! $should_sync && empty( $phone_fields ) ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'body'      => 'No updates',
			);
		}

		$sync_result = array(
			'ok'        => true,
			'http_code' => 200,
			'body'      => '',
		);
		if ( $should_sync ) {
			$sync_result = $this->request_json_with_phone_retry(
				'POST',
				$base . '/subscribers/sync',
				$token,
				$payload
			);
			if ( empty( $sync_result['ok'] ) ) {
				return $sync_result;
			}
		}

		if ( empty( $phone_fields ) ) {
			return $sync_result;
		}

		$subscriber_id = $this->extract_subscriber_id_from_body( (string) ( $sync_result['body'] ?? '' ) );
		$phone_result  = $this->update_subscriber_phone_by_email( $base, $token, $email, $phone_fields, $subscriber_id );
		if ( empty( $phone_result['ok'] ) ) {
			return $phone_result;
		}

		return $sync_result;
	}

	/**
	 * Fetch Mailrelay groups.
	 *
	 * @param bool $force_refresh Force refresh cache.
	 * @return array<int,array{id:int,name:string}>
	 */
	public function get_groups( bool $force_refresh = false ): array {
		$cache_key = 'relaypress_groups_cache';
		$cached    = get_transient( $cache_key );
		if ( ! $force_refresh && is_array( $cached ) ) {
			return $cached;
		}

		$opts  = RelayPress_Newsletter::get_options();
		$base  = rtrim( (string) $opts['api_base_url'], '/' );
		$token = (string) $opts['api_token'];
		if ( ! $base || ! $token ) {
			return array();
		}

		$url  = $base . '/groups';
		$resp = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'       => 'application/json',
					'X-AUTH-TOKEN' => $token,
				),
			)
		);

		if ( is_wp_error( $resp ) ) {
			return array();
		}

		$code = (int) wp_remote_retrieve_response_code( $resp );
		if ( 200 > $code || 300 <= $code ) {
			return array();
		}

		$body = (string) wp_remote_retrieve_body( $resp );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return array();
		}

		$list = $data['data'] ?? $data['items'] ?? $data;
		if ( ! is_array( $list ) ) {
			return array();
		}

		$groups = array();
		foreach ( $list as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$id   = isset( $row['id'] ) ? (int) $row['id'] : 0;
			$name = isset( $row['name'] ) ? (string) $row['name'] : '';
			if ( $id <= 0 || '' === $name ) {
				continue;
			}
			$groups[] = array(
				'id'   => $id,
				'name' => $name,
			);
		}

		set_transient( $cache_key, $groups, 30 * MINUTE_IN_SECONDS );
		return $groups;
	}

	/**
	 * Trigger confirmation resend.
	 *
	 * @param string $base Base API URL.
	 * @param string $token API token.
	 * @param int    $subscriber_id Subscriber ID.
	 * @return array
	 */
	private function resend_confirmation( string $base, string $token, int $subscriber_id ): array {
		$url  = $base . '/subscribers/' . $subscriber_id . '/resend_confirmation_email';
		$resp = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'       => 'application/json',
					'X-AUTH-TOKEN' => $token,
				),
				'body'    => '',
			)
		);

		if ( is_wp_error( $resp ) ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'body'      => $resp->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $resp );
		$body = (string) wp_remote_retrieve_body( $resp );
		return array(
			'ok'        => ( 200 <= $code && 300 > $code ),
			'http_code' => $code,
			'body'      => $body,
		);
	}

	/**
	 * Extract subscriber ID from a JSON response body.
	 *
	 * @param string $body Response body.
	 * @return int|null
	 */
	private function extract_subscriber_id_from_body( string $body ): ?int {
		if ( ! $body ) {
			return null;
		}
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return null;
		}

		$candidates = array(
			$data['id'] ?? null,
			$data['data']['id'] ?? null,
			$data['subscriber']['id'] ?? null,
		);
		foreach ( $candidates as $c ) {
			if ( is_numeric( $c ) && (int) $c > 0 ) {
				return (int) $c;
			}
		}
		return null;
	}

	/**
	 * Best-effort lookup for subscriber ID by email.
	 *
	 * @param string $base Base API URL.
	 * @param string $token API token.
	 * @param string $email Subscriber email.
	 * @return int|null
	 */
	private function find_subscriber_id_by_email_best_effort( string $base, string $token, string $email ): ?int {
		$variants = array(
			$base . '/subscribers?q[email_eq]=' . rawurlencode( $email ),
			$base . '/subscribers?q[email_cont]=' . rawurlencode( $email ),
		);

		foreach ( $variants as $url ) {
			$resp = wp_remote_get(
				$url,
				array(
					'timeout' => 15,
					'headers' => array(
						'Accept'       => 'application/json',
						'X-AUTH-TOKEN' => $token,
					),
				)
			);
			if ( is_wp_error( $resp ) ) {
				continue;
			}

			$code = (int) wp_remote_retrieve_response_code( $resp );
			if ( 200 > $code || 300 <= $code ) {
				continue;
			}

			$body = (string) wp_remote_retrieve_body( $resp );
			$data = json_decode( $body, true );
			if ( ! is_array( $data ) ) {
				continue;
			}

			$list = null;
			if ( isset( $data[0] ) && is_array( $data[0] ) ) {
				$list = $data;
			} elseif ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
				$list = $data['data'];
			} elseif ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
				$list = $data['items'];
			}

			if ( ! is_array( $list ) ) {
				continue;
			}

			foreach ( $list as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$row_email = strtolower( (string) ( $row['email'] ?? '' ) );
				if ( strtolower( $email ) === $row_email ) {
					$id = $row['id'] ?? null;
					if ( is_numeric( $id ) && (int) $id > 0 ) {
						return (int) $id;
					}
				}
			}
		}
		return null;
	}

	/**
	 * Send a JSON request to Mailrelay.
	 *
	 * @param string $method HTTP method.
	 * @param string $url Endpoint URL.
	 * @param string $token API token.
	 * @param array  $payload Payload data.
	 * @return array
	 */
	private function request_json( string $method, string $url, string $token, array $payload ): array {
		$resp = wp_remote_request(
			$url,
			array(
				'method'  => $method,
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
					'X-AUTH-TOKEN' => $token,
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $resp ) ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'body'      => $resp->get_error_message(),
				'retryable' => true,
			);
		}

		$code      = (int) wp_remote_retrieve_response_code( $resp );
		$body      = (string) wp_remote_retrieve_body( $resp );
		$retryable = ( 429 === $code || ( 500 <= $code && 600 > $code ) );

		$result = array(
			'ok'        => ( 200 <= $code && 300 > $code ),
			'http_code' => $code,
			'body'      => $body,
			'retryable' => $retryable,
		);

		if ( ! $result['ok'] ) {
			$this->debug_log(
				'Mailrelay request failed',
				array(
					'method'       => $method,
					'url'          => $url,
					'http_code'    => $code,
					'body'         => $body,
					'payload_keys' => array_keys( $payload ),
					'email_domain' => $this->email_domain_from_payload( $payload ),
				)
			);
		}

		return $result;
	}

	/**
	 * Detect "already exists" validation errors from Mailrelay.
	 *
	 * @param array $result Request result.
	 * @return array
	 */
	private function apply_already_exists_hint( array $result ): array {
		if ( 422 !== (int) ( $result['http_code'] ?? 0 ) ) {
			return $result;
		}

		$errors = $this->extract_errors( $result );
		if ( ! empty( $errors['email'] ) ) {
			$result['already_exists'] = true;
		}

		return $result;
	}

	/**
	 * Retry request without phone fields when rejected.
	 *
	 * @param string $method HTTP method.
	 * @param string $url Endpoint URL.
	 * @param string $token API token.
	 * @param array  $payload Payload data.
	 * @return array
	 */
	private function request_json_with_phone_retry( string $method, string $url, string $token, array $payload ): array {
		$result        = $this->request_json( $method, $url, $token, $payload );
		$retry_payload = $this->strip_phone_fields_if_needed( $payload, $result );
		if ( null === $retry_payload ) {
			return $result;
		}

		$retry                   = $this->request_json( $method, $url, $token, $retry_payload );
		$retry['phone_stripped'] = true;
		return $retry;
	}

	/**
	 * Remove phone fields from payload when Mailrelay rejects them.
	 *
	 * @param array $payload Original payload.
	 * @param array $result Response result.
	 * @return array|null
	 */
	private function strip_phone_fields_if_needed( array $payload, array $result ): ?array {
		if ( 422 !== (int) ( $result['http_code'] ?? 0 ) ) {
			return null;
		}

		$errors          = $this->extract_errors( $result );
		$has_phone_error = ! empty( $errors['sms_phone'] ) || ! empty( $errors['whatsapp_phone'] );
		if ( ! $has_phone_error ) {
			return null;
		}

		if ( ! isset( $payload['sms_phone'] ) && ! isset( $payload['whatsapp_phone'] ) ) {
			return null;
		}

		unset( $payload['sms_phone'], $payload['whatsapp_phone'] );
		return $payload;
	}

	/**
	 * Extract errors hash from a Mailrelay response.
	 *
	 * @param array $result Response result.
	 * @return array
	 */
	private function extract_errors( array $result ): array {
		$body = (string) ( $result['body'] ?? '' );
		if ( '' === $body ) {
			return array();
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return array();
		}

		$errors = $data['errors'] ?? null;
		return is_array( $errors ) ? $errors : array();
	}

	/**
	 * Extract phone fields from a fields payload.
	 *
	 * @param array $fields Fields payload.
	 * @return array
	 */
	private function extract_phone_fields_from_fields( array $fields ): array {
		$phone_fields = array();
		foreach ( array( 'sms_phone', 'whatsapp_phone' ) as $key ) {
			if ( isset( $fields[ $key ] ) && '' !== (string) $fields[ $key ] ) {
				$phone_fields[ $key ] = $fields[ $key ];
			}
		}
		return $phone_fields;
	}

	/**
	 * Update subscriber phone fields using the update endpoint.
	 *
	 * @param string   $base Base API URL.
	 * @param string   $token API token.
	 * @param string   $email Subscriber email.
	 * @param array    $phone_fields Phone fields.
	 * @param int|null $subscriber_id Subscriber ID.
	 * @return array
	 */
	private function update_subscriber_phone_by_email( string $base, string $token, string $email, array $phone_fields, ?int $subscriber_id = null ): array {
		if ( empty( $phone_fields ) ) {
			return array(
				'ok'        => true,
				'http_code' => 200,
				'body'      => '',
			);
		}

		$id = $subscriber_id;
		if ( ! $id ) {
			$id = $this->find_subscriber_id_by_email_best_effort( $base, $token, $email );
		}
		if ( ! $id ) {
			return array(
				'ok'        => false,
				'http_code' => 404,
				'body'      => 'Subscriber not found',
			);
		}

		return $this->request_json(
			'PATCH',
			$base . '/subscribers/' . (int) $id,
			$token,
			$phone_fields
		);
	}

	/**
	 * Check whether debug logging is enabled.
	 *
	 * @return bool
	 */
	private function is_debug_enabled(): bool {
		return defined( 'RELAYPRESS_DEBUG' ) && RELAYPRESS_DEBUG;
	}

	/**
	 * Extract an email domain from a payload.
	 *
	 * @param array $payload Payload data.
	 * @return string
	 */
	private function email_domain_from_payload( array $payload ): string {
		$email = $payload['email'] ?? '';
		if ( ! is_string( $email ) || '' === $email ) {
			return '';
		}

		$pos = strrpos( $email, '@' );
		if ( false === $pos ) {
			return '';
		}

		return substr( $email, $pos + 1 );
	}

	/**
	 * Log debug info when enabled.
	 *
	 * @param string $message Log message.
	 * @param array  $context Context data.
	 * @return void
	 */
	private function debug_log( string $message, array $context = array() ): void {
		if ( ! $this->is_debug_enabled() ) {
			return;
		}

		$line = '[RelayPress] ' . $message;
		if ( ! empty( $context ) && function_exists( 'wp_json_encode' ) ) {
			$line .= ' ' . wp_json_encode( $context );
		}

		if ( function_exists( 'error_log' ) ) {
			error_log( $line ); // phpcs:ignore
		}
	}
}
