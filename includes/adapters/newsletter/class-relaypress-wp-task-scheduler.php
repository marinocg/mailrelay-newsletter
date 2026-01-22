<?php
/**
 * WordPress task scheduler adapter.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress task scheduler adapter.
 */
final class RelayPress_WP_Task_Scheduler implements RelayPress_Task_Scheduler {
	/**
	 * Check if Action Scheduler functions exist.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return function_exists( 'as_enqueue_async_action' ) || function_exists( 'as_schedule_single_action' );
	}

	/**
	 * Enqueue an async action.
	 *
	 * @param string $hook Action hook.
	 * @param array  $args Action arguments.
	 * @param string $group Action group.
	 * @return bool
	 */
	public function enqueue( string $hook, array $args = array(), string $group = '' ): bool {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( $hook, $args, $group );
			return true;
		}
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time(), $hook, $args, $group );
			return true;
		}
		return false;
	}

	/**
	 * Schedule a single action at a specific time.
	 *
	 * @param int    $timestamp Unix timestamp.
	 * @param string $hook Action hook.
	 * @param array  $args Action arguments.
	 * @param string $group Action group.
	 * @return bool
	 */
	public function schedule( int $timestamp, string $hook, array $args = array(), string $group = '' ): bool {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( $timestamp, $hook, $args, $group );
			return true;
		}
		if ( function_exists( 'as_enqueue_async_action' ) && $timestamp <= time() ) {
			as_enqueue_async_action( $hook, $args, $group );
			return true;
		}
		return false;
	}
}
