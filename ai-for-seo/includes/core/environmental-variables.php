<?php
/**
 * Manages internal environmental state and caches.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region ENVIRONMENTAL VARIABLES ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Normalize stored environmental overrides into the complete runtime value map.
 *
 * @param mixed $current_environmental_variables Stored environmental overrides.
 * @return array Complete validated runtime environmental values.
 */
function ai4seo_normalize_environmental_variable_overrides_for_runtime( $current_environmental_variables ): array {
	// Missing or malformed storage represents an empty override map, so expose declared defaults unchanged.
	if ( ! is_array( $current_environmental_variables ) || ! $current_environmental_variables ) {
		return AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;
	}

	$loaded_environmental_variables = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;

	// Merge only declared base variables; unknown keys are excluded unless recognized as TTL companions below.
	foreach ( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES as $environmental_variable_name => $default_environmental_variable_value ) {
		$current_environmental_variable_value = $current_environmental_variables[ $environmental_variable_name ] ?? $default_environmental_variable_value;

		if ( ! ai4seo_validate_environmental_variable_value( $environmental_variable_name, $current_environmental_variable_value ) ) {
			ai4seo_debug_message( 2317181024, 'Invalid value for environmental variable "' . $environmental_variable_name . '"', true );
			$current_environmental_variable_value = $default_environmental_variable_value;
		}

		$loaded_environmental_variables[ $environmental_variable_name ] = $current_environmental_variable_value;
	}

	// TTL companions are runtime state even though they are not part of the declared base map.
	foreach ( $current_environmental_variables as $environmental_variable_name => $environmental_variable_value ) {
		if ( ! ai4seo_is_environmental_variable_ttl_name( (string) $environmental_variable_name ) ) {
			continue;
		}

		if ( ! ai4seo_validate_environmental_variable_value( $environmental_variable_name, $environmental_variable_value ) ) {
			ai4seo_debug_message( 2317181025, 'Invalid TTL value for environmental variable "' . $environmental_variable_name . '"', true );
			continue;
		}

		$loaded_environmental_variables[ $environmental_variable_name ] = (int) $environmental_variable_value;
	}

	return $loaded_environmental_variables;
}


/**
 * Read one failure-aware environmental snapshot from the active site's options table.
 *
 * This reader never publishes defaults or failed storage into request globals. Rechecking the
 * options-table identity after the query also prevents a site switch from mis-scoping its result.
 *
 * @return array{success: bool, values: array} Snapshot result.
 */
function ai4seo_read_authoritative_environmental_variables_snapshot(): array {
	global $wpdb;

	// Keep one stable failure shape so lock-owning callers can fail closed without publishing defaults.
	$failed_snapshot = array(
		'success' => false,
		'values'  => array(),
	);

	// Refuse to query until WordPress exposes one exact active options table for this request.
	if ( ! is_object( $wpdb ) || ! isset( $wpdb->options ) || '' === (string) $wpdb->options ) {
		return $failed_snapshot;
	}

	// Pin the active site's options table across the read so a mid-query site switch invalidates the result.
	$options_table = (string) $wpdb->options;

	// Use a value-only direct read so missing storage remains distinct from query failure without cache input.
	try {
		$option_query = ai4seo_prepare_database_query(
			'SELECT option_value FROM {{options_table}} WHERE option_name = {{option_name}} LIMIT 1',
			array(
				'options_table' => ai4seo_database_identifier_binding( 'table.options' ),
				'option_name'   => ai4seo_database_scalar_binding( '%s', AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME ),
			)
		);

		if ( false === $option_query ) {
			return $failed_snapshot;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The prepared one-row read must distinguish missing storage from a database failure.
		$serialized_environmental_variables = $wpdb->get_var( $option_query );
	} catch ( Throwable $throwable ) {
		ai4seo_debug_message( 2317181026, $throwable->getMessage(), true );
		return $failed_snapshot;
	}

	// Reject both database failures and scope drift instead of normalizing either into valid defaults.
	if ( $wpdb->last_error || ! isset( $wpdb->options ) || $options_table !== (string) $wpdb->options ) {
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 2317181027, 'Database error: ' . $wpdb->last_error, true );
		}

		return $failed_snapshot;
	}

	// A missing row is valid default-only state; malformed stored values are normalized below.
	$stored_environmental_variables = null === $serialized_environmental_variables
		? array()
		: ai4seo_safe_maybe_unserialize( $serialized_environmental_variables );

	return array(
		'success' => true,
		'values'  => ai4seo_normalize_environmental_variable_overrides_for_runtime( $stored_environmental_variables ),
	);
}


/**
 * Function to retrieve all environmental variables from database
 *
 * @param bool $use_cache Should we use the cache.
 * @return array All environmental variables
 */
