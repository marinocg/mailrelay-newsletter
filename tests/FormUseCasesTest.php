<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FormUseCasesTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['relaypress_test_filters'] = array();
		$GLOBALS['relaypress_test_actions'] = array();
		if ( class_exists( 'RelayPress_Test_Premium' ) ) {
			// no-op, class cannot be undefined in PHP.
		}
	}

	public function test_get_form_for_render_uses_lowest_published_when_free(): void {
		$forms = array(
			$this->make_form( 3, 'publish' ),
			$this->make_form( 2, 'draft' ),
			$this->make_form( 1, 'publish' ),
		);
		$this->set_repo( new Test_Single_Form_Repository( $forms ) );

		$form = RelayPress_Form_Use_Cases::get_form_for_render( 999 );

		$this->assertNotNull( $form );
		$this->assertSame( 1, $form->id );
	}

	public function test_get_form_for_render_uses_requested_id_when_premium(): void {
		$forms = array(
			$this->make_form( 5, 'publish' ),
			$this->make_form( 1, 'publish' ),
		);
		$this->set_repo( new FormUseCases_Test_Form_Repository( $forms ) );

		$form = RelayPress_Form_Use_Cases::get_form_for_render( 5 );

		$this->assertNotNull( $form );
		$this->assertSame( 5, $form->id );
	}

	public function test_primary_form_for_admin_skips_drafts(): void {
		$forms = array(
			$this->make_form( 4, 'draft' ),
			$this->make_form( 2, 'draft' ),
		);
		$this->set_repo( new FormUseCases_Test_Form_Repository( $forms ) );

		$form = RelayPress_Form_Use_Cases::get_primary_form_for_admin();

		$this->assertNotNull( $form );
		$this->assertSame( 2, $form->id );
	}

	public function test_get_form_for_render_returns_null_without_published_forms(): void {
		$forms = array(
			$this->make_form( 9, 'draft' ),
			$this->make_form( 3, 'draft' ),
		);
		$this->set_repo( new Test_Single_Form_Repository( $forms ) );

		$form = RelayPress_Form_Use_Cases::get_form_for_render( null );

		$this->assertNull( $form );
	}

	private function make_form( int $id, string $status ): RelayPress_Form {
		$form            = new RelayPress_Form();
		$form->id        = $id;
		$form->name      = 'Form ' . $id;
		$form->status    = $status;
		$form->updated_at = '2025-01-01 00:00:00';
		$form->created_at = '2025-01-01 00:00:00';
		$form->config     = array();
		return $form;
	}

	private function set_repo( RelayPress_Form_Repository_Interface $repo ): void {
		add_filter(
			'relaypress_form_repository',
			static function () use ( $repo ) {
				return $repo;
			}
		);
	}

}

final class FormUseCases_Test_Form_Repository implements RelayPress_Form_Repository_Interface {
	/**
	 * @var RelayPress_Form[]
	 */
	private array $forms;

	/**
	 * @param RelayPress_Form[] $forms
	 */
	public function __construct( array $forms ) {
		$this->forms = $forms;
	}

	public function get( int $id ): ?RelayPress_Form {
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
					static function ( RelayPress_Form $form ) use ( $allowed ): bool {
						return in_array( $form->status, $allowed, true );
					}
				)
			);
		}
		if ( ( $args['orderby'] ?? '' ) === 'ID' ) {
			usort(
				$forms,
				static function ( RelayPress_Form $a, RelayPress_Form $b ) use ( $args ): int {
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

	public function create( string $name, array $config, string $status ): ?RelayPress_Form {
		return null;
	}

	public function update( int $id, string $name, array $config, string $status ): ?RelayPress_Form {
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

final class Test_Single_Form_Repository implements RelayPress_Form_Repository_Interface {
	private FormUseCases_Test_Form_Repository $repo;

	/**
	 * @param RelayPress_Form[] $forms
	 */
	public function __construct( array $forms ) {
		$this->repo = new FormUseCases_Test_Form_Repository( $forms );
	}

	public function get( int $id ): ?RelayPress_Form {
		return $this->get_primary();
	}

	public function list( array $args = array() ): array {
		$primary = $this->get_primary();
		return $primary ? array( $primary ) : array();
	}

	public function create( string $name, array $config, string $status ): ?RelayPress_Form {
		return $this->repo->create( $name, $config, $status );
	}

	public function update( int $id, string $name, array $config, string $status ): ?RelayPress_Form {
		return $this->repo->update( $id, $name, $config, $status );
	}

	public function trash( int $id ): bool {
		return $this->repo->trash( $id );
	}

	public function count_published( ?int $exclude_id = null ): int {
		return $this->repo->count_published( $exclude_id );
	}

	private function get_primary(): ?RelayPress_Form {
		$forms = $this->repo->list(
			array(
				'post_status'    => array( 'publish' ),
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
		return $forms[0] ?? null;
	}
}
