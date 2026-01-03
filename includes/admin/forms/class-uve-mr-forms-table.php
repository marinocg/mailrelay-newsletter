<?php
/**
 * Forms list table.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Forms list table.
 */
final class UVE_MR_Forms_Table extends WP_List_Table {
	/**
	 * Forms list.
	 *
	 * @var UVE_MR_Form[]
	 */
	private array $forms = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'mr4wp_form',
				'plural'   => 'mr4wp_forms',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = 20;
		$page     = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby  = sanitize_key( (string) wp_unslash( $_GET['orderby'] ?? 'date' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order    = strtoupper( sanitize_text_field( (string) wp_unslash( $_GET['order'] ?? 'DESC' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$allowed_orderby = array( 'title', 'modified', 'date', 'status' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'date';
		}
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		$status = $this->current_status();
		if ( 'trash' === $status ) {
			$post_status = array( 'trash' );
		} elseif ( 'draft' === $status ) {
			$post_status = array( 'draft' );
		} elseif ( 'active' === $status ) {
			$post_status = array( 'publish' );
		} else {
			$post_status = array( 'publish', 'draft' );
		}

		$forms       = UVE_MR_Form_Use_Cases::list_forms(
			array(
				'post_status'    => $post_status,
				'posts_per_page' => $per_page,
				'offset'         => ( $page - 1 ) * $per_page,
				'orderby'        => $orderby,
				'order'          => $order,
			)
		);
		$this->forms = $forms;

		$counts      = wp_count_posts( UVE_MR_Form::POST_TYPE );
		$total_items = 0;
		if ( $counts ) {
			if ( 'trash' === $status ) {
				$total_items = (int) ( $counts->trash ?? 0 );
			} elseif ( 'draft' === $status ) {
				$total_items = (int) ( $counts->draft ?? 0 );
			} elseif ( 'active' === $status ) {
				$total_items = (int) ( $counts->publish ?? 0 );
			} else {
				$total_items = (int) ( $counts->publish ?? 0 ) + (int) ( $counts->draft ?? 0 );
			}
		}

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->items           = $this->forms;
	}

	/**
	 * Display message when no items are found.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No forms found.', 'uve-mailrelay-newsletter' );
	}

	/**
	 * Get table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'        => '<input type="checkbox" />',
			'name'      => __( 'Name', 'uve-mailrelay-newsletter' ),
			'shortcode' => __( 'Shortcode', 'uve-mailrelay-newsletter' ),
			'group'     => __( 'Group', 'uve-mailrelay-newsletter' ),
			'status'    => __( 'Status', 'uve-mailrelay-newsletter' ),
			'created'   => __( 'Created', 'uve-mailrelay-newsletter' ),
			'updated'   => __( 'Updated', 'uve-mailrelay-newsletter' ),
		);
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param UVE_MR_Form $item Form model.
	 * @return string
	 */
	protected function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="form_ids[]" value="%s" />',
			esc_attr( (string) $item->id )
		);
	}

	/**
	 * Render the name column.
	 *
	 * @param UVE_MR_Form $item Form model.
	 * @return string
	 */
	protected function column_name( $item ): string {
		$edit_url = add_query_arg(
			array(
				'page'    => 'uve-mr-newsletter-forms',
				'action'  => 'edit',
				'form_id' => $item->id,
			),
			admin_url( 'admin.php' )
		);

		$dup_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'    => 'uve-mr-newsletter-forms',
					'action'  => 'duplicate',
					'form_id' => $item->id,
				),
				admin_url( 'admin.php' )
			),
			'uve_mr_forms_action'
		);

