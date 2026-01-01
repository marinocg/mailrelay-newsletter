<?php
/**
 * Forms registration and migration.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Forms registration and migration.
 */
final class UVE_MR_Forms {
	private const MIGRATION_OPTION = 'uve_mr_forms_migrated';

	/**
	 * Register the custom post type.
	 *
	 * @return void
	 */
	public static function register_post_type(): void {
		register_post_type(
			UVE_MR_Form::POST_TYPE,
			array(
				'labels'             => array(
					'name'          => __( 'MR4WP Forms', 'uve-mailrelay-newsletter' ),
					'singular_name' => __( 'MR4WP Form', 'uve-mailrelay-newsletter' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_menu'       => false,
				'show_in_rest'       => false,
				'has_archive'        => false,
				'rewrite'            => false,
				'supports'           => array( 'title', 'revisions' ),
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'can_export'         => true,
			),
		);
	}

	/**
	 * Create a default form from existing settings if needed.
	 *
	 * @return void
	 */
	public static function maybe_migrate_default_form(): void {
		if ( get_option( self::MIGRATION_OPTION ) ) {
			return;
		}

		$existing = UVE_MR_Form_Use_Cases::list_forms(
			array(
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 1,
			)
		);

		if ( ! empty( $existing ) ) {
			update_option( self::MIGRATION_OPTION, '1' );
			return;
		}

		$opts                       = UVE_Mailrelay_Newsletter::get_options();
		$defaults                   = UVE_MR_Form_Config::defaults( $opts );
		$defaults['basics']['name'] = __( 'Default Form', 'uve-mailrelay-newsletter' );

		UVE_MR_Form_Use_Cases::create_form( $defaults['basics']['name'], $defaults );
		update_option( self::MIGRATION_OPTION, '1' );
	}
}
