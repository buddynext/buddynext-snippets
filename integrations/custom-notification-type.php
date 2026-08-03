<?php
/**
 * Plugin Name: BuddyNext Snippet - Add your own notification type
 * Description: Register a notification type from your plugin, send it, render its message and link, and give members a real on/off switch for it.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.1+
 * Tested up to: BuddyNext 1.1.1
 *
 * Sending a notification from another plugin is four small pieces. Skip the first
 * one and your notification still sends - but the member has no way to turn it off,
 * which is how a good integration becomes the reason someone uninstalls you.
 *
 *   1. Register the type in the prefs catalogue  <- gives the member the switch
 *   2. Send it                                    <- NotificationService::create()
 *   3. Render its text                            <- buddynext_notification_message
 *   4. Render its link                            <- buddynext_notification_url
 *
 * THE RULE FOR PARTNER PLUGINS: can_email => false
 *
 * BuddyNext never emails on your behalf. Its notification centre is a collective
 * display, so members see everything in one place - but the email for YOUR feature
 * is yours to send, from your own templates. Setting can_email => true here would
 * mean BuddyNext emails about your feature using its own wording, and a member who
 * unsubscribed from your emails would still get them. Keep it false and send your
 * own if you need to.
 *
 * WHY REGISTERING MATTERS MORE THAN IT LOOKS
 *
 * NotificationService::create() reads the recipient's per-type `on_site` preference
 * before it inserts anything. A type nobody registered has no row in Settings ->
 * Notifications, so the member cannot switch it off - and unknown types default to
 * ON. Register it and the switch is real.
 *
 * REPLACE THESE
 *
 *   MYPLUGIN_VERSION      the constant that proves YOUR plugin is loaded
 *   myplugin              your integration key, matching bridge-your-plugin.php
 *   myplugin.item_liked   your type slug - always prefix it, never a bare word
 *
 * Docs: developer-guide/30-hooks-notifications-email.md
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'buddynext_load_bridges',
	static function (): void {

		if ( ! defined( 'MYPLUGIN_VERSION' ) ) {
			return;
		}

		// The type slug. Prefix it with your plugin so it can never collide with
		// BuddyNext's own (bn.*) or another integration's.
		$type = 'myplugin.item_liked';

		// ── 1. Register it, so the member gets a switch ─────────────────────
		add_filter(
			'buddynext_notification_prefs_catalogue',
			static function ( array $catalogue ) use ( $type ): array {
				$catalogue[ $type ] = array(
					'label'              => __( 'Likes on your items', 'myplugin' ),
					'description'        => __( 'Someone liked an item you published.', 'myplugin' ),
					// Which section of Settings -> Notifications it appears under.
					// social | feed | spaces | messages | moderation | growth
					'group'              => \BuddyNext\Notifications\NotificationPrefCatalogue::GROUP_FEED,
					'default_on_site'    => true,
					// Ignored while can_email is false, but keep it honest.
					'default_email_freq' => 'never',
					// FALSE for partner plugins. See the header.
					'can_email'          => false,
				);
				return $catalogue;
			}
		);

		// ── 2. Send it ──────────────────────────────────────────────────────
		add_action(
			'myplugin_item_liked',
			static function ( int $item_id, int $liker_id, int $author_id ) use ( $type ): void {

				// Never notify someone about their own action.
				if ( $liker_id === $author_id || $author_id <= 0 ) {
					return;
				}

				// Respect the site owner's integration switch, like every other surface.
				if ( ! buddynext_integration_enabled( 'myplugin', 'feed' ) ) {
					return;
				}

				// Be block-aware. If the recipient has blocked the actor, say nothing.
				// BuddyNext gates its own notifications this way and yours should too.
				$blocks = buddynext_service( 'blocks' );
				if ( is_object( $blocks ) && method_exists( $blocks, 'is_blocked' )
					&& $blocks->is_blocked( $author_id, $liker_id ) ) {
					return;
				}

				buddynext_service( 'notifications' )->create(
					array(
						'recipient_id' => $author_id,
						'sender_id'    => $liker_id,
						'type'         => $type,
						'object_type'  => 'myplugin_item',
						'object_id'    => $item_id,

						// group_key collapses repeats. With one set, ten likes on the
						// same item update ONE unread row and bump its count, instead
						// of burying the member under ten notifications. Leave it out
						// only if every event genuinely deserves its own line.
						'group_key'    => $type . ':' . $item_id,

						'data'         => array(
							// Store a ready message and url. BuddyNext falls back to
							// these when your render filters below are not loaded in
							// the current request - during a cron run, for example.
							// Without them such a notification renders blank.
							'message' => sprintf(
								/* translators: %s: item title. */
								__( 'liked your item "%s"', 'myplugin' ),
								get_the_title( $item_id )
							),
							'url'     => (string) get_permalink( $item_id ),
						),
					)
				);

				// create() returns 0 - not an error - when the member has switched
				// this type off. That is the switch from step 1 doing its job.
			},
			10,
			3
		);

		// ── 3. Render the text ──────────────────────────────────────────────
		// Return '' for types that are not yours, or you will hijack everyone else's.
		add_filter(
			'buddynext_notification_message',
			static function ( string $message, string $notification_type, string $actor_name, int $object_id, array $data ) use ( $type ): string {
				if ( $notification_type !== $type ) {
					return $message;
				}
				return sprintf(
					/* translators: 1: actor display name, 2: item title. */
					__( '%1$s liked your item "%2$s"', 'myplugin' ),
					$actor_name,
					get_the_title( $object_id )
				);
			},
			10,
			5
		);

		// ── 4. Render the link ──────────────────────────────────────────────
		add_filter(
			'buddynext_notification_url',
			static function ( string $url, string $notification_type, int $actor_id, int $object_id, array $data ) use ( $type ): string {
				if ( $notification_type !== $type ) {
					return $url;
				}
				return (string) get_permalink( $object_id );
			},
			10,
			5
		);
	}
);
