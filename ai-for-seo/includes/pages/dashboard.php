<?php
/**
 * Renders the content of the submenu page for the AI for SEO dashboard page.
 *
 * @package AI_For_SEO
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ai4seo_can_use_plugin_content() ) {
	return;
}


// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_current_utc_hour           = (int) ai4seo_gmdate( 'H' );
$ai4seo_posts_table_analysis_state = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE );
$ai4seo_help_page_url              = ai4seo_get_subpage_url( 'help' );
$ai4seo_can_administer_plugin      = ai4seo_can_administer_plugin();


// === EXECUTE BULK GENERATION SOONER ======================================================== \\

// Keep the action and submitted token together because this dashboard link mutates cron state through GET.
$ai4seo_execute_sooner_nonce_action = 'ai4seo_execute_cron_job_sooner';
$ai4seo_execute_sooner_nonce        = sanitize_text_field( wp_unslash( $_GET['ai4seo_execute_cron_job_sooner_nonce'] ?? '' ) );

// Execute the state-changing action only when it originated from the protected dashboard link.
if ( $ai4seo_can_administer_plugin && isset( $_GET['ai4seo-execute-cron-job-sooner'] ) && wp_verify_nonce( $ai4seo_execute_sooner_nonce, $ai4seo_execute_sooner_nonce_action ) ) {
	ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
}

// Embed a fresh matching token in the action link so the subsequent GET can pass the nonce gate above.
$ai4seo_execute_sooner_button = '';

if ( $ai4seo_can_administer_plugin ) {
	$ai4seo_execute_sooner_text_link_url = ai4seo_get_subpage_url(
		'dashboard',
		array(
			'ai4seo-execute-cron-job-sooner'       => 'true',
			'ai4seo_execute_cron_job_sooner_nonce' => wp_create_nonce( $ai4seo_execute_sooner_nonce_action ),
		)
	);
	$ai4seo_execute_sooner_button        = ai4seo_get_small_a_tag_icon_button_tag( $ai4seo_execute_sooner_text_link_url, '', '', 'bolt', __( 'Execute sooner!', 'ai-for-seo' ), '', 'ai4seo_add_loading_html_to_element(this); ai4seo_show_full_page_loading_screen();' );
}


// === CREDITS BALANCE ======================================================================= \\

$ai4seo_current_credits_balance                                = ai4seo_robhub_api()->get_credits_balance();
$ai4seo_metadata_credits_cost_per_post                         = ai4seo_calculate_metadata_credits_cost_per_post();
$ai4seo_attachment_attributes_credits_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();
$ai4seo_is_robhub_account_synced                               = ai4seo_robhub_api()->is_account_synced();
$ai4seo_heavy_db_operations_disabled                           = (bool) ai4seo_get_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS );

// next free credits.
$ai4seo_next_free_credits_timestamp    = ai4seo_robhub_api()->read_environmental_variable( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP );
$ai4seo_free_plan_credits_amount       = ai4seo_get_plan_credits( 'free' );
$ai4seo_next_free_credits_seconds_left = ai4seo_get_time_difference_in_seconds( $ai4seo_next_free_credits_timestamp );


// === CHECK BULK GENERATION STATUS ========================================================== \\

// Normalize enabled post types through the shared accessor before exact UI comparisons.
$ai4seo_active_bulk_generation_post_types         = ai4seo_get_enabled_bulk_generation_post_types();
$ai4seo_bulk_generation_duration                  = (int) ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_DURATION );
$ai4seo_is_any_bulk_generation_enabled            = ! empty( $ai4seo_active_bulk_generation_post_types );
$ai4seo_should_auto_queue_bulk_generation_entries = ai4seo_should_auto_queue_bulk_generation_entries();

// Credit-aware queue eligibility feeds the chart without changing Autopilot's existing excavation rules.
$ai4seo_can_auto_queue_metadata_entries             = (
	$ai4seo_should_auto_queue_bulk_generation_entries
	&& $ai4seo_current_credits_balance >= $ai4seo_metadata_credits_cost_per_post
);
$ai4seo_can_auto_queue_attachment_attribute_entries = (
	$ai4seo_should_auto_queue_bulk_generation_entries
	&& $ai4seo_current_credits_balance >= $ai4seo_attachment_attributes_credits_cost_per_attachment_post
);
$ai4seo_bulk_generation_queue_count                 = ai4seo_get_bulk_generation_queue_count();
$ai4seo_bulk_generation_processing_count            = ai4seo_get_bulk_generation_processing_count();

// Manual queue guidance applies only when neither queued nor actively processing work remains.
$ai4seo_is_waiting_for_manual_queue_entries = (
	$ai4seo_is_any_bulk_generation_enabled
	&& ! $ai4seo_should_auto_queue_bulk_generation_entries
	&& 0 === $ai4seo_bulk_generation_queue_count
	&& 0 === $ai4seo_bulk_generation_processing_count
);
$ai4seo_bulk_generation_status              = ai4seo_get_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
$ai4seo_last_bulk_generation_update_time    = ai4seo_get_cron_job_status_update_time( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
$ai4seo_last_bulk_generation_run_was_longer_ago_than_bulk_generation_duration = $ai4seo_last_bulk_generation_update_time && ( time() - $ai4seo_last_bulk_generation_update_time > $ai4seo_bulk_generation_duration );
$ai4seo_last_bulk_generation_run_was_long_ago                                 = $ai4seo_last_bulk_generation_update_time && ( time() - $ai4seo_last_bulk_generation_update_time > $ai4seo_bulk_generation_duration + 300 );
$ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago                       = ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago();
$ai4seo_next_cron_job_call      = wp_next_scheduled( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
$ai4seo_next_cron_job_call_diff = ( $ai4seo_next_cron_job_call ? $ai4seo_next_cron_job_call - time() : 9999999 );


// === NEW OR EXISTING FILTER =============================================================== \\

// check if we should only generate data for new or existing posts.
// Resolve the shared state once so statistics and filter notices use the queue contract.
$ai4seo_bulk_generation_date_filter_state = ai4seo_get_current_bulk_generation_date_filter_state();


// === POST TYPES =========================================================================== \\

$ai4seo_supported_post_types            = ai4seo_get_supported_post_types();
$ai4seo_supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

$ai4seo_all_supported_post_types = array_merge( $ai4seo_supported_post_types, $ai4seo_supported_attachment_post_types );


// === CHANGE LOG ============================================================================ \\

$ai4seo_change_log = ai4seo_get_change_log();
// check if the anchor "ai4seo_recent_plugin_updates" parameter is in the URL.
$ai4seo_pre_open_recent_plugin_updates = isset( $_GET['ai4seo_recent_plugin_updates'] ) && sanitize_text_field( wp_unslash( $_GET['ai4seo_recent_plugin_updates'] ) ) === 'true';


// === NOTIFICATIONS ========================================================================= \\

$ai4seo_notifications = ai4seo_get_displayable_notifications();

if ( $ai4seo_can_administer_plugin ) {
	// Notification read state is shared site-wide and therefore administrative.
	ai4seo_mark_all_displayable_notifications_as_read();
}


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Keep notifications and the card grid in one response boundary without changing the established card container.
echo "<div class='ai4seo-dashboard-refresh-root'>";

// === NOTIFICATIONS ================================================================================= \\

// Render server-managed notices outside the card diff while retaining them in the dashboard refresh response.
echo "<div class='ai4seo-dashboard-notifications'>";

if ( $ai4seo_notifications ) {
	// Display the notifications.
	foreach ( $ai4seo_notifications as $ai4seo_this_notification_index => $ai4seo_this_notification ) {
		ai4seo_echo_notice_from_notification( $ai4seo_this_notification_index, $ai4seo_this_notification );
	}
}

echo '</div>';

// Preserve the dashboard class because its styles and the incremental card diff both target this container.
echo "<div class='ai4seo-cards-container ai4seo-dashboard'>";

	// === STATISTICS ============================================================================ \\

if ( $ai4seo_all_supported_post_types ) {
	$ai4seo_total_num_pending_posts            = 0;
	$ai4seo_total_num_pending_metadata_posts   = 0;
	$ai4seo_total_num_pending_attachment_posts = 0;
	$ai4seo_num_finished_posts_by_post_type    = ai4seo_get_num_finished_posts_by_post_type();
	$ai4seo_num_failed_posts_by_post_type      = ai4seo_get_num_failed_posts_by_post_type();
	$ai4seo_num_pending_posts_by_post_type     = ai4seo_get_num_pending_posts_by_post_type();
	$ai4seo_num_processing_posts_by_post_type  = ai4seo_get_num_processing_posts_by_post_type();
	$ai4seo_num_missing_posts_by_post_type     = ai4seo_get_num_missing_posts_by_post_type();

	/* translators: %s: post type name */
	$ai4seo_retry_all_failed_metadata_generations_link_label              = __( 'Retry all failed generations for <strong>%s</strong>', 'ai-for-seo' );
	$ai4seo_retry_all_failed_attachment_attributes_generations_link_label = __( 'Retry all failed <strong>media attribute</strong> generations', 'ai-for-seo' );
	$ai4seo_retry_all_failed_metadata_button_tags                         = array();
	$ai4seo_retry_all_failed_attachment_attributes_generations_link_tag   = '';

	echo "<div class='card ai4seo-card ai4seo-fully-centered-card ai4seo-three-column-card ai4seo-dashboard-statistics-card'>";

		// data shown might be incomplete, if the posts table analysis is not completed yet -> hint.
	if ( 'completed' !== $ai4seo_posts_table_analysis_state ) {
		echo "<div class='ai4seo-dashboard-posts-table-analysis-not-completed-hint'>";
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'gear', '', 'ai4seo-spinning-icon' ) );
			echo ' ';
			printf(
				/* translators: %s: plugin name */
				esc_html__( '%s is currently analyzing your pages and media files. Please wait.', 'ai-for-seo' ),
				esc_html( AI4SEO_PLUGIN_NAME )
			);
		echo '</div>';

		if ( ! $ai4seo_heavy_db_operations_disabled ) {
			echo "<div id='ai4seo-no-dashboard-refresh-delay'></div>";
		}
	}

		// refresh performance analysis button.
	if ( $ai4seo_can_administer_plugin && ! $ai4seo_heavy_db_operations_disabled && 'completed' === $ai4seo_posts_table_analysis_state ) {
		echo "<div class='ai4seo-top-right-refresh-button-wrapper'>";
			ai4seo_echo_wp_kses( ai4seo_get_small_icon_button_tag( 'rotate', __( 'Refresh statistics', 'ai-for-seo' ), '', 'ai4seo_refresh_dashboard_statistics(this); return false;' ) );
		echo '</div>';
	}

		// Default chart values keep the legend order stable before post-type data is available.
		$ai4seo_chart_values = ai4seo_get_seo_coverage_chart_values();

		// Keep one accumulator for the shared legend because the per-post-type chart variable is replaced in the loop.
		$ai4seo_chart_legend_values = $ai4seo_chart_values;

		$ai4seo_could_output_any_chart = false;

	foreach ( $ai4seo_all_supported_post_types as $ai4seo_this_post_type ) {
		$ai4seo_this_original_post_type                   = $ai4seo_this_post_type;
		$ai4seo_this_num_finished_post_ids                = $ai4seo_num_finished_posts_by_post_type[ $ai4seo_this_post_type ] ?? 0;
		$ai4seo_this_num_failed_post_ids                  = $ai4seo_num_failed_posts_by_post_type[ $ai4seo_this_post_type ] ?? 0;
		$ai4seo_this_num_pending_post_ids                 = $ai4seo_num_pending_posts_by_post_type[ $ai4seo_this_post_type ] ?? 0;
		$ai4seo_this_num_processing_post_ids              = $ai4seo_num_processing_posts_by_post_type[ $ai4seo_this_post_type ] ?? 0;
		$ai4seo_this_num_missing_post_ids                 = $ai4seo_num_missing_posts_by_post_type[ $ai4seo_this_post_type ] ?? 0;
		$ai4seo_this_num_eligible_for_auto_queue_post_ids = 0;

		if ( $ai4seo_can_administer_plugin && $ai4seo_this_num_failed_post_ids > 0 ) {
			if ( 'attachment' === $ai4seo_this_original_post_type ) {
				$ai4seo_retry_all_failed_attachment_attributes_generations_link_tag = ai4seo_get_small_icon_button_tag(
					'rotate',
					$ai4seo_retry_all_failed_attachment_attributes_generations_link_label,
					'ai4seo-ignore-during-dashboard-refresh',
					'ai4seo_retry_all_failed_attachment_attributes(this); return false;'
				);
			} elseif ( ! isset( $ai4seo_retry_all_failed_metadata_button_tags[ $ai4seo_this_original_post_type ] ) ) {
				$ai4seo_retry_all_failed_metadata_button_tags[ $ai4seo_this_original_post_type ] = ai4seo_get_small_icon_button_tag(
					'rotate',
					sprintf(
						$ai4seo_retry_all_failed_metadata_generations_link_label,
						ai4seo_get_post_type_translation( $ai4seo_this_original_post_type, true )
					),
					'ai4seo-ignore-during-dashboard-refresh',
					"ai4seo_retry_all_failed_metadata(this, '" . esc_js( $ai4seo_this_original_post_type ) . "'); return false;"
				);
			}
		}

		// Remove failed, pending, and processing entries from the coverage analyzer's missing total.
		$ai4seo_this_num_missing_post_ids -= $ai4seo_this_num_failed_post_ids;
		$ai4seo_this_num_missing_post_ids -= $ai4seo_this_num_pending_post_ids;
		$ai4seo_this_num_missing_post_ids -= $ai4seo_this_num_processing_post_ids;

		if ( $ai4seo_this_num_missing_post_ids < 0 ) {
			$ai4seo_this_num_missing_post_ids = 0;
		}

		// Attachment classification selects both the relevant credit cost and the pending-status subtotal.
		$ai4seo_this_is_attachment_post_type = in_array( $ai4seo_this_original_post_type, $ai4seo_supported_attachment_post_types, true );
		$ai4seo_this_can_auto_queue_entries  = in_array( $ai4seo_this_original_post_type, $ai4seo_active_bulk_generation_post_types, true )
			&& ( $ai4seo_this_is_attachment_post_type ? $ai4seo_can_auto_queue_attachment_attribute_entries : $ai4seo_can_auto_queue_metadata_entries );

		// Split immediately queueable entries from truly missing SEO without changing the chart total.
		if ( $ai4seo_this_can_auto_queue_entries ) {
			$ai4seo_this_num_eligible_for_auto_queue_post_ids = $ai4seo_this_num_missing_post_ids;
			$ai4seo_this_num_missing_post_ids                 = 0;
		}

		// Status-card totals include every incomplete entry for enabled post types, independent of chart segmentation.
		if ( in_array( $ai4seo_this_post_type, $ai4seo_active_bulk_generation_post_types, true ) ) {
			$ai4seo_this_num_pending_generation_posts = (
				$ai4seo_this_num_missing_post_ids
				+ $ai4seo_this_num_eligible_for_auto_queue_post_ids
				+ $ai4seo_this_num_pending_post_ids
				+ $ai4seo_this_num_processing_post_ids
			);
			$ai4seo_total_num_pending_posts          += $ai4seo_this_num_pending_generation_posts;

			// Reuse the attachment classification that also selected the correct auto-queue credit cost above.
			if ( $ai4seo_this_is_attachment_post_type ) {
				$ai4seo_total_num_pending_attachment_posts += $ai4seo_this_num_pending_generation_posts;
			} else {
				$ai4seo_total_num_pending_metadata_posts += $ai4seo_this_num_pending_generation_posts;
			}
		}

		// Queued combines all pending queue entries with entries currently being processed.
		$ai4seo_this_num_queued_post_ids = $ai4seo_this_num_pending_post_ids + $ai4seo_this_num_processing_post_ids;

		// Build each chart through the shared status-order helper used by the aggregate legend.
		$ai4seo_chart_values = ai4seo_get_seo_coverage_chart_values(
			$ai4seo_this_num_finished_post_ids,
			$ai4seo_this_num_queued_post_ids,
			$ai4seo_this_num_eligible_for_auto_queue_post_ids,
			$ai4seo_this_num_missing_post_ids,
			$ai4seo_this_num_failed_post_ids
		);

		// The shared legend aggregates every post-type chart while retaining the same semantic key order.
		foreach ( $ai4seo_chart_values as $ai4seo_chart_status => $ai4seo_chart_status_info ) {
			$ai4seo_chart_legend_values[ $ai4seo_chart_status ]['value'] += $ai4seo_chart_status_info['value'];
		}

		// get total value, and continue if it is 0.
		$ai4seo_total_value = array_sum( array_column( $ai4seo_chart_values, 'value' ) );

		// phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Treat integer and floating-point zero totals equivalently.
		if ( 0 == $ai4seo_total_value ) {
			continue;
		}

		$ai4seo_could_output_any_chart = true;

		// attachment -> media workaround.
		if ( 'attachment' === $ai4seo_this_post_type ) {
			$ai4seo_this_post_type = 'media';
		}

		$ai4seo_supported_post_type_label  = ai4seo_get_dashicon_tag_for_navigation( $ai4seo_this_post_type );
		$ai4seo_supported_post_type_label .= ucfirst( ai4seo_get_post_type_translation( $ai4seo_this_post_type, true ) );

		ai4seo_echo_half_donut_chart_with_headline_and_percentage( $ai4seo_supported_post_type_label, $ai4seo_chart_values, $ai4seo_this_num_finished_post_ids, $ai4seo_total_value, $ai4seo_posts_table_analysis_state, $ai4seo_this_post_type );
	}

		// chart legend container.
	if ( $ai4seo_could_output_any_chart ) {
		echo "<div class='ai4seo-chart-legend-container'>";
			ai4seo_echo_chart_legend( $ai4seo_chart_legend_values, $ai4seo_is_any_bulk_generation_enabled );
		echo '</div>';
	}

		// no data message.
	if ( ! $ai4seo_could_output_any_chart && 'completed' === $ai4seo_posts_table_analysis_state ) {
		echo "<div class='ai4seo-no-data-message ai4seo-dashboard-statistics-message-row ai4seo-red-message'>";
			echo "<div class='ai4seo-dashboard-statistics-message-content ai4seo-red-message'>";
				echo '<strong>' . esc_html__( 'Note:', 'ai-for-seo' ) . '</strong> ';
				echo esc_html__( 'No data to display yet. Please review your settings. It looks like all entries are currently excluded from analysis based on your configuration.', 'ai-for-seo' );
			echo '</div>';
		echo '</div>';
	}

	// Surface invalid persisted state instead of silently presenting statistics the queue cannot reproduce.
	if ( empty( $ai4seo_bulk_generation_date_filter_state['is_valid'] ) ) {
		echo "<div class='ai4seo-dashboard-new-or-existing-filter-note ai4seo-dashboard-statistics-message-row ai4seo-red-message'>";
			echo "<div class='ai4seo-dashboard-statistics-message-content ai4seo-red-message'>";
				echo '<strong>' . esc_html__( 'SEO Autopilot paused:', 'ai-for-seo' ) . '</strong> ';
				echo esc_html( ai4seo_get_invalid_bulk_generation_date_filter_message() );
			echo '</div>';
		echo '</div>';
	} elseif ( 'both' !== $ai4seo_bulk_generation_date_filter_state['filter'] ) {
		// echo message about the existing filter and that maybe entries are not shown in the stats.
		$ai4seo_new_or_existing_filter_text = '';

		if ( 'new' === $ai4seo_bulk_generation_date_filter_state['filter'] ) {
			$ai4seo_new_or_existing_filter_text = sprintf(
				/* translators: %s: reference timestamp */
				esc_html__( 'The SEO Autopilot is currently set to only generate data for new content created after %s. Therefore, existing content before this date is not included in the above statistics.', 'ai-for-seo' ),
				'<strong>' . esc_html( ai4seo_format_unix_timestamp( (int) $ai4seo_bulk_generation_date_filter_state['reference_timestamp'] ) ) . '</strong>'
			);
		} elseif ( 'existing' === $ai4seo_bulk_generation_date_filter_state['filter'] ) {
			$ai4seo_new_or_existing_filter_text = sprintf(
				/* translators: %s: reference timestamp */
				esc_html__( 'The SEO Autopilot is currently set to only generate data for existing content created before %s. Therefore, new content after this date is not included in the above statistics.', 'ai-for-seo' ),
				'<strong>' . esc_html( ai4seo_format_unix_timestamp( (int) $ai4seo_bulk_generation_date_filter_state['reference_timestamp'] ) ) . '</strong>'
			);
		}

		echo "<div class='ai4seo-dashboard-new-or-existing-filter-note ai4seo-dashboard-statistics-message-row ai4seo-red-message'>";
			echo "<div class='ai4seo-dashboard-statistics-message-content ai4seo-red-message'>";
				echo '<strong>' . esc_html__( 'Note:', 'ai-for-seo' ) . '</strong> ';
				ai4seo_echo_wp_kses( $ai4seo_new_or_existing_filter_text );
				ai4seo_echo_wp_kses( ' ' . esc_html__( 'You can change this setting in the SEO Autopilot settings.', 'ai-for-seo' ) );
			echo '</div>';
		echo '</div>';
	}

	if ( $ai4seo_can_administer_plugin && ( $ai4seo_retry_all_failed_metadata_button_tags || $ai4seo_retry_all_failed_attachment_attributes_generations_link_tag ) ) {
		echo "<div class='ai4seo-buttons-wrapper ai4seo-dashboard-retry-all-failed-wrapper ai4seo-ignore-during-dashboard-refresh'>";

		if ( $ai4seo_retry_all_failed_metadata_button_tags ) {
			foreach ( $ai4seo_retry_all_failed_metadata_button_tags as $ai4seo_this_retry_button_tag ) {
				ai4seo_echo_wp_kses( $ai4seo_this_retry_button_tag );
			}
		}

		if ( $ai4seo_retry_all_failed_attachment_attributes_generations_link_tag ) {
			ai4seo_echo_wp_kses( $ai4seo_retry_all_failed_attachment_attributes_generations_link_tag );
		}

			echo '</div>';
	}

		echo '</div>';

		// If manual queueing is enabled but no active post type has anything left to generate, show the normal finished state.
	if ( $ai4seo_is_waiting_for_manual_queue_entries && 0 === $ai4seo_total_num_pending_posts ) {
		$ai4seo_is_waiting_for_manual_queue_entries = false;
	}
}

	// force line break
	// echo "<div class='ai4seo-gap-zero'></div>";.


	// === CREDITS ========================================================================== \\

	// Calculate backlog coverage separately from "can generate anything" so large sites with <1% coverage do not show a false insufficiency warning.
	$ai4seo_can_afford_at_least_one_generation = true;

