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

	// Sites without a quiet period refresh during every trusted dashboard request.
	return 0 === ai4seo_get_analysis_refresh_interval( $current_num_posts_table_entries );
}

/**
 * Retry durable summary-rebuild scheduling during an ordinary plugin request.
 *
 * @param bool $recover_incomplete Also recover a missing continuation or an obsolete completed scan.
 * @return bool True when no rebuild is required or its durable scheduling contract is satisfied.
 */
function ai4seo_maybe_schedule_required_generation_status_summary_rebuild( bool $recover_incomplete = false ): bool {
	if ( $recover_incomplete && ! ai4seo_get_posts_table_analysis_readiness()['read_succeeded'] ) {
		return false;
	}
	if ( $recover_incomplete && ai4seo_is_posts_table_analysis_possible() ) {
		// Decide again on each CAS retry so a concurrent completion or newer rebuild always wins.
		$recovery_valid = false;
		$marked         = ai4seo_mutate_environmental_variable_overrides(
			static function ( array $overrides ): array {
				foreach ( array(
					AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE,
					AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS,
					AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
				) as $key ) {
					if ( array_key_exists( $key, $overrides ) && ! ai4seo_validate_environmental_variable_value( $key, $overrides[ $key ] ) ) {
						return array(
							'overrides' => $overrides,
							'changed'   => false,
							'result'    => false,
						);
					}
				}
				$state    = $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ] ?? 'idle';
				$progress = $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ] ?? array();
				$rebuild  = $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] ?? 'idle';
				$current  = 2 === (int) ( $progress['version'] ?? 0 );
				$changed  = 'idle' === $rebuild && ( ! $current || 'completed' !== $state || ! empty( $progress['failure'] ) );
				if ( $changed ) {
					// Resume committed bounds; only obsolete or inconsistent completion requires a fresh scan.
					$overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] = $current && 'completed' !== $state ? 'processing' : 'required';
				}
				return array(
					'overrides' => $overrides,
					'changed'   => $changed,
					'result'    => true,
				);
			},
			false,
			$recovery_valid,
			true
		);
		if ( ! $marked || ! $recovery_valid ) {
			return false;
		}
	}
	$summary_rebuild_state = ai4seo_read_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
		false
	);

	return ( 'idle' === $summary_rebuild_state && ! ai4seo_has_pending_analysis_reconciliation() && ! ai4seo_has_background_analysis()
		&& empty( ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH, false )['pending_reason'] ) )
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
	if ( ! ai4seo_maybe_schedule_required_generation_status_summary_rebuild( true ) ) {
		ai4seo_debug_message( 175943823, 'Could not schedule the required generation-status summary rebuild.', true );
	}

	// Recovery shares the scanner's lock and consumes this request's bounded analysis window.
	if ( 'idle' !== ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE, false )
		&& ( ai4seo_is_full_dashboard_request() || ai4seo_is_dashboard_refresh_ajax_request() ) ) {
		ai4seo_rebuild_generation_status_summary();
		return;
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

	if ( $last_known_num_posts_table_entries !== $current_num_posts_table_entries && ai4seo_uses_post_state_tables() ) {
		if ( ai4seo_try_start_posts_table_analysis( false ) ) {
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES, $current_num_posts_table_entries );
		}
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

	// The shared completion-based gate also controls dashboard retries after a quiet period.
	ai4seo_analyze_plugin_performance();
}

/**
 * Checks whether a completed analysis is still inside its block-based quiet period.
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

	$snapshot = ai4seo_read_authoritative_environmental_variables_snapshot(
		array(
			AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH,
			AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES,
		)
	);
	if ( empty( $snapshot['success'] ) ) {
		return true;
	}

	if ( 'completed' !== $snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ] ) {
		return false;
	}

	return ai4seo_get_analysis_refresh_timing( $snapshot['values'] )['resumes_at'] > time();
}


/**
 * Describe visible full-analysis quiet periods without starting or scheduling work.
 *
 * @param array $analysis Current authoritative analysis presentation.
 * @return array Empty outside the pause; otherwise analysis time, pause end and expected cron time.
 */
function ai4seo_get_dashboard_analysis_refresh_pause( array $analysis ): array {
	if ( 'completed' !== ( $analysis['status'] ?? '' )
		|| ai4seo_get_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS ) ) {
		return array();
	}
	$snapshot = ai4seo_read_authoritative_environmental_variables_snapshot(
		array(
			AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH,
			AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES,
		)
	);
	if ( empty( $snapshot['success'] ) || 'completed' !== $snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ] ) {
		return array();
	}

	$timing        = ai4seo_get_analysis_refresh_timing( $snapshot['values'] );
	$resumes_at    = $timing['resumes_at'];
	$last_analysis = $timing['last_analysis'];
	if ( $timing['interval'] < HOUR_IN_SECONDS || $resumes_at <= time() ) {
		return array();
	}
	// This is completion of the full scan, not the timestamp of a later targeted recheck.
	if ( ! in_array( ai4seo_classify_background_timestamp( $last_analysis, DAY_IN_SECONDS ), array( 'recent', 'stale' ), true ) ) {
		$last_analysis = 0;
	}
	$next_refresh = 0;
	$event        = AI4SEO_CRON_JOBS_ENABLED ? wp_get_scheduled_event( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME ) : false;
	if ( $event && $event->timestamp >= time() ) {
		$next_refresh = (int) $event->timestamp;
		if ( $next_refresh < $resumes_at ) {
			$interval     = (int) ( $event->interval ?? 0 );
			$next_refresh = $interval > 0 ? $next_refresh + (int) ceil( ( $resumes_at - $next_refresh ) / $interval ) * $interval : 0;
		}
	}

	// Targeted rechecks share this hook; only a requested full scan can advance the estimate.
	$has_pending_full_refresh = ! empty( $snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ]['pending_reason'] )
		|| 'required' === $snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ];
	$continuation             = AI4SEO_CRON_JOBS_ENABLED && $has_pending_full_refresh
		? wp_next_scheduled( AI4SEO_GENERATION_STATUS_SUMMARY_REBUILD_CRON_JOB_NAME )
		: false;
	if ( $continuation && $continuation >= $resumes_at && ( ! $next_refresh || $continuation < $next_refresh ) ) {
		$next_refresh = (int) $continuation;
	}

	return array(
		'last_analysis' => (int) $last_analysis,
		'resumes_at'    => $resumes_at,
		'next_refresh'  => $next_refresh,
	);
}


/**
 * Analyzes plugin performance data such as the amount of content "AI for SEO" can generate metadata for.
 *
 * @param bool   $debug if true, debug information will be printed.
 * @param bool   $force if true, bypass analysis throttling.
 * @param bool   $allow_trusted_admin_mutation if true, trusted admin mutations may start the analysis.
 * @param bool   $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @param string $start_reason Explicit trigger; only manual actions bypass the quiet period.
 * @return bool True on success, false on failure.
 */
function ai4seo_analyze_plugin_performance(
	bool $debug = false,
	bool $force = false,
	bool $allow_trusted_admin_mutation = false,
	bool $allow_debug_heavy_db_operations = false,
	string $start_reason = 'routine'
): bool {
	if ( ai4seo_should_skip_automatic_performance_analysis( $force ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 935259184, esc_html( __FUNCTION__ ) . ' > Skipping automatic performance analysis during the block-based quiet period.' );
		}

		return true;
	}

	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return false;
	}

	ai4seo_set_cron_job_status( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, 'processing' );

	// Sync the connected account before analysis when the background account snapshot is stale.
	$last_account_sync = ai4seo_robhub_api()->read_environmental_variable( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC );

	if ( ! ai4seo_get_account_readiness()['credits_ready'] || $last_account_sync > time() + 300 || $last_account_sync < time() - ai4seo_robhub_api()::BACKGROUND_ACCOUNT_SYNC_INTERVAL ) {
		ai4seo_maybe_refresh_account_state();
	}

	// Start the posts-table coverage refresh through the shared analysis queue.
	if ( ! ai4seo_try_start_posts_table_analysis( true, $debug, $force, $allow_trusted_admin_mutation, $allow_debug_heavy_db_operations, false, $start_reason ) ) {
		ai4seo_set_cron_job_status( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, 'finished-with-error' );
		return false;
	}

	// Record the analysis attempt after queuing so automatic runs can respect the throttle.
	if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME, time() ) ) {
		ai4seo_set_cron_job_status( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, 'finished-with-error' );
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
 * Returns whether the current request may start a posts table analysis.
 *
 * @param bool $allow_trusted_admin_mutation Whether trusted admin mutation requests may start analysis.
 * @return bool
 */
function ai4seo_can_start_posts_table_analysis( bool $allow_trusted_admin_mutation = false ): bool {
	// Keep the original dashboard/cron gate as the normal automatic analysis start path.
	if ( ai4seo_can_run_dashboard_or_cron_tasks() ) {
		return true;
	}

	// Admin mutation starts are opt-in so background work stays dashboard/cron-scoped by default.
	if ( ! $allow_trusted_admin_mutation ) {
		return false;
	}

	// Reuse the plugin's existing management capability check before trusting an admin mutation.
	if ( ! ai4seo_can_administer_plugin() ) {
		return false;
	}

	// Mutation-triggered starts should only happen from wp-admin, including admin AJAX.
	if ( ! ai4seo_is_function_usable( 'is_admin' ) || ! is_admin() ) {
		return false;
	}

	// AJAX mutation callers pass through the global AI4SEO nonce gate before reaching this helper.
	if ( wp_doing_ajax() && wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		return false;
	}

	return true;
}


/**
 * Expand the compact, independently validated full-scan policy.
 *
 * @param array $stored Persisted policy or its initial empty value.
 * @return array Completion, admission and coalesced request state.
 */
function ai4seo_get_analysis_refresh_policy( array $stored = array() ): array {
	return $stored + array(
		'version'         => 1,
		'started_at'      => 0,
		'reason'          => '',
		'completed_at'    => 0,
		'duration'        => 0,
		'rows'            => 0,
		'next_allowed_at' => 0,
		'pending_reason'  => '',
		'pending_at'      => 0,
	);
}

/**
 * Resolve the full-scan quiet period from the configured query block size.
 *
 * @param int $rows Physical posts-table rows, or -1 when unknown.
 * @return int Quiet period in seconds.
 */
function ai4seo_get_analysis_refresh_interval( int $rows ): int {
	if ( $rows >= 0 ) {
		$blocks = (int) ceil( $rows / ai4seo_get_database_chunk_size() );

		foreach ( AI4SEO_ANALYSIS_REFRESH_INTERVALS as $maximum_blocks => $interval ) {
			if ( $blocks <= $maximum_blocks ) {
				return $interval;
			}
		}
	}

	return AI4SEO_LARGE_SITE_AUTOMATIC_ANALYSIS_INTERVAL;
}

/**
 * Recalculate timing for existing and new policies without rewriting stored checkpoints.
 *
 * @param array $values Validated environmental snapshot from the current site.
 * @return array{interval: int, last_analysis: int, resumes_at: int} Effective timing.
 */
function ai4seo_get_analysis_refresh_timing( array $values ): array {
	$policy         = ai4seo_get_analysis_refresh_policy( $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] ?? array() );
	$progress       = $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ] ?? array();
	$count_ttl_name = ai4seo_get_environmental_variable_ttl_name( AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES );

	// Prefer a current inventory; an expired cache falls back to the last full scan's known size.
	if ( (int) ( $values[ $count_ttl_name ] ?? 0 ) > time() ) {
		$rows = (int) $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES ];
	} elseif ( $policy['completed_at'] ) {
		$rows = (int) $policy['rows'];
	} else {
		$rows = (int) ( $progress['total_rows'] ?? -1 );
	}

	$interval     = ai4seo_get_analysis_refresh_interval( $rows );
	$completed_at = (int) (
		$policy['completed_at']
		? $policy['completed_at']
		: ( $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME ] ?? 0 )
	);

	if ( ! in_array( ai4seo_classify_background_timestamp( $completed_at, DAY_IN_SECONDS ), array( 'recent', 'stale' ), true ) ) {
		$completed_at = 0;
	}

	return array(
		'interval'      => $interval,
		'last_analysis' => $completed_at,
		'resumes_at'    => $completed_at && $interval ? $completed_at + $interval : 0,
	);
}

/**
 * Mutate scheduling state without normalizing corrupted bytes into an empty policy.
 *
 * @param callable $callback Receives the expanded, validated policy.
 * @param array    $published Receives the policy from the successful CAS attempt.
 * @return bool Whether a valid state was retained or committed.
 */
function ai4seo_mutate_analysis_refresh_policy( callable $callback, &$published = null ): bool {
	return ai4seo_mutate_environmental_variable_overrides(
		static function ( array $overrides ) use ( $callback ): array {
			$stored = $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] ?? array();
			if ( ! ai4seo_validate_environmental_variable_value( AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH, $stored ) ) {
				return array();
			}
			$policy = $callback( ai4seo_get_analysis_refresh_policy( $stored ) );
			if ( ! ai4seo_validate_environmental_variable_value( AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH, $policy ) ) {
				return array();
			}
			$overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] = $policy;
			return array(
				'overrides' => $overrides,
				'changed'   => $stored !== $policy,
				'result'    => $policy,
			);
		},
		false,
		$published,
		true
	);
}

/**
 * Coalesce automatic requests without discarding the current scan checkpoint.
 *
 * @param string $reason Routine, settings, or repair trigger.
 * @param bool   $invalidate Whether existing results require a full verification.
 * @return bool Whether the request was durably stored.
 */
function ai4seo_request_analysis_refresh( string $reason, bool $invalidate = false ): bool {
	if ( ! in_array( $reason, array( 'routine', 'settings', 'repair' ), true ) ) {
		return false;
	}
	$mutation_result = null;
	return ai4seo_mutate_environmental_variable_overrides(
		static function ( array $overrides ) use ( $reason, $invalidate ): array {
			$before = $overrides;
			if ( ! ai4seo_validate_environmental_variable_value( AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH, $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] ?? array() ) ) {
				return array();
			}
			$policy = ai4seo_get_analysis_refresh_policy( $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] ?? array() );
			if ( '' === $policy['pending_reason'] || ( 'routine' === $policy['pending_reason'] && $invalidate ) ) {
				$policy['pending_reason'] = $reason;
				$policy['pending_at']     = time();
			}
			$overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] = $policy;
			if ( $invalidate ) {
				$overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] = 'required';
				if ( isset( $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_STORAGE ]['published_progress'] ) ) {
					$overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_STORAGE ]['live_invalidated'] = true;
				}
			}
			return array(
				'overrides' => $overrides,
				'changed'   => $before !== $overrides,
				'result'    => true,
			);
		},
		false,
		$mutation_result,
		true
	);
}

/**
 * Admit full scans centrally under the analysis lock; force only affects packet pacing.
 *
 * @param array  $values Authoritative environmental snapshot.
 * @param bool   $restart Whether completed work was requested again.
 * @param bool   $reset Whether an explicit reset was requested.
 * @param string $reason Receives the admitted start reason.
 * @return string run, deferred, or error.
 */
