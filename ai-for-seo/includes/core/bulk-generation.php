<?php
/**
 * Manages bulk-generation and SEO Autopilot queues.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region BULK GENERATION / SEO AUTOPILOT ======================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Prepares one metadata Auto Queue candidate query with a literal ordering policy.
 *
 * @param array  $post_ids Candidate post IDs.
 * @param string $post_type Post type to select.
 * @param string $post_date_filter Validated date-filter mode.
 * @param string $post_date_gmt Validated UTC boundary.
 * @param string $bulk_generation_order Requested queue order.
 * @return string|false Prepared query, or false when its typed bindings are invalid.
 */
function ai4seo_prepare_metadata_auto_queue_candidate_query(
	array $post_ids,
	string $post_type,
	string $post_date_filter,
	string $post_date_gmt,
	string $bulk_generation_order
) {
	return ai4seo_prepare_metadata_auto_queue_candidate_query_for_post_types(
		$post_ids,
		array( $post_type ),
		$post_date_filter,
		$post_date_gmt,
		$bulk_generation_order
	);
}


/**
 * Prepares one metadata Auto Queue candidate query across all enabled post types.
 *
 * @param array  $post_ids Candidate post IDs.
 * @param array  $post_types Post types to select.
 * @param string $post_date_filter Validated date-filter mode.
 * @param string $post_date_gmt Validated UTC boundary.
 * @param string $bulk_generation_order Requested queue order.
 * @param bool   $classify_all_candidates Whether shared classification needs the entire bounded batch.
 * @return string|false Prepared query, or false when its typed bindings are invalid.
 */
function ai4seo_prepare_metadata_auto_queue_candidate_query_for_post_types(
	array $post_ids,
	array $post_types,
	string $post_date_filter,
	string $post_date_gmt,
	string $bulk_generation_order,
	bool $classify_all_candidates = false
) {
	// Shared classification and random ordering need the full bounded batch; legacy ordered callers need only two rows.
	$result_limit = ! $classify_all_candidates && in_array( $bulk_generation_order, array( 'oldest', 'newest' ), true ) ? 2 : count( $post_ids );

	// Keep each supported ORDER BY policy as literal SQL so no caller-provided fragment reaches compilation.
	$query_template = "SELECT ID
		FROM {{posts_table}}
		WHERE post_type IN ({{post_types}})
		AND ID IN ({{post_ids}})
		AND post_status IN ({{post_statuses}})
		AND (
			{{date_filter_both}} = 'both'
			OR ({{date_filter_new}} = 'new' AND post_date_gmt > {{new_post_date_gmt}})
			OR ({{date_filter_existing}} = 'existing' AND post_date_gmt <= {{existing_post_date_gmt}})
		)
		LIMIT {{result_limit}}";

	if ( 'oldest' === $bulk_generation_order ) {
		$query_template = "SELECT ID
			FROM {{posts_table}}
			WHERE post_type IN ({{post_types}})
			AND ID IN ({{post_ids}})
			AND post_status IN ({{post_statuses}})
			AND (
				{{date_filter_both}} = 'both'
				OR ({{date_filter_new}} = 'new' AND post_date_gmt > {{new_post_date_gmt}})
				OR ({{date_filter_existing}} = 'existing' AND post_date_gmt <= {{existing_post_date_gmt}})
			)
			ORDER BY ID ASC
			LIMIT {{result_limit}}";
	} elseif ( 'newest' === $bulk_generation_order ) {
		$query_template = "SELECT ID
			FROM {{posts_table}}
			WHERE post_type IN ({{post_types}})
			AND ID IN ({{post_ids}})
			AND post_status IN ({{post_statuses}})
			AND (
				{{date_filter_both}} = 'both'
				OR ({{date_filter_new}} = 'new' AND post_date_gmt > {{new_post_date_gmt}})
				OR ({{date_filter_existing}} = 'existing' AND post_date_gmt <= {{existing_post_date_gmt}})
			)
			ORDER BY ID DESC
			LIMIT {{result_limit}}";
	}

	return ai4seo_prepare_database_query(
		$query_template,
		array(
			'posts_table'            => ai4seo_database_identifier_binding( 'table.posts' ),
			'post_types'             => ai4seo_database_list_binding( '%s', array_slice( $post_types, 0, 256 ) ),
			'post_ids'               => ai4seo_database_list_binding( '%d', $post_ids ),
			'post_statuses'          => ai4seo_database_list_binding( '%s', array( 'publish', 'future' ) ),
			'date_filter_both'       => ai4seo_database_scalar_binding( '%s', $post_date_filter ),
			'date_filter_new'        => ai4seo_database_scalar_binding( '%s', $post_date_filter ),
			'new_post_date_gmt'      => ai4seo_database_scalar_binding( '%s', $post_date_gmt ),
			'date_filter_existing'   => ai4seo_database_scalar_binding( '%s', $post_date_filter ),
			'existing_post_date_gmt' => ai4seo_database_scalar_binding( '%s', $post_date_gmt ),
			'result_limit'           => ai4seo_database_scalar_binding( '%d', $result_limit ),
		)
	);
}


/**
 * Prepares one attachment Auto Queue candidate query with a literal ordering policy.
 *
 * @param array  $post_ids Candidate attachment post IDs.
 * @param array  $post_types Supported attachment post types.
 * @param array  $mime_types Supported attachment MIME types.
 * @param string $post_date_filter Validated date-filter mode.
 * @param string $post_date_gmt Validated UTC boundary.
 * @param string $bulk_generation_order Requested queue order.
 * @param bool   $classify_all_candidates Whether shared classification needs the entire bounded batch.
 * @return string|false Prepared query, or false when its typed bindings are invalid.
 */
function ai4seo_prepare_attachment_auto_queue_candidate_query(
	array $post_ids,
	array $post_types,
	array $mime_types,
	string $post_date_filter,
	string $post_date_gmt,
	string $bulk_generation_order,
	bool $classify_all_candidates = false
) {
	if ( ! $mime_types ) {
		return false;
	}
	// Shared classification and random ordering need the full bounded batch; legacy ordered callers need only two rows.
	$result_limit = ! $classify_all_candidates && in_array( $bulk_generation_order, array( 'oldest', 'newest' ), true ) ? 2 : count( $post_ids );

	// Keep each supported ORDER BY policy as literal SQL so no caller-provided fragment reaches compilation.
	$query_template = "SELECT ID
		FROM {{posts_table}}
		WHERE post_type IN ({{post_types}})
		AND ID IN ({{post_ids}})
		AND post_status IN ({{post_statuses}})
		AND post_mime_type IN ({{mime_types}})
		AND (
			{{date_filter_both}} = 'both'
			OR ({{date_filter_new}} = 'new' AND post_date_gmt > {{new_post_date_gmt}})
			OR ({{date_filter_existing}} = 'existing' AND post_date_gmt <= {{existing_post_date_gmt}})
		)
		LIMIT {{result_limit}}";

	if ( 'oldest' === $bulk_generation_order ) {
		$query_template = "SELECT ID
			FROM {{posts_table}}
			WHERE post_type IN ({{post_types}})
			AND ID IN ({{post_ids}})
			AND post_status IN ({{post_statuses}})
			AND post_mime_type IN ({{mime_types}})
			AND (
				{{date_filter_both}} = 'both'
				OR ({{date_filter_new}} = 'new' AND post_date_gmt > {{new_post_date_gmt}})
				OR ({{date_filter_existing}} = 'existing' AND post_date_gmt <= {{existing_post_date_gmt}})
			)
			ORDER BY ID ASC
			LIMIT {{result_limit}}";
	} elseif ( 'newest' === $bulk_generation_order ) {
		$query_template = "SELECT ID
			FROM {{posts_table}}
			WHERE post_type IN ({{post_types}})
			AND ID IN ({{post_ids}})
			AND post_status IN ({{post_statuses}})
			AND post_mime_type IN ({{mime_types}})
			AND (
				{{date_filter_both}} = 'both'
				OR ({{date_filter_new}} = 'new' AND post_date_gmt > {{new_post_date_gmt}})
				OR ({{date_filter_existing}} = 'existing' AND post_date_gmt <= {{existing_post_date_gmt}})
			)
			ORDER BY ID DESC
			LIMIT {{result_limit}}";
	}

	return ai4seo_prepare_database_query(
		$query_template,
		array(
			'posts_table'            => ai4seo_database_identifier_binding( 'table.posts' ),
			'post_types'             => ai4seo_database_list_binding( '%s', $post_types ),
			'post_ids'               => ai4seo_database_list_binding( '%d', $post_ids ),
			'post_statuses'          => ai4seo_database_list_binding( '%s', array( 'publish', 'future', 'inherit' ) ),
			'mime_types'             => ai4seo_database_list_binding( '%s', $mime_types ),
			'date_filter_both'       => ai4seo_database_scalar_binding( '%s', $post_date_filter ),
			'date_filter_new'        => ai4seo_database_scalar_binding( '%s', $post_date_filter ),
			'new_post_date_gmt'      => ai4seo_database_scalar_binding( '%s', $post_date_gmt ),
			'date_filter_existing'   => ai4seo_database_scalar_binding( '%s', $post_date_filter ),
			'existing_post_date_gmt' => ai4seo_database_scalar_binding( '%s', $post_date_gmt ),
			'result_limit'           => ai4seo_database_scalar_binding( '%d', $result_limit ),
		)
	);
}


/**
 * Restores PHP's candidate order after an unordered eligibility query.
 *
 * Ordered SQL variants already return their requested ID order. Random mode
 * queries every eligible row in one bounded candidate chunk, then intersects
 * that result with the caller's shuffled candidate sequence.
 *
 * @param array  $candidate_post_ids Candidate IDs in requested PHP order.
 * @param array  $eligible_post_ids IDs returned by the eligibility query.
 * @param string $bulk_generation_order Requested queue order.
 * @return array Eligible integer IDs in the requested order.
 */
function ai4seo_order_auto_queue_eligible_post_ids( array $candidate_post_ids, array $eligible_post_ids, string $bulk_generation_order ): array {
	$eligible_post_ids = array_values( array_filter( array_map( 'intval', $eligible_post_ids ) ) );

	if ( in_array( $bulk_generation_order, array( 'oldest', 'newest' ), true ) ) {
		return $eligible_post_ids;
	}

	// Use a lookup only for membership; traversal order remains the shuffled candidate order.
	$eligible_post_id_lookup   = array_fill_keys( $eligible_post_ids, true );
	$ordered_eligible_post_ids = array();

	foreach ( $candidate_post_ids as $candidate_post_id ) {
		$candidate_post_id = (int) $candidate_post_id;

		if ( isset( $eligible_post_id_lookup[ $candidate_post_id ] ) ) {
			$ordered_eligible_post_ids[] = $candidate_post_id;
		}
	}

	return $ordered_eligible_post_ids;
}

/**
 * Normalizes post IDs from an option value without changing their queue order.
 *
 * @param mixed $option_value Raw option value or decoded post-ID collection.
 * @return array Ordered unique positive integer post IDs.
 */
function ai4seo_normalize_ordered_auto_queue_post_ids( $option_value ): array {
	return ai4seo_normalize_option_post_id_collection( $option_value );
}


/**
 * Normalize enabled bulk-generation post types to their canonical string domain.
 *
 * @param mixed $enabled_bulk_generation_post_types Candidate post-type collection.
 * @return array Canonical enabled post-type identifiers.
 */
function ai4seo_normalize_enabled_bulk_generation_post_types( $enabled_bulk_generation_post_types ): array {
	if ( ! is_array( $enabled_bulk_generation_post_types ) ) {
		return array();
	}

	$normalized_post_types = array();

	foreach ( $enabled_bulk_generation_post_types as $post_type ) {
		if ( ! is_string( $post_type ) && ! is_int( $post_type ) ) {
			continue;
		}

		$post_type = (string) $post_type;

		if ( preg_match( '/^[a-zA-Z0-9_-]+$/', $post_type ) ) {
			$normalized_post_types[] = $post_type;
		}
	}

	return array_values( array_unique( $normalized_post_types ) );
}


/**
 * Return enabled bulk-generation post types in their canonical string domain.
 *
 * String and integer values are retained so legacy numeric post-type representations remain
 * equivalent without allowing booleans, floats, arrays, or objects into membership checks.
 *
 * @return array Canonical enabled post-type identifiers.
 */
function ai4seo_get_enabled_bulk_generation_post_types(): array {
	// Read through the settings accessor while preserving legacy integer post-type representations below.
	$enabled_bulk_generation_post_types = ai4seo_get_setting( AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES );

	$types = ai4seo_normalize_enabled_bulk_generation_post_types( $enabled_bulk_generation_post_types );
	if ( ai4seo_get_enabled_bulk_generation_taxonomies() ) {
		$types[] = AI4SEO_TAXONOMY_PROXY_POST_TYPE;
	}
	return array_values( array_unique( $types ) );
}


/**
 * Read enabled bulk-generation post types from authoritative option storage.
 *
 * Queue admission and paid generation cannot rely on a request cache that predates an admin save.
 * Missing storage or a missing setting key uses the declared disabled-by-default value. Malformed or
 * unreadable storage fails closed so callers can preserve the queue and request a checked repair.
 *
 * @param bool|null  $read_succeeded Receives whether authoritative storage was decoded and validated.
 * @param array|null $settings_snapshot Receives the exact raw Settings option snapshot.
 * @return array Canonical persisted enabled post-type identifiers, or an empty array on failure.
 */
function ai4seo_read_persisted_enabled_bulk_generation_post_types(
	?bool &$read_succeeded = null,
	?array &$settings_snapshot = null
): array {
	$read_succeeded    = false;
	$settings_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_SETTINGS_OPTION_NAME );

	if ( null === $settings_snapshot ) {
		return array();
	}

	$persisted_settings = $settings_snapshot['exists'] ? $settings_snapshot['value'] : array();

	// Retain compatibility with the legacy extra-serialization and JSON shapes accepted by settings hydration.
	$persisted_settings = ai4seo_safe_maybe_unserialize( $persisted_settings );

	if ( ! is_array( $persisted_settings ) && is_string( $persisted_settings ) && ai4seo_is_json( $persisted_settings ) ) {
		$persisted_settings = json_decode( $persisted_settings, true );
	}

	if ( ! is_array( $persisted_settings ) ) {
		return array();
	}

	$enabled_bulk_generation_post_types = array_key_exists(
		AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES,
		$persisted_settings
	)
		? $persisted_settings[ AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES ]
		: AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES ];

	if ( ! ai4seo_validate_setting_value( AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES, $enabled_bulk_generation_post_types ) ) {
		return array();
	}

	$taxonomies = $persisted_settings[ AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES ] ?? array();
	if ( ! ai4seo_validate_setting_value( AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES, $taxonomies ) ) {
		return array();
	}
	$read_succeeded = true;
	$types          = ai4seo_normalize_enabled_bulk_generation_post_types( $enabled_bulk_generation_post_types );
	if ( $taxonomies ) {
		$types[] = AI4SEO_TAXONOMY_PROXY_POST_TYPE;
	}
	return array_values( array_unique( $types ) );
}


/**
 * Check if the auto generation is enabled for a specific context
 *
 * @param string $post_type The context of the auto generation (post, page, product, attachment, keyphrase etc.).
 * @return bool True if the auto generation is enabled, false if not
 */
function ai4seo_is_bulk_generation_enabled( string $post_type ): bool {
	$enabled_bulk_generation_post_types = ai4seo_get_enabled_bulk_generation_post_types();

	return in_array( $post_type, $enabled_bulk_generation_post_types, true );
}

/**
 * Check if any auto generation is enabled
 *
 * @return bool True if any auto generation is enabled, false if not
 */
function ai4seo_is_any_bulk_generation_enabled(): bool {
	$enabled_bulk_generation_post_types = ai4seo_get_enabled_bulk_generation_post_types();

	return count( $enabled_bulk_generation_post_types ) > 0;
}

/**
 * Check if SEO Autopilot should automatically queue applicable entries.
 *
 * @return bool
 */
function ai4seo_should_auto_queue_bulk_generation_entries(): bool {
	return ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_AUTO_QUEUE_ENTRIES ) === true;
}


/**
 * Function to check if the SEO Autopilot is running at least X amount of seconds
 *
 * @param int $duration The duration in seconds.
 * @return bool True if the SEO Autopilot is running at least X amount of seconds
 */
function ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago( int $duration = 300 ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 530953976, 'Prevented loop', true );
		return false;
	}

	$seo_autopilot_start_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME );

	if ( ! $seo_autopilot_start_time ) {
		return false;
	}

	return ( time() - $seo_autopilot_start_time ) >= $duration;
}

/**
 * Check whether the SEO Autopilot new/existing setting activates its date boundary.
 *
 * @param mixed $filter Stored new/existing filter.
 * @return bool True for the new and existing modes.
 */
function ai4seo_is_bulk_generation_date_filter_active( $filter ): bool {
	// Only the complementary new/existing modes require a persisted reference time.
	return in_array( $filter, array( 'new', 'existing' ), true );
}

/**
 * Resolve persisted SEO Autopilot date-filter state without side effects.
 *
 * The inactive "both" mode deliberately ignores its reference time and returns a neutral valid
 * DATETIME binding. Active filters require a positive integer timestamp whose UTC representation
 * fits MySQL's supported DATETIME range.
 *
 * @param mixed $filter Stored new/existing filter.
 * @param mixed $reference_timestamp Stored filter reference timestamp.
 * @return array Canonical state with validity, filter, reference time, SQL date, and error code.
 */
function ai4seo_get_bulk_generation_date_filter_state( $filter, $reference_timestamp ): array {
	// Canonicalize only string input because other persisted types are invalid filter state.
	$filter = is_string( $filter ) ? sanitize_key( $filter ) : '';

	// Start fail-closed so every rejected branch returns the same complete state shape.
	$filter_state = array(
		'is_valid'            => false,
		'filter'              => $filter,
		'reference_timestamp' => 0,
		'post_date_gmt'       => '',
		'error_code'          => 'invalid_filter',
	);

	// Reject unsupported filter values before considering their associated reference time.
	if ( ! in_array( $filter, AI4SEO_AVAILABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_OPTIONS, true ) ) {
		return $filter_state;
	}

	// Supply a valid neutral SQL binding when the query does not apply a date comparison.
	if ( 'both' === $filter ) {
		$filter_state['is_valid']      = true;
		$filter_state['post_date_gmt'] = AI4SEO_MYSQL_DATETIME_MINIMUM;
		$filter_state['error_code']    = '';

		return $filter_state;
	}

	// Require an exact positive integer so malformed persisted values cannot widen queue scope.
	$reference_timestamp = ( is_int( $reference_timestamp ) || is_string( $reference_timestamp ) )
		? filter_var(
			$reference_timestamp,
			FILTER_VALIDATE_INT,
			array(
				'options' => array(
					'min_range' => 1,
				),
			)
		)
		: false;

	if ( false === $reference_timestamp ) {
		$filter_state['error_code'] = 'invalid_reference_timestamp';

		return $filter_state;
	}

	// Format through DateTime so platform range failures remain explicit and recoverable.
	try {
		$reference_datetime = new DateTimeImmutable( '@' . $reference_timestamp );
		$post_date_gmt      = $reference_datetime
			->setTimezone( new DateTimeZone( 'UTC' ) )
			->format( 'Y-m-d H:i:s' );
	} catch ( Throwable $throwable ) {
		$filter_state['error_code'] = 'reference_timestamp_format_failed';

		return $filter_state;
	}

	// Keep invalid or out-of-range SQL dates out of every prepared query consumer.
	if ( ! ai4seo_is_valid_mysql_datetime( $post_date_gmt ) ) {
		$filter_state['error_code'] = 'invalid_post_date_gmt';

		return $filter_state;
	}

	// Publish the canonical active state only after every timestamp and SQL-date check succeeds.
	$filter_state['is_valid']            = true;
	$filter_state['reference_timestamp'] = $reference_timestamp;
	$filter_state['post_date_gmt']       = $post_date_gmt;
	$filter_state['error_code']          = '';

	return $filter_state;
}

/**
 * Resolve the new/existing-entry filter into query-safe date state.
 *
 * The inactive "both" mode uses a valid, neutral DATETIME binding so strict database modes never
 * receive an empty value. Active date filters require a positive integer timestamp that formats to
 * an exact MySQL DATETIME value; corrupted state is rejected instead of widening the queue scope.
 *
 * @param mixed $filter Stored new/existing filter.
 * @param mixed $reference_timestamp Stored filter reference timestamp.
 * @return array|false Query-safe filter and DATETIME value, or false when the state is invalid.
 */
function ai4seo_resolve_bulk_generation_date_filter( $filter, $reference_timestamp ) {
	// Reuse the canonical validator before exposing state to either prepared-query path.
	$date_filter_state = ai4seo_get_bulk_generation_date_filter_state( $filter, $reference_timestamp );

	if ( empty( $date_filter_state['is_valid'] ) ) {
		// Map each validation failure to a stable diagnostic without exposing invalid data to SQL.
		switch ( $date_filter_state['error_code'] ?? '' ) {
			case 'invalid_filter':
				ai4seo_debug_message( 748217659, 'Invalid SEO Autopilot new/existing filter; queue excavation was skipped.', true );
				break;

			case 'invalid_reference_timestamp':
				ai4seo_debug_message( 758217659, 'Invalid SEO Autopilot date-filter timestamp; queue excavation was skipped.', true );
				break;

			case 'reference_timestamp_format_failed':
				ai4seo_debug_message( 768217659, 'SEO Autopilot could not format its date-filter timestamp; queue excavation was skipped.', true );
				break;

			case 'invalid_post_date_gmt':
			default:
				ai4seo_debug_message( 778217659, 'SEO Autopilot produced an invalid database DATETIME value; queue excavation was skipped.', true );
				break;
		}

		return false;
	}

	// Return only the values consumed by the candidate-query variants.
	return array(
		'filter'        => $date_filter_state['filter'],
		'post_date_gmt' => $date_filter_state['post_date_gmt'],
	);
}


/**
 * Resolve the currently persisted SEO Autopilot date-filter setting and reference time.
 *
 * @return array Canonical state from ai4seo_get_bulk_generation_date_filter_state().
 */
function ai4seo_get_current_bulk_generation_date_filter_state(): array {
	// Pair the owning setting with its environmental boundary before validating either consumer.
	return ai4seo_get_bulk_generation_date_filter_state(
		ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER ),
		ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME )
	);
}

/**
 * Check whether an entry timestamp is eligible under resolved SEO Autopilot date-filter state.
 *
 * @param array $filter_state Canonical state from ai4seo_get_bulk_generation_date_filter_state().
 * @param int   $entry_timestamp Entry publication/upload timestamp in UTC.
 * @return bool True when the entry is inside the configured date scope.
 */
function ai4seo_does_bulk_generation_date_filter_include_timestamp( array $filter_state, int $entry_timestamp ): bool {
	// Invalid state is always fail-closed, matching queue excavation and analysis behavior.
	if ( empty( $filter_state['is_valid'] ) ) {
		return false;
	}

	// The inactive mode includes every entry without consulting its neutral reference time.
	if ( 'both' === ( $filter_state['filter'] ?? '' ) ) {
		return true;
	}

	$reference_timestamp = (int) ( $filter_state['reference_timestamp'] ?? 0 );

	// New entries begin strictly after the shared boundary.
	if ( 'new' === ( $filter_state['filter'] ?? '' ) ) {
		return $entry_timestamp > $reference_timestamp;
	}

	// The boundary itself belongs to the complementary existing-entry scope.
	return 'existing' === ( $filter_state['filter'] ?? '' )
		&& $entry_timestamp <= $reference_timestamp;
}

/**
 * Return the shared admin-facing explanation for invalid SEO Autopilot date-filter state.
 *
 * @return string Translated recovery guidance.
 */
function ai4seo_get_invalid_bulk_generation_date_filter_message(): string {
	// Keep recovery guidance identical everywhere invalid state is surfaced to administrators.
	return __( "SEO Autopilot cannot automatically queue entries because the 'New or existing entries' filter has an invalid reference date. Open the SEO Autopilot settings and save this filter again.", 'ai-for-seo' );
}


/**
 * Returns available bulk generation queue actions.
 *
 * @param string $surface Active surface: all, native, or custom.
 * @param string $active_status_filter Active SOOZ list status filter.
 * @param string $context Active queue context: all, metadata, or attachment_attributes.
 * @param string $list_location Active list location, such as main or related_attachments_modal.
 * @return array Bulk generation queue actions.
 */
function ai4seo_get_bulk_generation_queue_actions( string $surface = 'all', string $active_status_filter = 'all', string $context = 'all', string $list_location = 'main' ): array {
	$surface              = sanitize_key( $surface );
	$active_status_filter = sanitize_key( $active_status_filter );
	$context              = sanitize_key( $context );
	$list_location        = sanitize_key( $list_location );
	$plugin_name          = ai4seo_get_plugin_name();

	// The generated-data action affects the marker only, regardless of whether the entries are posts, pages, products, or media.
	$remove_generated_data_description = sprintf(
		/* translators: %s: Plugin name. */
		__( 'Deletes the %s generated-data marker for selected entries. Saved metadata, synced third-party SEO plugin values, and saved media library fields stay unchanged, but the entries can be queued again when applicable.', 'ai-for-seo' ),
		$plugin_name
	);

	// Queue actions control pending and force-overwrite state, and share their descriptions with the SOOZ table tooltip.
	$queue_actions = array(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE => array(
			'label'       => __( 'Add to queue (safe)', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Queues only selected entries that %s currently considers applicable, such as missing, failed, or manually excluded entries. Hidden, already queued, processing, or complete entries are skipped.', 'ai-for-seo' ),
				$plugin_name
			),
		),
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE_FORCE_OVERWRITE => array(
			'label'       => __( 'Add to queue (force)', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Queues selected valid entries in force mode so %s regenerates data even when saved values already exist. Hidden or currently processing entries are skipped.', 'ai-for-seo' ),
				$plugin_name
			),
		),
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_FROM_QUEUE => array(
			'label'       => __( 'Remove from queue', 'ai-for-seo' ),
			'description' => __( 'Removes selected entries from the pending queue and clears force mode. Saved metadata, generated-data markers, and entries already processing are not changed.', 'ai-for-seo' ),
		),
	);

	// Related-image actions start from selected metadata entries but mutate discovered media in the attachment queue.
	$related_attachment_queue_actions = array();

	if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES !== $context ) {
		$related_attachment_queue_actions = array(
			AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE => array(
				'label'       => __( 'Add all related images to queue (safe)', 'ai-for-seo' ),
				'description' => sprintf(
					/* translators: %s: Plugin name. */
					__( 'Finds images related to the selected entries using the same Related Media scan as %s, then queues only applicable related images for media attribute generation. Hidden, already queued, processing, or complete images are skipped.', 'ai-for-seo' ),
					$plugin_name
				),
			),
			AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE_FORCE_OVERWRITE => array(
				'label'       => __( 'Add all related images to queue (force)', 'ai-for-seo' ),
				'description' => sprintf(
					/* translators: %s: Plugin name. */
					__( 'Finds images related to the selected entries using the same Related Media scan as %s, then queues valid related images in force mode so media attributes can be regenerated. Hidden or currently processing images are skipped.', 'ai-for-seo' ),
					$plugin_name
				),
			),
			AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_RELATED_ATTACHMENTS_FROM_QUEUE => array(
				'label'       => __( 'Remove related images from queue', 'ai-for-seo' ),
				'description' => sprintf(
					/* translators: %s: Plugin name. */
					__( 'Finds images related to the selected entries using the same Related Media scan as %s, then removes pending related images from the media attribute queue and clears force mode for removed images. Processing, failed, missing, hidden, generated data, saved media fields, and related-post fallback metadata are not changed.', 'ai-for-seo' ),
					$plugin_name
				),
			),
		);
	}

	// Visibility actions are available only on SOOZ custom tables and depend on the active status filter.
	$hide_actions = array(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_HIDE_ENTRY => array(
			'label'       => __( 'Hide entry', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Hides selected entries from %s lists and automatic queueing, and removes them from pending and missing queue states.', 'ai-for-seo' ),
				$plugin_name
			),
		),
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_SHOW_ENTRY => array(
			'label'       => __( 'Show entry', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Shows selected hidden entries again and refreshes their %s status so they can appear in the relevant lists.', 'ai-for-seo' ),
				$plugin_name
			),
		),
	);

	// Auto Queue actions manage manual exclusions without changing saved metadata or media fields.
	$auto_queue_actions = array(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_DISALLOW_AUTO_QUEUE => array(
			'label'       => __( 'Exclude from Auto Queue feature', 'ai-for-seo' ),
			'description' => __( 'Prevents the Auto Queue feature from adding selected entries automatically. Pending and missing queue states are cleared, but manual queue actions remain available.', 'ai-for-seo' ),
		),
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_ALLOW_AUTO_QUEUE => array(
			'label'       => __( 'Allow for Auto Queue feature', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Removes the manual Auto Queue exclusion so selected entries can be added automatically again when %s detects they are applicable.', 'ai-for-seo' ),
				$plugin_name
			),
		),
	);

	// Data removal actions clarify whether SOOZ generation markers or active SOOZ metadata records are affected.
	$data_removal_actions = array(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_GENERATED_DATA => array(
			'label'       => __( 'Mark as not generated', 'ai-for-seo' ),
			'description' => $remove_generated_data_description,
		),
	);

	if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES !== $context ) {
		$data_removal_actions[ AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_SAVED_DATA ] = array(
			'label'       => __( 'Delete SOOZ metadata', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Deletes active %s metadata saved for selected entries from wp_postmeta. Third-party SEO plugin data and generated-data markers are not deleted.', 'ai-for-seo' ),
				$plugin_name
			),
		);
	}

	// Custom-instruction actions need a modal because the bulk dropdown itself cannot collect the instruction text.
	$custom_instructions_actions = array(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_SET_CUSTOM_INSTRUCTIONS => array(
			'label'          => __( 'Set custom instructions', 'ai-for-seo' ),
			'description'    => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Opens a modal to set entry-specific custom instructions for future %s generations. Existing entry-specific custom instructions are overwritten, and submitting an empty value clears them.', 'ai-for-seo' ),
				$plugin_name
			),
			'requires_modal' => true,
		),
	);

	if ( 'related_attachments_modal' === $list_location ) {
		// The embedded Related Media table inherits attachment.php, but this action is meant for main list tables only.
		$custom_instructions_actions = array();
	}
	if ( 'taxonomy' === $list_location ) {
		return array_merge( $queue_actions, $auto_queue_actions );
	}

	if ( 'native' === $surface ) {
		return array_merge( $queue_actions, $related_attachment_queue_actions, $custom_instructions_actions, $auto_queue_actions, $data_removal_actions );
	}

	if ( 'custom' === $surface ) {
		if ( 'hidden' === $active_status_filter ) {
			return array_merge(
				$queue_actions,
				$related_attachment_queue_actions,
				$custom_instructions_actions,
				$auto_queue_actions,
				$data_removal_actions,
				array(
					AI4SEO_BULK_GENERATION_QUEUE_ACTION_SHOW_ENTRY => $hide_actions[ AI4SEO_BULK_GENERATION_QUEUE_ACTION_SHOW_ENTRY ],
				)
			);
		}

		return array_merge(
			$queue_actions,
			$related_attachment_queue_actions,
			$custom_instructions_actions,
			$auto_queue_actions,
			$data_removal_actions,
			array(
				AI4SEO_BULK_GENERATION_QUEUE_ACTION_HIDE_ENTRY => $hide_actions[ AI4SEO_BULK_GENERATION_QUEUE_ACTION_HIDE_ENTRY ],
			)
		);
	}

	return array_merge( $queue_actions, $related_attachment_queue_actions, $custom_instructions_actions, $auto_queue_actions, $data_removal_actions, $hide_actions );
}


/**
 * Returns a bulk generation queue action label.
 *
 * @param string $action Action identifier.
 * @param bool   $include_plugin_name Whether to prefix the plugin name.
 * @return string Bulk generation queue action label.
 */
