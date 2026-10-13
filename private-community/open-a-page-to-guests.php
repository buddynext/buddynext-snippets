<?php
/**
 * Plugin Name: BuddyNext Snippet - Keep one community page open to guests on a private community
 * Description: On a private community every BuddyNext page sends a logged-out visitor to log in. This keeps the spaces directory listing open, and only that, so visitors can see what they would be joining.
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
 * page's hub key (feed, people, spaces, messages, notifications, auth,
 * membership). Return true and guests may open it.
 *
 * READ THIS BEFORE YOU WIDEN IT
 * -----------------------------
 * The filter answers for a whole hub, not for one page. Returning true for
 * 'spaces' on its own would open EVERY page under /spaces/ to guests: each open
 * space's home, its posts and its member list, on a community you made private.
 * That is almost never what "let visitors see the list of spaces" means.
 *
 * So this snippet opens the directory listing only. It checks that the request
 * is the /spaces/ index (no space in the address) and leaves every single space
 * behind the login, as it was. If you really do want open spaces readable by
 * guests, remove the `bn_space_slug` check below, knowing that is what it does.
 *
 * The same care applies to 'people': it covers the member directory AND every
 * member profile. This snippet does not open it.
 *
 * Verified on BuddyNext 1.2.5 by loading this file and asking the filter: the
 * spaces index answers true; the same hub with a space in the address answers
 * false; 'feed' and 'people' keep the default (false); 'auth' stays true. Not
 * walked in a browser on a site with Private community switched on.
 *
 * @package BuddyNextSnippets
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'buddynext_private_community_public_hub',
	static function ( $is_public, $hub ) {
		if ( $is_public ) {
			return true;
		}

		// The spaces directory only: the /spaces/ index, never a single space.
		return 'spaces' === $hub && '' === (string) get_query_var( 'bn_space_slug', '' );
	},
	10,
	2
);
