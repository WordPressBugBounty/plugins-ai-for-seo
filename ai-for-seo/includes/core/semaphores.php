<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region SEMAPHORES ============================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

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

	$deadline = microtime( true ) + (float) AI4SEO_SEMAPHORE_MAX_WAIT_SECONDS;
	$interval = (int) max( 1, floor( (float) AI4SEO_SEMAPHORE_POLL_INTERVAL_SECONDS * 1_000_000 ) ); // microseconds.

	while ( microtime( true ) < $deadline ) {
		// Fast path: create lock if not set.
		if ( ai4seo_try_create_lock( $option_key, $token ) === true ) {
			// Remember to release on shutdown.
			$GLOBALS['ai4seo_held_semaphores'][ $option_key ] = $token;
			return true;
		}

		// If set, check staleness and reclaim if stale.
		$existing = ai4seo_get_option( $option_key );

		if ( false !== $existing && ai4seo_is_lock_stale( $existing ) === true ) {
			// Remove stale lock and try again immediately.
			ai4seo_delete_option( $option_key );
			// Next loop iteration will try to acquire again.
		}

		// Wait before next attempt.
		usleep( $interval );
	}

	return false;
}

// =========================================================================================== \\

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

	$token = isset( $GLOBALS['ai4seo_held_semaphores'][ $option_key ] )
		? (string) $GLOBALS['ai4seo_held_semaphores'][ $option_key ]
		: '';

	if ( '' === $token ) {
		// We do not believe we hold this lock; do a safe attempt anyway.
		$existing = ai4seo_get_option( $option_key );

		if ( false === $existing ) {
			return true;
		}

		return false; // Someone else holds it.
	}

	$released = ai4seo_release_semaphore_by_key_and_token( $option_key, $token );

	// Clean local map regardless.
	unset( $GLOBALS['ai4seo_held_semaphores'][ $option_key ] );

	return $released;
}

// =========================================================================================== \\

/**
 * Build the option key for a semaphore name.
 *
 * @param string $critical_section_name Critical section identifier.
 * @return string Option key.
 */
function ai4seo_get_semaphore_option_key( string $critical_section_name ): string {
	$normalized = sanitize_key( (string) $critical_section_name );

	if ( '' === $normalized ) {
		// Keep key length safe and deterministic even if empty after sanitize_key().
		$normalized = 'empty';
	}

	// Option name length safety with original hash.
	$hash = md5( $normalized );
	return 'ai4seo_sem_' . $normalized . '_' . $hash;
}

// =========================================================================================== \\

/**
 * Ensure we always release held semaphores on shutdown.
 *
 * @return void
 */
function ai4seo_register_semaphore_shutdown_handler() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	register_shutdown_function(
		static function () {
			if ( empty( $GLOBALS['ai4seo_held_semaphores'] ) || ! is_array( $GLOBALS['ai4seo_held_semaphores'] ) ) {
				return;
			}

			foreach ( $GLOBALS['ai4seo_held_semaphores'] as $option_key => $token ) {
				// Best-effort release. Ignore result.
				ai4seo_release_semaphore_by_key_and_token( $option_key, $token );
			}

			// Reset map to avoid double work if shutdown functions chain.
			$GLOBALS['ai4seo_held_semaphores'] = array();
		}
	);
}

// =========================================================================================== \\

/**
 * Try to create the lock atomically.
 *
 * @param string $option_key Option key.
 * @param string $token      Unique token for the holder.
 * @return bool True if created, false otherwise.
 */
function ai4seo_try_create_lock( string $option_key, string $token ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 511053771, 'Prevented loop', true );
		return false;
	}

	$payload = array(
		'token'      => (string) $token,
		'started_at' => time(),
	);

	// Atomic when the option does not yet exist.
	// Do not autoload.
	return aa_option( $option_key, $payload, '', 'no' );
}

// =========================================================================================== \\

/**
 * Return true if the existing lock is stale by TTL.
 *
 * @param array $payload Stored payload.
 * @return bool
 */
function ai4seo_is_lock_stale( array $payload ): bool {
	$started_at = isset( $payload['started_at'] ) ? (int) $payload['started_at'] : 0;

	if ( $started_at <= 0 ) {
		return true;
	}

	return ( time() - $started_at ) > (int) AI4SEO_SEMAPHORE_TTL_SECONDS;
}

// =========================================================================================== \\

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

	$existing = ai4seo_get_option( $option_key );

	if ( false === $existing ) {
		return true; // Already gone.
	}

	$existing_token = is_array( $existing ) && isset( $existing['token'] ) ? (string) $existing['token'] : '';

	if ( $existing_token !== (string) $token ) {
		// Do not release another holder’s lock.
		return false;
	}

	ai4seo_delete_option( $option_key );
	return true;
}


// endregion
// ___________________________________________________________________________________________.
