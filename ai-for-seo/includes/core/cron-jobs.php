<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region CRON JOBS (CRONJOBS) ================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

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

// =========================================================================================== \\

/**
 * Function to un-schedule cron jobs
 *
 * @return void
 */
function ai4seo_un_schedule_cron_jobs() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	wp_clear_scheduled_hook( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
	wp_clear_scheduled_hook( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME );
	ai4seo_unschedule_active_metadata_migration_v235_cron_job();
}

// =========================================================================================== \\

/**
 * Checks whether the v235 active metadata migration is completed.
 *
 * @return bool
 */
function ai4seo_is_active_metadata_migration_v235_completed(): bool {
	global $wpdb;

	$active_metadata_migration_v235_state = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE );

	if ( 'completed' !== $active_metadata_migration_v235_state ) {
		return false;
	}

	$active_metadata_migration_v235_started_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME );

	if ( $active_metadata_migration_v235_started_time > 0 ) {
		return true;
	}

	if ( ai4seo_has_legacy_active_metadata_rows() ) {
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE, 'idle', false );
		return false;
	}

	if ( $wpdb->last_error ) {
		return false;
	}

	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME, time(), false );
	return true;
}

// =========================================================================================== \\

/**
 * Starts the v235 active metadata migration and schedules its cron job.
 *
 * @return void
 */
function ai4seo_start_active_metadata_migration_v235(): void {
	global $wpdb;

	if ( ! ai4seo_has_legacy_active_metadata_rows() && ! $wpdb->last_error ) {
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

// =========================================================================================== \\

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
		add_filter( 'cron_schedules', 'ai4seo_add_cron_job_intervals' );
	}

	if ( ! wp_next_scheduled( AI4SEO_ACTIVE_METADATA_MIGRATION_V235_CRON_JOB_NAME ) ) {
		wp_schedule_event( time() + 10, 'five_minutes', AI4SEO_ACTIVE_METADATA_MIGRATION_V235_CRON_JOB_NAME );
	}
}

// =========================================================================================== \\

/**
 * Unschedules the temporary v235 active metadata migration cron job.
 *
 * @return void
 */
function ai4seo_unschedule_active_metadata_migration_v235_cron_job(): void {
	wp_clear_scheduled_hook( AI4SEO_ACTIVE_METADATA_MIGRATION_V235_CRON_JOB_NAME );
}

// =========================================================================================== \\

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

// =========================================================================================== \\

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
	if ( in_array( $cron_job_status, array( 'processing', 'initiating' ) ) ) {
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

// =========================================================================================== \\

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

// =========================================================================================== \\

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
	$last_specific_cronjob_calls                   = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS );
	$last_specific_cronjob_calls[ $cron_job_name ] = $time;
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS, $last_specific_cronjob_calls );

	return true;
}

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

	$all_cronjob_job_status                   = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST );
	$all_cronjob_job_status[ $cron_job_name ] = $status;
	return ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST, $all_cronjob_job_status );
}

// =========================================================================================== \\

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

	$all_cronjob_job_status_time                   = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES );
	$all_cronjob_job_status_time[ $cron_job_name ] = time();

	return ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES, $all_cronjob_job_status_time );
}

// =========================================================================================== \\

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
	$last_cron_job_is_still_processing        = in_array( $cron_job_status, array( 'processing', 'initiating' ) );

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

		// remove all processing and pending ids.
		ai4seo_update_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, '' );
		ai4seo_update_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, '' );
		ai4seo_update_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );
		ai4seo_update_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );

		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'low-credits-balance' );
		return true;
	}

	// if no bulk generation is enabled, we can savely return here.
	if ( ! ai4seo_is_any_bulk_generation_enabled() ) {
		if ( $debug ) {
			ai4seo_debug_message( 277251009, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because every automated generation is disabled' ) ) );
		}

		ai4seo_set_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 'bulk-generation-disabled' );
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

		// metadata & keyphrase.
		$success = ai4seo_automated_metadata_generation( $debug );

		if ( $success ) {
			$made_some_progress = true;
		}

		// attachments.
		$success = ai4seo_automated_attachment_attributes_generation( $debug );

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

	// workaround: empty all leftover processing ids (only relevant if the generation was aborted for an unknown reason).
	ai4seo_update_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, array() );
	ai4seo_update_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, array() );

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

// =========================================================================================== \\

/**
 * Function to automatically generate metadata for posts
 *
 * @param bool $debug The debug value.
 * @param int  $only_this_post_id The only this post id value.
 * @return bool true on success, false on failure
 */
