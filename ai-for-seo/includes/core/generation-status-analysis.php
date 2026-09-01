<?php
/**
 * Generation-status analysis and posts-table indexing.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region GENERATION STATUS ANALYSIS ============================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.


/**
 * Returns the current number of entries in the wp_posts table.
 *
 * Uses the environmental variable cache when available to avoid repeated COUNT queries.
 *
 * @return int Number of wp_posts entries, or -1 if the count query fails.
 */
function ai4seo_get_current_posts_table_entries_count(): int {
	global $wpdb;

	if ( ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES ) ) {
		return (int) ai4seo_read_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES
		);
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The result is cached in the environmental-variable store below.
	$current_num_posts_table_entries = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts}" );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321671, 'Database error: ' . $wpdb->last_error, true );
		return -1;
	}

	ai4seo_update_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES,
		$current_num_posts_table_entries,
		true,
		HOUR_IN_SECONDS
	);

	return $current_num_posts_table_entries;
}

/**
 * Returns the current number of entries in the wp_postmeta table.
 *
 * Uses the environmental variable cache when available to avoid repeated COUNT queries.
 *
 * @return int Number of wp_postmeta entries, or -1 if the count query fails.
 */
function ai4seo_get_current_postmeta_table_entries_count(): int {
	global $wpdb;

	if ( ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTMETA_TABLE_ENTRIES ) ) {
		return (int) ai4seo_read_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTMETA_TABLE_ENTRIES
		);
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The result is cached in the environmental-variable store below.
	$current_num_postmeta_table_entries = (int) $wpdb->get_var( "SELECT COUNT(meta_id) FROM {$wpdb->postmeta}" );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321709, 'Database error: ' . $wpdb->last_error, true );
		return -1;
	}

	ai4seo_update_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTMETA_TABLE_ENTRIES,
		$current_num_postmeta_table_entries,
		true,
		HOUR_IN_SECONDS * 4
	);

	return $current_num_postmeta_table_entries;
}

/**
 * Returns whether the current request is an ordinary full dashboard page load.
 *
 * AJAX dashboard refreshes are handled separately, while cron retains its established init behavior.
 *
 * @return bool Whether this request is a full dashboard page load.
 */
function ai4seo_is_full_dashboard_request(): bool {
	// Only ordinary page loads are deferred from init to count-aware dashboard orchestration.
	return ! wp_doing_cron()
		&& ! wp_doing_ajax()
		&& ai4seo_is_plugin_page_active( 'dashboard' );
}


/**
 * Returns whether a small dashboard refresh request should force a fresh performance analysis.
 *
 * @param int $current_num_posts_table_entries Current number of wp_posts entries.
 * @return bool Whether to refresh analysis during this dashboard request.
 */
function ai4seo_should_refresh_performance_analysis_for_small_dashboard_request( int $current_num_posts_table_entries ): bool {
	if ( $current_num_posts_table_entries < 0 ) {
		return false;
	}

	if ( wp_doing_cron() ) {
		return false;
	}

	// Keep automatic AJAX refreshes limited to the trusted dashboard HTML endpoint.
	if ( wp_doing_ajax() ) {
		$is_dashboard_refresh_request = ai4seo_is_dashboard_refresh_ajax_request();
	} else {
		$is_dashboard_refresh_request = ai4seo_is_plugin_page_active( 'dashboard' );
	}

	if ( ! $is_dashboard_refresh_request ) {
		return false;
	}

	// One-batch sites are cheap enough to keep dashboard counters in sync during page and AJAX refreshes.
	return ( $current_num_posts_table_entries <= AI4SEO_POST_TABLE_ANALYSIS_BATCH_SIZE );
}

/**
 * Retry durable summary-rebuild scheduling during an ordinary plugin request.
 *
 * @return bool True when no rebuild is required or its durable scheduling contract is satisfied.
 */
function ai4seo_maybe_schedule_required_generation_status_summary_rebuild(): bool {
	$summary_rebuild_state = ai4seo_read_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
		false
	);

	return 'idle' === $summary_rebuild_state
		|| ai4seo_schedule_generation_status_summary_rebuild( false );
}


/**
 * Run performance analysis and advance the observed posts-table count only after checked success.
 *
 * @param int           $current_posts_table_entries Current authoritative posts-table count.
 * @param array         $analysis_arguments Arguments for the analysis callback.
 * @param callable|null $analysis_callback Optional deterministic callback for contract tests.
 * @return bool True only when analysis and the environmental progress write both succeed.
 */
function ai4seo_analyze_and_commit_current_posts_table_entries_count(
	int $current_posts_table_entries,
	array $analysis_arguments = array(),
	?callable $analysis_callback = null
): bool {
	if ( $current_posts_table_entries < 0 ) {
		return false;
	}

	if ( null === $analysis_callback ) {
		$analysis_callback = 'ai4seo_analyze_plugin_performance';
	}

	if ( ! (bool) call_user_func_array( $analysis_callback, $analysis_arguments ) ) {
		return false;
	}

	return ai4seo_update_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES,
		$current_posts_table_entries
	);
}


/**
 * Checks if the plugin performance analysis should be run.
 *
 * @return void
 */
function ai4seo_check_for_performance_analysis() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// A failed scheduling attempt leaves a durable state for the next ordinary plugin request to retry.
	if ( ! ai4seo_maybe_schedule_required_generation_status_summary_rebuild() ) {
		ai4seo_debug_message( 175943823, 'Could not schedule the required generation-status summary rebuild.', true );
	}

	// compare cached and real count of posts.
	$last_known_num_posts_table_entries = (int) ai4seo_read_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES
	);

	$current_num_posts_table_entries = ai4seo_get_current_posts_table_entries_count();

	if ( $current_num_posts_table_entries < 0 ) {
		return;
	}

	$posts_table_analysis_state        = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE );
	$is_dashboard_refresh_ajax_request = ai4seo_is_dashboard_refresh_ajax_request();

	// Init defers ordinary dashboard pages so this count-aware stage can choose fresh versus resume.
	$is_full_dashboard_request = ai4seo_is_full_dashboard_request();

	// Small dashboard refreshes should show counters from a fresh analysis immediately.
	if ( ai4seo_should_refresh_performance_analysis_for_small_dashboard_request( $current_num_posts_table_entries ) ) {
		ai4seo_analyze_and_commit_current_posts_table_entries_count(
			$current_num_posts_table_entries,
			array( false, true )
		);
		return;
	}

	if ( $last_known_num_posts_table_entries !== $current_num_posts_table_entries ) {
		ai4seo_analyze_and_commit_current_posts_table_entries_count( $current_num_posts_table_entries );
		return;
	}

	// Resume unfinished work on either trusted dashboard route before applying the completed-run throttle.
	if (
		'completed' !== $posts_table_analysis_state
		&& ( $is_full_dashboard_request || $is_dashboard_refresh_ajax_request )
	) {
		ai4seo_try_start_posts_table_analysis( false );
		return;
	}

	$last_performance_analysis_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME );
	$num_batches_needed             = ceil( $current_num_posts_table_entries / AI4SEO_POST_TABLE_ANALYSIS_BATCH_SIZE );
	$analyze_performance_interval   = AI4SEO_ANALYZE_PERFORMANCE_INTERVAL;

	if ( $num_batches_needed > 1 ) {
		$analyze_performance_interval += ( $num_batches_needed * 60 ); // add extra time based on number of batches needed.
	}

	// mainly useful if cron job didn't run for a while or on first plugin activation.
	if ( $last_performance_analysis_time <= time() - $analyze_performance_interval ) {
		ai4seo_analyze_plugin_performance();
	}
}

/**
 * Checks if an automatic performance analysis should be skipped for large sites.
 *
 * Manual refreshes can force the analysis even inside the automatic throttle window.
 *
 * @param bool $force Whether to bypass the automatic throttle.
 * @return bool True if the automatic analysis should be skipped.
 */
function ai4seo_should_skip_automatic_performance_analysis( bool $force = false ): bool {
	if ( $force ) {
		return false;
	}

	$current_num_posts_table_entries = ai4seo_get_current_posts_table_entries_count();

	if ( $current_num_posts_table_entries < 0 ) {
		return true;
	}

	if ( $current_num_posts_table_entries < AI4SEO_LARGE_SITE_POSTS_THRESHOLD ) {
		return false;
	}

	$posts_table_analysis_state = ai4seo_read_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE,
		false
	);

	if ( 'completed' !== $posts_table_analysis_state ) {
		return false;
	}

	$last_performance_analysis_time = (int) ai4seo_read_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME,
		false
	);

	if ( ! $last_performance_analysis_time ) {
		return false;
	}

	return ( $last_performance_analysis_time > time() - AI4SEO_LARGE_SITE_AUTOMATIC_ANALYSIS_INTERVAL );
}


/**
 * Analyzes plugin performance data such as the amount of content "AI for SEO" can generate metadata for.
 *
 * @param bool $debug if true, debug information will be printed.
 * @param bool $force if true, bypass analysis throttling.
 * @param bool $allow_trusted_admin_mutation if true, trusted admin mutations may start the analysis.
 * @param bool $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @return bool True on success, false on failure.
 */
function ai4seo_analyze_plugin_performance(
	bool $debug = false,
	bool $force = false,
	bool $allow_trusted_admin_mutation = false,
	bool $allow_debug_heavy_db_operations = false
): bool {
	if ( ai4seo_should_skip_automatic_performance_analysis( $force ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 935259184, esc_html( __FUNCTION__ ) . ' > Skipping automatic performance analysis due to large-site daily throttle.' );
		}

		return true;
	}

	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return false;
	}

	ai4seo_set_cron_job_status( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, 'processing' );

	// Sync the connected account before analysis when the background account snapshot is stale.
	$last_account_sync = ai4seo_robhub_api()->read_environmental_variable( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC );

	if ( $last_account_sync < time() - ai4seo_robhub_api()::BACKGROUND_ACCOUNT_SYNC_INTERVAL ) {
		ai4seo_sync_robhub_account( 'plugin_analyse', true );
	}

	// Start the posts-table coverage refresh through the shared analysis queue.
	if ( ! ai4seo_try_start_posts_table_analysis( true, $debug, $force, $allow_trusted_admin_mutation, $allow_debug_heavy_db_operations ) ) {
		ai4seo_set_cron_job_status( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, 'finished' );
		return false;
	}

	// Record the analysis attempt after queuing so automatic runs can respect the throttle.
	if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME, time() ) ) {
		ai4seo_set_cron_job_status( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, 'finished' );
		return false;
	}

	ai4seo_set_cron_job_status( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, 'finished' );
	return true;
}

/**
 * Start unfinished analysis from init unless the full dashboard will choose fresh-versus-resume later.
 *
 * Cron and AJAX retain the established init behavior. The bootstrap TOS gate decides whether this
 * callback is registered at all, while non-dashboard requests continue through the normal start gate.
 *
 * @return bool True when dashboard work was deferred or the ordinary start path succeeded.
 */
function ai4seo_maybe_start_posts_table_analysis_on_init(): bool {
	// Defer ordinary dashboard pages until the later count-aware orchestration stage.
	if ( ai4seo_is_full_dashboard_request() ) {
		return true;
	}

	// Cron, AJAX, and non-dashboard requests retain the established init start path.
	return ai4seo_try_start_posts_table_analysis( false );
}


/**
 * Tries to start the posts table analysis.
 *
 * @param bool $restart_if_completed if true, the analysis will be restarted even if it was already completed.
 * @param bool $debug if true, debug information will be printed.
 * @param bool $force if true, bypasses the short analysis run throttle.
 * @param bool $allow_trusted_admin_mutation if true, trusted admin mutations may start the analysis.
 * @param bool $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @param bool $reset_before_run if true, clear persisted analysis state after ownership is acquired.
 * @return bool True when the request was safely handled, false on ownership or persistence failure.
 */
function ai4seo_try_start_posts_table_analysis(
	bool $restart_if_completed = false,
	bool $debug = false,
	bool $force = false,
	bool $allow_trusted_admin_mutation = false,
	bool $allow_debug_heavy_db_operations = false,
	bool $reset_before_run = false
): bool {
	// Do not read or mutate analysis state unless this request is allowed to start analysis work.
	if ( ! ai4seo_can_start_posts_table_analysis( $allow_trusted_admin_mutation ) ) {
		return false;
	}

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 197197425, 'Prevented loop', true );
		return false;
	}

	return (bool) ai4seo_run_with_ignore_user_abort(
		'ai4seo_run_posts_table_analysis_task',
		array( $restart_if_completed, $debug, $force, $allow_debug_heavy_db_operations, $reset_before_run )
	);
}


/**
 * Checks whether post table analysis is allowed for the current request.
 *
 * @param bool $debug if true, debug information will be printed.
 * @param bool $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @return bool Whether post table analysis may run.
 */
function ai4seo_is_posts_table_analysis_possible( bool $debug = false, bool $allow_debug_heavy_db_operations = false ): bool {
	// Heavy DB operations are the global gate for post table analysis.
	$is_heavy_db_operations_disabled = ai4seo_get_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS );

	if ( ! $is_heavy_db_operations_disabled ) {
		return true;
	}

	// Only the nonce-backed debug dispatcher may bypass the saved heavy DB pause setting.
	return ( $debug && $allow_debug_heavy_db_operations );
}


/**
 * Runs one posts table analysis task.
 *
 * @param bool $restart_if_completed if true, the analysis will be restarted even if it was already completed.
 * @param bool $debug if true, debug information will be printed.
 * @param bool $force if true, bypasses the short analysis run throttle.
 * @param bool $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @param bool $reset_before_run if true, clear persisted analysis state after ownership is acquired.
 * @return bool True only when the owned task and advisory-lock release both succeed.
 */
function ai4seo_run_posts_table_analysis_task(
	bool $restart_if_completed = false,
	bool $debug = false,
	bool $force = false,
	bool $allow_debug_heavy_db_operations = false,
	bool $reset_before_run = false
): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 423645868, 'Prevented loop', true );
		return false;
	}

	$database_lock_name = ai4seo_get_posts_table_analysis_database_lock_name();
	$task_succeeded     = false;
	$release_succeeded  = false;

	if ( '' === $database_lock_name || ! ai4seo_acquire_database_advisory_lock( $database_lock_name ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 619820473, esc_html( __FUNCTION__ ) . ' > Could not acquire the posts-table analysis advisory lock.' );
		}

		return false;
	}

	try {
		$task_succeeded = ai4seo_run_posts_table_analysis_task_under_lock(
			$restart_if_completed,
			$debug,
			$force,
			$allow_debug_heavy_db_operations,
			$reset_before_run
		);
	} finally {
		$release_succeeded = ai4seo_release_database_advisory_lock( $database_lock_name );

		if ( ! $release_succeeded && $debug ) {
			ai4seo_debug_message( 903147526, esc_html( __FUNCTION__ ) . ' > Could not release the posts-table analysis advisory lock.' );
		}
	}

	return $task_succeeded && $release_succeeded;
}


/**
 * Run one posts-table analysis task while the active connection owns its site lock.
 *
 * @param bool $restart_if_completed if true, restart an already completed analysis.
 * @param bool $debug if true, print debug information.
 * @param bool $force if true, bypass the short analysis run throttle.
 * @param bool $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB setting.
 * @param bool $reset_before_run if true, clear persisted analysis state before processing.
 * @return bool True when every required state write and bounded work phase succeeded.
 */
