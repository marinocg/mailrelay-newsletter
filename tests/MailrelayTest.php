<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MailrelayTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['relaypress_test_options']    = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
			),
		);
		$GLOBALS['relaypress_test_http']       = array(
			'POST' => array(),
			'GET'  => array(),
			'PATCH' => array(),
		);
		$GLOBALS['relaypress_test_transients'] = array();
	}

	public function test_subscribe_returns_error_when_missing_config(): void {
		$GLOBALS['relaypress_test_options'][ RelayPress_Newsletter::OPT_KEY ]['api_base_url'] = '';

		$client = new RelayPress_WP_Mailrelay_Client();
		$result = $client->subscribe_with_confirmation( 'test@example.com', array( 1 ), true, '203.0.113.10' );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 0, $result['http_code'] );
	}

	public function test_subscribe_active_status_does_not_request_confirmation(): void {
		$GLOBALS['relaypress_test_http']['POST']['https://api.test/api/v1/subscribers'] = array(
			'response' => array( 'code' => 201 ),
			'body'     => wp_json_encode( array( 'id' => 123 ) ),
		);

		$client = new RelayPress_WP_Mailrelay_Client();
		$result = $client->subscribe_with_confirmation( 'test@example.com', array( 1 ), true, '203.0.113.10' );

		$this->assertTrue( $result['ok'] );
		$this->assertNull( $result['confirmation_requested_at'] );
		$this->assertNull( $result['confirmation_http_code'] );
	}

	public function test_subscribe_inactive_status_resends_confirmation(): void {
		$GLOBALS['relaypress_test_options'][ RelayPress_Newsletter::OPT_KEY ]['subscriber_status']                 = 'inactive';
		$GLOBALS['relaypress_test_http']['POST']['https://api.test/api/v1/subscribers']                               = array(
			'response' => array( 'code' => 201 ),
			'body'     => wp_json_encode( array( 'id' => 999 ) ),
		);
		$GLOBALS['relaypress_test_http']['POST']['https://api.test/api/v1/subscribers/999/resend_confirmation_email'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => 'ok',
		);

		$client = new RelayPress_WP_Mailrelay_Client();
		$result = $client->subscribe_with_confirmation( 'test@example.com', array( 1 ), true, '203.0.113.10' );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '2025-01-01 00:00:00', $result['confirmation_requested_at'] );
		$this->assertSame( 200, $result['confirmation_http_code'] );
	}

	public function test_subscribe_marks_already_exists_on_spanish_error(): void {
		$GLOBALS['relaypress_test_http']['POST']['https://api.test/api/v1/subscribers'] = array(
			'response' => array( 'code' => 422 ),
			'body'     => wp_json_encode(
				array(
					'errors' => array(
						'email' => array( 'El suscriptor ya existe' ),
					),
				)
			),
		);

		$client = new RelayPress_WP_Mailrelay_Client();
		$result = $client->subscribe_with_confirmation( 'test@example.com', array( 1 ), true, '203.0.113.10' );

		$this->assertTrue( $result['ok'] );
		$this->assertTrue( $result['already_exists'] );
		$this->assertSame( 422, $result['http_code'] );
	}

	public function test_update_subscriber_fields_by_email_updates_payload(): void {
		$GLOBALS['relaypress_test_http']['POST']['https://api.test/api/v1/subscribers/sync'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => 'ok',
		);

		$client = new RelayPress_WP_Mailrelay_Client();
		$result = $client->update_subscriber_fields_by_email(
			'test@example.com',
			array(
				'group_ids' => array( 10, 11 ),
				'fields'    => array(
					'name'  => 'Ada',
					'city'  => 'London',
				),
			)
		);

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 'POST', $GLOBALS['relaypress_test_last_http']['method'] ?? '' );
		$this->assertSame( 'https://api.test/api/v1/subscribers/sync', $GLOBALS['relaypress_test_last_http']['url'] ?? '' );
	}

	public function test_update_subscriber_fields_by_email_includes_phone_fields(): void {
		$GLOBALS['relaypress_test_http']['GET']['https://api.test/api/v1/subscribers?q[email_eq]=test%40example.com'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode(
				array(
					'data' => array(
						array(
							'id'    => 44,
							'email' => 'test@example.com',
						),
					),
				)
			),
		);
		$GLOBALS['relaypress_test_http']['PATCH']['https://api.test/api/v1/subscribers/44'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => 'ok',
		);

		$client = new RelayPress_WP_Mailrelay_Client();
		$result = $client->update_subscriber_fields_by_email(
			'test@example.com',
			array(
				'fields' => array(
					'sms_phone'      => '+34666666666',
					'whatsapp_phone' => '+34666666666',
				),
			)
		);

		$this->assertTrue( $result['ok'] );

		$body = $GLOBALS['relaypress_test_last_http']['args']['body'] ?? '';
		$data = json_decode( (string) $body, true );
		$this->assertIsArray( $data );
		$this->assertSame( '+34666666666', $data['sms_phone'] ?? '' );
		$this->assertSame( '+34666666666', $data['whatsapp_phone'] ?? '' );
	}

	public function test_subscribe_updates_existing_when_requested(): void {
		$GLOBALS['relaypress_test_http']['POST']['https://api.test/api/v1/subscribers'] = array(
			'response' => array( 'code' => 422 ),
			'body'     => wp_json_encode(
				array(
					'errors' => array(
						'email' => array( 'already exists' ),
					),
				)
			),
		);
		$GLOBALS['relaypress_test_http']['POST']['https://api.test/api/v1/subscribers/sync'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => 'ok',
		);

		$client = new RelayPress_WP_Mailrelay_Client();
		$result = $client->subscribe_with_confirmation(
			'test@example.com',
			array( 1 ),
			true,
			'203.0.113.10',
			array(
				'fields'          => array( 'name' => 'Ada' ),
				'update_existing' => true,
			)
		);

		$this->assertTrue( $result['ok'] );
		$this->assertTrue( $result['already_exists'] );
		$this->assertSame( 200, $result['http_code'] );
	}
}
