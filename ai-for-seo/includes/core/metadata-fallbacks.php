<?php
/**
 * Metadata fallback configuration and resolution.
 *
 * @package AI_For_SEO
 */

// Keep this core module inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === METADATA FALLBACKS ================================================================== \\

/**
 * Populate missing metadata values by applying configured fallbacks.
 *
 * @param int    $post_id                      The current post ID.
 * @param string $metadata_identifier          Metadata identifier to resolve.
 * @param array  $our_metadata                 Reference to metadata collection for this post.
 * @param array  $visited_metadata_identifiers Optional stack to avoid circular fallbacks.
 *
 * @return void
 */
function ai4seo_apply_possible_fallbacks( int $post_id, string $metadata_identifier, array &$our_metadata, array $visited_metadata_identifiers = array() ): void {
	// Retain the plugin-wide recursion guard because configured metadata fallbacks can call this resolver recursively.
	if ( ai4seo_prevent_loops( __FUNCTION__, 5, 100 ) ) {
		ai4seo_debug_message( 176107421, 'Prevented loop', true );
		return;
	}

	// Fallbacks only fill empty fields so existing generated, imported, or user-edited values always win.
	if ( isset( $our_metadata[ $metadata_identifier ] ) && $our_metadata[ $metadata_identifier ] ) {
		return;
	}

	// Stop path-local cycles before reading settings or following another metadata dependency.
	if ( in_array( $metadata_identifier, $visited_metadata_identifiers, true ) ) {
		return;
	}

	// Capture the active metadata set once so recursive field fallbacks cannot pull from disabled fields.
	$active_meta_tags = ai4seo_get_active_meta_tags();

	// Record the current field before resolving its configured source so descendants inherit the cycle guard.
	$visited_metadata_identifiers[] = $metadata_identifier;

	// Resolve the setting through the central field-to-setting mapping used by both output and validation.
	$fallback_setting_name = ai4seo_get_metadata_fallback_setting_name( $metadata_identifier );

	// Unsupported fields have no fallback contract and must remain untouched.
	if ( ! $fallback_setting_name ) {
		return;
	}

	// Read the saved preference only after confirming that this metadata field supports fallbacks.
	$fallback_preference = ai4seo_get_setting( $fallback_setting_name );

	// Validate against the identifier-only allowlist so translated labels never affect runtime resolution.
	$allowed_fallback_values = ai4seo_get_metadata_fallback_allowed_value_identifiers( $metadata_identifier );

	if ( ! is_string( $fallback_preference ) || '' === $fallback_preference || 'no-fallback' === $fallback_preference || ! in_array( $fallback_preference, $allowed_fallback_values, true ) ) {
		return;
	}

	// Direct post sources resolve locally, while metadata sources recurse through the same guarded workflow.
	$fallback_value = '';

	switch ( $fallback_preference ) {
		case 'post-title':
			$fallback_value = ai4seo_get_metadata_fallback_post_title( $post_id );
			break;

		case 'post-excerpt':
			$fallback_value = ai4seo_get_metadata_fallback_post_excerpt( $post_id );
			break;

		case 'content':
			$fallback_value = ai4seo_get_metadata_fallback_post_content( $post_id );
			break;

		default:
			$fallback_metadata_identifier = $fallback_preference;

			// Only active metadata fields may participate as fallback sources.
			if ( ! in_array( $fallback_metadata_identifier, $active_meta_tags, true ) ) {
				return;
			}

			// Prevent the next recursive hop when the configured chain already contains this field.
			if ( in_array( $fallback_metadata_identifier, $visited_metadata_identifiers, true ) ) {
				return;
			}

			// Resolve an empty dependency first so chained field fallbacks share one final value.
			if ( ! isset( $our_metadata[ $fallback_metadata_identifier ] ) || ! $our_metadata[ $fallback_metadata_identifier ] ) {
				ai4seo_apply_possible_fallbacks( $post_id, $fallback_metadata_identifier, $our_metadata, $visited_metadata_identifiers );
			}

			$fallback_value = $our_metadata[ $fallback_metadata_identifier ] ?? '';
			break;
	}

	// Normalize scalar sources without accepting arrays or objects as metadata output.
	if ( ! is_string( $fallback_value ) ) {
		if ( is_scalar( $fallback_value ) ) {
			$fallback_value = (string) $fallback_value;
		} else {
			return;
		}
	}

	// Empty normalized sources must not overwrite the unresolved field with a meaningless value.
	$fallback_value = trim( $fallback_value );

	if ( '' === $fallback_value ) {
		return;
	}

	// Store the resolved value in the shared metadata collection consumed by frontend injection.
	$our_metadata[ $metadata_identifier ] = $fallback_value;
}


/**
 * Retrieve the setting name that stores the fallback preference for a metadata identifier.
 *
 * @param string $metadata_identifier Metadata identifier.
 *
 * @return string|null The related setting constant or null when unsupported.
 */
