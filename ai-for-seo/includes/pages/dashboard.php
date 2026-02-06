<?php
/**
 * Renders the content of the submenu page for the AI for SEO dashboard page.
 *
 * @since 1.0
 */

if (!defined("ABSPATH")) {
    exit;
}

if (!ai4seo_can_manage_this_plugin()) {
    return;
}


// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

global $ai4seo_did_run_post_table_analysis;
$ai4seo_current_utc_hour = (int)gmdate("H");
$ai4seo_posts_table_analysis_state = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE);


// === REFRESH BUTTON ======================================================================== \\

// collect some admin links and buttons
$ai4seo_dashboard_url = ai4seo_get_subpage_url("dashboard");
$ai4seo_refresh_button = ai4seo_get_small_button_tag($ai4seo_dashboard_url, "rotate", __("Refresh page", "ai-for-seo"), "", "ai4seo_add_loading_html_to_element(this); ai4seo_show_full_page_loading_screen();");


// === EXECUTE BULK GENERATION SOONER ======================================================== \\

// check if the cron job should be executed sooner
if (isset($_GET["ai4seo-execute-cron-job-sooner"]) && $_GET["ai4seo-execute-cron-job-sooner"]) {
    ai4seo_inject_additional_cronjob_call(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
}

// execute cron job sooner link
$ai4seo_execute_sooner_text_link_url = ai4seo_get_subpage_url("dashboard", array("ai4seo-execute-cron-job-sooner" => true));
$ai4seo_execute_sooner_button = ai4seo_get_small_button_tag($ai4seo_execute_sooner_text_link_url, "bolt", __("Execute sooner!", "ai-for-seo"), "", "ai4seo_add_loading_html_to_element(this); ai4seo_show_full_page_loading_screen();");


// === CREDITS BALANCE ======================================================================= \\

$ai4seo_current_credits_balance = ai4seo_robhub_api()->get_credits_balance();
$ai4seo_metadata_credits_cost_per_post = ai4seo_calculate_metadata_credits_cost_per_post();
$ai4seo_attachment_attributes_credits_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();
$ai4seo_min_credits_cost_per_generation = min($ai4seo_metadata_credits_cost_per_post, $ai4seo_attachment_attributes_credits_cost_per_attachment_post);
$ai4seo_max_credits_cost_per_generation = max($ai4seo_metadata_credits_cost_per_post, $ai4seo_attachment_attributes_credits_cost_per_attachment_post);
$ai4seo_insufficient_credits_balance = ($ai4seo_current_credits_balance < $ai4seo_min_credits_cost_per_generation);
$ai4seo_is_robhub_account_synced = ai4seo_robhub_api()->is_account_synced();
$ai4seo_heavy_db_operations_disabled = (bool) ai4seo_get_setting(AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS);

// next free credits
$ai4seo_next_free_credits_timestamp = ai4seo_robhub_api()->read_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP);
$ai4seo_free_plan_credits_amount = ai4seo_get_plan_credits("free");
$ai4seo_next_free_credits_seconds_left = ai4seo_get_time_difference_in_seconds($ai4seo_next_free_credits_timestamp);


// === CHECK BULK GENERATION STATUS ========================================================== \\

