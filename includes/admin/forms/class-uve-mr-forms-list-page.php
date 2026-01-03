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

		if ( 'upgrade' === $notice ) {
			self::render_upgrade_modal( true );
		}
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
		} elseif ( 'upgrade' === $notice ) {
			$upgrade_url  = admin_url( 'admin.php?page=uve-mr-newsletter-upgrade' );
			$upgrade_link = '<a href="' . esc_url( $upgrade_url ) . '">' . esc_html__( 'Upgrade to Premium', 'uve-mailrelay-newsletter' ) . '</a>';
			$upgrade_text = sprintf(
				/* translators: %s: upgrade link. */
				__( 'Your form was saved as a draft. %s to publish more forms.', 'uve-mailrelay-newsletter' ),
				$upgrade_link
			);
			echo '<div class="notice notice-warning"><p>' . wp_kses_post( $upgrade_text ) . '</p></div>';
		} elseif ( 'error' === $notice ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Something went wrong.', 'uve-mailrelay-newsletter' ) . '</p></div>';
		}
	}

	/**
	 * Render the premium upgrade modal.
	 *
	 * @param bool $auto_open Whether to open automatically.
	 * @return void
	 */
	private static function render_upgrade_modal( bool $auto_open ): void {
		$upgrade_url = (string) apply_filters( 'uve_mr_premium_upgrade_url', 'https://relaypress.io' );
		$title       = (string) apply_filters( 'uve_mr_premium_upgrade_title', __( 'Unlock unlimited published forms', 'uve-mailrelay-newsletter' ) );
		$message     = (string) apply_filters( 'uve_mr_premium_upgrade_message', __( 'Publish as many signup forms as you want and keep your campaigns segmented.', 'uve-mailrelay-newsletter' ) );
		$cta_label   = (string) apply_filters( 'uve_mr_premium_upgrade_cta', __( 'Upgrade to Premium', 'uve-mailrelay-newsletter' ) );
		?>
		<div class="uve-mr-upgrade-overlay" id="uve-mr-upgrade-overlay" data-auto-open="<?php echo esc_attr( $auto_open ? '1' : '0' ); ?>" aria-hidden="true">
			<div class="uve-mr-upgrade-modal" role="dialog" aria-modal="true" aria-labelledby="uve-mr-upgrade-title">
				<button type="button" class="uve-mr-upgrade-close" aria-label="<?php echo esc_attr__( 'Close', 'uve-mailrelay-newsletter' ); ?>">
					&times;
				</button>
				<div class="uve-mr-upgrade-hero">
					<span class="uve-mr-upgrade-badge"><?php echo esc_html__( 'Premium', 'uve-mailrelay-newsletter' ); ?></span>
					<h2 id="uve-mr-upgrade-title"><?php echo esc_html( $title ); ?></h2>
					<p><?php echo esc_html( $message ); ?></p>
				</div>
				<div class="uve-mr-upgrade-body">
					<ul>
						<li><?php echo esc_html__( 'Unlimited published forms', 'uve-mailrelay-newsletter' ); ?></li>
						<li><?php echo esc_html__( 'Advanced segmentation and testing', 'uve-mailrelay-newsletter' ); ?></li>
						<li><?php echo esc_html__( 'Priority support', 'uve-mailrelay-newsletter' ); ?></li>
					</ul>
				</div>
				<div class="uve-mr-upgrade-actions">
					<?php if ( '' !== $upgrade_url ) : ?>
						<a class="button button-primary uve-mr-upgrade-cta" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener">
							<?php echo esc_html( $cta_label ); ?>
						</a>
					<?php endif; ?>
					<button type="button" class="button uve-mr-upgrade-dismiss"><?php echo esc_html__( 'Maybe later', 'uve-mailrelay-newsletter' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}
}
