<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TurnstileTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'turnstile_site_key'   => 'site_key',
				'turnstile_secret_key' => 'secret_key',
			),
		);
		$GLOBALS['relaypress_test_http']    = array(
			'POST' => array(),
			'GET'  => array(),
		);
	}

	public function test_get_site_key_from_options(): void {
		$this->assertSame( 'site_key', RelayPress_Turnstile::get_site_key() );
	}

	public function test_get_secret_key_from_options(): void {
		$this->assertSame( 'secret_key', RelayPress_Turnstile::get_secret_key() );
	}

	public function test_verify_returns_false_on_missing_token(): void {
		$this->assertFalse( RelayPress_Turnstile::verify( '', '203.0.113.10' ) );
	}

	public function test_verify_returns_true_on_success_response(): void {
		$GLOBALS['relaypress_test_http']['POST']['https://challenges.cloudflare.com/turnstile/v0/siteverify'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'success' => true ) ),
		);

		$this->assertTrue( RelayPress_Turnstile::verify( 'token', '203.0.113.10' ) );
	}
}
