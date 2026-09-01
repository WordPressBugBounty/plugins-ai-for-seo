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
	if ( ! wp_next_scheduled( AI4SEO_BULK_GENERATION_CRON_JOB_NAME ) ) {
		wp_schedule_event( time(), 'five_minutes', AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
	}

	// add custom cron schedule for analyzing the plugins performance.
	if ( ! wp_next_scheduled( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME ) ) {
		wp_schedule_event( time(), 'one_hour', AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME );
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
	ai4seo_unschedule_active_metadata_migration_v235_cron_job();
}


/**
 * Checks whether the v235 active metadata migration is completed.
 *
 * @return bool
 */
function ai4seo_is_active_metadata_migration_v235_completed(): bool {
	$active_metadata_migration_v235_state = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE );

	if ( 'completed' !== $active_metadata_migration_v235_state ) {
		return false;
	}

	$active_metadata_migration_v235_started_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME );

	if ( $active_metadata_migration_v235_started_time > 0 ) {
		return true;
	}

	$legacy_read_succeeded = false;
	$has_legacy_rows       = ai4seo_has_legacy_active_metadata_rows( $legacy_read_succeeded );

	if ( ! $legacy_read_succeeded ) {
		return false;
	}

	if ( $has_legacy_rows ) {
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE, 'idle', false );
		return false;
	}

	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME, time(), false );
	return true;
}


/**
 * Starts the v235 active metadata migration and schedules its cron job.
 *
 * @return void
 */
function ai4seo_start_active_metadata_migration_v235(): void {
	$legacy_read_succeeded = false;
	$has_legacy_rows       = ai4seo_has_legacy_active_metadata_rows( $legacy_read_succeeded );

	if ( $legacy_read_succeeded && ! $has_legacy_rows ) {
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE, 'completed' );
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME, time() );
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_LAST_RUN_TIME, time() );
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_PROCESSED_ENTRIES, 0 );
		ai4seo_unschedule_active_metadata_migration_v235_cron_job();
		return;
	}

	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE, 'idle' );
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME, time() );
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_LAST_RUN_TIME, 0 );
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_PROCESSED_ENTRIES, 0 );

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
 * @return bool
 */
function ai4seo_active_metadata_migration_v235_cron_job(): bool {
	if ( ! AI4SEO_CRON_JOBS_ENABLED && wp_doing_cron() ) {
		return true;
	}

	if ( ai4seo_is_active_metadata_migration_v235_completed() ) {
		ai4seo_unschedule_active_metadata_migration_v235_cron_job();
		return true;
	}

	$active_metadata_migration_v235_state        = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE, false );
	$active_metadata_migration_v235_started_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME, false );

	if ( 'processing' === $active_metadata_migration_v235_state && $active_metadata_migration_v235_started_time > time() - ( 15 * MINUTE_IN_SECONDS ) ) {
		return true;
	}

	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE, 'processing', false );
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_LAST_RUN_TIME, time(), false );
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME, time(), false );

	$is_finished = ai4seo_run_active_metadata_migration_v235_batch();

	if ( $is_finished ) {
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE, 'completed', false );
		ai4seo_unschedule_active_metadata_migration_v235_cron_job();
	} else {
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE, 'idle', false );
		ai4seo_schedule_active_metadata_migration_v235_cron_job();
	}

	return true;
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

	// is the cron job enabled?
	if ( ! AI4SEO_CRON_JOBS_ENABLED && wp_doing_cron() ) {
		return;
	}

	// Current time.
	$now = time();

	// Define a constant for the minimum interval in seconds.
	$bulk_generation_duration        = (int) ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_DURATION );
	$min_delay_for_looping_cron_jobs = $bulk_generation_duration + 10;
	$cron_job_status                 = ai4seo_get_cron_job_status( $cronjob_name );

	// do not allow an injection if the cron job status is still processing or initiating.
	if ( in_array( $cron_job_status, array( 'processing', 'initiating' ), true ) ) {
		return;
	}

	// Get the next scheduled time for the event.
	$next_scheduled = wp_next_scheduled( $cronjob_name );

	// Schedule the event for ASAP only if there isn't already one scheduled within the $delay + 1 seconds.
	if ( ! $next_scheduled || $next_scheduled > ( $now + $delay + 1 ) ) {
		// Clear the scheduled hook.
		wp_unschedule_event( $next_scheduled, $cronjob_name );

		// Schedule it to run ASAP (in $delay seconds).
		wp_schedule_single_event( $now + $delay, $cronjob_name );

		// set the status to scheduled.
		ai4seo_set_cron_job_status( $cronjob_name, 'scheduled' );
	}
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

	if ( ! is_numeric( $time ) ) {
		return false;
	}

	$cron_job_name = sanitize_key( $cron_job_name );
	$cron_job_name = preg_replace( '/[^a-zA-Z0-9_]/', '', $cron_job_name );

	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL, $time );

	// Merge this cron-specific timestamp inside the shared environmental option CAS.
	ai4seo_mutate_environmental_variable_value(
		AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS,
		static function ( array $cron_job_call_times ) use ( $cron_job_name, $time ): array {
			$cron_job_call_times[ $cron_job_name ] = $time;
			return $cron_job_call_times;
		}
	);

	return true;
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
		$cron_job_name = sanitize_key( $cron_job_name );
		$cron_job_name = preg_replace( '/[^a-zA-Z0-9_]/', '', $cron_job_name );

		$last_specific_cronjob_calls = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS );
		return (int) ( $last_specific_cronjob_calls[ $cron_job_name ] ?? 0 );
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

	$all_cronjob_job_status = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST );

	return $all_cronjob_job_status[ $cron_job_name ] ?? 'unknown';
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

	// first refresh the last status update time.
	ai4seo_refresh_cron_job_status_update_time( $cron_job_name );

	// Merge only this job's status so a concurrent cron callback cannot lose another job entry.
	return ai4seo_mutate_environmental_variable_value(
		AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST,
		static function ( array $cron_job_statuses ) use ( $cron_job_name, $status ): array {
			$cron_job_statuses[ $cron_job_name ] = $status;
			return $cron_job_statuses;
		}
	);
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

	// Resolve the timestamp once, then merge it inside every retry of the outer environmental CAS.
	$status_update_time = time();

	return ai4seo_mutate_environmental_variable_value(
		AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES,
		static function ( array $cron_job_status_update_times ) use ( $cron_job_name, $status_update_time ): array {
			$cron_job_status_update_times[ $cron_job_name ] = $status_update_time;
			return $cron_job_status_update_times;
		}
	);
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

	$all_cronjob_job_status_time = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES );

	return $all_cronjob_job_status_time[ $cron_job_name ] ?? 0;
}


