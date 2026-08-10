<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region META DATA ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Function to get the summary (amount of posts) of a specific options (generation status)
 *
 * @param string $option_name the name of the option (generation status).
 * @return array the generation status summary entry or false if not found
 */
function ai4seo_get_generation_status_summary_entry( string $option_name ): array {
	// Dashboard counters read the analysis-built summary, matching author and taxonomy term scope handling.
	$generation_status_summary = ai4seo_read_generation_status_summary( true, true );

	if ( ! isset( $generation_status_summary[ $option_name ] ) ) {
		return array();
	}

	return $generation_status_summary[ $option_name ];
}

// =========================================================================================== \\

/**
 * Function to get all missing posts by post type by using the generation status summary-cache
 *
 * @return array the missing posts by post type
 */
function ai4seo_get_num_missing_posts_by_post_type(): array {
	$num_missing_metadata_by_post_type = ai4seo_get_generation_status_summary_entry( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME );
	$num_missing_attachment_attributes = ai4seo_get_generation_status_summary_entry( AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	return array_merge( $num_missing_metadata_by_post_type, $num_missing_attachment_attributes );
}

// =========================================================================================== \\

/**
 * Function to get all fully covered posts by post type by using the generation status summary-cache
 *
 * @return array the fully covered posts by post type
 */
function ai4seo_get_num_fully_covered_posts_by_post_type(): array {
	$num_fully_covered_metadata_by_post_type = ai4seo_get_generation_status_summary_entry( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME );
	$num_fully_covered_attachment_attributes = ai4seo_get_generation_status_summary_entry( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	return array_merge( $num_fully_covered_metadata_by_post_type, $num_fully_covered_attachment_attributes );
}

// =========================================================================================== \\

/**
 * Function to get all generated posts by post type by using the generation status summary-cache
 *
 * @return array the generated posts by post type
 */
function ai4seo_get_num_generated_posts_by_post_type(): array {
	$num_generated_metadata_by_post_type = ai4seo_get_generation_status_summary_entry( AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME );
	$num_generated_attachment_attributes = ai4seo_get_generation_status_summary_entry( AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	return array_merge( $num_generated_metadata_by_post_type, $num_generated_attachment_attributes );
}

// =========================================================================================== \\

/**
 * Function to get all fully covered OR generated posts by post type by using the generation status summary-cache, depending on, if we care fully covered entries
 *
 * @return array the fully covered or generated posts by post type
 */
function ai4seo_get_num_finished_posts_by_post_type(): array {
	$num_finished_metadata_by_post_type = ai4seo_get_generation_status_summary_entry( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME );
	$num_finished_attachment_attributes = ai4seo_get_generation_status_summary_entry( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	return array_merge( $num_finished_metadata_by_post_type, $num_finished_attachment_attributes );
}


// =========================================================================================== \\

/**
 * Function to get all failed posts by post type by using the generation status summary-cache
 *
 * @return array the failed posts by post type
 */
function ai4seo_get_num_failed_posts_by_post_type(): array {
	$num_failed_metadata_by_post_type = ai4seo_get_generation_status_summary_entry( AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME );
	$num_failed_attachment_attributes = ai4seo_get_generation_status_summary_entry( AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	return array_merge( $num_failed_metadata_by_post_type, $num_failed_attachment_attributes );
}

// =========================================================================================== \\

/**
 * Function to get all pending posts by post type by using the generation status summary-cache
 *
 * @return array the pending posts by post type
 */
function ai4seo_get_num_pending_posts_by_post_type(): array {
	$num_pending_metadata_by_post_type = ai4seo_get_generation_status_summary_entry( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );
	$num_pending_attachment_attributes = ai4seo_get_generation_status_summary_entry( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	return array_merge( $num_pending_metadata_by_post_type, $num_pending_attachment_attributes );
}

// =========================================================================================== \\

/**
 * Function to get all processing posts by post type by using the generation status summary-cache
 *
 * @return array the processing posts by post type
 */
function ai4seo_get_num_processing_posts_by_post_type(): array {
	$num_processing_metadata_by_post_type = ai4seo_get_generation_status_summary_entry( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME );
	$num_processing_attachment_attributes = ai4seo_get_generation_status_summary_entry( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	return array_merge( $num_processing_metadata_by_post_type, $num_processing_attachment_attributes );
}

// =========================================================================================== \\

/**
 * Function to get the summary (amount of posts) of a specific options and post type
 *
 * @param string $option_name the name of the option (generation status).
 * @param string $post_type the post type.
 * @return int the amount of posts for this specific generation status and post type
 */
function ai4seo_get_num_generation_status_and_post_types_posts( string $option_name, string $post_type ): int {
	// Per-status counters use the same analysis-built summary as the broader dashboard count helpers.
	$generation_status_summary = ai4seo_read_generation_status_summary( true, true );

	if ( ! $generation_status_summary ) {
		return 0;
	}

	if ( ! isset( $generation_status_summary[ $option_name ] ) ) {
		return 0;
	}

	if ( ! isset( $generation_status_summary[ $option_name ][ $post_type ] ) ) {
		return 0;
	}

	return (int) $generation_status_summary[ $option_name ][ $post_type ];
}

// =========================================================================================== \\

/**
 * Returns the legacy active metadata postmeta key for a post and metadata identifier.
 *
 * @param int    $post_id The post id.
 * @param string $metadata_identifier The metadata identifier.
 * @return string The legacy postmeta key.
 */
function ai4seo_generate_legacy_postmeta_key_by_metadata_identifier( $post_id, $metadata_identifier ): string {
	return '_ai4seo_' . $post_id . '_' . $metadata_identifier;
}

// =========================================================================================== \\

/**
 * Gets the metadata identifier from a legacy active metadata postmeta key.
 *
 * @param string $metadata_postmeta_key The legacy postmeta key.
 * @return string The metadata identifier or an empty string if not found.
 */
function ai4seo_get_legacy_metadata_identifier_by_postmeta_key( string $metadata_postmeta_key ): string {
	$matches = array();
	preg_match( '/^_ai4seo_([0-9]+)_(.*)$/', $metadata_postmeta_key, $matches );

	if ( empty( $matches[2] ) ) {
		return '';
	}

	return $matches[2];
}

// =========================================================================================== \\

/**
 * Returns LIKE patterns for indexed legacy active metadata key lookups.
 *
 * @return array
 */
function ai4seo_get_legacy_active_metadata_postmeta_key_like_patterns(): array {
	global $wpdb;

	// Keep this pattern count aligned with the inline legacy metadata LIKE blocks required by WPCS.
	$legacy_active_metadata_postmeta_key_like_patterns = array();

	for ( $i = 0; $i <= 9; $i++ ) {
		$legacy_active_metadata_postmeta_key_like_patterns[] = $wpdb->esc_like( '_ai4seo_' . $i ) . '%' . $wpdb->esc_like( '_' ) . '%';
	}

	return $legacy_active_metadata_postmeta_key_like_patterns;
}

// =========================================================================================== \\

/**
 * Returns SQL conditions and parameters for indexed legacy active metadata key lookups.
 *
 * @return array
 */
function ai4seo_get_legacy_active_metadata_postmeta_key_like_conditions(): array {
	// Keep the previous helper shape available for any legacy internal caller.
	$legacy_active_metadata_postmeta_key_like_patterns   = ai4seo_get_legacy_active_metadata_postmeta_key_like_patterns();
	$legacy_active_metadata_postmeta_key_like_conditions = '('
		. 'meta_key LIKE %s OR '
		. 'meta_key LIKE %s OR '
		. 'meta_key LIKE %s OR '
		. 'meta_key LIKE %s OR '
		. 'meta_key LIKE %s OR '
		. 'meta_key LIKE %s OR '
		. 'meta_key LIKE %s OR '
		. 'meta_key LIKE %s OR '
		. 'meta_key LIKE %s OR '
		. 'meta_key LIKE %s'
		. ')';

	return array( $legacy_active_metadata_postmeta_key_like_conditions, $legacy_active_metadata_postmeta_key_like_patterns );
}

// =========================================================================================== \\

/**
 * Checks whether any legacy active metadata rows still exist.
 *
 * @return bool
 */
function ai4seo_has_legacy_active_metadata_rows(): bool {
	return ! empty( ai4seo_read_legacy_active_metadata_migration_v235_candidate_post_ids( 1 ) );
}

// =========================================================================================== \\

/**
 * Normalizes and filters active metadata values for JSON storage or active-tag reads.
 *
 * @param array $active_metadata_values The metadata values.
 * @param bool  $active_meta_tags_only Whether only currently active tags should be returned.
 * @return array
 */
function ai4seo_prepare_active_metadata_values( array $active_metadata_values, bool $active_meta_tags_only = false ): array {
	if ( ! defined( 'AI4SEO_METADATA_DETAILS' ) ) {
		return array();
	}

	$recognized_metadata_identifiers = array_keys( AI4SEO_METADATA_DETAILS );

	if ( $active_meta_tags_only ) {
		$active_meta_tags = ai4seo_get_active_meta_tags();

		if ( ! $active_meta_tags ) {
			return array();
		}

		$recognized_metadata_identifiers = array_values( array_intersect( $recognized_metadata_identifiers, $active_meta_tags ) );
	}

	$prepared_active_metadata_values = array();

	foreach ( $recognized_metadata_identifiers as $this_metadata_identifier ) {
		if ( ! array_key_exists( $this_metadata_identifier, $active_metadata_values ) ) {
			continue;
		}

		$this_metadata_value = $active_metadata_values[ $this_metadata_identifier ];

		if ( ! is_string( $this_metadata_value ) && ! is_scalar( $this_metadata_value ) ) {
			continue;
		}

		$this_metadata_value = ai4seo_normalize_editor_input_value( $this_metadata_value );
		$this_max_length     = ai4seo_get_max_editor_input_length( $this_metadata_identifier );
		$this_metadata_value = ai4seo_trim_string_to_length( $this_metadata_value, $this_max_length );

		$prepared_active_metadata_values[ $this_metadata_identifier ] = $this_metadata_value;
	}

	return $prepared_active_metadata_values;
}

// =========================================================================================== \\

/**
 * Decodes an active metadata JSON postmeta value.
 *
 * @param string $active_metadata_json_string The JSON string.
 * @param bool   $active_meta_tags_only Whether only currently active tags should be returned.
 * @return array
 */
function ai4seo_decode_active_metadata_json_string( string $active_metadata_json_string, bool $active_meta_tags_only = false ): array {
	if ( ! $active_metadata_json_string ) {
		return array();
	}

	$active_metadata = json_decode( $active_metadata_json_string, true );

	if ( ! is_array( $active_metadata ) ) {
		return array();
	}

	return ai4seo_prepare_active_metadata_values( $active_metadata, $active_meta_tags_only );
}

// =========================================================================================== \\

/**
 * Reads active metadata from the v235 JSON postmeta entry for a single post.
 *
 * @param int  $post_id The post id.
 * @param bool $active_meta_tags_only Whether only currently active tags should be returned.
 * @return array
 */
function ai4seo_read_active_metadata_from_post_meta( int $post_id, bool $active_meta_tags_only = false ): array {
	$active_metadata_json_string = get_post_meta( $post_id, AI4SEO_POST_META_ACTIVE_METADATA_META_KEY, true );

	if ( ! is_string( $active_metadata_json_string ) ) {
		return array();
	}

	return ai4seo_decode_active_metadata_json_string( $active_metadata_json_string, $active_meta_tags_only );
}

// =========================================================================================== \\

/**
 * Reads active metadata from the v235 JSON postmeta entry for multiple posts.
 *
 * @param array $post_ids The post ids.
 * @param bool  $active_meta_tags_only Whether only currently active tags should be returned.
 * @return array
 */
function ai4seo_read_active_metadata_by_post_ids( array $post_ids, bool $active_meta_tags_only = true ): array {
	global $wpdb;

	$post_ids = array_filter( array_map( 'absint', $post_ids ) );

	if ( ! $post_ids ) {
		return array();
	}

	$active_metadata_by_post_ids = array();
	$database_chunk_size         = ai4seo_get_database_chunk_size();
	$post_ids_chunks             = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( ! $this_post_ids_chunk ) {
			continue;
		}

		$this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

		$this_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ({$this_post_ids_placeholders}) ORDER BY meta_id ASC",
				...array_merge( array( AI4SEO_POST_META_ACTIVE_METADATA_META_KEY ), $this_post_ids_chunk )
			),
			ARRAY_A
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321696, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! $this_rows ) {
			continue;
		}

		foreach ( $this_rows as $this_row ) {
			$this_post_id = absint( $this_row['post_id'] );

			if ( array_key_exists( $this_post_id, $active_metadata_by_post_ids ) ) {
				continue;
			}

			$active_metadata_by_post_ids[ $this_post_id ] = ai4seo_decode_active_metadata_json_string( strval( $this_row['meta_value'] ), $active_meta_tags_only );
		}
	}

	return $active_metadata_by_post_ids;
}

// =========================================================================================== \\

/**
 * Saves active metadata to the v235 JSON postmeta entry.
 *
 * @param int   $post_id The post id.
 * @param array $active_metadata The active metadata values.
 * @param bool  $existing_active_metadata_wins Whether existing JSON values should win over provided values.
 * @return bool
 */
function ai4seo_save_active_metadata_to_postmeta( int $post_id, array $active_metadata, bool $existing_active_metadata_wins = false ): bool {
	global $wpdb;

	$post_id = absint( $post_id );

	if ( $post_id <= 0 || ! get_post( $post_id ) ) {
		return false;
	}

	$active_metadata = ai4seo_prepare_active_metadata_values( $active_metadata, false );

	for ( $i = 0; $i < 3; $i++ ) {
		$current_active_metadata_json_string = get_post_meta( $post_id, AI4SEO_POST_META_ACTIVE_METADATA_META_KEY, true );

		if ( ! is_string( $current_active_metadata_json_string ) ) {
			$current_active_metadata_json_string = '';
		}

		$current_active_metadata = ai4seo_decode_active_metadata_json_string( $current_active_metadata_json_string, false );

		if ( $existing_active_metadata_wins ) {
			$merged_active_metadata = $active_metadata;

			foreach ( $current_active_metadata as $this_metadata_identifier => $this_metadata_value ) {
				$merged_active_metadata[ $this_metadata_identifier ] = $this_metadata_value;
			}
		} else {
			$merged_active_metadata = $current_active_metadata;

			foreach ( $active_metadata as $this_metadata_identifier => $this_metadata_value ) {
				$merged_active_metadata[ $this_metadata_identifier ] = $this_metadata_value;
			}
		}

		$merged_active_metadata      = ai4seo_prepare_active_metadata_values( $merged_active_metadata, false );
		$active_metadata_json_string = wp_json_encode( $merged_active_metadata, JSON_UNESCAPED_UNICODE );

		if ( ! is_string( $active_metadata_json_string ) ) {
			return false;
		}

		if ( $current_active_metadata_json_string === $active_metadata_json_string ) {
			return true;
		}

		$previous_suppress = $wpdb->suppress_errors( true );
		$wpdb->last_error  = '';

		$result = update_post_meta(
			$post_id,
			AI4SEO_POST_META_ACTIVE_METADATA_META_KEY,
			wp_slash( $active_metadata_json_string ),
			$current_active_metadata_json_string
		);

		$had_error = ! empty( $wpdb->last_error );
		$wpdb->suppress_errors( $previous_suppress );

		if ( $had_error ) {
			ai4seo_debug_message( 984321702, 'Database error during active metadata update_post_meta: ' . $wpdb->last_error, true );
			return false;
		}

		if ( false !== $result ) {
			return true;
		}

		wp_cache_delete( $post_id, 'post_meta' );
		$latest_active_metadata_json_string = get_post_meta( $post_id, AI4SEO_POST_META_ACTIVE_METADATA_META_KEY, true );

		if ( $latest_active_metadata_json_string === $active_metadata_json_string ) {
			return true;
		}
	}

	return false;
}

// =========================================================================================== \\

/**
 * Reads candidate post ids with legacy active metadata rows for the v235 migration.
 *
 * @param int $limit The maximum amount of candidate post ids.
 * @return array
 */
function ai4seo_read_legacy_active_metadata_migration_v235_candidate_post_ids( int $limit ): array {
	global $wpdb;

	$limit = absint( $limit );

	if ( $limit <= 0 ) {
		return array();
	}

	$legacy_active_metadata_like_patterns = ai4seo_get_legacy_active_metadata_postmeta_key_like_patterns();

	$legacy_active_metadata_post_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT post_id
            FROM {$wpdb->postmeta}
            WHERE (
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s
            )
            LIMIT %d",
			...array_merge( $legacy_active_metadata_like_patterns, array( $limit ) )
		)
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321697, 'Database error: ' . $wpdb->last_error, true );
		return array();
	}

	if ( ! is_array( $legacy_active_metadata_post_ids ) ) {
		return array();
	}

	return array_values( array_filter( array_map( 'absint', $legacy_active_metadata_post_ids ) ) );
}

// =========================================================================================== \\

/**
 * Reads recognized legacy active metadata rows for specific post ids.
 *
 * @param array $post_ids The post ids.
 * @return array
 */
function ai4seo_read_legacy_active_metadata_by_post_ids( array $post_ids ): array {
	global $wpdb;

	if ( ! defined( 'AI4SEO_METADATA_DETAILS' ) ) {
		return array();
	}

	$post_ids = array_filter( array_map( 'absint', $post_ids ) );

	if ( ! $post_ids ) {
		return array();
	}

	$legacy_active_metadata_like_patterns = ai4seo_get_legacy_active_metadata_postmeta_key_like_patterns();

	$legacy_active_metadata_by_post_ids = array();
	$database_chunk_size                = ai4seo_get_database_chunk_size();
	$post_ids_chunks                    = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( ! $this_post_ids_chunk ) {
			continue;
		}

		$this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

		$this_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, post_id, meta_key, meta_value
                FROM {$wpdb->postmeta}
                WHERE (
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s
                )
                AND post_id IN ({$this_post_ids_placeholders})
                ORDER BY meta_id ASC",
				...array_merge( $legacy_active_metadata_like_patterns, $this_post_ids_chunk )
			),
			ARRAY_A
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321698, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! $this_rows ) {
			continue;
		}

		foreach ( $this_rows as $this_row ) {
			$this_post_id             = absint( $this_row['post_id'] );
			$this_metadata_identifier = ai4seo_get_legacy_metadata_identifier_by_postmeta_key( strval( $this_row['meta_key'] ) );

			if ( ! $this_metadata_identifier ) {
				continue;
			}

			if ( ! isset( AI4SEO_METADATA_DETAILS[ $this_metadata_identifier ] ) ) {
				continue;
			}

			$legacy_active_metadata_by_post_ids[ $this_post_id ][ $this_metadata_identifier ] = strval( $this_row['meta_value'] );
		}
	}

	foreach ( $legacy_active_metadata_by_post_ids as $this_post_id => $this_legacy_active_metadata ) {
		$legacy_active_metadata_by_post_ids[ $this_post_id ] = ai4seo_prepare_active_metadata_values( $this_legacy_active_metadata, false );
	}

	return $legacy_active_metadata_by_post_ids;
}

