<?php
/**
 * Plugin Name: BuddyNext Snippet - Let another membership plugin decide who reads members-only posts
 * Description: A members-only post opens for anyone who is logged in (BuddyNext alone) or for members on a paid plan (with BuddyNext Pro). This opens it only for people your membership plugin says have paid (Paid Memberships Pro in the example), and points the button on a locked post at your own pricing page.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.2.4+
 * Tested up to: BuddyNext 1.2.5
 *
 * WHEN YOU WANT THIS
 * ------------------
 * You already run a membership plugin with its own levels, checkout and login
 * pages, and "members only" on a BuddyNext post should mean "paying member", not
 * "has an account".
 *
 * HOW IT WORKS
 * ------------
 * 1. `buddynext_members_only_has_access` answers whether this viewer may read a
 *    members-only post. The author always can. Return true only for your payers.
 * 2. `buddynext_members_only_cta` is the button shown on the locked post. Send it
 *    to your pricing or login page.
 *
 * Both filters receive the viewer id and a small array about the post (id,
 * user_id, space_id). The same answer is used on the page and in the REST API,
 * so the mobile app follows it too.
 *
 * Both filters are added at priority 20 on purpose. BuddyNext Pro answers the
 * same two filters at priority 10 from its own plans; running later means your
 * membership plugin has the last word. If you sell with BuddyNext Pro's plans
 * you do not need this snippet.
 *
 * To use a different plugin, change the two marked lines: the "has this person
 * paid" test and the pricing page address.
 *
 * Verified on BuddyNext 1.2.5 (with Pro active) by loading this file with a stand-in for the
 * membership test: a logged-in member without a level is refused and gets the
 * button to the pricing page, a member with a level is let in, and a guest gets
 * the same button. Not run with Paid Memberships Pro switched on; test it with
 * your levels.
 *
 * @package BuddyNextSnippets
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether this person has paid. CHANGE THIS for your membership plugin.
 *
 * @param int $user_id Viewer id, 0 for a guest.
 * @return bool
 */
function bnx_snippet_viewer_has_paid( $user_id ) {
	if ( $user_id <= 0 ) {
		return false;
	}

	/**
	 * Lets you plug in any other test without editing this file.
	 *
	 * @param bool|null $paid    Null to use the default test below.
	 * @param int       $user_id Viewer id.
	 */
	$paid = apply_filters( 'bnx_snippet_viewer_has_paid', null, $user_id );
	if ( null !== $paid ) {
		return (bool) $paid;
	}

	// Paid Memberships Pro: any active level. Pass level ids to limit it, e.g. array( 2, 3 ).
	return function_exists( 'pmpro_hasMembershipLevel' ) && pmpro_hasMembershipLevel( null, $user_id );
}

add_filter(
	'buddynext_members_only_has_access',
	static function ( $has_access, $viewer_id, $post ) {
		return bnx_snippet_viewer_has_paid( (int) $viewer_id );
	},
	20,
	3
);

add_filter(
	'buddynext_members_only_cta',
	static function ( $cta, $viewer_id, $post ) {
		// CHANGE THIS to your pricing page. pmpro_url( 'levels' ) is Paid Memberships Pro's.
		$pricing = function_exists( 'pmpro_url' ) ? (string) pmpro_url( 'levels' ) : home_url( '/pricing/' );

		return array(
			'label' => __( 'Become a member to read this', 'bnx-snippet' ),
			'url'   => '' !== $pricing ? $pricing : home_url( '/pricing/' ),
		);
	},
	20,
	3
);