function ai4seo_run_posts_table_analysis_task_under_lock(
	bool $restart_if_completed,
	bool $debug,
	bool $force,
	bool $allow_debug_heavy_db_operations,
	bool $reset_before_run
): bool {
	$database_lock_name = ai4seo_get_posts_table_analysis_database_lock_name();

	if ( '' === $database_lock_name || ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $database_lock_name ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 844190267, esc_html( __FUNCTION__ ) . ' > Required posts-table analysis advisory lock is unavailable or not owned.' );
		}

		return false;
	}

	if ( ! ai4seo_is_posts_table_analysis_possible( $debug, $allow_debug_heavy_db_operations ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 101605330, esc_html( __FUNCTION__ ) . ' > Heavy database operations disabled.' );
		}

		// Preserve a durable rebuild request before reporting this intentionally deferred run as handled.
		if ( ! ai4seo_schedule_generation_status_summary_rebuild() ) {
			if ( $debug ) {
				ai4seo_debug_message( 291576843, esc_html( __FUNCTION__ ) . ' > Could not durably schedule the deferred generation-status rebuild.' );
			}

			return false;
		}

		// Persist completion so ordinary count checks do not spin while heavy work is intentionally paused.
		$deferred_state_succeeded = ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, 'completed', true );

		if ( ! $deferred_state_succeeded && $debug ) {
			ai4seo_debug_message( 657103984, esc_html( __FUNCTION__ ) . ' > Could not persist the deferred completed state.' );
		}

		return $deferred_state_succeeded;
	}

	// Resolve the related state markers from one coherent site-local read while ownership is held.
	$environmental_snapshot = ai4seo_read_authoritative_environmental_variables_snapshot();

	// Refuse partial or malformed snapshots before making any scheduling or persistence decision.
	if ( empty( $environmental_snapshot['success'] ) || ! isset( $environmental_snapshot['values'] ) || ! is_array( $environmental_snapshot['values'] ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 430965172, esc_html( __FUNCTION__ ) . ' > Could not read a valid authoritative analysis-state snapshot.' );
		}

		return false;
	}

	// Keep every initial decision on the same lock-scoped environmental generation.
	$environmental_values              = $environmental_snapshot['values'];
	$posts_table_analysis_state        = (string) $environmental_values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ];
	$posts_table_analysis_start_time   = (int) $environmental_values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME ];
	$last_core_run_time                = (int) $environmental_values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME ];
	$posts_table_analysis_last_post_id = (int) $environmental_values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID ];
	$do_restart                        = false;

	// Keep stale-run recovery and batch pacing aligned with the shared analysis limits.
	$processing_timeout  = AI4SEO_POST_TABLE_ANALYSIS_PROCESSING_TIMEOUT;
	$usleep_between_runs = AI4SEO_POST_TABLE_ANALYSIS_SLEEP_BETWEEN_RUNS;
	$total_max_run_time  = AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME;

	// Give AJAX-triggered runs four times the base processing window.
	if ( wp_doing_ajax() ) {
		$total_max_run_time *= 4;
	}

	// Let cron process larger batches while proportionally reducing its database-query cadence.
	if ( wp_doing_cron() ) {
		$total_max_run_time  *= 5;
		$usleep_between_runs *= 5;
	}

	// Bound iterations as well as wall-clock time so each task obeys the configured pacing envelope.
	$max_runs_per_task = $total_max_run_time / ( $usleep_between_runs / 1000000 );

	// Restart a stale processing marker, but leave a live analysis process as the sole owner of the task.
	if ( 'processing' === $posts_table_analysis_state ) {
		if ( ! $posts_table_analysis_start_time || ( time() - $posts_table_analysis_start_time ) > $processing_timeout ) {
			$do_restart = true;

			if ( $debug ) {
				ai4seo_debug_message( 604817040, esc_html( __FUNCTION__ ) . ' > Posts table analysis timed out -> restarting' );
			}
		} else {
			if ( $debug ) {
				ai4seo_debug_message( 364907215, esc_html( __FUNCTION__ ) . ' > Posts table analysis already in progress since ' . esc_html( ai4seo_gmdate( 'Y-m-d H:i:s', (int) $posts_table_analysis_start_time ) ) . ' -> stop' );
			}

			// A live marker without this connection's ownership cannot be safely replaced.
			return false;
		}
	}

	// Restart completed work only when the caller explicitly requests a fresh analysis.
	if ( 'completed' === $posts_table_analysis_state ) {
		if ( $restart_if_completed ) {
			$do_restart = true;

			if ( $debug ) {
				ai4seo_debug_message( 978731049, esc_html( __FUNCTION__ ) . ' > Posts table analysis already completed -> restarting' );
			}
		} else {
			if ( $debug ) {
				ai4seo_debug_message( 405037545, esc_html( __FUNCTION__ ) . ' > Posts table analysis already completed -> stop' );
			}

			// Preserve the completed state when no fresh analysis was requested.
			return true;
		}
	}

	// Suppress closely spaced ordinary runs while allowing forced and diagnostic executions.
	$run_interval_in_seconds = AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME * 2;

	if ( $last_core_run_time && ( time() - $last_core_run_time ) < $run_interval_in_seconds && ! $debug && ! $force ) {
		return true;
	}

	// Persist the throttling claim before any destructive reset or analysis work begins.
	if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME, time(), false ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 716284509, esc_html( __FUNCTION__ ) . ' > Could not persist the analysis run throttle marker.' );
		}

		return false;
	}

	// Clear persisted progress only after the state checks have authorized a restart.
	if ( ( $do_restart || $reset_before_run ) && ! ai4seo_reset_posts_table_analysis( true ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 983507261, esc_html( __FUNCTION__ ) . ' > Could not reset posts-table analysis state before this run.' );
		}

		return false;
	}

	// The checked reset already persisted cursor zero, so mirror it locally instead of reading it again.
	if ( $do_restart || $reset_before_run ) {
		$posts_table_analysis_last_post_id = 0;
	}

	// Publish the processing marker and timestamp before entering the bounded work loop.
	$start_time = time();

	$start_transition_result = ai4seo_bulk_update_environmental_variables(
		array(
			AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE      => 'processing',
			AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME => $start_time,
		),
		false
	);

	if ( empty( $start_transition_result['success'] ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 542618390, esc_html( __FUNCTION__ ) . ' > Could not persist the processing state and start time.' );
		}

		return false;
	}

	// Track loop progress separately from persisted offsets so unchanged offsets can stop runaway work.
	$previous_posts_table_analysis_last_post_id = -1;
	$run_counter                                = 0;
	$is_finished                                = false;
	$work_succeeded                             = true;
	$final_state_succeeded                      = false;

	try {
		while ( time() - $start_time < $total_max_run_time && $run_counter < $max_runs_per_task ) {
			if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $database_lock_name ) ) {
				if ( $debug ) {
					ai4seo_debug_message( 167904832, esc_html( __FUNCTION__ ) . ' > Advisory-lock ownership was lost before the next analysis chunk.' );
				}

				$work_succeeded = false;
				break;
			}

			++$run_counter;

			if ( $debug ) {
				ai4seo_debug_message( 231305633, esc_html( __FUNCTION__ ) . ' > Run #' . esc_html( $run_counter ) . ' - Last analyzed post ID: ' . esc_html( $posts_table_analysis_last_post_id ) );
			}

			// prevent infinite loop when the offset is not updated.
			if ( $posts_table_analysis_last_post_id === $previous_posts_table_analysis_last_post_id ) {
				if ( $debug ) {
					ai4seo_debug_message( 107971319, esc_html( __FUNCTION__ ) . ' > Posts table analysis last post id not updated -> stopping to prevent infinite loop' );
				}

				$work_succeeded = false;
				break;
			}

			$is_finished = ai4seo_perform_posts_table_analysis( $posts_table_analysis_last_post_id, $debug );

			if ( $is_finished ) {
				break;
			}

			$persisted_last_post_id = ai4seo_read_environmental_variable(
				AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID,
				false
			);

			if ( (string) $persisted_last_post_id === (string) $posts_table_analysis_last_post_id ) {
				if ( $debug ) {
					ai4seo_debug_message(
						734891205,
						esc_html( __FUNCTION__ ) . ' > Analysis chunk returned without completion or cursor progress. Last post ID: ' . esc_html( $posts_table_analysis_last_post_id )
					);
				}

				$work_succeeded = false;
				break;
			}

			// Carry the post-chunk cursor into the next iteration so it is not reread before work resumes.
			$previous_posts_table_analysis_last_post_id = $posts_table_analysis_last_post_id;
			$posts_table_analysis_last_post_id          = (int) $persisted_last_post_id;

			usleep( $usleep_between_runs );
		}
	} catch ( Throwable $e ) {
		$work_succeeded = false;
		ai4seo_debug_message( 842653579, $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), true );
	} finally {
		// Only the advisory-lock owner reaches this finalizer; every state publication remains checked.
		if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $database_lock_name ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 309746125, esc_html( __FUNCTION__ ) . ' > Advisory-lock ownership was lost before publishing the final analysis state.' );
			}

			$work_succeeded        = false;
			$final_state_succeeded = false;
		} elseif ( $is_finished ) {
			$final_state_succeeded = ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, 'completed', false );

			if ( $final_state_succeeded && $debug ) {
				ai4seo_debug_message( 174773382, esc_html( __FUNCTION__ ) . ' > Posts table analysis completed' );
			} elseif ( $debug ) {
				ai4seo_debug_message( 875420613, esc_html( __FUNCTION__ ) . ' > Could not persist the completed analysis state.' );
			}
		} else {
			$final_state_succeeded = ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, 'idle', false );

			if ( $final_state_succeeded && $debug ) {
				ai4seo_debug_message( 679211510, esc_html( __FUNCTION__ ) . ' > Posts table analysis paused, not yet completed' );
			} elseif ( $debug ) {
				ai4seo_debug_message( 248671935, esc_html( __FUNCTION__ ) . ' > Could not persist the paused analysis state.' );
			}
		}
	}

	if ( $debug ) {
		ai4seo_debug_message( 472361408, esc_html( __FUNCTION__ ) . ' > Current state: ' . esc_html( ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, false ) ) );
	}

	return $work_succeeded && $final_state_succeeded;
}


/**
 * Build one deterministic coverage transition with the same contradiction order as legacy additions.
 *
 * @param array $post_ids_by_option Coverage option names mapped to analyzed post IDs.
 * @return array{additions: array, removals: array} Normalized transition maps.
 */
function ai4seo_prepare_posts_table_analysis_option_transition( array $post_ids_by_option ): array {
	$additions = array();
	$removals  = array();

	// Preserve the established coverage-option order when two requested destinations contradict.
	foreach ( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS as $option_name ) {
		$post_ids = ai4seo_normalize_option_post_id_collection( $post_ids_by_option[ $option_name ] ?? array() );

		if ( ! $post_ids ) {
			continue;
		}

		foreach ( ai4seo_get_contradictory_post_id_option_names( $option_name ) as $contradictory_option_name ) {
			$removals[ $contradictory_option_name ] = array_merge(
				$removals[ $contradictory_option_name ] ?? array(),
				$post_ids
			);

			// A later destination in the legacy option order wins this overlapping membership.
			if ( isset( $additions[ $contradictory_option_name ] ) ) {
				$additions[ $contradictory_option_name ] = array_values(
					array_diff( $additions[ $contradictory_option_name ], $post_ids )
				);

				if ( ! $additions[ $contradictory_option_name ] ) {
					unset( $additions[ $contradictory_option_name ] );
				}
			}
		}

		$additions[ $option_name ] = array_values(
			array_unique(
				array_merge( $additions[ $option_name ] ?? array(), $post_ids )
			)
		);
	}

	return array(
		'additions' => ai4seo_normalize_post_id_option_mutation_map( $additions ),
		'removals'  => ai4seo_normalize_post_id_option_mutation_map( $removals ),
	);
}


/**
 * Keep analyzed summary batches aligned with the additions verified by the shared transition.
 *
 * Existing Pending, Processing, and Failed observations are retained. Coverage memberships are
 * restricted to the normalized additions whose final presence was verified under the shared fence.
 *
 * @param array $summary_batches Analyzed option/post-type membership batches.
 * @param array $verified_additions Verified coverage additions by option name.
 * @return array Normalized batches safe to merge into the persisted summary.
 */
function ai4seo_get_verified_posts_table_analysis_summary_batches( array $summary_batches, array $verified_additions ): array {
	$verified_additions = ai4seo_normalize_post_id_option_mutation_map( $verified_additions );
	$verified_batches   = array();

	foreach ( $summary_batches as $option_name => $post_type_batches ) {
		if ( ! is_string( $option_name ) || ! is_array( $post_type_batches ) ) {
			continue;
		}

		$is_coverage_option = in_array( $option_name, AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS, true );
		$verified_lookup    = $is_coverage_option
			? array_fill_keys( $verified_additions[ $option_name ] ?? array(), true )
			: array();

		foreach ( $post_type_batches as $post_type => $post_ids ) {
			if ( ! is_string( $post_type ) ) {
				continue;
			}

			$post_ids = ai4seo_normalize_option_post_id_collection( $post_ids );

			if ( $is_coverage_option ) {
				$post_ids = array_values(
					array_filter(
						$post_ids,
						static function ( int $post_id ) use ( $verified_lookup ): bool {
							return isset( $verified_lookup[ $post_id ] );
						}
					)
				);
			}

			if ( $post_ids ) {
				$verified_batches[ $option_name ][ $post_type ] = $post_ids;
			}
		}
	}

	return $verified_batches;
}


/**
 * Commit one analyzed chunk before advancing its durable posts-table cursor.
 *
 * @param array $new_post_ids_by_option Coverage additions calculated for this chunk.
 * @param array $generation_status_post_ids_to_add Summary membership batches calculated for this chunk.
 * @param int   $last_processed_post_id Durable cursor value after this chunk.
 * @param bool  $debug Whether debug messages should be emitted.
 * @return bool True only when option state, summary state, and the cursor were all persisted.
 */
function ai4seo_commit_posts_table_analysis_chunk(
	array $new_post_ids_by_option,
	array $generation_status_post_ids_to_add,
	int $last_processed_post_id,
	bool $debug
): bool {
	$transition = ai4seo_prepare_posts_table_analysis_option_transition( $new_post_ids_by_option );

	if ( $debug ) {
		foreach ( $transition['additions'] as $option_name => $post_ids ) {
			ai4seo_debug_message( 732600128, esc_html( __FUNCTION__ ) . ' > Adding to option ' . $option_name . ': ' . count( $post_ids ) . ' post ids' );
		}
	}

	if (
		( $transition['additions'] || $transition['removals'] )
		&& ! ai4seo_apply_post_id_option_transition( $transition['additions'], $transition['removals'] )
	) {
		ai4seo_debug_message( 175943821, esc_html( __FUNCTION__ ) . ' > Could not persist and verify the analyzed coverage transition.', true );
		return false;
	}

	$verified_summary_batches = ai4seo_get_verified_posts_table_analysis_summary_batches(
		$generation_status_post_ids_to_add,
		$transition['additions']
	);

	// Merge only verified chunk memberships into the latest full summary inside the bounded CAS retry loop.
	$summary_did_change                   = false;
	$current_generation_status_summary    = null;
	$generation_status_summary_was_stored = ai4seo_mutate_generation_status_summary(
		static function ( array $latest_summary ) use ( $verified_summary_batches ): array {
			$updated_summary = ai4seo_merge_generation_status_summary_post_id_batches(
				$latest_summary,
				$verified_summary_batches
			);

			return array(
				'summary' => $updated_summary,
				'changed' => ai4seo_get_comparable_generation_status_summary( $latest_summary )
					!== ai4seo_get_comparable_generation_status_summary( $updated_summary ),
			);
		},
		false,
		$summary_did_change,
		$current_generation_status_summary
	);

	if ( ! $generation_status_summary_was_stored ) {
		ai4seo_debug_message( 984321697, esc_html( __FUNCTION__ ) . ' > Could not persist a matching generation status summary pair.', true );
		return false;
	}

	if ( $debug ) {
		ai4seo_debug_message( 417529305, esc_html( __FUNCTION__ ) . ' > Current generation status summary: ' . esc_html( ai4seo_stringify( $current_generation_status_summary ) ) );
		ai4seo_debug_message( 408476980, esc_html( __FUNCTION__ ) . ' > Last processed post ID: ' . $last_processed_post_id );
	}

	if (
		! ai4seo_update_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID,
			$last_processed_post_id,
			false
		)
	) {
		ai4seo_debug_message( 175943822, esc_html( __FUNCTION__ ) . ' > Could not persist the posts table analysis cursor.', true );
		return false;
	}

	return true;
}


/**
 * Reads one required analysis post-ID option from its authoritative raw row.
 * Repairs the known legacy empty-string representation when it is safe to do so.
 *
 * @param string    $option_name Option name.
 * @param bool|null $read_succeeded Receives whether the authoritative read and decoding succeeded.
 * @param bool      $debug Whether diagnostic messages should be emitted for rejected storage.
 * @return array Canonical post IDs.
 */
