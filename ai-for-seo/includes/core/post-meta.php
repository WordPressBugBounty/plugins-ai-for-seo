<?php
/**
 * Provides direct post-metadata helpers and lookups.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region POST META ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Function to update a post meta if it is empty
 *
 * @param int    $post_id the post id.
 * @param string $meta_key the meta key.
 * @param string $meta_value the meta value.
 * @return bool True if the post meta was updated, false if not
 */
function ai4seo_update_postmeta_if_empty( int $post_id, string $meta_key, string $meta_value ): bool {
	$post_id    = sanitize_key( $post_id );
	$meta_key   = sanitize_key( $meta_key );
	$meta_value = sanitize_textarea_field( $meta_value );

	$current_value = get_post_meta( $post_id, $meta_key, true );

	if ( $current_value ) {
		return false;
	} else {
		return ai4seo_update_post_meta( $post_id, $meta_key, $meta_value );
	}
}


/**
 * Returns the canonical generated-data field identifiers supported by storage.
 *
 * @return string[] Canonical generated-data field identifiers.
 */
function ai4seo_get_supported_generated_data_field_identifiers(): array {
	return array(
		'focus-keyphrase',
		'meta-title',
		'meta-description',
		'keywords',
		'facebook-title',
		'facebook-description',
		'twitter-title',
		'twitter-description',
		'title',
		'alt-text',
		'caption',
		'description',
	);
}


/**
 * Removes generated-data fields that were persisted by obsolete storage contracts.
 *
 * Only the exact former field identifiers and their matching provenance entries are eligible.
 * Every other value remains available to the authoritative decoder's strict validation.
 *
 * @param array $generated_data Decoded generated-data snapshot to repair in place.
 * @return bool Whether the snapshot requires a persistent canonical repair.
 */
function ai4seo_remove_obsolete_generated_data_fields( array &$generated_data ): bool {
	$repair_required                     = false;
	$generated_at_by_field_is_collection = isset( $generated_data['generated_at_by_field'] ) && is_array( $generated_data['generated_at_by_field'] );
	$obsolete_generated_data_fields      = array(
		'language',
		'file-name',
	);

	// Keep this allowlist narrow so every unrelated unknown field still reaches strict validation.
	foreach ( $obsolete_generated_data_fields as $obsolete_generated_data_field ) {
		if ( array_key_exists( $obsolete_generated_data_field, $generated_data ) ) {
			unset( $generated_data[ $obsolete_generated_data_field ] );
			$repair_required = true;
		}

		// Malformed provenance containers remain untouched so the decoder continues to reject them.
		if ( $generated_at_by_field_is_collection && array_key_exists( $obsolete_generated_data_field, $generated_data['generated_at_by_field'] ) ) {
			unset( $generated_data['generated_at_by_field'][ $obsolete_generated_data_field ] );
			$repair_required = true;
		}
	}

	return $repair_required;
}


/**
 * Canonicalizes the pre-2.0.2 shared social field aliases and their provenance.
 *
 * Canonical values and timestamps win when a mixed historical snapshot contains both forms.
 *
 * @param array $generated_data Decoded generated-data snapshot.
 * @return array|false Canonicalized snapshot, or false when an alias shape is malformed.
 */
function ai4seo_canonicalize_legacy_generated_data_field_aliases( array $generated_data ) {
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
	$generated_at_by_field     = $generated_data['generated_at_by_field'] ?? array();

	if ( ! is_array( $generated_at_by_field ) ) {
		return false;
	}

	foreach ( $legacy_alias_destinations as $legacy_alias => $canonical_destinations ) {
		$alias_value_exists     = array_key_exists( $legacy_alias, $generated_data );
		$alias_timestamp_exists = array_key_exists( $legacy_alias, $generated_at_by_field );

		if ( $alias_timestamp_exists
			&& ( ! $alias_value_exists
				|| ! is_int( $generated_at_by_field[ $legacy_alias ] )
				|| $generated_at_by_field[ $legacy_alias ] <= 0
			)
		) {
			return false;
		}

		if ( ! $alias_value_exists ) {
			continue;
		}

		$alias_value = $generated_data[ $legacy_alias ];

		if ( ! is_string( $alias_value ) ) {
			return false;
		}

		foreach ( $canonical_destinations as $canonical_destination ) {
			if ( array_key_exists( $canonical_destination, $generated_data ) ) {
				continue;
			}

			// Historical aliases used the fallback limit; prepare them against each current destination.
			$generated_data[ $canonical_destination ] = ai4seo_prepare_generated_data_field_value( $canonical_destination, $alias_value );

			if ( $alias_timestamp_exists && ! array_key_exists( $canonical_destination, $generated_at_by_field ) ) {
				$generated_at_by_field[ $canonical_destination ] = $generated_at_by_field[ $legacy_alias ];
			}
		}

		unset( $generated_data[ $legacy_alias ], $generated_at_by_field[ $legacy_alias ] );
	}

	if ( array_key_exists( 'generated_at_by_field', $generated_data ) || $generated_at_by_field ) {
		$generated_data['generated_at_by_field'] = $generated_at_by_field;
	}

	return $generated_data;
}


