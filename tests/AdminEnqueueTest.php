<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminEnqueueTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['relaypress_test_styles'] = array();
		$GLOBALS['relaypress_test_filters'] = array();
	}

	public function test_upgrade_style_enqueued_on_non_plugin_pages(): void {
		RelayPress_Admin::admin_enqueue( 'index.php' );

		$this->assertArrayHasKey( 'relaypress-admin-upgrade', $GLOBALS['relaypress_test_styles'] );
	}

	public function test_upgrade_style_respects_filter(): void {
		add_filter(
			'relaypress_show_upgrade_ui',
			static function (): bool {
				return false;
			}
		);

		RelayPress_Admin::admin_enqueue( 'index.php' );

		$this->assertArrayNotHasKey( 'relaypress-admin-upgrade', $GLOBALS['relaypress_test_styles'] );
	}
}
