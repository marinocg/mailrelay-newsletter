<?php
/**
 * Simple service container.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple service container.
 */
final class UVE_MR_Container {
	/**
	 * Build mailrelay client.
	 *
	 * @return UVE_MR_Mailrelay_Client
	 */
	public static function mailrelay_client(): UVE_MR_Mailrelay_Client {
		return new UVE_MR_WP_Mailrelay_Client();
	}

	/**
	 * Build options repository.
	 *
	 * @return UVE_MR_Options_Repository
	 */
	public static function options_repository(): UVE_MR_Options_Repository {
		return new UVE_MR_WP_Options_Repository();
	}

	/**
	 * Build logs repository.
	 *
	 * @return UVE_MR_Logs_Repository
	 */
	public static function logs_repository(): UVE_MR_Logs_Repository {
		return new UVE_MR_WP_Logs_Repository();
	}

	/**
	 * Build Turnstile verifier.
	 *
	 * @return UVE_MR_Turnstile_Verifier
	 */
	public static function turnstile_verifier(): UVE_MR_Turnstile_Verifier {
		return new UVE_MR_WP_Turnstile_Verifier();
	}

	/**
	 * Build rate limiter.
	 *
	 * @return UVE_MR_Rate_Limiter
	 */
	public static function rate_limiter(): UVE_MR_Rate_Limiter {
		return new UVE_MR_WP_Rate_Limiter();
	}

	/**
	 * Build request context.
	 *
	 * @return UVE_MR_Request_Context
	 */
	public static function request_context(): UVE_MR_Request_Context {
		return new UVE_MR_WP_Request_Context();
	}

	/**
	 * Build input sanitizer.
	 *
	 * @return UVE_MR_Input_Sanitizer
	 */
	public static function input_sanitizer(): UVE_MR_Input_Sanitizer {
		return new UVE_MR_WP_Input_Sanitizer();
	}

	/**
	 * Build form repository.
	 *
	 * @return UVE_MR_Form_Repository_Interface
	 */
	public static function form_repository(): UVE_MR_Form_Repository_Interface {
		$repo = apply_filters( 'uve_mr_form_repository', null );
		if ( $repo instanceof UVE_MR_Form_Repository_Interface ) {
			return $repo;
		}
		return new UVE_MR_Form_Repository();
	}

	/**
	 * Build submit use case.
	 *
	 * @return UVE_MR_Submit_Use_Case
	 */
	public static function submit_use_case(): UVE_MR_Submit_Use_Case {
		return new UVE_MR_Submit_Use_Case(
			self::mailrelay_client(),
			self::options_repository(),
			self::logs_repository(),
			self::turnstile_verifier(),
			self::rate_limiter(),
			self::request_context(),
			self::input_sanitizer(),
			self::form_repository()
		);
	}
}
