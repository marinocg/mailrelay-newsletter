<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminMenuTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['uve_mr_test_menu_pages']    = array();
		$GLOBALS['uve_mr_test_submenu_pages'] = array();
	}

	public function test_admin_menu_registers_top_level_and_submenus(): void {
		UVE_MR_Admin::admin_menu();

		$this->assertCount( 1, $GLOBALS['uve_mr_test_menu_pages'] );
		$this->assertCount( 2, $GLOBALS['uve_mr_test_submenu_pages'] );

		$menu = $GLOBALS['uve_mr_test_menu_pages'][0];
		$this->assertSame( 'MR4WP', $menu['page_title'] );
		$this->assertSame( 'MR4WP', $menu['menu_title'] );
		$this->assertSame( 'uve-mr-newsletter', $menu['menu_slug'] );

		$settings = $GLOBALS['uve_mr_test_submenu_pages'][0];
		$this->assertSame( 'uve-mr-newsletter', $settings['parent_slug'] );
		$this->assertSame( 'Settings', $settings['menu_title'] );
		$this->assertSame( 'uve-mr-newsletter', $settings['menu_slug'] );

		$logs = $GLOBALS['uve_mr_test_submenu_pages'][1];
		$this->assertSame( 'uve-mr-newsletter', $logs['parent_slug'] );
		$this->assertSame( 'Logs', $logs['menu_title'] );
		$this->assertSame( 'uve-mr-newsletter-logs', $logs['menu_slug'] );
	}
}
