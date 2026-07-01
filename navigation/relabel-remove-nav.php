<?php
/**
 * Plugin Name: BuddyNext Snippet - Relabel or remove nav tabs
 * Description: Changes existing member-profile tabs - relabel About to Bio, remove Replies.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 *
 * To change tabs the core (or another addon) already registered, hook the
 * `buddynext_nav_items` filter. It runs per surface and hands you the raw
 * registration arrays plus the NavContext. Use the helpers so the mutation
 * stays valid:
 *
 *   buddynext_nav_set( $items, $id, $changes )   relabel / re-gate / retarget
 *   buddynext_nav_remove( $items, $ids )         drop one id or an array of ids
 *   buddynext_nav_move( $items, $id, $anchor )   set before/after/priority
 *
 * VERIFIED in this snippet: relabel (a count-less tab) and remove. Two caveats
 * found by testing on a live site:
 *  - Relabeling a tab that carries a COUNT badge (e.g. Likes) does not visibly
 *    change its label - target a count-less tab such as About.
 *  - Primary-tab ORDER is driven by each provider's registration, so a
 *    `buddynext_nav_move()` from this filter is a best-effort nudge, not a hard
 *    reorder. If you need a specific position, register your own tab with the
 *    `priority` you want rather than trying to move a core tab past others.
 *
 * The admin Navigation overrides also hook this filter at priority 20, so a
 * site owner can still override your changes - register at the default priority.
 *
 * Docs: developer-guide/47-nav-api.md (Recipe: reorder, remove, or modify existing items)
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'buddynext_nav_items',
	static function ( array $items, \BuddyNext\Nav\NavContext $ctx ): array {
		if ( 'profile' !== $ctx->surface ) {
			return $items;
		}

		// Relabel a count-less tab.
		$items = buddynext_nav_set( $items, 'about', array( 'label' => __( 'Bio', 'bnx-snippet' ) ) );

		// Remove a tab.
		$items = buddynext_nav_remove( $items, 'replies' );

		return $items;
	},
	10,
	2
);
