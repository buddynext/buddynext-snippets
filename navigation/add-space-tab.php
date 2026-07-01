<?php
/**
 * Plugin Name: BuddyNext Snippet - Add a space tab
 * Description: Adds a "Leaderboard" tab (with its own panel) to every space.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 *
 * A space tab uses the same Nav registry as a profile tab, with
 * `surface => 'space'`. Build the clean tab URL against the live space in the
 * lazy `url` callable, and draw the panel in `render` (PanelRenderer renders
 * only the active space panel). Gate visibility with `condition` - here the
 * viewer must be at least a member of the space.
 *
 * Docs: developer-guide/47-nav-api.md (Recipe: add a space tab)
 * Core examples: includes/Nav/Providers/SpaceNav.php
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'buddynext_register_nav',
	static function ( \BuddyNext\Nav\NavRegistry $registry ): void {
		$registry->register(
			array(
				'id'        => 'leaderboard',
				'surface'   => 'space',
				'layer'     => 'primary',
				'label'     => __( 'Leaderboard', 'bnx-snippet' ),
				'icon'      => 'list',
				'priority'  => 45,
				'condition' => static fn( \BuddyNext\Nav\NavContext $c ): bool => $c->role_at_least( 'member' ),
				'url'       => static function ( \BuddyNext\Nav\NavContext $c ): string {
					return trailingslashit( \BuddyNext\Core\PageRouter::space_url( $c->subject_id ) ) . 'leaderboard/';
				},
				'render'    => static function ( \BuddyNext\Nav\NavContext $c ): void {
					echo '<div class="bn-card bnx-leaderboard-panel">';
					printf(
						/* translators: %d: space ID. */
						esc_html__( 'This is the Leaderboard tab for space #%d. Replace this panel with your own content.', 'bnx-snippet' ),
						(int) $c->subject_id
					);
					echo '</div>';
				},
			)
		);
	}
);
