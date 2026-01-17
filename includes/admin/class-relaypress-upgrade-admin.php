<?php
/**
 * Premium upgrade admin page.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Premium upgrade admin page.
 */
final class RelayPress_Upgrade_Admin {
	/**
	 * Register upgrade submenu.
	 *
	 * @return void
	 */
	public static function admin_menu(): void {
		$show_upgrade = (bool) apply_filters( 'relaypress_show_upgrade_ui', true );
		if ( ! $show_upgrade ) {
			return;
		}

		add_submenu_page(
			'relaypress-newsletter',
			__( 'Upgrade to Premium', 'relaypress-newsletter' ),
			__( 'Upgrade to Premium', 'relaypress-newsletter' ),
			'manage_options',
			'relaypress-newsletter-upgrade',
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
		$show_upgrade = (bool) apply_filters( 'relaypress_show_upgrade_ui', true );
		if ( ! $show_upgrade ) {
			return;
		}

		if ( false === strpos( $hook_suffix, 'relaypress-newsletter-upgrade' ) ) {
			return;
		}

		$src = plugins_url( 'assets/admin-upgrade.css', RelayPress_Utils::plugin_file() );
		wp_enqueue_style( 'relaypress-admin-upgrade', $src, array(), RelayPress_Newsletter::VERSION );
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

		$upgrade_url = (string) apply_filters( 'relaypress_premium_upgrade_url', 'https://relaypress.io' );
		$title       = (string) apply_filters( 'relaypress_premium_upgrade_title', __( 'Unlock premium tools and integrations', 'relaypress-newsletter' ) );
		$message     = (string) apply_filters( 'relaypress_premium_upgrade_message', __( 'Get advanced segmentation, integrations, and priority support.', 'relaypress-newsletter' ) );
		$cta_label   = (string) apply_filters( 'relaypress_premium_upgrade_cta', __( 'Upgrade to Premium', 'relaypress-newsletter' ) );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Upgrade to Premium', 'relaypress-newsletter' ); ?></h1>
			<div class="relaypress-upgrade-card">
				<h2><?php echo esc_html( $title ); ?></h2>
				<p><?php echo esc_html( $message ); ?></p>
				<ul>
					<li><?php echo esc_html__( 'Premium integrations', 'relaypress-newsletter' ); ?></li>
					<li><?php echo esc_html__( 'Advanced segmentation and testing', 'relaypress-newsletter' ); ?></li>
					<li><?php echo esc_html__( 'Priority support', 'relaypress-newsletter' ); ?></li>
				</ul>
				<?php if ( '' !== $upgrade_url ) : ?>
					<p>
						<a class="button button-primary relaypress-upgrade-cta" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener">
							<?php echo esc_html( $cta_label ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
