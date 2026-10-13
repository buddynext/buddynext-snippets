# What belongs in this library, and how to add it

Read this before adding or changing a snippet. It exists so nobody spends a day on the wrong thing.

## The rule

**A snippet earns its place by being something site builders commonly want to do.** Each one answers a real "how do I ...?" from someone running a community: use my own login page, keep one page public, tell my CRM when a profile changes.

This library is **not** a hook reference. Having an example for every hook is not a goal, and a hook with no snippet is not a gap. Do not build coverage reports or count uncovered hooks. The hook reference lives in the BuddyNext developer guide.

**Every BuddyNext snippet lives here.** If a snippet is written for a support reply, a card or a chat, put it in this repo and link to it. Do not leave copies anywhere else.

## Before you write one

Ask these in order. Stop at the first "no".

1. **Did someone ask for it?** A support ticket, a card in Suggestions or Scope, a question from the owner or a customer. Your own guess about what might be useful is the weakest source; say so if that is all you have.
2. **Can the owner already do it in Settings?** If there is a setting, the answer is the setting, not a snippet.
3. **Is there a snippet for it already?** Check the README index. Extend that one instead of adding a near copy.
4. **Does BuddyNext give you a way in?** Use a documented hook, service or helper. If the product has no door for it, that is a card for the BuddyNext developers, not a workaround here.

## How to write it

- **Name it after the task, not the hook.** `use-your-own-login-and-signup-pages.php`, not `owns-core-auth-links-filter.php`.
- **One file, one job, ready to paste.** A self-contained mu-plugin: the header, `defined( 'ABSPATH' ) || exit;`, then the code. No build step, no second file.
- **The header explains it to someone who has never read the source:** when you would want this, how it works, what to change (mark those lines), and what it deliberately does not do.
- **Never fight BuddyNext, and never make BuddyNext fight another plugin.** A snippet adds a choice for one site. BuddyNext's own screens and links keep working for every site that does not install it.
- **State the BuddyNext version it needs** and the version it was last tested on.
- Escape output, sanitise input, use the text domain `bnx-snippet`.

## How to check it, and how to say so

The README promises that snippets are run, not just read. Hold to that, and be exact about what you did:

- **Best:** installed on a clean site, the event fired for real, the result seen.
- **Acceptable, if you say so:** loaded on a real BuddyNext site, its hooks fired, the result asserted. Write in the header exactly what was fired and what came back.
- **Not acceptable:** "read the code, looks right".

If something was not tested (a third-party plugin that was not active, a browser walk that was not done), the header says that in plain words. Test against the product as it is actually run: with BuddyNext Pro active, Pro may already answer the same hook.

Never test on a production site. On a shared demo site, load the file without installing it and roll back anything it writes.

## When you are done

1. Add a row to the README index under the right heading, with one sentence on what the site builder gets.
2. `git pull --rebase` before you commit. More than one person adds snippets here.
3. Commit straight to `master` with a message that names the task the snippet solves.
