<?php
/**
 * Form use cases.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form use cases.
 */
final class RelayPress_Form_Use_Cases {
	/**
	 * Build repository.
	 *
	 * @return RelayPress_Form_Repository_Interface
	 */
	private static function repo(): RelayPress_Form_Repository_Interface {
		return RelayPress_Container::form_repository();
	}

	/**
	 * List forms.
	 *
	 * @param array $args Query args.
	 * @return RelayPress_Form[]
	 */
	public static function list_forms( array $args = array() ): array {
		return self::repo()->list( $args );
	}

	/**
	 * Get a form by ID.
	 *
	 * @param int $id Form ID.
	 * @return RelayPress_Form|null
	 */
	public static function get_form( int $id ): ?RelayPress_Form {
		return self::repo()->get( $id );
	}

	/**
	 * Create a form.
	 *
	 * @param string $name Form name.
	 * @param array  $config Form config.
	 * @param string $status Form status.
	 * @return RelayPress_Form|null
	 */
	public static function create_form( string $name, array $config, string $status = 'publish' ): ?RelayPress_Form {
		$status = self::normalize_status( $status );
		return self::repo()->create( $name, $config, $status );
	}

	/**
	 * Update a form.
	 *
	 * @param int    $id Form ID.
	 * @param string $name Form name.
	 * @param array  $config Form config.
	 * @param string $status Form status.
	 * @return RelayPress_Form|null
	 */
	public static function update_form( int $id, string $name, array $config, string $status = 'publish' ): ?RelayPress_Form {
		$status = self::normalize_status( $status );
		return self::repo()->update( $id, $name, $config, $status );
	}

	/**
	 * Duplicate a form.
	 *
	 * @param int $id Form ID.
	 * @return RelayPress_Form|null
	 */
	public static function duplicate_form( int $id ): ?RelayPress_Form {
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
	 * @return RelayPress_Form|null
	 */
	public static function get_form_for_render( ?int $id ): ?RelayPress_Form {
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
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
		return $forms[0] ?? null;
	}

	/**
	 * Get the primary form for admin editing.
	 *
	 * @return RelayPress_Form|null
	 */
	public static function get_primary_form_for_admin(): ?RelayPress_Form {
		$args  = array(
			'post_status'    => array( 'publish' ),
			'posts_per_page' => 1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);
		$forms = self::repo()->list(
			array(
				'post_status'    => $args['post_status'],
				'posts_per_page' => $args['posts_per_page'],
				'orderby'        => $args['orderby'],
				'order'          => $args['order'],
			)
		);
		if ( empty( $forms ) ) {
			$args['post_status'] = array( 'draft' );
			$forms               = self::repo()->list( $args );
		}
		return $forms[0] ?? null;
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
