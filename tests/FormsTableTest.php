<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/admin/forms/class-uve-mr-forms-table.php';

final class FormsTableTest extends TestCase {
	public function test_columns_include_created(): void {
		$table   = new UVE_MR_Forms_Table();
		$columns = $table->get_columns();

		$this->assertArrayHasKey( 'created', $columns );
	}

	public function test_sortable_columns_include_created(): void {
		$table = new UVE_MR_Forms_Table();

		$renderer = Closure::bind(
			function(): array {
				return $this->get_sortable_columns();
			},
			$table,
			$table
		);
		$sortable = $renderer();

		$this->assertArrayHasKey( 'created', $sortable );
		$this->assertSame( array( 'date', true ), $sortable['created'] );
	}

	public function test_column_created_renders_date(): void {
		$table            = new UVE_MR_Forms_Table();
		$item             = new stdClass();
		$item->created_at = '2025-01-02 03:04:05';

		$renderer = Closure::bind(
			function( $item ): string {
				return $this->column_created( $item );
			},
			$table,
			$table
		);
		$html = (string) $renderer( $item );

		$this->assertSame( '2025-01-02 03:04', $html );
	}

	public function test_column_name_for_draft_includes_publish_action(): void {
		$table       = new UVE_MR_Forms_Table();
		$item        = new stdClass();
		$item->id    = 11;
		$item->name  = 'Draft Form';
		$item->status = 'draft';

		$renderer = Closure::bind(
			function( $item ): string {
				return $this->column_name( $item );
			},
			$table,
			$table
		);
		$html = (string) $renderer( $item );

		$this->assertStringContainsString( 'Publish', $html );
		$this->assertStringContainsString( 'action=publish', $html );
	}

	public function test_column_name_for_published_includes_draft_action(): void {
		$table       = new UVE_MR_Forms_Table();
		$item        = new stdClass();
		$item->id    = 22;
		$item->name  = 'Published Form';
		$item->status = 'publish';

		$renderer = Closure::bind(
			function( $item ): string {
				return $this->column_name( $item );
			},
			$table,
			$table
		);
		$html = (string) $renderer( $item );

		$this->assertStringContainsString( 'Move to Draft', $html );
		$this->assertStringContainsString( 'action=draft', $html );
	}
}