// =========================================================================================== \\

/**
 * Deletes legacy active metadata rows for specific post ids.
 *
 * @param array $post_ids The post ids.
 * @return bool
 */
function ai4seo_delete_legacy_active_metadata_for_post_ids( array $post_ids ): bool {
	global $wpdb;

	$sanitized_post_ids = array();

	foreach ( $post_ids as $this_post_id ) {
		if ( ! is_numeric( $this_post_id ) ) {
			continue;
		}

		$sanitized_post_ids[] = absint( $this_post_id );
	}

	$post_ids = array_values( array_unique( $sanitized_post_ids ) );

	if ( ! $post_ids ) {
		return true;
	}

	$legacy_active_metadata_like_patterns = ai4seo_get_legacy_active_metadata_postmeta_key_like_patterns();

	$database_chunk_size = ai4seo_get_database_chunk_size();
	$post_ids_chunks     = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( ! $this_post_ids_chunk ) {
			continue;
		}

		$this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta}
                WHERE (
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s OR
                    meta_key LIKE %s
                )
                AND post_id IN ({$this_post_ids_placeholders})",
				...array_merge( $legacy_active_metadata_like_patterns, $this_post_ids_chunk )
			)
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321699, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}
	}

	return true;
}

// =========================================================================================== \\

/**
 * Deletes all legacy active metadata rows.
 *
 * @return bool
 */
function ai4seo_delete_all_legacy_active_metadata(): bool {
	global $wpdb;

	$legacy_active_metadata_like_patterns = ai4seo_get_legacy_active_metadata_postmeta_key_like_patterns();

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta}
            WHERE (
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s OR
                meta_key LIKE %s
            )",
			$legacy_active_metadata_like_patterns
		)
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321700, 'Database error: ' . $wpdb->last_error, true );
		return false;
	}

	return true;
}

// =========================================================================================== \\

/**
 * Runs one v235 active metadata migration batch.
 *
 * @return bool True when migration is completed, false when more work may remain.
 */
function ai4seo_run_active_metadata_migration_v235_batch(): bool {
	global $wpdb;

	$post_ids = ai4seo_read_legacy_active_metadata_migration_v235_candidate_post_ids( AI4SEO_ACTIVE_METADATA_MIGRATION_V235_BATCH_SIZE );

	if ( ! $post_ids ) {
		if ( $wpdb->last_error ) {
			return false;
		}

		return true;
	}

	$legacy_active_metadata_by_post_ids = ai4seo_read_legacy_active_metadata_by_post_ids( $post_ids );

	if ( $wpdb->last_error ) {
		return false;
	}

	$overall_success = true;

	foreach ( $post_ids as $this_post_id ) {
		$this_legacy_active_metadata = $legacy_active_metadata_by_post_ids[ $this_post_id ] ?? array();

		if ( ! get_post( $this_post_id ) ) {
			$overall_success = ai4seo_delete_legacy_active_metadata_for_post_ids( array( $this_post_id ) ) && $overall_success;
			continue;
		}

		$this_legacy_active_metadata = ai4seo_prepare_active_metadata_values( $this_legacy_active_metadata, false );
		$this_success                = true;

		if ( $this_legacy_active_metadata ) {
			$this_success = ai4seo_save_active_metadata_to_postmeta( $this_post_id, $this_legacy_active_metadata, true );
		}

		if ( $this_success ) {
			$this_success = ai4seo_delete_legacy_active_metadata_for_post_ids( array( $this_post_id ) );
		}

		if ( ! $this_success ) {
			$overall_success = false;
		}
	}

	$active_metadata_migration_v235_processed_entries  = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_PROCESSED_ENTRIES, false );
	$active_metadata_migration_v235_processed_entries += count( $post_ids );
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_PROCESSED_ENTRIES, $active_metadata_migration_v235_processed_entries, false );

	if ( ! $overall_success ) {
		return false;
	}

	$has_remaining_legacy_active_metadata_rows = ai4seo_has_legacy_active_metadata_rows();

	if ( $wpdb->last_error ) {
		return false;
	}

	return ! $has_remaining_legacy_active_metadata_rows;
}

// =========================================================================================== \\

/**
 * Function to read the post meta from specific posts by the given post ids
 *
 * @param array $post_ids of post ids (all int).
 * @return array
 */
function ai4seo_read_our_plugins_metadata_by_post_ids( array $post_ids ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 610785731, 'Prevented loop', true );
		return array();
	}

	$active_meta_tags = ai4seo_get_active_meta_tags();

	if ( ! $active_meta_tags ) {
		return array();
	}

	// make sure all entries are numeric.
	foreach ( $post_ids as $post_id ) {
		if ( ! is_numeric( $post_id ) ) {
			return array();
		}
	}

	// bail early on empty.
	if ( empty( $post_ids ) ) {
		return array();
	}

	// sanitize IDs.
	$post_ids          = array_map( 'absint', $post_ids );
	$reordered_results = ai4seo_read_active_metadata_by_post_ids( $post_ids, true );

	if ( ai4seo_is_active_metadata_migration_v235_completed() ) {
		return $reordered_results;
	}

	$post_ids_requiring_legacy_fallback = array();

	foreach ( $post_ids as $this_post_id ) {
		$this_active_metadata = $reordered_results[ $this_post_id ] ?? array();

		foreach ( $active_meta_tags as $this_active_meta_tag ) {
			if ( ! array_key_exists( $this_active_meta_tag, $this_active_metadata ) ) {
				$post_ids_requiring_legacy_fallback[] = $this_post_id;
				break;
			}
		}
	}

	if ( ! $post_ids_requiring_legacy_fallback ) {
		return $reordered_results;
	}

	$legacy_active_metadata_by_post_ids = ai4seo_read_legacy_active_metadata_by_post_ids( array_values( array_unique( $post_ids_requiring_legacy_fallback ) ) );

	foreach ( $legacy_active_metadata_by_post_ids as $this_post_id => $this_legacy_active_metadata ) {
		foreach ( $active_meta_tags as $this_active_meta_tag ) {
			if ( ! array_key_exists( $this_active_meta_tag, $this_legacy_active_metadata ) ) {
				continue;
			}

			if ( array_key_exists( $this_active_meta_tag, $reordered_results[ $this_post_id ] ?? array() ) ) {
				continue;
			}

			$reordered_results[ $this_post_id ][ $this_active_meta_tag ] = $this_legacy_active_metadata[ $this_active_meta_tag ];
		}
	}

	return $reordered_results;
}

// =========================================================================================== \\

/**
 * Function to read the post's metadata for a specific third party plugin from specific posts by the given post ids
 *
 * @param mixed $third_party_plugin_name The third party plugin name value.
 * @param array $post_ids of post ids (all int).
 * @return array the metadata by post-ids, using metadata-identifier keys
 */
function ai4seo_read_third_party_seo_plugin_metadata_by_post_ids( $third_party_plugin_name, array $post_ids ): array {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 915529372, 'Prevented loop', true );
		return array();
	}

	// cast all post ids to int with absint and filter out non-numeric entries.
	$post_ids = array_filter( array_map( 'absint', $post_ids ) );

	// Make sure that all parameters are not empty.
	if ( empty( $post_ids ) ) {
		return array();
	}

	// workaround for Slim SEO.
	if ( AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO === $third_party_plugin_name ) {
		return ai4seo_read_slim_seo_metadata_by_post_ids( $post_ids );
	}

	// workaround for Squirrly SEO.
	if ( AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO === $third_party_plugin_name ) {
		return ai4seo_read_squirrly_seo_metadata_by_post_ids( $post_ids );
	}

	// workaround for All in One SEO.
	if ( AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO === $third_party_plugin_name ) {
		return ai4seo_read_all_in_one_seo_metadata_by_post_ids( $post_ids );
	}

	$third_party_seo_plugin_details = ai4seo_get_third_party_seo_plugin_details();

	// Make sure that all parameters are of the correct type.
	$metadata_postmeta_keys = $third_party_seo_plugin_details[ $third_party_plugin_name ]['generation-field-postmeta-keys'] ?? array();

	if ( ! $metadata_postmeta_keys ) {
		return array();
	}

	$metadata_postmeta_keys              = ai4seo_deep_sanitize( $metadata_postmeta_keys );
	$metadata_postmeta_keys_placeholders = implode( ',', array_fill( 0, count( $metadata_postmeta_keys ), '%s' ) );

	$database_chunk_size = ai4seo_get_database_chunk_size();
	$post_ids_chunks     = array_chunk( $post_ids, $database_chunk_size );

	// reorder results, to make post_id the 2d key, then the meta_keys the 1d key and meta_value the value
	// also skip entries with empty meta_value.
	$third_party_seo_plugins_metadata = array();

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( empty( $this_post_ids_chunk ) ) {
			continue;
		}

		$this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

		// read directly from database by searching for entries in the postmeta table.
		$query_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->postmeta} WHERE meta_key IN ({$metadata_postmeta_keys_placeholders}) AND post_id IN ({$this_post_ids_placeholders})",
				...array_values( array_merge( $metadata_postmeta_keys, $this_post_ids_chunk ) )
			),
			ARRAY_A
		);

		// on error.
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321657, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! $query_results ) {
			continue;
		}

		foreach ( $query_results as $query_result ) {
			$this_post_id = $query_result['post_id'];

			// find metadata identifier.
			$this_metadata_identifier = array_search( $query_result['meta_key'], $metadata_postmeta_keys );

			if ( ! $this_metadata_identifier ) {
				continue;
			}

			$third_party_seo_plugins_metadata[ $this_post_id ][ $this_metadata_identifier ] = strval( $query_result['meta_value'] );
		}
	}

	return $third_party_seo_plugins_metadata;
}

// =========================================================================================== \\

/**
 * Function to read the post's metadata for the Slim SEO plugin from specific posts by the given post ids
 *
 * @param array $post_ids of post ids (all int).
 * @return array the metadata by post-ids, using metadata-identifier keys
 */
function ai4seo_read_slim_seo_metadata_by_post_ids( array $post_ids ): array {
	// check postmeta "slim_seo". It's serialized with keys "title" and "description", nothing else.
	$metadata_identifier_mapping = array(
		'meta-title'       => 'title',
		'meta-description' => 'description',
	);

	// read postmeta entries.
	global $wpdb;

	// make sure all post ids are absolute integers.
	$post_ids = array_map( 'absint', $post_ids );

	// reorder results, to make post_id the 2d key, then the meta_keys the 1d key and meta_value the value
	// also skip entries with empty meta_value.
	$third_party_plugins_metadata = array();

	$database_chunk_size = ai4seo_get_database_chunk_size();
	$post_ids_chunks     = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( empty( $this_post_ids_chunk ) ) {
			continue;
		}

		$this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

		$this_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ({$this_post_ids_placeholders})",
				...array_merge( array( 'slim_seo' ), $this_post_ids_chunk )
			),
			ARRAY_A
		);

		// on error.
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321658, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! $this_rows ) {
			continue;
		}

		foreach ( $this_rows as $this_row ) {
			$this_post_id  = (int) $this_row['post_id'];
			$this_metadata = maybe_unserialize( $this_row['meta_value'] );

			if ( ! $this_metadata ) {
				continue;
			}

			foreach ( $metadata_identifier_mapping as $this_metadata_identifier => $this_third_party_plugin_key ) {
				$third_party_plugins_metadata[ $this_post_id ][ $this_metadata_identifier ] = $this_metadata[ $this_third_party_plugin_key ] ?? '';
			}
		}
	}

	return $third_party_plugins_metadata;
}

