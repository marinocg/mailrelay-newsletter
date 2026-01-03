<?php
/**
 * Form repository interface.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form repository interface.
 */
interface UVE_MR_Form_Repository_Interface {
	/**
	 * Get a form by ID.
	 *
	 * @param int $id Form ID.
	 * @return UVE_MR_Form|null
	 */
	public function get( int $id ): ?UVE_MR_Form;

	/**
	 * Get all forms.
	 *
	 * @param array $args Query args.
	 * @return UVE_MR_Form[]
	 */
	public function list( array $args = array() ): array;

	/**
	 * Create a new form.
	 *
	 * @param string $name Form name.
	 * @param array  $config Config array.
	 * @param string $status Form status.
	 * @return UVE_MR_Form|null
	 */
	public function create( string $name, array $config, string $status ): ?UVE_MR_Form;

	/**
	 * Update a form.
	 *
	 * @param int    $id Form ID.
	 * @param string $name Form name.
	 * @param array  $config Config array.
	 * @param string $status Form status.
	 * @return UVE_MR_Form|null
	 */
	public function update( int $id, string $name, array $config, string $status ): ?UVE_MR_Form;

	/**
	 * Trash a form.
	 *
	 * @param int $id Form ID.
	 * @return bool
	 */
	public function trash( int $id ): bool;
}