/**
 * Removes only Pending queue memberships observed in authoritative snapshots.
 *
 * Processing belongs to a live worker and is never inferred to be owned by the cleanup caller.
 * The storage primitive observes Pending and matching Force state only after acquiring the shared
 * fence, then fails closed if any out-of-band writer changes either exact snapshot.
 *
 * @param array $contexts Bulk generation queue contexts.
 * @return bool True only when every requested snapshot removal was verified.
 */
function ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships( array $contexts ): bool {
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

	if ( ! ai4seo_clear_primary_post_id_option_pairs( $primary_to_paired_option_names ) ) {
		ai4seo_debug_message( 641476228, 'Could not safely remove bulk generation queue snapshot memberships.', true );
		return false;
	}

	return true;
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
 * @return bool True only after settings, queue transitions, and fence release were verified.
 */
function ai4seo_reconcile_disabled_bulk_generation_queue_entries( ?array &$enabled_post_types = null ): bool {
	global $wpdb;

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
		$required_queue_options   = array(
			$metadata_queue_options['pending'] ?? '',
			$metadata_queue_options['force_overwrite'] ?? '',
			$metadata_queue_options['processing'] ?? '',
			$attachment_queue_options['pending'] ?? '',
			$attachment_queue_options['force_overwrite'] ?? '',
			$attachment_queue_options['processing'] ?? '',
		);

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

				if ( null === $option_snapshot ) {
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
		$metadata_post_ids            = $operation_succeeded
			? ai4seo_normalize_option_post_id_collection(
				array_merge(
					$queue_post_ids[ $metadata_pending_option ],
					$queue_post_ids[ $metadata_force_option ]
				)
			)
			: array();
		$attachment_post_ids          = $operation_succeeded
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

		if ( $operation_succeeded ) {
			$operation_succeeded = ai4seo_read_disabled_queue_inspection_state_under_lock(
				$inspection_state_snapshot,
				$inspection_state
			);
		}

		if ( $operation_succeeded ) {
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

		if ( $operation_succeeded ) {
			$current_metadata_pending_snapshot = ai4seo_get_raw_option_snapshot( $metadata_pending_option );

			if ( null === $current_metadata_pending_snapshot || ! ai4seo_renew_post_id_option_transition_semaphore() ) {
				$operation_succeeded = false;
			}
		}

		if ( $operation_succeeded ) {
			$current_metadata_force_snapshot = ai4seo_get_raw_option_snapshot( $metadata_force_option );

			if ( null === $current_metadata_force_snapshot || ! ai4seo_renew_post_id_option_transition_semaphore() ) {
				$operation_succeeded = false;
			}
		}

		if ( $operation_succeeded ) {
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
							if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context ) {
								$is_eligible = $source_exists && isset( $enabled_metadata_lookup[ $source_post_type ] );
							} else {
								$is_eligible = $relevant_context_enabled
									&& $source_exists
									&& in_array( $source_post_type, ai4seo_get_supported_attachment_post_types( false ), true )
									&& in_array( $source_post_mime_type, ai4seo_get_allowed_attachment_mime_types(), true );
							}

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

	// is the cron job enabled?
	if ( ! AI4SEO_CRON_JOBS_ENABLED && wp_doing_cron() ) {
		return true;
	}

	$bulk_generation_duration = (int) ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_DURATION );

	if ( ! $bulk_generation_duration ) {
		$bulk_generation_duration = AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_BULK_GENERATION_DURATION ];
	}

	// Reclaim only expired/missing durable owners before the status heartbeat can defer this run.
	if ( ! ai4seo_recover_stale_bulk_generation_processing_claims(
		array(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
		)
	) ) {
		ai4seo_debug_message( 641476229, 'Could not safely recover stale bulk-generation Processing claims.', true );
		return false;
	}

	$persisted_enabled_post_types = array();

	// Reconcile disabled queue intent before this run can inspect account state or make an API request.
	if ( ! ai4seo_reconcile_disabled_bulk_generation_queue_entries( $persisted_enabled_post_types ) ) {
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
	$start_time                               = time();
	$cron_job_status                          = ai4seo_get_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
	$cron_job_status_update_time              = ai4seo_get_cron_job_status_update_time( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
	$last_cron_job_status_update_was_recently = $start_time - $cron_job_status_update_time < $max_tolerated_execution_time;
	$last_cron_job_is_still_processing        = in_array( $cron_job_status, array( 'processing', 'initiating' ), true );

	// if the last cron job call was too recent, we should skip this call.
	// Maybe there was an server error, and we should give the server some time to recover.
	if ( $last_cron_job_is_still_processing && $last_cron_job_status_update_was_recently ) {
		if ( $debug ) {
			ai4seo_debug_message( 267805633, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( "skipped, because we're too close to another unfinished cron job call" ) ) );
		}

		return true;
	}

	// update the last execution time of this cron job.
	ai4seo_set_last_cron_job_call_time( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, $start_time );
	ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'initiating' );

	if ( ! $persisted_enabled_post_types ) {
		if ( $debug ) {
			ai4seo_debug_message( 277251009, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because every automated generation is disabled' ) ) );
		}

		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'bulk-generation-disabled' );
		return true;
	}

	// Refresh background notifications during the 5-minute bulk generation cron.
	ai4seo_check_for_new_notifications();

	// CHECK USERS ROBHUB ACCOUNT.
	$is_robhub_account_synced = ai4seo_robhub_api()->is_account_synced();

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
	if ( ai4seo_robhub_api()->get_credits_balance() < $min_credits_cost_per_entry ) {
		if ( $debug ) {
			ai4seo_debug_message( 458928065, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because of low Credits balance' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
			)
		) ) {
			ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'finished-with-error' );
			return false;
		}

		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'low-credits-balance' );
		return true;
	}

	// check if we have a posts table analysis completed.
	$posts_table_analysis_state = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, false );

	// if we have a posts table analysis not completed, we should first help to finish it.
	if ( 'completed' !== $posts_table_analysis_state ) {
		if ( $debug ) {
			ai4seo_debug_message( 240531747, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'posts table analysis not completed -> try to continue it first' ) ) );
		}

		ai4seo_try_start_posts_table_analysis();

		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'waiting-for-posts-table-analysis' );
		return true;
	}

	$run_counter = 1;
	$GLOBALS['ai4seo_is_running_automated_generation_cron_job']    = true;
	$GLOBALS['ai4seo_deep_context_search_statement_timeout_count'] = 0;

	do {
		$made_some_progress = false;

		if ( $debug ) {
			ai4seo_debug_message( 635240362, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( "start new run: #{$run_counter}" ) ) );
		}

		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'processing' );

		// Process metadata and keyphrases before the independent attachment queue.
		$metadata_terminal_billing_error = false;
		$success                         = ai4seo_automated_metadata_generation(
			$debug,
			0,
			$metadata_terminal_billing_error
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

		// Process attachment attributes only after metadata leaves the account usable.
		$attachment_terminal_billing_error = false;
		$success                           = ai4seo_automated_attachment_attributes_generation(
			$debug,
			0,
			$attachment_terminal_billing_error
		);

		if ( $attachment_terminal_billing_error ) {
			// Stop before the coordinator can sleep or schedule another immediate pass.
			return ai4seo_stop_automated_generation_after_terminal_billing_error(
				AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES
			);
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
	if ( $made_some_progress ) {
		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'finished' );
		ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
	} else {
		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'idle' );
	}

	$GLOBALS['ai4seo_is_running_automated_generation_cron_job'] = false;

	return true;
}


