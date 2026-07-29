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

// =========================================================================================== \\

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

// =========================================================================================== \\

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
	ai4seo_try_start_posts_table_analysis( true, $debug, $force, $allow_trusted_admin_mutation, $allow_debug_heavy_db_operations );

	// Record the analysis attempt after queuing so automatic runs can respect the throttle.
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME, time() );

	ai4seo_set_cron_job_status( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, 'finished' );
	return true;
}

/**
 * Tries to start the posts table analysis.
 *
 * @param bool $restart_if_completed if true, the analysis will be restarted even if it was already completed.
 * @param bool $debug if true, debug information will be printed.
 * @param bool $force if true, bypasses the short analysis run throttle.
 * @param bool $allow_trusted_admin_mutation if true, trusted admin mutations may start the analysis.
 * @param bool $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @return void
 */
function ai4seo_try_start_posts_table_analysis(
	bool $restart_if_completed = false,
	bool $debug = false,
	bool $force = false,
	bool $allow_trusted_admin_mutation = false,
	bool $allow_debug_heavy_db_operations = false
) {
	// Do not read or mutate analysis state unless this request is allowed to start analysis work.
	if ( ! ai4seo_can_start_posts_table_analysis( $allow_trusted_admin_mutation ) ) {
		return;
	}

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 197197425, 'Prevented loop', true );
		return;
	}

	ai4seo_run_with_ignore_user_abort(
		'ai4seo_run_posts_table_analysis_task',
		array( $restart_if_completed, $debug, $force, $allow_debug_heavy_db_operations )
	);
}

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Runs one posts table analysis task.
 *
 * @param bool $restart_if_completed if true, the analysis will be restarted even if it was already completed.
 * @param bool $debug if true, debug information will be printed.
 * @param bool $force if true, bypasses the short analysis run throttle.
 * @param bool $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @return void
 */
