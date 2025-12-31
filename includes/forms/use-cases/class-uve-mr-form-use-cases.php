<?php
/**
 * Form use cases.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form use cases.
 */
final class UVE_MR_Form_Use_Cases {
	/**
	 * Build repository.
	 *
	 * @return UVE_MR_Form_Repository_Interface
	 */
	private static function repo(): UVE_MR_Form_Repository_Interface {
		return new UVE_MR_WP_Form_Repository();
	}

	/**
	 * List forms.
	 *
	 * @param array $args Query args.
	 * @return UVE_MR_Form[]
	 */
	public static function list_forms( array $args = array() ): array {
		return self::repo()->list( $args );
	}

	/**
	 * Get a form by ID.
	 *
	 * @param int $id Form ID.
	 * @return UVE_MR_Form|null
	 */
	public static function get_form( int $id ): ?UVE_MR_Form {
		return self::repo()->get( $id );
	}

	/**
	 * Create a form.
	 *
	 * @param string $name Form name.
	 * @param array  $config Form config.
	 * @return UVE_MR_Form|null
	 */
	public static function create_form( string $name, array $config ): ?UVE_MR_Form {
		return self::repo()->create( $name, $config );
	}

	/**
	 * Update a form.
	 *
	 * @param int    $id Form ID.
	 * @param string $name Form name.
	 * @param array  $config Form config.
	 * @return UVE_MR_Form|null
	 */
	public static function update_form( int $id, string $name, array $config ): ?UVE_MR_Form {
		return self::repo()->update( $id, $name, $config );
	}

	/**
	 * Duplicate a form.
	 *
	 * @param int $id Form ID.
	 * @return UVE_MR_Form|null
	 */
	public static function duplicate_form( int $id ): ?UVE_MR_Form {
		$form = self::repo()->get( $id );
		if ( ! $form ) {
			return null;
		}

		$name = $form->name . ' (Copy)';
		return self::repo()->create( $name, $form->config );
	}

	/**
	 * Trash a form.
	 *
	 * @param int $id Form ID.
	 * @return bool
	 */
	public static function trash_form( int $id ): bool {
		return self::repo()->trash( $id );
	}

	/**
	 * Get a form for rendering.
	 *
	 * @param int|null $id Form ID.
	 * @return UVE_MR_Form|null
	 */
	public static function get_form_for_render( ?int $id ): ?UVE_MR_Form {
		if ( $id ) {
			return self::repo()->get( $id );
		}

		$forms = self::repo()->list(
			array(
				'post_status'    => array( 'publish' ),
				'posts_per_page' => 1,
				'orderby'        => 'modified',
			)
		);
		return $forms[0] ?? null;
	}
}