/**
 * Strictly decodes one raw generated-data postmeta value into normalized details.
 *
 * @param mixed     $raw_value Raw database meta_value.
 * @param array     $generated_data_details Receives normalized generated-data details.
 * @param bool|null $repair_required Receives whether obsolete storage fields require persistence repair.
 * @return bool True only for a supported JSON or legacy serialized array value.
 */
function ai4seo_decode_generated_data_postmeta_value_authoritatively(
	$raw_value,
	array &$generated_data_details,
	?bool &$repair_required = null
): bool {
	$generated_data_details = array();
	$repair_required        = false;

	if ( ! is_string( $raw_value ) ) {
		return false;
	}

	$generated_data = json_decode( $raw_value, true );
	$json_was_valid = JSON_ERROR_NONE === json_last_error() && is_array( $generated_data );

	if ( ! $json_was_valid ) {
		$generated_data = ai4seo_safe_maybe_unserialize( $raw_value );

		if ( ! is_array( $generated_data ) ) {
			return false;
		}
	}

	// Remove only the explicitly repairable fields before aliases and current-contract validation run.
	$repair_required = ai4seo_remove_obsolete_generated_data_fields( $generated_data );
	$generated_data  = ai4seo_canonicalize_legacy_generated_data_field_aliases( $generated_data );

	if ( false === $generated_data ) {
		return false;
	}

	$supported_generated_data_fields = ai4seo_get_supported_generated_data_field_identifiers();
	$generated_at_by_field           = $generated_data['generated_at_by_field'] ?? array();

	if ( ! is_array( $generated_at_by_field ) ) {
		return false;
	}

	foreach ( $generated_data as $generated_data_key => $generated_data_value ) {
		if ( ! is_string( $generated_data_key )
			|| '' === $generated_data_key
			|| sanitize_key( $generated_data_key ) !== $generated_data_key
		) {
			return false;
		}

		if ( 'generated_at' === $generated_data_key ) {
			if ( ! is_int( $generated_data_value ) || $generated_data_value <= 0 ) {
				return false;
			}

			continue;
		}

		if ( 'generated_at_by_field' === $generated_data_key ) {
			continue;
		}

		if ( ! in_array( $generated_data_key, $supported_generated_data_fields, true )
			|| ! is_string( $generated_data_value )
			|| ai4seo_prepare_generated_data_field_value( $generated_data_key, $generated_data_value ) !== $generated_data_value
		) {
			return false;
		}

		// Historically stored empty scalar fields are valid but do not represent generated content.
		if ( '' === $generated_data_value ) {
			unset( $generated_data[ $generated_data_key ], $generated_at_by_field[ $generated_data_key ] );
		}
	}

	foreach ( $generated_at_by_field as $generated_field => $generated_timestamp ) {
		if ( ! is_string( $generated_field )
			|| '' === $generated_field
			|| sanitize_key( $generated_field ) !== $generated_field
			|| ! in_array( $generated_field, $supported_generated_data_fields, true )
			|| ! array_key_exists( $generated_field, $generated_data )
			|| ! is_int( $generated_timestamp )
			|| $generated_timestamp <= 0
		) {
			return false;
		}
	}

	if ( array_key_exists( 'generated_at_by_field', $generated_data ) || $generated_at_by_field ) {
		$generated_data['generated_at_by_field'] = $generated_at_by_field;
	}

	$generated_data_details = ai4seo_prepare_generated_data_details( $generated_data );

	return isset( $generated_data_details['generated_data'], $generated_data_details['generated_at'], $generated_data_details['generated_at_by_field'] )
		&& is_array( $generated_data_details['generated_data'] )
		&& is_int( $generated_data_details['generated_at'] )
		&& is_array( $generated_data_details['generated_at_by_field'] );
}


