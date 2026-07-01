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
| [`relabel-remove-nav.php`](navigation/relabel-remove-nav.php) | Relabels and removes existing profile tabs. |

### hooks/
React to events with BuddyNext's action and filter hooks. See the developer guide: **Hooks** (`developer-guide/25-hooks-overview.md` and the per-area pages).

| Snippet | What it does |
| --- | --- |
| [`react-to-new-post.php`](hooks/react-to-new-post.php) | Runs your code whenever a member publishes an activity post (`buddynext_post_created`). |
| [`react-to-new-follow.php`](hooks/react-to-new-follow.php) | Runs your code whenever one member follows another (`buddynext_user_followed`). |
| [`react-to-space-join.php`](hooks/react-to-space-join.php) | Runs your code whenever a member joins a space (`buddynext_space_member_joined`). |
| [`react-to-post-deleted.php`](hooks/react-to-post-deleted.php) | Runs your code whenever an activity post is deleted (`buddynext_post_deleted`). |

## The two navigation systems (quick reference)

BuddyNext has two navigation systems - use the right seam for the surface you are extending:

- **Nav registry** (member-profile tabs, space tabs): register on the `buddynext_register_nav` action with `$registry->register([...])`. Each item declares its `label`, `icon`, a lazy `url`, and a `render` callable that draws its panel; `PanelRenderer` server-renders only the active tab's panel. Modify existing items with the `buddynext_nav_items` filter.
- **Left rail** (the persistent global column): add plain-array links with the `buddynext_rail_items` filter.

## Tested up to

Every snippet header carries a `Tested up to:` line naming the BuddyNext version it was last live-verified against (currently 1.0.4). These snippets are re-checked each release - the developer docs and their examples are never left stale. If a snippet stops working on a newer version, please open an issue.
