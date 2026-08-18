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
 * @return WP_Error|array|null Error on failure, response data on metadata success, or null on no-op.
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

	// The shared save dispatcher authorizes the plugin, while this handler authorizes its concrete post target.
	if ( ! ai4seo_can_edit_post( $ai4seo_this_post_id ) ) {
		return new WP_Error(
			6811221025,
			esc_html__( 'You are not allowed to edit this entry.', 'ai-for-seo' )
		);
	}

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
	$ai4seo_metadata_update_details   = array();
	$ai4seo_metadata_update_succeeded = ai4seo_update_active_metadata( $ai4seo_this_post_id, $ai4seo_new_metadata, true, $ai4seo_metadata_update_details );

	// Refresh derived state whenever SOOZ persistence succeeded, including partial third-party synchronization.
	if ( ! empty( $ai4seo_metadata_update_details['active_metadata_succeeded'] ) ) {
		ai4seo_refresh_one_posts_metadata_coverage_status( $ai4seo_this_post_id );
		ai4seo_remove_post_ids_from_all_generation_status_options( $ai4seo_this_post_id );
	}

	// A partial third-party result is still a successful editor save because SOOZ owns the submitted values.
	$ai4seo_third_party_sync_failed = ! $ai4seo_metadata_update_succeeded
		&& ! empty( $ai4seo_metadata_update_details['active_metadata_succeeded'] )
		&& empty( $ai4seo_metadata_update_details['third_party_sync_succeeded'] );
	$ai4seo_failed_third_party_syncs = $ai4seo_metadata_update_details['failed_third_party_syncs'] ?? array();
	$ai4seo_third_party_sync_warning = '';

	if ( $ai4seo_third_party_sync_failed ) {
		$ai4seo_failed_plugin_names = ai4seo_get_third_party_seo_plugin_names(
			array_keys( $ai4seo_failed_third_party_syncs )
		);

		if ( $ai4seo_failed_plugin_names ) {
			// Keep warning text unescaped in the JSON payload; the toast renderer inserts it with jQuery.text().
			$ai4seo_third_party_sync_warning = sprintf(
				/* translators: %s: Comma-separated third-party SEO plugin names. */
				__( 'Metadata was saved in SOOZ, but it could not be synchronized with %s.', 'ai-for-seo' ),
				implode( ', ', $ai4seo_failed_plugin_names )
			);
		} else {
			$ai4seo_third_party_sync_warning = __(
				'Metadata was saved in SOOZ, but one or more third-party SEO plugins could not be synchronized.',
				'ai-for-seo'
			);
		}

		// Add storage-initialization guidance only for integrations that failed and commonly need it.
		$ai4seo_squirrly_sync_failed = isset( $ai4seo_failed_third_party_syncs[ AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO ] );
		$ai4seo_aioseo_sync_failed   = isset( $ai4seo_failed_third_party_syncs[ AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO ] );

		if ( $ai4seo_squirrly_sync_failed && $ai4seo_aioseo_sync_failed ) {
			$ai4seo_third_party_sync_warning .= ' ' . __(
				'A common fix is to save this entry once in the Squirrly SEO Snippet editor and update it once in WordPress so All in One SEO can initialize its metadata, then save again in SOOZ.',
				'ai-for-seo'
			);
		} elseif ( $ai4seo_squirrly_sync_failed ) {
			$ai4seo_third_party_sync_warning .= ' ' . __(
				'A common fix is to open the Squirrly SEO Snippet editor for this entry, click its Save button once, and then save again in SOOZ.',
				'ai-for-seo'
			);
		} elseif ( $ai4seo_aioseo_sync_failed ) {
			$ai4seo_third_party_sync_warning .= ' ' . __(
				'A common fix is to update this entry once in WordPress so All in One SEO can initialize its metadata, and then save again in SOOZ.',
				'ai-for-seo'
			);
		}
	}

	// Genuine SOOZ failures and unclassified failures retain the existing generic error response.
	if ( ! $ai4seo_metadata_update_succeeded && ! $ai4seo_third_party_sync_failed ) {
		return new WP_Error(
			3518161025,
			esc_html__( 'Failed to update metadata. Please try again.', 'ai-for-seo' )
		);
	}

	// Read back only the Yoast fields that this installation is configured to synchronize.
	$ai4seo_yoast_metadata                  = array();
	$ai4seo_yoast_sync_metadata_identifiers = ai4seo_get_third_party_seo_plugin_sync_metadata_identifiers(
		AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO
	);
	$ai4seo_third_party_seo_plugin_details  = ai4seo_get_third_party_seo_plugin_details();
	$ai4seo_yoast_plugin_details            = $ai4seo_third_party_seo_plugin_details[ AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO ] ?? array();
	$ai4seo_yoast_postmeta_keys             = $ai4seo_yoast_plugin_details['generation-field-postmeta-keys'] ?? array();

	// Return only submitted fields that were configured for Yoast synchronization in this save request.
	foreach ( $ai4seo_yoast_sync_metadata_identifiers as $ai4seo_yoast_sync_metadata_identifier ) {
		if ( ! array_key_exists( $ai4seo_yoast_sync_metadata_identifier, $ai4seo_new_metadata )
			|| empty( $ai4seo_yoast_postmeta_keys[ $ai4seo_yoast_sync_metadata_identifier ] ) ) {
			continue;
		}

		// Use WordPress's metadata API so filters and cache semantics match the write path that just completed.
		$ai4seo_yoast_postmeta_value = get_post_meta(
			$ai4seo_this_post_id,
			sanitize_text_field( $ai4seo_yoast_postmeta_keys[ $ai4seo_yoast_sync_metadata_identifier ] ),
			true
		);

		if ( ! is_scalar( $ai4seo_yoast_postmeta_value ) ) {
			continue;
		}

		$ai4seo_yoast_metadata[ $ai4seo_yoast_sync_metadata_identifier ] = ai4seo_sanitize_editor_field_value( $ai4seo_yoast_postmeta_value );
	}

	// Read back successful AIOSEO fields from its canonical table so retained editors cannot restore stale state.
	$ai4seo_aioseo_metadata                  = array();
	$ai4seo_aioseo_sync_metadata_identifiers = ai4seo_get_third_party_seo_plugin_sync_metadata_identifiers(
		AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO
	);
	$ai4seo_failed_aioseo_metadata_identifiers = $ai4seo_failed_third_party_syncs[ AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO ] ?? array();
	$ai4seo_persisted_aioseo_metadata           = array();

	if ( $ai4seo_aioseo_sync_metadata_identifiers ) {
		$ai4seo_aioseo_metadata_by_post_ids = ai4seo_read_all_in_one_seo_metadata_by_post_ids( array( $ai4seo_this_post_id ) );
		$ai4seo_persisted_aioseo_metadata    = $ai4seo_aioseo_metadata_by_post_ids[ $ai4seo_this_post_id ] ?? array();
	}

	// Omit failed fields so the browser never presents an unsaved SOOZ value as synchronized AIOSEO state.
	foreach ( $ai4seo_aioseo_sync_metadata_identifiers as $ai4seo_aioseo_sync_metadata_identifier ) {
		if ( ! array_key_exists( $ai4seo_aioseo_sync_metadata_identifier, $ai4seo_new_metadata )
			|| in_array( $ai4seo_aioseo_sync_metadata_identifier, $ai4seo_failed_aioseo_metadata_identifiers, true )
			|| ! array_key_exists( $ai4seo_aioseo_sync_metadata_identifier, $ai4seo_persisted_aioseo_metadata ) ) {
			continue;
		}

		$ai4seo_aioseo_metadata[ $ai4seo_aioseo_sync_metadata_identifier ] = ai4seo_sanitize_editor_field_value(
			$ai4seo_persisted_aioseo_metadata[ $ai4seo_aioseo_sync_metadata_identifier ]
		);
	}

	return array(
		'metadata_editor' => array(
			'post_id'                 => $ai4seo_this_post_id,
			'metadata'                => $ai4seo_new_metadata,
			'yoast_metadata'          => $ai4seo_yoast_metadata,
			'aioseo_metadata'         => $ai4seo_aioseo_metadata,
			'third_party_sync_warning' => $ai4seo_third_party_sync_warning,
		),
	);
}
