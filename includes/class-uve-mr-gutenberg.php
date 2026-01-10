<?php
/**
 * Gutenberg block integration.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenberg block integration.
 */
final class UVE_MR_Gutenberg {

	const BLOCK_NAME    = 'uve-mr/newsletter';
	const SCRIPT_HANDLE = 'uve-mr-newsletter-block';

	/**
	 * Register the Gutenberg block.
	 *
	 * @return void
	 */
	public static function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$script_src = plugins_url(
			'assets/blocks/uve-mr-newsletter-block.js',
			__DIR__ . '/../class-uve-mailrelay-newsletter.php'
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			$script_src,
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor' ),
			UVE_Mailrelay_Newsletter::VERSION,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'uveMrNewsletterBlockData',
			array(
				'attributes'  => self::get_block_attributes(),
				'formOptions' => self::get_block_form_options(),
			)
		);

		register_block_type(
			self::BLOCK_NAME,
			array(
				'api_version'     => '2',
				'editor_script'   => self::SCRIPT_HANDLE,
				'render_callback' => array( __CLASS__, 'render_block' ),
				'attributes'      => self::get_block_attributes(),
			)
		);
	}

	/**
	 * Build block attributes (filterable for premium).
	 *
	 * @return array
	 */
	private static function get_block_attributes(): array {
		$opts = UVE_Mailrelay_Newsletter::get_options();

		$attributes = array(
			'title'            => array(
				'type'    => 'string',
				'default' => $opts['title'],
			),
			'description'      => array(
				'type'    => 'string',
				'default' => $opts['description'],
			),
			'emailPlaceholder' => array(
				'type'    => 'string',
				'default' => $opts['email_placeholder'],
			),
			'submitLabel'      => array(
				'type'    => 'string',
				'default' => $opts['submit_label'],
			),
			'groupIds'         => array(
				'type'    => 'string',
				'default' => $opts['group_ids'],
			),
			'privacyUrl'       => array(
				'type'    => 'string',
				'default' => $opts['privacy_url'],
			),
			'consentLabel'     => array(
				'type'    => 'string',
				'default' => $opts['consent_label'],
			),
			'extraClass'       => array(
				'type'    => 'string',
				'default' => '',
			),
			'ajaxMode'         => array(
				'type'    => 'boolean',
				'default' => '1' === (string) $opts['ajax_mode'],
			),
		);

		$attributes = apply_filters( 'uve_mr_block_attributes', $attributes );
		return is_array( $attributes ) ? $attributes : array();
	}

	/**
	 * Build form select options (filterable for premium).
	 *
	 * @return array
	 */
	private static function get_block_form_options(): array {
		$options = apply_filters( 'uve_mr_block_form_options', array() );
		return is_array( $options ) ? array_values( $options ) : array();
	}

	/**
	 * Render the block output.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_block( array $attributes ): string {
		$has_form_id = array_key_exists( 'formId', $attributes );
		$form_id     = $has_form_id ? (int) $attributes['formId'] : 0;

		if ( ! $has_form_id ) {
			$primary_form = UVE_MR_Form_Use_Cases::get_primary_form_for_admin();
			$form_id      = $primary_form ? $primary_form->id : 0;
		}

		if ( $form_id ) {
			$form_args = array(
				'id'    => $form_id,
				'class' => $attributes['extraClass'] ?? '',
			);
		} else {
			$form_args = array(
				'title'             => $attributes['title'] ?? '',
				'description'       => $attributes['description'] ?? '',
				'email_placeholder' => $attributes['emailPlaceholder'] ?? '',
				'submit_label'      => $attributes['submitLabel'] ?? '',
				'group_ids'         => $attributes['groupIds'] ?? '',
				'privacy_url'       => $attributes['privacyUrl'] ?? '',
				'consent_label'     => $attributes['consentLabel'] ?? '',
				'class'             => $attributes['extraClass'] ?? '',
				'ajax'              => ! empty( $attributes['ajaxMode'] ) ? '1' : '0',
			);
		}

		return UVE_MR_Frontend::shortcode( $form_args );
	}
}