if ( isset( $ai4seo_total_num_pending_posts ) && $ai4seo_total_num_pending_posts ) {
	$ai4seo_pending_generation_costs = array();

	if ( $ai4seo_total_num_pending_metadata_posts > 0 && $ai4seo_metadata_credits_cost_per_post > 0 ) {
		$ai4seo_pending_generation_costs[] = $ai4seo_metadata_credits_cost_per_post;
	}

	if ( $ai4seo_total_num_pending_attachment_posts > 0 && $ai4seo_attachment_attributes_credits_cost_per_attachment_post > 0 ) {
		$ai4seo_pending_generation_costs[] = $ai4seo_attachment_attributes_credits_cost_per_attachment_post;
	}

	if ( $ai4seo_pending_generation_costs ) {
		$ai4seo_min_credits_cost_for_pending_generation = min( $ai4seo_pending_generation_costs );
		$ai4seo_max_credits_cost_for_pending_generation = max( $ai4seo_pending_generation_costs );
		$ai4seo_can_afford_at_least_one_generation      = ( $ai4seo_current_credits_balance >= $ai4seo_min_credits_cost_for_pending_generation );

		if ( $ai4seo_can_afford_at_least_one_generation ) {
			$ai4seo_total_required_credits = max( 1, ( $ai4seo_total_num_pending_posts * $ai4seo_max_credits_cost_for_pending_generation ) );
			$ai4seo_raw_credits_percentage = ( $ai4seo_current_credits_balance / $ai4seo_total_required_credits ) * 100;
			$ai4seo_credits_percentage     = min( 100, max( 1, floor( $ai4seo_raw_credits_percentage ) ) );
		} else {
			$ai4seo_credits_percentage = 0;
		}
	} else {
		$ai4seo_credits_percentage = 100;
	}
} else {
	$ai4seo_credits_percentage = 100;
}

	echo "<div class='card ai4seo-card ai4seo-centered-card ai4seo-dashboard-card-min-height'>";

