# Roles and capabilities

How BuddyNext decides who can do what. Verified live on 1.0.4.

## The model

BuddyNext registers **no** custom WordPress roles. Authority is two BuddyNext-owned layers:

- **Community role** in user meta `bn_community_role`: `member` (default), `moderator`, `admin`, `owner`.
- **Per-space role** in the `bn_space_members.role` column: `owner`, `moderator`, `member`.

WordPress site admins are recognized via native `manage_options` (they pass every check). Ranking: `owner` 4 > `admin` 3 > `moderator` 2 > `member` 1 - a capability mapped to a role is granted to that role and everything above it.

## The one entry point

```php
buddynext_can( int $user_id, string $capability, array $context = array() ): bool
```

Never call `current_user_can()` against a BuddyNext capability or read `bn_community_role` directly - route every decision through `buddynext_can()` so all layers and filters apply. Pass `array( 'space_id' => $id )` in `$context` for space-scoped checks.

Resolution order inside `PermissionService::can()`: (1) WP admin, (2) community/space role vs the capability's minimum role, (3) explicit per-user grant (`bn_ability_{slug}` meta), (4) the `buddynext_user_can` filter - the final word.

Real capability slugs live in `PermissionService::ROLE_MAP`, e.g. `buddynext-feed/create-post` (member), `buddynext-feed/delete-any-post` (moderator), `buddynext-spaces/moderate` (moderator), `buddynext-spaces/create` (member).

## Grant or revoke a capability from code

See [`grant-capability.php`](grant-capability.php) - uses the `buddynext_user_can` filter (Layer 4) to grant `delete-any-post` to an allowlist. Verified live: `buddynext_can( 773, 'buddynext-feed/delete-any-post' )` returned `true` after the filter, while a non-allowlisted member returned `false`. Return `true` to grant, `false` to revoke, or the unchanged `$result` to stay out of the way.

Extension seams: `buddynext_user_can` (per-check override), `buddynext_role_map` (change a capability's minimum role), `buddynext_abilities` (register a new capability).

Full reference: `developer-guide/39-roles-and-capabilities.md`.
