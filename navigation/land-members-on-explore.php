<?php
/**
 * Plugin Name: BuddyNext Snippet - Land members on Explore
 * Description: Sends logged-in members who open /activity/ to the Explore feed instead of their Home feed.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.1+
 * Tested up to: BuddyNext 1.2.1
 *
 * Logged-out visitors already land on Explore when "Public explore feed" is on
 * (BuddyNext > Settings > General > Discovery). This does the same for members.
 * Only the bare /activity/ URL redirects, so /activity/explore/ and the other
 * feed views still work, and a site whose front page IS the activity page
 * keeps its homepage.
 *
 * Build the target with PageRouter::explore_url() rather than a hard-coded
 * path: the activity slug is configurable under Settings > Pages & URLs.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'template_redirect',
	static function (): void {
		if ( is_user_logged_in()
			&& 'feed' === get_query_var( 'bn_hub' )
			&& '' === (string) get_query_var( 'bn_activity_action' )
			&& ! is_front_page()
		) {
			wp_safe_redirect( \BuddyNext\Core\PageRouter::explore_url() );
			exit;
		}
	},
	5
);
