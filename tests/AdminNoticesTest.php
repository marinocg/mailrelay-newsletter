<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminNoticesTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['relaypress_test_current_user_can'] = true;
		$GLOBALS['relaypress_test_user_id']          = 1;
		$GLOBALS['relaypress_test_user_meta']        = array();
		$GLOBALS['relaypress_test_locate_template']  = '';
		$GLOBALS['relaypress_test_current_screen']   = (object) array( 'id' => 'relaypress-newsletter_page_relaypress-newsletter' );
	}

	public function test_template_override_notice_when_outdated(): void {
		$path = sys_get_temp_dir() . '/relaypress-form-override.php';
		file_put_contents(
			$path,
			"<?php\n/**\n * RelayPress Template Version: 1.0.0\n */\n"
		);
		$GLOBALS['relaypress_test_locate_template'] = $path;
		$GLOBALS['relaypress_test_user_meta'][1][ RelayPress_Admin::REVIEW_NOTICE_META ] = '1';

		ob_start();
		RelayPress_Admin::admin_notices();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'Please update the template to version', $output );
		$this->assertStringContainsString( RelayPress_Frontend::TEMPLATE_VERSION, $output );

		@unlink( $path );
	}

	public function test_review_notice_shows_on_plugin_screen(): void {
		$GLOBALS['relaypress_test_locate_template'] = '';

		ob_start();
		RelayPress_Admin::admin_notices();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'Leave a review', $output );
		$this->assertStringContainsString( 'support/plugin/relaypress-newsletter/reviews', $output );
		$this->assertStringContainsString( 'Review later', $output );
	}

	public function test_review_notice_hidden_when_snoozed(): void {
		$GLOBALS['relaypress_test_locate_template'] = '';
		$GLOBALS['relaypress_test_user_meta'][1][ RelayPress_Admin::REVIEW_NOTICE_SNOOZE_META ] = (string) ( time() + DAY_IN_SECONDS );

		ob_start();
		RelayPress_Admin::admin_notices();
		$output = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'Leave a review', $output );
	}
}
