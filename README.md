# BuddyNext Snippets

Copy-paste, ready-to-run code examples for extending [BuddyNext](https://github.com/buddynext/buddynext). Every snippet in this repo is a self-contained mu-plugin that has been dropped into a live `wp-content/mu-plugins/` and verified working - nothing here is illustrative-but-broken.

## How to use a snippet

1. Open the snippet file and read its header (what it does, which BuddyNext version it needs).
2. Copy it into `wp-content/mu-plugins/` (create that folder if it does not exist), or paste the body into your own plugin.
3. Adjust the labels, ids, and rendered content to your needs. Change the text domain from `bnx-snippet` to your own.

Every file guards `defined( 'ABSPATH' ) || exit;` and escapes its output, so it is safe to run as-is.

## Index

### navigation/
Add and change menus and tabs. See the developer guide: **Navigation API** (`developer-guide/47-nav-api.md`).

| Snippet | What it does |
| --- | --- |
| [`add-profile-tab.php`](navigation/add-profile-tab.php) | Adds a tab (with its own panel) to every member profile. |
| [`add-space-tab.php`](navigation/add-space-tab.php) | Adds a tab (with its own panel) to every space. |
| [`add-rail-item.php`](navigation/add-rail-item.php) | Adds a link to the persistent left-rail menu. |
| [`relabel-remove-nav.php`](navigation/relabel-remove-nav.php) | Reorders, relabels, and removes existing profile tabs. |

### hooks/
React to events with BuddyNext's action and filter hooks. See the developer guide: **Hooks** (`developer-guide/25-hooks-overview.md` and the per-area pages).

| Snippet | What it does |
| --- | --- |
| [`react-to-new-post.php`](hooks/react-to-new-post.php) | Runs your code whenever a member publishes an activity post (`buddynext_post_created`). |
| [`react-to-new-follow.php`](hooks/react-to-new-follow.php) | Runs your code whenever one member follows another (`buddynext_user_followed`). |
| [`react-to-space-join.php`](hooks/react-to-space-join.php) | Runs your code whenever a member joins a space (`buddynext_space_member_joined`). |
| [`react-to-post-deleted.php`](hooks/react-to-post-deleted.php) | Runs your code whenever an activity post is deleted (`buddynext_post_deleted`). |
| [`react-to-interests-change.php`](hooks/react-to-interests-change.php) | Runs your code whenever a member updates their picked interests (`buddynext_member_interests_updated`, BuddyNext 1.0.4+). |

### auth/
Registration, login, and email verification.

| Snippet | What it does |
| --- | --- |
| [`verify-existing-users.php`](auth/verify-existing-users.php) | One-time backfill that marks all existing members as email-verified, so pre-existing users are not locked out of posting after "Require email verification" is enabled (e.g. after a BuddyBoss migration). Idempotent, batched, fires no welcome emails/webhooks (verified live on a 1540-user 1.0.8 install). |

### templates/
Change what a template renders. See [`templates/README.md`](templates/README.md) and the developer guide: **Overriding templates** (`developer-guide/49-child-theme-template-overrides.md`).

| Snippet | What it does |
| --- | --- |
| [`wrap-a-template.php`](templates/wrap-a-template.php) | Injects markup around a template without copying it (`buddynext_after_template`). |
| [`README.md`](templates/README.md) | How to copy a template into your theme (`{theme}/buddynext/…`) + the no-`extract()` variable gotcha. |

### rest/
Talk to the BuddyNext REST API (100% REST, no admin-ajax). See [`rest/README.md`](rest/README.md) and the developer guide: **REST Contract** (`developer-guide/14-rest-contract.md`).

| Snippet | What it does |
| --- | --- |
| [`fetch-feed.js`](rest/fetch-feed.js) | Reads the home feed over REST with `X-WP-Nonce` + cursor pagination (verified `GET /feed/home` -> `{ items, next_cursor }`). |
| [`README.md`](rest/README.md) | The REST contract: namespace, nonce auth, envelope, cursor pagination, `per_page` limits. |

### roles-caps/
Grant, revoke, or gate what members can do. See [`roles-caps/README.md`](roles-caps/README.md) and the developer guide: **Roles and Capabilities** (`developer-guide/39-roles-and-capabilities.md`).

| Snippet | What it does |
| --- | --- |
| [`grant-capability.php`](roles-caps/grant-capability.php) | Grants a capability to specific members via the `buddynext_user_can` filter (verified: flips `buddynext_can()` for the allowlisted user). |
| [`README.md`](roles-caps/README.md) | The permission model: `buddynext_can()`, roles, the 4 resolution layers, capability slugs, filter seams. |

### suggestions/
Customize the people and space suggestion engines (BuddyNext 1.0.4+).

| Snippet | What it does |
| --- | --- |
| [`customize-suggestions.php`](suggestions/customize-suggestions.php) | Pins, removes, or reranks entries via `buddynext_follow_suggestions` / `buddynext_space_suggestions`, and tunes the interest-match ceiling (verified live: pin applies once the member has any signal; space exclusion + ceiling verified). |

### cron-async/
Defer slow work to the background. See [`cron-async/README.md`](cron-async/README.md) and the developer guide: **Cron and Async** (`developer-guide/37-cron-and-async.md`).

| Snippet | What it does |
| --- | --- |
| [`defer-work.php`](cron-async/defer-work.php) | Runs slow work after the request via an Action Scheduler async action under the `buddynext` group (verified queued + ran). |
| [`README.md`](cron-async/README.md) | The background-jobs rules: AS-first, the `buddynext` group, never force-disable WP-Cron. |

## The two navigation systems (quick reference)

BuddyNext has two navigation systems - use the right seam for the surface you are extending:

- **Nav registry** (member-profile tabs, space tabs): register on the `buddynext_register_nav` action with `$registry->register([...])`. Each item declares its `label`, `icon`, a lazy `url`, and a `render` callable that draws its panel; `PanelRenderer` server-renders only the active tab's panel. Modify existing items with the `buddynext_nav_items` filter.
- **Left rail** (the persistent global column): add plain-array links with the `buddynext_rail_items` filter.

## Tested up to

Every snippet header carries a `Tested up to:` line naming the BuddyNext version it was last live-verified against (1.0.4 for most; `auth/verify-existing-users.php` is verified to 1.0.8). These snippets are re-checked each release - the developer docs and their examples are never left stale. If a snippet stops working on a newer version, please open an issue.
