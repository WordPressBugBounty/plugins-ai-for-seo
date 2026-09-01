<?php
/**
 * Handles metadata generation, validation, storage, and synchronization.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region META DATA ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Resolve metadata affixes exactly as frontend output presents them for one post.
 *
 * @param int   $post_id Current post ID.
 * @param array $affixes Stored prefix or suffix map.
 *
 * @return array<string, string> Resolved affixes keyed by metadata identifier.
 */
function ai4seo_get_metadata_editor_preview_affixes( int $post_id, array $affixes ): array {
	$resolved_affixes         = array();
	$post_type                = get_post_type( $post_id );
	$placeholder_replacements = ai4seo_get_metadata_output_placeholder_replacements( $post_id );
	$post_title               = $placeholder_replacements['TITLE'] ?? '';

	foreach ( AI4SEO_AVAILABLE_METADATA_IDENTIFIERS as $metadata_identifier ) {
		$metadata_identifier = sanitize_key( $metadata_identifier );
		$raw_affix           = $affixes[ $metadata_identifier ] ?? '';
		$raw_affix           = is_scalar( $raw_affix ) ? (string) $raw_affix : '';
		$resolved_affix      = trim( sanitize_text_field( $raw_affix ) );

		// Product placeholders are omitted outside products, matching frontend metadata output.
		if ( 'product' !== $post_type && ai4seo_text_contains_product_placeholder( $raw_affix ) ) {
			$resolved_affix = '';
		} else {
			$resolved_affix = ai4seo_replace_text_placeholders( $resolved_affix, $placeholder_replacements );
		}

		$resolved_affixes[ $metadata_identifier ] = trim(
			ai4seo_replace_metadata_title_placeholder( $resolved_affix, $post_title )
		);
	}

	return $resolved_affixes;
}


/**
 * Build constant post and site data used by the client-side metadata previews.
 *
 * The live field values remain in their existing form controls; this context supplies only
 * fallbacks and presentation data that cannot be derived safely in the browser.
 *
 * @param int   $post_id         Post ID.
 * @param array $metadata_values Current editor metadata values.
 * @param array $active_fields   Active metadata identifiers.
 * @return array<string, mixed> Preview context.
 */
function ai4seo_get_metadata_editor_preview_context( int $post_id, array $metadata_values, array $active_fields ): array {
	$preview_values       = array();
	$fallback_preferences = array();
	$quality_windows      = array();
	$prefixes             = ai4seo_get_setting( AI4SEO_SETTING_METADATA_PREFIXES );
	$suffixes             = ai4seo_get_setting( AI4SEO_SETTING_METADATA_SUFFIXES );

	// Invalid stored affix shapes cannot contribute safely to the effective preview value.
	if ( ! is_array( $prefixes ) ) {
		$prefixes = array();
	}

	if ( ! is_array( $suffixes ) ) {
		$suffixes = array();
	}

	$prefixes = ai4seo_get_metadata_editor_preview_affixes( $post_id, $prefixes );
	$suffixes = ai4seo_get_metadata_editor_preview_affixes( $post_id, $suffixes );

	// Build parallel value, fallback, and quality maps keyed by the existing metadata identifiers.
	foreach ( AI4SEO_AVAILABLE_METADATA_IDENTIFIERS as $metadata_identifier ) {
		$metadata_identifier = sanitize_key( $metadata_identifier );

		$value = $metadata_values[ $metadata_identifier ] ?? '';

		$preview_values[ $metadata_identifier ] = is_scalar( $value ) ? ai4seo_normalize_editor_input_value( (string) $value ) : '';

		$fallback_setting_name = ai4seo_get_metadata_fallback_setting_name( $metadata_identifier );

		// Only metadata types with a declared fallback participate in client-side resolution.
		if ( $fallback_setting_name ) {
			$fallback_preference = ai4seo_get_setting( $fallback_setting_name );

			$fallback_preferences[ $metadata_identifier ] = is_string( $fallback_preference ) ? $fallback_preference : 'no-fallback';
		}

		$quality_window = ai4seo_get_generation_length_quality_window( 'metadata', $metadata_identifier );

		// Empty windows represent fields such as keywords that use item-based guidance instead.
		if ( $quality_window ) {
			$quality_windows[ $metadata_identifier ] = array(
				'min' => absint( $quality_window['min-length'] ?? 0 ),
				'max' => absint( $quality_window['max-length'] ?? 0 ),
			);
		}
	}

	// Resolve immutable WordPress context once for every platform preview in this workspace.
	$post_url       = get_permalink( $post_id );
	$featured_image = get_the_post_thumbnail_url( $post_id, 'large' );
	$site_url       = home_url( '/' );
	$site_domain    = wp_parse_url( $site_url, PHP_URL_HOST );
	$site_domain    = is_string( $site_domain ) ? $site_domain : '';
	$post_url       = is_string( $post_url ) ? $post_url : '';
	$featured_image = is_string( $featured_image ) ? $featured_image : '';

	// Keep the client context presentation-only; live form controls remain authoritative for saving.
	return array(
		'context'                 => 'metadata',
		'focusKeyphraseMaxLength' => AI4SEO_FOCUS_KEYPHRASE_RECOMMENDED_MAX_LENGTH,
		'keywordsMinimumItems'    => AI4SEO_METADATA_KEYWORDS_RECOMMENDED_MIN_ITEMS,
		'keywordsMaximumItems'    => AI4SEO_METADATA_KEYWORDS_RECOMMENDED_MAX_ITEMS,
		'values'                  => $preview_values,
		'activeFields'            => array_values( array_map( 'sanitize_key', $active_fields ) ),
		'fallbackPreferences'     => $fallback_preferences,
		'fallbackSources'         => array(
			'post-title'   => ai4seo_get_metadata_fallback_post_title( $post_id ),
			'post-excerpt' => ai4seo_get_metadata_fallback_post_excerpt( $post_id ),
			'content'      => ai4seo_get_metadata_fallback_post_content( $post_id ),
		),
		'prefixes'                => $prefixes,
		'suffixes'                => $suffixes,
		'qualityWindows'          => $quality_windows,
		'postUrl'                 => $post_url,
		'siteUrl'                 => $site_url,
		'siteDomain'              => $site_domain,
		'siteName'                => sanitize_text_field( get_bloginfo( 'name' ) ),
		'siteIcon'                => esc_url_raw( get_site_icon_url( 64 ) ),
		'featuredImage'           => esc_url_raw( $featured_image ),
		'featuredImageAltText'    => sanitize_text_field( get_post_meta( get_post_thumbnail_id( $post_id ), '_wp_attachment_image_alt', true ) ),
	);
}

// End metadata preview-context construction before the existing metadata utilities begin.

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


/**
 * Returns typed query bindings for the fixed legacy active-metadata key patterns.
 *
 * @return array Typed database-query bindings, or an empty array if the pattern registry is invalid.
 */
function ai4seo_get_legacy_active_metadata_database_query_bindings(): array {
	// Reuse the canonical escaped patterns so migration reads and deletes cannot diverge by operation.
	$patterns = ai4seo_get_legacy_active_metadata_postmeta_key_like_patterns();

	if ( 10 !== count( $patterns ) ) {
		return array();
	}

	$query_bindings = array(
		'postmeta_table'    => ai4seo_database_identifier_binding( 'table.postmeta' ),
		'legacy_key_regexp' => ai4seo_database_scalar_binding( '%s', '^_ai4seo_[0-9]+_.+$' ),
	);

	// Derive the numbered binding keys from the canonical pattern order instead of maintaining a duplicate list.
	foreach ( $patterns as $pattern_index => $pattern ) {
		$query_bindings[ 'legacy_pattern_' . $pattern_index ] = ai4seo_database_scalar_binding( '%s', $pattern );
	}

	return $query_bindings;
}


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


/**
 * Checks whether any legacy active metadata rows still exist.
 *
 * @param bool|null $read_succeeded Receives whether the candidate read completed successfully.
 * @return bool
 */
function ai4seo_has_legacy_active_metadata_rows( ?bool &$read_succeeded = null ): bool {
	$post_ids = ai4seo_read_legacy_active_metadata_migration_v235_candidate_post_ids( 1, $read_succeeded );

	return $read_succeeded && ! empty( $post_ids );
}


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


/**
 * Decodes an active metadata JSON postmeta value.
 *
 * @param string    $active_metadata_json_string The JSON string.
 * @param bool      $active_meta_tags_only Whether only currently active tags should be returned.
 * @param bool|null $decoding_succeeded Receives whether a valid JSON collection was decoded.
 * @return array
 */
function ai4seo_decode_active_metadata_json_string( string $active_metadata_json_string, bool $active_meta_tags_only = false, ?bool &$decoding_succeeded = null ): array {
	$decoding_succeeded = false;

	if ( ! $active_metadata_json_string ) {
		return array();
	}

	$active_metadata = json_decode( $active_metadata_json_string, true );

	if ( ! is_array( $active_metadata ) ) {
		return array();
	}

	$decoding_succeeded = true;
	return ai4seo_prepare_active_metadata_values( $active_metadata, $active_meta_tags_only );
}


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


/**
 * Reads active metadata from the v235 JSON postmeta entry for multiple posts.
 *
 * @param array     $post_ids The post ids.
 * @param bool      $active_meta_tags_only Whether only currently active tags should be returned.
 * @param bool|null $read_succeeded Receives whether every query and stored JSON decode succeeded.
 * @return array
 */
function ai4seo_read_active_metadata_by_post_ids( array $post_ids, bool $active_meta_tags_only = true, ?bool &$read_succeeded = null ): array {
	global $wpdb;

	$read_succeeded = false;
	$post_ids       = array_values( array_filter( array_map( 'absint', $post_ids ) ) );

	if ( ! $post_ids ) {
		$read_succeeded = true;
		return array();
	}

	$active_metadata_by_post_ids = array();
	$database_chunk_size         = ai4seo_get_database_chunk_size();
	$post_ids_chunks             = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( ! $this_post_ids_chunk ) {
			continue;
		}

		$this_query = ai4seo_prepare_database_query(
			'SELECT meta_id, post_id, meta_value FROM {{postmeta_table}} WHERE meta_key = {{meta_key}} AND post_id IN ({{post_ids}}) ORDER BY meta_id ASC',
			array(
				'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- This is an equality lookup for one fixed plugin key, bounded by a chunked post-ID list and ordered by the primary meta ID.
				'meta_key'       => ai4seo_database_scalar_binding( '%s', AI4SEO_POST_META_ACTIVE_METADATA_META_KEY ),
				'post_ids'       => ai4seo_database_list_binding( '%d', array_values( $this_post_ids_chunk ) ),
			)
		);

		if ( false === $this_query ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares this bounded current-state batch read; metadata rows can change between requests and ordering selects the canonical first row.
		$this_rows = $wpdb->get_results( $this_query, ARRAY_A );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321696, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! is_array( $this_rows ) ) {
			return array();
		}

		if ( ! $this_rows ) {
			continue;
		}

		foreach ( $this_rows as $this_row ) {
			if ( ! is_array( $this_row )
				|| ! array_key_exists( 'post_id', $this_row )
				|| ! array_key_exists( 'meta_value', $this_row )
				|| ! is_string( $this_row['meta_value'] ) ) {
				return array();
			}

			$this_post_id = ai4seo_normalize_database_id( $this_row['post_id'] );

			if ( false === $this_post_id || ! in_array( $this_post_id, $post_ids, true ) ) {
				return array();
			}

			if ( array_key_exists( $this_post_id, $active_metadata_by_post_ids ) ) {
				continue;
			}

			$this_decoding_succeeded = false;
			$this_active_metadata    = ai4seo_decode_active_metadata_json_string( $this_row['meta_value'], $active_meta_tags_only, $this_decoding_succeeded );

			if ( ! $this_decoding_succeeded ) {
				return array();
			}

			$active_metadata_by_post_ids[ $this_post_id ] = $this_active_metadata;
		}
	}

	$read_succeeded = true;
	return $active_metadata_by_post_ids;
}


/**
 * Strictly decodes one active-metadata postmeta value without normalizing malformed storage.
 *
 * @param string     $raw_value Exact persisted JSON bytes.
 * @param array|null $active_metadata Receives canonical active metadata on success.
 * @return bool Whether the stored collection exactly satisfies the active-metadata schema.
 */
function ai4seo_decode_active_metadata_postmeta_value_authoritatively( string $raw_value, ?array &$active_metadata = null ): bool {
	$active_metadata = array();
	$decoded_value   = json_decode( $raw_value, true );

	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded_value ) ) {
		return false;
	}

	$prepared_value = ai4seo_prepare_active_metadata_values( $decoded_value, false );

	// Reject unknown keys, structured/scalar-coerced values, and values storage normalization would alter.
	if ( count( $decoded_value ) !== count( $prepared_value ) ) {
		return false;
	}

	foreach ( $decoded_value as $metadata_identifier => $metadata_value ) {
		if ( ! is_string( $metadata_identifier )
			|| ! is_string( $metadata_value )
			|| ! array_key_exists( $metadata_identifier, $prepared_value )
			|| ! hash_equals( $metadata_value, $prepared_value[ $metadata_identifier ] )
		) {
			return false;
		}
	}

	$active_metadata = $prepared_value;
	return true;
}


/**
 * Reads one exact active-metadata row directly from authoritative postmeta storage.
 *
 * Missing storage is a successful empty snapshot. Duplicate rows, malformed JSON, and database
 * failures fail closed so a merge can never be based on an arbitrary or cached predecessor.
 *
 * @param int       $post_id Post ID.
 * @param bool|null $read_succeeded Receives whether exact storage and decoding were authoritative.
 * @return array{exists: bool, meta_id: int, raw_value: string, active_metadata: array}
 */
function ai4seo_read_authoritative_active_metadata_postmeta_snapshot(
	int $post_id,
	?bool &$read_succeeded = null
): array {
	global $wpdb;

	$post_id        = absint( $post_id );
	$read_succeeded = false;
	$empty_snapshot = array(
		'exists'          => false,
		'meta_id'         => 0,
		'raw_value'       => '',
		'active_metadata' => array(),
	);

	if ( $post_id <= 0 ) {
		return $empty_snapshot;
	}

	$query = ai4seo_prepare_database_query(
		'SELECT `meta_id`, `meta_key`, `meta_value`
		FROM {{postmeta_table}}
		WHERE `post_id` = {{post_id}}
		AND BINARY `meta_key` = BINARY {{meta_key}}
		ORDER BY `meta_id` ASC
		LIMIT {{row_limit}}',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Exact post ownership and LIMIT 2 bound this duplicate-owner check.
			'meta_key'       => ai4seo_database_scalar_binding( '%s', AI4SEO_POST_META_ACTIVE_METADATA_META_KEY ),
			'row_limit'      => ai4seo_database_scalar_binding( '%d', 2 ),
		)
	);

	if ( false === $query ) {
		return $empty_snapshot;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler owns this bounded exact-row read, which intentionally bypasses possibly stale postmeta caches.
	$rows = $wpdb->get_results( $query, ARRAY_A );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321737, 'Database error during authoritative active metadata read: ' . $wpdb->last_error, true );
		return $empty_snapshot;
	}

	if ( ! is_array( $rows ) || count( $rows ) > 1 ) {
		return $empty_snapshot;
	}

	if ( ! $rows ) {
		$read_succeeded = true;
		return $empty_snapshot;
	}

	$row             = reset( $rows );
	$meta_id         = is_array( $row ) ? ai4seo_normalize_database_id( $row['meta_id'] ?? null ) : false;
	$active_metadata = array();

	if ( ! is_array( $row )
		|| false === $meta_id
		|| ! array_key_exists( 'meta_key', $row )
		|| ! is_string( $row['meta_key'] )
		|| ! hash_equals( AI4SEO_POST_META_ACTIVE_METADATA_META_KEY, $row['meta_key'] )
		|| ! array_key_exists( 'meta_value', $row )
		|| ! is_string( $row['meta_value'] )
		|| ! ai4seo_decode_active_metadata_postmeta_value_authoritatively( $row['meta_value'], $active_metadata )
	) {
		return $empty_snapshot;
	}

	$read_succeeded = true;

	return array(
		'exists'          => true,
		'meta_id'         => $meta_id,
		'raw_value'       => $row['meta_value'],
		'active_metadata' => $active_metadata,
	);
}


/**
 * Returns the bounded advisory-lock name for one site's active-metadata snapshot owner.
 *
 * @param int $post_id Post ID.
 * @return string Site/post/key-scoped lock name, or an empty string for invalid input.
 */
function ai4seo_get_active_metadata_postmeta_lock_name( int $post_id ): string {
	global $wpdb;

	$post_id        = absint( $post_id );
	$postmeta_table = isset( $wpdb->postmeta ) ? (string) $wpdb->postmeta : '';

	if ( $post_id <= 0 || '' === $postmeta_table ) {
		return '';
	}

	$database_name = defined( 'DB_NAME' ) ? (string) DB_NAME : '';
	$scope         = $database_name . '|' . $postmeta_table . '|' . absint( get_current_blog_id() ) . '|' . $post_id . '|' . AI4SEO_POST_META_ACTIVE_METADATA_META_KEY;

	// MySQL and MariaDB limit advisory-lock names to 64 bytes; this hash isolates site, post, and key ownership.
	return 'ai4seo_active_' . substr( hash( 'sha256', $scope ), 0, 50 );
}


/**
 * Reads whether one stable active-metadata row still contains exact operation-owned bytes.
 *
 * @param int    $post_id Post ID.
 * @param int    $meta_id Active-metadata row ID.
 * @param string $expected_raw_value Exact raw value owned by the operation.
 * @return bool|null True for an exact match, false for missing/replaced ownership, null on read failure.
 */
function ai4seo_active_metadata_postmeta_row_matches_exact_value(
	int $post_id,
	int $meta_id,
	string $expected_raw_value
): ?bool {
	global $wpdb;

	$post_id = absint( $post_id );
	$meta_id = absint( $meta_id );

	if ( $post_id <= 0 || $meta_id <= 0 ) {
		return null;
	}

	$query = ai4seo_prepare_database_query(
		'SELECT `meta_value`
		FROM {{postmeta_table}}
		WHERE `meta_id` = {{meta_id}}
		AND `post_id` = {{post_id}}
		AND BINARY `meta_key` = BINARY {{meta_key}}
		LIMIT {{row_limit}}',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'meta_id'        => ai4seo_database_scalar_binding( '%d', $meta_id ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The primary key and post ID bound this ownership check to one row.
			'meta_key'       => ai4seo_database_scalar_binding( '%s', AI4SEO_POST_META_ACTIVE_METADATA_META_KEY ),
			'row_limit'      => ai4seo_database_scalar_binding( '%d', 1 ),
		)
	);

	if ( false === $query ) {
		return null;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler prepares a stable primary-key ownership read that must bypass postmeta caches.
	$current_raw_value = $wpdb->get_var( $query );

	if ( $wpdb->last_error ) {
		return null;
	}

	if ( null === $current_raw_value ) {
		return false;
	}

	return is_string( $current_raw_value ) && hash_equals( $expected_raw_value, $current_raw_value );
}


/**
 * Deletes only the exact active-metadata row created by the current failed add attempt.
 *
 * @param int    $post_id Post ID.
 * @param int    $meta_id Active-metadata row ID returned by the owned add.
 * @param string $expected_raw_value Exact raw value inserted by the owned add.
 * @return bool Whether the owned row is now absent or replaced without deleting a foreign owner.
 */
function ai4seo_delete_owned_active_metadata_postmeta_row(
	int $post_id,
	int $meta_id,
	string $expected_raw_value
): bool {
	global $wpdb;

	$post_id = absint( $post_id );
	$meta_id = absint( $meta_id );

	if ( $post_id <= 0 || $meta_id <= 0 ) {
		return false;
	}

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core metadata filter.
	$check = apply_filters( 'delete_post_metadata', null, $post_id, AI4SEO_POST_META_ACTIVE_METADATA_META_KEY, $expected_raw_value, false );

	if ( null !== $check ) {
		$row_still_matches = ai4seo_active_metadata_postmeta_row_matches_exact_value( $post_id, $meta_id, $expected_raw_value );

		return null !== $row_still_matches && ! $row_still_matches;
	}

	$query = ai4seo_prepare_database_query(
		'DELETE FROM {{postmeta_table}}
		WHERE `meta_id` = {{meta_id}}
		AND `post_id` = {{post_id}}
		AND BINARY `meta_key` = BINARY {{meta_key}}
		AND BINARY `meta_value` = BINARY {{meta_value}}
		LIMIT {{row_limit}}',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'meta_id'        => ai4seo_database_scalar_binding( '%d', $meta_id ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The primary key bounds this exact owned-row compensation.
			'meta_key'       => ai4seo_database_scalar_binding( '%s', AI4SEO_POST_META_ACTIVE_METADATA_META_KEY ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Stable primary-key and exact-byte ownership bound this compensation to one row.
			'meta_value'     => ai4seo_database_scalar_binding( '%s', $expected_raw_value ),
			'row_limit'      => ai4seo_database_scalar_binding( '%d', 1 ),
		)
	);

	if ( false === $query ) {
		return false;
	}

	$meta_ids = array( (string) $meta_id );

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core metadata action.
	do_action( 'delete_post_meta', $meta_ids, $post_id, AI4SEO_POST_META_ACTIVE_METADATA_META_KEY, $expected_raw_value );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core legacy metadata action.
	do_action( 'delete_postmeta', $meta_ids );

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Exact row identity and operation-owned bytes bound this compensation; metadata hooks and cache invalidation are repaired around it.
	$delete_result = $wpdb->query( $query );

	if ( false === $delete_result || $wpdb->last_error || (int) $delete_result > 1 ) {
		return false;
	}

	if ( 0 === (int) $delete_result ) {
		$row_still_matches = ai4seo_active_metadata_postmeta_row_matches_exact_value( $post_id, $meta_id, $expected_raw_value );

		return null !== $row_still_matches && ! $row_still_matches;
	}

	wp_cache_delete( $post_id, 'post_meta' );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core metadata action.
	do_action( 'deleted_post_meta', $meta_ids, $post_id, AI4SEO_POST_META_ACTIVE_METADATA_META_KEY, $expected_raw_value );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core legacy metadata action.
	do_action( 'deleted_postmeta', $meta_ids );

	return true;
}


/**
 * Saves active metadata to the v235 JSON postmeta entry.
 *
 * @param int        $post_id The post id.
 * @param array      $active_metadata The active metadata values.
 * @param bool       $existing_active_metadata_wins Whether existing JSON values should win over provided values.
 * @param array|null $operation_details Receives commit_state and active_metadata_changed.
 * @param array      $only_if_empty_metadata_identifiers Fields that may replace only a missing or empty stored value.
 * @param array      $only_if_missing_metadata_identifiers Fields that may replace only a missing stored key.
 * @return bool
 */
