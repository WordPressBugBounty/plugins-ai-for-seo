<?php
/**
 * Registers and processes plugin cron jobs.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region CRON JOBS (CRONJOBS) ================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Determine whether WordPress's built-in cron runner is explicitly disabled.
 *
 * @return bool Whether DISABLE_WP_CRON is enabled.
 */
function ai4seo_is_wordpress_cron_disabled(): bool {
	// An undefined constant keeps the default WordPress cron behavior enabled.
	return defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
}


/**
 * Function to schedule cron jobs
 *
 * @return void
 */
function ai4seo_schedule_cron_jobs() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// add custom cron schedule for automated metadata generation.
	if ( ! wp_next_scheduled( AI4SEO_BULK_GENERATION_CRON_JOB_NAME ) || wp_next_scheduled( AI4SEO_BULK_GENERATION_CRON_JOB_NAME ) > time() + 5 * MINUTE_IN_SECONDS + 30 ) {
		ai4seo_schedule_background_job_checked( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 1, 'five_minutes' );
	}

	// add custom cron schedule for analyzing the plugins performance.
	if ( ! wp_next_scheduled( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME ) || wp_next_scheduled( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME ) > time() + HOUR_IN_SECONDS + 30 ) {
		ai4seo_schedule_background_job_checked( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, 1, 'one_hour' );
	}

	if ( ! ai4seo_is_active_metadata_migration_v235_completed() ) {
		ai4seo_schedule_active_metadata_migration_v235_cron_job();
	}
}


/**
 * Function to un-schedule cron jobs
 *
 * @return void
 */
function ai4seo_un_schedule_cron_jobs() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Remove recurring work and any on-demand recovery task so deactivation leaves no plugin callbacks behind.
	wp_clear_scheduled_hook( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
	wp_clear_scheduled_hook( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME );
	wp_clear_scheduled_hook( AI4SEO_GENERATION_STATUS_SUMMARY_REBUILD_CRON_JOB_NAME );
	ai4seo_unschedule_taxonomy_proxy_sync();
	ai4seo_unschedule_active_metadata_migration_v235_cron_job();
}


/**
 * Schedule local inventory independently of account-dependent cron initialization.
 *
 * @return void
 */
function ai4seo_schedule_taxonomy_proxy_sync(): void {
	if ( ! AI4SEO_CRON_JOBS_ENABLED || ai4seo_does_user_need_to_accept_tos_toc_and_pp() ) {
		return;
	}
	if ( ! has_filter( 'cron_schedules', 'ai4seo_add_cron_job_intervals' ) ) {
		// phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- Local inventory reuses the established five-minute interval.
		add_filter( 'cron_schedules', 'ai4seo_add_cron_job_intervals' );
	}
	if ( ! wp_next_scheduled( AI4SEO_TAXONOMY_PROXY_SYNC_CRON_HOOK )
		&& ! wp_schedule_event( time() + 10, 'five_minutes', AI4SEO_TAXONOMY_PROXY_SYNC_CRON_HOOK ) ) {
		ai4seo_debug_message( 852107601, 'Unable to schedule taxonomy inventory.', true );
	}
}


/**
 * Remove both local hooks on deactivation, including non-admin CLI deactivation.
 *
 * @return void
 */
function ai4seo_unschedule_taxonomy_proxy_sync(): void {
	wp_clear_scheduled_hook( AI4SEO_TAXONOMY_PROXY_SYNC_CRON_HOOK );
	wp_clear_scheduled_hook( AI4SEO_TAXONOMY_PROXY_CONTINUATION_CRON_HOOK );
}


/**
 * Process one bounded, replayable section of the site-local taxonomy inventory.
 *
 * @return bool Whether this invocation completed a full inventory cycle.
 */
function ai4seo_taxonomy_proxy_sync_cron_job(): bool {
	if ( ! AI4SEO_CRON_JOBS_ENABLED || ai4seo_does_user_need_to_accept_tos_toc_and_pp() ) {
		return false;
	}
	$lock = ai4seo_get_taxonomy_proxy_sync_lock_name();
	if ( ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) || ! ai4seo_acquire_database_advisory_lock( $lock ) ) {
		return false;
	}
	$started = microtime( true );
	try {
		$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_TAXONOMY_PROXY_SYNC_OPTION_NAME );
		if ( null === $snapshot || ( $snapshot['exists'] && ! ai4seo_is_valid_taxonomy_proxy_sync_progress( $snapshot['value'] ) ) ) {
			ai4seo_debug_message( 852107602, 'Unreadable or unsupported taxonomy inventory checkpoint.', true );
			return false;
		}
		$progress = $snapshot['exists'] ? $snapshot['value'] : array( 'phase' => 'completed' );
		if ( 'completed' === $progress['phase'] ) {
			$upper = ai4seo_get_taxonomy_proxy_sync_upper_bound( 'source' );
			if ( is_wp_error( $upper ) ) {
				ai4seo_debug_message( 852107603, $upper->get_error_code(), true );
				return false;
			}
			$progress = array(
				'version'        => 1,
				'phase'          => 'source',
				'cursor'         => 0,
				'source_upper'   => $upper,
				'proxy_upper'    => 0,
				'last_run'       => 0,
				'last_status'    => 'pending',
				'last_error'     => '',
				'pending_delete' => null,
			);
		}
		$processed = 0;
		$error     = null;
		if ( ! empty( $progress['pending_delete'] ) ) {
			$result = ai4seo_delete_taxonomy_proxy( $progress['pending_delete']['post_id'], $progress, $snapshot );
			++$processed;
			if ( is_wp_error( $result ) ) {
				$error = $result;
			}
		}
		while ( null === $error && $processed < AI4SEO_TAXONOMY_PROXY_SYNC_BATCH_SIZE && microtime( true ) - $started < AI4SEO_TAXONOMY_PROXY_SYNC_TIME_LIMIT ) {
			if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) ) {
				return false;
			}
			$rows = ai4seo_read_taxonomy_proxy_sync_batch( $progress, min( 25, AI4SEO_TAXONOMY_PROXY_SYNC_BATCH_SIZE - $processed ) );
			if ( is_wp_error( $rows ) ) {
				$error = $rows;
				break;
			}
			if ( ! $rows ) {
				if ( 'proxies' === $progress['phase'] ) {
					$progress['phase'] = 'completed';
					break;
				}
				$upper = ai4seo_get_taxonomy_proxy_sync_upper_bound( 'proxies' );
				if ( is_wp_error( $upper ) ) {
					$error = $upper;
					break;
				}
				$progress['phase']       = 'proxies';
				$progress['cursor']      = 0;
				$progress['proxy_upper'] = $upper;
				continue;
			}
			foreach ( $rows as $row ) {
				if ( microtime( true ) - $started >= AI4SEO_TAXONOMY_PROXY_SYNC_TIME_LIMIT ) {
					break 2;
				}
				if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) ) {
					return false;
				}
				++$processed;
				if ( 'source' === $progress['phase'] ) {
					$result = ai4seo_get_taxonomy_proxy_source_state( (int) $row['id'] );
					if ( ! is_wp_error( $result ) && 'available' === $result['state'] ) {
						$result = ai4seo_upsert_taxonomy_proxy( $result );
					}
				} else {
					$result = ai4seo_reconcile_taxonomy_proxy( (int) $row['id'], $progress, $snapshot );
				}
				if ( is_wp_error( $result ) ) {
					$error = $result;
					break 2;
				}
				// Only confirmed records advance; a killed process can safely replay this section.
				$progress['cursor'] = (int) $row['id'];
			}
		}
		if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) ) {
			return false;
		}
		$completed               = null === $error && 'completed' === $progress['phase'];
		$progress['last_run']    = time();
		$progress['last_status'] = $error ? 'error' : ( $completed ? 'completed' : 'paused' );
		$progress['last_error']  = $error ? $error->get_error_code() : '';
		if ( $error ) {
			ai4seo_debug_message( 852107604, $error->get_error_code(), true );
		}
		if ( is_wp_error( ai4seo_save_taxonomy_proxy_sync_progress( $progress, $snapshot ) ) ) {
			ai4seo_debug_message( 852107605, 'Taxonomy inventory checkpoint was not saved; the section will be replayed.', true );
			return false;
		}
		if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) ) {
			return false;
		}
		if ( $completed ) {
			wp_clear_scheduled_hook( AI4SEO_TAXONOMY_PROXY_CONTINUATION_CRON_HOOK );
		} elseif ( ! wp_next_scheduled( AI4SEO_TAXONOMY_PROXY_CONTINUATION_CRON_HOOK )
			&& ! wp_schedule_single_event( time() + MINUTE_IN_SECONDS, AI4SEO_TAXONOMY_PROXY_CONTINUATION_CRON_HOOK ) ) {
			ai4seo_debug_message( 852107606, 'Unable to schedule taxonomy inventory continuation; the recurring event remains available.', true );
		}
		return $completed;
	} catch ( Throwable $throwable ) {
		// No cursor is committed on an unexpected callback failure; retain partial posts for repair.
		ai4seo_debug_message( 852107607, 'Taxonomy inventory interrupted: ' . get_class( $throwable ), true );
		return false;
	} finally {
		ai4seo_release_database_advisory_lock( $lock );
	}
}


/**
 * Checks whether the v235 active metadata migration is completed.
 *
 * @return bool
 */
function ai4seo_is_active_metadata_migration_v235_completed(): bool {
	// Stored completion and timestamps are hints; retained source rows remain authoritative.
	try {
		$has_rows = ai4seo_has_legacy_active_metadata_rows( $read );
		return $read && ! $has_rows;
	} catch ( Throwable $throwable ) {
		return false;
	}
}

/**
 * Starts the v235 active metadata migration and schedules its cron job.
 *
 * @return void
 */
function ai4seo_start_active_metadata_migration_v235(): void {
	// Scheduling does not reset retained progress or claim ownership through a timestamp.
	ai4seo_schedule_active_metadata_migration_v235_cron_job();
}

/**
 * Schedules the temporary v235 active metadata migration cron job.
 *
 * @return void
 */
function ai4seo_schedule_active_metadata_migration_v235_cron_job(): void {
	if ( ai4seo_is_active_metadata_migration_v235_completed() ) {
		return;
	}

	if ( ! has_filter( 'cron_schedules', 'ai4seo_add_cron_job_intervals' ) ) {
		// phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- The temporary migration intentionally reuses the five-minute plugin schedule.
		add_filter( 'cron_schedules', 'ai4seo_add_cron_job_intervals' );
	}

	if ( ! wp_next_scheduled( AI4SEO_ACTIVE_METADATA_MIGRATION_V235_CRON_JOB_NAME ) ) {
		wp_schedule_event( time() + 10, 'five_minutes', AI4SEO_ACTIVE_METADATA_MIGRATION_V235_CRON_JOB_NAME );
	}
}


/**
 * Unschedules the temporary v235 active metadata migration cron job.
 *
 * @return void
 */
function ai4seo_unschedule_active_metadata_migration_v235_cron_job(): void {
	wp_clear_scheduled_hook( AI4SEO_ACTIVE_METADATA_MIGRATION_V235_CRON_JOB_NAME );
}


/**
 * Cron handler for migrating legacy active metadata rows into the v235 JSON postmeta entry.
 *
 * @param bool $force Whether to retry a deferred local migration immediately.
 * @return bool
 */
function ai4seo_active_metadata_migration_v235_cron_job( bool $force = false ): bool {
	if ( ( ! AI4SEO_CRON_JOBS_ENABLED && wp_doing_cron() ) || ai4seo_does_user_need_to_accept_tos_toc_and_pp() ) {
		return false;
	}
	$lock = ai4seo_get_plugin_update_lock_name();
	if ( ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) || ! ai4seo_acquire_database_advisory_lock( $lock ) ) {
		return false;
	}
	try {
		$has_rows = ai4seo_has_legacy_active_metadata_rows( $read );
		if ( ! $read ) {
			return false;
		}
		$progress = ai4seo_read_plugin_update_progress( $snapshot );
		// A downgrade must not reset newer checkpoints or publish older compatibility markers.
		if ( null === $progress || version_compare( $progress['target'], AI4SEO_PLUGIN_VERSION_NUMBER, '>' ) ) {
			return false;
		}
		if ( $has_rows && 'completed' === ( $progress['steps']['active_metadata']['status'] ?? '' ) ) {
			$progress['steps']['active_metadata'] = ai4seo_plugin_update_step_result( 'pending' );
			if ( true !== ai4seo_compare_and_swap_option_snapshot( AI4SEO_PLUGIN_UPDATE_PROGRESS_OPTION_NAME, $snapshot, $progress, false ) ) {
				return false;
			}
			$force = true;
		}
		$complete = ai4seo_run_plugin_update_step(
			'active_metadata',
			static function ( array $previous ) use ( $has_rows ): array {
				if ( ! $has_rows ) {
					return ai4seo_plugin_update_step_result( 'completed' );
				}
				if ( ai4seo_get_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS ) ) {
					return ai4seo_plugin_update_step_result( 'blocked', 'heavy_operations_disabled', $previous['cursor'], $previous['issues'], $previous['examples'] );
				}
				$result = array();
				ai4seo_run_active_metadata_migration_v235_batch( $result, $previous );
				return $result;
			},
			$force || ! $has_rows
		);
		if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) ) {
			return false;
		}
		// Publish legacy compatibility markers together, preserving unrelated and unreadable state.
		$markers_saved = ai4seo_publish_plugin_update_markers(
			array(
				AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE => $complete ? 'completed' : 'idle',
				AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_LAST_RUN_TIME => time(),
				AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME => time(),
			)
		);
		if ( $complete && $markers_saved ) {
			ai4seo_unschedule_active_metadata_migration_v235_cron_job();
		}
		return $complete && $markers_saved;
	} finally {
		ai4seo_release_database_advisory_lock( $lock );
	}
}

/**
 * Classify a historical timestamp without turning unknown data into a recent run.
 *
 * @param mixed    $value Stored timestamp; legacy integer strings are accepted in memory.
 * @param int      $window Maximum age considered recent.
 * @param int|null $now Reference clock for deterministic inspection.
 * @return string Missing, invalid, future, recent or stale.
 */
function ai4seo_classify_background_timestamp( $value, int $window, ?int $now = null ): string {
	$now = null === $now ? time() : $now;
	if ( null === $value || 0 === $value || '0' === $value ) {
		return 'missing';
	}
	if ( ( ! is_int( $value ) && ! is_string( $value ) ) || false === filter_var( $value, FILTER_VALIDATE_INT ) || (int) $value < 0 ) {
		return 'invalid';
	}
	if ( (int) $value > $now ) {
		return 'future';
	}
	return $now - (int) $value < max( 1, $window ) ? 'recent' : 'stale';
}

/**
 * Read one job from raw storage, preserving healthy siblings in damaged maps.
 *
 * @param string $job Cron hook.
 * @return array Readability, raw status and independently classified timestamps.
 */
function ai4seo_inspect_background_job_state( string $job ): array {
	$result   = array(
		'readable'     => false,
		'status'       => 'unknown',
		'updated_at'   => null,
		'last_call'    => null,
		'time_quality' => 'invalid',
		'issues'       => array(),
	);
	$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
	if ( null === $snapshot || ( $snapshot['exists'] && ! is_array( $snapshot['value'] ) ) ) {
		$result['issues'][] = 'storage';
		return $result;
	}
	$values             = $snapshot['exists'] ? $snapshot['value'] : array();
	$result['readable'] = true;
	foreach ( array(
		'status'     => AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST,
		'updated_at' => AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES,
		'last_call'  => AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS,
	) as $field => $name ) {
		$map = $values[ $name ] ?? array();
		if ( ! is_array( $map ) || ( array_key_exists( $name, $values ) && null === $values[ $name ] ) ) {
			$result['readable'] = false;
			$result['issues'][] = $field;
			continue;
		}
		// Older last-call writers stripped punctuation; status writers did not.
		$key = 'last_call' === $field ? preg_replace( '/[^a-zA-Z0-9_]/', '', sanitize_key( $job ) ) : $job;
		if ( ! array_key_exists( $key, $map ) ) {
			continue;
		}
		$value = $map[ $key ];
		if ( 'status' === $field ) {
			if ( is_string( $value ) && preg_match( '/^[a-z0-9-]+$/', $value ) ) {
				$result['status'] = $value;
			} else {
				$result['issues'][] = $field;
			}
		} else {
			$quality = ai4seo_classify_background_timestamp( $value, max( 60, (int) ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_DURATION ) + 10 ) );
			if ( 'updated_at' === $field ) {
				$result['time_quality'] = $quality;
			}
			if ( in_array( $quality, array( 'recent', 'stale' ), true ) ) {
				$result[ $field ] = (int) $value;
			} elseif ( 'missing' !== $quality || null === $value ) {
				$result['issues'][] = $field;
			}
		}
	}
	if ( null === $result['updated_at'] && ! in_array( 'updated_at', $result['issues'], true ) ) {
		$result['time_quality'] = 'missing';
	}
	return $result;
}

/**
 * Publish only this job's related fields in one existing environmental CAS.
 *
 * @param string      $job Cron hook.
 * @param string|null $status New status, or null for a last-call update only.
 * @param int|null    $last_call Optional actual invocation timestamp.
 * @return bool Whether every requested field was stored together.
 */
function ai4seo_publish_background_job_state( string $job, ?string $status, ?int $last_call = null ): bool {
	if ( '' === $job || ( null !== $status && ! preg_match( '/^[a-z0-9-]+$/', $status ) ) || ( null !== $last_call && $last_call < 0 ) ) {
		return false;
	}
	$now    = time();
	$result = null;
	return ai4seo_mutate_environmental_variable_overrides(
		static function ( array $values ) use ( $job, $status, $last_call, $now ): array {
			$replacement = $values;
			$updates     = array();
			if ( null === $status && null === $last_call ) {
				$map    = $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST ] ?? null;
				$status = is_array( $map ) ? ( $map[ $job ] ?? null ) : null;
				if ( ! is_string( $status ) || ! preg_match( '/^[a-z0-9-]+$/', $status ) ) {
					return array();
				}
			}
			if ( null !== $status ) {
				$updates[ AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST ]              = array( $job, $status );
				$updates[ AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES ] = array( $job, $now );
			}
			if ( null !== $last_call ) {
				$key = preg_replace( '/[^a-zA-Z0-9_]/', '', sanitize_key( $job ) );
				$updates[ AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS ] = array( $key, $last_call );
				$replacement[ AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL ]       = $last_call;
			}
			foreach ( $updates as $name => $update ) {
				if ( array_key_exists( $name, $values ) && ! is_array( $values[ $name ] ) ) {
					return array();
				}
				$replacement[ $name ][ $update[0] ] = $update[1];
			}
			return array(
				'overrides' => $replacement,
				'changed'   => $replacement !== $values,
				'result'    => true,
			);
		},
		false,
		$result,
		true
	);
}

/**
 * Name a site-local execution/scheduling lock using the existing database lock scope.
 *
 * @param string $job Cron hook.
 * @return string Bounded advisory-lock name.
 */
function ai4seo_get_background_job_lock_name( string $job ): string {
	$scope = ai4seo_get_posts_table_analysis_database_lock_name();
	return '' === $scope ? '' : 'ai4seo-job-' . md5( $scope . '|' . $job );
}

/**
 * Acquire a coordinator without displacing per-post locks on legacy MySQL.
 *
 * @param string    $lock Site-local job lock.
 * @param bool|null $read_succeeded Distinguishes an unavailable capability read from contention.
 * @return bool Whether this request acquired the coordinator.
 */
function ai4seo_acquire_background_job_lock( string $lock, ?bool &$read_succeeded = null ): bool {
	$multiple = ai4seo_database_supports_multiple_advisory_locks( null, $read_succeeded );
	if ( ! $read_succeeded || ! ai4seo_is_valid_database_advisory_lock_name( $lock ) ) {
		return false;
	}
	// A paid request can outlive an ordinary 30-second semaphore. All job callers use this same
	// bounded lease; exact-token renewal and per-post ownership remain required before more work.
	return $multiple ? ai4seo_acquire_database_advisory_lock( $lock ) : ai4seo_acquire_semaphore( $lock, 15 * MINUTE_IN_SECONDS );
}

/**
 * Verify the current coordinator, renewing only a live exact-token legacy lease.
 *
 * @param string $lock Site-local job lock.
 * @return bool Whether the caller may continue.
 */
function ai4seo_renew_background_job_lock( string $lock ): bool {
	$key = ai4seo_get_semaphore_option_key( $lock );
	if ( null !== ai4seo_get_held_semaphore_record( $key ) ) {
		$snapshot = ai4seo_get_semaphore_option_snapshot( $key );
		return null !== $snapshot && $snapshot['exists']
			&& ! ai4seo_is_lock_stale( $snapshot['value'], 15 * MINUTE_IN_SECONDS )
			&& ai4seo_renew_semaphore( $lock );
	}
	return ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock );
}

/**
 * Release only the current request's job ownership.
 *
 * @param string $lock Site-local job lock.
 * @return bool Whether release was verified.
 */
function ai4seo_release_background_job_lock( string $lock ): bool {
	return null !== ai4seo_get_held_semaphore_record( ai4seo_get_semaphore_option_key( $lock ) )
		? ai4seo_release_semaphore( $lock ) : ai4seo_release_database_advisory_lock( $lock );
}

/**
 * Schedule verified work without first deleting a valid fallback event.
 *
 * @param string $job Cron hook.
 * @param int    $delay Delay in seconds.
 * @param string $recurrence Optional recurring schedule for regular initialization.
 * @return array Status and verified next timestamp; no optimistic persistence.
 */
function ai4seo_schedule_background_job_checked( string $job, int $delay = 1, string $recurrence = '' ): array {
	$result = array(
		'status'    => 'failed',
		'timestamp' => null,
	);
	if ( ! AI4SEO_CRON_JOBS_ENABLED || ( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME === $job && ai4seo_get_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS ) ) ) {
		$result['status'] = 'disabled';
		return $result;
	}
	$lock            = ai4seo_get_background_job_lock_name( $job );
	$owned           = ai4seo_renew_background_job_lock( $lock );
	$capability_read = false;
	if ( ! $owned && ! ai4seo_acquire_background_job_lock( $lock, $capability_read ) ) {
		$result['status'] = $capability_read ? 'deferred' : 'failed';
		return $result;
	}
	try {
		$state = ai4seo_inspect_background_job_state( $job );
		if ( ! $state['readable'] ) {
			$result['status'] = 'blocked';
		} else {
			$now      = time();
			$target   = $now + max( 1, $delay );
			$event    = wp_get_scheduled_event( $job );
			$interval = wp_get_schedules()[ $recurrence ]['interval'] ?? 0;
			// Regular initialization preserves an existing schedule; only explicit acceleration moves it.
			if ( $event && ( ( '' !== $recurrence && $event->timestamp <= $now + $interval + 30 ) || $event->timestamp <= $target + 1 ) ) {
				$result = array(
					'status'    => 'already-scheduled',
					'timestamp' => $event->timestamp,
				);
			} else {
				$schedule  = $event && $event->schedule ? $event->schedule : $recurrence;
				$scheduled = $schedule ? wp_schedule_event( $target, $schedule, $job, array(), true ) : wp_schedule_single_event( $target, $job, array(), true );
				$verified  = wp_get_scheduled_event( $job, array(), $target );
				if ( true === $scheduled && $verified && ai4seo_renew_background_job_lock( $lock ) ) {
					$result = array(
						'status'    => 'scheduled',
						'timestamp' => $target,
					);
					if ( $event && true !== wp_unschedule_event( $event->timestamp, $job, $event->args, true ) ) {
						$result['status'] = 'scheduled-with-fallback';
					}
					// Execution status remains the last actual run; scheduled work is read from WordPress.
				}
			}
		}
	} finally {
		if ( ! $owned && ! ai4seo_release_background_job_lock( $lock ) ) {
			$result['status'] = 'failed';
		}
	}
	if ( 'failed' === $result['status'] ) {
		ai4seo_debug_message( 853126001, 'Background scheduling could not be verified for ' . sanitize_key( $job ), true );
	}
	return $result;
}

/**
 * Describe the same background state in Dashboard and Help.
 *
 * @param string $job Cron hook.
 * @return array State plus a translated label and explanation.
 */
function ai4seo_get_background_job_readiness( string $job ): array {
	$state                    = ai4seo_inspect_background_job_state( $job );
	$state['next_run']        = wp_next_scheduled( $job );
	$state['active']          = in_array( $state['status'], array( 'initiating', 'processing' ), true ) && 'recent' === $state['time_quality'];
	$state['needs_attention'] = false;
	$state['label']           = __( 'Waiting for the next run', 'ai-for-seo' );
	$state['message']         = __( 'The next run is scheduled. This page will refresh automatically.', 'ai-for-seo' );
	if ( ! AI4SEO_CRON_JOBS_ENABLED || ( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME === $job && ai4seo_get_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS ) ) ) {
		$state['active']  = false;
		$state['label']   = __( 'Background execution is disabled', 'ai-for-seo' );
		$state['message'] = __( 'The configured pause is respected. Existing jobs are retained.', 'ai-for-seo' );
	} elseif ( ! $state['readable'] || $state['issues'] || ( in_array( $state['status'], array( 'initiating', 'processing' ), true ) && ! $state['active'] ) ) {
		$state['needs_attention'] = true;
		$state['label']           = __( 'Background status needs attention', 'ai-for-seo' );
		$state['message']         = __( 'The saved run status is unavailable or outdated. Existing jobs are retained; a new run will check their ownership before continuing.', 'ai-for-seo' );
	} elseif ( $state['active'] ) {
		$state['label']   = __( 'Processing...', 'ai-for-seo' );
		$state['message'] = __( 'A recent execution has reported progress. This page will refresh automatically.', 'ai-for-seo' );
	} elseif ( in_array( $state['status'], array( 'finished-with-error', 'data-blocked', 'low-credits-balance' ), true ) ) {
		$state['needs_attention'] = true;
		$state['label']           = __( 'Last run needs attention', 'ai-for-seo' );
		$state['message']         = __( 'The last run reported an error or a processing restriction. Review the account, queue diagnostics and Latest Activity before retrying.', 'ai-for-seo' );
	} elseif ( ! $state['next_run'] ) {
		$state['needs_attention'] = true;
		$state['label']           = __( 'No run scheduled', 'ai-for-seo' );
		$state['message']         = __( 'No scheduled execution could be found. Use Run sooner to request one, or check the cron configuration.', 'ai-for-seo' );
	} elseif ( $state['next_run'] < time() - 5 * MINUTE_IN_SECONDS ) {
		$state['needs_attention'] = true;
		$state['label']           = __( 'Scheduled execution is overdue', 'ai-for-seo' );
		$state['message']         = __( 'WordPress has not started the scheduled execution. Please check the cron configuration.', 'ai-for-seo' );
	} elseif ( 'finished' === $state['status'] ) {
		$state['label'] = __( 'Last run completed', 'ai-for-seo' );
	} elseif ( 'bulk-generation-disabled' === $state['status'] ) {
		$state['label']   = __( 'Last run was paused', 'ai-for-seo' );
		$state['message'] = __( 'The last run found no enabled content types. The current configuration will be checked before the next execution.', 'ai-for-seo' );
	}
	return $state;
}

