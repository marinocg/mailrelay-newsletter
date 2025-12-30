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
}
