<?php
/**
 * Plugin Name: BuddyNext Snippet - React when a member's profile is saved
 * Description: Runs your code whenever a member's profile values change (for example to keep a CRM or mailing list in step), whichever screen saved them.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.2.1+
 * Tested up to: BuddyNext 1.2.5
 *
 * WHEN YOU WANT THIS
 * ------------------
 * A member changes their job title, city or interests and you want that copied to
 * your CRM, newsletter tool or another plugin's user data.
 *
 * HOW IT WORKS
 * ------------
 * `buddynext_profile_saved` fires after the save has been written. Every way in
 * goes through it: the member's own Edit Profile, the admin member editor,
 * onboarding, registration and importers. $data holds what was submitted, keyed
 * by field key, so you can act only on the fields you care about.
 *
 * The request to your service is sent without waiting for the answer, so a slow
 * CRM never slows the member's save. Replace the address and the field keys.
 *
 * Verified on BuddyNext 1.2.5 by loading this file and firing the action for a
 * member with a changed 'location': one request was built for the address below
 * with the member's email and the new value (captured, not sent). A save that
 * touched none of the watched fields built no request.
 *
 * @package BuddyNextSnippets
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'buddynext_profile_saved',
	static function ( $user_id, $data ) {
		// The profile field keys you want to pass on. See BuddyNext > Members > Profile Fields.
		$watched = array( 'location', 'job_title' );
		$changed = array_intersect_key( (array) $data, array_flip( $watched ) );
		if ( empty( $changed ) ) {
			return;
		}

		$user = get_userdata( (int) $user_id );
		if ( ! $user ) {
			return;
		}

		wp_remote_post(
			'https://crm.example.com/api/contacts',
			array(
				'blocking' => false,
				'headers'  => array( 'Content-Type' => 'application/json' ),
				'body'     => wp_json_encode(
					array(
						'email'  => $user->user_email,
						'fields' => $changed,
					)
				),
			)
		);
	},
	10,
	2
);
