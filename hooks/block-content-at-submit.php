<?php
/**
 * Plugin Name: BuddyNext Snippet - Block content at submit with your own rule
 * Description: Rejects a post or comment at submit time (and on edit) when it matches a rule you define.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.2.0
 *
 * `buddynext_safeguard_check` runs in SafeguardService::check() after the built-in
 * IP, banned-word, blocked-domain, rate-limit and banned-hashtag gates, and before
 * the duplicate-content and new-member gates. Return true to allow, or a WP_Error to
 * reject - the WP_Error message is shown to the member. The same filter runs on edits
 * (verified in includes/Moderation/SafeguardService.php:91 and :150). This is the seam
 * the Pro Moderation Rules engine attaches its keyword blocklists and ML scoring to.
 *
 * Signature (FIVE args - declare all five):
 *   apply_filters( 'buddynext_safeguard_check', $result, int $user_id, string $content, string $link_url, string $context )
 *   - $context is 'create' or 'edit'.
 *
 * Two rule shapes are shown below:
 *   - a CONTENT rule (banned phrase) must keep running on edits, or editing becomes a
 *     way to smuggle content past you, so it does NOT gate on $context.
 *   - a "how often" rule (a cooldown / flood cap) is create-time only, or a member who
 *     hit the cap can no longer edit the posts they already published.
 *
 * Docs: developer-guide/41-extending-cookbook.md (Recipe 4)
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'buddynext_safeguard_check',
	static function ( $result, int $user_id, string $content, string $link_url, string $context ) {
		// Never override a block another safeguard already returned.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// A CONTENT rule: runs on create AND edit. Swap the phrase for your own list.
		if ( false !== stripos( $content, 'buy-now-cheap.example' ) ) {
			return new WP_Error(
				'bnx_snippet_blocked_link',
				__( 'That link is not allowed here.', 'bnx-snippet' )
			);
		}

		// A "how often" rule: create-time only. On an edit, stand down.
		if ( 'create' === $context && bnx_snippet_user_over_hourly_cap( $user_id ) ) {
			return new WP_Error(
				'bnx_snippet_flood',
				__( 'You are posting too quickly. Please try again shortly.', 'bnx-snippet' )
			);
		}

		return true;
	},
	10,
	5
);

/**
 * Demo cooldown check: at most 10 submissions per rolling hour, counted in a
 * per-user transient. Replace with your own throttle. Returns true when the member
 * is over the cap.
 *
 * @param int $user_id Submitting member.
 * @return bool
 */
function bnx_snippet_user_over_hourly_cap( int $user_id ): bool {
	if ( $user_id <= 0 ) {
		return false;
	}
	$key   = 'bnx_snippet_rate_' . $user_id;
	$count = (int) get_transient( $key );
	if ( $count >= 10 ) {
		return true;
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	return false;
}