function ai4seo_get_bulk_generation_queue_action_label( string $action, bool $include_plugin_name = false ): string {
	$action                        = sanitize_key( $action );
	$bulk_generation_queue_actions = ai4seo_get_bulk_generation_queue_actions( 'all' );
	$action_label                  = $bulk_generation_queue_actions[ $action ]['label'] ?? $action;

	if ( ! $include_plugin_name ) {
		return $action_label;
	}

	// Native WordPress bulk action labels use the short brand to keep option text compact.
	return sprintf(
		/* translators: 1: Short plugin name, 2: bulk action label. */
		__( '%1$s: %2$s', 'ai-for-seo' ),
		AI4SEO_SHORT_PLUGIN_NAME,
		$action_label
	);
}


/**
 * Checks whether a bulk generation queue action exists.
 *
 * @param string $action Action identifier.
 * @return bool Whether the action exists.
 */
function ai4seo_is_bulk_generation_queue_action( string $action ): bool {
	$action                        = sanitize_key( $action );
	$bulk_generation_queue_actions = ai4seo_get_bulk_generation_queue_actions( 'all' );

	return isset( $bulk_generation_queue_actions[ $action ] );
}


/**
 * Checks whether a bulk generation queue action is available on a specific surface.
 *
 * @param string $action Action identifier.
 * @param string $surface Active surface: all, native, or custom.
 * @param string $active_status_filter Active SOOZ list status filter.
 * @param string $context Active queue context: all, metadata, or attachment_attributes.
 * @param string $list_location Active list location, such as main or related_attachments_modal.
 * @return bool Whether the action is available.
 */
function ai4seo_is_bulk_generation_queue_action_available_for_surface( string $action, string $surface, string $active_status_filter = 'all', string $context = 'all', string $list_location = 'main' ): bool {
	$action                        = sanitize_key( $action );
	$bulk_generation_queue_actions = ai4seo_get_bulk_generation_queue_actions( $surface, $active_status_filter, $context, $list_location );

	return isset( $bulk_generation_queue_actions[ $action ] );
}


/**
 * Checks whether a bulk generation queue action needs a modal before it can be processed.
 *
 * @param string $action Action identifier.
 * @return bool Whether this action is modal-only.
 */
function ai4seo_is_bulk_generation_queue_action_modal_required( string $action ): bool {
	$action = sanitize_key( $action );

	// The shared action registry carries modal requirements so native and custom handlers cannot drift.
	$bulk_generation_queue_actions = ai4seo_get_bulk_generation_queue_actions( 'all' );

	return ! empty( $bulk_generation_queue_actions[ $action ]['requires_modal'] );
}


/**
 * Checks whether an action queues related attachment images from selected source posts.
 *
 * @param string $action Action identifier.
 * @return bool Whether this is a related attachment queue action.
 */
function ai4seo_is_related_attachment_bulk_generation_queue_action( string $action ): bool {
	$action = sanitize_key( $action );

	// The remove action uses the same scanner dispatch, but the processor applies different queue side effects.
	if ( ai4seo_is_related_attachment_remove_bulk_generation_queue_action( $action ) ) {
		return true;
	}

	return in_array(
		$action,
		array(
			AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE,
			AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE_FORCE_OVERWRITE,
		),
		true
	);
}


/**
 * Checks whether a related-image bulk action removes discovered attachments from Pending.
 *
 * @param string $action Action identifier.
 * @return bool Whether this is the related attachment remove action.
 */
function ai4seo_is_related_attachment_remove_bulk_generation_queue_action( string $action ): bool {
	$action = sanitize_key( $action );

	// Keep remove-action checks centralized because the action shares scanning but has different queue side effects.
	return ( AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_RELATED_ATTACHMENTS_FROM_QUEUE === $action );
}


/**
 * Returns the queue contexts supported by all bulk-generation routing helpers.
 *
 * @return array
 */
function ai4seo_get_bulk_generation_queue_contexts(): array {
	// Keep the shared registry ordered consistently for validation, aggregation, and future context-wide operations.
	return array(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
	);
}


/**
 * Checks whether a bulk generation queue context exists.
 *
 * @param string $context Queue context.
 * @return bool Whether the queue context exists.
 */
function ai4seo_is_bulk_generation_queue_context( string $context ): bool {
	$context = sanitize_key( $context );

	// Reuse the routing registry so validation cannot drift from context-wide operations.
	return in_array( $context, ai4seo_get_bulk_generation_queue_contexts(), true );
}


/**
 * Returns the queue option names for a bulk generation queue context.
 *
 * @param string $context Queue context.
 * @return array Queue option names.
 */
function ai4seo_get_bulk_generation_queue_options_by_context( string $context ): array {
	$context = sanitize_key( $context );

	switch ( $context ) {
		case AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA:
			return array(
				'missing'               => AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME,
				'pending'               => AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
				'processing'            => AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
				'failed'                => AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME,
				'force_overwrite'       => AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
				'hidden'                => AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME,
				'auto_queue_disallowed' => AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME,
			);

		case AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES:
			return array(
				'missing'               => AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'pending'               => AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'processing'            => AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'failed'                => AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'force_overwrite'       => AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'hidden'                => AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'auto_queue_disallowed' => AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			);
	}

	return array();
}


/**
 * Add automatically discovered entries to a generation queue without losing retained force-overwrite markers.
 *
 * @param array  $post_ids Post IDs selected by automatic queue excavation.
 * @param string $context  Bulk generation queue context.
 * @return bool True only when the complete queue transition was verified.
 */
function ai4seo_add_auto_queue_post_ids_preserving_force_overwrite( array $post_ids, string $context ): bool {
	$post_ids      = ai4seo_normalize_option_post_id_collection( $post_ids );
	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

	if (
		! $post_ids
		|| ! isset( $queue_options['pending'], $queue_options['processing'] )
	) {
		return false;
	}

	// Reuse the fully fenced eligibility barrier; Processing and every other blocker remain untouched.
	return null !== ai4seo_add_bounded_auto_queue_post_ids_preserving_force_overwrite(
		$post_ids,
		$context,
		PHP_INT_MAX
	);
}


/**
 * Persist exact rollback owners before an automatic Pending admission can become visible.
 *
 * @param string $context Bulk generation queue context.
 * @param array  $post_ids Exact planned admission IDs.
 * @param array  $force_post_id_lookup Authoritative Force membership lookup.
 * @param array  $rollback_tokens Receives rollback tokens keyed by post ID, including an ambiguous final write.
 * @return bool Whether every exact rollback owner was verified.
 */
function ai4seo_persist_auto_queue_admission_rollback_leases_under_lock(
	string $context,
	array $post_ids,
	array $force_post_id_lookup,
	array &$rollback_tokens
): bool {
	$post_ids             = ai4seo_normalize_ordered_auto_queue_post_ids( $post_ids );
	$membership_snapshots = array();

	foreach ( $post_ids as $post_id ) {
		$membership_snapshots[ $post_id ] = array(
			'pending_was_present' => false,
			'force_was_present'   => isset( $force_post_id_lookup[ $post_id ] ),
		);
	}

	return ai4seo_persist_queue_membership_rollback_leases_under_lock(
		$context,
		$membership_snapshots,
		$rollback_tokens
	);
}


/**
 * Exact-token remove verified admission rollback owners while preserving a replacement owner.
 *
 * @param string $context Bulk generation queue context.
 * @param array  $rollback_tokens Rollback tokens keyed by post ID; resolved entries are removed.
 * @return bool Whether every owned token was removed, absent, or already replaced.
 */
function ai4seo_clear_auto_queue_admission_rollback_leases_under_lock(
	string $context,
	array &$rollback_tokens
): bool {
	return ai4seo_clear_queue_membership_rollback_leases_under_lock( $context, $rollback_tokens );
}


/**
 * Atomically admits post IDs to one queue option without exceeding its cap.
 *
 * @param string $option_name Queue option name.
 * @param array  $candidate_post_ids Ordered candidate IDs.
 * @param int    $maximum_post_ids Maximum number of IDs retained in the option.
 * @param array  $admitted_post_ids IDs inserted by the successful compare-and-swap.
 * @return bool True after a successful admission or an authoritative no-op.
 */
function ai4seo_add_bounded_post_ids_to_option(
	string $option_name,
	array $candidate_post_ids,
	int $maximum_post_ids,
	array &$admitted_post_ids
): bool {
	$option_name        = sanitize_key( $option_name );
	$candidate_post_ids = ai4seo_normalize_ordered_auto_queue_post_ids( $candidate_post_ids );
	$maximum_post_ids   = max( 0, $maximum_post_ids );
	$admitted_post_ids  = array();

	if ( '' === $option_name || 0 === $maximum_post_ids || ! $candidate_post_ids ) {
		return true;
	}

	// Retry from an authoritative snapshot so simultaneous admissions retain both workers' IDs.
	for ( $write_attempt = 0; $write_attempt < 5; ++$write_attempt ) {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$option_snapshot = ai4seo_get_raw_option_snapshot( $option_name );

		if ( null === $option_snapshot ) {
			return false;
		}

		$current_post_ids        = ai4seo_normalize_ordered_auto_queue_post_ids( $option_snapshot['value'] );
		$new_post_ids            = $current_post_ids;
		$post_id_lookup          = array_fill_keys( $current_post_ids, true );
		$newly_admitted_post_ids = array();

		foreach ( $candidate_post_ids as $candidate_post_id ) {
			if ( count( $new_post_ids ) >= $maximum_post_ids ) {
				break;
			}

			if ( isset( $post_id_lookup[ $candidate_post_id ] ) ) {
				continue;
			}

			$new_post_ids[]                       = $candidate_post_id;
			$post_id_lookup[ $candidate_post_id ] = true;
			$newly_admitted_post_ids[]            = $candidate_post_id;
		}

		if ( ! $newly_admitted_post_ids ) {
			return ai4seo_renew_post_id_option_transition_semaphore();
		}

		if (
			in_array(
				$option_name,
				array(
					AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
					AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
					AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
					AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				),
				true
			)
			&& ! ai4seo_clear_disabled_queue_inspection_state_under_lock()
		) {
			return false;
		}

		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			$option_name,
			$option_snapshot,
			$new_post_ids
		);

		if ( null === $compare_and_swap_result ) {
			return false;
		}

		if ( $compare_and_swap_result ) {
			$admitted_post_ids = $newly_admitted_post_ids;
			return ai4seo_renew_post_id_option_transition_semaphore();
		}
	}

	return false;
}


/**
 * Removes only Pending queue memberships observed in authoritative snapshots.
 *
 * Processing belongs to a live worker and is never inferred to be owned by the cleanup caller.
 * The storage primitive observes Pending and matching Force state only after acquiring the shared
 * fence, then fails closed if any out-of-band writer changes either exact snapshot.
 *
 * @param array    $contexts Bulk generation queue contexts.
 * @param int|null $credit_threshold Require confirmed credits below this threshold for low-credit cleanup.
 * @param bool     $require_disabled_fields Require a verified empty field selection at the write boundary.
 * @return bool True only when every requested snapshot removal was verified.
 */
function ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships( array $contexts, ?int $credit_threshold = null, bool $require_disabled_fields = false ): bool {
	$contexts                       = array_values( array_unique( array_map( 'sanitize_key', $contexts ) ) );
	$primary_to_paired_option_names = array();

	if ( ! $contexts ) {
		return false;
	}

	foreach ( $contexts as $context ) {
		$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

		if ( ! isset( $queue_options['pending'], $queue_options['force_overwrite'] ) ) {
			return false;
		}

		$primary_to_paired_option_names[ $queue_options['pending'] ] = $queue_options['force_overwrite'];
	}

	$guard   = static function () use ( $contexts, $credit_threshold, $require_disabled_fields ): bool {
		$settings = ai4seo_inspect_settings();
		foreach ( $contexts as $context ) {
			$key = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context ? AI4SEO_SETTING_ACTIVE_META_TAGS : AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES;
			if ( ! ai4seo_are_bulk_generation_fields_usable( $context, $settings )
				|| ( $require_disabled_fields && ( $settings['settings'][ $key ] || isset( $settings['issues'][ $key ] ) ) ) ) {
				return false;
			}
		}
		if ( null !== $credit_threshold ) {
			$account = ai4seo_get_account_readiness( true );
			return $account['credits_ready'] && $account['credits'] < $credit_threshold;
		}
		return true;
	};
	$removed = null;
	if ( ! ai4seo_clear_primary_post_id_option_pairs( $primary_to_paired_option_names, $removed, $guard ) ) {
		ai4seo_debug_message( 641476228, 'Could not safely remove bulk generation queue snapshot memberships.', true );
		return false;
	}

	return true;
}


/**
 * Resolve one terminal transition to its single queue ownership context.
 *
 * @param array $destination_option_names Requested destination or explicit removal options.
 * @return string Bulk generation context, or an empty string when mixed/unknown.
 */
function ai4seo_get_bulk_generation_result_transition_context( array $destination_option_names ): string {
	$resolved_context = '';

	foreach ( $destination_option_names as $destination_option_name ) {
		$destination_option_name = sanitize_key( $destination_option_name );
		$this_context            = '';

		foreach ( ai4seo_get_bulk_generation_queue_contexts() as $context ) {
			$context_option_names = array_values( ai4seo_get_bulk_generation_queue_options_by_context( $context ) );

			if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context ) {
				$context_option_names[] = AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME;
				$context_option_names[] = AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME;
			} elseif ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context ) {
				$context_option_names[] = AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;
				$context_option_names[] = AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;
			}

			if ( in_array( $destination_option_name, $context_option_names, true ) ) {
				$this_context = $context;
				break;
			}
		}

		if ( '' === $this_context || ( '' !== $resolved_context && $resolved_context !== $this_context ) ) {
			return '';
		}

		$resolved_context = $this_context;
	}

	return $resolved_context;
}


/**
 * Apply a terminal bulk-generation result for one exact durable owner without a cron registry.
 *
 * @param int    $post_id Post ID whose generation state changed.
 * @param array  $destination_option_names Options that must contain the post ID after the transition;
 *                                         empty for an exact owned removal-only result.
 * @param string $claim_token Exact durable lease token.
 * @param array  $removal_option_names Additional exact removals in the same queue context.
 * @return bool True only when the owned terminal transition and lease removal were verified.
 */
function ai4seo_apply_owned_bulk_generation_result_transition(
	int $post_id,
	array $destination_option_names,
	string $claim_token,
	array $removal_option_names = array()
): bool {
	$claim_token = trim( $claim_token );

	if ( '' === $claim_token || 128 < strlen( $claim_token ) ) {
		return false;
	}

	return ai4seo_apply_bulk_generation_result_transition( $post_id, $destination_option_names, $claim_token, $removal_option_names );
}


/**
 * Checks whether a pending bulk generation queue entry should be processed in force-overwrite mode.
 *
 * @param int    $post_id Post ID.
 * @param string $context Queue context.
 * @return bool Whether this queued entry has a force-overwrite marker.
 */
function ai4seo_is_bulk_generation_queue_entry_force_overwrite( int $post_id, string $context ): bool {
	$post_id       = absint( $post_id );
	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

	if ( ! $post_id || ! $queue_options || ! isset( $queue_options['pending'], $queue_options['force_overwrite'] ) ) {
		return false;
	}

	$pending_post_ids         = ai4seo_get_post_ids_from_option( $queue_options['pending'] );
	$force_overwrite_post_ids = ai4seo_get_post_ids_from_option( $queue_options['force_overwrite'] );

	return in_array( $post_id, $pending_post_ids, true )
		&& in_array( $post_id, $force_overwrite_post_ids, true );
}


/**
 * Returns the default result shape for bulk generation queue actions.
 *
 * @param string $action Queue action.
 * @param int    $selected_entries Number of selected entries.
 * @return array Default result counts.
 */
function ai4seo_get_bulk_generation_queue_action_result_defaults( string $action, int $selected_entries ): array {
	// Keep the result shape stable for AJAX and native table handlers while new action types add their own counters.
	return array(
		'action'                         => sanitize_key( $action ),
		'selected'                       => max( 0, absint( $selected_entries ) ),
		'changed'                        => 0,
		'not_applicable'                 => 0,
		'skipped'                        => max( 0, absint( $selected_entries ) ),
		'generated_data_deleted'         => 0,
		'active_metadata_deleted'        => 0,
		'related_source_entries_scanned' => 0,
		'related_images_found'           => 0,
		'related_images_changed'         => 0,
		'related_images_skipped'         => 0,
		'related_sources_without_images' => 0,
		'related_partial_scans'          => 0,
		'custom_instructions_cleared'    => false,
		'queue_count'                    => 0,
		'statistics_pending'             => false,
	);
}