if ( $ai4seo_can_administer_plugin ) {
		// Refreshing account data is an administrative operation.
		echo "<div class='ai4seo-top-right-refresh-button-wrapper'>";
			ai4seo_echo_wp_kses( ai4seo_get_small_icon_button_tag( 'rotate', __( 'Resync account', 'ai-for-seo' ), '', 'ai4seo_refresh_robhub_account(this); return false;' ) );
		echo '</div>';
}

		// credits balance.
		echo "<div class='ai4seo-credits-container'>";
			echo "<h2 class='ai4seo-dashboard-section-heading'>";
				echo esc_html__( 'Credits', 'ai-for-seo' );
			echo '</h2>';

			echo "<div class='ai4seo-credits-number'>";
if ( $ai4seo_is_robhub_account_synced ) {
	echo esc_html( ai4seo_format_number_i18n( $ai4seo_current_credits_balance ) );
} else {
	echo "<span class='ai4seo-red-message'>";
		ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'triangle-exclamation', esc_html__( 'Failed to verify your license data. Please check your account settings.', 'ai-for-seo' ), 'ai4seo-red-icon' ) );
		echo esc_html__( 'License issue', 'ai-for-seo' );
	echo '</span>';
}
			echo '</div>';
		echo '</div>';

		// next free credits container.
