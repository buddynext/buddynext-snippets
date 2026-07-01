<?php
/**
 * Plugin Name: BuddyNext Snippet - React to a member joining a space
 * Description: Runs your code whenever a member joins a space.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.0.4
 *
 * `buddynext_space_member_joined` fires after a member joins a space (a direct
 * join on an open space, or an approved request). Signature (verified in
 * includes/Spaces/SpaceMemberService.php):
 *
 *   do_action( 'buddynext_space_member_joined', int $space_id, int $user_id, string $role );
 *
 * $role is the role the member joined with ('member'). This example records the
 * last join in an option so you can confirm the hook fired; replace the body
 * with your own side effect (post a welcome, grant access elsewhere, notify a
 * moderator, ...).
 *
 * Docs: developer-guide/29-hooks-spaces.md
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'buddynext_space_member_joined',
	static function ( int $space_id, int $user_id, string $role ): void {
		update_option(
			'bnx_last_space_join_seen',
			array(
				'space_id' => $space_id,
				'user_id'  => $user_id,
				'role'     => $role,
				'at'       => time(),
			),
			false
		);
	},
	10,
	3
);
