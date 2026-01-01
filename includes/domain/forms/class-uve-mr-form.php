<?php
/**
 * Form model.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form model.
 */
final class UVE_MR_Form {
	public const POST_TYPE      = 'mr4wp_form';
	public const META_CONFIG    = '_mr4wp_form_config';
	public const META_VERSION   = '_mr4wp_form_version';
	public const CONFIG_VERSION = 1;

	/**
	 * Form ID.
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * Form name.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * Form status.
	 *
	 * @var string
	 */
	public string $status;

	/**
	 * Last modified timestamp.
	 *
	 * @var string
	 */
	public string $updated_at;

	/**
	 * Raw config.
	 *
	 * @var array
	 */
	public array $config;

	/**
	 * Build from a WP_Post.
	 *
	 * @param WP_Post $post Post instance.
	 * @return self
	 */
	public static function from_post( WP_Post $post ): self {
		$self             = new self();
		$self->id         = (int) $post->ID;
		$self->name       = (string) $post->post_title;
		$self->status     = (string) $post->post_status;
		$self->updated_at = (string) $post->post_modified;
		$config           = get_post_meta( $post->ID, self::META_CONFIG, true );
		$self->config     = is_array( $config ) ? $config : array();
		return $self;
	}

	/**
	 * Get the effective config merged with defaults.
	 *
	 * @param array $defaults Default config.
	 * @return array
	 */
	public function merge_config( array $defaults ): array {
		return UVE_MR_Form_Config::merge( $defaults, $this->config );
	}
}