function ai4seo_automated_metadata_generation( $debug = false, $only_this_post_id = 0 ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 374991526, 'Prevented loop', true );
		return false;
	}

	$active_meta_tags = ai4seo_get_active_meta_tags();

	if ( ! $active_meta_tags ) {
		if ( $debug ) {
			ai4seo_debug_message( 979105658, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no active meta tags found -> skip' ) ) );
		}

		// remove all processing and pending ids.
		ai4seo_update_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, '' );
		ai4seo_update_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, '' );

		return false;
	}

	$metadata_credits_costs_per_post = ai4seo_calculate_metadata_credits_cost_per_post();

	// check the current credits balance, compare it to $metadata_credits_costs_per_post and if it's lower, return true.
	if ( ai4seo_robhub_api()->get_credits_balance() < $metadata_credits_costs_per_post ) {
		if ( $debug ) {
			ai4seo_debug_message( 275318901, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because of low Credits balance' ) ) );
		}

		// remove all processing and pending ids.
		ai4seo_update_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, '' );
		ai4seo_update_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, '' );
		return false;
	}

	// handle one single post id, if given, otherwise excavate new posts with missing metadata.
	if ( $only_this_post_id ) {
		$post_id = $only_this_post_id;
	} else {
		$auto_queue_entries = ai4seo_should_auto_queue_bulk_generation_entries();

		if ( $auto_queue_entries ) {
			// try to search for posts with missing metadata.
			$got_new_pending_posts = ai4seo_excavate_post_entries_with_missing_metadata( $debug );
			$pending_post_ids      = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );

			// Existing manual queue entries should still be processed when automatic excavation finds nothing new.
			if ( ! $got_new_pending_posts && ! $pending_post_ids ) {
				if ( $debug ) {
					ai4seo_debug_message( 513505109, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No new pending posts found' ) ) );
				}

				// remove all processing and pending ids.
				ai4seo_update_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, '' );
				ai4seo_update_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, '' );
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

			// remove all processing and pending ids.
			ai4seo_update_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, '' );

			if ( $auto_queue_entries ) {
				ai4seo_update_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, '' );
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

	if ( $debug ) {
		ai4seo_debug_message( 439931261, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'trying to generate metadata for #' . esc_html( $post_id ) ) ) );
	}

	// let's find fields to generate for this post id.
	$generate_this_fields                           = $active_meta_tags;
	$old_generated_metadata                         = ai4seo_read_generated_data_from_post_meta( $post_id );
	$old_available_metadata                         = ai4seo_read_available_metadata( $post_id );
	$overwrite_existing_metadata                    = ai4seo_get_setting( AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA );
	$focus_keyphrase_behavior                       = ai4seo_get_setting( AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA );
	$is_force_overwrite_bulk_generation_queue_entry = ai4seo_is_bulk_generation_queue_entry_force_overwrite(
		$post_id,
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA
	);

	if ( ! is_array( $overwrite_existing_metadata ) ) {
		$overwrite_existing_metadata = array();
	}

	// handle focus keyphrase behavior when existing meta title/description are present (SEO Autopilot)
	// consider both meta title and meta description as not generated, so that we can regenerate them
	// however, if we don't generate the meta title or description for some reason, the focus keyphrase generation will be skipped later.
	if ( in_array( 'focus-keyphrase', $generate_this_fields ) && AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE === $focus_keyphrase_behavior ) {
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
			if ( in_array( $this_metadata_identifier, $generate_this_fields ) && $this_metadata_value ) {
				$this_index = array_search( $this_metadata_identifier, $generate_this_fields );
				unset( $generate_this_fields[ $this_index ] );
			}
		}

		// nothing left to generate -> skip.
		if ( ! $generate_this_fields ) {
			if ( $debug ) {
				ai4seo_debug_message( 594533434, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no missing metadata found for post-id' ) ) );
			}

			// all metadata is already generated.
			ai4seo_remove_post_ids_from_all_generation_status_options( $post_id );
			ai4seo_add_post_ids_to_option( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_id );
			return true;
		}
	}

	// check for available metadata (from 3rd party seo plugins)
	// and remove meta tags from the missing metadata array that are already available, if we don't want to overwrite them.
	$is_post_fully_covered = true;

	if ( ! $is_force_overwrite_bulk_generation_queue_entry ) {
		foreach ( $generate_this_fields as $this_entry_index => $this_metadata_identifier ) {
			if ( isset( $old_available_metadata[ $this_metadata_identifier ] )
				&& $old_available_metadata[ $this_metadata_identifier ]
				&& ! in_array( $this_metadata_identifier, $overwrite_existing_metadata ) ) {
				unset( $generate_this_fields[ $this_entry_index ] );
				continue;
			}

			if ( ! isset( $old_available_metadata[ $this_metadata_identifier ] ) || ! $old_available_metadata[ $this_metadata_identifier ] ) {
				$is_post_fully_covered = false;
			}
		}

		// if we skip or regenerate the focus keyphrase, but neither meta title nor meta description is in the generation list, we should also skip the focus keyphrase generation.
		if ( ( AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP === $focus_keyphrase_behavior || AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE === $focus_keyphrase_behavior )
			&& in_array( 'focus-keyphrase', $generate_this_fields )
			&& ! in_array( 'meta-title', $generate_this_fields )
			&& ! in_array( 'meta-description', $generate_this_fields ) ) {
			unset( $generate_this_fields[ array_search( 'focus-keyphrase', $generate_this_fields ) ] );
		}

		// nothing left to generate -> skip.
		if ( ! $generate_this_fields ) {
			if ( $debug ) {
				ai4seo_debug_message( 777726188, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no missing metadata found for post-id' ) ) );
			}

			// all metadata is already generated.
			ai4seo_remove_post_ids_from_all_generation_status_options( $post_id );
			ai4seo_add_post_ids_to_option( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_id );
			return true;
		}

		// make sure to abort, if we have full coverage and don't want to generate metadata for fully covered entries.
		$generate_metadata_for_fully_covered_entries = ai4seo_do_generate_metadata_for_fully_covered_entries();

		if ( $is_post_fully_covered && ! $generate_metadata_for_fully_covered_entries ) {
			if ( $debug ) {
				ai4seo_debug_message( 245485518, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'full metadata coverage found and generation for fully covered entries is disabled -> skip' ) ) );
			}

			ai4seo_remove_post_ids_from_all_generation_status_options( $post_id );
			ai4seo_add_post_ids_to_option( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_id );
			return true;
		}
	}

	// mark post as being processed.
	ai4seo_add_post_ids_to_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, $post_id );

	// first, let's get a summary of the content.
	$post_content = ai4seo_get_condensed_post_content_from_database( $post_id );
	$post_context = $post_content;
	ai4seo_add_post_context( $post_id, $post_context, false, false );

	if ( ! $post_content && $post_context ) {
		$post_content = $post_context;
	}

	$post_content = sanitize_text_field( $post_content );
	$post_context = sanitize_text_field( $post_context );

	// if we have original content -> go ahead.
	$content_length = ai4seo_mb_strlen( trim( $post_content . ' ' . $post_context ) );

	// check if content is at least AI4SEO_TOO_SHORT_CONTENT_LENGTH characters long.
	if ( $content_length < AI4SEO_TOO_SHORT_CONTENT_LENGTH ) {
		ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, 'Post content is too short for post ID: ' . $post_id, $debug );
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Post content is too short' );
		return true;
	}

	// check if content is not larger than AI4SEO_MAX_TOTAL_CONTENT_SIZE characters.
	if ( $content_length > AI4SEO_MAX_TOTAL_CONTENT_SIZE ) {
		ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, 'Post content is too long for post ID: ' . $post_id, $debug );
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Post content is too long' );
		return true;
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

	if ( $third_party_keyphrase ) {
		$robhub_api_call_parameters['keyphrase'] = $third_party_keyphrase;
	}

	$robhub_api_call_parameters['trigger']         = 'automated';
	$robhub_api_call_parameters['website_context'] = ai4seo_get_website_context();
	$robhub_api_call_parameters['post_context']    = $post_context;

	// url.
	$post_permalink = get_permalink( $post_id );

	if ( $post_permalink ) {
		$robhub_api_call_parameters['content_url'] = $post_permalink;
	}

	// collect and build field instructions.
	$field_instructions       = array();
	$metadata_prefixes        = ai4seo_get_setting( AI4SEO_SETTING_METADATA_PREFIXES );
	$metadata_suffixes        = ai4seo_get_setting( AI4SEO_SETTING_METADATA_SUFFIXES );
	$placeholder_replacements = ai4seo_get_metadata_placeholder_replacements( $post_id );

	// Resolve the title placeholder separately so cron field instructions match manual metadata generation.
	$post_title_for_placeholders = sanitize_text_field( get_the_title( $post_id ) );

	foreach ( AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_details ) {
		$this_to_generate = in_array( $this_metadata_identifier, $generate_this_fields );
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

	$results = ai4seo_robhub_api()->call( 'ai4seo/generate-all-metadata', $robhub_api_call_parameters );

	// CHECK RESULTS.

	if ( ! ai4seo_robhub_api()->was_call_successful( $results ) ) {
		$error_message = $results['message'] ?? 'Generation with API endpoint failed for post ID: ' . $post_id;

		if ( isset( $results['code'] ) ) {
			$error_message .= ' (Error #' . sanitize_text_field( $results['code'] ) . ')';
		}

		ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, $error_message . ( $debug ? ': ' . ai4seo_stringify( $results ) : '' ), $debug );
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, $error_message );

		ai4seo_debug_message( 4133326, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Generation with API endpoint failed for post ID: ' . $post_id . ': ' . ai4seo_stringify( $results ) ) ), true );
		return false;
	}

	// The API call succeeded; validate its payload before saving any usable metadata fields.

	$raw_new_generated_metadata = $results['data'] ?? array();

	if ( empty( $raw_new_generated_metadata ) || ! is_array( $raw_new_generated_metadata ) ) {
		ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, 'Generation with API endpoint failed for post ID: ' . $post_id . ( $debug ? ': ' . ai4seo_stringify( $results ) : '' ), $debug );
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
		ai4seo_handle_failed_metadata_generation(
			$post_id,
			__FUNCTION__,
			'Generation returned no usable metadata for post ID: ' . $post_id,
			$debug
		);
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
	$this_success = ai4seo_update_active_metadata( $post_id, $new_generated_metadata, true );

	if ( ! $this_success ) {
		ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, 'Could not save generated metadata for post ID: ' . $post_id, $debug );
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Could not save generated metadata' );
		return false;
	}

	// Persist returned provenance before coverage changes so a failed snapshot cannot be reported as successful.
	$this_success = ai4seo_save_generated_data_to_postmeta(
		$post_id,
		$new_generated_metadata,
		true,
		0,
		$unresolved_generation_fields
	);

	if ( ! $this_success ) {
		ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, 'Could not save generated metadata provenance for post ID: ' . $post_id, $debug );
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Could not save generated metadata provenance' );
		return false;
	}

	// Save the content snapshot only after generated provenance exists; it controls future change detection.
	$this_success = ai4seo_save_post_content_summary_to_postmeta( $post_id, $post_content );

	if ( ! $this_success ) {
		ai4seo_handle_failed_metadata_generation( $post_id, __FUNCTION__, 'Could not save generated metadata content summary for post ID: ' . $post_id, $debug );
		ai4seo_add_latest_activity_entry( $post_id, 'error', 'metadata-bulk-generated', 0, 'Could not save generated metadata content summary' );
		return false;
	}

	// Rebuild exclusive coverage: partial results stay Missing; complete results become Fully Covered and Generated.
	ai4seo_remove_post_ids_from_all_generation_status_options( $post_id );

	if ( $unresolved_generation_fields ) {
		ai4seo_add_post_ids_to_option( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME, $post_id );

		// Preserve force-overwrite intent so omitted existing fields remain eligible on a later queued run.
		if ( $is_force_overwrite_bulk_generation_queue_entry ) {
			ai4seo_add_post_ids_to_option(
				AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
				$post_id
			);
		}
	} else {
		ai4seo_add_post_ids_to_option( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_id );
		ai4seo_add_post_ids_to_option( AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME, $post_id );
	}

	// Record usable partial responses as successful; unresolved eligibility is represented by coverage above.
	ai4seo_add_latest_activity_entry(
		$post_id,
		'success',
		'metadata-bulk-generated',
		(int) ( $results['credits-consumed'] ?? 0 )
	);

	if ( $debug ) {
		ai4seo_debug_message( 523529302, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'metadata generated for post ID: ' . $post_id . ': ' . esc_html( ai4seo_stringify( $new_generated_metadata ) ) ) ) );
	}

	return true;
}

