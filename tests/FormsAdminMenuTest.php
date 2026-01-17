<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FormsAdminMenuTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['relaypress_test_submenu_pages'] = array();
		$GLOBALS['relaypress_test_filters']       = array();
		require_once __DIR__ . '/../includes/admin/forms/class-relaypress-forms-admin.php';
	}

	public function test_forms_menu_uses_plural_label_by_default(): void {
		RelayPress_Forms_Admin::admin_menu();

		$this->assertCount( 1, $GLOBALS['relaypress_test_submenu_pages'] );
		$menu = $GLOBALS['relaypress_test_submenu_pages'][0];
		$this->assertSame( 'Forms', $menu['page_title'] );
		$this->assertSame( 'Forms', $menu['menu_title'] );
		$this->assertSame( 'relaypress-newsletter-forms', $menu['menu_slug'] );
	}

	public function test_forms_menu_uses_multi_label_when_filtered(): void {
		add_filter(
			'relaypress_forms_menu_label',
			static function (): string {
				return 'Forms';
			}
		);

		RelayPress_Forms_Admin::admin_menu();

		$this->assertCount( 1, $GLOBALS['relaypress_test_submenu_pages'] );
		$menu = $GLOBALS['relaypress_test_submenu_pages'][0];
		$this->assertSame( 'Forms', $menu['page_title'] );
		$this->assertSame( 'Forms', $menu['menu_title'] );
	}

	public function test_forms_admin_has_redirect_helper(): void {
		$this->assertTrue( method_exists( RelayPress_Forms_Admin::class, 'redirect_with_notice' ) );
	}
}
