<?php
/**
 * Plugin Name: BuddyNext Snippet - React to a new activity post
 * Description: Runs your code whenever a member publishes an activity post.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.0.4
 *
 * `buddynext_post_created` fires after a post is stored, from both the composer
 * and the scheduled-post publisher. Signature (verified in
 * includes/Feed/PostService.php):
 *
 *   do_action( 'buddynext_post_created', int $post_id, int $user_id, string $type );
 *
 * $type is the post kind: 'text', 'photo', 'video', 'poll', 'media', etc. This
 * example just records the last post seen in an option so you can confirm the
 * hook fired; replace the body with your own side effect (award points, notify,
 * mirror to another system, ...). Keep the work light or defer heavy work to
 * Action Scheduler - this runs inside the request that created the post.
 *
 * Docs: developer-guide/28-hooks-members-profiles-social.md (and hooks-feed-content)
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'buddynext_post_created',
	static function ( int $post_id, int $user_id, string $type ): void {
		update_option(
			'bnx_last_post_seen',
			array(
				'post_id' => $post_id,
				'user_id' => $user_id,
				'type'    => $type,
				'at'      => time(),
			),
			false
		);
	},
	10,
	3
);