function ai4seo_run_posts_table_analysis_task(
	bool $restart_if_completed = false,
	bool $debug = false,
	bool $force = false,
	bool $allow_debug_heavy_db_operations = false
) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 423645868, 'Prevented loop', true );
		return;
	}

	if ( ! ai4seo_is_posts_table_analysis_possible( $debug, $allow_debug_heavy_db_operations ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 101605330, esc_html( __FUNCTION__ ) . ' > Heavy database operations disabled.' );
		}

		// set state to completed to avoid further attempts.
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, 'completed', true );
		return;
	}

	// Check the persisted task state before starting or waiting for another analysis process.
	try {
		$posts_table_analysis_state      = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, false );
		$posts_table_analysis_start_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME, false );
		$do_restart                      = false;
		$processing_timeout              = AI4SEO_POST_TABLE_ANALYSIS_PROCESSING_TIMEOUT; // XX seconds.
		$usleep_between_runs             = AI4SEO_POST_TABLE_ANALYSIS_SLEEP_BETWEEN_RUNS; // 0.X seconds
		$total_max_run_time              = AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME; // X seconds.

		// double it when running in ajax.
		if ( wp_doing_ajax() ) {
			$total_max_run_time *= 4;
		}

		// for cron runs -> longer run time and sleep time.
		if ( wp_doing_cron() ) {
			$total_max_run_time  *= 5;
			$usleep_between_runs *= 5;
		}

		$max_runs_per_task = $total_max_run_time / ( $usleep_between_runs / 1000000 ); // calculate max runs per task based on sleep time.

		// first, check if the state is "in-progress" and if the last start time was longer ago than $timeout -> restart.
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

				// still in progress -> return.
				return;
			}
		}

		// check if we are competed and $restart_if_completed is true -> restart.
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

				// already completed, and we don't want to restart -> return.
				return;
			}
		}

		// to avoid multiple runs in a short time, we check when the last run was and if it's less than XX seconds ago, we skip.
		$run_interval_in_seconds = AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME * 2;
		$last_core_run_time      = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME, false );

		if ( $last_core_run_time && ( time() - $last_core_run_time ) < $run_interval_in_seconds && ! $debug && ! $force ) {
			return;
		}

		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME, time(), false );

		// if we decided to restart -> do it.
		if ( $do_restart ) {
			ai4seo_reset_posts_table_analysis();
		}

		// set start.
		$start_time = time();
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, 'processing', false );
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME, $start_time, false );
	} finally {
		// ai4seo_release_semaphore(__FUNCTION__);.
	}

	// call ai4seo_perform_posts_table_analysis() for max $total_max_run_time seconds.
	$previous_posts_table_analysis_last_post_id = -1;
	$run_counter                                = 0;
	$is_finished                                = false;

	try {
		while ( time() - $start_time < $total_max_run_time && $run_counter < $max_runs_per_task ) {
			++$run_counter;

			$posts_table_analysis_last_post_id = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID, false );

			if ( $debug ) {
				ai4seo_debug_message( 231305633, esc_html( __FUNCTION__ ) . ' > Run #' . esc_html( $run_counter ) . ' - Last analyzed post ID: ' . esc_html( $posts_table_analysis_last_post_id ) );
			}

			// prevent infinite loop when the offset is not updated.
			if ( $posts_table_analysis_last_post_id === $previous_posts_table_analysis_last_post_id ) {
				if ( $debug ) {
					ai4seo_debug_message( 107971319, esc_html( __FUNCTION__ ) . ' > Posts table analysis last post id not updated -> stopping to prevent infinite loop' );
				}
				break;
			}

			$is_finished = ai4seo_perform_posts_table_analysis( $posts_table_analysis_last_post_id, $debug );

			if ( $is_finished ) {
				break;
			}

			$previous_posts_table_analysis_last_post_id = $posts_table_analysis_last_post_id;

			usleep( $usleep_between_runs );
		}
	} catch ( Throwable $e ) {
		ai4seo_debug_message( 842653579, $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), true );
	} finally {
		// update state.
		if ( $is_finished ) {
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, 'completed', false );

			if ( $debug ) {
				ai4seo_debug_message( 174773382, esc_html( __FUNCTION__ ) . ' > Posts table analysis completed' );
			}
		} else {
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, 'idle', false );

			if ( $debug ) {
				ai4seo_debug_message( 679211510, esc_html( __FUNCTION__ ) . ' > Posts table analysis paused, not yet completed' );
			}
		}
	}

	if ( $debug ) {
		ai4seo_debug_message( 472361408, esc_html( __FUNCTION__ ) . ' > Current state: ' . esc_html( ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, false ) ) );
	}
}

