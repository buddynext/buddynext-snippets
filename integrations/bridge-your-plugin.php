<?php
/**
 * Plugin Name: BuddyNext Snippet - Bridge your plugin into BuddyNext
 * Description: A complete integration: your plugin's activity in the feed (member or space), a profile tab, a space tab, an on/off switch the site owner controls, and clean removal.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.1+
 * Tested up to: BuddyNext 1.1.1
 *
 * This is the whole pattern, not a fragment. BuddyNext's own bridges - WPMediaVerse,
 * Jetonomy, Gamification, Career Board, Learnomy, Listora, Eventonomy, WB Member Blog -
 * are all built exactly like this, and this snippet is modelled on them.
 *
 * WHAT YOU GET
 *
 *   1. Your content posts a card into the activity feed when it is published
 *   2. The same card can go into a SPACE feed instead of the site feed
 *   3. The card looks like every other integration card, because it uses the shared renderer
 *   4. A tab on member profiles for your content
 *   5. A tab inside spaces for your content
 *   6. A switch under BuddyNext > Integrations so the site owner can turn any of it off
 *   7. The card disappears when your content is deleted
 *
 * THE RULE THAT MATTERS MOST
 *
 * Nothing here runs unless BuddyNext is active AND the site owner has left your
 * integration on. Every surface checks. If BuddyNext is deactivated tomorrow, this
 * file does nothing at all rather than fataling - which is why it hooks
 * `buddynext_load_bridges` and guards on a real BuddyNext symbol.
 *
 * REPLACE THESE
 *
 *   MYPLUGIN_VERSION        the constant that proves YOUR plugin is loaded
 *   myplugin                the integration key (lowercase, no spaces)
 *   myplugin_item_published your plugin's own hook
 *
 * Docs: developer-guide/33-hooks-pro-and-integration.md
 */

defined( 'ABSPATH' ) || exit;

/**
 * Boot on BuddyNext's bridge hook, never on plugins_loaded.
 *
 * BuddyNext fires this once its services are bound. Hooking anything earlier means
 * IntegrationActivity and the nav registry may not exist yet.
 */
