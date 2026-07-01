<?php
/**
 * Plugin Name: BuddyNext Snippet - Add a left-rail menu item
 * Description: Adds a "Leaderboard" link to the persistent left-rail menu.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 *
 * The left rail is the persistent vertical column in the hub shell. It is NOT
 * part of the Nav registry - add links with the `buddynext_rail_items` filter.
 * Each item is a plain array. Register at the default priority so the site
 * owner's admin Navigation overrides (priority 20) still win.
 *
 * `group => 'you'` drops the item into the personal "You" section at the foot
 * of the rail; omit it for the top community group. `icon` is a BuddyNext icon
 * slug from assets/icons/ (e.g. list, bookmark), never a raw <svg>.
 *
 * Docs: developer-guide/47-nav-api.md (Recipe: add a left-rail menu item)
 * Core example: includes/Bridges/JetonomyBridge.php (inject_discussions_nav_item)
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'buddynext_rail_items',
	static function ( array $items ): array {
		$items[] = array(
			'key'   => 'bnx-leaderboard',
			'label' => __( 'Leaderboard', 'bnx-snippet' ),
			'url'   => home_url( '/leaderboard/' ),
			'icon'  => 'list',
			'show'  => true,
			'order' => 60,
		);

		return $items;
	}
);
