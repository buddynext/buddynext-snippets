# Cron and background jobs

Keep the request fast: defer slow work to Action Scheduler. Verified live on 1.0.4.

## The rules

1. **Pick the lightest mechanism.** Derivable data -> compute lazily + cache (no job). Event responses -> a one-off async action. Always-on work (digests, retries) -> a recurring schedule at the lowest acceptable cadence.
2. **Action Scheduler first, WP-Cron fallback.** Run jobs under the **`buddynext`** group so they're observable/retryable in Tools > Scheduled Actions; fall back to a direct call when AS is absent.
3. **Never force-disable WP-Cron.** The plugin never defines `DISABLE_WP_CRON` and never requires a system cron to be fast.

## Defer work off an event - `defer-work.php`

See [`defer-work.php`](defer-work.php). It enqueues a one-off async action on `buddynext_post_created` and runs the slow work in the handler out of band. Verified live: after a post, `bnx_deferred_after_post` was queued under the `buddynext` group with `[post_id, user_id]` and the handler ran.

Key points the snippet shows:
- Guard against double-queueing with `as_next_scheduled_action( $hook, $args, 'buddynext' )` before `as_enqueue_async_action(...)`.
- Register the handler on the **same hook name** - AS and WP-Cron both fire it.
- Provide an inline fallback for when `as_enqueue_async_action()` is unavailable, so the feature never silently no-ops.

For recurring jobs, use `as_schedule_recurring_action()` guarded by `as_has_scheduled_action()`, and prefer a self-(un)scheduling job (arm on first work, disarm when the queue drains) over a fixed poll.

Full reference: `developer-guide/37-cron-and-async.md` and `docs/standards/BACKGROUND-JOBS.md`.
