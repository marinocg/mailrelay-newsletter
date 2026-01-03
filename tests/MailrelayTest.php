<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MailrelayTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['uve_mr_test_options']    = array(
			UVE_Mailrelay_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
			),
		);
		$GLOBALS['uve_mr_test_http']       = array(
			'POST' => array(),
			'GET'  => array(),
		);
		$GLOBALS['uve_mr_test_transients'] = array();
	}

	public function test_subscribe_returns_error_when_missing_config(): void {
		$GLOBALS['uve_mr_test_options'][ UVE_Mailrelay_Newsletter::OPT_KEY ]['api_base_url'] = '';

		$client = new UVE_MR_WP_Mailrelay_Client();
		$result = $client->subscribe_with_confirmation( 'test@example.com', array( 1 ), true, '203.0.113.10' );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 0, $result['http_code'] );
	}

	public function test_subscribe_active_status_does_not_request_confirmation(): void {
		$GLOBALS['uve_mr_test_http']['POST']['https://api.test/api/v1/subscribers'] = array(
			'response' => array( 'code' => 201 ),
			'body'     => wp_json_encode( array( 'id' => 123 ) ),
		);

		$client = new UVE_MR_WP_Mailrelay_Client();
		$result = $client->subscribe_with_confirmation( 'test@example.com', array( 1 ), true, '203.0.113.10' );

		$this->assertTrue( $result['ok'] );
		$this->assertNull( $result['confirmation_requested_at'] );
		$this->assertNull( $result['confirmation_http_code'] );
	}

	public function test_subscribe_inactive_status_resends_confirmation(): void {
		$GLOBALS['uve_mr_test_options'][ UVE_Mailrelay_Newsletter::OPT_KEY ]['subscriber_status']                 = 'inactive';
		$GLOBALS['uve_mr_test_http']['POST']['https://api.test/api/v1/subscribers']                               = array(
			'response' => array( 'code' => 201 ),
			'body'     => wp_json_encode( array( 'id' => 999 ) ),
		);
		$GLOBALS['uve_mr_test_http']['POST']['https://api.test/api/v1/subscribers/999/resend_confirmation_email'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => 'ok',
		);

		$client = new UVE_MR_WP_Mailrelay_Client();
		$result = $client->subscribe_with_confirmation( 'test@example.com', array( 1 ), true, '203.0.113.10' );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '2025-01-01 00:00:00', $result['confirmation_requested_at'] );
		$this->assertSame( 200, $result['confirmation_http_code'] );
	}
}