/**
 * Resolve only the collections an action reads or changes, including derived memberships.
 *
 * @param string $action Bulk action.
 * @param string $context Queue context.
 * @return array Required option names, checked again inside the transition fence.
 */
function ai4seo_get_bulk_action_required_status_sources( string $action, string $context ): array {
	$options = ai4seo_get_bulk_generation_queue_options_by_context( $context );
	switch ( $action ) {
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE:
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE:
			$keys = array( 'pending', 'force_overwrite', 'processing', 'failed', 'hidden', 'auto_queue_disallowed', 'missing' );
			break;
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE_FORCE_OVERWRITE:
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE_FORCE_OVERWRITE:
			$keys = array( 'pending', 'force_overwrite', 'processing', 'failed', 'hidden' );
			break;
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_FROM_QUEUE:
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_RELATED_ATTACHMENTS_FROM_QUEUE:
			$keys = array( 'pending', 'force_overwrite' );
			break;
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_HIDE_ENTRY:
			$keys = array( 'hidden', 'pending', 'force_overwrite', 'missing' );
			break;
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_SHOW_ENTRY:
			$keys = array( 'hidden' );
			break;
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_DISALLOW_AUTO_QUEUE:
			$keys = array( 'auto_queue_disallowed', 'pending', 'force_overwrite', 'missing' );
			break;
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ALLOW_AUTO_QUEUE:
			$keys = array( 'auto_queue_disallowed' );
			break;
		default:
			$keys = array();
	}
	return array_values( array_intersect_key( $options, array_flip( $keys ) ) );
}

/**
 * Read one queue's operational readiness without repairing collections or ownership.
 *
 * @param string $context Queue context.
 * @return array Status, per-source diagnostics and known Pending count.
 */
function ai4seo_get_bulk_generation_context_readiness( string $context ): array {
	$options = ai4seo_get_bulk_generation_queue_options_by_context( $context );
	$result  = array(
		'status'          => 'ready',
		'blocked_sources' => array(),
		'pending_count'   => null,
		'owned_post_ids'  => array(),
	);
	if ( ! ai4seo_is_plugin_update_context_ready( $context, true ) ) {
		$result['status']            = 'context_blocked';
		$result['blocked_sources'][] = 'update_settings';
	}
	if ( ! $options ) {
		$result['status'] = 'system_error';
		return $result;
	}
	$settings_read_succeeded = false;
	if ( ! ai4seo_are_bulk_generation_fields_usable( $context, null, $settings_read_succeeded ) ) {
		$result['status']            = $settings_read_succeeded ? 'context_blocked' : 'system_error';
		$result['blocked_sources'][] = 'active_fields';
	}
	foreach ( array( 'pending', 'force_overwrite', 'processing', 'failed', 'hidden', 'auto_queue_disallowed' ) as $key ) {
		$inspection = ai4seo_inspect_analysis_post_id_option( $options[ $key ] );
		if ( ! $inspection['read_succeeded'] ) {
			$result['status'] = 'system_error';
		} elseif ( ! $inspection['complete'] && 'system_error' !== $result['status'] ) {
			$result['status'] = 'context_blocked';
		}
		if ( ! $inspection['read_succeeded'] || ! $inspection['complete'] ) {
			$result['blocked_sources'][] = $key;
		} elseif ( 'pending' === $key ) {
			$result['pending_count'] = count( $inspection['ids'] );
		}
	}
	$lease_option = ai4seo_get_processing_claim_lease_option_name( $options['processing'] );
	$snapshot     = ai4seo_get_raw_option_snapshot( $lease_option );
	$leases       = array();
	if ( null === $snapshot ) {
		$result['status']            = 'system_error';
		$result['blocked_sources'][] = 'processing_owners';
	} elseif ( $snapshot['exists'] && ( ! ai4seo_validate_processing_claim_leases( $snapshot['value'], $leases )
		|| ! ai4seo_validate_processing_claim_lease_context( $options['processing'], $leases ) ) ) {
		if ( 'system_error' !== $result['status'] ) {
			$result['status'] = 'context_blocked';
		}
		$result['blocked_sources'][] = 'processing_owners';
	}
	if ( $leases && ! ai4seo_are_processing_claim_deadlines_plausible( $leases ) ) {
		if ( 'system_error' !== $result['status'] ) {
			$result['status'] = 'context_blocked';
		}
		$result['blocked_sources'][] = 'processing_deadlines';
	}
	$result['owned_post_ids'] = array_keys( $leases );
	return $result;
}

/**
 * Distinguish a usable selection or explicit disable from unknown field settings.
 *
 * @param string     $context Queue context.
 * @param array|null $settings Fresh inspection, shared by fenced cleanup callers.
 * @param bool|null  $read_succeeded Distinguishes a storage error from damaged settings.
 * @return bool Whether the effective field selection can drive queue decisions.
 */
function ai4seo_are_bulk_generation_fields_usable( string $context, ?array $settings = null, ?bool &$read_succeeded = null ): bool {
	$read_succeeded = true;
	if ( null === $settings ) {
		$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_SETTINGS_OPTION_NAME );
		if ( null === $snapshot ) {
			$read_succeeded = false;
			return false;
		}
		$settings = ai4seo_inspect_settings( $snapshot );
	}
	$key = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context ? AI4SEO_SETTING_ACTIVE_META_TAGS : AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES;
	return $settings['readable'] && ( ! isset( $settings['issues'][ $key ] ) || ! empty( $settings['settings'][ $key ] ) );
}

/**
 * Explain queue restrictions using current storage, not a stale completed analysis.
 *
 * @param array|null $presentation Receives the severity and label shared by the displayed messages.
 * @return array Translated context-specific messages.
 */
function ai4seo_get_bulk_generation_queue_warnings( ?array &$presentation = null ): array {
	$warnings     = array();
	$analysis     = null;
	$presentation = array(
		'needs_attention' => false,
		'label'           => __( 'Automatic selection is in progress', 'ai-for-seo' ),
	);
	foreach ( ai4seo_get_bulk_generation_queue_contexts() as $context ) {
		$readiness = ai4seo_get_bulk_generation_context_readiness( $context );
		if ( 'ready' !== $readiness['status'] ) {
			$presentation['needs_attention'] = true;
			if ( in_array( 'active_fields', $readiness['blocked_sources'], true ) ) {
				$warnings[ $context ] = __( 'Processing is paused because the active field selection cannot be verified. Existing jobs are retained. Please review the active fields in Settings.', 'ai-for-seo' );
				continue;
			}
			if ( in_array( 'processing_deadlines', $readiness['blocked_sources'], true ) ) {
				$warnings[ $context ] = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context
					? __( 'Metadata processing is paused because an existing job has an implausible ownership expiry. The job is retained to prevent duplicate generation. Please contact support.', 'ai-for-seo' )
					: __( 'Media processing is paused because an existing job has an implausible ownership expiry. The job is retained to prevent duplicate generation. Please contact support.', 'ai-for-seo' );
				continue;
			}
			if ( in_array( 'update_settings', $readiness['blocked_sources'], true ) ) {
				$warnings[ $context ] = __( 'Processing is paused until the required update settings can be verified. Existing jobs are retained. See Help for update status.', 'ai-for-seo' );
				continue;
			}
			$warnings[ $context ] = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context
				? __( 'Metadata processing is paused because its queue data or processing ownership cannot be verified. Existing jobs are retained.', 'ai-for-seo' )
				: __( 'Media processing is paused because its queue data or processing ownership cannot be verified. Existing jobs are retained.', 'ai-for-seo' );
		} else {
			$analysis  = $analysis ?? ai4seo_get_posts_table_analysis_status();
			$selection = ai4seo_get_auto_queue_selection_status( $context, null, $analysis );
			if ( ! empty( $selection['message'] ) ) {
				$warnings[ $context ]            = $selection['message'];
				$presentation['needs_attention'] = $presentation['needs_attention'] || $selection['needs_attention'];
				if ( 'analysis_pending' === $selection['status'] ) {
					$presentation['label'] = 'deferred' === $analysis['status']
						? __( 'Analysis is paused', 'ai-for-seo' )
						: __( 'Analysis is being updated', 'ai-for-seo' );
				}
			}
		}
	}
	return $warnings;
}

/**
 * Read only the persisted rules needed by one automatic selection context.
 * Missing keys use declared defaults; damaged explicit choices never use defaults.
 *
 * @param string $context Queue context.
 * @return array Status, validated settings, date boundary and semantic fingerprint.
 */
function ai4seo_read_auto_queue_policy_snapshot( string $context ): array {
	$result = array(
		'status'      => 'system_error',
		'settings'    => array(),
		'fingerprint' => '',
		'reason'      => '',
	);
	if ( ! in_array( $context, ai4seo_get_bulk_generation_queue_contexts(), true ) ) {
		return $result;
	}
	$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_SETTINGS_OPTION_NAME );
	if ( null === $snapshot ) {
		return $result;
	}
	$stored           = ai4seo_decode_settings_snapshot( $snapshot );
	$result['status'] = 'filter_blocked';
	if ( ! is_array( $stored ) ) {
		$result['reason'] = 'settings';
		return $result;
	}
	$is_media = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context;
	$keys     = array(
		AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES,
		AI4SEO_SETTING_BULK_GENERATION_AUTO_QUEUE_ENTRIES,
		AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES,
		AI4SEO_SETTING_BULK_GENERATION_ORDER,
		AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER,
		$is_media ? AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS : AI4SEO_SETTING_DISABLED_POST_AUTHORS,
		$is_media ? AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES : AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES,
		$is_media ? AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES : AI4SEO_SETTING_ACTIVE_META_TAGS,
		$is_media ? AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES : AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES,
	);
	$keys     = array_merge(
		$keys,
		$is_media
			? array( AI4SEO_SETTING_ENABLED_ATTACHMENT_FILE_TYPES )
			: array(
				AI4SEO_SETTING_DISABLED_POST_TYPES,
				AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS,
				AI4SEO_SETTING_EXCLUDE_POSTS_IF_ANY_DISABLED_TAXONOMY_TERM,
			)
	);
	foreach ( $keys as $key ) {
		$value = array_key_exists( $key, $stored ) ? $stored[ $key ] : AI4SEO_DEFAULT_SETTINGS[ $key ];
		if ( AI4SEO_SETTING_ENABLED_ATTACHMENT_FILE_TYPES === $key && ! array_key_exists( $key, $stored ) ) {
			$previous_version = (string) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION );
			$defaults         = ai4seo_get_attachment_file_type_migration_values( $previous_version, $stored );
			$value            = $defaults[ $key ];
		}
		$value = ai4seo_normalize_boolean_setting_value( $key, $value );
		if ( AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS === $key && is_array( $value ) ) {
			foreach ( $value as $taxonomy => $term_ids ) {
				if ( ! is_string( $taxonomy ) || sanitize_key( $taxonomy ) !== $taxonomy || ! is_array( $term_ids ) ) {
					$result['reason'] = $key;
					return $result;
				}
				foreach ( $term_ids as $term_id ) {
					if ( false === ai4seo_normalize_option_post_id( $term_id ) ) {
						$result['reason'] = $key;
						return $result;
					}
				}
			}
		}
		// Reject nested or boolean ID values before the legacy validator can coerce them.
		if ( in_array( $key, array( AI4SEO_SETTING_DISABLED_POST_AUTHORS, AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS ), true ) && is_array( $value ) ) {
			foreach ( $value as $author_id ) {
				if ( false === ai4seo_normalize_option_post_id( $author_id ) ) {
					$result['reason'] = $key;
					return $result;
				}
			}
		}
		try {
			$valid = ai4seo_validate_setting_value( $key, $value );
		} catch ( Throwable $exception ) {
			$valid = false;
		}
		if ( ! $valid ) {
			$result['reason'] = $key;
			return $result;
		}
		// Match settings hydration only after rejecting malformed persisted filters.
		$result['settings'][ $key ] = ai4seo_deep_sanitize( $value );
		if ( AI4SEO_SETTING_BULK_GENERATION_AUTO_QUEUE_ENTRIES === $key && ! $value ) {
			$result['status'] = 'disabled';
			return $result;
		}
		if ( AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES === $key ) {
			$types = ai4seo_normalize_enabled_bulk_generation_post_types( $value );
			if ( ! $is_media && ai4seo_get_enabled_bulk_generation_taxonomies( $result['settings'] ) ) {
				$types[] = AI4SEO_TAXONOMY_PROXY_POST_TYPE;
			}
			if ( $is_media ? ! in_array( 'attachment', $types, true ) : ! array_diff( $types, array( 'attachment' ) ) ) {
				$result['status'] = 'disabled';
				return $result;
			}
		}
	}
	$filter = $result['settings'][ AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER ];
	if ( ! $result['settings'][ $is_media ? AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES : AI4SEO_SETTING_ACTIVE_META_TAGS ] ) {
		$result['status'] = 'disabled';
		return $result;
	}
	$reference = 0;
	if ( 'both' !== $filter ) {
		$environment = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
		if ( null === $environment ) {
			$result['status'] = 'system_error';
			return $result;
		}
		$values    = $environment['exists'] ? $environment['value'] : array();
		$reference = is_array( $values ) ? ( $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME ] ?? 0 ) : 0;
	}
	$result['date_filter'] = ai4seo_get_bulk_generation_date_filter_state( $filter, $reference );
	if ( ! $result['date_filter']['is_valid'] ) {
		$result['reason'] = 'date_reference';
		return $result;
	}
	// Candidate rows establish existence; a stale inventory cache must not hide a registered type.
	$result['post_types'] = $is_media ? ai4seo_get_supported_attachment_post_types( false ) : array_values(
		array_diff(
			array_intersect( array_keys( ai4seo_get_publicly_accessible_post_types() ), $types ),
			array_map( 'sanitize_key', $result['settings'][ AI4SEO_SETTING_DISABLED_POST_TYPES ] )
		)
	);
	if ( ! $is_media && ai4seo_get_enabled_bulk_generation_taxonomies( $result['settings'] ) ) {
		$result['post_types'][] = AI4SEO_TAXONOMY_PROXY_POST_TYPE;
	}
	$result['fingerprint'] = hash( 'sha256', wp_json_encode( array( $result['settings'], $result['date_filter'], $result['post_types'] ) ) );
	$result['status']      = 'ready';
	return $result;
}

/**
 * Explain live automatic-selection prerequisites without writing or repairing storage.
 *
 * @param string     $context Queue context.
 * @param array|null $policy Optional already-read policy.
 * @param array|null $analysis_status Optional shared analysis presentation snapshot.
 * @return array Structured status and user-facing reason.
 */