// =========================================================================================== \\

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
	$raw_posts = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_author, post_type, post_status, post_mime_type, post_date_gmt, post_date, post_modified_gmt, post_modified
        FROM {$wpdb->posts}
        WHERE ID > %d
        ORDER BY ID ASC
        LIMIT %d",
			$posts_table_analysis_last_post_id,
			$total_rows_per_run
		),
		ARRAY_A
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321680, 'Database error: ' . $wpdb->last_error, true );
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
	$raw_post_ids = array();

	foreach ( $raw_posts as $raw_post ) {
		$raw_post_ids[] = $raw_post['ID'];
	}

	// read generated data post ids.
	$generated_data_all_post_ids        = ai4seo_read_generated_data_post_ids_by_post_ids( $raw_post_ids );
	$generated_data_post_ids            = array();
	$generated_data_attachment_post_ids = array();

	// check if we should only generate data for new or existing posts.
	$bulk_generation_new_or_existing_filter                     = ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER );
	$bulk_generation_new_or_existing_filter_reference_timestamp = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME );

	// PRE-FILTER POSTS & SEPARATE ATTACHMENTS.

	$supported_post_types                = ai4seo_get_supported_post_types();
	$supported_attachment_post_types     = ai4seo_get_supported_attachment_post_types();
	$disabled_post_author_ids            = ai4seo_get_disabled_post_author_ids();
	$disabled_post_author_ids            = array_flip( $disabled_post_author_ids );
	$disabled_attachment_post_author_ids = ai4seo_get_disabled_attachment_post_author_ids();
	$disabled_attachment_post_author_ids = array_flip( $disabled_attachment_post_author_ids );
	$disabled_taxonomy_terms             = ai4seo_get_disabled_taxonomy_terms();

	// Analysis has to use the same WPML language scope as dashboards and queue excavation.
	$disabled_metadata_wpml_language_codes                    = ai4seo_get_disabled_metadata_wpml_language_codes();
	$disabled_metadata_wpml_language_code_lookup              = array_flip( $disabled_metadata_wpml_language_codes );
	$disabled_attachment_attributes_wpml_language_codes       = ai4seo_get_disabled_attachment_attributes_wpml_language_codes();
	$disabled_attachment_attributes_wpml_language_code_lookup = array_flip( $disabled_attachment_attributes_wpml_language_codes );
	$posts                                 = array();
	$attachment_posts                      = array();
	$post_ids_with_disabled_taxonomy_terms = array();

	if ( $disabled_taxonomy_terms ) {
		$post_ids_with_disabled_taxonomy_terms = ai4seo_get_post_ids_excluded_by_disabled_taxonomy_terms(
			$raw_post_ids,
			$disabled_taxonomy_terms
		);

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

			// check new and existing filter.
			if ( 'both' !== $bulk_generation_new_or_existing_filter && $bulk_generation_new_or_existing_filter_reference_timestamp && is_numeric( $bulk_generation_new_or_existing_filter_reference_timestamp ) ) {
				$posted_date           = $this_raw_post['post_date_gmt'] ?: $this_raw_post['post_date'] ?: $this_raw_post['post_modified_gmt'] ?: $this_raw_post['post_modified'];
				$posted_date_timestamp = @strtotime( $posted_date );

				if ( $posted_date && $posted_date_timestamp ) {
					if ( 'new' === $bulk_generation_new_or_existing_filter ) {
						if ( $posted_date_timestamp < $bulk_generation_new_or_existing_filter_reference_timestamp ) {
							continue; // skip existing posts.
						}
					} elseif ( $posted_date_timestamp >= $bulk_generation_new_or_existing_filter_reference_timestamp ) {
							continue; // skip new posts.

					}
				}
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

				// check availability of the file
				// todo: find a more efficient way to check this.
				/*
				$attachment_source_data = ai4seo_get_best_attachment_source( $this_post_id );

				if ( ! $attachment_source_data ) {
					continue;
				}*/

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

	// read AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME (include post IDs for validation).
	$current_generation_status_summary = ai4seo_read_generation_status_summary( false, true );

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
		$generate_metadata_for_fully_covered_entries   = ai4seo_do_generate_metadata_for_fully_covered_entries();
		$processing_post_ids                           = ai4seo_get_post_ids_from_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME );
		$pending_post_ids                              = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );
		$failed_post_ids                               = ai4seo_get_post_ids_from_option( AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME );
		$excluded_from_missing_metadata_post_ids       = array_merge(
			ai4seo_get_post_ids_from_option( AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME ),
			ai4seo_get_post_ids_from_option( AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME )
		);
		$excluded_from_missing_metadata_post_id_lookup = array_flip( array_values( array_unique( array_filter( array_map( 'absint', $excluded_from_missing_metadata_post_ids ) ) ) ) );

		// read the percentage of active metadata by post ids.
		$percentage_of_available_metadata_by_post_ids = ai4seo_read_percentage_of_available_metadata_by_post_ids( $post_ids );

		foreach ( $percentage_of_available_metadata_by_post_ids as $this_post_id => $this_percentage ) {
			$this_post_id                       = (int) $this_post_id;
			$this_post_type                     = $posts[ $this_post_id ]['post_type'] ?? '';
			$this_post_was_generated            = in_array( $this_post_id, $generated_data_all_post_ids );
			$is_this_post_excluded_from_missing = isset( $excluded_from_missing_metadata_post_id_lookup[ $this_post_id ] );

			// check if fully covered.
			if ( 100 == $this_percentage ) {
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
			if ( in_array( $this_post_id, $processing_post_ids ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME ][ $this_post_type ][] = $this_post_id;
			}

			// check if this post is in pending post ids.
			if ( in_array( $this_post_id, $pending_post_ids ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME ][ $this_post_type ][] = $this_post_id;
			}

			// check if this post is in failed post ids.
			if ( in_array( $this_post_id, $failed_post_ids ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME ][ $this_post_type ][] = $this_post_id;
			}
		}
	}

	// ANALYZE ATTACHMENT POSTS.

	if ( $attachment_posts_ids ) {
		$generate_attachment_attributes_for_fully_covered_entries = ai4seo_do_generate_attachment_attributes_for_fully_covered_entries();

		// BUILD ATTACHMENT ATTRIBUTES COVERAGE ARRAY.
		$attachment_attributes_coverage                  = ai4seo_read_and_analyse_attachment_attributes_coverage( $attachment_posts_ids );
		$num_total_attachment_attributes_fields          = ai4seo_get_active_num_attachment_attributes();
		$attachment_attributes_coverage_summary          = ai4seo_get_attachment_attributes_coverage_summary( $attachment_attributes_coverage );
		$processing_attachment_post_ids                  = ai4seo_get_post_ids_from_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		$pending_attachment_post_ids                     = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		$failed_attachment_post_ids                      = ai4seo_get_post_ids_from_option( AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		$excluded_from_missing_attachment_post_ids       = array_merge(
			ai4seo_get_post_ids_from_option( AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ),
			ai4seo_get_post_ids_from_option( AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME )
		);
		$excluded_from_missing_attachment_post_id_lookup = array_flip( array_values( array_unique( array_filter( array_map( 'absint', $excluded_from_missing_attachment_post_ids ) ) ) ) );
		unset( $attachment_attributes_coverage );

		// ADD ENTRIES TO THE GENERATION STATUS POST IDS.
		foreach ( $attachment_attributes_coverage_summary as $this_post_id => $num_fields_covered ) {
			$this_post_was_generated                       = in_array( $this_post_id, $generated_data_all_post_ids );
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
			if ( in_array( $this_post_id, $processing_attachment_post_ids ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][ $this_attachment_post_type ][] = (int) $this_post_id;
			}

			// check if this post is in pending attachment post ids.
			if ( in_array( $this_post_id, $pending_attachment_post_ids ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][ $this_attachment_post_type ][] = (int) $this_post_id;
			}

			// check if this post is in failed attachment post ids.
			if ( in_array( $this_post_id, $failed_attachment_post_ids ) ) {
				$generation_status_post_ids_to_add[ AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ][ $this_attachment_post_type ][] = (int) $this_post_id;
			}
		}
	}

	if ( $generation_status_post_ids_to_add ) {
		foreach ( $generation_status_post_ids_to_add as $option_name => $post_type_entries ) {
			if ( ! $post_type_entries || ! is_array( $post_type_entries ) ) {
				continue;
			}

			foreach ( $post_type_entries as $post_type => $post_ids ) {
				ai4seo_add_post_ids_to_generation_status_summary(
					$current_generation_status_summary,
					$option_name,
					$post_type,
					$post_ids
				);
			}
		}
	}

	// SAVE NEW POST IDS TO OPTIONS.

	foreach ( $new_post_ids_by_option as $this_option_name => $this_post_ids ) {
		if ( ! $this_post_ids ) {
			continue;
		}

		if ( $debug ) {
			ai4seo_debug_message( 732600128, esc_html( __FUNCTION__ ) . ' > Adding to option ' . $this_option_name . ': ' . count( $this_post_ids ) . ' post ids' );
		}

		ai4seo_add_post_ids_to_option( $this_option_name, $this_post_ids );
	}

	if ( $debug ) {
		ai4seo_debug_message( 417529305, esc_html( __FUNCTION__ ) . ' > Current generation status summary: ' . esc_html( ai4seo_stringify( $current_generation_status_summary ) ) );
	}

	// Store the full summary with post IDs for validation and incremental analysis updates.
	ai4seo_update_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, $current_generation_status_summary );

	// Store the small totals-only companion so dashboards and reports do not need to load all post IDs on large sites.
	ai4seo_update_option(
		AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
		ai4seo_get_generation_status_summary_totals( $current_generation_status_summary )
	);

	// KEEP TRACK OF LAST POST ID.

	if ( $debug ) {
		ai4seo_debug_message( 408476980, esc_html( __FUNCTION__ ) . ' > Last processed post ID: ' . $last_processed_post_id );
	}

	// update the last processed post id.
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID, $last_processed_post_id, false );

	// was last chunk? -> finished = true, otherwise false.
	return $is_last_chunk;
}

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Returns the current content type list cache version.
 *
 * @return int Cache version.
 */
