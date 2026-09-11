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
	// Preserve the category's silent no-op behavior outside the configured content boundary.
	if ( ! ai4seo_can_use_plugin_content() ) {
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

	// Instruction-only saves do not affect generation ownership or derived state.
	if ( ! $ai4seo_new_metadata ) {
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

		return null;
	}

	// Keep queue reservation, primary persistence, coverage publication, and ownership verification under one fence.
	$ai4seo_metadata_update_details       = array();
	$ai4seo_fenced_save_details           = array();
	$ai4seo_custom_instructions_saved     = ! $ai4seo_custom_instructions_were_submitted;
	$ai4seo_submitted_custom_instructions = $ai4seo_custom_instructions_were_submitted
		? $upcoming_save_anything_updates['metadata_editor_custom_instructions']
		: '';

	ai4seo_save_manual_editor_values_with_generation_fence(
		$ai4seo_this_post_id,
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
		static function () use (
			$ai4seo_this_post_id,
			$ai4seo_new_metadata,
			$ai4seo_custom_instructions_were_submitted,
			$ai4seo_submitted_custom_instructions,
			&$ai4seo_metadata_update_details,
			&$ai4seo_custom_instructions_saved
		): bool {
			// Validate before instructions or providers can commit; the locked writer still rechecks later.
			$preflight_succeeded = false;
			$preflight_reason    = '';
			ai4seo_read_authoritative_active_metadata_postmeta_snapshot( $ai4seo_this_post_id, $preflight_succeeded, $preflight_reason );

			// A failed snapshot must stop every write while retaining its phase for the shared error response.
			if ( ! $preflight_succeeded ) {
				$ai4seo_metadata_update_details['commit_state'] = 'not_committed';
				$ai4seo_metadata_update_details['diagnostic']   = ai4seo_record_metadata_save_diagnostic( 3518161025, 'preflight', $preflight_reason, array( 'post_id' => $ai4seo_this_post_id ) );
				return false;
			}

			// Instructions participate in the same reservation and must succeed before provider synchronization.
			if ( $ai4seo_custom_instructions_were_submitted ) {
				$ai4seo_custom_instructions_saved = ai4seo_save_custom_instructions_postmeta(
					$ai4seo_this_post_id,
					AI4SEO_POST_META_METADATA_CUSTOM_INSTRUCTIONS_META_KEY,
					$ai4seo_submitted_custom_instructions
				);

				if ( ! $ai4seo_custom_instructions_saved ) {
					$ai4seo_metadata_update_details['commit_state'] = 'not_committed';
					return false;
				}
			}

			// Detailed outcomes distinguish primary persistence from optional provider synchronization.
			ai4seo_update_active_metadata(
				$ai4seo_this_post_id,
				$ai4seo_new_metadata,
				true,
				$ai4seo_metadata_update_details
			);

			// A third-party warning is not a SOOZ persistence failure and must still commit coverage.
			return ! empty( $ai4seo_metadata_update_details['active_metadata_succeeded'] );
		},
		$ai4seo_fenced_save_details,
		$ai4seo_metadata_update_details
	);

	// Success and failure responses share the same confirmed values and diagnostic context.
	$ai4seo_save_response = ai4seo_build_metadata_editor_save_response(
		$ai4seo_this_post_id,
		$ai4seo_new_metadata,
		$ai4seo_metadata_update_details,
		$ai4seo_fenced_save_details
	);

	// Reservation failures take precedence because the persistence callback may never have run.
	if ( empty( $ai4seo_fenced_save_details['reservation_succeeded'] ) ) {
		return new WP_Error(
			7111221026,
			esc_html__( 'Metadata is currently being generated or could not be reserved for editing. Please wait a moment and try again.', 'ai-for-seo' ),
			$ai4seo_save_response
		);
	}

	// Preflight failures can explicitly assure the editor that no provider values were changed.
	if ( 'preflight' === ( $ai4seo_metadata_update_details['diagnostic']['phase'] ?? '' ) ) {
		return new WP_Error(
			3518161025,
			esc_html__( 'Stored SOOZ metadata could not be read unambiguously. No changes were made. Please contact support.', 'ai-for-seo' ),
			$ai4seo_save_response
		);
	}

	// Keep instruction failures separate from metadata failures for the dispatcher's existing error contract.
	if ( $ai4seo_custom_instructions_were_submitted && ! $ai4seo_custom_instructions_saved ) {
		return new WP_Error(
			6111221025,
			esc_html__( 'Failed to update custom instructions. Please try again.', 'ai-for-seo' ),
			$ai4seo_save_response
		);
	}

	// Once primary values persisted, any coverage, ownership, or release ambiguity must fail closed.
	if ( ! empty( $ai4seo_fenced_save_details['persistence_succeeded'] )
		&& ( empty( $ai4seo_fenced_save_details['coverage_succeeded'] ) || empty( $ai4seo_fenced_save_details['release_succeeded'] ) )
	) {
		return new WP_Error(
			7111221025,
			esc_html__( 'Metadata was saved and reserved from generation, but its coverage state could not be secured. Please refresh the page and try again.', 'ai-for-seo' ),
			$ai4seo_save_response
		);
	}

	// Primary storage failures retain confirmed partial results so the browser can avoid restoring stale state.
	if ( empty( $ai4seo_metadata_update_details['active_metadata_succeeded'] ) ) {
		$message = 'not_saved' === $ai4seo_save_response['metadata_editor']['status']
			? __( 'SOOZ metadata could not be saved. Please try again or contact support.', 'ai-for-seo' )
			: __( 'SOOZ metadata could not be fully saved. Some changes may already be stored in your SEO plugin. Reload this entry before trying again.', 'ai-for-seo' );
		return new WP_Error( 3518161025, $message, $ai4seo_save_response );
	}

	return $ai4seo_save_response;
}

