<?php
/**
 * Provides WordPress option storage helpers.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region WordPress OPTIONS ===================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound -- Retain this widely used helper's PHP 8 named-argument contract.
/**
 * Lightweight alternative to get_option() using direct $wpdb access.
 *
 * This function:
 * - Reads the option directly from the options table.
 * - Returns the provided default if the option does not exist or a DB error occurs.
 * - Safely unserializes the stored value without instantiating objects.
 *
 * @param string $option_name Name of the option to retrieve.
 * @param mixed  $default     Optional. Default value to return if the option does not exist.
 *                            Default false.
 * @param bool   $use_direct_database_call Optional. Whether to bypass get_option() and query the database directly.
 *
 * @return mixed The option value if found, otherwise the default.
 */
function ai4seo_get_option( string $option_name, $default = false, bool $use_direct_database_call = true ) {
	// phpcs:enable Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 663145060, 'Prevented loop', true );
		return '';
	}

	// If the caller explicitly wants to use get_option() instead of direct DB access, delegate to it.
	if ( ! $use_direct_database_call ) {
		return get_option( $option_name, $default );
	}

	if ( ! isset( $wpdb ) || ! $wpdb ) {
		return $default;
	}

	$option_name = trim( $option_name );

	if ( '' === $option_name ) {
		return $default;
	}

	try {
		// Directly query the options table for this specific option.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- This is the plugin's cache-aware direct option reader.
		$option_value_serialized = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value
             FROM {$wpdb->options}
             WHERE option_name = %s
             LIMIT 1",
				$option_name
			)
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321672, 'Database error: ' . $wpdb->last_error, true );
			return $default;
		}
	} catch ( Exception $exception ) {
		// In case of DB error, fall back to the default.
		return $default;
	}

	// If no row was found, return default.
	if ( null === $option_value_serialized ) {
		return $default;
	}

	// Decode direct database results without permitting stored objects to enter plugin state.
	return ai4seo_safe_maybe_unserialize( $option_value_serialized );
}


/**
 * Reads one option row with the exact bytes required for compare-and-swap writes.
 *
 * @param string $option_name Exact option name.
 * @return array{exists: bool, option_id: int, option_name: string, raw_value: string, value: mixed, autoload: string}|null Snapshot, or null on invalid input/database failure.
 */
function ai4seo_get_raw_option_snapshot( string $option_name ): ?array {
	global $wpdb;

	$option_name = trim( $option_name );

	if ( '' === $option_name || ! is_object( $wpdb ) ) {
		return null;
	}

	$option_snapshot_query = ai4seo_prepare_database_query(
		'SELECT option_id, option_name, option_value, autoload FROM {{options_table}} WHERE option_name = {{option_name}} LIMIT 1',
		array(
			'options_table' => ai4seo_database_identifier_binding( 'table.options' ),
			'option_name'   => ai4seo_database_scalar_binding( '%s', $option_name ),
		)
	);

	if ( false === $option_snapshot_query ) {
		return null;
	}

	// The raw stored bytes are required for an exact compare-and-swap predicate and cannot come from an option cache.
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this exact current-row snapshot.
	$option_snapshot = $wpdb->get_row( $option_snapshot_query, ARRAY_A );

	if ( $wpdb->last_error ) {
		return null;
	}

	if ( null === $option_snapshot ) {
		return array(
			'exists'      => false,
			'option_id'   => 0,
			'option_name' => $option_name,
			'raw_value'   => '',
			'value'       => null,
			'autoload'    => '',
		);
	}

	if (
		! is_array( $option_snapshot )
		|| ! isset( $option_snapshot['option_id'] )
		|| ! ctype_digit( (string) $option_snapshot['option_id'] )
		|| 0 >= (int) $option_snapshot['option_id']
		|| ! isset( $option_snapshot['option_name'] )
		|| ! is_string( $option_snapshot['option_name'] )
		|| ! array_key_exists( 'option_value', $option_snapshot )
		|| ! is_string( $option_snapshot['option_value'] )
		|| ! isset( $option_snapshot['autoload'] )
		|| ! is_string( $option_snapshot['autoload'] )
	) {
		return null;
	}

	return array(
		'exists'      => true,
		'option_id'   => (int) $option_snapshot['option_id'],
		'option_name' => $option_snapshot['option_name'],
		'raw_value'   => $option_snapshot['option_value'],
		'value'       => ai4seo_safe_maybe_unserialize( $option_snapshot['option_value'] ),
		'autoload'    => $option_snapshot['autoload'],
	);
}


/**
 * Reads exact raw snapshots for a non-empty ordered list of option names.
 *
 * The result retains the requested order and contains an explicit missing snapshot for every absent
 * option. Any invalid input, unexpected row, malformed snapshot, or database failure fails the entire
 * batch closed so callers never verify a cross-option transition against a partial result.
 *
 * @param array $option_names Exact option names in request order.
 * @return array<string,array{exists: bool, option_id: int, option_name: string, raw_value: string, value: mixed, autoload: string}>|null Snapshots keyed by exact option name, or null on failure.
 */
function ai4seo_get_raw_option_snapshots( array $option_names ): ?array {
	global $wpdb;

	// Reject invalid or oversized lists because chunking would mix cross-option snapshot generations.
	if (
		! is_object( $wpdb )
		|| ! isset( $wpdb->options )
		|| '' === (string) $wpdb->options
		|| ! $option_names
		|| ! ai4seo_is_database_value_list( $option_names )
		|| count( $option_names ) > ai4seo_get_database_placeholder_budget()
	) {
		return null;
	}

	$active_options_table = (string) $wpdb->options;

	// Preseed ordered missing snapshots while enforcing exact unique names for strict row matching.
	$option_name_lookup = array();
	$option_snapshots   = array();

	foreach ( $option_names as $option_name ) {
		if (
			! is_string( $option_name )
			|| '' === $option_name
			|| trim( $option_name ) !== $option_name
			|| 191 < strlen( $option_name )
			|| array_key_exists( $option_name, $option_name_lookup )
		) {
			return null;
		}

		$option_name_lookup[ $option_name ] = true;
		$option_snapshots[ $option_name ]   = array(
			'exists'      => false,
			'option_id'   => 0,
			'option_name' => $option_name,
			'raw_value'   => '',
			'value'       => null,
			'autoload'    => '',
		);
	}

	try {
		// Compile one active-table statement so every requested row belongs to the same read checkpoint.
		$option_snapshots_query = ai4seo_prepare_database_query(
			'SELECT option_id, option_name, option_value, autoload FROM {{options_table}} WHERE option_name IN ({{option_names}})',
			array(
				'options_table' => ai4seo_database_identifier_binding( 'table.options' ),
				'option_names'  => ai4seo_database_list_binding( '%s', $option_names ),
			)
		);

		if ( false === $option_snapshots_query ) {
			return null;
		}

		// The raw stored bytes are required for exact cross-option verification and cannot come from an option cache.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this bounded active-table snapshot batch.
		$option_snapshot_rows = $wpdb->get_results( $option_snapshots_query, ARRAY_A );
	} catch ( Exception $exception ) {
		return null;
	}

	// A query error, scope switch, or non-list result cannot represent one coherent verification checkpoint.
	if (
		$wpdb->last_error
		|| ! isset( $wpdb->options )
		|| $active_options_table !== (string) $wpdb->options
		|| ! is_array( $option_snapshot_rows )
	) {
		return null;
	}

	// Accept at most one strict row per requested name so ambiguous results fail the whole batch closed.
	$seen_option_names = array();

	foreach ( $option_snapshot_rows as $option_snapshot_row ) {
		if (
			! is_array( $option_snapshot_row )
			|| array_keys( $option_snapshot_row ) !== array( 'option_id', 'option_name', 'option_value', 'autoload' )
			|| ! is_string( $option_snapshot_row['option_name'] )
			|| ! array_key_exists( $option_snapshot_row['option_name'], $option_name_lookup )
			|| array_key_exists( $option_snapshot_row['option_name'], $seen_option_names )
			|| ! is_string( $option_snapshot_row['option_value'] )
			|| ! is_string( $option_snapshot_row['autoload'] )
		) {
			return null;
		}

		$option_id = ai4seo_normalize_database_id( $option_snapshot_row['option_id'] );

		if ( false === $option_id ) {
			return null;
		}

		$option_name     = $option_snapshot_row['option_name'];
		$option_snapshot = array(
			'exists'      => true,
			'option_id'   => $option_id,
			'option_name' => $option_name,
			'raw_value'   => $option_snapshot_row['option_value'],
			'value'       => ai4seo_safe_maybe_unserialize( $option_snapshot_row['option_value'] ),
			'autoload'    => $option_snapshot_row['autoload'],
		);

		if ( ! ai4seo_is_valid_raw_option_snapshot( $option_name, $option_snapshot ) ) {
			return null;
		}

		$seen_option_names[ $option_name ] = true;
		$option_snapshots[ $option_name ]  = $option_snapshot;
	}

	return $option_snapshots;
}


/**
 * Validates that a raw option snapshot belongs to one exact option name.
 *
 * @param string $option_name Exact option name.
 * @param array  $option_snapshot Candidate raw snapshot.
 * @return bool True when the complete snapshot shape and existence state are valid.
 */
function ai4seo_is_valid_raw_option_snapshot( string $option_name, array $option_snapshot ): bool {
	$expected_snapshot_keys = array(
		'exists',
		'option_id',
		'option_name',
		'raw_value',
		'value',
		'autoload',
	);

	if (
		array_keys( $option_snapshot ) !== $expected_snapshot_keys
		|| ! is_bool( $option_snapshot['exists'] )
		|| ! is_int( $option_snapshot['option_id'] )
		|| ! is_string( $option_snapshot['option_name'] )
		|| ! is_string( $option_snapshot['raw_value'] )
		|| ! is_string( $option_snapshot['autoload'] )
		|| $option_name !== $option_snapshot['option_name']
	) {
		return false;
	}

	if ( $option_snapshot['exists'] ) {
		return 0 < $option_snapshot['option_id'] && '' !== $option_snapshot['autoload'];
	}

	return 0 === $option_snapshot['option_id']
		&& '' === $option_snapshot['raw_value']
		&& '' === $option_snapshot['autoload'];
}


/**
 * Invalidates every WordPress cache bucket that can resolve one option.
 *
 * Direct compare-and-swap writers must not publish their owned snapshot after
 * the database mutation. Another writer can commit and cache a newer value
 * between the mutation and cache repair. Invalidating every possible bucket
 * makes the next public read resolve authoritative storage without replacing a
 * later writer's cache entry with stale bytes.
 *
 * @param string $option_name Exact option name.
 * @return void
 */
function ai4seo_invalidate_option_cache( string $option_name ): void {
	$option_name = trim( $option_name );

	if ( '' === $option_name ) {
		return;
	}

	// Clear every WordPress option bucket that may contain the authoritative value or its absence marker.
	if ( function_exists( 'wp_cache_delete' ) ) {
		wp_cache_delete( $option_name, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	// Let the notification owner decide whether this option invalidates its site-scoped safe view.
	if ( function_exists( 'ai4seo_maybe_reset_notification_request_cache' ) ) {
		ai4seo_maybe_reset_notification_request_cache( $option_name );
	}

	// Keep the plugin-owned request memo coherent with every authoritative
	// invalidation, including ambiguous cache-version conflict repair.
	if (
		defined( 'AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME' )
		&& AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME === $option_name
		&& function_exists( 'ai4seo_reset_content_type_list_cache_version_request_cache_for_current_site' )
	) {
		ai4seo_reset_content_type_list_cache_version_request_cache_for_current_site();
	}
}


/**
 * Replaces one exact raw option snapshot without overwriting another writer.
 *
 * A missing snapshot uses the options table's unique option-name key as the insert
 * compare-and-swap. Existing rows match their primary key, exact name, serialized
 * value, and autoload bytes. Option caches are invalidated, and plugin
 * option-change trackers are updated only after this request owns the successful
 * mutation.
 *
 * @param string $option_name Exact option name.
 * @param array  $expected_snapshot Snapshot from ai4seo_get_raw_option_snapshot().
 * @param mixed  $new_option_value Replacement option value.
 * @param bool   $autoload Whether the replacement should be autoloaded.
 * @param bool   $publish_wordpress_hooks Whether to mirror the successful core option actions.
 * @return bool|null True on success, false on a lost compare-and-swap race, null on invalid input/database failure.
 */
function ai4seo_compare_and_swap_option_snapshot(
	string $option_name,
	array $expected_snapshot,
	$new_option_value,
	bool $autoload = false,
	bool $publish_wordpress_hooks = false
): ?bool {
	global $wpdb;

	$option_name = trim( $option_name );

	if ( '' === $option_name || ! is_object( $wpdb ) ) {
		return null;
	}

	if ( ! ai4seo_is_valid_raw_option_snapshot( $option_name, $expected_snapshot ) ) {
		return null;
	}

	// The typed query compiler requires exact string bytes for %s bindings. WordPress stores
	// scalar option values as their string representation even when maybe_serialize() returns
	// an integer or boolean unchanged.
	$serialized_new_option_value = (string) maybe_serialize( $new_option_value );
	$new_autoload                = $autoload ? 'yes' : 'no';

	if ( ! $expected_snapshot['exists'] ) {
		$insert_query = ai4seo_prepare_database_query(
			'INSERT IGNORE INTO {{options_table}} (option_name, option_value, autoload) VALUES ({{option_name}}, {{option_value}}, {{autoload}})',
			array(
				'options_table' => ai4seo_database_identifier_binding( 'table.options' ),
				'option_name'   => ai4seo_database_scalar_binding( '%s', $option_name ),
				'option_value'  => ai4seo_database_scalar_binding( '%s', $serialized_new_option_value ),
				'autoload'      => ai4seo_database_scalar_binding( '%s', $new_autoload ),
			)
		);

		if ( false === $insert_query ) {
			return null;
		}

		// The unique option-name key turns this exact insert into the missing-row compare-and-swap.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this cache-reconciled CAS insert.
		$write_result = $wpdb->query( $insert_query );
	} else {
		$update_query = ai4seo_prepare_database_query(
			'UPDATE {{options_table}} SET option_value = {{new_option_value}}, autoload = {{new_autoload}} WHERE option_id = {{option_id}} AND BINARY option_name = BINARY {{option_name}} AND BINARY option_value = BINARY {{old_option_value}} AND BINARY autoload = BINARY {{old_autoload}}',
			array(
				'options_table'    => ai4seo_database_identifier_binding( 'table.options' ),
				'new_option_value' => ai4seo_database_scalar_binding( '%s', $serialized_new_option_value ),
				'new_autoload'     => ai4seo_database_scalar_binding( '%s', $new_autoload ),
				'option_id'        => ai4seo_database_scalar_binding( '%d', $expected_snapshot['option_id'] ),
				'option_name'      => ai4seo_database_scalar_binding( '%s', $expected_snapshot['option_name'] ),
				'old_option_value' => ai4seo_database_scalar_binding( '%s', $expected_snapshot['raw_value'] ),
				'old_autoload'     => ai4seo_database_scalar_binding( '%s', $expected_snapshot['autoload'] ),
			)
		);

		if ( false === $update_query ) {
			return null;
		}

		// The primary key plus exact row bytes prevents this writer from replacing any concurrent option mutation.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this cache-reconciled CAS update.
		$write_result = $wpdb->query( $update_query );
	}

	if ( false === $write_result || $wpdb->last_error ) {
		return null;
	}

	if ( 1 !== (int) $write_result ) {
		return false;
	}

	ai4seo_invalidate_option_cache( $option_name );
	ai4seo_maybe_reset_generation_status_summary_request_cache( $option_name );
	ai4seo_maybe_bump_content_type_list_cache_version( $option_name );

	if ( function_exists( 'ai4seo_track_generation_status_summary_option_change' ) ) {
		ai4seo_track_generation_status_summary_option_change( $option_name, $expected_snapshot['value'], $new_option_value );
	}

	if ( $publish_wordpress_hooks ) {
		// Environmental hook consumers must resolve the committed option before the mirrored core actions run.
		if (
			defined( 'AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME' )
			&& AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME === $option_name
			&& function_exists( 'ai4seo_read_all_environmental_variables' )
		) {
			ai4seo_read_all_environmental_variables( false );
		}

		if ( $expected_snapshot['exists'] ) {
			// Only the process that replaced the exact snapshot publishes WordPress' update actions.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror WordPress' dynamic option hook after a successful CAS update.
			do_action( "update_option_{$option_name}", $expected_snapshot['value'], $new_option_value, $option_name );
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror WordPress' global option hook after a successful CAS update.
			do_action( 'updated_option', $option_name, $expected_snapshot['value'], $new_option_value );
		} else {
			// Only the process that inserted the missing snapshot publishes WordPress' add actions.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror WordPress' dynamic option hook after a successful CAS insert.
			do_action( "add_option_{$option_name}", $option_name, $new_option_value );
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror WordPress' global option hook after a successful CAS insert.
			do_action( 'added_option', $option_name, $new_option_value );
		}
	}

	return true;
}


/**
 * Deletes one exact raw option snapshot without removing a replacement row.
 *
 * @param string $option_name Exact option name.
 * @param array  $expected_snapshot Snapshot from ai4seo_get_raw_option_snapshot().
 * @param bool   $publish_wordpress_hooks Whether to mirror successful core deletion actions.
 * @return bool|null True on deletion/already-missing success, false on a lost compare-and-swap race, null on invalid input/database failure.
 */
function ai4seo_compare_and_delete_option_snapshot(
	string $option_name,
	array $expected_snapshot,
	bool $publish_wordpress_hooks = false
): ?bool {
	global $wpdb;

	$option_name = trim( $option_name );

	if (
		'' === $option_name
		|| ! is_object( $wpdb )
		|| ! ai4seo_is_valid_raw_option_snapshot( $option_name, $expected_snapshot )
	) {
		return null;
	}

	if ( ! $expected_snapshot['exists'] ) {
		ai4seo_invalidate_option_cache( $option_name );
		ai4seo_maybe_reset_generation_status_summary_request_cache( $option_name );
		return true;
	}

	$delete_query = ai4seo_prepare_database_query(
		'DELETE FROM {{options_table}} WHERE option_id = {{option_id}} AND BINARY option_name = BINARY {{option_name}} AND BINARY option_value = BINARY {{option_value}} AND BINARY autoload = BINARY {{autoload}}',
		array(
			'options_table' => ai4seo_database_identifier_binding( 'table.options' ),
			'option_id'     => ai4seo_database_scalar_binding( '%d', $expected_snapshot['option_id'] ),
			'option_name'   => ai4seo_database_scalar_binding( '%s', $expected_snapshot['option_name'] ),
			'option_value'  => ai4seo_database_scalar_binding( '%s', $expected_snapshot['raw_value'] ),
			'autoload'      => ai4seo_database_scalar_binding( '%s', $expected_snapshot['autoload'] ),
		)
	);

	if ( false === $delete_query ) {
		return null;
	}

	// The exact primary key and row bytes prevent a reset from deleting a concurrently replaced map.
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this cache-reconciled CAS delete.
	$delete_result = $wpdb->query( $delete_query );

	if ( false === $delete_result || $wpdb->last_error ) {
		return null;
	}

	if ( 1 !== (int) $delete_result ) {
		return false;
	}

	ai4seo_invalidate_option_cache( $option_name );
	ai4seo_maybe_reset_generation_status_summary_request_cache( $option_name );
	ai4seo_maybe_bump_content_type_list_cache_version( $option_name );

	if ( function_exists( 'ai4seo_track_generation_status_summary_option_change' ) ) {
		ai4seo_track_generation_status_summary_option_change( $option_name, $expected_snapshot['value'], array() );
	}

	if ( $publish_wordpress_hooks ) {
		// Only the process that deleted the exact snapshot publishes WordPress' deletion actions.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror WordPress' dynamic option hook after a successful CAS delete.
		do_action( "delete_option_{$option_name}", $option_name );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror WordPress' global option hook after a successful CAS delete.
		do_action( 'deleted_option', $option_name );
	}

	return true;
}


/**
 * Refreshes WordPress' targeted option cache entries after direct option table writes.
 *
 * Every invocation reconciles the current cache contents. Request-local desired-state
 * fingerprints are intentionally avoided because WordPress, a persistent-cache drop-in, or
 * another writer can mutate a cache bucket and later return the option to earlier value bytes.
 *
 * @param string           $option_name Option name.
 * @param mixed            $option_value Option value.
 * @param bool             $option_exists Whether the option exists after the write.
 * @param string|bool|null $autoload Autoload value for the option.
 * @return void
 */
function ai4seo_refresh_option_cache( string $option_name, $option_value = null, bool $option_exists = true, $autoload = null ): void {
	$option_name = trim( $option_name );

	if ( '' === $option_name || ! function_exists( 'wp_cache_get' ) ) {
		return;
	}

	// Use WordPress' current autoload values when available so our cache layout matches core behavior.
	$autoload_values = array( 'yes', 'on', 'auto-on', 'auto' );
	if ( function_exists( 'wp_autoload_values_to_autoload' ) ) {
		$autoload_values = wp_autoload_values_to_autoload();
	}

	$is_autoloaded       = ( true === $autoload || in_array( (string) $autoload, $autoload_values, true ) );
	$cached_option_value = maybe_serialize( $option_value );

	// Repair alloptions only when the option belongs there or when a stale entry must be removed.
	// This avoids rewriting the full alloptions payload for frequent non-autoload status updates on large sites.
	$alloptions = wp_cache_get( 'alloptions', 'options' );

	if ( is_array( $alloptions ) ) {
		$did_update_alloptions = false;

		if ( $option_exists && $is_autoloaded ) {
			if ( ! isset( $alloptions[ $option_name ] ) || $alloptions[ $option_name ] !== $cached_option_value ) {
				$alloptions[ $option_name ] = $cached_option_value;
				$did_update_alloptions      = true;
			}
		} elseif ( isset( $alloptions[ $option_name ] ) ) {
			unset( $alloptions[ $option_name ] );
			$did_update_alloptions = true;
		}

		if ( $did_update_alloptions ) {
			wp_cache_set( 'alloptions', $alloptions, 'options' );
		}
	}

	// Keep the individual option cache in the same shape WordPress core expects.
	// Autoloaded options live in alloptions, non-autoloaded options live under their own option key.
	if ( $option_exists && $is_autoloaded ) {
		wp_cache_delete( $option_name, 'options' );
	} elseif ( $option_exists ) {
		wp_cache_set( $option_name, $cached_option_value, 'options' );
	} else {
		wp_cache_delete( $option_name, 'options' );
	}

	// Keep notoptions aligned so a previously missing option starts resolving immediately after insert/update,
	// and a deleted option does not trigger repeated database lookups.
	$notoptions            = wp_cache_get( 'notoptions', 'options' );
	$did_update_notoptions = false;

	if ( $option_exists ) {
		if ( is_array( $notoptions ) && isset( $notoptions[ $option_name ] ) ) {
			unset( $notoptions[ $option_name ] );
			$did_update_notoptions = true;
		}
	} else {
		if ( ! is_array( $notoptions ) ) {
			$notoptions            = array();
			$did_update_notoptions = true;
		}

		if ( ! isset( $notoptions[ $option_name ] ) ) {
			$notoptions[ $option_name ] = true;
			$did_update_notoptions      = true;
		}
	}

	if ( $did_update_notoptions ) {
		wp_cache_set( 'notoptions', $notoptions, 'options' );
	}
}


/**
 * Invalidates every WordPress option-cache bucket touched by a successful bulk delete.
 *
 * @param array $option_names Exact option names removed from the database.
 * @return void
 */
function ai4seo_invalidate_bulk_deleted_option_caches( array $option_names ): void {
	$option_names = array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( $option_name ): string {
						return is_string( $option_name ) ? $option_name : '';
					},
					$option_names
				)
			)
		)
	);

	if ( ! $option_names || ! function_exists( 'wp_cache_delete' ) ) {
		return;
	}

	foreach ( $option_names as $this_option_name ) {
		wp_cache_delete( $this_option_name, 'options' );
	}

	// Both aggregate buckets may contain an observed option, including stale negative entries.
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
}


