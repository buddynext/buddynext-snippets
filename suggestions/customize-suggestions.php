<?php
/**
 * Plugin Name: BuddyNext Snippet - Customize people and space suggestions
 * Description: Reranks, injects, or removes entries in the suggestion engines.
 * Version:     1.0.0
 * Requires:    BuddyNext 1.0.4+
 * Tested up to: BuddyNext 1.0.4
 *
 * Both suggestion engines expose a final-word filter on their ranked ID lists
 * (both run AFTER the built-in ranking and outside its cache, so your changes
 * apply on every request):
 *
 *   apply_filters( 'buddynext_follow_suggestions', int[] $ids, int $user_id );
 *   apply_filters( 'buddynext_space_suggestions',  int[] $ids, int $user_id );
 *
 * Return a reordered / filtered / extended array of IDs. Anything you inject
 * is still visibility-checked before display.
 *
 * One deliberate exception: on a TRUE cold start (the viewer follows no one
 * AND has no interest matches) the people engine returns empty WITHOUT firing
 * the seam - so an injected pick appears as soon as the member has any signal,
 * never on a completely blank account.
 *
 * A third tuning knob: interest matching ignores categories picked by more
 * than 10% of members (a category everyone shares carries no signal). Adjust
 * the ceiling per-site:
 *
 *   apply_filters( 'buddynext_interest_match_ceiling', float $fraction, int $user_id );
 */

defined( 'ABSPATH' ) || exit;

// Example 1: pin a staff account at the top of people suggestions.
add_filter(
	'buddynext_follow_suggestions',
	static function ( array $ids, int $user_id ): array {
		$staff = 42; // Your community-manager account ID.
		if ( $staff !== $user_id && ! in_array( $staff, $ids, true ) ) {
			array_unshift( $ids, $staff );
		}
		return $ids;
	},
	10,
	2
);

// Example 2: never suggest a specific space (e.g. a staff-only lounge).
add_filter(
	'buddynext_space_suggestions',
	static function ( array $ids ): array {
		$hidden = array( 7 ); // Space IDs to keep out of suggestions.
		return array_values( array_diff( $ids, $hidden ) );
	}
);

// Example 3: loosen the interest-match ceiling on a small community where
// even a widely-picked category still carries useful signal.
add_filter(
	'buddynext_interest_match_ceiling',
	static function (): float {
		return 0.25; // Default 0.10 (categories picked by >10% of members are skipped).
	}
);
