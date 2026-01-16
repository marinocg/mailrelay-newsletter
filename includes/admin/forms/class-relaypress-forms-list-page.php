<?php
/**
 * Forms list page.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Forms list page.
 */
final class RelayPress_Forms_List_Page {
	/**
	 * Render list view.
	 *
	 * @return void
	 */
	public static function render(): void {
		$table = new RelayPress_Forms_Table();
		$table->process_bulk_action();
		$table->prepare_items();
		$add_url = add_query_arg(
			array(
				'page'   => 'relaypress-newsletter-forms',
				'action' => 'new',
			),
			admin_url( 'admin.php' )
		);

		$notice = sanitize_text_field( (string) wp_unslash( $_GET['notice'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		self::render_notices( $notice );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html__( 'Forms', 'relaypress-newsletter' ); ?></h1>
			<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action"><?php echo esc_html__( 'Add New', 'relaypress-newsletter' ); ?></a>
			<hr class="wp-header-end">
			<form method="post">
				<?php wp_nonce_field( 'bulk-relaypress-forms' ); ?>
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
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form saved.', 'relaypress-newsletter' ) . '</p></div>';
		} elseif ( 'duplicated' === $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form duplicated.', 'relaypress-newsletter' ) . '</p></div>';
		} elseif ( 'trashed' === $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form moved to trash.', 'relaypress-newsletter' ) . '</p></div>';
		} elseif ( 'bulk-updated' === $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Forms updated.', 'relaypress-newsletter' ) . '</p></div>';
		} elseif ( 'error' === $notice ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Something went wrong.', 'relaypress-newsletter' ) . '</p></div>';
		}
	}
}