function ai4seo_get_auto_queue_selection_status( string $context, ?array $policy = null, ?array $analysis_status = null ): array {
	$policy = $policy ?? ai4seo_read_auto_queue_policy_snapshot( $context );
	$result = array(
		'status'           => $policy['status'],
		'reason'           => $policy['reason'],
		'message'          => '',
		'blocked_post_ids' => array(),
		'needs_attention'  => false,
	);
	if ( 'ready' === $result['status'] ) {
		$analysis_status = $analysis_status ?? ai4seo_get_posts_table_analysis_status();
		$analysis        = $analysis_status['readiness'];
		if ( empty( $analysis['read_succeeded'] ) ) {
			$result['status'] = 'system_error';
		} elseif ( empty( $analysis['contexts'][ $context ] ) ) {
			$result['status'] = $analysis['complete'] ? 'context_blocked' : 'analysis_pending';
		} else {
			// Completed analysis alone cannot prove that its derived candidate list still exists and is readable.
			$options    = ai4seo_get_bulk_generation_queue_options_by_context( $context );
			$snapshot   = ai4seo_get_raw_option_snapshot( $options['missing'] );
			$inspection = null === $snapshot ? null : ai4seo_inspect_analysis_post_id_option( $options['missing'], $snapshot );
			if ( null === $inspection || ! $inspection['read_succeeded'] ) {
				$result['status'] = 'system_error';
			} elseif ( ! $snapshot['exists'] || ! $inspection['complete'] ) {
				$result['status'] = 'analysis_pending';
			}
		}
	}
	if ( 'ready' === $result['status'] ) {
		$progress = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_QUEUE_SELECTION_PROGRESS, false );
		$previous = $progress[ $context ] ?? array();
		if ( ( $previous['policy'] ?? '' ) === $policy['fingerprint'] ) {
			// A verified current analysis supersedes an old wait without rewriting the selection checkpoint.
			$result['status']           = 'analysis_pending' === $previous['status'] ? 'ready' : $previous['status'];
			$result['blocked_post_ids'] = $previous['blocked_post_ids'];
		}
	}
	$label    = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context ? __( 'Metadata', 'ai-for-seo' ) : __( 'Media', 'ai-for-seo' );
	$messages = array(
		'filter_blocked'   => __( 'Automatic selection is paused because a saved filter cannot be verified. Review the Autopilot filters and exclusions. Existing jobs are retained; no filter values were changed.', 'ai-for-seo' ),
		'analysis_pending' => __( 'SEO Autopilot will continue automatically when the analysis is ready. Existing queued jobs can still be checked independently.', 'ai-for-seo' ),
		'system_error'     => __( 'Automatic selection could not read its required data. It will retry on a later scheduled run; this is not an empty selection.', 'ai-for-seo' ),
		'policy_changed'   => __( 'Automatic selection was deferred because its settings changed. The next scheduled run will use the current filters.', 'ai-for-seo' ),
		'deferred'         => __( 'Automatic selection is checking further candidates in bounded batches. No filter values are being changed.', 'ai-for-seo' ),
		'data_blocked'     => __( 'Some automatic candidates could not be evaluated. Their data is unchanged; other candidates are checked independently.', 'ai-for-seo' ),
		'context_blocked'  => __( 'Automatic selection is paused because required queue data cannot be verified. Existing jobs are retained.', 'ai-for-seo' ),
	);
	if ( 'analysis_pending' === $result['status'] ) {
		// Share one explanation across metadata and media; retain actual failures and intentional pauses.
		$result['needs_attention'] = 'failed' === $analysis_status['status'];
		$result['message']         = in_array( $analysis_status['status'], array( 'failed', 'deferred' ), true )
			? $analysis_status['message'] : $messages['analysis_pending'];
	} elseif ( isset( $messages[ $result['status'] ] ) ) {
		$result['message']         = $label . ': ' . $messages[ $result['status'] ];
		$result['needs_attention'] = ! in_array( $result['status'], array( 'policy_changed', 'deferred' ), true );
	} elseif ( $result['blocked_post_ids'] ) {
		$result['message']         = $label . ': ' . $messages['data_blocked'];
		$result['needs_attention'] = true;
	}
	return $result;
}

/**
 * Run one bounded selection pass and publish only derived progress and diagnostics.
 *
 * @param string $context Queue context.
 * @param array  $blocked_post_ids Pending owners verified as blocked by the current worker pass.
 * @return array Outcome used by workers, without conflating unknown data and an empty selection.
 */
function ai4seo_select_auto_queue_candidates( string $context, array $blocked_post_ids = array() ): array {
	$policy = ai4seo_read_auto_queue_policy_snapshot( $context );
	$result = ai4seo_find_auto_queue_candidates( $context, $policy, $blocked_post_ids );
	// Refresh only derived memberships when current source reads prove the index stale.
	// Publish this after admission so unrelated healthy candidates can progress in this pass.
	if ( ! empty( $result['rebuild_required'] ) || ( ! empty( $result['stale_index'] ) && in_array( $result['status'], array( 'queued', 'empty', 'data_blocked' ), true ) ) ) {
		$marked = ai4seo_mutate_environmental_variable_value(
			AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
			static function () {
				return 'required';
			},
			false,
			0,
			true
		);
		if ( ! $marked || ! ai4seo_schedule_generation_status_summary_rebuild( false ) ) {
			$result['status'] = 'system_error';
		} elseif ( 'empty' === $result['status'] ) {
			$result['status'] = 'analysis_pending';
		}
	} elseif ( 'analysis_pending' === $result['status'] && ! ai4seo_maybe_schedule_required_generation_status_summary_rebuild( true ) ) {
		$result['status'] = 'system_error';
	}
	$progress = array(
		'fingerprint'      => $result['fingerprint'] ?? '',
		'policy'           => $policy['fingerprint'],
		'offset'           => $result['offset'] ?? 0,
		'status'           => $result['status'],
		'checked_at'       => time(),
		'blocked_post_ids' => array_slice( array_values( array_unique( $result['blocked_post_ids'] ?? array() ) ), 0, 20 ),
		'stale_index'      => empty( $result['stale_index'] ) ? 0 : 1,
	);
	$saved    = ai4seo_mutate_environmental_variable_value(
		AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_QUEUE_SELECTION_PROGRESS,
		static function ( $current ) use ( $context, $progress ) {
			$current[ $context ] = $progress;
			return $current;
		},
		false,
		0,
		true
	);
	if ( ! $saved ) {
		$result['status'] = 'system_error';
	}
	return $result;
}

/**
 * Find and admit a small batch using one policy snapshot and existing fenced admission.
 *
 * @param string $context Queue context.
 * @param array  $policy Validated persisted rules.
 * @param array  $blocked_post_ids Verified unspent Pending blockers; never removals.
 * @return array Selection result and resumable offset.
 */
function ai4seo_find_auto_queue_candidates( string $context, array $policy, array $blocked_post_ids ): array {
	global $wpdb;
	// Preparation and sorting consume the same budget as candidate classification.
	$started = microtime( true );
	$result  = array(
		'status'           => $policy['status'],
		'blocked_post_ids' => array(),
		'offset'           => 0,
		'fingerprint'      => '',
	);
	if ( 'ready' !== $policy['status'] ) {
		return $result;
	}
	$result['status'] = 'system_error';
	$request_state    = ai4seo_classify_auto_queue_candidates( array(), $context, $policy );
	if ( 'ready' !== $request_state['status'] ) {
		$result['status'] = $request_state['status'];
		return $result;
	}
	// An unfinished scan may temporarily have no derived lists; resume it instead of repeatedly resetting it.
	$analysis = ai4seo_get_posts_table_analysis_readiness();
	if ( empty( $analysis['read_succeeded'] ) ) {
		return $result;
	}
	if ( empty( $analysis['contexts'][ $context ] ) ) {
		$result['status'] = $analysis['complete'] ? 'context_blocked' : 'analysis_pending';
		return $result;
	}
	$options     = ai4seo_get_bulk_generation_queue_options_by_context( $context );
	$memberships = array();
	foreach ( array( 'pending', 'processing', 'failed', 'hidden', 'auto_queue_disallowed', 'force_overwrite', 'missing' ) as $key ) {
		$snapshot = ai4seo_get_raw_option_snapshot( $options[ $key ] );
		if ( null === $snapshot ) {
			return $result;
		}
		$inspection = ai4seo_inspect_analysis_post_id_option( $options[ $key ], $snapshot );
		if ( ! $inspection['read_succeeded'] ) {
			return $result;
		}
		if ( ! $inspection['complete'] || ( 'missing' === $key && ! $snapshot['exists'] ) ) {
			$result['status']           = 'missing' === $key ? 'analysis_pending' : 'context_blocked';
			$result['rebuild_required'] = 'missing' === $key;
			return $result;
		}
		$memberships[ $key ] = $inspection['ids'];
	}
	$retained                   = array_intersect( $memberships['pending'], $blocked_post_ids );
	$result['blocked_post_ids'] = array_slice( array_values( $retained ), 0, 20 );
	$maximum_pending            = 2 + min( AI4SEO_AUTO_QUEUE_RETAINED_BLOCKER_LIMIT, count( $retained ) );
	if ( count( $memberships['pending'] ) >= $maximum_pending ) {
		$result['status'] = 'queue_full';
		return $result;
	}
	$is_media   = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context;
	$post_types = $policy['post_types'];
	$mime_types = $is_media ? ai4seo_get_enabled_attachment_mime_types() : array();
	if ( ! $post_types || ( $is_media && ! $mime_types ) ) {
		$result['status'] = 'empty';
		return $result;
	}
	$credits = $is_media ? ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post() : ai4seo_calculate_metadata_credits_cost_per_post();
	$account = ai4seo_get_account_readiness( true );
	if ( ! $account['credits_ready'] ) {
		$result['status'] = 'account_unavailable';
		return $result;
	}
	if ( $account['credits'] < $credits ) {
		$result['status'] = 'insufficient_credits';
		return $result;
	}
	$candidates        = array_values( array_diff( $memberships['missing'], $memberships['pending'], $memberships['processing'], $memberships['failed'], $memberships['hidden'], $memberships['auto_queue_disallowed'] ) );
	$order             = $policy['settings'][ AI4SEO_SETTING_BULK_GENERATION_ORDER ];
	$has_taxonomies    = ! $is_media && in_array( AI4SEO_TAXONOMY_PROXY_POST_TYPE, $post_types, true );
	$has_regular_types = (bool) array_diff( $post_types, array( AI4SEO_TAXONOMY_PROXY_POST_TYPE ) );
	if ( $has_taxonomies && ! $has_regular_types ) {
		$order = 'oldest';
	}
	// Fingerprint the source set before shuffling so random mode can resume its bounded scan.
	sort( $candidates, SORT_NUMERIC );
	// Both passes scan the full source set; classification separates posts from archives later.
	$oldest_candidates = $has_taxonomies ? $candidates : array();
	// A previous mixed-order offset must not skip candidates after the ordering policy changes.
	$result['fingerprint'] = hash( 'sha256', $policy['fingerprint'] . ( $has_taxonomies ? 'taxonomy-oldest-v1' : '' ) . wp_json_encode( $candidates ) );
	if ( 'newest' === $order ) {
		rsort( $candidates, SORT_NUMERIC );
	} elseif ( 'random' === $order ) {
		// Stable random ordering for this source set avoids revisiting the first batch forever.
		$seed        = hash( 'sha256', wp_json_encode( $candidates ) );
		$random_keys = array();
		foreach ( $candidates as $candidate_id ) {
			// Binary digests retain the hexadecimal ordering with less temporary storage.
			$random_keys[ $candidate_id ] = hash( 'sha256', $seed . $candidate_id, true );
		}
		asort( $random_keys, SORT_STRING );
		$candidates = array_keys( $random_keys );
		unset( $random_keys );
	}
	$candidate_count = count( $candidates );
	// One bounded scan selects ordinary posts; a second scans archive candidates oldest-first.
	$scan_count = $candidate_count * ( $has_taxonomies && $has_regular_types ? 2 : 1 );
	$progress   = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_QUEUE_SELECTION_PROGRESS, false );
	$previous   = $progress[ $context ] ?? array();
	$offset     = ( $previous['fingerprint'] ?? '' ) === $result['fingerprint'] ? (int) ( $previous['offset'] ?? 0 ) : 0;
	$offset     = $offset < $scan_count ? $offset : 0;
	if ( $offset ) {
		$result['blocked_post_ids'] = array_slice( array_values( array_unique( array_merge( $result['blocked_post_ids'], $previous['blocked_post_ids'] ) ) ), 0, 20 );
		$result['stale_index']      = ! empty( $previous['stale_index'] );
	}
	$chunk_size = min( AI4SEO_AUTO_QUEUE_SELECTION_CHUNK_SIZE, max( 1, ai4seo_get_database_chunk_size() ) );
	for ( $run = 0; $run < AI4SEO_AUTO_QUEUE_SELECTION_CHUNKS_PER_RUN && $offset < $scan_count; ++$run ) {
		if ( microtime( true ) - $started >= 5 ) {
			break;
		}
		$taxonomy_pass = $has_taxonomies && ( ! $has_regular_types || $offset >= $candidate_count );
		$pass_offset   = $taxonomy_pass && $has_regular_types ? $offset - $candidate_count : $offset;
		$pass_order    = $taxonomy_pass ? 'oldest' : $order;
		$chunk         = array_slice( $taxonomy_pass ? $oldest_candidates : $candidates, $pass_offset, $chunk_size );
		// The shared row classifier owns the date-source fallback for both analysis and selection.
		$query = $is_media
			? ai4seo_prepare_attachment_auto_queue_candidate_query( $chunk, $post_types, $mime_types, 'both', AI4SEO_MYSQL_DATETIME_MINIMUM, $pass_order, true )
			: ai4seo_prepare_metadata_auto_queue_candidate_query_for_post_types( $chunk, $post_types, 'both', AI4SEO_MYSQL_DATETIME_MINIMUM, $pass_order, true );
		if ( false === $query ) {
			return $result;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Existing typed candidate builder owns all bindings.
		$ids = $wpdb->get_col( $query );
		if ( $wpdb->last_error || ! is_array( $ids ) ) {
			return $result;
		}
		$seen = array();
		foreach ( $ids as $id ) {
			if ( false === ai4seo_normalize_database_id( $id ) || ! in_array( (int) $id, $chunk, true ) || isset( $seen[ (int) $id ] ) ) {
				return $result;
			}
			$seen[ (int) $id ] = true;
		}
		$ids                        = ai4seo_order_auto_queue_eligible_post_ids( $chunk, $ids, $pass_order );
		$classified                 = ai4seo_classify_auto_queue_candidates( $ids, $context, $policy );
		$result['stale_index']      = ! empty( $result['stale_index'] ) || count( $ids ) < count( $chunk ) || ! empty( $classified['stale_ids'] );
		$result['blocked_post_ids'] = array_slice( array_values( array_unique( array_merge( $result['blocked_post_ids'], $classified['blocked_post_ids'] ) ) ), 0, 20 );
		if ( 'ready' !== $classified['status'] ) {
			$result['status'] = $classified['status'];
			return $result;
		}
		if ( $has_taxonomies && $has_regular_types ) {
			// Skipping the other family is intentional and must not mark its healthy index entries stale.
			$classified['eligible_ids'] = array_values(
				$taxonomy_pass
					? array_intersect( $classified['eligible_ids'], $classified['taxonomy_ids'] )
					: array_diff( $classified['eligible_ids'], $classified['taxonomy_ids'] )
			);
		}
		if ( $classified['eligible_ids'] ) {
			$result['candidates_found'] = true;
			$admitted                   = ai4seo_add_bounded_auto_queue_post_ids_preserving_force_overwrite( array_slice( $classified['eligible_ids'], 0, 2 ), $context, $maximum_pending, $policy );
			if ( null === $admitted ) {
				$current          = ai4seo_read_auto_queue_policy_snapshot( $context );
				$result['status'] = 'ready' !== $current['status'] ? $current['status'] : ( $current['fingerprint'] !== $policy['fingerprint'] ? 'policy_changed' : 'system_error' );
				return $result;
			}
			if ( $admitted ) {
				$result['status'] = 'queued';
				return $result;
			}
		}
		$offset += count( $chunk );
	}
	$result['offset'] = $offset < $scan_count ? $offset : 0;
	$result['status'] = $offset < $scan_count ? 'deferred' : ( $result['blocked_post_ids'] ? 'data_blocked' : 'empty' );
	return $result;
}

/**
 * Recheck a bounded candidate set using the existing analysis and source readers.
 *
 * @param array  $post_ids Candidate identities, in selection order.
 * @param string $context Queue context.
 * @param array  $policy Authoritative selection policy.
 * @return array Status, eligible IDs, taxonomy identities and safely attributed blocked IDs.
 */
