<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region WordPress OPTIONS ===================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Function to get all post ids based on an option that is saved as json
 *
 * @param string $option
 * @return array
 */
function ai4seo_get_post_ids_from_option( string $option ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 769714633, 'Prevented loop', true );
		return array();
	}

	$option = sanitize_key( $option );

	// get post ids.
	$post_ids = ai4seo_get_option( $option );

	$post_ids = maybe_unserialize( $post_ids );

	// create empty option if it does not exist.
	if ( ! $post_ids ) {
		ai4seo_update_option( $option, array() );
		return array();
	}

	if ( ai4seo_is_json( $post_ids ) ) {
		$post_ids = json_decode( $post_ids );
	}

	// on error -> return empty array.
	if ( ! $post_ids || ! is_array( $post_ids ) ) {
		$post_ids = array();
	}

	// deep intval sanitize.
	$post_ids = ai4seo_deep_sanitize( $post_ids, 'intval' );

	// return unique post ids, remove 0.
	$post_ids = array_unique( $post_ids );

	$post_ids = array_filter(
		$post_ids,
		function ( $value ) {
			return 0 !== $value;
		}
	);

	return $post_ids;
}

// =========================================================================================== \\

/**
 * Returns the post-ID option names represented by the generation status summary.
 *
 * @return array
 */
function ai4seo_get_generation_status_summary_source_option_names(): array {
	// Cache the merged registry because every option write uses the same coverage and queue source set.
	static $source_option_names = null;

	if ( null === $source_option_names ) {
		$source_option_names = array_values(
			array_unique(
				array_merge(
					AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS,
					AI4SEO_GENERATION_STATUS_POST_ID_OPTIONS
				)
			)
		);
	}

	return $source_option_names;
}

// =========================================================================================== \\

/**
 * Normalizes a stored post-ID option value without reading the option again.
 *
 * @param mixed $option_value Raw option value before or after a write.
 * @return array
 */
function ai4seo_normalize_post_ids_from_option_value( $option_value ): array {
	// Support the serialized and JSON shapes retained for compatibility with older option storage.
	$option_value = maybe_unserialize( $option_value );

	if ( is_string( $option_value ) && '' !== $option_value && ai4seo_is_json( $option_value ) ) {
		$option_value = json_decode( $option_value, true );
	}

	if ( ! is_array( $option_value ) ) {
		return array();
	}

	// Ignore malformed nested or non-numeric values so they cannot become synthetic post IDs during coercion.
	$post_ids = array();

	foreach ( $option_value as $post_id ) {
		if ( ! is_scalar( $post_id ) || ! is_numeric( $post_id ) ) {
			continue;
		}

		$normalized_post_id = (int) $post_id;

		if ( $normalized_post_id > 0 && (float) $post_id === (float) $normalized_post_id ) {
			$post_ids[] = $normalized_post_id;
		}
	}

	// Stable numeric ordering lets the tracker compare membership instead of storage representation.
	$post_ids = array_values( array_unique( $post_ids ) );
	sort( $post_ids, SORT_NUMERIC );

	return $post_ids;
}

// =========================================================================================== \\

/**
 * Records actual status-option membership changes for one batched request-level reconciliation.
 *
 * @param string $option_name Option that was successfully mutated.
 * @param mixed  $old_value Option value before the mutation.
 * @param mixed  $new_value Option value after the mutation.
 * @return void
 */
