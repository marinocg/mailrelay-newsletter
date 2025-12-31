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
			if ( in_array( $action, array( 'duplicate', 'trash' ), true ) ) {
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
		} elseif ( 'error' === $notice ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Something went wrong.', 'uve-mailrelay-newsletter' ) . '</p></div>';
		}
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html__( 'Forms', 'uve-mailrelay-newsletter' ); ?></h1>
			<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action"><?php echo esc_html__( 'Add New', 'uve-mailrelay-newsletter' ); ?></a>
			<hr class="wp-header-end">
			<form method="post">
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
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Name', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_name" value="<?php echo esc_attr( $name ); ?>" required></td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Basics', 'uve-mailrelay-newsletter' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Title', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_config[basics][title]" value="<?php echo esc_attr( $config['basics']['title'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Description', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_config[basics][description]" value="<?php echo esc_attr( $config['basics']['description'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Email placeholder', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_config[basics][email_placeholder]" value="<?php echo esc_attr( $config['basics']['email_placeholder'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Submit button text', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_config[basics][submit_label]" value="<?php echo esc_attr( $config['basics']['submit_label'] ); ?>"></td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Destination', 'uve-mailrelay-newsletter' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Group IDs', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_config[destination][group_ids]" value="<?php echo esc_attr( $config['destination']['group_ids'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Subscriber status', 'uve-mailrelay-newsletter' ); ?></th>
						<td>
							<select name="form_config[destination][subscriber_status]">
								<option value="inactive" <?php selected( $config['destination']['subscriber_status'], 'inactive' ); ?>><?php echo esc_html__( 'Inactive (double opt-in)', 'uve-mailrelay-newsletter' ); ?></option>
								<option value="active" <?php selected( $config['destination']['subscriber_status'], 'active' ); ?>><?php echo esc_html__( 'Active (single opt-in)', 'uve-mailrelay-newsletter' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Fields', 'uve-mailrelay-newsletter' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$fields = $config['fields'] ?? array();
					foreach ( $fields as $key => $field ) :
						if ( ! is_array( $field ) ) {
							continue;
						}
						$enabled     = ! empty( $field['enabled'] );
						$label       = $field['label'] ?? '';
						$placeholder = $field['placeholder'] ?? '';
						$disabled    = ( 'email' === $key );
						?>
						<tr>
							<th scope="row"><?php echo esc_html( ucfirst( (string) $key ) ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="form_config[fields][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $enabled ); ?> <?php disabled( $disabled ); ?>>
									<?php echo esc_html__( 'Enable field', 'uve-mailrelay-newsletter' ); ?>
								</label>
								<?php if ( $disabled ) : ?>
									<input type="hidden" name="form_config[fields][<?php echo esc_attr( $key ); ?>][enabled]" value="1">
									<p class="description"><?php echo esc_html__( 'Email is required by Mailrelay and cannot be disabled.', 'uve-mailrelay-newsletter' ); ?></p>
								<?php endif; ?>
								<p>
									<label><?php echo esc_html__( 'Label', 'uve-mailrelay-newsletter' ); ?><br>
										<input type="text" class="regular-text" name="form_config[fields][<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $label ); ?>">
									</label>
								</p>
								<?php if ( 'email' === $key ) : ?>
									<p class="description"><?php echo esc_html__( 'Email placeholder is set in the Basics section.', 'uve-mailrelay-newsletter' ); ?></p>
								<?php else : ?>
									<p>
										<label><?php echo esc_html__( 'Placeholder', 'uve-mailrelay-newsletter' ); ?><br>
											<input type="text" class="regular-text" name="form_config[fields][<?php echo esc_attr( $key ); ?>][placeholder]" value="<?php echo esc_attr( $placeholder ); ?>">
										</label>
									</p>
								<?php endif; ?>
								<?php if ( 'phone' === $key ) : ?>
									<p class="description"><?php echo esc_html__( 'Used for SMS or WhatsApp in Mailrelay; the same value is sent to both.', 'uve-mailrelay-newsletter' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2><?php echo esc_html__( 'Consent', 'uve-mailrelay-newsletter' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Use default consent settings', 'uve-mailrelay-newsletter' ); ?></th>
						<td><label><input type="checkbox" name="form_config[consent][inherit]" value="1" <?php checked( $config['consent']['inherit'] ); ?>> <?php echo esc_html__( 'Inherit global consent text and URL', 'uve-mailrelay-newsletter' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Consent label', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_config[consent][label]" value="<?php echo esc_attr( $config['consent']['label'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Privacy URL', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_config[consent][privacy_url]" value="<?php echo esc_attr( $config['consent']['privacy_url'] ); ?>"></td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Spam protection', 'uve-mailrelay-newsletter' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Use default Turnstile settings', 'uve-mailrelay-newsletter' ); ?></th>
						<td><label><input type="checkbox" name="form_config[turnstile][inherit]" value="1" <?php checked( $config['turnstile']['inherit'] ); ?>> <?php echo esc_html__( 'Inherit global Turnstile keys', 'uve-mailrelay-newsletter' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Enable Turnstile on this form', 'uve-mailrelay-newsletter' ); ?></th>
						<td><label><input type="checkbox" name="form_config[turnstile][enabled]" value="1" <?php checked( $config['turnstile']['enabled'] ); ?>> <?php echo esc_html__( 'Enable spam protection', 'uve-mailrelay-newsletter' ); ?></label></td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Messages', 'uve-mailrelay-newsletter' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Success message', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_config[messages][success]" value="<?php echo esc_attr( $config['messages']['success'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Captcha error', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_config[messages][captcha]" value="<?php echo esc_attr( $config['messages']['captcha'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Consent error', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_config[messages][consent]" value="<?php echo esc_attr( $config['messages']['consent'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Generic error', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="text" class="regular-text" name="form_config[messages][error]" value="<?php echo esc_attr( $config['messages']['error'] ); ?>"></td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Advanced', 'uve-mailrelay-newsletter' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Use default rate limit', 'uve-mailrelay-newsletter' ); ?></th>
						<td><label><input type="checkbox" name="form_config[rate_limit][inherit]" value="1" <?php checked( $config['rate_limit']['inherit'] ); ?>> <?php echo esc_html__( 'Inherit global rate limits', 'uve-mailrelay-newsletter' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Max attempts', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="number" min="1" class="small-text" name="form_config[rate_limit][max]" value="<?php echo esc_attr( (string) $config['rate_limit']['max'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Window (seconds)', 'uve-mailrelay-newsletter' ); ?></th>
						<td><input type="number" min="60" class="small-text" name="form_config[rate_limit][window_seconds]" value="<?php echo esc_attr( (string) $config['rate_limit']['window_seconds'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Enable AJAX', 'uve-mailrelay-newsletter' ); ?></th>
						<td><label><input type="checkbox" name="form_config[ajax]" value="1" <?php checked( $config['ajax'], '1' ); ?>> <?php echo esc_html__( 'Submit via AJAX', 'uve-mailrelay-newsletter' ); ?></label></td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary" name="uve_mr_form_save" value="1"><?php echo esc_html__( 'Save Form', 'uve-mailrelay-newsletter' ); ?></button>
					<a href="<?php echo esc_url( $back_url ); ?>" class="button"><?php echo esc_html__( 'Back to list', 'uve-mailrelay-newsletter' ); ?></a>
				</p>
			</form>
		</div>

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
