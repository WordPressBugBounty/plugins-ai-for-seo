<?php
/**
 * Processes attachment attributes editor updates from save-anything requests.
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
 * Processes attachment attributes editor values from sanitized save-anything data.
 *
 * @param array $upcoming_save_anything_updates Sanitized updates shared by the ordered save-anything processors.
 * @return WP_Error|null Error on failure, null on success or no-op.
 */
function ai4seo_process_save_anything_attachment_attributes_editor_values( array &$upcoming_save_anything_updates ) {
	// Preserve the category's existing silent no-op behavior for users without plugin-management rights.
	if ( ! ai4seo_can_manage_this_plugin() ) {
		return null;
	}

	// Ignore save-anything requests that do not target the attachment attributes editor.
	if ( ! defined( 'AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS' ) || ! isset( $upcoming_save_anything_updates['attachment_attributes_editor_post_id'] ) ) {
		return null;
	}

	// Preserve the editor's existing integer coercion before validating the attachment target.
	$ai4seo_this_attachment_post_id = intval( $upcoming_save_anything_updates['attachment_attributes_editor_post_id'] );

	// Track field presence separately because an empty instruction value intentionally clears its postmeta.
	$ai4seo_custom_instructions_were_submitted = array_key_exists( 'attachment_attributes_editor_custom_instructions', $upcoming_save_anything_updates );

	// Reject non-attachment targets before writing attachment-specific postmeta or derived coverage state.
	if ( ! ai4seo_is_wordpress_attachment_post( $ai4seo_this_attachment_post_id ) ) {
		return new WP_Error(
			6311221025,
			esc_html__( 'Media post not found.', 'ai-for-seo' )
		);
	}

	// Collect the complete validated attribute set before any attachment-level values are written.
	$ai4seo_new_attachment_attributes = array();

	// Validate every submitted media field before writing any attachment values.
	foreach ( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_attachment_attribute_identifier => $ai4seo_attachment_attribute_details ) {
		$ai4seo_attachment_attribute_input_name = 'attachment_attribute_' . $ai4seo_attachment_attribute_identifier;

		// The shared save endpoint can contain unrelated categories, so process only present attachment fields.
		if ( ! isset( $upcoming_save_anything_updates[ $ai4seo_attachment_attribute_input_name ] ) ) {
			continue;
		}

		$ai4seo_attachment_attribute_value = $upcoming_save_anything_updates[ $ai4seo_attachment_attribute_input_name ];

		// Prefer the configured editor label while retaining a readable fallback for incomplete field definitions.
		if ( isset( $ai4seo_attachment_attribute_details['name'] ) && is_string( $ai4seo_attachment_attribute_details['name'] ) && '' !== $ai4seo_attachment_attribute_details['name'] ) {
			$ai4seo_attachment_attribute_field_label = $ai4seo_attachment_attribute_details['name'];
		} else {
			$ai4seo_attachment_attribute_field_label = ucwords( str_replace( '-', ' ', $ai4seo_attachment_attribute_identifier ) );
		}

		// Reject compound request values before passing them to string-only editor validation helpers.
		if ( ! is_scalar( $ai4seo_attachment_attribute_value ) ) {
			return new WP_Error(
				6511221025,
				sprintf(
					/* translators: %s: Field label */
					esc_html__( 'The value for "%s" must be text. Please refresh the page and try again.', 'ai-for-seo' ),
					esc_html( $ai4seo_attachment_attribute_field_label )
				)
			);
		}

		$ai4seo_length_limit = ai4seo_get_max_editor_input_length( $ai4seo_attachment_attribute_identifier );

		// Enforce the same per-field length contract used by the editor before any postmeta can be changed.
		if ( ai4seo_mb_strlen( (string) $ai4seo_attachment_attribute_value ) > $ai4seo_length_limit ) {
			return new WP_Error(
				5511221025,
				sprintf(
					/* translators: 1: Field label, 2: Length limit */
					esc_html__( 'The value for "%1$s" exceeds the maximum allowed length of %2$s characters. Please shorten your input and try again.', 'ai-for-seo' ),
					esc_html( $ai4seo_attachment_attribute_field_label ),
					esc_html( ai4seo_format_number_i18n( $ai4seo_length_limit ) )
				)
			);
		}

		$ai4seo_new_attachment_attributes[ $ai4seo_attachment_attribute_identifier ] = $ai4seo_attachment_attribute_value;
	}

	// Keep the existing explicit error for editor requests that contain neither values nor instructions.
	if ( ! $ai4seo_new_attachment_attributes && ! $ai4seo_custom_instructions_were_submitted ) {
		return new WP_Error(
			5711221025,
			esc_html__( 'No attachment attributes were provided to update.', 'ai-for-seo' )
		);
	}

	// Save attachment-level instructions only after all attribute values pass validation to avoid partial form updates.
	if ( $ai4seo_custom_instructions_were_submitted ) {
		$ai4seo_custom_instructions_saved = ai4seo_save_custom_instructions_postmeta(
			$ai4seo_this_attachment_post_id,
			AI4SEO_POST_META_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS_META_KEY,
			$upcoming_save_anything_updates['attachment_attributes_editor_custom_instructions']
		);

		// Return the persistence failure to the dispatcher so it owns the AJAX transport response.
		if ( ! $ai4seo_custom_instructions_saved ) {
			return new WP_Error(
				6211221025,
				esc_html__( 'Failed to update custom instructions. Please try again.', 'ai-for-seo' )
			);
		}
	}

	// Stop after an instruction-only save so attachment coverage and generation status remain unchanged.
	if ( ! $ai4seo_new_attachment_attributes ) {
		return null;
	}

	// Persist attributes through the existing media update mechanism used by the editor workflow.
	if ( ! ai4seo_update_attachment_attributes( $ai4seo_this_attachment_post_id, $ai4seo_new_attachment_attributes, true ) ) {
		return new WP_Error(
			6611221025,
			esc_html__( 'Failed to update attachment attributes. Please try again.', 'ai-for-seo' )
		);
	}

	// Refresh both derived attachment coverage and generation queues after the editor write.
	ai4seo_refresh_one_posts_attachment_attributes_coverage( $ai4seo_this_attachment_post_id );
	ai4seo_remove_post_ids_from_all_generation_status_options( $ai4seo_this_attachment_post_id );

	return null;
}
