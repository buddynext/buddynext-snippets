<?php
/**
 * Plugin Name: BuddyNext Snippet - Limit what Make, Zapier or n8n may do through the access webhook
 * Description: The access webhook's one secret can grant plans, change credits and more. This lets your payment scenario do only the plan actions, and notes each completed request on the member.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.2.5+
 * Tested up to: BuddyNext 1.2.5
 *
 * WHEN YOU WANT THIS
 * ------------------
 * You sell plans somewhere else (a checkout page, a Make or Zapier scenario) and
 * call POST /wp-json/buddynext/v1/webhook/access to give the buyer their plan.
 * That scenario never needs to change roles or credits, so if its secret leaks
 * you want the damage limited to what it was for.
 *
 * HOW IT WORKS
 * ------------
 * 1. `buddynext_access_webhook_actions` lists the actions the endpoint accepts.
 *    Anything not returned is answered 403 action_not_allowed. Here only the
 *    three plan actions are kept (plans need BuddyNext Pro).
 * 2. `buddynext_access_webhook_completed` fires after an action succeeded (not on
 *    a replayed retry). Here it keeps the last order reference on the member, so
 *    your own code or a support agent can find it.
 *
 * Verified on BuddyNext 1.2.5 by loading this file: the action list comes back as
 * the three plan actions only, and firing the completed action for a member
 * stores the reference (rolled back afterwards). Not run against a live Make or
 * Zapier scenario.
 *
 * @package BuddyNextSnippets
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'buddynext_access_webhook_actions',
	static function ( $actions ) {
		return array_values( array_intersect( (array) $actions, array( 'grant_plan', 'extend_plan', 'cancel_plan' ) ) );
	}
);

add_action(
	'buddynext_access_webhook_completed',
	static function ( $action, $user_id, $body, $response ) {
		if ( 'grant_ability' !== $action && 'grant_plan' !== $action ) {
			return;
		}
		$reference = isset( $body['reference'] ) ? sanitize_text_field( (string) $body['reference'] ) : '';
		if ( '' !== $reference ) {
			update_user_meta( (int) $user_id, 'my_last_order_reference', $reference );
		}
	},
	10,
	4
);
