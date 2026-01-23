<?php
/**
 * Turnstile settings admin page.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turnstile settings admin page.
 */
final class RelayPress_Turnstile_Admin {
	public const PAGE_SLUG = 'relaypress-newsletter-turnstile';

	/**
	 * Register submenu page.
	 *
	 * @return void
	 */
	public static function admin_menu(): void {
		add_submenu_page(
			'relaypress-newsletter',
			__( 'Turnstile', 'relaypress-newsletter' ),
			__( 'Turnstile', 'relaypress-newsletter' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Hide the submenu entry while keeping the page registered.
	 *
	 * @return void
	 */
	public static function hide_menu_css(): void {
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<style>
			#toplevel_page_relaypress-newsletter .wp-submenu a[href="admin.php?page=<?php echo esc_attr( self::PAGE_SLUG ); ?>"] {
				display: none !important;
			}
		</style>
		<?php
	}

	/**
	 * Handle Turnstile settings save.
	 *
	 * @return void
	 */
	public static function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'relaypress_save_turnstile' );

		$raw     = wp_unslash( $_POST[ RelayPress_Newsletter::OPT_KEY ] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw     = is_array( $raw ) ? $raw : array();
		$site    = sanitize_text_field( (string) ( $raw['turnstile_site_key'] ?? '' ) );
		$secret  = sanitize_text_field( (string) ( $raw['turnstile_secret_key'] ?? '' ) );
		$load_js = ! empty( $raw['turnstile_load_js'] ) ? '1' : '0';

		$opts                         = RelayPress_Newsletter::get_options();
		$opts['turnstile_site_key']   = $site;
		$opts['turnstile_secret_key'] = $secret;
		$opts['turnstile_load_js']    = $load_js;
		update_option( RelayPress_Newsletter::OPT_KEY, $opts );

		$redirect = add_query_arg(
			array(
				'page'    => self::PAGE_SLUG,
				'updated' => '1',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle Turnstile test submission.
	 *
	 * @return void
	 */
	public static function handle_test(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'relaypress_test_turnstile' );

		$token = sanitize_text_field( (string) wp_unslash( $_POST['cf-turnstile-response'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ip    = RelayPress_Container::request_context()->get_client_ip();
		$ok    = false;

		if ( '' !== $token && RelayPress_Container::turnstile_verifier()->verify( $token, $ip ) ) {
			$ok = true;
		}

		$redirect = add_query_arg(
			array(
				'page' => self::PAGE_SLUG,
				'test' => $ok ? 'ok' : 'fail',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Render the Turnstile settings page.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$opts       = RelayPress_Newsletter::get_options();
		$updated    = sanitize_text_field( (string) wp_unslash( $_GET['updated'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$test_state = sanitize_text_field( (string) wp_unslash( $_GET['test'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$site_key   = (string) ( $opts['turnstile_site_key'] ?? '' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Turnstile', 'relaypress-newsletter' ); ?></h1>
			<?php if ( '1' === $updated ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Turnstile settings updated.', 'relaypress-newsletter' ); ?></p>
				</div>
			<?php endif; ?>
			<?php if ( 'ok' === $test_state ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Turnstile test successful.', 'relaypress-newsletter' ); ?></p>
				</div>
			<?php elseif ( 'fail' === $test_state ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html__( 'Turnstile test failed. Please check your keys and try again.', 'relaypress-newsletter' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'relaypress_save_turnstile' ); ?>
				<input type="hidden" name="action" value="relaypress_save_turnstile" />

				<div class="relaypress-settings-grid">
					<div class="relaypress-settings-main">
						<div class="relaypress-panel">
							<h3><?php echo esc_html__( 'Cloudflare Turnstile', 'relaypress-newsletter' ); ?></h3>
							<div class="relaypress-form-grid">
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-turnstile-site"><?php echo esc_html__( 'Turnstile Site Key', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-turnstile-site" type="text" class="regular-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[turnstile_site_key]"
											value="<?php echo esc_attr( $opts['turnstile_site_key'] ); ?>" />
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-turnstile-secret"><?php echo esc_html__( 'Turnstile Secret Key', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-turnstile-secret" type="password" class="regular-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[turnstile_secret_key]"
											value="<?php echo esc_attr( $opts['turnstile_secret_key'] ); ?>" />
									</div>
								</div>
								<div class="relaypress-field-row">
									<span class="relaypress-field-label"><?php echo esc_html__( 'Load Turnstile script', 'relaypress-newsletter' ); ?></span>
									<div class="relaypress-field-control">
										<label>
											<input type="checkbox" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[turnstile_load_js]" value="1" <?php checked( $opts['turnstile_load_js'] ?? '1', '1' ); ?>>
											<?php echo esc_html__( 'Load the Turnstile JavaScript on the frontend', 'relaypress-newsletter' ); ?>
										</label>
										<p class="description"><?php echo esc_html__( 'Disable this if another plugin or theme already loads the Turnstile script.', 'relaypress-newsletter' ); ?></p>
									</div>
								</div>
							</div>
						</div>

					</div>

					<div class="relaypress-settings-help">
						<div class="relaypress-panel relaypress-help-panel">
							<h4><?php echo esc_html__( 'Get your API keys', 'relaypress-newsletter' ); ?></h4>
							<p>
								<?php
								printf(
									'%s %s',
									esc_html__( 'Create a Turnstile site in your Cloudflare dashboard to get the site and secret keys.', 'relaypress-newsletter' ),
									wp_kses_post(
										sprintf(
											'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
											esc_url( 'https://dash.cloudflare.com/?to=/:account/turnstile' ),
											esc_html__( 'Open Turnstile dashboard', 'relaypress-newsletter' )
										)
									)
								);
								?>
							</p>
						</div>

						<div class="relaypress-panel relaypress-help-panel">
							<h4><?php echo esc_html__( 'Script loading', 'relaypress-newsletter' ); ?></h4>
							<ul class="relaypress-help-list">
								<li><?php echo esc_html__( 'RelayPress can enqueue the Turnstile JavaScript automatically.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Disable it when your theme or another plugin already includes Turnstile.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Use the same Turnstile site key and secret pair as the other plugin to prevent validation errors.', 'relaypress-newsletter' ); ?></li>
							</ul>
						</div>
					</div>
				</div>

				<?php submit_button( __( 'Save Changes', 'relaypress-newsletter' ) ); ?>
			</form>

			<div class="relaypress-panel">
				<h3><?php echo esc_html__( 'Test configuration', 'relaypress-newsletter' ); ?></h3>
				<?php if ( '' === $site_key ) : ?>
					<p><?php echo esc_html__( 'Add your Turnstile keys above to run the test.', 'relaypress-newsletter' ); ?></p>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="relaypress-turnstile-test">
						<?php wp_nonce_field( 'relaypress_test_turnstile' ); ?>
						<input type="hidden" name="action" value="relaypress_test_turnstile" />
						<div id="relaypress-turnstile-test" class="relaypress-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
						<?php submit_button( __( 'Run Turnstile test', 'relaypress-newsletter' ), 'secondary', 'submit', false ); ?>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<style>
			.relaypress-settings-grid {
				display: grid;
				grid-template-columns: minmax(0, 3fr) minmax(260px, 1fr);
				gap: 24px;
				align-items: start;
				margin-top: 16px;
			}
			.relaypress-settings-main,
			.relaypress-settings-help {
				min-width: 0;
			}
			.relaypress-panel {
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				padding: 16px;
				margin-bottom: 16px;
			}
			.relaypress-panel h3,
			.relaypress-panel h4 {
				margin-top: 0;
			}
			.relaypress-help-panel {
				background: #f6f7f7;
			}
			.relaypress-form-grid {
				display: grid;
				gap: 12px;
			}
			.relaypress-field-row {
				display: grid;
				grid-template-columns: 220px minmax(0, 1fr);
				gap: 16px;
				align-items: start;
			}
			.relaypress-field-label {
				font-weight: 600;
			}
			.relaypress-field-control .regular-text {
				width: 100%;
				max-width: 420px;
			}
			.relaypress-help-list {
				margin: 0;
				padding-left: 18px;
			}
			.relaypress-help-list li {
				margin-bottom: 8px;
			}
			@media (max-width: 900px) {
				.relaypress-settings-grid {
					grid-template-columns: minmax(0, 1fr);
				}
				.relaypress-field-row {
					grid-template-columns: minmax(0, 1fr);
					gap: 6px;
				}
				.relaypress-field-label {
					margin-bottom: 2px;
				}
			}
			@media (max-width: 600px) {
				.relaypress-panel {
					padding: 12px;
				}
				.relaypress-form-grid {
					gap: 10px;
				}
				.relaypress-settings-grid {
					gap: 16px;
				}
			}
		</style>
		<?php if ( '' !== $site_key ) : ?>
			<script>
				(function() {
					function renderTest() {
						if (!window.turnstile) return;
						var el = document.getElementById('relaypress-turnstile-test');
						if (!el || el.getAttribute('data-rendered') === '1') return;
						el.setAttribute('data-rendered', '1');
						try {
							window.turnstile.render(el, {
								sitekey: el.getAttribute('data-sitekey')
							});
						} catch (e) {}
					}
					if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', renderTest);
					else renderTest();
				})();
			</script>
		<?php endif; ?>
		<?php if ( '' !== $site_key ) : ?>
			<?php
			if ( ! wp_script_is( 'cf-turnstile', 'enqueued' ) && ! wp_script_is( 'cf-turnstile', 'done' ) ) {
				wp_enqueue_script(
					'cf-turnstile',
					'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
					array(),
					RelayPress_Newsletter::VERSION,
					true
				);
			}
			?>
		<?php endif; ?>
		<?php
	}
}
