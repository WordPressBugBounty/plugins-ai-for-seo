<?php
/**
 * Processes metadata editor updates from save-anything requests.
 *
 * @since 2.0.0
 *
 * @package AI_For_SEO
 */

// Prevent direct execution because this processor depends on the loaded WordPress and plugin runtime.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes metadata editor values from sanitized save-anything data.
 *
 * @param array $upcoming_save_anything_updates Sanitized updates shared by the ordered save-anything processors.
 * @return WP_Error|null Error on failure, null on success or no-op.
 */
function ai4seo_process_save_anything_metadata_editor_values( array &$upcoming_save_anything_updates ) {
	// Preserve the category's existing silent no-op behavior for users without plugin-management rights.
	if ( ! ai4seo_can_manage_this_plugin() ) {
		return null;
	}

	// Ignore save-anything requests that do not target the metadata editor.
	if ( ! defined( 'AI4SEO_METADATA_DETAILS' ) || ! isset( $upcoming_save_anything_updates['metadata_editor_post_id'] ) ) {
		return null;
	}

	// Preserve the editor's existing integer coercion before passing the target to postmeta helpers.
	$ai4seo_this_post_id = intval( $upcoming_save_anything_updates['metadata_editor_post_id'] );

	// Track field presence separately because an empty instruction value intentionally clears its postmeta.
	$ai4seo_custom_instructions_were_submitted = array_key_exists( 'metadata_editor_custom_instructions', $upcoming_save_anything_updates );

	// Collect the complete validated metadata set before any editor-level values are written.
	$ai4seo_new_metadata = array();

	// Validate and normalize every submitted metadata field before writing any editor values.
	foreach ( AI4SEO_METADATA_DETAILS as $ai4seo_metadata_identifier => $ai4seo_metadata_details ) {
		$ai4seo_metadata_input_name = 'metadata_' . $ai4seo_metadata_identifier;

		// The shared save endpoint can contain unrelated categories, so process only present metadata fields.
		if ( ! isset( $upcoming_save_anything_updates[ $ai4seo_metadata_input_name ] ) ) {
			continue;
		}

		$ai4seo_metadata_input_value = $upcoming_save_anything_updates[ $ai4seo_metadata_input_name ];

		// Prefer the configured editor label while retaining a readable fallback for incomplete field definitions.
		if ( isset( $ai4seo_metadata_details['name'] ) && is_string( $ai4seo_metadata_details['name'] ) && '' !== $ai4seo_metadata_details['name'] ) {
			$ai4seo_metadata_field_label = $ai4seo_metadata_details['name'];
		} else {
			$ai4seo_metadata_field_label = ucwords( str_replace( '-', ' ', $ai4seo_metadata_identifier ) );
		}

		// Reject compound request values before passing them to string-only editor validation helpers.
		if ( ! is_scalar( $ai4seo_metadata_input_value ) ) {
			return new WP_Error(
				6411221025,
				sprintf(
					/* translators: %s: Field label */
					esc_html__( 'The value for "%s" must be text. Please refresh the page and try again.', 'ai-for-seo' ),
					esc_html( $ai4seo_metadata_field_label )
				)
			);
		}

		$ai4seo_length_limit = ai4seo_get_max_editor_input_length( $ai4seo_metadata_identifier );

		// Enforce the same per-field length contract used by the editor before any postmeta can be changed.
		if ( ai4seo_mb_strlen( (string) $ai4seo_metadata_input_value ) > $ai4seo_length_limit ) {
			return new WP_Error(
				5311221025,
				sprintf(
					/* translators: 1: Field label, 2: Length limit */
					esc_html__( 'The value for "%1$s" exceeds the maximum allowed length of %2$s characters. Please shorten your input and try again.', 'ai-for-seo' ),
					esc_html( $ai4seo_metadata_field_label ),
					esc_html( ai4seo_format_number_i18n( $ai4seo_length_limit ) )
				)
			);
		}

		// Canonicalize comma-separated keywords so saved editor values retain the plugin's expected format.
		if ( 'keywords' === $ai4seo_metadata_identifier ) {
			// Treat a non-string keyword payload as an intentional empty value, matching the previous include handler.
			if ( ! is_string( $ai4seo_metadata_input_value ) ) {
				$ai4seo_new_metadata[ $ai4seo_metadata_identifier ] = '';
				continue;
			}

			$ai4seo_metadata_keywords = array_map( 'trim', explode( ',', $ai4seo_metadata_input_value ) );
			$ai4seo_metadata_keywords = array_filter(
				$ai4seo_metadata_keywords,
				// Preserve zero-like keywords while removing only genuinely empty entries.
				static function ( $ai4seo_keyword ) {
					return '' !== $ai4seo_keyword;
				}
			);

			// Avoid passing an empty keyword list through the general scalar normalizer.
			if ( ! $ai4seo_metadata_keywords ) {
				$ai4seo_new_metadata[ $ai4seo_metadata_identifier ] = '';
				continue;
			}

			// Sanitize and deduplicate keywords before rebuilding the canonical comma-separated value.
			$ai4seo_metadata_keywords    = array_map( 'sanitize_text_field', $ai4seo_metadata_keywords );
			$ai4seo_metadata_keywords    = array_unique( $ai4seo_metadata_keywords );
			$ai4seo_metadata_input_value = implode( ', ', $ai4seo_metadata_keywords );
		}

		// Apply the shared editor normalization after field-specific handling so all stored values use one format.
		$ai4seo_metadata_input_value = ai4seo_normalize_editor_input_value( $ai4seo_metadata_input_value );

		$ai4seo_new_metadata[ $ai4seo_metadata_identifier ] = $ai4seo_metadata_input_value;
	}

	// Keep the existing explicit error for editor requests that contain neither values nor instructions.
	if ( ! $ai4seo_new_metadata && ! $ai4seo_custom_instructions_were_submitted ) {
		return new WP_Error(
			5611221025,
			esc_html__( 'No metadata values to update.', 'ai-for-seo' )
		);
	}

	// Save entry-level instructions only after all metadata values pass validation to avoid partial form updates.
	if ( $ai4seo_custom_instructions_were_submitted ) {
		$ai4seo_custom_instructions_saved = ai4seo_save_custom_instructions_postmeta(
			$ai4seo_this_post_id,
			AI4SEO_POST_META_METADATA_CUSTOM_INSTRUCTIONS_META_KEY,
			$upcoming_save_anything_updates['metadata_editor_custom_instructions']
		);

		// Return the persistence failure to the dispatcher so it owns the AJAX transport response.
		if ( ! $ai4seo_custom_instructions_saved ) {
			return new WP_Error(
				6111221025,
				esc_html__( 'Failed to update custom instructions. Please try again.', 'ai-for-seo' )
			);
		}
	}

	// Stop after an instruction-only save so metadata coverage and generation status remain unchanged.
	if ( ! $ai4seo_new_metadata ) {
		return null;
	}

	// Persist the complete validated metadata collection through the existing active-metadata mechanism.
	$ai4seo_metadata_was_updated = ai4seo_update_active_metadata( $ai4seo_this_post_id, $ai4seo_new_metadata, true );

	// Do not refresh derived status when the underlying metadata write did not complete.
	if ( ! $ai4seo_metadata_was_updated ) {
		return new WP_Error(
			3518161025,
			esc_html__( 'Failed to update metadata. Please try again.', 'ai-for-seo' )
		);
	}

	// Refresh both derived metadata coverage and generation queues after a successful editor write.
	ai4seo_refresh_one_posts_metadata_coverage_status( $ai4seo_this_post_id );
	ai4seo_remove_post_ids_from_all_generation_status_options( $ai4seo_this_post_id );

	return null;
}
