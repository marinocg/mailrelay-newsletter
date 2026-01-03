<?php
/**
 * Forms editor page.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Forms editor page.
 */
final class UVE_MR_Forms_Editor_Page {
	/**
	 * Render form editor.
	 *
	 * @param int|null $form_id Form ID.
	 * @return void
	 */
	public static function render( ?int $form_id = null ): void {
		$form_id  = null === $form_id ? absint( wp_unslash( $_GET['form_id'] ?? 0 ) ) : $form_id; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form     = $form_id ? UVE_MR_Form_Use_Cases::get_form( $form_id ) : null;
		$opts     = UVE_Mailrelay_Newsletter::get_options();
		$defaults = UVE_MR_Form_Config::defaults( $opts );

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
		$locale_labels = UVE_MR_Utils::locale_labels();
		if ( '' === $locale_value ) {
			$global_force = UVE_MR_Utils::normalize_locale( (string) ( $opts['locale_force'] ?? '' ) );
			$locale_value = '' !== $global_force ? $global_force : UVE_MR_Utils::default_locale_fallback();
		}

		$back_url = add_query_arg( 'page', 'uve-mr-newsletter-forms', admin_url( 'admin.php' ) );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $form ? __( 'Edit Form', 'uve-mailrelay-newsletter' ) : __( 'Add New Form', 'uve-mailrelay-newsletter' ) ); ?></h1>
			<?php
			$status = isset( $_GET['uve_mr_status'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['uve_mr_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'saved' === $status ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Form saved successfully.', 'uve-mailrelay-newsletter' ); ?></p>
				</div>
			<?php elseif ( 'error' === $status ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html__( 'Something went wrong while saving the form.', 'uve-mailrelay-newsletter' ); ?></p>
				</div>
			<?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( 'uve_mr_form_save' ); ?>
				<input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
				<div class="uve-mr-form-grid">
					<div class="uve-mr-field-row">
						<label class="uve-mr-field-label" for="uve-mr-form-name"><?php echo esc_html__( 'Name', 'uve-mailrelay-newsletter' ); ?></label>
						<div class="uve-mr-field-control">
							<input id="uve-mr-form-name" type="text" class="regular-text" name="form_name" value="<?php echo esc_attr( $name ); ?>" required>
						</div>
					</div>
					<div class="uve-mr-field-row">
						<span class="uve-mr-field-label"><?php echo esc_html__( 'Shortcode', 'uve-mailrelay-newsletter' ); ?></span>
						<div class="uve-mr-field-control">
							<?php if ( $form_id ) : ?>
								<code>[uve_mailrelay_newsletter id="<?php echo esc_attr( (string) $form_id ); ?>"]</code>
								<p class="description"><?php echo esc_html__( 'Paste this shortcode in posts, pages, or widgets.', 'uve-mailrelay-newsletter' ); ?></p>
							<?php else : ?>
								<code>[uve_mailrelay_newsletter id="…"]</code>
								<p class="description"><?php echo esc_html__( 'Save the form to generate the shortcode.', 'uve-mailrelay-newsletter' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<h2 class="nav-tab-wrapper uve-mr-tabs">
					<a href="#uve-mr-tab-fields" class="nav-tab nav-tab-active"><?php echo esc_html__( 'Fields', 'uve-mailrelay-newsletter' ); ?></a>
					<a href="#uve-mr-tab-messages" class="nav-tab"><?php echo esc_html__( 'Messages', 'uve-mailrelay-newsletter' ); ?></a>
					<a href="#uve-mr-tab-settings" class="nav-tab"><?php echo esc_html__( 'Settings', 'uve-mailrelay-newsletter' ); ?></a>
				</h2>

				<div id="uve-mr-tab-fields" class="uve-mr-tab-panel is-active">
					<div class="uve-mr-tab-grid">
						<div class="uve-mr-tab-main">
							<div class="uve-mr-panel">
								<h3><?php echo esc_html__( 'Destination', 'uve-mailrelay-newsletter' ); ?></h3>
								<div class="uve-mr-form-grid">
									<div class="uve-mr-field-row">
										<label class="uve-mr-field-label" for="uve-mr-groups-available"><?php echo esc_html__( 'Groups', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
							<?php
							$group_ids   = UVE_MR_Utils::parse_group_ids( (string) ( $config['destination']['group_ids'] ?? '' ) );
							$groups      = UVE_MR_Container::mailrelay_client()->get_groups();
							$refresh_url = wp_nonce_url(
								add_query_arg(
									array(
										'page'    => 'uve-mr-newsletter-forms',
										'action'  => $form_id ? 'edit' : 'new',
										'form_id' => $form_id,
										'uve_mr_refresh_groups' => 1,
									),
									admin_url( 'admin.php' )
								),
								'uve_mr_refresh_groups'
							);
							?>
							<input type="hidden" name="form_config[destination][group_ids]" id="uve-mr-group-ids" value="<?php echo esc_attr( implode( ',', $group_ids ) ); ?>">
							<div class="uve-mr-dual-list">
								<div>
									<label class="screen-reader-text" for="uve-mr-groups-available"><?php echo esc_html__( 'Available groups', 'uve-mailrelay-newsletter' ); ?></label>
									<select id="uve-mr-groups-available" multiple size="8">
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
									<button type="button" class="button" id="uve-mr-group-add"><?php echo esc_html__( 'Add →', 'uve-mailrelay-newsletter' ); ?></button>
									<button type="button" class="button" id="uve-mr-group-remove"><?php echo esc_html__( '← Remove', 'uve-mailrelay-newsletter' ); ?></button>
								</div>
								<div>
									<label class="screen-reader-text" for="uve-mr-groups-selected"><?php echo esc_html__( 'Selected groups', 'uve-mailrelay-newsletter' ); ?></label>
									<select id="uve-mr-groups-selected" multiple size="8">
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
								<a href="<?php echo esc_url( $refresh_url ); ?>"><?php echo esc_html__( 'Refresh groups', 'uve-mailrelay-newsletter' ); ?></a>
							</p>
							<?php if ( empty( $groups ) ) : ?>
								<p class="description"><?php echo esc_html__( 'No groups found or API not configured.', 'uve-mailrelay-newsletter' ); ?></p>
							<?php endif; ?>
										</div>
									</div>
									<div class="uve-mr-field-row">
										<label class="uve-mr-field-label" for="uve-mr-subscriber-status"><?php echo esc_html__( 'Subscriber status', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
											<select id="uve-mr-subscriber-status" name="form_config[destination][subscriber_status]">
												<option value="inactive" <?php selected( $config['destination']['subscriber_status'], 'inactive' ); ?>><?php echo esc_html__( 'Inactive (double opt-in)', 'uve-mailrelay-newsletter' ); ?></option>
												<option value="active" <?php selected( $config['destination']['subscriber_status'], 'active' ); ?>><?php echo esc_html__( 'Active (single opt-in)', 'uve-mailrelay-newsletter' ); ?></option>
											</select>
										</div>
									</div>
									<div class="uve-mr-field-row">
										<label class="uve-mr-field-label" for="uve-mr-locale-mode"><?php echo esc_html__( 'Locale', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
											<select id="uve-mr-locale-mode" name="form_config[destination][locale_mode]">
												<option value="inherit" <?php selected( $locale_mode, 'inherit' ); ?>><?php echo esc_html__( 'Use global setting', 'uve-mailrelay-newsletter' ); ?></option>
												<option value="browser" <?php selected( $locale_mode, 'browser' ); ?>><?php echo esc_html__( 'Use browser language', 'uve-mailrelay-newsletter' ); ?></option>
												<option value="force" <?php selected( $locale_mode, 'force' ); ?>><?php echo esc_html__( 'Force a specific language', 'uve-mailrelay-newsletter' ); ?></option>
											</select>
											<p class="description"><?php echo esc_html__( 'If the browser language is unsupported, the global fallback is used.', 'uve-mailrelay-newsletter' ); ?></p>
										</div>
									</div>
									<div class="uve-mr-field-row uve-mr-locale-force-row">
										<label class="uve-mr-field-label" for="uve-mr-locale-value"><?php echo esc_html__( 'Forced language', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
											<select id="uve-mr-locale-value" name="form_config[destination][locale]">
												<?php foreach ( $locale_labels as $value => $label ) : ?>
													<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $locale_value, $value ); ?>>
														<?php echo esc_html( $label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<p class="description"><?php echo esc_html__( 'Used only when forcing a language for this form.', 'uve-mailrelay-newsletter' ); ?></p>
										</div>
									</div>
								</div>
							</div>

							<div class="uve-mr-panel">
								<h3><?php echo esc_html__( 'Subscriber fields', 'uve-mailrelay-newsletter' ); ?></h3>
								<div class="uve-mr-table-wrap">
					<table class="widefat striped uve-mr-fields-table">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Field', 'uve-mailrelay-newsletter' ); ?></th>
								<th><?php echo esc_html__( 'Enable', 'uve-mailrelay-newsletter' ); ?></th>
								<th><?php echo esc_html__( 'Required', 'uve-mailrelay-newsletter' ); ?></th>
								<th><?php echo esc_html__( 'Label', 'uve-mailrelay-newsletter' ); ?></th>
								<th><?php echo esc_html__( 'Placeholder', 'uve-mailrelay-newsletter' ); ?></th>
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
											<p class="description"><?php echo esc_html__( 'Email is required by Mailrelay.', 'uve-mailrelay-newsletter' ); ?></p>
										<?php endif; ?>
									</td>
									<td>
										<input type="text" class="regular-text" name="form_config[fields][<?php echo esc_attr( $key ); ?>][placeholder]" value="<?php echo esc_attr( $placeholder ); ?>">
										<?php if ( 'phone' === $key ) : ?>
											<p class="description"><?php echo esc_html__( 'Use E.164 format (e.g., +34666666666). Sent to both SMS and WhatsApp in Mailrelay.', 'uve-mailrelay-newsletter' ); ?></p>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
								</div>
							</div>
						</div>
						<div class="uve-mr-tab-help">
							<h4><?php echo esc_html__( 'Quick guide', 'uve-mailrelay-newsletter' ); ?></h4>
							<ul class="uve-mr-help-list">
								<li><?php echo esc_html__( 'Pick one or more groups so subscribers land in the right lists.', 'uve-mailrelay-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Keep fields minimal to reduce drop-offs. Email is required by Mailrelay.', 'uve-mailrelay-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Use E.164 for Phone (for example, +34666666666) to avoid API errors.', 'uve-mailrelay-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Phone populates both SMS and WhatsApp fields in Mailrelay.', 'uve-mailrelay-newsletter' ); ?></li>
							</ul>
						</div>
					</div>
				</div>

				<div id="uve-mr-tab-messages" class="uve-mr-tab-panel">
					<div class="uve-mr-tab-grid">
						<div class="uve-mr-tab-main">
							<div class="uve-mr-panel">
								<div class="uve-mr-form-grid">
									<div class="uve-mr-field-row">
										<label class="uve-mr-field-label" for="uve-mr-title"><?php echo esc_html__( 'Title', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
											<input id="uve-mr-title" type="text" class="regular-text" name="form_config[basics][title]" value="<?php echo esc_attr( $config['basics']['title'] ); ?>">
										</div>
									</div>
									<div class="uve-mr-field-row">
										<label class="uve-mr-field-label" for="uve-mr-description"><?php echo esc_html__( 'Description', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
											<input id="uve-mr-description" type="text" class="regular-text" name="form_config[basics][description]" value="<?php echo esc_attr( $config['basics']['description'] ); ?>">
										</div>
									</div>
									<div class="uve-mr-field-row">
										<label class="uve-mr-field-label" for="uve-mr-submit-label"><?php echo esc_html__( 'Submit button text', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
											<input id="uve-mr-submit-label" type="text" class="regular-text" name="form_config[basics][submit_label]" value="<?php echo esc_attr( $config['basics']['submit_label'] ); ?>">
										</div>
									</div>
									<div class="uve-mr-field-row">
										<label class="uve-mr-field-label" for="uve-mr-success"><?php echo esc_html__( 'Success message', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
											<input id="uve-mr-success" type="text" class="regular-text" name="form_config[messages][success]" value="<?php echo esc_attr( $config['messages']['success'] ); ?>">
										</div>
									</div>
									<div class="uve-mr-field-row">
										<label class="uve-mr-field-label" for="uve-mr-captcha"><?php echo esc_html__( 'Captcha error', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
											<input id="uve-mr-captcha" type="text" class="regular-text" name="form_config[messages][captcha]" value="<?php echo esc_attr( $config['messages']['captcha'] ); ?>">
										</div>
									</div>
									<div class="uve-mr-field-row">
										<label class="uve-mr-field-label" for="uve-mr-consent-error"><?php echo esc_html__( 'Consent error', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
											<input id="uve-mr-consent-error" type="text" class="regular-text" name="form_config[messages][consent]" value="<?php echo esc_attr( $config['messages']['consent'] ); ?>">
										</div>
									</div>
									<div class="uve-mr-field-row">
										<label class="uve-mr-field-label" for="uve-mr-generic-error"><?php echo esc_html__( 'Generic error', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
											<input id="uve-mr-generic-error" type="text" class="regular-text" name="form_config[messages][error]" value="<?php echo esc_attr( $config['messages']['error'] ); ?>">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="uve-mr-tab-help">
							<h4><?php echo esc_html__( 'Tone and clarity', 'uve-mailrelay-newsletter' ); ?></h4>
							<ul class="uve-mr-help-list">
								<li><?php echo esc_html__( 'Keep messages short; they appear inline under the form.', 'uve-mailrelay-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Avoid technical wording. Tell users what to do next.', 'uve-mailrelay-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Match your site voice (formal, friendly, or direct).', 'uve-mailrelay-newsletter' ); ?></li>
							</ul>
						</div>
					</div>
				</div>

				<div id="uve-mr-tab-settings" class="uve-mr-tab-panel">
					<div class="uve-mr-tab-grid">
						<div class="uve-mr-tab-main">
							<div class="uve-mr-panel">
								<h3><?php echo esc_html__( 'Status', 'uve-mailrelay-newsletter' ); ?></h3>
								<div class="uve-mr-form-grid">
									<div class="uve-mr-field-row">
										<label class="uve-mr-field-label" for="uve-mr-form-status"><?php echo esc_html__( 'Visibility', 'uve-mailrelay-newsletter' ); ?></label>
										<div class="uve-mr-field-control">
											<select id="uve-mr-form-status" name="form_status">
												<option value="publish" <?php selected( $status, 'publish' ); ?>>
													<?php echo esc_html__( 'Published', 'uve-mailrelay-newsletter' ); ?>
												</option>
												<option value="draft" <?php selected( $status, 'draft' ); ?>>
													<?php echo esc_html__( 'Draft', 'uve-mailrelay-newsletter' ); ?>
												</option>
											</select>
										</div>
									</div>
								</div>
							</div>

							<div class="uve-mr-panel">
					<h3><?php echo esc_html__( 'Consent', 'uve-mailrelay-newsletter' ); ?></h3>
					<div class="uve-mr-form-grid">
						<div class="uve-mr-field-row">
							<span class="uve-mr-field-label"><?php echo esc_html__( 'Use default consent settings', 'uve-mailrelay-newsletter' ); ?></span>
							<div class="uve-mr-field-control">
								<label><input type="checkbox" name="form_config[consent][inherit]" value="1" <?php checked( $config['consent']['inherit'] ); ?>> <?php echo esc_html__( 'Inherit global consent text and URL', 'uve-mailrelay-newsletter' ); ?></label>
							</div>
						</div>
						<div class="uve-mr-field-row uve-mr-consent-fields">
							<label class="uve-mr-field-label" for="uve-mr-consent-label"><?php echo esc_html__( 'Consent label', 'uve-mailrelay-newsletter' ); ?></label>
							<div class="uve-mr-field-control">
								<input id="uve-mr-consent-label" type="text" class="regular-text" name="form_config[consent][label]" value="<?php echo esc_attr( $config['consent']['label'] ); ?>">
							</div>
						</div>
						<div class="uve-mr-field-row uve-mr-consent-fields">
							<label class="uve-mr-field-label" for="uve-mr-privacy-url"><?php echo esc_html__( 'Privacy URL', 'uve-mailrelay-newsletter' ); ?></label>
							<div class="uve-mr-field-control">
								<input id="uve-mr-privacy-url" type="text" class="regular-text" name="form_config[consent][privacy_url]" value="<?php echo esc_attr( $config['consent']['privacy_url'] ); ?>">
							</div>
						</div>
					</div>
							</div>

							<div class="uve-mr-panel">
					<h3><?php echo esc_html__( 'Spam protection', 'uve-mailrelay-newsletter' ); ?></h3>
					<div class="uve-mr-form-grid">
						<div class="uve-mr-field-row">
							<label class="uve-mr-field-label" for="uve-mr-turnstile-mode"><?php echo esc_html__( 'Turnstile', 'uve-mailrelay-newsletter' ); ?></label>
							<div class="uve-mr-field-control">
								<select id="uve-mr-turnstile-mode" name="form_config[turnstile][mode]" class="uve-mr-turnstile-mode">
									<option value="inherit" <?php selected( $config['turnstile']['mode'] ?? 'inherit', 'inherit' ); ?>>
										<?php echo esc_html__( 'Inherit global setting', 'uve-mailrelay-newsletter' ); ?>
									</option>
									<option value="on" <?php selected( $config['turnstile']['mode'] ?? 'inherit', 'on' ); ?>>
										<?php echo esc_html__( 'Enabled', 'uve-mailrelay-newsletter' ); ?>
									</option>
									<option value="off" <?php selected( $config['turnstile']['mode'] ?? 'inherit', 'off' ); ?>>
										<?php echo esc_html__( 'Disabled', 'uve-mailrelay-newsletter' ); ?>
									</option>
								</select>
								<p class="description"><?php echo esc_html__( 'Choose how this form handles spam protection.', 'uve-mailrelay-newsletter' ); ?></p>
							</div>
						</div>
					</div>
							</div>

							<div class="uve-mr-panel">
					<h3><?php echo esc_html__( 'Advanced', 'uve-mailrelay-newsletter' ); ?></h3>
					<div class="uve-mr-form-grid">
						<div class="uve-mr-field-row">
							<span class="uve-mr-field-label"><?php echo esc_html__( 'Use default rate limit', 'uve-mailrelay-newsletter' ); ?></span>
							<div class="uve-mr-field-control">
								<label><input type="checkbox" name="form_config[rate_limit][inherit]" value="1" <?php checked( $config['rate_limit']['inherit'] ); ?>> <?php echo esc_html__( 'Inherit global rate limits', 'uve-mailrelay-newsletter' ); ?></label>
							</div>
						</div>
						<div class="uve-mr-field-row uve-mr-rate-limit-fields">
							<label class="uve-mr-field-label" for="uve-mr-rate-limit-max"><?php echo esc_html__( 'Max attempts', 'uve-mailrelay-newsletter' ); ?></label>
							<div class="uve-mr-field-control">
								<input id="uve-mr-rate-limit-max" type="number" min="1" class="small-text" name="form_config[rate_limit][max]" value="<?php echo esc_attr( (string) $config['rate_limit']['max'] ); ?>">
							</div>
						</div>
						<div class="uve-mr-field-row uve-mr-rate-limit-fields">
							<label class="uve-mr-field-label" for="uve-mr-rate-limit-window"><?php echo esc_html__( 'Window (seconds)', 'uve-mailrelay-newsletter' ); ?></label>
							<div class="uve-mr-field-control">
								<input id="uve-mr-rate-limit-window" type="number" min="60" class="small-text" name="form_config[rate_limit][window_seconds]" value="<?php echo esc_attr( (string) $config['rate_limit']['window_seconds'] ); ?>">
							</div>
						</div>
						<div class="uve-mr-field-row">
							<span class="uve-mr-field-label"><?php echo esc_html__( 'Enable AJAX', 'uve-mailrelay-newsletter' ); ?></span>
							<div class="uve-mr-field-control">
								<label><input type="checkbox" name="form_config[ajax]" value="1" <?php checked( $config['ajax'], '1' ); ?>> <?php echo esc_html__( 'Submit via AJAX', 'uve-mailrelay-newsletter' ); ?></label>
							</div>
						</div>
					</div>
							</div>
						</div>
						<div class="uve-mr-tab-help">
							<h4><?php echo esc_html__( 'Defaults and overrides', 'uve-mailrelay-newsletter' ); ?></h4>
							<ul class="uve-mr-help-list">
								<li><?php echo esc_html__( 'Inherited settings use the global defaults from the main plugin settings page.', 'uve-mailrelay-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Override only when this form needs different behavior.', 'uve-mailrelay-newsletter' ); ?></li>
								<li><?php echo esc_html__( 'Turning off Turnstile disables it for this form only.', 'uve-mailrelay-newsletter' ); ?></li>
							</ul>
						</div>
					</div>
				</div>

				<div class="uve-mr-sticky-save">
					<button type="submit" class="button button-primary" name="uve_mr_form_save" value="1"><?php echo esc_html__( 'Save Form', 'uve-mailrelay-newsletter' ); ?></button>
					<a href="<?php echo esc_url( $back_url ); ?>" class="button"><?php echo esc_html__( 'Back to list', 'uve-mailrelay-newsletter' ); ?></a>
				</div>
			</form>
		</div>

		<?php
	}
}