function ai4seo_read_posts_table_analysis_post_id_option( string $option_name, ?bool &$read_succeeded = null, bool $debug = false ): array {
	$read_succeeded = false;
	$option_name    = trim( $option_name );

	if ( '' === $option_name || sanitize_key( $option_name ) !== $option_name ) {
		if ( $debug ) {
			ai4seo_debug_message( 976214358, esc_html( __FUNCTION__ ) . ' > Rejected an invalid required option name.' );
		}

		return array();
	}

	$option_snapshot = ai4seo_get_raw_option_snapshot( $option_name );

	if ( null === $option_snapshot || ! ai4seo_is_valid_raw_option_snapshot( $option_name, $option_snapshot ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 430687192, esc_html( __FUNCTION__ ) . ' > Could not read a valid raw snapshot for option: ' . esc_html( $option_name ) );
		}

		return array();
	}

	if ( ! $option_snapshot['exists'] ) {
		$read_succeeded = true;
		return array();
	}

	// Limit this compatibility repair to the four Pending/Processing rows written by the affected release.
	$legacy_empty_post_id_option_names = array(
		AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
		AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);

	// Canonicalize only the exact observed empty bytes so the repair cannot overwrite a concurrent queue write.
	if (
		'' === $option_snapshot['raw_value']
		&& in_array( $option_name, $legacy_empty_post_id_option_names, true )
	) {
		$repair_succeeded = ai4seo_compare_and_swap_option_snapshot(
			$option_name,
			$option_snapshot,
			array(),
			false
		);

		if ( true !== $repair_succeeded ) {
			if ( $debug ) {
				ai4seo_debug_message( 903826417, esc_html( __FUNCTION__ ) . ' > Could not safely repair the legacy empty post-ID option: ' . esc_html( $option_name ) );
			}

			return array();
		}

		if ( $debug ) {
			ai4seo_debug_message( 486120759, esc_html( __FUNCTION__ ) . ' > Repaired a legacy empty post-ID option: ' . esc_html( $option_name ) );
		}

		$read_succeeded = true;
		return array();
	}

	$option_value = ai4seo_safe_maybe_unserialize( $option_snapshot['value'] );

	if ( is_string( $option_value ) ) {
		$decoded_option_value = json_decode( $option_value, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded_option_value ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 682043719, esc_html( __FUNCTION__ ) . ' > Required option contains an invalid JSON collection: ' . esc_html( $option_name ) );
			}

			return array();
		}

		$option_value = $decoded_option_value;
	}

	if ( ! is_array( $option_value ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 319576842, esc_html( __FUNCTION__ ) . ' > Required option is not a post-ID collection: ' . esc_html( $option_name ) );
		}

		return array();
	}

	$post_ids       = array();
	$post_id_lookup = array();

	foreach ( $option_value as $post_id ) {
		$normalized_post_id = ai4seo_normalize_option_post_id( $post_id );

		if ( false === $normalized_post_id ) {
			if ( $debug ) {
				ai4seo_debug_message( 754261903, esc_html( __FUNCTION__ ) . ' > Required option contains an invalid post ID: ' . esc_html( $option_name ) );
			}

			return array();
		}

		if ( isset( $post_id_lookup[ $normalized_post_id ] ) ) {
			continue;
		}

		$post_id_lookup[ $normalized_post_id ] = true;
		$post_ids[]                            = $normalized_post_id;
	}

	$read_succeeded = true;
	return $post_ids;
}


/**
 * Reads required analysis post-ID options without accepting a partial snapshot.
 *
 * @param array     $option_names Option names.
 * @param bool|null $read_succeeded Receives whether every option read succeeded.
 * @param bool      $debug Whether diagnostic messages should be emitted for rejected storage.
 * @return array Post IDs keyed by option name.
 */
function ai4seo_read_posts_table_analysis_post_id_options( array $option_names, ?bool &$read_succeeded = null, bool $debug = false ): array {
	$read_succeeded     = false;
	$post_ids_by_option = array();

	foreach ( $option_names as $option_name ) {
		$this_read_succeeded = false;
		$this_post_ids       = ai4seo_read_posts_table_analysis_post_id_option( (string) $option_name, $this_read_succeeded, $debug );

		if ( ! $this_read_succeeded ) {
			return array();
		}

		$post_ids_by_option[ $option_name ] = $this_post_ids;
	}

	$read_succeeded = true;
	return $post_ids_by_option;
}


/**
 * Performs posts table analysis for a certain number of rows
 *
 * @param int  $posts_table_analysis_last_post_id the last post id that was analyzed.
 * @param bool $debug if true, debug information will be printed.
 * @return bool true if the analysis is completed, false otherwise
 */
function ai4seo_perform_posts_table_analysis( int $posts_table_analysis_last_post_id, bool $debug ): bool {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 381607754, 'Prevented loop', true );
		return true;
	}

	$allowed_attachment_mime_types = ai4seo_get_allowed_attachment_mime_types();
	$total_rows_per_run            = AI4SEO_POST_TABLE_ANALYSIS_BATCH_SIZE;

	// if ajax -> double it.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$total_rows_per_run *= 2;
	}

	// Cursor-based pagination query.
	$raw_posts_query = $wpdb->prepare(
		"SELECT ID, post_author, post_type, post_status, post_mime_type, post_date_gmt, post_date, post_modified_gmt, post_modified
		FROM {$wpdb->posts}
		WHERE ID > %d
		ORDER BY ID ASC
		LIMIT %d",
		$posts_table_analysis_last_post_id,
		$total_rows_per_run
	);

	if ( ! is_string( $raw_posts_query ) || '' === $raw_posts_query ) {
		if ( $debug ) {
			ai4seo_debug_message( 218640975, esc_html( __FUNCTION__ ) . ' > Could not prepare the posts-table batch query.' );
		}

		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The immediately preceding prepare call binds both numeric values; analysis requires the current ordered batch after its persisted cursor.
	$raw_posts = $wpdb->get_results( $raw_posts_query, ARRAY_A );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321680, 'Database error: ' . $wpdb->last_error, true );
		return false;
	}

	if ( ! is_array( $raw_posts ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 865109437, esc_html( __FUNCTION__ ) . ' > Posts-table batch query returned an invalid result shape.' );
		}

		return false;
	}

	if ( ! $raw_posts || count( $raw_posts ) === 0 ) {
		if ( $debug ) {
			ai4seo_debug_message( 681121926, esc_html( __FUNCTION__ ) . ' > No more posts to analyze' );
		}

		// no more posts to analyze -> finished.
		return true;
	}

	$num_raw_posts = count( $raw_posts );
	$is_last_chunk = $num_raw_posts < $total_rows_per_run;

	// get post ids.
	$raw_post_ids       = array();
	$previous_post_id   = $posts_table_analysis_last_post_id;
	$required_post_keys = array_flip(
		array(
			'ID',
			'post_author',
			'post_type',
			'post_status',
			'post_mime_type',
			'post_date_gmt',
			'post_date',
			'post_modified_gmt',
			'post_modified',
		)
	);
	$post_date_keys     = array( 'post_date_gmt', 'post_date', 'post_modified_gmt', 'post_modified' );

	foreach ( $raw_posts as $raw_post_index => $raw_post ) {
		if ( ! is_array( $raw_post ) || array_diff_key( $required_post_keys, $raw_post ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 492736018, esc_html( __FUNCTION__ ) . ' > Posts-table batch row is missing required fields. Batch row: ' . esc_html( (int) $raw_post_index + 1 ) );
			}

			return false;
		}

		$this_raw_post_id         = ai4seo_normalize_database_id( $raw_post['ID'] );
		$this_post_author_id      = ai4seo_normalize_option_post_id( $raw_post['post_author'], true );
		$this_post_type           = $raw_post['post_type'];
		$this_post_status         = $raw_post['post_status'];
		$this_post_mime_type      = $raw_post['post_mime_type'];
		$this_invalid_post_fields = array();

		if ( false === $this_raw_post_id ) {
			$this_invalid_post_fields[] = 'ID';
		}

		if ( false === $this_post_author_id ) {
			$this_invalid_post_fields[] = 'post_author';
		}

		if ( ! is_string( $this_post_type )
			|| '' === $this_post_type
			|| strlen( $this_post_type ) > 20
			|| sanitize_key( $this_post_type ) !== $this_post_type ) {
			$this_invalid_post_fields[] = 'post_type';
		}

		if ( ! is_string( $this_post_status )
			|| '' === $this_post_status
			|| strlen( $this_post_status ) > 20
			|| sanitize_key( $this_post_status ) !== $this_post_status ) {
			$this_invalid_post_fields[] = 'post_status';
		}

		if ( ! is_string( $this_post_mime_type )
			|| strlen( $this_post_mime_type ) > 100
			|| sanitize_mime_type( $this_post_mime_type ) !== $this_post_mime_type ) {
			$this_invalid_post_fields[] = 'post_mime_type';
		}

		if ( $this_invalid_post_fields ) {
			if ( $debug ) {
				$this_diagnostic_post_id = false === $this_raw_post_id ? 'unavailable' : (string) $this_raw_post_id;

				ai4seo_debug_message(
					157804629,
					esc_html( __FUNCTION__ ) . ' > Posts-table row failed validation. Post ID: ' . esc_html( $this_diagnostic_post_id ) . '; fields: ' . esc_html( implode( ', ', $this_invalid_post_fields ) )
				);
			}

			return false;
		}

		foreach ( $post_date_keys as $this_post_date_key ) {
			$this_post_date = $raw_post[ $this_post_date_key ];

			if ( ! is_string( $this_post_date )
				|| ( '0000-00-00 00:00:00' !== $this_post_date && ! ai4seo_is_valid_mysql_datetime( $this_post_date ) ) ) {
				if ( $debug ) {
					ai4seo_debug_message(
						608325741,
						esc_html( __FUNCTION__ ) . ' > Posts-table row contains an invalid date. Post ID: ' . esc_html( $this_raw_post_id ) . '; field: ' . esc_html( $this_post_date_key )
					);
				}

				return false;
			}
		}

		if ( $this_raw_post_id <= $previous_post_id ) {
			if ( $debug ) {
				ai4seo_debug_message( 943170586, esc_html( __FUNCTION__ ) . ' > Posts-table batch IDs are not strictly increasing. Post ID: ' . esc_html( $this_raw_post_id ) );
			}

			return false;
		}

		$raw_post_ids[]   = $this_raw_post_id;
		$previous_post_id = $this_raw_post_id;
	}

	// read generated data post ids.
	$generated_data_read_succeeded      = false;
	$generated_data_all_post_ids        = ai4seo_read_generated_data_post_ids_by_post_ids( $raw_post_ids, $generated_data_read_succeeded, $debug );
	$generated_data_post_ids            = array();
	$generated_data_attachment_post_ids = array();

	if ( ! $generated_data_read_succeeded ) {
		if ( $debug ) {
			ai4seo_debug_message( 381962704, esc_html( __FUNCTION__ ) . ' > Generated-data batch read failed.' );
		}

		return false;
	}

	// Resolve the same date-filter state used by queue excavation and content-list eligibility.
	$bulk_generation_date_filter_state = ai4seo_get_current_bulk_generation_date_filter_state();

	// PRE-FILTER POSTS & SEPARATE ATTACHMENTS.

	$supported_post_types                = ai4seo_get_supported_post_types();
	$supported_attachment_post_types     = ai4seo_get_supported_attachment_post_types();
	$disabled_post_author_ids            = ai4seo_get_disabled_post_author_ids();
	$disabled_post_author_ids            = array_flip( $disabled_post_author_ids );
	$disabled_attachment_post_author_ids = ai4seo_get_disabled_attachment_post_author_ids();
	$disabled_attachment_post_author_ids = array_flip( $disabled_attachment_post_author_ids );
	$disabled_taxonomy_terms             = ai4seo_get_enforced_disabled_taxonomy_terms();

	// Analysis has to use the same WPML language scope as dashboards and queue excavation.
	$disabled_metadata_wpml_language_codes                    = ai4seo_get_disabled_metadata_wpml_language_codes();
	$disabled_metadata_wpml_language_code_lookup              = array_flip( $disabled_metadata_wpml_language_codes );
	$disabled_attachment_attributes_wpml_language_codes       = ai4seo_get_disabled_attachment_attributes_wpml_language_codes();
	$disabled_attachment_attributes_wpml_language_code_lookup = array_flip( $disabled_attachment_attributes_wpml_language_codes );
	$posts                                 = array();
	$attachment_posts                      = array();
	$post_ids_with_disabled_taxonomy_terms = array();

	if ( $disabled_taxonomy_terms ) {
		$disabled_taxonomy_read_succeeded      = false;
		$post_ids_with_disabled_taxonomy_terms = ai4seo_get_post_ids_excluded_by_disabled_taxonomy_terms(
			$raw_post_ids,
			$disabled_taxonomy_terms,
			null,
			$disabled_taxonomy_read_succeeded
		);

		if ( ! $disabled_taxonomy_read_succeeded ) {
			if ( $debug ) {
				ai4seo_debug_message( 526819403, esc_html( __FUNCTION__ ) . ' > Disabled-taxonomy exclusion read failed.' );
			}

			return false;
		}

		$post_ids_with_disabled_taxonomy_terms = array_flip( $post_ids_with_disabled_taxonomy_terms );
	}

	if ( $supported_post_types || $supported_attachment_post_types ) {
		foreach ( $raw_posts as $this_raw_post ) {
			$this_post_id            = (int) $this_raw_post['ID'];
			$this_post_type          = $this_raw_post['post_type'];
			$is_post_type            = $supported_post_types && in_array( $this_post_type, $supported_post_types, true );
			$is_attachment_post_type = $supported_attachment_post_types && in_array( $this_post_type, $supported_attachment_post_types, true );
			$this_wpml_language_code = '';

			// WPML language exclusions apply before any generation-status bucket is rebuilt for this entry.
			if ( ( $is_post_type && $disabled_metadata_wpml_language_code_lookup )
				|| ( $is_attachment_post_type && $disabled_attachment_attributes_wpml_language_code_lookup ) ) {
				$this_wpml_language_code = ai4seo_get_cached_post_language_code_by_multilanguage_plugins( $this_post_id );
			}

			$is_disabled_metadata_wpml_language              = '' !== $this_wpml_language_code
				&& isset( $disabled_metadata_wpml_language_code_lookup[ $this_wpml_language_code ] );
			$is_disabled_attachment_attributes_wpml_language = '' !== $this_wpml_language_code
				&& isset( $disabled_attachment_attributes_wpml_language_code_lookup[ $this_wpml_language_code ] );

			// Generated counters include existing generated data before active-scope exclusions, matching disabled authors and taxonomy terms.
			if ( $is_post_type && in_array( $this_post_id, $generated_data_all_post_ids, true ) ) {
				$generated_data_post_ids[ $this_post_id ] = $this_post_type;
			}

			if ( $is_attachment_post_type && in_array( $this_post_id, $generated_data_all_post_ids, true ) ) {
				$generated_data_attachment_post_ids[ $this_post_id ] = $this_post_type;
			}

			// Apply the exact > / <= boundary contract used by the prepared queue queries.
			$posted_date = $this_raw_post['post_date_gmt'];

			if ( ! $posted_date ) {
				$posted_date = $this_raw_post['post_date'];
			}

			if ( ! $posted_date ) {
				$posted_date = $this_raw_post['post_modified_gmt'];
			}

			if ( ! $posted_date ) {
				$posted_date = $this_raw_post['post_modified'];
			}

			$posted_date_timestamp = $posted_date ? (int) strtotime( $posted_date ) : 0;

			if ( ! ai4seo_does_bulk_generation_date_filter_include_timestamp( $bulk_generation_date_filter_state, $posted_date_timestamp ) ) {
				continue;
			}

			// check by post type.
			if ( $is_post_type ) {
				if ( $is_disabled_metadata_wpml_language ) {
					continue;
				}

				if ( $disabled_post_author_ids && isset( $disabled_post_author_ids[ (int) $this_raw_post['post_author'] ] ) ) {
					continue;
				}

				if ( $post_ids_with_disabled_taxonomy_terms && isset( $post_ids_with_disabled_taxonomy_terms[ (int) $this_raw_post['ID'] ] ) ) {
					continue;
				}

				// skip if not status publish or future.
				if ( ! in_array( $this_raw_post['post_status'], array( 'publish', 'future' ), true ) ) {
					continue;
				}

				$posts[ $this_post_id ] = $this_raw_post;
			} elseif ( $is_attachment_post_type ) {
				if ( $is_disabled_attachment_attributes_wpml_language ) {
					continue;
				}

				if ( $disabled_attachment_post_author_ids && isset( $disabled_attachment_post_author_ids[ (int) $this_raw_post['post_author'] ] ) ) {
					continue;
				}

				// skip if not status publish, future or inherit.
				if ( ! in_array( $this_raw_post['post_status'], array( 'publish', 'future', 'inherit' ), true ) ) {
					continue;
				}

				// check mime type.
				if ( ! in_array( $this_raw_post['post_mime_type'], $allowed_attachment_mime_types, true ) ) {
					continue;
				}

				$attachment_posts[ $this_post_id ] = $this_raw_post;
			} else {
				// unsupported post type -> skip.
				continue;
			}
		}
	}

	// PREPARE.

	// get last $raw_posts entry.
	$last_raw_post          = end( $raw_posts );
	$last_processed_post_id = (int) $last_raw_post['ID'];

	unset( $raw_posts ); // free memory.

	$post_ids             = array_keys( $posts );
	$attachment_posts_ids = array_keys( $attachment_posts );

	// prepare the coverage based post ids array.
	$new_post_ids_by_option = array();

	foreach ( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS as $this_option_name ) {
		$new_post_ids_by_option[ $this_option_name ] = array();
	}

	// collect post ids per option and post type to reduce summary writes.
	$generation_status_post_ids_to_add = array();

	// GENERATED POSTS.
	foreach ( $generated_data_post_ids as $this_generated_post_id => $this_generated_post_type ) {
		$new_post_ids_by_option[ AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME ][] = $this_generated_post_id;
		$generation_status_post_ids_to_add[ AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME ][ $this_generated_post_type ][] = $this_generated_post_id;
	}

	foreach ( $generated_data_attachment_post_ids as $this_generated_attachment_post_id => $this_generated_attachment_post_type ) {
		$new_post_ids_by_option[ AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][] = (int) $this_generated_attachment_post_id;
		$generation_status_post_ids_to_add[ AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][ $this_generated_attachment_post_type ][] = (int) $this_generated_attachment_post_id;
	}

	// ANALYSE POSTS.

	if ( $post_ids ) {
		$generate_metadata_for_fully_covered_entries = ai4seo_do_generate_metadata_for_fully_covered_entries();
		$metadata_status_options_read_succeeded      = false;
		$metadata_status_post_ids_by_option          = ai4seo_read_posts_table_analysis_post_id_options(
			array(
				AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME,
			),
			$metadata_status_options_read_succeeded,
			$debug
		);

		if ( ! $metadata_status_options_read_succeeded ) {
			if ( $debug ) {
				ai4seo_debug_message( 709458126, esc_html( __FUNCTION__ ) . ' > Required metadata status-option read failed.' );
			}

			return false;
		}

		$processing_post_ids                           = $metadata_status_post_ids_by_option[ AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME ];
		$pending_post_ids                              = $metadata_status_post_ids_by_option[ AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME ];
		$failed_post_ids                               = $metadata_status_post_ids_by_option[ AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME ];
		$excluded_from_missing_metadata_post_ids       = array_merge(
			$metadata_status_post_ids_by_option[ AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME ],
			$metadata_status_post_ids_by_option[ AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME ]
		);
		$excluded_from_missing_metadata_post_id_lookup = array_flip( array_values( array_unique( array_filter( array_map( 'absint', $excluded_from_missing_metadata_post_ids ) ) ) ) );

		// read the percentage of active metadata by post ids.
		$metadata_coverage_read_succeeded             = false;
		$percentage_of_available_metadata_by_post_ids = ai4seo_read_percentage_of_available_metadata_by_post_ids( $post_ids, 0, $metadata_coverage_read_succeeded );

		if ( ! $metadata_coverage_read_succeeded ) {
			if ( $debug ) {
				ai4seo_debug_message( 264975831, esc_html( __FUNCTION__ ) . ' > Metadata coverage read failed.' );
			}

			return false;
		}

		foreach ( $percentage_of_available_metadata_by_post_ids as $this_post_id => $this_percentage ) {
			$this_post_id                       = (int) $this_post_id;
			$this_post_type                     = $posts[ $this_post_id ]['post_type'] ?? '';
			$this_post_was_generated            = in_array( $this_post_id, $generated_data_all_post_ids, true );
			$is_this_post_excluded_from_missing = isset( $excluded_from_missing_metadata_post_id_lookup[ $this_post_id ] );

			// Route every numeric representation of complete coverage through the same status bucket.
			if ( ai4seo_is_full_coverage_percentage( $this_percentage ) ) {
				// remove from fully covered those entries that has not been generated yet.
				if ( $generate_metadata_for_fully_covered_entries && ! $this_post_was_generated ) {
					$this_percentage = 0; // set to 0 to mark as missing.
				} else {
					$new_post_ids_by_option[ AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME ][]                               = $this_post_id;
					$generation_status_post_ids_to_add[ AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME ][ $this_post_type ][] = $this_post_id;
				}
			}

			if ( $this_percentage < 100 && ! $is_this_post_excluded_from_missing ) {
				$new_post_ids_by_option[ AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME ][]                               = $this_post_id;
				$generation_status_post_ids_to_add[ AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME ][ $this_post_type ][] = $this_post_id;
			}

			// check if this post is in processing post ids.
			if ( in_array( $this_post_id, $processing_post_ids, true ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME ][ $this_post_type ][] = $this_post_id;
			}

			// check if this post is in pending post ids.
			if ( in_array( $this_post_id, $pending_post_ids, true ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME ][ $this_post_type ][] = $this_post_id;
			}

			// check if this post is in failed post ids.
			if ( in_array( $this_post_id, $failed_post_ids, true ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME ][ $this_post_type ][] = $this_post_id;
			}
		}
	}

	// ANALYZE ATTACHMENT POSTS.

	if ( $attachment_posts_ids ) {
		$generate_attachment_attributes_for_fully_covered_entries = ai4seo_do_generate_attachment_attributes_for_fully_covered_entries();

		// BUILD ATTACHMENT ATTRIBUTES COVERAGE ARRAY.
		$attachment_coverage_read_succeeded = false;
		$attachment_attributes_coverage     = ai4seo_read_and_analyse_attachment_attributes_coverage( $attachment_posts_ids, $attachment_coverage_read_succeeded );

		if ( ! $attachment_coverage_read_succeeded ) {
			if ( $debug ) {
				ai4seo_debug_message( 817236490, esc_html( __FUNCTION__ ) . ' > Attachment coverage read failed.' );
			}

			return false;
		}

		$attachment_status_options_read_succeeded = false;
		$attachment_status_post_ids_by_option     = ai4seo_read_posts_table_analysis_post_id_options(
			array(
				AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			),
			$attachment_status_options_read_succeeded,
			$debug
		);

		if ( ! $attachment_status_options_read_succeeded ) {
			if ( $debug ) {
				ai4seo_debug_message( 135790864, esc_html( __FUNCTION__ ) . ' > Required attachment status-option read failed.' );
			}

			return false;
		}

		$num_total_attachment_attributes_fields          = ai4seo_get_active_num_attachment_attributes();
		$attachment_attributes_coverage_summary          = ai4seo_get_attachment_attributes_coverage_summary( $attachment_attributes_coverage );
		$processing_attachment_post_ids                  = $attachment_status_post_ids_by_option[ AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ];
		$pending_attachment_post_ids                     = $attachment_status_post_ids_by_option[ AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ];
		$failed_attachment_post_ids                      = $attachment_status_post_ids_by_option[ AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ];
		$excluded_from_missing_attachment_post_ids       = array_merge(
			$attachment_status_post_ids_by_option[ AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ],
			$attachment_status_post_ids_by_option[ AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ]
		);
		$excluded_from_missing_attachment_post_id_lookup = array_flip( array_values( array_unique( array_filter( array_map( 'absint', $excluded_from_missing_attachment_post_ids ) ) ) ) );
		unset( $attachment_attributes_coverage );

		// ADD ENTRIES TO THE GENERATION STATUS POST IDS.
		foreach ( $attachment_attributes_coverage_summary as $this_post_id => $num_fields_covered ) {
			// Canonicalize database-derived array keys before comparing them with integer option IDs.
			$this_post_id                                  = (int) $this_post_id;
			$this_post_was_generated                       = in_array( $this_post_id, $generated_data_all_post_ids, true );
			$this_attachment_post_type                     = 'attachment';
			$is_fully_covered                              = ( $num_fields_covered >= $num_total_attachment_attributes_fields );
			$is_this_attachment_post_excluded_from_missing = isset( $excluded_from_missing_attachment_post_id_lookup[ (int) $this_post_id ] );

			// check if fully covered.
			if ( $is_fully_covered ) {
				// remove from fully covered those entries that has not been generated yet.
				if ( $generate_attachment_attributes_for_fully_covered_entries && ! $this_post_was_generated ) {
					$is_fully_covered = false; // set to 'false' to mark as missing.
				} else {
					$new_post_ids_by_option[ AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][] = (int) $this_post_id;
					$generation_status_post_ids_to_add[ AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][ $this_attachment_post_type ][] = (int) $this_post_id;
				}
			}

			if ( ! $is_fully_covered && ! $is_this_attachment_post_excluded_from_missing ) {
				$new_post_ids_by_option[ AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][] = (int) $this_post_id;
				$generation_status_post_ids_to_add[ AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][ $this_attachment_post_type ][] = (int) $this_post_id;
			}

			// check if this post is in processing attachment post ids.
			if ( in_array( $this_post_id, $processing_attachment_post_ids, true ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][ $this_attachment_post_type ][] = (int) $this_post_id;
			}

			// check if this post is in pending attachment post ids.
			if ( in_array( $this_post_id, $pending_attachment_post_ids, true ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][ $this_attachment_post_type ][] = (int) $this_post_id;
			}

			// check if this post is in failed attachment post ids.
			if ( in_array( $this_post_id, $failed_attachment_post_ids, true ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][ $this_attachment_post_type ][] = (int) $this_post_id;
			}
		}
	}

	if (
		! ai4seo_commit_posts_table_analysis_chunk(
			$new_post_ids_by_option,
			$generation_status_post_ids_to_add,
			$last_processed_post_id,
			$debug
		)
	) {
		return false;
	}

	// was last chunk? -> finished = true, otherwise false.
	return $is_last_chunk;
}