/**
 * Render the shared background status with escaped presentation values.
 *
 * @param array $state Inspected presentation state.
 * @return string Status text and explanation.
 */
function ai4seo_get_background_job_status_html( array $state ): string {
	return '<div class="ai4seo-bulk-generation-status-text">' . esc_html( $state['label'] ) . '</div>'
		. '<div class="ai4seo-bulk-generation-status-subtext">' . esc_html( $state['message'] ) . '</div>';
}

/**
 * Function to inject an additional cronjob call of a specific cronjob name, but only if there isn't already one scheduled within the next minute
 *
 * @param String $cronjob_name the name of the cronjob.
 * @param int    $delay The delay value.
 * @return void
 */
function ai4seo_inject_additional_cronjob_call( string $cronjob_name, int $delay = 1 ) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 675990511, 'Prevented loop', true );
		return;
	}

	ai4seo_schedule_background_job_checked( $cronjob_name, $delay );
}


/**
 * Function to add custom cron schedule
 *
 * @param mixed $schedules The schedules value.
 * @return mixed
 */
function ai4seo_add_cron_job_intervals( $schedules ) {
	$schedules['five_minutes'] = array(
		'interval' => 60 * 5, // Number of seconds, 5 minutes in seconds.
		'display'  => ai4seo_are_translations_ready()
			? esc_html__( 'Every Five Minutes', 'ai-for-seo' )
			: esc_html( 'Every Five Minutes' ),
	);

	$schedules['one_hour'] = array(
		'interval' => 60 * 60, // Number of seconds, 60 minutes in seconds.
		'display'  => ai4seo_are_translations_ready()
			? esc_html__( 'Every Hour', 'ai-for-seo' )
			: esc_html( 'Every Hour' ),
	);

	return $schedules;
}


/**
 * Function to set the last execution time of a cronjob
 *
 * @param string $cron_job_name the name of the cronjob.
 * @param int    $time the time of the last execution.
 * @return bool true on success, false on failure
 */
function ai4seo_set_last_cron_job_call_time( string $cron_job_name, int $time = 0 ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 422248054, 'Prevented loop', true );
		return false;
	}

	if ( ! wp_doing_cron() ) {
		return false;
	}

	return ai4seo_publish_background_job_state( $cron_job_name, null, $time );
}


/**
 * Function to get the last execution time of a cronjob
 *
 * @param string $cron_job_name the name of the cronjob.
 * @return int the last execution time of a cronjob
 */
function ai4seo_get_last_cron_job_call_time( string $cron_job_name = '' ): int {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 877770607, 'Prevented loop', true );
		return 0;
	}

	if ( $cron_job_name ) {
		return ai4seo_inspect_background_job_state( $cron_job_name )['last_call'] ?? 0;
	} else {
		return (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL );
	}
}


/**
 * Function to get the current status of a specific cron job
 *
 * @param string $cron_job_name the name of the cron job.
 * @return string the status of the cron job
 */
function ai4seo_get_cron_job_status( string $cron_job_name ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 876565543, 'Prevented loop', true );
		return '';
	}

	return ai4seo_inspect_background_job_state( $cron_job_name )['status'];
}


/**
 * Function to set the current status of a specific cron job
 *
 * @param string $cron_job_name the name of the cron job.
 * @param string $status the status of the cron job.
 * @return bool true on success, false on failure
 */
function ai4seo_set_cron_job_status( string $cron_job_name, string $status ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 164630102, 'Prevented loop', true );
		return false;
	}

	if ( ! wp_doing_cron() ) {
		return false;
	}

	$status = sanitize_key( $status );

	return ai4seo_publish_background_job_state( $cron_job_name, $status );
}


/**
 * Function to refresh the last status update time of a specific cron job
 *
 * @param string $cron_job_name the name of the cron job.
 * @return bool true on success, false on failure
 */
function ai4seo_refresh_cron_job_status_update_time( string $cron_job_name ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 495640314, 'Prevented loop', true );
		return false;
	}

	return ai4seo_publish_background_job_state( $cron_job_name, null );
}


/**
 * Function to get the last status update time of a specific cron job
 *
 * @param string $cron_job_name the name of the cron job.
 * @return int the last status update time of the cron job
 */
function ai4seo_get_cron_job_status_update_time( string $cron_job_name ): int {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 190451061, 'Prevented loop', true );
		return 0;
	}

	return ai4seo_inspect_background_job_state( $cron_job_name )['updated_at'] ?? 0;
}


/**
 * Build the request-local key for one durable Processing claim.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @return string Registry key.
 */
function ai4seo_get_bulk_generation_processing_claim_registry_key( string $context, int $post_id ): string {
	return sanitize_key( $context ) . ':' . absint( $post_id );
}


/**
 * Register a durable Processing claim for terminal and shutdown recovery.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @param string $claim_token Exact durable lease token.
 * @return bool Whether the production claim was registered.
 */
function ai4seo_register_bulk_generation_processing_claim( string $context, int $post_id, string $claim_token ): bool {
	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );
	$post_id       = absint( $post_id );
	$claim_token   = trim( $claim_token );

	if ( ! isset( $queue_options['processing'] ) || ! $post_id || '' === $claim_token ) {
		return false;
	}

	$registry_key = ai4seo_get_bulk_generation_processing_claim_registry_key( $context, $post_id );

	$GLOBALS['ai4seo_bulk_generation_processing_claims'][ $registry_key ] = array(
		'context'     => sanitize_key( $context ),
		'post_id'     => $post_id,
		'processing'  => $queue_options['processing'],
		'claim_token' => $claim_token,
	);

	if ( empty( $GLOBALS['ai4seo_bulk_generation_processing_claim_shutdown_registered'] ) ) {
		add_action( 'shutdown', 'ai4seo_recover_bulk_generation_processing_claims_on_shutdown', PHP_INT_MAX - 10 );
		$GLOBALS['ai4seo_bulk_generation_processing_claim_shutdown_registered'] = true;
	}

	return true;
}


/**
 * Return the request-owned claim record for one context/post pair.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @return array|null Claim record.
 */
function ai4seo_get_registered_bulk_generation_processing_claim( string $context, int $post_id ): ?array {
	$registry_key = ai4seo_get_bulk_generation_processing_claim_registry_key( $context, $post_id );
	$claim        = $GLOBALS['ai4seo_bulk_generation_processing_claims'][ $registry_key ] ?? null;

	return is_array( $claim ) ? $claim : null;
}


/**
 * Forget one request-local claim after terminal persistence or ownership loss.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @return void
 */
function ai4seo_unregister_bulk_generation_processing_claim( string $context, int $post_id ): void {
	$registry_key = ai4seo_get_bulk_generation_processing_claim_registry_key( $context, $post_id );
	unset( $GLOBALS['ai4seo_bulk_generation_processing_claims'][ $registry_key ] );
}


/**
 * Mark one exact request-local claim so shutdown recovery must discard prior Pending/Force intent.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @param string $claim_token Exact durable lease token.
 * @return bool Whether the matching registered claim was marked.
 */
function ai4seo_mark_registered_bulk_generation_processing_claim_for_queue_intent_discard(
	string $context,
	int $post_id,
	string $claim_token
): bool {
	$registry_key = ai4seo_get_bulk_generation_processing_claim_registry_key( $context, $post_id );
	$claim        = $GLOBALS['ai4seo_bulk_generation_processing_claims'][ $registry_key ] ?? null;

	if ( ! is_array( $claim )
		|| ! is_string( $claim['claim_token'] ?? null )
		|| ! hash_equals( $claim_token, $claim['claim_token'] )
	) {
		return false;
	}

	$claim['discard_prior_queue_intent']                                  = true;
	$GLOBALS['ai4seo_bulk_generation_processing_claims'][ $registry_key ] = $claim;
	return true;
}


/**
 * Durably request another bulk-generation pass without consulting a stale Processing status flag.
 *
 * @param int $delay Delay in seconds.
 * @return bool Whether a suitable event exists or was scheduled.
 */
function ai4seo_schedule_bulk_generation_processing_recovery( int $delay = 10 ): bool {
	$delay          = max( 1, $delay );
	$scheduled_time = time() + $delay;
	$next_scheduled = wp_next_scheduled( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );

	if ( $next_scheduled && $next_scheduled <= $scheduled_time + 1 ) {
		return true;
	}

	$schedule_result = wp_schedule_single_event( $scheduled_time, AI4SEO_BULK_GENERATION_CRON_JOB_NAME );

	return false !== $schedule_result
		|| false !== wp_next_scheduled( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
}


/**
 * Request durable repair after disabled-type queue reconciliation cannot be verified.
 *
 * @return bool Whether both the processing retry and authoritative summary rebuild were scheduled.
 */
function ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery(): bool {
	$processing_recovery_scheduled = ai4seo_schedule_bulk_generation_processing_recovery();
	$summary_rebuild_scheduled     = function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' )
		&& ai4seo_schedule_generation_status_summary_rebuild();

	if ( ! $processing_recovery_scheduled || ! $summary_rebuild_scheduled ) {
		ai4seo_debug_message( 641476230, 'Could not durably schedule disabled bulk-generation queue reconciliation.', true );
	}

	return $processing_recovery_scheduled && $summary_rebuild_scheduled;
}


/**
 * Remove Pending and Force memberships that authoritative enabled-type settings no longer allow.
 *
 * Both queue families are observed and changed under the shared post-ID transition fence. Metadata
 * membership is resolved from bounded current posts-table snapshots; missing posts are no longer an
 * enabled type and are removed. The attachment setting controls the whole attachment queue because
 * image post subtypes and NextGEN-backed media share that one generation context.
 *
 * @param array|null $enabled_post_types Receives the verified persisted enabled post types.
 * @param array|null $contexts Optional queue contexts; null preserves reconciliation of both queues.
 * @return bool True only after settings, queue transitions, and fence release were verified.
 */
function ai4seo_reconcile_disabled_bulk_generation_queue_entries( ?array &$enabled_post_types = null, ?array $contexts = null ): bool {
	global $wpdb;
	$contexts = null === $contexts ? ai4seo_get_bulk_generation_queue_contexts() : $contexts;
	if ( ! $contexts || array_diff( $contexts, ai4seo_get_bulk_generation_queue_contexts() ) ) {
		return false;
	}
	$inspect_metadata = in_array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, $contexts, true );
	$inspect_media    = in_array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES, $contexts, true );

	$enabled_post_types               = array();
	$critical_section_name            = ai4seo_get_post_id_option_transition_semaphore_name();
	$operation_succeeded              = true;
	$release_succeeded                = false;
	$persisted_post_types             = array();
	$settings_snapshot                = null;
	$current_settings_snapshot        = null;
	$queue_option_snapshots           = array();
	$inspection_state_snapshot        = array();
	$inspection_state                 = null;
	$transition_changed_options       = array();
	$rollback_tokens_by_context       = array(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA => array(),
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES => array(),
	);
	$destructive_transition_attempted = false;
	$more_reconciliation_work_remains = false;

	if ( ! function_exists( 'ai4seo_acquire_semaphore' ) || ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
		return false;
	}

	try {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			$operation_succeeded = false;
		}

		$settings_read_succeeded = false;

		if ( $operation_succeeded ) {
			$persisted_post_types = ai4seo_read_persisted_enabled_bulk_generation_post_types(
				$settings_read_succeeded,
				$settings_snapshot
			);
			$operation_succeeded  = $settings_read_succeeded && is_array( $settings_snapshot );
		}

		$metadata_queue_options   = ai4seo_get_bulk_generation_queue_options_by_context(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA
		);
		$attachment_queue_options = ai4seo_get_bulk_generation_queue_options_by_context(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES
		);
		$required_queue_options   = array();
		foreach ( $contexts as $queue_context ) {
			$context_options = ai4seo_get_bulk_generation_queue_options_by_context( $queue_context );
			foreach ( array( 'pending', 'force_overwrite', 'processing' ) as $key ) {
				$required_queue_options[] = $context_options[ $key ] ?? '';
			}
		}

		if ( $operation_succeeded && in_array( '', $required_queue_options, true ) ) {
			$operation_succeeded = false;
		}

		$queue_post_ids = array();

		if ( $operation_succeeded ) {
			foreach ( $required_queue_options as $option_name ) {
				if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
					$operation_succeeded = false;
					break;
				}

				$option_snapshot = ai4seo_get_raw_option_snapshot( $option_name );
				$inspection      = null !== $option_snapshot ? ai4seo_inspect_analysis_post_id_option( $option_name, $option_snapshot ) : null;

				if ( null === $option_snapshot || ! $inspection['read_succeeded'] || ! $inspection['complete'] ) {
					$operation_succeeded = false;
					break;
				}

				$queue_option_snapshots[ $option_name ] = $option_snapshot;
				$queue_post_ids[ $option_name ]         = ai4seo_normalize_option_post_id_collection( $option_snapshot['value'] );
			}
		}

		$metadata_pending_option      = $metadata_queue_options['pending'] ?? '';
		$metadata_force_option        = $metadata_queue_options['force_overwrite'] ?? '';
		$metadata_processing_option   = $metadata_queue_options['processing'] ?? '';
		$attachment_pending_option    = $attachment_queue_options['pending'] ?? '';
		$attachment_force_option      = $attachment_queue_options['force_overwrite'] ?? '';
		$attachment_processing_option = $attachment_queue_options['processing'] ?? '';
		$metadata_post_ids            = $operation_succeeded && $inspect_metadata
			? ai4seo_normalize_option_post_id_collection(
				array_merge(
					$queue_post_ids[ $metadata_pending_option ],
					$queue_post_ids[ $metadata_force_option ]
				)
			)
			: array();
		$attachment_post_ids          = $operation_succeeded && $inspect_media
			? ai4seo_normalize_option_post_id_collection(
				array_merge(
					$queue_post_ids[ $attachment_pending_option ],
					$queue_post_ids[ $attachment_force_option ]
				)
			)
			: array();

		$settings_fingerprint         = '';
		$metadata_pending_fingerprint = '';
		$metadata_force_fingerprint   = '';

		if ( $operation_succeeded && $inspect_metadata ) {
			$operation_succeeded = ai4seo_read_disabled_queue_inspection_state_under_lock(
				$inspection_state_snapshot,
				$inspection_state
			);
		}

		if ( $operation_succeeded && $inspect_metadata ) {
			if ( ! is_array( $settings_snapshot ) ) {
				$operation_succeeded = false;
			} else {
				$settings_fingerprint         = ai4seo_get_disabled_queue_inspection_snapshot_fingerprint(
					AI4SEO_SETTINGS_OPTION_NAME,
					$settings_snapshot
				);
				$metadata_pending_fingerprint = ai4seo_get_disabled_queue_inspection_snapshot_fingerprint(
					$metadata_pending_option,
					$queue_option_snapshots[ $metadata_pending_option ]
				);
				$metadata_force_fingerprint   = ai4seo_get_disabled_queue_inspection_snapshot_fingerprint(
					$metadata_force_option,
					$queue_option_snapshots[ $metadata_force_option ]
				);
				$operation_succeeded          = '' !== $settings_fingerprint
					&& '' !== $metadata_pending_fingerprint
					&& '' !== $metadata_force_fingerprint;
			}
		}

		$inspection_state_matches_settings = $operation_succeeded
			&& is_array( $inspection_state )
			&& hash_equals( $settings_fingerprint, $inspection_state['settings_fingerprint'] );
		$metadata_inspection_was_complete  = $inspection_state_matches_settings
			&& true === $inspection_state['complete'];
		$metadata_inspection_anchor        = $inspection_state_matches_settings
			&& ! $metadata_inspection_was_complete
			? $inspection_state['last_inspected_metadata_post_id']
			: 0;
		$metadata_inspection_offset        = 0;
		$metadata_anchor_is_present        = false;

		if ( $operation_succeeded && 0 < $metadata_inspection_anchor ) {
			$metadata_anchor_position = array_search( $metadata_inspection_anchor, $metadata_post_ids, true );

			if ( false !== $metadata_anchor_position ) {
				$metadata_anchor_is_present = true;
				$metadata_inspection_offset = $metadata_anchor_position + 1;
			}
		}

		$all_disabled_attachment_post_ids  = $operation_succeeded
			&& ! in_array( 'attachment', $persisted_post_types, true )
			? $attachment_post_ids
			: array();
		$metadata_inspection_is_needed     = $operation_succeeded
			&& ! $metadata_inspection_was_complete
			&& $metadata_inspection_offset < count( $metadata_post_ids );
		$metadata_inspection_limit         = $metadata_inspection_is_needed
			&& $all_disabled_attachment_post_ids
			? 25
			: 50;
		$metadata_inspection_post_ids      = $metadata_inspection_is_needed
			? array_slice( $metadata_post_ids, $metadata_inspection_offset, $metadata_inspection_limit )
			: array();
		$metadata_inspection_reached_end   = $operation_succeeded
			&& (
				$metadata_inspection_was_complete
				|| $metadata_inspection_offset + count( $metadata_inspection_post_ids ) >= count( $metadata_post_ids )
			);
		$enabled_metadata_post_type_lookup = array_fill_keys(
			array_values( array_diff( $persisted_post_types, array( 'attachment' ) ) ),
			true
		);
		$disabled_metadata_post_ids        = $operation_succeeded && ! $enabled_metadata_post_type_lookup
			? $metadata_inspection_post_ids
			: array();

		if ( $operation_succeeded && $metadata_inspection_post_ids && $enabled_metadata_post_type_lookup ) {
			if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
				$operation_succeeded = false;
			}

			$post_type_query = $operation_succeeded
				? ai4seo_prepare_database_query(
					'SELECT ID, post_type FROM {{posts_table}} WHERE ID IN ({{post_ids}}) ORDER BY ID ASC',
					array(
						'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
						'post_ids'    => ai4seo_database_list_binding( '%d', $metadata_inspection_post_ids ),
					)
				)
				: false;

			if ( false === $post_type_query ) {
				$operation_succeeded = false;
			}

			$wpdb->last_error = '';
			$post_type_rows   = $operation_succeeded
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler prepares this bounded primary-key snapshot while the queue transition fence is held.
				? $wpdb->get_results( $post_type_query, ARRAY_A )
				: null;
			$database_error = (string) $wpdb->last_error;

			if ( ! ai4seo_renew_post_id_option_transition_semaphore() || '' !== $database_error || ! is_array( $post_type_rows ) ) {
				$operation_succeeded = false;
			}

			$post_types_by_id      = array();
			$inspected_post_lookup = array_fill_keys( $metadata_inspection_post_ids, true );

			foreach ( $operation_succeeded ? $post_type_rows : array() as $post_type_row ) {
				if (
					! is_array( $post_type_row )
					|| 2 !== count( $post_type_row )
					|| ! array_key_exists( 'ID', $post_type_row )
					|| ! array_key_exists( 'post_type', $post_type_row )
				) {
					$operation_succeeded = false;
					break;
				}

				$post_id   = ai4seo_normalize_database_id( $post_type_row['ID'] );
				$post_type = $post_type_row['post_type'];

				if (
					false === $post_id
					|| ! isset( $inspected_post_lookup[ $post_id ] )
					|| isset( $post_types_by_id[ $post_id ] )
					|| ! is_string( $post_type )
					|| '' === $post_type
					|| sanitize_key( $post_type ) !== $post_type
				) {
					$operation_succeeded = false;
					break;
				}

				$post_types_by_id[ $post_id ] = $post_type;
			}

			if ( $operation_succeeded ) {
				foreach ( $metadata_inspection_post_ids as $post_id ) {
					if ( AI4SEO_TAXONOMY_PROXY_POST_TYPE === ( $post_types_by_id[ $post_id ] ?? '' ) ) {
						$archive  = ai4seo_get_taxonomy_proxy_editor_context( $post_id );
						$settings = ai4seo_decode_settings_snapshot( $settings_snapshot );
						if ( is_wp_error( $archive ) || ! is_array( $settings ) ) {
							$operation_succeeded = false;
							break;
						}
						if ( ! $archive['available'] || ! in_array( $archive['source']['taxonomy'], ai4seo_get_enabled_bulk_generation_taxonomies( $settings ), true ) ) {
							$disabled_metadata_post_ids[] = $post_id;
						}
						continue;
					}
					if (
						! isset( $post_types_by_id[ $post_id ] )
						|| ! isset( $enabled_metadata_post_type_lookup[ $post_types_by_id[ $post_id ] ] )
					) {
						$disabled_metadata_post_ids[] = $post_id;
					}
				}
			}
		}

		$next_metadata_inspection_anchor = 0;

		if ( $operation_succeeded && ! $metadata_inspection_reached_end ) {
			$disabled_metadata_lookup = array_fill_keys( $disabled_metadata_post_ids, true );

			foreach ( array_reverse( $metadata_inspection_post_ids ) as $inspected_post_id ) {
				if ( ! isset( $disabled_metadata_lookup[ $inspected_post_id ] ) ) {
					$next_metadata_inspection_anchor = $inspected_post_id;
					break;
				}
			}

			if ( 0 === $next_metadata_inspection_anchor && $metadata_anchor_is_present ) {
				$next_metadata_inspection_anchor = $metadata_inspection_anchor;
			}
		}

		$attachment_inspection_limit      = $metadata_inspection_post_ids
			&& $all_disabled_attachment_post_ids
			? 25
			: 50;
		$disabled_attachment_post_ids     = $operation_succeeded
			? array_slice( $all_disabled_attachment_post_ids, 0, $attachment_inspection_limit )
			: array();
		$more_reconciliation_work_remains = $operation_succeeded
			&& (
				! $metadata_inspection_reached_end
				|| count( $disabled_attachment_post_ids ) < count( $all_disabled_attachment_post_ids )
			);

		$removals = array();

		if ( $disabled_metadata_post_ids ) {
			$removals[ $metadata_pending_option ] = $disabled_metadata_post_ids;
			$removals[ $metadata_force_option ]   = $disabled_metadata_post_ids;
		}

		if ( $disabled_attachment_post_ids ) {
			$removals[ $attachment_pending_option ] = $disabled_attachment_post_ids;
			$removals[ $attachment_force_option ]   = $disabled_attachment_post_ids;
		}

		$rollback_membership_snapshots_by_context = array();

		if ( $operation_succeeded && $disabled_metadata_post_ids ) {
			$metadata_pending_lookup    = array_fill_keys( $queue_post_ids[ $metadata_pending_option ], true );
			$metadata_force_lookup      = array_fill_keys( $queue_post_ids[ $metadata_force_option ], true );
			$metadata_processing_lookup = array_fill_keys( $queue_post_ids[ $metadata_processing_option ], true );

			foreach ( $disabled_metadata_post_ids as $post_id ) {
				if ( isset( $metadata_processing_lookup[ $post_id ] ) ) {
					$operation_succeeded = false;
					break;
				}

				$rollback_membership_snapshots_by_context[ AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ][ $post_id ] = array(
					'pending_was_present' => isset( $metadata_pending_lookup[ $post_id ] ),
					'force_was_present'   => isset( $metadata_force_lookup[ $post_id ] ),
				);
			}
		}

		if ( $operation_succeeded && $disabled_attachment_post_ids ) {
			$attachment_pending_lookup    = array_fill_keys( $queue_post_ids[ $attachment_pending_option ], true );
			$attachment_force_lookup      = array_fill_keys( $queue_post_ids[ $attachment_force_option ], true );
			$attachment_processing_lookup = array_fill_keys( $queue_post_ids[ $attachment_processing_option ], true );

			foreach ( $disabled_attachment_post_ids as $post_id ) {
				if ( isset( $attachment_processing_lookup[ $post_id ] ) ) {
					$operation_succeeded = false;
					break;
				}

				$rollback_membership_snapshots_by_context[ AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES ][ $post_id ] = array(
					'pending_was_present' => isset( $attachment_pending_lookup[ $post_id ] ),
					'force_was_present'   => isset( $attachment_force_lookup[ $post_id ] ),
				);
			}
		}

		// Preflight every planned context before the first write. A prior partial scrub must be
		// drained by bounded orphan recovery instead of being recreated ahead of its first survivor.
		if ( $operation_succeeded && $rollback_membership_snapshots_by_context ) {
			$queue_options_by_context = array(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA              => $metadata_queue_options,
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES => $attachment_queue_options,
			);

			foreach ( $rollback_membership_snapshots_by_context as $context => $membership_snapshots ) {
				$existing_leases = array();
				$queue_options   = $queue_options_by_context[ $context ] ?? array();

				if (
					! isset( $queue_options['processing'] )
					|| ! ai4seo_read_processing_claim_leases_under_lock( $queue_options['processing'], $existing_leases )
				) {
					$operation_succeeded = false;
					break;
				}

				foreach ( array_keys( $membership_snapshots ) as $post_id ) {
					if ( isset( $existing_leases[ $post_id ] ) ) {
						$operation_succeeded = false;
						break 2;
					}
				}
			}
		}

		if ( $operation_succeeded ) {
			foreach ( $rollback_membership_snapshots_by_context as $context => $membership_snapshots ) {
				if ( ! ai4seo_renew_post_id_option_transition_semaphore()
					|| ! ai4seo_persist_queue_membership_rollback_leases_under_lock(
						$context,
						$membership_snapshots,
						$rollback_tokens_by_context[ $context ],
						true
					) ) {
					$operation_succeeded = false;
					break;
				}
			}
		}

		if ( $operation_succeeded && $removals ) {
			$did_change                       = false;
			$destructive_transition_attempted = true;
			$operation_succeeded              = ai4seo_apply_normalized_post_id_option_transition_under_lock(
				array(),
				$removals,
				$did_change,
				$transition_changed_options
			);
		}

		if ( $operation_succeeded && ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			$operation_succeeded = false;
		}

		if ( $operation_succeeded ) {
			$current_settings_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_SETTINGS_OPTION_NAME );

			$operation_succeeded = is_array( $settings_snapshot )
				&& is_array( $current_settings_snapshot )
				&& $current_settings_snapshot['exists'] === $settings_snapshot['exists']
				&& $current_settings_snapshot['option_id'] === $settings_snapshot['option_id']
				&& $current_settings_snapshot['option_name'] === $settings_snapshot['option_name']
				&& $current_settings_snapshot['autoload'] === $settings_snapshot['autoload']
				&& hash_equals( $settings_snapshot['raw_value'], $current_settings_snapshot['raw_value'] );
		}

		if ( $operation_succeeded && $inspect_metadata ) {
			$current_metadata_pending_snapshot = ai4seo_get_raw_option_snapshot( $metadata_pending_option );

			if ( null === $current_metadata_pending_snapshot || ! ai4seo_renew_post_id_option_transition_semaphore() ) {
				$operation_succeeded = false;
			}
		}

		if ( $operation_succeeded && $inspect_metadata ) {
			$current_metadata_force_snapshot = ai4seo_get_raw_option_snapshot( $metadata_force_option );

			if ( null === $current_metadata_force_snapshot || ! ai4seo_renew_post_id_option_transition_semaphore() ) {
				$operation_succeeded = false;
			}
		}

		if ( $operation_succeeded && $inspect_metadata ) {
			$current_metadata_post_ids = ai4seo_normalize_option_post_id_collection(
				array_merge(
					ai4seo_normalize_option_post_id_collection( $current_metadata_pending_snapshot['value'] ),
					ai4seo_normalize_option_post_id_collection( $current_metadata_force_snapshot['value'] )
				)
			);

			if (
				! $metadata_inspection_reached_end
				&& 0 < $next_metadata_inspection_anchor
				&& ! in_array( $next_metadata_inspection_anchor, $current_metadata_post_ids, true )
			) {
				$next_metadata_inspection_anchor = 0;
			}

			$replacement_inspection_state = array(
				'version'                         => 1,
				'settings_fingerprint'            => $settings_fingerprint,
				'metadata_pending_fingerprint'    => ai4seo_get_disabled_queue_inspection_snapshot_fingerprint(
					$metadata_pending_option,
					$current_metadata_pending_snapshot
				),
				'metadata_force_fingerprint'      => ai4seo_get_disabled_queue_inspection_snapshot_fingerprint(
					$metadata_force_option,
					$current_metadata_force_snapshot
				),
				'last_inspected_metadata_post_id' => $metadata_inspection_reached_end
					? 0
					: $next_metadata_inspection_anchor,
				'complete'                        => $metadata_inspection_reached_end,
			);

			$operation_succeeded = '' !== $replacement_inspection_state['metadata_pending_fingerprint']
				&& '' !== $replacement_inspection_state['metadata_force_fingerprint']
				&& ai4seo_replace_disabled_queue_inspection_state_under_lock(
					$inspection_state_snapshot,
					$replacement_inspection_state
				);
		}

		$resolve_rollback_tokens = static function ( bool $discard_queue_intent ) use (
			&$rollback_tokens_by_context,
			$metadata_queue_options,
			$attachment_queue_options
		): bool {
			$queue_options_by_context = array(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA              => $metadata_queue_options,
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES => $attachment_queue_options,
			);

			foreach ( $rollback_tokens_by_context as $context => &$rollback_tokens ) {
				if ( ! $rollback_tokens ) {
					continue;
				}

				$queue_options       = $queue_options_by_context[ $context ];
				$expected_post_ids   = array_keys( $rollback_tokens );
				$reconciled_post_ids = array();

				if ( ! ai4seo_reconcile_orphan_processing_rollback_leases_under_lock(
					$queue_options['processing'],
					$queue_options['pending'],
					$queue_options['force_overwrite'],
					$expected_post_ids,
					$discard_queue_intent,
					$rollback_tokens,
					null,
					$reconciled_post_ids
				) ) {
					return false;
				}

				foreach ( $reconciled_post_ids as $reconciled_post_id ) {
					unset( $rollback_tokens[ $reconciled_post_id ] );
				}

				if ( $rollback_tokens ) {
					return false;
				}
			}
			unset( $rollback_tokens );

			return true;
		};

		if ( $operation_succeeded && ! $resolve_rollback_tokens( true ) ) {
			$operation_succeeded = false;
		}

		// A Settings replacement discovered after a destructive transition cannot strand queue intent.
		// Restore only memberships this call actually removed, preserving same-ID and unrelated additions.
		if ( ! $operation_succeeded && $destructive_transition_attempted ) {
			$replacement_settings_snapshot = is_array( $current_settings_snapshot )
				? $current_settings_snapshot
				: ai4seo_get_raw_option_snapshot( AI4SEO_SETTINGS_OPTION_NAME );
			$compensation_succeeded        = $resolve_rollback_tokens( false );
			$verified_settings_snapshot    = $compensation_succeeded
				? ai4seo_get_raw_option_snapshot( AI4SEO_SETTINGS_OPTION_NAME )
				: null;
			$replacement_snapshot_stable   = $compensation_succeeded
				&& is_array( $replacement_settings_snapshot )
				&& is_array( $verified_settings_snapshot )
				&& $verified_settings_snapshot['exists'] === $replacement_settings_snapshot['exists']
				&& $verified_settings_snapshot['option_id'] === $replacement_settings_snapshot['option_id']
				&& $verified_settings_snapshot['option_name'] === $replacement_settings_snapshot['option_name']
				&& $verified_settings_snapshot['autoload'] === $replacement_settings_snapshot['autoload']
				&& hash_equals( $replacement_settings_snapshot['raw_value'], $verified_settings_snapshot['raw_value'] );

			if ( ! $compensation_succeeded || ! $replacement_snapshot_stable ) {
				ai4seo_debug_message( 641476232, 'Could not verify disabled-queue compensation after a concurrent Settings replacement.', true );
			}
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	if ( ! $operation_succeeded || ! $release_succeeded ) {
		ai4seo_debug_message( 641476231, 'Could not safely reconcile disabled bulk-generation queue entries.', true );
		ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
		return false;
	}

	if ( $more_reconciliation_work_remains ) {
		// Keep every caller fail-closed until another bounded pass verifies the remaining queue state.
		ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
		return false;
	}

	$enabled_post_types = $persisted_post_types;
	return true;
}


