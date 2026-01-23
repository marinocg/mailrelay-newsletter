<?php
/**
 * WordPress Turnstile adapter.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Turnstile verifier adapter.
 */
final class RelayPress_WP_Turnstile_Verifier implements RelayPress_Turnstile_Verifier {
	/**
	 * Turnstile config.
	 *
	 * @var RelayPress_Turnstile_Config
	 */
	private RelayPress_Turnstile_Config $config;

	/**
	 * Create the adapter.
	 *
	 * @param RelayPress_Turnstile_Config|null $config Config adapter.
	 */
	public function __construct( ?RelayPress_Turnstile_Config $config = null ) {
		if ( null === $config ) {
			$config = new RelayPress_WP_Turnstile_Config();
		}
		$this->config = $config;
	}

	/**
	 * Verify a Turnstile token.
	 *
	 * @param string $token Token.
	 * @param string $ip Client IP.
	 * @return bool
	 */
	public function verify( string $token, string $ip ): bool {
		$secret = $this->config->get_secret_key();
		if ( ! $secret || ! $token ) {
			return false;
		}

		$resp = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 10,
				'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
				'body'    => array(
					'secret'   => $secret,
					'response' => $token,
					'remoteip' => $ip,
				),
			)
		);

		if ( is_wp_error( $resp ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $resp );
		$body = wp_remote_retrieve_body( $resp );
		if ( 200 > $code || 300 <= $code ) {
			return false;
		}

		$data = json_decode( $body, true );
		return is_array( $data ) && ! empty( $data['success'] );
	}
}