/**
 * Clears the generation status summary request cache.
 *
 * @return void
 */
function ai4seo_reset_generation_status_summary_request_cache(): void {
	global $ai4seo_generation_status_summary_request_cache;

	// Keep per-request reads coherent after summary writes in the same request.
	$ai4seo_generation_status_summary_request_cache = array();
}


/**
 * Returns the internal commit-marker option for the full/totals summary pair.
 *
 * The public summary options retain their legacy array shapes. This small marker
 * is written last and is invalidated before either public option changes.
 *
 * @return string Internal option name.
 */
function ai4seo_get_generation_status_summary_pair_state_option_name(): string {
	return AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME . '_pair_state_v1';
}


/**
 * Checks whether an option stores one public half of the generation-summary pair.
 *
 * @param string $option_name Option name.
 * @return bool Whether the option is the full summary or its totals companion.
 */
function ai4seo_is_generation_status_summary_public_option_name( string $option_name ): bool {
	return in_array(
		$option_name,
		array(
			AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME,
			AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
		),
		true
	);
}


/**
 * Returns the bounded number of full-summary compare-and-swap attempts.
 *
 * @return int Attempt limit.
 */
function ai4seo_get_generation_status_summary_persistence_attempt_limit(): int {
	return 5;
}


/**
 * Decodes legacy full-summary storage into its normalized public representation.
 *
 * @param mixed $stored_value Raw decoded option value.
 * @return array Normalized full summary, or an empty summary for malformed storage.
 */
function ai4seo_decode_generation_status_summary_storage_value( $stored_value ): array {
	$generation_status_summary = ai4seo_safe_maybe_unserialize( $stored_value );

	if ( ! is_array( $generation_status_summary )
		&& is_string( $generation_status_summary )
		&& '' !== $generation_status_summary
		&& ai4seo_is_json( $generation_status_summary ) ) {
		$generation_status_summary = json_decode( $generation_status_summary, true );
	}

	if ( ! is_array( $generation_status_summary ) ) {
		return array();
	}

	return ai4seo_normalize_generation_status_summary_storage(
		ai4seo_deep_sanitize( $generation_status_summary, 'absint' )
	);
}


/**
 * Returns totals in a stable shape for checksumming and semantic comparison.
 *
 * @param array $generation_status_summary_totals Raw totals summary.
 * @return array Canonical totals summary.
 */
function ai4seo_get_comparable_generation_status_summary_totals( array $generation_status_summary_totals ): array {
	$comparable_totals = array();

	foreach ( $generation_status_summary_totals as $option_name => $post_type_entries ) {
		if ( ! is_array( $post_type_entries ) ) {
			continue;
		}

		$option_name                       = (string) $option_name;
		$comparable_totals[ $option_name ] = array();

		foreach ( $post_type_entries as $post_type => $total ) {
			$comparable_totals[ $option_name ][ (string) $post_type ] = absint( $total );
		}

		ksort( $comparable_totals[ $option_name ], SORT_STRING );
	}

	ksort( $comparable_totals, SORT_STRING );

	return $comparable_totals;
}


/**
 * Builds the content-addressed commit marker for one summary/totals pair.
 *
 * @param array $generation_status_summary Full generation status summary.
 * @return array{version: int, generation: string, full_checksum: string, totals_checksum: string} Pair state.
 */
function ai4seo_build_generation_status_summary_pair_state( array $generation_status_summary ): array {
	$comparable_totals = ai4seo_get_comparable_generation_status_summary_totals(
		ai4seo_get_generation_status_summary_totals( $generation_status_summary )
	);
	$full_checksum     = hash( 'sha256', maybe_serialize( $generation_status_summary ) );
	$totals_checksum   = hash( 'sha256', maybe_serialize( $comparable_totals ) );

	return array(
		'version'         => 1,
		'generation'      => hash( 'sha256', $full_checksum . ':' . $totals_checksum ),
		'full_checksum'   => $full_checksum,
		'totals_checksum' => $totals_checksum,
	);
}


/**
 * Validates one persisted summary-pair commit marker.
 *
 * @param mixed $pair_state Candidate marker.
 * @return bool Whether the marker has the exact supported shape and checksums.
 */
function ai4seo_is_valid_generation_status_summary_pair_state( $pair_state ): bool {
	if ( ! is_array( $pair_state )
		|| array_keys( $pair_state ) !== array( 'version', 'generation', 'full_checksum', 'totals_checksum' )
		|| 1 !== $pair_state['version'] ) {
		return false;
	}

	foreach ( array( 'generation', 'full_checksum', 'totals_checksum' ) as $checksum_key ) {
		if ( ! is_string( $pair_state[ $checksum_key ] )
			|| 1 !== preg_match( '/^[a-f0-9]{64}$/', $pair_state[ $checksum_key ] ) ) {
			return false;
		}
	}

	return hash_equals(
		hash( 'sha256', $pair_state['full_checksum'] . ':' . $pair_state['totals_checksum'] ),
		$pair_state['generation']
	);
}


/**
 * Determines whether compact totals match the checksum in a valid pair marker.
 *
 * This is the totals half of pair verification. Readers must also compare the
 * full-summary checksum before treating the two public values as one generation.
 *
 * @param array $generation_status_summary_totals Totals option value.
 * @param mixed $pair_state Pair marker option value.
 * @return bool Whether the compact totals are safe to use.
 */
function ai4seo_are_generation_status_summary_totals_trusted( array $generation_status_summary_totals, $pair_state ): bool {
	if ( ! ai4seo_is_valid_generation_status_summary_pair_state( $pair_state ) ) {
		return false;
	}

	$totals_checksum = hash(
		'sha256',
		maybe_serialize( ai4seo_get_comparable_generation_status_summary_totals( $generation_status_summary_totals ) )
	);

	return hash_equals( $pair_state['totals_checksum'], $totals_checksum );
}


/**
 * Determines whether both public summary values belong to one committed pair.
 *
 * @param array $generation_status_summary Full summary option value.
 * @param array $generation_status_summary_totals Totals option value.
 * @param mixed $pair_state Pair marker option value.
 * @return bool Whether the exact full value and canonical totals match the marker.
 */
function ai4seo_are_generation_status_summary_pair_values_trusted(
	array $generation_status_summary,
	array $generation_status_summary_totals,
	$pair_state
): bool {
	if ( ! ai4seo_are_generation_status_summary_totals_trusted( $generation_status_summary_totals, $pair_state ) ) {
		return false;
	}

	$full_checksum = hash( 'sha256', maybe_serialize( $generation_status_summary ) );

	return hash_equals( $pair_state['full_checksum'], $full_checksum );
}


/**
 * Invalidates the summary-pair commit marker without deleting a later writer's marker.
 *
 * @return bool|null True on deletion/already-missing success, false after bounded conflicts, null on failure.
 */