// =========================================================================================== \\

/**
 * Returns the shared mapping between SOOZ identifiers and Squirrly's serialized SEO keys.
 *
 * @return array<string, string> Squirrly keys indexed by SOOZ metadata identifier.
 */
function ai4seo_get_squirrly_seo_metadata_identifier_mapping(): array {
	// Keep custom-table reads and writes on the same serialized Squirrly keys.
	return array(
		'meta-title'           => 'title',
		'meta-description'     => 'description',
		'keywords'             => 'keywords',
		'facebook-title'       => 'og_title',
		'facebook-description' => 'og_description',
		'twitter-title'        => 'tw_title',
		'twitter-description'  => 'tw_description',
	);
}

// =========================================================================================== \\

/**
 * Function to read the post's metadata for the Squirrly SEO plugin from specific posts by the given post ids
 *
 * @param array $post_ids of post ids (all int).
 * @return array the metadata by post-ids, using metadata-identifier keys
 */
function ai4seo_read_squirrly_seo_metadata_by_post_ids( array $post_ids ): array {
	// Check the prefixed qss table's serialized seo column for search, keyword, Open Graph, and Twitter values.
	$metadata_identifier_mapping = ai4seo_get_squirrly_seo_metadata_identifier_mapping();

	// read column "seo" in table "wp_qss".
	global $wpdb;

	// Initialize the values array.
	$all_squirrly_values = array();

	// Ensure post IDs are properly escaped and form the pattern for LIKE queries.
	$patterns = array_map(
		function ( $post_id ) {
			$post_id = intval( $post_id );
			return '%s:2:"ID";i:' . esc_sql( $post_id ) . ';%';
		},
		$post_ids
	);

	// Chunk pattern values to avoid oversized OR ... LIKE clauses.
	$database_chunk_size = ai4seo_get_database_chunk_size();
	$pattern_chunks      = array_chunk( $patterns, $database_chunk_size );

	foreach ( $pattern_chunks as $this_pattern_chunk ) {
		// Implode all patterns to use them in one SQL query chunk with multiple LIKE clauses.
		$like_clauses = implode( ' OR post LIKE ', array_fill( 0, count( $this_pattern_chunk ), '%s' ) );

		// Prepare the query to get SEO data for this chunk.
		$query = "
            SELECT post, seo
            FROM {$wpdb->prefix}qss
            WHERE post LIKE " . $like_clauses;

		// Execute the query
		// Safe: $query contains generated LIKE placeholders only; all pattern values are prepared here.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $query, ...$this_pattern_chunk ), OBJECT );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321682, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! $results ) {
			continue;
		}

		// Loop through the results and map them to the post IDs.
		foreach ( $results as $result ) {
			$post_id = false;

			// Check if the post data contains a serialized "ID" field.
			if ( preg_match( '/s:2:"ID";i:(\d+);/', $result->post, $matches ) ) {
				$post_id = intval( $matches[1] );
			}

			if ( $post_id ) {
				// Deserialize the SEO value without allowing malformed nested data to instantiate PHP objects.
				$this_posts_current_squirrly_values = maybe_unserialize( $result->seo );
				if ( is_string( $this_posts_current_squirrly_values ) && is_serialized( $this_posts_current_squirrly_values ) ) {
					$this_posts_current_squirrly_values = unserialize(
						$this_posts_current_squirrly_values,
						array( 'allowed_classes' => false )
					);
				}

				// Store the result for the post ID.
				if ( is_array( $this_posts_current_squirrly_values ) && ! empty( $this_posts_current_squirrly_values ) ) {
					$all_squirrly_values[ $post_id ] = $this_posts_current_squirrly_values;
				} else {
					$all_squirrly_values[ $post_id ] = array();
				}
			}
		}
	}

	// reorder results, to make post_id the 2d key, then the meta_keys the 1d key and meta_value the value
	// also skip entries with empty meta_value.
	$third_party_seo_plugins_metadata = array();

	foreach ( $all_squirrly_values as $post_id => $this_metadata ) {
		foreach ( $metadata_identifier_mapping as $this_metadata_identifier => $this_squirrly_seo_key ) {
			$third_party_seo_plugins_metadata[ $post_id ][ $this_metadata_identifier ] = $this_metadata[ $this_squirrly_seo_key ] ?? '';
		}
	}

	return $third_party_seo_plugins_metadata;
}

// =========================================================================================== \\

/**
 * Returns the shared mapping between SOOZ identifiers and AIOSEO table columns.
 *
 * @return array<string, string> AIOSEO column names indexed by SOOZ metadata identifier.
 */
function ai4seo_get_all_in_one_seo_metadata_identifier_mapping(): array {
	return array(
		'meta-title'           => 'title',
		'meta-description'     => 'description',
		'facebook-title'       => 'og_title',
		'facebook-description' => 'og_description',
		'twitter-title'        => 'twitter_title',
		'twitter-description'  => 'twitter_description',
	);
}

// =========================================================================================== \\

/**
 * Reads AIOSEO metadata for the requested posts from its canonical table.
 *
 * @param array $post_ids Post IDs.
 * @return array Metadata by post ID, using SOOZ metadata identifiers.
 */
function ai4seo_read_all_in_one_seo_metadata_by_post_ids( array $post_ids ): array {
	// Reuse the write-path allowlist so table reads and writes cannot drift to different columns.
	$metadata_identifier_mapping = ai4seo_get_all_in_one_seo_metadata_identifier_mapping();

	$post_ids = ai4seo_deep_sanitize( $post_ids, 'absint' );

	// Read mapped fields directly from AIOSEO's canonical table.
	global $wpdb;

	// Collect mapped metadata identifiers under their owning post IDs.
	$third_party_seo_plugins_metadata = array();

	$database_chunk_size = ai4seo_get_database_chunk_size();
	$post_ids_chunks     = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( empty( $this_post_ids_chunk ) ) {
			continue;
		}

		$this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

		$this_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}aioseo_posts WHERE post_id IN ({$this_post_ids_placeholders})",
				$this_post_ids_chunk
			),
			ARRAY_A
		);

		// on error.
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321660, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( empty( $this_rows ) ) {
			continue;
		}

		foreach ( $this_rows as $this_row ) {
			$this_post_id = (int) $this_row['post_id'];

			foreach ( $metadata_identifier_mapping as $this_metadata_identifier => $this_aioseo_key ) {
				$third_party_seo_plugins_metadata[ $this_post_id ][ $this_metadata_identifier ] = $this_row[ $this_aioseo_key ] ?? '';
			}
		}
	}

	return $third_party_seo_plugins_metadata;
}

// =========================================================================================== \\

/**
 * Returns the number of metadata fields
 *
 * @return int the number of metadata fields
 */
function ai4seo_get_num_metadata_fields(): int {
	return defined( 'AI4SEO_METADATA_DETAILS' ) ? count( AI4SEO_METADATA_DETAILS ) : 0;
}

// =========================================================================================== \\

function ai4seo_read_available_metadata( int $post_id, bool $consider_third_party_seo_plugin_metadata = true ): array {
	$available_metadata_by_post_ids = ai4seo_read_available_metadata_by_post_ids( array( $post_id ), $consider_third_party_seo_plugin_metadata );

	if ( ! isset( $available_metadata_by_post_ids[ $post_id ] ) ) {
		return array();
	}

	return $available_metadata_by_post_ids[ $post_id ];
}

// =========================================================================================== \\

/**
 * Function to read all the available metadata, regardless of the source, for a specific post by the given post id
 *
 * @param array $post_ids of post ids.
 * @param bool  $consider_third_party_seo_plugin_metadata if true, the own plugin's metadata will be preferred.
 * @return array the post meta coverage by post ids
 */
function ai4seo_read_available_metadata_by_post_ids( array $post_ids, bool $consider_third_party_seo_plugin_metadata = true ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 615102829, 'Prevented loop', true );
		return array();
	}

	// make sure post_ids is not empty.
	if ( empty( $post_ids ) ) {
		return array();
	}

	if ( ! defined( 'AI4SEO_METADATA_DETAILS' ) ) {
		return array();
	}

	// make sure all entries of post_ids are numeric.
	foreach ( $post_ids as $key => $post_id ) {
		if ( ! is_numeric( $post_id ) ) {
			ai4seo_debug_message( 964175850, 'post_id is not numeric: ' . ai4seo_stringify( $post_id ), true );
			return array();
		}
	}

	$available_metadata = array();

	// 1. read our own plugin's metadata
	$our_plugins_metadata_by_post_ids = ai4seo_read_our_plugins_metadata_by_post_ids( $post_ids );

	foreach ( $post_ids as $this_key => $this_post_id ) {
		$this_posts_got_missing_metadata = false;

		foreach ( AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_details ) {
			$available_metadata[ $this_post_id ][ $this_metadata_identifier ] = $our_plugins_metadata_by_post_ids[ $this_post_id ][ $this_metadata_identifier ] ?? '';

			// still empty -> mark as missing.
			if ( empty( $available_metadata[ $this_post_id ][ $this_metadata_identifier ] ) ) {
				$this_posts_got_missing_metadata = true;
			}
		}

		// if we have every metadata field filled, remove the post id from the array.
		if ( ! $this_posts_got_missing_metadata ) {
			unset( $post_ids[ $this_key ] );
		}
	}

	// should we consider third party seo plugins?
	if ( ! $consider_third_party_seo_plugin_metadata ) {
		return $available_metadata;
	}

	// all posts are filled with our own metadata? return the metadata here.
	if ( count( $post_ids ) === 0 ) {
		return $available_metadata;
	}

	// if not, we...

	// 2. check third party seo plugins
	$active_third_party_seo_plugin_details = ai4seo_get_active_third_party_seo_plugin_details();

	foreach ( $active_third_party_seo_plugin_details as $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details ) {
		$this_third_plugins_plugins_metadata_by_post_ids = ai4seo_read_third_party_seo_plugin_metadata_by_post_ids( $this_third_party_seo_plugin_identifier, $post_ids );

		if ( ! $this_third_plugins_plugins_metadata_by_post_ids ) {
			continue;
		}

		foreach ( $post_ids as $this_key => $this_post_id ) {
			$this_posts_got_missing_metadata = false;

			foreach ( AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_details ) {
				// skip if we already have the meta value from our own plugin (or any other third party plugin).
				if ( $available_metadata[ $this_post_id ][ $this_metadata_identifier ] ) {
					continue;
				}

				$available_metadata[ $this_post_id ][ $this_metadata_identifier ] = $this_third_plugins_plugins_metadata_by_post_ids[ $this_post_id ][ $this_metadata_identifier ] ?? '';

				// still empty -> mark as missing.
				if ( empty( $available_metadata[ $this_post_id ][ $this_metadata_identifier ] ) ) {
					$this_posts_got_missing_metadata = true;
				}
			}

			// if we have every metadata field filled, remove the post id from the array.
			if ( ! $this_posts_got_missing_metadata ) {
				unset( $post_ids[ $this_key ] );
			}
		}

		// all posts are filled with our own metadata? return the metadata here.
		if ( count( $post_ids ) === 0 ) {
			return $available_metadata;
		}
	}

	return $available_metadata;
}

// =========================================================================================== \\

/**
 * Function to return the amount of active metadata per post id
 *
 * @param array $post_ids of post ids.
 * @return array the amount of active metadata by post ids
 */
function ai4seo_read_num_available_metadata_by_post_ids( array $post_ids ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__, 1, 99999 ) ) {
		ai4seo_debug_message( 561144878, 'Prevented loop', true );
		return array();
	}

	if ( ! defined( 'AI4SEO_METADATA_DETAILS' ) ) {
		return array();
	}

	$active_meta_tags         = ai4seo_get_active_meta_tags();
	$focus_keyphrase_behavior = ai4seo_get_setting( AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA );
	$overwrite_metadata       = ai4seo_get_setting( AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA );

	if ( ! is_array( $overwrite_metadata ) ) {
		$overwrite_metadata = array();
	}

	$available_metadata = ai4seo_read_available_metadata_by_post_ids( $post_ids );

	if ( ! $available_metadata ) {
		return array();
	}

	// generate a summary of the post meta coverage array.
	$num_available_metadata_by_post_ids = array();

	foreach ( $available_metadata as $post_id => $this_metadata_entry ) {
		$num_available_metadata_by_post_ids[ $post_id ] = 0;

		foreach ( AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_details ) {
			if ( ! in_array( $this_metadata_identifier, $active_meta_tags, true ) ) {
				continue;
			}

			if ( isset( $this_metadata_entry[ $this_metadata_identifier ] ) && $this_metadata_entry[ $this_metadata_identifier ] ) {
				++$num_available_metadata_by_post_ids[ $post_id ];
			}
		}

		// workaround -> if we skip the focus keyphrase, but meta title and meta description are set, count it as available metadata.
		if ( ( ! isset( $this_metadata_entry['focus-keyphrase'] ) || ! $this_metadata_entry['focus-keyphrase'] )
			&& in_array( 'focus-keyphrase', $active_meta_tags, true )
			&& isset( $this_metadata_entry['meta-title'] ) && $this_metadata_entry['meta-title']
			&& isset( $this_metadata_entry['meta-description'] ) && $this_metadata_entry['meta-description']
		) {
			if ( AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP === $focus_keyphrase_behavior ) {
				++$num_available_metadata_by_post_ids[ $post_id ];
			}

			if ( AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE === $focus_keyphrase_behavior
				&& ! in_array( 'meta-title', $overwrite_metadata, true )
				&& ! in_array( 'meta-description', $overwrite_metadata, true ) ) {
				++$num_available_metadata_by_post_ids[ $post_id ];
			}
		}
	}

	return $num_available_metadata_by_post_ids;
}

// =========================================================================================== \\

/**
 * Function to return the percentage of active metadata per post id
 *
 * @param array $post_ids of post ids.
 * @param int   $round_precision the precision to round the percentage to.
 * @return array the amount of active metadata by post ids
 */
function ai4seo_read_percentage_of_available_metadata_by_post_ids( array $post_ids, int $round_precision = 0 ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 343030419, 'Prevented loop', true );
		return array();
	}

	$active_meta_tags = ai4seo_get_active_meta_tags();

	// no active meta tags -> return 100% for all posts if no active meta tags are defined.
	if ( ! $active_meta_tags || count( $active_meta_tags ) === 0 ) {
		$percentage_of_active_metadata_by_post_ids = array();

		foreach ( $post_ids as $this_post_id ) {
			$percentage_of_active_metadata_by_post_ids[ $this_post_id ] = 100;
		}

		return $percentage_of_active_metadata_by_post_ids;
	}

	// first read how many metadata values are available per post id,
	// then compare it with the total amount of active meta tags.
	$num_available_metadata_by_post_ids = ai4seo_read_num_available_metadata_by_post_ids( $post_ids );

	$num_active_meta_tags = count( $active_meta_tags );

	$percentage_of_active_metadata_by_post_ids = array();

	foreach ( $num_available_metadata_by_post_ids as $this_post_id => $this_num_active_metadata ) {
		$percentage_of_active_metadata_by_post_ids[ $this_post_id ] = round( ( $this_num_active_metadata / $num_active_meta_tags ) * 100, $round_precision );
		$percentage_of_active_metadata_by_post_ids[ $this_post_id ] = min( 100, max( 0, $percentage_of_active_metadata_by_post_ids[ $this_post_id ] ) );
	}

	return $percentage_of_active_metadata_by_post_ids;
}

