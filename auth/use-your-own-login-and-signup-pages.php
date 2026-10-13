<?php
/**
 * Plugin Name: BuddyNext Snippet - Use your own login and sign-up pages
 * Description: Hands the site's Log in, Register and Lost password links back to WordPress (or to the login plugin you run), and points BuddyNext's own Sign up links at a page you choose.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.2.5+
 * Tested up to: BuddyNext 1.2.5
 *
 * THE PROBLEM
 * -----------
 * On the front end BuddyNext answers WordPress's own auth addresses with its own
 * screens: wp_login_url(), wp_registration_url() and wp_lostpassword_url() all
 * return the BuddyNext page (PageRouter::core_login_url() and its two siblings,
 * hooked on `login_url`, `register_url` and `lostpassword_url` at priority 30).
 * A site that signs members in through another plugin, a membership product, an
 * SSO provider or a hand-built page wants those links left alone.
 *
 * WHAT THIS DOES
 * --------------
 * 1. `buddynext_owns_core_auth_links` (1.2.5) returns false, so BuddyNext stops
 *    rewriting the three WordPress addresses. Whatever WordPress, your theme or
 *    your login plugin returns for them is what visitors get.
 * 2. BuddyX and Reign carry their own copy of the same rewrite for BuddyNext
 *    (priority 20 on the same three filters), and that copy does not read the
 *    filter above. On those themes it is removed too; on any other theme the
 *    removal finds nothing and does nothing.
 * 3. `buddynext_signup_url` points every BuddyNext "Sign up" link at your page.
 * 4. `buddynext_post_register_redirect` and `buddynext_redirect_url` are shown
 *    commented out: where a member lands after signing up, logging in or logging
 *    out, if you want that to differ from the owner's settings.
 *
 * Accounts made on your own form are still full BuddyNext members. BuddyNext
 * listens on WordPress's `user_register` and `wp_login`, so email verification,
 * the welcome email, space auto-join, the member search index and the two-factor
 * challenge run whichever form created the account or signed the member in.
 *
 * WHAT IT DOES NOT DO (yet)
 * -------------------------
 * BuddyNext's own "Log in" links and its guest gate (a logged-out visitor opening
 * a members-only page, or any page of a private community) still lead to the
 * BuddyNext login screen: PageRouter::login_url() has no filter in 1.2.5. The
 * screens only BuddyNext can show (email verification, the reset-password step,
 * the two-factor code) stay there as well, which is what you want.
 *
 * Verified on BuddyNext 1.2.5 (free + Pro, BuddyX) by loading this file and
 * reading the addresses back in a front-end context: with the snippet off,
 * wp_login_url(), wp_registration_url() and wp_lostpassword_url() are BuddyNext
 * pages and PageRouter::signup_url() is the BuddyNext sign-up page; with it on,
 * the three WordPress addresses are WordPress's own again (wp-login.php, or
 * WooCommerce's lost-password page where WooCommerce is active) and signup_url()
 * is the page set below. Checked on BuddyX; the Reign filter names were read from
 * the theme, not run. It was not walked in a browser on a clean install with a
 * third-party login plugin active; test it with yours.
 *
 * Docs: developer-guide/31-hooks-moderation-auth-trust.md.
 *
 * @package BuddyNextSnippets
 */

defined( 'ABSPATH' ) || exit;

/**
 * Leave WordPress's Log in, Register and Lost password addresses alone.
 *
 * Return true again for a single request if you ever need the BuddyNext screens
 * back (for example behind your own feature flag).
 */
add_filter( 'buddynext_owns_core_auth_links', '__return_false' );

/**
 * Remove the BuddyX and Reign copies of the same rewrite.
 *
 * Both themes hook them at priority 20 when they load, so this runs on `init`,
 * after the theme. Each line is a no-op when that theme is not active.
 */
add_action(
	'init',
	static function () {
		foreach ( array( 'buddyx', 'reign' ) as $theme ) {
			remove_filter( 'login_url', $theme . '_buddynext_filter_login_url', 20 );
			remove_filter( 'register_url', $theme . '_buddynext_filter_register_url', 20 );
			remove_filter( 'lostpassword_url', $theme . '_buddynext_filter_lostpassword_url', 20 );
		}
	}
);

/**
 * Send BuddyNext's "Sign up" links to your registration page.
 *
 * @param string $url The BuddyNext sign-up address.
 * @return string
 */
add_filter(
	'buddynext_signup_url',
	static function ( $url ) {
		// Change '/join/' to the path of your registration page.
		return home_url( '/join/' );
	}
);

/*
 * Optional: where a new member lands straight after signing up on BuddyNext's own
 * form. Remove the comment markers to use it.
 *
 * add_filter(
 *     'buddynext_post_register_redirect',
 *     static function ( $url, $user_id, $params ) {
 *         return home_url( '/welcome/' );
 *     },
 *     10,
 *     3
 * );
 */

/*
 * Optional: where a member lands after logging in or out. $context is 'login',
 * 'logout' or 'onboarding'. This runs after the owner's own setting, so it wins.
 * An address on another domain also has to be allowed through WordPress's
 * `allowed_redirect_hosts` filter.
 *
 * add_filter(
 *     'buddynext_redirect_url',
 *     static function ( $url, $context, $fallback ) {
 *         return 'logout' === $context ? home_url( '/goodbye/' ) : $url;
 *     },
 *     10,
 *     3
 * );
 */