function ai4seo_save_active_metadata_to_postmeta(
	int $post_id,
	array $active_metadata,
	bool $existing_active_metadata_wins = false,
	?array &$operation_details = null,
	array $only_if_empty_metadata_identifiers = array(),
	array $only_if_missing_metadata_identifiers = array()
): bool {
	global $wpdb;

	$operation_details = array(
		'commit_state'            => 'not_committed',
		'active_metadata_changed' => false,
	);
	$post_id           = absint( $post_id );

	if ( $post_id <= 0 || ! defined( 'AI4SEO_METADATA_DETAILS' ) || ! get_post( $post_id ) ) {
		return false;
	}

	// Match update_post_meta() by applying revision writes to their parent post.
	$revision_parent_post_id = wp_is_post_revision( $post_id );

	if ( $revision_parent_post_id ) {
		$post_id = absint( $revision_parent_post_id );
	}

	$active_metadata                 = ai4seo_prepare_active_metadata_values( $active_metadata, false );
	$recognized_metadata_identifiers = array_keys( AI4SEO_METADATA_DETAILS );
	$normalize_metadata_identifiers  = static function ( array $metadata_identifiers ) use ( $recognized_metadata_identifiers ): array {
		$normalized_metadata_identifiers = array();

		foreach ( $metadata_identifiers as $metadata_identifier ) {
			if ( ! is_string( $metadata_identifier ) ) {
				continue;
			}

			$metadata_identifier = sanitize_key( $metadata_identifier );

			if ( in_array( $metadata_identifier, $recognized_metadata_identifiers, true ) ) {
				$normalized_metadata_identifiers[] = $metadata_identifier;
			}
		}

		return array_values( array_unique( $normalized_metadata_identifiers ) );
	};

	$only_if_empty_metadata_identifiers   = $normalize_metadata_identifiers( $only_if_empty_metadata_identifiers );
	$only_if_missing_metadata_identifiers = $normalize_metadata_identifiers( $only_if_missing_metadata_identifiers );
	$lock_name                            = ai4seo_get_active_metadata_postmeta_lock_name( $post_id );

	if ( '' === $lock_name ) {
		return false;
	}

	try {
		if ( ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock_name )
			|| ! ai4seo_acquire_database_advisory_lock( $lock_name )
		) {
			return false;
		}
	} catch ( Throwable $throwable ) {
		ai4seo_debug_message( 984321740, 'Could not acquire the active-metadata postmeta advisory lock: ' . $throwable->getMessage(), true );
		return false;
	}

	$save_succeeded              = false;
	$write_was_attempted         = false;
	$write_may_have_committed    = false;
	$authoritative_value_changed = false;
	$lock_released               = false;

	try {
		for ( $write_attempt = 0; $write_attempt < 3; ++$write_attempt ) {
			$read_succeeded = false;
			$snapshot       = ai4seo_read_authoritative_active_metadata_postmeta_snapshot( $post_id, $read_succeeded );

			if ( ! $read_succeeded ) {
				break;
			}

			$merged_active_metadata = $snapshot['active_metadata'];

			foreach ( $active_metadata as $metadata_identifier => $metadata_value ) {
				$stored_key_exists = array_key_exists( $metadata_identifier, $snapshot['active_metadata'] );

				if ( $stored_key_exists
					&& ( $existing_active_metadata_wins
						|| in_array( $metadata_identifier, $only_if_missing_metadata_identifiers, true ) )
				) {
					continue;
				}

				if ( $stored_key_exists
					&& in_array( $metadata_identifier, $only_if_empty_metadata_identifiers, true )
					&& '' !== $snapshot['active_metadata'][ $metadata_identifier ]
				) {
					continue;
				}

				$merged_active_metadata[ $metadata_identifier ] = $metadata_value;
			}

			$merged_active_metadata = ai4seo_prepare_active_metadata_values( $merged_active_metadata, false );
			$desired_raw_value      = wp_json_encode( $merged_active_metadata, JSON_UNESCAPED_UNICODE );
			$verified_encoding      = array();

			if ( ! is_string( $desired_raw_value )
				|| ! ai4seo_decode_active_metadata_postmeta_value_authoritatively( $desired_raw_value, $verified_encoding )
				|| $merged_active_metadata !== $verified_encoding
			) {
				break;
			}

			if ( ! empty( $snapshot['exists'] ) && hash_equals( $snapshot['raw_value'], $desired_raw_value ) ) {
				$authoritative_value_changed = $write_was_attempted;
				$save_succeeded              = true;
				break;
			}

			$captured_added_meta_ids = array();
			$capture_added_meta_id   = static function ( int $meta_id, int $object_id, string $meta_key, $meta_value ) use ( $post_id, $desired_raw_value, &$captured_added_meta_ids ): void {
				if ( $post_id === $object_id
					&& AI4SEO_POST_META_ACTIVE_METADATA_META_KEY === $meta_key
					&& is_string( $meta_value )
					&& hash_equals( $desired_raw_value, $meta_value )
				) {
					$captured_added_meta_ids[] = $meta_id;
				}
			};

			add_action( 'added_post_meta', $capture_added_meta_id, PHP_INT_MAX, 4 );

			$write_was_attempted      = true;
			$previous_suppress_errors = $wpdb->suppress_errors( true );
			$wpdb->last_error         = '';
			$write_result             = false;
			$database_error           = '';

			try {
				if ( ! empty( $snapshot['exists'] ) ) {
					$write_result = update_post_meta(
						$post_id,
						AI4SEO_POST_META_ACTIVE_METADATA_META_KEY,
						wp_slash( $desired_raw_value ),
						$snapshot['raw_value']
					);
				} else {
					$write_result = add_post_meta(
						$post_id,
						AI4SEO_POST_META_ACTIVE_METADATA_META_KEY,
						wp_slash( $desired_raw_value ),
						true
					);
				}

				$database_error = (string) $wpdb->last_error;
			} catch ( Throwable $throwable ) {
				$database_error = $throwable->getMessage();
			} finally {
				$wpdb->suppress_errors( $previous_suppress_errors );
				remove_action( 'added_post_meta', $capture_added_meta_id, PHP_INT_MAX );
			}

			$owned_added_meta_id = 0;

			if ( is_int( $write_result )
				&& $write_result > 0
				&& in_array( $write_result, $captured_added_meta_ids, true )
			) {
				$owned_added_meta_id = $write_result;
			}

			$verification_succeeded = false;
			$verified_snapshot      = ai4seo_read_authoritative_active_metadata_postmeta_snapshot( $post_id, $verification_succeeded );

			if ( $verification_succeeded
				&& ! empty( $verified_snapshot['exists'] )
				&& hash_equals( $desired_raw_value, $verified_snapshot['raw_value'] )
			) {
				$authoritative_value_changed = true;
				$save_succeeded              = true;
				break;
			}

			$verified_state_changed = $verification_succeeded
				&& ( ! empty( $snapshot['exists'] ) !== ! empty( $verified_snapshot['exists'] )
					|| ( ! empty( $snapshot['exists'] )
						&& ! empty( $verified_snapshot['exists'] )
						&& ! hash_equals( $snapshot['raw_value'], $verified_snapshot['raw_value'] ) ) );

			if ( false !== $write_result || ! $verification_succeeded || $verified_state_changed ) {
				$write_may_have_committed = true;
			}

			if ( $owned_added_meta_id > 0 ) {
				// Even exact compensation cannot prove a hook or foreign writer made no durable sibling change.
				$write_may_have_committed = true;
				$compensation_succeeded   = false;

				try {
					$compensation_succeeded = ai4seo_delete_owned_active_metadata_postmeta_row(
						$post_id,
						$owned_added_meta_id,
						$desired_raw_value
					);
				} catch ( Throwable $throwable ) {
					ai4seo_debug_message( 984321738, 'Active-metadata postmeta compensation could not be verified: ' . $throwable->getMessage(), true );
				}

				if ( ! $compensation_succeeded ) {
					break;
				}

				// Retry from the authoritative foreign or missing snapshot after removing only this add.
				continue;
			}

			if ( '' !== $database_error ) {
				ai4seo_debug_message( 984321702, 'Active-metadata postmeta write could not be verified: ' . $database_error, true );
				break;
			}

			if ( ! $verification_succeeded ) {
				break;
			}
		}
	} catch ( Throwable $throwable ) {
		$write_may_have_committed = $write_may_have_committed || $write_was_attempted;
		ai4seo_debug_message( 984321741, 'Active-metadata postmeta persistence could not be verified: ' . $throwable->getMessage(), true );
	} finally {
		try {
			$lock_released = ai4seo_release_database_advisory_lock( $lock_name );
		} catch ( Throwable $throwable ) {
			ai4seo_debug_message( 984321739, 'Could not release the active-metadata postmeta advisory lock: ' . $throwable->getMessage(), true );
		}
	}

	if ( ! $lock_released ) {
		ai4seo_debug_message( 984321739, 'Could not release the active-metadata postmeta advisory lock.', true );

		if ( $save_succeeded || $write_may_have_committed ) {
			$operation_details['commit_state'] = 'possibly_committed';
		}

		$operation_details['active_metadata_changed'] = $authoritative_value_changed;
		return false;
	}

	if ( $save_succeeded ) {
		$operation_details['commit_state']            = 'committed';
		$operation_details['active_metadata_changed'] = $authoritative_value_changed;
		return true;
	}

	if ( $write_may_have_committed ) {
		$operation_details['commit_state'] = 'possibly_committed';
	}

	return false;
}


/**
 * Reads candidate post ids with legacy active metadata rows for the v235 migration.
 *
 * @param int       $limit The maximum amount of candidate post ids.
 * @param bool|null $read_succeeded Receives whether query preparation and execution succeeded.
 * @return array
 */
function ai4seo_read_legacy_active_metadata_migration_v235_candidate_post_ids( int $limit, ?bool &$read_succeeded = null ): array {
	global $wpdb;

	$read_succeeded = false;
	$limit          = absint( $limit );

	if ( $limit <= 0 ) {
		$read_succeeded = true;
		return array();
	}

	// Pair the fixed legacy-key bindings with only the caller-controlled batch limit.
	$query_bindings = ai4seo_get_legacy_active_metadata_database_query_bindings();

	if ( ! $query_bindings ) {
		return array();
	}

	$query_bindings['limit'] = ai4seo_database_scalar_binding( '%d', $limit );
	$query                   = ai4seo_prepare_database_query(
		'SELECT DISTINCT post_id
		FROM {{postmeta_table}}
		WHERE (
			meta_key LIKE {{legacy_pattern_0}} OR
			meta_key LIKE {{legacy_pattern_1}} OR
			meta_key LIKE {{legacy_pattern_2}} OR
			meta_key LIKE {{legacy_pattern_3}} OR
			meta_key LIKE {{legacy_pattern_4}} OR
			meta_key LIKE {{legacy_pattern_5}} OR
			meta_key LIKE {{legacy_pattern_6}} OR
			meta_key LIKE {{legacy_pattern_7}} OR
			meta_key LIKE {{legacy_pattern_8}} OR
			meta_key LIKE {{legacy_pattern_9}}
		)
		AND BINARY meta_key REGEXP BINARY {{legacy_key_regexp}}
		LIMIT {{limit}}',
		$query_bindings
	);

	if ( false === $query ) {
		return array();
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares this one-shot migration candidate read; persisted legacy rows must be observed immediately before migration.
	$legacy_active_metadata_post_ids = $wpdb->get_col( $query );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321697, 'Database error: ' . $wpdb->last_error, true );
		return array();
	}

	if ( ! is_array( $legacy_active_metadata_post_ids ) ) {
		return array();
	}

	$normalized_post_id_lookup = array();

	foreach ( $legacy_active_metadata_post_ids as $legacy_active_metadata_post_id ) {
		if ( is_int( $legacy_active_metadata_post_id ) ) {
			$normalized_post_id = $legacy_active_metadata_post_id;
		} elseif ( is_string( $legacy_active_metadata_post_id ) && 1 === preg_match( '/^(0|[1-9][0-9]*)$/', $legacy_active_metadata_post_id ) ) {
			$normalized_post_id = (int) $legacy_active_metadata_post_id;

			if ( (string) $normalized_post_id !== $legacy_active_metadata_post_id ) {
				return array();
			}
		} else {
			return array();
		}

		if ( $normalized_post_id < 0 ) {
			return array();
		}

		// Retain orphan owner zero so the migration deletes it without attempting to migrate metadata.
		$normalized_post_id_lookup[ $normalized_post_id ] = $normalized_post_id;
	}

	$read_succeeded = true;

	return array_values( $normalized_post_id_lookup );
}


/**
 * Reads recognized legacy active metadata rows for specific post ids.
 *
 * @param array     $post_ids The post ids.
 * @param bool|null $read_succeeded Receives whether every required query completed successfully.
 * @return array
 */
function ai4seo_read_legacy_active_metadata_by_post_ids( array $post_ids, ?bool &$read_succeeded = null ): array {
	global $wpdb;

	$read_succeeded = false;

	if ( ! defined( 'AI4SEO_METADATA_DETAILS' ) ) {
		return array();
	}

	$post_ids = array_values( array_filter( array_map( 'absint', $post_ids ) ) );

	if ( ! $post_ids ) {
		$read_succeeded = true;
		return array();
	}

	$legacy_active_metadata_by_post_ids = array();
	$database_chunk_size                = ai4seo_get_database_chunk_size();
	$post_ids_chunks                    = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( ! $this_post_ids_chunk ) {
			continue;
		}

		// Rebuild bindings per chunk so the post-ID list cannot leak into the next migration query.
		$this_query_bindings = ai4seo_get_legacy_active_metadata_database_query_bindings();

		if ( ! $this_query_bindings ) {
			return array();
		}

		$this_query_bindings['post_ids'] = ai4seo_database_list_binding( '%d', array_values( $this_post_ids_chunk ) );
		$this_query                      = ai4seo_prepare_database_query(
			'SELECT meta_id, post_id, meta_key, meta_value
			FROM {{postmeta_table}}
			WHERE (
				meta_key LIKE {{legacy_pattern_0}} OR
				meta_key LIKE {{legacy_pattern_1}} OR
				meta_key LIKE {{legacy_pattern_2}} OR
				meta_key LIKE {{legacy_pattern_3}} OR
				meta_key LIKE {{legacy_pattern_4}} OR
				meta_key LIKE {{legacy_pattern_5}} OR
				meta_key LIKE {{legacy_pattern_6}} OR
				meta_key LIKE {{legacy_pattern_7}} OR
				meta_key LIKE {{legacy_pattern_8}} OR
				meta_key LIKE {{legacy_pattern_9}}
			)
			AND BINARY meta_key REGEXP BINARY {{legacy_key_regexp}}
			AND post_id IN ({{post_ids}})
			ORDER BY meta_id ASC',
			$this_query_bindings
		);

		if ( false === $this_query ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares this bounded one-shot migration read; ascending row order preserves the established last-recognized-value precedence.
		$this_rows = $wpdb->get_results( $this_query, ARRAY_A );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321698, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! is_array( $this_rows ) ) {
			return array();
		}

		if ( ! $this_rows ) {
			continue;
		}

		foreach ( $this_rows as $this_row ) {
			if ( ! is_array( $this_row )
				|| ! array_key_exists( 'post_id', $this_row )
				|| ! isset( $this_row['meta_key'] )
				|| ! is_string( $this_row['meta_key'] )
				|| ! array_key_exists( 'meta_value', $this_row )
				|| ! is_string( $this_row['meta_value'] ) ) {
				return array();
			}

			$this_post_id = ai4seo_normalize_database_id( $this_row['post_id'] );

			if ( false === $this_post_id || ! in_array( $this_post_id, $post_ids, true ) ) {
				return array();
			}

			$this_metadata_identifier = ai4seo_get_legacy_metadata_identifier_by_postmeta_key( $this_row['meta_key'] );

			if ( ! $this_metadata_identifier ) {
				continue;
			}

			if ( ! isset( AI4SEO_METADATA_DETAILS[ $this_metadata_identifier ] ) ) {
				continue;
			}

			$legacy_active_metadata_by_post_ids[ $this_post_id ][ $this_metadata_identifier ] = $this_row['meta_value'];
		}
	}

	foreach ( $legacy_active_metadata_by_post_ids as $this_post_id => $this_legacy_active_metadata ) {
		$legacy_active_metadata_by_post_ids[ $this_post_id ] = ai4seo_prepare_active_metadata_values( $this_legacy_active_metadata, false );
	}

	$read_succeeded = true;

	return $legacy_active_metadata_by_post_ids;
}


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

	// Share one immutable legacy-pattern binding set across the bounded post-ID delete chunks.
	$database_chunk_size = ai4seo_get_database_chunk_size();
	$post_ids_chunks     = array_chunk( $post_ids, $database_chunk_size );
	$query_bindings      = ai4seo_get_legacy_active_metadata_database_query_bindings();

	if ( ! $query_bindings ) {
		return false;
	}

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( ! $this_post_ids_chunk ) {
			continue;
		}

		$this_query_bindings             = $query_bindings;
		$this_query_bindings['post_ids'] = ai4seo_database_list_binding( '%d', $this_post_ids_chunk );
		$delete_query                    = ai4seo_prepare_database_query(
			'DELETE FROM {{postmeta_table}}
			WHERE (
				meta_key LIKE {{legacy_pattern_0}} OR
				meta_key LIKE {{legacy_pattern_1}} OR
				meta_key LIKE {{legacy_pattern_2}} OR
				meta_key LIKE {{legacy_pattern_3}} OR
				meta_key LIKE {{legacy_pattern_4}} OR
				meta_key LIKE {{legacy_pattern_5}} OR
				meta_key LIKE {{legacy_pattern_6}} OR
				meta_key LIKE {{legacy_pattern_7}} OR
				meta_key LIKE {{legacy_pattern_8}} OR
				meta_key LIKE {{legacy_pattern_9}}
			)
			AND BINARY meta_key REGEXP BINARY {{legacy_key_regexp}}
			AND post_id IN ({{post_ids}})',
			$this_query_bindings
		);

		if ( false === $delete_query ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the fixed legacy patterns and normalized post IDs; requested caches are cleared after success.
		$delete_result = $wpdb->query( $delete_query );

		if ( false === $delete_result || $wpdb->last_error ) {
			ai4seo_debug_message( 984321699, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}

		// Clear every requested post to cover stale negative caches and select/delete races.
		ai4seo_invalidate_postmeta_caches( $this_post_ids_chunk );
	}

	return true;
}


/**
 * Deletes all legacy active metadata rows.
 *
 * The operation snapshots its highest matching meta ID, then advances monotonically through that
 * bounded primary-key range. This includes orphaned post_id=0 rows while cache invalidation remains
 * limited to positive WordPress object owners. Rows inserted outside the snapshot remain for a later
 * cleanup, preventing concurrent replenishment from extending this request indefinitely. A final
 * bounded existence read reports those survivors so callers cannot mistake a partial cleanup for
 * completion.
 *
 * @return bool
 */
function ai4seo_delete_all_legacy_active_metadata(): bool {
	global $wpdb;

	// Establish the fixed pattern bindings and finite page size before taking the operation snapshot.
	$query_bindings      = ai4seo_get_legacy_active_metadata_database_query_bindings();
	$database_chunk_size = ai4seo_get_database_chunk_size();

	if ( ! $query_bindings || $database_chunk_size <= 0 ) {
		return false;
	}

	$high_water_query = ai4seo_prepare_database_query(
		'SELECT MAX(meta_id)
		FROM {{postmeta_table}}
		WHERE (
			meta_key LIKE {{legacy_pattern_0}} OR
			meta_key LIKE {{legacy_pattern_1}} OR
			meta_key LIKE {{legacy_pattern_2}} OR
			meta_key LIKE {{legacy_pattern_3}} OR
			meta_key LIKE {{legacy_pattern_4}} OR
			meta_key LIKE {{legacy_pattern_5}} OR
			meta_key LIKE {{legacy_pattern_6}} OR
			meta_key LIKE {{legacy_pattern_7}} OR
			meta_key LIKE {{legacy_pattern_8}} OR
			meta_key LIKE {{legacy_pattern_9}}
		)
		AND BINARY meta_key REGEXP BINARY {{legacy_key_regexp}}',
		$query_bindings
	);

	if ( false === $high_water_query ) {
		return false;
	}

	// Snapshot a finite operation-start boundary before resolving exact cache owners page by page.
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this current-state high-water read for a finite one-shot cleanup.
	$high_water_meta_id = $wpdb->get_var( $high_water_query );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321700, 'Database error: ' . $wpdb->last_error, true );
		return false;
	}

	if ( null === $high_water_meta_id ) {
		$high_water_meta_id = 0;
	} else {
		$high_water_meta_id = ai4seo_normalize_database_id( $high_water_meta_id );

		if ( false === $high_water_meta_id ) {
			return false;
		}
	}

	$meta_id_cursor = 0;

	while ( $meta_id_cursor < $high_water_meta_id ) {
		$this_page_bindings                       = $query_bindings;
		$this_page_bindings['meta_id_cursor']     = ai4seo_database_scalar_binding( '%d', $meta_id_cursor );
		$this_page_bindings['high_water_meta_id'] = ai4seo_database_scalar_binding( '%d', $high_water_meta_id );
		$this_page_bindings['query_limit']        = ai4seo_database_scalar_binding( '%d', $database_chunk_size );
		$affected_rows_query                      = ai4seo_prepare_database_query(
			'SELECT meta_id, post_id
			FROM {{postmeta_table}}
			WHERE (
				meta_key LIKE {{legacy_pattern_0}} OR
				meta_key LIKE {{legacy_pattern_1}} OR
				meta_key LIKE {{legacy_pattern_2}} OR
				meta_key LIKE {{legacy_pattern_3}} OR
				meta_key LIKE {{legacy_pattern_4}} OR
				meta_key LIKE {{legacy_pattern_5}} OR
				meta_key LIKE {{legacy_pattern_6}} OR
				meta_key LIKE {{legacy_pattern_7}} OR
				meta_key LIKE {{legacy_pattern_8}} OR
				meta_key LIKE {{legacy_pattern_9}}
			)
			AND BINARY meta_key REGEXP BINARY {{legacy_key_regexp}}
			AND meta_id > {{meta_id_cursor}}
			AND meta_id <= {{high_water_meta_id}}
			ORDER BY meta_id ASC
			LIMIT {{query_limit}}',
			$this_page_bindings
		);

		if ( false === $affected_rows_query ) {
			return false;
		}

		// Read one current-state primary-key page so exact rows and positive cache owners remain paired.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this monotonic bounded row page for exact deletion and cache invalidation.
		$affected_rows = $wpdb->get_results( $affected_rows_query, ARRAY_A );

		if ( $wpdb->last_error || ! is_array( $affected_rows ) ) {
			ai4seo_debug_message( 984321700, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}

		if ( ! $affected_rows ) {
			break;
		}

		$affected_meta_ids       = array();
		$affected_post_id_lookup = array();

		foreach ( $affected_rows as $affected_row ) {
			$this_meta_id = ai4seo_normalize_database_id( $affected_row['meta_id'] ?? null );

			if ( false === $this_meta_id ) {
				return false;
			}

			if ( $this_meta_id <= $meta_id_cursor || $this_meta_id > $high_water_meta_id ) {
				return false;
			}

			$affected_meta_ids[] = $this_meta_id;

			$this_post_id = isset( $affected_row['post_id'] ) ? (int) $affected_row['post_id'] : -1;

			if ( $this_post_id < 0 ) {
				return false;
			}

			if ( $this_post_id > 0 ) {
				$affected_post_id_lookup[ $this_post_id ] = $this_post_id;
			}
		}

		$next_meta_id_cursor = (int) end( $affected_meta_ids );

		if ( $next_meta_id_cursor <= $meta_id_cursor ) {
			return false;
		}

		$this_delete_bindings             = $query_bindings;
		$this_delete_bindings['meta_ids'] = ai4seo_database_list_binding( '%d', $affected_meta_ids );
		$delete_query                     = ai4seo_prepare_database_query(
			'DELETE FROM {{postmeta_table}}
			WHERE (
				meta_key LIKE {{legacy_pattern_0}} OR
				meta_key LIKE {{legacy_pattern_1}} OR
				meta_key LIKE {{legacy_pattern_2}} OR
				meta_key LIKE {{legacy_pattern_3}} OR
				meta_key LIKE {{legacy_pattern_4}} OR
				meta_key LIKE {{legacy_pattern_5}} OR
				meta_key LIKE {{legacy_pattern_6}} OR
				meta_key LIKE {{legacy_pattern_7}} OR
				meta_key LIKE {{legacy_pattern_8}} OR
				meta_key LIKE {{legacy_pattern_9}}
			)
			AND BINARY meta_key REGEXP BINARY {{legacy_key_regexp}}
			AND meta_id IN ({{meta_ids}})',
			$this_delete_bindings
		);

		if ( false === $delete_query ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared fixed legacy patterns and exact observed primary keys; positive owner caches are invalidated below.
		$delete_result = $wpdb->query( $delete_query );

		if ( false === $delete_result || $wpdb->last_error ) {
			ai4seo_debug_message( 984321700, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}

		// A zero-row race still owns these observed positive cache keys and must not stop cursor progress.
		ai4seo_invalidate_postmeta_caches( array_values( $affected_post_id_lookup ) );
		$meta_id_cursor = $next_meta_id_cursor;
	}

	$remaining_row_query = ai4seo_prepare_database_query(
		'SELECT 1 AS legacy_active_metadata_row, post_id
		FROM {{postmeta_table}}
		WHERE (
			meta_key LIKE {{legacy_pattern_0}} OR
			meta_key LIKE {{legacy_pattern_1}} OR
			meta_key LIKE {{legacy_pattern_2}} OR
			meta_key LIKE {{legacy_pattern_3}} OR
			meta_key LIKE {{legacy_pattern_4}} OR
			meta_key LIKE {{legacy_pattern_5}} OR
			meta_key LIKE {{legacy_pattern_6}} OR
			meta_key LIKE {{legacy_pattern_7}} OR
			meta_key LIKE {{legacy_pattern_8}} OR
			meta_key LIKE {{legacy_pattern_9}}
		)
		AND BINARY meta_key REGEXP BINARY {{legacy_key_regexp}}
		LIMIT 1',
		$query_bindings
	);

	if ( false === $remaining_row_query ) {
		return false;
	}

	// Verify completion without materializing surviving rows or extending the operation snapshot.
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the ten fixed legacy patterns; this bounded current-state read determines whether the finite cleanup fully completed.
	$remaining_legacy_active_metadata_row = $wpdb->get_row( $remaining_row_query, ARRAY_A );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321700, 'Database error: ' . $wpdb->last_error, true );
		return false;
	}

	if ( null === $remaining_legacy_active_metadata_row ) {
		return true;
	}

	if ( ! is_array( $remaining_legacy_active_metadata_row ) || ! array_key_exists( 'post_id', $remaining_legacy_active_metadata_row ) ) {
		return false;
	}

	$remaining_post_id = (int) $remaining_legacy_active_metadata_row['post_id'];

	if ( $remaining_post_id < 0 ) {
		return false;
	}

	if ( $remaining_post_id > 0 ) {
		ai4seo_invalidate_postmeta_caches( array( $remaining_post_id ) );
	}

	return false;
}