// =========================================================================================== \\

/**
 * Refreshes the metadata coverage for the given post by putting the post id into the corresponding option
 *
 * @param int          $post_id The post id to refresh the metadata coverage for.
 * @param WP_Post|null $post The post object to refresh the metadata coverage for.
 * @return void
 */
function ai4seo_refresh_one_posts_metadata_coverage_status( int $post_id, $post = null ) {
	if ( ! is_numeric( $post_id ) ) {
		return;
	}

	// remove post id if it's not a valid post.
	if ( ! ai4seo_is_post_a_valid_content_post( $post_id, $post ) ) {
		ai4seo_remove_post_ids_from_all_options( $post_id );
		return;
	}

	// consider which option to put the post id into.
	if ( ai4seo_read_is_posts_metadata_fully_covered( $post_id ) ) {
		ai4seo_add_post_ids_to_option( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_id );

		// check if the post has generated data.
		if ( ai4seo_post_has_generated_data( $post_id ) ) {
			ai4seo_add_post_ids_to_option( AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME, $post_id );
		}
	} else {
		ai4seo_add_post_ids_to_option( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME, $post_id );
	}
}

// =========================================================================================== \\

/**
 * Function to check if this post is a valid content post to be considered by our plugin
 *
 * @param int          $post_id The post id to check.
 * @param WP_Post|null $post The post value.
 * @return bool Whether the post is a valid content post
 */
function ai4seo_is_post_a_valid_content_post( int $post_id, ?WP_Post $post = null ): bool {
	if ( ! is_numeric( $post_id ) ) {
		return false;
	}

	// read post.
	if ( null === $post ) {
		$post = get_post( $post_id );
	}

	// check if the post could be read.
	if ( ! $post || is_wp_error( $post ) || ! isset( $post->post_type ) ) {
		return false;
	}

	// supported post types.
	$supported_post_types = ai4seo_get_supported_post_types();

	// check if the post is supported.
	if ( ! in_array( $post->post_type, $supported_post_types ) ) {
		return false;
	}

	// check post status.
	if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private', 'pending' ) ) ) {
		return false;
	}

	return true;
}

// =========================================================================================== \\

/**
 * Checks if the metadata for a given post is fully covered
 *
 * @param int $post_id The post id to check the metadata coverage for.
 * @return bool Whether the metadata for a given post is fully covered
 */
function ai4seo_read_is_posts_metadata_fully_covered( int $post_id ): bool {
	$percentage_of_active_metadata_by_post_ids = ai4seo_read_percentage_of_available_metadata_by_post_ids( array( $post_id ) );

	return ( ( $percentage_of_active_metadata_by_post_ids[ $post_id ] ?? 0 ) == 100 );
}

// =========================================================================================== \\

/**
 * Removes all post ids for all or a specific post type and generation status. It's recommended to run
 *
 * @param string $post_type The post type to remove the post ids for
 * @param string $generation_status_option_name The generation status option name to remove the post ids for
 * @return void
 */
function ai4seo_remove_all_post_ids_by_post_type_and_generation_status( string $post_type, string $generation_status_option_name ) {
	global $wpdb;

	$post_type = sanitize_text_field( $post_type );

	// read all ids from $generation_status_option_name and check which of them are of the given post_type.
	$post_ids = ai4seo_get_post_ids_from_option( $generation_status_option_name );

	// no failed posts? skip here.
	if ( ! $post_ids ) {
		return;
	}

	// make sure all post ids are absolute integers.
	$post_ids = array_map( 'absint', $post_ids );

	$possible_post_ids_of_post_type = array();
	$database_chunk_size            = ai4seo_get_database_chunk_size();
	$post_ids_chunks                = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( empty( $this_post_ids_chunk ) ) {
			continue;
		}

		$this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

		// nail down the post_type.
		$this_possible_post_ids_of_post_type = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID IN ({$this_post_ids_placeholders})",
				...array_merge( array( $post_type ), $this_post_ids_chunk )
			)
		);

		// on error.
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321661, 'Database error: ' . $wpdb->last_error, true );
			return;
		}

		if ( empty( $this_possible_post_ids_of_post_type ) ) {
			continue;
		}

		$possible_post_ids_of_post_type = array_merge( $possible_post_ids_of_post_type, $this_possible_post_ids_of_post_type );
	}

	if ( ! $possible_post_ids_of_post_type ) {
		return;
	}

	// remove all post_ids of the given post_type from $generation_status_option_name.
	ai4seo_remove_post_ids_from_option( $generation_status_option_name, $possible_post_ids_of_post_type );
}

// =========================================================================================== \\

/**
 * Decodes a generated-data postmeta value.
 *
 * @param mixed $generated_data_raw The raw postmeta value.
 * @return array
 */
function ai4seo_decode_generated_data_postmeta_value( $generated_data_raw ): array {
	// Empty postmeta values mean there is no generated-data snapshot to attribute in editor labels.
	if ( ! $generated_data_raw ) {
		return array();
	}

	// Older code paths may hand over already decoded arrays, so keep that format supported.
	if ( is_array( $generated_data_raw ) ) {
		return ai4seo_deep_sanitize( $generated_data_raw );
	}

	if ( ! is_string( $generated_data_raw ) ) {
		return array();
	}

	// Current generated data is JSON; the legacy cache migration may still surface serialized data.
	$generated_data = json_decode( $generated_data_raw, true );

	if ( ! is_array( $generated_data ) ) {
		$generated_data = maybe_unserialize( $generated_data_raw );
	}

	if ( ! is_array( $generated_data ) ) {
		return array();
	}

	return ai4seo_deep_sanitize( $generated_data );
}

// =========================================================================================== \\

/**
 * Prepares a generated-data field value for storage and exact editor comparisons.
 *
 * @param string $generated_data_identifier The generated field identifier.
 * @param mixed  $generated_data_value The generated field value.
 * @return string
 */
function ai4seo_prepare_generated_data_field_value( string $generated_data_identifier, $generated_data_value ): string {
	// Generated-data values must follow editor limits because source matching compares against editor inputs.
	$generated_data_value = ai4seo_normalize_editor_input_value( $generated_data_value );
	$max_length           = ai4seo_get_max_editor_input_length( $generated_data_identifier );

	return ai4seo_trim_string_to_length( $generated_data_value, $max_length );
}

// =========================================================================================== \\

/**
 * Prepares generated data for editor usage while keeping generated_at separate.
 *
 * @param array $generated_data The decoded generated data.
 * @return array
 */
function ai4seo_prepare_generated_data_details( array $generated_data ): array {
	// Keep generated_at at entry level so field-value readers never treat it as generated content.
	$prepared_generated_data = array();
	$generated_at            = absint( $generated_data['generated_at'] ?? 0 );

	unset( $generated_data['generated_at'] );

	// Field values use the same normalization and length limits as editor inputs for exact matching.
	foreach ( $generated_data as $this_generated_data_identifier => $this_generated_data_value ) {
		if ( ! is_string( $this_generated_data_value ) && ! is_scalar( $this_generated_data_value ) ) {
			continue;
		}

		$this_generated_data_identifier = sanitize_key( $this_generated_data_identifier );

		if ( ! $this_generated_data_identifier ) {
			continue;
		}

		$this_generated_data_value = ai4seo_prepare_generated_data_field_value(
			$this_generated_data_identifier,
			$this_generated_data_value
		);

		$prepared_generated_data[ $this_generated_data_identifier ] = $this_generated_data_value;
	}

	return array(
		'generated_data' => $prepared_generated_data,
		'generated_at'   => $generated_at,
	);
}

// =========================================================================================== \\

/**
 * Reads generated data details for a given post, if they exist.
 *
 * @param int $post_id The post id.
 * @return array
 */
function ai4seo_read_generated_data_details_from_post_meta( int $post_id ): array {
	// The detail reader is the only generated-data reader that exposes the entry-level timestamp.
	$generated_data_raw = get_post_meta( $post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, true );
	$generated_data     = ai4seo_decode_generated_data_postmeta_value( $generated_data_raw );

	if ( ! $generated_data ) {
		return array(
			'generated_data' => array(),
			'generated_at'   => 0,
		);
	}

	return ai4seo_prepare_generated_data_details( $generated_data );
}

// =========================================================================================== \\

/**
 * Reads the generated field values for a given post, if they exist.
 *
 * @param int $post_id The post id.
 * @return array
 */
function ai4seo_read_generated_data_from_post_meta( int $post_id ): array {
	// Existing callers expect only generated field values and must not receive generated_at.
	$generated_data_details = ai4seo_read_generated_data_details_from_post_meta( $post_id );

	return $generated_data_details['generated_data'] ?? array();
}

// =========================================================================================== \\

/**
 * Function to save the generated data for a given post.
 *
 * @param int   $post_id The post id.
 * @param array $generated_data The generated data.
 * @param bool  $update_generated_at Whether to update the top-level generated_at timestamp.
 * @param int   $generated_at The generated-at timestamp to store. Uses the current time when empty.
 * @param array $unresolved_fields Requested field identifiers omitted from a partial response.
 * @return bool
 */
function ai4seo_save_generated_data_to_postmeta(
	int $post_id,
	array $generated_data,
	bool $update_generated_at = true,
	int $generated_at = 0,
	array $unresolved_fields = array()
): bool {
	// Merge into the existing flat snapshot while removing stale provenance for explicitly unresolved fields.
	$generated_data_details = ai4seo_read_generated_data_details_from_post_meta( $post_id );
	$old_generated_data     = $generated_data_details['generated_data'] ?? array();
	$old_generated_at       = absint( $generated_data_details['generated_at'] ?? 0 );
	$is_field_value_updated = false;

	if ( ! $old_generated_data ) {
		$old_generated_data = array();
	}

	// Omitted requested fields keep their live values, but must lose stale generated provenance so they remain eligible later.
	foreach ( $unresolved_fields as $this_unresolved_field ) {
		$this_unresolved_field = sanitize_key( $this_unresolved_field );

		if ( $this_unresolved_field && 'generated_at' !== $this_unresolved_field ) {
			unset( $old_generated_data[ $this_unresolved_field ] );
		}
	}

	foreach ( $generated_data as $this_generated_data_identifier => $this_generated_data_value ) {
		$this_generated_data_identifier = sanitize_key( $this_generated_data_identifier );

		// generated_at is managed as entry metadata, never as a generated field value.
		if ( ! $this_generated_data_identifier || 'generated_at' === $this_generated_data_identifier ) {
			continue;
		}

		if ( ! is_string( $this_generated_data_value ) && ! is_scalar( $this_generated_data_value ) ) {
			continue;
		}

		// Field values are prepared through the shared generated-data path used by exact-match source checks.
		$this_generated_data_value = ai4seo_prepare_generated_data_field_value(
			$this_generated_data_identifier,
			$this_generated_data_value
		);

		$old_generated_data[ $this_generated_data_identifier ] = $this_generated_data_value;
		$is_field_value_updated                                = true;
	}

	// Manual and bulk generations stamp the snapshot; legacy migrations keep their unknown date.
	if ( $update_generated_at && $is_field_value_updated ) {
		$old_generated_data['generated_at'] = $generated_at > 0 ? absint( $generated_at ) : time();
	} elseif ( $old_generated_at > 0 ) {
		$old_generated_data['generated_at'] = $old_generated_at;
	}

	// Keep the generated-data postmeta flat: field identifiers plus the optional top-level generated_at entry.
	$generated_data_json_string = wp_json_encode( $old_generated_data, JSON_UNESCAPED_UNICODE );

	return ai4seo_update_post_meta(
		$post_id,
		AI4SEO_POST_META_GENERATED_DATA_META_KEY,
		$generated_data_json_string
	);
}

// =========================================================================================== \\

/**
 * Collects third-party SEO metadata source candidates for the metadata editor.
 *
 * @param int $post_id The post id.
 * @return array
 */
function ai4seo_read_metadata_editor_third_party_source_candidates( int $post_id ): array {
	// Source candidates are only needed for the SOOZ modal, not for the generic metadata reader.
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		return array();
	}

	$active_third_party_seo_plugin_details = ai4seo_get_active_third_party_seo_plugin_details();

	if ( ! $active_third_party_seo_plugin_details ) {
		return array();
	}

	$source_candidates = array();

	// Keep plugin identity next to each value because the active metadata reader intentionally drops source data.
	foreach ( $active_third_party_seo_plugin_details as $this_third_party_plugin_identifier => $this_third_party_plugin_details ) {
		$this_third_party_metadata_by_post_ids = ai4seo_read_third_party_seo_plugin_metadata_by_post_ids( $this_third_party_plugin_identifier, array( $post_id ) );
		$this_third_party_metadata             = $this_third_party_metadata_by_post_ids[ $post_id ] ?? array();

		if ( ! $this_third_party_metadata ) {
			continue;
		}

		$this_third_party_plugin_name = sanitize_text_field( $this_third_party_plugin_details['name'] ?? $this_third_party_plugin_identifier );

		foreach ( $this_third_party_metadata as $this_metadata_identifier => $this_metadata_value ) {
			$this_metadata_identifier = sanitize_key( $this_metadata_identifier );

			if ( ! $this_metadata_identifier || ( ! is_string( $this_metadata_value ) && ! is_scalar( $this_metadata_value ) ) ) {
				continue;
			}

			$this_metadata_value = ai4seo_normalize_editor_input_value( $this_metadata_value );

			if ( '' === $this_metadata_value ) {
				continue;
			}

			$source_candidates[ $this_metadata_identifier ][] = array(
				'plugin_name' => $this_third_party_plugin_name,
				'value'       => $this_metadata_value,
			);
		}
	}

	return $source_candidates;
}

// =========================================================================================== \\

/**
 * Builds source details for a SOOZ-generated editor field value.
 *
 * @param int  $generated_at The generated-at timestamp.
 * @param bool $was_changed_by_user Whether the active value differs from the generated value.
 * @return array
 */
function ai4seo_get_editor_field_sooz_source_details( int $generated_at = 0, bool $was_changed_by_user = false ): array {
	return array(
		'source_type'         => 'sooz',
		'generated_at'        => absint( $generated_at ),
		'was_changed_by_user' => $was_changed_by_user,
	);
}

// =========================================================================================== \\

/**
 * Builds source details for a third-party SEO editor field value.
 *
 * @param string $plugin_name The third-party plugin name.
 * @param bool   $was_changed_by_user Whether the active value differs from the imported value.
 * @param bool   $is_live_editor_value Whether the value came from the plugin's current unsaved editor state.
 * @return array
 */
function ai4seo_get_editor_field_third_party_source_details(
	string $plugin_name,
	bool $was_changed_by_user = false,
	bool $is_live_editor_value = false
): array {
	// Keep persisted-import and live-editor provenance in one shape for the shared source-message renderer.
	return array(
		'source_type'          => 'third_party_seo_plugin',
		'plugin_name'          => sanitize_text_field( $plugin_name ),
		'was_changed_by_user'  => $was_changed_by_user,
		'is_live_editor_value' => $is_live_editor_value,
	);
}

