<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LogsTableTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['relaypress_test_wpdb_columns']      = array();
		$GLOBALS['relaypress_test_wpdb_get_results']  = array();
		$GLOBALS['relaypress_test_wpdb_tables_like']  = null;
		$GLOBALS['relaypress_test_wpdb_tables_like_map'] = null;
		$GLOBALS['relaypress_test_wpdb_count']        = 0;
		$GLOBALS['relaypress_test_nonce_ok']          = true;
		$_GET                                     = array();
		$_SERVER                                  = array();
	}

	public function test_logs_table_renders_sort_indicators_and_pagination(): void {
		$table = $GLOBALS['wpdb']->prefix . RelayPress_Newsletter::TABLE;

		$GLOBALS['relaypress_test_wpdb_tables_like'] = $table;
		$GLOBALS['relaypress_test_wpdb_columns']     = array(
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
		$GLOBALS['relaypress_test_wpdb_get_results'] = array(
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
		$GLOBALS['relaypress_test_wpdb_count'] = 45;

		$_GET = array(
			'_wpnonce' => 'testnonce',
			'paged'    => '1',
			'per_page' => '20',
			'orderby'  => 'created_at',
			'order'    => 'asc',
		);

		ob_start();
		RelayPress_Logs::render_logs_table_safe();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'sorting-indicators', $html );
		$this->assertStringContainsString( 'pagination-links', $html );
		$this->assertStringContainsString( 'column-id', $html );
		$this->assertStringContainsString( 'column-source', $html );
		$this->assertStringContainsString( 'RelayPress subscription logs', $html );
	}

	public function test_logs_table_renders_email_primary_first_with_toggle(): void {
		$table = $GLOBALS['wpdb']->prefix . RelayPress_Newsletter::TABLE;

		$GLOBALS['relaypress_test_wpdb_tables_like'] = $table;
		$GLOBALS['relaypress_test_wpdb_columns']     = array(
			array( 'Field' => 'id' ),
			array( 'Field' => 'created_at' ),
			array( 'Field' => 'email' ),
			array( 'Field' => 'accepted' ),
			array( 'Field' => 'ip_hash' ),
			array( 'Field' => 'ip_raw' ),
			array( 'Field' => 'page_url' ),
			array( 'Field' => 'mailrelay_http_code' ),
		);
		$GLOBALS['relaypress_test_wpdb_get_results'] = array(
			array(
				'id'                  => 5,
				'created_at'          => '2026-01-01 10:00:00',
				'email'               => 'mobile@example.test',
				'accepted'            => 1,
				'ip_hash'             => 'abc',
				'ip_raw'              => '',
				'page_url'            => 'https://example.test/source',
				'mailrelay_http_code' => 201,
			),
		);
		$GLOBALS['relaypress_test_wpdb_count'] = 1;

		$_GET = array(
			'_wpnonce' => 'testnonce',
			'paged'    => '1',
			'per_page' => '20',
		);

		ob_start();
		RelayPress_Logs::render_logs_table_safe();
		$html = (string) ob_get_clean();

		$pos_email = strpos( $html, 'column-email' );
		$pos_date  = strpos( $html, 'column-date' );
		$pos_id    = strpos( $html, 'column-id' );

		$this->assertNotFalse( $pos_email );
		$this->assertNotFalse( $pos_date );
		$this->assertNotFalse( $pos_id );
		$this->assertTrue( $pos_email < $pos_date );
		$this->assertTrue( $pos_date < $pos_id );
		$this->assertStringContainsString( 'toggle-row', $html );
		$this->assertStringContainsString( 'aria-expanded="false"', $html );
		$this->assertStringContainsString( 'aria-controls="relaypress-log-row-5"', $html );
		$this->assertStringContainsString( 'data-colname="Email"', $html );
	}
}
