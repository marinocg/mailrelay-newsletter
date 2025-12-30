<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AjaxFrontendTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['uve_mr_test_options'] = array(
			UVE_Mailrelay_Newsletter::OPT_KEY => array(
				'title' => 'Title',
				'description' => 'Desc',
				'email_placeholder' => 'Email...',
				'submit_label' => 'Subscribe',
				'group_ids' => '1',
				'privacy_url' => '',
				'consent_label' => 'Consent',
				'ajax_mode' => '0',
			),
		);
		$_GET = array();
	}

	public function test_shortcode_renders_non_ajax_by_default(): void {
		$html = UVE_MR_Frontend::shortcode();
		$this->assertStringContainsString('data-ajax="0"', $html);
		$this->assertStringContainsString('data-ajax-url="https://example.test/wp-admin/admin-ajax.php"', $html);
	}

	public function test_shortcode_renders_ajax_when_enabled(): void {
		$GLOBALS['uve_mr_test_options'][ UVE_Mailrelay_Newsletter::OPT_KEY ]['ajax_mode'] = '1';

		$html = UVE_MR_Frontend::shortcode();
		$this->assertStringContainsString('data-ajax="1"', $html);
	}

	public function test_shortcode_ajax_attribute_overrides_option(): void {
		$GLOBALS['uve_mr_test_options'][ UVE_Mailrelay_Newsletter::OPT_KEY ]['ajax_mode'] = '1';

		$html = UVE_MR_Frontend::shortcode( array( 'ajax' => '0' ) );
		$this->assertStringContainsString('data-ajax="0"', $html);
	}
}
