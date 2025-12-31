<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['uve_mr_test_is_ssl']  = false;
		$GLOBALS['uve_mr_test_referer'] = '';
	}

	public function test_parse_group_ids_filters_and_dedupes(): void {
		$raw = '1, 2, 2, 0, -3, foo, 10';
		$this->assertSame( array( 1, 2, 10 ), UVE_MR_Utils::parse_group_ids( $raw ) );
	}

	public function test_defaults_include_ajax_mode(): void {
		$defaults = UVE_Mailrelay_Newsletter::defaults();
		$this->assertSame( '0', $defaults['ajax_mode'] );
	}

	public function test_current_url_builds_from_server_vars(): void {
		$_SERVER['HTTP_HOST']          = 'example.test';
		$_SERVER['REQUEST_URI']        = '/path?x=1';
		$GLOBALS['uve_mr_test_is_ssl'] = true;

		$this->assertSame( 'https://example.test/path?x=1', UVE_MR_Utils::current_url() );
	}

	public function test_safe_back_url_adds_query_args(): void {
		$GLOBALS['uve_mr_test_referer'] = 'https://example.test/page?foo=1';
		$url                            = UVE_MR_Utils::safe_back_url( array( 'uve_mr_status' => 'ok' ) );

		$this->assertSame( 'https://example.test/page?foo=1&uve_mr_status=ok', $url );
	}

	public function test_get_client_ip_returns_empty_on_invalid(): void {
		$_SERVER['REMOTE_ADDR'] = 'not-an-ip';
		$this->assertSame( '', UVE_MR_Utils::get_client_ip() );
	}

	public function test_get_client_ip_returns_valid_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
		$this->assertSame( '203.0.113.10', UVE_MR_Utils::get_client_ip() );
	}

	public function test_get_client_ip_prefers_cloudflare_header(): void {
		$_SERVER = array(
			'HTTP_CF_CONNECTING_IP' => '1.2.3.4',
			'HTTP_CF_RAY'           => 'abc123',
			'REMOTE_ADDR'           => '203.0.113.10',
		);

		$this->assertSame( '1.2.3.4', UVE_MR_Utils::get_client_ip() );
	}

	public function test_get_client_ip_prefers_public_from_forwarded_for(): void {
		$_SERVER = array(
			'HTTP_X_FORWARDED_FOR' => '10.0.0.1, 1.1.1.1',
			'REMOTE_ADDR'          => '192.168.0.1',
		);

		$this->assertSame( '1.1.1.1', UVE_MR_Utils::get_client_ip() );
	}

	public function test_get_client_ip_falls_back_to_first_valid_ip(): void {
		$_SERVER = array(
			'HTTP_X_FORWARDED_FOR' => '10.0.0.1',
			'REMOTE_ADDR'          => '192.168.0.1',
		);

		$this->assertSame( '10.0.0.1', UVE_MR_Utils::get_client_ip() );
	}

	public function test_safe_page_url_from_request_uses_candidate_on_same_host(): void {
		$data = array( 'uve_mr_page_url' => 'https://example.test/page' );
		$this->assertSame( 'https://example.test/page', UVE_MR_Utils::safe_page_url_from_request( $data ) );
	}

	public function test_safe_page_url_from_request_rejects_other_host(): void {
		$data = array( 'uve_mr_page_url' => 'https://evil.test/page' );
		$this->assertSame( 'https://example.test/', UVE_MR_Utils::safe_page_url_from_request( $data ) );
	}

	public function test_safe_page_url_from_request_falls_back_to_referer(): void {
		$GLOBALS['uve_mr_test_referer'] = 'https://example.test/from-ref';
		$data                           = array( 'uve_mr_page_url' => 'https://evil.test/page' );
		$this->assertSame( 'https://example.test/from-ref', UVE_MR_Utils::safe_page_url_from_request( $data ) );
	}

	public function test_safe_page_url_from_request_handles_array_input(): void {
		$GLOBALS['uve_mr_test_referer'] = 'https://example.test/from-ref';
		$data                           = array( 'uve_mr_page_url' => array( 'bad' ) );
		$this->assertSame( 'https://example.test/from-ref', UVE_MR_Utils::safe_page_url_from_request( $data ) );
	}
}
