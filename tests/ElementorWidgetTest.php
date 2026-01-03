<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../stubs/elementor.php';
require_once __DIR__ . '/../includes/class-uve-mr-elementor-newsletter-widget.php';

final class ElementorWidgetTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['uve_mr_test_filters'] = array();
		$GLOBALS['uve_mr_test_actions'] = array();
	}

	public function test_register_controls_skips_form_select_when_free(): void {
		$widget = new Test_Elementor_Widget();
		$widget->run_register_controls();

		$this->assertArrayNotHasKey( 'form_id', $widget->controls );
	}

	public function test_register_controls_adds_form_select_when_premium(): void {
		$this->add_elementor_form_control();
		$this->set_repo(
			new Elementor_Test_Form_Repository(
				array(
					$this->make_form( 2, 'publish' ),
					$this->make_form( 1, 'publish' ),
				)
			)
		);

		$widget = new Test_Elementor_Widget();
		$widget->run_register_controls();

		$this->assertArrayHasKey( 'form_id', $widget->controls );
		$options = $widget->controls['form_id']['options'] ?? array();
		$this->assertSame( 'Use legacy overrides', $options['0'] ?? '' );
		$this->assertSame( 'Form 1', $options['1'] ?? '' );
	}

	public function test_render_uses_primary_form_when_free(): void {
		$this->set_repo(
			new Elementor_Test_Form_Repository(
				array(
					$this->make_form( 3, 'publish' ),
					$this->make_form( 1, 'publish' ),
				)
			)
		);

		$widget           = new Test_Elementor_Render_Widget();
		$widget->settings = array();
		$output = $widget->capture_render();

		$this->assertStringContainsString( 'name="uve_mr_form_id" value="1"', $output );
	}

	public function test_render_uses_requested_form_when_selected(): void {
		$this->set_repo(
			new Elementor_Test_Form_Repository(
				array(
					$this->make_form( 3, 'publish' ),
					$this->make_form( 1, 'publish' ),
				)
			)
		);

		$widget           = new Test_Elementor_Render_Widget();
		$widget->settings = array(
			'form_id' => 3,
		);
		$output = $widget->capture_render();

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

	private function add_elementor_form_control(): void {
		add_action(
			'uve_mr_elementor_register_controls',
			static function ( $widget ): void {
				$forms        = UVE_MR_Form_Use_Cases::list_forms(
					array(
						'post_status'    => array( 'publish' ),
						'posts_per_page' => 100,
					)
				);
				$form_options = array(
					'0' => __( 'Use legacy overrides', 'uve-mailrelay-newsletter' ),
				);
				foreach ( $forms as $form ) {
					$form_options[ (string) $form->id ] = $form->name;
				}
				$widget->add_control(
					'form_id',
					array(
						'label'   => __( 'Form', 'uve-mailrelay-newsletter' ),
						'type'    => 'select',
						'options' => $form_options,
						'default' => '0',
					)
				);
			}
		);
	}
}

final class Test_Elementor_Widget extends UVE_MR_Elementor_Newsletter_Widget {
	public array $controls = array();

	public function add_control( string $id, array $args = array() ): void {
		$this->controls[ $id ] = $args;
	}

	public function run_register_controls(): void {
		$this->register_controls();
	}
}

final class Test_Elementor_Render_Widget extends UVE_MR_Elementor_Newsletter_Widget {
	public array $settings = array();

	public function get_settings_for_display(): array {
		return $this->settings;
	}

	public function capture_render(): string {
		ob_start();
		$this->render();
		return (string) ob_get_clean();
	}
}

final class Elementor_Test_Form_Repository implements UVE_MR_Form_Repository_Interface {
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
