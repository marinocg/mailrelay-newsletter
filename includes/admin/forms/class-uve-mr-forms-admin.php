<?php
/**
 * Forms admin screens.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Forms admin screens.
 */
final class UVE_MR_Forms_Admin {
	/**
	 * Register forms submenu.
	 *
	 * @return void
	 */
	public static function admin_menu(): void {
		add_submenu_page(
			'uve-mr-newsletter',
			__( 'Forms', 'uve-mailrelay-newsletter' ),
			__( 'Forms', 'uve-mailrelay-newsletter' ),
			'manage_options',
			'uve-mr-newsletter-forms',
			array( __CLASS__, 'render_forms_page' )
		);
	}

	/**
	 * Handle admin actions.
	 *
	 * @return void
	 */
	public static function admin_init(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['uve_mr_refresh_groups'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nonce = sanitize_text_field( (string) wp_unslash( $_GET['_wpnonce'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $nonce && wp_verify_nonce( $nonce, 'uve_mr_refresh_groups' ) ) {
				delete_transient( 'uve_mr_groups_cache' );
			}
		}

		if ( isset( $_POST['uve_mr_form_save'] ) ) {
			self::handle_save();
		}

		$page = sanitize_text_field( (string) wp_unslash( $_GET['page'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'uve-mr-newsletter-forms' === $page && isset( $_GET['action'], $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nonce = sanitize_text_field( (string) wp_unslash( $_GET['_wpnonce'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'uve_mr_forms_action' ) ) {
				return;
			}
			$action  = sanitize_text_field( (string) wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_id = absint( wp_unslash( $_GET['form_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $action, array( 'duplicate', 'trash', 'restore', 'delete' ), true ) ) {
				self::handle_row_action( $action, $form_id );
			}
		}
	}

	/**
	 * Render forms list or editor.
	 *
	 * @return void
	 */
	public static function render_forms_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_text_field( (string) wp_unslash( $_GET['action'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $action, array( 'edit', 'new' ), true ) ) {
			self::render_form_editor();
			return;
		}

		self::render_forms_list();
	}

	/**
	 * Render list view.
	 *
	 * @return void
	 */
	private static function render_forms_list(): void {
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
		if ( 'saved' === $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form saved.', 'uve-mailrelay-newsletter' ) . '</p></div>';
		} elseif ( 'duplicated' === $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form duplicated.', 'uve-mailrelay-newsletter' ) . '</p></div>';
		} elseif ( 'trashed' === $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form moved to trash.', 'uve-mailrelay-newsletter' ) . '</p></div>';
		} elseif ( 'bulk-updated' === $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Forms updated.', 'uve-mailrelay-newsletter' ) . '</p></div>';
		} elseif ( 'error' === $notice ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Something went wrong.', 'uve-mailrelay-newsletter' ) . '</p></div>';
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
	 * Render form editor.
	 *
	 * @return void
	 */
	private static function render_form_editor(): void {
		$form_id  = absint( wp_unslash( $_GET['form_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form     = $form_id ? UVE_MR_Form_Use_Cases::get_form( $form_id ) : null;
		$opts     = UVE_Mailrelay_Newsletter::get_options();
		$defaults = UVE_MR_Form_Config::defaults( $opts );

		if ( $form ) {
			$config = $form->merge_config( $defaults );
			$name   = $form->name;
		} else {
			$config = $defaults;
			$name   = '';
		}

		$back_url = add_query_arg( 'page', 'uve-mr-newsletter-forms', admin_url( 'admin.php' ) );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $form ? __( 'Edit Form', 'uve-mailrelay-newsletter' ) : __( 'Add New Form', 'uve-mailrelay-newsletter' ) ); ?></h1>
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

		<script>
			(function() {
				var tabs = document.querySelectorAll('.uve-mr-tabs a');
				var panels = document.querySelectorAll('.uve-mr-tab-panel');
				if (!tabs.length || !panels.length) return;

				function activateTab(targetId) {
					tabs.forEach(function(tab) {
						var isActive = tab.getAttribute('href') === targetId;
						tab.classList.toggle('nav-tab-active', isActive);
					});
					panels.forEach(function(panel) {
						panel.classList.toggle('is-active', '#' + panel.id === targetId);
					});
				}

				tabs.forEach(function(tab) {
					tab.addEventListener('click', function(ev) {
						ev.preventDefault();
						var targetId = tab.getAttribute('href');
						if (targetId) {
							activateTab(targetId);
						}
					});
				});

				activateTab('#uve-mr-tab-fields');

				function toggleGroup(checkboxSelector, rowSelector) {
					var checkbox = document.querySelector(checkboxSelector);
					if (!checkbox) return;
					var rows = document.querySelectorAll(rowSelector);
					function update() {
						var hide = checkbox.checked;
						rows.forEach(function(row) {
							row.style.display = hide ? 'none' : '';
						});
					}
					checkbox.addEventListener('change', update);
					update();
				}

				toggleGroup('input[name="form_config[consent][inherit]"]', '.uve-mr-consent-fields');
				toggleGroup('input[name="form_config[rate_limit][inherit]"]', '.uve-mr-rate-limit-fields');

				var available = document.getElementById('uve-mr-groups-available');
				var selected = document.getElementById('uve-mr-groups-selected');
				var hiddenInput = document.getElementById('uve-mr-group-ids');
				var addBtn = document.getElementById('uve-mr-group-add');
				var removeBtn = document.getElementById('uve-mr-group-remove');

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

		<style>
			.uve-mr-tab-panel {
				display: none;
				margin-top: 16px;
			}
			.uve-mr-tab-panel.is-active {
				display: block;
			}
			.uve-mr-tab-grid {
				display: grid;
				grid-template-columns: minmax(0, 3fr) minmax(260px, 1fr);
				gap: 24px;
				align-items: start;
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
			.uve-mr-tab-main {
				min-width: 0;
			}
			.uve-mr-panel {
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				padding: 16px;
				margin-bottom: 16px;
			}
			.uve-mr-panel h3 {
				margin-top: 0;
			}
			.uve-mr-tab-help {
				background: #f6f7f7;
				border: 1px solid #dcdcde;
				border-radius: 6px;
				padding: 14px 16px;
			}
			.uve-mr-tab-help h4 {
				margin-top: 0;
			}
			.uve-mr-help-list {
				margin: 0;
				padding-left: 18px;
			}
			.uve-mr-help-list li {
				margin-bottom: 8px;
			}
			.uve-mr-tab-panel .widefat input.regular-text {
				width: 100%;
				max-width: 100%;
			}
			.uve-mr-tab-panel .widefat td {
				vertical-align: top;
			}
			.uve-mr-tab-panel .widefat .description {
				margin-top: 4px;
			}
			.uve-mr-fields-table {
				width: 100%;
			}
			.uve-mr-table-wrap {
				overflow-x: auto;
			}
			.uve-mr-fields-table th:nth-child(2),
			.uve-mr-fields-table th:nth-child(3),
			.uve-mr-fields-table td:nth-child(2),
			.uve-mr-fields-table td:nth-child(3) {
				text-align: center;
				width: 80px;
			}
			.uve-mr-sticky-save {
				position: sticky;
				bottom: 12px;
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				padding: 12px 16px;
				display: flex;
				gap: 8px;
				justify-content: flex-start;
				z-index: 2;
				margin-top: 20px;
				box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
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
			@media (max-width: 960px) {
				.uve-mr-tab-grid {
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
		<?php
	}

	/**
	 * Handle saving a form.
	 *
	 * @return void
	 */
	private static function handle_save(): void {
		check_admin_referer( 'uve_mr_form_save' );

		$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
		$name    = sanitize_text_field( (string) wp_unslash( $_POST['form_name'] ?? '' ) );
		if ( '' === $name ) {
			self::redirect_with_notice( 'error' );
		}

		$opts     = UVE_Mailrelay_Newsletter::get_options();
		$defaults = UVE_MR_Form_Config::defaults( $opts );

		$raw_config = wp_unslash( $_POST['form_config'] ?? array() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_config = is_array( $raw_config ) ? $raw_config : array();

		$config                   = UVE_MR_Form_Config::sanitize( $raw_config, $defaults );
		$config['basics']['name'] = $name;

		$form = $form_id
			? UVE_MR_Form_Use_Cases::update_form( $form_id, $name, $config )
			: UVE_MR_Form_Use_Cases::create_form( $name, $config );

		if ( ! $form ) {
			self::redirect_with_notice( 'error' );
		}

		self::redirect_with_notice( 'saved' );
	}

	/**
	 * Handle row actions.
	 *
	 * @param string $action Action name.
	 * @param int    $form_id Form ID.
	 * @return void
	 */
	private static function handle_row_action( string $action, int $form_id ): void {
		if ( ! $form_id ) {
			self::redirect_with_notice( 'error' );
		}

		check_admin_referer( 'uve_mr_forms_action' );

		if ( 'duplicate' === $action ) {
			$form = UVE_MR_Form_Use_Cases::duplicate_form( $form_id );
			self::redirect_with_notice( $form ? 'duplicated' : 'error' );
		}

		if ( 'trash' === $action ) {
			$ok = UVE_MR_Form_Use_Cases::trash_form( $form_id );
			self::redirect_with_notice( $ok ? 'trashed' : 'error' );
		}

		if ( 'restore' === $action ) {
			$ok = wp_update_post(
				array(
					'ID'          => $form_id,
					'post_status' => 'publish',
				)
			);
			self::redirect_with_notice( $ok ? 'bulk-updated' : 'error' );
		}

		if ( 'delete' === $action ) {
			$ok = wp_delete_post( $form_id, true );
			self::redirect_with_notice( $ok ? 'bulk-updated' : 'error' );
		}
	}

	/**
	 * Redirect to list with notice.
	 *
	 * @param string $notice Notice key.
	 * @return void
	 */
	private static function redirect_with_notice( string $notice ): void {
		$url = add_query_arg(
			array(
				'page'   => 'uve-mr-newsletter-forms',
				'notice' => $notice,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
