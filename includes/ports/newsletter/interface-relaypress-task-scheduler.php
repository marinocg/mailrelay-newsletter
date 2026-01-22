<?php
/**
 * Task scheduler port.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Task scheduler port.
 */
interface RelayPress_Task_Scheduler {
	/**
	 * Check if async scheduling is available.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Enqueue an async action.
	 *
	 * @param string $hook Action hook.
	 * @param array  $args Action arguments.
	 * @param string $group Action group.
	 * @return bool
	 */
	public function enqueue( string $hook, array $args = array(), string $group = '' ): bool;

	/**
	 * Schedule a single action at a specific time.
	 *
	 * @param int    $timestamp Unix timestamp.
	 * @param string $hook Action hook.
	 * @param array  $args Action arguments.
	 * @param string $group Action group.
	 * @return bool
	 */
	public function schedule( int $timestamp, string $hook, array $args = array(), string $group = '' ): bool;
}
