<?php
/**
 * Admin settings and UI.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings and UI.
 */
final class UVE_MR_Admin {

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public static function admin_menu(): void {
		add_menu_page(
			__( 'MR4WP', 'uve-mailrelay-newsletter' ),
			__( 'MR4WP', 'uve-mailrelay-newsletter' ),
			'manage_options',
			'uve-mr-newsletter',
			array( __CLASS__, 'render_settings_page' ),
			'dashicons-email-alt2'
		);

		add_submenu_page(
			'uve-mr-newsletter',
			__( 'Settings', 'uve-mailrelay-newsletter' ),
			__( 'Settings', 'uve-mailrelay-newsletter' ),
			'manage_options',
			'uve-mr-newsletter',
			array( __CLASS__, 'render_settings_page' )
		);

		add_submenu_page(
			'uve-mr-newsletter',
			__( 'Logs', 'uve-mailrelay-newsletter' ),
			__( 'Logs', 'uve-mailrelay-newsletter' ),
			'manage_options',
			'uve-mr-newsletter-logs',
			array( __CLASS__, 'render_logs_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public static function admin_init(): void {
		if ( current_user_can( 'manage_options' ) && isset( $_GET['uve_mr_refresh_groups'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nonce = sanitize_text_field( (string) wp_unslash( $_GET['_wpnonce'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $nonce && wp_verify_nonce( $nonce, 'uve_mr_refresh_groups' ) ) {
				delete_transient( 'uve_mr_groups_cache' );
			}
		}

		register_setting(
			'uve_mr_newsletter',
			UVE_Mailrelay_Newsletter::OPT_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
				'default'           => UVE_Mailrelay_Newsletter::defaults(),
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function admin_enqueue( string $hook_suffix ): void {
		if ( false === strpos( $hook_suffix, 'uve-mr-newsletter-logs' ) ) {
			return;
		}

		$src = plugins_url( 'assets/admin-logs.css', __DIR__ . '/../class-uve-mailrelay-newsletter.php' );
		wp_enqueue_style( 'uve-mr-admin-logs', $src, array(), UVE_Mailrelay_Newsletter::VERSION );
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param mixed $raw Raw options.
	 * @return array
	 */
	public static function sanitize_options( $raw ): array {
		$raw = is_array( $raw ) ? $raw : array();
		$def = UVE_Mailrelay_Newsletter::defaults();

		$out                 = array();
		$out['api_base_url'] = esc_url_raw( trim( (string) ( $raw['api_base_url'] ?? $def['api_base_url'] ) ) );
		$out['api_token']    = sanitize_text_field( (string) ( $raw['api_token'] ?? $def['api_token'] ) );
		$out['group_ids']    = sanitize_text_field( (string) ( $raw['group_ids'] ?? $def['group_ids'] ) );

		$status                   = sanitize_text_field( (string) ( $raw['subscriber_status'] ?? $def['subscriber_status'] ) );
		$out['subscriber_status'] = in_array( $status, array( 'inactive', 'active' ), true ) ? $status : 'inactive';

		$out['turnstile_site_key']   = sanitize_text_field( (string) ( $raw['turnstile_site_key'] ?? $def['turnstile_site_key'] ) );
		$out['turnstile_secret_key'] = sanitize_text_field( (string) ( $raw['turnstile_secret_key'] ?? $def['turnstile_secret_key'] ) );

		$out['title']             = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['title'] ?? $def['title'] ) ) );
		$out['description']       = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['description'] ?? $def['description'] ) ) );
		$out['email_placeholder'] = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['email_placeholder'] ?? $def['email_placeholder'] ) ) );
		$out['submit_label']      = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['submit_label'] ?? $def['submit_label'] ) ) );
		$out['ajax_mode']         = ! empty( $raw['ajax_mode'] ) ? '1' : '0';

		$out['privacy_url']   = esc_url_raw( trim( (string) ( $raw['privacy_url'] ?? $def['privacy_url'] ) ) );
		$out['consent_label'] = UVE_MR_Utils::normalize_text( sanitize_text_field( (string) ( $raw['consent_label'] ?? $def['consent_label'] ) ) );

		$out['store_consent_log'] = ! empty( $raw['store_consent_log'] ) ? '1' : '0';
		$out['hash_ip']           = ! empty( $raw['hash_ip'] ) ? '1' : '0';
		$out['retention_days']    = isset( $raw['retention_days'] ) ? max( 1, (int) $raw['retention_days'] ) : (int) $def['retention_days'];

		$out['rate_limit_max']            = isset( $raw['rate_limit_max'] ) ? max( 1, (int) $raw['rate_limit_max'] ) : (int) $def['rate_limit_max'];
		$out['rate_limit_window_seconds'] = isset( $raw['rate_limit_window_seconds'] ) ? max( 60, (int) $raw['rate_limit_window_seconds'] ) : (int) $def['rate_limit_window_seconds'];

		$out['confirm_resend_max']            = isset( $raw['confirm_resend_max'] ) ? max( 0, (int) $raw['confirm_resend_max'] ) : (int) $def['confirm_resend_max'];
		$out['confirm_resend_window_seconds'] = isset( $raw['confirm_resend_window_seconds'] ) ? max( 60, (int) $raw['confirm_resend_window_seconds'] ) : (int) $def['confirm_resend_window_seconds'];

		return $out;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$opts = UVE_Mailrelay_Newsletter::get_options();
		?>
		<div class="wrap">
		<h1><?php echo esc_html__( 'MR4WP', 'uve-mailrelay-newsletter' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'uve_mr_newsletter' ); ?>
				<div class="uve-mr-settings-grid">
					<div class="uve-mr-settings-main">
						<div class="uve-mr-panel">
							<h3><?php echo esc_html__( 'Mailrelay API', 'uve-mailrelay-newsletter' ); ?></h3>
							<div class="uve-mr-form-grid">
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-api-base"><?php echo esc_html__( 'Mailrelay API base URL', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-api-base" type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[api_base_url]"
											value="<?php echo esc_attr( $opts['api_base_url'] ); ?>"
											placeholder="https://YOURACCOUNT.ipzmarketing.com/api/v1" />
										<p class="description">
											<?php echo esc_html__( 'Format example: https://YOURACCOUNT.ipzmarketing.com/api/v1', 'uve-mailrelay-newsletter' ); ?>
										</p>
									</div>
								</div>
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-api-token"><?php echo esc_html__( 'Mailrelay API token', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-api-token" type="password" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[api_token]"
											value="<?php echo esc_attr( $opts['api_token'] ); ?>" />
										<p class="description">
											<a class="uve-mr-external-link" href="<?php echo esc_url( 'https://help.mailrelay.com/en/support/solutions/articles/103000160465-api-keys' ); ?>" target="_blank" rel="noopener noreferrer">
												<span class="uve-mr-link-text"><?php echo esc_html__( 'API key documentation', 'uve-mailrelay-newsletter' ); ?></span>
												<span class="screen-reader-text"> <?php echo esc_html__( '(opens in a new tab)', 'uve-mailrelay-newsletter' ); ?></span>
												<span class="dashicons dashicons-external" aria-hidden="true"></span>
											</a>
										</p>
									</div>
								</div>
							</div>
						</div>

						<div class="uve-mr-panel">
							<h3><?php echo esc_html__( 'Defaults for new forms', 'uve-mailrelay-newsletter' ); ?></h3>
							<div class="uve-mr-form-grid">
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-settings-groups-available"><?php echo esc_html__( 'Default groups', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<?php
										$group_ids   = UVE_MR_Utils::parse_group_ids( (string) $opts['group_ids'] );
										$groups      = UVE_MR_Container::mailrelay_client()->get_groups();
										$refresh_url = wp_nonce_url(
											add_query_arg(
												array(
													'page' => 'uve-mr-newsletter',
													'uve_mr_refresh_groups' => 1,
												),
												admin_url( 'admin.php' )
											),
											'uve_mr_refresh_groups'
										);
										?>
										<input type="hidden" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[group_ids]" id="uve-mr-settings-group-ids" value="<?php echo esc_attr( implode( ',', $group_ids ) ); ?>">
										<div class="uve-mr-dual-list">
											<div>
												<label class="screen-reader-text" for="uve-mr-settings-groups-available"><?php echo esc_html__( 'Available groups', 'uve-mailrelay-newsletter' ); ?></label>
												<select id="uve-mr-settings-groups-available" multiple size="8">
													<?php if ( ! empty( $groups ) ) : ?>
														<?php foreach ( $groups as $group ) : ?>
															<?php if ( in_array( (int) $group['id'], $group_ids, true ) ) : ?>
																<?php continue; ?>
															<?php endif; ?>
															<option value="<?php echo esc_attr( (string) $group['id'] ); ?>">
																<?php echo esc_html( $group['name'] ); ?>
															</option>
														<?php endforeach; ?>
													<?php endif; ?>
												</select>
												<p class="description"><?php echo esc_html__( 'Available', 'uve-mailrelay-newsletter' ); ?></p>
											</div>
											<div class="uve-mr-dual-actions">
												<button type="button" class="button" id="uve-mr-settings-group-add"><?php echo esc_html__( 'Add →', 'uve-mailrelay-newsletter' ); ?></button>
												<button type="button" class="button" id="uve-mr-settings-group-remove"><?php echo esc_html__( '← Remove', 'uve-mailrelay-newsletter' ); ?></button>
											</div>
											<div>
												<label class="screen-reader-text" for="uve-mr-settings-groups-selected"><?php echo esc_html__( 'Selected groups', 'uve-mailrelay-newsletter' ); ?></label>
												<select id="uve-mr-settings-groups-selected" multiple size="8">
													<?php if ( ! empty( $groups ) ) : ?>
														<?php foreach ( $groups as $group ) : ?>
															<?php if ( ! in_array( (int) $group['id'], $group_ids, true ) ) : ?>
																<?php continue; ?>
															<?php endif; ?>
															<option value="<?php echo esc_attr( (string) $group['id'] ); ?>">
																<?php echo esc_html( $group['name'] ); ?>
															</option>
														<?php endforeach; ?>
													<?php endif; ?>
												</select>
												<p class="description"><?php echo esc_html__( 'Selected', 'uve-mailrelay-newsletter' ); ?></p>
											</div>
										</div>
										<p class="description">
											<?php echo esc_html__( 'These groups will be preselected when creating a new form.', 'uve-mailrelay-newsletter' ); ?>
											<a href="<?php echo esc_url( $refresh_url ); ?>"><?php echo esc_html__( 'Refresh groups', 'uve-mailrelay-newsletter' ); ?></a>
										</p>
										<?php if ( empty( $groups ) ) : ?>
											<p class="description"><?php echo esc_html__( 'No groups found or API not configured.', 'uve-mailrelay-newsletter' ); ?></p>
										<?php endif; ?>
									</div>
								</div>
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-default-status"><?php echo esc_html__( 'Subscription mode', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<select id="uve-mr-default-status" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[subscriber_status]">
											<option value="inactive" <?php selected( $opts['subscriber_status'], 'inactive' ); ?>><?php echo esc_html__( 'Double opt-in (inactive + confirmation)', 'uve-mailrelay-newsletter' ); ?></option>
											<option value="active" <?php selected( $opts['subscriber_status'], 'active' ); ?>><?php echo esc_html__( 'Single opt-in (active)', 'uve-mailrelay-newsletter' ); ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="uve-mr-panel">
							<h3><?php echo esc_html__( 'Spam protection', 'uve-mailrelay-newsletter' ); ?></h3>
							<div class="uve-mr-form-grid">
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-turnstile-site"><?php echo esc_html__( 'Turnstile Site Key', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-turnstile-site" type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[turnstile_site_key]"
											value="<?php echo esc_attr( $opts['turnstile_site_key'] ); ?>" />
									</div>
								</div>
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-turnstile-secret"><?php echo esc_html__( 'Turnstile Secret Key', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-turnstile-secret" type="password" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[turnstile_secret_key]"
											value="<?php echo esc_attr( $opts['turnstile_secret_key'] ); ?>" />
									</div>
								</div>
							</div>
						</div>

						<div class="uve-mr-panel">
							<h3><?php echo esc_html__( 'Text defaults', 'uve-mailrelay-newsletter' ); ?></h3>
							<div class="uve-mr-form-grid">
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-title"><?php echo esc_html__( 'Title', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-title" type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[title]" value="<?php echo esc_attr( $opts['title'] ); ?>">
									</div>
								</div>
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-description"><?php echo esc_html__( 'Description', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-description" type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[description]" value="<?php echo esc_attr( $opts['description'] ); ?>">
									</div>
								</div>
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-email-placeholder"><?php echo esc_html__( 'Email placeholder', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-email-placeholder" type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[email_placeholder]" value="<?php echo esc_attr( $opts['email_placeholder'] ); ?>">
									</div>
								</div>
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-submit-label"><?php echo esc_html__( 'Button text', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-submit-label" type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[submit_label]" value="<?php echo esc_attr( $opts['submit_label'] ); ?>">
									</div>
								</div>
								<div class="uve-mr-field-row">
									<span class="uve-mr-field-label"><?php echo esc_html__( 'Enable AJAX submissions', 'uve-mailrelay-newsletter' ); ?></span>
									<div class="uve-mr-field-control">
										<label><input type="checkbox" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[ajax_mode]" value="1" <?php checked( $opts['ajax_mode'] ?? '0', '1' ); ?>>
											<?php echo esc_html__( 'Submit via AJAX', 'uve-mailrelay-newsletter' ); ?></label>
									</div>
								</div>
							</div>
						</div>

						<div class="uve-mr-panel">
							<h3><?php echo esc_html__( 'GDPR + Logs', 'uve-mailrelay-newsletter' ); ?></h3>
							<div class="uve-mr-form-grid">
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-privacy-url"><?php echo esc_html__( 'Privacy policy URL', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-privacy-url" type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[privacy_url]"
											value="<?php echo esc_attr( $opts['privacy_url'] ); ?>">
									</div>
								</div>
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-consent-label"><?php echo esc_html__( 'Checkbox text', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-consent-label" type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[consent_label]"
											value="<?php echo esc_attr( $opts['consent_label'] ); ?>">
									</div>
								</div>
								<div class="uve-mr-field-row">
									<span class="uve-mr-field-label"><?php echo esc_html__( 'Consent logging', 'uve-mailrelay-newsletter' ); ?></span>
									<div class="uve-mr-field-control">
										<label><input type="checkbox" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[store_consent_log]" value="1" <?php checked( $opts['store_consent_log'], '1' ); ?>>
											<?php echo esc_html__( 'Store log in database', 'uve-mailrelay-newsletter' ); ?></label><br>
										<label><input type="checkbox" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[hash_ip]" value="1" <?php checked( $opts['hash_ip'], '1' ); ?>>
											<?php echo esc_html__( 'Store IP as hash', 'uve-mailrelay-newsletter' ); ?></label>
									</div>
								</div>
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-retention-days"><?php echo esc_html__( 'Retention (days)', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-retention-days" type="number" min="1" class="small-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[retention_days]"
											value="<?php echo esc_attr( (string) $opts['retention_days'] ); ?>">
									</div>
								</div>
							</div>
						</div>

						<div class="uve-mr-panel">
							<h3><?php echo esc_html__( 'Rate limit', 'uve-mailrelay-newsletter' ); ?></h3>
							<div class="uve-mr-form-grid">
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-rate-max"><?php echo esc_html__( 'Max attempts per IP+email', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-rate-max" type="number" min="1" class="small-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[rate_limit_max]"
											value="<?php echo esc_attr( (string) $opts['rate_limit_max'] ); ?>">
									</div>
								</div>
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-rate-window"><?php echo esc_html__( 'Window (seconds)', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-rate-window" type="number" min="60" class="small-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[rate_limit_window_seconds]"
											value="<?php echo esc_attr( (string) $opts['rate_limit_window_seconds'] ); ?>">
									</div>
								</div>
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-confirm-max"><?php echo esc_html__( 'Max confirmation resends per IP+email (0 = disable)', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-confirm-max" type="number" min="0" class="small-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[confirm_resend_max]"
											value="<?php echo esc_attr( (string) $opts['confirm_resend_max'] ); ?>">
									</div>
								</div>
								<div class="uve-mr-field-row">
									<label class="uve-mr-field-label" for="uve-mr-confirm-window"><?php echo esc_html__( 'Confirmation window (seconds)', 'uve-mailrelay-newsletter' ); ?></label>
									<div class="uve-mr-field-control">
										<input id="uve-mr-confirm-window" type="number" min="60" class="small-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[confirm_resend_window_seconds]"
											value="<?php echo esc_attr( (string) $opts['confirm_resend_window_seconds'] ); ?>">
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="uve-mr-settings-help">
						<div class="uve-mr-panel uve-mr-help-panel">
							<h4><?php echo esc_html__( 'How settings are used', 'uve-mailrelay-newsletter' ); ?></h4>
							<ul class="uve-mr-help-list">
								<li><?php echo esc_html__( 'API credentials are required to load groups and send subscribers.', 'uve-mailrelay-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'These defaults are applied when creating new forms, but each form can override them.', 'uve-mailrelay-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Turnstile keys are shared across all forms.', 'uve-mailrelay-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Rate limit values reduce abuse but can block repeated tests.', 'uve-mailrelay-newsletter' ); ?></li>
							</ul>
						</div>

						<div class="uve-mr-panel uve-mr-help-panel">
							<h4><?php echo esc_html__( 'Shortcodes', 'uve-mailrelay-newsletter' ); ?></h4>
							<p><?php echo esc_html__( 'Use a form shortcode from the Forms screen, for example:', 'uve-mailrelay-newsletter' ); ?></p>
							<p><code>[uve_mailrelay_newsletter id="123"]</code></p>
						</div>
					</div>
				</div>

				<div class="uve-mr-sticky-save">
					<?php submit_button( __( 'Save Changes', 'uve-mailrelay-newsletter' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</div>
		<style>
			.uve-mr-settings-grid {
				display: grid;
				grid-template-columns: minmax(0, 3fr) minmax(260px, 1fr);
				gap: 24px;
				align-items: start;
				margin-top: 16px;
			}
			.uve-mr-settings-main,
			.uve-mr-settings-help {
				min-width: 0;
			}
			.uve-mr-panel {
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				padding: 16px;
				margin-bottom: 16px;
			}
			.uve-mr-panel h3,
			.uve-mr-panel h4 {
				margin-top: 0;
			}
			.uve-mr-help-panel {
				background: #f6f7f7;
			}
			.uve-mr-form-grid {
				display: grid;
				gap: 12px;
			}
			.uve-mr-field-row {
				display: grid;
				grid-template-columns: 220px minmax(0, 1fr);
				gap: 16px;
				align-items: start;
			}
			.uve-mr-field-label {
				font-weight: 600;
			}
			.uve-mr-field-control .regular-text {
				width: 100%;
				max-width: 420px;
			}
			.uve-mr-field-control .small-text {
				width: 140px;
			}
			.uve-mr-field-control select {
				min-width: 220px;
				max-width: 420px;
			}
			.uve-mr-help-list {
				margin: 0;
				padding-left: 18px;
			}
			.uve-mr-help-list li {
				margin-bottom: 8px;
			}
			.uve-mr-dual-list {
				display: grid;
				grid-template-columns: minmax(240px, 1fr) auto minmax(240px, 1fr);
				gap: 16px;
				align-items: center;
				max-width: 860px;
			}
			.uve-mr-dual-list select {
				width: 100%;
				min-height: 160px;
			}
			.uve-mr-dual-actions {
				display: flex;
				flex-direction: column;
				gap: 8px;
			}
			.uve-mr-dual-actions .button {
				width: 100%;
				justify-content: center;
			}
			.uve-mr-sticky-save {
				position: sticky;
				bottom: 12px;
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				padding: 12px 16px;
				display: inline-flex;
				gap: 8px;
				justify-content: flex-start;
				z-index: 2;
				box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
				margin-top: 12px;
			}
			.uve-mr-external-link {
				text-decoration: none;
			}
			.uve-mr-external-link .uve-mr-link-text {
				text-decoration: underline;
			}
			.uve-mr-external-link .dashicons {
				font-size: 16px;
				margin-left: 4px;
				vertical-align: text-bottom;
			}
			@media (max-width: 960px) {
				.uve-mr-settings-grid {
					grid-template-columns: 1fr;
				}
			}
			@media (max-width: 782px) {
				.uve-mr-field-row {
					grid-template-columns: 1fr;
				}
				.uve-mr-field-label {
					margin-bottom: 4px;
				}
				.uve-mr-dual-list {
					grid-template-columns: 1fr;
				}
				.uve-mr-dual-actions {
					flex-direction: row;
					justify-content: flex-start;
				}
				.uve-mr-dual-actions .button {
					width: auto;
				}
			}
		</style>
		<script>
			(function() {
				var available = document.getElementById('uve-mr-settings-groups-available');
				var selected = document.getElementById('uve-mr-settings-groups-selected');
				var hiddenInput = document.getElementById('uve-mr-settings-group-ids');
				var addBtn = document.getElementById('uve-mr-settings-group-add');
				var removeBtn = document.getElementById('uve-mr-settings-group-remove');

				function moveSelected(from, to) {
					var opts = Array.from(from.options).filter(function(opt) { return opt.selected; });
					opts.forEach(function(opt) {
						opt.selected = false;
						to.appendChild(opt);
					});
					updateHidden();
				}

				function updateHidden() {
					if (!hiddenInput || !selected) return;
					var ids = Array.from(selected.options).map(function(opt) { return opt.value; });
					hiddenInput.value = ids.join(',');
				}

				if (addBtn && removeBtn && available && selected) {
					addBtn.addEventListener('click', function() { moveSelected(available, selected); });
					removeBtn.addEventListener('click', function() { moveSelected(selected, available); });
					available.addEventListener('dblclick', function() { moveSelected(available, selected); });
					selected.addEventListener('dblclick', function() { moveSelected(selected, available); });
					updateHidden();
				}
			})();
		</script>
		<?php
	}

	/**
	 * Render the logs page.
	 *
	 * @return void
	 */
	public static function render_logs_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Ensure schema is up-to-date before rendering (safe + idempotent).
		UVE_MR_Logs::maybe_create_or_update_table();

		if ( ! empty( $_POST['uve_mr_purge_now'] ) && check_admin_referer( 'uve_mr_purge_now' ) ) {
			$deleted = UVE_MR_Logs::purge_old_logs( true );
			// translators: %s: number of records deleted.
			echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Purged %s records.', 'uve-mailrelay-newsletter' ), (string) $deleted ) ) . '</p></div>';
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'MR4WP Logs', 'uve-mailrelay-newsletter' ); ?></h1>

			<?php UVE_MR_Logs::render_logs_table_safe(); ?>

			<form method="post" style="margin-top:12px;">
				<?php wp_nonce_field( 'uve_mr_purge_now' ); ?>
				<button type="submit" class="button" name="uve_mr_purge_now" value="1"><?php echo esc_html__( 'Purge now', 'uve-mailrelay-newsletter' ); ?></button>
			</form>
		</div>
		<?php
	}
}