/**
 * Renew and verify one exact durable Processing owner under the shared transition fence.
 *
 * The request-local cron registry is deliberately not consulted, so manual and cron workers can
 * use the same ownership checkpoint. An expired-but-unreplaced exact token may renew after it wins
 * the fence; stale recovery and reset cannot transfer that ownership concurrently.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @param string $claim_token Exact durable lease token.
 * @return bool True only when the exact token and Processing membership were renewed and verified.
 */
function ai4seo_renew_bulk_generation_processing_claim( string $context, int $post_id, string $claim_token ): bool {
	$context       = sanitize_key( $context );
	$post_id       = ai4seo_normalize_option_post_id( $post_id );
	$claim_token   = trim( $claim_token );
	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

	if (
		false === $post_id
		|| '' === $claim_token
		|| 128 < strlen( $claim_token )
		|| ! isset( $queue_options['processing'] )
	) {
		return false;
	}

	$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();
	$operation_succeeded   = false;
	$release_succeeded     = false;

	if ( ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		ai4seo_schedule_bulk_generation_processing_recovery();
		return false;
	}

	try {
		$leases = array();

		if ( ai4seo_read_processing_claim_leases_under_lock( $queue_options['processing'], $leases ) ) {
			$current_lease = $leases[ $post_id ] ?? null;

			if (
				is_array( $current_lease )
				&& hash_equals( $claim_token, $current_lease['token'] )
				&& empty( $current_lease['rollback_requested'] )
				&& ! array_key_exists( 'terminal_force_overwrite', $current_lease )
			) {
				$processing_snapshot = ai4seo_get_raw_option_snapshot( $queue_options['processing'] );

				if ( null !== $processing_snapshot && ai4seo_renew_post_id_option_transition_semaphore() ) {
					$processing_lookup = array_fill_keys(
						ai4seo_normalize_option_post_id_collection( $processing_snapshot['value'] ),
						true
					);

					if ( isset( $processing_lookup[ $post_id ] ) ) {
						$current_lease['expires_at'] = time() + ai4seo_get_processing_claim_lease_ttl_seconds();
						$lease_predicate_matched     = false;
						$operation_succeeded         = ai4seo_mutate_processing_claim_lease_under_lock(
							$queue_options['processing'],
							$post_id,
							$current_lease,
							$claim_token,
							$lease_predicate_matched
						) && $lease_predicate_matched;
					}
				}
			}
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	if ( $operation_succeeded && $release_succeeded ) {
		return true;
	}

	if ( function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' ) ) {
		ai4seo_schedule_generation_status_summary_rebuild();
	}

	ai4seo_schedule_bulk_generation_processing_recovery();
	return false;
}


/**
 * Revalidate one queued claim against authoritative eligibility at the accepted in-flight boundary.
 *
 * The exact Processing owner, two identical Settings snapshots, and the current posts-table identity
 * are verified while the shared queue fence is held. A proven disable discards stale queue intent;
 * uncertainty restores the original claim snapshots and schedules repair. Direct editor claims do
 * not use this boundary because persisted automated-generation settings do not govern them.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @param string $claim_token Exact durable lease token.
 * @param bool   $pending_was_present Pending membership captured by the queued claim.
 * @param bool   $force_was_present Force membership captured by the queued claim.
 * @return bool True only when this exact queued claim is authoritatively eligible to become in flight.
 */
function ai4seo_guard_queued_bulk_generation_processing_claim_eligibility(
	string $context,
	int $post_id,
	string $claim_token,
	bool $pending_was_present,
	bool $force_was_present
): bool {
	global $wpdb;

	$context       = sanitize_key( $context );
	$post_id       = ai4seo_normalize_option_post_id( $post_id );
	$claim_token   = trim( $claim_token );
	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

	if (
		false === $post_id
		|| '' === $claim_token
		|| 128 < strlen( $claim_token )
		|| ! isset( $queue_options['processing'] )
		|| ! in_array(
			$context,
			array(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
			),
			true
		)
	) {
		return false;
	}

	$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();
	$operation_succeeded   = false;
	$release_succeeded     = false;
	$is_eligible           = false;

	if ( ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
		return false;
	}

	try {
		$leases = array();

		if ( ai4seo_read_processing_claim_leases_under_lock( $queue_options['processing'], $leases ) ) {
			$current_lease = $leases[ $post_id ] ?? null;

			if (
				is_array( $current_lease )
				&& hash_equals( $claim_token, $current_lease['token'] )
				&& empty( $current_lease['rollback_requested'] )
				&& empty( $current_lease['ambiguous_spend'] )
				&& ! array_key_exists( 'terminal_force_overwrite', $current_lease )
			) {
				$processing_snapshot = ai4seo_get_raw_option_snapshot( $queue_options['processing'] );

				if ( null !== $processing_snapshot && ai4seo_renew_post_id_option_transition_semaphore() ) {
					$processing_lookup = array_fill_keys(
						ai4seo_normalize_option_post_id_collection( $processing_snapshot['value'] ),
						true
					);

					if ( isset( $processing_lookup[ $post_id ] ) ) {
						$settings_read_succeeded  = false;
						$settings_snapshot        = null;
						$enabled_post_types       = ai4seo_read_persisted_enabled_bulk_generation_post_types(
							$settings_read_succeeded,
							$settings_snapshot
						);
						$enabled_metadata_lookup  = array_fill_keys(
							array_values( array_diff( $enabled_post_types, array( 'attachment' ) ) ),
							true
						);
						$relevant_context_enabled = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context
							? ! empty( $enabled_metadata_lookup )
							: in_array( 'attachment', $enabled_post_types, true );
						$source_read_succeeded    = $settings_read_succeeded && ! $relevant_context_enabled;
						$source_exists            = false;
						$source_post_type         = '';
						$source_post_mime_type    = '';

						if ( $settings_read_succeeded && $relevant_context_enabled ) {
							$source_query = ai4seo_prepare_database_query(
								'SELECT ID, post_type, post_mime_type FROM {{posts_table}} WHERE ID = {{post_id}} LIMIT 1',
								array(
									'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
									'post_id'     => ai4seo_database_scalar_binding( '%d', $post_id ),
								)
							);

							if ( false !== $source_query ) {
								$wpdb->last_error = '';

								// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler owns this exact primary-key eligibility snapshot at the paid-request boundary.
								$source_row     = $wpdb->get_row( $source_query, ARRAY_A );
								$database_error = (string) $wpdb->last_error;

								if ( '' === $database_error && null === $source_row ) {
									$source_read_succeeded = true;
								} elseif (
									'' === $database_error
									&& is_array( $source_row )
									&& 3 === count( $source_row )
									&& ai4seo_normalize_database_id( $source_row['ID'] ?? null ) === $post_id
									&& is_string( $source_row['post_type'] ?? null )
									&& '' !== $source_row['post_type']
									&& sanitize_key( $source_row['post_type'] ) === $source_row['post_type']
									&& is_string( $source_row['post_mime_type'] ?? null )
									&& wp_check_invalid_utf8( $source_row['post_mime_type'] ) === $source_row['post_mime_type']
									&& false === strpos( $source_row['post_mime_type'], "\0" )
								) {
									$source_read_succeeded = true;
									$source_exists         = true;
									$source_post_type      = $source_row['post_type'];
									$source_post_mime_type = $source_row['post_mime_type'];
								}
							}
						}

						$current_settings_read_succeeded = false;
						$current_settings_snapshot       = null;
						ai4seo_read_persisted_enabled_bulk_generation_post_types(
							$current_settings_read_succeeded,
							$current_settings_snapshot
						);
						$settings_snapshot_stable = $settings_read_succeeded
							&& $current_settings_read_succeeded
							&& is_array( $settings_snapshot )
							&& is_array( $current_settings_snapshot )
							&& $current_settings_snapshot['exists'] === $settings_snapshot['exists']
							&& $current_settings_snapshot['option_id'] === $settings_snapshot['option_id']
							&& $current_settings_snapshot['option_name'] === $settings_snapshot['option_name']
							&& $current_settings_snapshot['autoload'] === $settings_snapshot['autoload']
							&& hash_equals( $settings_snapshot['raw_value'], $current_settings_snapshot['raw_value'] );

						if ( $settings_snapshot_stable && $source_read_succeeded ) {
							$taxonomy_read_succeeded = true;
							if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context ) {
								$is_eligible = $source_exists && isset( $enabled_metadata_lookup[ $source_post_type ] );
								if ( $is_eligible && AI4SEO_TAXONOMY_PROXY_POST_TYPE === $source_post_type ) {
									$archive                 = ai4seo_get_taxonomy_generation_eligibility( $post_id, 'automatic', ai4seo_decode_settings_snapshot( $current_settings_snapshot ) );
									$is_eligible             = ! is_wp_error( $archive );
									$taxonomy_read_succeeded = ! is_wp_error( $archive ) || in_array( $archive->get_error_code(), array( 'taxonomy_generation_disabled', 'taxonomy_generation_unavailable', 'taxonomy_content_short', 'taxonomy_language_excluded' ), true );
								}
							} else {
								$is_eligible = $relevant_context_enabled
									&& $source_exists
									&& in_array( $source_post_type, ai4seo_get_supported_attachment_post_types( false ), true )
									&& in_array( $source_post_mime_type, ai4seo_get_enabled_attachment_mime_types( $current_settings_snapshot ), true );
							}

							$current_lease['expires_at'] = time() + ai4seo_get_processing_claim_lease_ttl_seconds();
							$lease_predicate_matched     = false;
							$operation_succeeded         = $taxonomy_read_succeeded && ai4seo_mutate_processing_claim_lease_under_lock(
								$queue_options['processing'],
								$post_id,
								$current_lease,
								$claim_token,
								$lease_predicate_matched
							) && $lease_predicate_matched;
						}
					}
				}
			}
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	if ( ! $operation_succeeded || ! $release_succeeded ) {
		ai4seo_abort_bulk_generation_processing_claim_before_generation(
			$context,
			$post_id,
			$claim_token,
			$pending_was_present,
			$force_was_present
		);
		ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
		return false;
	}

	if ( $is_eligible ) {
		return true;
	}

	if ( ! ai4seo_mark_registered_bulk_generation_processing_claim_for_queue_intent_discard(
		$context,
		$post_id,
		$claim_token
	) ) {
		ai4seo_abort_bulk_generation_processing_claim_before_generation(
			$context,
			$post_id,
			$claim_token,
			$pending_was_present,
			$force_was_present
		);
		ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
		return false;
	}

	if ( ai4seo_release_bulk_generation_processing_claim( $context, $post_id, $claim_token, false, false, true ) ) {
		ai4seo_unregister_bulk_generation_processing_claim( $context, $post_id );
		return false;
	}

	ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
	return false;
}


/**
 * Persist one exact claim's recovery snapshots while Processing remains authoritative.
 *
 * An explicit ambiguous-spend marker with false/false snapshots, written immediately before a billable
 * generation request, is the conservative crash boundary. Generic no-restore snapshots remain distinct.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @param string $claim_token Exact durable lease token.
 * @param bool   $pending_was_present Pending membership recovery must restore.
 * @param bool   $force_was_present Force membership recovery must restore.
 * @param bool   $rollback_requested Whether cleanup may begin immediately rather than after expiry.
 * @param bool   $ambiguous_spend Whether a RobHub request may have been accepted or billed.
 * @return bool Whether the exact durable snapshots and Processing membership were verified.
 */
function ai4seo_persist_bulk_generation_processing_claim_queue_intent_snapshot(
	string $context,
	int $post_id,
	string $claim_token,
	bool $pending_was_present,
	bool $force_was_present,
	bool $rollback_requested = false,
	bool $ambiguous_spend = false
): bool {
	$context       = sanitize_key( $context );
	$post_id       = ai4seo_normalize_option_post_id( $post_id );
	$claim_token   = trim( $claim_token );
	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

	if (
		false === $post_id
		|| '' === $claim_token
		|| 128 < strlen( $claim_token )
		|| ! isset( $queue_options['processing'] )
		|| ( $ambiguous_spend && ( $pending_was_present || $force_was_present || $rollback_requested ) )
	) {
		return false;
	}

	$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();
	$operation_succeeded   = false;
	$release_succeeded     = false;

	if ( ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		ai4seo_schedule_bulk_generation_processing_recovery();
		return false;
	}

	try {
		$leases = array();

		if ( ai4seo_read_processing_claim_leases_under_lock( $queue_options['processing'], $leases ) ) {
			$current_lease       = $leases[ $post_id ] ?? null;
			$processing_snapshot = ai4seo_get_raw_option_snapshot( $queue_options['processing'] );
			$processing_lookup   = null === $processing_snapshot
				? array()
				: array_fill_keys(
					ai4seo_normalize_option_post_id_collection( $processing_snapshot['value'] ),
					true
				);

			if (
				is_array( $current_lease )
				&& hash_equals( $claim_token, $current_lease['token'] )
				&& ! array_key_exists( 'terminal_force_overwrite', $current_lease )
				&& ( $rollback_requested || empty( $current_lease['rollback_requested'] ) )
				&& ( $ambiguous_spend || empty( $current_lease['ambiguous_spend'] ) )
				&& null !== $processing_snapshot
				&& isset( $processing_lookup[ $post_id ] )
				&& ai4seo_renew_post_id_option_transition_semaphore()
			) {
				$current_lease['expires_at']          = time() + ai4seo_get_processing_claim_lease_ttl_seconds();
				$current_lease['pending_was_present'] = $pending_was_present;
				$current_lease['force_was_present']   = $force_was_present;

				if ( $rollback_requested ) {
					$current_lease['rollback_requested'] = true;
				} else {
					unset( $current_lease['rollback_requested'], $current_lease['preserve_new_queue_memberships'] );
				}

				if ( $ambiguous_spend ) {
					$current_lease['ambiguous_spend'] = true;
				} else {
					unset( $current_lease['ambiguous_spend'] );
				}

				unset(
					$current_lease['terminal_force_overwrite'],
					$current_lease['terminal_destination_option_names'],
					$current_lease['terminal_removal_option_names']
				);

				$lease_predicate_matched = false;
				$intent_persisted        = ai4seo_mutate_processing_claim_lease_under_lock(
					$queue_options['processing'],
					$post_id,
					$current_lease,
					$claim_token,
					$lease_predicate_matched
				) && $lease_predicate_matched;

				if ( $intent_persisted ) {
					$verified_leases = array();

					if ( ai4seo_read_processing_claim_leases_under_lock( $queue_options['processing'], $verified_leases ) ) {
						$verified_lease               = $verified_leases[ $post_id ] ?? null;
						$verified_processing_snapshot = ai4seo_get_raw_option_snapshot( $queue_options['processing'] );
						$verified_processing_lookup   = null === $verified_processing_snapshot
							? array()
							: array_fill_keys(
								ai4seo_normalize_option_post_id_collection( $verified_processing_snapshot['value'] ),
								true
							);

						$rollback_intent_verified = is_array( $verified_lease )
							&& ( $rollback_requested
								? ! empty( $verified_lease['rollback_requested'] )
								: ! array_key_exists( 'rollback_requested', $verified_lease ) );
						$ambiguous_spend_verified = is_array( $verified_lease )
							&& ( $ambiguous_spend
								? ! empty( $verified_lease['ambiguous_spend'] )
								: ! array_key_exists( 'ambiguous_spend', $verified_lease ) );
						$operation_succeeded      = is_array( $verified_lease )
							&& hash_equals( $claim_token, $verified_lease['token'] )
							&& $rollback_intent_verified
							&& $ambiguous_spend_verified
							&& ( $verified_lease['pending_was_present'] ?? null ) === $pending_was_present
							&& ( $verified_lease['force_was_present'] ?? null ) === $force_was_present
							&& ! array_key_exists( 'terminal_force_overwrite', $verified_lease )
							&& null !== $verified_processing_snapshot
							&& isset( $verified_processing_lookup[ $post_id ] )
							&& ai4seo_renew_post_id_option_transition_semaphore();
					}
				}
			}
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	if ( $operation_succeeded && $release_succeeded ) {
		return true;
	}

	ai4seo_schedule_bulk_generation_processing_recovery();
	return false;
}


/**
 * Persist the conservative crash marker immediately before a billable RobHub generation request.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @param string $claim_token Exact durable lease token.
 * @return bool Whether the exact ambiguous-spend marker was verified.
 */
function ai4seo_persist_bulk_generation_processing_claim_ambiguous_spend(
	string $context,
	int $post_id,
	string $claim_token
): bool {
	return ai4seo_persist_bulk_generation_processing_claim_queue_intent_snapshot(
		$context,
		$post_id,
		$claim_token,
		false,
		false,
		false,
		true
	);
}


/**
 * Release one exact durable Processing owner and resolve its authoritative queue intent.
 *
 * This API does not require the request-local cron registry. Its desired Pending and Force state is
 * persisted in the lease before Processing can be removed, allowing later recovery to finish either
 * an exact restoration or an intentional discard without touching a replacement token.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @param string $claim_token Exact durable lease token.
 * @param bool   $pending_was_present Whether Pending existed in the claim's fenced snapshot.
 * @param bool   $force_was_present Whether Force existed in the claim's fenced snapshot.
 * @param bool   $discard_prior_queue_intent Whether recovery must remove rather than restore Pending/Force.
 * @return bool True only when the exact rollback and lease removal were verified.
 */
function ai4seo_release_bulk_generation_processing_claim(
	string $context,
	int $post_id,
	string $claim_token,
	bool $pending_was_present,
	bool $force_was_present,
	bool $discard_prior_queue_intent = false
): bool {
	$context       = sanitize_key( $context );
	$post_id       = ai4seo_normalize_option_post_id( $post_id );
	$claim_token   = trim( $claim_token );
	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

	if (
		false === $post_id
		|| '' === $claim_token
		|| 128 < strlen( $claim_token )
		|| ! isset( $queue_options['pending'], $queue_options['processing'], $queue_options['failed'], $queue_options['force_overwrite'] )
	) {
		return false;
	}

	$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();
	$operation_succeeded   = false;
	$release_succeeded     = false;

	if ( ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		ai4seo_schedule_bulk_generation_processing_recovery();
		return false;
	}

	try {
		$leases = array();

		if ( ai4seo_read_processing_claim_leases_under_lock( $queue_options['processing'], $leases ) ) {
			$current_lease       = $leases[ $post_id ] ?? null;
			$has_ambiguous_spend = false;

			if ( $discard_prior_queue_intent ) {
				$pending_was_present = false;
				$force_was_present   = false;
			}

			if ( ! is_array( $current_lease ) || ! hash_equals( $claim_token, $current_lease['token'] ) ) {
				$current_lease = null;
			} elseif ( ! $discard_prior_queue_intent && ! empty( $current_lease['ambiguous_spend'] ) ) {
				// A possibly billed request may only resolve through discard/tombstone or terminal publication.
				$current_lease = null;
			} elseif (
				! $discard_prior_queue_intent
				&& array_key_exists( 'pending_was_present', $current_lease )
				&& (
					$pending_was_present !== $current_lease['pending_was_present']
					|| $force_was_present !== $current_lease['force_was_present']
				)
			) {
				$current_lease = null;
			}

			if ( is_array( $current_lease ) ) {
				$has_ambiguous_spend = ! empty( $current_lease['ambiguous_spend'] );
				$processing_snapshot = ai4seo_get_raw_option_snapshot( $queue_options['processing'] );
				$processing_lookup   = null === $processing_snapshot
					? array()
					: array_fill_keys(
						ai4seo_normalize_option_post_id_collection( $processing_snapshot['value'] ),
						true
					);

				if (
					null === $processing_snapshot
					|| ( ! isset( $processing_lookup[ $post_id ] ) && empty( $current_lease['rollback_requested'] ) )
				) {
					$current_lease = null;
				}
			}

			if ( is_array( $current_lease ) ) {
				$current_lease['expires_at']          = time() + ai4seo_get_processing_claim_lease_ttl_seconds();
				$current_lease['pending_was_present'] = $pending_was_present;
				$current_lease['force_was_present']   = $force_was_present;

				if ( $discard_prior_queue_intent ) {
					// Only the separately validated ambiguous-spend marker controls the billing tombstone.
					unset( $current_lease['rollback_requested'], $current_lease['preserve_new_queue_memberships'] );
				} else {
					$current_lease['rollback_requested'] = true;
				}

				unset(
					$current_lease['terminal_force_overwrite'],
					$current_lease['terminal_destination_option_names'],
					$current_lease['terminal_removal_option_names']
				);

				$lease_predicate_matched = false;
				$lease_intent_persisted  = ai4seo_mutate_processing_claim_lease_under_lock(
					$queue_options['processing'],
					$post_id,
					$current_lease,
					$claim_token,
					$lease_predicate_matched
				) && $lease_predicate_matched;

				if ( $lease_intent_persisted ) {
					$rollback_additions = $pending_was_present
						? array( $queue_options['pending'] => array( $post_id ) )
						: array();
					$rollback_removals  = array( $queue_options['processing'] => array( $post_id ) );

					if ( $has_ambiguous_spend ) {
						// Failed is a conservative billing tombstone until authoritative reconciliation completes.
						$rollback_additions[ $queue_options['failed'] ] = array( $post_id );
					}

					if ( $force_was_present ) {
						$rollback_additions[ $queue_options['force_overwrite'] ] = array( $post_id );
					} else {
						$rollback_removals[ $queue_options['force_overwrite'] ] = array( $post_id );
					}

					if ( ! $pending_was_present ) {
						$rollback_removals[ $queue_options['pending'] ] = array( $post_id );
					}

					$rollback_did_change = false;
					$operation_succeeded = ai4seo_apply_normalized_post_id_option_transition_under_lock(
						$rollback_additions,
						$rollback_removals,
						$rollback_did_change
					);
				}

				if ( $operation_succeeded ) {
					$lease_predicate_matched = false;
					$operation_succeeded     = ai4seo_mutate_processing_claim_lease_under_lock(
						$queue_options['processing'],
						$post_id,
						null,
						$claim_token,
						$lease_predicate_matched
					) && $lease_predicate_matched;
				}
			}
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	if ( $operation_succeeded && $release_succeeded ) {
		return true;
	}

	if ( function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' ) ) {
		ai4seo_schedule_generation_status_summary_rebuild();
	}

	ai4seo_schedule_bulk_generation_processing_recovery();
	return false;
}


/**
 * Compensate a durable claim published before its final fence checkpoint failed.
 *
 * No generation read, API request, or primary write has occurred when callers use this path, so the
 * lease's exact Pending and Force snapshots remain authoritative. Registering before the immediate
 * release also gives fatal/timeout shutdown recovery the same token; an incomplete release is retried
 * immediately through the durable recovery transaction.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @param string $claim_token Exact durable lease token.
 * @param bool   $pending_was_present Whether Pending existed in the claim's fenced snapshot.
 * @param bool   $force_was_present Whether Force existed in the claim's fenced snapshot.
 * @return bool Whether exact rollback completed or ownership had already transferred.
 */
function ai4seo_compensate_bulk_generation_processing_claim_after_failed_check(
	string $context,
	int $post_id,
	string $claim_token,
	bool $pending_was_present,
	bool $force_was_present
): bool {
	$claim = array(
		'context'     => sanitize_key( $context ),
		'post_id'     => absint( $post_id ),
		'claim_token' => trim( $claim_token ),
	);

	ai4seo_register_bulk_generation_processing_claim(
		$claim['context'],
		$claim['post_id'],
		$claim['claim_token']
	);

	if ( ai4seo_release_bulk_generation_processing_claim(
		$claim['context'],
		$claim['post_id'],
		$claim['claim_token'],
		$pending_was_present,
		$force_was_present
	) ) {
		ai4seo_unregister_bulk_generation_processing_claim( $claim['context'], $claim['post_id'] );
		return true;
	}

	return ai4seo_recover_registered_bulk_generation_processing_claim( $claim );
}


/**
 * Abort one exact claim before generation when authoritative input state could not be read.
 *
 * The original fenced Pending and Force memberships are restored before the worker returns, and a
 * prompt durable retry is requested. A failed exact release remains registered for shutdown recovery.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @param string $claim_token Exact durable lease token.
 * @param bool   $pending_was_present Whether Pending existed in the claim's fenced snapshot.
 * @param bool   $force_was_present Whether Force existed in the claim's fenced snapshot.
 * @return bool Whether exact rollback and retry scheduling were verified.
 */
function ai4seo_abort_bulk_generation_processing_claim_before_generation(
	string $context,
	int $post_id,
	string $claim_token,
	bool $pending_was_present,
	bool $force_was_present
): bool {
	if ( ! ai4seo_release_bulk_generation_processing_claim(
		$context,
		$post_id,
		$claim_token,
		$pending_was_present,
		$force_was_present
	) ) {
		return false;
	}

	ai4seo_unregister_bulk_generation_processing_claim( $context, $post_id );
	return ai4seo_schedule_bulk_generation_processing_recovery();
}


/**
 * Release one exact cron claim after a primary write may have committed without verification.
 *
 * Ambiguous storage must never restore stale Pending/Force intent because the requested primary value
 * may already be durable. The explicit pre-spend marker retains its rollback-free false/false shape;
 * cleanup publishes a conservative Failed tombstone while the summary rebuild reconciles exact
 * authoritative coverage. Auto Queue admission and generic manual discards never carry that marker.
 *
 * @param string $context Bulk generation context.
 * @param int    $post_id Post ID.
 * @param string $claim_token Exact durable lease token.
 * @return bool Whether exact claim recovery completed immediately.
 */
function ai4seo_release_bulk_generation_processing_claim_after_ambiguous_primary_write(
	string $context,
	int $post_id,
	string $claim_token
): bool {
	// The worker verified the durable no-restore snapshot before spend. Keep a request-local equivalent
	// so shutdown recovery has the same policy if scheduling or exact release is interrupted.
	ai4seo_mark_registered_bulk_generation_processing_claim_for_queue_intent_discard(
		$context,
		$post_id,
		$claim_token
	);

	$rebuild_scheduled = function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' )
		&& ai4seo_schedule_generation_status_summary_rebuild();

	// Never discard the last queue pointer until a durable reconciliation request is authoritative.
	if ( ! $rebuild_scheduled ) {
		ai4seo_schedule_bulk_generation_processing_recovery();
		return false;
	}

	$release_succeeded = ai4seo_release_bulk_generation_processing_claim(
		$context,
		$post_id,
		$claim_token,
		false,
		false,
		true
	);

	if ( $release_succeeded ) {
		ai4seo_unregister_bulk_generation_processing_claim( $context, $post_id );
	}

	$recovery_scheduled = ai4seo_schedule_bulk_generation_processing_recovery();
	return $release_succeeded && $rebuild_scheduled && $recovery_scheduled;
}


/**
 * Verify that every destination recorded by a partial terminal transition remains authoritative.
 *
 * Processing is removed only after all additions are verified by the transition primitive. A reset
 * can nevertheless remove Processing and then fail before removing its lease, so durable recovery
 * rechecks the recorded destinations rather than inferring completion from Processing absence alone.
 * A removal-only terminal transition has no destinations, but still renews the shared fence before replay.
 *
 * @param array $lease Strictly validated durable lease.
 * @param int   $post_id Post ID.
 * @return bool Whether every recorded terminal destination currently contains the post ID.
 */
function ai4seo_verify_processing_claim_terminal_destinations_under_lock( array $lease, int $post_id ): bool {
	$destination_option_names = $lease['terminal_destination_option_names'] ?? null;
	$post_id                  = ai4seo_normalize_option_post_id( $post_id );

	if ( false === $post_id || ! is_array( $destination_option_names ) ) {
		return false;
	}

	foreach ( $destination_option_names as $destination_option_name ) {
		if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}

		$destination_snapshot = ai4seo_get_raw_option_snapshot( $destination_option_name );

		if ( null === $destination_snapshot ) {
			return false;
		}

		$destination_lookup = array_fill_keys(
			ai4seo_normalize_option_post_id_collection( $destination_snapshot['value'] ),
			true
		);

		if ( ! isset( $destination_lookup[ $post_id ] ) ) {
			return false;
		}
	}

	return ai4seo_renew_post_id_option_transition_semaphore();
}


/**
 * Finish one strictly validated terminal claim transaction while the shared fence is held.
 *
 * Every recorded destination must already be authoritative before cleanup begins. Removal-only
 * terminal state has no destinations to preverify. Replaying the complete recorded state verifies
 * its exact same-context removals, including Processing and the recorded terminal Force disposition.
 *
 * @param string $processing_option_name Processing option that owns the lease context.
 * @param array  $lease Strict terminal-intent lease.
 * @param int    $post_id Post ID.
 * @return bool Whether the complete recorded terminal state was verified.
 */
function ai4seo_finish_processing_claim_terminal_transition_under_lock(
	string $processing_option_name,
	array $lease,
	int $post_id
): bool {
	$post_id          = ai4seo_normalize_option_post_id( $post_id );
	$validated_leases = array();

	if (
		false === $post_id
		|| ! ai4seo_validate_processing_claim_leases( array( $post_id => $lease ), $validated_leases )
		|| ! ai4seo_validate_processing_claim_lease_context( $processing_option_name, $validated_leases )
		|| ! isset( $validated_leases[ $post_id ]['terminal_force_overwrite'] )
	) {
		return false;
	}

	$lease = $validated_leases[ $post_id ];

	if ( ! ai4seo_verify_processing_claim_terminal_destinations_under_lock( $lease, $post_id ) ) {
		return false;
	}

	$terminal_additions = array();
	$terminal_removals  = array();

	foreach ( $lease['terminal_destination_option_names'] as $destination_option_name ) {
		$terminal_additions[ $destination_option_name ] = array( $post_id );
	}

	foreach ( $lease['terminal_removal_option_names'] as $removal_option_name ) {
		$terminal_removals[ $removal_option_name ] = array( $post_id );
	}

	$terminal_did_change = false;
	return ai4seo_apply_normalized_post_id_option_transition_under_lock(
		$terminal_additions,
		$terminal_removals,
		$terminal_did_change
	);
}


/**
 * Requeue one exact request-owned Processing claim after a fatal/timeout or early return.
 *
 * @param array $claim Request-local claim record.
 * @return bool Whether ownership was released or was already transferred.
 */
function ai4seo_recover_registered_bulk_generation_processing_claim( array $claim ): bool {
	$context                    = sanitize_key( $claim['context'] ?? '' );
	$post_id                    = absint( $claim['post_id'] ?? 0 );
	$claim_token                = is_string( $claim['claim_token'] ?? null ) ? $claim['claim_token'] : '';
	$queue_options              = ai4seo_get_bulk_generation_queue_options_by_context( $context );
	$discard_prior_queue_intent = true === ( $claim['discard_prior_queue_intent'] ?? false );

	if ( ! $post_id || '' === $claim_token || ! isset( $queue_options['pending'], $queue_options['processing'], $queue_options['failed'], $queue_options['force_overwrite'] ) ) {
		return false;
	}

	if ( $discard_prior_queue_intent
		&& (
			! function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' )
			|| ! ai4seo_schedule_generation_status_summary_rebuild()
		)
	) {
		ai4seo_schedule_bulk_generation_processing_recovery();
		return false;
	}

	$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();
	$operation_succeeded   = false;
	$release_succeeded     = false;
	$ownership_transferred = false;

	if ( ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		if ( function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' ) ) {
			ai4seo_schedule_generation_status_summary_rebuild();
		}

		ai4seo_schedule_bulk_generation_processing_recovery();
		return false;
	}

	try {
		$leases = array();

		if ( ! ai4seo_read_processing_claim_leases_under_lock( $queue_options['processing'], $leases ) ) {
			$operation_succeeded = false;
		} else {
			$current_lease = $leases[ $post_id ] ?? null;

			if ( ! is_array( $current_lease ) || ! hash_equals( $claim_token, $current_lease['token'] ) ) {
				// A replacement token is authoritative; this request must never touch its Processing generation.
				$ownership_transferred = true;
				$operation_succeeded   = true;
			} else {
				$processing_snapshot = ai4seo_get_raw_option_snapshot( $queue_options['processing'] );

				if ( null === $processing_snapshot || ! ai4seo_renew_post_id_option_transition_semaphore() ) {
					$operation_succeeded = false;
				} else {
					$processing_lookup = array_fill_keys(
						ai4seo_normalize_option_post_id_collection( $processing_snapshot['value'] ),
						true
					);

					$has_processing_membership          = isset( $processing_lookup[ $post_id ] );
					$has_terminal_intent                = array_key_exists( 'terminal_force_overwrite', $current_lease );
					$has_rollback_intent                = ! empty( $current_lease['rollback_requested'] )
						&& array_key_exists( 'pending_was_present', $current_lease )
						&& array_key_exists( 'force_was_present', $current_lease );
					$has_durable_no_restore_snapshot    = array_key_exists( 'pending_was_present', $current_lease )
						&& array_key_exists( 'force_was_present', $current_lease )
						&& false === $current_lease['pending_was_present']
						&& false === $current_lease['force_was_present'];
					$finish_terminal_cleanup            = ! $discard_prior_queue_intent && $has_terminal_intent;
					$requires_ambiguous_spend_tombstone = ! empty( $current_lease['ambiguous_spend'] )
						&& ! $has_terminal_intent;
					$has_scrub_rollback_marker          = ! empty( $current_lease['preserve_new_queue_memberships'] );

					if ( $has_scrub_rollback_marker ) {
						// Disabled-entry scrub owners are never request-local Processing claims.
						$operation_succeeded = false;
					} elseif ( $has_durable_no_restore_snapshot
						&& (
							! function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' )
							|| ! ai4seo_schedule_generation_status_summary_rebuild()
						) ) {
						// Never remove the last durable no-restore pointer without an authoritative repair marker.
						$operation_succeeded = false;
					} elseif (
						$finish_terminal_cleanup
						&& ! ai4seo_finish_processing_claim_terminal_transition_under_lock(
							$queue_options['processing'],
							$current_lease,
							$post_id
						)
					) {
						$operation_succeeded = false;
					} elseif ( $finish_terminal_cleanup ) {
						$operation_succeeded = true;
					} elseif ( $has_processing_membership || $has_rollback_intent || $requires_ambiguous_spend_tombstone ) {
						$rollback_intent_persisted = true;

						if ( $has_processing_membership || $requires_ambiguous_spend_tombstone ) {
							if ( $discard_prior_queue_intent ) {
								$current_lease['pending_was_present'] = false;
								$current_lease['force_was_present']   = false;
							} else {
								$current_lease['pending_was_present'] = array_key_exists( 'pending_was_present', $current_lease )
									? $current_lease['pending_was_present']
									: true;
								$current_lease['force_was_present']   = array_key_exists( 'force_was_present', $current_lease )
									? $current_lease['force_was_present']
									: ! empty( $current_lease['force_overwrite'] );
							}

							if ( $requires_ambiguous_spend_tombstone ) {
								// Preserve the explicit marker's valid no-rollback shape across an interrupted recovery.
								unset( $current_lease['rollback_requested'], $current_lease['preserve_new_queue_memberships'] );
							} else {
								$current_lease['rollback_requested'] = true;
							}

							unset(
								$current_lease['terminal_force_overwrite'],
								$current_lease['terminal_destination_option_names'],
								$current_lease['terminal_removal_option_names']
							);
							$lease_predicate_matched   = false;
							$rollback_intent_persisted = ai4seo_mutate_processing_claim_lease_under_lock(
								$queue_options['processing'],
								$post_id,
								$current_lease,
								$claim_token,
								$lease_predicate_matched
							) && $lease_predicate_matched;
							$has_rollback_intent       = $rollback_intent_persisted && ! $requires_ambiguous_spend_tombstone;
						}

						if ( ! $rollback_intent_persisted ) {
							$operation_succeeded = false;
						} elseif ( $requires_ambiguous_spend_tombstone ) {
							// A possibly billed request must not resurrect the generation request that produced it.
							$restore_pending = false;
							$restore_force   = false;
						} else {
							$restore_pending = $has_rollback_intent ? $current_lease['pending_was_present'] : true;
							$restore_force   = $has_rollback_intent
								? $current_lease['force_was_present']
								: ! empty( $current_lease['force_overwrite'] );
						}

						if ( $rollback_intent_persisted ) {
							$recovery_additions = $restore_pending
								? array( $queue_options['pending'] => array( $post_id ) )
								: array();
							$recovery_removals  = array( $queue_options['processing'] => array( $post_id ) );

							if ( $requires_ambiguous_spend_tombstone ) {
								$recovery_additions[ $queue_options['failed'] ] = array( $post_id );
							}

							if ( ! $restore_pending ) {
								$recovery_removals[ $queue_options['pending'] ] = array( $post_id );
							}

							if ( $restore_force ) {
								$recovery_additions[ $queue_options['force_overwrite'] ] = array( $post_id );
							} else {
								$recovery_removals[ $queue_options['force_overwrite'] ] = array( $post_id );
							}

							$recovery_did_change = false;
							$operation_succeeded = ai4seo_apply_normalized_post_id_option_transition_under_lock(
								$recovery_additions,
								$recovery_removals,
								$recovery_did_change
							);
						}
					} else {
						// A legacy terminal state is already visible; only its exact orphaned lease remains.
						$operation_succeeded = true;
					}
				}

				if ( $operation_succeeded ) {
					$lease_predicate_matched = false;
					$operation_succeeded     = ai4seo_mutate_processing_claim_lease_under_lock(
						$queue_options['processing'],
						$post_id,
						null,
						$claim_token,
						$lease_predicate_matched
					) && $lease_predicate_matched;
				}
			}
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	if ( $operation_succeeded && $release_succeeded ) {
		ai4seo_unregister_bulk_generation_processing_claim( $context, $post_id );

		if ( ! $ownership_transferred ) {
			ai4seo_schedule_bulk_generation_processing_recovery();
		}

		return true;
	}

	if ( function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' ) ) {
		ai4seo_schedule_generation_status_summary_rebuild();
	}

	ai4seo_schedule_bulk_generation_processing_recovery();
	return false;
}


/**
 * Recover every request-local claim that did not reach a verified terminal transition.
 *
 * @return void
 */
function ai4seo_recover_bulk_generation_processing_claims_on_shutdown(): void {
	$claims = $GLOBALS['ai4seo_bulk_generation_processing_claims'] ?? array();

	if ( ! is_array( $claims ) ) {
		return;
	}

	foreach ( $claims as $claim ) {
		if ( is_array( $claim ) ) {
			ai4seo_recover_registered_bulk_generation_processing_claim( $claim );
		}
	}
}


/**
 * Requeue bounded expired/missing Processing leases before admitting more work.
 *
 * @param array $contexts Bulk generation contexts.
 * @return bool True when every bounded repair was verified.
 */
function ai4seo_recover_stale_bulk_generation_processing_claims( array $contexts ): bool {
	$contexts              = array_values( array_unique( array_map( 'sanitize_key', $contexts ) ) );
	$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();
	$operation_succeeded   = true;
	$release_succeeded     = false;
	$did_requeue           = false;
	$more_repairs_remain   = false;
	$repair_limit          = 25;

	if ( ! $contexts || ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		return false;
	}

	try {
		foreach ( $contexts as $context ) {
			$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

			if ( ! isset( $queue_options['pending'], $queue_options['processing'], $queue_options['failed'], $queue_options['force_overwrite'] ) ) {
				$operation_succeeded = false;
				break;
			}

			if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
				$operation_succeeded = false;
				break;
			}

			$processing_snapshot = ai4seo_get_raw_option_snapshot( $queue_options['processing'] );
			$force_snapshot      = ai4seo_get_raw_option_snapshot( $queue_options['force_overwrite'] );
			$leases              = array();

			if (
				null === $processing_snapshot
				|| null === $force_snapshot
				|| ! ai4seo_read_processing_claim_leases_under_lock( $queue_options['processing'], $leases )
			) {
				$operation_succeeded = false;
				break;
			}

			$processing_post_ids = ai4seo_normalize_option_post_id_collection( $processing_snapshot['value'] );
			$processing_lookup   = array_fill_keys( $processing_post_ids, true );
			$force_lookup        = array_fill_keys(
				ai4seo_normalize_option_post_id_collection( $force_snapshot['value'] ),
				true
			);
			$repair_post_ids     = array();

			foreach ( $processing_post_ids as $processing_post_id ) {
				$current_lease = $leases[ $processing_post_id ] ?? null;

				if (
					! is_array( $current_lease )
					|| ! empty( $current_lease['rollback_requested'] )
					|| array_key_exists( 'terminal_force_overwrite', $current_lease )
					|| ! ai4seo_is_processing_claim_lease_active( $current_lease )
				) {
					$repair_post_ids[] = $processing_post_id;
				}
			}

			if ( count( $repair_post_ids ) > $repair_limit ) {
				$more_repairs_remain = true;
				$repair_post_ids     = array_slice( $repair_post_ids, 0, $repair_limit );
			}

			foreach ( $repair_post_ids as $repair_post_id ) {
				$current_lease = $leases[ $repair_post_id ] ?? null;

				if ( is_array( $current_lease ) && ! empty( $current_lease['preserve_new_queue_memberships'] ) ) {
					// Scrub rollback owners are orphan-only; Processing membership is an unverifiable collision.
					$operation_succeeded = false;
					break 2;
				}

				$has_durable_no_restore_snapshot    = is_array( $current_lease )
					&& array_key_exists( 'pending_was_present', $current_lease )
					&& array_key_exists( 'force_was_present', $current_lease )
					&& false === $current_lease['pending_was_present']
					&& false === $current_lease['force_was_present'];
				$requires_ambiguous_spend_tombstone = is_array( $current_lease )
					&& ! empty( $current_lease['ambiguous_spend'] )
					&& ! array_key_exists( 'terminal_force_overwrite', $current_lease );

				if ( $has_durable_no_restore_snapshot
					&& (
						! function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' )
						|| ! ai4seo_schedule_generation_status_summary_rebuild()
					) ) {
					$operation_succeeded = false;
					break 2;
				}

				// A published terminal transaction is authoritative even while Processing remains.
				if ( is_array( $current_lease ) && array_key_exists( 'terminal_force_overwrite', $current_lease ) ) {
					if ( ! ai4seo_finish_processing_claim_terminal_transition_under_lock(
						$queue_options['processing'],
						$current_lease,
						$repair_post_id
					) ) {
						$operation_succeeded = false;
						break 2;
					}

					$lease_predicate_matched = false;

					if (
						! ai4seo_mutate_processing_claim_lease_under_lock(
							$queue_options['processing'],
							$repair_post_id,
							null,
							$current_lease['token'],
							$lease_predicate_matched
						)
						|| ! $lease_predicate_matched
					) {
						$operation_succeeded = false;
						break 2;
					}

					unset( $leases[ $repair_post_id ] );
					continue;
				}

				$restore_pending = ! is_array( $current_lease )
					|| ! array_key_exists( 'pending_was_present', $current_lease )
					|| $current_lease['pending_was_present'];
				$restore_force   = is_array( $current_lease ) && array_key_exists( 'force_was_present', $current_lease )
					? $current_lease['force_was_present']
					: ( is_array( $current_lease )
						? ! empty( $current_lease['force_overwrite'] )
						: isset( $force_lookup[ $repair_post_id ] ) );

				if ( is_array( $current_lease ) ) {
					$current_lease['pending_was_present'] = $restore_pending;
					$current_lease['force_was_present']   = $restore_force;

					if ( $requires_ambiguous_spend_tombstone ) {
						unset( $current_lease['rollback_requested'], $current_lease['preserve_new_queue_memberships'] );
					} else {
						$current_lease['rollback_requested'] = true;
					}

					unset(
						$current_lease['terminal_force_overwrite'],
						$current_lease['terminal_destination_option_names'],
						$current_lease['terminal_removal_option_names']
					);

					$lease_predicate_matched = false;

					if (
						! ai4seo_mutate_processing_claim_lease_under_lock(
							$queue_options['processing'],
							$repair_post_id,
							$current_lease,
							$current_lease['token'],
							$lease_predicate_matched
						)
						|| ! $lease_predicate_matched
					) {
						$operation_succeeded = false;
						break 2;
					}

					$leases[ $repair_post_id ] = $current_lease;
				}

				$repair_additions = $restore_pending
					? array( $queue_options['pending'] => array( $repair_post_id ) )
					: array();
				$repair_removals  = array( $queue_options['processing'] => array( $repair_post_id ) );

				if ( $requires_ambiguous_spend_tombstone ) {
					$repair_additions[ $queue_options['failed'] ] = array( $repair_post_id );
				}

				if ( ! $restore_pending ) {
					$repair_removals[ $queue_options['pending'] ] = array( $repair_post_id );
				}

				if ( $restore_force ) {
					$repair_additions[ $queue_options['force_overwrite'] ] = array( $repair_post_id );
				} else {
					$repair_removals[ $queue_options['force_overwrite'] ] = array( $repair_post_id );
				}

				$repair_did_change = false;

				if (
					! ai4seo_apply_normalized_post_id_option_transition_under_lock(
						$repair_additions,
						$repair_removals,
						$repair_did_change
					)
				) {
					$operation_succeeded = false;
					break 2;
				}

				if ( is_array( $current_lease ) ) {
					$lease_predicate_matched = false;

					if (
						! ai4seo_mutate_processing_claim_lease_under_lock(
							$queue_options['processing'],
							$repair_post_id,
							null,
							$current_lease['token'],
							$lease_predicate_matched
						)
						|| ! $lease_predicate_matched
					) {
						$operation_succeeded = false;
						break 2;
					}
				}

				$did_requeue = true;
			}

			$remaining_cleanup_budget = max( 0, $repair_limit - count( $repair_post_ids ) );
			$orphan_lease_post_ids    = array();

			foreach ( array_keys( $leases ) as $lease_post_id ) {
				if ( ! isset( $processing_lookup[ $lease_post_id ] ) ) {
					$orphan_lease_post_ids[] = $lease_post_id;
				}
			}

			if ( count( $orphan_lease_post_ids ) > $remaining_cleanup_budget ) {
				$more_repairs_remain   = true;
				$orphan_lease_post_ids = array_slice( $orphan_lease_post_ids, 0, $remaining_cleanup_budget );
			}

			foreach ( $orphan_lease_post_ids as $lease_post_id ) {
				$orphan_lease                       = $leases[ $lease_post_id ];
				$has_terminal_intent                = array_key_exists( 'terminal_force_overwrite', $orphan_lease );
				$has_rollback_intent                = ! empty( $orphan_lease['rollback_requested'] )
					&& array_key_exists( 'pending_was_present', $orphan_lease )
					&& array_key_exists( 'force_was_present', $orphan_lease );
				$has_durable_no_restore_snapshot    = array_key_exists( 'pending_was_present', $orphan_lease )
					&& array_key_exists( 'force_was_present', $orphan_lease )
					&& false === $orphan_lease['pending_was_present']
					&& false === $orphan_lease['force_was_present'];
				$requires_ambiguous_spend_tombstone = ! empty( $orphan_lease['ambiguous_spend'] )
					&& ! $has_terminal_intent;
				$preserve_new_queue_memberships     = ! empty( $orphan_lease['preserve_new_queue_memberships'] );

				if ( $has_durable_no_restore_snapshot
					&& (
						! function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' )
						|| ! ai4seo_schedule_generation_status_summary_rebuild()
					) ) {
					$operation_succeeded = false;
					break 2;
				}

				if ( $has_terminal_intent ) {
					if ( ! ai4seo_finish_processing_claim_terminal_transition_under_lock(
						$queue_options['processing'],
						$orphan_lease,
						$lease_post_id
					) ) {
						$operation_succeeded = false;
						break 2;
					}
				} elseif ( $requires_ambiguous_spend_tombstone ) {
					$orphan_did_change = false;

					if ( ! ai4seo_apply_normalized_post_id_option_transition_under_lock(
						array( $queue_options['failed'] => array( $lease_post_id ) ),
						array(
							$queue_options['pending']    => array( $lease_post_id ),
							$queue_options['processing'] => array( $lease_post_id ),
							$queue_options['force_overwrite'] => array( $lease_post_id ),
						),
						$orphan_did_change
					) ) {
						$operation_succeeded = false;
						break 2;
					}
				} elseif ( $has_rollback_intent ) {
					$restore_pending = $orphan_lease['pending_was_present'];
					$restore_force   = $orphan_lease['force_was_present'];

					$orphan_additions = $restore_pending
						? array( $queue_options['pending'] => array( $lease_post_id ) )
						: array();
					$orphan_removals  = array( $queue_options['processing'] => array( $lease_post_id ) );

					if ( ! $restore_pending && ! $preserve_new_queue_memberships ) {
						$orphan_removals[ $queue_options['pending'] ] = array( $lease_post_id );
					}

					if ( $restore_force ) {
						$orphan_additions[ $queue_options['force_overwrite'] ] = array( $lease_post_id );
					} elseif ( ! $preserve_new_queue_memberships ) {
						$orphan_removals[ $queue_options['force_overwrite'] ] = array( $lease_post_id );
					}

					$orphan_did_change = false;

					if (
						! ai4seo_apply_normalized_post_id_option_transition_under_lock(
							$orphan_additions,
							$orphan_removals,
							$orphan_did_change
						)
					) {
						$operation_succeeded = false;
						break 2;
					}
				}

				$lease_predicate_matched = false;

				if (
					! ai4seo_mutate_processing_claim_lease_under_lock(
						$queue_options['processing'],
						$lease_post_id,
						null,
						$orphan_lease['token'],
						$lease_predicate_matched
					)
					|| ! $lease_predicate_matched
				) {
					$operation_succeeded = false;
					break 2;
				}
			}
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	if ( ! $operation_succeeded || ! $release_succeeded ) {
		if ( function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' ) ) {
			ai4seo_schedule_generation_status_summary_rebuild();
		}

		ai4seo_schedule_bulk_generation_processing_recovery();
		return false;
	}

	if ( $did_requeue || $more_repairs_remain ) {
		ai4seo_schedule_bulk_generation_processing_recovery();
	}

	return true;
}

/**
 * Stop one automated-generation queue after an account-scoped terminal billing error.
 *
 * Pending entries and their paired Force markers belong to the affected queue context. Processing
 * entries remain owned by their exact workers and must not be inferred to belong to this cleanup.
 *
 * @param string $context Bulk generation queue context.
 * @return bool True when the affected Pending snapshot was cleared and the paused status persisted.
 */
function ai4seo_stop_automated_generation_after_terminal_billing_error( string $context ): bool {
	// Remove only pending work owned by the affected generation context.
	$pending_cleanup_succeeded = ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
		array( $context )
	);

	// Persist a paused status only after cleanup succeeds; otherwise expose the cleanup failure.
	$cron_status                  = $pending_cleanup_succeeded ? 'low-credits-balance' : 'finished-with-error';
	$cron_status_update_succeeded = ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, $cron_status );

	// Release the request-local coordinator flag before returning from either terminal path.
	$GLOBALS['ai4seo_is_running_automated_generation_cron_job'] = false;

	return $pending_cleanup_succeeded && $cron_status_update_succeeded;
}


// CRONJOB: ai4seo_automated_generation_cron_job() ==============================================================.

/**
 * Function to automatically generate data for different kind of contexts
 *
 * @param bool $debug The debug value.
 * @return bool true on success, false on failure
 */
function ai4seo_automated_generation_cron_job( $debug = false ): bool {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return true;
	}
	$lock = ai4seo_get_background_job_lock_name( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
	if ( ! ai4seo_acquire_background_job_lock( $lock ) ) {
		return false;
	}
	try {
		$result = ai4seo_run_automated_generation_cron_job_under_lock( $debug, $lock );
	} finally {
		$GLOBALS['ai4seo_is_running_automated_generation_cron_job'] = false;
		$released = ai4seo_release_background_job_lock( $lock );
	}
	return $result && $released;
}

/**
 * Execute the coordinator with verified ownership independent of display timestamps.
 *
 * @param bool   $debug Diagnostic mode.
 * @param string $lock Owned coordinator lock.
 * @return bool Whether the run and its final state were verified.
 */
function ai4seo_run_automated_generation_cron_job_under_lock( bool $debug, string $lock ): bool {
	if ( ai4seo_get_background_job_lock_name( AI4SEO_BULK_GENERATION_CRON_JOB_NAME ) !== $lock || ! ai4seo_renew_background_job_lock( $lock ) ) {
		return false;
	}

	// is the cron job enabled?
	if ( ! AI4SEO_CRON_JOBS_ENABLED && wp_doing_cron() ) {
		return true;
	}

	$bulk_generation_duration = (int) ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_DURATION );

	if ( ! $bulk_generation_duration ) {
		$bulk_generation_duration = AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_BULK_GENERATION_DURATION ];
	}

	$ready_contexts               = array();
	$persisted_enabled_post_types = array();
	foreach ( ai4seo_get_bulk_generation_queue_contexts() as $context ) {
		$readiness = ai4seo_get_bulk_generation_context_readiness( $context );
		if ( 'system_error' === $readiness['status'] ) {
			if ( in_array( 'active_fields', $readiness['blocked_sources'], true ) ) {
				ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
			}
			return false;
		}
		if ( 'ready' !== $readiness['status'] ) {
			continue;
		}
		// Only a verifiably independent context may cross recovery and disabled-type reconciliation.
		if ( ! ai4seo_recover_stale_bulk_generation_processing_claims( array( $context ) )
			|| ! ai4seo_reconcile_disabled_bulk_generation_queue_entries( $persisted_enabled_post_types, array( $context ) ) ) {
			return false;
		}
		$ready_contexts[] = $context;
	}
	if ( ! $ready_contexts ) {
		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'data-blocked' );
		return false;
	}

	$max_execution_time              = $debug ? 20 : ( $bulk_generation_duration - 5 );
	$approximate_single_run_duration = 10;
	$max_tolerated_execution_time    = $debug ? 25 : ( $bulk_generation_duration + 10 );
	$max_runs                        = $debug ? 3 : round( $bulk_generation_duration / 3 );
	$metadata_credits_cost_per_post  = ai4seo_calculate_metadata_credits_cost_per_post();
	$attachment_attributes_credits_costs_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();
	$min_credits_cost_per_entry                              = min( $metadata_credits_cost_per_post, $attachment_attributes_credits_costs_per_attachment_post );

	// set the maximum execution time according to these functions needs.
	ai4seo_safe_set_time_limit( $max_tolerated_execution_time + 30 );

	// define the start time of this cron job function call.
	$start_time = time();
	// Ownership and durable per-post claims authorize work; stale display markers do not.
	if ( ! ai4seo_renew_background_job_lock( $lock ) || ! ai4seo_publish_background_job_state( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'initiating', $start_time ) ) {
		return false;
	}

	if ( ! $persisted_enabled_post_types ) {
		if ( $debug ) {
			ai4seo_debug_message( 277251009, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because every automated generation is disabled' ) ) );
		}

		return ai4seo_publish_background_job_state( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'bulk-generation-disabled' );
	}

	// Refresh background notifications during the 5-minute bulk generation cron.
	ai4seo_check_for_new_notifications();

	// CHECK USERS ROBHUB ACCOUNT.
	$account_readiness        = ai4seo_maybe_refresh_account_state();
	$is_robhub_account_synced = $account_readiness['credits_ready'];

	if ( ! $is_robhub_account_synced ) {
		if ( $debug ) {
			ai4seo_debug_message( 284451160, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Robhub account not synced -> skip' ) ) );
		}

		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'finished-with-error' );
		return false;
	}

	// check if credentials are set.
	if ( ! ai4seo_robhub_api()->check_credentials() ) {
		if ( $debug ) {
			ai4seo_debug_message( 755826516, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'auth failed -> skip' ) ) );
		}

		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'finished-with-error' );
		return false;
	}

	// check the current credits balance, compare it to $min_credits_cost_per_entry and if it's lower, return true.
	if ( $account_readiness['credits'] < $min_credits_cost_per_entry ) {
		if ( $debug ) {
			ai4seo_debug_message( 458928065, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because of low Credits balance' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
			),
			$min_credits_cost_per_entry
		) ) {
			ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'finished-with-error' );
			return false;
		}

		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'low-credits-balance' );
		return true;
	}

	// A legacy completed marker can still need recovery; use the same bounded continuation as the dashboard.
	if ( ai4seo_maybe_schedule_required_generation_status_summary_rebuild( true )
		&& 'idle' !== ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE, false ) ) {
		ai4seo_rebuild_generation_status_summary();
	}

	$run_counter     = 1;
	$has_data_blocks = count( $ready_contexts ) < count( ai4seo_get_bulk_generation_queue_contexts() );
	$GLOBALS['ai4seo_is_running_automated_generation_cron_job']    = true;
	$GLOBALS['ai4seo_deep_context_search_statement_timeout_count'] = 0;

	do {
		$made_some_progress = false;

		if ( $debug ) {
			ai4seo_debug_message( 635240362, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( "start new run: #{$run_counter}" ) ) );
		}

		if ( ! ai4seo_renew_background_job_lock( $lock )
			|| ! ai4seo_publish_background_job_state( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'processing' ) ) {
			return false;
		}

		// Process metadata and keyphrases before the independent attachment queue.
		$metadata_terminal_billing_error = false;
		$metadata_attempt                = array();
		$success                         = in_array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, $ready_contexts, true ) && ai4seo_automated_metadata_generation(
			$debug,
			0,
			$metadata_terminal_billing_error,
			$metadata_attempt
		);

		if ( $metadata_terminal_billing_error ) {
			// Prevent the attachment worker from spending against the same exhausted account.
			return ai4seo_stop_automated_generation_after_terminal_billing_error(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA
			);
		}

		if ( $success ) {
			$made_some_progress = true;
		}
		$has_data_blocks = $has_data_blocks || ! empty( $metadata_attempt['blocked_post_ids'] ) || in_array( $metadata_attempt['status'] ?? '', array( 'context_blocked', 'filter_blocked', 'data_blocked' ), true );
		if ( 'system_error' === ( $metadata_attempt['status'] ?? '' ) ) {
			$GLOBALS['ai4seo_is_running_automated_generation_cron_job'] = false;
			ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'finished-with-error' );
			return false;
		}

		// Process attachment attributes only after metadata leaves the account usable.
		if ( ! ai4seo_renew_background_job_lock( $lock ) ) {
			return false;
		}
		$attachment_terminal_billing_error = false;
		$attachment_attempt                = array();
		$success                           = in_array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES, $ready_contexts, true ) && ai4seo_automated_attachment_attributes_generation(
			$debug,
			0,
			$attachment_terminal_billing_error,
			$attachment_attempt
		);

		if ( $attachment_terminal_billing_error ) {
			// Stop before the coordinator can sleep or schedule another immediate pass.
			return ai4seo_stop_automated_generation_after_terminal_billing_error(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES
			);
		}
		$has_data_blocks = $has_data_blocks || ! empty( $attachment_attempt['blocked_post_ids'] ) || in_array( $attachment_attempt['status'] ?? '', array( 'context_blocked', 'filter_blocked', 'data_blocked' ), true );
		if ( 'system_error' === ( $attachment_attempt['status'] ?? '' ) ) {
			$GLOBALS['ai4seo_is_running_automated_generation_cron_job'] = false;
			ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'finished-with-error' );
			return false;
		}

		if ( $success ) {
			$made_some_progress = true;
		}

		if ( $made_some_progress ) {
			sleep( 3 );
			++$run_counter;
		} else {
			break;
		}
	} while (
		$made_some_progress &&
		time() - $start_time < $max_execution_time - $approximate_single_run_duration &&
		$run_counter <= $max_runs
	);

	// reschedule this cronjob asap, so that the next posts can be filled shortly.
	if ( ! ai4seo_renew_background_job_lock( $lock ) ) {
		return false;
	}
	if ( $made_some_progress ) {
		if ( ! ai4seo_publish_background_job_state( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'finished' ) ) {
			return false;
		}
		ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
	} elseif ( ! ai4seo_publish_background_job_state( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, $has_data_blocks ? 'data-blocked' : 'idle' ) ) {
		return false;
	}

	$GLOBALS['ai4seo_is_running_automated_generation_cron_job'] = false;

	return true;
}


