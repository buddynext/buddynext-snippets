<?php
/**
 * Plugin Name: BuddyNext Snippet - Gate who can join a space
 * Description: Blocks a member from joining or requesting any space while a flag is set on their account (a probation / hold pattern).
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.2.0
 *
 * `buddynext_can_join_space` is the single seam both the direct-join and the
 * request-to-join paths pass through (verified in
 * includes/Spaces/SpaceMemberService.php:131 and :254). Return false to block
 * both the join button and the request flow, so a gated space cannot be entered
 * through either path. This is the same seam BuddyNext Pro uses for paid gating.
 *
 * Signature:
 *   apply_filters( 'buddynext_can_join_space', bool $can, array $space, int $user_id, string $action )
 *   - $action is 'join' or 'request'.
 *
 * This example holds any member who carries the `bnx_join_blocked` user meta -
 * set that meta from your own code (moderation hold, unpaid dues, probation) and
 * clear it to release them. Swap the condition for a capability check, a plan
 * lookup, or a per-space meta flag to build your own rule.
 *
 * Docs: developer-guide/41-extending-cookbook.md (Recipe 6)
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'buddynext_can_join_space',
	static function ( bool $can, array $space, int $user_id, string $action ): bool {
		if ( ! $can ) {
			return $can; // Already blocked upstream - do not override.
		}

		// Held members cannot join or request any space until the flag is cleared.
		if ( $user_id > 0 && get_user_meta( $user_id, 'bnx_join_blocked', true ) ) {
			return false;
		}

		return $can;
	},
	10,
	4
);
