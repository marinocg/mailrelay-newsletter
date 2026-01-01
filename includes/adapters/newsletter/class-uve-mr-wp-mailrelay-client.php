<?php
/**
 * WordPress Mailrelay adapter.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Mailrelay client adapter.
 */
final class UVE_MR_WP_Mailrelay_Client implements UVE_MR_Mailrelay_Client {
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
		return UVE_MR_Mailrelay::subscribe_with_confirmation( $email, $group_ids, $accepted, $ip, $args );
	}

	/**
	 * Fetch Mailrelay groups.
	 *
	 * @param bool $force_refresh Force refresh cache.
	 * @return array<int,array{id:int,name:string}>
	 */
	public function get_groups( bool $force_refresh = false ): array {
		return UVE_MR_Mailrelay::get_groups( $force_refresh );
	}
}
