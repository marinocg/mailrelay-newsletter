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
			if ( in_array( $action, array( 'duplicate', 'trash', 'restore', 'delete', 'publish', 'draft' ), true ) ) {
				self::handle_row_action( $action, $form_id );
			}
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function admin_enqueue( string $hook_suffix ): void {
		if ( false === strpos( $hook_suffix, 'uve-mr-newsletter-forms' ) ) {
			return;
		}

		$base    = UVE_MR_Utils::plugin_file();
		$css_src = plugins_url( 'assets/admin-forms.css', $base );
		$js_src  = plugins_url( 'assets/admin-forms.js', $base );

		wp_enqueue_style( 'uve-mr-admin-forms', $css_src, array(), UVE_Mailrelay_Newsletter::VERSION );
		wp_enqueue_script( 'uve-mr-admin-forms', $js_src, array(), UVE_Mailrelay_Newsletter::VERSION, true );

		if ( ! UVE_MR_Utils::is_premium_installed() ) {
			$up_js = plugins_url( 'assets/admin-upgrade.js', $base );
			wp_enqueue_script( 'uve-mr-admin-upgrade', $up_js, array(), UVE_Mailrelay_Newsletter::VERSION, true );
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
			UVE_MR_Forms_Editor_Page::render();
			return;
		}

		UVE_MR_Forms_List_Page::render();
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
		$status = sanitize_text_field( (string) wp_unslash( $_POST['form_status'] ?? 'publish' ) );
		$status = 'draft' === $status ? 'draft' : 'publish';
		$notice = 'saved';
		if ( 'publish' === $status && ! UVE_MR_Form_Use_Cases::can_publish_form( $form_id ? $form_id : null ) ) {
			$status = 'draft';
			$notice = 'upgrade';
		}

		$opts     = UVE_Mailrelay_Newsletter::get_options();
		$defaults = UVE_MR_Form_Config::defaults( $opts );

		$raw_config = wp_unslash( $_POST['form_config'] ?? array() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_config = is_array( $raw_config ) ? $raw_config : array();

		$config                   = UVE_MR_Form_Config::sanitize( $raw_config, $defaults );
		$config['basics']['name'] = $name;

		$form = $form_id
			? UVE_MR_Form_Use_Cases::update_form( $form_id, $name, $config, $status )
			: UVE_MR_Form_Use_Cases::create_form( $name, $config, $status );

		if ( ! $form ) {
			self::redirect_with_notice( 'error' );
		}

		self::redirect_with_notice( $notice );
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

		if ( 'publish' === $action ) {
			$can_publish = UVE_MR_Form_Use_Cases::can_publish_form( $form_id );
			$status      = $can_publish ? 'publish' : 'draft';
			$ok          = wp_update_post(
				array(
					'ID'          => $form_id,
					'post_status' => $status,
				)
			);
			self::redirect_with_notice( $ok ? ( $can_publish ? 'saved' : 'upgrade' ) : 'error' );
		}

		if ( 'draft' === $action ) {
			$ok = wp_update_post(
				array(
					'ID'          => $form_id,
					'post_status' => 'draft',
				)
			);
			self::redirect_with_notice( $ok ? 'saved' : 'error' );
		}

		if ( 'restore' === $action ) {
			$can_publish = UVE_MR_Form_Use_Cases::can_publish_form( $form_id );
			$status      = $can_publish ? 'publish' : 'draft';
			$ok          = wp_update_post(
				array(
					'ID'          => $form_id,
					'post_status' => $status,
				)
			);
			self::redirect_with_notice( $ok ? ( $can_publish ? 'bulk-updated' : 'upgrade' ) : 'error' );
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
