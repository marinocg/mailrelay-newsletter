<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GutenbergBlockTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['uve_mr_test_filters'] = array();
		$GLOBALS['uve_mr_test_actions'] = array();
	}

	public function test_register_block_includes_form_id_attribute_by_default(): void {
		UVE_MR_Gutenberg::register_block();

		$block = $GLOBALS['uve_mr_test_blocks']['uve-mr/newsletter'] ?? array();
		$this->assertArrayHasKey( 'attributes', $block );
		$this->assertArrayHasKey( 'formId', $block['attributes'] );
		$this->assertSame( '', $block['attributes']['formId']['default'] ?? 'fallback' );
	}

	public function test_register_block_includes_form_options(): void {
		$this->set_repo(
			new Gutenberg_Test_Form_Repository(
				array(
					$this->make_form( 2, 'publish' ),
					$this->make_form( 1, 'publish' ),
				)
			)
		);

		UVE_MR_Gutenberg::register_block();

		$data = $GLOBALS['uve_mr_test_script_data'][ UVE_MR_Gutenberg::SCRIPT_HANDLE ]['uveMrNewsletterBlockData'] ?? array();
		$options = $data['formOptions'] ?? array();

		$labels = array();
		foreach ( $options as $option ) {
			$labels[] = $option['label'] ?? '';
		}
		$this->assertContains( 'Form 1', $labels );
		$this->assertContains( 'Form 2', $labels );
	}

	public function test_render_uses_primary_form_when_free(): void {
		$this->set_repo(
			new Gutenberg_Test_Form_Repository(
				array(
					$this->make_form( 3, 'publish' ),
					$this->make_form( 1, 'publish' ),
				)
			)
		);

		$output = UVE_MR_Gutenberg::render_block(
			array(
				'title' => 'Example',
			)
		);

		$this->assertStringContainsString( 'name="uve_mr_form_id" value="1"', $output );
	}

	public function test_render_uses_selected_form_when_form_id_set(): void {
		$this->set_repo(
			new Gutenberg_Test_Form_Repository(
				array(
					$this->make_form( 3, 'publish' ),
					$this->make_form( 1, 'publish' ),
				)
			)
		);

		$output = UVE_MR_Gutenberg::render_block(
			array(
				'formId' => '3',
			)
		);

		$this->assertStringContainsString( 'name="uve_mr_form_id" value="3"', $output );
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

final class Gutenberg_Test_Form_Repository implements UVE_MR_Form_Repository_Interface {
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