function ai4seo_read_all_environmental_variables( bool $use_cache = true ): array {
	global $wpdb;
	global $ai4seo_environmental_variables;
	global $ai4seo_environmental_variables_are_loaded;
	global $ai4seo_environmental_variables_options_table;

	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 690812093, 'Prevented loop', true );
		return array();
	}

	if ( ! isset( $ai4seo_environmental_variables ) || ! is_array( $ai4seo_environmental_variables ) ) {
		$ai4seo_environmental_variables = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;
	}

	$current_options_table = is_object( $wpdb ) && isset( $wpdb->options ) ? (string) $wpdb->options : '';

	// Retain compatibility with request caches initialized before this scope marker existed, then
	// reject the cache whenever switch_to_blog() changes the authoritative options table.
	if ( ! isset( $ai4seo_environmental_variables_options_table ) || ! is_string( $ai4seo_environmental_variables_options_table ) ) {
		$ai4seo_environmental_variables_options_table = $current_options_table;
	} elseif ( $ai4seo_environmental_variables_options_table !== $current_options_table ) {
		$ai4seo_environmental_variables               = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;
		$ai4seo_environmental_variables_are_loaded    = false;
		$ai4seo_environmental_variables_options_table = $current_options_table;
	}

	// get cached version.
	if ( $use_cache && ! empty( $ai4seo_environmental_variables_are_loaded ) ) {
		return $ai4seo_environmental_variables;
	}

	// Keep the request-global as the only cache so values are decoded before WordPress can
	// instantiate stored objects.
	$current_environmental_variables = ai4seo_get_option( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, false, true );

	$ai4seo_environmental_variables               = ai4seo_normalize_environmental_variable_overrides_for_runtime( $current_environmental_variables );
	$ai4seo_environmental_variables_are_loaded    = true;
	$ai4seo_environmental_variables_options_table = $current_options_table;

	return $ai4seo_environmental_variables;
}


/**
 * Function to retrieve a specific environmental variable
 *
 * @param string $environmental_variable_name The name of the environmental variable.
 * @param bool   $use_cache Should we use the cache.
 * @return mixed The value of the environmental variable
 */
function ai4seo_read_environmental_variable( string $environmental_variable_name, bool $use_cache = true ) {
	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 232735921, 'Prevented loop', true );
		return null;
	}

	// Make sure that $environmental_variable_name-parameter has content.
	if ( ! $environmental_variable_name ) {
		ai4seo_debug_message( 515181024, 'Environmental variable name is empty.', true );
		return null;
	}

	// check for the default value.
	if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] ) ) {
		ai4seo_debug_message( 56187825, 'Unknown environmental variable name: "' . $environmental_variable_name . '"', true );
		return null;
	}

	$current_environmental_variables = ai4seo_read_all_environmental_variables( $use_cache );

	// Check if the $environmental_variable_name-parameter exists in environmental variables-array.
	if ( isset( $current_environmental_variables[ $environmental_variable_name ] ) ) {
		return $current_environmental_variables[ $environmental_variable_name ];
	} else {
		return AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ];
	}
}


/**
 * Returns the bounded retry budget for shared environmental option mutations.
 *
 * @return int
 */
function ai4seo_get_environmental_variable_mutation_attempt_limit(): int {
	return 5;
}


/**
 * Reloads authoritative environmental storage into WordPress and request caches.
 *
 * @return bool True when a raw storage snapshot was available, false on snapshot failure.
 */
function ai4seo_reconcile_environmental_variable_caches_from_storage(): bool {
	$authoritative_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
	if ( null !== $authoritative_snapshot ) {
		// Another writer can commit after the raw read, so never publish the observed value or absence.
		ai4seo_invalidate_option_cache( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
	}

	ai4seo_read_all_environmental_variables( false );
	return null !== $authoritative_snapshot;
}


/**
 * Applies one mutation to the authoritative environmental override map using exact CAS retries.
 *
 * The callback receives the raw persisted override map and must return exactly:
 * - overrides: the complete replacement raw override map;
 * - changed: whether persistence is required;
 * - result: caller-specific data calculated from that same authoritative snapshot.
 *
 * @param callable $mutation_callback Builds one replacement from the latest raw override map.
 * @param bool     $publish_wordpress_hooks Whether successful writes mirror core option actions.
 * @param mixed    $mutation_result Receives caller-specific data from the final attempted snapshot.
 * @return bool True on a successful/no-op mutation, false after a storage failure or exhausted conflicts.
 */
function ai4seo_mutate_environmental_variable_overrides( callable $mutation_callback, bool $publish_wordpress_hooks = false, &$mutation_result = null ): bool {
	global $wpdb;

	// Require a closed callback result shape so partial mutation state can never reach persistence.
	$required_mutation_keys = array(
		'overrides',
		'changed',
		'result',
	);
	$attempt_limit          = ai4seo_get_environmental_variable_mutation_attempt_limit();
	$database_error         = '';

	// Re-run the callback against the latest raw option snapshot after every CAS conflict.
	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$option_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );

		if ( null === $option_snapshot ) {
			$database_error = is_object( $wpdb ) ? (string) $wpdb->last_error : '';
			break;
		}

		$current_overrides = is_array( $option_snapshot['value'] ) ? $option_snapshot['value'] : array();
		$mutation_state    = $mutation_callback( $current_overrides );

		if (
			! is_array( $mutation_state )
			|| array_keys( $mutation_state ) !== $required_mutation_keys
			|| ! is_array( $mutation_state['overrides'] )
			|| ! is_bool( $mutation_state['changed'] )
		) {
			break;
		}

		$mutation_result = $mutation_state['result'];
		if ( ! $mutation_state['changed'] ) {
			// The callback can outlive this snapshot; invalidation cannot overwrite a later cache publication.
			ai4seo_invalidate_option_cache( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
			ai4seo_read_all_environmental_variables( false );
			return true;
		}

		$replacement_raw_value = maybe_serialize( $mutation_state['overrides'] );

		// Treat an already-achieved exact replacement as success while repairing every request/cache view.
		if (
			$option_snapshot['exists']
			&& 'no' === $option_snapshot['autoload']
			&& hash_equals( $replacement_raw_value, $option_snapshot['raw_value'] )
		) {
			// Exact at observation time does not authorize publishing stale bytes after a later writer.
			ai4seo_invalidate_option_cache( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
			ai4seo_read_all_environmental_variables( false );
			return true;
		}

		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME,
			$option_snapshot,
			$mutation_state['overrides'],
			false,
			$publish_wordpress_hooks
		);

		if ( true === $compare_and_swap_result ) {
			ai4seo_read_all_environmental_variables( false );
			return true;
		}

		if ( null === $compare_and_swap_result ) {
			$database_error = is_object( $wpdb ) ? (string) $wpdb->last_error : '';
			break;
		}
	}

	// Failed writers must not leave optimistic or stale environmental state in either cache layer.
	ai4seo_reconcile_environmental_variable_caches_from_storage();
	if ( '' !== $database_error && is_object( $wpdb ) ) {
		$wpdb->last_error = $database_error;
	}
	return false;
}


