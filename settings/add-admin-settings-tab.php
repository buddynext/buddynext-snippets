<?php
/**
 * Plugin Name: BuddyNext Snippet - Add an admin settings tab
 * Description: Adds your own tab to the BuddyNext settings screen in wp-admin, with one saved option.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.1+
 * Tested up to: BuddyNext 1.2.1
 *
 * AdminHub::register_tab() puts your tab in the BuddyNext settings sidebar,
 * inside the same shell as the built-in tabs. Register on `init` (labels need
 * the textdomain). The tab body is yours: here a plain WordPress Settings API
 * form, so WordPress handles the nonce, the capability check and the save.
 *
 * To give your add-on its own top-level section instead, add one with the
 * `bn_admin_hub_sections` filter and register the tab into that key. Link to
 * the tab with AdminHub::tab_url( 'settings', 'bnx-extras' ), never a
 * hand-built ?page= URL, so a future placement move cannot break it.
 *
 * Docs: developer-guide/40-admin-pages-and-settings.md
 * Core examples: includes/Admin/ToolsTab.php
 */

defined( 'ABSPATH' ) || exit;

// The option, registered for the Settings API form below.
add_action(
	'admin_init',
	static function (): void {
		register_setting(
			'bnx_extras',
			'bnx_welcome_note',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
	}
);

add_action(
	'init',
	static function (): void {
		if ( ! class_exists( '\BuddyNext\Admin\AdminHub' ) ) {
			return;
		}
		\BuddyNext\Admin\AdminHub::register_tab(
			'settings',
			'bnx-extras',
			__( 'My add-on', 'bnx-snippet' ),
			static function (): void {
				?>
				<form method="post" action="options.php">
					<?php settings_fields( 'bnx_extras' ); ?>
					<p>
						<label for="bnx_welcome_note"><?php esc_html_e( 'Welcome note', 'bnx-snippet' ); ?></label><br />
						<input type="text" class="regular-text" id="bnx_welcome_note" name="bnx_welcome_note" value="<?php echo esc_attr( (string) get_option( 'bnx_welcome_note', '' ) ); ?>" />
					</p>
					<button type="submit" class="bn-btn" data-variant="primary"><?php esc_html_e( 'Save', 'bnx-snippet' ); ?></button>
				</form>
				<?php
			},
			array(
				'cap'      => 'manage_options',
				'subtitle' => __( 'Settings for my add-on.', 'bnx-snippet' ),
			)
		);
	}
);