function ai4seo_get_content_type_list_cache_version(): int {
	$cache_version = (int) ai4seo_get_option( AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME, 1, true );

	if ( $cache_version < 1 ) {
		return 1;
	}

	return $cache_version;
}

// =========================================================================================== \\

/**
 * Bumps the content type list cache version.
 *
 * @return void
 */
function ai4seo_bump_content_type_list_cache_version(): void {
	static $ai4seo_did_bump_content_type_list_cache_version = false;

	// One version bump per request is enough because cache keys only need to differ from earlier requests.
	if ( $ai4seo_did_bump_content_type_list_cache_version ) {
		return;
	}

	$ai4seo_did_bump_content_type_list_cache_version = true;
	$new_cache_version                               = (int) round( microtime( true ) * 1000 );

	ai4seo_update_option( AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME, $new_cache_version, false );
}

// =========================================================================================== \\

/**
 * Bumps content type list caches when an option can affect list membership or counters.
 *
 * @param string $option_name Option name.
 * @return void
 */
function ai4seo_maybe_bump_content_type_list_cache_version( string $option_name ): void {
	$option_name = trim( $option_name );

	// The version option must not invalidate itself, otherwise direct option writes could recurse.
	if ( '' === $option_name || AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME === $option_name ) {
		return;
	}

	// Post-ID status options and summary counters are the only option families that affect these list caches.
	if ( in_array( $option_name, AI4SEO_ALL_POST_ID_OPTIONS, true )
		|| in_array(
			$option_name,
			array(
				AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME,
				AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
			),
			true
		)
	) {
		ai4seo_bump_content_type_list_cache_version();
	}
}