/**
 * Mutates one environmental value from the latest authoritative option snapshot.
 *
 * The callback runs inside every outer compare-and-swap attempt, receives the
 * validated current value (or its declared default), and must return the complete
 * replacement value. Unrelated environmental overrides and an optional TTL remain
 * part of the same atomic option replacement.
 *
 * @param string   $environmental_variable_name Environmental variable to mutate.
 * @param callable $mutation_callback Builds the complete replacement from the latest value.
 * @param bool     $use_cache Whether to warm request state and publish mirrored WordPress option hooks.
 * @param int      $cache_ttl Cache TTL in seconds. Zero disables TTL companion updates.
 * @return bool True on a successful/no-op mutation, false on invalid output or storage failure.
 */
function ai4seo_mutate_environmental_variable_value(
	string $environmental_variable_name,
	callable $mutation_callback,
	bool $use_cache = true,
	int $cache_ttl = 0
): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 146829303, 'Prevented loop', true );
		return false;
	}

	if ( ai4seo_is_environmental_variable_ttl_name( $environmental_variable_name ) ) {
		ai4seo_debug_message( 146829304, 'Attempted to mutate a TTL companion environmental variable directly: "' . $environmental_variable_name . '"', true );
		return false;
	}

	if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] ) ) {
		ai4seo_debug_message( 146829305, 'Unknown environmental variable name: "' . $environmental_variable_name . '"', true );
		return false;
	}

	// Preserve the public cache flag's request-warming behavior without basing the mutation on that cache.
	if ( $use_cache ) {
		ai4seo_read_all_environmental_variables();
	}

	return ai4seo_mutate_environmental_variable_overrides(
		static function ( array $current_overrides ) use ( $environmental_variable_name, $mutation_callback, $cache_ttl ): array {
			$current_value = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ];

			// Invalid target bytes behave like the public reader's declared default and are repaired by this mutation.
			if (
				array_key_exists( $environmental_variable_name, $current_overrides )
				&& ai4seo_validate_environmental_variable_value( $environmental_variable_name, $current_overrides[ $environmental_variable_name ] )
			) {
				$current_value = $current_overrides[ $environmental_variable_name ];
			}

			$replacement_value = $mutation_callback( $current_value );

			if ( ! ai4seo_validate_environmental_variable_value( $environmental_variable_name, $replacement_value ) ) {
				ai4seo_debug_message( 146829306, 'Environmental variable mutation produced an invalid value for "' . $environmental_variable_name . '"', true );
				return array();
			}

			$replacement_value     = ai4seo_deep_sanitize( $replacement_value );
			$replacement_overrides = $current_overrides;
			$did_change            = false;

			// Persist only non-default overrides while retaining every unrelated raw entry.
			if ( ai4seo_are_persisted_state_values_equivalent( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ], $replacement_value ) ) {
				if ( array_key_exists( $environmental_variable_name, $replacement_overrides ) ) {
					unset( $replacement_overrides[ $environmental_variable_name ] );
					$did_change = true;
				}
			} elseif (
				! array_key_exists( $environmental_variable_name, $replacement_overrides )
				|| $replacement_overrides[ $environmental_variable_name ] !== $replacement_value
			) {
				$replacement_overrides[ $environmental_variable_name ] = $replacement_value;
				$did_change = true;
			}

			// Renew the value and its TTL inside one retry so concurrent map entries cannot be discarded.
			if ( 0 < $cache_ttl ) {
				$ttl_name        = ai4seo_get_environmental_variable_ttl_name( $environmental_variable_name );
				$replacement_ttl = time() + $cache_ttl;

				if ( ! isset( $replacement_overrides[ $ttl_name ] ) || $replacement_ttl !== $replacement_overrides[ $ttl_name ] ) {
					$replacement_overrides[ $ttl_name ] = $replacement_ttl;
					$did_change                         = true;
				}
			}

			return array(
				'overrides' => $replacement_overrides,
				'changed'   => $did_change,
				'result'    => null,
			);
		},
		$use_cache
	);
}


