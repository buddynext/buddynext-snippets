<?php
/**
 * Plugin Name: BuddyNext Snippet - Keep one community page open to guests on a private community
 * Description: On a private community every BuddyNext page sends a logged-out visitor to log in. This keeps the pages you choose (the spaces directory here) open, so visitors can see what they would be joining.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.2.4+
 * Tested up to: BuddyNext 1.2.5
 *
 * WHEN YOU WANT THIS
 * ------------------
 * "Private community" is on (BuddyNext > Settings > Privacy), and you still want a
 * shop window: a visitor should be able to browse the list of spaces, or the
 * member directory, before they decide to sign up.
 *
 * HOW IT WORKS
 * ------------
 * `buddynext_private_community_public_hub` is asked once per page view with the
 * page's hub key. Return true and guests may open it. The hub keys are:
 * feed, people, spaces, messages, notifications, auth, membership.
 *
 * Opening a hub opens its PAGES. What a guest can read inside is still decided
 * by each space and each post: a private or secret space stays closed, and a
 * members-only post still shows its teaser.
 *
 * Verified on BuddyNext 1.2.5 by loading this file and asking the filter: 'spaces'
 * answers true, 'feed' and 'people' keep the default (false), 'auth' stays true.
 * Not walked in a browser on a site with Private community switched on.
 *
 * @package BuddyNextSnippets
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'buddynext_private_community_public_hub',
	static function ( $is_public, $hub ) {
		// Add 'people' to also open the member directory.
		$open_to_guests = array( 'spaces' );

		return $is_public || in_array( $hub, $open_to_guests, true );
	},
	10,
	2
);
