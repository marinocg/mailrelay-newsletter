<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'RelayPress_Premium_Fake' ) ) {
	class RelayPress_Premium_Fake {}
}

final class UtilsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['relaypress_test_is_ssl']  = false;
		$GLOBALS['relaypress_test_referer'] = '';
		$GLOBALS['relaypress_test_filters'] = array();
	}

	public function test_parse_group_ids_filters_and_dedupes(): void {
		$raw = '1, 2, 2, 0, -3, foo, 10';
		$this->assertSame( array( 1, 2, 10 ), RelayPress_Utils::parse_group_ids( $raw ) );
	}

	public function test_defaults_include_ajax_mode(): void {
		$defaults = RelayPress_Newsletter::defaults();
		$this->assertSame( '0', $defaults['ajax_mode'] );
	}

	public function test_current_url_builds_from_server_vars(): void {
		$_SERVER['HTTP_HOST']          = 'example.test';
		$_SERVER['REQUEST_URI']        = '/path?x=1';
		$GLOBALS['relaypress_test_is_ssl'] = true;

		$this->assertSame( 'https://example.test/path?x=1', RelayPress_Utils::current_url() );
	}

	public function test_safe_back_url_adds_query_args(): void {
		$GLOBALS['relaypress_test_referer'] = 'https://example.test/page?foo=1';
		$url                            = RelayPress_Utils::safe_back_url( array( 'relaypress_status' => 'ok' ) );

		$this->assertSame( 'https://example.test/page?foo=1&relaypress_status=ok', $url );
	}

	public function test_get_client_ip_returns_empty_on_invalid(): void {
		$_SERVER['REMOTE_ADDR'] = 'not-an-ip';
		$this->assertSame( '', RelayPress_Utils::get_client_ip() );
	}

	public function test_get_client_ip_returns_valid_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
		$this->assertSame( '203.0.113.10', RelayPress_Utils::get_client_ip() );
	}

	public function test_get_client_ip_prefers_cloudflare_header(): void {
		$_SERVER = array(
			'HTTP_CF_CONNECTING_IP' => '1.2.3.4',
			'HTTP_CF_RAY'           => 'abc123',
			'REMOTE_ADDR'           => '203.0.113.10',
		);

		$this->assertSame( '1.2.3.4', RelayPress_Utils::get_client_ip() );
	}

	public function test_get_client_ip_prefers_public_from_forwarded_for(): void {
		$_SERVER = array(
			'HTTP_X_FORWARDED_FOR' => '10.0.0.1, 1.1.1.1',
			'REMOTE_ADDR'          => '192.168.0.1',
		);

		$this->assertSame( '1.1.1.1', RelayPress_Utils::get_client_ip() );
	}

	public function test_get_client_ip_falls_back_to_first_valid_ip(): void {
		$_SERVER = array(
			'HTTP_X_FORWARDED_FOR' => '10.0.0.1',
			'REMOTE_ADDR'          => '192.168.0.1',
		);

		$this->assertSame( '10.0.0.1', RelayPress_Utils::get_client_ip() );
	}

	public function test_safe_page_url_from_request_uses_candidate_on_same_host(): void {
		$data = array( 'relaypress_page_url' => 'https://example.test/page' );
		$this->assertSame( 'https://example.test/page', RelayPress_Utils::safe_page_url_from_request( $data ) );
	}

	public function test_safe_page_url_from_request_rejects_other_host(): void {
		$data = array( 'relaypress_page_url' => 'https://evil.test/page' );
		$this->assertSame( 'https://example.test/', RelayPress_Utils::safe_page_url_from_request( $data ) );
	}

	public function test_safe_page_url_from_request_falls_back_to_referer(): void {
		$GLOBALS['relaypress_test_referer'] = 'https://example.test/from-ref';
		$data                           = array( 'relaypress_page_url' => 'https://evil.test/page' );
		$this->assertSame( 'https://example.test/from-ref', RelayPress_Utils::safe_page_url_from_request( $data ) );
	}

	public function test_safe_page_url_from_request_handles_array_input(): void {
		$GLOBALS['relaypress_test_referer'] = 'https://example.test/from-ref';
		$data                           = array( 'relaypress_page_url' => array( 'bad' ) );
		$this->assertSame( 'https://example.test/from-ref', RelayPress_Utils::safe_page_url_from_request( $data ) );
	}

	public function test_normalize_country_code_accepts_valid(): void {
		$this->assertSame( 'ES', RelayPress_Utils::normalize_country_code( 'es' ) );
	}

	public function test_normalize_country_code_rejects_invalid(): void {
		$this->assertSame( '', RelayPress_Utils::normalize_country_code( 'XX' ) );
	}

	public function test_plugin_file_points_to_root_file(): void {
		$path = RelayPress_Utils::plugin_file();
		$this->assertStringEndsWith( 'class-relaypress-newsletter.php', $path );
		$this->assertFileExists( $path );
	}

	public function test_premium_installed_false_when_class_missing(): void {
		add_filter( 'relaypress_premium_class', fn() => 'RelayPress_Missing_Premium' );
		$this->assertFalse( RelayPress_Utils::is_premium_installed() );
	}

	public function test_premium_filter_skips_when_not_installed(): void {
		add_filter( 'relaypress_premium_class', fn() => 'RelayPress_Missing_Premium' );
		add_filter( 'relaypress_test_filter', fn() => 'filtered' );

		$this->assertSame(
			'default',
			RelayPress_Utils::premium_filter( 'relaypress_test_filter', 'value', 'default' )
		);
	}

	public function test_premium_filter_applies_when_installed(): void {
		add_filter( 'relaypress_premium_class', fn() => 'RelayPress_Premium_Fake' );
		add_filter( 'relaypress_test_filter', fn() => 'filtered' );

		$this->assertSame(
			'filtered',
			RelayPress_Utils::premium_filter( 'relaypress_test_filter', 'value', 'default' )
		);
	}
}
