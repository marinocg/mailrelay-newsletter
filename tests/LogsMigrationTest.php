<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LogsMigrationTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['relaypress_test_wpdb_tables_like']     = null;
		$GLOBALS['relaypress_test_wpdb_tables_like_map'] = null;
		$GLOBALS['relaypress_test_wpdb_query']           = 0;
		$GLOBALS['relaypress_test_wpdb_last_query']      = null;
	}

	public function test_migrate_legacy_table_renames_when_only_old_exists(): void {
		$new_table = $GLOBALS['wpdb']->prefix . RelayPress_Newsletter::TABLE;
		$old_table = $GLOBALS['wpdb']->prefix . 'uve_mr_newsletter_consent';

		$GLOBALS['relaypress_test_wpdb_tables_like_map'] = array(
			$new_table => null,
			$old_table => $old_table,
		);
		$GLOBALS['relaypress_test_wpdb_query'] = 1;

		RelayPress_Logs::maybe_migrate_legacy_table();

		$this->assertSame(
			"RENAME TABLE `{$old_table}` TO `{$new_table}`",
			$GLOBALS['relaypress_test_wpdb_last_query']
		);
	}
}