/**
 * Function to update a specific environmental variable
 *
 * @param string $environmental_variable_name The name of the environmental variable.
 * @param mixed  $new_environmental_variable_value The new value of the environmental variable.
 * @param bool   $use_cache Should we use the cache.
 * @param int    $cache_ttl Cache TTL in seconds. 0 disables TTL companion updates.
 * @return bool True if the environmental variable was updated successfully, false if not
 */
function ai4seo_update_environmental_variable( string $environmental_variable_name, $new_environmental_variable_value, bool $use_cache = true, int $cache_ttl = 0 ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 726736127, 'Prevented loop', true );
		return false;
	}

	if ( ai4seo_is_environmental_variable_ttl_name( $environmental_variable_name ) ) {
		ai4seo_debug_message( 141224226, 'Attempted to update TTL companion variable via ai4seo_update_environmental_variable(): "' . $environmental_variable_name . '"', true );
		return false;
	}

	if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] ) ) {
		ai4seo_debug_message( 51187825, 'Unknown environmental variable name: "' . $environmental_variable_name . '"', true );
		return false;
	}

	// Make sure that the new value of the environmental variable is valid.
	if ( ! ai4seo_validate_environmental_variable_value( $environmental_variable_name, $new_environmental_variable_value ) ) {
		ai4seo_debug_message( 535181024, 'Invalid value for environmental variable "' . $environmental_variable_name . '"', true );
		return false;
	}

	// sanitize.
	$new_environmental_variable_value = ai4seo_deep_sanitize( $new_environmental_variable_value );

	return ai4seo_mutate_environmental_variable_value(
		$environmental_variable_name,
		static function () use ( $new_environmental_variable_value ) {
			return $new_environmental_variable_value;
		},
		$use_cache,
		$cache_ttl
	);
}

/**
 * Keep the SEO Autopilot date-filter reference time consistent with its active setting.
 *
 * A supplied reference time is used when environmental-variable reset needs to restore the valid
 * boundary that belonged to the retained setting. Invalid active state receives a fresh boundary;
 * the inactive "both" filter deliberately keeps the environmental default of zero.
 *
 * @param mixed $reference_timestamp Optional reference time to restore instead of reading current state.
 * @return bool True when the existing state is valid or a valid reference time was persisted.
 */
function ai4seo_reconcile_bulk_generation_date_filter_reference_timestamp( $reference_timestamp = null ): bool {
	// Distinguish an explicit zero activation marker from the absence of an override.
	$has_reference_timestamp_override = func_num_args() > 0;
	$date_filter                      = ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER );

	// Read the persisted reference only when the caller did not supply reset or activation state.
	if ( ! $has_reference_timestamp_override ) {
		$reference_timestamp = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME );
	}

	$date_filter_state = ai4seo_get_bulk_generation_date_filter_state( $date_filter, $reference_timestamp );

	// Preserve valid current state, but persist a valid override when environmental data was reset.
	if ( ! empty( $date_filter_state['is_valid'] ) ) {
		if ( 'both' === $date_filter_state['filter'] || ! $has_reference_timestamp_override ) {
			return true;
		}

		return ai4seo_update_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME,
			$date_filter_state['reference_timestamp']
		);
	}

	// An unknown setting value cannot be repaired by changing only its environmental timestamp.
	if ( 'invalid_filter' === ( $date_filter_state['error_code'] ?? '' ) ) {
		return false;
	}

	// Replace invalid active-filter timestamps with one validated current boundary.
	$replacement_reference_timestamp = time();
	$replacement_date_filter_state   = ai4seo_get_bulk_generation_date_filter_state( $date_filter, $replacement_reference_timestamp );

	if ( empty( $replacement_date_filter_state['is_valid'] ) ) {
		return false;
	}

	return ai4seo_update_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME,
		$replacement_date_filter_state['reference_timestamp']
	);
}


/**
 * Function to delete an environmental variable
 *
 * @param string $environmental_variable_name The name of the environmental variable.
 * @return bool True if the environmental variable was deleted successfully, false if not
 */
function ai4seo_delete_environmental_variable( string $environmental_variable_name ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 912986381, 'Prevented loop', true );
		return false;
	}

	// Make sure that $environmental_variable_name-parameter has content.
	if ( ! $environmental_variable_name ) {
		ai4seo_debug_message( 491226225, 'Environmental variable name is empty.', true );
		return false;
	}

	return ai4seo_mutate_environmental_variable_overrides(
		static function ( array $current_overrides ) use ( $environmental_variable_name ): array {
			$did_change = array_key_exists( $environmental_variable_name, $current_overrides );

			if ( $did_change ) {
				unset( $current_overrides[ $environmental_variable_name ] );
			}

			return array(
				'overrides' => $current_overrides,
				'changed'   => $did_change,
				'result'    => null,
			);
		}
	);
}


/**
 * Deletes all environmental variables
 *
 * @return bool
 */
