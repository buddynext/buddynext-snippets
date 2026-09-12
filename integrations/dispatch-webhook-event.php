<?php
/**
 * Plugin Name: BuddyNext Snippet - Fire your own outbound webhook event
 * Description: Sends your plugin's own event to every external endpoint the site owner has registered, signed and retried like BuddyNext's own events.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.2.0
 *
 * BuddyNext's OutboundWebhookListener does nothing more than call
 *   buddynext_service( 'webhooks' )->dispatch( $event_slug, $payload )
 * from each core action handler (verified: includes/Outbound/OutboundWebhookService.php:403,
 * `public function dispatch( string $event_slug, array $payload ): void`). Your addon
 * does the same with its own slug. Delivery is queued to Action Scheduler, fanned out
 * only to endpoints subscribed to that slug, signed with each endpoint's HMAC secret,
 * logged, and retried with backoff. You write one line.
 *
 * Endpoints that subscribe to ALL events (an empty subscription list) receive your slug
 * automatically; endpoints with an explicit list receive it only if the slug is on it.
 *
 * Do NOT query bn_outbound_webhooks or build signatures yourself - dispatching through
 * the service is the only supported path and is what keeps signing, retry and logging
 * correct. Requires the Webhooks feature to be enabled (Platform -> Features).
 *
 * Docs: developer-guide/41-extending-cookbook.md (Recipe 3)
 */

defined( 'ABSPATH' ) || exit;

// Replace 'my_addon_course_completed' with your plugin's own action, and the slug and
// payload with your event. Use a dotted namespace for the slug (e.g. 'course.completed').
add_action(
	'my_addon_course_completed',
	static function ( int $user_id, int $course_id ): void {
		if ( ! function_exists( 'buddynext_service' ) ) {
			return; // BuddyNext inactive - nothing to dispatch to.
		}
		$webhooks = buddynext_service( 'webhooks' );
		if ( ! $webhooks || ! method_exists( $webhooks, 'dispatch' ) ) {
			return;
		}
		$webhooks->dispatch(
			'course.completed',
			array(
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'completed' => current_time( 'mysql', true ),
			)
		);
	},
	10,
	2
);
