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
		$label = self::forms_menu_label();
		add_submenu_page(
			'uve-mr-newsletter',
			$label,
			$label,
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

		$show_upgrade = (bool) apply_filters( 'uve_mr_show_upgrade_ui', true );
		if ( $show_upgrade ) {
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

		$renderer = apply_filters( 'uve_mr_forms_admin_renderer', null );
		if ( is_callable( $renderer ) ) {
			call_user_func( $renderer );
			return;
		}

		self::render_single_form_page();
	}

	/**
	 * Resolve forms menu label.
	 *
	 * @return string
	 */
	private static function forms_menu_label(): string {
		$default = __( 'Form', 'uve-mailrelay-newsletter' );
		return (string) apply_filters( 'uve_mr_forms_menu_label', $default );
	}

	/**
	 * Render the single-form editor view.
	 *
	 * @return void
	 */
	private static function render_single_form_page(): void {
		$form    = UVE_MR_Form_Use_Cases::get_primary_form_for_admin();
		$form_id = $form ? $form->id : 0;
		UVE_MR_Forms_Editor_Page::render( $form_id );
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
	 * Redirect to editor with notice.
	 *
	 * @param string $notice Notice key.
	 * @return void
	 */
	private static function redirect_with_notice( string $notice ): void {
		$url = add_query_arg(
			array(
				'page'          => 'uve-mr-newsletter-forms',
				'uve_mr_status' => $notice,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
