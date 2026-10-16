<?php
/**
 * Plugin Name: BuddyNext Snippet - Show WP Sell Services in BuddyNext
 * Description: Posts a feed card when a member's service goes live and when a buyer's review is approved, and adds a Services tab to member profiles. Orders are never posted.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.2.1+, WP Sell Services 1.8+
 * Tested up to: BuddyNext 1.2.5, WP Sell Services 1.8.0
 *
 * WHEN YOU WANT THIS
 *
 * You run BuddyNext and WP Sell Services on one site and want the marketplace to
 * show up in the community: members see new services in the feed, see reviews as
 * they are approved, and find a member's services on their profile. A built-in
 * bridge is planned; this covers the gap until then. It is built the same way as
 * integrations/bridge-your-plugin.php.
 *
 * WHAT IT DOES
 *
 *   1. A service goes live (published by the seller, approved from moderation,
 *      saved through REST or the wizard): a "listed a new service" card is posted
 *      to the site feed as the seller. Unpublished, trashed or given a password:
 *      the service card AND its review cards are hidden and kept; public again:
 *      the same cards come back with their reactions and comments. Deleted for
 *      good: all of them are removed. A password-protected service never gets a card.
 *   2. A review becomes public (posted when moderation is off, or approved from
 *      the moderation queue): a "reviewed a service" card is posted as the
 *      reviewer, with the star rating. If a review is rejected, its card is removed.
 *      The service page already shows the reviewer's name, so the card makes
 *      nothing public that was not public already.
 *   3. A Services tab on member profiles listing that member's live services,
 *      12 per page. The tab only appears for members who have a live service.
 *   4. A switch under BuddyNext > Integrations ("WP Sell Services") so the site
 *      owner can turn the feed cards or the profile tab off.
 *
 * WHAT IT DELIBERATELY DOES NOT DO
 *
 *   - Orders are never posted. A purchase is between a buyer and a seller; putting
 *     "X bought Y" in a public feed would publish who buys what. Do not add it.
 *   - No cards for services or reviews that existed before you installed this.
 *     It reacts to new events only.
 *   - No space feeds: cards go to the site feed (space id 0).
 *   - WP Sell Services has no "review deleted" event, so a review deleted on its
 *     own (not through rejection) keeps its card until its service is deleted.
 *
 * CHANGE THESE IF YOU LIKE
 *
 *   BNX_WPSS_PER_PAGE   services per page on the profile tab   (marked below)
 *   The card verbs and the tab label are normal translatable strings.
 *
 * HOW IT WAS CHECKED (2026-10-16)
 *
 * Installed as an mu-plugin on a Docker test site: WordPress 7.1, BuddyNext 1.2.5
 * on its own (BuddyNext Pro, Jetonomy and WPMediaVerse deactivated), WP Sell
 * Services 1.8.0 free. Events fired for real through WordPress and WP Sell
 * Services; cards read back from wp_bn_posts:
 *
 *   - service saved as pending (moderation on): no card
 *   - approved to publish: 1 card, posted as the seller
 *   - edited while live: still 1 card (no duplicate)
 *   - trashed, then set to draft: the card is hidden and kept (status draft)
 *   - published again: the SAME card is back (same id)
 *   - deleted for good: card removed
 *   - review created while pending: no card
 *   - approved with WP Sell Services' own ReviewService::moderate(): 1 card,
 *     posted as the reviewer, with the star rating
 *   - rejected: card removed
 *   - service with an approved review set to draft, trashed, or given a password
 *     while live: the service card and the review card are both hidden; made
 *     public again: both come back with the same ids
 *   - a new password-protected service, and a review approved on it: no cards
 *   - service deleted for good: its service card and review card are removed
 *   - feed switch off under Integrations, service published: no card
 *   - 13 services published in a burst: 13 cards (the rate-limit exemption
 *     works), and the seller's own text post right after is still refused as
 *     "posting too quickly" (the exemption does not leak)
 *   - profile tab, as an admin and as a regular member: page 1 shows 12 services
 *     and Next, ?svc_page=2 shows the 13th and Previous, ?svc_page=9 says there
 *     are no more with a link back; a member with no live services gets no tab
 *   - browser (Chrome): the tab and the feed card render through BuddyNext's own
 *     styles; at 390px the grid is one column with no sideways scroll
 *
 * Not tested: WP Sell Services Pro, BuddyNext Pro active at the same time, and
 * dark mode in a browser (colours come only from BuddyNext's own tokens).
 *
 * Docs: developer-guide/33-hooks-pro-and-integration.md, 47-nav-api.md
 */