/**
 * Reads one exact generated-data postmeta row and its strict normalized details.
 *
 * Missing storage is a successful snapshot with exists=false. Duplicate rows, database failures,
 * and malformed values fail closed. The previous_value member is shaped for WordPress's exact
 * update_post_meta() previous-value predicate: JSON remains a string and legacy serialization is
 * represented by its decoded array.
 *
 * @param int       $post_id Post ID.
 * @param bool|null $read_succeeded Receives whether exact storage and decoding were authoritative.
 * @return array{exists: bool, meta_id: int, raw_value: string, previous_value: mixed, generated_data_details: array}
 */
function ai4seo_read_authoritative_generated_data_postmeta_snapshot(
	int $post_id,
	?bool &$read_succeeded = null
): array {
	global $wpdb;

	$post_id        = absint( $post_id );
	$read_succeeded = false;
	$empty_snapshot = array(
		'exists'                 => false,
		'meta_id'                => 0,
		'raw_value'              => '',
		'previous_value'         => null,
		'generated_data_details' => array(
			'generated_data'        => array(),
			'generated_at'          => 0,
			'generated_at_by_field' => array(),
		),
	);

	if ( $post_id <= 0 ) {
		return $empty_snapshot;
	}

	$query = ai4seo_prepare_database_query(
		'SELECT `meta_id`, `meta_value`, `meta_key`
		FROM {{postmeta_table}}
		WHERE `post_id` = {{post_id}}
		AND `meta_key` = {{meta_key}}
		ORDER BY `meta_id` ASC
		LIMIT 2',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			'meta_key'       => ai4seo_database_scalar_binding( '%s', AI4SEO_POST_META_GENERATED_DATA_META_KEY ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Exact post ID plus LIMIT 2 bounds the duplicate-owner check.
		)
	);

	if ( false === $query ) {
		return $empty_snapshot;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler owns the exact post/key query; generated-data decisions must bypass possibly stale postmeta caches.
	$rows = $wpdb->get_results( $query, ARRAY_A );

	if ( $wpdb->last_error || ! is_array( $rows ) || count( $rows ) > 1 ) {
		return $empty_snapshot;
	}

	if ( ! $rows ) {
		$read_succeeded = true;
		return $empty_snapshot;
	}

	$row     = reset( $rows );
	$meta_id = is_array( $row ) ? ai4seo_normalize_database_id( $row['meta_id'] ?? null ) : false;

	if ( ! is_array( $row )
		|| false === $meta_id
		|| ! array_key_exists( 'meta_key', $row )
		|| ! is_string( $row['meta_key'] )
		|| ! hash_equals( AI4SEO_POST_META_GENERATED_DATA_META_KEY, $row['meta_key'] )
		|| ! array_key_exists( 'meta_value', $row )
		|| ! is_string( $row['meta_value'] )
	) {
		return $empty_snapshot;
	}

	$generated_data_details = array();

	if ( ! ai4seo_decode_generated_data_postmeta_value_authoritatively( $row['meta_value'], $generated_data_details ) ) {
		return $empty_snapshot;
	}

	$decoded_json   = json_decode( $row['meta_value'], true );
	$json_was_valid = JSON_ERROR_NONE === json_last_error() && is_array( $decoded_json );
	$previous_value = $json_was_valid ? $row['meta_value'] : ai4seo_safe_maybe_unserialize( $row['meta_value'] );

	if ( ! is_string( $previous_value ) && ! is_array( $previous_value ) ) {
		return $empty_snapshot;
	}

	$read_succeeded = true;

	return array(
		'exists'                 => true,
		'meta_id'                => $meta_id,
		'raw_value'              => $row['meta_value'],
		'previous_value'         => $previous_value,
		'generated_data_details' => $generated_data_details,
	);
}


/**
 * Returns the bounded advisory-lock name for one site's generated-data snapshot owner.
 *
 * @param int $post_id Post ID.
 * @return string Site/post/meta-scoped lock name, or an empty string for invalid input.
 */
function ai4seo_get_generated_data_postmeta_lock_name( int $post_id ): string {
	global $wpdb;

	$post_id        = absint( $post_id );
	$postmeta_table = isset( $wpdb->postmeta ) ? (string) $wpdb->postmeta : '';

	if ( $post_id <= 0 || '' === $postmeta_table ) {
		return '';
	}

	$database_name = defined( 'DB_NAME' ) ? (string) DB_NAME : '';
	$scope         = $database_name . '|' . $postmeta_table . '|' . absint( get_current_blog_id() ) . '|' . $post_id . '|' . AI4SEO_POST_META_GENERATED_DATA_META_KEY;

	// MySQL and MariaDB limit advisory-lock names to 64 bytes; the hash isolates database, multisite, post, and key ownership.
	return 'ai4seo_gen_' . substr( hash( 'sha256', $scope ), 0, 52 );
}