/**
 * Build one checked editor response for successful and partially failed saves.
 *
 * @param int   $ai4seo_this_post_id Target post.
 * @param array $ai4seo_new_metadata Submitted field identifiers and values.
 * @param array $ai4seo_metadata_update_details Observed storage outcomes.
 * @param array $ai4seo_fenced_save_details Generation fence outcomes.
 * @return array Additive response with checked values and safe diagnostics.
 */
function ai4seo_build_metadata_editor_save_response( int $ai4seo_this_post_id, array $ai4seo_new_metadata, array $ai4seo_metadata_update_details, array $ai4seo_fenced_save_details ): array {
	global $wpdb;

	// Primary persistence and the overall outcome differ when optional synchronization fails.
	$ai4seo_overall_save_succeeded    = ! empty( $ai4seo_metadata_update_details['overall_succeeded'] );
	$ai4seo_active_metadata_succeeded = ! empty( $ai4seo_metadata_update_details['active_metadata_succeeded'] );
	$ai4seo_reload_required           = $ai4seo_active_metadata_succeeded
		&& ( empty( $ai4seo_fenced_save_details['coverage_succeeded'] ) || empty( $ai4seo_fenced_save_details['release_succeeded'] ) );
	$ai4seo_warnings                  = $ai4seo_metadata_update_details['warnings'] ?? array();
	$ai4seo_diagnostic                = $ai4seo_metadata_update_details['diagnostic'] ?? ( $ai4seo_metadata_update_details['diagnostics'][0] ?? array() );
	$ai4seo_commit_state              = $ai4seo_metadata_update_details['commit_state'] ?? 'not_committed';
	$ai4seo_reload_required           = $ai4seo_reload_required || 'possibly_committed' === $ai4seo_commit_state;

	// Fence failures override storage diagnostics because they determine whether editing can safely continue.
	if ( empty( $ai4seo_fenced_save_details['reservation_succeeded'] ) ) {
		$ai4seo_commit_state    = 'not_committed';
		$ai4seo_reload_required = false;
		$ai4seo_diagnostic      = ai4seo_record_metadata_save_diagnostic( 7111221026, 'generation_fence', 'reservation_failed', array( 'post_id' => $ai4seo_this_post_id ) );
	} elseif ( ! empty( $ai4seo_fenced_save_details['persistence_succeeded'] ) && $ai4seo_reload_required ) {
		$ai4seo_diagnostic = ai4seo_record_metadata_save_diagnostic( 7111221025, 'generation_fence', 'coverage_or_release_failed', array( 'post_id' => $ai4seo_this_post_id ) );
	}

	// Return authoritative stored values rather than assuming that submitted values survived every hook.
	$ai4seo_persisted_metadata = array();
	if ( $ai4seo_active_metadata_succeeded ) {
		try {
			$own_read_succeeded = false;
			$snapshot           = ai4seo_read_authoritative_active_metadata_postmeta_snapshot( $ai4seo_this_post_id, $own_read_succeeded );
			if ( $own_read_succeeded ) {
				$ai4seo_persisted_metadata = array_intersect_key( $snapshot['active_metadata'], $ai4seo_new_metadata );
			} else {
				$ai4seo_reload_required = true;
				$ai4seo_diagnostic      = ai4seo_record_metadata_save_diagnostic( 3518161025, 'response_read', 'storage_read_failed', array( 'post_id' => $ai4seo_this_post_id ) );
			}
		} catch ( Throwable $throwable ) {
			$ai4seo_reload_required = true;
			$ai4seo_diagnostic      = ai4seo_record_metadata_save_diagnostic( 3518161025, 'response_read', 'storage_read_exception', array( 'post_id' => $ai4seo_this_post_id ), $throwable );
		}
	}

	// A partial third-party result is still a successful editor save because SOOZ owns the submitted values.
	$ai4seo_third_party_sync_failed  = ! $ai4seo_overall_save_succeeded
		&& $ai4seo_active_metadata_succeeded
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
				__( 'Metadata was saved in SOOZ, but synchronization with %s did not complete successfully.', 'ai-for-seo' ),
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

	// Provider guidance joins cleanup and readback warnings in the shared response contract.
	if ( $ai4seo_third_party_sync_warning ) {
		$ai4seo_warnings[] = $ai4seo_third_party_sync_warning;
	}

	// Read back only the Yoast fields that this installation is configured to synchronize.
	$ai4seo_yoast_metadata                  = array();
	$ai4seo_yoast_sync_metadata_identifiers = ai4seo_get_third_party_seo_plugin_sync_metadata_identifiers(
		AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO
	);
	$ai4seo_third_party_seo_plugin_details  = ai4seo_get_third_party_seo_plugin_details();
	$ai4seo_yoast_plugin_details            = $ai4seo_third_party_seo_plugin_details[ AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO ] ?? array();
	$ai4seo_yoast_postmeta_keys             = $ai4seo_yoast_plugin_details['generation-field-postmeta-keys'] ?? array();

	// A rejected save must not update the browser's provider state from an unrelated readback.
	if ( 'not_committed' === $ai4seo_commit_state ) {
		$ai4seo_yoast_sync_metadata_identifiers = array();
	}

	// A cache invalidation failure makes the following provider reads unsafe to present as confirmed values.
	if ( $ai4seo_yoast_sync_metadata_identifiers ) {
		try {
			wp_cache_delete( $ai4seo_this_post_id, 'post_meta' );
		} catch ( Throwable $throwable ) {
			$ai4seo_yoast_sync_metadata_identifiers = array();
			$ai4seo_reload_required                 = true;
			$ai4seo_diagnostic                      = ai4seo_record_metadata_save_diagnostic( 3518161025, 'response_read', 'cache_invalidation_exception', array( 'post_id' => $ai4seo_this_post_id ), $throwable );
		}
	}

	// Return only submitted fields that were configured for Yoast synchronization in this save request.
	foreach ( $ai4seo_yoast_sync_metadata_identifiers as $ai4seo_yoast_sync_metadata_identifier ) {
		if ( ! array_key_exists( $ai4seo_yoast_sync_metadata_identifier, $ai4seo_new_metadata )
			|| empty( $ai4seo_yoast_postmeta_keys[ $ai4seo_yoast_sync_metadata_identifier ] ) ) {
			continue;
		}

		// Use WordPress's metadata API so filters and cache semantics match the write path that just completed.
		try {
			$wpdb->last_error            = '';
			$ai4seo_yoast_postmeta_value = get_post_meta(
				$ai4seo_this_post_id,
				sanitize_text_field( $ai4seo_yoast_postmeta_keys[ $ai4seo_yoast_sync_metadata_identifier ] ),
				true
			);
			if ( $wpdb->last_error || ! is_scalar( $ai4seo_yoast_postmeta_value ) ) {
				$ai4seo_reload_required = true;
				$ai4seo_diagnostic      = ai4seo_record_metadata_save_diagnostic(
					3518161025,
					'response_read',
					'provider_read_failed',
					array(
						'post_id'  => $ai4seo_this_post_id,
						'provider' => 'yoast-seo',
						'field'    => $ai4seo_yoast_sync_metadata_identifier,
					)
				);
				continue;
			}
		} catch ( Throwable $throwable ) {
			$ai4seo_reload_required = true;
			$ai4seo_diagnostic      = ai4seo_record_metadata_save_diagnostic(
				3518161025,
				'response_read',
				'provider_read_exception',
				array(
					'post_id'  => $ai4seo_this_post_id,
					'provider' => 'yoast-seo',
					'field'    => $ai4seo_yoast_sync_metadata_identifier,
				),
				$throwable
			);
			continue;
		}

		$ai4seo_yoast_metadata[ $ai4seo_yoast_sync_metadata_identifier ] = ai4seo_sanitize_editor_field_value( $ai4seo_yoast_postmeta_value );
	}

	// Read back successful AIOSEO fields from its canonical table so retained editors cannot restore stale state.
	$ai4seo_aioseo_metadata                    = array();
	$ai4seo_aioseo_sync_metadata_identifiers   = ai4seo_get_third_party_seo_plugin_sync_metadata_identifiers(
		AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO
	);
	$ai4seo_failed_aioseo_metadata_identifiers = $ai4seo_failed_third_party_syncs[ AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO ] ?? array();
	$ai4seo_persisted_aioseo_metadata          = array();

	if ( $ai4seo_aioseo_sync_metadata_identifiers && 'not_committed' !== $ai4seo_commit_state ) {
		try {
			$provider_read_succeeded            = false;
			$ai4seo_aioseo_metadata_by_post_ids = ai4seo_read_all_in_one_seo_metadata_by_post_ids( array( $ai4seo_this_post_id ), $provider_read_succeeded );
			$ai4seo_persisted_aioseo_metadata   = $provider_read_succeeded ? ( $ai4seo_aioseo_metadata_by_post_ids[ $ai4seo_this_post_id ] ?? array() ) : array();
			$ai4seo_reload_required             = $ai4seo_reload_required || ! $provider_read_succeeded;
		} catch ( Throwable $throwable ) {
			$ai4seo_reload_required = true;
			$ai4seo_diagnostic      = ai4seo_record_metadata_save_diagnostic(
				3518161025,
				'response_read',
				'provider_read_exception',
				array(
					'post_id'  => $ai4seo_this_post_id,
					'provider' => 'aioseo',
				),
				$throwable
			);
		}
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

	// Explain why the browser must retain the current editor and stop automatic navigation.
	if ( $ai4seo_reload_required ) {
		$ai4seo_warnings[] = __( 'The saved state could not be fully confirmed. Reload this entry before updating it again.', 'ai-for-seo' );
	}

	// Classify commitment first, then promote only fully confirmed, warning-free primary saves.
	$ai4seo_save_status = 'partial';
	if ( 'not_committed' === $ai4seo_commit_state ) {
		$ai4seo_save_status = 'not_saved';
	} elseif ( 'possibly_committed' === $ai4seo_commit_state ) {
		$ai4seo_save_status = 'unknown';
	}

	if ( $ai4seo_active_metadata_succeeded && ! $ai4seo_warnings && ! $ai4seo_reload_required ) {
		$ai4seo_save_status = 'complete';
	}

	// Every response carries a request identity even when no lower-level failure supplied a diagnostic.
	if ( ! $ai4seo_diagnostic ) {
		$ai4seo_diagnostic = ai4seo_record_metadata_save_diagnostic(
			'complete' === $ai4seo_save_status ? 0 : 3518161025,
			'metadata',
			$ai4seo_save_status,
			array( 'post_id' => $ai4seo_this_post_id )
		);
	}

	// Reuse the same deduplicated warnings for structured clients and the legacy combined warning field.
	$ai4seo_warnings = array_values( array_unique( $ai4seo_warnings ) );
	return array(
		'diagnostic'      => $ai4seo_diagnostic,
		'metadata_editor' => array(
			'post_id'                  => $ai4seo_this_post_id,
			'metadata'                 => $ai4seo_persisted_metadata,
			'status'                   => $ai4seo_save_status,
			'reload_required'          => $ai4seo_reload_required,
			'warnings'                 => $ai4seo_warnings,
			'yoast_metadata'           => $ai4seo_yoast_metadata,
			'aioseo_metadata'          => $ai4seo_aioseo_metadata,
			'third_party_sync_warning' => implode( ' ', $ai4seo_warnings ),
		),
	);
}