/**
 * Runs one v235 active metadata migration batch.
 *
 * @return bool True when migration is completed, false when more work may remain.
 */
function ai4seo_run_active_metadata_migration_v235_batch(): bool {
	$candidate_read_succeeded = false;
	$post_ids                 = ai4seo_read_legacy_active_metadata_migration_v235_candidate_post_ids(
		AI4SEO_ACTIVE_METADATA_MIGRATION_V235_BATCH_SIZE,
		$candidate_read_succeeded
	);

	if ( ! $candidate_read_succeeded ) {
		return false;
	}

	if ( ! $post_ids ) {
		return true;
	}

	$legacy_read_succeeded              = false;
	$legacy_active_metadata_by_post_ids = ai4seo_read_legacy_active_metadata_by_post_ids( $post_ids, $legacy_read_succeeded );

	if ( ! $legacy_read_succeeded ) {
		return false;
	}

	$overall_success       = true;
	$processed_entry_count = 0;

	foreach ( $post_ids as $this_post_id ) {
		$this_legacy_active_metadata = $legacy_active_metadata_by_post_ids[ $this_post_id ] ?? array();

		if ( $this_post_id <= 0 || ! get_post( $this_post_id ) ) {
			$this_success = ai4seo_delete_legacy_active_metadata_for_post_ids( array( $this_post_id ) );

			if ( $this_success ) {
				++$processed_entry_count;
			} else {
				$overall_success = false;
			}

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

		if ( $this_success ) {
			++$processed_entry_count;
		} else {
			$overall_success = false;
		}
	}

	if ( $processed_entry_count > 0 ) {
		$progress_updated = ai4seo_mutate_environmental_variable_value(
			AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_PROCESSED_ENTRIES,
			static function ( $current_processed_entry_count ) use ( $processed_entry_count ): int {
				return max( 0, (int) $current_processed_entry_count ) + $processed_entry_count;
			},
			false
		);

		if ( ! $progress_updated ) {
			$overall_success = false;
		}
	}

	if ( ! $overall_success ) {
		return false;
	}

	$remaining_read_succeeded                  = false;
	$has_remaining_legacy_active_metadata_rows = ai4seo_has_legacy_active_metadata_rows( $remaining_read_succeeded );

	if ( ! $remaining_read_succeeded ) {
		return false;
	}

	return ! $has_remaining_legacy_active_metadata_rows;
}


/**
 * Function to read the post meta from specific posts by the given post ids
 *
 * @param array     $post_ids of post ids (all int).
 * @param bool|null $read_succeeded Receives whether every own-metadata read succeeded.
 * @return array
 */
function ai4seo_read_our_plugins_metadata_by_post_ids( array $post_ids, ?bool &$read_succeeded = null ): array {
	$read_succeeded = false;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 610785731, 'Prevented loop', true );
		return array();
	}

	$active_meta_tags = ai4seo_get_active_meta_tags();

	if ( ! $active_meta_tags ) {
		$read_succeeded = true;
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
		$read_succeeded = true;
		return array();
	}

	// sanitize IDs.
	$post_ids                       = array_map( 'absint', $post_ids );
	$active_metadata_read_succeeded = false;
	$reordered_results              = ai4seo_read_active_metadata_by_post_ids( $post_ids, true, $active_metadata_read_succeeded );

	if ( ! $active_metadata_read_succeeded ) {
		return array();
	}

	if ( ai4seo_is_active_metadata_migration_v235_completed() ) {
		$read_succeeded = true;
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
		$read_succeeded = true;
		return $reordered_results;
	}

	$legacy_metadata_read_succeeded     = false;
	$legacy_active_metadata_by_post_ids = ai4seo_read_legacy_active_metadata_by_post_ids(
		array_values( array_unique( $post_ids_requiring_legacy_fallback ) ),
		$legacy_metadata_read_succeeded
	);

	if ( ! $legacy_metadata_read_succeeded ) {
		return array();
	}

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

	$read_succeeded = true;
	return $reordered_results;
}


/**
 * Function to read the post's metadata for a specific third party plugin from specific posts by the given post ids
 *
 * @param mixed     $third_party_plugin_name The third party plugin name value.
 * @param array     $post_ids of post ids (all int).
 * @param bool|null $read_succeeded Receives whether every provider-metadata read succeeded.
 * @return array the metadata by post-ids, using metadata-identifier keys
 */
function ai4seo_read_third_party_seo_plugin_metadata_by_post_ids( $third_party_plugin_name, array $post_ids, ?bool &$read_succeeded = null ): array {
	global $wpdb;

	$read_succeeded = false;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 915529372, 'Prevented loop', true );
		return array();
	}

	// cast all post ids to int with absint and filter out non-numeric entries.
	$post_ids = array_values( array_filter( array_map( 'absint', $post_ids ) ) );

	// Make sure that all parameters are not empty.
	if ( empty( $post_ids ) ) {
		$read_succeeded = true;
		return array();
	}

	// workaround for Slim SEO.
	if ( AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO === $third_party_plugin_name ) {
		return ai4seo_read_slim_seo_metadata_by_post_ids( $post_ids, $read_succeeded );
	}

	// workaround for Squirrly SEO.
	if ( AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO === $third_party_plugin_name ) {
		return ai4seo_read_squirrly_seo_metadata_by_post_ids( $post_ids, $read_succeeded );
	}

	// workaround for All in One SEO.
	if ( AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO === $third_party_plugin_name ) {
		return ai4seo_read_all_in_one_seo_metadata_by_post_ids( $post_ids, $read_succeeded );
	}

	$third_party_seo_plugin_details = ai4seo_get_third_party_seo_plugin_details();

	// Make sure that all parameters are of the correct type.
	$metadata_postmeta_keys = $third_party_seo_plugin_details[ $third_party_plugin_name ]['generation-field-postmeta-keys'] ?? array();

	if ( ! $metadata_postmeta_keys ) {
		$read_succeeded = true;
		return array();
	}

	$metadata_postmeta_keys = ai4seo_deep_sanitize( $metadata_postmeta_keys );

	$database_chunk_size = ai4seo_get_database_chunk_size();
	$post_ids_chunks     = array_chunk( $post_ids, $database_chunk_size );

	// reorder results, to make post_id the 2d key, then the meta_keys the 1d key and meta_value the value
	// also skip entries with empty meta_value.
	$third_party_seo_plugins_metadata = array();

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( empty( $this_post_ids_chunk ) ) {
			continue;
		}

		$this_query = ai4seo_prepare_database_query(
			'SELECT * FROM {{postmeta_table}} WHERE meta_key IN ({{meta_keys}}) AND post_id IN ({{post_ids}})',
			array(
				'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				'meta_keys'      => ai4seo_database_list_binding( '%s', array_values( $metadata_postmeta_keys ) ),
				'post_ids'       => ai4seo_database_list_binding( '%d', array_values( $this_post_ids_chunk ) ),
			)
		);

		if ( false === $this_query ) {
			return array();
		}

		// Read provider metadata as one current-state batch because third-party plugins can update these rows independently of AI for SEO.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares the bounded batch; AI for SEO owns no cache for independently mutable provider metadata.
		$query_results = $wpdb->get_results( $this_query, ARRAY_A );

		// on error.
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321657, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! is_array( $query_results ) ) {
			return array();
		}

		if ( ! $query_results ) {
			continue;
		}

		foreach ( $query_results as $query_result ) {
			if ( ! is_array( $query_result )
				|| ! array_key_exists( 'post_id', $query_result )
				|| ! isset( $query_result['meta_key'] )
				|| ! is_string( $query_result['meta_key'] )
				|| ! array_key_exists( 'meta_value', $query_result )
				|| ! is_string( $query_result['meta_value'] ) ) {
				return array();
			}

			$this_post_id = ai4seo_normalize_database_id( $query_result['post_id'] );

			if ( false === $this_post_id || ! in_array( $this_post_id, $post_ids, true ) ) {
				return array();
			}

			// find metadata identifier.
			$this_metadata_identifier = array_search( $query_result['meta_key'], $metadata_postmeta_keys, true );

			if ( false === $this_metadata_identifier ) {
				continue;
			}

			$third_party_seo_plugins_metadata[ $this_post_id ][ $this_metadata_identifier ] = $query_result['meta_value'];
		}
	}

	$read_succeeded = true;
	return $third_party_seo_plugins_metadata;
}

/**
 * Decode a compound SEO collection through its supported serialization layers.
 *
 * @param mixed     $stored_value      Raw or WordPress-decoded metadata.
 * @param bool|null $decoding_failed   Optional. Receives whether structured or unsafe data failed closed.
 * @param bool|null $collection_decoded Optional. Receives whether a valid array collection was decoded.
 * @return array Decoded safe values, or an empty array for missing, unsafe, or malformed data.
 */
function ai4seo_decode_compound_seo_values(
	$stored_value,
	?bool &$decoding_failed = null,
	?bool &$collection_decoded = null
): array {
	$decoding_failed           = false;
	$collection_decoded        = false;
	$serialized_layer_detected = is_string( $stored_value )
		&& ( is_serialized( trim( $stored_value ) ) || ai4seo_is_legacy_serialized_custom_object( trim( $stored_value ) ) );

	// Decode the standard storage layer when the value came directly from the database.
	$decoded_values = ai4seo_safe_maybe_unserialize( $stored_value );

	// Supported legacy formats can wrap the same collection in one additional serialization layer.
	if ( is_string( $decoded_values ) ) {
		$serialized_layer_detected = $serialized_layer_detected
			|| is_serialized( trim( $decoded_values ) )
			|| ai4seo_is_legacy_serialized_custom_object( trim( $decoded_values ) );
		$decoded_values            = ai4seo_safe_maybe_unserialize( $decoded_values );
	}

	if ( ! is_array( $decoded_values ) ) {
		// Plain scalar values retain the established empty-collection fallback; recognized structured data fails explicitly.
		$decoding_failed = $serialized_layer_detected || is_object( $decoded_values ) || is_resource( $decoded_values );
		return array();
	}

	// Validate arrays that WordPress or another caller may already have decoded before reaching this boundary.
	$remaining_nodes = 10000;

	if ( ! ai4seo_is_safe_unserialized_value( $decoded_values, 0, $remaining_nodes ) ) {
		$decoding_failed = true;
		return array();
	}

	$collection_decoded = true;

	return $decoded_values;
}

/**
 * Decode Slim SEO's compound metadata without allowing objects.
 *
 * @param mixed     $stored_value Raw or WordPress-decoded Slim SEO metadata.
 * @param bool|null $decoding_failed Optional. Receives whether structured or unsafe data failed closed.
 * @param bool|null $collection_decoded Optional. Receives whether a valid array collection was decoded.
 * @return array Decoded metadata, or an empty array for missing, unsafe, or malformed data.
 */
function ai4seo_decode_slim_seo_values( $stored_value, ?bool &$decoding_failed = null, ?bool &$collection_decoded = null ): array {
	return ai4seo_decode_compound_seo_values( $stored_value, $decoding_failed, $collection_decoded );
}


/**
 * Function to read the post's metadata for the Slim SEO plugin from specific posts by the given post ids
 *
 * @param array     $post_ids of post ids (all int).
 * @param bool|null $read_succeeded Receives whether every query and provider collection decode succeeded.
 * @return array the metadata by post-ids, using metadata-identifier keys
 */
function ai4seo_read_slim_seo_metadata_by_post_ids( array $post_ids, ?bool &$read_succeeded = null ): array {
	$read_succeeded = false;

	// check postmeta "slim_seo". It's serialized with keys "title" and "description", nothing else.
	$metadata_identifier_mapping = array(
		'meta-title'       => 'title',
		'meta-description' => 'description',
	);

	// read postmeta entries.
	global $wpdb;

	// make sure all post ids are absolute integers.
	$post_ids = array_values( array_map( 'absint', $post_ids ) );

	if ( ! $post_ids ) {
		$read_succeeded = true;
		return array();
	}

	// reorder results, to make post_id the 2d key, then the meta_keys the 1d key and meta_value the value
	// also skip entries with empty meta_value.
	$third_party_plugins_metadata = array();
	$malformed_row_encountered    = false;

	$database_chunk_size = ai4seo_get_database_chunk_size();
	$post_ids_chunks     = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( empty( $this_post_ids_chunk ) ) {
			continue;
		}

		$this_query = ai4seo_prepare_database_query(
			'SELECT * FROM {{postmeta_table}} WHERE meta_key = {{meta_key}} AND post_id IN ({{post_ids}})',
			array(
				'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- This is an equality lookup for Slim SEO's fixed key, bounded by the configured chunked post-ID list.
				'meta_key'       => ai4seo_database_scalar_binding( '%s', 'slim_seo' ),
				'post_ids'       => ai4seo_database_list_binding( '%d', array_values( $this_post_ids_chunk ) ),
			)
		);

		if ( false === $this_query ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares this bounded raw-storage batch; bypassing WordPress metadata caching prevents eager unserialization at the safety boundary.
		$this_rows = $wpdb->get_results( $this_query, ARRAY_A );

		// on error.
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321658, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! is_array( $this_rows ) ) {
			return array();
		}

		if ( ! $this_rows ) {
			continue;
		}

		foreach ( $this_rows as $this_row ) {
			if ( ! is_array( $this_row )
				|| ! array_key_exists( 'post_id', $this_row )
				|| ! array_key_exists( 'meta_value', $this_row )
				|| ! is_string( $this_row['meta_value'] ) ) {
				$malformed_row_encountered = true;
				continue;
			}

			// Direct SQL bypasses WordPress's postmeta decoder, so decode the compound Slim SEO row safely here.
			$this_post_id = ai4seo_normalize_database_id( $this_row['post_id'] );

			if ( false === $this_post_id || ! in_array( $this_post_id, $post_ids, true ) ) {
				$malformed_row_encountered = true;
				continue;
			}

			$this_decoding_failed    = false;
			$this_collection_decoded = false;
			$this_metadata           = ai4seo_decode_slim_seo_values( $this_row['meta_value'], $this_decoding_failed, $this_collection_decoded );

			if ( $this_decoding_failed
				|| ( ! $this_collection_decoded && '' !== trim( $this_row['meta_value'] ) ) ) {
				$malformed_row_encountered = true;
				continue;
			}

			if ( ! $this_metadata ) {
				continue;
			}

			foreach ( $metadata_identifier_mapping as $this_metadata_identifier => $this_third_party_plugin_key ) {
				$third_party_plugins_metadata[ $this_post_id ][ $this_metadata_identifier ] = $this_metadata[ $this_third_party_plugin_key ] ?? '';
			}
		}
	}

	$read_succeeded = ! $malformed_row_encountered;
	return $third_party_plugins_metadata;
}


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

/**
 * Decode Squirrly's version-dependent serialized SEO collection without allowing objects.
 *
 * @param mixed     $stored_value    Raw value read from Squirrly's custom table.
 * @param bool|null $decoding_failed Optional. Receives whether non-empty malformed data failed closed.
 * @return array Decoded SEO values, or an empty array for missing, unsafe, or malformed data.
 */
function ai4seo_decode_squirrly_seo_values( $stored_value, ?bool &$decoding_failed = null ): array {
	$collection_decoded = false;
	$decoded_values     = ai4seo_decode_compound_seo_values( $stored_value, $decoding_failed, $collection_decoded );

	// NULL and blank provider columns are valid empty state; other scalars must never be overwritten as if missing.
	if (
		! $decoding_failed
		&& ! $collection_decoded
		&& null !== $stored_value
		&& ( ! is_string( $stored_value ) || '' !== trim( $stored_value ) )
	) {
		$decoding_failed = true;
	}

	return $decoded_values;
}


/**
 * Reads indexed Squirrly URL-hash candidates from an authoritative requested-post snapshot.
 *
 * Squirrly historically hashes ordinary posts as md5(ID) and custom post types as
 * md5(post_type . ID). Both candidates are retained so provider-version classification
 * drift cannot turn an existing canonical row into a false absence.
 *
 * @param array     $post_ids Normalized requested post IDs.
 * @param bool|null $read_succeeded Receives whether every requested-post query and row validation succeeded.
 * @return array<string,array<int,bool>> Candidate hashes mapped to their expected post IDs.
 */
