<?php
/**
 * Plugin Name: BuddyNext Snippet - Add a member-profile tab
 * Description: Adds a "Notes" tab (with its own panel) to every member profile.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.0.4
 *
 * A profile tab is one entry on the Nav registry. Register it on the
 * `buddynext_register_nav` action: give it a lazy `url` (the clean tab URL) and
 * a `render` callable - PanelRenderer draws that callable's output when the tab
 * is the active one, so the surface server-renders only the panel being viewed.
 *
 * Docs: developer-guide/47-nav-api.md (Recipe: add a member-profile tab)
 * Canonical core example: includes/Profile/GamificationAchievements.php
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'buddynext_register_nav',
	static function ( \BuddyNext\Nav\NavRegistry $registry ): void {
		$registry->register(
			array(
				'id'       => 'notes',
				'surface'  => 'profile',
				'layer'    => 'primary',
				'label'    => __( 'Notes', 'bnx-snippet' ),
				'icon'     => 'file-text',
				'priority' => 65,
				'url'      => static function ( \BuddyNext\Nav\NavContext $c ): string {
					return trailingslashit( \BuddyNext\Core\PageRouter::profile_url( $c->subject_id ) ) . 'notes/';
				},
				'render'   => static function ( \BuddyNext\Nav\NavContext $c ): void {
					echo '<div class="bn-card bnx-notes-panel">';
					printf(
						/* translators: %d: member ID. */
						esc_html__( 'This is the Notes tab for member #%d. Replace this panel with your own content.', 'bnx-snippet' ),
						(int) $c->subject_id
					);
					echo '</div>';
				},
			)
		);
	}
);