/**
 * Function to automatically generate metadata for posts
 *
 * @param bool       $debug The debug value.
 * @param int        $only_this_post_id The only this post id value.
 * @param bool       $terminal_billing_error Receives whether an account-scoped terminal billing error stopped generation.
 * @param array|null $attempt Receives the processing outcome and bounded blocked entry IDs.
 * @return bool true on success, false on failure
 */
function ai4seo_automated_metadata_generation(
	$debug = false,
	$only_this_post_id = 0,
	bool &$terminal_billing_error = false,
	?array &$attempt = null
): bool {
	return ai4seo_run_bulk_generation_attempts( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, 'ai4seo_attempt_metadata_generation', $debug, $only_this_post_id, $terminal_billing_error, $attempt );
}

/**
 * Attempt one metadata owner; the public worker bounds iteration over isolated failures.
 *
 * @param bool  $debug Debug output.
 * @param int   $only_this_post_id Direct generation ID, or zero for queued work.
 * @param bool  $terminal_billing_error Account-scoped stop output.
 * @param array $attempt Structured result output.
 * @param array $excluded_post_ids Already inspected IDs in this pass.
 * @return bool Verified progress.
 */
function ai4seo_attempt_metadata_generation(
	$debug,
	$only_this_post_id,
	bool &$terminal_billing_error,
	array &$attempt,
	array $excluded_post_ids
): bool {
	// Clear the output before any early return so callers never observe a prior invocation's state.
	$terminal_billing_error = false;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 374991526, 'Prevented loop', true );
		return false;
	}

	$persisted_enabled_post_types = array();

	// A direct editor generation may proceed, but no queued work can cross an unverifiable disabled-type barrier.
	if ( ! ai4seo_reconcile_disabled_bulk_generation_queue_entries( $persisted_enabled_post_types, array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ) ) ) {
		return false;
	}

	$enabled_metadata_post_types = array_values( array_diff( $persisted_enabled_post_types, array( 'attachment' ) ) );

	if ( ! $only_this_post_id && ! $enabled_metadata_post_types ) {
		$attempt['status'] = 'empty';
		return false;
	}

	$active_meta_tags = ai4seo_get_active_meta_tags();

	if ( ! $active_meta_tags ) {
		$attempt['status'] = 'empty';
		if ( $debug ) {
			ai4seo_debug_message( 979105658, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no active meta tags found -> skip' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ),
			null,
			true
		) ) {
			$attempt['status'] = 'system_error';
			return false;
		}

		return false;
	}

	$metadata_credits_costs_per_post = ai4seo_calculate_metadata_credits_cost_per_post();
	$account_readiness               = ai4seo_maybe_refresh_account_state();
	if ( ! $account_readiness['credits_ready'] ) {
		$attempt['status'] = 'account_unavailable';
		return false;
	}

	// check the current credits balance, compare it to $metadata_credits_costs_per_post and if it's lower, return true.
	if ( $account_readiness['credits'] < $metadata_credits_costs_per_post ) {
		if ( $debug ) {
			ai4seo_debug_message( 275318901, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because of low Credits balance' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ),
			$metadata_credits_costs_per_post
		) ) {
			$attempt['status'] = 'system_error';
			return false;
		}
		$attempt['status'] = 'insufficient_credits';
		return false;
	}

	// handle one single post id, if given, otherwise excavate new posts with missing metadata.
	if ( $only_this_post_id ) {
		$post_id = $only_this_post_id;
	} else {
		$auto_queue_entries = ai4seo_should_auto_queue_bulk_generation_entries();
		$pending_post_ids   = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );

		if ( $auto_queue_entries && ! $pending_post_ids ) {
			// try to search for posts with missing metadata.
			$queue_barrier_failed  = false;
			$got_new_pending_posts = ai4seo_excavate_post_entries_with_missing_metadata( $debug, $queue_barrier_failed, $selection );

			// An unverifiable admission must remain pending for a later run, never be consumed by this request.
			if ( $queue_barrier_failed ) {
				$attempt['status'] = $selection['status'];
				if ( $debug ) {
					ai4seo_debug_message( 513505111, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Auto Queue admission barrier failed -> retry later' ) ) );
				}

				return false;
			}

			$pending_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );

			// Existing manual queue entries should still be processed when automatic excavation finds nothing new.
			if ( ! $got_new_pending_posts && ! $pending_post_ids ) {
				$attempt['status'] = 'empty';
				if ( $debug ) {
					ai4seo_debug_message( 513505109, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No new pending posts found' ) ) );
				}

				// Remove Pending entries while preserving Processing work owned by another request.
				if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
					array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA )
				) ) {
					$attempt['status'] = 'system_error';
					return false;
				}
				return false;
			}
		} elseif ( $debug ) {
			ai4seo_debug_message( 513505110, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Auto queue entries disabled -> using existing pending posts only' ) ) );
		}

		if ( ! isset( $pending_post_ids ) ) {
			$pending_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );
		}

		if ( ! $pending_post_ids ) {
			$attempt['status'] = 'empty';
			// skip here because we don't have any posts or pages.
			if ( $debug ) {
				ai4seo_debug_message( 872810398, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No pending posts found' ) ) );
			}

			if ( $auto_queue_entries && ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
				array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA )
			) ) {
				$attempt['status'] = 'system_error';
				return false;
			}

			return false;
		}

		if ( $debug ) {
			ai4seo_debug_message( 809859626, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Found pending post(s): ' . esc_html( implode( ', ', $pending_post_ids ) ) ) ) );
		}

		$pending_post_ids = array_values( array_diff( $pending_post_ids, $excluded_post_ids ) );
		if ( ! $pending_post_ids ) {
			$attempt['status'] = 'empty';
			return false;
		}
		$post_id = reset( $pending_post_ids );
	}

	// make sure every entry is numeric.
	if ( ! is_numeric( $post_id ) || ! $post_id ) {
		if ( $debug ) {
			ai4seo_debug_message( 940723212, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'post-id is not numeric or not set' ) ) );
		}

		return false;
	}

	$post_id                                        = absint( $post_id );
	$attempt['post_id']                             = $post_id;
	$processing_claimed                             = false;
	$is_force_overwrite_bulk_generation_queue_entry = false;
	$processing_claim_token                         = '';
	$pending_was_present                            = false;
	$force_was_present                              = false;

	if ( ! $only_this_post_id ) {
		$pre_claim_enabled_post_types = array();

		// Close settings changes during excavation before the selected queue entry can become Processing.
		if ( ! ai4seo_reconcile_disabled_bulk_generation_queue_entries( $pre_claim_enabled_post_types, array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ) )
			|| ! array_values( array_diff( $pre_claim_enabled_post_types, array( 'attachment' ) ) ) ) {
			return false;
		}
	}

	// Own the exact generation before Force mode or mutable post state can be observed.
	if ( $only_this_post_id ) {
		$processing_claim_checked = ai4seo_claim_post_id_for_direct_processing(
			AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
			AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
			$post_id,
			$processing_claimed,
			AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
			$is_force_overwrite_bulk_generation_queue_entry,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present
		);
	} else {
		$processing_claim_checked = ai4seo_claim_pending_post_id_for_processing(
			AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
			AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
			$post_id,
			$processing_claimed,
			AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
			$is_force_overwrite_bulk_generation_queue_entry,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present
		);
	}

	$processing_claim_succeeded = $processing_claim_checked && $processing_claimed;

	if ( ! $processing_claim_succeeded ) {
		if ( $processing_claim_checked && ! $processing_claimed ) {
			$attempt['status'] = 'temporarily_busy';
		}
		if ( ! $processing_claim_checked && $processing_claimed && '' !== $processing_claim_token ) {
			ai4seo_compensate_bulk_generation_processing_claim_after_failed_check(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
				$post_id,
				$processing_claim_token,
				$pending_was_present,
				$force_was_present
			);
		}

		if ( $debug ) {
			ai4seo_debug_message( 641476225, esc_html( __FUNCTION__ ) . ' > Could not exclusively claim the metadata queue entry.', true );
		}

		return false;
	}

	if ( ! ai4seo_register_bulk_generation_processing_claim(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
		$post_id,
		$processing_claim_token
	) ) {
		ai4seo_recover_registered_bulk_generation_processing_claim(
			array(
				'context'     => AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
				'post_id'     => $post_id,
				'claim_token' => $processing_claim_token,
			)
		);
		return false;
	}

	if ( $debug ) {
		ai4seo_debug_message( 439931261, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'trying to generate metadata for #' . esc_html( $post_id ) ) ) );
	}

	// Resolve active storage before any billable generation request. One failed entry does not stop the queue.
	$active_storage_view = $only_this_post_id ? ai4seo_recover_active_metadata( $post_id ) : ai4seo_read_active_metadata_resolved_view( $post_id );
	if ( 'unreadable' === $active_storage_view['classification']
		|| ( ! $only_this_post_id && ! in_array( $active_storage_view['classification'], array( 'single', 'missing' ), true ) ) ) {
		return ai4seo_finish_blocked_bulk_generation_attempt(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			$post_id,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present,
			! in_array( $active_storage_view['reason'], array( 'storage_read_failed', 'storage_read_exception' ), true ),
			__( 'Stored metadata needs attention. The queued entry is retained; no generation was requested.', 'ai-for-seo' ),
			$attempt
		);
	}
	if ( $active_storage_view['conflicts'] ) {
		$reason = 'Stored metadata has conflicting values; resolve them in the metadata editor';
		ai4seo_debug_metadata_recovery( $post_id, 'generation_preflight_skipped', $active_storage_view, array_keys( $active_storage_view['conflicts'] ) );
		$failure_status_succeeded = ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, $reason, $debug, $processing_claim_token );
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, $reason );
		return $failure_status_succeeded;
	}

	// let's find fields to generate for this post id.
	$generated_data_read_succeeded = false;
	$history_failure_reason        = '';
	$generated_data_snapshot       = ai4seo_read_authoritative_generated_data_postmeta_snapshot(
		$post_id,
		$generated_data_read_succeeded,
		$history_failure_reason
	);
	$generated_data_details        = $generated_data_snapshot['generated_data_details'];
	if ( ! $generated_data_read_succeeded ) {
		return ai4seo_finish_blocked_bulk_generation_attempt(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			$post_id,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present,
			'damaged_history' === $history_failure_reason,
			__( 'Generation history needs attention. The queued entry is retained; no generation was requested.', 'ai-for-seo' ),
			$attempt
		);
	}
	$available_metadata_read_succeeded = false;
	$metadata_issues                   = array();
	$storage_views                     = array();
	$metadata_by_id                    = ai4seo_read_available_metadata_by_post_ids(
		array( $post_id ),
		true,
		$available_metadata_read_succeeded,
		$storage_views,
		false,
		$metadata_issues
	);
	$old_available_metadata            = $metadata_by_id[ $post_id ] ?? array();

	// Never spend credits or derive a terminal status from an unavailable/malformed primary snapshot.
	if ( ! $available_metadata_read_succeeded || $metadata_issues ) {
		return ai4seo_finish_blocked_bulk_generation_attempt(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			$post_id,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present,
			$available_metadata_read_succeeded && ! empty( $metadata_issues ),
			__( 'Metadata sources need attention. The queued entry is retained; no generation was requested.', 'ai-for-seo' ),
			$attempt
		);
	}

	$generate_this_fields        = $active_meta_tags;
	$old_generated_metadata      = $generated_data_details['generated_data'];
	$overwrite_existing_metadata = ai4seo_get_setting( AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA );
	$focus_keyphrase_behavior    = ai4seo_get_setting( AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA );

	if ( ! is_array( $overwrite_existing_metadata ) ) {
		$overwrite_existing_metadata = array();
	}

	// handle focus keyphrase behavior when existing meta title/description are present (SEO Autopilot)
	// consider both meta title and meta description as not generated, so that we can regenerate them
	// however, if we don't generate the meta title or description for some reason, the focus keyphrase generation will be skipped later.
	if ( in_array( 'focus-keyphrase', $generate_this_fields, true ) && AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE === $focus_keyphrase_behavior ) {
		unset( $old_generated_metadata['meta-title'] );
		unset( $old_generated_metadata['meta-description'] );
	}

	if ( $is_force_overwrite_bulk_generation_queue_entry && $debug ) {
		ai4seo_debug_message( 275768046, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Force overwrite queue marker found -> keeping all active metadata fields' ) ) );
	}

	if ( ! $is_force_overwrite_bulk_generation_queue_entry ) {
		// remove all already generated metadata from the $generate_this_meta_tags array.
		foreach ( $old_generated_metadata as $this_metadata_identifier => $this_metadata_value ) {
			// not available -> skip, despite we generated it before.
			if ( ! isset( $old_available_metadata[ $this_metadata_identifier ] ) || ! $old_available_metadata[ $this_metadata_identifier ] ) {
				continue;
			}

			// already generated -> skip.
			if ( in_array( $this_metadata_identifier, $generate_this_fields, true ) && $this_metadata_value ) {
				$this_index = array_search( $this_metadata_identifier, $generate_this_fields, true );
				unset( $generate_this_fields[ $this_index ] );
			}
		}

		// nothing left to generate -> skip.
		if ( ! $generate_this_fields ) {
			if ( $debug ) {
				ai4seo_debug_message( 594533434, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no missing metadata found for post-id' ) ) );
			}

			// All metadata is already generated; commit the terminal state as one checked transition.
			return ai4seo_apply_bulk_generation_result_transition(
				$post_id,
				array( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME )
			);
		}
	}

	// check for available metadata (from 3rd party seo plugins)
	// and remove meta tags from the missing metadata array that are already available, if we don't want to overwrite them.
	$is_post_fully_covered = true;

	if ( ! $is_force_overwrite_bulk_generation_queue_entry ) {
		foreach ( $generate_this_fields as $this_entry_index => $this_metadata_identifier ) {
			if ( isset( $old_available_metadata[ $this_metadata_identifier ] )
				&& $old_available_metadata[ $this_metadata_identifier ]
				&& ! in_array( $this_metadata_identifier, $overwrite_existing_metadata, true ) ) {
				unset( $generate_this_fields[ $this_entry_index ] );
				continue;
			}

			if ( ! isset( $old_available_metadata[ $this_metadata_identifier ] ) || ! $old_available_metadata[ $this_metadata_identifier ] ) {
				$is_post_fully_covered = false;
			}
		}

		// if we skip or regenerate the focus keyphrase, but neither meta title nor meta description is in the generation list, we should also skip the focus keyphrase generation.
		if ( ( AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP === $focus_keyphrase_behavior || AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE === $focus_keyphrase_behavior )
			&& in_array( 'focus-keyphrase', $generate_this_fields, true )
			&& ! in_array( 'meta-title', $generate_this_fields, true )
			&& ! in_array( 'meta-description', $generate_this_fields, true ) ) {
			unset( $generate_this_fields[ array_search( 'focus-keyphrase', $generate_this_fields, true ) ] );
		}

		// nothing left to generate -> skip.
		if ( ! $generate_this_fields ) {
			if ( $debug ) {
				ai4seo_debug_message( 777726188, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no missing metadata found for post-id' ) ) );
			}

			// All metadata is already generated; commit the terminal state as one checked transition.
			return ai4seo_apply_bulk_generation_result_transition(
				$post_id,
				array( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME )
			);
		}

		// make sure to abort, if we have full coverage and don't want to generate metadata for fully covered entries.
		$generate_metadata_for_fully_covered_entries = ai4seo_do_generate_metadata_for_fully_covered_entries();

		if ( $is_post_fully_covered && ! $generate_metadata_for_fully_covered_entries ) {
			if ( $debug ) {
				ai4seo_debug_message( 245485518, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'full metadata coverage found and generation for fully covered entries is disabled -> skip' ) ) );
			}

			return ai4seo_apply_bulk_generation_result_transition(
				$post_id,
				array( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME )
			);
		}
	}

	// First, get the shared transport content, post context, and structured language evidence.
	$content_source_read_succeeded = false;
	$content_source_post_exists    = false;
	$content_failure_reason        = '';
	$prepared_content              = ai4seo_prepare_metadata_generation_content_data(
		$post_id,
		'',
		$content_source_read_succeeded,
		$content_source_post_exists,
		$content_failure_reason
	);

	if ( ! $content_source_read_succeeded ) {
		return ai4seo_finish_blocked_bulk_generation_attempt(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			$post_id,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present,
			'damaged_source' === $content_failure_reason,
			__( 'Content sources need attention. The queued entry is retained; no generation was requested.', 'ai-for-seo' ),
			$attempt
		);
	}

	if ( ! $content_source_post_exists ) {
		$failure_status_succeeded = ai4seo_handle_failed_metadata_generation(
			$post_id,
			__FUNCTION__,
			'Post no longer exists for post ID: ' . $post_id,
			$debug,
			$processing_claim_token
		);
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Post no longer exists' );
		return $failure_status_succeeded;
	}

	if ( ! $prepared_content['content_extraction_succeeded'] ) {
		$failure_status_succeeded = ai4seo_handle_failed_metadata_generation(
			$post_id,
			__FUNCTION__,
			'Post content could not be extracted for post ID: ' . $post_id,
			$debug,
			$processing_claim_token
		);
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Post content could not be read' );
		return $failure_status_succeeded;
	}

	$post_content     = $prepared_content['content'];
	$post_context     = $prepared_content['post_context'];
	$content_analysis = $prepared_content['content_analysis'];

	$post_content = sanitize_text_field( $post_content );
	$post_context = sanitize_text_field( $post_context );

	// Optional URL/type context must not turn a genuinely short authoritative post row into generation input.
	$required_content_length = ai4seo_mb_strlen(
		trim(
			implode(
				' ',
				array(
					$content_analysis['body_text'] ?? '',
					$content_analysis['post_title'] ?? '',
					$content_analysis['excerpt_text'] ?? '',
				)
			)
		)
	);
	$content_length          = ai4seo_mb_strlen( trim( $post_content . ' ' . $post_context ) );

	// Check whether the required posts/builder snapshot has enough local language evidence.
	if ( ai4seo_is_taxonomy_proxy( $post_id ) && empty( $prepared_content['has_sufficient_content'] ) ) {
		return ai4seo_apply_bulk_generation_result_transition( $post_id, array( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME ), $processing_claim_token );
	}
	if ( ! ai4seo_is_taxonomy_proxy( $post_id ) && $required_content_length < AI4SEO_TOO_SHORT_CONTENT_LENGTH ) {
		$failure_status_succeeded = ai4seo_handle_failed_metadata_generation(
			$post_id,
			__FUNCTION__,
			'Post content is too short for post ID: ' . $post_id,
			$debug,
			$processing_claim_token
		);
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Post content is too short' );
		return $failure_status_succeeded;
	}

	// check if content is not larger than AI4SEO_MAX_TOTAL_CONTENT_SIZE characters.
	if ( $content_length > AI4SEO_MAX_TOTAL_CONTENT_SIZE ) {
		$failure_status_succeeded = ai4seo_handle_failed_metadata_generation(
			$post_id,
			__FUNCTION__,
			'Post content is too long for post ID: ' . $post_id,
			$debug,
			$processing_claim_token
		);
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Post content is too long' );
		return $failure_status_succeeded;
	}

	// here we put our new generated data.
	$metadata_generation_language = ai4seo_get_posts_language( $post_id );
	$metadata_generation_language = sanitize_text_field( $metadata_generation_language );

	$robhub_api_call_parameters = array(
		'content'  => $post_content,
		'language' => $metadata_generation_language,
	);

	// check for a key phrase.
	$third_party_keyphrase = sanitize_text_field( ai4seo_get_any_third_party_seo_plugin_keyphrase( $post_id ) );

	if ( '' !== $third_party_keyphrase ) {
		$robhub_api_call_parameters['keyphrase'] = $third_party_keyphrase;
	}

	$robhub_api_call_parameters['trigger']          = 'automated';
	$robhub_api_call_parameters['website_context']  = ai4seo_get_website_context();
	$robhub_api_call_parameters['post_context']     = $post_context;
	$robhub_api_call_parameters['content_analysis'] = $content_analysis;

	// url.
	$post_permalink = $prepared_content['content_url'] ?? get_permalink( $post_id );

	if ( $post_permalink ) {
		$robhub_api_call_parameters['content_url'] = $post_permalink;
	}

	// collect and build field instructions.
	$field_instructions       = array();
	$metadata_prefixes        = ai4seo_get_setting( AI4SEO_SETTING_METADATA_PREFIXES );
	$metadata_suffixes        = ai4seo_get_setting( AI4SEO_SETTING_METADATA_SUFFIXES );
	$placeholder_replacements = ai4seo_get_metadata_output_placeholder_replacements( $post_id );

	// Reuse the exact required post-row title so placeholder expansion cannot collapse a failed reread.
	$post_title_for_placeholders = sanitize_text_field( $content_analysis['post_title'] ?? '' );

	foreach ( AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_details ) {
		$this_to_generate = in_array( $this_metadata_identifier, $generate_this_fields, true );
		$this_old_value   = $old_available_metadata[ $this_metadata_identifier ] ?? '';
		$this_prefix      = $metadata_prefixes[ $this_metadata_identifier ] ?? '';
		$this_suffix      = $metadata_suffixes[ $this_metadata_identifier ] ?? '';

		if ( ! $this_to_generate && ! $this_old_value ) {
			continue;
		}

		$this_prefix = ai4seo_replace_text_placeholders( $this_prefix, $placeholder_replacements );
		$this_suffix = ai4seo_replace_text_placeholders( $this_suffix, $placeholder_replacements );
		$this_prefix = ai4seo_replace_metadata_title_placeholder(
			$this_prefix,
			$post_title_for_placeholders
		);
		$this_suffix = ai4seo_replace_metadata_title_placeholder(
			$this_suffix,
			$post_title_for_placeholders
		);

		$field_instructions[ $this_metadata_identifier ] = array(
			'generate'  => $this_to_generate,
			'old_value' => $this_old_value,
			'prefix'    => $this_prefix,
			'suffix'    => $this_suffix,
		);
	}

	$robhub_api_call_parameters['approximate_cost']   = ai4seo_calculate_metadata_credits_cost_per_post( $generate_this_fields );
	$robhub_api_call_parameters['field_instructions'] = $field_instructions;

	// SEO Autopilot uses the same instruction collection as manual metadata generation.
	$custom_instructions = ai4seo_get_generation_custom_instructions( 'metadata', $post_id );

	if ( $custom_instructions ) {
		$robhub_api_call_parameters['custom_instructions'] = $custom_instructions;
	}

	// Input preparation can include a remote rendered-content fallback; reverify before spending credits.
	if ( ! ai4seo_renew_bulk_generation_processing_claim(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
		$post_id,
		$processing_claim_token
	) ) {
		return false;
	}

	if ( ! $only_this_post_id && ! ai4seo_guard_queued_bulk_generation_processing_claim_eligibility(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
		$post_id,
		$processing_claim_token,
		$pending_was_present,
		$force_was_present
	) ) {
		return false;
	}

	// Persist no-restore intent before the request can be accepted or billed; an unverified marker fails closed.
	if ( ! ai4seo_persist_bulk_generation_processing_claim_ambiguous_spend(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
		$post_id,
		$processing_claim_token
	) ) {
		return false;
	}

	$taxonomy_generation = null;
	if ( ai4seo_is_taxonomy_proxy( $post_id ) ) {
		$taxonomy_generation = ai4seo_get_taxonomy_generation_snapshot( $post_id, $prepared_content, false );
		if ( is_wp_error( $taxonomy_generation ) ) {
			return ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, 'The archive changed before generation.', $debug, $processing_claim_token );
		}
	}
	$results = ai4seo_robhub_api()->call( 'ai4seo/generate-all-metadata', $robhub_api_call_parameters );

	// Preserve account-scoped stop intent even if ownership changes while the request is in flight.
	$terminal_billing_error = ai4seo_robhub_api()->is_terminal_billing_error_code( $results['code'] ?? null );

	// A reset or replacement worker may have transferred ownership while the API request was in flight.
	if ( ! ai4seo_renew_bulk_generation_processing_claim(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
		$post_id,
		$processing_claim_token
	) ) {
		return false;
	}

	// CHECK RESULTS.

	if ( ! ai4seo_robhub_api()->was_call_successful( $results ) ) {
		$error_message = $results['message'] ?? 'Generation with API endpoint failed for post ID: ' . $post_id;

		if ( isset( $results['code'] ) ) {
			$error_message .= ' (Error #' . sanitize_text_field( $results['code'] ) . ')';
		}

		if ( ! ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, $error_message . ( $debug ? ': ' . ai4seo_stringify( $results ) : '' ), $debug ) ) {
			return false;
		}
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, $error_message );

		ai4seo_debug_message( 4133326, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Generation with API endpoint failed for post ID: ' . $post_id . ': ' . ai4seo_stringify( $results ) ) ), true );
		return false;
	}

	// The API call succeeded; validate its payload before saving any usable metadata fields.

	$raw_new_generated_metadata = $results['data'] ?? array();

	if ( empty( $raw_new_generated_metadata ) || ! is_array( $raw_new_generated_metadata ) ) {
		if ( ! ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, 'Generation with API endpoint failed for post ID: ' . $post_id . ( $debug ? ': ' . ai4seo_stringify( $results ) : '' ), $debug ) ) {
			return false;
		}
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'No data returned from API endpoint' );
		return false;
	}

	// Apply the shared save contract so cron and manual generation filter, cap, and preserve fields identically.
	$prepared_generated_output    = ai4seo_prepare_generated_output_fields_for_save(
		'metadata',
		$raw_new_generated_metadata,
		$generate_this_fields,
		$field_instructions
	);
	$new_generated_metadata       = $prepared_generated_output['values'];
	$unresolved_generation_fields = $prepared_generated_output['unresolved_fields'];

	// Keep usable partial responses successful; fail only when no requested field survives preparation.
	if ( ! $new_generated_metadata ) {
		if ( ! ai4seo_handle_failed_metadata_generation(
			$post_id,
			__FUNCTION__,
			'Generation returned no usable metadata for post ID: ' . $post_id,
			$debug
		) ) {
			return false;
		}
		ai4seo_add_latest_activity_entry(
			$post_id,
			'error',
			'metadata-bulk-generated',
			0,
			'No usable metadata returned from API endpoint'
		);
		return false;
	}

	// Log unresolved identifiers for support while continuing with the usable partial response.
	if ( $unresolved_generation_fields ) {
		ai4seo_debug_message(
			673318905,
			esc_html( __FUNCTION__ ) . ' > Partial metadata response for post ID ' . $post_id
				. '. Unresolved fields: ' . implode( ', ', $unresolved_generation_fields )
		);
	}

	// Update only prepared fields so omitted fields retain their existing stored values.
	$metadata_update_details                   = array();
	$third_party_sync_failure_activity_details = '';

	if ( ! ai4seo_renew_bulk_generation_processing_claim(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
		$post_id,
		$processing_claim_token
	) ) {
		return false;
	}

	$taxonomy_result_locked = false;
	if ( $taxonomy_generation ) {
		$taxonomy_result_locked = ai4seo_lock_taxonomy_generation_result( $post_id, $taxonomy_generation );
		if ( ! $taxonomy_result_locked ) {
			ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', (int) ( $results['credits-consumed'] ?? 0 ), __( 'The archive or stored values changed during generation. The result was not applied; review this entry before trying again.', 'ai-for-seo' ) );
			return ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, 'Archive generation result needs review.', $debug, $processing_claim_token );
		}
	}
	try {
		$metadata_update_succeeded = ai4seo_update_active_metadata(
			$post_id,
			$new_generated_metadata,
			true,
			$metadata_update_details,
			$taxonomy_generation ? array(
				'expected_metadata'          => $taxonomy_generation['values'],
				'taxonomy_generation_source' => $prepared_content['source_fingerprint'],
			) : array()
		);

		// Only a genuine SOOZ failure enters the generation-failure queue and risks a later API retry.
		if ( ! $metadata_update_succeeded ) {
			$active_metadata_succeeded    = ! empty( $metadata_update_details['active_metadata_succeeded'] );
			$third_party_sync_succeeded   = ! empty( $metadata_update_details['third_party_sync_succeeded'] );
			$metadata_update_commit_state = isset( $metadata_update_details['commit_state'] )
				&& is_string( $metadata_update_details['commit_state'] )
				? $metadata_update_details['commit_state']
				: 'possibly_committed';

			// Partial synchronization retains generated data and records failed integrations for support.
			if ( $active_metadata_succeeded && ! $third_party_sync_succeeded ) {
				$failed_plugin_names = ai4seo_get_third_party_seo_plugin_names(
					array_keys( $metadata_update_details['failed_third_party_syncs'] ?? array() )
				);
				$failed_plugin_list  = $failed_plugin_names
					? wp_sprintf_l( '%l', $failed_plugin_names )
					: __( 'selected third-party SEO integrations', 'ai-for-seo' );

				$third_party_sync_failure_activity_details = sprintf(
					/* translators: %s: List of third-party SEO integrations that failed to synchronize. */
					__( 'Generated metadata was saved in SOOZ, but synchronization failed for: %s', 'ai-for-seo' ),
					$failed_plugin_list
				);

				ai4seo_debug_message(
					693318905,
					esc_html( __FUNCTION__ ) . ' > ' . esc_html( $third_party_sync_failure_activity_details ),
					true
				);

				// Continue into provenance, content-summary, coverage, and successful activity persistence below.
			} elseif ( 'not_committed' !== $metadata_update_commit_state ) {
				ai4seo_release_bulk_generation_processing_claim_after_ambiguous_primary_write(
					AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
					$post_id,
					$processing_claim_token
				);
				ai4seo_debug_message( 641476232, 'Generated metadata may have committed; released exact ownership without stale queue restoration and scheduled reconciliation.', true );
				return false;
			} else {
				// A failed SOOZ write retains the existing failure-queue and activity behavior.
				if ( ! ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, 'Could not save generated metadata for post ID: ' . $post_id, $debug ) ) {
					return false;
				}
				ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Could not save generated metadata' );
				return false;
			}
		}

		// Persist returned provenance before coverage changes so a failed snapshot cannot be reported as successful.
		if ( ! ai4seo_renew_bulk_generation_processing_claim(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			$post_id,
			$processing_claim_token
		) ) {
			return false;
		}

		$generated_data_save_details = array();
		$this_success                = ai4seo_save_generated_data_to_postmeta(
			$post_id,
			$new_generated_metadata,
			true,
			0,
			$unresolved_generation_fields,
			$generated_data_save_details,
			$taxonomy_generation ? array( 'taxonomy_generation_source' => $prepared_content['source_fingerprint'] ) : array()
		);

		if ( ! $this_success ) {
			$generated_data_commit_state = isset( $generated_data_save_details['commit_state'] )
				&& is_string( $generated_data_save_details['commit_state'] )
				? $generated_data_save_details['commit_state']
				: 'possibly_committed';

			// Primary metadata already committed, so every provenance failure must discard stale queue intent.
			ai4seo_release_bulk_generation_processing_claim_after_ambiguous_primary_write(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
				$post_id,
				$processing_claim_token
			);

			if ( 'not_committed' === $generated_data_commit_state ) {
				ai4seo_debug_message( 641476227, 'Generated metadata committed without provenance; discarded prior queue intent and scheduled authoritative reconciliation.', true );
				return false;
			}

			ai4seo_debug_message( 641476229, 'Generated metadata provenance may have committed; released exact ownership without stale queue restoration and scheduled reconciliation.', true );
			return false;
		}

		// Save the content snapshot only after generated provenance exists; it controls future change detection.
		if ( ! ai4seo_renew_bulk_generation_processing_claim(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			$post_id,
			$processing_claim_token
		) ) {
			return false;
		}

		$this_success = ai4seo_save_post_content_summary_to_postmeta( $post_id, $post_content );

		if ( ! $this_success ) {
			if ( ! ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, 'Could not save generated metadata content summary for post ID: ' . $post_id, $debug ) ) {
				return false;
			}
			ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Could not save generated metadata content summary' );
			return false;
		}

		// Rebuild exclusive coverage: partial results stay Missing; complete results become Fully Covered and Generated.
		if ( $unresolved_generation_fields ) {
			$result_option_names = array( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME );

			// Preserve force-overwrite intent so omitted existing fields remain eligible on a later queued run.
			if ( $is_force_overwrite_bulk_generation_queue_entry ) {
				$result_option_names[] = AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME;
			}
		} else {
			$result_option_names = array(
				AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
			);
		}

		if ( ! ai4seo_apply_bulk_generation_result_transition( $post_id, $result_option_names ) ) {
			ai4seo_debug_message( 641476221, 'Could not persist the final metadata generation status transition.', true );
			ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Could not persist final generation status' );
			return false;
		}

		// Record returned field identifiers for activity details; unresolved eligibility remains represented by coverage above.
		ai4seo_add_latest_activity_entry(
			$post_id,
			'success',
			'metadata-bulk-generated',
			(int) ( $results['credits-consumed'] ?? 0 ),
			$third_party_sync_failure_activity_details,
			array_keys( $new_generated_metadata )
		);

		if ( $debug ) {
			ai4seo_debug_message( 523529302, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'metadata generated for post ID: ' . $post_id . ': ' . esc_html( ai4seo_stringify( $new_generated_metadata ) ) ) ) );
		}

		return true;
	} finally {
		if ( $taxonomy_result_locked ) {
			ai4seo_release_database_advisory_lock( ai4seo_get_taxonomy_proxy_sync_lock_name() );
		}
	}
}