function ai4seo_read_squirrly_url_hash_candidates_by_post_ids( array $post_ids, ?bool &$read_succeeded = null ): array {
	global $wpdb;

	$read_succeeded      = false;
	$requested_id_lookup = array_fill_keys( $post_ids, true );

	if ( ! $requested_id_lookup ) {
		$read_succeeded = true;
		return array();
	}

	$post_types_by_post_id = array();

	foreach ( array_chunk( array_keys( $requested_id_lookup ), ai4seo_get_database_chunk_size() ) as $this_post_id_chunk ) {
		$post_snapshot_query = ai4seo_prepare_database_query(
			'SELECT ID, post_type FROM {{posts_table}} WHERE ID IN ({{post_ids}})',
			array(
				'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
				'post_ids'    => ai4seo_database_list_binding( '%d', array_values( $this_post_id_chunk ) ),
			)
		);

		if ( false === $post_snapshot_query ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares this primary-key-bounded authoritative post-type snapshot; independently mutable provider hashes must be derived from current rows.
		$post_rows = $wpdb->get_results( $post_snapshot_query, ARRAY_A );

		if ( $wpdb->last_error || ! is_array( $post_rows ) ) {
			ai4seo_debug_message( 984321682, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		foreach ( $post_rows as $post_row ) {
			if ( ! is_array( $post_row )
				|| 2 !== count( $post_row )
				|| ! array_key_exists( 'ID', $post_row )
				|| ! array_key_exists( 'post_type', $post_row )
			) {
				return array();
			}

			$post_id   = ai4seo_normalize_database_id( $post_row['ID'] );
			$post_type = $post_row['post_type'];

			if ( false === $post_id
				|| ! isset( $requested_id_lookup[ $post_id ] )
				|| isset( $post_types_by_post_id[ $post_id ] )
				|| ! is_string( $post_type )
				|| '' === $post_type
				|| sanitize_key( $post_type ) !== $post_type
			) {
				return array();
			}

			$post_types_by_post_id[ $post_id ] = $post_type;
		}
	}

	$url_hash_candidates = array();

	foreach ( $post_types_by_post_id as $post_id => $post_type ) {
		$this_post_hashes = array_unique(
			array(
				md5( (string) $post_id ),
				md5( $post_type . (string) $post_id ),
			)
		);

		foreach ( $this_post_hashes as $this_post_hash ) {
			$url_hash_candidates[ $this_post_hash ][ $post_id ] = true;
		}
	}

	// A candidate collision leaves SQL identity ambiguous even if one serialized row currently appears authoritative.
	foreach ( $url_hash_candidates as $expected_post_ids ) {
		if ( 1 !== count( $expected_post_ids ) ) {
			return array();
		}
	}

	$read_succeeded = true;
	return $url_hash_candidates;
}


/**
 * Function to read the post's metadata for the Squirrly SEO plugin from specific posts by the given post ids
 *
 * @param array     $post_ids of post ids (all int).
 * @param bool|null $read_succeeded Receives whether every query and matched provider collection decode succeeded.
 * @return array the metadata by post-ids, using metadata-identifier keys
 */
function ai4seo_read_squirrly_seo_metadata_by_post_ids( array $post_ids, ?bool &$read_succeeded = null ): array {
	$read_succeeded = false;

	// Check the prefixed qss table's serialized seo column for search, keyword, Open Graph, and Twitter values.
	$metadata_identifier_mapping = ai4seo_get_squirrly_seo_metadata_identifier_mapping();

	// Build a stable, deduplicated request set while retaining the established invalid-ID skip behavior.
	$normalized_post_ids = array();
	$requested_id_lookup = array();

	foreach ( $post_ids as $post_id ) {
		$normalized_post_id = ai4seo_normalize_database_id( $post_id );

		if ( false === $normalized_post_id || isset( $requested_id_lookup[ $normalized_post_id ] ) ) {
			continue;
		}

		$requested_id_lookup[ $normalized_post_id ] = true;
		$normalized_post_ids[]                      = $normalized_post_id;
	}

	if ( ! $normalized_post_ids ) {
		$read_succeeded = true;
		return array();
	}

	$url_hash_read_succeeded = false;
	$url_hash_candidates     = ai4seo_read_squirrly_url_hash_candidates_by_post_ids( $normalized_post_ids, $url_hash_read_succeeded );

	if ( ! $url_hash_read_succeeded ) {
		return array();
	}

	// An authoritatively missing WordPress post is valid absence, not permission to scan orphan provider state.
	if ( ! $url_hash_candidates ) {
		$read_succeeded = true;
		return array();
	}

	$current_blog_id = ai4seo_normalize_database_id( get_current_blog_id() );

	if ( false === $current_blog_id ) {
		return array();
	}

	global $wpdb;

	$all_squirrly_values       = array();
	$matched_post_id_lookup    = array();
	$maximum_hash_bindings     = ai4seo_get_database_placeholder_budget() - 1;
	$url_hash_query_chunk_size = min( ai4seo_get_database_chunk_size() * 2, $maximum_hash_bindings );

	if ( $url_hash_query_chunk_size <= 0 ) {
		return array();
	}

	foreach ( array_chunk( array_keys( $url_hash_candidates ), $url_hash_query_chunk_size ) as $this_url_hash_chunk ) {
		$query = ai4seo_prepare_database_query(
			'SELECT blog_id, url_hash, post, seo
			FROM {{squirrly_table}}
			WHERE blog_id = {{blog_id}}
			AND url_hash IN ({{url_hashes}})',
			array(
				'squirrly_table' => ai4seo_database_identifier_binding( 'table.squirrly' ),
				'blog_id'        => ai4seo_database_scalar_binding( '%d', $current_blog_id ),
				'url_hashes'     => ai4seo_database_list_binding( '%s', array_values( $this_url_hash_chunk ) ),
			)
		);

		if ( false === $query ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares one bounded lookup through Squirrly's canonical blog/hash index; provider rows remain uncached because Squirrly can update them independently.
		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( $wpdb->last_error || ! is_array( $results ) ) {
			ai4seo_debug_message( 984321682, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		foreach ( $results as $result ) {
			if ( ! is_array( $result )
				|| 4 !== count( $result )
				|| ! array_key_exists( 'blog_id', $result )
				|| ! array_key_exists( 'url_hash', $result )
				|| ! array_key_exists( 'post', $result )
				|| ! array_key_exists( 'seo', $result )
			) {
				return array();
			}

			$result_blog_id = ai4seo_normalize_database_id( $result['blog_id'] );
			$url_hash       = $result['url_hash'];
			$post_raw       = $result['post'];

			if ( $current_blog_id !== $result_blog_id
				|| ! is_string( $url_hash )
				|| 1 !== preg_match( '/^[a-f0-9]{32}$/', $url_hash )
				|| ! isset( $url_hash_candidates[ $url_hash ] )
				|| ! is_string( $post_raw )
			) {
				return array();
			}

			$squirrly_post_values = ai4seo_safe_maybe_unserialize( $post_raw );
			$post_id              = is_array( $squirrly_post_values )
				? ai4seo_normalize_database_id( $squirrly_post_values['ID'] ?? null )
				: false;

			if ( false === $post_id
				|| ! isset( $requested_id_lookup[ $post_id ] )
				|| ! isset( $url_hash_candidates[ $url_hash ][ $post_id ] )
				|| isset( $matched_post_id_lookup[ $post_id ] )
			) {
				return array();
			}

			$this_decoding_failed = false;
			$this_metadata        = ai4seo_decode_squirrly_seo_values( $result['seo'], $this_decoding_failed );

			if ( $this_decoding_failed ) {
				return array();
			}

			$matched_post_id_lookup[ $post_id ] = true;
			$all_squirrly_values[ $post_id ]    = $this_metadata;
		}
	}

	// Reorder results, making post ID the outer key and SOOZ's metadata identifiers the inner keys.
	$third_party_seo_plugins_metadata = array();

	foreach ( $all_squirrly_values as $post_id => $this_metadata ) {
		foreach ( $metadata_identifier_mapping as $this_metadata_identifier => $this_squirrly_seo_key ) {
			$third_party_seo_plugins_metadata[ $post_id ][ $this_metadata_identifier ] = $this_metadata[ $this_squirrly_seo_key ] ?? '';
		}
	}

	$read_succeeded = true;
	return $third_party_seo_plugins_metadata;
}


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


/**
 * Returns one static AIOSEO column query from the closed metadata-column allowlist.
 *
 * @param string $operation   Either read, update-value, or update-null.
 * @param string $column_name Allowlisted AIOSEO metadata column.
 * @return string Static named-token query template, or an empty string when unsupported.
 */
function ai4seo_get_all_in_one_seo_metadata_query_template( string $operation, string $column_name ): string {
	// Keep every column literal so the shared read/write mapping never becomes a dynamic SQL identifier.
	static $query_templates = array(
		'read'         => array(
			'title'               => 'SELECT title AS metadata_value FROM {{aioseo_table}} WHERE post_id = {{post_id}}',
			'description'         => 'SELECT description AS metadata_value FROM {{aioseo_table}} WHERE post_id = {{post_id}}',
			'og_title'            => 'SELECT og_title AS metadata_value FROM {{aioseo_table}} WHERE post_id = {{post_id}}',
			'og_description'      => 'SELECT og_description AS metadata_value FROM {{aioseo_table}} WHERE post_id = {{post_id}}',
			'twitter_title'       => 'SELECT twitter_title AS metadata_value FROM {{aioseo_table}} WHERE post_id = {{post_id}}',
			'twitter_description' => 'SELECT twitter_description AS metadata_value FROM {{aioseo_table}} WHERE post_id = {{post_id}}',
		),
		'update-value' => array(
			'title'               => 'UPDATE {{aioseo_table}} SET title = {{metadata_value}} WHERE post_id = {{post_id}} AND BINARY title = BINARY {{previous_metadata_value}} LIMIT {{row_limit}}',
			'description'         => 'UPDATE {{aioseo_table}} SET description = {{metadata_value}} WHERE post_id = {{post_id}} AND BINARY description = BINARY {{previous_metadata_value}} LIMIT {{row_limit}}',
			'og_title'            => 'UPDATE {{aioseo_table}} SET og_title = {{metadata_value}} WHERE post_id = {{post_id}} AND BINARY og_title = BINARY {{previous_metadata_value}} LIMIT {{row_limit}}',
			'og_description'      => 'UPDATE {{aioseo_table}} SET og_description = {{metadata_value}} WHERE post_id = {{post_id}} AND BINARY og_description = BINARY {{previous_metadata_value}} LIMIT {{row_limit}}',
			'twitter_title'       => 'UPDATE {{aioseo_table}} SET twitter_title = {{metadata_value}} WHERE post_id = {{post_id}} AND BINARY twitter_title = BINARY {{previous_metadata_value}} LIMIT {{row_limit}}',
			'twitter_description' => 'UPDATE {{aioseo_table}} SET twitter_description = {{metadata_value}} WHERE post_id = {{post_id}} AND BINARY twitter_description = BINARY {{previous_metadata_value}} LIMIT {{row_limit}}',
		),
		'update-null'  => array(
			'title'               => 'UPDATE {{aioseo_table}} SET title = {{metadata_value}} WHERE post_id = {{post_id}} AND title IS NULL LIMIT {{row_limit}}',
			'description'         => 'UPDATE {{aioseo_table}} SET description = {{metadata_value}} WHERE post_id = {{post_id}} AND description IS NULL LIMIT {{row_limit}}',
			'og_title'            => 'UPDATE {{aioseo_table}} SET og_title = {{metadata_value}} WHERE post_id = {{post_id}} AND og_title IS NULL LIMIT {{row_limit}}',
			'og_description'      => 'UPDATE {{aioseo_table}} SET og_description = {{metadata_value}} WHERE post_id = {{post_id}} AND og_description IS NULL LIMIT {{row_limit}}',
			'twitter_title'       => 'UPDATE {{aioseo_table}} SET twitter_title = {{metadata_value}} WHERE post_id = {{post_id}} AND twitter_title IS NULL LIMIT {{row_limit}}',
			'twitter_description' => 'UPDATE {{aioseo_table}} SET twitter_description = {{metadata_value}} WHERE post_id = {{post_id}} AND twitter_description IS NULL LIMIT {{row_limit}}',
		),
	);

	return $query_templates[ $operation ][ $column_name ] ?? '';
}


/**
 * Reads AIOSEO metadata for the requested posts from its canonical table.
 *
 * @param array     $post_ids Post IDs.
 * @param bool|null $read_succeeded Receives whether every provider-table read succeeded.
 * @return array Metadata by post ID, using SOOZ metadata identifiers.
 */
function ai4seo_read_all_in_one_seo_metadata_by_post_ids( array $post_ids, ?bool &$read_succeeded = null ): array {
	$read_succeeded = false;

	// Reuse the write-path allowlist so table reads and writes cannot drift to different columns.
	$metadata_identifier_mapping = ai4seo_get_all_in_one_seo_metadata_identifier_mapping();

	$post_ids = array_values( array_unique( array_filter( ai4seo_deep_sanitize( $post_ids, 'absint' ) ) ) );

	if ( ! $post_ids ) {
		$read_succeeded = true;
		return array();
	}

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

		$this_query = ai4seo_prepare_database_query(
			'SELECT post_id, title, description, og_title, og_description, twitter_title, twitter_description
			FROM {{aioseo_table}}
			WHERE post_id IN ({{post_ids}})',
			array(
				'aioseo_table' => ai4seo_database_identifier_binding( 'table.aioseo_posts' ),
				'post_ids'     => ai4seo_database_list_binding( '%d', array_values( $this_post_ids_chunk ) ),
			)
		);

		if ( false === $this_query ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares this bounded current-state provider read; AI for SEO owns no cache for AIOSEO's independently mutable table.
		$this_rows = $wpdb->get_results( $this_query, ARRAY_A );

		// on error.
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321660, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! is_array( $this_rows ) ) {
			return array();
		}

		if ( empty( $this_rows ) ) {
			continue;
		}

		foreach ( $this_rows as $this_row ) {
			if ( ! is_array( $this_row ) || ! array_key_exists( 'post_id', $this_row ) ) {
				return array();
			}

			$this_post_id = ai4seo_normalize_database_id( $this_row['post_id'] );

			if ( false === $this_post_id || ! in_array( $this_post_id, $post_ids, true ) ) {
				return array();
			}

			foreach ( $metadata_identifier_mapping as $this_metadata_identifier => $this_aioseo_key ) {
				if ( ! array_key_exists( $this_aioseo_key, $this_row ) ) {
					return array();
				}

				$third_party_seo_plugins_metadata[ $this_post_id ][ $this_metadata_identifier ] = $this_row[ $this_aioseo_key ];
			}
		}
	}

	$read_succeeded = true;
	return $third_party_seo_plugins_metadata;
}


/**
 * Returns the number of metadata fields
 *
 * @return int the number of metadata fields
 */
function ai4seo_get_num_metadata_fields(): int {
	return defined( 'AI4SEO_METADATA_DETAILS' ) ? count( AI4SEO_METADATA_DETAILS ) : 0;
}


/**
 * Read all available metadata for one post.
 *
 * @param int       $post_id Post ID.
 * @param bool      $consider_third_party_seo_plugin_metadata Whether third-party SEO metadata should be considered.
 * @param bool|null $read_succeeded Receives whether every metadata-source read succeeded.
 * @return array Available metadata keyed by metadata identifier.
 */
function ai4seo_read_available_metadata( int $post_id, bool $consider_third_party_seo_plugin_metadata = true, ?bool &$read_succeeded = null ): array {
	$available_metadata_by_post_ids = ai4seo_read_available_metadata_by_post_ids(
		array( $post_id ),
		$consider_third_party_seo_plugin_metadata,
		$read_succeeded
	);

	if ( ! isset( $available_metadata_by_post_ids[ $post_id ] ) ) {
		return array();
	}

	return $available_metadata_by_post_ids[ $post_id ];
}


/**
 * Function to read all the available metadata, regardless of the source, for a specific post by the given post id
 *
 * @param array     $post_ids of post ids.
 * @param bool      $consider_third_party_seo_plugin_metadata if true, the own plugin's metadata will be preferred.
 * @param bool|null $read_succeeded Receives whether every metadata-source read succeeded.
 * @return array the post meta coverage by post ids
 */
function ai4seo_read_available_metadata_by_post_ids( array $post_ids, bool $consider_third_party_seo_plugin_metadata = true, ?bool &$read_succeeded = null ): array {
	$read_succeeded = false;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 615102829, 'Prevented loop', true );
		return array();
	}

	// make sure post_ids is not empty.
	if ( empty( $post_ids ) ) {
		$read_succeeded = true;
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
	$our_metadata_read_succeeded      = false;
	$our_plugins_metadata_by_post_ids = ai4seo_read_our_plugins_metadata_by_post_ids( $post_ids, $our_metadata_read_succeeded );

	if ( ! $our_metadata_read_succeeded ) {
		return array();
	}

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
		$read_succeeded = true;
		return $available_metadata;
	}

	// all posts are filled with our own metadata? return the metadata here.
	if ( count( $post_ids ) === 0 ) {
		$read_succeeded = true;
		return $available_metadata;
	}

	// Fill remaining gaps from active third-party SEO plugins.

	// 2. check third party seo plugins
	$active_third_party_seo_plugin_details = ai4seo_get_active_third_party_seo_plugin_details();

	foreach ( $active_third_party_seo_plugin_details as $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details ) {
		$this_provider_read_succeeded                    = false;
		$this_third_plugins_plugins_metadata_by_post_ids = ai4seo_read_third_party_seo_plugin_metadata_by_post_ids(
			$this_third_party_seo_plugin_identifier,
			$post_ids,
			$this_provider_read_succeeded
		);

		if ( ! $this_provider_read_succeeded ) {
			return array();
		}

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
			$read_succeeded = true;
			return $available_metadata;
		}
	}

	$read_succeeded = true;
	return $available_metadata;
}


/**
 * Function to return the amount of active metadata per post id
 *
 * @param array     $post_ids of post ids.
 * @param bool|null $read_succeeded Receives whether every metadata-source read succeeded.
 * @return array the amount of active metadata by post ids
 */
function ai4seo_read_num_available_metadata_by_post_ids( array $post_ids, ?bool &$read_succeeded = null ): array {
	$read_succeeded = false;

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

	$available_metadata_read_succeeded = false;
	$available_metadata                = ai4seo_read_available_metadata_by_post_ids( $post_ids, true, $available_metadata_read_succeeded );

	if ( ! $available_metadata_read_succeeded ) {
		return array();
	}

	if ( ! $available_metadata ) {
		$read_succeeded = true;
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

	$read_succeeded = true;
	return $num_available_metadata_by_post_ids;
}


/**
 * Function to return the percentage of active metadata per post id
 *
 * @param array     $post_ids of post ids.
 * @param int       $round_precision the precision to round the percentage to.
 * @param bool|null $read_succeeded Receives whether every metadata-source read succeeded.
 * @return array the amount of active metadata by post ids
 */
function ai4seo_read_percentage_of_available_metadata_by_post_ids( array $post_ids, int $round_precision = 0, ?bool &$read_succeeded = null ): array {
	$read_succeeded = false;

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

		$read_succeeded = true;
		return $percentage_of_active_metadata_by_post_ids;
	}

	// first read how many metadata values are available per post id,
	// then compare it with the total amount of active meta tags.
	$num_metadata_read_succeeded        = false;
	$num_available_metadata_by_post_ids = ai4seo_read_num_available_metadata_by_post_ids( $post_ids, $num_metadata_read_succeeded );

	if ( ! $num_metadata_read_succeeded ) {
		return array();
	}

	$num_active_meta_tags = count( $active_meta_tags );

	$percentage_of_active_metadata_by_post_ids = array();

	foreach ( $num_available_metadata_by_post_ids as $this_post_id => $this_num_active_metadata ) {
		$percentage_of_active_metadata_by_post_ids[ $this_post_id ] = round( ( $this_num_active_metadata / $num_active_meta_tags ) * 100, $round_precision );
		$percentage_of_active_metadata_by_post_ids[ $this_post_id ] = min( 100, max( 0, $percentage_of_active_metadata_by_post_ids[ $this_post_id ] ) );
	}

	$read_succeeded = true;
	return $percentage_of_active_metadata_by_post_ids;
}


/**
 * Persists one manual editor value set while owning the exact durable Processing claim.
 *
 * The shared transition fence is held only while claiming, renewing, or completing ownership. The
 * durable Processing lease remains visible to reset and stale-recovery paths while primary storage
 * runs. A proven clean primary failure restores the exact prior Pending and Force memberships. Once
 * primary storage commits or becomes ambiguous, cleanup never restores stale queue intent and schedules
 * durable summary repair if exact ownership, coverage, or release cannot be verified.
 *
 * @param int        $post_id Post ID being saved manually.
 * @param string     $context Metadata or attachment-attributes queue context.
 * @param callable   $persistence_callback Callback that persists primary manual values.
 * @param array|null $operation_details Receives reservation, persistence, coverage, rollback, and release outcomes.
 * @param array|null $persistence_details Optional structured callback outcome with a commit_state key.
 * @return bool True only when persistence, exact derived state, and lock release all succeeded.
 */
function ai4seo_save_manual_editor_values_with_generation_fence(
	int $post_id,
	string $context,
	callable $persistence_callback,
	?array &$operation_details = null,
	?array &$persistence_details = null
): bool {
	$post_id                            = absint( $post_id );
	$context                            = sanitize_key( $context );
	$has_structured_persistence_details = func_num_args() >= 5;

	$persistence_details = array(
		'commit_state' => $has_structured_persistence_details ? 'possibly_committed' : 'not_committed',
	);

	$operation_details = array(
		'reservation_succeeded'             => false,
		'ownership_verified_before_storage' => false,
		'persistence_succeeded'             => false,
		'persistence_commit_state'          => $persistence_details['commit_state'],
		'ownership_verified_after_storage'  => false,
		'coverage_succeeded'                => false,
		'rollback_attempted'                => false,
		'rollback_succeeded'                => true,
		'release_succeeded'                 => false,
		'summary_rebuild_scheduled'         => false,
	);

	if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context ) {
		$pending_option_name    = AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME;
		$processing_option_name = AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME;
		$force_option_name      = AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME;
	} elseif ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context ) {
		$pending_option_name    = AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;
		$processing_option_name = AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;
		$force_option_name      = AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;
	} else {
		return false;
	}

	if ( $post_id <= 0 ) {
		return false;
	}

	$processing_claimed     = false;
	$is_force_overwrite     = false;
	$processing_claim_token = '';
	$pending_was_present    = false;
	$force_was_present      = false;
	$claim_checked          = ai4seo_claim_post_id_for_direct_processing(
		$pending_option_name,
		$processing_option_name,
		$post_id,
		$processing_claimed,
		$force_option_name,
		$is_force_overwrite,
		$processing_claim_token,
		$pending_was_present,
		$force_was_present
	);

	if ( ! $processing_claimed || '' === $processing_claim_token ) {
		return false;
	}

	$verify_processing_claim  = static function () use ( $context, $post_id, $processing_claim_token ): bool {
		try {
			return ai4seo_renew_bulk_generation_processing_claim( $context, $post_id, $processing_claim_token );
		} catch ( Throwable $throwable ) {
			ai4seo_debug_message( 418620947, 'Could not verify durable manual-editor generation ownership: ' . $throwable->getMessage(), true );
			return false;
		}
	};
	$release_processing_claim = static function ( bool $restore_pending, bool $restore_force, bool $discard_prior_queue_intent = false ) use ( $context, $post_id, $processing_claim_token ): bool {
		try {
			return ai4seo_release_bulk_generation_processing_claim(
				$context,
				$post_id,
				$processing_claim_token,
				$restore_pending,
				$restore_force,
				$discard_prior_queue_intent
			);
		} catch ( Throwable $throwable ) {
			ai4seo_debug_message( 418620948, 'Could not release durable manual-editor generation ownership: ' . $throwable->getMessage(), true );
			return false;
		}
	};
	$schedule_summary_rebuild = static function (): bool {
		try {
			return ai4seo_schedule_generation_status_summary_rebuild();
		} catch ( Throwable $throwable ) {
			ai4seo_debug_message( 418620950, 'Could not schedule manual-editor generation-summary repair: ' . $throwable->getMessage(), true );
			return false;
		}
	};
	$discard_processing_claim = static function () use ( &$operation_details, $release_processing_claim, $schedule_summary_rebuild ): bool {
		// Persist reconciliation before deleting the last exact pointer to a committed or ambiguous primary write.
		$operation_details['summary_rebuild_scheduled'] = $schedule_summary_rebuild();

		if ( ! $operation_details['summary_rebuild_scheduled'] ) {
			return false;
		}

		return $release_processing_claim( false, false, true );
	};

	if ( ! $claim_checked ) {
		// The claim API can report a failed final renewal after durably publishing exact-token ownership.
		$operation_details['rollback_attempted'] = true;
		$operation_details['rollback_succeeded'] = $release_processing_claim( $pending_was_present, $force_was_present );
		$operation_details['release_succeeded']  = $operation_details['rollback_succeeded'];

		if ( ! $operation_details['rollback_succeeded'] ) {
			$operation_details['summary_rebuild_scheduled'] = $schedule_summary_rebuild();
		}

		ai4seo_debug_message( 867234101, 'A durable manual-editor generation claim survived a failed claim check and required exact-token compensation.', true );
		return false;
	}

	$operation_details['ownership_verified_before_storage'] = $verify_processing_claim();
	$operation_details['reservation_succeeded']             = $operation_details['ownership_verified_before_storage'];

	if ( ! $operation_details['reservation_succeeded'] ) {
		$operation_details['rollback_attempted'] = true;
		$operation_details['rollback_succeeded'] = $release_processing_claim( $pending_was_present, $force_was_present );
		$operation_details['release_succeeded']  = $operation_details['rollback_succeeded'];

		if ( ! $operation_details['rollback_succeeded'] ) {
			$operation_details['summary_rebuild_scheduled'] = $schedule_summary_rebuild();
		}
	} else {
		try {
			$operation_details['persistence_succeeded'] = (bool) call_user_func( $persistence_callback );
		} catch ( Throwable $throwable ) {
			ai4seo_debug_message( 418620945, 'Manual editor persistence failed while generation ownership was held: ' . $throwable->getMessage(), true );
		}

		if ( $operation_details['persistence_succeeded'] ) {
			$operation_details['persistence_commit_state'] = 'committed';
		} elseif ( $has_structured_persistence_details
			&& isset( $persistence_details['commit_state'] )
			&& is_string( $persistence_details['commit_state'] )
			&& in_array( $persistence_details['commit_state'], array( 'not_committed', 'committed', 'possibly_committed' ), true )
		) {
			$operation_details['persistence_commit_state'] = $persistence_details['commit_state'];
		} elseif ( $has_structured_persistence_details ) {
			$operation_details['persistence_commit_state'] = 'possibly_committed';
		} else {
			$operation_details['persistence_commit_state'] = 'not_committed';
		}

		$operation_details['ownership_verified_after_storage'] = $verify_processing_claim();

		if ( ! $operation_details['persistence_succeeded'] ) {
			if ( 'not_committed' === $operation_details['persistence_commit_state'] ) {
				$operation_details['rollback_attempted'] = true;
				$operation_details['rollback_succeeded'] = $release_processing_claim( $pending_was_present, $force_was_present );
				$operation_details['release_succeeded']  = $operation_details['rollback_succeeded'];

				if ( ! $operation_details['rollback_succeeded'] ) {
					$operation_details['summary_rebuild_scheduled'] = $schedule_summary_rebuild();
				}
			} else {
				// Committed or ambiguous primary state must never be made claimable by restoring stale intent.
				$operation_details['release_succeeded'] = $discard_processing_claim();
			}
		} else {
			$coverage_transition     = false;
			$coverage_read_succeeded = false;

			try {
				if ( $operation_details['ownership_verified_after_storage']
					&& AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context
				) {
					// Classify the stored post once so covered content and untracked drafts remain exclusive paths.
					$post = get_post( $post_id );

					if ( $post instanceof WP_Post && in_array( $post->post_status, array( 'draft', 'auto-draft' ), true ) ) {
						if ( ai4seo_is_metadata_editor_enabled_for_post_type( $post->post_type ) ) {
							// Drafts have no coverage destination, so clear every registered queue state plus coverage memberships.
							$draft_queue_option_names   = ai4seo_get_bulk_generation_queue_options_by_context( $context );
							$draft_removal_option_names = array(
								$draft_queue_option_names['missing'],
								AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME,
								AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
							);
							unset( $draft_queue_option_names['missing'], $draft_queue_option_names['processing'] );
							$draft_removal_option_names = array_merge( $draft_removal_option_names, array_values( $draft_queue_option_names ) );

							// Commit the absence-only result through the exact owner's durable terminal transition.
							$operation_details['coverage_succeeded'] = ai4seo_apply_owned_bulk_generation_result_transition(
								$post_id,
								array(),
								$processing_claim_token,
								$draft_removal_option_names
							);
							$operation_details['release_succeeded']  = $operation_details['coverage_succeeded'];
						}
					} elseif ( $post instanceof WP_Post && ai4seo_is_post_a_valid_content_post( $post_id, $post ) ) {
						$coverage_transition = ai4seo_build_metadata_coverage_post_id_option_transition(
							$post_id,
							true,
							true,
							$coverage_read_succeeded
						);
					}
				} elseif ( $operation_details['ownership_verified_after_storage']
					&& AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context
					&& ai4seo_is_post_a_valid_attachment( $post_id )
				) {
					$coverage_transition = ai4seo_build_attachment_attributes_coverage_post_id_option_transition(
						$post_id,
						true,
						true,
						$coverage_read_succeeded
					);
				}

				if ( $coverage_read_succeeded && is_array( $coverage_transition ) && ! empty( $coverage_transition['additions'] ) ) {
					$operation_details['coverage_succeeded'] = ai4seo_apply_owned_bulk_generation_result_transition(
						$post_id,
						array_keys( $coverage_transition['additions'] ),
						$processing_claim_token,
						array_keys( $coverage_transition['removals'] )
					);
					$operation_details['release_succeeded']  = $operation_details['coverage_succeeded'];
				}
			} catch ( Throwable $throwable ) {
				ai4seo_debug_message( 418620949, 'Could not commit manual-editor coverage under durable generation ownership: ' . $throwable->getMessage(), true );
			}

			if ( ! $operation_details['coverage_succeeded'] ) {
				// Primary storage is authoritative now; only exact-token cleanup is safe, never queue restoration.
				$operation_details['release_succeeded'] = $discard_processing_claim();
			}

			if ( ( ! $operation_details['coverage_succeeded'] || ! $operation_details['release_succeeded'] )
				&& ! $operation_details['summary_rebuild_scheduled']
			) {
				$operation_details['summary_rebuild_scheduled'] = $schedule_summary_rebuild();
			}
		}
	}

	if ( $operation_details['rollback_attempted'] && ! $operation_details['rollback_succeeded'] ) {
		ai4seo_debug_message( 418620946, 'Could not restore the exact generation queue state after a failed manual editor save.', true );
	}

	return $operation_details['reservation_succeeded']
		&& $operation_details['persistence_succeeded']
		&& $operation_details['coverage_succeeded']
		&& $operation_details['release_succeeded'];
}


