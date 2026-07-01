<?php
/**
 * Plugin Name: BuddyNext Snippet - Defer heavy work to the background
 * Description: Runs slow work AFTER the request via Action Scheduler, under the buddynext group.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.0.4
 *
 * BuddyNext's rule: keep the request fast. When an event handler needs to do
 * slow work (call an API, write many rows, rebuild a cache), don't do it inline
 * - enqueue a one-off async action and let Action Scheduler run it out of band.
 * Run it under the `buddynext` group so it's observable/retryable in
 * Tools > Scheduled Actions. Fall back to a direct call when AS is absent so the
 * feature still works. This mirrors the core fan-out pattern in
 * includes/Notifications/NotificationListener.php.
 *
 * Here we defer work whenever a post is created. Replace bnx_do_deferred_work()
 * with your own side effect.
 *
 * Docs: developer-guide/37-cron-and-async.md ; standard: docs/standards/BACKGROUND-JOBS.md
 */

defined( 'ABSPATH' ) || exit;

const BNX_ASYNC_GROUP = 'buddynext';

// 1) Enqueue the async action from an event (keep the request light).
add_action(
	'buddynext_post_created',
	static function ( int $post_id, int $user_id, string $type ): void {
		$args = array( $post_id, $user_id );
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			// Guard against double-queueing the same job.
			if ( ! as_next_scheduled_action( 'bnx_deferred_after_post', $args, BNX_ASYNC_GROUP ) ) {
				as_enqueue_async_action( 'bnx_deferred_after_post', $args, BNX_ASYNC_GROUP );
			}
			return;
		}
		// No Action Scheduler: run inline as a fallback.
		bnx_do_deferred_work( $post_id, $user_id );
	},
	10,
	3
);

// 2) The handler - same hook name AS (or WP-Cron) fires.
add_action( 'bnx_deferred_after_post', 'bnx_do_deferred_work', 10, 2 );

function bnx_do_deferred_work( int $post_id, int $user_id ): void {
	// Your slow work here. This demo just records that it ran.
	update_option( 'bnx_deferred_ran', array( 'post_id' => $post_id, 'user_id' => $user_id, 'at' => time() ), false );
}