function ai4seo_admit_analysis_refresh( array &$values, bool $restart, bool &$reset, string &$reason ): string {
	if ( ! in_array( $reason, array( 'routine', 'manual', 'settings', 'repair' ), true )
		|| ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_posts_table_analysis_database_lock_name() ) ) {
		return 'error';
	}
	$progress  = $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ];
	$usable    = 2 === (int) ( $progress['version'] ?? 0 );
	$completed = $usable && 'completed' === $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ] && empty( $progress['failure'] );
	$policy    = ai4seo_get_analysis_refresh_policy( $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] );
	$rebuild   = $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ];
	// Older heavy-work pauses could mark a partially scanned table completed. Prove exhaustion first.
	if ( 'manual' !== $reason && $usable && 'completed' === $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ]
		&& ! $policy['completed_at'] && 'idle' !== $rebuild ) {
		$remaining = ai4seo_read_posts_table_analysis_tail(
			(int) $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID ],
			(int) $progress['upper_post_id']
		);
		if ( null === $remaining ) {
			return 'error';
		}
		if ( $remaining ) {
			if ( ! ai4seo_mutate_environmental_variable_value(
				AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE,
				static function ( $current ): string {
					return 'completed' === $current ? 'idle' : $current;
				},
				false
			) ) {
				return 'error';
			}
			$values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ] = 'idle';
			$completed = false;
		}
	}
	$storage = $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_STORAGE ];
	if ( $completed && 'active' === ( $storage['phase'] ?? '' ) && ! isset( $storage['published_progress'] )
		&& ! ai4seo_adopt_published_analysis( $progress, (int) $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID ] ) ) {
		return 'error';
	}
	// Adopt an already completed scan once on upgrade, without inventing a historic duration.
	if ( $completed && 0 === (int) $policy['completed_at'] ) {
		$adopted_policy = array();
		$adopted        = ai4seo_mutate_analysis_refresh_policy(
			static function ( $stored ) use ( $progress ): array {
				$current = ai4seo_get_analysis_refresh_policy( $stored );
				if ( ! $current['completed_at'] ) {
					$current['completed_at']    = time();
					$current['rows']            = (int) $progress['total_rows'];
					$current['next_allowed_at'] = $current['completed_at'] + ai4seo_get_analysis_refresh_interval( $current['rows'] );
				}
				return $current;
			},
			$adopted_policy
		);
		if ( ! $adopted ) {
			return 'error';
		}
		$policy = $adopted_policy;
	}
	$values[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] = $policy;
	if ( 'manual' !== $reason ) {
		$reset = false;
		if ( $usable && ! $completed && 'required' === $rebuild ) {
			// Finish the fixed bounds first. A later settings/repair request remains pending.
			if ( ! ai4seo_mutate_environmental_variable_value(
				AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
				static function ( $current ): string {
					return 'required' === $current ? 'resume_then_rebuild' : $current;
				},
				false
			) ) {
				return 'error';
			}
			$values[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] = 'resume_then_rebuild';
		}
		if ( $completed && ( $restart || 'required' === $rebuild || '' !== $policy['pending_reason'] )
			&& time() < ai4seo_get_analysis_refresh_timing( $values )['resumes_at'] ) {
			return ai4seo_request_analysis_refresh( 'required' === $rebuild ? 'repair' : $reason ) ? 'deferred' : 'error';
		}
		$reason = ! $usable ? 'initial' : ( '' !== $policy['pending_reason'] ? $policy['pending_reason'] : ( 'required' === $rebuild ? 'repair' : $reason ) );
	}
	$values[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] = $policy;
	return 'run';
}

/**
 * Persist the actual full-run start once, retaining newer requests during CAS retries.
 *
 * @param bool   $fresh Whether a new generation was initialized.
 * @param string $reason Admitted start reason.
 * @param array  $admitted Request snapshot observed at admission.
 * @return bool Whether the start is durable.
 */
function ai4seo_record_analysis_refresh_start( bool $fresh, string $reason, array $admitted ): bool {
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_posts_table_analysis_database_lock_name() ) ) {
		return false;
	}

	return ai4seo_mutate_analysis_refresh_policy(
		static function ( $stored ) use ( $fresh, $reason, $admitted ): array {
			$policy = ai4seo_get_analysis_refresh_policy( $stored );
			if ( $fresh || ! $policy['started_at'] ) {
				$policy['started_at'] = time();
				$policy['reason']     = $fresh ? $reason : 'resume';
			}
			if ( $fresh && $admitted['pending_reason'] === $policy['pending_reason'] && $admitted['pending_at'] === $policy['pending_at'] ) {
				$policy['pending_reason'] = '';
				$policy['pending_at']     = 0;
			}
			return $policy;
		}
	);
}

/**
 * Publish completion and its quiet period together, preserving concurrent refresh requests.
 *
 * @param bool|null $pending Receives whether bounded background replay still has work.
 * @return bool Whether the final checkpoint committed.
 */
function ai4seo_record_analysis_refresh_completion( ?bool &$pending = null ): bool {
	$pending = false;
	return ai4seo_uses_post_state_tables() ? ai4seo_publish_indexed_analysis( $pending ) : ai4seo_commit_analysis_refresh_completion();
}

/**
 * Commit completion and, when supplied, the published generation in the same CAS.
 *
 * @param array|null $storage Published routing state inside the status transaction.
 * @return bool
 */
function ai4seo_commit_analysis_refresh_completion( ?array $storage = null ): bool {
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_posts_table_analysis_database_lock_name() ) ) {
		return false;
	}

	$mutation_result = null;
	return ai4seo_mutate_environmental_variable_overrides(
		static function ( array $overrides ) use ( $storage ): array {
			if ( ! ai4seo_validate_environmental_variable_value( AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH, $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] ?? array() ) ) {
				return array();
			}
			if ( null !== $storage ) {
				$storage['published_progress'] = $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ] ?? array();
				$storage['live_invalidated']   = in_array( $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] ?? '', array( 'required', 'resume_then_rebuild' ), true );
				if ( ! ai4seo_validate_post_state_storage_status( $storage ) ) {
					return array();
				}
				$overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_STORAGE ] = $storage;
			}
			$rebuild = $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] ?? 'idle';
			if ( in_array( $rebuild, array( 'processing', 'resume_then_rebuild' ), true ) ) {
				$overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] = 'processing' === $rebuild ? 'idle' : 'required';
			}
			$policy                    = ai4seo_get_analysis_refresh_policy( $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] ?? array() );
			$now                       = time();
			$policy['duration']        = $policy['started_at'] ? max( 1, $now - (int) $policy['started_at'] ) : 0;
			$policy['completed_at']    = $now;
			$policy['started_at']      = 0;
			$policy['rows']            = (int) ( $overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ]['total_rows'] ?? 0 );
			$policy['next_allowed_at'] = $now + ai4seo_get_analysis_refresh_interval( $policy['rows'] );
			$overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ]           = $policy;
			$overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ] = 'completed';
			return array(
				'overrides' => $overrides,
				'changed'   => true,
				'result'    => true,
			);
		},
		false,
		$mutation_result,
		true
	);
}

/**
 * Determine the next useful wake-up without polling a full-scan quiet period.
 *
 * @return int|null Timestamp, or null on a failed authoritative read.
 */
function ai4seo_get_analysis_refresh_wake_time(): ?int {
	$snapshot = ai4seo_read_authoritative_environmental_variables_snapshot(
		array(
			AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH,
			AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES,
		)
	);
	if ( empty( $snapshot['success'] ) ) {
		return null;
	}
	$values = $snapshot['values'];
	$wake   = time() + 1;
	if ( 'completed' !== $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ] ) {
		$paced_background = ai4seo_has_background_analysis()
			&& ai4seo_get_analysis_refresh_interval(
				(int) ( $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ]['total_rows'] ?? -1 )
			) > 0;

		return $paced_background
			? max( $wake, (int) $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME ] + AI4SEO_BACKGROUND_ANALYSIS_INTERVAL )
			: $wake;
	}
	$wake = max( $wake, ai4seo_get_analysis_refresh_timing( $values )['resumes_at'] );
	if ( ai4seo_uses_post_state_tables() ) {
		$rows = ai4seo_post_state_query( 'SELECT MIN(expires_at) AS due FROM {{work}} WHERE kind=1', array(), true );
		if ( null === $rows ) {
			return null;
		}
		if ( isset( $rows[0]['due'] ) ) {
			$wake = min( $wake, max( time() + 1, (int) $rows[0]['due'] ) );
		}
	} elseif ( ai4seo_has_pending_analysis_reconciliation() ) {
		$wake = time() + 1;
	}
	return $wake;
}


/**
 * Tries to start the posts table analysis.
 *
 * @param bool   $restart_if_completed if true, the analysis will be restarted even if it was already completed.
 * @param bool   $debug if true, debug information will be printed.
 * @param bool   $force if true, bypasses the short analysis run throttle.
 * @param bool   $allow_trusted_admin_mutation if true, trusted admin mutations may start the analysis.
 * @param bool   $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @param bool   $reset_before_run if true, clear persisted analysis state after ownership is acquired.
 * @param string $start_reason Explicit trigger; only manual actions bypass the quiet period.
 * @return bool True when the request was safely handled, false on ownership or persistence failure.
 */
function ai4seo_try_start_posts_table_analysis(
	bool $restart_if_completed = false,
	bool $debug = false,
	bool $force = false,
	bool $allow_trusted_admin_mutation = false,
	bool $allow_debug_heavy_db_operations = false,
	bool $reset_before_run = false,
	string $start_reason = 'routine'
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
		array( $restart_if_completed, $debug, $force, $allow_debug_heavy_db_operations, $reset_before_run, $start_reason )
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
	if ( ai4seo_is_post_state_storage_paused() ) {
		return false;
	}
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
 * @param bool   $restart_if_completed if true, the analysis will be restarted even if it was already completed.
 * @param bool   $debug if true, debug information will be printed.
 * @param bool   $force if true, bypasses the short analysis run throttle.
 * @param bool   $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @param bool   $reset_before_run if true, clear persisted analysis state after ownership is acquired.
 * @param string $start_reason Explicit trigger; only manual actions bypass the quiet period.
 * @return bool True only when the owned task and advisory-lock release both succeed.
 */
function ai4seo_run_posts_table_analysis_task(
	bool $restart_if_completed = false,
	bool $debug = false,
	bool $force = false,
	bool $allow_debug_heavy_db_operations = false,
	bool $reset_before_run = false,
	string $start_reason = 'routine'
): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 423645868, 'Prevented loop', true );
		return false;
	}

	$database_lock_name     = ai4seo_get_posts_table_analysis_database_lock_name();
	$task_succeeded         = false;
	$release_succeeded      = false;
	$continuation_succeeded = true;

	if ( '' === $database_lock_name || ! ai4seo_acquire_database_advisory_lock( $database_lock_name ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 619820473, esc_html( __FUNCTION__ ) . ' > Could not acquire the posts-table analysis advisory lock.' );
		}

		return false;
	}

	try {
		if ( ai4seo_uses_post_state_tables() && ! ai4seo_cleanup_post_state_generations() ) {
			return false;
		}
		$task_succeeded = ai4seo_run_posts_table_analysis_task_under_lock(
			$restart_if_completed,
			$debug,
			$force,
			$allow_debug_heavy_db_operations,
			$reset_before_run,
			$start_reason
		);
		if ( $task_succeeded
			&& 'completed' === ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, false )
			&& ai4seo_is_posts_table_analysis_possible() ) {
			$task_succeeded = ai4seo_process_analysis_reconciliation();
		}
	} finally {
		$release_succeeded = ai4seo_release_database_advisory_lock( $database_lock_name );

		// Keep continuation scheduling outside the ownership window; dashboard requests can also resume it.
		if ( 'idle' !== ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE, false )
			|| ai4seo_has_pending_analysis_reconciliation()
			|| ai4seo_has_background_analysis()
			|| ! empty( ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH, false )['pending_reason'] ) ) {
			$continuation_succeeded = ai4seo_schedule_generation_status_summary_rebuild( false );

			// Scheduling failures must be visible even when an ordinary dashboard request started this run.
			if ( ! $continuation_succeeded ) {
				ai4seo_debug_message( 175943823, 'Could not schedule analysis continuation; the pending rebuild and committed progress remain stored.', true );
			}
		}

		if ( ! $release_succeeded && $debug ) {
			ai4seo_debug_message( 903147526, esc_html( __FUNCTION__ ) . ' > Could not release the posts-table analysis advisory lock.' );
		}
	}

	return $task_succeeded && $release_succeeded && $continuation_succeeded;
}


/**
 * Run one posts-table analysis task while the active connection owns its site lock.
 *
 * @param bool   $restart_if_completed if true, restart an already completed analysis.
 * @param bool   $debug if true, print debug information.
 * @param bool   $force if true, bypass the short analysis run throttle.
 * @param bool   $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB setting.
 * @param bool   $reset_before_run if true, clear persisted analysis state before processing.
 * @param string $start_reason Explicit trigger; only manual actions bypass the quiet period.
 * @return bool True when every required state write and bounded work phase succeeded.
 */
