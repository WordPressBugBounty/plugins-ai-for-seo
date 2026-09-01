<?php
/**
 * Provides plugin locks and singleton semaphores.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region SEMAPHORES ============================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Determine whether the active database connection can own multiple named locks concurrently.
 *
 * MariaDB supports independent named locks. MySQL added that behavior in 5.7.5; older MySQL
 * releases implicitly release the previously held name when GET_LOCK() acquires another one.
 *
 * @param string|null $server_version Optional server version for deterministic capability tests.
 * @return bool Whether nested advisory locks preserve every previously acquired lock.
 */
function ai4seo_database_supports_multiple_advisory_locks( ?string $server_version = null ): bool {
	global $wpdb;

	if ( null === $server_version ) {
		$wpdb->last_error = '';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Server capabilities are immutable during the request and cannot be derived from a WordPress cache.
		$server_version = (string) $wpdb->get_var( 'SELECT VERSION()' );

		if ( $wpdb->last_error ) {
			return false;
		}
	}

	$server_version = trim( $server_version );

	if ( '' === $server_version ) {
		return false;
	}

	if ( false !== stripos( $server_version, 'mariadb' ) ) {
		return true;
	}

	$version_number = preg_replace( '/[^0-9.].*$/', '', $server_version );

	return is_string( $version_number )
		&& '' !== $version_number
		&& version_compare( $version_number, '5.7.5', '>=' );
}


/**
 * Validate a connection-owned advisory-lock name against the server byte limit.
 *
 * @param string $lock_name Database advisory-lock name.
 * @return bool Whether the name can be passed safely to GET_LOCK() or RELEASE_LOCK().
 */
function ai4seo_is_valid_database_advisory_lock_name( string $lock_name ): bool {
	return '' !== $lock_name && strlen( $lock_name ) <= 64;
}


/**
 * Acquire a connection-owned database advisory lock without waiting.
 *
 * MySQL and MariaDB limit named locks to 64 bytes. The lock is released
 * automatically if the owning database connection terminates.
 *
 * @param string $lock_name Database advisory-lock name.
 * @return bool True only when the active database connection acquired the lock.
 */
