<?php
/**
 * Single-form repository adapter.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository that exposes only the primary published form.
 */
class UVE_MR_Single_Form_Repository implements UVE_MR_Form_Repository_Interface {

	/**
	 * Get a form by ID.
	 *
	 * @param int $id Form ID.
	 * @return UVE_MR_Form|null
	 */
	public function get( int $id ): ?UVE_MR_Form {
		return $this->get_primary();
	}

	/**
	 * Get all forms (single entry only).
	 *
	 * @param array $args Query args (ignored).
	 * @return UVE_MR_Form[]
	 */
	public function list( array $args = array() ): array {
		$statuses = $this->normalize_statuses( $args );
		$primary  = $statuses ? $this->get_primary_by_statuses( $statuses ) : $this->get_primary();
		return $primary ? array( $primary ) : array();
	}

	/**
	 * Create a new form.
	 *
	 * @param string $name Form name.
	 * @param array  $config Config array.
	 * @param string $status Form status.
	 * @return UVE_MR_Form|null
	 */
	public function create( string $name, array $config, string $status ): ?UVE_MR_Form {
		if ( ! function_exists( 'wp_insert_post' ) ) {
			return null;
		}
		$post_id = wp_insert_post(
			array(
				'post_type'   => UVE_MR_Form::POST_TYPE,
				'post_status' => $status,
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
	 * @param string $status Form status.
	 * @return UVE_MR_Form|null
	 */
	public function update( int $id, string $name, array $config, string $status ): ?UVE_MR_Form {
		if ( ! function_exists( 'get_post' ) || ! function_exists( 'wp_update_post' ) ) {
			return null;
		}
		$post = get_post( $id );
		if ( ! $post || UVE_MR_Form::POST_TYPE !== $post->post_type ) {
			return null;
		}

		$post_id = wp_update_post(
			array(
				'ID'          => $id,
				'post_title'  => $name,
				'post_status' => $status,
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

	/**
	 * Count published forms, optionally excluding one ID.
	 *
	 * @param int|null $exclude_id Form ID to exclude.
	 * @return int
	 */
	public function count_published( ?int $exclude_id = null ): int {
		if ( ! function_exists( 'wp_count_posts' ) ) {
			return 0;
		}

		$counts    = wp_count_posts( UVE_MR_Form::POST_TYPE );
		$published = (int) ( $counts->publish ?? 0 );

		if ( $exclude_id && function_exists( 'get_post' ) ) {
			$post = get_post( $exclude_id );
			if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
				$published = max( 0, $published - 1 );
			}
		}

		return $published;
	}

	/**
	 * Resolve the primary published form.
	 *
	 * @return UVE_MR_Form|null
	 */
	private function get_primary(): ?UVE_MR_Form {
		return $this->get_primary_by_statuses( array( 'publish', 'draft' ) );
	}

	/**
	 * Get primary form for a list of statuses.
	 *
	 * @param array $statuses Status list.
	 * @return UVE_MR_Form|null
	 */
	private function get_primary_by_statuses( array $statuses ): ?UVE_MR_Form {
		if ( ! function_exists( 'get_posts' ) ) {
			return null;
		}
		if ( empty( $statuses ) ) {
			return null;
		}
		if ( in_array( 'any', $statuses, true ) ) {
			$posts = get_posts(
				array(
					'post_type'      => UVE_MR_Form::POST_TYPE,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'perm'           => 'readable',
				)
			);
			$forms = $this->build_forms_from_posts( $posts );
			return $forms[0] ?? null;
		}
		foreach ( $statuses as $status ) {
			$posts = get_posts(
				array(
					'post_type'      => UVE_MR_Form::POST_TYPE,
					'post_status'    => array( $status ),
					'posts_per_page' => 1,
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'perm'           => 'readable',
				)
			);
			if ( empty( $posts ) ) {
				continue;
			}
			$forms = $this->build_forms_from_posts( $posts );
			if ( isset( $forms[0] ) ) {
				return $forms[0];
			}
		}
		return null;
	}

	/**
	 * Normalize post statuses from query args.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	private function normalize_statuses( array $args ): array {
		$status = $args['post_status'] ?? array();
		if ( is_string( $status ) ) {
			$status = array( $status );
		}
		if ( ! is_array( $status ) ) {
			return array();
		}
		$clean = array();
		foreach ( $status as $item ) {
			if ( ! is_string( $item ) ) {
				continue;
			}
			$item = trim( $item );
			if ( '' !== $item ) {
				$clean[] = $item;
			}
		}
		return array_values( array_unique( $clean ) );
	}

	/**
	 * Build form models from posts.
	 *
	 * @param array $posts Posts list.
	 * @return UVE_MR_Form[]
	 */
	protected function build_forms_from_posts( array $posts ): array {
		$forms = array();
		foreach ( $posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$forms[] = UVE_MR_Form::from_post( $post );
			}
		}
		return $forms;
	}
}
