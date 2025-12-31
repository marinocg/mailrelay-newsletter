<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LogsTableTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['uve_mr_test_wpdb_columns']      = array();
		$GLOBALS['uve_mr_test_wpdb_get_results']  = array();
		$GLOBALS['uve_mr_test_wpdb_tables_like']  = null;
		$GLOBALS['uve_mr_test_wpdb_count']        = 0;
		$GLOBALS['uve_mr_test_nonce_ok']          = true;
		$_GET                                     = array();
		$_SERVER                                  = array();
	}

	public function test_logs_table_renders_sort_indicators_and_pagination(): void {
		$table = $GLOBALS['wpdb']->prefix . UVE_Mailrelay_Newsletter::TABLE;

		$GLOBALS['uve_mr_test_wpdb_tables_like'] = $table;
		$GLOBALS['uve_mr_test_wpdb_columns']     = array(
			array( 'Field' => 'id' ),
			array( 'Field' => 'created_at' ),
			array( 'Field' => 'email' ),
			array( 'Field' => 'accepted' ),
			array( 'Field' => 'ip_hash' ),
			array( 'Field' => 'ip_raw' ),
			array( 'Field' => 'page_url' ),
			array( 'Field' => 'mailrelay_http_code' ),
			array( 'Field' => 'confirmation_requested_at' ),
			array( 'Field' => 'confirmation_http_code' ),
		);
		$GLOBALS['uve_mr_test_wpdb_get_results'] = array(
			array(
				'id'                        => 10,
				'created_at'                => '2025-12-31 08:23:56',
				'email'                     => 'test@example.test',
				'accepted'                  => 1,
				'ip_hash'                   => 'abc',
				'ip_raw'                    => '',
				'page_url'                  => 'https://example.test/source',
				'mailrelay_http_code'       => 201,
				'confirmation_requested_at' => '2025-12-31 08:24:00',
				'confirmation_http_code'    => 204,
			),
		);
		$GLOBALS['uve_mr_test_wpdb_count'] = 45;

		$_GET = array(
			'_wpnonce' => 'testnonce',
			'paged'    => '1',
			'per_page' => '20',
			'orderby'  => 'created_at',
			'order'    => 'asc',
		);

		ob_start();
		UVE_MR_Logs::render_logs_table_safe();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'sorting-indicators', $html );
		$this->assertStringContainsString( 'pagination-links', $html );
		$this->assertStringContainsString( 'column-id', $html );
		$this->assertStringContainsString( 'column-source', $html );
	}
}
