<?php
/**
 * Plugin Name: BuddyNext Snippet - Ask the gate before offering an action
 * Description: Only render a "Join this space" control when the join would actually be allowed, so you never offer an action a listener will refuse.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.2.0
 *
 * Free cannot know what Pro (or your own gate on `buddynext_can_join_space`) will
 * decide. If you render a Join button for anyone looking at an open space, a
 * plan-gated space shows the member a "you need a paid plan" screen with a Join
 * button beside it inviting them to join anyway. Ask the gate first:
 *
 *   buddynext_service( 'space_members' )->can_join( array|object $space, int $user_id ): bool
 *   (verified in includes/Spaces/SpaceMemberService.php:2596 — pass 0 for a guest)
 *
 * can_join() runs the SAME gate the join itself runs, so a true answer means the
 * offer is real. The same reasoning applies to any control you render on someone
 * else's behalf: ask whether the action would succeed before advertising it.
 *
 * Docs: developer-guide/41-extending-cookbook.md (Recipe 14)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Echo a Join control for a space, but only when the current user could actually
 * join it. Returns silently when the join would be refused.
 *
 * @param array|object $space A bn_spaces row (array) or the object templates carry.
 * @return void
 */
function bnx_snippet_maybe_render_join( $space ): void {
	if ( ! function_exists( 'buddynext_service' ) ) {
		return;
	}
	$members = buddynext_service( 'space_members' );
	if ( ! $members || ! method_exists( $members, 'can_join' ) ) {
		return;
	}

	// Ask the gate the join itself uses. Only a real offer gets a button.
	if ( ! $members->can_join( $space, get_current_user_id() ) ) {
		return;
	}

	$space_id = is_array( $space ) ? (int) ( $space['id'] ?? 0 ) : (int) ( $space->id ?? 0 );
	printf(
		'<button type="button" class="bnx-join" data-space-id="%d">%s</button>',
		esc_attr( (string) $space_id ),
		esc_html__( 'Join this space', 'bnx-snippet' )
	);
}
