<?php
/**
 * Simple service container.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple service container.
 */
final class RelayPress_Container {
	/**
	 * Build mailrelay client.
	 *
	 * @return RelayPress_Mailrelay_Client
	 */
	public static function mailrelay_client(): RelayPress_Mailrelay_Client {
		return new RelayPress_WP_Mailrelay_Client();
	}

	/**
	 * Build options repository.
	 *
	 * @return RelayPress_Options_Repository
	 */
	public static function options_repository(): RelayPress_Options_Repository {
		return new RelayPress_WP_Options_Repository();
	}

	/**
	 * Build logs repository.
	 *
	 * @return RelayPress_Logs_Repository
	 */
	public static function logs_repository(): RelayPress_Logs_Repository {
		return new RelayPress_WP_Logs_Repository();
	}

	/**
	 * Build Turnstile verifier.
	 *
	 * @return RelayPress_Turnstile_Verifier
	 */
	public static function turnstile_verifier(): RelayPress_Turnstile_Verifier {
		return new RelayPress_WP_Turnstile_Verifier();
	}

	/**
	 * Build rate limiter.
	 *
	 * @return RelayPress_Rate_Limiter
	 */
	public static function rate_limiter(): RelayPress_Rate_Limiter {
		return new RelayPress_WP_Rate_Limiter();
	}

	/**
	 * Build request context.
	 *
	 * @return RelayPress_Request_Context
	 */
	public static function request_context(): RelayPress_Request_Context {
		return new RelayPress_WP_Request_Context();
	}

	/**
	 * Build input sanitizer.
	 *
	 * @return RelayPress_Input_Sanitizer
	 */
	public static function input_sanitizer(): RelayPress_Input_Sanitizer {
		return new RelayPress_WP_Input_Sanitizer();
	}

	/**
	 * Build form repository.
	 *
	 * @return RelayPress_Form_Repository_Interface
	 */
	public static function form_repository(): RelayPress_Form_Repository_Interface {
		$repo = apply_filters( 'relaypress_form_repository', null );
		if ( $repo instanceof RelayPress_Form_Repository_Interface ) {
			return $repo;
		}
		return new RelayPress_Form_Repository();
	}

	/**
	 * Build submit use case.
	 *
	 * @return RelayPress_Submit_Use_Case
	 */
	public static function submit_use_case(): RelayPress_Submit_Use_Case {
		return new RelayPress_Submit_Use_Case(
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