// =========================================================================================== \\

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
}

// =========================================================================================== \\

/**
 * Clears the generation status summary request cache when a related option changes.
 *
 * @param string $option_name Option name.
 * @return void
 */
function ai4seo_maybe_reset_generation_status_summary_request_cache( string $option_name ): void {
	// Only summary-related option writes can make this request-local summary cache stale.
	if ( ! in_array(
		$option_name,
		array(
			AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME,
			AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
		),
		true
	) ) {
		return;
	}

	ai4seo_reset_generation_status_summary_request_cache();
}

// =========================================================================================== \\

/**
 * Read the generation status summary option.
 *
 * @param bool $totals_only When true, return legacy totals-only format.
 * @param bool $use_direct_database_call When true, read directly from the database, bypassing any caching layers.
 * @return array Generation status summary.
 */
function ai4seo_read_generation_status_summary( bool $totals_only = true, bool $use_direct_database_call = true ): array {
	global $ai4seo_generation_status_summary_request_cache;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 451439298, 'Prevented loop', true );
		return array();
	}

	if ( ! is_array( $ai4seo_generation_status_summary_request_cache ?? null ) ) {
		$ai4seo_generation_status_summary_request_cache = array();
	}

	// Cache parsed summaries for the current request so dashboard counters do not repeat DB reads and unserialization.
	$cache_key = ( $totals_only ? 'totals' : 'full' ) . '_' . ( $use_direct_database_call ? 'direct' : 'cached' );

	if ( isset( $ai4seo_generation_status_summary_request_cache[ $cache_key ] ) ) {
		return $ai4seo_generation_status_summary_request_cache[ $cache_key ];
	}

	if ( $totals_only ) {
		// Prefer the compact totals option for counter-style reads to avoid loading the full post-id summary on large sites.
		$generation_status_summary_totals = ai4seo_get_option( AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME, null, $use_direct_database_call );

		if ( is_array( $generation_status_summary_totals ) ) {
			$generation_status_summary_totals                             = ai4seo_deep_sanitize( $generation_status_summary_totals, 'absint' );
			$ai4seo_generation_status_summary_request_cache[ $cache_key ] = $generation_status_summary_totals;

			return $generation_status_summary_totals;
		}
	}

	// Fall back to the full summary when totals are missing, for example after upgrading from an older plugin version.
	// The totals companion is rebuilt below so later reads can use the smaller option.
	$generation_status_summary = ai4seo_get_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, '{}', $use_direct_database_call );

	if ( ! is_array( $generation_status_summary ) ) {
		$generation_status_summary = maybe_unserialize( $generation_status_summary );
	}

	if ( ! is_array( $generation_status_summary )
		&& is_string( $generation_status_summary )
		&& '' !== $generation_status_summary
		&& ai4seo_is_json( $generation_status_summary ) ) {
		$generation_status_summary = json_decode( $generation_status_summary, true );
	}

	if ( ! is_array( $generation_status_summary ) ) {
		$generation_status_summary = array();
	}

	$generation_status_summary = ai4seo_deep_sanitize( $generation_status_summary, 'absint' );

	if ( ! $totals_only ) {
		$generation_status_summary                                    = ai4seo_normalize_generation_status_summary_storage( $generation_status_summary );
		$ai4seo_generation_status_summary_request_cache[ $cache_key ] = $generation_status_summary;

		return $generation_status_summary;
	}

	$generation_status_summary_totals                             = ai4seo_get_generation_status_summary_totals( $generation_status_summary );
	$ai4seo_generation_status_summary_request_cache[ $cache_key ] = $generation_status_summary_totals;

	// Backfill the totals companion after reading the full summary once.
	ai4seo_update_option( AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME, $generation_status_summary_totals );

	return $generation_status_summary_totals;
}

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

	// Retain compatibility with serialized and JSON storage while rejecting undecodable data.
	$generation_status_summary = maybe_unserialize( $generation_status_summary );

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

		foreach ( $post_type_entries as $summary_entry ) {
			if ( ! is_array( $summary_entry )
				|| ! array_key_exists( 'total', $summary_entry )
				|| ! isset( $summary_entry['post_ids'] )
				|| ! is_array( $summary_entry['post_ids'] ) ) {
				return null;
			}

			// Reject partial, duplicate, or non-numeric memberships rather than coercing corrupt data into valid IDs.
			$normalized_post_ids = array();

			foreach ( $summary_entry['post_ids'] as $post_id ) {
				if ( ! is_scalar( $post_id ) || ! is_numeric( $post_id ) ) {
					return null;
				}

				$normalized_post_id = (int) $post_id;

				if ( $normalized_post_id < 1 || (float) $post_id !== (float) $normalized_post_id ) {
					return null;
				}

				$normalized_post_ids[] = $normalized_post_id;
			}

			// Stored totals must describe the same unique membership set before it can be updated incrementally.
			if ( ! is_scalar( $summary_entry['total'] )
				|| ! is_numeric( $summary_entry['total'] ) ) {
				return null;
			}

			$normalized_total = (int) $summary_entry['total'];

			if ( $normalized_total < 0
				|| (float) $summary_entry['total'] !== (float) $normalized_total
				|| count( $normalized_post_ids ) !== count( array_unique( $normalized_post_ids ) )
				|| $normalized_total !== count( $normalized_post_ids ) ) {
				return null;
			}
		}
	}

	// Normalize only after validating so malformed storage cannot silently become an authoritative empty summary.
	return ai4seo_normalize_generation_status_summary_storage(
		ai4seo_deep_sanitize( $generation_status_summary, 'absint' )
	);
}

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Schedules a one-off full analysis when incremental summary storage is unusable.
 *
 * @return void
 */