/**
 * Deletes legacy RobHub API lock options while retaining coherent WordPress option caches.
 *
 * The cleanup advances monotonically through an operation-start option-ID high-water mark. Only
 * the exact primary key plus name/value bytes observed before each delete are removed, and one
 * final bounded lookup fails closed when concurrent matching rows survive or appear later.
 *
 * @return bool True on success.
 */
function ai4seo_delete_legacy_robhub_api_lock_options(): bool {
	global $wpdb;

	$legacy_lock_name_prefix  = $wpdb->esc_like( '_robhub_api_lock_' );
	$legacy_lock_like_pattern = '%' . $legacy_lock_name_prefix . '%';
	$high_water_query         = ai4seo_prepare_database_query(
		'SELECT MAX(option_id) FROM {{options_table}}',
		array(
			'options_table' => ai4seo_database_identifier_binding( 'table.options' ),
		)
	);

	if ( false === $high_water_query ) {
		return false;
	}

	// Validate the active options identifier before a settings lookup can issue SQL through that table property.
	$database_chunk_size = ai4seo_get_database_chunk_size();

	// The primary-key boundary fixes one finite options-table snapshot for this cleanup request.
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this current-state high-water lookup for the bounded mutation.
	$high_water_option_id = $wpdb->get_var( $high_water_query );

	if ( $wpdb->last_error ) {
		return false;
	}

	if ( null === $high_water_option_id ) {
		$high_water_option_id = 0;
	} elseif ( ! ctype_digit( (string) $high_water_option_id ) || 0 >= (int) $high_water_option_id ) {
		return false;
	} else {
		$high_water_option_id = (int) $high_water_option_id;
	}

	$option_id_cursor = 0;

	while ( $option_id_cursor < $high_water_option_id ) {
		$option_rows_query = ai4seo_prepare_database_query(
			'SELECT option_id, option_name, option_value FROM {{options_table}} FORCE INDEX (PRIMARY) WHERE option_id > {{option_id_cursor}} AND option_id <= {{high_water_option_id}} AND option_name LIKE {{lock_pattern}} ORDER BY option_id ASC LIMIT {{query_limit}}',
			array(
				'options_table'        => ai4seo_database_identifier_binding( 'table.options' ),
				'option_id_cursor'     => ai4seo_database_scalar_binding( '%d', $option_id_cursor ),
				'high_water_option_id' => ai4seo_database_scalar_binding( '%d', $high_water_option_id ),
				'lock_pattern'         => ai4seo_database_scalar_binding( '%s', $legacy_lock_like_pattern ),
				'query_limit'          => ai4seo_database_scalar_binding( '%d', $database_chunk_size ),
			)
		);

		if ( false === $option_rows_query ) {
			return false;
		}

		// Each bounded page supplies raw bytes for exact compare-and-delete processing.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this bounded current-state page for the cache-aware mutation.
		$legacy_lock_option_rows = $wpdb->get_results( $option_rows_query, ARRAY_A );

		if ( $wpdb->last_error || ! is_array( $legacy_lock_option_rows ) ) {
			return false;
		}

		if ( ! $legacy_lock_option_rows ) {
			break;
		}

		$next_option_id_cursor = $option_id_cursor;

		foreach ( $legacy_lock_option_rows as $legacy_lock_option_row_index => $legacy_lock_option_row ) {
			if ( ! is_array( $legacy_lock_option_row )
				|| ! isset( $legacy_lock_option_row['option_id'] )
				|| ! ctype_digit( (string) $legacy_lock_option_row['option_id'] )
				|| ! isset( $legacy_lock_option_row['option_name'] )
				|| ! is_string( $legacy_lock_option_row['option_name'] )
				|| ! array_key_exists( 'option_value', $legacy_lock_option_row )
				|| ! is_string( $legacy_lock_option_row['option_value'] )
			) {
				return false;
			}

			$this_option_id = (int) $legacy_lock_option_row['option_id'];

			if ( $this_option_id <= $next_option_id_cursor || $this_option_id > $high_water_option_id ) {
				return false;
			}

			$legacy_lock_option_rows[ $legacy_lock_option_row_index ]['option_id'] = $this_option_id;
			$next_option_id_cursor = $this_option_id;
		}

		$deleted_option_names = array();

		foreach ( $legacy_lock_option_rows as $legacy_lock_option_row ) {
			$delete_query = ai4seo_prepare_database_query(
				'DELETE FROM {{options_table}} WHERE option_id = {{option_id}} AND BINARY option_name = BINARY {{option_name}} AND BINARY option_value = BINARY {{option_value}}',
				array(
					'options_table' => ai4seo_database_identifier_binding( 'table.options' ),
					'option_id'     => ai4seo_database_scalar_binding( '%d', $legacy_lock_option_row['option_id'] ),
					'option_name'   => ai4seo_database_scalar_binding( '%s', $legacy_lock_option_row['option_name'] ),
					'option_value'  => ai4seo_database_scalar_binding( '%s', $legacy_lock_option_row['option_value'] ),
				)
			);

			if ( false === $delete_query ) {
				ai4seo_invalidate_bulk_deleted_option_caches( $deleted_option_names );
				return false;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this primary-key and exact observed name/value comparison; caches for rows actually removed are invalidated below.
			$delete_result = $wpdb->query( $delete_query );

			if ( false === $delete_result || $wpdb->last_error ) {
				ai4seo_invalidate_bulk_deleted_option_caches( $deleted_option_names );
				return false;
			}

			if ( $delete_result > 0 ) {
				$deleted_option_names[] = $legacy_lock_option_row['option_name'];
			}
		}

		ai4seo_invalidate_bulk_deleted_option_caches( $deleted_option_names );
		$option_id_cursor = $next_option_id_cursor;
	}

	$remaining_option_query = ai4seo_prepare_database_query(
		'SELECT option_id FROM {{options_table}} WHERE option_name LIKE {{lock_pattern}} ORDER BY option_id ASC LIMIT 1',
		array(
			'options_table' => ai4seo_database_identifier_binding( 'table.options' ),
			'lock_pattern'  => ai4seo_database_scalar_binding( '%s', $legacy_lock_like_pattern ),
		)
	);

	if ( false === $remaining_option_query ) {
		return false;
	}

	// One final bounded lookup distinguishes a completed snapshot from a currently clean lock set.
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this final existence verification after the direct mutation.
	$remaining_option_id = $wpdb->get_var( $remaining_option_query );

	if ( $wpdb->last_error ) {
		return false;
	}

	return null === $remaining_option_id;
}


/**
 * Update or insert an option using direct $wpdb access.
 *
 * This function behaves similar to update_option(), but bypasses the core
 * update_option() internals and writes directly to the options table.
 *
 * - Inserts the option if it does not exist.
 * - Updates the option if it exists and the value has changed.
 * - Reconciles direct-mode caches successfully when the stored value is unchanged.
 * - Synchronizes the options cache so get_option() sees the new value.
 *
 * @param string           $option_name   Name of the option to update.
 * @param mixed            $option_value  Value to store. Will be maybe_serialize()'d.
 * @param string|bool|null $autoload Optional. Whether to load the option when WordPress starts up.
 *                                   Accepts 'yes', 'no', true, false, or null.
 *                                   Null keeps existing autoload or defaults to 'yes' on insert.
 * @param bool             $use_direct_database_call Optional. Whether to bypass update_option() and query the database directly.
 *
 * @return bool True after a direct write/cache reconciliation or delegated change, false on failure or a delegated no-op.
 */
function ai4seo_update_option( string $option_name, $option_value, $autoload = false, bool $use_direct_database_call = true ): bool {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 635985897, 'Prevented loop', true );
		return false;
	}

	// If the caller explicitly wants to use update_option() instead of direct DB access, delegate to it.
	if ( ! $use_direct_database_call ) {
		// Capture the previous membership before WordPress mutates status options so reconciliation can use a delta.
		$old_value = get_option( $option_name, null );
		$result    = update_option( $option_name, $option_value, $autoload );

		if ( $result ) {
			ai4seo_maybe_bump_content_type_list_cache_version( $option_name );

			// The tracker loads after this helper but is available whenever runtime option mutations occur.
			if ( function_exists( 'ai4seo_track_generation_status_summary_option_change' ) ) {
				ai4seo_track_generation_status_summary_option_change( $option_name, $old_value, $option_value );
			}
		}

		return $result;
	}

	if ( ! isset( $wpdb ) || ! $wpdb ) {
		return false;
	}

	$option_name = trim( $option_name );

	if ( '' === $option_name ) {
		return false;
	}

	// Use ai4seo_get_option() with a distinct default so we can detect non-existent options.
	$old_value = ai4seo_get_option( $option_name, null, $use_direct_database_call );

	// Normalize new vs old for comparison using serialization, matching core semantics.
	$serialized_new = (string) maybe_serialize( $option_value );
	$serialized_old = ( null === $old_value ) ? null : (string) maybe_serialize( $old_value );

	// If storage already matches, invalidate every possible lookup bucket without publishing this
	// observed value. A concurrent writer can commit newer bytes after the read above.
	if ( null !== $old_value && $serialized_new === $serialized_old ) {
		ai4seo_invalidate_option_cache( $option_name );
		ai4seo_maybe_reset_generation_status_summary_request_cache( $option_name );
		return true;
	}

	// Read the current row so we can preserve or inspect autoload and existence.
	try {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- This storage primitive must distinguish inserts from updates and preserve autoload state.
		$existing_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT option_id, option_value, autoload
                 FROM {$wpdb->options}
                 WHERE option_name = %s
                 LIMIT 1",
				$option_name
			),
			ARRAY_A
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321673, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}
	} catch ( Throwable $e ) {
		return false;
	}

	$is_insert = ( null === $existing_row );

	// Resolve and normalize the autoload value.
	if ( null === $autoload ) {
		if ( false === $is_insert && isset( $existing_row['autoload'] ) && '' !== $existing_row['autoload'] ) {
			$autoload = $existing_row['autoload'];
		} else {
			// Default autoload behavior in WordPress is 'yes' for new options.
			$autoload = 'yes';
		}
	} elseif ( 'no' === $autoload || false === $autoload || ( is_string( $autoload ) && strtolower( $autoload ) === 'no' ) ) {
		$autoload = 'no';
	} else {
		$autoload = 'yes';
	}

	// Perform insert or update via $wpdb.
	try {
		if ( true === $is_insert ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- The cache is synchronized immediately after this intentional direct write.
			$result = $wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => $option_name,
					'option_value' => $serialized_new,
					'autoload'     => $autoload,
				),
				array(
					'%s',
					'%s',
					'%s',
				)
			);

			if ( false === $result ) {
				return false;
			}

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321674, 'Database error: ' . $wpdb->last_error, true );
				return false;
			}
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The cache is synchronized immediately after this intentional direct write.
			$result = $wpdb->update(
				$wpdb->options,
				array(
					'option_value' => $serialized_new,
					'autoload'     => $autoload,
				),
				array(
					'option_name' => $option_name,
				),
				array(
					'%s',
					'%s',
				),
				array(
					'%s',
				)
			);

			// Propagate database failures so callers cannot treat unpersisted state as committed.
			if ( false === $result ) {
				return false;
			}
			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321675, 'Database error: ' . $wpdb->last_error, true );
				return false;
			}
		}

		// Never publish the locally written value after direct SQL. A later writer may already own
		// newer bytes, so authoritative invalidation is the only race-safe cache reconciliation.
		ai4seo_invalidate_option_cache( $option_name );
		ai4seo_maybe_reset_generation_status_summary_request_cache( $option_name );
		ai4seo_maybe_bump_content_type_list_cache_version( $option_name );

		// Record only successful source-option mutations; no-op cache repairs returned before this point.
		if ( function_exists( 'ai4seo_track_generation_status_summary_option_change' ) ) {
			ai4seo_track_generation_status_summary_option_change( $option_name, $old_value, $option_value );
		}
	} catch ( Throwable $e ) {
		return false;
	}

	return true;
}


