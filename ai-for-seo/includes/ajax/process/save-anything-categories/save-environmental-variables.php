<?php
/**
 * Processes plugin environmental-variable updates from save-anything requests.
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
 * Processes environmental-variable updates from sanitized save-anything data.
 *
 * @param array $upcoming_save_anything_updates Sanitized updates shared by the ordered save-anything processors.
 * @return WP_Error|null Error on failure, null on success or no-op.
 */
function ai4seo_process_save_anything_environmental_variables( array &$upcoming_save_anything_updates ) {
	// Leave payloads without internal state fields to their owning save processors.
	if ( ! array_intersect( array_keys( $upcoming_save_anything_updates ), array_keys( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES ) ) ) {
		return null;
	}

	// Protect direct processor callers in addition to the central mixed-payload preflight.
	if ( ! ai4seo_can_administer_plugin() ) {
		return new WP_Error(
			11420725,
			esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' )
		);
	}

	// Maintain both old/new history for side effects and a direct new-value map for the bulk writer.
	$ai4seo_recent_environmental_variable_changes = array();
	$ai4seo_environmental_variable_updates        = array();

	// Use the defaults registry as the allowlist for environmental-variable names accepted by save-anything.
	foreach ( array_keys( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES ) as $ai4seo_this_environmental_variable_name ) {
		// The shared save endpoint can contain unrelated categories, so process only present environmental variables.
		if ( ! isset( $upcoming_save_anything_updates[ $ai4seo_this_environmental_variable_name ] ) ) {
			continue;
		}

		$ai4seo_this_old_environmental_variable_value = ai4seo_read_environmental_variable( $ai4seo_this_environmental_variable_name );
		$ai4seo_this_new_environmental_variable_value = $upcoming_save_anything_updates[ $ai4seo_this_environmental_variable_name ];

		// Convert the one datetime-local UI value before comparison and validation against its stored timestamp.
		if ( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME === $ai4seo_this_environmental_variable_name ) {
			// Require the established datetime-local markers so numeric timestamps pass through unchanged.
			if ( is_string( $ai4seo_this_new_environmental_variable_value )
				&& strpos( $ai4seo_this_new_environmental_variable_value, 'T' ) !== false
				&& strpos( $ai4seo_this_new_environmental_variable_value, '-' ) !== false ) {
				$ai4seo_this_new_environmental_variable_value = ai4seo_convert_datetime_local_to_timestamp( $ai4seo_this_new_environmental_variable_value );
			}
		}

		// Retain loose comparison because saved option scalars can differ only by PHP representation.
		// phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Preserve existing save-anything equality semantics across scalar environmental-variable values.
		if ( $ai4seo_this_new_environmental_variable_value == $ai4seo_this_old_environmental_variable_value ) {
			continue;
		}

		// Reject the entire category before persistence when any submitted value violates its registered contract.
		if ( ! ai4seo_validate_environmental_variable_value( $ai4seo_this_environmental_variable_name, $ai4seo_this_new_environmental_variable_value ) ) {
			return new WP_Error(
				461219225,
				sprintf(
					/* translators: %s: Environmental variable name. */
					esc_html__( 'Invalid environmental variable value for %s', 'ai-for-seo' ),
					$ai4seo_this_environmental_variable_name
				)
			);
		}

		// Keep old/new pairs for post-save effects while collecting the same new values for one bulk write.
		$ai4seo_recent_environmental_variable_changes[ $ai4seo_this_environmental_variable_name ] = array( $ai4seo_this_old_environmental_variable_value, $ai4seo_this_new_environmental_variable_value );
		$ai4seo_environmental_variable_updates[ $ai4seo_this_environmental_variable_name ]        = $ai4seo_this_new_environmental_variable_value;
	}

	// Reuse the environmental-variable bulk writer so the category persists all validated changes atomically.
	if ( $ai4seo_environmental_variable_updates ) {
		$ai4seo_environmental_variable_update_result = ai4seo_bulk_update_environmental_variables( $ai4seo_environmental_variable_updates );

		// Preserve diagnostics for registry mismatches that should be impossible after the allowlist pass above.
		if ( ! empty( $ai4seo_environmental_variable_update_result['invalid_names'] ) ) {
			ai4seo_debug_message( 3017171025, 'Bulk update skipped unknown names: ' . implode( ', ', $ai4seo_environmental_variable_update_result['invalid_names'] ) );
		}

		// Log rejected values separately so validation regressions remain distinguishable from unknown names.
		if ( ! empty( $ai4seo_environmental_variable_update_result['invalid_values'] ) ) {
			ai4seo_debug_message( 3117171025, 'Bulk update skipped invalid values for: ' . implode( ', ', $ai4seo_environmental_variable_update_result['invalid_values'] ) );
		}

		// Return persistence failures to the dispatcher while retaining the existing diagnostic message.
		if ( true !== $ai4seo_environmental_variable_update_result['success'] ) {
			ai4seo_debug_message( 3217171025, 'Bulk update failed to persist changes.' );
			return new WP_Error(
				3217171,
				esc_html__( 'Failed to update environmental variables.', 'ai-for-seo' )
			);
		}
	}

	// Keep analysis-sensitive variables declarative so additional triggers can share the same refresh path.
	$ai4seo_analysis_trigger_environmental_variables = array(
		AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME,
	);

	// Refresh posts-table analysis once when any persisted environmental change affects its derived status.
	foreach ( $ai4seo_analysis_trigger_environmental_variables as $ai4seo_this_environmental_variable_key ) {
		// The recent-change map prevents refreshes for submitted values that were equal to stored state.
		if ( isset( $ai4seo_recent_environmental_variable_changes[ $ai4seo_this_environmental_variable_key ] ) ) {
			$ai4seo_posts_table_analysis_was_refreshed = ai4seo_force_posts_table_analysis_refresh_after_admin_mutation();

			if ( ! $ai4seo_posts_table_analysis_was_refreshed ) {
				$ai4seo_rebuild_was_scheduled = ai4seo_schedule_generation_status_summary_rebuild();

				if ( ! $ai4seo_rebuild_was_scheduled ) {
					ai4seo_debug_message( 89120826, 'Could not durably schedule generation-status reconciliation after saving an analysis-sensitive environmental variable.', true );
				}

				return new WP_Error(
					89120826,
					esc_html__( 'The environmental variables were saved, but their generation-status statistics could not be reconciled. A background repair was requested. Please refresh the page and try again.', 'ai-for-seo' )
				);
			}

			break;
		}
	}

	// Synchronize enhanced-reporting acceptance details only when that consent value actually changed.
	if ( isset( $ai4seo_recent_environmental_variable_changes[ AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED ] ) ) {
		// Record separate acceptance and revocation timestamps because RobHub reporting distinguishes both events.
		if ( $ai4seo_recent_environmental_variable_changes[ AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED ][1] ) {
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME, time() );

			// Send the updated consent state only after its local event timestamp is stored.
			ai4seo_set_tos_accept_details( true, 'accepted enhanced reporting' );
		} else {
			// A false transition records revocation independently from any previous acceptance event.
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_REVOKED_TIME, time() );

			// Send the updated consent state only after its local event timestamp is stored.
			ai4seo_set_tos_accept_details( false, 'revoked enhanced reporting' );
		}
	}

	return null;
}