function ai4seo_schedule_generation_status_summary_rebuild(): void {
	// Reuse the cron injector so concurrent failures converge on one near-term rebuild task.
	ai4seo_inject_additional_cronjob_call( AI4SEO_GENERATION_STATUS_SUMMARY_REBUILD_CRON_JOB_NAME );
}

// =========================================================================================== \\

/**
 * Rebuilds invalid generation status summary storage through the existing full analysis mechanism.
 *
 * @return void
 */
function ai4seo_rebuild_generation_status_summary(): void {
	// A dedicated cron callback can force a clean reset without granting admin-mutation privileges.
	ai4seo_force_posts_table_analysis_refresh( false, true );
}

// =========================================================================================== \\

/**
 * Synchronizes summary buckets for source-option memberships changed during one request.
 *
 * @param array $changed_post_ids_by_option Changed post IDs keyed by their source option name.
 * @return bool Whether the stored summary changed.
 */
function ai4seo_sync_generation_status_summary_for_option_changes( array $changed_post_ids_by_option ): bool {
	static $is_syncing = false;

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
		// Reject missing, malformed, or totals-only storage instead of persisting a partial reconstruction.
		ai4seo_reset_generation_status_summary_request_cache();
		$generation_status_summary = ai4seo_read_generation_status_summary_for_incremental_sync();

		if ( null === $generation_status_summary ) {
			ai4seo_schedule_generation_status_summary_rebuild();
			return false;
		}

		$original_generation_status_summary = $generation_status_summary;
		$post_types_by_post_id               = array();

		// Resolve every changed post type once for all touched source options.
		foreach ( array_keys( $all_changed_post_id_lookup ) as $post_id ) {
			$post_type = get_post_type( $post_id );

			if ( $post_type ) {
				$post_types_by_post_id[ $post_id ] = sanitize_key( $post_type );
			}
		}

		foreach ( $normalized_option_changes as $option_name => $post_ids ) {
			$post_id_lookup = array_flip( $post_ids );

			// Remove this option's changed IDs before rebuilding only its final live memberships.
			foreach ( $generation_status_summary[ $option_name ] ?? array() as $post_type => $summary_entry ) {
				$summary_post_ids = array_values(
					array_filter(
						$summary_entry['post_ids'],
						function ( $post_id ) use ( $post_id_lookup ) {
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

			// Read each touched source option once after all request-level transitions have completed.
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

		// Compare canonical memberships so harmless key or ID ordering never causes companion rewrites.
		if ( ai4seo_get_comparable_generation_status_summary( $generation_status_summary )
			=== ai4seo_get_comparable_generation_status_summary( $original_generation_status_summary ) ) {
			return false;
		}

		// Persist the full summary and its compact totals companion once for the complete request-level batch.
		ai4seo_update_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, $generation_status_summary );
		ai4seo_update_option(
			AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
			ai4seo_get_generation_status_summary_totals( $generation_status_summary )
		);

		return true;
	} finally {
		// Release the recursion guard even when a storage or WordPress callback throws.
		$is_syncing = false;
	}
}

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Retrieve post IDs that have generated data stored in postmeta.
 *
 * @param array $post_ids List of post IDs to check.
 * @return array Sanitized list of post IDs with generated data.
 */
function ai4seo_read_generated_data_post_ids_by_post_ids( array $post_ids ): array {
	global $wpdb;

	if ( empty( $post_ids ) ) {
		return array();
	}

	// Sanitize and filter invalid entries.
	$post_ids = array_filter( array_map( 'absint', $post_ids ) );

	if ( empty( $post_ids ) ) {
		return array();
	}

	$generated_data_post_ids = array();
	$database_chunk_size     = ai4seo_get_database_chunk_size();
	$post_ids_chunks         = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		// Build dynamic placeholders.
		$placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

		// Prepare query safely.
		$this_generated_data_post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id
                FROM {$wpdb->postmeta}
                WHERE meta_key = %s AND post_id IN ($placeholders)",
				...array_merge(
					array( AI4SEO_POST_META_GENERATED_DATA_META_KEY ),
					$this_post_ids_chunk
				)
			)
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321681, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! $this_generated_data_post_ids ) {
			continue;
		}

		$generated_data_post_ids = array_merge( $generated_data_post_ids, $this_generated_data_post_ids );
	}

	// Sanitize result set.
	return array_map( 'absint', (array) $generated_data_post_ids );
}

