<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminMenuTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['relaypress_test_menu_pages']    = array();
		$GLOBALS['relaypress_test_submenu_pages'] = array();
	}

	public function test_admin_menu_registers_top_level_and_submenus(): void {
		RelayPress_Admin::admin_menu();

		$this->assertCount( 1, $GLOBALS['relaypress_test_menu_pages'] );
		$this->assertCount( 2, $GLOBALS['relaypress_test_submenu_pages'] );

		$menu = $GLOBALS['relaypress_test_menu_pages'][0];
		$this->assertSame( 'RelayPress', $menu['page_title'] );
		$this->assertSame( 'RelayPress', $menu['menu_title'] );
		$this->assertSame( 'relaypress-newsletter', $menu['menu_slug'] );

		$settings = $GLOBALS['relaypress_test_submenu_pages'][0];
		$this->assertSame( 'relaypress-newsletter', $settings['parent_slug'] );
		$this->assertSame( 'Settings', $settings['menu_title'] );
		$this->assertSame( 'relaypress-newsletter', $settings['menu_slug'] );

		$logs = $GLOBALS['relaypress_test_submenu_pages'][1];
		$this->assertSame( 'relaypress-newsletter', $logs['parent_slug'] );
		$this->assertSame( 'Logs', $logs['menu_title'] );
		$this->assertSame( 'relaypress-newsletter-logs', $logs['menu_slug'] );
	}
}