/**
 * Delete an option using direct $wpdb access.
 *
 * This function:
 * - Deletes the option row directly from the options table.
 * - Treats an already-absent row as a successful direct cleanup.
 * - Wraps all $wpdb operations in a try/catch block.
 * - Synchronizes the options cache so get_option() and friends stay in sync.
 *
 * Direct database mode does not trigger WordPress option hooks; delegated mode mirrors delete_option().
 *
 * @param string $option_name Name of the option to delete.
 * @param bool   $use_direct_database_call Optional. Whether to bypass delete_option() and query the database directly.
 *
 * @return bool True if the option was deleted or already absent, false on a detected failure.
 */
function ai4seo_delete_option( string $option_name, bool $use_direct_database_call = true ): bool {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 980160314, 'Prevented loop', true );
		return false;
	}

	if ( ! $use_direct_database_call ) {
		// Capture source-option membership before deletion so the shared tracker can reconcile removed IDs.
		$old_value = get_option( $option_name, null );
		$result    = delete_option( $option_name );

		if ( $result ) {
			ai4seo_maybe_bump_content_type_list_cache_version( $option_name );

			// Deletions use the same request-level reconciliation path as ordinary option updates.
			if ( function_exists( 'ai4seo_track_generation_status_summary_option_change' ) ) {
				ai4seo_track_generation_status_summary_option_change( $option_name, $old_value, array() );
			}
		}

		return $result;
	}

	if ( ! isset( $wpdb ) || ! $wpdb ) {
		return false;
	}

	$option_name = trim( $option_name );

	if ( '' === $option_name ) {
		return false;
	}

	// Read before direct SQL deletion because this writer intentionally bypasses WordPress option hooks.
	$old_value = ai4seo_get_option( $option_name, null, true );

	try {
		// Delete the option row directly from the options table.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The cache is synchronized immediately after this intentional direct delete.
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s",
				$option_name
			)
		);

		if ( false === $result || $wpdb->last_error ) {
			ai4seo_debug_message( 984321676, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}
	} catch ( Exception $exception ) {
		// On DB error, indicate failure.
		return false;
	}

	// Invalidate rather than publishing absence: another writer may have inserted a replacement
	// between the delete and cache reconciliation.
	ai4seo_invalidate_option_cache( $option_name );
	ai4seo_maybe_reset_generation_status_summary_request_cache( $option_name );
	ai4seo_maybe_bump_content_type_list_cache_version( $option_name );

	// Track only an actual row removal; cache-only cleanup does not change authoritative membership.
	if ( 0 < (int) $result && function_exists( 'ai4seo_track_generation_status_summary_option_change' ) ) {
		ai4seo_track_generation_status_summary_option_change( $option_name, $old_value, array() );
	}

	// A completed query with no reported database error owns either the deletion or an already-absent state.
	return true;
}


/**
 * Normalize one option-backed post ID without accepting loose numeric forms.
 *
 * @param mixed $post_id Candidate post ID.
 * @param bool  $allow_zero Whether the canonical zero sentinel is accepted.
 * @return int|false Canonical integer, or false when invalid.
 */
function ai4seo_normalize_option_post_id( $post_id, bool $allow_zero = false ) {
	if ( is_int( $post_id ) ) {
		$normalized_post_id = $post_id;
	} elseif ( is_string( $post_id ) && 1 === preg_match( $allow_zero ? '/^(?:0|[1-9][0-9]*)$/' : '/^[1-9][0-9]*$/', $post_id ) ) {
		$normalized_post_id = (int) $post_id;

		// String equality detects integer overflow without lossy floating-point comparisons.
		if ( (string) $normalized_post_id !== $post_id ) {
			return false;
		}
	} else {
		return false;
	}

	if ( 0 === $normalized_post_id ) {
		return $allow_zero ? 0 : false;
	}

	return 0 < $normalized_post_id ? $normalized_post_id : false;
}


/**
 * Normalize a legacy option value to unique post IDs while preserving first-seen order.
 *
 * @param mixed $option_value Raw, serialized, JSON, or decoded option value.
 * @param bool  $sort_numerically Whether to sort the normalized membership set.
 * @return array<int,int> Canonical positive post IDs.
 */
function ai4seo_normalize_option_post_id_collection( $option_value, bool $sort_numerically = false ): array {
	$option_value = ai4seo_safe_maybe_unserialize( $option_value );

	if ( is_string( $option_value ) && '' !== $option_value && ai4seo_is_json( $option_value ) ) {
		$option_value = json_decode( $option_value, true );
	}

	if ( ! is_array( $option_value ) ) {
		return array();
	}

	$post_ids = array();
	$seen_ids = array();

	foreach ( $option_value as $post_id ) {
		$normalized_post_id = ai4seo_normalize_option_post_id( $post_id );

		if ( false === $normalized_post_id || isset( $seen_ids[ $normalized_post_id ] ) ) {
			continue;
		}

		$seen_ids[ $normalized_post_id ] = true;
		$post_ids[]                      = $normalized_post_id;
	}

	if ( $sort_numerically ) {
		sort( $post_ids, SORT_NUMERIC );
	}

	return $post_ids;
}


/**
 * Reads post IDs from an option while retaining its legacy array and JSON shapes.
 *
 * @param string $option Option name containing post IDs.
 * @return array
 */
function ai4seo_get_post_ids_from_option( string $option ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 769714633, 'Prevented loop', true );
		return array();
	}

	$option = sanitize_key( $option );

	// Read the post-ID collection through the shared direct-option wrapper.
	$post_ids = ai4seo_get_option( $option );

	// Reads must remain side-effect free: a stale empty snapshot must never overwrite a concurrent owner.
	// The checked mutation primitives create a missing option row when a caller actually changes membership.
	if ( ! $post_ids ) {
		return array();
	}

	return ai4seo_normalize_option_post_id_collection( $post_ids );
}


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


/**
 * Normalizes a stored post-ID option value without reading the option again.
 *
 * @param mixed $option_value Raw option value before or after a write.
 * @return array
 */
function ai4seo_normalize_post_ids_from_option_value( $option_value ): array {
	// Stable numeric ordering lets the tracker compare membership instead of storage representation.
	return ai4seo_normalize_option_post_id_collection( $option_value, true );
}


/**
 * Whether exact-site summary change tracking is suppressed by an authoritative bulk rebuild.
 *
 * @return bool True only for the currently active options-table scope.
 */
function ai4seo_is_generation_status_summary_bulk_rebuild_suppressed(): bool {
	global $wpdb;
	global $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes;

	$scope_key = is_object( $wpdb ) && isset( $wpdb->options ) ? (string) $wpdb->options : '';

	return '' !== $scope_key
		&& is_array( $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes ?? null )
		&& isset( $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes[ $scope_key ] )
		&& is_array( $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes[ $scope_key ] )
		&& (int) ( $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes[ $scope_key ]['blog_id'] ?? 0 )
			=== (int) get_current_blog_id();
}


/**
 * Begin one exact-site reset scope whose per-ID changes are superseded by a durable full rebuild.
 *
 * The rebuild marker and any required cron event are verified before the existing site batch is
 * discarded or later source writes are suppressed. Callers must already own every mutation fence
 * for the reset and must restore the returned scope before releasing those fences.
 *
 * @return array Exact scope handle, or an empty array when suppression could not begin safely.
 */
function ai4seo_begin_generation_status_summary_bulk_rebuild_suppression(): array {
	global $wpdb;
	global $ai4seo_generation_status_summary_pending_option_changes;
	global $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes;

	$scope_key = is_object( $wpdb ) && isset( $wpdb->options ) ? (string) $wpdb->options : '';
	$blog_id   = (int) get_current_blog_id();

	if ( '' === $scope_key
		|| $blog_id <= 0
		|| ! function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' )
		|| ! ai4seo_schedule_generation_status_summary_rebuild()
		|| 'required' !== ai4seo_read_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
			false
		) ) {
		return array();
	}

	if ( ! is_array( $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes ?? null ) ) {
		$ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes = array();
	}

	// Reset ownership is not nestable for one site; preserve the original owner if misuse is detected.
	if ( isset( $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes[ $scope_key ] ) ) {
		return array();
	}

	$scope = array(
		'options_table' => $scope_key,
		'blog_id'       => $blog_id,
	);

	$ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes[ $scope_key ] = $scope;

	// The authoritative rebuild supersedes only this site's earlier request-local per-ID transitions.
	if ( is_array( $ai4seo_generation_status_summary_pending_option_changes ?? null ) ) {
		unset( $ai4seo_generation_status_summary_pending_option_changes[ $scope_key ] );
	}

	return $scope;
}


/**
 * Restore exact-site summary tracking before an exclusive bulk-reset owner releases its fences.
 *
 * @param array $scope Exact scope handle returned by the begin helper.
 * @return bool True when the exact suppression scope was removed.
 */
function ai4seo_end_generation_status_summary_bulk_rebuild_suppression( array $scope ): bool {
	global $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes;

	$scope_key = isset( $scope['options_table'] ) && is_string( $scope['options_table'] )
		? $scope['options_table']
		: '';
	$blog_id   = isset( $scope['blog_id'] ) ? (int) $scope['blog_id'] : 0;

	if ( '' === $scope_key || $blog_id <= 0 ) {
		return false;
	}

	if ( ! is_array( $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes ?? null )
		|| ! isset( $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes[ $scope_key ] )
		|| $scope !== $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes[ $scope_key ] ) {
		return false;
	}

	unset( $ai4seo_generation_status_summary_bulk_rebuild_suppression_scopes[ $scope_key ] );

	return true;
}


/**
 * Records actual status-option membership changes for one batched request-level reconciliation.
 *
 * @param string $option_name Option that was successfully mutated.
 * @param mixed  $old_value Option value before the mutation.
 * @param mixed  $new_value Option value after the mutation.
 * @return void
 */
function ai4seo_track_generation_status_summary_option_change( string $option_name, $old_value, $new_value ): void {
	global $wpdb;

	// Ignore settings and internal options that never contribute buckets to the dashboard summary.
	if ( ! in_array( $option_name, ai4seo_get_generation_status_summary_source_option_names(), true ) ) {
		return;
	}

	// Reset-owned bulk changes are reconciled by one durable authoritative rebuild, never per ID.
	if ( ai4seo_is_generation_status_summary_bulk_rebuild_suppressed() ) {
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

	// Accumulate by site and source option so switched-blog writes can never share one reconciliation batch.
	global $ai4seo_generation_status_summary_pending_option_changes;
	global $ai4seo_is_generation_status_summary_flush_registered;

	if ( ! is_array( $ai4seo_generation_status_summary_pending_option_changes ?? null ) ) {
		$ai4seo_generation_status_summary_pending_option_changes = array();
	}

	// The active options table identifies the storage scope even when a multisite request switches blogs.
	$scope_key = (string) $wpdb->options;

	if ( ! isset( $ai4seo_generation_status_summary_pending_option_changes[ $scope_key ] )
		|| ! is_array( $ai4seo_generation_status_summary_pending_option_changes[ $scope_key ] )
		|| ! isset( $ai4seo_generation_status_summary_pending_option_changes[ $scope_key ]['blog_id'] )
		|| ! isset( $ai4seo_generation_status_summary_pending_option_changes[ $scope_key ]['changes'] )
		|| ! is_array( $ai4seo_generation_status_summary_pending_option_changes[ $scope_key ]['changes'] ) ) {
		$ai4seo_generation_status_summary_pending_option_changes[ $scope_key ] = array(
			'blog_id' => (int) get_current_blog_id(),
			'changes' => array(),
		);
	}

	$scope_changes =& $ai4seo_generation_status_summary_pending_option_changes[ $scope_key ]['changes'];

	foreach ( $changed_post_ids as $post_id ) {
		// Preserve the first observed membership and update only the request's latest state for this ID.
		if ( ! isset( $scope_changes[ $option_name ][ $post_id ] ) ) {
			$scope_changes[ $option_name ][ $post_id ] = array(
				'initial' => isset( $old_post_id_lookup[ $post_id ] ),
				'current' => isset( $new_post_id_lookup[ $post_id ] ),
			);
		} else {
			$scope_changes[ $option_name ][ $post_id ]['current'] = isset( $new_post_id_lookup[ $post_id ] );
		}

		// A transition that returns to its initial membership requires no final summary reconciliation.
		if ( $scope_changes[ $option_name ][ $post_id ]['initial']
			=== $scope_changes[ $option_name ][ $post_id ]['current'] ) {
			unset( $scope_changes[ $option_name ][ $post_id ] );
		}
	}

	// Drop empty option buckets so the shutdown flush receives only effective final transitions.
	if ( empty( $scope_changes[ $option_name ] ) ) {
		unset( $scope_changes[ $option_name ] );
	}

	// Remove the site scope when this request's transitions cancel each other completely.
	if ( ! $scope_changes ) {
		unset( $ai4seo_generation_status_summary_pending_option_changes[ $scope_key ] );
	}

	unset( $scope_changes );

	// One late shutdown callback batches cron loops, AJAX bulk actions, and direct queue clears alike.
	if ( empty( $ai4seo_is_generation_status_summary_flush_registered ) ) {
		add_action( 'shutdown', 'ai4seo_flush_generation_status_summary_option_changes', PHP_INT_MAX - 1 );
		$ai4seo_is_generation_status_summary_flush_registered = true;
	}
}


/**
 * Merge one detached summary-change scope back into the live request batch.
 *
 * Changes recorded while the older scope was detached win as the latest membership, while the
 * detached scope retains the combined transition's first observed membership.
 *
 * @param string $options_table Options-table identity for the site scope.
 * @param array  $pending_scope Detached scope containing blog ID and membership changes.
 * @return void
 */
function ai4seo_requeue_generation_status_summary_pending_scope( string $options_table, array $pending_scope ): void {
	global $ai4seo_generation_status_summary_pending_option_changes;

	if (
		'' === $options_table
		|| ! isset( $pending_scope['blog_id'], $pending_scope['changes'] )
		|| ! is_array( $pending_scope['changes'] )
		|| ! $pending_scope['changes']
	) {
		return;
	}

	if ( ! is_array( $ai4seo_generation_status_summary_pending_option_changes ?? null ) ) {
		$ai4seo_generation_status_summary_pending_option_changes = array();
	}

	if (
		! isset( $ai4seo_generation_status_summary_pending_option_changes[ $options_table ] )
		|| ! is_array( $ai4seo_generation_status_summary_pending_option_changes[ $options_table ] )
		|| ! isset( $ai4seo_generation_status_summary_pending_option_changes[ $options_table ]['changes'] )
		|| ! is_array( $ai4seo_generation_status_summary_pending_option_changes[ $options_table ]['changes'] )
	) {
		$ai4seo_generation_status_summary_pending_option_changes[ $options_table ] = array(
			'blog_id' => (int) $pending_scope['blog_id'],
			'changes' => array(),
		);
	}

	$live_changes =& $ai4seo_generation_status_summary_pending_option_changes[ $options_table ]['changes'];

	foreach ( $pending_scope['changes'] as $option_name => $post_id_states ) {
		if ( ! is_string( $option_name ) || ! is_array( $post_id_states ) ) {
			continue;
		}

		foreach ( $post_id_states as $post_id => $detached_state ) {
			if (
				! is_array( $detached_state )
				|| ! isset( $detached_state['initial'], $detached_state['current'] )
				|| ! is_bool( $detached_state['initial'] )
				|| ! is_bool( $detached_state['current'] )
			) {
				continue;
			}

			if ( isset( $live_changes[ $option_name ][ $post_id ] )
				&& is_array( $live_changes[ $option_name ][ $post_id ] )
				&& isset( $live_changes[ $option_name ][ $post_id ]['current'] )
				&& is_bool( $live_changes[ $option_name ][ $post_id ]['current'] ) ) {
				$combined_state = array(
					'initial' => $detached_state['initial'],
					'current' => $live_changes[ $option_name ][ $post_id ]['current'],
				);
			} else {
				$combined_state = $detached_state;
			}

			if ( $combined_state['initial'] === $combined_state['current'] ) {
				unset( $live_changes[ $option_name ][ $post_id ] );
			} else {
				$live_changes[ $option_name ][ $post_id ] = $combined_state;
			}
		}

		if ( empty( $live_changes[ $option_name ] ) ) {
			unset( $live_changes[ $option_name ] );
		}
	}

	if ( ! $live_changes ) {
		unset( $ai4seo_generation_status_summary_pending_option_changes[ $options_table ] );
	}

	unset( $live_changes );
}


/**
 * Preserve a detached site batch and durably request an authoritative replacement analysis.
 *
 * @param string $options_table Options-table identity for the site scope.
 * @param array  $pending_scope Detached scope to preserve for any later flush in this request.
 * @return bool True when a verified rebuild event exists.
 */
function ai4seo_defer_generation_status_summary_pending_scope( string $options_table, array $pending_scope ): bool {
	ai4seo_requeue_generation_status_summary_pending_scope( $options_table, $pending_scope );

	if ( ! function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' ) ) {
		ai4seo_debug_message( 175943824, 'Could not schedule a deferred generation-status summary reconciliation.', true );
		return false;
	}

	$rebuild_scheduled = ai4seo_schedule_generation_status_summary_rebuild();

	if ( ! $rebuild_scheduled ) {
		ai4seo_debug_message( 175943824, 'Could not schedule a deferred generation-status summary reconciliation.', true );
	}

	return $rebuild_scheduled;
}


/**
 * Reconciles all status-option changes collected during the current request.
 *
 * @return bool True when every site batch was reconciled or durably deferred.
 */
function ai4seo_flush_generation_status_summary_option_changes(): bool {
	global $ai4seo_generation_status_summary_pending_option_changes;
	global $ai4seo_environmental_variables_are_loaded;
	global $wpdb;

	// Requests without effective membership changes have no summary work to perform.
	if ( ! is_array( $ai4seo_generation_status_summary_pending_option_changes ?? null )
		|| ! $ai4seo_generation_status_summary_pending_option_changes ) {
		return true;
	}

	// Detach the current batch before reconciliation so nested source writes cannot replay it.
	$pending_scopes = $ai4seo_generation_status_summary_pending_option_changes;
	$ai4seo_generation_status_summary_pending_option_changes = array();

	// The owning generation module can be unavailable in isolated option-helper tests.
	if ( ! function_exists( 'ai4seo_sync_generation_status_summary_for_option_changes' ) ) {
		foreach ( $pending_scopes as $options_table => $pending_scope ) {
			if ( is_string( $options_table ) && is_array( $pending_scope ) ) {
				ai4seo_requeue_generation_status_summary_pending_scope( $options_table, $pending_scope );
			}
		}

		return false;
	}

	$all_scopes_succeeded = true;

	// Re-enter each captured site before reducing and reconciling its final request-level transitions.
	foreach ( $pending_scopes as $options_table => $pending_scope ) {
		if ( ! is_array( $pending_scope )
			|| ! isset( $pending_scope['blog_id'], $pending_scope['changes'] )
			|| ! is_array( $pending_scope['changes'] )
			|| ! $pending_scope['changes'] ) {
			$all_scopes_succeeded = false;
			continue;
		}

		$target_blog_id   = (int) $pending_scope['blog_id'];
		$switched_to_blog = false;

		// Switch only when the captured batch belongs to another active multisite blog.
		if ( get_current_blog_id() !== $target_blog_id ) {
			if ( ! is_multisite()
				|| ! function_exists( 'switch_to_blog' )
				|| ! function_exists( 'restore_current_blog' )
				|| ! switch_to_blog( $target_blog_id ) ) {
				ai4seo_requeue_generation_status_summary_pending_scope( (string) $options_table, $pending_scope );
				$all_scopes_succeeded = false;
				continue;
			}

			$switched_to_blog = true;
		}

		try {
			// Reject a stale/deleted blog ID rather than applying its batch to a different options table.
			if ( (string) $wpdb->options !== (string) $options_table ) {
				ai4seo_requeue_generation_status_summary_pending_scope( (string) $options_table, $pending_scope );
				$all_scopes_succeeded = false;
				continue;
			}

			$pending_option_changes = array();

			foreach ( $pending_scope['changes'] as $option_name => $post_id_states ) {
				if ( ! is_array( $post_id_states ) ) {
					continue;
				}

				foreach ( array_keys( $post_id_states ) as $post_id ) {
					$pending_option_changes[ $option_name ][ $post_id ] = true;
				}
			}

			if ( ! $pending_option_changes ) {
				continue;
			}

			$database_lock_name = function_exists( 'ai4seo_get_posts_table_analysis_database_lock_name' )
				? ai4seo_get_posts_table_analysis_database_lock_name()
				: '';

			// Never race a full analysis. Preserve the request batch and persist a restart requirement instead.
			if ( '' === $database_lock_name || ! ai4seo_acquire_database_advisory_lock( $database_lock_name ) ) {
				$scope_deferred       = ai4seo_defer_generation_status_summary_pending_scope( (string) $options_table, $pending_scope );
				$all_scopes_succeeded = $all_scopes_succeeded && $scope_deferred;
				continue;
			}

			$scope_succeeded   = false;
			$release_succeeded = false;

			try {
				if ( 'processing' === ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, false ) ) {
					$scope_succeeded = ai4seo_defer_generation_status_summary_pending_scope( (string) $options_table, $pending_scope );
				} else {
					$sync_succeeded = false;
					ai4seo_sync_generation_status_summary_for_option_changes( $pending_option_changes, $sync_succeeded );

					if ( $sync_succeeded ) {
						$scope_succeeded = true;
					} else {
						$scope_succeeded = ai4seo_defer_generation_status_summary_pending_scope( (string) $options_table, $pending_scope );
					}
				}
			} finally {
				$release_succeeded = ai4seo_release_database_advisory_lock( $database_lock_name );
			}

			if ( ! $release_succeeded ) {
				ai4seo_defer_generation_status_summary_pending_scope( (string) $options_table, $pending_scope );
				$scope_succeeded = false;
			}

			$all_scopes_succeeded = $all_scopes_succeeded && $scope_succeeded;
		} finally {
			if ( $switched_to_blog ) {
				restore_current_blog();
				$ai4seo_environmental_variables_are_loaded = false;
			}
		}
	}

	return $all_scopes_succeeded;
}

