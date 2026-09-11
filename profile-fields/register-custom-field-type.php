<?php
/**
 * Plugin Name: BuddyNext Snippet - Register a custom profile field type
 * Description: Adds a brand-new profile field type with its own input, display, sanitize, and search behaviour.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0+
 * Tested up to: BuddyNext 1.2.0
 *
 * A field TYPE (not a field of an existing type) is registered in two places and
 * given behaviour through a small set of filters. Verified against:
 *   - includes/Profile/FieldType.php            (buddynext_field_types + render/sanitize/display/searchable)
 *   - includes/Admin/Members/ProfileFieldsManager.php (buddynext_profile_field_types[_labels])
 *
 * The two registries, and why a complete type touches both:
 *   - `buddynext_field_types`         the engine registry, source of truth for a
 *                                     type's metadata + the render/sanitize pipeline.
 *                                     Register here or the type degrades to plain text.
 *   - `buddynext_profile_field_types` the admin field-type dropdown. Register here so
 *                                     an owner can actually pick your type.
 *
 * This example ships a "Twitter handle" type. Change the slug, label, and the
 * render/sanitize bodies to build your own. BuddyNext Pro's
 * includes/Profile/AdvancedFieldTypes.php (Location, Conditional, ...) is the
 * full shipping reference for this pattern.
 *
 * Docs: developer-guide/41-extending-cookbook.md (Recipe 16)
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	static function (): void {

		$slug = 'twitter_handle';

		// 1) Register the type with the engine. Required descriptor keys: label,
		//    value_kind ('scalar' | 'multi' | 'bool'), is_choice, is_searchable_capable.
		//    sample_value lets the conformance suite prove the type renders its storage.
		add_filter(
			'buddynext_field_types',
			static function ( array $types ) use ( $slug ): array {
				$types[ $slug ] = array(
					'label'                 => __( 'Twitter handle', 'bnx-snippet' ),
					'value_kind'            => 'scalar',
					'is_choice'             => false,
					'is_searchable_capable' => true,
					'sample_value'          => '@example',
				);
				return $types;
			}
		);

		// 2) Offer it in the admin field-type dropdown, with a label.
		add_filter(
			'buddynext_profile_field_types',
			static function ( array $types ) use ( $slug ): array {
				$types[] = $slug;
				return $types;
			}
		);
		add_filter(
			'buddynext_profile_field_type_labels',
			static function ( array $labels ) use ( $slug ): array {
				$labels[ $slug ] = __( 'Twitter handle', 'bnx-snippet' );
				return $labels;
			}
		);

		// 3) Sanitize on save. Return the value to store; return '' to reject.
		add_filter(
			'buddynext_field_sanitize',
			static function ( $handled, array $field, $raw ) use ( $slug ) {
				if ( $slug !== ( $field['type'] ?? '' ) ) {
					return $handled; // Not ours - pass through untouched.
				}
				return '@' . ltrim( sanitize_text_field( (string) $raw ), '@' );
			},
			10,
			3
		);

		// 4) Render the input on the profile edit form. Return the field HTML.
		add_filter(
			'buddynext_field_render_input',
			static function ( $handled, array $field, $value, $name ) use ( $slug ) {
				if ( $slug !== ( $field['type'] ?? '' ) ) {
					return $handled;
				}
				return sprintf(
					'<input type="text" name="%s" value="%s" placeholder="@handle" />',
					esc_attr( (string) $name ),
					esc_attr( (string) $value )
				);
			},
			10,
			4
		);

		// 5) Render the display value on the profile. Return the display HTML.
		add_filter(
			'buddynext_field_render_display',
			static function ( $handled, array $field, $value ) use ( $slug ) {
				if ( $slug !== ( $field['type'] ?? '' ) ) {
					return $handled;
				}
				$handle = ltrim( (string) $value, '@' );
				return sprintf(
					'<a href="https://twitter.com/%s" rel="nofollow">@%1$s</a>',
					esc_attr( $handle )
				);
			},
			10,
			3
		);

		// 6) Feed the search index a plain-text form, since the type declared
		//    is_searchable_capable => true.
		add_filter(
			'buddynext_field_searchable_text',
			static function ( $text, array $field, $value ) use ( $slug ) {
				if ( $slug !== ( $field['type'] ?? '' ) ) {
					return $text;
				}
				return ltrim( (string) $value, '@' );
			},
			10,
			3
		);
	}
);
