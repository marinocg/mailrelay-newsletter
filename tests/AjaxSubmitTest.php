<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AjaxSubmitTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['uve_mr_test_no_exit']   = true;
		$GLOBALS['uve_mr_test_nonce_ok']  = true;
		$GLOBALS['uve_mr_test_transients'] = array();
		$GLOBALS['uve_mr_test_http']       = array( 'POST' => array(), 'GET' => array() );
		$_SERVER['REMOTE_ADDR']            = '203.0.113.10';

		$GLOBALS['uve_mr_test_options'] = array(
			UVE_Mailrelay_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
			),
		);

		$GLOBALS['uve_mr_test_http']['POST']['https://challenges.cloudflare.com/turnstile/v0/siteverify'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'success' => true ) ),
		);
		$GLOBALS['uve_mr_test_http']['POST']['https://api.test/api/v1/subscribers'] = array(
			'response' => array( 'code' => 201 ),
			'body'     => wp_json_encode( array( 'id' => 123 ) ),
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['uve_mr_test_no_exit'], $GLOBALS['uve_mr_test_nonce_ok'] );
		$_POST = array();
	}

	private function base_post(): array {
		return array(
			'_wpnonce' => 'nonce',
			'uve_mr_hp' => '',
			'cf-turnstile-response' => 'token',
			'uve_mr_group_ids' => '1',
			'uve_mr_page_url' => 'https://example.test/page',
			'subscriber' => array(
				'email' => 'test@example.com',
				'subscribed_with_acceptance' => '1',
			),
		);
	}

	public function test_ajax_success_returns_ok_payload(): void {
		$_POST = $this->base_post();

		ob_start();
		UVE_MR_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'ok', $data['data']['status'] );
	}

	public function test_ajax_invalid_nonce_returns_ok_payload(): void {
		$GLOBALS['uve_mr_test_nonce_ok'] = false;
		$_POST = $this->base_post();

		ob_start();
		UVE_MR_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'ok', $data['data']['status'] );
	}

	public function test_ajax_invalid_email_returns_ok_payload(): void {
		$_POST = $this->base_post();
		$_POST['subscriber']['email'] = 'not-an-email';

		ob_start();
		UVE_MR_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'ok', $data['data']['status'] );
	}

	public function test_ajax_honeypot_returns_ok_payload(): void {
		$_POST = $this->base_post();
		$_POST['uve_mr_hp'] = 'spam';

		ob_start();
		UVE_MR_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'ok', $data['data']['status'] );
	}

	public function test_ajax_consent_missing_returns_error_payload(): void {
		$_POST = $this->base_post();
		$_POST['subscriber']['subscribed_with_acceptance'] = '0';

		ob_start();
		UVE_MR_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertFalse( $data['success'] );
		$this->assertSame( 'consent', $data['data']['status'] );
	}

	public function test_ajax_captcha_failure_returns_error_payload(): void {
		$GLOBALS['uve_mr_test_http']['POST']['https://challenges.cloudflare.com/turnstile/v0/siteverify'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'success' => false ) ),
		);
		$_POST = $this->base_post();

		ob_start();
		UVE_MR_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertFalse( $data['success'] );
		$this->assertSame( 'captcha', $data['data']['status'] );
	}

	public function test_ajax_rate_limit_returns_ok_payload(): void {
		$key = 'uve_mr_rl_' . md5( '203.0.113.10|test@example.com' );
		$GLOBALS['uve_mr_test_transients'][ $key ] = 5;
		$_POST = $this->base_post();

		ob_start();
		UVE_MR_Submit::handle_submit_ajax();
		$output = (string) ob_get_clean();

		$data = json_decode( $output, true );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'ok', $data['data']['status'] );
	}
}