/**
 * Returns the bounded retry budget for post-ID option mutations.
 *
 * @return int
 */
function ai4seo_get_post_id_option_mutation_attempt_limit(): int {
	return 5;
}


/**
 * Return the shared site-local semaphore name for every post-ID option mutation.
 *
 * @return string Critical-section name.
 */
function ai4seo_get_post_id_option_transition_semaphore_name(): string {
	return 'post-id-option-transition';
}


/**
 * Renew and verify ownership of the shared post-ID transition fence.
 *
 * Long option collections can take longer than the semaphore TTL to normalize and persist. Every
 * fenced transition therefore checkpoints the exact request-local token at phase boundaries instead
 * of assuming that the initial acquisition remains valid for the whole operation.
 *
 * @return bool True only while this request still owns the exact site-local semaphore token.
 */
function ai4seo_renew_post_id_option_transition_semaphore(): bool {
	return function_exists( 'ai4seo_renew_semaphore' )
		&& ai4seo_renew_semaphore( ai4seo_get_post_id_option_transition_semaphore_name() );
}


/**
 * Replace one post-ID option with an exact empty collection while the shared fence is held.
 *
 * @param string $option_name Option to clear.
 * @param bool   $membership_changed Receives whether valid post-ID membership changed.
 * @return bool True after exact empty storage was persisted or observed.
 */
function ai4seo_clear_post_id_option_under_lock( string $option_name, bool &$membership_changed ): bool {
	$membership_changed = false;
	$attempt_limit      = ai4seo_get_post_id_option_mutation_attempt_limit();
	$empty_raw_value    = (string) maybe_serialize( array() );

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$option_snapshot = ai4seo_get_raw_option_snapshot( $option_name );

		if ( null === $option_snapshot ) {
			return false;
		}

		$current_post_ids = ai4seo_normalize_option_post_id_collection( $option_snapshot['value'] );

		if (
			$option_snapshot['exists']
			&& 'no' === $option_snapshot['autoload']
			&& hash_equals( $empty_raw_value, $option_snapshot['raw_value'] )
		) {
			ai4seo_invalidate_option_cache( $option_name );
			return ai4seo_renew_post_id_option_transition_semaphore();
		}

		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			$option_name,
			$option_snapshot,
			array(),
			false
		);

		if ( true === $compare_and_swap_result ) {
			$membership_changed = ! empty( $current_post_ids );
			return ai4seo_renew_post_id_option_transition_semaphore();
		}

		if ( null === $compare_and_swap_result ) {
			return false;
		}
	}

	ai4seo_invalidate_option_cache( $option_name );
	return false;
}


/**
 * Clear multiple post-ID options inside the shared site-scoped transition fence.
 *
 * Every target is attempted even after an earlier database failure, then authoritative storage is
 * re-read before the fence is released. This lets callers distinguish a complete reset from a
 * retryable partial clear without allowing an unfenced queue writer to interleave.
 *
 * @param array $option_names Post-ID option names to clear.
 * @param bool  $did_change Receives whether any valid membership changed.
 * @return bool True only when every exact empty value was verified and the fence released.
 */
function ai4seo_clear_post_id_options( array $option_names, &$did_change = null ): bool {
	$normalized_option_names = array();
	$did_change              = false;

	foreach ( $option_names as $option_name ) {
		if ( ! is_string( $option_name ) ) {
			continue;
		}

		$option_name = sanitize_key( $option_name );

		if ( '' === $option_name || in_array( $option_name, $normalized_option_names, true ) ) {
			continue;
		}

		$normalized_option_names[] = $option_name;
	}

	if ( ! $normalized_option_names ) {
		return false;
	}

	$critical_section_name  = ai4seo_get_post_id_option_transition_semaphore_name();
	$clears_succeeded       = true;
	$verification_succeeded = true;
	$release_succeeded      = false;

	if ( ! function_exists( 'ai4seo_acquire_semaphore' ) || ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		return false;
	}

	try {
		foreach ( $normalized_option_names as $option_name ) {
			$this_membership_changed = false;
			$this_clear_succeeded    = ai4seo_clear_post_id_option_under_lock( $option_name, $this_membership_changed );

			$clears_succeeded = $clears_succeeded && $this_clear_succeeded;
			$did_change       = $did_change || $this_membership_changed;
		}

		// Verify and release one potentially site-wide collection at a time to keep peak memory bounded.
		foreach ( $normalized_option_names as $option_name ) {
			if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
				$verification_succeeded = false;
				break;
			}

			$option_snapshot = ai4seo_get_raw_option_snapshot( $option_name );

			if (
				null === $option_snapshot
				|| ! $option_snapshot['exists']
				|| ai4seo_normalize_option_post_id_collection( $option_snapshot['value'] )
			) {
				$verification_succeeded = false;
			}

			// Release the decoded site-wide collection before the next snapshot is allocated.
			unset( $option_snapshot );
		}

		if ( $verification_succeeded && ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			$verification_succeeded = false;
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	return $clears_succeeded && $verification_succeeded && $release_succeeded;
}


/**
 * Clear current Pending memberships and their paired Force markers from one exact fenced snapshot.
 *
 * The pair map is intentionally restricted to the plugin's two Pending-to-Force relationships. No
 * caller-supplied candidate IDs are accepted: each generation is observed only after the shared fence
 * is held. Every required write gets one exact raw-snapshot CAS, so an out-of-band conflict fails closed
 * instead of semantically retrying against a later generation of the same numeric post ID.
 *
 * @param array $primary_to_paired_option_names Pending option names mapped to their Force option.
 * @param array $removed_post_ids_by_primary Receives canonical Pending IDs cleared per primary option.
 * @return bool True only when every requested pair was verified and the shared fence released.
 */
function ai4seo_clear_primary_post_id_option_pairs( array $primary_to_paired_option_names, &$removed_post_ids_by_primary = null ): bool {
	$allowed_pairs                 = array(
		AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME => AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME => AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);
	$processing_options_by_primary = array(
		AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME => AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME => AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);
	$normalized_pairs              = array();

	foreach ( $primary_to_paired_option_names as $primary_option_name => $paired_option_name ) {
		if ( ! is_string( $primary_option_name ) || ! is_string( $paired_option_name ) ) {
			continue;
		}

		$primary_option_name = sanitize_key( $primary_option_name );
		$paired_option_name  = sanitize_key( $paired_option_name );

		if ( ! isset( $allowed_pairs[ $primary_option_name ] ) || $allowed_pairs[ $primary_option_name ] !== $paired_option_name ) {
			continue;
		}

		$normalized_pairs[ $primary_option_name ] = $paired_option_name;
	}

	$removed_post_ids_by_primary = array();

	if ( ! $normalized_pairs || count( $normalized_pairs ) !== count( $primary_to_paired_option_names ) ) {
		return false;
	}

	$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();
	$pair_snapshots        = array();
	$operation_succeeded   = true;
	$release_succeeded     = false;
	$observed_post_ids     = array();

	if ( ! function_exists( 'ai4seo_acquire_semaphore' ) || ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		return false;
	}

	try {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		// Freeze every pair's exact raw generation before the first destructive write.
		foreach ( $normalized_pairs as $primary_option_name => $paired_option_name ) {
			if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
				$operation_succeeded = false;
				break;
			}

			$primary_snapshot = ai4seo_get_raw_option_snapshot( $primary_option_name );
			$paired_snapshot  = ai4seo_get_raw_option_snapshot( $paired_option_name );

			if ( null === $primary_snapshot || null === $paired_snapshot ) {
				$operation_succeeded = false;
				break;
			}

			$pair_snapshots[ $primary_option_name ]    = array(
				'paired_option_name' => $paired_option_name,
				'primary'            => $primary_snapshot,
				'paired'             => $paired_snapshot,
			);
			$observed_post_ids[ $primary_option_name ] = ai4seo_normalize_option_post_id_collection( $primary_snapshot['value'] );
		}

		if ( $operation_succeeded ) {
			foreach ( $pair_snapshots as $primary_option_name => $pair_snapshot ) {
				if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
					$operation_succeeded = false;
					break;
				}

				$primary_post_ids = $observed_post_ids[ $primary_option_name ];

				if ( ! $primary_post_ids ) {
					continue;
				}

				$paired_post_ids     = ai4seo_normalize_option_post_id_collection( $pair_snapshot['paired']['value'] );
				$paired_post_ids_new = array_values( array_diff( $paired_post_ids, $primary_post_ids ) );

				// Remove Force first so a later Pending conflict cannot leave a marker that changes future admission semantics.
				if (
					$paired_post_ids !== $paired_post_ids_new
					&& (
						! ai4seo_renew_post_id_option_transition_semaphore()
						|| true !== ai4seo_compare_and_swap_option_snapshot(
							$pair_snapshot['paired_option_name'],
							$pair_snapshot['paired'],
							$paired_post_ids_new,
							false
						)
					)
				) {
					$operation_succeeded = false;
					break;
				}

				if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
					$operation_succeeded = false;
					break;
				}

				// A second-stage conflict safely retains Pending without Force for a checked retry.
				if ( true !== ai4seo_compare_and_swap_option_snapshot( $primary_option_name, $pair_snapshot['primary'], array(), false ) ) {
					$operation_succeeded = false;
					break;
				}
			}
		}

		if ( $operation_succeeded ) {
			// Explicit queue clearing supersedes only orphan rollback intent for the Pending IDs it removed.
			foreach ( $normalized_pairs as $primary_option_name => $paired_option_name ) {
				$reconciled_post_ids = array();

				if (
					$observed_post_ids[ $primary_option_name ]
					&& ! ai4seo_reconcile_orphan_processing_rollback_leases_under_lock(
						$processing_options_by_primary[ $primary_option_name ],
						$primary_option_name,
						$paired_option_name,
						$observed_post_ids[ $primary_option_name ],
						true,
						array(),
						null,
						$reconciled_post_ids
					)
				) {
					$operation_succeeded = false;
					break;
				}
			}
		}

		if ( $operation_succeeded ) {
			foreach ( $normalized_pairs as $primary_option_name => $paired_option_name ) {
				if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
					$operation_succeeded = false;
					break;
				}

				$primary_snapshot = ai4seo_get_raw_option_snapshot( $primary_option_name );
				$paired_snapshot  = ai4seo_get_raw_option_snapshot( $paired_option_name );
				$removed_lookup   = array_fill_keys( $observed_post_ids[ $primary_option_name ], true );

				if ( null === $primary_snapshot || null === $paired_snapshot ) {
					$operation_succeeded = false;
					break;
				}

				if ( ai4seo_normalize_option_post_id_collection( $primary_snapshot['value'] ) ) {
					$operation_succeeded = false;
					break;
				}

				foreach ( ai4seo_normalize_option_post_id_collection( $paired_snapshot['value'] ) as $paired_post_id ) {
					if ( isset( $removed_lookup[ $paired_post_id ] ) ) {
						$operation_succeeded = false;
						break 2;
					}
				}
			}

			if ( $operation_succeeded && ! ai4seo_renew_post_id_option_transition_semaphore() ) {
				$operation_succeeded = false;
			}
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	if ( ! $operation_succeeded || ! $release_succeeded ) {
		return false;
	}

	$removed_post_ids_by_primary = $observed_post_ids;
	return true;
}


/**
 * Normalize an option-to-post-IDs mutation map.
 *
 * Invalid ID shapes are ignored rather than coerced to another post. Option names retain the
 * public helpers' sanitize_key() compatibility behavior.
 *
 * @param array $mutation_map Option names mapped to a scalar or list of post IDs.
 * @return array<string,array<int,int>> Canonical mutation map.
 */
function ai4seo_normalize_post_id_option_mutation_map( array $mutation_map ): array {
	$normalized_mutation_map = array();

	foreach ( $mutation_map as $option_name => $post_ids ) {
		if ( ! is_string( $option_name ) ) {
			continue;
		}

		$option_name = sanitize_key( $option_name );

		if ( '' === $option_name ) {
			continue;
		}

		if ( ! is_array( $post_ids ) ) {
			$post_ids = array( $post_ids );
		}

		$post_ids = ai4seo_normalize_option_post_id_collection( $post_ids );

		if ( ! $post_ids ) {
			continue;
		}

		$existing_post_ids                       = $normalized_mutation_map[ $option_name ] ?? array();
		$normalized_mutation_map[ $option_name ] = ai4seo_normalize_option_post_id_collection(
			array_merge( $existing_post_ids, $post_ids )
		);
	}

	return $normalized_mutation_map;
}


