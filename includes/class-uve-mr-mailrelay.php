<?php
/**
 * Mailrelay API helpers.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mailrelay API helpers.
 */
final class UVE_MR_Mailrelay {

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
	public static function subscribe_with_confirmation( string $email, array $group_ids, bool $accepted, string $ip, array $args = array() ): array {
		$opts  = UVE_Mailrelay_Newsletter::get_options();
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

		$fields = $args['fields'] ?? array();
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			foreach ( $fields as $key => $value ) {
				if ( ! is_string( $key ) || '' === $key ) {
					continue;
				}
				$payload[ $key ] = $value;
			}
		}

		$create = self::post_json(
			$base . '/subscribers',
			$token,
			$payload
		);

		$out = array(
			'ok'                        => ! empty( $create['ok'] ) || ! empty( $create['already_exists'] ),
			'http_code'                 => $create['http_code'] ?? null,
			'body'                      => $create['body'] ?? null,
			'confirmation_requested_at' => null,
			'confirmation_http_code'    => null,
			'confirmation_response'     => null,
		);

		if ( 'inactive' !== $status ) {
			return $out;
		}

		if ( 0 < (int) $opts['confirm_resend_max'] ) {
			$k = 'uve_mr_crl_' . md5( $ip . '|' . strtolower( $email ) );
			$c = (int) get_transient( $k );
			if ( $c >= (int) $opts['confirm_resend_max'] ) {
				return $out;
			}
			set_transient( $k, $c + 1, (int) $opts['confirm_resend_window_seconds'] );
		}

		$subscriber_id = null;
		if ( ! empty( $create['ok'] ) ) {
			$subscriber_id = self::extract_subscriber_id_from_body( (string) ( $create['body'] ?? '' ) );
		} elseif ( ! empty( $create['already_exists'] ) ) {
			$subscriber_id = self::find_subscriber_id_by_email_best_effort( $base, $token, $email );
		}

		if ( $subscriber_id ) {
			$confirm                          = self::resend_confirmation( $base, $token, (int) $subscriber_id );
			$out['confirmation_requested_at'] = current_time( 'mysql' );
			$out['confirmation_http_code']    = $confirm['http_code'] ?? null;
			$out['confirmation_response']     = $confirm['body'] ?? null;
		}

		return $out;
	}

	/**
	 * Trigger confirmation resend.
	 *
	 * @param string $base Base API URL.
	 * @param string $token API token.
	 * @param int    $subscriber_id Subscriber ID.
	 * @return array
	 */
	private static function resend_confirmation( string $base, string $token, int $subscriber_id ): array {
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
	private static function extract_subscriber_id_from_body( string $body ): ?int {
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
	private static function find_subscriber_id_by_email_best_effort( string $base, string $token, string $email ): ?int {
		$variants = array(
			$base . '/subscribers?email=' . rawurlencode( $email ),
			$base . '/subscribers?search=' . rawurlencode( $email ),
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
	 * Send a JSON POST request to Mailrelay.
	 *
	 * @param string $url Endpoint URL.
	 * @param string $token API token.
	 * @param array  $payload Payload data.
	 * @return array
	 */
	private static function post_json( string $url, string $token, array $payload ): array {
		$resp = wp_remote_post(
			$url,
			array(
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
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $resp );
		$body = (string) wp_remote_retrieve_body( $resp );

		if ( 200 <= $code && 300 > $code ) {
			return array(
				'ok'        => true,
				'http_code' => $code,
				'body'      => $body,
			);
		}

		if ( 422 === $code ) {
			$data         = json_decode( $body, true );
			$email_errors = $data['errors']['email'] ?? null;
			$text         = is_array( $email_errors ) ? implode( ' ', array_map( 'strval', $email_errors ) ) : ( is_string( $email_errors ) ? $email_errors : '' );
			if ( $text && false !== stripos( $text, 'already exists' ) ) {
				return array(
					'ok'             => false,
					'already_exists' => true,
					'http_code'      => $code,
					'body'           => $body,
				);
			}
		}

		return array(
			'ok'        => false,
			'http_code' => $code,
			'body'      => $body,
		);
	}
}