function ai4seo_run_posts_table_analysis_task_under_lock(
	bool $restart_if_completed,
	bool $debug,
	bool $force,
	bool $allow_debug_heavy_db_operations,
	bool $reset_before_run,
	string $start_reason = 'routine'
): bool {
	global $wpdb;
	static $dashboard_deadlines = array();
	$task_started_at            = hrtime( true );
	$database_lock_name         = ai4seo_get_posts_table_analysis_database_lock_name();

	if ( '' === $database_lock_name || ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $database_lock_name ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 844190267, esc_html( __FUNCTION__ ) . ' > Required posts-table analysis advisory lock is unavailable or not owned.' );
		}

		return false;
	}

	// A storage transition freezes the scan checkpoint; it is not a heavy-work cancellation.
	if ( ai4seo_is_post_state_storage_paused() ) {
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

		if ( 2 === (int) ( ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS, false )['version'] ?? 0 ) ) {
			return true;
		}

		// Persist completion so ordinary count checks do not spin while heavy work is intentionally paused.
		$deferred_state_succeeded = ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, 'completed', true );

		if ( ! $deferred_state_succeeded && $debug ) {
			ai4seo_debug_message( 657103984, esc_html( __FUNCTION__ ) . ' > Could not persist the deferred completed state.' );
		}

		return $deferred_state_succeeded;
	}

	// Resolve the related state markers from one coherent site-local read while ownership is held.
	$environmental_snapshot = ai4seo_read_authoritative_environmental_variables_snapshot(
		array(
			AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH,
			AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES,
		)
	);

	// Refuse partial or malformed snapshots before making any scheduling or persistence decision.
	if ( empty( $environmental_snapshot['success'] ) || ! isset( $environmental_snapshot['values'] ) || ! is_array( $environmental_snapshot['values'] ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 430965172, esc_html( __FUNCTION__ ) . ' > Could not read a valid authoritative analysis-state snapshot.' );
		}

		return false;
	}

	$background = ai4seo_has_background_analysis();
	if ( $background ) {
		if ( ! ai4seo_process_analysis_reconciliation() ) {
			return false;
		}
		if (
			ai4seo_get_analysis_refresh_interval(
				(int) ( $environmental_snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ]['total_rows'] ?? -1 )
			) > 0
			&& 'manual' !== $start_reason
			&& 'recent' === ai4seo_classify_background_timestamp(
				(int) $environmental_snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME ],
				AI4SEO_BACKGROUND_ANALYSIS_INTERVAL
			)
		) {
			return true;
		}
	}

	$admission = ai4seo_admit_analysis_refresh( $environmental_snapshot['values'], $restart_if_completed, $reset_before_run, $start_reason );
	if ( 'run' !== $admission ) {
		return 'deferred' === $admission;
	}
	// Keep the admitted snapshot: a later settings request must not turn this resume into a reset.
	$refresh_policy       = ai4seo_get_analysis_refresh_policy( $environmental_snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ] );
	$restart_if_completed = $restart_if_completed || '' !== $refresh_policy['pending_reason'];

	// Keep every initial decision on the same lock-scoped environmental generation.
	$environmental_values              = $environmental_snapshot['values'];
	$posts_table_analysis_state        = (string) $environmental_values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ];
	$posts_table_analysis_start_time   = (int) $environmental_values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME ];
	$last_core_run_time                = (int) $environmental_values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME ];
	$posts_table_analysis_last_post_id = (int) $environmental_values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID ];
	$analysis_progress                 = $environmental_values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ];
	$rebuild_state                     = $environmental_values[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ];

	// Keep legacy recovery and later initialization on the same validated progress snapshot.
	$has_initialized_progress = 2 === (int) ( $analysis_progress['version'] ?? 0 );
	$do_restart               = ! $has_initialized_progress || 'required' === $rebuild_state;

	// Log only bounded state identifiers so support can distinguish sparse IDs, retries, and fresh scans.
	if ( $debug ) {
		ai4seo_debug_message(
			890174625,
			sprintf(
				'Analysis checkpoint: state=%s; rebuild=%s; cursor=%d; upper ID=%d; examined=%d; starting rows=%d; diagnostic=%d; cron=%d; ajax=%d; force=%d; reset requested=%d; start reason=%s.',
				$posts_table_analysis_state,
				$rebuild_state,
				$posts_table_analysis_last_post_id,
				(int) ( $analysis_progress['upper_post_id'] ?? 0 ),
				(int) ( $analysis_progress['examined_rows'] ?? 0 ),
				(int) ( $analysis_progress['total_rows'] ?? 0 ),
				(int) ( $analysis_progress['failure']['code'] ?? 0 ),
				(int) wp_doing_cron(),
				(int) wp_doing_ajax(),
				(int) $force,
				(int) $reset_before_run,
				$start_reason
			)
		);
	}

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
	$is_dashboard = ! wp_doing_cron() && ( ai4seo_is_full_dashboard_request() || ai4seo_is_dashboard_refresh_ajax_request() );
	if ( $is_dashboard ) {
		$total_max_run_time = AI4SEO_DASHBOARD_ANALYSIS_MAX_EXECUTION_TIME;
	}

	// Bound iterations as well as wall-clock time so each task obeys the configured pacing envelope.
	$max_runs_per_task = $total_max_run_time / ( $usleep_between_runs / 1000000 );

	// Reuse committed bounds after a stopped run; a recent marker without a failure still defers re-entry.
	if ( 'processing' === $posts_table_analysis_state ) {
		$has_resumable_failure = $has_initialized_progress && ! empty( $analysis_progress['failure'] );
		$is_processing_stale   = 'recent' !== ai4seo_classify_background_timestamp( $posts_table_analysis_start_time, $processing_timeout );

		if ( $has_resumable_failure || $is_processing_stale ) {
			// The initial restart decision already covers pending resets and unusable legacy progress.
			if ( $debug ) {
				ai4seo_debug_message(
					604817040,
					'Previous analysis stopped; recovery=' . ( $do_restart || $reset_before_run ? 'reset' : 'resume' )
					. '; cursor=' . $posts_table_analysis_last_post_id
					. '; diagnostic=' . (int) ( $analysis_progress['failure']['code'] ?? 0 ) . '.'
				);
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
		} elseif ( ! $do_restart && ! $reset_before_run ) {
			if ( $debug ) {
				ai4seo_debug_message( 405037545, esc_html( __FUNCTION__ ) . ' > Posts table analysis already completed -> stop' );
			}

			// Preserve the completed state when no fresh analysis was requested.
			if ( in_array( $rebuild_state, array( 'processing', 'resume_then_rebuild' ), true ) ) {
				return ai4seo_complete_generation_status_summary_rebuild_state();
			}
			return true;
		}
	}

	// Suppress closely spaced ordinary runs while allowing forced and diagnostic executions.
	$run_interval_in_seconds = empty( $analysis_progress['failure'] )
		? AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME * 2 : AI4SEO_POST_TABLE_ANALYSIS_PROCESSING_TIMEOUT;

	if ( 'recent' === ai4seo_classify_background_timestamp( $last_core_run_time, $run_interval_in_seconds ) && ! $debug && ! $force ) {
		// This path deliberately skips work; the configured debug destination explains repeated refreshes.
		ai4seo_debug_message( 890174627, 'Analysis deferred by the request throttle; retry in ' . max( 0, $run_interval_in_seconds - ( time() - $last_core_run_time ) ) . ' seconds; cursor=' . $posts_table_analysis_last_post_id . '.' );
		return true;
	}

	// Persist the throttling claim before any destructive reset or analysis work begins.
	if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME, time(), false ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 716284509, esc_html( __FUNCTION__ ) . ' > Could not persist the analysis run throttle marker.' );
		}

		return false;
	}

	// Claim and invalidate the old completion together so interruption cannot skip the required reset.
	$claim_resumed_rebuild = 'resume_then_rebuild' === $rebuild_state && ( $do_restart || $reset_before_run );
	if ( 'required' === $rebuild_state || $claim_resumed_rebuild ) {
		$did_claim = false;

		$claim_succeeded = ai4seo_mutate_environmental_variable_overrides(
			static function ( array $current_overrides ) use ( $claim_resumed_rebuild ): array {
				// Recheck the pending request on every CAS retry without replacing another writer's state.
				$current_rebuild = $current_overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] ?? 'idle';
				$should_claim    = 'required' === $current_rebuild || ( $claim_resumed_rebuild && 'resume_then_rebuild' === $current_rebuild );

				// Default idle state and empty progress force a reset if this request stops before clearing sources.
				if ( $should_claim ) {
					$current_overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] = 'processing';
					unset(
						$current_overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ],
						$current_overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ]
					);
				}

				// The shared mutation contract carries the same decision for writes and no-op retries.
				return array(
					'overrides' => $current_overrides,
					'changed'   => $should_claim,
					'result'    => $should_claim,
				);
			},
			false,
			$did_claim
		);

		if ( ! $claim_succeeded || ! $did_claim ) {
			// A failed claim preserves the previous checkpoint, so log without replacing its diagnostic.
			ai4seo_debug_message( 890174624, 'Analysis rebuild claim was not acquired; storage mutation succeeded=' . (int) $claim_succeeded . '; pending request still matched=' . (int) $did_claim . '.', true );
			return false;
		}

		$rebuild_state = 'processing';
	}

	// Clear persisted progress only after the state checks have authorized a restart.
	if ( $do_restart || $reset_before_run ) {
		if ( ! ai4seo_reset_posts_table_analysis( true ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 983507261, esc_html( __FUNCTION__ ) . ' > Could not reset posts-table analysis state before this run.' );
			}

			// Keep the reset failure visible on the dashboard as well as in the manual debug result.
			return ai4seo_record_posts_table_analysis_failure( 983507261, 0, 'reset' );
		}

		// The checked reset persisted cursor zero; keep the local cursor on the same generation.
		$posts_table_analysis_last_post_id = 0;
	}

	// Capture fresh bounds only after a reset or when migrating an unfinished legacy scan.
	if ( ( $do_restart || $reset_before_run || ! $has_initialized_progress )
		&& ! ai4seo_initialize_posts_table_analysis_progress() ) {
		return false;
	}

	// A new attempt clears the previous diagnostic together with its processing marker below.
	$analysis_progress            = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS, false );
	$analysis_progress['failure'] = array();
	if ( ! ai4seo_record_analysis_refresh_start( $do_restart || $reset_before_run || ! $has_initialized_progress, $start_reason, $refresh_policy ) ) {
		return false;
	}
	// Other foreground callers retain their one-packet limit on large sites.
	if ( ! $is_dashboard && ! wp_doing_cron() && (int) ( $analysis_progress['total_rows'] ?? 0 ) >= AI4SEO_LARGE_SITE_POSTS_THRESHOLD ) {
		$max_runs_per_task = 1;
	}
	$background = ai4seo_has_background_analysis();
	if (
		$background
		&& ai4seo_get_analysis_refresh_interval( (int) ( $analysis_progress['total_rows'] ?? -1 ) ) > 0
	) {
		$max_runs_per_task  = 1;
		$total_max_run_time = 1;
	}
	$work_deadline = ( $background ? $task_started_at : hrtime( true ) ) + $total_max_run_time * 1000000000;
	if ( $is_dashboard ) {
		// Init and rendering can both enter the worker; they share one request budget.
		if ( ! isset( $dashboard_deadlines[ $wpdb->options ] ) ) {
			$dashboard_deadlines[ $wpdb->options ] = $task_started_at + $total_max_run_time * 1000000000;
		}
		$work_deadline = $dashboard_deadlines[ $wpdb->options ];
	}

	// Publish the processing marker and timestamp before entering the bounded work loop.
	$start_time = time();

	$start_transition_result = ai4seo_bulk_update_environmental_variables(
		array(
			AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE      => 'processing',
			AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME => $start_time,
			AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS   => $analysis_progress,
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
		while ( hrtime( true ) < $work_deadline && $run_counter < $max_runs_per_task ) {
			if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $database_lock_name ) ) {
				if ( $debug ) {
					ai4seo_debug_message( 167904832, esc_html( __FUNCTION__ ) . ' > Advisory-lock ownership was lost before the next analysis chunk.' );
				}

				$work_succeeded = false;
				break;
			}

			// The ownership query itself may have waited; check again before starting work.
			if ( hrtime( true ) >= $work_deadline ) {
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

			$remaining_microseconds = (int) max( 0, ( $work_deadline - hrtime( true ) ) / 1000 );
			if ( $remaining_microseconds > 0 && $run_counter < $max_runs_per_task ) {
				usleep( min( $usleep_between_runs, $remaining_microseconds ) );
			}
		}
	} catch ( Throwable $e ) {
		$work_succeeded = false;
		ai4seo_record_posts_table_analysis_failure( 842653579 );
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
			$publication_pending   = false;
			$final_state_succeeded = ai4seo_record_analysis_refresh_completion( $publication_pending );
			if ( $publication_pending ) {
				$is_finished = false;
			}

			// A leftover processing marker after a failed write is a failure, not another active worker.
			if ( ! $final_state_succeeded ) {
				ai4seo_record_posts_table_analysis_failure( 875420613 );
			}

			if ( $final_state_succeeded && $debug ) {
				ai4seo_debug_message( 174773382, esc_html( __FUNCTION__ ) . ' > Posts table analysis completed' );
			} elseif ( $debug ) {
				ai4seo_debug_message( 875420613, esc_html( __FUNCTION__ ) . ' > Could not persist the completed analysis state.' );
			}
		} else {
			// Keep a specific row diagnostic when available; otherwise explain the stalled invocation.
			if ( ! $work_succeeded ) {
				$failed_progress = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS, false );

				if ( empty( $failed_progress['failure'] ) ) {
					ai4seo_record_posts_table_analysis_failure( 734891205 );
				}
			}

			// Retain committed rows so the next request can resume the same bounded scan.
			$final_state_succeeded = ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, 'idle', false );

			// Keep a specific chunk failure when present; otherwise identify the failed lifecycle write.
			if ( ! $final_state_succeeded ) {
				if ( $work_succeeded ) {
					ai4seo_record_posts_table_analysis_failure( 248671935, 0, 'analysis_state' );
				} else {
					ai4seo_debug_message( 248671935, 'Could not persist the paused analysis state; retaining the preceding chunk failure diagnostic.', true );
				}
			}

			if ( $final_state_succeeded && $debug ) {
				ai4seo_debug_message( 679211510, 'Analysis yielded; chunks=' . $run_counter . '; elapsed=' . ( time() - $start_time ) . ' seconds; execution limit=' . $total_max_run_time . ' seconds; chunk work succeeded=' . (int) $work_succeeded . '.' );
			}
		}
	}

	if ( $debug ) {
		ai4seo_debug_message( 472361408, esc_html( __FUNCTION__ ) . ' > Current state: ' . esc_html( ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, false ) ) );
	}

	return $work_succeeded && $final_state_succeeded;
}


/**
 * Capture a bounded scan once while the current connection owns the analysis lock.
 *
 * @return bool Whether the aggregate and initialized progress were persisted successfully.
 */
function ai4seo_initialize_posts_table_analysis_progress(): bool {
	global $wpdb;

	// Only the scanner owner may establish the boundary shared by later requests.
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_posts_table_analysis_database_lock_name() ) ) {
		return false;
	}

	// One aggregate captures the initial row count and the upper ID from the same table view.
	$query = ai4seo_prepare_database_query(
		'SELECT COUNT(*) AS total_rows, COALESCE(MAX(ID), 0) AS upper_post_id FROM {{posts_table}}',
		array( 'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ) )
	);

	if ( false === $query ) {
		return ai4seo_record_posts_table_analysis_failure( 984321698 );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed table aggregate establishes one scan boundary and is persisted for all subsequent chunks.
	$aggregate = $wpdb->get_row( $query, ARRAY_A );

	if ( $wpdb->last_error || ! is_array( $aggregate )
		|| false === ai4seo_normalize_option_post_id( $aggregate['total_rows'] ?? null, true )
		|| false === ai4seo_normalize_option_post_id( $aggregate['upper_post_id'] ?? null, true ) ) {
		return ai4seo_record_posts_table_analysis_failure( 984321698 );
	}

	$blocked_options = array();
	foreach ( ai4seo_get_bulk_generation_queue_contexts() as $context ) {
		$options = ai4seo_get_bulk_generation_queue_options_by_context( $context );
		foreach ( array( 'pending', 'processing', 'failed', 'hidden', 'auto_queue_disallowed', 'force_overwrite' ) as $key ) {
			$inspection = ai4seo_inspect_post_state_candidates( $options[ $key ], array() );
			if ( ! $inspection['read_succeeded'] ) {
				return ai4seo_record_posts_table_analysis_failure( 430687192 );
			}
			if ( ! $inspection['complete'] ) {
				$blocked_options[] = $options[ $key ];
			}
		}
	}

	// Version two keeps source health separate from physical scan progress; the cursor and counts advance only after chunk commits.
	$progress = array(
		'version'         => 2,
		'upper_post_id'   => (int) $aggregate['upper_post_id'],
		'total_rows'      => (int) $aggregate['total_rows'],
		'examined_rows'   => 0,
		'failure'         => array(),
		'warnings'        => array(
			'count'   => 0,
			'samples' => array(),
		),
		'blocked_options' => $blocked_options,
	);

	$initialized = ai4seo_bulk_update_environmental_variables(
		array(
			AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS => $progress,
			AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_TIMING   => array(),
		),
		false
	);
	if ( empty( $initialized['success'] ) ) {
		return ai4seo_record_posts_table_analysis_failure( 175943822 );
	}

	// Emit the captured count and ID boundary only after initialization has actually committed.
	ai4seo_debug_message( 890174626, 'Analysis initialized; starting rows=' . $progress['total_rows'] . '; upper ID=' . $progress['upper_post_id'] . '.' );

	return true;
}


/**
 * Retain a safe failure identifier without modifying another worker's progress.
 *
 * @param int    $code Existing diagnostic code.
 * @param int    $post_id Affected post ID, or zero when unavailable.
 * @param string $field Affected field name, or an empty string.
 * @return false Always false so callers can return the failed operation directly.
 */
function ai4seo_record_posts_table_analysis_failure( int $code, int $post_id = 0, string $field = '' ): bool {
	// Logging remains available even when this connection cannot safely publish a diagnostic.
	ai4seo_debug_message( $code, 'Posts-table analysis failed. Post ID: ' . $post_id . '; field: ' . $field, true );

	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_posts_table_analysis_database_lock_name() ) ) {
		return false;
	}

	// Mutate the authoritative value so a failed read cannot replace committed progress with defaults.
	$diagnostic_stored = ai4seo_mutate_environmental_variable_value(
		AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS,
		static function ( array $progress ) use ( $code, $post_id, $field ): array {
			// Version zero retains initialization failures only when no valid bounds are stored.
			if ( ! $progress ) {
				$progress = array(
					'version'       => 0,
					'upper_post_id' => 0,
					'total_rows'    => 0,
					'examined_rows' => 0,
				);
			}

			// Preserve the latest counters on every CAS retry while replacing only this attempt's diagnostic.
			$progress['failure'] = array(
				'code'    => $code,
				'post_id' => $post_id,
				'field'   => $field,
			);

			return $progress;
		},
		false
	);

	if ( ! $diagnostic_stored ) {
		ai4seo_debug_message( 175943822, 'Could not persist the posts-table analysis failure diagnostic.', true );
	}

	return false;
}


/**
 * Resolve one authoritative presentation of analysis progress for dashboard and debug output.
 *
 * @param bool $allow_debug_heavy_db_operations Whether this reflects an authorized debug override.
 * @return array Status, translated message, and the persisted progress snapshot.
 */