function ai4seo_invalidate_generation_status_summary_pair_state(): ?bool {
	$pair_state_option_name = ai4seo_get_generation_status_summary_pair_state_option_name();
	$attempt_limit          = ai4seo_get_generation_status_summary_persistence_attempt_limit();

	// Retry deletion from a fresh marker snapshot so a replacement marker is never removed.
	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$pair_state_snapshot = ai4seo_get_raw_option_snapshot( $pair_state_option_name );

		if ( null === $pair_state_snapshot ) {
			return null;
		}

		$delete_result = ai4seo_compare_and_delete_option_snapshot( $pair_state_option_name, $pair_state_snapshot );

		if ( null === $delete_result ) {
			return null;
		}

		if ( $delete_result ) {
			return true;
		}
	}

	return false;
}


/**
 * Replaces one option snapshot or accepts an already-owned exact value.
 *
 * @param string $option_name Exact option name.
 * @param array  $option_snapshot Raw option snapshot.
 * @param mixed  $replacement_value Replacement value.
 * @return bool|null True on success, false on a lost race, null on failure.
 */
function ai4seo_replace_generation_status_summary_option_snapshot( string $option_name, array $option_snapshot, $replacement_value ): ?bool {
	$replacement_raw_value = maybe_serialize( $replacement_value );

	if ( $option_snapshot['exists']
		&& 'no' === $option_snapshot['autoload']
		&& hash_equals( $replacement_raw_value, $option_snapshot['raw_value'] ) ) {
		ai4seo_invalidate_option_cache( $option_name );
		return true;
	}

	return ai4seo_compare_and_swap_option_snapshot(
		$option_name,
		$option_snapshot,
		$replacement_value,
		false,
		false
	);
}


/**
 * Writes totals and the commit marker for an exact, currently owned full summary.
 *
 * @param array $generation_status_summary Exact full summary owned by the caller.
 * @return bool|null True for a verified pair, false after a competing write, null on failure.
 */
function ai4seo_reconcile_generation_status_summary_pair( array $generation_status_summary ): ?bool {
	$full_raw_value         = maybe_serialize( $generation_status_summary );
	$totals                 = ai4seo_get_comparable_generation_status_summary_totals(
		ai4seo_get_generation_status_summary_totals( $generation_status_summary )
	);
	$totals_raw_value       = maybe_serialize( $totals );
	$pair_state             = ai4seo_build_generation_status_summary_pair_state( $generation_status_summary );
	$pair_state_raw_value   = maybe_serialize( $pair_state );
	$pair_state_option_name = ai4seo_get_generation_status_summary_pair_state_option_name();

	// Capture full and totals together so the initial write decision uses one coherent checkpoint.
	$initial_snapshots = ai4seo_get_raw_option_snapshots(
		array(
			AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME,
			AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
		)
	);

	if ( null === $initial_snapshots ) {
		return null;
	}

	$full_snapshot   = $initial_snapshots[ AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME ];
	$totals_snapshot = $initial_snapshots[ AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME ];

	if ( ! $full_snapshot['exists'] || ! hash_equals( $full_raw_value, $full_snapshot['raw_value'] ) ) {
		return false;
	}

	$totals_write_result = ai4seo_replace_generation_status_summary_option_snapshot(
		AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
		$totals_snapshot,
		$totals
	);

	if ( true !== $totals_write_result ) {
		return $totals_write_result;
	}

	// Totals are not committed until the full row is still the exact row used to derive them.
	$post_totals_snapshots = ai4seo_get_raw_option_snapshots(
		array(
			AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME,
			$pair_state_option_name,
		)
	);

	if ( null === $post_totals_snapshots ) {
		return null;
	}

	$full_snapshot       = $post_totals_snapshots[ AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME ];
	$pair_state_snapshot = $post_totals_snapshots[ $pair_state_option_name ];

	if ( ! $full_snapshot['exists'] || ! hash_equals( $full_raw_value, $full_snapshot['raw_value'] ) ) {
		return false;
	}

	$pair_state_write_result = ai4seo_replace_generation_status_summary_option_snapshot(
		$pair_state_option_name,
		$pair_state_snapshot,
		$pair_state
	);

	if ( true !== $pair_state_write_result ) {
		return $pair_state_write_result;
	}

	// Verify all three authoritative rows in one coherent checkpoint before reporting a committed pair.
	$verification_snapshots = ai4seo_get_raw_option_snapshots(
		array(
			AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME,
			AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
			$pair_state_option_name,
		)
	);

	if ( null === $verification_snapshots ) {
		return null;
	}

	$full_snapshot       = $verification_snapshots[ AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME ];
	$totals_snapshot     = $verification_snapshots[ AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME ];
	$pair_state_snapshot = $verification_snapshots[ $pair_state_option_name ];

	if ( ! $full_snapshot['exists']
		|| ! $totals_snapshot['exists']
		|| ! $pair_state_snapshot['exists']
		|| ! hash_equals( $full_raw_value, $full_snapshot['raw_value'] )
		|| ! hash_equals( $totals_raw_value, $totals_snapshot['raw_value'] )
		|| ! hash_equals( $pair_state_raw_value, $pair_state_snapshot['raw_value'] ) ) {
		return false;
	}

	ai4seo_reset_generation_status_summary_request_cache();
	return true;
}


/**
 * Applies a callback to the latest full summary and persists one checked pair.
 *
 * The callback runs again after a lost compare-and-swap race and must therefore
 * be deterministic and free of externally visible side effects.
 *
 * @param callable $mutation_callback Receives the latest normalized summary and returns summary/changed keys.
 * @param bool     $require_valid_existing_summary Whether missing or malformed full storage must fail closed.
 * @param mixed    $did_change Receives the successful callback's semantic-change flag.
 * @param mixed    $persisted_summary Receives the exact verified full summary.
 * @return bool Whether a complete matching pair was verified.
 */
function ai4seo_mutate_generation_status_summary(
	callable $mutation_callback,
	bool $require_valid_existing_summary = false,
	&$did_change = null,
	&$persisted_summary = null
): bool {
	$did_change        = false;
	$persisted_summary = null;
	$attempt_limit     = ai4seo_get_generation_status_summary_persistence_attempt_limit();

	// Rebuild the requested summary from the latest full snapshot after every ownership conflict.
	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$full_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME );

		if ( null === $full_snapshot ) {
			return false;
		}

		if ( $require_valid_existing_summary ) {
			if ( ! $full_snapshot['exists'] ) {
				return false;
			}

			$current_summary = ai4seo_normalize_generation_status_summary_for_incremental_sync_value( $full_snapshot['value'] );

			if ( null === $current_summary ) {
				return false;
			}
		} else {
			$current_summary = $full_snapshot['exists']
				? ai4seo_decode_generation_status_summary_storage_value( $full_snapshot['value'] )
				: array();
		}

		$summary_mutation = $mutation_callback( $current_summary );

		if ( ! is_array( $summary_mutation )
			|| array_keys( $summary_mutation ) !== array( 'summary', 'changed' )
			|| ! is_array( $summary_mutation['summary'] )
			|| ! is_bool( $summary_mutation['changed'] ) ) {
			return false;
		}

		$pair_state_invalidation = ai4seo_invalidate_generation_status_summary_pair_state();

		if ( null === $pair_state_invalidation ) {
			return false;
		}

		if ( ! $pair_state_invalidation ) {
			continue;
		}

		$full_write_result = ai4seo_replace_generation_status_summary_option_snapshot(
			AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME,
			$full_snapshot,
			$summary_mutation['summary']
		);

		if ( null === $full_write_result ) {
			return false;
		}

		if ( ! $full_write_result ) {
			continue;
		}

		$pair_result = ai4seo_reconcile_generation_status_summary_pair( $summary_mutation['summary'] );

		if ( null === $pair_result ) {
			return false;
		}

		if ( ! $pair_result ) {
			continue;
		}

		$did_change        = $summary_mutation['changed'];
		$persisted_summary = $summary_mutation['summary'];
		return true;
	}

	return false;
}


/**
 * Persists one exact full summary and its checked totals companion.
 *
 * @param array $generation_status_summary Desired full summary.
 * @return bool Whether a complete matching pair was verified.
 */
function ai4seo_persist_generation_status_summary( array $generation_status_summary ): bool {
	return ai4seo_mutate_generation_status_summary(
		static function ( array $current_summary ) use ( $generation_status_summary ): array {
			return array(
				'summary' => $generation_status_summary,
				'changed' => ai4seo_get_comparable_generation_status_summary( $current_summary )
					!== ai4seo_get_comparable_generation_status_summary( $generation_status_summary ),
			);
		}
	);
}


/**
 * Mutates the legacy totals-only summary when no authoritative full membership exists.
 *
 * Totals-only storage cannot be promoted to a full summary because it has no post-ID memberships.
 * Keep its compatibility path bounded and compare-and-swap protected while leaving the pair marker
 * absent, so readers never mistake it for a committed full/totals pair.
 *
 * @param callable $mutation_callback Receives current totals and returns totals/changed keys.
 * @return bool Whether the mutation completed or was already satisfied.
 */
function ai4seo_mutate_legacy_generation_status_summary_totals( callable $mutation_callback ): bool {
	$attempt_limit = ai4seo_get_generation_status_summary_persistence_attempt_limit();

	// Keep totals-only compatibility bounded without ever synthesizing missing full memberships.
	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$pair_state_invalidation = ai4seo_invalidate_generation_status_summary_pair_state();

		if ( null === $pair_state_invalidation ) {
			return false;
		}

		if ( ! $pair_state_invalidation ) {
			continue;
		}

		$totals_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME );

		if ( null === $totals_snapshot ) {
			return false;
		}

		$current_totals  = is_array( $totals_snapshot['value'] )
			? ai4seo_get_comparable_generation_status_summary_totals( $totals_snapshot['value'] )
			: array();
		$totals_mutation = $mutation_callback( $current_totals );

		if ( ! is_array( $totals_mutation )
			|| array_keys( $totals_mutation ) !== array( 'totals', 'changed' )
			|| ! is_array( $totals_mutation['totals'] )
			|| ! is_bool( $totals_mutation['changed'] ) ) {
			return false;
		}

		$replacement_totals = ai4seo_get_comparable_generation_status_summary_totals( $totals_mutation['totals'] );

		if ( ! $totals_mutation['changed'] && $replacement_totals === $current_totals ) {
			ai4seo_invalidate_option_cache( AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME );
			ai4seo_reset_generation_status_summary_request_cache();
			return true;
		}

		$write_result = ai4seo_replace_generation_status_summary_option_snapshot(
			AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
			$totals_snapshot,
			$replacement_totals
		);

		if ( null === $write_result ) {
			return false;
		}

		if ( $write_result ) {
			ai4seo_reset_generation_status_summary_request_cache();
			return true;
		}
	}

	return false;
}


/**
 * Returns mutable request-local content-list cache versions by exact site scope.
 *
 * @return array<string, int> Cache versions keyed by options-table and blog identity.
 */
function &ai4seo_get_content_type_list_cache_version_request_cache(): array {
	// Keep the mutable map private to its accessor, matching the nearby bump-state registry.
	static $request_cache_by_site = array();

	return $request_cache_by_site;
}


/**
 * Clears the current site's request-local content-list cache version.
 *
 * An unavailable site identity cannot safely identify one record, so clear the
 * complete request map rather than risk retaining a stale version.
 *
 * @return void
 */
function ai4seo_reset_content_type_list_cache_version_request_cache_for_current_site(): void {
	// Resolve the shared map and the exact current site identity before invalidating anything.
	$request_cache_by_site =& ai4seo_get_content_type_list_cache_version_request_cache();
	$current_site_scope    = function_exists( 'ai4seo_get_site_options_request_cache_scope' )
		? ai4seo_get_site_options_request_cache_scope()
		: '';

	// An unavailable identity cannot target one entry, so invalidate every possibly stale site.
	if ( '' === $current_site_scope ) {
		$request_cache_by_site = array();
		return;
	}

	// Exact site identities preserve valid memoized versions belonging to other sites.
	unset( $request_cache_by_site[ $current_site_scope ] );
}


/**
 * Returns the current content type list cache version.
 *
 * @return int Cache version.
 */
function ai4seo_get_content_type_list_cache_version(): int {
	// Resolve the shared map and current site identity before consulting persistent storage.
	$request_cache_by_site =& ai4seo_get_content_type_list_cache_version_request_cache();
	$current_site_scope    = function_exists( 'ai4seo_get_site_options_request_cache_scope' )
		? ai4seo_get_site_options_request_cache_scope()
		: '';

	// Reuse only the value published for this exact options-table and blog identity.
	if ( '' !== $current_site_scope && array_key_exists( $current_site_scope, $request_cache_by_site ) ) {
		return $request_cache_by_site[ $current_site_scope ];
	}

	// A raw snapshot distinguishes an authoritative missing row from a transient
	// database failure. Failed reads retain the fallback but remain retryable.
	$option_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME );

	if ( null === $option_snapshot ) {
		return 1;
	}

	// Missing and malformed versions share the stable initial namespace.
	$cache_version = $option_snapshot['exists'] ? (int) $option_snapshot['value'] : 1;

	if ( $cache_version < 1 ) {
		$cache_version = 1;
	}

	// Publish successful snapshots only when their exact site identity is available.
	if ( '' !== $current_site_scope ) {
		$request_cache_by_site[ $current_site_scope ] = $cache_version;
	}

	return $cache_version;
}


/**
 * Forces a checked content type list cache version change.
 *
 * @return bool Whether a distinct version was persisted and read back.
 */
function ai4seo_force_bump_content_type_list_cache_version(): bool {
	// A mutation attempt can observe or create a newer namespace even when a later
	// snapshot fails, so no previously memoized version may survive this boundary.
	ai4seo_reset_content_type_list_cache_version_request_cache_for_current_site();

	$maximum_attempts        = 5;
	$normalize_cache_version = static function ( $value ): ?int {
		if ( is_string( $value ) ) {
			$maximum_integer = (string) PHP_INT_MAX;

			if (
				strlen( $value ) > strlen( $maximum_integer )
				|| ( strlen( $value ) === strlen( $maximum_integer ) && strcmp( $value, $maximum_integer ) > 0 )
			) {
				return null;
			}
		}

		$normalized_versions = ai4seo_normalize_database_ids( array( $value ) );

		if ( false === $normalized_versions || 1 !== count( $normalized_versions ) ) {
			return null;
		}

		return $normalized_versions[0];
	};

	for ( $attempt = 1; $attempt <= $maximum_attempts; ++$attempt ) {
		$option_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME );

		if ( null === $option_snapshot ) {
			return false;
		}

		// Preserve the established fallback for missing, malformed, and non-positive values without loose casts.
		$previous_cache_version = $normalize_cache_version( $option_snapshot['value'] );

		if ( null === $previous_cache_version ) {
			$previous_cache_version = 1;
		}

		$time_cache_version = (int) round( microtime( true ) * 1000 );

		if ( $time_cache_version > $previous_cache_version ) {
			$new_cache_version = $time_cache_version;
		} elseif ( $previous_cache_version < PHP_INT_MAX ) {
			$new_cache_version = $previous_cache_version + 1;
		} else {
			// Retain the positive wraparound used when the stored counter reaches PHP_INT_MAX.
			$new_cache_version = 1;
		}

		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME,
			$option_snapshot,
			(string) $new_cache_version,
			false
		);

		if ( null === $compare_and_swap_result ) {
			return false;
		}

		if ( ! $compare_and_swap_result ) {
			continue;
		}

		$readback_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME );

		if ( null === $readback_snapshot || ! $readback_snapshot['exists'] ) {
			return false;
		}

		$readback_cache_version = $normalize_cache_version( $readback_snapshot['value'] );

		if (
			null === $readback_cache_version
			|| $readback_cache_version === $previous_cache_version
		) {
			return false;
		}

		// A later writer can commit after this readback, so never publish the observed snapshot.
		// Invalidating every option bucket makes the next public read resolve the latest stored row.
		ai4seo_invalidate_option_cache( AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME );

		return true;
	}

	// Reconcile caches with the last authoritative row even though this writer exhausted its retries.
	$latest_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME );

	if ( null === $latest_snapshot ) {
		return false;
	}

	if ( $latest_snapshot['exists'] ) {
		$latest_cache_version = $normalize_cache_version( $latest_snapshot['value'] );
		if ( null === $latest_cache_version ) {
			return false;
		}
	}

	// This snapshot can already be stale; invalidate instead of publishing either its value or absence.
	ai4seo_invalidate_option_cache( AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME );
	return false;
}


/**
 * Returns request-local content-list cache-version bump state by options table.
 *
 * @return array<string, array<string, mixed>> Mutable request state.
 */
