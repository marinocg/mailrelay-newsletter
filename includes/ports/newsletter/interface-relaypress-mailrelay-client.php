<?php
/**
 * Mailrelay client port.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RelayPress_Mailrelay_Client {
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
	public function subscribe_with_confirmation( string $email, array $group_ids, bool $accepted, string $ip, array $args = array() ): array;

	/**
	 * Update subscriber fields by email.
	 *
	 * @param string $email Subscriber email.
	 * @param array  $args Update payload.
	 * @return array
	 */
	public function update_subscriber_fields_by_email( string $email, array $args = array() ): array;

	/**
	 * Fetch Mailrelay groups.
	 *
	 * @param bool $force_refresh Force refresh cache.
	 * @return array<int,array{id:int,name:string}>
	 */
	public function get_groups( bool $force_refresh = false ): array;
}
