<?php
/**
 * Save extensions use-case.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save extensions use-case.
 */
final class RelayPress_Save_Extensions {

	/**
	 * Extension registry.
	 *
	 * @var RelayPress_Extension_Registry
	 */
	private RelayPress_Extension_Registry $registry;

	/**
	 * Extension state repository.
	 *
	 * @var RelayPress_Extension_State_Repository
	 */
	private RelayPress_Extension_State_Repository $state;

	/**
	 * Create the save extensions use-case.
	 *
	 * @param RelayPress_Extension_Registry         $registry Extension registry.
	 * @param RelayPress_Extension_State_Repository $state    Extension state repository.
	 */
	public function __construct(
		RelayPress_Extension_Registry $registry,
		RelayPress_Extension_State_Repository $state
	) {
		$this->registry = $registry;
		$this->state    = $state;
	}

	/**
	 * Execute the save extensions use-case.
	 *
	 * @param string[] $enabled_slugs Enabled slugs.
	 * @return void
	 */
	public function execute( array $enabled_slugs ): void {
		$enabled_map = array();
		foreach ( $enabled_slugs as $slug ) {
			$enabled_map[ $slug ] = true;
		}

		foreach ( $this->registry->get_providers() as $provider ) {
			$extension = $provider->get_extension();
			$slug      = $extension->get_slug();
			$this->state->set_enabled( $slug, isset( $enabled_map[ $slug ] ) );
		}
	}
}
