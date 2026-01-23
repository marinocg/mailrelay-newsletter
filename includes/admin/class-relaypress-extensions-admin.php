<?php
/**
 * Extensions admin page adapter.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extensions admin page adapter.
 */
final class RelayPress_Extensions_Admin {

	/**
	 * List extensions use-case.
	 *
	 * @var RelayPress_List_Extensions|null
	 */
	private static ?RelayPress_List_Extensions $list_use_case = null;

	/**
	 * Save extensions use-case.
	 *
	 * @var RelayPress_Save_Extensions|null
	 */
	private static ?RelayPress_Save_Extensions $save_use_case = null;

	/**
	 * Register admin hooks.
	 *
	 * @param RelayPress_List_Extensions $list_use_case List use-case.
	 * @param RelayPress_Save_Extensions $save_use_case Save use-case.
	 * @return void
	 */
	public static function init(
		RelayPress_List_Extensions $list_use_case,
		RelayPress_Save_Extensions $save_use_case
	): void {
		self::$list_use_case = $list_use_case;
		self::$save_use_case = $save_use_case;
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_post_relaypress_save_extensions', array( __CLASS__, 'handle_save' ) );
	}

	/**
	 * Register extensions submenu.
	 *
	 * @return void
	 */
	public static function admin_menu(): void {
		add_submenu_page(
			'relaypress-newsletter',
			__( 'Extensions', 'relaypress-newsletter' ),
			__( 'Extensions', 'relaypress-newsletter' ),
			'manage_options',
			'relaypress-newsletter-extensions',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Handle the extensions form submission.
	 *
	 * @return void
	 */
	public static function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) || null === self::$save_use_case ) {
			return;
		}

		check_admin_referer( 'relaypress_save_extensions' );