/**
 * Helps handle failed metadata generation by removing the post id from all generation status options and adding it to the failed ones
 *
 * @param int    $post_id the attachment post id.
 * @param string $function_name the name of the function that failed.
 * @param string $error_message the error message.
 * @param bool   $debug if true, debug information will be printed.
 * @param string $processing_claim_token Optional exact durable claim token.
 * @return bool True only when the failed terminal state was verified.
 */
function ai4seo_handle_failed_metadata_generation(
	int $post_id,
	string $function_name = '',
	string $error_message = '',
	bool $debug = false,
	string $processing_claim_token = ''
): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Preserve existing debug callers.
	if ( $function_name ) {
		ai4seo_debug_message( 585453895, esc_html( $function_name ) . ' >' . esc_html( ai4seo_stringify( $error_message ) ), true );
	} else {
		ai4seo_debug_message( 791605560, $error_message, true );
	}

	$transition_succeeded = ai4seo_apply_bulk_generation_result_transition(
		$post_id,
		array( AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME ),
		$processing_claim_token
	);

	if ( ! $transition_succeeded ) {
		ai4seo_debug_message( 641476222, 'Could not persist the failed metadata generation status transition.', true );
	}

	return $transition_succeeded;
}


/**
 * Function to automatically generate attributes for attachments
 *
 * @param bool       $debug debug mode yes or no.
 * @param int        $only_this_attachment_post_id care only this attachment post id.
 * @param bool       $terminal_billing_error Receives whether an account-scoped terminal billing error stopped generation.
 * @param array|null $attempt Receives the processing outcome and bounded blocked entry IDs.
 * @return bool true on success, false on failure
 */