function ai4seo_track_generation_status_summary_option_change( string $option_name, $old_value, $new_value ): void {
	// Ignore settings and internal options that never contribute buckets to the dashboard summary.
	if ( ! in_array( $option_name, ai4seo_get_generation_status_summary_source_option_names(), true ) ) {
		return;
	}

	// Symmetric difference identifies only IDs whose membership in this specific source option changed.
	$old_post_ids       = ai4seo_normalize_post_ids_from_option_value( $old_value );
	$new_post_ids       = ai4seo_normalize_post_ids_from_option_value( $new_value );
	$old_post_id_lookup = array_flip( $old_post_ids );
	$new_post_id_lookup = array_flip( $new_post_ids );
	$changed_post_ids   = array_keys(
		array_diff_key( $old_post_id_lookup, $new_post_id_lookup )
		+ array_diff_key( $new_post_id_lookup, $old_post_id_lookup )
	);

	if ( ! $changed_post_ids ) {
		return;
	}

	// Accumulate by source option so the final reconciliation reads only options touched by this request.
	global $ai4seo_generation_status_summary_pending_option_changes;
	global $ai4seo_is_generation_status_summary_flush_registered;

	if ( ! is_array( $ai4seo_generation_status_summary_pending_option_changes ?? null ) ) {
		$ai4seo_generation_status_summary_pending_option_changes = array();
	}

	foreach ( $changed_post_ids as $post_id ) {
		// Preserve the first observed membership and update only the request's latest state for this ID.
		if ( ! isset( $ai4seo_generation_status_summary_pending_option_changes[ $option_name ][ $post_id ] ) ) {
			$ai4seo_generation_status_summary_pending_option_changes[ $option_name ][ $post_id ] = array(
				'initial' => isset( $old_post_id_lookup[ $post_id ] ),
				'current' => isset( $new_post_id_lookup[ $post_id ] ),
			);
		} else {
			$ai4seo_generation_status_summary_pending_option_changes[ $option_name ][ $post_id ]['current'] = isset( $new_post_id_lookup[ $post_id ] );
		}

		// A transition that returns to its initial membership requires no final summary reconciliation.
		if ( $ai4seo_generation_status_summary_pending_option_changes[ $option_name ][ $post_id ]['initial']
			=== $ai4seo_generation_status_summary_pending_option_changes[ $option_name ][ $post_id ]['current'] ) {
			unset( $ai4seo_generation_status_summary_pending_option_changes[ $option_name ][ $post_id ] );
		}
	}

	// Drop empty option buckets so the shutdown flush receives only effective final transitions.
	if ( empty( $ai4seo_generation_status_summary_pending_option_changes[ $option_name ] ) ) {
		unset( $ai4seo_generation_status_summary_pending_option_changes[ $option_name ] );
	}

	// One late shutdown callback batches cron loops, AJAX bulk actions, and direct queue clears alike.
	if ( empty( $ai4seo_is_generation_status_summary_flush_registered ) ) {
		add_action( 'shutdown', 'ai4seo_flush_generation_status_summary_option_changes', PHP_INT_MAX );
		$ai4seo_is_generation_status_summary_flush_registered = true;
	}
}

// =========================================================================================== \\

/**
 * Reconciles all status-option changes collected during the current request.
 *
 * @return void
 */
function ai4seo_flush_generation_status_summary_option_changes(): void {
	global $ai4seo_generation_status_summary_pending_option_changes;

	// Requests without effective membership changes have no summary work to perform.
	if ( ! is_array( $ai4seo_generation_status_summary_pending_option_changes ?? null )
		|| ! $ai4seo_generation_status_summary_pending_option_changes ) {
		return;
	}

	// Reduce tracked initial/current states to the final changed-ID lookup accepted by the summary subsystem.
	$pending_option_changes = array();

	foreach ( $ai4seo_generation_status_summary_pending_option_changes as $option_name => $post_id_states ) {
		foreach ( array_keys( $post_id_states ) as $post_id ) {
			$pending_option_changes[ $option_name ][ $post_id ] = true;
		}
	}

	// Detach the current batch before reconciliation so nested source writes cannot replay it.
	$ai4seo_generation_status_summary_pending_option_changes = array();

	// A concurrently running full analysis owns the summary and will read the final live option memberships itself.
	if ( 'processing' === ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, false )
		|| ! function_exists( 'ai4seo_sync_generation_status_summary_for_option_changes' ) ) {
		return;
	}

	// Reconcile the request's final state once, rather than once for every intermediate transition.
	ai4seo_sync_generation_status_summary_for_option_changes( $pending_option_changes );
}

// =========================================================================================== \\

/**
 * Function to add post ids to an option that is saved as json
 *
 * @param mixed $option The option value.
 * @param mixed $post_ids The post ids value.
 * @return bool
 */
function ai4seo_add_post_ids_to_option( $option, $post_ids ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 942539402, 'Prevented loop', true );
		return false;
	}

	$option = sanitize_key( $option );

	if ( ! is_array( $post_ids ) ) {
		$post_ids = array( $post_ids );
	}

	// intval sanitize.
	$post_ids = ai4seo_deep_sanitize( $post_ids, 'intval' );

	// logic based removals.
	ai4seo_remove_contradictory_post_ids( $option, $post_ids );

	// get old post ids.
	$old_post_ids = ai4seo_get_post_ids_from_option( $option );

	// add the new post ids to the old ones.
	$new_post_ids = array_merge( $old_post_ids, $post_ids );
	$new_post_ids = ai4seo_deep_sanitize( $new_post_ids, 'intval' );
	$new_post_ids = array_unique( $new_post_ids );
	$new_post_ids = array_values( $new_post_ids );

	// remove 0 entries.
	$new_post_ids = array_filter(
		$new_post_ids,
		function ( $value ) {
			return 0 !== $value;
		}
	);

	// The shared option writer records real membership deltas for one request-level summary flush.
	return ai4seo_update_option( $option, $new_post_ids );
}

