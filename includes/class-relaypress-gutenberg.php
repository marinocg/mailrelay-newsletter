<?php
/**
 * Gutenberg block integration.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenberg block integration.
 */
final class RelayPress_Gutenberg {

	const BLOCK_NAME    = 'relaypress/newsletter';
	const SCRIPT_HANDLE = 'relaypress-newsletter-block';

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
			'assets/blocks/relaypress-newsletter-block.js',
			__DIR__ . '/../class-relaypress-newsletter.php'
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			$script_src,
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor' ),
			RelayPress_Newsletter::VERSION,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'relaypressNewsletterBlockData',
			array(
				'attributes'       => self::get_block_attributes(),
				'formOptions'      => self::get_block_form_options(),
				'formEmptyMessage' => __( 'No forms yet. Create your first form.', 'relaypress-newsletter' ),
				'formCreateLabel'  => __( 'Create a form', 'relaypress-newsletter' ),
				'formCreateUrl'    => self::get_forms_create_url(),
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
		$opts = RelayPress_Newsletter::get_options();

		$attributes = array(
			'formId'           => array(
				'type'    => 'string',
				'default' => '',
			),
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

		$attributes = apply_filters( 'relaypress_block_attributes', $attributes );
		return is_array( $attributes ) ? $attributes : array();
	}

	/**
	 * Build form select options (filterable for premium).
	 *
	 * @return array
	 */
	private static function get_block_form_options(): array {
		$forms   = RelayPress_Form_Use_Cases::list_forms(
			array(
				'post_status'    => array( 'publish' ),
				'posts_per_page' => 100,
			)
		);
		$options = array();

		foreach ( $forms as $form ) {
			$options[] = array(
				'value' => (string) $form->id,
				'label' => $form->name,
			);
		}

		$options = apply_filters( 'relaypress_block_form_options', $options );
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
		$form_id_raw = $has_form_id ? (string) $attributes['formId'] : '';
		$form_id     = absint( $form_id_raw );

		if ( ! $form_id ) {
			$primary_form = RelayPress_Form_Use_Cases::get_primary_form_for_admin();
			$form_id      = $primary_form ? $primary_form->id : 0;
		}

		if ( $form_id ) {
			$form_args = array(
				'id'    => $form_id,
				'class' => $attributes['extraClass'] ?? '',
			);
			if ( array_key_exists( 'ajaxMode', $attributes ) ) {
				$form_args['ajax'] = ! empty( $attributes['ajaxMode'] ) ? '1' : '0';
			}
			if ( array_key_exists( 'privacyUrl', $attributes ) ) {
				$form_args['privacy_url'] = (string) $attributes['privacyUrl'];
			}
			if ( array_key_exists( 'consentLabel', $attributes ) ) {
				$form_args['consent_label'] = (string) $attributes['consentLabel'];
			}
		} else {
			return RelayPress_Frontend::shortcode();
		}

		return RelayPress_Frontend::shortcode( $form_args );
	}

	/**
	 * Build the admin URL to create a form.
	 *
	 * @return string
	 */
	private static function get_forms_create_url(): string {
		return add_query_arg(
			array(
				'page'   => 'relaypress-newsletter-forms',
				'action' => 'new',
			),
			admin_url( 'admin.php' )
		);
	}
}
