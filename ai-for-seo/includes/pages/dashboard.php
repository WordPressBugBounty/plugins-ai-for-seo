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

// === REFRESH BUTTON ======================================================================== \\

// collect some admin links and buttons
$ai4seo_this_admin_tab_url = ai4seo_get_admin_url("dashboard");
$ai4seo_refresh_button = ai4seo_get_small_button_tag($ai4seo_this_admin_tab_url, "rotate", __("Refresh page", "ai-for-seo"));


// === EXECUTE BULK GENERATION SOONER ======================================================== \\

// check if the cron job should be executed sooner
if (isset($_GET["ai4seo-execute-cron-job-sooner"]) && $_GET["ai4seo-execute-cron-job-sooner"]) {
    ai4seo_inject_additional_cronjob_call(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
}

// execute cron job sooner link
$ai4seo_execute_sooner_text_link_url = ai4seo_get_admin_url("dashboard", array("ai4seo-execute-cron-job-sooner" => true));
$ai4seo_execute_sooner_button = ai4seo_get_small_button_tag($ai4seo_execute_sooner_text_link_url, "bolt", __("Execute sooner!", "ai-for-seo"));


// === CREDITS BALANCE ======================================================================= \\

// reset credits balance check
if (isset($_GET["ai4seo_refresh_credits_balance"])) {
    ai4seo_robhub_api()->reset_last_credit_balance_check();
}

$ai4seo_current_credits_balance = ai4seo_robhub_api()->get_credits_balance();
$ai4seo_insufficient_credits_balance = ($ai4seo_current_credits_balance < AI4SEO_MIN_CREDITS_BALANCE);


// === CHECK BULK GENERATION STATUS ========================================================== \\

$ai4seo_active_bulk_generation_post_types = ai4seo_get_setting(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES);
$ai4seo_is_any_bulk_generation_enabled = !empty($ai4seo_active_bulk_generation_post_types);
$ai4seo_bulk_generation_status = ai4seo_get_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_last_bulk_generation_update_time = ai4seo_get_cron_job_status_update_time(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_last_bulk_generation_run_was_long_ago = $ai4seo_last_bulk_generation_update_time && (time() - $ai4seo_last_bulk_generation_update_time > 30);
$ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago = ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago();
$ai4seo_next_cron_job_call = wp_next_scheduled(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_next_cron_job_call_diff = ($ai4seo_next_cron_job_call ? $ai4seo_next_cron_job_call - time() : 9999999);


// === DISCOUNTS ============================================================================= \\

$ai4seo_is_first_purchase_discount_available = (bool) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_IS_FIRST_PURCHASE_DISCOUNT_AVAILABLE);
$ai4seo_early_bird_discount_time_left = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_EARLY_BIRD_DISCOUNT_TIME_LEFT);


// === POST TYPES =========================================================================== \\

$ai4seo_supported_post_types = ai4seo_get_supported_post_types();
$ai4seo_supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

$ai4seo_all_supported_post_types = array_merge($ai4seo_supported_post_types, $ai4seo_supported_attachment_post_types);


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-cards-container'>";

    // === STATISTICS ============================================================================ \\

    $ai4seo_total_num_pending_posts = 0;
    $ai4seo_num_finished_posts_by_post_type = ai4seo_get_all_finished_posts_by_post_type();
    $ai4seo_num_failed_posts_by_post_type = ai4seo_get_all_failed_posts_by_post_type();
    $ai4seo_num_pending_posts_by_post_type = ai4seo_get_all_pending_posts_by_post_type();
    $ai4seo_num_processing_posts_by_post_type = ai4seo_get_all_processing_posts_by_post_type();
    $ai4seo_num_missing_posts_by_post_type = ai4seo_get_all_missing_posts_by_post_type();

    echo "<div class='card ai4seo-card ai4seo-fully-centered-card ai4seo-three-column-card' style='padding-bottom: 0;'>";

        // default values
        $ai4seo_chart_values = [
            'done' => ['value' => 0, 'color' => '#00aa00'], // Green
            'processing' => ['value' => 0, 'color' => '#007bff'], // Blue
            'missing' => ['value' => 0, 'color' => '#dddddd'], // gray
            'failed' => ['value' => 0, 'color' => '#dc3545'], // Red
        ];

        foreach ($ai4seo_all_supported_post_types AS $ai4seo_supported_post_type) {
            $ai4seo_this_num_finished_post_ids = $ai4seo_num_finished_posts_by_post_type[$ai4seo_supported_post_type] ?? 0;
            $ai4seo_this_num_failed_post_ids = $ai4seo_num_failed_posts_by_post_type[$ai4seo_supported_post_type] ?? 0;
            $ai4seo_this_num_pending_post_ids = $ai4seo_num_pending_posts_by_post_type[$ai4seo_supported_post_type] ?? 0;
            $ai4seo_this_num_processing_post_ids = $ai4seo_num_processing_posts_by_post_type[$ai4seo_supported_post_type] ?? 0;
            $ai4seo_this_num_missing_post_ids = $ai4seo_num_missing_posts_by_post_type[$ai4seo_supported_post_type] ?? 0;

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

            if (in_array($ai4seo_supported_post_type, $ai4seo_active_bulk_generation_post_types)) {
                $ai4seo_total_num_pending_posts += $ai4seo_this_num_missing_post_ids;
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
            if ($ai4seo_supported_post_type == "attachment") {
                $ai4seo_supported_post_type = "media files";
            }

            $ai4seo_supported_post_type_label = ai4seo_get_post_type_translation($ai4seo_supported_post_type, true);
            $ai4seo_supported_post_type_label = ucfirst($ai4seo_supported_post_type_label);

            ai4seo_echo_half_donut_chart_with_headline_and_percentage($ai4seo_supported_post_type_label, $ai4seo_chart_values, $ai4seo_this_num_finished_post_ids, $ai4seo_total_value);
        }

        echo "<div class='ai4seo-chart-legend-container'>";
            ai4seo_echo_chart_legend($ai4seo_chart_values);
        echo "</div>";

        // clear both
        echo "<div class='ai4seo-clear-both'></div>";

    echo "</div>";


    // === CREDITS ========================================================================== \\

    // calculate the percentage we are able to generate metadata for base on the current credits balance and the required credits per entry ($ai4seo_total_num_missing_posts * AI4SEO_MIN_CREDITS_BALANCE)
    if ($ai4seo_total_num_pending_posts) {
        $ai4seo_credits_percentage = min(100, $ai4seo_current_credits_balance
            ? round($ai4seo_current_credits_balance / ($ai4seo_total_num_pending_posts * AI4SEO_MIN_CREDITS_BALANCE) * 100) : 0);
    } else {
        $ai4seo_credits_percentage = 100;
    }

    echo "<div class='card ai4seo-card ai4seo-centered-card' style='min-height: 475px;'>";

        // refresh credits balance button
        # todo: replace with ajax based reload
        echo "<div class='ai4seo-refresh-credits-balance-button-wrapper'>";
            echo ai4seo_wp_kses(ai4seo_get_small_button_tag("#", "rotate", __("Refresh", "ai-for-seo"), "ai4seo-show-credits-used-info", "add_refresh_credits_balance_parameter_and_reload_page();"));
        echo "</div>";

        // credits balance
        echo "<div class='ai4seo-credits-container'>";
            echo "<h4>";
                echo esc_html__("Credits", "ai-for-seo");
            echo "</h4>";

            echo "<div class='ai4seo-credits-number'>";
                echo $ai4seo_current_credits_balance;
            echo "</div>";
        echo "</div>";

        // costs breakdown
        echo "<div class='ai4seo-credits-generation-costs-info'>";
            ai4seo_echo_cost_breakdown_section($ai4seo_credits_percentage);
        echo "</div>";

        // how to get credits section
        echo "<div class='ai4seo-how-to-get-credits-container'>";

            // discount available
            if ($ai4seo_is_first_purchase_discount_available || $ai4seo_early_bird_discount_time_left) {
                echo "<div class='ai4seo-red-bubble ai4seo-discount-available-message'>";
                if ($ai4seo_early_bird_discount_time_left) {
                    echo sprintf(
                        esc_html__("%s%% discount available (time left: %s)", "ai-for-seo"),
                        AI4SEO_EARLY_BIRD_DISCOUNT,
                        "<span class='ai4seo-countdown' data-trigger='add_refresh_credits_balance_parameter_and_reload_page'>" . esc_html(ai4seo_format_seconds_to_hhmmss($ai4seo_early_bird_discount_time_left)) . "</span>"
                    );
                } else {
                    echo sprintf(
                        esc_html__("%s%% discount available", "ai-for-seo"),
                        AI4SEO_FIRST_PURCHASE_DISCOUNT
                    );
                }
                echo "</div>";
            } else {
                echo "<div class='ai4seo-gap'>";
                echo "</div>";
            }

            // Turn Buy credits button
            echo "<div class='ai4seo-buy-credits-button-container'>";
                echo ai4seo_wp_kses(
                    ai4seo_get_button_text_link_tag("#", "arrow-up-right-from-square", esc_html__("Get more Credits", "ai-for-seo"),
                        ($ai4seo_current_credits_balance < AI4SEO_BLUE_GET_MORE_CREDITS_BUTTON_THRESHOLD ? "ai4seo-success-button" : ""),
                        "ai4seo_open_get_more_credits_modal();")
                );
            echo "</div>";
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
        echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag($ai4seo_purchase_plan_url, "circle-up", esc_html__("Secure This Offer", "ai-for-seo"), "ai4seo-success-button"));
        echo "</p>";
        echo "</div>";
    }*/


    // ___________________________________________________________________________________________ \\
    // === SEO AUTOPILOT ========================================================================= \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    // find proper task scheduler status text
    if ($ai4seo_next_cron_job_call_diff >= 10) {
        $ai4seo_additional_sub_status_text = sprintf(esc_html__("The task is set to run in less than %u minutes.", "ai-for-seo"), ceil($ai4seo_next_cron_job_call_diff / 60));
    } else {
        $ai4seo_additional_sub_status_text = esc_html__("Task is scheduled to execute any moment.", "ai-for-seo");
    }

    // execute sooner link
    if ($ai4seo_next_cron_job_call_diff >= 70) {
        $ai4seo_additional_sub_status_text .= " " . ai4seo_wp_kses($ai4seo_execute_sooner_button);
    } else {
        $ai4seo_additional_sub_status_text .= " " . ai4seo_wp_kses($ai4seo_refresh_button);
    }

    // CARD
    echo "<div class='card ai4seo-card ai4seo-centered-card' style='min-height: 475px;'>";
        echo "<h4>" . esc_html__("SEO Autopilot (Bulk Generation)", "ai-for-seo") . "</h4>";

        echo "<div class='ai4seo-bulk-generation-status-container'>";
            if (!$ai4seo_is_any_bulk_generation_enabled) {
                echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is deactivated", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-inactive-logo'>";

                echo "<div class='ai4seo-bulk-generation-status-text'>";
                    echo esc_html__("Off");
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                    echo esc_html__("SEO Autopilot has not been set up yet.", "ai-for-seo");
                echo "</div>";
            } else if ($ai4seo_total_num_pending_posts == 0) {
                echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is active but idling", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-active-logo'>";

                echo "<div class='ai4seo-bulk-generation-status-text'>";
                    echo esc_html__("All done & idle");
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                    echo esc_html__("Waiting for new entries to process.", "ai-for-seo");
                echo "</div>";
            } else if ($ai4seo_insufficient_credits_balance) {
                echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is active but no credits available", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-active-logo'>";

                // triangle-exclamation on the top right corner
                echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
                    echo ai4seo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation"));
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-text'>";
                    echo esc_html__("Insufficient Credits");
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                    echo esc_html__("Not enough Credits available. Please get more Credits.", "ai-for-seo");
                echo "</div>";

            } else if ($ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago && $ai4seo_last_bulk_generation_run_was_long_ago) {
                echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is active but slow", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-active-logo'>";

                // triangle-exclamation on the top right corner
                echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
                    echo ai4seo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation"));
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-text'>";
                    echo esc_html__("Pending...");
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                    echo esc_html__("The last bulk generation run was longer ago than expected, which may indicate an issue with your cron job configuration. Please check your cron job settings to ensure consistent execution.", "ai-for-seo");
                    $ai4seo_pending_bulk_generation_tooltip_text = ai4seo_get_inefficient_cron_jobs_notice();
                    echo ai4seo_wp_kses(ai4seo_get_icon_with_tooltip_tag($ai4seo_pending_bulk_generation_tooltip_text));
                    if ($ai4seo_additional_sub_status_text) {
                        echo " " . ai4seo_wp_kses($ai4seo_additional_sub_status_text);
                    }
                echo "</div>";
            } else if (in_array($ai4seo_bulk_generation_status, ["initiating", "processing", "finished"]) && $ai4seo_last_bulk_generation_update_time) {
                echo "<div class='ai4seo-bulk-generation-status-animated-logo-container'>";
                    echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("512x512-animated")) . "' class='ai4seo-bulk-generation-status-animated-logo-pulse'>";
                    echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("512x512-animated")) . "' alt='" . esc_attr__("SEO Autopilot is processing", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-animated-logo'>";
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-text' style='color: #444;'>";
                    echo esc_html__("Processing");
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                    echo esc_html__("Please refresh this page and check the \"Recent Activity\" section for results.", "ai-for-seo");
                echo "</div>";
            } else if (in_array($ai4seo_bulk_generation_status, ["idle"]) && $ai4seo_last_bulk_generation_update_time) {
                // triangle-exclamation on the top right corner
                #echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
                #    echo ai4seo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation"));
                #echo "</div>";

                echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is active but not generating", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-active-logo'>";

                echo "<div class='ai4seo-bulk-generation-status-text' style='color: #444;'>";
                    echo esc_html__("Pending...");
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                    echo esc_html__("SEO Autopilot is active and looking for new entries to process. If this status persists for more than five minutes, please review your settings.", "ai-for-seo");

                if ($ai4seo_additional_sub_status_text) {
                    echo " " . ai4seo_wp_kses($ai4seo_additional_sub_status_text);
                }
                echo "</div>";

            // something went wrong, if we wait at least x seconds after setup without any activity
            } else if ($ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago) {
                echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is stuck", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-inactive-logo'>";

                // triangle-exclamation on the top right corner
                echo "<div class='ai4seo-bulk-generation-status-active-logo-triangle-exclamation'>";
                    echo ai4seo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation"));
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-text'>";
                    echo esc_html__("Error");
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                    echo esc_html__("Something went wrong. Please check your cron job settings and (cli) PHP error logs.", "ai-for-seo");

                    if ($ai4seo_additional_sub_status_text) {
                        echo " " . ai4seo_wp_kses($ai4seo_additional_sub_status_text);
                    }
                echo "</div>";
            } else {
                // waiting for task scheduler to start
                echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("256x256")) . "' alt='" . esc_attr__("SEO Autopilot is waiting for task scheduler to start", "ai-for-seo") . "' class='ai4seo-bulk-generation-status-active-logo'>";

                echo "<div class='ai4seo-bulk-generation-status-text'>";
                    echo esc_html__("Initializing...");
                echo "</div>";

                echo "<div class='ai4seo-bulk-generation-status-subtext'>";
                    echo esc_html__("Waiting for task scheduler to start.", "ai-for-seo");

                    if ($ai4seo_additional_sub_status_text) {
                        echo " " . ai4seo_wp_kses($ai4seo_additional_sub_status_text);
                    }
                echo "</div>";
            }

        echo "</div>";

        // Bulk Generation Buttons
        echo "<div class='ai4seo-bulk-generation-button-container'>";
            echo "<div class='ai4seo-buttons-wrapper'>";
                if ($ai4seo_is_any_bulk_generation_enabled) {
                    // stop SEO Autopilot
                    echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "stop-circle", esc_html__("Stop SEO Autopilot", "ai-for-seo"), "ai4seo-abort-button", "ai4seo_stop_bulk_generation(this)"));
                }
                echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "arrow-up-right-from-square", esc_html__("Set up SEO Autopilot", "ai-for-seo"), "", "ai4seo_open_modal_from_schema(\"seo-autopilot\", {modal_size: \"small\"});"));
            echo "</div>";
        echo "</div>";

    echo "</div>";


    // === Recent Activity ========================================================================== \\

    $ai4seo_latest_activity = get_option(AI4SEO_LATEST_ACTIVITY_OPTION_NAME, array());

    echo "<div class='card ai4seo-card' style='min-height: 475px;'>";
        echo "<h4>" . esc_html__("Recent Activity", "ai-for-seo") . "</h4>";

        if (!$ai4seo_latest_activity) {
            echo "<p style='font-style: italic; width: 100%; text-align: center;'>" . esc_html__("No recent activity. Try to generate metadata or media attributes first.", "ai-for-seo") . "</p>";
        } else {
            echo "<div class='ai4seo-latest-activity-container'>";
            foreach ($ai4seo_latest_activity AS $ai4seo_this_latest_activity_entry) {
                $ai4seo_this_tab_icon = AI4SEO_TAB_ICONS_BY_POST_TYPE[$ai4seo_this_latest_activity_entry["post_type"] ?? ""] ?? AI4SEO_TAB_ICONS_BY_POST_TYPE['default'];

                echo "<div class='ai4seo-latest-activity-item'>";
                    echo "<div class='ai4seo-latest-activity-item-icon'>";
                        echo ai4seo_wp_kses($ai4seo_this_tab_icon);
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
                            echo ai4seo_wp_kses(ai4seo_get_svg_tag("circle-check", esc_html__("Success", "ai-for-seo"), "ai4seo-gray-icon")) . " ";
                        } else {
                            echo "<div class='ai4seo-red-message'>";
                            echo ai4seo_wp_kses(ai4seo_get_svg_tag("circle-xmark", esc_html__("Error", "ai-for-seo"), "ai4seo-red-icon")) . " ";
                        }

                            // if details given, output them else the action
                            if (isset($ai4seo_this_latest_activity_entry["details"]) && $ai4seo_this_latest_activity_entry["details"]) {
                                echo mb_substr(esc_html($ai4seo_this_latest_activity_entry["details"]), 0, 160);
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

                            if (!$ai4seo_this_latest_activity_entry_is_success) {
                                echo ". " . esc_html__("Please click the edit button to try again and see the full error message.", "ai-for-seo");
                            }

                        echo "</div>";

                    echo "</div>";

                    if ($ai4seo_this_latest_activity_entry["cost"] ?? 0) {
                        echo "<div class='ai4seo-latest-activity-item-costs'>";
                            echo "-" . esc_html($ai4seo_this_latest_activity_entry["cost"]) . " " . esc_html__("Cr.", "ai-for-seo");
                        echo "</div>";
                    }

                    echo "<div class='ai4seo-latest-activity-item-buttons'>";
                        // see post / media preview
                        if (isset($ai4seo_this_latest_activity_entry["url"]) && $ai4seo_this_latest_activity_entry["url"]) {
                            echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag($ai4seo_this_latest_activity_entry["url"], "eye", "", "", "", "_blank"));
                        }

                        if (in_array(($ai4seo_this_latest_activity_entry["post_type"] ?? ""), $ai4seo_supported_attachment_post_types)) {
                            echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "pen-to-square", "", "", "ai4seo_open_attachment_attributes_editor_modal(" . esc_js($ai4seo_this_latest_activity_entry["post_id"]) . ");"));
                        } else {
                            if (isset($ai4seo_this_latest_activity_entry["url"]) && $ai4seo_this_latest_activity_entry["url"]) {
                                # todo: add source code button using ajax modal, and then only showing the header of the page
                                #echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "code", "", "", "ai4seo_open_view_source(\"" . esc_url($ai4seo_this_latest_activity_entry["url"]) . "\")"));
                            }
                            echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "pen-to-square", "", "", "ai4seo_open_metadata_editor_modal(" . esc_js($ai4seo_this_latest_activity_entry["post_id"]) . ");"));
                        }
                    echo "</div>";
                echo "</div>";
            }
            echo "</div>";
        }

        # todo: add show full log button

    echo "</div>";


    // === Money Back Guarantee ========================================================================== \\

    echo "<div class='card ai4seo-card'>";
        echo "<h4 style='cursor: pointer; margin-bottom: 0;' onclick='ai4seo_toggle_visibility(jQuery(this).next(), jQuery(this).find(\".ai4seo-caret-down\"), jQuery(this).find(\".ai4seo-caret-up\"), 200);'>";
            echo esc_html__("Guarantee", "ai-for-seo");
            echo "<div class='ai4seo-caret-down'>";
                echo ai4seo_wp_kses(ai4seo_get_svg_tag("caret-down"));
            echo "</div>";
            echo "<div class='ai4seo-caret-up' style='display: none;'>";
                echo ai4seo_wp_kses(ai4seo_get_svg_tag("caret-up"));
            echo "</div>";
        echo "</h4>";

        echo "<div style='display: none; margin-top: 1.5rem;'>";
            echo ai4seo_wp_kses(ai4seo_get_svg_tag("handshake", __("Guarantee", "ai-for-seo"), "ai4seo-handshake-icon")) . " ";
            # todo: hide if not applicable
            if (true) {
                ai4seo_output_money_back_guarantee_notice();
            }
        echo "</div>";
    echo "</div>";


    // === Plugin Recent Updates ========================================================================== \\

    echo "<div class='card ai4seo-card'>";
        echo "<h4 style='cursor: pointer; margin-bottom: 0;' onclick='ai4seo_toggle_visibility(jQuery(this).next(), jQuery(this).find(\".ai4seo-caret-down\"), jQuery(this).find(\".ai4seo-caret-up\"), 200);'>";
            echo esc_html__("Recent Plugin Updates", "ai-for-seo");
            echo "<div class='ai4seo-caret-down'>";
                echo ai4seo_wp_kses(ai4seo_get_svg_tag("caret-down"));
            echo "</div>";
            echo "<div class='ai4seo-caret-up' style='display: none;'>";
                echo ai4seo_wp_kses(ai4seo_get_svg_tag("caret-up"));
            echo "</div>";
        echo "</h4>";

        echo "<div class='ai4seo-recent-plugin-updates-content' style='display: none;'>";
            echo esc_html__("We update the plugin regularly to improve its performance and add new features. Please check the changelog for more information.", "ai-for-seo") . "<br><br><br>";

            // 08th April 2025: 2.0.2 released
            echo "<h4>" . esc_html("08th April 2025") . "</h4>";
            echo "<div class='ai4seo-bubble'>v.2.0.2</div>";
            echo '<ul>
                <li>* Improved Prefix & Suffix Support: Prefixes and suffixes are now correctly applied when using the "Generate with AI" button in both the Metadata Editor and the Attachment Attributes Editor.</li>
                <li>* Enhanced Mobile UX: Better responsiveness and usability on the Pages / Posts and Media Files views for mobile devices.</li>
                <li>* Account Page Improvements: Added direct buttons for managing your active subscription and customizing Pay-As-You-Go settings.</li>
                <li>* Updated Help Section: Improved help content and clearer "First Steps" guidance for new users.</li>
                <li>* Bug Fixes & Maintenance: Fixed 11 minor bugs, corrected typos, and implemented security updates.</li>';
            echo '</ul>';

            // 20th March 2025: 2.0.0 released
            echo "<h4>" . esc_html("20th March 2025") . "</h4>";
            echo "<div class='ai4seo-bubble'>v.2.0.0</div>";

            echo '<ul> 
                <li>* Complete UI/UX Overhaul: The look, feel, design, layout, and navigation of the plugin have been completely redesigned.</li>
                <li>* Enhanced Mobile Experience: Improved usability and user experience for mobile users.</li>
                <li>* New "Account" Page: Users can now manage their license key directly from this page.</li>
                <li>* Incognito Mode: SEO and web agencies can hide the plugin from other users/admins (available in the new "Account" page).</li>
                <li>* White-Label Feature: SEO and web agencies can rebrand the plugin with their own name or further hide it from other users/admins (available in the new "Account" page).</li>
                <li>* Customizable Generator Hints: Added a setting to modify or disable generator hints in the source code for additional privacy (available in the "Account" page).</li>
                <li>* Privacy & Data Policy Update: Moved to the new "Account" page.</li>
                <li>* New Metadata Customization Options: Added settings to apply prefixes and suffixes to metadata and media attributes.</li>
                <li>* Advanced Media Attribute Control: New setting allows users to specify which media attributes the plugin should use.</li>
                <li>* "SEO Autopilot" Feature: Replaces bulk generation checkboxes with a more intuitive and easy-to-use interface, directly accessible from the dashboard.</li>
                <li>* "Recent Activity" Dashboard Section: Track all manual and automatic metadata and media attribute generations in one place.</li>
                <li>* Implemented new ways to get credits:
                    <ol>
                        <li>* Introduced Credit Packs, allowing users to purchase additional credits as needed.</li>
                        <li>* Added a Pay-As-You-Go option for automatic credit refills when running low.</li>
                        <li>* All credit purchasing options are now combined in a "Get more Credits" modal, accessible from the dashboard.</li>
                    </ol>
                </li>
                <li>* "Guarantee" Section: Review our Guarantees and Refund Policy directly on the dashboard.</li>
                <li>* "Recent Plugin Updates" Section: Stay informed about the latest updates from the dashboard.</li>
                <li>* New "Support & Feedback" Section: Easily access support and provide feedback directly from the dashboard.</li>
                <li>* Tons more minor improvements, bug fixes, and performance enhancements.</li>
            </ul>';

            // 05th March 2025: v1.2.15 released
            echo "<h4>" . esc_html("05th March 2025") . "</h4>";
            echo "<div class='ai4seo-bubble'>v.1.2.15</div>";
            echo '<ul>
                <li>* Fixed a bug where the plugin would not recognize the correct post type for media files.</li>
                </ul>';

            // 12th February 2025: v1.2.14 released
            echo "<h4>" . esc_html("12th February 2025") . "</h4>";
            echo "<div class='ai4seo-bubble'>v.1.2.14</div>";
            echo '<ul>
                <li>* Added a setting to control whether entries with a complete metadata set are ignored during bulk generation (default) or included, overwriting all of their existing metadata.</li>
                <li>* Added a setting to control whether entries with a complete media attribute set are ignored during bulk generation (default) or included, overwriting all of their existing media attributes.</li>
                <li>* Added a setting called "Bulk Generation Duration" in Help > Troubleshooting to adjust the runtime of a single bulk generation process. This can be useful in cases where server limitations impact processing.</li>
            </ul>';

        echo "</div>";
    echo "</div>";


    // === Ask for feedback ========================================================================== \\

    echo "<div class='card ai4seo-card'>";
        echo "<h4 style='cursor: pointer; margin-bottom: 0;' onclick='ai4seo_toggle_visibility(jQuery(this).next(), jQuery(this).find(\".ai4seo-caret-down\"), jQuery(this).find(\".ai4seo-caret-up\"), 200);'>";
            echo esc_html__("Support & Feedback", "ai-for-seo");
            echo "<div class='ai4seo-caret-down'>";
                echo ai4seo_wp_kses(ai4seo_get_svg_tag("caret-down"));
            echo "</div>";
            echo "<div class='ai4seo-caret-up' style='display: none;'>";
                echo ai4seo_wp_kses(ai4seo_get_svg_tag("caret-up"));
            echo "</div>";
        echo "</h4>";

        echo "<div style='display: none; margin-top: 1.5rem;'>";

            // HELP SECTION
            // icon
            echo ai4seo_wp_kses(ai4seo_get_svg_tag("circle-question", __("Help section", "ai-for-seo"), "ai4seo-big-paragraph-icon")) . " ";

            echo ai4seo_wp_kses(sprintf(
                /* translators: %s is a clickable email address */
                __("Check our <a href='%s'>help section</a> for a detailed <a href='%s'>getting started guide</a>, our organized <a href='%s'>F.A.Q</a> or other <a href='%s'>useful links</a>.", "ai-for-seo"),
                esc_url(ai4seo_get_admin_url("help")),
                esc_url(ai4seo_get_admin_url("help") . "#ai4seo-getting-started-section"),
                esc_url(ai4seo_get_admin_url("help") . "#ai4seo-faq-section"),
                esc_url(ai4seo_get_admin_url("help") . "#ai4seo-links-section")
            ));

            echo "<br><br>";

            // button to help section
            echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag(esc_url(ai4seo_get_admin_url("help")), "arrow-up-right-from-square", esc_html__("Go to our help section", "ai-for-seo")));

            echo "<br><br><br>";

            // CONTACT US
            // icon
            echo ai4seo_wp_kses(ai4seo_get_svg_tag("rocket-chat", __("Contact us", "ai-for-seo"), "ai4seo-big-paragraph-icon")) . " ";

            echo ai4seo_wp_kses(sprintf(
                /* translators: %s is a clickable email address */
                __("Missing a feature, need assistance, or looking for a quote?", "ai-for-seo") . " " .
                __("Please <a href='%s' target='blank'>contact us</a>. We offer support in any language.", "ai-for-seo"),
                esc_url(AI4SEO_OFFICIAL_CONTACT_URL)
            ));

            echo "<br><br>";

            // button to contact us
            echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag(esc_url(AI4SEO_OFFICIAL_CONTACT_URL), "arrow-up-right-from-square", esc_html__("Contact us", "ai-for-seo"), "", "", "_blank"));

            echo "<br><br><br>";

            // RATE US
            // icon
            echo ai4seo_wp_kses(ai4seo_get_svg_tag("star", __("Rate us", "ai-for-seo"), "ai4seo-big-paragraph-icon")) . " ";

            //  like our plugin rate us at AI4SEO_OFFICIAL_WORDPRESS_ORG_PAGE
            echo ai4seo_wp_kses(sprintf(
                __("Like our plugin and want to support us? Please <a href='%s' target='blank'>rate us</a> on WordPress.org. We appreciate your feedback!", "ai-for-seo"),
                esc_url(AI4SEO_RATE_US_URL)
            ));

            echo "<br><br>";

            // button to rate us
            echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag(esc_url(AI4SEO_RATE_US_URL), "arrow-up-right-from-square", esc_html__("Rate us", "ai-for-seo"), "", "", "_blank"));

        echo "</div>";
    echo "</div>";

echo "</div>";