// =========================================================================================== \\

function ai4seo_reset_posts_table_analysis() {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 402005224, 'Prevented loop', true );
		return;
	}

	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID, 0, false );
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, 'idle', false );

	// reset seo coverage base post ids options.
	foreach ( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS as $this_option_name ) {
		ai4seo_update_option( $this_option_name, array() );
	}

	// reset generation status summary.
	$generation_status_summary = array();

	foreach ( AI4SEO_ALL_POST_ID_OPTIONS as $this_option_name ) {
		$generation_status_summary[ $this_option_name ] = array();
	}

	// Reset writes an empty full summary so the next analysis starts from a clean post-id state.
	ai4seo_update_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, $generation_status_summary );

	// Keep the totals companion in sync with the reset full summary.
	ai4seo_update_option(
		AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
		ai4seo_get_generation_status_summary_totals( $generation_status_summary )
	);
}

// =========================================================================================== \\

/**
 * Resets post table analysis data and starts the analysis when the current request may run it.
 *
 * @param bool $debug if true, debug information will be printed.
 * @param bool $force if true, bypasses the short analysis run throttle.
 * @param bool $allow_trusted_admin_mutation if true, trusted admin mutations may start the analysis.
 * @param bool $allow_debug_heavy_db_operations if true, debug runs may bypass the heavy DB operations setting.
 * @return void
 */