// =========================================================================================== \\

/**
 * Helps handle failed metadata generation by removing the post id from all generation status options and adding it to the failed ones
 *
 * @param int    $post_id the attachment post id.
 * @param string $function_name the name of the function that failed.
 * @param string $error_message the error message.
 * @param bool   $debug if true, debug information will be printed.
 * @return void
 */
function ai4seo_handle_failed_metadata_generation( int $post_id, string $function_name = '', string $error_message = '', bool $debug = false ) {
	if ( $function_name ) {
		ai4seo_debug_message( 585453895, esc_html( $function_name ) . ' >' . esc_html( ai4seo_stringify( $error_message ) ), true );
	} else {
		ai4seo_debug_message( 791605560, $error_message, true );
	}

	ai4seo_remove_post_ids_from_all_generation_status_options( $post_id );
	ai4seo_add_post_ids_to_option( AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME, $post_id );
}

// =========================================================================================== \\

/**
 * Determines whether to use base64 encoding or URL for image upload based on user setting and automatic logic
 *
 * @param string $attachment_url The attachment URL to check
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
				if ( ! in_array( strtolower( $file_extension ), $ai4seo_allowed_image_file_type_names ) ) {
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

// =========================================================================================== \\

/**
 * Function to automatically generate attributes for attachments
 *
 * @param bool $debug debug mode yes or no
 * @param int  $only_this_attachment_post_id care only this attachment post id
 * @return bool true on success, false on failure
 */