function &ai4seo_get_content_type_list_cache_version_bump_state(): array {
	static $bump_state_by_options_table = array();

	return $bump_state_by_options_table;
}


/**
 * Bumps the content type list cache version immediately, then coalesces later request mutations.
 *
 * The first mutation establishes a fresh namespace before any reader can publish derived data. Every
 * later mutation marks the active site dirty so the maximum-priority shutdown finalizer advances the
 * namespace again after summary reconciliation and all other request writers have completed.
 *
 * @return void
 */
function ai4seo_bump_content_type_list_cache_version(): void {
	global $wpdb;

	$bump_state_by_options_table =& ai4seo_get_content_type_list_cache_version_bump_state();

	// A switched multisite request owns one independent version row per active options table.
	$options_table = is_object( $wpdb ) && isset( $wpdb->options ) && is_string( $wpdb->options )
		? $wpdb->options
		: '';

	if ( '' === $options_table ) {
		return;
	}

	if ( isset( $bump_state_by_options_table[ $options_table ] ) ) {
		$bump_state_by_options_table[ $options_table ]['needs_final_bump'] = true;
		return;
	}

	// Always install request state: a failed first bump is immediately dirty and must be retried by the finalizer.
	$initial_bump_succeeded = ai4seo_force_bump_content_type_list_cache_version();

	$bump_state_by_options_table[ $options_table ] = array(
		'blog_id'          => function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0,
		'needs_final_bump' => ! $initial_bump_succeeded,
	);
}


/**
 * Advances every site namespace that received mutations after its request-initial bump.
 *
 * @return bool True when every required final bump succeeded or no late mutation occurred.
 */
function ai4seo_finalize_content_type_list_cache_version_bumps(): bool {
	global $wpdb;

	$bump_state_by_options_table =& ai4seo_get_content_type_list_cache_version_bump_state();
	$all_final_bumps_succeeded   = true;

	foreach ( $bump_state_by_options_table as $options_table => &$bump_state ) {
		if ( empty( $bump_state['needs_final_bump'] ) ) {
			continue;
		}

		$switched_blog = false;

		if ( $wpdb->options !== $options_table ) {
			$blog_id = isset( $bump_state['blog_id'] ) ? absint( $bump_state['blog_id'] ) : 0;

			if ( ! is_multisite() || ! $blog_id || ! switch_to_blog( $blog_id ) || $wpdb->options !== $options_table ) {
				$all_final_bumps_succeeded = false;
				continue;
			}

			$switched_blog = true;
		}

		try {
			if ( ai4seo_force_bump_content_type_list_cache_version() ) {
				$bump_state['needs_final_bump'] = false;
			} else {
				$all_final_bumps_succeeded = false;
			}
		} finally {
			if ( $switched_blog ) {
				restore_current_blog();
			}
		}
	}
	unset( $bump_state );

	if ( ! $all_final_bumps_succeeded ) {
		ai4seo_debug_message( 443871205, 'Could not persist every final content-list cache-version bump.', true );
	}

	return $all_final_bumps_succeeded;
}


/**
 * Bumps content type list caches when an option can affect list membership or counters.
 *
 * @param string $option_name Option name.
 * @return void
 */
function ai4seo_maybe_bump_content_type_list_cache_version( string $option_name ): void {
	// Normalize hook payloads before comparing them with the cache-version option name.
	$option_name = trim( $option_name );

	// Direct version writes invalidate their request memo but must not enqueue a recursive bump.
	if ( '' === $option_name ) {
		return;
	}

	if ( AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME === $option_name ) {
		ai4seo_reset_content_type_list_cache_version_request_cache_for_current_site();
		return;
	}

	// Post-ID status options and summary counters are the only option families that affect these list caches.
	if ( in_array( $option_name, AI4SEO_ALL_POST_ID_OPTIONS, true )
		|| ai4seo_is_generation_status_summary_public_option_name( $option_name )
	) {
		ai4seo_bump_content_type_list_cache_version();
	}
}


/**
 * Registers hooks that invalidate content type list caches.
 *
 * @return void
 */
function ai4seo_add_content_type_list_cache_invalidation_hooks(): void {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Post and taxonomy mutations can change row membership even when metadata status options stay unchanged.
	$actions = array(
		'save_post',
		'delete_post',
		'deleted_post',
		'trashed_post',
		'untrashed_post',
		'transition_post_status',
		'set_object_terms',
	);

	foreach ( $actions as $this_action ) {
		add_action( $this_action, 'ai4seo_bump_content_type_list_cache_version', 5, 20 );
	}

	// Also catch direct WordPress option writes outside ai4seo_update_option().
	add_action( 'added_option', 'ai4seo_maybe_bump_content_type_list_cache_version', 10, 1 );
	add_action( 'updated_option', 'ai4seo_maybe_bump_content_type_list_cache_version', 10, 1 );
	add_action( 'deleted_option', 'ai4seo_maybe_bump_content_type_list_cache_version', 10, 1 );
	add_action( 'added_option', 'ai4seo_maybe_reset_generation_status_summary_request_cache', 10, 1 );
	add_action( 'updated_option', 'ai4seo_maybe_reset_generation_status_summary_request_cache', 10, 1 );
	add_action( 'deleted_option', 'ai4seo_maybe_reset_generation_status_summary_request_cache', 10, 1 );

	// Core option writers invalidate the commit marker before changing either public row.
	add_action( 'add_option', 'ai4seo_maybe_invalidate_generation_status_summary_pair_state', 1, 1 );
	add_action( 'update_option', 'ai4seo_maybe_invalidate_generation_status_summary_pair_state', 1, 1 );
	add_action( 'delete_option', 'ai4seo_maybe_invalidate_generation_status_summary_pair_state', 1, 1 );
}


/**
 * Invalidates the pair marker immediately before a relevant WordPress option write.
 *
 * @param string $option_name Option name.
 * @return void
 */
function ai4seo_maybe_invalidate_generation_status_summary_pair_state( string $option_name ): void {
	if ( ai4seo_is_generation_status_summary_public_option_name( $option_name ) ) {
		ai4seo_invalidate_generation_status_summary_pair_state();
	}
}


/**
 * Clears the generation status summary request cache when a related option changes.
 *
 * @param string $option_name Option name.
 * @return void
 */
function ai4seo_maybe_reset_generation_status_summary_request_cache( string $option_name ): void {
	// Only summary-related option writes can make this request-local summary cache stale.
	if ( ! ai4seo_is_generation_status_summary_public_option_name( $option_name ) ) {
		return;
	}

	ai4seo_reset_generation_status_summary_request_cache();
	ai4seo_invalidate_generation_status_summary_pair_state();
}


/**
 * Read the generation status summary option.
 *
 * @param bool $totals_only When true, return legacy totals-only format.
 * @param bool $use_direct_database_call When true, read directly from the database, bypassing any caching layers.
 * @return array Generation status summary.
 */
function ai4seo_read_generation_status_summary( bool $totals_only = true, bool $use_direct_database_call = true ): array {
	global $ai4seo_generation_status_summary_request_cache;
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 451439298, 'Prevented loop', true );
		return array();
	}

	// A request-local cache key and every direct read must belong to one exact active options table.
	if ( ! is_object( $wpdb ) || ! isset( $wpdb->options ) || ! ai4seo_is_valid_database_identifier( $wpdb->options ) ) {
		return array();
	}

	$options_table                  = $wpdb->options;
	$is_options_table_scope_current = static function () use ( $options_table ): bool {
		global $wpdb;

		return is_object( $wpdb )
			&& isset( $wpdb->options )
			&& is_string( $wpdb->options )
			&& $options_table === $wpdb->options;
	};

	if ( ! is_array( $ai4seo_generation_status_summary_request_cache ?? null ) ) {
		$ai4seo_generation_status_summary_request_cache = array();
	}

	// Cache parsed summaries for the current request so dashboard counters do not repeat DB reads and unserialization.
	$cache_key = sprintf(
		'%s|%s_%s',
		$options_table,
		$totals_only ? 'totals' : 'full',
		$use_direct_database_call ? 'direct' : 'cached'
	);

	if ( isset( $ai4seo_generation_status_summary_request_cache[ $cache_key ] ) ) {
		return $is_options_table_scope_current()
			? $ai4seo_generation_status_summary_request_cache[ $cache_key ]
			: array();
	}

	$legacy_totals_without_marker          = null;
	$generation_status_summary             = null;
	$generation_status_summary_was_loaded  = false;
	$generation_status_summary_was_present = false;
	$direct_summary_snapshots              = null;

	if ( $totals_only ) {
		// Resolve the marker key once so batch and fallback paths address the same option.
		$pair_state_option_name = ai4seo_get_generation_status_summary_pair_state_option_name();
	}

	// Batch direct totals reads into one checkpoint; a failed batch falls back to established readers below.
	if ( $totals_only && $use_direct_database_call ) {
		$direct_summary_options_table = $options_table;
		$direct_summary_snapshots     = ai4seo_get_raw_option_snapshots(
			array(
				AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME,
				AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
				$pair_state_option_name,
			)
		);

		// Never fall back, publish a repair, or memoize rows after a mid-read site-scope change.
		if ( ! $is_options_table_scope_current() || $direct_summary_options_table !== $wpdb->options ) {
			return array();
		}
	}

	// Prefer the totals companion only when both public values still match its commit marker.
	if ( $totals_only ) {
		if ( is_array( $direct_summary_snapshots ) ) {
			$totals_snapshot     = $direct_summary_snapshots[ AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME ];
			$pair_state_snapshot = $direct_summary_snapshots[ $pair_state_option_name ];

			$generation_status_summary_totals = $totals_snapshot['exists'] ? $totals_snapshot['value'] : null;
			$pair_state                       = $pair_state_snapshot['exists'] ? $pair_state_snapshot['value'] : null;
		} else {
			$generation_status_summary_totals = ai4seo_get_option( AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME, null, $use_direct_database_call );

			if ( ! $is_options_table_scope_current() ) {
				return array();
			}

			$pair_state = ai4seo_get_option(
				$pair_state_option_name,
				null,
				$use_direct_database_call
			);

			if ( ! $is_options_table_scope_current() ) {
				return array();
			}
		}

		if ( is_array( $generation_status_summary_totals ) ) {
			$generation_status_summary_totals = ai4seo_deep_sanitize( $generation_status_summary_totals, 'absint' );
			$legacy_totals_without_marker     = $generation_status_summary_totals;

			if ( ai4seo_are_generation_status_summary_totals_trusted( $generation_status_summary_totals, $pair_state ) ) {
				// Read the full value before trusting totals. Direct legacy writers invalidate the marker
				// immediately after their write, so this checksum closes that small intervening window.
				if ( is_array( $direct_summary_snapshots ) ) {
					$full_snapshot             = $direct_summary_snapshots[ AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME ];
					$generation_status_summary = $full_snapshot['exists'] ? $full_snapshot['value'] : null;
				} else {
					$generation_status_summary = ai4seo_get_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, null, $use_direct_database_call );

					if ( ! $is_options_table_scope_current() ) {
						return array();
					}
				}

				$generation_status_summary_was_loaded  = true;
				$generation_status_summary_was_present = null !== $generation_status_summary;

				if ( ! is_array( $generation_status_summary ) ) {
					$generation_status_summary = ai4seo_safe_maybe_unserialize( $generation_status_summary );
				}

				if ( ! is_array( $generation_status_summary )
					&& is_string( $generation_status_summary )
					&& '' !== $generation_status_summary
					&& ai4seo_is_json( $generation_status_summary ) ) {
					$generation_status_summary = json_decode( $generation_status_summary, true );
				}

				if ( $generation_status_summary_was_present
					&& is_array( $generation_status_summary )
					&& ai4seo_are_generation_status_summary_pair_values_trusted(
						$generation_status_summary,
						$generation_status_summary_totals,
						$pair_state
					) ) {
					if ( ! $is_options_table_scope_current() ) {
						return array();
					}

					$ai4seo_generation_status_summary_request_cache[ $cache_key ] = $generation_status_summary_totals;
					return $generation_status_summary_totals;
				}
			}
		}
	}

	// Fall back to the full summary when totals are missing, for example after upgrading from an older plugin version.
	// The totals companion is rebuilt below so later reads can verify and reuse the committed aggregate.
	if ( ! $generation_status_summary_was_loaded ) {
		if ( is_array( $direct_summary_snapshots ) ) {
			$full_snapshot             = $direct_summary_snapshots[ AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME ];
			$generation_status_summary = $full_snapshot['exists'] ? $full_snapshot['value'] : '{}';
		} else {
			$generation_status_summary = ai4seo_get_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, '{}', $use_direct_database_call );

			if ( ! $is_options_table_scope_current() ) {
				return array();
			}
		}

		$generation_status_summary_was_present = null !== $generation_status_summary;
	} elseif ( ! $generation_status_summary_was_present ) {
		// Preserve the historical missing-full fallback used by the repair and legacy-totals branches below.
		$generation_status_summary = '{}';
	}

	if ( ! is_array( $generation_status_summary ) ) {
		// Retain legacy serialized summaries while blocking object-bearing option data.
		$generation_status_summary = ai4seo_safe_maybe_unserialize( $generation_status_summary );
	}

	if ( ! is_array( $generation_status_summary )
		&& is_string( $generation_status_summary )
		&& '' !== $generation_status_summary
		&& ai4seo_is_json( $generation_status_summary ) ) {
		$generation_status_summary = json_decode( $generation_status_summary, true );
	}

	$can_repair_summary_pair = is_array( $generation_status_summary );

	if ( ! is_array( $generation_status_summary ) ) {
		$generation_status_summary = array();
	}

	$generation_status_summary = ai4seo_deep_sanitize( $generation_status_summary, 'absint' );

	if ( ! $totals_only ) {
		$generation_status_summary = ai4seo_normalize_generation_status_summary_storage( $generation_status_summary );

		if ( ! $is_options_table_scope_current() ) {
			return array();
		}

		$ai4seo_generation_status_summary_request_cache[ $cache_key ] = $generation_status_summary;

		return $generation_status_summary;
	}

	$validated_full_summary = ai4seo_normalize_generation_status_summary_for_incremental_sync_value( $generation_status_summary );

	// Preserve legacy totals when no authoritative full memberships exist to adjudicate the companion.
	if ( is_array( $legacy_totals_without_marker )
		&& ( null === $validated_full_summary
			|| ! ai4seo_get_comparable_generation_status_summary( $generation_status_summary ) ) ) {
		if ( ! $is_options_table_scope_current() ) {
			return array();
		}

		$ai4seo_generation_status_summary_request_cache[ $cache_key ] = $legacy_totals_without_marker;
		return $legacy_totals_without_marker;
	}

	$generation_status_summary_totals = ai4seo_get_generation_status_summary_totals( $generation_status_summary );

	// Repair from the latest full snapshot inside CAS retries so a stale reader can never overwrite a newer writer.
	if ( $can_repair_summary_pair && null !== $validated_full_summary ) {
		if ( ! $is_options_table_scope_current() ) {
			return array();
		}

		$pair_repair_did_change = false;
		$repaired_full_summary  = null;
		$pair_was_repaired      = ai4seo_mutate_generation_status_summary(
			static function ( array $latest_full_summary ): array {
				return array(
					'summary' => $latest_full_summary,
					'changed' => false,
				);
			},
			true,
			$pair_repair_did_change,
			$repaired_full_summary
		);

		if ( ! $is_options_table_scope_current() ) {
			return array();
		}

		if ( $pair_was_repaired && is_array( $repaired_full_summary ) ) {
			$generation_status_summary_totals = ai4seo_get_generation_status_summary_totals( $repaired_full_summary );
		}
	}

	if ( ! $is_options_table_scope_current() ) {
		return array();
	}

	$ai4seo_generation_status_summary_request_cache[ $cache_key ] = $generation_status_summary_totals;

	return $generation_status_summary_totals;
}


/**
 * Normalize stored generation status summary to include total and post_ids entries.
 *
 * @param array $generation_status_summary Raw summary from storage.
 * @return array Normalized summary with totals and post IDs.
 */