function ai4seo_delete_all_environmental_variables(): bool {
	$option_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );

	if ( null === $option_snapshot ) {
		ai4seo_reconcile_environmental_variable_caches_from_storage();
		return false;
	}

	$delete_result = ai4seo_compare_and_delete_option_snapshot(
		AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME,
		$option_snapshot
	);

	if ( true === $delete_result ) {
		ai4seo_read_all_environmental_variables( false );
		return true;
	}

	// A conflicting writer owns the newer map; fail without deleting it and publish its state locally.
	ai4seo_reconcile_environmental_variable_caches_from_storage();
	return false;
}


/**
 * Bulk update environmental variables.
 *
 * Accepts an associative array of updates like array( 'variable_name' => 'new_value', ... ).
 * Each entry is validated against AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES and
 * ai4seo_validate_environmental_variable_value() before being applied.
 * Values equal to their defaults are removed from the stored overrides.
 *
 * @param array $environmental_variable_updates Associative array: name => value.
 * @param bool  $warm_request_cache Whether to preserve the public bulk helper's request-cache warm-up.
 * @return array {
 *     @type bool  $success        True if persisted successfully (or nothing to persist), false on DB write failure.
 *     @type int   $updated_count  Number of variables that changed (added/updated/removed).
 *     @type array $invalid_names  List of names skipped because they are unknown.
 *     @type array $invalid_values List of names skipped because the value was invalid.
 * }
 */
function ai4seo_bulk_update_environmental_variables( array $environmental_variable_updates, bool $warm_request_cache = true ): array {
	$result = array(
		'success'        => true,
		'updated_count'  => 0,
		'invalid_names'  => array(),
		'invalid_values' => array(),
	);

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 210791927, 'Prevented loop', true );
		return $result;
	}

	// Nothing to do.
	if ( empty( $environmental_variable_updates ) ) {
		return $result;
	}

	// Retain the established request-cache warm-up contract without using it as mutation input.
	if ( $warm_request_cache ) {
		ai4seo_read_all_environmental_variables();
	}

	$validated_updates = array();

	foreach ( $environmental_variable_updates as $this_name => $this_value ) {
		if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $this_name ] ) ) {
			$result['invalid_names'][] = $this_name;
			ai4seo_debug_message( 2017171025, 'Unknown environmental variable name in bulk update: "' . $this_name . '"', true );
			continue;
		}

		if ( ! ai4seo_validate_environmental_variable_value( $this_name, $this_value ) ) {
			$result['invalid_values'][] = $this_name;
			ai4seo_debug_message( 2117171025, 'Invalid value for environmental variable "' . $this_name . '" in bulk update.', true );
			continue;
		}

		$validated_updates[ $this_name ] = ai4seo_deep_sanitize( $this_value );
	}

	if ( ! $validated_updates ) {
		return $result;
	}

	$mutation_updated_count = 0;
	$did_update             = ai4seo_mutate_environmental_variable_overrides(
		static function ( array $current_overrides ) use ( $validated_updates ): array {
			$replacement_overrides = $current_overrides;
			$updated_count         = 0;

			foreach ( $validated_updates as $this_name => $this_value ) {
				if ( ai4seo_are_persisted_state_values_equivalent( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $this_name ], $this_value ) ) {
					if ( array_key_exists( $this_name, $replacement_overrides ) ) {
						unset( $replacement_overrides[ $this_name ] );
						++$updated_count;
					}
					continue;
				}

				// Retain the stored representation when only a storage-compatible scalar type differs.
				if (
					array_key_exists( $this_name, $replacement_overrides )
					&& ai4seo_are_persisted_state_values_equivalent( $replacement_overrides[ $this_name ], $this_value )
				) {
					continue;
				}

				$replacement_overrides[ $this_name ] = $this_value;
				++$updated_count;
			}

			return array(
				'overrides' => $replacement_overrides,
				'changed'   => 0 < $updated_count,
				'result'    => $updated_count,
			);
		},
		false,
		$mutation_updated_count
	);

	$result['updated_count'] = (int) $mutation_updated_count;

	if ( ! $did_update ) {
		$result['success'] = false;
		ai4seo_debug_message( 2217171025, 'Failed to persist environmental variables in bulk update.', true );
	}

	return $result;
}


/**
 * Normalize one durable automatic-retry request token.
 *
 * Boolean true remains readable only as a legacy marker so existing interrupted requests can be
 * migrated to a generation-specific token before reconciliation. False is the declared idle value.
 *
 * @param mixed $request_token Candidate token.
 * @return string|bool Canonical token, true for a legacy marker, or false when idle/invalid.
 */
function ai4seo_normalize_auto_retry_failed_request_token( $request_token ) {
	if ( true === $request_token ) {
		return true;
	}

	if ( ! is_string( $request_token ) ) {
		return false;
	}

	$request_token = strtolower( trim( $request_token ) );

	return 1 === preg_match(
		'/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/',
		$request_token
	)
		? $request_token
		: false;
}


/**
 * Replace one environmental value only when its latest validated generation still matches.
 *
 * A mismatched value is a successful no-op: it belongs to another writer and must remain intact.
 * Storage failures remain distinguishable through the function result.
 *
 * @param string $environmental_variable_name Environmental variable name.
 * @param mixed  $expected_value Exact currently owned value.
 * @param mixed  $replacement_value Replacement value.
 * @param bool   $did_replace Receives whether the expected generation was replaced.
 * @return bool True after a checked replacement or mismatch no-op, false on validation/storage failure.
 */