// =========================================================================================== \\

/**
 * Reads source details for Metadata Editor fields.
 *
 * Match priority:
 * 1. SOOZ generated data exact match.
 * 2. Third-party SEO plugin exact match.
 * 3. SOOZ generated data exists, active value differs.
 * 4. Third-party SEO plugin value exists, active value differs.
 *
 * @param int   $post_id The post id.
 * @param array $active_metadata The active metadata values shown in the editor.
 * @return array
 */
function ai4seo_read_metadata_editor_source_details( int $post_id, array $active_metadata ): array {
	// Metadata source labels are derived only for the SOOZ editor modal and leave active metadata resolution unchanged.
	$post_id = absint( $post_id );

	if ( ! $post_id || ! defined( 'AI4SEO_METADATA_DETAILS' ) ) {
		return array();
	}

	$generated_data_details        = ai4seo_read_generated_data_details_from_post_meta( $post_id );
	$generated_data                = $generated_data_details['generated_data'] ?? array();
	$generated_at                  = absint( $generated_data_details['generated_at'] ?? 0 );
	$third_party_source_candidates = ai4seo_read_metadata_editor_third_party_source_candidates( $post_id );
	$source_details                = array();

	// SOOZ-generated values are checked before third-party values to handle plugin-sync copies correctly.
	foreach ( AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_details ) {
		$this_active_value                = ai4seo_normalize_editor_input_value( $active_metadata[ $this_metadata_identifier ] ?? '' );
		$this_generated_value             = ai4seo_normalize_editor_input_value( $generated_data[ $this_metadata_identifier ] ?? '' );
		$this_has_generated_value         = array_key_exists( $this_metadata_identifier, $generated_data ) && '' !== $this_generated_value;
		$this_third_party_candidates      = $third_party_source_candidates[ $this_metadata_identifier ] ?? array();
		$this_first_third_party_candidate = array();
		$this_exact_third_party_candidate = array();

		// Keep the first third-party value as the fallback source when no exact third-party match exists.
		foreach ( $this_third_party_candidates as $this_third_party_candidate ) {
			$this_third_party_value = ai4seo_normalize_editor_input_value( $this_third_party_candidate['value'] ?? '' );

			if ( '' === $this_third_party_value ) {
				continue;
			}

			if ( ! $this_first_third_party_candidate ) {
				$this_first_third_party_candidate = $this_third_party_candidate;
			}

			if ( $this_active_value === $this_third_party_value ) {
				$this_exact_third_party_candidate = $this_third_party_candidate;
				break;
			}
		}

		if ( $this_has_generated_value && $this_active_value === $this_generated_value ) {
			$source_details[ $this_metadata_identifier ] = ai4seo_get_editor_field_sooz_source_details( $generated_at, false );
			continue;
		}

		if ( $this_exact_third_party_candidate ) {
			$source_details[ $this_metadata_identifier ] = ai4seo_get_editor_field_third_party_source_details( $this_exact_third_party_candidate['plugin_name'] ?? '', false );
			continue;
		}

		if ( $this_has_generated_value ) {
			$source_details[ $this_metadata_identifier ] = ai4seo_get_editor_field_sooz_source_details( $generated_at, true );
			continue;
		}

		if ( $this_first_third_party_candidate ) {
			$source_details[ $this_metadata_identifier ] = ai4seo_get_editor_field_third_party_source_details( $this_first_third_party_candidate['plugin_name'] ?? '', true );
		}
	}

	return $source_details;
}

// =========================================================================================== \\

/**
 * Reads source details for Media Attributes Editor fields.
 *
 * @param int   $attachment_post_id The attachment post id.
 * @param array $active_attachment_attributes The active media attribute values shown in the editor.
 * @return array
 */
function ai4seo_read_attachment_attributes_editor_source_details( int $attachment_post_id, array $active_attachment_attributes ): array {
	// Media attributes do not have third-party SEO sources, so only SOOZ generated-data snapshots are considered.
	$attachment_post_id = absint( $attachment_post_id );

	if ( ! $attachment_post_id || ! defined( 'AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS' ) ) {
		return array();
	}

	$generated_data_details = ai4seo_read_generated_data_details_from_post_meta( $attachment_post_id );
	$generated_data         = $generated_data_details['generated_data'] ?? array();
	$generated_at           = absint( $generated_data_details['generated_at'] ?? 0 );
	$source_details         = array();

	// Compare against the active attachment attributes shown in the modal to detect user edits.
	foreach ( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $this_attachment_attribute_identifier => $this_attachment_attribute_details ) {
		$this_active_value        = ai4seo_normalize_editor_input_value( $active_attachment_attributes[ $this_attachment_attribute_identifier ] ?? '' );
		$this_generated_value     = ai4seo_normalize_editor_input_value( $generated_data[ $this_attachment_attribute_identifier ] ?? '' );
		$this_has_generated_value = array_key_exists( $this_attachment_attribute_identifier, $generated_data ) && '' !== $this_generated_value;

		if ( ! $this_has_generated_value ) {
			continue;
		}

		$source_details[ $this_attachment_attribute_identifier ] = ai4seo_get_editor_field_sooz_source_details(
			$generated_at,
			$this_active_value !== $this_generated_value
		);
	}

	return $source_details;
}

// =========================================================================================== \\

/**
 * Normalizes a postmeta value for comparisons against WordPress read-back values.
 *
 * @param mixed $meta_value Metadata value.
 * @return string
 */
function ai4seo_normalize_post_meta_value_for_comparison( $meta_value ): string {
	// WordPress returns scalar postmeta as strings and unserializes supported complex values.
	if ( is_scalar( $meta_value ) || null === $meta_value ) {
		return (string) $meta_value;
	}

	return maybe_serialize( $meta_value );
}

// =========================================================================================== \\

/**
 * Checks whether a postmeta update reached its requested effective state.
 *
 * @param int    $post_id              Post ID used by update_post_meta().
 * @param string $meta_key             Metadata key.
 * @param mixed  $meta_value           Requested metadata value before slashing.
 * @param mixed  $prev_value           Optional previous value constraint.
 * @param array  $previous_meta_values Values read before a constrained update.
 * @return bool
 */
function ai4seo_did_post_meta_update_reach_requested_state(
	int $post_id,
	string $meta_key,
	$meta_value,
	$prev_value = '',
	array $previous_meta_values = array()
): bool {
	// Match update_post_meta() by evaluating revision writes against their parent post.
	$revision_parent_post_id = wp_is_post_revision( $post_id );

	if ( $revision_parent_post_id ) {
		$post_id = absint( $revision_parent_post_id );
	}

	// Bypass any value cached before the failed/short-circuited write when verifying effective storage.
	wp_cache_delete( $post_id, 'post_meta' );

	// Compare against the value after the same metadata sanitization WordPress applies during persistence.
	$meta_key            = wp_unslash( $meta_key );
	$meta_subtype        = get_object_subtype( 'post', $post_id );
	$expected_meta_value = sanitize_meta( $meta_key, $meta_value, 'post', $meta_subtype );
	$expected_comparison = ai4seo_normalize_post_meta_value_for_comparison( $expected_meta_value );
	$latest_meta_values   = get_post_meta( $post_id, $meta_key, false );
	$latest_meta_values   = is_array( $latest_meta_values ) ? $latest_meta_values : array();
	$matching_value_count = 0;

	// An unconstrained update must align every duplicate row, not merely find one matching row.
	foreach ( $latest_meta_values as $latest_meta_value ) {
		$latest_comparison = ai4seo_normalize_post_meta_value_for_comparison( $latest_meta_value );

		if ( $expected_comparison === $latest_comparison ) {
			$matching_value_count++;
		}
	}

	// An absent key and an empty requested value have the same effective WordPress metadata value.
	if ( ! $latest_meta_values ) {
		return '' === $expected_comparison;
	}

	// Without a previous-value constraint, update_post_meta() targets every row for this key.
	if ( '' === $prev_value ) {
		return count( $latest_meta_values ) === $matching_value_count;
	}

	// A constrained update becomes an insert when no metadata row existed before the request.
	if ( ! $previous_meta_values ) {
		return $matching_value_count > 0;
	}

	// Constrained updates must account for every previously eligible duplicate row.
	$expected_previous_meta_value  = sanitize_meta( $meta_key, $prev_value, 'post', $meta_subtype );
	$previous_comparison           = ai4seo_normalize_post_meta_value_for_comparison( $expected_previous_meta_value );
	$previous_target_value_count   = 0;
	$previous_expected_value_count = 0;

	foreach ( $previous_meta_values as $previous_meta_value ) {
		$this_previous_comparison = ai4seo_normalize_post_meta_value_for_comparison( $previous_meta_value );

		if ( $previous_comparison === $this_previous_comparison ) {
			$previous_target_value_count++;
		}

		if ( $expected_comparison === $this_previous_comparison ) {
			$previous_expected_value_count++;
		}
	}

	// Existing rows with no matching previous value mean WordPress had nothing eligible to update.
	if ( 0 === $previous_target_value_count ) {
		return false;
	}

	// A same-value constrained update succeeds only while every previously matching row remains present.
	if ( $expected_comparison === $previous_comparison ) {
		return $matching_value_count >= $previous_expected_value_count;
	}

	$latest_previous_value_count = 0;

	foreach ( $latest_meta_values as $latest_meta_value ) {
		$latest_comparison = ai4seo_normalize_post_meta_value_for_comparison( $latest_meta_value );

		if ( $previous_comparison === $latest_comparison ) {
			$latest_previous_value_count++;
		}
	}

	// Every targeted row must disappear and contribute a corresponding requested-value row.
	return 0 === $latest_previous_value_count
		&& $matching_value_count >= ( $previous_expected_value_count + $previous_target_value_count );
}

// =========================================================================================== \\

/**
 * Safer wrapper for update_post_meta(). Same parameters and order.
 *
 * @param int    $post_id     Post ID.
 * @param string $meta_key    Metadata key.
 * @param mixed  $meta_value  Metadata value. Can be any serializable type.
 * @param mixed  $prev_value  Optional. Previous value to check before updating.
 *
 * @return bool True when the requested state was persisted or was already effective. False otherwise.
 */
function ai4seo_update_post_meta( int $post_id, string $meta_key, $meta_value, $prev_value = '' ): bool {
	// Basic validation to avoid useless DB calls.
	$post_id = absint( $post_id );

	if ( $post_id <= 0 || '' === $meta_key ) {
		return false;
	}

	// Ensure the post exists. get_post() is cached by WP, cheap enough.
	if ( ! get_post( $post_id ) ) {
		return false;
	}

	// Resolve revisions before capturing previous values so read-back checks use WordPress's actual target.
	$revision_parent_post_id = wp_is_post_revision( $post_id );

	if ( $revision_parent_post_id ) {
		$post_id = absint( $revision_parent_post_id );
	}

	global $wpdb;

	// Preserve pre-write rows only when a previous-value constraint affects which duplicates are eligible.
	$previous_meta_values = array();

	if ( '' !== $prev_value ) {
		$previous_meta_values = get_post_meta( $post_id, $meta_key, false );

		if ( ! is_array( $previous_meta_values ) ) {
			$previous_meta_values = array();
		}
	}

	// Retain the unslashed request because the WordPress call receives a separately slashed value below.
	$requested_meta_value = $meta_value;

	// Capture and suppress low-level DB errors for a clean boolean outcome.
	$previous_suppress_errors = $wpdb->suppress_errors( true );

	// Ignore stale errors from earlier queries when classifying this specific metadata operation.
	$wpdb->last_error = '';

	// Slash the value for update_post_meta().
	$meta_value = wp_slash( $meta_value );

	// Delegate the write to WordPress so metadata filters and short-circuits remain authoritative.
	$update_result = update_post_meta( $post_id, $meta_key, $meta_value, $prev_value );

	// Capture the operation error before restoring the caller's database error-display preference.
	$database_error_occurred = ! empty( $wpdb->last_error );

	$wpdb->suppress_errors( $previous_suppress_errors );

	if ( $database_error_occurred ) {
		ai4seo_debug_message( 984321662, 'Database error during update_post_meta: ' . $wpdb->last_error, true );
		return false;
	}

	if ( false !== $update_result ) {
		return true;
	}

	// WordPress also returns false for unchanged values, so verify the effective state before reporting failure.
	return ai4seo_did_post_meta_update_reach_requested_state(
		$post_id,
		$meta_key,
		$requested_meta_value,
		$prev_value,
		$previous_meta_values
	);
}

// =========================================================================================== \\

/**
 * Function to read the post content summary for a given post
 *
 * @param int $post_id the post id.
 * @return string
 */
function ai4seo_read_post_content_summary_from_post_meta( int $post_id ): string {
	// reading in post meta, looking for the meta_key AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY.
	$post_content_summary = get_post_meta( $post_id, AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY, true );

	if ( ! $post_content_summary ) {
		return '';
	}

	return sanitize_text_field( $post_content_summary );
}

// =========================================================================================== \\

/**
 * Function to save the post content summary for a given post
 *
 * @param int    $post_id the post id.
 * @param string $post_content_summary the content summary.
 * @return bool
 */
function ai4seo_save_post_content_summary_to_postmeta( int $post_id, string $post_content_summary ): bool {
	// sanitize the post content.
	$post_content_summary = sanitize_text_field( $post_content_summary );

	// save the data.
	return ai4seo_update_post_meta( $post_id, AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY, $post_content_summary );
}

// =========================================================================================== \\

/**
 * Returns the configured maximum length for the given editor field identifier.
 *
 * @param string $identifier The metadata or attachment identifier to evaluate.
 * @return int
 */
function ai4seo_get_max_editor_input_length( string $identifier ): int {
	if ( ! defined( 'AI4SEO_MAX_EDITOR_INPUT_LENGTHS' ) ) {
		return 512;
	}

	$identifier   = strtolower( $identifier );
	$max_lengths  = AI4SEO_MAX_EDITOR_INPUT_LENGTHS;
	$fallback_max = (int) ( $max_lengths['fallback'] ?? 512 );

	if ( isset( $max_lengths[ $identifier ] ) ) {
		return (int) $max_lengths[ $identifier ];
	}

	return $fallback_max;
}

// =========================================================================================== \\

/**
 * Normalizes editor input values so they can be validated and trimmed consistently.
 *
 * @param mixed $value The value to normalize.
 * @param bool  $should_unslash The should unslash value.
 * @return string
 */
function ai4seo_normalize_editor_input_value( $value, bool $should_unslash = false ): string {
	if ( is_scalar( $value ) ) {
		$value = (string) $value;
	}

	if ( ! is_string( $value ) ) {
		return '';
	}

	// normalize metadata raw value.
	$value = trim( $value );

	if ( $should_unslash ) {
		$value = wp_unslash( $value );
	}

	return $value;
}

// =========================================================================================== \\

/**
 * Sanitizes editor input values while preserving literal backslashes.
 *
 * @param mixed $value The value to sanitize.
 * @return string
 */
function ai4seo_sanitize_editor_field_value( $value ): string {
	if ( is_scalar( $value ) ) {
		$value = (string) $value;
	}

	if ( ! is_string( $value ) ) {
		return '';
	}

	$value = wp_check_invalid_utf8( $value );
	$value = wp_strip_all_tags( $value );
	$value = preg_replace( '/[\r\n\t ]+/', ' ', $value );

	if ( null === $value ) {
		return '';
	}

	return trim( $value );
}