function ai4seo_normalize_generation_status_summary_storage( array $generation_status_summary ): array {
	$normalized_summary = array();

	foreach ( $generation_status_summary as $option_name => $post_type_entries ) {
		if ( ! is_array( $post_type_entries ) ) {
			continue;
		}

		foreach ( $post_type_entries as $post_type => $summary_entry ) {
			$post_ids = array();

			if ( is_array( $summary_entry ) && isset( $summary_entry['post_ids'] ) && is_array( $summary_entry['post_ids'] ) ) {
				$post_ids = array_map( 'absint', $summary_entry['post_ids'] );
			}

			$post_ids = array_values( array_unique( array_filter( $post_ids ) ) );

			$normalized_summary[ $option_name ][ $post_type ] = array(
				'total'    => count( $post_ids ),
				'post_ids' => $post_ids,
			);
		}
	}

	return $normalized_summary;
}


/**
 * Return totals-only summary for backward compatibility.
 *
 * @param array $generation_status_summary Raw or normalized summary data.
 * @return array Totals by option and post type.
 */
function ai4seo_get_generation_status_summary_totals( array $generation_status_summary ): array {
	$totals_summary = array();

	foreach ( $generation_status_summary as $option_name => $post_type_entries ) {
		if ( ! is_array( $post_type_entries ) ) {
			continue;
		}

		foreach ( $post_type_entries as $post_type => $summary_entry ) {
			if ( is_array( $summary_entry ) ) {
				if ( array_key_exists( 'total', $summary_entry ) ) {
					$totals_summary[ $option_name ][ $post_type ] = (int) $summary_entry['total'];
					continue;
				}

				if ( isset( $summary_entry['post_ids'] ) && is_array( $summary_entry['post_ids'] ) ) {
					$totals_summary[ $option_name ][ $post_type ] = count( array_unique( array_map( 'absint', $summary_entry['post_ids'] ) ) );
					continue;
				}
			}

			$totals_summary[ $option_name ][ $post_type ] = (int) $summary_entry;
		}
	}

	return $totals_summary;
}


/**
 * Append post IDs to the generation status summary and keep totals in sync.
 *
 * @param array  $generation_status_summary Summary array passed by reference.
 * @param string $option_name Option name to update.
 * @param string $post_type Post type key.
 * @param array  $post_ids Post IDs to append.
 * @return void
 */
function ai4seo_add_post_ids_to_generation_status_summary( array &$generation_status_summary, string $option_name, string $post_type, array $post_ids ): void {
	ai4seo_remove_contradictory_post_ids_from_generation_status_summary( $generation_status_summary, $option_name, $post_ids );

	if ( ! isset( $generation_status_summary[ $option_name ] ) || ! is_array( $generation_status_summary[ $option_name ] ) ) {
		$generation_status_summary[ $option_name ] = array();
	}

	if ( ! isset( $generation_status_summary[ $option_name ][ $post_type ] ) || ! is_array( $generation_status_summary[ $option_name ][ $post_type ] ) ) {
		$generation_status_summary[ $option_name ][ $post_type ] = array(
			'total'    => 0,
			'post_ids' => array(),
		);
	}

	if ( ! isset( $generation_status_summary[ $option_name ][ $post_type ]['post_ids'] ) || ! is_array( $generation_status_summary[ $option_name ][ $post_type ]['post_ids'] ) ) {
		$generation_status_summary[ $option_name ][ $post_type ]['post_ids'] = array();
	}

	$post_ids = array_filter( array_map( 'absint', $post_ids ) );
	$generation_status_summary[ $option_name ][ $post_type ]['post_ids'] = array_merge(
		$generation_status_summary[ $option_name ][ $post_type ]['post_ids'],
		$post_ids
	);
	$generation_status_summary[ $option_name ][ $post_type ]['post_ids'] = array_values(
		array_unique( array_map( 'absint', $generation_status_summary[ $option_name ][ $post_type ]['post_ids'] ) )
	);
	$generation_status_summary[ $option_name ][ $post_type ]['total']    = count(
		$generation_status_summary[ $option_name ][ $post_type ]['post_ids']
	);
}


/**
 * Merges collected option/post-type ID batches into one full summary.
 *
 * @param array $generation_status_summary Current full summary.
 * @param array $post_ids_by_option_and_type IDs grouped by option and post type.
 * @return array Updated full summary.
 */
function ai4seo_merge_generation_status_summary_post_id_batches( array $generation_status_summary, array $post_ids_by_option_and_type ): array {
	foreach ( $post_ids_by_option_and_type as $option_name => $post_type_entries ) {
		if ( ! is_array( $post_type_entries ) ) {
			continue;
		}

		foreach ( $post_type_entries as $post_type => $post_ids ) {
			if ( ! is_array( $post_ids ) ) {
				continue;
			}

			ai4seo_add_post_ids_to_generation_status_summary(
				$generation_status_summary,
				(string) $option_name,
				(string) $post_type,
				$post_ids
			);
		}
	}

	return $generation_status_summary;
}


/**
 * Validates one decoded full-summary value for incremental reconciliation.
 *
 * Canonical positive integers and their canonical decimal-string legacy form
 * are accepted. Loose floats, exponents, signs, leading zeroes, and values that
 * overflow the active PHP integer width are rejected by the shared ID policy.
 *
 * @param mixed $generation_status_summary Decoded or legacy-encoded summary value.
 * @return array|null Normalized summary, or null when a full analysis must rebuild storage.
 */
function ai4seo_normalize_generation_status_summary_for_incremental_sync_value( $generation_status_summary ): ?array {
	// Retain compatibility with serialized and JSON storage while rejecting unsafe or undecodable data.
	$generation_status_summary = ai4seo_safe_maybe_unserialize( $generation_status_summary );

	if ( ! is_array( $generation_status_summary )
		&& is_string( $generation_status_summary )
		&& '' !== $generation_status_summary
		&& ai4seo_is_json( $generation_status_summary ) ) {
		$generation_status_summary = json_decode( $generation_status_summary, true );
	}

	if ( ! is_array( $generation_status_summary ) ) {
		return null;
	}

	// Incremental updates require recognized buckets with complete post-ID memberships.
	foreach ( $generation_status_summary as $option_name => $post_type_entries ) {
		if ( ! in_array( $option_name, AI4SEO_ALL_POST_ID_OPTIONS, true ) || ! is_array( $post_type_entries ) ) {
			return null;
		}

		foreach ( $post_type_entries as $post_type => $summary_entry ) {
			if ( ! is_array( $summary_entry )
				|| ! array_key_exists( 'total', $summary_entry )
				|| ! isset( $summary_entry['post_ids'] )
				|| ! is_array( $summary_entry['post_ids'] ) ) {
				return null;
			}

			// The shared canonical policy rejects overflow and loose numeric representations before casting.
			$normalized_post_ids = ai4seo_normalize_database_ids( $summary_entry['post_ids'] );

			if ( false === $normalized_post_ids
				|| count( $normalized_post_ids ) !== count( $summary_entry['post_ids'] ) ) {
				return null;
			}

			// Stored totals must describe the same unique membership set before it can be updated incrementally.
			if ( 0 === $summary_entry['total'] || '0' === $summary_entry['total'] ) {
				$normalized_total = 0;
			} else {
				$normalized_totals = ai4seo_normalize_database_ids( array( $summary_entry['total'] ) );

				if ( false === $normalized_totals || 1 !== count( $normalized_totals ) ) {
					return null;
				}

				$normalized_total = $normalized_totals[0];
			}

			if ( count( $normalized_post_ids ) !== $normalized_total ) {
				return null;
			}

			$generation_status_summary[ $option_name ][ $post_type ] = array(
				'total'    => $normalized_total,
				'post_ids' => $normalized_post_ids,
			);
		}
	}

	// Normalize only after validating so malformed storage cannot silently become an authoritative empty summary.
	return ai4seo_normalize_generation_status_summary_storage(
		ai4seo_deep_sanitize( $generation_status_summary, 'absint' )
	);
}


/**
 * Reads and validates full summary storage before an incremental reconciliation.
 *
 * @return array|null Normalized summary, or null when a full analysis must rebuild storage.
 */
function ai4seo_read_generation_status_summary_for_incremental_sync(): ?array {
	// A distinct null default separates a missing option from a valid summary with no entries.
	$generation_status_summary = ai4seo_get_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, null, true );

	if ( null === $generation_status_summary ) {
		return null;
	}

	return ai4seo_normalize_generation_status_summary_for_incremental_sync_value( $generation_status_summary );
}


/**
 * Returns a stable semantic representation for summary equality checks.
 *
 * @param array $generation_status_summary Full generation status summary.
 * @return array
 */
function ai4seo_get_comparable_generation_status_summary( array $generation_status_summary ): array {
	// Empty option buckets carry no counter meaning, so omit them before sorting the remaining semantic keys.
	$comparable_summary = array();

	foreach ( ai4seo_normalize_generation_status_summary_storage( $generation_status_summary ) as $option_name => $post_type_entries ) {
		if ( ! $post_type_entries ) {
			continue;
		}

		foreach ( $post_type_entries as $post_type => $summary_entry ) {
			$post_ids = $summary_entry['post_ids'];
			sort( $post_ids, SORT_NUMERIC );

			$comparable_summary[ $option_name ][ $post_type ] = array(
				'total'    => count( $post_ids ),
				'post_ids' => $post_ids,
			);
		}

		ksort( $comparable_summary[ $option_name ], SORT_STRING );
	}

	ksort( $comparable_summary, SORT_STRING );

	return $comparable_summary;
}


/**
 * Schedules a one-off full analysis when incremental summary storage is unusable.
 *
 * @param bool $require_restart Whether the next rebuild must reset before resuming analysis.
 * @return bool True only when durable rebuild state and a cron event were verified.
 */
function ai4seo_schedule_generation_status_summary_rebuild( bool $require_restart = true ): bool {
	$rebuild_state = ai4seo_read_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
		false
	);

	if ( $require_restart ) {
		if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE, 'required', false ) ) {
			return false;
		}

		$rebuild_state = 'required';
	}

	if ( 'idle' === $rebuild_state ) {
		return true;
	}

	// The durable marker is the deferred contract while administrators intentionally pause heavy work.
	if ( ! ai4seo_is_posts_table_analysis_possible() ) {
		return true;
	}

	$next_scheduled = wp_next_scheduled( AI4SEO_GENERATION_STATUS_SUMMARY_REBUILD_CRON_JOB_NAME );

	if ( false === $next_scheduled ) {
		$schedule_result = wp_schedule_single_event(
			time() + 1,
			AI4SEO_GENERATION_STATUS_SUMMARY_REBUILD_CRON_JOB_NAME,
			array(),
			true
		);

		if ( is_wp_error( $schedule_result ) || true !== $schedule_result ) {
			return false;
		}
	}

	// A persisted marker without a persisted event is intentionally left for the next request to retry.
	return false !== wp_next_scheduled( AI4SEO_GENERATION_STATUS_SUMMARY_REBUILD_CRON_JOB_NAME );
}


/**
 * Completes one rebuild without overwriting a newer required-state request.
 *
 * The environmental mutation callback is re-run after every option CAS conflict. A concurrent
 * writer that changes processing back to required therefore wins instead of being overwritten by
 * an unconditional idle write from the older rebuild owner.
 *
 * @param string $completion_state Receives idle after completion or required when superseded.
 * @return bool True only when a valid completion state was observed and preserved.
 */
function ai4seo_complete_generation_status_summary_rebuild_state( &$completion_state = null ): bool {
	$completion_state   = '';
	$mutation_state     = '';
	$mutation_succeeded = ai4seo_mutate_environmental_variable_value(
		AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
		static function ( $current_state ) use ( &$mutation_state ): string {
			if ( 'required' === $current_state ) {
				$mutation_state = 'required';
				return 'required';
			}

			if ( 'processing' === $current_state ) {
				$mutation_state = 'idle';
				return 'idle';
			}

			$mutation_state = '';
			return (string) $current_state;
		},
		false
	);

	$completion_state = $mutation_state;
	return $mutation_succeeded && in_array( $mutation_state, array( 'idle', 'required' ), true );
}


/**
 * Rebuilds invalid generation status summary storage through the existing full analysis mechanism.
 *
 * @return bool True when the rebuild completed or its next bounded continuation was scheduled.
 */
function ai4seo_rebuild_generation_status_summary(): bool {
	$rebuild_state = ai4seo_read_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
		false
	);

	if ( 'idle' === $rebuild_state ) {
		return true;
	}

	// Leave the durable request untouched; a later ordinary request will schedule it after re-enabling work.
	if ( ! ai4seo_is_posts_table_analysis_possible() ) {
		return false;
	}

	$restart_required = 'required' === $rebuild_state;

	if (
		$restart_required
		&& ! ai4seo_update_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
			'processing',
			false
		)
	) {
		return false;
	}

	if ( $restart_required ) {
		$analysis_succeeded = ai4seo_force_posts_table_analysis_refresh( false, true );
	} else {
		$analysis_succeeded = ai4seo_try_start_posts_table_analysis( false, false, true );
	}

	if ( ! $analysis_succeeded ) {
		$latest_rebuild_state = ai4seo_read_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
			false
		);

		if (
			'required' !== $latest_rebuild_state
			&& ! ai4seo_update_environmental_variable(
				AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
				'required',
				false
			)
		) {
			return false;
		}

		ai4seo_schedule_generation_status_summary_rebuild( false );
		return false;
	}

	$latest_rebuild_state = ai4seo_read_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
		false
	);

	// A late source mutation requested a fresh pass while this bounded run was active.
	if ( 'required' === $latest_rebuild_state ) {
		return ai4seo_schedule_generation_status_summary_rebuild( false );
	}

	$analysis_state = ai4seo_read_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE,
		false
	);

	if ( 'completed' === $analysis_state ) {
		$completion_state = '';

		if ( ! ai4seo_complete_generation_status_summary_rebuild_state( $completion_state ) ) {
			return false;
		}

		// A source mutation that won the completion CAS needs another bounded pass.
		if ( 'required' === $completion_state ) {
			return ai4seo_schedule_generation_status_summary_rebuild( false );
		}

		return true;
	}

	// Large sites resume the same reset pass instead of restarting from zero on every cron event.
	if ( 'processing' !== $latest_rebuild_state ) {
		if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE, 'processing', false ) ) {
			return false;
		}
	}

	return ai4seo_schedule_generation_status_summary_rebuild( false );
}


/**
 * Synchronizes summary buckets for source-option memberships changed during one request.
 *
 * @param array $changed_post_ids_by_option Changed post IDs keyed by their source option name.
 * @param bool  $sync_succeeded Receives whether the final summary state was verified.
 * @return bool Whether the stored summary changed.
 */
function ai4seo_sync_generation_status_summary_for_option_changes( array $changed_post_ids_by_option, &$sync_succeeded = null ): bool {
	static $is_syncing = false;
	$sync_succeeded    = false;

	// Summary writes can trigger related option hooks, so nested reconciliation must stop at this boundary.
	if ( $is_syncing ) {
		return false;
	}

	$source_option_names        = ai4seo_get_generation_status_summary_source_option_names();
	$normalized_option_changes  = array();
	$all_changed_post_id_lookup = array();

	// Normalize the tracker lookup shape and discard options that are not represented in the summary.
	foreach ( $changed_post_ids_by_option as $option_name => $changed_post_ids ) {
		$option_name = sanitize_key( $option_name );

		if ( ! in_array( $option_name, $source_option_names, true ) || ! is_array( $changed_post_ids ) ) {
			continue;
		}

		$post_ids = array();

		foreach ( $changed_post_ids as $post_id_key => $post_id_value ) {
			$post_ids[] = ( true === $post_id_value && is_numeric( $post_id_key ) )
				? $post_id_key
				: $post_id_value;
		}

		// Reuse source-option normalization so direct callers cannot inject malformed changed-ID shapes.
		$post_ids = ai4seo_normalize_post_ids_from_option_value( $post_ids );

		if ( ! $post_ids ) {
			continue;
		}

		$normalized_option_changes[ $option_name ] = $post_ids;

		foreach ( $post_ids as $post_id ) {
			$all_changed_post_id_lookup[ $post_id ] = true;
		}
	}

	if ( ! $normalized_option_changes ) {
		return false;
	}

	// Hold the recursion guard across validation, source reads, and companion-option writes.
	$is_syncing = true;

	try {
		$post_types_by_post_id = array();

		// Resolve every changed post type once for all touched source options.
		foreach ( array_keys( $all_changed_post_id_lookup ) as $post_id ) {
			$post_type = get_post_type( $post_id );

			if ( $post_type ) {
				$post_types_by_post_id[ $post_id ] = sanitize_key( $post_type );
			}
		}

		$summary_did_change = false;
		$summary_was_stored = ai4seo_mutate_generation_status_summary(
			static function ( array $generation_status_summary ) use ( $normalized_option_changes, $post_types_by_post_id ): array {
				$original_generation_status_summary = $generation_status_summary;

				foreach ( $normalized_option_changes as $option_name => $post_ids ) {
					$post_id_lookup = array_flip( $post_ids );

					// Remove this option's changed IDs before rebuilding only its final live memberships.
					foreach ( $generation_status_summary[ $option_name ] ?? array() as $post_type => $summary_entry ) {
						$summary_post_ids = array_values(
							array_filter(
								$summary_entry['post_ids'],
								static function ( $post_id ) use ( $post_id_lookup ) {
									return ! isset( $post_id_lookup[ $post_id ] );
								}
							)
						);

						if ( ! $summary_post_ids ) {
							unset( $generation_status_summary[ $option_name ][ $post_type ] );
							continue;
						}

						$generation_status_summary[ $option_name ][ $post_type ] = array(
							'total'    => count( $summary_post_ids ),
							'post_ids' => $summary_post_ids,
						);
					}

					// Re-read live membership on every CAS retry before reconstructing the touched IDs.
					$live_post_id_lookup = array_flip( ai4seo_get_post_ids_from_option( $option_name ) );
					$post_ids_by_type    = array();

					foreach ( $post_ids as $post_id ) {
						if ( ! isset( $live_post_id_lookup[ $post_id ], $post_types_by_post_id[ $post_id ] ) ) {
							continue;
						}

						$post_ids_by_type[ $post_types_by_post_id[ $post_id ] ][] = $post_id;
					}

					foreach ( $post_ids_by_type as $post_type => $post_ids_for_type ) {
						ai4seo_add_post_ids_to_generation_status_summary(
							$generation_status_summary,
							$option_name,
							$post_type,
							$post_ids_for_type
						);
					}
				}

				return array(
					'summary' => $generation_status_summary,
					'changed' => ai4seo_get_comparable_generation_status_summary( $generation_status_summary )
						!== ai4seo_get_comparable_generation_status_summary( $original_generation_status_summary ),
				);
			},
			true,
			$summary_did_change
		);

		if ( ! $summary_was_stored ) {
			ai4seo_schedule_generation_status_summary_rebuild();
			return false;
		}

		$sync_succeeded = true;
		return $summary_did_change;
	} finally {
		// Release the recursion guard even when a storage or WordPress callback throws.
		$is_syncing = false;
	}
}