function ai4seo_automated_attachment_attributes_generation( bool $debug = false, int $only_this_attachment_post_id = 0 ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 583323686, 'Prevented loop', true );
		return false;
	}

	$active_attachment_attributes    = ai4seo_get_active_attachment_attributes();
	$supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

	if ( ! $active_attachment_attributes ) {
		if ( $debug ) {
			ai4seo_debug_message( 156074497, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no active meta tags found -> skip' ) ) );
		}

		// remove all processing and pending ids.
		ai4seo_update_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );
		ai4seo_update_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );

		return false;
	}

	$approximate_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();

	// check the current credits balance, compare it to $approximate_cost_per_attachment_post and if it's lower, return true.
	if ( ai4seo_robhub_api()->get_credits_balance() < $approximate_cost_per_attachment_post ) {
		if ( $debug ) {
			ai4seo_debug_message( 616491274, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because of low Credits balance' ) ) );
		}

		// remove all processing and pending ids.
		ai4seo_update_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );
		ai4seo_update_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );

		return false;
	}

	if ( $only_this_attachment_post_id ) {
		$attachment_post_id = $only_this_attachment_post_id;
	} else {
		$auto_queue_entries = ai4seo_should_auto_queue_bulk_generation_entries();

		if ( $auto_queue_entries ) {
			// try to search for attachment posts with missing attributes.
			$got_new_pending_attachment_post_ids = ai4seo_excavate_attachments_with_missing_attributes( $debug );
			$pending_attachment_post_ids         = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

			// Existing manual queue entries should still be processed when automatic excavation finds nothing new.
			if ( ! $got_new_pending_attachment_post_ids && ! $pending_attachment_post_ids ) {
				// skip here because we don't have any attachment posts.
				if ( $debug ) {
					ai4seo_debug_message( 466380276, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No pending media posts found' ) ) );
				}

				// remove all processing and pending ids.
				ai4seo_update_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );
				ai4seo_update_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );

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

			ai4seo_update_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );

			if ( $auto_queue_entries ) {
				ai4seo_update_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );
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

	if ( $debug ) {
		ai4seo_debug_message( 537974883, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'going to generate media attributes for #' . esc_html( $attachment_post_id ) ) ) );
	}

	$generate_this_fields                           = $active_attachment_attributes;
	$old_generated_attachment_attributes            = ai4seo_read_generated_data_from_post_meta( $attachment_post_id );
	$old_available_attachment_attributes            = ai4seo_read_available_attachment_attributes( $attachment_post_id );
	$is_force_overwrite_bulk_generation_queue_entry = ai4seo_is_bulk_generation_queue_entry_force_overwrite(
		$attachment_post_id,
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES
	);

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

			if ( in_array( $this_attachment_attribute_identifier, $generate_this_fields ) && $this_attachment_attribute_value ) {
				$this_index = array_search( $this_attachment_attribute_identifier, $generate_this_fields );
				unset( $generate_this_fields[ $this_index ] );
			}
		}

		// nothing left to generate -> skip.
		if ( ! $generate_this_fields ) {
			if ( $debug ) {
				ai4seo_debug_message( 375893908, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'no missing attachment attributes found for post-id' ) ) );
			}

			// all metadata is already generated.
			ai4seo_remove_post_ids_from_all_generation_status_options( $attachment_post_id );
			ai4seo_add_post_ids_to_option( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id );
			return true;
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
				&& ! in_array( $this_attachment_attribute_identifier, $overwrite_existing_attachment_attributes ) ) {
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

			// all metadata is already generated.
			ai4seo_remove_post_ids_from_all_generation_status_options( $attachment_post_id );
			ai4seo_add_post_ids_to_option( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id );
			return true;
		}

		// make sure to abort, if we have full coverage and don't want to generate attachment attribute for fully covered entries.
		$generate_attachment_attributes_for_fully_covered_entries = ai4seo_get_setting( AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES );

		if ( $is_attachment_post_fully_covered && ! $generate_attachment_attributes_for_fully_covered_entries ) {
			if ( $debug ) {
				ai4seo_debug_message( 334900672, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'full attachment atributes coverage found and generation for fully covered entries is disabled -> skip' ) ) );
			}

			ai4seo_remove_post_ids_from_all_generation_status_options( $attachment_post_id );
			ai4seo_add_post_ids_to_option( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id );
			return true;
		}
	}

	// mark post as being processed.
	ai4seo_add_post_ids_to_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id );

	// there are missing attachment attributes -> generate it
	// first, let's get the wp_post entry for more checks.
	$attachment_post               = get_post( $attachment_post_id );
	$attachment_post_type          = $attachment_post ? $attachment_post->post_type : '';
	$attachment_post_mime_type     = ai4seo_get_attachment_post_mime_type( $attachment_post_id );
	$allowed_attachment_mime_types = ai4seo_get_allowed_attachment_mime_types();

	// Check existence before relying on the safely initialized post type for queued entries.
	if ( ! $attachment_post || ! in_array( $attachment_post_type, $supported_attachment_post_types ) ) {
		ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, 'Post is not a media for media post ID: ' . $attachment_post_id, $debug );
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, 'Post is not a media' );
		return true;
	}

	// check if it's one of the allowed mime types.
	if ( ! in_array( $attachment_post_mime_type, $allowed_attachment_mime_types, true ) ) {
		ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, 'Mime type not supported for media post ID: ' . $attachment_post_id, $debug );
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, 'Mime type not supported' );
		return true;
	}

	// check url of the attachment.
	$attachment_url = ai4seo_get_attachment_url( $attachment_post_id );

	if ( ! $attachment_url ) {
		ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, 'Media URL not found for media post ID: ' . $attachment_post_id, $debug );
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, 'Media URL not found' );
		return true;
	}

	// Resolve the same full-versus-intermediate source used by manual generation before choosing its transport.
	$attachment_image_source = ai4seo_get_attachment_generation_image_source(
		$attachment_post_id,
		$attachment_url,
		$attachment_post_mime_type
	);

	if ( ! $attachment_image_source ) {
		ai4seo_handle_failed_attachment_generation(
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
		return true;
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
		$this_to_generate = in_array( $this_attachment_attribute_identifier, $generate_this_fields );
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

	// RobHub owns repair attempts, so issue one request through the shared image transport.
	$results = ai4seo_call_attachment_attributes_generation_api( $attachment_image_source, $robhub_api_call_parameters );

	// CHECK RESULTS.

	if ( ! ai4seo_robhub_api()->was_call_successful( $results ?? false ) ) {
		$error_message = $results['message'] ?? 'Generation with API endpoint failed for attachment post ID: ' . $attachment_post_id;

		if ( isset( $results['code'] ) ) {
			$error_message .= ' (Error #' . sanitize_text_field( $results['code'] ) . ')';
		}

		ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, $error_message . ( $debug ? ': ' . ai4seo_stringify( $results ) : '' ), $debug );
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, $error_message );

		ai4seo_debug_message( 4133326, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'API call failed for media post ID: ' . $attachment_post_id . ': ' . ai4seo_stringify( $results ) ) ), true );
		return false;
	}

	// The API call succeeded; validate its payload before saving any usable media attributes.

	$raw_new_attachment_attributes = $results['data'] ?? array();

	if ( empty( $raw_new_attachment_attributes ) || ! is_array( $raw_new_attachment_attributes ) ) {
		ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, 'Could not interpret data for media post ID: ' . $attachment_post_id . ( $debug ? ': ' . ai4seo_stringify( $results ) : '' ), $debug );
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
		ai4seo_handle_failed_attachment_generation(
			$attachment_post_id,
			__FUNCTION__,
			'Generation returned no usable media attributes for post ID: ' . $attachment_post_id,
			$debug
		);
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
	$this_success = ai4seo_update_attachment_attributes( $attachment_post_id, $new_attachment_attributes, true );

	if ( ! $this_success ) {
		ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, 'Could not save generated media attributes for post ID: ' . $attachment_post_id, $debug );
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, 'Could not save generated media attributes' );
		return false;
	}

	// Persist returned provenance before coverage changes so a failed snapshot cannot be reported as successful.
	$this_success = ai4seo_save_generated_data_to_postmeta(
		$attachment_post_id,
		$new_attachment_attributes,
		true,
		0,
		$unresolved_generation_fields
	);

	if ( ! $this_success ) {
		ai4seo_handle_failed_attachment_generation( $attachment_post_id, __FUNCTION__, 'Could not save generated media attribute provenance for post ID: ' . $attachment_post_id, $debug );
		ai4seo_add_latest_activity_entry( $attachment_post_id, 'error', 'attachment-attributes-bulk-generated', 0, 'Could not save generated media attribute provenance' );
		return false;
	}

	// Rebuild exclusive coverage: partial results stay Missing; complete results become Fully Covered and Generated.
	ai4seo_remove_post_ids_from_all_generation_status_options( $attachment_post_id );

	if ( $unresolved_generation_fields ) {
		ai4seo_add_post_ids_to_option( AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id );

		// Preserve force-overwrite intent so omitted existing fields remain eligible on a later queued run.
		if ( $is_force_overwrite_bulk_generation_queue_entry ) {
			ai4seo_add_post_ids_to_option(
				AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				$attachment_post_id
			);
		}
	} else {
		ai4seo_add_post_ids_to_option( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id );
		ai4seo_add_post_ids_to_option( AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id );
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

// =========================================================================================== \\

/**
 * Helps handle failed attachment generation by removing the post id from all generation status options and adding it to the failed ones
 *
 * @param int    $attachment_post_id the attachment post id.
 * @param string $function_name the name of the function that failed.
 * @param string $error_message the error message.
 * @param bool   $debug if true, debug information will be printed.
 * @return void
 */
function ai4seo_handle_failed_attachment_generation( int $attachment_post_id, string $function_name = '', string $error_message = '', bool $debug = false ) {
	if ( $error_message ) {
		if ( $function_name ) {
			ai4seo_debug_message( 689393850, esc_html( $function_name ) . ' >' . esc_html( ai4seo_stringify( $error_message ) ), true );
		} else {
			ai4seo_debug_message( 250362054, $error_message, true );
		}
	}

	ai4seo_remove_post_ids_from_all_generation_status_options( $attachment_post_id );
	ai4seo_add_post_ids_to_option( AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id );
}

// =========================================================================================== \\

/**
 * Add automatically discovered entries to a generation queue without losing retained force-overwrite markers.
 *
 * @param array  $post_ids Post IDs selected by automatic queue excavation.
 * @param string $context  Bulk generation queue context.
 * @return void
 */
function ai4seo_add_auto_queue_post_ids_preserving_force_overwrite( array $post_ids, string $context ): void {
	// Resolve context-specific options so one preservation sequence serves metadata and media queues.
	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

	// Pending insertion clears Force Overwrite as contradictory, so capture retained markers before queueing.
	$force_overwrite_post_ids = array_values(
		array_intersect(
			$post_ids,
			ai4seo_get_post_ids_from_option( $queue_options['force_overwrite'] )
		)
	);

	// Use the normal Pending transition so every other contradictory generation state is still cleared.
	ai4seo_add_post_ids_to_option( $queue_options['pending'], $post_ids );

	// Restore only pre-existing markers so ordinary automatically queued entries remain non-force.
	if ( $force_overwrite_post_ids ) {
		ai4seo_add_post_ids_to_option( $queue_options['force_overwrite'], $force_overwrite_post_ids );
	}
}

// =========================================================================================== \\

/**
 * Function to excavate posts, pages, products etc. with missing metadata.
 * Is used by the cronjob "ai4seo_automated_generation_cron_job" to find posts and pages that are missing metadata
 *
 * @param bool $debug if true, debug information will be printed
 * @return bool
 */
function ai4seo_excavate_post_entries_with_missing_metadata( bool $debug = false ): bool {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 730356485, 'Prevented loop', true );
		return false;
	}

	$metadata_credits_costs_per_post = ai4seo_calculate_metadata_credits_cost_per_post();

	// check the current credits balance, compare it to $metadata_credits_costs_per_post and if it's lower, return true.
	if ( ai4seo_robhub_api()->get_credits_balance() < $metadata_credits_costs_per_post ) {
		if ( $debug ) {
			ai4seo_debug_message( 690700036, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'skipped, because of low Credits balance' ) ) );
		}

		// remove all processing and pending ids.
		ai4seo_update_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, '' );
		ai4seo_update_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, '' );

		return false;
	}

	$supported_post_types = ai4seo_get_supported_post_types();

	// find out if the automation is enabled.
	$enabled_bulk_generation_post_types = array();

	foreach ( $supported_post_types as $this_post_type ) {
		if ( ai4seo_is_bulk_generation_enabled( $this_post_type ) ) {
			$enabled_bulk_generation_post_types[] = $this_post_type;
		}
	}

	// if automation is completely disabled -> return.
	if ( ! $enabled_bulk_generation_post_types ) {
		if ( $debug ) {
			ai4seo_debug_message( 894341588, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No automation enabled' ) ) );
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

	$new_pending_post_ids = $pending_metadata_post_ids;

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

	// check if we should only generate metadata for new or existing posts.
	$bulk_generation_new_or_existing_filter                     = ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER );
	$bulk_generation_new_or_existing_filter_reference_timestamp = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME );

	$database_chunk_size = ai4seo_get_database_chunk_size();

	// go through each enabled automated generation post types and read at least two post ids.
	foreach ( $enabled_bulk_generation_post_types as $this_post_type ) {
		if ( count( $new_pending_post_ids ) >= 2 ) {
			break;
		}

		// Normalize the optional date filter before choosing the matching prepared query variant below.
		$post_date_filter = 'both';
		$post_date_gmt    = '';

		if ( 'both' !== $bulk_generation_new_or_existing_filter && $bulk_generation_new_or_existing_filter_reference_timestamp && is_numeric( $bulk_generation_new_or_existing_filter_reference_timestamp ) ) {
			$post_date_filter = ( 'new' === $bulk_generation_new_or_existing_filter ) ? 'new' : 'existing';
			$post_date_gmt    = ai4seo_gmdate( 'Y-m-d H:i:s', (int) $bulk_generation_new_or_existing_filter_reference_timestamp );
		}

		// chunk candidates and query only current post type, stop once we have 2 IDs total.
		$candidate_post_ids_chunks = array_chunk( $candidate_post_ids, $database_chunk_size );
		foreach ( $candidate_post_ids_chunks as $this_candidate_post_ids_chunk ) {
			if ( count( $new_pending_post_ids ) >= 2 ) {
				break;
			}

			$post_ids_placeholders = implode( ', ', array_fill( 0, count( $this_candidate_post_ids_chunk ), '%d' ) );

			// Parameter order mirrors the shared WHERE block: post type, IDs, statuses, and date-filter checks.
			$query_parameters = array_merge(
				array( $this_post_type ),
				$this_candidate_post_ids_chunk,
				array(
					'publish',
					'future',
					$post_date_filter,
					$post_date_filter,
					$post_date_gmt,
					$post_date_filter,
					$post_date_gmt,
				)
			);

			// Keep ordering as literal SQL variants so PluginCheck does not have to trust a dynamic ORDER BY fragment.
			if ( 'oldest' === $bulk_generation_order ) {
				$this_new_pending_post_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID
                        FROM {$wpdb->posts}
                        WHERE post_type = %s
                        AND ID IN ( {$post_ids_placeholders} )
                        AND post_status IN ( %s, %s )
                        AND (
                            %s = 'both'
                            OR ( %s = 'new' AND post_date_gmt > %s )
                            OR ( %s = 'existing' AND post_date_gmt <= %s )
                        )
                        ORDER BY ID ASC
                        LIMIT %d",
						...array_merge(
							$query_parameters,
							array( 2 )
						)
					)
				);
			} elseif ( 'newest' === $bulk_generation_order ) {
				$this_new_pending_post_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID
                        FROM {$wpdb->posts}
                        WHERE post_type = %s
                        AND ID IN ( {$post_ids_placeholders} )
                        AND post_status IN ( %s, %s )
                        AND (
                            %s = 'both'
                            OR ( %s = 'new' AND post_date_gmt > %s )
                            OR ( %s = 'existing' AND post_date_gmt <= %s )
                        )
                        ORDER BY ID DESC
                        LIMIT %d",
						...array_merge(
							$query_parameters,
							array( 2 )
						)
					)
				);
			} else {
				$this_new_pending_post_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID
                        FROM {$wpdb->posts}
                        WHERE post_type = %s
                        AND ID IN ( {$post_ids_placeholders} )
                        AND post_status IN ( %s, %s )
                        AND (
                            %s = 'both'
                            OR ( %s = 'new' AND post_date_gmt > %s )
                            OR ( %s = 'existing' AND post_date_gmt <= %s )
                        )
                        LIMIT %d",
						...array_merge(
							$query_parameters,
							array( 2 )
						)
					)
				);
			}

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321678, 'Database error: ' . $wpdb->last_error, true );
				return false;
			}

			if ( $this_new_pending_post_ids ) {
				$new_pending_post_ids = array_merge( $new_pending_post_ids, $this_new_pending_post_ids );

				if ( count( $new_pending_post_ids ) >= 2 ) {
					$new_pending_post_ids = array_slice( $new_pending_post_ids, 0, 2 );
					break;
				}
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
		ai4seo_add_auto_queue_post_ids_preserving_force_overwrite(
			$new_pending_post_ids,
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA
		);

		if ( $debug ) {
			ai4seo_debug_message( 588880985, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'New pending post(s): ' . esc_textarea( implode( ', ', $new_pending_post_ids ) ) ) ) );
		}
	}

	return true;
}