function ai4seo_classify_auto_queue_candidates( array $post_ids, string $context, array $policy ): array {
	global $wpdb;
	$result = array(
		'status'           => 'system_error',
		'eligible_ids'     => array(),
		'blocked_post_ids' => array(),
		'stale_ids'        => array(),
		'taxonomy_ids'     => array(),
	);
	// Shared readers use the request's settings. A concurrent save requires a fresh request,
	// never temporary global overrides that could affect another plugin callback.
	foreach ( $policy['settings'] as $key => $value ) {
		if ( ai4seo_deep_sanitize( ai4seo_normalize_boolean_setting_value( $key, ai4seo_get_setting( $key ) ) ) !== $value ) {
			$result['status'] = 'policy_changed';
			return $result;
		}
	}
	if ( ! $post_ids ) {
		$result['status'] = 'ready';
		return $result;
	}
	$query = ai4seo_prepare_database_query(
		'SELECT ID, post_author, post_type, post_status, post_mime_type, post_date_gmt, post_date, post_modified_gmt, post_modified FROM {{posts_table}} WHERE ID IN ({{post_ids}})',
		array(
			'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
			'post_ids'    => ai4seo_database_list_binding( '%d', $post_ids ),
		)
	);
	if ( false === $query ) {
		return $result;
	}
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Typed bounded identity query; admission must use current rows.
	$rows = $wpdb->get_results( $query, ARRAY_A );
	if ( $wpdb->last_error || ! is_array( $rows ) ) {
		return $result;
	}
	$is_media    = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context;
	$settings    = $policy['settings'];
	$types       = $is_media ? array() : $policy['post_types'];
	$media_types = $is_media ? $policy['post_types'] : array();
	$mimes       = $is_media ? ai4seo_get_enabled_attachment_mime_types() : array();
	$authors     = $settings[ $is_media ? AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS : AI4SEO_SETTING_DISABLED_POST_AUTHORS ];
	$languages   = $settings[ $is_media ? AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES : AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES ];
	$seen        = array();
	$eligible    = array();
	$archive_ids = array();
	foreach ( $rows as $row ) {
		$id = is_array( $row ) ? ai4seo_normalize_database_id( $row['ID'] ?? null ) : false;
		if ( false === $id || ! in_array( $id, $post_ids, true ) || isset( $seen[ $id ] ) ) {
			return $result;
		}
		$seen[ $id ] = true;
		if ( ! $is_media && AI4SEO_TAXONOMY_PROXY_POST_TYPE === $row['post_type'] ) {
			$archive_ids[] = $id;
			$archive       = ai4seo_get_taxonomy_generation_eligibility( $id, 'automatic', $settings );
			if ( ! is_wp_error( $archive ) ) {
				$eligible[] = $id;
			} elseif ( ! in_array( $archive->get_error_code(), array( 'taxonomy_generation_disabled', 'taxonomy_generation_unavailable', 'taxonomy_content_short', 'taxonomy_language_excluded' ), true ) ) {
				$result['blocked_post_ids'][] = $id;
			}
			continue;
		}
		$row_state = ai4seo_classify_posts_table_analysis_row( $row, $types, $media_types, $mimes, $policy['date_filter'] );
		if ( $row_state['field'] ) {
			$result['blocked_post_ids'][] = $id;
			continue;
		}
		if ( ! $row_state['eligible'] || ( $authors && in_array( (int) $row['post_author'], array_map( 'intval', $authors ), true ) ) || ! ai4seo_does_bulk_generation_date_filter_include_timestamp( $policy['date_filter'], $row_state['timestamp'] ) ) {
			continue;
		}
		if ( $languages ) {
			$wpdb->last_error = '';
			try {
				$language = ai4seo_get_post_language_code_by_multilanguage_plugins( $id );
			} catch ( Throwable $exception ) {
				return $result;
			}
			if ( $wpdb->last_error ) {
				return $result;
			}
			if ( '' === $language && ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WPML ) ) {
				$result['blocked_post_ids'][] = $id;
				continue;
			}
			if ( in_array( $language, $languages, true ) ) {
				continue;
			}
		}
		$eligible[] = $id;
	}
	$post_candidates = array_values( array_diff( $eligible, $archive_ids ) );
	if ( ! $is_media && $post_candidates && $settings[ AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS ] ) {
		$taxonomy_read_succeeded = false;
		$excluded                = ai4seo_get_post_ids_excluded_by_disabled_taxonomy_terms(
			$post_candidates,
			$settings[ AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS ],
			$settings[ AI4SEO_SETTING_EXCLUDE_POSTS_IF_ANY_DISABLED_TAXONOMY_TERM ],
			$taxonomy_read_succeeded
		);
		if ( ! $taxonomy_read_succeeded ) {
			return $result;
		}
		$eligible = array_values( array_diff( $eligible, $excluded ) );
	}
	if ( $eligible ) {
		$generated_data_read_succeeded = false;
		$failure                       = array();
		$issues                        = array();
		$generated                     = ai4seo_read_generated_data_post_ids_by_post_ids( $eligible, $generated_data_read_succeeded, false, $failure, $issues );
		if ( ! $generated_data_read_succeeded ) {
			return $result;
		}
		$coverage_ok = false;
		if ( $is_media ) {
			$coverage = ai4seo_read_and_analyse_attachment_attributes_coverage( $eligible, $coverage_ok, $issues );
			$coverage = ai4seo_get_attachment_attributes_coverage_summary( $coverage );
			$full     = count( ai4seo_get_active_attachment_attributes() );
		} else {
			$fields   = null;
			$coverage = ai4seo_read_percentage_of_available_metadata_by_post_ids( $eligible, 0, $coverage_ok, $fields, $issues );
			$full     = 100;
		}
		if ( ! $coverage_ok ) {
			return $result;
		}
		$result['blocked_post_ids'] = array_values( array_unique( array_merge( $result['blocked_post_ids'], array_column( $issues, 'post_id' ) ) ) );
		$include_complete           = $settings[ $is_media ? AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES : AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES ];
		foreach ( $eligible as $id ) {
			if ( ! in_array( $id, $result['blocked_post_ids'], true ) && isset( $coverage[ $id ] ) && ( $coverage[ $id ] < $full || ( $include_complete && ! in_array( $id, $generated, true ) ) ) ) {
				$result['eligible_ids'][] = $id;
			}
		}
	}
	$result['eligible_ids'] = array_values( array_intersect( $post_ids, $result['eligible_ids'] ) );
	$result['stale_ids']    = array_values( array_diff( $post_ids, $result['eligible_ids'], $result['blocked_post_ids'] ) );
	$result['taxonomy_ids'] = $archive_ids;
	$result['status']       = 'ready';
	return $result;
}

/**
 * Add trustworthy counts without changing the legacy numeric counter API.
 *
 * @param array $result Existing action result, or an empty status response.
 * @return array Response with nullable count and explicit completeness.
 */
function ai4seo_build_bulk_action_response( array $result ): array {
	$count    = 0;
	$complete = true;
	foreach ( ai4seo_get_bulk_generation_queue_contexts() as $context ) {
		$options    = ai4seo_get_bulk_generation_queue_options_by_context( $context );
		$inspection = ai4seo_inspect_analysis_post_id_option( $options['pending'] );
		$complete   = $complete && $inspection['complete'] && $inspection['read_succeeded'];
		$count     += count( $inspection['ids'] );
	}
	$result['queue_count']          = $complete ? $count : null;
	$result['queue_count_complete'] = $complete;
	return $result;
}

/**
 * Bound retries to distinct candidates while retaining the boolean worker contract.
 * Failed preflight claims restore Pending at the tail through the existing fenced transition.
 *
 * @param string     $context Queue context.
 * @param callable   $worker Single-owner worker.
 * @param bool       $debug Debug output.
 * @param int        $only_post_id Direct owner, or zero for queued work.
 * @param bool       $terminal_billing_error Account-scoped stop output.
 * @param array|null $attempt Status and bounded blocked IDs.
 * @return bool Whether one owner made verified progress.
 */
function ai4seo_run_bulk_generation_attempts( string $context, callable $worker, bool $debug, int $only_post_id, bool &$terminal_billing_error, ?array &$attempt ): bool {
	$terminal_billing_error = false;
	$excluded               = array();
	$blocked                = array();
	$started                = microtime( true );
	$refilled               = false;
	for ( $index = 0; $index < 10; ++$index ) {
		$attempt = array(
			'status'           => 'system_error',
			'post_id'          => 0,
			'blocked_post_ids' => $blocked,
		);
		if ( ! $only_post_id ) {
			$readiness = ai4seo_get_bulk_generation_context_readiness( $context );
			if ( 'ready' !== $readiness['status'] ) {
				if ( 'system_error' === $readiness['status'] && in_array( 'active_fields', $readiness['blocked_sources'], true ) ) {
					ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
				}
				$attempt = array_merge( $attempt, $readiness );
				return false;
			}
			// Recovery owns stale leases; an intact owner never consumes this pass's candidate budget.
			$excluded = array_values( array_unique( array_merge( $excluded, $readiness['owned_post_ids'] ) ) );
		}
		$success = $worker( $debug, $only_post_id, $terminal_billing_error, $attempt, $excluded );
		if ( $success ) {
			$attempt['status'] = 'processed';
			return true;
		}
		if ( ! $only_post_id && ! $terminal_billing_error && $blocked && ! $refilled && 'empty' === $attempt['status'] && microtime( true ) - $started < 5 ) {
			$refilled  = true;
			$selection = ai4seo_select_auto_queue_candidates( $context, $blocked );
			if ( 'queued' === $selection['status'] ) {
				continue;
			}
			if ( ! in_array( $selection['status'], array( 'empty', 'disabled', 'queue_full', 'insufficient_credits' ), true ) ) {
				$attempt['status'] = $selection['status'];
			}
		}
		if ( $terminal_billing_error || $only_post_id || ! in_array( $attempt['status'], array( 'data_blocked', 'temporarily_busy' ), true ) ) {
			if ( $blocked && 'empty' === $attempt['status'] ) {
				$attempt['status'] = 'data_blocked';
			}
			return false;
		}
		$excluded[] = $attempt['post_id'];
		if ( 'data_blocked' === $attempt['status'] ) {
			$blocked[] = $attempt['post_id'];
		}
		$attempt['blocked_post_ids'] = $blocked;
		if ( microtime( true ) - $started >= 5 ) {
			break;
		}
	}
	// A full pass of retained owners still needs a bounded admission opportunity.
	if ( ! $only_post_id && ! $terminal_billing_error && $blocked && ! $refilled && microtime( true ) - $started < 5 ) {
		$selection = ai4seo_select_auto_queue_candidates( $context, $blocked );
		if ( ! in_array( $selection['status'], array( 'empty', 'disabled', 'queue_full', 'insufficient_credits' ), true ) ) {
			$attempt['status'] = $selection['status'];
		}
	}
	return false;
}

/**
 * Retain an unspent job; only positively identified source defects are skippable.
 *
 * @param string $context Queue context.
 * @param int    $post_id Owner.
 * @param string $claim_token Exact claim token.
 * @param bool   $pending_was_present Captured Pending intent.
 * @param bool   $force_was_present Captured Force intent.
 * @param bool   $data_blocked Whether a source defect was safely attributed.
 * @param string $reason Diagnostic without customer field values.
 * @param array  $attempt Structured output.
 * @return bool False, preserving the boolean failure contract.
 */
function ai4seo_finish_blocked_bulk_generation_attempt( string $context, int $post_id, string $claim_token, bool $pending_was_present, bool $force_was_present, bool $data_blocked, string $reason, array &$attempt ): bool {
	$restored          = ai4seo_abort_bulk_generation_processing_claim_before_generation( $context, $post_id, $claim_token, $pending_was_present, $force_was_present );
	$attempt['status'] = $restored && $data_blocked ? 'data_blocked' : 'system_error';
	$attempt['reason'] = $reason;
	if ( $restored && $data_blocked ) {
		$action = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context ? 'metadata-bulk-generated' : 'attachment-attributes-bulk-generated';
		ai4seo_add_latest_activity_entry( $post_id, 'error', $action, 0, $reason );
	}
	return false;
}


/**
 * Applies the bulk custom-instructions action to selected valid entries.
 *
 * @param array  $post_ids Selected post IDs.
 * @param string $context Queue context.
 * @param mixed  $custom_instructions Raw custom-instructions value.
 * @return array Result counts.
 */
function ai4seo_process_bulk_custom_instructions_action( array $post_ids, string $context, $custom_instructions ): array {
	$context          = sanitize_key( $context );
	$post_ids         = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$selected_entries = count( $post_ids );
	$result           = ai4seo_get_bulk_generation_queue_action_result_defaults(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_SET_CUSTOM_INSTRUCTIONS,
		$selected_entries
	);

	if ( ! ai4seo_is_bulk_generation_queue_context( $context ) ) {
		return $result;
	}

	// Resolve the same entry-level postmeta key used by the Metadata and Media Attributes editors.
	$meta_key = ai4seo_get_custom_instructions_postmeta_key_by_bulk_generation_queue_context( $context );

	if ( '' === $meta_key ) {
		return $result;
	}

	// Reuse the same validity rules as queue actions so unsupported/deleted rows are skipped consistently.
	$valid_post_ids = ai4seo_get_valid_bulk_generation_queue_post_ids( $post_ids, $context );

	if ( ! $valid_post_ids ) {
		return $result;
	}

	// Save one normalized value across the valid batch; empty strings clear existing entry-level instructions.
	$save_result     = ai4seo_save_custom_instructions_postmeta_for_post_ids(
		$valid_post_ids,
		$meta_key,
		$custom_instructions
	);
	$changed_entries = count( $save_result['saved_post_ids'] );

	// "Changed" means the valid entry was successfully saved or cleared, even when the stored value was unchanged.
	$result['changed']                     = $changed_entries;
	$result['skipped']                     = max( 0, $selected_entries - $changed_entries );
	$result['custom_instructions_cleared'] = ! empty( $save_result['custom_instructions_cleared'] );

	return $result;
}


/**
 * Applies a related-image bulk action from selected source posts to the attachment queue.
 *
 * @param string    $action Queue action.
 * @param array     $source_post_ids Selected source post IDs.
 * @param bool|null $queue_transition_failed Receives whether a requested queue-state transition failed.
 * @return array Result counts.
 */
