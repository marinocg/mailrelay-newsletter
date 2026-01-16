<?php
/**
 * Forms editor page.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Forms editor page.
 */
final class RelayPress_Forms_Editor_Page {
	/**
	 * Render form editor.
	 *
	 * @param int|null $form_id Form ID.
	 * @return void
	 */
	public static function render( ?int $form_id = null ): void {
		$form_id  = null === $form_id ? absint( wp_unslash( $_GET['form_id'] ?? 0 ) ) : $form_id; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form     = $form_id ? RelayPress_Form_Use_Cases::get_form( $form_id ) : null;
		$opts     = RelayPress_Newsletter::get_options();
		$defaults = RelayPress_Form_Config::defaults( $opts );

		if ( $form ) {
			$config = $form->merge_config( $defaults );
			$name   = $form->name;
			$status = $form->status;
		} else {
			$config = $defaults;
			$name   = '';
			$status = 'publish';
		}
		$destination   = $config['destination'] ?? array();
		$locale_mode   = (string) ( $destination['locale_mode'] ?? 'inherit' );
		$locale_value  = (string) ( $destination['locale'] ?? '' );
		$locale_labels = RelayPress_Utils::locale_labels();
		if ( '' === $locale_value ) {
			$global_force = RelayPress_Utils::normalize_locale( (string) ( $opts['locale_force'] ?? '' ) );
			$locale_value = '' !== $global_force ? $global_force : RelayPress_Utils::default_locale_fallback();
		}

		$back_url = add_query_arg( 'page', 'relaypress-newsletter-forms', admin_url( 'admin.php' ) );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $form ? __( 'Edit Form', 'relaypress-newsletter' ) : __( 'Add New Form', 'relaypress-newsletter' ) ); ?></h1>
			<?php
			$notice = isset( $_GET['notice'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status = isset( $_GET['relaypress_status'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['relaypress_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' === $notice ) {
				$notice = $status;
			}
			if ( 'saved' === $notice ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Form saved successfully.', 'relaypress-newsletter' ); ?></p>
				</div>
			<?php elseif ( 'error' === $notice ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html__( 'Something went wrong while saving the form.', 'relaypress-newsletter' ); ?></p>
				</div>
			<?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( 'relaypress_form_save' ); ?>
				<input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
				<div class="relaypress-form-grid">
					<div class="relaypress-field-row">
						<label class="relaypress-field-label" for="relaypress-form-name"><?php echo esc_html__( 'Name', 'relaypress-newsletter' ); ?></label>
						<div class="relaypress-field-control">
							<input id="relaypress-form-name" type="text" class="regular-text" name="form_name" value="<?php echo esc_attr( $name ); ?>" required>
						</div>
					</div>
					<div class="relaypress-field-row">
						<span class="relaypress-field-label"><?php echo esc_html__( 'Shortcode', 'relaypress-newsletter' ); ?></span>
						<div class="relaypress-field-control">
							<?php if ( $form_id ) : ?>
								<code>[relaypress_newsletter id="<?php echo esc_attr( (string) $form_id ); ?>"]</code>
								<p class="description"><?php echo esc_html__( 'Paste this shortcode in posts, pages, or widgets.', 'relaypress-newsletter' ); ?></p>
							<?php else : ?>
								<code>[relaypress_newsletter id="…"]</code>
								<p class="description"><?php echo esc_html__( 'Save the form to generate the shortcode.', 'relaypress-newsletter' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<h2 class="nav-tab-wrapper relaypress-tabs">
					<a href="#relaypress-tab-fields" class="nav-tab nav-tab-active"><?php echo esc_html__( 'Fields', 'relaypress-newsletter' ); ?></a>
					<a href="#relaypress-tab-messages" class="nav-tab"><?php echo esc_html__( 'Messages', 'relaypress-newsletter' ); ?></a>
					<a href="#relaypress-tab-settings" class="nav-tab"><?php echo esc_html__( 'Settings', 'relaypress-newsletter' ); ?></a>
				</h2>

				<div id="relaypress-tab-fields" class="relaypress-tab-panel is-active">
					<div class="relaypress-tab-grid">
						<div class="relaypress-tab-main">
							<div class="relaypress-panel">
								<h3><?php echo esc_html__( 'Destination', 'relaypress-newsletter' ); ?></h3>
								<div class="relaypress-form-grid">
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-groups-available"><?php echo esc_html__( 'Groups', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
							<?php
							$group_ids   = RelayPress_Utils::parse_group_ids( (string) ( $config['destination']['group_ids'] ?? '' ) );
							$groups      = RelayPress_Container::mailrelay_client()->get_groups();
							$refresh_url = wp_nonce_url(
								add_query_arg(
									array(
										'page'    => 'relaypress-newsletter-forms',
										'action'  => $form_id ? 'edit' : 'new',
										'form_id' => $form_id,
										'relaypress_refresh_groups' => 1,
									),
									admin_url( 'admin.php' )
								),
								'relaypress_refresh_groups'
							);
							?>
							<input type="hidden" name="form_config[destination][group_ids]" id="relaypress-group-ids" value="<?php echo esc_attr( implode( ',', $group_ids ) ); ?>">
							<div class="relaypress-dual-list">
								<div>
									<label class="screen-reader-text" for="relaypress-groups-available"><?php echo esc_html__( 'Available groups', 'relaypress-newsletter' ); ?></label>
									<select id="relaypress-groups-available" multiple size="8">
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
									<button type="button" class="button" id="relaypress-group-add"><?php echo esc_html__( 'Add →', 'relaypress-newsletter' ); ?></button>
									<button type="button" class="button" id="relaypress-group-remove"><?php echo esc_html__( '← Remove', 'relaypress-newsletter' ); ?></button>
								</div>
								<div>
									<label class="screen-reader-text" for="relaypress-groups-selected"><?php echo esc_html__( 'Selected groups', 'relaypress-newsletter' ); ?></label>
									<select id="relaypress-groups-selected" multiple size="8">
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
								<a href="<?php echo esc_url( $refresh_url ); ?>"><?php echo esc_html__( 'Refresh groups', 'relaypress-newsletter' ); ?></a>
							</p>
							<?php if ( empty( $groups ) ) : ?>
								<p class="description"><?php echo esc_html__( 'No groups found or API not configured.', 'relaypress-newsletter' ); ?></p>
							<?php endif; ?>
										</div>
									</div>
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-subscriber-status"><?php echo esc_html__( 'Subscriber status', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<select id="relaypress-subscriber-status" name="form_config[destination][subscriber_status]">
												<option value="inactive" <?php selected( $config['destination']['subscriber_status'], 'inactive' ); ?>><?php echo esc_html__( 'Inactive (double opt-in)', 'relaypress-newsletter' ); ?></option>
												<option value="active" <?php selected( $config['destination']['subscriber_status'], 'active' ); ?>><?php echo esc_html__( 'Active (single opt-in)', 'relaypress-newsletter' ); ?></option>
											</select>
										</div>
									</div>
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-locale-mode"><?php echo esc_html__( 'Locale', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<select id="relaypress-locale-mode" name="form_config[destination][locale_mode]">
												<option value="inherit" <?php selected( $locale_mode, 'inherit' ); ?>><?php echo esc_html__( 'Use global setting', 'relaypress-newsletter' ); ?></option>
												<option value="browser" <?php selected( $locale_mode, 'browser' ); ?>><?php echo esc_html__( 'Use browser language', 'relaypress-newsletter' ); ?></option>
												<option value="force" <?php selected( $locale_mode, 'force' ); ?>><?php echo esc_html__( 'Force a specific language', 'relaypress-newsletter' ); ?></option>
											</select>
											<p class="description"><?php echo esc_html__( 'If the browser language is unsupported, the global fallback is used.', 'relaypress-newsletter' ); ?></p>
										</div>
									</div>
									<div class="relaypress-field-row relaypress-locale-force-row">
										<label class="relaypress-field-label" for="relaypress-locale-value"><?php echo esc_html__( 'Forced language', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<select id="relaypress-locale-value" name="form_config[destination][locale]">
												<?php foreach ( $locale_labels as $value => $label ) : ?>
													<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $locale_value, $value ); ?>>
														<?php echo esc_html( $label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<p class="description"><?php echo esc_html__( 'Used only when forcing a language for this form.', 'relaypress-newsletter' ); ?></p>
										</div>
									</div>
								</div>
							</div>

							<div class="relaypress-panel">
								<h3><?php echo esc_html__( 'Subscriber fields', 'relaypress-newsletter' ); ?></h3>
								<div class="relaypress-table-wrap">
					<table class="widefat striped relaypress-fields-table">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Field', 'relaypress-newsletter' ); ?></th>
								<th><?php echo esc_html__( 'Enable', 'relaypress-newsletter' ); ?></th>
								<th><?php echo esc_html__( 'Required', 'relaypress-newsletter' ); ?></th>
								<th><?php echo esc_html__( 'Label', 'relaypress-newsletter' ); ?></th>
								<th><?php echo esc_html__( 'Placeholder', 'relaypress-newsletter' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$fields = $config['fields'] ?? array();
							foreach ( $fields as $key => $field ) :
								if ( ! is_array( $field ) ) {
									continue;
								}
								$enabled     = ! empty( $field['enabled'] );
								$required    = ! empty( $field['required'] );
								$label       = $field['label'] ?? '';
								$placeholder = $field['placeholder'] ?? '';
								$disabled    = ( 'email' === $key );
								?>
								<tr>
									<td><strong><?php echo esc_html( ucfirst( (string) $key ) ); ?></strong></td>
									<td>
										<input type="checkbox" name="form_config[fields][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $enabled ); ?> <?php disabled( $disabled ); ?>>
										<?php if ( $disabled ) : ?>
											<input type="hidden" name="form_config[fields][<?php echo esc_attr( $key ); ?>][enabled]" value="1">
										<?php endif; ?>
									</td>
									<td>
										<input type="checkbox" name="form_config[fields][<?php echo esc_attr( $key ); ?>][required]" value="1" <?php checked( $required ); ?> <?php disabled( $disabled ); ?>>
										<?php if ( $disabled ) : ?>
											<input type="hidden" name="form_config[fields][<?php echo esc_attr( $key ); ?>][required]" value="1">
										<?php endif; ?>
									</td>
									<td>
										<input type="text" class="regular-text" name="form_config[fields][<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $label ); ?>">
										<?php if ( $disabled ) : ?>
											<p class="description"><?php echo esc_html__( 'Email is required by Mailrelay.', 'relaypress-newsletter' ); ?></p>
										<?php endif; ?>
									</td>
									<td>
										<input type="text" class="regular-text" name="form_config[fields][<?php echo esc_attr( $key ); ?>][placeholder]" value="<?php echo esc_attr( $placeholder ); ?>">
										<?php if ( 'phone' === $key ) : ?>
											<p class="description"><?php echo esc_html__( 'Accepts E.164 (e.g., +34666666666) or local numbers with a default country. Sent to both SMS and WhatsApp in Mailrelay.', 'relaypress-newsletter' ); ?></p>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
								</div>
							</div>
						</div>
						<div class="relaypress-tab-help">
							<h4><?php echo esc_html__( 'Quick guide', 'relaypress-newsletter' ); ?></h4>
							<ul class="relaypress-help-list">
								<li><?php echo esc_html__( 'Pick one or more groups so subscribers land in the right lists.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Keep fields minimal to reduce drop-offs. Email is required by Mailrelay.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Use E.164 for Phone (for example, +34666666666) to avoid API errors.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Phone populates both SMS and WhatsApp fields in Mailrelay.', 'relaypress-newsletter' ); ?></li>
							</ul>
						</div>
					</div>
				</div>

				<div id="relaypress-tab-messages" class="relaypress-tab-panel">
					<div class="relaypress-tab-grid">
						<div class="relaypress-tab-main">
							<div class="relaypress-panel">
								<div class="relaypress-form-grid">
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-title"><?php echo esc_html__( 'Title', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<input id="relaypress-title" type="text" class="regular-text" name="form_config[basics][title]" value="<?php echo esc_attr( $config['basics']['title'] ); ?>">
										</div>
									</div>
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-description"><?php echo esc_html__( 'Description', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<input id="relaypress-description" type="text" class="regular-text" name="form_config[basics][description]" value="<?php echo esc_attr( $config['basics']['description'] ); ?>">
										</div>
									</div>
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-submit-label"><?php echo esc_html__( 'Submit button text', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<input id="relaypress-submit-label" type="text" class="regular-text" name="form_config[basics][submit_label]" value="<?php echo esc_attr( $config['basics']['submit_label'] ); ?>">
										</div>
									</div>
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-success"><?php echo esc_html__( 'Success message', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<input id="relaypress-success" type="text" class="regular-text" name="form_config[messages][success]" value="<?php echo esc_attr( $config['messages']['success'] ); ?>">
										</div>
									</div>
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-captcha"><?php echo esc_html__( 'Captcha error', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<input id="relaypress-captcha" type="text" class="regular-text" name="form_config[messages][captcha]" value="<?php echo esc_attr( $config['messages']['captcha'] ); ?>">
										</div>
									</div>
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-consent-error"><?php echo esc_html__( 'Consent error', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<input id="relaypress-consent-error" type="text" class="regular-text" name="form_config[messages][consent]" value="<?php echo esc_attr( $config['messages']['consent'] ); ?>">
										</div>
									</div>
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-phone-error"><?php echo esc_html__( 'Phone error', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<input id="relaypress-phone-error" type="text" class="regular-text" name="form_config[messages][phone]" value="<?php echo esc_attr( $config['messages']['phone'] ); ?>">
										</div>
									</div>
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-generic-error"><?php echo esc_html__( 'Generic error', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<input id="relaypress-generic-error" type="text" class="regular-text" name="form_config[messages][error]" value="<?php echo esc_attr( $config['messages']['error'] ); ?>">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="relaypress-tab-help">
							<h4><?php echo esc_html__( 'Tone and clarity', 'relaypress-newsletter' ); ?></h4>
							<ul class="relaypress-help-list">
								<li><?php echo esc_html__( 'Keep messages short; they appear inline under the form.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Avoid technical wording. Tell users what to do next.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Match your site voice (formal, friendly, or direct).', 'relaypress-newsletter' ); ?></li>
							</ul>
						</div>
					</div>
				</div>

				<div id="relaypress-tab-settings" class="relaypress-tab-panel">
					<div class="relaypress-tab-grid">
						<div class="relaypress-tab-main">
							<div class="relaypress-panel">
								<h3><?php echo esc_html__( 'Status', 'relaypress-newsletter' ); ?></h3>
								<div class="relaypress-form-grid">
									<div class="relaypress-field-row">
										<label class="relaypress-field-label" for="relaypress-form-status"><?php echo esc_html__( 'Visibility', 'relaypress-newsletter' ); ?></label>
										<div class="relaypress-field-control">
											<select id="relaypress-form-status" name="form_status">
												<option value="publish" <?php selected( $status, 'publish' ); ?>>
													<?php echo esc_html__( 'Published', 'relaypress-newsletter' ); ?>
												</option>
												<option value="draft" <?php selected( $status, 'draft' ); ?>>
													<?php echo esc_html__( 'Draft', 'relaypress-newsletter' ); ?>
												</option>
											</select>
										</div>
									</div>
								</div>
							</div>

							<div class="relaypress-panel">
					<h3><?php echo esc_html__( 'Consent', 'relaypress-newsletter' ); ?></h3>
					<div class="relaypress-form-grid">
						<div class="relaypress-field-row">
							<span class="relaypress-field-label"><?php echo esc_html__( 'Use default consent settings', 'relaypress-newsletter' ); ?></span>
							<div class="relaypress-field-control">
								<label><input type="checkbox" name="form_config[consent][inherit]" value="1" <?php checked( $config['consent']['inherit'] ); ?>> <?php echo esc_html__( 'Inherit global consent text and URL', 'relaypress-newsletter' ); ?></label>
							</div>
						</div>
						<div class="relaypress-field-row relaypress-consent-fields">
							<label class="relaypress-field-label" for="relaypress-consent-label"><?php echo esc_html__( 'Consent label', 'relaypress-newsletter' ); ?></label>
							<div class="relaypress-field-control">
								<input id="relaypress-consent-label" type="text" class="regular-text" name="form_config[consent][label]" value="<?php echo esc_attr( $config['consent']['label'] ); ?>">
							</div>
						</div>
						<div class="relaypress-field-row relaypress-consent-fields">
							<label class="relaypress-field-label" for="relaypress-privacy-url"><?php echo esc_html__( 'Privacy URL', 'relaypress-newsletter' ); ?></label>
							<div class="relaypress-field-control">
								<input id="relaypress-privacy-url" type="text" class="regular-text" name="form_config[consent][privacy_url]" value="<?php echo esc_attr( $config['consent']['privacy_url'] ); ?>">
							</div>
						</div>
					</div>
							</div>

							<div class="relaypress-panel">
					<h3><?php echo esc_html__( 'Spam protection', 'relaypress-newsletter' ); ?></h3>
					<div class="relaypress-form-grid">
						<div class="relaypress-field-row">
							<label class="relaypress-field-label" for="relaypress-turnstile-mode"><?php echo esc_html__( 'Turnstile', 'relaypress-newsletter' ); ?></label>
							<div class="relaypress-field-control">
								<select id="relaypress-turnstile-mode" name="form_config[turnstile][mode]" class="relaypress-turnstile-mode">
									<option value="inherit" <?php selected( $config['turnstile']['mode'] ?? 'inherit', 'inherit' ); ?>>
										<?php echo esc_html__( 'Inherit global setting', 'relaypress-newsletter' ); ?>
									</option>
									<option value="on" <?php selected( $config['turnstile']['mode'] ?? 'inherit', 'on' ); ?>>
										<?php echo esc_html__( 'Enabled', 'relaypress-newsletter' ); ?>
									</option>
									<option value="off" <?php selected( $config['turnstile']['mode'] ?? 'inherit', 'off' ); ?>>
										<?php echo esc_html__( 'Disabled', 'relaypress-newsletter' ); ?>
									</option>
								</select>
								<p class="description"><?php echo esc_html__( 'Choose how this form handles spam protection.', 'relaypress-newsletter' ); ?></p>
							</div>
						</div>
					</div>
							</div>

							<div class="relaypress-panel">
					<h3><?php echo esc_html__( 'Advanced', 'relaypress-newsletter' ); ?></h3>
					<div class="relaypress-form-grid">
						<div class="relaypress-field-row">
							<span class="relaypress-field-label"><?php echo esc_html__( 'Use default rate limit', 'relaypress-newsletter' ); ?></span>
							<div class="relaypress-field-control">
								<label><input type="checkbox" name="form_config[rate_limit][inherit]" value="1" <?php checked( $config['rate_limit']['inherit'] ); ?>> <?php echo esc_html__( 'Inherit global rate limits', 'relaypress-newsletter' ); ?></label>
							</div>
						</div>
						<div class="relaypress-field-row relaypress-rate-limit-fields">
							<label class="relaypress-field-label" for="relaypress-rate-limit-max"><?php echo esc_html__( 'Max attempts', 'relaypress-newsletter' ); ?></label>
							<div class="relaypress-field-control">
								<input id="relaypress-rate-limit-max" type="number" min="1" class="small-text" name="form_config[rate_limit][max]" value="<?php echo esc_attr( (string) $config['rate_limit']['max'] ); ?>">
							</div>
						</div>
						<div class="relaypress-field-row relaypress-rate-limit-fields">
							<label class="relaypress-field-label" for="relaypress-rate-limit-window"><?php echo esc_html__( 'Window (seconds)', 'relaypress-newsletter' ); ?></label>
							<div class="relaypress-field-control">
								<input id="relaypress-rate-limit-window" type="number" min="60" class="small-text" name="form_config[rate_limit][window_seconds]" value="<?php echo esc_attr( (string) $config['rate_limit']['window_seconds'] ); ?>">
							</div>
						</div>
						<div class="relaypress-field-row">
							<span class="relaypress-field-label"><?php echo esc_html__( 'Enable AJAX', 'relaypress-newsletter' ); ?></span>
							<div class="relaypress-field-control">
								<label><input type="checkbox" name="form_config[ajax]" value="1" <?php checked( $config['ajax'], '1' ); ?>> <?php echo esc_html__( 'Submit via AJAX', 'relaypress-newsletter' ); ?></label>
							</div>
						</div>
					</div>
							</div>
						</div>
						<div class="relaypress-tab-help">
							<h4><?php echo esc_html__( 'Defaults and overrides', 'relaypress-newsletter' ); ?></h4>
							<ul class="relaypress-help-list">
								<li><?php echo esc_html__( 'Inherited settings use the global defaults from the main plugin settings page.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Override only when this form needs different behavior.', 'relaypress-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Turning off Turnstile disables it for this form only.', 'relaypress-newsletter' ); ?></li>
							</ul>
						</div>
					</div>
				</div>

				<div class="relaypress-sticky-save">
					<button type="submit" class="button button-primary" name="relaypress_form_save" value="1"><?php echo esc_html__( 'Save Form', 'relaypress-newsletter' ); ?></button>
					<a href="<?php echo esc_url( $back_url ); ?>" class="button"><?php echo esc_html__( 'Back to list', 'relaypress-newsletter' ); ?></a>
				</div>
			</form>
		</div>

		<?php
	}
}