defined( 'ABSPATH' ) || exit;

// Services shown per page on the profile tab. CHANGE if you like.
const BNX_WPSS_PER_PAGE = 12;


add_action(
	'buddynext_load_bridges',
	static function (): void {

		// WP Sell Services must be active. If it is not, this file does nothing.
		if ( ! defined( 'WPSS_VERSION' ) || ! class_exists( '\BuddyNext\Feed\IntegrationActivity' ) ) {
			return;
		}

		// ── The owner's switch under BuddyNext > Integrations ───────────────
		add_filter(
			'buddynext_integrations',
			static function ( array $items ): array {
				$items['wpss'] = array(
					'label'      => __( 'WP Sell Services', 'bnx-snippet' ),
					'version'    => WPSS_VERSION,
					'has_feed'   => true,
					'has_nav'    => true,
					'has_search' => false,
				);
				return $items;
			}
		);

		// ── Two card types of our own ──────────────────────────────────────
		// Not 'listing': Listora's bridge renders 'listing' cards, and a second
		// renderer on that type would take over Listora's cards.
		add_filter(
			'buddynext_feed_allowed_post_types',
			static function ( array $types ): array {
				$types[] = 'wpss_service';
				$types[] = 'wpss_review';
				return $types;
			}
		);

		// ── 1. A service goes live, or stops being live ─────────────────────
		// The post status change is the one signal every "make it live" path goes
		// through (editor, moderation approval, REST, wizard).
		add_action(
			'transition_post_status',
			static function ( string $new_status, string $old_status, \WP_Post $post ): void {
				if ( 'wpss_service' !== $post->post_type ) {
					return;
				}
				$url = \BuddyNext\Feed\IntegrationActivity::published_permalink( $post );
				if ( '' === $url ) {
					return;
				}

				// Not visible to everyone (draft, pending, private, trash, or a
				// password on it): hide the service card AND its review cards, but
				// keep them, so making it public again brings the same cards back.
				// Checked on every save, so adding a password to a live service
				// hides its cards straight away.
				if ( ! bnx_wpss_is_public( $post ) ) {
					\BuddyNext\Feed\IntegrationActivity::withdraw_by_meta( 'wpss_service', 'wpss_service_id', (int) $post->ID );
					\BuddyNext\Feed\IntegrationActivity::withdraw_by_meta( 'wpss_review', 'wpss_service_id', (int) $post->ID );
					return;
				}

				if ( ! buddynext_integration_enabled( 'wpss', 'feed' ) ) {
					return;
				}
				// Back from hidden: bring the same cards back, with their dates,
				// reactions and comments. Only post a new card if there was none.
				\BuddyNext\Feed\IntegrationActivity::restore_by_meta( 'wpss_review', 'wpss_service_id', (int) $post->ID );
				if ( \BuddyNext\Feed\IntegrationActivity::restore( $url, 'wpss_service' ) ) {
					return;
				}
				// publish() is idempotent on (type, url): saving a live service again
				// does not post a second card.
				bnx_wpss_publish_card(
					(int) $post->post_author,
					__( 'listed a new service', 'bnx-snippet' ),
					$url,
					get_the_title( $post ),
					'wpss_service',
					wp_trim_words( wp_strip_all_tags( (string) ( $post->post_excerpt ? $post->post_excerpt : $post->post_content ) ), 30 ),
					(string) get_the_post_thumbnail_url( $post, 'large' ),
					(int) $post->ID
				);
			},
			10,
			3
		);

		// Deleted for good: remove the service card and every review card for it.
		add_action(
			'before_delete_post',
			static function ( int $post_id ): void {
				if ( 'wpss_service' === get_post_type( $post_id ) ) {
					\BuddyNext\Feed\IntegrationActivity::remove_by_meta( 'wpss_service', 'wpss_service_id', $post_id );
					\BuddyNext\Feed\IntegrationActivity::remove_by_meta( 'wpss_review', 'wpss_service_id', $post_id );
				}
			}
		);

		// ── 2. A review becomes public, or is rejected ──────────────────────
		add_action(
			'wpss_review_created',
			static function ( $review_id ): void {
				bnx_wpss_post_review_card( (int) $review_id );
			},
			20,
			1
		);
		add_action(
			'wpss_review_moderated',
			static function ( $review_id, $new_status ): void {
				if ( 'approved' === $new_status ) {
					bnx_wpss_post_review_card( (int) $review_id );
				} elseif ( 'rejected' === $new_status ) {
					$review = bnx_wpss_get_review( (int) $review_id );
					if ( $review ) {
						\BuddyNext\Feed\IntegrationActivity::remove( bnx_wpss_review_url( $review ), 'wpss_review' );
					}
				}
			},
			20,
			2
		);

		// ── Render both cards through the shared renderer ──────────────────
		add_filter(
			'buddynext_render_post_body_wpss_service',
			static function ( $html, $args ): string {
				return \BuddyNext\Feed\IntegrationActivity::render_bridge_card( (array) $args, 'store', __( 'Services', 'bnx-snippet' ) );
			},
			10,
			2
		);
		add_filter(
			'buddynext_render_post_body_wpss_review',
			static function ( $html, $args ): string {
				return \BuddyNext\Feed\IntegrationActivity::render_bridge_card( (array) $args, 'star', __( 'Service review', 'bnx-snippet' ) );
			},
			10,
			2
		);

		// ── 3. A Services tab on member profiles ───────────────────────────
		add_action(
			'buddynext_register_nav',
			static function ( \BuddyNext\Nav\NavRegistry $registry ): void {
				if ( ! buddynext_integration_enabled( 'wpss', 'nav' ) ) {
					return;
				}

				$registry->register(
					array(
						// The id IS the URL segment: BuddyNext opens /members/{name}/services/
						// by matching "services" to a tab id. Keep the two identical.
						'id'        => 'services',
						'surface'   => 'profile',
						'layer'     => 'primary',
						'label'     => __( 'Services', 'bnx-snippet' ),
						// No icon: profile tabs are text-only in BuddyNext.
						'priority'  => 70,
						// Only for members who have at least one live service.
						'condition' => static fn( \BuddyNext\Nav\NavContext $c ): bool => bnx_wpss_count_services( (int) $c->subject_id ) > 0,
						'count'     => static fn( \BuddyNext\Nav\NavContext $c ): int => bnx_wpss_count_services( (int) $c->subject_id ),
						'url'       => static fn( \BuddyNext\Nav\NavContext $c ): string =>
							trailingslashit( \BuddyNext\Core\PageRouter::profile_url( (int) $c->subject_id ) ) . 'services/',
						'render'    => static function ( \BuddyNext\Nav\NavContext $c ): void {
							bnx_wpss_render_services_panel( (int) $c->subject_id );
						},
					)
				);
			}
		);
	}
);

