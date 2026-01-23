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
		$config = new RelayPress_WP_Turnstile_Config();
		$this->assertSame( 'site_key', $config->get_site_key() );
	}

	public function test_get_secret_key_from_options(): void {
		$config = new RelayPress_WP_Turnstile_Config();
		$this->assertSame( 'secret_key', $config->get_secret_key() );
	}

	public function test_verify_returns_false_on_missing_token(): void {
		$verifier = new RelayPress_WP_Turnstile_Verifier( new RelayPress_WP_Turnstile_Config() );
		$this->assertFalse( $verifier->verify( '', '203.0.113.10' ) );
	}

	public function test_verify_returns_true_on_success_response(): void {
		$GLOBALS['relaypress_test_http']['POST']['https://challenges.cloudflare.com/turnstile/v0/siteverify'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'success' => true ) ),
		);

		$verifier = new RelayPress_WP_Turnstile_Verifier( new RelayPress_WP_Turnstile_Config() );
		$this->assertTrue( $verifier->verify( 'token', '203.0.113.10' ) );
	}

	public function test_settings_page_shows_test_panel_when_site_key_present(): void {
		$GLOBALS['relaypress_test_options'][ RelayPress_Newsletter::OPT_KEY ]['turnstile_site_key'] = 'site_key';

		ob_start();
		RelayPress_Turnstile_Admin::render_page();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Test configuration', $html );
		$this->assertStringContainsString( 'relaypress-turnstile-test', $html );
	}

	public function test_settings_page_prompts_for_keys_when_missing(): void {
		$GLOBALS['relaypress_test_options'][ RelayPress_Newsletter::OPT_KEY ]['turnstile_site_key'] = '';

		ob_start();
		RelayPress_Turnstile_Admin::render_page();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Add your Turnstile keys above to run the test.', $html );
		$this->assertStringNotContainsString( 'relaypress-turnstile-test', $html );
	}
}