function ai4seo_get_posts_table_analysis_status( bool $allow_debug_heavy_db_operations = false ): array {
	// Default to a read failure so unavailable state never appears as a completed scan.
	$snapshot  = ai4seo_read_authoritative_environmental_variables_snapshot( array( AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH ) );
	$status    = 'failed';
	$message   = __( 'Analysis state could not be read. Please run the plugin performance analysis from Help to collect diagnostics.', 'ai-for-seo' );
	$progress  = array();
	$warning   = '';
	$preparing = false;

	// All consumers use the same precedence: intentional pause, recorded failure, then completion.
	if ( ! empty( $snapshot['success'] ) ) {
		$values   = $snapshot['values'];
		$progress = $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ];
		$failure  = $progress['failure'] ?? array();
		$storage  = ai4seo_read_post_state_storage_status();

		// Storage migration pauses also apply to debug runs and are independent of the heavy-operations setting.
		if ( null === $storage ) {
			$message = __( 'Analysis is paused because post status storage could not be read. Existing progress is retained. Please use Help to inspect the problem.', 'ai-for-seo' );
		} elseif ( $storage && ! in_array( $storage['phase'], array( 'active', 'legacy' ), true ) ) {
			$status    = 'deferred';
			$message   = in_array( $storage['phase'], array( 'export_wait', 'export' ), true )
				? __( 'Analysis is paused while post status storage is being exported. Existing progress is retained.', 'ai-for-seo' )
				: __( 'Analysis is paused while post status storage is being migrated. Existing progress is retained.', 'ai-for-seo' );
			$update    = ai4seo_read_plugin_update_progress();
			$preparing = ! $failure && '' === $storage['error']
				&& in_array( $storage['phase'], array( 'draining', 'import', 'verify', 'publish' ), true )
				&& AI4SEO_PLUGIN_VERSION_NUMBER === ( $update['target'] ?? '' )
				&& ai4seo_is_expected_plugin_update_step( 'post_state_storage_v1', $update['steps']['post_state_storage_v1'] ?? array() );
			if ( $preparing ) {
				$message = __( 'SOOZ is preparing your existing SEO status data. Analysis will resume after preparation is complete. Your saved progress is retained.', 'ai-for-seo' );
			}
		} elseif ( ! ai4seo_is_posts_table_analysis_possible( $allow_debug_heavy_db_operations, $allow_debug_heavy_db_operations ) ) {
			$status  = 'deferred';
			$message = __( 'Analysis is paused because heavy database operations are disabled.', 'ai-for-seo' );
		} elseif ( $failure ) {
			$message = sprintf(
				/* translators: 1: Diagnostic code. 2: Post ID, zero if unavailable. 3: Field name. */
				__( 'Analysis failed. Diagnostic: %1$s; post ID: %2$s; field: %3$s. Run the plugin performance analysis from Help for details.', 'ai-for-seo' ),
				$failure['code'],
				$failure['post_id'],
				$failure['field']
			);
		} elseif ( 2 === (int) ( $progress['version'] ?? 0 ) && 'completed' === $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ]
			&& 'idle' === $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] ) {
			$status  = 'completed';
			$message = __( 'Analysis complete.', 'ai-for-seo' );
		} else {
			$status  = 'incomplete';
			$message = __( 'Your pages and media files are being analyzed to improve SEO coverage statistics. This page refreshes automatically while analysis is in progress.', 'ai-for-seo' );
		}
	}

	if ( ! empty( $progress['warnings']['count'] ) ) {
		if ( 'completed' === $status ) {
			$message = __( 'Analysis completed with warnings.', 'ai-for-seo' );
		}
		$warning = sprintf(
			/* translators: 1: Number of isolated entries. 2: Sample post IDs. */
			_n(
				'%1$s entry has unreadable or ambiguous data and is excluded from coverage statistics and SEO Autopilot. Percentages cover reliably analyzed entries only. Stored data is unchanged. Example post IDs: %2$s. Run the plugin performance analysis from Help for details.',
				'%1$s entries have unreadable or ambiguous data and are excluded from coverage statistics and SEO Autopilot. Percentages cover reliably analyzed entries only. Stored data is unchanged. Example post IDs: %2$s. Run the plugin performance analysis from Help for details.',
				(int) $progress['warnings']['count'],
				'ai-for-seo'
			),
			ai4seo_format_number_i18n( (int) $progress['warnings']['count'] ),
			implode( ', ', array_column( $progress['warnings']['samples'], 'post_id' ) )
		);
		$message .= ' ' . $warning;
	}

	$readiness = ai4seo_get_posts_table_analysis_readiness();
	if ( ! empty( $readiness['blocked_options'] ) ) {
		$contexts = array();
		foreach ( $readiness['contexts'] as $context => $ready ) {
			$options = ai4seo_get_bulk_generation_queue_options_by_context( $context );
			if ( array_intersect( array_values( $options ), $readiness['blocked_options'] ) ) {
				$contexts[] = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context ? __( 'Posts', 'ai-for-seo' ) : __( 'Media', 'ai-for-seo' );
			}
		}
		$source_warning = sprintf(
			/* translators: %s: Affected content contexts. */
			__( 'Some queue or exclusion data could not be read (%s). Related status totals are unavailable and new automatic queue entries are paused. Stored queues are unchanged. After correcting the data, run the analysis again from Help.', 'ai-for-seo' ),
			implode( ', ', $contexts )
		);
		$warning = trim( $warning . ' ' . $source_warning );
		if ( 'completed' === $status ) {
			$message = __( 'Analysis completed with warnings.', 'ai-for-seo' ) . ' ' . $warning;
		} else {
			$message .= ' ' . $source_warning;
		}
	}

	// Keep the worker marker available for debug consumers that must distinguish lock deferral.
	return array(
		'status'                      => $status,
		'message'                     => $message,
		'warning'                     => $warning,
		'progress'                    => $progress,
		'readiness'                   => $readiness,
		'state'                       => $snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ] ?? '',
		'preparing'                   => $preparing,
		'background'                  => ! empty( $storage['building_generation'] ) || ( ! empty( $storage['published_progress'] ) && 'completed' !== $status ),
		'waiting'                     => ! empty( $storage['published_progress'] ) && empty( $storage['building_generation'] ) && 'incomplete' === $status,
		'live_progress'               => $storage['published_progress'] ?? array(),
		'estimated_remaining_seconds' => 'incomplete' === $status ? ai4seo_get_posts_table_analysis_remaining_seconds(
			$progress,
			$snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_TIMING ] ?? array(),
			time()
		) : null,
	);
}


/**
 * Carry a small wall-clock sample alongside the next successful scan checkpoint.
 *
 * @param array $progress Progress before the checkpoint.
 * @param array $timing Previous validated timing sample.
 * @param int   $examined_rows Examined rows after the checkpoint.
 * @param int   $now Current Unix timestamp.
 * @return array Timing to publish atomically with the cursor and progress.
 */
function ai4seo_get_posts_table_analysis_checkpoint_timing( array $progress, array $timing, int $examined_rows, int $now ): array {
	// Restart the sample after a reset, changed bounds, clock reversal or a long interruption.
	if ( (int) ( $timing['last_rows'] ?? -1 ) !== (int) $progress['examined_rows']
		|| (int) ( $timing['upper_post_id'] ?? -1 ) !== (int) $progress['upper_post_id']
		|| (int) ( $timing['total_rows'] ?? -1 ) !== (int) $progress['total_rows']
		|| $now < (int) ( $timing['last_time'] ?? 0 )
		|| $now - (int) ( $timing['last_time'] ?? 0 ) > 15 * MINUTE_IN_SECONDS ) {
		$timing = array(
			'upper_post_id' => (int) $progress['upper_post_id'],
			'total_rows'    => (int) $progress['total_rows'],
			'first_rows'    => $examined_rows,
			'first_time'    => $now,
			'last_rows'     => $examined_rows,
			'last_time'     => $now,
		);
	}
	$timing['last_rows'] = $examined_rows;
	$timing['last_time'] = $now;
	return $timing;
}


/**
 * Estimate remaining wall time from committed rows, including normal scheduling gaps.
 *
 * @param array $progress Current validated scan progress.
 * @param array $timing Current validated timing sample.
 * @param int   $now Current Unix timestamp.
 * @return int|null Approximate seconds, or null until a fresh, useful sample exists.
 */
function ai4seo_get_posts_table_analysis_remaining_seconds( array $progress, array $timing, int $now ): ?int {
	if ( ! $timing || ! in_array( (int) ( $progress['version'] ?? 0 ), array( 1, 2 ), true )
		|| (int) $timing['last_rows'] !== (int) $progress['examined_rows']
		|| (int) $timing['upper_post_id'] !== (int) $progress['upper_post_id']
		|| (int) $timing['total_rows'] !== (int) $progress['total_rows']
		|| $now < (int) $timing['last_time']
		|| $now - (int) $timing['last_time'] > 15 * MINUTE_IN_SECONDS ) {
		return null;
	}
	$elapsed   = (int) $timing['last_time'] - (int) $timing['first_time'];
	$processed = (int) $timing['last_rows'] - (int) $timing['first_rows'];
	$remaining = (int) $progress['total_rows'] - (int) $progress['examined_rows'];
	if ( $elapsed < 30 || $processed <= 0 || $remaining <= 0 ) {
		return null;
	}
	$seconds = ceil( $remaining * ( $elapsed / $processed ) );
	return is_finite( $seconds ) && $seconds < PHP_INT_MAX ? (int) $seconds : null;
}


/**
 * Describe the actual outcome of one authorized manual analysis invocation.
 *
 * @param bool $analysis_succeeded Whether the bounded invocation succeeded.
 * @return array Compatible debug result with an additional analysis status.
 */