function ai4seo_automated_attachment_attributes_generation(
	bool $debug = false,
	int $only_this_attachment_post_id = 0,
	bool &$terminal_billing_error = false,
	?array &$attempt = null
): bool {
	return ai4seo_run_bulk_generation_attempts( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES, 'ai4seo_attempt_attachment_attributes_generation', $debug, $only_this_attachment_post_id, $terminal_billing_error, $attempt );
}

/**
 * Attempt one media owner without losing the caller's queue intent.
 *
 * @param bool  $debug Debug output.
 * @param int   $only_this_attachment_post_id Direct owner, or zero for queued work.
 * @param bool  $terminal_billing_error Account-scoped stop output.
 * @param array $attempt Structured output.
 * @param array $excluded_post_ids Already inspected IDs in this pass.
 * @return bool Verified progress.
 */
function ai4seo_attempt_attachment_attributes_generation(
	bool $debug,
	int $only_this_attachment_post_id,
	bool &$terminal_billing_error,
	array &$attempt,
	array $excluded_post_ids
): bool {
	// Clear the output before any early return so callers never observe a prior invocation's state.
	$terminal_billing_error = false;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 583323686, 'Prevented loop', true );
		return false;
	}

	$persisted_enabled_post_types = array();

	// A direct editor generation may proceed, but no queued work can cross an unverifiable disabled-type barrier.
	if ( ! ai4seo_reconcile_disabled_bulk_generation_queue_entries( $persisted_enabled_post_types, array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES ) ) ) {
		return false;
	}

	if ( ! $only_this_attachment_post_id && ! in_array( 'attachment', $persisted_enabled_post_types, true ) ) {
		$attempt['status'] = 'empty';
		return false;
	}

	$active_attachment_attributes    = ai4seo_get_active_attachment_attributes();
	$supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

	if ( ! $active_attachment_attributes ) {
		$attempt['status'] = 'empty';
		if ( $debug ) {
			ai4seo_debug_message( 156074497, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no active meta tags found -> skip' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES ),
			null,
			true
		) ) {
			$attempt['status'] = 'system_error';
			return false;
		}

		return false;
	}

	$approximate_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();
	$account_readiness                    = ai4seo_maybe_refresh_account_state();
	if ( ! $account_readiness['credits_ready'] ) {
		$attempt['status'] = 'account_unavailable';
		return false;
	}

	// check the current credits balance, compare it to $approximate_cost_per_attachment_post and if it's lower, return true.
	if ( $account_readiness['credits'] < $approximate_cost_per_attachment_post ) {
		if ( $debug ) {
			ai4seo_debug_message( 616491274, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because of low Credits balance' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES ),
			$approximate_cost_per_attachment_post
		) ) {
			$attempt['status'] = 'system_error';
			return false;
		}

		$attempt['status'] = 'insufficient_credits';
		return false;
	}

	if ( $only_this_attachment_post_id ) {
		$attachment_post_id = $only_this_attachment_post_id;
	} else {
		$auto_queue_entries          = ai4seo_should_auto_queue_bulk_generation_entries();
		$pending_attachment_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

		if ( $auto_queue_entries && ! $pending_attachment_post_ids ) {
			// try to search for attachment posts with missing attributes.
			$queue_barrier_failed                = false;
			$got_new_pending_attachment_post_ids = ai4seo_excavate_attachments_with_missing_attributes( $debug, $queue_barrier_failed, $selection );

			// An unverifiable admission must remain pending for a later run, never be consumed by this request.
			if ( $queue_barrier_failed ) {
				$attempt['status'] = $selection['status'];
				if ( $debug ) {
					ai4seo_debug_message( 466380278, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Auto Queue admission barrier failed -> retry later' ) ) );
				}

				return false;
			}

			$pending_attachment_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

			// Existing manual queue entries should still be processed when automatic excavation finds nothing new.
			if ( ! $got_new_pending_attachment_post_ids && ! $pending_attachment_post_ids ) {
				$attempt['status'] = 'empty';
				// skip here because we don't have any attachment posts.
				if ( $debug ) {
					ai4seo_debug_message( 466380276, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No pending media posts found' ) ) );
				}

				// Remove Pending entries while preserving Processing work owned by another request.
				if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
					array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES )
				) ) {
					$attempt['status'] = 'system_error';
					return false;
				}

				return false;
			}
		} elseif ( $debug ) {
			ai4seo_debug_message( 466380277, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Auto queue entries disabled -> using existing pending media posts only' ) ) );
		}

		if ( ! isset( $pending_attachment_post_ids ) ) {
			$pending_attachment_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		}

		if ( ! $pending_attachment_post_ids ) {
			$attempt['status'] = 'empty';
			// skip here because we don't have any attachment posts.
			if ( $debug ) {
				ai4seo_debug_message( 170584235, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No pending media posts found' ) ) );
			}

			if ( $auto_queue_entries && ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
				array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES )
			) ) {
				$attempt['status'] = 'system_error';
				return false;
			}

			return false;
		}

		if ( $debug ) {
			ai4seo_debug_message( 968541383, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Found pending media post(s): ' . esc_html( implode( ', ', $pending_attachment_post_ids ) ) ) ) );
		}

		$pending_attachment_post_ids = array_values( array_diff( $pending_attachment_post_ids, $excluded_post_ids ) );
		if ( ! $pending_attachment_post_ids ) {
			$attempt['status'] = 'empty';
			return false;
		}
		$attachment_post_id = reset( $pending_attachment_post_ids );
	}

	// make sure every entry is numeric.
	if ( ! is_numeric( $attachment_post_id ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 993108805, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'media post-id is not numeric' ) ) );
		}
		return false;
	}

	$attachment_post_id                             = absint( $attachment_post_id );
	$attempt['post_id']                             = $attachment_post_id;
	$processing_claimed                             = false;
	$is_force_overwrite_bulk_generation_queue_entry = false;
	$processing_claim_token                         = '';
	$pending_was_present                            = false;
	$force_was_present                              = false;

	if ( ! $only_this_attachment_post_id ) {
		$pre_claim_enabled_post_types = array();

		// Close settings changes during excavation before the selected queue entry can become Processing.
		if ( ! ai4seo_reconcile_disabled_bulk_generation_queue_entries( $pre_claim_enabled_post_types, array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES ) )
			|| ! in_array( 'attachment', $pre_claim_enabled_post_types, true ) ) {
			return false;
		}
	}

	// Own the exact generation before Force mode or mutable attachment state can be observed.
	if ( $only_this_attachment_post_id ) {
		$processing_claim_checked = ai4seo_claim_post_id_for_direct_processing(
			AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			$attachment_post_id,
			$processing_claimed,
			AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			$is_force_overwrite_bulk_generation_queue_entry,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present
		);
	} else {
		$processing_claim_checked = ai4seo_claim_pending_post_id_for_processing(
			AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			$attachment_post_id,
			$processing_claimed,
			AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			$is_force_overwrite_bulk_generation_queue_entry,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present
		);
	}

	$processing_claim_succeeded = $processing_claim_checked && $processing_claimed;

	if ( ! $processing_claim_succeeded ) {
		if ( ! $processing_claim_checked && $processing_claimed && '' !== $processing_claim_token ) {
			ai4seo_compensate_bulk_generation_processing_claim_after_failed_check(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
				$attachment_post_id,
				$processing_claim_token,
				$pending_was_present,
				$force_was_present
			);
		}

		if ( $debug ) {
			ai4seo_debug_message( 641476226, esc_html( __FUNCTION__ ) . ' > Could not exclusively claim the attachment queue entry.', true );
		}
		if ( $processing_claim_checked && ! $processing_claimed ) {
			$attempt['status'] = 'temporarily_busy';
		}

		return false;
	}

	if ( ! ai4seo_register_bulk_generation_processing_claim(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
		$attachment_post_id,
		$processing_claim_token
	) ) {
		ai4seo_recover_registered_bulk_generation_processing_claim(
			array(
				'context'     => AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
				'post_id'     => $attachment_post_id,
				'claim_token' => $processing_claim_token,
			)
		);
		return false;
	}

	if ( $debug ) {
		ai4seo_debug_message( 537974883, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'going to generate media attributes for #' . esc_html( $attachment_post_id ) ) ) );
	}

	$generated_data_read_succeeded = false;
	$history_failure_reason        = '';
	$generated_data_snapshot       = ai4seo_read_authoritative_generated_data_postmeta_snapshot(
		$attachment_post_id,
		$generated_data_read_succeeded,
		$history_failure_reason
	);
	$generated_data_details        = $generated_data_snapshot['generated_data_details'];
	if ( ! $generated_data_read_succeeded ) {
		return ai4seo_finish_blocked_bulk_generation_attempt(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
			$attachment_post_id,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present,
			'damaged_history' === $history_failure_reason,
			__( 'Generation history needs attention. The queued entry is retained; no generation was requested.', 'ai-for-seo' ),
			$attempt
		);
	}
	$attachment_source_read_succeeded = false;
	$attachment_post_exists           = false;
	$attachment_failure_reason        = '';
	$attachment_issues                = null;
	$attachment_source_snapshot       = ai4seo_read_attachment_attribute_source_snapshot(
		$attachment_post_id,
		$attachment_source_read_succeeded,
		$attachment_post_exists,
		$attachment_issues,
		$attachment_failure_reason
	);

	// Never spend credits or derive a terminal status from an unavailable/malformed primary snapshot.
	if ( ! $attachment_source_read_succeeded ) {
		return ai4seo_finish_blocked_bulk_generation_attempt(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
			$attachment_post_id,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present,
			'damaged_source' === $attachment_failure_reason,
			__( 'Media sources need attention. The queued entry is retained; no generation was requested.', 'ai-for-seo' ),
			$attempt
		);
	}

	$generate_this_fields                = $active_attachment_attributes;
	$old_generated_attachment_attributes = $generated_data_details['generated_data'];
	$old_available_attachment_attributes = array(
		'title'       => $attachment_post_exists ? $attachment_source_snapshot['post']['post_title'] : '',
		'caption'     => $attachment_post_exists ? $attachment_source_snapshot['post']['post_excerpt'] : '',
		'description' => $attachment_post_exists ? $attachment_source_snapshot['post']['post_content'] : '',
		'alt-text'    => $attachment_post_exists ? $attachment_source_snapshot['alt_text']['value'] : '',
	);
	$attachment_post_type                = $attachment_post_exists ? $attachment_source_snapshot['post']['post_type'] : '';
	$attachment_post_mime_type           = $attachment_post_exists ? $attachment_source_snapshot['post']['post_mime_type'] : '';
	$allowed_attachment_mime_types       = ai4seo_get_allowed_attachment_mime_types();

	// Validate the exact source before any early coverage terminal can classify an unsupported owner.
	if ( ! $attachment_post_exists || ! in_array( $attachment_post_type, $supported_attachment_post_types, true ) ) {
		$failure_status_succeeded = ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, 'Post is not a media for media post ID: ' . $attachment_post_id, $debug );
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, 'Post is not a media' );
		return $failure_status_succeeded;
	}

	if ( ! in_array( $attachment_post_mime_type, $allowed_attachment_mime_types, true ) ) {
		$failure_status_succeeded = ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, 'Mime type not supported for media post ID: ' . $attachment_post_id, $debug );
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, 'Mime type not supported' );
		return $failure_status_succeeded;
	}

	$attachment_url = ai4seo_resolve_attachment_url_from_source_snapshot( $attachment_source_snapshot );

	if ( ! $attachment_url ) {
		$failure_status_succeeded = ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, 'Media URL not found for media post ID: ' . $attachment_post_id, $debug );
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, 'Media URL not found' );
		return $failure_status_succeeded;
	}

	// Optional image metadata may improve delivery selection; the verified full URL remains its fallback.
	$attachment_image_source = ai4seo_get_attachment_generation_image_source(
		$attachment_post_id,
		$attachment_url,
		$attachment_post_mime_type
	);

	if ( ! $attachment_image_source ) {
		$failure_status_succeeded = ai4seo_handle_failed_attachment_generation(
			$attachment_post_id,
			__FUNCTION__,
			'Media source not found for media post ID: ' . $attachment_post_id,
			$debug
		);
		ai4seo_add_latest_activity_entry(
			$attachment_post_id,
			'error',
			'attachment-attributes-bulk-generated',
			0,
			'Media source not found'
		);
		return $failure_status_succeeded;
	}

	if ( $is_force_overwrite_bulk_generation_queue_entry && $debug ) {
		ai4seo_debug_message( 714227053, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Force overwrite queue marker found -> keeping all active attachment attributes' ) ) );
	}

	if ( ! $is_force_overwrite_bulk_generation_queue_entry ) {
		// remove all already generated metadata from the $generate_this_meta_tags array.
		foreach ( $old_generated_attachment_attributes as $this_attachment_attribute_identifier => $this_attachment_attribute_value ) {
			// not available -> skip, despite we generated it before.
			if ( ! isset( $old_available_attachment_attributes[ $this_attachment_attribute_identifier ] ) || ! $old_available_attachment_attributes[ $this_attachment_attribute_identifier ] ) {
				continue;
			}

			if ( in_array( $this_attachment_attribute_identifier, $generate_this_fields, true ) && $this_attachment_attribute_value ) {
				$this_index = array_search( $this_attachment_attribute_identifier, $generate_this_fields, true );
				unset( $generate_this_fields[ $this_index ] );
			}
		}

		// nothing left to generate -> skip.
		if ( ! $generate_this_fields ) {
			if ( $debug ) {
				ai4seo_debug_message( 375893908, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no missing attachment attributes found for post-id' ) ) );
			}

			// All attributes are already generated; commit the terminal state as one checked transition.
			return ai4seo_apply_bulk_generation_result_transition(
				$attachment_post_id,
				array( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME )
			);
		}
	}

	// check for available attachment attributes
	// and remove meta tags from the missing metadata array that are already available, if we don't want to overwrite them.
	$overwrite_existing_attachment_attributes = ai4seo_get_setting( AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES );
	$is_attachment_post_fully_covered         = true;

	if ( ! $is_force_overwrite_bulk_generation_queue_entry ) {
		foreach ( $generate_this_fields as $this_index => $this_attachment_attribute_identifier ) {
			if ( isset( $old_available_attachment_attributes[ $this_attachment_attribute_identifier ] )
				&& $old_available_attachment_attributes[ $this_attachment_attribute_identifier ]
				&& ! in_array( $this_attachment_attribute_identifier, $overwrite_existing_attachment_attributes, true ) ) {
				unset( $generate_this_fields[ $this_index ] );
				continue;
			}

			if ( ! isset( $old_available_attachment_attributes[ $this_attachment_attribute_identifier ] ) || ! $old_available_attachment_attributes[ $this_attachment_attribute_identifier ] ) {
				$is_attachment_post_fully_covered = false;
			}
		}

		// nothing left to generate -> skip.
		if ( ! $generate_this_fields ) {
			if ( $debug ) {
				ai4seo_debug_message( 556643354, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no missing attachment found found for attachment post-id' ) ) );
			}

			// All attributes are already generated; commit the terminal state as one checked transition.
			return ai4seo_apply_bulk_generation_result_transition(
				$attachment_post_id,
				array( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME )
			);
		}

		// make sure to abort, if we have full coverage and don't want to generate attachment attribute for fully covered entries.
		$generate_attachment_attributes_for_fully_covered_entries = ai4seo_do_generate_attachment_attributes_for_fully_covered_entries();

		if ( $is_attachment_post_fully_covered && ! $generate_attachment_attributes_for_fully_covered_entries ) {
			if ( $debug ) {
				ai4seo_debug_message( 334900672, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'full attachment atributes coverage found and generation for fully covered entries is disabled -> skip' ) ) );
			}

			return ai4seo_apply_bulk_generation_result_transition(
				$attachment_post_id,
				array( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME )
			);
		}
	}

	// PREPARE ROBHUB API CALL.
	$attachment_attributes_generation_language = ai4seo_get_attachments_language( $attachment_post_id );
	$wpml_language                             = sanitize_text_field( ai4seo_try_get_post_language_by_checking_multilanguage_plugins( $attachment_post_id ) );

	$robhub_api_call_parameters = array(
		'language'        => $attachment_attributes_generation_language,
		'system_language' => sanitize_text_field( ai4seo_get_wordpress_language() ),
	);

	if ( $wpml_language ) {
		$robhub_api_call_parameters['wpml_language'] = $wpml_language;
	}

	$robhub_api_call_parameters['trigger']         = 'automated';
	$robhub_api_call_parameters['website_context'] = ai4seo_get_website_context();

	$attachment_usage_context = ai4seo_get_attachment_post_related_context( $attachment_post_id );

	if ( ! empty( $attachment_usage_context ) ) {
		$robhub_api_call_parameters['attachment_usage_context'] = $attachment_usage_context;
	}

	// collect and build field instructions.
	$field_instructions                  = array();
	$attachment_attributes_prefixes      = ai4seo_get_setting( AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES );
	$attachment_attributes_suffixes      = ai4seo_get_setting( AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES );
	$attachment_placeholder_replacements = ai4seo_get_attachment_placeholder_replacements( $attachment_post_id );

	foreach ( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $this_attachment_attribute_identifier => $this_attachment_attribute_details ) {
		$this_to_generate = in_array( $this_attachment_attribute_identifier, $generate_this_fields, true );
		$this_old_value   = $old_available_attachment_attributes[ $this_attachment_attribute_identifier ] ?? '';
		$this_prefix      = $attachment_attributes_prefixes[ $this_attachment_attribute_identifier ] ?? '';
		$this_suffix      = $attachment_attributes_suffixes[ $this_attachment_attribute_identifier ] ?? '';

		if ( ! $this_to_generate && ! $this_old_value ) {
			continue;
		}

		$this_prefix = ai4seo_replace_text_placeholders( $this_prefix, $attachment_placeholder_replacements );
		$this_suffix = ai4seo_replace_text_placeholders( $this_suffix, $attachment_placeholder_replacements );

		$field_instructions[ $this_attachment_attribute_identifier ] = array(
			'generate'  => $this_to_generate,
			'old_value' => $this_old_value,
			'prefix'    => $this_prefix,
			'suffix'    => $this_suffix,
		);
	}

	$robhub_api_call_parameters['approximate_cost']   = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post( $generate_this_fields );
	$robhub_api_call_parameters['field_instructions'] = $field_instructions;

	// SEO Autopilot uses the same instruction collection as manual media attribute generation.
	$custom_instructions = ai4seo_get_generation_custom_instructions( 'attachment_attributes', $attachment_post_id );

	if ( $custom_instructions ) {
		$robhub_api_call_parameters['custom_instructions'] = $custom_instructions;
	}

	// Source selection and optional context enrichment can be slow; reverify before spending credits.
	if ( ! ai4seo_renew_bulk_generation_processing_claim(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
		$attachment_post_id,
		$processing_claim_token
	) ) {
		return false;
	}

	if ( ! $only_this_attachment_post_id && ! ai4seo_guard_queued_bulk_generation_processing_claim_eligibility(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
		$attachment_post_id,
		$processing_claim_token,
		$pending_was_present,
		$force_was_present
	) ) {
		return false;
	}

	// Persist no-restore intent before the request can be accepted or billed; an unverified marker fails closed.
	if ( ! ai4seo_persist_bulk_generation_processing_claim_ambiguous_spend(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
		$attachment_post_id,
		$processing_claim_token
	) ) {
		return false;
	}

	// RobHub owns repair attempts, so issue one request through the shared image transport.
	$results = ai4seo_call_attachment_attributes_generation_api( $attachment_image_source, $robhub_api_call_parameters );

	// Preserve account-scoped stop intent even if ownership changes while the request is in flight.
	$terminal_billing_error = ai4seo_robhub_api()->is_terminal_billing_error_code( $results['code'] ?? null );

	// A reset or replacement worker may have transferred ownership while the API request was in flight.
	if ( ! ai4seo_renew_bulk_generation_processing_claim(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
		$attachment_post_id,
		$processing_claim_token
	) ) {
		return false;
	}

	// CHECK RESULTS.

	if ( ! ai4seo_robhub_api()->was_call_successful( $results ?? false ) ) {
		$error_message = $results['message'] ?? 'Generation with API endpoint failed for attachment post ID: ' . $attachment_post_id;

		if ( isset( $results['code'] ) ) {
			$error_message .= ' (Error #' . sanitize_text_field( $results['code'] ) . ')';
		}

		if ( ! ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, $error_message . ( $debug ? ': ' . ai4seo_stringify( $results ) : '' ), $debug ) ) {
			return false;
		}
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, $error_message );

		ai4seo_debug_message( 4133326, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'API call failed for media post ID: ' . $attachment_post_id . ': ' . ai4seo_stringify( $results ) ) ), true );
		return false;
	}

	// The API call succeeded; validate its payload before saving any usable media attributes.

	$raw_new_attachment_attributes = $results['data'] ?? array();

	if ( empty( $raw_new_attachment_attributes ) || ! is_array( $raw_new_attachment_attributes ) ) {
		if ( ! ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, 'Could not interpret data for media post ID: ' . $attachment_post_id . ( $debug ? ': ' . ai4seo_stringify( $results ) : '' ), $debug ) ) {
			return false;
		}
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, 'Could not interpret data' );
		return false;
	}

	// Apply the shared save contract so cron and manual generation filter, cap, and preserve fields identically.
	$prepared_generated_output    = ai4seo_prepare_generated_output_fields_for_save(
		'attachment_attributes',
		$raw_new_attachment_attributes,
		$generate_this_fields,
		$field_instructions,
		true
	);
	$new_attachment_attributes    = $prepared_generated_output['values'];
	$unresolved_generation_fields = $prepared_generated_output['unresolved_fields'];

	// Keep usable partial responses successful; fail only when no requested field survives preparation.
	if ( ! $new_attachment_attributes ) {
		if ( ! ai4seo_handle_failed_attachment_generation(
			$attachment_post_id,
			__FUNCTION__,
			'Generation returned no usable media attributes for post ID: ' . $attachment_post_id,
			$debug
		) ) {
			return false;
		}
		ai4seo_add_latest_activity_entry(
			$attachment_post_id,
			'error',
			'attachment-attributes-bulk-generated',
			0,
			'No usable media attributes returned from API endpoint'
		);
		return false;
	}

	// Log unresolved identifiers for support while continuing with the usable partial response.
	if ( $unresolved_generation_fields ) {
		ai4seo_debug_message(
			763318905,
			esc_html( __FUNCTION__ ) . ' > Partial media attribute response for attachment post ID ' . $attachment_post_id
				. '. Unresolved fields: ' . implode( ', ', $unresolved_generation_fields )
		);
	}

	// Update only prepared fields and stop before provenance or coverage changes when persistence fails.
	if ( ! ai4seo_renew_bulk_generation_processing_claim(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
		$attachment_post_id,
		$processing_claim_token
	) ) {
		return false;
	}

	$attachment_update_details = array();
	$this_success              = ai4seo_update_attachment_attributes(
		$attachment_post_id,
		$new_attachment_attributes,
		true,
		$attachment_update_details
	);

	if ( ! $this_success ) {
		$attachment_update_commit_state = isset( $attachment_update_details['commit_state'] )
			&& is_string( $attachment_update_details['commit_state'] )
			? $attachment_update_details['commit_state']
			: 'possibly_committed';

		if ( 'not_committed' !== $attachment_update_commit_state ) {
			ai4seo_release_bulk_generation_processing_claim_after_ambiguous_primary_write(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
				$attachment_post_id,
				$processing_claim_token
			);
			ai4seo_debug_message( 641476231, 'Generated media attributes may have committed; released exact ownership without stale queue restoration and scheduled reconciliation.', true );
			return false;
		}

		if ( ! ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, 'Could not save generated media attributes for post ID: ' . $attachment_post_id, $debug ) ) {
			return false;
		}
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, 'Could not save generated media attributes' );
		return false;
	}

	// Persist returned provenance before coverage changes so a failed snapshot cannot be reported as successful.
	if ( ! ai4seo_renew_bulk_generation_processing_claim(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
		$attachment_post_id,
		$processing_claim_token
	) ) {
		return false;
	}

	$generated_data_save_details = array();
	$this_success                = ai4seo_save_generated_data_to_postmeta(
		$attachment_post_id,
		$new_attachment_attributes,
		true,
		0,
		$unresolved_generation_fields,
		$generated_data_save_details
	);

	if ( ! $this_success ) {
		$generated_data_commit_state = isset( $generated_data_save_details['commit_state'] )
			&& is_string( $generated_data_save_details['commit_state'] )
			? $generated_data_save_details['commit_state']
			: 'possibly_committed';

		// Primary media attributes already committed, so every provenance failure must discard stale queue intent.
		ai4seo_release_bulk_generation_processing_claim_after_ambiguous_primary_write(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
			$attachment_post_id,
			$processing_claim_token
		);

		if ( 'not_committed' === $generated_data_commit_state ) {
			ai4seo_debug_message( 641476228, 'Generated media attributes committed without provenance; discarded prior queue intent and scheduled authoritative reconciliation.', true );
			return false;
		}

		ai4seo_debug_message( 641476230, 'Generated media provenance may have committed; released exact ownership without stale queue restoration and scheduled reconciliation.', true );
		return false;
	}

	// Rebuild exclusive coverage: partial results stay Missing; complete results become Fully Covered and Generated.
	if ( $unresolved_generation_fields ) {
		$result_option_names = array( AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

		// Preserve force-overwrite intent so omitted existing fields remain eligible on a later queued run.
		if ( $is_force_overwrite_bulk_generation_queue_entry ) {
			$result_option_names[] = AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;
		}
	} else {
		$result_option_names = array(
			AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
		);
	}

	if ( ! ai4seo_apply_bulk_generation_result_transition( $attachment_post_id, $result_option_names ) ) {
		ai4seo_debug_message( 641476223, 'Could not persist the final attachment generation status transition.', true );
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, 'Could not persist final generation status' );
		return false;
	}

	// Record returned field identifiers for activity details; unresolved eligibility remains represented by coverage above.
	ai4seo_add_latest_activity_entry(
		$attachment_post_id,
		'success',
		'attachment-attributes-bulk-generated',
		(int) ( $results['credits-consumed'] ?? 0 ),
		'',
		array_keys( $new_attachment_attributes )
	);

	if ( $debug ) {
		ai4seo_debug_message( 422896712, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'updated media attributes for #' . esc_html( $attachment_post_id ) . ':' . esc_html( ai4seo_stringify( $new_attachment_attributes ) ) ) ) );
	}

	return true;
}


