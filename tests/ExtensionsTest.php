<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ExtensionsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['relaypress_test_filters']       = array();
		$GLOBALS['relaypress_test_actions']       = array();
		$GLOBALS['relaypress_test_submenu_pages'] = array();
		$GLOBALS['relaypress_test_options']       = array();

		RelayPress_Newsletter::init();
	}

	public function test_extensions_registry_includes_turnstile(): void {
		$registry = new RelayPress_WP_Extension_Registry();
		$state    = new RelayPress_WP_Extension_State_Repository();
		$list     = new RelayPress_List_Extensions( $registry, $state );
		$items    = $list->execute();
		$slugs    = array_map(
			static fn( RelayPress_Extension_Status $item ): string => $item->get_extension()->get_slug(),
			$items
		);

		$this->assertContains( 'turnstile', $slugs );
	}

	public function test_extensions_menu_registers_submenu(): void {
		do_action( 'admin_menu' );

		$found = false;
		foreach ( $GLOBALS['relaypress_test_submenu_pages'] as $submenu ) {
			if ( 'relaypress-newsletter-extensions' === ( $submenu['menu_slug'] ?? '' ) ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found );
	}
}