/**
 * Apply one exact post-ID option mutation with bounded compare-and-swap retries.
 *
 * @param string $option_name Exact option name.
 * @param array  $post_ids_to_add Canonical IDs to append when absent.
 * @param array  $post_ids_to_remove Canonical IDs to remove.
 * @param bool   $membership_changed Receives whether persisted membership changed.
 * @return bool True after a successful or already-achieved mutation.
 */
function ai4seo_mutate_post_id_option_membership(
	string $option_name,
	array $post_ids_to_add,
	array $post_ids_to_remove,
	bool &$membership_changed
): bool {
	$membership_changed = false;
	$remove_lookup      = array_fill_keys( $post_ids_to_remove, true );
	$attempt_limit      = ai4seo_get_post_id_option_mutation_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$option_snapshot = ai4seo_get_raw_option_snapshot( $option_name );

		if ( null === $option_snapshot ) {
			return false;
		}

		$current_post_ids = ai4seo_normalize_option_post_id_collection( $option_snapshot['value'] );
		$new_post_ids     = array();

		foreach ( $current_post_ids as $post_id ) {
			if ( ! isset( $remove_lookup[ $post_id ] ) ) {
				$new_post_ids[] = $post_id;
			}
		}

		$new_post_id_lookup = array_fill_keys( $new_post_ids, true );

		foreach ( $post_ids_to_add as $post_id ) {
			if ( isset( $new_post_id_lookup[ $post_id ] ) ) {
				continue;
			}

			$new_post_id_lookup[ $post_id ] = true;
			$new_post_ids[]                 = $post_id;
		}

		$this_attempt_changed = $current_post_ids !== $new_post_ids;
		$replacement_raw      = (string) maybe_serialize( $new_post_ids );

		// Removing from a missing option is already achieved and must not create an empty row.
		if ( ! $option_snapshot['exists'] && ! $new_post_ids ) {
			ai4seo_invalidate_option_cache( $option_name );
			return ai4seo_renew_post_id_option_transition_semaphore();
		}

		// Exact storage is a successful no-op; invalidation cannot overwrite a later cache writer.
		if (
			$option_snapshot['exists']
			&& 'no' === $option_snapshot['autoload']
			&& hash_equals( $replacement_raw, $option_snapshot['raw_value'] )
		) {
			ai4seo_invalidate_option_cache( $option_name );
			$membership_changed = $this_attempt_changed;
			return ai4seo_renew_post_id_option_transition_semaphore();
		}

		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			$option_name,
			$option_snapshot,
			$new_post_ids,
			false
		);

		if ( true === $compare_and_swap_result ) {
			$membership_changed = $this_attempt_changed;
			return ai4seo_renew_post_id_option_transition_semaphore();
		}

		if ( null === $compare_and_swap_result ) {
			return false;
		}
	}

	ai4seo_invalidate_option_cache( $option_name );
	return false;
}


/**
 * Apply one normalized post-ID transition while the shared semaphore is already held.
 *
 * @param array $additions Normalized option names mapped to IDs that must be present.
 * @param array $removals Normalized option names mapped to IDs that must be absent.
 * @param bool  $did_change Receives whether any option membership changed.
 * @param array $changed_options Receives option names whose membership this call mutated.
 * @return bool True only when the complete requested final state was verified.
 */
function ai4seo_apply_normalized_post_id_option_transition_under_lock(
	array $additions,
	array $removals,
	&$did_change,
	&$changed_options = null
): bool {
	$option_set      = array_fill_keys( array_merge( array_keys( $additions ), array_keys( $removals ) ), true );
	$did_change      = false;
	$changed_options = array();

	if ( ! $option_set ) {
		return false;
	}

	if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
		return false;
	}

	$queue_addition_option_names = array(
		AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
		AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);
	$queue_addition_is_effective = false;

	foreach ( $queue_addition_option_names as $queue_option_name ) {
		$post_ids_to_add = $additions[ $queue_option_name ] ?? array();

		if ( ! $post_ids_to_add ) {
			continue;
		}

		$queue_snapshot = ai4seo_get_raw_option_snapshot( $queue_option_name );

		if ( null === $queue_snapshot || ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$current_queue_lookup = array_fill_keys(
			ai4seo_normalize_option_post_id_collection( $queue_snapshot['value'] ),
			true
		);

		foreach ( $post_ids_to_add as $post_id_to_add ) {
			if ( ! isset( $current_queue_lookup[ $post_id_to_add ] ) ) {
				$queue_addition_is_effective = true;
				break 2;
			}
		}
	}

	// A surviving inspection cursor may observe queue removals only. Exact deletion must therefore
	// be durable before publishing any new Pending or Force membership under this same fence.
	if (
		$queue_addition_is_effective
		&& ! ai4seo_clear_disabled_queue_inspection_state_under_lock()
	) {
		return false;
	}

	$pending_to_force_option_names = array(
		AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME => AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME => AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);

	// A safe Pending admission that explicitly removes Force must establish non-force mode first.
	// Otherwise a failure between Pending addition and Force removal exposes a consumable forced entry.
	foreach ( $pending_to_force_option_names as $pending_option_name => $force_option_name ) {
		$force_post_ids_to_remove_before_pending = array_values(
			array_intersect(
				$additions[ $pending_option_name ] ?? array(),
				$removals[ $force_option_name ] ?? array()
			)
		);

		if ( ! $force_post_ids_to_remove_before_pending ) {
			continue;
		}

		$this_option_changed = false;

		if (
			! ai4seo_mutate_post_id_option_membership(
				$force_option_name,
				array(),
				$force_post_ids_to_remove_before_pending,
				$this_option_changed
			)
		) {
			return false;
		}

		$did_change = $did_change || $this_option_changed;

		if ( $this_option_changed ) {
			$changed_options[ $force_option_name ] = true;
		}
	}

	$addition_option_names = array_keys( $additions );
	usort(
		$addition_option_names,
		static function ( string $left_option_name, string $right_option_name ): int {
			$pending_option_names = array(
				AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			);
			$left_priority        = in_array( $left_option_name, $pending_option_names, true ) ? 0 : 1;
			$right_priority       = in_array( $right_option_name, $pending_option_names, true ) ? 0 : 1;

			// Force is always the final addition: it is meaningful only alongside durable Pending ownership.
			if ( in_array( $left_option_name, AI4SEO_FORCE_OVERWRITE_BULK_GENERATION_POST_ID_OPTIONS, true ) ) {
				$left_priority = 2;
			}

			if ( in_array( $right_option_name, AI4SEO_FORCE_OVERWRITE_BULK_GENERATION_POST_ID_OPTIONS, true ) ) {
				$right_priority = 2;
			}

			return $left_priority === $right_priority
				? strcmp( $left_option_name, $right_option_name )
				: $left_priority <=> $right_priority;
		}
	);

	// Establish every destination membership before removing any previous ownership state.
	foreach ( $addition_option_names as $option_name ) {
		$this_option_changed = false;

		if (
			! ai4seo_mutate_post_id_option_membership(
				$option_name,
				$additions[ $option_name ],
				array(),
				$this_option_changed
			)
		) {
			return false;
		}

		$did_change = $did_change || $this_option_changed;

		if ( $this_option_changed ) {
			$changed_options[ $option_name ] = true;
		}
	}

	// Verify and release one potentially site-wide collection before beginning the destructive phase.
	foreach ( $addition_option_names as $option_name ) {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$option_snapshot = ai4seo_get_raw_option_snapshot( $option_name );

		if ( null === $option_snapshot ) {
			return false;
		}

		$current_lookup = array_fill_keys(
			ai4seo_normalize_option_post_id_collection( $option_snapshot['value'] ),
			true
		);

		foreach ( $additions[ $option_name ] as $post_id ) {
			if ( ! isset( $current_lookup[ $post_id ] ) ) {
				return false;
			}
		}

		// Release the decoded destination collection before entering the next checkpoint or phase.
		unset( $option_snapshot, $current_lookup );
	}

	$removal_option_names = array_keys( $removals );
	usort(
		$removal_option_names,
		static function ( string $left_option_name, string $right_option_name ): int {
			$processing_option_names = array(
				AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			);
			$left_priority           = in_array( $left_option_name, $processing_option_names, true ) ? 0 : 1;
			$right_priority          = in_array( $right_option_name, $processing_option_names, true ) ? 0 : 1;

			return $left_priority === $right_priority
				? strcmp( $left_option_name, $right_option_name )
				: $left_priority <=> $right_priority;
		}
	);

	// Remove prior ownership only after destinations have been durably observed.
	foreach ( $removal_option_names as $option_name ) {
		// When one option appears in both maps, the requested addition wins.
		$effective_removals = array_values(
			array_diff(
				$removals[ $option_name ],
				$additions[ $option_name ] ?? array()
			)
		);

		if ( ! $effective_removals ) {
			continue;
		}

		$this_option_changed = false;

		if (
			! ai4seo_mutate_post_id_option_membership(
				$option_name,
				array(),
				$effective_removals,
				$this_option_changed
			)
		) {
			return false;
		}

		$did_change = $did_change || $this_option_changed;

		if ( $this_option_changed ) {
			$changed_options[ $option_name ] = true;
		}
	}

	// Sort the full option set so sequential fresh reads verify the requested end state deterministically.
	ksort( $option_set, SORT_STRING );

	foreach ( array_keys( $option_set ) as $option_name ) {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$option_snapshot = ai4seo_get_raw_option_snapshot( $option_name );

		if ( null === $option_snapshot ) {
			return false;
		}

		$current_lookup  = array_fill_keys(
			ai4seo_normalize_option_post_id_collection( $option_snapshot['value'] ),
			true
		);
		$addition_lookup = array_fill_keys( $additions[ $option_name ] ?? array(), true );

		foreach ( $addition_lookup as $post_id => $unused ) {
			if ( ! isset( $current_lookup[ $post_id ] ) ) {
				return false;
			}
		}

		foreach ( $removals[ $option_name ] ?? array() as $post_id ) {
			if ( ! isset( $addition_lookup[ $post_id ] ) && isset( $current_lookup[ $post_id ] ) ) {
				return false;
			}
		}

		// Bound peak memory to one decoded verification collection at a time.
		unset( $option_snapshot, $current_lookup, $addition_lookup );
	}

	return ai4seo_renew_post_id_option_transition_semaphore();
}


/**
 * Apply a site-scoped cross-option post-ID transition.
 *
 * Every target uses bounded exact CAS retries and owns one deterministic site-local semaphore until
 * final-state verification. Additions are persisted and verified before removals so a second-stage
 * failure leaves duplicate ownership for a retry instead of losing the post ID entirely. Single-option
 * writers participate in the same fence as related multi-option transitions.
 *
 * @param array $additions Option names mapped to IDs that must be present.
 * @param array $removals Option names mapped to IDs that must be absent.
 * @param bool  $did_change Receives whether any option membership changed.
 * @return bool True only when the complete requested final state was verified and the lock released.
 */
function ai4seo_apply_post_id_option_transition( array $additions, array $removals, &$did_change = null ): bool {
	$additions  = ai4seo_normalize_post_id_option_mutation_map( $additions );
	$removals   = ai4seo_normalize_post_id_option_mutation_map( $removals );
	$option_set = array_fill_keys( array_merge( array_keys( $additions ), array_keys( $removals ) ), true );
	$did_change = false;

	if ( ! $option_set ) {
		return false;
	}

	$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();
	$transition_succeeded  = false;
	$release_succeeded     = false;

	if ( ! function_exists( 'ai4seo_acquire_semaphore' ) || ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		return false;
	}

	try {
		$transition_succeeded = ai4seo_apply_normalized_post_id_option_transition_under_lock(
			$additions,
			$removals,
			$did_change
		);
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	return $transition_succeeded && $release_succeeded;
}


/**
 * Apply an admission transition after filtering current blocking memberships inside the shared fence.
 *
 * Blocking options are read-only predicates for this operation. Candidate IDs found in any blocker are
 * removed from every requested addition and removal before the transition begins, so a stale caller
 * snapshot can never demote active Processing ownership.
 *
 * @param array $additions Option names mapped to candidate IDs that must be present.
 * @param array $removals Option names mapped to candidate IDs that must be absent.
 * @param array $blocking_option_names Option names whose current members must be excluded.
 * @param array $admitted_post_ids Receives candidates not found in a blocker under the fence.
 * @param bool  $did_change Receives whether any admitted membership changed.
 * @return bool True only when blocking state and the complete filtered transition were verified.
 */
function ai4seo_apply_post_id_option_admission_transition(
	array $additions,
	array $removals,
	array $blocking_option_names,
	&$admitted_post_ids = null,
	&$did_change = null
): bool {
	$additions          = ai4seo_normalize_post_id_option_mutation_map( $additions );
	$removals           = ai4seo_normalize_post_id_option_mutation_map( $removals );
	$candidate_post_ids = array();
	$did_change         = false;
	$admitted_post_ids  = array();

	foreach ( $additions as $addition_post_ids ) {
		$candidate_post_ids = array_merge( $candidate_post_ids, $addition_post_ids );
	}

	$candidate_post_ids = ai4seo_normalize_option_post_id_collection( $candidate_post_ids );

	if ( ! $candidate_post_ids ) {
		return false;
	}

	$normalized_blocking_option_names = array();

	foreach ( $blocking_option_names as $blocking_option_name ) {
		if ( ! is_string( $blocking_option_name ) ) {
			return false;
		}

		$blocking_option_name = sanitize_key( $blocking_option_name );

		if ( '' === $blocking_option_name ) {
			return false;
		}

		$normalized_blocking_option_names[ $blocking_option_name ] = true;
	}

	if ( ! $normalized_blocking_option_names ) {
		return false;
	}

	ksort( $normalized_blocking_option_names, SORT_STRING );

	$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();
	$operation_succeeded   = false;
	$release_succeeded     = false;
	$eligible_post_ids     = array();

	if ( ! function_exists( 'ai4seo_acquire_semaphore' ) || ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		return false;
	}

	try {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$blocked_post_id_lookup = array();

		foreach ( array_keys( $normalized_blocking_option_names ) as $blocking_option_name ) {
			if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
				return false;
			}

			$blocking_snapshot = ai4seo_get_raw_option_snapshot( $blocking_option_name );

			if ( null === $blocking_snapshot ) {
				return false;
			}

			foreach ( ai4seo_normalize_option_post_id_collection( $blocking_snapshot['value'] ) as $blocked_post_id ) {
				$blocked_post_id_lookup[ $blocked_post_id ] = true;
			}
		}

		$eligible_post_ids = array_values(
			array_filter(
				$candidate_post_ids,
				static function ( int $post_id ) use ( $blocked_post_id_lookup ): bool {
					return ! isset( $blocked_post_id_lookup[ $post_id ] );
				}
			)
		);

		if ( ! $eligible_post_ids ) {
			$operation_succeeded = true;
		} else {
			foreach ( $additions as $option_name => $mutation_post_ids ) {
				if ( isset( $normalized_blocking_option_names[ $option_name ] ) ) {
					unset( $additions[ $option_name ] );
					continue;
				}

				$additions[ $option_name ] = array_values( array_intersect( $mutation_post_ids, $eligible_post_ids ) );

				if ( ! $additions[ $option_name ] ) {
					unset( $additions[ $option_name ] );
				}
			}

			foreach ( $removals as $option_name => $mutation_post_ids ) {
				if ( isset( $normalized_blocking_option_names[ $option_name ] ) ) {
					unset( $removals[ $option_name ] );
					continue;
				}

				$removals[ $option_name ] = array_values( array_intersect( $mutation_post_ids, $eligible_post_ids ) );

				if ( ! $removals[ $option_name ] ) {
					unset( $removals[ $option_name ] );
				}
			}

			$operation_succeeded = ai4seo_apply_normalized_post_id_option_transition_under_lock(
				$additions,
				$removals,
				$did_change
			);
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	if ( ! $operation_succeeded || ! $release_succeeded ) {
		$did_change = false;
		return false;
	}

	$admitted_post_ids = $eligible_post_ids;
	return true;
}


/**
 * Return the durable claim-lease option paired with a production Processing option.
 *
 * @param string $processing_option_name Processing option name.
 * @return string Site-local lease option name, or an empty string for an unknown context.
 */
function ai4seo_get_processing_claim_lease_option_name( string $processing_option_name ): string {
	switch ( sanitize_key( $processing_option_name ) ) {
		case AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME:
			return 'ai4seo_processing_metadata_claim_leases';

		case AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			return 'ai4seo_processing_attachment_attributes_claim_leases';

		default:
			return '';
	}
}


/**
 * Fingerprint one exact raw option snapshot for durable queue-inspection ownership.
 *
 * @param string $option_name Expected option name.
 * @param array  $option_snapshot Exact raw option snapshot.
 * @return string Lowercase SHA-256 fingerprint, or an empty string for an invalid snapshot.
 */
function ai4seo_get_disabled_queue_inspection_snapshot_fingerprint( string $option_name, array $option_snapshot ): string {
	if ( ! ai4seo_is_valid_raw_option_snapshot( $option_name, $option_snapshot ) ) {
		return '';
	}

	return hash(
		'sha256',
		(string) maybe_serialize(
			array(
				'exists'      => $option_snapshot['exists'],
				'option_id'   => $option_snapshot['option_id'],
				'option_name' => $option_snapshot['option_name'],
				'raw_value'   => $option_snapshot['raw_value'],
				'autoload'    => $option_snapshot['autoload'],
			)
		)
	);
}


/**
 * Normalize the durable metadata queue-inspection continuation envelope.
 *
 * @param mixed $state_value Stored option value.
 * @return array{version:int,settings_fingerprint:string,metadata_pending_fingerprint:string,metadata_force_fingerprint:string,last_inspected_metadata_post_id:int,complete:bool}|null Strict state, or null when malformed.
 */
function ai4seo_normalize_disabled_queue_inspection_state( $state_value ): ?array {
	$state_value = ai4seo_safe_maybe_unserialize( $state_value );
	$state_keys  = array(
		'version',
		'settings_fingerprint',
		'metadata_pending_fingerprint',
		'metadata_force_fingerprint',
		'last_inspected_metadata_post_id',
		'complete',
	);

	if (
		! is_array( $state_value )
		|| array_keys( $state_value ) !== $state_keys
		|| 1 !== $state_value['version']
		|| ! is_string( $state_value['settings_fingerprint'] )
		|| 1 !== preg_match( '/\A[0-9a-f]{64}\z/D', $state_value['settings_fingerprint'] )
		|| ! is_string( $state_value['metadata_pending_fingerprint'] )
		|| 1 !== preg_match( '/\A[0-9a-f]{64}\z/D', $state_value['metadata_pending_fingerprint'] )
		|| ! is_string( $state_value['metadata_force_fingerprint'] )
		|| 1 !== preg_match( '/\A[0-9a-f]{64}\z/D', $state_value['metadata_force_fingerprint'] )
		|| ! is_int( $state_value['last_inspected_metadata_post_id'] )
		|| 0 > $state_value['last_inspected_metadata_post_id']
		|| ! is_bool( $state_value['complete'] )
		|| ( $state_value['complete'] && 0 !== $state_value['last_inspected_metadata_post_id'] )
	) {
		return null;
	}

	return $state_value;
}


/**
 * Read the site-local queue-inspection state while the shared transition fence is held.
 *
 * Malformed storage is returned as a valid raw snapshot with null normalized state so a caller can
 * safely restart at the beginning and exact-replace it without treating advisory cursor bytes as queue intent.
 *
 * @param array      $state_snapshot Receives the exact raw state option snapshot.
 * @param array|null $state Receives normalized state, or null for missing/malformed storage.
 * @return bool Whether authoritative storage and fence ownership were verified.
 */
function ai4seo_read_disabled_queue_inspection_state_under_lock( array &$state_snapshot, ?array &$state ): bool {
	$state_snapshot = array();
	$state          = null;

	if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
		return false;
	}

	$current_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_DISABLED_QUEUE_INSPECTION_STATE_OPTION_NAME );

	if ( null === $current_snapshot ) {
		return false;
	}

	$state_snapshot = $current_snapshot;
	$state          = $current_snapshot['exists']
		? ai4seo_normalize_disabled_queue_inspection_state( $current_snapshot['value'] )
		: null;

	return ai4seo_renew_post_id_option_transition_semaphore();
}