if ( $ai4seo_current_credits_balance < $ai4seo_free_plan_credits_amount ) {
	echo "<div class='ai4seo-next-free-credits-container'>";
		$ai4seo_credits_badge_html = sprintf(
			'<span class="ai4seo-credits-usage-badge"><span class="ai4seo-bigger-green-number">+%1$s</span> %2$s</span>',
			esc_html( ai4seo_format_number_i18n( AI4SEO_DAILY_FREE_CREDITS_AMOUNT ) ),
			esc_html__( 'Credits', 'ai-for-seo' )
		);

		$ai4seo_countdown_html = sprintf(
			'<span class="ai4seo-countdown ai4seo-ignore-during-dashboard-refresh" data-time-left="%1$s" data-trigger="ai4seo_reload_page">%2$s</span>',
			esc_attr( $ai4seo_next_free_credits_seconds_left ),
			esc_html( ai4seo_format_seconds_to_hhmmss_or_days_hhmmss( $ai4seo_next_free_credits_seconds_left ) )
		);

		$ai4seo_next_free_credits_time_html = sprintf(
			'<strong>%s</strong>',
			$ai4seo_countdown_html
		);

		ai4seo_echo_wp_kses(
			sprintf(
				/* translators: 1: credits badge HTML, 2: countdown HTML (wrapped in <strong>) */
				__( 'Next %1$s in %2$s', 'ai-for-seo' ),
				$ai4seo_credits_badge_html,
				$ai4seo_next_free_credits_time_html
			)
		);

		$ai4seo_free_credits_tooltip = sprintf(
			/* translators: 1: number of free credits, 2: free plan credits amount */
			__( 'We provide you with <strong>%1$s free Credits each day</strong> if your balance falls below %2$s Credits. Simply keep using the plugin to receive them automatically.', 'ai-for-seo' ),
			esc_html( ai4seo_format_number_i18n( AI4SEO_DAILY_FREE_CREDITS_AMOUNT ) ),
			esc_html( ai4seo_format_number_i18n( $ai4seo_free_plan_credits_amount ) ),
		);
		ai4seo_echo_wp_kses( ai4seo_get_icon_with_tooltip_tag( $ai4seo_free_credits_tooltip ) );
	echo '</div>';
}

