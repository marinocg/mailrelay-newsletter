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
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, UVE_Mailrelay_Newsletter::NONCE ) ) {
			wp_safe_redirect( UVE_MR_Utils::safe_back_url( array( 'uve_mr_status' => 'ok' ) ) );
			exit;
		}

		$result = self::process_submission( $_POST );
		$status = $result['status'] ?? 'ok';

		wp_safe_redirect( UVE_MR_Utils::safe_back_url( array( 'uve_mr_status' => $status ) ) );
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
			self::send_ajax_result( self::build_result( 'ok' ) );
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
		$opts = UVE_Mailrelay_Newsletter::get_options();

		$hp = isset( $data['uve_mr_hp'] ) ? sanitize_text_field( wp_unslash( $data['uve_mr_hp'] ) ) : '';
		if ( '' !== $hp ) {
			return self::build_result( 'ok' );
		}

		$email_raw = isset( $data['subscriber']['email'] ) ? wp_unslash( $data['subscriber']['email'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$email     = sanitize_email( (string) $email_raw );
		$accepted  = isset( $data['subscriber']['subscribed_with_acceptance'] ) ? sanitize_text_field( wp_unslash( $data['subscriber']['subscribed_with_acceptance'] ) ) : '';
		$accepted  = ( '1' === $accepted );
		$ip        = UVE_MR_Utils::get_client_ip();

		if ( ! $email || ! is_email( $email ) ) {
			return self::build_result( 'ok' );
		}

		if ( ! $accepted ) {
			return self::build_result( 'consent' );
		}

		$rl_key = 'uve_mr_rl_' . md5( $ip . '|' . strtolower( $email ) );
		$count  = (int) get_transient( $rl_key );
		if ( (int) $opts['rate_limit_max'] <= $count ) {
			return self::build_result( 'ok' );
		}
		set_transient( $rl_key, $count + 1, (int) $opts['rate_limit_window_seconds'] );

		$token = sanitize_text_field( (string) wp_unslash( $data['cf-turnstile-response'] ?? '' ) );
		if ( ! UVE_MR_Turnstile::verify( $token, $ip ) ) {
			return self::build_result( 'captcha' );
		}

		$group_ids_raw = sanitize_text_field( (string) wp_unslash( $data['uve_mr_group_ids'] ?? $opts['group_ids'] ) );
		$group_ids     = UVE_MR_Utils::parse_group_ids( $group_ids_raw );
		$page_url      = sanitize_text_field( (string) wp_unslash( $data['uve_mr_page_url'] ?? '' ) );

		$result = UVE_MR_Mailrelay::subscribe_with_confirmation( $email, $group_ids, true, $ip );

		if ( '1' === $opts['store_consent_log'] ) {
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

		return self::build_result( 'ok' );
	}

	/**
	 * Build a result payload.
	 *
	 * @param string $status Status.
	 * @return array{status:string,message:string}
	 */
	private static function build_result( string $status ): array {
		$messages = array(
			'ok'      => __( 'Thanks. If the email is valid, you will receive a confirmation email (or you were already subscribed).', 'uve-mailrelay-newsletter' ),
			'captcha' => __( 'Please verify you are human.', 'uve-mailrelay-newsletter' ),
			'consent' => __( 'You must accept the privacy policy.', 'uve-mailrelay-newsletter' ),
			'error'   => __( 'We could not complete the request. Please try again.', 'uve-mailrelay-newsletter' ),
		);

		return array(
			'status'  => $status,
			'message' => $messages[ $status ] ?? $messages['error'],
		);
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