/**
 * Helps handle failed attachment generation by removing the post id from all generation status options and adding it to the failed ones
 *
 * @param int    $attachment_post_id the attachment post id.
 * @param string $function_name the name of the function that failed.
 * @param string $error_message the error message.
 * @param bool   $debug if true, debug information will be printed.
 * @return bool True only when the failed terminal state was verified.
 */
function ai4seo_handle_failed_attachment_generation( int $attachment_post_id, string $function_name = '', string $error_message = '', bool $debug = false ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Preserve existing debug callers.
	if ( $error_message ) {
		if ( $function_name ) {
			ai4seo_debug_message( 689393850, esc_html( $function_name ) . ' >' . esc_html( ai4seo_stringify( $error_message ) ), true );
		} else {
			ai4seo_debug_message( 250362054, $error_message, true );
		}
	}

	$transition_succeeded = ai4seo_apply_bulk_generation_result_transition( $attachment_post_id, array( AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ) );

	if ( ! $transition_succeeded ) {
		ai4seo_debug_message( 641476224, 'Could not persist the failed attachment generation status transition.', true );
	}

	return $transition_succeeded;
}


/**
 * Moves one bulk-generation entry to its complete requested result state under one shared fence.
 *
 * Additions are established before generation statuses and force-overwrite markers are removed, so a
 * later persistence failure leaves a retryable duplicate state instead of losing the entry. Destination
 * contradictions use the same ownership rules as ai4seo_add_post_ids_to_option().
 *
 * @param int    $post_id Post ID whose generation state changed.
 * @param array  $destination_option_names Options that must contain the post ID after the transition;
 *                                         empty only for an exact owned removal-only result.
 * @param string $owned_claim_token Optional exact durable token for registry-independent ownership.
 * @param array  $owned_removal_option_names Additional exact removals for an explicit owned transition.
 * @return bool True only when every requested membership was verified and the fence was released.
 */