// =========================================================================================== \\

/**
 * Trims a string to the provided maximum length.
 *
 * @param string $value      The string to trim.
 * @param int    $max_length The maximum length.
 * @return string
 */
function ai4seo_trim_string_to_length( string $value, int $max_length ): string {
	if ( $max_length <= 0 ) {
		return $value;
	}

	return ai4seo_mb_substr( $value, 0, $max_length );
}

// =========================================================================================== \\

/**
 * Join field declarations, immutable storage caps, and soft API quality windows for all save paths.
 *
 * @param string $context Generation context.
 * @return array<string, array<string, mixed>>
 */
function ai4seo_get_generated_output_contract( string $context ): array {
	$context = sanitize_key( $context );

	// Reuse established declarations so API identifiers cannot drift from editor and storage paths.
	if ( 'metadata' === $context ) {
		$field_details = defined( 'AI4SEO_METADATA_DETAILS' ) ? AI4SEO_METADATA_DETAILS : array();
	} elseif ( 'attachment_attributes' === $context ) {
		$field_details = defined( 'AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS' ) ? AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS : array();
	} else {
		return array();
	}

	// Combine effective soft targets with canonical hard caps; only the hard caps can alter a saved value.
	$fixed_quality_windows = AI4SEO_GENERATED_OUTPUT_QUALITY_WINDOWS[ $context ] ?? array();
	$contract              = array();

	// Build one canonical entry per declared field so manual and cron saves share identical mappings.
	foreach ( $field_details as $field_identifier => $this_field_details ) {
		$field_identifier = sanitize_key( $field_identifier );

		if ( ! $field_identifier || ! is_array( $this_field_details ) ) {
			continue;
		}

		$quality_window = ai4seo_get_generation_length_quality_window( $context, $field_identifier );

		if ( ! $quality_window ) {
			$quality_window = $fixed_quality_windows[ $field_identifier ] ?? array();
		}

		$contract[ $field_identifier ] = array(
			'api-identifier'  => sanitize_key( $this_field_details['api-identifier'] ?? '' ),
			'hard-max-length' => ai4seo_get_max_editor_input_length( $field_identifier ),
			'quality-window'  => $quality_window,
		);
	}

	// Filename generation is not currently exposed in the editor, but keep its response rules ready for API compatibility.
	if ( 'attachment_attributes' === $context ) {
		$contract['file-name'] = array(
			'api-identifier'  => 'image_file_name',
			'hard-max-length' => ai4seo_get_max_editor_input_length( 'file-name' ),
			'quality-window'  => $fixed_quality_windows['file-name'] ?? array(),
		);
	}

	return $contract;
}

// =========================================================================================== \\

/**
 * Normalize model separator variation and case-insensitive duplicates before enforcing the shared item cap.
 *
 * @param string $value     Raw generated keywords.
 * @param int    $max_items Maximum number of unique keywords.
 * @return array{value: string, was_limited: bool}
 */
function ai4seo_normalize_generated_output_keywords( string $value, int $max_items ): array {
	// Accept common model separators even though storage uses one canonical comma-space representation.
	$keyword_parts = preg_split( '/[,;\r\n]+/u', $value );

	if ( ! is_array( $keyword_parts ) ) {
		$keyword_parts = array( $value );
	}

	$normalized_keywords = array();
	$keyword_keys        = array();

	// Preserve the first wording and order while removing empty and case-insensitive duplicate items.
	foreach ( $keyword_parts as $this_keyword ) {
		$this_keyword = ai4seo_sanitize_editor_field_value( $this_keyword );

		if ( '' === $this_keyword ) {
			continue;
		}

		$this_keyword_key = function_exists( 'mb_strtolower' )
			? mb_strtolower( $this_keyword, 'UTF-8' )
			: strtolower( $this_keyword );

		if ( isset( $keyword_keys[ $this_keyword_key ] ) ) {
			continue;
		}

		$keyword_keys[ $this_keyword_key ] = true;
		$normalized_keywords[]             = $this_keyword;
	}

	// Apply the item cap after deduplication so repeated keywords do not consume valid slots.
	$was_limited = $max_items > 0 && count( $normalized_keywords ) > $max_items;

	if ( $was_limited ) {
		$normalized_keywords = array_slice( $normalized_keywords, 0, $max_items );
	}

	return array(
		'value'       => implode( ', ', $normalized_keywords ),
		'was_limited' => $was_limited,
	);
}

// =========================================================================================== \\

/**
 * Apply immutable storage caps without mechanically truncating values merely for soft quality-window misses.
 *
 * @param string $value      Generated value.
 * @param int    $max_length Hard character limit.
 * @return string
 */
function ai4seo_truncate_generated_output_at_word_boundary( string $value, int $max_length ): string {
	// No generated content can fit when affixes consume the complete storage allowance.
	if ( $max_length <= 0 ) {
		return '';
	}

	// Leave values untouched when no hard-cap repair is required.
	if ( ai4seo_mb_strlen( $value ) <= $max_length ) {
		return trim( $value );
	}

	// Take a Unicode-safe prefix first, then determine whether the hard cap split a word.
	$truncated_value = rtrim( ai4seo_trim_string_to_length( $value, $max_length ) );
	$next_character  = ai4seo_mb_substr( $value, $max_length, 1 );

	// Keep a complete final word when the next character already starts a natural boundary.
	if ( '' === $next_character || preg_match( '/^[\s\p{P}]$/u', $next_character ) ) {
		return $truncated_value;
	}

	// Backtrack only when the exact cap falls inside a whitespace-delimited word.
	$word_boundary_value = preg_replace( '/[\s\p{P}]+\S*$/u', '', $truncated_value );

	if ( is_string( $word_boundary_value ) && '' !== trim( $word_boundary_value ) ) {
		return rtrim( $word_boundary_value );
	}

	// Scripts without whitespace boundaries still receive a Unicode-safe hard truncation.
	return $truncated_value;
}

// =========================================================================================== \\

/**
 * Filter and normalize generated values before any manual or automated save.
 *
 * Quality-window misses are retained. Only missing, empty, non-string, or technically unusable
 * fields remain unresolved. Prefixes and suffixes are included when calculating hard caps.
 *
 * @param string $context            Generation context.
 * @param mixed  $generated_data     Raw API response data.
 * @param array  $requested_fields   Internal field identifiers requested from the API.
 * @param array  $field_instructions Field instructions containing resolved prefixes and suffixes.
 * @param bool   $apply_affixes       Whether returned values should include their prefix and suffix.
 * @return array{values: array<string, string>, unresolved_fields: string[], quality_misses: string[], hard_capped_fields: string[]}
 */
function ai4seo_prepare_generated_output_fields_for_save(
	string $context,
	$generated_data,
	array $requested_fields,
	array $field_instructions = array(),
	bool $apply_affixes = false
): array {
	// Canonicalize the requested set once so every save path ignores unexpected response keys.
	$contract         = ai4seo_get_generated_output_contract( $context );
	$requested_fields = array_values( array_unique( array_filter( array_map( 'sanitize_key', $requested_fields ) ) ) );
	$result           = array(
		'values'             => array(),
		'unresolved_fields'  => array(),
		'quality_misses'     => array(),
		'hard_capped_fields' => array(),
	);

	// A recoverable non-object response leaves all requested fields unresolved so live values survive.
	if ( ! is_array( $generated_data ) ) {
		$result['unresolved_fields'] = $requested_fields;
		return $result;
	}

	// Validate fields independently so one usable partial value can still be saved.
	foreach ( $requested_fields as $field_identifier ) {
		if ( ! isset( $contract[ $field_identifier ] ) ) {
			$result['unresolved_fields'][] = $field_identifier;
			continue;
		}

		$this_contract       = $contract[ $field_identifier ];
		$this_api_identifier = $this_contract['api-identifier'] ?? '';

		// Prefer canonical plugin keys while accepting API identifiers at this compatibility boundary.
		if ( array_key_exists( $field_identifier, $generated_data ) ) {
			$raw_value = $generated_data[ $field_identifier ];
		} elseif ( $this_api_identifier && array_key_exists( $this_api_identifier, $generated_data ) ) {
			$raw_value = $generated_data[ $this_api_identifier ];
		} else {
			$result['unresolved_fields'][] = $field_identifier;
			continue;
		}

		// Arrays, objects, null, and numeric placeholders are not usable generated text.
		if ( ! is_string( $raw_value ) ) {
			$result['unresolved_fields'][] = $field_identifier;
			continue;
		}

		// Decode entities before Unicode length checks so encoded characters do not inflate measurements.
		$raw_value          = html_entity_decode( $raw_value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$quality_window     = is_array( $this_contract['quality-window'] ?? null ) ? $this_contract['quality-window'] : array();
		$was_quality_missed = false;

		// Apply field-specific technical normalization before deciding whether the value is usable.
		if ( 'keywords' === $field_identifier ) {
			$normalized_keywords = ai4seo_normalize_generated_output_keywords(
				$raw_value,
				absint( $quality_window['max-items'] ?? 10 )
			);
			$value               = $normalized_keywords['value'];
			$was_quality_missed  = $normalized_keywords['was_limited'];
		} elseif ( 'file-name' === $field_identifier ) {
			$value = preg_replace( '/\.(?:avif|gif|jpe?g|png|webp)$/iu', '', trim( $raw_value ) );
			$value = is_string( $value ) ? sanitize_file_name( $value ) : '';
			$value = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
		} else {
			$value = ai4seo_sanitize_editor_field_value( $raw_value );
		}

		// Reject normalization results that contain no storable text before affix budgeting.
		if ( '' === $value ) {
			$result['unresolved_fields'][] = $field_identifier;
			continue;
		}

		// Reserve storage for resolved affixes because hard caps apply after prefixes and suffixes are attached.
		$this_field_instructions = is_array( $field_instructions[ $field_identifier ] ?? null )
			? $field_instructions[ $field_identifier ]
			: array();
		$prefix                  = ai4seo_sanitize_editor_field_value( $this_field_instructions['prefix'] ?? '' );
		$suffix                  = ai4seo_sanitize_editor_field_value( $this_field_instructions['suffix'] ?? '' );
		$hard_max_length         = absint( $this_contract['hard-max-length'] ?? 0 );

		// Enforce the immutable storage limit only after accounting for the final affixed form.
		if ( $hard_max_length > 0 ) {
			$affix_length = ai4seo_mb_strlen( $prefix ) + ai4seo_mb_strlen( $suffix );

			if ( '' !== $prefix ) {
				++$affix_length;
			}

			if ( '' !== $suffix ) {
				++$affix_length;
			}

			$generated_value_max_length = $hard_max_length - $affix_length;

			// Preserve the live field instead of saving affix-only content when no generated text can fit.
			if ( $generated_value_max_length <= 0 ) {
				$result['unresolved_fields'][] = $field_identifier;
				continue;
			}

			// Remove complete trailing keywords before general truncation to avoid storing a partial keyword.
			if ( 'keywords' === $field_identifier && ai4seo_mb_strlen( $value ) > $generated_value_max_length ) {
				$normalized_keyword_items       = explode( ', ', $value );
				$normalized_keyword_items_count = count( $normalized_keyword_items );

				while ( $normalized_keyword_items_count > 1 && ai4seo_mb_strlen( implode( ', ', $normalized_keyword_items ) ) > $generated_value_max_length ) {
					array_pop( $normalized_keyword_items );
					--$normalized_keyword_items_count;
				}

				$value = implode( ', ', $normalized_keyword_items );
			}

			// Use word-boundary truncation only for the immutable hard cap, never for a soft target miss.
			if ( ai4seo_mb_strlen( $value ) > $generated_value_max_length ) {
				$value = ai4seo_truncate_generated_output_at_word_boundary( $value, $generated_value_max_length );

				$result['hard_capped_fields'][] = $field_identifier;
			}
		}

		// Treat values emptied by normalization or capping as unresolved instead of overwriting stored data.
		if ( '' === $value ) {
			$result['unresolved_fields'][] = $field_identifier;
			continue;
		}

		// Measure the generated portion against soft targets for diagnostics without rejecting it.
		$value_length = ai4seo_mb_strlen( $value );
		$min_length   = absint( $quality_window['min-length'] ?? 0 );
		$max_length   = absint( $quality_window['max-length'] ?? 0 );

		if ( ( $min_length > 0 && $value_length < $min_length ) || ( $max_length > 0 && $value_length > $max_length ) ) {
			$was_quality_missed = true;
		}

		if ( $was_quality_missed ) {
			$result['quality_misses'][] = $field_identifier;
		}

		// Attach affixes only for callers saving final content; provenance callers retain the generated portion.
		$result['values'][ $field_identifier ] = $apply_affixes
			? trim( $prefix . ' ' . $value . ' ' . $suffix )
			: $value;
	}

	// Keep accepted target misses visible through the established support diagnostics channel.
	if ( $result['quality_misses'] ) {
		ai4seo_debug_message( 753239417, 'Generated output saved outside its quality window: ' . implode( ', ', $result['quality_misses'] ) );
	}

	return $result;
}

// =========================================================================================== \\

/**
 * Updates active metadata and synchronizes configured third-party SEO integrations.
 *
 * @param int        $post_id                 Post ID.
 * @param array      $metadata_updates        Metadata updates.
 * @param bool       $overwrite_existing_data Whether existing data should be overwritten.
 * @param array|null $operation_details       Optional detailed persistence result populated by reference.
 * @return bool True on complete success, false on SOOZ or third-party persistence failure.
 */
function ai4seo_update_active_metadata(
	int $post_id,
	array $metadata_updates,
	bool $overwrite_existing_data = false,
	?array &$operation_details = null
): bool {
	// Initialize details before guard clauses so callers always receive the complete result shape.
	$operation_details = array(
		'overall_succeeded'          => false,
		'active_metadata_succeeded'  => false,
		'third_party_sync_succeeded' => true,
		'failed_third_party_syncs'   => array(),
	);

	if ( ! defined( 'AI4SEO_METADATA_DETAILS' ) ) {
		return false;
	}

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 239030919, 'Prevented loop', true );
		return false;
	}

	// Apply the same editor normalization contract to manual and generated metadata values.
	$metadata_updates = ai4seo_deep_sanitize( $metadata_updates, 'ai4seo_sanitize_editor_field_value' );

	// Non-forced saves use the field allowlist to decide which existing values may be replaced.
	$metadata_identifiers_to_overwrite = array();

	if ( ! $overwrite_existing_data ) {
		$metadata_identifiers_to_overwrite = ai4seo_get_setting( AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA );

		if ( ! is_array( $metadata_identifiers_to_overwrite ) ) {
			$metadata_identifiers_to_overwrite = array();
		}
	}

	// Track integration and SOOZ outcomes independently until both persistence paths have completed.
	$third_party_sync_succeeded               = true;
	$third_party_sync_reached_requested_state = false;
	$current_active_metadata                  = ai4seo_read_active_metadata_from_post_meta( $post_id, false );
	$did_merge_legacy_active_metadata         = false;
	$active_metadata_updates_to_save          = array();

	// Until migration finishes, merge missing legacy values into the same atomic SOOZ JSON write.
	if ( ! ai4seo_is_active_metadata_migration_v235_completed() ) {
		$legacy_active_metadata_by_post_ids = ai4seo_read_legacy_active_metadata_by_post_ids( array( $post_id ) );
		$legacy_active_metadata             = $legacy_active_metadata_by_post_ids[ $post_id ] ?? array();

		foreach ( $legacy_active_metadata as $this_metadata_identifier => $this_metadata_value ) {
			if ( array_key_exists( $this_metadata_identifier, $current_active_metadata ) ) {
				continue;
			}

			$current_active_metadata[ $this_metadata_identifier ]         = $this_metadata_value;
			$active_metadata_updates_to_save[ $this_metadata_identifier ] = $this_metadata_value;
			$did_merge_legacy_active_metadata                             = true;
		}
	}

	$should_save_own_metadata = $did_merge_legacy_active_metadata;

	// Process only registered metadata so API aliases, field limits, and integration mappings remain centralized.
	foreach ( AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_details ) {
		$this_api_identifier = $this_metadata_details['api-identifier'];

		if ( isset( $metadata_updates[ $this_metadata_identifier ] ) ) {
			$this_new_metadata_value = $metadata_updates[ $this_metadata_identifier ];
		} elseif ( isset( $metadata_updates[ $this_api_identifier ] ) ) {
			// Preserve API-identifier compatibility for metadata generated by historical plugin versions.
			$this_new_metadata_value = $metadata_updates[ $this_api_identifier ];
		} else {
			continue;
		}

		// Normalize and cap values before either persistence path receives them.
		$this_new_metadata_value = ai4seo_normalize_editor_input_value( $this_new_metadata_value );
		$this_max_length         = ai4seo_get_max_editor_input_length( $this_metadata_identifier );
		$this_new_metadata_value = ai4seo_trim_string_to_length( $this_new_metadata_value, $this_max_length );

		// Resolve field-level overwrite intent once and share it with SOOZ and every integration.
		if ( true === $overwrite_existing_data ) {
			$overwrite_this_metadata_field = true;
		} else {
			$overwrite_this_metadata_field = in_array( $this_metadata_identifier, $metadata_identifiers_to_overwrite, true );
		}

		// Synchronize integrations first because an intentional non-overwrite skip controls SOOZ precedence.
		$this_third_party_sync_result = ai4seo_update_third_party_seo_plugins_metadata(
			$post_id,
			$this_metadata_identifier,
			$this_new_metadata_value,
			$overwrite_this_metadata_field
		);

		// Remember any successful integration write so the frontend cache is purged once after the loop.
		if ( $this_third_party_sync_result['sync_reached_requested_state'] ) {
			$third_party_sync_reached_requested_state = true;
		}

		// Aggregate plugin and field failures for retryable caller-facing diagnostics.
		if ( ! $this_third_party_sync_result['sync_succeeded'] ) {
			$third_party_sync_succeeded = false;

			foreach ( $this_third_party_sync_result['failed_plugin_identifiers'] as $failed_plugin_identifier ) {
				if ( ! isset( $operation_details['failed_third_party_syncs'][ $failed_plugin_identifier ] ) ) {
					$operation_details['failed_third_party_syncs'][ $failed_plugin_identifier ] = array();
				}

				$operation_details['failed_third_party_syncs'][ $failed_plugin_identifier ][] = $this_metadata_identifier;
			}
		}

		// Preserve existing third-party precedence when non-overwrite mode encountered populated data.
		if ( $this_third_party_sync_result['skip_own_metadata'] ) {
			continue;
		}

		// Queue the SOOZ value only when overwrite rules permit replacing its current value.
		if ( $overwrite_this_metadata_field ) {
			$current_active_metadata[ $this_metadata_identifier ]         = $this_new_metadata_value;
			$active_metadata_updates_to_save[ $this_metadata_identifier ] = $this_new_metadata_value;
			$should_save_own_metadata                                     = true;
		} else {
			$this_current_metadata_value = $current_active_metadata[ $this_metadata_identifier ] ?? '';

			if ( $this_current_metadata_value ) {
				continue;
			}

			$current_active_metadata[ $this_metadata_identifier ]         = $this_new_metadata_value;
			$active_metadata_updates_to_save[ $this_metadata_identifier ] = $this_new_metadata_value;
			$should_save_own_metadata                                     = true;
		}
	}

	// A no-op is successful; actual writes replace these defaults with their observed outcomes.
	$active_metadata_succeeded                 = true;
	$legacy_active_metadata_cleanup_succeeded = true;

	if ( $should_save_own_metadata ) {
		$active_metadata_succeeded = ai4seo_save_active_metadata_to_postmeta( $post_id, $active_metadata_updates_to_save );

		if ( $active_metadata_succeeded ) {
			$legacy_active_metadata_cleanup_succeeded = ai4seo_delete_legacy_active_metadata_for_post_ids( array( $post_id ) );
		}
	}

	// Expose persistence separately from integration synchronization while retaining the legacy Boolean return.
	$operation_details['active_metadata_succeeded']  = $active_metadata_succeeded;
	$operation_details['third_party_sync_succeeded'] = $third_party_sync_succeeded;

	foreach ( $operation_details['failed_third_party_syncs'] as $failed_plugin_identifier => $failed_metadata_identifiers ) {
		$operation_details['failed_third_party_syncs'][ $failed_plugin_identifier ] = array_values( array_unique( $failed_metadata_identifiers ) );
	}

	$operation_details['overall_succeeded'] = $active_metadata_succeeded && $legacy_active_metadata_cleanup_succeeded && $third_party_sync_succeeded;

	// Purge once when SOOZ persisted or any third-party target reached the requested state.
	if ( ( $should_save_own_metadata && $active_metadata_succeeded ) || $third_party_sync_reached_requested_state ) {
		// Cache integrations are optional, so their exceptions must not change persistence results.
		try {
			ai4seo_purge_frontend_cache_for_post( $post_id );
		} catch ( Exception $e ) {
			// Continue with the already-determined persistence result.
		}
	}

	return $operation_details['overall_succeeded'];
}

