<?php
/**
 * Plugin Name: BuddyNext Snippet - Read BuddyNext data through the service layer
 * Description: Fetch posts correctly from your own code - gate for the viewer, then batch-hydrate in one query, in order.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.1.5+
 * Tested up to: BuddyNext 1.2.0
 *
 * Never query BuddyNext's tables directly. Reach its data through the service layer
 * (`buddynext_service( '<name>' )`), which owns caching, visibility and the shape of
 * the row. This example reads posts, the most common case, and shows the two calls
 * that matter (verified in includes/Feed/PostService.php):
 *
 *   - filter_visible( array $post_ids, int $viewer ): array   (:1700)
 *       Returns only the ids the viewer may see. A FETCH IS NOT A GATE - run this
 *       BEFORE hydrating, or you will hand a member content they cannot read.
 *   - get_many( array $post_ids ): array                      (:924)
 *       Hydrates a batch in ONE query, IN THE ORDER YOU ASKED. Looping get() over ids
 *       is one query per row - 20 round trips for what one IN() answers. Ids with no
 *       row are skipped, so the result may be shorter than the input.
 *
 * Docs: developer-guide/41-extending-cookbook.md (Recipe 8 and Recipe 15)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return hydrated post objects for the given ids that $viewer is allowed to see,
 * in the order requested, in a single query.
 *
 * @param int[] $post_ids Post ids, in the order you want them back.
 * @param int   $viewer   Viewer user id (0 = logged out).
 * @return array Hydrated post rows the viewer may see.
 */
function bnx_snippet_read_posts_for_viewer( array $post_ids, int $viewer ): array {
	if ( ! function_exists( 'buddynext_service' ) || empty( $post_ids ) ) {
		return array();
	}

	$posts = buddynext_service( 'post_service' );
	if ( ! $posts ) {
		return array();
	}

	// 1) Gate FIRST: keep only the ids this viewer may read, preserving order.
	$visible = $posts->filter_visible( $post_ids, $viewer );
	if ( empty( $visible ) ) {
		return array();
	}

	// 2) Hydrate the survivors in one query, in the order asked.
	return $posts->get_many( $visible );
}

/*
 * Example use — remove or adapt. Logs how many of a set of ids the current viewer
 * can actually see, proving the gate ran before the fetch.
 *
 * add_action( 'init', function () {
 *     $rows = bnx_snippet_read_posts_for_viewer( array( 1, 2, 3, 4, 5 ), get_current_user_id() );
 *     error_log( 'Visible posts for viewer: ' . count( $rows ) );
 * } );
 */