add_action(
	'buddynext_load_bridges',
	static function (): void {

		// Self-guard. BuddyNext fired the hook, so it is here - but YOUR plugin
		// might not be. Guard on a symbol your plugin really defines.
		if ( ! defined( 'MYPLUGIN_VERSION' ) ) {
			return;
		}

		// ── 1. Declare the integration ──────────────────────────────────────
		// This is what puts your switch on BuddyNext > Integrations. Without it
		// the owner has no way to turn your surfaces off, and buddynext_integration_enabled()
		// has nothing to answer about.
		add_filter(
			'buddynext_integrations',
			static function ( array $items ): array {
				$items['myplugin'] = array(
					'label'      => __( 'My Plugin', 'myplugin' ),
					'version'    => defined( 'MYPLUGIN_VERSION' ) ? MYPLUGIN_VERSION : null,
					'has_feed'   => true,   // you publish feed cards
					'has_nav'    => true,   // you add a profile and/or space tab
					'has_search' => false,  // set true only if you index into BN search
				);
				return $items;
			}
		);

		// ── 2. Publish a card when your content is created ──────────────────
		add_action(
			'myplugin_item_published',
			static function ( int $item_id, int $author_id, int $space_id = 0 ): void {

				// Gate on the owner's switch. Do this on EVERY surface, every time.
				if ( ! buddynext_integration_enabled( 'myplugin', 'feed' ) ) {
					return;
				}

				$permalink = get_permalink( $item_id );
				if ( ! $permalink ) {
					return;
				}

				\BuddyNext\Feed\IntegrationActivity::publish(
					$author_id,
					// The VERB. This is what the card says happened - "posted a new
					// listing", "completed a course". Do not leave it empty: two cards
					// from the same plugin with no verb read as duplicates.
					__( 'published a new item', 'myplugin' ),
					$permalink,
					get_the_title( $item_id ),
					// A REAL type, never the default 'link'. Allowed values include:
					// discussion, job, resume, event, listing, course, badge, media,
					// activity, announcement. Pick the closest one - Explore and the
					// feed filters classify cards by it, and 'link' means "unclassified".
					'listing',
					// Excerpt - one or two lines under the title. Optional, but a card
					// with a title and nothing else is the least useful card you can ship.
					wp_trim_words( (string) get_post_field( 'post_excerpt', $item_id ), 30 ),
					// SPACE ID. Pass 0 for the site-wide feed, or a space id to post the
					// card INTO that space's feed instead. Spaces are the better home for
					// most integrations: the content reaches the people who asked for it.
					$space_id,
					// Meta travels with the card. 'image' gives it cover art; without one
					// the card renders its compact text form with no empty box reserved.
					array(
						'image' => (string) get_the_post_thumbnail_url( $item_id, 'large' ),
					)
				);

				// publish() is idempotent on (type, url): if your hook fires twice, the
				// second call returns 0 rather than creating a duplicate card. You do not
				// need to guard against double-firing yourself.
			},
			10,
			3
		);

		// ── 3. Render the card ──────────────────────────────────────────────
		// One filter per type, and ALWAYS through the shared renderer. That is what
		// makes your card look like every other integration card instead of a
		// one-off. Do not hand-roll the markup.
		add_filter(
			'buddynext_render_post_body_listing',
			static function ( $html, $args ): string {
				return \BuddyNext\Feed\IntegrationActivity::render_bridge_card(
					$args,
					'list',                               // an icon slug from assets/icons/
					__( 'My Plugin', 'myplugin' )        // the source label on the card
				);
			},
			10,
			2
		);

		// ── 4. Remove the card when the content goes ────────────────────────
		// Pass the SAME type you published with, or the card is orphaned - it stays
		// in the feed pointing at a page that 404s.
		add_action(
			'myplugin_item_deleted',
			static function ( string $permalink ): void {
				\BuddyNext\Feed\IntegrationActivity::remove( $permalink, 'listing' );
			}
		);

		// ── 5. A tab on member profiles, and one inside spaces ──────────────
		add_action(
			'buddynext_register_nav',
			static function ( \BuddyNext\Nav\NavRegistry $registry ): void {

				// Same switch, different aspect.
				if ( ! buddynext_integration_enabled( 'myplugin', 'nav' ) ) {
					return;
				}

				// PROFILE tab - no 'icon' key. Profile tabs are text-only in
				// BuddyNext; an icon here makes yours the only tab that looks different.
				$registry->register(
					array(
						'id'       => 'myplugin-items',
						'surface'  => 'profile',
						'layer'    => 'primary',
						'label'    => __( 'Items', 'myplugin' ),
						'priority' => 70,
						'url'      => static fn( \BuddyNext\Nav\NavContext $c ): string =>
							trailingslashit( \BuddyNext\Core\PageRouter::profile_url( $c->subject_id ) ) . 'items/',
						'render'   => static function ( \BuddyNext\Nav\NavContext $c ): void {
							echo '<div class="bn-card">';
							printf(
								esc_html__( 'Items published by member #%d. Render your own list here.', 'myplugin' ),
								(int) $c->subject_id
							);
							echo '</div>';
						},
					)
				);

				// SPACE tab - an icon IS correct here. Space tabs carry icons in
				// BuddyNext; profile tabs do not. The two surfaces genuinely differ.
				$registry->register(
					array(
						'id'        => 'myplugin-space-items',
						'surface'   => 'space',
						'layer'     => 'primary',
						'label'     => __( 'Items', 'myplugin' ),
						'icon'      => 'list',
						'priority'  => 50,
						// Only members of the space see it.
						'condition' => static fn( \BuddyNext\Nav\NavContext $c ): bool =>
							$c->role_at_least( 'member' ),
						'url'       => static fn( \BuddyNext\Nav\NavContext $c ): string =>
							trailingslashit( \BuddyNext\Core\PageRouter::space_url( $c->subject_id ) ) . 'items/',
						'render'    => static function ( \BuddyNext\Nav\NavContext $c ): void {
							echo '<div class="bn-card">';
							printf(
								esc_html__( 'Items in space #%d. Render your own list here.', 'myplugin' ),
								(int) $c->subject_id
							);
							echo '</div>';
						},
					)
				);
			}
		);
	}
);