/**
 * Builds the exact metadata coverage transition for one valid content post.
 *
 * @param int       $post_id Post ID whose current persisted metadata is authoritative.
 * @param bool      $clear_failed_generation_status Whether Failed must be absent after success.
 * @param bool      $clear_claimable_generation_status Whether Pending and Force must also be absent.
 * @param bool|null $read_succeeded Receives whether coverage and generated-data reads were authoritative.
 * @return array{additions: array, removals: array}|array{} Normalized transition maps, or empty on read failure.
 */
function ai4seo_build_metadata_coverage_post_id_option_transition(
	int $post_id,
	bool $clear_failed_generation_status = false,
	bool $clear_claimable_generation_status = false,
	?bool &$read_succeeded = null
): array {
	$read_succeeded = false;

	$coverage_option_names         = array(
		AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
	);
	$coverage_read_succeeded       = false;
	$generated_data_read_succeeded = false;
	$is_fully_covered              = ai4seo_read_is_posts_metadata_fully_covered( $post_id, $coverage_read_succeeded );
	$has_generated_data            = ai4seo_post_has_generated_data( $post_id, $generated_data_read_succeeded );

	if ( ! $coverage_read_succeeded || ! $generated_data_read_succeeded ) {
		return array();
	}

	$has_generated_data   = $is_fully_covered && $has_generated_data;
	$destination_option   = $is_fully_covered
		? AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME
		: AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME;
	$transition_additions = array( $destination_option => array( $post_id ) );
	$transition_removals  = array();

	if ( $has_generated_data ) {
		$transition_additions[ AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME ] = array( $post_id );
	}

	foreach ( $coverage_option_names as $coverage_option_name ) {
		if ( ! isset( $transition_additions[ $coverage_option_name ] ) ) {
			$transition_removals[ $coverage_option_name ] = array( $post_id );
		}
	}

	if ( $clear_failed_generation_status ) {
		$transition_removals[ AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME ] = array( $post_id );
	}

	if ( $clear_claimable_generation_status ) {
		$transition_removals[ AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME ]         = array( $post_id );
		$transition_removals[ AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME ] = array( $post_id );
	}

	$read_succeeded = true;
	return array(
		'additions' => $transition_additions,
		'removals'  => $transition_removals,
	);
}


/**
 * Refreshes the metadata coverage for the given post by putting the post id into the corresponding option
 *
 * @param int          $post_id The post id to refresh the metadata coverage for.
 * @param WP_Post|null $post The post object to refresh the metadata coverage for.
 * @param bool         $clear_failed_generation_status Whether a successful manual save also clears Failed.
 * @return bool True only when the complete requested coverage state was verified.
 */
function ai4seo_refresh_one_posts_metadata_coverage_status(
	int $post_id,
	$post = null,
	bool $clear_failed_generation_status = false
): bool {
	if ( $post_id <= 0 ) {
		return false;
	}

	// remove post id if it's not a valid post.
	if ( ! ai4seo_is_post_a_valid_content_post( $post_id, $post ) ) {
		return ai4seo_remove_post_ids_from_all_options( $post_id );
	}

	$coverage_read_succeeded = false;
	$coverage_transition     = ai4seo_build_metadata_coverage_post_id_option_transition(
		$post_id,
		$clear_failed_generation_status,
		false,
		$coverage_read_succeeded
	);

	if ( ! $coverage_read_succeeded ) {
		return false;
	}

	return ai4seo_apply_post_id_option_transition(
		$coverage_transition['additions'],
		$coverage_transition['removals']
	);
}


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
	if ( ! in_array( $post->post_type, $supported_post_types, true ) ) {
		return false;
	}

	// check post status.
	if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private', 'pending' ), true ) ) {
		return false;
	}

	return true;
}


/**
 * Checks if the metadata for a given post is fully covered
 *
 * @param int       $post_id The post id to check the metadata coverage for.
 * @param bool|null $read_succeeded Receives whether the authoritative metadata reads succeeded.
 * @return bool Whether the metadata for a given post is fully covered
 */
function ai4seo_read_is_posts_metadata_fully_covered( int $post_id, ?bool &$read_succeeded = null ): bool {
	$read_succeeded                            = false;
	$percentage_of_active_metadata_by_post_ids = ai4seo_read_percentage_of_available_metadata_by_post_ids(
		array( $post_id ),
		0,
		$read_succeeded
	);

	if ( ! $read_succeeded ) {
		return false;
	}

	// Reuse the shared numeric boundary so persisted strings and calculated values agree.
	return ai4seo_is_full_coverage_percentage( $percentage_of_active_metadata_by_post_ids[ $post_id ] ?? 0 );
}


/**
 * Removes all post ids for all or a specific post type and generation status. It's recommended to run
 *
 * @param string $post_type The post type to remove the post ids for.
 * @param string $generation_status_option_name The generation status option name to remove the post ids for.
 * @return bool True only when every matching post ID was verified absent from the status option.
 */
function ai4seo_remove_all_post_ids_by_post_type_and_generation_status( string $post_type, string $generation_status_option_name ): bool {
	global $wpdb;

	$post_type                     = sanitize_text_field( $post_type );
	$generation_status_option_name = sanitize_key( $generation_status_option_name );

	// The public convenience reader collapses database failures to an empty collection; this destructive path must not.
	$generation_status_snapshot = ai4seo_get_raw_option_snapshot( $generation_status_option_name );

	if ( null === $generation_status_snapshot ) {
		return false;
	}

	$post_ids = ai4seo_normalize_option_post_id_collection( $generation_status_snapshot['value'] );

	// A verified missing or empty option already satisfies the requested removal.
	if ( ! $post_ids ) {
		return true;
	}

	$possible_post_ids_of_post_type = array();
	$database_chunk_size            = ai4seo_get_database_chunk_size();
	$post_ids_chunks                = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( empty( $this_post_ids_chunk ) ) {
			continue;
		}

		$this_query = ai4seo_prepare_database_query(
			'SELECT ID FROM {{posts_table}} WHERE post_type = {{post_type}} AND ID IN ({{post_ids}})',
			array(
				'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
				'post_type'   => ai4seo_database_scalar_binding( '%s', $post_type ),
				'post_ids'    => ai4seo_database_list_binding( '%d', array_values( $this_post_ids_chunk ) ),
			)
		);

		if ( false === $this_query ) {
			return false;
		}

		// Nail down the post type against current rows immediately before mutating the generation-status option.
		$wpdb->last_error = '';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares the bounded list; freshness is required for this one-shot option mutation decision.
		$this_possible_post_ids_of_post_type = $wpdb->get_col( $this_query );

		// on error.
		if ( $wpdb->last_error || ! is_array( $this_possible_post_ids_of_post_type ) ) {
			ai4seo_debug_message( 984321661, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}

		if ( empty( $this_possible_post_ids_of_post_type ) ) {
			continue;
		}

		$possible_post_ids_of_post_type = array_merge( $possible_post_ids_of_post_type, $this_possible_post_ids_of_post_type );
	}

	if ( ! $possible_post_ids_of_post_type ) {
		return true;
	}

	// remove all post_ids of the given post_type from $generation_status_option_name.
	return ai4seo_remove_post_ids_from_options(
		array( $generation_status_option_name ),
		ai4seo_normalize_option_post_id_collection( $possible_post_ids_of_post_type )
	);
}


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

	// Prefer canonical JSON while retaining the legacy serialized fallback for persisted generated-data rows.
	$generated_data = json_decode( $generated_data_raw, true );

	if ( ! is_array( $generated_data ) ) {
		$generated_data = ai4seo_safe_maybe_unserialize( $generated_data_raw );
	}

	if ( ! is_array( $generated_data ) ) {
		return array();
	}

	return ai4seo_deep_sanitize( $generated_data );
}


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


/**
 * Prepares generated data for editor usage while keeping provenance timestamps separate.
 *
 * @param array $generated_data The decoded generated data.
 * @return array
 */
function ai4seo_prepare_generated_data_details( array $generated_data ): array {
	// Keep provenance entries outside field values so content readers never treat them as generated content.
	$prepared_generated_data       = array();
	$generated_at                  = absint( $generated_data['generated_at'] ?? 0 );
	$generated_at_by_field         = array();
	$generated_at_by_field_storage = $generated_data['generated_at_by_field'] ?? array();

	if ( ! is_array( $generated_at_by_field_storage ) ) {
		$generated_at_by_field_storage = array();
	}

	unset( $generated_data['generated_at'], $generated_data['generated_at_by_field'] );

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

		// Empty historical fields have no generated content and must not retain provenance.
		if ( '' === $this_generated_data_value ) {
			continue;
		}

		$prepared_generated_data[ $this_generated_data_identifier ] = $this_generated_data_value;

		// Legacy snapshots use one timestamp; retain it as the fallback until a field is generated again.
		$this_generated_at = absint( $generated_at_by_field_storage[ $this_generated_data_identifier ] ?? $generated_at );

		if ( $this_generated_at > 0 ) {
			$generated_at_by_field[ $this_generated_data_identifier ] = $this_generated_at;
		}
	}

	return array(
		'generated_data'        => $prepared_generated_data,
		'generated_at'          => $generated_at,
		'generated_at_by_field' => $generated_at_by_field,
	);
}


/**
 * Reads generated data details for a given post, if they exist.
 *
 * @param int $post_id The post id.
 * @return array
 */
function ai4seo_read_generated_data_details_from_post_meta( int $post_id ): array {
	// The detail reader is the only generated-data reader that exposes provenance timestamps.
	$generated_data_raw = get_post_meta( $post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, true );
	$generated_data     = ai4seo_decode_generated_data_postmeta_value( $generated_data_raw );

	if ( ! $generated_data ) {
		return array(
			'generated_data'        => array(),
			'generated_at'          => 0,
			'generated_at_by_field' => array(),
		);
	}

	return ai4seo_prepare_generated_data_details( $generated_data );
}


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


/**
 * Builds one canonical generated-data snapshot from an authoritative current state and new fields.
 *
 * @param array     $current_details Current strict generated-data details.
 * @param array     $generated_data Newly generated field values.
 * @param bool      $update_generated_at Whether updated nonempty fields receive provenance timestamps.
 * @param int       $generation_timestamp Timestamp already fixed for every contention retry.
 * @param array     $unresolved_fields Requested fields omitted from a partial response.
 * @param bool|null $build_succeeded Receives whether every supplied generated field was supported.
 * @return array{generated_data: array, generated_at: int, generated_at_by_field: array}
 */
function ai4seo_build_generated_data_details_for_save(
	array $current_details,
	array $generated_data,
	bool $update_generated_at,
	int $generation_timestamp,
	array $unresolved_fields,
	?bool &$build_succeeded = null
): array {
	$build_succeeded           = false;
	$supported_fields          = ai4seo_get_supported_generated_data_field_identifiers();
	$legacy_alias_destinations = array(
		'social-media-title'       => array(
			'facebook-title',
			'twitter-title',
		),
		'social-media-description' => array(
			'facebook-description',
			'twitter-description',
		),
	);
	$current_generated_data    = is_array( $current_details['generated_data'] ?? null )
		? $current_details['generated_data']
		: array();
	$generated_at              = absint( $current_details['generated_at'] ?? 0 );
	$generated_at_by_field     = is_array( $current_details['generated_at_by_field'] ?? null )
		? $current_details['generated_at_by_field']
		: array();
	$raw_input_by_identifier   = array();
	$prepared_input            = array();

	foreach ( $generated_data as $generated_data_identifier => $generated_data_value ) {
		if ( ! is_string( $generated_data_identifier ) && ! is_int( $generated_data_identifier ) ) {
			return array();
		}

		$generated_data_identifier = sanitize_key( (string) $generated_data_identifier );

		if ( '' === $generated_data_identifier ) {
			return array();
		}

		if ( 'generated_at' === $generated_data_identifier || 'generated_at_by_field' === $generated_data_identifier ) {
			continue;
		}

		if ( ! in_array( $generated_data_identifier, $supported_fields, true )
			&& ! array_key_exists( $generated_data_identifier, $legacy_alias_destinations )
		) {
			return array();
		}

		if ( array_key_exists( $generated_data_identifier, $raw_input_by_identifier ) ) {
			return array();
		}

		$raw_input_by_identifier[ $generated_data_identifier ] = $generated_data_value;
	}

	// Canonical request keys always win over a historical shared-social alias in the same response.
	foreach ( $supported_fields as $supported_field ) {
		if ( ! array_key_exists( $supported_field, $raw_input_by_identifier ) ) {
			continue;
		}

		$generated_data_value = $raw_input_by_identifier[ $supported_field ];

		if ( ! is_string( $generated_data_value ) && ! is_scalar( $generated_data_value ) ) {
			return array();
		}

		$prepared_input[ $supported_field ] = ai4seo_prepare_generated_data_field_value( $supported_field, $generated_data_value );
	}

	foreach ( $legacy_alias_destinations as $legacy_alias => $canonical_destinations ) {
		if ( ! array_key_exists( $legacy_alias, $raw_input_by_identifier ) ) {
			continue;
		}

		$generated_data_value = $raw_input_by_identifier[ $legacy_alias ];

		if ( ! is_string( $generated_data_value ) && ! is_scalar( $generated_data_value ) ) {
			return array();
		}

		foreach ( $canonical_destinations as $canonical_destination ) {
			if ( array_key_exists( $canonical_destination, $raw_input_by_identifier ) ) {
				continue;
			}

			$prepared_input[ $canonical_destination ] = ai4seo_prepare_generated_data_field_value( $canonical_destination, $generated_data_value );
		}
	}

	// Omitted requested fields keep their live values, but lose stale generated provenance and eligibility markers.
	foreach ( $unresolved_fields as $unresolved_field ) {
		if ( ! is_string( $unresolved_field ) && ! is_int( $unresolved_field ) ) {
			continue;
		}

		$unresolved_field = sanitize_key( (string) $unresolved_field );
		$fields_to_remove = $legacy_alias_destinations[ $unresolved_field ] ?? array( $unresolved_field );

		foreach ( $fields_to_remove as $field_to_remove ) {
			if ( ! in_array( $field_to_remove, $supported_fields, true ) ) {
				continue;
			}

			unset( $current_generated_data[ $field_to_remove ], $generated_at_by_field[ $field_to_remove ] );
		}
	}

	$is_nonempty_field_updated = false;

	foreach ( $prepared_input as $generated_data_identifier => $generated_data_value ) {
		// Empty API fields remove stale generated ownership but never create or stamp new ownership.
		if ( '' === $generated_data_value ) {
			unset( $current_generated_data[ $generated_data_identifier ], $generated_at_by_field[ $generated_data_identifier ] );
			continue;
		}

		$current_generated_data[ $generated_data_identifier ] = $generated_data_value;
		$is_nonempty_field_updated                            = true;

		if ( $update_generated_at ) {
			$generated_at_by_field[ $generated_data_identifier ] = $generation_timestamp;
		}
	}

	foreach ( $generated_at_by_field as $generated_data_identifier => $field_timestamp ) {
		if ( ! array_key_exists( $generated_data_identifier, $current_generated_data ) ) {
			unset( $generated_at_by_field[ $generated_data_identifier ] );
		}
	}

	if ( $update_generated_at && $is_nonempty_field_updated ) {
		$generated_at = $generation_timestamp;
	}

	$build_succeeded = true;

	return array(
		'generated_data'        => $current_generated_data,
		'generated_at'          => $generated_at,
		'generated_at_by_field' => $generated_at_by_field,
	);
}


/**
 * Compares generated-data details without depending on associative insertion order.
 *
 * @param array $first_details First generated-data details.
 * @param array $second_details Second generated-data details.
 * @return bool Whether field values and provenance are identical.
 */
function ai4seo_generated_data_details_are_identical( array $first_details, array $second_details ): bool {
	$first_generated_data      = $first_details['generated_data'] ?? null;
	$second_generated_data     = $second_details['generated_data'] ?? null;
	$first_generated_at        = $first_details['generated_at'] ?? null;
	$second_generated_at       = $second_details['generated_at'] ?? null;
	$first_generated_by_field  = $first_details['generated_at_by_field'] ?? null;
	$second_generated_by_field = $second_details['generated_at_by_field'] ?? null;

	if ( ! is_array( $first_generated_data )
		|| ! is_array( $second_generated_data )
		|| ! is_int( $first_generated_at )
		|| ! is_int( $second_generated_at )
		|| ! is_array( $first_generated_by_field )
		|| ! is_array( $second_generated_by_field )
	) {
		return false;
	}

	ksort( $first_generated_data );
	ksort( $second_generated_data );
	ksort( $first_generated_by_field );
	ksort( $second_generated_by_field );

	return $first_generated_data === $second_generated_data
		&& $first_generated_at === $second_generated_at
		&& $first_generated_by_field === $second_generated_by_field;
}


/**
 * Encodes canonical generated-data details into the flat persisted snapshot shape.
 *
 * @param array $generated_data_details Canonical generated-data details.
 * @return string|false Encoded JSON, or false when encoding/strict validation fails.
 */
function ai4seo_encode_generated_data_details_for_postmeta( array $generated_data_details ) {
	$generated_data        = $generated_data_details['generated_data'] ?? null;
	$generated_at          = $generated_data_details['generated_at'] ?? null;
	$generated_at_by_field = $generated_data_details['generated_at_by_field'] ?? null;

	if ( ! is_array( $generated_data ) || ! is_int( $generated_at ) || ! is_array( $generated_at_by_field ) ) {
		return false;
	}

	if ( $generated_at > 0 ) {
		$generated_data['generated_at'] = $generated_at;
	}

	if ( $generated_at_by_field ) {
		$generated_data['generated_at_by_field'] = $generated_at_by_field;
	}

	$generated_data_json = wp_json_encode( $generated_data, JSON_UNESCAPED_UNICODE );

	if ( ! is_string( $generated_data_json ) ) {
		return false;
	}

	$verified_details = array();

	if ( ! ai4seo_decode_generated_data_postmeta_value_authoritatively( $generated_data_json, $verified_details )
		|| ! ai4seo_generated_data_details_are_identical( $generated_data_details, $verified_details )
	) {
		return false;
	}

	return $generated_data_json;
}


