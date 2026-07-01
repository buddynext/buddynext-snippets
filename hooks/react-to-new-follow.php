<?php
/**
 * Plugin Name: BuddyNext Snippet - React to a new follow
 * Description: Runs your code whenever one member follows another.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.0.4
 *
 * `buddynext_user_followed` fires after a follow relationship is created.
 * Signature (verified in includes/SocialGraph/FollowService.php):
 *
 *   do_action( 'buddynext_user_followed', int $follower_id, int $following_id );
 *
 * $follower_id is the member who clicked Follow; $following_id is the member
 * being followed. This example records the last follow in an option so you can
 * confirm the hook fired; replace the body with your own side effect (welcome
 * DM, points, notify, sync a CRM, ...).
 *
 * Docs: developer-guide/28-hooks-members-profiles-social.md
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'buddynext_user_followed',
	static function ( int $follower_id, int $following_id ): void {
		update_option(
			'bnx_last_follow_seen',
			array(
				'follower_id'  => $follower_id,
				'following_id' => $following_id,
				'at'           => time(),
			),
			false
		);
	},
	10,
	2
);
