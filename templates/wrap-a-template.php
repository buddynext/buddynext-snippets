<?php
/**
 * Plugin Name: BuddyNext Snippet - Inject markup around a template (no copy)
 * Description: Adds markup after a specific BuddyNext template without overriding it.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.0.4
 *
 * If you only need to add markup around a template - not rewrite its body - you
 * do NOT have to copy it into your theme. Every template render fires two
 * actions (from includes/Core/TemplateLoader.php):
 *
 *   do_action( 'buddynext_before_template', string $path, string $relative );
 *   do_action( 'buddynext_after_template',  string $path, string $relative );
 *
 * $relative is the template's path under templates/ (e.g. 'feed/home.php',
 * 'profile/view.php', 'spaces/home.php'), so match on it to target one surface.
 * This example adds a notice after the activity feed; escape everything you echo.
 *
 * Docs: developer-guide/49-child-theme-template-overrides.md (wrap-hook alternative)
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'buddynext_after_template',
	static function ( string $path, string $relative ): void {
		if ( 'feed/home.php' !== $relative ) {
			return;
		}
		echo '<div class="bn-card bnx-feed-note" style="margin-top:var(--bn-s4,1rem)">';
		echo esc_html__( 'Injected after the activity feed by a snippet - no template copy needed.', 'bnx-snippet' );
		echo '</div>';
	},
	10,
	2
);
