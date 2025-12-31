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

		$forms       = UVE_MR_Form_Use_Cases::list_forms(
			array(
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => $per_page,
				'offset'         => ( $page - 1 ) * $per_page,
			)
		);
		$this->forms = $forms;

		$counts      = wp_count_posts( UVE_MR_Form::POST_TYPE );
		$total_items = 0;
		if ( $counts ) {
			$total_items += (int) ( $counts->publish ?? 0 );
			$total_items += (int) ( $counts->draft ?? 0 );
			$total_items += (int) ( $counts->private ?? 0 );
		}

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->items = $this->forms;
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
			'name'      => __( 'Name', 'uve-mailrelay-newsletter' ),
			'shortcode' => __( 'Shortcode', 'uve-mailrelay-newsletter' ),
			'group'     => __( 'Group', 'uve-mailrelay-newsletter' ),
			'status'    => __( 'Status', 'uve-mailrelay-newsletter' ),
			'updated'   => __( 'Updated', 'uve-mailrelay-newsletter' ),
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

		$actions = array(
			'edit'      => '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'uve-mailrelay-newsletter' ) . '</a>',
			'duplicate' => '<a href="' . esc_url( $dup_url ) . '">' . esc_html__( 'Duplicate', 'uve-mailrelay-newsletter' ) . '</a>',
			'trash'     => '<a href="' . esc_url( $trash_url ) . '">' . esc_html__( 'Trash', 'uve-mailrelay-newsletter' ) . '</a>',
		);

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
		return esc_html( ucfirst( (string) $item->status ) );
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
}
