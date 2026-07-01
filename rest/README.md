# Talking to the BuddyNext REST API

The BuddyNext frontend is 100% REST - no `admin-ajax.php`. Everything lives under `wp-json/buddynext/v1/` (Pro under `buddynext-pro/v1/`). If you are building an app or a custom client, this is the surface you use.

## The contract (verified live on 1.0.4)

| Rule | Value |
| --- | --- |
| Namespace (Free) | `buddynext/v1` |
| Auth | `X-WP-Nonce` header, nonce from `wp_create_nonce( 'wp_rest' )` (never in the query string) |
| Success body | The data array/object, HTTP status on the response (200/201) |
| Error body | `{ "code": "...", "message": "...", "data": { "status": N } }` |
| Pagination | Cursor-based (`{ items, next_cursor }`) for timelines/directories; page-numbered for bounded admin/search lists |
| `per_page` max | 50 on collection reads |
| Permission | Every route declares a `permission_callback` - never omitted |

Verified example: `GET /buddynext/v1/feed/home` returns `200` with `{ items: [...], next_cursor: "..." }`.

## Read the home feed (JS)

See [`fetch-feed.js`](fetch-feed.js) - a working `fetch()` consumer that sends the nonce and follows the cursor. Localize the base URL + nonce from PHP:

```php
wp_localize_script( 'my-handle', 'myCfg', array(
	'restUrl'   => esc_url_raw( rest_url( 'buddynext/v1/' ) ),
	'restNonce' => wp_create_nonce( 'wp_rest' ),
) );
```

Full route reference: `developer-guide/14-rest-contract.md` (rules) and `15`-`24` (per-resource routes: feed/posts, spaces, members, social graph, notifications, moderation, auth, search, webhooks, pro).
