<?php
/**
 * Plugin Name: BuddyNext Snippet - Add space settings fields
 * Description: Adds your own per-space settings (a URL and a toggle) that space owners edit under Manage space -> Custom fields.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0.4+
 * Tested up to: BuddyNext 1.2.1
 *
 * Register a field and BuddyNext does the rest: the input appears in the
 * space's "Custom fields" settings tab, it saves over
 * POST /buddynext/v1/spaces/{id}/fields with inline validation, only the space
 * owner can write it (`writable_by` => 'moderator' widens that), and it is
 * stored in bn_space_meta. No form, nonce or save handler to write.
 *
 * Read a value anywhere with buddynext_get_space_field( $space_id, $key ) -
 * it returns the typed value, with your `default` when unset.
 *
 * Docs: developer-guide/09-schema-spaces.md, developer-guide/29-hooks-spaces.md
 * Core examples: includes/Spaces/CoreSpaceFields.php
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'buddynext_register_space_fields',
	static function (): void {
		buddynext_register_space_field(
			'bnx_meeting_url',
			array(
				'type'        => 'url',
				'label'       => __( 'Meeting link', 'bnx-snippet' ),
				'description' => __( 'Shown to members as the space\'s regular call link.', 'bnx-snippet' ),
				'visibility'  => 'members',
				'sort_order'  => 10,
			)
		);

		buddynext_register_space_field(
			'bnx_show_meeting',
			array(
				'type'        => 'boolean',
				'label'       => __( 'Show the meeting link', 'bnx-snippet' ),
				'default'     => '1',
				'sort_order'  => 20,
			)
		);
	}
);

// Use the values: show the link above the space feed, to members only.
add_action(
	'buddynext_part_space_feed_panel_before',
	static function ( array $args ): void {
		$space_id = (int) ( $args['space_id'] ?? 0 );
		if ( ! $space_id || empty( $args['is_member'] ) || ! buddynext_get_space_field( $space_id, 'bnx_show_meeting' ) ) {
			return;
		}
		$url = (string) buddynext_get_space_field( $space_id, 'bnx_meeting_url' );
		if ( '' === $url ) {
			return;
		}
		printf(
			'<p class="bn-card" style="padding: var(--bn-s3) var(--bn-s4); margin-block-end: var(--bn-s4);"><a href="%1$s">%2$s</a></p>',
			esc_url( $url ),
			esc_html__( 'Join the space call', 'bnx-snippet' )
		);
	}
);