/**
 * Live services a member has, from one COUNT query (cached per request).
 *
 * @param int $user_id Member.
 * @return int
 */
function bnx_wpss_count_services( int $user_id ): int {
	static $counts = array();
	if ( $user_id <= 0 ) {
		return 0;
	}
	if ( ! isset( $counts[ $user_id ] ) ) {
		$counts[ $user_id ] = (int) count_user_posts( $user_id, 'wpss_service', true );
	}
	return $counts[ $user_id ];
}

/**
 * Post one card, past the member posting rate limit.
 *
 * BuddyNext limits how many posts a member makes per minute. A card here is not
 * the member typing: it follows something WP Sell Services already did, and an
 * admin approving a seller's services in bulk would otherwise lose every card
 * after the limit, silently. The exemption is added for this one write only and
 * removed straight after, so the member's own posting stays limited.
 *
 * @param int    $member_id Card author.
 * @param string $verb      What happened.
 * @param string $url       Page the card links to (also its identity).
 * @param string $title     Card title.
 * @param string $type      Card type.
 * @param string $excerpt   Line under the title.
 * @param string $image     Cover image URL, or ''.
 * @param int    $service_id The service the card belongs to (stored on the card,
 *                           so all of a service's cards can be hidden together).
 * @return void
 */
function bnx_wpss_publish_card( int $member_id, string $verb, string $url, string $title, string $type, string $excerpt, string $image, int $service_id ): void {
	$exempt = static fn(): bool => true;
	add_filter( 'buddynext_rate_limit_exempt', $exempt, 99 );
	try {
		\BuddyNext\Feed\IntegrationActivity::publish(
			$member_id,
			$verb,
			$url,
			$title,
			$type,
			$excerpt,
			0,
			array(
				'image'           => $image,
				'wpss_service_id' => $service_id,
			)
		);
	} finally {
		remove_filter( 'buddynext_rate_limit_exempt', $exempt, 99 );
	}
}

