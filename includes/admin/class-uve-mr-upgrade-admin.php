<?php
/**
 * Premium upgrade admin page.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Premium upgrade admin page.
 */
final class UVE_MR_Upgrade_Admin {
	/**
	 * Register upgrade submenu.
	 *
	 * @return void
	 */
	public static function admin_menu(): void {
		$show_upgrade = (bool) apply_filters( 'uve_mr_show_upgrade_ui', true );
		if ( ! $show_upgrade ) {
			return;
		}

		add_submenu_page(
			'uve-mr-newsletter',
			__( 'Upgrade to Premium', 'uve-mailrelay-newsletter' ),
			__( 'Upgrade to Premium', 'uve-mailrelay-newsletter' ),
			'manage_options',
			'uve-mr-newsletter-upgrade',
			array( __CLASS__, 'render_upgrade_page' )
		);
	}

	/**
	 * Enqueue upgrade assets.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function admin_enqueue( string $hook_suffix ): void {
		$show_upgrade = (bool) apply_filters( 'uve_mr_show_upgrade_ui', true );
		if ( ! $show_upgrade ) {
			return;
		}

		if ( false === strpos( $hook_suffix, 'uve-mr-newsletter-upgrade' ) ) {
			return;
		}

		$src = plugins_url( 'assets/admin-upgrade.css', UVE_MR_Utils::plugin_file() );
		wp_enqueue_style( 'uve-mr-admin-upgrade', $src, array(), UVE_Mailrelay_Newsletter::VERSION );
	}

	/**
	 * Render the premium upgrade page.
	 *
	 * @return void
	 */
	public static function render_upgrade_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$upgrade_url = (string) apply_filters( 'uve_mr_premium_upgrade_url', 'https://relaypress.io' );
		$title       = (string) apply_filters( 'uve_mr_premium_upgrade_title', __( 'Unlock unlimited published forms', 'uve-mailrelay-newsletter' ) );
		$message     = (string) apply_filters( 'uve_mr_premium_upgrade_message', __( 'Publish as many signup forms as you want and keep your campaigns segmented.', 'uve-mailrelay-newsletter' ) );
		$cta_label   = (string) apply_filters( 'uve_mr_premium_upgrade_cta', __( 'Upgrade to Premium', 'uve-mailrelay-newsletter' ) );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Upgrade to Premium', 'uve-mailrelay-newsletter' ); ?></h1>
			<div class="uve-mr-upgrade-card">
				<h2><?php echo esc_html( $title ); ?></h2>
				<p><?php echo esc_html( $message ); ?></p>
				<ul>
					<li><?php echo esc_html__( 'Unlimited published forms', 'uve-mailrelay-newsletter' ); ?></li>
					<li><?php echo esc_html__( 'Advanced segmentation and testing', 'uve-mailrelay-newsletter' ); ?></li>
					<li><?php echo esc_html__( 'Priority support', 'uve-mailrelay-newsletter' ); ?></li>
				</ul>
				<?php if ( '' !== $upgrade_url ) : ?>
					<p>
						<a class="button button-primary uve-mr-upgrade-cta" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener">
							<?php echo esc_html( $cta_label ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