function ai4seo_get_posts_table_analysis_debug_operation_result( bool $analysis_succeeded ): array {
	// A successful bounded invocation may still leave work; a failed one cannot claim completion.
	$analysis = ai4seo_get_posts_table_analysis_status( true );

	if ( ! $analysis_succeeded && 'failed' !== $analysis['status'] ) {
		$analysis['status']  = 'processing' === $analysis['state'] ? 'deferred' : 'failed';
		$analysis['message'] = 'deferred' === $analysis['status']
			? __( 'Another analysis run is still in progress. Please try again after it finishes.', 'ai-for-seo' )
			: __( 'The analysis operation failed. Please review the debug log for the failing read, write, or lock operation.', 'ai-for-seo' );
	}

	// Legacy scans have no row-count snapshot, so only initialized scans can show numeric progress.
	$progress = $analysis['progress'];

	if ( in_array( (int) ( $progress['version'] ?? 0 ), array( 1, 2 ), true ) ) {
		$analysis['message'] .= ' ' . sprintf(
			/* translators: 1: Rows examined. 2: Number of rows at scan initialization. */
			__( '%1$s rows examined; %2$s rows at the start of this scan.', 'ai-for-seo' ),
			ai4seo_format_number_i18n( (int) $progress['examined_rows'] ),
			ai4seo_format_number_i18n( (int) $progress['total_rows'] )
		);
	}

	// Preserve the established debug response fields while carrying the more specific analysis status.
	return array(
		'success' => $analysis_succeeded && 'failed' !== $analysis['status'],
		'message' => $analysis['message'],
		'status'  => $analysis['status'],
	);
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
	// Replay coverage in the verified transition order so later missing-field batches cannot erase generated history.
	$ordered_option_names = array_unique( array_merge( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS, array_keys( $summary_batches ) ) );

	foreach ( $ordered_option_names as $option_name ) {
		$post_type_batches = $summary_batches[ $option_name ] ?? null;

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
 * @param int   $num_examined_rows Number of physical rows examined in this chunk.
 * @param int   $previous_post_id Cursor at the start of this chunk.
 * @param array $isolated_rows Attributed data problems excluded from coverage.
 * @param array $blocked_options Operational sources that could not be decoded completely.
 * @param array $eligible_post_types Eligible metadata identities mapped to post types.
 * @param array $eligible_media_types Eligible media identities mapped to post types.
 * @return bool True only when coverage, summary, cursor, and examined count were all persisted.
 */
function ai4seo_commit_posts_table_analysis_chunk(
	array $new_post_ids_by_option,
	array $generation_status_post_ids_to_add,
	int $last_processed_post_id,
	bool $debug,
	int $num_examined_rows,
	int $previous_post_id,
	array $isolated_rows = array(),
	array $blocked_options = array(),
	array $eligible_post_types = array(),
	array $eligible_media_types = array()
): bool {
	$isolated_rows     = ai4seo_merge_posts_table_analysis_issues( array(), $isolated_rows );
	$isolated_post_ids = array_column( $isolated_rows, 'post_id' );
	foreach ( $new_post_ids_by_option as $option_name => $ids ) {
		$new_post_ids_by_option[ $option_name ] = array_values( array_diff( $ids, $isolated_post_ids ) );
	}
	$transition = ai4seo_prepare_posts_table_analysis_option_transition( $new_post_ids_by_option );

	// Missing membership is the existing automatic-admission barrier. Clear stale coverage too,
	// while leaving Pending/Processing ownership and the strict generation preflight untouched.
	if ( $isolated_post_ids ) {
		foreach ( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS as $option_name ) {
			$transition['removals'][ $option_name ] = array_values( array_unique( array_merge( $transition['removals'][ $option_name ] ?? array(), $isolated_post_ids ) ) );
		}
	}

	if ( $debug ) {
		foreach ( $transition['additions'] as $option_name => $post_ids ) {
			ai4seo_debug_message( 732600128, esc_html( __FUNCTION__ ) . ' > Adding to option ' . $option_name . ': ' . count( $post_ids ) . ' post ids' );
		}
	}

	if ( ai4seo_uses_post_state_tables() ) {
		if ( ! ai4seo_commit_indexed_analysis_memberships( $transition, $generation_status_post_ids_to_add, $isolated_post_ids, $eligible_post_types, $eligible_media_types ) ) {
			return ai4seo_record_posts_table_analysis_failure( 175943821 );
		}
	} else {
		global $wpdb;
		$previous_write_scope                           = $GLOBALS['ai4seo_analysis_summary_write_scope'] ?? null;
		$GLOBALS['ai4seo_analysis_summary_write_scope'] = (string) $wpdb->options;
		try {
			$coverage_stored = ! ( $transition['additions'] || $transition['removals'] )
			|| ai4seo_apply_post_id_option_transition( $transition['additions'], $transition['removals'] );
		} finally {
			$GLOBALS['ai4seo_analysis_summary_write_scope'] = $previous_write_scope;
		}
		if ( ! $coverage_stored ) {
			ai4seo_debug_message( 175943821, esc_html( __FUNCTION__ ) . ' > Could not persist and verify the analyzed coverage transition.', true );
			return ai4seo_record_posts_table_analysis_failure( 175943821 );
		}

		$verified_summary_batches = ai4seo_get_verified_posts_table_analysis_summary_batches(
			$generation_status_post_ids_to_add,
			$transition['additions']
		);

		// Merge only verified chunk memberships into the latest full summary inside the bounded CAS retry loop.
		$summary_did_change                   = false;
		$current_generation_status_summary    = null;
		$generation_status_summary_was_stored = ai4seo_mutate_generation_status_summary(
			static function ( array $latest_summary ) use ( $verified_summary_batches, $isolated_post_ids ): array {
				// The shared merge also removes contradictory coverage memberships on retried chunks.
				$updated_summary = ai4seo_merge_generation_status_summary_post_id_batches(
					$latest_summary,
					$verified_summary_batches
				);
				if ( $isolated_post_ids ) {
					foreach ( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS as $option_name ) {
						foreach ( $updated_summary[ $option_name ] ?? array() as $post_type => $entry ) {
							$remaining_ids                                 = array_values( array_diff( $entry['post_ids'], $isolated_post_ids ) );
							$updated_summary[ $option_name ][ $post_type ] = array(
								'total'    => count( $remaining_ids ),
								'post_ids' => $remaining_ids,
							);
						}
					}
				}

				return array(
					'summary' => $updated_summary,
					'changed' => $latest_summary !== $updated_summary,
				);
			},
			false,
			$summary_did_change,
			$current_generation_status_summary
		);

		if ( ! $generation_status_summary_was_stored || ! is_array( $current_generation_status_summary ) ) {
			ai4seo_debug_message( 984321697, esc_html( __FUNCTION__ ) . ' > Could not persist a matching generation status summary pair.', true );
			return ai4seo_record_posts_table_analysis_failure( 984321697 );
		}

		if ( $debug ) {
			// Compact totals retain useful accounting evidence without dumping every post ID on large sites.
			ai4seo_debug_message( 417529305, esc_html( __FUNCTION__ ) . ' > Current generation status totals: ' . esc_html( ai4seo_stringify( ai4seo_get_generation_status_summary_totals( $current_generation_status_summary ) ) ) );
		}
	}
	do_action( 'ai4seo_post_state_checkpoint', 'before_analysis_checkpoint' );
	// Verify the expected cursor only after coverage and summary writes have both succeeded.
	$snapshot = ai4seo_read_authoritative_environmental_variables_snapshot();
	$progress = $snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ] ?? array();

	if ( empty( $snapshot['success'] ) || ! in_array( (int) ( $progress['version'] ?? 0 ), array( 1, 2 ), true )
		|| (int) $snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID ] !== $previous_post_id
		|| $last_processed_post_id <= $previous_post_id
		|| $last_processed_post_id > (int) $progress['upper_post_id']
		|| $num_examined_rows <= 0
		|| $num_examined_rows > PHP_INT_MAX - (int) $progress['examined_rows'] ) {
		return ai4seo_record_posts_table_analysis_failure( 175943822 );
	}

	// Publish cursor and count in one environmental CAS so retrying a failed write cannot double-count.
	$timing                    = ai4seo_get_posts_table_analysis_checkpoint_timing(
		$progress,
		$snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_TIMING ],
		(int) $progress['examined_rows'] + $num_examined_rows,
		time()
	);
	$progress['examined_rows'] = (int) $progress['examined_rows'] + $num_examined_rows;
	$progress['failure']       = array();
	if ( 2 === (int) $progress['version'] ) {
		$progress['blocked_options'] = array_values( array_unique( array_merge( $progress['blocked_options'], $blocked_options ) ) );
	}
	if ( $isolated_rows ) {
		$warnings             = $progress['warnings'] ?? array(
			'count'   => 0,
			'samples' => array(),
		);
		$progress['warnings'] = array(
			'count'   => (int) $warnings['count'] + count( $isolated_rows ),
			'samples' => array_slice( array_merge( $warnings['samples'], $isolated_rows ), 0, AI4SEO_POSTS_TABLE_ANALYSIS_WARNING_SAMPLE_LIMIT ),
		);
	}
	$progress_transition = ai4seo_bulk_update_environmental_variables(
		array(
			AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID => $last_processed_post_id,
			AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS     => $progress,
			AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_TIMING       => $timing,
		),
		false
	);

	if ( empty( $progress_transition['success'] ) ) {
		ai4seo_debug_message( 175943822, esc_html( __FUNCTION__ ) . ' > Could not persist the posts table analysis cursor.', true );
		return ai4seo_record_posts_table_analysis_failure( 175943822 );
	}

	// Log cursor movement only after the matching row count has committed successfully.
	if ( $debug ) {
		ai4seo_debug_message( 408476980, 'Analysis chunk committed; cursor=' . $previous_post_id . ' -> ' . $last_processed_post_id . '; chunk rows=' . $num_examined_rows . '; examined=' . $progress['examined_rows'] . '; starting rows=' . $progress['total_rows'] . '; upper ID=' . $progress['upper_post_id'] . '.' );
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
 * @param array      $option_names Option names.
 * @param bool|null  $read_succeeded Receives whether every option read succeeded.
 * @param bool       $debug Whether diagnostic messages should be emitted for rejected storage.
 * @param array|null $blocked_options Opt-in analysis output of incomplete sources; null retains strict reads.
 * @param array|null $candidate_ids Restrict indexed reads to this bounded packet.
 * @return array Post IDs keyed by option name.
 */
function ai4seo_read_posts_table_analysis_post_id_options( array $option_names, ?bool &$read_succeeded = null, bool $debug = false, ?array &$blocked_options = null, ?array $candidate_ids = null ): array {
	if ( null !== $candidate_ids && ai4seo_uses_post_state_tables() ) {
		$members        = ai4seo_read_post_state_for_ids( $candidate_ids, $option_names );
		$read_succeeded = null !== $members;
		return null === $members ? array() : array_map( 'array_keys', $members );
	}
	$read_succeeded     = false;
	$post_ids_by_option = array();

	foreach ( $option_names as $option_name ) {
		$this_read_succeeded = false;
		if ( null !== $blocked_options ) {
			$inspection          = ai4seo_inspect_analysis_post_id_option( $option_name );
			$this_read_succeeded = $inspection['read_succeeded'];
			$this_post_ids       = $inspection['ids'];
			if ( ! $inspection['complete'] ) {
				$blocked_options[] = $option_name;
			}
		} else {
			$this_post_ids = ai4seo_read_posts_table_analysis_post_id_option( (string) $option_name, $this_read_succeeded, $debug );
		}

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
	if ( ai4seo_is_post_state_storage_paused() ) {
		return false;
	}

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 381607754, 'Prevented loop', true );
		return ai4seo_record_posts_table_analysis_failure( 381607754 );
	}

	// Initialization belongs to the locked coordinator; direct chunks must already have fixed bounds.
	$progress = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS, false );
	if ( ! in_array( (int) ( $progress['version'] ?? 0 ), array( 1, 2 ), true ) ) {
		return ai4seo_record_posts_table_analysis_failure( 430965172 );
	}

	$allowed_attachment_mime_types = ai4seo_get_allowed_attachment_mime_types();
	$total_rows_per_run            = ai4seo_get_database_chunk_size();

	// Cursor-based pagination query.
	$raw_posts_query = $wpdb->prepare(
		"SELECT ID, post_author, post_type, post_status, post_mime_type, post_date_gmt, post_date, post_modified_gmt, post_modified
		FROM {$wpdb->posts}
		WHERE ID > %d AND ID <= %d
		ORDER BY ID ASC
		LIMIT %d",
		$posts_table_analysis_last_post_id,
		(int) $progress['upper_post_id'],
		$total_rows_per_run
	);

	if ( ! is_string( $raw_posts_query ) || '' === $raw_posts_query ) {
		if ( $debug ) {
			ai4seo_debug_message( 218640975, esc_html( __FUNCTION__ ) . ' > Could not prepare the posts-table batch query.' );
		}

		return ai4seo_record_posts_table_analysis_failure( 218640975 );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The prepare call binds the cursor, upper boundary, and limit; analysis requires the current ordered batch.
	$raw_posts = $wpdb->get_results( $raw_posts_query, ARRAY_A );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321680, 'Database error: ' . $wpdb->last_error, true );
		return ai4seo_record_posts_table_analysis_failure( 984321680 );
	}

	if ( ! is_array( $raw_posts ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 865109437, esc_html( __FUNCTION__ ) . ' > Posts-table batch query returned an invalid result shape.' );
		}

		return ai4seo_record_posts_table_analysis_failure( 865109437 );
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

	// Every physical row advances the cursor; only supported types need dependent reads.
	$supported_post_types              = array_merge( ai4seo_get_supported_post_types(), array( AI4SEO_TAXONOMY_PROXY_POST_TYPE ) );
	$supported_attachment_post_types   = ai4seo_get_supported_attachment_post_types();
	$coverage_candidate_post_id_lookup = array();
	$supported_post_ids                = array();
	$last_validated_post_id            = $posts_table_analysis_last_post_id;
	$bulk_generation_date_filter_state = ai4seo_get_current_bulk_generation_date_filter_state();
	$row_issues                        = array();
	$row_classifications               = array();
	$blocked_options                   = array();
	foreach ( $raw_posts as $raw_post ) {
		$this_raw_post_id = is_array( $raw_post ) ? ai4seo_normalize_database_id( $raw_post['ID'] ?? null ) : false;
		if ( false === $this_raw_post_id || $this_raw_post_id <= $last_validated_post_id || $this_raw_post_id > (int) $progress['upper_post_id'] ) {
			return ai4seo_record_posts_table_analysis_failure( 943170586, 0, 'id' );
		}
		$last_validated_post_id = $this_raw_post_id;
		$classification         = ai4seo_classify_posts_table_analysis_row(
			$raw_post,
			$supported_post_types,
			$supported_attachment_post_types,
			$allowed_attachment_mime_types,
			$bulk_generation_date_filter_state
		);

		$row_classifications[ $this_raw_post_id ] = $classification;
		if ( '' !== $classification['field'] ) {
			ai4seo_add_posts_table_analysis_issue( $row_issues, $this_raw_post_id, $classification['field'], 'invalid_post_field' );
			continue;
		}
		if ( $classification['supported'] ) {
			$supported_post_ids[] = $this_raw_post_id;
		}
		if ( $classification['eligible'] ) {
			$coverage_candidate_post_id_lookup[ $this_raw_post_id ] = true;
		}
	}

	// Unsupported records never need generated-data reads, even when they contain malformed metadata.
	$generated_data_read_succeeded      = false;
	$generated_data_failure             = array();
	$isolated_generated_data_rows       = array();
	$generated_data_all_post_ids        = ai4seo_read_generated_data_post_ids_by_post_ids( $supported_post_ids, $generated_data_read_succeeded, $debug, $generated_data_failure, $isolated_generated_data_rows );
	$generated_data_post_id_lookup      = array_fill_keys( $generated_data_all_post_ids, true );
	$isolated_generated_data_rows       = ai4seo_merge_posts_table_analysis_issues( $row_issues, $isolated_generated_data_rows );
	$isolated_generated_data_lookup     = array_fill_keys( array_column( $isolated_generated_data_rows, 'post_id' ), true );
	$generated_data_post_ids            = array();
	$generated_data_attachment_post_ids = array();

	if ( ! $generated_data_read_succeeded ) {
		if ( $debug ) {
			ai4seo_debug_message( 381962704, esc_html( __FUNCTION__ ) . ' > Generated-data batch read failed.' );
		}

		return ai4seo_record_posts_table_analysis_failure(
			$generated_data_failure['code'] ?? 381962704,
			$generated_data_failure['post_id'] ?? 0,
			'ai4seo_generated_data'
		);
	}

	// Resolve the same date-filter state used by queue excavation and content-list eligibility.
	$bulk_generation_date_filter_state = ai4seo_get_current_bulk_generation_date_filter_state();

	// PRE-FILTER POSTS & SEPARATE ATTACHMENTS.

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
			$supported_post_ids,
			$disabled_taxonomy_terms,
			null,
			$disabled_taxonomy_read_succeeded
		);

		if ( ! $disabled_taxonomy_read_succeeded ) {
			if ( $debug ) {
				ai4seo_debug_message( 526819403, esc_html( __FUNCTION__ ) . ' > Disabled-taxonomy exclusion read failed.' );
			}

			return ai4seo_record_posts_table_analysis_failure( 526819403 );
		}

		$post_ids_with_disabled_taxonomy_terms = array_flip( $post_ids_with_disabled_taxonomy_terms );
	}

	if ( $supported_post_types || $supported_attachment_post_types ) {
		foreach ( $raw_posts as $this_raw_post ) {
			$this_post_id = (int) $this_raw_post['ID'];
			// Unknown generated history must never be interpreted as missing or fully covered.
			if ( isset( $isolated_generated_data_lookup[ $this_post_id ] ) ) {
				continue;
			}
			$this_post_type = $this_raw_post['post_type'];
			if ( AI4SEO_TAXONOMY_PROXY_POST_TYPE === $this_post_type ) {
				$archive = ai4seo_get_taxonomy_proxy_editor_context( $this_post_id );
				if ( is_wp_error( $archive ) ) {
					ai4seo_add_posts_table_analysis_issue( $row_issues, $this_post_id, 'taxonomy', 'source_unavailable' );
					continue;
				}
				if ( $archive['available'] ) {
					$posts[ $this_post_id ] = $this_raw_post;
					if ( isset( $generated_data_post_id_lookup[ $this_post_id ] ) ) {
						$generated_data_post_ids[ $this_post_id ] = $this_post_type;
					}
				}
				continue;
			}
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
			if ( $is_post_type && isset( $generated_data_post_id_lookup[ $this_post_id ] ) ) {
				$generated_data_post_ids[ $this_post_id ] = $this_post_type;
			}

			if ( $is_attachment_post_type && isset( $generated_data_post_id_lookup[ $this_post_id ] ) ) {
				$generated_data_attachment_post_ids[ $this_post_id ] = $this_post_type;
			}

			// Reuse the first pass's status and MIME checks before applying the remaining coverage filters.
			if ( ! isset( $coverage_candidate_post_id_lookup[ $this_post_id ] ) ) {
				continue;
			}

			// Apply the exact > / <= boundary contract used by the prepared queue queries.
			$posted_date_timestamp = $row_classifications[ $this_post_id ]['timestamp'];

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

				$posts[ $this_post_id ] = $this_raw_post;
			} elseif ( $is_attachment_post_type ) {
				if ( $is_disabled_attachment_attributes_wpml_language ) {
					continue;
				}

				if ( $disabled_attachment_post_author_ids && isset( $disabled_attachment_post_author_ids[ (int) $this_raw_post['post_author'] ] ) ) {
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

	// The first pass retained the final physical ID, including rows excluded from every coverage bucket.
	$last_processed_post_id = $last_validated_post_id;

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
				AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME,
				AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME,
			),
			$metadata_status_options_read_succeeded,
			$debug,
			$blocked_options,
			$post_ids
		);

		if ( ! $metadata_status_options_read_succeeded ) {
			if ( $debug ) {
				ai4seo_debug_message( 709458126, esc_html( __FUNCTION__ ) . ' > Required metadata status-option read failed.' );
			}

			return ai4seo_record_posts_table_analysis_failure( 709458126 );
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
		$coverage_issues                              = array();
		$analysis_field_states                        = null;
		$metadata_coverage_read_succeeded             = false;
		$percentage_of_available_metadata_by_post_ids = ai4seo_read_percentage_of_available_metadata_by_post_ids( $post_ids, 0, $metadata_coverage_read_succeeded, $analysis_field_states, $coverage_issues );

		if ( ! $metadata_coverage_read_succeeded ) {
			if ( $debug ) {
				ai4seo_debug_message( 264975831, esc_html( __FUNCTION__ ) . ' > Metadata coverage read failed.' );
			}

			return ai4seo_record_posts_table_analysis_failure( 264975831 );
		}

		$isolated_generated_data_rows = ai4seo_merge_posts_table_analysis_issues( $isolated_generated_data_rows, $coverage_issues );
		foreach ( $percentage_of_available_metadata_by_post_ids as $this_post_id => $this_percentage ) {
			$this_post_id                       = (int) $this_post_id;
			$this_post_type                     = $posts[ $this_post_id ]['post_type'] ?? '';
			$this_post_was_generated            = isset( $generated_data_post_id_lookup[ $this_post_id ] );
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
		$coverage_issues                    = array();
		$attachment_coverage_read_succeeded = false;
		$attachment_attributes_coverage     = ai4seo_read_and_analyse_attachment_attributes_coverage( $attachment_posts_ids, $attachment_coverage_read_succeeded, $coverage_issues );

		if ( ! $attachment_coverage_read_succeeded ) {
			if ( $debug ) {
				ai4seo_debug_message( 817236490, esc_html( __FUNCTION__ ) . ' > Attachment coverage read failed.' );
			}

			return ai4seo_record_posts_table_analysis_failure( 817236490 );
		}

		$isolated_generated_data_rows             = ai4seo_merge_posts_table_analysis_issues( $isolated_generated_data_rows, $coverage_issues );
		$attachment_status_options_read_succeeded = false;
		$attachment_status_post_ids_by_option     = ai4seo_read_posts_table_analysis_post_id_options(
			array(
				AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			),
			$attachment_status_options_read_succeeded,
			$debug,
			$blocked_options,
			$attachment_posts_ids
		);

		if ( ! $attachment_status_options_read_succeeded ) {
			if ( $debug ) {
				ai4seo_debug_message( 135790864, esc_html( __FUNCTION__ ) . ' > Required attachment status-option read failed.' );
			}

			return ai4seo_record_posts_table_analysis_failure( 135790864 );
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
			$debug,
			$num_raw_posts,
			$posts_table_analysis_last_post_id,
			$isolated_generated_data_rows,
			$blocked_options,
			array_column( $posts, 'post_type', 'ID' ),
			array_column( $attachment_posts, 'post_type', 'ID' )
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
 * @return array|null Normalized full summary, or null when the stored value cannot be decoded.
 */
function ai4seo_decode_generation_status_summary_storage_value( $stored_value ): ?array {
	// Read both serialized option values and the legacy JSON representation through the same decoder.
	$generation_status_summary = ai4seo_safe_maybe_unserialize( $stored_value );

	if ( ! is_array( $generation_status_summary )
		&& is_string( $generation_status_summary )
		&& '' !== $generation_status_summary
		&& ai4seo_is_json( $generation_status_summary ) ) {
		$generation_status_summary = json_decode( $generation_status_summary, true );
	}

	// Preserve decode failure as a distinct state so incremental writers cannot erase stored evidence.
	if ( ! is_array( $generation_status_summary ) ) {
		return null;
	}

	// Valid decoded summaries retain the shared membership-normalization contract.
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
 * @param bool     $allow_invalid_existing_summary Whether an explicit replacement may discard undecodable storage.
 * @return bool Whether a complete matching pair was verified.
 */
function ai4seo_mutate_generation_status_summary(
	callable $mutation_callback,
	bool $require_valid_existing_summary = false,
	&$did_change = null,
	&$persisted_summary = null,
	bool $allow_invalid_existing_summary = false
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

			// A failed decode must never become the empty base of an incremental summary write.
			if ( null === $current_summary ) {
				if ( ! $allow_invalid_existing_summary ) {
					// Preserve the damaged pair while requesting the reset that ordinary analysis resumes cannot perform.
					ai4seo_schedule_generation_status_summary_rebuild();
					return false;
				}

				// Only the explicit replacement path may discard an undecodable base.
				$current_summary = array();
			}
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
	// Supply the shared mutator's output slots even though this caller only needs its success flag.
	$did_change        = false;
	$persisted_summary = null;

	// Full replacements, including analysis resets, may repair storage that incremental writes reject.
	return ai4seo_mutate_generation_status_summary(
		static function ( array $current_summary ) use ( $generation_status_summary ): array {
			return array(
				'summary' => $generation_status_summary,
				'changed' => ai4seo_get_comparable_generation_status_summary( $current_summary )
					!== ai4seo_get_comparable_generation_status_summary( $generation_status_summary ),
			);
		},
		false,
		$did_change,
		$persisted_summary,
		true
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
		$blog_id       = isset( $bump_state['blog_id'] ) ? absint( $bump_state['blog_id'] ) : 0;

		// A deleted site has no remaining namespace to invalidate. A failed site
		// lookup must retain pending work instead of treating it as a deletion.
		if ( is_multisite() && $blog_id ) {
			$site = get_site( $blog_id );
			if ( ! $site && ! $wpdb->last_error ) {
				// A cached miss may originate from an earlier failed read.
				wp_cache_delete( $blog_id, 'sites' );
				$site = get_site( $blog_id );
			}
			if ( ! $site ) {
				if ( $wpdb->last_error ) {
					wp_cache_delete( $blog_id, 'sites' );
					$all_final_bumps_succeeded = false;
				} else {
					$bump_state['needs_final_bump'] = false;
				}
				continue;
			}
		}

		if ( $wpdb->options !== $options_table ) {
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
 * Returns generated-data counts by post type for reset controls.
 *
 * @return array Generated-data counts by post type.
 */
function ai4seo_get_generated_data_reset_post_type_counts(): array {
	$generation_status_summary       = ai4seo_read_generation_status_summary( true, true );
	$generated_data_post_type_counts = array();
	$generated_data_option_names     = array(
		AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);

	foreach ( $generated_data_option_names as $this_generated_data_option_name ) {
		if ( ! isset( $generation_status_summary[ $this_generated_data_option_name ] )
			|| ! is_array( $generation_status_summary[ $this_generated_data_option_name ] ) ) {
			continue;
		}

		foreach ( $generation_status_summary[ $this_generated_data_option_name ] as $this_post_type => $this_num_generated_entries ) {
			$this_post_type             = sanitize_key( $this_post_type );
			$this_num_generated_entries = absint( $this_num_generated_entries );

			if ( ! $this_post_type || ! $this_num_generated_entries ) {
				continue;
			}

			if ( ! isset( $generated_data_post_type_counts[ $this_post_type ] ) ) {
				$generated_data_post_type_counts[ $this_post_type ] = 0;
			}

			$generated_data_post_type_counts[ $this_post_type ] += $this_num_generated_entries;
		}
	}

	return $generated_data_post_type_counts;
}


/**
 * Read the generation status summary option.
 *
 * @param bool $totals_only When true, return legacy totals-only format.
 * @param bool $use_direct_database_call When true, read directly from the database, bypassing any caching layers.
 * @return array Generation status summary.
 */
function ai4seo_read_generation_status_summary( bool $totals_only = true, bool $use_direct_database_call = true ): array {
	if ( ai4seo_uses_post_state_tables() ) {
		return ( $totals_only ? ai4seo_read_post_state_totals() : ai4seo_export_post_state_summary() ) ?? array();
	}
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
 * Read durable, site-local reconciliation work without accepting malformed storage as empty.
 *
 * @param bool $due_only Select only currently runnable indexed work.
 * @return array|null Raw snapshot with a validated ID-to-generation map, or null on failure.
 */
function ai4seo_read_analysis_reconciliation_snapshot( bool $due_only = false ): ?array {
	$state = ai4seo_read_post_state_storage_status();
	if ( null === $state || ( $state && 'legacy' !== $state['phase'] ) ) {
		$ids = null;
		if ( null !== $state && $due_only ) {
			$rows = ai4seo_post_state_query(
				'SELECT post_id FROM {{work}} FORCE INDEX (expiry) WHERE kind=1 AND expires_at<={{now}} ORDER BY expires_at,work_id LIMIT {{limit}}',
				array(
					'now'   => time(),
					'limit' => ai4seo_post_state_batch_size(),
				),
				true
			);
			if ( null === $rows ) {
				return null;
			}
			$ids = array_map( 'intval', array_column( $rows, 'post_id' ) );
		}
		$value = null === $state ? null : ai4seo_read_post_state_work( 1, $ids, 1000 );
		return null === $value ? null : array(
			'exists' => true,
			'value'  => $value,
		);
	}
	$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_ANALYSIS_RECONCILIATION_OPTION_NAME );
	if ( null === $snapshot ) {
		return null;
	}
	if ( ! $snapshot['exists'] ) {
		$snapshot['value'] = array();
	}
	if ( ! is_array( $snapshot['value'] ) ) {
		return null;
	}
	foreach ( $snapshot['value'] as $post_id => $entry ) {
		if ( false === ai4seo_normalize_database_id( $post_id ) || ! is_array( $entry )
			|| ! isset( $entry['token'], $entry['coverage'] ) || ! is_string( $entry['token'] )
			|| '' === $entry['token'] || ! is_bool( $entry['coverage'] ) ) {
			return null;
		}
		if ( array_key_exists( 'options', $entry ) && ( ! is_array( $entry['options'] ) || ! $entry['options']
			|| count( array_filter( $entry['options'], 'is_string' ) ) !== count( $entry['options'] )
			|| array_diff( $entry['options'], ai4seo_get_generation_status_summary_source_option_names() ) ) ) {
			return null;
		}
	}
	return $snapshot;
}


/**
 * Treat unreadable reconciliation storage as pending rather than silently admitting stale state.
 *
 * @return bool Whether reconciliation remains outstanding.
 */
function ai4seo_has_pending_analysis_reconciliation(): bool {
	if ( null === ai4seo_read_post_state_storage_status() ) {
		return true;
	}
	if ( ai4seo_uses_post_state_tables() ) {
		$pending = ai4seo_post_state_query( 'SELECT 1 FROM {{work}} WHERE kind=1 LIMIT 1', array(), true );
		return null === $pending || (bool) $pending;
	}
	$snapshot = ai4seo_read_analysis_reconciliation_snapshot();
	return null === $snapshot || ! empty( $snapshot['value'] );
}


/**
 * Atomically admit new generations or acknowledge only the generations actually reconciled.
 *
 * @param array     $post_ids IDs to admit.
 * @param bool      $refresh_coverage Whether current coverage also needs refreshing.
 * @param array     $completed Previously read entries whose successful work may be acknowledged.
 * @param array     $options_by_post_id Changed summary sources per ID; omitted entries retain the conservative all-source scope.
 * @param bool|null $durably_stored Receives the write outcome independently of scheduler availability.
 * @return bool Whether the queue mutation and its required wake-up succeeded.
 */
function ai4seo_mutate_analysis_reconciliation( array $post_ids, bool $refresh_coverage = false, array $completed = array(), array $options_by_post_id = array(), ?bool &$durably_stored = null ): bool {
	$durably_stored = false;
	$name           = ai4seo_get_post_id_option_transition_semaphore_name();
	$owns           = null !== ai4seo_get_held_semaphore_record( ai4seo_get_semaphore_option_key( $name ) );
	if ( ! $owns && ! ai4seo_acquire_semaphore( $name ) ) {
		return false;
	}
	$released = true;
	try {
		$stored         = ai4seo_mutate_analysis_reconciliation_under_lock( $post_ids, $refresh_coverage, $completed, $options_by_post_id );
		$durably_stored = $stored;
	} finally {
		if ( ! $owns ) {
			$released = ai4seo_release_semaphore( $name );
		}
	}
	// Wake targeted work even when a full scan is scheduled far in the future.
	return $stored && $released
		&& ( $owns || ! $post_ids || ai4seo_schedule_generation_status_summary_rebuild( false ) );
}

/**
 * Select the current storage only after acquiring the shared write fence.
 *
 * @param array $post_ids Identities to admit.
 * @param bool  $refresh_coverage Coverage refresh.
 * @param array $completed Exact completed tokens.
 * @param array $options_by_post_id Source scopes.
 * @return bool
 */
function ai4seo_mutate_analysis_reconciliation_under_lock( array $post_ids, bool $refresh_coverage = false, array $completed = array(), array $options_by_post_id = array() ): bool {
	$state = ai4seo_read_post_state_storage_status();
	if ( null === $state || ( $state && 'legacy' !== $state['phase'] ) ) {
		global $wpdb;
		if ( null !== $state && ( $GLOBALS['ai4seo_post_state_transaction'] ?? null ) === (string) $wpdb->options ) {
			return ai4seo_post_state_enqueue_under_lock( ai4seo_normalize_option_post_id_collection( $post_ids ), $refresh_coverage, $completed, $options_by_post_id );
		}
		return null !== $state && ai4seo_post_state_transaction(
			static function ( array $current ) use ( $post_ids, $refresh_coverage, $completed, $options_by_post_id ): bool {
				return $current && 'legacy' !== $current['phase']
					&& ai4seo_post_state_enqueue_under_lock( ai4seo_normalize_option_post_id_collection( $post_ids ), $refresh_coverage, $completed, $options_by_post_id );
			}
		);
	}
	$post_ids      = ai4seo_normalize_option_post_id_collection( $post_ids );
	$token         = wp_generate_uuid4();
	$attempt_limit = ai4seo_get_post_id_option_mutation_attempt_limit();
	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$snapshot = ai4seo_read_analysis_reconciliation_snapshot();
		if ( null === $snapshot ) {
			return false;
		}
		$updated = $snapshot['value'];
		foreach ( $completed as $post_id => $entry ) {
			if ( ( $updated[ $post_id ] ?? null ) === $entry ) {
				unset( $updated[ $post_id ] );
			}
		}
		foreach ( $post_ids as $post_id ) {
			$options             = array_values(
				array_unique(
					array_merge(
						$options_by_post_id[ $post_id ] ?? ai4seo_get_generation_status_summary_source_option_names(),
						isset( $updated[ $post_id ] ) ? ( $updated[ $post_id ]['options'] ?? ai4seo_get_generation_status_summary_source_option_names() ) : array()
					)
				)
			);
			$updated[ $post_id ] = array(
				'token'    => $token,
				'coverage' => $refresh_coverage || ! empty( $updated[ $post_id ]['coverage'] )
					|| ( isset( $options_by_post_id[ $post_id ] ) && (bool) array_intersect( $options, AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS ) ),
				'options'  => $options,
			);
		}
		if ( $updated === $snapshot['value'] ) {
			return true;
		}
		$result = ai4seo_compare_and_swap_option_snapshot( AI4SEO_ANALYSIS_RECONCILIATION_OPTION_NAME, $snapshot, $updated );
		if ( null === $result ) {
			return false;
		}
		if ( $result ) {
			return true;
		}
	}
	return false;
}


/**
 * Queue affected IDs and arrange continuation without invalidating committed scan progress.
 *
 * @param array $post_ids Changed IDs.
 * @param bool  $refresh_coverage Whether taxonomy source changes require a fresh coverage read.
 * @return bool Whether durable work and its continuation were verified.
 */
function ai4seo_enqueue_analysis_reconciliation( array $post_ids, bool $refresh_coverage = false ): bool {
	$options = ai4seo_get_analysis_reconciliation_source_options( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA );
	return ai4seo_mutate_analysis_reconciliation( $post_ids, $refresh_coverage, array(), array_fill_keys( $post_ids, $options ) )
		&& ai4seo_schedule_generation_status_summary_rebuild( false );
}


/**
 * Resolve the summary sources belonging to one generation context.
 *
 * @param string $context Generation context.
 * @return array Source option names.
 */
function ai4seo_get_analysis_reconciliation_source_options( string $context ): array {
	return array_values(
		array_filter(
			ai4seo_get_generation_status_summary_source_option_names(),
			static function ( string $option ) use ( $context ): bool {
				return ai4seo_get_bulk_generation_result_transition_context( array( $option ) ) === $context;
			}
		)
	);
}


/**
 * Reconcile a bounded batch after the full scan, retaining work across failures and new writes.
 *
 * @return bool Whether this invocation succeeded; remaining entries are continued by cron.
 */
function ai4seo_process_analysis_reconciliation(): bool {
	global $wpdb;
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_posts_table_analysis_database_lock_name() ) ) {
		return false;
	}
	$snapshot = ai4seo_read_analysis_reconciliation_snapshot( true );
	if ( null === $snapshot ) {
		return false;
	}
	// Each context gets a bounded batch, so a broken media source cannot starve posts.
	$batches = array();
	foreach ( $snapshot['value'] as $post_id => $entry ) {
		$context = ai4seo_get_bulk_generation_result_transition_context( $entry['options'] ?? ai4seo_get_generation_status_summary_source_option_names() );
		if ( count( $batches[ $context ] ?? array() ) < AI4SEO_ANALYSIS_RECONCILIATION_BATCH_SIZE ) {
			$batches[ $context ][ $post_id ] = $entry;
		}
	}
	$succeeded = true;
	foreach ( $batches as $batch ) {
		if ( ! ai4seo_reconcile_analysis_batch( $batch ) ) {
			$succeeded = false;
		}
	}
	return $succeeded;
}


/**
 * Repair one bounded source group while its original tokens remain durable.
 *
 * @param array $batch Previously read reconciliation entries.
 * @return bool Whether the group was repaired and acknowledged.
 */
function ai4seo_reconcile_analysis_batch( array $batch ): bool {
	if ( ai4seo_uses_post_state_tables() ) {
		return ai4seo_reconcile_indexed_analysis_batch( $batch );
	}
	global $wpdb;
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_posts_table_analysis_database_lock_name() ) ) {
		return false;
	}
	$taxonomy_lock  = ai4seo_get_taxonomy_proxy_sync_lock_name();
	$needs_coverage = in_array( true, array_column( $batch, 'coverage' ), true );
	if ( $needs_coverage && ! ai4seo_acquire_database_advisory_lock( $taxonomy_lock ) ) {
		return false;
	}
	$previous_write_scope                           = $GLOBALS['ai4seo_analysis_summary_write_scope'] ?? null;
	$GLOBALS['ai4seo_analysis_summary_write_scope'] = (string) $wpdb->options;
	try {
		$additions = array();
		$removals  = array();
		// Preserve canonical source order, including generated history after missing coverage.
		$changes      = array_fill_keys( ai4seo_get_generation_status_summary_source_option_names(), array() );
		$coverage_ids = array_keys(
			array_filter(
				$batch,
				static function ( array $entry ): bool {
					return $entry['coverage'];
				}
			)
		);
		$rows         = array();
		if ( $coverage_ids ) {
			$query = ai4seo_prepare_database_query(
				'SELECT ID, post_author, post_type, post_status, post_mime_type, post_date_gmt, post_date, post_modified_gmt, post_modified FROM {{posts_table}} WHERE ID IN ({{post_ids}})',
				array(
					'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
					'post_ids'    => ai4seo_database_list_binding( '%d', $coverage_ids ),
				)
			);
			if ( false === $query ) {
				return false;
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Typed bounded IDs; reconciliation requires current persisted rows.
			$rows = $wpdb->get_results( $query, ARRAY_A );
			if ( $wpdb->last_error || ! is_array( $rows ) ) {
				return false;
			}
			$rows = array_column( $rows, null, 'ID' );
		}
		foreach ( $batch as $post_id => $entry ) {
			$options = $entry['options'] ?? ai4seo_get_generation_status_summary_source_option_names();
			foreach ( $options as $option ) {
				$changes[ $option ][] = $post_id;
			}
			if ( ! $entry['coverage'] ) {
				continue;
			}
			foreach ( ai4seo_get_bulk_generation_queue_contexts() as $context ) {
				$coverage_options = array_values( array_intersect( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS, ai4seo_get_analysis_reconciliation_source_options( $context ) ) );
				if ( ! array_intersect( $options, $coverage_options ) ) {
					continue;
				}
				$transition = ai4seo_build_analysis_reconciliation_coverage( (int) $post_id, $rows[ $post_id ] ?? null, $context );
				if ( null === $transition ) {
					return false;
				}
				foreach ( $transition['additions'] as $option_name => $ids ) {
					$additions[ $option_name ] = array_merge( $additions[ $option_name ] ?? array(), $ids );
				}
				foreach ( $transition['removals'] as $option_name => $ids ) {
					$removals[ $option_name ] = array_merge( $removals[ $option_name ] ?? array(), $ids );
				}
				foreach ( array_merge( array_keys( $transition['additions'] ), array_keys( $transition['removals'] ) ) as $option ) {
					$changes[ $option ][] = $post_id;
				}
			}
		}
		if ( ( $additions || $removals ) && ! ai4seo_apply_post_id_option_transition( $additions, $removals ) ) {
			return false;
		}
		$succeeded = false;
		ai4seo_sync_generation_status_summary_for_option_changes( $changes, $succeeded, false );
		if ( ! $succeeded ) {
			// Actual summary corruption still needs an authoritative rebuild; transient source or CAS
			// failures retain the bounded queue without resetting a healthy completed analysis.
			$summary = ai4seo_get_raw_option_snapshot( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME );
			if ( null !== $summary && ( ! $summary['exists']
				|| null === ai4seo_normalize_generation_status_summary_for_incremental_sync_value( $summary['value'] ) ) ) {
				ai4seo_schedule_generation_status_summary_rebuild();
			}
			return false;
		}
		// Entries stay in storage throughout processing, including fatal errors. Exact-token acknowledgement
		// cannot remove another request's newer update of the same ID, even after a CAS retry.
		return ai4seo_mutate_analysis_reconciliation( array(), false, $batch );
	} finally {
		$GLOBALS['ai4seo_analysis_summary_write_scope'] = $previous_write_scope;
		if ( $needs_coverage ) {
			ai4seo_release_database_advisory_lock( $taxonomy_lock );
		}
	}
}


/**
 * Re-read current coverage with the scan's eligibility rules, without changing its checkpoint.
 *
 * @param int        $post_id Queued identity.
 * @param array|null $row Current database row, or null when deleted.
 * @param string     $context Generation context being repaired.
 * @param bool|null  $eligible Receives whether this identity belongs to the filtered context.
 * @param array|null $issues Opt-in attribution of malformed data; SQL failures remain failures.
 * @return array|null Coverage transition, or null when an authoritative read failed.
 */
function ai4seo_build_analysis_reconciliation_coverage( int $post_id, ?array $row, string $context, ?bool &$eligible = null, ?array &$issues = null ): ?array {
	$eligible      = false;
	$is_media      = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context;
	$settings_read = false;
	ai4seo_get_setting( $is_media ? AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES : AI4SEO_SETTING_ACTIVE_META_TAGS, $settings_read );
	if ( ! $settings_read ) {
		return null;
	}
	$missing    = $is_media ? AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME : AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME;
	$covered    = $is_media ? AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME : AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME;
	$generated  = $is_media ? AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME : AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME;
	$transition = array(
		'additions' => array(),
		'removals'  => array_fill_keys( ai4seo_get_analysis_reconciliation_source_options( $context ), array( $post_id ) ),
	);
	if ( null === $row ) {
		return $transition;
	}
	$date_filter = ai4seo_get_current_bulk_generation_date_filter_state();
	$state       = ai4seo_classify_posts_table_analysis_row(
		$row,
		$is_media ? array() : array_merge( ai4seo_get_supported_post_types(), array( AI4SEO_TAXONOMY_PROXY_POST_TYPE ) ),
		$is_media ? ai4seo_get_supported_attachment_post_types() : array(),
		ai4seo_get_allowed_attachment_mime_types(),
		$date_filter
	);
	if ( $state['field'] ) {
		if ( null !== $issues ) {
			ai4seo_add_posts_table_analysis_issue( $issues, $post_id, $state['field'], 'invalid_post_row' );
			$transition['removals'] = array_fill_keys( array( $missing, $covered, $generated ), array( $post_id ) );
			return $transition;
		}
		return null;
	}
	if ( ! $state['supported'] ) {
		return $transition;
	}
	$is_archive = AI4SEO_TAXONOMY_PROXY_POST_TYPE === $row['post_type'];
	if ( $is_archive ) {
		$archive = ai4seo_get_taxonomy_proxy_editor_context( $post_id );
		if ( is_wp_error( $archive ) ) {
			return null;
		}
		if ( ! $archive['available'] ) {
			return $transition;
		}
	}
	// Retain the existing cleanup for deleted/unsupported entries and unavailable archives.
	// Existing eligible identities only need their derived coverage memberships refreshed.
	$transition['removals'] = array_fill_keys( array( $missing, $covered, $generated ), array( $post_id ) );
	$read                   = false;
	if ( null === $issues ) {
		$has_generated = ai4seo_post_has_generated_data( $post_id, $read );
	} else {
		$failure       = null;
		$has_generated = (bool) ai4seo_read_generated_data_post_ids_by_post_ids( array( $post_id ), $read, false, $failure, $issues );
	}
	if ( ! $read ) {
		return null;
	}
	if ( $issues ) {
		return $transition;
	}
	// Generated history remains counted independently of active coverage exclusions.
	if ( $has_generated ) {
		$transition['additions'][ $generated ] = array( $post_id );
		unset( $transition['removals'][ $generated ] );
	}
	if ( ! $is_archive ) {
		$authors   = $is_media ? ai4seo_get_disabled_attachment_post_author_ids() : ai4seo_get_disabled_post_author_ids();
		$languages = $is_media ? ai4seo_get_disabled_attachment_attributes_wpml_language_codes() : ai4seo_get_disabled_metadata_wpml_language_codes();
		if ( ! $state['eligible'] || in_array( (int) $row['post_author'], $authors, true )
			|| ! ai4seo_does_bulk_generation_date_filter_include_timestamp( $date_filter, $state['timestamp'] )
			|| ( $languages && in_array( ai4seo_get_cached_post_language_code_by_multilanguage_plugins( $post_id ), $languages, true ) ) ) {
			return $transition;
		}
		$terms = $is_media ? array() : ai4seo_get_enforced_disabled_taxonomy_terms();
		if ( $terms ) {
			$excluded = ai4seo_get_post_ids_excluded_by_disabled_taxonomy_terms( array( $post_id ), $terms, null, $read );
			if ( ! $read ) {
				return null;
			}
			if ( $excluded ) {
				return $transition;
			}
		}
	}
	$eligible = true;
	if ( null === $issues ) {
		$full = $is_media ? ai4seo_are_attachment_attributes_fully_covered( $post_id, $read ) : ai4seo_read_is_posts_metadata_fully_covered( $post_id, $read );
	} elseif ( $is_media ) {
		$coverage = ai4seo_read_and_analyse_attachment_attributes_coverage( array( $post_id ), $read, $issues );
		$counts   = ai4seo_get_attachment_attributes_coverage_summary( $coverage );
		$full     = ( $counts[ $post_id ] ?? 0 ) >= ai4seo_get_active_num_attachment_attributes();
	} else {
		$fields   = null;
		$coverage = ai4seo_read_percentage_of_available_metadata_by_post_ids( array( $post_id ), 0, $read, $fields, $issues );
		$full     = ai4seo_is_full_coverage_percentage( $coverage[ $post_id ] ?? 0 );
	}
	if ( ! $read ) {
		return null;
	}
	if ( $issues ) {
		$eligible = false;
		return array(
			'additions' => array(),
			'removals'  => array_fill_keys( array( $missing, $covered, $generated ), array( $post_id ) ),
		);
	}
	$generate_covered = $is_media ? ai4seo_do_generate_attachment_attributes_for_fully_covered_entries() : ai4seo_do_generate_metadata_for_fully_covered_entries();
	$full             = $full && ( ! $generate_covered || $has_generated );
	if ( ! $full ) {
		$options = ai4seo_get_bulk_generation_queue_options_by_context( $context );
		foreach ( array( 'hidden', 'auto_queue_disallowed' ) as $key ) {
			$inspection = ai4seo_inspect_post_state_candidates( $options[ $key ], array( $post_id ) );
			if ( ! $inspection['read_succeeded'] || ! $inspection['complete'] ) {
				return null;
			}
			if ( in_array( $post_id, $inspection['ids'], true ) ) {
				return $transition;
			}
		}
	}
	$destination                             = $full ? $covered : $missing;
	$transition['additions'][ $destination ] = array( $post_id );
	unset( $transition['removals'][ $destination ] );
	return $transition;
}


/**
 * Schedule a one-off continuation for a full analysis or queued ID reconciliation.
 *
 * @param bool $require_restart Whether the next rebuild must reset before resuming analysis.
 * @return bool Whether work is already idle, durably deferred while paused, or scheduled.
 */
function ai4seo_schedule_generation_status_summary_rebuild( bool $require_restart = true ): bool {
	$rebuild_state = ai4seo_read_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
		false
	);

	if ( $require_restart ) {
		if ( ! ai4seo_request_analysis_refresh( 'repair', true ) ) {
			return false;
		}

		$rebuild_state = 'required';
	}

	if ( 'idle' === $rebuild_state && ! ai4seo_has_pending_analysis_reconciliation() && ! ai4seo_has_background_analysis()
		&& empty( ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH, false )['pending_reason'] ) ) {
		return true;
	}

	// The durable marker is the deferred contract while administrators intentionally pause heavy work.
	if ( ! ai4seo_is_posts_table_analysis_possible() ) {
		return true;
	}

	$wake_at = ai4seo_get_analysis_refresh_wake_time();
	if ( null === $wake_at ) {
		return false;
	}
	$next_scheduled = wp_next_scheduled( AI4SEO_GENERATION_STATUS_SUMMARY_REBUILD_CRON_JOB_NAME );
	// A new post edit must bring a distant full-scan event forward.
	if ( false !== $next_scheduled && $next_scheduled > $wake_at + 5 ) {
		if ( ! wp_unschedule_event( $next_scheduled, AI4SEO_GENERATION_STATUS_SUMMARY_REBUILD_CRON_JOB_NAME ) ) {
			return false;
		}
		$next_scheduled = false;
	}

	if ( false === $next_scheduled ) {
		// Yielded batches share the worker throttle; failed attempts back off before the next automatic retry.
		$progress = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS, false );
		$last_run = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME, false );
		$interval = empty( $progress['failure'] ) ? AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME * 2 : AI4SEO_POST_TABLE_ANALYSIS_PROCESSING_TIMEOUT;
		$delay    = 'recent' === ai4seo_classify_background_timestamp( $last_run, $interval ) ? max( 1, $interval - ( time() - $last_run ) ) : 1;

		$schedule_result = wp_schedule_single_event(
			max( time() + $delay, $wake_at ),
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
			if ( in_array( $current_state, array( 'required', 'resume_then_rebuild' ), true ) ) {
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
 * Resume a full summary rebuild or reconcile queued IDs through the existing analysis worker.
 *
 * @return bool True when the rebuild completed or its next bounded continuation was scheduled.
 */
function ai4seo_rebuild_generation_status_summary(): bool {
	$rebuild_state = ai4seo_read_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
		false
	);

	if ( 'idle' === $rebuild_state && ! ai4seo_has_pending_analysis_reconciliation() && ! ai4seo_has_background_analysis()
		&& empty( ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ANALYSIS_REFRESH, false )['pending_reason'] ) ) {
		return true;
	}

	// Leave the durable request untouched; a later ordinary request will schedule it after re-enabling work.
	if ( ! ai4seo_is_posts_table_analysis_possible() ) {
		return false;
	}

	// Cron may continue healthy batches promptly, but recorded failures retain the same retry backoff.
	$progress           = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS, false );
	$analysis_succeeded = ai4seo_try_start_posts_table_analysis( false, false, wp_doing_cron() && empty( $progress['failure'] ) );

	if ( ! $analysis_succeeded ) {
		// An unavailable lock returns before the worker finalizer; retain a cron continuation in that case too.
		ai4seo_schedule_generation_status_summary_rebuild( false );
	}

	return $analysis_succeeded;
}


/**
 * Synchronizes summary buckets for source-option memberships changed during one request.
 *
 * @param array $changed_post_ids_by_option Changed post IDs keyed by their source option name.
 * @param bool  $sync_succeeded Receives whether the final summary state was verified.
 * @param bool  $schedule_rebuild_on_failure Whether a caller without durable retry work needs a full rebuild.
 * @return bool Whether the stored summary changed.
 */
function ai4seo_sync_generation_status_summary_for_option_changes( array $changed_post_ids_by_option, &$sync_succeeded = null, bool $schedule_rebuild_on_failure = true ): bool {
	if ( ai4seo_uses_post_state_tables() ) {
		$ids = array();
		foreach ( $changed_post_ids_by_option as $members ) {
			foreach ( $members as $key => $value ) {
				$ids[] = true === $value ? (int) $key : (int) $value;
			}
		}
		$sync_succeeded = ai4seo_mutate_analysis_reconciliation( array_values( array_unique( $ids ) ), true );
		return $sync_succeeded;
	}
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
		$post_types_read       = false;
		$post_types_by_post_id = ai4seo_read_post_types_by_post_ids( array_keys( $all_changed_post_id_lookup ), $post_types_read );
		if ( ! $post_types_read ) {
			return false;
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
					$inspection = ai4seo_inspect_analysis_post_id_option( $option_name );
					if ( ! $inspection['read_succeeded'] || ! $inspection['complete'] ) {
						return array();
					}
					$live_post_id_lookup = array_fill_keys( $inspection['ids'], true );
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
					'changed' => $generation_status_summary !== $original_generation_status_summary,
				);
			},
			true,
			$summary_did_change
		);

		if ( ! $summary_was_stored ) {
			if ( $schedule_rebuild_on_failure ) {
				ai4seo_schedule_generation_status_summary_rebuild();
			}
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
 * @param array      $post_ids List of post IDs to check.
 * @param bool|null  $read_succeeded Receives whether every authoritative read succeeded.
 * @param bool       $debug Whether diagnostic messages should be emitted for rejected storage.
 * @param array|null $failure Receives the diagnostic code and post ID for rejected generated data.
 * @param array|null $isolated_rows Pass an empty array to isolate corrupt or duplicate history once per post; null retains fail-closed reads. Published only after the entire batch succeeds.
 * @return array Sanitized list of post IDs with generated data.
 */
function ai4seo_read_generated_data_post_ids_by_post_ids( array $post_ids, ?bool &$read_succeeded = null, bool $debug = false, ?array &$failure = null, ?array &$isolated_rows = null ): array {
	global $wpdb;

	$isolation_enabled = null !== $isolated_rows;
	$isolated_batch    = array();
	if ( $isolation_enabled ) {
		$isolated_rows = array();
	}
	$read_succeeded = false;
	$failure        = array();

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
		$this_query_bindings = array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The key lookup is paired with a bounded post-ID chunk for an indexed postmeta batch read.
			'meta_key'       => ai4seo_database_scalar_binding( '%s', AI4SEO_POST_META_GENERATED_DATA_META_KEY ),
			'post_ids'       => ai4seo_database_list_binding( '%d', array_values( $this_post_ids_chunk ) ),
			'query_limit'    => ai4seo_database_scalar_binding( '%d', count( $this_post_ids_chunk ) + 1 ),
		);
		if ( $isolation_enabled ) {
			// Group before reading values: duplicate histories cannot consume the row limit, hide
			// healthy posts, or trigger cleanup. One statement observes ownership and content together.
			$this_query_template                         = 'SELECT generated_owners.meta_id, generated_owners.post_id, generated_value.meta_value, generated_owners.owner_count
				FROM (
					SELECT MIN(meta_id) AS meta_id, post_id, COUNT(*) AS owner_count
					FROM {{postmeta_table}} WHERE meta_key = {{meta_key}} AND post_id IN ({{post_ids}})
					GROUP BY post_id
				) AS generated_owners
				LEFT JOIN {{value_postmeta_table}} AS generated_value
					ON generated_owners.owner_count = 1 AND generated_value.meta_id = generated_owners.meta_id
				ORDER BY generated_owners.meta_id ASC LIMIT {{query_limit}}';
			$this_query_bindings['value_postmeta_table'] = ai4seo_database_identifier_binding( 'table.postmeta' );
		}
		$this_query = ai4seo_prepare_database_query( $this_query_template, $this_query_bindings );

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
				|| ( ! is_string( $this_generated_data_row['meta_value'] )
					&& ( ! $isolation_enabled || null !== $this_generated_data_row['meta_value'] ) ) ) {
				if ( $debug ) {
					ai4seo_debug_message( 837261945, esc_html( __FUNCTION__ ) . ' > Generated-data query returned a malformed row.' );
				}

				return array();
			}

			$this_generated_data_meta_id     = ai4seo_normalize_database_id( $this_generated_data_row['meta_id'] );
			$this_generated_data_post_id     = ai4seo_normalize_database_id( $this_generated_data_row['post_id'] );
			$this_generated_data_owner_count = $isolation_enabled
				? ai4seo_normalize_database_id( $this_generated_data_row['owner_count'] ?? null )
				: 1;

			if ( false === $this_generated_data_meta_id
				|| false === $this_generated_data_post_id
				|| false === $this_generated_data_owner_count
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
				// Surface the exact conflicting record through the scanner's persisted diagnostic.
				$failure = array(
					'code'    => 408572136,
					'post_id' => $this_generated_data_post_id,
				);

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
			if ( $this_generated_data_owner_count > 1 ) {
				// The representative meta ID is diagnostic only; never choose, merge, or repair an owner.
				$isolated_batch[] = array(
					'post_id' => $this_generated_data_post_id,
					'meta_id' => $this_generated_data_meta_id,
					'reason'  => 'duplicate_owners',
					'field'   => '',
				);
				if ( $debug ) {
					ai4seo_debug_message( 408572136, 'Generated-data post isolated. Post ID: ' . $this_generated_data_post_id . '; meta ID: ' . $this_generated_data_meta_id . '; reason: duplicate_owners; row count: ' . $this_generated_data_owner_count );
				}
				continue;
			}
			$this_generated_data_details         = array();
			$this_generated_data_repair_required = false;
			$this_generated_data_diagnostics     = array();

			// Validate supported content before publication; unrelated fields do not contribute to coverage.
			if ( ! ai4seo_decode_generated_data_postmeta_value_authoritatively(
				$this_generated_data_row['meta_value'],
				$this_generated_data_details,
				$this_generated_data_repair_required,
				true,
				$this_generated_data_diagnostics
			) ) {
				if ( $isolation_enabled ) {
					$isolated_batch[] = array(
						'post_id' => $this_generated_data_post_id,
						'meta_id' => $this_generated_data_meta_id,
						'reason'  => $this_generated_data_diagnostics['reason'],
						'field'   => $this_generated_data_diagnostics['field'],
					);
					if ( $debug ) {
						ai4seo_debug_message( 746205318, 'Generated-data row isolated. Post ID: ' . $this_generated_data_post_id . '; meta ID: ' . $this_generated_data_meta_id . '; reason: ' . $this_generated_data_diagnostics['reason'] . '; field: ' . $this_generated_data_diagnostics['field'] );
					}
					continue;
				}
				// Keep malformed relevant data identifiable without changing the existing repair policy.
				$failure = array(
					'code'    => 746205318,
					'post_id' => $this_generated_data_post_id,
				);

				if ( $debug ) {
					ai4seo_debug_message(
						746205318,
						esc_html( __FUNCTION__ ) . ' > Generated-data value failed authoritative decoding. Post ID: ' . esc_html( $this_generated_data_post_id ) . '; meta ID: ' . esc_html( $this_generated_data_meta_id )
							. '; reason: ' . esc_html( $this_generated_data_diagnostics['reason'] ) . '; field: ' . esc_html( $this_generated_data_diagnostics['field'] )
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

	if ( $isolation_enabled ) {
		$isolated_rows = $isolated_batch;
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
		if ( ai4seo_uses_post_state_tables() ) {
			$reset_succeeded = ai4seo_reset_indexed_analysis_generation();
			return $reset_succeeded;
		}

		// The checked shared-fence clear verifies every coverage source before state or summary publication.
		global $wpdb;
		$previous_write_scope                           = $GLOBALS['ai4seo_analysis_summary_write_scope'] ?? null;
		$GLOBALS['ai4seo_analysis_summary_write_scope'] = (string) $wpdb->options;
		try {
			$coverage_cleared = ai4seo_clear_post_id_options( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS );
		} finally {
			$GLOBALS['ai4seo_analysis_summary_write_scope'] = $previous_write_scope;
		}
		if ( $coverage_cleared ) {
			// Commit cursor and lifecycle state together so readers cannot observe a half-reset analysis.
			$progress_transition_result = ai4seo_bulk_update_environmental_variables(
				array(
					AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID => 0,
					AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE        => 'idle',
					AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS     => array(),
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

				// Identify the reset phase separately from the coordinator's overall reset diagnostic.
				if ( ! $reset_succeeded ) {
					ai4seo_debug_message( 890174623, 'Analysis reset failed while publishing the empty generation-status summary pair.', true );
				}
			} else {
				ai4seo_debug_message( 890174622, 'Analysis reset failed while clearing the cursor, lifecycle state, and progress.', true );
			}
		} else {
			ai4seo_debug_message( 890174621, 'Analysis reset failed while clearing or verifying coverage source options.', true );
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
 * @param bool   $debug if true, debug information will be printed.
 * @param bool   $force if true, bypasses the short analysis run throttle.
 * @param bool   $allow_trusted_admin_mutation if true, trusted admin mutations may start the analysis.
 * @param bool   $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @param string $start_reason Explicit trigger; only manual actions bypass the quiet period.
 * @return bool True when the replacement analysis was safely claimed and run.
 */
function ai4seo_force_posts_table_analysis_refresh(
	bool $debug = false,
	bool $force = false,
	bool $allow_trusted_admin_mutation = false,
	bool $allow_debug_heavy_db_operations = false,
	string $start_reason = 'manual'
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
		'manual' === $start_reason,
		$start_reason
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
	return ai4seo_request_analysis_refresh( 'settings', true )
		&& ai4seo_force_posts_table_analysis_refresh( $debug, true, true, false, 'settings' );
}



// endregion
// ___________________________________________________________________________________________.


/**
 * Describe safely attributable analysis failures without retaining customer values.
 *
 * @param array $issues Existing row issues.
 * @param array $additional Additional row issues.
 * @return array One diagnostic per physical post, in discovery order.
 */
function ai4seo_merge_posts_table_analysis_issues( array $issues, array $additional ): array {
	$by_post = array();
	foreach ( array_merge( $issues, $additional ) as $issue ) {
		if ( ! isset( $by_post[ $issue['post_id'] ] ) ) {
			$by_post[ $issue['post_id'] ] = $issue;
		}
	}
	return array_values( $by_post );
}

/**
 * Read operational membership for analysis without repairing its stored bytes.
 *
 * @param string     $option_name Known post-ID option.
 * @param array|null $snapshot Optional already-read authoritative snapshot.
 * @return array IDs, completeness and transport success are independent.
 */
function ai4seo_inspect_analysis_post_id_option( string $option_name, ?array $snapshot = null ): array {
	if ( null === $snapshot && isset( AI4SEO_POST_STATE_BUCKETS[ $option_name ] ) && ai4seo_uses_post_state_tables() ) {
		$ids = ai4seo_export_post_state_ids( $option_name );
		return array(
			'ids'            => $ids ?? array(),
			'read_succeeded' => null !== $ids,
			'complete'       => null !== $ids,
			'issues'         => null === $ids ? array( 'unreliable_read' ) : array(),
		);
	}
	$result = array(
		'ids'            => array(),
		'complete'       => false,
		'read_succeeded' => false,
		'issues'         => array(),
	);
	if ( ! in_array( $option_name, AI4SEO_ALL_POST_ID_OPTIONS, true ) ) {
		$result['issues'][] = 'unsupported_option';
		return $result;
	}
	$snapshot = $snapshot ?? ai4seo_get_raw_option_snapshot( $option_name );
	if ( null === $snapshot || ! ai4seo_is_valid_raw_option_snapshot( $option_name, $snapshot ) ) {
		$result['issues'][] = 'unreliable_read';
		return $result;
	}
	$result['read_succeeded'] = true;
	$value                    = $snapshot['exists'] ? ai4seo_safe_maybe_unserialize( $snapshot['value'] ) : array();

	// The historic empty queue representation is safe to interpret, but never rewritten here.
	$empty_queue_options = array(
		AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
		AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);
	if ( '' === $value && in_array( $option_name, $empty_queue_options, true ) ) {
		$value = array();
	}
	if ( is_string( $value ) ) {
		$value = json_decode( $value, true );
	}
	if ( ! is_array( $value ) ) {
		$result['issues'][] = 'invalid_collection';
		return $result;
	}
	$result['complete'] = true;
	foreach ( $value as $id ) {
		$id = ai4seo_normalize_option_post_id( $id );
		if ( false === $id ) {
			$result['complete'] = false;
			$result['issues']   = array( 'invalid_post_id' );
			continue;
		}
		$result['ids'][ $id ] = $id;
	}
	$result['ids'] = array_values( $result['ids'] );
	return $result;
}

/**
 * Classify only fields actually consumed by the active analysis filters.
 *
 * @param array $row Identified physical post row.
 * @param array $post_types Supported metadata types.
 * @param array $attachment_types Supported media types.
 * @param array $mime_types Allowed media types.
 * @param array $date_filter Current date-filter state.
 * @return array Supported/eligible flags, selected timestamp and invalid field.
 */
function ai4seo_classify_posts_table_analysis_row( array $row, array $post_types, array $attachment_types, array $mime_types, array $date_filter ): array {
	$result = array(
		'supported' => false,
		'eligible'  => false,
		'timestamp' => 0,
		'field'     => '',
	);
	$type   = $row['post_type'] ?? null;
	if ( AI4SEO_TAXONOMY_PROXY_POST_TYPE === $type && in_array( $type, $post_types, true ) ) {
		$result['supported'] = true;
		$result['eligible']  = 'publish' === ( $row['post_status'] ?? '' );
		return $result;
	}
	if ( ! is_string( $type ) || '' === $type || strlen( $type ) > 20 || sanitize_key( $type ) !== $type ) {
		$result['field'] = 'post_type';
		return $result;
	}
	$is_metadata         = in_array( $type, $post_types, true );
	$result['supported'] = $is_metadata || in_array( $type, $attachment_types, true );
	if ( ! $result['supported'] ) {
		return $result;
	}
	if ( ! isset( $row['post_status'] )
		|| ! is_string( $row['post_status'] )
		|| '' === $row['post_status']
		|| strlen( $row['post_status'] ) > 20
		|| sanitize_key( $row['post_status'] ) !== $row['post_status'] ) {
		$result['field'] = 'post_status';
		return $result;
	}
	if ( ! in_array( $row['post_status'], $is_metadata ? array( 'publish', 'future' ) : array( 'publish', 'future', 'inherit' ), true ) ) {
		return $result;
	}
	if ( ! $is_metadata ) {
		if ( ! isset( $row['post_mime_type'] ) || ! is_string( $row['post_mime_type'] ) ) {
			$result['field'] = 'post_mime_type';
			return $result;
		}
		if ( ! in_array( $row['post_mime_type'], $mime_types, true ) ) {
			return $result;
		}
	}
	$excluded_authors = $is_metadata ? ai4seo_get_disabled_post_author_ids() : ai4seo_get_disabled_attachment_post_author_ids();
	if ( $excluded_authors && false === ai4seo_normalize_option_post_id( $row['post_author'] ?? null, true ) ) {
		$result['field'] = 'post_author';
		return $result;
	}
	if ( 'both' !== ( $date_filter['filter'] ?? '' ) ) {
		foreach ( array( 'post_date_gmt', 'post_date', 'post_modified_gmt', 'post_modified' ) as $field ) {
			$value = $row[ $field ] ?? null;
			// Match the existing truthy fallback selection before validating its selected source.
			if ( ! $value ) {
				continue;
			}
			if ( ! is_string( $value ) || ( '0000-00-00 00:00:00' !== $value && ! ai4seo_is_valid_mysql_datetime( $value ) ) ) {
				$result['field'] = $field;
				return $result;
			}
			$result['timestamp'] = (int) strtotime( $value );
			break;
		}
	}
	$result['eligible'] = true;
	return $result;
}

/**
 * Separate a completed scan from trustworthy automatic queue admission.
 *
 * @return array Completion, per-context readiness and unreliable option names.
 */
function ai4seo_get_posts_table_analysis_readiness(): array {
	// Admission and recovery must not mistake malformed persisted state for declared defaults.
	$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
	$readable = null !== $snapshot && ( ! $snapshot['exists'] || is_array( $snapshot['value'] ) );
	$values   = $readable && $snapshot['exists'] ? $snapshot['value'] : array();
	foreach ( array(
		AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE,
		AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS,
		AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE,
	) as $key ) {
		if ( array_key_exists( $key, $values ) && ! ai4seo_validate_environmental_variable_value( $key, $values[ $key ] ) ) {
			$readable = false;
		}
	}
	$values        = $readable ? $values : array();
	$progress      = $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ] ?? array();
	$complete      = $readable && 2 === (int) ( $progress['version'] ?? 0 )
		&& 'completed' === ( $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ] ?? '' )
		&& 'idle' === ( $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] ?? 'idle' )
		&& empty( $progress['failure'] );
	$storage_state = $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_STORAGE ] ?? array();
	if ( ! ai4seo_validate_post_state_storage_status( $storage_state ) ) {
		$readable = false;
		$complete = false;
	}
	if ( $readable && isset( $storage_state['building_generation'], $storage_state['published_progress'] ) ) {
		$progress = $storage_state['published_progress'];
		$complete = 'active' === $storage_state['phase'] && empty( $storage_state['live_invalidated'] );
	}
	if ( ! $readable ) {
		$pending         = null;
		$pending_options = array();
	} elseif ( ai4seo_uses_post_state_tables() ) {
		// Table-backed admission fences pending IDs individually. A repair marker is not
		// an unreadable context: unrelated verified posts may still enter the queue.
		$rows = ai4seo_post_state_query( 'SELECT 1 FROM {{work}} WHERE kind=1 LIMIT 1', array(), true );
		// Preserve the aggregate catch-up flag for coverage filters; only admission is per-ID.
		$pending_options = array();
		$pending         = null === $rows ? null : array( 'value' => $rows );
		$readable        = null !== $rows;
	} else {
		$pending         = ai4seo_read_analysis_reconciliation_snapshot();
		$pending_options = array();
		foreach ( $pending['value'] ?? array() as $entry ) {
			foreach ( $entry['options'] ?? ai4seo_get_generation_status_summary_source_option_names() as $option ) {
				$pending_options[ $option ] = true;
			}
		}
	}
	$result = array(
		'read_succeeded'  => $readable,
		'complete'        => $complete && null !== $pending && ! $pending['value'],
		'contexts'        => array(),
		'blocked_options' => $progress['blocked_options'] ?? array(),
	);
	foreach ( ai4seo_get_bulk_generation_queue_contexts() as $context ) {
		$options                        = ai4seo_get_bulk_generation_queue_options_by_context( $context );
		$result['contexts'][ $context ] = $complete && null !== $pending
			&& ! array_intersect( ai4seo_get_analysis_reconciliation_source_options( $context ), array_keys( $pending_options ) )
			&& ! array_intersect( array_values( $options ), $result['blocked_options'] );
	}
	return $result;
}

/**
 * Validate live membership sources while the automatic admission fence is owned.
 *
 * @param string $context Queue context.
 * @return bool Whether admission can use complete, current membership information.
 */
function ai4seo_validate_analysis_auto_queue_admission_under_lock( string $context ): bool {
	if ( ! ai4seo_is_plugin_update_context_ready( $context, true ) ) {
		return false;
	}
	if ( ! ai4seo_renew_post_id_option_transition_semaphore() ) {
		return false;
	}
	$readiness = ai4seo_get_posts_table_analysis_readiness();
	if ( empty( $readiness['contexts'][ $context ] ) ) {
		return false;
	}
	$options = ai4seo_get_bulk_generation_queue_options_by_context( $context );
	foreach ( array( 'pending', 'processing', 'failed', 'hidden', 'auto_queue_disallowed', 'force_overwrite', 'missing' ) as $key ) {
		$inspection = ai4seo_inspect_post_state_candidates( $options[ $key ], array() );
		if ( ! $inspection['read_succeeded'] || ! $inspection['complete'] ) {
			ai4seo_schedule_generation_status_summary_rebuild();
			return false;
		}
	}
	return ai4seo_renew_post_id_option_transition_semaphore();
}


/**
 * Append an attributed read problem only for callers opting into analysis isolation.
 *
 * @param array|null $issues Analysis issues, or null for a strict read.
 * @param int        $post_id Authoritatively identified post.
 * @param string     $field Affected logical field, or empty for all fields.
 * @param string     $reason Non-secret diagnostic reason.
 * @return void
 */
function ai4seo_add_posts_table_analysis_issue( ?array &$issues, int $post_id, string $field, string $reason = 'unreadable_metadata' ): void {
	if ( null !== $issues ) {
		$issues[] = array(
			'post_id' => $post_id,
			'meta_id' => 0,
			'reason'  => $reason,
			'field'   => $field,
		);
	}
}

/**
 * Determine whether an analysis field is unknown, preserving higher-priority failures.
 *
 * @param array|null $issues Attributed read problems.
 * @param int        $post_id Requested post.
 * @param string     $field Logical metadata field.
 * @return bool Whether a relevant value is unknown.
 */
function ai4seo_is_posts_table_analysis_field_unknown( ?array $issues, int $post_id, string $field ): bool {
	foreach ( $issues ?? array() as $issue ) {
		if ( $post_id === $issue['post_id'] && ( '' === $issue['field'] || $field === $issue['field'] ) ) {
			return true;
		}
	}
	return false;
}
