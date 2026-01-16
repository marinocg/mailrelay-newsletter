<?php
/**
 * Form repository adapter.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'RelayPress_Single_Form_Repository' ) ) {
	return;
}

/**
 * Full form repository.
 */
final class RelayPress_Form_Repository extends RelayPress_Single_Form_Repository {
	/**
	 * Get a form by ID.
	 *
	 * @param int $id Form ID.
	 * @return RelayPress_Form|null
	 */
	public function get( int $id ): ?RelayPress_Form {
		if ( $id <= 0 ) {
			return null;
		}
		if ( ! function_exists( 'get_post' ) ) {
			return null;
		}
		$post = get_post( $id );
		if ( ! $post || RelayPress_Form::POST_TYPE !== $post->post_type ) {
			return null;
		}
		if ( 'trash' === $post->post_status ) {
			return null;
		}
		return RelayPress_Form::from_post( $post );
	}

	/**
	 * Get all forms.
	 *
	 * @param array $args Query args.
	 * @return RelayPress_Form[]
	 */
	public function list( array $args = array() ): array {
		if ( ! function_exists( 'get_posts' ) ) {
			return array();
		}
		$defaults   = array(
			'post_type'      => RelayPress_Form::POST_TYPE,
			'post_status'    => array( 'publish' ),
			'posts_per_page' => 50,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'perm'           => 'readable',
		);
		$query_args = wp_parse_args( $args, $defaults );
		$posts      = get_posts( $query_args );
		if ( empty( $posts ) ) {
			$query_args['post_status'] = 'any';
			$posts                     = get_posts( $query_args );
		}
		return $this->build_forms_from_posts( $posts );
	}
}
