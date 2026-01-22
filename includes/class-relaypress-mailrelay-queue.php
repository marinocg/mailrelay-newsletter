<?php
/**
 * Mailrelay queue handler.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mailrelay queue handler.
 */
final class RelayPress_Mailrelay_Queue {
	public const ACTION_HOOK = 'relaypress_mailrelay_subscribe';
	public const GROUP       = 'relaypress_mailrelay';

	/**
	 * Register queue handlers.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( self::ACTION_HOOK, array( __CLASS__, 'handle' ), 10, 2 );
	}

	/**
	 * Handle queued subscription.
	 *
	 * @param array $payload Subscription payload.
	 * @param int   $attempt Attempt number.
	 * @return void
	 */
	public static function handle( array $payload, int $attempt = 1 ): void {
		$payload['force_sync'] = true;
		$payload['attempt']    = $attempt;

		RelayPress_Container::subscribe_use_case()->execute( $payload );
	}
}