/**
 * Function to automatically generate metadata for posts
 *
 * @param bool $debug The debug value.
 * @param int  $only_this_post_id The only this post id value.
 * @param bool $terminal_billing_error Receives whether an account-scoped terminal billing error stopped generation.
 * @return bool true on success, false on failure
 */
function ai4seo_automated_metadata_generation(
	$debug = false,
	$only_this_post_id = 0,
	bool &$terminal_billing_error = false
): bool {
	// Clear the output before any early return so callers never observe a prior invocation's state.
	$terminal_billing_error = false;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 374991526, 'Prevented loop', true );
		return false;
	}

	$persisted_enabled_post_types = array();

	// A direct editor generation may proceed, but no queued work can cross an unverifiable disabled-type barrier.
	if ( ! ai4seo_reconcile_disabled_bulk_generation_queue_entries( $persisted_enabled_post_types ) ) {
		return false;
	}

	$enabled_metadata_post_types = array_values( array_diff( $persisted_enabled_post_types, array( 'attachment' ) ) );

	if ( ! $only_this_post_id && ! $enabled_metadata_post_types ) {
		return false;
	}

	$active_meta_tags = ai4seo_get_active_meta_tags();

	if ( ! $active_meta_tags ) {
		if ( $debug ) {
			ai4seo_debug_message( 979105658, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no active meta tags found -> skip' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA )
		) ) {
			return false;
		}

		return false;
	}

	$metadata_credits_costs_per_post = ai4seo_calculate_metadata_credits_cost_per_post();

	// check the current credits balance, compare it to $metadata_credits_costs_per_post and if it's lower, return true.
	if ( ai4seo_robhub_api()->get_credits_balance() < $metadata_credits_costs_per_post ) {
		if ( $debug ) {
			ai4seo_debug_message( 275318901, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because of low Credits balance' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA )
		) ) {
			return false;
		}
		return false;
	}

	// handle one single post id, if given, otherwise excavate new posts with missing metadata.
	if ( $only_this_post_id ) {
		$post_id = $only_this_post_id;
	} else {
		$auto_queue_entries = ai4seo_should_auto_queue_bulk_generation_entries();

		if ( $auto_queue_entries ) {
			// try to search for posts with missing metadata.
			$queue_barrier_failed  = false;
			$got_new_pending_posts = ai4seo_excavate_post_entries_with_missing_metadata( $debug, $queue_barrier_failed );

			// An unverifiable admission must remain pending for a later run, never be consumed by this request.
			if ( $queue_barrier_failed ) {
				if ( $debug ) {
					ai4seo_debug_message( 513505111, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Auto Queue admission barrier failed -> retry later' ) ) );
				}

				return false;
			}

			$pending_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );

			// Existing manual queue entries should still be processed when automatic excavation finds nothing new.
			if ( ! $got_new_pending_posts && ! $pending_post_ids ) {
				if ( $debug ) {
					ai4seo_debug_message( 513505109, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No new pending posts found' ) ) );
				}

				// Remove Pending entries while preserving Processing work owned by another request.
				if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
					array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA )
				) ) {
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
			// skip here because we don't have any posts or pages.
			if ( $debug ) {
				ai4seo_debug_message( 872810398, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No pending posts found' ) ) );
			}

			if ( $auto_queue_entries && ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
				array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA )
			) ) {
				return false;
			}

			return false;
		}

		if ( $debug ) {
			ai4seo_debug_message( 809859626, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Found pending post(s): ' . esc_html( implode( ', ', $pending_post_ids ) ) ) ) );
		}

		// only take one post id.
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
	$processing_claimed                             = false;
	$is_force_overwrite_bulk_generation_queue_entry = false;
	$processing_claim_token                         = '';
	$pending_was_present                            = false;
	$force_was_present                              = false;

	if ( ! $only_this_post_id ) {
		$pre_claim_enabled_post_types = array();

		// Close settings changes during excavation before the selected queue entry can become Processing.
		if ( ! ai4seo_reconcile_disabled_bulk_generation_queue_entries( $pre_claim_enabled_post_types )
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

	// let's find fields to generate for this post id.
	$generated_data_read_succeeded     = false;
	$generated_data_details            = ai4seo_read_authoritative_generated_data_details_for_post(
		$post_id,
		$generated_data_read_succeeded
	);
	$available_metadata_read_succeeded = false;
	$old_available_metadata            = ai4seo_read_available_metadata(
		$post_id,
		true,
		$available_metadata_read_succeeded
	);

	// Never spend credits or derive a terminal status from an unavailable/malformed primary snapshot.
	if ( ! $generated_data_read_succeeded || ! $available_metadata_read_succeeded ) {
		ai4seo_abort_bulk_generation_processing_claim_before_generation(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			$post_id,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present
		);
		return false;
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
	$prepared_content              = ai4seo_prepare_metadata_generation_content_data(
		$post_id,
		'',
		$content_source_read_succeeded,
		$content_source_post_exists
	);

	if ( ! $content_source_read_succeeded ) {
		ai4seo_abort_bulk_generation_processing_claim_before_generation(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			$post_id,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present
		);
		return false;
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
	if ( $required_content_length < AI4SEO_TOO_SHORT_CONTENT_LENGTH ) {
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
	$post_permalink = get_permalink( $post_id );

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

	$metadata_update_succeeded = ai4seo_update_active_metadata( $post_id, $new_generated_metadata, true, $metadata_update_details );

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
			$failed_plugin_list  = $failed_plugin_names ? implode( ', ', $failed_plugin_names ) : 'selected third-party SEO integrations';

			$third_party_sync_failure_activity_details = 'Generated metadata was saved in SOOZ, but synchronization failed for: ' . $failed_plugin_list;

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
		$generated_data_save_details
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

	// Record usable partial responses as successful; unresolved eligibility is represented by coverage above.
	ai4seo_add_latest_activity_entry(
		$post_id,
		'success',
		'metadata-bulk-generated',
		(int) ( $results['credits-consumed'] ?? 0 ),
		$third_party_sync_failure_activity_details
	);

	if ( $debug ) {
		ai4seo_debug_message( 523529302, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'metadata generated for post ID: ' . $post_id . ': ' . esc_html( ai4seo_stringify( $new_generated_metadata ) ) ) ) );
	}

	return true;
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
 * Determines whether to use base64 encoding or URL for image upload based on user setting and automatic logic
 *
 * @param string $attachment_url The attachment URL to check.
 * @return bool true if base64 should be used, false if URL should be used
 */
function ai4seo_should_use_base64_image( string $attachment_url ): bool {
	global $ai4seo_allowed_image_file_type_names;

	// Get the user's preference for image upload method.
	$image_upload_method = ai4seo_get_setting( AI4SEO_SETTING_IMAGE_UPLOAD_METHOD );

	switch ( $image_upload_method ) {
		case 'base64':
			// User explicitly chose base64 - always encode and send image data directly.
			return true;

		case 'url':
			// User explicitly chose URL - always send the image URL.
			return false;

		case 'auto':
		default:
			// Auto mode: use intelligent logic to decide the best method
			// Default to URL method for better performance (smaller payload).
			$ai4seo_use_base64_image = false;

			// First check: Validate URL format
			// If URL format is invalid, we must use base64 as fallback.
			if ( ! filter_var( $attachment_url, FILTER_VALIDATE_URL ) ) {
				$ai4seo_use_base64_image = true;
			}

			// Second check: Detect localhost/development environments
			// Our API cannot access localhost URLs, so base64 is required.
			if ( ! $ai4seo_use_base64_image && ai4seo_robhub_api()->are_we_on_a_localhost_system() ) {
				$ai4seo_use_base64_image = true;
			}

			// third check: Validate file type at the end of the URL.
			if ( ! $ai4seo_use_base64_image ) {
				// Get the file extension from the URL.
				$file_extension = pathinfo( $attachment_url, PATHINFO_EXTENSION );

				// If the file extension is not in our allowed list, we must use base64.
				if ( ! in_array( strtolower( $file_extension ), $ai4seo_allowed_image_file_type_names, true ) ) {
					$ai4seo_use_base64_image = true;
				}
			}

			// Third check: Test URL accessibility (only if we haven't already decided on base64).
			if ( ! $ai4seo_use_base64_image ) {
				// Attempt to get HTTP headers to verify the URL is accessible.
				$attachment_url_headers = get_headers( $attachment_url );

				// If we can't get headers or they're malformed, the URL is not accessible.
				if ( ! $attachment_url_headers || ! is_array( $attachment_url_headers ) || ! isset( $attachment_url_headers[0] ) ) {
					$ai4seo_use_base64_image = true;
				}

				// Check for successful HTTP response (200 OK)
				// If the response is not successful, our Server won't be able to access the URL.
				if ( strpos( $attachment_url_headers[0], '200' ) === false ) {
					$ai4seo_use_base64_image = true;
				}
			}

			return $ai4seo_use_base64_image;
	}
}


/**
 * Function to automatically generate attributes for attachments
 *
 * @param bool $debug debug mode yes or no.
 * @param int  $only_this_attachment_post_id care only this attachment post id.
 * @param bool $terminal_billing_error Receives whether an account-scoped terminal billing error stopped generation.
 * @return bool true on success, false on failure
 */
function ai4seo_automated_attachment_attributes_generation(
	bool $debug = false,
	int $only_this_attachment_post_id = 0,
	bool &$terminal_billing_error = false
): bool {
	// Clear the output before any early return so callers never observe a prior invocation's state.
	$terminal_billing_error = false;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 583323686, 'Prevented loop', true );
		return false;
	}

	$persisted_enabled_post_types = array();

	// A direct editor generation may proceed, but no queued work can cross an unverifiable disabled-type barrier.
	if ( ! ai4seo_reconcile_disabled_bulk_generation_queue_entries( $persisted_enabled_post_types ) ) {
		return false;
	}

	if ( ! $only_this_attachment_post_id && ! in_array( 'attachment', $persisted_enabled_post_types, true ) ) {
		return false;
	}

	$active_attachment_attributes    = ai4seo_get_active_attachment_attributes();
	$supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

	if ( ! $active_attachment_attributes ) {
		if ( $debug ) {
			ai4seo_debug_message( 156074497, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no active meta tags found -> skip' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES )
		) ) {
			return false;
		}

		return false;
	}

	$approximate_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();

	// check the current credits balance, compare it to $approximate_cost_per_attachment_post and if it's lower, return true.
	if ( ai4seo_robhub_api()->get_credits_balance() < $approximate_cost_per_attachment_post ) {
		if ( $debug ) {
			ai4seo_debug_message( 616491274, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because of low Credits balance' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES )
		) ) {
			return false;
		}

		return false;
	}

	if ( $only_this_attachment_post_id ) {
		$attachment_post_id = $only_this_attachment_post_id;
	} else {
		$auto_queue_entries = ai4seo_should_auto_queue_bulk_generation_entries();

		if ( $auto_queue_entries ) {
			// try to search for attachment posts with missing attributes.
			$queue_barrier_failed                = false;
			$got_new_pending_attachment_post_ids = ai4seo_excavate_attachments_with_missing_attributes( $debug, $queue_barrier_failed );

			// An unverifiable admission must remain pending for a later run, never be consumed by this request.
			if ( $queue_barrier_failed ) {
				if ( $debug ) {
					ai4seo_debug_message( 466380278, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Auto Queue admission barrier failed -> retry later' ) ) );
				}

				return false;
			}

			$pending_attachment_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

			// Existing manual queue entries should still be processed when automatic excavation finds nothing new.
			if ( ! $got_new_pending_attachment_post_ids && ! $pending_attachment_post_ids ) {
				// skip here because we don't have any attachment posts.
				if ( $debug ) {
					ai4seo_debug_message( 466380276, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No pending media posts found' ) ) );
				}

				// Remove Pending entries while preserving Processing work owned by another request.
				if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
					array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES )
				) ) {
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
			// skip here because we don't have any attachment posts.
			if ( $debug ) {
				ai4seo_debug_message( 170584235, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No pending media posts found' ) ) );
			}

			if ( $auto_queue_entries && ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
				array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES )
			) ) {
				return false;
			}

			return false;
		}

		if ( $debug ) {
			ai4seo_debug_message( 968541383, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Found pending media post(s): ' . esc_html( implode( ', ', $pending_attachment_post_ids ) ) ) ) );
		}

		// only take one post id.
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
	$processing_claimed                             = false;
	$is_force_overwrite_bulk_generation_queue_entry = false;
	$processing_claim_token                         = '';
	$pending_was_present                            = false;
	$force_was_present                              = false;

	if ( ! $only_this_attachment_post_id ) {
		$pre_claim_enabled_post_types = array();

		// Close settings changes during excavation before the selected queue entry can become Processing.
		if ( ! ai4seo_reconcile_disabled_bulk_generation_queue_entries( $pre_claim_enabled_post_types )
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

	$generated_data_read_succeeded    = false;
	$generated_data_details           = ai4seo_read_authoritative_generated_data_details_for_post(
		$attachment_post_id,
		$generated_data_read_succeeded
	);
	$attachment_source_read_succeeded = false;
	$attachment_post_exists           = false;
	$attachment_source_snapshot       = ai4seo_read_attachment_attribute_source_snapshot(
		$attachment_post_id,
		$attachment_source_read_succeeded,
		$attachment_post_exists
	);

	// Never spend credits or derive a terminal status from an unavailable/malformed primary snapshot.
	if ( ! $generated_data_read_succeeded || ! $attachment_source_read_succeeded ) {
		ai4seo_abort_bulk_generation_processing_claim_before_generation(
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
			$attachment_post_id,
			$processing_claim_token,
			$pending_was_present,
			$force_was_present
		);
		return false;
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

	// Record usable partial responses as successful; unresolved eligibility is represented by coverage above.
	ai4seo_add_latest_activity_entry(
		$attachment_post_id,
		'success',
		'attachment-attributes-bulk-generated',
		(int) ( $results['credits-consumed'] ?? 0 )
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
 * @return string|false Prepared query, or false when its typed bindings are invalid.
 */
function ai4seo_prepare_metadata_auto_queue_candidate_query_for_post_types(
	array $post_ids,
	array $post_types,
	string $post_date_filter,
	string $post_date_gmt,
	string $bulk_generation_order
) {
	// Ordered modes need only two rows; random mode must expose the complete bounded eligibility set to PHP.
	$result_limit = in_array( $bulk_generation_order, array( 'oldest', 'newest' ), true ) ? 2 : count( $post_ids );

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
 * @return string|false Prepared query, or false when its typed bindings are invalid.
 */
function ai4seo_prepare_attachment_auto_queue_candidate_query(
	array $post_ids,
	array $post_types,
	array $mime_types,
	string $post_date_filter,
	string $post_date_gmt,
	string $bulk_generation_order
) {
	// Ordered modes need only two rows; random mode must expose the complete bounded eligibility set to PHP.
	$result_limit = in_array( $bulk_generation_order, array( 'oldest', 'newest' ), true ) ? 2 : count( $post_ids );

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
 * Normalizes post IDs from an option value without changing their queue order.
 *
 * @param mixed $option_value Raw option value or decoded post-ID collection.
 * @return array Ordered unique positive integer post IDs.
 */
function ai4seo_normalize_ordered_auto_queue_post_ids( $option_value ): array {
	return ai4seo_normalize_option_post_id_collection( $option_value );
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
 * Atomically removes post IDs from one ordered queue option.
 *
 * @param string $option_name Queue option name.
 * @param array  $post_ids Post IDs to remove.
 * @return bool True after a successful removal or authoritative no-op.
 */
function ai4seo_remove_post_ids_from_option_atomically( string $option_name, array $post_ids ): bool {
	$option_name = sanitize_key( $option_name );
	$post_ids    = ai4seo_normalize_ordered_auto_queue_post_ids( $post_ids );

	if ( '' === $option_name || ! $post_ids ) {
		return true;
	}

	$membership_changed = false;

	// The caller already owns the shared transition fence, so reuse only the bounded single-option CAS.
	return ai4seo_mutate_post_id_option_membership( $option_name, array(), $post_ids, $membership_changed );
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
 * Atomically queues automatic candidates while retaining their existing force-overwrite markers.
 *
 * @param array  $post_ids Post IDs selected by automatic queue excavation.
 * @param string $context Bulk generation queue context.
 * @param int    $maximum_pending_post_ids Queue capacity.
 * @return array|null IDs admitted by this call, or null when the queue mutation failed.
 */
function ai4seo_add_bounded_auto_queue_post_ids_preserving_force_overwrite(
	array $post_ids,
	string $context,
	int $maximum_pending_post_ids
): ?array {
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
		$read_eligibility_snapshot = static function () use ( $queue_options, $required_option_keys ): ?array {
			$option_memberships = array();

			foreach ( $required_option_keys as $required_option_key ) {
				if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
					return null;
				}

				$option_snapshot = ai4seo_get_raw_option_snapshot( $queue_options[ $required_option_key ] );

				if ( null === $option_snapshot ) {
					return null;
				}

				$option_memberships[ $required_option_key ] = array_fill_keys(
					ai4seo_normalize_ordered_auto_queue_post_ids( $option_snapshot['value'] ),
					true
				);
			}

			return $option_memberships;
		};
		$filter_eligible_post_ids  = static function ( array $candidate_post_ids, array $option_memberships ): array {
			$eligible_post_ids = array();

			foreach ( $candidate_post_ids as $candidate_post_id ) {
				if ( ! isset( $option_memberships['missing'][ $candidate_post_id ] ) ) {
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
 * @param bool      $debug                If true, debug information will be printed.
 * @param bool|null $queue_barrier_failed Set to true only when admission could not be verified safely.
 * @return bool
 */
function ai4seo_excavate_post_entries_with_missing_metadata( bool $debug = false, ?bool &$queue_barrier_failed = null ): bool {
	global $wpdb;

	$queue_barrier_failed = false;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 730356485, 'Prevented loop', true );
		return false;
	}

	$settings_read_succeeded = false;
	$persisted_post_types    = ai4seo_read_persisted_enabled_bulk_generation_post_types( $settings_read_succeeded );

	if ( ! $settings_read_succeeded ) {
		$queue_barrier_failed = true;
		ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
		return false;
	}

	$supported_post_types = ai4seo_get_supported_post_types();

	// Find enabled metadata types from authoritative persisted settings before consulting account state.
	$enabled_bulk_generation_post_types = array_values(
		array_intersect( $supported_post_types, $persisted_post_types )
	);

	if ( ! $enabled_bulk_generation_post_types ) {
		if ( $debug ) {
			ai4seo_debug_message( 894341588, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No automation enabled' ) ) );
		}

		return false;
	}

	$metadata_credits_costs_per_post = ai4seo_calculate_metadata_credits_cost_per_post();

	// check the current credits balance, compare it to $metadata_credits_costs_per_post and if it's lower, return true.
	if ( ai4seo_robhub_api()->get_credits_balance() < $metadata_credits_costs_per_post ) {
		if ( $debug ) {
			ai4seo_debug_message( 690700036, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because of low Credits balance' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA )
		) ) {
			$queue_barrier_failed = true;
		}

		return false;
	}

	// check the number of already pending posts.
	$pending_metadata_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );

	if ( $pending_metadata_post_ids && count( $pending_metadata_post_ids ) >= 2 ) {
		// skip here because we already have two posts pending, that are going to be processed
		// better keep the amount of post ids low if the user suddenly stops the automation.
		if ( $debug ) {
			ai4seo_debug_message( 693756939, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Already >= 2 posts pending -> skip' ) ) );
		}

		return true;
	}

	// only these posts we have to look for.
	$missing_metadata_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME );

	if ( ! $missing_metadata_post_ids ) {
		// skip here because we don't have any posts or pages.
		if ( $debug ) {
			ai4seo_debug_message( 411642134, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No posts found' ) ) );
		}

		return false;
	}

	$missing_metadata_post_ids = array_unique( $missing_metadata_post_ids );

	// additionally, these posts we have to ignore.
	$failed_metadata_post_ids                = ai4seo_get_post_ids_from_option( AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME );
	$processing_metadata_post_ids            = ai4seo_get_post_ids_from_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME );
	$hidden_metadata_post_ids                = ai4seo_get_post_ids_from_option( AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME );
	$auto_queue_disallowed_metadata_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME );

	// Exclude entries that are already queued, failed, hidden, or explicitly disallowed for Auto Queue.
	$exclude_this_post_ids = array_merge( $pending_metadata_post_ids, $processing_metadata_post_ids, $failed_metadata_post_ids, $hidden_metadata_post_ids, $auto_queue_disallowed_metadata_post_ids );

	// check if all values are numeric.
	foreach ( $exclude_this_post_ids as &$this_excluded_post_id ) {
		$this_excluded_post_id = absint( $this_excluded_post_id );
	}

	// make sure that $exclude_this_post_ids is an array and not empty (otherwise the query will fail).
	if ( ! $exclude_this_post_ids ) {
		$exclude_this_post_ids = array( 0 );
	}

	$exclude_this_post_ids = array_unique( $exclude_this_post_ids );

	$candidate_post_ids = array_values( array_diff( $missing_metadata_post_ids, $exclude_this_post_ids ) );

	if ( ! $candidate_post_ids ) {
		if ( $debug ) {
			ai4seo_debug_message( 345541132, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No candidates after exclusions' ) ) );
		}

		return false;
	}

	$new_pending_post_ids = array();

	// check bulk generation order.
	$bulk_generation_order = ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_ORDER );

	// Apply order in PHP (avoid ORDER BY RAND(), keep SQL simple).
	switch ( $bulk_generation_order ) {
		case 'oldest':
			sort( $candidate_post_ids, SORT_NUMERIC );
			break;
		case 'newest':
			rsort( $candidate_post_ids, SORT_NUMERIC );
			break;
		case 'random':
		default:
			shuffle( $candidate_post_ids );
			break;
	}

	// Resolve the optional date filter before any candidate query can reach the database.
	$bulk_generation_date_filter_query_state = ai4seo_resolve_bulk_generation_date_filter(
		ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER ),
		ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME )
	);

	// Stop before candidate SQL when stored date-filter state cannot be represented safely.
	if ( false === $bulk_generation_date_filter_query_state ) {
		return false;
	}

	// Unpack the validated state once so query parameters retain the established concise vocabulary.
	$post_date_filter = $bulk_generation_date_filter_query_state['filter'];
	$post_date_gmt    = $bulk_generation_date_filter_query_state['post_date_gmt'];

	$database_chunk_size = ai4seo_get_database_chunk_size();

	// Query every enabled post type together so the globally ordered candidate sequence remains authoritative.
	$candidate_post_ids_chunks = array_chunk( $candidate_post_ids, $database_chunk_size );

	foreach ( $candidate_post_ids_chunks as $this_candidate_post_ids_chunk ) {
		$candidate_query = ai4seo_prepare_metadata_auto_queue_candidate_query_for_post_types(
			$this_candidate_post_ids_chunk,
			$enabled_bulk_generation_post_types,
			$post_date_filter,
			$post_date_gmt,
			$bulk_generation_order
		);

		if ( false === $candidate_query ) {
			ai4seo_debug_message( 984321678, 'Could not prepare the metadata Auto Queue candidate query.', true );
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares every binding; mutable queue, status, and date state requires one fresh bounded candidate read.
		$eligible_post_ids = $wpdb->get_col( $candidate_query );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321678, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}

		// Random eligibility queries are unordered, so restore the shuffled candidate sequence in PHP.
		$eligible_post_ids = ai4seo_order_auto_queue_eligible_post_ids(
			$this_candidate_post_ids_chunk,
			(array) $eligible_post_ids,
			$bulk_generation_order
		);

		if ( $eligible_post_ids ) {
			$new_pending_post_ids = array_merge( $new_pending_post_ids, $eligible_post_ids );

			if ( count( $new_pending_post_ids ) >= 2 ) {
				$new_pending_post_ids = array_slice( $new_pending_post_ids, 0, 2 );
				break;
			}
		}
	}

	if ( ! $new_pending_post_ids && ! $pending_metadata_post_ids ) {
		// skip here because we don't have any posts or pages.
		if ( $debug ) {
			ai4seo_debug_message( 345541131, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No posts found' ) ) );
		}

		return false;
	}

	// Queue discovered metadata through the helper so partial force-overwrite runs retain their mode.
	if ( $new_pending_post_ids ) {
		$admitted_pending_post_ids = ai4seo_add_bounded_auto_queue_post_ids_preserving_force_overwrite(
			$new_pending_post_ids,
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			2
		);

		if ( null === $admitted_pending_post_ids ) {
			$queue_barrier_failed = true;
			return false;
		}

		$new_pending_post_ids = $admitted_pending_post_ids;

		if ( $debug ) {
			ai4seo_debug_message( 588880985, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'New pending post(s): ' . esc_textarea( implode( ', ', $new_pending_post_ids ) ) ) ) );
		}
	}

	return true;
}


/**
 * Function to excavate attachments with missing attributes.
 * Is used by the cronjob "ai4seo_automated_generation_cron_job"
 *
 * @param bool      $debug                If true, debug information will be printed.
 * @param bool|null $queue_barrier_failed Set to true only when admission could not be verified safely.
 * @return bool
 */
function ai4seo_excavate_attachments_with_missing_attributes( bool $debug = false, ?bool &$queue_barrier_failed = null ): bool {
	global $wpdb;

	$queue_barrier_failed = false;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 548885475, 'Prevented loop', true );
		return false;
	}

	$settings_read_succeeded = false;
	$persisted_post_types    = ai4seo_read_persisted_enabled_bulk_generation_post_types( $settings_read_succeeded );

	if ( ! $settings_read_succeeded ) {
		$queue_barrier_failed = true;
		ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
		return false;
	}

	// Disabled attachment automation cannot consult credits or any other RobHub state.
	if ( ! in_array( 'attachment', $persisted_post_types, true ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 457348107, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No automation enabled' ) ) );
		}

		return false;
	}

	$allowed_attachment_mime_types   = ai4seo_get_allowed_attachment_mime_types();
	$supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

	$approximate_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();

	// check the current credits balance, compare it to $approximate_cost_per_attachment_post and if it's lower, return false.
	if ( ai4seo_robhub_api()->get_credits_balance() < $approximate_cost_per_attachment_post ) {
		if ( $debug ) {
			ai4seo_debug_message( 791110592, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because of low Credits balance' ) ) );
		}

		// Remove Pending entries while preserving Processing work owned by another request.
		if ( ! ai4seo_remove_bulk_generation_pending_queue_snapshot_memberships(
			array( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES )
		) ) {
			$queue_barrier_failed = true;
		}

		return false;
	}

	// check the number of already planned posts.
	$pending_attributes_attachment_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	if ( $pending_attributes_attachment_post_ids && count( $pending_attributes_attachment_post_ids ) >= 2 ) {
		// skip here because we already have two attachment posts that are going to be processed
		// better keep the amount of post ids low if the user suddenly stops the automation.
		if ( $debug ) {
			ai4seo_debug_message( 977145156, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Already >= 2 media posts to generate -> skip' ) ) );
		}

		return true;
	}

	// only consider this attachment posts with missing post ids.
	$missing_attachment_attributes_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	if ( ! $missing_attachment_attributes_post_ids ) {
		// skip here because we don't have any attachment posts with missing attributes.
		if ( $debug ) {
			ai4seo_debug_message( 325744145, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No media posts found' ) ) );
		}

		return false;
	}

	$missing_attachment_attributes_post_ids = array_unique( $missing_attachment_attributes_post_ids );

	// additionally, exclude these attachment posts.
	$processing_attachment_attributes_post_ids            = ai4seo_get_post_ids_from_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
	$failed_attachment_attributes_post_ids                = ai4seo_get_post_ids_from_option( AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
	$hidden_attachment_attributes_post_ids                = ai4seo_get_post_ids_from_option( AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
	$auto_queue_disallowed_attachment_attributes_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	// Exclude entries that are already queued, failed, hidden, or explicitly disallowed for Auto Queue.
	$exclude_this_attachment_post_ids = array_merge( $pending_attributes_attachment_post_ids, $processing_attachment_attributes_post_ids, $failed_attachment_attributes_post_ids, $hidden_attachment_attributes_post_ids, $auto_queue_disallowed_attachment_attributes_post_ids );

	// check if all values are numeric.
	foreach ( $exclude_this_attachment_post_ids as &$this_excluded_attachment_post_id ) {
		$this_excluded_attachment_post_id = absint( $this_excluded_attachment_post_id );
	}

	// make sure that $exclude_this_post_ids is an array and not empty (otherwise the query will fail).
	if ( ! $exclude_this_attachment_post_ids ) {
		$exclude_this_attachment_post_ids = array( 0 );
	}

	$exclude_this_attachment_post_ids = array_unique( $exclude_this_attachment_post_ids );

	// check bulk generation order.
	$bulk_generation_order = ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_ORDER );

	// Resolve the optional date filter before any candidate query can reach the database.
	$bulk_generation_date_filter_query_state = ai4seo_resolve_bulk_generation_date_filter(
		ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER ),
		ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME )
	);

	// Stop before candidate SQL when stored date-filter state cannot be represented safely.
	if ( false === $bulk_generation_date_filter_query_state ) {
		return false;
	}

	// Unpack the validated state once so query parameters retain the established concise vocabulary.
	$post_date_filter = $bulk_generation_date_filter_query_state['filter'];
	$post_date_gmt    = $bulk_generation_date_filter_query_state['post_date_gmt'];

	// Bind supported attachment post types as placeholders instead of concatenating a post_type clause.
	// An empty sentinel keeps the prepared IN clause valid and deliberately matches no post type.
	if ( ! $supported_attachment_post_types ) {
		$supported_attachment_post_types = array( '' );
	}

	$supported_attachment_post_types = array_slice( $supported_attachment_post_types, 0, 256 );

	// remove excluded IDs in PHP first, then chunk only candidates.
	$candidate_attachment_post_ids = array_values( array_diff( $missing_attachment_attributes_post_ids, $exclude_this_attachment_post_ids ) );

	if ( ! $candidate_attachment_post_ids ) {
		if ( $debug ) {
			ai4seo_debug_message( 742528302, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No candidates after exclusions' ) ) );
		}

		return false;
	}

	// Apply order in PHP (avoid ORDER BY RAND(), keep SQL simple).
	switch ( $bulk_generation_order ) {
		case 'oldest':
			sort( $candidate_attachment_post_ids, SORT_NUMERIC );
			break;
		case 'newest':
			rsort( $candidate_attachment_post_ids, SORT_NUMERIC );
			break;
		case 'random':
		default:
			shuffle( $candidate_attachment_post_ids );
			break;
	}

	$new_pending_attachment_post_ids = array();

	$database_chunk_size = ai4seo_get_database_chunk_size();

	$candidate_attachment_post_ids_chunks = array_chunk( $candidate_attachment_post_ids, $database_chunk_size );

	foreach ( $candidate_attachment_post_ids_chunks as $this_candidate_post_ids_chunk ) {
		$candidate_query = ai4seo_prepare_attachment_auto_queue_candidate_query(
			$this_candidate_post_ids_chunk,
			$supported_attachment_post_types,
			$allowed_attachment_mime_types,
			$post_date_filter,
			$post_date_gmt,
			$bulk_generation_order
		);

		if ( false === $candidate_query ) {
			ai4seo_debug_message( 984321679, 'Could not prepare the attachment Auto Queue candidate query.', true );
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares every binding; mutable queue, status, MIME, and date state requires one fresh bounded candidate read.
		$eligible_post_ids = $wpdb->get_col( $candidate_query );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321679, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}

		// Random eligibility queries are unordered, so restore the shuffled candidate sequence in PHP.
		$eligible_post_ids = ai4seo_order_auto_queue_eligible_post_ids(
			$this_candidate_post_ids_chunk,
			(array) $eligible_post_ids,
			$bulk_generation_order
		);

		if ( $eligible_post_ids ) {
			$new_pending_attachment_post_ids = array_merge( $new_pending_attachment_post_ids, $eligible_post_ids );

			if ( count( $new_pending_attachment_post_ids ) >= 2 ) {
				$new_pending_attachment_post_ids = array_slice( $new_pending_attachment_post_ids, 0, 2 );
				break;
			}
		}
	}

	if ( ! $new_pending_attachment_post_ids && ! $pending_attributes_attachment_post_ids ) {
		// skip here because we don't have any posts or pages.
		if ( $debug ) {
			ai4seo_debug_message( 742528301, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No new media found' ) ) );
		}

		return false;
	}

	// Queue discovered media through the helper so partial force-overwrite runs retain their mode.
	if ( $new_pending_attachment_post_ids ) {
		$admitted_pending_attachment_post_ids = ai4seo_add_bounded_auto_queue_post_ids_preserving_force_overwrite(
			$new_pending_attachment_post_ids,
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
			2
		);

		if ( null === $admitted_pending_attachment_post_ids ) {
			$queue_barrier_failed = true;
			return false;
		}

		$new_pending_attachment_post_ids = $admitted_pending_attachment_post_ids;

		if ( $debug ) {
			ai4seo_debug_message( 209429876, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Added pending media: ' . ( implode( ', ', $new_pending_attachment_post_ids ) ) ) ) );
		}
	}

	return true;
}


// endregion
// ___________________________________________________________________________________________.