function ai4seo_acquire_database_advisory_lock( string $lock_name ): bool {
	global $wpdb;

	if ( ! ai4seo_is_valid_database_advisory_lock_name( $lock_name ) ) {
		return false;
	}

	$lock_query = ai4seo_prepare_database_query(
		'SELECT GET_LOCK({{lock_name}}, {{wait_seconds}})',
		array(
			'lock_name'    => ai4seo_database_scalar_binding( '%s', $lock_name ),
			'wait_seconds' => ai4seo_database_scalar_binding( '%d', 0 ),
		)
	);

	if ( false === $lock_query ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares the bounded lock name and zero wait; GET_LOCK reads connection state that cannot be cached.
	$lock_result = $wpdb->get_var( $lock_query );

	return ! $wpdb->last_error && '1' === (string) $lock_result;
}


/**
 * Release a database advisory lock owned by the active connection.
 *
 * @param string $lock_name Database advisory-lock name.
 * @return bool True only when the active database connection released the lock.
 */
function ai4seo_release_database_advisory_lock( string $lock_name ): bool {
	global $wpdb;

	if ( ! ai4seo_is_valid_database_advisory_lock_name( $lock_name ) ) {
		return false;
	}

	$release_query = ai4seo_prepare_database_query(
		'SELECT RELEASE_LOCK({{lock_name}})',
		array(
			'lock_name' => ai4seo_database_scalar_binding( '%s', $lock_name ),
		)
	);

	if ( false === $release_query ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares the bounded lock name; RELEASE_LOCK mutates connection state and cannot be cached.
	$lock_result = $wpdb->get_var( $release_query );

	return ! $wpdb->last_error && '1' === (string) $lock_result;
}


/**
 * Check whether the active database connection still owns an advisory lock.
 *
 * @param string $lock_name Database advisory-lock name.
 * @return bool True only when IS_USED_LOCK() identifies the active connection.
 */
function ai4seo_is_database_advisory_lock_owned_by_current_connection( string $lock_name ): bool {
	global $wpdb;

	if ( ! ai4seo_is_valid_database_advisory_lock_name( $lock_name ) ) {
		return false;
	}

	$ownership_query = ai4seo_prepare_database_query(
		'SELECT IS_USED_LOCK({{lock_name}}) = CONNECTION_ID()',
		array(
			'lock_name' => ai4seo_database_scalar_binding( '%s', $lock_name ),
		)
	);

	if ( false === $ownership_query ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares the bounded name; ownership is connection state and cannot be cached.
	$ownership_result = $wpdb->get_var( $ownership_query );

	return ! $wpdb->last_error && '1' === (string) $ownership_result;
}


/**
 * Return the database/site-scoped advisory-lock name for posts-table analysis ownership.
 *
 * @return string Advisory-lock name, or an empty string when site storage is unavailable.
 */
function ai4seo_get_posts_table_analysis_database_lock_name(): string {
	global $wpdb;

	if ( ! is_object( $wpdb ) || ! isset( $wpdb->options ) || '' === (string) $wpdb->options ) {
		return '';
	}

	$database_name = defined( 'DB_NAME' ) ? (string) DB_NAME : '';
	$site_scope    = $database_name . '|' . (string) $wpdb->options . '|' . (string) get_current_blog_id();

	// The fixed prefix plus full hash is 53 bytes, safely below the server's 64-byte limit.
	return 'ai4seo-post-analysis-' . md5( $site_scope );
}


/**
 * Describe the active site-local options storage used by option-backed semaphores.
 *
 * @return array{options_table: string, blog_id: int}|null Storage scope, or null when unavailable.
 */
function ai4seo_get_semaphore_storage_scope(): ?array {
	global $wpdb;

	if ( ! is_object( $wpdb ) || ! isset( $wpdb->options ) || '' === (string) $wpdb->options ) {
		return null;
	}

	return array(
		'options_table' => (string) $wpdb->options,
		'blog_id'       => function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0,
	);
}


/**
 * Build a collision-resistant request-local key for one site-local semaphore.
 *
 * @param string $options_table Exact options table identity.
 * @param string $option_key Exact semaphore option name.
 * @return string Request-local ownership key.
 */
function ai4seo_get_held_semaphore_storage_key( string $options_table, string $option_key ): string {
	return hash( 'sha256', $options_table . "\0" . $option_key );
}


/**
 * Return the locally held record for the active site's semaphore option.
 *
 * @param string $option_key Exact semaphore option name.
 * @return array{option_key: string, token: string, options_table: string, blog_id: int}|null Held record.
 */
function ai4seo_get_held_semaphore_record( string $option_key ): ?array {
	$scope = ai4seo_get_semaphore_storage_scope();

	if ( null === $scope || ! isset( $GLOBALS['ai4seo_held_semaphores'] ) || ! is_array( $GLOBALS['ai4seo_held_semaphores'] ) ) {
		return null;
	}

	$storage_key = ai4seo_get_held_semaphore_storage_key( $scope['options_table'], $option_key );
	$record      = $GLOBALS['ai4seo_held_semaphores'][ $storage_key ] ?? null;

	if (
		! is_array( $record )
		|| ! isset( $record['option_key'], $record['token'], $record['options_table'], $record['blog_id'] )
		|| $option_key !== $record['option_key']
		|| $scope['options_table'] !== $record['options_table']
		|| ! is_string( $record['token'] )
		|| '' === $record['token']
		|| ! is_int( $record['blog_id'] )
	) {
		return null;
	}

	return $record;
}


/**
 * Acquire a semaphore for a critical section name.
 * Polls every 0.1s up to 5s. Auto-releases on shutdown.
 *
 * @param string $critical_section_name Critical section identifier.
 * @return bool True on success, false on timeout.
 */
function ai4seo_acquire_semaphore( string $critical_section_name ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 503298098, 'Prevented loop', true );
		return false;
	}

	ai4seo_register_semaphore_shutdown_handler();

	$option_key = ai4seo_get_semaphore_option_key( $critical_section_name );
	$token      = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : ( uniqid( 'ai4seo_', true ) );
	$scope      = ai4seo_get_semaphore_storage_scope();

	if ( null === $scope ) {
		return false;
	}

	$deadline = microtime( true ) + (float) AI4SEO_SEMAPHORE_MAX_WAIT_SECONDS;
	$interval = (int) max( 1, floor( (float) AI4SEO_SEMAPHORE_POLL_INTERVAL_SECONDS * 1_000_000 ) ); // microseconds.

	while ( microtime( true ) < $deadline ) {
		// Fast path: create lock if not set.
		if ( ai4seo_try_create_lock( $option_key, $token ) === true ) {
			// Retain the exact site storage identity so blog switches cannot overwrite ownership.
			$storage_key                                       = ai4seo_get_held_semaphore_storage_key( $scope['options_table'], $option_key );
			$GLOBALS['ai4seo_held_semaphores'][ $storage_key ] = array(
				'option_key'    => $option_key,
				'token'         => $token,
				'options_table' => $scope['options_table'],
				'blog_id'       => $scope['blog_id'],
			);
			return true;
		}

		// If set, check staleness and reclaim if stale.
		$existing_lock = ai4seo_get_semaphore_option_snapshot( $option_key );

		if ( is_array( $existing_lock )
			&& $existing_lock['exists']
			&& ai4seo_is_lock_stale( $existing_lock['value'] )
			&& ai4seo_delete_semaphore_option_if_matches( $option_key, $existing_lock['raw_value'] )
		) {
			// The exact stale payload was reclaimed; retry immediately without an unnecessary poll delay.
			continue;
		}

		// Wait before next attempt.
		usleep( $interval );
	}

	return false;
}


/**
 * Release a previously acquired semaphore.
 *
 * @param string $critical_section_name Critical section identifier.
 * @return bool True if released (or not present), false if lock belongs to someone else or was not held.
 */
function ai4seo_release_semaphore( string $critical_section_name ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 641172961, 'Prevented loop', true );
		return false;
	}

	$option_key = ai4seo_get_semaphore_option_key( $critical_section_name );
	$scope      = ai4seo_get_semaphore_storage_scope();
	$record     = ai4seo_get_held_semaphore_record( $option_key );
	$token      = null !== $record ? $record['token'] : '';

	if ( '' === $token ) {
		// We do not believe we hold this lock; do a safe attempt anyway.
		$existing_lock = ai4seo_get_semaphore_option_snapshot( $option_key );

		if ( is_array( $existing_lock ) && ! $existing_lock['exists'] ) {
			return true;
		}

		return false; // Someone else holds it.
	}

	$released = ai4seo_release_semaphore_by_key_and_token( $option_key, $token );

	// Clean only the active site's ownership record regardless of the storage result.
	if ( null !== $scope ) {
		$storage_key = ai4seo_get_held_semaphore_storage_key( $scope['options_table'], $option_key );
		unset( $GLOBALS['ai4seo_held_semaphores'][ $storage_key ] );
	}

	return $released;
}