function ai4seo_compare_and_swap_environmental_variable_value(
	string $environmental_variable_name,
	$expected_value,
	$replacement_value,
	bool &$did_replace
): bool {
	$did_replace = false;

	if (
		! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] )
		|| ! ai4seo_validate_environmental_variable_value( $environmental_variable_name, $expected_value )
		|| ! ai4seo_validate_environmental_variable_value( $environmental_variable_name, $replacement_value )
	) {
		return false;
	}

	$expected_value     = ai4seo_deep_sanitize( $expected_value );
	$replacement_value  = ai4seo_deep_sanitize( $replacement_value );
	$mutation_result    = false;
	$mutation_succeeded = ai4seo_mutate_environmental_variable_overrides(
		static function ( array $current_overrides ) use ( $environmental_variable_name, $expected_value, $replacement_value ): array {
			$current_value = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ];

			if (
				array_key_exists( $environmental_variable_name, $current_overrides )
				&& ai4seo_validate_environmental_variable_value(
					$environmental_variable_name,
					$current_overrides[ $environmental_variable_name ]
				)
			) {
				$current_value = $current_overrides[ $environmental_variable_name ];
			}

			if ( $current_value !== $expected_value ) {
				return array(
					'overrides' => $current_overrides,
					'changed'   => false,
					'result'    => false,
				);
			}

			$replacement_overrides = $current_overrides;

			if ( ai4seo_are_persisted_state_values_equivalent( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ], $replacement_value ) ) {
				unset( $replacement_overrides[ $environmental_variable_name ] );
			} else {
				$replacement_overrides[ $environmental_variable_name ] = $replacement_value;
			}

			return array(
				'overrides' => $replacement_overrides,
				'changed'   => $replacement_overrides !== $current_overrides,
				'result'    => true,
			);
		},
		false,
		$mutation_result
	);

	$did_replace = $mutation_succeeded && true === $mutation_result;
	return $mutation_succeeded;
}


/**
 * Validate value of an environmental variable
 *
 * @param string $environmental_variable_name The name of the environmental variable.
 * @param mixed  $environmental_variable_value The value of the environmental variable.
 */