function ai4seo_get_metadata_fallback_setting_name( string $metadata_identifier ): ?string {
	// Keep this forward mapping explicit because declarations expose separate constants for each metadata field.
	switch ( $metadata_identifier ) {
		case 'meta-title':
			return AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE;
		case 'meta-description':
			return AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION;
		case 'facebook-title':
			return AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE;
		case 'facebook-description':
			return AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION;
		case 'twitter-title':
			return AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE;
		case 'twitter-description':
			return AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION;
	}

	return null;
}


/**
 * Retrieve the metadata identifier mapped to a fallback setting name.
 *
 * @param string $setting_name Setting identifier.
 *
 * @return string|null Metadata identifier or null if not supported.
 */
function ai4seo_get_fallback_metadata_identifier_by_setting_name( string $setting_name ): ?string {
	// Mirror the forward mapping so settings validation can resolve identifiers without scanning declarations.
	switch ( $setting_name ) {
		case AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE:
			return 'meta-title';
		case AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION:
			return 'meta-description';
		case AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE:
			return 'facebook-title';
		case AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION:
			return 'facebook-description';
		case AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE:
			return 'twitter-title';
		case AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION:
			return 'twitter-description';
	}

	return null;
}


/**
 * Return the available fallback options for a metadata identifier.
 *
 * @param string $metadata_identifier Metadata identifier.
 *
 * @return array<string,string> Allowed fallback options.
 */
function ai4seo_get_metadata_fallback_allowed_values( string $metadata_identifier ): array {
	// Preserve key order because the settings page renders these translated choices directly.
	switch ( $metadata_identifier ) {
		case 'meta-title':
			return array(
				'no-fallback'    => esc_html__( 'No fallback', 'ai-for-seo' ),
				'post-title'     => esc_html__( 'Use post title', 'ai-for-seo' ),
				'facebook-title' => esc_html__( 'Use Facebook title', 'ai-for-seo' ),
				'twitter-title'  => esc_html__( 'Use Twitter title', 'ai-for-seo' ),
			);

		case 'meta-description':
			return array(
				'no-fallback'          => esc_html__( 'No fallback', 'ai-for-seo' ),
				'post-excerpt'         => esc_html__( 'Use post excerpt', 'ai-for-seo' ),
				'content'              => esc_html__( 'Use post content', 'ai-for-seo' ),
				'facebook-description' => esc_html__( 'Use Facebook description', 'ai-for-seo' ),
				'twitter-description'  => esc_html__( 'Use Twitter description', 'ai-for-seo' ),
			);

		case 'facebook-title':
			return array(
				'no-fallback'   => esc_html__( 'No fallback', 'ai-for-seo' ),
				'post-title'    => esc_html__( 'Use post title', 'ai-for-seo' ),
				'meta-title'    => esc_html__( 'Use meta title', 'ai-for-seo' ),
				'twitter-title' => esc_html__( 'Use Twitter title', 'ai-for-seo' ),
			);

		case 'facebook-description':
			return array(
				'no-fallback'         => esc_html__( 'No fallback', 'ai-for-seo' ),
				'post-excerpt'        => esc_html__( 'Use post excerpt', 'ai-for-seo' ),
				'content'             => esc_html__( 'Use post content', 'ai-for-seo' ),
				'meta-description'    => esc_html__( 'Use meta description', 'ai-for-seo' ),
				'twitter-description' => esc_html__( 'Use Twitter description', 'ai-for-seo' ),
			);

		case 'twitter-title':
			return array(
				'no-fallback'    => esc_html__( 'No fallback', 'ai-for-seo' ),
				'post-title'     => esc_html__( 'Use post title', 'ai-for-seo' ),
				'meta-title'     => esc_html__( 'Use meta title', 'ai-for-seo' ),
				'facebook-title' => esc_html__( 'Use Facebook title', 'ai-for-seo' ),
			);

		case 'twitter-description':
			return array(
				'no-fallback'          => esc_html__( 'No fallback', 'ai-for-seo' ),
				'post-excerpt'         => esc_html__( 'Use post excerpt', 'ai-for-seo' ),
				'content'              => esc_html__( 'Use post content', 'ai-for-seo' ),
				'meta-description'     => esc_html__( 'Use meta description', 'ai-for-seo' ),
				'facebook-description' => esc_html__( 'Use Facebook description', 'ai-for-seo' ),
			);
	}

	return array();
}


/**
 * Return the available fallback identifiers for a metadata identifier.
 *
 * @param string $metadata_identifier Metadata identifier.
 *
 * @return array<int,string> Allowed fallback identifiers.
 */
function ai4seo_get_metadata_fallback_allowed_value_identifiers( string $metadata_identifier ): array {
	// Keep validation independent from translated labels by returning the same contract as identifiers only.
	switch ( $metadata_identifier ) {
		case 'meta-title':
			return array(
				'no-fallback',
				'post-title',
				'facebook-title',
				'twitter-title',
			);

		case 'meta-description':
			return array(
				'no-fallback',
				'post-excerpt',
				'content',
				'facebook-description',
				'twitter-description',
			);

		case 'facebook-title':
			return array(
				'no-fallback',
				'post-title',
				'meta-title',
				'twitter-title',
			);

		case 'facebook-description':
			return array(
				'no-fallback',
				'post-excerpt',
				'content',
				'meta-description',
				'twitter-description',
			);

		case 'twitter-title':
			return array(
				'no-fallback',
				'post-title',
				'meta-title',
				'facebook-title',
			);

		case 'twitter-description':
			return array(
				'no-fallback',
				'post-excerpt',
				'content',
				'meta-description',
				'facebook-description',
			);
	}

	return array();
}