/**
 * Renew a locally held semaphore without overwriting a replacement holder.
 *
 * @param string $critical_section_name Critical section identifier.
 * @return bool True only while the exact locally held token still owns the lock.
 */
function ai4seo_renew_semaphore( string $critical_section_name ): bool {
	global $wpdb;

	$option_key = ai4seo_get_semaphore_option_key( $critical_section_name );
	$record     = ai4seo_get_held_semaphore_record( $option_key );
	$token      = null !== $record ? $record['token'] : '';

	if ( '' === $token || ! isset( $wpdb->options ) ) {
		return false;
	}

	$existing_lock = ai4seo_get_semaphore_option_snapshot( $option_key );

	if (
		! is_array( $existing_lock )
		|| ! $existing_lock['exists']
		|| ! is_array( $existing_lock['value'] )
		|| ! isset( $existing_lock['value']['token'], $existing_lock['value']['started_at'] )
		|| ! is_string( $existing_lock['value']['token'] )
		|| $token !== $existing_lock['value']['token']
		|| ! is_int( $existing_lock['value']['started_at'] )
	) {
		return false;
	}

	$renewed_payload   = array(
		'token'      => $token,
		'started_at' => time(),
	);
	$renewed_raw_value = maybe_serialize( $renewed_payload );

	// A same-second renewal already observed the exact owned payload authoritatively. Avoid an
	// identical UPDATE and its otherwise-required zero-row readback while retaining cache hygiene.
	if ( hash_equals( $renewed_raw_value, $existing_lock['raw_value'] ) ) {
		ai4seo_invalidate_semaphore_option_cache( $option_key );
		return true;
	}

	// The raw previous value is the CAS predicate, so a replacement holder can never be overwritten.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Exact-value renewal is the semaphore lease CAS; option caches are invalidated after a confirmed update or same-value match.
	$updated_rows = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = %s",
			$renewed_raw_value,
			$option_key,
			$existing_lock['raw_value']
		)
	);

	if ( false === $updated_rows || $wpdb->last_error ) {
		return false;
	}

	if ( 1 === (int) $updated_rows ) {
		ai4seo_invalidate_semaphore_option_cache( $option_key );
		return true;
	}

	if ( 0 !== (int) $updated_rows ) {
		return false;
	}

	// A concurrent same-token renewal may publish the desired bytes before this CAS affects a row.
	// Accept that race only after an authoritative exact-byte readback.
	$current_lock = ai4seo_get_semaphore_option_snapshot( $option_key );

	if (
		! is_array( $current_lock )
		|| ! $current_lock['exists']
		|| ! hash_equals( $renewed_raw_value, $current_lock['raw_value'] )
	) {
		return false;
	}

	ai4seo_invalidate_semaphore_option_cache( $option_key );

	return true;
}


