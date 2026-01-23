<?php
/**
 * Admin settings and UI.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings and UI.
 */
final class RelayPress_Admin {
	const REVIEW_NOTICE_META        = 'relaypress_dismiss_review_notice';
	const REVIEW_NOTICE_SNOOZE_META = 'relaypress_review_notice_snooze_until';

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public static function admin_menu(): void {
		add_menu_page(
			__( 'RelayPress', 'relaypress-newsletter' ),
			__( 'RelayPress', 'relaypress-newsletter' ),
			'manage_options',
			'relaypress-newsletter',
			array( __CLASS__, 'render_settings_page' ),
			'dashicons-email-alt2'
		);

		add_submenu_page(
			'relaypress-newsletter',
			__( 'Settings', 'relaypress-newsletter' ),
			__( 'Settings', 'relaypress-newsletter' ),
			'manage_options',
			'relaypress-newsletter',
			array( __CLASS__, 'render_settings_page' )
		);

		add_submenu_page(
			'relaypress-newsletter',
			__( 'Logs', 'relaypress-newsletter' ),
			__( 'Logs', 'relaypress-newsletter' ),
			'manage_options',
			'relaypress-newsletter-logs',
			array( __CLASS__, 'render_logs_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public static function admin_init(): void {
		if ( current_user_can( 'manage_options' ) && isset( $_GET['relaypress_refresh_groups'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nonce = sanitize_text_field( (string) wp_unslash( $_GET['_wpnonce'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $nonce && wp_verify_nonce( $nonce, 'relaypress_refresh_groups' ) ) {
				delete_transient( 'relaypress_groups_cache' );
			}
		}
		if ( current_user_can( 'manage_options' ) && isset( $_GET['relaypress_dismiss_review_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nonce = sanitize_text_field( (string) wp_unslash( $_GET['_wpnonce'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $nonce && wp_verify_nonce( $nonce, 'relaypress_dismiss_review_notice' ) ) {
				update_user_meta( get_current_user_id(), self::REVIEW_NOTICE_META, '1' );
				delete_user_meta( get_current_user_id(), self::REVIEW_NOTICE_SNOOZE_META );
			}
		}
		if ( current_user_can( 'manage_options' ) && isset( $_GET['relaypress_review_notice_later'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nonce = sanitize_text_field( (string) wp_unslash( $_GET['_wpnonce'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $nonce && wp_verify_nonce( $nonce, 'relaypress_review_notice_later' ) ) {
				update_user_meta( get_current_user_id(), self::REVIEW_NOTICE_SNOOZE_META, (string) ( time() + 14 * DAY_IN_SECONDS ) );
			}
		}

		register_setting(
			'relaypress_newsletter',
			RelayPress_Newsletter::OPT_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
				'default'           => RelayPress_Newsletter::defaults(),
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
		if ( 'toplevel_page_relaypress-newsletter' === $hook_suffix ) {
			$base   = RelayPress_Utils::plugin_file();
			$js_src = plugins_url( 'assets/admin-forms.js', $base );
			wp_enqueue_script( 'relaypress-admin-settings', $js_src, array(), RelayPress_Newsletter::VERSION, true );
		}

		if ( false !== strpos( $hook_suffix, 'relaypress-newsletter-logs' ) ) {
			$src = plugins_url( 'assets/admin-logs.css', RelayPress_Utils::plugin_file() );
			wp_enqueue_style( 'relaypress-admin-logs', $src, array(), RelayPress_Newsletter::VERSION );
		}

		$show_upgrade = (bool) apply_filters( 'relaypress_show_upgrade_ui', true );
		if ( $show_upgrade ) {
			$src = plugins_url( 'assets/admin-upgrade.css', RelayPress_Utils::plugin_file() );
			wp_enqueue_style( 'relaypress-admin-upgrade', $src, array(), RelayPress_Newsletter::VERSION );
		}
	}

	/**
	 * Render admin notices.
	 *
	 * @return void
	 */
	public static function admin_notices(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		$screen_id = (string) $screen->id;
		if ( 'dashboard' !== $screen_id && false === strpos( $screen_id, 'relaypress-newsletter' ) ) {
			return;
		}

		$turnstile_state = new RelayPress_WP_Extension_State_Repository();
		if ( $turnstile_state->is_enabled( RelayPress_Turnstile_Extension_Provider::SLUG, true )
			&& ! RelayPress_Container::turnstile_config()->is_enabled()
		) {
			$turnstile_url = admin_url( 'admin.php?page=relaypress-newsletter-turnstile' );
			$message       = sprintf(
				/* translators: %s: Turnstile settings URL. */
				__( 'Turnstile is enabled but not configured. <a href="%s">Add your site and secret keys</a> to avoid validation errors.', 'relaypress-newsletter' ),
				esc_url( $turnstile_url )
			);
			?>
			<div class="notice notice-warning">
				<p><?php echo wp_kses_post( $message ); ?></p>
			</div>
			<?php
		}

		$info = RelayPress_Frontend::get_template_info();
		if ( empty( $info['override'] ) ) {
			$info = null;
		}
		if ( $info ) {
			$version = (string) ( $info['version'] ?? '' );
			if ( '' === $version || version_compare( $version, RelayPress_Frontend::TEMPLATE_VERSION, '<' ) ) {
				$message = sprintf(
					/* translators: %s: template version. */
					__( 'Your theme is overriding the RelayPress form template. Please update the template to version %s to ensure compatibility.', 'relaypress-newsletter' ),
					RelayPress_Frontend::TEMPLATE_VERSION
				);
				?>
				<div class="notice notice-warning is-dismissible">
					<p><?php echo esc_html( $message ); ?></p>
					<p><?php echo esc_html__( 'Override locations: relaypress-newsletter/form.php or mr4wp/form.php.', 'relaypress-newsletter' ); ?></p>
				</div>
				<?php
			}
		}

		if ( 'dashboard' === $screen_id || false === strpos( $screen_id, 'relaypress-newsletter' ) ) {
			return;
		}
		if ( '1' === (string) get_user_meta( get_current_user_id(), self::REVIEW_NOTICE_META, true ) ) {
			return;
		}
		$snooze_until = (int) get_user_meta( get_current_user_id(), self::REVIEW_NOTICE_SNOOZE_META, true );
		if ( $snooze_until && time() < $snooze_until ) {
			return;
		}
		$review_url  = 'https://wordpress.org/support/plugin/relaypress-newsletter/reviews/?rate=5#new-post';
		$dismiss_url = add_query_arg(
			array(
				'relaypress_dismiss_review_notice' => '1',
				'_wpnonce'                         => wp_create_nonce( 'relaypress_dismiss_review_notice' ),
			),
			admin_url( 'admin.php?page=relaypress-newsletter' )
		);
		$later_url   = add_query_arg(
			array(
				'relaypress_review_notice_later' => '1',
				'_wpnonce'                       => wp_create_nonce( 'relaypress_review_notice_later' ),
			),
			admin_url( 'admin.php?page=relaypress-newsletter' )
		);
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php echo esc_html__( 'Enjoying RelayPress? Please consider leaving a review on WordPress.org.', 'relaypress-newsletter' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $review_url ); ?>" target="_blank" rel="noopener">
					<?php echo esc_html__( 'Leave a review', 'relaypress-newsletter' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( $later_url ); ?>">
					<?php echo esc_html__( 'Review later', 'relaypress-newsletter' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( $dismiss_url ); ?>">
					<?php echo esc_html__( 'Dismiss', 'relaypress-newsletter' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Reorder submenu items for the plugin menu.
	 *
	 * @return void
	 */
	public static function reorder_submenu(): void {
		global $submenu;
		$parent = 'relaypress-newsletter';
		if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
			return;
		}

		$desired = array(
			'relaypress-newsletter',
			'relaypress-newsletter-forms',
			'relaypress-newsletter-logs',
			'relaypress-newsletter-extensions',
			'relaypress-newsletter-upgrade',
		);

		$ordered = array();
		$other   = array();
		foreach ( $submenu[ $parent ] as $item ) {
			$slug = $item[2] ?? '';
			if ( in_array( $slug, $desired, true ) ) {
				$ordered[ $slug ] = $item;
				continue;
			}
			$other[] = $item;
		}

		$final = array();
		foreach ( $desired as $slug ) {
			if ( isset( $ordered[ $slug ] ) ) {
				$final[] = $ordered[ $slug ];
			}
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu[ $parent ] = array_merge( $final, $other );
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param mixed $raw Raw options.
	 * @return array
	 */
	public static function sanitize_options( $raw ): array {
		$raw = is_array( $raw ) ? $raw : array();
		$def = RelayPress_Newsletter::defaults();

		$out                 = array();
		$out['api_base_url'] = esc_url_raw( trim( (string) ( $raw['api_base_url'] ?? $def['api_base_url'] ) ) );
		$out['api_token']    = sanitize_text_field( (string) ( $raw['api_token'] ?? $def['api_token'] ) );
		$out['group_ids']    = sanitize_text_field( (string) ( $raw['group_ids'] ?? $def['group_ids'] ) );

		$status                       = sanitize_text_field( (string) ( $raw['subscriber_status'] ?? $def['subscriber_status'] ) );
		$out['subscriber_status']     = in_array( $status, array( 'inactive', 'active' ), true ) ? $status : 'inactive';
		$out['mailrelay_queue_first'] = ! empty( $raw['mailrelay_queue_first'] ) ? '1' : '0';

		$out['turnstile_site_key']   = sanitize_text_field( (string) ( $raw['turnstile_site_key'] ?? $def['turnstile_site_key'] ) );
		$out['turnstile_secret_key'] = sanitize_text_field( (string) ( $raw['turnstile_secret_key'] ?? $def['turnstile_secret_key'] ) );

		$out['title']             = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['title'] ?? $def['title'] ) ) );
		$out['description']       = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['description'] ?? $def['description'] ) ) );
		$out['email_placeholder'] = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['email_placeholder'] ?? $def['email_placeholder'] ) ) );
		$out['submit_label']      = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['submit_label'] ?? $def['submit_label'] ) ) );
		$out['ajax_mode']         = ! empty( $raw['ajax_mode'] ) ? '1' : '0';
		$locale_fallback_raw      = sanitize_text_field( (string) ( $raw['locale_fallback'] ?? $def['locale_fallback'] ) );
		$locale_fallback          = RelayPress_Utils::normalize_locale( $locale_fallback_raw );
		$out['locale_fallback']   = '' !== $locale_fallback ? $locale_fallback : RelayPress_Utils::default_locale_fallback();
		$locale_mode_raw          = sanitize_text_field( (string) ( $raw['locale_mode'] ?? $def['locale_mode'] ) );
		$out['locale_mode']       = in_array( $locale_mode_raw, array( 'browser', 'force' ), true ) ? $locale_mode_raw : 'browser';
		$locale_force_raw         = sanitize_text_field( (string) ( $raw['locale_force'] ?? $def['locale_force'] ) );
		$locale_force             = RelayPress_Utils::normalize_locale( $locale_force_raw );
		if ( '' === $locale_force ) {
			$locale_force = RelayPress_Utils::default_locale_fallback();
		}
		$out['locale_force'] = $locale_force;

		$default_phone_country_raw         = sanitize_text_field( (string) ( $raw['default_phone_country'] ?? $def['default_phone_country'] ) );
		$default_phone_country_norm        = RelayPress_Utils::normalize_country_code( $default_phone_country_raw );
		$out['default_phone_country']      = $default_phone_country_norm;
		$out['send_raw_phone_on_fail']     = ! empty( $raw['send_raw_phone_on_fail'] ) ? '1' : '0';
		$out['hide_phone_prefix_selector'] = ! empty( $raw['hide_phone_prefix_selector'] ) ? '1' : '0';
		if ( '' === $default_phone_country_norm ) {
			$out['hide_phone_prefix_selector'] = '0';
		}

		$out['privacy_url']   = esc_url_raw( trim( (string) ( $raw['privacy_url'] ?? $def['privacy_url'] ) ) );
		$out['consent_label'] = RelayPress_Utils::normalize_text( sanitize_text_field( (string) ( $raw['consent_label'] ?? $def['consent_label'] ) ) );

		$out['store_consent_log'] = ! empty( $raw['store_consent_log'] ) ? '1' : '0';
		$out['log_phone_raw']     = ! empty( $raw['log_phone_raw'] ) ? '1' : '0';
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

		$opts = RelayPress_Newsletter::get_options();
		?>
		<div class="wrap">
		<h1><?php echo esc_html__( 'RelayPress', 'relaypress-newsletter' ); ?></h1>
			<?php
			$updated = isset( $_GET['settings-updated'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['settings-updated'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' !== $updated ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Settings saved successfully.', 'relaypress-newsletter' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'relaypress_newsletter' ); ?>
				<div class="relaypress-settings-grid">
					<div class="relaypress-settings-main">
						<div class="relaypress-panel">
							<h3><?php echo esc_html__( 'Mailrelay API', 'relaypress-newsletter' ); ?></h3>
							<div class="relaypress-form-grid">
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-api-base"><?php echo esc_html__( 'Mailrelay API base URL', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-api-base" type="text" class="regular-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[api_base_url]"
											value="<?php echo esc_attr( $opts['api_base_url'] ); ?>"
											placeholder="https://YOURACCOUNT.ipzmarketing.com/api/v1" />
										<p class="description">
											<?php echo esc_html__( 'Format example: https://YOURACCOUNT.ipzmarketing.com/api/v1', 'relaypress-newsletter' ); ?>
										</p>
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-api-token"><?php echo esc_html__( 'Mailrelay API token', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-api-token" type="password" class="regular-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[api_token]"
											value="<?php echo esc_attr( $opts['api_token'] ); ?>" />
										<p class="description">
											<a class="relaypress-external-link" href="<?php echo esc_url( 'https://help.mailrelay.com/en/support/solutions/articles/103000160465-api-keys' ); ?>" target="_blank" rel="noopener noreferrer">
												<span class="relaypress-link-text"><?php echo esc_html__( 'API key documentation', 'relaypress-newsletter' ); ?></span>
												<span class="screen-reader-text"> <?php echo esc_html__( '(opens in a new tab)', 'relaypress-newsletter' ); ?></span>
												<span class="dashicons dashicons-external" aria-hidden="true"></span>
											</a>
										</p>
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-mailrelay-queue-first"><?php echo esc_html__( 'Queue first attempt', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<label>
											<input id="relaypress-mailrelay-queue-first" type="checkbox" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[mailrelay_queue_first]" value="1" <?php checked( $opts['mailrelay_queue_first'] ?? '0', '1' ); ?>>
											<?php echo esc_html__( 'Send the first Mailrelay request to Action Scheduler instead of running it immediately.', 'relaypress-newsletter' ); ?>
										</label>
										<p class="description"><?php echo esc_html__( 'Improves performance but may delay the opt-in email.', 'relaypress-newsletter' ); ?></p>
									</div>
								</div>
							</div>
						</div>

						<div class="relaypress-panel">
							<h3><?php echo esc_html__( 'Defaults for new forms', 'relaypress-newsletter' ); ?></h3>
							<div class="relaypress-form-grid">
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-settings-groups-available"><?php echo esc_html__( 'Default groups', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<?php
										$group_ids   = RelayPress_Utils::parse_group_ids( (string) $opts['group_ids'] );
										$groups      = RelayPress_Container::mailrelay_client()->get_groups();
										$refresh_url = wp_nonce_url(
											add_query_arg(
												array(
													'page' => 'relaypress-newsletter',
													'relaypress_refresh_groups' => 1,
												),
												admin_url( 'admin.php' )
											),
											'relaypress_refresh_groups'
										);
										?>
										<input type="hidden" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[group_ids]" id="relaypress-settings-group-ids" value="<?php echo esc_attr( implode( ',', $group_ids ) ); ?>">
										<div class="relaypress-dual-list">
											<div>
												<label class="screen-reader-text" for="relaypress-settings-groups-available"><?php echo esc_html__( 'Available groups', 'relaypress-newsletter' ); ?></label>
												<select id="relaypress-settings-groups-available" multiple size="8">
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
												<p class="description"><?php echo esc_html__( 'Available', 'relaypress-newsletter' ); ?></p>
											</div>
											<div class="relaypress-dual-actions">
												<button type="button" class="button" id="relaypress-settings-group-add"><?php echo esc_html__( 'Add →', 'relaypress-newsletter' ); ?></button>
												<button type="button" class="button" id="relaypress-settings-group-remove"><?php echo esc_html__( '← Remove', 'relaypress-newsletter' ); ?></button>
											</div>
											<div>
												<label class="screen-reader-text" for="relaypress-settings-groups-selected"><?php echo esc_html__( 'Selected groups', 'relaypress-newsletter' ); ?></label>
												<select id="relaypress-settings-groups-selected" multiple size="8">
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
												<p class="description"><?php echo esc_html__( 'Selected', 'relaypress-newsletter' ); ?></p>
											</div>
										</div>
										<p class="description">
											<?php echo esc_html__( 'These groups will be preselected when creating a new form.', 'relaypress-newsletter' ); ?>
											<a href="<?php echo esc_url( $refresh_url ); ?>"><?php echo esc_html__( 'Refresh groups', 'relaypress-newsletter' ); ?></a>
										</p>
										<?php if ( empty( $groups ) ) : ?>
											<p class="description"><?php echo esc_html__( 'No groups found or API not configured.', 'relaypress-newsletter' ); ?></p>
										<?php endif; ?>
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-default-status"><?php echo esc_html__( 'Subscription mode', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<select id="relaypress-default-status" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[subscriber_status]">
											<option value="inactive" <?php selected( $opts['subscriber_status'], 'inactive' ); ?>><?php echo esc_html__( 'Double opt-in (inactive + confirmation)', 'relaypress-newsletter' ); ?></option>
											<option value="active" <?php selected( $opts['subscriber_status'], 'active' ); ?>><?php echo esc_html__( 'Single opt-in (active)', 'relaypress-newsletter' ); ?></option>
										</select>
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-locale-fallback"><?php echo esc_html__( 'Default locale fallback', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<?php
										$locale_labels   = RelayPress_Utils::locale_labels();
										$locale_fallback = RelayPress_Utils::normalize_locale( (string) ( $opts['locale_fallback'] ?? '' ) );
										if ( '' === $locale_fallback ) {
											$locale_fallback = RelayPress_Utils::default_locale_fallback();
										}
										?>
										<select id="relaypress-locale-fallback" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[locale_fallback]">
											<?php foreach ( $locale_labels as $value => $label ) : ?>
												<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $locale_fallback, $value ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description"><?php echo esc_html__( 'Used when the browser language is not supported.', 'relaypress-newsletter' ); ?></p>
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-locale-mode"><?php echo esc_html__( 'Default locale mode', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<select id="relaypress-locale-mode" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[locale_mode]">
											<option value="browser" <?php selected( (string) ( $opts['locale_mode'] ?? 'browser' ), 'browser' ); ?>>
												<?php echo esc_html__( 'Use browser language', 'relaypress-newsletter' ); ?>
											</option>
											<option value="force" <?php selected( (string) ( $opts['locale_mode'] ?? 'browser' ), 'force' ); ?>>
												<?php echo esc_html__( 'Force a specific language', 'relaypress-newsletter' ); ?>
											</option>
										</select>
									</div>
								</div>
								<div class="relaypress-field-row relaypress-locale-force-row">
									<label class="relaypress-field-label" for="relaypress-locale-force"><?php echo esc_html__( 'Default forced language', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<?php
										$locale_force = RelayPress_Utils::normalize_locale( (string) ( $opts['locale_force'] ?? '' ) );
										if ( '' === $locale_force ) {
											$locale_force = RelayPress_Utils::default_locale_fallback();
										}
										?>
										<select id="relaypress-locale-force" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[locale_force]">
											<?php foreach ( $locale_labels as $value => $label ) : ?>
												<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $locale_force, $value ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description"><?php echo esc_html__( 'Used when forcing a language globally.', 'relaypress-newsletter' ); ?></p>
									</div>
								</div>
							</div>
						</div>

						<div class="relaypress-panel">
							<h3><?php echo esc_html__( 'Phone normalization', 'relaypress-newsletter' ); ?></h3>
							<div class="relaypress-form-grid">
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-default-phone-country"><?php echo esc_html__( 'Default phone country', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<?php
										$country_options       = RelayPress_Utils::country_options();
										$default_phone_country = RelayPress_Utils::normalize_country_code( (string) ( $opts['default_phone_country'] ?? '' ) );
										$phone_country_labels  = array();
										foreach ( $country_options as $code => $label ) {
											$prefix = RelayPress_Phone_Normalizer::calling_code_for_country( (string) $code );
											if ( '' !== $prefix ) {
												$label .= ' (+' . $prefix . ')';
											}
											$phone_country_labels[ $code ] = $label;
										}
										uasort(
											$phone_country_labels,
											static function ( string $left, string $right ): int {
												return strcasecmp( $left, $right );
											}
										);
										?>
										<select id="relaypress-default-phone-country" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[default_phone_country]">
											<option value=""><?php echo esc_html__( 'No default', 'relaypress-newsletter' ); ?></option>
											<?php foreach ( $phone_country_labels as $value => $label ) : ?>
												<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $default_phone_country, $value ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description"><?php echo esc_html__( 'Used to normalize local phone numbers when no country is provided in the form.', 'relaypress-newsletter' ); ?></p>
									</div>
								</div>
								<div class="relaypress-field-row">
									<span class="relaypress-field-label"><?php echo esc_html__( 'Phone prefix selector', 'relaypress-newsletter' ); ?></span>
									<div class="relaypress-field-control">
										<?php $can_hide_prefix = '' !== $default_phone_country; ?>
										<label><input id="relaypress-hide-phone-prefix" type="checkbox" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[hide_phone_prefix_selector]" value="1" <?php checked( $opts['hide_phone_prefix_selector'] ?? '0', '1' ); ?> <?php disabled( ! $can_hide_prefix ); ?>>
											<?php echo esc_html__( 'Hide the phone prefix selector and use the default country prefix', 'relaypress-newsletter' ); ?></label>
										<p id="relaypress-hide-phone-prefix-note" class="description" <?php echo $can_hide_prefix ? 'style="display:none;"' : ''; ?>><?php echo esc_html__( 'Select a default phone country to enable this option.', 'relaypress-newsletter' ); ?></p>
										<p id="relaypress-hide-phone-prefix-override" class="description" <?php echo $can_hide_prefix ? '' : 'style="display:none;"'; ?>><?php echo esc_html__( 'If the user enters a phone prefix, it will be used instead of the default.', 'relaypress-newsletter' ); ?></p>
									</div>
								</div>
								<div class="relaypress-field-row">
									<span class="relaypress-field-label"><?php echo esc_html__( 'Raw phone fallback', 'relaypress-newsletter' ); ?></span>
									<div class="relaypress-field-control">
										<label><input type="checkbox" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[send_raw_phone_on_fail]" value="1" <?php checked( $opts['send_raw_phone_on_fail'] ?? '0', '1' ); ?>>
											<?php echo esc_html__( 'Send raw phone to Mailrelay when normalization fails', 'relaypress-newsletter' ); ?></label>
										<p class="description"><?php echo esc_html__( 'Unprocessed phone numbers may be rejected by Mailrelay and are not recommended.', 'relaypress-newsletter' ); ?></p>
									</div>
								</div>
							</div>
						</div>

						<div class="relaypress-panel">
							<h3><?php echo esc_html__( 'Text defaults', 'relaypress-newsletter' ); ?></h3>
							<div class="relaypress-form-grid">
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-title"><?php echo esc_html__( 'Title', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-title" type="text" class="regular-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[title]" value="<?php echo esc_attr( $opts['title'] ); ?>">
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-description"><?php echo esc_html__( 'Description', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-description" type="text" class="regular-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[description]" value="<?php echo esc_attr( $opts['description'] ); ?>">
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-email-placeholder"><?php echo esc_html__( 'Email placeholder', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-email-placeholder" type="text" class="regular-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[email_placeholder]" value="<?php echo esc_attr( $opts['email_placeholder'] ); ?>">
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-submit-label"><?php echo esc_html__( 'Button text', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-submit-label" type="text" class="regular-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[submit_label]" value="<?php echo esc_attr( $opts['submit_label'] ); ?>">
									</div>
								</div>
								<div class="relaypress-field-row">
									<span class="relaypress-field-label"><?php echo esc_html__( 'Enable AJAX submissions', 'relaypress-newsletter' ); ?></span>
									<div class="relaypress-field-control">
										<label><input type="checkbox" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[ajax_mode]" value="1" <?php checked( $opts['ajax_mode'] ?? '0', '1' ); ?>>
											<?php echo esc_html__( 'Submit via AJAX', 'relaypress-newsletter' ); ?></label>
									</div>
								</div>
							</div>
						</div>

						<div class="relaypress-panel">
							<h3><?php echo esc_html__( 'GDPR + Logs', 'relaypress-newsletter' ); ?></h3>
							<div class="relaypress-form-grid">
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-privacy-url"><?php echo esc_html__( 'Privacy policy URL', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-privacy-url" type="text" class="regular-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[privacy_url]"
											value="<?php echo esc_attr( $opts['privacy_url'] ); ?>">
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-consent-label"><?php echo esc_html__( 'Checkbox text', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-consent-label" type="text" class="regular-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[consent_label]"
											value="<?php echo esc_attr( $opts['consent_label'] ); ?>">
									</div>
								</div>
								<div class="relaypress-field-row">
									<span class="relaypress-field-label"><?php echo esc_html__( 'Consent logging', 'relaypress-newsletter' ); ?></span>
									<div class="relaypress-field-control">
										<label><input type="checkbox" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[store_consent_log]" value="1" <?php checked( $opts['store_consent_log'], '1' ); ?>>
											<?php echo esc_html__( 'Store log in database', 'relaypress-newsletter' ); ?></label><br>
										<label><input type="checkbox" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[log_phone_raw]" value="1" <?php checked( $opts['log_phone_raw'] ?? '0', '1' ); ?>>
											<?php echo esc_html__( 'Store raw phone input in logs', 'relaypress-newsletter' ); ?></label><br>
										<label><input type="checkbox" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[hash_ip]" value="1" <?php checked( $opts['hash_ip'], '1' ); ?>>
											<?php echo esc_html__( 'Store IP as hash', 'relaypress-newsletter' ); ?></label>
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-retention-days"><?php echo esc_html__( 'Retention (days)', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-retention-days" type="number" min="1" class="small-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[retention_days]"
											value="<?php echo esc_attr( (string) $opts['retention_days'] ); ?>">
									</div>
								</div>
							</div>
						</div>

						<div class="relaypress-panel">
							<h3><?php echo esc_html__( 'Rate limit', 'relaypress-newsletter' ); ?></h3>
							<div class="relaypress-form-grid">
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-rate-max"><?php echo esc_html__( 'Max attempts per IP+email', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-rate-max" type="number" min="1" class="small-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[rate_limit_max]"
											value="<?php echo esc_attr( (string) $opts['rate_limit_max'] ); ?>">
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-rate-window"><?php echo esc_html__( 'Window (seconds)', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-rate-window" type="number" min="60" class="small-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[rate_limit_window_seconds]"
											value="<?php echo esc_attr( (string) $opts['rate_limit_window_seconds'] ); ?>">
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-confirm-max"><?php echo esc_html__( 'Max confirmation resends per IP+email (0 = disable)', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-confirm-max" type="number" min="0" class="small-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[confirm_resend_max]"
											value="<?php echo esc_attr( (string) $opts['confirm_resend_max'] ); ?>">
									</div>
								</div>
								<div class="relaypress-field-row">
									<label class="relaypress-field-label" for="relaypress-confirm-window"><?php echo esc_html__( 'Confirmation window (seconds)', 'relaypress-newsletter' ); ?></label>
									<div class="relaypress-field-control">
										<input id="relaypress-confirm-window" type="number" min="60" class="small-text" name="<?php echo esc_attr( RelayPress_Newsletter::OPT_KEY ); ?>[confirm_resend_window_seconds]"
											value="<?php echo esc_attr( (string) $opts['confirm_resend_window_seconds'] ); ?>">
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="relaypress-settings-help">
						<div class="relaypress-panel relaypress-help-panel">
							<h4><?php echo esc_html__( 'How settings are used', 'relaypress-newsletter' ); ?></h4>
							<ul class="relaypress-help-list">
								<li><?php echo esc_html__( 'API credentials are required to load groups and send subscribers.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'These defaults are applied when creating new forms, but each form can override them.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Turnstile keys are shared across all forms.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Rate limit values reduce abuse but can block repeated tests.', 'relaypress-newsletter' ); ?></li>
							</ul>
						</div>

						<div class="relaypress-panel relaypress-help-panel">
							<h4><?php echo esc_html__( 'Anti-spam settings', 'relaypress-newsletter' ); ?></h4>
							<p>
								<?php
								printf(
									wp_kses_post(
										/* translators: %s: Extensions settings URL. */
										__( 'Configure anti-spam protections from the <a href="%s">Extensions</a> screen.', 'relaypress-newsletter' )
									),
									esc_url( admin_url( 'admin.php?page=relaypress-newsletter-extensions' ) )
								);
								?>
							</p>
						</div>

						<div class="relaypress-panel relaypress-help-panel">
							<h4><?php echo esc_html__( 'Shortcodes', 'relaypress-newsletter' ); ?></h4>
							<p><?php echo esc_html__( 'Use a form shortcode from the Forms screen, for example:', 'relaypress-newsletter' ); ?></p>
							<p><code>[relaypress_newsletter id="123"]</code></p>
						</div>
					</div>
				</div>

				<div class="relaypress-sticky-save">
					<?php submit_button( __( 'Save Changes', 'relaypress-newsletter' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
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
			.relaypress-field-control .small-text {
				width: 140px;
			}
			.relaypress-field-control select {
				min-width: 220px;
				max-width: 420px;
			}
			.relaypress-help-list {
				margin: 0;
				padding-left: 18px;
			}
			.relaypress-help-list li {
				margin-bottom: 8px;
			}
			.relaypress-dual-list {
				display: grid;
				grid-template-columns: minmax(240px, 1fr) auto minmax(240px, 1fr);
				gap: 16px;
				align-items: center;
				max-width: 860px;
			}
			.relaypress-dual-list select {
				width: 100%;
				min-height: 160px;
			}
			.relaypress-dual-actions {
				display: flex;
				flex-direction: column;
				gap: 8px;
			}
			.relaypress-dual-actions .button {
				width: 100%;
				justify-content: center;
			}
			.relaypress-sticky-save {
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
			.relaypress-external-link {
				text-decoration: none;
			}
			.relaypress-external-link .relaypress-link-text {
				text-decoration: underline;
			}
			.relaypress-external-link .dashicons {
				font-size: 16px;
				margin-left: 4px;
				vertical-align: text-bottom;
			}
			@media (max-width: 960px) {
				.relaypress-settings-grid {
					grid-template-columns: 1fr;
				}
			}
			@media (max-width: 782px) {
				.relaypress-field-row {
					grid-template-columns: 1fr;
				}
				.relaypress-field-label {
					margin-bottom: 4px;
				}
				.relaypress-dual-list {
					grid-template-columns: 1fr;
				}
				.relaypress-dual-actions {
					flex-direction: row;
					justify-content: flex-start;
				}
				.relaypress-dual-actions .button {
					width: auto;
				}
			}
		</style>
		<script>
			(function() {
				var available = document.getElementById('relaypress-settings-groups-available');
				var selected = document.getElementById('relaypress-settings-groups-selected');
				var hiddenInput = document.getElementById('relaypress-settings-group-ids');
				var addBtn = document.getElementById('relaypress-settings-group-add');
				var removeBtn = document.getElementById('relaypress-settings-group-remove');
				var phoneCountry = document.getElementById('relaypress-default-phone-country');
				var hidePhonePrefix = document.getElementById('relaypress-hide-phone-prefix');
				var hidePhonePrefixNote = document.getElementById('relaypress-hide-phone-prefix-note');

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

				function syncPhonePrefixToggle() {
					if (!phoneCountry || !hidePhonePrefix) return;
					var hasDefault = !!phoneCountry.value;
					hidePhonePrefix.disabled = !hasDefault;
					if (!hasDefault) {
						hidePhonePrefix.checked = false;
					}
					if (hidePhonePrefixNote) {
						hidePhonePrefixNote.style.display = hasDefault ? 'none' : 'block';
					}
					var overrideNote = document.getElementById('relaypress-hide-phone-prefix-override');
					if (overrideNote) {
						overrideNote.style.display = hasDefault ? 'block' : 'none';
					}
				}

				if (phoneCountry && hidePhonePrefix) {
					phoneCountry.addEventListener('change', syncPhonePrefixToggle);
					syncPhonePrefixToggle();
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
		RelayPress_Logs::maybe_create_or_update_table();

		if ( ! empty( $_POST['relaypress_purge_now'] ) && check_admin_referer( 'relaypress_purge_now' ) ) {
			$deleted = RelayPress_Logs::purge_old_logs( true );
			// translators: %s: number of records deleted.
			echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Purged %s records.', 'relaypress-newsletter' ), (string) $deleted ) ) . '</p></div>';
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'RelayPress Logs', 'relaypress-newsletter' ); ?></h1>

			<?php RelayPress_Logs::render_logs_table_safe(); ?>

			<form method="post" style="margin-top:12px;">
				<?php wp_nonce_field( 'relaypress_purge_now' ); ?>
				<button type="submit" class="button" name="relaypress_purge_now" value="1"><?php echo esc_html__( 'Purge now', 'relaypress-newsletter' ); ?></button>
			</form>
		</div>
		<?php
	}
}