/**
 * Get a shortened fallback text from the post title.
 *
 * @param int $post_id Current post ID.
 *
 * @return string Prepared fallback text.
 */
function ai4seo_get_metadata_fallback_post_title( int $post_id ): string {
	// Normalize the WordPress title before applying the title-specific fallback length.
	$post_title = get_the_title( $post_id );
	$post_title = ai4seo_prepare_metadata_fallback_text( $post_title );

	// Skip the length helper when WordPress did not provide usable source text.
	if ( '' === $post_title ) {
		return '';
	}

	return ai4seo_limit_metadata_fallback_text( $post_title, 100 );
}


/**
 * Get a shortened fallback text from the post excerpt.
 *
 * @param int $post_id Current post ID.
 *
 * @return string Prepared fallback text.
 */
function ai4seo_get_metadata_fallback_post_excerpt( int $post_id ): string {
	// Normalize the WordPress excerpt before applying the description fallback length.
	$post_excerpt = get_the_excerpt( $post_id );
	$post_excerpt = ai4seo_prepare_metadata_fallback_text( $post_excerpt );

	// Skip the length helper when WordPress did not provide usable source text.
	if ( '' === $post_excerpt ) {
		return '';
	}

	return ai4seo_limit_metadata_fallback_text( $post_excerpt, 150 );
}


/**
 * Get a shortened fallback text from the post content.
 *
 * @param int $post_id Current post ID.
 *
 * @return string Prepared fallback text.
 */
function ai4seo_get_metadata_fallback_post_content( int $post_id ): string {
	// Read raw post content so fallback cleanup owns markup removal and whitespace normalization.
	$post_content = get_post_field( 'post_content', $post_id, 'raw' );
	$post_content = ai4seo_prepare_metadata_fallback_text( $post_content );

	// Skip the length helper when WordPress did not provide usable source text.
	if ( '' === $post_content ) {
		return '';
	}

	return ai4seo_limit_metadata_fallback_text( $post_content, 150 );
}


/**
 * Prepare a string for fallback usage by removing markup and normalizing whitespace.
 *
 * @param mixed $text Input text.
 *
 * @return string Cleaned text.
 */
function ai4seo_prepare_metadata_fallback_text( $text ): string {
	// Reject non-string WordPress values instead of coercing unsupported source types.
	if ( ! is_string( $text ) ) {
		return '';
	}

	// Remove rendered markup and collapse all Unicode whitespace before measuring fallback length.
	$text = wp_strip_all_tags( $text );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', $text );

	// preg_replace() may return null for invalid UTF-8, which should behave like an unavailable source.
	if ( ! is_string( $text ) ) {
		$text = '';
	}

	return trim( $text );
}


/**
 * Limit fallback text length while keeping current words intact where possible.
 *
 * @param string $text        Prepared text.
 * @param int    $base_length Hard character limit before soft extension.
 *
 * @return string Shortened text.
 */
function ai4seo_limit_metadata_fallback_text( string $text, int $base_length ): string {
	// Preserve empty inputs without invoking multibyte helpers.
	if ( '' === $text ) {
		return '';
	}

	// Values within the base limit require no soft extension or additional normalization.
	if ( ai4seo_mb_strlen( $text ) <= $base_length ) {
		return $text;
	}

	// Inspect only the next 20 characters so a fallback can finish the current word without growing freely.
	$base_snippet      = ai4seo_mb_substr( $text, 0, $base_length );
	$remaining_segment = ai4seo_mb_substr( $text, $base_length, 20 );

	// Build the optional soft extension separately so spacing remains correct at the hard split point.
	$append = '';

	if ( '' !== $remaining_segment ) {
		$remaining_segment_trimmed  = ltrim( $remaining_segment );
		$leading_whitespace_removed = ai4seo_mb_strlen( $remaining_segment_trimmed ) !== ai4seo_mb_strlen( $remaining_segment );

		// Capture at most the remainder of the split word from the bounded look-ahead segment.
		if ( '' !== $remaining_segment_trimmed && preg_match( '/^\S{0,20}/u', $remaining_segment_trimmed, $match ) && isset( $match[0] ) ) {
			$append_segment = $match[0];

			// Restore one separator only when ltrim() removed the boundary whitespace.
			if ( $leading_whitespace_removed && '' !== $append_segment && ! preg_match( '/\s$/u', $base_snippet ) ) {
				$append = ' ' . $append_segment;
			} else {
				$append = $append_segment;
			}
		}
	}

	// Normalize only boundary whitespace after recombining the fixed base and optional word extension.
	$result = $base_snippet . $append;

	return trim( $result );
}