/**
 * Build the option key for a semaphore name.
 *
 * @param string $critical_section_name Critical section identifier.
 * @return string Option key.
 */
function ai4seo_get_semaphore_option_key( string $critical_section_name ): string {
	$normalized_name = sanitize_key( $critical_section_name );

	if ( '' === $normalized_name ) {
		// Keep key length safe and deterministic even if empty after sanitize_key().
		$normalized_name = 'empty';
	}

	// Keep the full normalized-name hash even when the readable segment must fit option_name's 191-character limit.
	$option_key_prefix          = 'ai4seo_sem_';
	$full_normalized_name_hash  = md5( $normalized_name );
	$maximum_readable_name_size = 191 - strlen( $option_key_prefix ) - 1 - strlen( $full_normalized_name_hash );
	$readable_name              = substr( $normalized_name, 0, $maximum_readable_name_size );

	return $option_key_prefix . $readable_name . '_' . $full_normalized_name_hash;
}


/**
 * Release every request-locally held semaphore in its owning site context.
 *
 * @return void
 */
function ai4seo_release_all_held_semaphores(): void {
	global $wpdb;

	if ( empty( $GLOBALS['ai4seo_held_semaphores'] ) || ! is_array( $GLOBALS['ai4seo_held_semaphores'] ) ) {
		return;
	}

	// Detach first so nested shutdown behavior cannot release one token twice.
	$held_semaphores                   = $GLOBALS['ai4seo_held_semaphores'];
	$GLOBALS['ai4seo_held_semaphores'] = array();

	foreach ( $held_semaphores as $record ) {
		if (
			! is_array( $record )
			|| ! isset( $record['option_key'], $record['token'], $record['options_table'], $record['blog_id'] )
			|| ! is_string( $record['option_key'] )
			|| '' === $record['option_key']
			|| ! is_string( $record['token'] )
			|| '' === $record['token']
			|| ! is_string( $record['options_table'] )
			|| '' === $record['options_table']
			|| ! is_int( $record['blog_id'] )
		) {
			continue;
		}

		$switched_to_blog = false;

		// A held token can belong to a blog that is no longer active at shutdown.
		if ( ! isset( $wpdb->options ) || $record['options_table'] !== (string) $wpdb->options ) {
			if (
				0 >= $record['blog_id']
				|| ! function_exists( 'is_multisite' )
				|| ! is_multisite()
				|| ! function_exists( 'switch_to_blog' )
				|| ! function_exists( 'restore_current_blog' )
				|| get_current_blog_id() === $record['blog_id']
				|| ! switch_to_blog( $record['blog_id'] )
			) {
				continue;
			}

			$switched_to_blog = true;
		}

		try {
			// Never release the same-named option from a different site's table.
			if ( isset( $wpdb->options ) && $record['options_table'] === (string) $wpdb->options ) {
				ai4seo_release_semaphore_by_key_and_token( $record['option_key'], $record['token'] );
			}
		} finally {
			if ( $switched_to_blog ) {
				restore_current_blog();
			}
		}
	}
}


