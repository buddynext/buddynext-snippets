/**
 * BuddyNext Snippet - Read the home feed over REST
 * Tested up to: BuddyNext 1.0.4
 *
 * The BuddyNext frontend is 100% REST under wp-json/buddynext/v1/. Auth is the
 * standard logged-in WordPress pattern: cookie + the `wp_rest` nonce sent in the
 * `X-WP-Nonce` header (never in the query string). Timelines and directories use
 * cursor pagination: the response is { items: [...], next_cursor: "..." }, and
 * you pass the cursor back to get the next page.
 *
 * This exact call was verified live on 1.0.4: GET /feed/home -> 200 with
 * { items, next_cursor }.
 *
 * In a real script, localize the base URL + nonce from PHP:
 *   wp_localize_script( 'my-handle', 'myCfg', array(
 *     'restUrl'   => esc_url_raw( rest_url( 'buddynext/v1/' ) ),
 *     'restNonce' => wp_create_nonce( 'wp_rest' ),
 *   ) );
 *
 * Docs: developer-guide/14-rest-contract.md, developer-guide/15-rest-feed-posts.md
 */

async function bnxGetHomeFeed( cursor ) {
	const url = new URL( myCfg.restUrl + 'feed/home', window.location.origin );
	if ( cursor ) {
		url.searchParams.set( 'cursor', cursor );
	}

	const res = await fetch( url, {
		headers: { 'X-WP-Nonce': myCfg.restNonce },
	} );

	if ( ! res.ok ) {
		// Error envelope: { code, message, data: { status } }
		const err = await res.json();
		throw new Error( err.message || ( 'HTTP ' + res.status ) );
	}

	const body = await res.json();
	// body.items = posts for this page; body.next_cursor = pass back for the next page.
	return body;
}

// Example: first page, then the next page.
// const page1 = await bnxGetHomeFeed();
// const page2 = await bnxGetHomeFeed( page1.next_cursor );