function ai4seo_validate_environmental_variable_value( string $environmental_variable_name, $environmental_variable_value ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 705807482, 'Prevented loop', true );
		return false;
	}

	if ( ai4seo_is_environmental_variable_ttl_name( $environmental_variable_name ) ) {
		return is_numeric( $environmental_variable_value ) && (int) $environmental_variable_value >= 0;
	}

	switch ( $environmental_variable_name ) {
		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION:
			// contains only of numbers and dots.
			return is_string( $environmental_variable_value ) && preg_match( '/^[0-9.]+$/', $environmental_variable_value );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_TOS_DETAILS_CHECKSUM:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_REVOKED_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_LAST_MODAL_OPEN_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTMETA_TABLE_ENTRIES:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_LAST_RUN_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_PROCESSED_ENTRIES:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME:
			// contains only of numbers.
			return is_numeric( $environmental_variable_value ) && $environmental_variable_value >= 0;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE:
			// Persisted processing states use exact string identifiers.
			return is_string( $environmental_variable_value ) && in_array( $environmental_variable_value, array( 'idle', 'processing', 'completed' ), true );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE:
			// A required rebuild restarts once; processing resumes the same bounded replacement analysis.
			return is_string( $environmental_variable_value ) && in_array( $environmental_variable_value, array( 'idle', 'required', 'processing' ), true );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT:
			// empty or array with at least name and percentage.
			if ( empty( $environmental_variable_value ) ) {
				return true;
			}

			if ( ! is_array( $environmental_variable_value ) || ! isset( $environmental_variable_value['name'] ) || ! isset( $environmental_variable_value['percentage'] ) ) {
				ai4seo_debug_message( 531729725, 'Invalid current discount environmental variable.', true );
				return false;
			}

			// name contains only small letters and "-".
			if ( ! is_string( $environmental_variable_value['name'] ) || ! preg_match( '/^[a-z0-9-]+$/', $environmental_variable_value['name'] ) ) {
				ai4seo_debug_message( 541729725, 'Invalid discount name in the current discount environmental variable.', true );
				return false;
			}

			// percentage must be int.
			if ( ! is_numeric( $environmental_variable_value['percentage'] ) || $environmental_variable_value['percentage'] < 0 || $environmental_variable_value['percentage'] > 100 ) {
				ai4seo_debug_message( 551729725, 'Invalid percentage in the current discount environmental variable.', true );
				return false;
			}

			// if expire_in is provided, check its integer and between 0 and 99.999.999.
			if ( isset( $environmental_variable_value['expire_in'] ) && ( ! is_numeric( $environmental_variable_value['expire_in'] ) || $environmental_variable_value['expire_in'] < 0 || $environmental_variable_value['expire_in'] > 99999999 ) ) {
				ai4seo_debug_message( 561729725, 'Invalid expire_in in the current discount environmental variable.', true );
				return false;
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED:
			// boolean.
			return is_bool( $environmental_variable_value );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_RETRY_FAILED_REQUIRED:
			// False is idle, true is a migratable legacy marker, and strings are generation tokens.
			return false === $environmental_variable_value
				|| false !== ai4seo_normalize_auto_retry_failed_request_token( $environmental_variable_value );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST:
			// array of strings (containing a-z and -).
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $key => $value ) {
				if ( ! is_string( $value ) || ! preg_match( '/^[a-z0-9-]+$/', $value ) ) {
					return false;
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME:
			// Match the queue validator while retaining zero as the inactive filter's sentinel value.
			$validated_reference_timestamp = ( is_int( $environmental_variable_value ) || is_string( $environmental_variable_value ) )
				? filter_var(
					$environmental_variable_value,
					FILTER_VALIDATE_INT,
					array(
						'options' => array(
							'min_range' => 0,
						),
					)
				)
				: false;

			if ( false === $validated_reference_timestamp ) {
				ai4seo_debug_message( 5713171224, 'Invalid value in the automated generations new or existing filter reference times setting.', true );
				return false;
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER:
			return is_bool( $environmental_variable_value );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES:
			// array of integers >= 0.
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $key => $value ) {
				if ( ! is_numeric( $value ) || $value < 0 ) {
					return false;
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS:
			// PAYG routing accepts only statuses from the shared account-response contract.
			return is_string( $environmental_variable_value ) && in_array( $environmental_variable_value, AI4SEO_ALLOWED_PAYG_STATUS, true );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON:
			if ( ! is_string( $environmental_variable_value ) ) {
				return false;
			}

			return ( '' === $environmental_variable_value || sanitize_key( $environmental_variable_value ) === $environmental_variable_value );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE:
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $this_value ) {
				if ( ! is_string( $this_value ) || sanitize_key( $this_value ) !== $this_value ) {
					return false;
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE:
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $this_key => $this_value ) {
				if ( ! is_string( $this_key ) || '' === $this_key ) {
					return false;
				}

				if ( ! is_array( $this_value ) ) {
					return false;
				}

				foreach ( $this_value as $this_author_id => $this_author_label ) {
					if ( ! is_numeric( $this_author_id ) || (int) $this_author_id <= 0 ) {
						return false;
					}

					if ( ! is_string( $this_author_label ) ) {
						return false;
					}
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE:
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $this_taxonomy_name => $this_taxonomy_data ) {
				if ( ! is_string( $this_taxonomy_name ) || sanitize_key( $this_taxonomy_name ) !== $this_taxonomy_name || '' === $this_taxonomy_name ) {
					return false;
				}

				if (
					! is_array( $this_taxonomy_data )
					|| ! isset( $this_taxonomy_data['label'] )
					|| ! isset( $this_taxonomy_data['terms'] )
					|| ! is_string( $this_taxonomy_data['label'] )
					|| ! is_array( $this_taxonomy_data['terms'] )
				) {
					return false;
				}

				foreach ( $this_taxonomy_data['terms'] as $this_term_id => $this_term_name ) {
					if ( ! is_numeric( $this_term_id ) || (int) $this_term_id <= 0 ) {
						return false;
					}

					if ( ! is_string( $this_term_name ) ) {
						return false;
					}
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE:
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $this_value ) {
				if ( ! is_numeric( $this_value ) || (int) $this_value < 0 ) {
					return false;
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE:
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $this_key => $this_value ) {
				if ( ! is_string( $this_key ) || '' === $this_key ) {
					return false;
				}

				if ( ! is_numeric( $this_value ) || (int) $this_value < 0 ) {
					return false;
				}
			}

			return true;

		default:
			return false;
	}
}


/**
 * Check if an environmental variable name is a TTL companion variable.
 *
 * @param string $environmental_variable_name Environmental variable name.
 * @return bool True if the name ends with the TTL suffix, false otherwise.
 */
function ai4seo_is_environmental_variable_ttl_name( string $environmental_variable_name ): bool {
	return substr( $environmental_variable_name, -strlen( AI4SEO_ENVIRONMENTAL_VARIABLE_CACHE_TTL_SUFFIX ) ) === AI4SEO_ENVIRONMENTAL_VARIABLE_CACHE_TTL_SUFFIX;
}


/**
 * Build ttl companion environmental variable name.
 *
 * @param string $environmental_variable_name Environmental variable name.
 * @return string
 */
function ai4seo_get_environmental_variable_ttl_name( string $environmental_variable_name ): string {
	return $environmental_variable_name . AI4SEO_ENVIRONMENTAL_VARIABLE_CACHE_TTL_SUFFIX;
}

// =========================================================================================== \
/**
 * Returns true if an environmental variable cache TTL exists and is not expired.
 *
 * @param string $environmental_variable_name The base environmental variable name.
 * @return bool
 */
function ai4seo_is_environmental_variable_cache_available( string $environmental_variable_name ): bool {
	if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] ) ) {
		return false;
	}

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 301224226, 'Prevented loop', true );
		return false;
	}

	$ttl_name                    = ai4seo_get_environmental_variable_ttl_name( $environmental_variable_name );
	$all_environmental_variables = ai4seo_read_all_environmental_variables();

	if ( ! isset( $all_environmental_variables[ $ttl_name ] ) || ! is_numeric( $all_environmental_variables[ $ttl_name ] ) ) {
		return false;
	}

	return (int) $all_environmental_variables[ $ttl_name ] > time();
}


/**
 * Invalidate one environmental variable cache by removing its ttl companion value.
 *
 * @param string $environmental_variable_name The base environmental variable name.
 * @return bool True when the cache was invalidated or already absent, false on invalid input or storage failure.
 */
function ai4seo_invalidate_environmental_variable_cache( string $environmental_variable_name ): bool {
	if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] ) ) {
		return false;
	}

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 311224226, 'Prevented loop', true );
		return false;
	}

	return ai4seo_delete_environmental_variable( ai4seo_get_environmental_variable_ttl_name( $environmental_variable_name ) );
}