function ai4seo_force_posts_table_analysis_refresh(
	bool $debug = false,
	bool $force = false,
	bool $allow_trusted_admin_mutation = false,
	bool $allow_debug_heavy_db_operations = false
): void {
	// Check the start gate before resetting state so unsupported contexts cannot leave analysis stale.
	if ( ! ai4seo_can_start_posts_table_analysis( $allow_trusted_admin_mutation ) ) {
		return;
	}

	// Preserve the existing fallback path when analysis setup is not currently possible.
	if ( ! ai4seo_is_posts_table_analysis_possible( $debug, $allow_debug_heavy_db_operations ) ) {
		ai4seo_try_start_posts_table_analysis( true, $debug, $force, $allow_trusted_admin_mutation, $allow_debug_heavy_db_operations );
		return;
	}

	// Reset only after the request has proved it can immediately attempt the replacement analysis run.
	ai4seo_reset_posts_table_analysis();
	ai4seo_try_start_posts_table_analysis( true, $debug, $force, $allow_trusted_admin_mutation, $allow_debug_heavy_db_operations );
}

// =========================================================================================== \\

/**
 * Forces a posts table analysis refresh after a trusted admin mutation.
 *
 * @param bool $debug if true, debug information will be printed.
 * @return void
 */
function ai4seo_force_posts_table_analysis_refresh_after_admin_mutation( bool $debug = false ): void {
	// Mutation-triggered refreshes are already user-initiated, so bypass the short analysis throttle.
	ai4seo_force_posts_table_analysis_refresh( $debug, true, true );
}



// endregion
// ___________________________________________________________________________________________.