// =========================================================================================== \\

/**
 * Function to excavate attachments with missing attributes.
 * Is used by the cronjob "ai4seo_automated_generation_cron_job"
 *
 * @param bool $debug if true, debug information will be printed
 * @return bool
 */
function ai4seo_excavate_attachments_with_missing_attributes( bool $debug = false ): bool {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 548885475, 'Prevented loop', true );
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

		// remove all processing and pending ids.
		ai4seo_update_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );
		ai4seo_update_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, '' );

		return false;
	}

	// is automation disabled, skip.
	if ( ! ai4seo_is_bulk_generation_enabled( 'attachment' ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 457348107, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'No automation enabled' ) ) );
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

	// perform esc_sql on every entry of $ai4seo_supported_attachment_mime_types.
	$only_this_mime_types_sql_terms = array();

	foreach ( $allowed_attachment_mime_types as $this_mime_type ) {
		$only_this_mime_types_sql_terms[] = esc_sql( $this_mime_type );
	}

	// check bulk generation order.
	$bulk_generation_order = ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_ORDER );

	// check if we should only generate media attributes for new or existing media files.
	$bulk_generation_new_or_existing_filter                     = ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER );
	$bulk_generation_new_or_existing_filter_reference_timestamp = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME );

	// Normalize the optional date filter before choosing the matching prepared query variant below.
	$post_date_filter = 'both';
	$post_date_gmt    = '';

	if ( 'both' !== $bulk_generation_new_or_existing_filter && $bulk_generation_new_or_existing_filter_reference_timestamp && is_numeric( $bulk_generation_new_or_existing_filter_reference_timestamp ) ) {
		$post_date_filter = ( 'new' === $bulk_generation_new_or_existing_filter ) ? 'new' : 'existing';
		$post_date_gmt    = ai4seo_gmdate( 'Y-m-d H:i:s', (int) $bulk_generation_new_or_existing_filter_reference_timestamp );
	}

	// Bind supported attachment post types as placeholders instead of concatenating a post_type clause.
	$supported_attachment_post_types = array_slice( $supported_attachment_post_types ?: array( '' ), 0, 256 );
	$post_type_placeholders          = implode( ', ', array_fill( 0, count( $supported_attachment_post_types ), '%s' ) );

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
		$post_ids_placeholders   = implode( ', ', array_fill( 0, count( $this_candidate_post_ids_chunk ), '%d' ) );
		$mime_types_placeholders = implode( ', ', array_fill( 0, count( $only_this_mime_types_sql_terms ), '%s' ) );

		// Parameter order mirrors the shared WHERE block: post types, IDs, statuses, MIME types, and date-filter checks.
		$query_parameters = array_merge(
			$supported_attachment_post_types,
			$this_candidate_post_ids_chunk,
			array( 'publish', 'future', 'inherit' ),
			$only_this_mime_types_sql_terms,
			array(
				$post_date_filter,
				$post_date_filter,
				$post_date_gmt,
				$post_date_filter,
				$post_date_gmt,
			)
		);

		// Keep ordering as literal SQL variants so PluginCheck does not have to trust a dynamic ORDER BY fragment.
		if ( 'oldest' === $bulk_generation_order ) {
			$chunk_result = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID
                    FROM {$wpdb->posts}
                    WHERE post_type IN ( {$post_type_placeholders} )
                    AND ID IN ( {$post_ids_placeholders} )
                    AND post_status IN ( %s, %s, %s )
                    AND post_mime_type IN ( {$mime_types_placeholders} )
                    AND (
                        %s = 'both'
                        OR ( %s = 'new' AND post_date_gmt > %s )
                        OR ( %s = 'existing' AND post_date_gmt <= %s )
                    )
                    ORDER BY ID ASC
                    LIMIT %d ",
					...array_merge(
						$query_parameters,
						array( 2 )
					)
				)
			);
		} elseif ( 'newest' === $bulk_generation_order ) {
			$chunk_result = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID
                    FROM {$wpdb->posts}
                    WHERE post_type IN ( {$post_type_placeholders} )
                    AND ID IN ( {$post_ids_placeholders} )
                    AND post_status IN ( %s, %s, %s )
                    AND post_mime_type IN ( {$mime_types_placeholders} )
                    AND (
                        %s = 'both'
                        OR ( %s = 'new' AND post_date_gmt > %s )
                        OR ( %s = 'existing' AND post_date_gmt <= %s )
                    )
                    ORDER BY ID DESC
                    LIMIT %d ",
					...array_merge(
						$query_parameters,
						array( 2 )
					)
				)
			);
		} else {
			$chunk_result = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID
                    FROM {$wpdb->posts}
                    WHERE post_type IN ( {$post_type_placeholders} )
                    AND ID IN ( {$post_ids_placeholders} )
                    AND post_status IN ( %s, %s, %s )
                    AND post_mime_type IN ( {$mime_types_placeholders} )
                    AND (
                        %s = 'both'
                        OR ( %s = 'new' AND post_date_gmt > %s )
                        OR ( %s = 'existing' AND post_date_gmt <= %s )
                    )
                    LIMIT %d ",
					...array_merge(
						$query_parameters,
						array( 2 )
					)
				)
			);
		}

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321679, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}

		if ( $chunk_result ) {
			$new_pending_attachment_post_ids = array_merge( $new_pending_attachment_post_ids, $chunk_result );

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
		ai4seo_add_auto_queue_post_ids_preserving_force_overwrite(
			$new_pending_attachment_post_ids,
			AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES
		);

		if ( $debug ) {
			ai4seo_debug_message( 209429876, esc_html( __FUNCTION__ ) . ' >' . esc_html( ai4seo_stringify( 'Added pending media: ' . ( implode( ', ', $new_pending_attachment_post_ids ) ) ) ) );
		}
	}

	return true;
}


// endregion
// ___________________________________________________________________________________________.