		$enabled = array();
		$raw     = wp_unslash( $_POST['relaypress_extensions'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( is_array( $raw ) ) {
			foreach ( $raw as $slug => $flag ) {
				$slug = sanitize_key( (string) $slug );
				$flag = sanitize_text_field( (string) $flag );
				if ( '1' === $flag ) {
					$enabled[] = $slug;
				}
			}
		}

		self::$save_use_case->execute( $enabled );

		$redirect = add_query_arg(
			array(
				'page'    => 'relaypress-newsletter-extensions',
				'updated' => '1',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Render the extensions admin page.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) || null === self::$list_use_case ) {
			return;
		}

		$extensions = self::$list_use_case->execute();
		$updated    = sanitize_text_field( (string) wp_unslash( $_GET['updated'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Extensions', 'relaypress-newsletter' ); ?></h1>
			<?php if ( '1' === $updated ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Extensions updated.', 'relaypress-newsletter' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'relaypress_save_extensions' ); ?>
				<input type="hidden" name="action" value="relaypress_save_extensions" />

				<table class="widefat striped relaypress-extensions-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Extension', 'relaypress-newsletter' ); ?></th>
							<th><?php echo esc_html__( 'Status', 'relaypress-newsletter' ); ?></th>
							<th><?php echo esc_html__( 'Activation', 'relaypress-newsletter' ); ?></th>
							<th><?php echo esc_html__( 'Settings', 'relaypress-newsletter' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $extensions ) ) : ?>
							<tr>
								<td colspan="4"><?php echo esc_html__( 'No extensions installed.', 'relaypress-newsletter' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $extensions as $item ) : ?>
								<?php
									$extension         = $item->get_extension();
									$slug              = $extension->get_slug();
									$enabled           = $item->is_enabled();
									$available         = $extension->is_available();
									$settings          = $extension->get_settings_url();
									$checkbox_id       = 'relaypress-ext-' . sanitize_key( $slug );
									$status_label      = $available ? ( $enabled ? __( 'Enabled', 'relaypress-newsletter' ) : __( 'Disabled', 'relaypress-newsletter' ) ) : __( 'Unavailable', 'relaypress-newsletter' );
									$status_class      = $available ? ( $enabled ? 'is-enabled' : 'is-disabled' ) : 'is-unavailable';
									$availability_note = ( ! $available ) ? $extension->get_requirement_note() : '';
									$badges            = apply_filters( 'relaypress_extension_badges', array(), $extension, $item );
								?>
								<tr>
									<td>
										<strong><?php echo esc_html( $extension->get_label() ); ?></strong>
										<?php if ( is_array( $badges ) && ! empty( $badges ) ) : ?>
											<span class="relaypress-extension-tags">
												<?php foreach ( $badges as $badge ) : ?>
													<?php
														$badge_label = '';
														$badge_class = '';
													if ( is_array( $badge ) ) {
														$badge_label = (string) ( $badge['label'] ?? '' );
														$badge_class = (string) ( $badge['class'] ?? '' );
													} elseif ( is_string( $badge ) ) {
														$badge_label = $badge;
													}
													if ( '' === $badge_label ) {
														continue;
													}
													?>
													<span class="relaypress-extension-tag <?php echo esc_attr( $badge_class ); ?>">
														<?php echo esc_html( $badge_label ); ?>
													</span>
												<?php endforeach; ?>
											</span>
										<?php endif; ?>
										<p class="description">
											<?php echo esc_html( $extension->get_description() ); ?>
											<?php if ( '' !== $availability_note ) : ?>
												<br /><span class="relaypress-extensions-note"><?php echo esc_html( $availability_note ); ?></span>
											<?php endif; ?>
										</p>
									</td>
									<td>
										<span class="relaypress-extensions-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
									</td>
									<td>
										<label class="relaypress-extensions-toggle" for="<?php echo esc_attr( $checkbox_id ); ?>">
											<input
												id="<?php echo esc_attr( $checkbox_id ); ?>"
												type="checkbox"
												name="relaypress_extensions[<?php echo esc_attr( $slug ); ?>]"
												value="1"
												<?php checked( $enabled, true ); ?>
												<?php echo $available ? '' : 'disabled="disabled"'; ?>
											/>
											<span class="relaypress-extensions-switch" aria-hidden="true"></span>
										</label>
									</td>
									<td>
										<?php if ( ! empty( $settings ) ) : ?>
											<a href="<?php echo esc_url( $settings ); ?>"><?php echo esc_html__( 'Settings', 'relaypress-newsletter' ); ?></a>
										<?php else : ?>
											&mdash;
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<p class="relaypress-extensions-save-note">
					<?php echo esc_html__( 'Toggle an extension, then click "Save Changes" to apply.', 'relaypress-newsletter' ); ?>
				</p>

				<?php submit_button( __( 'Save Changes', 'relaypress-newsletter' ) ); ?>
			</form>
		</div>
		<style>
			.relaypress-extensions-table td {
				vertical-align: top;
			}
			.relaypress-extension-tags {
				display: inline-flex;
				flex-wrap: wrap;
				gap: 6px;
				margin-left: 8px;
				vertical-align: middle;
			}
			.relaypress-extension-tag {
				display: inline-flex;
				align-items: center;
				padding: 2px 8px;
				border-radius: 999px;
				font-weight: 600;
				font-size: 11px;
				line-height: 1.6;
				background: #eef2ff;
				color: #3730a3;
			}
			.relaypress-extension-tag.is-premium {
				background: #fff4e5;
				color: #b45309;
			}
			.relaypress-extensions-badge {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				padding: 2px 10px;
				border-radius: 999px;
				font-weight: 600;
				font-size: 12px;
				line-height: 1.8;
				width: fit-content;
				border: 1px solid transparent;
			}
			.relaypress-extensions-badge.is-enabled {
				background: #e7f5ee;
				border-color: #9fd8b2;
				color: #146c43;
			}
			.relaypress-extensions-badge.is-disabled {
				background: #f1f5f9;
				border-color: #cbd5e1;
				color: #475569;
			}
			.relaypress-extensions-badge.is-unavailable {
				background: #fff1f2;
				border-color: #fecdd3;
				color: #9f1239;
			}
			.relaypress-extensions-note {
				color: #9f1239;
				font-weight: 600;
			}
			.relaypress-extensions-toggle {
				display: inline-flex;
				align-items: center;
				gap: 8px;
			}
			.relaypress-extensions-toggle input {
				position: absolute;
				opacity: 0;
				width: 1px;
				height: 1px;
				overflow: hidden;
			}
			.relaypress-extensions-switch {
				position: relative;
				width: 44px;
				height: 24px;
				background: #cbd5e1;
				border-radius: 999px;
				transition: background 0.2s ease;
			}
			.relaypress-extensions-switch::after {
				content: "";
				position: absolute;
				top: 3px;
				left: 3px;
				width: 18px;
				height: 18px;
				border-radius: 50%;
				background: #fff;
				transition: transform 0.2s ease;
			}
			.relaypress-extensions-toggle input:checked + .relaypress-extensions-switch {
				background: #16a34a;
			}
			.relaypress-extensions-toggle input:checked + .relaypress-extensions-switch::after {
				transform: translateX(20px);
			}
			.relaypress-extensions-toggle input:disabled + .relaypress-extensions-switch {
				opacity: 0.6;
				cursor: not-allowed;
			}
			.relaypress-extensions-save-note {
				margin-top: 16px;
				color: #475569;
			}
		</style>
		<?php
	}
}
