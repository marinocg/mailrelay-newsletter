<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WidgetsTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		unset( $GLOBALS['relaypress_test_registered_widgets'], $GLOBALS['relaypress_test_use_block_widgets'] );
	}

	public function test_register_widget_skips_when_block_widgets_enabled(): void {
		$GLOBALS['relaypress_test_use_block_widgets'] = true;

		RelayPress_Widgets::register_widget();

		$this->assertEmpty( $GLOBALS['relaypress_test_registered_widgets'] ?? array() );
	}

	public function test_register_widget_registers_when_block_widgets_disabled(): void {
		$GLOBALS['relaypress_test_use_block_widgets'] = false;

		RelayPress_Widgets::register_widget();

		$this->assertSame(
			array( 'RelayPress_Newsletter_Widget' ),
			$GLOBALS['relaypress_test_registered_widgets'] ?? array()
		);
	}
}