if ( $ai4seo_can_administer_plugin ) {
		echo "<div class='ai4seo-how-to-get-credits-container'>";

if ( $ai4seo_is_robhub_account_synced ) {
	// current discount.
	ai4seo_echo_current_discount();

	// Turn Buy credits button.
	echo "<div class='ai4seo-buy-credits-button-container'>";
		ai4seo_echo_wp_kses(
			ai4seo_get_icon_button_tag(
				'circle-plus',
				esc_html__( 'Get more Credits', 'ai-for-seo' ),
				( $ai4seo_current_credits_balance < AI4SEO_BLUE_GET_MORE_CREDITS_BUTTON_THRESHOLD || $ai4seo_credits_percentage < 100 ? 'ai4seo-primary-button' : '' ),
				'ai4seo_open_get_more_credits_modal();'
			)
		);
	echo '</div>';

} else {
	// go to Account Settings.
	echo "<div class='ai4seo-gap'></div>";

	ai4seo_echo_wp_kses(
		ai4seo_get_a_tag_icon_button_tag( ai4seo_get_subpage_url( 'account' ), '', '', 'key', esc_html__( 'Account Settings', 'ai-for-seo' ), 'ai4seo-primary-button' )
	);
}
		echo '</div>';
}

		// costs breakdown.
		echo "<div class='ai4seo-credits-generation-costs-info'>";
			ai4seo_echo_cost_breakdown_section( $ai4seo_credits_percentage, $ai4seo_can_afford_at_least_one_generation );
		echo '</div>';

	echo '</div>';

	// ___________________________________________________________________________________________ \\
	// === SEO AUTOPILOT ========================================================================= \\
	// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ( $ai4seo_is_robhub_account_synced ) {
	$ai4seo_additional_sub_status_text = '<br>';

	// Add the last cron job call in a readable format.
	if ( $ai4seo_last_bulk_generation_update_time ) {
		// Use the shared formatter so relative and absolute execution-time messages stay consistent.
		$ai4seo_additional_sub_status_text .= ' ' . ai4seo_get_last_execution_time_text( $ai4seo_last_bulk_generation_update_time );
	} else {
		$ai4seo_additional_sub_status_text .= ' ' . esc_html__( 'The SEO Autopilot has never been executed yet.', 'ai-for-seo' );
	}

	// find proper task scheduler status text.
	if ( $ai4seo_next_cron_job_call_diff >= 10 ) {
		$ai4seo_next_cron_job_call_diff_minutes = ceil( $ai4seo_next_cron_job_call_diff / 60 );
		$ai4seo_additional_sub_status_text     .= ' ' . sprintf(
			/* translators: %s: time until next execution */
			esc_html__( 'It should continue in less than %s.', 'ai-for-seo' ),
			sprintf(
				/* translators: %s: number of minutes */
				_n( '%s minute', '%s minutes', $ai4seo_next_cron_job_call_diff_minutes, 'ai-for-seo' ),
				ai4seo_format_number_i18n( $ai4seo_next_cron_job_call_diff_minutes )
			),
		);
	} else {
		$ai4seo_additional_sub_status_text .= ' ' . esc_html__( 'It should continue in a few moments.', 'ai-for-seo' );
	}

	// Emphasize the existing automatic refresh behavior because the manual refresh fallback is no longer shown.
	$ai4seo_additional_sub_status_text .= ' <strong>' . esc_html__( 'This page will refresh automatically.', 'ai-for-seo' ) . '</strong>';

	// Show the option to trigger the next cron run sooner when it is not imminent.
	if ( $ai4seo_can_administer_plugin && $ai4seo_next_cron_job_call_diff >= 70 ) {
		$ai4seo_additional_sub_status_text .= ' ' . $ai4seo_execute_sooner_button;
	}

	// CARD.
	echo "<div class='card ai4seo-card ai4seo-centered-card ai4seo-dashboard-card-min-height'>";
		echo "<h2 class='ai4seo-dashboard-section-heading'>" . esc_html__( 'SEO Autopilot (Bulk Generation)', 'ai-for-seo' ) . '</h2>';

		echo "<div class='ai4seo-bulk-generation-status-container'>";
	if ( ! $ai4seo_is_any_bulk_generation_enabled ) {
		echo "<img src='" . esc_url( ai4seo_get_sooz_logo_url( '256x256' ) ) . "' alt='" . esc_attr__( 'SEO Autopilot is deactivated', 'ai-for-seo' ) . "' class='ai4seo-bulk-generation-status-inactive-logo'>";

		echo "<div class='ai4seo-bulk-generation-status-text'>";
		echo esc_html__( 'Off', 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-subtext'>";
		echo esc_html__( 'SEO Autopilot has not been set up yet.', 'ai-for-seo' );
		echo '</div>';
	} elseif ( $ai4seo_is_waiting_for_manual_queue_entries ) {
		echo "<img src='" . esc_url( ai4seo_get_sooz_logo_url( '256x256' ) ) . "' alt='" . esc_attr__( 'SEO Autopilot is waiting for manually queued entries', 'ai-for-seo' ) . "' class='ai4seo-bulk-generation-status-active-logo'>";

		echo "<div class='ai4seo-bulk-generation-status-text ai4seo-bulk-generation-status-muted'>";
			echo esc_html__( 'Empty queue', 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-subtext'>";
			echo esc_html__( 'Auto Queue Entries is disabled and the queue is empty. Add entries to the queue manually from the Posts or Media lists so SEO Autopilot can continue.', 'ai-for-seo' );
		echo '</div>';
	} elseif ( 'completed' !== $ai4seo_posts_table_analysis_state ) {
		echo "<img src='" . esc_url( ai4seo_get_sooz_logo_url( '256x256' ) ) . "' alt='" . esc_attr__( 'SEO Autopilot is active, but it is currently waiting for the analysis tasks to finish.', 'ai-for-seo' ) . "' class='ai4seo-bulk-generation-status-active-logo'>";

		echo "<div class='ai4seo-bulk-generation-status-text'>";
		echo esc_html__( 'Analyzing...', 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-subtext'>";
			printf(
				/* translators: %s: plugin name */
				esc_html__( '%s is analyzing your pages and media files. Please wait until the analysis is complete.', 'ai-for-seo' ),
				esc_html( AI4SEO_PLUGIN_NAME )
			);
		echo '</div>';
	} elseif ( isset( $ai4seo_total_num_pending_posts ) && 0 === $ai4seo_total_num_pending_posts ) {
		echo "<img src='" . esc_url( ai4seo_get_sooz_logo_url( '256x256' ) ) . "' alt='" . esc_attr__( 'SEO Autopilot is active but idling', 'ai-for-seo' ) . "' class='ai4seo-bulk-generation-status-active-logo'>";

		echo "<div class='ai4seo-bulk-generation-status-text'>";
			echo esc_html__( 'All done & idle', 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-subtext'>";
			echo esc_html__( 'Waiting for new entries to process.', 'ai-for-seo' );
		echo '</div>';
	} elseif ( ! $ai4seo_can_afford_at_least_one_generation ) {
		echo "<img src='" . esc_url( ai4seo_get_sooz_logo_url( '256x256' ) ) . "' alt='" . esc_attr__( 'SEO Autopilot is active but no credits available', 'ai-for-seo' ) . "' class='ai4seo-bulk-generation-status-active-logo'>";

		// triangle-exclamation on the top right corner.
		echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'triangle-exclamation' ) );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-text'>";
			echo esc_html__( 'Insufficient Credits', 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-subtext'>";
			echo esc_html__( 'Not enough Credits available. Please get more Credits.', 'ai-for-seo' );
		echo '</div>';

	} elseif ( $ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago && $ai4seo_last_bulk_generation_run_was_long_ago ) {
		echo "<img src='" . esc_url( ai4seo_get_sooz_logo_url( '256x256' ) ) . "' alt='" . esc_attr__( 'SEO Autopilot is active but slow', 'ai-for-seo' ) . "' class='ai4seo-bulk-generation-status-active-logo'>";

		// triangle-exclamation in the top right corner.
		echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'triangle-exclamation' ) );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-text'>";
			echo esc_html__( 'Pending...', 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-subtext'>";
			echo esc_html__( 'The last SEO Autopilot execution was longer ago than expected, which may indicate an issue with your cron job configuration. Please check your cron job settings to ensure consistent execution.', 'ai-for-seo' );
		if ( $ai4seo_additional_sub_status_text ) {
				echo ' ';
				ai4seo_echo_wp_kses( $ai4seo_additional_sub_status_text );
		}
			echo '</div>';
	} elseif ( in_array( $ai4seo_bulk_generation_status, array( 'initiating', 'processing', 'scheduled', 'finished' ), true ) && $ai4seo_last_bulk_generation_update_time && ! $ai4seo_last_bulk_generation_run_was_longer_ago_than_bulk_generation_duration ) {
		echo "<div class='ai4seo-bulk-generation-status-animated-logo-container'>";
			echo "<img src='" . esc_url( ai4seo_get_sooz_logo_url( '512x512-animated' ) ) . "' class='ai4seo-bulk-generation-status-animated-logo-pulse'>";
			echo "<img src='" . esc_url( ai4seo_get_sooz_logo_url( '512x512-animated' ) ) . "' alt='" . esc_attr__( 'SEO Autopilot is processing', 'ai-for-seo' ) . "' class='ai4seo-bulk-generation-status-animated-logo'>";
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-text ai4seo-bulk-generation-status-muted'>";
			echo esc_html__( 'Processing...', 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-subtext'>";
			echo esc_html__( 'Please wait and check the "Recent Activity" section for results.', 'ai-for-seo' );
			// Reinforce that the processing state will update without requiring a manual reload.
			echo ' <strong>' . esc_html__( 'This page will refresh automatically.', 'ai-for-seo' ) . '</strong>';
		echo '</div>';
	} elseif ( $ai4seo_last_bulk_generation_update_time && ( 'idle' === $ai4seo_bulk_generation_status || ( in_array( $ai4seo_bulk_generation_status, array( 'initiating', 'processing', 'finished', 'scheduled' ), true ) && $ai4seo_last_bulk_generation_run_was_longer_ago_than_bulk_generation_duration ) ) ) {
		// triangle-exclamation in the top right corner
		// echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
		// ai4seo_echo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation"));
		// echo "</div>";.

		echo "<img src='" . esc_url( ai4seo_get_sooz_logo_url( '256x256' ) ) . "' alt='" . esc_attr__( 'SEO Autopilot is active but not generating', 'ai-for-seo' ) . "' class='ai4seo-bulk-generation-status-active-logo'>";

		echo "<div class='ai4seo-bulk-generation-status-text ai4seo-bulk-generation-status-muted'>";
			echo esc_html__( 'Pending...', 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-subtext'>";
			echo esc_html__( 'The SEO Autopilot is active and currently waiting for the next scheduled execution in order to process the pending entries.', 'ai-for-seo' );

		if ( $ai4seo_additional_sub_status_text ) {
			echo ' ';
			ai4seo_echo_wp_kses( $ai4seo_additional_sub_status_text );
		}
		echo '</div>';

		// something went wrong, if we wait at least x seconds after setup without any activity.
	} elseif ( ! $ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago ) {
		// waiting for task scheduler to start.
		echo "<img src='" . esc_url( ai4seo_get_sooz_logo_url( '256x256' ) ) . "' alt='" . esc_attr__( 'SEO Autopilot is waiting for task scheduler to start', 'ai-for-seo' ) . "' class='ai4seo-bulk-generation-status-active-logo'>";

		echo "<div class='ai4seo-bulk-generation-status-text'>";
		echo esc_html__( 'Initializing...', 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-subtext'>";
		echo esc_html__( 'Waiting for task scheduler to start.', 'ai-for-seo' );

		if ( $ai4seo_additional_sub_status_text ) {
			echo ' ';
			ai4seo_echo_wp_kses( $ai4seo_additional_sub_status_text );
		}
		echo '</div>';
	} else {
		echo "<img src='" . esc_url( ai4seo_get_sooz_logo_url( '256x256' ) ) . "' alt='" . esc_attr__( 'SEO Autopilot is stuck', 'ai-for-seo' ) . "' class='ai4seo-bulk-generation-status-inactive-logo'>";

		// triangle-exclamation in the top right corner.
		echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
		ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'triangle-exclamation' ) );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-text'>";
		echo esc_html__( 'Error', 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-bulk-generation-status-subtext'>";
		echo esc_html__( 'Something went wrong. Please try again. If the issue continues, review your cron job configuration and check your PHP CLI error logs for details.', 'ai-for-seo' );

		if ( $ai4seo_additional_sub_status_text ) {
			echo ' ';
			ai4seo_echo_wp_kses( $ai4seo_additional_sub_status_text );
		}
		echo '</div>';
	}

			echo '</div>';

	if ( $ai4seo_can_administer_plugin ) {
			// Bulk Generation controls change site-wide automation settings.
			echo "<div class='ai4seo-bulk-generation-button-container'>";
			echo "<div class='ai4seo-buttons-wrapper'>";
	if ( $ai4seo_is_any_bulk_generation_enabled ) {
		// stop SEO Autopilot.
		ai4seo_echo_wp_kses( ai4seo_get_abort_button_tag( 'stop-circle', esc_html__( 'Stop SEO Autopilot', 'ai-for-seo' ), '', 'ai4seo_stop_bulk_generation(this);' ) );
	}

				// setup SEO Autopilot.
				ai4seo_echo_wp_kses( ai4seo_get_icon_button_tag( 'paper-plane', esc_html__( 'Set up SEO Autopilot', 'ai-for-seo' ), '', 'ai4seo_open_modal_from_schema("seo-autopilot", {modal_size: "small", unsaved_changes_warnings: true});' ) );
				echo '</div>';
				echo '</div>';
	}

				echo '</div>';
}


	// === Recent Activity ========================================================================== \\

	$ai4seo_latest_activity = ai4seo_get_option( AI4SEO_LATEST_ACTIVITY_OPTION_NAME, array() );
	$ai4seo_latest_activity = is_array( $ai4seo_latest_activity )
		? ai4seo_filter_latest_activity_entries_for_current_user( $ai4seo_latest_activity )
		: array();

	// Give the failed-status legend action a stable target within the incrementally refreshed dashboard.
	echo "<div id='ai4seo-recent-activity' class='card ai4seo-card ai4seo-dashboard-card-min-height'>";
		echo "<h2 class='ai4seo-dashboard-section-heading'>" . esc_html__( 'Recent Activity', 'ai-for-seo' ) . '</h2>';

if ( ! $ai4seo_latest_activity ) {
	echo "<p class='ai4seo-latest-activity-empty-message'>" . esc_html__( 'No recent activity. Try to generate metadata or media attributes first.', 'ai-for-seo' ) . '</p>';
} else {
	echo "<div class='ai4seo-latest-activity-container'>";
	foreach ( $ai4seo_latest_activity as $ai4seo_this_latest_activity_entry ) {
		// Resolve one fallback title for both the visible entry text and its action-specific accessible labels.
		$ai4seo_this_plugin_page_icon            = ai4seo_get_dashicon_tag_for_navigation( $ai4seo_this_latest_activity_entry['post_type'] ?? '' );
		$ai4seo_this_latest_activity_entry_title = (string) ( $ai4seo_this_latest_activity_entry['title'] ?? '' );

		if ( '' === $ai4seo_this_latest_activity_entry_title ) {
			if ( $ai4seo_this_latest_activity_entry['post_id'] ?? 0 ) {
				$ai4seo_this_latest_activity_entry_title = sprintf(
					/* translators: %s: Post ID. */
					esc_html__( 'Post ID %s', 'ai-for-seo' ),
					$ai4seo_this_latest_activity_entry['post_id']
				);
			} else {
				$ai4seo_this_latest_activity_entry_title = esc_html__( 'N/A', 'ai-for-seo' );
			}
		}

		echo "<div class='ai4seo-latest-activity-item'>";
			echo "<div class='ai4seo-latest-activity-item-icon'>";
				ai4seo_echo_wp_kses( $ai4seo_this_plugin_page_icon );
			echo '</div>';

			echo "<div class='ai4seo-latest-activity-item-text'>";

				echo '<strong>';
		if ( $ai4seo_this_latest_activity_entry['timestamp'] ?? 0 ) {
			echo esc_html( ai4seo_format_unix_timestamp( $ai4seo_this_latest_activity_entry['timestamp'] ?? 0 ) ) . ' - ';
		}
				echo '</strong>';

				// title.
				echo esc_html( $ai4seo_this_latest_activity_entry_title );

				echo '<br>';

				// status.
				$ai4seo_this_latest_activity_entry_is_success = ( $ai4seo_this_latest_activity_entry['status'] ?? 'error' ) === 'success';

		if ( $ai4seo_this_latest_activity_entry_is_success ) {
			echo "<div class='ai4seo-green-message'>";
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'circle-check', esc_html__( 'Success', 'ai-for-seo' ), 'ai4seo-gray-icon' ) );
			echo ' ';
		} else {
			echo "<div class='ai4seo-red-message'>";
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'circle-xmark', esc_html__( 'Error', 'ai-for-seo' ), 'ai4seo-red-icon' ) );
			echo ' ';
		}

					// if details given, output them else the action.
		if ( isset( $ai4seo_this_latest_activity_entry['details'] ) && $ai4seo_this_latest_activity_entry['details'] ) {
				echo esc_html( ai4seo_mb_substr( $ai4seo_this_latest_activity_entry['details'], 0, 160 ) );
		} else {
					// metadata-manually-generated", "metadata-bulk-generated", "attachment-attributes-manually-generated", "attachment-attributes-bulk-generated.
			switch ( $ai4seo_this_latest_activity_entry['action'] ) {
				case 'metadata-manually-generated':
					echo esc_html__( 'Metadata manually generated', 'ai-for-seo' );
					break;
				case 'metadata-bulk-generated':
					echo esc_html__( 'Metadata generated (by SEO Autopilot)', 'ai-for-seo' );
					break;
				case 'attachment-attributes-manually-generated':
					echo esc_html__( 'Media attributes manually generated', 'ai-for-seo' );
					break;
				case 'attachment-attributes-bulk-generated':
					echo esc_html__( 'Media attributes generated (by SEO Autopilot)', 'ai-for-seo' );
					break;
			}
		}
								echo '</div>';

								echo '</div>';

		if ( $ai4seo_this_latest_activity_entry['cost'] ?? 0 ) {
			echo "<div class='ai4seo-latest-activity-item-costs'><div class='ai4seo-credits-usage-badge'>";
				echo esc_html( ai4seo_format_number_i18n( $ai4seo_this_latest_activity_entry['cost'] ) ) . ' ' . esc_html__( 'Cr.', 'ai-for-seo' );
			echo '</div></div>';
		}

								echo "<div class='ai4seo-latest-activity-item-buttons'>";
								// see post / media preview.
		if ( isset( $ai4seo_this_latest_activity_entry['url'] ) && $ai4seo_this_latest_activity_entry['url'] ) {
			$ai4seo_view_latest_activity_entry_label = sprintf(
				/* translators: %s: Entry title. */
				esc_html__( 'View %s', 'ai-for-seo' ),
				$ai4seo_this_latest_activity_entry_title
			);
			ai4seo_echo_wp_kses( ai4seo_get_a_tag_icon_button_tag( $ai4seo_this_latest_activity_entry['url'], '', '_blank', 'eye', '', '', '', $ai4seo_view_latest_activity_entry_label ) );
		}

		// Preserve legacy numeric post types as strings while rejecting compound stored values.
		$ai4seo_this_latest_activity_post_type = $ai4seo_this_latest_activity_entry['post_type'] ?? '';
		$ai4seo_this_latest_activity_post_type = is_string( $ai4seo_this_latest_activity_post_type ) || is_int( $ai4seo_this_latest_activity_post_type )
			? (string) $ai4seo_this_latest_activity_post_type
			: '';

		if ( in_array( $ai4seo_this_latest_activity_post_type, $ai4seo_supported_attachment_post_types, true ) ) {
			// Name the icon-only editor action with the same entry identity shown in this activity row.
			$ai4seo_open_latest_activity_entry_editor_label = sprintf(
				/* translators: %s: Entry title. */
				esc_html__( 'Open media attributes editor for %s', 'ai-for-seo' ),
				$ai4seo_this_latest_activity_entry_title
			);
			ai4seo_echo_wp_kses( ai4seo_get_icon_button_tag( 'pen-to-square', '', '', 'ai4seo_open_attachment_attributes_editor_modal(' . esc_js( $ai4seo_this_latest_activity_entry['post_id'] ) . ');', $ai4seo_open_latest_activity_entry_editor_label ) );
		} else {
			// Keep the metadata editor action distinct from the adjacent view-entry action for screen readers.
			$ai4seo_open_latest_activity_entry_editor_label = sprintf(
				/* translators: %s: Entry title. */
				esc_html__( 'Open metadata editor for %s', 'ai-for-seo' ),
				$ai4seo_this_latest_activity_entry_title
			);
			ai4seo_echo_wp_kses( ai4seo_get_icon_button_tag( 'pen-to-square', '', '', 'ai4seo_open_metadata_editor_modal(' . esc_js( $ai4seo_this_latest_activity_entry['post_id'] ) . ');', $ai4seo_open_latest_activity_entry_editor_label ) );
		}
								echo '</div>';
								echo '</div>';
	}
	echo '</div>';
}

		// todo: add show full log button.

	echo '</div>';


	$ai4seo_dashboard_toggle_onclick   = 'ai4seo_toggle_collapsible_section(this, 200);';
	$ai4seo_dashboard_toggle_onkeydown = 'ai4seo_handle_collapsible_section_keydown(event, this, 200);';

	// === Ask for feedback ========================================================================== \\

	$ai4seo_support_feedback_trigger_id = 'ai4seo-support-feedback-trigger';
	$ai4seo_support_feedback_panel_id   = 'ai4seo-support-feedback-panel';

	echo "<div class='card ai4seo-card ai4seo-ignore-during-dashboard-refresh'>";
		echo "<h2 class='ai4seo-dashboard-section-heading ai4seo-dashboard-collapsible-heading'>";
		echo "<button type='button' class='ai4seo-dashboard-collapsible-trigger' id='" . esc_attr( $ai4seo_support_feedback_trigger_id ) . "' aria-expanded='true' aria-controls='" . esc_attr( $ai4seo_support_feedback_panel_id ) . "' onclick='" . esc_attr( $ai4seo_dashboard_toggle_onclick ) . "' onkeydown='" . esc_attr( $ai4seo_dashboard_toggle_onkeydown ) . "'>";
			echo esc_html__( 'Support & Feedback', 'ai-for-seo' );
			echo "<span class='ai4seo-caret-down'>";
				ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'caret-down' ) );
			echo '</span>';
			echo "<span class='ai4seo-caret-up ai4seo-display-none'>";
				ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'caret-up' ) );
			echo '</span>';
		echo '</button>';
		echo '</h2>';

		echo "<div class='ai4seo-dashboard-section-content' id='" . esc_attr( $ai4seo_support_feedback_panel_id ) . "'>";
			// HELP SECTION
			// icon.
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'circle-question', __( 'Help section', 'ai-for-seo' ), 'ai4seo-big-paragraph-icon' ) );
			echo ' ';

			ai4seo_echo_wp_kses(
				sprintf(
				/* translators: %1$s is help section link, %2$s is getting started guide link, %3$s is FAQ link, %4$s is useful links section link */
					__( 'Check our <a href="%1$s">help section</a> for a detailed <a href="%2$s">getting started guide</a>, our organized <a href="%3$s">F.A.Q</a> or other <a href="%4$s">useful links</a>.', 'ai-for-seo' ),
					esc_url( $ai4seo_help_page_url ),
					esc_url( $ai4seo_help_page_url . '#ai4seo-getting-started-section' ),
					esc_url( $ai4seo_help_page_url . '#ai4seo-faq-section' ),
					esc_url( $ai4seo_help_page_url . '#ai4seo-links-section' )
				)
			);

			// button to help section.
			echo "<div class='ai4seo-dashboard-action-row'>";
				ai4seo_echo_wp_kses( ai4seo_get_a_tag_icon_button_tag( $ai4seo_help_page_url, '', '', 'arrow-up-right-from-square', esc_html__( 'Go to our help section', 'ai-for-seo' ) ) );
			echo '</div>';

			// CONTACT US
			// icon.
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'comments', __( 'Contact us', 'ai-for-seo' ), 'ai4seo-big-paragraph-icon' ) );
			echo ' ';

			ai4seo_echo_wp_kses(
				sprintf(
					__( 'Missing a feature, need assistance, or looking for a quote?', 'ai-for-seo' ) . ' ' .
					/* translators: %s is a clickable email address */
					__( "Please <a href='%s' target='blank'>contact us</a>. We offer support in any language.", 'ai-for-seo' ),
					esc_url( AI4SEO_OFFICIAL_CONTACT_URL )
				)
			);

			// button to contact us.
			echo "<div class='ai4seo-dashboard-action-row'>";
				ai4seo_echo_wp_kses( ai4seo_get_contact_us_button() );
			echo '</div>';

			// RATE US
			// icon.
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'star', __( 'Rate us', 'ai-for-seo' ), 'ai4seo-big-paragraph-icon' ) );
			echo ' ';

			// like our plugin rate us at AI4SEO_OFFICIAL_WORDPRESS_ORG_PAGE.
			ai4seo_echo_wp_kses(
				sprintf(
				/* translators: %s is the rate us link */
					__( "Like our plugin and want to support us? Please <a href='%s' target='blank'>rate us</a> on WordPress.org. We appreciate your feedback!", 'ai-for-seo' ),
					esc_url( AI4SEO_OFFICIAL_RATE_US_URL )
				)
			);

			// button to rate us.
			echo "<div class='ai4seo-dashboard-action-row-final'>";
				ai4seo_echo_wp_kses( ai4seo_get_a_tag_icon_button_tag( esc_url( AI4SEO_OFFICIAL_RATE_US_URL ), '', '_blank', 'arrow-up-right-from-square', esc_html__( 'Rate us', 'ai-for-seo' ) ) );
			echo '</div>';

			echo '</div>';
			echo '</div>';


			// === Recent Plugin Updates ========================================================================== \\

			$ai4seo_recent_plugin_updates_trigger_id = 'ai4seo-recent-plugin-updates-trigger';
			$ai4seo_recent_plugin_updates_panel_id   = 'ai4seo-recent-plugin-updates-panel';

			echo "<div class='card ai4seo-card ai4seo-ignore-during-dashboard-refresh' id='ai4seo_recent_plugin_updates'>";
			echo "<h2 class='ai4seo-dashboard-section-heading ai4seo-dashboard-collapsible-heading'>";
			echo "<button type='button' class='ai4seo-dashboard-collapsible-trigger' id='" . esc_attr( $ai4seo_recent_plugin_updates_trigger_id ) . "' aria-expanded='" . ( $ai4seo_pre_open_recent_plugin_updates ? 'true' : 'false' ) . "' aria-controls='" . esc_attr( $ai4seo_recent_plugin_updates_panel_id ) . "' onclick='" . esc_attr( $ai4seo_dashboard_toggle_onclick ) . "' onkeydown='" . esc_attr( $ai4seo_dashboard_toggle_onkeydown ) . "'>";
			echo esc_html__( 'Recent Plugin Updates', 'ai-for-seo' );
			echo "<span class='ai4seo-caret-down'>";
				ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'caret-down' ) );
			echo '</span>';
			echo "<span class='ai4seo-caret-up ai4seo-display-none'>";
				ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'caret-up' ) );
			echo '</span>';
			echo '</button>';
			echo '</h2>';

			echo "<div class='ai4seo-recent-plugin-updates-content" . ( $ai4seo_pre_open_recent_plugin_updates ? '' : ' ai4seo-display-none' ) . "' id='" . esc_attr( $ai4seo_recent_plugin_updates_panel_id ) . "'>";
			echo esc_html__( 'We update the plugin regularly to improve its performance and add new features. Please check the changelog for more information.', 'ai-for-seo' ) . '<br>';

			// Generate updates dynamically from const parameter.
			foreach ( $ai4seo_change_log as $ai4seo_this_plugin_update_index => $ai4seo_this_plugin_update_details ) {
				$ai4seo_this_is_first_plugin_update  = ( 0 === $ai4seo_this_plugin_update_index );
				$ai4seo_this_changes_count           = count( $ai4seo_this_plugin_update_details['updates'] );
				$ai4seo_this_is_important_update     = $ai4seo_this_plugin_update_details['important'] ?? false;
				$ai4seo_this_plugin_update_dom_index = (int) $ai4seo_this_plugin_update_index + 1;
				$ai4seo_this_plugin_update_trigger   = 'ai4seo-plugin-update-' . $ai4seo_this_plugin_update_dom_index . '-trigger';
				$ai4seo_this_plugin_update_panel     = 'ai4seo-plugin-update-' . $ai4seo_this_plugin_update_dom_index . '-panel';

				// skip not important updates after the 5th entry.
				if ( $ai4seo_this_plugin_update_index >= 5 && ! $ai4seo_this_is_important_update ) {
					continue;
				}

				// Header with date, version, and collapsible functionality.
				echo "<button type='button' class='ai4seo-recent-plugin-updates-title" . ( $ai4seo_this_is_important_update ? ' ai4seo-recent-plugin-updates-important-title' : '' ) . "' id='" . esc_attr( $ai4seo_this_plugin_update_trigger ) . "' aria-expanded='" . ( $ai4seo_this_is_first_plugin_update ? 'true' : 'false' ) . "' aria-controls='" . esc_attr( $ai4seo_this_plugin_update_panel ) . "' onclick='" . esc_attr( $ai4seo_dashboard_toggle_onclick ) . "' onkeydown='" . esc_attr( $ai4seo_dashboard_toggle_onkeydown ) . "'>";
					// title.
				if ( $ai4seo_this_is_important_update ) {
					echo "<span class='ai4seo-blue-bubble'>" . esc_html( $ai4seo_this_plugin_update_details['version'] ) . '</span> ';
				} else {
					echo "<span class='ai4seo-bubble'>" . esc_html( $ai4seo_this_plugin_update_details['version'] ) . '</span> ';
				}

					echo esc_html( $ai4seo_this_plugin_update_details['date'] . ' ' );

					// Changes count.
					echo "<span class='ai4seo-changes-count'>(" . sprintf(
						/* translators: %s = number of changes */
						esc_html( _n( '%s change', '%s changes', $ai4seo_this_changes_count, 'ai-for-seo' ) ),
						esc_html( ai4seo_format_number_i18n( $ai4seo_this_changes_count ) )
					) . ')</span>';

				// Caret icons - first entry expanded, others collapsed.
				if ( $ai4seo_this_is_first_plugin_update ) {
					echo "<span class='ai4seo-caret-down ai4seo-display-none'>";
						ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'caret-down' ) );
					echo '</span>';
					echo "<span class='ai4seo-caret-up'>";
						ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'caret-up' ) );
					echo '</span>';
				} else {
					echo "<span class='ai4seo-caret-down'>";
						ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'caret-down' ) );
					echo '</span>';
					echo "<span class='ai4seo-caret-up ai4seo-display-none'>";
						ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'caret-up' ) );
					echo '</span>';
				}

				echo '</button>';

				// Content - first entry expanded, others collapsed.
				echo "<div class='ai4seo-changelog-entry-content" . ( ! $ai4seo_this_is_first_plugin_update ? ' ai4seo-display-none' : '' ) . "' id='" . esc_attr( $ai4seo_this_plugin_update_panel ) . "'>";
					echo '<ul>';
				foreach ( $ai4seo_this_plugin_update_details['updates'] as $ai4seo_this_plugin_update_item ) {
					echo '<li>' . wp_kses_post( $ai4seo_this_plugin_update_item ) . '</li>';
				}
					echo '</ul>';
				echo '</div>';
			}

			echo '</div>';
			echo '</div>';

			// === Money Back Guarantee ========================================================================== \\

			$ai4seo_guarantee_trigger_id = 'ai4seo-guarantee-trigger';
			$ai4seo_guarantee_panel_id   = 'ai4seo-guarantee-panel';

			echo "<div class='card ai4seo-card ai4seo-ignore-during-dashboard-refresh'>";
			echo "<h2 class='ai4seo-dashboard-section-heading ai4seo-dashboard-collapsible-heading'>";
			echo "<button type='button' class='ai4seo-dashboard-collapsible-trigger' id='" . esc_attr( $ai4seo_guarantee_trigger_id ) . "' aria-expanded='false' aria-controls='" . esc_attr( $ai4seo_guarantee_panel_id ) . "' onclick='" . esc_attr( $ai4seo_dashboard_toggle_onclick ) . "' onkeydown='" . esc_attr( $ai4seo_dashboard_toggle_onkeydown ) . "'>";
			echo esc_html__( 'Guarantee', 'ai-for-seo' );
			echo "<span class='ai4seo-caret-down'>";
				ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'caret-down' ) );
			echo '</span>';
			echo "<span class='ai4seo-caret-up ai4seo-display-none'>";
				ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'caret-up' ) );
			echo '</span>';
			echo '</button>';
			echo '</h2>';

			echo "<div class='ai4seo-dashboard-section-content ai4seo-display-none' id='" . esc_attr( $ai4seo_guarantee_panel_id ) . "'>";
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'handshake', __( 'Guarantee', 'ai-for-seo' ), 'ai4seo-handshake-icon' ) );
			echo ' ';
			// The guarantee remains visible while no applicability filter is available here.
			ai4seo_output_money_back_guarantee_notice();
			echo '</div>';
			echo '</div>';

			echo '</div>';
			// Close the refresh root after the card grid so AJAX can return one complete dashboard boundary.
			echo '</div>';
