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
		add_options_page(
			__( 'MR4WP', 'uve-mailrelay-newsletter' ),
			__( 'MR4WP', 'uve-mailrelay-newsletter' ),
			'manage_options',
			'uve-mr-newsletter',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public static function admin_init(): void {
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

		$out['title']             = sanitize_text_field( (string) ( $raw['title'] ?? $def['title'] ) );
		$out['description']       = sanitize_text_field( (string) ( $raw['description'] ?? $def['description'] ) );
		$out['email_placeholder'] = sanitize_text_field( (string) ( $raw['email_placeholder'] ?? $def['email_placeholder'] ) );
		$out['submit_label']      = sanitize_text_field( (string) ( $raw['submit_label'] ?? $def['submit_label'] ) );

		$out['privacy_url']   = esc_url_raw( trim( (string) ( $raw['privacy_url'] ?? $def['privacy_url'] ) ) );
		$out['consent_label'] = sanitize_text_field( (string) ( $raw['consent_label'] ?? $def['consent_label'] ) );

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

		// Ensure schema is up-to-date before rendering (safe + idempotent).
		UVE_MR_Logs::maybe_create_or_update_table();

		if ( ! empty( $_POST['uve_mr_purge_now'] ) && check_admin_referer( 'uve_mr_purge_now' ) ) {
			$deleted = UVE_MR_Logs::purge_old_logs( true );
			// translators: %s: number of records deleted.
			echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Purgados %s registros.', 'uve-mailrelay-newsletter' ), (string) $deleted ) ) . '</p></div>';
		}

		$opts = UVE_Mailrelay_Newsletter::get_options();
		?>
		<div class="wrap">
		<h1><?php echo esc_html__( 'MR4WP', 'uve-mailrelay-newsletter' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'uve_mr_newsletter' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Mailrelay API base URL', 'uve-mailrelay-newsletter' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[api_base_url]"
								value="<?php echo esc_attr( $opts['api_base_url'] ); ?>"
								placeholder="https://YOURACCOUNT.ipzmarketing.com/api/v1" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Mailrelay API token', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="password" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[api_token]"
								value="<?php echo esc_attr( $opts['api_token'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Group IDs', 'uve-mailrelay-newsletter' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[group_ids]"
								value="<?php echo esc_attr( $opts['group_ids'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Modo suscripción', 'uve-mailrelay-newsletter' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[subscriber_status]">
								<option value="inactive" <?php selected( $opts['subscriber_status'], 'inactive' ); ?>><?php echo esc_html__( 'Double opt-in (inactive + confirmación)', 'uve-mailrelay-newsletter' ); ?></option>
								<option value="active" <?php selected( $opts['subscriber_status'], 'active' ); ?>><?php echo esc_html__( 'Single opt-in (active)', 'uve-mailrelay-newsletter' ); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php echo esc_html__( 'Turnstile Site Key', 'uve-mailrelay-newsletter' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[turnstile_site_key]"
								value="<?php echo esc_attr( $opts['turnstile_site_key'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Turnstile Secret Key', 'uve-mailrelay-newsletter' ); ?></th>
						<td>
							<input type="password" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[turnstile_secret_key]"
								value="<?php echo esc_attr( $opts['turnstile_secret_key'] ); ?>" />
						</td>
					</tr>

					<tr>
						<th scope="row"><?php echo esc_html__( 'Textos', 'uve-mailrelay-newsletter' ); ?></th>
						<td>
							<p><label><?php echo esc_html__( 'Título', 'uve-mailrelay-newsletter' ); ?><br>
									<input type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[title]" value="<?php echo esc_attr( $opts['title'] ); ?>">
								</label></p>
							<p><label><?php echo esc_html__( 'Descripción', 'uve-mailrelay-newsletter' ); ?><br>
									<input type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[description]" value="<?php echo esc_attr( $opts['description'] ); ?>">
								</label></p>
							<p><label><?php echo esc_html__( 'Placeholder email', 'uve-mailrelay-newsletter' ); ?><br>
									<input type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[email_placeholder]" value="<?php echo esc_attr( $opts['email_placeholder'] ); ?>">
								</label></p>
							<p><label><?php echo esc_html__( 'Texto botón', 'uve-mailrelay-newsletter' ); ?><br>
									<input type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[submit_label]" value="<?php echo esc_attr( $opts['submit_label'] ); ?>">
								</label></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php echo esc_html__( 'RGPD + Logs', 'uve-mailrelay-newsletter' ); ?></th>
						<td>
							<p><label><?php echo esc_html__( 'URL política de privacidad', 'uve-mailrelay-newsletter' ); ?><br>
									<input type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[privacy_url]"
										value="<?php echo esc_attr( $opts['privacy_url'] ); ?>">
								</label></p>
							<p><label><?php echo esc_html__( 'Texto del checkbox', 'uve-mailrelay-newsletter' ); ?><br>
									<input type="text" class="regular-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[consent_label]"
										value="<?php echo esc_attr( $opts['consent_label'] ); ?>">
								</label></p>
							<p>
								<label><input type="checkbox" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[store_consent_log]" value="1" <?php checked( $opts['store_consent_log'], '1' ); ?>>
									<?php echo esc_html__( 'Guardar log en BD', 'uve-mailrelay-newsletter' ); ?></label><br>
								<label><input type="checkbox" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[hash_ip]" value="1" <?php checked( $opts['hash_ip'], '1' ); ?>>
									<?php echo esc_html__( 'Guardar IP como hash', 'uve-mailrelay-newsletter' ); ?></label><br>
								<label><?php echo esc_html__( 'Retención (días)', 'uve-mailrelay-newsletter' ); ?><br>
									<input type="number" min="1" class="small-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[retention_days]"
										value="<?php echo esc_attr( (string) $opts['retention_days'] ); ?>">
								</label>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php echo esc_html__( 'Rate limit', 'uve-mailrelay-newsletter' ); ?></th>
						<td>
							<p><label><?php echo esc_html__( 'Máx intentos por IP+email', 'uve-mailrelay-newsletter' ); ?><br>
									<input type="number" min="1" class="small-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[rate_limit_max]"
										value="<?php echo esc_attr( (string) $opts['rate_limit_max'] ); ?>">
								</label></p>
							<p><label><?php echo esc_html__( 'Ventana (segundos)', 'uve-mailrelay-newsletter' ); ?><br>
									<input type="number" min="60" class="small-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[rate_limit_window_seconds]"
										value="<?php echo esc_attr( (string) $opts['rate_limit_window_seconds'] ); ?>">
								</label></p>

							<hr>
							<p><label><?php echo esc_html__( 'Máx reenvíos confirmación por IP+email (0 = desactivar)', 'uve-mailrelay-newsletter' ); ?><br>
									<input type="number" min="0" class="small-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[confirm_resend_max]"
										value="<?php echo esc_attr( (string) $opts['confirm_resend_max'] ); ?>">
								</label></p>
							<p><label><?php echo esc_html__( 'Ventana confirmación (segundos)', 'uve-mailrelay-newsletter' ); ?><br>
									<input type="number" min="60" class="small-text" name="<?php echo esc_attr( UVE_Mailrelay_Newsletter::OPT_KEY ); ?>[confirm_resend_window_seconds]"
										value="<?php echo esc_attr( (string) $opts['confirm_resend_window_seconds'] ); ?>">
								</label></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr>
			<h2><?php echo esc_html__( 'Shortcode', 'uve-mailrelay-newsletter' ); ?></h2>
			<p><code>[uve_mailrelay_newsletter]</code></p>

			<hr>
			<h2><?php echo esc_html__( 'Logs recientes', 'uve-mailrelay-newsletter' ); ?></h2>
			<?php UVE_MR_Logs::render_logs_table_safe(); ?>

			<form method="post" style="margin-top:12px;">
				<?php wp_nonce_field( 'uve_mr_purge_now' ); ?>
				<button type="submit" class="button" name="uve_mr_purge_now" value="1"><?php echo esc_html__( 'Purgar ahora', 'uve-mailrelay-newsletter' ); ?></button>
			</form>
		</div>
		<?php
	}
}
