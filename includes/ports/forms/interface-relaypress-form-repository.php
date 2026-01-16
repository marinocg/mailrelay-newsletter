<?php
/**
 * Form repository interface.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form repository interface.
 */
interface RelayPress_Form_Repository_Interface {
	/**
	 * Get a form by ID.
	 *
	 * @param int $id Form ID.
	 * @return RelayPress_Form|null
	 */
	public function get( int $id ): ?RelayPress_Form;

	/**
	 * Get all forms.
	 *
	 * @param array $args Query args.
	 * @return RelayPress_Form[]
	 */
	public function list( array $args = array() ): array;

	/**
	 * Create a new form.
	 *
	 * @param string $name Form name.
	 * @param array  $config Config array.
	 * @param string $status Form status.
	 * @return RelayPress_Form|null
	 */
	public function create( string $name, array $config, string $status ): ?RelayPress_Form;

	/**
	 * Update a form.
	 *
	 * @param int    $id Form ID.
	 * @param string $name Form name.
	 * @param array  $config Config array.
	 * @param string $status Form status.
	 * @return RelayPress_Form|null
	 */
	public function update( int $id, string $name, array $config, string $status ): ?RelayPress_Form;

	/**
	 * Trash a form.
	 *
	 * @param int $id Form ID.
	 * @return bool
	 */
	public function trash( int $id ): bool;

	/**
	 * Count published forms, optionally excluding one ID.
	 *
	 * @param int|null $exclude_id Form ID to exclude.
	 * @return int
	 */
	public function count_published( ?int $exclude_id = null ): int;
}