function ai4seo_process_related_attachment_bulk_generation_queue_action( string $action, array $source_post_ids, ?bool &$queue_transition_failed = null ): array {
	$action                  = sanitize_key( $action );
	$source_post_ids         = array_values( array_unique( array_filter( array_map( 'absint', $source_post_ids ) ) ) );
	$queue_transition_failed = false;

	// Remove actions share the scanner but intentionally skip add-only fallback writes and cron injection.
	$is_related_attachment_remove_queue_action = ai4seo_is_related_attachment_remove_bulk_generation_queue_action( $action );

	// Related-image actions count selected source posts first; skipped is recalculated against discovered images later.
	$result            = ai4seo_get_bulk_generation_queue_action_result_defaults( $action, count( $source_post_ids ) );
	$result['skipped'] = 0;

	if ( ! ai4seo_is_related_attachment_bulk_generation_queue_action( $action ) ) {
		$result['queue_count'] = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}
	foreach ( ai4seo_get_bulk_action_required_status_sources( $action, AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES ) as $option_name ) {
		$inspection = ai4seo_inspect_analysis_post_id_option( $option_name );
		if ( ! $inspection['read_succeeded'] || ! $inspection['complete'] ) {
			$queue_transition_failed = true;
			return $result;
		}
	}

	// Source entries use the metadata context; the discovered media IDs are queued in the attachment context later.
	$valid_source_post_ids                    = ai4seo_get_valid_bulk_generation_queue_post_ids(
		$source_post_ids,
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA
	);
	$result['related_source_entries_scanned'] = count( $valid_source_post_ids );

	if ( ! $valid_source_post_ids ) {
		$result['queue_count'] = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}
	if ( ! $is_related_attachment_remove_queue_action ) {
		$settings_read_succeeded = false;
		$enabled_post_types      = ai4seo_read_persisted_enabled_bulk_generation_post_types( $settings_read_succeeded );
		if ( ! $settings_read_succeeded ) {
			$queue_transition_failed = true;
			return $result;
		}
		if ( ! in_array( 'attachment', $enabled_post_types, true ) ) {
			$queue_transition_failed         = true;
			$result['content_type_disabled'] = true;
			$result['error_message']         = __( 'Select Media in SEO Autopilot before adding related images to the queue. No entries were added.', 'ai-for-seo' );
			return $result;
		}
	}

	$related_attachment_post_id_lookup             = array();
	$related_attachment_post_ids_by_source_post_id = array();

	// Scan source posts with the existing Related Media scanner and keep first-source fallback ownership for duplicates.
	foreach ( $valid_source_post_ids as $this_source_post_id ) {
		$this_related_attachment_scan_result = ai4seo_get_related_attachment_scan_result( $this_source_post_id );
		$this_related_attachment_post_ids    = array_values(
			array_unique(
				array_filter(
					array_map(
						'absint',
						(array) ( $this_related_attachment_scan_result['attachment_post_ids'] ?? array() )
					)
				)
			)
		);

		if ( ! empty( $this_related_attachment_scan_result['is_partial'] ) ) {
			++$result['related_partial_scans'];
		}

		if ( ! $this_related_attachment_post_ids ) {
			++$result['related_sources_without_images'];
			continue;
		}

		// First selected source post wins, matching the plan while avoiding repeated fallback rewrites.
		foreach ( $this_related_attachment_post_ids as $this_related_attachment_post_id ) {
			if ( isset( $related_attachment_post_id_lookup[ $this_related_attachment_post_id ] ) ) {
				continue;
			}

			$related_attachment_post_id_lookup[ $this_related_attachment_post_id ] = $this_related_attachment_post_id;

			if ( ! isset( $related_attachment_post_ids_by_source_post_id[ $this_source_post_id ] ) ) {
				$related_attachment_post_ids_by_source_post_id[ $this_source_post_id ] = array();
			}

			$related_attachment_post_ids_by_source_post_id[ $this_source_post_id ][] = $this_related_attachment_post_id;
		}
	}

	// Use the deduplicated discovery set for both the result count and the per-attachment authorization pass.
	$discovered_related_attachment_post_ids = array_values( $related_attachment_post_id_lookup );
	$result['related_images_found']         = count( $discovered_related_attachment_post_ids );

	// Source-post access does not grant access to media owned by another user, so authorize every discovered target.
	$related_attachment_post_ids = ai4seo_filter_editable_post_ids( $discovered_related_attachment_post_ids );
	$editable_attachment_lookup  = array_fill_keys( $related_attachment_post_ids, true );

	// Keep fallback ownership groups aligned with the authorized attachment set before any postmeta write occurs.
	foreach ( $related_attachment_post_ids_by_source_post_id as $this_source_post_id => $this_related_attachment_post_ids ) {
		$this_related_attachment_post_ids = array_values(
			array_filter(
				$this_related_attachment_post_ids,
				function ( int $attachment_post_id ) use ( $editable_attachment_lookup ): bool {
					return isset( $editable_attachment_lookup[ $attachment_post_id ] );
				}
			)
		);

		if ( $this_related_attachment_post_ids ) {
			$related_attachment_post_ids_by_source_post_id[ $this_source_post_id ] = $this_related_attachment_post_ids;
			continue;
		}

		unset( $related_attachment_post_ids_by_source_post_id[ $this_source_post_id ] );
	}

	if ( ! $related_attachment_post_ids ) {
		$result['related_images_skipped'] = $result['related_images_found'];
		$result['skipped']                = $result['related_images_skipped'];
		$result['queue_count']            = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}

	if ( ! $is_related_attachment_remove_queue_action ) {
		// Add actions persist fallback values in the same grouped shape used by the single-entry Related Media flow.
		foreach ( $related_attachment_post_ids_by_source_post_id as $this_source_post_id => $this_related_attachment_post_ids ) {
			if ( ai4seo_update_attachment_related_post_id_for_attachment_post_ids( $this_related_attachment_post_ids, (int) $this_source_post_id ) ) {
				continue;
			}

			ai4seo_debug_message(
				4527052603,
				'Could not update related post fallback values for related image bulk action source post ID ' . absint( $this_source_post_id ),
				true
			);
		}
	}

	// Attachment queue state is read once so large bulk actions do not repeat option reads for each source post.
	$attachment_queue_options = ai4seo_get_bulk_generation_queue_options_by_context( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES );

	if ( ! $attachment_queue_options ) {
		$result['related_images_skipped'] = $result['related_images_found'];
		$result['skipped']                = $result['related_images_skipped'];
		$result['queue_count']            = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}

	// Validate discovered IDs as attachment queue entries before applying the previously-read queue state.
	$valid_related_attachment_post_ids = ai4seo_get_valid_bulk_generation_queue_post_ids(
		$related_attachment_post_ids,
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES
	);
	if ( ! $is_related_attachment_remove_queue_action ) {
		$valid_related_attachment_post_ids = ai4seo_filter_generation_enabled_attachment_ids( $valid_related_attachment_post_ids );
	}

	// Pending and Force mode are shared by all related-image queue mutations.
	$pending_attachment_post_ids          = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['pending'] ) );
	$force_overwrite_attachment_post_ids  = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['force_overwrite'] ) );
	$changed_attachment_post_ids          = array();
	$transition_additions                 = array();
	$transition_removals                  = array();
	$should_schedule_bulk_generation      = false;
	$should_filter_processing_memberships = false;

	switch ( $action ) {
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE:
			// Soft mode needs applicability queues to match Add to queue (safe) for attachment attributes.
			$missing_attachment_post_ids               = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['missing'] ) );
			$processing_attachment_post_ids            = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['processing'] ) );
			$failed_attachment_post_ids                = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['failed'] ) );
			$hidden_attachment_post_ids                = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['hidden'] ) );
			$auto_queue_disallowed_attachment_post_ids = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['auto_queue_disallowed'] ) );
			$applicable_attachment_post_ids            = array_values(
				array_unique(
					array_merge(
						$missing_attachment_post_ids,
						$failed_attachment_post_ids,
						$auto_queue_disallowed_attachment_post_ids
					)
				)
			);
			$applicable_attachment_post_ids            = array_values( array_diff( $applicable_attachment_post_ids, $hidden_attachment_post_ids ) );
			$applicable_attachment_post_ids            = array_values( array_intersect( $valid_related_attachment_post_ids, $applicable_attachment_post_ids ) );

			// Soft mode mirrors Add to queue (safe): queue applicable media only and leave Processing entries untouched.
			$changed_attachment_post_ids = array_values(
				array_diff(
					$applicable_attachment_post_ids,
					$pending_attachment_post_ids,
					$processing_attachment_post_ids
				)
			);

			if ( $changed_attachment_post_ids ) {
				$transition_additions                 = array(
					$attachment_queue_options['pending'] => $changed_attachment_post_ids,
				);
				$transition_removals                  = array(
					$attachment_queue_options['failed'] => $changed_attachment_post_ids,
					$attachment_queue_options['force_overwrite'] => $changed_attachment_post_ids,
				);
				$should_schedule_bulk_generation      = true;
				$should_filter_processing_memberships = true;
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE_FORCE_OVERWRITE:
			// Force mode ignores saved values but still respects active processing and hidden media.
			$processing_attachment_post_ids            = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['processing'] ) );
			$failed_attachment_post_ids                = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['failed'] ) );
			$hidden_attachment_post_ids                = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['hidden'] ) );
			$candidate_attachment_post_ids             = array_values(
				array_diff(
					$valid_related_attachment_post_ids,
					$processing_attachment_post_ids,
					$hidden_attachment_post_ids
				)
			);
			$pending_attachment_post_id_lookup         = array_flip( $pending_attachment_post_ids );
			$force_overwrite_attachment_post_id_lookup = array_flip( $force_overwrite_attachment_post_ids );
			$failed_attachment_post_id_lookup          = array_flip( $failed_attachment_post_ids );

			// Force mode can promote Pending media, but it must not modify Processing or Hidden media.
			$changed_attachment_post_ids = array_values(
				array_filter(
					$candidate_attachment_post_ids,
					function ( $attachment_post_id ) use ( $pending_attachment_post_id_lookup, $force_overwrite_attachment_post_id_lookup, $failed_attachment_post_id_lookup ) {
						return ! isset( $pending_attachment_post_id_lookup[ $attachment_post_id ] )
							|| ! isset( $force_overwrite_attachment_post_id_lookup[ $attachment_post_id ] )
							|| isset( $failed_attachment_post_id_lookup[ $attachment_post_id ] );
					}
				)
			);

			if ( $changed_attachment_post_ids ) {
				$transition_additions                 = array(
					$attachment_queue_options['pending'] => $changed_attachment_post_ids,
					$attachment_queue_options['force_overwrite'] => $changed_attachment_post_ids,
				);
				$transition_removals                  = array(
					$attachment_queue_options['failed'] => $changed_attachment_post_ids,
				);
				$should_schedule_bulk_generation      = true;
				$should_filter_processing_memberships = true;
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_RELATED_ATTACHMENTS_FROM_QUEUE:
			// Remove mode only changes Pending queue membership and clears matching Force mode markers.
			$changed_attachment_post_ids = array_values(
				array_intersect(
					$valid_related_attachment_post_ids,
					$pending_attachment_post_ids
				)
			);

			if ( $changed_attachment_post_ids ) {
				$transition_removals = array(
					$attachment_queue_options['pending'] => $changed_attachment_post_ids,
					$attachment_queue_options['force_overwrite'] => $changed_attachment_post_ids,
				);
			}
			break;
	}

	if ( $changed_attachment_post_ids && ( $transition_additions || $transition_removals ) ) {
		$transition_did_change     = false;
		$transition_succeeded      = false;
		$required_complete_options = ai4seo_get_bulk_action_required_status_sources( $action, AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES );

		if ( $should_filter_processing_memberships ) {
			$admitted_attachment_post_ids = array();
			$transition_succeeded         = ai4seo_apply_post_id_option_admission_transition(
				$transition_additions,
				$transition_removals,
				array(
					$attachment_queue_options['processing'],
					$attachment_queue_options['hidden'],
				),
				$admitted_attachment_post_ids,
				$transition_did_change,
				$required_complete_options
			);
			$changed_attachment_post_ids  = $transition_succeeded ? $admitted_attachment_post_ids : array();
		} else {
			$transition_succeeded = ai4seo_apply_post_id_option_transition(
				$transition_additions,
				$transition_removals,
				$transition_did_change,
				$required_complete_options
			);
		}

		if ( ! $transition_succeeded ) {
			$queue_transition_failed         = true;
			$changed_attachment_post_ids     = array();
			$should_schedule_bulk_generation = false;

			ai4seo_debug_message( 836122082, 'Could not apply the selected related-image queue transition.', true );
		} elseif ( $should_schedule_bulk_generation && $changed_attachment_post_ids ) {
			// Schedule generation only after every related-image membership is verified in fresh storage.
			ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
		}
	}

	// Return both generic queue counts and related-image-specific counts for native notices and AJAX toasts.
	$result['changed']                = count( $changed_attachment_post_ids );
	$result['related_images_changed'] = $result['changed'];
	$result['related_images_skipped'] = max( 0, $result['related_images_found'] - $result['related_images_changed'] );
	$result['skipped']                = $result['related_images_skipped'];
	$result['queue_count']            = ai4seo_get_bulk_generation_queue_count();

	return $result;
}


/**
 * Applies a bulk generation queue action to selected post IDs.
 *
 * @param string    $action Queue action.
 * @param array     $post_ids Selected post IDs.
 * @param string    $context Queue context.
 * @param bool|null $queue_transition_failed Receives whether a requested queue-state transition failed.
 * @return array Result counts.
 */
