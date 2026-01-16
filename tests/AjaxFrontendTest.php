<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AjaxFrontendTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['uve_mr_test_filters'] = array();
		$GLOBALS['uve_mr_test_actions'] = array();
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
		$this->set_repo(
			new AjaxFrontend_Test_Form_Repository(
				array(
					$this->make_form( 1, 'publish' ),
				)
			)
		);
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

	private function make_form( int $id, string $status ): UVE_MR_Form {
		$form             = new UVE_MR_Form();
		$form->id         = $id;
		$form->name       = 'Form ' . $id;
		$form->status     = $status;
		$form->updated_at = '2025-01-01 00:00:00';
		$form->created_at = '2025-01-01 00:00:00';
		$form->config     = array();
		return $form;
	}

	private function set_repo( UVE_MR_Form_Repository_Interface $repo ): void {
		add_filter(
			'uve_mr_form_repository',
			static function () use ( $repo ) {
				return $repo;
			}
		);
	}
}

final class AjaxFrontend_Test_Form_Repository implements UVE_MR_Form_Repository_Interface {
	/**
	 * @var UVE_MR_Form[]
	 */
	private array $forms;

	/**
	 * @param UVE_MR_Form[] $forms
	 */
	public function __construct( array $forms ) {
		$this->forms = $forms;
	}

	public function get( int $id ): ?UVE_MR_Form {
		foreach ( $this->forms as $form ) {
			if ( $form->id === $id ) {
				return $form;
			}
		}
		return null;
	}

	public function list( array $args = array() ): array {
		$forms = $this->forms;
		$status = $args['post_status'] ?? array();
		$allowed = array();
		if ( is_string( $status ) ) {
			$allowed = array( $status );
		} elseif ( is_array( $status ) ) {
			$allowed = $status;
		}
		if ( $allowed ) {
			$forms = array_values(
				array_filter(
					$forms,
					static function ( UVE_MR_Form $form ) use ( $allowed ): bool {
						return in_array( $form->status, $allowed, true );
					}
				)
			);
		}
		if ( ( $args['orderby'] ?? '' ) === 'ID' ) {
			usort(
				$forms,
				static function ( UVE_MR_Form $a, UVE_MR_Form $b ) use ( $args ): int {
					$cmp = $a->id <=> $b->id;
					$order = strtoupper( (string) ( $args['order'] ?? 'ASC' ) );
					return 'DESC' === $order ? -$cmp : $cmp;
				}
			);
		}
		$limit = (int) ( $args['posts_per_page'] ?? 0 );
		if ( $limit > 0 ) {
			$forms = array_slice( $forms, 0, $limit );
		}
		return $forms;
	}

	public function create( string $name, array $config, string $status ): ?UVE_MR_Form {
		return null;
	}

	public function update( int $id, string $name, array $config, string $status ): ?UVE_MR_Form {
		return null;
	}

	public function trash( int $id ): bool {
		return false;
	}

	public function count_published( ?int $exclude_id = null ): int {
		$count = 0;
		foreach ( $this->forms as $form ) {
			if ( 'publish' !== $form->status ) {
				continue;
			}
			if ( null !== $exclude_id && $form->id === $exclude_id ) {
				continue;
			}
			$count++;
		}
		return $count;
	}
}
