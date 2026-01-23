<?php
/**
 * List extensions use-case.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * List extensions use-case.
 */
final class RelayPress_List_Extensions {

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
	 * Create the list extensions use-case.
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
	 * Execute the list extensions use-case.
	 *
	 * @return RelayPress_Extension_Status[]
	 */
	public function execute(): array {
		$providers = $this->registry->get_providers();
		$items     = array();

		foreach ( $providers as $provider ) {
			$extension = $provider->get_extension();
			$enabled   = $this->state->is_enabled( $extension->get_slug(), $extension->is_default_enabled() );
			$items[]   = new RelayPress_Extension_Status( $extension, $enabled );
		}

		usort(
			$items,
			static function ( RelayPress_Extension_Status $a, RelayPress_Extension_Status $b ): int {
				return strcmp( $a->get_extension()->get_label(), $b->get_extension()->get_label() );
			}
		);

		return $items;
	}
}