// =========================================================================================== \\

/**
 * Builds the shared result contract used by every third-party metadata writer.
 *
 * @param bool $write_attempted                Whether a persistence operation was attempted.
 * @param bool $write_succeeded                Whether every requested persistence state was reached.
 * @param bool $skipped_existing               Whether an existing value intentionally prevented a write.
 * @param bool $write_reached_requested_state  Whether at least one attempted persistence target reached its state.
 * @return array{write_attempted: bool, write_succeeded: bool, skipped_existing: bool, write_reached_requested_state: bool}
 */
function ai4seo_build_third_party_seo_plugin_metadata_write_result(
	bool $write_attempted = false,
	bool $write_succeeded = false,
	bool $skipped_existing = false,
	bool $write_reached_requested_state = false
): array {
	// Keeping construction centralized prevents integration-specific result shapes from drifting apart.
	return array(
		'write_attempted'               => $write_attempted,
		'write_succeeded'               => $write_succeeded,
		'skipped_existing'              => $skipped_existing,
		'write_reached_requested_state' => $write_reached_requested_state,
	);
}

// =========================================================================================== \\

/**
 * Updates one selected third-party SEO plugin and reports the write outcome.
 *
 * @param int    $post_id                           Post ID.
 * @param string $third_party_seo_plugin_identifier Plugin identifier.
 * @param array  $third_party_seo_plugin_details    Plugin registry details.
 * @param string $metadata_identifier               Metadata identifier.
 * @param string $metadata_value                    Metadata value.
 * @param bool   $overwrite_existing_data           Whether existing data should be overwritten.
 * @return array{write_attempted: bool, write_succeeded: bool, skipped_existing: bool, write_reached_requested_state: bool}
 */
function ai4seo_update_one_third_party_seo_plugin_metadata(
	int $post_id,
	string $third_party_seo_plugin_identifier,
	array $third_party_seo_plugin_details,
	string $metadata_identifier,
	string $metadata_value,
	bool $overwrite_existing_data
): array {
	// Unsupported mappings start as non-attempted failures and are upgraded only by an explicit skip or write.
	$write_result = ai4seo_build_third_party_seo_plugin_metadata_write_result();
	$only_if_empty = ! $overwrite_existing_data;

	// Route integrations with compound storage through their dedicated preservation logic.
	if ( AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO === $third_party_seo_plugin_identifier ) {
		$supports_inbound_postmeta_sync = ai4seo_does_third_party_seo_plugin_support_inbound_postmeta_sync(
			$third_party_seo_plugin_details
		);

		if ( $supports_inbound_postmeta_sync ) {
			ai4seo_manage_third_party_seo_metadata_sync_request_state( 'begin-outbound', $post_id );
		}

		try {
			return ai4seo_update_active_metadata_for_slim_seo( $post_id, $metadata_identifier, $metadata_value, $only_if_empty );
		} finally {
			if ( $supports_inbound_postmeta_sync ) {
				ai4seo_manage_third_party_seo_metadata_sync_request_state( 'end-outbound', $post_id );
			}
		}
	}

	if ( AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO === $third_party_seo_plugin_identifier ) {
		return ai4seo_update_active_metadata_for_squirrly_seo( $post_id, $metadata_identifier, $metadata_value, $only_if_empty );
	}

	// AIOSEO owns its canonical row, so do not alter either of its storage locations before it exists.
	if ( AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO === $third_party_seo_plugin_identifier
		&& ! ai4seo_does_all_in_one_seo_post_row_exist( $post_id ) ) {
		return $write_result;
	}

	// Ordinary integrations store each field under the registry-provided postmeta key.
	$third_party_postmeta_key = sanitize_text_field( $third_party_seo_plugin_details['generation-field-postmeta-keys'][ $metadata_identifier ] ?? '' );

	if ( '' === $third_party_postmeta_key ) {
		return $write_result;
	}

	// Existing non-empty integration data intentionally prevents both its overwrite and the corresponding SOOZ write.
	if ( $only_if_empty && get_post_meta( $post_id, $third_party_postmeta_key, true ) ) {
		$postmeta_write_result = ai4seo_build_third_party_seo_plugin_metadata_write_result(
			false,
			true,
			true
		);
	} else {
		// Capability-driven suppression keeps outbound writes from re-entering the generic inbound mirror.
		$postmeta_write_result = ai4seo_build_third_party_seo_plugin_metadata_write_result( true );
		$supports_inbound_postmeta_sync = ai4seo_does_third_party_seo_plugin_support_inbound_postmeta_sync(
			$third_party_seo_plugin_details
		);

		// The inbound hook must not interpret a SOOZ-originated write as a separate editor change.
		if ( $supports_inbound_postmeta_sync ) {
			ai4seo_manage_third_party_seo_metadata_sync_request_state( 'begin-outbound', $post_id );
		}

		// The finally block guarantees request-local suppression cannot leak after any write outcome.
		try {
			$postmeta_write_result['write_succeeded'] = ai4seo_update_post_meta( $post_id, $third_party_postmeta_key, $metadata_value );
			$postmeta_write_result['write_reached_requested_state'] = $postmeta_write_result['write_succeeded'];
		} finally {
			// Pair suppression with every attempted write, including writes that throw before returning a result.
			if ( $supports_inbound_postmeta_sync ) {
				ai4seo_manage_third_party_seo_metadata_sync_request_state( 'end-outbound', $post_id );
			}
		}
	}

	// All ordinary integrations are complete after their postmeta write.
	if ( AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO !== $third_party_seo_plugin_identifier ) {
		return $postmeta_write_result;
	}

	// AIOSEO maintains both a postmeta mirror and its own table; both requested states must succeed.
	$aioseo_table_write_result = ai4seo_update_active_metadata_for_all_in_one_seo(
		$post_id,
		$metadata_identifier,
		$metadata_value,
		$only_if_empty
	);

	// Preserve AIOSEO's dual-storage contract while retaining SOOZ precedence based on its postmeta mirror.
	$aioseo_write_attempted = $postmeta_write_result['write_attempted'] || $aioseo_table_write_result['write_attempted'];
	$aioseo_write_succeeded = $postmeta_write_result['write_succeeded'] && $aioseo_table_write_result['write_succeeded'];
	$aioseo_write_reached_requested_state = $postmeta_write_result['write_reached_requested_state']
		|| $aioseo_table_write_result['write_reached_requested_state'];

	return ai4seo_build_third_party_seo_plugin_metadata_write_result(
		$aioseo_write_attempted,
		$aioseo_write_succeeded,
		$postmeta_write_result['skipped_existing'],
		$aioseo_write_reached_requested_state
	);
}

// =========================================================================================== \\

/**
 * Updates configured third-party SEO plugins and reports synchronization details.
 *
 * @param int    $post_id                 Post ID.
 * @param string $metadata_identifier     Metadata identifier.
 * @param string $metadata_value          Metadata value.
 * @param bool   $overwrite_existing_data Whether existing data should be overwritten.
 * @return array{skip_own_metadata: bool, sync_attempted: bool, sync_succeeded: bool, sync_reached_requested_state: bool, failed_plugin_identifiers: array}
 */
function ai4seo_update_third_party_seo_plugins_metadata(
	int $post_id,
	string $metadata_identifier,
	string $metadata_value,
	bool $overwrite_existing_data
): array {
	// Default to a successful no-op so inactive, deselected, and unsupported integrations remain neutral.
	$sync_result = array(
		'skip_own_metadata'            => false,
		'sync_attempted'               => false,
		'sync_succeeded'               => true,
		'sync_reached_requested_state' => false,
		'failed_plugin_identifiers'    => array(),
	);

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 378117210, 'Prevented loop', true );
		$sync_result['sync_succeeded'] = false;
		return $sync_result;
	}

	// The field allowlist is evaluated before plugin discovery to avoid unnecessary activation checks.
	$metadata_identifiers_to_sync = ai4seo_get_setting( AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA );

	if ( ! $metadata_identifiers_to_sync
		|| ! is_array( $metadata_identifiers_to_sync )
		|| ! in_array( $metadata_identifier, $metadata_identifiers_to_sync, true ) ) {
		return $sync_result;
	}

	// Only active supported plugins can represent an outbound synchronization target.
	$active_supported_third_party_seo_plugins = ai4seo_get_active_third_party_seo_plugin_details();

	if ( ! $active_supported_third_party_seo_plugins ) {
		return $sync_result;
	}

	// Intersect active integrations with the user's plugin-level synchronization allowlist.
	$plugin_identifiers_to_sync = ai4seo_get_setting( AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS );

	if ( ! $plugin_identifiers_to_sync || ! is_array( $plugin_identifiers_to_sync ) ) {
		return $sync_result;
	}

	foreach ( $active_supported_third_party_seo_plugins as $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details ) {
		// Ignore active plugins that were not selected for outbound synchronization.
		if ( ! in_array( $this_third_party_seo_plugin_identifier, $plugin_identifiers_to_sync, true ) ) {
			continue;
		}

		// A plugin without a mapping for this field is unsupported rather than failed.
		if ( ! isset( $this_third_party_seo_plugin_details['generation-field-postmeta-keys'][ $metadata_identifier ] ) ) {
			continue;
		}

		// Isolate each plugin outcome so later configured integrations still run after an ordinary write failure.
		$this_plugin_write_result = ai4seo_update_one_third_party_seo_plugin_metadata(
			$post_id,
			$this_third_party_seo_plugin_identifier,
			$this_third_party_seo_plugin_details,
			$metadata_identifier,
			$metadata_value,
			$overwrite_existing_data
		);

		// Record actual persistence attempts independently from intentional existing-value skips.
		if ( $this_plugin_write_result['write_attempted'] ) {
			$sync_result['sync_attempted'] = true;
		}

		// Any populated integration retains the established non-overwrite precedence over SOOZ.
		if ( $this_plugin_write_result['skipped_existing'] ) {
			$sync_result['skip_own_metadata'] = true;
		}

		// Any reached persistence target triggers one deferred frontend cache purge after all fields finish.
		if ( $this_plugin_write_result['write_reached_requested_state'] ) {
			$sync_result['sync_reached_requested_state'] = true;
		}

		// Aggregate failures without short-circuiting later configured integrations.
		if ( ! $this_plugin_write_result['write_succeeded'] ) {
			$sync_result['sync_succeeded']              = false;
			$sync_result['failed_plugin_identifiers'][] = sanitize_key( $this_third_party_seo_plugin_identifier );
		}
	}

	$sync_result['failed_plugin_identifiers'] = array_values( array_unique( array_filter( $sync_result['failed_plugin_identifiers'] ) ) );

	return $sync_result;
}

