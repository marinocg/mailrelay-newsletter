<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AjaxSubmitTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['relaypress_test_no_exit']   = true;
		$GLOBALS['relaypress_test_nonce_ok']  = true;
		$GLOBALS['relaypress_test_transients'] = array();
		$GLOBALS['relaypress_test_http']       = array( 'POST' => array(), 'GET' => array() );
		$GLOBALS['relaypress_test_last_http']  = array();
		$_SERVER['REMOTE_ADDR']            = '203.0.113.10';

		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1,2',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_site_key'            => 'site_key',
				'turnstile_secret_key'          => 'secret_key',
			),
		);

		$GLOBALS['relaypress_test_http']['POST']['https://challenges.cloudflare.com/turnstile/v0/siteverify'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'success' => true ) ),
		);
		$GLOBALS['relaypress_test_http']['POST']['https://api.test/api/v1/subscribers'] = array(
			'response' => array( 'code' => 201 ),
			'body'     => wp_json_encode( array( 'id' => 123 ) ),
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['relaypress_test_no_exit'], $GLOBALS['relaypress_test_nonce_ok'] );
		$_POST = array();
	}

	private function base_post(): array {
		return array(
			'_wpnonce' => 'nonce',
			'relaypress_hp' => '',
			'cf-turnstile-response' => 'token',
			'relaypress_group_ids' => '1,2',
			'relaypress_page_url' => 'https://example.test/page',
			'subscriber' => array(
				'email' => 'test@example.com',
				'subscribed_with_acceptance' => '1',
			),
		);
	}

	public function test_ajax_success_returns_ok_payload(): void {
		$_POST = $this->base_post();

		ob_start();
		RelayPress_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'ok', $data['data']['status'] );
	}

	public function test_ajax_invalid_nonce_returns_ok_payload(): void {
		$GLOBALS['relaypress_test_nonce_ok'] = false;
		$_POST = $this->base_post();

		ob_start();
		RelayPress_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'ok', $data['data']['status'] );
	}

	public function test_ajax_invalid_email_returns_ok_payload(): void {
		$_POST = $this->base_post();
		$_POST['subscriber']['email'] = 'not-an-email';

		ob_start();
		RelayPress_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'ok', $data['data']['status'] );
	}

	public function test_ajax_honeypot_returns_ok_payload(): void {
		$_POST = $this->base_post();
		$_POST['relaypress_hp'] = 'spam';

		ob_start();
		RelayPress_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'ok', $data['data']['status'] );
	}

	public function test_ajax_consent_missing_returns_error_payload(): void {
		$_POST = $this->base_post();
		$_POST['subscriber']['subscribed_with_acceptance'] = '0';

		ob_start();
		RelayPress_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertFalse( $data['success'] );
		$this->assertSame( 'consent', $data['data']['status'] );
	}

	public function test_ajax_captcha_failure_returns_error_payload(): void {
		$GLOBALS['relaypress_test_http']['POST']['https://challenges.cloudflare.com/turnstile/v0/siteverify'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'success' => false ) ),
		);
		$_POST = $this->base_post();

		ob_start();
		RelayPress_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertFalse( $data['success'] );
		$this->assertSame( 'captcha', $data['data']['status'] );
	}

	public function test_ajax_rate_limit_returns_ok_payload(): void {
		$key = 'relaypress_rl_' . md5( '203.0.113.10|test@example.com' );
		$GLOBALS['relaypress_test_transients'][ $key ] = 5;
		$_POST = $this->base_post();

		ob_start();
		RelayPress_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'ok', $data['data']['status'] );
	}

	public function test_ajax_group_ids_intersect_with_config(): void {
		$_POST                   = $this->base_post();
		$_POST['relaypress_group_ids'] = '2,3';

		ob_start();
		RelayPress_Submit::handle_submit_ajax();
		ob_end_clean();

		$last = $GLOBALS['relaypress_test_last_http'];
		$this->assertSame( 'POST', $last['method'] ?? '' );
		$this->assertSame( 'https://api.test/api/v1/subscribers', $last['url'] ?? '' );
		$payload = json_decode( $last['args']['body'] ?? '', true );
		$this->assertSame( array( 2 ), $payload['group_ids'] ?? array() );
	}

	public function test_ajax_group_ids_falls_back_to_config(): void {
		$_POST = $this->base_post();
		unset( $_POST['relaypress_group_ids'] );

		ob_start();
		RelayPress_Submit::handle_submit_ajax();
		ob_end_clean();

		$last = $GLOBALS['relaypress_test_last_http'];
		$this->assertSame( 'https://api.test/api/v1/subscribers', $last['url'] ?? '' );
		$payload = json_decode( $last['args']['body'] ?? '', true );
		$this->assertSame( array( 1, 2 ), $payload['group_ids'] ?? array() );
	}
}
