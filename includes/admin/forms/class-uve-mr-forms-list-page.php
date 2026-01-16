<?php
/**
 * Forms list page.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Forms list page.
 */
final class UVE_MR_Forms_List_Page {
	/**
	 * Render list view.
	 *
	 * @return void
	 */
	public static function render(): void {
		$table = new UVE_MR_Forms_Table();
		$table->process_bulk_action();
		$table->prepare_items();
		$add_url = add_query_arg(
			array(
				'page'   => 'uve-mr-newsletter-forms',
				'action' => 'new',
			),
			admin_url( 'admin.php' )
		);

		$notice = sanitize_text_field( (string) wp_unslash( $_GET['notice'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		self::render_notices( $notice );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html__( 'Forms', 'uve-mailrelay-newsletter' ); ?></h1>
			<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action"><?php echo esc_html__( 'Add New', 'uve-mailrelay-newsletter' ); ?></a>
			<hr class="wp-header-end">
			<form method="post">
				<?php wp_nonce_field( 'bulk-uve-mr-forms' ); ?>
				<?php $table->views(); ?>
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render admin notices.
	 *
	 * @param string $notice Notice key.
	 * @return void
	 */
	private static function render_notices( string $notice ): void {
		if ( 'saved' === $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form saved.', 'uve-mailrelay-newsletter' ) . '</p></div>';
		} elseif ( 'duplicated' === $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form duplicated.', 'uve-mailrelay-newsletter' ) . '</p></div>';
		} elseif ( 'trashed' === $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form moved to trash.', 'uve-mailrelay-newsletter' ) . '</p></div>';
		} elseif ( 'bulk-updated' === $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Forms updated.', 'uve-mailrelay-newsletter' ) . '</p></div>';
		} elseif ( 'error' === $notice ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Something went wrong.', 'uve-mailrelay-newsletter' ) . '</p></div>';
		}
	}
}
