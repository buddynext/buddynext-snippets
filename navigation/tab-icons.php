<?php
/**
 * Plugin Name: BuddyNext Snippet - Icons on profile and space tabs
 * Description: Puts an icon on each profile and space tab and stacks it above a smaller label, for a compact tab bar.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.1+
 * Tested up to: BuddyNext 1.2.1
 *
 * Tabs already render an item's `icon` before its label, so this is two
 * small pieces:
 *
 * 1. `buddynext_nav_items` gives each tab an icon by its id. The filter runs
 *    per surface; `$ctx->surface` is 'profile' or 'space'. Icons come from
 *    BuddyNext's own set in assets/icons/ (use the file name without .svg).
 *    A tab you don't list keeps no icon, and a tab that already has one keeps it.
 * 2. A little CSS stacks the icon above the label. For icon-only tabs, set
 *    BNX_TAB_ICONS_ONLY to true: the label is hidden visually, and screen
 *    readers still get it from the tab's aria-label.
 *
 * Docs: developer-guide/47-nav-api.md
 */

defined( 'ABSPATH' ) || exit;

const BNX_TAB_ICONS_ONLY = false;

add_filter(
	'buddynext_nav_items',
	static function ( array $items, $ctx ): array {
		$icons = array(
			'profile' => array(
				'posts'       => 'activity',
				'about'       => 'user',
				'replies'     => 'reply',
				'media'       => 'image',
				'files'       => 'file-text',
				'likes'       => 'heart',
				'network'     => 'users',
				'connections' => 'users',
				'followers'   => 'user-plus',
				'following'   => 'user-check',
				'scheduled'   => 'clock',
				'pending'     => 'inbox',
				// Tabs added by integrations, when active.
				'discussions'  => 'message-square',
				'gamification' => 'award',
				'events'       => 'calendar',
				'portfolio'    => 'briefcase',
			),
			'space'   => array(
				'feed'       => 'activity',
				'about'      => 'info',
				'members'    => 'users',
				'subspaces'  => 'layers',
				'media'      => 'image',
				'files'      => 'file-text',
				'moderation' => 'shield',
				// Tabs added by integrations, when active.
				'discussions' => 'message-square',
				'events'      => 'calendar',
			),
		);
		$map = $icons[ $ctx->surface ] ?? array();
		foreach ( $items as &$item ) {
			$id = (string) ( $item['id'] ?? '' );
			if ( empty( $item['icon'] ) && isset( $map[ $id ] ) ) {
				$item['icon'] = $map[ $id ];
			}
		}
		unset( $item );
		return $items;
	},
	10,
	2
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$css = '
			[data-bn-nav][role="tablist"] .bn-tab { display: inline-grid; grid-template-columns: auto auto; justify-content: center; align-items: center; column-gap: var(--bn-s1); row-gap: var(--bn-s1); min-inline-size: 72px; }
			[data-bn-nav][role="tablist"] .bn-tab > svg { grid-column: 1 / -1; justify-self: center; inline-size: 20px; block-size: 20px; }
			[data-bn-nav][role="tablist"] .bn-tab .bn-tab__label { font-size: var(--bn-text-xs); }
		';
		if ( BNX_TAB_ICONS_ONLY ) {
			$css .= '
				[data-bn-nav][role="tablist"] .bn-tab:has(> svg) .bn-tab__label {
					position: absolute; inline-size: 1px; block-size: 1px; overflow: hidden;
					clip: rect(0 0 0 0); white-space: nowrap;
				}
				[data-bn-nav][role="tablist"] .bn-tab:has(> svg) { min-inline-size: 48px; }
			';
		}
		wp_add_inline_style( 'bn-base', $css );
	},
	20
);