/**
 * Load a review through WP Sell Services' own service.
 *
 * @param int $review_id Review ID.
 * @return object|null
 */
function bnx_wpss_get_review( int $review_id ) {
	if ( $review_id <= 0 || ! class_exists( '\WPSellServices\Services\ReviewService' ) ) {
		return null;
	}
	return ( new \WPSellServices\Services\ReviewService() )->get( $review_id );
}

/**
 * The unique address a review's card points at (the service page, at the review).
 *
 * @param object $review Review.
 * @return string
 */
function bnx_wpss_review_url( $review ): string {
	return get_permalink( (int) $review->service_id ) . '#wpss-review-' . (int) $review->id;
}

/**
 * Post a review card when the review is approved and its service is live.
 *
 * @param int $review_id Review ID.
 * @return void
 */
function bnx_wpss_post_review_card( int $review_id ): void {
	if ( ! function_exists( 'buddynext_integration_enabled' ) || ! buddynext_integration_enabled( 'wpss', 'feed' ) ) {
		return;
	}
	$review = bnx_wpss_get_review( $review_id );
	// Held for moderation, or rejected: nothing is public yet.
	if ( ! $review || 'approved' !== $review->status ) {
		return;
	}
	// Never point a public card at a service that is not public itself.
	$service = get_post( (int) $review->service_id );
	if ( ! $service || ! bnx_wpss_is_public( $service ) ) {
		return;
	}

	bnx_wpss_publish_card(
		(int) $review->reviewer_id,
		/* translators: %d: star rating from 1 to 5. */
		sprintf( __( 'reviewed a service: %d out of 5 stars', 'bnx-snippet' ), (int) $review->rating ),
		bnx_wpss_review_url( $review ),
		get_the_title( (int) $review->service_id ),
		'wpss_review',
		wp_trim_words( wp_strip_all_tags( (string) $review->content ), 30 ),
		(string) get_the_post_thumbnail_url( (int) $review->service_id, 'large' ),
		(int) $review->service_id
	);
}

/**
 * Whether a service is open to everyone: published and not password-protected.
 *
 * A card in the feed is public, so it must never carry the title or text of a
 * service that only some people may see.
 *
 * @param \WP_Post $post Service.
 * @return bool
 */
function bnx_wpss_is_public( \WP_Post $post ): bool {
	return 'publish' === $post->post_status && '' === (string) $post->post_password;
}

/**
 * The Services tab panel: the member's live services, paginated.
 *
 * Uses its own page parameter (svc_page), not WordPress's "paged", so no
 * canonical redirect can rewrite the link into a URL BuddyNext does not route.
 *
 * @param int $user_id Profile owner.
 * @return void
 */