/**
 * Exact-replace and verify the non-autoloaded site-local queue-inspection state.
 *
 * @param array $expected_snapshot Exact prior state option snapshot.
 * @param array $replacement_state Strict replacement state.
 * @return bool Whether the replacement bytes and fence ownership were verified.
 */
function ai4seo_replace_disabled_queue_inspection_state_under_lock( array $expected_snapshot, array $replacement_state ): bool {
	$normalized_state = ai4seo_normalize_disabled_queue_inspection_state( $replacement_state );

	if (
		null === $normalized_state
		|| ! ai4seo_is_valid_raw_option_snapshot( AI4SEO_DISABLED_QUEUE_INSPECTION_STATE_OPTION_NAME, $expected_snapshot )
		|| ! ai4seo_renew_post_id_option_transition_semaphore()
	) {
		return false;
	}

	$replacement_raw = (string) maybe_serialize( $normalized_state );

	if (
		! $expected_snapshot['exists']
		|| 'no' !== $expected_snapshot['autoload']
		|| ! hash_equals( $replacement_raw, $expected_snapshot['raw_value'] )
	) {
		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			AI4SEO_DISABLED_QUEUE_INSPECTION_STATE_OPTION_NAME,
			$expected_snapshot,
			$normalized_state,
			false
		);

		if ( true !== $compare_and_swap_result ) {
			return false;
		}
	} else {
		ai4seo_invalidate_option_cache( AI4SEO_DISABLED_QUEUE_INSPECTION_STATE_OPTION_NAME );
	}

	if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
		return false;
	}

	$verified_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_DISABLED_QUEUE_INSPECTION_STATE_OPTION_NAME );

	return is_array( $verified_snapshot )
		&& $verified_snapshot['exists']
		&& 'no' === $verified_snapshot['autoload']
		&& hash_equals( $replacement_raw, $verified_snapshot['raw_value'] )
		&& ai4seo_normalize_disabled_queue_inspection_state( $verified_snapshot['value'] ) === $normalized_state
		&& ai4seo_renew_post_id_option_transition_semaphore();
}


/**
 * Exact-delete and verify the advisory queue-inspection state under the shared transition fence.
 *
 * @param callable|null $ownership_checkpoint Optional stronger reset-ownership checkpoint.
 * @return bool Whether the state is authoritatively absent and ownership remains held.
 */
function ai4seo_clear_disabled_queue_inspection_state_under_lock( ?callable $ownership_checkpoint = null ): bool {
	$checkpoint = static function () use ( $ownership_checkpoint ): bool {
		return ai4seo_renew_post_id_option_transition_semaphore()
			&& ( null === $ownership_checkpoint || true === $ownership_checkpoint() );
	};

	if ( ! $checkpoint() ) {
		return false;
	}

	$state_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_DISABLED_QUEUE_INSPECTION_STATE_OPTION_NAME );

	if ( null === $state_snapshot ) {
		return false;
	}

	if ( $state_snapshot['exists'] ) {
		if ( ! $checkpoint() ) {
			return false;
		}

		$delete_result = ai4seo_compare_and_delete_option_snapshot(
			AI4SEO_DISABLED_QUEUE_INSPECTION_STATE_OPTION_NAME,
			$state_snapshot
		);

		if ( true !== $delete_result ) {
			return false;
		}
	}

	if ( ! $checkpoint() ) {
		return false;
	}

	$verified_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_DISABLED_QUEUE_INSPECTION_STATE_OPTION_NAME );

	return is_array( $verified_snapshot )
		&& ! $verified_snapshot['exists']
		&& $checkpoint();
}


/**
 * Normalize a durable Processing claim-lease map.
 *
 * @param mixed $lease_value Stored option value.
 * @return array<int,array{token:string,expires_at:int,force_overwrite:bool,pending_was_present?:bool,force_was_present?:bool,rollback_requested?:bool,preserve_new_queue_memberships?:bool,ambiguous_spend?:bool,terminal_force_overwrite?:bool,terminal_destination_option_names?:array<int,string>,terminal_removal_option_names?:array<int,string>}> Valid leases keyed by post ID.
 */
function ai4seo_normalize_processing_claim_leases( $lease_value ): array {
	$lease_value = ai4seo_safe_maybe_unserialize( $lease_value );

	if ( ! is_array( $lease_value ) ) {
		return array();
	}

	$normalized_leases = array();

	foreach ( $lease_value as $post_id => $lease ) {
		$post_id = ai4seo_normalize_option_post_id( $post_id );

		if (
			false === $post_id
			|| ! is_array( $lease )
			|| ! isset( $lease['token'], $lease['expires_at'] )
			|| ! is_string( $lease['token'] )
			|| '' === $lease['token']
			|| strlen( $lease['token'] ) > 128
			|| 1 !== preg_match( '/^[A-Za-z0-9_-]+$/D', $lease['token'] )
			|| ! is_int( $lease['expires_at'] )
			|| 0 > $lease['expires_at']
		) {
			continue;
		}

		$normalized_lease = array(
			'token'           => $lease['token'],
			'expires_at'      => $lease['expires_at'],
			'force_overwrite' => true === ( $lease['force_overwrite'] ?? false ),
		);

		if ( is_bool( $lease['terminal_force_overwrite'] ?? null ) ) {
			$normalized_lease['terminal_force_overwrite'] = $lease['terminal_force_overwrite'];
		}

		if ( is_bool( $lease['pending_was_present'] ?? null ) ) {
			$normalized_lease['pending_was_present'] = $lease['pending_was_present'];
		}

		if ( is_bool( $lease['force_was_present'] ?? null ) ) {
			$normalized_lease['force_was_present'] = $lease['force_was_present'];
		}

		if ( true === ( $lease['rollback_requested'] ?? false ) ) {
			$normalized_lease['rollback_requested'] = $lease['rollback_requested'];
		}

		if ( true === ( $lease['preserve_new_queue_memberships'] ?? false ) ) {
			$normalized_lease['preserve_new_queue_memberships'] = $lease['preserve_new_queue_memberships'];
		}

		if ( true === ( $lease['ambiguous_spend'] ?? false ) ) {
			$normalized_lease['ambiguous_spend'] = $lease['ambiguous_spend'];
		}

		if ( is_array( $lease['terminal_removal_option_names'] ?? null ) ) {
			$terminal_removal_option_names = array();

			foreach ( $lease['terminal_removal_option_names'] as $terminal_removal_option_name ) {
				if (
					! is_string( $terminal_removal_option_name )
					|| ! in_array( $terminal_removal_option_name, AI4SEO_ALL_POST_ID_OPTIONS, true )
				) {
					$terminal_removal_option_names = array();
					break;
				}

				$terminal_removal_option_names[] = $terminal_removal_option_name;
			}

			$terminal_removal_option_names = array_values( array_unique( $terminal_removal_option_names ) );
			sort( $terminal_removal_option_names, SORT_STRING );

			if ( count( $terminal_removal_option_names ) === count( $lease['terminal_removal_option_names'] ) ) {
				$normalized_lease['terminal_removal_option_names'] = $terminal_removal_option_names;
			}
		}

		if ( is_array( $lease['terminal_destination_option_names'] ?? null ) ) {
			$terminal_destination_option_names = array();

			foreach ( $lease['terminal_destination_option_names'] as $terminal_destination_option_name ) {
				if (
					! is_string( $terminal_destination_option_name )
					|| ! in_array( $terminal_destination_option_name, AI4SEO_ALL_POST_ID_OPTIONS, true )
				) {
					$terminal_destination_option_names = array();
					break;
				}

				$terminal_destination_option_names[] = $terminal_destination_option_name;
			}

			$terminal_destination_option_names = array_values( array_unique( $terminal_destination_option_names ) );
			sort( $terminal_destination_option_names, SORT_STRING );

			if ( count( $terminal_destination_option_names ) === count( $lease['terminal_destination_option_names'] ) ) {
				$normalized_lease['terminal_destination_option_names'] = $terminal_destination_option_names;
			}
		}

		$normalized_leases[ $post_id ] = $normalized_lease;
	}

	ksort( $normalized_leases, SORT_NUMERIC );
	return $normalized_leases;
}


/**
 * Strictly validate a durable Processing claim-lease map without discarding rows or fields.
 *
 * Production ownership readers and writers must distinguish an empty map from corrupted storage.
 * Silently normalizing malformed rows would let a later mutation erase an unrelated live owner.
 *
 * @param mixed $lease_value Stored option value.
 * @param array $leases Receives normalized leases only when the complete map is valid.
 * @return bool Whether every map key, row, and supported field is valid.
 */
function ai4seo_validate_processing_claim_leases( $lease_value, array &$leases ): bool {
	$leases           = array();
	$validated_leases = array();
	$lease_value      = ai4seo_safe_maybe_unserialize( $lease_value );

	if ( ! is_array( $lease_value ) ) {
		return false;
	}

	$allowed_lease_keys = array(
		'token',
		'expires_at',
		'force_overwrite',
		'pending_was_present',
		'force_was_present',
		'rollback_requested',
		'preserve_new_queue_memberships',
		'ambiguous_spend',
		'terminal_force_overwrite',
		'terminal_destination_option_names',
		'terminal_removal_option_names',
	);

	foreach ( $lease_value as $post_id => $lease ) {
		$normalized_post_id = ai4seo_normalize_option_post_id( $post_id );

		if (
			false === $normalized_post_id
			|| ! is_array( $lease )
			|| array_diff( array_keys( $lease ), $allowed_lease_keys )
			|| ! isset( $lease['token'], $lease['expires_at'] )
			|| ! is_string( $lease['token'] )
			|| '' === $lease['token']
			|| strlen( $lease['token'] ) > 128
			|| 1 !== preg_match( '/^[A-Za-z0-9_-]+$/D', $lease['token'] )
			|| ! is_int( $lease['expires_at'] )
			|| 0 > $lease['expires_at']
			|| ! array_key_exists( 'force_overwrite', $lease )
			|| ! is_bool( $lease['force_overwrite'] )
			|| ( array_key_exists( 'pending_was_present', $lease ) && ! is_bool( $lease['pending_was_present'] ) )
			|| ( array_key_exists( 'force_was_present', $lease ) && ! is_bool( $lease['force_was_present'] ) )
			|| ( array_key_exists( 'rollback_requested', $lease ) && true !== $lease['rollback_requested'] )
			|| ( array_key_exists( 'preserve_new_queue_memberships', $lease ) && true !== $lease['preserve_new_queue_memberships'] )
			|| ( array_key_exists( 'ambiguous_spend', $lease ) && true !== $lease['ambiguous_spend'] )
			|| ( array_key_exists( 'terminal_force_overwrite', $lease ) && ! is_bool( $lease['terminal_force_overwrite'] ) )
			|| ( array_key_exists( 'terminal_destination_option_names', $lease ) && ! is_array( $lease['terminal_destination_option_names'] ) )
			|| ( array_key_exists( 'terminal_removal_option_names', $lease ) && ! is_array( $lease['terminal_removal_option_names'] ) )
		) {
			return false;
		}

		$has_pending_snapshot = array_key_exists( 'pending_was_present', $lease );
		$has_force_snapshot   = array_key_exists( 'force_was_present', $lease );
		$terminal_field_count = (int) array_key_exists( 'terminal_force_overwrite', $lease )
			+ (int) array_key_exists( 'terminal_destination_option_names', $lease )
			+ (int) array_key_exists( 'terminal_removal_option_names', $lease );

		if (
			$has_pending_snapshot !== $has_force_snapshot
			|| ( 0 !== $terminal_field_count && 3 !== $terminal_field_count )
			|| ( isset( $lease['rollback_requested'] ) && ( ! $has_pending_snapshot || 0 !== $terminal_field_count ) )
			|| ( isset( $lease['preserve_new_queue_memberships'] )
				&& (
					! isset( $lease['rollback_requested'] )
					|| ! $has_pending_snapshot
					|| ( false === $lease['pending_was_present'] && false === $lease['force_was_present'] )
					|| $lease['force_overwrite'] !== $lease['force_was_present']
				) )
			|| ( isset( $lease['ambiguous_spend'] )
				&& (
					! $has_pending_snapshot
					|| false !== ( $lease['pending_was_present'] ?? null )
					|| false !== ( $lease['force_was_present'] ?? null )
					|| isset( $lease['rollback_requested'] )
					|| 0 !== $terminal_field_count
				)
			)
		) {
			return false;
		}

		$terminal_removal_option_names     = array();
		$terminal_destination_option_names = array();

		// Removal-only terminal intent has no destinations; its required removals are validated below.
		if ( array_key_exists( 'terminal_destination_option_names', $lease ) ) {
			if ( array_values( $lease['terminal_destination_option_names'] ) !== $lease['terminal_destination_option_names'] ) {
				return false;
			}

			foreach ( $lease['terminal_destination_option_names'] as $terminal_destination_option_name ) {
				if (
					! is_string( $terminal_destination_option_name )
					|| ! in_array( $terminal_destination_option_name, AI4SEO_ALL_POST_ID_OPTIONS, true )
					|| in_array( $terminal_destination_option_name, $terminal_destination_option_names, true )
				) {
					return false;
				}

				$terminal_destination_option_names[] = $terminal_destination_option_name;
			}

			sort( $terminal_destination_option_names, SORT_STRING );
		}

		if ( array_key_exists( 'terminal_removal_option_names', $lease ) ) {
			if ( array_values( $lease['terminal_removal_option_names'] ) !== $lease['terminal_removal_option_names'] ) {
				return false;
			}

			foreach ( $lease['terminal_removal_option_names'] as $terminal_removal_option_name ) {
				if (
					! is_string( $terminal_removal_option_name )
					|| ! in_array( $terminal_removal_option_name, AI4SEO_ALL_POST_ID_OPTIONS, true )
					|| in_array( $terminal_removal_option_name, $terminal_removal_option_names, true )
				) {
					return false;
				}

				$terminal_removal_option_names[] = $terminal_removal_option_name;
			}

			if ( ! $terminal_removal_option_names ) {
				return false;
			}

			sort( $terminal_removal_option_names, SORT_STRING );
		}

		if ( array_intersect( $terminal_destination_option_names, $terminal_removal_option_names ) ) {
			return false;
		}

		$normalized_lease = array(
			'token'           => $lease['token'],
			'expires_at'      => $lease['expires_at'],
			'force_overwrite' => true === ( $lease['force_overwrite'] ?? false ),
		);

		if ( array_key_exists( 'terminal_force_overwrite', $lease ) ) {
			$normalized_lease['terminal_force_overwrite'] = $lease['terminal_force_overwrite'];
		}

		if ( array_key_exists( 'pending_was_present', $lease ) ) {
			$normalized_lease['pending_was_present'] = $lease['pending_was_present'];
		}

		if ( array_key_exists( 'force_was_present', $lease ) ) {
			$normalized_lease['force_was_present'] = $lease['force_was_present'];
		}

		if ( array_key_exists( 'rollback_requested', $lease ) ) {
			$normalized_lease['rollback_requested'] = $lease['rollback_requested'];
		}

		if ( array_key_exists( 'preserve_new_queue_memberships', $lease ) ) {
			$normalized_lease['preserve_new_queue_memberships'] = $lease['preserve_new_queue_memberships'];
		}

		if ( array_key_exists( 'ambiguous_spend', $lease ) ) {
			$normalized_lease['ambiguous_spend'] = $lease['ambiguous_spend'];
		}

		if ( array_key_exists( 'terminal_removal_option_names', $lease ) ) {
			$normalized_lease['terminal_removal_option_names'] = $terminal_removal_option_names;
		}

		if ( array_key_exists( 'terminal_destination_option_names', $lease ) ) {
			$normalized_lease['terminal_destination_option_names'] = $terminal_destination_option_names;
		}

		$validated_leases[ $normalized_post_id ] = $normalized_lease;
	}

	// Two differently shaped keys must never collapse onto one authoritative post ID.
	if ( count( $validated_leases ) !== count( $lease_value ) ) {
		return false;
	}

	ksort( $validated_leases, SORT_NUMERIC );
	$leases = $validated_leases;
	return true;
}


