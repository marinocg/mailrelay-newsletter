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
		$opts = UVE_Mailrelay_Newsletter::get_options();

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, UVE_Mailrelay_Newsletter::NONCE ) ) {
			wp_safe_redirect( UVE_MR_Utils::safe_back_url( array( 'uve_mr_status' => 'ok' ) ) );
			exit;
		}

		$hp = isset( $_POST['uve_mr_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['uve_mr_hp'] ) ) : '';
		if ( '' !== $hp ) {
			wp_safe_redirect( UVE_MR_Utils::safe_back_url( array( 'uve_mr_status' => 'ok' ) ) );
			exit;
		}

		$email_raw = isset( $_POST['subscriber']['email'] ) ? wp_unslash( $_POST['subscriber']['email'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$email     = sanitize_email( (string) $email_raw );
		$accepted  = isset( $_POST['subscriber']['subscribed_with_acceptance'] ) ? sanitize_text_field( wp_unslash( $_POST['subscriber']['subscribed_with_acceptance'] ) ) : '';
		$accepted  = ( '1' === $accepted );
		$ip        = UVE_MR_Utils::get_client_ip();

		if ( ! $email || ! is_email( $email ) ) {
			wp_safe_redirect( UVE_MR_Utils::safe_back_url( array( 'uve_mr_status' => 'ok' ) ) );
			exit;
		}

		if ( ! $accepted ) {
			wp_safe_redirect( UVE_MR_Utils::safe_back_url( array( 'uve_mr_status' => 'consent' ) ) );
			exit;
		}

		$rl_key = 'uve_mr_rl_' . md5( $ip . '|' . strtolower( $email ) );
		$count  = (int) get_transient( $rl_key );
		if ( (int) $opts['rate_limit_max'] <= $count ) {
			wp_safe_redirect( UVE_MR_Utils::safe_back_url( array( 'uve_mr_status' => 'ok' ) ) );
			exit;
		}
		set_transient( $rl_key, $count + 1, (int) $opts['rate_limit_window_seconds'] );

		$token = sanitize_text_field( (string) wp_unslash( $_POST['cf-turnstile-response'] ?? '' ) );
		if ( ! UVE_MR_Turnstile::verify( $token, $ip ) ) {
			wp_safe_redirect( UVE_MR_Utils::safe_back_url( array( 'uve_mr_status' => 'captcha' ) ) );
			exit;
		}

		$group_ids_raw = sanitize_text_field( (string) wp_unslash( $_POST['uve_mr_group_ids'] ?? $opts['group_ids'] ) );
		$group_ids     = UVE_MR_Utils::parse_group_ids( $group_ids_raw );
		$page_url      = sanitize_text_field( (string) wp_unslash( $_POST['uve_mr_page_url'] ?? '' ) );

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

		wp_safe_redirect( UVE_MR_Utils::safe_back_url( array( 'uve_mr_status' => 'ok' ) ) );
		exit;
	}
}
