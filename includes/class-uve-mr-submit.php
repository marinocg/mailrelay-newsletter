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
		return UVE_MR_Container::submit_use_case()->process_submission( $data );
	}

	/**
	 * Build a result payload.
	 *
	 * @param string $status Status.
	 * @param array  $messages Messages map.
	 * @return array{status:string,message:string}
	 */
	private static function build_result( string $status, array $messages ): array {
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