function ai4seo_apply_bulk_generation_result_transition(
	int $post_id,
	array $destination_option_names,
	string $owned_claim_token = '',
	array $owned_removal_option_names = array()
): bool {
	$post_id           = ai4seo_normalize_option_post_id( $post_id );
	$owned_claim_token = trim( $owned_claim_token );

	if ( false === $post_id || 128 < strlen( $owned_claim_token ) ) {
		return false;
	}

	// A removal-only owner must derive one context exclusively from its explicit removals.
	$context_option_names = $destination_option_names;

	if ( ! $context_option_names ) {
		if ( '' === $owned_claim_token || ! $owned_removal_option_names ) {
			return false;
		}

		foreach ( $owned_removal_option_names as $owned_removal_option_name ) {
			if ( ! is_string( $owned_removal_option_name ) ) {
				return false;
			}
		}

		$context_option_names = $owned_removal_option_names;
	}

	$context = ai4seo_get_bulk_generation_result_transition_context( $context_option_names );

	if ( '' === $context ) {
		return false;
	}

	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );
	$additions     = array();
	$removals      = array();

	foreach ( $destination_option_names as $destination_option_name ) {
		$destination_option_name = sanitize_key( $destination_option_name );

		if ( ! in_array( $destination_option_name, AI4SEO_ALL_POST_ID_OPTIONS, true ) ) {
			return false;
		}

		$additions[ $destination_option_name ] = array( $post_id );

		foreach ( ai4seo_get_contradictory_post_id_option_names( $destination_option_name ) as $contradictory_option_name ) {
			$removals[ $contradictory_option_name ] = array( $post_id );
		}
	}

	foreach ( array( 'pending', 'processing', 'failed', 'force_overwrite' ) as $status_option_key ) {
		$removals[ $queue_options[ $status_option_key ] ] = array( $post_id );
	}

	if ( $owned_removal_option_names && '' === $owned_claim_token ) {
		return false;
	}

	foreach ( $owned_removal_option_names as $owned_removal_option_name ) {
		if ( ! is_string( $owned_removal_option_name ) ) {
			return false;
		}

		$owned_removal_option_name = sanitize_key( $owned_removal_option_name );

		if (
			! in_array( $owned_removal_option_name, AI4SEO_ALL_POST_ID_OPTIONS, true )
			|| ai4seo_get_bulk_generation_result_transition_context( array( $owned_removal_option_name ) ) !== $context
			|| isset( $additions[ $owned_removal_option_name ] )
		) {
			return false;
		}

		$removals[ $owned_removal_option_name ] = array( $post_id );
	}

	$registered_claim                  = ai4seo_get_registered_bulk_generation_processing_claim( $context, $post_id );
	$registered_claim_token            = is_array( $registered_claim ) && is_string( $registered_claim['claim_token'] ?? null )
		? $registered_claim['claim_token']
		: '';
	$claim_token                       = '' !== $owned_claim_token ? $owned_claim_token : $registered_claim_token;
	$uses_owned_claim                  = '' !== $claim_token;
	$terminal_force_overwrite          = isset( $additions[ $queue_options['force_overwrite'] ] );
	$terminal_destination_option_names = array_keys( $additions );
	sort( $terminal_destination_option_names, SORT_STRING );
	$terminal_removal_option_names = array_keys( array_diff_key( $removals, $additions ) );
	sort( $terminal_removal_option_names, SORT_STRING );
	$attempt_limit  = 3;
	$ownership_lost = false;

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$transition_succeeded = false;

		if ( ! $uses_owned_claim ) {
			$transition_succeeded = ai4seo_apply_post_id_option_transition( $additions, $removals );
		} else {
			$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();
			$release_succeeded     = false;

			if ( ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
				continue;
			}

			try {
				$leases = array();

				if ( ! ai4seo_read_processing_claim_leases_under_lock( $queue_options['processing'], $leases ) ) {
					$transition_succeeded = false;
				} else {
					$current_lease       = $leases[ $post_id ] ?? null;
					$processing_snapshot = ai4seo_get_raw_option_snapshot( $queue_options['processing'] );

					if (
						! is_array( $current_lease )
						|| ! hash_equals( $claim_token, $current_lease['token'] )
						|| ! ai4seo_is_processing_claim_lease_active( $current_lease )
						|| null === $processing_snapshot
					) {
						$ownership_lost = true;
					} else {
						$processing_lookup              = array_fill_keys(
							ai4seo_normalize_option_post_id_collection( $processing_snapshot['value'] ),
							true
						);
						$has_processing_membership      = isset( $processing_lookup[ $post_id ] );
						$has_matching_terminal_intent   = array_key_exists( 'terminal_force_overwrite', $current_lease )
							&& $terminal_force_overwrite === $current_lease['terminal_force_overwrite']
							&& isset( $current_lease['terminal_destination_option_names'], $current_lease['terminal_removal_option_names'] )
							&& $terminal_destination_option_names === $current_lease['terminal_destination_option_names']
							&& $terminal_removal_option_names === $current_lease['terminal_removal_option_names'];
						$terminal_destinations_verified = $has_processing_membership
							|| (
								$has_matching_terminal_intent
								&& ai4seo_verify_processing_claim_terminal_destinations_under_lock( $current_lease, $post_id )
							);

						if ( ! $terminal_destinations_verified ) {
							$ownership_lost = true;
						} else {
							unset( $current_lease['rollback_requested'], $current_lease['preserve_new_queue_memberships'], $current_lease['ambiguous_spend'] );
							$current_lease['expires_at']                        = time() + ai4seo_get_processing_claim_lease_ttl_seconds();
							$current_lease['terminal_force_overwrite']          = $terminal_force_overwrite;
							$current_lease['terminal_destination_option_names'] = $terminal_destination_option_names;
							$current_lease['terminal_removal_option_names']     = $terminal_removal_option_names;
							$lease_predicate_matched                            = false;
							$terminal_intent_persisted                          = ai4seo_mutate_processing_claim_lease_under_lock(
								$queue_options['processing'],
								$post_id,
								$current_lease,
								$claim_token,
								$lease_predicate_matched
							) && $lease_predicate_matched;

							if ( $terminal_intent_persisted ) {
								$transition_did_change = false;
								$transition_succeeded  = ai4seo_apply_normalized_post_id_option_transition_under_lock(
									$additions,
									$removals,
									$transition_did_change
								);

								if ( $transition_succeeded ) {
									$lease_predicate_matched = false;
									$transition_succeeded    = ai4seo_mutate_processing_claim_lease_under_lock(
										$queue_options['processing'],
										$post_id,
										null,
										$claim_token,
										$lease_predicate_matched
									) && $lease_predicate_matched;
								}
							}
						}
					}
				}
			} finally {
				$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
			}

			$transition_succeeded = $transition_succeeded && $release_succeeded;
		}

		if ( $transition_succeeded ) {
			if ( '' !== $registered_claim_token && hash_equals( $registered_claim_token, $claim_token ) ) {
				ai4seo_unregister_bulk_generation_processing_claim( $context, $post_id );
			}

			return true;
		}

		if ( $ownership_lost ) {
			break;
		}
	}

	if ( function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' ) ) {
		ai4seo_schedule_generation_status_summary_rebuild();
	}

	if ( $uses_owned_claim ) {
		ai4seo_schedule_bulk_generation_processing_recovery();
	}

	return false;
}


/**
 * Persist exact queue-membership rollback owners before a fenced mutation can become visible.
 *
 * The Processing lease option doubles as a bounded mutation ledger. Orphan recovery already restores
 * the exact Pending and Force snapshots for rollback_requested leases without touching a replacement
 * token, so admission and disabled-entry scrubs can share one strict durable transaction shape.
 *
 * @param string $context Bulk generation queue context.
 * @param array  $membership_snapshots Post IDs mapped to exact Pending and Force snapshot booleans.
 * @param array  $rollback_tokens Receives rollback tokens keyed by post ID, including an ambiguous final write.
 * @param bool   $preserve_new_queue_memberships Whether rollback may add snapshots but never remove later memberships.
 * @return bool Whether every exact rollback owner was verified.
 */
function ai4seo_persist_queue_membership_rollback_leases_under_lock(
	string $context,
	array $membership_snapshots,
	array &$rollback_tokens,
	bool $preserve_new_queue_memberships = false
): bool {
	$queue_options                   = ai4seo_get_bulk_generation_queue_options_by_context( $context );
	$rollback_tokens                 = array();
	$normalized_membership_snapshots = array();

	if ( ! isset( $queue_options['processing'] ) || ! $membership_snapshots ) {
		return false;
	}

	foreach ( $membership_snapshots as $post_id => $membership_snapshot ) {
		$normalized_post_id = ai4seo_normalize_option_post_id( $post_id );

		if (
			false === $normalized_post_id
			|| isset( $normalized_membership_snapshots[ $normalized_post_id ] )
			|| ! is_array( $membership_snapshot )
			|| array( 'pending_was_present', 'force_was_present' ) !== array_keys( $membership_snapshot )
			|| ! is_bool( $membership_snapshot['pending_was_present'] )
			|| ! is_bool( $membership_snapshot['force_was_present'] )
		) {
			return false;
		}

		$normalized_membership_snapshots[ $normalized_post_id ] = $membership_snapshot;
	}

	if ( count( $normalized_membership_snapshots ) !== count( $membership_snapshots ) ) {
		return false;
	}

	foreach ( $normalized_membership_snapshots as $post_id => $membership_snapshot ) {
		$rollback_token              = ai4seo_create_processing_claim_token();
		$rollback_tokens[ $post_id ] = $rollback_token;
		$lease_predicate_matched     = false;
		$replacement_lease           = array(
			'token'               => $rollback_token,
			'expires_at'          => time() + ai4seo_get_processing_claim_lease_ttl_seconds(),
			'force_overwrite'     => $membership_snapshot['force_was_present'],
			'pending_was_present' => $membership_snapshot['pending_was_present'],
			'force_was_present'   => $membership_snapshot['force_was_present'],
			'rollback_requested'  => true,
		);

		if ( $preserve_new_queue_memberships ) {
			$replacement_lease['preserve_new_queue_memberships'] = true;
		}

		$lease_persisted = ai4seo_mutate_processing_claim_lease_under_lock(
			$queue_options['processing'],
			$post_id,
			$replacement_lease,
			'',
			$lease_predicate_matched
		);

		if ( ! $lease_persisted || ! $lease_predicate_matched ) {
			return false;
		}
	}

	return true;
}


/**
 * Exact-token remove verified queue rollback owners while preserving a replacement owner.
 *
 * @param string $context Bulk generation queue context.
 * @param array  $rollback_tokens Rollback tokens keyed by post ID; resolved entries are removed.
 * @return bool Whether every owned token was removed, absent, or already replaced.
 */
function ai4seo_clear_queue_membership_rollback_leases_under_lock(
	string $context,
	array &$rollback_tokens
): bool {
	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

	if ( ! isset( $queue_options['processing'] ) ) {
		return false;
	}

	foreach ( $rollback_tokens as $post_id => $rollback_token ) {
		$leases = array();

		if ( ! ai4seo_read_processing_claim_leases_under_lock( $queue_options['processing'], $leases ) ) {
			return false;
		}

		$current_lease = $leases[ $post_id ] ?? null;

		if ( ! is_array( $current_lease ) || ! hash_equals( $rollback_token, $current_lease['token'] ) ) {
			// Absence is already clean; a different exact token is a later authoritative owner.
			unset( $rollback_tokens[ $post_id ] );
			continue;
		}

		if (
			empty( $current_lease['rollback_requested'] )
			|| ! array_key_exists( 'pending_was_present', $current_lease )
			|| ! array_key_exists( 'force_was_present', $current_lease )
		) {
			return false;
		}

		$lease_predicate_matched = false;

		if (
			! ai4seo_mutate_processing_claim_lease_under_lock(
				$queue_options['processing'],
				$post_id,
				null,
				$rollback_token,
				$lease_predicate_matched
			)
			|| ! $lease_predicate_matched
		) {
			return false;
		}

		unset( $rollback_tokens[ $post_id ] );
	}

	return true;
}


/**
 * Atomically queues automatic candidates while retaining their existing force-overwrite markers.
 *
 * @param array      $post_ids Post IDs selected by automatic queue excavation.
 * @param string     $context Bulk generation queue context.
 * @param int        $maximum_pending_post_ids Queue capacity.
 * @param array|null $selection_policy Validated selection rules to recheck under the queue fence.
 * @return array|null IDs admitted by this call, or null when the queue mutation failed.
 */
function ai4seo_add_bounded_auto_queue_post_ids_preserving_force_overwrite(
	array $post_ids,
	string $context,
	int $maximum_pending_post_ids,
	?array $selection_policy = null
): ?array {
	$selection_policy = $selection_policy ?? ai4seo_read_auto_queue_policy_snapshot( $context );
	if ( 'ready' !== $selection_policy['status'] ) {
		return null;
	}
	$post_ids                  = ai4seo_normalize_ordered_auto_queue_post_ids( $post_ids );
	$queue_options             = ai4seo_get_bulk_generation_queue_options_by_context( $context );
	$admitted_post_ids         = array();
	$planned_post_ids          = array();
	$admission_rollback_tokens = array();
	$barrier_result            = null;
	$release_succeeded         = false;
	$recovery_required         = false;
	$required_option_keys      = array(
		'missing',
		'processing',
		'hidden',
		'auto_queue_disallowed',
		'failed',
		'force_overwrite',
	);

	foreach ( array_merge( array( 'pending' ), $required_option_keys ) as $required_option_key ) {
		if ( ! isset( $queue_options[ $required_option_key ] ) ) {
			return null;
		}
	}

	if ( ! $post_ids || 0 >= $maximum_pending_post_ids ) {
		return array();
	}

	// Use the same site-scoped fence as every post-ID option transition so Pending admission and
	// the Processing/Hidden/Disallowed ownership checks form one indivisible plugin operation.
	$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();

	if ( ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		return null;
	}

	try {
		$read_eligibility_snapshot = static function () use ( $queue_options, $required_option_keys, $context, $selection_policy, $post_ids ): ?array {
			$account = ai4seo_get_account_readiness( true );
			$cost    = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context ? ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post() : ai4seo_calculate_metadata_credits_cost_per_post();
			if ( ! $account['credits_ready'] || $account['credits'] < $cost ) {
				return null;
			}
			if ( ! ai4seo_validate_analysis_auto_queue_admission_under_lock( $context ) ) {
				return null;
			}
			$current_policy = ai4seo_read_auto_queue_policy_snapshot( $context );
			if ( 'ready' !== $current_policy['status'] || $current_policy['fingerprint'] !== $selection_policy['fingerprint'] ) {
				return null;
			}
			$classified = ai4seo_classify_auto_queue_candidates( $post_ids, $context, $current_policy );
			if ( 'ready' !== $classified['status'] ) {
				return null;
			}
			$option_memberships = array( 'eligible' => array_fill_keys( $classified['eligible_ids'], true ) );

			foreach ( $required_option_keys as $required_option_key ) {
				if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
					return null;
				}

				$option_snapshot = ai4seo_get_raw_option_snapshot( $queue_options[ $required_option_key ] );

				if ( null === $option_snapshot ) {
					return null;
				}
				$inspection = ai4seo_inspect_analysis_post_id_option( $queue_options[ $required_option_key ], $option_snapshot );
				if ( ! $inspection['read_succeeded'] || ! $inspection['complete'] || ( 'missing' === $required_option_key && ! $option_snapshot['exists'] ) ) {
					return null;
				}

				$option_memberships[ $required_option_key ] = array_fill_keys(
					$inspection['ids'],
					true
				);
			}

			$final_policy = ai4seo_read_auto_queue_policy_snapshot( $context );
			if ( 'ready' !== $final_policy['status'] || $final_policy['fingerprint'] !== $selection_policy['fingerprint'] ) {
				return null;
			}
			return $option_memberships;
		};
		$filter_eligible_post_ids  = static function ( array $candidate_post_ids, array $option_memberships ): array {
			$eligible_post_ids = array();

			foreach ( $candidate_post_ids as $candidate_post_id ) {
				if ( ! isset( $option_memberships['missing'][ $candidate_post_id ], $option_memberships['eligible'][ $candidate_post_id ] ) ) {
					continue;
				}

				if (
					isset( $option_memberships['processing'][ $candidate_post_id ] )
					|| isset( $option_memberships['hidden'][ $candidate_post_id ] )
					|| isset( $option_memberships['auto_queue_disallowed'][ $candidate_post_id ] )
					|| isset( $option_memberships['failed'][ $candidate_post_id ] )
				) {
					continue;
				}

				$eligible_post_ids[] = $candidate_post_id;
			}

			return $eligible_post_ids;
		};
		$clear_admission_rollbacks = static function ( array $safe_post_ids ) use ( $context, &$admission_rollback_tokens ): bool {
			$safe_post_id_lookup = array_fill_keys( ai4seo_normalize_ordered_auto_queue_post_ids( $safe_post_ids ), true );
			$selected_tokens     = array_intersect_key( $admission_rollback_tokens, $safe_post_id_lookup );

			if ( ! $selected_tokens ) {
				return true;
			}

			$cleanup_succeeded = ai4seo_clear_auto_queue_admission_rollback_leases_under_lock(
				$context,
				$selected_tokens
			);

			foreach ( array_keys( $safe_post_id_lookup ) as $safe_post_id ) {
				if ( ! isset( $selected_tokens[ $safe_post_id ] ) ) {
					unset( $admission_rollback_tokens[ $safe_post_id ] );
				}
			}

			return $cleanup_succeeded;
		};

		$initial_memberships = $read_eligibility_snapshot();

		if ( null === $initial_memberships ) {
			$barrier_result = null;
		} else {
			$eligible_post_ids = $filter_eligible_post_ids( $post_ids, $initial_memberships );

			if ( ! $eligible_post_ids ) {
				$barrier_result = array();
			} else {
				$pending_snapshot = ai4seo_renew_post_id_option_transition_semaphore()
					? ai4seo_get_raw_option_snapshot( $queue_options['pending'] )
					: null;

				if ( null === $pending_snapshot ) {
					$barrier_result    = null;
					$recovery_required = true;
				} else {
					$current_pending_post_ids = ai4seo_normalize_ordered_auto_queue_post_ids( $pending_snapshot['value'] );
					$current_pending_lookup   = array_fill_keys( $current_pending_post_ids, true );

					foreach ( $eligible_post_ids as $eligible_post_id ) {
						if ( count( $current_pending_post_ids ) + count( $planned_post_ids ) >= $maximum_pending_post_ids ) {
							break;
						}

						if ( ! isset( $current_pending_lookup[ $eligible_post_id ] ) ) {
							$planned_post_ids[] = $eligible_post_id;
						}
					}

					if ( ! $planned_post_ids ) {
						$barrier_result = array();
					} elseif ( ! ai4seo_persist_auto_queue_admission_rollback_leases_under_lock(
						$context,
						$planned_post_ids,
						$initial_memberships['force_overwrite'],
						$admission_rollback_tokens
					) ) {
						$rollback_cleanup_succeeded = $clear_admission_rollbacks( $planned_post_ids );
						$barrier_result             = null;
						$recovery_required          = true;

						if ( ! $rollback_cleanup_succeeded ) {
							$recovery_required = true;
						}
					} else {
						$admission_write_succeeded = ai4seo_add_bounded_post_ids_to_option(
							$queue_options['pending'],
							$planned_post_ids,
							$maximum_pending_post_ids,
							$admitted_post_ids
						);
						$non_admitted_post_ids     = array_values( array_diff( $planned_post_ids, $admitted_post_ids ) );

						if ( ! $admission_write_succeeded ) {
							$pending_rollback_succeeded = ! $admitted_post_ids
								|| ai4seo_remove_post_ids_from_option_atomically( $queue_options['pending'], $admitted_post_ids );
							$safe_lease_cleanup_ids     = $non_admitted_post_ids;

							if ( $pending_rollback_succeeded ) {
								$safe_lease_cleanup_ids = array_merge( $safe_lease_cleanup_ids, $admitted_post_ids );
							}

							$rollback_cleanup_succeeded = $clear_admission_rollbacks( $safe_lease_cleanup_ids );
							$barrier_result             = null;
							$recovery_required          = true;

							if ( ! $pending_rollback_succeeded || ! $rollback_cleanup_succeeded ) {
								$recovery_required = true;
							}
						} elseif ( ! $admitted_post_ids ) {
							$rollback_cleanup_succeeded = $clear_admission_rollbacks( $planned_post_ids );
							$barrier_result             = $rollback_cleanup_succeeded ? array() : null;
							$recovery_required          = ! $rollback_cleanup_succeeded;
						} else {
							$final_memberships = $read_eligibility_snapshot();

							if ( null === $final_memberships ) {
								$pending_rollback_succeeded = ai4seo_remove_post_ids_from_option_atomically(
									$queue_options['pending'],
									$admitted_post_ids
								);
								$safe_lease_cleanup_ids     = $non_admitted_post_ids;

								if ( $pending_rollback_succeeded ) {
									$safe_lease_cleanup_ids = array_merge( $safe_lease_cleanup_ids, $admitted_post_ids );
								}

								$rollback_cleanup_succeeded = $clear_admission_rollbacks( $safe_lease_cleanup_ids );
								$barrier_result             = null;
								$recovery_required          = true;

								if ( ! $pending_rollback_succeeded || ! $rollback_cleanup_succeeded ) {
									$recovery_required = true;
								}
							} else {
								$final_eligible_post_ids = $filter_eligible_post_ids( $admitted_post_ids, $final_memberships );
								$rollback_post_ids       = array_values( array_diff( $admitted_post_ids, $final_eligible_post_ids ) );
								$rollback_succeeded      = ! $rollback_post_ids
									|| ai4seo_remove_post_ids_from_option_atomically( $queue_options['pending'], $rollback_post_ids );

								if ( ! $rollback_succeeded ) {
									$rollback_cleanup_succeeded = $clear_admission_rollbacks( $non_admitted_post_ids );
									$barrier_result             = null;
									$recovery_required          = true;

									if ( ! $rollback_cleanup_succeeded ) {
										$recovery_required = true;
									}
								} else {
									$pending_snapshot           = ai4seo_renew_post_id_option_transition_semaphore()
										? ai4seo_get_raw_option_snapshot( $queue_options['pending'] )
										: null;
									$pending_verification_owned = null !== $pending_snapshot
										&& ai4seo_renew_post_id_option_transition_semaphore();
									$verification_succeeded     = $pending_verification_owned;

									if ( $pending_verification_owned ) {
										$pending_lookup = array_fill_keys(
											ai4seo_normalize_ordered_auto_queue_post_ids( $pending_snapshot['value'] ),
											true
										);

										foreach ( $final_eligible_post_ids as $eligible_post_id ) {
											if ( ! isset( $pending_lookup[ $eligible_post_id ] ) ) {
												$verification_succeeded = false;
												break;
											}
										}

										foreach ( $rollback_post_ids as $rollback_post_id ) {
											if ( isset( $pending_lookup[ $rollback_post_id ] ) ) {
												$verification_succeeded = false;
												break;
											}
										}
									}

									if ( ! $verification_succeeded ) {
										$pending_compensation_succeeded = ! $final_eligible_post_ids
											|| ai4seo_remove_post_ids_from_option_atomically(
												$queue_options['pending'],
												$final_eligible_post_ids
											);
										$safe_lease_cleanup_ids         = array_merge( $non_admitted_post_ids, $rollback_post_ids );

										if ( $pending_compensation_succeeded ) {
											$safe_lease_cleanup_ids = array_merge( $safe_lease_cleanup_ids, $final_eligible_post_ids );
										}

										$rollback_cleanup_succeeded = $clear_admission_rollbacks( $safe_lease_cleanup_ids );
										$barrier_result             = null;
										$recovery_required          = true;

										if ( ! $pending_compensation_succeeded || ! $rollback_cleanup_succeeded ) {
											$recovery_required = true;
										}
									} else {
										$rollback_cleanup_succeeded = $clear_admission_rollbacks( $planned_post_ids );
										$barrier_result             = $rollback_cleanup_succeeded ? $final_eligible_post_ids : null;
										$recovery_required          = ! $rollback_cleanup_succeeded;
									}
								}
							}
						}
					}
				}
			}
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	if ( ! $release_succeeded ) {
		$barrier_result    = null;
		$recovery_required = true;
	}

	if ( $admission_rollback_tokens ) {
		$recovery_required = true;
	}

	if ( $recovery_required ) {
		if ( function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' ) ) {
			ai4seo_schedule_generation_status_summary_rebuild();
		}

		ai4seo_schedule_bulk_generation_processing_recovery();
	}

	// Force-overwrite markers intentionally survive automatic pending and processing transitions unchanged.
	return $barrier_result;
}


/**
 * Function to excavate posts, pages, products etc. with missing metadata.
 * Is used by the cronjob "ai4seo_automated_generation_cron_job" to find posts and pages that are missing metadata
 *
 * @param bool       $debug                Retained for caller compatibility; unused by shared selection.
 * @param bool|null  $queue_barrier_failed Receives whether selection is blocked, incomplete, or unverifiable.
 * @param array|null $selection Structured selection outcome.
 * @param array      $blocked_post_ids Pending owners verified as blocked by this worker pass.
 * @return bool
 */
function ai4seo_excavate_post_entries_with_missing_metadata( bool $debug = false, ?bool &$queue_barrier_failed = null, ?array &$selection = null, array $blocked_post_ids = array() ): bool {
	$selection            = ai4seo_select_auto_queue_candidates( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, $blocked_post_ids );
	$queue_barrier_failed = in_array( $selection['status'], array( 'system_error', 'policy_changed', 'context_blocked', 'filter_blocked', 'analysis_pending', 'deferred', 'data_blocked' ), true );
	return in_array( $selection['status'], array( 'queued', 'queue_full' ), true ) || ( 'empty' === $selection['status'] && ! empty( $selection['candidates_found'] ) );
}


/**
 * Function to excavate attachments with missing attributes.
 * Is used by the cronjob "ai4seo_automated_generation_cron_job"
 *
 * @param bool       $debug                Retained for caller compatibility; unused by shared selection.
 * @param bool|null  $queue_barrier_failed Receives whether selection is blocked, incomplete, or unverifiable.
 * @param array|null $selection Structured selection outcome.
 * @param array      $blocked_post_ids Pending owners verified as blocked by this worker pass.
 * @return bool
 */
function ai4seo_excavate_attachments_with_missing_attributes( bool $debug = false, ?bool &$queue_barrier_failed = null, ?array &$selection = null, array $blocked_post_ids = array() ): bool {
	$selection            = ai4seo_select_auto_queue_candidates( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES, $blocked_post_ids );
	$queue_barrier_failed = in_array( $selection['status'], array( 'system_error', 'policy_changed', 'context_blocked', 'filter_blocked', 'analysis_pending', 'deferred', 'data_blocked' ), true );
	return in_array( $selection['status'], array( 'queued', 'queue_full' ), true ) || ( 'empty' === $selection['status'] && ! empty( $selection['candidates_found'] ) );
}


// endregion
// ___________________________________________________________________________________________.