/**
 * Reads whether one stable generated-data row still contains exact operation-owned bytes.
 *
 * @param int    $post_id Post ID.
 * @param int    $meta_id Generated-data meta ID.
 * @param string $expected_raw_value Exact raw value owned by the operation.
 * @return bool|null True for an exact match, false for missing/replaced ownership, null on read failure.
 */
function ai4seo_generated_data_postmeta_row_matches_exact_value(
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
		LIMIT 1',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'meta_id'        => ai4seo_database_scalar_binding( '%d', $meta_id ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			'meta_key'       => ai4seo_database_scalar_binding( '%s', AI4SEO_POST_META_GENERATED_DATA_META_KEY ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The primary key and post ID bound this ownership check to one row.
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
 * Reads whether one generated-data owner authoritatively contains exact canonical bytes.
 *
 * @param int    $post_id Post ID.
 * @param int    $meta_id Generated-data meta ID.
 * @param string $expected_raw_value Expected canonical raw value.
 * @return bool|null True for the exact sole owner, false for changed ownership, null on read failure.
 */
function ai4seo_generated_data_postmeta_snapshot_matches_exact_value(
	int $post_id,
	int $meta_id,
	string $expected_raw_value
): ?bool {
	// The authoritative reader also rejects duplicate owners and malformed replacement storage.
	$read_succeeded = false;
	$snapshot       = ai4seo_read_authoritative_generated_data_postmeta_snapshot( $post_id, $read_succeeded );

	if ( ! $read_succeeded ) {
		return null;
	}

	return ! empty( $snapshot['exists'] )
		&& (int) ( $snapshot['meta_id'] ?? 0 ) === absint( $meta_id )
		&& is_string( $snapshot['raw_value'] ?? null )
		&& hash_equals( $expected_raw_value, $snapshot['raw_value'] );
}


/**
 * Replaces an unchanged generated-data row with its canonical representation.
 *
 * The stable primary key, post owner, exact meta key, and raw bytes form a compare-and-swap
 * predicate. A concurrent generated-data writer therefore wins without requiring another lock.
 *
 * @param int    $post_id Post ID.
 * @param int    $meta_id Generated-data meta ID.
 * @param string $expected_raw_value Exact raw value read by the repair operation.
 * @param string $replacement_raw_value Canonical replacement bytes.
 * @return bool|null True when canonical storage is present, false after a lost race, null on failure.
 */
function ai4seo_compare_and_swap_generated_data_postmeta_value(
	int $post_id,
	int $meta_id,
	string $expected_raw_value,
	string $replacement_raw_value
): ?bool {
	global $wpdb;

	// Stable positive identifiers are required before constructing any ownership predicate.
	$post_id = absint( $post_id );
	$meta_id = absint( $meta_id );

	if ( $post_id <= 0 || $meta_id <= 0 || hash_equals( $expected_raw_value, $replacement_raw_value ) ) {
		return null;
	}

	// Independently verify the caller's replacement so this low-level helper cannot store legacy bytes.
	$verified_replacement_details = array();
	$replacement_repair_required  = false;

	if ( ! ai4seo_decode_generated_data_postmeta_value_authoritatively( $replacement_raw_value, $verified_replacement_details, $replacement_repair_required )
		|| $replacement_repair_required
	) {
		return null;
	}

	// Match update_post_meta() sanitization while refusing any hook that changes canonical bytes.
	$meta_subtype                = get_object_subtype( 'post', $post_id );
	$sanitized_replacement_value = sanitize_meta( AI4SEO_POST_META_GENERATED_DATA_META_KEY, $replacement_raw_value, 'post', $meta_subtype );

	if ( ! is_string( $sanitized_replacement_value ) || ! hash_equals( $replacement_raw_value, $sanitized_replacement_value ) ) {
		return null;
	}

	// A metadata short-circuit is accepted only when authoritative storage already proves its result.
	$update_check = apply_filters(
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors WordPress core's authoritative update metadata filter.
		'update_post_metadata',
		null,
		$post_id,
		AI4SEO_POST_META_GENERATED_DATA_META_KEY,
		$replacement_raw_value,
		$expected_raw_value
	);

	if ( null !== $update_check ) {
		$replacement_is_authoritative = ai4seo_generated_data_postmeta_snapshot_matches_exact_value( $post_id, $meta_id, $replacement_raw_value );

		if ( true === $replacement_is_authoritative ) {
			wp_cache_delete( $post_id, 'post_meta' );
		}

		return true === $replacement_is_authoritative ? true : null;
	}

	// The primary key and original raw bytes make the update safe without nesting another advisory lock.
	$query = ai4seo_prepare_database_query(
		'UPDATE {{postmeta_table}}
		SET `meta_value` = {{replacement_meta_value}}
		WHERE `meta_id` = {{meta_id}}
		AND `post_id` = {{post_id}}
		AND BINARY `meta_key` = BINARY {{meta_key}}
		AND BINARY `meta_value` = BINARY {{expected_meta_value}}
		LIMIT {{row_limit}}',
		array(
			'postmeta_table'         => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'replacement_meta_value' => ai4seo_database_scalar_binding( '%s', $replacement_raw_value ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Stable primary-key ownership and exact old bytes bound this one-row repair.
			'meta_id'                => ai4seo_database_scalar_binding( '%d', $meta_id ),
			'post_id'                => ai4seo_database_scalar_binding( '%d', $post_id ),
			'meta_key'               => ai4seo_database_scalar_binding( '%s', AI4SEO_POST_META_GENERATED_DATA_META_KEY ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The primary key and post owner bound the exact key check to one row.
			'expected_meta_value'    => ai4seo_database_scalar_binding( '%s', $expected_raw_value ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- These exact bytes are the CAS predicate for the stable primary-key owner.
			'row_limit'              => ai4seo_database_scalar_binding( '%d', 1 ),
		)
	);

	if ( false === $query ) {
		return null;
	}

	// Direct SQL mirrors the metadata API's pre-update actions before attempting the exact CAS.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Direct CAS mirrors WordPress core's pre-update metadata action.
	do_action( 'update_post_meta', $meta_id, $post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, $replacement_raw_value );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Direct CAS mirrors WordPress core's legacy pre-update metadata action.
	do_action( 'update_postmeta', $meta_id, $post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, $replacement_raw_value );

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler binds the stable owner and exact bytes; successful writes invalidate postmeta cache below.
	$update_result = $wpdb->query( $query );

	if ( false === $update_result || $wpdb->last_error || (int) $update_result > 1 ) {
		return null;
	}

	// A zero-row CAS can still be successful when another repair wrote the identical canonical bytes.
	if ( 0 === (int) $update_result ) {
		$replacement_is_authoritative = ai4seo_generated_data_postmeta_snapshot_matches_exact_value( $post_id, $meta_id, $replacement_raw_value );

		if ( true === $replacement_is_authoritative ) {
			wp_cache_delete( $post_id, 'post_meta' );
		}

		return $replacement_is_authoritative;
	}

	// Only this post's metadata cache and post-update hooks need reconciliation after our own write.
	wp_cache_delete( $post_id, 'post_meta' );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Direct CAS mirrors WordPress core's post-update metadata action.
	do_action( 'updated_post_meta', $meta_id, $post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, $replacement_raw_value );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Direct CAS mirrors WordPress core's legacy post-update metadata action.
	do_action( 'updated_postmeta', $meta_id, $post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, $replacement_raw_value );

	return true;
}


/**
 * Deletes only the exact generated-data row created by the current failed add attempt.
 *
 * This mirrors WordPress's delete metadata filter/actions while the primary-key and raw-byte
 * predicate prevent compensation from deleting a replacement owner.
 *
 * @param int    $post_id Post ID.
 * @param int    $meta_id Generated-data meta ID returned by the owned add.
 * @param string $expected_raw_value Exact raw value inserted by the owned add.
 * @return bool Whether the owned row is now absent or replaced without deleting a foreign owner.
 */
function ai4seo_delete_owned_generated_data_postmeta_row(
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
	$check = apply_filters( 'delete_post_metadata', null, $post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, $expected_raw_value, false );

	if ( null !== $check ) {
		$row_still_matches = ai4seo_generated_data_postmeta_row_matches_exact_value( $post_id, $meta_id, $expected_raw_value );

		return null !== $row_still_matches && ! $row_still_matches;
	}

	$query = ai4seo_prepare_database_query(
		'DELETE FROM {{postmeta_table}}
		WHERE `meta_id` = {{meta_id}}
		AND `post_id` = {{post_id}}
		AND BINARY `meta_key` = BINARY {{meta_key}}
		AND BINARY `meta_value` = BINARY {{meta_value}}
		LIMIT 1',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'meta_id'        => ai4seo_database_scalar_binding( '%d', $meta_id ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			'meta_key'       => ai4seo_database_scalar_binding( '%s', AI4SEO_POST_META_GENERATED_DATA_META_KEY ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The primary key bounds the exact owned-row compensation.
			'meta_value'     => ai4seo_database_scalar_binding( '%s', $expected_raw_value ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Stable primary-key and exact-byte ownership bound this compensation to one row.
		)
	);

	if ( false === $query ) {
		return false;
	}

	$meta_ids = array( (string) $meta_id );

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core metadata action.
	do_action( 'delete_post_meta', $meta_ids, $post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, $expected_raw_value );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core legacy metadata action.
	do_action( 'delete_postmeta', $meta_ids );

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler binds the stable primary key and exact operation-owned bytes; cache and metadata actions are repaired below.
	$delete_result = $wpdb->query( $query );

	if ( false === $delete_result || $wpdb->last_error || (int) $delete_result > 1 ) {
		return false;
	}

	if ( 0 === (int) $delete_result ) {
		$row_still_matches = ai4seo_generated_data_postmeta_row_matches_exact_value( $post_id, $meta_id, $expected_raw_value );

		return null !== $row_still_matches && ! $row_still_matches;
	}

	wp_cache_delete( $post_id, 'post_meta' );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core metadata action.
	do_action( 'deleted_post_meta', $meta_ids, $post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, $expected_raw_value );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirrors the authoritative WordPress core legacy metadata action.
	do_action( 'deleted_postmeta', $meta_ids );

	return true;
}


/**
 * Reads one generated-data snapshot and normalized field details from authoritative storage.
 *
 * Missing storage is a successful empty result. Duplicate rows, database failures, and values that
 * cannot be decoded to the supported JSON or legacy serialized array formats fail closed through
 * the appended success output and never consult the object cache.
 *
 * @param int       $post_id Post ID.
 * @param bool|null $read_succeeded Receives whether exact storage and decoding were authoritative.
 * @param bool|null $row_exists Receives whether the exact generated-data row exists.
 * @return array{generated_data: array, generated_at: int, generated_at_by_field: array} Normalized details.
 */
function ai4seo_read_authoritative_generated_data_details_for_post(
	int $post_id,
	?bool &$read_succeeded = null,
	?bool &$row_exists = null
): array {
	$post_id              = absint( $post_id );
	$read_succeeded       = false;
	$row_exists           = false;
	$empty_generated_data = array(
		'generated_data'        => array(),
		'generated_at'          => 0,
		'generated_at_by_field' => array(),
	);

	if ( $post_id <= 0 ) {
		return $empty_generated_data;
	}

	$snapshot = ai4seo_read_authoritative_generated_data_postmeta_snapshot( $post_id, $read_succeeded );

	if ( ! $read_succeeded ) {
		return $empty_generated_data;
	}

	$row_exists = ! empty( $snapshot['exists'] );

	return $snapshot['generated_data_details'] ?? $empty_generated_data;
}


/**
 * Reads whether one post has a valid generated-data snapshot from authoritative storage.
 *
 * @param int       $post_id Post ID.
 * @param bool|null $read_succeeded Receives whether exact storage and decoding were authoritative.
 * @return bool Whether the valid snapshot contains generated field data.
 */
function ai4seo_read_generated_data_presence_for_post( int $post_id, ?bool &$read_succeeded = null ): bool {
	$generated_data_details = ai4seo_read_authoritative_generated_data_details_for_post( $post_id, $read_succeeded );
	$generated_fields       = $generated_data_details['generated_data'] ?? null;

	return $read_succeeded && is_array( $generated_fields ) && ! empty( $generated_fields );
}


/**
 * Returns whether a post has authoritative generated data.
 *
 * @param int       $post_id The post ID.
 * @param bool|null $read_succeeded Receives whether exact storage and decoding were authoritative.
 * @return bool Whether generated field data exists.
 */
function ai4seo_post_has_generated_data( int $post_id, ?bool &$read_succeeded = null ): bool {
	return ai4seo_read_generated_data_presence_for_post( $post_id, $read_succeeded );
}


/**
 * Invalidates WordPress postmeta caches for normalized post IDs.
 *
 * @param array $post_ids Post IDs whose complete postmeta cache must be discarded.
 * @return void
 */
function ai4seo_invalidate_postmeta_caches( array $post_ids ): void {
	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );

	foreach ( $post_ids as $this_post_id ) {
		wp_cache_delete( $this_post_id, 'post_meta' );
	}
}


/**
 * Invalidates the environmental cache derived from the complete postmeta table.
 *
 * Direct SQL deletes bypass every WordPress metadata hook, so their aggregate table count must be
 * repaired explicitly in addition to the affected per-post object caches.
 *
 * @return bool True when the cache was invalidated or was already absent.
 */
function ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete(): bool {
	return ai4seo_invalidate_environmental_variable_cache(
		AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTMETA_TABLE_ENTRIES
	);
}


/**
 * Run an optional ownership checkpoint for a chunked postmeta mutation.
 *
 * @param callable|null $ownership_checkpoint Callback that returns true while the caller owns its fence.
 * @return bool True when no checkpoint is required or ownership was renewed and verified.
 */
function ai4seo_run_postmeta_delete_ownership_checkpoint( ?callable $ownership_checkpoint ): bool {
	if ( null === $ownership_checkpoint ) {
		return true;
	}

	try {
		return true === call_user_func( $ownership_checkpoint );
	} catch ( Throwable $throwable ) {
		return false;
	}
}


/**
 * Deletes a postmeta key for specific post IDs.
 *
 * Each successful chunk invalidates every requested post ID, including IDs without a row when the
 * query began. This also clears stale negative caches and closes the select/delete race window.
 *
 * @param array         $post_ids Post IDs.
 * @param string        $meta_key Postmeta key.
 * @param array|null    $possibly_deleted_post_ids Receives every ID in a successfully executed chunk.
 * @param callable|null $ownership_checkpoint Optional lease-renewal/ownership callback.
 * @return bool True on success.
 */
function ai4seo_delete_postmeta_for_post_ids_and_meta_key(
	array $post_ids,
	string $meta_key,
	?array &$possibly_deleted_post_ids = null,
	?callable $ownership_checkpoint = null
): bool {
	global $wpdb;

	$post_ids                  = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$meta_key                  = sanitize_key( $meta_key );
	$possibly_deleted_post_ids = array();

	if ( ! $post_ids || ! $meta_key ) {
		return true;
	}

	$database_chunk_size = ai4seo_get_database_chunk_size();
	$post_ids_chunks     = array_chunk( $post_ids, $database_chunk_size );
	$delete_query_ran    = false;

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( ! $this_post_ids_chunk ) {
			continue;
		}

		if ( ! ai4seo_run_postmeta_delete_ownership_checkpoint( $ownership_checkpoint ) ) {
			if ( $delete_query_ran ) {
				ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
			}

			return false;
		}

		$delete_query = ai4seo_prepare_database_query(
			'DELETE FROM {{postmeta_table}} WHERE meta_key = {{meta_key}} AND post_id IN ({{post_ids}})',
			array(
				'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- WordPress indexes postmeta.meta_key and the post-ID constraint bounds this exact-key delete.
				'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ),
				'post_ids'       => ai4seo_database_list_binding( '%d', $this_post_ids_chunk ),
			)
		);

		if ( false === $delete_query ) {
			if ( $delete_query_ran ) {
				ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
			}

			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the closed table/key/ID specification; every affected cache is invalidated after success.
		$delete_result = $wpdb->query( $delete_query );

		if ( false === $delete_result || $wpdb->last_error ) {
			ai4seo_debug_message( 984321707, 'Database error: ' . $wpdb->last_error, true );

			if ( $delete_query_ran ) {
				ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
			}

			return false;
		}

		$delete_query_ran          = true;
		$possibly_deleted_post_ids = array_values(
			array_unique( array_merge( $possibly_deleted_post_ids, $this_post_ids_chunk ) )
		);
		ai4seo_invalidate_postmeta_caches( $this_post_ids_chunk );
	}

	if ( ! ai4seo_run_postmeta_delete_ownership_checkpoint( $ownership_checkpoint ) ) {
		if ( $delete_query_ran ) {
			ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
		}

		return false;
	}

	return ! $delete_query_ran || ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
}


/**
 * Deletes every postmeta row with one exact key and repairs affected object caches.
 *
 * The operation snapshots its highest matching meta ID, then advances monotonically through that
 * bounded row range. Paging by the primary key also owns orphaned post_id=0 rows that cannot be
 * represented by a WordPress object-cache key. One final bounded lookup fails closed when concurrent
 * rows survive outside the snapshot, without allowing replenishment to extend the processing loop.
 *
 * @param string        $meta_key Postmeta key.
 * @param callable|null $ownership_checkpoint Optional lease-renewal/ownership callback.
 * @return bool True on success.
 */
function ai4seo_delete_all_postmeta_for_meta_key( string $meta_key, ?callable $ownership_checkpoint = null ): bool {
	global $wpdb;

	$meta_key = sanitize_key( $meta_key );

	if ( ! $meta_key ) {
		return true;
	}

	if ( ! ai4seo_run_postmeta_delete_ownership_checkpoint( $ownership_checkpoint ) ) {
		return false;
	}

	$database_chunk_size = ai4seo_get_database_chunk_size();
	$high_water_query    = ai4seo_prepare_database_query(
		'SELECT MAX(meta_id) FROM {{postmeta_table}} WHERE meta_key = {{meta_key}}',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The indexed exact-key aggregate establishes one finite cleanup boundary.
			'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ),
		)
	);

	if ( false === $high_water_query ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the operation-start boundary; this mutation must use current storage state.
	$high_water_meta_id = $wpdb->get_var( $high_water_query );

	if ( $wpdb->last_error ) {
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
		if ( ! ai4seo_run_postmeta_delete_ownership_checkpoint( $ownership_checkpoint ) ) {
			ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
			return false;
		}

		$affected_rows_query = ai4seo_prepare_database_query(
			'SELECT meta_id, post_id FROM {{postmeta_table}} WHERE meta_key = {{meta_key}} AND meta_id > {{meta_id_cursor}} AND meta_id <= {{high_water_meta_id}} ORDER BY meta_id ASC LIMIT {{query_limit}}',
			array(
				'postmeta_table'     => ai4seo_database_identifier_binding( 'table.postmeta' ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The exact key is paired with monotonic primary-key bounds and one configured result page.
				'meta_key'           => ai4seo_database_scalar_binding( '%s', $meta_key ),
				'meta_id_cursor'     => ai4seo_database_scalar_binding( '%d', $meta_id_cursor ),
				'high_water_meta_id' => ai4seo_database_scalar_binding( '%d', $high_water_meta_id ),
				'query_limit'        => ai4seo_database_scalar_binding( '%d', $database_chunk_size ),
			)
		);

		if ( false === $affected_rows_query ) {
			ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
			return false;
		}

		// This current-state page identifies the exact rows and positive cache owners of the following delete.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the closed primary-key page used to own deletion and cache invalidation.
		$affected_rows = $wpdb->get_results( $affected_rows_query, ARRAY_A );

		if ( $wpdb->last_error || ! is_array( $affected_rows ) ) {
			ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
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
				ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
				return false;
			}

			if ( $this_meta_id <= $meta_id_cursor || $this_meta_id > $high_water_meta_id ) {
				ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
				return false;
			}

			$affected_meta_ids[] = $this_meta_id;

			$this_post_id = isset( $affected_row['post_id'] ) ? (int) $affected_row['post_id'] : -1;

			if ( $this_post_id < 0 ) {
				ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
				return false;
			}

			if ( $this_post_id > 0 ) {
				$affected_post_id_lookup[ $this_post_id ] = $this_post_id;
			}
		}

		$next_meta_id_cursor = (int) end( $affected_meta_ids );

		if ( $next_meta_id_cursor <= $meta_id_cursor ) {
			ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
			return false;
		}

		$delete_query = ai4seo_prepare_database_query(
			'DELETE FROM {{postmeta_table}} WHERE meta_key = {{meta_key}} AND meta_id IN ({{meta_ids}})',
			array(
				'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The primary-key list owns every row; the exact key prevents deleting a concurrently replaced identity outside this operation.
				'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ),
				'meta_ids'       => ai4seo_database_list_binding( '%d', $affected_meta_ids ),
			)
		);

		if ( false === $delete_query ) {
			ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
			return false;
		}

		if ( ! ai4seo_run_postmeta_delete_ownership_checkpoint( $ownership_checkpoint ) ) {
			ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared exact observed primary keys; positive postmeta cache owners are invalidated below.
		$delete_result = $wpdb->query( $delete_query );

		if ( false === $delete_result || $wpdb->last_error ) {
			ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
			return false;
		}

		ai4seo_invalidate_postmeta_caches( array_values( $affected_post_id_lookup ) );
		$meta_id_cursor = $next_meta_id_cursor;
	}

	if ( ! ai4seo_run_postmeta_delete_ownership_checkpoint( $ownership_checkpoint ) ) {
		ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
		return false;
	}

	$remaining_post_id_query = ai4seo_prepare_database_query(
		'SELECT post_id FROM {{postmeta_table}} WHERE meta_key = {{meta_key}} ORDER BY meta_id ASC LIMIT 1',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The indexed exact-key lookup is bounded to one survivor after the finite mutation.
			'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ),
		)
	);

	if ( false === $remaining_post_id_query ) {
		ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
		return false;
	}

	// One current-state row distinguishes a completed snapshot from a currently clean key set.
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this bounded final verification after the direct mutation.
	$remaining_post_id = $wpdb->get_var( $remaining_post_id_query );

	if ( $wpdb->last_error ) {
		ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
		return false;
	}

	if ( null === $remaining_post_id ) {
		return ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
	}

	ai4seo_invalidate_postmeta_caches( array( $remaining_post_id ) );
	ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
	return false;
}


// endregion
// ___________________________________________________________________________________________.