// =========================================================================================== \\

/**
 * Function to remove post ids from options that are contrary to the option that got added to
 *
 * @param string $add_to_this_option The option that got added to.
 * @param array  $post_ids The post ids that got added (and need to get removed).
 * @return void
 */
function ai4seo_remove_contradictory_post_ids( string $add_to_this_option, array $post_ids ) {
	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 173818084, 'Prevented loop', true );
		return;
	}

	switch ( $add_to_this_option ) {
		// now missing -> remove from fully covered and generated.
		case AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME:
			ai4seo_remove_post_ids_from_option( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_ids );
			ai4seo_remove_post_ids_from_option( AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME, $post_ids );
			break;
		case AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			ai4seo_remove_post_ids_from_option( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $post_ids );
			ai4seo_remove_post_ids_from_option( AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $post_ids );
			break;

		// now fully covered -> remove from missing.
		case AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME:
			ai4seo_remove_post_ids_from_option( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME, $post_ids );
			break;
		case AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			ai4seo_remove_post_ids_from_option( AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $post_ids );
			break;

		// now processing -> remove from pending.
		case AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME:
			ai4seo_remove_post_ids_from_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, $post_ids );
			break;
		case AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			ai4seo_remove_post_ids_from_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $post_ids );
			break;

		// now pending -> remove from processing.
		case AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME:
			ai4seo_remove_post_ids_from_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, $post_ids );
			ai4seo_remove_post_ids_from_option( AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME, $post_ids );
			break;
		case AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			ai4seo_remove_post_ids_from_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $post_ids );
			ai4seo_remove_post_ids_from_option( AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $post_ids );
			break;
	}
}

// =========================================================================================== \\

/**
 * Remove post ids from an option that is saved as json
 *
 * @param string    $remove_from_this_option
 * @param int|array $post_ids
 * @return bool
 */
function ai4seo_remove_post_ids_from_option( string $remove_from_this_option, $post_ids ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 534042781, 'Prevented loop', true );
		return false;
	}

	$remove_from_this_option = sanitize_key( $remove_from_this_option );

	if ( ! is_array( $post_ids ) ) {
		$post_ids = array( $post_ids );
	}

	// make sure every entry is numeric.
	foreach ( $post_ids as $key => $post_id ) {
		if ( ! is_numeric( $post_id ) ) {
			unset( $post_ids[ $key ] );
		}
	}

	// get old post ids.
	$old_post_ids = ai4seo_get_post_ids_from_option( $remove_from_this_option );

	// remove the new post ids from the old ones.
	$new_post_ids = array_diff( $old_post_ids, $post_ids );

	// rearrange the array keys to start at 0.
	$new_post_ids = array_values( $new_post_ids );
	$new_post_ids = array_unique( $new_post_ids );

	// intval sanitize.
	$new_post_ids = ai4seo_deep_sanitize( $new_post_ids, 'intval' );

	// remove 0 entries.
	$new_post_ids = array_filter(
		$new_post_ids,
		function ( $value ) {
			return 0 !== $value;
		}
	);

	// check if old and new post ids are the same.
	if ( $old_post_ids === $new_post_ids ) {
		return false;
	}

	// update the option.
	return ai4seo_update_option( $remove_from_this_option, $new_post_ids );
}

// =========================================================================================== \\

/**
 * Function to remove post ids from EVERY WP_OPTION
 *
 * @param int|array $post_ids
 */
function ai4seo_remove_post_ids_from_all_options( $post_ids ) {
	foreach ( AI4SEO_ALL_POST_ID_OPTIONS as $ai4seo_option ) {
		ai4seo_remove_post_ids_from_option( $ai4seo_option, $post_ids );
	}
}

// =========================================================================================== \\

/**
 * Function ro remove post ids from EVERY WP_OPTION that handles the SEO COVERAGE
 *
 * @param int|array $post_ids
 */