function bnx_wpss_render_services_panel( int $user_id ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page number.
	$page  = isset( $_GET['svc_page'] ) ? max( 1, absint( wp_unslash( $_GET['svc_page'] ) ) ) : 1;
	$query = new \WP_Query(
		array(
			'post_type'      => 'wpss_service',
			'post_status'    => 'publish',
			'author'         => $user_id,
			'posts_per_page' => BNX_WPSS_PER_PAGE,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( ! $query->have_posts() ) {
		if ( $page > 1 ) {
			// An old or edited link past the last page: point back to the start.
			printf(
				'<div class="bn-card"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( 'There are no more services.', 'bnx-snippet' ),
				esc_url( remove_query_arg( 'svc_page' ) ),
				esc_html__( 'Back to the first page', 'bnx-snippet' )
			);
			return;
		}
		echo '<div class="bn-card"><p>' . esc_html__( 'No services yet.', 'bnx-snippet' ) . '</p></div>';
		return;
	}

	// One query for every thumbnail on the page, not one per service.
	update_post_thumbnail_cache( $query );
	?>
	<style>
		.bnx-wpss-grid { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; grid-template-columns: repeat( auto-fill, minmax( 15rem, 1fr ) ); }
		.bnx-wpss-item { background: var( --bn-surface ); border: 1px solid var( --bn-border ); border-radius: var( --bn-radius-base ); overflow: hidden; }
		.bnx-wpss-item a { display: block; color: var( --bn-ink ); text-decoration: none; }
		.bnx-wpss-item a:focus-visible { outline: 2px solid var( --bn-accent ); outline-offset: 2px; }
		.bnx-wpss-item img { display: block; width: 100%; aspect-ratio: 16 / 9; object-fit: cover; }
		.bnx-wpss-item__body { padding: 0.75rem 1rem; }
		.bnx-wpss-item__title { margin: 0 0 0.25rem; font-size: var( --bn-text-base ); }
		.bnx-wpss-item__price { margin: 0; color: var( --bn-ink-3 ); font-size: var( --bn-text-sm ); }
		.bnx-wpss-pager { display: flex; justify-content: space-between; gap: 1rem; margin-block-start: 1rem; }
		@media ( max-width: 480px ) { .bnx-wpss-grid { grid-template-columns: 1fr; } }
	</style>
	<ul class="bnx-wpss-grid">
		<?php
		while ( $query->have_posts() ) :
			$query->the_post();
			$price = (float) get_post_meta( get_the_ID(), '_wpss_starting_price', true );
			?>
			<li class="bnx-wpss-item">
				<a href="<?php the_permalink(); ?>">
					<?php the_post_thumbnail( 'medium_large', array( 'alt' => '' ) ); ?>
					<div class="bnx-wpss-item__body">
						<h3 class="bnx-wpss-item__title"><?php the_title(); ?></h3>
						<?php if ( $price > 0 && function_exists( 'wpss_catalog_price_html' ) ) : ?>
							<p class="bnx-wpss-item__price">
								<?php
								/* translators: %s: starting price. */
								echo wp_kses_post( sprintf( __( 'From %s', 'bnx-snippet' ), wpss_catalog_price_html( $price ) ) );
								?>
							</p>
						<?php endif; ?>
					</div>
				</a>
			</li>
		<?php endwhile; ?>
	</ul>
	<?php
	wp_reset_postdata();

	if ( $query->max_num_pages > 1 ) :
		?>
		<nav class="bnx-wpss-pager" aria-label="<?php esc_attr_e( 'Services pages', 'bnx-snippet' ); ?>">
			<span>
				<?php if ( $page > 1 ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'svc_page', $page - 1 ) ); ?>" rel="prev"><?php esc_html_e( 'Previous', 'bnx-snippet' ); ?></a>
				<?php endif; ?>
			</span>
			<span>
				<?php if ( $page < $query->max_num_pages ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'svc_page', $page + 1 ) ); ?>" rel="next"><?php esc_html_e( 'Next', 'bnx-snippet' ); ?></a>
				<?php endif; ?>
			</span>
		</nav>
		<?php
	endif;
}