/**
 * Ensure we always release held semaphores on shutdown.
 *
 * @return void
 */
function ai4seo_register_semaphore_shutdown_handler() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	register_shutdown_function( 'ai4seo_release_all_held_semaphores' );
}


/**
 * Try to create the lock atomically.
 *
 * @param string $option_key Option key.
 * @param string $token      Unique token for the holder.
 * @return bool True if created, false otherwise.
 */
function ai4seo_try_create_lock( string $option_key, string $token ): bool {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 511053771, 'Prevented loop', true );
		return false;
	}

	$option_key = trim( $option_key );
	$token      = trim( $token );

	if ( '' === $option_key || '' === $token || strlen( $option_key ) > 191 || ! isset( $wpdb->options ) ) {
		return false;
	}

	$payload            = array(
		'token'      => $token,
		'started_at' => time(),
	);
	$serialized_payload = maybe_serialize( $payload );

	// INSERT IGNORE is a genuine create-if-absent operation: a duplicate key leaves the current holder untouched.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The unique option_name constraint provides the semaphore CAS; caches are invalidated below.
	$inserted_rows = $wpdb->query(
		$wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)",
			$option_key,
			$serialized_payload,
			'no'
		)
	);

	if ( 1 !== (int) $inserted_rows ) {
		return false;
	}

	ai4seo_invalidate_semaphore_option_cache( $option_key );

	// Only successful inserts publish WordPress' post-write option hooks.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror WordPress' dynamic option hook after a successful CAS insert.
	do_action( "add_option_{$option_key}", $option_key, $payload );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror WordPress' global option hook after a successful CAS insert.
	do_action( 'added_option', $option_key, $payload );

	return true;
}


/**
 * Return true if the existing lock is stale by TTL.
 *
 * @param mixed $payload Stored payload.
 * @return bool
 */
function ai4seo_is_lock_stale( $payload ): bool {
	if ( ! is_array( $payload )
		|| ! isset( $payload['token'], $payload['started_at'] )
		|| ! is_string( $payload['token'] )
		|| '' === trim( $payload['token'] )
		|| ! is_int( $payload['started_at'] )
	) {
		return true;
	}

	$started_at = $payload['started_at'];

	if ( $started_at <= 0 ) {
		return true;
	}

	$current_time          = time();
	$maximum_future_offset = max( 1, (int) AI4SEO_SEMAPHORE_MAX_WAIT_SECONDS );

	// Tolerate minor clock drift, but reclaim corrupt locks that could otherwise remain live indefinitely.
	if ( $started_at > ( $current_time + $maximum_future_offset ) ) {
		return true;
	}

	return ( $current_time - $started_at ) > (int) AI4SEO_SEMAPHORE_TTL_SECONDS;
}