		$trash_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'    => 'uve-mr-newsletter-forms',
					'action'  => 'trash',
					'form_id' => $item->id,
				),
				admin_url( 'admin.php' )
			),
			'uve_mr_forms_action'
		);

		if ( 'trash' === $item->status ) {
			$restore_url = wp_nonce_url(
				add_query_arg(
					array(
						'page'    => 'uve-mr-newsletter-forms',
						'action'  => 'restore',
						'form_id' => $item->id,
					),
					admin_url( 'admin.php' )
				),
				'uve_mr_forms_action'
			);

			$delete_url = wp_nonce_url(
				add_query_arg(
					array(
						'page'    => 'uve-mr-newsletter-forms',
						'action'  => 'delete',
						'form_id' => $item->id,
					),
					admin_url( 'admin.php' )
				),
				'uve_mr_forms_action'
			);

			$actions = array(
				'restore' => '<a href="' . esc_url( $restore_url ) . '">' . esc_html__( 'Restore', 'uve-mailrelay-newsletter' ) . '</a>',
				'delete'  => '<a href="' . esc_url( $delete_url ) . '">' . esc_html__( 'Delete Permanently', 'uve-mailrelay-newsletter' ) . '</a>',
			);
		} else {
			if ( 'publish' === $item->status ) {
				$publish_action_key = 'draft';
				$publish_label      = esc_html__( 'Move to Draft', 'uve-mailrelay-newsletter' );
			} else {
				$publish_action_key = 'publish';
				$publish_label      = esc_html__( 'Publish', 'uve-mailrelay-newsletter' );
			}

			$publish_url = wp_nonce_url(
				add_query_arg(
					array(
						'page'    => 'uve-mr-newsletter-forms',
						'action'  => $publish_action_key,
						'form_id' => $item->id,
					),
					admin_url( 'admin.php' )
				),
				'uve_mr_forms_action'
			);

			$actions = array(
				'edit'              => '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'uve-mailrelay-newsletter' ) . '</a>',
				'duplicate'         => '<a href="' . esc_url( $dup_url ) . '">' . esc_html__( 'Duplicate', 'uve-mailrelay-newsletter' ) . '</a>',
				$publish_action_key => '<a href="' . esc_url( $publish_url ) . '">' . $publish_label . '</a>',
				'trash'             => '<a href="' . esc_url( $trash_url ) . '">' . esc_html__( 'Trash', 'uve-mailrelay-newsletter' ) . '</a>',
			);
		}

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_url ),
			esc_html( $item->name ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render shortcode column.
	 *
	 * @param UVE_MR_Form $item Form model.
	 * @return string
	 */
	protected function column_shortcode( $item ): string {
		return '<code>[uve_mailrelay_newsletter id="' . esc_attr( (string) $item->id ) . '"]</code>';
	}

	/**
	 * Render group column.
	 *
	 * @param UVE_MR_Form $item Form model.
	 * @return string
	 */
	protected function column_group( $item ): string {
		$opts     = UVE_Mailrelay_Newsletter::get_options();
		$defaults = UVE_MR_Form_Config::defaults( $opts );
		$config   = $item->merge_config( $defaults );
		return esc_html( (string) ( $config['destination']['group_ids'] ?? '' ) );
	}

	/**
	 * Render status column.
	 *
	 * @param UVE_MR_Form $item Form model.
	 * @return string
	 */
	protected function column_status( $item ): string {
		$status_obj = function_exists( 'get_post_status_object' ) ? get_post_status_object( $item->status ) : null;
		if ( $status_obj && ! empty( $status_obj->label ) ) {
			return esc_html( (string) $status_obj->label );
		}
		return esc_html( ucfirst( (string) $item->status ) );
	}

	/**
	 * Render created column.
	 *
	 * @param UVE_MR_Form $item Form model.
	 * @return string
	 */
	protected function column_created( $item ): string {
		$time = strtotime( (string) $item->created_at );
		if ( ! $time ) {
			return '';
		}
		return esc_html( date_i18n( 'Y-m-d H:i', $time ) );
	}

	/**
	 * Render updated column.
	 *
	 * @param UVE_MR_Form $item Form model.
	 * @return string
	 */
	protected function column_updated( $item ): string {
		$time = strtotime( (string) $item->updated_at );
		if ( ! $time ) {
			return '';
		}
		return esc_html( date_i18n( 'Y-m-d H:i', $time ) );
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'name'    => array( 'title', false ),
			'status'  => array( 'status', false ),
			'created' => array( 'date', true ),
			'updated' => array( 'modified', false ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		$status = $this->current_status();
		if ( 'trash' === $status ) {
			return array(
				'restore' => __( 'Restore', 'uve-mailrelay-newsletter' ),
				'delete'  => __( 'Delete Permanently', 'uve-mailrelay-newsletter' ),
			);
		}

		return array(
			'trash' => __( 'Move to Trash', 'uve-mailrelay-newsletter' ),
		);
	}

	/**
	 * Get views for status filters.
	 *
	 * @return array
	 */
	protected function get_views() {
		$counts = wp_count_posts( UVE_MR_Form::POST_TYPE );
		$base   = add_query_arg( 'page', 'uve-mr-newsletter-forms', admin_url( 'admin.php' ) );
		$status = $this->current_status();

		$all    = (int) ( $counts->publish ?? 0 ) + (int) ( $counts->draft ?? 0 );
		$active = (int) ( $counts->publish ?? 0 );
		$draft  = (int) ( $counts->draft ?? 0 );
		$trash  = (int) ( $counts->trash ?? 0 );

		$views        = array();
		$views['all'] = sprintf(
			'<a href="%s" class="%s">%s</a>',
			esc_url( $base ),
			'all' === $status ? 'current' : '',
			// translators: %d: number of forms.
			esc_html( sprintf( __( 'All (%d)', 'uve-mailrelay-newsletter' ), $all ) )
		);

		$views['active'] = sprintf(
			'<a href="%s" class="%s">%s</a>',
			esc_url( add_query_arg( 'status', 'active', $base ) ),
			'active' === $status ? 'current' : '',
			// translators: %d: number of forms.
			esc_html( sprintf( __( 'Published (%d)', 'uve-mailrelay-newsletter' ), $active ) )
		);

		$views['draft'] = sprintf(
			'<a href="%s" class="%s">%s</a>',
			esc_url( add_query_arg( 'status', 'draft', $base ) ),
			'draft' === $status ? 'current' : '',
			// translators: %d: number of forms.
			esc_html( sprintf( __( 'Draft (%d)', 'uve-mailrelay-newsletter' ), $draft ) )
		);

		$views['trash'] = sprintf(
			'<a href="%s" class="%s">%s</a>',
			esc_url( add_query_arg( 'status', 'trash', $base ) ),
			'trash' === $status ? 'current' : '',
			// translators: %d: number of forms.
			esc_html( sprintf( __( 'Trash (%d)', 'uve-mailrelay-newsletter' ), $trash ) )
		);

		return $views;
	}

	/**
	 * Process bulk actions.
	 *
	 * @return void
	 */
	public function process_bulk_action(): void {
		$action = $this->current_action();
		if ( ! $action ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		$form_ids = isset( $_POST['form_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['form_ids'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ids      = array_filter( $form_ids );
		if ( empty( $ids ) ) {
			return;
		}

		$upgrade = false;
		foreach ( $ids as $id ) {
			if ( 'trash' === $action ) {
				wp_trash_post( $id );
			} elseif ( 'restore' === $action ) {
				$can_publish = UVE_MR_Form_Use_Cases::can_publish_form( $id );
				$status      = $can_publish ? 'publish' : 'draft';
				wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => $status,
					)
				);
				if ( ! $can_publish ) {
					$upgrade = true;
				}
			} elseif ( 'delete' === $action ) {
				wp_delete_post( $id, true );
			}
		}

		$redirect = add_query_arg(
			array(
				'page'   => 'uve-mr-newsletter-forms',
				'notice' => $upgrade ? 'upgrade' : 'bulk-updated',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Get current status filter.
	 *
	 * @return string
	 */
	private function current_status(): string {
		$status = sanitize_text_field( (string) wp_unslash( $_GET['status'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'trash' === $status ) {
			return 'trash';
		}
		if ( 'draft' === $status ) {
			return 'draft';
		}
		if ( 'active' === $status ) {
			return 'active';
		}
		return 'all';
	}
}