/**
 * Function to save the generated data for a given post.
 *
 * @param int        $post_id The post id.
 * @param array      $generated_data The generated data.
 * @param bool       $update_generated_at Whether to update the field and top-level generated_at timestamps.
 * @param int        $generated_at The generated-at timestamp to store. Uses the current time when empty.
 * @param array      $unresolved_fields Requested field identifiers omitted from a partial response.
 * @param array|null $operation_details Receives commit_state: not_committed, committed, or possibly_committed.
 * @return bool
 */
function ai4seo_save_generated_data_to_postmeta(
	int $post_id,
	array $generated_data,
	bool $update_generated_at = true,
	int $generated_at = 0,
	array $unresolved_fields = array(),
	?array &$operation_details = null
): bool {
	global $wpdb;

	$operation_details = array(
		'commit_state' => 'not_committed',
	);
	$post_id           = absint( $post_id );

	if ( $post_id <= 0 || ! get_post( $post_id ) ) {
		return false;
	}

	$revision_parent_post_id = wp_is_post_revision( $post_id );

	if ( $revision_parent_post_id ) {
		$post_id = absint( $revision_parent_post_id );
	}

	$lock_name = ai4seo_get_generated_data_postmeta_lock_name( $post_id );

	if ( '' === $lock_name
		|| ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock_name )
		|| ! ai4seo_acquire_database_advisory_lock( $lock_name )
	) {
		return false;
	}

	$generation_timestamp     = $update_generated_at ? ( $generated_at > 0 ? absint( $generated_at ) : time() ) : 0;
	$save_succeeded           = false;
	$write_may_have_committed = false;
	$lock_released            = false;

	try {
		for ( $write_attempt = 0; $write_attempt < 3; ++$write_attempt ) {
			$read_succeeded = false;
			$snapshot       = ai4seo_read_authoritative_generated_data_postmeta_snapshot( $post_id, $read_succeeded );

			if ( ! $read_succeeded ) {
				break;
			}

			$build_succeeded = false;
			$desired_details = ai4seo_build_generated_data_details_for_save(
				$snapshot['generated_data_details'],
				$generated_data,
				$update_generated_at,
				$generation_timestamp,
				$unresolved_fields,
				$build_succeeded
			);

			if ( ! $build_succeeded ) {
				break;
			}

			if ( ai4seo_generated_data_details_are_identical( $snapshot['generated_data_details'], $desired_details ) ) {
				$operation_details['commit_state'] = 'committed';
				$save_succeeded                    = true;
				break;
			}

			$desired_raw_value = ai4seo_encode_generated_data_details_for_postmeta( $desired_details );

			if ( false === $desired_raw_value ) {
				break;
			}

			$captured_added_meta_ids = array();
			$capture_added_meta_id   = static function ( int $meta_id, int $object_id, string $meta_key, $meta_value ) use ( $post_id, $desired_raw_value, &$captured_added_meta_ids ): void {
				if ( $post_id === $object_id
					&& AI4SEO_POST_META_GENERATED_DATA_META_KEY === $meta_key
					&& is_string( $meta_value )
					&& hash_equals( $desired_raw_value, $meta_value )
				) {
					$captured_added_meta_ids[] = $meta_id;
				}
			};

			add_action( 'added_post_meta', $capture_added_meta_id, PHP_INT_MAX, 4 );

			$previous_suppress_errors = $wpdb->suppress_errors( true );
			$wpdb->last_error         = '';
			$write_result             = false;
			$database_error           = '';

			try {
				if ( ! empty( $snapshot['exists'] ) ) {
					$write_result = update_post_meta(
						$post_id,
						AI4SEO_POST_META_GENERATED_DATA_META_KEY,
						wp_slash( $desired_raw_value ),
						$snapshot['previous_value']
					);
				} else {
					$write_result = add_post_meta(
						$post_id,
						AI4SEO_POST_META_GENERATED_DATA_META_KEY,
						wp_slash( $desired_raw_value ),
						true
					);
				}

				$database_error = (string) $wpdb->last_error;
			} catch ( Throwable $throwable ) {
				$database_error = $throwable->getMessage();
			} finally {
				$wpdb->suppress_errors( $previous_suppress_errors );
				remove_action( 'added_post_meta', $capture_added_meta_id, PHP_INT_MAX );
			}

			$owned_added_meta_id = 0;

			if ( is_int( $write_result )
				&& $write_result > 0
				&& in_array( $write_result, $captured_added_meta_ids, true )
			) {
				$owned_added_meta_id = $write_result;
			}

			$verification_succeeded = false;
			$verified_snapshot      = ai4seo_read_authoritative_generated_data_postmeta_snapshot( $post_id, $verification_succeeded );

			if ( $verification_succeeded
				&& ai4seo_generated_data_details_are_identical( $verified_snapshot['generated_data_details'], $desired_details )
			) {
				$operation_details['commit_state'] = 'committed';
				$save_succeeded                    = true;
				break;
			}

			if ( $owned_added_meta_id > 0 ) {
				$compensation_succeeded = false;

				try {
					$compensation_succeeded = ai4seo_delete_owned_generated_data_postmeta_row( $post_id, $owned_added_meta_id, $desired_raw_value );
				} catch ( Throwable $throwable ) {
					ai4seo_debug_message( 984321732, 'Generated-data postmeta compensation could not be verified: ' . $throwable->getMessage(), true );
				}

				if ( ! $compensation_succeeded ) {
					$write_may_have_committed = true;
					break;
				}

				// The operation-owned row is gone; retry from the authoritative foreign or missing snapshot.
				continue;
			} elseif (
				false !== $write_result
				|| ! $verification_succeeded
				|| '' !== $database_error
			) {
				$write_may_have_committed = true;
			}

			if ( '' !== $database_error ) {
				ai4seo_debug_message( 984321730, 'Generated-data postmeta write could not be verified: ' . $database_error, true );
				break;
			}

			if ( ! $verification_succeeded ) {
				break;
			}
		}
	} finally {
		$lock_released = ai4seo_release_database_advisory_lock( $lock_name );
	}

	if ( ! $lock_released ) {
		ai4seo_debug_message( 984321731, 'Could not release the generated-data postmeta advisory lock.', true );
		$write_may_have_committed = $write_may_have_committed || $save_succeeded;
		$save_succeeded           = false;
	}

	if ( ! $save_succeeded && $write_may_have_committed ) {
		$operation_details['commit_state'] = 'possibly_committed';
	}

	return $save_succeeded;
}


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
	$generated_at_by_field         = $generated_data_details['generated_at_by_field'] ?? array();
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
			$this_generated_at = absint( $generated_at_by_field[ $this_metadata_identifier ] ?? 0 );

			$source_details[ $this_metadata_identifier ] = ai4seo_get_editor_field_sooz_source_details( $this_generated_at, false );
			continue;
		}

		if ( $this_exact_third_party_candidate ) {
			$source_details[ $this_metadata_identifier ] = ai4seo_get_editor_field_third_party_source_details( $this_exact_third_party_candidate['plugin_name'] ?? '', false );
			continue;
		}

		if ( $this_has_generated_value ) {
			$this_generated_at = absint( $generated_at_by_field[ $this_metadata_identifier ] ?? 0 );

			$source_details[ $this_metadata_identifier ] = ai4seo_get_editor_field_sooz_source_details( $this_generated_at, true );
			continue;
		}

		if ( $this_first_third_party_candidate ) {
			$source_details[ $this_metadata_identifier ] = ai4seo_get_editor_field_third_party_source_details( $this_first_third_party_candidate['plugin_name'] ?? '', true );
		}
	}

	return $source_details;
}


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
	$generated_at_by_field  = $generated_data_details['generated_at_by_field'] ?? array();
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
			absint( $generated_at_by_field[ $this_attachment_attribute_identifier ] ?? 0 ),
			$this_active_value !== $this_generated_value
		);
	}

	return $source_details;
}


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
	$meta_key             = wp_unslash( $meta_key );
	$meta_subtype         = get_object_subtype( 'post', $post_id );
	$expected_meta_value  = sanitize_meta( $meta_key, $meta_value, 'post', $meta_subtype );
	$expected_comparison  = ai4seo_normalize_post_meta_value_for_comparison( $expected_meta_value );
	$latest_meta_values   = get_post_meta( $post_id, $meta_key, false );
	$latest_meta_values   = is_array( $latest_meta_values ) ? $latest_meta_values : array();
	$matching_value_count = 0;

	// An unconstrained update must align every duplicate row, not merely find one matching row.
	foreach ( $latest_meta_values as $latest_meta_value ) {
		$latest_comparison = ai4seo_normalize_post_meta_value_for_comparison( $latest_meta_value );

		if ( $expected_comparison === $latest_comparison ) {
			++$matching_value_count;
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
			++$previous_target_value_count;
		}

		if ( $expected_comparison === $this_previous_comparison ) {
			++$previous_expected_value_count;
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
			++$latest_previous_value_count;
		}
	}

	// Every targeted row must disappear and contribute a corresponding requested-value row.
	return 0 === $latest_previous_value_count
		&& $matching_value_count >= ( $previous_expected_value_count + $previous_target_value_count );
}


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
		'commit_state'               => 'not_committed',
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
	$third_party_sync_may_have_committed      = false;
	$did_merge_legacy_active_metadata         = false;
	$active_metadata_updates_to_save          = array();
	$only_if_empty_metadata_identifiers       = array();
	$only_if_missing_metadata_identifiers     = array();

	// Until migration finishes, merge missing legacy values into the same atomic SOOZ JSON write.
	if ( ! ai4seo_is_active_metadata_migration_v235_completed() ) {
		$legacy_read_succeeded              = false;
		$legacy_active_metadata_by_post_ids = ai4seo_read_legacy_active_metadata_by_post_ids( array( $post_id ), $legacy_read_succeeded );

		if ( ! $legacy_read_succeeded ) {
			return false;
		}

		$legacy_active_metadata = $legacy_active_metadata_by_post_ids[ $post_id ] ?? array();

		foreach ( $legacy_active_metadata as $this_metadata_identifier => $this_metadata_value ) {
			$active_metadata_updates_to_save[ $this_metadata_identifier ] = $this_metadata_value;
			$only_if_missing_metadata_identifiers[]                       = $this_metadata_identifier;
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
		$this_third_party_sync_may_have_committed = false;

		try {
			$this_third_party_sync_result = ai4seo_update_third_party_seo_plugins_metadata(
				$post_id,
				$this_metadata_identifier,
				$this_new_metadata_value,
				$overwrite_this_metadata_field,
				$this_third_party_sync_may_have_committed
			);
		} finally {
			$third_party_sync_may_have_committed = $third_party_sync_may_have_committed
				|| $this_third_party_sync_may_have_committed;

			if ( $third_party_sync_may_have_committed && 'committed' !== $operation_details['commit_state'] ) {
				$operation_details['commit_state'] = 'possibly_committed';
			}
		}

		// Remember any successful integration write so the frontend cache is purged once after the loop.
		if ( $this_third_party_sync_result['sync_reached_requested_state'] ) {
			$third_party_sync_reached_requested_state = true;
			$operation_details['commit_state']        = 'committed';
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

		// Queue the SOOZ value; the locked writer evaluates non-overwrite intent against exact storage.
		$active_metadata_updates_to_save[ $this_metadata_identifier ] = $this_new_metadata_value;
		$should_save_own_metadata                                     = true;

		// A live update for this field supersedes a legacy missing-key-only migration candidate.
		$only_if_missing_metadata_identifiers = array_values(
			array_diff( $only_if_missing_metadata_identifiers, array( $this_metadata_identifier ) )
		);

		if ( $overwrite_this_metadata_field ) {
			$only_if_empty_metadata_identifiers = array_values(
				array_diff( $only_if_empty_metadata_identifiers, array( $this_metadata_identifier ) )
			);
		} else {
			$only_if_empty_metadata_identifiers[] = $this_metadata_identifier;
		}
	}

	// A no-op is successful; actual writes replace these defaults with their observed outcomes.
	$active_metadata_succeeded                = true;
	$legacy_active_metadata_cleanup_succeeded = true;
	$active_metadata_commit_state             = 'not_committed';

	if ( $should_save_own_metadata ) {
		$active_metadata_operation_details = array();
		$active_metadata_succeeded         = ai4seo_save_active_metadata_to_postmeta(
			$post_id,
			$active_metadata_updates_to_save,
			false,
			$active_metadata_operation_details,
			array_values( array_unique( $only_if_empty_metadata_identifiers ) ),
			array_values( array_unique( $only_if_missing_metadata_identifiers ) )
		);
		$active_metadata_commit_state      = isset( $active_metadata_operation_details['commit_state'] )
			&& is_string( $active_metadata_operation_details['commit_state'] )
			&& in_array( $active_metadata_operation_details['commit_state'], array( 'not_committed', 'committed', 'possibly_committed' ), true )
			? $active_metadata_operation_details['commit_state']
			: 'possibly_committed';

		if ( $active_metadata_succeeded ) {
			$operation_details['commit_state']        = 'committed';
			$legacy_active_metadata_cleanup_succeeded = ai4seo_delete_legacy_active_metadata_for_post_ids( array( $post_id ) );
		} elseif ( 'possibly_committed' === $active_metadata_commit_state
			&& 'committed' !== $operation_details['commit_state']
		) {
			$operation_details['commit_state'] = 'possibly_committed';
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
	if ( ( $should_save_own_metadata && ( $active_metadata_succeeded || 'not_committed' !== $active_metadata_commit_state ) )
		|| $third_party_sync_reached_requested_state
	) {
		// Cache integrations are optional, so their exceptions must not change persistence results.
		try {
			ai4seo_purge_frontend_cache_for_post( $post_id );
		} catch ( Exception $e ) {
			ai4seo_debug_message( 1908261200, 'Frontend cache purge failed after metadata persistence: ' . $e->getMessage(), true );
		}
	}

	return $operation_details['overall_succeeded'];
}


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


/**
 * Reports whether a provider value is literally empty without treating numeric or boolean values as empty.
 *
 * @param mixed $metadata_value Provider metadata value.
 * @return bool True only for a missing/null value or an empty string.
 */
function ai4seo_is_third_party_seo_metadata_value_literally_empty( $metadata_value ): bool {
	return null === $metadata_value || ( is_string( $metadata_value ) && '' === $metadata_value );
}


/**
 * Reads at most two exact postmeta rows so ordinary provider writes can reject ambiguous duplicates.
 *
 * @param int       $post_id        Post ID.
 * @param string    $meta_key       Provider postmeta key.
 * @param bool|null $read_succeeded Receives whether the authoritative read completed successfully.
 * @return array<int,array{meta_id:int,meta_value:string}> Exact raw postmeta rows in stable ID order.
 */
function ai4seo_read_exact_third_party_postmeta_rows(
	int $post_id,
	string $meta_key,
	?bool &$read_succeeded = null
): array {
	global $wpdb;

	$read_succeeded = false;
	$query          = ai4seo_prepare_database_query(
		'SELECT meta_id, meta_value
		FROM {{postmeta_table}}
		WHERE post_id = {{post_id}}
		AND BINARY meta_key = BINARY {{meta_key}}
		ORDER BY meta_id ASC
		LIMIT {{row_limit}}',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One provider key and owner identify the bounded exact-row snapshot.
			'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ),
			'row_limit'      => ai4seo_database_scalar_binding( '%d', 2 ),
		)
	);

	if ( false === $query ) {
		return array();
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares this bounded current-state snapshot for an exact raw-value CAS.
	$rows = $wpdb->get_results( $query, ARRAY_A );

	if ( $wpdb->last_error || ! is_array( $rows ) ) {
		ai4seo_debug_message( 984321706, 'Database error: ' . $wpdb->last_error, true );
		return array();
	}

	$normalized_rows = array();

	foreach ( $rows as $row ) {
		$meta_id        = ai4seo_normalize_database_id( $row['meta_id'] ?? null );
		$raw_meta_value = $row['meta_value'] ?? null;

		if ( false === $meta_id || ! is_string( $raw_meta_value ) ) {
			return array();
		}

		$normalized_rows[] = array(
			'meta_id'    => $meta_id,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- This is a normalized in-memory result key, not a SQL predicate.
			'meta_value' => $raw_meta_value,
		);
	}

	$read_succeeded = true;

	return $normalized_rows;
}


/**
 * Updates one stable postmeta row only while its exact raw bytes remain operation-owned.
 *
 * WordPress's empty previous-value argument is unconstrained, so the ordinary metadata API cannot
 * protect an empty provider value against a concurrent editor. This helper mirrors the core metadata
 * hooks around one exact raw-value CAS and invalidates the canonical postmeta cache after success.
 *
 * @param int    $meta_id            Stable postmeta row ID.
 * @param int    $post_id            Owning post ID.
 * @param string $meta_key           Provider postmeta key.
 * @param string $previous_raw_value Exact raw value observed before the write.
 * @param string $metadata_value     Requested provider metadata value.
 * @return bool|null True on success or an authoritative hook short-circuit, false on contention, null on failure.
 */
function ai4seo_compare_and_swap_empty_third_party_postmeta_row(
	int $meta_id,
	int $post_id,
	string $meta_key,
	string $previous_raw_value,
	string $metadata_value
): ?bool {
	global $wpdb;

	// Match update_post_meta() by allowing the standard metadata filter to own the requested operation.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core metadata hook.
	$check = apply_filters( 'update_post_metadata', null, $post_id, $meta_key, $metadata_value, '' );

	if ( null !== $check ) {
		return $check ? true : null;
	}

	$meta_subtype               = get_object_subtype( 'post', $post_id );
	$unsanitized_metadata_value = $metadata_value;
	$metadata_value             = sanitize_meta( $meta_key, $metadata_value, 'post', $meta_subtype );
	$replacement_raw_value      = maybe_serialize( $metadata_value );

	if ( ! is_string( $replacement_raw_value ) ) {
		return null;
	}

	if ( hash_equals( $previous_raw_value, $replacement_raw_value ) ) {
		return true;
	}

	$update_query = ai4seo_prepare_database_query(
		'UPDATE {{postmeta_table}}
		SET meta_value = {{replacement_meta_value}}
		WHERE meta_id = {{meta_id}}
		AND post_id = {{post_id}}
		AND BINARY meta_key = BINARY {{meta_key}}
		AND BINARY meta_value = BINARY {{previous_meta_value}}
		LIMIT {{row_limit}}',
		array(
			'postmeta_table'         => ai4seo_database_identifier_binding( 'table.postmeta' ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- The stable primary key bounds this exact previous-byte CAS to one provider row.
			'replacement_meta_value' => ai4seo_database_scalar_binding( '%s', $replacement_raw_value ),
			'meta_id'                => ai4seo_database_scalar_binding( '%d', $meta_id ),
			'post_id'                => ai4seo_database_scalar_binding( '%d', $post_id ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The stable primary key bounds this exact provider-key ownership predicate to one row.
			'meta_key'               => ai4seo_database_scalar_binding( '%s', $meta_key ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- The stable primary key bounds this exact previous-byte ownership predicate to one row.
			'previous_meta_value'    => ai4seo_database_scalar_binding( '%s', $previous_raw_value ),
			'row_limit'              => ai4seo_database_scalar_binding( '%d', 1 ),
		)
	);

	if ( false === $update_query ) {
		return null;
	}

	// Mirror WordPress core's before-update actions using the same unsanitized and serialized values.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core metadata hook.
	do_action( 'update_post_meta', $meta_id, $post_id, $meta_key, $unsanitized_metadata_value );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core legacy metadata hook.
	do_action( 'update_postmeta', $meta_id, $post_id, $meta_key, $replacement_raw_value );

	$previous_suppress_errors = $wpdb->suppress_errors( true );
	$wpdb->last_error         = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler binds stable row identity and exact previous bytes; cache and metadata actions are repaired below after a committed update.
	$query_result   = $wpdb->query( $update_query );
	$database_error = (string) $wpdb->last_error;

	$wpdb->suppress_errors( $previous_suppress_errors );

	if ( false === $query_result || '' !== $database_error ) {
		ai4seo_debug_message( 984321707, 'Database error during exact third-party postmeta update: ' . $database_error, true );
		return null;
	}

	if ( 1 !== $query_result ) {
		return false;
	}

	wp_cache_delete( $post_id, 'post_meta' );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core metadata hook.
	do_action( 'updated_post_meta', $meta_id, $post_id, $meta_key, $unsanitized_metadata_value );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core legacy metadata hook.
	do_action( 'updated_postmeta', $meta_id, $post_id, $meta_key, $replacement_raw_value );

	return true;
}


/**
 * Reads whether one ordinary-provider postmeta row still has exact operation-owned identity and bytes.
 *
 * @param int    $meta_id        Stable postmeta row ID.
 * @param int    $post_id        Owning post ID.
 * @param string $meta_key       Provider postmeta key.
 * @param string $raw_meta_value Exact raw value inserted by the operation.
 * @return bool|null True for an exact match, false for missing/replaced ownership, null on read failure.
 */
function ai4seo_third_party_postmeta_row_matches_exact_value(
	int $meta_id,
	int $post_id,
	string $meta_key,
	string $raw_meta_value
): ?bool {
	global $wpdb;

	$meta_id = absint( $meta_id );
	$post_id = absint( $post_id );

	if ( $meta_id <= 0 || $post_id <= 0 || '' === $meta_key ) {
		return null;
	}

	$query = ai4seo_prepare_database_query(
		'SELECT meta_value FROM {{postmeta_table}}
		WHERE meta_id = {{meta_id}}
		AND post_id = {{post_id}}
		AND BINARY meta_key = BINARY {{meta_key}}
		LIMIT {{row_limit}}',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'meta_id'        => ai4seo_database_scalar_binding( '%d', $meta_id ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Stable primary-key and post ownership bound this exact provider-key check to one row.
			'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ),
			'row_limit'      => ai4seo_database_scalar_binding( '%d', 1 ),
		)
	);

	if ( false === $query ) {
		return null;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler binds stable row identity; this compensation checkpoint must bypass possibly stale postmeta caches.
	$current_raw_value = $wpdb->get_var( $query );

	if ( $wpdb->last_error ) {
		return null;
	}

	return is_string( $current_raw_value ) && hash_equals( $raw_meta_value, $current_raw_value );
}


/**
 * Deletes only the exact ordinary-provider postmeta row created by this operation.
 *
 * The stable meta ID and exact raw bytes prevent compensation from deleting a concurrently
 * changed provider owner while the standard WordPress delete filter/actions remain observable.
 *
 * @param int    $meta_id           Stable postmeta row ID.
 * @param int    $post_id           Owning post ID.
 * @param string $meta_key          Provider postmeta key.
 * @param mixed  $logical_meta_value Sanitized logical value exposed by WordPress's add hook.
 * @param string $raw_meta_value    Exact raw value inserted by the operation.
 * @return bool Whether the operation-owned row is now absent or no longer operation-owned.
 */
function ai4seo_delete_owned_third_party_postmeta_row(
	int $meta_id,
	int $post_id,
	string $meta_key,
	$logical_meta_value,
	string $raw_meta_value
): bool {
	global $wpdb;

	$meta_id = absint( $meta_id );
	$post_id = absint( $post_id );

	if ( $meta_id <= 0 || $post_id <= 0 || '' === $meta_key ) {
		return false;
	}

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors WordPress core's authoritative delete-metadata filter.
	$check = apply_filters( 'delete_post_metadata', null, $post_id, $meta_key, $logical_meta_value, false );

	if ( null !== $check ) {
		$row_still_matches = ai4seo_third_party_postmeta_row_matches_exact_value( $meta_id, $post_id, $meta_key, $raw_meta_value );

		return null !== $row_still_matches && ! $row_still_matches;
	}

	$delete_query = ai4seo_prepare_database_query(
		'DELETE FROM {{postmeta_table}}
		WHERE meta_id = {{meta_id}}
		AND post_id = {{post_id}}
		AND BINARY meta_key = BINARY {{meta_key}}
		AND BINARY meta_value = BINARY {{meta_value}}
		LIMIT {{row_limit}}',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'meta_id'        => ai4seo_database_scalar_binding( '%d', $meta_id ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Stable primary-key and post ownership bound this exact provider-key compensation to one row.
			'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Stable primary-key identity bounds this exact operation-owned byte predicate to one row.
			'meta_value'     => ai4seo_database_scalar_binding( '%s', $raw_meta_value ),
			'row_limit'      => ai4seo_database_scalar_binding( '%d', 1 ),
		)
	);

	if ( false === $delete_query ) {
		return false;
	}

	$meta_ids = array( (string) $meta_id );

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors WordPress core's pre-delete metadata action.
	do_action( 'delete_post_meta', $meta_ids, $post_id, $meta_key, $logical_meta_value );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors WordPress core's legacy pre-delete postmeta action.
	do_action( 'delete_postmeta', $meta_ids );

	$previous_suppress_errors = $wpdb->suppress_errors( true );
	$wpdb->last_error         = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler binds stable row identity and exact operation-owned bytes; cache and post-delete actions are repaired after a committed delete.
	$delete_result  = $wpdb->query( $delete_query );
	$database_error = (string) $wpdb->last_error;

	$wpdb->suppress_errors( $previous_suppress_errors );

	if ( false === $delete_result || '' !== $database_error || (int) $delete_result > 1 ) {
		ai4seo_debug_message( 984321733, 'Database error during exact ordinary-provider postmeta compensation: ' . $database_error, true );
		return false;
	}

	if ( 0 === (int) $delete_result ) {
		$row_still_matches = ai4seo_third_party_postmeta_row_matches_exact_value( $meta_id, $post_id, $meta_key, $raw_meta_value );

		return null !== $row_still_matches && ! $row_still_matches;
	}

	wp_cache_delete( $post_id, 'post_meta' );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors WordPress core's post-delete metadata action.
	do_action( 'deleted_post_meta', $meta_ids, $post_id, $meta_key, $logical_meta_value );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors WordPress core's legacy post-delete postmeta action.
	do_action( 'deleted_postmeta', $meta_ids );

	return true;
}


/**
 * Returns the bounded advisory-lock name for one site's ordinary provider metadata owner.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Provider postmeta key.
 * @return string Site/post/key-scoped lock name, or an empty string for invalid input.
 */
function ai4seo_get_third_party_postmeta_only_if_empty_lock_name( int $post_id, string $meta_key ): string {
	global $wpdb;

	$post_id        = absint( $post_id );
	$postmeta_table = isset( $wpdb->postmeta ) ? (string) $wpdb->postmeta : '';

	if ( $post_id <= 0 || '' === $meta_key || '' === $postmeta_table ) {
		return '';
	}

	$database_name = defined( 'DB_NAME' ) ? (string) DB_NAME : '';
	$scope         = $database_name . '|' . $postmeta_table . '|' . absint( get_current_blog_id() ) . '|' . $post_id . '|' . $meta_key;

	// MySQL and MariaDB limit advisory-lock names to 64 bytes; the hash also keeps provider keys private.
	return 'ai4seo_meta_' . substr( hash( 'sha256', $scope ), 0, 52 );
}


/**
 * Writes an ordinary provider postmeta value without overwriting any concurrent or duplicate owner.
 *
 * @param int    $post_id        Post ID.
 * @param string $meta_key       Provider postmeta key.
 * @param string $metadata_value Requested provider metadata value.
 * @return array{write_attempted: bool, write_succeeded: bool, skipped_existing: bool, write_reached_requested_state: bool}
 */
function ai4seo_update_empty_third_party_postmeta(
	int $post_id,
	string $meta_key,
	string $metadata_value
): array {
	global $wpdb;

	$write_result = ai4seo_build_third_party_seo_plugin_metadata_write_result();
	$post_id      = absint( $post_id );

	if ( $post_id <= 0 || ! get_post( $post_id ) ) {
		return $write_result;
	}

	// Match update_post_meta() by applying ordinary provider writes to a revision's parent post.
	$revision_parent_post_id = wp_is_post_revision( $post_id );

	if ( $revision_parent_post_id ) {
		$post_id = absint( $revision_parent_post_id );
	}

	for ( $write_attempt = 0; $write_attempt < 3; ++$write_attempt ) {
		$read_succeeded = false;
		$current_rows   = ai4seo_read_exact_third_party_postmeta_rows( $post_id, $meta_key, $read_succeeded );

		if ( ! $read_succeeded ) {
			return $write_result;
		}

		if ( count( $current_rows ) > 1 ) {
			foreach ( $current_rows as $current_row ) {
				if ( ! ai4seo_is_third_party_seo_metadata_value_literally_empty( $current_row['meta_value'] ) ) {
					$write_result['write_succeeded']  = true;
					$write_result['skipped_existing'] = true;
					return $write_result;
				}
			}

			// Multiple empty rows have no unambiguous canonical owner and must remain untouched.
			return $write_result;
		}

		if ( $current_rows ) {
			$current_row = $current_rows[0];

			if ( ! ai4seo_is_third_party_seo_metadata_value_literally_empty( $current_row['meta_value'] ) ) {
				$write_result['write_succeeded']  = true;
				$write_result['skipped_existing'] = true;
				return $write_result;
			}

			$write_result['write_attempted'] = true;
			$compare_and_swap_result         = ai4seo_compare_and_swap_empty_third_party_postmeta_row(
				$current_row['meta_id'],
				$post_id,
				$meta_key,
				$current_row['meta_value'],
				$metadata_value
			);

			if ( true === $compare_and_swap_result ) {
				$write_result['write_succeeded']               = true;
				$write_result['write_reached_requested_state'] = true;
				return $write_result;
			}

			if ( null === $compare_and_swap_result ) {
				return $write_result;
			}

			continue;
		}

		$lock_name = ai4seo_get_third_party_postmeta_only_if_empty_lock_name( $post_id, $meta_key );

		if ( '' === $lock_name || ! ai4seo_acquire_database_advisory_lock( $lock_name ) ) {
			return $write_result;
		}

		$locked_read_succeeded            = false;
		$locked_rows                      = array();
		$add_result                       = false;
		$database_error                   = '';
		$lock_released                    = false;
		$add_was_filter_owned             = false;
		$add_was_verified                 = false;
		$owned_added_meta_id              = 0;
		$owned_added_raw_value            = '';
		$owned_added_value                = null;
		$compensation_succeeded           = false;
		$post_compensation_read_succeeded = false;
		$post_compensation_rows           = array();

		try {
			// The pre-lock snapshot is advisory only. Re-read under the site/post/key fence before creating an owner.
			$locked_rows = ai4seo_read_exact_third_party_postmeta_rows( $post_id, $meta_key, $locked_read_succeeded );

			if ( $locked_read_succeeded && ! $locked_rows ) {
				// Capture a filter-owned short-circuit separately from a real core insert and its exact value.
				$add_filter_was_short_circuited = false;
				$captured_added_rows            = array();
				$capture_add_filter_result      = static function ( $check, int $object_id, string $added_meta_key ) use ( $post_id, $meta_key, &$add_filter_was_short_circuited ) {
					if ( $post_id === $object_id && hash_equals( $meta_key, $added_meta_key ) && null !== $check ) {
						$add_filter_was_short_circuited = true;
					}

					return $check;
				};
				$capture_added_row              = static function ( int $meta_id, int $object_id, string $added_meta_key, $added_meta_value ) use ( $post_id, $meta_key, &$captured_added_rows ): void {
					if ( $post_id !== $object_id || ! hash_equals( $meta_key, $added_meta_key ) ) {
						return;
					}

					$added_raw_value = maybe_serialize( $added_meta_value );

					if ( ! is_string( $added_raw_value ) ) {
						return;
					}

					$captured_added_rows[ $meta_id ] = array(
						'logical_value' => $added_meta_value,
						'raw_value'     => $added_raw_value,
					);
				};

				add_filter( 'add_post_metadata', $capture_add_filter_result, PHP_INT_MAX, 5 );
				add_action( 'added_post_meta', $capture_added_row, PHP_INT_MAX, 4 );

				// Core's metadata API preserves its add filters/actions while the advisory lock serializes cooperating creators.
				$write_result['write_attempted'] = true;
				$previous_suppress_errors        = $wpdb->suppress_errors( true );
				$wpdb->last_error                = '';

				try {
					try {
						$add_result     = add_post_meta( $post_id, $meta_key, wp_slash( $metadata_value ), true );
						$database_error = (string) $wpdb->last_error;
					} finally {
						remove_filter( 'add_post_metadata', $capture_add_filter_result, PHP_INT_MAX );
						remove_action( 'added_post_meta', $capture_added_row, PHP_INT_MAX );
					}
				} finally {
					$wpdb->suppress_errors( $previous_suppress_errors );
				}

				if ( is_int( $add_result ) && $add_result > 0 && isset( $captured_added_rows[ $add_result ] ) ) {
					$owned_added_meta_id   = $add_result;
					$owned_added_raw_value = $captured_added_rows[ $add_result ]['raw_value'];
					$owned_added_value     = $captured_added_rows[ $add_result ]['logical_value'];
				} elseif ( $add_filter_was_short_circuited && '' === $database_error && false !== $add_result ) {
					// WordPress treats a non-false add_post_metadata short-circuit as authoritative success.
					$add_was_filter_owned                          = true;
					$write_result['write_reached_requested_state'] = true;
				}

				if ( $owned_added_meta_id > 0 ) {
					$verification_succeeded = false;
					$verification_rows      = ai4seo_read_exact_third_party_postmeta_rows( $post_id, $meta_key, $verification_succeeded );

					$add_was_verified = $verification_succeeded
						&& 1 === count( $verification_rows )
						&& $owned_added_meta_id === $verification_rows[0]['meta_id']
						&& hash_equals( $owned_added_raw_value, $verification_rows[0]['meta_value'] );

					if ( $add_was_verified ) {
						$write_result['write_reached_requested_state'] = true;
					} else {
						$compensation_succeeded = ai4seo_delete_owned_third_party_postmeta_row(
							$owned_added_meta_id,
							$post_id,
							$meta_key,
							$owned_added_value,
							$owned_added_raw_value
						);

						if ( $compensation_succeeded ) {
							// Re-read while still fenced so the next attempt can classify the untouched external winner.
							$post_compensation_rows = ai4seo_read_exact_third_party_postmeta_rows(
								$post_id,
								$meta_key,
								$post_compensation_read_succeeded
							);
						}
					}
				}
			}
		} finally {
			$lock_released = ai4seo_release_database_advisory_lock( $lock_name );
		}

		if ( ! $lock_released ) {
			ai4seo_debug_message( 984321709, 'Could not release the ordinary-provider postmeta advisory lock.', true );
			$write_result['write_succeeded']  = false;
			$write_result['skipped_existing'] = false;
			return $write_result;
		}

		if ( ! $locked_read_succeeded ) {
			return $write_result;
		}

		if ( $locked_rows ) {
			// A cooperating creator won after the first snapshot. Re-evaluate its exact row without writing.
			continue;
		}

		if ( '' !== $database_error ) {
			ai4seo_debug_message( 984321708, 'Database error during unique third-party postmeta add: ' . $database_error, true );
			return $write_result;
		}

		if ( $add_was_filter_owned || $add_was_verified ) {
			$write_result['write_succeeded'] = true;
			return $write_result;
		}

		if ( $owned_added_meta_id > 0 ) {
			if ( ! $compensation_succeeded || ! $post_compensation_read_succeeded ) {
				return $write_result;
			}

			if ( count( $post_compensation_rows ) > 1 ) {
				foreach ( $post_compensation_rows as $post_compensation_row ) {
					if ( ! ai4seo_is_third_party_seo_metadata_value_literally_empty( $post_compensation_row['meta_value'] ) ) {
						$write_result['write_succeeded']  = true;
						$write_result['skipped_existing'] = true;
						return $write_result;
					}
				}

				return $write_result;
			}

			if ( $post_compensation_rows
				&& ! ai4seo_is_third_party_seo_metadata_value_literally_empty( $post_compensation_rows[0]['meta_value'] ) ) {
				$write_result['write_succeeded']  = true;
				$write_result['skipped_existing'] = true;
				return $write_result;
			}

			// The owned row is gone and no populated foreign winner exists; retry missing or empty state normally.
			continue;
		}
	}

	return $write_result;
}


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
	$write_result  = ai4seo_build_third_party_seo_plugin_metadata_write_result();
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

	// Capability-driven suppression keeps outbound writes from re-entering the generic inbound mirror.
	$supports_inbound_postmeta_sync = ai4seo_does_third_party_seo_plugin_support_inbound_postmeta_sync(
		$third_party_seo_plugin_details
	);

	// The inbound hook must not interpret a SOOZ-originated write as a separate editor change.
	if ( $supports_inbound_postmeta_sync ) {
		ai4seo_manage_third_party_seo_metadata_sync_request_state( 'begin-outbound', $post_id );
	}

	// The finally block guarantees request-local suppression cannot leak after any write outcome.
	try {
		if ( $only_if_empty ) {
			$postmeta_write_result = ai4seo_update_empty_third_party_postmeta(
				$post_id,
				$third_party_postmeta_key,
				$metadata_value
			);
		} else {
			$postmeta_write_result                                  = ai4seo_build_third_party_seo_plugin_metadata_write_result( true );
			$postmeta_write_result['write_succeeded']               = ai4seo_update_post_meta( $post_id, $third_party_postmeta_key, $metadata_value );
			$postmeta_write_result['write_reached_requested_state'] = $postmeta_write_result['write_succeeded'];
		}
	} finally {
		// Pair suppression with every attempted write, including writes that throw before returning a result.
		if ( $supports_inbound_postmeta_sync ) {
			ai4seo_manage_third_party_seo_metadata_sync_request_state( 'end-outbound', $post_id );
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
	$aioseo_write_attempted               = $postmeta_write_result['write_attempted'] || $aioseo_table_write_result['write_attempted'];
	$aioseo_write_succeeded               = $postmeta_write_result['write_succeeded'] && $aioseo_table_write_result['write_succeeded'];
	$aioseo_write_reached_requested_state = $postmeta_write_result['write_reached_requested_state']
		|| $aioseo_table_write_result['write_reached_requested_state'];

	return ai4seo_build_third_party_seo_plugin_metadata_write_result(
		$aioseo_write_attempted,
		$aioseo_write_succeeded,
		$postmeta_write_result['skipped_existing'],
		$aioseo_write_reached_requested_state
	);
}


/**
 * Updates configured third-party SEO plugins and reports synchronization details.
 *
 * @param int       $post_id                 Post ID.
 * @param string    $metadata_identifier     Metadata identifier.
 * @param string    $metadata_value          Metadata value.
 * @param bool      $overwrite_existing_data Whether existing data should be overwritten.
 * @param bool|null $sync_may_have_committed Receives whether any configured provider write was attempted or interrupted.
 * @return array{skip_own_metadata: bool, sync_attempted: bool, sync_succeeded: bool, sync_reached_requested_state: bool, failed_plugin_identifiers: array}
 */
function ai4seo_update_third_party_seo_plugins_metadata(
	int $post_id,
	string $metadata_identifier,
	string $metadata_value,
	bool $overwrite_existing_data,
	?bool &$sync_may_have_committed = null
): array {
	$sync_may_have_committed = false;

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
		$prior_provider_may_have_committed = $sync_may_have_committed;
		$sync_may_have_committed           = true;
		$this_plugin_write_result          = ai4seo_update_one_third_party_seo_plugin_metadata(
			$post_id,
			$this_third_party_seo_plugin_identifier,
			$this_third_party_seo_plugin_details,
			$metadata_identifier,
			$metadata_value,
			$overwrite_existing_data
		);
		$sync_may_have_committed           = $prior_provider_may_have_committed
			|| $this_plugin_write_result['write_attempted']
			|| $this_plugin_write_result['write_reached_requested_state'];

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
	$write_result                = ai4seo_build_third_party_seo_plugin_metadata_write_result();
	$metadata_identifier_mapping = ai4seo_get_squirrly_seo_metadata_identifier_mapping();
	$squirrly_seo_key            = $metadata_identifier_mapping[ $metadata_identifier ] ?? '';

	if ( ! $squirrly_seo_key ) {
		return $write_result;
	}

	$post_id = absint( $post_id );

	if ( $post_id <= 0 ) {
		return $write_result;
	}

	$url_hash_read_succeeded = false;
	$url_hash_candidates     = ai4seo_read_squirrly_url_hash_candidates_by_post_ids( array( $post_id ), $url_hash_read_succeeded );
	$current_blog_id         = ai4seo_normalize_database_id( get_current_blog_id() );

	if ( ! $url_hash_read_succeeded || ! $url_hash_candidates || false === $current_blog_id ) {
		return $write_result;
	}

	global $wpdb;

	$requested_metadata_value = sanitize_text_field( $metadata_value );

	for ( $write_attempt = 0; $write_attempt < 3; ++$write_attempt ) {
		$squirrly_read_query = ai4seo_prepare_database_query(
			'SELECT blog_id, url_hash, post, seo
			FROM {{squirrly_table}}
			WHERE blog_id = {{blog_id}}
			AND url_hash IN ({{url_hashes}})
			LIMIT {{row_limit}}',
			array(
				'squirrly_table' => ai4seo_database_identifier_binding( 'table.squirrly' ),
				'blog_id'        => ai4seo_database_scalar_binding( '%d', $current_blog_id ),
				'url_hashes'     => ai4seo_database_list_binding( '%s', array_keys( $url_hash_candidates ) ),
				'row_limit'      => ai4seo_database_scalar_binding( '%d', 2 ),
			)
		);

		if ( false === $squirrly_read_query ) {
			return $write_result;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares this bounded provider-owned snapshot through Squirrly's site/hash index; exact raw columns are required for compare-and-swap.
		$current_squirrly_rows = $wpdb->get_results( $squirrly_read_query, ARRAY_A );

		if ( $wpdb->last_error || ! is_array( $current_squirrly_rows ) ) {
			ai4seo_debug_message( 984321683, 'Database error: ' . $wpdb->last_error, true );
			return $write_result;
		}

		// A missing or ambiguous provider identity cannot be updated without risking another row.
		if ( 1 !== count( $current_squirrly_rows ) ) {
			return $write_result;
		}

		$current_squirrly_row = $current_squirrly_rows[0];

		if ( ! is_array( $current_squirrly_row )
			|| 4 !== count( $current_squirrly_row )
			|| ! array_key_exists( 'blog_id', $current_squirrly_row )
			|| ! array_key_exists( 'url_hash', $current_squirrly_row )
			|| ! array_key_exists( 'post', $current_squirrly_row )
			|| ! array_key_exists( 'seo', $current_squirrly_row )
		) {
			return $write_result;
		}

		$row_blog_id      = ai4seo_normalize_database_id( $current_squirrly_row['blog_id'] );
		$current_url_hash = $current_squirrly_row['url_hash'];
		$current_post_raw = $current_squirrly_row['post'];
		$current_seo_raw  = $current_squirrly_row['seo'];

		if ( $current_blog_id !== $row_blog_id
			|| ! is_string( $current_url_hash )
			|| 1 !== preg_match( '/^[a-f0-9]{32}$/', $current_url_hash )
			|| ! isset( $url_hash_candidates[ $current_url_hash ][ $post_id ] )
			|| ! is_string( $current_post_raw )
			|| ( null !== $current_seo_raw && ! is_string( $current_seo_raw ) )
		) {
			return $write_result;
		}

		$current_squirrly_post_values = ai4seo_safe_maybe_unserialize( $current_post_raw );
		$current_post_id              = is_array( $current_squirrly_post_values )
			? ai4seo_normalize_database_id( $current_squirrly_post_values['ID'] ?? null )
			: false;

		if ( $post_id !== $current_post_id ) {
			return $write_result;
		}

		$squirrly_decoding_failed = false;
		$current_squirrly_values  = ai4seo_decode_squirrly_seo_values( $current_seo_raw, $squirrly_decoding_failed );

		if ( $squirrly_decoding_failed ) {
			return $write_result;
		}

		$current_metadata_value = $current_squirrly_values[ $squirrly_seo_key ] ?? null;

		if ( $only_if_empty && ! ai4seo_is_third_party_seo_metadata_value_literally_empty( $current_metadata_value ) ) {
			$write_result['write_succeeded']  = true;
			$write_result['skipped_existing'] = true;
			return $write_result;
		}

		$current_squirrly_values[ $squirrly_seo_key ] = $requested_metadata_value;
		$replacement_seo_raw                          = maybe_serialize( $current_squirrly_values );
		$write_result['write_attempted']              = true;

		if ( $replacement_seo_raw === $current_seo_raw ) {
			$write_result['write_succeeded']               = true;
			$write_result['write_reached_requested_state'] = true;
			return $write_result;
		}

		// Share stable row identity and replacement bindings across the nullable CAS variants.
		$squirrly_update_bindings = array(
			'squirrly_table' => ai4seo_database_identifier_binding( 'table.squirrly' ),
			'seo_value'      => ai4seo_database_scalar_binding( '%s', $replacement_seo_raw ),
			'blog_id'        => ai4seo_database_scalar_binding( '%d', $current_blog_id ),
			'url_hash'       => ai4seo_database_scalar_binding( '%s', $current_url_hash ),
			'post_value'     => ai4seo_database_scalar_binding( '%s', $current_post_raw ),
		);

		if ( null === $current_seo_raw ) {
			$squirrly_update_query_template = 'UPDATE {{squirrly_table}} SET seo = {{seo_value}} WHERE blog_id = {{blog_id}} AND url_hash = {{url_hash}} AND BINARY post = BINARY {{post_value}} AND seo IS NULL LIMIT {{row_limit}}';
		} else {
			$squirrly_update_bindings['previous_seo_value'] = ai4seo_database_scalar_binding( '%s', $current_seo_raw );
			$squirrly_update_query_template                 = 'UPDATE {{squirrly_table}} SET seo = {{seo_value}} WHERE blog_id = {{blog_id}} AND url_hash = {{url_hash}} AND BINARY post = BINARY {{post_value}} AND BINARY seo = BINARY {{previous_seo_value}} LIMIT {{row_limit}}';
		}

		$squirrly_update_bindings['row_limit'] = ai4seo_database_scalar_binding( '%d', 1 );
		$squirrly_update_query                 = ai4seo_prepare_database_query( $squirrly_update_query_template, $squirrly_update_bindings );

		if ( false === $squirrly_update_query ) {
			return $write_result;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler binds indexed site/hash identity plus the exact raw owner and snapshot; a zero-row result is retried from current storage.
		$query_result = $wpdb->query( $squirrly_update_query );

		if ( false === $query_result || $wpdb->last_error ) {
			ai4seo_debug_message( 984321684, 'Database error: ' . $wpdb->last_error, true );
			return $write_result;
		}

		if ( 1 === $query_result ) {
			$write_result['write_succeeded']               = true;
			$write_result['write_reached_requested_state'] = true;
			return $write_result;
		}
	}

	return $write_result;
}


/**
 * Deletes only a Slim SEO postmeta row whose stable identity and raw value are still operation-owned.
 *
 * @param int    $meta_id        Stable postmeta row ID returned by the operation's add.
 * @param int    $post_id        Owning post ID.
 * @param string $meta_key       Slim SEO's compound postmeta key.
 * @param string $raw_meta_value Exact raw value observed after the operation's add.
 * @return bool True when cleanup completed or ownership had already changed, false on failure.
 */
function ai4seo_delete_owned_slim_seo_postmeta_row( int $meta_id, int $post_id, string $meta_key, string $raw_meta_value ): bool {
	global $wpdb;

	$delete_query = ai4seo_prepare_database_query(
		'DELETE FROM {{postmeta_table}} WHERE meta_id = {{meta_id}} AND post_id = {{post_id}} AND meta_key = {{meta_key}} AND BINARY meta_value = BINARY {{meta_value}} LIMIT {{row_limit}}',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'meta_id'        => ai4seo_database_scalar_binding( '%d', $meta_id ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Primary meta_id and post owner establish identity; the provider key prevents cross-contract cleanup.
			'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Exact raw bytes prove the row still contains this operation's inserted value before rollback.
			'meta_value'     => ai4seo_database_scalar_binding( '%s', $raw_meta_value ),
			'row_limit'      => ai4seo_database_scalar_binding( '%d', 1 ),
		)
	);

	if ( false === $delete_query ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler binds a stable row ID, owner, key, and exact operation-owned raw bytes.
	$delete_result = $wpdb->query( $delete_query );

	if ( false === $delete_result || $wpdb->last_error ) {
		ai4seo_debug_message( 146829301, 'Database error: ' . $wpdb->last_error, true );
		return false;
	}

	if ( 1 === $delete_result ) {
		wp_cache_delete( $post_id, 'post_meta' );
	}

	return true;
}


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
	global $wpdb;

	// Missing mappings retain an explicit non-attempted failure result.
	$write_result = ai4seo_build_third_party_seo_plugin_metadata_write_result();

	// Reuse the integration registry so inbound hooks and outbound writes share one field mapping.
	$third_party_seo_plugins = ai4seo_get_third_party_seo_plugin_details();
	$compound_sync_details   = ai4seo_get_third_party_seo_plugin_compound_postmeta_sync_details(
		$third_party_seo_plugins[ AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO ] ?? array()
	);
	$slim_seo_postmeta_key   = $compound_sync_details['postmeta_key'] ?? '';
	$metadata_array_keys     = $compound_sync_details['generation_field_array_keys'] ?? array();
	$slim_seo_key            = $metadata_array_keys[ $metadata_identifier ] ?? '';

	if ( ! $slim_seo_postmeta_key || ! $slim_seo_key ) {
		return $write_result;
	}

	$requested_metadata_value  = sanitize_text_field( $metadata_value );
	$operation_owned_meta_id   = 0;
	$operation_owned_raw_value = '';
	$update_filter_was_applied = false;

	try {
		for ( $write_attempt = 0; $write_attempt < 3; ++$write_attempt ) {
			$slim_seo_read_query = ai4seo_prepare_database_query(
				'SELECT meta_id, meta_value FROM {{postmeta_table}} WHERE post_id = {{post_id}} AND meta_key = {{meta_key}} ORDER BY meta_id ASC LIMIT {{row_limit}}',
				array(
					'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
					'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The exact provider key is paired with one post owner, primary-key ordering, and a two-row ambiguity limit.
					'meta_key'       => ai4seo_database_scalar_binding( '%s', $slim_seo_postmeta_key ),
					'row_limit'      => ai4seo_database_scalar_binding( '%d', 2 ),
				)
			);

			if ( false === $slim_seo_read_query ) {
				return $write_result;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- This typed raw snapshot bypasses eager metadata unserialization and supplies a stable meta_id for compare-and-swap.
			$current_slim_seo_rows = $wpdb->get_results( $slim_seo_read_query, ARRAY_A );

			if ( $wpdb->last_error || ! is_array( $current_slim_seo_rows ) ) {
				ai4seo_debug_message( 146829301, 'Database error: ' . $wpdb->last_error, true );
				return $write_result;
			}

			if ( count( $current_slim_seo_rows ) > 1 ) {
				// If this operation lost a concurrent add race, discard only its exact row and merge the winner.
				foreach ( $current_slim_seo_rows as $ambiguous_slim_seo_row ) {
					$ambiguous_meta_id = ai4seo_normalize_database_id( $ambiguous_slim_seo_row['meta_id'] ?? null );

					if ( false === $ambiguous_meta_id ) {
						return $write_result;
					}

					if ( $ambiguous_meta_id === $operation_owned_meta_id
						&& ( ! is_string( $ambiguous_slim_seo_row['meta_value'] ?? null )
							|| ! hash_equals( $operation_owned_raw_value, $ambiguous_slim_seo_row['meta_value'] ) ) ) {
						// A hook or concurrent writer replaced the inserted bytes, so the row is no longer ours to roll back.
						$operation_owned_meta_id   = 0;
						$operation_owned_raw_value = '';
					}
				}

				if ( $operation_owned_meta_id > 0
					&& ! ai4seo_delete_owned_slim_seo_postmeta_row( $operation_owned_meta_id, $post_id, $slim_seo_postmeta_key, $operation_owned_raw_value ) ) {
					return $write_result;
				}

				if ( $operation_owned_meta_id <= 0 ) {
					// Pre-existing duplicate provider rows have no unambiguous WordPress metadata identity.
					return $write_result;
				}

				$operation_owned_meta_id   = 0;
				$operation_owned_raw_value = '';
				continue;
			}

			if ( ! $current_slim_seo_rows ) {
				// A previously owned row disappeared independently, so no cleanup responsibility remains.
				$operation_owned_meta_id         = 0;
				$operation_owned_raw_value       = '';
				$replacement_slim_seo_values     = array( $slim_seo_key => $requested_metadata_value );
				$write_result['write_attempted'] = true;

				if ( ! $update_filter_was_applied ) {
					$update_filter_was_applied = true;
					$update_check              = apply_filters(
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This intentionally preserves WordPress core's update_post_metadata short-circuit contract.
						'update_post_metadata',
						null,
						$post_id,
						$slim_seo_postmeta_key,
						$replacement_slim_seo_values,
						''
					);

					if ( null !== $update_check ) {
						if ( ! $update_check ) {
							return $write_result;
						}

						$write_result['write_succeeded']               = true;
						$write_result['write_reached_requested_state'] = true;
						return $write_result;
					}
				}

				$captured_meta_id        = 0;
				$captured_raw_meta_value = '';
				$capture_added_row       = static function ( $meta_id, $object_id, $meta_key, $meta_value ) use ( $post_id, $slim_seo_postmeta_key, &$captured_meta_id, &$captured_raw_meta_value ): void {
					if ( (int) $object_id !== $post_id || $meta_key !== $slim_seo_postmeta_key || ! is_numeric( $meta_id ) ) {
						return;
					}

					$captured_meta_id        = (int) $meta_id;
					$captured_raw_meta_value = maybe_serialize( $meta_value );
				};

				// Capture core's exact sanitized insertion payload before later added-post-meta hooks can mutate the row.
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This temporary observer mirrors WordPress core's added_post_meta action contract.
				add_action( 'added_post_meta', $capture_added_row, PHP_INT_MIN, 4 );

				try {
					$added_meta_id = add_post_meta( $post_id, $slim_seo_postmeta_key, $replacement_slim_seo_values, true );
				} finally {
					remove_action( 'added_post_meta', $capture_added_row, PHP_INT_MIN );
				}

				if ( is_int( $added_meta_id ) && $added_meta_id > 0 && $captured_meta_id === $added_meta_id ) {
					$operation_owned_meta_id   = $captured_meta_id;
					$operation_owned_raw_value = $captured_raw_meta_value;
					continue;
				}

				if ( false !== $added_meta_id ) {
					// A metadata add short-circuit or unobservable provider insert is authoritative but never rollback-owned.
					$write_result['write_succeeded']               = true;
					$write_result['write_reached_requested_state'] = true;
					return $write_result;
				}

				if ( $wpdb->last_error ) {
					ai4seo_debug_message( 146829301, 'Database error: ' . $wpdb->last_error, true );
					return $write_result;
				}

				// A concurrent creator can make the unique add lose without a database error; re-read and merge it.
				continue;
			}

			$current_slim_seo_row       = $current_slim_seo_rows[0];
			$current_slim_seo_meta_id   = ai4seo_normalize_database_id( $current_slim_seo_row['meta_id'] ?? null );
			$current_slim_seo_raw_value = $current_slim_seo_row['meta_value'] ?? null;

			if ( false === $current_slim_seo_meta_id
				|| ( null !== $current_slim_seo_raw_value && ! is_string( $current_slim_seo_raw_value ) ) ) {
				return $write_result;
			}

			if ( $operation_owned_meta_id > 0 && $current_slim_seo_meta_id !== $operation_owned_meta_id ) {
				// Another actor removed this operation's row and supplied the current stable winner.
				$operation_owned_meta_id   = 0;
				$operation_owned_raw_value = '';
			} elseif ( $operation_owned_meta_id > 0
				&& ( ! is_string( $current_slim_seo_raw_value )
					|| ! hash_equals( $operation_owned_raw_value, $current_slim_seo_raw_value ) ) ) {
				// Never adopt bytes changed by an add hook or another writer as this operation's rollback predicate.
				$operation_owned_meta_id   = 0;
				$operation_owned_raw_value = '';
			}

			$slim_seo_decoding_failed = false;
			$current_slim_seo_values  = ai4seo_decode_slim_seo_values( $current_slim_seo_raw_value, $slim_seo_decoding_failed );

			if ( $slim_seo_decoding_failed ) {
				ai4seo_debug_message( 146829302, 'Unsafe or invalid serialized Slim SEO metadata prevented an update.', true );
				return $write_result;
			}

			$current_metadata_value = $current_slim_seo_values[ $slim_seo_key ] ?? null;

			if ( $only_if_empty
				&& $operation_owned_meta_id <= 0
				&& ! ai4seo_is_third_party_seo_metadata_value_literally_empty( $current_metadata_value ) ) {
				$write_result['write_succeeded']  = true;
				$write_result['skipped_existing'] = true;
				return $write_result;
			}

			$current_slim_seo_values[ $slim_seo_key ] = $requested_metadata_value;
			$write_result['write_attempted']          = true;

			if ( ! $update_filter_was_applied ) {
				$update_filter_was_applied = true;
				$update_check              = apply_filters(
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This intentionally preserves WordPress core's update_post_metadata short-circuit contract.
					'update_post_metadata',
					null,
					$post_id,
					$slim_seo_postmeta_key,
					$current_slim_seo_values,
					''
				);

				if ( null !== $update_check ) {
					if ( ! $update_check ) {
						return $write_result;
					}

					// WordPress treats a truthy short-circuit as the authoritative successful result.
					$write_result['write_succeeded']               = true;
					$write_result['write_reached_requested_state'] = true;
					$operation_owned_meta_id                       = 0;
					return $write_result;
				}
			}

			$meta_subtype                = get_object_subtype( 'post', $post_id );
			$replacement_slim_seo_values = sanitize_meta( $slim_seo_postmeta_key, $current_slim_seo_values, 'post', $meta_subtype );
			$replacement_decoding_failed = false;
			$replacement_slim_seo_values = ai4seo_decode_slim_seo_values( $replacement_slim_seo_values, $replacement_decoding_failed );

			if ( $replacement_decoding_failed || ! $replacement_slim_seo_values ) {
				return $write_result;
			}

			$replacement_slim_seo_raw_value = maybe_serialize( $replacement_slim_seo_values );

			if ( $replacement_slim_seo_raw_value === $current_slim_seo_raw_value ) {
				$write_result['write_succeeded']               = true;
				$write_result['write_reached_requested_state'] = true;
				$operation_owned_meta_id                       = 0;
				return $write_result;
			}

			$slim_seo_update_bindings = array(
				'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- The exact raw value is the compare-and-swap payload and is constrained by primary meta_id, post owner, key, and one-row limit.
				'meta_value'     => ai4seo_database_scalar_binding( '%s', $replacement_slim_seo_raw_value ),
				'meta_id'        => ai4seo_database_scalar_binding( '%d', $current_slim_seo_meta_id ),
				'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The exact provider key supplements the primary meta_id identity and post owner in this compare-and-swap.
				'meta_key'       => ai4seo_database_scalar_binding( '%s', $slim_seo_postmeta_key ),
				'row_limit'      => ai4seo_database_scalar_binding( '%d', 1 ),
			);

			if ( null === $current_slim_seo_raw_value ) {
				$slim_seo_update_query = ai4seo_prepare_database_query(
					'UPDATE {{postmeta_table}} SET meta_value = {{meta_value}} WHERE meta_id = {{meta_id}} AND post_id = {{post_id}} AND meta_key = {{meta_key}} AND meta_value IS NULL LIMIT {{row_limit}}',
					$slim_seo_update_bindings
				);
			} else {
				$slim_seo_update_bindings['previous_meta_value'] = ai4seo_database_scalar_binding( '%s', $current_slim_seo_raw_value );
				$slim_seo_update_query                           = ai4seo_prepare_database_query(
					'UPDATE {{postmeta_table}} SET meta_value = {{meta_value}} WHERE meta_id = {{meta_id}} AND post_id = {{post_id}} AND meta_key = {{meta_key}} AND BINARY meta_value = BINARY {{previous_meta_value}} LIMIT {{row_limit}}',
					$slim_seo_update_bindings
				);
			}

			if ( false === $slim_seo_update_query ) {
				return $write_result;
			}

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Direct CAS preserves WordPress core's pre-update metadata hook contract.
			do_action( 'update_post_meta', $current_slim_seo_meta_id, $post_id, $slim_seo_postmeta_key, $replacement_slim_seo_values );
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Direct CAS preserves WordPress core's legacy pre-update metadata hook contract.
			do_action( 'update_postmeta', $current_slim_seo_meta_id, $post_id, $slim_seo_postmeta_key, $replacement_slim_seo_raw_value );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler binds the stable meta_id, owner, key, and exact raw snapshot; successful writes invalidate WordPress's postmeta cache below.
			$query_result = $wpdb->query( $slim_seo_update_query );

			if ( false === $query_result || $wpdb->last_error ) {
				ai4seo_debug_message( 146829301, 'Database error: ' . $wpdb->last_error, true );
				return $write_result;
			}

			if ( 1 !== $query_result ) {
				continue;
			}

			wp_cache_delete( $post_id, 'post_meta' );
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Direct CAS preserves WordPress core's post-update metadata hook contract after success.
			do_action( 'updated_post_meta', $current_slim_seo_meta_id, $post_id, $slim_seo_postmeta_key, $replacement_slim_seo_values );
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Direct CAS preserves WordPress core's legacy post-update metadata hook contract after success.
			do_action( 'updated_postmeta', $current_slim_seo_meta_id, $post_id, $slim_seo_postmeta_key, $replacement_slim_seo_raw_value );

			$write_result['write_succeeded']               = true;
			$write_result['write_reached_requested_state'] = true;
			$operation_owned_meta_id                       = 0;
			return $write_result;
		}
	} finally {
		if ( $operation_owned_meta_id > 0 ) {
			ai4seo_delete_owned_slim_seo_postmeta_row(
				$operation_owned_meta_id,
				$post_id,
				$slim_seo_postmeta_key,
				$operation_owned_raw_value
			);
		}
	}

	return $write_result;
}


/**
 * Checks whether AIOSEO has initialized canonical storage for a post.
 *
 * @param int $post_id Post ID.
 * @return bool True when AIOSEO owns a row for the post.
 */
function ai4seo_does_all_in_one_seo_post_row_exist( int $post_id ): bool {
	global $wpdb;
	static $row_exists_by_storage_key = array();

	$identifier_registry = ai4seo_get_database_identifier_registry();
	$aioseo_table_name   = $identifier_registry['table.aioseo_posts'] ?? '';
	$current_blog_id     = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0;

	if ( ! is_string( $aioseo_table_name ) || '' === $aioseo_table_name ) {
		return false;
	}

	$storage_key = $current_blog_id . '|' . $aioseo_table_name . '|' . $post_id;

	if ( array_key_exists( $storage_key, $row_exists_by_storage_key ) ) {
		return $row_exists_by_storage_key[ $storage_key ];
	}

	// Read only the canonical identifier because field values are irrelevant to the preflight decision.
	$query = ai4seo_prepare_database_query(
		'SELECT post_id FROM {{aioseo_table}} WHERE post_id = {{post_id}} LIMIT 1',
		array(
			'aioseo_table' => ai4seo_database_identifier_binding( 'table.aioseo_posts' ),
			'post_id'      => ai4seo_database_scalar_binding( '%d', $post_id ),
		)
	);

	if ( false === $query ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares this provider-table preflight and the request-local static cache immediately below owns reuse; AI for SEO owns no AIOSEO cache.
	$aioseo_post_id = $wpdb->get_var( $query );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 874321686, 'Database error: ' . $wpdb->last_error, true );
		return false;
	}

	if ( null === $aioseo_post_id ) {
		// A provider may create its canonical row later in the same request, so negative probes remain fresh.
		return false;
	}

	$row_exists_by_storage_key[ $storage_key ] = true;

	return true;
}


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

	$aioseo_column_name = $metadata_identifier_mapping[ $metadata_identifier ] ?? '';

	if ( ! $aioseo_column_name ) {
		return $write_result;
	}

	$aioseo_read_query_template = ai4seo_get_all_in_one_seo_metadata_query_template( 'read', $aioseo_column_name );

	if ( ! $aioseo_read_query_template ) {
		return $write_result;
	}

	global $wpdb;

	$requested_metadata_value = sanitize_text_field( $metadata_value );

	for ( $write_attempt = 0; $write_attempt < 3; ++$write_attempt ) {
		$aioseo_read_query = ai4seo_prepare_database_query(
			$aioseo_read_query_template,
			array(
				'aioseo_table' => ai4seo_database_identifier_binding( 'table.aioseo_posts' ),
				'post_id'      => ai4seo_database_scalar_binding( '%d', $post_id ),
			)
		);

		if ( false === $aioseo_read_query ) {
			return $write_result;
		}

		// Every attempt re-reads the allowlisted column so a lost comparison can merge from current provider state.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepares this current provider-column snapshot; AI for SEO owns no AIOSEO cache.
		$current_aioseo_row = $wpdb->get_row( $aioseo_read_query, ARRAY_A );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321686, 'Database error: ' . $wpdb->last_error, true );
			return $write_result;
		}

		// AIOSEO owns row creation; an absent or malformed row intentionally remains a silent failure.
		if ( ! is_array( $current_aioseo_row ) || ! array_key_exists( 'metadata_value', $current_aioseo_row ) ) {
			return $write_result;
		}

		$current_metadata_value = $current_aioseo_row['metadata_value'];

		if ( null !== $current_metadata_value && ! is_string( $current_metadata_value ) ) {
			return $write_result;
		}

		if ( $only_if_empty && ! ai4seo_is_third_party_seo_metadata_value_literally_empty( $current_metadata_value ) ) {
			$write_result['write_succeeded']  = true;
			$write_result['skipped_existing'] = true;
			return $write_result;
		}

		$write_result['write_attempted'] = true;

		if ( $requested_metadata_value === $current_metadata_value ) {
			$write_result['write_succeeded']               = true;
			$write_result['write_reached_requested_state'] = true;
			return $write_result;
		}

		$update_operation             = null === $current_metadata_value ? 'update-null' : 'update-value';
		$aioseo_update_query_template = ai4seo_get_all_in_one_seo_metadata_query_template( $update_operation, $aioseo_column_name );

		if ( ! $aioseo_update_query_template ) {
			return $write_result;
		}

		$aioseo_update_bindings = array(
			'aioseo_table'   => ai4seo_database_identifier_binding( 'table.aioseo_posts' ),
			'metadata_value' => ai4seo_database_scalar_binding( '%s', $requested_metadata_value ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
		);

		if ( null !== $current_metadata_value ) {
			$aioseo_update_bindings['previous_metadata_value'] = ai4seo_database_scalar_binding( '%s', $current_metadata_value );
		}

		$aioseo_update_bindings['row_limit'] = ai4seo_database_scalar_binding( '%d', 1 );
		$aioseo_update_query                 = ai4seo_prepare_database_query( $aioseo_update_query_template, $aioseo_update_bindings );

		if ( false === $aioseo_update_query ) {
			return $write_result;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler binds the stable provider row identity and exact prior column bytes; a zero-row comparison miss is retried from current storage.
		$query_result = $wpdb->query( $aioseo_update_query );

		if ( false === $query_result || $wpdb->last_error ) {
			ai4seo_debug_message( 984321687, 'Database error: ' . $wpdb->last_error, true );
			return $write_result;
		}

		if ( 1 === $query_result ) {
			$write_result['write_succeeded']               = true;
			$write_result['write_reached_requested_state'] = true;
			return $write_result;
		}
	}

	return $write_result;
}



/**
 * Returns the language of a post / page / product
 *
 * @param int $post_id the post id.
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


/**
 * Return display names for active metadata fields.
 *
 * @param array|null $active_meta_tags Optional active metadata identifiers.
 * @return array Active metadata field display names.
 */
function ai4seo_get_active_meta_tags_names( $active_meta_tags = null ): array {
	if ( null === $active_meta_tags ) {
		$active_meta_tags = ai4seo_get_active_meta_tags();
	}

	$active_meta_tags_names = array();

	foreach ( AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details ) {
		if ( in_array( $ai4seo_this_metadata_identifier, $active_meta_tags, true ) && isset( $ai4seo_this_metadata_details['name'] ) ) {
			$active_meta_tags_names[] = $ai4seo_this_metadata_details['name'];
		}
	}

	return $active_meta_tags_names;
}


// endregion
// ___________________________________________________________________________________________.
