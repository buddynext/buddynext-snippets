<?php
/**
 * Plugin Name: BuddyNext Snippet - React when a member updates their interests
 * Description: Runs your code whenever a member changes their picked interests.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0.4+
 * Tested up to: BuddyNext 1.0.4
 *
 * Members pick interests (space categories) during onboarding and edit them on
 * their profile. Every write path funnels through one choke point, which fires:
 *
 *   do_action( 'buddynext_member_interests_updated', int $user_id );
 *
 * BuddyNext itself uses this to refresh the member's people/space suggestions.
 * Read the member's current picks with
 * ( new \BuddyNext\Onboarding\OnboardingService() )->get_interest_ids( $user_id )
 * - it returns space-category IDs in pick order (empty array when none).
 *
 * Docs: developer-guide (hooks) + docs/plans note: picks are stored in the
 * 'interests' profile field, one row per pick - never user meta.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'buddynext_member_interests_updated',
	static function ( int $user_id ): void {
		$ids = ( new \BuddyNext\Onboarding\OnboardingService() )->get_interest_ids( $user_id );

		// Your reaction here. This demo just records the latest picks.
		update_option( 'bnx_last_interest_change', array( 'user_id' => $user_id, 'picks' => $ids ), false );
	},
	10,
	1
);
