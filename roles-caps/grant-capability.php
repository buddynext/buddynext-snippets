<?php
/**
 * Plugin Name: BuddyNext Snippet - Grant a capability to specific members
 * Description: Gives chosen members a BuddyNext capability they would not have by role.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.0.4
 *
 * Every permission decision flows through buddynext_can( $user_id, $capability,
 * $context ). Layer 4 of the resolution is the `buddynext_user_can` filter - the
 * final word, so you can grant OR revoke any capability from code. Signature
 * (verified in includes/Core/PermissionService.php):
 *
 *   apply_filters( 'buddynext_user_can', bool $result, int $user_id, string $capability, array $context );
 *
 * This example grants "delete any post" (normally moderator+) to an allowlist of
 * trusted members. Real capability slugs live in PermissionService::ROLE_MAP
 * (e.g. buddynext-feed/create-post, buddynext-feed/delete-any-post,
 * buddynext-spaces/moderate). Return true to grant, false to revoke; return the
 * unchanged $result to stay out of the way.
 *
 * Do NOT call current_user_can() against a BuddyNext capability or read
 * bn_community_role directly - always go through buddynext_can().
 *
 * Docs: developer-guide/39-roles-and-capabilities.md
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'buddynext_user_can',
	static function ( bool $result, int $user_id, string $capability, array $context ): bool {
		// Your trusted-member ids (or resolve them however you like).
		$trusted = array( 773 );

		if ( 'buddynext-feed/delete-any-post' === $capability && in_array( $user_id, $trusted, true ) ) {
			return true;
		}

		return $result;
	},
	10,
	4
);