/**
 * Removes post IDs from summary buckets that contradict the bucket currently being added.
 *
 * @param array  $generation_status_summary Summary array passed by reference.
 * @param string $add_to_this_option Option receiving the post IDs.
 * @param array  $post_ids Post IDs to remove from contradictory options.
 * @return void
 */
function ai4seo_remove_contradictory_post_ids_from_generation_status_summary( array &$generation_status_summary, string $add_to_this_option, array $post_ids ): void {
	$post_ids           = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$add_to_this_option = sanitize_key( $add_to_this_option );

	if ( ! $post_ids || ! $add_to_this_option ) {
		return;
	}

	$remove_from_options = array();

	switch ( $add_to_this_option ) {
		case AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME:
			$remove_from_options = array(
				AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
			);
			break;
		case AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			$remove_from_options = array(
				AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			);
			break;
		case AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME:
			$remove_from_options = array( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME );
			break;
		case AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			$remove_from_options = array( AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
			break;
		case AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME:
			$remove_from_options = array( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );
			break;
		case AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			$remove_from_options = array( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
			break;
		case AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME:
			$remove_from_options = array( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME );
			break;
		case AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
			$remove_from_options = array( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
			break;
	}

	if ( ! $remove_from_options ) {
		return;
	}

	$post_id_lookup = array_flip( $post_ids );

	foreach ( $remove_from_options as $this_option_name ) {
		if ( ! isset( $generation_status_summary[ $this_option_name ] ) || ! is_array( $generation_status_summary[ $this_option_name ] ) ) {
			continue;
		}

		foreach ( $generation_status_summary[ $this_option_name ] as $this_post_type => $this_summary_entry ) {
			if ( ! is_array( $this_summary_entry )
				|| ! isset( $this_summary_entry['post_ids'] )
				|| ! is_array( $this_summary_entry['post_ids'] ) ) {
				continue;
			}

			$this_entry_post_ids = array_values( array_unique( array_filter( array_map( 'absint', $this_summary_entry['post_ids'] ) ) ) );
			$this_entry_post_ids = array_values(
				array_filter(
					$this_entry_post_ids,
					function ( $post_id ) use ( $post_id_lookup ) {
						return ! isset( $post_id_lookup[ $post_id ] );
					}
				)
			);

			if ( ! $this_entry_post_ids ) {
				unset( $generation_status_summary[ $this_option_name ][ $this_post_type ] );
				continue;
			}

			$generation_status_summary[ $this_option_name ][ $this_post_type ]['post_ids'] = $this_entry_post_ids;
			$generation_status_summary[ $this_option_name ][ $this_post_type ]['total']    = count( $this_entry_post_ids );
		}
	}
}


/**
 * Retrieve post IDs that have generated data stored in postmeta.
 *
 * @param array     $post_ids List of post IDs to check.
 * @param bool|null $read_succeeded Receives whether every authoritative read succeeded.
 * @param bool      $debug Whether diagnostic messages should be emitted for rejected storage.
 * @return array Sanitized list of post IDs with generated data.
 */
function ai4seo_read_generated_data_post_ids_by_post_ids( array $post_ids, ?bool &$read_succeeded = null, bool $debug = false ): array {
	global $wpdb;

	$read_succeeded = false;

	if ( empty( $post_ids ) ) {
		$read_succeeded = true;
		return array();
	}

	// Require canonical IDs before any authoritative generated-data row can influence coverage state.
	$post_ids = ai4seo_normalize_database_ids( $post_ids );

	if ( false === $post_ids ) {
		if ( $debug ) {
			ai4seo_debug_message( 890132467, esc_html( __FUNCTION__ ) . ' > Rejected a non-canonical requested post-ID collection.' );
		}

		return array();
	}

	if ( empty( $post_ids ) ) {
		$read_succeeded = true;
		return array();
	}

	$generated_data_post_id_lookup = array();
	$requested_post_id_lookup      = array_fill_keys( $post_ids, true );
	$seen_meta_id_lookup           = array();
	$seen_post_id_lookup           = array();
	$database_chunk_size           = ai4seo_get_database_chunk_size();
	$post_ids_chunks               = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		$this_query_template = 'SELECT meta_id, post_id, meta_value FROM {{postmeta_table}} ' .
			'WHERE meta_key = {{meta_key}} AND post_id IN ({{post_ids}}) ORDER BY meta_id ASC LIMIT {{query_limit}}';
		$this_query          = ai4seo_prepare_database_query(
			$this_query_template,
			array(
				'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The key lookup is paired with a bounded post-ID chunk for an indexed postmeta batch read.
				'meta_key'       => ai4seo_database_scalar_binding( '%s', AI4SEO_POST_META_GENERATED_DATA_META_KEY ),
				'post_ids'       => ai4seo_database_list_binding( '%d', array_values( $this_post_ids_chunk ) ),
				'query_limit'    => ai4seo_database_scalar_binding( '%d', count( $this_post_ids_chunk ) + 1 ),
			)
		);

		if ( false === $this_query ) {
			if ( $debug ) {
				ai4seo_debug_message( 102938475, esc_html( __FUNCTION__ ) . ' > Could not prepare a generated-data batch query.' );
			}

			return array();
		}

		$wpdb->last_error = '';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the closed table/meta/ID spec; this freshness-critical analysis read is consumed once per chunk.
		$this_generated_data_rows = $wpdb->get_results( $this_query, ARRAY_A );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321681, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! is_array( $this_generated_data_rows ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 564738291, esc_html( __FUNCTION__ ) . ' > Generated-data batch query returned an invalid result shape.' );
			}

			return array();
		}

		if ( ! $this_generated_data_rows ) {
			continue;
		}

		foreach ( $this_generated_data_rows as $this_generated_data_row ) {
			if ( ! is_array( $this_generated_data_row )
				|| ! array_key_exists( 'meta_id', $this_generated_data_row )
				|| ! array_key_exists( 'post_id', $this_generated_data_row )
				|| ! array_key_exists( 'meta_value', $this_generated_data_row )
				|| ! is_string( $this_generated_data_row['meta_value'] ) ) {
				if ( $debug ) {
					ai4seo_debug_message( 837261945, esc_html( __FUNCTION__ ) . ' > Generated-data query returned a malformed row.' );
				}

				return array();
			}

			$this_generated_data_meta_id = ai4seo_normalize_database_id( $this_generated_data_row['meta_id'] );
			$this_generated_data_post_id = ai4seo_normalize_database_id( $this_generated_data_row['post_id'] );

			if ( false === $this_generated_data_meta_id
				|| false === $this_generated_data_post_id
				|| ! isset( $requested_post_id_lookup[ $this_generated_data_post_id ] ) ) {
				if ( $debug ) {
					ai4seo_debug_message( 291840653, esc_html( __FUNCTION__ ) . ' > Generated-data query returned invalid or unexpected ownership identifiers.' );
				}

				return array();
			}

			if ( isset( $seen_meta_id_lookup[ $this_generated_data_meta_id ] ) ) {
				if ( $debug ) {
					ai4seo_debug_message( 675319824, esc_html( __FUNCTION__ ) . ' > Generated-data query returned a duplicate meta ID: ' . esc_html( $this_generated_data_meta_id ) );
				}

				return array();
			}

			if ( isset( $seen_post_id_lookup[ $this_generated_data_post_id ] ) ) {
				if ( $debug ) {
					ai4seo_debug_message(
						408572136,
						esc_html( __FUNCTION__ ) . ' > Multiple generated-data rows own the same post ID: ' . esc_html( $this_generated_data_post_id )
					);
				}

				return array();
			}

			$seen_meta_id_lookup[ $this_generated_data_meta_id ] = true;
			$seen_post_id_lookup[ $this_generated_data_post_id ] = true;
			$this_generated_data_details                         = array();
			$this_generated_data_repair_required                 = false;

			// Decode before coverage publication so unsupported rows still fail the entire source read.
			if ( ! ai4seo_decode_generated_data_postmeta_value_authoritatively(
				$this_generated_data_row['meta_value'],
				$this_generated_data_details,
				$this_generated_data_repair_required
			) ) {
				if ( $debug ) {
					ai4seo_debug_message(
						746205318,
						esc_html( __FUNCTION__ ) . ' > Generated-data value failed authoritative decoding. Post ID: ' . esc_html( $this_generated_data_post_id ) . '; meta ID: ' . esc_html( $this_generated_data_meta_id )
					);
				}

				return array();
			}

			// Persist accepted legacy cleanup before this row can influence the analysis result.
			if ( $this_generated_data_repair_required ) {
				$this_generated_data_canonical_value = ai4seo_encode_generated_data_details_for_postmeta( $this_generated_data_details );
				$this_generated_data_repair_result   = null;

				if ( false !== $this_generated_data_canonical_value ) {
					$this_generated_data_repair_result = ai4seo_compare_and_swap_generated_data_postmeta_value(
						$this_generated_data_post_id,
						$this_generated_data_meta_id,
						$this_generated_data_row['meta_value'],
						$this_generated_data_canonical_value
					);
				}

				// Encoding, database failures, and lost races all prevent cursor progress for this batch.
				if ( true !== $this_generated_data_repair_result ) {
					if ( $debug ) {
						ai4seo_debug_message(
							850193624,
							esc_html( __FUNCTION__ ) . ' > Could not safely repair legacy generated-data row. Post ID: ' . esc_html( $this_generated_data_post_id ) . '; meta ID: ' . esc_html( $this_generated_data_meta_id )
						);
					}

					return array();
				}

				// Record identifiers only after canonical storage is authoritative; generated content stays private.
				if ( $debug ) {
					ai4seo_debug_message(
						617240983,
						esc_html( __FUNCTION__ ) . ' > Repaired legacy generated-data row. Post ID: ' . esc_html( $this_generated_data_post_id ) . '; meta ID: ' . esc_html( $this_generated_data_meta_id )
					);
				}
			}

			// Empty canonical snapshots are valid repairs but do not represent generated coverage.
			if ( ! empty( $this_generated_data_details['generated_data'] ) ) {
				$generated_data_post_id_lookup[ $this_generated_data_post_id ] = true;
			}
		}
	}

	$read_succeeded = true;
	return array_map( 'intval', array_keys( $generated_data_post_id_lookup ) );
}


/**
 * Reset post-table analysis state and its generation-status summaries.
 *
 * @param bool $analysis_lock_is_held Whether the caller already owns the site advisory lock.
 * @return bool True only when source clears, state writes, summary persistence, and lock release succeed.
 */
function ai4seo_reset_posts_table_analysis( bool $analysis_lock_is_held = false ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 402005224, 'Prevented loop', true );
		return false;
	}

	$database_lock_name = ai4seo_get_posts_table_analysis_database_lock_name();
	$lock_acquired_here = false;
	$release_succeeded  = true;
	$reset_succeeded    = false;
	$reset_started      = false;

	if ( '' === $database_lock_name ) {
		return false;
	}

	if ( $analysis_lock_is_held ) {
		if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $database_lock_name ) ) {
			return false;
		}
	} else {
		if ( ! ai4seo_acquire_database_advisory_lock( $database_lock_name ) ) {
			return false;
		}

		$lock_acquired_here = true;
	}

	try {
		$reset_started = true;

		// The checked shared-fence clear verifies every coverage source before state or summary publication.
		if ( ai4seo_clear_post_id_options( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS ) ) {
			// Commit cursor and lifecycle state together so readers cannot observe a half-reset analysis.
			$progress_transition_result = ai4seo_bulk_update_environmental_variables(
				array(
					AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID => 0,
					AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE        => 'idle',
				),
				false
			);

			if ( ! empty( $progress_transition_result['success'] ) ) {
				$generation_status_summary = array();

				// Recreate the complete empty summary shape before publishing its verified pair.
				foreach ( AI4SEO_ALL_POST_ID_OPTIONS as $this_option_name ) {
					$generation_status_summary[ $this_option_name ] = array();
				}

				// Publish the empty pair only after every authoritative source and progress write was verified.
				$reset_succeeded = ai4seo_persist_generation_status_summary( $generation_status_summary );
			}
		}
	} finally {
		if ( $lock_acquired_here ) {
			$release_succeeded = ai4seo_release_database_advisory_lock( $database_lock_name );
		}
	}

	$reset_succeeded = $reset_succeeded && $release_succeeded;

	// Any failed phase after clearing began may have committed a partial source/progress state.
	if ( $reset_started && ! $reset_succeeded ) {
		ai4seo_schedule_generation_status_summary_rebuild();
	}

	return $reset_succeeded;
}


/**
 * Resets post table analysis data and starts the analysis when the current request may run it.
 *
 * @param bool $debug if true, debug information will be printed.
 * @param bool $force if true, bypasses the short analysis run throttle.
 * @param bool $allow_trusted_admin_mutation if true, trusted admin mutations may start the analysis.
 * @param bool $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @return bool True when the replacement analysis was safely claimed and run.
 */
function ai4seo_force_posts_table_analysis_refresh(
	bool $debug = false,
	bool $force = false,
	bool $allow_trusted_admin_mutation = false,
	bool $allow_debug_heavy_db_operations = false
): bool {
	// Check the start gate before resetting state so unsupported contexts cannot leave analysis stale.
	if ( ! ai4seo_can_start_posts_table_analysis( $allow_trusted_admin_mutation ) ) {
		return false;
	}

	// Preserve the existing fallback path when analysis setup is not currently possible.
	if ( ! ai4seo_is_posts_table_analysis_possible( $debug, $allow_debug_heavy_db_operations ) ) {
		return ai4seo_schedule_generation_status_summary_rebuild();
	}

	// Reset only inside the same connection-owned fence that protects the replacement analysis run.
	return ai4seo_try_start_posts_table_analysis(
		true,
		$debug,
		$force,
		$allow_trusted_admin_mutation,
		$allow_debug_heavy_db_operations,
		true
	);
}


/**
 * Forces a posts table analysis refresh after a trusted admin mutation.
 *
 * @param bool $debug if true, debug information will be printed.
 * @return bool True when the checked replacement refresh succeeds.
 */
function ai4seo_force_posts_table_analysis_refresh_after_admin_mutation( bool $debug = false ): bool {
	// Mutation-triggered refreshes are already user-initiated, so bypass the short analysis throttle.
	return ai4seo_force_posts_table_analysis_refresh( $debug, true, true );
}



// endregion
// ___________________________________________________________________________________________.