function ai4seo_remove_post_ids_from_all_seo_coverage_options( $post_ids ) {
	foreach ( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS as $ai4seo_option ) {
		ai4seo_remove_post_ids_from_option( $ai4seo_option, $post_ids );
	}
}

// =========================================================================================== \\

/**
 * Function to remove post ids from EVERY WP_OPTION that handles the GENERATION STATUS
 *
 * @param int|array $post_ids
 */
function ai4seo_remove_post_ids_from_all_generation_status_options( $post_ids ) {
	foreach ( AI4SEO_GENERATION_STATUS_POST_ID_OPTIONS as $ai4seo_option ) {
		ai4seo_remove_post_ids_from_option( $ai4seo_option, $post_ids );
	}

	// Force-overwrite markers are a queue mode, but they must be cleared when a queued run leaves generation status.
	foreach ( AI4SEO_FORCE_OVERWRITE_BULK_GENERATION_POST_ID_OPTIONS as $ai4seo_option ) {
		ai4seo_remove_post_ids_from_option( $ai4seo_option, $post_ids );
	}
}

// =========================================================================================== \\

/**
 * Returns the active overwrite existing metadata settings
 *
 * @return array The active overwrite existing metadata settings
 */
function ai4seo_get_active_overwrite_existing_metadata(): array {
	$active_meta_tags            = ai4seo_get_active_meta_tags();
	$overwrite_existing_metadata = ai4seo_get_setting( AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA );

	// remove from $overwrite_existing_metadata any meta tag that is not in $active_meta_tags.
	$active_overwrite_existing_metadata = array();

	foreach ( $overwrite_existing_metadata as $this_overwrite_existing_metadata ) {
		if ( in_array( $this_overwrite_existing_metadata, $active_meta_tags ) ) {
			$active_overwrite_existing_metadata[] = $this_overwrite_existing_metadata;
		}
	}

	return $active_overwrite_existing_metadata;
}

// =========================================================================================== \\

/**
 * Returns the setting if we should generate metadata for fully covered entries.
 * But only if we have active overwrite existing metadata settings.
 *
 * @return bool Whether to generate metadata for fully covered entries
 */
function ai4seo_do_generate_metadata_for_fully_covered_entries(): bool {
	$generate_metadata_for_fully_covered_entries = ai4seo_get_setting( AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES );

	if ( ! $generate_metadata_for_fully_covered_entries ) {
		return false;
	}

	$active_overwrite_existing_metadata = ai4seo_get_active_overwrite_existing_metadata();

	return ! empty( $active_overwrite_existing_metadata );
}

// =========================================================================================== \\

/**
 * Returns the active overwrite existing media attributes settings
 *
 * @return array The active overwrite existing media attributes settings
 */
function ai4seo_get_active_overwrite_existing_attachment_attributes(): array {
	$active_attachment_attributes             = ai4seo_get_active_attachment_attributes();
	$overwrite_existing_attachment_attributes = ai4seo_get_setting( AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES );

	// remove from $overwrite_existing_attachment_attributes any attachment attribute that is not in $active_attachment_attributes.
	$active_overwrite_existing_attachment_attributes = array();

	foreach ( $overwrite_existing_attachment_attributes as $this_overwrite_existing_attachment_attribute ) {
		if ( in_array( $this_overwrite_existing_attachment_attribute, $active_attachment_attributes ) ) {
			$active_overwrite_existing_attachment_attributes[] = $this_overwrite_existing_attachment_attribute;
		}
	}

	return $active_overwrite_existing_attachment_attributes;
}

// =========================================================================================== \\

/**
 * Returns the setting if we should generate attachment attributes for fully covered entries.
 * But only if we have active overwrite existing attachment attributes settings.
 *
 * @return bool Whether to generate attachment attributes for fully covered entries
 */
function ai4seo_do_generate_attachment_attributes_for_fully_covered_entries(): bool {
	$generate_attachment_attributes_for_fully_covered_entries = ai4seo_get_setting( AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES );

	if ( ! $generate_attachment_attributes_for_fully_covered_entries ) {
		return false;
	}

	$active_overwrite_existing_attachment_attributes = ai4seo_get_active_overwrite_existing_attachment_attributes();

	return ! empty( $active_overwrite_existing_attachment_attributes );
}


// endregion
// ___________________________________________________________________________________________.
