<?php
/**
 * Plugin Name: BuddyNext Snippet - React to a deleted post
 * Description: Runs your code whenever an activity post is deleted.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.0.4
 *
 * `buddynext_post_deleted` fires after a post and its child rows are removed.
 * Signature (verified in includes/Feed/PostService.php):
 *
 *   do_action( 'buddynext_post_deleted', int $post_id, int $user_id );
 *
 * $user_id is the member who deleted it. The post row is already gone when this
 * fires, so use the ids to clean up anything you stored against the post (an
 * external mirror, a cache entry, a points ledger, ...). This example records
 * the last deletion in an option so you can confirm the hook fired.
 *
 * Docs: developer-guide/27-hooks-feed-content.md
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'buddynext_post_deleted',
	static function ( int $post_id, int $user_id ): void {
		update_option(
			'bnx_last_post_deleted_seen',
			array(
				'post_id' => $post_id,
				'user_id' => $user_id,
				'at'      => time(),
			),
			false
		);
	},
	10,
	2
);
