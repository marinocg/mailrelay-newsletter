<?php
/**
 * WordPress-backed form repository.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress-backed form repository.
 */
final class UVE_MR_WP_Form_Repository implements UVE_MR_Form_Repository_Interface {
	/**
	 * Get a form by ID.
	 *
	 * @param int $id Form ID.
	 * @return UVE_MR_Form|null
	 */
	public function get( int $id ): ?UVE_MR_Form {
		if ( $id <= 0 ) {
			return null;
		}
		if ( ! function_exists( 'get_post' ) ) {
			return null;
		}
		$post = get_post( $id );
		if ( ! $post || UVE_MR_Form::POST_TYPE !== $post->post_type ) {
			return null;
		}
		if ( 'trash' === $post->post_status ) {
			return null;
		}
		return UVE_MR_Form::from_post( $post );
	}

	/**
	 * Get all forms.
	 *
	 * @param array $args Query args.
	 * @return UVE_MR_Form[]
	 */
	public function list( array $args = array() ): array {
		if ( ! function_exists( 'get_posts' ) ) {
			return array();
		}
		$defaults   = array(
			'post_type'      => UVE_MR_Form::POST_TYPE,
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 50,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		);
		$query_args = wp_parse_args( $args, $defaults );
		$posts      = get_posts( $query_args );
		$forms      = array();
		foreach ( $posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$forms[] = UVE_MR_Form::from_post( $post );
			}
		}
		return $forms;
	}

	/**
	 * Create a new form.
	 *
	 * @param string $name Form name.
	 * @param array  $config Config array.
	 * @return UVE_MR_Form|null
	 */
	public function create( string $name, array $config ): ?UVE_MR_Form {
		if ( ! function_exists( 'wp_insert_post' ) ) {
			return null;
		}
		$post_id = wp_insert_post(
			array(
				'post_type'   => UVE_MR_Form::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $name,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return null;
		}

		update_post_meta( $post_id, UVE_MR_Form::META_CONFIG, $config );
		update_post_meta( $post_id, UVE_MR_Form::META_VERSION, UVE_MR_Form::CONFIG_VERSION );

		$post = get_post( $post_id );
		return $post instanceof WP_Post ? UVE_MR_Form::from_post( $post ) : null;
	}

	/**
	 * Update a form.
	 *
	 * @param int    $id Form ID.
	 * @param string $name Form name.
	 * @param array  $config Config array.
	 * @return UVE_MR_Form|null
	 */
	public function update( int $id, string $name, array $config ): ?UVE_MR_Form {
		if ( ! function_exists( 'get_post' ) || ! function_exists( 'wp_update_post' ) ) {
			return null;
		}
		$post = get_post( $id );
		if ( ! $post || UVE_MR_Form::POST_TYPE !== $post->post_type ) {
			return null;
		}

		$post_id = wp_update_post(
			array(
				'ID'         => $id,
				'post_title' => $name,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return null;
		}

		update_post_meta( $post_id, UVE_MR_Form::META_CONFIG, $config );
		update_post_meta( $post_id, UVE_MR_Form::META_VERSION, UVE_MR_Form::CONFIG_VERSION );

		$post = get_post( $post_id );
		return $post instanceof WP_Post ? UVE_MR_Form::from_post( $post ) : null;
	}

	/**
	 * Trash a form.
	 *
	 * @param int $id Form ID.
	 * @return bool
	 */
	public function trash( int $id ): bool {
		if ( $id <= 0 ) {
			return false;
		}
		if ( ! function_exists( 'get_post' ) || ! function_exists( 'wp_trash_post' ) ) {
			return false;
		}
		$post = get_post( $id );
		if ( ! $post || UVE_MR_Form::POST_TYPE !== $post->post_type ) {
			return false;
		}
		$res = wp_trash_post( $id );
		return ( $res instanceof WP_Post );
	}
}