// =========================================================================================== \\

/**
 * Updates one Squirrly SEO metadata field while preserving its serialized row structure.
 *
 * @param int    $post_id             Post ID.
 * @param string $metadata_identifier Metadata identifier.
 * @param string $metadata_value      Metadata value.
 * @param bool   $only_if_empty       Whether existing non-empty metadata should be preserved.
 * @return array{write_attempted: bool, write_succeeded: bool, skipped_existing: bool, write_reached_requested_state: bool}
 */
function ai4seo_update_active_metadata_for_squirrly_seo(
	int $post_id,
	string $metadata_identifier,
	string $metadata_value,
	bool $only_if_empty = false
): array {
	// Missing mappings or storage rows must retain an explicit non-attempted failure result.
	$write_result = ai4seo_build_third_party_seo_plugin_metadata_write_result();

	// Squirrly stores all supported fields together in the serialized qss.seo column.
	$metadata_identifier_mapping = ai4seo_get_squirrly_seo_metadata_identifier_mapping();

	$this_squirrly_seo_key = $metadata_identifier_mapping[ $metadata_identifier ] ?? '';

	if ( ! $this_squirrly_seo_key ) {
		return $write_result;
	}

	// Load the complete serialized row so unrelated Squirrly fields survive the update.
	global $wpdb;

	// The post ID lives inside Squirrly's serialized post column rather than a dedicated SQL column.
	$squirrly_post_pattern = '%s:2:"ID";i:' . esc_sql( $post_id ) . ';%';

	$current_squirrly_values = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT seo FROM {$wpdb->prefix}qss WHERE post LIKE %s",
			$squirrly_post_pattern
		)
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321683, 'Database error: ' . $wpdb->last_error, true );
		return $write_result;
	}
	$current_squirrly_values = maybe_unserialize( $current_squirrly_values );

	// Squirrly data can contain a second serialization layer depending on the plugin version.
	if ( $current_squirrly_values && is_string( $current_squirrly_values ) ) {
		$current_squirrly_values = unserialize( $current_squirrly_values );
	}

	// Normalize every missing or malformed row to the same empty collection outcome.
	if ( ! $current_squirrly_values || ! is_array( $current_squirrly_values ) ) {
		$current_squirrly_values = array();
	}

	// A missing or unreadable Squirrly row cannot be synchronized safely.
	if ( empty( $current_squirrly_values ) ) {
		return $write_result;
	}

	// Non-overwrite mode treats an existing integration value as an intentional successful skip.
	if ( $only_if_empty ) {
		if ( isset( $current_squirrly_values[ $this_squirrly_seo_key ] ) && $current_squirrly_values[ $this_squirrly_seo_key ] ) {
			$write_result['write_succeeded']  = true;
			$write_result['skipped_existing'] = true;
			return $write_result;
		}
	}

	// Replace only the requested key before writing the complete serialized collection back.
	$requested_metadata_value                            = sanitize_text_field( $metadata_value );
	$current_squirrly_values[ $this_squirrly_seo_key ]  = $requested_metadata_value;
	$write_result['write_attempted']                     = true;

	$query_result = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->prefix}qss SET seo = %s WHERE post LIKE %s",
			maybe_serialize( $current_squirrly_values ),
			$squirrly_post_pattern
		)
	);

	if ( false === $query_result || $wpdb->last_error ) {
		ai4seo_debug_message( 984321684, 'Database error: ' . $wpdb->last_error, true );
		return $write_result;
	}

	// A positive affected-row count proves the requested serialized collection was written.
	if ( $query_result > 0 ) {
		$write_result['write_succeeded']               = true;
		$write_result['write_reached_requested_state'] = true;
		return $write_result;
	}

	// Read back the affected field because a zero-row SQL result may still be an idempotent success.
	$latest_squirrly_values = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT seo FROM {$wpdb->prefix}qss WHERE post LIKE %s",
			$squirrly_post_pattern
		)
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321688, 'Database error: ' . $wpdb->last_error, true );
		return $write_result;
	}

	$latest_squirrly_values = maybe_unserialize( $latest_squirrly_values );

	if ( is_string( $latest_squirrly_values ) ) {
		$latest_squirrly_values = maybe_unserialize( $latest_squirrly_values );
	}

	$write_result['write_succeeded'] = is_array( $latest_squirrly_values )
		&& array_key_exists( $this_squirrly_seo_key, $latest_squirrly_values )
		&& $requested_metadata_value === (string) $latest_squirrly_values[ $this_squirrly_seo_key ];
	$write_result['write_reached_requested_state'] = $write_result['write_succeeded'];

	return $write_result;
}

// =========================================================================================== \\

/**
 * Updates one Slim SEO metadata field while preserving its shared postmeta structure.
 *
 * @param int    $post_id             Post ID.
 * @param string $metadata_identifier Metadata identifier.
 * @param string $metadata_value      Metadata value.
 * @param bool   $only_if_empty       Whether existing non-empty metadata should be preserved.
 * @return array{write_attempted: bool, write_succeeded: bool, skipped_existing: bool, write_reached_requested_state: bool}
 */
function ai4seo_update_active_metadata_for_slim_seo(
	int $post_id,
	string $metadata_identifier,
	string $metadata_value,
	bool $only_if_empty = false
): array {
	// Missing mappings retain an explicit non-attempted failure result.
	$write_result = ai4seo_build_third_party_seo_plugin_metadata_write_result();

	// Reuse the integration registry so inbound hooks and outbound writes share one field mapping.
	$third_party_seo_plugins = ai4seo_get_third_party_seo_plugin_details();
	$compound_sync_details   = ai4seo_get_third_party_seo_plugin_compound_postmeta_sync_details(
		$third_party_seo_plugins[ AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO ] ?? array()
	);
	$slim_seo_postmeta_key   = $compound_sync_details['postmeta_key'] ?? '';
	$metadata_array_keys     = $compound_sync_details['generation_field_array_keys'] ?? array();
	$this_slim_seo_key       = $metadata_array_keys[ $metadata_identifier ] ?? '';

	if ( ! $slim_seo_postmeta_key || ! $this_slim_seo_key ) {
		return $write_result;
	}

	// Load the shared value so updating one field cannot discard its sibling.
	$current_slim_seo_values = get_post_meta( $post_id, $slim_seo_postmeta_key, true );
	$current_slim_seo_values = maybe_unserialize( $current_slim_seo_values );

	// A missing or malformed value is equivalent to an empty Slim SEO collection.
	if ( ! is_array( $current_slim_seo_values ) || ! $current_slim_seo_values ) {
		$current_slim_seo_values = array();
	}

	// Non-overwrite mode treats an existing integration value as an intentional successful skip.
	if ( $only_if_empty ) {
		if ( isset( $current_slim_seo_values[ $this_slim_seo_key ] ) && $current_slim_seo_values[ $this_slim_seo_key ] ) {
			$write_result['write_succeeded']  = true;
			$write_result['skipped_existing'] = true;
			return $write_result;
		}
	}

	// Replace only the requested key and persist the complete collection through the shared wrapper.
	$current_slim_seo_values[ $this_slim_seo_key ] = sanitize_text_field( $metadata_value );
	$write_result['write_attempted']                = true;
	$write_result['write_succeeded']                = ai4seo_update_post_meta( $post_id, $slim_seo_postmeta_key, $current_slim_seo_values );
	$write_result['write_reached_requested_state']  = $write_result['write_succeeded'];

	return $write_result;
}

// =========================================================================================== \\

/**
 * Checks whether AIOSEO has initialized canonical storage for a post.
 *
 * @param int $post_id Post ID.
 * @return bool True when AIOSEO owns a row for the post.
 */
function ai4seo_does_all_in_one_seo_post_row_exist( int $post_id ): bool {
	global $wpdb;
	static $row_exists_by_post_id = array();

	if ( array_key_exists( $post_id, $row_exists_by_post_id ) ) {
		return $row_exists_by_post_id[ $post_id ];
	}

	// Read only the canonical identifier because field values are irrelevant to the preflight decision.
	$aioseo_post_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->prefix}aioseo_posts WHERE post_id = %d LIMIT 1",
			$post_id
		)
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 874321686, 'Database error: ' . $wpdb->last_error, true );
		return false;
	}

	$row_exists_by_post_id[ $post_id ] = null !== $aioseo_post_id;

	return $row_exists_by_post_id[ $post_id ];
}

// =========================================================================================== \\

/**
 * Updates one AIOSEO metadata field in its own table.
 *
 * @param int    $post_id             Post ID.
 * @param string $metadata_identifier Metadata identifier.
 * @param string $metadata_value      Metadata value.
 * @param bool   $only_if_empty       Whether existing non-empty metadata should be preserved.
 * @return array{write_attempted: bool, write_succeeded: bool, skipped_existing: bool, write_reached_requested_state: bool}
 */
function ai4seo_update_active_metadata_for_all_in_one_seo(
	int $post_id,
	string $metadata_identifier,
	string $metadata_value,
	bool $only_if_empty = false
): array {
	// Missing mappings retain an explicit non-attempted failure result.
	$write_result = ai4seo_build_third_party_seo_plugin_metadata_write_result();

	// Reuse the read-path allowlist so only supported AIOSEO columns can reach the dynamic query.
	$metadata_identifier_mapping = ai4seo_get_all_in_one_seo_metadata_identifier_mapping();

	$this_aioseo_column_name = $metadata_identifier_mapping[ $metadata_identifier ] ?? '';

	if ( ! $this_aioseo_column_name ) {
		return $write_result;
	}

	global $wpdb;

	if ( $only_if_empty ) {
		// The preservation path needs the current value and simultaneously confirms row ownership.
		$current_aioseo_row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT ' . esc_sql( $this_aioseo_column_name ) . " AS metadata_value FROM {$wpdb->prefix}aioseo_posts WHERE post_id = %d",
				$post_id
			)
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321686, 'Database error: ' . $wpdb->last_error, true );
			return $write_result;
		}

		// AIOSEO owns row creation; an absent row intentionally becomes a silent synchronization failure.
		if ( ! is_object( $current_aioseo_row ) || ! property_exists( $current_aioseo_row, 'metadata_value' ) ) {
			return $write_result;
		}

		if ( $current_aioseo_row->metadata_value ) {
			$write_result['write_succeeded']  = true;
			$write_result['skipped_existing'] = true;
			return $write_result;
		}
	} elseif ( ! ai4seo_does_all_in_one_seo_post_row_exist( $post_id ) ) {
		return $write_result;
	}

	// Sanitize and write only the allowlisted column selected by the fixed identifier mapping above.
	$requested_metadata_value        = sanitize_text_field( $metadata_value );
	$write_result['write_attempted'] = true;
	$query_result                    = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->prefix}aioseo_posts SET " . esc_sql( $this_aioseo_column_name ) . ' = %s WHERE post_id = %d',
			$requested_metadata_value,
			$post_id
		)
	);

	if ( false === $query_result || $wpdb->last_error ) {
		ai4seo_debug_message( 984321687, 'Database error: ' . $wpdb->last_error, true );
		return $write_result;
	}

	// A positive affected-row count proves the requested column value was written.
	if ( $query_result > 0 ) {
		$write_result['write_succeeded']               = true;
		$write_result['write_reached_requested_state'] = true;
		return $write_result;
	}

	// A zero-row result requires readback to distinguish idempotence from an absent row.
	$latest_metadata_row = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT ' . esc_sql( $this_aioseo_column_name ) . " AS metadata_value FROM {$wpdb->prefix}aioseo_posts WHERE post_id = %d",
			$post_id
		)
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321689, 'Database error: ' . $wpdb->last_error, true );
		return $write_result;
	}

	$write_result['write_succeeded'] = is_object( $latest_metadata_row )
		&& property_exists( $latest_metadata_row, 'metadata_value' )
		&& $requested_metadata_value === (string) $latest_metadata_row->metadata_value;
	$write_result['write_reached_requested_state'] = $write_result['write_succeeded'];

	return $write_result;
}


// =========================================================================================== \\

/**
 * Returns the language of a post / page / product
 *
 * @param int $post_id the post id
 * @return string the language of the post
 */
function ai4seo_get_posts_language( int $post_id ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 482665630, 'Prevented loop', true );
		return '';
	}

	$metadata_generation_language = sanitize_text_field( ai4seo_get_setting( AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE ) );

	do {
		if ( 'auto' === $metadata_generation_language ) {
			// try to get post language using multilanguage plugin.
			$multilanguage_plugin_language = sanitize_text_field( ai4seo_try_get_post_language_by_checking_multilanguage_plugins( $post_id ) );

			if ( $multilanguage_plugin_language ) {
				$metadata_generation_language = $multilanguage_plugin_language;
				break;
			}

			// we stay at "auto" if we could not find a language -> let the AI detect the language.
		}
	} while ( false );

	return $metadata_generation_language;
}

// =========================================================================================== \\

/**
 * Retrieves the active meta tags
 *
 * @return array The active meta tags
 */
function ai4seo_get_active_meta_tags(): array {
	if ( ai4seo_prevent_loops( __FUNCTION__, 1, 99999 ) ) {
		ai4seo_debug_message( 798608158, 'Prevented loop', true );
		return array();
	}

	$active_meta_tags = ai4seo_get_setting( AI4SEO_SETTING_ACTIVE_META_TAGS );

	if ( ! is_array( $active_meta_tags ) ) {
		return array();
	}

	return $active_meta_tags;
}

// =========================================================================================== \\

function ai4seo_get_active_meta_tags_names( $active_meta_tags = null ): array {
	if ( null === $active_meta_tags ) {
		$active_meta_tags = ai4seo_get_active_meta_tags();
	}

	$active_meta_tags_names = array();

	foreach ( AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details ) {
		if ( in_array( $ai4seo_this_metadata_identifier, $active_meta_tags ) && isset( $ai4seo_this_metadata_details['name'] ) ) {
			$active_meta_tags_names[] = $ai4seo_this_metadata_details['name'];
		}
	}

	return $active_meta_tags_names;
}


// endregion
// ___________________________________________________________________________________________.