$ai4seo_active_bulk_generation_post_types = ai4seo_get_setting(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES);
$ai4seo_bulk_generation_duration = (int) ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_DURATION);
$ai4seo_is_any_bulk_generation_enabled = !empty($ai4seo_active_bulk_generation_post_types);
$ai4seo_bulk_generation_status = ai4seo_get_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_last_bulk_generation_update_time = ai4seo_get_cron_job_status_update_time(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_last_bulk_generation_run_was_longer_ago_than_bulk_generation_duration = $ai4seo_last_bulk_generation_update_time && (time() - $ai4seo_last_bulk_generation_update_time > $ai4seo_bulk_generation_duration);
$ai4seo_last_bulk_generation_run_was_long_ago = $ai4seo_last_bulk_generation_update_time && (time() - $ai4seo_last_bulk_generation_update_time > $ai4seo_bulk_generation_duration + 300);
$ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago = ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago();
$ai4seo_next_cron_job_call = wp_next_scheduled(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_next_cron_job_call_diff = ($ai4seo_next_cron_job_call ? $ai4seo_next_cron_job_call - time() : 9999999);


// === POST TYPES =========================================================================== \\

$ai4seo_supported_post_types = ai4seo_get_supported_post_types();
$ai4seo_supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

$ai4seo_all_supported_post_types = array_merge($ai4seo_supported_post_types, $ai4seo_supported_attachment_post_types);


// === CHANGE LOG ============================================================================ \\

$ai4seo_change_log = ai4seo_get_change_log();
// check if the anchor "ai4seo_recent_plugin_updates" parameter is in the URL
$ai4seo_pre_open_recent_plugin_updates = isset($_GET["ai4seo_recent_plugin_updates"]) && $_GET["ai4seo_recent_plugin_updates"] == "true";


// === NOTIFICATIONS ========================================================================= \\

$ai4seo_notifications = ai4seo_get_displayable_notifications();

// Mark all unread notifications as read when displayed
ai4seo_mark_all_displayable_notifications_as_read();


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-cards-container ai4seo-dashboard'>";

    // === NOTIFICATIONS ================================================================================= \\

    if ($ai4seo_notifications) {
        // Display the notifications
        foreach ($ai4seo_notifications as $ai4seo_this_notification_index => $ai4seo_this_notification) {
            ai4seo_echo_notice_from_notification($ai4seo_this_notification_index, $ai4seo_this_notification);
        }
    }

    // === STATISTICS ============================================================================ \\

    if ($ai4seo_all_supported_post_types) {
        $ai4seo_total_num_pending_posts = 0;
        $ai4seo_num_finished_posts_by_post_type = ai4seo_get_num_finished_posts_by_post_type();
        $ai4seo_num_failed_posts_by_post_type = ai4seo_get_num_failed_posts_by_post_type();
        $ai4seo_num_pending_posts_by_post_type = ai4seo_get_num_pending_posts_by_post_type();
        $ai4seo_num_processing_posts_by_post_type = ai4seo_get_num_processing_posts_by_post_type();
        $ai4seo_num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();

        $ai4seo_retry_all_failed_metadata_generations_link_label = esc_html__("Retry all failed generations for %s", "ai-for-seo");
        $ai4seo_retry_all_failed_attachment_attributes_generations_link_label = esc_html__("Retry all failed media attribute generations", "ai-for-seo");
        $ai4seo_retry_all_failed_metadata_button_tags = array();
        $ai4seo_retry_all_failed_attachment_attributes_generations_link_tag = "";

        echo "<div class='card ai4seo-card ai4seo-fully-centered-card ai4seo-three-column-card ai4seo-dashboard-statistics-card'>";

            // data shown might be incomplete, if the posts table analysis is not completed yet -> hint
            if ($ai4seo_posts_table_analysis_state !== 'completed') {
                echo "<div class='ai4seo-dashboard-posts-table-analysis-not-completed-hint'>";
                    ai4seo_echo_wp_kses(ai4seo_get_svg_tag("gear", '', "ai4seo-spinning-icon"));
                    echo " ";
                    echo esc_html__("AI for SEO is currently analyzing your pages and media files. Please wait.", "ai-for-seo");
                echo "</div>";

                if (!$ai4seo_heavy_db_operations_disabled && $ai4seo_did_run_post_table_analysis) {
                    echo "<div id='ai4seo-no-dashboard-refresh-delay'></div>";
                }
            }

            // refresh performance analysis button
            if (!$ai4seo_heavy_db_operations_disabled && $ai4seo_posts_table_analysis_state === 'completed') {
                echo "<div class='ai4seo-top-right-refresh-button-wrapper'>";
                    ai4seo_echo_wp_kses(ai4seo_get_small_button_tag("#", "rotate", __("Refresh", "ai-for-seo"), "", "ai4seo_refresh_dashboard_statistics(this); return false;"));
                echo "</div>";
            }

            // default values
            $ai4seo_chart_values = [
                'done' => ['value' => 0, 'color' => '#00aa00'], // Green
                'processing' => ['value' => 0, 'color' => '#007bff'], // Blue
                'missing' => ['value' => 0, 'color' => '#dddddd'], // gray
                'failed' => ['value' => 0, 'color' => '#dc3545'], // Red
            ];

            foreach ($ai4seo_all_supported_post_types as $ai4seo_this_post_type) {
                $ai4seo_this_original_post_type = $ai4seo_this_post_type;
                $ai4seo_this_num_finished_post_ids = $ai4seo_num_finished_posts_by_post_type[$ai4seo_this_post_type] ?? 0;
                $ai4seo_this_num_failed_post_ids = $ai4seo_num_failed_posts_by_post_type[$ai4seo_this_post_type] ?? 0;
                $ai4seo_this_num_pending_post_ids = $ai4seo_num_pending_posts_by_post_type[$ai4seo_this_post_type] ?? 0;
                $ai4seo_this_num_processing_post_ids = $ai4seo_num_processing_posts_by_post_type[$ai4seo_this_post_type] ?? 0;
                $ai4seo_this_num_missing_post_ids = $ai4seo_num_missing_posts_by_post_type[$ai4seo_this_post_type] ?? 0;

                if ($ai4seo_this_num_failed_post_ids > 0) {
                    if ($ai4seo_this_original_post_type === "attachment") {
                        $ai4seo_retry_all_failed_attachment_attributes_generations_link_tag = ai4seo_get_small_button_tag(
                            "#",
                            "rotate",
                            $ai4seo_retry_all_failed_attachment_attributes_generations_link_label,
                            "ai4seo-ignore-during-dashboard-refresh",
                            "ai4seo_retry_all_failed_attachment_attributes(this); return false;"
                        );
                    } else if (!isset($ai4seo_retry_all_failed_metadata_button_tags[$ai4seo_this_original_post_type])) {
                        $ai4seo_retry_all_failed_metadata_button_tags[$ai4seo_this_original_post_type] = ai4seo_get_small_button_tag(
                            "#",
                            "rotate",
                            sprintf(
                                $ai4seo_retry_all_failed_metadata_generations_link_label,
                                ai4seo_get_post_type_translation($ai4seo_this_original_post_type, true)
                            ),
                            "ai4seo-ignore-during-dashboard-refresh",
                            "ai4seo_retry_all_failed_metadata(this, '" . esc_js($ai4seo_this_original_post_type) . "'); return false;"
                        );
                    }
                }

                //workaround when cron job is not processing -> set pending and processing to 0
                if ($ai4seo_bulk_generation_status != "processing") {
                    $ai4seo_this_num_pending_post_ids = 0;
                    $ai4seo_this_num_processing_post_ids = 0;
                }

                // remove failed, pending and failed from missing
                $ai4seo_this_num_missing_post_ids -= $ai4seo_this_num_failed_post_ids;
                $ai4seo_this_num_missing_post_ids -= $ai4seo_this_num_pending_post_ids;
                $ai4seo_this_num_missing_post_ids -= $ai4seo_this_num_processing_post_ids;

                if ($ai4seo_this_num_missing_post_ids < 0) {
                    $ai4seo_this_num_missing_post_ids = 0;
                }

                if (in_array($ai4seo_this_post_type, $ai4seo_active_bulk_generation_post_types)) {
                    $ai4seo_total_num_pending_posts += $ai4seo_this_num_missing_post_ids;
                    $ai4seo_total_num_pending_posts += $ai4seo_this_num_pending_post_ids;
                    $ai4seo_total_num_pending_posts += $ai4seo_this_num_processing_post_ids;
                }

                # todo: separate pending and processing posts
                # workaround: add $ai4seo_this_num_processing_post_ids to $ai4seo_this_num_pending_post_ids until we can better
                # separate them in the future
                $ai4seo_this_num_pending_post_ids += $ai4seo_this_num_processing_post_ids;

                $ai4seo_chart_values = [
                    'done' => ['value' => $ai4seo_this_num_finished_post_ids, 'color' => '#00aa00'], // Green
                    'processing' => ['value' => $ai4seo_this_num_pending_post_ids, 'color' => '#007bff'], // Blue
                    'missing' => ['value' => $ai4seo_this_num_missing_post_ids, 'color' => '#dddddd'], // gray
                    'failed' => ['value' => $ai4seo_this_num_failed_post_ids, 'color' => '#dc3545'], // Red
                ];

                // get total value, and continue if it is 0
                $ai4seo_total_value = array_sum(array_column($ai4seo_chart_values, "value"));

                if ($ai4seo_total_value == 0) {
                    continue;
                }

                // attachment -> media workaround
                if ($ai4seo_this_post_type == "attachment") {
                    $ai4seo_this_post_type = "media";
                }

                $ai4seo_supported_post_type_label = ai4seo_get_dashicon_tag_for_navigation($ai4seo_this_post_type);
                $ai4seo_supported_post_type_label .= ucfirst(ai4seo_get_post_type_translation($ai4seo_this_post_type, true));

                ai4seo_echo_half_donut_chart_with_headline_and_percentage($ai4seo_supported_post_type_label, $ai4seo_chart_values, $ai4seo_this_num_finished_post_ids, $ai4seo_total_value, $ai4seo_posts_table_analysis_state, $ai4seo_this_post_type);
            }

            echo "<div class='ai4seo-chart-legend-container'>";
                ai4seo_echo_chart_legend($ai4seo_chart_values);
            echo "</div>";

            // clear both
            echo "<div class='ai4seo-clear-both' style='margin-top: 2rem;'></div>";

            if ($ai4seo_retry_all_failed_metadata_button_tags || $ai4seo_retry_all_failed_attachment_attributes_generations_link_tag) {
                echo "<div class='ai4seo-buttons-wrapper ai4seo-dashboard-retry-all-failed-wrapper'>";

                    if ($ai4seo_retry_all_failed_metadata_button_tags) {
                        foreach ($ai4seo_retry_all_failed_metadata_button_tags as $ai4seo_this_retry_button_tag) {
                            ai4seo_echo_wp_kses($ai4seo_this_retry_button_tag);
                        }
                    }

                    if ($ai4seo_retry_all_failed_attachment_attributes_generations_link_tag) {
                        ai4seo_echo_wp_kses($ai4seo_retry_all_failed_attachment_attributes_generations_link_tag);
                    }

                echo "</div>";
            }

        echo "</div>";

    }

    // force line break
    //echo "<div class='ai4seo-gap-zero'></div>";


    // === CREDITS ========================================================================== \\

    // calculate the percentage we are able to generate metadata for base on the current credits balance and the required credits per entry ($ai4seo_total_num_missing_posts * $ai4seo_min_credits_balance)
    if (isset($ai4seo_total_num_pending_posts) && $ai4seo_total_num_pending_posts) {
        $ai4seo_credits_percentage = min(100, $ai4seo_current_credits_balance
            ? round($ai4seo_current_credits_balance / max(1, ($ai4seo_total_num_pending_posts * $ai4seo_max_credits_cost_per_generation)) * 100) : 0);
    } else {
        $ai4seo_credits_percentage = 100;
    }

    echo "<div class='card ai4seo-card ai4seo-centered-card' style='min-height: 475px;'>";

        // refresh credits balance button
        echo "<div class='ai4seo-top-right-refresh-button-wrapper'>";
            ai4seo_echo_wp_kses(ai4seo_get_small_button_tag("#", "rotate", __("Refresh", "ai-for-seo"), "", "ai4seo_refresh_robhub_account(this); return false;"));
        echo "</div>";

        // credits balance
        echo "<div class='ai4seo-credits-container'>";
            echo "<h4>";
                echo esc_html__("Credits", "ai-for-seo");
            echo "</h4>";

            echo "<div class='ai4seo-credits-number'>";
                if ($ai4seo_is_robhub_account_synced) {
                    echo esc_html($ai4seo_current_credits_balance);
                } else {
                    echo "<span class='ai4seo-red-message'>";
                        ai4seo_echo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation", esc_html__("Failed to verify your license data. Please check your account settings.", "ai-for-seo"), "ai4seo-red-icon"));
                        echo esc_html__("N/A", "ai-for-seo");
                    echo "</span>";
                }
            echo "</div>";
        echo "</div>";

        // next free credits container
        if ($ai4seo_current_credits_balance < $ai4seo_free_plan_credits_amount) {
            echo "<div class='ai4seo-next-free-credits-container'>";
                ai4seo_echo_wp_kses(sprintf(
                    __('Next <span class="ai4seo-credits-usage-badge">+%1$s Credits</span> in <strong>%2$s</strong>', 'ai-for-seo'),
                    esc_html(AI4SEO_DAILY_FREE_CREDITS_AMOUNT),
                    "<span class='ai4seo-countdown' data-time-left='" . esc_attr($ai4seo_next_free_credits_seconds_left) . "' data-trigger='ai4seo_reload_page'>" . esc_html(ai4seo_format_seconds_to_hhmmss_or_days_hhmmss($ai4seo_next_free_credits_seconds_left)) . "</span>",
                    esc_html($ai4seo_free_plan_credits_amount)
                ));

                $ai4seo_free_credits_tooltip = sprintf(
                    __("We provide you with <strong>%s free Credits each day</strong> if your balance falls below %s Credits. Simply keep using the plugin to receive them automatically.", "ai-for-seo"),
                    esc_html(AI4SEO_DAILY_FREE_CREDITS_AMOUNT),
                    esc_html($ai4seo_free_plan_credits_amount),
                );
                ai4seo_echo_wp_kses(ai4seo_get_icon_with_tooltip_tag($ai4seo_free_credits_tooltip));
            echo "</div>";
        }

        // costs breakdown
        echo "<div class='ai4seo-credits-generation-costs-info'>";
            ai4seo_echo_cost_breakdown_section($ai4seo_credits_percentage);
        echo "</div>";

        echo "<div class='ai4seo-how-to-get-credits-container'>";

            if ($ai4seo_is_robhub_account_synced) {
                // current discount
                ai4seo_echo_current_discount();

                // Turn Buy credits button
                echo "<div class='ai4seo-buy-credits-button-container'>";
                    ai4seo_echo_wp_kses(
                        ai4seo_get_button_text_link_tag("#", "arrow-up-right-from-square", esc_html__("Get more Credits", "ai-for-seo"),
                            ($ai4seo_current_credits_balance < AI4SEO_BLUE_GET_MORE_CREDITS_BUTTON_THRESHOLD ? "ai4seo-success-button" : ""),
                            "ai4seo_open_get_more_credits_modal();")
                    );
                echo "</div>";

            } else {
                // go to Account Settings
                ai4seo_echo_wp_kses(
                    ai4seo_get_button_text_link_tag(ai4seo_get_subpage_url("account"), "key", esc_html__("Account Settings", "ai-for-seo"), "ai4seo-success-button")
                );
            }
        echo "</div>";

    echo "</div>";


    // ___________________________________________________________________________________________ \\
    // === BLACK FRIDAY OFFER NOTICE ============================================================= \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    /*if ($ai4seo_user_is_on_free_plan && time() <= 1733612399) {
        echo "<div class='card ai4seo-card' style='border-color:#037;background-color:#ace4f5;'>";
        echo "<h2 style='margin-bottom:5px;color:#037;font-weight:bold;font-size:2.5em;text-align:center;'>";
        echo "BLACK-FRIDAY-SALE:";
        echo "</h2>";

        echo "<h3 style='color:#b40a0a;text-decoration:underline;text-align:center;font-size:1.8em;font-weight:bold;'>";
        echo "30% LIFETIME DISCOUNT";
        echo "</h3>";

        echo "<p style='margin-top:0;font-size:larger;text-align:center;line-height:2em;'>";
        echo "By using the coupon-code ";
        echo "<span style='background-color:#003377;color:#fff;padding:2px 8px;border-radius:5px;'>";
        echo "BLACKFRIDAY";
        echo "</span>";
        echo " you will get a <span style='font-weight: bold; font-size: larger;'>30% lifetime discount</span> on all plans.";
        echo "</p>";

        echo "<p style='text-align:center;'>";
        ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag($ai4seo_purchase_plan_url, "circle-up", esc_html__("Secure This Offer", "ai-for-seo"), "ai4seo-success-button"));
        echo "</p>";
        echo "</div>";
    }*/


    // ___________________________________________________________________________________________ \\
    // === SEO AUTOPILOT ========================================================================= \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    if ($ai4seo_is_robhub_account_synced) {
        $ai4seo_additional_sub_status_text = "<br>";

        // add last cron job call $ai4seo_last_bulk_generation_update_time in readable format
        if ($ai4seo_last_bulk_generation_update_time) {
            $ai4seo_additional_sub_status_text .= " " . sprintf(
                    esc_html__("Last execution was on %s.", "ai-for-seo"),
                    esc_html(ai4seo_format_unix_timestamp($ai4seo_last_bulk_generation_update_time, 'auto-miss'))
                );
        } else {
            $ai4seo_additional_sub_status_text .= " " . esc_html__("The SEO Autopilot has never been executed yet.", "ai-for-seo");
        }

        // find proper task scheduler status text
        if ($ai4seo_next_cron_job_call_diff >= 10) {
            $ai4seo_next_cron_job_call_diff_minutes = ceil($ai4seo_next_cron_job_call_diff / 60);
            $ai4seo_additional_sub_status_text .= " " . sprintf(
                esc_html__("It should continue in less than %s.", "ai-for-seo"),
                sprintf(
                    _n("%s minute", "%s minutes", $ai4seo_next_cron_job_call_diff_minutes, "ai-for-seo"),
                    $ai4seo_next_cron_job_call_diff_minutes
                ),
            );
        } else {
            $ai4seo_additional_sub_status_text .= " " . esc_html__("It should continue in a few moments.", "ai-for-seo");
        }

        $ai4seo_additional_sub_status_text .= " " . esc_html__("This page will refresh automatically.", "ai-for-seo");

        // execute sooner link
        if ($ai4seo_next_cron_job_call_diff >= 70) {
            $ai4seo_additional_sub_status_text .= " " . $ai4seo_execute_sooner_button;
        } else {
            $ai4seo_additional_sub_status_text .= " " . $ai4seo_refresh_button;
        }

        // CARD
        echo "<div class='card ai4seo-card ai4seo-centered-card' style='min-height: 475px;'>";
            echo "<h4>" . esc_html__("SEO Autopilot (Bulk Generation)", "ai-for-seo") . "</h4>";

            echo "<div class='ai4seo-bulk-generation-status-container'>";
                if (!$ai4seo_is_any_bulk_generation_enabled) {
                    echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is deactivated", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-inactive-logo'>";

                    echo "<div class='ai4seo-bulk-generation-status-text'>";
                        echo esc_html__("Off", "ai-for-seo");
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                        echo esc_html__("SEO Autopilot has not been set up yet.", "ai-for-seo");
                    echo "</div>";
                } else if ($ai4seo_posts_table_analysis_state !== 'completed') {
                    echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is active, but it is currently waiting for the analysis tasks to finish.", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-active-logo'>";

                    echo "<div class='ai4seo-bulk-generation-status-text'>";
                    echo esc_html__("Analyzing...", "ai-for-seo");
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                    echo esc_html__("AI for SEO is analyzing your pages and media files. Please wait until the analysis is complete.", "ai-for-seo");
                    echo "</div>";
                } else if (isset($ai4seo_total_num_pending_posts) && $ai4seo_total_num_pending_posts == 0) {
                    echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is active but idling", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-active-logo'>";

                    echo "<div class='ai4seo-bulk-generation-status-text'>";
                        echo esc_html__("All done & idle", "ai-for-seo");
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                        echo esc_html__("Waiting for new entries to process.", "ai-for-seo");
                    echo "</div>";
                } else if ($ai4seo_insufficient_credits_balance) {
                    echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is active but no credits available", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-active-logo'>";

                    // triangle-exclamation on the top right corner
                    echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
                        ai4seo_echo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation"));
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-text'>";
                        echo esc_html__("Insufficient Credits", "ai-for-seo");
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                        echo esc_html__("Not enough Credits available. Please get more Credits.", "ai-for-seo");
                    echo "</div>";

                } else if ($ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago && $ai4seo_last_bulk_generation_run_was_long_ago) {
                    echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is active but slow", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-active-logo'>";

                    // triangle-exclamation in the top right corner
                    echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
                        ai4seo_echo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation"));
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-text'>";
                        echo esc_html__("Pending...", "ai-for-seo");
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                        echo esc_html__("The last SEO Autopilot execution was longer ago than expected, which may indicate an issue with your cron job configuration. Please check your cron job settings to ensure consistent execution.", "ai-for-seo");
                        if ($ai4seo_additional_sub_status_text) {
                            echo " ";
                            ai4seo_echo_wp_kses($ai4seo_additional_sub_status_text);
                        }
                    echo "</div>";
                } else if (in_array($ai4seo_bulk_generation_status, ["initiating", "processing", "scheduled", "finished"]) && $ai4seo_last_bulk_generation_update_time && !$ai4seo_last_bulk_generation_run_was_longer_ago_than_bulk_generation_duration) {
                    echo "<div class='ai4seo-bulk-generation-status-animated-logo-container'>";
                        echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("512x512-animated")) . "' class='ai4seo-bulk-generation-status-animated-logo-pulse'>";
                        echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("512x512-animated")) . "' alt='" . esc_attr__("SEO Autopilot is processing", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-animated-logo'>";
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-text' style='color: #444;'>";
                        echo esc_html__("Processing", "ai-for-seo");
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                        echo esc_html__("Please wait and check the \"Recent Activity\" section for results.", "ai-for-seo");
                        echo " " . esc_html__("This page will refresh automatically.", "ai-for-seo");
                    echo "</div>";
                } else if ($ai4seo_last_bulk_generation_update_time && ($ai4seo_bulk_generation_status == "idle" || (in_array($ai4seo_bulk_generation_status, ["initiating", "processing", "finished", "scheduled"]) && $ai4seo_last_bulk_generation_run_was_longer_ago_than_bulk_generation_duration))) {
                    // triangle-exclamation in the top right corner
                    #echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
                    #    ai4seo_echo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation"));
                    #echo "</div>";

                    echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is active but not generating", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-active-logo'>";

                    echo "<div class='ai4seo-bulk-generation-status-text' style='color: #444;'>";
                        echo esc_html__("Pending...", "ai-for-seo");
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                        echo esc_html__("The SEO Autopilot is active and currently waiting for the next scheduled execution in order to process the pending entries.", "ai-for-seo");

                    if ($ai4seo_additional_sub_status_text) {
                        echo " ";
                        ai4seo_echo_wp_kses($ai4seo_additional_sub_status_text);
                    }
                    echo "</div>";

                // something went wrong, if we wait at least x seconds after setup without any activity
                } else if (!$ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago) {
                    // waiting for task scheduler to start
                    echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is waiting for task scheduler to start", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-active-logo'>";

                    echo "<div class='ai4seo-bulk-generation-status-text'>";
                    echo esc_html__("Initializing...", "ai-for-seo");
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                    echo esc_html__("Waiting for task scheduler to start.", "ai-for-seo");

                    if ($ai4seo_additional_sub_status_text) {
                        echo " ";
                        ai4seo_echo_wp_kses($ai4seo_additional_sub_status_text);
                    }
                    echo "</div>";
                } else {
                    echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is stuck", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-inactive-logo'>";

                    // triangle-exclamation in the top right corner
                    echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
                    ai4seo_echo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation"));
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-text'>";
                    echo esc_html__("Error", "ai-for-seo");
                    echo "</div>";

                    echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                    echo esc_html__("Something went wrong. Please try again. If the issue continues, review your cron job configuration and check your PHP CLI error logs for details.", "ai-for-seo");

                    if ($ai4seo_additional_sub_status_text) {
                        echo " ";
                        ai4seo_echo_wp_kses($ai4seo_additional_sub_status_text);
                    }
                    echo "</div>";
                }

            echo "</div>";

            // Bulk Generation Buttons
            echo "<div class='ai4seo-bulk-generation-button-container'>";
                echo "<div class='ai4seo-buttons-wrapper'>";
                    if ($ai4seo_is_any_bulk_generation_enabled) {
                        // stop SEO Autopilot
                        ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag("#", "stop-circle", esc_html__("Stop SEO Autopilot", "ai-for-seo"), "ai4seo-abort-button", "ai4seo_stop_bulk_generation(this)"));
                    }
                    ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag("#", "arrow-up-right-from-square", esc_html__("Set up SEO Autopilot", "ai-for-seo"), "", "ai4seo_open_modal_from_schema(\"seo-autopilot\", {modal_size: \"small\"});"));
                echo "</div>";
            echo "</div>";

        echo "</div>";
    }


    // === Recent Activity ========================================================================== \\

    $ai4seo_latest_activity = ai4seo_get_option(AI4SEO_LATEST_ACTIVITY_OPTION_NAME, array());

    echo "<div class='card ai4seo-card' style='min-height: 475px;'>";
        echo "<h4>" . esc_html__("Recent Activity", "ai-for-seo") . "</h4>";

        if (!$ai4seo_latest_activity) {
            echo "<p style='font-style: italic; width: 100%; text-align: center;'>" . esc_html__("No recent activity. Try to generate metadata or media attributes first.", "ai-for-seo") . "</p>";
        } else {
            echo "<div class='ai4seo-latest-activity-container'>";
            foreach ($ai4seo_latest_activity AS $ai4seo_this_latest_activity_entry) {
                $ai4seo_this_plugin_page_icon = ai4seo_get_dashicon_tag_for_navigation($ai4seo_this_latest_activity_entry["post_type"] ?? "");

                echo "<div class='ai4seo-latest-activity-item'>";
                    echo "<div class='ai4seo-latest-activity-item-icon'>";
                        ai4seo_echo_wp_kses($ai4seo_this_plugin_page_icon);
                    echo "</div>";

                    echo "<div class='ai4seo-latest-activity-item-text'>";

                        echo "<strong>";
                            if ($ai4seo_this_latest_activity_entry["timestamp"] ?? 0) {
                                echo esc_html(ai4seo_format_unix_timestamp($ai4seo_this_latest_activity_entry["timestamp"] ?? 0)) . " - ";
                            }

                            // title
                            echo esc_html($ai4seo_this_latest_activity_entry["title"] ?? "");
                        echo "</strong>";

                        echo "<br>";

                        // status
                        $ai4seo_this_latest_activity_entry_is_success = ($ai4seo_this_latest_activity_entry["status"] ?? "error") == "success";

                        if ($ai4seo_this_latest_activity_entry_is_success) {
                            echo "<div class='ai4seo-green-message'>";
                            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("circle-check", esc_html__("Success", "ai-for-seo"), "ai4seo-gray-icon"));
                            echo " ";
                        } else {
                            echo "<div class='ai4seo-red-message'>";
                            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("circle-xmark", esc_html__("Error", "ai-for-seo"), "ai4seo-red-icon"));
                            echo " ";
                        }

                            // if details given, output them else the action
                            if (isset($ai4seo_this_latest_activity_entry["details"]) && $ai4seo_this_latest_activity_entry["details"]) {
                                echo esc_html(ai4seo_mb_substr($ai4seo_this_latest_activity_entry["details"], 0, 160));
                            } else {
                                // metadata-manually-generated", "metadata-bulk-generated", "attachment-attributes-manually-generated", "attachment-attributes-bulk-generated
                                switch ($ai4seo_this_latest_activity_entry["action"]) {
                                    case "metadata-manually-generated":
                                        echo esc_html__("Metadata manually generated", "ai-for-seo");
                                        break;
                                    case "metadata-bulk-generated":
                                        echo esc_html__("Metadata generated (by SEO Autopilot)", "ai-for-seo");
                                        break;
                                    case "attachment-attributes-manually-generated":
                                        echo esc_html__("Media attributes manually generated", "ai-for-seo");
                                        break;
                                    case "attachment-attributes-bulk-generated":
                                        echo esc_html__("Media attributes generated (by SEO Autopilot)", "ai-for-seo");
                                        break;
                                }
                            }

                            /*if (!$ai4seo_this_latest_activity_entry_is_success) {
                                echo ". " . esc_html__("Please click the edit button to try again and see the full error message.", "ai-for-seo");
                            }*/

                        echo "</div>";

                    echo "</div>";

                    if ($ai4seo_this_latest_activity_entry["cost"] ?? 0) {
                        echo "<div class='ai4seo-latest-activity-item-costs'><div class='ai4seo-credits-usage-badge'>";
                            echo esc_html($ai4seo_this_latest_activity_entry["cost"]) . " " . esc_html__("Cr.", "ai-for-seo");
                        echo "</div></div>";
                    }

                    echo "<div class='ai4seo-latest-activity-item-buttons'>";
                        // see post / media preview
                        if (isset($ai4seo_this_latest_activity_entry["url"]) && $ai4seo_this_latest_activity_entry["url"]) {
                            ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag($ai4seo_this_latest_activity_entry["url"], "eye", "", "", "", "_blank"));
                        }

                        if (in_array(($ai4seo_this_latest_activity_entry["post_type"] ?? ""), $ai4seo_supported_attachment_post_types)) {
                            ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag("#", "pen-to-square", "", "", "ai4seo_open_attachment_attributes_editor_modal(" . esc_js($ai4seo_this_latest_activity_entry["post_id"]) . ");"));
                        } else {
                            if (isset($ai4seo_this_latest_activity_entry["url"]) && $ai4seo_this_latest_activity_entry["url"]) {
                                # todo: add source code button using ajax modal, and then only showing the header of the page
                                #ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag("#", "code", "", "", "ai4seo_open_view_source(\"" . esc_url($ai4seo_this_latest_activity_entry["url"]) . "\")"));
                            }
                            ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag("#", "pen-to-square", "", "", "ai4seo_open_metadata_editor_modal(" . esc_js($ai4seo_this_latest_activity_entry["post_id"]) . ");"));
                        }
                    echo "</div>";
                echo "</div>";
            }
            echo "</div>";
        }

        # todo: add show full log button

    echo "</div>";


    // === Personal SEO Expert ========================================================================== \\

    echo "<div class='card ai4seo-card ai4seo-ignore-during-dashboard-refresh ai4seo-dashboard-expert-card'>";
        echo "<h4 style='cursor: pointer; margin-bottom: 0;' onclick='ai4seo_toggle_visibility(jQuery(this).next(), jQuery(this).find(\".ai4seo-caret-down\"), jQuery(this).find(\".ai4seo-caret-up\"), 200);'>";
            echo esc_html__("Your Personal SEO Expert", "ai-for-seo");
            echo "<div class='ai4seo-caret-down'>";
                ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-down"));
            echo "</div>";
            echo "<div class='ai4seo-caret-up' style='display: none;'>";
                ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-up"));
            echo "</div>";
        echo "</h4>";

        # todo: make this dynamic based on real status and availability
        echo "<div class='ai4seo-dashboard-expert-card-inner'>";
            echo "<div class='ai4seo-dashboard-expert-avatar' aria-hidden='true'>";
                echo "<img src='" . esc_url(ai4seo_get_assets_images_url("andre-erbis-at-space-codes.webp")) . "' alt='" . esc_attr__("SEO Expert Avatar", "ai-for-seo") . "'>";
            echo "</div>";

            echo "<div class='ai4seo-dashboard-expert-content'>";
                echo "<div class='ai4seo-dashboard-expert-name'>";
                    echo esc_html__("André Erbis", "ai-for-seo");

                    // online status badge between 2:00 UTC and 13:00 UTC
                    if ($ai4seo_current_utc_hour >= 2 && $ai4seo_current_utc_hour < 13) {
                        echo "<div class='ai4seo-online-status-badge' title='" . esc_attr__("Online", "ai-for-seo") . "'>";
                            echo "<div class='ai4seo-online-status-icon'></div>";
                            echo esc_html__("Online", "ai-for-seo");
                        echo "</div>";
                    }
                echo "</div>";

                echo "<p>" . esc_html__("Hi, I'm your personal SEO expert!", "ai-for-seo") . "</p>";
                echo "<p>" . esc_html__("Whether you need help setting up the plugin, planning your SEO strategy, or want to discuss a custom approach, the AI for SEO team and I have you covered.", "ai-for-seo") . "</p>";
                echo "<p>";
                    ai4seo_echo_wp_kses(__("Together, we’ll grow your visibility and turn your search goals into real results. Some users report an <strong>increase</strong> in reach and clicks of over <strong>1900%</strong>.", "ai-for-seo"));
                echo "</p>";
                echo "<p>";
                    ai4seo_echo_wp_kses(__("Ready to take your SEO to the next level? <strong>Let’s connect!</strong>", "ai-for-seo"));
                echo "</p>";

                ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag("https://aiforseo.ai/contact", "arrow-up-right-from-square", esc_html__("Contact SEO Expert now", "ai-for-seo"), "", "", "_blank"));
            echo "</div>";
        echo "</div>";
    echo "</div>";


    // === Money Back Guarantee ========================================================================== \\

    echo "<div class='card ai4seo-card ai4seo-ignore-during-dashboard-refresh'>";
        echo "<h4 style='cursor: pointer; margin-bottom: 0;' onclick='ai4seo_toggle_visibility(jQuery(this).next(), jQuery(this).find(\".ai4seo-caret-down\"), jQuery(this).find(\".ai4seo-caret-up\"), 200);'>";
            echo esc_html__("Guarantee", "ai-for-seo");
            echo "<div class='ai4seo-caret-down'>";
                ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-down"));
            echo "</div>";
            echo "<div class='ai4seo-caret-up' style='display: none;'>";
                ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-up"));
            echo "</div>";
        echo "</h4>";

        echo "<div style='display: none; margin-top: 1.5rem;'>";
            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("handshake", __("Guarantee", "ai-for-seo"), "ai4seo-handshake-icon"));
            echo " ";
            # todo: hide if not applicable
            if (true) {
                ai4seo_output_money_back_guarantee_notice();
            }
        echo "</div>";
    echo "</div>";


    // === Recent Plugin Updates ========================================================================== \\

    echo "<div class='card ai4seo-card ai4seo-ignore-during-dashboard-refresh' id='ai4seo_recent_plugin_updates'>";
        echo "<h4 style='cursor: pointer; margin-bottom: 0;' onclick='ai4seo_toggle_visibility(jQuery(this).next(), jQuery(this).find(\".ai4seo-caret-down\"), jQuery(this).find(\".ai4seo-caret-up\"), 200);'>";
            echo esc_html__("Recent Plugin Updates", "ai-for-seo");
            echo "<div class='ai4seo-caret-down'>";
                ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-down"));
            echo "</div>";
            echo "<div class='ai4seo-caret-up' style='display: none;'>";
                ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-up"));
            echo "</div>";
        echo "</h4>";

        echo "<div class='ai4seo-recent-plugin-updates-content' style='display: " . ($ai4seo_pre_open_recent_plugin_updates ? "inline-block" : "none") . ";'>";
            echo esc_html__("We update the plugin regularly to improve its performance and add new features. Please check the changelog for more information.", "ai-for-seo") . "<br>";

            // Generate updates dynamically from const parameter
            foreach ($ai4seo_change_log as $ai4seo_this_plugin_update_index => $this_plugin_update_details) {
                $ai4seo_this_is_first_plugin_update = ($ai4seo_this_plugin_update_index === 0);
                $ai4seo_this_changes_count = count($this_plugin_update_details['updates']);
                $ai4seo_this_is_important_update = $this_plugin_update_details['important'] ?? false;

                // skip not important updates after the 5th entry
                if ($ai4seo_this_plugin_update_index >= 5 && !$ai4seo_this_is_important_update) {
                    continue;
                }
                
                // Header with date, version, and collapsible functionality
                echo "<div class='ai4seo-recent-plugin-updates-title" . ($ai4seo_this_is_important_update ? " ai4seo-recent-plugin-updates-important-title" : "") . "' onclick='ai4seo_toggle_visibility(jQuery(this).next(), jQuery(this).find(\".ai4seo-caret-down\"), jQuery(this).find(\".ai4seo-caret-up\"), 200);'>";
                    // title
                    echo "➢ ";

                    echo "<div class='ai4seo-bubble' style='margin-left: 10px;'>" . esc_html($this_plugin_update_details['version']) . "</div> ";

                    echo esc_html($this_plugin_update_details['date'] . " ");

                    // Changes count
                    echo "<span class='ai4seo-changes-count'>(" . sprintf(
                        /* translators: %d = number of changes */
                            _n('%d change', '%d changes', $ai4seo_this_changes_count, 'ai-for-seo'),
                            esc_html($ai4seo_this_changes_count)
                        ) . ")</span>";

                    // Caret icons - first entry expanded, others collapsed
                    if ($ai4seo_this_is_first_plugin_update) {
                        echo "<div class='ai4seo-caret-down' style='display: none;'>";
                            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-down"));
                        echo "</div>";
                        echo "<div class='ai4seo-caret-up'>";
                            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-up"));
                        echo "</div>";
                    } else {
                        echo "<div class='ai4seo-caret-down'>";
                            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-down"));
                        echo "</div>";
                        echo "<div class='ai4seo-caret-up' style='display: none;'>";
                            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-up"));
                        echo "</div>";
                    }
                    
                echo "</div>";
                
                // Content - first entry expanded, others collapsed
                echo "<div class='ai4seo-changelog-entry-content'" . (!$ai4seo_this_is_first_plugin_update ? " style='display: none;'" : "") . ">";
                    echo '<ul>';
                    foreach ($this_plugin_update_details['updates'] as $update_item) {
                        echo '<li>' . wp_kses_post($update_item) . '</li>';
                    }
                    echo '</ul>';
                echo "</div>";
            }

        echo "</div>";
    echo "</div>";


    // === Ask for feedback ========================================================================== \\

    echo "<div class='card ai4seo-card ai4seo-ignore-during-dashboard-refresh'>";
        echo "<h4 style='cursor: pointer; margin-bottom: 0;' onclick='ai4seo_toggle_visibility(jQuery(this).next(), jQuery(this).find(\".ai4seo-caret-down\"), jQuery(this).find(\".ai4seo-caret-up\"), 200);'>";
            echo esc_html__("Support & Feedback", "ai-for-seo");
            echo "<div class='ai4seo-caret-down'>";
                ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-down"));
            echo "</div>";
            echo "<div class='ai4seo-caret-up' style='display: none;'>";
                ai4seo_echo_wp_kses(ai4seo_get_svg_tag("caret-up"));
            echo "</div>";
        echo "</h4>";

        echo "<div style='display: none; margin-top: 1.5rem;'>";

            // HELP SECTION
            // icon
            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("circle-question", __("Help section", "ai-for-seo"), "ai4seo-big-paragraph-icon"));
            echo " ";

            ai4seo_echo_wp_kses(sprintf(
                /* translators: %s is a clickable email address */
                __("Check our <a href='%s'>help section</a> for a detailed <a href='%s'>getting started guide</a>, our organized <a href='%s'>F.A.Q</a> or other <a href='%s'>useful links</a>.", "ai-for-seo"),
                esc_url(ai4seo_get_subpage_url("help")),
                esc_url(ai4seo_get_subpage_url("help") . "#ai4seo-getting-started-section"),
                esc_url(ai4seo_get_subpage_url("help") . "#ai4seo-faq-section"),
                esc_url(ai4seo_get_subpage_url("help") . "#ai4seo-links-section")
            ));

            echo "<br><br>";

            // button to help section
            ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag(esc_url(ai4seo_get_subpage_url("help")), "arrow-up-right-from-square", esc_html__("Go to our help section", "ai-for-seo")));

            echo "<br><br><br>";

            // CONTACT US
            // icon
            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("envelope", __("Contact us", "ai-for-seo"), "ai4seo-big-paragraph-icon"));
            echo " ";

            ai4seo_echo_wp_kses(sprintf(
                /* translators: %s is a clickable email address */
                __("Missing a feature, need assistance, or looking for a quote?", "ai-for-seo") . " " .
                __("Please <a href='%s' target='blank'>contact us</a>. We offer support in any language.", "ai-for-seo"),
                esc_url(AI4SEO_OFFICIAL_CONTACT_URL)
            ));

            echo "<br><br>";

            // button to contact us
            ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag(esc_url(AI4SEO_OFFICIAL_CONTACT_URL), "arrow-up-right-from-square", esc_html__("Contact us", "ai-for-seo"), "", "", "_blank"));

            echo "<br><br><br>";

            // RATE US
            // icon
            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("star", __("Rate us", "ai-for-seo"), "ai4seo-big-paragraph-icon"));
            echo " ";

            //  like our plugin rate us at AI4SEO_OFFICIAL_WORDPRESS_ORG_PAGE
            ai4seo_echo_wp_kses(sprintf(
                __("Like our plugin and want to support us? Please <a href='%s' target='blank'>rate us</a> on WordPress.org. We appreciate your feedback!", "ai-for-seo"),
                esc_url(AI4SEO_OFFICIAL_RATE_US_URL)
            ));

            echo "<br><br>";

            // button to rate us
            ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag(esc_url(AI4SEO_OFFICIAL_RATE_US_URL), "arrow-up-right-from-square", esc_html__("Rate us", "ai-for-seo"), "", "", "_blank"));

        echo "</div>";
    echo "</div>";

echo "</div>";