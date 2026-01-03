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
		return UVE_MR_Container::form_repository();
	}

	/**
	 * Build form limits provider.
	 *
	 * @return UVE_MR_Form_Limits
	 */
	private static function limits(): UVE_MR_Form_Limits {
		return UVE_MR_Container::form_limits();
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
	 * @param string $status Form status.
	 * @return UVE_MR_Form|null
	 */
	public static function create_form( string $name, array $config, string $status = 'publish' ): ?UVE_MR_Form {
		$status = self::normalize_status( $status );
		if ( 'publish' === $status && ! self::can_publish_form( null ) ) {
			$status = 'draft';
		}
		return self::repo()->create( $name, $config, $status );
	}

	/**
	 * Update a form.
	 *
	 * @param int    $id Form ID.
	 * @param string $name Form name.
	 * @param array  $config Form config.
	 * @param string $status Form status.
	 * @return UVE_MR_Form|null
	 */
	public static function update_form( int $id, string $name, array $config, string $status = 'publish' ): ?UVE_MR_Form {
		$status = self::normalize_status( $status );
		if ( 'publish' === $status && ! self::can_publish_form( $id ) ) {
			$status = 'draft';
		}
		return self::repo()->update( $id, $name, $config, $status );
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
		return self::repo()->create( $name, $form->config, 'draft' );
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
			$form = self::repo()->get( $id );
			if ( $form && 'publish' !== $form->status ) {
				return null;
			}
			return $form;
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

	/**
	 * Get the maximum number of published forms allowed.
	 *
	 * @return int
	 */
	public static function max_published_forms(): int {
		return self::limits()->max_published_forms();
	}

	/**
	 * Check whether another form can be published.
	 *
	 * @param int|null $form_id Form ID.
	 * @return bool
	 */
	public static function can_publish_form( ?int $form_id ): bool {
		$max = self::max_published_forms();
		if ( $max <= 0 ) {
			return true;
		}
		return self::repo()->count_published( $form_id ) < $max;
	}

	/**
	 * Normalize form status to supported values.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	private static function normalize_status( string $status ): string {
		return 'draft' === $status ? 'draft' : 'publish';
	}
}