/**
 * Validate terminal lease intent against the queue family owned by its Processing option.
 *
 * @param string $processing_option_name Processing option name.
 * @param array  $leases Strictly normalized lease map.
 * @return bool Whether every terminal option belongs to the same queue context.
 */
function ai4seo_validate_processing_claim_lease_context( string $processing_option_name, array $leases ): bool {
	switch ( sanitize_key( $processing_option_name ) ) {
		case AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME:
			$context_option_names = array(
				AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
			);
			$pending_option_name  = AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME;
			$force_option_name    = AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME;
			break;

		case AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			$context_option_names = array(
				AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			);
			$pending_option_name  = AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;
			$force_option_name    = AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;
			break;

		default:
			return false;
	}

	$context_option_lookup = array_fill_keys( $context_option_names, true );

	foreach ( $leases as $lease ) {
		if ( ! array_key_exists( 'terminal_force_overwrite', $lease ) ) {
			continue;
		}

		$destination_option_names = $lease['terminal_destination_option_names'];
		$removal_option_names     = $lease['terminal_removal_option_names'];

		foreach ( array_merge( $destination_option_names, $removal_option_names ) as $option_name ) {
			if ( ! isset( $context_option_lookup[ $option_name ] ) ) {
				return false;
			}
		}

		$force_is_destination = in_array( $force_option_name, $destination_option_names, true );
		$force_is_removal     = in_array( $force_option_name, $removal_option_names, true );

		if (
			! in_array( $processing_option_name, $removal_option_names, true )
			|| ! in_array( $pending_option_name, $removal_option_names, true )
			|| ( $lease['terminal_force_overwrite'] && ( ! $force_is_destination || $force_is_removal ) )
			|| ( ! $lease['terminal_force_overwrite'] && ( $force_is_destination || ! $force_is_removal ) )
		) {
			return false;
		}
	}

	return true;
}


/**
 * Return a lease duration longer than every supported generation request budget.
 *
 * @return int Lease duration in seconds.
 */
function ai4seo_get_processing_claim_lease_ttl_seconds(): int {
	$bulk_generation_duration = 60;

	if ( function_exists( 'ai4seo_get_setting' ) && defined( 'AI4SEO_SETTING_BULK_GENERATION_DURATION' ) ) {
		$stored_duration = (int) ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_DURATION );

		if ( 0 < $stored_duration ) {
			$bulk_generation_duration = min( 300, $stored_duration );
		}
	}

	return max( 180, $bulk_generation_duration + 120 );
}


/**
 * Determine whether one normalized lease still owns Processing.
 *
 * @param array    $lease Normalized lease record.
 * @param int|null $current_time Optional deterministic current timestamp.
 * @return bool Whether the lease has not expired.
 */
function ai4seo_is_processing_claim_lease_active( array $lease, ?int $current_time = null ): bool {
	$current_time = null === $current_time ? time() : $current_time;

	return isset( $lease['token'], $lease['expires_at'] )
		&& is_string( $lease['token'] )
		&& '' !== $lease['token']
		&& is_int( $lease['expires_at'] )
		&& $lease['expires_at'] > $current_time;
}


/**
 * Create an opaque Processing ownership token.
 *
 * @return string Claim token.
 */
function ai4seo_create_processing_claim_token(): string {
	if ( function_exists( 'wp_generate_uuid4' ) ) {
		return wp_generate_uuid4();
	}

	return hash( 'sha256', uniqid( 'ai4seo_processing_claim_', true ) . (string) wp_rand() );
}


/**
 * Read a production Processing lease map while the shared transition fence is held.
 *
 * @param string $processing_option_name Processing option name.
 * @param array  $leases Receives normalized leases.
 * @return bool True when authoritative storage was read under an owned fence.
 */
function ai4seo_read_processing_claim_leases_under_lock( string $processing_option_name, array &$leases ): bool {
	$leases            = array();
	$lease_option_name = ai4seo_get_processing_claim_lease_option_name( $processing_option_name );

	if ( '' === $lease_option_name || ! ai4seo_renew_post_id_option_transition_semaphore() ) {
		return false;
	}

	$lease_snapshot = ai4seo_get_raw_option_snapshot( $lease_option_name );

	if ( null === $lease_snapshot ) {
		return false;
	}

	if ( ! $lease_snapshot['exists'] ) {
		$leases = array();
	} else {
		$normalized_leases = array();

		if (
			! ai4seo_validate_processing_claim_leases( $lease_snapshot['value'], $normalized_leases )
			|| ! ai4seo_validate_processing_claim_lease_context( $processing_option_name, $normalized_leases )
		) {
			return false;
		}

		$leases = $normalized_leases;
	}

	return ai4seo_renew_post_id_option_transition_semaphore();
}


/**
 * Token-conditionally replace or remove one production Processing claim lease.
 *
 * An empty expected token matches only an absent lease. A null expected token is an
 * unconditional fenced repair and is reserved for stale-lease reconciliation.
 *
 * @param string      $processing_option_name Processing option name.
 * @param int         $post_id Canonical post ID.
 * @param array|null  $replacement_lease Replacement lease, or null to remove it.
 * @param string|null $expected_token Token predicate; empty means absent, null means unconditional.
 * @param bool        $predicate_matched Receives whether the token predicate matched.
 * @return bool True after a verified mutation/no-op, false on storage or fence failure.
 */
function ai4seo_mutate_processing_claim_lease_under_lock(
	string $processing_option_name,
	int $post_id,
	?array $replacement_lease,
	?string $expected_token,
	bool &$predicate_matched
): bool {
	$lease_option_name = ai4seo_get_processing_claim_lease_option_name( $processing_option_name );
	$post_id           = ai4seo_normalize_option_post_id( $post_id );
	$predicate_matched = false;

	if ( '' === $lease_option_name || false === $post_id ) {
		return false;
	}

	if ( null !== $replacement_lease ) {
		$normalized_replacement = array();

		if (
			! ai4seo_validate_processing_claim_leases( array( $post_id => $replacement_lease ), $normalized_replacement )
			|| ! ai4seo_validate_processing_claim_lease_context( $processing_option_name, $normalized_replacement )
			|| ! isset( $normalized_replacement[ $post_id ] )
		) {
			return false;
		}

		$replacement_lease = $normalized_replacement[ $post_id ];
	}

	$attempt_limit = ai4seo_get_post_id_option_mutation_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$lease_snapshot = ai4seo_get_raw_option_snapshot( $lease_option_name );

		if ( null === $lease_snapshot ) {
			return false;
		}

		$current_leases = array();

		if (
			$lease_snapshot['exists']
			&& (
				! ai4seo_validate_processing_claim_leases( $lease_snapshot['value'], $current_leases )
				|| ! ai4seo_validate_processing_claim_lease_context( $processing_option_name, $current_leases )
			)
		) {
			return false;
		}

		$current_token = isset( $current_leases[ $post_id ] ) ? $current_leases[ $post_id ]['token'] : '';

		if ( null !== $expected_token && ! hash_equals( $expected_token, $current_token ) ) {
			return ai4seo_renew_post_id_option_transition_semaphore();
		}

		$predicate_matched = true;

		if ( null === $replacement_lease ) {
			unset( $current_leases[ $post_id ] );
		} else {
			$current_leases[ $post_id ] = $replacement_lease;
			ksort( $current_leases, SORT_NUMERIC );
		}

		$replacement_raw = (string) maybe_serialize( $current_leases );

		if (
			$lease_snapshot['exists']
			&& 'no' === $lease_snapshot['autoload']
			&& hash_equals( $replacement_raw, $lease_snapshot['raw_value'] )
		) {
			ai4seo_invalidate_option_cache( $lease_option_name );
			return ai4seo_renew_post_id_option_transition_semaphore();
		}

		if ( ! $lease_snapshot['exists'] && ! $current_leases ) {
			ai4seo_invalidate_option_cache( $lease_option_name );
			return ai4seo_renew_post_id_option_transition_semaphore();
		}

		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			$lease_option_name,
			$lease_snapshot,
			$current_leases,
			false
		);

		if ( true === $compare_and_swap_result ) {
			return ai4seo_renew_post_id_option_transition_semaphore();
		}

		if ( null === $compare_and_swap_result ) {
			return false;
		}
	}

	return false;
}


/**
 * Reconcile exact orphan rollback leases without touching Processing-backed or replacement owners.
 *
 * The caller must already own the shared post-ID fence. When queue intent is discarded, Pending and
 * Force are removed before the lease is consumed. Otherwise the durable pre-operation snapshots are
 * replayed. A failed transition retains the exact lease so the normal recovery pass can retry it.
 *
 * @param string        $processing_option_name Processing option name.
 * @param string        $pending_option_name Paired Pending option name.
 * @param string        $force_overwrite_option_name Paired Force option name.
 * @param array         $post_ids Candidate orphan lease post IDs.
 * @param bool          $discard_queue_intent Whether explicit queue clearing overrides stored rollback intent.
 * @param array         $expected_tokens Optional exact token predicates keyed by post ID.
 * @param callable|null $ownership_checkpoint Optional additional ownership-renewal callback.
 * @param array|null    $reconciled_post_ids Receives IDs whose exact rollback token was consumed.
 * @return bool True only when every storage read, owned transition, and exact-token removal was verified.
 */
function ai4seo_reconcile_orphan_processing_rollback_leases_under_lock(
	string $processing_option_name,
	string $pending_option_name,
	string $force_overwrite_option_name,
	array $post_ids,
	bool $discard_queue_intent = false,
	array $expected_tokens = array(),
	?callable $ownership_checkpoint = null,
	?array &$reconciled_post_ids = null
): bool {
	$allowed_queue_options       = array(
		AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME => array(
			'pending'         => AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
			'force_overwrite' => AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
		),
		AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME => array(
			'pending'         => AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			'force_overwrite' => AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
		),
	);
	$processing_option_name      = sanitize_key( $processing_option_name );
	$pending_option_name         = sanitize_key( $pending_option_name );
	$force_overwrite_option_name = sanitize_key( $force_overwrite_option_name );
	$post_ids                    = ai4seo_normalize_option_post_id_collection( $post_ids );
	$reconciled_post_ids         = array();

	if (
		! isset( $allowed_queue_options[ $processing_option_name ] )
		|| $pending_option_name !== $allowed_queue_options[ $processing_option_name ]['pending']
		|| $force_overwrite_option_name !== $allowed_queue_options[ $processing_option_name ]['force_overwrite']
	) {
		return false;
	}

	$normalized_expected_tokens = array();

	foreach ( $expected_tokens as $expected_post_id => $expected_token ) {
		$expected_post_id = ai4seo_normalize_option_post_id( $expected_post_id );

		if (
			false === $expected_post_id
			|| ! is_string( $expected_token )
			|| '' === $expected_token
			|| 128 < strlen( $expected_token )
		) {
			return false;
		}

		$normalized_expected_tokens[ $expected_post_id ] = $expected_token;
	}

	$checkpoint = static function () use ( $ownership_checkpoint ): bool {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		if ( null === $ownership_checkpoint ) {
			return true;
		}

		try {
			return true === call_user_func( $ownership_checkpoint );
		} catch ( Throwable $throwable ) {
			return false;
		}
	};

	if ( ! $post_ids ) {
		return $checkpoint();
	}

	if ( ! $checkpoint() ) {
		return false;
	}

	$processing_snapshot = ai4seo_get_raw_option_snapshot( $processing_option_name );

	if ( null === $processing_snapshot ) {
		return false;
	}

	$processing_option_value = $processing_snapshot['value'];

	if ( is_string( $processing_option_value ) && '' !== $processing_option_value && ai4seo_is_json( $processing_option_value ) ) {
		$processing_option_value = json_decode( $processing_option_value, true );
	}

	if ( $processing_snapshot['exists'] && ! is_array( $processing_option_value ) ) {
		return false;
	}

	$processing_option_value = is_array( $processing_option_value ) ? $processing_option_value : array();

	foreach ( $processing_option_value as $raw_processing_post_id ) {
		if ( false === ai4seo_normalize_option_post_id( $raw_processing_post_id ) ) {
			return false;
		}
	}

	$processing_lookup = array_fill_keys(
		ai4seo_normalize_option_post_id_collection( $processing_option_value ),
		true
	);
	$leases            = array();

	if ( ! ai4seo_read_processing_claim_leases_under_lock( $processing_option_name, $leases ) ) {
		return false;
	}

	foreach ( $post_ids as $post_id ) {
		$current_lease = $leases[ $post_id ] ?? null;

		if ( ! is_array( $current_lease ) || isset( $processing_lookup[ $post_id ] ) ) {
			continue;
		}

		if (
			! empty( $normalized_expected_tokens )
			&& (
				! isset( $normalized_expected_tokens[ $post_id ] )
				|| ! hash_equals( $normalized_expected_tokens[ $post_id ], $current_lease['token'] )
			)
		) {
			// A different exact token is a later owner and must remain untouched.
			continue;
		}

		if (
			empty( $current_lease['rollback_requested'] )
			|| ! array_key_exists( 'pending_was_present', $current_lease )
			|| ! array_key_exists( 'force_was_present', $current_lease )
		) {
			// Active/plain and terminal leases are outside this rollback-only repair.
			continue;
		}

		$restore_pending                = $discard_queue_intent ? false : $current_lease['pending_was_present'];
		$restore_force                  = $discard_queue_intent ? false : $current_lease['force_was_present'];
		$preserve_new_queue_memberships = ! $discard_queue_intent
			&& ! empty( $current_lease['preserve_new_queue_memberships'] );
		$additions                      = $restore_pending
			? array( $pending_option_name => array( $post_id ) )
			: array();
		$removals                       = array( $processing_option_name => array( $post_id ) );

		if ( $restore_pending ) {
			unset( $removals[ $pending_option_name ] );
		} elseif ( ! $preserve_new_queue_memberships ) {
			$removals[ $pending_option_name ] = array( $post_id );
		}

		if ( $restore_force ) {
			$additions[ $force_overwrite_option_name ] = array( $post_id );
		} elseif ( ! $preserve_new_queue_memberships ) {
			$removals[ $force_overwrite_option_name ] = array( $post_id );
		}

		if ( ! $checkpoint() ) {
			return false;
		}

		$transition_did_change = false;

		if ( ! ai4seo_apply_normalized_post_id_option_transition_under_lock( $additions, $removals, $transition_did_change ) ) {
			return false;
		}

		if ( ! $checkpoint() ) {
			return false;
		}

		$lease_predicate_matched = false;

		if (
			! ai4seo_mutate_processing_claim_lease_under_lock(
				$processing_option_name,
				$post_id,
				null,
				$current_lease['token'],
				$lease_predicate_matched
			)
			|| ! $lease_predicate_matched
		) {
			return false;
		}

		$reconciled_post_ids[] = $post_id;
		unset( $leases[ $post_id ] );
	}

	return $checkpoint();
}


/**
 * Conditionally claim one pending post ID for processing under the shared transition fence.
 *
 * A durable claimed/token output remains authoritative even when the Boolean result is false after
 * the final fence checkpoint; callers must exact-release that token before abandoning the request.
 *
 * @param string $pending_option_name Pending option name.
 * @param string $processing_option_name Processing option name.
 * @param mixed  $post_id Post ID to claim.
 * @param bool   $claimed Receives true only when this call owns the completed transition.
 * @param string $force_overwrite_option_name Paired Force option, or an empty string when unused.
 * @param bool   $is_force_overwrite Receives Force membership captured for the claimed Pending generation.
 * @param string $processing_claim_token Receives the durable production ownership token.
 * @param bool   $pending_was_present Receives authoritative rollback Pending intent resolved under the fence.
 * @param bool   $force_was_present Receives Force membership from the authoritative fenced snapshot.
 * @return bool True when storage was checked successfully, including an already-unclaimable state.
 */
function ai4seo_claim_pending_post_id_for_processing(
	string $pending_option_name,
	string $processing_option_name,
	$post_id,
	&$claimed = null,
	string $force_overwrite_option_name = '',
	&$is_force_overwrite = null,
	&$processing_claim_token = null,
	&$pending_was_present = null,
	&$force_was_present = null
): bool {
	return ai4seo_claim_post_id_for_processing_state(
		$pending_option_name,
		$processing_option_name,
		$force_overwrite_option_name,
		$post_id,
		true,
		$claimed,
		$is_force_overwrite,
		$processing_claim_token,
		$pending_was_present,
		$force_was_present
	);
}


