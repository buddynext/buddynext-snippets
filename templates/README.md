# Overriding BuddyNext templates

Two ways to change what a BuddyNext template renders. Both verified live on BuddyNext 1.0.4.

## 1. Wrap it with a hook (no copy) - `wrap-a-template.php`

If you only need to add markup around a template, drop [`wrap-a-template.php`](wrap-a-template.php) in `wp-content/mu-plugins/`. Every template render fires `buddynext_before_template` and `buddynext_after_template`, each passing `(string $path, string $relative)`; match on `$relative` (e.g. `feed/home.php`) to target one surface. No template copy, survives plugin updates.

## 2. Copy it into your theme (full control)

BuddyNext resolves every template through a three-tier loader (`BuddyNext\Core\TemplateLoader::locate()`), first hit wins:

1. `{active-child-theme}/buddynext/{relative}`
2. `{parent-theme}/buddynext/{relative}`
3. `{plugin}/templates/{relative}`

So to override `templates/partials/post-card.php`, create `{your-theme}/buddynext/partials/post-card.php` - keep the subpath under `buddynext/` identical to the subpath under the plugin's `templates/`. The loader serves your copy on every surface that renders that template. (Verified: a file at `{theme}/buddynext/partials/share-modal.php` was served instead of the plugin default.)

### The one gotcha: no `extract()`

`TemplateLoader::render()` does NOT call `extract()`. It imports the passed variables manually, and only:

- **string** keys,
- matching the PHP identifier regex `^[a-zA-Z_][a-zA-Z0-9_]*$`,
- that are not reserved loader locals (`path`, `relative`, `variables`, `bn_reserved`, `bn_key`, `bn_value`, `bn_filtered`, `bn_html`).

A bad key is dropped **silently** (no notice). So: **read the `@var` block in the template's PHP header** - it is the authoritative list of the variables in scope for your override. If a name is not in that header, it is not available; do not assume globals or extras.

Many parts also carry an `Overridable:` header line naming the exact target path to copy to.

Full reference: `developer-guide/49-child-theme-template-overrides.md`.