/**
 * Invalidate all environmental variable caches by removing every __ttl_time entry.
 *
 * @return bool True when every cache TTL was removed or already absent.
 */
function ai4seo_invalidate_all_environmental_variable_caches(): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 321224226, 'Prevented loop', true );
		return false;
	}

	return ai4seo_mutate_environmental_variable_overrides(
		static function ( array $current_overrides ): array {
			$did_change = false;

			foreach ( $current_overrides as $this_name => $unused_value ) {
				if ( ! ai4seo_is_environmental_variable_ttl_name( (string) $this_name ) ) {
					continue;
				}

				unset( $current_overrides[ $this_name ] );
				$did_change = true;
			}

			return array(
				'overrides' => $current_overrides,
				'changed'   => $did_change,
				'result'    => null,
			);
		}
	);
}


/**
 * Returns environmental variable => action map for cache invalidation.
 *
 * @return array<string, array<int, string>>
 */
function ai4seo_get_environmental_variable_to_action_cache_invalidation_map(): array {
	return array(
		AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES => array(
			'save_post',
			'delete_post',
			'deleted_post',
			'trashed_post',
			'untrashed_post',
			'transition_post_status',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE => array(
			'activated_plugin',
			'deactivated_plugin',
			'delete_post',
			'deleted_post',
			'transition_post_status',
			'switch_theme',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE => array(
			'activated_plugin',
			'deactivated_plugin',
			'save_post',
			'delete_post',
			'deleted_post',
			'trashed_post',
			'untrashed_post',
			'transition_post_status',
			'add_attachment',
			'edit_attachment',
			'delete_attachment',
			'switch_theme',
			'profile_update',
			'user_register',
			'deleted_user',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE => array(
			'activated_plugin',
			'deactivated_plugin',
			'save_post',
			'delete_post',
			'deleted_post',
			'trashed_post',
			'untrashed_post',
			'transition_post_status',
			'set_object_terms',
			'created_term',
			'edited_term',
			'delete_term',
			'switch_theme',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE => array(
			'save_post',
			'delete_post',
			'deleted_post',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE => array(
			'add_attachment',
			'edit_attachment',
			'delete_attachment',
			'updated_post_meta',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE => array(
			'add_attachment',
			'delete_attachment',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE => array(
			'add_attachment',
			'delete_attachment',
			'save_post',
			'deleted_post',
		),
	);
}

/**
 * Register all cache invalidation hooks based on environmental variable map.
 *
 * @return void
 */
function ai4seo_add_invalidate_caches_hooks(): void {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$environmental_variable_to_action_map = ai4seo_get_environmental_variable_to_action_cache_invalidation_map();

	foreach ( $environmental_variable_to_action_map as $this_environmental_variable_name => $this_actions ) {
		foreach ( $this_actions as $this_action ) {
			add_action(
				$this_action,
				function () use ( $this_environmental_variable_name ) {
					ai4seo_invalidate_environmental_variable_cache( $this_environmental_variable_name );
				},
				5,
				20
			);
		}
	}
}



/**
 * Read attachment ID lookup cache entry by normalized filename.
 *
 * @param string $normalized_filename Normalized attachment filename.
 * @return int|false
 */
function ai4seo_get_cached_attachment_id_from_filename( string $normalized_filename ) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 331224226, 'Prevented loop', true );
		return false;
	}

	if ( ! ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE ) ) {
		return false;
	}

	$lookup_cache = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE );

	if ( ! is_array( $lookup_cache ) || ! isset( $lookup_cache[ $normalized_filename ] ) ) {
		return false;
	}

	return (int) $lookup_cache[ $normalized_filename ];
}


/**
 * Store attachment ID lookup cache entry by normalized filename.
 *
 * @param string $normalized_filename Normalized attachment filename.
 * @param int    $attachment_id Attachment post ID.
 * @return void
 */
function ai4seo_set_cached_attachment_id_from_filename( string $normalized_filename, int $attachment_id ): void {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 341224226, 'Prevented loop', true );
		return;
	}

	// Merge and trim inside each CAS retry so another filename cached concurrently is retained.
	ai4seo_mutate_environmental_variable_value(
		AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE,
		static function ( array $lookup_cache ) use ( $normalized_filename, $attachment_id ): array {
			$lookup_cache[ $normalized_filename ] = (int) $attachment_id;

			if ( count( $lookup_cache ) > 200 ) {
				$lookup_cache = array_slice( $lookup_cache, -200, null, true );
			}

			return $lookup_cache;
		},
		true,
		HOUR_IN_SECONDS
	);
}


// endregion
// ___________________________________________________________________________________________.
