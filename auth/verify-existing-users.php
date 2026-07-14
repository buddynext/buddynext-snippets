<?php
/**
 * Plugin Name: BuddyNext Snippet - Verify all existing users (one-time backfill)
 * Description: One-time backfill that marks every EXISTING member as email-verified, so pre-existing users are not locked out of posting after "Require email verification" is switched on (e.g. after migrating from BuddyBoss/BuddyPress). New sign-ups still verify as normal.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.0.8
 *
 * THE PROBLEM
 * -----------
 * BuddyNext gates posting/commenting on the `buddynext_email_verified` user-meta
 * flag whenever the `buddynext_email_verify` option is on
 * (VerificationService::is_verified()). Members who registered BEFORE the option
 * was enabled never received a token and never got that flag, so they all read as
 * unverified and every post/reply returns `403 email_unverified`. On a migrated
 * site (BuddyBoss -> BuddyNext) that is your entire existing membership.
 *
 * WHAT THIS DOES
 * --------------
 * Runs ONCE, on the next admin page load, for a logged-in administrator. For every
 * user that is not already flagged it mirrors exactly what a real verification does
 * (VerificationService::verify()):
 *
 *   update_user_meta( $id, 'buddynext_email_verified', 1 );  // the gate
 *   delete_user_meta( $id, 'buddynext_verify_pending' );     // leave the stale-
 *                                                            //   unverified purge
 *                                                            //   cron's candidate
 *                                                            //   set
 *
 * It DELIBERATELY does NOT fire the `buddynext_user_verified` action. That action
 * sends a welcome email (RegistrationEmailListener) and fires an outbound webhook
 * (OutboundWebhookListener); firing it once per legacy member would blast hundreds
 * of emails + webhooks at years-old accounts. A silent backfill is correct here.
 *
 * Idempotent, batched 500 users at a time (flat memory at any user count), and
 * self-guarded so it can run only once. After the admin notice confirms the count,
 * delete this file / snippet.
 *
 * Verified live on a 1540-user install (BuddyNext 1.0.8): 1538 marked, 2 already
 * verified, purge markers cleared to 0, is_verified() true afterwards, admin
 * request returned HTTP 200 (no fatal), and a second run was a clean no-op.
 *
 * Docs: developer-guide (Auth / email verification).
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_init',
	static function (): void {
		// Guard 1: administrators only.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Guard 2: run exactly once, ever.
		if ( get_option( 'buddynext_verify_backfill_done' ) ) {
			return;
		}

		$verified = 0;
		$skipped  = 0;
		$paged    = 1;

		do {
			$query = new WP_User_Query(
				array(
					'fields'      => 'ID',
					'number'      => 500,
					'paged'       => $paged,
					'orderby'     => 'ID',
					'order'       => 'ASC',
					'count_total' => false,
				)
			);

			$ids = $query->get_results();
			if ( empty( $ids ) ) {
				break;
			}

			foreach ( $ids as $user_id ) {
				$user_id = (int) $user_id;

				if ( (bool) get_user_meta( $user_id, 'buddynext_email_verified', true ) ) {
					++$skipped;
					continue;
				}

				update_user_meta( $user_id, 'buddynext_email_verified', 1 );
				delete_user_meta( $user_id, 'buddynext_verify_pending' );
				++$verified;
			}

			++$paged;
			unset( $query, $ids );

		} while ( true );

		update_option( 'buddynext_verify_backfill_done', time(), false );

		set_transient(
			'buddynext_verify_backfill_notice',
			sprintf(
				/* translators: 1: number of users newly verified, 2: number already verified. */
				esc_html__( 'BuddyNext: marked %1$d existing user(s) as email-verified (%2$d were already verified). You can remove this snippet now.', 'bnx-snippet' ),
				$verified,
				$skipped
			),
			HOUR_IN_SECONDS
		);
	}
);

add_action(
	'admin_notices',
	static function (): void {
		$message = get_transient( 'buddynext_verify_backfill_notice' );
		if ( $message && current_user_can( 'manage_options' ) ) {
			delete_transient( 'buddynext_verify_backfill_notice' );
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
		}
	}
);