/**
 * Exclusively claim one directly requested post ID when no Processing owner exists.
 *
 * Direct/manual generation does not require prior Pending membership. When Pending does exist, it is
 * removed by the same fenced transition and its paired Force mode is captured before that removal.
 * A durable claimed/token output remains authoritative even when the Boolean result is false after
 * the final fence checkpoint; callers must exact-release that token before abandoning the request.
 *
 * @param string $pending_option_name Pending option paired with the processing context.
 * @param string $processing_option_name Processing option name.
 * @param mixed  $post_id Post ID to claim.
 * @param bool   $claimed Receives true only when this call owns the completed transition.
 * @param string $force_overwrite_option_name Paired Force option, or an empty string when unused.
 * @param bool   $is_force_overwrite Receives Force membership captured for an observed Pending generation.
 * @param string $processing_claim_token Receives the durable production ownership token.
 * @param bool   $pending_was_present Receives authoritative rollback Pending intent resolved under the fence.
 * @param bool   $force_was_present Receives Force membership from the authoritative fenced snapshot.
 * @return bool True when storage was checked successfully, including an already-owned state.
 */
function ai4seo_claim_post_id_for_direct_processing(
	string $pending_option_name,
	string $processing_option_name,
	$post_id,
	&$claimed = null,
	string $force_overwrite_option_name = '',
	&$is_force_overwrite = null,
	&$processing_claim_token = null,
	&$pending_was_present = null,
	&$force_was_present = null
): bool {
	return ai4seo_claim_post_id_for_processing_state(
		$pending_option_name,
		$processing_option_name,
		$force_overwrite_option_name,
		$post_id,
		false,
		$claimed,
		$is_force_overwrite,
		$processing_claim_token,
		$pending_was_present,
		$force_was_present
	);
}


/**
 * Claim one post ID for processing using one authoritative Pending/Processing/Force snapshot.
 *
 * A durable claimed/token output remains authoritative even when the Boolean result is false after
 * the final fence checkpoint; callers must exact-release that token before abandoning the request.
 *
 * @param string $pending_option_name Pending option name.
 * @param string $processing_option_name Processing option name.
 * @param string $force_overwrite_option_name Paired Force option, or an empty string when unused.
 * @param mixed  $post_id Post ID to claim.
 * @param bool   $require_pending Whether Pending membership is required.
 * @param bool   $claimed Receives true only when this call owns the completed transition.
 * @param bool   $is_force_overwrite Receives the captured Force mode for the claimed generation.
 * @param string $processing_claim_token Receives the durable production ownership token.
 * @param bool   $pending_was_present Receives authoritative rollback Pending intent resolved under the fence.
 * @param bool   $force_was_present Receives Force membership from the authoritative fenced snapshot.
 * @return bool True when storage was checked successfully, including an unclaimable state.
 */
function ai4seo_claim_post_id_for_processing_state(
	string $pending_option_name,
	string $processing_option_name,
	string $force_overwrite_option_name,
	$post_id,
	bool $require_pending,
	&$claimed,
	&$is_force_overwrite,
	&$processing_claim_token = null,
	&$pending_was_present = null,
	&$force_was_present = null
): bool {
	$pending_option_name         = sanitize_key( $pending_option_name );
	$processing_option_name      = sanitize_key( $processing_option_name );
	$force_overwrite_option_name = sanitize_key( $force_overwrite_option_name );
	$post_id                     = ai4seo_normalize_option_post_id( $post_id );
	$claimed                     = false;
	$is_force_overwrite          = false;
	$processing_claim_token      = '';
	$pending_was_present         = false;
	$force_was_present           = false;

	if (
		'' === $pending_option_name
		|| '' === $processing_option_name
		|| $pending_option_name === $processing_option_name
		|| ( '' !== $force_overwrite_option_name && in_array( $force_overwrite_option_name, array( $pending_option_name, $processing_option_name ), true ) )
		|| false === $post_id
	) {
		return false;
	}

	$critical_section_name        = ai4seo_get_post_id_option_transition_semaphore_name();
	$claim_succeeded              = false;
	$claim_did_change             = false;
	$changed_options              = array();
	$operation_succeeded          = false;
	$release_succeeded            = false;
	$transition_succeeded         = false;
	$observed_force_mode          = false;
	$uses_durable_lease           = '' !== ai4seo_get_processing_claim_lease_option_name( $processing_option_name );
	$new_claim_token              = '';
	$lease_was_persisted          = false;
	$processing_was_present       = false;
	$processing_recovery_required = false;

	if ( ! function_exists( 'ai4seo_acquire_semaphore' ) || ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		return false;
	}

	try {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		// Both ownership predicates must come from authoritative snapshots read inside the same fence.
		$pending_snapshot    = ai4seo_get_raw_option_snapshot( $pending_option_name );
		$processing_snapshot = ai4seo_get_raw_option_snapshot( $processing_option_name );
		$force_snapshot      = '' === $force_overwrite_option_name
			? false
			: ai4seo_get_raw_option_snapshot( $force_overwrite_option_name );

		if ( null === $pending_snapshot || null === $processing_snapshot || null === $force_snapshot ) {
			return false;
		}

		$pending_lookup         = array_fill_keys(
			ai4seo_normalize_option_post_id_collection( $pending_snapshot['value'] ),
			true
		);
		$processing_lookup      = array_fill_keys(
			ai4seo_normalize_option_post_id_collection( $processing_snapshot['value'] ),
			true
		);
		$has_pending_membership = isset( $pending_lookup[ $post_id ] );
		$pending_was_present    = $has_pending_membership;
		$processing_was_present = isset( $processing_lookup[ $post_id ] );
		$force_lookup           = false === $force_snapshot
			? array()
			: array_fill_keys(
				ai4seo_normalize_option_post_id_collection( $force_snapshot['value'] ),
				true
			);
		$force_was_present      = isset( $force_lookup[ $post_id ] );

		$current_lease                     = null;
		$current_lease_token               = '';
		$current_lease_is_active           = false;
		$current_lease_owns_processing     = false;
		$current_lease_has_recovery_intent = false;
		$current_lease_blocks_replacement  = false;

		if ( $uses_durable_lease ) {
			$current_leases = array();

			if ( ! ai4seo_read_processing_claim_leases_under_lock( $processing_option_name, $current_leases ) ) {
				return false;
			}

			$current_lease                     = $current_leases[ $post_id ] ?? null;
			$current_lease_token               = is_array( $current_lease ) ? $current_lease['token'] : '';
			$current_lease_is_active           = is_array( $current_lease )
				&& ai4seo_is_processing_claim_lease_active( $current_lease );
			$current_lease_owns_processing     = $processing_was_present && $current_lease_is_active;
			$current_lease_has_recovery_intent = is_array( $current_lease )
				&& (
					! empty( $current_lease['rollback_requested'] )
					|| ! empty( $current_lease['ambiguous_spend'] )
					|| array_key_exists( 'terminal_force_overwrite', $current_lease )
				);
			$current_lease_blocks_replacement  = is_array( $current_lease )
				&& ! (
					$processing_was_present
					&& ! $current_lease_is_active
					&& ! $current_lease_has_recovery_intent
				);
		}

		// Active owners and interrupted recovery transactions must complete before another claim.
		if ( $current_lease_blocks_replacement || ( ! $uses_durable_lease && $processing_was_present ) || ( $require_pending && ! $has_pending_membership ) ) {
			$operation_succeeded          = true;
			$processing_recovery_required = $current_lease_blocks_replacement
				&& ( ! $current_lease_owns_processing || $current_lease_has_recovery_intent );
		} else {
			$observed_force_mode          = $has_pending_membership && $force_was_present;
			$rollback_pending_was_present = $has_pending_membership;

			// A direct claimant that supersedes dead Processing must not discard its queued retry intent.
			if ( $uses_durable_lease && $processing_was_present ) {
				$rollback_pending_was_present = is_array( $current_lease )
					&& array_key_exists( 'pending_was_present', $current_lease )
					? $current_lease['pending_was_present']
					: true;
			}

			$pending_was_present = $rollback_pending_was_present;

			if ( $uses_durable_lease ) {
				$new_claim_token         = ai4seo_create_processing_claim_token();
				$new_lease               = array(
					'token'               => $new_claim_token,
					'expires_at'          => time() + ai4seo_get_processing_claim_lease_ttl_seconds(),
					'force_overwrite'     => $observed_force_mode,
					'pending_was_present' => $rollback_pending_was_present,
					'force_was_present'   => $force_was_present,
				);
				$lease_predicate_matched = false;

				if (
					! ai4seo_mutate_processing_claim_lease_under_lock(
						$processing_option_name,
						$post_id,
						$new_lease,
						$current_lease_token,
						$lease_predicate_matched
					)
				) {
					return false;
				}

				if ( ! $lease_predicate_matched ) {
					$operation_succeeded = true;
				} else {
					$lease_was_persisted = true;
				}
			}

			if ( ! $uses_durable_lease || $lease_was_persisted ) {
				$transition_succeeded = ai4seo_apply_normalized_post_id_option_transition_under_lock(
					array( $processing_option_name => array( $post_id ) ),
					array( $pending_option_name => array( $post_id ) ),
					$claim_did_change,
					$changed_options
				);
				$claim_succeeded      = $transition_succeeded
					&& ( $processing_was_present || isset( $changed_options[ $processing_option_name ] ) );
				$operation_succeeded  = $transition_succeeded;

				if ( ! $transition_succeeded ) {
					$compensation_additions = $has_pending_membership
						? array( $pending_option_name => array( $post_id ) )
						: array();
					$compensation_removals  = $processing_was_present
						? array()
						: array( $processing_option_name => array( $post_id ) );
					$compensation_changed   = false;

					if ( $compensation_additions || $compensation_removals ) {
						ai4seo_apply_normalized_post_id_option_transition_under_lock(
							$compensation_additions,
							$compensation_removals,
							$compensation_changed
						);
					}

					if ( $uses_durable_lease && $lease_was_persisted ) {
						$lease_compensation_matched = false;
						$prior_lease_replacement    = is_array( $current_lease ) ? $current_lease : null;

						if (
							! ai4seo_mutate_processing_claim_lease_under_lock(
								$processing_option_name,
								$post_id,
								$prior_lease_replacement,
								$new_claim_token,
								$lease_compensation_matched
							)
							|| ! $lease_compensation_matched
						) {
							// A durable zero-expiry marker makes any uncompensated Processing membership reclaimable.
							$expired_lease_matched = false;
							ai4seo_mutate_processing_claim_lease_under_lock(
								$processing_option_name,
								$post_id,
								array(
									'token'               => $new_claim_token,
									'expires_at'          => 0,
									'force_overwrite'     => $observed_force_mode,
									'pending_was_present' => $rollback_pending_was_present,
									'force_was_present'   => $force_was_present,
								),
								$new_claim_token,
								$expired_lease_matched
							);
						}
					}
				}
			}

			if ( $operation_succeeded && ! ai4seo_renew_post_id_option_transition_semaphore() ) {
				$operation_succeeded = false;
			}
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	// Durable token ownership remains recoverable even when the final semaphore delete reports failure.
	$claimed                = $claim_succeeded && ( $release_succeeded || $uses_durable_lease );
	$is_force_overwrite     = $claimed && $observed_force_mode;
	$processing_claim_token = $claimed && $uses_durable_lease ? $new_claim_token : '';

	if ( $processing_recovery_required && function_exists( 'ai4seo_schedule_bulk_generation_processing_recovery' ) ) {
		ai4seo_schedule_bulk_generation_processing_recovery();
	}

	return $operation_succeeded && ( $release_succeeded || $claimed );
}


/**
 * Return option names whose membership contradicts adding to one option.
 *
 * @param string $add_to_this_option Destination option.
 * @return array<int,string> Contradictory option names.
 */
function ai4seo_get_contradictory_post_id_option_names( string $add_to_this_option ): array {
	switch ( $add_to_this_option ) {
		case AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME:
			return array(
				AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
			);
		case AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			return array(
				AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			);
		case AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME:
			return array( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME );
		case AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			return array( AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		case AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME:
			return array( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );
		case AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			return array( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		case AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME:
			return array(
				AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
			);
		case AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			return array(
				AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			);
		default:
			return array();
	}
}


/**
 * Adds post IDs to a generation-state option.
 *
 * @param mixed $option The option value.
 * @param mixed $post_ids The post IDs value.
 * @return bool
 */
function ai4seo_add_post_ids_to_option( $option, $post_ids ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 942539402, 'Prevented loop', true );
		return false;
	}

	$option   = sanitize_key( $option );
	$post_ids = ai4seo_normalize_option_post_id_collection( is_array( $post_ids ) ? $post_ids : array( $post_ids ) );

	if ( '' === $option || ! $post_ids ) {
		return false;
	}

	$removals = array();
	foreach ( ai4seo_get_contradictory_post_id_option_names( $option ) as $contradictory_option_name ) {
		$removals[ $contradictory_option_name ] = $post_ids;
	}

	return ai4seo_apply_post_id_option_transition(
		array( $option => $post_ids ),
		$removals
	);
}


/**
 * Remove post IDs from options that contradict one destination option.
 *
 * @param string $add_to_this_option The destination option.
 * @param array  $post_ids Post IDs to remove.
 * @return void
 */
function ai4seo_remove_contradictory_post_ids( string $add_to_this_option, array $post_ids ) {
	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 173818084, 'Prevented loop', true );
		return;
	}

	$removals = array();
	foreach ( ai4seo_get_contradictory_post_id_option_names( $add_to_this_option ) as $contradictory_option_name ) {
		$removals[ $contradictory_option_name ] = $post_ids;
	}

	if ( $removals ) {
		ai4seo_apply_post_id_option_transition( array(), $removals );
	}
}


/**
 * Removes post IDs from a generation-state option.
 *
 * @param string    $remove_from_this_option Option name to update.
 * @param int|array $post_ids Post IDs to remove.
 * @return bool
 */
function ai4seo_remove_post_ids_from_option( string $remove_from_this_option, $post_ids ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 534042781, 'Prevented loop', true );
		return false;
	}

	$remove_from_this_option = sanitize_key( $remove_from_this_option );
	$post_ids                = ai4seo_normalize_option_post_id_collection( is_array( $post_ids ) ? $post_ids : array( $post_ids ) );

	if ( '' === $remove_from_this_option || ! $post_ids ) {
		return false;
	}

	$did_change = false;
	$succeeded  = ai4seo_apply_post_id_option_transition(
		array(),
		array( $remove_from_this_option => $post_ids ),
		$did_change
	);

	return $succeeded && $did_change;
}


/**
 * Removes post IDs from a complete option set under one checked transition fence.
 *
 * @param array     $remove_from_these_options Option names to update.
 * @param int|array $post_ids Post IDs to remove.
 * @param bool      $did_change Receives whether any option membership changed.
 * @return bool True only when every requested absence was verified and the lock released.
 */
function ai4seo_remove_post_ids_from_options( array $remove_from_these_options, $post_ids, &$did_change = null ): bool {
	$post_ids   = ai4seo_normalize_option_post_id_collection( is_array( $post_ids ) ? $post_ids : array( $post_ids ) );
	$removals   = array();
	$did_change = false;

	if ( ! $post_ids ) {
		return false;
	}

	foreach ( $remove_from_these_options as $option_name ) {
		if ( ! is_string( $option_name ) ) {
			continue;
		}

		$option_name = sanitize_key( $option_name );

		if ( '' !== $option_name ) {
			$removals[ $option_name ] = $post_ids;
		}
	}

	if ( ! $removals ) {
		return false;
	}

	return ai4seo_apply_post_id_option_transition( array(), $removals, $did_change );
}


/**
 * Removes post IDs from every plugin post-ID option.
 *
 * @param int|array $post_ids Post IDs to remove.
 * @return bool True only when every requested absence was verified.
 */
function ai4seo_remove_post_ids_from_all_options( $post_ids ) {
	return ai4seo_remove_post_ids_from_options( AI4SEO_ALL_POST_ID_OPTIONS, $post_ids );
}


/**
 * Removes post IDs from every SEO coverage option.
 *
 * @param int|array $post_ids Post IDs to remove.
 * @return bool True only when every requested absence was verified.
 */
function ai4seo_remove_post_ids_from_all_seo_coverage_options( $post_ids ) {
	return ai4seo_remove_post_ids_from_options( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS, $post_ids );
}


/**
 * Removes post IDs from every generation-status option.
 *
 * @param int|array $post_ids Post IDs to remove.
 * @return bool True only when every requested absence was verified.
 */
function ai4seo_remove_post_ids_from_all_generation_status_options( $post_ids ) {
	// Force-overwrite markers are queue modes and leave generation status under the same checked fence.
	return ai4seo_remove_post_ids_from_options(
		array_merge(
			AI4SEO_GENERATION_STATUS_POST_ID_OPTIONS,
			AI4SEO_FORCE_OVERWRITE_BULK_GENERATION_POST_ID_OPTIONS
		),
		$post_ids
	);
}


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
		if ( in_array( $this_overwrite_existing_metadata, $active_meta_tags, true ) ) {
			$active_overwrite_existing_metadata[] = $this_overwrite_existing_metadata;
		}
	}

	return $active_overwrite_existing_metadata;
}


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
		if ( in_array( $this_overwrite_existing_attachment_attribute, $active_attachment_attributes, true ) ) {
			$active_overwrite_existing_attachment_attributes[] = $this_overwrite_existing_attachment_attribute;
		}
	}

	return $active_overwrite_existing_attachment_attributes;
}


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
