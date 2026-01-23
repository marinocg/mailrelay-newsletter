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
		$js_src = plugins_url( 'assets/admin-upgrade.js', RelayPress_Utils::plugin_file() );
		wp_enqueue_script( 'relaypress-admin-upgrade', $js_src, array(), RelayPress_Newsletter::VERSION, true );
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

		$upgrade_url   = (string) apply_filters( 'relaypress_premium_upgrade_url', 'https://marinocg.github.io/mailrelay-newsletter/' );
		$learn_url     = (string) apply_filters( 'relaypress_premium_learn_more_url', $upgrade_url );
		$page_title    = (string) apply_filters( 'relaypress_premium_upgrade_title', __( 'Roadmap & what\'s coming next', 'relaypress-newsletter' ) );
		$page_subtitle = (string) apply_filters(
			'relaypress_premium_upgrade_message',
			__( 'RelayPress (Mailrelay Newsletter) — development status, planned integrations, and next steps', 'relaypress-newsletter' )
		);
		$cta_label     = (string) apply_filters( 'relaypress_premium_upgrade_cta', __( 'Get Premium (WooCommerce)', 'relaypress-newsletter' ) );

		$opts                 = RelayPress_Newsletter::get_options();
		$mailrelay_configured = '' !== (string) ( $opts['api_base_url'] ?? '' ) && '' !== (string) ( $opts['api_token'] ?? '' );
		$premium_active       = class_exists( 'RelayPress_Premium' );
		$woo_active           = class_exists( 'WooCommerce' );
		$queue_engine         = RelayPress_Container::task_scheduler()->is_available()
			? __( 'Action Scheduler', 'relaypress-newsletter' )
			: __( 'WP-Cron', 'relaypress-newsletter' );

		$turnstile_state      = new RelayPress_WP_Extension_State_Repository();
		$turnstile_enabled    = $turnstile_state->is_enabled( RelayPress_Turnstile_Extension_Provider::SLUG, true );
		$turnstile_configured = RelayPress_Container::turnstile_config()->is_enabled();
		if ( $turnstile_enabled && $turnstile_configured ) {
			$turnstile_status = __( 'Enabled', 'relaypress-newsletter' );
		} elseif ( $turnstile_enabled ) {
			$turnstile_status = __( 'Needs configuration', 'relaypress-newsletter' );
		} else {
			$turnstile_status = __( 'Disabled', 'relaypress-newsletter' );
		}

		$status_pill_version = sprintf(
			/* translators: %s: plugin version. */
			__( 'Installed: RelayPress v%s', 'relaypress-newsletter' ),
			RelayPress_Newsletter::VERSION
		);
		$mailrelay_status = $mailrelay_configured
			? __( 'Mailrelay: Connected', 'relaypress-newsletter' )
			: __( 'Mailrelay: Not connected', 'relaypress-newsletter' );
		$woo_status       = $woo_active
			? __( 'WooCommerce: Active', 'relaypress-newsletter' )
			: __( 'WooCommerce: Not active', 'relaypress-newsletter' );

		$cta_url   = '';
		$cta_label = $cta_label;
		if ( $premium_active ) {
			if ( $woo_active ) {
				$cta_label = __( 'Open WooCommerce Integration', 'relaypress-newsletter' );
				$cta_url   = admin_url( 'admin.php?page=wc-settings&tab=relaypress' );
			} else {
				$cta_label = __( 'Install/Activate WooCommerce', 'relaypress-newsletter' );
				$cta_url   = admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' );
			}
		} else {
			$cta_url = $upgrade_url;
		}

		$debug_report = array(
			'relaypress_version' => RelayPress_Newsletter::VERSION,
			'wordpress_version'  => get_bloginfo( 'version' ),
			'php_version'        => PHP_VERSION,
			'mailrelay'          => $mailrelay_configured ? 'connected' : 'not_connected',
			'woocommerce'        => $woo_active ? 'active' : 'inactive',
			'queue_engine'       => $queue_engine,
			'turnstile'          => $turnstile_status,
		);
		?>
		<div class="wrap relaypress-upgrade">
			<div class="relaypress-upgrade-header">
				<div class="relaypress-upgrade-header-left">
					<h1 class="relaypress-upgrade-title"><?php echo esc_html( $page_title ); ?></h1>
					<p class="relaypress-upgrade-subtitle"><?php echo esc_html( $page_subtitle ); ?></p>
					<p class="relaypress-upgrade-focus"><?php echo esc_html__( 'Focus right now: WooCommerce Premium extension. Next: more integrations.', 'relaypress-newsletter' ); ?></p>
				</div>
				<div class="relaypress-upgrade-header-right">
					<div class="relaypress-pill-group">
						<span class="relaypress-pill relaypress-pill-muted"><?php echo esc_html( $status_pill_version ); ?></span>
						<span class="relaypress-pill <?php echo esc_attr( $mailrelay_configured ? 'relaypress-pill-ok' : 'relaypress-pill-warn' ); ?>">
							<?php echo esc_html( $mailrelay_status ); ?>
						</span>
						<span class="relaypress-pill <?php echo esc_attr( $woo_active ? 'relaypress-pill-ok' : 'relaypress-pill-warn' ); ?>">
							<?php echo esc_html( $woo_status ); ?>
						</span>
					</div>
					<?php if ( '' !== $cta_url ) : ?>
						<div class="relaypress-upgrade-cta">
							<?php if ( $premium_active ) : ?>
								<a class="button button-primary" href="<?php echo esc_url( $cta_url ); ?>">
									<?php echo esc_html( $cta_label ); ?>
								</a>
							<?php else : ?>
								<a class="button button-primary" href="<?php echo esc_url( $cta_url ); ?>" target="_blank" rel="noopener">
									<?php echo esc_html( $cta_label ); ?>
								</a>
								<?php if ( '' !== $learn_url ) : ?>
									<a class="button button-secondary" href="<?php echo esc_url( $learn_url ); ?>" target="_blank" rel="noopener">
										<?php echo esc_html__( 'Learn what\'s included', 'relaypress-newsletter' ); ?>
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="notice notice-info inline relaypress-upgrade-notice">
				<p><?php echo esc_html__( 'This roadmap reflects current priorities. Items may shift based on stability and user feedback.', 'relaypress-newsletter' ); ?></p>
			</div>

			<div class="relaypress-upgrade-grid">
				<div class="relaypress-upgrade-main">
					<div class="card relaypress-card" id="relaypress-woocommerce">
						<div class="relaypress-card-header">
							<h2><?php echo esc_html__( 'WooCommerce (Premium) — in development', 'relaypress-newsletter' ); ?></h2>
							<div class="relaypress-badge-group">
								<span class="relaypress-badge relaypress-badge-dev"><?php echo esc_html__( 'In development', 'relaypress-newsletter' ); ?></span>
								<span class="relaypress-badge relaypress-badge-premium"><?php echo esc_html__( 'Premium', 'relaypress-newsletter' ); ?></span>
							</div>
						</div>
						<p><?php echo esc_html__( 'Add compliant opt-in at checkout and sync customers to Mailrelay without slowing down checkout.', 'relaypress-newsletter' ); ?></p>

						<h3><?php echo esc_html__( 'What it includes (v1)', 'relaypress-newsletter' ); ?></h3>
						<p class="relaypress-card-subtitle"><?php echo esc_html__( 'Launch essentials (Pro v1)', 'relaypress-newsletter' ); ?></p>
						<ul class="relaypress-checklist">
							<li><span class="relaypress-status relaypress-status-progress" aria-hidden="true">🟡</span><span class="relaypress-item-text"><?php echo esc_html__( 'Enable/disable Woo integration + health status', 'relaypress-newsletter' ); ?></span></li>
							<li><span class="relaypress-status relaypress-status-progress" aria-hidden="true">🟡</span><span class="relaypress-item-text"><?php echo esc_html__( 'Checkout opt-in checkbox (Classic + Blocks)', 'relaypress-newsletter' ); ?></span></li>
							<li><span class="relaypress-status relaypress-status-progress" aria-hidden="true">🟡</span><span class="relaypress-item-text"><?php echo esc_html__( 'Subscribe triggers (processing/paid/completed) + guest support', 'relaypress-newsletter' ); ?></span></li>
							<li><span class="relaypress-status relaypress-status-progress" aria-hidden="true">🟡</span><span class="relaypress-item-text"><?php echo esc_html__( 'Field mapping (email, name, phone, country)', 'relaypress-newsletter' ); ?></span></li>
							<li><span class="relaypress-status relaypress-status-progress" aria-hidden="true">🟡</span><span class="relaypress-item-text"><?php echo esc_html__( 'Group routing rules (base group + product/category rules)', 'relaypress-newsletter' ); ?></span></li>
							<li><span class="relaypress-status relaypress-status-next" aria-hidden="true">⏳</span><span class="relaypress-item-text"><?php echo esc_html__( 'Order-level observability (per-order sync status + logs)', 'relaypress-newsletter' ); ?></span></li>
							<li><span class="relaypress-status relaypress-status-progress" aria-hidden="true">🟡</span><span class="relaypress-item-text"><?php echo esc_html__( 'Compliance defaults (explicit opt-in + store consent evidence)', 'relaypress-newsletter' ); ?></span></li>
						</ul>

						<details class="relaypress-details">
							<summary><?php echo esc_html__( 'Next wave (v1.1+)', 'relaypress-newsletter' ); ?></summary>
							<ul class="relaypress-checklist">
								<li><span class="relaypress-status relaypress-status-next" aria-hidden="true">⏳</span><span class="relaypress-item-text"><?php echo esc_html__( 'Thank-you page fallback modal (for express/one-click flows if consent missing)', 'relaypress-newsletter' ); ?></span></li>
								<li><span class="relaypress-status relaypress-status-next" aria-hidden="true">⏳</span><span class="relaypress-item-text"><?php echo esc_html__( 'Customer preference page ("My Account" marketing opt-in)', 'relaypress-newsletter' ); ?></span></li>
								<li><span class="relaypress-status relaypress-status-next" aria-hidden="true">⏳</span><span class="relaypress-item-text"><?php echo esc_html__( 'Lifecycle enrichment fields (order_count, total_spent, last_order_date)', 'relaypress-newsletter' ); ?></span></li>
								<li><span class="relaypress-status relaypress-status-next" aria-hidden="true">⏳</span><span class="relaypress-item-text"><?php echo esc_html__( 'Bulk backfill tool (sync past customers)', 'relaypress-newsletter' ); ?></span></li>
								<li><span class="relaypress-status relaypress-status-next" aria-hidden="true">⏳</span><span class="relaypress-item-text"><?php echo esc_html__( 'Abandoned cart signals (automation triggers)', 'relaypress-newsletter' ); ?></span></li>
							</ul>
						</details>

						<div class="relaypress-callout">
							<p><?php echo esc_html__( 'Pro feature that drives ROI: better targeting + automations + deliverability at scale.', 'relaypress-newsletter' ); ?></p>
						</div>
					</div>

					<div class="card relaypress-card">
						<div class="relaypress-card-header">
							<h2><?php echo esc_html__( 'Planned next integrations (Premium)', 'relaypress-newsletter' ); ?></h2>
							<div class="relaypress-badge-group">
								<span class="relaypress-badge relaypress-badge-planned"><?php echo esc_html__( 'Planned', 'relaypress-newsletter' ); ?></span>
								<span class="relaypress-badge relaypress-badge-premium"><?php echo esc_html__( 'Premium', 'relaypress-newsletter' ); ?></span>
							</div>
						</div>
						<p><?php echo esc_html__( 'After WooCommerce reaches stable v1, we\'ll expand RelayPress with additional ESP/form integrations.', 'relaypress-newsletter' ); ?></p>

						<div class="relaypress-split-list">
							<div>
								<h3><?php echo esc_html__( 'Form builders', 'relaypress-newsletter' ); ?></h3>
								<ul class="relaypress-bullets">
									<li><span class="relaypress-badge relaypress-badge-planned"><?php echo esc_html__( 'Planned', 'relaypress-newsletter' ); ?></span><span class="relaypress-item-text"><?php echo esc_html__( 'Elementor Forms (deep integration)', 'relaypress-newsletter' ); ?></span></li>
									<li><span class="relaypress-badge relaypress-badge-planned"><?php echo esc_html__( 'Planned', 'relaypress-newsletter' ); ?></span><span class="relaypress-item-text"><?php echo esc_html__( 'Contact Form 7 / WPForms (based on demand)', 'relaypress-newsletter' ); ?></span></li>
									<li><span class="relaypress-badge relaypress-badge-planned"><?php echo esc_html__( 'Planned', 'relaypress-newsletter' ); ?></span><span class="relaypress-item-text"><?php echo esc_html__( 'Ninja Forms / WPForms', 'relaypress-newsletter' ); ?></span></li>
									<li><span class="relaypress-badge relaypress-badge-planned"><?php echo esc_html__( 'Planned', 'relaypress-newsletter' ); ?></span><span class="relaypress-item-text"><?php echo esc_html__( 'Better field mapping + conditional routing', 'relaypress-newsletter' ); ?></span></li>
								</ul>
							</div>
							<div>
								<h3><?php echo esc_html__( 'Other plugins', 'relaypress-newsletter' ); ?></h3>
								<ul class="relaypress-bullets">
									<li><span class="relaypress-badge relaypress-badge-planned"><?php echo esc_html__( 'Planned', 'relaypress-newsletter' ); ?></span><span class="relaypress-item-text"><?php echo esc_html__( 'Events plugins (The Events Calendar / Amelia / Bookly)', 'relaypress-newsletter' ); ?></span></li>
									<li><span class="relaypress-badge relaypress-badge-planned"><?php echo esc_html__( 'Planned', 'relaypress-newsletter' ); ?></span><span class="relaypress-item-text"><?php echo esc_html__( 'LMS plugins (LearnDash / TutorLMS / LifterLMS)', 'relaypress-newsletter' ); ?></span></li>
									<li><span class="relaypress-badge relaypress-badge-planned"><?php echo esc_html__( 'Planned', 'relaypress-newsletter' ); ?></span><span class="relaypress-item-text"><?php echo esc_html__( 'Membership plugins (MemberPress / Paid Memberships Pro / Restrict Content Pro)', 'relaypress-newsletter' ); ?></span></li>
								</ul>
							</div>
						</div>

						<div>
							<h3><?php echo esc_html__( 'Automation signals', 'relaypress-newsletter' ); ?></h3>
							<ul class="relaypress-bullets">
								<li><span class="relaypress-badge relaypress-badge-planned"><?php echo esc_html__( 'Planned', 'relaypress-newsletter' ); ?></span><span class="relaypress-item-text"><?php echo esc_html__( 'Abandoned cart events (requires Woo signals)', 'relaypress-newsletter' ); ?></span></li>
								<li><span class="relaypress-badge relaypress-badge-planned"><?php echo esc_html__( 'Planned', 'relaypress-newsletter' ); ?></span><span class="relaypress-item-text"><?php echo esc_html__( 'Product catalog sync (enables richer segmentation/automations)', 'relaypress-newsletter' ); ?></span></li>
							</ul>
						</div>

						<p class="relaypress-card-note"><?php echo esc_html__( 'These items will be prioritized by: demand, maintainability, and support burden.', 'relaypress-newsletter' ); ?></p>
					</div>

					<details class="card relaypress-card relaypress-card-collapsed">
						<summary>
							<span class="relaypress-card-summary"><?php echo esc_html__( 'Core (Free) — foundation improvements', 'relaypress-newsletter' ); ?></span>
							<span class="relaypress-badge relaypress-badge-core"><?php echo esc_html__( 'Core', 'relaypress-newsletter' ); ?></span>
							<span class="relaypress-card-toggle-hint"><?php echo esc_html__( 'Click to expand', 'relaypress-newsletter' ); ?></span>
						</summary>
						<ul class="relaypress-checklist">
							<li><span class="relaypress-status relaypress-status-done" aria-hidden="true">✅</span><span class="relaypress-item-text"><?php echo esc_html__( 'Reliable subscription pipeline (queue + retries where applicable)', 'relaypress-newsletter' ); ?></span></li>
							<li><span class="relaypress-status relaypress-status-done" aria-hidden="true">✅</span><span class="relaypress-item-text"><?php echo esc_html__( 'Multiple forms (CPT-based forms + per-form config)', 'relaypress-newsletter' ); ?></span></li>
							<li><span class="relaypress-status relaypress-status-progress" aria-hidden="true">🟡</span><span class="relaypress-item-text"><?php echo esc_html__( 'Diagnostics snapshot (system status + copy debug report)', 'relaypress-newsletter' ); ?></span></li>
						</ul>
					</details>
				</div>

				<div class="relaypress-upgrade-sidebar">
					<div class="card relaypress-card">
						<h2><?php echo esc_html__( 'Quick links', 'relaypress-newsletter' ); ?></h2>
						<ul class="relaypress-links">
							<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=relaypress-newsletter' ) ); ?>"><?php echo esc_html__( 'Settings', 'relaypress-newsletter' ); ?></a></li>
							<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=relaypress-newsletter-forms' ) ); ?>"><?php echo esc_html__( 'Forms', 'relaypress-newsletter' ); ?></a></li>
							<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=relaypress-newsletter-logs' ) ); ?>"><?php echo esc_html__( 'Logs', 'relaypress-newsletter' ); ?></a></li>
							<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=relaypress-newsletter-extensions' ) ); ?>"><?php echo esc_html__( 'Extensions', 'relaypress-newsletter' ); ?></a></li>
							<li><a href="#relaypress-system-status"><?php echo esc_html__( 'Diagnostics', 'relaypress-newsletter' ); ?></a></li>
							<li>
								<?php if ( $premium_active && $woo_active ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=relaypress' ) ); ?>"><?php echo esc_html__( 'WooCommerce integration (Premium)', 'relaypress-newsletter' ); ?></a>
								<?php else : ?>
									<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'WooCommerce integration (Premium)', 'relaypress-newsletter' ); ?></a>
								<?php endif; ?>
							</li>
						</ul>
					</div>

					<div class="card relaypress-card" id="relaypress-system-status">
						<h2><?php echo esc_html__( 'System status', 'relaypress-newsletter' ); ?></h2>
						<ul class="relaypress-status-list">
							<li>
								<span class="relaypress-status-label"><?php echo esc_html__( 'WordPress version', 'relaypress-newsletter' ); ?></span>
								<span class="relaypress-status-value"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
							</li>
							<li>
								<span class="relaypress-status-label"><?php echo esc_html__( 'PHP version', 'relaypress-newsletter' ); ?></span>
								<span class="relaypress-status-value"><?php echo esc_html( PHP_VERSION ); ?></span>
							</li>
							<li>
								<span class="relaypress-status-label"><?php echo esc_html__( 'WooCommerce', 'relaypress-newsletter' ); ?></span>
								<span class="relaypress-status-value"><?php echo esc_html( $woo_active ? __( 'Active', 'relaypress-newsletter' ) : __( 'Not active', 'relaypress-newsletter' ) ); ?></span>
							</li>
							<li>
								<span class="relaypress-status-label"><?php echo esc_html__( 'Mailrelay connection', 'relaypress-newsletter' ); ?></span>
								<span class="relaypress-status-value"><?php echo esc_html( $mailrelay_configured ? __( 'OK', 'relaypress-newsletter' ) : __( 'Not connected', 'relaypress-newsletter' ) ); ?></span>
							</li>
							<li>
								<span class="relaypress-status-label"><?php echo esc_html__( 'Queue engine', 'relaypress-newsletter' ); ?></span>
								<span class="relaypress-status-value"><?php echo esc_html( $queue_engine ); ?></span>
							</li>
							<li>
								<span class="relaypress-status-label"><?php echo esc_html__( 'Turnstile', 'relaypress-newsletter' ); ?></span>
								<span class="relaypress-status-value"><?php echo esc_html( $turnstile_status ); ?></span>
							</li>
						</ul>
						<div class="relaypress-status-actions">
							<button type="button"
								class="button button-secondary"
								id="relaypress-copy-debug"
								data-report="<?php echo esc_attr( wp_json_encode( $debug_report ) ); ?>"
								data-success="<?php echo esc_attr__( 'Debug report copied.', 'relaypress-newsletter' ); ?>"
								data-failure="<?php echo esc_attr__( 'Copy failed. Please try again.', 'relaypress-newsletter' ); ?>">
								<?php echo esc_html__( 'Copy debug report', 'relaypress-newsletter' ); ?>
							</button>
							<span class="relaypress-copy-status" id="relaypress-copy-status" role="status" aria-live="polite"></span>
						</div>
					</div>

					<div class="card relaypress-card">
						<h2><?php echo esc_html__( 'Compliance notes', 'relaypress-newsletter' ); ?></h2>
						<ul class="relaypress-bullets">
							<li><?php echo esc_html__( 'Mailrelay is an external service used to store and manage subscribers.', 'relaypress-newsletter' ); ?></li>
							<li><?php echo esc_html__( 'Cloudflare Turnstile may be used for anti-spam protection.', 'relaypress-newsletter' ); ?></li>
							<li class="relaypress-link-row">
								<a href="<?php echo esc_url( 'https://mailrelay.com/en/terms-of-use/' ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Mailrelay Terms', 'relaypress-newsletter' ); ?></a>
								<span aria-hidden="true">·</span>
								<a href="<?php echo esc_url( 'https://mailrelay.com/en/privacy-policy/' ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Mailrelay Privacy', 'relaypress-newsletter' ); ?></a>
								<span aria-hidden="true">·</span>
								<a href="<?php echo esc_url( 'https://www.cloudflare.com/privacypolicy/' ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Turnstile Privacy', 'relaypress-newsletter' ); ?></a>
							</li>
						</ul>
					</div>

					<?php if ( ! $premium_active ) : ?>
						<div class="card relaypress-card relaypress-card-premium">
							<h2><?php echo esc_html__( 'Unlock WooCommerce (Premium)', 'relaypress-newsletter' ); ?></h2>
							<ul class="relaypress-bullets">
								<li><?php echo esc_html__( 'Checkout opt-in (Classic + Blocks)', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Async sync + routing rules', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Consent evidence + observability', 'relaypress-newsletter' ); ?></li>
							</ul>
							<?php if ( '' !== $upgrade_url ) : ?>
								<a class="button button-primary" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener">
									<?php echo esc_html( $cta_label ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<div class="card relaypress-card relaypress-card-premium">
							<h2><?php echo esc_html__( 'WooCommerce status', 'relaypress-newsletter' ); ?></h2>
							<p>
								<?php
								echo esc_html(
									$woo_active
										? __( 'WooCommerce detected and ready for RelayPress settings.', 'relaypress-newsletter' )
										: __( 'WooCommerce is not active yet. Enable it to finish setup.', 'relaypress-newsletter' )
								);
								?>
							</p>
							<?php if ( '' !== $cta_url ) : ?>
								<a class="button button-secondary" href="<?php echo esc_url( $cta_url ); ?>">
									<?php echo esc_html( $cta_label ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<p class="relaypress-legal">
				<?php echo esc_html__( 'Mailrelay, Cloudflare, and Turnstile are registered trademarks of their respective owners. RelayPress is not affiliated with them.', 'relaypress-newsletter' ); ?>
			</p>
		</div>
		<?php
	}
}
