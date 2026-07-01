<?php
/**
 * Plugin Name: BuddyNext Snippet - Reorder, relabel, or remove nav tabs
 * Description: Changes existing member-profile tabs - move About to the front, relabel it, remove Replies.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.0.4
 *
 * To change tabs the core (or another addon) already registered, hook the
 * `buddynext_nav_items` filter. It runs per surface and hands you the raw
 * registration arrays plus the NavContext. Use the helpers so the mutation
 * stays valid:
 *
 *   buddynext_nav_set( $items, $id, $changes )   relabel / re-gate / retarget
 *   buddynext_nav_move( $items, $id, $anchor )   reorder: ['priority'=>int] (or before/after)
 *   buddynext_nav_remove( $items, $ids )         drop one id or an array of ids
 *
 * All three are VERIFIED working live. `priority` is the reliable reorder lever
 * (lower = earlier). ONE precedence rule to know: if the SITE OWNER has already
 * relabeled or reordered a tab in the admin Navigation screen, that saved
 * setting wins for that tab (admin overrides apply at priority 20 - the intended
 * precedence). Your change is honoured for every tab the owner has not
 * customised. So if a nav_set/nav_move seems to do nothing, the owner has
 * almost certainly pinned that tab in the admin.
 *
 * The admin overrides hook this same filter at priority 20, so register at the
 * default priority (10) and the owner still wins.
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

		// Reorder: move the About tab to the front (priority 1, lower = earlier).
		$items = buddynext_nav_move( $items, 'about', array( 'priority' => 1 ) );

		// Relabel it.
		$items = buddynext_nav_set( $items, 'about', array( 'label' => __( 'Bio', 'bnx-snippet' ) ) );

		// Remove a tab.
		$items = buddynext_nav_remove( $items, 'replies' );

		return $items;
	},
	10,
	2
);