function ai4seo_process_bulk_generation_queue_action( string $action, array $post_ids, string $context, ?bool &$queue_transition_failed = null ): array {
	$action           = sanitize_key( $action );
	$context          = sanitize_key( $context );
	$post_ids         = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$selected_entries = count( $post_ids );
	if ( ! array_key_exists( $action, ai4seo_get_bulk_generation_queue_actions( 'custom', 'all', 'metadata', 'taxonomy' ) ) ) {
		$post_ids = array_values(
			array_filter(
				$post_ids,
				static function ( $id ): bool {
					return ! ai4seo_is_taxonomy_proxy( $id );
				}
			)
		);
	}
	$result                                  = ai4seo_get_bulk_generation_queue_action_result_defaults( $action, $selected_entries );
	$changed_post_ids                        = array();
	$not_applicable_entries                  = 0;
	$generated_data_deleted_entries          = 0;
	$active_metadata_deleted_entries         = 0;
	$should_refresh_posts_table_analysis     = false;
	$should_schedule_bulk_generation         = false;
	$should_filter_processing_memberships    = false;
	$transition_additions                    = array();
	$transition_removals                     = array();
	$queue_transition_failed                 = false;
	$irreversible_postmeta_deletion_post_ids = array();
	$postmeta_read_failed                    = false;
	$postmeta_deletion_failed                = false;

	if ( ! ai4seo_is_bulk_generation_queue_action( $action ) || ! ai4seo_is_bulk_generation_queue_context( $context ) ) {
		$result['queue_count'] = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}

	// Related-image actions originate from metadata tables but mutate attachment queue state in a dedicated processor.
	if ( ai4seo_is_related_attachment_bulk_generation_queue_action( $action ) ) {
		if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA !== $context ) {
			$result['queue_count'] = ai4seo_get_bulk_generation_queue_count();
			return $result;
		}

		return ai4seo_process_related_attachment_bulk_generation_queue_action( $action, $post_ids, $queue_transition_failed );
	}

	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

	if ( ! $queue_options ) {
		$result['queue_count'] = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}

	$valid_post_ids            = ai4seo_get_valid_bulk_generation_queue_post_ids( $post_ids, $context );
	$required_complete_options = ai4seo_get_bulk_action_required_status_sources( $action, $context );
	if ( $valid_post_ids && in_array( $action, array( AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE, AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE_FORCE_OVERWRITE ), true ) ) {
		// Match the worker's persisted selection before reporting a durable queue admission.
		$settings_read_succeeded = false;
		$settings_snapshot       = null;
		$enabled_post_types      = ai4seo_read_persisted_enabled_bulk_generation_post_types( $settings_read_succeeded, $settings_snapshot );
		if ( ! $settings_read_succeeded ) {
			$queue_transition_failed = true;
			return $result;
		}
		$taxonomy_settings = ai4seo_decode_settings_snapshot( $settings_snapshot );
		foreach ( $valid_post_ids as $index => $post_id ) {
			if ( ! ai4seo_is_taxonomy_proxy( $post_id ) ) {
				if ( ! in_array( get_post_type( $post_id ), $enabled_post_types, true ) ) {
					$queue_transition_failed         = true;
					$result['content_type_disabled'] = true;
					$result['error_message']         = __( 'Select the content types of the selected entries in SEO Autopilot before adding them to the queue. No entries were added.', 'ai-for-seo' );
					return $result;
				}
				continue;
			}
			$archive = ai4seo_get_taxonomy_generation_eligibility( $post_id );
			if ( is_wp_error( $archive ) ) {
				$queue_transition_failed = true;
				return $result;
			}
			if ( ! in_array( $archive['taxonomy'], ai4seo_get_enabled_bulk_generation_taxonomies( $taxonomy_settings ), true ) ) {
				$queue_transition_failed = true;
				$result['error_message'] = __( 'Select the taxonomies of the selected archives in SEO Autopilot before adding them to the queue. No entries were added.', 'ai-for-seo' );
				return $result;
			}
			if ( ! $archive['has_sufficient_content'] ) {
				unset( $valid_post_ids[ $index ] );
			}
		}
		$valid_post_ids = array_values( $valid_post_ids );
	}
	foreach ( $required_complete_options as $option_name ) {
		$inspection = ai4seo_inspect_analysis_post_id_option( $option_name );
		if ( ! $inspection['complete'] || ! $inspection['read_succeeded'] ) {
			$queue_transition_failed = true;
			return $result;
		}
	}
	if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context
		&& in_array( $action, array( AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE, AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE_FORCE_OVERWRITE ), true ) ) {
		$valid_post_ids = ai4seo_filter_generation_enabled_attachment_ids( $valid_post_ids );
	}
	$pending_post_ids               = ai4seo_get_post_ids_from_option( $queue_options['pending'] );
	$processing_post_ids            = ai4seo_get_post_ids_from_option( $queue_options['processing'] );
	$force_overwrite_post_ids       = ai4seo_get_post_ids_from_option( $queue_options['force_overwrite'] );
	$hidden_post_ids                = ai4seo_get_post_ids_from_option( $queue_options['hidden'] );
	$auto_queue_disallowed_post_ids = ai4seo_get_post_ids_from_option( $queue_options['auto_queue_disallowed'] );

	switch ( $action ) {
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE:
			$applicable_post_ids    = ai4seo_get_applicable_bulk_generation_queue_post_ids( $valid_post_ids, $context );
			$not_applicable_entries = count( array_diff( $valid_post_ids, $applicable_post_ids ) );

			// Processing entries are active work and must never be moved back to Pending by a manual safe action.
			$changed_post_ids = array_values( array_diff( $applicable_post_ids, $pending_post_ids, $processing_post_ids ) );

			if ( $changed_post_ids ) {
				$transition_additions                 = array(
					$queue_options['pending'] => $changed_post_ids,
				);
				$transition_removals                  = array(
					$queue_options['force_overwrite'] => $changed_post_ids,
					$queue_options['failed']          => $changed_post_ids,
				);
				$should_schedule_bulk_generation      = true;
				$should_filter_processing_memberships = true;
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE_FORCE_OVERWRITE:
			// Retry incomplete Failed cleanup even when Pending and Force were already saved.
			$failed_post_ids = ai4seo_get_post_ids_from_option( $queue_options['failed'] );

			// Force-overwrite can promote already Pending entries, but must not touch Processing entries.
			$candidate_post_ids = array_values( array_diff( $valid_post_ids, $processing_post_ids, $hidden_post_ids ) );
			$changed_post_ids   = array_values(
				array_filter(
					$candidate_post_ids,
					function ( $post_id ) use ( $pending_post_ids, $force_overwrite_post_ids, $failed_post_ids ) {
						return ! in_array( $post_id, $pending_post_ids, true )
							|| ! in_array( $post_id, $force_overwrite_post_ids, true )
							|| in_array( $post_id, $failed_post_ids, true );
					}
				)
			);

			if ( $changed_post_ids ) {
				$transition_additions                 = array(
					$queue_options['pending']         => $changed_post_ids,
					$queue_options['force_overwrite'] => $changed_post_ids,
				);
				$transition_removals                  = array(
					$queue_options['failed'] => $changed_post_ids,
				);
				$should_schedule_bulk_generation      = true;
				$should_filter_processing_memberships = true;
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_FROM_QUEUE:
			$changed_post_ids = array_values( array_intersect( $valid_post_ids, $pending_post_ids ) );

			if ( $changed_post_ids ) {
				$transition_removals = array(
					$queue_options['pending']         => $changed_post_ids,
					$queue_options['force_overwrite'] => $changed_post_ids,
				);
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_HIDE_ENTRY:
			$changed_post_ids = array_values( array_diff( $valid_post_ids, $hidden_post_ids ) );

			if ( $changed_post_ids ) {
				$transition_additions = array(
					$queue_options['hidden'] => $changed_post_ids,
				);
				$transition_removals  = array(
					$queue_options['pending']         => $changed_post_ids,
					$queue_options['force_overwrite'] => $changed_post_ids,
					$queue_options['missing']         => $changed_post_ids,
				);
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_SHOW_ENTRY:
			$changed_post_ids = array_values( array_intersect( $valid_post_ids, $hidden_post_ids ) );

			if ( $changed_post_ids ) {
				$transition_removals                 = array(
					$queue_options['hidden'] => $changed_post_ids,
				);
				$should_refresh_posts_table_analysis = true;
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_DISALLOW_AUTO_QUEUE:
			$changed_post_ids = array_values( array_diff( $valid_post_ids, $auto_queue_disallowed_post_ids ) );

			if ( $changed_post_ids ) {
				$transition_additions = array(
					$queue_options['auto_queue_disallowed'] => $changed_post_ids,
				);
				$transition_removals  = array(
					$queue_options['pending']         => $changed_post_ids,
					$queue_options['force_overwrite'] => $changed_post_ids,
					$queue_options['missing']         => $changed_post_ids,
				);
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ALLOW_AUTO_QUEUE:
			$changed_post_ids = array_values( array_intersect( $valid_post_ids, $auto_queue_disallowed_post_ids ) );

			if ( $changed_post_ids ) {
				$transition_removals                 = array(
					$queue_options['auto_queue_disallowed'] => $changed_post_ids,
				);
				$should_refresh_posts_table_analysis = true;
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_GENERATED_DATA:
			$generated_data_post_ids    = ai4seo_read_post_ids_with_postmeta_key( $valid_post_ids, AI4SEO_POST_META_GENERATED_DATA_META_KEY, $postmeta_read_failed );
			$generated_data_option_name = ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context )
				? AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME
				: AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;

			if ( $postmeta_read_failed ) {
				break;
			}

			if ( $generated_data_post_ids ) {
				$possibly_deleted_post_ids = array();
				$deletion_succeeded        = ai4seo_delete_postmeta_for_post_ids_and_meta_key(
					$generated_data_post_ids,
					AI4SEO_POST_META_GENERATED_DATA_META_KEY,
					$possibly_deleted_post_ids
				);

				$changed_post_ids                        = $possibly_deleted_post_ids;
				$irreversible_postmeta_deletion_post_ids = $possibly_deleted_post_ids;
				$generated_data_deleted_entries          = count( $possibly_deleted_post_ids );

				if ( $deletion_succeeded ) {
					$should_refresh_posts_table_analysis = true;
					$transition_removals                 = array(
						$generated_data_option_name => $possibly_deleted_post_ids,
					);
				} else {
					$postmeta_deletion_failed = true;
				}
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_SAVED_DATA:
			if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA !== $context ) {
				break;
			}

			$active_metadata_post_ids = ai4seo_read_post_ids_with_postmeta_key( $valid_post_ids, AI4SEO_POST_META_ACTIVE_METADATA_META_KEY, $postmeta_read_failed );

			if ( $postmeta_read_failed ) {
				break;
			}

			if ( $active_metadata_post_ids ) {
				$possibly_deleted_post_ids = array();
				$deletion_succeeded        = ai4seo_delete_postmeta_for_post_ids_and_meta_key(
					$active_metadata_post_ids,
					AI4SEO_POST_META_ACTIVE_METADATA_META_KEY,
					$possibly_deleted_post_ids
				);

				$changed_post_ids                        = $possibly_deleted_post_ids;
				$irreversible_postmeta_deletion_post_ids = $possibly_deleted_post_ids;
				$active_metadata_deleted_entries         = count( $possibly_deleted_post_ids );

				if ( $deletion_succeeded ) {
					$should_refresh_posts_table_analysis = true;
				} else {
					$postmeta_deletion_failed = true;
				}
			}
			break;
	}

	if ( $postmeta_read_failed || $postmeta_deletion_failed ) {
		$queue_transition_failed             = true;
		$should_refresh_posts_table_analysis = false;
		$should_schedule_bulk_generation     = false;
		$transition_additions                = array();
		$transition_removals                 = array();

		if (
			$irreversible_postmeta_deletion_post_ids
			&& ! ai4seo_schedule_generation_status_summary_rebuild()
		) {
			ai4seo_debug_message( 836122086, 'Could not durably schedule generation-status repair after a partial postmeta deletion.', true );
		}

		if ( $postmeta_read_failed ) {
			ai4seo_debug_message( 836122088, 'Could not identify the selected postmeta owners; no deletion was attempted.', true );
		} else {
			ai4seo_debug_message( 836122087, 'Could not complete the selected postmeta deletion; conservative affected IDs require repair.', true );
		}
	}

	if ( $changed_post_ids && ( $transition_additions || $transition_removals ) ) {
		$transition_did_change = false;
		$transition_succeeded  = false;

		if ( $should_filter_processing_memberships ) {
			$admitted_post_ids    = array();
			$transition_succeeded = ai4seo_apply_post_id_option_admission_transition(
				$transition_additions,
				$transition_removals,
				array(
					$queue_options['processing'],
					$queue_options['hidden'],
				),
				$admitted_post_ids,
				$transition_did_change,
				$required_complete_options
			);
			$changed_post_ids     = $transition_succeeded ? $admitted_post_ids : array();
		} else {
			$transition_succeeded = ai4seo_apply_post_id_option_transition(
				$transition_additions,
				$transition_removals,
				$transition_did_change,
				$required_complete_options
			);
		}

		if ( ! $transition_succeeded ) {
			$queue_transition_failed             = true;
			$should_refresh_posts_table_analysis = false;
			$should_schedule_bulk_generation     = false;

			if ( $irreversible_postmeta_deletion_post_ids ) {
				// The source mutation cannot be rolled back; persist a full rebuild request for authoritative repair.
				if ( ! ai4seo_schedule_generation_status_summary_rebuild() ) {
					ai4seo_debug_message( 836122084, 'Could not durably schedule generation-status repair after postmeta deletion.', true );
				}
			} else {
				$changed_post_ids                = array();
				$generated_data_deleted_entries  = 0;
				$active_metadata_deleted_entries = 0;
			}

			ai4seo_debug_message( 836122081, 'Could not apply the selected bulk-generation queue transition.', true );
		} elseif ( $should_schedule_bulk_generation && $changed_post_ids ) {
			ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
		}
	}

	if ( $should_refresh_posts_table_analysis ) {
		// Refresh the generation status summary after the already-authorized bulk queue mutation.
		if ( ! ai4seo_force_posts_table_analysis_refresh_after_admin_mutation() ) {
			$result['statistics_pending'] = true;
			if ( ! ai4seo_schedule_generation_status_summary_rebuild() ) {
				ai4seo_debug_message( 836122085, 'Could not durably schedule generation-status repair after an admin refresh failure.', true );
			}

			ai4seo_debug_message( 836122083, 'Could not refresh posts-table analysis after the selected bulk-generation mutation.', true );
		}
	}

	$changed_entries = count( $changed_post_ids );

	$result['changed']                 = $changed_entries;
	$result['not_applicable']          = $not_applicable_entries;
	$result['skipped']                 = max( 0, $selected_entries - $changed_entries - $not_applicable_entries );
	$result['generated_data_deleted']  = $generated_data_deleted_entries;
	$result['active_metadata_deleted'] = $active_metadata_deleted_entries;
	$result['queue_count']             = ai4seo_get_bulk_generation_queue_count();

	return $result;
}


/**
 * Filters selected post IDs to entries that can be handled by the queue context.
 *
 * @param array  $post_ids Post IDs.
 * @param string $context Queue context.
 * @return array Valid post IDs.
 */
function ai4seo_get_valid_bulk_generation_queue_post_ids( array $post_ids, string $context ): array {
	$context        = sanitize_key( $context );
	$valid_post_ids = array();

	foreach ( $post_ids as $this_post_id ) {
		$this_post_id = absint( $this_post_id );

		if ( ! $this_post_id ) {
			continue;
		}

		$this_post = get_post( $this_post_id );

		if ( ! $this_post || is_wp_error( $this_post ) ) {
			continue;
		}

		if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context && ai4seo_is_post_a_valid_content_post( $this_post_id, $this_post ) ) {
			$valid_post_ids[] = $this_post_id;
			continue;
		}

		if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context && ai4seo_is_post_a_valid_attachment( $this_post_id, $this_post ) ) {
			$valid_post_ids[] = $this_post_id;
		}
	}

	return array_values( array_unique( $valid_post_ids ) );
}


/**
 * Filters selected queue IDs to entries that are applicable for normal queueing.
 *
 * @param array  $post_ids Post IDs.
 * @param string $context Queue context.
 * @return array Applicable post IDs.
 */
function ai4seo_get_applicable_bulk_generation_queue_post_ids( array $post_ids, string $context ): array {
	$context  = sanitize_key( $context );
	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );

	switch ( $context ) {
		case AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA:
			$applicable_post_ids = array_merge(
				ai4seo_get_post_ids_from_option( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME ),
				ai4seo_get_post_ids_from_option( AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME ),
				ai4seo_get_post_ids_from_option( AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME )
			);
			$hidden_post_ids     = ai4seo_get_post_ids_from_option( AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME );
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES:
			$applicable_post_ids = array_merge(
				ai4seo_get_post_ids_from_option( AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ),
				ai4seo_get_post_ids_from_option( AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ),
				ai4seo_get_post_ids_from_option( AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME )
			);
			$hidden_post_ids     = ai4seo_get_post_ids_from_option( AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
			break;

		default:
			return array();
	}

	$applicable_post_ids = array_values( array_unique( array_filter( array_map( 'absint', $applicable_post_ids ) ) ) );
	$hidden_post_ids     = array_values( array_unique( array_filter( array_map( 'absint', $hidden_post_ids ?? array() ) ) ) );
	$applicable_post_ids = array_values( array_diff( $applicable_post_ids, $hidden_post_ids ) );

	return array_values( array_intersect( $post_ids, $applicable_post_ids ) );
}


/**
 * Counts bulk generation entries for one queue status across metadata and attachment contexts.
 *
 * @param string $status Queue status key from ai4seo_get_bulk_generation_queue_options_by_context().
 * @return int
 */
function ai4seo_get_bulk_generation_entry_count_by_status( string $status ): int {
	global $wpdb;

	// Reuse the context-to-option map so queue counters stay aligned with bulk action routing.
	$option_names = array();

	foreach ( ai4seo_get_bulk_generation_queue_contexts() as $context ) {
		$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

		// Unknown status keys do not map to a persisted queue option and therefore contribute no entries.
		if ( ! isset( $queue_options[ $status ] ) ) {
			continue;
		}

		$option_names[] = $queue_options[ $status ];
	}

	// An unmapped status has no authoritative queue rows and must not trigger option reads.
	if ( ! $option_names ) {
		return 0;
	}

	// Counts and fallback reads require one exact active options-table scope.
	if ( ! is_object( $wpdb ) || ! isset( $wpdb->options ) || ! ai4seo_is_valid_database_identifier( $wpdb->options ) ) {
		return 0;
	}

	// Prefer one strict batch while preserving independent reads below as the transient-failure fallback.
	$options_table    = $wpdb->options;
	$option_snapshots = ai4seo_get_raw_option_snapshots( $option_names );

	// A site switch during the batch invalidates both its rows and any sequential fallback opportunity.
	if ( ! isset( $wpdb->options ) || ! is_string( $wpdb->options ) || $options_table !== $wpdb->options ) {
		return 0;
	}

	if ( is_array( $option_snapshots ) ) {
		$entry_count = 0;

		foreach ( $option_names as $option_name ) {
			$entry_count += count( ai4seo_normalize_option_post_id_collection( $option_snapshots[ $option_name ]['value'] ) );
		}

		return $entry_count;
	}

	// Preserve the established independent-read fallback after a transient batch failure.
	$entry_count = 0;

	foreach ( $option_names as $option_name ) {
		$post_ids = ai4seo_get_post_ids_from_option( $option_name );

		// Stop before accumulating a row or querying the next context after a fallback-site switch.
		if ( ! isset( $wpdb->options ) || ! is_string( $wpdb->options ) || $options_table !== $wpdb->options ) {
			return 0;
		}

		$entry_count += count( $post_ids );
	}

	return $entry_count;
}


/**
 * Count all entries currently queued for SEO Autopilot.
 *
 * @return int
 */
function ai4seo_get_bulk_generation_queue_count(): int {
	// The pending status is the persisted source of all work currently waiting in either queue context.
	return ai4seo_get_bulk_generation_entry_count_by_status( 'pending' );
}


/**
 * Count all SEO Autopilot entries currently being processed.
 *
 * Processing entries are no longer in the pending queue, but they still represent active
 * Autopilot work and must be considered before the dashboard reports an empty queue.
 *
 * @return int
 */
function ai4seo_get_bulk_generation_processing_count(): int {
	// Use the shared status counter so active work follows the same context mapping as pending work.
	return ai4seo_get_bulk_generation_entry_count_by_status( 'processing' );
}


/**
 * Excavates queue entries for the currently enabled SEO Autopilot post types.
 *
 * @param bool $debug Retained for caller compatibility; unused by shared selection.
 * @return bool
 */
function ai4seo_try_excavate_bulk_generation_entries_for_enabled_post_types( bool $debug = false ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Preserve the public caller signature after delegation to shared selection.
	$did_excavate = false;
	foreach ( ai4seo_get_bulk_generation_queue_contexts() as $context ) {
		$policy = ai4seo_read_auto_queue_policy_snapshot( $context );
		if ( 'system_error' === $policy['status'] ) {
			return false;
		}
		if ( 'ready' !== $policy['status'] ) {
			continue;
		}
		$readiness = ai4seo_get_bulk_generation_context_readiness( $context );
		if ( 'system_error' === $readiness['status'] ) {
			return false;
		}
		if ( 'ready' !== $readiness['status'] ) {
			continue;
		}
		$enabled_types = array();
		if ( ! ai4seo_reconcile_disabled_bulk_generation_queue_entries( $enabled_types, array( $context ) ) ) {
			return false;
		}
		$selection = ai4seo_select_auto_queue_candidates( $context );
		if ( 'system_error' === $selection['status'] ) {
			return false;
		}
		$did_excavate = in_array( $selection['status'], array( 'queued', 'queue_full' ), true ) || $did_excavate;
	}

	return $did_excavate;
}


// endregion
// ___________________________________________________________________________________________.