/**
 * Invalidate every WordPress option-cache bucket that can retain semaphore state.
 *
 * Deleting notoptions rather than writing a missing marker keeps a concurrent replacement visible.
 *
 * @param string $option_key Semaphore option key.
 * @return void
 */
function ai4seo_invalidate_semaphore_option_cache( string $option_key ): void {
	wp_cache_delete( $option_key, 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
}


/**
 * Read a semaphore option while retaining the exact serialized value used by compare-and-delete.
 *
 * @param string $option_key Semaphore option key.
 * @return array{exists: bool, raw_value: string, value: mixed}|null Snapshot, or null on invalid input/database failure.
 */
function ai4seo_get_semaphore_option_snapshot( string $option_key ): ?array {
	global $wpdb;

	$option_key = trim( $option_key );

	if ( '' === $option_key || strlen( $option_key ) > 191 || ! isset( $wpdb->options ) ) {
		return null;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The raw stored bytes are required for the semaphore delete CAS and must not come from a decoded option cache.
	$raw_option_value = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
			$option_key
		)
	);

	if ( '' !== $wpdb->last_error ) {
		return null;
	}

	if ( null === $raw_option_value ) {
		return array(
			'exists'    => false,
			'raw_value' => '',
			'value'     => null,
		);
	}

	$raw_option_value = (string) $raw_option_value;

	return array(
		'exists'    => true,
		'raw_value' => $raw_option_value,
		'value'     => ai4seo_safe_maybe_unserialize( $raw_option_value ),
	);
}


/**
 * Delete a semaphore only while its serialized payload still matches the observed value.
 *
 * @param string $option_key               Semaphore option key.
 * @param string $observed_serialized_value Exact raw option value observed before deletion.
 * @return bool True only when the matching row was deleted.
 */
function ai4seo_delete_semaphore_option_if_matches( string $option_key, string $observed_serialized_value ): bool {
	global $wpdb;

	$option_key                = trim( $option_key );
	$observed_serialized_value = (string) $observed_serialized_value;

	if ( '' === $option_key || strlen( $option_key ) > 191 || ! isset( $wpdb->options ) ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Exact-value deletion is the semaphore release CAS; caches are invalidated below.
	$deleted_rows = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name = %s AND BINARY option_value = %s",
			$option_key,
			$observed_serialized_value
		)
	);

	if ( 1 !== (int) $deleted_rows ) {
		return false;
	}

	ai4seo_invalidate_semaphore_option_cache( $option_key );

	// Only the process that removed the exact observed row publishes deletion hooks.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror WordPress' dynamic option hook after a successful CAS delete.
	do_action( "delete_option_{$option_key}", $option_key );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror WordPress' global option hook after a successful CAS delete.
	do_action( 'deleted_option', $option_key );

	return true;
}


/**
 * Release by option key and token. Internal helper.
 *
 * @param string $option_key Option key.
 * @param string $token      Token we expect to hold.
 * @return bool True if released or not present, false if held by someone else.
 */
function ai4seo_release_semaphore_by_key_and_token( string $option_key, string $token ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 255831310, 'Prevented loop', true );
		return false;
	}

	$existing_lock = ai4seo_get_semaphore_option_snapshot( $option_key );

	if ( null === $existing_lock ) {
		return false;
	}

	if ( ! $existing_lock['exists'] ) {
		return true; // Already gone.
	}

	$existing_token = is_array( $existing_lock['value'] ) && isset( $existing_lock['value']['token'] ) && is_string( $existing_lock['value']['token'] )
		? $existing_lock['value']['token']
		: '';

	if ( $existing_token !== $token ) {
		// Do not release another holder’s lock.
		return false;
	}

	return ai4seo_delete_semaphore_option_if_matches( $option_key, $existing_lock['raw_value'] );
}


// endregion
// ___________________________________________________________________________________________.
